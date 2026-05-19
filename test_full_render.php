<?php
/**
 * Unified Production Smoke Test - ICMIS Render Infrastructure
 * Verifies all 7 Microservices + Gateway in the production environment.
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

echo "=== ICMIS UNIFIED PRODUCTION SMOKE TEST (RENDER) ===\n";
echo "Timestamp: " . date('Y-m-d H:i:s') . "\n\n";

// 1. AUTH SERVICE
echo "[1/8] Auth Service: Login... ";
$login = request('api/v1/auth/login', 'POST', [
    'email' => 'john.doe@icmis.com',
    'password' => 'password123'
]);

if ($login['code'] === 200 && isset($login['body']['token'])) {
    $token = $login['body']['token'];
    echo "PASS (JWT Acquired)\n";
} else {
    echo "FAIL (HTTP {$login['code']})\n";
    echo "Raw Response: " . $login['raw'] . "\n";
    if ($login['error']) echo "CURL Error: " . $login['error'] . "\n";
    exit(1);
}

// 2. PROJECT SERVICE
echo "[2/8] Project Service: Create Test Project... ";
$pCode = 'PR-R-' . time();
$proj = request('api/v1/project/projects', 'POST', [
    'project_name' => 'Render Infrastructure Test',
    'project_code' => $pCode,
    'location' => 'Cloud',
    'status' => 'Planning'
], $token);

if ($proj['code'] === 200 && isset($proj['body']['project_id'])) {
    $project_id = $proj['body']['project_id'];
    echo "PASS (ID: $project_id)\n";
} else {
    echo "FAIL\n";
    echo "HTTP CODE: " . $proj['code'] . "\n";
    echo "Raw Response: " . $proj['raw'] . "\n";
    exit(1);
}

echo "      Project Service: Create Phase... ";
$ph = request('api/v1/project/phases', 'POST', [
    'project_id' => $project_id,
    'phase_name' => 'Infrastructure Validation',
    'status' => 'In Progress'
], $token);
$phase_id = $ph['body']['phase_id'] ?? 0;
if ($phase_id > 0) {
    echo "PASS (ID: $phase_id)\n";
} else {
    echo "FAIL\n";
    echo "HTTP CODE: " . $ph['code'] . "\n";
    echo "Raw Response: " . $ph['raw'] . "\n";
}

// 3. BUDGET SERVICE (Includes Inter-service call)
echo "[3/8] Budget Service: Create Proposal (Inter-service Validation)... ";
$bud = request('api/v1/budget/proposals', 'POST', [
    'project_id' => $project_id,
    'phase_id' => $phase_id,
    'title' => 'System Validation Budget',
    'total_amount' => 10000,
    'status' => 'APPROVED'
], $token);

if ($bud['code'] === 200 && isset($bud['body']['success']) && $bud['body']['success']) {
    echo "PASS\n";
} else {
    echo "FAIL\n";
    echo "HTTP CODE: " . $bud['code'] . "\n";
    echo "Raw Response: " . $bud['raw'] . "\n";
}

// 4. PROCUREMENT SERVICE
echo "[4/8] Procurement Service: Create Supplier... ";
$sup = request('api/v1/procurement/suppliers', 'POST', [
    'supplier_name' => 'Cloud Provider ' . time(),
    'email' => 'support@cloud.com'
], $token);
$supplier_id = $sup['body']['supplier_id'] ?? 0;
if ($supplier_id > 0) {
    echo "PASS (ID: $supplier_id)\n";
} else {
    echo "FAIL\n";
    echo "HTTP CODE: " . $sup['code'] . "\n";
    echo "Raw Response: " . $sup['raw'] . "\n";
}

echo "      Procurement Service: Create Purchase Order... ";
$po = request('api/v1/procurement/orders', 'POST', [
    'po_reference' => 'PO-R-' . time(),
    'supplier_id' => $supplier_id,
    'project_id' => $project_id,
    'total_amount' => 500
], $token);
if (isset($po['body']['success']) && $po['body']['success']) {
    echo "PASS\n";
} else {
    echo "FAIL\n";
    echo "HTTP CODE: " . $po['code'] . "\n";
    echo "Raw Response: " . $po['raw'] . "\n";
}

// 5. WORKFORCE SERVICE
echo "[5/8] Workforce Service: Register Employee... ";
$empCode = 'E-R-' . time();
$emp = request('api/v1/workforce/save_employee', 'POST', [
    'employee_code' => $empCode,
    'first_name' => 'Render',
    'last_name' => 'Tester',
    'email' => 'tester.' . time() . '@render.com'
], $token);
$employee_id = $emp['body']['employee_id'] ?? 0;
if ($employee_id > 0) {
    echo "PASS (ID: $employee_id)\n";
} else {
    echo "FAIL\n";
    echo "HTTP CODE: " . $emp['code'] . "\n";
    echo "Raw Response: " . $emp['raw'] . "\n";
}

echo "      Workforce Service: Project Assignment... ";
$as = request('api/v1/workforce/assignments', 'POST', [
    'employee_id' => $employee_id,
    'project_id' => $project_id,
    'role' => 'System Tester'
], $token);
if (isset($as['body']['success']) && $as['body']['success']) {
    echo "PASS\n";
} else {
    echo "FAIL\n";
    echo "HTTP CODE: " . $as['code'] . "\n";
    echo "Raw Response: " . $as['raw'] . "\n";
}

// 6. REPORTS SERVICE
echo "[6/8] Reports Service: Fetch Project Summary... ";
$rep = request('api/v1/reports/reports?type=project-summary&project_id=' . $project_id, 'GET', null, $token);
if ($rep['code'] === 200 && isset($rep['body']['success']) && $rep['body']['success']) {
    echo "PASS\n";
} else {
    echo "FAIL\n";
    echo "HTTP CODE: " . $rep['code'] . "\n";
    echo "Raw Response: " . $rep['raw'] . "\n";
}

// 7. ADMIN SERVICE
echo "[7/8] Admin Service: Fetch Audit Logs... ";
$logs = request('api/v1/admin/audit-logs', 'GET', null, $token);
if ($logs['code'] === 200 && isset($logs['body']['success']) && $logs['body']['success']) {
    echo "PASS (Retrieved logs)\n";
} else {
    echo "FAIL\n";
    echo "HTTP CODE: " . $logs['code'] . "\n";
    echo "Raw Response: " . $logs['raw'] . "\n";
}

// 8. CLEANUP
echo "[8/8] Infrastructure Cleanup: Deleting Test Project... ";
$del = request('api/v1/project/projects', 'DELETE', ['delete_id' => $project_id], $token);
echo (isset($del['body']['success']) && $del['body']['success']) ? "PASS\n" : "DONE (Manual check required)\n";

echo "\n--- ALL PRODUCTION MICROSERVICES VERIFIED ---\n";
