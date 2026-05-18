<?php
// modules/procurement/php/fetch_stockout.php
require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../../core/ApiHelper.php';
require_once __DIR__ . '/../../../core/ProjectContext.php';
header('Content-Type: application/json');

$method = $_SERVER['REQUEST_METHOD'];
$project_id = $_GET['project_id'] ?? ProjectContext::getProjectId();

$params = "?project_id=$project_id";
$res = ApiHelper::call("procurement/stockout$params", $method);

$rows = $res['data']['data'] ?? [];

// Enrich issued_to name from Workforce service
$employeeMap = [];
$empRes = ApiHelper::get('workforce/employees?action=list');
if ($empRes['status'] === 200) {
	$employees = $empRes['data']['data'] ?? [];
	foreach ($employees as $emp) {
		$employeeMap[$emp['employee_id']] = trim(($emp['first_name'] ?? '') . ' ' . ($emp['last_name'] ?? ''));
	}
}

foreach ($rows as &$row) {
	$empId = $row['issued_to_employee_id'] ?? null;
	if ($empId) {
		$row['issued_to'] = $employeeMap[$empId] ?? ('Employee #' . $empId);
	} else {
		$row['issued_to'] = $row['issued_to'] ?? 'N/A';
	}
}

http_response_code($res['status']);
echo json_encode($rows);
?>