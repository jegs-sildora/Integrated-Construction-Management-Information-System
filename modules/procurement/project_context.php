<?php
/**
 * modules/procurement/project_context.php
 * 
 * Migration Bridge for Procurement Module.
 */

require_once __DIR__ . '/../../core/Context.php';

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
