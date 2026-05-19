<?php
/**
 * Budget Summary API v1 - Budget Service
 * Returns financial overview for a specific project.
 */
header('Content-Type: application/json');
require_once __DIR__ . '/../../Database.php';

$conn = Database::getConnection();

$project_id = isset($_GET['project_id']) ? intval($_GET['project_id']) : 0;

if ($project_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Project ID required']);
    exit;
}

try {
    // 0. Fetch Project Name from Project Service
    $project_name = 'Unknown Project';
    $project_service_url = 'http://project-service/api/v1/projects.php?fetch_id=' . $project_id;
    $ch = curl_init($project_service_url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 3);
    $proj_res = curl_exec($ch);
    if (curl_getinfo($ch, CURLINFO_HTTP_CODE) === 200) {
        $proj_data = json_decode($proj_res, true);
        $project_name = $proj_data['project']['project_name'] ?? 'Unknown Project';
    }

    // 1. Get Approved Budget from Proposals
    $stmt = $conn->prepare("SELECT SUM(total_amount) as total_budget FROM budget_proposals WHERE project_id = ? AND status = 'APPROVED'");
    $stmt->execute([$project_id]);
    $budget = floatval($stmt->fetch()['total_budget'] ?? 0);

    // 2. Get Total Expenses
    $stmt = $conn->prepare("SELECT SUM(amount) as total_spent FROM budget_expenses WHERE project_id = ? AND status = 'APPROVED'");
    $stmt->execute([$project_id]);
    $spent = floatval($stmt->fetch()['total_spent'] ?? 0);

    // 3. Get Breakdown by Category
    $stmt = $conn->prepare("SELECT category, SUM(amount) as amount FROM budget_expenses WHERE project_id = ? AND status = 'APPROVED' GROUP BY category");
    $stmt->execute([$project_id]);
    $categories = $stmt->fetchAll();

    echo json_encode([
        'success' => true,
        'data' => [
            'project_name' => $project_name,
            'total_budget' => $budget,
            'total_spent' => $spent,
            'actual_spending' => $spent,
            'remaining' => $budget - $spent,
            'utilization' => $budget > 0 ? round(($spent / $budget) * 100, 2) : 0,
            'category_breakdown' => $categories
        ]
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
