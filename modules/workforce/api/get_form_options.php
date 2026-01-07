<?php
/**
 * Get Form Options API - Workforce Module
 * Returns JSON for job titles, departments, and supervisors
 * 
 * DATABASE SCHEMA (from icmis_db.sql):
 * - workforce_job_titles: job_title_id, title_name, department, description, default_daily_rate, is_active
 * - workforce_employees: employee_id, employee_code, user_id, job_title_id, first_name, last_name, email, phone, status (ENUM: Active, Inactive, Terminated), hire_date
 */
require_once __DIR__ . '/../../../../config/database.php';
header('Content-Type: application/json');

// basic auth / session check could be added here
try {
    $out = [
        'job_titles' => [],
        'departments' => [],
        'supervisors' => []
    ];

    // job titles
    $stmt = $conn->prepare("SELECT job_title_id, title_name, department FROM workforce_job_titles WHERE is_active = 1 ORDER BY title_name");
    $stmt->execute();
    $out['job_titles'] = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

    // departments - distinct from job_titles.department
    $stmt = $conn->prepare("SELECT DISTINCT department FROM workforce_job_titles WHERE department IS NOT NULL AND department != '' ORDER BY department");
    $stmt->execute();
    $rows = $stmt->get_result()->fetch_all(MYSQLI_NUM);
    $out['departments'] = array_map(function($r){ return $r[0]; }, $rows);

    // supervisors - existing employees
    $stmt = $conn->prepare("SELECT employee_id, CONCAT(first_name, ' ', last_name) as name FROM workforce_employees WHERE status = 'Active' ORDER BY first_name, last_name");
    $stmt->execute();
    $out['supervisors'] = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

    echo json_encode(['success' => true, 'data' => $out]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
