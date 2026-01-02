<?php
// modules/procurement/php/fetch_stockin.php

// 1. SETUP HEADERS & ERROR HANDLING
header('Content-Type: application/json');
ini_set('display_errors', 0); 
error_reporting(E_ALL);

try {
    // --- STEP A: CONNECT TO PROCUREMENT DB ---
    // This file should be in the same folder
    $proc_db_path = __DIR__ . '/db_connect.php';
    if (!file_exists($proc_db_path)) {
        throw new Exception("Procurement DB file missing.");
    }
    include $proc_db_path;
    
    // Capture the connection (handles $conn_proc or $conn)
    $proc_conn = $conn_proc ?? ($conn ?? null);
    if (!$proc_conn || $proc_conn->connect_error) {
        throw new Exception("Procurement DB Connection Failed.");
    }

    // --- STEP B: CONNECT TO MAIN DB (For User Names) ---
    $icmis_conn = null;
    // Go up 3 levels: php -> procurement -> modules -> root -> config
    $main_db_path = __DIR__ . '/../../../config/database.php';
    
    if (file_exists($main_db_path)) {
        // 1. Back up the procurement connection so it doesn't get overwritten
        $temp_proc = $proc_conn; 
        
        // 2. Load the main config
        include $main_db_path; 
        
        // 3. Capture the new connection (usually $conn)
        if (isset($conn) && !$conn->connect_error) {
            $icmis_conn = $conn;
        }
        
        // 4. Restore the procurement connection variable
        $proc_conn = $temp_proc;
    }

    // --- STEP C: FETCH LOGS FROM PROCUREMENT DB ---
    $sql = "SELECT 
                log.id, 
                log.po_id,
                po.po_reference, 
                log.item_name, 
                log.quantity_received, 
                DATE_FORMAT(log.created_at, '%b %d, %Y %h:%i %p') as date_received, 
                log.received_by 
            FROM inventory_receiving_logs log
            LEFT JOIN purchase_orders po ON log.po_id = po.po_id
            ORDER BY log.created_at DESC
            LIMIT 50";

    $result = $proc_conn->query($sql);

    if (!$result) {
        throw new Exception("SQL Error: " . $proc_conn->error);
    }

    $logs = [];
    $user_ids = [];

    while ($row = $result->fetch_assoc()) {
        $logs[] = $row;
        // Collect User IDs to fetch names later
        if (!empty($row['received_by'])) {
            $user_ids[] = $row['received_by'];
        }
    }

    // --- STEP D: FETCH USER NAMES FROM MAIN DB ---
    $user_map = [];
    
    if (!empty($user_ids) && $icmis_conn) {
        // Sanitize IDs for safety
        $ids = implode(',', array_map('intval', array_unique($user_ids)));
        
        // CORRECTED QUERY: Uses 'full_name' instead of firstname/lastname
        $u_sql = "SELECT user_id, full_name FROM users WHERE user_id IN ($ids)";
        $u_result = $icmis_conn->query($u_sql);
        
        if ($u_result) {
            while ($u = $u_result->fetch_assoc()) {
                $user_map[$u['user_id']] = $u['full_name'];
            }
        }
    }

    // --- STEP E: MERGE DATA ---
    $final_data = [];
    foreach ($logs as $log) {
        $uid = $log['received_by'];
        
        // Use the fetched name, or fallback to "User ID: X" if not found
        if (isset($user_map[$uid])) {
            $log['received_by_name'] = $user_map[$uid];
        } else {
            $log['received_by_name'] = "User ID: " . $uid;
        }
        
        $final_data[] = $log;
    }

    // Output valid JSON
    echo json_encode($final_data);

} catch (Exception $e) {
    // Return error as JSON so it doesn't break the frontend
    http_response_code(500);
    echo json_encode([
        'error' => true,
        'message' => $e->getMessage()
    ]);
}
?>