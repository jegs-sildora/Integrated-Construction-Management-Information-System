<?php
// download_payroll_pdf.php
// Accepts a POSTed JSON payload with payroll data and renders a printable HTML report.

session_start();

// Include config + DB
if (file_exists(__DIR__ . '/../../config/config.php')) include_once __DIR__ . '/../../config/config.php';
if (file_exists(__DIR__ . '/../../config/database.php')) include_once __DIR__ . '/../../config/database.php';

// Include logger if present
if (file_exists(__DIR__ . '/../../core/Logger.php')) include_once __DIR__ . '/../../core/Logger.php';

// Basic auth: require logged-in user
if (!isset($_SESSION['user_id'])) {
  header('Location: ' . (defined('BASE_URL') ? BASE_URL : '/index.php'));
  exit;
}

// Authorization: allow only certain roles to export/print payroll
$userRole = $_SESSION['user_role'] ?? 'Staff';
$allowedRoles = ['admin', 'payroll', 'hr', 'project manager', 'financial manager'];
if (!in_array(strtolower($userRole), $allowedRoles, true)) {
  http_response_code(403);
  echo 'You are not authorized to view this report';
  exit;
}

// Helper
function esc($v) { return htmlspecialchars((string)$v); }

$projectName = 'Project';
$period = '-';
$dateGen = date('F j, Y g:i A');
$totals = ['gross' => 0, 'deductions' => 0, 'net' => 0, 'count' => 0];
$rows = [];

// If POST payload provided (client-side submit), prefer that
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['payload'])) {
  $data = json_decode($_POST['payload'], true);
  if ($data) {
    $projectName = htmlspecialchars($data['projectName'] ?? $projectName);
    $period = htmlspecialchars($data['period'] ?? $period);
    $dateGen = htmlspecialchars($data['dateGen'] ?? $dateGen);
    $totals = $data['totals'] ?? $totals;
    $rows = $data['data'] ?? [];
  }

} elseif (isset($_GET['period_id'])) {
  // Server-side: render by period_id
  $period_id = intval($_GET['period_id']);
  $project_id = isset($_GET['project_id']) ? intval($_GET['project_id']) : 0;

  // Build DB connection if not available
  if (!isset($conn) || !$conn instanceof mysqli) {
    if (defined('DB_HOST')) {
      $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    } else {
      echo 'Database configuration missing'; exit;
    }
  }

  // Fetch period info
  $pstmt = $conn->prepare("SELECT start_date, end_date, pay_date, status FROM workforce_payroll_periods WHERE period_id = ? LIMIT 1");
  $pstmt->bind_param('i', $period_id);
  $pstmt->execute();
  $pres = $pstmt->get_result()->fetch_assoc();
  $pstmt->close();

  if ($pres) {
    $period = date('M j', strtotime($pres['start_date'])) . ' - ' . date('M j, Y', strtotime($pres['end_date']));
    $dateGen = date('F j, Y g:i A');

    // Fetch payroll rows
    $sql = "SELECT wp.*, CONCAT(e.first_name, ' ', e.last_name) AS fullname, e.employee_code, COALESCE(e.daily_rate, 0) as daily_rate, e.monthly_salary
        FROM workforce_payroll wp
        JOIN workforce_employees e ON wp.employee_id = e.employee_id
        " . ($project_id > 0 ? 'JOIN workforce_assignments wa ON e.employee_id = wa.employee_id' : '') . "
        WHERE wp.period_id = ? " . ($project_id > 0 ? 'AND wa.project_id = ?' : '') . "
        ORDER BY fullname ASC";

    $stmt = $conn->prepare($sql);
    if ($project_id > 0) $stmt->bind_param('ii', $period_id, $project_id); else $stmt->bind_param('i', $period_id);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($r = $res->fetch_assoc()) {
      $rows[] = [
        'code' => $r['employee_code'],
        'fullname' => $r['fullname'],
        'days_worked' => isset($r['hours_worked']) ? round($r['hours_worked']/8,2) : '-',
        'ot_hours' => $r['ot_hours'] ?? '-',
        'daily_rate' => $r['daily_rate'] ?? 0,
        'gross_pay' => $r['gross_pay'] ?? 0,
        'deductions' => ($r['gross_pay'] ?? 0) - ($r['net_pay'] ?? 0),
        'net_pay' => $r['net_pay'] ?? 0
      ];
      $totals['gross'] += floatval($r['gross_pay'] ?? 0);
      $totals['net'] += floatval($r['net_pay'] ?? 0);
      $totals['deductions'] += (($r['gross_pay'] ?? 0) - ($r['net_pay'] ?? 0));
      $totals['count']++;
    }
    $stmt->close();
  } else {
    echo 'Period not found'; exit;
  }

} else {
  echo 'No printable data provided'; exit;
}

