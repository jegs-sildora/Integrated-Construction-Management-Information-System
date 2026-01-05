<?php
// export_pdf.php - Central Data Handler & Logger

// Ensure no output happens before JSON
ob_start();

header('Content-Type: application/json');

// ERROR HANDLING
function jsonErrorHandler($errno, $errstr, $errfile, $errline) {
    ob_clean();
    echo json_encode(['success' => false, 'message' => "Server Error: $errstr at $errfile:$errline"]);
    exit;
}
set_error_handler("jsonErrorHandler");

try {
    // Include config for database connection
    require_once __DIR__ . '/../../../config/config.php';
    
    // Create database connection
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    if ($conn->connect_error) {
        throw new Exception("Database connection failed: " . $conn->connect_error);
    }
    $conn->set_charset("utf8mb4");

    if (!isset($_POST['project_id']) || empty($_POST['project_id'])) {
        throw new Exception('Project ID is required');
    }

    $project_id = intval($_POST['project_id']);
    $report_type = isset($_POST['report_type']) ? $_POST['report_type'] : 'budget-summary';
    $target_phase = isset($_POST['phase']) ? $_POST['phase'] : '';
    $user_name = "John Doe"; // Static for now

    // ==========================================
    // 1. LOG THE REPORT (Recent Reports Feature)
    // ==========================================
    
    // Create friendly names
    $friendly_type = ucwords(str_replace('-', ' ', $report_type));
    $context_label = ($target_phase && $target_phase !== 'all') ? "Phase $target_phase" : "Overall";
    
    // Determine report title
    $report_name = $friendly_type;
    if ($report_type === 'phase-analysis' && $target_phase && $target_phase !== 'all') {
        $report_name .= " (Phase $target_phase)";
    } elseif ($report_type === 'cash-flow') {
        $report_name .= " (Monthly)";
    } elseif ($report_type === 'labor-analysis') {
        $report_name = "Labor Cost Analysis";
    }

    // Insert into database
    // This makes the "Recent Reports" table work
    $check_table = $conn->query("SHOW TABLES LIKE 'budget_generated_reports'");
    if ($check_table && $check_table->num_rows > 0) {
        $log_sql = "INSERT INTO budget_generated_reports (project_id, report_type, report_name, context, generated_by) VALUES (?, ?, ?, ?, ?)";
        $stmt_log = $conn->prepare($log_sql);
        if ($stmt_log) {
            $stmt_log->bind_param("issss", $project_id, $report_type, $report_name, $context_label, $user_name);
            $stmt_log->execute();
            $stmt_log->close();
        }
    }

    // ==========================================
    // 2. FETCH DATA LOGIC
    // ==========================================

    // Fetch Common Project Details
    $sql_project = "SELECT p.project_id, p.project_code, p.project_name, p.location,
                    (SELECT COALESCE(SUM(bp.total_amount), 0) FROM budget_proposals bp WHERE bp.project_id = p.project_id AND bp.status = 'APPROVED') as total_budget,
                    (SELECT COALESCE(SUM(e.amount), 0) FROM budget_expenses e WHERE e.project_id = p.project_id AND e.status = 'APPROVED') as actual_spending
                    FROM icmis_projects p WHERE p.project_id = ?";

    $stmt = $conn->prepare($sql_project);
    $stmt->bind_param("i", $project_id);
    $stmt->execute();
    $project = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$project) throw new Exception('Project not found');

    $response_data = ['project' => $project];

    // Handle Specific Report Logic
    switch ($report_type) {
        case 'phase-analysis':
            $all_phases = ['Phase 1: Mobilization', 'Phase 2: Structural', 'Phase 3: MEPFS', 'Phase 4: Finishing'];
            $phase_data = [];
            foreach ($all_phases as $phase) {
                if ($target_phase !== '' && $target_phase !== 'all') {
                    $phase_num = substr($phase, 6, 1);
                    if ($target_phase != $phase_num) continue;
                }
                
                // Budget
                $sql_b = "SELECT COALESCE(SUM(bp.total_amount), 0) as budget 
                          FROM budget_proposals bp 
                          LEFT JOIN icmis_project_phases pp ON bp.phase_id = pp.phase_id 
                          WHERE bp.project_id = ? AND pp.phase_name = ? AND bp.status = 'APPROVED'";
                $stmt = $conn->prepare($sql_b);
                $stmt->bind_param("is", $project_id, $phase);
                $stmt->execute();
                $budget = floatval($stmt->get_result()->fetch_assoc()['budget']);
                $stmt->close();

                // Actual
                $sql_a = "SELECT COALESCE(SUM(e.amount), 0) as spent 
                          FROM budget_expenses e 
                          LEFT JOIN icmis_project_phases pp ON e.phase_id = pp.phase_id 
                          WHERE e.project_id = ? AND pp.phase_name = ? AND e.status = 'APPROVED'";
                $stmt = $conn->prepare($sql_a);
                $stmt->bind_param("is", $project_id, $phase);
                $stmt->execute();
                $spent = floatval($stmt->get_result()->fetch_assoc()['spent']);
                $stmt->close();

                $phase_data[] = [
                    'phase_name' => $phase,
                    'budget' => $budget,
                    'spent' => $spent,
                    'variance' => $budget - $spent,
                    'utilization' => $budget > 0 ? ($spent / $budget) * 100 : 0
                ];
            }
            $response_data['phases'] = $phase_data;
            break;

        case 'labor-analysis':
            $sql_labor = "SELECT e.expense_date, e.description, e.amount, pp.phase_name as phase 
                          FROM budget_expenses e
                          LEFT JOIN icmis_project_phases pp ON e.phase_id = pp.phase_id
                          WHERE e.project_id = ? AND e.category = 'LABOR' AND e.status = 'APPROVED' 
                          ORDER BY e.expense_date DESC";
            $stmt = $conn->prepare($sql_labor);
            $stmt->bind_param("i", $project_id);
            $stmt->execute();
            $result = $stmt->get_result();
            
            $labor_expenses = [];
            $total_labor = 0;
            while($row = $result->fetch_assoc()) {
                $labor_expenses[] = $row;
                $total_labor += floatval($row['amount']);
            }
            $stmt->close();
            
            $response_data['labor_expenses'] = $labor_expenses;
            $response_data['total_labor'] = $total_labor;
            break;

        case 'cash-flow':
            $sql_flow = "SELECT DATE_FORMAT(expense_date, '%Y-%m') as month_year, SUM(amount) as monthly_total 
                         FROM budget_expenses 
                         WHERE project_id = ? AND status = 'APPROVED' 
                         GROUP BY month_year ORDER BY month_year ASC";
            $stmt = $conn->prepare($sql_flow);
            $stmt->bind_param("i", $project_id);
            $stmt->execute();
            $result = $stmt->get_result();
            
            $cash_flow = [];
            while($row = $result->fetch_assoc()) {
                $cash_flow[] = $row;
            }
            $stmt->close();
            
            $response_data['cash_flow'] = $cash_flow;
            break;

        case 'expense-log':
        default: 
            $sql_expenses = "SELECT e.expense_date, e.category, e.description, s.supplier_name, e.amount, e.status, pp.phase_name as phase
                             FROM budget_expenses e
                             LEFT JOIN procurement_suppliers s ON e.supplier_id = s.supplier_id
                             LEFT JOIN icmis_project_phases pp ON e.phase_id = pp.phase_id
                             WHERE e.project_id = ? AND e.status = 'APPROVED'
                             ORDER BY e.expense_date DESC";
            $stmt = $conn->prepare($sql_expenses);
            $stmt->bind_param("i", $project_id);
            $stmt->execute();
            $result = $stmt->get_result();

            $expenses = [];
            $totals = ['materials' => 0, 'labor' => 0, 'equipment' => 0];

            while ($row = $result->fetch_assoc()) {
                $expenses[] = $row;
                $cat = strtoupper($row['category']);
                $amt = floatval($row['amount']);
                if ($cat == 'MATERIALS') $totals['materials'] += $amt;
                elseif ($cat == 'LABOR') $totals['labor'] += $amt;
                elseif ($cat == 'EQUIPMENT') $totals['equipment'] += $amt;
            }
            
            $response_data['expenses'] = $expenses;
            $response_data['totals'] = array_merge($totals, ['grand_total' => array_sum($totals)]);
            break;
    }

    // Success Output
    ob_clean();
    echo json_encode(['success' => true, 'data' => $response_data]);

} catch (Exception $e) {
    ob_clean();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>