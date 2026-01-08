<?php
$currentPage = basename($_SERVER['PHP_SELF']);
?>

<?php if ($section === 'employees'): ?>
<div class="tabs">
    <a href="employees.php" class="tab <?= $currentPage === 'employees.php' ? 'active' : '' ?>">Employees</a>
    <a href="employee_groups.php" class="tab <?= $currentPage === 'employee_groups.php' ? 'active' : '' ?>">Employee Groups</a>
</div>
<?php endif; ?>

<?php if ($section === 'attendance'): ?>
<div class="tabs">
      <a href="attendance.php" class="tab <?= $currentPage === 'attendance.php' ? 'active' : '' ?>">Individual Attendance</a>
    <a href="attendance_group.php" class="tab <?= $currentPage === 'attendance_group.php' ? 'active' : '' ?>">Group Attendance</a>
</div>
<?php endif; ?>


<?php if ($section === 'assignments'): ?>
<div class="tabs">
   <a href="assignments.php" class="tab <?= $currentPage === 'assignments.php' ? 'active' : '' ?>">Assignments</a>
    <a href="group_assignments.php" class="tab <?= $currentPage === 'group_assignments.php' ? 'active' : '' ?>">Group Assignments</a>
</div>
<?php endif; ?>



<?php if ($section === 'projects'): ?>
<div class="tabs">
    <a href="projects.php"
       class="tab <?= $currentPage === 'projects.php' ? 'active' : '' ?>">
        Projects
    </a>

    <a href="phases.php"
       class="tab <?= $currentPage === 'phases.php' ? 'active' : '' ?>">
        Phases
    </a>

    <a href="tasks.php"
       class="tab <?= $currentPage === 'tasks.php' ? 'active' : '' ?>">
        Tasks
    </a>
</div>
<?php endif; ?>


<?php if ($section === 'payroll'): ?>
<div class="tabs">
    <a href="payroll.php"
       class="tab <?= $currentPage === 'payroll.php' ? 'active' : '' ?>">
        Payroll Period
    </a>

    <a href="payroll_processing.php"
       class="tab <?= $currentPage === 'payroll_processing.php' ? 'active' : '' ?>">
        Employee Payroll
    </a>
</div>

<?php endif; ?>



<?php if ($section === 'reports'): ?>
<div class="tabs">

    <a href="employee_reports.php"
       class="tab <?= $currentPage === 'employee_reports.php' ? 'active' : '' ?>">
        Employee Reports
    </a>

    <a href="attendance_reports.php"
       class="tab <?= $currentPage === 'attendance_reports.php' ? 'active' : '' ?>">
        Attendance Reports
    </a>

    <a href="assignments_reports.php"
       class="tab <?= $currentPage === 'assignments_reports.php' ? 'active' : '' ?>">
        Assignment Reports
    </a>

    <a href="payroll_reports.php"
       class="tab <?= $currentPage === 'payroll_reports.php' ? 'active' : '' ?>">
        Payroll Reports
    </a>
</div>

<?php endif; ?>


