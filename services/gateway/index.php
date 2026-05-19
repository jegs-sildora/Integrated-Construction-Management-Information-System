<?php
/**
 * --- API Gateway Configuration (v1.3 - Ultra Robust) ---
 */

// Force UTF-8 and JSON
ob_start();
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *'); 
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-CSRF-Token');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    ob_end_clean();
    http_response_code(204);
    exit;
}

// 1. Service Mapping
$services = [
    'auth' => getenv('AUTH_SERVICE_URL') ?: 'https://icmis-auth.onrender.com',
    'project' => getenv('PROJECT_SERVICE_URL') ?: 'https://icmis-project.onrender.com',
    'budget' => getenv('BUDGET_SERVICE_URL') ?: 'https://icmis-budget.onrender.com',
    'procurement' => getenv('PROCUREMENT_SERVICE_URL') ?: 'https://icmis-procurement.onrender.com',
    'workforce' => getenv('WORKFORCE_SERVICE_URL') ?: 'https://icmis-workforce.onrender.com',
    'reports' => getenv('REPORTS_SERVICE_URL') ?: 'https://icmis-reports.onrender.com',
    'admin' => getenv('ADMIN_SERVICE_URL') ?: 'https://icmis-admin.onrender.com'
];

$requestUri = $_SERVER['REQUEST_URI'];
$method = $_SERVER['REQUEST_METHOD'];

// --- Diagnostic Endpoint ---
if (strpos($requestUri, '/api/v1/gateway/health') !== false) {
    ob_end_clean();
    $results = [];
    foreach ($services as $name => $url) {
        $ch = curl_init($url . '/index.php');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 3);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
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

// 2. Path Resolution
$path = parse_url($requestUri, PHP_URL_PATH);
$pathParts = explode('/', trim($path, '/'));

// We expect /api/v1/{service}/{endpoint}
if (count($pathParts) < 3 || $pathParts[0] !== 'api' || $pathParts[1] !== 'v1') {
    ob_end_clean();
    http_response_code(404);
    echo json_encode(['error' => 'Invalid API route', 'path' => $path]);
    exit;
}

$serviceKey = $pathParts[2];
$subPath = implode('/', array_slice($pathParts, 3)) ?: 'index';

if (!isset($services[$serviceKey])) {
    ob_end_clean();
    http_response_code(404);
    echo json_encode(['error' => "Service '$serviceKey' not recognized"]);
    exit;
}

$targetUrl = rtrim($services[$serviceKey], '/') . '/api/v1/' . $subPath . '.php';
if (!empty($_SERVER['QUERY_STRING'])) {
    $targetUrl .= '?' . $_SERVER['QUERY_STRING'];
}

// 3. Auth Middleware
$isPublic = ($serviceKey === 'auth' && in_array($subPath, ['login', 'signup', 'index']));

if (!$isPublic) {
    $headers = array_change_key_case(apache_request_headers(), CASE_LOWER);
    $authHeader = $headers['authorization'] ?? '';
    
    if (empty($authHeader)) {
        ob_end_clean();
        http_response_code(401);
        echo json_encode(['error' => 'Missing Authorization Header']);
        exit;
    }

    $ch = curl_init(rtrim($services['auth'], '/') . '/api/v1/validate.php');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ["Authorization: $authHeader"]);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    $authRes = curl_exec($ch);
    if (curl_getinfo($ch, CURLINFO_HTTP_CODE) !== 200) {
        ob_end_clean();
        http_response_code(401);
        echo $authRes ?: json_encode(['error' => 'Authentication Failed']);
        exit;
    }
}

// 4. Proxy Request
$ch = curl_init($targetUrl);
$forwardHeaders = [];
foreach (apache_request_headers() as $key => $val) {
    if (!in_array(strtolower($key), ['host', 'content-length', 'connection', 'expect'])) {
        $forwardHeaders[] = "$key: $val";
    }
}

curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
curl_setopt($ch, CURLOPT_HTTPHEADER, $forwardHeaders);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_TIMEOUT, 30);

if (in_array($method, ['POST', 'PUT', 'DELETE', 'PATCH'])) {
    curl_setopt($ch, CURLOPT_POSTFIELDS, file_get_contents('php://input'));
}

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlError = curl_error($ch);

if ($response === false) {
    ob_end_clean();
    http_response_code(502);
    echo json_encode([
        'error' => "Backend Unreachable",
        'service' => $serviceKey,
        'url' => $targetUrl,
        'curl_error' => $curlError
    ]);
    exit;
}

// Final output
ob_end_clean();
http_response_code($httpCode);
echo $response;
