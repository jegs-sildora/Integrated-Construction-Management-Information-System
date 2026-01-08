<!doctype html>
<html lang="en">

<?php
  $pageTitle = "Workforce & Labor - Group Assignments";
  include '../components/head.php';
?>

<body>
  <div class="dashboard-container">

    <!-- SIDEBAR -->
    <?php include '../components/sidebar.php'; ?>

    <!-- MAIN CONTENT -->
    <main class="main-content" role="main">

      <!-- TOP BAR -->
      <?php
        $title = "Group Assignments";
        $breadcrumbs = [
          ['label' => 'Labor & Workforce > Assignments', 'link' => null],
          ['label' => 'Group Assignments', 'link' => null]
        ];
        include '../components/top-bar.php';
      ?>

      <!-- CONTENT WRAPPER -->
      <section class="content-wrapper" aria-labelledby="group-assignments-table-heading">

        <?php
          $section = 'assignments';
          include '../components/tabs.php';
        ?>

        <!-- CONTROL BAR -->
        <section class="control-card">

          <div class="table-header-left">
            <h1>Group Assignments</h1>
            <p>Manage and track group project assignments</p>
          </div>

          <div class="table-header-right">

            <!-- SEARCH -->
            <div class="table-search">
              <i class="fa-solid fa-magnifying-glass"></i>
              <input
                type="search"
                placeholder="Search by group, task, or project"
                aria-label="Search group assignments"
              />
            </div>

            <!-- FILTER -->
            <div class="filter-dropdown">
              <button id="FilterBtn" class="btn-secondary">
                <i class="fa-solid fa-filter"></i> Filter
              </button>

              <div id="FilterMenu" class="filter-menu">
                <div class="filter-options">

                  <label>Status</label>
                  <select id="groupAssignmentStatusFilter">
                    <option value="">All</option>
                    <option value="Active">Active</option>
                    <option value="Completed">Completed</option>
                    <option value="On Hold">On Hold</option>
                  </select>

                  <label>Project</label>
                  <select id="groupAssignmentProjectFilter">
                    <option value="">All Projects</option>
                  </select>

                  <button id="applyFilter" class="btn-primary">
                    <i class="fa-solid fa-check"></i> Apply
                  </button>

                </div>
              </div>
            </div>

            <!-- ADD BUTTON -->
            <button id="addGroupAssignmentBtn" class="btn-primary">
              <i class="fa-solid fa-user-plus"></i> Add Group Assignment
            </button>

          </div>
        </section>

        <!-- TABLE CARD -->
        <section class="table-card">

          <table id="generalTable" class="styled-table">
            <thead>
              <tr>
                <th>Group</th>
                <th>Group Leader</th>
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

            <tbody>
              <!-- Populated via AJAX -->
            </tbody>
          </table>

        </section>

        <!-- TABLE FOOTER -->
        <?php include '../components/table-footer.php'; ?>

      </section>
      <?php include '../components/footer.php'; ?>
    </main>

    <!-- GROUP ASSIGNMENT MODAL -->
    <?php include 'group_modal.php'; ?>

    <!-- SCRIPTS -->
    <?php include '../components/scripts.php'; ?>


