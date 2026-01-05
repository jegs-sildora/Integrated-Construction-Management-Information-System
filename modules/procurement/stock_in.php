<?php
// modules/inventory/stock_in.php

require_once '../../config/config.php';
if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['user_id'])) { header("Location: " . BASE_URL . "index.php"); exit(); }

// 1. Connect to Main DB (for Project Names/Context)
require_once '../../config/database.php';

// 2. Connect to Procurement/Inventory DB (REQUIRED for checking POs)
require_once 'php/db_connect.php'; 

// ==========================================================================
// 3. SESSION BASED CONTEXT LOGIC
// ==========================================================================
$project_id = 0;

if (isset($_GET['project_id'])) {
    $project_id = intval($_GET['project_id']);
    $_SESSION['current_project_id'] = $project_id;
} 
elseif (isset($_SESSION['current_project_id'])) {
    $project_id = $_SESSION['current_project_id'];
}

// ==========================================================================
// 4. FETCH PROJECT DATA
// ==========================================================================
$project_name = 'Select Project';

if ($project_id > 0) {
    // Use Main DB Connection ($conn)
    $stmt = $conn->prepare("SELECT project_name FROM projects WHERE project_id = ?");
    $stmt->bind_param("i", $project_id);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($row = $res->fetch_assoc()) $project_name = $row['project_name'];
}

