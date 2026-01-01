<!DOCTYPE html>
<html lang="en">
<head>
  <!-- Global project styles -->
  <link rel="stylesheet" href="/icmis_budget/css/output.css">
  <link rel="stylesheet" href="/icmis_budget/css/input.css">
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Budget Dashboard - ICMIS</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Arimo:wght@400;500;600;700&display=swap" rel="stylesheet">
  <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
  <script src="https://unpkg.com/lucide@latest"></script>
  <style>
    body {
      font-family: 'Arimo', sans-serif;
    }
  </style>
</head>
<body class="bg-gray-50">
  <?php 
    include __DIR__ . '/connection.php';
    include __DIR__ . '/core/Context.php';
    
    // Get selected project ID from global context BEFORE sidebar
    $selected_project_id = getProjectContext($conn);
    
    $userName = "John Doe";
    $userRole = "Financial Manager";
    $notificationCount = 0;
  ?>
  <?php include __DIR__ . '/../components/sidebar.php'; ?>

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

    // Build breadcrumb navigation with dropdown
    $current_page = basename($_SERVER['PHP_SELF']);
    $breadcrumbHTML = '<div class="flex items-center gap-2 text-sm">';
    
    // Project Dropdown
    $breadcrumbHTML .= '<div class="relative inline-block">';
    $breadcrumbHTML .= '<select id="projectSelector" onchange="window.location.href=\'' . $current_page . '?project_id=\' + this.value" class="appearance-none bg-white border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-2 focus:ring-[#e9922c] focus:border-[#e9922c] pl-3 pr-8 py-1.5 hover:bg-gray-50 transition-colors cursor-pointer font-medium">';
    
    foreach ($projects as $proj) {
      $selected = ($proj['project_id'] == $selected_project_id) ? 'selected' : '';
      $breadcrumbHTML .= '<option value="' . $proj['project_id'] . '" ' . $selected . '>' . htmlspecialchars($proj['name']) . '</option>';
    }
    
    $breadcrumbHTML .= '</select>';
    $breadcrumbHTML .= '<svg class="w-3 h-3 text-gray-500 absolute right-2 top-1/2 transform -translate-y-1/2 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>';
    $breadcrumbHTML .= '</div>';
    
    $breadcrumbHTML .= '</div>';

    // Set header variables
    $pageTitle = "Budget Dashboard";
    $pageSection = "Budget & Cost Control";
    $pageSubTitle = $breadcrumbHTML;
    
    include __DIR__ . '/../components/header.php';

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
      // Query budget proposals and expenses separately, then merge
      // First get all phases with approved budget proposals
      $sql_phases = "SELECT 
                      bp.phase,
                      COALESCE(SUM(bp.total_amount), 0) as allocated,
                      MIN(bp.phase_start_date) as phase_start_date,
                      MAX(bp.phase_end_date) as phase_end_date
                     FROM budget_proposals bp
                     WHERE bp.project_id = ? AND bp.status = 'APPROVED'
                     GROUP BY bp.phase
                     ORDER BY bp.phase";
      $stmt_phases = $conn->prepare($sql_phases);
      $stmt_phases->bind_param("i", $selected_project_id);
      $stmt_phases->execute();
      $result_phases = $stmt_phases->get_result();
      
      $phase_definitions = [
        'Phase 1: Mobilization' => ['color' => 'blue', 'icon' => 'truck'],
        'Phase 2: Structural' => ['color' => 'purple', 'icon' => 'building-2'],
        'Phase 3: MEPFS' => ['color' => 'orange', 'icon' => 'zap'],
        'Phase 4: Finishing' => ['color' => 'green', 'icon' => 'check-circle-2']
      ];

      // Build phases data from budget proposals
      while ($row = $result_phases->fetch_assoc()) {
        $phase_name = $row['phase'];
        $allocated = floatval($row['allocated']);
        
        // Format date range
        $date_range = 'No dates set';
        if (!empty($row['phase_start_date']) && !empty($row['phase_end_date'])) {
          $start_date = new DateTime($row['phase_start_date']);
          $end_date = new DateTime($row['phase_end_date']);
          $date_range = $start_date->format('M j') . ' - ' . $end_date->format('M j, Y');
        }
        
        // Only add phases that are in our definitions
        if (isset($phase_definitions[$phase_name])) {
          $phases_data[$phase_name] = [
            'phase' => $phase_name,
            'color' => $phase_definitions[$phase_name]['color'],
            'icon' => $phase_definitions[$phase_name]['icon'],
            'date_range' => $date_range,
            'allocated' => $allocated,
            'spent' => 0,
            'remaining' => $allocated,
            'utilization' => 0,
            'expense_count' => 0,
            'status' => 'Active' // Phases with approved budgets are active
          ];
        }
      }
      $stmt_phases->close();

      // Now get expenses data and update the phases
      $sql_expenses = "SELECT 
                        phase,
                        COALESCE(SUM(CASE WHEN status = 'APPROVED' THEN amount ELSE 0 END), 0) as spent,
                        COUNT(expense_id) as expense_count
                       FROM budget_expenses
                       WHERE project_id = ?
                       GROUP BY phase";
      $stmt_expenses = $conn->prepare($sql_expenses);
      $stmt_expenses->bind_param("i", $selected_project_id);
      $stmt_expenses->execute();
      $result_expenses = $stmt_expenses->get_result();

      while ($row = $result_expenses->fetch_assoc()) {
        $phase_name = $row['phase'];
        $spent = floatval($row['spent']);
        
        if (isset($phases_data[$phase_name])) {
          $phases_data[$phase_name]['spent'] = $spent;
          $phases_data[$phase_name]['remaining'] = $phases_data[$phase_name]['allocated'] - $spent;
          $phases_data[$phase_name]['utilization'] = $phases_data[$phase_name]['allocated'] > 0 ? 
                                                      ($spent / $phases_data[$phase_name]['allocated']) * 100 : 0;
          $phases_data[$phase_name]['expense_count'] = intval($row['expense_count']);
          
          // Update status based on utilization
          if ($phases_data[$phase_name]['utilization'] > 100) {
            $phases_data[$phase_name]['status'] = 'Over Budget';
          } elseif ($phases_data[$phase_name]['utilization'] >= 99) {
            $phases_data[$phase_name]['status'] = 'Completed';
          }
          // Otherwise keep 'Active' status
        }
      }
      $stmt_expenses->close();

      // Fill in missing phases with zero data
      foreach ($phase_definitions as $phase_name => $phase_info) {
        if (!isset($phases_data[$phase_name])) {
          $phases_data[$phase_name] = [
            'phase' => $phase_name,
            'color' => $phase_info['color'],
            'icon' => $phase_info['icon'],
            'date_range' => 'No dates set',
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
      <?php if ($has_approved_proposals): ?>
      <!-- Dynamic Alert Banner -->
      <?php
        $alert_colors = [
          'high' => ['bg' => 'bg-red-50', 'border' => 'border-red-200', 'icon' => 'text-red-600', 'text' => 'text-red-900'],
          'warning' => ['bg' => 'bg-amber-50', 'border' => 'border-amber-200', 'icon' => 'text-amber-600', 'text' => 'text-amber-900'],
          'good' => ['bg' => 'bg-green-50', 'border' => 'border-green-200', 'icon' => 'text-green-600', 'text' => 'text-green-900']
        ];
        $colors = $alert_colors[$alert_type];
      ?>
      <div class="<?php echo $colors['bg']; ?> border <?php echo $colors['border']; ?> rounded-lg p-4 mb-6 flex items-start gap-3">
        <i data-lucide="alert-triangle" class="w-5 h-5 <?php echo $colors['icon']; ?> mt-0.5 shrink-0"></i>
        <p class="<?php echo $colors['text']; ?> text-sm font-medium"><?php echo htmlspecialchars($alert_message); ?></p>
      </div>

      
      <!-- Budget Summary Cards -->
      <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
        <!-- Total Budget Allocated -->
        <div class="bg-white rounded-xl shadow-sm p-6 border-2 border-[#e9922c] hover:shadow-lg transition-shadow duration-300">
          <div class="flex items-center justify-center w-12 h-12 bg-linear-to-br from-[#e9922c] to-[#d17f1f] rounded-lg mb-4">
            <i data-lucide="wallet" class="w-6 h-6 text-white"></i>
          </div>
          <p class="text-gray-600 text-sm mb-1">Total Budget Allocated</p>
          <p class="text-gray-900 text-3xl font-bold mb-1">₱<?php echo number_format($total_budget, 2); ?></p>
          <p class="text-gray-500 text-xs">Across 4 phases</p>
        </div>

        <!-- Total Spent -->
        <div class="bg-white rounded-xl shadow-sm p-6 border-2 <?php echo $budget_utilization > 90 ? 'border-red-500' : 'border-green-500'; ?> hover:shadow-lg transition-shadow duration-300">
          <div class="flex items-center justify-center w-12 h-12 bg-linear-to-br <?php echo $budget_utilization > 90 ? 'from-red-500 to-red-600' : 'from-green-500 to-green-600'; ?> rounded-lg mb-4">
            <i data-lucide="trending-down" class="w-6 h-6 text-white"></i>
          </div>
          <p class="text-gray-600 text-sm mb-1">Total Spent</p>
          <p class="text-gray-900 text-3xl font-bold mb-1">₱<?php echo number_format($actual_spending, 2); ?></p>
          <p class="text-gray-500 text-xs"><?php echo number_format($budget_utilization, 1); ?>% utilized</p>
        </div>

        <!-- Remaining Budget -->
        <div class="bg-white rounded-xl shadow-sm p-6 border-2 border-blue-500 hover:shadow-lg transition-shadow duration-300">
          <div class="flex items-center justify-center w-12 h-12 bg-linear-to-br from-blue-500 to-blue-600 rounded-lg mb-4">
            <i data-lucide="piggy-bank" class="w-6 h-6 text-white"></i>
          </div>
          <p class="text-gray-600 text-sm mb-1">Remaining Budget</p>
          <p class="text-gray-900 text-3xl font-bold mb-1">₱<?php echo number_format($remaining_budget, 2); ?></p>
          <p class="text-gray-500 text-xs"><?php echo number_format(100 - $budget_utilization, 1); ?>% available</p>
        </div>

        <!-- Active Phases -->
        <div class="bg-white rounded-xl shadow-sm p-6 border-2 border-purple-500 hover:shadow-lg transition-shadow duration-300">
          <div class="flex items-center justify-center w-12 h-12 bg-linear-to-br from-purple-500 to-purple-600 rounded-lg mb-4">
            <i data-lucide="layers" class="w-6 h-6 text-white"></i>
          </div>
          <p class="text-gray-600 text-sm mb-1">Active Phases</p>
          <p class="text-gray-900 text-3xl font-bold mb-1"><?php echo $active_phases_count; ?>/4</p>
          <p class="text-gray-500 text-xs">Currently ongoing</p>
        </div>
      </div>

      <!-- Phase Cards Grid -->
      <div class="grid grid-cols-1 <?php if ($active_phases_count > 1) echo 'lg:grid-cols-2'; ?> gap-6 mb-8">
        <?php foreach ($phases_data as $phase_name => $phase):
          // Only render active phases (not upcoming)
          if ($phase['status'] === 'Upcoming') continue;
          
          $color = $phase['color'];
          $phase_number = substr($phase_name, 6, 1); // Extract phase number
          $phase_short_name = str_replace('Phase ' . $phase_number . ': ', '', $phase_name);
          
          // Progress bar color should match the phase card color
          $progress_color = $color;
          
          // Status badge colors
          $status_colors = [
            'Completed' => 'bg-green-100 text-green-700 border-green-300',
            'Active' => 'bg-blue-100 text-blue-700 border-blue-300',
            'Upcoming' => 'bg-gray-100 text-gray-600 border-gray-300',
            'Over Budget' => 'bg-red-100 text-red-700 border-red-300'
          ];
        ?>
        <!-- Phase Card -->
        <div class="bg-white border-2 border-<?php echo $color; ?>-500 rounded-xl shadow-sm hover:shadow-xl hover:-translate-y-1 transition-all duration-300 overflow-hidden cursor-pointer phase-card" data-phase="<?php echo htmlspecialchars($phase_name); ?>">
          <!-- Card Header with Gradient -->
          <div class="bg-linear-to-r from-<?php echo $color; ?>-500 to-<?php echo $color; ?>-600 px-5 py-4 flex items-center justify-between">
            <div class="flex items-center gap-3">
              <div class="w-10 h-10 bg-white/20 backdrop-blur-sm rounded-lg flex items-center justify-center">
                <i data-lucide="<?php echo $phase['icon']; ?>" class="w-5 h-5 text-white"></i>
              </div>
              <div>
                <h3 class="text-white font-bold text-lg">Phase <?php echo $phase_number; ?>: <?php echo htmlspecialchars($phase_short_name); ?></h3>
              </div>
            </div>
            <span class="px-3 py-1 text-xs font-semibold rounded-full border <?php echo $status_colors[$phase['status']]; ?>">
              <?php echo $phase['status']; ?>
            </span>
          </div>

          <!-- Card Body -->
          <div class="p-5">
            <!-- Timeline -->
            <div class="flex items-center gap-2 text-gray-600 text-sm mb-4">
              <i data-lucide="calendar" class="w-4 h-4"></i>
              <span><?php echo $phase['date_range']; ?></span>
            </div>

            <!-- Metrics Grid -->
            <div class="grid grid-cols-3 gap-3 mb-4">
              <!-- Budget -->
              <div class="bg-gray-50 rounded-lg p-3 text-center">
                <p class="text-xs text-gray-500 mb-1">Budget</p>
                <p class="text-lg font-bold text-gray-900">₱<?php echo number_format($phase['allocated'], 2); ?></p>
              </div>

              <!-- Spent -->
              <div class="bg-gray-50 rounded-lg p-3 text-center">
                <p class="text-xs text-gray-500 mb-1">Spent</p>
                <p class="text-lg font-bold text-<?php echo $color; ?>-600">₱<?php echo number_format($phase['spent'], 2); ?></p>
                <p class="text-xs text-gray-500"><?php echo number_format($phase['utilization'], 1); ?>%</p>
              </div>

              <!-- Remaining -->
              <div class="bg-gray-50 rounded-lg p-3 text-center">
                <p class="text-xs text-gray-500 mb-1">Remaining</p>
                <p class="text-lg font-bold text-gray-900">₱<?php echo number_format($phase['remaining'], 2); ?></p>
                <p class="text-xs text-gray-500"><?php echo number_format(100 - $phase['utilization'], 1); ?>%</p>
              </div>
            </div>

            <!-- Progress Bar -->
            <div class="mb-4">
              <div class="flex items-center justify-between mb-2">
                <span class="text-sm font-medium text-gray-700">Budget Utilization</span>
                <span class="text-sm font-bold text-gray-900"><?php echo number_format($phase['utilization'], 1); ?>%</span>
              </div>
              <div class="w-full bg-gray-200 rounded-full h-3 overflow-hidden">
                <div class="bg-<?php echo $progress_color; ?>-500 h-3 rounded-full transition-all duration-300" style="width: <?php echo min($phase['utilization'], 100); ?>%"></div>
              </div>
              <?php if ($phase['utilization'] > 100): ?>
              <div class="flex items-center gap-2 mt-2 text-red-600 text-xs">
                <i data-lucide="alert-triangle" class="w-3 h-3"></i>
                <span>Over budget by ₱<?php echo number_format($phase['spent'] - $phase['allocated'], 2); ?></span>
              </div>
              <?php endif; ?>
            </div>

            <!-- Card Footer -->
            <div class="flex items-center justify-between pt-4 border-t border-gray-200">
              <div class="flex items-center gap-4 text-sm text-gray-600">
                <div class="flex items-center gap-1">
                  <i data-lucide="receipt" class="w-4 h-4"></i>
                  <span><?php echo $phase['expense_count']; ?> expenses</span>
                </div>
              </div>
              <button class="flex items-center gap-1 text-<?php echo $color; ?>-600 hover:text-<?php echo $color; ?>-700 font-medium text-sm transition-colors">
                <span>View Details</span>
                <i data-lucide="chevron-right" class="w-4 h-4"></i>
              </button>
            </div>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
      <?php else: ?>
      <!-- No Approved Budget Proposals State -->
      <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-12 h-[calc(100vh-128px)] flex items-center justify-center">
        <div class="max-w-md mx-auto text-center">
          <div class="flex justify-center mb-6">
            <div class="bg-blue-50 rounded-full p-6">
              <svg class="w-16 h-16 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
              </svg>
            </div>
          </div>
          <h2 class="text-xl text-gray-900 font-bold mb-3">No Approved Budget Proposals</h2>
          <p class="text-gray-500 mb-8 leading-relaxed">
            Please create and approve budget proposals first to view the budget dashboard and track expenses.
          </p>
          <!-- Helper Information Section -->
            <div class="mt-8 pt-8 border-t border-gray-200">
              <p class="text-sm text-gray-600 mb-3 text-left">To view the budget dashboard:</p>
              
              <!-- Feature Cards Grid -->
              <div class="space-y-3">
                <!-- Feature 1 -->
                <div class="flex gap-3 bg-gray-50 rounded-lg p-3 text-left">
                  <div class="flex-shrink-0 mt-1.5">
                    <div class="w-1.5 h-1.5 bg-[#e9922c] rounded-full"></div>
                  </div>
                  <div>
                    <div class="text-sm text-gray-900 font-medium">Navigate to Budget Proposals section</div>
                  </div>
                </div>

                <!-- Feature 2 -->
                <div class="flex gap-3 bg-gray-50 rounded-lg p-3 text-left">
                  <div class="flex-shrink-0 mt-1.5">
                    <div class="w-1.5 h-1.5 bg-[#e9922c] rounded-full"></div>
                  </div>
                  <div>
                    <div class="text-sm text-gray-900 font-medium">Create a new budget proposal for this project</div>
                  </div>
                </div>

                <!-- Feature 3 -->
                <div class="flex gap-3 bg-gray-50 rounded-lg p-3 text-left">
                  <div class="flex-shrink-0 mt-1.5">
                    <div class="w-1.5 h-1.5 bg-[#e9922c] rounded-full"></div>
                  </div>
                  <div>
                    <div class="text-sm text-gray-900 font-medium">Submit and get approval for the proposal</div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      <?php endif; ?>
    </div>
  </main>

  <!-- Toast included globally via header.php -->

  <!-- Phase Detail Modal (Receipt Style) -->
  <div id="phase-detail-modal" class="hidden fixed inset-0 bg-black/60 backdrop-blur-sm z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-4xl max-h-[90vh] overflow-hidden">
      <!-- Modal Header -->
      <div class="bg-linear-to-r from-slate-800 to-slate-700 px-6 py-5 relative">
        <div class="absolute top-0 left-0 right-0 h-1 phase-modal-border"></div>
        <div class="flex items-center justify-between">
          <div class="flex items-center gap-4">
            <div class="w-12 h-12 bg-linear-to-br from-[#e9922c] to-[#d17f1f] rounded-lg flex items-center justify-center text-white text-2xl font-bold shadow-lg">
              I
            </div>
            <div>
              <h3 class="text-2xl font-bold text-white">Phase Budget Details</h3>
              <p class="text-gray-300 text-sm" id="phase-modal-project">Project Details</p>
            </div>
          </div>
          <button type="button" id="close-phase-modal-btn" class="text-white hover:bg-white/10 rounded-lg p-2 transition-colors">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
            </svg>
          </button>
        </div>
      </div>

      <!-- Scrollable Content -->
      <div class="overflow-y-auto max-h-[calc(90vh-160px)] p-6">
        <!-- Receipt Document -->
        <div id="receipt-content" class="bg-white rounded-xl border-t-4 phase-border shadow-lg">
          <!-- Company Header -->
          <div class="border-b-2 border-dashed border-gray-300 p-6 text-center">
            <div class="flex justify-center mb-3">
              <div class="w-16 h-16 bg-linear-to-br from-[#e9922c] to-[#d17f1f] rounded-xl flex items-center justify-center text-white text-3xl font-bold shadow-lg">
                I
              </div>
            </div>
            <h2 class="text-2xl font-bold text-gray-900">ICMIS</h2>
            <p class="text-sm text-gray-600">Integrated Construction Management Information System</p>
            <p class="text-xs text-gray-500 mt-1">Budget Proposal Document</p>
          </div>

          <!-- Info Grid -->
          <div class="grid grid-cols-2 gap-4 p-6 border-b border-gray-200 bg-gray-50">
            <div>
              <p class="text-xs text-gray-500 mb-1">Phase Name</p>
              <p class="text-sm font-bold text-gray-900" id="receipt-phase-name">-</p>
            </div>
            <div>
              <p class="text-xs text-gray-500 mb-1">Status</p>
              <span id="receipt-phase-status" class="inline-block px-3 py-1 text-xs font-semibold rounded-full">-</span>
            </div>
            <div>
              <p class="text-xs text-gray-500 mb-1">Date Range</p>
              <p class="text-sm font-medium text-gray-700" id="receipt-date-range">-</p>
            </div>
            <div>
              <p class="text-xs text-gray-500 mb-1">Project</p>
              <p class="text-sm font-medium text-gray-700" id="receipt-project-name"><?php echo htmlspecialchars($project_name); ?></p>
            </div>
          </div>

          <!-- Summary Cards -->
          <div class="grid grid-cols-3 gap-4 p-6 border-b border-gray-200">
            <div class="bg-green-50 border border-green-200 rounded-lg p-4 text-center">
              <p class="text-xs text-green-600 font-medium mb-1">ALLOCATED</p>
              <p class="text-2xl font-bold text-green-700" id="receipt-allocated">₱0.00</p>
            </div>
            <div class="border rounded-lg p-4 text-center receipt-spent-card">
              <p class="text-xs font-medium mb-1">SPENT</p>
              <p class="text-2xl font-bold" id="receipt-spent">₱0.00</p>
              <p class="text-xs text-gray-600 mt-1" id="receipt-utilization">0%</p>
            </div>
            <div class="bg-purple-50 border border-purple-200 rounded-lg p-4 text-center">
              <p class="text-xs text-purple-600 font-medium mb-1">REMAINING</p>
              <p class="text-2xl font-bold text-purple-700" id="receipt-remaining">₱0.00</p>
            </div>
          </div>

          <!-- Utilization Bar -->
          <div class="px-6 py-4 border-b border-gray-200">
            <div class="flex items-center justify-between mb-2">
              <span class="text-sm font-semibold text-gray-700">Budget Utilization</span>
              <span class="text-sm font-bold text-gray-900" id="receipt-util-percent">0%</span>
            </div>
            <div class="w-full bg-gray-200 rounded-full h-4 overflow-hidden">
              <div id="receipt-progress-bar" class="h-4 rounded-full transition-all duration-300" style="width: 0%"></div>
            </div>
          </div>

          <!-- Approved Budget Proposals -->
          <div class="p-6 border-b border-gray-200">
            <h3 class="text-lg font-bold text-gray-900 mb-4 flex items-center gap-2">
              <i data-lucide="file-text" class="w-5 h-5"></i>
              Approved Budget Proposals
            </h3>
            <div id="receipt-budget-proposals" class="space-y-2">
              <!-- Dynamic budget proposals will be inserted here -->
              <div class="text-center py-8 text-gray-500">
                <p>Loading budget proposals...</p>
              </div>
            </div>
          </div>

          <!-- Line Items Breakdown -->
          <div class="p-6">
            <h3 class="text-lg font-bold text-gray-900 mb-4 flex items-center gap-2">
              <i data-lucide="list" class="w-5 h-5"></i>
              Expense Line Items
            </h3>
            <div id="receipt-line-items" class="space-y-2">
              <!-- Dynamic line items will be inserted here -->
              <div class="text-center py-8 text-gray-500">
                <p>No expenses recorded for this phase yet.</p>
              </div>
            </div>
          </div>

          <!-- Grand Total Section -->
          <div class="border-t-2 border-dashed border-gray-300 phase-total-bg p-6">
            <div class="flex items-center justify-between">
              <div>
                <p class="text-sm text-white font-bold mb-1">Phase Budget Total</p>
                <p class="text-xs text-white font-semibold">All approved expenses</p>
              </div>
              <p class="text-4xl font-bold text-white" id="receipt-grand-total">₱0.00</p>
            </div>
          </div>
        </div>
      </div>

      <!-- Modal Footer -->
      <div class="px-6 py-4 bg-gray-50 border-t border-gray-200 flex items-center justify-between">
        <button type="button" onclick="printPhaseDetails()" class="flex items-center gap-2 px-4 py-2 bg-white border border-gray-300 text-gray-700 hover:bg-gray-50 rounded-lg text-sm font-medium transition-colors">
          <i data-lucide="printer" class="w-4 h-4"></i>
          Print
        </button>
        <div class="flex gap-2">
          <button type="button" onclick="downloadPhasePDF()" class="flex items-center gap-2 px-4 py-2 bg-[#e9922c] hover:bg-[#d17f1f] text-white rounded-lg text-sm font-medium transition-colors">
            <i data-lucide="download" class="w-4 h-4"></i>
            Download PDF
          </button>
          <button type="button" id="close-phase-modal-btn-2" class="px-4 py-2 bg-gray-200 hover:bg-gray-300 text-gray-700 rounded-lg text-sm font-medium transition-colors">
            Close
          </button>
        </div>
      </div>
    </div>
  </div>

  <script>
    // Initialize Lucide icons
    lucide.createIcons();

    // ============================================
    // PHASE DETAIL MODAL FUNCTIONALITY
    // ============================================

    const phaseModal = document.getElementById('phase-detail-modal');
    const closePhaseModalBtn = document.getElementById('close-phase-modal-btn');
    const closePhaseModalBtn2 = document.getElementById('close-phase-modal-btn-2');
    
    // Phase data storage
    let currentPhaseData = null;

    // Phase colors mapping
    const phaseColors = {
      'Phase 1: Mobilization': { color: 'blue', bg: 'bg-blue-500', border: 'border-blue-500' },
      'Phase 2: Structural': { color: 'purple', bg: 'bg-purple-500', border: 'border-purple-500' },
      'Phase 3: MEPFS': { color: 'orange', bg: 'bg-orange-500', border: 'border-orange-500' },
      'Phase 4: Finishing': { color: 'green', bg: 'bg-green-500', border: 'border-green-500' }
    };

    // Open phase detail modal
    function openPhaseModal(phaseName) {
      <?php if ($selected_project_id > 0): ?>
      const phaseData = <?php echo json_encode($phases_data); ?>;
      
      if (!phaseData[phaseName]) {
        showToast('Phase data not found', 'error');
        return;
      }

      currentPhaseData = phaseData[phaseName];
      const colors = phaseColors[phaseName];

      // Update modal styling
      document.querySelectorAll('.phase-modal-border').forEach(el => {
        el.className = 'absolute top-0 left-0 right-0 h-1 phase-modal-border ' + colors.bg;
      });
      document.querySelectorAll('.phase-border').forEach(el => {
        el.className = 'border-t-4 phase-border ' + colors.border;
      });
      document.querySelectorAll('.phase-total-bg').forEach(el => {
        el.className = 'border-t-2 border-dashed border-gray-300 phase-total-bg p-6 ' + colors.bg;
      });

      // Update receipt content
      document.getElementById('receipt-phase-name').textContent = phaseName;
      document.getElementById('receipt-date-range').textContent = currentPhaseData.date_range;
      document.getElementById('receipt-project-name').textContent = '<?php echo addslashes($project_name); ?>';

      // Status badge
      const statusBadge = document.getElementById('receipt-phase-status');
      const statusColors = {
        'Completed': 'bg-green-100 text-green-700 border-green-300',
        'Active': 'bg-blue-100 text-blue-700 border-blue-300',
        'Upcoming': 'bg-gray-100 text-gray-600 border-gray-300',
        'Over Budget': 'bg-red-100 text-red-700 border-red-300'
      };
      statusBadge.className = 'inline-block px-3 py-1 text-xs font-semibold rounded-full border ' + statusColors[currentPhaseData.status];
      statusBadge.textContent = currentPhaseData.status;

      // Financial summary - Display full amounts without M suffix
      document.getElementById('receipt-allocated').textContent = '₱' + currentPhaseData.allocated.toLocaleString('en-PH', {minimumFractionDigits: 2, maximumFractionDigits: 2});
      document.getElementById('receipt-spent').textContent = '₱' + currentPhaseData.spent.toLocaleString('en-PH', {minimumFractionDigits: 2, maximumFractionDigits: 2});
      document.getElementById('receipt-remaining').textContent = '₱' + currentPhaseData.remaining.toLocaleString('en-PH', {minimumFractionDigits: 2, maximumFractionDigits: 2});
      document.getElementById('receipt-utilization').textContent = currentPhaseData.utilization.toFixed(1) + '% utilized';
      document.getElementById('receipt-util-percent').textContent = currentPhaseData.utilization.toFixed(1) + '%';
      document.getElementById('receipt-grand-total').textContent = '₱' + currentPhaseData.spent.toLocaleString('en-PH', {minimumFractionDigits: 2, maximumFractionDigits: 2});

      // Update spent card color
      const spentCard = document.querySelector('.receipt-spent-card');
      if (currentPhaseData.utilization > 100) {
        spentCard.className = 'bg-red-50 border border-red-200 rounded-lg p-4 text-center receipt-spent-card';
        spentCard.querySelector('p:first-child').className = 'text-xs text-red-600 font-medium mb-1';
        spentCard.querySelector('p:nth-child(2)').className = 'text-2xl font-bold text-red-700';
      } else {
        spentCard.className = 'bg-blue-50 border border-blue-200 rounded-lg p-4 text-center receipt-spent-card';
        spentCard.querySelector('p:first-child').className = 'text-xs text-blue-600 font-medium mb-1';
        spentCard.querySelector('p:nth-child(2)').className = 'text-2xl font-bold text-blue-700';
      }

      // Progress bar
      const progressBar = document.getElementById('receipt-progress-bar');
      let progressColor = 'bg-green-500';
      if (currentPhaseData.utilization > 100) {
        progressColor = 'bg-red-500';
      } else if (currentPhaseData.utilization >= 90) {
        progressColor = 'bg-orange-500';
      } else if (currentPhaseData.utilization >= 70) {
        progressColor = 'bg-yellow-500';
      }
      progressBar.className = 'h-4 rounded-full transition-all duration-300 ' + progressColor;
      progressBar.style.width = Math.min(currentPhaseData.utilization, 100) + '%';

      // Fetch budget proposals and line items
      fetchPhaseBudgetProposals(phaseName);
      fetchPhaseLineItems(phaseName);

      // Show modal
      phaseModal.classList.remove('hidden');
      document.body.classList.add('modal-open');
      lucide.createIcons();
      <?php else: ?>
      showToast('Please select a project first', 'error');
      <?php endif; ?>
    }

    async function fetchPhaseBudgetProposals(phaseName) {
      const proposalsContainer = document.getElementById('receipt-budget-proposals');
      proposalsContainer.innerHTML = '<div class="text-center py-4"><i data-lucide="loader-2" class="w-6 h-6 animate-spin mx-auto text-gray-400"></i></div>';
      lucide.createIcons();

      try {
        const response = await fetch(`budget_expenses/get_phase_budget_proposals.php?project_id=<?php echo $selected_project_id; ?>&phase=${encodeURIComponent(phaseName)}`);
        const result = await response.json();

        if (result.success && result.proposals.length > 0) {
          const proposals = result.proposals;
          let html = '';

          proposals.forEach(proposal => {
            const statusColors = {
              'APPROVED': 'bg-green-100 text-green-700 border-green-300'
            };
            const badgeClass = statusColors[proposal.status] || 'bg-gray-100 text-gray-700 border-gray-300';
            const formattedDate = new Date(proposal.created_at).toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' });

            html += `
              <div class="border border-gray-200 rounded-lg p-4 hover:border-${phaseColors[phaseName].color}-400 hover:shadow-md transition-all duration-200">
                <div class="flex items-start justify-between mb-3">
                  <div class="flex-1">
                    <div class="flex items-center gap-2 mb-2">
                      <span class="${badgeClass} px-2 py-1 rounded border text-xs font-semibold">${proposal.status}</span>
                      <span class="text-xs text-gray-500">${formattedDate}</span>
                    </div>
                    <div class="flex items-center gap-2 mb-1">
                      <span class="text-xs font-mono text-gray-600 bg-gray-100 px-2 py-1 rounded">${proposal.code}</span>
                      <h4 class="text-sm font-bold text-gray-900">${proposal.title}</h4>
                    </div>
                    <p class="text-xs text-gray-600 mt-1">${proposal.description}</p>
                    <div class="flex items-center gap-4 mt-2 text-xs text-gray-500">
                      <span><i data-lucide="calendar" class="w-3 h-3 inline"></i> ${proposal.phase_start_date} to ${proposal.phase_end_date}</span>
                      <span><i data-lucide="layers" class="w-3 h-3 inline"></i> ${proposal.line_item_count} line items</span>
                      <span><i data-lucide="user" class="w-3 h-3 inline"></i> ${proposal.user_name}</span>
                    </div>
                  </div>
                  <div class="text-right ml-4">
                    <p class="text-lg font-bold text-gray-900">₱${parseFloat(proposal.total_amount).toLocaleString('en-PH', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</p>
                    <span class="text-xs text-gray-500">Total Budget</span>
                  </div>
                </div>
              </div>
            `;
          });

          proposalsContainer.innerHTML = html;
        } else {
          proposalsContainer.innerHTML = '<div class="text-center py-8 text-gray-500"><p>No approved budget proposals for this phase yet.</p></div>';
        }

        lucide.createIcons();
      } catch (error) {
        console.error('Error fetching phase budget proposals:', error);
        proposalsContainer.innerHTML = '<div class="text-center py-8 text-red-500"><p>Failed to load budget proposals. Please try again.</p></div>';
      }
    }

    async function fetchPhaseLineItems(phaseName) {
      const lineItemsContainer = document.getElementById('receipt-line-items');
      lineItemsContainer.innerHTML = '<div class="text-center py-4"><i data-lucide="loader-2" class="w-6 h-6 animate-spin mx-auto text-gray-400"></i></div>';
      lucide.createIcons();

      try {
        const response = await fetch(`budget_expenses/get_phase_expenses.php?project_id=<?php echo $selected_project_id; ?>&phase=${encodeURIComponent(phaseName)}`);
        const result = await response.json();

        if (result.success && result.expenses.length > 0) {
          const expenses = result.expenses;
          let html = '';

          expenses.forEach(expense => {
            const categoryColors = {
              'MATERIALS': 'bg-purple-100 text-purple-700 border-purple-300',
              'LABOR': 'bg-amber-100 text-amber-700 border-amber-300',
              'EQUIPMENT': 'bg-green-100 text-green-700 border-green-300'
            };
            const badgeClass = categoryColors[expense.category] || 'bg-gray-100 text-gray-700 border-gray-300';

            html += `
              <div class="border border-gray-200 rounded-lg p-4 hover:border-${phaseColors[phaseName].color}-400 hover:shadow-md transition-all duration-200">
                <div class="flex items-start justify-between mb-2">
                  <div class="flex-1">
                    <div class="flex items-center gap-2 mb-1">
                      <span class="${badgeClass} px-2 py-1 rounded border text-xs font-semibold">${expense.category}</span>
                      <span class="text-xs text-gray-500">${new Date(expense.expense_date).toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' })}</span>
                    </div>
                    <p class="text-sm font-medium text-gray-900">${expense.description}</p>
                    <p class="text-xs text-gray-600 mt-1">Supplier: ${expense.supplier_name || 'N/A'}</p>
                  </div>
                  <div class="text-right ml-4">
                    <p class="text-lg font-bold text-gray-900">₱${parseFloat(expense.amount).toLocaleString('en-PH', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</p>
                    <span class="text-xs ${expense.status === 'APPROVED' ? 'text-green-600' : 'text-amber-600'}">${expense.status}</span>
                  </div>
                </div>
              </div>
            `;
          });

          lineItemsContainer.innerHTML = html;
        } else {
          lineItemsContainer.innerHTML = '<div class="text-center py-8 text-gray-500"><p>No expenses recorded for this phase yet.</p></div>';
        }

        lucide.createIcons();
      } catch (error) {
        console.error('Error fetching phase expenses:', error);
        lineItemsContainer.innerHTML = '<div class="text-center py-8 text-red-500"><p>Failed to load expenses. Please try again.</p></div>';
      }
    }

    function closePhaseModal() {
      phaseModal.classList.add('hidden');
      document.body.classList.remove('modal-open');
      currentPhaseData = null;
    }

    // Event listeners
    closePhaseModalBtn.addEventListener('click', closePhaseModal);
    closePhaseModalBtn2.addEventListener('click', closePhaseModal);
    phaseModal.addEventListener('click', (e) => {
      if (e.target === phaseModal) {
        closePhaseModal();
      }
    });

    // Phase card click handlers
    document.querySelectorAll('.phase-card').forEach(card => {
      card.addEventListener('click', function() {
        const phaseName = this.dataset.phase;
        openPhaseModal(phaseName);
      });
    });

    // Print function
    function printPhaseDetails() {
      window.print();
    }

    // Download PDF function
    function downloadPhasePDF() {
      if (!currentPhaseData) return;
      
      // Open PDF generator in new window/tab
      const pdfUrl = `download_phase_pdf.php?project_id=<?php echo $selected_project_id; ?>&phase=${encodeURIComponent(currentPhaseData.phase)}`;
      window.open(pdfUrl, '_blank');
    }

    // Add print styles
    const printStyles = document.createElement('style');
    printStyles.textContent = `
      @media print {
        body * { visibility: hidden; }
        #receipt-content, #receipt-content * { visibility: visible; }
        #receipt-content { position: absolute; left: 0; top: 0; width: 100%; }
        .no-print { display: none !important; }
      }
    `;
    document.head.appendChild(printStyles);

    // Prevent background scroll when modal is open
    const style = document.createElement('style');
    style.textContent = `
      body.modal-open {
        overflow: hidden;
      }
    `;
    document.head.appendChild(style);
  </script>
</body>
</html>
