<?php
/**
 * ========================= API: Print =========================
 * Purpose: Wrapper for workforce reports specifically for printing.
 * Redirects logic to reports.php v1.
 * ============================================================================ 
 */

// Since both print_report.php and reports.php share the same data source 
// in the microservice architecture, we use the centralized reports.php.

require_once __DIR__ . '/reports.php';
