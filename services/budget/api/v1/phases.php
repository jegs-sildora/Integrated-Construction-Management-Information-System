<?php
/**
 * Budget Phases API v1 - Budget Service
 * Aggregates budget data per phase by communicating with the Project Service.
 */
header('Content-Type: application/json');

require_once __DIR__ . '/../../Database.php';

use Budget\Database;

$db = new Database();
$conn = $db->getConnection();

$project_id = isset($_GET['project_id']) ? intval($_GET['project_id']) : 0;

if ($project_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Project ID required']);
    exit;
}

try {
    // 1. Fetch Phases from Project Service via Gateway (Internal Call)
    $project_service_url = 'http://project-service/api/v1/phases.php?project_id=' . $project_id;
    $ch = curl_init($project_service_url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
    $project_res = curl_exec($ch);
    $project_status = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    if ($project_status !== 200) {
        throw new Exception("Could not fetch phases from Project Service (Status: $project_status)");
    }

    $project_data = json_decode($project_res, true);
    $raw_phases = $project_data['phases'] ?? [];

    // 2. Fetch Budget Allocations (Approved Proposals)
    $stmt = $conn->prepare("SELECT phase_id, SUM(total_amount) as allocated FROM budget_proposals WHERE project_id = ? AND status = 'APPROVED' GROUP BY phase_id");
    $stmt->execute([$project_id]);
    $allocations = $stmt->fetchAll(PDO::FETCH_KEY_PAIR) ?: [];

    // 3. Fetch Actual Spending
    $stmt = $conn->prepare("SELECT phase_id, SUM(amount) as spent, COUNT(*) as expense_count FROM budget_expenses WHERE project_id = ? AND status = 'APPROVED' GROUP BY phase_id");
    $stmt->execute([$project_id]);
    $spending_raw = $stmt->fetchAll();
    
    $spending = [];
    foreach ($spending_raw as $row) {
        $spending[$row['phase_id']] = [
            'spent' => floatval($row['spent']),
            'count' => intval($row['expense_count'])
        ];
    }

    // 4. Transform into format expected by UI
    $phases_data = [];
    $colors = ['blue', 'green', 'purple', 'orange', 'red', 'indigo'];
    $icons = ['excavator' => 'truck', 'foundation' => 'layers', 'structure' => 'home', 'finishing' => 'paint-bucket'];
    
    foreach ($raw_phases as $index => $rp) {
        $phase_id = $rp['phase_id'] ?? $rp['id'];
        $phase_name = $rp['phase_name'] ?? $rp['name'];
        $allocated = floatval($allocations[$phase_id] ?? 0);
        $spent = floatval($spending[$phase_id]['spent'] ?? 0);
        $expense_count = intval($spending[$phase_id]['count'] ?? 0);
        $utilization = $allocated > 0 ? ($spent / $allocated) * 100 : 0;
        
        // Determine Status
        $status = 'Upcoming';
        $now = date('Y-m-d');
        if ($rp['status'] === 'Completed') {
            $status = 'Completed';
        } elseif ($utilization > 100) {
            $status = 'Over Budget';
        } elseif ($rp['start_date'] <= $now && ($rp['end_date'] >= $now || empty($rp['end_date']))) {
            $status = 'Active';
        }

        // Map icons based on name keywords
        $icon = 'activity';
        foreach ($icons as $key => $val) {
            if (stripos($phase_name, $key) !== false) {
                $icon = $val;
                break;
            }
        }

        $phases_data[$phase_name] = [
            'phase_id' => $phase_id,
            'status' => $status,
            'color' => $colors[$index % count($colors)],
            'icon' => $icon,
            'date_range' => ($rp['start_date'] ? date('M d, Y', strtotime($rp['start_date'])) : 'TBD') . ' - ' . ($rp['end_date'] ? date('M d, Y', strtotime($rp['end_date'])) : 'TBD'),
            'allocated' => $allocated,
            'spent' => $spent,
            'remaining' => $allocated - $spent,
            'utilization' => $utilization,
            'expense_count' => $expense_count
        ];
    }

    echo json_encode([
        'success' => true,
        'phases' => $phases_data
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
