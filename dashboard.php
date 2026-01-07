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

// Ensure project context is applied to top-level pages
// Include project context helper (uses DB constants from config)
require_once __DIR__ . '/modules/workforce/project_context.php';
$ctxConn = getWorkforceConnection();
$selected_project_id = getProjectContext($ctxConn);
// If a project context exists but URL doesn't include project_id, redirect to attach it
if (!isset($_GET['project_id']) && !empty($selected_project_id)) {
    header('Location: ' . BASE_URL . 'dashboard.php?project_id=' . intval($selected_project_id));
    exit();
}

// // 3. Data Fetching for Dashboard Widgets
// // A. Active Projects
// $sql_proj = "SELECT COUNT(*) as total FROM projects WHERE status = 'ONGOING'";
// $res_proj = $conn->query($sql_proj);
// $active_projects = $res_proj->fetch_assoc()['total'] ?? 0;

// // B. Total Budget (Sum)
// $sql_budget = "SELECT SUM(budget) as total FROM projects";
// $res_budget = $conn->query($sql_budget);
// $total_budget = $res_budget->fetch_assoc()['total'] ?? 0;

// // C. Total Staff
// $sql_staff = "SELECT COUNT(*) as total FROM users WHERE role != 'admin'";
// $res_staff = $conn->query($sql_staff);
// $total_staff = $res_staff->fetch_assoc()['total'] ?? 0;

// // D. Recent Projects List
// $sql_recent = "SELECT name, location, status, budget FROM projects ORDER BY created_at DESC LIMIT 5";
// $recent_projects = $conn->query($sql_recent);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="apple-touch-icon" sizes="180x180" href="<?php echo BASE_URL; ?>assets/images/favicon/apple-touch-icon.png">
    <link rel="icon" type="image/png" sizes="32x32" href="<?php echo BASE_URL; ?>assets/images/favicon/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="<?php echo BASE_URL; ?>assets/images/favicon/favicon-16x16.png">
    <link rel="manifest" href="<?php echo BASE_URL; ?>assets/images/favicon/site.webmanifest">
    <title>Dashboard | ICMIS</title>
    
    <?php include __DIR__ . '/includes/head_assets.php'; ?>
