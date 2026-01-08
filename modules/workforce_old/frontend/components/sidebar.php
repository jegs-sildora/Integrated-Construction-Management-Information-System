<?php
$currentPage = basename($_SERVER['PHP_SELF']);

$menuItems = [
    [
        'name' => 'Dashboard',
        'icon' => 'fa-solid fa-house',
        'link' => '../dashboard/dashboard.php'
    ],
    [
        'name' => 'Project Management',
        'icon' => 'fa-regular fa-folder-open',
        'link' => '../projects/projects.php'
    ],
    [
        'name' => 'Budgeting & Cost Control',
        'icon' => 'fa-solid fa-wallet',
        'submenu' => [
            ['name' => 'Budget Proposals', 'link' => '../budget/budget-proposals.php']
        ]
    ],
    [
        'name' => 'Procurement',
        'icon' => 'fa-solid fa-cart-shopping',
        'submenu' => [
            ['name' => 'Inventory Masterlist', 'link' => '../procurement/inventory.php'],
            ['name' => 'Purchase Orders', 'link' => '../procurement/orders.php'],
            ['name' => 'Stock In', 'link' => '../procurement/stock-in.php'],
            ['name' => 'Stock Out', 'link' => '../procurement/stock-out.php'],
            ['name' => 'Suppliers', 'link' => '../procurement/suppliers.php']
        ]
    ],
    [
        'name' => 'Labor & Workforce',
        'icon' => 'fa-solid fa-users-gear',
        'submenu' => [
            ['name' => 'Dashboard', 'link' => '../dashboard/dashboard.php'],
            ['name' => 'Employees', 'link' => '../employees/employees.php'],
            // Removed Employees Groups link from submenu for visibility
            ['name' => 'Attendance', 'link' => '../attendance/attendance.php'],
            ['name' => 'Assignments', 'link' => '../assignments/assignments.php'],
            ['name' => 'Projects', 'link' => '../projects/projects.php'],
            ['name' => 'Payroll', 'link' => '../payroll/payroll.php'],
            ['name' => 'Reports', 'link' => '../reports/employee_reports.php']
        ]
    ],
    [
        'name' => 'System Admin',
        'icon' => 'fa-solid fa-gear',
        'link' => '../admin/system-admin.php'
    ]
];
?>

<aside class="sidebar">
    <div class="logo">
        <div class="logo-icon">I</div>
        <span>ICMIS</span>
    </div>

    <nav class="menu">
    <?php foreach ($menuItems as $item): ?>
        <?php if (isset($item['submenu'])): 
            $isActive = false;
            // Check if any submenu is active
            foreach ($item['submenu'] as $sub) {
                // Highlight submenu items based on the current page
                if ($currentPage === basename($sub['link']) || 
                    ($currentPage === 'employee_groups.php' && $sub['name'] === 'Employees') || 
                    ($currentPage === 'employee_assignments.php' && $sub['name'] === 'Employees') || 
                    ($currentPage === 'employee_profile.php' && $sub['name'] === 'Employees') || 
                    ($currentPage === 'employee_attendance.php' && $sub['name'] === 'Employees') || 
                     ($currentPage === 'employee_payroll.php' && $sub['name'] === 'Employees') || 
                    ($currentPage === 'group_assignments.php' && $sub['name'] === 'Assignments')|| 
                     ($currentPage === 'phases.php' && $sub['name'] === 'Projects') ||
                     ($currentPage === 'payroll_processing.php' && $sub['name'] === 'Payroll') ||
                     ($currentPage === 'payslips.php' && $sub['name'] === 'Payroll')||

                      ($currentPage === 'employee_reports.php' && $sub['name'] === 'Reports')||
                      ($currentPage === 'attendance_reports.php' && $sub['name'] === 'Reports')||
                      ($currentPage === 'assignments_reports.php' && $sub['name'] === 'Reports')||
                       ($currentPage === 'payroll_reports.php' && $sub['name'] === 'Reports')
                     ) {
                    $isActive = true;
                }
            }
        ?>
            <details <?= $isActive ? 'open' : '' ?>>
                <summary class="menu-item <?= $isActive ? 'active' : '' ?>">
                    <div class="menu-label">
                        <i class="<?= $item['icon'] ?>"></i>
                        <span><?= $item['name'] ?></span>
                    </div>
                </summary>
                <div class="submenu">
                    <?php foreach ($item['submenu'] as $sub): 
                        // Check if the current page matches the submenu link, or if it's employees_groups.php, highlight Employees
                        $subActive = ($currentPage === basename($sub['link']) || 
                                      ($currentPage === 'employee_groups.php' && $sub['name'] === 'Employees') || 
                                      ($currentPage === 'employee_assignments.php' && $sub['name'] === 'Employees') || 
                                        ($currentPage === 'employee_profile.php' && $sub['name'] === 'Employees') || 
                                        ($currentPage === 'employee_attendance.php' && $sub['name'] === 'Employees') || 
                                        ($currentPage === 'employee_payroll.php' && $sub['name'] === 'Employees') || 
                                      ($currentPage === 'group_assignments.php' && $sub['name'] === 'Assignments')|| 
                                      ($currentPage === 'phases.php' && $sub['name'] === 'Projects') ||
                                      ($currentPage === 'payroll_processing.php' && $sub['name'] === 'Payroll') ||
                                      ($currentPage === 'payslips.php' && $sub['name'] === 'Payroll') ||

                                    ($currentPage === 'employee_reports.php' && $sub['name'] === 'Reports')||
                                    ($currentPage === 'attendance_reports.php' && $sub['name'] === 'Reports')||
                                    ($currentPage === 'assignments_reports.php' && $sub['name'] === 'Reports')||
                                    ($currentPage === 'payroll_reports.php' && $sub['name'] === 'Reports')
                                      ) ? 'active' : '';
                    ?>
                        <a href="<?= $sub['link'] ?>" class="submenu-item <?= $subActive ?>"><?= $sub['name'] ?></a>
                    <?php endforeach; ?>
                </div>
            </details>
        <?php else: 
            $active = ($currentPage === basename($item['link'])) ? 'active' : '';
        ?>
            <a href="<?= $item['link'] ?>" class="menu-item <?= $active ?>">
                <div class="menu-label">
                    <i class="<?= $item['icon'] ?>"></i>
                    <span><?= $item['name'] ?></span>
                </div>
            </a>
        <?php endif; ?>
    <?php endforeach; ?>

</nav>
</aside>


