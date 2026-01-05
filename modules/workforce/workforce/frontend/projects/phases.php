<!doctype html>
<html lang="en">
  <?php 
    $pageTitle = "Labor & Workforce - Phases"; 
    include '../components/head.php'; 
  ?>    

  <style>
    .status-badge {
        padding: 5px 12px;
        border-radius: 20px;
        font-size: 0.85rem;
        font-weight: 600;
        color: white;
        display: inline-block;
        min-width: 90px;
        text-align: center;
        text-transform: capitalize;
    }
    .status-not-started { background-color: #6c757d; }
    .status-in-progress { background-color: #0d6efd; }
    .status-completed { background-color: #198754; }
    
    .priority-badge { font-weight: bold; }
    .priority-low { color: #198754; }
    .priority-medium { color: #fd7e14; }
    .priority-high { color: #dc3545; }
    .priority-critical { color: #dc3545; font-weight: 900; text-transform: uppercase; }

    .description-cell {
        max-width: 250px;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        color: #666;
    }
  </style>

  <body>
    <div class="dashboard-container">
      <?php include '../components/sidebar.php'; ?> 

      <main class="main-content" role="main">
        <?php 
          $title = "Phase Management"; 
          $breadcrumbs = [
            ['label' => 'Labor & Workforce', 'link' => null], 
            ['label' => 'Phases', 'link' => null]
          ];
          include '../components/top-bar.php'; 
        ?>

        <section class="content-wrapper" aria-labelledby="group-table-heading">
          <?php 
            $section = 'projects';
            include '../components/tabs.php'; 
          ?>

          <section class="control-card">
            <div class="table-header-left">
              <h1>Phase Management</h1>
              <p>Define and manage project phases and milestones.</p>
            </div>

            <div class="table-header-right">
              <div class="table-search">
                <i class="fa-solid fa-magnifying-glass"></i>
                <input type="search" placeholder="Search phase or project" aria-label="Search projects" />
              </div>

              <div class="filter-dropdown">
                <button id="FilterBtn" class="btn-secondary">
                  <i class="fa-solid fa-filter"></i> Filter
                </button>
              </div>

              <div id="FilterMenu" class="filter-menu" style="display: none;">
                <div class="filter-options">
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
                  <button id="applyPhaseFilter" class="btn-primary" style="width: 100%; margin-top: 10px;">Apply</button>
                </div>
              </div>

              <button id="addPhaseBtn" class="btn-primary">
                <i class="fa-solid fa-plus"></i> Add Phase
              </button>
            </div>
          </section>

          <section class="dashboard-grid" role="region" aria-label="Phase statistics">
            <div class="stat-card">
              <h3><i class="fa-solid fa-tasks stat-icon"></i> Total Phases</h3>
              <h2 id="statTotal">0</h2>
              <p class="status-text">Across all projects</p>
            </div>
            <div class="stat-card">
              <h3><i class="fa-solid fa-sync stat-icon"></i> Active Phases</h3>
              <h2 id="statActive">0</h2>
              <p class="status-text">Currently in progress</p>
            </div>
            <div class="stat-card">
              <h3><i class="fa-solid fa-check stat-icon"></i> Completed Phases</h3>
              <h2 id="statCompleted">0</h2>
              <p class="status-text">Successfully finished</p>
            </div>
            <div class="stat-card">
              <h3><i class="fa-solid fa-calendar-check stat-icon"></i> Upcoming Phases</h3>
              <h2 id="statUpcoming">0</h2>
              <p class="status-text">Scheduled to start</p>
            </div>
          </section>

           <section class="table-card">
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
                <tbody></tbody>
              </table>
            </div>
          </section>
           <?php include '../components/table-footer.php'; ?>
        </section>

        <?php include '../components/footer.php'; ?>
      </main>

      <div id="phaseModal" class="modal" style="display: none;">
        <div class="modal-content">
            <div class="modal-header">
                <h2 id="phaseModalTitle">Add Phase</h2>
                <span class="close" id="closePhaseModal" style="cursor:pointer;">&times;</span>
            </div>
            <form id="phaseForm">
                <div class="modal-body">
                     <div class="form-group" style="margin-bottom: 15px;">
                        <label>Phase ID</label>
                        <input type="text" id="phase_id" name="phase_id" readonly style="background: #f0f0f0;">
                    </div>

                    <div class="form-group" style="margin-bottom: 15px;">
                        <label>Phase Name *</label>
                        <input type="text" id="phase_name" name="phase_name" required placeholder="e.g. Foundation">
                    </div>

                    <div class="form-group" style="margin-bottom: 15px;">
                        <label>Associated Project *</label>
                        <select id="project_id" name="project_id" required>
                            <option value="">Select Project</option>
                        </select>
                    </div>

                    <div class="form-group" style="margin-bottom: 15px;">
                        <label>Description</label>
                        <textarea id="description" name="description" rows="3" placeholder="Brief description..."></textarea>
                    </div>

                    <div class="form-row" style="display: flex; gap: 15px; margin-bottom: 15px;">
                        <div class="form-group" style="flex: 1;">
                            <label>Start Date</label>
                            <input type="date" id="start_date" name="start_date">
                        </div>
                        <div class="form-group" style="flex: 1;">
                            <label>Expected Completion</label>
                            <input type="date" id="end_date" name="end_date">
                        </div>
                    </div>

                    <div class="form-row" style="display: flex; gap: 15px; margin-bottom: 15px;">
                        <div class="form-group" style="flex: 1;">
                            <label>Status</label>
                            <select id="status" name="status">
                                <option value="Not Started">Not Started</option>
                                <option value="In Progress">In Progress</option>
                                <option value="Completed">Completed</option>
                            </select>
                        </div>
                        <div class="form-group" style="flex: 1;">
                            <label>Priority</label>
                            <select id="priority" name="priority">
                                <option value="Low">Low</option>
                                <option value="Medium" selected>Medium</option>
                                <option value="High">High</option>
                                <option value="Critical">Critical</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Budget (₱)</label>
                        <input type="number" id="budget" name="budget" step="0.01" placeholder="e.g. 50000">
                    </div>
                </div>
                <div class="modal-footer" style="padding-top: 15px; text-align: right; border-top: 1px solid #ddd;">
                    <button type="button" class="btn-secondary" id="cancelPhaseModal">Cancel</button>
                    <button type="submit" class="btn-primary" id="phaseModalBtnText">Add Phase</button>
                </div>
            </form>
        </div>
      </div>
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

    // 1. LOAD PHASES
    function loadPhases() {
        $.ajax({
            url: backendUrl,
            method: "GET",
            dataType: "json",
            success: function (res) {
                if (!res.success) return; 

                tableManager.clear();
                let total = 0, inProgress = 0, completed = 0, upcoming = 0;
                const today = new Date();

                if (res.phases) {
                    res.phases.forEach(p => {
                        total++;
                        // Safe Values for nulls
                        const safeStatus = p.status || 'Not Started';
                        const safePriority = p.priority || 'Medium';
                        const safeBudget = p.budget ? parseFloat(p.budget) : 0;

                        // CSS Classes
                        const statusClass = `status-${safeStatus.toLowerCase().replace(/\s+/g, '-')}`;
                        const priorityClass = `priority-${safePriority.toLowerCase()}`;
                        
                        const row = document.createElement("tr");
                        row.innerHTML = `
                            <td><strong>${p.phase_name}</strong></td>
                            <td>${p.project_name || '<span style="color:#ccc">None</span>'}</td>
                            <td><div class="description-cell">${p.description || '-'}</div></td>
                            <td>${p.start_date || '-'}</td>
                            <td><span class="status-badge ${statusClass}">${safeStatus}</span></td>
                            <td><span class="${priorityClass}">${safePriority}</span></td>
                            <td>₱${safeBudget.toLocaleString()}</td>
                            <td class="text-center">
                                <i class="fa-solid fa-pen-to-square action-icon edit-phase" data-id="${p.phase_id}" title="Edit" style="color: var(--icon-edit); cursor:pointer;"></i>
                                <i class="fa-solid fa-trash action-icon delete-phase" data-id="${p.phase_id}" title="Delete" style="color: var(--icon-delete); cursor:pointer; margin-left:10px;"></i>
                            </td>
                        `;
                        tableManager.addRow(row);

                        if(safeStatus === 'In Progress') inProgress++;
                        if(safeStatus === 'Completed') completed++;
                        if(safeStatus === 'Not Started' && new Date(p.start_date) > today) upcoming++;
                    });
                }
                tableManager.render();
                
                if(document.getElementById('statTotal')) {
                    document.getElementById('statTotal').innerText = total;
                    document.getElementById('statActive').innerText = inProgress;
                    document.getElementById('statCompleted').innerText = completed;
                    document.getElementById('statUpcoming').innerText = upcoming;
                }
            }
        });
    }
    loadPhases();

    // 2. ADD PHASE (FIX: Fetches ID and Projects without 'undefined' errors)
    $("#addPhaseBtn").click(() => {
        $("#phaseForm")[0].reset();
        $("#phaseModalTitle").text("Add Phase");
        $("#phaseModalBtnText").text("Add Phase");

        // Fetch Next ID 
        $.getJSON(backendUrl, { get_next_id: 1 }, res => {
            if(res.success) {
                $("#phase_id").val(res.next_id); 
            }
        });

        // Load Projects
        $.getJSON("../../backend/projects/backend_projects.php", { fetch_all: 1 }, res => {
            if(res.success && res.projects) {
                let opts = '<option value="">Select Project</option>';
                res.projects.forEach(p => {
                    opts += `<option value="${p.project_id}">${p.project_name}</option>`;
                });
                $("#project_id").html(opts);
            }
        });

        $("#phaseModal").fadeIn();
    });

    // 3. SUBMIT FORM
    $("#phaseForm").submit(function (e) {
        e.preventDefault();
        
        if($("#project_id").val() === "") {
            alert("Please select a project.");
            return;
        }

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
                } else {
                    showToast(res.message, "error"); 
                }
            },
            error: function (xhr) {
                console.error(xhr.responseText);
                showToast("System Error", "error");
            }
        });
    });

    // 4. EDIT PHASE
    $(document).on("click", ".edit-phase", function () {
        const id = $(this).data("id");
        
        $.getJSON(backendUrl, { fetch_id: id }, res => {
            if(res.success) {
                const p = res.phase;
                $("#phase_id").val(p.phase_id); 
                $("#phase_name").val(p.phase_name);
                $("#description").val(p.description);
                $("#start_date").val(p.start_date);
                $("#end_date").val(p.end_date);
                $("#status").val(p.status);
                $("#priority").val(p.priority);
                $("#budget").val(p.budget);

                // Load Projects
                $.getJSON("../../backend/projects/backend_projects.php", { fetch_all: 1 }, projRes => {
                    if(projRes.success) {
                        let opts = '<option value="">Select Project</option>';
                        projRes.projects.forEach(proj => {
                            const sel = (proj.project_id == p.project_id) ? 'selected' : '';
                            opts += `<option value="${proj.project_id}" ${sel}>${proj.project_name}</option>`;
                        });
                        $("#project_id").html(opts);
                    }
                });

                $("#phaseModalTitle").text("Edit Phase");
                $("#phaseModalBtnText").text("Save Changes");
                $("#phaseModal").fadeIn();
            }
        });
    });

    // 5. DELETE
    $(document).on("click", ".delete-phase", function () {
        if(confirm("Are you sure?")) {
            const id = $(this).data("id");
            $.post(backendUrl, { delete_id: id }, res => {
                if(res.success) {
                    showToast(res.message, "success");
                    loadPhases();
                } else {
                    showToast(res.message, "error");
                }
            }, "json");
        }
    });

    // UI Helpers
    $("#closePhaseModal, #cancelPhaseModal").click(() => $("#phaseModal").fadeOut());
    
    $("#FilterBtn").click((e) => { e.stopPropagation(); $("#FilterMenu").toggle(); });
    $(document).click((e) => { if (!$(e.target).closest('.filter-dropdown, .filter-menu').length) $("#FilterMenu").hide(); });
    
    $("#applyPhaseFilter").click(() => {
        const status = $("#phaseStatusFilter").val();
        const priority = $("#phasePriorityFilter").val();
        tableManager.setFilterCallback(row => {
            const rStatus = $(row).find(".status-badge").text();
            const rPriority = $(row).find("span[class^='priority-']").text();
            return (!status || rStatus === status) && (!priority || rPriority === priority);
        });
        $("#FilterMenu").hide();
    });
});
</script>
</body>
</html>