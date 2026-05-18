<?php
/**
 * Reports Module - Procurement Reports Dashboard
 * ICMIS - Integrated Construction Management Information System
 */

if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../core/ApiHelper.php';
require_once __DIR__ . '/../../includes/report_print_layout.php';

// Use procurement project context
require_once __DIR__ . '/project_context.php';
$selected_project_id = getProjectContext();

// Fetch current project info
$current_project_name = "All Projects";
$current_project_code = "";
$current_project_status = "";

if ($selected_project_id > 0) {
    $projRes = ApiHelper::get("project/projects?fetch_id=" . $selected_project_id);
    $project = $projRes['data']['project'] ?? null;
    if ($project) {
        $current_project_name = $project['project_name'];
        $current_project_code = $project['project_code'];
        $current_project_status = $project['status'] ?? 'Active';
    }
}

// Fetch all projects for dropdown
$projectRes = ApiHelper::get('project/projects');
$projects = $projectRes['data']['projects'] ?? [];

// Get user name for footer
$userName = $_SESSION['user_name'] ?? "Admin";

// ==========================================================================
// FETCH STATS FOR DASHBOARD CARDS
// ==========================================================================
$stat_total_items = 0;
$stat_low_stock = 0;
$stat_pending_orders = 0;
$stat_total_value = 0;

// Fetch stats via API
$statsRes = ApiHelper::get("procurement/reports?action=stats&project_id=" . $selected_project_id);
$stats = $statsRes['data']['data'] ?? [];

$stat_total_items = $stats['total_items'] ?? 0;
$stat_low_stock = $stats['low_stock'] ?? 0;
$stat_total_value = $stats['total_value'] ?? 0;
$stat_pending_orders = $stats['pending_orders'] ?? 0;

// ==========================================================================
// FETCH RECENT REPORTS
// ==========================================================================
$recent_reports = [];
$reportRes = ApiHelper::get("reports/reports?category=procurement&project_id=" . $selected_project_id);
$recent_reports = $reportRes['data']['reports'] ?? [];

$pageSection = "Procurement & Inventory";
$pageTitle = "Reports";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Procurement Reports | ICMIS</title>
    
    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="apple-touch-icon" sizes="180x180" href="../../assets/images/favicon/apple-touch-icon.png">
    <link rel="icon" type="image/png" sizes="32x32" href="../../assets/images/favicon/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="../../assets/images/favicon/favicon-16x16.png">
    <link rel="manifest" href="../../assets/images/favicon/site.webmanifest">
    <script src="https://unpkg.com/lucide@latest"></script>
    <?php include '../../includes/head_assetsv2.php'; ?>

    <style>
        * { font-family: 'Inter', sans-serif; }

        /* Template Card Hover */
        .report-template-card {
            transition: all 0.2s ease;
        }
        .report-template-card:hover {
            transform: translateY(-2px);
        }

        /* Shared Print Styles */
        <?php echo renderPrintStyles(); ?>
    </style>
