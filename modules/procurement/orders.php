<?php
// modules/procurement/orders.php

// 1. Load Config & Session
require_once '../../config/config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 2. Security Check
if (!isset($_SESSION['user_id'])) {
    header("Location: " . BASE_URL . "index.php");
    exit();
}

// 3. Database Connections
// A. Main DB Connection (For Project Names)
require_once '../../config/database.php';
$main_conn = $conn; 

// B. Procurement DB Connection (For Orders)
include __DIR__ . '/php/db_connect.php';
$procurement_conn = $conn_proc; 

// 4. Fetch Project Map [id => name] to avoid complex cross-DB joins
$projects_map = [];
$proj_sql = "SELECT project_id, name FROM projects";
if ($proj_result = $main_conn->query($proj_sql)) {
    while($p = $proj_result->fetch_assoc()) {
        $projects_map[$p['project_id']] = $p['name'];
    }
}

// 5. Fetch KPI Statistics
$sql_kpi = "SELECT 
    COUNT(*) as total,
    SUM(CASE WHEN status = 'PENDING' THEN 1 ELSE 0 END) as pending,
    SUM(CASE WHEN status = 'APPROVED' THEN 1 ELSE 0 END) as approved, -- Added this line
    SUM(CASE WHEN status = 'COMPLETED' THEN 1 ELSE 0 END) as completed
FROM purchase_orders";
$kpi_result = $procurement_conn->query($sql_kpi);
$kpi = $kpi_result->fetch_assoc();

// 6. Fetch Orders List with Supplier Name
$sql_orders = "SELECT po.*, s.supplierName 
               FROM purchase_orders po
               LEFT JOIN suppliers s ON po.supplier_id = s.supplierID
               ORDER BY po.created_at DESC";
$result_orders = $procurement_conn->query($sql_orders);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Purchase Orders | ICMIS</title>
    
    <?php include '../../includes/head_assets.php'; ?>
    <link rel="stylesheet" href="css/style.css">

    <style>
        body { font-family: 'Inter', sans-serif; }
        
        @keyframes modalSlideIn {
            from { opacity: 0; transform: translateY(-20px) scale(0.95); }
            to { opacity: 1; transform: translateY(0) scale(1); }
        }
        .animate-modal-slide-in {
            animation: modalSlideIn 0.3s ease-out forwards;
        }
    </style>
