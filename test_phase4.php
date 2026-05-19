<?php
/**
 * Phase 4 Smoke Test - Budget & Procurement Service Integrity
 * Verifies:
 * 1. Budget Service: Proposal & Expense Management
 * 2. Procurement Service: Supplier & PO Management
 * 3. Inter-service Communication: Budget Service fetching Phases from Project Service
 */

//$gateway_url = 'http://localhost:8000'; // Default to local
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
        CURLOPT_TIMEOUT => 30
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

echo "--- ICMIS PHASE 4 SMOKE TEST (BUDGET & PROCUREMENT) ---\n\n";

// 0. Login
echo "[0/6] Authenticating... ";
$login = request('api/v1/auth/login', 'POST', [
    'email' => 'john.doe@icmis.com',
    'password' => 'password123'
]);

if ($login['code'] === 200 && isset($login['body']['token'])) {
    $token = $login['body']['token'];
    echo "PASS\n";
} else {
    echo "FAIL\n";
    print_r($login['body']);
    exit(1);
}

// 1. Setup Test Project
echo "[1/6] Setting up Test Project... ";
$pRes = request('api/v1/project/projects', 'POST', [
    'project_name' => 'Phase 4 Test Project',
    'project_code' => 'P4-TEST-' . time(),
    'location' => 'Budget Lab',
    'total_budget' => 5000000,
    'status' => 'Active'
], $token);

if ($pRes['code'] === 200 && isset($pRes['body']['project_id'])) {
    $project_id = $pRes['body']['project_id'];
    echo "PASS (ID: $project_id)\n";
} else {
    echo "FAIL\n";
    print_r($pRes['body']);
    exit(1);
}

// 1.5 Create a Phase for the project
echo "[1.5/6] Creating Phase... ";
$phRes = request('api/v1/project/phases', 'POST', [
    'project_id' => $project_id,
    'phase_name' => 'Initial Phase',
    'start_date' => date('Y-m-d'),
    'status' => 'Pending'
], $token);
$phase_id = $phRes['body']['phase_id'] ?? 0;
echo "PASS (ID: $phase_id)\n";

// 2. Budget Service: Create Proposal
echo "[2/6] Budget Service: Creating Proposal... ";
$propRes = request('api/v1/budget/proposals', 'POST', [
    'project_id' => $project_id,
    'phase_id' => $phase_id,
    'title' => 'Structural Materials Proposal',
    'total_amount' => 250000,
    'status' => 'APPROVED'
], $token);

if ($propRes['code'] === 200 && isset($propRes['body']['proposal_id'])) {
    $proposal_id = $propRes['body']['proposal_id'];
    echo "PASS (ID: $proposal_id)\n";
} else {
    echo "FAIL\n";
    echo "HTTP CODE: " . $propRes['code'] . "\n";
    echo "RAW BODY: " . $propRes['raw'] . "\n";
    print_r($propRes['body']);
}

// 3. Budget Service: Inter-service Communication (Fetch Phases)
echo "[3/6] Budget Service: Testing Inter-service Call (Fetch Phases)... ";
$phRes = request("api/v1/budget/phases?project_id=$project_id", 'GET', null, $token);
if ($phRes['code'] === 200 && isset($phRes['body']['phases'])) {
    echo "PASS (Successfully fetched phases from Project Service)\n";
} else {
    echo "FAIL\n";
    echo "HTTP CODE: " . $phRes['code'] . "\n";
    echo "RAW BODY: " . $phRes['raw'] . "\n";
    print_r($phRes['body']);
}

// 4. Procurement Service: Create Supplier
echo "[4/6] Procurement Service: Creating Supplier... ";
$suppRes = request('api/v1/procurement/suppliers', 'POST', [
    'supplier_name' => 'Test Supplier ' . time(),
    'contact_person' => 'Test Agent',
    'email' => 'supplier@test.com',
    'phone' => '0917-000-0000',
    'address' => '123 Supply Ave'
], $token);

if ($suppRes['code'] === 200 && isset($suppRes['body']['supplier_id'])) {
    $supplier_id = $suppRes['body']['supplier_id'];
    echo "PASS (ID: $supplier_id)\n";
} else {
    echo "FAIL\n";
    echo "HTTP CODE: " . $suppRes['code'] . "\n";
    print_r($suppRes['body']);
}

// 5. Procurement Service: Create Purchase Order
echo "[5/6] Procurement Service: Creating Purchase Order... ";
$poRes = request('api/v1/procurement/orders', 'POST', [
    'po_reference' => 'PO-' . time(),
    'supplier_id' => $supplier_id,
    'project_id' => $project_id,
    'order_date' => date('Y-m-d'),
    'total_amount' => 50000,
    'status' => 'PENDING',
    'items' => [
        ['item_name' => 'Steel Rods', 'quantity' => 100, 'unit_cost' => 500]
    ]
], $token);

if ($poRes['code'] === 200 && isset($poRes['body']['po_id'])) {
    echo "PASS (PO Created)\n";
} else {
    echo "FAIL\n";
    print_r($poRes['body']);
}

// 6. Cleanup
echo "[6/6] Cleaning up... ";
request('api/v1/project/projects', 'DELETE', ['delete_id' => $project_id], $token);
echo "DONE\n";

echo "\n--- PHASE 4 SMOKE TEST COMPLETE ---\n";
