<!DOCTYPE html>
<html lang="en">
<?php
$pageTitle = "Payroll Payslip Details";
include '../components/head.php';
?>

<style>
/* ===== Payslip Card ===== */
.payslip-container {
  max-width: 720px;
  margin: 30px auto;
  padding: 25px;
  border: 1px solid #e0e0e0;
  border-radius: 10px;
  background-color: #fff;
  font-family: "Inter", sans-serif;
  box-shadow: 0 4px 12px rgba(0,0,0,0.08);
}

/* ===== Header ===== */
.payslip-header {
  text-align: center;
  margin-bottom: 30px;
}

.payslip-header h1 {
  margin: 0;
  font-size: 24px;
  font-weight: 600;
}

.payslip-header h2 {
  margin: 5px 0 0;
  font-size: 22px;
  font-weight: 600;
  color: var(--primary-orange);
}

.payslip-header .company-address {
  margin: 5px 0 0;
  font-size: 14px;
  color: #555;
}

/* ===== Employee Info ===== */
.employee-info {
  display: flex;
  justify-content: center;
  gap: 40px;
  margin-bottom: 25px;
  padding-bottom: 15px;
  border-bottom: 2px solid #ddd;
  flex-wrap: wrap;
  text-align: left;
}

.info-column {
  flex: 1 1 250px;
}

.info-column p {
  margin: 5px 0;
  font-size: 14px;
}

.info-column p strong {
  display: inline-block;
  width: 120px;
  color: #333;
}

/* ===== Tables ===== */
.payslip-table {
  width: 100%;
  border-collapse: collapse;
  font-size: 14px;
  margin-bottom: 30px;
}

.payslip-table th,
.payslip-table td {
  padding: 10px 12px;
  border-bottom: 1px solid #ddd;
  text-align: right;
}

.payslip-table th:first-child,
.payslip-table td:first-child {
  text-align: left;
}

.payslip-table thead tr {
  background-color: #f5f5f5;
  font-weight: 600;
}

.payslip-table tr.total-row td {
  font-weight: 700;
  border-top: 2px solid #ddd;
}

/* Space between earnings & deductions */
.payslip-deductions {
  margin-top: 40px;
}

/* ===== Buttons ===== */
.payslip-actions {
  display: flex;
  gap: 12px;
  justify-content: flex-end;
  margin-top: 30px;
}

.btn-primary, .btn-secondary {
  padding: 8px 16px;
  border-radius: 6px;
  display: inline-flex;
  align-items: center;
  gap: 6px;
  font-size: 14px;
}

/* ===== Signatures ===== */
.payslip-signatures {
  display: flex;
  justify-content: space-between;
  margin-top: 40px;
  flex-wrap: wrap;
  gap: 20px;
}

.payslip-signatures .signature-block {
  flex: 1 1 40%;
  text-align: center;
}

.payslip-signatures .signature-line {
  height: 1.5px;
  margin-bottom: 3px;
  border-bottom: 1px solid #333;
}

.payslip-signatures .signature-block p {
  font-size: 12px;
  margin: 0;
}

/* ===== Hide elements when printing ===== */
@media print {
  .payslip-actions { display: none !important; }
  .btn-secondary { display: none !important; }
  .page-title { display: none !important; }

   @page {
    margin: 10mm; /* optional, adjust print margins */
    size: auto;
   }
}

/* ===== Responsive ===== */
@media (max-width: 600px) {
  .payslip-container { padding: 20px; }
  .employee-info { flex-direction: column; gap: 10px; }
  .info-column p strong { width: auto; }
  .payslip-table th, .payslip-table td { padding: 6px 8px; font-size: 12px; }
  .payslip-header h1 { font-size: 20px; }
  .payslip-header h2 { font-size: 18px; }
  .payslip-header .company-address { font-size: 12px; }
  .pay-date { text-align: left; margin-bottom: 10px; }
  .payslip-actions { flex-direction: column; gap: 8px; align-items: stretch; }
  .btn-primary, .btn-secondary { width: 100%; justify-content: center; }
  .payslip-signatures { flex-direction: column; gap: 20px; }
  .payslip-signatures .signature-block { width: 100%; }
}
</style>

