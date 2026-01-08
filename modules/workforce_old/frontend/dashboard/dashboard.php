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
          <h2 id="payrollTotal">₱0</h2>
          <p id="payrollBreakdown">Paid: ₱0 | Pending: ₱0</p>
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
          <h3>Employee Status</h3>
          <div class="chart-container">
            <canvas id="employeeStatusChart"></canvas>
          </div>
        </div>

      </section>

    </section>
    <?php include '../components/footer.php'; ?>
  </main>
</section>

<?php include '../components/scripts.php'; ?>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
let attendanceChart, employeeStatusChart;

function initCharts() {
  const defaultOptions = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: { 
      legend: { position: 'bottom', labels: { boxWidth: 12, padding: 15 } } 
    }
  };

  // Attendance line chart
  const attendanceCtx = document.getElementById("attendanceChart").getContext("2d");
  attendanceChart = new Chart(attendanceCtx, {
    type: "line",
    data: {
      labels: ["Mon","Tue","Wed","Thu","Fri","Sat","Sun"],
      datasets: [{
        label: "Attendance %",
        data: [0,0,0,0,0,0,0],
        borderColor: "#3b82f6",
        backgroundColor: "rgba(59,130,246,0.1)",
        tension: 0.3,
        fill: true,
        pointRadius: 5,
        pointBackgroundColor: "#3b82f6"
      }]
    },
    options: {
      ...defaultOptions,
      scales: {
        y: { min:0, max:100, ticks:{callback: value=>value+"%"}, grid:{color:'rgba(0,0,0,0.05)'} },
        x: { grid: { display:false } }
      }
    }
  });

  // Employee status chart
  const employeeCtx = document.getElementById("employeeStatusChart").getContext("2d");
  employeeStatusChart = new Chart(employeeCtx, {
    type: "doughnut",
    data: {
      labels: ["Active","Inactive","Terminated"],
      datasets: [{
        data: [0,0,0],
        backgroundColor: ["#10b981","#f59e0b","#ef4444"],
        borderWidth: 0,
        hoverOffset: 10
      }]
    },
    options: {
      ...defaultOptions,
      cutout: '70%'
    }
  });
}

function loadDashboard() {
  $.getJSON("../../backend/dashboard/backend_dashboard.php", res => {
    if (!res.success) return;

    const totalEmployees = res.stats.employees.total;
    const active = res.stats.employees.active;
    const onLeave = res.stats.employees.on_leave;
    const inactive = Math.max(totalEmployees - active - onLeave, 0);

    // Employees
    $("#totalEmployees").text(totalEmployees);
    $("#employeeBreakdown").text(`Active: ${active} | On Leave: ${onLeave}`);

    // Workforce utilization
    const utilRate = res.stats.utilization.rate || 0;
    $("#utilizationRate").text(utilRate + "%");
    $("#utilizationBreakdown").text(`Assigned: ${res.stats.utilization.assigned} | Unassigned: ${res.stats.utilization.unassigned}`);

    // Attendance
    $("#attendanceRate").text(res.stats.attendance.today + "%");
    $("#attendanceBreakdown").text(`Present: ${res.stats.attendance.present} | Absent: ${res.stats.attendance.absent}`);

    // Payroll
    $("#payrollTotal").text("₱" + (res.stats.payroll.total || 0).toLocaleString());
    $("#payrollBreakdown").text(`Paid: ₱${(res.stats.payroll.paid || 0).toLocaleString()} | Pending: ₱${(res.stats.payroll.pending || 0).toLocaleString()}`);


    // Update attendance chart
    const trend = res.charts.attendanceTrend || [0,0,0,0,0,0,0];
    attendanceChart.data.datasets[0].data = trend;
    attendanceChart.update();

    // Update employee status chart
    employeeStatusChart.data.datasets[0].data = [active, inactive, onLeave];
    employeeStatusChart.update();
  });
}

$(document).ready(function(){
  initCharts();
  loadDashboard();
});
</script>



<style>
.chart-card {
  background: #fff;
  padding: 20px;
  border-radius: 12px;
  box-shadow: 0 2px 8px rgba(0,0,0,0.05);
  display: flex;
  flex-direction: column;
  align-items: stretch;
  margin-bottom: 20px;
}

.chart-card h3 {
  margin-bottom: 10px;
  font-size: 16px;
  font-weight: 600;
}

.chart-container {
  width: 100%;
  height: 300px;
  position: relative;
}

@media (max-width: 768px) {
  .chart-container {
    height: 250px;
  }
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
