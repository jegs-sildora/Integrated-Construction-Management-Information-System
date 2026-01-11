<?php
// 1. Load Configuration & Database
require_once __DIR__ . '/config/config.php';
require_once BASE_PATH . '/config/database.php';

// 2. Security: Ensure User is Logged In
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    header("Location: " . BASE_URL . "index.php");
    exit();
}

/**
 * DASHBOARD DATA FETCHING
 * -----------------------
 * Queries are aggregated globally to provide a system-wide overview.
 */

// A. Project Statistics (Active vs Total)
$sql_proj_stats = "SELECT 
    COUNT(*) as total_projects,
    SUM(CASE WHEN status IN ('In Progress', 'Active') THEN 1 ELSE 0 END) as active_projects
FROM icmis_projects";
$res_proj_stats = $conn->query($sql_proj_stats);
$proj_data = $res_proj_stats->fetch_assoc();
$total_projects = $proj_data['total_projects'] ?? 0;
$active_projects = $proj_data['active_projects'] ?? 0;

// B. Financial Overview (Total Budget vs Actual Spent)
// Total Budget from Projects
$sql_budget = "SELECT SUM(total_budget) as total_budget FROM icmis_projects";
$res_budget = $conn->query($sql_budget);
$total_budget = $res_budget->fetch_assoc()['total_budget'] ?? 0;

// Total Expenses (Approved only)
$sql_expenses = "SELECT SUM(amount) as total_spent FROM budget_expenses WHERE status = 'APPROVED'";
$res_expenses = $conn->query($sql_expenses);
$total_spent = $res_expenses->fetch_assoc()['total_spent'] ?? 0;

// C. Workforce (Active employees WITHOUT assignments)
// Count employees marked Active that do not have any records in workforce_assignments
$sql_staff = "SELECT COUNT(*) as total_staff
    FROM workforce_employees e
    LEFT JOIN workforce_assignments wa ON e.employee_id = wa.employee_id
    WHERE e.status = 'Active' AND wa.employee_id IS NULL";
$res_staff = $conn->query($sql_staff);
$total_staff = $res_staff->fetch_assoc()['total_staff'] ?? 0;

// D. Procurement Pending Actions (Pending POs)
$sql_po = "SELECT COUNT(*) as pending_po FROM procurement_purchase_orders WHERE status = 'PENDING'";
$res_po = $conn->query($sql_po);
$pending_po = $res_po->fetch_assoc()['pending_po'] ?? 0;

// E. Chart Data: Top 5 Projects by Budget vs Expenses
$sql_chart_financials = "SELECT 
        p.project_name, 
        p.total_budget,
        COALESCE(SUM(e.amount), 0) as total_expenses
    FROM icmis_projects p
    LEFT JOIN budget_expenses e ON p.project_id = e.project_id AND e.status = 'APPROVED'
    GROUP BY p.project_id, p.project_name, p.total_budget
    ORDER BY p.total_budget DESC
    LIMIT 5";
$res_chart_financials = $conn->query($sql_chart_financials);
$chart_labels = [];
$chart_budget = [];
$chart_expenses = [];
while ($row = $res_chart_financials->fetch_assoc()) {
    $chart_labels[] = $row['project_name'];
    $chart_budget[] = $row['total_budget'];
    $chart_expenses[] = $row['total_expenses'];
}

// F. Chart Data: Expense Category Distribution
$sql_chart_cats = "SELECT category, SUM(amount) as total FROM budget_expenses WHERE status = 'APPROVED' GROUP BY category";
$res_chart_cats = $conn->query($sql_chart_cats);
$cat_labels = [];
$cat_data = [];
while ($row = $res_chart_cats->fetch_assoc()) {
    $cat_labels[] = $row['category'];
    $cat_data[] = $row['total'];
}

