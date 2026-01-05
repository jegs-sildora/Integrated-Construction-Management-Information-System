<?php
// ============================================================
// ALL PHP LOGIC MUST BE BEFORE ANY HTML OUTPUT
// ============================================================

// Connection & Context - using centralized config
include __DIR__ . '/project_context.php';
$conn = getBudgetConnection();

// Get project_id and phase from global context
$selected_project_id = getProjectContext($conn);
$selected_phase = getPhaseContext();

// Fetch projects from main database
$sql_projects = "SELECT project_id, project_code, project_name FROM icmis_projects ORDER BY project_id DESC";
$result_projects = $conn->query($sql_projects);
$projects = [];
if ($result_projects && $result_projects->num_rows > 0) {
  while ($row = $result_projects->fetch_assoc()) {
    $projects[] = $row;
    // Set first project as default if none selected
    if ($selected_project_id == 0) {
      $selected_project_id = $row['project_id'];
      $_SESSION['selected_project_id'] = $selected_project_id;
    }
  }
}

// Get selected project details
$project_name = 'No Project Selected';
$project_code = '';
if ($selected_project_id > 0) {
  $sql_project = "SELECT project_code, project_name FROM icmis_projects WHERE project_id = ?";
  $stmt = $conn->prepare($sql_project);
  $stmt->bind_param("i", $selected_project_id);
  $stmt->execute();
  $result = $stmt->get_result();
  if ($result && $result->num_rows > 0) {
    $project = $result->fetch_assoc();
    $project_name = $project['project_name'];
    $project_code = $project['project_code'];
  }
  $stmt->close();
}

// Fetch active phases from approved budget proposals (using phase_id with JOIN)
$phases = [];
if ($selected_project_id > 0) {
  $sql_phases = "SELECT DISTINCT pp.phase_id, pp.phase_name 
                 FROM budget_proposals bp
                 JOIN icmis_project_phases pp ON bp.phase_id = pp.phase_id
                 WHERE bp.project_id = ? AND bp.status = 'APPROVED'
                 ORDER BY pp.phase_name";
  $stmt_phases = $conn->prepare($sql_phases);
  $stmt_phases->bind_param("i", $selected_project_id);
  $stmt_phases->execute();
  $result_phases = $stmt_phases->get_result();
  if ($result_phases && $result_phases->num_rows > 0) {
    while ($row = $result_phases->fetch_assoc()) {
      $phases[] = ['phase_id' => $row['phase_id'], 'phase_name' => $row['phase_name']];
    }
  }
  $stmt_phases->close();
}

// Header variables
$pageTitle = "Expense Tracker";
$pageSubTitle = "Add New Expense";
$pageSection = "Budget & Cost Control";
$userName = $_SESSION['user_name'] ?? "Admin";
$userRole = $_SESSION['user_role'] ?? "Financial Manager";
$notificationCount = 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <?php include __DIR__ . '/../../includes/head_assetsv2.php'; ?>
  <title>Add New Expense - ICMIS</title>
  <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
  <script src="https://unpkg.com/lucide@latest"></script>
  <style>
    * { font-family: 'Inter', sans-serif; }
  </style>
