<?php
/**
 * --- API Gateway Configuration (v1.4 - Zero-Trash Production Proxy) ---
 */

// 1. Initialize Buffer to prevent stray output
ob_start();

// 2. Dynamic Service Discovery
$services = [
    'auth'        => getenv('AUTH_SERVICE_URL')        ?: 'https://icmis-auth.onrender.com',
    'project'     => getenv('PROJECT_SERVICE_URL')     ?: 'https://icmis-project.onrender.com',
    'budget'      => getenv('BUDGET_SERVICE_URL')      ?: 'https://icmis-budget.onrender.com',
    'procurement' => getenv('PROCUREMENT_SERVICE_URL') ?: 'https://icmis-procurement.onrender.com',
    'workforce'   => getenv('WORKFORCE_SERVICE_URL')   ?: 'https://icmis-workforce.onrender.com',
    'reports'     => getenv('REPORTS_SERVICE_URL')     ?: 'https://icmis-reports.onrender.com',
    'admin'       => getenv('ADMIN_SERVICE_URL')       ?: 'https://icmis-admin.onrender.com'
];

$requestUri = $_SERVER['REQUEST_URI'];
$method     = $_SERVER['REQUEST_METHOD'];

// --- Diagnostic Endpoint ---
if (strpos($requestUri, '/api/v1/gateway/health') !== false) {
    ob_end_clean();
    header('Content-Type: application/json');
    $results = [];
    foreach ($services as $name => $url) {
        $ch = curl_init($url . '/index.php');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 3);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_exec($ch);
        $results[$name] = [
            'http_code' => curl_getinfo($ch, CURLINFO_HTTP_CODE),
            'endpoint'  => $url
        ];
    }
    echo json_encode(['gateway' => 'online', 'backends' => $results]);
    exit;
}

// 3. Parse Route
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
$targetUrl = rtrim($services[$serviceKey], '/') . '/api/v1/' . $subPath . '.php';

if (!empty($_SERVER['QUERY_STRING'])) {
    $targetUrl .= '?' . $_SERVER['QUERY_STRING'];
}

// 4. Handle Public vs Private Routes
$isPublic = ($serviceKey === 'auth' && in_array($subPath, ['login', 'signup', 'index']));

if (!$isPublic) {
    $allHeaders = array_change_key_case(apache_request_headers(), CASE_LOWER);
    $jwt = $allHeaders['authorization'] ?? '';
    
    if (empty($jwt)) {
        ob_end_clean();
        http_response_code(401);
        echo json_encode(['error' => 'Unauthorized: No token provided']);
        exit;
    }

    // Verify Token with Auth Service
    $vch = curl_init(rtrim($services['auth'], '/') . '/api/v1/validate.php');
    curl_setopt($vch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($vch, CURLOPT_HTTPHEADER, ["Authorization: $jwt"]);
    curl_setopt($vch, CURLOPT_SSL_VERIFYPEER, false);
    $vRes = curl_exec($vch);
    if (curl_getinfo($vch, CURLINFO_HTTP_CODE) !== 200) {
        ob_end_clean();
        http_response_code(401);
        echo $vRes ?: json_encode(['error' => 'Session expired']);
        exit;
    }
}

// 5. Execute Proxy Request
$ch = curl_init($targetUrl);
$fwdHeaders = [];
foreach (apache_request_headers() as $k => $v) {
    if (!in_array(strtolower($k), ['host', 'content-length', 'connection'])) {
        $fwdHeaders[] = "$k: $v";
    }
}

curl_setopt_array($ch, [
    CURLOPT_CUSTOMREQUEST  => $method,
    CURLOPT_HTTPHEADER     => $fwdHeaders,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HEADER         => true, // We fetch headers to split them properly
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_SSL_VERIFYPEER => false,
    CURLOPT_TIMEOUT        => 30
]);

if (in_array($method, ['POST', 'PUT', 'DELETE'])) {
    curl_setopt($ch, CURLOPT_POSTFIELDS, file_get_contents('php://input'));
}

$rawResponse = curl_exec($ch);
$info        = curl_getinfo($ch);

if ($rawResponse === false) {
    ob_end_clean();
    http_response_code(502);
    echo json_encode(['error' => 'Backend offline', 'details' => curl_error($ch)]);
    exit;
}

// 6. Split Headers and Body (CRITICAL FIX)
$headerSize = $info['header_size'];
$body       = substr($rawResponse, $headerSize);

// Clear any accidental output (warnings/notices) and send clean body
ob_end_clean();
header('Content-Type: application/json; charset=utf-8');
http_response_code($info['http_code']);
echo $body;
