<?php
/**
 * Workforce Context Manager
 * Handles global project selection across Labor & Workforce pages
 * Implements the "Project Context Pattern" for seamless UX
 * 
 * Database Schema Reference (icmis_db.sql):
 * - icmis_projects: project_id, project_code, project_name, description, location, status, start_date, end_date, completion_rate, project_manager_id, total_budget
 * - workforce_employees: employee_id, employee_code, user_id, job_title_id, first_name, last_name, email, phone, status, hire_date
 * - workforce_job_titles: job_title_id, title_name, department, description, default_daily_rate, is_active
 * - workforce_assignments: assignment_id, employee_id, project_id, phase_id, role, task_description, start_date, end_date, status
 * - workforce_attendance: attendance_id, employee_id, project_id, attendance_date, time_in, time_out, status, remarks
 * - workforce_payroll: payroll_id, employee_id, period_id, hours_worked, gross_pay, net_pay, status
 * - workforce_payroll_periods: period_id, start_date, end_date, pay_date, status
 * - workforce_employee_groups: group_id, group_name, group_leader_id, description
 * - workforce_group_memberships: membership_id, employee_id, group_id, role_in_group, joined_date
 */

// Include config for database connection
require_once __DIR__ . '/../../config/config.php';

// Create database connection using mysqli (consistent with other modules)
function getWorkforceConnection() {
    static $conn = null;
    if ($conn === null) {
        $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
        if ($conn->connect_error) {
            die("Connection failed: " . $conn->connect_error);
        }
        $conn->set_charset("utf8mb4");
        date_default_timezone_set('Asia/Manila');
    }
    return $conn;
}

/**
 * Get the current project context
 * Priority: URL parameter > Session > Default project (ID=1) > First available project
 * 
 * Entry point (dashboard.php) should capture project_id=1 from URL to set context
 */
function getProjectContext($conn) {
    // Check if project_id is provided in URL
    if (isset($_GET['project_id']) && !empty($_GET['project_id'])) {
        $project_id = intval($_GET['project_id']);
        
        // Verify project exists in icmis_projects table
        $sql = "SELECT project_id FROM icmis_projects WHERE project_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $project_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $_SESSION['selected_project_id'] = $project_id;
            $stmt->close();
            return $project_id;
        }
        $stmt->close();
    }
    
    // Check session
    if (isset($_SESSION['selected_project_id']) && !empty($_SESSION['selected_project_id'])) {
        $project_id = intval($_SESSION['selected_project_id']);
        
        $sql = "SELECT project_id FROM icmis_projects WHERE project_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $project_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $stmt->close();
            return $project_id;
        }
        $stmt->close();
    }
    
    // Try default project_id = 1 first (common entry point)
    $sql = "SELECT project_id FROM icmis_projects WHERE project_id = 1";
    $result = $conn->query($sql);
    if ($result && $result->num_rows > 0) {
        $_SESSION['selected_project_id'] = 1;
        return 1;
    }
    
    // Fallback: Get first available project
    $sql = "SELECT project_id FROM icmis_projects ORDER BY project_id ASC LIMIT 1";
    $result = $conn->query($sql);
    
    if ($result && $result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $project_id = intval($row['project_id']);
        $_SESSION['selected_project_id'] = $project_id;
        return $project_id;
    }
    
    return 0;
}

/**
 * Build context-aware URL
 */
function buildContextUrl($base_url, $params = []) {
    if (isset($_SESSION['selected_project_id'])) {
        $params['project_id'] = $_SESSION['selected_project_id'];
    }
    return $base_url . (!empty($params) ? '?' . http_build_query($params) : '');
}
