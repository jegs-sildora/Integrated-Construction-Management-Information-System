<?php
/**
 * Project Context Manager (Microservices Version)
 * Handles global project selection across Procurement pages
 */

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../core/ApiHelper.php';

/**
 * Get the current project context
 */
function getProjectContext($conn = null) {
    if (isset($_GET['project_id']) && !empty($_GET['project_id'])) {
        $project_id = intval($_GET['project_id']);
        $_SESSION['selected_project_id'] = $project_id;
        return $project_id;
    }
    
    if (isset($_SESSION['selected_project_id']) && !empty($_SESSION['selected_project_id'])) {
        return intval($_SESSION['selected_project_id']);
    }
    
    try {
        $res = ApiHelper::get('project/projects');
        $projects = $res['data']['projects'] ?? [];
        if (!empty($projects)) {
            $project_id = intval($projects[0]['project_id']);
            $_SESSION['selected_project_id'] = $project_id;
            return $project_id;
        }
    } catch (Exception $e) {
        error_log("Project Context Error: " . $e->getMessage());
    }
    
    return 0;
}

function getProcurementConnection() { 
    global $conn;
    if (!isset($conn)) {
        require_once __DIR__ . '/../../config/database.php';
    }
    return $conn; 
}

function clearProjectContext() {
    unset($_SESSION['selected_project_id']);
}
?>