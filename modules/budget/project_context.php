<?php
/**
 * modules/budget/project_context.php
 * 
 * Module-specific wrapper for ProjectContext.
 */

require_once __DIR__ . '/../../core/ProjectContext.php';

/**
 * Get the current project context
 */
function getProjectContext($conn = null) {
    return ProjectContext::getProjectId();
}

/**
 * Get the database connection (Legacy Mock support)
 */
function getBudgetConnection() { 
    global $conn;
    if (!isset($conn)) {
        require_once __DIR__ . '/../../config/database.php';
    }
    return $conn; 
}

/**
 * Get the current phase context
 */
function getPhaseContext() {
    if (isset($_GET['phase']) && !empty($_GET['phase'])) {
        $phase = $_GET['phase'];
        $_SESSION['selected_phase'] = $phase;
        return $phase;
    }
    return $_SESSION['selected_phase'] ?? 'All Phases';
}

/**
 * Clear project context
 */
function clearProjectContext() {
    ProjectContext::clear();
}

/**
 * Build navigation URL with context
 */
function buildContextUrl($base_url, $additional_params = []) {
    $params = $additional_params;
    $phase = getPhaseContext();
    if ($phase !== 'All Phases' && !isset($params['phase'])) {
        $params['phase'] = $phase;
    }
    return ProjectContext::buildUrl($base_url, $params);
}
?>