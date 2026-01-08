<?php
/**
 * job_titles_include.php
 * Fetch active job titles into $job_titles for server-side includes (no JSON output)
 */
// Use existing $conn if present, otherwise load database helper
if(!isset($conn)){
    require_once __DIR__ . '/../../../../config/database.php';
}

$job_titles = [];
try{
    $stmt = $conn->prepare("SELECT job_title_id, title_name, department, default_daily_rate, default_monthly_salary FROM workforce_job_titles WHERE is_active = 1 ORDER BY title_name");
    $stmt->execute();
    $job_titles = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
} catch(Exception $e) {
    // fail silently for include
    $job_titles = [];
}

?>