<script>
$(document).ready(function () {

  const backendUrl = "../../backend/assignments/backend_group_assignments.php";

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

  $("#nextPage").click(() => tableManager.nextPage());
  $("#prevPage").click(() => tableManager.prevPage());

  /* =====================================================
   * FILTERS
   * ===================================================== */
  $("#applyFilter").click(() => {
    const status  = $("#groupAssignmentStatusFilter").val();
    const project = $("#groupAssignmentProjectFilter").val();

    tableManager.setFilterCallback(row => {
      const rowStatus  = $(row).find(".status-badge").text().toLowerCase();
      const rowProject = $(row).find("td").eq(2).text().toLowerCase();

      return (!status  || rowStatus === status.toLowerCase()) &&
             (!project || rowProject === project.toLowerCase());
    });

    $("#FilterMenu").hide();
  });

  function loadProjectFilter() {
    $.getJSON("../../backend/projects/backend_projects.php",{ fetch_all: 1 }, res => {
      if (!res.success) return;
      $("#groupAssignmentProjectFilter").html(
        `<option value="">All Projects</option>` +
        res.projects.map(p =>
          `<option value="${p.project_name}">${p.project_name}</option>`
        )
      );
    });
  }
  loadProjectFilter();

  /* =====================================================
   * LOAD GROUP ASSIGNMENTS
   * ===================================================== */
  function loadGroupAssignments() {
    $.getJSON(backendUrl, { action: 'fetch_all' }, res => {
      if (!res.success) {
        showToast("Error fetching group assignments", "error");
        return;
      }

      tableManager.clear();

      res.records.forEach(a => {
        const statusClass = a.status.replace(" ", "-");
        const row = document.createElement("tr");

        row.innerHTML = `
          <td>
            <div class="group-cell">
              <strong>${a.group_name}</strong><br>
              <small>#${a.group_id}</small>
            </div>
          </td>
          <td>${a.group_leaders}</td>
          <td>${a.project_name}</td>
          <td>${a.task_description}</td>
          <td>${a.phase_name || ""}</td>
          <td>${a.role}</td>
          <td>${a.start_date}</td>
          <td>${a.end_date || ""}</td>
          <td>
            <span class="status-badge status-${statusClass}">
              ${a.status}
            </span>
          </td>
          <td class="text-center">
            <i class="fa-solid fa-pen-to-square action-icon edit-btn"
               data-id="${a.group_assignment_id}"
               style="color:var(--icon-edit);cursor:pointer"></i>
            <i class="fa-solid fa-trash action-icon delete-btn"
               data-id="${a.group_assignment_id}"
               style="color:var(--icon-delete);cursor:pointer;margin-left:10px"></i>
          </td>
        `;
        tableManager.addRow(row);
      });

      tableManager.render();
    });
  }
  loadGroupAssignments();

  /* =====================================================
   * MODAL VARIABLES
   * ===================================================== */
  const groupModal = $("#groupAssignmentModal");
  const groupForm  = $("#groupAssignmentForm");

  /* =====================================================
   * ADD GROUP ASSIGNMENT
   * ===================================================== */
  $("#addGroupAssignmentBtn").click(() => {
    groupForm[0].reset();
    $("#groupModalTitle").text("Add New Group Assignment");

    $.getJSON(`${backendUrl}?get_next_id=1`, (res) => {
            if (res.success) $("#group_assignment_id").val(res.next_id);
          });

    groupModal.fadeIn();
  });

  $("#closeGroupAssignmentModal, #cancelGroupAssignmentModal").click(() => {
    groupForm[0].reset();
    groupModal.fadeOut();
  });

  /* =====================================================
   * DROPDOWNS
   * ===================================================== */
  function loadDropdowns() {
    $.getJSON("../../backend/employee/backend_employee_groups.php?fetch_groups.php", res => {
      if (!res.success) return;
      $("#groupSelect").html(
        `<option value="">Select Group</option>` +
        res.groups.map(g =>
          `<option value="${g.group_id}">${g.group_name}</option>`
        )
      );
    });

    $.getJSON("../../backend/projects/backend_projects.php",{ fetch_all: 1 }, res => {
      if (!res.success) return;
      $("#projectSelectGroup").html(
        `<option value="">Select Project</option>` +
        res.projects.map(p =>
          `<option value="${p.project_id}">${p.project_name}</option>`
        )
      );
    });
  }
  loadDropdowns();

  $("#projectSelectGroup").change(function () {
    const projectId = this.value;

          if (!projectId) {
            $("#phaseSelect").html('<option value="">Select Phase</option>');
            return;
          }

    $.post("../../backend/projects/backend_phases.php",
            { project_id: projectId },
      res => {
        if (!res.success) return;
        $("#phaseSelectGroup").html(
          `<option value="">Select Phase</option>` +
          res.phases.map(ph =>
            `<option value="${ph.phase_id}">${ph.phase_name}</option>`
          )
        );
      },
      "json"
    );
  });

  /* =====================================================
   * SAVE GROUP ASSIGNMENT
   * ===================================================== */
  groupForm.submit(e => {
    e.preventDefault();

    $.post(backendUrl,
      groupForm.serialize(), // backend reads POST data for add/update
      res => {
        if (!res.success) {
          showToast(res.message, "error");
          return;
        }
        showToast(res.message, "success");
        groupModal.fadeOut();
        loadGroupAssignments();
      },
      "json"
    );
  });

  /* =====================================================
   * EDIT GROUP ASSIGNMENT
   * ===================================================== */
  $(document).on("click", ".edit-btn", function () {
    const id = $(this).data("id");
    $.getJSON(backendUrl, { group_assignment_id: id }, res => {
      if (!res.success) return;

      const a = res.assignment;
      $("#group_assignment_id").val(a.group_assignment_id);
      $("#groupSelect").val(a.group_id);
      $("#projectSelectGroup").val(a.project_id).trigger("change");
      setTimeout(() => $("#phaseSelectGroup").val(a.phase_id), 200);
      $("#taskDescriptionGroup").val(a.task_description);
      $("#roleGroup").val(a.role);
      $("#startDateGroup").val(a.start_date);
      $("#endDateGroup").val(a.end_date);
      $("#statusGroup").val(a.status);
      $("#notesGroup").val(a.notes);

      $("#groupModalTitle").text("Edit Group Assignment");
      groupModal.fadeIn();
    });
  });

  /* =====================================================
   * DELETE GROUP ASSIGNMENT
   * ===================================================== */
  $(document).on("click", ".delete-btn", function () {
    if (!confirm("Delete this group assignment?")) return;
    const id = $(this).data("id");

    $.post(backendUrl,
      { group_assignment_id: id, action: 'delete' },
      res => {
        if (!res.success) {
          showToast(res.message, "error");
          return;
        }
        showToast(res.message, "success");
        loadGroupAssignments();
      },
      "json"
    );
  });

});
</script>

</body>
</html>
