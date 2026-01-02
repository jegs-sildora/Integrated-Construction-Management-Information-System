<?php
  require_once __DIR__ . '/../../config/config.php';
?>

<?php
    // reports.php
    include __DIR__ . '/connection.php';
    include __DIR__ . '/project_context.php';
    
    // Start session if not started
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    // 1. Get selected project ID from global context
    $selected_project_id = getProjectContext($conn);
    
    // 2. Fetch Current Project Name for Context Display
    $current_project_name = "No Project Selected";
    $current_project_code = "";
    
    if ($selected_project_id) {
        $stmt = $conn->prepare("SELECT name, project_code FROM projects WHERE project_id = ?");
        $stmt->bind_param("i", $selected_project_id);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($row = $res->fetch_assoc()) {
            $current_project_name = $row['name'];
            $current_project_code = $row['project_code'];
        }
        $stmt->close();
    }

    // 3. Fetch Real Reports from Database
    $recent_reports = [];
    $tableExists = $conn->query("SHOW TABLES LIKE 'generated_reports'");
    
    if ($tableExists && $tableExists->num_rows > 0 && $selected_project_id) {
        $report_sql = "SELECT * FROM generated_reports 
                       WHERE project_id = ? 
                       ORDER BY created_at DESC 
                       LIMIT 10";
        
        $stmt = $conn->prepare($report_sql);
        $stmt->bind_param("i", $selected_project_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        while ($row = $result->fetch_assoc()) {
            $recent_reports[] = $row;
        }
        $stmt->close();
    }

    $userName = "John Doe";
    $userRole = "Financial Manager";
    $notificationCount = 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <!-- Global project styles -->
    <link rel="stylesheet" href="/icmis_budget/css/output.css">
    <link rel="stylesheet" href="/icmis_budget/css/input.css">
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Financial Reports - ICMIS</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Arimo:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
  <script src="https://unpkg.com/lucide@latest"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.31/jspdf.plugin.autotable.min.js"></script>
  
  <style> body { font-family: 'Arimo', sans-serif; } </style>
</head>
<body class="bg-gray-50 text-slate-800">
  <?php include __DIR__ . '/../components/sidebar.php'; ?>

  <?php
    // Fetch all projects for dropdown
    $sql_projects = "SELECT project_id, project_code, name FROM projects ORDER BY created_at DESC";
    $result_projects = $conn->query($sql_projects);
    $projects = [];
    if ($result_projects && $result_projects->num_rows > 0) {
      while ($row = $result_projects->fetch_assoc()) {
        $projects[] = $row;
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

    $pageTitle = "Financial Reports";
    $pageSection = "Budget & Cost Control";
    $pageSubTitle = $breadcrumbHTML;
    
    // Header includes the toast container and logic automatically
    include __DIR__ . '/../components/header.php';
  ?>

  <main class="ml-56 mt-20 p-6">
    <div class="max-w-7xl mx-auto">
      
      <div class="border-b border-gray-200 pb-6 mb-8">
        <div class="flex items-center justify-between">
          <div>
            <h1 class="text-2xl text-gray-900 font-bold">Financial Reports</h1>
            <p class="text-sm text-gray-500 mt-1">Generate and access phase-based financial documentation for <span class="font-semibold text-[#e9922c]"><?php echo htmlspecialchars($current_project_name); ?></span></p>
          </div>
        </div>
      </div>

      <div class="mb-10">
        <h2 class="text-xl font-bold text-gray-900 mb-6">Quick Generate Templates</h2>
        
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
          <div class="bg-white border-2 border-gray-200 rounded-xl p-6 hover:border-blue-300 hover:shadow-lg transition-all duration-200 report-template-card flex flex-col justify-between" data-template="budget-summary">
            <div>
                <div class="flex items-center justify-center w-12 h-12 bg-blue-100 rounded-xl mb-4 text-blue-600"><i data-lucide="bar-chart-3" class="w-6 h-6"></i></div>
                <h3 class="text-lg font-bold text-gray-900 mb-1">Budget Summary</h3>
                <p class="text-sm text-gray-600 mb-4">Project Health Check. Total Limits vs. Actuals.</p>
            </div>
            <button onclick="generateReport('budget-summary')" class="generate-btn w-full flex items-center justify-center gap-2 px-4 py-2 bg-white border-2 border-blue-600 text-blue-700 hover:bg-blue-50 font-semibold rounded-lg transition-colors">
              <i data-lucide="download" class="w-4 h-4"></i> Generate
            </button>
          </div>

          <div class="bg-white border-2 border-gray-200 rounded-xl p-6 hover:border-orange-300 hover:shadow-lg transition-all duration-200 report-template-card flex flex-col justify-between" data-template="phase-analysis">
            <div>
                <div class="flex items-center justify-center w-12 h-12 bg-orange-100 rounded-xl mb-4 text-orange-600"><i data-lucide="trending-down" class="w-6 h-6"></i></div>
                <h3 class="text-lg font-bold text-gray-900 mb-1">Phase Variance</h3>
                <p class="text-sm text-gray-600 mb-4">Approved Proposals vs. Expenses per phase.</p>
                <div class="mb-4">
                    <select class="phase-selector w-full text-xs bg-gray-50 border border-gray-200 px-2 py-1.5 rounded focus:border-orange-500 outline-none">
                        <option value="">Select Phase...</option>
                        <option value="1">Phase 1: Mobilization</option>
                        <option value="2">Phase 2: Structural</option>
                        <option value="3">Phase 3: MEPFS</option>
                        <option value="4">Phase 4: Finishing</option>
                    </select>
                </div>
            </div>
            <button onclick="generateReport('phase-analysis')" disabled class="generate-btn w-full flex items-center justify-center gap-2 px-4 py-2 bg-white border-2 border-orange-500 text-orange-600 hover:bg-orange-50 font-semibold rounded-lg transition-colors disabled:opacity-50 disabled:cursor-not-allowed">
              <i data-lucide="download" class="w-4 h-4"></i> Generate
            </button>
          </div>

          <div class="bg-white border-2 border-gray-200 rounded-xl p-6 hover:border-purple-300 hover:shadow-lg transition-all duration-200 report-template-card flex flex-col justify-between" data-template="labor-analysis">
            <div>
                <div class="flex items-center justify-center w-12 h-12 bg-purple-100 rounded-xl mb-4 text-purple-600"><i data-lucide="hard-hat" class="w-6 h-6"></i></div>
                <h3 class="text-lg font-bold text-gray-900 mb-1">Labor Analysis</h3>
                <p class="text-sm text-gray-600 mb-4">Workforce spending and payroll impact.</p>
            </div>
            <button onclick="generateReport('labor-analysis')" class="generate-btn w-full flex items-center justify-center gap-2 px-4 py-2 bg-white border-2 border-purple-600 text-purple-700 hover:bg-purple-50 font-semibold rounded-lg transition-colors">
              <i data-lucide="download" class="w-4 h-4"></i> Generate
            </button>
          </div>

          <div class="bg-white border-2 border-gray-200 rounded-xl p-6 hover:border-green-300 hover:shadow-lg transition-all duration-200 report-template-card flex flex-col justify-between" data-template="expense-log">
            <div>
                <div class="flex items-center justify-center w-12 h-12 bg-green-100 rounded-xl mb-4 text-green-600"><i data-lucide="receipt" class="w-6 h-6"></i></div>
                <h3 class="text-lg font-bold text-gray-900 mb-1">Expense Log</h3>
                <p class="text-sm text-gray-600 mb-4">Detailed supplier & material audit trail.</p>
            </div>
            <button onclick="generateReport('expense-log')" class="generate-btn w-full flex items-center justify-center gap-2 px-4 py-2 bg-white border-2 border-green-600 text-green-700 hover:bg-green-50 font-semibold rounded-lg transition-colors">
              <i data-lucide="download" class="w-4 h-4"></i> Generate
            </button>
          </div>

          <div class="bg-white border-2 border-gray-200 rounded-xl p-6 hover:border-teal-300 hover:shadow-lg transition-all duration-200 report-template-card flex flex-col justify-between" data-template="cash-flow">
            <div>
                <div class="flex items-center justify-center w-12 h-12 bg-teal-100 rounded-xl mb-4 text-teal-600"><i data-lucide="calendar" class="w-6 h-6"></i></div>
                <h3 class="text-lg font-bold text-gray-900 mb-1">Cash Flow</h3>
                <p class="text-sm text-gray-600 mb-4">Monthly burn rate and liquidity analysis.</p>
            </div>
            <button onclick="generateReport('cash-flow')" class="generate-btn w-full flex items-center justify-center gap-2 px-4 py-2 bg-white border-2 border-teal-600 text-teal-700 hover:bg-teal-50 font-semibold rounded-lg transition-colors">
              <i data-lucide="download" class="w-4 h-4"></i> Generate
            </button>
          </div>
        </div>
      </div>

      <div class="bg-white border border-gray-200 rounded-xl overflow-hidden shadow-sm">
          <div class="px-6 py-4 border-b border-gray-100 flex justify-between items-center bg-gray-50">
              <div class="flex items-center gap-3">
                  <h3 class="font-bold text-gray-800">Recent Reports</h3>
                  <?php if($selected_project_id): ?>
                  <span class="text-xs font-medium text-gray-500 bg-white border border-gray-200 px-2 py-0.5 rounded-full">
                      Filtered by: <?php echo htmlspecialchars($current_project_name); ?>
                  </span>
                  <?php endif; ?>
              </div>
              <button onclick="location.reload()" class="text-sm text-gray-500 hover:text-[#e9922c] font-medium transition-colors flex items-center gap-1">
                <i data-lucide="refresh-cw" class="w-3 h-3"></i> Refresh List
              </button>
          </div>
          <div class="overflow-x-auto">
              <table class="w-full text-left border-collapse">
                  <thead class="bg-gray-50 text-xs uppercase text-gray-500 font-semibold">
                      <tr>
                          <th class="px-6 py-3">Report Name</th>
                          <th class="px-6 py-3">Type</th>
                          <th class="px-6 py-3">Context</th>
                          <th class="px-6 py-3">Date Generated</th>
                          <th class="px-6 py-3 text-right">Actions</th>
                      </tr>
                  </thead>
                  <tbody class="divide-y divide-gray-100 text-sm">
                      <?php if(empty($recent_reports)): ?>
                        <tr>
                            <td colspan="5" class="px-6 py-8 text-center text-gray-500 bg-white">
                                <div class="flex flex-col items-center">
                                    <i data-lucide="folder-open" class="w-8 h-8 text-gray-300 mb-2"></i>
                                    <p>No reports found for this project.</p>
                                    <p class="text-xs text-gray-400 mt-1">Generate a template above to see it here.</p>
                                </div>
                            </td>
                        </tr>
                      <?php else: ?>
                        <?php foreach($recent_reports as $report): ?>
                        <tr class="hover:bg-gray-50 transition">
                            <td class="px-6 py-4 font-medium text-gray-700 flex items-center">
                                <i data-lucide="file-text" class="w-4 h-4 text-gray-400 mr-2"></i>
                                <?php echo htmlspecialchars($report['report_name'] ?? 'Untitled Report'); ?>
                            </td>
                            <td class="px-6 py-4">
                                <?php 
                                    $colors = [
                                        'budget-summary' => 'blue',
                                        'phase-analysis' => 'orange',
                                        'labor-analysis' => 'purple',
                                        'expense-log' => 'green',
                                        'cash-flow' => 'teal'
                                    ];
                                    $type = $report['report_type'] ?? 'default';
                                    $color = $colors[$type] ?? 'gray';
                                ?>
                                <span class="px-2 py-1 rounded-full text-xs font-bold bg-<?php echo $color; ?>-100 text-<?php echo $color; ?>-700 capitalize">
                                    <?php echo str_replace('-', ' ', $type); ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 text-gray-500"><?php echo htmlspecialchars($report['context'] ?? $current_project_name); ?></td>
                            <td class="px-6 py-4 text-gray-500">
                                <?php echo htmlspecialchars(date('M d, Y h:i A', strtotime($report['created_at'] ?? 'now'))); ?>
                            </td>
                            <td class="px-6 py-4 text-right space-x-2">
                                <button onclick="generateReport('<?php echo $report['report_type']; ?>')" class="generate-btn p-2 text-gray-400 hover:text-[#e9922c] hover:bg-orange-50 rounded-full transition" title="Download Again">
                                    <i data-lucide="download" class="w-4 h-4"></i>
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                      <?php endif; ?>
                  </tbody>
              </table>
          </div>
      </div>
      
    </div>
  </main>

<script>
    // Initialize Icons
    lucide.createIcons();

    // ============================================
    // UI STATE MANAGEMENT (FIXED FOR SMALL BUTTONS)
    // ============================================
    
    function setLoadingState(button) {
        if(!button) return;
        
        // Save original state
        button.dataset.originalContent = button.innerHTML;
        button.dataset.originalClasses = button.className;
        button.disabled = true;

        // Check if it's the small icon-only button (via rounded-full class)
        const isSmallButton = button.classList.contains('rounded-full');

        if (isSmallButton) {
            // Logic for table button: spinner only, no text, no expansion
            button.classList.add('opacity-80', 'cursor-not-allowed', 'text-[#e9922c]');
            button.innerHTML = `<i data-lucide="loader-2" class="w-4 h-4 animate-spin"></i>`;
        } else {
            // Logic for large card button: standard loading state
            const width = button.offsetWidth;
            button.style.width = `${width}px`; // Lock width
            button.classList.add('opacity-80', 'cursor-not-allowed');
            button.innerHTML = `
                <div class="flex items-center justify-center gap-2">
                    <i data-lucide="loader-2" class="w-4 h-4 animate-spin"></i>
                    <span>Generating...</span>
                </div>
            `;
        }
        
        lucide.createIcons();
    }

    function resetLoadingState(button) {
        if(!button) return;
        button.disabled = false;
        button.style.width = '';
        button.className = button.dataset.originalClasses;
        button.innerHTML = button.dataset.originalContent;
        lucide.createIcons();
    }

    function showSuccessState(button) {
        if(!button) return;
        
        const isSmallButton = button.classList.contains('rounded-full');

        if (isSmallButton) {
            // Small button success: Green Checkmark only
            button.classList.remove('text-gray-400', 'hover:text-[#e9922c]', 'hover:bg-orange-50');
            button.classList.add('text-green-600', 'bg-green-50');
            button.innerHTML = `<i data-lucide="check" class="w-4 h-4"></i>`;
        } else {
            // Large button success: Full "Downloaded" bar
            button.className = "w-full flex items-center justify-center gap-2 px-4 py-2 bg-green-600 text-white font-semibold rounded-lg transition-all duration-300 shadow-sm";
            button.innerHTML = `
                <div class="flex items-center justify-center gap-2">
                    <i data-lucide="check-circle" class="w-4 h-4"></i>
                    <span>Downloaded</span>
                </div>
            `;
        }
        
        lucide.createIcons();

        // Use global showToast with persistence (persist = true)
        if (typeof showToast === 'function') {
            showToast('Report generated successfully!', 'success', true);
        }

        // Reload page to show new record in Recent Reports table
        setTimeout(() => {
            window.location.reload();
        }, 1500);
    }

    // ============================================
    // MAIN REPORT GENERATOR
    // ============================================

    async function generateReport(templateType) {
        const button = event.target.closest('.generate-btn');
        const card = button.closest('.report-template-card');
        const projectId = '<?php echo $selected_project_id; ?>';

        // Validation for Phase Analysis
        let selectedPhase = '';
        if (templateType === 'phase-analysis' && card) {
            const phaseSelector = card.querySelector('.phase-selector');
            if (!phaseSelector.value) {
                if (typeof showToast === 'function') {
                    showToast('Please select a phase first', 'error');
                } else {
                    alert('Please select a phase first');
                }
                return;
            }
            selectedPhase = phaseSelector.value;
        }

        if (!projectId) {
            if (typeof showToast === 'function') {
                showToast('Please select a project first', 'error');
            }
            return;
        }

        // Apply Professional Loading State
        setLoadingState(button);

        try {
            // 1. Fetch Data
            const formData = new FormData();
            formData.append('project_id', projectId);
            formData.append('report_type', templateType);
            if (selectedPhase) formData.append('phase', selectedPhase);

            const response = await fetch('budget_expenses/export_pdf.php', {
                method: 'POST',
                body: formData
            });

            // Check if response is valid JSON
            const contentType = response.headers.get("content-type");
            if (!contentType || !contentType.includes("application/json")) {
                const text = await response.text();
                throw new Error("Server returned an invalid format. Debug: " + text.substring(0, 100));
            }

            const result = await response.json();
            if (!result.success) throw new Error(result.message);

            // 2. Generate PDF
            const { jsPDF } = window.jspdf;
            const doc = new jsPDF();
            const data = result.data;

            switch (templateType) {
                case 'budget-summary':
                    generateBudgetSummaryPDF(doc, data);
                    break;
                case 'phase-analysis':
                    generatePhaseAnalysisPDF(doc, data);
                    break;
                case 'labor-analysis':
                    generateLaborAnalysisPDF(doc, data);
                    break;
                case 'expense-log':
                    generateExpenseLogPDF(doc, data);
                    break;
                case 'cash-flow':
                    generateCashFlowPDF(doc, data);
                    break;
            }

            // 3. Show Success Animation & Reload
            showSuccessState(button);

        } catch (error) {
            console.error('Generation Error:', error);
            if (typeof showToast === 'function') {
                showToast('Failed: ' + error.message, 'error');
            } else {
                alert('Failed: ' + error.message);
            }
            resetLoadingState(button); 
        }
    }

    // ============================================
    // PDF STYLING & GENERATORS (Consistent Style)
    // ============================================

    function addCommonHeader(doc, title, project) {
        const pageWidth = doc.internal.pageSize.getWidth();
        const currentDate = new Date().toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' });

        // Brand
        doc.setFillColor(233, 146, 44); // #e9922c
        doc.rect(pageWidth / 2 - 6, 15, 12, 12, 'F');
        doc.setTextColor(255, 255, 255);
        doc.setFontSize(16);
        doc.setFont('helvetica', 'bold');
        doc.text('I', pageWidth / 2, 24, { align: 'center' });

        // Title
        doc.setTextColor(0, 0, 0);
        doc.setFontSize(10);
        doc.setFont('helvetica', 'normal');
        doc.text('ICMIS - Integrated Construction Management Information System', pageWidth / 2, 32, { align: 'center' });
        doc.setFontSize(16);
        doc.setFont('helvetica', 'bold');
        doc.text(title.toUpperCase(), pageWidth / 2, 42, { align: 'center' });

        // Info Box
        doc.setFontSize(9);
        doc.setFont('helvetica', 'normal');
        doc.setDrawColor(200, 200, 200);
        doc.rect(14, 48, pageWidth - 28, 24);
        
        doc.setFont('helvetica', 'bold');
        doc.text('Project Name:', 18, 56);
        doc.text('Project Code:', 18, 64);
        doc.setFont('helvetica', 'normal');
        doc.text(project.name, 45, 56);
        doc.text(project.project_code || 'N/A', 45, 64);

        doc.setFont('helvetica', 'bold');
        doc.text('Date:', 120, 56);
        doc.text('User:', 120, 64);
        doc.setFont('helvetica', 'normal');
        doc.text(currentDate, 145, 56);
        doc.text('<?php echo $userName; ?>', 145, 64);
        
        return 80;
    }

    function addCommonFooter(doc) {
        const pageCount = doc.internal.getNumberOfPages();
        const pageWidth = doc.internal.pageSize.getWidth();
        const pageHeight = doc.internal.pageSize.getHeight();
        
        for (let i = 1; i <= pageCount; i++) {
            doc.setPage(i);
            const footerY = pageHeight - 35;

            doc.setTextColor(0, 0, 0);
            doc.setFontSize(9);
            
            // Signatures
            doc.text('Prepared By:', 18, footerY);
            doc.line(18, footerY + 8, 80, footerY + 8);
            doc.setFont('helvetica', 'italic');
            doc.setFontSize(8);
            doc.text('Budget Officer', 18, footerY + 13);

            doc.setFont('helvetica', 'normal');
            doc.setFontSize(9);
            doc.text('Approved By:', pageWidth - 80, footerY);
            doc.line(pageWidth - 80, footerY + 8, pageWidth - 18, footerY + 8);
            doc.setFont('helvetica', 'italic');
            doc.setFontSize(8);
            doc.text('Project Manager', pageWidth - 80, footerY + 13);

            // Page Number
            doc.setTextColor(150, 150, 150);
            doc.setFont('helvetica', 'normal');
            doc.text(`Page ${i} of ${pageCount}`, pageWidth / 2, pageHeight - 10, { align: 'center' });
        }
    }

    // --- PDF GENERATORS ---

    function generateBudgetSummaryPDF(doc, data) {
        let y = addCommonHeader(doc, 'Project Budget Summary', data.project);
        const p = data.project;
        const t = data.totals;
        const dateStr = new Date().toISOString().split('T')[0];

        doc.setFillColor(248, 250, 252);
        doc.setDrawColor(226, 232, 240);
        doc.rect(14, y, 182, 35, 'FD');
        
        doc.setFontSize(12);
        doc.setFont('helvetica', 'bold');
        doc.setTextColor(30, 41, 59);
        doc.text('Executive Summary', 18, y + 10);
        
        doc.setFontSize(10);
        doc.setFont('helvetica', 'normal');
        doc.text('Total Allocated Budget:', 18, y + 20);
        doc.text('Total Actual Spending:', 18, y + 28);
        doc.setFont('helvetica', 'bold');
        doc.text('PHP ' + parseFloat(p.total_budget).toLocaleString('en-PH', {minimumFractionDigits: 2}), 70, y + 20);
        doc.text('PHP ' + parseFloat(p.actual_spending).toLocaleString('en-PH', {minimumFractionDigits: 2}), 70, y + 28);

        const rows = [
            ['Materials', 'PHP ' + t.materials.toLocaleString('en-PH', {minimumFractionDigits: 2})],
            ['Labor', 'PHP ' + t.labor.toLocaleString('en-PH', {minimumFractionDigits: 2})],
            ['Equipment', 'PHP ' + t.equipment.toLocaleString('en-PH', {minimumFractionDigits: 2})],
            ['TOTAL', 'PHP ' + t.grand_total.toLocaleString('en-PH', {minimumFractionDigits: 2})]
        ];

        doc.autoTable({
            startY: y + 45,
            head: [['Category', 'Amount']],
            body: rows,
            theme: 'grid',
            headStyles: { fillColor: [233, 146, 44] },
            columnStyles: { 1: { halign: 'right', fontStyle: 'bold' } }
        });

        addCommonFooter(doc);
        doc.save(`Budget_Summary_${p.project_code}_${dateStr}.pdf`);
    }

    function generatePhaseAnalysisPDF(doc, data) {
        let y = addCommonHeader(doc, 'Phase Variance Analysis', data.project);
        const dateStr = new Date().toISOString().split('T')[0];
        
        const rows = data.phases.map(p => [
            p.phase_name,
            'PHP ' + p.budget.toLocaleString('en-PH', {minimumFractionDigits: 2}),
            'PHP ' + p.spent.toLocaleString('en-PH', {minimumFractionDigits: 2}),
            'PHP ' + p.variance.toLocaleString('en-PH', {minimumFractionDigits: 2}),
            p.utilization.toFixed(1) + '%'
        ]);

        doc.autoTable({
            startY: y,
            head: [['Phase Name', 'Budget', 'Actual Spent', 'Variance', 'Util.']],
            body: rows,
            theme: 'grid',
            headStyles: { fillColor: [233, 146, 44] },
            columnStyles: { 1: {halign:'right'}, 2: {halign:'right'}, 3: {halign:'right', fontStyle:'bold'}, 4: {halign:'center'} },
            didParseCell: function(data) {
                if (data.section === 'body' && data.column.index === 3) {
                    const val = parseFloat(data.cell.raw.replace(/[PHP ,]/g, ''));
                    if (val < 0) data.cell.styles.textColor = [220, 38, 38];
                    else data.cell.styles.textColor = [22, 163, 74];
                }
            }
        });
        
        addCommonFooter(doc);
        doc.save(`Phase_Variance_${data.project.project_code}_${dateStr}.pdf`);
    }

    function generateLaborAnalysisPDF(doc, data) {
        let y = addCommonHeader(doc, 'Labor Cost Analysis', data.project);
        const dateStr = new Date().toISOString().split('T')[0];
        
        doc.setFontSize(11);
        doc.setFont('helvetica', 'bold');
        doc.text(`Total Labor Spend: PHP ${data.total_labor.toLocaleString('en-PH', {minimumFractionDigits: 2})}`, 14, y);

        const rows = data.labor_expenses.map(e => [
            new Date(e.expense_date).toLocaleDateString(),
            e.description,
            e.phase,
            'PHP ' + parseFloat(e.amount).toLocaleString('en-PH', {minimumFractionDigits: 2})
        ]);

        doc.autoTable({
            startY: y + 5,
            head: [['Date', 'Description', 'Phase', 'Amount']],
            body: rows,
            theme: 'grid',
            headStyles: { fillColor: [233, 146, 44] },
            columnStyles: { 3: { halign: 'right' } }
        });

        addCommonFooter(doc);
        doc.save(`Labor_Analysis_${data.project.project_code}_${dateStr}.pdf`);
    }

    function generateExpenseLogPDF(doc, data) {
        let y = addCommonHeader(doc, 'Detailed Expense Log', data.project);
        const dateStr = new Date().toISOString().split('T')[0];

        const rows = data.expenses.map(e => [
            new Date(e.expense_date).toLocaleDateString(),
            e.category,
            e.description,
            e.supplier_name || '-',
            'PHP ' + parseFloat(e.amount).toLocaleString('en-PH', {minimumFractionDigits: 2})
        ]);

        doc.autoTable({
            startY: y,
            head: [['Date', 'Category', 'Description', 'Supplier', 'Amount']],
            body: rows,
            theme: 'grid',
            headStyles: { fillColor: [233, 146, 44] },
            columnStyles: { 4: { halign: 'right' } }
        });

        addCommonFooter(doc);
        doc.save(`Expense_Log_${data.project.project_code}_${dateStr}.pdf`);
    }

    function generateCashFlowPDF(doc, data) {
        let y = addCommonHeader(doc, 'Monthly Cash Flow', data.project);
        const dateStr = new Date().toISOString().split('T')[0];

        const rows = data.cash_flow.map(c => {
            const dateParts = c.month_year.split('-');
            const dateObj = new Date(dateParts[0], dateParts[1] - 1);
            return [dateObj.toLocaleString('en-US', { month: 'long', year: 'numeric' }), 'PHP ' + parseFloat(c.monthly_total).toLocaleString('en-PH', {minimumFractionDigits: 2})];
        });

        doc.autoTable({
            startY: y,
            head: [['Month', 'Total Outflow']],
            body: rows,
            theme: 'grid',
            headStyles: { fillColor: [233, 146, 44] },
            columnStyles: { 1: { halign: 'right', fontStyle: 'bold' } }
        });

        addCommonFooter(doc);
        doc.save(`Cash_Flow_${data.project.project_code}_${dateStr}.pdf`);
    }

    // Phase Selector Listener
    document.querySelectorAll('.phase-selector').forEach(selector => {
      selector.addEventListener('change', function() {
        const card = this.closest('.report-template-card');
        const button = card.querySelector('.generate-btn');
        button.disabled = !this.value;
      });
    });
</script>
</body>
</html>