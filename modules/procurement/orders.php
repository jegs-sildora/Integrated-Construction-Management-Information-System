<?php
// modules/procurement/orders.php

require_once '../../config/config.php';
if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['user_id'])) { header("Location: " . BASE_URL . "index.php"); exit(); }

require_once '../../core/ApiHelper.php';

// Prefer procurement project context if available
if (file_exists(__DIR__ . '/project_context.php')) {
    require_once __DIR__ . '/project_context.php';
    $__ctx_project = getProjectContext();
    if ($__ctx_project && $__ctx_project > 0) {
        $_SESSION['current_project_id'] = $__ctx_project;
    }
}

// ==========================================================================
// 1. SESSION BASED CONTEXT LOGIC
// ==========================================================================
$project_id = 0;

// Priority 1: URL Parameter (Updates Session)
if (isset($_GET['project_id'])) {
    $project_id = intval($_GET['project_id']);
    $_SESSION['current_project_id'] = $project_id; 
} 
// Priority 2: Session Data (Persistence)
elseif (isset($_SESSION['current_project_id'])) {
    $project_id = $_SESSION['current_project_id'];
}

// ==========================================================================
// 2. FETCH DATA FOR DROPDOWN & CURRENT PROJECT
// ==========================================================================
$project_name = 'Select Project';
$project_code = 'N/A';
$projects_list = [];

// Fetch Current Project Info via Gateway
if ($project_id > 0) {
    $projRes = ApiHelper::get("project/projects/$project_id");
    if ($projRes['status'] === 200 && !empty($projRes['data'])) {
        $project_name = $projRes['data']['project_name'];
        $project_code = $projRes['data']['project_code'];
    }
}

// Fetch ALL Projects for Dropdown via Gateway
$allProjRes = ApiHelper::get("project/projects");
if ($allProjRes['status'] === 200) { $projects_list = $allProjRes['data']; }

// ==========================================================================
// 3. FETCH ORDERS DATA VIA GATEWAY
// ==========================================================================
$kpi = ['total' => 0, 'pending' => 0, 'approved' => 0, 'completed' => 0];
$orders_list = [];

if ($project_id > 0) {
    // Fetch KPI via Gateway
    $kpiRes = ApiHelper::get("procurement/kpi?project_id=$project_id");
    if ($kpiRes['status'] === 200) { $kpi = $kpiRes['data']; }

    // Fetch Orders List via Gateway
    $ordersRes = ApiHelper::get("procurement/orders?project_id=$project_id");
    if ($ordersRes['status'] === 200) { $orders_list = $ordersRes['data']; }
}

$pageSection = "Procurement & Inventory";
$pageTitle = "Purchase Orders";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Purchase Orders - <?= htmlspecialchars($project_name) ?></title>
    <?php include '../../includes/head_assets.php'; ?>
    <link rel="stylesheet" href="css/style.css">
