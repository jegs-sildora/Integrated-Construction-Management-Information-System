<?php
// modules/inventory/stock_out.php

require_once '../../config/config.php';
if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['user_id'])) { header("Location: " . BASE_URL . "index.php"); exit(); }

// Connect to Main DB (centralized config)
require_once '../../config/database.php'; 

// Prefer procurement project context if available
if (file_exists(__DIR__ . '/project_context.php')) {
  require_once __DIR__ . '/project_context.php';
  $__proc_conn = getProcurementConnection();
  $__ctx_project = getProjectContext($__proc_conn);
  if ($__ctx_project && $__ctx_project > 0) {
    $_SESSION['current_project_id'] = $__ctx_project;
  }
}

// ==========================================================================
// 3. SESSION BASED CONTEXT LOGIC
// ==========================================================================
$project_id = 0;

if (isset($_GET['project_id'])) {
    $project_id = intval($_GET['project_id']);
    $_SESSION['current_project_id'] = $project_id;
} 
elseif (isset($_SESSION['current_project_id'])) {
    $project_id = $_SESSION['current_project_id'];
}

// ==========================================================================
// 4. FETCH PROJECT DATA
// ==========================================================================
$project_name = 'Select Project';

if ($project_id > 0) {
    // Use Main DB Connection ($conn)
    $stmt = $conn->prepare("SELECT project_name FROM icmis_projects WHERE project_id = ?");
    $stmt->bind_param("i", $project_id);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($row = $res->fetch_assoc()) $project_name = $row['project_name'];
}

// ==========================================================================
// 5. CHECK FOR PURCHASE ORDERS (EMPTY STATE LOGIC)
// ==========================================================================
$has_orders = false;
if ($project_id > 0) {
    // Check for purchase orders in procurement_purchase_orders
    $check_sql = "SELECT COUNT(*) as count FROM procurement_purchase_orders WHERE project_id = ?";
    if ($stmt = $conn->prepare($check_sql)) {
        $stmt->bind_param("i", $project_id);
        $stmt->execute();
        $res = $stmt->get_result();
        $row = $res->fetch_assoc();
        if ($row['count'] > 0) {
            $has_orders = true;
        }
    }
}

$pageSection = "Procurement & Inventory";
$pageTitle = "Stock Out Management";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Stock Out | <?= htmlspecialchars($project_name) ?></title>
    <?php include '../../includes/head_assets.php'; ?>
    <link rel="stylesheet" href="css/style.css"> 
    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
