<?php
// modules/procurement/php/fetch_suppliers.php
header('Content-Type: application/json');

// Use centralized config
require_once __DIR__ . '/../../../config/config.php';
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($conn->connect_error) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit;
}
$conn->set_charset("utf8mb4");

try {
    $search = isset($_GET['search']) ? trim($_GET['search']) : '';

    if ($search !== '') {
    $sql = "SELECT supplier_id, supplier_name, contact_person, contact_number, email, address, status
        FROM procurement_suppliers
        WHERE (supplier_name LIKE ? OR contact_person LIKE ?) AND UPPER(TRIM(status)) = 'ACTIVE'
        ORDER BY supplier_id ASC
        LIMIT 200";
        $stmt = $conn->prepare($sql);
        $param = '%' . $search . '%';
        $stmt->bind_param('ss', $param, $param);
        $stmt->execute();
        $res = $stmt->get_result();
    } else {
        $sql = "SELECT supplier_id, supplier_name, contact_person, contact_number, email, address, status
            FROM procurement_suppliers
            WHERE UPPER(TRIM(status)) = 'ACTIVE'
            ORDER BY supplier_id ASC
            LIMIT 500";
        $res = $conn->query($sql);
    }

    $suppliers = [];
    if ($res) {
        while ($row = $res->fetch_assoc()) {
            $suppliers[] = [
                'supplier_id' => $row['supplier_id'],
                'supplier_name' => $row['supplier_name'],
                'contact_person' => $row['contact_person'],
                'contact_number' => $row['contact_number'],
                'email' => $row['email'],
                'address' => $row['address'],
                'status' => $row['status'] ?? null
            ];
        }
    }

    echo json_encode(['success' => true, 'suppliers' => $suppliers]);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
} finally {
    if (isset($stmt) && $stmt) $stmt->close();
    $conn->close();
}
?>