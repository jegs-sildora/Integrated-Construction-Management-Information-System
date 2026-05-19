<?php
/**
 * Phase 2 Smoke Test - Auth & Gateway Integrity
 * Verifies:
 * 1. Gateway Health Check
 * 2. Auth Login (Public)
 * 3. Auth Token Validation
 * 4. Gateway JWT Enforcement (Protected Route)
 */

$gateway_url = 'https://icmis-gateway.onrender.com';

function request($path, $method = 'GET', $data = null, $token = null) {
    global $gateway_url;
    $url = $gateway_url . '/' . ltrim($path, '/');
    $ch = curl_init($url);
    
    $headers = ['Content-Type: application/json', 'Accept: application/json'];
    if ($token) {
        $headers[] = "Authorization: Bearer $token";
    }
    
    curl_setopt_array($ch, [
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CUSTOMREQUEST => strtoupper($method),
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_TIMEOUT => 60
    ]);
    
    if ($data !== null) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    }
    
    $res = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err = curl_error($ch);
    curl_close($ch);
    
    return [
        'code' => $code,
        'body' => json_decode($res, true),
        'raw' => $res,
        'error' => $err
    ];
}

echo "--- ICMIS PHASE 2 SMOKE TEST ---\n\n";

// 1. Gateway Health
echo "[1/4] Checking Gateway Health... ";
$h = request('health');
if ($h['code'] === 200 && isset($h['body']['status']) && $h['body']['status'] === 'online') {
    echo "PASS (Online)\n";
} else {
    echo "FAIL (Code: {$h['code']})\n";
    print_r($h['body']);
}

// 2. Auth Login
echo "[2/4] Testing Auth Login (via Gateway Proxy)... ";
$login = request('api/v1/auth/login', 'POST', [
    'email' => 'john.doe@icmis.com',
    'password' => 'password123'
]);

if ($login['code'] === 200 && isset($login['body']['token'])) {
    $token = $login['body']['token'];
    echo "PASS (Token acquired)\n";
} else {
    echo "FAIL (Code: {$login['code']})\n";
    print_r($login['body']);
    exit(1);
}

// 3. Auth Validation
echo "[3/4] Testing Auth Token Validation... ";
$val = request('api/v1/auth/validate', 'POST', null, $token);
if ($val['code'] === 200 && isset($val['body']['valid']) && $val['body']['valid'] === true) {
    echo "PASS (Valid token)\n";
} else {
    echo "FAIL (Code: {$val['code']})\n";
    print_r($val['body']);
}

// 4. Protected Route Enforcement
echo "[4/4] Testing Protected Route (Project Service via Gateway)... ";
$proj = request('api/v1/project/projects', 'GET', null, $token);
if ($proj['code'] === 200 && isset($proj['body']['projects'])) {
    echo "PASS (Retrieved " . count($proj['body']['projects']) . " projects)\n";
} else {
    echo "FAIL (Code: {$proj['code']})\n";
    print_r($proj['body']);
}

echo "\n--- SMOKE TEST COMPLETE ---\n";
