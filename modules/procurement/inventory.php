<?php
// modules/inventory/inventory.php

require_once '../../config/config.php';
if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['user_id'])) { header("Location: " . BASE_URL . "index.php"); exit(); }
require_once '../../config/database.php';

// ==========================================================================
// 1. SESSION BASED CONTEXT LOGIC
// ==========================================================================
$project_id = 0;

if (isset($_GET['project_id'])) {
    $project_id = intval($_GET['project_id']); // Allow 0 for "All Projects"
    $_SESSION['current_project_id'] = $project_id;
} 
elseif (isset($_SESSION['current_project_id'])) {
    $project_id = $_SESSION['current_project_id'];
}

// ==========================================================================
// 2. FETCH DATA
// ==========================================================================
$project_name = 'All Projects (Global View)';
$projects_list = [];

if ($project_id > 0) {
    $stmt = $conn->prepare("SELECT project_name FROM projects WHERE project_id = ?");
    $stmt->bind_param("i", $project_id);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($row = $res->fetch_assoc()) $project_name = $row['project_name'];
}

// Fetch All Projects for Dropdown
$sql_all = "SELECT project_id, project_name FROM projects ORDER BY created_at DESC";
$res_all = $conn->query($sql_all);
if ($res_all) { while($p = $res_all->fetch_assoc()) $projects_list[] = $p; }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inventory - <?= htmlspecialchars($project_name) ?></title>
    <?php include '../../includes/head_assets.php'; ?>
    <link rel="stylesheet" href="css/style.css"> 
    <style>
        @media print {
            .no-print { display: none !important; }
            .print-only { display: block !important; }
        }
    </style>
</head>
<body class="bg-slate-50 font-sans text-slate-800">

    <div class="no-print">
        <?php include '../../includes/sidebar.php'; ?>
        <?php include '../../includes/header.php'; ?>
    </div>
    
    <input type="hidden" id="current_project_id" value="<?= $project_id ?>">

    <main class="ml-56 pt-24 p-8 min-h-screen transition-all duration-300">
        
        <div class="print-only hidden mb-6">
            <div class="text-center border-b pb-4 mb-4">
                <h1 class="text-2xl font-bold uppercase">Inventory Masterlist</h1>
                <h2 class="text-lg"><?= htmlspecialchars($project_name) ?></h2>
                <p class="text-xs mt-1">Generated: <span id="print-date"></span></p>
            </div>
        </div>

        <div class="content-wrapper space-y-6">
            <div class="flex justify-between items-end bg-white p-6 rounded-xl shadow-sm border border-slate-100 no-print">
                <div>
                    <h1 class="text-2xl font-black text-navy-dark">Inventory Masterlist</h1>
                    <p class="text-slate-500 mt-1">Real-time view of stock levels for <span class="text-amber-600 font-bold"><?= htmlspecialchars($project_name) ?></span>.</p>
                </div>
                <button class="bg-white border border-slate-200 text-slate-700 hover:bg-slate-50 px-4 py-2 rounded-lg font-medium shadow-sm transition-colors flex items-center gap-2" onclick="printReport()">
                    <i class="fa-solid fa-print"></i> Print Report
                </button>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 no-print">
                <div class="bg-white p-6 rounded-xl shadow-sm border border-slate-100 flex items-center justify-between">
                    <div><h3 class="text-slate-500 text-xs font-bold uppercase">Total Items</h3><h2 id="total-items-count" class="text-3xl font-black text-navy-dark mt-1">0</h2></div>
                    <div class="w-12 h-12 bg-blue-50 text-blue-600 rounded-lg flex items-center justify-center text-xl"><i class="fa-solid fa-layer-group"></i></div>
                </div>
                <div class="bg-white p-6 rounded-xl shadow-sm border border-slate-100 flex items-center justify-between">
                    <div><h3 class="text-slate-500 text-xs font-bold uppercase">Total Valuation</h3><h2 id="total-value-count" class="text-3xl font-black text-green-600 mt-1">₱0.00</h2></div>
                    <div class="w-12 h-12 bg-green-50 text-green-600 rounded-lg flex items-center justify-center text-xl"><i class="fa-solid fa-sack-dollar"></i></div>
                </div>
                <div class="bg-white p-6 rounded-xl shadow-sm border border-red-100 flex items-center justify-between">
                    <div><h3 class="text-red-500 text-xs font-bold uppercase">Low Stock</h3><h2 id="low-stock-count" class="text-3xl font-black text-red-600 mt-1">0</h2></div>
                    <div class="w-12 h-12 bg-red-50 text-red-600 rounded-lg flex items-center justify-center text-xl"><i class="fa-solid fa-triangle-exclamation"></i></div>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead class="bg-slate-50 border-b border-slate-200">
                            <tr>
                                <th class="px-6 py-4 text-xs font-bold text-slate-500 uppercase text-center">Item Name</th>
                                <th class="px-6 py-4 text-xs font-bold text-slate-500 uppercase text-center">Category</th>
                                <th class="px-6 py-4 text-xs font-bold text-slate-500 uppercase text-center">Stock Level</th>
                                <th class="px-6 py-4 text-xs font-bold text-slate-500 uppercase text-center">Unit Cost</th>
                                <th class="px-6 py-4 text-xs font-bold text-slate-500 uppercase text-center">Total Value</th>
                                <th class="px-6 py-4 text-xs font-bold text-slate-500 uppercase text-center">Status</th>
                            </tr>
                        </thead>
                        <tbody id="inventory-table-body" class="divide-y divide-slate-100">
                            </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>

    <script src="js/inventory.js"></script>
    <script>
        function printReport() {
            const dateEl = document.getElementById('print-date');
            if(dateEl) dateEl.innerText = new Date().toLocaleString();
            window.print();
        }
    </script>
</body>
</html>