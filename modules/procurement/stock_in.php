<?php
// modules/inventory/stock_in.php

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
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Stock In | ICMIS</title>
    
    <?php include '../../includes/head_assets.php'; ?>
    <link rel="stylesheet" href="css/style.css"> 
</head>
<body class="bg-slate-50 font-sans text-slate-800">

    <?php include '../../includes/sidebar.php'; ?>
    <?php 
        $pageTitle = "Inventory Management";
        $pageSubTitle = "Stock In (Receiving)";
        include '../../includes/header.php'; 
    ?>

    <main class="ml-56 pt-24 p-8 min-h-screen transition-all duration-300">
        
        <div class="content-wrapper space-y-6">
            <div class="flex justify-between items-end bg-white p-6 rounded-xl shadow-sm border border-slate-100">
                <div>
                    <h1 class="text-2xl font-black text-navy-dark">Stock In (Receiving)</h1>
                    <p class="text-slate-500 mt-1">Receive items from Approved Purchase Orders.</p>
                </div>
                <button class="bg-primary hover:bg-primary-hover text-white px-5 py-2.5 rounded-xl font-bold shadow-lg shadow-blue-100 transition-all flex items-center gap-2 transform hover:-translate-y-0.5" onclick="openStockModal()">
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
                                <th class="px-6 py-4 text-xs font-bold text-slate-500 uppercase tracking-wider text-center">Log ID</th>
                                <th class="px-6 py-4 text-xs font-bold text-slate-500 uppercase tracking-wider text-center">PO Reference</th>
                                <th class="px-6 py-4 text-xs font-bold text-slate-500 uppercase tracking-wider text-center">Item Name</th>
                                <th class="px-6 py-4 text-xs font-bold text-slate-500 uppercase tracking-wider text-center">Qty Received</th>
                                <th class="px-6 py-4 text-xs font-bold text-slate-500 uppercase tracking-wider text-center">Date Received</th>
                                <th class="px-6 py-4 text-xs font-bold text-slate-500 uppercase tracking-wider text-center">Received By</th>
                            </tr>
                        </thead>
                        <tbody id="stockin-table-body" class="divide-y divide-slate-100">
                            </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>

    <div id="stockModal" class="fixed inset-0 z-50 hidden bg-black/60 flex items-center justify-center backdrop-blur-sm transition-opacity duration-300">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-3xl overflow-hidden transform transition-all scale-100 m-4">
            
            <div class="bg-gradient-to-r from-navy-dark to-slate-800 px-6 py-4 border-b border-slate-700 flex justify-between items-center">
                <div class="flex items-center gap-3">
                    <div class="bg-white/10 p-2 rounded-lg text-white">
                        <i class="fa-solid fa-dolly"></i>
                    </div>
                    <div>
                        <h2 class="text-lg font-bold text-white">Receive Stock</h2>
                        <p class="text-slate-400 text-xs">Select a PO to view ordered items</p>
                    </div>
                </div>
                <button type="button" class="text-slate-400 hover:text-white transition-colors text-xl" onclick="closeStockModal()">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>

            <div class="p-6 max-h-[80vh] overflow-y-auto custom-scrollbar">
                
                <div class="mb-6">
                    <label class="block text-xs font-bold text-slate-500 uppercase mb-2">Select Approved PO</label>
                    <select id="stk_po_select" class="w-full p-3 bg-white border border-slate-300 text-slate-700 font-medium rounded-xl focus:ring-2 focus:ring-primary focus:border-primary outline-none transition-shadow">
                        <option value="" disabled selected>-- Select an Approved Order --</option>
                    </select>
                </div>

                <div id="items_container" class="hidden animate-fade-in">
                    <div class="flex justify-between items-center mb-3">
                        <h3 class="text-sm font-bold text-navy-dark uppercase">Items in this Order</h3>
                        <span class="text-xs text-slate-500 italic">* Uncheck items you are NOT receiving today</span>
                    </div>

                    <div class="border border-slate-200 rounded-xl overflow-hidden mb-6">
                        <table class="w-full text-left">
                            <thead class="bg-slate-50 border-b border-slate-200">
                                <tr>
                                    <th class="px-4 py-3 w-10 text-center"><input type="checkbox" id="check_all_items" checked class="rounded border-slate-300 text-primary focus:ring-primary"></th>
                                    <th class="px-4 py-3 text-xs font-bold text-slate-500 uppercase">Item Name</th>
                                    <th class="px-4 py-3 text-xs font-bold text-slate-500 uppercase text-right">Ordered</th>
                                    <th class="px-4 py-3 text-xs font-bold text-slate-500 uppercase text-right w-32">Receive Qty</th>
                                </tr>
                            </thead>
                            <tbody id="po_items_list" class="divide-y divide-slate-100 bg-white">
                                </tbody>
                        </table>
                    </div>
                    
                    <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-3 flex gap-3 mb-6">
                        <i class="fa-solid fa-circle-info text-yellow-600 mt-0.5"></i>
                        <div class="text-xs text-yellow-800">
                            <strong>Note:</strong> Items received will be added to current inventory. The Purchase Order status will be updated to <b>COMPLETED</b> if all items are fully received.
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4 pt-2 border-t border-slate-100">
                    <div>
                        <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Received By</label>
                        <input type="text" id="stk_user_display" value="<?php echo htmlspecialchars($_SESSION['user_name'] ?? 'Admin'); ?>" class="w-full p-2.5 bg-slate-100 border border-slate-200 text-slate-600 font-bold rounded-lg cursor-not-allowed" readonly>
                        <input type="hidden" id="stk_user_id" value="<?php echo $_SESSION['user_id'] ?? 1; ?>">
                    </div>
                    <div class="flex items-end justify-end gap-3">
                        <button type="button" class="px-5 py-2.5 text-sm font-bold text-slate-600 hover:bg-slate-100 rounded-xl transition-colors" onclick="closeStockModal()">Cancel</button>
                        <button id="confirm_receive_btn" type="button" class="px-5 py-2.5 text-sm font-bold text-white bg-primary hover:bg-primary-hover rounded-xl shadow-md transition-colors disabled:opacity-50 disabled:cursor-not-allowed">
                            Confirm Receipt
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="js/stockin.js"></script>
    
</body>
</html>