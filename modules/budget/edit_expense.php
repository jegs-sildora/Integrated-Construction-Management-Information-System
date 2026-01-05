<!DOCTYPE html>
<html lang="en">
<head>
  <!-- Global project styles -->
  <link rel="stylesheet" href="/icmis_budget/css/output.css">
  <link rel="stylesheet" href="/icmis_budget/css/input.css">
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Edit Expense - ICMIS</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Arimo:wght@400;500;600;700&display=swap" rel="stylesheet">
  <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
  <script src="https://unpkg.com/lucide@latest"></script>
  <style>
    body {
      font-family: 'Arimo', sans-serif;
    }
  </style>
</head>
<body class="bg-gray-50">
  <?php include __DIR__ . '/../../includes/sidebar.php'; ?>
  <?php 
    // Connection & Context - using centralized config
    include __DIR__ . '/project_context.php';
    $conn = getBudgetConnection();
  ?>
  
  <?php 
    $pageTitle = "Edit Expense";
    $pageSubTitle = "Update Expense Details";
    $pageSection = "Budget & Cost Control";
    $userName = "John Doe";
    $userRole = "Financial Manager";
    $notificationCount = 0;
    include __DIR__ . '/../../includes/header.php'; 
  ?>

  <?php
    // Get expense_id from URL
    if (!isset($_GET['id']) || empty($_GET['id'])) {
      header('Location: expenses.php');
      exit;
    }

    $expense_id = intval($_GET['id']);

    // Fetch projects from main database
    $sql_projects = "SELECT project_id, project_code, project_name FROM icmis_projects ORDER BY project_id DESC";
    $result_projects = $conn->query($sql_projects);
    $projects = [];
    if ($result_projects && $result_projects->num_rows > 0) {
      while ($row = $result_projects->fetch_assoc()) {
        $projects[] = $row;
      }
    }
  ?>

  <!-- Toast included globally via header.php -->

  <main class="ml-56 mt-20 p-6">
    <div class="max-w-7xl mx-auto">
      <!-- Back Link -->
      <a href="expenses.php" class="inline-flex items-center text-gray-600 hover:text-gray-900 mb-6 transition-colors underline">
        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
        </svg>
        Back to Expenses Dashboard
      </a>

      <!-- Page Header -->
      <div class="mb-8 text-center">
        <h1 class="text-2xl font-bold text-gray-900 mb-2">Edit Expense</h1>
        <p class="text-sm text-gray-600">Update expense details below</p>
      </div>

      <!-- Loading State -->
      <div id="loading-container" class="flex items-center justify-center py-12">
        <div class="text-center">
          <svg class="animate-spin h-12 w-12 text-[#e9922c] mx-auto mb-4" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
          </svg>
          <p class="text-gray-600">Loading expense details...</p>
        </div>
      </div>

      <!-- 2-Column Grid Layout -->
      <div id="form-container" class="grid grid-cols-2 gap-6" style="display: none;">
        
        <!-- LEFT CARD - Form -->
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-6 h-[calc(100vh-200px)] overflow-y-auto">
          
          <form id="expense-form">
            <input type="hidden" id="expense-id" value="<?php echo $expense_id; ?>">
            
            <!-- Project Selection -->
            <div class="mb-6">
              <label for="project" class="block text-sm text-gray-700 mb-2">Select Project <span class="text-red-500">*</span></label>
              <select id="project" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-orange-500 outline-none">
                <option value="">Select a project</option>
                  <?php foreach ($projects as $proj): ?>
                    <option value="<?php echo $proj['project_id']; ?>" 
                            data-code="<?php echo htmlspecialchars($proj['project_code']); ?>" 
                            data-name="<?php echo htmlspecialchars($proj['project_name']); ?>"> <?php echo htmlspecialchars($proj['project_name']); ?> </option>
                  <?php endforeach; ?>
              </select>
            </div>

            <!-- Expense Date -->
            <div class="mb-6">
              <label for="expense-date" class="block text-sm text-gray-700 mb-2">Expense Date <span class="text-red-500">*</span></label>
              <input type="date" id="expense-date" max="<?php echo date('Y-m-d'); ?>" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-orange-500 outline-none">
              <p class="mt-1 text-xs text-gray-500">Date when expense was incurred</p>
            </div>

            <!-- Phase -->
            <div class="mb-6">
              <label for="phase" class="block text-sm text-gray-700 mb-2">Phase <span class="text-red-500">*</span></label>
              <select id="phase" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-orange-500 outline-none">
                <option value="">Select project phase</option>
                <option value="Phase 1: Mobilization">Phase 1: Mobilization</option>
                <option value="Phase 2: Structural">Phase 2: Structural</option>
                <option value="Phase 3: MEPFS">Phase 3: MEPFS</option>
                <option value="Phase 4: Finishing">Phase 4: Finishing</option>
              </select>
            </div>

            <!-- Category -->
            <div class="mb-6">
              <label for="category" class="block text-sm text-gray-700 mb-2">Category <span class="text-red-500">*</span></label>
              <select id="category" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-orange-500 outline-none">
                <option value="">Select a category</option>
                <option value="MATERIALS">Materials (Cement, Wood, Steel, etc.)</option>
                <option value="LABOR">Labor (Workers, Contractors)</option>
                <option value="EQUIPMENT">Equipment (Rentals, Tools, Machinery)</option>
              </select>
              <p class="mt-1 text-xs text-gray-500">Select the expense category for proper budget tracking</p>
            </div>

            <!-- Description -->
            <div class="mb-6">
              <label for="description" class="block text-sm text-gray-700 mb-2">Item / Description <span class="text-red-500">*</span></label>
              <div class="relative">
                <textarea id="description" rows="3" maxlength="500" placeholder="Enter detailed description of the expense..." class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-orange-500 outline-none resize-none"></textarea>
                <div class="absolute bottom-2 right-3 text-xs text-gray-400">
                  <span id="char-count">0</span>/500
                </div>
              </div>
            </div>

            <!-- Quantity and Unit Cost -->
            <div class="mb-6">
              <div class="grid grid-cols-2 gap-4">
                <div>
                  <label for="quantity" class="block text-sm text-gray-700 mb-2">Quantity <span class="text-red-500">*</span></label>
                  <input type="number" id="quantity" placeholder="0" min="0" step="1" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-orange-500 outline-none">
                </div>
                <div>
                  <label for="unit-cost" class="block text-sm text-gray-700 mb-2">Unit Cost (₱) <span class="text-red-500">*</span></label>
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
              
              <!-- Current Receipt -->
              <div id="current-receipt" class="hidden mb-3 p-3 bg-blue-50 border border-blue-200 rounded-lg">
                <div class="flex items-center justify-between">
                  <div class="flex items-center gap-2">
                    <svg class="w-5 h-5 text-blue-600" fill="currentColor" viewBox="0 0 20 20">
                      <path fill-rule="evenodd" d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4z" clip-rule="evenodd"/>
                    </svg>
                    <span class="text-sm text-blue-900 font-medium">Current Receipt</span>
                  </div>
                  <button type="button" id="view-current-receipt" class="text-sm text-blue-600 hover:text-blue-800 underline">View</button>
                </div>
              </div>
              
              <!-- Upload Area -->
              <div id="upload-area" class="border-2 border-dashed border-gray-300 rounded-lg p-8 text-center bg-gray-50 hover:border-[#e9922c] hover:bg-orange-50 transition-all duration-200 cursor-pointer">
                <input type="file" id="receipt-file" accept=".pdf,.png,.jpg,.jpeg" class="hidden">
                <div class="flex flex-col items-center">
                  <svg class="w-12 h-12 text-gray-400 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>
                  </svg>
                  <p class="text-sm text-gray-600 mb-1">Click to upload new receipt or drag and drop</p>
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
                  <input type="radio" id="status-pending" name="status" value="PENDING" class="w-4 h-4 text-[#e9922c] border-gray-300">
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
            <div class="w-12 h-12 bg-[#e9922c] rounded-lg flex items-center justify-center text-white text-2xl font-bold mr-4">
              I
            </div>
            <div>
              <h2 class="text-lg font-bold text-gray-900">Expense Receipt</h2>
              <p class="text-xs text-gray-500">ICMIS - Budget & Cost Control</p>
            </div>
          </div>

          <!-- Current Date -->
          <div class="text-sm text-gray-600 mb-6">
            <span class="font-medium">Date:</span> <span id="preview-date"></span>
          </div>

          <!-- Project Information -->
          <div class="mb-6 space-y-3">
            <div class="bg-gray-50 rounded-lg p-4 border border-gray-200">
              <p class="text-xs text-gray-500 uppercase tracking-wide mb-1">Project</p>
              <p id="preview-project" class="text-sm font-medium text-gray-900"></p>
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
              <svg class="w-16 h-16 mx-auto mb-3 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
              </svg>
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
              <span id="submit-text">Update Expense</span>
            </button>
          </div>

        </div>

      </div>
    </div>
  </main>

  <script>
    // Initialize Lucide icons
    lucide.createIcons();

    // State Management
    let lineItems = [];
    let currentReceiptPath = null;

    // Form elements
    const expenseForm = document.getElementById('expense-form');
    const expenseIdInput = document.getElementById('expense-id');
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
    const currentReceiptDiv = document.getElementById('current-receipt');
    const viewCurrentReceiptBtn = document.getElementById('view-current-receipt');
    let selectedFile = null;

    // Preview elements
    const previewDate = document.getElementById('preview-date');
    const previewProject = document.getElementById('preview-project');
    const previewPhase = document.getElementById('preview-phase');
    const previewCategory = document.getElementById('preview-category');
    const previewSupplier = document.getElementById('preview-supplier');
    const previewStatus = document.getElementById('preview-status');
    const previewAmount = document.getElementById('preview-amount');

    // Buttons
    const cancelBtn = document.getElementById('cancel-btn');
    const submitBtn = document.getElementById('submit-expense-btn');
    const submitIcon = document.getElementById('submit-icon');
    const loadingIcon = document.getElementById('loading-icon');
    const submitText = document.getElementById('submit-text');

    // Containers
    const loadingContainer = document.getElementById('loading-container');
    const formContainer = document.getElementById('form-container');

    // ============================================
    // LOAD EXPENSE DATA
    // ============================================
    async function loadExpenseData() {
      try {
        const expenseId = expenseIdInput.value;
        const response = await fetch(`budget_expenses/get_expense_for_edit.php?id=${expenseId}`);
        const result = await response.json();

        if (!result.success) {
          showToast(result.message || 'Failed to load expense data', 'error');
          setTimeout(() => {
            window.location.href = 'expenses.php';
          }, 2000);
          return;
        }

        const expense = result.expense;

        // Populate form fields
        projectSelect.value = expense.project_id;
        expenseDateInput.value = expense.expense_date;
        phaseSelect.value = expense.phase;
        supplierNameInput.value = expense.supplier_name;
        notesInput.value = expense.notes || '';

        // Set status
        if (expense.status === 'APPROVED') {
          document.getElementById('status-approved').checked = true;
        } else {
          document.getElementById('status-pending').checked = true;
        }

        // Load line items
        if (expense.line_items && expense.line_items.length > 0) {
          lineItems = expense.line_items;
          renderLineItems();
          updateGrandTotal();
        }

        // Handle receipt
        if (expense.receipt_path) {
          currentReceiptPath = expense.receipt_path;
          currentReceiptDiv.classList.remove('hidden');
          viewCurrentReceiptBtn.onclick = () => {
            window.open(expense.receipt_path, '_blank');
          };
        }

        // Update preview
        updatePreview();

        // Show form, hide loading
        loadingContainer.style.display = 'none';
        formContainer.style.display = 'grid';

      } catch (error) {
        console.error('Error loading expense:', error);
        showToast('An error occurred while loading expense data', 'error');
        setTimeout(() => {
          window.location.href = 'expenses.php';
        }, 2000);
      }
    }

    // Load data on page load
    document.addEventListener('DOMContentLoaded', loadExpenseData);

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

    // Character count
    descriptionField.addEventListener('input', () => {
      charCount.textContent = descriptionField.value.length;
    });

    // ============================================
    // LINE ITEMS MANAGEMENT
    // ============================================
    let editingItemId = null; // Track which item is being edited

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

      // Check if we're editing an existing item
      if (editingItemId !== null) {
        // Update existing item
        const itemIndex = lineItems.findIndex(item => item.id === editingItemId);
        if (itemIndex !== -1) {
          lineItems[itemIndex] = {
            id: editingItemId,
            category: category,
            description: description,
            quantity: quantity,
            unitCost: unitCost,
            subtotal: subtotal
          };
          showToast('Expense item updated successfully', 'success');
          editingItemId = null;
          addLineItemBtn.innerHTML = `
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            Add Expense Item
          `;
        }
      } else {
        // Add new item
        const item = {
          id: Date.now(),
          category: category,
          description: description,
          quantity: quantity,
          unitCost: unitCost,
          subtotal: subtotal
        };
        lineItems.push(item);
        showToast('Expense item added successfully', 'success');
      }

      renderLineItems();
      updateGrandTotal();

      // Clear inputs
      categorySelect.value = '';
      descriptionField.value = '';
      quantityInput.value = '';
      unitCostInput.value = '';
      charCount.textContent = '0';
      itemSubtotalSpan.textContent = '₱0.00';

      categorySelect.focus();
    });

    // Function to edit a line item
    function editLineItem(itemId) {
      const item = lineItems.find(i => i.id === itemId);
      if (!item) return;

      // Populate form with item data
      categorySelect.value = item.category;
      descriptionField.value = item.description;
      quantityInput.value = item.quantity;
      unitCostInput.value = item.unitCost;
      charCount.textContent = item.description.length;
      
      // Calculate and display subtotal
      calculateItemSubtotal();

      // Set editing mode
      editingItemId = itemId;
      
      // Change button text to "Update Item"
      addLineItemBtn.innerHTML = `
        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
        </svg>
        Update Expense Item
      `;

      // Scroll to form
      categorySelect.focus();
      window.scrollTo({ top: 0, behavior: 'smooth' });
    }

    // Make editLineItem available globally
    window.editLineItem = editLineItem;

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
      div.className = 'bg-gray-50 rounded-lg p-3 border border-gray-200 cursor-pointer hover:bg-gray-100 transition-colors';
      div.onclick = function(e) {
        // Don't trigger if clicking the delete button
        if (e.target.closest('button')) return;
        editLineItem(item.id);
      };
      
      const categoryBadgeColors = {
        'MATERIALS': 'bg-purple-100 text-purple-800',
        'LABOR': 'bg-cyan-100 text-cyan-800',
        'EQUIPMENT': 'bg-green-100 text-green-800'
      };

      div.innerHTML = `
        <div class="flex justify-between items-start mb-2">
          <div class="flex-1">
            <div class="flex items-center gap-2 mb-1">
              <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium ${categoryBadgeColors[item.category] || 'bg-gray-100 text-gray-800'}">
                ${item.category}
              </span>
              <span class="text-xs text-gray-400 italic">Click to edit</span>
            </div>
            <p class="text-sm font-medium text-gray-900">${item.description}</p>
            <p class="text-xs text-gray-500 mt-1">${item.quantity} × ₱${formatPeso(item.unitCost)}</p>
          </div>
          <button type="button" onclick="removeLineItem(${item.id}); event.stopPropagation();" class="text-red-500 hover:text-red-700 transition-colors ml-2">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
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
      const grandTotal = lineItems.reduce((sum, item) => sum + item.subtotal, 0);
      previewAmount.textContent = '₱' + formatPeso(grandTotal);
    }

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
        previewProject.textContent = selectedProject.getAttribute('data-name') || selectedProject.text;
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
      formData.append('expense_id', expenseIdInput.value);
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
        const response = await fetch('budget_expenses/update_expense.php', {
          method: 'POST',
          body: formData
        });

        const result = await response.json();

        if (result.success) {
          // Persist toast so it appears after redirect
          showToast('Expense updated successfully!', 'success', true);

          // Redirect after short delay
          setTimeout(() => {
            window.location.href = 'expenses.php?project_id=' + projectSelect.value + '&phase=' + encodeURIComponent(phaseSelect.value);
          }, 1000);
        } else {
          showToast(result.message || 'Failed to update expense. Please try again.', 'error');
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
        submitText.textContent = 'Updating...';
        submitBtn.style.opacity = '0.7';
        submitBtn.style.cursor = 'not-allowed';
      } else {
        submitIcon.classList.remove('hidden');
        loadingIcon.classList.add('hidden');
        submitText.textContent = 'Update Expense';
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
  </script>
</body>
</html>
