<!doctype html>
<html lang="en">
  <?php 
    $pageTitle = "Labor & Workforce - Tasks"; 
    include '../components/head.php'; 
  ?>

  <style>
    /* --- Status Badge Styles --- */
    .status-badge {
        padding: 5px 12px;
        border-radius: 20px;
        font-size: 0.85rem;
        font-weight: 600;
        color: white; 
        display: inline-block;
        min-width: 80px;
        text-align: center;
    }
    .status-not-started { background-color: #6c757d; }
    .status-in-progress { background-color: #0d6efd; }
    .status-on-hold { background-color: #ffc107; color: #000; }
    .status-completed { background-color: #198754; }
    
    /* --- Priority Badge Styles --- */
    .priority-badge {
        padding: 4px 8px;
        border-radius: 4px;
        font-size: 0.8rem;
        font-weight: bold;
    }
    .priority-low { color: #198754; background: #d1e7dd; }
    .priority-medium { color: #fd7e14; background: #ffe5d0; }
    .priority-high { color: #dc3545; background: #f8d7da; }
    .priority-critical { color: #fff; background: #dc3545; }

    /* --- Action Icons --- */
    .action-icon {
        cursor: pointer;
        font-size: 1.1rem;
        padding: 5px;
        transition: transform 0.2s;
        margin: 0 5px;
    }
    .action-icon:hover { transform: scale(1.2); }
    .edit-icon { color: #0d6efd; }
    .delete-icon { color: #dc3545; }

    /* --- FILTER MENU STYLES (NEW) --- */
    .table-header-right {
        position: relative; /* Anchor for the absolute menu */
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .filter-menu {
        position: absolute;
        top: 100%; /* Push it down below the button */
        right: 120px; /* Adjust based on your layout to align with button */
        width: 250px;
        background: white;
        border: 1px solid #ddd;
        border-radius: 8px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        padding: 15px;
        z-index: 1000;
        margin-top: 5px;
    }
    
    .filter-options label {
        display: block;
        margin-bottom: 5px;
        font-weight: 600;
        font-size: 0.9rem;
    }
    
    .filter-options select {
        width: 100%;
        padding: 8px;
        border: 1px solid #ccc;
        border-radius: 4px;
        margin-bottom: 15px;
    }
  </style>

  <body>
    <div class="dashboard-container">
      <?php include '../components/sidebar.php'; ?> 

      <main class="main-content" role="main">
        <?php 
          $title = "Task Management"; 
          $breadcrumbs = [ 
            ['label' => 'Projects', 'link' => 'projects.php'],
            ['label' => 'Tasks', 'link' => null]
          ]; 
          include '../components/top-bar.php'; 
        ?> 

        <section class="content-wrapper">
          <?php
            $section = 'projects';
            include '../components/tabs.php'; 
          ?> 

          <section class="control-card">
            <div class="table-header-left">
              <h1>Task Management</h1>
              <p>Define and manage project tasks, assignments, and hours.</p>
            </div>

            <div class="table-header-right">
              <div class="table-search">
                <i class="fa-solid fa-magnifying-glass"></i>
                <input type="search" placeholder="Search tasks..." aria-label="Search tasks"/>
              </div>

              <div class="filter-dropdown">
                <button id="FilterBtn" class="btn-secondary">
                  <i class="fa-solid fa-filter"></i> Filter
                </button>
              </div>

              <div id="FilterMenu" class="filter-menu" style="display: none;">
                <div class="filter-options">
                  <label>Status</label>
                  <select id="taskStatusFilter">
                    <option value="">All Statuses</option>
                    <option value="Not Started">Not Started</option>
                    <option value="In Progress">In Progress</option>
                    <option value="On Hold">On Hold</option>
                    <option value="Completed">Completed</option>
                  </select>
                  
                  <label>Priority</label>
                  <select id="taskPriorityFilter">
                    <option value="">All Priorities</option>
                    <option value="Low">Low</option>
                    <option value="Medium">Medium</option>
                    <option value="High">High</option>
                    <option value="Critical">Critical</option>
                  </select>
                  
                  <button id="applyTaskFilter" class="btn-primary" style="width: 100%;">Apply Filters</button>
                </div>
              </div>

              <button id="addTaskBtn" class="btn-primary">
                <i class="fa-solid fa-plus"></i> Add Task
              </button>
            </div>
          </section>

          <section class="dashboard-grid">
            <div class="stat-card">
              <h3><i class="fa-solid fa-list-check stat-icon" style="color: var(--primary-color);"></i> Total Tasks</h3>
              <h2 id="statTotal">0</h2>
            </div>
            <div class="stat-card">
              <h3><i class="fa-solid fa-spinner stat-icon" style="color: var(--success-color);"></i> In Progress</h3>
              <h2 id="statActive">0</h2>
            </div>
            <div class="stat-card">
              <h3><i class="fa-solid fa-clock stat-icon" style="color: #f59e0b;"></i> Est. Hours</h3>
              <h2 id="statHours">0</h2>
            </div>
            <div class="stat-card">
              <h3><i class="fa-solid fa-triangle-exclamation stat-icon" style="color: var(--danger-color);"></i> Overdue</h3>
              <h2 id="statOverdue">0</h2>
            </div>
          </section>

          <section class="table-card">
            <div class="table-container">
              <table id="generalTable" class="styled-table">
                <thead>
                  <tr>
                    <th>Task Name</th>
                    <th>Project / Phase</th>
                    <th>Assigned To</th>
                    <th>Due Date</th>
                    <th>Status</th>
                    <th>Priority</th>
                    <th>Hours</th>
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

      <div id="taskModal" class="modal" style="display: none;">
        <div class="modal-content">
          <div class="modal-header">
            <h2 id="taskModalTitle">Add New Task</h2>
            <span class="close" id="closeTaskModal">&times;</span>
          </div>
          
          <form id="taskForm">
            <div class="modal-body">
              <div class="form-row" style="display: flex; gap: 15px; margin-bottom: 15px;">
                <div class="form-group" style="flex: 1;">
                  <label for="task_id">Task ID</label>
                  <input type="text" id="task_id" name="task_id" readonly required class="readonly-input" style="background: #f0f0f0;">
                </div>
                <div class="form-group" style="flex: 2;">
                  <label for="task_name">Task Name</label>
                  <input type="text" id="task_name" name="task_name" required placeholder="Enter task name">
                </div>
              </div>

              <div class="form-row" style="display: flex; gap: 15px; margin-bottom: 15px;">
                <div class="form-group" style="flex: 1;">
                  <label for="project_id">Project</label>
                  <select id="project_id" name="project_id" required>
                      <option value="">Loading...</option>
                  </select>
                </div>
                <div class="form-group" style="flex: 1;">
                  <label for="phase_id">Phase</label>
                  <select id="phase_id" name="phase_id">
                      <option value="">Select Project First</option>
                  </select>
                </div>
              </div>

              <div class="form-row" style="display: flex; gap: 15px; margin-bottom: 15px;">
                <div class="form-group" style="flex: 1;">
                  <label for="assigned_to">Assigned To</label>
                  <input type="text" id="assigned_to" name="assigned_to" placeholder="Employee Name">
                </div>
                <div class="form-group" style="flex: 1;">
                  <label for="department">Department</label>
                  <input type="text" id="department" name="department" placeholder="e.g. IT, HR">
                </div>
              </div>

              <div class="form-row" style="display: flex; gap: 15px; margin-bottom: 15px;">
                 <div class="form-group" style="flex: 1;">
                  <label for="due_date">Due Date</label>
                  <input type="date" id="due_date" name="due_date" required>
                </div>
                <div class="form-group" style="flex: 1;">
                  <label for="estimated_hours">Est. Hours</label>
                  <input type="number" id="estimated_hours" name="estimated_hours" step="0.5" placeholder="0.0">
                </div>
              </div>

               <div class="form-row" style="display: flex; gap: 15px; margin-bottom: 15px;">
                <div class="form-group" style="flex: 1;">
                  <label for="status">Status</label>
                  <select id="status" name="status">
                    <option value="Not Started">Not Started</option>
                    <option value="In Progress">In Progress</option>
                    <option value="On Hold">On Hold</option>
                    <option value="Completed">Completed</option>
                  </select>
                </div>
                <div class="form-group" style="flex: 1;">
                  <label for="priority">Priority</label>
                  <select id="priority" name="priority">
                    <option value="Low">Low</option>
                    <option value="Medium" selected>Medium</option>
                    <option value="High">High</option>
                    <option value="Critical">Critical</option>
                  </select>
                </div>
              </div>

              <div class="form-group" style="margin-bottom: 15px;">
                <label for="description">Description</label>
                <textarea id="description" name="description" rows="3" placeholder="Task details..." style="width: 100%;"></textarea>
              </div>
            </div>

            <div class="modal-footer" style="text-align: right; padding-top: 10px; border-top: 1px solid #ddd;">
              <button type="button" class="btn-secondary" id="cancelTaskModal">Cancel</button>
              <button type="submit" class="btn-primary" id="taskModalBtnText">Save Task</button>
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

      const backendUrl = "../../backend/projects/backend_tasks.php";

      // 2. Load Tasks
      function loadTasks() {
          $.ajax({
              url: backendUrl,
              method: "GET",
              dataType: "json",
              success: function (res) {
                  if (!res.success) return showToast(res.message || "Error", "error");

                  tableManager.clear();
                  let total = 0, active = 0, totalHours = 0, overdue = 0;
                  const now = new Date();

                  res.tasks.forEach(t => {
                      total++;
                      if (t.status === 'In Progress') active++;
                      totalHours += parseFloat(t.estimated_hours || 0);
                      
                      const dueDate = new Date(t.due_date);
                      if (t.due_date && dueDate < now && t.status !== 'Completed') overdue++;

                      const projectPhase = `<small><b>P:</b> ${t.project_name || '-'}</small><br><small><b>Ph:</b> ${t.phase_name || '-'}</small>`;
                      const displayStatus = t.status ? t.status : 'Not Started'; 
                      const statusClass = `status-${displayStatus.toLowerCase().replace(/\s+/g, '-')}`;
                      const priorityClass = t.priority ? `priority-${t.priority.toLowerCase()}` : 'priority-medium';

                      const row = document.createElement("tr");
                      row.innerHTML = ` 
                          <td>
                              <strong>${t.task_name}</strong><br>
                              <small class="text-muted">${t.description ? t.description.substring(0, 30) + '...' : ''}</small>
                          </td>
                          <td>${projectPhase}</td>
                          <td>${t.assigned_to || '<span class="text-muted">Unassigned</span>'}</td>
                          <td>${t.due_date || '-'}</td>
                          <td><span class="status-badge ${statusClass}">${displayStatus}</span></td>
                          <td><span class="priority-badge ${priorityClass}">${t.priority || 'Medium'}</span></td>
                          <td>${t.estimated_hours || 0} hrs</td>
                          <td class="text-center">
                              <i class="fa-solid fa-pen-to-square action-icon edit-icon edit-task" data-id="${t.task_id}" title="Edit"></i>
                              <i class="fa-solid fa-trash action-icon delete-icon delete-task" data-id="${t.task_id}" title="Delete"></i>
                          </td>
                      `;
                      tableManager.addRow(row);
                  });

                  tableManager.render();
                  
                  if(document.getElementById('statTotal')) {
                    document.getElementById('statTotal').innerText = total;
                    document.getElementById('statActive').innerText = active;
                    document.getElementById('statHours').innerText = Math.round(totalHours);
                    document.getElementById('statOverdue').innerText = overdue;
                  }
              },
              error: function () { console.log("Backend Error"); }
          });
      }

      loadTasks();

      // ==========================================
      // 3. FILTER LOGIC (UPDATED)
      // ==========================================
      
      // Toggle Menu
      $("#FilterBtn").click((e) => { 
        e.stopPropagation(); 
        $("#FilterMenu").toggle(); 
      });

      // Close menu when clicking outside
      $(document).click((e) => { 
        if (!$(e.target).closest('.filter-dropdown, #FilterMenu').length) {
          $("#FilterMenu").hide(); 
        }
      });

      // Apply Filter
      $("#applyTaskFilter").click(() => {
          // Get selected values and convert to lowercase for easier comparison
          const status = $("#taskStatusFilter").val().toLowerCase();
          const priority = $("#taskPriorityFilter").val().toLowerCase();

          tableManager.setFilterCallback(row => {
              // Get text from the row's badges
              const rowStatus = $(row).find("span.status-badge").text().trim().toLowerCase();
              const rowPriority = $(row).find("span.priority-badge").text().trim().toLowerCase();

              // Check logic: if filter is empty OR if matches
              let statusMatch = (status === "") || (rowStatus === status);
              let priorityMatch = (priority === "") || (rowPriority === priority);

              return statusMatch && priorityMatch;
          });
          
          $("#FilterMenu").hide();
      });

      // ==========================================
      // 4. ADD / EDIT / DELETE / FORM LOGIC
      // ==========================================
      
      $("#addTaskBtn").click(() => {
          $("#taskForm")[0].reset();
          $("#taskModalTitle").text("Add New Task");
          $("#taskModalBtnText").text("Add Task");
          $.getJSON(backendUrl, { get_next_id: 1 }, res => {
            if(res.success) $("#task_id").val(res.next_id);
          });
          loadProjectsDropdown();
          $("#taskModal").fadeIn();
      });

      $(document).on("click", ".edit-task", function() {
          const id = $(this).data("id");
          $.getJSON(backendUrl, { fetch_id: id }, res => {
              if (res.success) {
                  const r = res.record;
                  $("#task_id").val(r.task_id);
                  $("#task_name").val(r.task_name);
                  $("#description").val(r.description);
                  $("#assigned_to").val(r.assigned_to);
                  $("#due_date").val(r.due_date);
                  $("#status").val(r.status);
                  $("#priority").val(r.priority);
                  $("#estimated_hours").val(r.estimated_hours);
                  $("#department").val(r.department);
                  loadProjectsDropdown(r.project_id, r.phase_id);
                  $("#taskModalTitle").text("Edit Task");
                  $("#taskModalBtnText").text("Save Changes");
                  $("#taskModal").fadeIn();
              } else {
                  showToast("Error fetching task", "error");
              }
          });
      });

      $(document).on("click", ".delete-task", function () {
          const id = $(this).data("id");
          if (confirm("Are you sure you want to delete this task?")) {
              $.post(backendUrl, { delete_id: id }, res => {
                  if (res.success) { showToast(res.message, "success"); loadTasks(); } 
                  else { showToast(res.message, "error"); }
              }, "json");
          }
      });

      $("#taskForm").submit(function(e) {
          e.preventDefault();
          $.post(backendUrl, $(this).serialize(), res => {
              if (res.success) { showToast(res.message, "success"); loadTasks(); $("#taskModal").fadeOut(); } 
              else { showToast(res.message, "error"); }
          }, "json");
      });

      function loadProjectsDropdown(selectedProject = null, selectedPhase = null) {
          $.getJSON("../../backend/projects/backend_projects.php", { fetch_all: 1 }, res => {
              if(res.success) {
                  let html = '<option value="">Select Project</option>';
                  if(res.projects) {
                      res.projects.forEach(p => {
                          const sel = (selectedProject && p.project_id == selectedProject) ? 'selected' : '';
                          html += `<option value="${p.project_id}" ${sel}>${p.project_name}</option>`;
                      });
                  }
                  $("#project_id").html(html);
                  if(selectedProject) loadPhasesDropdown(selectedProject, selectedPhase);
                  else $("#phase_id").html('<option value="">Select Project First</option>');
              }
          });
      }

      $("#project_id").change(function() { loadPhasesDropdown($(this).val()); });

      function loadPhasesDropdown(projectId, selectedPhase = null) {
          if(!projectId) { $("#phase_id").html('<option value="">Select Project First</option>'); return; }
          $.post("../../backend/projects/backend_phases.php", { project_id: projectId }, res => {
              if(res.success) {
                  let html = '<option value="">Select Phase</option>';
                  if(res.phases && res.phases.length > 0) {
                      res.phases.forEach(ph => {
                          const sel = (selectedPhase && ph.phase_id == selectedPhase) ? 'selected' : '';
                          html += `<option value="${ph.phase_id}" ${sel}>${ph.phase_name}</option>`;
                      });
                  } else { html = '<option value="">No phases found</option>'; }
                  $("#phase_id").html(html);
              }
          }, "json");
      }

      $("#closeTaskModal, #cancelTaskModal").click(() => $("#taskModal").fadeOut());
  });
  </script>

  </body>
</html>