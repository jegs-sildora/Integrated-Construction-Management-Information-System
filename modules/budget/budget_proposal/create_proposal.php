<?php
// ============================================================
// ALL PHP LOGIC MUST BE BEFORE ANY HTML OUTPUT
// ============================================================

include __DIR__ . '/../project_context.php';
$conn = getBudgetConnection();

// Initialize default values
$default_labor_workers = 0;
$default_labor_hourly = 0.00;
$default_labor_daily = 0.00;
$default_equipment_quantity = 0;
$default_equipment_days = 0;
$default_equipment_rate = 0.00;

// Fetch projects from database
$sql = "SELECT project_id, project_code, project_name, status FROM icmis_projects ORDER BY project_id DESC";
$result = $conn->query($sql);
$projects = [];
if ($result && $result->num_rows > 0) {
  while ($row = $result->fetch_assoc()) {
    $projects[] = $row;
  }
}

// Get selected project and phase from global context
$selected_project_id = getProjectContext($conn);
$selected_phase_id = getPhaseContext();

// Normalize phase id if provided via URL or numeric string
if (isset($_GET['phase_id']) && !empty($_GET['phase_id'])) {
    $selected_phase_id = intval($_GET['phase_id']);
} else {
    $selected_phase_id = is_numeric($selected_phase_id) ? intval($selected_phase_id) : 0;
}

// Preload phases for the selected project (if any)
$result_phases = null;
if ($selected_project_id && $selected_project_id > 0) {
    $sql_ph = "SELECT phase_id, phase_name, start_date, end_date FROM icmis_project_phases WHERE project_id = ? ORDER BY start_date ASC";
    $stmt_ph = $conn->prepare($sql_ph);
    if ($stmt_ph) {
        $stmt_ph->bind_param('i', $selected_project_id);
        $stmt_ph->execute();
        $result_phases = $stmt_ph->get_result();
        $stmt_ph->close();
    }
}

// Header variables
$pageTitle = "Budget Proposals";
$pageSubTitle = "Create New Proposal";
$pageSection = "Budget & Cost Control";
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <?php include __DIR__ . '/../../../includes/head_assetsv2.php'; ?>
  <title>Create Budget Proposal | ICMIS</title>
  <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
  <style>
    * { font-family: 'Inter', sans-serif; }
  </style>
</head>
<body class="bg-gray-50">
  <?php 
    include __DIR__ . '/../../../includes/sidebar.php';
    include __DIR__ . '/../../../includes/toast.php';
    include __DIR__ . '/../../../includes/header.php'; 
  ?>

    <main class="ml-56 mt-18 p-6 transition-all duration-300 animate-fade-in">
        <div class="max-w-7xl mx-auto">
            <a href="../proposals.php" class="inline-flex items-center text-gray-600 hover:text-gray-900 mb-6 transition-colors underline">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="4" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
            </a>

            <div class="mb-8 text-center">
                <h1 class="text-3xl font-bold text-gray-900 mb-2">Create New Budget Proposal</h1>
                <p class="text-sm text-gray-600">Fill in the details below to create a comprehensive budget proposal</p>
            </div>

            <div class="grid grid-cols-2 gap-6">
            
                <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-6 h-[calc(100vh)] overflow-y-auto">

                    <div class="mb-6">
                        <label for="project" class="block text-sm text-gray-700 mb-2">Select Project</label>
                        <select id="project" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-orange-500 outline-none">
                            <option value="">-- Select a Project --</option>
                            <?php foreach ($projects as $project):
                                $isSelected = ($selected_project_id && $selected_project_id == $project['project_id']) ? 'selected' : ''; ?>
                                <option value="<?php echo htmlspecialchars($project['project_code'] . ' - ' . $project['project_name']); ?>" data-id="<?php echo $project['project_id']; ?>" <?php echo $isSelected; ?>>
                                    <?php echo htmlspecialchars($project['project_code'] . ' - ' . $project['project_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-6">
                        <label for="targetPhase" class="block text-sm text-gray-700 mb-2">Target Milestone / Phase</label>
                        <select id="targetPhase" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-orange-500 outline-none">
                            <option value="">Select Phase...</option>
                            <?php if ($result_phases && $result_phases->num_rows > 0):
                                while ($ph = $result_phases->fetch_assoc()): ?>
                                    <option value="<?php echo intval($ph['phase_id']); ?>" data-id="<?php echo intval($ph['phase_id']); ?>" data-start="<?php echo htmlspecialchars($ph['start_date']); ?>" data-end="<?php echo htmlspecialchars($ph['end_date']); ?>" <?php echo ($selected_phase_id && $selected_phase_id == $ph['phase_id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($ph['phase_name']); ?>
                                    </option>
                                <?php endwhile; 
                            endif; ?>
                        </select>
                    </div>

                    <div class="mb-6">
                        <label for="proposalTitle" class="block text-sm text-gray-700 mb-2">Proposal Title</label>
                        <input type="text" id="proposalTitle" placeholder="e.g., Q1 2025 Construction Materials Budget" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-orange-500 outline-none">
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
                        <button id="add-material" class="w-full bg-[#e9922c] hover:bg-[#d17f1f] text-white rounded-lg py-3 px-6 transition-all shadow-sm flex items-center justify-center mt-18 font-bold">
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
                        <button id="save-draft" class="border-2 border-gray-300 text-gray-700 hover:bg-gray-50 rounded-lg py-3 px-6 font-bold transition-all">
                            Save Draft
                        </button>
                        <button id="submit-proposal" class="bg-[#e9922c] hover:bg-[#d17f1f] text-white rounded-lg py-3 px-6 transition-all shadow-sm font-bold">
                            Submit Proposal
                        </button>
                    </div>

                </div>
            </div>
        </div>
    </main>

    <script src="js/create_proposal.js"></script>
</body>
</html>