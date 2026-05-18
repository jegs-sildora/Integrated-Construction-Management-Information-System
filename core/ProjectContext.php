<?php
/**
 * core/ProjectContext.php
 * 
 * Centralized Project Context Manager for the Microservices Architecture.
 * Handles fetching, setting, and persisting the selected project ID across all modules.
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/ApiHelper.php';

// Ensure session is started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

class ProjectContext {
    
    /**
     * Get the current project context ID.
     * Priority: 
     * 1. URL Parameter (?project_id=X)
     * 2. Session Variable ($_SESSION['selected_project_id'])
     * 3. Fallback: First available project from API
     * 
     * @return int The selected project ID, or 0 if none found.
     */
    public static function getProjectId() {
        // 1. Check URL parameter (explicit selection)
        if (isset($_GET['project_id']) && !empty($_GET['project_id'])) {
            $project_id = intval($_GET['project_id']);
            self::setProjectId($project_id);
            return $project_id;
        }
        
        // 2. Check session (persisted selection)
        if (isset($_SESSION['selected_project_id']) && !empty($_SESSION['selected_project_id'])) {
            return intval($_SESSION['selected_project_id']);
        }
        
        // 3. Fallback: Get first available project from Project Service
        try {
            $res = ApiHelper::get('project/projects');
            $projects = $res['data']['projects'] ?? [];
            if (!empty($projects)) {
                $project_id = intval($projects[0]['project_id']);
                self::setProjectId($project_id);
                return $project_id;
            }
        } catch (Exception $e) {
            error_log("ProjectContext Error: " . $e->getMessage());
        }
        
        return 0;
    }
    
    /**
     * Set the current project ID in session.
     * 
     * @param int $project_id
     */
    public static function setProjectId($project_id) {
        $_SESSION['selected_project_id'] = intval($project_id);
    }
    
    /**
     * Clear the project context from session.
     */
    public static function clear() {
        unset($_SESSION['selected_project_id']);
        unset($_SESSION['selected_phase']); // Clear phase too if it exists
    }
    
    /**
     * Build a URL that preserves the current project context.
     * 
     * @param string $base_url
     * @param array $params Additional query parameters
     * @return string
     */
    public static function buildUrl($base_url, $params = []) {
        $project_id = self::getProjectId();
        if ($project_id > 0 && !isset($params['project_id'])) {
            $params['project_id'] = $project_id;
        }
        
        if (empty($params)) {
            return $base_url;
        }
        
        $separator = (strpos($base_url, '?') === false) ? '?' : '&';
        return $base_url . $separator . http_build_query($params);
    }
}