</head>
<body class="bg-gray-50 text-slate-800">
    
    <div class="no-print">
        <?php include __DIR__ . '/../../includes/sidebar.php'; ?>

        <?php
        // Build breadcrumb navigation with dropdown
        $current_page = basename($_SERVER['PHP_SELF']);
        $breadcrumbHTML = '<div class="flex items-center gap-2 text-sm">';
        
        // Project Dropdown
        $breadcrumbHTML .= '<div class="relative inline-block">';
        $breadcrumbHTML .= '<select id="projectSelector" onchange="changeProject(this.value)" class="appearance-none bg-white border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-2 focus:ring-[#e9922c] focus:border-[#e9922c] pl-3 pr-8 py-1.5 hover:bg-gray-50 transition-colors cursor-pointer font-medium">';
        $breadcrumbHTML .= '<option value="">All Projects</option>';
        
        foreach ($projects as $proj) {
            $selected = ($proj['project_id'] == $selected_project_id) ? 'selected' : '';
            $breadcrumbHTML .= '<option value="' . $proj['project_id'] . '" ' . $selected . '>' . htmlspecialchars($proj['project_name']) . '</option>';
        }
        
        $breadcrumbHTML .= '</select>';
        $breadcrumbHTML .= '<svg class="w-3 h-3 text-gray-500 absolute right-2 top-1/2 transform -translate-y-1/2 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>';
        $breadcrumbHTML .= '</div>';
        $breadcrumbHTML .= '</div>';

        $pageSubTitle = $breadcrumbHTML;
        include __DIR__ . '/../../includes/header.php';
        ?>
    </div>

    <!-- Hidden input for project context -->
    <input type="hidden" id="selected_project_id" value="<?php echo $selected_project_id; ?>">

    <!-- Print Header (Hidden on Screen) -->
    <?php echo renderPrintHeader('Procurement Reports', [
        'project_name' => $current_project_name,
        'project_code' => $current_project_code,
        'report_type' => 'Reports Dashboard',
        'generated_by' => $userName
    ]); ?>

    <main class="ml-56 mt-20 p-6 transition-all duration-300 animate-fade-in">
        <div class="max-w-7xl mx-auto">

            <!-- Page Header -->
            <div class="border-b border-gray-200 pb-6 mb-8 no-print">
                <div class="flex items-center justify-between">
                    <div>
                        <h1 class="text-2xl text-gray-900 font-bold">Procurement Reports</h1>
                        <p class="text-sm text-gray-500 mt-1">Generate comprehensive procurement and inventory reports for 
                            <span class="font-semibold text-[#e9922c]"><?php echo htmlspecialchars($current_project_name); ?></span>
                        </p>
                    </div>
                    <div class="flex gap-3">
                        <button onclick="printReport()" class="flex items-center gap-2 px-4 py-2 bg-[#e9922c] border border-gray-300 text-white rounded-lg hover:bg-[#d17f1f] transition-all font-bold">
                            <i data-lucide="printer" class="w-4 h-4"></i>
                            Print
                        </button>
                    </div>
                </div>
            </div>

            <!-- Quick Generate Templates Section -->
            <div class="mb-10 no-print">
                <h2 class="text-xl font-bold text-gray-900 mb-6">Quick Generate Templates</h2>

                <!-- PROCUREMENT REPORTS -->
                <div class="category-section mb-8" data-category="procurement">
                    <div class="flex items-center gap-3 mb-4">
                        <div class="w-8 h-8 rounded-lg bg-purple-100 flex items-center justify-center">
                            <i data-lucide="package" class="w-4 h-4 text-purple-600"></i>
                        </div>
                        <h3 class="text-lg font-bold text-gray-800">Procurement & Inventory</h3>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        
                        <!-- Inventory Status -->
                        <div class="report-template-card bg-white border-2 border-gray-200 rounded-xl p-6 hover:border-purple-300 hover:shadow-lg flex flex-col justify-between" data-template="inventory-status" data-category="procurement">
                            <div>
                                <div class="flex items-center gap-3 mb-3">
                                    <div class="w-10 h-10 rounded-lg bg-purple-100 flex items-center justify-center">
                                        <i data-lucide="boxes" class="w-5 h-5 text-purple-600"></i>
                                    </div>
                                    <h4 class="font-bold text-gray-900">Inventory Status</h4>
                                </div>
                                <p class="text-sm text-gray-500 mb-4">Current stock levels, low stock alerts, and inventory valuation report.</p>
                            </div>
                            <button onclick="generateReport('inventory-status', event)" class="generate-btn w-full flex items-center justify-center gap-2 px-4 py-2 bg-white border-2 border-purple-600 text-purple-700 hover:bg-purple-50 font-semibold rounded-lg transition-colors">
                                <i data-lucide="download" class="w-4 h-4"></i>
                                Generate Report
                            </button>
                        </div>

                        <!-- Purchase Orders -->
                        <div class="report-template-card bg-white border-2 border-gray-200 rounded-xl p-6 hover:border-indigo-300 hover:shadow-lg flex flex-col justify-between" data-template="purchase-orders" data-category="procurement">
                            <div>
                                <div class="flex items-center gap-3 mb-3">
                                    <div class="w-10 h-10 rounded-lg bg-indigo-100 flex items-center justify-center">
                                        <i data-lucide="file-text" class="w-5 h-5 text-indigo-600"></i>
                                    </div>
                                    <h4 class="font-bold text-gray-900">Purchase Orders</h4>
                                </div>
                                <p class="text-sm text-gray-500 mb-4">Summary of all purchase orders with status tracking and supplier details.</p>
                            </div>
                            <button onclick="generateReport('purchase-orders', event)" class="generate-btn w-full flex items-center justify-center gap-2 px-4 py-2 bg-white border-2 border-indigo-600 text-indigo-700 hover:bg-indigo-50 font-semibold rounded-lg transition-colors">
                                <i data-lucide="download" class="w-4 h-4"></i>
                                Generate Report
                            </button>
                        </div>

                        <!-- Stock Movement -->
                        <div class="report-template-card bg-white border-2 border-gray-200 rounded-xl p-6 hover:border-pink-300 hover:shadow-lg flex flex-col justify-between" data-template="stock-movement" data-category="procurement">
                            <div>
                                <div class="flex items-center gap-3 mb-3">
                                    <div class="w-10 h-10 rounded-lg bg-pink-100 flex items-center justify-center">
                                        <i data-lucide="arrow-left-right" class="w-5 h-5 text-pink-600"></i>
                                    </div>
                                    <h4 class="font-bold text-gray-900">Stock Movement</h4>
                                </div>
                                <p class="text-sm text-gray-500 mb-4">Track all stock in/out transactions with dates and quantities.</p>
                            </div>
                            <button onclick="generateReport('stock-movement', event)" class="generate-btn w-full flex items-center justify-center gap-2 px-4 py-2 bg-white border-2 border-pink-600 text-pink-700 hover:bg-pink-50 font-semibold rounded-lg transition-colors">
                                <i data-lucide="download" class="w-4 h-4"></i>
                                Generate Report
                            </button>
                        </div>

                    </div>
                </div>
            </div>

            <!-- Quick Stats Summary -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-10 no-print">
                <div class="bg-white rounded-xl border border-gray-200 p-6 shadow-sm">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Total Items</p>
                            <h3 id="stat-total-items" class="text-2xl font-black text-gray-900 mt-1"><?php echo number_format($stat_total_items); ?></h3>
                        </div>
                        <div class="w-12 h-12 rounded-lg bg-purple-100 flex items-center justify-center">
                            <i data-lucide="boxes" class="w-6 h-6 text-purple-600"></i>
                        </div>
                    </div>
                </div>
                <div class="bg-white rounded-xl border border-gray-200 p-6 shadow-sm">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Low Stock Items</p>
                            <h3 id="stat-low-stock" class="text-2xl font-black text-red-600 mt-1"><?php echo number_format($stat_low_stock); ?></h3>
                        </div>
                        <div class="w-12 h-12 rounded-lg bg-red-100 flex items-center justify-center">
                            <i data-lucide="alert-triangle" class="w-6 h-6 text-red-600"></i>
                        </div>
                    </div>
                </div>
                <div class="bg-white rounded-xl border border-gray-200 p-6 shadow-sm">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Pending Orders</p>
                            <h3 id="stat-pending-orders" class="text-2xl font-black text-indigo-600 mt-1"><?php echo number_format($stat_pending_orders); ?></h3>
                        </div>
                        <div class="w-12 h-12 rounded-lg bg-indigo-100 flex items-center justify-center">
                            <i data-lucide="clock" class="w-6 h-6 text-indigo-600"></i>
                        </div>
                    </div>
                </div>
                <div class="bg-white rounded-xl border border-gray-200 p-6 shadow-sm">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Total Valuation</p>
                            <h3 id="stat-total-value" class="text-2xl font-black text-green-600 mt-1">₱<?php echo number_format($stat_total_value, 2); ?></h3>
                        </div>
                        <div class="w-12 h-12 rounded-lg bg-green-100 flex items-center justify-center">
                            <i data-lucide="banknote" class="w-6 h-6 text-green-600"></i>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent Reports Table -->
            <div class="bg-white border border-gray-200 rounded-xl overflow-hidden shadow-sm mt-8">
                <div class="px-6 py-4 border-b border-gray-100 flex justify-between items-center bg-gray-50 no-print">
                    <div class="flex items-center gap-3">
                        <h3 class="font-bold text-gray-800">Recent Reports</h3>
                        <?php if($selected_project_id): ?>
                        <span class="text-xs font-medium text-gray-500 bg-white border border-gray-200 px-2 py-0.5 rounded-full">
                            Filtered by: <?php echo htmlspecialchars($current_project_name); ?>
                        </span>
                        <?php endif; ?>
                    </div>
                    <div class="flex gap-2">
                        <button onclick="printReport()" class="text-sm text-gray-600 hover:text-[#e9922c] font-medium transition-colors flex items-center gap-1 px-3 py-1.5 border border-gray-300 rounded-lg hover:bg-gray-50">
                            <i data-lucide="printer" class="w-3 h-3"></i> Print List
                        </button>
                        <button onclick="location.reload()" class="text-sm text-gray-500 hover:text-[#e9922c] font-medium transition-colors flex items-center gap-1 px-2">
                            <i data-lucide="refresh-cw" class="w-3 h-3"></i> Refresh
                        </button>
                    </div>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead class="bg-gray-50 text-xs uppercase text-gray-500 font-semibold">
                            <tr>
                                <th class="px-6 py-3">Report Name</th>
                                <th class="px-6 py-3">Type</th>
                                <th class="px-6 py-3">Project</th>
                                <th class="px-6 py-3">Generated By</th>
                                <th class="px-6 py-3">Date Generated</th>
                                <th class="px-6 py-3 text-right no-print">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 text-sm">
                            <?php if(empty($recent_reports)): ?>
                            <tr>
                                <td colspan="6" class="px-6 py-8 text-center text-gray-500 bg-white">
                                    <div class="flex flex-col items-center gap-2">
                                        <i data-lucide="file-x" class="w-10 h-10 text-gray-300"></i>
                                        <p class="font-medium">No procurement reports generated yet</p>
                                        <p class="text-xs text-gray-400">Use the templates above to generate your first report</p>
                                    </div>
                                </td>
                            </tr>
                            <?php else: ?>
                                <?php foreach($recent_reports as $report): ?>
                                <tr class="hover:bg-gray-50 transition">
                                    <td class="px-6 py-4 font-medium text-gray-700 flex items-center gap-2">
                                        <?php
                                        $icon = 'file-text';
                                        $iconColor = 'text-purple-600';
                                        if ($report['report_type'] === 'inventory-status') {
                                            $icon = 'boxes';
                                            $iconColor = 'text-purple-600';
                                        } elseif ($report['report_type'] === 'purchase-orders') {
                                            $icon = 'file-text';
                                            $iconColor = 'text-indigo-600';
                                        } elseif ($report['report_type'] === 'stock-movement') {
                                            $icon = 'arrow-left-right';
                                            $iconColor = 'text-pink-600';
                                        }
                                        ?>
                                        <i data-lucide="<?php echo $icon; ?>" class="w-4 h-4 <?php echo $iconColor; ?>"></i>
                                        <?php echo htmlspecialchars($report['report_name'] ?? 'Untitled Report'); ?>
                                    </td>
                                    <td class="px-6 py-4">
                                        <?php
                                        $badgeColor = 'bg-purple-100 text-purple-700';
                                        $typeName = ucwords(str_replace('-', ' ', $report['report_type']));
                                        if ($report['report_type'] === 'purchase-orders') {
                                            $badgeColor = 'bg-indigo-100 text-indigo-700';
                                        } elseif ($report['report_type'] === 'stock-movement') {
                                            $badgeColor = 'bg-pink-100 text-pink-700';
                                        }
                                        ?>
                                        <span class="px-2 py-1 text-xs font-medium rounded-full <?php echo $badgeColor; ?>">
                                            <?php echo $typeName; ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-gray-600"><?php echo htmlspecialchars($report['project_name'] ?? 'All'); ?></td>
                                    <td class="px-6 py-4 text-gray-600"><?php echo htmlspecialchars($report['generated_by'] ?? '-'); ?></td>
                                    <td class="px-6 py-4 text-gray-500"><?php echo date('M j, Y h:i A', strtotime($report['created_at'])); ?></td>
                                    <td class="px-6 py-4 text-right no-print">
                                        <button onclick="regenerateReport('<?php echo $report['report_type']; ?>')" class="text-[#e9922c] hover:text-orange-700 font-medium text-sm flex items-center gap-1 ml-auto">
                                            <i data-lucide="refresh-cw" class="w-3 h-3"></i> Regenerate
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

        <!-- Print Footer -->
        <?php echo renderPrintFooter($userName, 'Procurement Manager'); ?>
    </main>

    <!-- Toast Container -->
    <?php include '../../includes/toast.php'; ?>

    <script src="js/reports.js"></script>
    <script>
        // Initialize Lucide Icons
        lucide.createIcons();

        // ============================================
        // PRINT FUNCTIONALITY (Shared)
        // ============================================
        <?php echo renderPrintReportScript(); ?>

        // ============================================
        // PROJECT CONTEXT CHANGE
        // ============================================
        function changeProject(projectId) {
            const url = new URL(window.location.href);
            if (projectId && parseInt(projectId, 10) > 0) {
                url.searchParams.set('project_id', projectId);
            } else {
                url.searchParams.delete('project_id');
            }
            window.location.href = url.toString();
        }
    </script>
</body>
</html>
