<!doctype html>
<html lang="en">
  <?php 
    $pageTitle = "Labor & Workforce - Projects";  // Set the title of the page
    include '../components/head.php';  // All CSS links are handled here 
  ?>

  <body>
    <section class="dashboard-container">
      <?php include '../components/sidebar.php'; ?> <!-- Sidebar, don't modify -->

      <main class="main-content" role="main">
        <?php 
          $title = "Project Management"; 
          $breadcrumbs = [ 
            ['label' => 'Labor & Workforce', 'link' => null], 
            ['label' => $title, 'link' => null]
          ]; 
          include '../components/top-bar.php'; 
        ?> 

        <!-- CONTENT WRAPPER START -->
        <section class="content-wrapper" aria-labelledby="group-table-heading">
          <?php
            $section = 'projects';
            include '../components/tabs.php'; 
            ?> <!-- Include tabs -->

          <!-- Control Bar -->
          <section class="control-card">
            <!-- Left: Title and Subtitle -->
            <div class="table-header-left">
              <h1>Project Management</h1>
              <p>Create, manage, and track all construction projects.</p>
            </div>

            <!-- Right: Search, Filter, and Add Group Button -->
            <div class="table-header-right">
              <!-- Search -->
              <div class="table-search">
                <i class="fa-solid fa-magnifying-glass"></i>
                <input type="search" placeholder="Search by project name or manager" aria-label="Search projects"/>
              </div>

              <!-- Filter Button -->
              <div class="filter-dropdown">
                <button id="FilterBtn" class="btn-secondary">
                  <i class="fa-solid fa-filter"></i> Filter
                </button>
              </div>

              <!-- Floating Filter Menu -->
              <div id="FilterMenu" class="filter-menu">
                <div class="filter-options">
                  <label>Status:</label>
                  <select id="projectStatusFilter">
                    <option value="">All</option>
                    <option value="Planning">Planning</option>
                    <option value="In Progress">In Progress</option>
                    <option value="On Hold">On Hold</option>
                    <option value="Completed">Completed</option>
                    <option value="Cancelled">Cancelled</option>
                  </select>
                  <br />

                  <label>Budget Range:</label>
                  <select id="projectBudgetFilter">
                    <option value="">All</option>
                    <option value="low">&lt; ₱1M</option>
                    <option value="mid">₱1M – ₱5M</option>
                    <option value="high">&gt; ₱5M</option>
                  </select>
                  <br />

                  <button id="applyProjectFilter" class="apply-filter-btn">
                    <i class="fa-solid fa-check"></i> Apply
                  </button>
                </div>
  
              </div>
                  <!-- Action Buttons -->
                <button id="addProjectBtn" class="btn-primary">
                  <i class="fa-solid fa-user-plus"></i> Add Project
                </button>
            </div>
          </section>

          <!-- Stat Cards -->
          <section class="dashboard-grid" role="region" aria-label="Project statistics">
            <div class="stat-card">
              <h3>
                <i class="fa-solid fa-clipboard-list stat-icon total-projects"></i>
                Total Projects
              </h3>
              <h2>0</h2>
              <p class="status-text">All registered projects</p>
            </div>
            <div class="stat-card">
              <h3>
                <i class="fa-solid fa-project-diagram stat-icon ongoing-projects"></i>
                Ongoing Projects
              </h3>
              <h2>0</h2>
              <p class="status-text">Currently in progress</p>
            </div>
            <div class="stat-card">
              <h3>
                <i class="fa-solid fa-calendar-alt stat-icon upcoming-projects"></i>
                Upcoming Projects
              </h3>
              <h2>0</h2>
              <p class="status-text">Scheduled to start</p>
            </div>
            <div class="stat-card">
              <h3>
                <i class="fa-solid fa-dollar-sign stat-icon project-budget"></i>
                Project Budget
              </h3>
              <h2>₱0</h2>
              <p class="status-text">Total allocated funds</p>
            </div>
          </section>

          <!-- Table -->
          <section class="table-card">
            <table id="generalTable" class="styled-table">
              <thead>
                <tr>
                  <th>Project Name</th>
                  <th>Project Manager</th>
                  <th>Description</th>
                  <th>Start Date</th>
                  <th>Status</th>
                  <th>Budget</th>
                  <th class="text-center">Actions</th>
                </tr>
              </thead>
              <tbody>
                <!-- Dynamic rows inserted here via AJAX -->
              </tbody>
            </table>
          </section>

          <!-- Pagination and Record Count -->
          <?php include '../components/table-footer.php'; ?>

        </section>

        <?php include '../components/footer.php'; ?>
      </section>
    </main>

    <!-- Modal Include -->
    <?php include 'projectmodal.php'; ?>  

    <?php include '../components/scripts.php'; ?>  <!-- General scripts -->

  <script>
