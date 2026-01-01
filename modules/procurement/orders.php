<?php
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
    <title>Purchase Orders - ICMIS</title>
    
    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
    <link href="https://fonts.googleapis.com/css2?family=Arimo:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <?php include '../../includes/head_assets.php'; ?>
    <link rel="stylesheet" href="css/style.css">

    <style>
        body { font-family: 'Arimo', sans-serif; }
        
        /* Modal Animation & Stacking */
        .modal-overlay { z-index: 50; }
        
        @keyframes modalSlideIn {
            from { opacity: 0; transform: translateY(-20px) scale(0.95); }
            to { opacity: 1; transform: translateY(0) scale(1); }
        }
        .animate-modal-slide-in {
            animation: modalSlideIn 0.3s ease-out forwards;
        }
    </style>
</head>
<body class="bg-slate-50 text-slate-800">

    <?php include '../../includes/sidebar.php'; ?>
    <?php include '../../includes/header.php'; ?>

    <main class="ml-56 pt-24 p-8 min-h-screen transition-all duration-300">
        
        <div class="content-wrapper space-y-6">
            <div class="flex justify-between items-end bg-white p-6 rounded-xl shadow-sm border border-slate-100">
                <div>
                    <h1 class="text-2xl font-black text-navy-dark">Purchase Orders</h1>
                    <p class="text-slate-500 mt-1">Manage and track all purchase requests.</p>
                </div>
                <button class="bg-primary hover:bg-primary-hover text-white px-4 py-2 rounded-lg font-bold shadow-sm transition-colors flex items-center gap-2 cursor-pointer" onclick="goToNewPurchase()">
                    <i class="fa-solid fa-plus"></i> New Request
                </button>
            </div>

            <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead class="bg-slate-50 border-b border-slate-200">
                            <tr>
                                <th class="px-6 py-4 text-xs font-bold text-slate-500 uppercase tracking-wider min-w-[140px]">Order ID</th>
                                <th class="px-6 py-4 text-xs font-bold text-slate-500 uppercase tracking-wider">Qty</th> 
                                <th class="px-6 py-4 text-xs font-bold text-slate-500 uppercase tracking-wider">Item Name</th>      
                                <th class="px-6 py-4 text-xs font-bold text-slate-500 uppercase tracking-wider">Brand / Model</th>  
                                <th class="px-6 py-4 text-xs font-bold text-slate-500 uppercase tracking-wider">Location</th>
                                <th class="px-6 py-4 text-xs font-bold text-slate-500 uppercase tracking-wider">Total Cost</th>
                                <th class="px-6 py-4 text-xs font-bold text-slate-500 uppercase tracking-wider">Status</th>
                                <th class="px-6 py-4 text-xs font-bold text-slate-500 uppercase tracking-wider">Supplier</th>
                                <th class="px-6 py-4 text-xs font-bold text-slate-500 uppercase tracking-wider">Date</th>
                                <th class="px-6 py-4 text-xs font-bold text-slate-500 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody id="order-table-body" class="divide-y divide-slate-100">
                            </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>

    <div id="addModal" class="hidden fixed inset-0 bg-black/60 z-50 flex items-center justify-center p-4 backdrop-blur-sm modal-overlay">
        <div class="bg-white rounded-2xl shadow-2xl max-w-2xl w-full transform animate-modal-slide-in">
            <div class="bg-slate-50 px-6 py-4 border-b border-slate-100 flex justify-between items-center rounded-t-2xl">
                <h2 class="text-xl font-bold text-navy-dark">New Purchase Request</h2>
                <button type="button" class="text-slate-400 hover:text-red-500 transition-colors text-2xl cursor-pointer" onclick="closeModal()">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>

            <form id="addOrderForm" class="p-6 space-y-4">
                <div class="grid grid-cols-2 gap-4">
                    <div class="flex flex-col gap-1">
                        <label class="text-sm font-bold text-slate-600">Order ID</label>
                        <input type="text" id="input_orderID" name="input_orderID" class="bg-slate-100 p-2 border border-slate-200 rounded-lg" readonly>
                    </div>
                    <div class="flex flex-col gap-1">
                        <label class="text-sm font-bold text-slate-600">Order Date</label>
                        <input type="date" id="input_date" name="input_date" required class="p-2 border border-slate-200 rounded-lg focus:ring-2 focus:ring-blue-500">
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div class="flex flex-col gap-1">
                        <label class="text-sm font-bold text-slate-600">Item Name</label>
                        <input type="text" id="input_itemName" name="input_itemName" placeholder="e.g. Office Chair" required class="p-2 border border-slate-200 rounded-lg focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div class="flex flex-col gap-1">
                        <label class="text-sm font-bold text-slate-600">Subtext / Brand</label>
                        <input type="text" id="input_itemSubtext" name="input_itemSubtext" placeholder="e.g. Ergonomic Black" class="p-2 border border-slate-200 rounded-lg focus:ring-2 focus:ring-blue-500">
                    </div>
                </div>

                <div class="grid grid-cols-3 gap-4">
                    <div class="flex flex-col gap-1">
                        <label class="text-sm font-bold text-slate-600">Quantity</label>
                        <input type="number" id="input_quantity" name="input_quantity" placeholder="0" required class="p-2 border border-slate-200 rounded-lg focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div class="flex flex-col gap-1">
                        <label class="text-sm font-bold text-slate-600">Unit</label>
                        <select id="input_unit" name="input_unit" required class="p-2 border border-slate-200 rounded-lg bg-white focus:ring-2 focus:ring-blue-500">
                            <option value="pcs">pcs</option>
                            <option value="sets">sets</option>
                            <option value="boxes">boxes</option>
                            <option value="units">units</option>
                            <option value="gals">gals</option>
                            <option value="bags">bags</option>
                        </select>
                    </div>
                    <div class="flex flex-col gap-1">
                        <label class="text-sm font-bold text-slate-600">Total Cost (₱)</label>
                        <input type="number" step="0.01" id="input_cost" name="input_cost" placeholder="0.00" required class="p-2 border border-slate-200 rounded-lg focus:ring-2 focus:ring-blue-500">
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div class="flex flex-col gap-1">
                        <label class="text-sm font-bold text-slate-600">Supplier</label>
                        <select id="input_supplier" name="input_supplier" required class="p-2 border border-slate-200 rounded-lg bg-white focus:ring-2 focus:ring-blue-500">
                            <option value="" disabled selected>Loading...</option>
                        </select>
                    </div>
                    <div class="flex flex-col gap-1">
                        <label class="text-sm font-bold text-slate-600">Location / Department</label>
                        <input type="text" id="input_location" name="input_location" placeholder="e.g. IT Dept" class="p-2 border border-slate-200 rounded-lg focus:ring-2 focus:ring-blue-500">
                    </div>
                </div>

                <div class="flex flex-col gap-1">
                    <label class="text-sm font-bold text-slate-600">Status</label>
                    <select id="input_status" name="input_status" class="p-2 border border-slate-200 rounded-lg bg-white focus:ring-2 focus:ring-blue-500">
                        <option value="Pending">Pending</option>
                        <option value="Approved">Approved</option>
                        <option value="Rejected">Rejected</option>
                    </select>
                </div>

                <div class="pt-4 border-t border-slate-100 flex justify-end gap-3">
                    <button type="button" class="px-4 py-2 border border-slate-300 text-slate-700 rounded-lg hover:bg-slate-50 cursor-pointer" onclick="closeModal()">Cancel</button>
                    <button type="submit" class="px-4 py-2 text-white rounded-lg font-bold cursor-pointer shadow-md bg-primary hover:bg-primary-hover">Save Order</button>
                </div>
            </form>
        </div>
    </div>

    <div id="deleteModal" class="hidden fixed inset-0 bg-black/60 z-50 flex items-center justify-center p-4 backdrop-blur-sm modal-overlay">
        <div class="bg-white rounded-2xl shadow-2xl max-w-md w-full transform animate-modal-slide-in">
            <div class="p-6 rounded-t-2xl" style="background: linear-gradient(to right, #dc2626, #b91c1c);">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-4">
                        <div class="bg-white rounded-full p-3 shadow-lg">
                            <i class="fa-solid fa-triangle-exclamation text-2xl text-red-600"></i>
                        </div>
                        <div>
                            <h3 class="text-2xl font-bold text-white">Delete Order</h3>
                            <p class="text-red-100 text-sm mt-1">Permanent action</p>
                        </div>
                    </div>
                    <button onclick="closeDeleteModal()" class="text-white hover:bg-white/10 hover:bg-opacity-60 p-2 rounded-lg transition-all cursor-pointer">
                        <i class="fa-solid fa-xmark text-xl"></i>
                    </button>
                </div>
            </div>

            <div class="p-8">
                <div class="mb-6">
                    <p class="text-gray-700 text-lg font-medium mb-2">Are you sure you want to delete this order?</p>
                    <p class="text-gray-500 text-sm">This action is permanent and cannot be undone.</p>
                </div>
                
                <div class="bg-red-50 border-2 border-red-200 rounded-xl p-5 shadow-sm">
                    <div class="flex items-start gap-4">
                        <div class="bg-red-100 rounded-full p-2 shrink-0">
                            <i class="fa-solid fa-tag text-red-600"></i>
                        </div>
                        <div class="flex-1">
                            <p class="text-sm font-bold text-red-900 mb-1.5 uppercase tracking-wide">Order ID</p>
                            <p id="delete_id_display" class="text-lg font-bold text-red-700 font-mono bg-white px-3 py-2 rounded-lg border border-red-200">PO-XXXX</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="flex items-center justify-end gap-3 p-6 bg-gray-50 border-t border-gray-200 rounded-b-2xl">
                <button onclick="closeDeleteModal()" class="px-6 py-3 text-gray-700 bg-white border-2 border-gray-300 rounded-xl hover:bg-gray-100 hover:border-gray-400 transition-all font-semibold shadow-sm cursor-pointer">
                    Cancel
                </button>
                <button id="confirmDeleteBtn" class="px-6 py-3 text-white rounded-xl transition-all font-semibold flex items-center gap-2 shadow-lg hover:shadow-xl cursor-pointer" style="background: linear-gradient(to right, #dc2626, #b91c1c);">
                    <i class="fa-solid fa-trash-can"></i>
                    Delete Order
                </button>
            </div>
        </div>
    </div>

    <script src="js/orders.js"></script>
</body>
</html>