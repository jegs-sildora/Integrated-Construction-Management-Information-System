<?php
/**
 * Reports API - Download Previously Generated Report
 * ICMIS - Integrated Construction Management Information System
 */

session_start();
require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../../config/database.php';

$report_id = intval($_GET['id'] ?? 0);

if ($report_id <= 0) {
    die("Invalid Report ID");
}

// Fetch report metadata from microservice
$res = ApiHelper::get("reports/reports?id=$report_id");
if ($res['status'] !== 200) {
    die("Report not found via API");
}

// Since reports are currently generated on-the-fly via print_report.php?type=X
// and the microservice only stores metadata, we redirect to print_report.php
// with the correct parameters retrieved from the metadata.

$report = null;
// The list endpoint with filter by id might return a single object or list
// Based on our reports service implementation: GET ?id=X returns {success:true, report:{...}}
$report = $res['data']['reports'][0] ?? null; 

if (!$report) {
    die("Report details not found");
}

$type = $report['report_type'];
$project_id = $report['project_id'];

header("Location: ../print_report.php?type=$type&project_id=$project_id");
exit;
?>