$(document).ready(function () {
    const tableManager = new TableManager({
        tableSelector: "#generalTable",
        searchSelector: ".table-search input",
        rowsPerPage: 10,
        recordCountSelector: "#recordCount",
        pageNumbersSelector: "#pageNumbers",
    });

    const backendUrl = "../../backend/projects/backend_projects.php";

    // ------------------ Load Projects ------------------
    function loadProjects() {
        $.ajax({
            url: backendUrl,
            method: "GET",
            dataType: "json",
            success: function (res) {
                if (!res.success) return showToast("Error fetching projects", "error");

                tableManager.clear();

                let totalProjects = res.projects.length;
                let activeCount = 0, completedCount = 0, upcomingCount = 0;
                const now = new Date();

                res.projects.forEach(p => {
                    const row = document.createElement("tr");
                    row.innerHTML = ` 
                        <td>
                         <div class="employee-info">
                        <strong>${p.project_name}</strong><br>
                        <small>#${p.project_id}</small>
                         </div>
                        <td>
                         <div class="employee-info">
                        ${p.manager_name || "-"}<br>
                        <small>#${p.project_manager_id}</small>
                        </div>
                        </td>
                        <td><div class="description-cell">${p.description}</div></td>
                        <td>${p.start_date || "-"}</td>
                        <td><span class="status-badge status-${p.status.replace(" ", "-")}">${p.status}</span></td>
                        <td>₱${p.budget || 0}</td>
                        <td class="text-center">
                            <i class="fa-solid fa-pen-to-square action-icon edit-btn" data-id="${p.project_id}" title="Edit" style="color: var(--icon-edit); cursor: pointer;"></i>
                            <i class="fa-solid fa-trash action-icon delete-btn" data-id="${p.project_id}" title="Delete" style="color: var(--icon-delete); cursor: pointer; margin-left: 10px;"></i>
                        </td>
                    `;
                    tableManager.addRow(row);

                    // Count stats
                    if (p.status.toLowerCase() === "planning" || p.status.toLowerCase() === "in progress") activeCount++;
                    if (p.status.toLowerCase() === "completed") completedCount++;
                    if (new Date(p.start_date) > now) upcomingCount++;
                });

                tableManager.render();

                // Animate stats cards
                const statCards = $(".dashboard-grid .stat-card h2");
                const stats = [
                    { element: statCards[0], value: totalProjects },
                    { element: statCards[1], value: activeCount },
                    { element: statCards[2], value: completedCount },
                    { element: statCards[3], value: upcomingCount },
                ];
                animateStats(stats);
            },
            error: function () {
                showToast("AJAX error fetching projects", "error");
            }
        });
    }

    loadProjects();

    // ------------------ Pagination ------------------
    $("#nextPage").click(() => tableManager.nextPage());
    $("#prevPage").click(() => tableManager.prevPage());

    // ------------------ Filter ------------------
    $("#applyProjectFilter").click(() => {
        const status = $("#projectStatusFilter").val();
        const budget = $("#projectBudgetFilter").val();

        tableManager.setFilterCallback(row => {
            const rowStatus = $(row).find("span.status-badge").text().toLowerCase();
            const rowBudget = parseFloat($(row).find("td").eq(5).text().replace(/[^0-9.]/g, '')) || 0;

            let statusMatch = !status || rowStatus === status.toLowerCase();
            let budgetMatch = true;

            if (budget) {
                switch (budget) {
                    case 'low': budgetMatch = rowBudget < 1000000; break;
                    case 'mid': budgetMatch = rowBudget >= 1000000 && rowBudget <= 5000000; break;
                    case 'high': budgetMatch = rowBudget > 5000000; break;
                }
            }

            return statusMatch && budgetMatch;
        });

        $("#FilterMenu").hide();
    });

    // ------------------ Add Project ------------------
    $("#addProjectBtn").click(() => {
        $("#projectForm")[0].reset();
        $("#projectModalTitle").text("Add Project");
        $("#projectModalBtnText").text("Add Project");

        // Get next project ID
        $.getJSON(backendUrl, { get_next_id: 1 }, res => {
            if (res.success) $("#project_id").val(res.project_id);
            else showToast("Failed to fetch next Project ID", "error");
        });

        // Fetch active employees for manager select
        $.getJSON("../../backend/employee/backend_employee.php", { fetch_all: 1 }, res => {
            if (res.success) {
                const managerSelect = $("#managerSelect");
                managerSelect.empty().append('<option value="">Select Project Manager</option>');

                res.employees.forEach(emp => {
                    if (emp.status.toLowerCase() === "active") {
                        const fullName = emp.first_name + " " + emp.last_name;
                        managerSelect.append(`<option value="${emp.employee_id}">${fullName} (${emp.employee_id})</option>`);
                    }
                });
            }
        });

        $("#projectModal").fadeIn();
    });

    // ------------------ Edit Project ------------------
    $(document).on("click", ".edit-btn", function () {
        const projectId = $(this).data("id");

        $.getJSON(backendUrl, { fetch_id: projectId }, res => {
            if (res.success) {
                const project = res.project;

                Object.keys(project).forEach(k => {
                    $(`#${k}`).val(project[k]);
                });

                // Populate manager select
                $.getJSON("../../backend/employee/backend_employee.php", { fetch_all: 1 }, empRes => {
                    if (empRes.success) {
                        const managerSelect = $("#managerSelect");
                        managerSelect.empty().append('<option value="">Select Project Manager</option>');

                        empRes.employees.forEach(emp => {
                            if (emp.status.toLowerCase() === "active") {
                                const fullName = emp.first_name + " " + emp.last_name;
                                const selected = project.project_manager_id == emp.employee_id ? "selected" : "";
                                managerSelect.append(`<option value="${emp.employee_id}" ${selected}>${fullName}</option>`);
                            }
                        });
                        $("#projectModalTitle").text("Edit Project");
                        $("#projectModalBtnText").text("Save Changes");
                        $("#projectModal").fadeIn();
                    }
                });
            } else {
                showToast(res.message || "Error fetching project", "error");
            }
        });
    });

    // ------------------ Delete Project ------------------
    $(document).on("click", ".delete-btn", function () {
        const projectId = $(this).data("id");
        if (confirm("Are you sure you want to delete this project?")) {
            $.post(backendUrl, { delete_id: projectId }, res => {
                if (res.success) {
                    showToast(res.message, "success");
                    loadProjects();
                } else showToast(res.message || "Error deleting project", "error");
            }, "json");
        }
    });

    // ------------------ Close Modal ------------------
    function closeModal() {
        $("#projectForm")[0].reset();
        $("#projectModal").fadeOut();
    }
    $("#closeProjectModal, #cancelProjectModal").click(closeModal);

    // ------------------ Submit Add/Edit ------------------
    $("#projectForm").submit(function (e) {
        e.preventDefault();
        $.ajax({
            url: backendUrl,
            method: "POST",
            data: $(this).serialize(),
            dataType: "json",
            success: function (res) {
                if (res.success) {
                    showToast(res.message, "success");
                    loadProjects();
                    closeModal();
                } else showToast(res.message || "Error saving project", "error");
            },
            error: function () {
                showToast("AJAX error occurred", "error");
            }
        });
    });
});
</script>

  </body>
</html>
