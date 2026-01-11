<?php
/**
 * Reports Module - Main Dashboard
 * ICMIS - Integrated Construction Management Information System
 * 
 * Provides centralized report generation across all modules:
 * Budget, Procurement, Project, and Workforce
 */

session_start();
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/report_print_layout.php';

// Create database connection
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
$conn->set_charset("utf8mb4");

// Get selected project from session/URL
$selected_project_id = 0;
if (isset($_GET['project_id']) && !empty($_GET['project_id'])) {
    $selected_project_id = intval($_GET['project_id']);
    $_SESSION['current_project_id'] = $selected_project_id;
} elseif (isset($_SESSION['current_project_id'])) {
    $selected_project_id = $_SESSION['current_project_id'];
}

// Fetch current project info (enhanced with status and more details)
$current_project_name = "All Projects";
$current_project_code = "";
$current_project_status = "";
$current_project_location = "";
$current_project_budget = 0;
$current_project_completion = 0;

if ($selected_project_id) {
    $stmt = $conn->prepare("SELECT project_name, project_code, status, location, total_budget, completion_rate FROM icmis_projects WHERE project_id = ?");
    $stmt->bind_param("i", $selected_project_id);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($row = $res->fetch_assoc()) {
        $current_project_name = $row['project_name'];
        $current_project_code = $row['project_code'];
        $current_project_status = $row['status'] ?? 'Active';
        $current_project_location = $row['location'] ?? 'N/A';
        $current_project_budget = floatval($row['total_budget']);
        $current_project_completion = floatval($row['completion_rate']);
    }
    $stmt->close();
}

// Fetch all projects for dropdown
$projects = [];
$result_projects = $conn->query("SELECT project_id, project_code, project_name FROM icmis_projects ORDER BY project_id DESC");
if ($result_projects && $result_projects->num_rows > 0) {
    while ($row = $result_projects->fetch_assoc()) {
        $projects[] = $row;
    }
}

// Fetch recent generated reports (if table exists)
$recent_reports = [];
$tableExists = $conn->query("SHOW TABLES LIKE 'budget_generated_reports'");
if ($tableExists && $tableExists->num_rows > 0) {
    $report_sql = "SELECT report_id AS id, report_type AS category, report_name, project_id, generated_by, created_at FROM budget_generated_reports ORDER BY created_at DESC LIMIT 20";
    $result = $conn->query($report_sql);
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $recent_reports[] = $row;
        }
    }
}

