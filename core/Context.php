<?php
/**
 * core/Context.php
 * 
 * Migration Bridge for legacy Context functions.
 * Redirects to the modern ProjectContext class.
 */

require_once __DIR__ . '/ProjectContext.php';

/**
 * Get the current project context
 */
function getProjectContext($conn = null) {
    return ProjectContext::getProjectId();
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
function buildContextUrl($base_url, $params = []) {
    return ProjectContext::buildUrl($base_url, $params);
}