<body>
<section class="dashboard-container">

  <?php include '../components/sidebar.php'; ?>

  <main class="main-content" role="main">

    <?php
      $title = "Payroll Payslip";
      $breadcrumbs = [
        ['label' => 'Labor & Workforce > Payroll ', 'link' => null],
        ['label' => $title, 'link' => null]
      ];
      include '../components/top-bar.php';
    ?>

    <section class="content-wrapper">
      <section class="table-card" id="payslipCard">
        <div class="payslip-container">

        <!-- Back Button -->
        <div style="margin-bottom: 15px;">
          <button id="backBtn" class="btn-secondary" style="display: inline-flex; align-items: center; gap: 6px;">
            <i class="fa-solid fa-arrow-left"></i> Back
          </button>
        </div>


          <!-- Header -->
          <div class="payslip-header">
            <h1><span style="color: var(--primary-orange);">ICMIS</span> Payslip</h1>
            <p class="company-address"></p> <!--- Pwede nadi Address --->
          </div>

          <!-- Employee Info -->
          <div class="employee-info">
            <div class="info-column">
              <p><strong>Employee ID:</strong> <span id="empId"></span></p>
              <p><strong>Name:</strong> <span id="empName"></span></p>
              <p><strong>Position:</strong> <span id="empPosition"></span></p>
            </div>
            <div class="info-column">
              <p><strong>Employee Type:</strong> <span id="empType"></span></p>
              <p><strong>Payroll Period:</strong> <span id="periodName"></span></p>
              <p><strong>Pay Date:</strong> <span id="payDate"></span></p>
            </div>
          </div>

          <!-- Earnings Table -->
          <table class="payslip-table">
            <thead>
              <tr><th>Earnings</th><th>Amount (₱)</th></tr>
            </thead>
            <tbody>
              <tr><td>Basic Salary</td><td id="basicPay">0.00</td></tr>
              <tr><td>Overtime Pay</td><td id="overtimePay">0.00</td></tr>
              <tr class="total-row"><td>Gross Pay</td><td id="grossPay">0.00</td></tr>
            </tbody>
          </table>

                    <!-- Deductions Table -->
            <table class="payslip-table payslip-deductions">
            <thead>
                <tr><th>Deductions</th><th>Amount (₱)</th></tr>
            </thead>
            <tbody>
                <tr><td>SSS</td><td id="sss">0.00</td></tr>
                <tr><td>PhilHealth</td><td id="philhealth">0.00</td></tr>
                <tr><td>Pag-IBIG</td><td id="pagibig">0.00</td></tr>
                <tr><td>Other Deductions</td><td id="otherDeduction">0.00</td></tr>
                <tr>
                <td><strong>Total Deductions</strong></td>
                <td id="totalDeductions">0.00</td>
                </tr>
                <tr class="total-row">
                <td><strong>Net Pay</strong></td>
                <td id="netPay">0.00</td>
                </tr>
            </tbody>
            </table>


          <!-- Signatures -->
          <div class="payslip-signatures">
            <div class="signature-block"><div class="signature-line"></div><p>Employee Signature</p></div>
            <div class="signature-block"><div class="signature-line"></div><p>Authorized Signatory</p></div>
          </div>

          <!-- Actions -->
          <div class="payslip-actions">
            <button id="printBtn" class="btn-secondary">
              <i class="fa-solid fa-print"></i> Print
            </button>
            <button id="exportBtn" class="btn-primary">
              <i class="fa-solid fa-file-pdf"></i> Export PDF
            </button>
          </div>

        </div>
      </section>
    </section>

    <?php include '../components/footer.php'; ?>
  </main>
</section>



