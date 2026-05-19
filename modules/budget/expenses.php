<?php
  // 1. Connection & Context - using centralized config (MUST be before any HTML output)
  include __DIR__ . '/project_context.php';
  require_once __DIR__ . '/../../core/ApiHelper.php';

  $conn = getBudgetConnection();
  
  // Get selected project ID and phase from global context BEFORE HTML
  $selected_project_id = getProjectContext($conn);
  $selected_phase = getPhaseContext();
  
  // Fetch all projects for dropdown from Project Service
  $projectRes = ApiHelper::get('project/projects');
  $projects = $projectRes['data']['projects'] ?? [];
  $projectsById = [];
  foreach ($projects as $proj) {
    $projectsById[$proj['project_id']] = $proj;
  }

  // Define phases
  $phases = [
    'All Phases',
    'Phase 1: Mobilization',
    'Phase 2: Structural',
    'Phase 3: MEPFS',
    'Phase 4: Finishing'
  ];

  // Build breadcrumb navigation with dropdowns
  $current_page = basename($_SERVER['PHP_SELF']);
  $breadcrumbHTML = '<div class="flex items-center gap-2 text-sm">';
  
  // Project Dropdown
  $breadcrumbHTML .= '<div class="relative inline-block">';
  $breadcrumbHTML .= '<select id="projectSelector" onchange="window.location.href=\'' . $current_page . '?project_id=\' + this.value + \'&phase=' . urlencode($selected_phase) . '\'" class="appearance-none bg-white border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-2 focus:ring-[#e9922c] focus:border-[#e9922c] pl-3 pr-8 py-1.5 hover:bg-gray-50 transition-colors cursor-pointer font-medium">';
  
  foreach ($projects as $proj) {
    $selected = ($proj['project_id'] == $selected_project_id) ? 'selected' : '';
    $breadcrumbHTML .= '<option value="' . $proj['project_id'] . '" ' . $selected . '>' . htmlspecialchars($proj['project_name']) . '</option>';
  }
  
  $breadcrumbHTML .= '</select>';
  $breadcrumbHTML .= '<svg class="w-3 h-3 text-gray-500 absolute right-2 top-1/2 transform -translate-y-1/2 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>';
  $breadcrumbHTML .= '</div>';
  
  $breadcrumbHTML .= '</div>';

  // Set header variables
  $pageTitle = "Expense Tracker";
  $pageSection = "Budget & Cost Control";
  $pageSubTitle = $breadcrumbHTML;

  // Initialize project data variables
  $project_name = 'No Project Selected';
  $total_budget = 0;
  $actual_spending = 0;
  $remaining_budget = 0;
  $budget_utilization = 0;
  $alert_type = 'good';
  $alert_message = '';

  // Fetch selected project details with budget data from Budget Service
  if ($selected_project_id > 0) {
    $summaryRes = ApiHelper::get('budget/summary?project_id=' . $selected_project_id);
    $projectSummary = $summaryRes['data'] ?? [];

    if (isset($projectsById[$selected_project_id])) {
      $project_name = $projectsById[$selected_project_id]['project_name'] ?? $project_name;
    }

    if (!empty($projectSummary)) {
        $total_budget = floatval($projectSummary['total_budget'] ?? 0);
        $actual_spending = floatval($projectSummary['actual_spending'] ?? $projectSummary['total_spent'] ?? 0);
        $remaining_budget = $total_budget - $actual_spending;
        $budget_utilization = $total_budget > 0 ? ($actual_spending / $total_budget) * 100 : 0;

        // Determine alert type and message
        if ($budget_utilization >= 85) {
          $alert_type = 'high';
          $alert_message = "$project_name has utilized " . number_format($budget_utilization, 1) . "% of its budget. Consider reviewing remaining expenses.";
        } elseif ($budget_utilization >= 70) {
          $alert_type = 'warning';
          $alert_message = "$project_name: Budget tracking on schedule at " . number_format($budget_utilization, 1) . "% utilization.";
        } else {
          $alert_type = 'good';
          $alert_message = "$project_name is under budget at " . number_format($budget_utilization, 1) . "% utilization. Good progress.";
        }
    }

    // Fetch phase-based budget data from Budget Service
    $phaseDataRes = ApiHelper::get('budget/phases?project_id=' . $selected_project_id);
    $phases_data = $phaseDataRes['data']['phases'] ?? [];

    // Fetch recent expenses from Budget Service (synced from Procurement)
    $expenseRes = ApiHelper::get('budget/expenses?project_id=' . $selected_project_id . '&limit=10');
    $expenses = $expenseRes['data']['expenses'] ?? [];
    if (!empty($expenses)) {
      $suppliersMap = [];
      $supRes = ApiHelper::get('procurement/suppliers?per_page=1000');
      if ($supRes['status'] === 200) {
        foreach (($supRes['data']['suppliers'] ?? []) as $sup) {
          $suppliersMap[$sup['supplier_id']] = $sup['supplier_name'];
        }
      }
      foreach ($expenses as &$expense) {
        $sid = $expense['supplier_id'] ?? null;
        $expense['supplier_name'] = $suppliersMap[$sid] ?? 'N/A';
      }
    }
    $total_expenses = count($expenses);

    // Check for approved proposals
    $proposalsCheckRes = ApiHelper::get('budget/proposals?project_id=' . $selected_project_id . '&status=APPROVED&count_only=true');
    $has_approved_proposals = ($proposalsCheckRes['data']['count'] ?? 0) > 0;
  } else {
    $expenses = [];
    $total_expenses = 0;
    $phases_data = [];
    $has_approved_proposals = false;
  }
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Expense Tracker | ICMIS</title>
  
  <?php include __DIR__ . '/../../includes/head_assetsv2.php'; ?>
  
  <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
  <script src="https://unpkg.com/lucide@latest"></script>
  
  
    <style>
        * { font-family: 'Inter', sans-serif; }
        .employee-avatar { width: 36px; height: 36px; border-radius: 50%; background: linear-gradient(135deg, #e9922c, #f59e0b); display: flex; align-items: center; justify-content: center; color: white; font-weight: 600; font-size: 14px; }
        .tab-btn.active { border-color: #e9922c; color: #e9922c; }
        .tab-btn.inactive { border-color: transparent; color: #6b7280; }
        .tab-btn.inactive:hover { color: #374151; }
        .group-details { transition: max-height 0.3s ease-in-out; max-height: 0; overflow: hidden; }
        .group-details.open { max-height: 2000px; }
    </style>
</head>
<body class="bg-gray-50">
  <?php 
    include __DIR__ . '/../../includes/sidebar.php';
    include __DIR__ . '/../../includes/toast.php';
    include __DIR__ . '/../../includes/header.php';
  ?>

  <!-- Main Content Area -->
  <main class="ml-56 mt-16 p-6 transition-all duration-300 animate-fade-in">
    <div class="max-w-7xl mx-auto">
      <div class="flex items-center gap-1 mb-6 border-b border-gray-200">
        <button onclick="window.location.href='expenses.php'" id="tab-employees" class="tab-btn px-6 py-3 text-sm font-semibold border-b-2 border-[#e9922c] text-[#e9922c] transition-colors">
              Procurement Expense
        </button>
        <button onclick="window.location.href='payroll_expenses.php'" id="tab-groups" class="tab-btn px-6 py-3 text-sm font-semibold border-b-2 border-transparent text-gray-500 hover:text-gray-700 transition-colors">
              Payroll Expense
        </button>
        </div>
      </div>
      <!-- Page Header Section -->
      <div class="flex items-center justify-between mb-6">
        <div>
          <h1 class="text-2xl text-gray-900 font-bold">Expenses</h1>
          <p class="text-sm text-gray-500 mt-1">Expenses are automatically synced from completed Purchase Orders</p>
        </div>
        
        <!-- Info Badge: Expenses now come from Procurement -->
        <div class="flex items-center gap-2 bg-blue-50 text-blue-700 px-4 py-2.5 rounded-lg border border-blue-200">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
          </svg>
          <span class="text-sm font-bold">Synced from Procurement</span>
        </div>
      </div>

      <div id="procurementView">
      <?php if (empty($expenses)): ?>
      <!-- Empty State Card -->
      <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-12">
        <div class="max-w-md mx-auto text-center">
          <!-- Icon Section -->
          <div class="flex justify-center mb-6">
            <div class="bg-orange-50 rounded-full p-6">
              <svg class="w-16 h-16 text-[#e9922c]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 14l6-6m-5.5.5h.01m4.99 5h.01M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16l3.5-2 3.5 2 3.5-2 3.5 2zM10 8.5a.5.5 0 11-1 0 .5.5 0 011 0zm5 5a.5.5 0 11-1 0 .5.5 0 011 0z" />
              </svg>
            </div>
          </div>

          <!-- Text Content -->
          <h2 class="text-xl text-gray-900 font-bold mb-3">No Expenses Recorded Yet</h2>
          <p class="text-gray-500 mb-8 leading-relaxed">
            <?php if ($selected_project_id > 0): ?>
              No expenses have been recorded for <strong><?php echo htmlspecialchars($project_name); ?></strong> yet. 
              Start tracking expenses to monitor budget utilization and project spending.
            <?php else: ?>
              Please select a project from the dropdown above to view and track expenses.
            <?php endif; ?>
          </p>

          <!-- Helper Information Section -->
          <div class="mt-8 pt-8 border-t border-gray-200">
            <p class="text-sm text-gray-600 mb-3 text-left">Track expenses for:</p>
            
            <!-- Feature Cards Grid -->
            <div class="space-y-3">
              <!-- Feature 1 -->
              <div class="flex gap-3 bg-gray-50 rounded-lg p-3 text-left">
                <div class="shrink-0 mt-1.5">
                  <div class="w-1.5 h-1.5 bg-purple-600 rounded-full"></div>
                </div>
                <div>
                  <div class="text-sm text-gray-900 font-medium">Materials & Supplies</div>
                  <div class="text-xs text-gray-500 mt-0.5">Construction materials, equipment, and supplies</div>
                </div>
              </div>

              <!-- Feature 2 -->
              <div class="flex gap-3 bg-gray-50 rounded-lg p-3 text-left">
                <div class="shrink-0 mt-1.5">
                  <div class="w-1.5 h-1.5 bg-cyan-600 rounded-full"></div>
                </div>
                <div>
                  <div class="text-sm text-gray-900 font-medium">Labor & Services</div>
                  <div class="text-xs text-gray-500 mt-0.5">Workforce costs and professional services</div>
                </div>
              </div>

              <!-- Feature 3 -->
              <div class="flex gap-3 bg-gray-50 rounded-lg p-3 text-left">
                <div class="shrink-0 mt-1.5">
                  <div class="w-1.5 h-1.5 bg-green-600 rounded-full"></div>
                </div>
                <div>
                  <div class="text-sm text-gray-900 font-medium">Equipment & Machinery</div>
                  <div class="text-xs text-gray-500 mt-0.5">Heavy equipment rentals and machinery costs</div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
      <?php else: ?>
      <!-- Expenses Table -->
      <div class="bg-white border border-gray-200 rounded-xl shadow-sm overflow-hidden">
        <!-- Table Header Section -->
        <div class="p-6 border-b border-gray-200 flex items-center justify-between">
          <div>
            <h2 class="text-lg font-bold text-gray-900 mb-1">Recent Expenses</h2>
            <p class="text-sm text-gray-500">Latest transactions for <?php echo htmlspecialchars($project_name); ?></p>
          </div>
          <div class="flex gap-3"></div>
        </div>
        

        <!-- Table -->
        <div class="overflow-x-auto">
          <table class="w-full">
            <thead>
              <tr class="bg-gray-50 border-b border-gray-200">
                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Date</th>
                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Phase</th>
                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Category</th>
                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Item/Description</th>
                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Supplier Name</th>
                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Amount</th>
                <th class="px-6 py-3 text-center text-xs font-semibold text-gray-600 uppercase tracking-wider">Status</th>
                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Actions</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                <?php foreach ($expenses as $expense): ?>
                  <tr class="hover:bg-gray-50 transition-colors duration-150">
                    <td class="px-6 py-4 whitespace-nowrap">
                      <span class="text-sm text-gray-900"><?php echo date('M d, Y', strtotime($expense['expense_date'])); ?></span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                      <?php
                        $phase_badge_colors = [
                          'Phase 1: Mobilization' => 'bg-blue-100 text-blue-700 border-blue-300',
                          'Phase 2: Structural' => 'bg-purple-100 text-purple-700 border-purple-300',
                          'Phase 3: MEPFS' => 'bg-orange-100 text-orange-700 border-orange-300',
                          'Phase 4: Finishing' => 'bg-green-100 text-green-700 border-green-300'
                        ];
                        $phase_display = $expense['phase'] ?? 'Phase 1: Mobilization';
                        $phase_number = substr($phase_display, 6, 1);
                        $phase_badge_class = $phase_badge_colors[$phase_display] ?? 'bg-gray-100 text-gray-700 border-gray-300';
                      ?>
                      <span class="<?php echo $phase_badge_class; ?> px-2 py-1 rounded border text-xs font-semibold">
                        P<?php echo $phase_number; ?>
                      </span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                      <?php
                        $category = strtolower($expense['category']);
                        $badge_colors = [
                          'materials' => 'bg-purple-100 text-purple-700',
                          'labor' => 'bg-cyan-100 text-cyan-700',
                          'equipment' => 'bg-green-100 text-green-700'
                        ];
                        $badge_class = $badge_colors[$category] ?? 'bg-gray-100 text-gray-700';
                      ?>
                      <span class="<?php echo $badge_class; ?> px-3 py-1 rounded text-xs font-medium">
                        <?php echo htmlspecialchars(ucfirst($expense['category'])); ?>
                      </span>
                    </td>
                    <td class="px-6 py-4">
                      <span class="text-sm text-gray-900"><?php echo htmlspecialchars($expense['description']); ?></span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                      <span class="text-sm text-gray-600"><?php echo htmlspecialchars($expense['supplier_name'] ?? 'N/A'); ?></span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                      <span class="text-sm font-semibold text-gray-900">₱<?php echo number_format($expense['amount'], 2); ?></span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                      <?php
                        $status_colors = [
                          'APPROVED' => 'bg-green-100 text-green-700',
                          'PENDING' => 'bg-amber-100 text-amber-700'
                        ];
                        $status_class = $status_colors[$expense['status']] ?? 'bg-gray-100 text-gray-700';
                      ?>
                      <span class="<?php echo $status_class; ?> px-3 py-1 rounded-full text-xs font-medium">
                        <?php echo htmlspecialchars($expense['status']); ?>
                      </span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-center">
                      <div class="flex items-center justify-center gap-2">
                        <!-- View modal removed; keep Procurement link -->
                        <!-- Edit removed: Expenses now come from Procurement POs -->
                        <a href="../procurement/orders.php?po_id=<?php echo $expense['po_id'] ?? ''; ?>" class="text-gray-400 hover:text-green-800 transition-colors" title="View in Procurement">
                          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                          </svg>
                        </a>
                      </div>
                    </td>
                  </tr>
                <?php endforeach; ?>
            </tbody>
          </table>
        </div>

        <!-- Table Footer -->
        <div class="bg-gray-50 border-t border-gray-200 px-6 py-4 flex items-center justify-between">
          <p class="text-sm text-gray-600">
            Showing <?php echo min(1, $total_expenses); ?>-<?php echo $total_expenses; ?> of <?php echo $total_expenses; ?> expenses
          </p>
          <div class="flex gap-2">
            <button class="px-4 py-2 bg-white border border-gray-300 text-gray-700 hover:bg-gray-50 rounded-lg text-sm font-medium transition-colors duration-150">
              Previous
            </button>
            <button class="px-4 py-2 bg-[#e9922c] hover:bg-[#d17f1f] text-white rounded-lg text-sm font-medium transition-colors duration-150">
              Next
            </button>
          </div>
        </div>
      </div>
      <?php endif; ?>
      </div>

      <div id="payrollView" class="hidden">
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-12 text-center">
          <div class="max-w-md mx-auto">
            <div class="flex justify-center mb-6">
              <div class="bg-gray-100 rounded-full p-6">
                <i data-lucide="file-text" class="w-10 h-10 text-gray-500"></i>
              </div>
            </div>
            <h2 class="text-xl text-gray-900 font-bold mb-3">Payroll Expenses</h2>
            <p class="text-gray-500 mb-6">View and manage payroll-related disbursements and reports.</p>
            <a href="../workforce/payroll.php" class="inline-flex items-center gap-2 px-4 py-2 bg-[#e9922c] text-white rounded-md font-semibold">Open Payroll Expenses</a>
          </div>
        </div>
      </div>
    </div>
  </main>

  <!-- Toast included globally via header.php -->

  <!-- View Expense Modal removed -->

  <!-- Delete Expense Modal -->
  <div id="deleteExpenseModal" class="hidden fixed inset-0 bg-black/60 backdrop-blur-sm z-50 flex items-center justify-center p-4 animate-fade-in">
    <div class="bg-white rounded-2xl shadow-2xl max-w-md w-full transform transition-all animate-modal-slide-in">
      <!-- Modal Header with Red Accent -->
      <div class="bg-gradient-to-r from-red-600 to-red-700 p-6 rounded-t-2xl">
        <div class="flex items-center justify-between">
          <div class="flex items-center gap-4">
            <div class="bg-white rounded-full p-3 shadow-lg">
              <svg class="w-8 h-8 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
              </svg>
            </div>
            <div>
              <h3 class="text-2xl font-bold text-white">Delete Expense</h3>
              <p class="text-red-100 text-sm mt-1">Permanent action</p>
            </div>
          </div>
          <button onclick="closeDeleteExpenseModal()" class="text-white hover:bg-white hover:bg-opacity-20 p-2 rounded-lg transition-all">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </div>
      </div>

      <!-- Modal Body -->
      <div class="p-8">
        <div class="mb-6">
          <p class="text-gray-700 text-lg font-medium mb-2">
            Are you sure you want to delete this expense?
          </p>
          <p class="text-gray-500 text-sm">
            This action is permanent and cannot be undone. All associated data will be removed.
          </p>
        </div>
        
        <div class="bg-red-50 border-2 border-red-200 rounded-xl p-5 shadow-sm">
          <div class="flex items-start gap-4">
            <div class="bg-red-100 rounded-full p-2 shrink-0">
              <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
              </svg>
            </div>
            <div class="flex-1">
              <p class="text-sm font-bold text-red-900 mb-1.5 uppercase tracking-wide">Expense Description</p>
              <p id="deleteExpenseDescription" class="text-sm text-red-700 bg-white px-3 py-2 rounded-lg border border-red-200"></p>
            </div>
          </div>
        </div>
      </div>

      <!-- Modal Footer -->
      <div class="flex items-center justify-end gap-3 p-6 bg-gray-50 border-t border-gray-200 rounded-b-2xl">
        <button onclick="closeDeleteExpenseModal()" class="px-6 py-3 text-gray-700 bg-white border-2 border-gray-300 rounded-xl hover:bg-gray-100 hover:border-gray-400 transition-all font-semibold shadow-sm">
          Cancel
        </button>
        <button id="confirmDeleteExpenseBtn" onclick="confirmDeleteExpense()" class="px-6 py-3 bg-gradient-to-r from-red-600 to-red-700 hover:from-red-700 hover:to-red-800 text-white rounded-xl transition-all font-semibold flex items-center gap-2 shadow-lg hover:shadow-xl">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
          </svg>
          Delete Expense
        </button>
      </div>
    </div>
  </div>

  <style>
    @keyframes modal-slide-in {
      from {
        opacity: 0;
        transform: translateY(-20px) scale(0.95);
      }
      to {
        opacity: 1;
        transform: translateY(0) scale(1);
      }
    }

    .animate-modal-slide-in {
      animation: modal-slide-in 0.3s ease-out forwards;
    }

    #deleteExpenseModal {
      transition: opacity 0.2s ease-out;
    }
  </style>
        
  <script src="js/expenses.js"></script>
  <script>
    function switchExpensesTab(tab) {
      const p = document.getElementById('procurementView');
      const w = document.getElementById('payrollView');
      const bProc = document.getElementById('tabProcurement');
      const bPay = document.getElementById('tabPayroll');
      if (!p || !w || !bProc || !bPay) return;
      if (tab === 'payroll') {
        p.classList.add('hidden');
        w.classList.remove('hidden');
        bProc.classList.remove('bg-[#e9922c]','text-white');
        bProc.classList.add('border','border-gray-200','text-gray-700');
        bPay.classList.add('bg-[#e9922c]','text-white');
        bPay.classList.remove('border','border-gray-200');
      } else {
        p.classList.remove('hidden');
        w.classList.add('hidden');
        bPay.classList.remove('bg-[#e9922c]','text-white');
        bPay.classList.add('border','border-gray-200','text-gray-700');
        bProc.classList.add('bg-[#e9922c]','text-white');
        bProc.classList.remove('border','border-gray-200');
      }
    }
    // Initialize default tab
    document.addEventListener('DOMContentLoaded', function(){ if(document.getElementById('tabProcurement')) switchExpensesTab('procurement'); });
  </script>
</body>
</html>