<?php
/**
 * Project Context Manager - Procurement Module
 * Handles global project selection across Procurement & Inventory pages
 * Implements the "Project Context Pattern" for seamless UX
 */

// Include config for database connection
require_once __DIR__ . '/../../config/config.php';

// Create database connection
function getProcurementConnection() {
    static $conn = null;
    if ($conn === null) {
        $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
        if ($conn->connect_error) {
            die("Connection failed: " . $conn->connect_error);
        }
        $conn->set_charset("utf8mb4");
        // Set timezone
        date_default_timezone_set('Asia/Manila');
    }
    return $conn;
}

/**
 * Get the current project context
 * Priority: URL parameter > Session > First available project
 */
function getProjectContext($conn) {
    // Check if project_id is provided in URL (user is explicitly selecting)
    if (isset($_GET['project_id']) && !empty($_GET['project_id'])) {
        $project_id = intval($_GET['project_id']);
        
        // Verify project exists
        $sql = "SELECT project_id FROM icmis_projects WHERE project_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $project_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            // Valid project, save to session
            $_SESSION['procurement_selected_project_id'] = $project_id;
            $stmt->close();
            return $project_id;
        }
        $stmt->close();
    }
    
    // Check if we have a project in session
    if (isset($_SESSION['procurement_selected_project_id']) && !empty($_SESSION['procurement_selected_project_id'])) {
        $project_id = intval($_SESSION['procurement_selected_project_id']);
        
        // Verify project still exists
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
    
    // No valid project in URL or session, get first available project
    $sql = "SELECT project_id FROM icmis_projects ORDER BY project_id DESC LIMIT 1";
    $result = $conn->query($sql);
    
    if ($result && $result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $project_id = intval($row['project_id']);
        $_SESSION['procurement_selected_project_id'] = $project_id;
        return $project_id;
    }
    
    // No projects exist
    return 0;
}

/**
 * Get the current phase context
 */
function getPhaseContext() {
    // Check URL parameter first
    if (isset($_GET['phase_id']) && !empty($_GET['phase_id'])) {
        $phase_id = intval($_GET['phase_id']);
        $_SESSION['procurement_selected_phase_id'] = $phase_id;
        return $phase_id;
    }
    
    // Check session
    if (isset($_SESSION['procurement_selected_phase_id']) && !empty($_SESSION['procurement_selected_phase_id'])) {
        return intval($_SESSION['procurement_selected_phase_id']);
    }
    
    // No phase selected
    return 0;
}

/**
 * Clear project context (useful for logout or context reset)
 */
function clearProjectContext() {
    unset($_SESSION['procurement_selected_project_id']);
    unset($_SESSION['procurement_selected_phase_id']);
}

/**
 * Build navigation URL with current project context
 */
function buildContextUrl($base_url, $additional_params = []) {
    $params = [];
    
    // Add project_id if exists
    if (isset($_SESSION['procurement_selected_project_id'])) {
        $params['project_id'] = $_SESSION['procurement_selected_project_id'];
    }
    
    // Add phase_id if exists
    if (isset($_SESSION['procurement_selected_phase_id'])) {
        $params['phase_id'] = $_SESSION['procurement_selected_phase_id'];
    }
    
    // Merge with additional params
    $params = array_merge($params, $additional_params);
    
    // Build query string
    if (!empty($params)) {
        return $base_url . '?' . http_build_query($params);
    }
    
    return $base_url;
}
?>