</head>
<body class="bg-gray-50">

    <?php include '../../includes/sidebar.php'; ?>
    <?php 
        $pageTitle = "Purchase Orders";
        $pageSection = "Procurement & Inventory";
        include '../../includes/header.php'; 
    ?>
    <?php include 'purchase_order/order_modal.php'; ?>

    <main class="ml-56 pt-24 p-6 mt-4 min-h-screen transition-all duration-300">
        <div class="max-w-7xl mx-auto">
            
            <div class="flex items-center justify-between mb-8">
                <div>
                    <h1 class="text-2xl font-bold text-navy-dark">Purchase Orders</h1>
                    <p class="text-slate-500 mt-1">Manage procurement requests for projects.</p>
                </div>
                
                <a href="purchase_order/create_order.php" class="flex items-center gap-2 bg-primary hover:bg-primary-hover text-white px-6 py-3 rounded-xl font-bold shadow-lg shadow-orange-200 transition-all duration-200 transform hover:-translate-y-0.5">
                    <i class="fa-solid fa-plus text-sm"></i>
                    <span>New Purchase Request</span>
                </a>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
    
                <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-100 flex items-center justify-between group hover:shadow-md transition-all">
                    <div>
                        <h3 class="text-slate-400 text-xs font-bold uppercase tracking-widest">Total Orders</h3>
                        <h2 class="text-3xl font-black text-navy-dark mt-1"><?= number_format($kpi['total'] ?? 0) ?></h2>
                    </div>
                    <div class="w-14 h-14 bg-blue-50 text-blue-600 rounded-xl flex items-center justify-center text-2xl group-hover:scale-110 transition-transform">
                        <i class="fa-solid fa-file-invoice"></i>
                    </div>
                </div>

                <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-100 flex items-center justify-between group hover:shadow-md transition-all">
                    <div>
                        <h3 class="text-slate-400 text-xs font-bold uppercase tracking-widest">Pending</h3>
                        <h2 class="text-3xl font-black text-amber-600 mt-1"><?= number_format($kpi['pending'] ?? 0) ?></h2>
                    </div>
                    <div class="w-14 h-14 bg-amber-50 text-amber-600 rounded-xl flex items-center justify-center text-2xl group-hover:scale-110 transition-transform">
                        <i class="fa-solid fa-clock-rotate-left"></i>
                    </div>
                </div>

                <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-100 flex items-center justify-between group hover:shadow-md transition-all">
                    <div>
                        <h3 class="text-slate-400 text-xs font-bold uppercase tracking-widest">Approved</h3>
                        <h2 class="text-3xl font-black text-indigo-600 mt-1"><?= number_format($kpi['approved'] ?? 0) ?></h2>
                    </div>
                    <div class="w-14 h-14 bg-indigo-50 text-indigo-600 rounded-xl flex items-center justify-center text-2xl group-hover:scale-110 transition-transform">
                        <i class="fa-solid fa-thumbs-up"></i>
                    </div>
                </div>

                <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-100 flex items-center justify-between group hover:shadow-md transition-all">
                    <div>
                        <h3 class="text-slate-400 text-xs font-bold uppercase tracking-widest">Completed</h3>
                        <h2 class="text-3xl font-black text-green-600 mt-1"><?= number_format($kpi['completed'] ?? 0) ?></h2>
                    </div>
                    <div class="w-14 h-14 bg-green-50 text-green-600 rounded-xl flex items-center justify-center text-2xl group-hover:scale-110 transition-transform">
                        <i class="fa-solid fa-circle-check"></i>
                    </div>
                </div>

            </div>

            <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-slate-50 border-b border-slate-200">
                                <th class="px-6 py-5 text-xs font-bold text-slate-500 uppercase tracking-widest">Reference</th>
                                <th class="px-6 py-5 text-xs font-bold text-slate-500 uppercase tracking-widest">Order Details</th>
                                <th class="px-6 py-5 text-xs font-bold text-slate-500 uppercase tracking-widest">Project / Phase</th>
                                <th class="px-6 py-5 text-xs font-bold text-slate-500 uppercase tracking-widest">Supplier</th>
                                <th class="px-6 py-5 text-xs font-bold text-slate-500 uppercase tracking-widest text-right">Amount</th>
                                <th class="px-6 py-5 text-xs font-bold text-slate-500 uppercase tracking-widest">Status</th>
                                <th class="px-6 py-5 text-xs font-bold text-slate-500 uppercase tracking-widest text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <?php if ($result_orders && $result_orders->num_rows > 0): ?>
                                <?php while ($row = $result_orders->fetch_assoc()): ?>
                                    <?php 
                                        // Map Project ID to Name
                                        $projName = $projects_map[$row['project_id']] ?? 'Unknown Project';
                                        
                                        // Status Colors
                                        $status = strtoupper($row['status']);
                                        $statusClass = match($status) {
                                            'PENDING' => 'bg-amber-50 text-amber-700 border border-amber-100',
                                            'APPROVED' => 'bg-blue-50 text-blue-700 border border-blue-100',
                                            'COMPLETED' => 'bg-green-50 text-green-700 border border-green-100',
                                            'REJECTED' => 'bg-red-50 text-red-700 border border-red-100',
                                            default => 'bg-gray-100 text-gray-600'
                                        };
                                    ?>
                                    <tr class="hover:bg-slate-50 transition-colors group">
                                        <td class="px-6 py-4">
                                            <div class="font-bold text-navy-dark"><?= htmlspecialchars($row['po_reference']) ?></div>
                                            <div class="text-xs text-slate-400"><?= date('M d, Y', strtotime($row['created_at'])) ?></div>
                                        </td>

                                        <td class="px-6 py-4">
                                            <span class="font-medium text-slate-700"><?= htmlspecialchars($row['order_title']) ?></span>
                                        </td>

                                        <td class="px-6 py-4">
                                            <div class="font-bold text-xs text-blue-600 uppercase mb-1"><?= htmlspecialchars($row['phase']) ?></div>
                                            <div class="text-sm text-slate-600 truncate max-w-[200px]" title="<?= htmlspecialchars($projName) ?>">
                                                <?= htmlspecialchars($projName) ?>
                                            </div>
                                        </td>

                                        <td class="px-6 py-4">
                                            <span class="text-sm font-medium text-slate-700"><?= htmlspecialchars($row['supplierName']) ?></span>
                                        </td>

                                        <td class="px-6 py-4 text-right">
                                            <span class="font-bold text-slate-700">₱<?= number_format($row['total_amount'], 2) ?></span>
                                        </td>

                                        <td class="px-6 py-4">
                                            <span class="px-3 py-1 rounded-full text-[10px] font-bold uppercase tracking-wide <?= $statusClass ?>">
                                                <?= htmlspecialchars($status) ?>
                                            </span>
                                        </td>

                                        <td class="px-6 py-4 whitespace-nowrap text-center">
                                            <div class="flex items-center justify-center">
                                                <button onclick="openOrderModal(<?= $row['po_id'] ?>)" class="text-gray-400 p-2 rounded-lg hover:text-blue-600 hover:bg-blue-50 transition-all" title="View Details">
                                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                                    </svg>
                                                </button>

                                                <a href="purchase_order/edit_order.php?id=<?= $row['po_id'] ?>" class="text-gray-500 p-2 rounded-lg hover:text-green-600 transition-colors duration-200" title="Edit">
                                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                                    </svg>
                                                </a>

                                                <button onclick="openDeleteModal('<?= $row['po_id'] ?>', '<?= htmlspecialchars($row['po_reference']) ?>')" class="text-gray-500 p-2 rounded-lg hover:text-red-600 transition-colors duration-200" title="Delete">
                                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                    </svg>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="7" class="px-6 py-10 text-center text-slate-400 italic">
                                        No orders found. Click "New Purchase Request" to create one.
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>

    <div id="deleteModal" class="hidden fixed inset-0 bg-black/60 backdrop-blur-sm z-50 flex items-center justify-center p-4 animate-fade-in">
        <div class="bg-white rounded-2xl shadow-2xl max-w-md w-full transform transition-all animate-modal-slide-in">
            <div class="bg-gradient-to-r from-red-600 to-red-700 p-6 rounded-t-2xl">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-4">
                        <div class="bg-white rounded-full p-3 shadow-lg">
                            <svg class="w-8 h-8 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-2xl font-bold text-white">Delete Order</h3>
                            <p class="text-red-100 text-sm mt-1">Permanent action</p>
                        </div>
                    </div>
                    <button onclick="closeDeleteModal()" class="text-white hover:bg-white/10 hover:bg-opacity-60 p-2 rounded-lg transition-all">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
            </div>

            <div class="p-8">
                <div class="mb-6">
                    <p class="text-gray-700 text-lg font-medium mb-2">
                        Are you sure you want to delete this purchase order?
                    </p>
                    <p class="text-gray-500 text-sm">
                        This action is permanent and cannot be undone. All associated data will be removed.
                    </p>
                </div>
                
                <div class="bg-red-50 border-2 border-red-200 rounded-xl p-5 shadow-sm">
                    <div class="flex items-start gap-4">
                        <div class="bg-red-100 rounded-full p-2 shrink-0">
                            <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                            </svg>
                        </div>
                        <div class="flex-1">
                            <p class="text-sm font-bold text-red-900 mb-1.5 uppercase tracking-wide">Order Reference</p>
                            <p id="delete_id_display" class="text-lg font-bold text-red-700 font-mono bg-white px-3 py-2 rounded-lg border border-red-200">PO-XXXX</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="flex items-center justify-end gap-3 p-6 bg-gray-50 border-t border-gray-200 rounded-b-2xl">
                <button onclick="closeDeleteModal()" class="px-6 py-3 text-gray-700 bg-white border-2 border-gray-300 rounded-xl hover:bg-gray-100 hover:border-gray-400 transition-all font-semibold shadow-sm cursor-pointer">
                    Cancel
                </button>
                
                <form id="deleteForm" method="POST" action="php/delete_order.php">
                    <input type="hidden" name="po_id" id="delete_po_id">
                    
                    <button type="submit" class="px-6 py-3 bg-gradient-to-r from-red-600 to-red-700 hover:from-red-700 hover:to-red-800 text-white rounded-xl transition-all font-semibold flex items-center gap-2 shadow-lg hover:shadow-xl cursor-pointer">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                        </svg>
                        Delete Order
                    </button>
                </form>
            </div>
        </div>
    </div>

    <script src="js/orders.js"></script>
    <script>
        function openDeleteModal(id, reference) {
            document.getElementById('delete_id_display').innerText = reference;
            document.getElementById('delete_po_id').value = id;
            document.getElementById('deleteModal').classList.remove('hidden');
        }
        function closeDeleteModal() {
            document.getElementById('deleteModal').classList.add('hidden');
        }
    </script>
</body>
</html>