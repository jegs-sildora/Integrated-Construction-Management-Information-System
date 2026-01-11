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
    $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
    $per_page = isset($_GET['per_page']) ? max(1, intval($_GET['per_page'])) : 10;
    $offset = ($page - 1) * $per_page;

    // Count total matching rows
    if ($search !== '') {
        $countStmt = $conn->prepare("SELECT COUNT(*) AS cnt FROM procurement_suppliers WHERE (supplier_name LIKE ? OR contact_person LIKE ?) AND UPPER(TRIM(status)) = 'ACTIVE'");
        $param = '%' . $search . '%';
        $countStmt->bind_param('ss', $param, $param);
        $countStmt->execute();
        $total = $countStmt->get_result()->fetch_assoc()['cnt'] ?? 0;
        $countStmt->close();

        $sql = "SELECT supplier_id, supplier_name, contact_person, contact_number, email, address, status
            FROM procurement_suppliers
            WHERE (supplier_name LIKE ? OR contact_person LIKE ?) AND UPPER(TRIM(status)) = 'ACTIVE'
            ORDER BY supplier_id ASC
            LIMIT ? OFFSET ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('ssii', $param, $param, $per_page, $offset);
        $stmt->execute();
        $res = $stmt->get_result();
    } else {
        $countRes = $conn->query("SELECT COUNT(*) as cnt FROM procurement_suppliers WHERE UPPER(TRIM(status)) = 'ACTIVE'");
        $total = $countRes->fetch_assoc()['cnt'] ?? 0;

        $sql = "SELECT supplier_id, supplier_name, contact_person, contact_number, email, address, status
            FROM procurement_suppliers
            WHERE UPPER(TRIM(status)) = 'ACTIVE'
            ORDER BY supplier_id ASC
            LIMIT ? OFFSET ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('ii', $per_page, $offset);
        $stmt->execute();
        $res = $stmt->get_result();
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

    $total_pages = $per_page > 0 ? intval(ceil($total / $per_page)) : 0;
    echo json_encode([
        'success' => true,
        'suppliers' => $suppliers,
        'total' => intval($total),
        'page' => $page,
        'per_page' => $per_page,
        'total_pages' => $total_pages
    ]);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
} finally {
    if (isset($stmt) && $stmt) $stmt->close();
    $conn->close();
}
?>