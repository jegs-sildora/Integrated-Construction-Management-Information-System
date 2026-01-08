<!doctype html>
<html lang="en">
<?php
  $pageTitle = "Workforce & Labor - Employee Groups";
  include '../components/head.php';
?>

<body>
  <section class="dashboard-container">

    <!-- Sidebar -->
    <?php include '../components/sidebar.php'; ?>

    <main class="main-content" role="main">

      <!-- Top Bar / Breadcrumbs -->
      <?php
        $title = "Employee Groups";
        $breadcrumbs = [
          ['label' => 'Labor & Workforce > Employees', 'link' => null],
          ['label' => $title, 'link' => null]
        ];
        include '../components/top-bar.php';
      ?>

      <!-- ================= CONTENT WRAPPER ================= -->
      <section class="content-wrapper">

       
       <?php
          $section = 'employees'; 
          include '../components/tabs.php';
        ?>
        <!-- Control Bar -->
        <section class="control-card">
          <div class="table-header-left">
            <h1>Employee Groups Management</h1>
            <p>Create and manage employee groups efficiently</p>
          </div>
          <div class="table-header-right">
            <div class="table-search">
              <i class="fa-solid fa-magnifying-glass"></i>
              <input type="search" placeholder="Search groups..." aria-label="Search groups" />
            </div>

            <div class="filter-dropdown">
              <button id="FilterBtn" class="btn-secondary">
                <i class="fa-solid fa-filter"></i> Filter
              </button>
            </div>
            <div id="FilterMenu" class="filter-menu">
              <div class="filter-options">
                <div>
                  <label>Status:</label>
                  <select id="groupStatusFilter">
                    <option value="">All</option>
                    <option value="Active">Active</option>
                    <option value="Inactive">Inactive</option>
                  </select>
                </div>
                <div>
                  <label>Type:</label>
                  <select id="groupTypeFilter">
                    <option value="">All</option>
                    <option value="Full-Time">Full-Time</option>
                    <option value="Part-Time">Part-Time</option>
                  </select>
                </div>
                <button id="applyFilter" class="btn-primary">
                  <i class="fa-solid fa-check"></i> Apply
                </button>
              </div>
            </div>

            <button id="addRecordBtn" class="btn-primary">
              <i class="fa-solid fa-plus"></i> Add Group
            </button>
          </div>
        </section>

        <!-- Table -->
        <section class="table-card">
          <table id="generalTable" class="styled-table">
            <thead>
              <tr>
                <th>Group Name</th>
                <th>Leader</th>
                <th>Status</th>
                <th>Description</th>
                <th class="text-center">Actions</th>
              </tr>
            </thead>
            <tbody>
              <!-- Dynamic rows populated via AJAX -->
            </tbody>
          </table>
        </section>

        <?php include '../components/table-footer.php'; ?>

      </section>
      <!-- ================= CONTENT WRAPPER END ================= -->

      <?php include '../components/footer.php'; ?>
    </main>
  </section>

  <!-- Group Modal -->
  <?php include 'groupmodal.php'; ?>
  <?php include '../components/scripts.php'; ?>

