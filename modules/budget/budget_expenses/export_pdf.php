<?php
header('Content-Type: application/json');

require_once __DIR__ . '/../../../config/config.php';

$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($conn->connect_error) {
    echo json_encode(['success' => false, 'message' => 'DB connection failed: ' . $conn->connect_error]);
    exit;
}
$conn->set_charset('utf8mb4');

$project_id = isset($_POST['project_id']) ? intval($_POST['project_id']) : (isset($_GET['project_id']) ? intval($_GET['project_id']) : 0);
$report_type = isset($_POST['report_type']) ? $_POST['report_type'] : (isset($_GET['report_type']) ? $_GET['report_type'] : 'expense-log');
$phase = isset($_POST['phase']) ? $_POST['phase'] : (isset($_GET['phase']) ? $_GET['phase'] : '');

if ($project_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Project ID is required']);
    exit;
}

// Fetch basic project info
$project = ['project_id' => $project_id, 'project_name' => 'All Projects', 'project_code' => ''];
$stmt = $conn->prepare("SELECT project_id, project_name, project_code, total_budget, completion_rate FROM icmis_projects WHERE project_id = ? LIMIT 1");
$stmt->bind_param('i', $project_id);
$stmt->execute();
$res = $stmt->get_result();
if ($row = $res->fetch_assoc()) {
    $project = array_merge($project, $row);
}
$stmt->close();

$payload = ['project' => $project];

try {
    switch ($report_type) {
        case 'budget-summary':
        case 'budget_summary':
            // Fetch expenses for project
            $sql = "SELECT e.expense_id, e.expense_date, e.category, e.description, e.amount, s.supplier_name
                    FROM budget_expenses e
                    LEFT JOIN procurement_suppliers s ON e.supplier_id = s.supplier_id
                    WHERE e.project_id = ? ORDER BY e.expense_date DESC";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param('i', $project_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $expenses = [];
            $totals = ['grand_total' => 0];
            while ($r = $result->fetch_assoc()) {
                $expenses[] = $r;
                $totals['grand_total'] += floatval($r['amount']);
            }
            $stmt->close();

            $payload['expenses'] = $expenses;
            $payload['totals'] = $totals;
            break;

        case 'labor-analysis':
        case 'labor_analysis':
            // Example: use payroll_expenses table if exists
            $sql = "SELECT le.expense_id, le.expense_date, le.description, le.phase, le.amount
                    FROM payroll_expenses le
                    WHERE le.project_id = ? ORDER BY le.expense_date DESC";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param('i', $project_id);
            $stmt->execute();
            $res = $stmt->get_result();
            $list = [];
            $total = 0;
            while ($r = $res->fetch_assoc()) {
                $list[] = $r;
                $total += floatval($r['amount']);
            }
            $stmt->close();
            $payload['labor_expenses'] = $list;
            $payload['total_labor'] = $total;
            break;

        case 'cash-flow':
            // Aggregate by month from budget_expenses
            $sql = "SELECT DATE_FORMAT(expense_date, '%Y-%m') as month_year, SUM(amount) as monthly_total
                    FROM budget_expenses
                    WHERE project_id = ?
                    GROUP BY month_year
                    ORDER BY month_year DESC
                    LIMIT 24";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param('i', $project_id);
            $stmt->execute();
            $res = $stmt->get_result();
            $flow = [];
            while ($r = $res->fetch_assoc()) {
                $flow[] = $r;
            }
            $stmt->close();
            $payload['cash_flow'] = $flow;
            break;

        default:
            // default to expense-log
            $sql = "SELECT e.expense_id, e.expense_date, e.category, e.description, e.amount, s.supplier_name
                    FROM budget_expenses e
                    LEFT JOIN procurement_suppliers s ON e.supplier_id = s.supplier_id
                    WHERE e.project_id = ? ORDER BY e.expense_date DESC";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param('i', $project_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $expenses = [];
            while ($r = $result->fetch_assoc()) {
                $expenses[] = $r;
            }
            $stmt->close();
            $payload['expenses'] = $expenses;
            break;
    }

    echo json_encode(['success' => true, 'data' => $payload]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

$conn->close();
