<!-- ADD/EDIT GROUP ASSIGNMENT MODAL -->
<div id="groupAssignmentModal" class="modal">
  <div class="modal-content">
    <div class="modal-header">
      <h3 id="groupModalTitle">Add Group Assignment</h3>
      <span class="close-btn" id="closeGroupAssignmentModal">&times;</span>
    </div>

    <form id="groupAssignmentForm">
      <!-- Group Assignment ID (hidden for editing) -->
      <div class="form-group">
        <label for="group_assignment_id">Assignment ID</label>
        <input type="text" id="group_assignment_id" name="group_assignment_id" readonly class="input-readonly-bg">
      </div>

      <!-- Group -->
      <div class="form-group">
        <label for="groupSelect">Group *</label>
        <select id="groupSelect" name="group_id" required>
          <option value="">Select Group</option>
          <!-- Dynamically populated via AJAX -->
        </select>
      </div>

      <!-- Project -->
      <div class="form-group">
        <label for="projectSelectGroup">Project *</label>
        <select id="projectSelectGroup" name="project_id" required>
          <option value="">Select Project</option>
          <!-- Dynamically populated -->
        </select>
      </div>

      <!-- Phase -->
      <div class="form-group">
        <label for="phaseSelectGroup">Phase *</label>
        <select id="phaseSelectGroup" name="phase_id" required>
          <option value="">Select Phase</option>
          <!-- Dynamically populated based on selected project -->
        </select>
      </div>

      <!-- Task -->
      <div class="form-group">
        <label for="taskDescriptionGroup">Task *</label>
        <input type="text" id="taskDescriptionGroup" name="task_description" placeholder="Enter task name" required>
      </div>

      <!-- Role -->
      <div class="form-group">
        <label for="roleGroup">Role *</label>
        <input type="text" id="roleGroup" name="role" placeholder="Enter role" required>
      </div>

      <!-- Start Date -->
      <div class="form-group">
        <label for="startDateGroup">Start Date *</label>
        <input type="date" id="startDateGroup" name="start_date" required>
      </div>

      <!-- End Date -->
      <div class="form-group">
        <label for="endDateGroup">End Date *</label>
        <input type="date" id="endDateGroup" name="end_date" required>
      </div>

      <!-- Notes -->
      <div class="form-group">
        <label for="notesGroup">Notes</label>
        <textarea id="notesGroup" name="notes" rows="3" placeholder="Additional notes..."></textarea>
      </div>

      <!-- Status -->
      <div class="form-group">
        <label for="statusGroup">Status *</label>
        <select id="statusGroup" name="status" required>
          <option value="">Select Status</option>
          <option value="Active">Active</option>
          <option value="Completed">Completed</option>
          <option value="Cancelled">Cancelled</option>
        </select>
      </div>

      <!-- Submit -->
      <div class="modal-footer">
        <button type="button" class="btn-cancel" id="cancelGroupAssignmentModal">Cancel</button>
        <button type="submit" class="btn-save" id="groupAssignmentModalBtnText">Save Group Assignment</button>
      </div>
    </form>
  </div>
</div>
