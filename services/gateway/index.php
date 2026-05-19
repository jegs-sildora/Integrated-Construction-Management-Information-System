<?php
/**
 * --- API Gateway Configuration (v1.8 - PURE BODY PROXY) ---
 */

error_reporting(0);
ini_set('display_errors', 0);

// Nuke all output buffers
while (ob_get_level()) { ob_end_clean(); }
ob_start();

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

// Standardize route parsing
$path = parse_url($requestUri, PHP_URL_PATH);
$parts = explode('/', trim($path, '/'));

if (count($parts) < 3 || $parts[0] !== 'api' || $parts[1] !== 'v1') {
    http_response_code(404);
    die(json_encode(['success' => false, 'error' => 'Route mismatch']));
}

$serviceKey = $parts[2];
if (!isset($services[$serviceKey])) {
    http_response_code(404);
    die(json_encode(['success' => false, 'error' => 'Service unknown']));
}

$subPath = implode('/', array_slice($parts, 3)) ?: 'index';
$targetUrl = $services[$serviceKey] . '/api/v1/' . $subPath . '.php';

if (!empty($_SERVER['QUERY_STRING'])) {
    $targetUrl .= '?' . $_SERVER['QUERY_STRING'];
}

// 1. Internal Security
$isPublic = ($serviceKey === 'auth' && in_array($subPath, ['login', 'signup', 'index', 'reset_admin']));
if (!$isPublic) {
    $headers = array_change_key_case(apache_request_headers(), CASE_LOWER);
    $jwt = $headers['authorization'] ?? '';
    if (empty($jwt)) {
        http_response_code(401);
        die(json_encode(['success' => false, 'error' => 'Auth required']));
    }
    $vch = curl_init($services['auth'] . '/api/v1/validate.php');
    curl_setopt($vch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($vch, CURLOPT_HTTPHEADER, ["Authorization: $jwt"]);
    curl_setopt($vch, CURLOPT_SSL_VERIFYPEER, false);
    $vRes = curl_exec($vch);
    if (curl_getinfo($vch, CURLINFO_HTTP_CODE) !== 200) {
        http_response_code(401);
        die(json_encode(['success' => false, 'error' => 'Invalid session']));
    }
}

// 2. Proxy Execution (Body Only)
$ch = curl_init($targetUrl);
$fwdHeaders = [];
foreach (apache_request_headers() as $k => $v) {
    $lowK = strtolower($k);
    if (!in_array($lowK, ['host', 'content-length', 'connection', 'expect', 'accept-encoding'])) {
        $fwdHeaders[] = "$k: $v";
    }
}

curl_setopt_array($ch, [
    CURLOPT_CUSTOMREQUEST  => $method,
    CURLOPT_HTTPHEADER     => $fwdHeaders,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HEADER         => false, // DO NOT INJECT HEADERS INTO BODY
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
    http_response_code(502);
    echo json_encode(['success' => false, 'error' => 'Node offline', 'details' => curl_error($ch)]);
} else {
    ob_clean();
    header('Content-Type: application/json; charset=utf-8');
    http_response_code($httpCode);
    echo $response;
}
exit;
