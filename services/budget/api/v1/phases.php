<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

require_once __DIR__ . '/../../Database.php';

use Budget\Database;

$db = new Database();
$conn = $db->getConnection();

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

if (!isset($_GET['project_id'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Project ID required']);
    exit;
}

$project_id = intval($_GET['project_id']);

try {
    // Ported from modules/budget/budget_proposal/get_project_phases.php
    $sql = "SELECT phase_id, phase_name, start_date, end_date, duration 
            FROM project_phases 
            WHERE project_id = ? 
            ORDER BY start_date ASC";

    $stmt = $conn->prepare($sql);
    $stmt->execute([$project_id]);
    $phases = [];

    while ($row = $stmt->fetch()) {
        $phases[] = [
            'id' => $row['phase_id'],
            'name' => $row['phase_name'],
            'start_date' => $row['start_date'],
            'end_date' => $row['end_date'],
            'duration' => $row['duration']
        ];
    }

    echo json_encode(['success' => true, 'phases' => $phases]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
