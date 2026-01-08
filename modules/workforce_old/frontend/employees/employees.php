<!doctype html>
<html lang="en">
<?php
  $pageTitle = "Workforce & Labor - Employees";
  include '../components/head.php';
?>

<body>
  <section class="dashboard-container">

    <!-- Sidebar -->
    <?php include '../components/sidebar.php'; ?>

    <main class="main-content" role="main">

      <!-- Top Bar / Breadcrumbs -->
      <?php
        $title = "Employee Profiles";
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
            <h1>Employee Management</h1>
            <p>Manage, View, and Edit Employee Profiles</p>
          </div>
          <div class="table-header-right">

            <!-- Search -->
            <div class="table-search">
              <i class="fa-solid fa-magnifying-glass"></i>
              <input type="search" placeholder="Search by name, role, or ID" aria-label="Search employees" />
            </div>

            <!-- Filter -->
            <div class="filter-dropdown">
              <button id="FilterBtn" class="btn-secondary">
                <i class="fa-solid fa-filter"></i> Filter
              </button>
            </div>
            <div id="FilterMenu" class="filter-menu">
              <div class="filter-options">
                <div>
                  <label>Status:</label>
                  <select id="employeeStatusFilter">
                    <option value="">All</option>
                    <option value="Active">Active</option>
                    <option value="Inactive">Inactive</option>
                  </select>
                </div>
                <div>
                  <label>Type:</label>
                  <select id="employeeTypeFilter">
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

            <!-- Add Employee -->
            <button id="addRecordBtn" class="btn-primary">
              <i class="fa-solid fa-user-plus"></i> Add Employee
            </button>
          </div>
        </section>

        <!-- Stats Cards -->
        <section class="dashboard-grid">
          <div class="stat-card">
            <h3><i class="fa-solid fa-users stat-icon total-employees"></i> Total Employees</h3>
            <h2>0</h2>
            <p>All registered employees</p>
          </div>
          <div class="stat-card">
            <h3><i class="fa-solid fa-user-check stat-icon active-employees"></i> Active</h3>
            <h2>0</h2>
            <p>Currently working</p>
          </div>
          <div class="stat-card">
            <h3><i class="fa-solid fa-user-slash stat-icon inactive-employees"></i> Inactive</h3>
            <h2>0</h2>
            <p>Not active</p>
          </div>
          <div class="stat-card">
            <h3><i class="fa-solid fa-user-plus stat-icon new-employees"></i> New This Month</h3>
            <h2>0</h2>
            <p>Recently joined</p>
          </div>
        </section>

        <!-- Table -->
        <section class="table-card">
          <table id="generalTable" class="styled-table">
            <thead>
              <tr>
                <th>Employee</th>
                <th>Position</th>
                <th>Skill</th>
                <th>Type</th>
                <th>Status</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              <!-- Rows populated via AJAX -->
            </tbody>
          </table>
        </section>

        <?php include '../components/table-footer.php'; ?>
      </section>
      <!-- ================= CONTENT WRAPPER END ================= -->

      <?php include '../components/footer.php'; ?>
    </main>
  </section>

  <!-- Add/Edit Employee Modal -->
    <?php include 'modal.php'; ?>
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

    const backendUrl = "../../backend/employee/backend_employee.php";

    // ------------------ Load Employees ------------------
    function loadEmployees() {
        $.ajax({
            url: backendUrl,
            method: "GET",
            dataType: "json",
            success: function (res) {
                if (!res.success) return showToast("Error fetching employees", "error");

                tableManager.clear();

                let totalEmployees = res.employees.length;
                let activeCount = 0, inactiveCount = 0, newThisMonth = 0;
                const now = new Date();

                res.employees.forEach(emp => {
                   const initials = (emp.first_name[0] + emp.last_name[0]).toUpperCase();
                    const row = document.createElement("tr");
                    row.innerHTML = `
                        <td>
                            <div class="employee-cell">
                              <div class="avatar">${initials}</div>
                              <div class="employee-info">
                                     <strong>${emp.first_name} ${emp.last_name}</strong>
                                    <small>#${emp.employee_id}</small>
                              </div>
                            </div>
                        </td>
                        <td>${emp.position}</td>
                        <td>${emp.skill_type}</td>
                        <td>${emp.employment_type}</td>
                        <td><span class="status ${emp.status.toLowerCase()}">${emp.status}</span></td>
                        <td>
                            <i class="fa-solid fa-eye action-icon view-btn" data-id="${emp.employee_id}" title="View" style="color: var(--icon-view); cursor: pointer;"></i>
                            <i class="fa-solid fa-pen-to-square action-icon edit-btn" data-id="${emp.employee_id}" title="Edit" style="color: var(--icon-edit); cursor: pointer; margin-left: 10px;"></i>
                            <i class="fa-solid fa-trash action-icon delete-btn" data-id="${emp.employee_id}" title="Delete" style="color: var(--icon-delete); cursor: pointer; margin-left: 10px;"></i>
                        </td>
                    `;
                    tableManager.addRow(row);

                    // Update stats
                    if (emp.status.toLowerCase() === "active") activeCount++;
                    if (emp.status.toLowerCase() === "inactive") inactiveCount++;
                    const startDate = new Date(emp.start_date);
                    if (startDate.getMonth() === now.getMonth() && startDate.getFullYear() === now.getFullYear()) {
                        newThisMonth++;
                    }
                });

                tableManager.render();

                // Animate stats cards
                const statCards = $(".dashboard-grid .stat-card h2");
                const stats = [
                    { element: statCards[0], value: totalEmployees },
                    { element: statCards[1], value: activeCount },
                    { element: statCards[2], value: inactiveCount },
                    { element: statCards[3], value: newThisMonth },
                ];
                animateStats(stats);
            },
            error: function () {
                showToast("AJAX error fetching employees", "error");
            }
        });
    }

    loadEmployees();

    // ------------------ Pagination ------------------
    $("#nextPage").click(() => tableManager.nextPage());
    $("#prevPage").click(() => tableManager.prevPage());

    // ------------------ Filter ------------------
    $("#applyFilter").click(() => {
        const status = $("#employeeStatusFilter").val();
        const type = $("#employeeTypeFilter").val();

        tableManager.setFilterCallback(row => {
            const rowStatus = $(row).find("span.status").text().toLowerCase();
            const rowType = $(row).find("td").eq(3).text().toLowerCase();

            return (!status || rowStatus === status.toLowerCase()) &&
                   (!type || rowType === type.toLowerCase());
        });

        $("#FilterMenu").hide();
    });

    // ------------------ Add Employee ------------------
    $("#addRecordBtn").click(() => {
        $("#employeeForm")[0].reset();
        $("#employeeModalTitle").text("Add Employee");
        $("#employeeModalBtnText").text("Add Employee");

        // Get next employee ID
        $.getJSON(backendUrl, { get_next_id: 1 }, res => {
            if (res.success) {
                $("#employee_id").val(res.employee_id);
                showToast("Employee ID generated successfully", "success");
            } else {
                showToast("Error generating Employee ID", "error");
            }
        });

        $("#employeeModal").fadeIn();
    });

    $(document).on('click', '.view-btn', function () {
        const employeeId = $(this).data('id');
        window.location.href = `employee_profile.php?id=${employeeId}`;
      });


    // ------------------ Edit Employee ------------------
    $(document).on("click", ".edit-btn", function () {
        const employeeId = $(this).data("id");

        $.getJSON(backendUrl, { fetch_id: employeeId }, res => {
            if (res.success) {
                const emp = res.employee;
                Object.keys(emp).forEach(k => {
                    $(`#${k}`).val(emp[k]);
                });

                $("#employeeModalTitle").text("Edit Employee");
                $("#employeeModalBtnText").text("Update Employee");
                $("#employeeModal").fadeIn();
            } else {
                showToast(res.message || "Error fetching employee", "error");
            }
        });
    });

    // ------------------ Delete Employee ------------------
    $(document).on("click", ".delete-btn", function () {
        const employeeId = $(this).data("id");
        if (confirm("Are you sure you want to delete this employee?")) {
            $.post(backendUrl, { delete_id: employeeId }, res => {
                if (res.success) {
                    showToast(res.message, "success");
                    loadEmployees();
                } else {
                    showToast(res.message || "Error deleting employee", "error");
                }
            }, "json");
        }
    });

    // ------------------ Close Modal ------------------
    function closeModal() {
        $("#employeeForm")[0].reset();
        $("#employeeModal").fadeOut(300, function () {
            $(this).css("display", "none");
        });
    }
    $("#closeEmployeeModal, #cancelEmployeeModal").click(closeModal);

    // ------------------ Submit Add/Edit ------------------
    $("#employeeForm").submit(function (e) {
        e.preventDefault();
        $.ajax({
            url: backendUrl,
            method: "POST",
            data: $(this).serialize(),
            dataType: "json",
            success: function (res) {
                if (res.success) {
                    showToast(res.message, "success");
                    loadEmployees();
                    closeModal();
                } else {
                    showToast(res.message || "Error saving employee", "error");
                }
            },
            error: function () {
                showToast("AJAX error occurred", "error");
            }
        });
    });



 $('#employment_type').change(function() {
    const type = $(this).val();

    if (type === 'Daily') {
        $('#daily_rate').prop('disabled', false);
        $('#monthly_salary').prop('disabled', true).val('');
        $('#end_date').prop('disabled', true).val('');
        $('#payment_type').val('Daily').prop('disabled', true);
    } 
    else if (type === 'Regular') {
        $('#monthly_salary').prop('disabled', false);
        $('#daily_rate').prop('disabled', true).val('');
        $('#end_date').prop('disabled', true).val('');
        $('#payment_type').val('Monthly').prop('disabled', true);
    } 
    else if (type === 'Contractual') {
        $('#end_date').prop('disabled', false);
        $('#payment_type').prop('disabled', false);

        // Apply payment type logic immediately
        const payment = $('#payment_type').val();
        if (payment === 'Daily') {
            $('#daily_rate').prop('disabled', false);
            $('#monthly_salary').prop('disabled', true).val('');
        } else if (payment === 'Monthly') {
            $('#monthly_salary').prop('disabled', false);
            $('#daily_rate').prop('disabled', true).val('');
        } else {
            $('#daily_rate, #monthly_salary').prop('disabled', false);
        }
    } 
    else {
        $('#daily_rate, #monthly_salary').prop('disabled', false);
        $('#end_date').prop('disabled', true).val('');
        $('#payment_type').prop('disabled', true);
    }
});


$('#payment_type').change(function() {
    const payment = $(this).val();
    const type = $('#employment_type').val();

    if (type === 'Contractual') {
        if (payment === 'Daily') {
            $('#daily_rate').prop('disabled', false);
            $('#monthly_salary').prop('disabled', true).val('');
        } else if (payment === 'Monthly') {
            $('#monthly_salary').prop('disabled', false);
            $('#daily_rate').prop('disabled', true).val('');
        } else {
            $('#daily_rate, #monthly_salary').prop('disabled', false);
        }
    }
});



});
</script>
  </body>
</html>
