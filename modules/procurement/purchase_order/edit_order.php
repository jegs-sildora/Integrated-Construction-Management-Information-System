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

// Fetch Phases
$result_phases = $conn->query("SELECT DISTINCT phase FROM budget_proposals WHERE status = 'APPROVED' ORDER BY phase ASC");

// Fetch Suppliers
$result_suppliers = $conn->query("SELECT supplier_id, supplier_name FROM procurement_suppliers ORDER BY supplier_name ASC");

// Fetch Budget Items
$result_budget_items = $conn->query("SELECT bli.item_name, bli.quantity, bli.unit_cost FROM budget_line_items bli JOIN budget_proposals bp ON bli.proposal_id = bp.proposal_id WHERE bp.status = 'APPROVED' ORDER BY bli.item_name ASC");

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

    <main class="ml-56 pt-24 p-6 min-h-screen transition-all duration-300">
        <div class="max-w-7xl mx-auto pt-6">
            
            <a href="../orders.php" class="inline-flex items-center text-gray-600 hover:text-gray-900 mb-6 transition-colors underline">
                <i class="fa-solid fa-arrow-left mr-2"></i>
                Back to Dashboard
            </a>

            <div class="mb-8 text-center">
                <h1 class="text-2xl font-bold text-navy-dark">Edit Purchase Order</h1>
                <p class="text-slate-500 mt-1">Update details for Reference ID: <strong><?= htmlspecialchars($po_data['po_reference']) ?></strong></p>
            </div>

            <div class="grid grid-cols-2 gap-6">
                <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-6 h-[calc(100vh-220px)] overflow-y-auto custom-scrollbar">
                    
                    <input type="hidden" id="po_id" value="<?= $po_id ?>">

                    <div class="mb-6">
                        <label class="block text-sm font-bold text-gray-700 mb-2 uppercase tracking-wide">Project Destination</label>
                        <select id="project" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary outline-none">
                            <option value="">-- Select Project --</option>
                            <?php if ($result_projects): while($proj = $result_projects->fetch_assoc()): ?>
                                <option value="<?= $proj['project_id'] ?>" 
                                        data-name="<?= htmlspecialchars($proj['project_name']) ?>"
                                        <?= ($proj['project_id'] == $po_data['project_id']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($proj['project_code'] . " - " . $proj['project_name']) ?>
                                </option>
                            <?php endwhile; endif; ?>
                        </select>
                    </div>

                    <div class="mb-6">
                        <label class="block text-sm font-bold text-gray-700 mb-2 uppercase tracking-wide">Target Phase</label>
                        <select id="targetPhase" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary outline-none">
                            <option value="">Select Approved Phase...</option>
                            <?php if ($result_phases): while($phase = $result_phases->fetch_assoc()): ?>
                                <option value="<?= htmlspecialchars($phase['phase']) ?>"
                                        <?= ($phase['phase'] == $po_data['phase']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($phase['phase']) ?>
                                </option>
                            <?php endwhile; endif; ?>
                        </select>
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
                                <select id="item-name" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary outline-none bg-white">
                                    <option value="">-- Select Approved Item --</option>
                                    <?php if ($result_budget_items): 
                                        $result_budget_items->data_seek(0); 
                                        while($item = $result_budget_items->fetch_assoc()): ?>
                                            <option value="<?= htmlspecialchars($item['item_name']) ?>"
                                                    data-qty="<?= $item['quantity'] ?>" 
                                                    data-price="<?= $item['unit_cost'] ?>">
                                                <?= htmlspecialchars($item['item_name']) ?>
                                            </option>
                                    <?php endwhile; endif; ?>
                                </select>
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
                                <?= htmlspecialchars($po_data['phase']) ?>
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
    // --- 0. Helper: Toast Notification (ADDED MISSING FUNCTION) ---
    function showToast(message, type = 'success') {
        const existingToast = document.querySelector('.toast-notification');
        if (existingToast) existingToast.remove();

        const toast = document.createElement('div');
        toast.className = `toast-notification fixed bottom-5 right-5 px-6 py-4 rounded-xl shadow-2xl text-white font-bold transform transition-all duration-300 translate-y-20 opacity-0 z-50 flex items-center gap-3`;
        
        if (type === 'success') {
            toast.classList.add('bg-green-600');
            toast.innerHTML = `<i class="fa-solid fa-circle-check"></i> <span>${message}</span>`;
        } else if (type === 'error') {
            toast.classList.add('bg-red-600');
            toast.innerHTML = `<i class="fa-solid fa-circle-xmark"></i> <span>${message}</span>`;
        } else {
            toast.classList.add('bg-blue-600');
            toast.innerHTML = `<i class="fa-solid fa-circle-info"></i> <span>${message}</span>`;
        }

        document.body.appendChild(toast);

        requestAnimationFrame(() => {
            toast.classList.remove('translate-y-20', 'opacity-0');
        });

        setTimeout(() => {
            toast.classList.add('translate-y-20', 'opacity-0');
            setTimeout(() => toast.remove(), 300);
        }, 3000);
    }

    // Load existing items from PHP
    let orderItems = <?= json_encode($existing_items) ?>;
    let editingItemId = null; // Track which item is being edited

    document.addEventListener('DOMContentLoaded', () => {
        renderItems();
        updatePreviews(); 
    });

    // --- 1. Form & Preview Logic ---
    
    // Update Previews based on selections
    function updatePreviews() {
        // Project
        const projectSelect = document.getElementById('project');
        if (projectSelect.selectedIndex >= 0) {
            const selectedProj = projectSelect.options[projectSelect.selectedIndex];
            document.getElementById('preview-project').innerText = selectedProj.dataset.name || 'No project selected';
        }
        
        // Phase
        const phaseVal = document.getElementById('targetPhase').value;
        document.getElementById('preview-phase').innerText = phaseVal || 'No phase selected';
        
        // Supplier
        const supVal = document.getElementById('supplier').value;
        document.getElementById('preview-supplier').innerText = supVal || 'No supplier selected';
    }

    // Attach listeners
    document.getElementById('project').addEventListener('change', updatePreviews);
    document.getElementById('targetPhase').addEventListener('change', updatePreviews);
    document.getElementById('supplier').addEventListener('input', updatePreviews);

    // Auto-fill Item Inputs from Select Data attributes
    document.getElementById('item-name').addEventListener('change', function() {
        // Only auto-fill if NOT in edit mode (to prevent overwriting manually edited values during a load)
        if (editingItemId === null) {
            const selected = this.options[this.selectedIndex];
            if (this.value) {
                document.getElementById('item-qty').value = selected.dataset.qty;
                document.getElementById('item-price').value = selected.dataset.price;
            } else {
                clearItemInputs();
            }
        }
    });

    // --- 2. Item Management Logic (Add / Edit / Delete) ---

    // A. Main Button Click Handler
    document.getElementById('add-item-btn').addEventListener('click', () => {
        const nameSelect = document.getElementById('item-name');
        const name = nameSelect.value;
        const qty = parseFloat(document.getElementById('item-qty').value);
        const price = parseFloat(document.getElementById('item-price').value);

        if (!name || isNaN(qty) || isNaN(price) || qty <= 0) {
            showToast('Valid name, quantity, and price are required', 'error');
            return;
        }

        if (editingItemId !== null) {
            // UPDATE EXISTING ITEM
            const index = orderItems.findIndex(i => i.id === editingItemId);
            if (index !== -1) {
                orderItems[index].name = name;
                orderItems[index].qty = qty;
                orderItems[index].price = price;
                orderItems[index].total = qty * price;
                showToast('Item updated successfully', 'success');
            }
            resetFormState();
        } else {
            // ADD NEW ITEM
            const newItem = { id: Date.now(), name, qty, price, total: qty * price };
            orderItems.push(newItem);
        }

        renderItems();
        clearItemInputs();
    });

    // B. Load Item into Form (Triggered by clicking the row)
    function editItem(id) {
        const item = orderItems.find(i => i.id === id);
        if (!item) return;

        // 1. Populate Inputs
        document.getElementById('item-name').value = item.name;
        document.getElementById('item-qty').value = item.qty;
        document.getElementById('item-price').value = item.price;

        // 2. Set Edit State
        editingItemId = id;

        // 3. Update Button UI
        const btn = document.getElementById('add-item-btn');
        btn.innerHTML = '<i class="fa-solid fa-rotate"></i> Update Item';
        btn.classList.remove('bg-navy-dark', 'hover:bg-slate-700');
        btn.classList.add('bg-orange-600', 'hover:bg-orange-700'); // Visual cue for edit mode

        // 4. Scroll to form (optional, good for mobile)
        document.getElementById('item-name').scrollIntoView({ behavior: 'smooth', block: 'center' });
    }

    // C. Render the List
    function renderItems() {
        const container = document.getElementById('order-items-container');
        if (orderItems.length === 0) {
            container.innerHTML = '<div class="text-center py-10 text-gray-400 italic text-sm">No items. Add items using the form.</div>';
            document.getElementById('grand-total').innerText = '₱0.00';
            return;
        }

        let html = '';
        let total = 0;
        
        orderItems.forEach(item => {
            total += item.total;
            // Add visual highlight if this is the item currently being edited
            const activeClass = (item.id === editingItemId) ? 'ring-2 ring-orange-500 bg-orange-50' : 'bg-gray-50 border-gray-100 hover:bg-blue-50 cursor-pointer';
            
            html += `
                <div onclick="editItem(${item.id})" class="flex justify-between items-center p-3 rounded-lg border transition-all ${activeClass} animate-fade-in group relative">
                    <div class="flex-1">
                        <p class="text-sm font-bold text-gray-900">${item.name}</p>
                        <p class="text-xs text-gray-500">${item.qty} × ₱${item.price.toLocaleString(undefined, {minimumFractionDigits: 2})}</p>
                    </div>
                    <div class="text-right flex items-center gap-4">
                        <span class="text-sm font-black text-navy-dark">₱${item.total.toLocaleString(undefined, {minimumFractionDigits: 2})}</span>
                        
                        <button onclick="event.stopPropagation(); removeItem(${item.id})" class="text-gray-400 hover:text-red-600 transition-colors p-1 rounded-full hover:bg-white">
                            <i class="fa-solid fa-trash-can"></i>
                        </button>
                    </div>
                    
                    <div class="absolute inset-0 flex items-center justify-center bg-white/50 opacity-0 group-hover:opacity-100 pointer-events-none transition-opacity">
                        <span class="text-xs font-bold text-blue-600 bg-white px-2 py-1 rounded shadow-sm">Click to Edit</span>
                    </div>
                </div>
            `;
        });
        
        container.innerHTML = html;
        document.getElementById('grand-total').innerText = '₱' + total.toLocaleString(undefined, {minimumFractionDigits: 2});
    }

    // D. Remove Item
    function removeItem(id) {
        // If we are deleting the item currently being edited, reset the form
        if (id === editingItemId) {
            resetFormState();
            clearItemInputs();
        }
        orderItems = orderItems.filter(i => i.id !== id);
        renderItems();
    }

    // E. Utilities
    function resetFormState() {
        editingItemId = null;
        const btn = document.getElementById('add-item-btn');
        btn.innerHTML = '<i class="fa-solid fa-plus"></i> Add Item';
        btn.classList.add('bg-navy-dark', 'hover:bg-slate-700');
        btn.classList.remove('bg-orange-600', 'hover:bg-orange-700');
        
        // Re-render to remove the active highlight ring
        renderItems();
    }

    function clearItemInputs() {
        document.getElementById('item-name').value = '';
        document.getElementById('item-qty').value = '';
        document.getElementById('item-price').value = '';
    }

    // --- 3. Submit Final Update ---
    document.getElementById('submit-po-btn').addEventListener('click', function() {
        const btn = this;
        const poId = document.getElementById('po_id').value;
        const projectId = document.getElementById('project').value;
        const phase = document.getElementById('targetPhase').value;
        const supplier = document.getElementById('supplier').value;
        const title = document.getElementById('orderTitle').value;
        const status = document.querySelector('input[name="orderStatus"]:checked').value;

        if (!projectId || !phase || !supplier || orderItems.length === 0) {
            showToast('Please complete all fields and ensure items are added.', 'error');
            return;
        }

        btn.disabled = true;
        btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Updating...';

        const payload = {
            po_id: poId,
            project_id: projectId,
            phase: phase,
            supplier: supplier,
            title: title,
            status: status,
            items: orderItems
        };

        fetch('update_order.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                showToast(data.message, 'success');
                setTimeout(() => window.location.href = '../orders.php?msg=updated', 500);
            } else {
                showToast('Error: ' + data.message, 'error');
                btn.disabled = false;
                btn.innerText = 'Update Purchase Order';
            }
        })
        .catch(err => {
            console.error(err);
            showToast('Server error', 'error');
            btn.disabled = false;
            btn.innerText = 'Update Purchase Order';
        });
    });
</script>
</body>
</html>