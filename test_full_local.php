<?php
/**
 * Unified Local Smoke Test - Full System Flow
 */

$gateway_url = 'http://localhost:8000';

function request($path, $method = 'GET', $data = null, $token = null) {
    global $gateway_url;
    $url = $gateway_url . '/' . ltrim($path, '/');
    $ch = curl_init($url);
    $headers = ['Content-Type: application/json', 'Accept: application/json'];
    if ($token) $headers[] = "Authorization: Bearer $token";
    curl_setopt_array($ch, [
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CUSTOMREQUEST => strtoupper($method),
        CURLOPT_TIMEOUT => 10
    ]);
    if ($data !== null) curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    $res = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return ['code' => $code, 'body' => json_decode($res, true)];
}

echo "=== ICMIS UNIFIED LOCAL SMOKE TEST ===\n\n";

// 1. Auth
echo "[1/5] Auth: Login... ";
$auth = request('api/v1/auth/login', 'POST', ['email' => 'john.doe@icmis.com', 'password' => 'password123']);
$token = $auth['body']['token'] ?? null;
if ($token) echo "PASS\n"; else die("FAIL\n");

// 2. Project
echo "[2/5] Project: Create Project... ";
$proj = request('api/v1/project/projects', 'POST', ['project_name' => 'Local Full Test', 'project_code' => 'LF-'.time()], $token);
$project_id = $proj['body']['project_id'] ?? null;
if ($project_id) echo "PASS (ID: $project_id)\n"; else die("FAIL\n");

echo "      Project: Create Phase... ";
$ph = request('api/v1/project/phases', 'POST', ['project_id' => $project_id, 'phase_name' => 'Test Phase'], $token);
$phase_id = $ph['body']['phase_id'] ?? null;
if ($phase_id) echo "PASS (ID: $phase_id)\n"; else die("FAIL\n");

// 3. Budget
echo "[3/5] Budget: Create Proposal... ";
$bud = request('api/v1/budget/proposals', 'POST', ['project_id' => $project_id, 'phase_id' => $phase_id, 'title' => 'Test Budget', 'total_amount' => 5000], $token);
if (isset($bud['body']['success']) && $bud['body']['success']) echo "PASS\n"; else { echo "FAIL\n"; print_r($bud['body']); }

// 4. Procurement
echo "[4/5] Procurement: Create Supplier... ";
$sup = request('api/v1/procurement/suppliers', 'POST', ['supplier_name' => 'Local Supplier '.time()], $token);
$supplier_id = $sup['body']['supplier_id'] ?? null;
if ($supplier_id) echo "PASS\n"; else die("FAIL\n");

echo "      Procurement: Create Order... ";
$ord = request('api/v1/procurement/orders', 'POST', ['po_reference' => 'PO-L-'.time(), 'supplier_id' => $supplier_id, 'project_id' => $project_id, 'total_amount' => 1000], $token);
if (isset($ord['body']['success']) && $ord['body']['success']) echo "PASS\n"; else die("FAIL\n");

// 5. Workforce
echo "[5/5] Workforce: Create Employee... ";
$emp = request('api/v1/workforce/save_employee', 'POST', ['employee_code' => 'E-L-'.time(), 'first_name' => 'Local', 'last_name' => 'Worker', 'email' => 'l.worker@test.com'], $token);
$employee_id = $emp['body']['employee_id'] ?? null;
if ($employee_id) echo "PASS\n"; else die("FAIL\n");

echo "      Workforce: Assign... ";
$as = request('api/v1/workforce/assignments', 'POST', ['employee_id' => $employee_id, 'project_id' => $project_id, 'role' => 'Tester'], $token);
if (isset($as['body']['success']) && $as['body']['success']) echo "PASS\n"; else die("FAIL\n");

echo "\n--- ALL LOCAL SYSTEMS OPERATIONAL ---\n";
echo "Cleaning up local test project... ";
request('api/v1/project/projects', 'DELETE', ['delete_id' => $project_id], $token);
echo "DONE\n";
