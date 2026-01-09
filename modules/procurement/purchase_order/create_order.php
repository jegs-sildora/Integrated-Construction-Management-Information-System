<?php
    // 1. Use centralized config and project context
    require_once __DIR__ . '/../project_context.php';
    $conn = getProcurementConnection();
    
    // Get selected project and phase from context
    $selected_project_id = getProjectContext($conn);
    $selected_phase_id = getPhaseContext();

    // 2. Fetch Phases directly from icmis_project_phases for the selected project
    $sql_phases = "SELECT pp.phase_id, pp.phase_name
                   FROM icmis_project_phases pp
                   WHERE pp.project_id = ?
                   ORDER BY pp.phase_name ASC";
    $stmt_phases = $conn->prepare($sql_phases);
    $stmt_phases->bind_param("i", $selected_project_id);
    $stmt_phases->execute();
    $result_phases = $stmt_phases->get_result();

    // 3. Fetch Approved Budget Items (optionally filter by proposal_id)
    $selected_proposal_id = isset($_GET['proposal_id']) ? intval($_GET['proposal_id']) : 0;
    if ($selected_proposal_id > 0) {
        $stmt_bli = $conn->prepare("SELECT line_item_id, item_name, quantity, unit_cost FROM budget_line_items WHERE proposal_id = ? ORDER BY line_item_id ASC");
        $stmt_bli->bind_param('i', $selected_proposal_id);
        $stmt_bli->execute();
        $result_budget_items = $stmt_bli->get_result();
    } else {
        $sql_budget_items = "SELECT DISTINCT bli.item_name, bli.unit_cost, bli.quantity 
                             FROM budget_line_items bli
                             JOIN budget_proposals bp ON bli.proposal_id = bp.proposal_id
                             WHERE bp.status = 'APPROVED'
                             ORDER BY bli.item_name ASC";
        $result_budget_items = $conn->query($sql_budget_items);
    }
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Purchase Order | ICMIS</title>
    
    <?php include __DIR__ . '/../../../includes/head_assets.php'; ?> 
    <style>
        body { font-family: 'Inter', sans-serif; }
    </style>