</head>
<body class="bg-slate-50 font-sans text-slate-800">

    <?php include __DIR__ . '/includes/sidebar.php'; ?>

    <main class="ml-56 p-8 min-h-screen transition-all duration-300">
        
        <header class="flex justify-between items-center mb-8 animate-fade-in">
            <div>
                <h1 class="text-3xl font-black text-navy-dark tracking-tight">Dashboard Overview</h1>
                <p class="text-slate-500 mt-1 font-medium">
                    Welcome back, <span class="text-[#e9922c] font-bold"><?php echo htmlspecialchars($_SESSION['user_name'] ?? 'User'); ?></span>
                </p>
            </div>
            <div class="flex items-center gap-4">
                <span class="px-4 py-2 bg-white border border-slate-200 rounded-lg text-sm font-bold text-slate-600 shadow-sm flex items-center gap-2">
                    <i class="fa-regular fa-calendar text-[#e9922c]"></i>
                    <?php echo date('F j, Y'); ?>
                </span>
            </div>
        </header>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8 animate-fade-in">
            
            <div class="bg-white p-6 rounded-xl shadow-sm border border-slate-100 hover:shadow-md transition-all duration-300 relative overflow-hidden group">
                <div class="absolute right-0 top-0 w-24 h-24 bg-blue-50 rounded-bl-full -mr-4 -mt-4 transition-transform group-hover:scale-110"></div>
                <div class="relative z-10">
                    <div class="text-slate-500 text-xs font-bold uppercase tracking-wider mb-2">Active Projects</div>
                    <div class="text-4xl font-black text-navy-dark mb-1"><?php echo $active_projects; ?></div>
                    <div class="text-xs text-green-600 font-bold flex items-center gap-1">
                        <i class="fa-solid fa-arrow-trend-up"></i> Operations Running
                    </div>
                </div>
                <div class="absolute right-5 top-5 text-blue-500 text-xl z-10 opacity-80">
                    <i class="fa-solid fa-building-columns"></i>
                </div>
            </div>

            <div class="bg-white p-6 rounded-xl shadow-sm border border-slate-100 hover:shadow-md transition-all duration-300 relative overflow-hidden group">
                <div class="absolute right-0 top-0 w-24 h-24 bg-orange-50 rounded-bl-full -mr-4 -mt-4 transition-transform group-hover:scale-110"></div>
                <div class="relative z-10">
                    <div class="text-slate-500 text-xs font-bold uppercase tracking-wider mb-2">Total Budget</div>
                    <div class="text-4xl font-black text-navy-dark mb-1">₱<?php echo number_format($total_budget / 1000000, 1); ?>M</div>
                    <div class="text-xs text-orange-600 font-bold flex items-center gap-1">
                        <i class="fa-solid fa-chart-pie"></i> Allocated Funds
                    </div>
                </div>
                <div class="absolute right-5 top-5 text-[#e9922c] text-xl z-10 opacity-80">
                    <i class="fa-solid fa-wallet"></i>
                </div>
            </div>

            <div class="bg-white p-6 rounded-xl shadow-sm border border-slate-100 hover:shadow-md transition-all duration-300 relative overflow-hidden group">
                <div class="absolute right-0 top-0 w-24 h-24 bg-purple-50 rounded-bl-full -mr-4 -mt-4 transition-transform group-hover:scale-110"></div>
                <div class="relative z-10">
                    <div class="text-slate-500 text-xs font-bold uppercase tracking-wider mb-2">Total Staff</div>
                    <div class="text-4xl font-black text-navy-dark mb-1"><?php echo $total_staff; ?></div>
                    <div class="text-xs text-purple-600 font-bold flex items-center gap-1">
                        <i class="fa-solid fa-users"></i> Registered Users
                    </div>
                </div>
                <div class="absolute right-5 top-5 text-purple-500 text-xl z-10 opacity-80">
                    <i class="fa-solid fa-helmet-safety"></i>
                </div>
            </div>

            <div class="bg-white p-6 rounded-xl shadow-sm border border-slate-100 hover:shadow-md transition-all duration-300 relative overflow-hidden group">
                <div class="absolute right-0 top-0 w-24 h-24 bg-teal-50 rounded-bl-full -mr-4 -mt-4 transition-transform group-hover:scale-110"></div>
                <div class="relative z-10">
                    <div class="text-slate-500 text-xs font-bold uppercase tracking-wider mb-2">System Status</div>
                    <div class="text-4xl font-black text-navy-dark mb-1">Online</div>
                    <div class="text-xs text-teal-600 font-bold flex items-center gap-1">
                        <i class="fa-solid fa-check-circle"></i> All Systems Go
                    </div>
                </div>
                <div class="absolute right-5 top-5 text-teal-500 text-xl z-10 opacity-80">
                    <i class="fa-solid fa-server"></i>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden animate-slide-in">
            <div class="p-6 border-b border-slate-100 flex justify-between items-center">
                <h2 class="text-lg font-bold text-navy-dark">Recent Projects</h2>
                <a href="projects.php" class="text-xs font-bold text-[#e9922c] hover:text-orange-700 uppercase tracking-wide">View All</a>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="px-6 py-4 text-xs font-bold text-slate-500 uppercase tracking-wider">Project Name</th>
                            <th class="px-6 py-4 text-xs font-bold text-slate-500 uppercase tracking-wider">Location</th>
                            <th class="px-6 py-4 text-xs font-bold text-slate-500 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-4 text-xs font-bold text-slate-500 uppercase tracking-wider text-right">Budget</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <?php if ($recent_projects && $recent_projects->num_rows > 0): ?>
                            <?php while($row = $recent_projects->fetch_assoc()): ?>
                            <tr class="hover:bg-slate-50 transition-colors">
                                <td class="px-6 py-4 text-sm font-bold text-navy-dark">
                                    <?php echo htmlspecialchars($row['name']); ?>
                                </td>
                                <td class="px-6 py-4 text-sm text-slate-600">
                                    <i class="fa-solid fa-location-dot text-slate-400 mr-2"></i>
                                    <?php echo htmlspecialchars($row['location']); ?>
                                </td>
                                <td class="px-6 py-4">
                                    <?php 
                                        $statusClass = match($row['status']) {
                                            'ONGOING' => 'bg-blue-100 text-blue-700',
                                            'COMPLETED' => 'bg-green-100 text-green-700',
                                            'PLANNING' => 'bg-amber-100 text-amber-700',
                                            default => 'bg-slate-100 text-slate-600'
                                        };
                                    ?>
                                    <span class="px-2.5 py-1 rounded-full text-xs font-bold <?php echo $statusClass; ?>">
                                        <?php echo htmlspecialchars($row['status']); ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-sm font-mono font-bold text-slate-700 text-right">
                                    ₱<?php echo number_format($row['budget']); ?>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="4" class="px-6 py-8 text-center text-slate-500 text-sm">
                                    No projects found in the system.
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </main>

</body>
</html>