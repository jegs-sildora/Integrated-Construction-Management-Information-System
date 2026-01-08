<!DOCTYPE html>
<html lang="en">
<?php
  $pageTitle = "Employee Profile - Workforce Management";
  include '../components/head.php';
?>

<style>
  /* ====================== Section Cards ====================== */
.section-card {
  background-color: #ffffff;
  border-radius: 12px;
  box-shadow: 0 6px 18px rgba(0, 0, 0, 0.06);
  padding: 25px 30px;
  margin-bottom: 30px;
  transition: transform 0.15s ease, box-shadow 0.15s ease;
}

.section-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 8px 22px rgba(0, 0, 0, 0.09);
}

/* ====================== Section Titles ====================== */
.section-title {
  font-size: 22px;
  font-weight: 600;
  color: #111827;
  margin-bottom: 20px;
  border-bottom: 2px solid #f3f4f6;
  padding-bottom: 10px;
}

/* ====================== Info Grid ====================== */
.info-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 16px 40px; /* vertical and horizontal gap */
}

/* ====================== Info Items ====================== */
.info-item {
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.info-label {
  font-size: 13px;
  font-weight: 500;
  color: #6b7280;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.info-value {
  font-size: 16px;
  font-weight: 300;
  color: #111827;
  background-color: transparent;
  padding: 0;
  border: none;
  border-radius: 0;
}

/* subtle separator between items (optional) */
.info-item:not(:last-child) {
  border-bottom: 1px solid #f3f4f6;
  padding-bottom: 10px;
  margin-bottom: 10px;
}

/* ====================== Status ====================== */
#jobStatus {
  color: #059669; /* green for active */
}

#jobStatus.inactive {
  color: #ef4444; /* red for inactive */
}

/* ====================== Group Membership Pills ====================== */
#groupMembership {
  display: flex;
  flex-wrap: wrap;
  gap: 10px;
  padding-top: 5px;
}

#groupMembership div {
  background-color: #e0f2fe;
  color: #0369a1;
  padding: 5px 14px;
  border-radius: 20px;
  font-size: 13px;
  font-weight: 600;
  white-space: nowrap;
  transition: all 0.2s;
}

#groupMembership div:hover {
  background-color: #bae6fd;
  color: #0c4a6e;
}

.tab-content {

  border: none;
}

/* ====================== Responsive ====================== */
@media (max-width: 768px) {
  .info-grid {
    grid-template-columns: 1fr;
    gap: 14px 0;
  }

  .section-title {
    font-size: 20px;
  }

  .info-value {
    font-size: 15px;
  }
}
</style>
<body>
  <section class="dashboard-container">

    <!-- Sidebar -->
    <?php include '../components/sidebar.php'; ?>

    <main class="main-content" role="main">
      <!-- Top Bar / Breadcrumbs -->
      <?php
        $title = "Employee Profile";
        $breadcrumbs = [
          ['label' => 'Labor & Workforce > Employees', 'link' => null],
          ['label' => $title, 'link' => null]
        ];
        include '../components/top-bar.php';
      ?>

      <!-- ================= CONTENT WRAPPER ================= -->
      <section class="content-wrapper">

         <?php include 'employee_profile/employee_header.php'; ?>

        <?php 
          $activeTab = 'personal';
          $employeeID = $_GET['id'] ?? '';
          include 'employee_profile/employee_tabs.php';
        ?>
     


        <!-- ================= CONTENTS ================= -->
