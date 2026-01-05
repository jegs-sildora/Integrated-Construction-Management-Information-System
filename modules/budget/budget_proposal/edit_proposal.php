<?php
// ============================================================
// ALL PHP LOGIC MUST BE BEFORE ANY HTML OUTPUT
// ============================================================

include __DIR__ . '/../project_context.php';
$conn = getBudgetConnection();

// Get proposal ID from URL
$proposal_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($proposal_id <= 0) {
  header('Location: ../proposals.php');
  exit;
}

// Fetch proposal details
$sql = "SELECT bp.*, p.project_code, p.project_name 
    FROM budget_proposals bp 
    LEFT JOIN icmis_projects p ON bp.project_id = p.project_id 
    WHERE bp.proposal_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $proposal_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
  header('Location: ../proposals.php');
  exit;
}

$proposal = $result->fetch_assoc();
$stmt->close();

// Fetch line items
$sql_items = "SELECT * FROM budget_line_items WHERE proposal_id = ? ORDER BY line_item_id";
$stmt_items = $conn->prepare($sql_items);
$stmt_items->bind_param("i", $proposal_id);
$stmt_items->execute();
$result_items = $stmt_items->get_result();

$line_items = [];
while ($row = $result_items->fetch_assoc()) {
  $line_items[] = $row;
}
$stmt_items->close();

// Fetch all projects for dropdown
$sql_projects = "SELECT project_id, project_code, project_name, status FROM icmis_projects ORDER BY project_id DESC";
$result_projects = $conn->query($sql_projects);
$projects = [];
if ($result_projects && $result_projects->num_rows > 0) {
  while ($row = $result_projects->fetch_assoc()) {
    $projects[] = $row;
  }
}

