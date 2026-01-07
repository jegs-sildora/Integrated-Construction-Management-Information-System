<!DOCTYPE html>
<html lang="en">
<?php 
$pageTitle = "Labor & Workforce Dashboard";
include '../components/head.php';
?>
<body>

<section class="dashboard-container">
  <?php include '../components/sidebar.php'; ?>

  <main class="main-content">
    <?php
    $title = "Dashboard";
    $breadcrumbs = [
      ['label' => 'Labor & Workforce', 'link' => null],
      ['label' => 'Dashboard', 'link' => null]
    ];
    include '../components/top-bar.php';
    ?>

    <section class="content-wrapper">

      <!-- ================= STATS ================= -->
      <section class="dashboard-grid">
        <div class="stat-card">
          <h4>Total Employees</h4>
          <h2 id="totalEmployees">—</h2>
          <p id="employeeBreakdown">Loading...</p>
        </div>

        <div class="stat-card">
          <h4>Workforce Utilization</h4>
          <h2 id="utilizationRate">—%</h2>
          <p id="utilizationBreakdown">Loading...</p>
        </div>

        <div class="stat-card">
          <h4>Attendance Today</h4>
          <h2 id="attendanceRate">—%</h2>
          <p id="attendanceBreakdown">Loading...</p>
        </div>

        <div class="stat-card under-construction">
          <span class="material-symbols-outlined">payments</span>
          <h4>Payroll This Month</h4>
          <p>Under Construction</p>
        </div>
      </section>

      <!-- ================= CHARTS ================= -->
      <section class="main-grid">

        <div class="chart-card">
          <h3>Attendance Trend</h3>
          <div class="chart-container">
            <canvas id="attendanceChart"></canvas>
          </div>
        </div>

        <div class="chart-card">
          <h3>Project Status</h3>
          <div class="chart-container">
            <canvas id="projectChart"></canvas>
          </div>
        </div>

        <div class="chart-card">
          <h3>Employee Status</h3>
          <div class="chart-container">
            <canvas id="employeeStatusChart"></canvas>
          </div>
        </div>

        <div class="stat-card under-construction">
          <span class="material-symbols-outlined">insights</span>
          <h4>Advanced Analytics</h4>
          <p>Forecasting & workforce optimization coming soon</p>
        </div>

      </section>

    </section>
    <?php include '../components/footer.php'; ?>
  </main>
</section>

<?php include '../components/scripts.php'; ?>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
let attendanceChart, projectChart, employeeStatusChart;

function initCharts() {
  const defaultOptions = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: { legend: { position: 'bottom' } }
  };

  attendanceChart = new Chart(document.getElementById("attendanceChart"), {
    type: "line",
    data: {
      labels: ["Mon","Tue","Wed","Thu","Fri","Sat","Sun"],
      datasets: [{
        label: "Attendance %",
        data: [],
        borderColor: "#3b82f6",
        backgroundColor: "rgba(59,130,246,0.1)",
        tension: 0.3,
        fill: true,
        pointRadius: 5,
        pointBackgroundColor: "#fff"
      }]
    },
    options: {...defaultOptions,
      scales: {
        y: {
          min: 0,
          max: 100,
          ticks: { callback: value => value + "%" },
          grid: { color: 'rgba(0,0,0,0.05)' }
        },
        x: { grid: { display: false } }
      }
    }
  });

  projectChart = new Chart(document.getElementById("projectChart"), {
    type: "doughnut",
    data: {
      labels: ["Planning","In Progress","Completed","On Hold"],
      datasets: [{
        data: [],
        backgroundColor: ["#f59e0b","#3b82f6","#10b981","#ef4444"],
        borderWidth: 0,
        hoverOffset: 10
      }]
    },
    options: {...defaultOptions, cutout: '70%'}
  });

  employeeStatusChart = new Chart(document.getElementById("employeeStatusChart"), {
    type: "doughnut",
    data: {
      labels: ["Active","Inactive","On Leave"],
      datasets: [{
        data: [],
        backgroundColor: ["#10b981","#ef4444","#f59e0b"],
        borderWidth: 0,
        hoverOffset: 10
      }]
    },
    options: {...defaultOptions, cutout: '70%'}
  });
}

function loadDashboard() {
  $.getJSON("../../backend/dashboard/backend_dashboard.php", res => {
    if (!res.success) return;

    // Total employees & breakdown
    $("#totalEmployees").text(res.stats.employees.total);
    $("#employeeBreakdown").text(`Active: ${res.stats.employees.active} | On Leave: ${res.stats.employees.on_leave}`);

    // Workforce utilization
    const assigned = res.charts.assignmentUtilization.assigned;
    const unassigned = res.charts.assignmentUtilization.unassigned;
    const utilization = Math.round((assigned / (assigned + unassigned)) * 100);
    $("#utilizationRate").text(utilization + "%");
    $("#utilizationBreakdown").text(`Assigned: ${assigned} | Unassigned: ${unassigned}`);

    // Attendance
    $("#attendanceRate").text(res.stats.attendance.today + "%");
    $("#attendanceBreakdown").text(`Present: ${res.stats.attendance.present} | Absent: ${res.stats.attendance.absent}`);

    // Payroll (commented out)
    // $("#payrollTotal").text("₱" + res.stats.payroll.total.toLocaleString());
    // $("#payrollBreakdown").text(`Paid: ₱${res.stats.payroll.paid.toLocaleString()} | Pending: ₱${res.stats.payroll.pending.toLocaleString()}`);

    // Update charts
    attendanceChart.data.datasets[0].data = res.charts.attendanceTrend;
    attendanceChart.update();

    projectChart.data.datasets[0].data = [
      res.charts.projectStatus.planning,
      res.charts.projectStatus.in_progress,
      res.charts.projectStatus.completed,
      res.charts.projectStatus.on_hold
    ];
    projectChart.update();

    employeeStatusChart.data.datasets[0].data = [
      res.charts.employeeStatus.active,
      res.charts.employeeStatus.inactive,
      res.charts.employeeStatus.on_leave
    ];
    employeeStatusChart.update();
  });
}

$(document).ready(function(){
  initCharts();
  loadDashboard();
});
</script>

<style>
/* Chart card styling */
.chart-card {
  background: #fff;
  padding: 20px;
  border-radius: 12px;
  box-shadow: 0 2px 8px rgba(0,0,0,0.05);
  display: flex;
  flex-direction: column;
  align-items: stretch;
}

.chart-container {
  position: relative;
  width: 100%;
  height: 300px;
  margin-top: 10px;
}

.chart-card h3 {
  margin-bottom: 5px;
  font-size: 16px;
  font-weight: 600;
}

.under-construction {
  text-align: center;
  opacity: 0.6;
}

.under-construction span {
  font-size: 40px;
  color: #f59e0b;
}
</style>

</body>
</html>
