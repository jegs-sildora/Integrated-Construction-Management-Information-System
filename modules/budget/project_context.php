<?php
// /modules/budget/project_context.php
// Bridging legacy context to Project Microservice

if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../../core/ApiHelper.php';

class ProjectContext {
    public static function getProjectId() {
        return $_SESSION['current_project_id'] ?? 0;
    }
}
