<?php
/**
 * modules/procurement/project_context.php
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
function getProcurementConnection() { 
    global $conn;
    if (!isset($conn)) {
        require_once __DIR__ . '/../../config/database.php';
    }
    return $conn; 
}

/**
 * Clear project context
 */
function clearProjectContext() {
    ProjectContext::clear();
}
?>