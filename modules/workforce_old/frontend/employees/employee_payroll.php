<!DOCTYPE html>
<html lang="en">
<?php
  $pageTitle = "Employee Payroll - Workforce Management";
  include '../components/head.php';
?>

<body>
  <section class="dashboard-container">

    <!-- Sidebar -->
    <?php include '../components/sidebar.php'; ?>

    <main class="main-content" role="main">
      <!-- Top Bar / Breadcrumbs -->
      <?php
        $title = "Employee Payroll Summary";
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
          $activeTab = 'payroll';
          $employeeID = $_GET['id'] ?? '';
          include 'employee_profile/employee_tabs.php';
        ?>

        <!-- ================= Payroll Stats Cards ================= -->
        <section class="dashboard-grid" style="margin-bottom: 30px;">
          <div class="stat-card">
            <h3><i class="fa-solid fa-calendar-check stat-icon"></i> Days Worked</h3>
            <h2 id="daysWorked">0</h2>
            <p>Total days worked</p>
          </div>
          <div class="stat-card">
            <h3><i class="fa-solid fa-money-bill stat-icon"></i> Gross Pay</h3>
            <h2 id="grossPay">0</h2>
            <p>Total gross pay</p>
          </div>
          <div class="stat-card">
            <h3><i class="fa-solid fa-money-check stat-icon"></i> Deductions</h3>
            <h2 id="deductions">0</h2>
            <p>Total deductions</p>
          </div>
          <div class="stat-card">
            <h3><i class="fa-solid fa-wallet stat-icon"></i> Net Pay</h3>
            <h2 id="netPay">0</h2>
            <p>Total net pay</p>
          </div>
        </section>

        <!-- ================= PAYROLL DETAIL TABLE ================= -->
        <section class="table-card section-card">
          <table id="payrollTable" class="styled-table">
            <thead>
              <tr>
                <th>Period</th>
                <th>Days Worked</th>
                <th>Gross Pay</th>
                <th>Deductions</th>
                <th>Net Pay</th>
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

  const backendUrl = "../../backend/employee/backend_payroll.php"; // NEW PAYROLL BACKEND
  const employeeID = $('body').data('employee-id') || new URLSearchParams(window.location.search).get('id');

  // ------------------ Table Manager ------------------
  const tableManager = new TableManager({
    tableSelector: "#payrollTable",
    searchSelector: ".table-search input",
    rowsPerPage: 10,
    recordCountSelector: "#recordCount",
    pageNumbersSelector: "#pageNumbers",
  });

  // ------------------ Load Payroll Data ------------------
  function loadPayroll() {
    $.ajax({
      url: backendUrl,
      method: "GET",
      data: { fetch_id: employeeID },
      dataType: "json",
      success: function(res) {
        if (!res.success) return showToast(res.message || "Error fetching payroll data", "error");

        const payrollRecords = Array.isArray(res.employee.payroll_records) 
                                  ? res.employee.payroll_records 
                                  : [];

        // ---- Aggregate totals ----
        let totalDays = 0, grossPay = 0, deductions = 0, netPay = 0;

        payrollRecords.forEach(rec => {
          totalDays += Number(rec.days_worked || 0);
          grossPay += Number(rec.gross_pay || 0);
          deductions += Number(rec.deductions || 0);
          netPay += Number(rec.net_pay || 0);
        });

        // Update summary cards
        $("#daysWorked").text(totalDays);
        $("#grossPay").text(grossPay.toFixed(2));
        $("#deductions").text(deductions.toFixed(2));
        $("#netPay").text(netPay.toFixed(2));

        // ---- Populate Payroll Table ----
        tableManager.clear();

        if (payrollRecords.length === 0) {
          const noRow = document.createElement('tr');
          noRow.innerHTML = `<td colspan="5" style="text-align:center;">No payroll records found</td>`;
          tableManager.addRow(noRow);
          tableManager.render();
          return;
        }

        payrollRecords.forEach(rec => {
          const row = document.createElement('tr');
          row.innerHTML = `
            <td>${rec.period || '-'}</td>
            <td>${rec.days_worked ?? 0}</td>
            <td>${rec.gross_pay ?? 0}</td>
            <td>${rec.deductions ?? 0}</td>
            <td>${rec.net_pay ?? 0}</td>
          `;
          tableManager.addRow(row);
        });

        tableManager.render();
      },
      error: function() {
        showToast("AJAX error fetching payroll data", "error");
      }
    });
  }

  // Load payroll data on page load
  loadPayroll();

  // ------------------ Pagination ------------------
  $("#nextPage").click(() => tableManager.nextPage());
  $("#prevPage").click(() => tableManager.prevPage());

});
</script>

</body>
</html>
