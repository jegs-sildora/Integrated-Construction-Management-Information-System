<?php
// ============================================================
// GROUP DETAILS VIEW
// ============================================================

include __DIR__ . '/project_context.php'; // Adjust path if needed relative to root
$conn = getWorkforceConnection();

$group_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$group_code = isset($_GET['group_code']) ? trim($_GET['group_code']) : '';
$group = null;
$members = [];

// Allow lookup by `group_code` (preferred) or numeric `id` as fallback.
if ($group_code) {
    $sql = "SELECT g.*, 
            CONCAT(l.first_name, ' ', l.last_name) as leader_name,
            l.employee_code as leader_code
            FROM workforce_employee_groups g
            LEFT JOIN workforce_employees l ON g.group_leader_id = l.employee_id
            WHERE g.group_code = ? LIMIT 1";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $group_code);
    $stmt->execute();
    $group = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($group) {
        $group_id = intval($group['group_id']);
    }
} elseif ($group_id) {
    $sql = "SELECT g.*, 
            CONCAT(l.first_name, ' ', l.last_name) as leader_name,
            l.employee_code as leader_code
            FROM workforce_employee_groups g
            LEFT JOIN workforce_employees l ON g.group_leader_id = l.employee_id
            WHERE g.group_id = ? LIMIT 1";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $group_id);
    $stmt->execute();
    $group = $stmt->get_result()->fetch_assoc();
    $stmt->close();

}

// 2. Fetch Group Members (use resolved $group_id)
if ($group && $group_id) {
    $memSql = "SELECT e.employee_id, e.first_name, e.last_name, e.employee_code, e.status,
               gm.role_in_group, gm.joined_date,
               jt.title_name, jt.default_daily_rate
               FROM workforce_group_memberships gm
               JOIN workforce_employees e ON gm.employee_id = e.employee_id
               LEFT JOIN workforce_job_titles jt ON e.job_title_id = jt.job_title_id
               WHERE gm.group_id = ?
               ORDER BY e.last_name ASC";
    
    $stmt = $conn->prepare($memSql);
    $stmt->bind_param("i", $group_id);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($row = $res->fetch_assoc()) {
        $members[] = $row;
    }
    $stmt->close();
}

// Data for Edit Modal
$all_employees = [];
$e_result = $conn->query("SELECT e.employee_id, e.first_name, e.last_name, jt.title_name FROM workforce_employees e LEFT JOIN workforce_job_titles jt ON e.job_title_id = jt.job_title_id WHERE e.status = 'Active' ORDER BY e.last_name ASC");
if ($e_result) while ($row = $e_result->fetch_assoc()) $all_employees[] = $row;

$leaders = [];
$l_result = $conn->query("SELECT e.employee_id, e.first_name, e.last_name FROM workforce_employees e WHERE e.job_title_id = 11 AND e.status = 'Active' ORDER BY e.last_name ASC");
if ($l_result) while ($row = $l_result->fetch_assoc()) $leaders[] = $row;

