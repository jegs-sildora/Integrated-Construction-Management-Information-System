<?php
$currentPage = basename($_SERVER['PHP_SELF']);

$menuItems = [
    [
        'name' => 'Dashboard',
        'icon' => 'fa-solid fa-house',
        'link' => '/ICMIS/Project_Management/dashboards.html'
    ],
    [
        'name' => 'Project Management',
        'icon' => 'fa-regular fa-folder-open',
        'submenu' => [
            ['name' => 'Projects', 'link' => '../projects/projects.php'],
            ['name' => 'Schedule', 'link' => '/ICMIS/Project_Management/schedule.html'],
        ]
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
            ['name' => 'Inventory Masterlist', 'link' => '/ICMIS/Procurement_and_Inventory/inventory.html'],
            ['name' => 'Purchase Orders', 'link' => '/ICMIS/Procurement_and_Inventory/orders.html'],
            ['name' => 'Stock In', 'link' => '/ICMIS/Procurement_and_Inventory/stock-in.html'],
            ['name' => 'Stock Out', 'link' => '/ICMIS/Procurement_and_Inventory/stock-out.html'],
            ['name' => 'Suppliers', 'link' => '/ICMIS/Procurement_and_Inventory/suppliers.html']
        ]
    ],
    [
        'name' => 'Labor & Workforce',
        'icon' => 'fa-solid fa-users-gear',
        'submenu' => [
            ['name' => 'Dashboard', 'link' => '../dashboard/dashboard.php'],
            ['name' => 'Employees', 'link' => '../employees/employees.php'],
            ['name' => 'Attendance', 'link' => '../attendance/attendance.php'],
            ['name' => 'Assignments', 'link' => '../assignments/assignments.php'],
            ['name' => 'Payroll', 'link' => '../payroll/payroll.php'],
            ['name' => 'Reports', 'link' => '../reports/reports.html']
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
            
            foreach ($item['submenu'] as $sub) {
        
                if ($currentPage === basename($sub['link']) || 
                    ($currentPage === 'employee_groups.php' && $sub['name'] === 'Employees') || 
                    ($currentPage === 'group_assignments.php' && $sub['name'] === 'Assignments')|| 
                     ($currentPage === 'phases.php' && $sub['name'] === 'Projects') ||
                     ($currentPage === 'payroll_processing.php' && $sub['name'] === 'Payroll')
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
                     
                        $subActive = ($currentPage === basename($sub['link']) || 
                                      ($currentPage === 'employee_groups.php' && $sub['name'] === 'Employees') || 
                                      ($currentPage === 'group_assignments.php' && $sub['name'] === 'Assignments')|| 
                                      ($currentPage === 'phases.php' && $sub['name'] === 'Projects') ||
                                      ($currentPage === 'payroll_processing.php' && $sub['name'] === 'Payroll')
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


