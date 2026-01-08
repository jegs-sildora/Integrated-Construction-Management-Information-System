<!DOCTYPE html>
<html lang="en">
<?php
  $pageTitle = "Employee Assignments - Workforce Management";
  include '../components/head.php';
?>

<body>
  <section class="dashboard-container">

    <!-- Sidebar -->
    <?php include '../components/sidebar.php'; ?>

    <main class="main-content" role="main">
      <!-- Top Bar / Breadcrumbs -->
      <?php
        $title = "Employee Assignments";
        $breadcrumbs = [
          ['label' => 'Labor & Workforce > Employees', 'link' => null],
          ['label' => $title, 'link' => null]
        ];
        include '../components/top-bar.php';
      ?>

      <!-- ================= CONTENT WRAPPER ================= -->
      <section class="content-wrapper">

         <?php include 'employee_profile/employee_header.php'; ?>

        <?php 
          $activeTab = 'assignments';
          $employeeID = $_GET['id'] ?? '';
          include 'employee_profile/employee_tabs.php';
        ?>


        <!-- ================= CONTENTS ================= -->
        
        
     <!-- Table -->
        <section class="table-card">
          <table id="generalTable" class="styled-table">
           <thead>
            <tr>
              <th>Project</th>
              <th>Task</th>
              <th>Start Date</th>
              <th>End Date</th>
              <th>Status</th>
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


  <?php include '../components/scripts.php'; ?>
 <script src="employee_profile/employee_script.js"></script>
<script>
$(document).ready(function () {


    
   const activeTab = '<?php echo $activeTab ?? "personal"; ?>'; 

  // Hide edit button for non-personal pages
  if (activeTab !== 'personal') {
    $('#editProfileBtn').hide();
  } else {
    $('#editProfileBtn').show();
  }

  // -------------------- Employee Header --------------------
  // Using the refactored EmployeeHeader component (no options needed if declared inside)
  new EmployeeHeader();

  // -------------------- Employee Assignments Module --------------------
  (function ($, window) {
    function EmployeeAssignments() {
      // Internal variables (no need to pass)
      const backendUrl = "../../backend/employee/backend_employee.php";
      const employeeID = $('body').data('employee-id') || new URLSearchParams(window.location.search).get('id');

      if (!employeeID) {
        console.error("Employee ID not found!");
        return;
      }

      // Table Manager
      const tableManager = new TableManager({
        tableSelector: "#generalTable",
        searchSelector: ".table-search input",
        rowsPerPage: 10,
        recordCountSelector: "#recordCount",
        pageNumbersSelector: "#pageNumbers",
      });

      // Load assignments
    function loadAssignments() {
  $.ajax({
    url: "../../backend/employee/backend_employee.php",
    method: "GET",
    data: { fetch_id: employeeID },
    dataType: "json",
    success: function(res) {
      if (!res.success) {
        return showToast(res.message || "Error fetching assignments", "error");
      }

      tableManager.clear();

      const assignments = Array.isArray(res.employee.assignments) ? res.employee.assignments : [];

      if (assignments.length === 0) {
        // Show a single row indicating no assignments
        const noRow = document.createElement("tr");
        noRow.innerHTML = `<td colspan="5" style="text-align:center;">No assignments found</td>`;
        tableManager.addRow(noRow);
        tableManager.render();
        return;
      }

      assignments.forEach(a => {
        const row = document.createElement("tr");
        row.innerHTML = `
          <td>${a.project_name || 'N/A'}</td>
          <td>${a.task || '-'}</td>
          <td>${a.start_date || '-'}</td>
          <td>${a.end_date || '-'}</td>
          <td>${a.status || '-'}</td>
        `;
        tableManager.addRow(row);
      });

      tableManager.render();
    },
    error: function() {
      showToast("AJAX error fetching assignments", "error");
    }
  });
}

// Call the function
loadAssignments();



      // Pagination
      $("#nextPage").click(() => tableManager.nextPage());
      $("#prevPage").click(() => tableManager.prevPage());
    }

    // Expose globally
    window.EmployeeAssignments = EmployeeAssignments;
  })(jQuery, window);

  // Initialize
  new EmployeeAssignments();

});
</script>

</body>
</html>
