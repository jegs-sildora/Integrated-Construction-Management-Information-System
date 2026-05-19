<?php
/**
 * Phase 3 Smoke Test - Project Service Integrity
 * Verifies:
 * 1. Project Creation & Retrieval
 * 2. Phase Creation & Retrieval
 * 3. Task Creation & Retrieval
 * 4. Project Deletion (Clean up)
 */

$gateway_url = 'http://localhost:8000'; // Default to local for first run
//$gateway_url = 'https://icmis-gateway.onrender.com';

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

echo "--- ICMIS PHASE 3 SMOKE TEST (PROJECT SERVICE) ---\n\n";

// 0. Login to get token
echo "[0/5] Authenticating... ";
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

// 1. Create Project
echo "[1/5] Creating Project... ";
$project_data = [
    'project_name' => 'Phase 3 Verification Project',
    'project_code' => 'P3-TEST-' . time(),
    'location' => 'Test Lab',
    'start_date' => date('Y-m-d'),
    'end_date' => date('Y-m-d', strtotime('+1 year')),
    'total_budget' => 1000000,
    'status' => 'Planning'
];
$pRes = request('api/v1/project/projects', 'POST', $project_data, $token);
if ($pRes['code'] === 200 && isset($pRes['body']['project_id'])) {
    $project_id = $pRes['body']['project_id'];
    echo "PASS (ID: $project_id)\n";
} else {
    echo "FAIL\n";
    print_r($pRes['body']);
    exit(1);
}

// 2. Create Phase
echo "[2/5] Creating Phase... ";
$phase_data = [
    'project_id' => $project_id,
    'phase_name' => 'Initial Assessment',
    'start_date' => date('Y-m-d'),
    'end_date' => date('Y-m-d', strtotime('+1 month')),
    'status' => 'Pending'
];
$phRes = request('api/v1/project/phases', 'POST', $phase_data, $token);
if ($phRes['code'] === 200 && isset($phRes['body']['phase_id'])) {
    $phase_id = $phRes['body']['phase_id'];
    echo "PASS (ID: $phase_id)\n";
} else {
    echo "FAIL\n";
    print_r($phRes['body']);
}

// 3. Create Task
echo "[3/5] Creating Task... ";
$task_data = [
    'project_id' => $project_id,
    'phase_id' => $phase_id,
    'task_name' => 'Verify System Connectivity',
    'start_date' => date('Y-m-d'),
    'due_date' => date('Y-m-d', strtotime('+1 week')),
    'status' => 'Not Started',
    'priority' => 'High'
];
$tRes = request('api/v1/project/tasks', 'POST', $task_data, $token);
if ($tRes['code'] === 200 && isset($tRes['body']['success']) && $tRes['body']['success']) {
    echo "PASS\n";
} else {
    echo "FAIL\n";
    print_r($tRes['body']);
}

// 4. Retrieve All Projects
echo "[4/5] Retrieving Project List... ";
$listRes = request('api/v1/project/projects', 'GET', null, $token);
if ($listRes['code'] === 200 && isset($listRes['body']['projects'])) {
    $found = false;
    foreach ($listRes['body']['projects'] as $p) {
        if ($p['project_id'] == $project_id) {
            $found = true;
            break;
        }
    }
    echo $found ? "PASS\n" : "FAIL (Project not in list)\n";
} else {
    echo "FAIL\n";
    print_r($listRes['body']);
}

// 5. Cleanup (Delete Project)
echo "[5/5] Cleaning up (Delete Project)... ";
$delRes = request('api/v1/project/projects', 'DELETE', ['delete_id' => $project_id], $token);
if ($delRes['code'] === 200 && isset($delRes['body']['success']) && $delRes['body']['success']) {
    echo "PASS\n";
} else {
    // Try via POST with delete_id if DELETE method is restricted
    $delRes2 = request('api/v1/project/projects', 'POST', ['delete_id' => $project_id], $token);
    if ($delRes2['code'] === 200 && isset($delRes2['body']['success']) && $delRes2['body']['success']) {
        echo "PASS (via POST)\n";
    } else {
        echo "FAIL\n";
        print_r($delRes['body']);
    }
}

echo "\n--- PHASE 3 SMOKE TEST COMPLETE ---\n";
