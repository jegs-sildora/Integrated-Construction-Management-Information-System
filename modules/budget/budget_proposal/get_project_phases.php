<?php
// modules/budget/budget_proposal/get_project_phases.php
require_once __DIR__ . '/../../../config/config.php';

header('Content-Type: application/json');

// 1. Validate Input
if (!isset($_GET['project_id'])) {
    echo json_encode(['success' => false, 'message' => 'Project ID required']);
    exit;
}

$project_id = intval($_GET['project_id']);

$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($conn->connect_error) {
    echo json_encode(['success' => false, 'message' => 'DB Connection Error']);
    exit;
}

// 2. Corrected SQL Query
// We select directly from 'icmis_project_phases' where project_id matches.
$sql = "SELECT phase_id, phase_name, start_date, end_date, duration 
        FROM icmis_project_phases 
        WHERE project_id = ? 
        ORDER BY start_date ASC"; // Optional: sort by date

$stmt = $conn->prepare($sql);
if (!$stmt) {
    echo json_encode(['success' => false, 'message' => 'Query Prepare Failed: ' . $conn->error]);
    exit;
}

$stmt->bind_param("i", $project_id);
$stmt->execute();
$result = $stmt->get_result();

$phases = [];
while ($row = $result->fetch_assoc()) {
    $phases[] = [
        'id' => $row['phase_id'],
        'name' => $row['phase_name'],
        'start_date' => $row['start_date'],
        'end_date' => $row['end_date'],
        'duration' => $row['duration']
    ];
}

// 3. Return JSON
echo json_encode(['success' => true, 'phases' => $phases]);

$stmt->close();
$conn->close();
?>