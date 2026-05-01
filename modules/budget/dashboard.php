<?php 
  // 1. Connection & Context - using centralized config
  include __DIR__ . '/project_context.php';
  require_once __DIR__ . '/../../core/ApiHelper.php';
  
  $conn = getBudgetConnection();
  
  // Get selected project ID
  $selected_project_id = getProjectContext($conn);
  
  // Fetch all projects for dropdown from Project Service
  $projectRes = ApiHelper::get('project/projects');
  $projects = $projectRes['data']['projects'] ?? [];

  // Build breadcrumb navigation with dropdown
  $current_page = basename($_SERVER['PHP_SELF']);
  $breadcrumbHTML = '<div class="flex items-center gap-2 text-sm">';
  
  // Project Dropdown
  $breadcrumbHTML .= '<div class="relative inline-block">';
  $breadcrumbHTML .= '<select id="projectSelector" onchange="window.location.href=\'' . $current_page . '?project_id=\' + this.value" class="appearance-none bg-white border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-2 focus:ring-[#e9922c] focus:border-[#e9922c] pl-3 pr-8 py-1.5 hover:bg-gray-50 transition-colors cursor-pointer font-medium">';
  
  foreach ($projects as $proj) {
    $selected = ($proj['project_id'] == $selected_project_id) ? 'selected' : '';
    $breadcrumbHTML .= '<option value="' . $proj['project_id'] . '" ' . $selected . '>' . htmlspecialchars($proj['project_name']) . '</option>';
  }
  
  $breadcrumbHTML .= '</select>';
  $breadcrumbHTML .= '<svg class="w-3 h-3 text-gray-500 absolute right-2 top-1/2 transform -translate-y-1/2 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>';
  $breadcrumbHTML .= '</div>';
  $breadcrumbHTML .= '</div>';

  // 4. Set Header Variables
  $pageSection = "Budget & Cost Control";
  $pageTitle = "Budget Dashboard";
  $pageSubTitle = $breadcrumbHTML; // Pass the HTML dropdown here
  
  // 5. Budget Calculation Logic
  $project_name = 'No Project Selected';
  $total_budget = 0;
  $actual_spending = 0;
  $remaining_budget = 0;
  $budget_utilization = 0;
  $alert_type = 'good';
  $alert_message = '';

  if ($selected_project_id > 0) {
    // Fetch project summary from Budget Service
    $summaryRes = ApiHelper::get('budget/summary?project_id=' . $selected_project_id);
    $projectSummary = $summaryRes['data'] ?? [];

    if (!empty($projectSummary)) {
      $project_name = $projectSummary['project_name'] ?? 'N/A';
      $total_budget = floatval($projectSummary['total_budget'] ?? 0);
      $actual_spending = floatval($projectSummary['actual_spending'] ?? 0);
      $remaining_budget = $total_budget - $actual_spending;
      $budget_utilization = $total_budget > 0 ? ($actual_spending / $total_budget) * 100 : 0;

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

    // Fetch phases data from Budget Service
    $phaseRes = ApiHelper::get('budget/phases?project_id=' . $selected_project_id);
    $phases_data = $phaseRes['data']['phases'] ?? [];

    $active_phases_count = 0;
    foreach ($phases_data as $phase) {
      if ($phase['status'] === 'Active' || $phase['status'] === 'Over Budget') $active_phases_count++;
    }

    // Check for approved proposals
    $proposalsCheckRes = ApiHelper::get('budget/proposals?project_id=' . $selected_project_id . '&status=APPROVED&count_only=true');
    $has_approved_proposals = ($proposalsCheckRes['data']['count'] ?? 0) > 0;
  } else {
    $phases_data = [];
    $active_phases_count = 0;
    $has_approved_proposals = false;
  }
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Budget Dashboard | ICMIS</title>
  <script src="https://unpkg.com/lucide@latest"></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link rel="stylesheet" href="./css/output.css">
  <link rel="stylesheet" href="./css/input.css">

  <?php include '../../includes/head_assetsv2.php'; ?>


  <style>
    * {
      font-family: 'Inter', sans-serif;
    }
  </style>
</head>
<body class="bg-gray-50">

  <?php 
  // FIX: Go up two levels to find 'includes'
  // Current: modules/budget/dashboard.php
  // Target:  includes/sidebar.php
  include __DIR__ . '/../../includes/sidebar.php'; 
  ?>

  <?php 
  // FIX: Go up two levels to find 'includes'
  include __DIR__ . '/../../includes/header.php'; 
  ?>

  <main class="ml-56 pt-26 p-6 transition-all duration-300 animate-fade-in">
    <div class="max-w-7xl mx-auto">
      <?php if ($has_approved_proposals): ?>
      
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

      <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
        <div class="bg-white rounded-xl shadow-sm p-6 border-2 border-[#e9922c] hover:shadow-lg transition-shadow duration-300">
          <div class="flex items-center justify-center w-12 h-12 bg-linear-to-br from-[#e9922c] to-[#d17f1f] rounded-lg mb-4">
            <i data-lucide="wallet" class="w-6 h-6 text-white"></i>
          </div>
          <p class="text-gray-600 text-sm mb-1">Total Budget Allocated</p>
          <p class="text-gray-900 text-3xl font-black mb-1">₱<?php echo number_format($total_budget, 2); ?></p>
        </div>

        <div class="bg-white rounded-xl shadow-sm p-6 border-2 <?php echo $budget_utilization > 90 ? 'border-red-500' : 'border-green-500'; ?> hover:shadow-lg transition-shadow duration-300">
          <div class="flex items-center justify-center w-12 h-12 bg-linear-to-br <?php echo $budget_utilization > 90 ? 'from-red-500 to-red-600' : 'from-green-500 to-green-600'; ?> rounded-lg mb-4">
            <i data-lucide="trending-down" class="w-6 h-6 text-white"></i>
          </div>
          <p class="text-gray-600 text-sm mb-1">Total Spent</p>
          <p class="text-gray-900 text-3xl font-black mb-1">₱<?php echo number_format($actual_spending, 2); ?></p>
          <p class="text-gray-500 text-xs"><?php echo number_format($budget_utilization, 1); ?>% utilized</p>
        </div>

        <div class="bg-white rounded-xl shadow-sm p-6 border-2 border-blue-500 hover:shadow-lg transition-shadow duration-300">
          <div class="flex items-center justify-center w-12 h-12 bg-linear-to-br from-blue-500 to-blue-600 rounded-lg mb-4">
            <i data-lucide="piggy-bank" class="w-6 h-6 text-white"></i>
          </div>
          <p class="text-gray-600 text-sm mb-1">Remaining Budget</p>
          <p class="text-gray-900 text-3xl font-black mb-1">₱<?php echo number_format($remaining_budget, 2); ?></p>
          <p class="text-gray-500 text-xs"><?php echo number_format(100 - $budget_utilization, 1); ?>% available</p>
        </div>

        <div class="bg-white rounded-xl shadow-sm p-6 border-2 border-purple-500 hover:shadow-lg transition-shadow duration-300">
          <div class="flex items-center justify-center w-12 h-12 bg-linear-to-br from-purple-500 to-purple-600 rounded-lg mb-4">
            <i data-lucide="layers" class="w-6 h-6 text-white"></i>
          </div>
          <p class="text-gray-600 text-sm mb-1">Active Phases</p>
          <p class="text-gray-900 text-3xl font-black mb-1"><?php echo $active_phases_count; ?>/4</p>
          <p class="text-gray-500 text-xs">Currently ongoing</p>
        </div>
      </div>

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
        <div class="bg-white border-2 border-<?php echo $color; ?>-500 rounded-xl shadow-sm hover:shadow-xl hover:-translate-y-1 transition-all duration-300 overflow-hidden cursor-pointer phase-card" data-phase="<?php echo htmlspecialchars($phase_name); ?>">
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

          <div class="p-5">
            <div class="flex items-center gap-2 text-gray-600 text-sm mb-4">
              <i data-lucide="calendar" class="w-4 h-4"></i>
              <span><?php echo $phase['date_range']; ?></span>
            </div>

            <div class="grid grid-cols-3 gap-3 mb-4">
              <div class="bg-gray-50 rounded-lg p-3 text-center">
                <p class="text-xs text-gray-500 mb-1">Budget</p>
                <p class="text-lg font-black text-gray-900">₱<?php echo number_format($phase['allocated'], 2); ?></p>
              </div>

              <div class="bg-gray-50 rounded-lg p-3 text-center">
                <p class="text-xs text-gray-500 mb-1">Spent</p>
                <p class="text-lg font-black text-<?php echo $color; ?>-600">₱<?php echo number_format($phase['spent'], 2); ?></p>
                <p class="text-xs text-gray-500"><?php echo number_format($phase['utilization'], 1); ?>%</p>
              </div>

              <div class="bg-gray-50 rounded-lg p-3 text-center">
                <p class="text-xs text-gray-500 mb-1">Remaining</p>
                <p class="text-lg font-black text-gray-900">₱<?php echo number_format($phase['remaining'], 2); ?></p>
                <p class="text-xs text-gray-500"><?php echo number_format(100 - $phase['utilization'], 1); ?>%</p>
              </div>
            </div>

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
          <div class="mt-8 pt-8 border-t border-gray-200">
              <p class="text-sm text-gray-600 mb-3 text-left">To view the budget dashboard:</p>
              
              <div class="space-y-3">
                <div class="flex gap-3 bg-gray-50 rounded-lg p-3 text-left">
                  <div class="flex-shrink-0 mt-1.5">
                    <div class="w-1.5 h-1.5 bg-[#e9922c] rounded-full"></div>
                  </div>
                  <div>
                    <div class="text-sm text-gray-900 font-medium">Navigate to Budget Proposals section</div>
                  </div>
                </div>
                <div class="flex gap-3 bg-gray-50 rounded-lg p-3 text-left">
                  <div class="flex-shrink-0 mt-1.5">
                    <div class="w-1.5 h-1.5 bg-[#e9922c] rounded-full"></div>
                  </div>
                  <div>
                    <div class="text-sm text-gray-900 font-medium">Create a new budget proposal for this project</div>
                  </div>
                </div>
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

  <div id="phase-detail-modal" class="hidden fixed inset-0 bg-black/60 backdrop-blur-sm z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-4xl max-h-[96vh] overflow-hidden">
      <div class="bg-linear-to-r from-slate-800 to-slate-700 px-6 py-5 relative">
        <div class="absolute top-0 left-0 right-0 h-1 phase-modal-border"></div>
        <div class="flex items-center justify-between">
          <div class="flex items-center gap-4">
              <div class="w-20 h-20 bg-white rounded-2xl flex items-center justify-center shadow-lg border border-gray-100 p-2">
                  <img src="/icmis/assets/images/nobg_logo.png" alt="ICMIS Logo" class="w-full h-full object-contain">
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

      <div class="overflow-y-auto max-h-[calc(90vh-160px)] p-6">
        <div id="receipt-content" class="bg-white rounded-xl border-t-4 phase-border shadow-lg">
          <div class="border-b-2 border-dashed border-gray-300 p-6 text-center">
            <div class="flex justify-center mb-3">
                <div class="w-20 h-20 bg-white rounded-2xl flex items-center justify-center shadow-lg border border-gray-100 p-2">
                  <img src="/icmis/assets/images/nobg_logo.png" alt="ICMIS Logo" class="w-full h-full object-contain">
                </div>
            </div>
            <h2 class="text-2xl font-bold text-gray-900">ICMIS</h2>
            <p class="text-sm text-gray-600">Integrated Construction Management Information System</p>
            <p class="text-xs text-gray-500 mt-1">Budget Proposal Document</p>
          </div>

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

          <div class="grid grid-cols-3 gap-4 p-6 border-b border-gray-200">
            <div class="bg-green-50 border border-green-200 rounded-lg p-4 text-center">
              <p class="text-xs text-green-600 font-medium mb-1">ALLOCATED</p>
              <p class="text-2xl font-black text-green-700" id="receipt-allocated">₱0.00</p>
            </div>
            <div class="border rounded-lg p-4 text-center receipt-spent-card">
              <p class="text-xs font-medium mb-1">SPENT</p>
              <p class="text-2xl font-black" id="receipt-spent">₱0.00</p>
              <p class="text-xs text-gray-600 mt-1" id="receipt-utilization">0%</p>
            </div>
            <div class="bg-purple-50 border border-purple-200 rounded-lg p-4 text-center">
              <p class="text-xs text-purple-600 font-medium mb-1">REMAINING</p>
              <p class="text-2xl font-black text-purple-700" id="receipt-remaining">₱0.00</p>
            </div>
          </div>

          <div class="px-6 py-4 border-b border-gray-200">
            <div class="flex items-center justify-between mb-2">
              <span class="text-sm font-semibold text-gray-700">Budget Utilization</span>
              <span class="text-sm font-black text-gray-900" id="receipt-util-percent">0%</span>
            </div>
            <div class="w-full bg-gray-200 rounded-full h-4 overflow-hidden">
              <div id="receipt-progress-bar" class="h-4 rounded-full transition-all duration-300" style="width: 0%"></div>
            </div>
          </div>

          <div class="p-6 border-b border-gray-200">
            <h3 class="text-lg font-bold text-gray-900 mb-4 flex items-center gap-2">
              <i data-lucide="file-text" class="w-5 h-5"></i>
              Approved Budget Proposals
            </h3>
            <div id="receipt-budget-proposals" class="space-y-2">
              <div class="text-center py-8 text-gray-500">
                <p>Loading budget proposals...</p>
              </div>
            </div>
          </div>

          <div class="p-6">
            <h3 class="text-lg font-bold text-gray-900 mb-4 flex items-center gap-2">
              <i data-lucide="list" class="w-5 h-5"></i>
              Expense Line Items
            </h3>
            <div id="receipt-line-items" class="space-y-2">
              <div class="text-center py-8 text-gray-500">
                <p>No expenses recorded for this phase yet.</p>
              </div>
            </div>
          </div>

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

      <div class="px-6 py-4 bg-gray-50 border-t border-gray-200 flex items-center justify-end">
        <div class="flex gap-2">
          <button type="button" onclick="downloadPhasePDF()" class="flex items-center gap-2 px-4 py-2 bg-[#e9922c] hover:bg-[#d17f1f] text-white rounded-lg text-sm font-medium transition-colors">
            <i data-lucide="printer" class="w-4 h-4"></i>
            Print
          </button>
          <button type="button" id="close-phase-modal-btn-2" class="px-4 py-2 bg-gray-200 hover:bg-gray-300 text-gray-700 rounded-lg text-sm font-medium transition-colors">
            Close
          </button>
        </div>
      </div>
    </div>
  </div>

  <script>
    // expose server-side data to external JS
    window.PHASES_DATA = <?php echo json_encode($phases_data); ?>;
    window.SELECTED_PROJECT_ID = <?php echo (int)$selected_project_id; ?>;
    window.PROJECT_NAME = <?php echo json_encode($project_name); ?>;
  </script>
  <script src="js/dashboard.js"></script>
</body>
</html>