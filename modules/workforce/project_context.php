<?php
/**
 * Workforce Context Manager
 * Handles global project selection across Labor & Workforce pages
 * Implements the "Project Context Pattern" for seamless UX
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
 * Priority: URL parameter > Session > First available project
 */
function getProjectContext($conn) {
    // Check if project_id is provided in URL
    if (isset($_GET['project_id']) && !empty($_GET['project_id'])) {
        $project_id = intval($_GET['project_id']);
        
        // Verify project exists
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
    
    // Get first available project
    $sql = "SELECT project_id FROM icmis_projects ORDER BY project_id DESC LIMIT 1";
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
