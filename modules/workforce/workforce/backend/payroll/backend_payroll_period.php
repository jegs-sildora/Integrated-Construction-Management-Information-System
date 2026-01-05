<?php
/**
 * backend_payroll_period.php
 * Generic backend for Payroll Periods (CRUD + fetch + get_next_id)
 */

require '../config.php'; // PDO connection
header('Content-Type: application/json');

try {
    // -------------------- DELETE --------------------
    if (isset($_POST['delete_id'])) {
        $period_id = $_POST['delete_id'];

        // Prevent deletion of processed periods
        $stmt = $conn->prepare("SELECT status FROM payroll_periods WHERE period_id = ?");
        $stmt->execute([$period_id]);
        $period = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$period) throw new Exception("Payroll period not found");
        if ($period['status'] === 'Processed') {
            throw new Exception("Cannot delete a processed payroll period");
        }

        $stmt = $conn->prepare("DELETE FROM payroll_periods WHERE period_id = ?");
        $stmt->execute([$period_id]);

        echo json_encode([
            'success' => true,
            'message' => 'Payroll period deleted successfully'
        ]);
        exit;
    }

    // -------------------- GET NEXT ID --------------------
    if (isset($_GET['get_next_id'])) {
        $stmt = $conn->query("SELECT period_id FROM payroll_periods ORDER BY period_id DESC LIMIT 1");
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $lastId = $row ? intval(substr($row['period_id'], 3)) : 0;
        $nextId = "PRD" . str_pad($lastId + 1, 3, "0", STR_PAD_LEFT);

        echo json_encode(['success' => true, 'next_id' => $nextId]);
        exit;
    }

    // -------------------- FETCH SINGLE RECORD --------------------
    if (isset($_GET['fetch_id'])) {
        $period_id = $_GET['fetch_id'];
        $stmt = $conn->prepare("SELECT * FROM payroll_periods WHERE period_id = ?");
        $stmt->execute([$period_id]);
        $record = $stmt->fetch(PDO::FETCH_ASSOC);

        echo json_encode([
            'success' => (bool)$record,
            'record' => $record ?? null,
            'message' => $record ? 'Record fetched successfully' : 'Record not found'
        ]);
        exit;
    }

    // -------------------- ADD / EDIT --------------------
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $period_id      = $_POST['period_id'] ?? '';
        $period_name    = $_POST['period_name'] ?? '';
        $period_start   = $_POST['period_start'] ?? '';
        $period_end     = $_POST['period_end'] ?? '';
        $pay_date       = $_POST['pay_date'] ?? null;
        $status         = $_POST['status'] ?? 'Open';
        $processed_by   = $_POST['processed_by'] ?? null;
        $processed_date = $_POST['processed_date'] ?? null;
        $remarks        = $_POST['remarks'] ?? '';

        if (!$period_start || !$period_end) throw new Exception("Start and End dates are required");

        // Check if period exists
        $stmt = $conn->prepare("SELECT COUNT(*) FROM payroll_periods WHERE period_id = ?");
        $stmt->execute([$period_id]);
        $exists = $stmt->fetchColumn() > 0;

        if ($exists) {
            // UPDATE
            $sql = "UPDATE payroll_periods SET
                        period_name    = :period_name,
                        period_start   = :period_start,
                        period_end     = :period_end,
                        pay_date       = :pay_date,
                        status         = :status,
                        processed_by   = :processed_by,
                        processed_date = :processed_date,
                        remarks        = :remarks
                    WHERE period_id = :period_id";
            $stmt = $conn->prepare($sql);
            $stmt->execute([
                ':period_name'    => $period_name,
                ':period_start'   => $period_start,
                ':period_end'     => $period_end,
                ':pay_date'       => $pay_date ?: null,
                ':status'         => $status,
                ':processed_by'   => $processed_by ?: null,
                ':processed_date' => $processed_date ?: null,
                ':remarks'        => $remarks,
                ':period_id'      => $period_id
            ]);

            echo json_encode(['success' => true, 'message' => 'Payroll period updated successfully']);
        } else {
            // INSERT
            // Generate period_id if empty
            if (!$period_id) {
                $stmt = $conn->query("SELECT period_id FROM payroll_periods ORDER BY period_id DESC LIMIT 1");
                $row = $stmt->fetch(PDO::FETCH_ASSOC);
                $lastId = $row ? intval(substr($row['period_id'], 3)) : 0;
                $period_id = "PRD" . str_pad($lastId + 1, 3, "0", STR_PAD_LEFT);
            }

            $sql = "INSERT INTO payroll_periods
                        (period_id, period_name, period_start, period_end, pay_date, status, processed_by, processed_date, remarks)
                    VALUES
                        (:period_id, :period_name, :period_start, :period_end, :pay_date, :status, :processed_by, :processed_date, :remarks)";
            $stmt = $conn->prepare($sql);
            $stmt->execute([
                ':period_id'      => $period_id,
                ':period_name'    => $period_name,
                ':period_start'   => $period_start,
                ':period_end'     => $period_end,
                ':pay_date'       => $pay_date ?: null,
                ':status'         => $status,
                ':processed_by'   => $processed_by ?: null,
                ':processed_date' => $processed_date ?: null,
                ':remarks'        => $remarks
            ]);

            echo json_encode(['success' => true, 'message' => 'Payroll period added successfully', 'period_id' => $period_id]);
        }
        exit;
    }

    // -------------------- FETCH ALL --------------------
    $stmt = $conn->query("SELECT * FROM payroll_periods ORDER BY period_start DESC");
    $periods = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'records' => $periods
    ]);

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
