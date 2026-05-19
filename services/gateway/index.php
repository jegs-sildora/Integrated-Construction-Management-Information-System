<?php
/**
 * --- API Gateway Configuration (v1.5 - Guaranteed Output Integrity) ---
 */

// Step 1: Prevent any accidental output before final JSON
ob_start();

// Step 2: Global Configuration
$services = [
    'auth'        => rtrim(getenv('AUTH_SERVICE_URL')        ?: 'https://icmis-auth.onrender.com', '/'),
    'project'     => rtrim(getenv('PROJECT_SERVICE_URL')     ?: 'https://icmis-project.onrender.com', '/'),
    'budget'      => rtrim(getenv('BUDGET_SERVICE_URL')      ?: 'https://icmis-budget.onrender.com', '/'),
    'procurement' => rtrim(getenv('PROCUREMENT_SERVICE_URL') ?: 'https://icmis-procurement.onrender.com', '/'),
    'workforce'   => rtrim(getenv('WORKFORCE_SERVICE_URL')   ?: 'https://icmis-workforce.onrender.com', '/'),
    'reports'     => rtrim(getenv('REPORTS_SERVICE_URL')     ?: 'https://icmis-reports.onrender.com', '/'),
    'admin'       => rtrim(getenv('ADMIN_SERVICE_URL')       ?: 'https://icmis-admin.onrender.com', '/')
];

$requestUri = $_SERVER['REQUEST_URI'];
$method     = $_SERVER['REQUEST_METHOD'];

// --- Health Check Logic ---
if (strpos($requestUri, '/api/v1/gateway/health') !== false) {
    ob_end_clean();
    header('Content-Type: application/json; charset=utf-8');
    $results = [];
    foreach ($services as $name => $url) {
        $ch = curl_init($url . '/index.php');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 3);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_exec($ch);
        $results[$name] = ['code' => curl_getinfo($ch, CURLINFO_HTTP_CODE), 'url' => $url];
    }
    echo json_encode(['status' => 'online', 'backends' => $results]);
    exit;
}

// Step 3: Route Parsing
$path = parse_url($requestUri, PHP_URL_PATH);
$parts = explode('/', trim($path, '/'));

if (count($parts) < 3 || $parts[0] !== 'api' || $parts[1] !== 'v1') {
    ob_end_clean();
    http_response_code(404);
    echo json_encode(['error' => 'Route not found', 'path' => $path]);
    exit;
}

$serviceKey = $parts[2];
if (!isset($services[$serviceKey])) {
    ob_end_clean();
    http_response_code(404);
    echo json_encode(['error' => "Service '$serviceKey' unknown"]);
    exit;
}

$subPath = implode('/', array_slice($parts, 3)) ?: 'index';
$targetUrl = $services[$serviceKey] . '/api/v1/' . $subPath . '.php';

if (!empty($_SERVER['QUERY_STRING'])) {
    $targetUrl .= '?' . $_SERVER['QUERY_STRING'];
}

// Step 4: Internal Authentication
$isPublic = ($serviceKey === 'auth' && in_array($subPath, ['login', 'signup', 'index']));

if (!$isPublic) {
    $headers = array_change_key_case(apache_request_headers(), CASE_LOWER);
    $jwt = $headers['authorization'] ?? '';
    
    if (empty($jwt)) {
        ob_end_clean();
        http_response_code(401);
        echo json_encode(['error' => 'Authentication required']);
        exit;
    }

    $vch = curl_init($services['auth'] . '/api/v1/validate.php');
    curl_setopt($vch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($vch, CURLOPT_HTTPHEADER, ["Authorization: $jwt"]);
    curl_setopt($vch, CURLOPT_SSL_VERIFYPEER, false);
    $vRes = curl_exec($vch);
    if (curl_getinfo($vch, CURLINFO_HTTP_CODE) !== 200) {
        ob_end_clean();
        http_response_code(401);
        echo $vRes ?: json_encode(['error' => 'Invalid session']);
        exit;
    }
}

// Step 5: Core Proxy Request
$ch = curl_init($targetUrl);
$fwdHeaders = [];
foreach (apache_request_headers() as $k => $v) {
    if (!in_array(strtolower($k), ['host', 'content-length', 'connection', 'expect'])) {
        $fwdHeaders[] = "$k: $v";
    }
}

curl_setopt_array($ch, [
    CURLOPT_CUSTOMREQUEST  => $method,
    CURLOPT_HTTPHEADER     => $fwdHeaders,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HEADER         => false, // CRITICAL: Only return the body to prevent JSON corruption
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_SSL_VERIFYPEER => false,
    CURLOPT_TIMEOUT        => 30
]);

if (in_array($method, ['POST', 'PUT', 'DELETE'])) {
    curl_setopt($ch, CURLOPT_POSTFIELDS, file_get_contents('php://input'));
}

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

if ($response === false) {
    ob_end_clean();
    http_response_code(502);
    echo json_encode(['error' => 'Backend Service Offline', 'details' => curl_error($ch)]);
    exit;
}

// Step 6: Pure Output Generation
ob_end_clean();
header('Content-Type: application/json; charset=utf-8');
http_response_code($httpCode);
echo $response;
