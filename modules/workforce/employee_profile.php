<?php
/**
 * Employee Profile View
 * Displays full details of a specific employee in a read-only format via microservice.
 */
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../core/ApiHelper.php';

$id = $_GET['id'] ?? 0;
$employee = null;

if ($id) {
    // Fetch employee data via microservice
    $res = ApiHelper::get("workforce/employees?id=$id");
    if ($res['status'] === 200 && isset($res['data']['employee'])) {
        $employee = $res['data']['employee'];
        // Enhance with position info if not already there
        if (empty($employee['position_name']) && !empty($employee['job_title_id'])) {
            $res_opt = ApiHelper::get("workforce/form-options");
            if ($res_opt['status'] === 200) {
                foreach ($res_opt['data']['job_titles'] as $jt) {
                    if ($jt['job_title_id'] == $employee['job_title_id']) {
                        $employee['position_name'] = $jt['title_name'];
                        $employee['department'] = $jt['department'];
                        break;
                    }
                }
            }
        }
    }
}

// Helper for currency formatting
function formatMoney($val) {
    return '₱' . number_format((float)$val, 2);
}

// Helper for date formatting
function formatDate($date) {
    return $date ? date('F j, Y', strtotime($date)) : 'N/A';
}

// Header Variables
$pageSection = "Labor & Workforce";
$pageTitle = "Employee Profile";
$pageSubTitle = $employee ? htmlspecialchars($employee['first_name'] . ' ' . $employee['last_name']) : "Employee Details";
$userName = $_SESSION['user_name'] ?? "Admin";
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Employee Profile | ICMIS</title>
    
    <?php include __DIR__ . '/../../includes/head_assetsv2.php'; ?>
    <script src="https://unpkg.com/lucide@latest"></script>
    
    <style>
        * { font-family: 'Inter', sans-serif; }
    </style>
