<?php
// modules/procurement/purchase_order/edit_order.php

require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../../core/ApiHelper.php';

// 2. Validate ID
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die("Invalid Order ID");
}
$po_id = intval($_GET['id']);

// 3. Fetch Existing Purchase Order Data via Microservice
$res = ApiHelper::get("procurement/orders?id=$po_id");
if ($res['status'] !== 200 || !($res['data']['success'] ?? false)) {
    die("Purchase Order not found via API.");
}

$po_data = $res['data']['order'];
$existing_items = [];
foreach ($po_data['items'] as $item) {
    $existing_items[] = [
        'id' => $item['po_item_id'],
        'name' => $item['item_name'],
        'qty' => floatval($item['quantity']),
        'price' => floatval($item['unit_cost']),
        'total' => floatval($item['total_cost'])
    ];
}

// 4. Fetch Cross-Service Project Data
$po_project_id = $po_data['project_id'];
$po_project_name = 'N/A';
$res_proj = ApiHelper::get("project/projects/$po_project_id");
if ($res_proj['status'] === 200 && !empty($res_proj['data'])) {
    $po_project_name = ($res_proj['data']['project_code'] ?? 'N/A') . ' - ' . $res_proj['data']['project_name'];
}

// 5. Fetch Phase Name (display-only)
$po_phase_name = 'N/A';
$po_phase_id = $po_data['phase_id'] ?? 0;
if ($po_phase_id > 0) {
    $res_phases = ApiHelper::get("project/phases?project_id=$po_project_id");
    if ($res_phases['status'] === 200) {
        foreach ($res_phases['data']['phases'] as $p) {
            if (intval($p['phase_id']) === intval($po_phase_id)) {
                $po_phase_name = $p['phase_name'];
                break;
            }
        }
    }
}

// 6. Fetch Suppliers for dropdown
$suppliers = [];
$res_sup = ApiHelper::get("procurement/suppliers");
if ($res_sup['status'] === 200) {
    $suppliers = $res_sup['data']['suppliers'] ?? [];
}

