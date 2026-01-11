<?php
// modules/procurement/purchase_order/edit_order.php

// 1. Use centralized config for single database connection
require_once __DIR__ . '/../../../config/config.php';
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}
$conn->set_charset("utf8mb4");

// 2. Validate ID
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die("Invalid Order ID");
}
$po_id = intval($_GET['id']);

// 3. Fetch Existing Purchase Order Data
$stmt = $conn->prepare("SELECT po.*, s.supplier_name FROM procurement_purchase_orders po LEFT JOIN procurement_suppliers s ON po.supplier_id = s.supplier_id WHERE po.po_id = ?");
$stmt->bind_param("i", $po_id);
$stmt->execute();
$result_po = $stmt->get_result();
if ($result_po->num_rows === 0) {
    die("Purchase Order not found.");
}
$po_data = $result_po->fetch_assoc();
$stmt->close();

// 4. Fetch Existing Items for this PO
$stmt_items = $conn->prepare("SELECT * FROM procurement_purchase_order_items WHERE po_id = ?");
$stmt_items->bind_param("i", $po_id);
$stmt_items->execute();
$result_items = $stmt_items->get_result();
$existing_items = [];
while ($row = $result_items->fetch_assoc()) {
    $existing_items[] = [
        'id' => $row['po_item_id'], // Use DB ID to distinguish saved items
        'name' => $row['item_name'],
        'qty' => floatval($row['quantity']),
        'price' => floatval($row['unit_cost']),
        'total' => floatval($row['total_cost'])
    ];
}
$stmt_items->close();

// 5. Fetch Dropdown Data
$result_projects = $conn->query("SELECT project_id, project_code, project_name FROM icmis_projects ORDER BY project_name ASC");

// Fetch the phase_name for this PO's phase_id (display-only)
$po_phase_name = '';
$po_phase_id = $po_data['phase_id'] ?? 0;
if ($po_phase_id > 0) {
    $stmt_phase = $conn->prepare("SELECT phase_name FROM icmis_project_phases WHERE phase_id = ? LIMIT 1");
    if ($stmt_phase) {
        $stmt_phase->bind_param('i', $po_phase_id);
        $stmt_phase->execute();
        $res_phase = $stmt_phase->get_result();
        if ($r = $res_phase->fetch_assoc()) {
            $po_phase_name = $r['phase_name'];
        }
        $stmt_phase->close();
    }
}

// Fetch the project display name for this PO's project_id (read-only)
$po_project_name = '';
$po_project_id = $po_data['project_id'] ?? 0;
if ($po_project_id > 0) {
    $stmt_proj_name = $conn->prepare("SELECT project_code, project_name FROM icmis_projects WHERE project_id = ? LIMIT 1");
    if ($stmt_proj_name) {
        $stmt_proj_name->bind_param('i', $po_project_id);
        $stmt_proj_name->execute();
        $res_proj = $stmt_proj_name->get_result();
        if ($rp = $res_proj->fetch_assoc()) {
            $po_project_name = $rp['project_code'] . ' - ' . $rp['project_name'];
        }
        $stmt_proj_name->close();
    }
}

// Fetch Suppliers
$result_suppliers = $conn->query("SELECT supplier_id, supplier_name FROM procurement_suppliers ORDER BY supplier_name ASC");

// Fetch Budget Items (optionally filter by proposal_id)
$selected_proposal_id = isset($_GET['proposal_id']) ? intval($_GET['proposal_id']) : 0;
if ($selected_proposal_id > 0) {
    $stmt_bli = $conn->prepare("SELECT line_item_id, item_name, quantity, unit_cost FROM budget_line_items WHERE proposal_id = ? ORDER BY line_item_id ASC");
    $stmt_bli->bind_param('i', $selected_proposal_id);
    $stmt_bli->execute();
    $result_budget_items = $stmt_bli->get_result();
} else {
    $result_budget_items = $conn->query("SELECT bli.item_name, bli.quantity, bli.unit_cost FROM budget_line_items bli JOIN budget_proposals bp ON bli.proposal_id = bp.proposal_id WHERE bp.status = 'APPROVED' ORDER BY bli.item_name ASC");
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
                            <?php if ($result_suppliers): 
                                $result_suppliers->data_seek(0); 
                                while($sup = $result_suppliers->fetch_assoc()): ?>
                                    <option value="<?= htmlspecialchars($sup['supplier_name']) ?>">
                            <?php endwhile; endif; ?>
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
                                        <?php if ($result_budget_items): 
                                            $result_budget_items->data_seek(0); 
                                            while($item = $result_budget_items->fetch_assoc()): ?>
                                                <option value="<?= htmlspecialchars($item['item_name']) ?>" data-qty="<?= $item['quantity'] ?>" data-price="<?= $item['unit_cost'] ?>"></option>
                                        <?php endwhile; endif; ?>
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
                                <?= htmlspecialchars($po_data['phase'] ?? '') ?>
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