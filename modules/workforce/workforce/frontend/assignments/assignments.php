<!doctype html>
<html lang="en">
<?php
  $pageTitle = "Workforce & Labor - Assignments";
  include '../components/head.php';
?>

<body>
<section class="dashboard-container">

  <!-- Sidebar -->
  <?php include '../components/sidebar.php'; ?>

  <main class="main-content" role="main">

    <!-- Top Bar -->
    <?php
      $title = "Employee Assignments";
      $breadcrumbs = [
        ['label' => 'Labor & Workforce', 'link' => null],
        ['label' => $title, 'link' => null]
      ];
      include '../components/top-bar.php';
    ?>

    <!-- ================= CONTENT WRAPPER ================= -->
    <section class="content-wrapper">

      <?php
        $section = 'assignments';
        include '../components/tabs.php';
      ?>

      <!-- Control Bar -->
      <section class="control-card">
        <div class="table-header-left">
          <h1>Assignments</h1>
          <p>Manage and track employee project assignments</p>
        </div>

        <div class="table-header-right">
          <!-- Search -->
          <div class="table-search">
            <i class="fa-solid fa-magnifying-glass"></i>
            <input type="search" placeholder="Search assignments..." />
          </div>

          <!-- Filter -->
          <div class="filter-dropdown">
            <button id="FilterBtn" class="btn-secondary">
              <i class="fa-solid fa-filter"></i> Filter
            </button>
          </div>

          <div id="FilterMenu" class="filter-menu">
            <div class="filter-options">
              <label>Status:</label>
              <select id="assignmentStatusFilter">
                <option value="">All</option>
                <option value="Active">Active</option>
                <option value="Completed">Completed</option>
                <option value="On Hold">On Hold</option>
              </select>

              <label>Role:</label>
              <select id="assignmentRoleFilter">
                <option value="">All</option>
                <option value="Foreman">Foreman</option>
                <option value="Electrician">Electrician</option>
                <option value="Plumber">Plumber</option>
                <option value="Mason">Mason</option>
                <option value="Welder">Welder</option>
              </select>

              <label>Project:</label>
              <select id="assignmentProjectFilter">
                <option value="">All Projects</option>
              </select>

              <button id="applyFilter" class="btn-primary">
                <i class="fa-solid fa-check"></i> Apply
              </button>
            </div>
          </div>

          <button id="addAssignmentBtn" class="btn-primary">
            <i class="fa-solid fa-user-plus"></i> Add Assignment
          </button>

          <button id="bulkAssignmentBtn" class="btn-primary">
            <i class="fa-solid fa-users"></i> Bulk Assignment
          </button>
        </div>
      </section>

      <!-- Table -->
      <section class="table-card">
        <table id="generalTable" class="styled-table">
          <thead>
            <tr>
              <th>Employee</th>
              <th>Project</th>
              <th>Task</th>
              <th>Phase</th>
              <th>Role</th>
              <th>Start Date</th>
              <th>End Date</th>
              <th>Status</th>
              <th class="text-center">Actions</th>
            </tr>
          </thead>
          <tbody></tbody>
        </table>
      </section>

      <?php include '../components/table-footer.php'; ?>

    </section>
    <!-- ================= CONTENT WRAPPER END ================= -->

    <?php include '../components/footer.php'; ?>
  </main>
</section>

<?php include 'modal.php'; ?>
<?php include '../components/scripts.php'; ?>

 <script>
