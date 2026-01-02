<?php
    // 1. Establish Isolated Connections
    require_once __DIR__ . '/../../../config/database.php';
    $icmis_conn = $conn; // Main icmis DB

    include __DIR__ . '/../php/db_connect.php'; 
    $procurement_conn = $conn_proc ?? $conn; // Procurement DB

    include __DIR__ . '/../../budget/connection.php';
    $budget_conn = $conn; // icmis_budget DB

    // 2. Fetch Approved Phases from Budget Proposals
    $sql_phases = "SELECT DISTINCT phase FROM budget_proposals WHERE status = 'APPROVED' ORDER BY phase ASC";
    $result_phases = mysqli_query($budget_conn, $sql_phases);

    // 3. Fetch Approved Budget Items
    $sql_budget_items = "SELECT DISTINCT bli.item_name 
                         FROM budget_line_items bli
                         JOIN budget_proposals bp ON bli.proposal_id = bp.proposal_id
                         WHERE bp.status = 'APPROVED'
                         ORDER BY bli.item_name ASC";
    $result_budget_items = mysqli_query($budget_conn, $sql_budget_items);
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
<body class="bg-gray-50">
    <?php include __DIR__ . '/../../../includes/sidebar.php'; ?> 
    
    <?php 
        $pageTitle = "Purchase Orders";
        $pageSection = "Procurement & Inventory";
        $pageSubTitle = "Create New PO";
        include __DIR__ . '/../../../includes/header.php';
    ?>

    <?php
        // 2. Data Fetching using isolated connection variables
        
        // Fetch Projects FROM MAIN ICMIS DB
        $sql_projects = "SELECT project_id, project_code, name FROM projects ORDER BY name ASC";
        $result_projects = $icmis_conn->query($sql_projects);

				// 4. Fetch Approved Phases (Budget DB)
        // We fetch the 'phase' column which represents the Target Milestone in your icmis_budget.sql
        $sql_phases = "SELECT DISTINCT phase FROM budget_proposals WHERE status = 'APPROVED' ORDER BY phase ASC";
        $result_phases = mysqli_query($budget_conn, $sql_phases);

        // Fetch Suppliers FROM PROCUREMENT DB
        $sql_suppliers = "SELECT supplierID, supplierName FROM suppliers ORDER BY supplierName ASC";
        $result_suppliers = $procurement_conn->query($sql_suppliers);

			// Fetch Approved Budget Items FROM icmis_budget
			$sql_budget_items = "SELECT bli.item_name, bli.quantity, bli.unit_cost 
													FROM budget_line_items bli
													JOIN budget_proposals bp ON bli.proposal_id = bp.proposal_id
													WHERE bp.status = 'APPROVED'
													ORDER BY bli.item_name ASC";
			$result_budget_items = mysqli_query($budget_conn, $sql_budget_items);
    ?>

    <main class="ml-56 pt-24 p-6 min-h-screen transition-all duration-300">
        <div class="max-w-7xl mx-auto pt-6">
            
            <a href="../orders.php" class="inline-flex items-center text-gray-600 hover:text-gray-900 mb-6 transition-colors underline">
                <i class="fa-solid fa-arrow-left mr-2"></i>
                Back to Purchase Orders' Dashboard
            </a>

            <div class="mb-8 text-center">
                <h1 class="text-2xl font-bold text-navy-dark">New Purchase Request</h1>
                <p class="text-slate-500 mt-1">Fill in the details below to create a comprehensive purchase order</p>
            </div>

            <div class="grid grid-cols-2 gap-6">
                <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-6 h-[calc(100vh-220px)] overflow-y-auto custom-scrollbar">
                    
                    <div class="mb-6">
                        <label class="block text-sm font-bold text-gray-700 mb-2 uppercase tracking-wide">Project Destination (from icmis.projects)</label>
                        <select id="project" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary outline-none">
                            <option value="">-- Select Project --</option>
                            <?php if ($result_projects): while($proj = $result_projects->fetch_assoc()): ?>
                                <option value="<?= $proj['project_id'] ?>" data-name="<?= htmlspecialchars($proj['name']) ?>">
                                    <?= htmlspecialchars($proj['project_code'] . " - " . $proj['name']) ?>
                                </option>
                            <?php endwhile; endif; ?>
                        </select>
                    </div>

										<div class="mb-6">
                        <label for="targetPhase" class="block text-sm font-bold text-gray-700 mb-2 uppercase tracking-wide">Target Milestone / Phase</label>
                        <select id="targetPhase" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary outline-none">
                            <option value="">Select Approved Phase...</option>
                            <?php if ($result_phases): while($phase = mysqli_fetch_assoc($result_phases)): ?>
                                <option value="<?= htmlspecialchars($phase['phase']) ?>">
                                    <?= htmlspecialchars($phase['phase']) ?>
                                </option>
                            <?php endwhile; endif; ?>
                        </select>
                    </div>

                    <div class="mb-6">
												<label class="block text-sm font-bold text-gray-700 mb-2 uppercase tracking-wide">Supplier</label>
												<input type="text" id="supplier" list="suppliers-list" placeholder="Search or select a supplier..." class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary outline-none">
												
												<datalist id="suppliers-list">
														<?php if ($result_suppliers): 
																// Reset the pointer to the beginning if the result set was used earlier
																$result_suppliers->data_seek(0); 
																while($sup = $result_suppliers->fetch_assoc()): ?>
																		<option value="<?= htmlspecialchars($sup['supplierName']) ?>" data-id="<?= $sup['supplierID'] ?>">
														<?php endwhile; endif; ?>
												</datalist>
										</div>

                    <div class="mb-6">
                        <label class="block text-sm font-bold text-gray-700 mb-2 uppercase tracking-wide">Order Title</label>
                        <input type="text" id="orderTitle" placeholder="e.g., Phase 1 Concrete Materials" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary outline-none">
                    </div>

                    <div class="border-t border-gray-100 pt-6 mt-6">
											<h3 class="text-md font-bold text-navy-dark mb-4 uppercase italic">Budget Line Items (from icmis_budget)</h3>
											<div class="space-y-4">
													<div>
															<label class="block text-sm text-gray-600 mb-1">Approved Budget Item</label>
															<select id="item-name" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary outline-none bg-white">
																	<option value="">-- Select Approved Item --</option>
																	<?php if ($result_budget_items): 
																			mysqli_data_seek($result_budget_items, 0); // Reset pointer
																			while($item = mysqli_fetch_assoc($result_budget_items)): ?>
																					<option value="<?= htmlspecialchars($item['item_name']) ?>">
																							<?= htmlspecialchars($item['item_name']) ?>
																					</option>
																	<?php endwhile; endif; ?>
															</select>
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
													
													<button id="add-item-btn" class="w-full bg-navy-dark text-white rounded-lg py-3 font-bold hover:bg-slate-700 transition-all flex items-center justify-center gap-2">
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
                        <div class="grid grid-cols-2 gap-4">
                            <button id="save-draft-btn" class="border-2 border-gray-300 text-gray-700 hover:bg-gray-50 font-bold py-3 rounded-lg transition-all">Save Draft</button>
                            <button id="submit-po-btn" class="bg-primary hover:bg-primary-hover text-white font-bold py-3 rounded-lg shadow-md transition-all">Submit Purchase Order</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <script>
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
				const itemSelect = document.getElementById('item-name');
				const qtyInput = document.getElementById('item-qty');
				const priceInput = document.getElementById('item-price');

				// Listen for changes in the Item Dropdown
				itemSelect.addEventListener('change', function() {
						// Get the selected option element
						const selectedOption = this.options[this.selectedIndex];

						// Extract the data attributes we set in PHP
						const approvedQty = selectedOption.getAttribute('data-qty');
						const approvedPrice = selectedOption.getAttribute('data-price');

						// Auto-fill the inputs
						if (this.value !== "") {
								qtyInput.value = approvedQty;
								priceInput.value = approvedPrice;
							
						} else {
								qtyInput.value = '';
								priceInput.value = '';
						}
				});
		});

				// Update Supplier Preview based on the search list value
				document.getElementById('supplier').addEventListener('input', function() {
						const name = this.value;
						const previewSupplier = document.getElementById('preview-supplier');
						
						// Update the preview text; if empty, show fallback
						previewSupplier.innerText = name || 'No supplier selected';
						
						// Toggle italic style based on whether a supplier is selected
						previewSupplier.classList.toggle('italic', !name);
				});

        document.getElementById('add-item-btn').addEventListener('click', () => {
            const name = document.getElementById('item-name').value;
            const qty = parseFloat(document.getElementById('item-qty').value);
            const price = parseFloat(document.getElementById('item-price').value);

            if (!name || isNaN(qty) || isNaN(price) || qty <= 0) {
                showToast('Valid name, quantity, and price are required', 'warning');
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

        // Disable button to prevent double submit
        btn.disabled = true;
        btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Processing...';

        // Prepare Data
        const payload = {
            project_id: projectId,
            phase: phase,
            supplier: supplier, // Sending name; PHP will lookup ID
            title: title,
            items: orderItems
        };

        // Send Request
        fetch('save_order.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showToast(data.message, 'success');
                setTimeout(() => window.location.href = '../orders.php?msg=created', 500);
            } else {
                showToast('Error: ' + data.message, 'error');
                btn.disabled = false;
                btn.innerText = 'Submit Purchase Order';
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showToast('Server connection error', 'error');
            btn.disabled = false;
            btn.innerText = 'Submit Purchase Order';
        });
    });
    </script>
</body>
</html>