// G. Recent Projects List
$sql_recent = "SELECT project_name, location, status, total_budget FROM icmis_projects ORDER BY project_id DESC LIMIT 5";
$recent_projects = $conn->query($sql_recent);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard | ICMIS</title>
    
    <?php include __DIR__ . '/includes/head_assets.php'; ?>
    
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body class="bg-slate-50 font-sans text-slate-800">

    <?php include __DIR__ . '/includes/sidebar.php'; ?>
    <?php
    // Explicitly disable project selector on dashboard page
    $show_project_selector = false;
    include __DIR__ . '/includes/header.php'; ?>

    <main class="ml-56 mt-16 p-8 min-h-screen transition-all duration-300 animate-fade-in">
        
        <div class="flex justify-between items-center mb-8 animate-fade-in">
            <div>
                <h1 class="text-3xl font-black text-navy-dark tracking-tight">System Overview</h1>
                <p class="text-slate-500 mt-1 font-medium">
                    Snapshot of Construction Operations & Financials
                </p>
            </div>
            <div class="flex items-center gap-4">
                <span class="px-4 py-2 bg-white border border-slate-200 rounded-lg text-sm font-bold text-slate-600 shadow-sm flex items-center gap-2">
                    <i class="fa-regular fa-calendar text-[#e9922c]"></i>
                    <?php echo date('F j, Y'); ?>
                </span>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8 animate-fade-in">
            
            <div class="bg-white p-6 rounded-xl shadow-sm border border-slate-100 hover:shadow-md transition-all duration-300 relative overflow-hidden group">
                <div class="absolute right-0 top-0 w-24 h-24 bg-blue-50 rounded-bl-full -mr-4 -mt-4 transition-transform group-hover:scale-110"></div>
                <div class="relative z-10">
                    <div class="text-slate-500 text-xs font-bold uppercase tracking-wider mb-2">Projects Status</div>
                    <div class="flex items-baseline gap-2">
                        <div class="text-4xl font-black text-navy-dark"><?php echo $active_projects; ?></div>
                        <div class="text-sm font-medium text-slate-400">/ <?php echo $total_projects; ?> Total</div>
                    </div>
                    <div class="text-xs text-blue-600 font-bold flex items-center gap-1 mt-1">
                        <i class="fa-solid fa-hard-hat"></i> Active Sites
                    </div>
                </div>
                <div class="absolute right-5 top-5 text-blue-500 text-xl z-10 opacity-80">
                    <i class="fa-solid fa-building"></i>
                </div>
            </div>

            <div class="bg-white p-6 rounded-xl shadow-sm border border-slate-100 hover:shadow-md transition-all duration-300 relative overflow-hidden group">
                <div class="absolute right-0 top-0 w-24 h-24 bg-orange-50 rounded-bl-full -mr-4 -mt-4 transition-transform group-hover:scale-110"></div>
                <div class="relative z-10">
                    <div class="text-slate-500 text-xs font-bold uppercase tracking-wider mb-2">Total Budget</div>
                    <div class="text-3xl font-black text-navy-dark mb-1">₱<?php echo number_format($total_budget / 1000000, 1); ?>M</div>
                    <div class="text-xs text-orange-600 font-bold flex items-center gap-1">
                        <i class="fa-solid fa-coins"></i> Spent: ₱<?php echo number_format($total_spent / 1000000, 2); ?>M
                    </div>
                </div>
                <div class="absolute right-5 top-5 text-[#e9922c] text-xl z-10 opacity-80">
                    <i class="fa-solid fa-wallet"></i>
                </div>
            </div>

            <div class="bg-white p-6 rounded-xl shadow-sm border border-slate-100 hover:shadow-md transition-all duration-300 relative overflow-hidden group">
                <div class="absolute right-0 top-0 w-24 h-24 bg-purple-50 rounded-bl-full -mr-4 -mt-4 transition-transform group-hover:scale-110"></div>
                <div class="relative z-10">
                    <div class="text-slate-500 text-xs font-bold uppercase tracking-wider mb-2">Active Workforce</div>
                    <div class="text-4xl font-black text-navy-dark mb-1"><?php echo $total_staff; ?></div>
                    <div class="text-xs text-purple-600 font-bold flex items-center gap-1">
                        <i class="fa-solid fa-users-gear"></i> Deployable Staff
                    </div>
                </div>
                <div class="absolute right-5 top-5 text-purple-500 text-xl z-10 opacity-80">
                    <i class="fa-solid fa-helmet-safety"></i>
                </div>
            </div>

            <div class="bg-white p-6 rounded-xl shadow-sm border border-slate-100 hover:shadow-md transition-all duration-300 relative overflow-hidden group">
                <div class="absolute right-0 top-0 w-24 h-24 bg-red-50 rounded-bl-full -mr-4 -mt-4 transition-transform group-hover:scale-110"></div>
                <div class="relative z-10">
                    <div class="text-slate-500 text-xs font-bold uppercase tracking-wider mb-2">Pending Purchase Orders</div>
                    <div class="text-4xl font-black text-navy-dark mb-1"><?php echo $pending_po; ?></div>
                    <div class="text-xs text-red-600 font-bold flex items-center gap-1">
                        <i class="fa-solid fa-triangle-exclamation"></i> Needs Approval
                    </div>
                </div>
                <div class="absolute right-5 top-5 text-red-500 text-xl z-10 opacity-80">
                    <i class="fa-solid fa-clipboard-check"></i>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
            <div class="lg:col-span-2 bg-white p-6 rounded-xl shadow-sm border border-slate-200">
                <div class="mb-4">
                    <h2 class="text-lg font-bold text-navy-dark">Financial Health Overview</h2>
                    <p class="text-xs text-slate-500">Budget Allocation vs. Actual Expenses (Top Projects)</p>
                </div>
                <div class="h-64">
                    <canvas id="financialChart"></canvas>
                </div>
            </div>

            <div class="bg-white p-6 rounded-xl shadow-sm border border-slate-200">
                <div class="mb-4">
                    <h2 class="text-lg font-bold text-navy-dark">Expense Distribution</h2>
                    <p class="text-xs text-slate-500">Breakdown by Cost Category</p>
                </div>
                <div class="h-64 flex justify-center">
                    <canvas id="categoryChart"></canvas>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden animate-slide-in">
            <div class="p-6 border-b border-slate-100 flex justify-between items-center bg-slate-50/50">
                <h2 class="text-lg font-bold text-navy-dark">Recent Projects</h2>
                <a href="projects.php" class="text-xs font-bold text-[#e9922c] hover:text-orange-700 uppercase tracking-wide flex items-center gap-1">
                    View All <i class="fa-solid fa-arrow-right"></i>
                </a>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left">
                    <thead class="bg-slate-50 border-b border-slate-100">
                        <tr>
                            <th class="px-6 py-4 text-xs font-extrabold text-slate-500 uppercase tracking-wider">Project Name</th>
                            <th class="px-6 py-4 text-xs font-extrabold text-slate-500 uppercase tracking-wider">Location</th>
                            <th class="px-6 py-4 text-xs font-extrabold text-slate-500 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-4 text-xs font-extrabold text-slate-500 uppercase tracking-wider text-right">Total Budget</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <?php if ($recent_projects && $recent_projects->num_rows > 0): ?>
                            <?php while($row = $recent_projects->fetch_assoc()): ?>
                            <tr class="hover:bg-orange-50/30 transition-colors duration-150">
                                <td class="px-6 py-4">
                                    <div class="text-sm font-bold text-navy-dark"><?php echo htmlspecialchars($row['project_name']); ?></div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="text-sm text-slate-600 flex items-center gap-2">
                                        <i class="fa-solid fa-location-dot text-slate-400 text-xs"></i>
                                        <?php echo htmlspecialchars($row['location']); ?>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <?php 
                                        $status = $row['status'];
                                        $statusClass = match($status) {
                                            'In Progress', 'Active' => 'bg-blue-100 text-blue-700 border border-blue-200',
                                            'Completed' => 'bg-green-100 text-green-700 border border-green-200',
                                            'Planning' => 'bg-amber-100 text-amber-700 border border-amber-200',
                                            'Cancelled' => 'bg-red-100 text-red-700 border border-red-200',
                                            default => 'bg-slate-100 text-slate-600 border border-slate-200'
                                        };
                                    ?>
                                    <span class="px-3 py-1 rounded-full text-xs font-bold shadow-sm <?php echo $statusClass; ?>">
                                        <?php echo htmlspecialchars($status); ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-sm font-mono font-bold text-slate-700 text-right">
                                    ₱<?php echo number_format($row['total_budget'], 2); ?>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="4" class="px-6 py-12 text-center text-slate-400 text-sm italic">
                                    <i class="fa-regular fa-folder-open text-2xl mb-2 block opacity-50"></i>
                                    No projects found in the system.
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </main>

    <script>
        // Setup Charts
        const ctxFinance = document.getElementById('financialChart').getContext('2d');
        const ctxCategory = document.getElementById('categoryChart').getContext('2d');

        // Data from PHP
        const projectLabels = <?php echo json_encode($chart_labels); ?>;
        const projectBudgets = <?php echo json_encode($chart_budget); ?>;
        const projectExpenses = <?php echo json_encode($chart_expenses); ?>;
        
        const catLabels = <?php echo json_encode($cat_labels); ?>;
        const catData = <?php echo json_encode($cat_data); ?>;

        // 1. Financial Chart (Bar)
        new Chart(ctxFinance, {
            type: 'bar',
            data: {
                labels: projectLabels,
                datasets: [
                    {
                        label: 'Total Budget',
                        data: projectBudgets,
                        backgroundColor: '#e9922c', // Primary Orange
                        borderRadius: 4
                    },
                    {
                        label: 'Actual Expenses',
                        data: projectExpenses,
                        backgroundColor: '#1e293b', // Navy Dark
                        borderRadius: 4
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'top' }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: { borderDash: [2, 4] }
                    },
                    x: {
                        grid: { display: false }
                    }
                }
            }
        });

        // 2. Category Chart (Doughnut)
        // If no data, provide placeholders to avoid empty chart error
        const hasData = catData.length > 0;
        new Chart(ctxCategory, {
            type: 'doughnut',
            data: {
                labels: hasData ? catLabels : ['No Expenses Yet'],
                datasets: [{
                    data: hasData ? catData : [1],
                    backgroundColor: [
                        '#3b82f6', // Blue (Materials)
                        '#10b981', // Green (Labor)
                        '#f59e0b', // Amber (Equipment)
                        '#64748b'  // Slate
                    ],
                    borderWidth: 0
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'bottom' }
                },
                cutout: '70%'
            }
        });
    </script>
</body>
</html>