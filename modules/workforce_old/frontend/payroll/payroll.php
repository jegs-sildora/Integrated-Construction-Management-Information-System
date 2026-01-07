<!DOCTYPE html>
<html lang="en">
<?php
// payroll_periods.php
// Place this in your pages folder. It uses your existing includes (head.php, sidebar.php, top-bar.php, footer, scripts).
$pageTitle = "Labor & Workforce - Periods";
include '../components/head.php';
?>
<body>
  <div class="dashboard-container">
    <?php include '../components/sidebar.php'; ?>

    <main class="main-content" role="main">
      <?php
        $title = "Payroll Periods";
        $breadcrumbs = [
          ['label' => 'Labor & Workforce > Payroll', 'link' => null],
          ['label' => $title, 'link' => null]
        ];
        include '../components/top-bar.php';
      ?>

      <section class="content-wrapper">

        <?php $section = 'payroll'; include '../components/tabs.php'; ?>

        <!-- Control bar (uses template fixed IDs where applicable) -->
        <section class="control-card">
          <div class="table-header-left">
            <h1>Payroll Periods</h1>
            <p>Create and manage payroll periods</p>
          </div>

          <div class="table-header-right">
            <div class="table-search">
              <i class="fa-solid fa-magnifying-glass"></i>
              <input id="globalSearch" type="search" placeholder="Search by period name or date" aria-label="Search payroll periods" />
            </div>

            <div class="filter-dropdown">
              <button id="FilterBtn" class="btn-secondary"><i class="fa-solid fa-filter"></i> Filter</button>
            </div>

            <div id="FilterMenu" class="filter-menu" style="display:none;">
              <div class="filter-options">
                <label>Status:</label>
                <select id="filterStatus">
                  <option value="">All</option>
                  <option value="Open">Open</option>
                  <option value="Closed">Closed</option>
                  <option value="Processed">Processed</option>
                </select>

                <br /><br />
                <button id="applyFilter" class="btn-primary"><i class="fa-solid fa-check"></i> Apply</button>
                <button id="clearFilter" class="btn-secondary" style="margin-left:8px;">Clear</button>
              </div>
            </div>

            <button id="addRecordBtn" class="btn-primary"><i class="fa-solid fa-plus"></i> Add Period</button>
          </div>
        </section>

        <section class="dashboard-grid" role="region" aria-label="Payroll statistics">
        <div class="stat-card">
          <h3>
            <i class="fa-solid fa-calendar-days stat-icon open-periods"></i>
            Open Periods
          </h3>
          <h2 id="statOpen">—</h2>
          <p class="status-text">Currently active payroll periods</p>
        </div>

        <div class="stat-card">
          <h3>
            <i class="fa-solid fa-check-circle stat-icon processed-periods"></i>
            Processed Periods
          </h3>
          <h2 id="statProcessed">—</h2>
          <p class="status-text">Payroll periods already processed</p>
        </div>

        <div class="stat-card">
          <h3>
            <i class="fa-solid fa-money-bill-wave stat-icon last-pay-date"></i>
            Last Pay Date
          </h3>
          <h2 id="statLastPayDate">—</h2>
          <p class="status-text">Most recent payroll processed</p>
        </div>
        </section>


        <!-- Table -->
        <section class="table-card">
          <table id="generalTable" class="styled-table">
            <thead>
              <tr>
                <th>Period Name</th>
                <th>Start Date</th>
                <th>End Date</th>
                <th>Pay Date</th>
                <th>Status</th>
                <th class="text-center">Actions</th>
              </tr>
            </thead>
            <tbody></tbody>
          </table>
        </section>

        <?php include '../components/table-footer.php'; ?>

      </section>
        <?php include '../components/footer.php'; ?>
    </main>
  </div>
         <?php include 'modal.php';?>

  <?php include '../components/scripts.php'; // ensures jQuery etc. ?>

 <script>
