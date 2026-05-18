<?php
/**
 * --- API Gateway Configuration (v1.1 - Hardened) ---
 */

// 1. Strict CORS Policy
header('Access-Control-Allow-Origin: *'); 
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-CSRF-Token');
header('Access-Control-Max-Age: 86400'); 

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
$headers = apache_request_headers();
$normalizedHeaders = array_change_key_case($headers, CASE_LOWER);

$path = parse_url($requestUri, PHP_URL_PATH);
$scriptName = $_SERVER['SCRIPT_NAME'];
if (strpos($path, $scriptName) === 0) {
    $path = substr($path, strlen($scriptName));
}
$pathParts = explode('/', ltrim($path, '/'));

if (count($pathParts) < 3 || $pathParts[0] !== 'api' || $pathParts[1] !== 'v1') {
    http_response_code(404);
    echo json_encode(['error' => 'Invalid API route', 'path' => $path]);
    exit;
}

$serviceKey = $pathParts[2];
$subPathParts = array_slice($pathParts, 3);

if (!isset($services[$serviceKey])) {
    http_response_code(404);
    echo json_encode(['error' => "Service '$serviceKey' not found"]);
    exit;
}

$fileName = count($subPathParts) > 0 ? implode('/', $subPathParts) : 'index';
$targetBaseUrl = $services[$serviceKey];
$targetUrl = rtrim($targetBaseUrl, '/') . '/api/v1/' . $fileName . '.php';

if (!empty($_SERVER['QUERY_STRING'])) {
    $targetUrl .= '?' . $_SERVER['QUERY_STRING'];
}

// --- Authentication Middleware ---
$publicRoutes = ['auth' => ['login', 'signup']];
$isPublicRoute = isset($publicRoutes[$serviceKey]) && in_array($fileName, $publicRoutes[$serviceKey]);

if (!$isPublicRoute) {
    $authHeader = $normalizedHeaders['authorization'] ?? '';
    if (empty($authHeader)) {
        http_response_code(401);
        echo json_encode(['error' => 'Authorization header missing']);
        exit;
    }

    $ch = curl_init(rtrim($services['auth'], '/') . '/api/v1/validate.php');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ["Authorization: $authHeader"]);
    $authResponse = curl_exec($ch);
    $authStatus = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    if ($authStatus !== 200) {
        http_response_code(401);
        echo $authResponse ?: json_encode(['error' => 'Invalid token']);
        exit;
    }
    
    $authData = json_decode($authResponse, true);
    $userData = $authData['user'] ?? [];
    $headers['X-User-ID'] = $userData['user_id'] ?? '';
    $headers['X-User-Role'] = $userData['user_role'] ?? '';
    $headers['X-User-Name'] = $userData['user_name'] ?? '';
}

// --- Reverse Proxy ---
$ch = curl_init($targetUrl);
$forwardHeaders = [];
foreach ($headers as $key => $value) {
    $lowerKey = strtolower($key);
    if (!in_array($lowerKey, ['host', 'content-length', 'expect', 'connection', 'transfer-encoding'])) {
        $forwardHeaders[] = "$key: $value";
    }
}

curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
curl_setopt($ch, CURLOPT_HTTPHEADER, $forwardHeaders);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HEADER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 30);

if (in_array($method, ['POST', 'PUT', 'PATCH', 'DELETE'])) {
    $input = file_get_contents('php://input');
    if (!empty($input)) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, $input);
    }
}

$response = curl_exec($ch);

if ($response === false) {
    $error = curl_error($ch);
    http_response_code(502);
    echo json_encode([
        'error' => "Bad Gateway: Service '$serviceKey' unreachable", 
        'details' => $error, 
        'target_url' => $targetUrl,
        'resolved_service_base' => $services[$serviceKey]
    ]);
    exit;
}

$headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$resHeaders = substr($response, 0, $headerSize);
$resBody = substr($response, $headerSize);

$headerLines = explode("\r\n", $resHeaders);
foreach ($headerLines as $line) {
    if (!empty($line) && !preg_match('/^(Transfer-Encoding|Connection|Content-Length|HTTP\/)/i', $line)) {
        header($line);
    }
}

http_response_code($httpCode);
echo $resBody;
