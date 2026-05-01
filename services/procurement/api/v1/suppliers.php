<?php
/**
 * Suppliers API v1 - Procurement Service
 */
header('Content-Type: application/json');
require_once __DIR__ . '/../../Database.php';

$db = Database::getConnection();
$method = $_SERVER['REQUEST_METHOD'];

try {
    if ($method === 'GET') {
        if (isset($_GET['id'])) {
            $stmt = $db->prepare("SELECT * FROM suppliers WHERE supplier_id = ?");
            $stmt->execute([$_GET['id']]);
            echo json_encode(['success' => true, 'supplier' => $stmt->fetch()]);
        } else {
            $stmt = $db->query("SELECT * FROM suppliers ORDER BY supplier_name ASC");
            echo json_encode(['success' => true, 'suppliers' => $stmt->fetchAll()]);
        }
    } elseif ($method === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
        
        if (isset($input['delete_id'])) {
            $stmt = $db->prepare("DELETE FROM suppliers WHERE supplier_id = ?");
            $stmt->execute([$input['delete_id']]);
            echo json_encode(['success' => true, 'message' => 'Supplier deleted']);
            exit;
        }

        $supplier_id = $input['supplier_id'] ?? 0;
        if ($supplier_id > 0) {
            $stmt = $db->prepare("UPDATE suppliers SET supplier_name = ?, contact_person = ?, email = ? WHERE supplier_id = ?");
            $stmt->execute([$input['supplier_name'], $input['contact_person'] ?? '', $input['email'] ?? '', $supplier_id]);
            echo json_encode(['success' => true, 'message' => 'Supplier updated']);
        } else {
            $stmt = $db->prepare("INSERT INTO suppliers (supplier_name, contact_person, email) VALUES (?, ?, ?)");
            $stmt->execute([$input['supplier_name'], $input['contact_person'] ?? '', $input['email'] ?? '']);
            echo json_encode(['success' => true, 'message' => 'Supplier created', 'id' => $db->lastInsertId()]);
        }
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