$(document).ready(function () {
  const backendUrl = "../../backend/payroll/backend_payroll_period.php";

  const tableManager = new TableManager({
    tableSelector: "#generalTable",
    searchSelector: "#globalSearch",
    rowsPerPage: 10,
    recordCountSelector: "#recordCount",
    pageNumbersSelector: "#pageNumbers"
  });

  // Toggle filter menu
  $("#FilterBtn").click(() => $("#FilterMenu").toggle());

  // Apply / Clear filter
  $("#applyFilter").click(() => {
    const status = $("#filterStatus").val();
    tableManager.setFilterCallback(row => {
      const rowStatus = $(row).find("td").eq(4).text().trim().toLowerCase();
      return status ? rowStatus === status.toLowerCase() : true;
    });
    $("#FilterMenu").hide();
  });

  $("#clearFilter").click(() => {
    $("#filterStatus").val('');
    tableManager.setFilterCallback(null);
    $("#FilterMenu").hide();
  });

  // ------------------ Load Payroll Periods ------------------
  function loadPeriods() {
    $.getJSON(backendUrl, function(res) {
      if(!res.success) return showToast("Error loading payroll periods", "error");

      tableManager.clear();

      // Stats counters
      let openCount = 0, processedCount = 0, lastPayDate = null;

      res.records.forEach(p => {
        const row = document.createElement("tr");
        row.innerHTML = `
          <td>${(p.period_name)}</td>
          <td>${p.period_start || ''}</td>
          <td>${p.period_end || ''}</td>
          <td>${p.pay_date || ''}</td>
          <td>${p.status}</td>
          <td class="text-center">
            <i class="fa-solid fa-pen-to-square action-icon edit-btn" data-id="${p.period_id}" title="Edit" style="color: var(--icon-edit); cursor:pointer; margin-left:10px;"></i>
            <i class="fa-solid fa-trash action-icon delete-btn" data-id="${p.period_id}" title="Delete" style="color: var(--icon-delete); cursor:pointer; margin-left:10px;"></i>
          </td>
        `;
        tableManager.addRow(row);

        // Update stats
        if(p.status?.toLowerCase() === 'open') openCount++;
        if(p.status?.toLowerCase() === 'processed') processedCount++;
        if(p.pay_date) {
          if(!lastPayDate || new Date(p.pay_date) > new Date(lastPayDate)) lastPayDate = p.pay_date;
        }
      });

      tableManager.render();

      // Update stats display
      $("#statOpen").text(openCount);
      $("#statProcessed").text(processedCount);
      $("#statLastPayDate").text(lastPayDate || '—');
    }).fail(() => showToast("AJAX error fetching payroll periods", "error"));
  }

  loadPeriods();

  // ------------------ Pagination ------------------
  $("#nextPage").click(() => tableManager.nextPage());
  $("#prevPage").click(() => tableManager.prevPage());

  // ------------------ Open Add Modal ------------------
  $("#addRecordBtn").click(() => {
    $("#modalForm")[0].reset();
    $("#modalTitle").text("Add Payroll Period");
    $("#modalActionBtn").text("Save");

    // Clear IDs / Fields
    $("#period_id").val("");  
    $("#periodName").val("");  
    $("#periodStart").val("");  
    $("#periodEnd").val("");  
    $("#pay_date").val("");  
    $("#periodStatus").val("Open");
    $("#processedBy").val("");  
    $("#processedDate").val("");  
    $("#remarks").val("");

    // Get next ID from backend
    $.getJSON(backendUrl, { get_next_id: 1 }, function(res) {
      if(res.success) $("#period_id").val(res.next_id);
    });

    $("#modalTemplate").fadeIn(200);
  });

  // ------------------ Close Modal ------------------
  $("#closeModal, #cancelModalBtn").click(() => {
    $("#modalForm")[0].reset();
    $("#modalTemplate").fadeOut(200);
  });

  // ------------------ Auto-generate Period Name ------------------
 $("#periodStart, #periodEnd").change(function() {
  const start = $("#periodStart").val();
  const end = $("#periodEnd").val();
  if(start && end) {
    const s = new Date(start);
    const e = new Date(end);
    const options = { month: "short", day: "numeric" };
    $("#periodName").val(`${s.toLocaleDateString('en-US', options)} - ${e.toLocaleDateString('en-US', options)} Period`);
  }
});


  // ------------------ Submit Add/Edit ------------------
  $("#modalForm").submit(function(e) {
    e.preventDefault();
    $.ajax({
      url: backendUrl,
      method: "POST",
      data: $(this).serialize(),
      dataType: "json",
      success: function(res) {
        if(res.success) {
          showToast(res.message || "Saved successfully", "success");
          $("#modalTemplate").fadeOut(200);
          loadPeriods();
        } else {
          showToast(res.message || "Failed to save", "error");
        }
      },
      error: function() {
        showToast("AJAX error occurred", "error");
      }
    });
  });

  // ------------------ Edit Record ------------------
  $(document).on("click", ".edit-btn", function() {
    const id = $(this).data("id");
    $.getJSON(backendUrl, { fetch_id: id }, function(res) {
      if(res.success && res.record) {
        const p = res.record;
        $("#period_id").val(p.period_id);
        $("#periodName").val(p.period_name);
        $("#periodStart").val(p.period_start);
        $("#periodEnd").val(p.period_end);
        $("#pay_date").val(p.pay_date);
        $("#periodStatus").val(p.status);
        $("#processedBy").val(p.processed_by);
        $("#processedDate").val(p.processed_date ? p.processed_date.replace(" ", "T") : "");
        $("#remarks").val(p.remarks);

        $("#modalTitle").text("Edit Payroll Period");
        $("#modalActionBtn").text("Update");
        $("#modalTemplate").fadeIn(200);
      } else {
        showToast("Record not found", "error");
      }
    });
  });

  // ------------------ Delete Record ------------------
  $(document).on("click", ".delete-btn", function() {
    const id = $(this).data("id");
    if(confirm("Are you sure you want to delete this payroll period?")) {
      $.post(backendUrl, { delete_id: id }, function(res) {
        if(res.success) {
          showToast(res.message || "Deleted successfully", "success");
          loadPeriods();
        } else {
          showToast(res.message || "Failed to delete: " + (res.message || ""), "error");
        }
      }, "json");
    }
  });

});
</script>

</body>
</html>
