<?php
require '../config.php'; // PDO connection
header('Content-Type: application/json');

try {
    // -------------------- DELETE PROJECT --------------------
    if (isset($_POST['delete_id'])) {
        $project_id = $_POST['delete_id'];
        $stmt = $conn->prepare("DELETE FROM projects WHERE project_id = :project_id");
        $stmt->execute([':project_id' => $project_id]);

        echo json_encode([
            'success' => $stmt->rowCount() > 0,
            'message' => $stmt->rowCount() > 0 ? 'Project deleted successfully' : 'Project not found'
        ]);
        exit;
    }

    // -------------------- GET NEXT PROJECT ID --------------------
    if (isset($_GET['get_next_id'])) {
        $stmt = $conn->query("SELECT project_id FROM projects ORDER BY project_id DESC LIMIT 1");
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($row) {
            $num = intval(substr($row['project_id'], 3)) + 1;
        } else {
            $num = 1;
        }
        $nextId = "PRJ" . str_pad($num, 3, "0", STR_PAD_LEFT);

        echo json_encode(['success' => true, 'project_id' => $nextId]);
        exit;
    }

    // -------------------- FETCH SINGLE PROJECT --------------------
    if (isset($_GET['fetch_id'])) {
        $project_id = $_GET['fetch_id'];
        $stmt = $conn->prepare("
            SELECT p.*, CONCAT(e.first_name, ' ', e.last_name) AS manager_name
            FROM projects p
            LEFT JOIN employees e ON p.project_manager_id = e.employee_id
            WHERE p.project_id = :project_id
            LIMIT 1
        ");
        $stmt->execute([':project_id' => $project_id]);
        $project = $stmt->fetch(PDO::FETCH_ASSOC);

        echo json_encode([
            'success' => (bool)$project,
            'project' => $project ?: null,
            'message' => $project ? 'Project fetched successfully' : 'Project not found'
        ]);
        exit;
    }

    // -------------------- ADD / EDIT PROJECT --------------------
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['delete_id'])) {
        $project_id = $_POST['project_id'] ?? '';
        $project_name = $_POST['project_name'] ?? '';
        $project_manager = $_POST['project_manager_id'] ?? null;
        $description = $_POST['description'] ?? '';
        $start_date = $_POST['start_date'] ?? null;
        $end_date = $_POST['end_date'] ?? null;
        $status = $_POST['status'] ?? 'Planning';
        $budget = $_POST['budget'] ?? 0;

        // Check if project exists
        $stmt = $conn->prepare("SELECT COUNT(*) FROM projects WHERE project_id = ?");
        $stmt->execute([$project_id]);
        $exists = $stmt->fetchColumn() > 0;

        if ($exists) {
            // UPDATE
            $sql = "UPDATE projects SET 
                        project_name = :project_name,
                        project_manager_id = :project_manager_id,
                        description = :description,
                        start_date = :start_date,
                        end_date = :end_date,
                        status = :status,
                        budget = :budget
                    WHERE project_id = :project_id";
            $stmt = $conn->prepare($sql);
            $stmt->execute([
                ':project_name' => $project_name,
                ':project_manager_id' => $project_manager,
                ':description' => $description,
                ':start_date' => $start_date,
                ':end_date' => $end_date,
                ':status' => $status,
                ':budget' => $budget,
                ':project_id' => $project_id
            ]);

            echo json_encode(['success' => true, 'message' => 'Project updated successfully']);
        } else {
            // INSERT
            $sql = "INSERT INTO projects
                        (project_id, project_name, project_manager_id, description, start_date, end_date, status, budget)
                    VALUES
                        (:project_id, :project_name, :project_manager_id, :description, :start_date, :end_date, :status, :budget)";
            $stmt = $conn->prepare($sql);
            $stmt->execute([
                ':project_id' => $project_id,
                ':project_name' => $project_name,
                ':project_manager_id' => $project_manager,
                ':description' => $description,
                ':start_date' => $start_date,
                ':end_date' => $end_date,
                ':status' => $status,
                ':budget' => $budget
            ]);

            echo json_encode(['success' => true, 'message' => 'Project added successfully', 'project_id' => $project_id]);
        }
        exit;
    }

    // -------------------- FETCH ALL PROJECTS --------------------
    $stmt = $conn->prepare("
        SELECT p.*, CONCAT(e.first_name, ' ', e.last_name) AS manager_name
        FROM projects p
        LEFT JOIN employees e ON p.project_manager_id = e.employee_id
        ORDER BY p.start_date DESC
    ");
    $stmt->execute();
    $projects = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'projects' => $projects
    ]);

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
