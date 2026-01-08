<!doctype html>
<html lang="en">
  <?php 
    $pageTitle = "Labor & Workforce - Phases"; // Name of Page
    include '../components/head.php'; // All CSS links
  ?>    

  <body>
    <div class="dashboard-container">
      <?php include '../components/sidebar.php'; ?> <!-- Sidebar - Keep as is -->

      <main class="main-content" role="main">
        <?php 
          $title = "Phase Management"; 
          $breadcrumbs = [
            ['label' => 'Labor & Workforce', 'link' => null], 
            ['label' => 'Phases', 'link' => null]
          ];
          include '../components/top-bar.php'; 
        ?>

        <!-- CONTENT WRAPPER START -->
        <section class="content-wrapper" aria-labelledby="group-table-heading">
          
          <?php 
            $section = 'projects';
           include '../components/tabs.php'; 
            ?>

          <!-- Control Bar -->
          <section class="control-card">
            <div class="table-header-left">
              <h1>Phase Management</h1>
              <p>Define and manage project phases and milestones.</p>
            </div>

            <div class="table-header-right">
              <!-- Search -->
              <div class="table-search">
                <i class="fa-solid fa-magnifying-glass"></i>
                <input type="search" placeholder="Search phase or project" aria-label="Search projects" />
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
                  <div>
                    <label>Status:</label>
                    <select id="phaseStatusFilter">
                      <option value="">All</option>
                      <option value="Not Started">Not Started</option>
                      <option value="In Progress">In Progress</option>
                      <option value="Completed">Completed</option>
                    </select>

                    <br />

                    <label>Priority:</label>
                    <select id="phasePriorityFilter">
                      <option value="">All</option>
                      <option value="Low">Low</option>
                      <option value="Medium">Medium</option>
                      <option value="High">High</option>
                      <option value="Critical">Critical</option>
                    </select>

                    <br />
                    <button id="applyPhaseFilter" class="btn-primary">Apply</button>
                  </div>
                </div>
              </div>
              <!-- Action Buttons -->
              <button id="addPhaseBtn" class="btn-primary">
                <i class="fa-solid fa-plus"></i> Add Phase
              </button>
            </div>
          </section>

          <!-- Phase Statistics Section -->
          <section class="dashboard-grid" role="region" aria-label="Phase statistics">
            <div class="stat-card">
              <h3><i class="fa-solid fa-tasks stat-icon"></i> Total Phases</h3>
              <h2>0</h2>
              <p class="status-text">Across all projects</p>
            </div>

            <div class="stat-card">
              <h3><i class="fa-solid fa-sync stat-icon"></i> Active Phases</h3>
              <h2>0</h2>
              <p class="status-text">Currently in progress</p>
            </div>

            <div class="stat-card">
              <h3><i class="fa-solid fa-check stat-icon"></i> Completed Phases</h3>
              <h2>0</h2>
              <p class="status-text">Successfully finished</p>
            </div>

            <div class="stat-card">
              <h3><i class="fa-solid fa-calendar-check stat-icon"></i> Upcoming Phases</h3>
              <h2>0</h2>
              <p class="status-text">Scheduled to start</p>
            </div>
          </section>


           <section class="table-card">
          
            <!-- TABLE -->
            <div class="table-container">
              <table id="generalTable" class="styled-table">
                <thead>
                  <tr>
                    <th>Phase Name</th>
                    <th>Project</th>
                    <th>Description</th>
                    <th>Start Date</th>
                    <th>Status</th>
                    <th>Priority</th>
                    <th>Budget</th>
                    <th class="text-center">Actions</th>
                  </tr>
                </thead>
                <tbody>
                  <!-- Loaded via AJAX -->
                </tbody>
              </table>
            </div>
                  <!-- Pagination and Record Count -->
            
          </section>
            <?php include '../components/table-footer.php'; ?>
        </section>

          <?php include '../components/footer.php'; ?>
      </main>

      <?php include 'phasemodal.php'; ?> <!-- Phase Modal -->
    </div>

   <?php include '../components/scripts.php'; ?> 
 <script>
