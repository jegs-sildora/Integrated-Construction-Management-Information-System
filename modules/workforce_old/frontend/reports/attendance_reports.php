<!DOCTYPE html>
<html lang="en">
<?php
  $pageTitle = "Attendance Reports - Workforce Management";
  include '../components/head.php';
?>

<body>
  <section class="dashboard-container">

    <!-- Sidebar -->
    <?php include '../components/sidebar.php'; ?>

    <main class="main-content" role="main">

      <!-- Top Bar / Breadcrumbs -->
      <?php
        $title = "Attendance Reports";
        $breadcrumbs = [
          ['label' => 'Reports', 'link' => null],
          ['label' => $title, 'link' => null]
        ];
        include '../components/top-bar.php';
      ?>

      <section class="content-wrapper">

           <?php
          $section = 'reports'; // Replace with active tab
          include '../components/tabs.php';
             ?>

        <!-- Control bar -->
        <section class="control-card">
          <div class="table-header-left">
            <h1>Attendance Reports</h1>
            <p>Detailed attendance analytics per employee</p>
          </div>
          <div class="table-header-right">
            <div class="table-search">
              <i class="fa-solid fa-magnifying-glass"></i>
              <input type="search" placeholder="Search by employee name..." />
            </div>
            <div class="filter-dropdown">
              <button id="FilterBtn" class="btn-secondary">
                <i class="fa-solid fa-filter"></i> Filter
              </button>
            </div>
            <div id="FilterMenu" class="filter-menu">
              <div class="filter-options">
                <label>Department</label>
                <select id="departmentFilter">
                  <option value="all">All Departments</option>
                  <option value="construction">Construction</option>
                  <option value="electrical">Electrical</option>
                  <option value="plumbing">Plumbing</option>
                  <option value="carpentry">Carpentry</option>
                </select>

                <label>Period</label>
                <select id="periodFilter">
                  <option value="last-week">Last Week</option>
                  <option value="last-month">Last Month</option>
                  <option value="last-quarter">Last Quarter</option>
                  <option value="custom">Custom Range</option>
                </select>

                <div id="customDateRange" style="display:none; margin-top:10px;">
                  <input type="date" id="startDate">
                  <input type="date" id="endDate">
                </div>

                <button id="applyFilter" class="btn-primary">
                  <i class="fa-solid fa-check"></i> Apply
                </button>
              </div>
            </div>
          </div>
        </section>

        <!-- Charts Section -->
        <section class="report-section">
          <div class="section-header">
            <h3>Attendance Summary</h3>
            <div class="section-actions">
              <button class="export-btn" id="exportCsvBtn">
                <span class="material-symbols-outlined">download</span> Export CSV
              </button>
            </div>
          </div>

          <div class="card">
            <div style="display:flex; flex-wrap:wrap; gap:20px; margin-bottom:30px;">
              <div style="flex:1; min-width:300px;">
                <canvas id="attendanceChart"></canvas>
              </div>
              <div style="flex:1; min-width:300px;">
                <canvas id="attendanceDistributionChart"></canvas>
              </div>
            </div>

            <div style="margin-top:20px;">
              <canvas id="attendanceTrendChart"></canvas>
            </div>
          </div>
        </section>

        <!-- Attendance Table Section -->
        <section class="report-section">
          <div class="section-header">
            <h3>Detailed Attendance Records</h3>
          </div>

          <div class="table-card">
            <table id="generalTable" class="styled-table">
              <thead>
                <tr>
                  <th>Employee</th>
                  <th>ID</th>
                  <th>Department</th>
                  <th>Date</th>
                  <th>Status</th>
                  <th>Hours Worked</th>
                  <th>Remarks</th>
                </tr>
              </thead>
              <tbody>
                <!-- Rows populated via AJAX -->
              </tbody>
            </table>
          </div>
        </section>

        <?php include '../components/table-footer.php'; ?>
      </section>

      <?php include '../components/footer.php'; ?>
    </main>
  </section>

  <?php include '../components/scripts.php'; ?>

  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

  <script>
    $(document).ready(function() {

      const tableManager = new TableManager({
        tableSelector: "#generalTable",
        searchSelector: ".table-search input",
        rowsPerPage: 10,
        recordCountSelector: "#recordCount",
        pageNumbersSelector: "#pageNumbers",
      });

      // Show/hide custom date range
      $("#periodFilter").change(function() {
        if($(this).val() === 'custom') {
          $("#customDateRange").show();
        } else {
          $("#customDateRange").hide();
        }
      });

      function loadAttendanceReports() {
        $.ajax({
          url: "../../backend/reports/backend_attendance.php",
          method: "GET",
          dataType: "json",
          data: {
            department: $("#departmentFilter").val(),
            period: $("#periodFilter").val(),
            start: $("#startDate").val(),
            end: $("#endDate").val()
          },
          success: function(res) {
            if(!res.success) return showToast(res.message || "Error loading reports", "error");

            // ---- Populate Table ----
            tableManager.clear();
            res.records.forEach(item => {
              const row = document.createElement('tr');
              row.innerHTML = `
                <td>${item.employee_name}</td>
                <td>${item.employee_id}</td>
                <td>${item.department}</td>
                <td>${item.date}</td>
                <td>${item.status}</td>
                <td>${item.hours_worked}</td>
                <td>${item.remarks || '-'}</td>
              `;
              tableManager.addRow(row);
            });
            tableManager.render();

            // ---- Populate Charts ----
            const labels = res.chart.daily.map(d => d.date);
            const presentData = res.chart.daily.map(d => d.present);
            const lateData = res.chart.daily.map(d => d.late);
            const absentData = res.chart.daily.map(d => d.absent);

            new Chart(document.getElementById('attendanceChart'), {
              type: 'bar',
              data: {
                labels: labels,
                datasets: [
                  { label: 'Present', data: presentData, backgroundColor: '#10b981', borderRadius: 6 },
                  { label: 'Late', data: lateData, backgroundColor: '#f59e0b', borderRadius: 6 },
                  { label: 'Absent', data: absentData, backgroundColor: '#ef4444', borderRadius: 6 }
                ]
              },
              options: { responsive:true }
            });

            new Chart(document.getElementById('attendanceDistributionChart'), {
              type: 'pie',
              data: {
                labels: ['Present','Late','Absent','Leave'],
                datasets: [{
                  data: [res.chart.summary.present, res.chart.summary.late, res.chart.summary.absent, res.chart.summary.leave],
                  backgroundColor: ['#10b981','#f59e0b','#ef4444','#3b82f6'],
                  borderWidth: 0
                }]
              },
              options: { responsive:true }
            });

            new Chart(document.getElementById('attendanceTrendChart'), {
              type: 'line',
              data: {
                labels: res.chart.trend.labels,
                datasets: [{
                  label:'Attendance Rate',
                  data: res.chart.trend.data,
                  borderColor:'#3b82f6',
                  backgroundColor:'rgba(59,130,246,0.1)',
                  tension:0.3,
                  fill:true
                }]
              },
              options:{ responsive:true }
            });
          },
          error:function(){ showToast("AJAX error fetching attendance reports","error"); }
        });
      }

      $("#applyFilter").click(loadAttendanceReports);

      // Initial load
      loadAttendanceReports();

      // Pagination
      $("#nextPage").click(() => tableManager.nextPage());
      $("#prevPage").click(() => tableManager.prevPage());

      // Export CSV
      $("#exportCsvBtn").click(function() {
        alert("Export CSV clicked! Backend integration needed.");
      });

    });
  </script>

</body>
</html>