<div id="personal" class="tab-content active">

  <!-- Personal Information Card -->
  <div class="section-card">
    <h3 class="section-title">Personal Information</h3>
    <div class="info-grid">
      <div class="info-item">
        <div class="info-label">Full Name</div>
        <div class="info-value" id="personalName">N/A</div>
      </div>
      <div class="info-item">
        <div class="info-label">Suffix</div>
        <div class="info-value" id="personalSuffix">N/A</div>
      </div>
      <div class="info-item">
        <div class="info-label">Employee ID</div>
        <div class="info-value" id="personalID">N/A</div>
      </div>
      <div class="info-item">
        <div class="info-label">Gender</div>
        <div class="info-value" id="personalGender">N/A</div>
      </div>
      <div class="info-item">
        <div class="info-label">Age</div>
        <div class="info-value" id="personalAge">N/A</div>
      </div>
      <div class="info-item">
        <div class="info-label">Email</div>
        <div class="info-value" id="personalEmail">N/A</div>
      </div>
      <div class="info-item">
        <div class="info-label">Contact Number</div>
        <div class="info-value" id="personalContact">N/A</div>
      </div>
      <div class="info-item">
        <div class="info-label">Address</div>
        <div class="info-value" id="personalAddress">N/A</div>
      </div>
      <div class="info-item">
        <div class="info-label">Emergency Contact Name</div>
        <div class="info-value" id="personalEmergencyName">N/A</div>
      </div>
      <div class="info-item">
        <div class="info-label">Emergency Contact Phone</div>
        <div class="info-value" id="personalEmergencyPhone">N/A</div>
      </div>
   
    </div>
  </div>

  <!-- Job Information Card -->
  <div class="section-card">
    <h3 class="section-title">Job Information</h3>
    <div class="info-grid">
      <div class="info-item">
        <div class="info-label">Position / Role</div>
        <div class="info-value" id="jobPosition">N/A</div>
      </div>
      <div class="info-item">
        <div class="info-label">Skill Type</div>
        <div class="info-value" id="profileSkill">N/A</div>
      </div>
      <div class="info-item">
        <div class="info-label">Employment Type</div>
        <div class="info-value" id="jobType">N/A</div>
      </div>
      <div class="info-item">
        <div class="info-label">Payment Type</div>
        <div class="info-value" id="jobPaymentType">N/A</div>
      </div>
      <div class="info-item">
        <div class="info-label">Daily Rate</div>
        <div class="info-value" id="jobDailyRate">N/A</div>
      </div>
      <div class="info-item">
        <div class="info-label">Monthly Salary</div>
        <div class="info-value" id="jobMonthlySalary">N/A</div>
      </div>
      <div class="info-item">
        <div class="info-label">Start Date</div>
        <div class="info-value" id="jobStartDate">N/A</div>
      </div>
      <div class="info-item">
        <div class="info-label">End Date</div>
        <div class="info-value" id="jobEndDate">N/A</div>
      </div>
      <div class="info-item">
        <div class="info-label">Status</div>
        <div class="info-value" id="jobStatus">N/A</div>
      </div>
    </div>
  </div>

  <!-- Group Membership Card -->
  <div class="section-card">
    <h3 class="section-title">Group Membership</h3>
    <div class="info-grid">
      <div class="info-item">
        <div class="info-label">Groups</div>
        <div class="info-value" id="groupMembership">
          Does not Belong to a Group
        </div>
      </div>
    </div>
  </div>

</div>

          


</section>
      <!-- ================= CONTENT WRAPPER END ================= -->

      <?php include '../components/footer.php'; ?>
    </main>
  </section>

  <?php include 'modal.php'; ?>

  <?php include '../components/scripts.php'; ?>
  <script src="employee_profile/employee_script.js"></script>

<script>


 new EmployeeHeader();
(function ($, window) {

  function EmployeeModule() {
    // -------------------- Internal Variables --------------------
    const backendUrl = "../../backend/employee/backend_employee.php";
    const employeeID = $('body').data('employee-id') || new URLSearchParams(window.location.search).get('id');

    if (!employeeID) {
      console.error("Employee ID not found!");
      return;
    }

    // -------------------- Load Employee Profile --------------------
    function loadEmployeeProfile() {
      $.getJSON(backendUrl, { fetch_id: employeeID })
        .done(res => {
          if (!res.success) {
            console.error("Error loading employee data:", res.message);
            return;
          }

          const emp = res.employee;

          // ---------------- Personal Info ----------------
          $('#personalName').text(`${emp.first_name} ${emp.last_name}`);
          $('#personalSuffix').text(emp.suffix || '-');
          $('#personalID').text(emp.employee_id);
          $('#personalGender').text(emp.gender || '-');
          $('#personalAge').text(emp.age || '-');
          $('#personalEmail').text(emp.email || '-');
          $('#personalContact').text(emp.phone || '-');
          $('#personalAddress').text(emp.address || '-');
          $('#personalEmergencyName').text(emp.emergency_contact_name || '-');
          $('#personalEmergencyPhone').text(emp.emergency_contact_phone || '-');

          // ---------------- Job Info ----------------
          $('#jobPosition').text(emp.position || '-');
          $('#profileSkill').text(emp.skill_type || '-');
          $('#jobType').text(emp.employment_type || '-');
          $('#jobPaymentType').text(emp.payment_type || '-');
          $('#jobDailyRate').text(emp.daily_rate ? `$${emp.daily_rate}` : '-');
          $('#jobMonthlySalary').text(emp.monthly_salary ? `$${emp.monthly_salary}` : '-');
          $('#jobStartDate').text(emp.start_date || '-');
          $('#jobEndDate').text(emp.end_date || '-');
          $('#jobStatus').text(emp.status || '-');

          // Status color
          $('#jobStatus').removeClass('inactive');
          if (emp.status.toLowerCase() !== 'active') $('#jobStatus').addClass('inactive');

          // ---------------- Groups ----------------
          let groupsHTML = 'Does not belong to a Group';
          if (Array.isArray(emp.groups) && emp.groups.length) {
            groupsHTML = emp.groups.map(g => `<div>${g.group_name}</div>`).join('');
          }
          $('#groupMembership').html(groupsHTML);

        })
        .fail(() => {
          console.error("AJAX error loading employee profile.");
        });
    }

    loadEmployeeProfile();
  }

  // Expose globally
  window.EmployeeModule = EmployeeModule;

})(jQuery, window);

// -------------------- Initialize Module --------------------
$(document).ready(function () {
  new EmployeeModule();
});
</script>



</body>
</html>
