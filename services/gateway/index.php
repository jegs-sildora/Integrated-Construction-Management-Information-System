<?php
/**
 * --- API Gateway Configuration (v1.1 - Hardened) ---
 */

// 1. Strict CORS Policy
header('Access-Control-Allow-Origin: *'); // In production, replace * with the actual domain
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-CSRF-Token');
header('Access-Control-Max-Age: 86400'); // 24 hours cache

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

// 2. Payload Size Restriction (e.g., 5MB limit)
$maxPayloadSize = 5 * 1024 * 1024;
$contentLength = $_SERVER['CONTENT_LENGTH'] ?? 0;
if ($contentLength > $maxPayloadSize) {
    http_response_code(413);
    echo json_encode(['error' => 'Payload too large. Maximum size is 5MB.']);
    exit;
}

// 3. Basic Rate Limiting (Simple File-Based Implementation)
$ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
$rateLimitFile = sys_get_temp_dir() . '/ratelimit_' . md5($ip);
$limit = 1000; // requests
$window = 60; // seconds

$requests = [];
if (file_exists($rateLimitFile)) {
    $requests = json_decode(file_get_contents($rateLimitFile), true) ?: [];
}

// Filter requests within the window
$now = time();
$requests = array_filter($requests, function($timestamp) use ($now, $window) {
    return $timestamp > ($now - $window);
});

if (count($requests) >= $limit) {
    http_response_code(429);
    header('Retry-After: ' . ($window - ($now - reset($requests))));
    echo json_encode(['error' => 'Too many requests. Please try again later.']);
    exit;
}

$requests[] = $now;
file_put_contents($rateLimitFile, json_encode($requests));

$services = [
    'auth' => getenv('AUTH_SERVICE_URL') ?: 'http://auth-service',
    'project' => getenv('PROJECT_SERVICE_URL') ?: 'http://project-service',
    'budget' => getenv('BUDGET_SERVICE_URL') ?: 'http://budget-service',
    'procurement' => getenv('PROCUREMENT_SERVICE_URL') ?: 'http://procurement-service',
    'workforce' => getenv('WORKFORCE_SERVICE_URL') ?: 'http://workforce-service',
    'reports' => getenv('REPORTS_SERVICE_URL') ?: 'http://reports-service'
];

$requestUri = $_SERVER['REQUEST_URI'];
$method = $_SERVER['REQUEST_METHOD'];
$headers = apache_request_headers();

// Standardize path: /api/v1/{service}/{path}
$path = parse_url($requestUri, PHP_URL_PATH);
$pathParts = explode('/', ltrim($path, '/'));

// Check for /api/v1/ prefix
if (count($pathParts) < 3 || $pathParts[0] !== 'api' || $pathParts[1] !== 'v1') {
    http_response_code(404);
    echo json_encode(['error' => 'Invalid API route. Use /api/v1/{service}/{endpoint}']);
    exit;
}

$serviceKey = $pathParts[2];
$subPathParts = array_slice($pathParts, 3);

if (!isset($services[$serviceKey])) {
    http_response_code(404);
    echo json_encode(['error' => "Service '$serviceKey' not found"]);
    exit;
}

// REST-style ID handling: if last part is numeric, treat it as fetch_id
$fetchId = null;
if (count($subPathParts) > 0 && is_numeric(end($subPathParts))) {
    $fetchId = array_pop($subPathParts);
}

$fileName = count($subPathParts) > 0 ? implode('/', $subPathParts) : 'index';
$targetBaseUrl = $services[$serviceKey];

// Map to v1 file structure: http://service/api/v1/{fileName}.php
$targetUrl = $targetBaseUrl . '/api/v1/' . $fileName . '.php';

$queryParams = [];
if (!empty($_SERVER['QUERY_STRING'])) {
    parse_str($_SERVER['QUERY_STRING'], $queryParams);
}

if ($fetchId !== null) {
    // Standardize fetch_id param for downstream services
    $queryParams['fetch_id'] = $fetchId;
    $queryParams['id'] = $fetchId; // Support both patterns
}

if (!empty($queryParams)) {
    $targetUrl .= '?' . http_build_query($queryParams);
}

// --- Authentication Middleware ---
// Public routes do not require JWT
$publicRoutes = [
    'auth' => ['login', 'signup']
];
$isPublicRoute = isset($publicRoutes[$serviceKey]) && in_array($fileName, $publicRoutes[$serviceKey]);

if (!$isPublicRoute) {
    $authHeader = $headers['Authorization'] ?? '';
    if (empty($authHeader)) {
        http_response_code(401);
        echo json_encode(['error' => 'Authorization header missing']);
        exit;
    }

    // Call Auth Service internally to validate token (v1)
    $ch = curl_init($services['auth'] . '/api/v1/validate.php');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ["Authorization: $authHeader"]);
    $authResponse = curl_exec($ch);
    $authStatus = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    if ($authStatus !== 200) {
        http_response_code(401);
        echo $authResponse ?: json_encode(['error' => 'Invalid token']);
        exit;
    }
    
    // Token is valid. Inject user data into headers for downstream services.
    $userData = json_decode($authResponse, true)['user'] ?? [];
    $headers['X-User-ID'] = $userData['user_id'] ?? '';
    $headers['X-User-Role'] = $userData['user_role'] ?? '';
    $headers['X-User-Name'] = $userData['user_name'] ?? '';
}

// --- Reverse Proxy ---
$ch = curl_init($targetUrl);

// Prepare headers for forwarding
$forwardHeaders = [];
foreach ($headers as $key => $value) {
    if (!in_array(strtolower($key), ['host', 'content-length', 'expect'])) {
        $forwardHeaders[] = "$key: $value";
    }
}

curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
curl_setopt($ch, CURLOPT_HTTPHEADER, $forwardHeaders);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HEADER, true);

if ($method === 'POST' || $method === 'PUT' || $method === 'PATCH' || $method === 'DELETE') {
    curl_setopt($ch, CURLOPT_POSTFIELDS, file_get_contents('php://input'));
}

$response = curl_exec($ch);

if ($response === false) {
    $error = curl_error($ch);
    http_response_code(502);
    echo json_encode(['error' => "Bad Gateway: Service '$serviceKey' unreachable", 'details' => $error]);
    exit;
}

$headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

$resHeaders = substr($response, 0, $headerSize);
$resBody = substr($response, $headerSize);

// Forward response headers
$headerLines = explode("\r\n", $resHeaders);
foreach ($headerLines as $line) {
    if (!empty($line) && !preg_match('/^(Transfer-Encoding|Connection|Content-Length):/i', $line)) {
        header($line);
    }
}

http_response_code($httpCode);
echo $resBody;
