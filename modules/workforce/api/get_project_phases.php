<?php
// File: modules/workforce/api/get_project_phases.php
header('Content-Type: application/json');

// Adjust this path if your project_context.php is in a different location relative to this file
include __DIR__ . '/../project_context.php'; 

$conn = getWorkforceConnection();
$project_id = isset($_GET['project_id']) ? intval($_GET['project_id']) : 0;

if ($project_id > 0) {
    // Fetch phases for the selected project
    $stmt = $conn->prepare("SELECT phase_id, phase_name FROM icmis_project_phases WHERE project_id = ? ORDER BY start_date ASC, phase_name ASC");
    $stmt->bind_param("i", $project_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $phases = [];
    while ($row = $result->fetch_assoc()) {
        $phases[] = $row;
    }
    
    echo json_encode(['success' => true, 'phases' => $phases]);
    $stmt->close();
} else {
    echo json_encode(['success' => false, 'phases' => []]);
}

$conn->close();
?>