$userName = $_SESSION['user_name'] ?? "Admin";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports Center | ICMIS</title>
    
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

        /* Category Tab Styles */
        .category-tab {
            transition: all 0.2s ease;
        }
        .category-tab.active {
            border-color: #e9922c;
            color: #e9922c;
        }
        .category-tab:not(.active):hover {
            border-color: #d1d5db;
            color: #374151;
        }

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
        $breadcrumbHTML .= '<select id="projectSelector" onchange="window.location.href=\'index.php?project_id=\' + this.value" class="appearance-none bg-white border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-2 focus:ring-[#e9922c] focus:border-[#e9922c] pl-3 pr-8 py-1.5 hover:bg-gray-50 transition-colors cursor-pointer font-medium">';
        $breadcrumbHTML .= '<option value="">All Projects</option>';
        
        foreach ($projects as $proj) {
            $selected = ($proj['project_id'] == $selected_project_id) ? 'selected' : '';
            $breadcrumbHTML .= '<option value="' . $proj['project_id'] . '" ' . $selected . '>' . htmlspecialchars($proj['project_name']) . '</option>';
        }
        
        $breadcrumbHTML .= '</select>';
        $breadcrumbHTML .= '<svg class="w-3 h-3 text-gray-500 absolute right-2 top-1/2 transform -translate-y-1/2 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>';
        $breadcrumbHTML .= '</div>';
        $breadcrumbHTML .= '</div>';

        $pageTitle = "Reports Center";
        $pageSection = "Reports & Analytics";
        $pageSubTitle = $breadcrumbHTML;
        include __DIR__ . '/../../includes/header.php';
        ?>
    </div>

    <!-- Print Header (Hidden on Screen) -->
    <?php echo renderPrintHeader('ICMIS Reports Center', [
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
                        <h1 class="text-2xl text-gray-900 font-bold">Reports Center</h1>
                        <p class="text-sm text-gray-500 mt-1">Generate comprehensive reports across all modules for 
                            <span class="font-semibold text-[#e9922c]"><?php echo htmlspecialchars($current_project_name); ?></span>
                        </p>
                    </div>
                    <div class="flex gap-3">
                        <button onclick="printReport()" class="flex items-center gap-2 px-4 py-2 bg-white border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 font-medium transition-colors">
                            <i data-lucide="printer" class="w-4 h-4"></i>
                            Print
                        </button>
                    </div>
                </div>
            </div>

            <!-- Category Tabs -->
            <div class="category-tabs mb-8 no-print">
                <div class="flex items-center gap-1 border-b border-gray-200">
                    <button onclick="switchCategory('all')" class="category-tab px-6 py-3 text-sm font-semibold border-b-2 border-[#e9922c] text-[#e9922c] transition-colors" data-category="all">
                        All Reports
                    </button>
                    <button onclick="switchCategory('budget')" class="category-tab px-6 py-3 text-sm font-semibold border-b-2 border-transparent text-gray-500 hover:text-gray-700 transition-colors" data-category="budget">
                        Budget
                    </button>
                    <button onclick="switchCategory('procurement')" class="category-tab px-6 py-3 text-sm font-semibold border-b-2 border-transparent text-gray-500 hover:text-gray-700 transition-colors" data-category="procurement">
                        Procurement
                    </button>
                    <button onclick="switchCategory('project')" class="category-tab px-6 py-3 text-sm font-semibold border-b-2 border-transparent text-gray-500 hover:text-gray-700 transition-colors" data-category="project">
                        Project
                    </button>
                    <button onclick="switchCategory('workforce')" class="category-tab px-6 py-3 text-sm font-semibold border-b-2 border-transparent text-gray-500 hover:text-gray-700 transition-colors" data-category="workforce">
                        Workforce
                    </button>
                </div>
            </div>

            <!-- Quick Generate Templates Section -->
            <div class="mb-10 no-print">
                <h2 class="text-xl font-bold text-gray-900 mb-6">Quick Generate Templates</h2>

                <!-- BUDGET REPORTS -->
                <div class="category-section mb-8" data-category="budget">
                    <div class="flex items-center gap-3 mb-4">
                        <div class="w-8 h-8 rounded-lg bg-blue-100 flex items-center justify-center">
                            <i data-lucide="wallet" class="w-4 h-4 text-blue-600"></i>
                        </div>
                        <h3 class="text-lg font-bold text-gray-800">Budget & Cost Control</h3>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <!-- Budget Summary -->
                        <div class="report-template-card bg-white border-2 border-gray-200 rounded-xl p-6 hover:border-blue-300 hover:shadow-lg flex flex-col justify-between" data-template="budget-summary" data-category="budget">
                            <div>
                                <div class="flex items-center gap-3 mb-3">
                                    <div class="w-10 h-10 rounded-lg bg-blue-100 flex items-center justify-center">
                                        <i data-lucide="pie-chart" class="w-5 h-5 text-blue-600"></i>
                                    </div>
                                    <h4 class="font-bold text-gray-900">Budget Summary</h4>
                                </div>
                                <p class="text-sm text-gray-500 mb-4">Overview of allocated budget, spending, and remaining funds across all phases.</p>
                            </div>
                            <button onclick="generateReport('budget-summary')" class="generate-btn w-full flex items-center justify-center gap-2 px-4 py-2 bg-white border-2 border-blue-600 text-blue-700 hover:bg-blue-50 font-semibold rounded-lg transition-colors">
                                <i data-lucide="download" class="w-4 h-4"></i>
                                Generate Report
                            </button>
                        </div>

                        <!-- Expense Log -->
                        <div class="report-template-card bg-white border-2 border-gray-200 rounded-xl p-6 hover:border-blue-300 hover:shadow-lg flex flex-col justify-between" data-template="expense-log" data-category="budget">
                            <div>
                                <div class="flex items-center gap-3 mb-3">
                                    <div class="w-10 h-10 rounded-lg bg-green-100 flex items-center justify-center">
                                        <i data-lucide="receipt" class="w-5 h-5 text-green-600"></i>
                                    </div>
                                    <h4 class="font-bold text-gray-900">Expense Log</h4>
                                </div>
                                <p class="text-sm text-gray-500 mb-4">Detailed log of all recorded expenses with categories and suppliers.</p>
                            </div>
                            <button onclick="generateReport('expense-log')" class="generate-btn w-full flex items-center justify-center gap-2 px-4 py-2 bg-white border-2 border-green-600 text-green-700 hover:bg-green-50 font-semibold rounded-lg transition-colors">
                                <i data-lucide="download" class="w-4 h-4"></i>
                                Generate Report
                            </button>
                        </div>

                        <!-- Cash Flow -->
                        <div class="report-template-card bg-white border-2 border-gray-200 rounded-xl p-6 hover:border-blue-300 hover:shadow-lg flex flex-col justify-between" data-template="cash-flow" data-category="budget">
                            <div>
                                <div class="flex items-center gap-3 mb-3">
                                    <div class="w-10 h-10 rounded-lg bg-teal-100 flex items-center justify-center">
                                        <i data-lucide="trending-up" class="w-5 h-5 text-teal-600"></i>
                                    </div>
                                    <h4 class="font-bold text-gray-900">Cash Flow Analysis</h4>
                                </div>
                                <p class="text-sm text-gray-500 mb-4">Monthly cash flow breakdown showing spending patterns over time.</p>
                            </div>
                            <button onclick="generateReport('cash-flow')" class="generate-btn w-full flex items-center justify-center gap-2 px-4 py-2 bg-white border-2 border-teal-600 text-teal-700 hover:bg-teal-50 font-semibold rounded-lg transition-colors">
                                <i data-lucide="download" class="w-4 h-4"></i>
                                Generate Report
                            </button>
                        </div>
                    </div>
                </div>

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
                            <button onclick="generateReport('inventory-status')" class="generate-btn w-full flex items-center justify-center gap-2 px-4 py-2 bg-white border-2 border-purple-600 text-purple-700 hover:bg-purple-50 font-semibold rounded-lg transition-colors">
                                <i data-lucide="download" class="w-4 h-4"></i>
                                Generate Report
                            </button>
                        </div>

                        <!-- Purchase Orders -->
                        <div class="report-template-card bg-white border-2 border-gray-200 rounded-xl p-6 hover:border-purple-300 hover:shadow-lg flex flex-col justify-between" data-template="purchase-orders" data-category="procurement">
                            <div>
                                <div class="flex items-center gap-3 mb-3">
                                    <div class="w-10 h-10 rounded-lg bg-indigo-100 flex items-center justify-center">
                                        <i data-lucide="file-text" class="w-5 h-5 text-indigo-600"></i>
                                    </div>
                                    <h4 class="font-bold text-gray-900">Purchase Orders</h4>
                                </div>
                                <p class="text-sm text-gray-500 mb-4">Summary of all purchase orders with status tracking and supplier details.</p>
                            </div>
                            <button onclick="generateReport('purchase-orders')" class="generate-btn w-full flex items-center justify-center gap-2 px-4 py-2 bg-white border-2 border-indigo-600 text-indigo-700 hover:bg-indigo-50 font-semibold rounded-lg transition-colors">
                                <i data-lucide="download" class="w-4 h-4"></i>
                                Generate Report
                            </button>
                        </div>

                        <!-- Stock Movement -->
                        <div class="report-template-card bg-white border-2 border-gray-200 rounded-xl p-6 hover:border-purple-300 hover:shadow-lg flex flex-col justify-between" data-template="stock-movement" data-category="procurement">
                            <div>
                                <div class="flex items-center gap-3 mb-3">
                                    <div class="w-10 h-10 rounded-lg bg-pink-100 flex items-center justify-center">
                                        <i data-lucide="arrow-left-right" class="w-5 h-5 text-pink-600"></i>
                                    </div>
                                    <h4 class="font-bold text-gray-900">Stock Movement</h4>
                                </div>
                                <p class="text-sm text-gray-500 mb-4">Track all stock in/out transactions with dates and quantities.</p>
                            </div>
                            <button onclick="generateReport('stock-movement')" class="generate-btn w-full flex items-center justify-center gap-2 px-4 py-2 bg-white border-2 border-pink-600 text-pink-700 hover:bg-pink-50 font-semibold rounded-lg transition-colors">
                                <i data-lucide="download" class="w-4 h-4"></i>
                                Generate Report
                            </button>
                        </div>
                    </div>
                </div>

                <!-- PROJECT REPORTS -->
                <div class="category-section mb-8" data-category="project">
                    <div class="flex items-center gap-3 mb-4">
                        <div class="w-8 h-8 rounded-lg bg-orange-100 flex items-center justify-center">
                            <i data-lucide="folder-kanban" class="w-4 h-4 text-orange-600"></i>
                        </div>
                        <h3 class="text-lg font-bold text-gray-800">Project Management</h3>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <!-- Project Summary -->
                        <div class="report-template-card bg-white border-2 border-gray-200 rounded-xl p-6 hover:border-orange-300 hover:shadow-lg flex flex-col justify-between" data-template="project-summary" data-category="project">
                            <div>
                                <div class="flex items-center gap-3 mb-3">
                                    <div class="w-10 h-10 rounded-lg bg-orange-100 flex items-center justify-center">
                                        <i data-lucide="layout-dashboard" class="w-5 h-5 text-orange-600"></i>
                                    </div>
                                    <h4 class="font-bold text-gray-900">Project Summary</h4>
                                </div>
                                <p class="text-sm text-gray-500 mb-4">High-level overview of project status, timeline, and key milestones.</p>
                            </div>
                            <button onclick="generateReport('project-summary')" class="generate-btn w-full flex items-center justify-center gap-2 px-4 py-2 bg-white border-2 border-orange-600 text-orange-700 hover:bg-orange-50 font-semibold rounded-lg transition-colors">
                                <i data-lucide="download" class="w-4 h-4"></i>
                                Generate Report
                            </button>
                        </div>

                        <!-- Phase Progress -->
                        <div class="report-template-card bg-white border-2 border-gray-200 rounded-xl p-6 hover:border-orange-300 hover:shadow-lg flex flex-col justify-between" data-template="phase-progress" data-category="project">
                            <div>
                                <div class="flex items-center gap-3 mb-3">
                                    <div class="w-10 h-10 rounded-lg bg-amber-100 flex items-center justify-center">
                                        <i data-lucide="git-branch" class="w-5 h-5 text-amber-600"></i>
                                    </div>
                                    <h4 class="font-bold text-gray-900">Phase Progress</h4>
                                </div>
                                <p class="text-sm text-gray-500 mb-4">Detailed breakdown of each project phase with completion percentages.</p>
                            </div>
                            <button onclick="generateReport('phase-progress')" class="generate-btn w-full flex items-center justify-center gap-2 px-4 py-2 bg-white border-2 border-amber-600 text-amber-700 hover:bg-amber-50 font-semibold rounded-lg transition-colors">
                                <i data-lucide="download" class="w-4 h-4"></i>
                                Generate Report
                            </button>
                        </div>

                        <!-- Task Status -->
                        <div class="report-template-card bg-white border-2 border-gray-200 rounded-xl p-6 hover:border-orange-300 hover:shadow-lg flex flex-col justify-between" data-template="task-status" data-category="project">
                            <div>
                                <div class="flex items-center gap-3 mb-3">
                                    <div class="w-10 h-10 rounded-lg bg-yellow-100 flex items-center justify-center">
                                        <i data-lucide="check-square" class="w-5 h-5 text-yellow-600"></i>
                                    </div>
                                    <h4 class="font-bold text-gray-900">Task Status</h4>
                                </div>
                                <p class="text-sm text-gray-500 mb-4">Complete list of tasks with assignees, deadlines, and current status.</p>
                            </div>
                            <button onclick="generateReport('task-status')" class="generate-btn w-full flex items-center justify-center gap-2 px-4 py-2 bg-white border-2 border-yellow-600 text-yellow-700 hover:bg-yellow-50 font-semibold rounded-lg transition-colors">
                                <i data-lucide="download" class="w-4 h-4"></i>
                                Generate Report
                            </button>
                        </div>
                    </div>
                </div>

                <!-- WORKFORCE REPORTS -->
                <div class="category-section mb-8" data-category="workforce">
                    <div class="flex items-center gap-3 mb-4">
                        <div class="w-8 h-8 rounded-lg bg-cyan-100 flex items-center justify-center">
                            <i data-lucide="users" class="w-4 h-4 text-cyan-600"></i>
                        </div>
                        <h3 class="text-lg font-bold text-gray-800">Labor & Workforce</h3>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <!-- Employee Roster -->
                        <div class="report-template-card bg-white border-2 border-gray-200 rounded-xl p-6 hover:border-cyan-300 hover:shadow-lg flex flex-col justify-between" data-template="employee-roster" data-category="workforce">
                            <div>
                                <div class="flex items-center gap-3 mb-3">
                                    <div class="w-10 h-10 rounded-lg bg-cyan-100 flex items-center justify-center">
                                        <i data-lucide="user-check" class="w-5 h-5 text-cyan-600"></i>
                                    </div>
                                    <h4 class="font-bold text-gray-900">Employee Roster</h4>
                                </div>
                                <p class="text-sm text-gray-500 mb-4">Complete list of employees with positions, skills, and contact details.</p>
                            </div>
                            <button onclick="generateReport('employee-roster')" class="generate-btn w-full flex items-center justify-center gap-2 px-4 py-2 bg-white border-2 border-cyan-600 text-cyan-700 hover:bg-cyan-50 font-semibold rounded-lg transition-colors">
                                <i data-lucide="download" class="w-4 h-4"></i>
                                Generate Report
                            </button>
                        </div>

                        <!-- Attendance Summary -->
                        <div class="report-template-card bg-white border-2 border-gray-200 rounded-xl p-6 hover:border-cyan-300 hover:shadow-lg flex flex-col justify-between" data-template="attendance-summary" data-category="workforce">
                            <div>
                                <div class="flex items-center gap-3 mb-3">
                                    <div class="w-10 h-10 rounded-lg bg-emerald-100 flex items-center justify-center">
                                        <i data-lucide="calendar-check" class="w-5 h-5 text-emerald-600"></i>
                                    </div>
                                    <h4 class="font-bold text-gray-900">Attendance Summary</h4>
                                </div>
                                <p class="text-sm text-gray-500 mb-4">Attendance records with present/absent statistics by employee.</p>
                            </div>
                            <button onclick="generateReport('attendance-summary')" class="generate-btn w-full flex items-center justify-center gap-2 px-4 py-2 bg-white border-2 border-emerald-600 text-emerald-700 hover:bg-emerald-50 font-semibold rounded-lg transition-colors">
                                <i data-lucide="download" class="w-4 h-4"></i>
                                Generate Report
                            </button>
                        </div>

                        <!-- Payroll Report -->
                        <div class="report-template-card bg-white border-2 border-gray-200 rounded-xl p-6 hover:border-cyan-300 hover:shadow-lg flex flex-col justify-between" data-template="payroll-report" data-category="workforce">
                            <div>
                                <div class="flex items-center gap-3 mb-3">
                                    <div class="w-10 h-10 rounded-lg bg-rose-100 flex items-center justify-center">
                                        <i data-lucide="banknote" class="w-5 h-5 text-rose-600"></i>
                                    </div>
                                    <h4 class="font-bold text-gray-900">Payroll Report</h4>
                                </div>
                                <p class="text-sm text-gray-500 mb-4">Payroll summary with wages, deductions, and net pay calculations.</p>
                            </div>
                            <button onclick="generateReport('payroll-report')" class="generate-btn w-full flex items-center justify-center gap-2 px-4 py-2 bg-white border-2 border-rose-600 text-rose-700 hover:bg-rose-50 font-semibold rounded-lg transition-colors">
                                <i data-lucide="download" class="w-4 h-4"></i>
                                Generate Report
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent Reports Table -->
            <div class="bg-white border border-gray-200 rounded-xl overflow-hidden shadow-sm">
                <div class="px-6 py-4 border-b border-gray-100 flex justify-between items-center bg-gray-50 no-print">
                    <div class="flex items-center gap-3">
                        <h3 class="font-bold text-gray-800">Recently Generated Reports</h3>
                        <span class="text-xs font-medium text-gray-500 bg-white border border-gray-200 px-2 py-0.5 rounded-full">
                            <?php echo count($recent_reports); ?> reports
                        </span>
                    </div>
                    <div class="flex gap-2">
                        <button onclick="location.reload()" class="text-sm text-gray-500 hover:text-[#e9922c] font-medium transition-colors flex items-center gap-1 px-2">
                            <i data-lucide="refresh-cw" class="w-4 h-4"></i>
                            Refresh
                        </button>
                    </div>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead class="bg-gray-50 text-xs uppercase text-gray-500 font-semibold">
                            <tr>
                                <th class="px-6 py-3">Report Name</th>
                                <th class="px-6 py-3">Category</th>
                                <th class="px-6 py-3">Project</th>
                                <th class="px-6 py-3">Generated By</th>
                                <th class="px-6 py-3">Date</th>
                                <th class="px-6 py-3 no-print">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 text-sm">
                            <?php if (count($recent_reports) > 0): ?>
                                <?php foreach ($recent_reports as $report): ?>
                                <tr class="hover:bg-gray-50">
                                    <td class="px-6 py-4 font-medium text-gray-900"><?php echo htmlspecialchars($report['report_name'] ?? ''); ?></td>
                                    <td class="px-6 py-4">
                                        <span class="px-2 py-1 text-xs font-medium rounded-full bg-gray-100 text-gray-700">
                                            <?php echo htmlspecialchars($report['category'] ?? ''); ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-gray-600"><?php echo htmlspecialchars($report['project_name'] ?? 'All'); ?></td>
                                    <td class="px-6 py-4 text-gray-600"><?php echo htmlspecialchars($report['generated_by'] ?? ''); ?></td>
                                    <td class="px-6 py-4 text-gray-500"><?php echo date('M j, Y h:i A', strtotime($report['created_at'])); ?></td>
                                    <td class="px-6 py-4 no-print">
                                        <button onclick="downloadReport(<?php echo $report['id']; ?>)" class="text-[#e9922c] hover:text-orange-700 font-medium">
                                            <i data-lucide="download" class="w-4 h-4"></i>
                                        </button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6" class="px-6 py-12 text-center text-gray-500">
                                        <div class="flex flex-col items-center gap-3">
                                            <i data-lucide="file-x" class="w-12 h-12 text-gray-300"></i>
                                            <p class="font-medium">No reports generated yet</p>
                                            <p class="text-sm">Use the templates above to generate your first report</p>
                                        </div>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Print Footer -->
        <?php echo renderPrintFooter($userName, 'Reports Admin'); ?>
    </main>

    <!-- Toast Container -->
    <?php include '../../includes/toast.php'; ?>

    <script>
        // Initialize Lucide Icons
        lucide.createIcons();

        // ============================================
        // CATEGORY TAB SWITCHING
        // ============================================
        function switchCategory(category) {
            // Update tab states
            document.querySelectorAll('.category-tab').forEach(tab => {
                if (tab.dataset.category === category) {
                    tab.classList.add('border-[#e9922c]', 'text-[#e9922c]');
                    tab.classList.remove('border-transparent', 'text-gray-500');
                } else {
                    tab.classList.remove('border-[#e9922c]', 'text-[#e9922c]');
                    tab.classList.add('border-transparent', 'text-gray-500');
                }
            });

            // Show/hide category sections
            document.querySelectorAll('.category-section').forEach(section => {
                if (category === 'all' || section.dataset.category === category) {
                    section.style.display = 'block';
                } else {
                    section.style.display = 'none';
                }
            });
        }

        // ============================================
        // PRINT FUNCTIONALITY (Shared)
        // ============================================
        <?php echo renderPrintReportScript(); ?>

        // ============================================
        // UI STATE MANAGEMENT
        // ============================================
        function setLoadingState(button) {
            if (!button) return;
            button.dataset.originalContent = button.innerHTML;
            button.dataset.originalClasses = button.className;
            button.disabled = true;

            const width = button.offsetWidth;
            button.style.width = `${width}px`;
            button.classList.add('opacity-80', 'cursor-not-allowed');
            button.innerHTML = `<div class="flex items-center justify-center gap-2"><i data-lucide="loader-2" class="w-4 h-4 animate-spin"></i><span>Generating...</span></div>`;
            lucide.createIcons();
        }

        function resetLoadingState(button) {
            if (!button) return;
            button.disabled = false;
            button.style.width = '';
            button.className = button.dataset.originalClasses;
            button.innerHTML = button.dataset.originalContent;
            lucide.createIcons();
        }

        function showSuccessState(button) {
            if (!button) return;
            button.className = "generate-btn w-full flex items-center justify-center gap-2 px-4 py-2 bg-green-600 text-white font-semibold rounded-lg transition-all duration-300 shadow-sm";
            button.innerHTML = `<div class="flex items-center justify-center gap-2"><i data-lucide="check-circle" class="w-4 h-4"></i><span>Downloaded</span></div>`;
            lucide.createIcons();

            if (typeof showToast === 'function') {
                showToast('Report generated successfully!', 'success', true);
            }

            setTimeout(() => {
                resetLoadingState(button);
            }, 2000);
        }

        // ============================================
        // REPORT GENERATION (Print-Based)
        // ============================================
        function generateReport(templateType) {
            const button = event.target.closest('.generate-btn');
            const projectId = '<?php echo $selected_project_id; ?>';

            setLoadingState(button);

            // Build URL for the print-based report
            let url = `print_report.php?type=${encodeURIComponent(templateType)}`;
            if (projectId && projectId !== '0') {
                url += `&project_id=${projectId}`;
            }

            // Open in new tab
            const win = window.open(url, '_blank', 'noopener,noreferrer');
            if (win) {
                win.opener = null;
            }

            // Show success and reset button state
            setTimeout(() => {
                showSuccessState(button);
            }, 500);
        }

        function getReportTitle(templateType) {
            const titles = {
                'budget-summary': 'Budget Summary Report',
                'expense-log': 'Expense Log Report',
                'cash-flow': 'Cash Flow Analysis',
                'inventory-status': 'Inventory Status Report',
                'purchase-orders': 'Purchase Orders Report',
                'stock-movement': 'Stock Movement Report',
                'project-summary': 'Project Summary Report',
                'phase-progress': 'Phase Progress Report',
                'task-status': 'Task Status Report',
                'employee-roster': 'Employee Roster',
                'attendance-summary': 'Attendance Summary',
                'payroll-report': 'Payroll Report'
            };
            return titles[templateType] || 'Report';
        }

        function downloadReport(reportId) {
            // Open the report download in a new tab/window and prevent the opener from accessing it
            const url = `api/download_report.php?id=${reportId}`;
            // Try to use noopener/noreferrer features when supported
            const win = window.open(url, '_blank', 'noopener,noreferrer');
            if (win) win.opener = null;
        }
    </script>
</body>
</html>
<?php $conn->close(); ?>
