<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

require_once __DIR__ . '/../../Database.php';
require_once __DIR__ . '/../../Logger.php';

use Budget\Database;

$db = new Database();
$conn = $db->getConnection();

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Handle JSON Input
$payload = json_decode(file_get_contents('php://input'), true) ?: [];
if (is_array($payload)) {
    $_POST = array_merge($_POST, $payload);
}

switch ($method) {
    case 'GET':
        $id = intval($_GET['id'] ?? $_GET['fetch_id'] ?? 0);
        if ($id > 0) {
            getExpenseDetails($conn, $id);
        } else {
            listExpenses($conn);
        }
        break;
    case 'POST':
        if (isset($_POST['delete_id'])) {
            deleteExpense($conn, intval($_POST['delete_id']));
        } elseif (!empty($_POST['expense_id'])) {
            updateExpense($conn, intval($_POST['expense_id']));
        } else {
            saveExpense($conn);
        }
        break;
    case 'PUT':
        $id = intval($_POST['expense_id'] ?? $_GET['id'] ?? $_GET['fetch_id'] ?? 0);
        updateExpense($conn, $id);
        break;
    case 'DELETE':
        $id = intval($_POST['expense_id'] ?? $_GET['id'] ?? $_GET['fetch_id'] ?? 0);
        deleteExpense($conn, $id);
        break;
    default:
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Method Not Allowed']);
        break;
}

function listExpenses($conn) {
    try {
        $project_id = isset($_GET['project_id']) ? intval($_GET['project_id']) : 0;
        $phase_id = isset($_GET['phase_id']) ? intval($_GET['phase_id']) : 0;
        $status = $_GET['status'] ?? '';
        $limit = isset($_GET['limit']) ? max(1, intval($_GET['limit'])) : 0;

        $where = [];
        $params = [];

        if ($project_id > 0) {
            $where[] = "project_id = ?";
            $params[] = $project_id;
        }
        if ($phase_id > 0) {
            $where[] = "phase_id = ?";
            $params[] = $phase_id;
        }
        if (!empty($status)) {
            $where[] = "status = ?";
            $params[] = strtoupper($status);
        }

        $whereSql = !empty($where) ? "WHERE " . implode(" AND ", $where) : "";
        $limitSql = $limit > 0 ? " LIMIT $limit" : '';

        $sql = "SELECT * FROM budget_expenses $whereSql ORDER BY expense_date DESC $limitSql";
        $stmt = $conn->prepare($sql);
        $stmt->execute($params);
        $expenses = $stmt->fetchAll();

        echo json_encode(['success' => true, 'expenses' => $expenses]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

function getExpenseDetails($conn, $id) {
    try {
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
    // Deprecated: kept for backward compatibility
    listExpenses($conn);
}

function saveExpense($conn) {
    try {
        $data = $_POST;
        if (!$data) throw new Exception('Invalid JSON data');

        $project_id = intval($data['project_id'] ?? 0);
        $phase_id = isset($data['phase_id']) ? intval($data['phase_id']) : null;
        $supplier_id = isset($data['supplier_id']) ? intval($data['supplier_id']) : null;
        $category = strtoupper($data['category'] ?? 'MATERIALS');
        $description = trim($data['description'] ?? '');
        $amount = floatval($data['amount'] ?? 0);
        $expense_date = $data['expense_date'] ?? date('Y-m-d');
        $status = strtoupper($data['status'] ?? 'PENDING');

        $created_by = isset($_SERVER['HTTP_X_USER_ID']) ? intval($_SERVER['HTTP_X_USER_ID']) : (isset($data['created_by']) ? intval($data['created_by']) : null);

        $sql = "INSERT INTO budget_expenses (project_id, phase_id, supplier_id, category, description, amount, expense_date, status, created_by) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?) RETURNING expense_id";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$project_id, $phase_id, $supplier_id, $category, $description, $amount, $expense_date, $status, $created_by]);
        $expense_id = intval($stmt->fetchColumn());

        Logger::create('Budget', "Created expense: $description (₱" . number_format($amount, 2) . ")", $expense_id);

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

function updateExpense($conn, $expense_id) {
    try {
        if ($expense_id <= 0) throw new Exception('Invalid expense ID');

        $data = $_POST;
        $project_id = intval($data['project_id'] ?? 0);
        $phase_id = isset($data['phase_id']) ? intval($data['phase_id']) : null;
        $supplier_id = isset($data['supplier_id']) ? intval($data['supplier_id']) : null;
        $category = strtoupper($data['category'] ?? 'MATERIALS');
        $description = trim($data['description'] ?? '');
        $amount = floatval($data['amount'] ?? 0);
        $expense_date = $data['expense_date'] ?? date('Y-m-d');
        $status = strtoupper($data['status'] ?? 'PENDING');

        $sql = "UPDATE budget_expenses SET project_id = ?, phase_id = ?, supplier_id = ?, category = ?, description = ?, amount = ?, expense_date = ?, status = ? WHERE expense_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$project_id, $phase_id, $supplier_id, $category, $description, $amount, $expense_date, $status, $expense_id]);

        Logger::update('Budget', "Updated expense ID #$expense_id", $expense_id);

        echo json_encode(['success' => true, 'message' => 'Expense updated successfully']);
    } catch (Exception $e) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

function deleteExpense($conn, $expense_id) {
    try {
        if ($expense_id <= 0) throw new Exception('Invalid expense ID');

        $stmt = $conn->prepare("DELETE FROM budget_expenses WHERE expense_id = ?");
        $stmt->execute([$expense_id]);

        Logger::delete('Budget', "Deleted expense ID #$expense_id", $expense_id);

        echo json_encode(['success' => true, 'message' => 'Expense deleted successfully']);
    } catch (Exception $e) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

