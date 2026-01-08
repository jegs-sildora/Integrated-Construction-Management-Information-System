<!DOCTYPE html>
<html lang="en">
<?php
  $pageTitle = "Employee Profile - Workforce Management";
  include '../components/head.php';
?>


<body>
  <section class="dashboard-container">

    <!-- Sidebar -->
    <?php include '../components/sidebar.php'; ?>

    <main class="main-content" role="main">
      <!-- Top Bar / Breadcrumbs -->
      <?php
        $title = "Employee Profile";
        $breadcrumbs = [
          ['label' => 'Labor & Workforce', 'link' => '../employees/employees.php'],
          ['label' => $title, 'link' => null]
        ];
        include '../components/top-bar.php';
      ?>

      <!-- ================= CONTENT WRAPPER ================= -->
      <section class="content-wrapper">

         <?php include 'employee_profile/employee_head.php'; ?>

        <?php include 'employee_profile/employee_tabs.php'; ?>


        <!-- ================= CONTENTS ================= -->
        
      

                <?php include 'employee_profile/assignments.php'; ?>


        <!-- Attendance -->
        <div id="attendance" class="tab-content section-card">
          <h3 class="section-title">Attendance Summary</h3>
          <div class="info-grid" id="attendanceSummary"></div>
        </div>

        <!-- Payroll -->
        <div id="payroll" class="tab-content section-card">
          <h3 class="section-title">Payroll Summary</h3>
          <div class="payroll-summary" id="payrollSummary"></div>
          <button class="btn-primary" id="generatePayrollBtn">Generate Payroll</button>
          <button class="btn-primary" id="downloadPayrollBtn" style="display:none;">Download Payroll</button>
        </div>

      </section>
      <!-- ================= CONTENT WRAPPER END ================= -->

      <?php include '../components/footer.php'; ?>
    </main>
  </section>


  <?php include '../components/scripts.php'; ?>
  <script>
    $(document).ready(function(){

      const backendUrl = "../../backend/employee/backend_employee.php";
      const employeeID = "<?php echo $_GET['id'] ?? 'EMP002'; ?>"; // Replace with actual ID

      // ---------- Load Employee Info ----------
      function loadEmployeeProfile(){
        $.getJSON(backendUrl, { fetch_id: employeeID }, res=>{
          if(!res.success) return alert("Error loading employee data");

          const emp = res.employee;
          $('#profileInitials').text((emp.first_name[0]+emp.last_name[0]).toUpperCase());
          $('#profileName').text(emp.first_name + " " + emp.last_name);
          $('#profilePosition').text(`${emp.position} | ${emp.employee_id} | ${emp.employment_type}`);
          $('#profileStatus').text(emp.status);
          $('#profileSkill').text(emp.skill_type);
          $('#profileStartDate').text(emp.start_date);
          $('#profileRate').text(emp.daily_rate ? '$'+emp.daily_rate : '$0');

          // Personal & Job Info
          $('#personalName').text(emp.first_name + " " + emp.last_name);
          $('#personalID').text(emp.employee_id);
          $('#personalEmail').text(emp.email);
          $('#personalContact').text(emp.contact_number);
          $('#personalAddress').text(emp.address);
          $('#personalEmergency').text(emp.emergency_contact);
          $('#jobPosition').text(emp.position);
          $('#jobType').text(emp.employment_type);
          $('#jobRate').text(emp.daily_rate ? '$'+emp.daily_rate+' / '+(emp.monthly_salary || 0) : '$0');
          $('#jobStartDate').text(emp.start_date);
          $('#jobStatus').text(emp.status);

          // Group Memberships
          let groupsHTML = emp.groups.map(g=>`<div>${g.name} (${g.role})</div>`).join('');
          $('#groupMembership').html(groupsHTML || 'No groups');

          // Assignment History
          let assignRows = '';
          emp.assignments.forEach(a=>{
            assignRows += `<tr>
              <td>${a.project}</td>
              <td>${a.task}</td>
              <td>${a.start_date}</td>
              <td>${a.end_date || '-'}</td>
              <td>${a.status}</td>
            </tr>`;
          });
          $('#assignmentTable tbody').html(assignRows);
          $('#assignmentTable').DataTable({ destroy:true, paging:true, pageLength:5, searching:false });

          // Attendance Summary
          let attendanceHTML = `
            <div class="info-item"><div class="info-label">Days Present</div><div class="info-value">${emp.attendance.present}</div></div>
            <div class="info-item"><div class="info-label">Days Absent</div><div class="info-value">${emp.attendance.absent}</div></div>
            <div class="info-item"><div class="info-label">Late Arrivals</div><div class="info-value">${emp.attendance.late}</div></div>
            <div class="info-item"><div class="info-label">Overtime Hours</div><div class="info-value">${emp.attendance.overtime}</div></div>
          `;
          $('#attendanceSummary').html(attendanceHTML);

          // Payroll Summary
          let payrollHTML = `
            <div class="payroll-item"><div class="payroll-label">Gross Pay</div><div class="payroll-value">$${emp.payroll.gross}</div></div>
            <div class="payroll-item"><div class="payroll-label">Tax Deduction</div><div class="payroll-value">-$${emp.payroll.tax}</div></div>
            <div class="payroll-item"><div class="payroll-label">SSS Contribution</div><div class="payroll-value">-$${emp.payroll.sss}</div></div>
            <div class="payroll-item"><div class="payroll-label">PhilHealth</div><div class="payroll-value">-$${emp.payroll.philhealth}</div></div>
            <div class="payroll-item total"><div class="payroll-label">Net Pay</div><div class="payroll-value">$${emp.payroll.net}</div></div>
          `;
          $('#payrollSummary').html(payrollHTML);
        });
      }

      loadEmployeeProfile();

      // ---------- Tab Switching ----------
      $('.tab').click(function(){
        const tabName = $(this).data('tab');
        $('.tab').removeClass('active');
        $(this).addClass('active');
        $('.tab-content').removeClass('active');
        $('#'+tabName).addClass('active');
      });

      // ---------- Generate Payroll ----------
      $('#generatePayrollBtn').click(function(){
        alert('Payroll generated successfully for this employee!');
        $('#downloadPayrollBtn').show();
      });

      // ---------- Download Payroll ----------
      $('#downloadPayrollBtn').click(function(){
        alert('Payroll download initiated!');
      });

      // ---------- Edit Modal ----------
      $('.edit-btn, .close-btn').click(function(){
        $('#editEmployeeModal').toggle();
      });

      $('#saveEmployeeBtn').click(function(){
        alert('Employee info saved!');
        $('#editEmployeeModal').hide();
        loadEmployeeProfile();
      });

          // ---------- Back Button ----------
    $('#backBtn').click(function(){
      window.history.back(); // goes back to previous page
    });


    });
  </script>
</body>
</html>