</head>
<body class="bg-gray-50">
  <?php include __DIR__ . '/../../includes/sidebar.php'; ?>
  <?php include __DIR__ . '/../../includes/toast.php'; ?>
  <?php include __DIR__ . '/../../includes/header.php'; ?>

  <!-- Toast included globally via header.php -->

  <main class="ml-56 mt-20 p-6">
    <div class="max-w-7xl mx-auto">
      <!-- Back Link -->
      <a href="expenses.php?project_id=<?php echo $selected_project_id; ?>&phase=<?php echo urlencode($selected_phase ?: 'All Phases'); ?>" class="inline-flex items-center text-gray-600 hover:text-gray-900 mb-6 transition-colors underline">
        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
        </svg>
        Back to Expenses Dashboard
      </a>

      <!-- Page Header -->
      <div class="mb-8 text-center">
        <h1 class="text-2xl font-bold text-gray-900 mb-2">Add New Expense</h1>
        <p class="text-sm text-gray-600">Fill in the details below to record a new project expense</p>
      </div>

      <!-- 2-Column Grid Layout -->
      <div class="grid grid-cols-2 gap-6">
        
        <!-- LEFT CARD - Form -->
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-6 h-[calc(100vh-200px)] overflow-y-auto">
          
          <form id="expense-form">
            <!-- Project Selection -->
            <div class="mb-6">
              <label for="project" class="block text-sm text-gray-700 mb-2">Select Project <span class="text-red-500">*</span></label>
              <select id="project" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-orange-500 outline-none">
                <option value="">Select a project</option>
                <?php foreach ($projects as $proj): ?>
                  <option value="<?php echo $proj['project_id']; ?>" 
                          data-code="<?php echo htmlspecialchars($proj['project_code']); ?>" 
                          data-name="<?php echo htmlspecialchars($proj['project_name']); ?>" <?php echo ($proj['project_id'] == $selected_project_id) ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($proj['project_name']); ?> </option>
                <?php endforeach; ?>
              </select>
            </div>

            <!-- Expense Date -->
            <div class="mb-6">
              <label for="expense-date" class="block text-sm text-gray-700 mb-2">Expense Date <span class="text-red-500">*</span></label>
              <input type="date" id="expense-date" value="<?php echo date('Y-m-d'); ?>" max="<?php echo date('Y-m-d'); ?>" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-orange-500 outline-none">
              <p class="mt-1 text-xs text-gray-500">Date when expense was incurred</p>
            </div>

            <!-- Phase -->
            <div class="mb-6">
              <label for="phase" class="block text-sm text-gray-700 mb-2">Phase <span class="text-red-500">*</span> <span class="text-xs text-gray-500 font-normal">(Required for smart suggestions)</span></label>
              <select id="phase" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-orange-500 outline-none">
                <option value="">Select project phase</option>
                <?php foreach ($phases as $phase): ?>
                  <option value="<?php echo htmlspecialchars($phase['phase_id']); ?>" <?php echo ($selected_phase == $phase['phase_id']) ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($phase['phase_name']); ?>
                  </option>
                <?php endforeach; ?>
              </select>
              <p class="mt-1 text-xs text-gray-500">
                <?php if (empty($phases)): ?>
                  ⚠️ No active phases found. Please create an approved budget proposal first.
                <?php else: ?>
                  Select phase first to enable smart item suggestions below
                <?php endif; ?>
              </p>
            </div>

            <!-- Category -->
            <div class="mb-6">
              <label for="category" class="block text-sm text-gray-700 mb-2">Category <span class="text-red-500">*</span></label>
              <select id="category" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-orange-500 outline-none">
                <option value="">Select a category</option>
                <option value="MATERIALS">Materials (Cement, Wood, Steel, etc.)</option>
                <option value="EQUIPMENT">Equipment (Rentals, Tools, Machinery)</option>
                <option value="PROFESSIONAL_FEES">Professional Fees (Engineers, Consultants)</option>
                <option value="SUBCONTRACTOR">Subcontractor Services</option>
                <option value="OVERHEAD">Overhead & Other (Permits, Fuel, Utilities)</option>
              </select>
              <p class="mt-1 text-xs text-gray-500">💡 <strong>Note:</strong> Labor/Wages are managed in the Payroll module</p>
            </div>

            <!-- Description -->
            <div class="mb-6">
              <label for="description" class="block text-sm text-gray-700 mb-2">Item / Description <span class="text-red-500">*</span></label>
              <div class="relative">
                <textarea id="description" rows="3" maxlength="500" placeholder="Enter detailed description of the expense..." class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-orange-500 outline-none resize-none"></textarea>
                <div class="absolute bottom-2 right-3 text-xs text-gray-400">
                  <span id="char-count">0</span>/500
                </div>
                <!-- Autocomplete Suggestions Dropdown -->
                <div id="description-suggestions" class="hidden absolute z-50 w-full mt-1 bg-white border border-gray-300 rounded-lg shadow-lg max-h-60 overflow-y-auto">
                  <!-- Suggestions will be populated here -->
                </div>
              </div>
              <p class="mt-1 text-xs text-gray-500">💡 Start typing to see planned items from budget proposals</p>
            </div>

            <!-- Quantity and Unit Cost -->
            <div class="mb-6">
              <div class="grid grid-cols-2 gap-4">
                <div>
                  <label for="quantity" class="block text-sm text-gray-700 mb-2">Quantity</label>
                  <input type="number" id="quantity" placeholder="0" min="0" step="1" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-orange-500 outline-none">
                </div>
                <div>
                  <label for="unit-cost" class="block text-sm text-gray-700 mb-2">Unit Cost (₱)</label>
                  <input type="number" id="unit-cost" placeholder="0.00" min="0" step="0.01" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-orange-500 outline-none">
                </div>
              </div>
            </div>

            <!-- Calculated Subtotal Display -->
            <div class="mb-6 bg-orange-50 border border-orange-200 rounded-lg p-4">
              <div class="flex items-center justify-between">
                <span class="text-sm text-gray-700 font-medium">Subtotal:</span>
                <span id="item-subtotal" class="text-xl font-bold text-[#e9922c]">₱0.00</span>
              </div>
              <p class="text-xs text-gray-500 mt-2">Quantity × Unit Cost</p>
            </div>
            
            <!-- Supplier/Vendor Name -->
            <div class="mb-6">
              <label for="supplier-name" class="block text-sm text-gray-700 mb-2">Supplier/Vendor Name <span class="text-red-500">*</span></label>
              <input type="text" id="supplier-name" placeholder="Enter supplier or vendor name" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-orange-500 outline-none">
            </div>

            <!-- Receipt Upload -->
            <div class="mb-6">
              <label class="block text-sm text-gray-700 mb-2">Receipt Upload <span class="text-gray-400">(Optional)</span></label>
              
              <!-- Upload Area -->
              <div id="upload-area" class="border-2 border-dashed border-gray-300 rounded-lg p-8 text-center bg-gray-50 hover:border-[#e9922c] hover:bg-orange-50 transition-all duration-200 cursor-pointer">
                <input type="file" id="receipt-file" accept=".pdf,.png,.jpg,.jpeg" class="hidden">
                <div class="flex flex-col items-center">
                  <svg class="w-12 h-12 text-gray-400 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>
                  </svg>
                  <p class="text-sm text-gray-600 mb-1">Click to upload or drag and drop</p>
                  <p class="text-xs text-gray-500">PDF, PNG, JPG up to 5MB</p>
                </div>
              </div>

              <!-- File Preview -->
              <div id="file-preview" class="hidden border border-gray-300 rounded-lg p-4 bg-white mt-2">
                <div class="flex items-center justify-between">
                  <div class="flex items-center gap-3">
                    <div class="shrink-0 w-10 h-10 bg-[#e9922c] bg-opacity-10 rounded-lg flex items-center justify-center">
                      <svg class="w-5 h-5 text-[#e9922c]" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4z" clip-rule="evenodd"/>
                      </svg>
                    </div>
                    <div class="flex-1 min-w-0">
                      <p id="file-name" class="text-sm font-medium text-gray-900 truncate"></p>
                      <p id="file-size" class="text-xs text-gray-500"></p>
                    </div>
                  </div>
                  <button type="button" id="remove-file-btn" class="shrink-0 text-gray-400 hover:text-red-600 transition-colors duration-150">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                  </button>
                </div>
              </div>
            </div>

            <!-- Approval Status -->
            <div class="mb-6">
              <label class="block text-sm text-gray-700 mb-3">Approval Status <span class="text-red-500">*</span></label>
              <div class="flex items-center gap-6">
                <label class="flex items-center cursor-pointer">
                  <input type="radio" id="status-pending" name="status" value="PENDING" checked class="w-4 h-4 text-[#e9922c] border-gray-300">
                  <span class="ml-2 text-sm text-gray-700">Pending</span>
                </label>
                <label class="flex items-center cursor-pointer">
                  <input type="radio" id="status-approved" name="status" value="APPROVED" class="w-4 h-4 text-[#e9922c] border-gray-300">
                  <span class="ml-2 text-sm text-gray-700">Approved</span>
                </label>
              </div>
            </div>

            <!-- Additional Notes -->
            <div class="mb-6">
              <label for="notes" class="block text-sm text-gray-700 mb-2">Additional Notes <span class="text-gray-400">(Optional)</span></label>
              <textarea id="notes" rows="3" placeholder="Any additional information..." class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-orange-500 outline-none resize-none"></textarea>
            </div>

            <!-- Add Item Button -->
            <div class="mb-6">
              <button type="button" id="add-line-item-btn" class="w-full bg-[#e9922c] hover:bg-[#d17f1f] text-white rounded-lg py-3 px-6 transition-all shadow-sm flex items-center justify-center font-medium">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                Add Expense Item
              </button>
            </div>
          </form>

        </div>

        <!-- RIGHT CARD - Preview -->
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-6 h-[calc(100vh-200px)] overflow-y-auto">
          
          <!-- Receipt Header -->
          <div class="flex items-center mb-6">
              <div class="w-12 h-12 bg-white rounded-lg flex items-center justify-center shadow-md p-1.5 mr-4 border border-gray-100">
                  <img src="/icmis/assets/images/nobg_logo.png" alt="ICMIS Logo" class="w-full h-full object-contain">
              </div>
              <div>
                  <h2 class="text-xl font-bold text-gray-900">ICMIS</h2>
                  <p class="text-sm text-gray-500">EXPENSE SUMMARY</p>
              </div>
          </div>

          <!-- Current Date -->
          <div class="text-sm text-gray-600 mb-6">
            <span class="font-medium">Date:</span> <span id="preview-date"><?php echo date('F d, Y'); ?></span>
          </div>

          <!-- Project Information -->
          <div class="mb-6 space-y-3">
            <div class="bg-gray-50 rounded-lg p-4 border border-gray-200">
              <p class="text-xs text-gray-500 uppercase tracking-wide mb-1">Project</p>
              <p id="preview-project" class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($project_name); ?></p>
            </div>

            <div class="bg-gray-50 rounded-lg p-4 border border-gray-200">
              <p class="text-xs text-gray-500 uppercase tracking-wide mb-1">Phase</p>
              <p id="preview-phase" class="text-sm font-medium text-gray-400 italic">No phase selected</p>
            </div>

            <div class="bg-gray-50 rounded-lg p-4 border border-gray-200">
              <p class="text-xs text-gray-500 uppercase tracking-wide mb-1">Category</p>
              <p id="preview-category" class="text-sm font-medium text-gray-400 italic">No category selected</p>
            </div>
          </div>

          <!-- Supplier Info -->
          <div class="mb-6">
            <div class="bg-gray-50 rounded-lg p-4 border border-gray-200">
              <p class="text-xs text-gray-500 mb-1">Supplier</p>
              <p id="preview-supplier" class="text-sm text-gray-400 italic">No supplier entered</p>
            </div>
          </div>

          <!-- Expense Line Items -->
          <div class="mb-6">
            <h3 class="text-sm font-semibold text-gray-900 mb-3 uppercase tracking-wide">Expense Line Items</h3>
            
            <!-- Empty State -->
            <div id="empty-state" class="text-center py-8 text-gray-400 italic">
              <p class="text-sm">No items added yet. Start adding expense items from the left panel.</p>
            </div>

            <!-- Line Items List -->
            <div id="line-items-list" class="space-y-2" style="display: none;">
              <!-- Items will be rendered here -->
            </div>
          </div>

          <!-- Grand Total -->
          <div class="border-t-2 border-gray-200 pt-4 mb-6">
            <div class="flex items-center justify-between mb-2">
              <span class="text-sm text-gray-600">Status:</span>
              <span id="preview-status" class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">Pending</span>
            </div>
            <div class="flex items-center justify-between">
              <span class="text-lg font-bold text-gray-900">TOTAL AMOUNT</span>
              <span id="preview-amount" class="text-2xl font-bold text-[#e9922c]">₱0.00</span>
            </div>
          </div>

          <!-- Action Buttons -->
          <div class="grid grid-cols-2 gap-4">
            <button type="button" id="cancel-btn" class="px-6 py-3 border-2 border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition-all text-sm font-medium">
              Cancel
            </button>
            <button type="submit" form="expense-form" id="submit-expense-btn" class="px-6 py-3 bg-[#e9922c] hover:bg-[#d17f1f] text-white rounded-lg transition-all shadow-sm text-sm font-medium flex items-center justify-center">
              <svg id="submit-icon" class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
              </svg>
              <svg id="loading-icon" class="hidden w-5 h-5 mr-2 animate-spin" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
              </svg>
              <span id="submit-text">Save Expense</span>
            </button>
          </div>

        </div>

      </div>
    </div>
  </main>

  <style>
    /* Autocomplete suggestions */
    #description-suggestions {
      scrollbar-width: thin;
      scrollbar-color: #e9922c #f3f4f6;
    }
    
    #description-suggestions::-webkit-scrollbar {
      width: 8px;
    }
    
    #description-suggestions::-webkit-scrollbar-track {
      background: #f3f4f6;
      border-radius: 4px;
    }
    
    #description-suggestions::-webkit-scrollbar-thumb {
      background: #e9922c;
      border-radius: 4px;
    }
    
    #description-suggestions::-webkit-scrollbar-thumb:hover {
      background: #d17f1f;
    }

    .suggestion-item {
      transition: all 0.2s ease;
    }

    .suggestion-item:hover {
      background-color: #fff7ed !important;
    }
  </style>

  <script>
    // Initialize Lucide icons
    lucide.createIcons();

    // State Management
    let lineItems = [];

    // Form elements
    const expenseForm = document.getElementById('expense-form');
    const projectSelect = document.getElementById('project');
    const expenseDateInput = document.getElementById('expense-date');
    const phaseSelect = document.getElementById('phase');
    const categorySelect = document.getElementById('category');
    const descriptionField = document.getElementById('description');
    const quantityInput = document.getElementById('quantity');
    const unitCostInput = document.getElementById('unit-cost');
    const itemSubtotalSpan = document.getElementById('item-subtotal');
    const addLineItemBtn = document.getElementById('add-line-item-btn');
    const supplierNameInput = document.getElementById('supplier-name');
    const notesInput = document.getElementById('notes');
    const charCount = document.getElementById('char-count');

    // File upload
    const uploadArea = document.getElementById('upload-area');
    const receiptFileInput = document.getElementById('receipt-file');
    const filePreview = document.getElementById('file-preview');
    const removeFileBtn = document.getElementById('remove-file-btn');
    let selectedFile = null;

    // Preview elements
    const previewDate = document.getElementById('preview-date');
    const previewProject = document.getElementById('preview-project');
    const previewPhase = document.getElementById('preview-phase');
    const previewCategory = document.getElementById('preview-category');
    const previewDescription = document.getElementById('preview-description');
    const previewSupplier = document.getElementById('preview-supplier');
    const previewStatus = document.getElementById('preview-status');
    const previewAmount = document.getElementById('preview-amount');
    const previewMaterialDetails = document.getElementById('preview-material-details');
    const previewQuantity = document.getElementById('preview-quantity');
    const previewUnitCost = document.getElementById('preview-unit-cost');

    // Buttons
    const cancelBtn = document.getElementById('cancel-btn');
    const submitBtn = document.getElementById('submit-expense-btn');
    const submitIcon = document.getElementById('submit-icon');
    const loadingIcon = document.getElementById('loading-icon');
    const submitText = document.getElementById('submit-text');

    // Autocomplete
    const suggestionsContainer = document.getElementById('description-suggestions');
    let debounceTimer;
    let selectedSuggestionIndex = -1;

    // ============================================
    // ITEM SUBTOTAL CALCULATION
    // ============================================
    function calculateItemSubtotal() {
      const quantity = parseFloat(quantityInput.value) || 0;
      const unitCost = parseFloat(unitCostInput.value) || 0;
      const subtotal = quantity * unitCost;
      
      itemSubtotalSpan.textContent = '₱' + formatPeso(subtotal);
    }

    quantityInput.addEventListener('input', calculateItemSubtotal);
    unitCostInput.addEventListener('input', calculateItemSubtotal);

    // ============================================
    // LINE ITEMS MANAGEMENT
    // ============================================
    addLineItemBtn.addEventListener('click', () => {
      const category = categorySelect.value;
      const description = descriptionField.value.trim();
      const quantity = parseFloat(quantityInput.value) || 0;
      const unitCost = parseFloat(unitCostInput.value) || 0;
      const subtotal = quantity * unitCost;

      // Validation
      if (!category) {
        showToast('Please select a category', 'warning');
        categorySelect.focus();
        return;
      }

      if (!description) {
        showToast('Please enter a description', 'warning');
        descriptionField.focus();
        return;
      }

      if (description.length < 10) {
        showToast('Description must be at least 10 characters', 'warning');
        descriptionField.focus();
        return;
      }

      if (quantity <= 0) {
        showToast('Please enter a valid quantity', 'warning');
        quantityInput.focus();
        return;
      }

      if (unitCost <= 0) {
        showToast('Please enter a valid unit cost', 'warning');
        unitCostInput.focus();
        return;
      }

      // Add item to array
      const item = {
        id: Date.now(),
        category: category,
        description: description,
        quantity: quantity,
        unitCost: unitCost,
        subtotal: subtotal
      };

      lineItems.push(item);
      renderLineItems();
      updateGrandTotal();

      // Clear inputs
      categorySelect.value = '';
      descriptionField.value = '';
      quantityInput.value = '';
      unitCostInput.value = '';
      charCount.textContent = '0';
      itemSubtotalSpan.textContent = '₱0.00';

      showToast('Expense item added successfully', 'success');
      categorySelect.focus();
    });

    function removeLineItem(id) {
      lineItems = lineItems.filter(item => item.id !== id);
      renderLineItems();
      updateGrandTotal();
      showToast('Item removed successfully', 'error');
    }

    function renderLineItems() {
      const emptyState = document.getElementById('empty-state');
      const lineItemsList = document.getElementById('line-items-list');

      if (lineItems.length === 0) {
        emptyState.style.display = 'block';
        lineItemsList.style.display = 'none';
        return;
      }

      emptyState.style.display = 'none';
      lineItemsList.style.display = 'block';
      lineItemsList.innerHTML = '';

      lineItems.forEach(item => {
        const itemElement = createLineItemElement(item);
        lineItemsList.appendChild(itemElement);
      });
    }

    function createLineItemElement(item) {
      const div = document.createElement('div');
      div.className = 'bg-gray-50 rounded-lg p-3 border border-gray-200';
      
      const categoryBadgeColors = {
        'MATERIALS': 'bg-purple-100 text-purple-800',
        'EQUIPMENT': 'bg-green-100 text-green-800',
        'PROFESSIONAL_FEES': 'bg-blue-100 text-blue-800',
        'SUBCONTRACTOR': 'bg-orange-100 text-orange-800',
        'OVERHEAD': 'bg-gray-100 text-gray-800'
      };

      div.innerHTML = `
        <div class="flex justify-between items-start mb-2">
          <div class="flex-1">
            <div class="flex items-center gap-2 mb-1">
              <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium ${categoryBadgeColors[item.category] || 'bg-gray-100 text-gray-800'}">
                ${item.category}
              </span>
            </div>
            <p class="text-sm font-medium text-gray-900">${item.description}</p>
            <p class="text-xs text-gray-500 mt-1">${item.quantity} × ₱${formatPeso(item.unitCost)}</p>
          </div>
          <button type="button" onclick="removeLineItem(${item.id})" class="text-red-500 hover:text-red-700 transition-colors ml-2">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
            </svg>
          </button>
        </div>
        <div class="text-right">
          <span class="text-sm font-semibold text-gray-900">₱${formatPeso(item.subtotal)}</span>
        </div>
      `;
      
      return div;
    }

    function updateGrandTotal() {
      const total = lineItems.reduce((sum, item) => sum + item.subtotal, 0);
      previewAmount.textContent = '₱' + formatPeso(total);
    }

    // Make removeLineItem available globally
    window.removeLineItem = removeLineItem;

    // ============================================
    // CHARACTER COUNTER & AUTOCOMPLETE
    // ============================================
    descriptionField.addEventListener('input', function() {
      const count = this.value.length;
      charCount.textContent = count;
      
      const searchText = this.value.trim();
      clearTimeout(debounceTimer);
      
      if (searchText.length >= 2) {
        const projectId = projectSelect.value;
        const phase = phaseSelect.value;
        
        if (!projectId) {
          showSuggestionMessage('⚠️ Please select a project first');
          return;
        }
        
        if (!phase) {
          showSuggestionMessage('⚠️ Please select a phase first to see planned items');
          return;
        }

        debounceTimer = setTimeout(() => {
          fetchPlannedItems(projectId, phase, searchText);
        }, 300);
      } else {
        hideSuggestions();
      }
      
      updatePreview();
    });

    // Keyboard navigation for suggestions
    descriptionField.addEventListener('keydown', function(e) {
      const suggestions = suggestionsContainer.querySelectorAll('.suggestion-item');
      
      if (suggestions.length === 0) return;
      
      if (e.key === 'ArrowDown') {
        e.preventDefault();
        selectedSuggestionIndex = Math.min(selectedSuggestionIndex + 1, suggestions.length - 1);
        updateSelectedSuggestion(suggestions);
      } else if (e.key === 'ArrowUp') {
        e.preventDefault();
        selectedSuggestionIndex = Math.max(selectedSuggestionIndex - 1, -1);
        updateSelectedSuggestion(suggestions);
      } else if (e.key === 'Enter' && selectedSuggestionIndex >= 0) {
        e.preventDefault();
        suggestions[selectedSuggestionIndex].click();
      } else if (e.key === 'Escape') {
        hideSuggestions();
      }
    });

    async function fetchPlannedItems(projectId, phase, searchText) {
      try {
        const response = await fetch(`budget_expenses/get_planned_items.php?project_id=${projectId}&phase=${encodeURIComponent(phase)}&search=${encodeURIComponent(searchText)}`);
        const result = await response.json();

        if (result.success && result.items.length > 0) {
          displaySuggestions(result.items);
        } else {
          hideSuggestions();
        }
      } catch (error) {
        console.error('Error fetching planned items:', error);
        hideSuggestions();
      }
    }

    function displaySuggestions(items) {
      selectedSuggestionIndex = -1;
      
      const html = items.map((item, index) => `
        <div class="suggestion-item px-4 py-3 hover:bg-orange-50 cursor-pointer border-b border-gray-100 last:border-b-0 transition-colors" data-index="${index}" data-item='${JSON.stringify(item)}'>
          <div class="flex items-start gap-3">
            <svg class="w-5 h-5 text-[#e9922c] flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
            </svg>
            <div class="flex-1 min-w-0">
              <p class="text-sm font-medium text-gray-900">${item.item_name}</p>
              <p class="text-xs text-gray-500 mt-1">
                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-800">
                  ${item.category}
                </span>
                <span class="ml-2">Planned: ${item.quantity.toFixed(0)} @ ₱${item.unit_cost.toLocaleString('en-PH', {minimumFractionDigits: 2})}</span>
              </p>
              <p class="text-xs text-gray-400 mt-1">From: ${item.proposal_code}</p>
            </div>
          </div>
        </div>
      `).join('');

      suggestionsContainer.innerHTML = html;
      suggestionsContainer.classList.remove('hidden');

      // Add click handlers
      suggestionsContainer.querySelectorAll('.suggestion-item').forEach(item => {
        item.addEventListener('click', function() {
          const itemData = JSON.parse(this.dataset.item);
          selectSuggestion(itemData);
        });
      });
    }

    function updateSelectedSuggestion(suggestions) {
      suggestions.forEach((item, index) => {
        if (index === selectedSuggestionIndex) {
          item.classList.add('bg-orange-50');
        } else {
          item.classList.remove('bg-orange-50');
        }
      });

      if (selectedSuggestionIndex >= 0 && suggestions[selectedSuggestionIndex]) {
        suggestions[selectedSuggestionIndex].scrollIntoView({ block: 'nearest', behavior: 'smooth' });
      }
    }

    function selectSuggestion(item) {
      descriptionField.value = item.item_name;
      charCount.textContent = item.item_name.length;
      
      // Auto-fill category
      const categoryMap = {
        'MATERIAL': 'MATERIALS',
        'EQUIPMENT': 'EQUIPMENT',
        'LABOR': null // Labor items should not be added manually - use Payroll module
      };
      
      if (categoryMap[item.category]) {
        categorySelect.value = categoryMap[item.category];
        categorySelect.dispatchEvent(new Event('change'));
      }

      hideSuggestions();
      supplierNameInput.focus();
      updatePreview();
    }

    function hideSuggestions() {
      suggestionsContainer.classList.add('hidden');
      suggestionsContainer.innerHTML = '';
      selectedSuggestionIndex = -1;
    }

    function showSuggestionMessage(message) {
      suggestionsContainer.innerHTML = `
        <div class="px-4 py-3 text-sm text-gray-600 text-center">
          ${message}
        </div>
      `;
      suggestionsContainer.classList.remove('hidden');
    }

    // Close suggestions when clicking outside
    document.addEventListener('click', function(e) {
      if (!descriptionField.contains(e.target) && !suggestionsContainer.contains(e.target)) {
        hideSuggestions();
      }
    });

    // ============================================
    // FILE UPLOAD HANDLING
    // ============================================
    uploadArea.addEventListener('click', () => {
      receiptFileInput.click();
    });

    receiptFileInput.addEventListener('change', function(e) {
      handleFileSelect(e.target.files[0]);
    });

    uploadArea.addEventListener('dragover', (e) => {
      e.preventDefault();
      uploadArea.classList.add('border-[#e9922c]', 'bg-orange-50');
    });

    uploadArea.addEventListener('dragleave', () => {
      uploadArea.classList.remove('border-[#e9922c]', 'bg-orange-50');
    });

    uploadArea.addEventListener('drop', (e) => {
      e.preventDefault();
      uploadArea.classList.remove('border-[#e9922c]', 'bg-orange-50');
      
      const file = e.dataTransfer.files[0];
      handleFileSelect(file);
    });

    function handleFileSelect(file) {
      if (!file) return;

      // Validate file type
      const validTypes = ['application/pdf', 'image/png', 'image/jpeg', 'image/jpg'];
      if (!validTypes.includes(file.type)) {
        showToast('Invalid file type. Please upload PDF, PNG, or JPG only.', 'error');
        return;
      }

      // Validate file size (5MB)
      const maxSize = 5 * 1024 * 1024;
      if (file.size > maxSize) {
        showToast('File size exceeds 5MB. Please upload a smaller file.', 'error');
        return;
      }

      selectedFile = file;
      showFilePreview(file);
    }

    function showFilePreview(file) {
      const fileName = file.name;
      const fileSize = (file.size / (1024 * 1024)).toFixed(2);

      document.getElementById('file-name').textContent = fileName;
      document.getElementById('file-size').textContent = `${fileSize} MB`;

      uploadArea.classList.add('hidden');
      filePreview.classList.remove('hidden');
    }

    removeFileBtn.addEventListener('click', (e) => {
      e.stopPropagation();
      removeFile();
    });

    function removeFile() {
      selectedFile = null;
      receiptFileInput.value = '';
      filePreview.classList.add('hidden');
      uploadArea.classList.remove('hidden');
    }

    // ============================================
    // PROJECT CHANGE - FETCH ACTIVE PHASES
    // ============================================
    projectSelect.addEventListener('change', async function() {
      const projectId = this.value;
      
      if (projectId) {
        // Fetch active phases for the selected project
        try {
          const response = await fetch(`budget_expenses/get_phase_budget_proposals.php?project_id=${projectId}`);
          const result = await response.json();
          
          // Clear existing phase options
          phaseSelect.innerHTML = '<option value="">Select project phase</option>';
          
          if (result.success && result.phases && result.phases.length > 0) {
            result.phases.forEach(phase => {
              const option = document.createElement('option');
              option.value = phase;
              option.textContent = phase;
              phaseSelect.appendChild(option);
            });
            
            // Show success message
            const helpText = phaseSelect.parentElement.querySelector('.text-xs');
            if (helpText) {
              helpText.textContent = 'Select phase first to enable smart item suggestions below';
              helpText.className = 'mt-1 text-xs text-gray-500';
            }
          } else {
            // No phases found
            const helpText = phaseSelect.parentElement.querySelector('.text-xs');
            if (helpText) {
              helpText.textContent = '⚠️ No active phases found. Please create an approved budget proposal first.';
              helpText.className = 'mt-1 text-xs text-amber-600';
            }
          }
        } catch (error) {
          console.error('Error fetching phases:', error);
          showToast('Failed to load phases. Please try again.', 'error');
        }
      } else {
        // Clear phases if no project selected
        phaseSelect.innerHTML = '<option value="">Select project phase</option>';
      }
      
      updatePreview();
    });

    // ============================================
    // LIVE PREVIEW UPDATES
    // ============================================
    expenseDateInput.addEventListener('change', updatePreview);
    phaseSelect.addEventListener('change', updatePreview);
    categorySelect.addEventListener('change', updatePreview);
    supplierNameInput.addEventListener('input', updatePreview);
    
    document.querySelectorAll('input[name="status"]').forEach(radio => {
      radio.addEventListener('change', updatePreview);
    });

    function updatePreview() {
      // Project
      const selectedProject = projectSelect.options[projectSelect.selectedIndex];
      if (selectedProject && projectSelect.value) {
        previewProject.textContent = selectedProject.getAttribute('data-name');
        previewProject.className = 'text-sm font-medium text-gray-900';
      } else {
        previewProject.textContent = 'No project selected';
        previewProject.className = 'text-sm font-medium text-gray-400 italic';
      }

      // Date
      if (expenseDateInput.value) {
        const date = new Date(expenseDateInput.value);
        previewDate.textContent = date.toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' });
      }

      // Phase
      if (phaseSelect.value) {
        previewPhase.textContent = phaseSelect.value;
        previewPhase.className = 'text-sm font-medium text-gray-900';
      } else {
        previewPhase.textContent = 'No phase selected';
        previewPhase.className = 'text-sm font-medium text-gray-400 italic';
      }

      // Category
      if (categorySelect.value) {
        previewCategory.textContent = categorySelect.options[categorySelect.selectedIndex].text;
        previewCategory.className = 'text-sm font-medium text-gray-900';
      } else {
        previewCategory.textContent = 'No category selected';
        previewCategory.className = 'text-sm font-medium text-gray-400 italic';
      }

      // Description
      const desc = descriptionField.value.trim();
      if (desc) {
        previewDescription.textContent = desc;
        previewDescription.className = 'text-sm text-gray-900';
      } else {
        previewDescription.textContent = 'No description entered';
        previewDescription.className = 'text-sm text-gray-400 italic';
      }

      // Supplier
      const supplier = supplierNameInput.value.trim();
      if (supplier) {
        previewSupplier.textContent = supplier;
        previewSupplier.className = 'text-sm text-gray-900';
      } else {
        previewSupplier.textContent = 'No supplier entered';
        previewSupplier.className = 'text-sm text-gray-400 italic';
      }

      // Status
      const statusRadio = document.querySelector('input[name="status"]:checked');
      if (statusRadio) {
        const status = statusRadio.value;
        if (status === 'PENDING') {
          previewStatus.textContent = 'Pending';
          previewStatus.className = 'inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800';
        } else {
          previewStatus.textContent = 'Approved';
          previewStatus.className = 'inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800';
        }
      }
    }

    function formatPeso(amount) {
      return amount.toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ',');
    }

    // ============================================
    // FORM VALIDATION & SUBMISSION
    // ============================================
    function validateForm() {
      if (!projectSelect.value) {
        showToast('Please select a project', 'warning');
        projectSelect.focus();
        return false;
      }

      if (!expenseDateInput.value) {
        showToast('Please enter expense date', 'warning');
        expenseDateInput.focus();
        return false;
      }

      if (!phaseSelect.value) {
        showToast('Please select a phase', 'warning');
        phaseSelect.focus();
        return false;
      }

      const supplier = supplierNameInput.value.trim();
      if (!supplier) {
        showToast('Please enter supplier name', 'warning');
        supplierNameInput.focus();
        return false;
      }

      if (supplier.length < 3) {
        showToast('Supplier name must be at least 3 characters', 'warning');
        supplierNameInput.focus();
        return false;
      }

      if (lineItems.length === 0) {
        showToast('Please add at least one expense item', 'warning');
        categorySelect.focus();
        return false;
      }

      return true;
    }

    expenseForm.addEventListener('submit', async function(e) {
      e.preventDefault();

      if (!validateForm()) {
        return;
      }

      // Show loading state
      setLoadingState(true);

      // Calculate total amount
      const totalAmount = lineItems.reduce((sum, item) => sum + item.subtotal, 0);

      // Prepare form data
      const formData = new FormData();
      formData.append('project_id', projectSelect.value);
      formData.append('expense_date', expenseDateInput.value);
      formData.append('phase', phaseSelect.value);
      formData.append('supplier_name', supplierNameInput.value.trim());
      formData.append('total_amount', totalAmount.toFixed(2));
      formData.append('status', document.querySelector('input[name="status"]:checked').value);
      formData.append('notes', notesInput.value.trim());
      formData.append('line_items', JSON.stringify(lineItems));

      // Add file if selected
      if (selectedFile) {
        formData.append('receipt', selectedFile);
      }

      try {
        const response = await fetch('save_expense.php', {
          method: 'POST',
          body: formData
        });

        const result = await response.json();

        if (result.success) {
          // Persist toast so it appears after redirect
          showToast('Expense added successfully!', 'success', true);

          // Redirect after short delay
          setTimeout(() => {
            window.location.href = 'expenses.php?project_id=' + projectSelect.value + '&phase=' + encodeURIComponent(phaseSelect.value);
          }, 1000);
        } else {
          showToast(result.message || 'Failed to add expense. Please try again.', 'error');
          setLoadingState(false);
        }
      } catch (error) {
        console.error('Error:', error);
        showToast('An error occurred. Please try again.', 'error');
        setLoadingState(false);
      }
    });

    function setLoadingState(isLoading) {
      submitBtn.disabled = isLoading;
      cancelBtn.disabled = isLoading;

      if (isLoading) {
        submitIcon.classList.add('hidden');
        loadingIcon.classList.remove('hidden');
        submitText.textContent = 'Saving...';
        submitBtn.style.opacity = '0.7';
        submitBtn.style.cursor = 'not-allowed';
      } else {
        submitIcon.classList.remove('hidden');
        loadingIcon.classList.add('hidden');
        submitText.textContent = 'Save Expense';
        submitBtn.style.opacity = '1';
        submitBtn.style.cursor = 'pointer';
      }
    }

    // Cancel button
    cancelBtn.addEventListener('click', () => {
      if (confirm('Are you sure you want to cancel? All unsaved changes will be lost.')) {
        window.location.href = 'expenses.php?project_id=' + projectSelect.value + '&phase=' + encodeURIComponent(phaseSelect.value || 'All Phases');
      }
    });

    // Initialize preview on page load
    updatePreview();
    updateGrandTotal();
  </script>
</body>
</html>
