<?php
require 'config.php';
header('Content-Type: application/json');

$period_id = $_GET['period_id'] ?? null;

if (!$period_id) {
    echo json_encode(['success' => false, 'message' => 'Period ID missing']);
    exit;
}

$stmt = $conn->prepare("SELECT * FROM payroll_periods WHERE period_id = ?");
$stmt->execute([$period_id]);
$period = $stmt->fetch(PDO::FETCH_ASSOC);

if ($period) {
    echo json_encode(['success' => true, 'period' => $period]);
} else {
    echo json_encode(['success' => false, 'message' => 'Payroll period not found']);
}
