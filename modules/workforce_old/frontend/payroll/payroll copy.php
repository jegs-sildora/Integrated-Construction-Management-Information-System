<!doctype html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Payroll Management - Workforce Management System</title>
    <link
      rel="stylesheet"
      href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined"
    />
    <link rel="stylesheet" href="../css/main.css" />
    <link rel="stylesheet" href="../css/tab.css" />
    <link rel="stylesheet" href="../css/sidebar.css" />
    <link rel="stylesheet" href="../css/tablestyle.css" />
    <link rel="stylesheet" href="../css/dataTables.min.css" />
  </head>

  <body>
    <?php include '../components/sidebar.php'; ?>

    <main class="main-container" role="main">
      <?php $title = "Payroll Management"; $subtitle = "Process payroll based on
      attendance records and employee data."; include
      '../components/header.php'; ?>

      <!-- PAYROLL STATISTICS CARDS -->
      <section
        class="stats-container"
        role="region"
        aria-label="Time and payroll statistics"
      >
        <!-- Hours Recorded Today -->
        <div class="stat-card">
          <div class="stat-icon icon-blue">
            <span class="material-symbols-outlined">schedule</span>
          </div>
          <h4>Hours Recorded Today</h4>
          <h2 class="value">0</h2>
          <p>
            Regular: <strong>0 hrs</strong> | Overtime:
            <strong>0 hrs</strong>
          </p>
        </div>

        <!-- Payroll Executed -->
        <div class="stat-card">
          <div class="stat-icon icon-green">
            <span class="material-symbols-outlined">payments</span>
          </div>
          <h4>Payroll Executed</h4>
          <h2 class="value">₱0</h2>
          <p>This Week: <strong>₱0</strong></p>
        </div>

        <!-- Pending Confirmations -->
        <div class="stat-card">
          <div class="stat-icon icon-orange">
            <span class="material-symbols-outlined">pending_actions</span>
          </div>
          <h4>Pending Confirmations</h4>
          <h2 class="value">0</h2>
          <p>
            Attendance: <strong>0</strong> | Leave:
            <strong>0</strong>
          </p>
        </div>

        <div class="stat-card">
          <div class="stat-icon icon-purple">
            <span class="material-symbols-outlined">event</span>
          </div>
          <h4>Next Payroll Date</h4>
          <h2 class="value">—</h2>
          <p>Not scheduled</p>
        </div>
      </section>

      <?php include 'tabs.php'; ?>

      <!-- PAYROLL PERIODS TABLE -->
      <section
        class="general-table-container"
        role="region"
        aria-labelledby="payroll-period-heading"
      >
        <!-- TABLE HEADER -->
        <div class="table-header">
          <div class="table-header-left">
            <!-- SEARCH -->
            <div class="table-search">
              <span class="material-symbols-outlined">search</span>
              <input
                type="search"
                placeholder="Search by period name or date"
                aria-label="Search payroll periods"
              />
            </div>

            <!-- FILTER -->
            <div
              class="filter-dropdown"
              style="position: relative; display: inline-block"
            >
              <button id="payrollFilterBtn" class="filter-btn">
                <span class="material-symbols-outlined">filter_list</span>
                Filter
              </button>

              <div
                id="payrollFilterMenu"
                class="filter-menu"
                style="
                  display: none;
                  position: absolute;
                  top: 100%;
                  left: 0;
                  background: #fff;
                  border: 1px solid #ccc;
                  padding: 10px;
                  z-index: 10;
                "
              >
                <label>Status:</label>
                <select id="payrollStatusFilter">
                  <option value="">All</option>
                  <option value="Draft">Draft</option>
                  <option value="Locked">Locked</option>
                  <option value="Processed">Processed</option>
                  <option value="Paid">Paid</option>
                </select>

                <br /><br />
                <button id="applyPayrollFilter" class="apply-filter-btn">
                  Apply
                </button>
              </div>
            </div>
          </div>

          <!-- HEADER RIGHT ACTIONS -->
          <div class="table-header-right">
            <button id="addPayrollPeriodBtn" class="add-btn">
              <span class="material-symbols-outlined">add</span>
              New Payroll Period
            </button>

            <button class="export-btn">
              <span class="material-symbols-outlined">download</span>
              Export
            </button>

            <button class="export-btn">
              <span class="material-symbols-outlined">print</span>
              Print
            </button>
          </div>
        </div>

        <!-- TABLE -->
        <div class="table-container">
          <table id="generalTable" class="general-table">
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
            <tbody>
              <!-- Loaded via AJAX -->
            </tbody>
          </table>

          <!-- PAGINATION -->
          <div class="custom-footer">
            <button class="page-btn" id="prevPage">Prev</button>
            <span id="pageNumbers"></span>
            <button class="page-btn" id="nextPage">Next</button>
          </div>

          <!-- RECORD COUNT -->
          <div class="records-footer">
            <span id="recordCount" class="records-count">
              Showing 0 of 0 payroll periods
            </span>
          </div>
        </div>
      </section>
    </main>

    <?php include 'modal.php'?>

    <script src="../js/jquery.min.js"></script>
    <script src="../js/dataTables.min.js"></script>
    <script src="../js/main.js"></script>
    <script>
      $(document).ready(function () {
        // Initialize TableManager
        window.globalTables["generalTable"] = new TableManager(
          "#generalTable",
          ".table-search input",
          10,
          "#recordCount",
          "#pageNumbers",
        );

        const tableManager = window.globalTables["generalTable"];
        const table = tableManager.table; // DataTable instance

        // -----------------------------
        // Apply Filter
        // -----------------------------
        $("#applyPayrollFilter").click(() => {
          const statusFilter = $("#payrollStatusFilter").val();

          tableManager.setFilterCallback((row) => {
            const rowStatus = $(row).find("td").eq(4).text();
            return statusFilter ? rowStatus === statusFilter : true;
          });

          $("#payrollFilterMenu").hide();
        });

        // Toggle filter menu
        $("#payrollFilterBtn").click(() => $("#payrollFilterMenu").toggle());

        // -----------------------------
        // Load Payroll Periods Table
        // -----------------------------
        function loadPayrollPeriods() {
          $.ajax({
            url: "../../backend/fetch_payroll_periods.php",
            method: "GET",
            dataType: "json",
            success: function (res) {
              if (!res.success) return alert("Failed to load payroll periods");

              table.clear(); // Clear existing rows

              res.periods.forEach((p) => {
                const payDate = p.processed_date
                  ? p.processed_date.split(" ")[0]
                  : "—";

                table.row
                  .add([
                    p.period_name,
                    p.period_start,
                    p.period_end,
                    payDate,
                    p.status,
                    `<div class="card-actions text-center">
                        <button class="icon-btn edit-payroll-btn" data-id="${p.period_id}" title="Edit">
                            <span class="material-symbols-outlined">edit</span>
                        </button>
                        <button class="icon-btn delete-payroll-btn" data-id="${p.period_id}" title="Delete">
                            <span class="material-symbols-outlined">delete</span>
                        </button>
                    </div>`,
                  ])
                  .draw(false);
              });

              tableManager.refresh(); // Refresh pagination, search, filter
            },
            error: function (err) {
              console.error(err);
              alert("AJAX error fetching payroll periods");
            },
          });
        }

        // Initial load
        loadPayrollPeriods();

        function updatePeriodName() {
          const startDate = $("#periodStart").val();
          const endDate = $("#periodEnd").val();

          if (startDate && endDate) {
            const start = new Date(startDate);
            const end = new Date(endDate);

            const options = { month: "short", day: "numeric" };
            const startStr = start.toLocaleDateString("en-US", options);
            const endStr = end.toLocaleDateString("en-US", options);

            // Generate period name
            $("#periodName").val(`${startStr} - ${endStr} Period`);
          } else {
            $("#periodName").val(""); // Clear if dates are incomplete
          }
        }

        // Bind change events
        $("#periodStart, #periodEnd").on("change", updatePeriodName);

        // -----------------------------
        // Open Add Payroll Period Modal
        // -----------------------------
        $("#addPayrollPeriodBtn").click(() => {
          $("#modalTitle").text("Add Payroll Period");
          $("#payrollPeriodForm")[0].reset();
          $.getJSON(
            "../../backend/get_next_payroll_period_id.php",
            function (res) {
              if (res.success) $("#period_id").val(res.period_id);
              else alert("Failed to generate payroll period ID");
            },
          );

          $("#payrollPeriodModal").addClass("show");
        });

        // -----------------------------
        // Close Modal
        // -----------------------------
        $("#closePayrollPeriodModal").click(() => {
          $("#payrollPeriodModal").removeClass("show");
        });

        // -----------------------------
        // Edit Payroll Period
        // -----------------------------
        $(document).on("click", ".edit-payroll-btn", function () {
          const periodId = $(this).data("id");

          $.ajax({
            url: "../../backend/get_payroll_period.php",
            method: "GET",
            data: { period_id: periodId },
            dataType: "json",
            success: function (res) {
              if (!res.success) return alert("Failed to fetch payroll period");

              const period = res.period;

              // Update modal title
              $("#modalTitle").text("Edit Payroll Period");

              // Fill form fields
              $("#period_id").val(period.period_id);
              $("#periodName").val(period.period_name || "");
              $("#periodStart").val(period.period_start);
              $("#periodEnd").val(period.period_end);
              $("#pay_date").val(period.pay_date || "");
              $("#periodStatus").val(period.status);
              $("#processedBy").val(period.processed_by || "");
              $("#processedDate").val(
                period.processed_date
                  ? period.processed_date.replace(" ", "T")
                  : "",
              );
              $("#remarks").val(period.remarks || "");

              // Show the modal
              $("#payrollPeriodModal").addClass("show");
            },
            error: function () {
              alert("Error fetching payroll period");
            },
          });
        });

        // -----------------------------
        // Submit Payroll Period Form
        // -----------------------------
        $("#payrollPeriodForm").submit(function (e) {
          e.preventDefault();

          const formData = {
            period_id: $("#period_id").val(),
            period_name: $("#periodName").val(),
            period_start: $("#periodStart").val(),
            period_end: $("#periodEnd").val(),
            pay_date: $("#pay_date").val(),
            status: $("#periodStatus").val(),
            processed_by: $("#processedBy").val(),
            processed_date: $("#processedDate").val(),
            remarks: $("#remarks").val(),
          };

          $.ajax({
            url: "../../backend/save_payroll_period.php",
            method: "POST",
            data: formData,
            dataType: "json",
            success: function (res) {
              if (res.success) {
                loadPayrollPeriods();
                alert("Payroll period saved successfully!");
                $("#payrollPeriodModal").removeClass("show");
                
              } else {
                alert(res.message || "Failed to save payroll period");
              }
            },
            error: function () {
              alert("Error saving payroll period");
            },
          });
        });
        // -----------------------------
        // Delete Payroll Period
        // -----------------------------
        $(document).on("click", ".delete-payroll-btn", function () {
          const periodId = $(this).data("id");

          if (!confirm("Are you sure you want to delete this payroll period?"))
            return;

          $.ajax({
            url: "../../backend/delete_payroll_period.php",
            method: "POST",
            data: { period_id: periodId },
            dataType: "json",
            success: function (res) {
              if (res.success) {
                loadPayrollPeriods();
                alert("Payroll period deleted successfully!");
              } else {
                alert("Error: " + res.message);
              }
            },
            error: function () {
              alert("Failed to delete payroll period");
            },
          });
        });
      });
    </script>
  </body>
</html>