</head>
<body class="bg-gray-50">
    <?php 
        include __DIR__ . '/../../includes/sidebar.php';
        include __DIR__ . '/../../includes/toast.php';
        include __DIR__ . '/../../includes/header.php'; 
    ?>

    <main class="ml-56 mt-20 p-6 transition-all duration-300 animate-fade-in">
        <div class="max-w-7xl mx-auto">

            <?php if (!$employee): ?>
                <div class="flex flex-col items-center justify-center h-[60vh]">
                    <div class="bg-white p-8 rounded-2xl shadow-sm border border-gray-200 text-center max-w-md">
                        <div class="w-16 h-16 bg-red-100 text-red-500 rounded-full flex items-center justify-center mx-auto mb-4">
                            <i data-lucide="user-x" class="w-8 h-8"></i>
                        </div>
                        <h2 class="text-2xl font-bold text-gray-800 mb-2">Employee Not Found</h2>
                        <p class="text-gray-500 mb-6">The employee with ID #<?php echo htmlspecialchars($id); ?> does not exist or has been removed.</p>
                        <a href="employees.php" class="px-6 py-3 bg-[#d17f1f] text-white rounded-xl font-bold hover:bg-[#b56b17] transition-all inline-flex items-center gap-2">
                            <i data-lucide="arrow-left" class="w-4 h-4"></i>
                            Back to Directory
                        </a>
                    </div>
                </div>
            <?php else: ?>

            <div class="mb-6 flex items-center justify-between">
                <a href="employees.php" class="flex items-center gap-2 text-gray-500 hover:text-[#d17f1f] transition-colors font-medium">
                    <i data-lucide="arrow-left" class="w-5 h-5"></i> Back to Employee Directory
                </a>
            </div>

            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden mb-6">
                <div class="bg-[#d17f1f] px-8 py-8">
                    <div class="flex flex-col md:flex-row items-center gap-6">
                        <div class="w-24 h-24 rounded-2xl bg-white/20 backdrop-blur-md flex items-center justify-center shadow-inner border border-white/20 text-white text-3xl font-bold">
                            <?php echo substr($employee['first_name'], 0, 1) . substr($employee['last_name'], 0, 1); ?>
                        </div>
                        
                        <div class="text-center md:text-left flex-1">
                            <div class="flex flex-col md:flex-row md:items-center gap-3 mb-1">
                                <h1 class="text-3xl font-bold text-white tracking-tight">
                                    <?php echo htmlspecialchars($employee['first_name'] . ' ' . $employee['last_name'] . ' ' . ($employee['suffix'] ?? '')); ?>
                                </h1>
                                <?php 
                                    $statusColors = [
                                        'Active' => 'bg-green-500/20 text-green-50 border-green-400/30',
                                        'Inactive' => 'bg-gray-500/20 text-gray-50 border-gray-400/30',
                                        'Terminated' => 'bg-red-500/20 text-red-50 border-red-400/30',
                                    ];
                                    $badgeClass = $statusColors[$employee['status']] ?? $statusColors['Inactive'];
                                ?>
                                <span class="px-3 py-1 rounded-full text-sm font-semibold border backdrop-blur-sm w-fit mx-auto md:mx-0 <?php echo $badgeClass; ?>">
                                    <?php echo htmlspecialchars($employee['status']); ?>
                                </span>
                            </div>
                            <p class="text-white font-bold text-lg flex items-center justify-center md:justify-start gap-2">
                                <i data-lucide="briefcase" class="w-4 h-4 opacity-75"></i>
                                <?php echo htmlspecialchars($employee['position_name'] ?? 'No Position'); ?> 
                                <span class="opacity-60">•</span> 
                                <?php echo htmlspecialchars($employee['department'] ?? 'No Department'); ?>
                            </p>
                            <p class="text-white text-sm mt-1 font-bold">ID: <?php echo htmlspecialchars($employee['employee_code']); ?></p>
                        </div>

                        <div class="flex gap-3">
                            <?php if(!empty($employee['phone'])): ?>
                            <a href="tel:<?php echo $employee['phone']; ?>" class="p-3 bg-white/10 hover:bg-white/20 rounded-xl transition-colors text-white" title="Call">
                                <i data-lucide="phone" class="w-5 h-5"></i>
                            </a>
                            <?php endif; ?>
                            <?php if(!empty($employee['email'])): ?>
                            <a href="mailto:<?php echo $employee['email']; ?>" class="p-3 bg-white/10 hover:bg-white/20 rounded-xl transition-colors text-white" title="Email">
                                <i data-lucide="mail" class="w-5 h-5"></i>
                            </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                
                <div class="space-y-6">
                    <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100">
                        <div class="flex items-center gap-2 mb-4 border-b border-gray-100 pb-3">
                            <div class="p-2 bg-orange-50 text-[#d17f1f] rounded-lg">
                                <i data-lucide="user" class="w-5 h-5"></i>
                            </div>
                            <h4 class="text-lg font-bold text-gray-800">Personal Details</h4>
                        </div>
                        
                        <div class="space-y-4">
                            <div>
                                <label class="block text-xs font-bold text-gray-400 uppercase tracking-wider mb-1">Gender</label>
                                <p class="text-gray-700 font-medium"><?php echo htmlspecialchars($employee['gender'] ?? 'Not set'); ?></p>
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-gray-400 uppercase tracking-wider mb-1">Birthday</label>
                                <p class="text-gray-700 font-medium flex items-center gap-2">
                                    <i data-lucide="cake" class="w-4 h-4 text-gray-400"></i>
                                    <?php echo formatDate($employee['birthday']); ?>
                                </p>
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-gray-400 uppercase tracking-wider mb-1">Address</label>
                                <p class="text-gray-700 font-medium leading-relaxed">
                                    <?php echo nl2br(htmlspecialchars($employee['address'] ?? 'N/A')); ?>
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100">
                        <div class="flex items-center gap-2 mb-4 border-b border-gray-100 pb-3">
                            <div class="p-2 bg-red-50 text-red-500 rounded-lg">
                                <i data-lucide="heart-pulse" class="w-5 h-5"></i>
                            </div>
                            <h4 class="text-lg font-bold text-gray-800">Emergency Contact</h4>
                        </div>
                        
                        <div class="space-y-4">
                            <div>
                                <label class="block text-xs font-bold text-gray-400 uppercase tracking-wider mb-1">Contact Name</label>
                                <p class="text-gray-700 font-bold"><?php echo htmlspecialchars($employee['emergency_contact_name'] ?? 'N/A'); ?></p>
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-gray-400 uppercase tracking-wider mb-1">Phone Number</label>
                                <p class="text-gray-700 font-medium flex items-center gap-2">
                                    <i data-lucide="phone-call" class="w-4 h-4 text-gray-400"></i>
                                    <?php echo htmlspecialchars($employee['emergency_contact_phone'] ?? 'N/A'); ?>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="space-y-6">
                    <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100 h-full">
                        <div class="flex items-center gap-2 mb-4 border-b border-gray-100 pb-3">
                            <div class="p-2 bg-blue-50 text-blue-600 rounded-lg">
                                <i data-lucide="briefcase" class="w-5 h-5"></i>
                            </div>
                            <h4 class="text-lg font-bold text-gray-800">Employment Info</h4>
                        </div>

                        <div class="grid grid-cols-1 gap-5">
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-xs font-bold text-gray-400 uppercase tracking-wider mb-1">Date Hired</label>
                                    <p class="text-gray-700 font-medium"><?php echo formatDate($employee['hire_date']); ?></p>
                                </div>
                                <div>
                                    <label class="block text-xs font-bold text-gray-400 uppercase tracking-wider mb-1">Tenure</label>
                                    <p class="text-gray-700 font-medium">
                                        <?php 
                                            if (!empty($employee['hire_date'])) {
                                                $diff = date_diff(date_create($employee['hire_date']), date_create('today'));
                                                echo $diff->y . ' yrs, ' . $diff->m . ' mos';
                                            } else { echo 'N/A'; }
                                        ?>
                                    </p>
                                </div>
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-gray-400 uppercase tracking-wider mb-1">Employment Type</label>
                                <span class="inline-flex items-center gap-1.5 px-3 py-1 bg-blue-50 text-blue-700 rounded-lg text-sm font-semibold">
                                    <i data-lucide="clock" class="w-3.5 h-3.5"></i>
                                    <?php echo htmlspecialchars($employee['employment_type'] ?? 'N/A'); ?>
                                </span>
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-gray-400 uppercase tracking-wider mb-1">Payment Basis</label>
                                <p class="text-gray-700 font-medium"><?php echo htmlspecialchars($employee['payment_type'] ?? 'N/A'); ?></p>
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-gray-400 uppercase tracking-wider mb-1">Supervisor</label>
                                <p class="text-gray-700 font-medium flex items-center gap-2">
                                    <i data-lucide="user-check" class="w-4 h-4 text-gray-400"></i>
                                    <?php echo !empty($employee['supervisor_id']) ? 'ID #' . $employee['supervisor_id'] : 'None'; ?>
                                </p>
                            </div>

                            <div class="pt-4 border-t border-gray-100">
                                <label class="block text-xs font-bold text-gray-400 uppercase tracking-wider mb-2">Internal Notes</label>
                                <div class="bg-gray-50 p-4 rounded-xl text-sm text-gray-600 italic">
                                    <?php echo !empty($employee['notes']) ? htmlspecialchars($employee['notes']) : 'No notes available.'; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="space-y-6">
                    <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100">
                        <div class="flex items-center gap-2 mb-4 border-b border-gray-100 pb-3">
                            <div class="p-2 bg-green-50 text-green-600 rounded-lg">
                                <i data-lucide="banknote" class="w-5 h-5"></i>
                            </div>
                            <h4 class="text-lg font-bold text-gray-800">Compensation</h4>
                        </div>

                        <div class="space-y-5">
                            <div class="bg-gradient-to-br from-green-50 to-emerald-50 p-4 rounded-xl border border-green-100">
                                <label class="block text-xs font-bold text-green-700 uppercase tracking-wider mb-1">Monthly Salary</label>
                                <p class="text-2xl font-bold text-green-800"><?php echo formatMoney($employee['monthly_salary'] ?? 0); ?></p>
                            </div>

                            <div class="flex items-center justify-between p-3 bg-gray-50 rounded-xl">
                                <label class="text-sm font-semibold text-gray-600">Daily Rate</label>
                                <p class="text-lg font-bold text-gray-800"><?php echo formatMoney($employee['daily_rate'] ?? 0); ?></p>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100">
                        <div class="flex items-center gap-2 mb-4 border-b border-gray-100 pb-3">
                            <div class="p-2 bg-purple-50 text-purple-600 rounded-lg">
                                <i data-lucide="landmark" class="w-5 h-5"></i>
                            </div>
                            <h4 class="text-lg font-bold text-gray-800">Banking Details</h4>
                        </div>

                        <div class="space-y-4">
                            <div>
                                <label class="block text-xs font-bold text-gray-400 uppercase tracking-wider mb-1">Bank Name</label>
                                <p class="text-gray-700 font-bold text-lg"><?php echo htmlspecialchars($employee['bank_name'] ?? 'N/A'); ?></p>
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-gray-400 uppercase tracking-wider mb-1">Account Number</label>
                                <div class="flex items-center gap-2 font-mono text-gray-700 bg-gray-50 px-3 py-2 rounded-lg border border-gray-200">
                                    <i data-lucide="credit-card" class="w-4 h-4 text-gray-400"></i>
                                    <?php echo htmlspecialchars($employee['bank_account'] ?? 'N/A'); ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <?php endif; ?>
        </div>
    </main>

    <script>
        // Initialize Icons
        lucide.createIcons();

        function openEditModal(id) {
            if(confirm("To edit this profile, you will be redirected to the main directory. Continue?")) {
                window.location.href = 'employees.php'; 
            }
        }
    </script>
</body>
</html>