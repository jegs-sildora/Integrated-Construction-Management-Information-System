<?php
/**
 * proposals.php - Unified Proposals API v1
 */
header('Content-Type: application/json');
require_once __DIR__ . '/../../Database.php';

$db = Database::getConnection();
$method = $_SERVER['REQUEST_METHOD'];

try {
    switch ($method) {
        case 'GET':
            if (isset($_GET['id']) || isset($_GET['fetch_id'])) {
                $id = $_GET['id'] ?? $_GET['fetch_id'];
                $stmt = $db->prepare("SELECT * FROM budget_proposals WHERE proposal_id = ?");
                $stmt->execute([$id]);
                echo json_encode(['success' => true, 'proposal' => $stmt->fetch()]);
            } elseif (isset($_GET['project_id'])) {
                $stmt = $db->prepare("SELECT * FROM budget_proposals WHERE project_id = ? ORDER BY created_at DESC");
                $stmt->execute([$_GET['project_id']]);
                echo json_encode(['success' => true, 'proposals' => $stmt->fetchAll()]);
            } else {
                $stmt = $db->prepare("SELECT * FROM budget_proposals ORDER BY created_at DESC");
                $stmt->execute();
                echo json_encode(['success' => true, 'proposals' => $stmt->fetchAll()]);
            }
            break;

        case 'POST':
            $input = json_decode(file_get_contents('php://input'), true);
            $sql = "INSERT INTO budget_proposals (project_id, phase_id, title, total_amount, status) VALUES (?, ?, ?, ?, ?) RETURNING proposal_id";
            $stmt = $db->prepare($sql);
            $stmt->execute([$input['project_id'], $input['phase_id'], $input['title'], $input['total_amount'], $input['status'] ?? 'Pending']);
            echo json_encode(['success' => true, 'proposal_id' => $stmt->fetchColumn()]);
            break;

        case 'PUT':
            $input = json_decode(file_get_contents('php://input'), true);
            $id = $input['proposal_id'] ?? null;
            if (!$id) throw new Exception("ID required");
            $sql = "UPDATE budget_proposals SET title = ?, total_amount = ?, status = ? WHERE proposal_id = ?";
            $stmt = $db->prepare($sql);
            $stmt->execute([$input['title'], $input['total_amount'], $input['status'], $id]);
            echo json_encode(['success' => true]);
            break;

        case 'DELETE':
            $id = $_GET['id'] ?? null;
            if (!$id) throw new Exception("ID required");
            $stmt = $db->prepare("DELETE FROM budget_proposals WHERE proposal_id = ?");
            $stmt->execute([$id]);
            echo json_encode(['success' => true]);
            break;
    }
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
