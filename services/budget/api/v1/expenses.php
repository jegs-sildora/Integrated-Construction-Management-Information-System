<?php
/**
 * expenses.php - Unified Expenses API v1
 */
header('Content-Type: application/json');
require_once __DIR__ . '/../../Database.php';

use Budget\Database;

$db = Database::getConnection();
$method = $_SERVER['REQUEST_METHOD'];

try {
    switch ($method) {
        case 'GET':
            if (isset($_GET['id']) || isset($_GET['fetch_id'])) {
                $id = $_GET['id'] ?? $_GET['fetch_id'];
                $stmt = $db->prepare("SELECT * FROM budget_expenses WHERE expense_id = ?");
                $stmt->execute([$id]);
                echo json_encode(['success' => true, 'expense' => $stmt->fetch()]);
            } elseif (isset($_GET['project_id'])) {
                $stmt = $db->prepare("SELECT * FROM budget_expenses WHERE project_id = ? ORDER BY expense_date DESC");
                $stmt->execute([$_GET['project_id']]);
                echo json_encode(['success' => true, 'expenses' => $stmt->fetchAll()]);
            } else {
                $stmt = $db->prepare("SELECT * FROM budget_expenses ORDER BY expense_date DESC");
                $stmt->execute();
                echo json_encode(['success' => true, 'expenses' => $stmt->fetchAll()]);
            }
            break;

        case 'POST':
            $input = json_decode(file_get_contents('php://input'), true);
            $sql = "INSERT INTO budget_expenses (project_id, phase_id, category, description, amount, expense_date) VALUES (?, ?, ?, ?, ?, ?) RETURNING expense_id";
            $stmt = $db->prepare($sql);
            $stmt->execute([$input['project_id'], $input['phase_id'], $input['category'], $input['description'], $input['amount'], $input['expense_date'] ?? date('Y-m-d')]);
            echo json_encode(['success' => true, 'expense_id' => $stmt->fetchColumn()]);
            break;
    }
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