<script>
$(document).ready(function () {

    // ----------------- Backend URL -----------------
    const backendURL = "../../backend/employee/backend_employee_groups.php";

    /*** Initialize TableManager ***/
    const tableManager = new TableManager({
        tableSelector: "#generalTable",
        searchSelector: ".table-search input",
        rowsPerPage: 10,
        recordCountSelector: "#recordCount",
        pageNumbersSelector: "#pageNumbers",
    });

    // ----------------- Apply Filters -----------------
    $("#applyFilter").click(() => {
        const status = $("#groupStatusFilter").val()?.toLowerCase();
        const type = $("#groupTypeFilter").val()?.toLowerCase();

        tableManager.setFilterCallback((row) => {
            const rowStatus = $(row).find("span.status").text().toLowerCase();
            const rowType = $(row).find("td").eq(3).text().toLowerCase();
            return (!status || rowStatus === status) && (!type || rowType === type);
        });

        $("#FilterMenu").hide();
    });

    // ----------------- Load Groups -----------------
    function loadEmployeeGroups() {
        $.getJSON(`${backendURL}?fetch&_=` + new Date().getTime(), function(res) {
            if (!res.success) return showToast("Error fetching employee groups", "error");

            tableManager.clear();

            let total = 0, active = 0, inactive = 0;

            res.groups.forEach(g => {
                const row = document.createElement("tr");
                row.innerHTML = `
                    <td>
                        <div class="group-cell">
                            <strong>${g.group_name}</strong><br>
                            <small>#${g.group_id}</small>
                        </div>
                    </td>
                    <td>${g.leader_name || "-"}</td>
                    <td><span class="status ${g.status.toLowerCase()}">${g.status}</span></td>
                    <td>${g.description}</td>
                    <td>
                        <i class="fa-solid fa-pen-to-square action-icon edit-group" data-id="${g.group_id}" title="Edit" style="color: var(--icon-edit); cursor:pointer;"></i>
                        <i class="fa-solid fa-trash action-icon delete-group" data-id="${g.group_id}" title="Delete" style="color: var(--icon-delete); cursor:pointer; margin-left:10px;"></i>
                    </td>
                `;
                tableManager.addRow(row);

                total++;
                if (g.status.toLowerCase() === "active") active++;
                if (g.status.toLowerCase() === "inactive") inactive++;
            });

            tableManager.render();
            console.log(`Total: ${total}, Active: ${active}, Inactive: ${inactive}`);
        });
    }

    loadEmployeeGroups();

    // ----------------- Pagination -----------------
    $("#nextPage").click(() => tableManager.nextPage());
    $("#prevPage").click(() => tableManager.prevPage());

    // ----------------- Open Create Group Modal -----------------
    $("#addRecordBtn").click(() => {
        // Fetch employees
       $.getJSON("../../backend/employee/backend_employee.php?fetch_employees", function(res) {
            if (!res.success) return;

            const container = $("#group_members_container");
            const leaderSelect = $("#groupLeaderSelect");
            container.empty();
            leaderSelect.empty().append('<option value="">Select Leader</option>');

            res.employees.forEach(emp => {
                const fullName = emp.first_name + " " + emp.last_name;
                container.append(`
                    <div>
                        <input type="checkbox" name="group_members[]" value="${emp.employee_id}" id="emp_${emp.employee_id}">
                        <label for="emp_${emp.employee_id}">${fullName}</label>
                    </div>
                `);
                leaderSelect.append(`<option value="${emp.employee_id}">${fullName} (${emp.employee_id})</option>`);
            });

            leaderSelect.off("change").on("change", function() {
                const leaderId = $(this).val();
                container.find("input[type='checkbox']").each(function(){
                    $(this).prop("disabled", $(this).val() === leaderId);
                    if ($(this).val() === leaderId) $(this).prop("checked", true);
                });
            });
        });

        // Get next group ID
        $.getJSON(`${backendURL}?get_next_id=1`, function(res) {
            if (res.success) $("#group_id").val(res.next_id);
        });

        $("#groupModalTitle").text("Create Group");
        $("#groupModalBtnText").text("Create Group");
        $("#groupModal").fadeIn();
    });

    // ----------------- Open Edit Group Modal -----------------
    $(document).on("click", ".edit-group", function() {
        const groupId = $(this).data("id");

        $.getJSON(`${backendURL}?fetch_id=${groupId}`, function(res) {
            if (!res.success) return showToast(res.message || "Failed to fetch group data", "error");

            const group = res.group;
            $("#group_id").val(group.group_id);
            $("#group_name").val(group.group_name);
            $("#description").val(group.description);

            const leaderSelect = $("#groupLeaderSelect");
            const membersContainer = $("#group_members_container");
            leaderSelect.empty().append('<option value="">Select Leader</option>');
            membersContainer.empty();

            res.employees.forEach(emp => {
                const fullName = emp.first_name + " " + emp.last_name;
                const isLeader = emp.employee_id == group.leader_id;
                const isMember = group.members.includes(emp.employee_id);

                membersContainer.append(`
                    <div>
                        <input type="checkbox" name="group_members[]" value="${emp.employee_id}" id="emp_${emp.employee_id}" ${isMember ? 'checked' : ''}>
                        <label for="emp_${emp.employee_id}">${fullName}</label>
                    </div>
                `);

                leaderSelect.append(`<option value="${emp.employee_id}" ${isLeader ? 'selected' : ''}>${fullName}</option>`);
            });

            leaderSelect.off("change").on("change", function() {
                const leaderId = $(this).val();
                membersContainer.find("input[type='checkbox']").each(function() {
                    $(this).prop("disabled", $(this).val() === leaderId);
                    if ($(this).val() === leaderId) $(this).prop("checked", true);
                });
            });

            $("#groupModalTitle").text("Edit Group");
            $("#groupModalBtnText").text("Update Group");
            $("#groupModal").fadeIn();
        });
    });

    // ----------------- Close Modal -----------------
    $("#closeGroupModal, #cancelGroupModal").click(() => $("#groupModal").fadeOut());

    // ----------------- Submit Create/Edit -----------------
    $("#groupForm").submit(function(e) {
        e.preventDefault();
        $.ajax({
            url: backendURL,
            method: "POST",
            data: $(this).serialize(),
            dataType: "json",
            success: function(res) {
                if (res.success) {
                    loadEmployeeGroups();
                    $("#groupForm")[0].reset();
                    $("#groupModal").fadeOut();
                    showToast(res.message, "success");
                } else {
                    showToast(res.message, "error");
                }
            },
            error: function() {
                showToast("AJAX error occurred", "error");
            }
        });
    });

    // ----------------- Delete Group -----------------
    $(document).on("click", ".delete-group", function() {
        const groupId = $(this).data("id");
        if (!confirm("Are you sure you want to delete this group?")) return;

        $.post(backendURL, { delete_id: groupId }, function(res) {
            if (res.success) {
                showToast("Group deleted successfully!", "success");
                loadEmployeeGroups();
            } else {
                showToast(res.message || "Error deleting group", "error");
            }
        }, "json");
    });

});
</script>
</body>
</html>
