<?php
error_reporting(0);
ini_set('display_errors', 0);
/**
 * ========================= API: Download Report =========================
 * Purpose: Redirects to the print-optimized view of a report based on 
 * its metadata.
 * ============================================================================ 
 */

require_once __DIR__ . '/../../Database.php';

$db = \Database::getConnection();
$report_id = intval($_GET['id'] ?? 0);

if ($report_id <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid report ID']);
    exit;
}

$stmt = $db->prepare("SELECT * FROM generated_reports WHERE report_id = ?");
$stmt->execute([$report_id]);
$report = $stmt->fetch();

if (!$report) {
    http_response_code(404);
    echo json_encode(['success' => false, 'message' => 'Report not found']);
    exit;
}

// Since reports are currently generated on-the-fly via print_report.php
// in the modules directory (transition phase), we return the parameters 
// needed for redirection or just return the redirect instruction.

echo json_encode([
    'success' => true,
    'redirect_url' => "modules/reports/print_report.php?type=" . $report['report_type'] . "&project_id=" . $report['project_id'],
    'report' => $report
]);
