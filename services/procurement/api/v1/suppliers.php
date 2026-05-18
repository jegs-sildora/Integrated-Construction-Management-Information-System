<?php
/**
 * Suppliers API v1 - Procurement Service
 */
header('Content-Type: application/json');
require_once __DIR__ . '/../../Database.php';
require_once __DIR__ . '/../../Logger.php';

$db = Database::getConnection();
$method = $_SERVER['REQUEST_METHOD'];

// Handle JSON input for POST/PUT/DELETE
$payload = [];
$raw = file_get_contents('php://input');
if (!empty($raw)) {
    $decoded = json_decode($raw, true);
    if (is_array($decoded)) {
        $payload = $decoded;
    }
}
if (!empty($_POST)) {
    $payload = array_merge($payload, $_POST);
}

try {
    $id = intval($_GET['id'] ?? $_GET['fetch_id'] ?? $payload['id'] ?? $payload['supplier_id'] ?? $payload['delete_id'] ?? 0);

    if ($method === 'GET') {
        if ($id > 0) {
            $stmt = $db->prepare("SELECT * FROM suppliers WHERE supplier_id = ?");
            $stmt->execute([$id]);
            $supplier = $stmt->fetch();

            echo json_encode([
                'success' => (bool)$supplier,
                'supplier' => $supplier ?: null
            ]);
            exit;
        }

        $search = trim($_GET['search'] ?? '');
        $page = max(1, intval($_GET['page'] ?? 1));
        $per_page = max(1, intval($_GET['per_page'] ?? 10));
        $offset = ($page - 1) * $per_page;

        $where = [];
        $params = [];
        if ($search !== '') {
            $where[] = "(supplier_name ILIKE ? OR contact_person ILIKE ? OR email ILIKE ? OR contact_number ILIKE ?)";
            $searchParam = "%$search%";
            $params[] = $searchParam;
            $params[] = $searchParam;
            $params[] = $searchParam;
            $params[] = $searchParam;
        }

        $whereSql = !empty($where) ? "WHERE " . implode(" AND ", $where) : "";

        $sql = "SELECT * FROM suppliers $whereSql ORDER BY supplier_name ASC LIMIT $per_page OFFSET $offset";
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $suppliers = $stmt->fetchAll();

        $countSql = "SELECT COUNT(*) as total FROM suppliers $whereSql";
        $stmtCount = $db->prepare($countSql);
        $stmtCount->execute($params);
        $total = intval($stmtCount->fetch()['total'] ?? 0);

        echo json_encode([
            'success' => true,
            'suppliers' => $suppliers,
            'total' => $total,
            'page' => $page,
            'per_page' => $per_page
        ]);
        exit;
    }

    if ($method === 'DELETE' || ($method === 'POST' && isset($payload['delete_id']))) {
        $supplier_id = $id;
        if ($supplier_id <= 0) {
            echo json_encode(['success' => false, 'message' => 'Invalid supplier ID']);
            exit;
        }

        $stmtName = $db->prepare("SELECT supplier_name FROM suppliers WHERE supplier_id = ?");
        $stmtName->execute([$supplier_id]);
        $name = $stmtName->fetchColumn();

        $stmt = $db->prepare("DELETE FROM suppliers WHERE supplier_id = ?");
        $stmt->execute([$supplier_id]);

        Logger::delete('Procurement', "Deleted supplier: $name", $supplier_id);

        echo json_encode(['success' => true, 'message' => 'Supplier deleted']);
        exit;
    }

    if ($method === 'POST' || $method === 'PUT') {
        $supplier_id = intval($payload['supplier_id'] ?? $payload['id'] ?? 0);
        $name = trim($payload['supplier_name'] ?? $payload['name'] ?? '');
        $contact_person = trim($payload['contact_person'] ?? $payload['person'] ?? '');
        $contact_number = trim($payload['contact_number'] ?? $payload['phone'] ?? '');
        $email = trim($payload['email'] ?? '');
        $address = trim($payload['address'] ?? '');
        $status = trim($payload['status'] ?? 'Active');

        if ($name === '') {
            echo json_encode(['success' => false, 'message' => 'Supplier name is required']);
            exit;
        }

        if ($supplier_id > 0) {
            $stmt = $db->prepare("UPDATE suppliers SET supplier_name = ?, contact_person = ?, contact_number = ?, email = ?, address = ?, status = ? WHERE supplier_id = ?");
            $stmt->execute([$name, $contact_person, $contact_number, $email, $address, $status, $supplier_id]);

            Logger::update('Procurement', "Updated supplier: $name", $supplier_id);

            echo json_encode(['success' => true, 'message' => 'Supplier updated']);
        } else {
            $stmt = $db->prepare("INSERT INTO suppliers (supplier_name, contact_person, contact_number, email, address, status) VALUES (?, ?, ?, ?, ?, ?) RETURNING supplier_id");
            $stmt->execute([$name, $contact_person, $contact_number, $email, $address, $status]);
            $new_id = intval($stmt->fetchColumn());

            Logger::create('Procurement', "Created supplier: $name", $new_id);

            echo json_encode(['success' => true, 'message' => 'Supplier created', 'id' => $new_id]);
        }
        exit;
    }

    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

