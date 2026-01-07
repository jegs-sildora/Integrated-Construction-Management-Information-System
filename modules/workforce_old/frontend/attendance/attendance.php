<!doctype html>
<html lang="en">
<?php
  $pageTitle = "Workforce & Labor - Attendance Management";
  include '../components/head.php';
?>

<body>
  <div class="dashboard-container">

    <!-- Sidebar -->
    <?php include '../components/sidebar.php'; ?>

    <main class="main-content" role="main">

      <!-- Top Bar / Breadcrumbs -->
      <?php
        $title = "Attendance Management";
        $breadcrumbs = [
          ['label' => 'Labor & Workforce', 'link' => null],
          ['label' => $title, 'link' => null]
        ];
        include '../components/top-bar.php';
      ?>

      <section class="content-wrapper">

        <!-- Control Bar -->
        <section class="control-card">
          <div class="table-header-left">
            <h1>Individual Employee Attendance</h1>
            <p>Admin-managed attendance tracking for payroll processing</p>
          </div>
          <div class="table-header-right">
            <div class="table-search">
              <i class="fa-solid fa-magnifying-glass"></i>
              <input type="search" placeholder="Search employees" aria-label="Search employees">
            </div>

            <div class="filter-dropdown">
              <button id="FilterBtn" class="btn-secondary"><i class="fa-solid fa-filter"></i> Filter</button>
            </div>

            <div id="FilterMenu" class="filter-menu">
              <div class="filter-options">
                <label>Status:</label>
                <select id="attendanceStatusFilter">
                  <option value="">All</option>
                  <option value="Present">Present</option>
                  <option value="Absent">Absent</option>
                  <option value="Late">Late</option>
                  <option value="On Leave">On Leave</option>
                  <option value="Unassigned">Unassigned</option>
                </select>

                <label>Group:</label>
                <select id="attendanceGroupFilter">
                  <option value="">All</option>
                </select>

                <label>Project:</label>
                <select id="attendanceProjectFilter">
                  <option value="">All</option>
                </select>

                <button id="applyAttendanceFilter" class="btn-primary">
                  <i class="fa-solid fa-check"></i> Apply
                </button>
              </div>
            </div>

            <input type="date" id="attendanceDate" value="<?= date('Y-m-d') ?>" class="attendance-date">
            <button class="btn-primary" id="lockAttendanceBtn"><i class="fa-solid fa-lock"></i> Lock</button>
          </div>
        </section>

        <!-- Table -->
        <section class="table-card">
          <table id="generalTable" class="styled-table">
            <thead>
              <tr>
                <th>Employee</th>
                <th>Group</th> 
                <th>Time In</th>
                <th>Time Out</th>
                <th>Assigned Project</th>
                <th>Status</th>
                <th>Remarks</th>
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

  <?php include '../components/scripts.php'; ?>

  <script>
  $(document).ready(function () {
    // TableManager
    const tableManager = new TableManager({
      tableSelector: "#generalTable",
      searchSelector: ".table-search input",
      rowsPerPage: 5,
      recordCountSelector: "#recordCount",
      pageNumbersSelector: "#pageNumbers",
    });


    function loadGroupFilter() {
      $.ajax({
        url: "../../backend/employee/backend_employee.php?fetch_employees",
        method: "GET",
        dataType: "json",
        success: function(res) {
          if (res.success) {
            let groups = {};
            res.employees.forEach(emp => {
              if (emp.group_id && emp.group_name) groups[emp.group_id] = emp.group_name;
            });

            let options = `<option value="">All Groups</option>`;
            Object.entries(groups).forEach(([id, name]) => {
              options += `<option value="${id}">${name}</option>`;
            });

            $("#attendanceGroupFilter").html(options);
          }
        },
        error: function() {
          showToast("Error fetching groups", "error");
        }
      });
    }

    loadGroupFilter();

    function loadProjectFilter(date = $("#attendanceDate").val()) {
        const projectsSet = new Set();

        // Go through each row in the table and collect unique project names
        $("#generalTable tbody tr").each(function() {
          const projectCell = $(this).find(".project-name");
          const projectName = projectCell.text().trim();
          const projectId = projectCell.data("assignment-id");

          if (projectName && projectName !== "Unassigned" && projectId) {
            projectsSet.add(JSON.stringify({ id: projectId, name: projectName }));
          }
        });

        // Populate the filter dropdown
        let options = `<option value="">All Projects</option>`;
        Array.from(projectsSet).forEach(p => {
          const proj = JSON.parse(p);
          options += `<option value="${proj.id}">${proj.name}</option>`;
        });
        $("#attendanceProjectFilter").html(options);
      }


 function loadAttendance(date = $("#attendanceDate").val()) {
  $.ajax({
    url: "../../backend/employee/backend_employee.php?fetch_employees",
    method: "GET",
    data: { date },
    dataType: "json",
    success: async function(res) {
      if (!res.success) return showToast("Failed to load employees");

      tableManager.clear();

      // ------------------ Fetch assignments for all employees ------------------
      const assignmentPromises = res.employees.map(emp =>
        $.ajax({
          url: "../../backend/attendance/fetch_employee_assignment.php",
          method: "GET",
          data: { employee_id: emp.employee_id, date },
          dataType: "json"
        }).then(assignRes => {
          if (assignRes.success && assignRes.assignment) {
            emp.project_id = assignRes.assignment.project_id;
            emp.project_name = assignRes.assignment.project_name;
          } else {
            emp.project_id = null;
            emp.project_name = "Unassigned";
          }
        }).catch(() => {
          emp.project_id = null;
          emp.project_name = "Error";
        })
      );

      await Promise.all(assignmentPromises);

      // ------------------ Sort employees ------------------
      res.employees.sort((a, b) => {
        const aAssigned = a.project_id ? 1 : 0;
        const bAssigned = b.project_id ? 1 : 0;

        if (aAssigned !== bAssigned) return bAssigned - aAssigned; // assigned first

        if (a.last_name.toLowerCase() < b.last_name.toLowerCase()) return -1;
        if (a.last_name.toLowerCase() > b.last_name.toLowerCase()) return 1;

        if (a.first_name.toLowerCase() < b.first_name.toLowerCase()) return -1;
        if (a.first_name.toLowerCase() > b.first_name.toLowerCase()) return 1;

        return 0;
      });

      // ------------------ Render table ------------------
      res.employees.forEach(emp => {
        const initials = (emp.first_name[0] + emp.last_name[0]).toUpperCase();
        const row = document.createElement("tr");
        row.dataset.groupId = emp.group_id || '';

        row.innerHTML = `
          <td>
            <div class="employee-cell" style="display:flex; align-items:center; gap:10px;">
              <div class="avatar">${initials}</div>
              <div class="employee-info">
                <strong>${emp.last_name}, ${emp.first_name}</strong>
                <small>#${emp.employee_id}</small>
              </div>
            </div>
          </td>
          <td>
            <div class="group-cell">
              <strong>${emp.group_name || "Unassigned"}</strong><br>
              <small>${emp.group_id ? "#" + emp.group_id : ""}</small>
            </div>
          </td>
          <td><input type="time" class="time-input start-time"></td>
          <td><input type="time" class="time-input end-time"></td>
          <td><span class="project-name ${emp.project_id ? '' : 'muted'}">${emp.project_name}</span></td>
          <td>
            <select class="status-select" ${emp.project_id ? '' : 'disabled'}>
              <option value="Present">Present</option>
              <option value="Absent" selected>Absent</option>
              <option value="Late">Late</option>
              <option value="On Leave">On Leave</option>
              <option value="Unassigned" ${!emp.project_id ? 'selected' : ''}>Unassigned</option>
            </select>
          <td><input type="text" class="remarks-input" placeholder="Remarks" ${emp.project_id ? '' : 'disabled'}></td>
        `;

        tableManager.addRow(row);
      });

      tableManager.render();
    },
    error: function() { showToast("Failed to fetch employees"); }
  });
}

// Initial load
loadAttendance();

loadProjectFilter($("#attendanceDate").val());

    // ------------------ Filters ------------------
    $("#applyAttendanceFilter").click(() => {
      const status = $("#attendanceStatusFilter").val();
      const project = $("#attendanceProjectFilter").val();
      const group = $("#attendanceGroupFilter").val();

      tableManager.setFilterCallback(row => {
        const rowStatus = $(row).find(".status-select").val().toLowerCase();
        const rowProject = $(row).find(".project-name").text().toLowerCase();
        const rowGroup = $(row).data("group-id"); // We'll add this below

        return (!status || rowStatus === status.toLowerCase()) &&
              (!project || rowProject === project.toLowerCase()) &&
              (!group || rowGroup === group);
      });

      $("#FilterMenu").hide();
    });

    $("#attendanceDate").change(function() { loadAttendance($(this).val()); });

    // ------------------ Auto-update status ------------------
    function updateRowStatus(row) {
      const timeIn = row.find(".start-time").val();
      const timeOut = row.find(".end-time").val();
      const statusSelect = row.find(".status-select");
      const projectCell = row.find(".project-name");
      if (projectCell.hasClass("muted") || projectCell.text() === "Unassigned") {
        statusSelect.val("Unassigned").prop("disabled", true);
        row.find(".time-input").prop("disabled", true);
        return;
      }
      if (timeIn && timeOut) statusSelect.val("Present");
      else if (!timeIn && !timeOut && !["Late","On Leave"].includes(statusSelect.val()))
        statusSelect.val("Absent");
    }
    $(document).on("change", "#generalTable .time-input", function(){ updateRowStatus($(this).closest("tr")); });

    // ------------------ Lock Attendance ------------------
    $("#lockAttendanceBtn").click(() => {
      if (!confirm("Lock attendance for this date?")) return;
      const attendanceDate = $("#attendanceDate").val();
      const records = [];
      $("#generalTable tbody tr").each(function() {
        const row = $(this);
        const employeeId = row.find(".employee-cell small").text().replace("#", "").trim();
        records.push({
          employee_id: employeeId,
          attendance_date: attendanceDate,
          time_in: row.find(".start-time").val() || null,
          time_out: row.find(".end-time").val() || null,
          status: row.find(".status-select").val(),
          project_id: row.find(".project-name").data("assignment-id") || null,
          remarks: row.find(".remarks-input").val()
        });
      });

      $.ajax({
        url: "../../backend/attendance/add_attendance.php",
        method: "POST",
        contentType: "application/json",
        data: JSON.stringify({ records }),
        success: function(res) {
          if (res.success) {
            showToast("Attendance locked successfully!", "success");
            $("#generalTable input, #generalTable select").prop("disabled", true);
          } else showToast(res.message || "Error locking attendance", "error");
        },
        error: function() { showToast("Failed to lock attendance", "error"); }
      });
    });

    // ------------------ Pagination ------------------
    $("#nextPage").click(() => tableManager.nextPage());
    $("#prevPage").click(() => tableManager.prevPage());

  });
  </script>
</body>
</html>
