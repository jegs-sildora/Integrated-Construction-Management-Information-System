<?php
/**
 * modules/project/project_context.php
 * 
 * Migration Bridge for Project Module.
 */

require_once __DIR__ . '/../../core/Context.php';

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
