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

    <script>
        // showToast is provided globally by includes/toast.php

        // --- AUTO-FILL FROM PROJECT CONTEXT ---
        function autoFillFromContext() {
            const selectedProjectId = document.body.getAttribute('data-selected-project');
            const selectedPhaseId = document.body.getAttribute('data-selected-phase');
            
            // Auto-select project
            if (selectedProjectId && selectedProjectId !== '0') {
                const projectSelect = document.getElementById('project');
                projectSelect.value = selectedProjectId;
                
                // Trigger change event to update preview
                const event = new Event('change');
                projectSelect.dispatchEvent(event);
            }
            
            // Auto-select phase
            if (selectedPhaseId && selectedPhaseId !== '0') {
                const phaseSelect = document.getElementById('targetPhase');
                
                // Find option by phase_id data attribute
                const options = phaseSelect.querySelectorAll('option');
                for (let option of options) {
                    if (option.getAttribute('data-phase-id') === selectedPhaseId) {
                        phaseSelect.value = option.value;
                        
                        // Trigger change event to update preview
                        const event = new Event('change');
                        phaseSelect.dispatchEvent(event);
                        break;
                    }
                }
            }
        }

        // --- LOAD LINE ITEMS FROM PROPOSAL VIA AJAX ---
        document.addEventListener('DOMContentLoaded', function() {
            const proposalSelect = document.getElementById('proposal_select');
            const itemInput = document.getElementById('item-name');
            const itemDatalist = document.getElementById('item-name-list');
            const projectSelect = document.getElementById('project');
            const phaseSelect = document.getElementById('targetPhase');

            if (proposalSelect) {
                proposalSelect.addEventListener('change', function() {
                    const pid = this.value;
                    if (!pid) return;
                    fetch(`/icmis/modules/budget/budget_proposal/get_proposal_details.php?id=${pid}`)
                        .then(res => res.json())
                        .then(resp => {
                            if (!resp.success) return showToast('Failed to load proposal', 'error');
                            // Populate datalist items
                            if (itemDatalist) {
                                itemDatalist.innerHTML = '';
                                resp.items.forEach(it => {
                                    const opt = document.createElement('option');
                                    opt.value = it.item_name;
                                    opt.setAttribute('data-qty', it.quantity || '');
                                    opt.setAttribute('data-price', it.unit_cost || '');
                                    itemDatalist.appendChild(opt);
                                });
                            }

                            // Auto-select project and phase from proposal
                            if (resp.proposal && resp.proposal.project_id) {
                                projectSelect.value = resp.proposal.project_id;
                                projectSelect.dispatchEvent(new Event('change'));
                            }
                            if (resp.proposal && resp.proposal.phase_id) {
                                // find option with matching data-phase-id
                                for (let opt of phaseSelect.options) {
                                    if (opt.getAttribute('data-phase-id') == resp.proposal.phase_id) {
                                        phaseSelect.value = opt.value;
                                        phaseSelect.dispatchEvent(new Event('change'));
                                        break;
                                    }
                                }
                            }
                        })
                        .catch(err => {
                            console.error(err);
                            showToast('Error loading proposal details', 'error');
                        });
                });
            }
        });

        // --- MAIN LOGIC ---
        let orderItems = [];

        document.getElementById('project').addEventListener('change', function() {
            const name = this.options[this.selectedIndex].dataset.name;
            document.getElementById('preview-project').innerText = name || 'No project selected';
            document.getElementById('preview-project').classList.toggle('italic', !name);
        });

        document.getElementById('targetPhase').addEventListener('change', function() {
            const phaseName = this.value;
            const previewPhase = document.getElementById('preview-phase');
            previewPhase.innerText = phaseName || 'No phase selected';
            previewPhase.classList.toggle('italic', !phaseName);
        });

        document.addEventListener('DOMContentLoaded', function() {
            // Auto-fill from project context
            autoFillFromContext();
            
            const itemInput = document.getElementById('item-name');
            const itemDatalist = document.getElementById('item-name-list');
            const qtyInput = document.getElementById('item-qty');
            const priceInput = document.getElementById('item-price');
            if (itemInput) {
                itemInput.addEventListener('change', function() {
                    const val = this.value;
                    let approvedQty = null;
                    let approvedPrice = null;
                    if (itemDatalist) {
                        const options = itemDatalist.querySelectorAll('option');
                        for (let opt of options) {
                            if (opt.value === val) {
                                approvedQty = opt.getAttribute('data-qty');
                                approvedPrice = opt.getAttribute('data-price');
                                break;
                            }
                        }
                    }

                    if (val) {
                        qtyInput.value = (approvedQty !== null && approvedQty !== '') ? Number(approvedQty) : 1;
                        priceInput.value = (approvedPrice !== null && approvedPrice !== '') ? Number(approvedPrice) : '';
                    } else {
                        qtyInput.value = '';
                        priceInput.value = '';
                    }
                });
            }
        });

        document.getElementById('supplier').addEventListener('input', function() {
            const name = this.value;
            const previewSupplier = document.getElementById('preview-supplier');
            previewSupplier.innerText = name || 'No supplier selected';
            previewSupplier.classList.toggle('italic', !name);
        });

        document.getElementById('add-item-btn').addEventListener('click', () => {
            const name = document.getElementById('item-name').value;
            const qty = parseFloat(document.getElementById('item-qty').value);
            const price = parseFloat(document.getElementById('item-price').value);

            if (!name || isNaN(qty) || isNaN(price) || qty <= 0) {
                showToast('Valid name, quantity, and price are required', 'error');
                return;
            }

            const item = { id: Date.now(), name, qty, price, total: qty * price };
            orderItems.push(item);
            renderItems();
            
            document.getElementById('item-name').value = '';
            document.getElementById('item-qty').value = '';
            document.getElementById('item-price').value = '';
        });

        function renderItems() {
            const container = document.getElementById('order-items-container');
            if (orderItems.length === 0) {
                container.innerHTML = '<div class="text-center py-10 text-gray-400 italic text-sm">Add items to build the purchase order.</div>';
                document.getElementById('grand-total').innerText = '₱0.00';
                return;
            }

            let html = '';
            let total = 0;
            orderItems.forEach(item => {
                total += item.total;
                html += `
                    <div class="flex justify-between items-center bg-gray-50 p-3 rounded-lg border border-gray-100 animate-fade-in">
                        <div class="flex-1">
                            <p class="text-sm font-bold text-gray-900">${item.name}</p>
                            <p class="text-xs text-gray-500">${item.qty} units × ₱${item.price.toLocaleString(undefined, {minimumFractionDigits: 2})}</p>
                        </div>
                        <div class="text-right flex items-center gap-4">
                            <span class="text-sm font-black text-navy-dark">₱${item.total.toLocaleString(undefined, {minimumFractionDigits: 2})}</span>
                            <button onclick="removeItem(${item.id})" class="text-red-400 hover:text-red-600 transition-colors">
                                <i class="fa-solid fa-trash-can"></i>
                            </button>
                        </div>
                    </div>
                `;
            });
            container.innerHTML = html;
            document.getElementById('grand-total').innerText = '₱' + total.toLocaleString(undefined, {minimumFractionDigits: 2});
        }

        function removeItem(id) {
            orderItems = orderItems.filter(i => i.id !== id);
            renderItems();
        }

        document.getElementById('submit-po-btn').addEventListener('click', function() {
            const btn = this;
            const projectId = document.getElementById('project').value;
            const phase = document.getElementById('targetPhase').value;
            const supplier = document.getElementById('supplier').value;
            const title = document.getElementById('orderTitle').value;

            if (!projectId || !phase || !supplier || orderItems.length === 0) {
                showToast('Please complete all fields and add items.', 'error');
                return;
            }

            btn.disabled = true;
            btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Processing...';

            const payload = {
                project_id: projectId,
                phase: phase,
                supplier: supplier,
                title: title,
                items: orderItems
            };

            fetch('save_order.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload)
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    setTimeout(() => window.location.href = '../orders.php?msg=created', 500);
                } else {
                    showToast('Error: ' + data.message, 'error');
                    btn.disabled = false;
                    btn.innerText = 'Submit Purchase Order';
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showToast('Server connection error. Check console for details.', 'error');
                btn.disabled = false;
                btn.innerText = 'Submit Purchase Order';
            });
        });
    </script>
</body>
</html>