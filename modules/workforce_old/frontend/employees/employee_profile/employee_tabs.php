<!-- profile-tabs.php -->
<div class="tabs flex flex-wrap gap-4 mb-6">
  <a href="employee_profile.php?id=<?= $employeeID ?>"
     class="tab <?= ($activeTab === 'personal') ? 'active' : '' ?>">
     Personal & Job Info
  </a>

  <a href="employee_assignments.php?id=<?= $employeeID ?>"
     class="tab <?= ($activeTab === 'assignments') ? 'active' : '' ?>">
     Assignment History
  </a>

  <a href="employee_attendance.php?id=<?= $employeeID ?>"
     class="tab <?= ($activeTab === 'attendance') ? 'active' : '' ?>">
     Attendance
  </a>

  <a href="employee_payroll.php?id=<?= $employeeID ?>"
     class="tab <?= ($activeTab === 'payroll') ? 'active' : '' ?>">
     Payroll
  </a>
</div>

