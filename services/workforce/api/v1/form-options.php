<?php
/**
 * Form Options API v1 - Workforce Service
 */
header('Content-Type: application/json');
require_once __DIR__ . '/../../Database.php';

$db = \Database::getConnection();

try {
    // Fetch Job Titles
    $stmt = $db->query("SELECT job_title_id, title_name FROM job_titles WHERE is_active = TRUE ORDER BY title_name");
    $job_titles = $stmt->fetchAll();

    // Fetch Employees (for supervisors/leaders)
    $stmt = $db->query("SELECT employee_id, first_name, last_name FROM employees WHERE status = 'Active' ORDER BY last_name");
    $employees = $stmt->fetchAll();

    echo json_encode([
        'success' => true,
        'job_titles' => $job_titles,
        'employees' => $employees
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
