<?php
/**
 * ========================= API: Job Titles =========================
 * Purpose: Fetch active job titles and their default rates.
 * ============================================================================ 
 */

header('Content-Type: application/json');
require_once __DIR__ . '/../../Database.php';

try {
    $db = Database::getConnection();
    
    $sql = "SELECT job_title_id, title_name, department, default_daily_rate, default_monthly_salary, status 
            FROM job_titles 
            WHERE status = 'Active' 
            ORDER BY title_name ASC";
            
    $stmt = $db->query($sql);
    $job_titles = $stmt->fetchAll();
    
    echo json_encode([
        'success' => true,
        'job_titles' => $job_titles
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error fetching job titles: ' . $e->getMessage()
    ]);
}
