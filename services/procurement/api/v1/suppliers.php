<?php
/**
 * Suppliers API v1 - Procurement Service
 */
header('Content-Type: application/json');
require_once __DIR__ . '/../../Database.php';
require_once __DIR__ . '/../../Logger.php';

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
            $supplier_id = intval($input['delete_id']);
            // Get name for logging
            $stmtName = $db->prepare("SELECT supplier_name FROM suppliers WHERE supplier_id = ?");
            $stmtName->execute([$supplier_id]);
            $name = $stmtName->fetchColumn();

            $stmt = $db->prepare("DELETE FROM suppliers WHERE supplier_id = ?");
            $stmt->execute([$supplier_id]);
            
            Logger::delete('Procurement', "Deleted supplier: $name", $supplier_id);
            
            echo json_encode(['success' => true, 'message' => 'Supplier deleted']);
            exit;
        }

        $supplier_id = intval($input['supplier_id'] ?? 0);
        $name = $input['supplier_name'];
        
        if ($supplier_id > 0) {
            $stmt = $db->prepare("UPDATE suppliers SET supplier_name = ?, contact_person = ?, email = ? WHERE supplier_id = ?");
            $stmt->execute([$name, $input['contact_person'] ?? '', $input['email'] ?? '', $supplier_id]);
            
            Logger::update('Procurement', "Updated supplier: $name", $supplier_id);
            
            echo json_encode(['success' => true, 'message' => 'Supplier updated']);
        } else {
            $stmt = $db->prepare("INSERT INTO suppliers (supplier_name, contact_person, email) VALUES (?, ?, ?)");
            $stmt->execute([$name, $input['contact_person'] ?? '', $input['email'] ?? '']);
            $new_id = intval($db->lastInsertId());
            
            Logger::create('Procurement', "Created supplier: $name", $new_id);
            
            echo json_encode(['success' => true, 'message' => 'Supplier created', 'id' => $new_id]);
        }
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
