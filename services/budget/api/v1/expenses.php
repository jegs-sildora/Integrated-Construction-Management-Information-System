<?php
/**
 * expenses.php - Unified Expenses API v1
 */
header('Content-Type: application/json');
require_once __DIR__ . '/../../Database.php';

$db = \Database::getConnection();
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
            $project_id = intval($input['project_id'] ?? 0);
            $phase_id = intval($input['phase_id'] ?? 0);

            if ($project_id <= 0) throw new Exception("Valid Project ID required");

            // Inter-service Validation
            $project_service_base = rtrim(getenv('PROJECT_SERVICE_URL') ?: 'http://project-service', '/');
            
            // Verify Project
            $ch = curl_init($project_service_base . '/api/v1/projects.php?fetch_id=' . $project_id);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 3);
            $res = curl_exec($ch);
            $proj_data = json_decode($res, true);
            if (!isset($proj_data['success']) || !$proj_data['success']) {
                throw new Exception("Invalid Project ID: Project does not exist in Project Service");
            }

            // Verify Phase if provided
            if ($phase_id > 0) {
                $ch = curl_init($project_service_base . '/api/v1/phases.php?fetch_id=' . $phase_id);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_TIMEOUT, 3);
                $res = curl_exec($ch);
                $phase_data = json_decode($res, true);
                if (!isset($phase_data['success']) || !$phase_data['success']) {
                    throw new Exception("Invalid Phase ID: Phase does not exist in Project Service");
                }
            }

            $sql = "INSERT INTO budget_expenses (project_id, phase_id, category, description, amount, expense_date) VALUES (?, ?, ?, ?, ?, ?) RETURNING expense_id";
            $stmt = $db->prepare($sql);
            $stmt->execute([$project_id, $phase_id, $input['category'], $input['description'], $input['amount'], $input['expense_date'] ?? date('Y-m-d')]);
            echo json_encode(['success' => true, 'expense_id' => $stmt->fetchColumn()]);
            break;

        case 'PUT':
            $input = json_decode(file_get_contents('php://input'), true);
            $id = $input['expense_id'] ?? null;
            if (!$id) throw new Exception("ID required");
            $sql = "UPDATE budget_expenses SET category = ?, description = ?, amount = ?, expense_date = ?, status = ? WHERE expense_id = ?";
            $stmt = $db->prepare($sql);
            $stmt->execute([$input['category'], $input['description'], $input['amount'], $input['expense_date'], $input['status'], $id]);
            echo json_encode(['success' => true]);
            break;

        case 'DELETE':
            $id = $_GET['id'] ?? null;
            if (!$id) throw new Exception("ID required");
            $stmt = $db->prepare("DELETE FROM budget_expenses WHERE expense_id = ?");
            $stmt->execute([$id]);
            echo json_encode(['success' => true]);
            break;
    }
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