</head>
<body class="bg-gray-50">
    <?php include '../../includes/sidebar.php'; ?>
    <?php include '../../includes/header.php'; ?>
    <?php include '../../includes/toast.php'; ?>
    <?php if(file_exists('purchase_order/order_modal.php')) include 'purchase_order/order_modal.php'; ?>

    <input type="hidden" id="current_project_id" value="<?= $project_id ?>">

    <main class="ml-56 pt-24 min-h-screen transition-all duration-300 animate-fade-in">
        <div class="content-wrapper space-y-6">
            
            <div class="flex justify-between items-end bg-white p-6 rounded-xl shadow-sm border border-slate-100">
                <div>
                    <h1 class="text-2xl font-black text-navy-dark">Purchase Orders</h1>
                    <p class="text-slate-500 mt-1">Manage procurement and supplier orders for <span class="text-amber-600 font-bold"><?= htmlspecialchars($project_name) ?></span>.</p>
                </div>
                
                <?php if($project_id > 0): ?>
                <a href="purchase_order/create_order.php?project_id=<?= $project_id ?>" class="flex items-center gap-2 bg-primary hover:bg-primary-hover text-white px-6 py-3 rounded-xl font-bold shadow-lg shadow-orange-200 transition-all">
                    <i class="fa-solid fa-plus text-sm"></i>
                    <span>New Purchase Request</span>
                </a>
                <?php endif; ?>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
                <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-100 flex items-center justify-between">
                    <div><h3 class="text-slate-400 text-xs font-bold uppercase">Total Orders</h3><h2 class="text-3xl font-black text-navy-dark mt-1"><?= number_format($kpi['total'] ?? 0) ?></h2></div>
                    <div class="w-14 h-14 bg-blue-50 text-blue-600 rounded-xl flex items-center justify-center text-2xl"><i class="fa-solid fa-file-invoice"></i></div>
                </div>
                <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-100 flex items-center justify-between">
                    <div><h3 class="text-slate-400 text-xs font-bold uppercase">Pending</h3><h2 class="text-3xl font-black text-amber-600 mt-1"><?= number_format($kpi['pending'] ?? 0) ?></h2></div>
                    <div class="w-14 h-14 bg-amber-50 text-amber-600 rounded-xl flex items-center justify-center text-2xl"><i class="fa-solid fa-clock-rotate-left"></i></div>
                </div>
                <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-100 flex items-center justify-between">
                    <div><h3 class="text-slate-400 text-xs font-bold uppercase">Approved</h3><h2 class="text-3xl font-black text-indigo-600 mt-1"><?= number_format($kpi['approved'] ?? 0) ?></h2></div>
                    <div class="w-14 h-14 bg-indigo-50 text-indigo-600 rounded-xl flex items-center justify-center text-2xl"><i class="fa-solid fa-thumbs-up"></i></div>
                </div>
                <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-100 flex items-center justify-between">
                    <div><h3 class="text-slate-400 text-xs font-bold uppercase">Completed</h3><h2 class="text-3xl font-black text-green-600 mt-1"><?= number_format($kpi['completed'] ?? 0) ?></h2></div>
                    <div class="w-14 h-14 bg-green-50 text-green-600 rounded-xl flex items-center justify-center text-2xl"><i class="fa-solid fa-circle-check"></i></div>
                </div>
            </div>

            <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
                <table class="w-full text-left border-collapse">
                    <thead class="bg-slate-50 border-b border-slate-200">
                        <tr>
                            <th class="px-6 py-5 text-xs font-bold text-slate-500 uppercase">Reference</th>
                            <th class="px-6 py-5 text-xs font-bold text-slate-500 uppercase">Order Details</th>
                            <th class="px-6 py-5 text-xs font-bold text-slate-500 uppercase">Supplier</th>
                            <th class="px-6 py-5 text-xs font-bold text-slate-500 uppercase text-right">Amount</th>
                            <th class="px-6 py-5 text-xs font-bold text-slate-500 uppercase">Status</th>
                            <th class="px-6 py-5 text-xs font-bold text-slate-500 uppercase text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <?php if (!empty($orders_list)): ?>
                            <?php foreach ($orders_list as $row): ?>
                                <?php 
                                    $status = strtoupper($row['status']);
                                    $statusClass = match($status) {
                                        'PENDING' => 'bg-amber-50 text-amber-700', 'APPROVED' => 'bg-blue-50 text-blue-700',
                                        'COMPLETED' => 'bg-green-50 text-green-700', 'REJECTED' => 'bg-red-50 text-red-700',
                                        default => 'bg-gray-100 text-gray-600'
                                    };
                                ?>
                                <tr class="hover:bg-slate-50 transition-colors">
                                    <td class="px-6 py-4">
                                        <div class="font-bold text-navy-dark"><?= htmlspecialchars($row['po_reference'] ?? '') ?></div>
                                        <div class="text-xs text-slate-400"><?= date('M d, Y', strtotime($row['order_date'])) ?></div>
                                    </td>
                                    <td class="px-6 py-4 font-medium text-slate-700"><?= htmlspecialchars($row['order_title'] ?? '') ?></td>
                                    <td class="px-6 py-4 text-sm font-medium text-slate-700"><?= htmlspecialchars($row['supplier_name'] ?? 'N/A') ?></td>
                                    <td class="px-6 py-4 text-right font-bold text-slate-700">₱<?= number_format($row['total_amount'] ?? 0, 2) ?></td>
                                    <td class="px-6 py-4"><span class="px-3 py-1 rounded-full text-[10px] font-bold uppercase <?= $statusClass ?>"><?= $status ?></span></td>
                                    <td class="px-6 py-4 text-center">
                                        <button onclick="openOrderModal(<?= $row['po_id'] ?>)" class="text-gray-400 hover:text-blue-600"><i class="fa-solid fa-eye"></i></button>
                                        <a href="purchase_order/edit_order.php?id=<?= $row['po_id'] ?>" class="text-gray-400 hover:text-green-600 ml-2"><i class="fa-solid fa-pen-to-square"></i></a>
                                        <button onclick="openDeleteModal('<?= $row['po_id'] ?>', '<?= htmlspecialchars($row['po_reference'] ?? '') ?>')" class="text-gray-400 hover:text-red-600 ml-2"><i class="fa-solid fa-trash"></i></button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="6" class="px-6 py-12 text-center text-slate-400 italic">
                                <?= ($project_id > 0) ? 'No orders found.' : 'Please select a project above.' ?>
                            </td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>
    
    <?php include 'purchase_order/delete_modal_partial.php'; ?>
    <script src="js/orders.js"></script>
</body>
</html>