</head>
<body class="bg-slate-50 font-sans text-slate-800">

    <?php include '../../includes/sidebar.php'; ?>
    <?php include '../../includes/header.php'; ?>
    <?php include '../../includes/toast.php'; ?>


      <div id="issueStockModal" class="hidden fixed inset-0 bg-black/60 z-60 flex items-center justify-center p-4 backdrop-blur-sm transition-opacity duration-300">
        <div class="bg-white rounded-2xl shadow-2xl max-w-md w-full transform transition-all scale-100">
          <div class="p-5 rounded-t-2xl flex justify-between items-center bg-red-700 border-b border-red-800">
            <h2 class="text-lg font-bold text-white">Issue Stock</h2>
            <button onclick="closeIssueModal()" class="text-white/80 hover:text-white transition-colors"><i class="fa-solid fa-xmark text-lg"></i></button>
          </div>
          <form id="issueStockForm" class="p-6 space-y-5">
            <div class="flex flex-col gap-1.5">
              <label class="text-xs font-bold text-slate-500 uppercase">Select Item</label>
              <select id="stock_itemID" name="stock_itemID" required class="w-full p-2.5 border border-slate-300 rounded-xl bg-white focus:ring-2 focus:ring-red-200 outline-none transition-all">
                <option value="" disabled selected>Loading Inventory...</option>
              </select>
            </div>
            <div class="grid grid-cols-3 gap-4">
              <div class="flex flex-col gap-1.5">
                <div class="flex items-center gap-2">
                  <label class="text-xs font-bold text-slate-500 uppercase">Quantity</label>
                  <button type="button" id="stock_max_btn" class="text-xs font-semibold text-slate-600 bg-slate-100 px-2 py-0.5 rounded disabled:opacity-50" aria-label="Use maximum available">MAX</button>
                </div>
                <input type="number" id="stock_quantity" name="stock_quantity" required min="0.01" step="0.01" class="w-full p-2.5 border border-slate-300 rounded-xl focus:ring-2 focus:ring-red-200 outline-none transition-all">
              </div>

              <div class="flex flex-col gap-1.5">
                <label class="text-xs font-bold text-slate-500 uppercase">Available</label>
                <input type="text" id="stock_available_qty" readonly class="w-full p-2.5 bg-slate-50 border border-slate-200 rounded-xl text-slate-500 cursor-not-allowed">
              </div>

              <div class="flex flex-col gap-1.5">
                <label class="text-xs font-bold text-slate-500 uppercase">Unit</label>
                <input type="text" id="stock_unit" readonly class="w-full p-2.5 bg-slate-50 border border-slate-200 rounded-xl text-slate-500 cursor-not-allowed">
              </div>
            </div>
            <p id="stock_quantity_error" class="text-sm text-red-600 font-bold text-center" aria-live="polite"></p>
            <?php
              // Fetch employees whose job title is 'Warehouseman'
              $warehousemen = [];
              $wm_sql = "SELECT we.employee_id, we.employee_code, we.first_name, we.last_name FROM workforce_employees we JOIN workforce_job_titles wjt ON we.job_title_id = wjt.job_title_id WHERE wjt.title_name = 'Warehouseman' AND COALESCE(we.status, 'Active') = 'Active' ORDER BY we.first_name, we.last_name";
              if ($wm_stmt = $conn->prepare($wm_sql)) {
                $wm_stmt->execute();
                $wm_res = $wm_stmt->get_result();
                while ($wm_row = $wm_res->fetch_assoc()) {
                  $warehousemen[] = $wm_row;
                }
                $wm_stmt->close();
              }
            ?>

            <div class="flex flex-col gap-1.5">
              <label class="text-xs font-bold text-slate-500 uppercase">Issued To (Person/Area)</label>
              <input type="text" id="stock_issuedTo" name="stock_issuedTo" list="warehouseman_list" required class="w-full p-2.5 border border-slate-300 rounded-xl focus:ring-2 focus:ring-red-200 outline-none transition-all" placeholder="Enter employee name...">

              <datalist id="warehouseman_list">
                <?php if (!empty($warehousemen)): ?>
                  <?php foreach ($warehousemen as $wm): ?>
                        <?php $display = '(' . htmlspecialchars($wm['employee_code']) . ') - ' . htmlspecialchars(trim($wm['first_name'] . ' ' . $wm['last_name'])); ?>
                        <option value="<?= $display ?>"><?= $display ?></option>
                  <?php endforeach; ?>
                <?php endif; ?>
              </datalist>

              <input type="hidden" id="stock_issuedTo_id" name="stock_issuedTo_id" value="">

              <?php
                // Build a name -> id map for client-side lookup
                $warehousemen_map = [];
                foreach ($warehousemen as $wm) {
                  $code = isset($wm['employee_code']) ? $wm['employee_code'] : '';
                  $full = trim($wm['first_name'] . ' ' . $wm['last_name']);
                  if ($code !== '') {
                    $display = '(' . $code . ') - ' . $full;
                    $warehousemen_map[$display] = [
                      'id' => (int)$wm['employee_id'],
                      'code' => $code
                    ];
                  }
                }
              ?>
              <script>
                window.warehousemenMap = <?= json_encode($warehousemen_map, JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_AMP|JSON_HEX_QUOT) ?> || {};
              </script>
            </div>
            <div class="flex flex-col gap-1.5">
              <label class="text-xs font-bold text-slate-500 uppercase">Notes (Optional)</label>
              <textarea id="stock_notes" name="stock_notes" rows="2" class="w-full p-2.5 border border-slate-300 rounded-xl focus:ring-2 focus:ring-red-200 outline-none transition-all"></textarea>
            </div>
                        
            <div class="flex justify-end gap-3 pt-4 border-t border-slate-100">
              <button type="button" class="px-5 py-2.5 text-sm font-bold text-slate-600 hover:text-slate-800 transition-colors" onclick="closeIssueModal()">Cancel</button>
              <button type="submit" class="px-5 py-2.5 text-sm font-bold text-white bg-red-600 hover:bg-red-700 rounded-xl shadow-lg shadow-red-100 transition-all transform hover:-translate-y-0.5">Confirm Issuance</button>
            </div>
          </form>
        </div>
      </div>

      <script src="js/stockout.js"></script>
    <input type="hidden" id="current_project_id" value="<?= $project_id ?>">

    <main class="ml-56 pt-24 min-h-screen transition-all duration-300 animate-fade-in">
        
        <?php if ($project_id == 0): ?>
            <div class="flex flex-col items-center justify-center h-[calc(100vh-140px)]">
                <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-12 text-center max-w-lg w-full">
                    <div class="w-20 h-20 bg-slate-100 rounded-full flex items-center justify-center mx-auto mb-6">
                        <i class="fa-solid fa-folder-open text-3xl text-slate-400"></i>
                    </div>
                    <h2 class="text-xl font-bold text-slate-700 mb-2">No Project Selected</h2>
                    <p class="text-slate-500">Please select a project from the header to manage inventory.</p>
                </div>
            </div>

        <?php elseif (!$has_orders): ?>
            <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-12 h-[calc(100vh-140px)] flex items-center justify-center">
              <div class="max-w-md mx-auto text-center">
                
                <div class="flex justify-center mb-6">
                  <div class="bg-blue-50 rounded-full p-6">
                    <svg class="w-16 h-16 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                    </svg>
                  </div>
                </div>

                <h2 class="text-xl text-gray-900 font-bold mb-3">No Inventory Available</h2>
                
                <p class="text-gray-500 mb-8 leading-relaxed">
                  You cannot issue items because no purchase orders exist for this project yet. Please purchase and receive items first.
                </p>

                <div class="mt-8 pt-8 border-t border-gray-200">
                    <p class="text-sm text-gray-600 mb-3 text-left">To start issuing items:</p>
                    
                    <div class="space-y-3">
                      
                      <div class="flex gap-3 bg-gray-50 rounded-lg p-3 text-left">
                        <div class="flex-shrink-0 mt-1.5">
                          <div class="w-1.5 h-1.5 bg-[#e9922c] rounded-full"></div>
                        </div>
                        <div>
                          <div class="text-sm text-gray-900 font-medium">Navigate to Procurement section</div>
                        </div>
                      </div>

                      <div class="flex gap-3 bg-gray-50 rounded-lg p-3 text-left">
                        <div class="flex-shrink-0 mt-1.5">
                          <div class="w-1.5 h-1.5 bg-[#e9922c] rounded-full"></div>
                        </div>
                        <div>
                          <div class="text-sm text-gray-900 font-medium">Create and approve a purchase order</div>
                        </div>
                      </div>

                      <div class="flex gap-3 bg-gray-50 rounded-lg p-3 text-left">
                        <div class="flex-shrink-0 mt-1.5">
                          <div class="w-1.5 h-1.5 bg-[#e9922c] rounded-full"></div>
                        </div>
                        <div>
                          <div class="text-sm text-gray-900 font-medium">Receive the items in 'Stock In' to build inventory</div>
                        </div>
                      </div>

                    </div>
                </div>

              </div>
            </div>

        <?php else: ?>
            <div class="content-wrapper space-y-6">
                <div class="flex justify-between items-end bg-white p-6 rounded-xl shadow-sm border border-slate-100">
                    <div>
                        <h1 class="text-2xl font-black text-navy-dark">Stock Issuance</h1>
                        <p class="text-slate-500 mt-1">Real-time view of issued items for <span class="text-red-600 font-bold"><?= htmlspecialchars($project_name) ?></span>.</p>
                    </div>
                    <button class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-lg font-bold shadow-sm flex items-center gap-2 cursor-pointer transition-transform hover:-translate-y-0.5" onclick="openIssueModal()">
                        <i class="fa-solid fa-box-open"></i> Issue Item
                    </button>
                </div>

                <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
                    <div class="p-5 border-b border-slate-100 bg-slate-50/50">
                        <h3 class="font-bold text-slate-700">Recent Issuance Logs</h3>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse">
                            <thead class="bg-slate-50 border-b border-slate-200">
                                <tr>
                                    <th class="px-6 py-4 text-xs font-bold text-slate-500 uppercase text-center">Stock ID</th>
                                    <th class="px-6 py-4 text-xs font-bold text-slate-500 uppercase text-center">Item Name</th>
                                    <th class="px-6 py-4 text-xs font-bold text-slate-500 uppercase text-center">Qty Issued</th>
                                    <th class="px-6 py-4 text-xs font-bold text-slate-500 uppercase text-center">Unit</th>
                                    <th class="px-6 py-4 text-xs font-bold text-slate-500 uppercase text-center">Issued To</th>
                                    <th class="px-6 py-4 text-xs font-bold text-slate-500 uppercase text-center">Date</th>
                                </tr>
                            </thead>
                            <tbody id="stock-out-table-body" class="divide-y divide-slate-100">
                                </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Issue Stock modal moved below </main> to avoid stacking/context issues -->
        <?php endif; ?>
    </main>
</body>
</html>