<?php include '../components/scripts.php'; ?>


<script>
$(document).ready(function() {
  const urlParams = new URLSearchParams(window.location.search);
  const employeeId = urlParams.get('employee_id');
  const periodId = urlParams.get('period_id');
  const backend = "../../backend/payroll/backend_payroll.php";

  function formatPeso(amount) {
    return `₱${Number(amount).toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
  }

  function loadPayslip(empId, periodId) {
  $.getJSON(backend, { action: "fetch_payroll", period_id: periodId }, res => {
    if (!res.success) return alert("Error fetching payslip");

    const record = res.payroll.find(r => r.employee_id === empId);
    if (!record) return alert("Payslip not found");

    // Employee info
    $("#empId").text(record.employee_id ?? "-");
    $("#empName").text(`${record.first_name ?? ""} ${record.last_name ?? ""}`.trim());
    $("#periodName").text(record.period_name ?? periodId);
    $("#payDate").text(record.pay_date ?? "-");
    $("#empPosition").text(record.position ?? "-");
    $("#empType").text(record.employment_type ?? "-");

    // Earnings calculation
    const totalHours = (record.hours_worked ?? 0) + (record.overtime_hours ?? 0);
    const basicPay = totalHours > 0 
      ? (record.gross_pay - record.overtime_hours * (record.gross_pay / totalHours))
      : 0;

    $("#basicPay").text(formatPeso(basicPay));
    $("#overtimePay").text(formatPeso((record.gross_pay ?? 0) - basicPay));
    $("#grossPay").text(formatPeso(record.gross_pay ?? 0));

        // Deductions
        const sss = record.sss_contribution ?? 0;
        const philhealth = record.philhealth_contribution ?? 0;
        const pagibig = record.pagibig_contribution ?? 0;
        const other = record.other_deductions ?? 0;

        $("#sss").text(formatPeso(sss));
        $("#philhealth").text(formatPeso(philhealth));
        $("#pagibig").text(formatPeso(pagibig));
        $("#otherDeduction").text(formatPeso(other));

        const totalDeductions = sss + philhealth + pagibig + other;
        $("#totalDeductions").html(`<strong>${formatPeso(totalDeductions)}</strong>`);

        // Net Pay
        $("#netPay").text(formatPeso(record.net_pay ?? 0));
  });
}

// Back Button
$("#backBtn").click(() => {
  window.history.back(); // goes back to previous page
});







  // Print Payslip
  $("#printBtn").click(() => {
    window.print();
  });

  // Export PDF
  $("#exportBtn").click(() => {
  const { jsPDF } = window.jspdf;
  const container = document.querySelector(".payslip-container");

  // Temporarily hide buttons
  const actions = container.querySelector(".payslip-actions");
  if (actions) actions.style.display = "none";

  html2canvas(container, {
    scale: 2,       // higher resolution
    useCORS: true   // for images if needed
  }).then((canvas) => {
    const imgData = canvas.toDataURL("image/png");
    const pdf = new jsPDF("p", "pt", "a4");
    const pdfWidth = pdf.internal.pageSize.getWidth();
    const pdfHeight = pdf.internal.pageSize.getHeight();

    const imgWidth = pdfWidth;
    const imgHeight = (canvas.height * pdfWidth) / canvas.width;

    let heightLeft = imgHeight;
    let position = 0;

    pdf.addImage(imgData, "PNG", 0, position, imgWidth, imgHeight);
    heightLeft -= pdfHeight;

    while (heightLeft > 0) {
      position = heightLeft - imgHeight;
      pdf.addPage();
      pdf.addImage(imgData, "PNG", 0, position, imgWidth, imgHeight);
      heightLeft -= pdfHeight;
    }

    pdf.save(`Payslip_${employeeId}_${periodId}.pdf`);

    // Restore buttons
    if (actions) actions.style.display = "flex";
  });
});




  loadPayslip(employeeId, periodId);
});
</script>
</body>
</html>
