<?php
// --- API Gateway Configuration (v1) ---
$services = [
    'auth' => 'http://auth-service',
    'project' => 'http://project-service',
    'budget' => 'http://budget-service',
    'procurement' => 'http://procurement-service',
    'workforce' => 'http://workforce-service'
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
$subPath = implode('/', array_slice($pathParts, 3));

if (!isset($services[$serviceKey])) {
    http_response_code(404);
    echo json_encode(['error' => "Service '$serviceKey' not found"]);
    exit;
}

$targetBaseUrl = $services[$serviceKey];
// Map to v1 file structure: http://service/api/v1/{subPath}.php
$targetUrl = $targetBaseUrl . '/api/v1/' . $subPath . '.php';
if (!empty($_SERVER['QUERY_STRING'])) {
    $targetUrl .= '?' . $_SERVER['QUERY_STRING'];
}

// --- Authentication Middleware ---
// Public routes do not require JWT
$publicRoutes = [
    'auth' => ['login', 'signup']
];
$isPublicRoute = isset($publicRoutes[$serviceKey]) && in_array($subPath, $publicRoutes[$serviceKey]);

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

if ($method === 'POST' || $method === 'PUT' || $method === 'PATCH') {
    curl_setopt($ch, CURLOPT_POSTFIELDS, file_get_contents('php://input'));
}

$response = curl_exec($ch);
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
