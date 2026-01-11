<?php
/**
 * audit_logs.php
 * ICMIS Admin Module: Professional Audit Trail
 */

session_start();
require_once __DIR__ . '/../../config/config.php';

// Access Control - Admin Only
if (!isset($_SESSION['user_id'])) {
    header('Location: /icmis/index.php?error=not_logged_in');
    exit;
}

// Database Connection
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);
$conn->set_charset("utf8mb4");

// Pagination & Filtering
$per_page = 10;
$page = max(1, intval($_GET['page'] ?? 1));
$offset = ($page - 1) * $per_page;

$filter_module = $_GET['module'] ?? '';
$filter_action = $_GET['action'] ?? '';
$filter_user   = $_GET['user'] ?? '';
$filter_date   = $_GET['date'] ?? '';

// Build Query
$where_clauses = ["1=1"];
$params = [];
$types = "";

if ($filter_module) {
    $where_clauses[] = "module = ?";
    $params[] = $filter_module;
    $types .= "s";
}
if ($filter_action) {
    $where_clauses[] = "action LIKE ?";
    $params[] = "%$filter_action%";
    $types .= "s";
}
if ($filter_user) {
    $where_clauses[] = "(u.full_name LIKE ? OR al.user_id = ?)";
    $params[] = "%$filter_user%";
    $params[] = intval($filter_user);
    $types .= "si";
}
if ($filter_date) {
    $where_clauses[] = "DATE(al.created_at) = ?";
    $params[] = $filter_date;
    $types .= "s";
}

$where_sql = implode(" AND ", $where_clauses);

// Fetch Total Count
$count_sql = "SELECT COUNT(*) as total FROM icmis_audit_logs al LEFT JOIN icmis_users u ON al.user_id = u.user_id WHERE $where_sql";
$stmt = $conn->prepare($count_sql);
if (!empty($params)) $stmt->bind_param($types, ...$params);
$stmt->execute();
$total_rows = $stmt->get_result()->fetch_assoc()['total'];
$total_pages = ceil($total_rows / $per_page);

// Fetch Data
$sql = "SELECT al.*, u.full_name as username, u.role 
    FROM icmis_audit_logs al 
        LEFT JOIN icmis_users u ON al.user_id = u.user_id 
        WHERE $where_sql 
        ORDER BY al.created_at DESC 
        LIMIT ? OFFSET ?";
        
$params[] = $per_page;
$params[] = $offset;
$types .= "ii";

