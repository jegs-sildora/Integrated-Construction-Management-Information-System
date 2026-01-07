<!-- ADD/EDIT ASSIGNMENT MODAL -->
<div id="assignmentModal" class="modal">
  <div class="modal-content">
    <div class="modal-header">
      <h3 id="modalTitle">Add Assignment</h3>
      <span class="close-btn" id="closeAssignmentModal">&times;</span>
    </div>

    <form id="assignmentForm">
      <!-- Assignment ID (hidden for editing) -->
      <div class="form-group">
        <label for="assignment_id">Assignment ID</label>
        <input type="text" id="assignment_id" name="assignment_id" readonly class="input-readonly-bg">
      </div>

      <!-- Employee -->
      <div class="form-group">
        <label for="employeeSelect">Employee *</label>
        <select id="employeeSelect" name="employee_id" required>
          <option value="">Select Employee</option>
          <!-- Dynamically populated -->
        </select>
      </div>

      <!-- Project -->
      <div class="form-group">
        <label for="projectSelect">Project *</label>
        <select id="projectSelect" name="project_id" required>
          <option value="">Select Project</option>
          <!-- Dynamically populated -->
        </select>
      </div>

      <!-- Phase -->
      <div class="form-group">
        <label for="phaseSelect">Phase *</label>
        <select id="phaseSelect" name="phase_id" required>
          <option value="">Select Phase</option>
          <!-- Dynamically populated based on selected project -->
        </select>
      </div>

      <!-- Task -->
      <div class="form-group">
        <label for="taskName">Task *</label>
        <input type="text" id="taskName" name="task_name" placeholder="Enter task name" required>
      </div>

      <!-- Role -->
      <div class="form-group">
        <label for="role">Role *</label>
        <input type="text" id="role" name="role" placeholder="Enter role" required>
      </div>

      <!-- Start Date -->
      <div class="form-group">
        <label for="startDate">Start Date *</label>
        <input type="date" id="startDate" name="start_date" required>
      </div>

      <!-- End Date -->
      <div class="form-group">
        <label for="endDate">End Date *</label>
        <input type="date" id="endDate" name="end_date" required>
      </div>

      <!-- Notes -->
      <div class="form-group">
        <label for="notes">Notes</label>
        <textarea id="notes" name="notes" rows="3" placeholder="Additional notes..."></textarea>
      </div>

      <!-- Submit -->
      <div class="modal-footer">
        <button type="button" class="btn-cancel" id="cancelAssignmentModal">Cancel</button>
        <button type="submit" class="btn-save" id="assignmentModalBtnText">Save Assignment</button>
      </div>
    </form>
  </div>
</div>



<!-- BULK ASSIGNMENT MODAL -->
<div id="bulkAssignmentModal" class="modal">
  <div class="modal-content">
    <div class="modal-header">
      <h3>Bulk Assignment</h3>
      <span class="close-btn" id="closeBulkModal">&times;</span>
    </div>

    <form id="bulkAssignmentForm">
      <!-- Search Employees -->
      <div class="form-group">
        <label for="employeeSearch">Search Employees</label>
        <input type="text" id="employeeSearch" placeholder="Search by name or ID">
      </div>

      <div class="bulk-employee-selection">
        <label>
          <input type="checkbox" id="selectAllEmployees"> Select All
        </label>
      </div>

      <!-- Employee List -->
      <div class="form-group" style="max-height:300px; overflow-y:auto;">
        <ul id="employeeList" style="list-style:none; padding-left:0;">
          <!-- Dynamically loaded employee checkboxes -->
        </ul>
      </div>

      <!-- Project -->
      <div class="form-group">
        <label for="bulkProjectSelect">Project *</label>
        <select id="bulkProjectSelect" name="project_id" required>
          <option value="">Select Project</option>
        </select>
      </div>

      <!-- Phase -->
      <div class="form-group">
        <label for="bulkPhaseSelect">Phase *</label>
        <select id="bulkPhaseSelect" name="phase_id" required>
          <option value="">Select Phase</option>
        </select>
      </div>

      <!-- Task -->
      <div class="form-group">
        <label for="bulkTaskName">Task *</label>
        <input type="text" id="bulkTaskName" name="task_name" placeholder="Enter task name" required>
      </div>

      <!-- Role -->
      <div class="form-group">
        <label for="bulkRole">Role *</label>
        <input type="text" id="bulkRole" name="role" placeholder="Enter role" required>
      </div>

      <!-- Start Date -->
      <div class="form-group">
        <label for="bulkStartDate">Start Date *</label>
        <input type="date" id="bulkStartDate" name="start_date" required>
      </div>

      <!-- End Date -->
      <div class="form-group">
        <label for="bulkEndDate">End Date</label>
        <input type="date" id="bulkEndDate" name="end_date">
      </div>

      <!-- Notes -->
      <div class="form-group">
        <label for="bulkNotes">Notes</label>
        <textarea id="bulkNotes" name="notes" rows="3" placeholder="Additional notes..."></textarea>
      </div>

      <div class="modal-footer">
        <button type="button" class="btn-cancel" id="cancelBulkAssignmentModal">Cancel</button>
        <button type="submit" class="btn-save" id="bulkAssignmentModalBtnText">Assign to Selected Employees</button>
      </div>
    </form>
  </div>
</div>
