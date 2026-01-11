<?php
  // 1. Connection & Context
  include __DIR__ . '/project_context.php';
  // Fallback connection if getBudgetConnection not defined
  $conn = function_exists('getBudgetConnection') ? getBudgetConnection() : new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

  // Get selected project from context
  $selected_project_id = function_exists('getProjectContext') ? getProjectContext($conn) : (isset($_SESSION['selected_project_id']) ? $_SESSION['selected_project_id'] : 0);
  
  // Basic Projects Dropdown Logic (Preserved from your design)
  $projects = [];
  $res = $conn->query("SELECT project_id, project_name FROM icmis_projects ORDER BY project_id DESC");
  if ($res) while($r = $res->fetch_assoc()) $projects[] = $r;

  // FETCH PAYROLL EXPENSES (Closed Payrolls Only)
  $expenses = [];
  $total_payroll_expense = 0;

  $pageTitle = "Expense Tracker";
  $pageSection = "Budget & Cost Control";

  if ($selected_project_id > 0) {
      // Query: Join Payroll -> Periods -> Employees -> Job Titles
      // Filter: Period Status = 'Closed' AND Project Context
      $sql = "SELECT 
                  wp.payroll_id,
                  wp.net_pay as amount, -- Expense is the Net Pay disbursed
                  wp.gross_pay,
                  (wp.gross_pay - wp.net_pay) as deductions,
                  wpp.pay_date as expense_date,
                  wpp.start_date,
                  wpp.end_date,
                  CONCAT(e.first_name, ' ', e.last_name) as employee_name,
                  jt.title_name as role,
                  pp.phase_name
              FROM workforce_payroll wp
              JOIN workforce_payroll_periods wpp ON wp.period_id = wpp.period_id
              JOIN workforce_employees e ON wp.employee_id = e.employee_id
              -- Join Assignments to link Employee to Project/Phase for this context
              JOIN workforce_assignments wa ON e.employee_id = wa.employee_id 
              LEFT JOIN workforce_job_titles jt ON e.job_title_id = jt.job_title_id
              LEFT JOIN icmis_project_phases pp ON wa.phase_id = pp.phase_id
              WHERE wpp.status = 'Closed' 
              AND wa.project_id = ?
              AND wa.status = 'Active' -- Ensure we get their active assignment for the project
              ORDER BY wpp.pay_date DESC, e.last_name ASC";
      
      $stmt = $conn->prepare($sql);
      $stmt->bind_param("i", $selected_project_id);
      $stmt->execute();
      $result = $stmt->get_result();
      
      while ($row = $result->fetch_assoc()) {
          $expenses[] = $row;
          $total_payroll_expense += $row['amount'];
      }
      $stmt->close();
  }
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Payroll Expenses | ICMIS</title>
  <?php include __DIR__ . '/../../includes/head_assetsv2.php'; ?>
  <script src="https://unpkg.com/lucide@latest"></script>
