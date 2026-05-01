<?php
// download_payslip_pdf.php
// Renders a printable payslip for a single employee.
// Refactored to use ApiHelper for microservices communication.

session_start();

require_once __DIR__ . '/../../core/ApiHelper.php';

// Require login
if (!isset($_SESSION['user_id'])) {
    header('Location: ' . (defined('BASE_URL') ? BASE_URL : '/index.php'));
    exit;
}

// Authorization
$userRole = $_SESSION['user_role'] ?? 'Staff';
$allowedRoles = ['admin', 'payroll', 'hr', 'project manager', 'financial manager'];
if (!in_array(strtolower($userRole), $allowedRoles, true)) {
    http_response_code(403);
    echo 'You are not authorized to view this payslip.';
    exit;
}

function esc($v) { return htmlspecialchars((string)$v); }

$employee = null;
$meta = ['project' => 'Project', 'period' => '-', 'generated' => date('F j, Y g:i A')];

// Accept POSTed payload (from JS) or GET params (period_id & employee_id)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['payload'])) {
    $data = json_decode($_POST['payload'], true);
    if ($data && isset($data['data']) && is_array($data['data']) && count($data['data'])>0) {
        $row = $data['data'][0];
        $employee = $row;
        $meta['project'] = $data['projectName'] ?? $meta['project'];
        $meta['period'] = $data['period'] ?? $meta['period'];
        $meta['generated'] = $data['dateGen'] ?? $meta['generated'];
    } else {
        echo 'Invalid payload'; exit;
    }

} elseif (isset($_GET['employee_id']) && isset($_GET['period_id'])) {
    $employee_id = intval($_GET['employee_id']);
    $period_id = intval($_GET['period_id']);
    $project_id = isset($_GET['project_id']) ? intval($_GET['project_id']) : 0;

    try {
        $apiRes = ApiHelper::get("workforce/payroll?action=get_payslip&period_id=$period_id&employee_id=$employee_id&project_id=$project_id");
        
        if ($apiRes['status'] === 200 && $apiRes['data']['success']) {
            $resData = $apiRes['data']['data'];
            $r = $resData['payroll'];
            $pres = $resData['period'];

            $employee = [
                'code' => $r['code'] ?? '',
                'fullname' => $r['fullname'] ?? '',
                'basic_pay' => $r['basic_pay'] ?? ($r['monthly_salary'] ?? 0),
                'ot_pay' => $r['ot_pay'] ?? 0,
                'gross_pay' => $r['gross_pay'] ?? 0,
                'sss_deduction' => $r['sss_deduction'] ?? 0,
                'philhealth_deduction' => $r['philhealth_deduction'] ?? 0,
                'pagibig_deduction' => $r['pagibig_deduction'] ?? 0,
                'deductions' => $r['deductions'] ?? (($r['sss_deduction']??0)+($r['philhealth_deduction']??0)+($r['pagibig_deduction']??0)),
                'net_pay' => $r['net_pay'] ?? 0,
                'days_worked' => $r['days_worked'] ?? ($r['hours_worked'] ? round($r['hours_worked']/8,2) : '-'),
                'ot_hours' => $r['ot_hours'] ?? 0
            ];

            if ($pres) $meta['period'] = date('M j', strtotime($pres['start_date'])) . ' - ' . date('M j, Y', strtotime($pres['end_date']));
        } else {
            echo 'Payslip not found: ' . ($apiRes['data']['message'] ?? 'Unknown error');
            exit;
        }
    } catch (Exception $e) {
        echo 'API Error: ' . $e->getMessage();
        exit;
    }

} else {
    echo 'No payslip data provided'; exit;
}

