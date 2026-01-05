<?php
// Get the current file name (e.g., 'employees.php')
$currentPage = basename($_SERVER['PHP_SELF']);

// --- FIX: Auto-detect the section if it is not already defined ---
if (!isset($section)) {
    // defaults
    $section = ''; 

    // Logic to determine section based on the filename
    if (in_array($currentPage, ['employees.php', 'employee_groups.php'])) {
        $section = 'employees';
    } 
    elseif (in_array($currentPage, ['attendance.php', 'attendance_group.php'])) {
        $section = 'attendance';
    } 
    elseif (in_array($currentPage, ['assignments.php', 'group_assignments.php'])) {
        $section = 'assignments';
    } 
    elseif (in_array($currentPage, ['projects.php', 'phases.php', 'tasks.php'])) {
        $section = 'projects';
    } 
    elseif (in_array($currentPage, ['payroll.php', 'payroll_processing.php'])) {
        $section = 'payroll';
    }
}
?>

<?php if ($section === 'employees'): ?>
<div class="tabs">
    <a href="../employees/employees.php" class="tab <?= $currentPage === 'employees.php' ? 'active' : '' ?>">Employees</a>
    <a href="../employees/employee_groups.php" class="tab <?= $currentPage === 'employee_groups.php' ? 'active' : '' ?>">Employee Groups</a>
</div>
<?php endif; ?>

<?php if ($section === 'attendance'): ?>
<div class="tabs">
    <a href="../attendance/attendance.php" class="tab <?= $currentPage === 'attendance.php' ? 'active' : '' ?>">Individual Attendance</a>
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
    <a href="projects.php" class="tab <?= $currentPage === 'projects.php' ? 'active' : '' ?>">Projects</a>
    <a href="phases.php" class="tab <?= $currentPage === 'phases.php' ? 'active' : '' ?>">Phases</a>
    <a href="tasks.php" class="tab <?= $currentPage === 'tasks.php' ? 'active' : '' ?>">Tasks</a>
</div>
<?php endif; ?>

<?php if ($section === 'payroll'): ?>
<div class="tabs">
    <a href="payroll.php" class="tab <?= $currentPage === 'payroll.php' ? 'active' : '' ?>">Payroll Period</a>
    <a href="payroll_processing.php" class="tab <?= $currentPage === 'payroll_processing.php' ? 'active' : '' ?>">Employee Payroll</a>
</div>
<?php endif; ?>