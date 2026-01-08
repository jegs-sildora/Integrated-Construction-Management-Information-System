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
        ['label' => 'Labor & Workforce > Payroll ', 'link' => null],
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
            <th>Actions</th> <!-- NEW -->
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

  /* ================= TABLE MANAGER ================= */
  const tableManager = new TableManager({
    tableSelector: "#generalTable",
    searchSelector: "#globalSearch",
    rowsPerPage: 10,
    recordCountSelector: "#recordCount",
    pageNumbersSelector: "#pageNumbers"
  });

  const backend = "../../backend/payroll/backend_payroll.php";

  /* ================= INITIAL HIDE (OPTION 2) ================= */
  $(".action-buttons").hide();
  $("#calcBtn, #approveBtn, #processBtn").hide();

  /* ================= NORMALIZE STATUS ================= */
  function normalizeStatus(status) {
    if (!status) return "";

    return status
      .toString()
      .toLowerCase()
      .replace(/\s+/g, "_"); // "Processed Payroll" → "processed_payroll"
  }

  /* ================= ACTION BUTTON VISIBILITY ================= */
  function updateActionButtons(rawStatus) {
    const status = normalizeStatus(rawStatus);

    $(".action-buttons").hide();
    $("#calcBtn, #approveBtn, #processBtn").hide();

    if (status === "not_calculated") {
      $("#calcBtn").show();
      $(".action-buttons").show();
      return;
    }

    if (status === "calculated") {
      $("#approveBtn").show();
      $(".action-buttons").show();
      return;
    }

    if (status === "approved") {
      $("#processBtn").show();
      $(".action-buttons").show();
      return;
    }

    /* processed or anything else = view only */
  }

  /* ================= LIST PAYROLL PERIODS ================= */
  function listPeriods() {
    $.getJSON(backend, { action: "list_periods" }, res => {
      if (!res.success) {
        showToast("Unable to load periods", "error");
        return;
      }

      const sel = $("#periodSelect");
      sel.empty().append('<option value="">Select Payroll Period</option>');

      res.periods.forEach(p => {
        sel.append(
          `<option value="${p.period_id}">
            ${p.period_name} (${p.period_start} — ${p.period_end})
          </option>`
        );
      });
    });
  }

  /* ================= FETCH PAYROLL ================= */
  function fetchPayroll(periodId) {
    if (!periodId) return;

    $.getJSON(backend, { action: "fetch_payroll", period_id: periodId }, res => {
      tableManager.clear();

      if (!res.success || !res.payroll.length) {
        showToast("No employees found", "info");
        $(".action-buttons").hide();
        tableManager.render();
        return;
      }

      /* 🔹 Period-level status (single source of truth) */
      updateActionButtons(res.payroll[0].status);

      res.payroll.forEach(r => {
        const row = document.createElement("tr");
        const name = `${r.first_name || ""} ${r.last_name || ""}`.trim();

        row.innerHTML = `
          <td>#${r.employee_id}</td>
          <td>${name}</td>
          <td>${Number(r.hours_worked || 0).toFixed(2)} hrs</td>
          <td>${Number(r.overtime_hours || 0).toFixed(2)} hrs</td>
          <td>₱${Number(r.gross_pay || 0).toFixed(2)}</td>
          <td>₱${Number(r.net_pay || 0).toFixed(2)}</td>
          <td>
            <span class="status-badge status-${normalizeStatus(r.status)}">
              ${r.status || "—"}
            </span>
          </td>
          <td>
          ${
            normalizeStatus(r.status) === "processed"
              ? `
               <i class="fa-solid fa-eye action-icon view-btn" 
                  data-id="${r.employee_id}" 
                  data-period="${periodId}" 
                  title="View Payslip" 
                  style="color: var(--icon-view); cursor: pointer;">
                </i>
              `
              : `
                <span class=" description-cell text-muted">
                  Generate payslips after payroll is processed
                </span>
              `
          }
        </td>

        `;

        tableManager.addRow(row);
      });

      tableManager.render();
    }).fail(() => {
      showToast("AJAX error loading payroll", "error");
    });
  }

  /* ================= PERIOD CHANGE ================= */
  $("#periodSelect").on("change", function () {
    const periodId = $(this).val();

    if (!periodId) {
      tableManager.clear();
      tableManager.render();
      $(".action-buttons").hide();
      return;
    }

    fetchPayroll(periodId);
  });

  /* ================= CALCULATE ================= */
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
      fetchPayroll(pid);
    }, "json");
  });

  /* ================= APPROVE ================= */
  $("#approveBtn").click(() => {
    const pid = $("#periodSelect").val();
    if (!pid) return showToast("Select a period", "error");

    $.post(backend + "?action=approve", { period_id: pid }, res => {
      showToast(res.message || "Payroll approved", "success");
      fetchPayroll(pid);
    }, "json");
  });

  /* ================= PROCESS ================= */
  $("#processBtn").click(() => {
    const pid = $("#periodSelect").val();
    if (!pid) return showToast("Select a period", "error");

    $.post(backend + "?action=process", { period_id: pid }, res => {
      showToast(res.message || "Payroll processed", "success");
      fetchPayroll(pid);
    }, "json");
  });

  /* ================= INIT ================= */
  listPeriods();

  $("#nextPage").click(() => tableManager.nextPage());
  $("#prevPage").click(() => tableManager.prevPage());

});

/* ================= VIEW PAYSLIP ================= */
// ------------------ VIEW PAYSLIP ------------------
$(document).on("click", ".view-btn", function() {
  const empId = $(this).data("id");        // <-- match the HTML
  const periodId = $(this).data("period"); // <-- stays the same
  window.location.href = `payslips.php?employee_id=${empId}&period_id=${periodId}`;
});

</script>

</body>
</html>