</head>
<body class="bg-gray-50" data-selected-project="<?= $selected_project_id ?>" data-selected-phase="<?= $selected_phase_id ?>">
    <?php include __DIR__ . '/../../../includes/sidebar.php'; ?> 
    
    <?php 
        $pageTitle = "Purchase Orders";
        $pageSection = "Procurement & Inventory";
        $pageSubTitle = "Create New PO";
        include __DIR__ . '/../../../includes/header.php';
    ?>
    <?php include __DIR__ . '/../../../includes/toast.php'; ?>

    <?php
        // 2. Data Fetching using single connection
        
        // Fetch Projects
        $result_projects = false;
        if (!empty($selected_project_id) && intval($selected_project_id) > 0) {
            $stmt_proj = $conn->prepare("SELECT project_id, project_code, project_name FROM icmis_projects WHERE project_id = ? LIMIT 1");
            $stmt_proj->bind_param('i', $selected_project_id);
            $stmt_proj->execute();
            $result_projects = $stmt_proj->get_result();
        } else {
            $sql_projects = "SELECT project_id, project_code, project_name FROM icmis_projects ORDER BY project_name ASC";
            $result_projects = $conn->query($sql_projects);
        }

        // Fetch Suppliers
        $sql_suppliers = "SELECT supplier_id, supplier_name FROM procurement_suppliers ORDER BY supplier_name ASC";
        $result_suppliers = $conn->query($sql_suppliers);
        
        // Fetch Approved Proposals for AJAX-driven budget items
        $sql_proposals = "SELECT proposal_id, code, title, project_id FROM budget_proposals WHERE status = 'APPROVED' ORDER BY created_at DESC";
        $result_proposals = $conn->query($sql_proposals);
    ?>

    <main class="ml-56 pt-20 p-6 min-h-screen transition-all duration-300">
        <div class="max-w-7xl mx-auto pt-6">
            
            <a href="../orders.php" class="inline-flex items-center text-gray-600 hover:text-gray-900 mb-6 transition-colors underline">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="4" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
            </a>

            <div class="mb-8 text-center">
                <h1 class="text-3xl font-bold text-navy-dark">New Purchase Order</h1>
                <p class="text-slate-500 mt-1">Fill in the details below to create a comprehensive purchase order</p>
            </div>

            <div class="grid grid-cols-2 gap-6">
                <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-6 h-[calc(100vh-220px)] overflow-y-auto custom-scrollbar">
                    
                    <div class="mb-6">
                        <label class="block text-sm font-bold text-gray-700 mb-2 uppercase tracking-wide">Project Destination</label>
                        <select id="project" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary outline-none">
                            <?php if ($result_projects && $result_projects->num_rows > 0): ?>
                                <option value="">-- Select Project --</option>
                                <?php while($proj = $result_projects->fetch_assoc()): ?>
                                    <option value="<?= $proj['project_id'] ?>" data-name="<?= htmlspecialchars($proj['project_name']) ?>" <?= (isset($selected_project_id) && $selected_project_id == $proj['project_id']) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($proj['project_code'] . " - " . $proj['project_name']) ?>
                                    </option>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <option value="" disabled selected>No Projects</option>
                            <?php endif; ?>
                        </select>
                    </div>

                    <div class="mb-6">
                        <label for="targetPhase" class="block text-sm font-bold text-gray-700 mb-2 uppercase tracking-wide">Target Milestone / Phase</label>
                        <select id="targetPhase" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary outline-none">
                            <option value="">Select Approved Phase...</option>
                            <?php if ($result_phases): while($phase = $result_phases->fetch_assoc()): ?>
                                <option value="<?= htmlspecialchars($phase['phase_name']) ?>" data-phase-id="<?= $phase['phase_id'] ?>">
                                    <?= htmlspecialchars($phase['phase_name']) ?>
                                </option>
                            <?php endwhile; endif; ?>
                        </select>
                    </div>

                    <div class="mb-6">
                        <label class="block text-sm font-bold text-gray-700 mb-2 uppercase tracking-wide">Supplier</label>
                        <input type="text" id="supplier" list="suppliers-list" placeholder="Search or select a supplier..." class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary outline-none">
                        
                        <datalist id="suppliers-list">
                            <?php if ($result_suppliers): 
                                $result_suppliers->data_seek(0); 
                                while($sup = $result_suppliers->fetch_assoc()): ?>
                                    <option value="<?= htmlspecialchars($sup['supplier_name']) ?>" data-id="<?= $sup['supplier_id'] ?>">
                            <?php endwhile; endif; ?>
                        </datalist>
                    </div>

                    <div class="mb-6">
                        <label class="block text-sm font-bold text-gray-700 mb-2 uppercase tracking-wide">Order Title</label>
                        <input type="text" id="orderTitle" placeholder="e.g., Phase 1 Concrete Materials" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary outline-none">
                    </div>

                    <div class="border-t border-gray-100 pt-6 mt-6">
                        <h3 class="text-md font-bold text-navy-dark mb-4 uppercase italic">Line Items</h3>
                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm text-gray-600 mb-1">Approved Budget Item</label>
                                <input id="item-name" list="item-name-list" placeholder="-- Select Approved Item --" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary outline-none bg-white">
                                <datalist id="item-name-list">
                                    <?php if ($result_budget_items): 
                                        $result_budget_items->data_seek(0); 
                                        while($item = $result_budget_items->fetch_assoc()): ?>
                                            <option value="<?= htmlspecialchars($item['item_name']) ?>" data-qty="<?= $item['quantity'] ?? '' ?>" data-price="<?= $item['unit_cost'] ?? '' ?>"></option>
                                    <?php endwhile; endif; ?>
                                </datalist>
                            </div>

                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm text-gray-600 mb-1">Quantity</label>
                                    <input type="number" id="item-qty" min="1" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary">
                                </div>
                                <div>
                                    <label class="block text-sm text-gray-600 mb-1">Unit Price (₱)</label>
                                    <input type="number" id="item-price" step="0.01" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary">
                                </div>
                            </div>
                            
                            <button id="add-item-btn" type="button" class="w-full bg-navy-dark text-white rounded-lg py-3 font-bold hover:bg-slate-700 transition-all flex items-center justify-center gap-2">
                                <i class="fa-solid fa-plus"></i> Add to Request
                            </button>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-6 h-[calc(100vh-220px)] overflow-y-auto custom-scrollbar flex flex-col">
                    <div class="flex items-center mb-6">
                        <div class="w-12 h-12 bg-white rounded-lg flex items-center justify-center shadow-md p-1.5 mr-4 border border-gray-100">
                            <img src="/icmis/assets/images/nobg_logo.png" alt="ICMIS Logo" class="w-full h-full object-contain">
                        </div>
                        <div>
                            <h2 class="text-xl font-bold text-gray-900">ICMIS</h2>
                            <p class="text-sm text-gray-500 font-bold uppercase tracking-widest">Purchase Order Summary Preview</p>
                        </div>
                    </div>

                    <div class="space-y-3 mb-6">
                        <div class="bg-gradient-to-r from-blue-50 to-blue-100 border-l-4 border-blue-500 rounded-lg p-4">
                            <span class="text-xs font-bold text-blue-700 uppercase tracking-widest">Project</span>
                            <p id="preview-project" class="text-sm font-bold text-gray-900 mt-1 italic">No project selected</p>
                        </div>

                        <div class="bg-gradient-to-r from-purple-50 to-purple-100 border-l-4 border-purple-500 rounded-lg p-4">
                            <span class="text-xs font-bold text-purple-700 uppercase tracking-widest">Target Milestone / Phase</span>
                            <p id="preview-phase" class="text-sm font-bold text-gray-900 mt-1 italic">No phase selected</p>
                        </div>

                        <div class="bg-gradient-to-r from-green-50 to-green-100 border-l-4 border-green-500 rounded-lg p-4">
                            <span class="text-xs font-bold text-green-700 uppercase tracking-widest">Vendor/Supplier</span>
                            <p id="preview-supplier" class="text-sm font-bold text-gray-900 mt-1 italic">No supplier selected</p>
                        </div>
                    </div>

                    <div class="flex-1">
                        <h3 class="text-sm font-bold text-gray-700 uppercase mb-3 border-b pb-2">Requested Items</h3>
                        <div id="order-items-container" class="space-y-2">
                            <div class="text-center py-10 text-gray-400 italic text-sm">Add items to build the purchase order.</div>
                        </div>
                    </div>

                    <div class="border-t-2 border-dashed border-gray-200 pt-4 mt-6">
                        <div class="flex justify-between items-center mb-6">
                            <span class="text-lg font-bold text-gray-700">GRAND TOTAL</span>
                            <span id="grand-total" class="text-3xl font-black text-primary">₱0.00</span>
                        </div>
                        <div class="grid grid-cols-1">
                            <button id="submit-po-btn" type="button" class="bg-primary hover:bg-primary-hover text-white font-bold py-3 rounded-lg shadow-md transition-all">Submit Purchase Order</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <script src="js/create_order.js"></script>
</body>
</html>