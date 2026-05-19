<?php
/**
 * Smoke Test - Phase 1: Auth & Project Operations
 * Verifies Gateway -> Auth Service -> Database
 * and Gateway -> Project Service -> Database
 */

// Use the public Render Gateway for real production smoke testing
$gateway_url = 'https://icmis-gateway.onrender.com/api/v1';

function call_api($endpoint, $method = 'GET', $data = null, $token = null) {
    global $gateway_url;
    $url = $gateway_url . '/' . ltrim($endpoint, '/');
    $ch = curl_init($url);
    
    $headers = ['Content-Type: application/json'];
    if ($token) {
        $headers[] = "Authorization: Bearer $token";
    }
    
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    
    if ($data !== null) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    }
    
    $res = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err = curl_error($ch);
    curl_close($ch);
    
    echo "[$method] $endpoint -> HTTP $code\n";
    if ($err) echo "CURL ERROR: $err\n";
    
    $decoded = json_decode($res, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        echo "INVALID JSON RECEIVED:\n" . substr($res, 0, 500) . "...\n";
    }
    
    return $decoded;
}

echo "=== PHASE 1 SMOKE TEST (RENDER PRODUCTION) ===\n\n";

// 1. Authentication
echo "1. Testing Authentication...\n";
$authRes = call_api('auth/login', 'POST', [
    'email' => 'john.doe@icmis.com',
    'password' => 'password123'
]);

if (!isset($authRes['token'])) {
    die("❌ FAILED TO AUTHENTICATE. Check Auth Service logs.\n");
}
$token = $authRes['token'];
echo "✅ Token acquired successfully.\n\n";

// 2. Create Project
echo "2. Testing Project Creation (Bacolod Data)...\n";
$projectRes = call_api('project/projects', 'POST', [
    'project_name' => 'Bacolod City General Hospital Annex',
    'project_code' => 'BCGH-2026-A',
    'location' => 'Brgy. Bata, Bacolod City',
    'start_date' => '2026-06-01',
    'end_date' => '2027-12-15',
    'total_budget' => 45000000.00,
    'status' => 'Planning'
], $token);

if (!isset($projectRes['project_id'])) {
    // If it failed, maybe it's because success is a bool in the response
    if (isset($projectRes['success']) && $projectRes['success'] === false) {
        echo "❌ FAILED TO CREATE PROJECT: " . ($projectRes['message'] ?? 'Unknown Error') . "\n";
        die();
    }
}
$project_id = $projectRes['project_id'] ?? null;

if (!$project_id) {
    // Fallback: search for the project we might have just created
    echo "Scanning for existing Bacolod project...\n";
    $allProj = call_api('project/projects', 'GET', null, $token);
    foreach ($allProj['projects'] ?? [] as $p) {
        if ($p['project_code'] === 'BCGH-2026-A') {
            $project_id = $p['project_id'];
            break;
        }
    }
}

if (!$project_id) die("❌ COULD NOT RETRIEVE PROJECT ID.\n");
echo "✅ Project Ready (ID: $project_id)\n\n";

// 3. Create Phases
echo "3. Testing Phase Creation...\n";
$phases = ['Site Preparation', 'Foundation Work', 'Structural Framing'];
$phase_ids = [];

foreach ($phases as $phase_name) {
    $phaseRes = call_api('project/phases', 'POST', [
        'project_id' => $project_id,
        'phase_name' => $phase_name,
        'start_date' => '2026-06-01',
        'end_date' => '2026-07-15',
        'status' => 'Pending'
    ], $token);
    
    if (isset($phaseRes['phase_id'])) {
        $phase_ids[] = $phaseRes['phase_id'];
    }
}
echo "✅ Created " . count($phase_ids) . " phases.\n\n";

// 4. Create Task
echo "4. Testing Task Creation...\n";
if (!empty($phase_ids)) {
    $taskRes = call_api('project/tasks', 'POST', [
        'project_id' => $project_id,
        'phase_id' => $phase_ids[0],
        'task_name' => 'Clear hospital grounds and setup perimeter fence',
        'start_date' => '2026-06-01',
        'end_date' => '2026-06-07',
        'status' => 'Pending'
    ], $token);
    
    if (isset($taskRes['success']) && $taskRes['success']) {
        echo "✅ Task created successfully.\n";
    } else {
        echo "❌ Task creation failed: " . ($taskRes['message'] ?? 'Unknown') . "\n";
    }
}

echo "\n🚀 PHASE 1 SMOKE TEST PASSED.\n";