// Prepared By
$prepared_by = $_SESSION['user_name'] ?? 'System Generated';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Payslip - <?php echo esc($employee['fullname'] ?? 'Employee'); ?></title>
  <?php include __DIR__ . '/../../includes/head_assetsv2.php'; ?>
  <style>
    body { font-family: 'Inter', sans-serif; background-color: #f8fafc; color: #1e293b; padding: 40px; }
    .report-container { max-width: 1000px; margin: 0 auto; background: white; padding: 40px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1); border-radius: 8px; }
    @media print { @page { margin: 0.5in; size: auto; } body { background-color: white !important; color: black !important; padding: 0 !important; -webkit-print-color-adjust: exact; } .report-container { box-shadow: none !important; padding: 0 !important; width: 100% !important; max-width: none !important; } .no-print { display: none !important; } table { width: 100% !important; border-collapse: collapse !important; font-size: 10pt !important; } thead tr { background-color: #f3f4f6 !important; } thead th { border: 1px solid #9ca3af !important; padding: 8px !important; color: black !important; font-weight: bold !important; text-transform: uppercase !important; } tbody td { border: 1px solid #e5e7eb !important; padding: 8px !important; color: black !important; } .print-footer { margin-top: 50px !important; page-break-inside: avoid; } }
    .print-logo { height: 80px; width: auto; margin: 0 auto 10px auto; display: block; }
    .mono{font-family:ui-monospace, SFMono-Regular, Menlo, Monaco, 'Roboto Mono', monospace}
  </style>
</head>
<body>

  <div class="report-container">
    <div class="text-center border-b-2 border-slate-800 pb-6 mb-8">
      <img src="../../assets/images/nobg_logo.png" alt="ICMIS Logo" class="print-logo">
      <h1 class="text-2xl font-black uppercase tracking-wide text-slate-900 mt-2">Payslip</h1>
      <p class="text-sm font-medium text-slate-500 uppercase tracking-widest">Integrated Construction Management Information System</p>
      <p class="text-xs text-slate-400 mt-1">Generated on: <?php echo esc($meta['generated']); ?></p>
    </div>

    <div class="grid grid-cols-2 gap-8 mb-8 text-sm">
      <div>
        <table class="w-full">
          <tr><td class="font-bold text-slate-500 py-1 w-32">Project:</td><td class="font-bold text-slate-900 py-1"><?php echo esc($meta['project']); ?></td></tr>
          <tr><td class="font-bold text-slate-500 py-1">Period:</td><td class="text-slate-900 py-1"><?php echo esc($meta['period']); ?></td></tr>
        </table>
      </div>
      <div>
        <table class="w-full">
          <tr><td class="font-bold text-slate-500 py-1 w-32">Employee:</td><td class="text-slate-900 py-1"><?php echo esc($employee['fullname'] ?? '-'); ?></td></tr>
          <tr><td class="font-bold text-slate-500 py-1">Employee Code:</td><td class="text-slate-900 py-1 mono"><?php echo esc($employee['code'] ?? '-'); ?></td></tr>
        </table>
      </div>
    </div>

    <div class="mb-8 bg-slate-50 rounded-lg border border-slate-200 p-6">
      <h3 class="text-xs font-bold text-slate-500 uppercase mb-4">Summary</h3>
      <div class="grid grid-cols-3 gap-4 text-center">
        <div class="p-2 border-r border-slate-200"><p class="text-xs text-slate-500 uppercase font-bold">Gross Pay</p><p class="text-xl font-black text-slate-800 mt-1 mono">₱<?php echo number_format((float)($employee['gross_pay'] ?? 0), 2); ?></p></div>
        <div class="p-2 border-r border-slate-200"><p class="text-xs text-slate-500 uppercase font-bold">Deductions</p><p class="text-xl font-black text-red-700 mt-1 mono">₱<?php echo number_format((float)($employee['deductions'] ?? 0), 2); ?></p></div>
        <div class="p-2"><p class="text-xs text-slate-500 uppercase font-bold">Net Pay</p><p class="text-xl font-black text-slate-800 mt-1 mono">₱<?php echo number_format((float)($employee['net_pay'] ?? 0), 2); ?></p></div>
      </div>
    </div>

    <div class="mb-8">
      <h3 class="text-sm font-bold text-slate-900 uppercase border-b border-slate-200 pb-2 mb-4">Payslip Details</h3>
      <table class="w-full text-left border-collapse">
        <thead class="bg-slate-100"><tr>
          <th class="px-4 py-2 text-xs font-bold text-slate-600 uppercase border border-slate-300">Description</th>
          <th class="px-4 py-2 text-xs font-bold text-slate-600 uppercase border border-slate-300 text-right">Amount</th>
        </tr></thead>
        <tbody>
          <tr><td class="px-4 py-2 text-sm border border-slate-200">Basic Pay</td><td class="px-4 py-2 text-sm border border-slate-200 text-right mono">₱<?php echo number_format((float)($employee['basic_pay'] ?? 0),2); ?></td></tr>
          <tr><td class="px-4 py-2 text-sm border border-slate-200">Overtime</td><td class="px-4 py-2 text-sm border border-slate-200 text-right mono">₱<?php echo number_format((float)($employee['ot_pay'] ?? 0),2); ?></td></tr>
          <tr><td class="px-4 py-2 text-sm border border-slate-200">Total Gross</td><td class="px-4 py-2 text-sm border border-slate-200 text-right mono font-bold">₱<?php echo number_format((float)($employee['gross_pay'] ?? 0),2); ?></td></tr>
          <tr><td class="px-4 py-2 text-sm border border-slate-200">SSS</td><td class="px-4 py-2 text-sm border border-slate-200 text-right mono">₱<?php echo number_format((float)($employee['sss_deduction'] ?? 0),2); ?></td></tr>
          <tr><td class="px-4 py-2 text-sm border border-slate-200">PhilHealth</td><td class="px-4 py-2 text-sm border border-slate-200 text-right mono">₱<?php echo number_format((float)($employee['philhealth_deduction'] ?? 0),2); ?></td></tr>
          <tr><td class="px-4 py-2 text-sm border border-slate-200">Pag-IBIG</td><td class="px-4 py-2 text-sm border border-slate-200 text-right mono">₱<?php echo number_format((float)($employee['pagibig_deduction'] ?? 0),2); ?></td></tr>
          <tr class="bg-slate-50"><td class="px-4 py-2 text-sm border border-slate-200 font-bold">Total Deductions</td><td class="px-4 py-2 text-sm border border-slate-200 text-right mono font-bold">₱<?php echo number_format((float)($employee['deductions'] ?? 0),2); ?></td></tr>
          <tr><td class="px-4 py-2 text-sm border border-slate-200 font-bold">Net Pay</td><td class="px-4 py-2 text-sm border border-slate-200 text-right mono font-bold text-[#e9922c]">₱<?php echo number_format((float)($employee['net_pay'] ?? 0),2); ?></td></tr>
        </tbody>
      </table>
    </div>

    <div class="print-footer mt-12 pt-8">
      <div class="grid grid-cols-3 gap-8">
        <div class="text-center"><p class="text-xs font-bold text-slate-500 uppercase mb-12">Prepared By:</p><div class="border-b border-slate-800 w-3/4 mx-auto"></div><p class="text-sm font-bold mt-2 text-slate-900 uppercase"><?php echo htmlspecialchars($prepared_by); ?></p></div>
        <div class="text-center"><p class="text-xs font-bold text-slate-500 uppercase mb-12">Verified By:</p><div class="border-b border-slate-800 w-3/4 mx-auto"></div><p class="text-sm font-bold mt-2 text-slate-900 uppercase">Project Engineer</p></div>
        <div class="text-center"><p class="text-xs font-bold text-slate-500 uppercase mb-12">Approved By:</p><div class="border-b border-slate-800 w-3/4 mx-auto"></div><p class="text-sm font-bold mt-2 text-slate-900 uppercase">Project Manager</p></div>
      </div>
    </div>

    <div class="no-print mt-8 text-center"><p class="text-sm text-gray-500 mb-4">Press the button below if printing does not start automatically.</p><button onclick="window.print()" class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 font-semibold shadow-md transition-colors">Print Payslip</button></div>

  </div>

  <script>
    window.onload = function() { setTimeout(()=>{ window.print(); }, 500); };
  </script>
</body>
</html>
