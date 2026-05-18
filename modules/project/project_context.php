<?php
/**
 * modules/project/project_context.php
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
function getProjectConnection() {
    global $conn;
    if (!isset($conn)) {
        require_once __DIR__ . '/../../config/database.php';
    }
    return $conn;
}

/**
 * Build navigation URL with context
 */
function buildContextUrl($base_url, $params = []) {
    return ProjectContext::buildUrl($base_url, $params);
}

/**
 * Clear project context
 */
function clearProjectContext() {
    ProjectContext::clear();
}
?>