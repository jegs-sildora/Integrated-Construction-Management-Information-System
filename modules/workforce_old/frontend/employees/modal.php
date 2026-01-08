<!-- EMPLOYEE MODAL -->
<div id="employeeModal" class="modal">
  <div class="modal-content">
    <div class="modal-header">
      <h3 id="employeeModalTitle">Add Employee</h3>
      <span class="close-btn" id="closeEmployeeModal">&times;</span>
    </div>

    <form id="employeeForm">
      <div class="form-group">
        <label for="employee_id">Employee ID</label>
        <input type="text" id="employee_id" name="employee_id" readonly class="input-readonly-bg">
      </div>

      <div class="form-row">
        <div class="form-group">
          <label for="first_name">First Name</label>
          <input type="text" id="first_name" name="first_name" placeholder="Enter first name" required>
        </div>


        <div class="form-group">
          <label for="middle_name">Middle Name</label>
          <input type="text" id="middle_name" name="middle_name" placeholder="Enter middle name">
        </div>

        
        <div class="form-group">
          <label for="last_name">Last Name</label>
          <input type="text" id="last_name" name="last_name" placeholder="Enter last name" required>
        </div>

      </div>

      <div class="form-row">

         <div class="form-group">
          <label for="suffix">Suffix</label>
          <input type="text" id="suffix" name="suffix" placeholder="e.g., Jr., Sr., III">
        </div>

      <div class="form-group">
        <label for="gender">Gender</label>
        <select id="gender" name="gender" required>
          <option value="">Select gender</option>
          <option value="Male">Male</option>
          <option value="Female">Female</option>
          <option value="Other">Other</option>
        </select>
      </div>

      <div class="form-group">
        <label for="age">Age</label>
        <input type="number" id="age" name="age" placeholder="Enter age" min="18" max="65">
      </div>
    </div>

     

      <div class="form-row">
        <div class="form-group">
          <label for="email">Email</label>
          <input type="email" id="email" name="email" placeholder="Enter email address">
        </div>
        <div class="form-group">
          <label for="phone">Phone</label>
          <input type="text" id="phone" name="phone" placeholder="Enter phone number">
        </div>
      </div>

      <div class="form-group">
        <label for="address">Address</label>
        <textarea id="address" name="address" placeholder="Enter full address"></textarea>
      </div>

      <div class="form-row">
        <div class="form-group">
          <label for="emergency_contact_name">Emergency Contact Name</label>
          <input type="text" id="emergency_contact_name" name="emergency_contact_name" placeholder="Enter emergency contact name">
        </div>
        <div class="form-group">
          <label for="emergency_contact_phone">Emergency Contact Phone</label>
          <input type="text" id="emergency_contact_phone" name="emergency_contact_phone" placeholder="Enter emergency contact phone">
        </div>
      </div>

      <div class="form-row">
        <div class="form-group">
          <label for="position">Position</label>
          <input type="text" id="position" name="position" placeholder="Enter job position">
        </div>
        <div class="form-group">
          <label for="skill_type">Skill Type</label>
          <input type="text" id="skill_type" name="skill_type" placeholder="Enter skill type">
        </div>
      </div>

      <div class="form-row">
        <div class="form-group">
          <label for="employment_type">Employment Type</label>
          <select id="employment_type" name="employment_type" required>
            <option value="">Select employment type</option>
            <option value="Daily">Daily</option>
            <option value="Regular">Regular</option>
            <option value="Contractual">Contractual</option>
          </select>
        </div>
        <div class="form-group">
          <label for="payment_type">Payment Type</label>
          <select id="payment_type" name="payment_type" disabled>
            <option value="">Select payment type</option>
            <option value="Daily">Daily</option>
            <option value="Monthly">Monthly</option>
          </select>
        </div>
        <div class="form-group">
          <label for="status">Status</label>
          <select id="status" name="status">
            <option value="">Select status</option>
            <option value="Active">Active</option>
            <option value="Inactive">Inactive</option>
            <option value="Terminated">Terminated</option>
          </select>
        </div>
      </div>

      <div class="form-row">
        <div class="form-group">
          <label for="daily_rate">Daily Rate</label>
          <input type="number" step="0.01" id="daily_rate" name="daily_rate" placeholder="Enter daily rate">
        </div>
        <div class="form-group">
          <label for="monthly_salary">Monthly Salary</label>
          <input type="number" step="0.01" id="monthly_salary" name="monthly_salary" placeholder="Enter monthly salary">
        </div>
      </div>

      <div class="form-row">
      <div class="form-group">
        <label for="start_date">Start Date</label>
        <input type="date" id="start_date" name="start_date">
      </div>
      <div class="form-group">
        <label for="end_date">End Date</label>
        <input type="date" id="end_date" name="end_date" disabled>
      </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn-cancel" id="cancelEmployeeModal">Cancel</button>
        <button type="submit" class="btn-save" id="employeeModalBtnText">Add Employee</button>
      </div>
    </form>
  </div>
</div>