$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Audit Logs | ICMIS Admin</title>
    <?php include __DIR__ . '/../../includes/head_assetsv2.php'; ?>
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
        .modal-overlay { background-color: rgba(15, 23, 42, 0.5); backdrop-filter: blur(4px); }
        .modal-content { transform: scale(0.95); opacity: 0; transition: all 0.2s ease-out; }
        .modal-content.modal-open { transform: scale(1); opacity: 1; }
        /* Json Syntax Highlighting simple */
        .json-key { color: #8b5cf6; font-weight: bold; }
        .json-string { color: #059669; }
        .json-number { color: #d97706; }
    </style>
</head>
<body class="bg-gray-50 text-slate-800">
    
    <?php include __DIR__ . '/../../includes/sidebar.php'; ?>
    <?php include __DIR__ . '/../../includes/header.php'; ?>

    <main class="ml-56 mt-18 p-8 transition-all duration-300 animate-fade-in">
        <div class="max-w-[90rem] mx-auto">
            
            <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 mb-8">
                <div>
                    <h1 class="text-2xl font-black text-gray-900 tracking-tight">System Audit Logs</h1>
                    <p class="text-sm text-gray-500 mt-1">
                        Monitoring system integrity and user activity.
                        <span id="auditTotalRecords" class="ml-2 inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-800">
                            <?= number_format($total_rows) ?> Records
                        </span>
                    </p>
                </div>
                <div class="flex gap-2">
                    <button onclick="exportLogs()" class="px-4 py-2 bg-[#e9922c] text-white rounded-lg text-sm font-bold hover:bg-[#d17f1f] shadow-sm transition-colors flex items-center gap-2">
                        <i data-lucide="download" class="w-4 h-4"></i> Export CSV
                    </button>
                </div>
            </div>

            <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-4 mb-6">
                <form id="filterForm" method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-4 items-end">
                    
                    <div>
                        <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Date</label>
                        <input type="date" name="date" value="<?= htmlspecialchars($filter_date) ?>" class="w-full bg-gray-50 border border-gray-200 text-gray-700 text-sm rounded-lg focus:ring-[#e9922c] focus:border-[#e9922c] p-2.5">
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Module</label>
                        <select name="module" class="w-full bg-gray-50 border border-gray-200 text-gray-700 text-sm rounded-lg focus:ring-[#e9922c] focus:border-[#e9922c] p-2.5">
                            <option value="">All Modules</option>
                            <option value="AUTH" <?= $filter_module == 'AUTH' ? 'selected' : '' ?>>Auth / Login</option>
                            <option value="WORKFORCE" <?= $filter_module == 'WORKFORCE' ? 'selected' : '' ?>>Workforce</option>
                            <option value="BUDGET" <?= $filter_module == 'BUDGET' ? 'selected' : '' ?>>Budget</option>
                            <option value="INVENTORY" <?= $filter_module == 'INVENTORY' ? 'selected' : '' ?>>Inventory</option>
                            <option value="PROCUREMENT" <?= $filter_module == 'PROCUREMENT' ? 'selected' : '' ?>>Procurement</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Action Type</label>
                        <select name="action" class="w-full bg-gray-50 border border-gray-200 text-gray-700 text-sm rounded-lg focus:ring-[#e9922c] focus:border-[#e9922c] p-2.5">
                            <option value="">All Actions</option>
                            <option value="CREATE" <?= $filter_action == 'CREATE' ? 'selected' : '' ?>>Create</option>
                            <option value="UPDATE" <?= $filter_action == 'UPDATE' ? 'selected' : '' ?>>Update</option>
                            <option value="DELETE" <?= $filter_action == 'DELETE' ? 'selected' : '' ?>>Delete</option>
                            <option value="LOGIN" <?= $filter_action == 'LOGIN' ? 'selected' : '' ?>>Login/Access</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-gray-500 uppercase mb-1">User / ID</label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                                <i data-lucide="search" class="w-4 h-4 text-gray-400"></i>
                            </div>
                            <input type="text" name="user" value="<?= htmlspecialchars($filter_user) ?>" placeholder="Search user..." class="w-full bg-gray-50 border border-gray-200 text-gray-700 text-sm rounded-lg focus:ring-[#e9922c] focus:border-[#e9922c] pl-10 p-2.5">
                        </div>
                    </div>
                </form>
            </div>

            <div class="bg-white border border-gray-200 rounded-xl shadow-sm overflow-hidden">
                <div id="auditTableContainer" class="overflow-x-auto relative">
                    <div id="auditLoading" class="hidden absolute inset-0 flex items-center justify-center bg-white/60 z-10">
                        <svg class="animate-spin h-8 w-8 text-gray-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
                        </svg>
                    </div>
                    <table class="w-full text-left border-collapse">
                        <thead class="bg-gray-50 border-b border-gray-200">
                            <tr>
                                <th class="px-6 py-4 text-xs font-bold text-gray-500 uppercase tracking-wider w-40">Timestamp</th>
                                <th class="px-6 py-4 text-xs font-bold text-gray-500 uppercase tracking-wider w-48">User</th>
                                <th class="px-6 py-4 text-xs font-bold text-gray-500 uppercase tracking-wider w-32">Module</th>
                                <th class="px-6 py-4 text-xs font-bold text-gray-500 uppercase tracking-wider w-32">Action</th>
                                <th class="px-6 py-4 text-xs font-bold text-gray-500 uppercase tracking-wider">Details</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 bg-white">
                            <?php if ($result->num_rows > 0): ?>
                                <?php while($row = $result->fetch_assoc()): 
                                    // Determine Badge Colors
                                    $action_class = 'bg-gray-100 text-gray-800 border-gray-200';
                                    if (strpos($row['action'], 'CREATE') !== false) $action_class = 'bg-green-50 text-green-700 border-green-200';
                                    elseif (strpos($row['action'], 'UPDATE') !== false) $action_class = 'bg-blue-50 text-blue-700 border-blue-200';
                                    elseif (strpos($row['action'], 'DELETE') !== false) $action_class = 'bg-red-50 text-red-700 border-red-200';
                                    elseif (strpos($row['action'], 'LOGIN') !== false) $action_class = 'bg-purple-50 text-purple-700 border-purple-200';
                                    
                                    // Process Details for Modal
                                    $details_raw = $row['details'];
                                    $details_short = mb_strimwidth($details_raw, 0, 60, "...");
                                ?>
                                <tr class="hover:bg-slate-50/50 transition-colors" role="button" tabindex="0" style="cursor:pointer;" onclick="viewPayload(this)" onkeydown="if(event.key==='Enter') viewPayload(this)" data-payload="<?= htmlspecialchars($details_raw) ?>">
                                    
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <p class="text-sm font-medium text-gray-900"><?= date('M j, Y', strtotime($row['created_at'])) ?></p>
                                        <p class="text-xs text-gray-400 font-mono"><?= date('H:i:s', strtotime($row['created_at'])) ?></p>
                                    </td>

                                    <td class="px-6 py-4">
                                        <div class="flex items-center gap-3">
                                            <div class="w-8 h-8 rounded-full bg-gray-100 flex items-center justify-center text-gray-500 text-xs font-bold border border-gray-200">
                                                <?= strtoupper(substr($row['username'] ?? 'S', 0, 2)) ?>
                                            </div>
                                            <div>
                                                <p class="text-sm font-bold text-gray-800"><?= htmlspecialchars($row['username'] ?? 'System') ?></p>
                                                <p class="text-xs text-gray-500"><?= ucfirst(strtolower($row['role'] ?? 'System')) ?></p>
                                            </div>
                                        </div>
                                    </td>

                                    <td class="px-6 py-4">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-md text-xs font-medium bg-gray-100 text-gray-800 border border-gray-200">
                                            <?= htmlspecialchars($row['module']) ?>
                                        </span>
                                    </td>

                                    <td class="px-6 py-4">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-md text-xs font-bold border uppercase tracking-wide <?= $action_class ?>">
                                            <?= htmlspecialchars($row['action']) ?>
                                        </span>
                                    </td>

                                    <td class="px-6 py-4">
                                        <span class="text-sm text-gray-600 font-mono truncate max-w-[200px]"><?= htmlspecialchars($details_short) ?></span>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="5" class="px-6 py-12 text-center">
                                        <div class="flex flex-col items-center justify-center">
                                            <div class="w-12 h-12 bg-gray-100 rounded-full flex items-center justify-center mb-3">
                                                <i data-lucide="search-x" class="w-6 h-6 text-gray-400"></i>
                                            </div>
                                            <p class="text-gray-900 font-medium">No audit records found</p>
                                            <p class="text-gray-500 text-sm">Try adjusting your filters</p>
                                        </div>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <?php if ($total_pages > 1): ?>
                <div id="auditPagination" class="px-6 py-4 bg-gray-50 border-t border-gray-200 flex items-center justify-between">
                    <p class="text-sm text-gray-500">
                        Showing page <span class="font-bold text-gray-900"><?= $page ?></span> of <span class="font-bold text-gray-900"><?= $total_pages ?></span>
                    </p>
                    <div class="flex gap-2">
                        <?php if ($page > 1): ?>
                            <a href="?page=<?= $page-1 ?>&<?= http_build_query(array_diff_key($_GET, ['page'=>''])) ?>" class="ajax-page-link px-3 py-1.5 bg-white border border-gray-300 rounded-md text-sm font-medium hover:bg-gray-50 text-gray-700">Previous</a>
                        <?php endif; ?>
                        
                        <?php if ($page < $total_pages): ?>
                            <a href="?page=<?= $page+1 ?>&<?= http_build_query(array_diff_key($_GET, ['page'=>''])) ?>" class="ajax-page-link px-3 py-1.5 bg-white border border-gray-300 rounded-md text-sm font-medium hover:bg-gray-50 text-gray-700">Next</a>
                        <?php endif; ?>
                    </div>
                </div>
                <?php else: ?>
                <div id="auditPagination"></div>
                <?php endif; ?>
            </div>

        </div>
    </main>

    <div id="payloadModal" class="hidden fixed inset-0 z-50 overflow-y-auto" aria-modal="true">
        <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 modal-overlay transition-opacity" onclick="closeModal()"></div>
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
            
            <div class="modal-content inline-block align-bottom bg-white rounded-xl text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-2xl w-full">
                <div class="bg-gray-900 px-6 py-4 border-b border-gray-800 flex justify-between items-center">
                    <h3 class="text-lg leading-6 font-bold text-white flex items-center gap-2">
                        <i data-lucide="code" class="w-5 h-5 text-[#e9922c]"></i> Transaction Payload
                    </h3>
                    <button onclick="closeModal()" class="text-gray-400 hover:text-white transition-colors">
                        <i data-lucide="x" class="w-5 h-5"></i>
                    </button>
                </div>
                
                <div class="p-6 bg-slate-50">
                    <div class="bg-slate-900 rounded-lg p-4 border border-slate-700 shadow-inner overflow-x-auto">
                        <pre id="jsonContainer" class="text-xs font-mono leading-relaxed text-slate-300"></pre>
                    </div>
                </div>

                <div class="bg-white px-6 py-3 flex justify-end border-t border-gray-100">
                    <button type="button" onclick="closeModal()" class="px-4 py-2 bg-gray-100 text-gray-700 font-bold rounded-lg hover:bg-gray-200 transition-colors text-sm">
                        Close
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script src="js/audit_logs.js"></script>
</body>
</html>