// ==========================================================================
// 5. CHECK FOR PURCHASE ORDERS (EMPTY STATE LOGIC)
// ==========================================================================
$has_orders = false;
if ($project_id > 0) {
    // Check 'icmis_procurement_inventory_db' for approved POs
    $check_sql = "SELECT COUNT(*) as count FROM icmis_procurement_inventory_db.purchase_orders WHERE project_id = ?";
    if ($stmt = $conn->prepare($check_sql)) {
        $stmt->bind_param("i", $project_id);
        $stmt->execute();
        $res = $stmt->get_result();
        $row = $res->fetch_assoc();
        if ($row['count'] > 0) {
            $has_orders = true;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Stock In - <?= htmlspecialchars($project_name) ?></title>
    <?php include '../../includes/head_assets.php'; ?>
    <link rel="stylesheet" href="css/style.css"> 
    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
</head>
<body class="bg-slate-50 font-sans text-slate-800">

    <?php include '../../includes/sidebar.php'; ?>
    <?php include '../../includes/header.php'; ?>

    <input type="hidden" id="current_project_id" value="<?= $project_id ?>">

    <main class="ml-56 pt-24 p-8 min-h-screen transition-all duration-300">
        
        <?php if ($project_id == 0): ?>
            <div class="flex flex-col items-center justify-center h-[calc(100vh-140px)]">
                <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-12 text-center max-w-lg w-full">
                    <div class="w-20 h-20 bg-slate-100 rounded-full flex items-center justify-center mx-auto mb-6">
                        <i class="fa-solid fa-folder-open text-3xl text-slate-400"></i>
                    </div>
                    <h2 class="text-xl font-bold text-slate-700 mb-2">No Project Selected</h2>
                    <p class="text-slate-500">Please select a project from the header to manage inventory.</p>
                </div>
            </div>

        <?php elseif (!$has_orders): ?>
            <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-12 h-[calc(100vh-128px)] flex items-center justify-center mt-2">
                <div class="max-w-md mx-auto text-center">
                    
                    <div class="flex justify-center mb-6">
                        <div class="bg-blue-50 rounded-full p-6">
                            <svg class="w-16 h-16 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/>
                            </svg>
                        </div>
                    </div>

                    <h2 class="text-xl text-gray-900 font-bold mb-3">No Approved Purchase Orders</h2>
                    
                    <p class="text-gray-500 mb-8 leading-relaxed">
                    Please create and approve purchase orders first to start receiving stock items and tracking inventory.
                    </p>

                    <div class="mt-8 pt-8 border-t border-gray-200">
                        <p class="text-sm text-gray-600 mb-3 text-left">To receive stock items:</p>
                        
                        <div class="space-y-3">
                            <div class="flex gap-3 bg-gray-50 rounded-lg p-3 text-left">
                                <div class="flex-shrink-0 mt-1.5">
                                    <div class="w-1.5 h-1.5 bg-[#e9922c] rounded-full"></div>
                                </div>
                                <div>
                                    <div class="text-sm text-gray-900 font-medium">Navigate to <span class="font-bold">Purchase Orders</span> section</div>
                                </div>
                            </div>

                            <div class="flex gap-3 bg-gray-50 rounded-lg p-3 text-left">
                                <div class="flex-shrink-0 mt-1.5">
                                    <div class="w-1.5 h-1.5 bg-[#e9922c] rounded-full"></div>
                                </div>
                                <div>
                                    <div class="text-sm text-gray-900 font-medium">Create a new purchase order for this project</div>
                                </div>
                            </div>

                            <div class="flex gap-3 bg-gray-50 rounded-lg p-3 text-left">
                                <div class="flex-shrink-0 mt-1.5">
                                    <div class="w-1.5 h-1.5 bg-[#e9922c] rounded-full"></div>
                                </div>
                                <div>
                                    <div class="text-sm text-gray-900 font-medium">Submit and get approval for the order</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        <?php else: ?>
            <div class="content-wrapper space-y-6">
                <div class="flex justify-between items-end bg-white p-6 rounded-xl shadow-sm border border-slate-100">
                    <div>
                        <h1 class="text-2xl font-black text-navy-dark">Stock In (Receiving)</h1>
                        <p class="text-slate-500 mt-1">Real-time view of received items for <span class="text-blue-600 font-bold"><?= htmlspecialchars($project_name) ?></span>.</p>
                    </div>
                    <button class="bg-blue-600 hover:bg-blue-700 text-white px-5 py-2.5 rounded-xl font-bold shadow-lg shadow-blue-100 transition-all flex items-center gap-2 transform hover:-translate-y-0.5 cursor-pointer" onclick="openStockModal()">
                        <i class="fa-solid fa-box-open"></i> 
                        <span>Receive Items</span>
                    </button>
                </div>

                <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
                    <div class="p-5 border-b border-slate-100 bg-slate-50/50">
                        <h3 class="font-bold text-slate-700">Recent Receiving Logs</h3>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse">
                            <thead class="bg-slate-50 border-b border-slate-200">
                                <tr>
                                    <th class="px-6 py-4 text-xs font-bold text-slate-500 uppercase text-center">Ref No.</th>
                                    <th class="px-6 py-4 text-xs font-bold text-slate-500 uppercase text-center">Item Name</th>
                                    <th class="px-6 py-4 text-xs font-bold text-slate-500 uppercase text-center">Qty Received</th>
                                    <th class="px-6 py-4 text-xs font-bold text-slate-500 uppercase text-center">Date Received</th>
                                </tr>
                            </thead>
                            <tbody id="stockin-table-body" class="divide-y divide-slate-100">
                                </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div id="stockModal" class="fixed inset-0 z-50 hidden bg-black/60 flex items-center justify-center backdrop-blur-sm transition-opacity duration-300">
                <div class="bg-white rounded-2xl shadow-2xl w-full max-w-3xl overflow-hidden transform transition-all scale-100 m-4">
                    
                    <div class="bg-blue-600 px-6 py-4 border-b border-slate-700 flex justify-between items-center">
                        <h2 class="text-lg font-bold text-white">Receive Stock - <?= htmlspecialchars($project_name) ?></h2>
                        <button type="button" class="text-slate-400 hover:text-white" onclick="closeStockModal()">
                            <i class="fa-solid fa-xmark"></i>
                        </button>
                    </div>
                    
                    <div class="p-6">
                        <div class="mb-6">
                            <label class="block text-xs font-bold text-slate-500 uppercase mb-2">Select Approved PO</label>
                            <select id="stk_po_select" class="w-full p-3 bg-white border border-slate-300 rounded-xl outline-none">
                                <option value="" disabled selected>-- Select an Approved Order --</option>
                            </select>
                        </div>

                        <div id="items_container" class="hidden">
                            <div class="border border-slate-200 rounded-xl overflow-hidden mb-6">
                                <table class="w-full text-left">
                                    <thead class="bg-slate-50 border-b border-slate-200">
                                        <tr>
                                            <th class="px-4 py-3 w-10 text-center">
                                                <input type="checkbox" id="check_all_items" checked>
                                            </th>
                                            <th class="px-4 py-3 text-xs font-bold text-slate-500 uppercase">Item</th>
                                            <th class="px-4 py-3 text-xs font-bold text-slate-500 uppercase text-right">Ordered</th>
                                            <th class="px-4 py-3 text-xs font-bold text-slate-500 uppercase text-right w-32">Receive</th>
                                        </tr>
                                    </thead>
                                    <tbody id="po_items_list" class="divide-y divide-slate-100 bg-white">
                                        </tbody>
                                </table>
                            </div>
                        </div>

                        <div class="flex justify-end gap-3 pt-2 border-t border-slate-100">
                            <button class="px-5 py-2.5 text-sm font-bold text-slate-600" onclick="closeStockModal()">Cancel</button>
                            <button id="confirm_receive_btn" class="px-5 py-2.5 text-sm font-bold text-white bg-blue-600 hover:bg-blue-700 rounded-xl">Confirm Receipt</button>
                        </div>
                    </div>
                </div>
            </div>
            
            <script src="js/stockin.js"></script>

        <?php endif; ?>
    </main>
</body>
</html>