$(document).ready(function () {

  /* =====================================================
   * TABLE MANAGER
   * ===================================================== */
  const tableManager = new TableManager({
    tableSelector: "#generalTable",
    searchSelector: ".table-search input",
    rowsPerPage: 10,
    recordCountSelector: "#recordCount",
    pageNumbersSelector: "#pageNumbers",
  });

  /* =====================================================
   * FILTERS
   * ===================================================== */
  $("#applyAssignmentFilter").click(() => {
    const status  = $("#assignmentStatusFilter").val();
    const role    = $("#assignmentRoleFilter").val();
    const project = $("#assignmentProjectFilter").val();

    tableManager.setFilterCallback(row => {
      const rowStatus  = $(row).find(".status-badge").text().toLowerCase();
      const rowRole    = $(row).find("td").eq(4).text().toLowerCase();
      const rowProject = $(row).find("td").eq(1).text().toLowerCase();

      return (!status  || rowStatus  === status.toLowerCase()) &&
             (!role    || rowRole    === role.toLowerCase()) &&
             (!project || rowProject === project.toLowerCase());
    });

    $("#FilterMenu").hide();
  });

  function loadProjectFilter() {
    $.getJSON("../../backend/projects/fetch_projects.php", res => {
      if (!res.success) return;
      $("#assignmentProjectFilter").html(
        `<option value="">All Projects</option>` +
        res.projects.map(p =>
          `<option value="${p.project_name}">${p.project_name}</option>`
        )
      );
    });
  }

  loadProjectFilter();

  /* =====================================================
   * LOAD ASSIGNMENTS
   * ===================================================== */
  function loadAssignments() {
    $.ajax({
      url: "../../backend/assignments/fetch_assignments.php",
      dataType: "json",
      success: res => {
        if (!res.success) return showToast("Error fetching assignments", "error");

        tableManager.clear();

        res.assignments.forEach(a => {
          const status = a.status.replace(" ", "-");
          const row = document.createElement("tr");
                         
          row.innerHTML = `
            <td>
               <div class="employee-cell">
                  <div class="avatar">${a.initials}</div>
                  <div class="employee-info">
                        <strong>${a.employee_name}</strong>
                        <small>#${a.employee_id}</small>
                  </div>
            </div>
            </td>
            <td>${a.project_name}</td>
            <td><div class="description-cell">${a.task_name}</div></td>
            <td>${a.phase_name || ""}</td>
            <td>${a.role}</td>
            <td>${a.start_date}</td>
            <td>${a.end_date || ""}</td>
            <td>
              <span class="status-badge status-${status}">${a.status}</span>
            </td>
            <td class="text-center">
              <i class="fa-solid fa-pen-to-square edit-btn action-icon"
                 data-id="${a.assignment_id}"
                 style="color:var(--icon-edit);cursor:pointer"></i>
              <i class="fa-solid fa-trash delete-btn action-icon"
                 data-id="${a.assignment_id}"
                 style="color:var(--icon-delete);cursor:pointer;margin-left:10px"></i>
            </td>
          `;
          tableManager.addRow(row);
        });

        tableManager.render();
      }
    });
  }

  loadAssignments();

  $("#nextPage").click(() => tableManager.nextPage());
  $("#prevPage").click(() => tableManager.prevPage());

  /* =====================================================
   * MODAL VARIABLES
   * ===================================================== */
  const assignmentModal     = $("#assignmentModal");
  const assignmentForm      = $("#assignmentForm");
  const bulkAssignmentModal = $("#bulkAssignmentModal");
  const bulkAssignmentForm  = $("#bulkAssignmentForm");

  /* =====================================================
   * ADD ASSIGNMENT
   * ===================================================== */
  $("#addAssignmentBtn").click(() => {
    assignmentForm[0].reset();
    $("#modalTitle").text("Add Assignment");

    $.getJSON("../../backend/assignments/get_next_assignment_id.php", res => {
      if (res.success) $("#assignment_id").val(res.assignment_id);
    });

    assignmentModal.fadeIn();
  });

  $("#closeAssignmentModal, #cancelAssignmentModal").click(() => {
    assignmentModal.fadeOut();
    assignmentForm[0].reset();
  });

  /* =====================================================
   * LOAD DROPDOWNS
   * ===================================================== */
 function loadDropdowns() {
  // Load Employees
  $.getJSON("../../backend/employee/backend_employee.php?fetch_employees", res => {
    if (!res.success || !res.employees) return;
    
    const employeeOptions = res.employees
      .map(e => `<option value="${e.employee_id}">${e.first_name} ${e.last_name}</option>`)
      .join(""); // Join to make a single HTML string

    $("#employeeSelect").html(`<option value="">Select Employee</option>${employeeOptions}`);
  });

  // Load Projects
  $.getJSON("../../backend/projects/backend_projects.php", { fetch_all: 1 }, res => {
    if (!res.success || !res.projects) return;

    const projectOptions = res.projects
      .map(p => `<option value="${p.project_id}">${p.project_name}</option>`)
      .join("");

    $("#projectSelect").html(`<option value="">Select Project</option>${projectOptions}`);
  });
}

// Trigger loading
loadDropdowns();

$("#projectSelect").change(function () {
  const projectId = this.value;

  if (!projectId) {
    $("#phaseSelect").html('<option value="">Select Phase</option>');
    return;
  }

  $.post(
    "../../backend/projects/backend_phases.php",
    { project_id: projectId },
    function (res) {
      if (!res.success || !Array.isArray(res.phases)) {
        $("#phaseSelect").html('<option value="">No phases found</option>');
        return;
      }

      $("#phaseSelect").html(
        '<option value="">Select Phase</option>' +
        res.phases.map(ph =>
          `<option value="${ph.phase_id}">${ph.phase_name}</option>`
        ).join("")
      );
    },
    "json"
  );
});

  /* =====================================================
   * SAVE ASSIGNMENT
   * ===================================================== */
  assignmentForm.submit(function (e) {
    e.preventDefault();

    $.post("../../backend/assignments/save_assignment.php",
      $(this).serialize(),
      res => {
        if (!res.success) return showToast(res.message, "error");
        showToast(res.message, "success");
        assignmentModal.fadeOut();
        loadAssignments();
      },
      "json"
    );
  });

  /* =====================================================
   * EDIT / DELETE
   * ===================================================== */
  $(document).on("click", ".edit-btn", function () {
    $.post("../../backend/assignments/get_assignment.php",
      { assignment_id: $(this).data("id") },
      res => {
        if (!res.success) return;

        const a = res.assignment;
        $("#assignment_id").val(a.assignment_id);
        $("#employeeSelect").val(a.employee_id);
        $("#projectSelect").val(a.project_id).trigger("change");

        setTimeout(() => $("#phaseSelect").val(a.phase_id), 200);

        $("#taskName").val(a.task_name);
        $("#role").val(a.role);
        $("#startDate").val(a.start_date);
        $("#endDate").val(a.end_date);
        $("#notes").val(a.notes);

        $("#modalTitle").text("Edit Assignment");
        assignmentModal.fadeIn();
      },
      "json"
    );
  });

  $(document).on("click", ".delete-btn", function () {
    if (!confirm("Delete this assignment?")) return;

    $.post("../../backend/assignments/delete_assignment.php",
      { assignment_id: $(this).data("id") },
      res => {
        if (!res.success) return showToast(res.message, "error");
        showToast(res.message, "success");
        loadAssignments();
      },
      "json"
    );
  });

  /* =====================================================
   * BULK ASSIGNMENTS
   * ===================================================== */
  $("#bulkAssignmentBtn").click(() => {
    bulkAssignmentForm[0].reset();
    $("#selectAllEmployees").prop("checked", false);
    loadBulkDropdowns();
    loadEmployeeCheckboxes();
    bulkAssignmentModal.fadeIn();
  });

  $("#closeBulkModal, #cancelBulkAssignmentModal").click(() => {
    bulkAssignmentModal.fadeOut();
  });

  function loadEmployeeCheckboxes() {
    $.getJSON("../../backend/employee/backend_employee.php?fetch_employees", res => {
      if (!res.success) return;
      $("#employeeList").html(
        res.employees.map(e => `
          <li>
            <label>
              <input type="checkbox" name="employee_ids[]" value="${e.employee_id}">
              ${e.first_name} ${e.last_name} (${e.employee_id})
            </label>
          </li>
        `)
      );
    });
  }

  $("#selectAllEmployees").change(function () {
    $("#employeeList input").prop("checked", this.checked);
  });

  function loadBulkDropdowns() {
    $.getJSON("../../backend/projects/backend_projects.php", { fetch_all: 1 }, res => {
      if (!res.success) return;
      $("#bulkProjectSelect").html(
        `<option value="">Select Project</option>` +
        res.projects.map(p =>
          `<option value="${p.project_id}">${p.project_name}</option>`
        )
      );
    });

    $("#bulkProjectSelect").change(function () {
      if (!this.value) return;

      $.post("../../backend/projects/backend_phases.php",
        { project_id: this.value },
        res => {
          if (!res.success) return;
          $("#bulkPhaseSelect").html(
            `<option value="">Select Phase</option>` +
            res.phases.map(ph =>
              `<option value="${ph.phase_id}">${ph.phase_name}</option>`
            )
          );
        },
        "json"
      );
    });
  }

  bulkAssignmentForm.submit(function (e) {
    e.preventDefault();

    $.post("../../backend/assignments/save_bulk_assignments.php",
      $(this).serialize(),
      res => {
        if (!res.success) return showToast(res.message, "error");
        showToast(res.message, "success");
        bulkAssignmentModal.fadeOut();
        loadAssignments();
      },
      "json"
    );
  });

});
</script>
</body>
</html>
