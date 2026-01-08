<?php

require '../config.php';
header('Content-Type: application/json');

try {
    // -------------------- DELETE EMPLOYEE --------------------
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

        $num = $lastId ? intval(substr($lastId, 3)) + 1 : 1;
        $nextId = 'EMP' . str_pad($num, 3, '0', STR_PAD_LEFT);

        echo json_encode(['success' => true, 'employee_id' => $nextId]);
        exit;
    }

     // -------------------- FETCH SINGLE EMPLOYEE --------------------
if (isset($_GET['fetch_id'])) {
    $id = $_GET['fetch_id'];

    // 1️⃣ Fetch employee info
    $stmt = $conn->prepare("SELECT * FROM employees WHERE employee_id = ?");
    $stmt->execute([$id]);
    $employee = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$employee) {
        echo json_encode([
            'success' => false,
            'message' => 'Employee not found'
        ]);
        exit;
    }

    // 2️⃣ Fetch group memberships
    $stmt = $conn->prepare("
        SELECT g.group_id, g.group_name
        FROM group_memberships gm
        JOIN employee_groups g ON gm.group_id = g.group_id
        WHERE gm.employee_id = :id AND (gm.left_date IS NULL OR gm.left_date > CURDATE())
    ");
    $stmt->execute(['id' => $id]);
    $employee['groups'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 3️⃣ Fetch attendance records with hours worked and remarks
    $stmt = $conn->prepare("
        SELECT 
            attendance_date AS date,
            IF(time_out IS NOT NULL AND time_in IS NOT NULL,
                ROUND(TIME_TO_SEC(TIMEDIFF(time_out, time_in)) / 3600, 2),
                0
            ) AS hours_worked,
            status,
            remarks
        FROM attendance
        WHERE employee_id = :id
        ORDER BY attendance_date DESC
    ");
    $stmt->execute(['id' => $id]);
    $employee['attendance_records'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 4️⃣ Fetch assignments
    $stmt = $conn->prepare("
        SELECT 
            a.project_id,
            p.project_name,
            a.task_description AS task,
            a.start_date,
            a.end_date,
            a.status
        FROM assignments a
        JOIN projects p ON a.project_id = p.project_id
        WHERE a.employee_id = :id
        ORDER BY a.start_date DESC
    ");
    $stmt->execute(['id' => $id]);
    $employee['assignments'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // ✅ Return final JSON
    echo json_encode([
        'success' => true,
        'employee' => $employee,
        'message' => 'Employee fetched successfully'
    ]);
    exit;
}



    // -------------------- ADD / EDIT EMPLOYEE --------------------
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['delete_id'])) {
        // Collect POST data
        $employee_id = trim($_POST['employee_id'] ?? '');
        $first_name = trim($_POST['first_name'] ?? '');
        $middle_name = trim($_POST['middle_name'] ?? '');
        $last_name = trim($_POST['last_name'] ?? '');
        $suffix = trim($_POST['suffix'] ?? '');
        $gender = $_POST['gender'] ?? null;
        $age = !empty($_POST['age']) ? intval($_POST['age']) : null;
        $email = !empty($_POST['email']) ? $_POST['email'] : null;
        $phone = !empty($_POST['phone']) ? $_POST['phone'] : null;
        $address = !empty($_POST['address']) ? $_POST['address'] : null;
        $emergency_contact_name = !empty($_POST['emergency_contact_name']) ? $_POST['emergency_contact_name'] : null;
        $emergency_contact_phone = !empty($_POST['emergency_contact_phone']) ? $_POST['emergency_contact_phone'] : null;
        $position = trim($_POST['position'] ?? '');
        $skill_type = trim($_POST['skill_type'] ?? '');
        $employment_type = $_POST['employment_type'] ?? 'Regular';
        $payment_type = $_POST['payment_type'] ?? null;
        $daily_rate = !empty($_POST['daily_rate']) ? $_POST['daily_rate'] : null;
        $monthly_salary = !empty($_POST['monthly_salary']) ? $_POST['monthly_salary'] : null;
        $start_date = !empty($_POST['start_date']) ? $_POST['start_date'] : null;
        $end_date = !empty($_POST['end_date']) ? $_POST['end_date'] : null;
        $status = $_POST['status'] ?? 'Active';

        // Check if employee exists
        $stmt = $conn->prepare("SELECT COUNT(*) FROM employees WHERE employee_id = ?");
        $stmt->execute([$employee_id]);
        $exists = $stmt->fetchColumn() > 0;

        if ($exists) {
            // UPDATE
            $sql = "UPDATE employees SET
                        first_name = :first_name,
                        middle_name = :middle_name,
                        last_name = :last_name,
                        suffix = :suffix,
                        gender = :gender,
                        age = :age,
                        email = :email,
                        phone = :phone,
                        address = :address,
                        emergency_contact_name = :emergency_contact_name,
                        emergency_contact_phone = :emergency_contact_phone,
                        position = :position,
                        skill_type = :skill_type,
                        employment_type = :employment_type,
                        payment_type = :payment_type,
                        daily_rate = :daily_rate,
                        monthly_salary = :monthly_salary,
                        start_date = :start_date,
                        end_date = :end_date,
                        status = :status
                    WHERE employee_id = :employee_id";
            $stmt = $conn->prepare($sql);
            $stmt->execute([
                ':first_name' => $first_name,
                ':middle_name' => $middle_name,
                ':last_name' => $last_name,
                ':suffix' => $suffix,
                ':gender' => $gender,
                ':age' => $age,
                ':email' => $email,
                ':phone' => $phone,
                ':address' => $address,
                ':emergency_contact_name' => $emergency_contact_name,
                ':emergency_contact_phone' => $emergency_contact_phone,
                ':position' => $position,
                ':skill_type' => $skill_type,
                ':employment_type' => $employment_type,
                ':payment_type' => $payment_type,
                ':daily_rate' => $daily_rate,
                ':monthly_salary' => $monthly_salary,
                ':start_date' => $start_date,
                ':end_date' => $end_date,
                ':status' => $status,
                ':employee_id' => $employee_id
            ]);
            echo json_encode(['success' => true, 'message' => 'Employee updated successfully']);
        } else {
            // INSERT
            $sql = "INSERT INTO employees (
                        employee_id, first_name, middle_name, last_name, suffix, gender, age,
                        email, phone, address,
                        emergency_contact_name, emergency_contact_phone, position, skill_type,
                        employment_type, payment_type, daily_rate, monthly_salary,
                        start_date, end_date, status
                    ) VALUES (
                        :employee_id, :first_name, :middle_name, :last_name, :suffix, :gender, :age,
                        :email, :phone, :address,
                        :emergency_contact_name, :emergency_contact_phone, :position, :skill_type,
                        :employment_type, :payment_type, :daily_rate, :monthly_salary,
                        :start_date, :end_date, :status
                    )";
            $stmt = $conn->prepare($sql);
            $stmt->execute([
                ':employee_id' => $employee_id,
                ':first_name' => $first_name,
                ':middle_name' => $middle_name,
                ':last_name' => $last_name,
                ':suffix' => $suffix,
                ':gender' => $gender,
                ':age' => $age,
                ':email' => $email,
                ':phone' => $phone,
                ':address' => $address,
                ':emergency_contact_name' => $emergency_contact_name,
                ':emergency_contact_phone' => $emergency_contact_phone,
                ':position' => $position,
                ':skill_type' => $skill_type,
                ':employment_type' => $employment_type,
                ':payment_type' => $payment_type,
                ':daily_rate' => $daily_rate,
                ':monthly_salary' => $monthly_salary,
                ':start_date' => $start_date,
                ':end_date' => $end_date,
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
