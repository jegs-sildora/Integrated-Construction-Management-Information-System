<!DOCTYPE html>
<html lang="en">
<?php 
$pageTitle = "Payroll Management";
include '../components/head.php'; 
?>
<body>

<section class="dashboard-container">
  <?php include '../components/sidebar.php'; ?>

  <main class="main-content">
    <?php
      $title = "Payroll Management";
      $breadcrumbs = [
        ['label' => 'Labor & Workforce', 'link' => null],
        ['label' => $title, 'link' => null]
      ];
      include '../components/top-bar.php';
    ?>

    <section class="content-wrapper">

     <?php $section = 'payroll'; include '../components/tabs.php'; ?>
      <!-- Control Bar for Payroll -->
        <section class="control-card">
          <div class="table-header-left">
            <h1>Payroll Management</h1>
            <p>Manage payroll periods, calculate, approve, and process payroll</p>
          </div>

         <div class="table-header-right">

          <!-- Payroll Period Select -->
         <select id="periodSelect" class="form-input">
            <option value="">Select Payroll Period</option>
          </select>


          <!-- Payroll Actions -->
          <div class="action-buttons">
          <button id="calcBtn" class="btn-primary">
            <i class="fa-solid fa-calculator"></i> Calculate Payroll
          </button>
          <button id="approveBtn" class="btn-secondary">
            <i class="fa-solid fa-check"></i> Approve Payroll
          </button>
          <button id="processBtn" class="btn-secondary">
            <i class="fa-solid fa-money-bill-wave"></i> Process Payroll
          </button>
          </div>

        </section>


      <!-- ================= PAYROLL TABLE ================= -->
      <section class="table-card">
        <div class="table-container">
          <table id="generalTable" class="styled-table">
          <thead>
            <tr>
              <th>Employee ID</th>
              <th>Name</th>
              <th>Hours Worked</th>
              <th>Overtime Hrs</th>
              <th>Gross Pay</th>
              <th>Net Pay</th>
              <th>Status</th>
            </tr>
          </thead>
          <tbody></tbody>
        </table>

        </div>
      </section>
            <?php include '../components/table-footer.php'; ?>
    </section>

    <?php include '../components/footer.php'; ?>
  </main>
</section>

<?php include '../components/scripts.php'; ?>

<script>
$(document).ready(function () {
const tableManager = new TableManager({
    tableSelector: "#generalTable",
    searchSelector: "#globalSearch",
    rowsPerPage: 10,
    recordCountSelector: "#recordCount",
    pageNumbersSelector: "#pageNumbers"
  });


  const backend = "../../backend/payroll/backend_payroll.php";





  // ------------------ LIST PERIODS ------------------
  function listPeriods() {
    $.getJSON(backend, { action: 'list_periods' }, res => {
      if (!res.success) return showToast("Unable to load periods","error");

      const sel = $("#periodSelect");
      sel.empty().append('<option value="">Select Payroll Period</option>');

      res.periods.forEach(p => {
        sel.append(`<option value="${p.period_id}">${p.period_name} (${p.period_start} — ${p.period_end})</option>`);
      });
    });
  }

  $("#periodSelect").on("change", function () {
  const periodId = $(this).val();
 

  if (!periodId) {
    tableManager.clear();
    tableManager.render();
    return;
  }

  fetchPayroll(periodId);
});

  // ------------------ FETCH PAYROLL ------------------
 function fetchPayroll(periodId) {
  if (!periodId) return;

  $.getJSON(backend, { action: "fetch_payroll", period_id: periodId }, res => {
    tableManager.clear();

    if (!res.success || !res.payroll.length) {
      showToast("No employees found", "info");
      tableManager.render(); // render empty table
      return;
    }

    res.payroll.forEach(r => {
      const row = document.createElement("tr");
      const name = `${r.first_name || ""} ${r.last_name || ""}`.trim();

      row.innerHTML = `
        <td>${r.employee_id}</td>
        <td>${name}</td>
        <td>${Number(r.hours_worked || 0).toFixed(2)}</td>
        <td>${Number(r.overtime_hours || 0).toFixed(2)}</td>
        <td>₱${Number(r.gross_pay || 0).toFixed(2)}</td>
        <td>₱${Number(r.net_pay || 0).toFixed(2)}</td>
        <td>${r.status || '—'}</td>
      `;

      tableManager.addRow(row);
    });

    tableManager.render();
  }).fail(() => {
    showToast("AJAX error loading payroll", "error");
  });
}





  // ------------------ CALCULATE PAYROLL ------------------
  $("#calcBtn").click(() => {
  const pid = $("#periodSelect").val();
  if (!pid) return showToast("Select a period", "error");

  $("#calcBtn").prop("disabled", true).text("Calculating...");

  $.post(backend + "?action=calculate", { period_id: pid }, res => {
    $("#calcBtn").prop("disabled", false).text("Calculate Payroll");

    if (!res.success) {
      showToast(res.message || "Calculation failed", "error");
      return;
    }

    showToast("Payroll calculated", "success");

    // Reload table AFTER calculation
    fetchPayroll(pid);
  }, "json").fail(() => {
    $("#calcBtn").prop("disabled", false).text("Calculate Payroll");
    showToast("AJAX error", "error");
  });
});


  // ------------------ APPROVE / PROCESS ------------------
  $("#approveBtn").click(() => {
    const pid = $("#periodSelect").val();
    if (!pid) return showToast("Select a period","error");

    $.post(backend + "?action=approve", { period_id: pid, approved_by: "admin" }, res => {
      showToast(res.message || "Payroll approved","success");
    }, "json");
  });

  $("#processBtn").click(() => {
    const pid = $("#periodSelect").val();
    if (!pid) return showToast("Select a period","error");

    $.post(backend + "?action=process", { period_id: pid, processed_by: "admin" }, res => {
      showToast(res.message || "Payroll processed","success");
    }, "json");
  });

  // ------------------ PERIOD CHANGE ------------------


  // ------------------ INITIAL LOAD ------------------
  listPeriods();

});
</script>

</body>
</html>
