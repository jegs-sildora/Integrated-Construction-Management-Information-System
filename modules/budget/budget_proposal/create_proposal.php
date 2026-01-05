<?php
	require_once __DIR__ . '/../../../config/config.php';

    // Initialize default values
    $default_labor_workers = 0;
    $default_labor_hourly = 0.00;
    $default_labor_daily = 0.00;
    $default_equipment_quantity = 0;
    $default_equipment_days = 0;
    $default_equipment_rate = 0.00;
?>

<!DOCTYPE html>
<html lang="en">
<head>
	<link rel="stylesheet" href="../css/output.css">
	<link rel="stylesheet" href="../css/input.css">
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Create Budget Proposal | ICMIS</title>
	<link rel="preconnect" href="https://fonts.googleapis.com">
	<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
	<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="apple-touch-icon" sizes="180x180" href="../../../assets/images/favicon/apple-touch-icon.png">
    <link rel="icon" type="image/png" sizes="32x32" href="../../../assets/images/favicon/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="../../../assets/images/favicon/favicon-16x16.png">
    <link rel="manifest" href="../../../assets/images/favicon/site.webmanifest">
</head>
<body class="bg-gray-50">
    <?php 
		include __DIR__ . '/../../../includes/sidebar.php';
		include __DIR__ . '/../project_context.php';
		$conn = getBudgetConnection();
		include __DIR__ . '/../../../includes/header.php'; 
		include __DIR__ . '/../../../includes/toast.php';
	?>

	<?php
		// Fetch projects from database
		$sql = "SELECT project_id, project_code, project_name, status FROM projects ORDER BY project_id DESC";
		$result = $conn->query($sql);
		$projects = [];
		if ($result && $result->num_rows > 0) {
			while ($row = $result->fetch_assoc()) {
				$projects[] = $row;
			}
		}
	?>

    <main class="ml-56 mt-24 p-6">
        <div class="max-w-7xl mx-auto">
            <a href="../proposals.php" class="inline-flex items-center text-gray-600 hover:text-gray-900 mb-6 transition-colors underline">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
                Back to Budget Proposal Dashboard
            </a>

            <div class="mb-8 text-center">
                <h1 class="text-2xl font-bold text-gray-900 mb-2">Create New Budget Proposal</h1>
                <p class="text-sm text-gray-600">Fill in the details below to create a comprehensive budget proposal</p>
            </div>

            <div class="grid grid-cols-2 gap-6">
            
                <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-6 h-[calc(100vh)] overflow-y-auto">

                    <div class="mb-6">
                        <label for="project" class="block text-sm text-gray-700 mb-2">Select Project</label>
                        <select id="project" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-orange-500 outline-none">
                            <option value="">-- Select a Project --</option>
                            <?php foreach ($projects as $project): ?>
                            <option value="<?php echo htmlspecialchars($project['project_code'] . ' - ' . $project['project_name']); ?>" data-id="<?php echo $project['project_id']; ?>">
                                <?php echo htmlspecialchars($project['project_code'] . ' - ' . $project['project_name']); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-6">
                        <label for="proposalTitle" class="block text-sm text-gray-700 mb-2">Proposal Title</label>
                        <input type="text" id="proposalTitle" placeholder="e.g., Q1 2025 Construction Materials Budget" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-orange-500 outline-none">
                    </div>

                    <div class="mb-6">
                        <label for="targetPhase" class="block text-sm text-gray-700 mb-2">Target Milestone / Phase</label>
                        <select id="targetPhase" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-orange-500 outline-none">
                            <option value="">Select Phase...</option>
                            <option value="Phase 1: Mobilization">Phase 1: Mobilization</option>
                            <option value="Phase 2: Structural">Phase 2: Structural</option>
                            <option value="Phase 3: MEPFS">Phase 3: MEPFS</option>
                            <option value="Phase 4: Finishing">Phase 4: Finishing</option>
                        </select>
                    </div>

                    <div class="mb-6 grid grid-cols-2 gap-4">
                        <div>
                            <label for="phaseStartDate" class="block text-sm text-gray-700 mb-2">Phase Start Date</label>
                            <input type="date" id="phaseStartDate" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-orange-500 outline-none">
                        </div>
                        <div>
                            <label for="phaseEndDate" class="block text-sm text-gray-700 mb-2">Phase End Date</label>
                            <input type="date" id="phaseEndDate" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-orange-500 outline-none">
                        </div>
                    </div>

                    <div class="mb-6">
                        <label for="scopeDescription" class="block text-sm text-gray-700 mb-2">Scope Description / Justification</label>
                        <textarea id="scopeDescription" rows="3" placeholder="Describe what this budget will achieve (e.g., 'Covers all excavation, rebar installation, and concrete pouring for the basement level')" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-orange-500 outline-none resize-none"></textarea>
                    </div>

                    <div class="border-b border-gray-200 mb-6">
                        <div class="flex space-x-8">
                            <button id="tab-materials" class="pb-3 border-b-2 border-[#e9922c] text-[#e9922c] font-medium transition-all">
                                Materials
                            </button>
                            <button id="tab-labor" class="pb-3 border-b-2 border-transparent text-gray-500 hover:text-gray-700 font-medium transition-all">
                                Labor
                            </button>
                            <button id="tab-equipment" class="pb-3 border-b-2 border-transparent text-gray-500 hover:text-gray-700 font-medium transition-all">
                                Equipment
                            </button>
                        </div>
                    </div>

                    <div id="panel-materials" class="tab-panel">
                        <div class="space-y-4 mb-6">
                            <div>
                                <label for="material-name" class="block text-sm text-gray-700 mb-2">Material Type</label>
                                <input type="text" id="material-name" list="materials-options" placeholder="Select or type custom material" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-orange-500 outline-none">
                                <datalist id="materials-options">
                                    <option value="Portland Cement (40kg)">
                                    <option value="Deformed Steel Bar - 10mm (DSB)">
                                    <option value="Deformed Steel Bar - 12mm (DSB)">
                                    <option value="Concrete Hollow Blocks (CHB) - 4&quot;">
                                    <option value="Concrete Hollow Blocks (CHB) - 6&quot;">
                                    <option value="Washed Sand / River Sand (cu.m)">
                                    <option value="Gravel (cu.m)">
                                    <option value="Coco Lumber (bd.ft)">
                                    <option value="Marine Plywood (1/4&quot;)">
                                    <option value="Phenolic Board (1/2&quot;)">
                                    <option value="Tie Wire #16 (kg)">
                                </datalist>
                            </div>
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label for="material-quantity" class="block text-sm text-gray-700 mb-2">Quantity</label>
                                    <input type="number" id="material-quantity" placeholder="0" min="0" step="1" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-orange-500 outline-none">
                                </div>
                                <div>
                                    <label for="material-cost" class="block text-sm text-gray-700 mb-2">Unit Cost (₱)</label>
                                    <input type="number" id="material-cost" placeholder="0.00" min="0" step="0.01" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-orange-500 outline-none">
                                </div>
                            </div>

                            <div class="bg-orange-50 border border-orange-200 rounded-lg p-4">
                                <div class="flex justify-between items-center">
                                    <span class="text-sm font-medium text-gray-700">Total Cost:</span>
                                    <span id="material-total-cost" class="text-lg font-bold text-[#e9922c]">₱0.00</span>
                                </div>
                            </div>
                        </div>
                        <button id="add-material" class="w-full bg-[#e9922c] hover:bg-[#d17f1f] text-white rounded-lg py-3 px-6 transition-all shadow-sm flex items-center justify-center mt-18">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                            </svg>
                            Add Material Item
                        </button>
                    </div>

                    <div id="panel-labor" class="tab-panel hidden">
                        <div class="space-y-4 mb-6">
                            <div>
                                <label for="labor-type" class="block text-sm text-gray-700 mb-2">Labor Type</label>
                                <input type="text" id="labor-type" list="labor-options" placeholder="Select or type custom labor role" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-orange-500 outline-none">
                                <datalist id="labor-options">
                                    <option value="General Foreman">
                                    <option value="Skilled Mason">
                                    <option value="Mason Helper">
                                    <option value="Rough Carpenter">
                                    <option value="Finishing Carpenter">
                                    <option value="Steelman / Rebar Man">
                                    <option value="Master Electrician">
                                    <option value="Welder (SMAW)">
                                    <option value="Common Laborer / Peon">
                                    <option value="Safety Officer">
                                </datalist>
                            </div>
                            <div class="grid grid-cols-3 gap-4">
                                <div>
                                    <label for="labor-quantity" class="block text-sm text-gray-700 mb-2">No. of Workers</label>
                                    <input type="number" id="labor-quantity" placeholder="0" min="0" step="1" value="<?php echo $default_labor_workers; ?>" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-orange-500 outline-none">
                                </div>
                                <div>
                                    <label for="labor-rate" class="block text-sm text-gray-700 mb-2">Hourly Rate (₱)</label>
                                    <input type="number" id="labor-hourly-rate" placeholder="0.00" min="0" step="0.01" value="<?php echo number_format($default_labor_hourly, 2, '.', ''); ?>" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-orange-500 outline-none">
                                </div>
                                <div>
                                    <label for="labor-rate" class="block text-sm text-gray-700 mb-2">Daily Rate (₱)</label>
                                    <input type="number" id="labor-daily-rate" placeholder="0.00" min="0" step="0.01" value="<?php echo number_format($default_labor_daily, 2, '.', ''); ?>" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-orange-500 outline-none">
                                </div>
                            </div>
                            <div class="bg-orange-50 border border-orange-200 rounded-lg p-4">
                                <div class="flex justify-between items-center">
                                    <span class="text-sm font-medium text-gray-700">Total Cost:</span>
                                    <span id="labor-total-cost" class="text-lg font-bold text-[#e9922c]">₱0.00</span>
                                </div>
                            </div>
                        </div>
                        <button id="add-labor" class="w-full bg-[#e9922c] hover:bg-[#d17f1f] text-white rounded-lg py-3 px-6 transition-all shadow-sm flex items-center justify-center mt-18">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                            </svg>
                            Add Labor Item
                        </button>
                    </div>

                    <div id="panel-equipment" class="tab-panel hidden">
                        <div class="space-y-4 mb-6">
                            <div>
                                <label for="equipment-name" class="block text-sm text-gray-700 mb-2">Equipment Type</label>
                                <input type="text" id="equipment-name" list="equipment-options" placeholder="Select or type custom equipment" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-orange-500 outline-none">
                                <datalist id="equipment-options">
                                    <option value="One-Bagger Concrete Mixer">
                                    <option value="Plate Compactor">
                                    <option value="Welding Machine (Portable)">
                                    <option value="Cut-off Machine (14&quot;)">
                                    <option value="Jackhammer (Electric)">
                                    <option value="Submersible Pump">
                                    <option value="Scaffolding Set (H-Frame)">
                                    <option value="Angle Grinder (4&quot;)">
                                    <option value="Elf Truck">
                                    <option value="Backhoe">
                                </datalist>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Acquisition Type</label>
                                <div class="flex space-x-6">
                                    <div class="flex items-center">
                                        <input id="acq-rental" type="radio" name="acquisition_type" value="RENTAL" checked 
                                                class="w-4 h-4 text-orange-600 border-gray-300 focus:ring-orange-500 cursor-pointer">
                                        <label for="acq-rental" class="ml-2 block text-sm text-gray-700 cursor-pointer">
                                                Rental
                                        </label>
                                    </div>
                                    <div class="flex items-center">
                                        <input id="acq-purchase" type="radio" name="acquisition_type" value="PURCHASE" 
                                                class="w-4 h-4 text-orange-600 border-gray-300 focus:ring-orange-500 cursor-pointer">
                                        <label for="acq-purchase" class="ml-2 block text-sm text-gray-700 cursor-pointer">
                                                Purchase
                                        </label>
                                    </div>
                                </div>
                            </div>

                            <div id="rental-fields" class="grid grid-cols-3 gap-4">
                                <div>
                                    <label for="equipment-quantity" class="block text-sm text-gray-700 mb-2">Quantity</label>
                                    <input type="number" id="equipment-quantity" placeholder="0" min="0" step="1" value="<?php echo $default_equipment_quantity; ?>" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-orange-500 outline-none">
                                </div>
                                <div>
                                    <label for="equipment-days" class="block text-sm text-gray-700 mb-2">Days</label>
                                    <input type="number" id="equipment-days" placeholder="0" min="0" step="1" value="<?php echo $default_equipment_days; ?>" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-orange-500 outline-none">
                                </div>
                                <div>
                                    <label for="equipment-rental-rate" class="block text-sm text-gray-700 mb-2">Daily Rate (₱)</label>
                                    <input type="number" id="equipment-rental-rate" placeholder="0.00" min="0" step="0.01" value="<?php echo number_format($default_equipment_rate, 2, '.', ''); ?>" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-orange-500 outline-none">
                                </div>
                            </div>
                            
                            <div id="purchase-fields" class="grid grid-cols-2 gap-4 hidden">
                                <div>
                                    <label for="equipment-purchase-quantity" class="block text-sm text-gray-700 mb-2">Quantity</label>
                                    <input type="number" id="equipment-purchase-quantity" placeholder="0" min="0" step="1" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-orange-500 outline-none">
                                </div>
                                <div>
                                    <label for="equipment-purchase-cost" class="block text-sm text-gray-700 mb-2">Unit Cost (₱)</label>
                                    <input type="number" id="equipment-purchase-cost" placeholder="0.00" min="0" step="0.01" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-orange-500 outline-none">
                                </div>
                            </div>

                            <div class="bg-orange-50 border border-orange-200 rounded-lg p-4">
                                <div class="flex justify-between items-center">
                                    <span class="text-sm font-medium text-gray-700">Total Cost:</span>
                                    <span id="equipment-total-cost" class="text-lg font-bold text-[#e9922c]">₱0.00</span>
                                </div>
                            </div>
                        </div>
                        <button id="add-equipment" class="w-full bg-[#e9922c] hover:bg-[#d17f1f] text-white rounded-lg py-3 px-6 transition-all shadow-sm flex items-center justify-center mt-18">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                            </svg>
                            Add Equipment Item
                        </button>
                    </div>
                </div>

                <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-6 h-[calc(100vh)] overflow-y-auto">

                    <div class="flex items-center mb-6">
                        <div class="w-12 h-12 bg-white rounded-lg flex items-center justify-center shadow-md p-1.5 mr-4 border border-gray-100">
                            <img src="/icmis/assets/images/nobg_logo.png" alt="ICMIS Logo" class="w-full h-full object-contain">
                        </div>
                        <div>
                            <h2 class="text-xl font-bold text-gray-900">ICMIS</h2>
                            <p class="text-sm text-gray-500">PROPOSAL SUMMARY</p>
                        </div>
                    </div>

                    <div class="text-sm text-gray-600 mb-6">
                        <?php echo date('F j, Y'); ?>
                    </div>

                    <div class="mb-6 space-y-3">
                        <div class="bg-gradient-to-r from-blue-50 to-blue-100 border-l-4 border-blue-500 rounded-lg p-4">
                            <span class="text-xs text-blue-700 uppercase font-semibold">Project</span>
                            <p id="preview-project" class="text-sm font-medium text-gray-900 mt-1">No project selected</p>
                        </div>

                        <div class="bg-gradient-to-r from-purple-50 to-purple-100 border-l-4 border-purple-500 rounded-lg p-4">
                            <span class="text-xs text-purple-700 uppercase font-semibold">Phase / Milestone</span>
                            <p id="preview-phase" class="text-sm font-medium text-gray-900 mt-1 italic">No phase specified</p>
                        </div>

                        <div class="bg-gradient-to-r from-green-50 to-green-100 border-l-4 border-green-500 rounded-lg p-4">
                            <span class="text-xs text-green-700 uppercase font-semibold">Timeline</span>
                            <p id="preview-timeline" class="text-sm font-medium text-gray-900 mt-1 italic">No timeline set</p>
                        </div>

                        <div class="bg-gradient-to-r from-amber-50 to-orange-100 border-l-4 border-[#e9922c] rounded-lg p-4">
                            <span class="text-xs text-orange-700 uppercase font-semibold">Proposal Title</span>
                            <p id="preview-title" class="text-sm font-medium text-gray-900 mt-1 italic">No title entered</p>
                        </div>

                        <div id="preview-scope-container" class="bg-gray-50 border border-gray-200 rounded-lg p-4" style="display: none;">
                            <span class="text-xs text-gray-600 uppercase font-semibold">Scope Description</span>
                            <p id="preview-scope" class="text-sm text-gray-700 mt-1"></p>
                        </div>
                    </div>

                    <div class="mb-6">
                        <h3 class="text-sm font-semibold text-gray-700 uppercase mb-3">Line Items</h3>
                        
                        <div id="empty-state" class="text-center py-8 text-gray-400 italic">
                            <p class="text-sm">No items added yet. Start adding materials, labor, or equipment from the left panel.</p>
                        </div>
                        <div id="materials-section" class="mb-4" style="display: none;">
                            <div class="flex items-center mb-2">
                                <span class="bg-purple-100 text-purple-700 text-xs px-2 py-1 rounded font-semibold">MATERIALS</span>
                            </div>
                            <div id="materials-list" class="space-y-2 max-h-48 overflow-y-auto"></div>
                        </div>

                        <div id="labor-section" class="mb-4" style="display: none;">
                            <div class="flex items-center mb-2">
                                <span class="bg-amber-100 text-amber-700 text-xs px-2 py-1 rounded font-semibold">LABOR</span>
                            </div>
                            <div id="labor-list" class="space-y-2 max-h-48 overflow-y-auto"></div>
                        </div>

                        <div id="equipment-section" class="mb-4" style="display: none;">
                            <div class="flex items-center mb-2">
                                <span class="bg-green-100 text-green-700 text-xs px-2 py-1 rounded font-semibold">EQUIPMENT</span>
                            </div>
                            <div id="equipment-list" class="space-y-2 max-h-48 overflow-y-auto"></div>
                        </div>
                    </div>

                    <div class="border-t-2 border-gray-200 pt-4 mb-6">
                        <div class="flex justify-between items-center">
                            <span class="text-lg font-semibold text-gray-700">GRAND TOTAL</span>
                            <span id="grand-total" class="text-3xl font-bold text-[#e9922c]">₱0.00</span>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <button id="save-draft" class="border-2 border-gray-300 text-gray-700 hover:bg-gray-50 rounded-lg py-3 px-6 font-medium transition-all">
                            Save Draft
                        </button>
                        <button id="submit-proposal" class="bg-[#e9922c] hover:bg-[#d17f1f] text-white rounded-lg py-3 px-6 font-medium transition-all shadow-sm">
                            Submit Proposal
                        </button>
                    </div>

                </div>
            </div>
        </div>
    </main>

    <script>
        // State Management
        let items = [];
        let activeTab = 'materials';

        // Tab Switching
        const tabs = ['materials', 'labor', 'equipment'];
        tabs.forEach(tab => {
            document.getElementById(`tab-${tab}`).addEventListener('click', () => {
                switchTab(tab);
            });
        });

        function switchTab(tab) {
            activeTab = tab;
            
            // Update tab buttons
            tabs.forEach(t => {
                const tabBtn = document.getElementById(`tab-${t}`);
                const panel = document.getElementById(`panel-${t}`);
                
                if (t === tab) {
                    tabBtn.classList.remove('border-transparent', 'text-gray-500');
                    tabBtn.classList.add('border-[#e9922c]', 'text-[#e9922c]');
                    panel.classList.remove('hidden');
                } else {
                    tabBtn.classList.remove('border-[#e9922c]', 'text-[#e9922c]');
                    tabBtn.classList.add('border-transparent', 'text-gray-500');
                    panel.classList.add('hidden');
                }
            });
        }

        // Add Item Functions
        document.getElementById('add-material').addEventListener('click', () => {
            const materialInput = document.getElementById('material-name');
            const name = materialInput.value.trim();
            const quantity = parseFloat(document.getElementById('material-quantity').value) || 0;
            const cost = parseFloat(document.getElementById('material-cost').value) || 0;

            if (name && quantity > 0 && cost > 0) {
                addItem('materials', name, quantity, cost);
                // Clear inputs
                materialInput.value = '';
                document.getElementById('material-quantity').value = '';
                document.getElementById('material-cost').value = '';
                document.getElementById('material-total-cost').textContent = '₱0.00';
                showToast('Material item added successfully', 'success');
            } else {
                showToast('Please fill in all fields with valid values', 'warning');
            }
        });

        document.getElementById('add-labor').addEventListener('click', () => {
            const laborInput = document.getElementById('labor-type');
            const name = laborInput.value.trim();
            const quantity = parseFloat(document.getElementById('labor-quantity').value) || 0;
            const cost = parseFloat(document.getElementById('labor-daily-rate').value) || 0;

            if (name && quantity > 0 && cost > 0) {
                addItem('labor', name, quantity, cost);
                // Clear inputs
                laborInput.value = '';
                document.getElementById('labor-quantity').value = '';
                document.getElementById('labor-daily-rate').value = '';
                document.getElementById('labor-hourly-rate').value = '';
                document.getElementById('labor-total-cost').textContent = '₱0.00';
                showToast('Labor item added successfully', 'success');
            } else {
                showToast('Please fill in all fields with valid values', 'warning');
            }
        });

        document.getElementById('add-equipment').addEventListener('click', () => {
            const equipmentInput = document.getElementById('equipment-name');
            const name = equipmentInput.value.trim();
            
            let quantity = 0;
            let totalCost = 0;
            let acquisitionType = '';

            // Check which acquisition type is selected
            if (acqRentalRadio.checked) {
                acquisitionType = 'RENTAL';
                const qty = parseFloat(equipmentQuantity.value) || 0;
                const days = parseFloat(equipmentDays.value) || 0;
                const rate = parseFloat(equipmentRentalRate.value) || 0;
                quantity = qty;
                totalCost = qty * days * rate;

                if (name && qty > 0 && days > 0 && rate > 0) {
                    addItem('equipment', name, quantity, totalCost / quantity); // Use average cost per unit
                    // Clear inputs
                    equipmentInput.value = '';
                    equipmentQuantity.value = '';
                    equipmentDays.value = '';
                    equipmentRentalRate.value = '';
                    equipmentTotalCost.textContent = '₱0.00';
                    showToast('Equipment (Rental) added successfully', 'success');
                } else {
                    showToast('Please fill in all rental fields with valid values', 'warning');
                }
            } else {
                acquisitionType = 'PURCHASE';
                const qty = parseFloat(equipmentPurchaseQuantity.value) || 0;
                const cost = parseFloat(equipmentPurchaseCost.value) || 0;
                quantity = qty;
                totalCost = qty * cost;

                if (name && qty > 0 && cost > 0) {
                    addItem('equipment', name, quantity, cost);
                    // Clear inputs
                    equipmentInput.value = '';
                    equipmentPurchaseQuantity.value = '';
                    equipmentPurchaseCost.value = '';
                    equipmentTotalCost.textContent = '₱0.00';
                    showToast('Equipment (Purchase) added successfully', 'success');
                } else {
                    showToast('Please fill in all purchase fields with valid values', 'warning');
                }
            }
        });

        function addItem(category, name, quantity, unitCost) {
            const subtotal = quantity * unitCost;
            const item = {
                id: Date.now(),
                category: category,
                name: name,
                quantity: quantity,
                unitCost: unitCost,
                subtotal: subtotal
            };

            items.push(item);
            renderItems();
            updateGrandTotal();
        }

        function removeItem(id) {
            items = items.filter(item => item.id !== id);
            renderItems();
            updateGrandTotal();
            showToast('Item removed successfully', 'error');
        }

        function renderItems() {
            const emptyState = document.getElementById('empty-state');
            const materialsSection = document.getElementById('materials-section');
            const laborSection = document.getElementById('labor-section');
            const equipmentSection = document.getElementById('equipment-section');
            const materialsList = document.getElementById('materials-list');
            const laborList = document.getElementById('labor-list');
            const equipmentList = document.getElementById('equipment-list');

            if (items.length === 0) {
                emptyState.style.display = 'block';
                materialsSection.style.display = 'none';
                laborSection.style.display = 'none';
                equipmentSection.style.display = 'none';
                return;
            }

            emptyState.style.display = 'none';

            // Separate items by category
            const materialItems = items.filter(item => item.category === 'materials');
            const laborItems = items.filter(item => item.category === 'labor');
            const equipmentItems = items.filter(item => item.category === 'equipment');

            // Render Materials
            
            if (materialItems.length > 0) {
                materialsSection.style.display = 'block';
                
                materialsList.innerHTML = '';
                materialItems.forEach((item, index) => {
                    const element = createItemElement(item);
                    materialsList.appendChild(element);
                });
            } else {
                materialsSection.style.display = 'none';
            }

            // Render Labor
            if (laborItems.length > 0) {
                laborSection.style.display = 'block';
                laborList.innerHTML = '';
                laborItems.forEach((item) => {
                    const element = createItemElement(item);
                    laborList.appendChild(element);
                });
            } else {
                laborSection.style.display = 'none';
            }

            // Render Equipment
            if (equipmentItems.length > 0) {
                equipmentSection.style.display = 'block';
                equipmentList.innerHTML = '';
                equipmentItems.forEach((item) => {
                    const element = createItemElement(item);
                    equipmentList.appendChild(element);
                });
            } else {
                equipmentSection.style.display = 'none';
            }
        }

        function createItemElement(item) {
            const div = document.createElement('div');
            div.className = 'bg-gray-50 rounded-lg p-3 border border-gray-200';
            
            const flexContainer = document.createElement('div');
            flexContainer.className = 'flex justify-between items-start mb-2';
            
            const leftContent = document.createElement('div');
            leftContent.className = 'flex-1';
            
            const itemName = document.createElement('p');
            itemName.className = 'text-sm font-medium text-gray-900';
            itemName.textContent = item.name;
            
            const itemDetails = document.createElement('p');
            itemDetails.className = 'text-xs text-gray-500';
            itemDetails.textContent = `${item.quantity} × ₱${formatPeso(item.unitCost)}`;
            
            leftContent.appendChild(itemName);
            leftContent.appendChild(itemDetails);
            
            const removeBtn = document.createElement('button');
            removeBtn.className = 'text-red-500 hover:text-red-700 transition-colors ml-2';
            removeBtn.onclick = () => removeItem(item.id);
            removeBtn.innerHTML = `
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            `;
            
            flexContainer.appendChild(leftContent);
            flexContainer.appendChild(removeBtn);
            
            const totalDiv = document.createElement('div');
            totalDiv.className = 'text-right';
            totalDiv.innerHTML = `<span class="text-sm font-semibold text-gray-900">₱${formatPeso(item.subtotal)}</span>`;
            
            div.appendChild(flexContainer);
            div.appendChild(totalDiv);
            
            return div;
        }

        function updateGrandTotal() {
            const total = items.reduce((sum, item) => sum + item.subtotal, 0);
            document.getElementById('grand-total').textContent = '₱' + formatPeso(total);
        }

        function formatPeso(amount) {
            return amount.toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ',');
        }

        // Live Preview Updates
        document.getElementById('project').addEventListener('change', (e) => {
            const previewProject = document.getElementById('preview-project');
            const text = e.target.value || 'No project selected';
            previewProject.textContent = text;
            previewProject.className = text === 'No project selected' ? 'text-sm font-medium text-gray-400 mt-1 italic' : 'text-sm font-medium text-gray-900 mt-1';
        });

        document.getElementById('proposalTitle').addEventListener('input', (e) => {
            const previewTitle = document.getElementById('preview-title');
            const text = e.target.value.trim() || 'No title entered';
            previewTitle.textContent = text;
            previewTitle.className = text === 'No title entered' ? 'text-sm font-medium text-gray-400 mt-1 italic' : 'text-sm font-medium text-gray-900 mt-1';
        });

        document.getElementById('targetPhase').addEventListener('change', (e) => {
            const previewPhase = document.getElementById('preview-phase');
            const text = e.target.value || 'No phase specified';
            previewPhase.textContent = text;
            previewPhase.className = text === 'No phase specified' ? 'text-sm font-medium text-gray-400 mt-1 italic' : 'text-sm font-medium text-gray-900 mt-1';
        });

        function updateTimeline() {
            const startDate = document.getElementById('phaseStartDate').value;
            const endDate = document.getElementById('phaseEndDate').value;
            const previewTimeline = document.getElementById('preview-timeline');
            
            if (startDate && endDate) {
                const start = new Date(startDate);
                const end = new Date(endDate);
                const formattedStart = start.toLocaleDateString('en-US', { month: 'short', day: '2-digit', year: 'numeric' });
                const formattedEnd = end.toLocaleDateString('en-US', { month: 'short', day: '2-digit', year: 'numeric' });
                previewTimeline.textContent = `${formattedStart} - ${formattedEnd}`;
                previewTimeline.className = 'text-sm font-medium text-gray-900 mt-1';
            } else {
                previewTimeline.textContent = 'No timeline set';
                previewTimeline.className = 'text-sm font-medium text-gray-400 mt-1 italic';
            }
        }

        document.getElementById('phaseStartDate').addEventListener('change', updateTimeline);
        document.getElementById('phaseEndDate').addEventListener('change', updateTimeline);

        document.getElementById('scopeDescription').addEventListener('input', (e) => {
            const previewScope = document.getElementById('preview-scope');
            const previewScopeContainer = document.getElementById('preview-scope-container');
            const text = e.target.value.trim();
            
            if (text) {
                previewScope.textContent = text;
                previewScopeContainer.style.display = 'block';
            } else {
                previewScopeContainer.style.display = 'none';
            }
        });

        // Labor Rate Auto-Calculation (Two-Way Binding)
        const laborDailyRateInput = document.getElementById('labor-daily-rate');
        const laborHourlyRateInput = document.getElementById('labor-hourly-rate');
        const STANDARD_WORK_HOURS = 8;

        // Flag to prevent infinite loop
        let isCalculating = false;

        // When Daily Rate changes, calculate Hourly Rate
        laborDailyRateInput.addEventListener('input', function() {
            if (isCalculating) return;
            
            const dailyRate = parseFloat(this.value);
            
            if (isNaN(dailyRate) || dailyRate <= 0 || this.value.trim() === '') {
                laborHourlyRateInput.value = '';
                return;
            }
            
            isCalculating = true;
            const hourlyRate = dailyRate / STANDARD_WORK_HOURS;
            laborHourlyRateInput.value = hourlyRate.toFixed(2);
            isCalculating = false;
        });

        // When Hourly Rate changes, calculate Daily Rate
        laborHourlyRateInput.addEventListener('input', function() {
            if (isCalculating) return;
            
            const hourlyRate = parseFloat(this.value);
            
            if (isNaN(hourlyRate) || hourlyRate <= 0 || this.value.trim() === '') {
                laborDailyRateInput.value = '';
                return;
            }
            
            isCalculating = true;
            const dailyRate = hourlyRate * STANDARD_WORK_HOURS;
            laborDailyRateInput.value = dailyRate.toFixed(2);
            isCalculating = false;
        });

        // Equipment Acquisition Type Toggle and Auto-Calculation
        const acqRentalRadio = document.getElementById('acq-rental');
        const acqPurchaseRadio = document.getElementById('acq-purchase');
        const rentalFields = document.getElementById('rental-fields');
        const purchaseFields = document.getElementById('purchase-fields');
        const equipmentTotalCost = document.getElementById('equipment-total-cost');

        // Rental fields
        const equipmentQuantity = document.getElementById('equipment-quantity');
        const equipmentDays = document.getElementById('equipment-days');
        const equipmentRentalRate = document.getElementById('equipment-rental-rate');

        // Purchase fields
        const equipmentPurchaseQuantity = document.getElementById('equipment-purchase-quantity');
        const equipmentPurchaseCost = document.getElementById('equipment-purchase-cost');

        // Toggle between Rental and Purchase fields
        function toggleAcquisitionType() {
            if (acqRentalRadio.checked) {
                rentalFields.classList.remove('hidden');
                purchaseFields.classList.add('hidden');
                // Clear purchase fields
                equipmentPurchaseQuantity.value = '';
                equipmentPurchaseCost.value = '';
                calculateEquipmentTotal();
            } else {
                rentalFields.classList.add('hidden');
                purchaseFields.classList.remove('hidden');
                // Clear rental fields
                equipmentQuantity.value = '';
                equipmentDays.value = '';
                equipmentRentalRate.value = '';
                calculateEquipmentTotal();
            }
        }

        // Calculate equipment total cost
        function calculateEquipmentTotal() {
            let total = 0;

            if (acqRentalRadio.checked) {
                // Rental: Quantity × Days × Daily Rate
                const quantity = parseFloat(equipmentQuantity.value) || 0;
                const days = parseFloat(equipmentDays.value) || 0;
                const rate = parseFloat(equipmentRentalRate.value) || 0;
                total = quantity * days * rate;
            } else {
                // Purchase: Quantity × Unit Cost
                const quantity = parseFloat(equipmentPurchaseQuantity.value) || 0;
                const cost = parseFloat(equipmentPurchaseCost.value) || 0;
                total = quantity * cost;
            }

            equipmentTotalCost.textContent = '₱' + formatPeso(total);
        }

        // Event listeners for acquisition type
        acqRentalRadio.addEventListener('change', toggleAcquisitionType);
        acqPurchaseRadio.addEventListener('change', toggleAcquisitionType);

        // Event listeners for rental calculation
        equipmentQuantity.addEventListener('input', calculateEquipmentTotal);
        equipmentDays.addEventListener('input', calculateEquipmentTotal);
        equipmentRentalRate.addEventListener('input', calculateEquipmentTotal);

        // Event listeners for purchase calculation
        equipmentPurchaseQuantity.addEventListener('input', calculateEquipmentTotal);
        equipmentPurchaseCost.addEventListener('input', calculateEquipmentTotal);

        // Material Total Cost Calculation
        const materialQuantityInput = document.getElementById('material-quantity');
        const materialCostInput = document.getElementById('material-cost');
        const materialTotalCost = document.getElementById('material-total-cost');

        function calculateMaterialTotal() {
            const quantity = parseFloat(materialQuantityInput.value) || 0;
            const cost = parseFloat(materialCostInput.value) || 0;
            const total = quantity * cost;
            materialTotalCost.textContent = '₱' + formatPeso(total);
        }

        materialQuantityInput.addEventListener('input', calculateMaterialTotal);
        materialCostInput.addEventListener('input', calculateMaterialTotal);

        // Labor Total Cost Calculation
        const laborQuantityInput = document.getElementById('labor-quantity');
        const laborTotalCost = document.getElementById('labor-total-cost');

        function calculateLaborTotal() {
            const quantity = parseFloat(laborQuantityInput.value) || 0;
            const dailyRate = parseFloat(laborDailyRateInput.value) || 0;
            const total = quantity * dailyRate;
            laborTotalCost.textContent = '₱' + formatPeso(total);
        }

        laborQuantityInput.addEventListener('input', calculateLaborTotal);
        laborDailyRateInput.addEventListener('input', calculateLaborTotal);
        laborHourlyRateInput.addEventListener('input', calculateLaborTotal);

        // Calculate initial labor total on page load
        calculateLaborTotal();

        // Form Submission
        document.getElementById('save-draft').addEventListener('click', () => {
            if (validateForm()) {
                submitProposal('DRAFT');
            }
        });

        document.getElementById('submit-proposal').addEventListener('click', () => {
            if (validateForm()) {
                submitProposal('PENDING');
            }
        });

        function validateForm() {
            const project = document.getElementById('project').value;
            const title = document.getElementById('proposalTitle').value.trim();
            const targetPhase = document.getElementById('targetPhase').value;
            const startDate = document.getElementById('phaseStartDate').value;
            const endDate = document.getElementById('phaseEndDate').value;
            const scopeDescription = document.getElementById('scopeDescription').value.trim();

            if (!project) {
                showToast('Please select a project', 'warning');
                return false;
            }

            if (!title) {
                showToast('Please enter a proposal title', 'warning');
                return false;
            }

            if (!targetPhase) {
                showToast('Please select a target phase', 'warning');
                return false;
            }

            if (!startDate) {
                showToast('Please set phase start date', 'warning');
                return false;
            }

            if (!endDate) {
                showToast('Please set phase end date', 'warning');
                return false;
            }

            if (new Date(endDate) <= new Date(startDate)) {
                showToast('End date must be after start date', 'warning');
                return false;
            }

            if (!scopeDescription) {
                showToast('Please provide scope description', 'warning');
                return false;
            }

            if (items.length === 0) {
                showToast('Please add at least one item to the proposal', 'warning');
                return false;
            }

            return true;
        }

        function submitProposal(status) {
            const projectSelect = document.getElementById('project');
            const selectedOption = projectSelect.options[projectSelect.selectedIndex];
            const projectId = selectedOption.getAttribute('data-id');
            const title = document.getElementById('proposalTitle').value.trim();
            const targetPhase = document.getElementById('targetPhase').value;
            const startDate = document.getElementById('phaseStartDate').value;
            const endDate = document.getElementById('phaseEndDate').value;
            const scopeDescription = document.getElementById('scopeDescription').value.trim();
            const totalAmount = items.reduce((sum, item) => sum + item.subtotal, 0);

            const data = {
                project_id: projectId,
                title: title,
                target_phase: targetPhase,
                phase_start_date: startDate,
                phase_end_date: endDate,
                scope_description: scopeDescription,
                items: items,
                status: status,
                total_amount: totalAmount
            };

            // Disable buttons during submission
            document.getElementById('save-draft').disabled = true;
            document.getElementById('submit-proposal').disabled = true;

            console.log('Submitting data:', data);

            fetch('http://localhost/icmis/modules/budget/budget_proposal/save_proposal.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(data)
            })
            .then(response => {
                console.log('Response status:', response.status);
                return response.text();
            })
            .then(text => {
                console.log('Response text:', text);
                try {
                    const result = JSON.parse(text);
                    if (result.success) {
                        showToast(result.message + ' - Code: ' + result.code, 'success', true);
                        // Redirect to proposals page after a short delay
                        setTimeout(() => {
                            window.location.href = '../proposals.php';
                        }, 300);
                    } else {
                        showToast('Error: ' + result.message, 'error');
                        // Re-enable buttons
                        document.getElementById('save-draft').disabled = false;
                        document.getElementById('submit-proposal').disabled = false;
                    }
                } catch (e) {
                    console.error('JSON parse error:', e);
                    showToast('Server returned invalid response', 'error');
                    document.getElementById('save-draft').disabled = false;
                    document.getElementById('submit-proposal').disabled = false;
                }
            })
            .catch(error => {
                console.error('Fetch error:', error);
                showToast('Error submitting proposal: ' + error.message, 'error');
                // Re-enable buttons
                document.getElementById('save-draft').disabled = false;
                document.getElementById('submit-proposal').disabled = false;
            });
        }

        // Make removeItem available globally
        window.removeItem = removeItem;
    </script>
</body>
</html>