<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Workforce & Labor - Tasks</title>
 <link
      rel="stylesheet"
      href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200"
    />
    <link rel="stylesheet" href="../css/main.css" />
    <link rel="stylesheet" href="../css/tab.css" />
    <link rel="stylesheet" href="../css/sidebar.css" />
    <link rel="stylesheet" href="../css/tablestyle.css" />
    <link rel="stylesheet" href="../css/dataTables.min.css" />
  </head>
<body>

    <?php include '../components/sidebar.php'?>
    <main class="main-container" role="main">
      <?php $title = "Task Management "; $subtitle = "Define and manage project tasks and sub-tasks."; 
      include '../components/header.php'; ?>
    

    <!-- TASK SUMMARY CARDS -->
<section class="stats-container" role="region" aria-label="Task statistics">
  <!-- Total Tasks -->
  <div class="stat-card">
    <div class="stat-icon icon-blue">
      <span class="material-symbols-outlined">assignment</span>
    </div>
    <h4>Total Tasks</h4>
    <h2 class="value">86</h2>
    <p>Across all projects</p>
  </div>

  <!-- Active Tasks -->
  <div class="stat-card">
    <div class="stat-icon icon-green">
      <span class="material-symbols-outlined">autorenew</span>
    </div>
    <h4>Active Tasks</h4>
    <h2 class="value">42</h2>
    <p>Currently in progress</p>
  </div>

  <!-- Completed Tasks -->
  <div class="stat-card">
    <div class="stat-icon icon-gold">
      <span class="material-symbols-outlined">check_circle</span>
    </div>
    <h4>Completed Tasks</h4>
    <h2 class="value">124</h2>
    <p>Successfully finished</p>
  </div>

  <!-- Overdue Tasks -->
  <div class="stat-card">
    <div class="stat-icon icon-red">
      <span class="material-symbols-outlined">priority_high</span>
    </div>
    <h4>Overdue Tasks</h4>
    <h2 class="value">3</h2>
    <p>Require immediate attention</p>
  </div>
</section>

 <?php include 'tabs.php'; ?>


    <!-- TASK MANAGEMENT TABLE -->
<section class="general-table-container" role="region" aria-label="Task Management">
  <!-- TABLE HEADER -->
  <div class="table-header">
    <div class="table-header-left">
      <!-- SEARCH -->
      <div class="table-search">
        <span class="material-symbols-outlined">search</span>
        <input type="search" placeholder="Search task, project, or phase" aria-label="Search tasks"/>
      </div>

      <!-- FILTER -->
      <div class="filter-dropdown" style="position: relative">
        <button id="taskFilterBtn" class="filter-btn">
          <span class="material-symbols-outlined">filter_list</span>
          Filter
        </button>

        <div id="taskFilterMenu" class="filter-menu" style="display: none; position: absolute; top: 100%; left: 0; background: #fff; border: 1px solid #ccc; padding: 10px; z-index: 10;">
          <label>Status:</label>
          <select id="taskStatusFilter">
            <option value="">All</option>
            <option value="Not Started">Not Started</option>
            <option value="In Progress">In Progress</option>
            <option value="On Hold">On Hold</option>
            <option value="Completed">Completed</option>
          </select>
          <br/>

          <label>Priority:</label>
          <select id="taskPriorityFilter">
            <option value="">All</option>
            <option value="Low">Low</option>
            <option value="Medium">Medium</option>
            <option value="High">High</option>
            <option value="Critical">Critical</option>
          </select>
          <br/>

          <button id="applyTaskFilter" class="apply-filter-btn">Apply</button>
        </div>
      </div>
    </div>

    <!-- HEADER RIGHT ACTIONS -->
    <div class="table-header-right">
      <button id="addTaskBtn" class="add-btn">
        <span class="material-symbols-outlined">add</span>
        Add Task
      </button>

      <button class="export-btn" onclick="exportTasksToCSV()">
        <span class="material-symbols-outlined">download</span>
        Export
      </button>

      <button class="export-btn" onclick="printTasks()">
        <span class="material-symbols-outlined">print</span>
        Print
      </button>
    </div>
  </div>

  <!-- TABLE -->
  <div class="table-container">
    <table id="tasksTable" class="general-table">
      <thead>
        <tr>
          <th>Task Name</th>
          <th>Project</th>
          <th>Phase</th>
          <th>Description</th>
          <th>Start Date</th>
          <th>End Date</th>
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

    <!-- PAGINATION -->
    <div class="custom-footer">
      <button class="page-btn" id="prevTaskPage">Prev</button>
      <span id="taskPageNumbers"></span>
      <button class="page-btn" id="nextTaskPage">Next</button>
    </div>

    <!-- RECORD COUNT -->
    <div class="records-footer">
      <span id="taskRecordCount" class="records-count">Showing 0 of 0 tasks</span>
    </div>
  </div>
</section>

</main>
    <?php include 'taskmodal.php'?>

    <script src="../js/jquery.min.js"></script>
    <script src="../js/dataTables.min.js"></script>
    <script src="../js/main.js"></script>
  <script>
   $(document).ready(function(){


   });
  </script>
</body>
</html>