</head>
<body class="bg-gray-50">
  <?php 
    include __DIR__ . '/../../includes/sidebar.php';
    include __DIR__ . '/../../includes/toast.php';
    include __DIR__ . '/../../includes/header.php';
  ?>

  <main class="ml-56 mt-16 p-6 transition-all duration-300 animate-fade-in">
    <div class="max-w-7xl mx-auto">
      <div class="flex items-center gap-1 mb-6 border-b border-gray-200">
        <button onclick="window.location.href='expenses.php'" id="tab-employees" class="tab-btn px-6 py-3 text-sm font-semibold border-b-2 border-transparent text-gray-500 hover:text-gray-700 transition-colors">
              Procurement Expense
        </button>
        <button onclick="window.location.href='payroll_expenses.php'" id="tab-groups" class="tab-btn px-6 py-3 text-sm font-semibold border-b-2 border-[#e9922c] text-[#e9922c] transition-colors">
              Payroll Expense
        </button>
        </div>
      </div>
      <div class="flex items-center justify-between mb-6">
        <div>
          <h1 class="text-2xl text-gray-900 font-bold">Payroll Expenses</h1>
          <p class="text-sm text-gray-500 mt-1">Tracking disbursed salaries for closed payroll periods.</p>
        </div>

        <!-- Info Badge: Expenses now come from Procurement -->
        <div class="flex items-center gap-2 bg-blue-50 text-blue-700 px-4 py-2.5 rounded-lg border border-blue-200">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
          </svg>
          <span class="text-sm font-bold">Synced from Payroll</span>
        </div>
      </div>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
          <div class="bg-white p-6 rounded-xl border border-gray-200 shadow-sm flex items-center justify-between">
              <div>
                  <p class="text-sm font-medium text-gray-500 uppercase">Total Disbursed</p>
                  <p class="text-2xl font-black text-gray-900 mt-1">₱<?= number_format($total_payroll_expense, 2) ?></p>
              </div>
              <div class="p-3 bg-orange-50 rounded-full">
                  <i data-lucide="wallet" class="w-6 h-6 text-[#e9922c]"></i>
              </div>
          </div>
          <div class="bg-white p-6 rounded-xl border border-gray-200 shadow-sm flex items-center justify-between">
              <div>
                  <p class="text-sm font-medium text-gray-500 uppercase">Closed Records</p>
                  <p class="text-2xl font-black text-gray-900 mt-1"><?= count($expenses) ?></p>
              </div>
              <div class="p-3 bg-blue-50 rounded-full">
                  <i data-lucide="users" class="w-6 h-6 text-blue-600"></i>
              </div>
          </div>
      </div>

      <?php if (empty($expenses)): ?>
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-12 text-center h-94 flex flex-col items-center justify-center">
            <div class="mx-auto w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mb-4">
                <i data-lucide="folder-open" class="w-8 h-8 text-gray-400"></i>
            </div>
            <h3 class="text-lg font-bold text-gray-900">No Closed Payrolls Found</h3>
            <p class="text-gray-500 mt-1">Select a project or ensure payroll periods are finalized in the Workforce module.</p>
        </div>
      <?php else: ?>
        <div class="bg-white border border-gray-200 rounded-xl shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead class="bg-gray-50 border-b border-gray-200">
                        <tr>
                            <th class="px-6 py-4 text-xs font-bold text-gray-500 uppercase">Pay Date</th>
                            <th class="px-6 py-4 text-xs font-bold text-gray-500 uppercase">Employee</th>
                            <th class="px-6 py-4 text-xs font-bold text-gray-500 uppercase">Period Covered</th>
                            <th class="px-6 py-4 text-xs font-bold text-gray-500 uppercase text-right">Gross</th>
                            <th class="px-6 py-4 text-xs font-bold text-gray-500 uppercase text-right">(-) Deductions</th>
                            <th class="px-6 py-4 text-xs font-bold text-[#e9922c] uppercase text-right">Net Amount</th>
                            <th class="px-6 py-4 text-center w-20"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <?php foreach($expenses as $exp): 
                            $period_label = date('M j', strtotime($exp['start_date'])) . ' - ' . date('j, Y', strtotime($exp['end_date']));
                        ?>
                        <tr class="hover:bg-orange-50/30 transition-colors group">
                            <td class="px-6 py-4 text-sm text-gray-900 font-medium">
                                <?= date('M d, Y', strtotime($exp['expense_date'])) ?>
                            </td>
                            <td class="px-6 py-4">
                                <p class="text-sm font-bold text-gray-900"><?= htmlspecialchars($exp['employee_name']) ?></p>
                                <p class="text-xs text-gray-500"><?= htmlspecialchars($exp['role']) ?></p>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-600">
                                <span class="bg-gray-100 text-gray-700 px-2 py-1 rounded text-xs border border-gray-200">
                                    <?= $period_label ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 text-sm text-right font-mono text-gray-600">
                                <?= number_format($exp['gross_pay'], 2) ?>
                            </td>
                            <td class="px-6 py-4 text-sm text-right font-mono text-red-500">
                                (<?= number_format($exp['deductions'], 2) ?>)
                            </td>
                            <td class="px-6 py-4 text-sm text-right font-mono font-bold text-[#e9922c]">
                                ₱<?= number_format($exp['amount'], 2) ?>
                            </td>
                            <td class="px-6 py-4 text-center">
                                <button onclick="PayrollExpenses.viewDetails(<?= $exp['payroll_id'] ?>)" 
                                        class="p-2 text-gray-400 hover:text-[#e9922c] hover:bg-orange-50 rounded-lg transition-all"
                                        title="View Breakdown">
                                    <i data-lucide="eye" class="w-4 h-4"></i>
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
      <?php endif; ?>

    </div>
  </main>

  <div id="payrollDetailModal" class="hidden fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 bg-gray-900/50 backdrop-blur-sm transition-opacity" aria-hidden="true" onclick="PayrollExpenses.closeModal()"></div>
        <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
        <div class="relative inline-block align-bottom bg-white rounded-xl text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg w-full">
            <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                <div class="flex justify-between items-start">
                    <div class="flex items-center gap-3">
                        <div class="mx-auto flex-shrink-0 flex items-center justify-center h-10 w-10 rounded-full bg-orange-100 sm:mx-0">
                            <i data-lucide="receipt" class="h-5 w-5 text-[#e9922c]"></i>
                        </div>
                        <div>
                            <h3 class="text-lg leading-6 font-bold text-gray-900" id="modal-title">Payroll Details</h3>
                            <p class="text-sm text-gray-500" id="modal-subtitle">Loading...</p>
                        </div>
                    </div>
                    <button onclick="PayrollExpenses.closeModal()" class="text-gray-400 hover:text-gray-500">
                        <i data-lucide="x" class="w-5 h-5"></i>
                    </button>
                </div>
                
                <div class="mt-6 space-y-4" id="modal-content">
                    <div class="animate-pulse flex space-x-4">
                        <div class="flex-1 space-y-4 py-1">
                            <div class="h-4 bg-gray-200 rounded w-3/4"></div>
                            <div class="space-y-2">
                                <div class="h-4 bg-gray-200 rounded"></div>
                                <div class="h-4 bg-gray-200 rounded w-5/6"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse border-t border-gray-100">
                <button type="button" onclick="PayrollExpenses.closeModal()" class="w-full inline-flex justify-center rounded-lg border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                    Close
                </button>
            </div>
        </div>
    </div>
  </div>

  <script src="js/payroll_expenses.js"></script>
  <script>
    document.addEventListener('DOMContentLoaded', () => {
        if(typeof lucide !== 'undefined') lucide.createIcons();
    });
  </script>
</body>
</html>