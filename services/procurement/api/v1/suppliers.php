<?php
error_reporting(0);
ini_set('display_errors', 0);
/**
 * suppliers.php - Unified Suppliers API v1
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
                $stmt = $db->prepare("SELECT * FROM suppliers WHERE supplier_id = ?");
                $stmt->execute([$id]);
                echo json_encode(['success' => true, 'supplier' => $stmt->fetch()]);
            } else {
                $stmt = $db->prepare("SELECT * FROM suppliers ORDER BY supplier_name ASC");
                $stmt->execute();
                echo json_encode(['success' => true, 'suppliers' => $stmt->fetchAll()]);
            }
            break;

        case 'POST':
            $input = json_decode(file_get_contents('php://input'), true);
            $sql = "INSERT INTO suppliers (supplier_name, contact_person, email, contact_number, address) VALUES (?, ?, ?, ?, ?) RETURNING supplier_id";
            $stmt = $db->prepare($sql);
            $stmt->execute([$input['supplier_name'], $input['contact_person'], $input['email'], $input['contact_number'] ?? $input['phone'], $input['address']]);
            echo json_encode(['success' => true, 'supplier_id' => $stmt->fetchColumn()]);
            break;

        case 'PUT':
            $input = json_decode(file_get_contents('php://input'), true);
            $id = $input['supplier_id'] ?? null;
            if (!$id) throw new Exception("Supplier ID required");
            $sql = "UPDATE suppliers SET supplier_name = ?, contact_person = ?, email = ?, contact_number = ?, address = ? WHERE supplier_id = ?";
            $stmt = $db->prepare($sql);
            $stmt->execute([$input['supplier_name'], $input['contact_person'], $input['email'], $input['contact_number'] ?? $input['phone'], $input['address'], $id]);
            echo json_encode(['success' => true]);
            break;

        case 'DELETE':
            $id = $_GET['id'] ?? null;
            if (!$id) throw new Exception("Supplier ID required");
            $stmt = $db->prepare("DELETE FROM suppliers WHERE supplier_id = ?");
            $stmt->execute([$id]);
            echo json_encode(['success' => true]);
            break;
    }
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

