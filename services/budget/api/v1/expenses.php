<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

require_once __DIR__ . '/../../Database.php';

use Budget\Database;

$db = new Database();
$conn = $db->getConnection();

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'OPTIONS') {
    http_response_code(200);
    exit;
}

switch ($method) {
    case 'GET':
        if (isset($_GET['id'])) {
            getExpenseDetails($conn, intval($_GET['id']));
        } elseif (isset($_GET['project_id'])) {
            getPhaseExpenses($conn);
        } else {
            listExpenses($conn);
        }
        break;
    case 'POST':
        saveExpense($conn);
        break;
    default:
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Method Not Allowed']);
        break;
}

function listExpenses($conn) {
    try {
        $sql = "SELECT * FROM budget_expenses ORDER BY expense_date DESC";
        $stmt = $conn->query($sql);
        $expenses = $stmt->fetchAll();
        echo json_encode(['success' => true, 'expenses' => $expenses]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

function getExpenseDetails($conn, $id) {
    try {
        // No cross-boundary joins
        $sql = "SELECT * FROM budget_expenses WHERE expense_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$id]);
        $expense = $stmt->fetch();

        if (!$expense) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Expense not found']);
            return;
        }

        echo json_encode(['success' => true, 'expense' => $expense]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

function getPhaseExpenses($conn) {
    try {
        $project_id = intval($_GET['project_id']);
        $phase_id = isset($_GET['phase_id']) ? intval($_GET['phase_id']) : null;
        
        $sql = "SELECT * FROM budget_expenses WHERE project_id = ?";
        $params = [$project_id];
        
        if ($phase_id) {
            $sql .= " AND phase_id = ?";
            $params[] = $phase_id;
        }
        
        $sql .= " ORDER BY expense_date DESC";
        
        $stmt = $conn->prepare($sql);
        $stmt->execute($params);
        $expenses = $stmt->fetchAll();

        echo json_encode([
            'success' => true,
            'expenses' => $expenses,
            'count' => count($expenses)
        ]);
    } catch (Exception $e) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

function saveExpense($conn) {
    try {
        $data = json_decode(file_get_contents('php://input'), true);
        if (!$data) throw new Exception('Invalid JSON data');

        // Logic for saving expense (can be used for sync from Procurement)
        $project_id = intval($data['project_id'] ?? 0);
        $phase_id = isset($data['phase_id']) ? intval($data['phase_id']) : null;
        $supplier_id = isset($data['supplier_id']) ? intval($data['supplier_id']) : null;
        $category = strtoupper($data['category'] ?? 'MATERIALS');
        $description = trim($data['description'] ?? '');
        $amount = floatval($data['amount'] ?? 0);
        $expense_date = $data['expense_date'] ?? date('Y-m-d');
        $status = strtoupper($data['status'] ?? 'PENDING');
        $created_by = isset($data['created_by']) ? intval($data['created_by']) : null;

        $sql = "INSERT INTO budget_expenses (project_id, phase_id, supplier_id, category, description, amount, expense_date, status, created_by) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?) RETURNING expense_id";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$project_id, $phase_id, $supplier_id, $category, $description, $amount, $expense_date, $status, $created_by]);
        $expense_id = $stmt->fetchColumn();

        echo json_encode([
            'success' => true,
            'message' => 'Expense saved successfully',
            'expense_id' => $expense_id
        ]);
    } catch (Exception $e) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}
