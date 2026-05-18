<?php
/**
 * ========================= API: Project Phases =========================
 * Purpose: Proxy to fetch phases from the Project microservice for 
 * use in the Workforce module.
 * ============================================================================ 
 */

header('Content-Type: application/json');

// Get Project Service URL
$project_service_url = getenv('PROJECT_SERVICE_URL') ?: 'http://project-service';

// Standardize parameters from query string
$params = !empty($_SERVER['QUERY_STRING']) ? '?' . $_SERVER['QUERY_STRING'] : '';
$url = $project_service_url . '/api/v1/phases.php' . $params;

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 5);

// Forward Authorization header
if (isset($_SERVER['HTTP_AUTHORIZATION'])) {
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: ' . $_SERVER['HTTP_AUTHORIZATION']]);
}

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

http_response_code($httpCode ?: 500);
echo $response ?: json_encode(['success' => false, 'message' => 'Project service unreachable']);
