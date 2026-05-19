<?php
error_reporting(0);
ini_set('display_errors', 0);
/**
 * Reports API v1 - Budget Service
 */
header('Content-Type: application/json');
require_once __DIR__ . '/../../Database.php';

$db = \Database::getConnection();
$method = $_SERVER['REQUEST_METHOD'];

try {
    if ($method === 'GET') {
        // Migration Notice: 
        // Real reports are now handled by the Reports Service via 'reports/reports'.
        // This endpoint remains as a stub for backwards compatibility during transition.

        echo json_encode([
            'success' => true,
            'message' => 'Reporting has moved to the Reports Service.',
            'reports' => []
        ]);

    } else {
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
