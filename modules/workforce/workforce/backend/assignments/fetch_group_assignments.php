<?php
require '../config.php';
header('Content-Type: application/json');

try {
    // Fetch all group assignments with project, phase, and group details
    $stmt = $conn->query("
        SELECT 
            ga.group_assignment_id,
            ga.group_id,
            g.group_name,
            ga.project_id,
            p.project_name,
            ga.phase_id,
            ph.phase_name,
            ga.task_description,
            ga.role,
            ga.start_date,
            ga.end_date,
            ga.status,
            g.group_leader_id
        FROM group_assignments ga
        LEFT JOIN employee_groups g ON ga.group_id = g.group_id
        LEFT JOIN projects p ON ga.project_id = p.project_id
        LEFT JOIN project_phases ph ON ga.phase_id = ph.phase_id
        ORDER BY ga.start_date DESC
    ");

    $assignments = [];

    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        // Get group leaders name
        $leaderName = "";
        if (!empty($row['group_leader_id'])) {
            $leaderStmt = $conn->prepare("SELECT first_name, last_name FROM employees WHERE employee_id = ?");
            $leaderStmt->execute([$row['group_leader_id']]);
            $leader = $leaderStmt->fetch(PDO::FETCH_ASSOC);
            if ($leader) {
                $leaderName = $leader['first_name'] . " " . $leader['last_name'];
            }
        }

        $assignments[] = [
            "group_assignment_id" => $row['group_assignment_id'],
            "group_id" => $row['group_id'],
            "group_name" => $row['group_name'],
            "group_leaders" => $leaderName,
            "project_name" => $row['project_name'],
            "task_description" => $row['task_description'],
            "phase_name" => $row['phase_name'],
            "role" => $row['role'],
            "start_date" => $row['start_date'],
            "end_date" => $row['end_date'],
            "status" => $row['status']
        ];
    }

    echo json_encode([
        "success" => true,
        "assignments" => $assignments
    ]);

} catch (PDOException $e) {
    echo json_encode([
        "success" => false,
        "message" => "Database error: " . $e->getMessage()
    ]);
}
