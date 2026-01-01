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
    <title>Stock In - ICMIS</title>
    
    <?php include '../../includes/head_assets.php'; ?>
    <link rel="stylesheet" href="css/style.css"> 
</head>
<body class="bg-slate-50 font-sans text-slate-800">

    <?php include '../../includes/sidebar.php'; ?>

    <?php include '../../includes/header.php'; ?>

    <main class="ml-56 pt-24 p-8 min-h-screen transition-all duration-300">
        
        <div class="content-wrapper space-y-6">
            <div class="flex justify-between items-end bg-white p-6 rounded-xl shadow-sm border border-slate-100">
                <div>
                    <h1 class="text-2xl font-black text-navy-dark">Stock In (Receiving)</h1>
                    <p class="text-slate-500 mt-1">Process incoming items from approved Purchase Orders.</p>
                </div>
                <button class="bg-primary hover:bg-primary-hover text-white px-4 py-2 rounded-lg font-bold shadow-sm transition-colors flex items-center gap-2" onclick="openStockModal()">
                    <i class="fa-solid fa-box-open"></i> Receive Stock
                </button>
            </div>

            <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead class="bg-slate-50 border-b border-slate-200">
                            <tr>
                                <th class="px-6 py-4 text-xs font-bold text-slate-500 uppercase tracking-wider">Reference No.</th>
                                <th class="px-6 py-4 text-xs font-bold text-slate-500 uppercase tracking-wider">PO Source</th>
                                <th class="px-6 py-4 text-xs font-bold text-slate-500 uppercase tracking-wider">Item Name</th>
                                <th class="px-6 py-4 text-xs font-bold text-slate-500 uppercase tracking-wider">Qty Received</th>
                                <th class="px-6 py-4 text-xs font-bold text-slate-500 uppercase tracking-wider">Date Received</th>
                                <th class="px-6 py-4 text-xs font-bold text-slate-500 uppercase tracking-wider">Received By</th>
                            </tr>
                        </thead>
                        <tbody id="stockin-table-body" class="divide-y divide-slate-100">
                            </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>

    <div id="stockModal" class="fixed inset-0 z-50 hidden bg-black/50 flex items-center justify-center backdrop-blur-sm transition-opacity duration-300">
        <div class="bg-white rounded-xl shadow-2xl w-full max-w-md overflow-hidden transform transition-all scale-100">
            <div class="bg-slate-50 px-6 py-4 border-b border-slate-100 flex justify-between items-center">
                <h2 class="text-lg font-bold text-navy-dark">Receive Stock</h2>
                <button type="button" class="text-slate-400 hover:text-red-500 transition-colors text-xl" onclick="closeStockModal()">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>

            <form id="stockForm" class="p-6 space-y-4">
                <div class="space-y-1">
                    <label class="block text-xs font-bold text-slate-500 uppercase">Select Approved PO</label>
                    <select id="stk_po_select" onchange="autoFillStockDetails()" required class="w-full p-2.5 bg-white border border-slate-300 text-slate-700 text-sm rounded-lg focus:ring-primary focus:border-primary block">
                        </select>
                </div>
                
                <div class="space-y-1">
                    <label class="block text-xs font-bold text-slate-500 uppercase">Item Name</label>
                    <input type="text" id="stk_item" class="w-full p-2.5 bg-slate-100 border border-slate-200 text-slate-500 text-sm rounded-lg" readonly>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div class="space-y-1">
                        <label class="block text-xs font-bold text-slate-500 uppercase">Quantity</label>
                        <input type="number" id="stk_qty" required class="w-full p-2.5 bg-white border border-slate-300 text-slate-700 text-sm rounded-lg focus:ring-primary focus:border-primary">
                    </div>
                    <div class="space-y-1">
                        <label class="block text-xs font-bold text-slate-500 uppercase">Unit</label>
                        <input type="text" id="stk_unit" class="w-full p-2.5 bg-slate-100 border border-slate-200 text-slate-500 text-sm rounded-lg" readonly>
                    </div>
                </div>

                <div class="space-y-1">
                    <label class="block text-xs font-bold text-slate-500 uppercase">Received By</label>
                    <input type="text" id="stk_user" value="<?php echo htmlspecialchars($_SESSION['user_name'] ?? 'Admin'); ?>" required class="w-full p-2.5 bg-white border border-slate-300 text-slate-700 text-sm rounded-lg focus:ring-primary focus:border-primary" readonly>
                </div>

                <div class="pt-4 mt-2 flex justify-end gap-3">
                    <button type="button" class="px-4 py-2 text-sm font-bold text-slate-600 hover:bg-slate-100 rounded-lg transition-colors" onclick="closeStockModal()">Cancel</button>
                    <button type="submit" class="px-4 py-2 text-sm font-bold text-white bg-primary hover:bg-primary-hover rounded-lg shadow-md transition-colors">Confirm Receipt</button>
                </div>
            </form>
        </div>
    </div>

    <script src="js/stockin.js"></script>
    
</body>
</html>