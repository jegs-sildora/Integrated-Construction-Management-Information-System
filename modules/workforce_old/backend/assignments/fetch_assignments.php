<?php
require_once '../config.php'; // your PDO connection

header('Content-Type: application/json');

try {
    // Fetch assignments with employee, project, task, phase info
    $sql = "
    SELECT 
        a.assignment_id,
        e.first_name,
        e.last_name,
        e.employee_id,
        a.role,
        a.start_date,
        a.end_date,
        a.status,
        p.project_name,
        a.task_description AS task_name,
        ph.phase_name
    FROM assignments a
    INNER JOIN employees e ON a.employee_id = e.employee_id
    INNER JOIN projects p ON a.project_id = p.project_id
    LEFT JOIN project_phases ph ON a.phase_id = ph.phase_id
    ORDER BY e.employee_id ASC
";

    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $assignments = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Prepare data for frontend
    $response = [];
    foreach ($assignments as $a) {
        $initials = strtoupper(substr($a['first_name'], 0, 1) . substr($a['last_name'], 0, 1));
        $avatarColor = "#" . substr(md5($a['employee_id']), 0, 6); // deterministic color per employee

        $response[] = [
            'assignment_id' => $a['assignment_id'],
            'employee_name' => $a['first_name'] . " " . $a['last_name'],
            'employee_id' => $a['employee_id'],
            'initials' => $initials,
            'avatar_color' => $avatarColor,
            'project_name' => $a['project_name'],
            'task_name' => $a['task_name'],
            'phase_name' => $a['phase_name'],
            'role' => $a['role'],
            'start_date' => $a['start_date'],
            'end_date' => $a['end_date'],
            'status' => $a['status']
        ];
    }

    echo json_encode([
        'success' => true,
        'assignments' => $response
    ]);

} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
    exit;
}