$(document).ready(function () {
    const tableManager = new TableManager({
        tableSelector: "#generalTable",
        searchSelector: ".table-search input",
        rowsPerPage: 10,
        recordCountSelector: "#recordCount",
        pageNumbersSelector: "#pageNumbers",
    });

    const backendUrl = "../../backend/projects/backend_phases.php";

    // ------------------ Load Phases ------------------
    function loadPhases() {
        $.ajax({
            url: backendUrl,
            method: "GET",
            dataType: "json",
            success: function (res) {
                if (!res.success) return showToast("Error fetching phases", "error");

                tableManager.clear();

                const today = new Date();
                let total = res.phases.length, inProgress = 0, completed = 0, notStarted = 0, upcoming = 0;

                res.phases.forEach(p => {
                    const row = document.createElement("tr");
                    row.innerHTML = `
                        <td>${p.phase_name}</td>
                        <td>${p.project_name || "-"}</td>
                        <td><div class="description-cell">${p.description || ""}</div></td>
                        <td>${p.start_date || "-"}</td>
                        <td><span class="status-badge status-${p.status.replace(" ", "-")}">${p.status}</span></td>
                        <td>${p.priority || "-"}</td>
                        <td>₱${Number(p.budget || 0).toLocaleString()}</td>
                        <td class="text-center">
                            <i class="fa-solid fa-pen-to-square action-icon edit-phase" data-id="${p.phase_id}" title="Edit" style="color: var(--icon-edit); cursor: pointer;"></i>
                            <i class="fa-solid fa-trash action-icon delete-phase" data-id="${p.phase_id}" title="Delete" style="color: var(--icon-delete); cursor: pointer; margin-left: 10px;"></i>
                        </td>
                    `;
                    tableManager.addRow(row);

                    // Count stats
                    switch (p.status.toLowerCase()) {
                        case "in progress": inProgress++; break;
                        case "completed": completed++; break;
                        case "not started": notStarted++; break;
                    }
                    if (new Date(p.start_date) > today) upcoming++;
                });

                tableManager.render();

                // Animate stats cards
                const statCards = $(".dashboard-grid .stat-card h2");
                const stats = [
                    { element: statCards[0], value: total },
                    { element: statCards[1], value: inProgress },
                    { element: statCards[2], value: completed },
                    { element: statCards[3], value: notStarted }
                ];
                animateStats(stats);
            },
            error: function () {
                showToast("AJAX error fetching phases", "error");
            }
        });
    }

    loadPhases();

    // ------------------ Filter ------------------
    $("#applyPhaseFilter").click(() => {
        const status = $("#phaseStatusFilter").val();
        const priority = $("#phasePriorityFilter").val();

        tableManager.setFilterCallback(row => {
            const rowStatus = $(row).find("span.status-badge").text().toLowerCase();
            const rowPriority = $(row).find("td").eq(5).text().toLowerCase();

            const statusMatch = !status || rowStatus === status.toLowerCase();
            const priorityMatch = !priority || rowPriority === priority.toLowerCase();

            return statusMatch && priorityMatch;
        });

        $("#FilterMenu").hide();
    });

    // ------------------ Add Phase ------------------
    $("#addPhaseBtn").click(() => {
        $("#phaseForm")[0].reset();
        $("#phaseModalTitle").text("Add Phase");
        $("#phaseModalBtnText").text("Add Phase");

        // Get next phase ID
        $.getJSON(backendUrl, { get_next_id: 1 }, res => {
            if (res.success) $("#phase_id").val(res.phase_id);
            else showToast("Failed to fetch next Phase ID", "error");
        });

        // Load projects
       $.getJSON("../../backend/projects/backend_projects.php", { fetch_all: 1 }, res => {
        if (res.success) {
            const projectSelect = $("#project_id");
            projectSelect.empty().append('<option value="">Select Project</option>');

            res.projects.forEach(proj => {  // or res.projects if your backend returns that key
                // Optional: filter if needed, e.g., only active projects
                if (proj.status.toLowerCase() === "planning" || proj.status.toLowerCase() === "in progress") {
                    projectSelect.append(
                        `<option value="${proj.project_id}">${proj.project_name}</option>`
                    );
                }
            });
        }
      });


        $("#phaseModal").fadeIn();
    });

    // ------------------ Edit Phase ------------------
    $(document).on("click", ".edit-phase", function () {
        const phaseId = $(this).data("id");

        $.getJSON(backendUrl, { fetch_id: phaseId }, res => {
            if (!res.success) return showToast(res.message || "Error fetching phase", "error");

            const p = res.record;
            Object.keys(p).forEach(k => $(`#${k}`).val(p[k]));

            // Load projects
            $.getJSON("../../backend/projects/backend_projects.php", { fetch_all: 1 }, projRes => {
                if (projRes.success) {
                    const select = $("#project_id");
                    select.empty().append('<option value="">Select Project</option>');
                    projRes.records.forEach(prj => {
                        const selected = prj.project_id === p.project_id ? "selected" : "";
                        select.append(`<option value="${prj.project_id}" ${selected}>${prj.project_name}</option>`);
                    });
                    $("#phaseModalTitle").text("Edit Phase");
                    $("#phaseModalBtnText").text("Save Changes");
                    $("#phaseModal").fadeIn();
                }
            });
        });
    });

    // ------------------ Delete Phase ------------------
    $(document).on("click", ".delete-phase", function () {
        const phaseId = $(this).data("id");
        if (!confirm("Are you sure you want to delete this phase?")) return;

        $.post(backendUrl, { delete_id: phaseId }, res => {
            if (res.success) {
                showToast(res.message, "success");
                loadPhases();
            } else showToast(res.message || "Error deleting phase", "error");
        }, "json");
    });

    // ------------------ Submit Add/Edit Phase ------------------
    $("#phaseForm").submit(function (e) {
        e.preventDefault();
        $.ajax({
            url: backendUrl,
            method: "POST",
            data: $(this).serialize(),
            dataType: "json",
            success: function (res) {
                if (res.success) {
                    showToast(res.message, "success");
                    loadPhases();
                    $("#phaseModal").fadeOut();
                    $("#phaseForm")[0].reset();
                } else showToast(res.message || "Error saving phase", "error");
            },
            error: function () {
                showToast("AJAX error occurred", "error");
            }
        });
    });

    // ------------------ Close Modal ------------------
    $("#closePhaseModal, #cancelPhaseModal").click(() => {
        $("#phaseForm")[0].reset();
        $("#phaseModal").fadeOut();
    });
});
</script>

  </body>
</html>