// 7. Fetch Approved Budget Items for dropdown
$budget_items = [];
$res_budget = ApiHelper::get("budget/proposals?project_id=$po_project_id&status=APPROVED");
if ($res_budget['status'] === 200) {
    // Note: In a real system we might want to fetch line items for each approved proposal
    // For now, we'll try to get all approved line items for the project
    // Actually, budget/proposals?id=X returns items.
    foreach ($res_budget['data']['proposals'] as $prop) {
        $res_items = ApiHelper::get("budget/proposals?id=" . $prop['proposal_id']);
        if ($res_items['status'] === 200 && isset($res_items['data']['items'])) {
            foreach ($res_items['data']['items'] as $bi) {
                $budget_items[] = $bi;
            }
        }
    }
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Order | ICMIS</title>
    <?php include __DIR__ . '/../../../includes/head_assets.php'; ?> 
    <style> body { font-family: 'Inter', sans-serif; } </style>
</head>
<body class="bg-gray-50">
    <?php include __DIR__ . '/../../../includes/sidebar.php'; ?> 
    
    <?php 
        $pageTitle = "Purchase Orders";
        $pageSection = "Procurement & Inventory";
        $pageSubTitle = "Edit PO: " . htmlspecialchars($po_data['po_reference']);
        include __DIR__ . '/../../../includes/header.php';
    ?>
    <?php include __DIR__ . '/../../../includes/toast.php'; ?>

    <main class="ml-56 pt-20 p-6 min-h-screen transition-all duration-300 animate-fade-in">
        <div class="max-w-7xl mx-auto pt-6">
            
            <a href="../orders.php" class="inline-flex items-center text-gray-600 hover:text-gray-900 mb-6 transition-colors underline">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="4" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
            </a>

            <div class="mb-8 text-center">
                <h1 class="text-3xl font-bold text-navy-dark">Edit Purchase Order</h1>
                <p class="text-gray-600 font-semibold">Puchase Order Reference Code: <span class="font-bold text-[#e9922c]"><?= htmlspecialchars($po_data['po_reference']) ?></span></p>
            </div>

            <div class="grid grid-cols-2 gap-6">
                <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-6 h-[calc(100vh-220px)] overflow-y-auto custom-scrollbar">
                    
                    <input type="hidden" id="po_id" value="<?= $po_id ?>">

                    <div class="mb-6">
                        <label class="block text-sm font-bold text-gray-700 mb-2 uppercase tracking-wide">Project Destination</label>
                        <input type="hidden" id="project" value="<?= htmlspecialchars($po_project_id) ?>">
                        <input type="text" id="project_display" class="w-full px-4 py-3 border border-gray-300 rounded-lg bg-gray-100 outline-none" readonly value="<?= htmlspecialchars($po_project_name) ?>">
                        <p class="text-xs text-slate-400 mt-2">Project is read-only and comes from the original Purchase Order.</p>
                    </div>

                    <div class="mb-6">
                        <label class="block text-sm font-bold text-gray-700 mb-2 uppercase tracking-wide">Target Phase</label>
                        <input type="hidden" id="targetPhase" value="<?= htmlspecialchars($po_phase_id) ?>">
                        <input type="text" id="targetPhase_display" class="w-full px-4 py-3 border border-gray-300 rounded-lg bg-gray-100 outline-none" readonly value="<?= htmlspecialchars($po_phase_name) ?>">
                        <p class="text-xs text-slate-400 mt-2">Phase is read-only and comes from the original Purchase Order.</p>
                    </div>

                    <div class="mb-6">
                        <label class="block text-sm font-bold text-gray-700 mb-2 uppercase tracking-wide">Supplier</label>
                        <input type="text" id="supplier" list="suppliers-list" 
                               value="<?= htmlspecialchars($po_data['supplier_name'] ?? '') ?>"
                               placeholder="Search or select a supplier..." 
                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary outline-none">
                        
                        <datalist id="suppliers-list">
                            <?php foreach($suppliers as $sup): ?>
                                <option value="<?= htmlspecialchars($sup['supplier_name']) ?>">
                            <?php endforeach; ?>
                        </datalist>
                    </div>

                    <div class="mb-6">
                        <label class="block text-sm font-bold text-gray-700 mb-2 uppercase tracking-wide">Order Title</label>
                        <input type="text" id="orderTitle" 
                               value="<?= htmlspecialchars($po_data['order_title']) ?>"
                               placeholder="e.g., Phase 1 Concrete Materials" 
                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary outline-none">
                    </div>

                    <div class="mb-6">
                        <label class="block text-sm font-bold text-gray-700 mb-3 uppercase tracking-wide">Status</label>
                        <div class="grid grid-cols-2 gap-3">
                            <?php 
                            $statuses = ['PENDING', 'APPROVED', 'COMPLETED', 'REJECTED'];
                            foreach ($statuses as $status): 
                                $isChecked = ($po_data['status'] === $status);
                                $activeClass = $isChecked ? 'bg-orange-50 border-orange-500 ring-1 ring-orange-500' : 'border-gray-200 hover:bg-gray-50';
                            ?>
                            <label class="flex items-center px-4 py-3 border rounded-lg cursor-pointer transition-all <?= $activeClass ?>">
                                <input type="radio" name="orderStatus" value="<?= $status ?>" <?= $isChecked ? 'checked' : '' ?> 
                                       class="w-4 h-4 text-orange-500 border-gray-300 focus:ring-orange-500">
                                <span class="ml-3 text-sm font-bold text-gray-700 capitalize"><?= strtolower($status) ?></span>
                            </label>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <div class="border-t border-gray-100 pt-6 mt-6">
                        <h3 class="text-md font-bold text-navy-dark mb-4 uppercase italic">Line Items</h3>
                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm text-gray-600 mb-1">Approved Budget Item</label>
                                    <input id="item-name" list="item-name-list" placeholder="-- Select Approved Item --" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary outline-none bg-white">
                                    <datalist id="item-name-list">
                                        <?php foreach($budget_items as $item): ?>
                                            <option value="<?= htmlspecialchars($item['item_name']) ?>" data-qty="<?= $item['quantity'] ?>" data-price="<?= $item['unit_cost'] ?>"></option>
                                        <?php endforeach; ?>
                                    </datalist>
                            </div>

                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm text-gray-600 mb-1">Quantity</label>
                                    <input type="number" id="item-qty" min="1" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary">
                                </div>
                                <div>
                                    <label class="block text-sm text-gray-600 mb-1">Unit Price (₱)</label>
                                    <input type="number" id="item-price" step="0.01" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary">
                                </div>
                            </div>
                            
                            <button id="add-item-btn" class="w-full bg-navy-dark text-white rounded-lg py-3 font-bold hover:bg-slate-700 transition-all flex items-center justify-center gap-2">
                                <i class="fa-solid fa-plus"></i> Add Item
                            </button>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-6 h-[calc(100vh-220px)] overflow-y-auto custom-scrollbar flex flex-col">
                    <div class="flex items-center mb-6">
                        <div class="w-12 h-12 bg-white rounded-lg flex items-center justify-center shadow-md p-1.5 mr-4 border border-gray-100">
                            <img src="/icmis/assets/images/nobg_logo.png" alt="Logo" class="w-full h-full object-contain">
                        </div>
                        <div>
                            <h2 class="text-xl font-bold text-gray-900">ICMIS</h2>
                            <p class="text-sm text-gray-500 font-bold uppercase tracking-widest">Order Summary Preview</p>
                        </div>
                    </div>

                    <div class="space-y-3 mb-6">
                        <div class="bg-gradient-to-r from-blue-50 to-blue-100 border-l-4 border-blue-500 rounded-lg p-4">
                            <span class="text-xs font-bold text-blue-700 uppercase tracking-widest">Project</span>
                            <p id="preview-project" class="text-sm font-bold text-gray-900 mt-1 italic">
                                <?= !empty($po_data['project_id']) ? 'Project Loaded' : 'No project selected' ?>
                            </p>
                        </div>
                        <div class="bg-gradient-to-r from-purple-50 to-purple-100 border-l-4 border-purple-500 rounded-lg p-4">
                            <span class="text-xs font-bold text-purple-700 uppercase tracking-widest">Target Milestone</span>
                                <p id="preview-phase" class="text-sm font-bold text-gray-900 mt-1 italic">
                                <?= htmlspecialchars($po_phase_name) ?>
                            </p>
                        </div>
                        <div class="bg-gradient-to-r from-green-50 to-green-100 border-l-4 border-green-500 rounded-lg p-4">
                            <span class="text-xs font-bold text-green-700 uppercase tracking-widest">Vendor</span>
                            <p id="preview-supplier" class="text-sm font-bold text-gray-900 mt-1 italic">
                                <?= htmlspecialchars($po_data['supplier_name'] ?? '') ?>
                            </p>
                        </div>
                    </div>

                    <div class="flex-1">
                        <h3 class="text-sm font-bold text-gray-700 uppercase mb-3 border-b pb-2">Order Items</h3>
                        <div id="order-items-container" class="space-y-2"></div>
                    </div>

                    <div class="border-t-2 border-dashed border-gray-200 pt-4 mt-6">
                        <div class="flex justify-between items-center mb-6">
                            <span class="text-lg font-bold text-gray-700">GRAND TOTAL</span>
                            <span id="grand-total" class="text-3xl font-black text-primary">₱0.00</span>
                        </div>
                        <div class="grid grid-cols-1">
                            <button id="submit-po-btn" class="bg-primary hover:bg-primary-hover text-white font-bold py-3 rounded-lg shadow-md transition-all">Update Purchase Order</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <script>
        // Pass PHP data to JS
        window.existingOrderItems = <?= json_encode($existing_items) ?>;
    </script>
    <script src="js/edit_order.js"></script>
</body>
</html>