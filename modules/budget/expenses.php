<?php
  require_once __DIR__ . '/../../config/config.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <!-- Global project styles -->
  <link rel="stylesheet" href="/css/output.css">
  <link rel="stylesheet" href="/css/input.css">
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Expense Tracker - ICMIS</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
  <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
  <script src="https://unpkg.com/lucide@latest"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.31/jspdf.plugin.autotable.min.js"></script>
  <link rel="apple-touch-icon" sizes="180x180" href="../../assets/images/favicon/apple-touch-icon.png">
  <link rel="icon" type="image/png" sizes="32x32" href="../../assets/images/favicon/favicon-32x32.png">
  <link rel="icon" type="image/png" sizes="16x16" href="../../assets/images/favicon/favicon-16x16.png">
  <link rel="manifest" href="../../assets/images/favicon/site.webmanifest">
  <?php include '../../includes/head_assetsv2.php'; ?>
  <style>
    * { font-family: 'Inter', sans-serif; }
  </style>
</head>
<body class="bg-gray-50">
  <?php 
    include __DIR__ . '/connection.php';
    include __DIR__ . '/project_context.php';
    
    // Get selected project ID and phase from global context BEFORE sidebar
    $selected_project_id = getProjectContext($conn);
    $selected_phase = getPhaseContext();
    
  ?>
  <?php include __DIR__ . '/../../includes/sidebar.php'; ?>

  <?php

    // Fetch all projects for dropdown
    $sql_projects = "SELECT project_id, project_code, name FROM projects ORDER BY created_at DESC";
    $result_projects = $conn->query($sql_projects);
    $projects = [];
    if ($result_projects && $result_projects->num_rows > 0) {
      while ($row = $result_projects->fetch_assoc()) {
        $projects[] = $row;
        // Set first project as default if none selected
        if ($selected_project_id == 0) {
          $selected_project_id = $row['project_id'];
        }
      }
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
      $breadcrumbHTML .= '<option value="' . $proj['project_id'] . '" ' . $selected . '>' . htmlspecialchars($proj['name']) . '</option>';
    }
    
    $breadcrumbHTML .= '</select>';
    $breadcrumbHTML .= '<svg class="w-3 h-3 text-gray-500 absolute right-2 top-1/2 transform -translate-y-1/2 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>';
    $breadcrumbHTML .= '</div>';
    
    $breadcrumbHTML .= '</div>';

    // Set header variables
    $pageTitle = "Expense Tracker";
    $pageSection = "Budget & Cost Control";
    $pageSubTitle = $breadcrumbHTML;
    
    include __DIR__ . '/../../includes/header.php';

    // Initialize project data variables
    $project_name = 'No Project Selected';
    $total_budget = 0;
    $actual_spending = 0;
    $remaining_budget = 0;
    $budget_utilization = 0;
    $alert_type = 'good';
    $alert_message = '';

    // Fetch selected project details with budget data
    if ($selected_project_id > 0) {
      // Get project basic info with total approved budget proposals
      $sql_project = "SELECT p.project_id, p.project_code, p.name,
                      (SELECT COALESCE(SUM(bp.total_amount), 0) 
                       FROM budget_proposals bp 
                       WHERE bp.project_id = p.project_id AND bp.status = 'APPROVED') as total_budget,
                      (SELECT COALESCE(SUM(e.amount), 0) 
                       FROM budget_expenses e 
                       WHERE e.project_id = p.project_id AND e.status = 'APPROVED') as actual_spending
                      FROM projects p
                      WHERE p.project_id = ?";
      $stmt = $conn->prepare($sql_project);
      $stmt->bind_param("i", $selected_project_id);
      $stmt->execute();
      $result = $stmt->get_result();
      
      if ($result && $result->num_rows > 0) {
        $project = $result->fetch_assoc();
        $project_name = $project['name'];
        $total_budget = floatval($project['total_budget']);
        $actual_spending = floatval($project['actual_spending']);
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
      $stmt->close();

      // Fetch phase-based budget data
      $phases_data = [];
      $sql_phases = "SELECT 
                      e.phase,
                      (SELECT COALESCE(SUM(bp.total_amount), 0) 
                       FROM budget_proposals bp 
                       WHERE bp.project_id = ? 
                       AND bp.phase = e.phase 
                       AND bp.status = 'APPROVED') as allocated,
                      COALESCE(SUM(CASE WHEN e.status = 'APPROVED' THEN e.amount ELSE 0 END), 0) as spent,
                      COUNT(e.expense_id) as expense_count
                     FROM budget_expenses e
                     WHERE e.project_id = ?
                     GROUP BY e.phase
                     ORDER BY e.phase";
      $stmt_phases = $conn->prepare($sql_phases);
      $stmt_phases->bind_param("ii", $selected_project_id, $selected_project_id);
      $stmt_phases->execute();
      $result_phases = $stmt_phases->get_result();
      
      $phase_definitions = [
        'Phase 1: Mobilization' => ['color' => 'blue', 'icon' => 'truck', 'date_range' => 'Jan 15 - Mar 30'],
        'Phase 2: Structural' => ['color' => 'purple', 'icon' => 'building-2', 'date_range' => 'Apr 1 - Jun 30'],
        'Phase 3: MEPFS' => ['color' => 'orange', 'icon' => 'zap', 'date_range' => 'Jul 1 - Sep 30'],
        'Phase 4: Finishing' => ['color' => 'green', 'icon' => 'check-circle-2', 'date_range' => 'Oct 1 - Dec 31']
      ];

      while ($row = $result_phases->fetch_assoc()) {
        $phase_name = $row['phase'];
        $allocated = floatval($row['allocated']);
        $spent = floatval($row['spent']);
        $remaining = $allocated - $spent;
        $utilization = $allocated > 0 ? ($spent / $allocated) * 100 : 0;
        
        // Determine status
        $status = 'Upcoming';
        if ($utilization > 100) {
          $status = 'Over Budget';
        } elseif ($utilization > 0) {
          $status = 'Active';
        } elseif ($allocated > 0 && $spent >= $allocated * 0.99) {
          $status = 'Completed';
        }
        
        $phases_data[$phase_name] = [
          'phase' => $phase_name,
          'color' => $phase_definitions[$phase_name]['color'],
          'icon' => $phase_definitions[$phase_name]['icon'],
          'date_range' => $phase_definitions[$phase_name]['date_range'],
          'allocated' => $allocated,
          'spent' => $spent,
          'remaining' => $remaining,
          'utilization' => $utilization,
          'expense_count' => intval($row['expense_count']),
          'status' => $status
        ];
      }
      $stmt_phases->close();

      // Fill in missing phases with zero data
      foreach ($phase_definitions as $phase_name => $phase_info) {
        if (!isset($phases_data[$phase_name])) {
          $phases_data[$phase_name] = [
            'phase' => $phase_name,
            'color' => $phase_info['color'],
            'icon' => $phase_info['icon'],
            'date_range' => $phase_info['date_range'],
            'allocated' => 0,
            'spent' => 0,
            'remaining' => 0,
            'utilization' => 0,
            'expense_count' => 0,
            'status' => 'Upcoming'
          ];
        }
      }

      // Calculate active phases count
      $active_phases_count = 0;
      foreach ($phases_data as $phase) {
        if ($phase['status'] === 'Active' || $phase['status'] === 'Over Budget') {
          $active_phases_count++;
        }
      }
    }

    // Fetch monthly chart data for the project (Sept-Dec)
    $chart_months = ['Sept', 'Oct', 'Nov', 'Dec'];
    $projected_data = [0, 0, 0, 0];
    $actual_data = [0, 0, 0, 0];

    if ($selected_project_id > 0) {
      // Get monthly expenses for actual spending (Sept=9, Oct=10, Nov=11, Dec=12)
      $sql_monthly = "SELECT MONTH(expense_date) as month, YEAR(expense_date) as year, SUM(amount) as total
                      FROM budget_expenses
                      WHERE project_id = ? AND status = 'APPROVED'
                      AND MONTH(expense_date) BETWEEN 9 AND 12
                      GROUP BY YEAR(expense_date), MONTH(expense_date)
                      ORDER BY YEAR(expense_date), MONTH(expense_date)";
      $stmt_monthly = $conn->prepare($sql_monthly);
      $stmt_monthly->bind_param("i", $selected_project_id);
      $stmt_monthly->execute();
      $result_monthly = $stmt_monthly->get_result();
      
      // Initialize array to track monthly totals across years
      $monthly_totals = [0, 0, 0, 0]; // Sept, Oct, Nov, Dec
      
      while ($row = $result_monthly->fetch_assoc()) {
        $month_num = intval($row['month']);
        // Map Sept(9)->0, Oct(10)->1, Nov(11)->2, Dec(12)->3
        $month_index = $month_num - 9;
        if ($month_index >= 0 && $month_index < 4) {
          $monthly_totals[$month_index] += floatval($row['total']);
        }
      }
      
      $actual_data = $monthly_totals;
      $stmt_monthly->close();

      // Calculate projected spending (evenly distributed across 4 months)
      $monthly_projected = $total_budget / 12;
      for ($i = 0; $i < 4; $i++) {
        $projected_data[$i] = $monthly_projected;
      }
    }

    // Fetch recent expenses for the table
    $expenses = [];
    if ($selected_project_id > 0) {
      $sql_expenses = "SELECT e.*, s.name as supplier_name
                       FROM budget_expenses e
                       LEFT JOIN budget_suppliers s ON e.supplier_id = s.supplier_id
                       WHERE e.project_id = ?
                       ORDER BY e.expense_date DESC
                       LIMIT 10";
      $stmt_expenses = $conn->prepare($sql_expenses);
      $stmt_expenses->bind_param("i", $selected_project_id);
      $stmt_expenses->execute();
      $result_expenses = $stmt_expenses->get_result();
      
      if ($result_expenses && $result_expenses->num_rows > 0) {
        while ($row = $result_expenses->fetch_assoc()) {
          $expenses[] = $row;
        }
      }
      $stmt_expenses->close();
    }

    $total_expenses = count($expenses);

    // Check if there are approved budget proposals for the selected project
    $has_approved_proposals = false;
    if ($selected_project_id > 0) {
      $sql_check_proposals = "SELECT COUNT(*) as proposal_count 
                              FROM budget_proposals 
                              WHERE project_id = ? AND status = 'APPROVED'";
      $stmt_check = $conn->prepare($sql_check_proposals);
      $stmt_check->bind_param("i", $selected_project_id);
      $stmt_check->execute();
      $result_check = $stmt_check->get_result();
      if ($result_check && $row_check = $result_check->fetch_assoc()) {
        $has_approved_proposals = intval($row_check['proposal_count']) > 0;
      }
      $stmt_check->close();
    }
  ?>

  <!-- Main Content Area -->
  <main class="ml-56 mt-20 p-6">
    <div class="max-w-7xl mx-auto">
      <!-- Page Header Section -->
      <div class="flex items-center justify-between mb-6">
        <div>
          <h1 class="text-2xl text-gray-900 font-bold">Expenses</h1>
          <p class="text-sm text-gray-500 mt-1">Track and manage project expenses</p>
        </div>
        <a href="add_expense.php?project_id=<?php echo $selected_project_id; ?>&phase=<?php echo urlencode($selected_phase); ?>" class="flex items-center gap-2 bg-[#e9922c] text-white px-6 py-2.5 rounded-lg hover:bg-[#d17f1f] transition-colors duration-200 shadow-sm">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
          </svg>
          <span class="font-medium">New Expense</span>
        </a>
      </div>

      <!-- Expenses Table -->
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
          <div class="flex gap-3">
            <button id="export-pdf-btn" class="flex items-center gap-2 px-4 py-2 bg-[#e9922c] hover:bg-[#d17f1f] text-white rounded-lg transition-colors duration-150">
              <i data-lucide="file-text" class="w-4 h-4"></i>
              <span class="text-sm font-medium">Export as PDF</span>
            </button>
            <button id="filter-toggle-btn" class="flex items-center gap-2 px-4 py-2 border border-gray-300 bg-white hover:bg-gray-50 text-gray-700 rounded-lg transition-colors duration-150">
              <i data-lucide="filter" class="w-4 h-4"></i>
              <span class="text-sm font-medium">Filter</span>
            </button>
          </div>
        </div>

        <!-- Filter Panel -->
        <div id="filter-panel" class="hidden border-b border-gray-200 bg-gray-50 p-6">
          <div class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-6 gap-4">
            <!-- Date Filter -->
            <div>
              <label for="filter-date" class="block text-xs font-medium text-gray-700 mb-1">Date</label>
              <input type="date" id="filter-date" class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#e9922c] focus:border-transparent">
            </div>

            <!-- Category Filter -->
            <div>
              <label for="filter-category" class="block text-xs font-medium text-gray-700 mb-1">Category</label>
              <select id="filter-category" class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#e9922c] focus:border-transparent">
                <option value="">All Categories</option>
                <option value="MATERIALS">Materials</option>
                <option value="LABOR">Labor</option>
                <option value="EQUIPMENT">Equipment</option>
              </select>
            </div>

            <!-- Description Filter -->
            <div>
              <label for="filter-description" class="block text-xs font-medium text-gray-700 mb-1">Item / Description</label>
              <input type="text" id="filter-description" placeholder="Search..." class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#e9922c] focus:border-transparent">
            </div>

            <!-- Supplier Filter -->
            <div>
              <label for="filter-supplier" class="block text-xs font-medium text-gray-700 mb-1">Supplier</label>
              <input type="text" id="filter-supplier" placeholder="Search..." class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#e9922c] focus:border-transparent">
            </div>

            <!-- Amount Filter -->
            <div>
              <label for="filter-amount" class="block text-xs font-medium text-gray-700 mb-1">Amount</label>
              <input type="number" id="filter-amount" placeholder="Min amount" step="0.01" class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#e9922c] focus:border-transparent">
            </div>

            <!-- Status Filter -->
            <div>
              <label for="filter-status" class="block text-xs font-medium text-gray-700 mb-1">Status</label>
              <select id="filter-status" class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#e9922c] focus:border-transparent">
                <option value="">All Status</option>
                <option value="APPROVED">Approved</option>
                <option value="PENDING">Pending</option>
              </select>
            </div>
          </div>

          <!-- Filter Actions -->
          <div class="flex items-center justify-between mt-4 pt-4 border-t border-gray-200">
            <div class="text-sm text-gray-600">
              <span id="filter-results-count">Showing all expenses</span>
            </div>
            <div class="flex gap-2">
              <button id="clear-filters-btn" class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors duration-150">
                Clear Filters
              </button>
              <button id="apply-filters-btn" class="px-4 py-2 text-sm font-medium text-white bg-[#e9922c] hover:bg-[#d17f1f] rounded-lg transition-colors duration-150">
                Apply Filters
              </button>
            </div>
          </div>
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
                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Status</th>
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
                        <button onclick="openExpenseViewModal(<?php echo $expense['expense_id']; ?>)" class="text-blue-600 hover:text-blue-800 transition-colors" title="View Details">
                          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                          </svg>
                        </button>
                        <a href="edit_expense.php?id=<?php echo $expense['expense_id']; ?>" class="text-green-600 hover:text-green-800 transition-colors" title="Edit">
                          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                          </svg>
                        </a>
                        <button onclick="openDeleteExpenseModal(<?php echo $expense['expense_id']; ?>, '<?php echo htmlspecialchars($expense['description'], ENT_QUOTES); ?>')" class="text-red-600 hover:text-red-800 transition-colors" title="Delete">
                          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                          </svg>
                        </button>
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
  </main>

  <!-- Toast included globally via header.php -->

  <!-- View Expense Modal -->
  <div id="viewExpenseModal" class="hidden fixed inset-0 bg-black/60 backdrop-blur-sm z-50 flex items-center justify-center p-4 animate-fade-in">
    <div class="bg-white rounded-2xl shadow-2xl max-w-3xl w-full transform transition-all animate-modal-slide-in max-h-[90vh] overflow-y-auto">
      <!-- Modal Header -->
      <div class="bg-gradient-to-r from-blue-600 to-blue-700 p-6 rounded-t-2xl sticky top-0 z-10">
        <div class="flex items-center justify-between">
          <div class="flex items-center gap-4">
            <div class="bg-white rounded-full p-3 shadow-lg">
              <svg class="w-8 h-8 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
              </svg>
            </div>
            <div>
              <h3 class="text-2xl font-bold text-white">Expense Details</h3>
              <p class="text-blue-100 text-sm mt-1">View expense information</p>
            </div>
          </div>
          <button onclick="closeViewExpenseModal()" class="text-white hover:bg-white hover:bg-opacity-20 p-2 rounded-lg transition-all">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </div>
      </div>

      <!-- Modal Body -->
      <div id="viewExpenseContent" class="p-8">
        <div class="flex items-center justify-center py-12">
          <svg class="animate-spin h-10 w-10 text-blue-600" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
          </svg>
        </div>
      </div>

      <!-- Modal Footer -->
      <div class="flex items-center justify-end gap-3 p-6 bg-gray-50 border-t border-gray-200 rounded-b-2xl">
        <button onclick="closeViewExpenseModal()" class="px-6 py-3 text-gray-700 bg-white border-2 border-gray-300 rounded-xl hover:bg-gray-100 hover:border-gray-400 transition-all font-semibold shadow-sm">
          Close
        </button>
      </div>
    </div>
  </div>

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

    #viewExpenseModal,
    #deleteExpenseModal {
      transition: opacity 0.2s ease-out;
    }
  </style>
        
  <script>
    // Initialize Lucide icons
    lucide.createIcons();

    // Modal functionality removed - now using add_expense.php page for adding expenses

    // ============================================
    // FILTER FUNCTIONALITY
    // ============================================

    const filterToggleBtn = document.getElementById('filter-toggle-btn');
    const filterPanel = document.getElementById('filter-panel');
    const applyFiltersBtn = document.getElementById('apply-filters-btn');
    const clearFiltersBtn = document.getElementById('clear-filters-btn');
    const filterResultsCount = document.getElementById('filter-results-count');

    // Filter inputs
    const filterDate = document.getElementById('filter-date');
    const filterCategory = document.getElementById('filter-category');
    const filterDescription = document.getElementById('filter-description');
    const filterSupplier = document.getElementById('filter-supplier');
    const filterAmount = document.getElementById('filter-amount');
    const filterStatus = document.getElementById('filter-status');

    // Toggle filter panel
    if (filterToggleBtn) {
      filterToggleBtn.addEventListener('click', () => {
        filterPanel.classList.toggle('hidden');
        
        // Update button appearance
        if (filterPanel.classList.contains('hidden')) {
          filterToggleBtn.classList.remove('bg-[#e9922c]', 'text-white');
          filterToggleBtn.classList.add('bg-white', 'text-gray-700');
        } else {
          filterToggleBtn.classList.add('bg-[#e9922c]', 'text-white');
          filterToggleBtn.classList.remove('bg-white', 'text-gray-700', 'hover:bg-gray-50');
        }
      });
    }

    // Apply filters
    if (applyFiltersBtn) {
      applyFiltersBtn.addEventListener('click', applyFilters);
    }

    // Clear filters
    if (clearFiltersBtn) {
      clearFiltersBtn.addEventListener('click', clearFilters);
    }

    // Apply filters on Enter key
    [filterDate, filterCategory, filterDescription, filterSupplier, filterAmount, filterStatus].forEach(input => {
      if (input) {
        input.addEventListener('keypress', (e) => {
          if (e.key === 'Enter') {
            applyFilters();
          }
        });
      }
    });

    function applyFilters() {
      const tbody = document.querySelector('tbody');
      if (!tbody) return;

      const rows = tbody.querySelectorAll('tr');
      let visibleCount = 0;

      // Get filter values
      const dateFilter = filterDate.value;
      const categoryFilter = filterCategory.value.toUpperCase();
      const descriptionFilter = filterDescription.value.toLowerCase().trim();
      const supplierFilter = filterSupplier.value.toLowerCase().trim();
      const amountFilter = parseFloat(filterAmount.value) || 0;
      const statusFilter = filterStatus.value.toUpperCase();

      rows.forEach(row => {
        let shouldShow = true;

        // Date filter
        if (dateFilter && shouldShow) {
          const dateCell = row.querySelector('td:nth-child(1)');
          if (dateCell) {
            const rowDate = dateCell.textContent.trim();
            const rowDateObj = new Date(rowDate);
            const filterDateObj = new Date(dateFilter);
            
            if (rowDateObj.toDateString() !== filterDateObj.toDateString()) {
              shouldShow = false;
            }
          }
        }

        // Category filter
        if (categoryFilter && shouldShow) {
          const categoryCell = row.querySelector('td:nth-child(2) span');
          if (categoryCell) {
            const rowCategory = categoryCell.textContent.trim().toUpperCase();
            if (rowCategory !== categoryFilter) {
              shouldShow = false;
            }
          }
        }

        // Description filter
        if (descriptionFilter && shouldShow) {
          const descriptionCell = row.querySelector('td:nth-child(3)');
          if (descriptionCell) {
            const rowDescription = descriptionCell.textContent.trim().toLowerCase();
            if (!rowDescription.includes(descriptionFilter)) {
              shouldShow = false;
            }
          }
        }

        // Supplier filter
        if (supplierFilter && shouldShow) {
          const supplierCell = row.querySelector('td:nth-child(4)');
          if (supplierCell) {
            const rowSupplier = supplierCell.textContent.trim().toLowerCase();
            if (!rowSupplier.includes(supplierFilter)) {
              shouldShow = false;
            }
          }
        }

        // Amount filter (minimum amount)
        if (amountFilter > 0 && shouldShow) {
          const amountCell = row.querySelector('td:nth-child(5)');
          if (amountCell) {
            const rowAmountText = amountCell.textContent.trim().replace(/[₱,]/g, '');
            const rowAmount = parseFloat(rowAmountText);
            if (rowAmount < amountFilter) {
              shouldShow = false;
            }
          }
        }

        // Status filter
        if (statusFilter && shouldShow) {
          const statusCell = row.querySelector('td:nth-child(6) span');
          if (statusCell) {
            const rowStatus = statusCell.textContent.trim().toUpperCase();
            if (rowStatus !== statusFilter) {
              shouldShow = false;
            }
          }
        }

        // Show/hide row
        if (shouldShow) {
          row.style.display = '';
          visibleCount++;
        } else {
          row.style.display = 'none';
        }
      });

      // Update results count
      updateFilterResultsCount(visibleCount, rows.length);

      // Add active indicator to filter button if any filter is active
      const hasActiveFilters = dateFilter || categoryFilter || descriptionFilter || supplierFilter || amountFilter > 0 || statusFilter;
      if (hasActiveFilters) {
        filterToggleBtn.classList.add('bg-[#e9922c]', 'text-white');
        filterToggleBtn.classList.remove('bg-white', 'text-gray-700');
      }
    }

    function clearFilters() {
      // Clear all filter inputs
      filterDate.value = '';
      filterCategory.value = '';
      filterDescription.value = '';
      filterSupplier.value = '';
      filterAmount.value = '';
      filterStatus.value = '';

      // Show all rows
      const tbody = document.querySelector('tbody');
      if (tbody) {
        const rows = tbody.querySelectorAll('tr');
        rows.forEach(row => {
          row.style.display = '';
        });
        updateFilterResultsCount(rows.length, rows.length);
      }

      // Reset filter button appearance
      filterToggleBtn.classList.remove('bg-[#e9922c]', 'text-white');
      filterToggleBtn.classList.add('bg-white', 'text-gray-700');
    }

    function updateFilterResultsCount(visible, total) {
      if (visible === total) {
        filterResultsCount.textContent = `Showing all ${total} expense${total !== 1 ? 's' : ''}`;
      } else {
        filterResultsCount.textContent = `Showing ${visible} of ${total} expense${total !== 1 ? 's' : ''}`;
      }
    }

    // ============================================
    // PDF EXPORT FUNCTIONALITY
    // ============================================

    const exportPdfBtn = document.getElementById('export-pdf-btn');

    if (exportPdfBtn) {
      exportPdfBtn.addEventListener('click', generateExpenseReport);
    }

    async function generateExpenseReport() {
      const { jsPDF } = window.jspdf;
      
      try {
        // Show loading state
        exportPdfBtn.disabled = true;
        exportPdfBtn.innerHTML = '<i data-lucide="loader-2" class="w-4 h-4 animate-spin"></i><span class="text-sm font-medium">Generating...</span>';
        lucide.createIcons();

        // Prepare filter data
        const formData = new FormData();
        formData.append('project_id', <?php echo $selected_project_id; ?>);
        
        // Add active filters
        const filterDate = document.getElementById('filter-date').value;
        const filterCategory = document.getElementById('filter-category').value;
        const filterDescription = document.getElementById('filter-description').value;
        const filterSupplier = document.getElementById('filter-supplier').value;
        const filterAmount = document.getElementById('filter-amount').value;
        const filterStatus = document.getElementById('filter-status').value;

        if (filterDate) formData.append('filter_date', filterDate);
        if (filterCategory) formData.append('filter_category', filterCategory);
        if (filterDescription) formData.append('filter_description', filterDescription);
        if (filterSupplier) formData.append('filter_supplier', filterSupplier);
        if (filterAmount) formData.append('filter_amount', filterAmount);
        if (filterStatus) formData.append('filter_status', filterStatus);

        // Fetch data from server
        const response = await fetch('budget_expenses/export_pdf.php', {
          method: 'POST',
          body: formData
        });

        const result = await response.json();

        if (!result.success) {
          showToast(result.message || 'Failed to generate report', 'error');
          return;
        }

        const data = result.data;
        const project = data.project;
        const expenses = data.expenses;
        const totals = data.totals;

        // Generate PDF
        const doc = new jsPDF();
        const projectName = project.name;
        const projectCode = project.project_code;
        const totalBudget = parseFloat(project.total_budget);
        const actualSpending = parseFloat(project.actual_spending);
        const currentDate = new Date().toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' });

      // ===== HEADER SECTION =====
      const pageWidth = doc.internal.pageSize.getWidth();
      
      // Company Logo/Icon (Orange Square with "I")
      doc.setFillColor(233, 146, 44); // #e9922c
      doc.rect(pageWidth / 2 - 6, 15, 12, 12, 'F');
      doc.setTextColor(255, 255, 255);
      doc.setFontSize(16);
      doc.setFont('helvetica', 'bold');
      doc.text('I', pageWidth / 2, 24, { align: 'center' });

      // Company Name
      doc.setTextColor(0, 0, 0);
      doc.setFontSize(10);
      doc.setFont('helvetica', 'normal');
      doc.text('ICMIS - Integrated Construction Management Information System', pageWidth / 2, 32, { align: 'center' });

      // Report Title
      doc.setFontSize(16);
      doc.setFont('helvetica', 'bold');
      doc.text('PROJECT EXPENSE REPORT', pageWidth / 2, 42, { align: 'center' });

      // Project Information Box
      doc.setFontSize(9);
      doc.setFont('helvetica', 'normal');
      doc.setDrawColor(200, 200, 200);
      doc.rect(14, 48, pageWidth - 28, 28);
      
      doc.setFont('helvetica', 'bold');
      doc.text('Project Name:', 18, 54);
      doc.setFont('helvetica', 'normal');
      doc.text(projectName, 45, 54);

      doc.setFont('helvetica', 'bold');
      doc.text('Project Code:', 18, 60);
      doc.setFont('helvetica', 'normal');
      doc.text(projectCode || 'N/A', 45, 60);

      doc.setFont('helvetica', 'bold');
      doc.text('Report Date:', 18, 66);
      doc.setFont('helvetica', 'normal');
      doc.text(currentDate, 45, 66);

      doc.setFont('helvetica', 'bold');
      doc.text('Generated By:', 18, 72);
      doc.setFont('helvetica', 'normal');
      doc.text('<?php echo addslashes($userName); ?> (<?php echo addslashes($userRole); ?>)', 45, 72);

      // ===== EXPENSE TABLE =====
      const tableData = expenses.map(expense => [
        new Date(expense.expense_date).toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' }),
        expense.category,
        expense.description,
        expense.supplier_name || 'N/A',
        'PHP ' + parseFloat(expense.amount).toLocaleString('en-PH', {minimumFractionDigits: 2, maximumFractionDigits: 2}),
        expense.status
      ]);

      doc.autoTable({
        startY: 82,
        head: [['Date', 'Category', 'Description', 'Supplier Name', 'Amount', 'Status']],
        body: tableData,
        theme: 'grid',
        headStyles: {
          fillColor: [233, 146, 44],
          textColor: [255, 255, 255],
          fontStyle: 'bold',
          fontSize: 9
        },
        bodyStyles: {
          fontSize: 8,
          textColor: [50, 50, 50]
        },
        columnStyles: {
          0: { cellWidth: 25 },
          1: { cellWidth: 25 },
          2: { cellWidth: 50 },
          3: { cellWidth: 35 },
          4: { cellWidth: 28, halign: 'right' },
          5: { cellWidth: 22 }
        },
        alternateRowStyles: {
          fillColor: [245, 245, 245]
        }
      });

      // ===== FINANCIAL SUMMARY SECTION =====
      let finalY = doc.lastAutoTable.finalY + 10;

      // Summary box
      doc.setFillColor(250, 250, 250);
      doc.rect(14, finalY, pageWidth - 28, 35, 'F');
      doc.setDrawColor(200, 200, 200);
      doc.rect(14, finalY, pageWidth - 28, 35);

      doc.setFontSize(11);
      doc.setFont('helvetica', 'bold');
      doc.text('FINANCIAL SUMMARY', 18, finalY + 6);

      doc.setFontSize(9);
      doc.setFont('helvetica', 'normal');
      
      // Category breakdown
      doc.text('Total Materials:', 18, finalY + 13);
      doc.setFont('helvetica', 'bold');
      doc.text('PHP ' + totals.materials.toLocaleString('en-PH', {minimumFractionDigits: 2, maximumFractionDigits: 2}), 60, finalY + 13);

      doc.setFont('helvetica', 'normal');
      doc.text('Total Labor:', 18, finalY + 19);
      doc.setFont('helvetica', 'bold');
      doc.text('PHP ' + totals.labor.toLocaleString('en-PH', {minimumFractionDigits: 2, maximumFractionDigits: 2}), 60, finalY + 19);

      doc.setFont('helvetica', 'normal');
      doc.text('Total Equipment:', 18, finalY + 25);
      doc.setFont('helvetica', 'bold');
      doc.text('PHP ' + totals.equipment.toLocaleString('en-PH', {minimumFractionDigits: 2, maximumFractionDigits: 2}), 60, finalY + 25);

      // Grand total
      doc.setDrawColor(233, 146, 44);
      doc.setLineWidth(0.5);
      doc.line(18, finalY + 28, pageWidth - 18, finalY + 28);

      doc.setFontSize(11);
      doc.setFont('helvetica', 'bold');
      doc.setTextColor(233, 146, 44);
      doc.text('GRAND TOTAL SPENT:', 18, finalY + 33);
      doc.text('PHP ' + totals.grand_total.toLocaleString('en-PH', {minimumFractionDigits: 2, maximumFractionDigits: 2}), pageWidth - 18, finalY + 33, { align: 'right' });

      // ===== APPROVAL FOOTER =====
      finalY += 45;

      doc.setTextColor(0, 0, 0);
      doc.setFontSize(9);
      doc.setFont('helvetica', 'normal');

      // Prepared By
      doc.text('Prepared By:', 18, finalY);
      doc.line(18, finalY + 8, 80, finalY + 8);
      doc.setFont('helvetica', 'italic');
      doc.setFontSize(8);
      doc.text('Budget Officer', 18, finalY + 12);

      // Approved By
      doc.setFont('helvetica', 'normal');
      doc.setFontSize(9);
      doc.text('Approved By:', pageWidth - 80, finalY);
      doc.line(pageWidth - 80, finalY + 8, pageWidth - 18, finalY + 8);
      doc.setFont('helvetica', 'italic');
      doc.setFontSize(8);
      doc.text('Project Manager', pageWidth - 80, finalY + 12);

      // Footer
      const pageCount = doc.internal.getNumberOfPages();
      for (let i = 1; i <= pageCount; i++) {
        doc.setPage(i);
        doc.setFontSize(8);
        doc.setTextColor(128, 128, 128);
        doc.setFont('helvetica', 'normal');
        doc.text(
          `Page ${i} of ${pageCount}`,
          pageWidth / 2,
          doc.internal.pageSize.getHeight() - 10,
          { align: 'center' }
        );
        doc.text(
          'Generated by ICMIS on ' + currentDate,
          pageWidth / 2,
          doc.internal.pageSize.getHeight() - 6,
          { align: 'center' }
        );
      }

      // Save the PDF
      const fileName = `Expense_Report_${projectCode || 'Project'}_${new Date().toISOString().split('T')[0]}.pdf`;
      doc.save(fileName);

      showToast('Expense report exported successfully!', 'success');
    } catch (error) {
      console.error('Error generating PDF:', error);
      showToast('Failed to generate PDF report. Please try again.', 'error');
    } finally {
      // Restore button state
      exportPdfBtn.disabled = false;
      exportPdfBtn.innerHTML = '<i data-lucide="file-text" class="w-4 h-4"></i><span class="text-sm font-medium">Export as PDF</span>';
      lucide.createIcons();
    }
  }

    // ============================================
    // VIEW EXPENSE MODAL
    // ============================================
    function openExpenseViewModal(expenseId) {
      const modal = document.getElementById('viewExpenseModal');
      const content = document.getElementById('viewExpenseContent');
      
      modal.classList.remove('hidden');
      document.body.style.overflow = 'hidden';
      
      // Show loading state
      content.innerHTML = `
        <div class="flex items-center justify-center py-12">
          <svg class="animate-spin h-10 w-10 text-blue-600" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
          </svg>
        </div>
      `;
      
      // Fetch expense details
      fetch(`budget_expenses/get_expense_details.php?id=${expenseId}`)
        .then(response => response.json())
        .then(result => {
          if (result.success) {
            const expense = result.expense;
            
            // Category badge colors
            const categoryColors = {
              'MATERIALS': 'bg-purple-100 text-purple-800',
              'EQUIPMENT': 'bg-green-100 text-green-800',
              'PROFESSIONAL_FEES': 'bg-blue-100 text-blue-800',
              'SUBCONTRACTOR': 'bg-orange-100 text-orange-800',
              'OVERHEAD': 'bg-gray-100 text-gray-800',
              'LABOR': 'bg-cyan-100 text-cyan-800'
            };
            
            const statusColors = {
              'APPROVED': 'bg-green-100 text-green-700',
              'PENDING': 'bg-yellow-100 text-yellow-700',
              'REJECTED': 'bg-red-100 text-red-700'
            };
            
            content.innerHTML = `
              <div class="space-y-6">
                <!-- Project and Phase Info -->
                <div class="grid grid-cols-2 gap-4">
                  <div class="bg-gray-50 rounded-xl p-4 border border-gray-200">
                    <p class="text-xs text-gray-500 uppercase tracking-wide mb-2 font-semibold">Project</p>
                    <p class="text-lg font-bold text-gray-900">${expense.project_name || 'N/A'}</p>
                    <p class="text-sm text-gray-500 mt-1">${expense.project_code || ''}</p>
                  </div>
                  <div class="bg-gray-50 rounded-xl p-4 border border-gray-200">
                    <p class="text-xs text-gray-500 uppercase tracking-wide mb-2 font-semibold">Phase</p>
                    <p class="text-lg font-bold text-gray-900">${expense.phase || 'N/A'}</p>
                  </div>
                </div>
                
                <!-- Expense Date and Amount -->
                <div class="grid grid-cols-2 gap-4">
                  <div class="bg-blue-50 rounded-xl p-4 border-2 border-blue-200">
                    <p class="text-xs text-blue-700 uppercase tracking-wide mb-2 font-semibold">Expense Date</p>
                    <p class="text-lg font-bold text-blue-900">${expense.formatted_date}</p>
                  </div>
                  <div class="bg-orange-50 rounded-xl p-4 border-2 border-orange-200">
                    <p class="text-xs text-orange-700 uppercase tracking-wide mb-2 font-semibold">Amount</p>
                    <p class="text-2xl font-bold text-orange-900">₱${parseFloat(expense.amount).toLocaleString('en-PH', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</p>
                  </div>
                </div>
                
                <!-- Category and Status -->
                <div class="grid grid-cols-2 gap-4">
                  <div>
                    <p class="text-sm text-gray-600 mb-2 font-medium">Category</p>
                    <span class="inline-flex items-center px-4 py-2 rounded-lg text-sm font-semibold ${categoryColors[expense.category] || 'bg-gray-100 text-gray-800'}">
                      ${expense.category}
                    </span>
                  </div>
                  <div>
                    <p class="text-sm text-gray-600 mb-2 font-medium">Status</p>
                    <span class="inline-flex items-center px-4 py-2 rounded-full text-sm font-semibold ${statusColors[expense.status] || 'bg-gray-100 text-gray-700'}">
                      ${expense.status}
                    </span>
                  </div>
                </div>
                
                <!-- Description -->
                <div class="bg-gray-50 rounded-xl p-5 border border-gray-200">
                  <p class="text-sm text-gray-600 mb-2 font-semibold uppercase tracking-wide">Description</p>
                  <p class="text-gray-900 leading-relaxed">${expense.description}</p>
                </div>
                
                <!-- Supplier Information -->
                <div class="bg-linear-to-br from-blue-50 to-indigo-50 rounded-xl p-5 border-2 border-blue-200">
                  <div class="flex items-center gap-3 mb-3">
                    <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                    </svg>
                    <p class="text-sm text-blue-900 font-bold uppercase tracking-wide">Supplier Information</p>
                  </div>
                  <p class="text-xl font-bold text-blue-900 mb-2">${expense.supplier_name || 'N/A'}</p>
                  ${expense.phone ? `<p class="text-sm text-blue-700">📞 ${expense.phone}</p>` : ''}
                  ${expense.email ? `<p class="text-sm text-blue-700">📧 ${expense.email}</p>` : ''}
                  ${expense.address ? `<p class="text-sm text-blue-700 mt-2">📍 ${expense.address}</p>` : ''}
                </div>
                
                ${expense.notes ? `
                <div class="bg-yellow-50 rounded-xl p-5 border-2 border-yellow-200">
                  <p class="text-sm text-yellow-900 mb-2 font-semibold uppercase tracking-wide">Additional Notes</p>
                  <p class="text-gray-700 leading-relaxed">${expense.notes}</p>
                </div>
                ` : ''}
                
                ${expense.receipt_path ? `
                <div class="bg-green-50 rounded-xl p-5 border-2 border-green-200">
                  <div class="flex items-center justify-between">
                    <div class="flex items-center gap-3">
                      <svg class="w-6 h-6 text-green-600" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4z" clip-rule="evenodd"/>
                      </svg>
                      <p class="text-sm text-green-900 font-semibold">Receipt Attached</p>
                    </div>
                    <a href="${expense.receipt_path}" target="_blank" class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg text-sm font-medium transition-colors">
                      View Receipt
                    </a>
                  </div>
                </div>
                ` : ''}
                
                <!-- Timestamps -->
                <div class="border-t border-gray-200 pt-4 grid grid-cols-2 gap-4 text-sm text-gray-500">
                  <div>
                    <span class="font-medium">Created:</span> ${expense.formatted_created}
                  </div>
                  <div>
                    <span class="font-medium">Updated:</span> ${expense.formatted_updated}
                  </div>
                </div>
              </div>
            `;
          } else {
            content.innerHTML = `
              <div class="text-center py-8">
                <svg class="w-16 h-16 text-red-500 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <p class="text-gray-700 font-medium">${result.message || 'Failed to load expense details'}</p>
              </div>
            `;
          }
        })
        .catch(error => {
          console.error('Error fetching expense details:', error);
          content.innerHTML = `
            <div class="text-center py-8">
              <svg class="w-16 h-16 text-red-500 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
              <p class="text-gray-700 font-medium">An error occurred while loading expense details</p>
            </div>
          `;
        });
    }

    function closeViewExpenseModal() {
      document.getElementById('viewExpenseModal').classList.add('hidden');
      document.body.style.overflow = '';
    }

    // ============================================
    // DELETE EXPENSE FUNCTIONALITY
    // ============================================
    let expenseToDelete = null;

    function openDeleteExpenseModal(expenseId, description) {
      expenseToDelete = expenseId;
      document.getElementById('deleteExpenseDescription').textContent = description;
      document.getElementById('deleteExpenseModal').classList.remove('hidden');
      document.body.style.overflow = 'hidden';
    }

    function closeDeleteExpenseModal() {
      expenseToDelete = null;
      document.getElementById('deleteExpenseModal').classList.add('hidden');
      document.body.style.overflow = '';
    }

    function confirmDeleteExpense() {
      if (!expenseToDelete) return;

      const confirmBtn = document.getElementById('confirmDeleteExpenseBtn');
      const originalBtnContent = confirmBtn.innerHTML;
      
      // Disable button and show loading state
      confirmBtn.disabled = true;
      confirmBtn.innerHTML = `
        <svg class="animate-spin h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
          <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
          <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
        </svg>
        Deleting...
      `;

      fetch('budget_expenses/delete_expense.php', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify({
          expense_id: expenseToDelete
        })
      })
      .then(response => response.json())
      .then(result => {
        if (result.success) {
          // Persist toast so it appears after reload
          showToast(result.message || 'Expense deleted successfully', 'success', true);
          closeDeleteExpenseModal();
          // Reload page after short delay
          setTimeout(() => {
            window.location.reload();
          }, 300);
        } else {
          showToast('Error: ' + result.message, 'error');
          // Re-enable button
          confirmBtn.disabled = false;
          confirmBtn.innerHTML = originalBtnContent;
        }
      })
      .catch(error => {
        console.error('Delete error:', error);
        showToast('Error deleting expense: ' + error.message, 'error');
        // Re-enable button
        confirmBtn.disabled = false;
        confirmBtn.innerHTML = originalBtnContent;
      });
    }

    // Close modals when clicking outside
    document.getElementById('viewExpenseModal').addEventListener('click', function(e) {
      if (e.target === this) {
        closeViewExpenseModal();
      }
    });

    document.getElementById('deleteExpenseModal').addEventListener('click', function(e) {
      if (e.target === this) {
        closeDeleteExpenseModal();
      }
    });

    // Close modals with Escape key
    document.addEventListener('keydown', function(e) {
      if (e.key === 'Escape') {
        const viewModal = document.getElementById('viewExpenseModal');
        const deleteModal = document.getElementById('deleteExpenseModal');
        
        if (!viewModal.classList.contains('hidden')) {
          closeViewExpenseModal();
        }
        if (!deleteModal.classList.contains('hidden')) {
          closeDeleteExpenseModal();
        }
      }
    });


  </script>
</body>
</html>