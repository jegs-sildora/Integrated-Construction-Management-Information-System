<?php
	require_once __DIR__ . '/../../../config/config.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
	<!-- Global project styles -->
	<link rel="stylesheet" href="../css/output.css">
	<link rel="stylesheet" href="../css/input.css">
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Edit Budget Proposal</title>
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
<body>
	<?php 
		include __DIR__ . '/../../../includes/sidebar.php';
		include __DIR__ . '/../connection.php';  
		include __DIR__ . '/../../../includes/header.php'; 
	?>

	<?php
		// Get proposal ID from URL
		$proposal_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
		
		if ($proposal_id <= 0) {
			die("Invalid proposal ID");
		}

		// Fetch proposal details
		$sql = "SELECT bp.*, p.project_code, p.name as project_name 
				FROM budget_proposals bp 
				LEFT JOIN projects p ON bp.project_id = p.project_id 
				WHERE bp.proposal_id = ?";
		$stmt = $conn->prepare($sql);
		$stmt->bind_param("i", $proposal_id);
		$stmt->execute();
		$result = $stmt->get_result();
		
		if ($result->num_rows === 0) {
			die("Proposal not found");
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
		$sql_projects = "SELECT project_id, project_code, name, status FROM projects ORDER BY created_at DESC";
		$result_projects = $conn->query($sql_projects);
		$projects = [];
		if ($result_projects && $result_projects->num_rows > 0) {
			while ($row = $result_projects->fetch_assoc()) {
				$projects[] = $row;
			}
		}
	?>
	
	<?php include __DIR__ . '/../../../includes/toast.php'; ?>

	<main class="ml-56 mt-20 p-6">
		<div class="max-w-7xl mx-auto">
			<!-- Back Link -->
			<a href="../proposals.php" class="inline-flex items-center text-gray-600 hover:text-gray-900 mb-6 transition-colors underline">
				<svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
					<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
				</svg>
				Back to Budget Proposal Dashboard
			</a>

			<!-- Page Header -->
			<div class="mb-8">
				<h1 class="text-3xl font-bold text-gray-900 mb-2">Edit Budget Proposal</h1>
				<p class="text-gray-600">Proposal Code: <span class="font-semibold text-[#e9922c]"><?php echo htmlspecialchars($proposal['code']); ?></span></p>
			</div>

			<!-- 2-Column Grid Layout -->
			<div class="grid grid-cols-2 gap-6">
			
				<!-- LEFT CARD -->
				<div class="bg-white rounded-xl border border-gray-200 shadow-sm p-6 h-[calc(100vh-170px)] overflow-y-auto">
					<!-- Project Selection -->
					<div class="mb-6">
						<label for="project" class="block text-sm text-gray-700 mb-2">Select Project</label>
						<select id="project" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-orange-500 outline-none">
							<option value="">-- Select a Project --</option>
							<?php foreach ($projects as $project): ?>
							<option value="<?php echo htmlspecialchars($project['project_code'] . ' - ' . $project['name']); ?>" 
								data-id="<?php echo $project['project_id']; ?>"
								<?php echo ($project['project_id'] == $proposal['project_id']) ? 'selected' : ''; ?>>
								<?php echo htmlspecialchars($project['project_code'] . ' - ' . $project['name']); ?>
							</option>
							<?php endforeach; ?>
						</select>
					</div>

					<!-- Proposal Title -->
					<div class="mb-6">
						<label for="proposalTitle" class="block text-sm text-gray-700 mb-2">Proposal Title</label>
						<input type="text" id="proposalTitle" placeholder="e.g., Q1 2025 Construction Materials Budget" 
							value="<?php echo htmlspecialchars($proposal['title']); ?>"
							class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-orange-500 outline-none">
					</div>

					<!-- Target Milestone / Phase -->
					<div class="mb-6">
						<label for="targetPhase" class="block text-sm text-gray-700 mb-2">Target Milestone / Phase</label>
						<select id="targetPhase" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-orange-500 outline-none">
							<option value="">Select Phase...</option>
							<option value="Phase 1: Mobilization" <?php echo (isset($proposal['target_phase']) && $proposal['target_phase'] == 'Phase 1: Mobilization') ? 'selected' : ''; ?>>Phase 1: Mobilization</option>
							<option value="Phase 2: Structural" <?php echo (isset($proposal['target_phase']) && $proposal['target_phase'] == 'Phase 2: Structural') ? 'selected' : ''; ?>>Phase 2: Structural</option>
							<option value="Phase 3: MEPFS" <?php echo (isset($proposal['target_phase']) && $proposal['target_phase'] == 'Phase 3: MEPFS') ? 'selected' : ''; ?>>Phase 3: MEPFS</option>
							<option value="Phase 4: Finishing" <?php echo (isset($proposal['target_phase']) && $proposal['target_phase'] == 'Phase 4: Finishing') ? 'selected' : ''; ?>>Phase 4: Finishing</option>
						</select>
					</div>

					<!-- Phase Duration -->
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

					<!-- Scope Description / Justification -->
					<div class="mb-6">
						<label for="scopeDescription" class="block text-sm text-gray-700 mb-2">Scope Description / Justification</label>
						<textarea id="scopeDescription" rows="3" placeholder="Describe what this budget will achieve (e.g., 'Covers all excavation, rebar installation, and concrete pouring for the basement level')" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-orange-500 outline-none resize-none"><?php echo isset($proposal['scope_description']) ? htmlspecialchars($proposal['scope_description']) : ''; ?></textarea>
					</div>

					<!-- Status -->
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

					<!-- Tab Navigation -->
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

					<!-- Tab Content Panels -->
					<!-- Materials Tab -->
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

					<!-- Labor Tab -->
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

					<!-- Equipment Tab -->
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

				<!-- RIGHT CARD -->
				<div class="bg-white rounded-xl border border-gray-200 shadow-sm p-6 h-[calc(100vh-170px)] overflow-y-auto">
					<!-- Receipt Header -->
					<div class="flex items-center mb-6">
						<div class="w-12 h-12 bg-[#e9922c] rounded-lg flex items-center justify-center text-white text-2xl font-bold mr-4">
							I
						</div>
						<div>
							<h2 class="text-xl font-bold text-gray-900">ICMIS</h2>
							<p class="text-sm text-gray-500">PROPOSAL SUMMARY</p>
						</div>
					</div>

					<!-- Current Date -->
					<div class="text-sm text-gray-600 mb-6">
						<?php echo date('F j, Y'); ?>
					</div>

					<!-- Project Information Cards -->
					<div class="mb-6 space-y-3">
						<!-- Project Card -->
						<div class="bg-gradient-to-r from-blue-50 to-blue-100 border-l-4 border-blue-500 rounded-lg p-4">
							<span class="text-xs text-blue-700 uppercase font-semibold">Project</span>
							<p id="preview-project" class="text-sm font-medium text-gray-900 mt-1"><?php echo htmlspecialchars($proposal['project_code'] . ' - ' . $proposal['project_name']); ?></p>
						</div>

						<!-- Phase / Milestone Card -->
						<div class="bg-gradient-to-r from-purple-50 to-purple-100 border-l-4 border-purple-500 rounded-lg p-4">
							<span class="text-xs text-purple-700 uppercase font-semibold">Phase / Milestone</span>
							<p id="preview-phase" class="text-sm font-medium <?php echo (isset($proposal['target_phase']) && !empty($proposal['target_phase'])) ? 'text-gray-900' : 'text-gray-400 italic'; ?> mt-1"><?php echo (isset($proposal['target_phase']) && !empty($proposal['target_phase'])) ? htmlspecialchars($proposal['target_phase']) : 'No phase specified'; ?></p>
						</div>

						<!-- Timeline Card -->
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

						<!-- Proposal Title Card -->
						<div class="bg-gradient-to-r from-amber-50 to-orange-100 border-l-4 border-[#e9922c] rounded-lg p-4">
							<span class="text-xs text-orange-700 uppercase font-semibold">Proposal Title</span>
							<p id="preview-title" class="text-sm font-medium text-gray-900 mt-1"><?php echo htmlspecialchars($proposal['title']); ?></p>
						</div>

						<!-- Scope Description Card (conditional) -->
						<div id="preview-scope-container" class="bg-gray-50 border border-gray-200 rounded-lg p-4" style="display: <?php echo (isset($proposal['scope_description']) && !empty($proposal['scope_description'])) ? 'block' : 'none'; ?>;">
							<span class="text-xs text-gray-600 uppercase font-semibold">Scope Description</span>
							<p id="preview-scope" class="text-sm text-gray-700 mt-1"><?php echo isset($proposal['scope_description']) ? htmlspecialchars($proposal['scope_description']) : ''; ?></p>
						</div>

						<!-- Status Card -->
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

					<!-- Line Items Section -->
					<div class="mb-6">
						<h3 class="text-sm font-semibold text-gray-700 uppercase mb-3">Line Items</h3>
						
						<!-- Empty State -->
						<div id="empty-state" class="text-center py-8 text-gray-400" style="display: none;">
							<p class="text-sm">No items added yet</p>
							<p class="text-xs mt-1">Add items from the left panel</p>
						</div>

						<!-- Materials Section -->
						<div id="materials-section" class="mb-4" style="display: none;">
							<div class="flex items-center mb-2">
								<span class="bg-purple-100 text-purple-700 text-xs px-2 py-1 rounded font-semibold">MATERIALS</span>
							</div>
							<div id="materials-list" class="space-y-2 max-h-48 overflow-y-auto"></div>
						</div>

						<!-- Labor Section -->
						<div id="labor-section" class="mb-4" style="display: none;">
							<div class="flex items-center mb-2">
								<span class="bg-amber-100 text-amber-700 text-xs px-2 py-1 rounded font-semibold">LABOR</span>
							</div>
							<div id="labor-list" class="space-y-2 max-h-48 overflow-y-auto"></div>
						</div>

						<!-- Equipment Section -->
						<div id="equipment-section" class="mb-4" style="display: none;">
							<div class="flex items-center mb-2">
								<span class="bg-green-100 text-green-700 text-xs px-2 py-1 rounded font-semibold">EQUIPMENT</span>
							</div>
							<div id="equipment-list" class="space-y-2 max-h-48 overflow-y-auto"></div>
						</div>
					</div>

					<!-- Grand Total -->
					<div class="border-t-2 border-gray-200 pt-4 mb-6">
						<div class="flex justify-between items-center">
							<span class="text-lg font-semibold text-gray-700">GRAND TOTAL</span>
							<span id="grand-total" class="text-3xl font-bold text-[#e9922c]">₱0.00</span>
						</div>
					</div>

					<!-- Action Buttons -->
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
		// State Management
		let items = [];
		let activeTab = 'materials';
		let editingItemId = null; // Track which item is being edited
		const proposalId = <?php echo $proposal_id; ?>;

		// Load existing line items
		const existingItems = <?php echo json_encode($line_items); ?>;
		existingItems.forEach(item => {
			const categoryMap = {
				'MATERIAL': 'materials',
				'LABOR': 'labor',
				'EQUIPMENT': 'equipment'
			};
			items.push({
				id: Date.now() + Math.random(), // Generate unique ID
				category: categoryMap[item.category] || item.category.toLowerCase(),
				name: item.item_name,
				quantity: parseFloat(item.quantity),
				unitCost: parseFloat(item.unit_cost),
				subtotal: parseFloat(item.subtotal)
			});
		});

		// Initial render
		renderItems();
		updateGrandTotal();

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
			const name = document.getElementById('material-name').value.trim();
			const quantity = parseFloat(document.getElementById('material-quantity').value) || 0;
			const cost = parseFloat(document.getElementById('material-cost').value) || 0;

			if (name && quantity > 0 && cost > 0) {
				if (editingItemId !== null) {
					// Update existing item
					updateItem(editingItemId, 'materials', name, quantity, cost);
					showToast('Material item updated successfully', 'success');
				} else {
					// Add new item
					addItem('materials', name, quantity, cost);
					showToast('Material item added successfully', 'success');
				}
				// Clear inputs and reset editing state
				document.getElementById('material-name').value = '';
				document.getElementById('material-quantity').value = '';
				document.getElementById('material-cost').value = '';
				editingItemId = null;
				updateButtonText();
			} else {
				showToast('Please fill in all fields with valid values', 'warning');
			}
		});

		document.getElementById('add-labor').addEventListener('click', () => {
			const laborTypeSelect = document.getElementById('labor-type');
			const name = laborTypeSelect.value.trim();
			const quantity = parseFloat(document.getElementById('labor-quantity').value) || 0;
			const cost = parseFloat(document.getElementById('labor-rate').value) || 0;

			if (name && quantity > 0 && cost > 0) {
				if (editingItemId !== null) {
					// Update existing item
					updateItem(editingItemId, 'labor', name, quantity, cost);
					showToast('Labor item updated successfully', 'success');
				} else {
					// Add new item
					addItem('labor', name, quantity, cost);
					showToast('Labor item added successfully', 'success');
				}
				// Clear inputs and reset editing state
				laborTypeSelect.selectedIndex = 0;
				document.getElementById('labor-quantity').value = '';
				document.getElementById('labor-rate').value = '';
				editingItemId = null;
				updateButtonText();
			} else {
				showToast('Please fill in all fields with valid values', 'warning');
			}
		});

		document.getElementById('add-equipment').addEventListener('click', () => {
			const name = document.getElementById('equipment-name').value.trim();
			const quantity = parseFloat(document.getElementById('equipment-quantity').value) || 0;
			const cost = parseFloat(document.getElementById('equipment-rate').value) || 0;

			if (name && quantity > 0 && cost > 0) {
				if (editingItemId !== null) {
					// Update existing item
					updateItem(editingItemId, 'equipment', name, quantity, cost);
					showToast('Equipment item updated successfully', 'success');
				} else {
					// Add new item
					addItem('equipment', name, quantity, cost);
					showToast('Equipment item added successfully', 'success');
				}
				// Clear inputs and reset editing state
				document.getElementById('equipment-name').value = '';
				document.getElementById('equipment-quantity').value = '';
				document.getElementById('equipment-rate').value = '';
				editingItemId = null;
				updateButtonText();
			} else {
				showToast('Please fill in all fields with valid values', 'warning');
			}
		});

		function addItem(category, name, quantity, unitCost) {
			const subtotal = quantity * unitCost;
			const item = {
				id: Date.now() + Math.random(),
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

		function updateItem(id, category, name, quantity, unitCost) {
			const subtotal = quantity * unitCost;
			const itemIndex = items.findIndex(item => item.id === id);
			
			if (itemIndex !== -1) {
				items[itemIndex] = {
					id: id,
					category: category,
					name: name,
					quantity: quantity,
					unitCost: unitCost,
					subtotal: subtotal
				};
				renderItems();
				updateGrandTotal();
			}
		}

		function editItem(id) {
			const item = items.find(i => i.id === id);
			if (!item) return;

			// Set editing state
			editingItemId = id;
			
			// Switch to the appropriate tab
			switchTab(item.category);
			
			// Populate form fields based on category
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
			
			// Update button text
			updateButtonText();
		}

		function updateButtonText() {
			const materialBtn = document.getElementById('add-material');
			const laborBtn = document.getElementById('add-labor');
			const equipmentBtn = document.getElementById('add-equipment');
			
			if (editingItemId !== null) {
				materialBtn.innerHTML = `
					<svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
						<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
					</svg>
					Update Material Item
				`;
				laborBtn.innerHTML = `
					<svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
						<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
					</svg>
					Update Labor Item
				`;
				equipmentBtn.innerHTML = `
					<svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
						<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
					</svg>
					Update Equipment Item
				`;
			} else {
				materialBtn.innerHTML = `
					<svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
						<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
					</svg>
					Add Material Item
				`;
				laborBtn.innerHTML = `
					<svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
						<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
					</svg>
					Add Labor Item
				`;
				equipmentBtn.innerHTML = `
					<svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
						<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
					</svg>
					Add Equipment Item
				`;
			}
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
				materialItems.forEach((item) => {
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
			div.className = 'bg-gray-50 rounded-lg p-3 border border-gray-200 cursor-pointer hover:bg-gray-100 transition-colors';
			
			const flexContainer = document.createElement('div');
			flexContainer.className = 'flex justify-between items-start mb-2';
			
			const leftContent = document.createElement('div');
			leftContent.className = 'flex-1';
			leftContent.onclick = () => editItem(item.id);
			
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

		// Update status preview when radio buttons change
		document.querySelectorAll('input[name="proposalStatus"]').forEach(radio => {
			radio.addEventListener('change', (e) => {
				const previewStatus = document.getElementById('preview-status');
				const status = e.target.value;
				const statusColors = {
					'DRAFT': 'text-gray-700',
					'PENDING': 'text-amber-700',
					'APPROVED': 'text-green-700',
					'REJECTED': 'text-red-700'
				};
				const colorClass = statusColors[status] || 'text-gray-700';
				previewStatus.innerHTML = `<span class="${colorClass}">${status}</span>`;
			});
		});

		// Form Submission
		document.getElementById('save-draft').addEventListener('click', () => {
			if (validateForm()) {
				updateProposal('DRAFT');
			}
		});

		document.getElementById('update-proposal').addEventListener('click', () => {
			if (validateForm()) {
				const status = document.querySelector('input[name="proposalStatus"]:checked').value;
				updateProposal(status);
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

		function updateProposal(status) {
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
				proposal_id: proposalId,
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
			document.getElementById('update-proposal').disabled = true;

			console.log('Updating proposal:', data);

			fetch('http://localhost/icmis/modules/budget/budget_proposal/update_proposal.php', {
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
					showToast(result.message, 'success', true);
					// Redirect to proposals page after a short delay
					setTimeout(() => {
						window.location.href = '../proposals.php';
					}, 300);
					} else {
						showToast('Error: ' + result.message, 'error');
						// Re-enable buttons
						document.getElementById('save-draft').disabled = false;
						document.getElementById('update-proposal').disabled = false;
					}
				} catch (e) {
					console.error('JSON parse error:', e);
					showToast('Server returned invalid response', 'error');
					document.getElementById('save-draft').disabled = false;
					document.getElementById('update-proposal').disabled = false;
				}
			})
			.catch(error => {
				console.error('Fetch error:', error);
				showToast('Error updating proposal: ' + error.message, 'error');
				// Re-enable buttons
				document.getElementById('save-draft').disabled = false;
				document.getElementById('update-proposal').disabled = false;
			});
		}

		// Make functions available globally
		window.removeItem = removeItem;
		window.editItem = editItem;
	</script>
</body>
</html>