// Audit log: record export/view action
if (class_exists('Logger')) {
  try {
    Logger::init($conn ?? null);
    $logDetails = 'Payroll report viewed/exported';
    if (!empty($period_id)) {
      Logger::export('Workforce', $logDetails . ' for period_id=' . intval($period_id), intval($period_id));
    } else {
      Logger::export('Workforce', $logDetails, null);
    }
  } catch (Throwable $e) {
    // Fail silently for logging
  }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Payroll Report - <?php echo $projectName; ?></title>
  <?php include __DIR__ . '/../../includes/head_assetsv2.php'; ?>
  <style>
    body { font-family: 'Inter', sans-serif; background-color: #f8fafc; color: #1e293b; padding: 40px; }
    .report-container { max-width: 1000px; margin: 0 auto; background: white; padding: 40px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1); border-radius: 8px; }
    @media print { @page { margin: 0.5in; size: auto; } body { background-color: white !important; color: black !important; padding: 0 !important; -webkit-print-color-adjust: exact; } .report-container { box-shadow: none !important; padding: 0 !important; width: 100% !important; max-width: none !important; } .no-print { display: none !important; } table { width: 100% !important; border-collapse: collapse !important; font-size: 10pt !important; } thead tr { background-color: #f3f4f6 !important; } thead th { border: 1px solid #9ca3af !important; padding: 8px !important; color: black !important; font-weight: bold !important; text-transform: uppercase !important; } tbody td { border: 1px solid #e5e7eb !important; padding: 8px !important; color: black !important; } .print-footer { margin-top: 50px !important; page-break-inside: avoid; } }
    .print-logo { height: 80px; width: auto; margin: 0 auto 10px auto; display: block; }
  </style>
</head>
<body>

  <div class="report-container">
    <div class="text-center border-b-2 border-slate-800 pb-6 mb-8">
      <img src="../../assets/images/nobg_logo.png" alt="ICMIS Logo" class="print-logo">
      <h1 class="text-2xl font-black uppercase tracking-wide text-slate-900 mt-2">Payroll Report</h1>
      <p class="text-sm font-medium text-slate-500 uppercase tracking-widest">Integrated Construction Management Information System</p>
      <p class="text-xs text-slate-400 mt-1">Generated on: <?php echo $dateGen; ?></p>
    </div>

    <div class="grid grid-cols-2 gap-8 mb-8 text-sm">
      <div>
        <table class="w-full">
          <tr><td class="font-bold text-slate-500 py-1 w-32">Project:</td><td class="font-bold text-slate-900 py-1"><?php echo $projectName; ?></td></tr>
          <tr><td class="font-bold text-slate-500 py-1">Period:</td><td class="text-slate-900 py-1"><?php echo $period; ?></td></tr>
        </table>
      </div>
      <div>
        <table class="w-full">
          <tr><td class="font-bold text-slate-500 py-1 w-32">Employees:</td><td class="text-slate-900 py-1"><?php echo esc($totals['count'] ?? count($rows)); ?></td></tr>
          <tr><td class="font-bold text-slate-500 py-1">Total Net Pay:</td><td class="text-slate-900 py-1 font-mono">₱<?php echo number_format((float)($totals['net'] ?? 0), 2); ?></td></tr>
        </table>
      </div>
    </div>

    <div class="mb-8 bg-slate-50 rounded-lg border border-slate-200 p-6">
      <h3 class="text-xs font-bold text-slate-500 uppercase mb-4">Summary</h3>
      <div class="grid grid-cols-3 gap-4 text-center">
        <div class="p-2 border-r border-slate-200"><p class="text-xs text-slate-500 uppercase font-bold">Total Gross</p><p class="text-xl font-black text-slate-800 mt-1 font-mono">₱<?php echo number_format((float)($totals['gross'] ?? 0), 2); ?></p></div>
        <div class="p-2 border-r border-slate-200"><p class="text-xs text-slate-500 uppercase font-bold">Deductions</p><p class="text-xl font-black text-red-700 mt-1 font-mono">₱<?php echo number_format((float)($totals['deductions'] ?? 0), 2); ?></p></div>
        <div class="p-2"><p class="text-xs text-slate-500 uppercase font-bold">Net Pay</p><p class="text-xl font-black text-slate-800 mt-1 font-mono">₱<?php echo number_format((float)($totals['net'] ?? 0), 2); ?></p></div>
      </div>
    </div>

    <div class="mb-8">
      <h3 class="text-sm font-bold text-slate-900 uppercase border-b border-slate-200 pb-2 mb-4">Payroll Lines</h3>
      <table class="w-full text-left border-collapse">
        <thead class="bg-slate-100"><tr>
          <th class="px-4 py-2 text-xs font-bold text-slate-600 uppercase border border-slate-300">Code</th>
          <th class="px-4 py-2 text-xs font-bold text-slate-600 uppercase border border-slate-300">Employee</th>
          <th class="px-4 py-2 text-xs font-bold text-slate-600 uppercase border border-slate-300 text-center">Days</th>
          <th class="px-4 py-2 text-xs font-bold text-slate-600 uppercase border border-slate-300 text-center">OT</th>
          <th class="px-4 py-2 text-xs font-bold text-slate-600 uppercase border border-slate-300 text-right">Rate</th>
          <th class="px-4 py-2 text-xs font-bold text-slate-600 uppercase border border-slate-300 text-right">Gross</th>
          <th class="px-4 py-2 text-xs font-bold text-slate-600 uppercase border border-slate-300 text-right">Deductions</th>
          <th class="px-4 py-2 text-xs font-bold text-slate-600 uppercase border border-slate-300 text-right">Net</th>
        </tr></thead>
        <tbody>
        <?php foreach ($rows as $r): ?>
          <tr>
            <td class="px-4 py-2 text-sm border border-slate-200 font-mono"><?php echo esc($r['code'] ?? '-'); ?></td>
            <td class="px-4 py-2 text-sm border border-slate-200"><?php echo esc($r['fullname'] ?? '-'); ?></td>
            <td class="px-4 py-2 text-sm border border-slate-200 text-center"><?php echo esc($r['days_worked'] ?? '-'); ?></td>
            <td class="px-4 py-2 text-sm border border-slate-200 text-center"><?php echo esc($r['ot_hours'] ?? '-'); ?></td>
            <td class="px-4 py-2 text-sm border border-slate-200 text-right font-mono">₱<?php echo number_format((float)($r['daily_rate'] ?? 0),2); ?></td>
            <td class="px-4 py-2 text-sm border border-slate-200 text-right font-mono">₱<?php echo number_format((float)($r['gross_pay'] ?? 0),2); ?></td>
            <td class="px-4 py-2 text-sm border border-slate-200 text-right font-mono">₱<?php echo number_format((float)($r['deductions'] ?? 0),2); ?></td>
            <td class="px-4 py-2 text-sm border border-slate-200 text-right font-mono font-bold text-[#e9922c]">₱<?php echo number_format((float)($r['net_pay'] ?? 0),2); ?></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>

    <div class="print-footer mt-12 pt-8">
      <div class="grid grid-cols-3 gap-8">
        <div class="text-center"><p class="text-xs font-bold text-slate-500 uppercase mb-12">Prepared By:</p><div class="border-b border-slate-800 w-3/4 mx-auto"></div><p class="text-sm font-bold mt-2 text-slate-900 uppercase">System Generated</p></div>
        <div class="text-center"><p class="text-xs font-bold text-slate-500 uppercase mb-12">Verified By:</p><div class="border-b border-slate-800 w-3/4 mx-auto"></div><p class="text-sm font-bold mt-2 text-slate-900 uppercase">Project Engineer</p></div>
        <div class="text-center"><p class="text-xs font-bold text-slate-500 uppercase mb-12">Approved By:</p><div class="border-b border-slate-800 w-3/4 mx-auto"></div><p class="text-sm font-bold mt-2 text-slate-900 uppercase">Project Manager</p></div>
      </div>
    </div>

    <div class="no-print mt-8 text-center"><p class="text-sm text-gray-500 mb-4">Press the button below if printing does not start automatically.</p><button onclick="window.print()" class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 font-semibold shadow-md transition-colors">Print Report</button></div>

  </div>

  <script>
    window.onload = function() { setTimeout(()=>{ window.print(); }, 500); };
  </script>
</body>
</html>