// Header variables
$pageTitle = "Budget Proposals";
$pageSubTitle = "Edit Proposal";
$pageSection = "Budget & Cost Control";
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <?php include __DIR__ . '/../../../includes/head_assetsv2.php'; ?>
  <title>Edit Budget Proposal | ICMIS</title>
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

	<main class="ml-56 mt-20 p-6">
		<div class="max-w-7xl mx-auto">
			<a href="../proposals.php" class="inline-flex items-center text-gray-600 hover:text-gray-900 mb-6 transition-colors underline">
				<svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
					<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
				</svg>
			</a>

			<div class="mb-8 text-center">
				<h1 class="text-3xl font-bold text-gray-900 mb-2">Edit Budget Proposal</h1>
				<p class="text-gray-600">Proposal Code: <span class="font-semibold text-[#e9922c]"><?php echo htmlspecialchars($proposal['code']); ?></span></p>
			</div>

			<div class="grid grid-cols-2 gap-6">
			
				<div class="bg-white rounded-xl border border-gray-200 shadow-sm p-6 h-[calc(100vh-170px)] overflow-y-auto">
					<div class="mb-6">
						<label for="project" class="block text-sm text-gray-700 mb-2">Select Project</label>
						<select id="project" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-orange-500 outline-none">
							<option value="">-- Select a Project --</option>
							<?php foreach ($projects as $project): ?>
							<option value="<?php echo htmlspecialchars($project['project_code'] . ' - ' . $project['project_name']); ?>" 
								data-id="<?php echo $project['project_id']; ?>"
								<?php echo ($project['project_id'] == $proposal['project_id']) ? 'selected' : ''; ?>>
								<?php echo htmlspecialchars($project['project_code'] . ' - ' . $project['project_name']); ?>
							</option>
							<?php endforeach; ?>
						</select>
					</div>

					<div class="mb-6">
							<label for="targetPhase" class="block text-sm text-gray-700 mb-2">Target Milestone / Phase</label>
							<select id="targetPhase" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-orange-500 outline-none">
									<option value="">Loading phases...</option>
							</select>
					</div>

					<div class="mb-6">
						<label for="proposalTitle" class="block text-sm text-gray-700 mb-2">Proposal Title</label>
						<input type="text" id="proposalTitle" placeholder="e.g., Q1 2025 Construction Materials Budget" 
							value="<?php echo htmlspecialchars($proposal['title']); ?>"
							class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-orange-500 outline-none">
					</div>

					<div class="mb-6 grid grid-cols-2 gap-4">
						<div>
							<label for="phaseStartDate" class="block text-sm text-gray-700 mb-2">Phase Start Date</label>
							<input type="date" id="phaseStartDate" 
								value="<?php echo isset($proposal['phase_start_date']) ? htmlspecialchars($proposal['phase_start_date']) : ''; ?>"
								class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-orange-500 outline-none">
						</div>
						<div>
							<label for="phaseEndDate" class="block text-sm text-gray-700 mb-2">Phase End Date</label>
							<input type="date" id="phaseEndDate" 
								value="<?php echo isset($proposal['phase_end_date']) ? htmlspecialchars($proposal['phase_end_date']) : ''; ?>"
								class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-orange-500 outline-none">
						</div>
					</div>

					<div class="mb-6">
						<label for="scopeDescription" class="block text-sm text-gray-700 mb-2">Scope Description / Justification</label>
						<textarea id="scopeDescription" rows="3" placeholder="Describe what this budget will achieve (e.g., 'Covers all excavation, rebar installation, and concrete pouring for the basement level')" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-orange-500 outline-none resize-none"><?php echo isset($proposal['scope_description']) ? htmlspecialchars($proposal['scope_description']) : ''; ?></textarea>
					</div>

					<div class="mb-6">
						<label class="block text-sm text-gray-700 mb-3">Status</label>
						<div class="grid grid-cols-2 gap-3">
							<label class="flex items-center px-4 py-3 border border-gray-300 rounded-lg cursor-pointer hover:bg-gray-50 transition-colors <?php echo ($proposal['status'] == 'DRAFT') ? 'bg-orange-50 border-orange-500' : ''; ?>">
								<input type="radio" name="proposalStatus" value="DRAFT" <?php echo ($proposal['status'] == 'DRAFT') ? 'checked' : ''; ?> class="w-4 h-4 text-orange-500 border-gray-300 focus:ring-orange-500">
								<span class="ml-3 text-sm font-medium text-gray-700">Draft</span>
							</label>
							<label class="flex items-center px-4 py-3 border border-gray-300 rounded-lg cursor-pointer hover:bg-gray-50 transition-colors <?php echo ($proposal['status'] == 'PENDING') ? 'bg-orange-50 border-orange-500' : ''; ?>">
								<input type="radio" name="proposalStatus" value="PENDING" <?php echo ($proposal['status'] == 'PENDING') ? 'checked' : ''; ?> class="w-4 h-4 text-orange-500 border-gray-300 focus:ring-orange-500">
								<span class="ml-3 text-sm font-medium text-gray-700">Pending</span>
							</label>
							<label class="flex items-center px-4 py-3 border border-gray-300 rounded-lg cursor-pointer hover:bg-gray-50 transition-colors <?php echo ($proposal['status'] == 'APPROVED') ? 'bg-orange-50 border-orange-500' : ''; ?>">
								<input type="radio" name="proposalStatus" value="APPROVED" <?php echo ($proposal['status'] == 'APPROVED') ? 'checked' : ''; ?> class="w-4 h-4 text-orange-500 border-gray-300 focus:ring-orange-500">
								<span class="ml-3 text-sm font-medium text-gray-700">Approved</span>
							</label>
							<label class="flex items-center px-4 py-3 border border-gray-300 rounded-lg cursor-pointer hover:bg-gray-50 transition-colors <?php echo ($proposal['status'] == 'REJECTED') ? 'bg-orange-50 border-orange-500' : ''; ?>">
								<input type="radio" name="proposalStatus" value="REJECTED" <?php echo ($proposal['status'] == 'REJECTED') ? 'checked' : ''; ?> class="w-4 h-4 text-orange-500 border-gray-300 focus:ring-orange-500">
								<span class="ml-3 text-sm font-medium text-gray-700">Rejected</span>
							</label>
						</div>
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
								<label for="material-name" class="block text-sm text-gray-700 mb-2">Item Name</label>
								<input type="text" id="material-name" list="materials-options" placeholder="Select or type custom material" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-orange-500 outline-none">
								<datalist id="materials-options">
									<option value="Portland Cement (40kg)">
									<option value="Deformed Steel Bar - 10mm (DSB)">
									<option value="Deformed Steel Bar - 12mm (DSB)">
									<option value="Concrete Hollow Blocks (CHB) - 4&quot;">
									<option value="Concrete Hollow Blocks (CHB) - 6&quot;">
									<option value="Washed Sand / River Sand (cu.m)">
									<option value="Gravel 3/4 (cu.m)">
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
								<select id="labor-type" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-orange-500 outline-none">
									<option value="">-- Select Role --</option>
									<option value="General Foreman">General Foreman</option>
									<option value="Skilled Mason">Skilled Mason</option>
									<option value="Mason Helper">Mason Helper</option>
									<option value="Rough Carpenter">Rough Carpenter</option>
									<option value="Finishing Carpenter">Finishing Carpenter</option>
									<option value="Steelman / Rebar Man">Steelman / Rebar Man</option>
									<option value="Master Electrician">Master Electrician</option>
									<option value="Welder (SMAW)">Welder (SMAW)</option>
									<option value="Common Laborer / Peon">Common Laborer / Peon</option>
									<option value="Safety Officer">Safety Officer</option>
								</select>
							</div>
							<div class="grid grid-cols-2 gap-4">
								<div>
									<label for="labor-quantity" class="block text-sm text-gray-700 mb-2">Workers/Hours</label>
									<input type="number" id="labor-quantity" placeholder="0" min="0" step="1" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-orange-500 outline-none">
								</div>
								<div>
									<label for="labor-rate" class="block text-sm text-gray-700 mb-2">Rate per Unit (₱)</label>
									<input type="number" id="labor-rate" placeholder="0.00" min="0" step="0.01" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-orange-500 outline-none">
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
								<label for="equipment-name" class="block text-sm text-gray-700 mb-2">Equipment Name</label>
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
									<option value="Elf Truck (Rental)">
									<option value="Backhoe (Rental - Per Hour)">
								</datalist>
							</div>
							<div class="grid grid-cols-2 gap-4">
								<div>
									<label for="equipment-quantity" class="block text-sm text-gray-700 mb-2">Quantity/Days</label>
									<input type="number" id="equipment-quantity" placeholder="0" min="0" step="1" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-orange-500 outline-none">
								</div>
								<div>
									<label for="equipment-rate" class="block text-sm text-gray-700 mb-2">Daily/Unit Rate (₱)</label>
									<input type="number" id="equipment-rate" placeholder="0.00" min="0" step="0.01" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-orange-500 outline-none">
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

				<div class="bg-white rounded-xl border border-gray-200 shadow-sm p-6 h-[calc(100vh-170px)] overflow-y-auto">
					<div class="flex items-center mb-6">
						<div class="w-12 h-12 bg-[#e9922c] rounded-lg flex items-center justify-center text-white text-2xl font-bold mr-4">
							I
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
							<p id="preview-project" class="text-sm font-medium text-gray-900 mt-1"><?php echo htmlspecialchars($proposal['project_code'] . ' - ' . $proposal['project_name']); ?></p>
						</div>

						<div class="bg-gradient-to-r from-purple-50 to-purple-100 border-l-4 border-purple-500 rounded-lg p-4">
							<span class="text-xs text-purple-700 uppercase font-semibold">Phase / Milestone</span>
							<p id="preview-phase" class="text-sm font-medium <?php echo (isset($proposal['target_phase']) && !empty($proposal['target_phase'])) ? 'text-gray-900' : 'text-gray-400 italic'; ?> mt-1"><?php echo (isset($proposal['target_phase']) && !empty($proposal['target_phase'])) ? htmlspecialchars($proposal['target_phase']) : 'No phase specified'; ?></p>
						</div>

						<div class="bg-gradient-to-r from-green-50 to-green-100 border-l-4 border-green-500 rounded-lg p-4">
							<span class="text-xs text-green-700 uppercase font-semibold">Timeline</span>
							<p id="preview-timeline" class="text-sm font-medium <?php echo (isset($proposal['phase_start_date']) && isset($proposal['phase_end_date']) && !empty($proposal['phase_start_date']) && !empty($proposal['phase_end_date'])) ? 'text-gray-900' : 'text-gray-400 italic'; ?> mt-1">
								<?php 
								if (isset($proposal['phase_start_date']) && isset($proposal['phase_end_date']) && !empty($proposal['phase_start_date']) && !empty($proposal['phase_end_date'])) {
									$start = date('M d, Y', strtotime($proposal['phase_start_date']));
									$end = date('M d, Y', strtotime($proposal['phase_end_date']));
									echo $start . ' - ' . $end;
								} else {
									echo 'No timeline set';
								}
								?>
							</p>
						</div>

						<div class="bg-gradient-to-r from-amber-50 to-orange-100 border-l-4 border-[#e9922c] rounded-lg p-4">
							<span class="text-xs text-orange-700 uppercase font-semibold">Proposal Title</span>
							<p id="preview-title" class="text-sm font-medium text-gray-900 mt-1"><?php echo htmlspecialchars($proposal['title']); ?></p>
						</div>

						<div id="preview-scope-container" class="bg-gray-50 border border-gray-200 rounded-lg p-4" style="display: <?php echo (isset($proposal['scope_description']) && !empty($proposal['scope_description'])) ? 'block' : 'none'; ?>;">
							<span class="text-xs text-gray-600 uppercase font-semibold">Scope Description</span>
							<p id="preview-scope" class="text-sm text-gray-700 mt-1"><?php echo isset($proposal['scope_description']) ? htmlspecialchars($proposal['scope_description']) : ''; ?></p>
						</div>

						<div class="bg-gray-50 border border-gray-200 rounded-lg p-4">
							<span class="text-xs text-gray-600 uppercase font-semibold">Status</span>
							<p id="preview-status" class="text-sm font-medium mt-1">
								<?php
									$statusColors = [
										'DRAFT' => 'text-gray-700',
										'PENDING' => 'text-amber-700',
										'APPROVED' => 'text-green-700',
										'REJECTED' => 'text-red-700'
									];
									$statusColor = $statusColors[$proposal['status']] ?? 'text-gray-700';
								?>
								<span class="<?php echo $statusColor; ?>"><?php echo htmlspecialchars($proposal['status']); ?></span>
							</p>
						</div>
					</div>

					<div class="mb-6">
						<h3 class="text-sm font-semibold text-gray-700 uppercase mb-3">Line Items</h3>
						
						<div id="empty-state" class="text-center py-8 text-gray-400" style="display: none;">
							<p class="text-sm">No items added yet</p>
							<p class="text-xs mt-1">Add items from the left panel</p>
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
							Save as Draft
						</button>
						<button id="update-proposal" class="bg-[#e9922c] hover:bg-[#d17f1f] text-white rounded-lg py-3 px-6 font-medium transition-all shadow-sm">
							Update Proposal
						</button>
					</div>
				</div>
			</div>
		</div>
	</main>
