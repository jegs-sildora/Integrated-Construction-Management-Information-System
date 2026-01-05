<?php

require '../config.php';
header('Content-Type: application/json');

try {
    // -------------------- DELETE EMPLOYEE (optional) --------------------
    if (isset($_POST['delete_id'])) {
        $id = $_POST['delete_id'];
        $stmt = $conn->prepare("DELETE FROM employees WHERE employee_id = :id");
        $stmt->execute(['id' => $id]);

        echo json_encode([
            'success' => $stmt->rowCount() > 0,
            'message' => $stmt->rowCount() > 0 ? 'Employee deleted successfully' : 'Employee not found'
        ]);
        exit;
    }

    // -------------------- GET NEXT EMPLOYEE ID --------------------
    if (isset($_GET['get_next_id'])) {
        $stmt = $conn->prepare("SELECT employee_id FROM employees ORDER BY employee_id DESC LIMIT 1");
        $stmt->execute();
        $lastId = $stmt->fetch(PDO::FETCH_ASSOC)['employee_id'] ?? null;

        if ($lastId) {
            $num = intval(substr($lastId, 3)) + 1; // Assumes "EMP" prefix
        } else {
            $num = 1;
        }

        $nextId = 'EMP' . str_pad($num, 3, '0', STR_PAD_LEFT);
        echo json_encode(['success' => true, 'employee_id' => $nextId]);
        exit;
    }

    // -------------------- FETCH SINGLE EMPLOYEE --------------------
    if (isset($_GET['fetch_id'])) {
        $id = $_GET['fetch_id'];
        $stmt = $conn->prepare("SELECT * FROM employees WHERE employee_id = ?");
        $stmt->execute([$id]);
        $employee = $stmt->fetch(PDO::FETCH_ASSOC);

        echo json_encode([
            'success' => (bool)$employee,
            'employee' => $employee ?? null,
            'message' => $employee ? 'Employee fetched successfully' : 'Employee not found'
        ]);
        exit;
    }

    // -------------------- ADD / EDIT EMPLOYEE --------------------
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['delete_id'])) {
        // Collect POST data
        $employee_id = trim($_POST['employee_id'] ?? '');
        $first_name = trim($_POST['first_name'] ?? '');
        $last_name = trim($_POST['last_name'] ?? '');
        $email = !empty($_POST['email']) ? $_POST['email'] : null;
        $phone = !empty($_POST['phone']) ? $_POST['phone'] : null;
        $address = !empty($_POST['address']) ? $_POST['address'] : null;
        $emergency_contact_name = !empty($_POST['emergency_contact_name']) ? $_POST['emergency_contact_name'] : null;
        $emergency_contact_phone = !empty($_POST['emergency_contact_phone']) ? $_POST['emergency_contact_phone'] : null;
        $position = trim($_POST['position'] ?? '');
        $skill_type = trim($_POST['skill_type'] ?? '');
        $employment_type = $_POST['employment_type'] ?? 'Regular';
        $daily_rate = !empty($_POST['daily_rate']) ? $_POST['daily_rate'] : null;
        $monthly_salary = !empty($_POST['monthly_salary']) ? $_POST['monthly_salary'] : null;
        $start_date = !empty($_POST['start_date']) ? $_POST['start_date'] : null;
        $status = $_POST['status'] ?? 'Active';

        // Check if employee exists
        $stmt = $conn->prepare("SELECT COUNT(*) FROM employees WHERE employee_id = ?");
        $stmt->execute([$employee_id]);
        $exists = $stmt->fetchColumn() > 0;

        if ($exists) {
            // UPDATE
            $sql = "UPDATE employees SET
                        first_name = :first_name,
                        last_name = :last_name,
                        email = :email,
                        phone = :phone,
                        address = :address,
                        emergency_contact_name = :emergency_contact_name,
                        emergency_contact_phone = :emergency_contact_phone,
                        position = :position,
                        skill_type = :skill_type,
                        employment_type = :employment_type,
                        daily_rate = :daily_rate,
                        monthly_salary = :monthly_salary,
                        start_date = :start_date,
                        status = :status
                    WHERE employee_id = :employee_id";
            $stmt = $conn->prepare($sql);
            $stmt->execute([
                ':first_name' => $first_name,
                ':last_name' => $last_name,
                ':email' => $email,
                ':phone' => $phone,
                ':address' => $address,
                ':emergency_contact_name' => $emergency_contact_name,
                ':emergency_contact_phone' => $emergency_contact_phone,
                ':position' => $position,
                ':skill_type' => $skill_type,
                ':employment_type' => $employment_type,
                ':daily_rate' => $daily_rate,
                ':monthly_salary' => $monthly_salary,
                ':start_date' => $start_date,
                ':status' => $status,
                ':employee_id' => $employee_id
            ]);
            echo json_encode(['success' => true, 'message' => 'Employee updated successfully']);
        } else {
            // INSERT
            $sql = "INSERT INTO employees (
                        employee_id, first_name, last_name, email, phone, address,
                        emergency_contact_name, emergency_contact_phone, position,
                        skill_type, employment_type, daily_rate, monthly_salary,
                        start_date, status
                    ) VALUES (
                        :employee_id, :first_name, :last_name, :email, :phone, :address,
                        :emergency_contact_name, :emergency_contact_phone, :position,
                        :skill_type, :employment_type, :daily_rate, :monthly_salary,
                        :start_date, :status
                    )";
            $stmt = $conn->prepare($sql);
            $stmt->execute([
                ':employee_id' => $employee_id,
                ':first_name' => $first_name,
                ':last_name' => $last_name,
                ':email' => $email,
                ':phone' => $phone,
                ':address' => $address,
                ':emergency_contact_name' => $emergency_contact_name,
                ':emergency_contact_phone' => $emergency_contact_phone,
                ':position' => $position,
                ':skill_type' => $skill_type,
                ':employment_type' => $employment_type,
                ':daily_rate' => $daily_rate,
                ':monthly_salary' => $monthly_salary,
                ':start_date' => $start_date,
                ':status' => $status
            ]);
            echo json_encode(['success' => true, 'message' => 'Employee added successfully', 'employee_id' => $employee_id]);
        }
        exit;
    }

    // -------------------- FETCH ALL EMPLOYEES WITH GROUPS --------------------
            $stmt = $conn->prepare("
                SELECT 
                    e.*,
                    g.group_id,
                    g.group_name
                FROM employees e
                LEFT JOIN group_memberships gm
                    ON e.employee_id = gm.employee_id
                    AND (gm.left_date IS NULL OR gm.left_date > CURDATE())
                LEFT JOIN employee_groups g
                    ON gm.group_id = g.group_id
                ORDER BY e.employee_id ASC
            ");
            $stmt->execute();
            $employees = $stmt->fetchAll(PDO::FETCH_ASSOC);

            echo json_encode([
                'success' => true,
                'employees' => $employees
            ]);


} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
