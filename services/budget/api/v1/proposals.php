<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
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
            getProposalDetails($conn, intval($_GET['id']));
        } else {
            listProposals($conn);
        }
        break;
    case 'POST':
        saveProposal($conn);
        break;
    case 'PUT':
        updateProposal($conn);
        break;
    case 'DELETE':
        deleteProposal($conn);
        break;
    default:
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Method Not Allowed']);
        break;
}

function listProposals($conn) {
    try {
        $sql = "SELECT * FROM budget_proposals ORDER BY created_at DESC";
        $stmt = $conn->query($sql);
        $proposals = $stmt->fetchAll();
        echo json_encode(['success' => true, 'proposals' => $proposals]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

function getProposalDetails($conn, $id) {
    try {
        // Cross-boundary joins removed as per instructions
        $sql = "SELECT * FROM budget_proposals WHERE proposal_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$id]);
        $proposal = $stmt->fetch();

        if (!$proposal) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Proposal not found']);
            return;
        }

        // Fetch line items
        $sql_items = "SELECT * FROM budget_line_items WHERE proposal_id = ? ORDER BY line_item_id ASC";
        $stmt_items = $conn->prepare($sql_items);
        $stmt_items->execute([$id]);
        $items = $stmt_items->fetchAll();

        echo json_encode([
            'success' => true,
            'proposal' => $proposal,
            'items' => $items
        ]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

function saveProposal($conn) {
    try {
        $data = json_decode(file_get_contents('php://input'), true);
        if (!$data) throw new Exception('Invalid JSON data');

        $project_id = intval($data['project_id'] ?? 0);
        $phase_id = isset($data['phase_id']) ? intval($data['phase_id']) : null;
        $title = trim($data['title'] ?? '');
        $description = trim($data['description'] ?? $data['scope_description'] ?? '');
        $total_amount = floatval($data['total_amount'] ?? 0);
        $status = strtoupper($data['status'] ?? 'DRAFT');
        $created_by = isset($data['created_by']) ? intval($data['created_by']) : null;
        $items = $data['items'] ?? [];

        if ($project_id <= 0 || empty($title) || empty($items)) {
            throw new Exception('Missing required fields');
        }

        // Generate proposal code (PostgreSQL version)
        $year = date('Y');
        $sql_count = "SELECT COUNT(*) FROM budget_proposals WHERE EXTRACT(YEAR FROM created_at) = ?";
        $stmt_count = $conn->prepare($sql_count);
        $stmt_count->execute([$year]);
        $count = $stmt_count->fetchColumn() + 1;
        $code = "BP-" . $year . "-" . str_pad($count, 4, '0', STR_PAD_LEFT);

        $conn->beginTransaction();

        $sql = "INSERT INTO budget_proposals (project_id, phase_id, code, title, description, total_amount, status, created_by, created_at) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, CURRENT_TIMESTAMP) RETURNING proposal_id";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$project_id, $phase_id, $code, $title, $description, $total_amount, $status, $created_by]);
        $proposal_id = $stmt->fetchColumn();

        $sql_items = "INSERT INTO budget_line_items (proposal_id, category, item_name, quantity, unit_cost, subtotal) VALUES (?, ?, ?, ?, ?, ?)";
        $stmt_items = $conn->prepare($sql_items);

        foreach ($items as $item) {
            $category = strtoupper($item['category'] ?? '');
            if ($category === 'MATERIALS') $category = 'MATERIAL';
            
            $stmt_items->execute([
                $proposal_id,
                $category,
                $item['name'] ?? $item['item_name'],
                floatval($item['quantity']),
                floatval($item['unitCost'] ?? $item['unit_cost']),
                floatval($item['subtotal'])
            ]);
        }

        $conn->commit();

        echo json_encode([
            'success' => true,
            'message' => 'Proposal saved successfully',
            'proposal_id' => $proposal_id,
            'code' => $code
        ]);
    } catch (Exception $e) {
        if ($conn->inTransaction()) $conn->rollBack();
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

function updateProposal($conn) {
    try {
        $data = json_decode(file_get_contents('php://input'), true);
        if (!$data || !isset($data['proposal_id'])) throw new Exception('Invalid JSON or missing proposal_id');

        $proposal_id = intval($data['proposal_id']);
        $project_id = intval($data['project_id'] ?? 0);
        $phase_id = isset($data['phase_id']) ? intval($data['phase_id']) : null;
        $title = trim($data['title'] ?? '');
        $description = trim($data['description'] ?? $data['scope_description'] ?? '');
        $total_amount = floatval($data['total_amount'] ?? 0);
        $status = strtoupper($data['status'] ?? 'DRAFT');
        $items = $data['items'] ?? [];

        $conn->beginTransaction();

        $sql = "UPDATE budget_proposals SET project_id = ?, phase_id = ?, title = ?, description = ?, total_amount = ?, status = ? WHERE proposal_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$project_id, $phase_id, $title, $description, $total_amount, $status, $proposal_id]);

        // Delete old items and insert new ones
        $conn->prepare("DELETE FROM budget_line_items WHERE proposal_id = ?")->execute([$proposal_id]);

        $sql_items = "INSERT INTO budget_line_items (proposal_id, category, item_name, quantity, unit_cost, subtotal) VALUES (?, ?, ?, ?, ?, ?)";
        $stmt_items = $conn->prepare($sql_items);

        foreach ($items as $item) {
            $category = strtoupper($item['category'] ?? '');
            if ($category === 'MATERIALS') $category = 'MATERIAL';

            $stmt_items->execute([
                $proposal_id,
                $category,
                $item['name'] ?? $item['item_name'],
                floatval($item['quantity']),
                floatval($item['unitCost'] ?? $item['unit_cost']),
                floatval($item['subtotal'])
            ]);
        }

        $conn->commit();
        echo json_encode(['success' => true, 'message' => 'Proposal updated successfully']);
    } catch (Exception $e) {
        if ($conn->inTransaction()) $conn->rollBack();
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

function deleteProposal($conn) {
    try {
        $data = json_decode(file_get_contents('php://input'), true);
        $proposal_id = intval($data['proposal_id'] ?? $_GET['id'] ?? 0);

        if ($proposal_id <= 0) throw new Exception('Invalid proposal ID');

        $conn->beginTransaction();
        $conn->prepare("DELETE FROM budget_line_items WHERE proposal_id = ?")->execute([$proposal_id]);
        $conn->prepare("DELETE FROM budget_proposals WHERE proposal_id = ?")->execute([$proposal_id]);
        $conn->commit();

        echo json_encode(['success' => true, 'message' => 'Proposal deleted successfully']);
    } catch (Exception $e) {
        if ($conn->inTransaction()) $conn->rollBack();
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}