$pageSection = "Labor & Workforce";
$pageTitle = "Group Details";
$pageSubTitle = $group ? htmlspecialchars($group['group_name']) : "Not Found";
$userName = $_SESSION['user_name'] ?? "Admin";
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Group Details | ICMIS</title>
    
    <?php include __DIR__ . '/../../includes/head_assetsv2.php'; ?>
    <script src="https://unpkg.com/lucide@latest"></script>
    
    <style>
        * { font-family: 'Inter', sans-serif; }
        .member-list-container::-webkit-scrollbar { width: 6px; }
        .member-list-container::-webkit-scrollbar-track { background: #f1f1f1; }
        .member-list-container::-webkit-scrollbar-thumb { background: #d1d5db; border-radius: 10px; }
        .modal-content { transform: scale(0.95); opacity: 0; transition: all 0.2s ease-out; }
        .modal-open .modal-content { transform: scale(1); opacity: 1; }
    </style>
</head>
<body class="bg-gray-50 text-gray-900">
    <?php 
        include __DIR__ . '/../../includes/sidebar.php';
        include __DIR__ . '/../../includes/toast.php';
        include __DIR__ . '/../../includes/header.php'; 
    ?>

    <main class="ml-56 mt-16 p-8">
        <div class="max-w-7xl mx-auto">

            <?php if (!$group): ?>
                <div class="flex flex-col items-center justify-center h-[60vh]">
                    <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mb-4">
                        <i data-lucide="users-2" class="w-8 h-8 text-gray-400"></i>
                    </div>
                    <h2 class="text-xl font-bold text-gray-900">Group Not Found</h2>
                    <p class="text-gray-500 mt-2 mb-6">The deployment group you requested does not exist.</p>
                    <a href="employee_groups.php" class="px-5 py-2.5 bg-white border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 font-medium transition-colors">
                        Back to Groups
                    </a>
                </div>
            <?php else: ?>

            <div class="mb-6 flex items-center justify-between">
                <a href="employee_groups.php" class="flex items-center gap-2 text-gray-500 hover:text-[#d17f1f] transition-colors font-medium">
                    <i data-lucide="arrow-left" class="w-5 h-5"></i> Back to Groups
                </a>
            </div>

            <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden mb-8">
                <div class="bg-gradient-to-r from-gray-900 to-gray-800 px-8 py-8 relative overflow-hidden">
                    <div class="absolute top-0 right-0 p-8 opacity-10">
                        <i data-lucide="hard-hat" class="w-32 h-32 text-white"></i>
                    </div>
                    <div class="relative z-10">
                        <div class="flex flex-col justify-between gap-6">
                            <div>
                                <div class="flex items-center gap-3 mb-2">
                                    <span class="px-2.5 py-1 rounded-md bg-white/10 border border-white/20 text-white text-xs font-semibold backdrop-blur-sm">
                                        Code: <?php echo htmlspecialchars($group['group_code'] ?? $group_id); ?>
                                    </span>
                                </div>
                                <h1 class="text-3xl font-bold text-white tracking-tight mb-2">
                                    <?php echo htmlspecialchars($group['group_name']); ?>
                                </h1>
                                <p class="text-gray-400 max-w-2xl text-md leading-relaxed">
                                    <?php echo htmlspecialchars($group['description'] ?: 'No description provided.'); ?>
                                </p>
                            </div>
                            <div class="flex items-center gap-4 bg-white/5 border border-white/10 p-4 rounded-xl backdrop-blur-sm">
                                <div class="w-12 h-12 rounded-full bg-[#d17f1f] flex items-center justify-center text-white font-bold text-lg shadow-lg border-2 border-gray-800">
                                    <?php echo substr($group['leader_name'] ?? 'NA', 0, 1); ?>
                                </div>
                                <div>
                                    <p class="text-xs text-gray-400 uppercase tracking-wider font-bold mb-0.5">Team Leader</p>
                                    <p class="text-white font-semibold"><?php echo htmlspecialchars($group['leader_name'] ?? 'Not Assigned'); ?></p>
                                    <p class="text-xs text-gray-500 font-mono"><?php echo htmlspecialchars($group['leader_code'] ?? ''); ?></p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200 flex justify-between items-center bg-gray-50/50">
                    <h3 class="font-bold text-gray-800 flex items-center gap-2">
                        <i data-lucide="hard-hat" class="w-4 h-4 text-[#d17f1f]"></i>
                        Assigned Members
                    </h3>
                </div>
                
                <table class="w-full">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="text-center px-6 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Employee</th>
                            <th class="text-center px-6 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Role in Group</th>
                            <th class="text-center px-6 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Date Joined</th>
                            <th class="text-center px-6 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Status</th>
                            <th class="text-center px-6 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <?php if(empty($members)): ?>
                            <tr><td colspan="5" class="px-6 py-8 text-center text-gray-500">No members assigned to this group yet.</td></tr>
                        <?php else: ?>
                            <?php foreach($members as $m): 
                                $initials = substr($m['first_name'],0,1) . substr($m['last_name'],0,1);
                            ?>
                            <tr class="hover:bg-gray-50 transition-colors group">
                                <td class="px-6 py-3.5">
                                    <div class="flex items-center gap-3">
                                        <div class="w-9 h-9 rounded-full bg-gray-100 flex items-center justify-center text-xs font-bold text-gray-600 border border-gray-200">
                                            <?php echo $initials; ?>
                                        </div>
                                        <div>
                                            <p class="text-md font-semibold text-gray-900"><?php echo htmlspecialchars($m['first_name'] . ' ' . $m['last_name']); ?></p>
                                            <p class="text-xs text-gray-500 font-mono"><?php echo htmlspecialchars($m['employee_code']); ?></p>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-3.5 font-bold text-gray-600 text-center">
                                    <?php echo htmlspecialchars($m['role_in_group'] ?? $m['title_name']); ?>
                                </td>
                                <td class="px-6 py-3.5 font-bold text-gray-600 text-center">
                                    <?php echo date('M j, Y', strtotime($m['joined_date'])); ?>
                                </td>
                                <td class="px-6 py-3.5 font-bold text-gray-600 text-xs text-center">
                                    <span class="inline-flex items-center px-2 py-1 rounded font-bold 
                                        <?php echo $m['status'] === 'Active' ? 'bg-green-50 text-green-700' : 'bg-gray-100 text-gray-600'; ?>">
                                        <?php echo htmlspecialchars($m['status']); ?>
                                    </span>
                                </td>
                                <td class="px-6 py-3.5 text-center">
                                    <a href="employee_profile.php?id=<?php echo $m['employee_id']; ?>" class="text-gray-400 hover:text-[#d17f1f] transition-colors p-1 inline-flex items-center justify-center">
                                        <i data-lucide="external-link" class="w-4 h-4"></i>
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <?php endif; ?>
        </div>
    </main>

    <div id="groupModal" class="hidden fixed inset-0 z-50 overflow-hidden">
        <div class="flex items-center justify-center min-h-screen px-4">
            <div class="fixed inset-0 bg-gray-900/60 backdrop-blur-sm transition-opacity" onclick="closeGroupModal()"></div>
            
            <div class="relative bg-white rounded-2xl shadow-2xl max-w-3xl w-full flex flex-col max-h-[90vh] modal-content">
                <div class="bg-gradient-to-r from-gray-900 to-gray-800 px-6 py-5 rounded-t-2xl shrink-0 flex justify-between items-center">
                    <div class="flex items-center gap-3">
                        <div class="bg-white/10 p-2 rounded-lg"><i data-lucide="pencil" class="w-5 h-5 text-white"></i></div>
                        <div>
                            <h3 class="text-lg font-bold text-white">Edit Deployment Group</h3>
                            <p class="text-xs text-gray-400">Update details and membership</p>
                        </div>
                    </div>
                    <button onclick="closeGroupModal()" class="text-gray-400 hover:text-white transition-colors"><i data-lucide="x" class="w-5 h-5"></i></button>
                </div>

                <form id="groupForm" onsubmit="event.preventDefault(); saveGroup();" class="flex flex-col flex-1 overflow-hidden">
                    <input type="hidden" name="action" id="group_action" value="update">
                    <input type="hidden" name="group_id" id="group_id_input" value="<?php echo $group_id; ?>">
                    
                    <div class="flex-1 overflow-y-auto custom-scrollbar p-6">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div class="space-y-5">
                                <div>
                                    <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-2">Group Name</label>
                                    <input type="text" name="group_name" id="edit_group_name" required class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl focus:bg-white focus:ring-2 focus:ring-[#e9922c] focus:border-transparent outline-none transition-all font-medium">
                                </div>
                                <div>
                                    <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-2">Leader / Foreman</label>
                                    <select name="leader_id" id="edit_leader" class="w-full px-4 py-2.5 bg-white border border-gray-200 rounded-xl focus:ring-2 focus:ring-[#e9922c] focus:border-transparent outline-none">
                                        <option value="">Select Leader...</option>
                                        <?php foreach($leaders as $leader): ?>
                                            <option value="<?php echo $leader['employee_id']; ?>">
                                                <?php echo htmlspecialchars($leader['first_name'] . ' ' . $leader['last_name']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-2">Description</label>
                                    <textarea name="description" id="edit_description" rows="3" class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl focus:bg-white focus:ring-2 focus:ring-[#e9922c] focus:border-transparent outline-none resize-none"></textarea>
                                </div>
                            </div>

                            <div class="flex flex-col h-full bg-gray-50 rounded-xl border border-gray-200 overflow-hidden">
                                <div class="px-4 py-3 border-b border-gray-200 bg-white flex justify-between items-center">
                                    <h4 class="text-md font-bold text-gray-800">Add Members</h4>
                                    <span class="text-xs bg-gray-100 text-gray-600 px-2 py-1 rounded-md border border-gray-200" id="selectedCount">0 Selected</span>
                                </div>
                                <div class="p-3 border-b border-gray-200 bg-white">
                                    <input type="text" id="memberSearch" placeholder="Search employees..." class="w-full pl-3 pr-3 py-2 text-md bg-gray-50 border border-gray-200 rounded-lg focus:outline-none focus:border-gray-400">
                                </div>
                                <div class="flex-1 overflow-y-auto p-2 member-list-container space-y-1 h-[400px]" id="memberList">
                                    <?php foreach($all_employees as $e): ?>
                                    <label class="flex items-center gap-3 p-2 hover:bg-white rounded-lg cursor-pointer transition-colors border border-transparent hover:border-gray-100 group">
                                        <input type="checkbox" name="members[]" value="<?php echo $e['employee_id']; ?>" class="member-checkbox w-4 h-4 text-[#e9922c] border-gray-300 rounded focus:ring-[#e9922c]">
                                        <div class="flex-1">
                                            <p class="text-md font-medium text-gray-700"><?php echo htmlspecialchars($e['first_name'] . ' ' . $e['last_name']); ?></p>
                                            <p class="text-xs text-gray-500"><?php echo htmlspecialchars($e['title_name'] ?? 'No Position'); ?></p>
                                        </div>
                                    </label>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="px-6 py-4 bg-gray-50 border-t border-gray-200 flex justify-end gap-3 shrink-0">
                        <button type="button" onclick="closeGroupModal()" class="px-5 py-2.5 text-md font-medium text-gray-600 bg-white border border-gray-300 rounded-xl hover:bg-gray-100">Cancel</button>
                        <button type="submit" class="px-5 py-2.5 text-md font-bold text-white bg-[#e9922c] rounded-xl hover:bg-[#d17f1f] shadow-sm flex items-center gap-2">
                            <i data-lucide="check" class="w-4 h-4"></i> Save Changes
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="js/group_details.js"></script>
</body>
</html>