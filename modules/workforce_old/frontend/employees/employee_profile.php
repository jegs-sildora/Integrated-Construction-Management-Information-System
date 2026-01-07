<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Employee Profile - Workforce Management System</title>
   <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" />
  <link rel="stylesheet" href="../css/style.css" />
  <link rel="stylesheet" href="../css/main.css" />
  <link rel="stylesheet" href="../css/sidebar.css" />
  <link rel="stylesheet" href="../css/tablestyle.css" />
  <link rel="stylesheet" href="../css/dataTables.min.css" />
</head>
<body>


   <?php include '../components/sidebar.php'?>

  <main class="dashboard-container">
   
      <?php 
      $backLink = "../employees/employees.php"; 
      $backText = "Back to Employees"; 
      include '../components/header.php';
      ?>

    
    <div class="profile-header">
      <div class="profile-image">JD</div>
      <div class="profile-info">
        <h2>John Diaz</h2>
        <p>Electrician | EMP002 | Regular Employee</p>
        
        <div class="profile-details">
          <div class="detail-item">
            <span class="detail-label">STATUS</span>
            <span class="detail-value" style="color: #059669;">Active</span>
          </div>
          <div class="detail-item">
            <span class="detail-label">SKILL TYPE</span>
            <span class="detail-value">Electrical</span>
          </div>
          <div class="detail-item">
            <span class="detail-label">START DATE</span>
            <span class="detail-value">Jan 15, 2023</span>
          </div>
          <div class="detail-item">
            <span class="detail-label">DAILY RATE</span>
            <span class="detail-value">$35.00</span>
          </div>
        </div>
      </div>
    </div>
    
    <div class="tabs">
      <div class="tab active" data-tab="personal">Personal & Job Info</div>
      <div class="tab" data-tab="assignments">Assignment History</div>
      <div class="tab" data-tab="attendance">Attendance Summary</div>
      <div class="tab" data-tab="payroll">Payroll Summary</div>
    </div>
    
    <!-- Personal & Job Info Tab -->
    <div id="personal" class="tab-content active">
      <div class="section-card">
        <h3 class="section-title">Personal Information</h3>
        <div class="info-grid">
          <div class="info-item">
            <div class="info-label">Full Name</div>
            <div class="info-value">John Diaz</div>
          </div>
          <div class="info-item">
            <div class="info-label">Employee ID</div>
            <div class="info-value">EMP002</div>
          </div>
          <div class="info-item">
            <div class="info-label">Contact Number</div>
            <div class="info-value">+1 (555) 123-4567</div>
          </div>
          <div class="info-item">
            <div class="info-label">Email</div>
            <div class="info-value">john.diaz@example.com</div>
          </div>
          <div class="info-item">
            <div class="info-label">Address</div>
            <div class="info-value">123 Main Street, Anytown, ST 12345</div>
          </div>
          <div class="info-item">
            <div class="info-label">Emergency Contact</div>
            <div class="info-value">Maria Diaz - +1 (555) 987-6543</div>
          </div>
        </div>
      </div>
      
      <div class="section-card">
        <h3 class="section-title">Job Information</h3>
        <div class="info-grid">
          <div class="info-item">
            <div class="info-label">Position / Role</div>
            <div class="info-value">Electrician</div>
          </div>
          <div class="info-item">
            <div class="info-label">Skill Type</div>
            <div class="info-value">Electrical</div>
          </div>
          <div class="info-item">
            <div class="info-label">Employment Type</div>
            <div class="info-value">Regular</div>
          </div>
          <div class="info-item">
            <div class="info-label">Daily / Monthly Rate</div>
            <div class="info-value">$35.00 / $7,000.00</div>
          </div>
          <div class="info-item">
            <div class="info-label">Start Date</div>
            <div class="info-value">January 15, 2023</div>
          </div>
          <div class="info-item">
            <div class="info-label">Status</div>
            <div class="info-value">Active</div>
          </div>
        </div>
      </div>

      <div class="section-card">
        <h3 class="section-title">Group Membership</h3>
        <div class="info-grid">
          <div class="info-item">
            <div class="info-label">Groups</div>
            <div class="info-value" id="groupMembership">
              <!-- Groups will be populated dynamically -->
            </div>
          </div>
        </div>
      </div>
    </div>
    
    <!-- Assignment History Tab -->
    <div id="assignments" class="tab-content">
      <div class="section-card">
        <h3 class="section-title">Current Assignments</h3>
        <table class="history-table">
          <thead>
            <tr>
              <th>Project</th>
              <th>Task</th>
              <th>Start Date</th>
              <th>Status</th>
            </tr>
          </thead>
          <tbody>
            <tr>
              <td>Riverside Apartments</td>
              <td>Electrical Installation - Phase 2</td>
              <td>Nov 1, 2023</td>
              <td><span class="status-badge status-active">In Progress</span></td>
            </tr>
            <tr>
              <td>Downtown Office Complex</td>
              <td>Lighting System Upgrade</td>
              <td>Oct 15, 2023</td>
              <td><span class="status-badge status-active">In Progress</span></td>
            </tr>
          </tbody>
        </table>
      </div>
      
      <div class="section-card">
        <h3 class="section-title">Past Assignments</h3>
        <table class="history-table">
          <thead>
            <tr>
              <th>Project</th>
              <th>Task</th>
              <th>Start Date</th>
              <th>End Date</th>
              <th>Status</th>
            </tr>
          </thead>
          <tbody>
            <tr>
              <td>Hillside Residences</td>
              <td>Electrical Rough-in</td>
              <td>Aug 10, 2023</td>
              <td>Sep 30, 2023</td>
              <td><span class="status-badge status-completed">Completed</span></td>
            </tr>
            <tr>
              <td>City Mall Renovation</td>
              <td>Lighting Retrofit</td>
              <td>Jun 5, 2023</td>
              <td>Aug 5, 2023</td>
              <td><span class="status-badge status-completed">Completed</span></td>
            </tr>
            <tr>
              <td>Parkview Condos</td>
              <td>Panel Installation</td>
              <td>Apr 20, 2023</td>
              <td>Jun 10, 2023</td>
              <td><span class="status-badge status-completed">Completed</span></td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
    
    <!-- Attendance Summary Tab -->
    <div id="attendance" class="tab-content">
      <div class="section-card">
        <h3 class="section-title">Attendance Summary</h3>
        <p>For detailed attendance information, please visit the <a href="employee_attendance_summary.html">Attendance Summary page</a>.</p>
        <div class="info-grid">
          <div class="info-item">
            <div class="info-label">Days Present (This Month)</div>
            <div class="info-value">20</div>
          </div>
          <div class="info-item">
            <div class="info-label">Days Absent (This Month)</div>
            <div class="info-value">2</div>
          </div>
          <div class="info-item">
            <div class="info-label">Late Arrivals (This Month)</div>
            <div class="info-value">1</div>
          </div>
          <div class="info-item">
            <div class="info-label">Overtime Hours (This Month)</div>
            <div class="info-value">8.5</div>
          </div>
        </div>
      </div>
    </div>
    
    <!-- Payroll Summary Tab -->
    <div id="payroll" class="tab-content">
      <div class="section-card">
        <div style="display: flex; justify-content: space-between; align-items: center;">
          <h3 class="section-title">Payroll Summary</h3>
          <button class="add-btn" id="generateEmployeePayrollBtn" style="padding: 8px 16px; font-size: 14px;">
            <span class="material-symbols-outlined">calculate</span>
            Generate Payroll
          </button>
        </div>
        <div class="payroll-summary">
          <div class="payroll-item">
            <div class="payroll-label">Gross Pay</div>
            <div class="payroll-value">$2,800.00</div>
          </div>
          <div class="payroll-item">
            <div class="payroll-label">Tax Deduction</div>
            <div class="payroll-value">-$420.00</div>
          </div>
          <div class="payroll-item">
            <div class="payroll-label">SSS Contribution</div>
            <div class="payroll-value">-$140.00</div>
          </div>
          <div class="payroll-item">
            <div class="payroll-label">PhilHealth</div>
            <div class="payroll-value">-$84.00</div>
          </div>
          <div class="payroll-divider"></div>
          <div class="payroll-item total">
            <div class="payroll-label">Net Pay</div>
            <div class="payroll-value">$2,156.00</div>
          </div>
        </div>
        <div style="margin-top: 20px; text-align: center;">
          <button class="btn-primary" id="downloadPayrollBtn" style="display: none;">
            <span class="material-symbols-outlined">download</span>
            Download Payroll Slip
          </button>
        </div>
      </div>
      
      <div class="section-card">
        <h3 class="section-title">Work Hours Summary</h3>
        <p>Payroll is calculated based on attendance records managed by administrators.</p>
        <div class="payroll-summary">
          <div class="payroll-item">
            <div class="payroll-label">Current Month Hours</div>
            <div class="payroll-value">120 hours</div>
          </div>
          <div class="payroll-item">
            <div class="payroll-label">Overtime Hours</div>
            <div class="payroll-value">8.5 hours</div>
          </div>
          <div class="payroll-item">
            <div class="payroll-label">Estimated Gross Pay</div>
            <div class="payroll-value">$4,200.00</div>
          </div>
        </div>
      </div>
    </div>
  </main>

  <!-- Edit Employee Modal -->
  <div id="editEmployeeModal" class="modal">
    <div class="modal-content">
      <div class="modal-header">
        <h2>Edit Employee Information</h2>
        <button class="close-btn">&times;</button>
      </div>
      <div class="modal-body">
        <div class="form-group">
          <label for="editFirstName">First Name</label>
          <input type="text" id="editFirstName" class="form-control" placeholder="Enter first name">
        </div>
        <div class="form-group">
          <label for="editLastName">Last Name</label>
          <input type="text" id="editLastName" class="form-control" placeholder="Enter last name">
        </div>
        <div class="form-group">
          <label for="editPosition">Position</label>
          <input type="text" id="editPosition" class="form-control" placeholder="Enter position">
        </div>
        <div class="form-group">
          <label for="editEmail">Email</label>
          <input type="email" id="editEmail" class="form-control" placeholder="Enter email">
        </div>
      </div>
      <div class="modal-footer">
        <button class="btn btn-secondary close-btn">Cancel</button>
        <button class="btn btn-primary" id="saveEmployeeBtn">Save Changes</button>
      </div>
    </div>
  </div>

 <script src="../js/jquery.min.js"></script>
  <script src="../js/dataTables.min.js"></script>
  <script>
    // Employee Profile specific functionality
    document.addEventListener('DOMContentLoaded', function() {
      // Initialize DataTables for all history tables
      $('.history-table').each(function() {
        $(this).DataTable({
          "paging": true,
          "pageLength": 5,
          "lengthChange": false,
          "searching": false,
          "ordering": true,
          "info": true,
          "autoWidth": false,
          "responsive": true
        });
      });
      
      // Tab switching functionality
      const tabs = document.querySelectorAll('.tab');
      const tabContents = document.querySelectorAll('.tab-content');
      
      tabs.forEach(tab => {
        tab.addEventListener('click', () => {
          // Remove active class from all tabs and contents
          tabs.forEach(t => t.classList.remove('active'));
          tabContents.forEach(tc => tc.classList.remove('active'));
          
          // Add active class to clicked tab
          tab.classList.add('active');
          
          // Show corresponding content
          const tabName = tab.getAttribute('data-tab');
          document.getElementById(tabName).classList.add('active');
        });
      });
      
      // Action button handlers
      document.querySelectorAll('.action-btn').forEach(button => {
        button.addEventListener('click', function() {
          const action = this.title;
          console.log(action + ' clicked');
          // In a real app, you would implement the actual functionality here
          
          // Open edit modal when edit button is clicked
          if (action === 'Edit Employee') {
            document.getElementById('editEmployeeModal').style.display = 'block';
          }
        });
      });
      
      // Modal save button handler
      const saveEmployeeBtn = document.getElementById('saveEmployeeBtn');
      if (saveEmployeeBtn) {
        saveEmployeeBtn.addEventListener('click', function() {
          // Show loading spinner
          const modalContent = this.closest('.modal-content');
          showLoadingSpinner(modalContent);
          
          // Simulate API call delay
          setTimeout(() => {
            hideLoadingSpinner();
            document.getElementById('editEmployeeModal').style.display = 'none';
            alert('Employee information updated successfully!');
          }, 1500);
        });
      }
      
      // Add event listener for Generate Employee Payroll button
      const generatePayrollBtn = document.getElementById('generateEmployeePayrollBtn');
      if (generatePayrollBtn) {
        generatePayrollBtn.addEventListener('click', generateEmployeePayroll);
      }
      
      // Add event listener for Download Payroll button
      const downloadPayrollBtn = document.getElementById('downloadPayrollBtn');
      if (downloadPayrollBtn) {
        downloadPayrollBtn.addEventListener('click', downloadEmployeePayroll);
      }
    });

    /**
     * Generates payroll for the current employee based on confirmed attendance records
     */
    function generateEmployeePayroll() {
      showLoadingSpinner(document.querySelector('.dashboard-container'));
      
      // In a real application, this would fetch confirmed attendance records and calculate payroll:
      /*
      fetch(`/api/payroll/generate/EMP001`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Authorization': 'Bearer ' + getAuthToken()
        },
        body: JSON.stringify({
          periodStart: '2025-12-01',
          periodEnd: '2025-12-15'
        })
      })
      .then(response => response.json())
      .then(data => {
        hideLoadingSpinner();
        if (data.success) {
          alert('Payroll generated successfully for ' + data.employeeName + '!');
          
          // Update payroll display
          updatePayrollDisplay(data.payroll);
          
          // Show download button
          document.getElementById('downloadPayrollBtn').style.display = 'inline-flex';
        } else {
          alert('Error generating payroll: ' + data.message);
        }
      })
      .catch(error => {
        hideLoadingSpinner();
        console.error('Error:', error);
        alert('Network error occurred while generating payroll.');
      });
      */
      
      // Simulate API processing for demo purposes
      setTimeout(() => {
        hideLoadingSpinner();
        
        // Show success message
        alert('Payroll generated successfully for John Diaz!');
        
        // Show download button
        document.getElementById('downloadPayrollBtn').style.display = 'inline-flex';
      }, 2000);
    }
    
    /**
     * Updates the payroll display with calculated values
     * @param {Object} payrollData - The calculated payroll data
     */
    function updatePayrollDisplay(payrollData) {
      // In a real application, this would update the UI with actual calculated values
      /*
      document.querySelector('.payroll-value.gross').textContent = formatCurrency(payrollData.grossPay);
      document.querySelector('.payroll-value.tax').textContent = formatCurrency(payrollData.taxDeduction);
      document.querySelector('.payroll-value.sss').textContent = formatCurrency(payrollData.sssDeduction);
      document.querySelector('.payroll-value.philhealth').textContent = formatCurrency(payrollData.philhealthDeduction);
      document.querySelector('.payroll-value.total-deductions').textContent = formatCurrency(payrollData.totalDeductions);
      document.querySelector('.payroll-value.net').textContent = formatCurrency(payrollData.netPay);
      */
    }
    
    /**
     * Formats a number as currency
     * @param {number} amount - The amount to format
     * @returns {string} Formatted currency string
     */
    function formatCurrency(amount) {
      return '$' + amount.toFixed(2).replace(/\d(?=(\d{3})+\.)/g, '$&,');
    }

    /**
     * Downloads the employee payroll slip
     */
    function downloadEmployeePayroll() {
      // Get employee data (in a real app, this would come from the server)
      const employeeData = {
        name: 'John Diaz',
        id: 'EMP002',
        position: 'Electrician',
        employmentType: 'Regular',
        hoursWorked: 160,
        hourlyRate: 35,
        grossPay: 5600,
        taxDeduction: 840,
        sssContribution: 280,
        philhealth: 168,
        netPay: 4312
      };
      
      // Create payroll slip content
      const payrollContent = `
Employee Payroll Slip
====================

Employee Name: ${employeeData.name}
Employee ID: ${employeeData.id}
Position: ${employeeData.position}
Employment Type: ${employeeData.employmentType}

Pay Period: December 1-15, 2025

Hours Worked: ${employeeData.hoursWorked}
Hourly Rate: $${employeeData.hourlyRate.toFixed(2)}

Gross Pay: $${employeeData.grossPay.toFixed(2)}
Tax Deduction: -$${employeeData.taxDeduction.toFixed(2)}
SSS Contribution: -$${employeeData.sssContribution.toFixed(2)}
PhilHealth: -$${employeeData.philhealth.toFixed(2)}
--------------------------------
Net Pay: $${employeeData.netPay.toFixed(2)}

Generated on: ${new Date().toLocaleDateString()}
      `.trim();
      
      // Create blob and download
      const blob = new Blob([payrollContent], { type: 'text/plain;charset=utf-8;' });
      const url = URL.createObjectURL(blob);
      const link = document.createElement('a');
      link.setAttribute('href', url);
      link.setAttribute('download', `payroll_${employeeData.id}_${new Date().toISOString().slice(0, 10)}.txt`);
      link.style.visibility = 'hidden';
      document.body.appendChild(link);
      link.click();
      document.body.removeChild(link);
    }

    /**
     * Shows a loading spinner
     */
    function showLoadingSpinner(container) {
      if (!container) return;
      
      // Create spinner element
      const spinner = document.createElement('div');
      spinner.className = 'loading-spinner';
      spinner.innerHTML = `
        <div class="spinner-content">
          <div class="spinner"></div>
          <p>Generating payroll...</p>
        </div>
      `;
      
      // Add spinner styles if not already present
      if (!document.getElementById('spinner-styles')) {
        const style = document.createElement('style');
        style.id = 'spinner-styles';
        style.textContent = `
          .loading-spinner {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(255, 255, 255, 0.8);
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 9999;
          }
          
          .spinner-content {
            text-align: center;
          }
          
          .spinner {
            border: 4px solid #f3f3f3;
            border-top: 4px solid #f59e0b;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            animation: spin 1s linear infinite;
            margin: 0 auto 10px;
          }
          
          @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
          }
        `;
        document.head.appendChild(style);
      }
      
      container.appendChild(spinner);
    }

    /**
     * Hides the loading spinner
     */
    function hideLoadingSpinner() {
      const spinner = document.querySelector('.loading-spinner');
      if (spinner) {
        spinner.remove();
      }
    }

    /**
     * Calculates payroll based on employment type with consistent field interpretation
     * @param {Object} employee - Employee data object
     * @param {string} employee.employmentType - Type of employment ('Daily', 'Regular', 'Contractual')
     * @param {number} employee.hourlyRate - Hourly rate for daily employees
     * @param {number} employee.monthlySalary - Monthly salary for regular/contractual employees
     * @param {number} employee.hoursWorked - Total hours worked in the period
     * @param {number} employee.overtimeHours - Overtime hours worked
     * @returns {Object} Payroll calculation results
     */
    function calculatePayroll(employee) {
      // Validate required fields based on employment type
      if (!employee.employmentType) {
        throw new Error('Employment type is required');
      }

      let grossPay = 0;
      let overtimePay = 0;
      const overtimeRate = 1.5; // Standard overtime multiplier

      switch (employee.employmentType.toLowerCase()) {
        case 'daily':
          // Daily employees are paid hourly
          if (typeof employee.hourlyRate !== 'number' || employee.hourlyRate <= 0) {
            throw new Error('Valid hourlyRate is required for Daily employees');
          }
          
          // Calculate regular pay (up to 8 hours per day)
          const regularHours = Math.min(employee.hoursWorked, 8);
          const regularPay = regularHours * employee.hourlyRate;
          
          // Calculate overtime pay for hours beyond 8 per day
          const dailyOvertime = Math.max(0, employee.hoursWorked - 8);
          overtimePay = dailyOvertime * employee.hourlyRate * overtimeRate;
          
          grossPay = regularPay + overtimePay;
          break;

        case 'regular':
        case 'contractual':
          // Regular and Contractual employees receive a monthly salary
          if (typeof employee.monthlySalary !== 'number' || employee.monthlySalary <= 0) {
            throw new Error('Valid monthlySalary is required for Regular/Contractual employees');
          }
          
          // Convert monthly salary to daily rate for calculation
          // Assuming 22 working days in a month
          const dailyRate = employee.monthlySalary / 22;
          
          // Calculate pay based on days worked
          // Assuming 8 hours per day as standard
          const daysWorked = employee.hoursWorked / 8;
          grossPay = daysWorked * dailyRate;
          
          // Calculate overtime pay
          if (employee.overtimeHours > 0) {
            // Overtime is calculated based on hourly rate derived from daily rate
            const hourlyRateFromDaily = dailyRate / 8;
            overtimePay = employee.overtimeHours * hourlyRateFromDaily * overtimeRate;
            grossPay += overtimePay;
          }
          break;

        default:
          throw new Error(`Unsupported employment type: ${employee.employmentType}`);
      }

      // Calculate deductions (simplified example)
      const taxRate = 0.15; // 15% tax
      const sssContribution = 0.05; // 5% SSS
      const philhealthContribution = 0.03; // 3% PhilHealth
      
      const taxDeduction = grossPay * taxRate;
      const sssDeduction = grossPay * sssContribution;
      const philhealthDeduction = grossPay * philhealthContribution;
      
      const totalDeductions = taxDeduction + sssDeduction + philhealthDeduction;
      const netPay = grossPay - totalDeductions;

      return {
        grossPay: parseFloat(grossPay.toFixed(2)),
        overtimePay: parseFloat(overtimePay.toFixed(2)),
        taxDeduction: parseFloat(taxDeduction.toFixed(2)),
        sssDeduction: parseFloat(sssDeduction.toFixed(2)),
        philhealthDeduction: parseFloat(philhealthDeduction.toFixed(2)),
        totalDeductions: parseFloat(totalDeductions.toFixed(2)),
        netPay: parseFloat(netPay.toFixed(2))
      };
    }

    // Example usage:
    // const employeeData = {
    //   employmentType: 'Daily',
    //   hourlyRate: 500,
    //   hoursWorked: 10,
    //   overtimeHours: 2
    // };
    // 
    // const payroll = calculatePayroll(employeeData);
    // console.log(payroll);
  </script>
  
  <script>
    
    // Function to load group membership dynamically
    function loadGroupMembership() {
      // In a real application, this would fetch data from the server
      // For demo purposes, we'll use sample data
      
      // Sample group data for this employee
      const employeeGroups = [
        { name: 'Electrical Team 1', role: 'Electrician' },
        { name: 'Night Shift Laborers', role: 'Backup Electrician' }
      ];
      
      const groupMembershipElement = document.getElementById('groupMembership');
      
      if (employeeGroups.length === 0) {
        groupMembershipElement.innerHTML = '<div>No group memberships</div>';
        return;
      }
      
      let groupsHtml = '';
      employeeGroups.forEach(group => {
        groupsHtml += `<div>${group.name} (${group.role})</div>`;
      });
      
      groupMembershipElement.innerHTML = groupsHtml;
    }
    
    // Load group membership when page loads
    document.addEventListener('DOMContentLoaded', function() {
      loadGroupMembership();
    });
  </script>
</body>
</html>