<script>
    // ==========================================
    // 1. STATE MANAGEMENT & INITIALIZATION
    // ==========================================
    let items = [];
    let activeTab = 'materials';
    let editingItemId = null;
    const proposalId = <?php echo $proposal_id; ?>;

    // CAPTURE SAVED DATA FROM PHP
    const savedProjectId = "<?php echo $proposal['project_id']; ?>";
    
    // --- CHANGED: Capture the ID, not the name, for reliable matching ---
    const savedPhaseId = "<?php echo $proposal['phase_id'] ?? ''; ?>";

    // Load existing line items
    const existingItems = <?php echo json_encode($line_items); ?>;
    if (existingItems) {
        existingItems.forEach(item => {
            const categoryMap = {
                'MATERIAL': 'materials',
                'LABOR': 'labor',
                'EQUIPMENT': 'equipment'
            };
            items.push({
                id: Date.now() + Math.random(),
                category: categoryMap[item.category] || item.category.toLowerCase(),
                name: item.item_name,
                quantity: parseFloat(item.quantity),
                unitCost: parseFloat(item.unit_cost),
                subtotal: parseFloat(item.subtotal)
            });
        });
    }

    // ==========================================
    // 2. PHASE AUTOMATION LOGIC
    // ==========================================

    // Function: Load Phases from API
   // Function: Load Phases from API
    function loadPhases(projectId, preSelectId = null) {
        const phaseSelect = document.getElementById('targetPhase');
        phaseSelect.innerHTML = '<option value="">Loading...</option>';
        phaseSelect.disabled = true;

        if (!projectId) {
            phaseSelect.innerHTML = '<option value="">-- Select a Project First --</option>';
            phaseSelect.disabled = false;
            return;
        }

        fetch(`get_project_phases.php?project_id=${projectId}`) 
            .then(response => {
                if (!response.ok) throw new Error("API Not Found (404)");
                return response.json();
            })
            .then(data => {
                phaseSelect.innerHTML = '<option value="">-- Select Target Phase --</option>';
                
                if (data.success && data.phases.length > 0) {
                    data.phases.forEach(phase => {
                        const option = document.createElement('option');
                        
                        option.value = phase.id; 
                        option.textContent = phase.name;
                        
                        // Store metadata for auto-fill
                        option.dataset.start = phase.start_date || '';
                        option.dataset.end = phase.end_date || '';
                        option.dataset.duration = phase.duration;
                        
                        // CHECK FOR SAVED ID
                        // We use loose equality (==) in case types differ (string vs int)
                        if (preSelectId && phase.id == preSelectId) {
                            option.selected = true;
                            
                            // 1. Fill dates
                            autoFillPhaseDetails(option);
                            
                            // 2. FIX: Force update the Preview Card Text immediately
                            const previewPhaseEl = document.getElementById('preview-phase');
                            if(previewPhaseEl) {
                                previewPhaseEl.textContent = phase.name;
                                // Remove gray/italic styling if present
                                previewPhaseEl.classList.remove('text-gray-400', 'italic');
                                previewPhaseEl.classList.add('text-gray-900');
                            }
                        }
                        
                        phaseSelect.appendChild(option);
                    });
                } else {
                    phaseSelect.innerHTML = '<option value="">No phases found</option>';
                }
                phaseSelect.disabled = false;
            })
            .catch(error => {
                console.error('Error:', error);
                phaseSelect.innerHTML = '<option value="">Error loading phases</option>';
                phaseSelect.disabled = false;
            });
    }

    // Function: Auto-Fill Dates & Title
    function autoFillPhaseDetails(selectedOption) {
        if (!selectedOption.value) return;

        const startDate = selectedOption.dataset.start;
        const endDate = selectedOption.dataset.end;
        // const phaseName = selectedOption.textContent; // Use textContent if you need name

        // Auto-fill dates if available
        if(startDate) document.getElementById('phaseStartDate').value = startDate;
        if(endDate) document.getElementById('phaseEndDate').value = endDate;

        // Trigger visual updates
        document.getElementById('proposalTitle').dispatchEvent(new Event('input'));
        updateTimeline(); 
    }

    // ==========================================
    // 3. CORE FUNCTIONS
    // ==========================================

    // Function: Update Timeline Preview
    function updateTimeline() {
        const startDate = document.getElementById('phaseStartDate').value;
        const endDate = document.getElementById('phaseEndDate').value;
        const previewTimeline = document.getElementById('preview-timeline');
        
        if (startDate && endDate) {
            const start = new Date(startDate);
            const end = new Date(endDate);
            
            const options = { month: 'short', day: '2-digit', year: 'numeric' };
            const formattedStart = start.toLocaleDateString('en-US', options);
            const formattedEnd = end.toLocaleDateString('en-US', options);
            
            previewTimeline.textContent = `${formattedStart} - ${formattedEnd}`;
            previewTimeline.className = 'text-sm font-medium text-gray-900 mt-1';
        } else {
            previewTimeline.textContent = 'No timeline set';
            previewTimeline.className = 'text-sm font-medium text-gray-400 mt-1 italic';
        }
    }

    function formatPeso(amount) {
        return amount.toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ',');
    }

    function updateGrandTotal() {
        const total = items.reduce((sum, item) => sum + item.subtotal, 0);
        document.getElementById('grand-total').textContent = '₱' + formatPeso(total);
    }

    // ==========================================
    // 4. EVENT LISTENERS
    // ==========================================

    // INITIAL LOAD
    renderItems();
    updateGrandTotal();
    
    // --- CHANGED: Pass the ID to loadPhases
    if (savedProjectId) {
        loadPhases(savedProjectId, savedPhaseId);
    }

    // Project Changed
    document.getElementById('project').addEventListener('change', function() {
        const selectedOption = this.options[this.selectedIndex];
        const projectId = selectedOption.getAttribute('data-id');
        
        // Reset fields
        document.getElementById('phaseStartDate').value = '';
        document.getElementById('phaseEndDate').value = '';
        
        loadPhases(projectId, null);
    });

    // Phase Changed
    document.getElementById('targetPhase').addEventListener('change', function() {
        const selectedOption = this.options[this.selectedIndex];
        autoFillPhaseDetails(selectedOption);
        
        // Update Preview Text immediately
        document.getElementById('preview-phase').textContent = selectedOption.textContent;
    });

    // Date Changes
    document.getElementById('phaseStartDate').addEventListener('change', updateTimeline);
    document.getElementById('phaseEndDate').addEventListener('change', updateTimeline);

    // Tab Switching
    const tabs = ['materials', 'labor', 'equipment'];
    tabs.forEach(tab => {
        document.getElementById(`tab-${tab}`).addEventListener('click', () => {
            activeTab = tab;
            tabs.forEach(t => {
                const btn = document.getElementById(`tab-${t}`);
                const panel = document.getElementById(`panel-${t}`);
                if (t === tab) {
                    btn.classList.remove('border-transparent', 'text-gray-500');
                    btn.classList.add('border-[#e9922c]', 'text-[#e9922c]');
                    panel.classList.remove('hidden');
                } else {
                    btn.classList.remove('border-[#e9922c]', 'text-[#e9922c]');
                    btn.classList.add('border-transparent', 'text-gray-500');
                    panel.classList.add('hidden');
                }
            });
        });
    });

    // Add Item Listeners
    function handleAddItem(category, nameInputId, qtyInputId, costInputId) {
        const nameInput = document.getElementById(nameInputId);
        const qtyInput = document.getElementById(qtyInputId);
        const costInput = document.getElementById(costInputId);
        
        let name = nameInput.value;
        if(nameInput.tagName === 'SELECT') name = nameInput.value; 
        
        name = name.trim();
        const quantity = parseFloat(qtyInput.value) || 0;
        const cost = parseFloat(costInput.value) || 0;

        if (name && quantity > 0 && cost > 0) {
            const subtotal = quantity * cost;
            
            if (editingItemId !== null) {
                // Update
                const index = items.findIndex(i => i.id === editingItemId);
                if (index !== -1) {
                    items[index] = { id: editingItemId, category, name, quantity, unitCost: cost, subtotal };
                }
                showToast('Item updated successfully', 'success');
                editingItemId = null;
            } else {
                // Add
                items.push({ id: Date.now(), category, name, quantity, unitCost: cost, subtotal });
                showToast('Item added successfully', 'success');
            }
            
            // Reset UI
            if(nameInput.tagName === 'INPUT') nameInput.value = '';
            else nameInput.selectedIndex = 0;
            
            qtyInput.value = '';
            costInput.value = '';
            renderItems();
            updateGrandTotal();
            updateButtonText();
        } else {
            showToast('Please fill all fields', 'warning');
        }
    }

    document.getElementById('add-material').addEventListener('click', () => 
        handleAddItem('materials', 'material-name', 'material-quantity', 'material-cost'));
        
    document.getElementById('add-labor').addEventListener('click', () => 
        handleAddItem('labor', 'labor-type', 'labor-quantity', 'labor-rate'));
        
    document.getElementById('add-equipment').addEventListener('click', () => 
        handleAddItem('equipment', 'equipment-name', 'equipment-quantity', 'equipment-rate'));

    // Render Helpers
    function renderItems() {
        const emptyState = document.getElementById('empty-state');
        const materialsSection = document.getElementById('materials-section');
        const laborSection = document.getElementById('labor-section');
        const equipmentSection = document.getElementById('equipment-section');
        const materialsList = document.getElementById('materials-list');
        const laborList = document.getElementById('labor-list');
        const equipmentList = document.getElementById('equipment-list');

        if (items.length === 0) {
            if(emptyState) emptyState.style.display = 'block';
            materialsSection.style.display = 'none';
            laborSection.style.display = 'none';
            equipmentSection.style.display = 'none';
            return;
        }

        if(emptyState) emptyState.style.display = 'none';

        const materialItems = items.filter(item => item.category === 'materials');
        const laborItems = items.filter(item => item.category === 'labor');
        const equipmentItems = items.filter(item => item.category === 'equipment');

        renderCategoryList(materialItems, materialsSection, materialsList);
        renderCategoryList(laborItems, laborSection, laborList);
        renderCategoryList(equipmentItems, equipmentSection, equipmentList);
    }

    function renderCategoryList(categoryItems, section, listContainer) {
        if (categoryItems.length > 0) {
            section.style.display = 'block';
            listContainer.innerHTML = '';
            categoryItems.forEach(item => {
                listContainer.appendChild(createItemElement(item));
            });
        } else {
            section.style.display = 'none';
        }
    }

    function createItemElement(item) {
        const div = document.createElement('div');
        div.className = 'bg-gray-50 rounded-lg p-3 border border-gray-200 cursor-pointer hover:bg-gray-100 transition-colors';
        div.innerHTML = `
            <div class="flex justify-between items-start mb-2">
                <div class="flex-1" onclick="editItem(${item.id})">
                    <p class="text-sm font-medium text-gray-900">${item.name}</p>
                    <p class="text-xs text-gray-500">${item.quantity} × ₱${formatPeso(item.unitCost)}</p>
                </div>
                <button onclick="removeItem(${item.id})" class="text-red-500 hover:text-red-700 ml-2">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="text-right">
                <span class="text-sm font-semibold text-gray-900">₱${formatPeso(item.subtotal)}</span>
            </div>
        `;
        return div;
    }

    function editItem(id) {
        const item = items.find(i => i.id === id);
        if (!item) return;
        editingItemId = id;
        
        document.getElementById(`tab-${item.category}`).click();
        
        if (item.category === 'materials') {
            document.getElementById('material-name').value = item.name;
            document.getElementById('material-quantity').value = item.quantity;
            document.getElementById('material-cost').value = item.unitCost;
        } else if (item.category === 'labor') {
            document.getElementById('labor-type').value = item.name;
            document.getElementById('labor-quantity').value = item.quantity;
            document.getElementById('labor-rate').value = item.unitCost;
        } else if (item.category === 'equipment') {
            document.getElementById('equipment-name').value = item.name;
            document.getElementById('equipment-quantity').value = item.quantity;
            document.getElementById('equipment-rate').value = item.unitCost;
        }
        updateButtonText();
    }

    function updateButtonText() {
        const action = editingItemId !== null ? 'Update' : 'Add';
        const icon = editingItemId !== null ? '<i class="fas fa-save mr-2"></i>' : '<i class="fas fa-plus mr-2"></i>';
        
        document.getElementById('add-material').innerHTML = `${icon} ${action} Material Item`;
        document.getElementById('add-labor').innerHTML = `${icon} ${action} Labor Item`;
        document.getElementById('add-equipment').innerHTML = `${icon} ${action} Equipment Item`;
    }

    function removeItem(id) {
        items = items.filter(item => item.id !== id);
        renderItems();
        updateGrandTotal();
    }

    // Live Preview
    document.getElementById('proposalTitle').addEventListener('input', function(e) {
        document.getElementById('preview-title').textContent = e.target.value || 'No title entered';
    });

    // Form Submission
    document.getElementById('save-draft').addEventListener('click', () => {
        if (validateForm()) submitProposal('DRAFT');
    });

    document.getElementById('update-proposal').addEventListener('click', () => {
        if (validateForm()) {
            const status = document.querySelector('input[name="proposalStatus"]:checked')?.value || 'PENDING';
            submitProposal(status);
        }
    });

    function validateForm() {
        if (items.length === 0) {
            showToast('Please add at least one line item', 'warning');
            return false;
        }
        if (!document.getElementById('project').value) {
            showToast('Please select a project', 'warning');
            return false;
        }
        return true;
    }

    function submitProposal(status) {
        const projectSelect = document.getElementById('project');
        const projectId = projectSelect.options[projectSelect.selectedIndex].getAttribute('data-id');
        
        // --- CHANGED: Renamed 'target_phase' to 'phase_id' and send the ID value ---
        const data = {
            proposal_id: proposalId,
            project_id: projectId,
            phase_id: document.getElementById('targetPhase').value, // This is now the ID
            title: document.getElementById('proposalTitle').value,
            phase_start_date: document.getElementById('phaseStartDate').value,
            phase_end_date: document.getElementById('phaseEndDate').value,
            scope_description: document.getElementById('scopeDescription').value,
            items: items,
            status: status,
            total_amount: items.reduce((sum, item) => sum + item.subtotal, 0)
        };

        fetch('update_proposal.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data)
        })
        .then(res => res.json())
        .then(result => {
            if (result.success) {
                showToast('Proposal updated successfully!', 'success');
                setTimeout(() => window.location.href = '../proposals.php', 1000);
            } else {
                showToast('Error: ' + result.message, 'error');
            }
        })
        .catch(err => {
            console.error(err);
            showToast('Network error occurred', 'error');
        });
    }

    window.editItem = editItem;
    window.removeItem = removeItem;
</script>
</body>
</html>