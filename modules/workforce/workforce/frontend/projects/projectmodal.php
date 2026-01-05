<!-- PROJECT MODAL (Add/Edit) -->
<div id="projectModal" class="modal">
  <div class="modal-content">
    <div class="modal-header">
      <h3 id="projectModalTitle">Add Project</h3>
      <span class="close-btn" id="closeProjectModal">&times;</span>
    </div>

    <form id="projectForm">
      <!-- Project ID (hidden for editing) -->
      <div class="form-group">
        <label for="project_id">Project ID</label>
        <input type="text" id="project_id" name="project_id" readonly class="input-readonly-bg">
      </div>

      <!-- Project Name -->
      <div class="form-group">
        <label for="project_name">Project Name *</label>
        <input type="text" id="project_name" name="project_name" placeholder="Enter project name" required>
      </div>

      <!-- Project Manager -->
      <div class="form-group">
        <label for="managerSelect">Project Manager</label>
        <select id="managerSelect" name="project_manager_id" required>
          <option value="">Select Project Manager</option>
          <!-- Dynamically populated with project managers -->
        </select>
      </div>

      <!-- Description -->
      <div class="form-group">
        <label for="description">Description</label>
        <textarea id="description" name="description" rows="3"></textarea>
      </div>

      <!-- Start Date -->
      <div class="form-group">
        <label for="start_date">Start Date</label>
        <input type="date" id="start_date" name="start_date">
      </div>

      <!-- End Date -->
      <div class="form-group">
        <label for="end_date">End Date</label>
        <input type="date" id="end_date" name="end_date">
      </div>

      <!-- Status -->
      <div class="form-group">
        <label for="status">Status</label>
        <select id="status" name="status">
          <option value="Planning">Planning</option>
          <option value="In Progress">In Progress</option>
          <option value="On Hold">On Hold</option>
          <option value="Completed">Completed</option>
          <option value="Cancelled">Cancelled</option>
        </select>
      </div>

      <!-- Budget -->
      <div class="form-group">
        <label for="budget">Budget (₱)</label>
        <input type="number" step="0.01" id="budget" name="budget">
      </div>

      <!-- Action Buttons -->
      <div class="modal-footer">
        <button type="button" class="btn-cancel" id="cancelProjectModal">Cancel</button>
        <button type="submit" class="btn-save" id="projectModalBtnText">Add Project</button>
      </div>
    </form>
  </div>
</div>
