<?php
require '../config.php';
header('Content-Type: application/json');

try {
    // -------------------- DELETE GROUP --------------------
    if (isset($_POST['delete_id'])) {
        $group_id = $_POST['delete_id'];

        // Delete group (will cascade to memberships if FK is set with ON DELETE CASCADE)
        $stmt = $conn->prepare("DELETE FROM employee_groups WHERE group_id = :group_id");
        $stmt->execute(['group_id' => $group_id]);

        echo json_encode([
            'success' => $stmt->rowCount() > 0,
            'message' => $stmt->rowCount() > 0 ? 'Group deleted successfully' : 'Group not found or cannot delete due to dependencies'
        ]);
        exit;
    }

    // -------------------- GET NEXT GROUP ID --------------------
    if (isset($_GET['get_next_id'])) {
        $stmt = $conn->query("SELECT group_id FROM employee_groups ORDER BY group_id DESC LIMIT 1");
        $lastId = $stmt->fetchColumn();
        $num = $lastId ? intval(substr($lastId, 3)) + 1 : 1;
        $next_id = 'GRP' . str_pad($num, 3, '0', STR_PAD_LEFT);
        echo json_encode(['success' => true, 'next_id' => $next_id]);
        exit;
    }

    // -------------------- FETCH SINGLE GROUP --------------------
    if (isset($_GET['fetch_id'])) {
        $group_id = $_GET['fetch_id'];
        $stmt = $conn->prepare("SELECT * FROM employee_groups WHERE group_id = ?");
        $stmt->execute([$group_id]);
        $group = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($group) {
            $empStmt = $conn->query("SELECT employee_id, first_name, last_name FROM employees");
            $employees = $empStmt->fetchAll(PDO::FETCH_ASSOC);

            $memStmt = $conn->prepare("SELECT employee_id FROM group_memberships WHERE group_id = :group_id AND (left_date IS NULL OR left_date > CURDATE())");
            $memStmt->execute(['group_id' => $group_id]);
            $members = $memStmt->fetchAll(PDO::FETCH_COLUMN);

            echo json_encode([
                'success' => true,
                'group' => [
                    'group_id' => $group['group_id'],
                    'group_name' => $group['group_name'],
                    'leader_id' => $group['group_leader_id'],
                    'description' => $group['description'],
                    'status' => $group['status'],
                    'members' => $members
                ],
                'employees' => $employees
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Group not found']);
        }
        exit;
    }

    // -------------------- CREATE / UPDATE GROUP --------------------
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $group_id     = $_POST['group_id'] ?? '';
        $group_name   = trim($_POST['group_name'] ?? '');
        $group_leader = $_POST['group_leader_id'] ?? null;
        $description  = trim($_POST['description'] ?? '');
        $status       = $_POST['status'] ?? 'Active';
        $members      = $_POST['group_members'] ?? [];

        if (empty($group_name) || empty($group_leader)) {
            echo json_encode(['success' => false, 'message' => 'Group Name and Leader are required']);
            exit;
        }

        $stmt = $conn->prepare("SELECT COUNT(*) FROM employee_groups WHERE group_id = ?");
        $stmt->execute([$group_id]);
        $exists = $stmt->fetchColumn() > 0;

        if ($exists) {
            // --- UPDATE EXISTING GROUP ---
            $stmt = $conn->prepare("
                UPDATE employee_groups SET 
                    group_name = :group_name,
                    group_leader_id = :leader_id,
                    description = :description,
                    status = :status,
                    updated_at = NOW()
                WHERE group_id = :group_id
            ");
            $stmt->execute([
                ':group_name' => $group_name,
                ':leader_id' => $group_leader,
                ':description' => $description,
                ':status' => $status,
                ':group_id' => $group_id
            ]);

            // Delete old members first
            $stmt = $conn->prepare("DELETE FROM group_memberships WHERE group_id = :group_id");
            $stmt->execute(['group_id' => $group_id]);
        } else {
            // --- CREATE NEW GROUP ---
            $stmt = $conn->prepare("
                INSERT INTO employee_groups (group_id, group_name, group_leader_id, description, status, created_at, updated_at)
                VALUES (:group_id, :group_name, :leader_id, :description, :status, NOW(), NOW())
            ");

            // Use provided group_id or generate one
            if (empty($group_id)) {
                $stmt2 = $conn->query("SELECT group_id FROM employee_groups ORDER BY group_id DESC LIMIT 1");
                $lastId = $stmt2->fetchColumn();
                $num = $lastId ? intval(substr($lastId, 3)) + 1 : 1;
                $group_id = 'GRP' . str_pad($num, 3, '0', STR_PAD_LEFT);
            }

            $stmt->execute([
                ':group_id' => $group_id,
                ':group_name' => $group_name,
                ':leader_id' => $group_leader,
                ':description' => $description,
                ':status' => $status
            ]);
        }

        // Ensure leader is included
        if (!in_array($group_leader, $members)) $members[] = $group_leader;

        // Insert members
        $stmt = $conn->prepare("
            INSERT INTO group_memberships (employee_id, group_id, role_in_group, joined_date, created_at)
            VALUES (:employee_id, :group_id, :role, CURDATE(), NOW())
        ");
        foreach ($members as $emp_id) {
            $stmt->execute([
                ':employee_id' => $emp_id,
                ':group_id' => $group_id,
                ':role' => ($emp_id == $group_leader) ? 'Leader' : 'Member'
            ]);
        }

        echo json_encode(['success' => true, 'message' => $exists ? 'Group updated successfully' : 'Group created successfully', 'group_id' => $group_id]);
        exit;
    }

    // -------------------- FETCH ALL GROUPS --------------------
    $stmt = $conn->prepare("
        SELECT g.group_id, g.group_name, g.group_leader_id,
               CONCAT(e.first_name, ' ', e.last_name) AS leader_name,
               g.description, g.status
        FROM employee_groups g
        LEFT JOIN employees e ON g.group_leader_id = e.employee_id
        ORDER BY g.group_name ASC
    ");
    $stmt->execute();
    $groups = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['success' => true, 'groups' => $groups]);

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
