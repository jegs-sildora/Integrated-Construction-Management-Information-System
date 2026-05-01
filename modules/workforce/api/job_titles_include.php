<?php
/**
 * job_titles_include.php
 * Fetch active job titles into $job_titles for server-side includes (no JSON output)
 */
require_once __DIR__ . '/../../../core/ApiHelper.php';

$job_titles = [];
try {
    $res = ApiHelper::get('workforce/form-options');
    $job_titles = $res['data']['job_titles'] ?? [];
} catch(Exception $e) {
    // fail silently for include
    $job_titles = [];
}
?>
