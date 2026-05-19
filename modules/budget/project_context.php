<?php
/**
 * modules/budget/project_context.php
 * 
 * Migration Bridge for Budget Module.
 */

require_once __DIR__ . '/../../core/Context.php';

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
