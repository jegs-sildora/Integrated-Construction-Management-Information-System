<?php
require '../config.php';
header('Content-Type: application/json');

$employeeId = $_GET['employee_id'] ?? null;
$date       = $_GET['date'] ?? date('Y-m-d');

if (!$employeeId) {
  echo json_encode(['success' => false, 'message' => 'Missing employee ID']);
  exit;
}

$sql = "
  SELECT 
    a.assignment_id,
    a.project_id,
    p.project_name
  FROM assignments a
  JOIN projects p ON p.project_id = a.project_id
  WHERE a.employee_id = ?
    AND a.start_date <= ?
  ORDER BY a.start_date DESC
  LIMIT 1
";

$stmt = $conn->prepare($sql);
$stmt->execute([$employeeId, $date]);
$assignment = $stmt->fetch(PDO::FETCH_ASSOC);

echo json_encode([
  'success' => true,
  'assignment' => $assignment ?: null
]);
