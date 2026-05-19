<?php
    // 1. Use centralized config and project context
    require_once __DIR__ . '/../../../config/config.php';
    require_once __DIR__ . '/../../../core/ApiHelper.php';
    require_once __DIR__ . '/../../../core/ProjectContext.php';
    
    if (session_status() === PHP_SESSION_NONE) session_start();

    // Get selected project and phase from centralized context
    $selected_project_id = ProjectContext::getProjectId();
    $selected_phase_id = $_GET['phase_id'] ?? 0;

    // 2. Fetch Phases via API
    $phases = [];
    if ($selected_project_id > 0) {
        $res_phases = ApiHelper::get("project/phases?project_id=$selected_project_id");
        if ($res_phases['status'] === 200) {
            $phases = $res_phases['data']['phases'] ?? [];
        }
    }

    // 3. Fetch Approved Budget Items via API
    $budget_items = [];
    $selected_proposal_id = intval($_GET['proposal_id'] ?? 0);
    
    if ($selected_proposal_id > 0) {
        $res_bli = ApiHelper::get("budget/proposals?id=$selected_proposal_id");
        if ($res_bli['status'] === 200) {
            $budget_items = $res_bli['data']['items'] ?? [];
        }
    } else {
        $res_bli = ApiHelper::get("budget/proposals?project_id=$selected_project_id&status=APPROVED");
        if ($res_bli['status'] === 200) {
            foreach ($res_bli['data']['proposals'] as $prop) {
                $res_items = ApiHelper::get("budget/proposals?id=" . $prop['proposal_id']);
                if ($res_items['status'] === 200) {
                    $budget_items = array_merge($budget_items, $res_items['data']['items'] ?? []);
                }
            }
        }
    }

    // 4. Fetch Projects for dropdown
    $projects = [];
    $res_proj = ApiHelper::get("project/projects");
    if ($res_proj['status'] === 200) {
        $projects = $res_proj['data']['projects'] ?? [];
    }

    // 5. Fetch Suppliers for dropdown
    $suppliers = [];
    $res_sup = ApiHelper::get("procurement/suppliers");
    if ($res_sup['status'] === 200) {
        $suppliers = $res_sup['data']['suppliers'] ?? [];
    }
    
    // 6. Fetch Approved Proposals for dropdown
    $proposals = [];
    $res_props = ApiHelper::get("budget/proposals?status=APPROVED");
    if ($res_props['status'] === 200) {
        $proposals = $res_props['data']['proposals'] ?? [];
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

    <main class="ml-56 pt-20 p-6 min-h-screen transition-all duration-300 animate-fade-in">
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
                        <label class="block text-sm font-bold text-gray-700 mb-2 uppercase tracking-wide">Select Project</label>
                        <select id="project" onchange="window.location.href='?project_id='+this.value" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary outline-none bg-white">
                            <option value="">-- Choose Project --</option>
                            <?php foreach($projects as $proj): ?>
                                <option value="<?= $proj['project_id'] ?>" <?= $selected_project_id == $proj['project_id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($proj['project_code'] . ' - ' . $proj['project_name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-6">
                        <label class="block text-sm font-bold text-gray-700 mb-2 uppercase tracking-wide">Target Phase</label>
                        <select id="targetPhase" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary outline-none bg-white">
                            <option value="">-- Select Phase --</option>
                            <?php foreach($phases as $ph): ?>
                                <option value="<?= $ph['phase_id'] ?>" <?= $selected_phase_id == $ph['phase_id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($ph['phase_name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-6">
                        <label class="block text-sm font-bold text-gray-700 mb-2 uppercase tracking-wide">Supplier</label>
                        <input type="text" id="supplier" list="suppliers-list" placeholder="Search or select a supplier..." class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary outline-none">
                        <datalist id="suppliers-list">
                            <?php foreach($suppliers as $sup): ?>
                                <option value="<?= htmlspecialchars($sup['supplier_name']) ?>">
                            <?php endforeach; ?>
                        </datalist>
                    </div>

                    <div class="mb-6">
                        <label class="block text-sm font-bold text-gray-700 mb-2 uppercase tracking-wide">Order Title</label>
                        <input type="text" id="orderTitle" placeholder="e.g., Phase 1 Concrete Materials" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary outline-none">
                    </div>

                    <div class="border-t border-gray-100 pt-6 mt-6">
                        <h3 class="text-md font-bold text-navy-dark mb-4 uppercase italic">Line Items</h3>
                        
                        <div class="mb-4">
                            <label class="block text-sm text-gray-600 mb-1">Filter Items by Approved Proposal (Optional)</label>
                            <select onchange="window.location.href='?project_id=<?= $selected_project_id ?>&proposal_id='+this.value" class="w-full px-4 py-3 border border-gray-300 rounded-lg outline-none bg-slate-50 text-sm">
                                <option value="">-- All Approved Items --</option>
                                <?php foreach($proposals as $prop): ?>
                                    <?php if ($prop['project_id'] == $selected_project_id): ?>
                                    <option value="<?= $prop['proposal_id'] ?>" <?= $selected_proposal_id == $prop['proposal_id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($prop['code'] . ': ' . $prop['title']) ?>
                                    </option>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </select>
                        </div>

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
                            <img src="/assets/images/nobg_logo.png" alt="Logo" class="w-full h-full object-contain">
                        </div>
                        <div>
                            <h2 class="text-xl font-bold text-gray-900">ICMIS</h2>
                            <p class="text-sm text-gray-500 font-bold uppercase tracking-widest">Order Summary Preview</p>
                        </div>
                    </div>

                    <div class="space-y-3 mb-6">
                        <div class="bg-gradient-to-r from-blue-50 to-blue-100 border-l-4 border-blue-500 rounded-lg p-4">
                            <span class="text-xs font-bold text-blue-700 uppercase tracking-widest">Project</span>
                            <p id="preview-project" class="text-sm font-bold text-gray-900 mt-1 italic">No project selected</p>
                        </div>
                        <div class="bg-gradient-to-r from-purple-50 to-purple-100 border-l-4 border-purple-500 rounded-lg p-4">
                            <span class="text-xs font-bold text-purple-700 uppercase tracking-widest">Target Milestone</span>
                            <p id="preview-phase" class="text-sm font-bold text-gray-900 mt-1 italic">No phase selected</p>
                        </div>
                        <div class="bg-gradient-to-r from-green-50 to-green-100 border-l-4 border-green-500 rounded-lg p-4">
                            <span class="text-xs font-bold text-green-700 uppercase tracking-widest">Vendor</span>
                            <p id="preview-supplier" class="text-sm font-bold text-gray-900 mt-1 italic">No supplier selected</p>
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
                            <button id="submit-po-btn" class="bg-primary hover:bg-primary-hover text-white font-bold py-3 rounded-lg shadow-md transition-all">Generate Purchase Order</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <script src="js/create_order.js"></script>
</body>
</html>