<?php
/**
 * --- API Gateway Configuration (v1.2 - Expert Hardened) ---
 */

header('Access-Control-Allow-Origin: *'); 
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-CSRF-Token');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

$services = [
    'auth' => getenv('AUTH_SERVICE_URL') ?: 'http://icmis-auth',
    'project' => getenv('PROJECT_SERVICE_URL') ?: 'http://icmis-project',
    'budget' => getenv('BUDGET_SERVICE_URL') ?: 'http://icmis-budget',
    'procurement' => getenv('PROCUREMENT_SERVICE_URL') ?: 'http://icmis-procurement',
    'workforce' => getenv('WORKFORCE_SERVICE_URL') ?: 'http://icmis-workforce',
    'reports' => getenv('REPORTS_SERVICE_URL') ?: 'http://icmis-reports',
    'admin' => getenv('ADMIN_SERVICE_URL') ?: 'http://icmis-admin'
];

$requestUri = $_SERVER['REQUEST_URI'];
$method = $_SERVER['REQUEST_METHOD'];

// --- Diagnostic Endpoint ---
if (strpos($requestUri, '/api/v1/gateway/health') !== false) {
    $results = [];
    foreach ($services as $name => $url) {
        $ch = curl_init($url . '/index.php');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 2);
        curl_exec($ch);
        $results[$name] = [
            'url' => $url,
            'http_code' => curl_getinfo($ch, CURLINFO_HTTP_CODE),
            'error' => curl_error($ch)
        ];
    }
    echo json_encode(['gateway' => 'online', 'backends' => $results]);
    exit;
}

$path = parse_url($requestUri, PHP_URL_PATH);
$pathParts = explode('/', ltrim($path, '/'));

// Handle /api/v1 prefix
if (count($pathParts) < 3 || $pathParts[0] !== 'api' || $pathParts[1] !== 'v1') {
    http_response_code(404);
    echo json_encode(['error' => 'Invalid API route', 'hint' => 'Routes must start with /api/v1/']);
    exit;
}

$serviceKey = $pathParts[2];
$subPathParts = array_slice($pathParts, 3);

if (!isset($services[$serviceKey])) {
    http_response_code(404);
    echo json_encode(['error' => "Service '$serviceKey' not recognized by Gateway"]);
    exit;
}

$fileName = count($subPathParts) > 0 ? implode('/', $subPathParts) : 'index';
$targetUrl = rtrim($services[$serviceKey], '/') . '/api/v1/' . $fileName . '.php';

if (!empty($_SERVER['QUERY_STRING'])) {
    $targetUrl .= '?' . $_SERVER['QUERY_STRING'];
}

// --- Auth Bypass for Login/Signup ---
$isPublic = ($serviceKey === 'auth' && in_array($fileName, ['login', 'signup', 'index']));

if (!$isPublic) {
    $headers = array_change_key_case(apache_request_headers(), CASE_LOWER);
    $authHeader = $headers['authorization'] ?? '';
    
    if (empty($authHeader)) {
        http_response_code(401);
        echo json_encode(['error' => 'Missing Authorization Header']);
        exit;
    }

    $ch = curl_init(rtrim($services['auth'], '/') . '/api/v1/validate.php');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ["Authorization: $authHeader"]);
    $authRes = curl_exec($ch);
    if (curl_getinfo($ch, CURLINFO_HTTP_CODE) !== 200) {
        http_response_code(401);
        echo $authRes ?: json_encode(['error' => 'Authentication Failed']);
        exit;
    }
}

// --- Forward Request ---
$ch = curl_init($targetUrl);
$forwardHeaders = [];
foreach (apache_request_headers() as $key => $val) {
    if (!in_array(strtolower($key), ['host', 'content-length', 'connection'])) {
        $forwardHeaders[] = "$key: $val";
    }
}

curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
curl_setopt($ch, CURLOPT_HTTPHEADER, $forwardHeaders);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HEADER, true);

if (in_array($method, ['POST', 'PUT', 'DELETE'])) {
    curl_setopt($ch, CURLOPT_POSTFIELDS, file_get_contents('php://input'));
}

$response = curl_exec($ch);

if ($response === false) {
    http_response_code(502);
    echo json_encode([
        'error' => "Backend Service Unreachable",
        'service' => $serviceKey,
        'debug_url' => $targetUrl,
        'curl_error' => curl_error($ch)
    ]);
    exit;
}

$headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
$resBody = substr($response, $headerSize);
http_response_code(curl_getinfo($ch, CURLINFO_HTTP_CODE));
echo $resBody;
