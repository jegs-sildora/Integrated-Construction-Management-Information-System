<?php
require_once '../../config/config.php';
if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['user_id'])) { header("Location: " . BASE_URL . "index.php"); exit(); }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Stock Out - ICMIS</title>
    
    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
    <link href="https://fonts.googleapis.com/css2?family=Arimo:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <?php include '../../includes/head_assets.php'; ?>
    <link rel="stylesheet" href="css/style.css">

    <style>
        body { font-family: 'Arimo', sans-serif; }
        
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
                    <h1 class="text-2xl font-black text-navy-dark">Stock Issuance</h1>
                    <p class="text-slate-500 mt-1">Record items released for project use.</p>
                </div>
                <button class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-lg font-bold shadow-sm transition-colors flex items-center gap-2 cursor-pointer" onclick="openIssueModal()">
                    <i class="fa-solid fa-box-open"></i> Issue Item
                </button>
            </div>

            <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead class="bg-slate-50 border-b border-slate-200">
                            <tr>
                                <th class="px-6 py-4 text-xs font-bold text-slate-500 uppercase tracking-wider">Ref No.</th>
                                <th class="px-6 py-4 text-xs font-bold text-slate-500 uppercase tracking-wider">Item Name</th>
                                <th class="px-6 py-4 text-xs font-bold text-slate-500 uppercase tracking-wider">Quantity</th>
                                <th class="px-6 py-4 text-xs font-bold text-slate-500 uppercase tracking-wider">Unit</th>
                                <th class="px-6 py-4 text-xs font-bold text-slate-500 uppercase tracking-wider">Issued To</th>
                                <th class="px-6 py-4 text-xs font-bold text-slate-500 uppercase tracking-wider">Date Issued</th>
                                <th class="px-6 py-4 text-xs font-bold text-slate-500 uppercase tracking-wider">Notes</th>
                            </tr>
                        </thead>
                        <tbody id="stock-out-table-body" class="divide-y divide-slate-100">
                            </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>

    <div id="issueStockModal" class="hidden fixed inset-0 bg-black/60 z-50 flex items-center justify-center p-4 backdrop-blur-sm modal-overlay">
        <div class="bg-white rounded-2xl shadow-2xl max-w-md w-full transform animate-modal-slide-in">
            
            <div class="p-6 rounded-t-2xl flex justify-between items-center" style="background: linear-gradient(to right, #dc2626, #b91c1c);">
                <div class="flex items-center gap-3">
                    <div class="bg-white/20 p-2 rounded-lg">
                        <i class="fa-solid fa-dolly text-white text-xl"></i>
                    </div>
                    <h2 class="text-xl font-bold text-white">Issue Stock</h2>
                </div>
                <button onclick="closeIssueModal()" class="text-white hover:bg-white/20 p-2 rounded-lg transition-all cursor-pointer">
                    <i class="fa-solid fa-xmark text-lg"></i>
                </button>
            </div>

            <form id="issueStockForm" class="p-6 space-y-4">
                
                <div class="flex flex-col gap-1">
                    <label class="text-sm font-bold text-slate-600">Select Item</label>
                    <select id="stock_itemID" name="stock_itemID" required class="p-2 border border-slate-200 rounded-lg bg-white focus:ring-2 focus:ring-red-500 focus:outline-none">
                        <option value="" disabled selected>Loading Inventory...</option>
                    </select>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div class="flex flex-col gap-1">
                        <label class="text-sm font-bold text-slate-600">Quantity</label>
                        <input type="number" id="stock_quantity" name="stock_quantity" placeholder="0" required class="p-2 border border-slate-200 rounded-lg focus:ring-2 focus:ring-red-500 focus:outline-none">
                    </div>
                    <div class="flex flex-col gap-1">
                        <label class="text-sm font-bold text-slate-600">Unit</label>
                        <input type="text" id="stock_unit" class="bg-slate-100 p-2 border border-slate-200 rounded-lg text-slate-500" readonly>
                    </div>
                </div>

                <div class="flex flex-col gap-1">
                    <label class="text-sm font-bold text-slate-600">Issued To</label>
                    <input type="text" id="stock_issuedTo" name="stock_issuedTo" placeholder="Foreman / Department / Project" required class="p-2 border border-slate-200 rounded-lg focus:ring-2 focus:ring-red-500 focus:outline-none">
                </div>

                <div class="flex flex-col gap-1">
                    <label class="text-sm font-bold text-slate-600">Notes</label>
                    <input type="text" id="stock_notes" name="stock_notes" placeholder="Reason for issuance..." class="p-2 border border-slate-200 rounded-lg focus:ring-2 focus:ring-red-500 focus:outline-none">
                </div>

                <div class="flex justify-end gap-3 pt-4 border-t border-slate-100">
                    <button type="button" class="px-4 py-2 border border-slate-300 text-slate-700 rounded-lg hover:bg-slate-50 cursor-pointer font-medium" onclick="closeIssueModal()">Cancel</button>
                    <button type="submit" class="px-4 py-2 text-white rounded-lg font-bold shadow-md hover:shadow-lg transition-all cursor-pointer flex items-center gap-2" style="background: linear-gradient(to right, #dc2626, #b91c1c);">
                        <i class="fa-solid fa-check"></i> Confirm
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script src="js/stockout.js"></script>
</body>
</html>