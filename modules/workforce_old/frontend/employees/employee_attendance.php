<!DOCTYPE html>
<html lang="en">
<?php
  $pageTitle = "Employee Attendance - Workforce Management";
  include '../components/head.php';
?>

<body>
  <section class="dashboard-container">

    <!-- Sidebar -->
    <?php include '../components/sidebar.php'; ?>

    <main class="main-content" role="main">
      <!-- Top Bar / Breadcrumbs -->
      <?php
        $title = "Employee Attendance";
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
          $activeTab = 'attendance';
          $employeeID = $_GET['id'] ?? '';
          include 'employee_profile/employee_tabs.php';
        ?>

        <!-- ================= Attendance Stats Cards ================= -->
        <section class="dashboard-grid" style="margin-bottom: 30px;">
          <div class="stat-card">
            <h3><i class="fa-solid fa-calendar-check stat-icon"></i> Days Present</h3>
            <h2 id="daysPresent">0</h2>
            <p>Total days present</p>
          </div>
          <div class="stat-card">
            <h3><i class="fa-solid fa-calendar-xmark stat-icon"></i> Days Absent</h3>
            <h2 id="daysAbsent">0</h2>
            <p>Total days absent</p>
          </div>
          <div class="stat-card">
            <h3><i class="fa-solid fa-clock stat-icon"></i> Late Arrivals</h3>
            <h2 id="lateArrivals">0</h2>
            <p>Times late</p>
          </div>
          <div class="stat-card">
            <h3><i class="fa-solid fa-hourglass-half stat-icon"></i> Overtime Hours</h3>
            <h2 id="overtimeHours">0</h2>
            <p>Total overtime hours</p>
          </div>
        </section>


          <!-- ================= ATTENDANCE DETAIL TABLE ================= -->
        <section class="table-card section-card">
          <table id="attendanceTable" class="styled-table">
            <thead>
              <tr>
                <th>Date</th>
                <th>Hours Worked</th>
                <th>Status</th>
                <th>Remarks</th>
              </tr>
            </thead>
            <tbody>
              <!-- JS will populate rows here -->
            </tbody>
          </table>
        </section>

        <?php include '../components/table-footer.php'; ?>

      </section>
      <!-- ================= CONTENT WRAPPER END ================= -->

      <?php include '../components/footer.php'; ?>
    </main>
  </section>

  <?php include '../components/scripts.php'; ?>
  <script src="employee_profile/employee_script.js"></script>

  <script>
$(document).ready(function () {

  // Initialize employee header
  new EmployeeHeader();

  const activeTab = '<?php echo $activeTab ?? "personal"; ?>'; 

  // Hide edit button for non-personal pages
  if (activeTab !== 'personal') {
    $('#editProfileBtn').hide();
  } else {
    $('#editProfileBtn').show();
  }

  const backendUrl = "../../backend/employee/backend_employee.php";
  const employeeID = $('body').data('employee-id') || new URLSearchParams(window.location.search).get('id');

  // ------------------ Table Manager ------------------
  const tableManager = new TableManager({
    tableSelector: "#attendanceTable",
    searchSelector: ".table-search input",
    rowsPerPage: 10,
    recordCountSelector: "#recordCount",
    pageNumbersSelector: "#pageNumbers",
  });

  // ------------------ Load Attendance ------------------
  function loadAttendance() {
    $.ajax({
      url: backendUrl,
      method: "GET",
      data: { fetch_id: employeeID },
      dataType: "json",
      success: function(res) {
        if (!res.success) return showToast(res.message || "Error fetching attendance", "error");

        const attendanceRecords = Array.isArray(res.employee.attendance_records) 
                                  ? res.employee.attendance_records 
                                  : [];

        // ---- Aggregate totals ----
        let daysPresent = 0, daysAbsent = 0, lateArrivals = 0, overtimeHours = 0;

        attendanceRecords.forEach(rec => {
          const status = (rec.status || '').toLowerCase();
          const hours = Number(rec.hours_worked || 0);

          if (status === 'present') daysPresent++;
          if (status === 'absent') daysAbsent++;
          if (status === 'late') lateArrivals++;
          if (hours > 8) overtimeHours += (hours - 8); // overtime = hours worked beyond 8
        });

        // Update summary cards
        $("#daysPresent").text(daysPresent);
        $("#daysAbsent").text(daysAbsent);
        $("#lateArrivals").text(lateArrivals);
        $("#overtimeHours").text(overtimeHours.toFixed(2));

        // ---- Populate Attendance Table ----
        tableManager.clear();

        if (attendanceRecords.length === 0) {
          const noRow = document.createElement('tr');
          noRow.innerHTML = `<td colspan="4" style="text-align:center;">No attendance records found</td>`;
          tableManager.addRow(noRow);
          tableManager.render();
          return;
        }

        attendanceRecords.forEach(rec => {
          const row = document.createElement('tr');

          // Add status class for styling
          let statusClass = '';
          switch ((rec.status || '').toLowerCase()) {
            case 'present': statusClass = 'status-present'; break;
            case 'absent': statusClass = 'status-absent'; break;
            case 'late': statusClass = 'status-late'; break;
            case 'overtime': statusClass = 'status-overtime'; break;
          }

          row.innerHTML = `
            <td>${rec.date || '-'}</td>
            <td>${rec.hours_worked ?? 0}</td>
            <td class="${statusClass}">${rec.status || '-'}</td>
            <td>${rec.remarks || '-'}</td>
          `;
          tableManager.addRow(row);
        });

        tableManager.render();
      },
      error: function() {
        showToast("AJAX error fetching attendance", "error");
      }
    });
  }

  // Load data on page load
  loadAttendance();

  // ------------------ Pagination ------------------
  $("#nextPage").click(() => tableManager.nextPage());
  $("#prevPage").click(() => tableManager.prevPage());

});
</script>

</body>
</html>