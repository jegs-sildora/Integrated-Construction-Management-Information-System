<?php
/**
 * Project Context Manager (Microservices Version)
 * Handles global project selection across Budget & Cost Control pages
 */

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../core/ApiHelper.php';

/**
 * Get the current project context
 * Priority: URL parameter > Session > First available project
 */
function getProjectContext($conn = null) {
    // Check if project_id is provided in URL
    if (isset($_GET['project_id']) && !empty($_GET['project_id'])) {
        $project_id = intval($_GET['project_id']);
        $_SESSION['selected_project_id'] = $project_id;
        return $project_id;
    }
    
    // Check session
    if (isset($_SESSION['selected_project_id']) && !empty($_SESSION['selected_project_id'])) {
        return intval($_SESSION['selected_project_id']);
    }
    
    // Fallback: get first available project from API
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

function getBudgetConnection() { 
    global $conn;
    if (!isset($conn)) {
        require_once __DIR__ . '/../../config/database.php';
    }
    return $conn; 
}

function getPhaseContext() {
    if (isset($_GET['phase']) && !empty($_GET['phase'])) {
        $phase = $_GET['phase'];
        $_SESSION['selected_phase'] = $phase;
        return $phase;
    }
    return $_SESSION['selected_phase'] ?? 'All Phases';
}

function clearProjectContext() {
    unset($_SESSION['selected_project_id']);
    unset($_SESSION['selected_phase']);
}

function buildContextUrl($base_url, $additional_params = []) {
    $params = [];
    if (isset($_SESSION['selected_project_id'])) {
        $params['project_id'] = $_SESSION['selected_project_id'];
    }
    if (isset($_SESSION['selected_phase']) && $_SESSION['selected_phase'] !== 'All Phases') {
        $params['phase'] = $_SESSION['selected_phase'];
    }
    $params = array_merge($params, $additional_params);
    return !empty($params) ? $base_url . '?' . http_build_query($params) : $base_url;
}
?>