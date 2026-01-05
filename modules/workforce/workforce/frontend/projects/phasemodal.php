<!-- PHASE MODAL (Add and Edit) -->
<div id="phaseModal" class="modal">
  <div class="modal-content">
    <div class="modal-header">
      <h3 id="phaseModalTitle">Add Phase</h3>
      <span class="close-btn" id="closePhaseModal">&times;</span>
    </div>

    <form id="phaseForm">

      <div class="form-group">
        <label for="phase_id">Phase ID</label>
        <input type="text" id="phase_id" name="phase_id" readonly class="input-readonly-bg" />
      </div>

      <!-- Phase Name -->
      <div class="form-group">
        <label for="phase_name">Phase Name *</label>
        <input type="text" id="phase_name" name="phase_name" required placeholder="e.g. Site Preparation" />
      </div>

      <!-- Associated Project -->
      <div class="form-group">
        <label for="project_id">Associated Project *</label>
        <select id="project_id" name="project_id" required>
          <option value="">Select Project</option>
          <!-- Populated via AJAX -->
        </select>
      </div>

      <!-- Description -->
      <div class="form-group">
        <label for="description">Description</label>
        <textarea id="description" name="description" rows="3" placeholder="Brief description of the phase"></textarea>
      </div>

      <!-- Start Date -->
      <div class="form-group">
        <label for="start_date">Start Date</label>
        <input type="date" id="start_date" name="start_date" />
      </div>

      <!-- End Date -->
      <div class="form-group">
        <label for="end_date">Expected Completion</label>
        <input type="date" id="end_date" name="end_date" />
      </div>

      <!-- Status -->
      <div class="form-group">
        <label for="status">Status</label>
        <select id="status" name="status">
          <option value="Not Started">Not Started</option>
          <option value="In Progress">In Progress</option>
          <option value="Completed">Completed</option>
        </select>
      </div>

      <!-- Budget -->
      <div class="form-group">
        <label for="budget">Budget (₱)</label>
        <input type="number" id="budget" name="budget" step="1000" min="0" placeholder="e.g. 500000" />
      </div>

      <!-- Priority -->
      <div class="form-group">
        <label for="priority">Priority</label>
        <select id="priority" name="priority">
          <option value="Low">Low</option>
          <option value="Medium">Medium</option>
          <option value="High">High</option>
          <option value="Critical">Critical</option>
        </select>
      </div>

      <!-- Submit Buttons -->
      <div class="modal-footer">
        <button type="button" class="btn-cancel" id="cancelPhaseModal">Cancel</button>
        <button type="submit" class="btn-save" id="phaseModalBtnText">Add Phase</button>
      </div>
    </form>
  </div>
</div>
