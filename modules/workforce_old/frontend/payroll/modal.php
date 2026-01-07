<!-- Generic Modal Template for Payroll Period -->
<div id="modalTemplate" class="modal">
  <div class="modal-content">

    <!-- Modal Header -->
    <div class="modal-header">
      <h3 id="modalTitle">Add Payroll Period</h3>
      <span class="close-btn" id="closeModal">&times;</span>
    </div>

    <!-- Modal Form -->
    <form id="modalForm">
      <!-- Payroll Period ID -->
      <div class="form-group">
        <label for="period_id">Payroll Period ID</label>
        <input type="text" id="period_id" name="period_id" readonly placeholder="Auto-generated">
      </div>

      <!-- Period Name -->
      <div class="form-group">
        <label for="periodName">Period Name</label>
        <input type="text" id="periodName" name="period_name" readonly placeholder="Auto-generated">
      </div>

      <!-- Start Date -->
      <div class="form-group">
        <label for="periodStart">Start Date *</label>
        <input type="date" id="periodStart" name="period_start" required>
      </div>

      <!-- End Date -->
      <div class="form-group">
        <label for="periodEnd">End Date *</label>
        <input type="date" id="periodEnd" name="period_end" required>
      </div>

      <!-- Pay Date -->
      <div class="form-group">
        <label for="pay_date">Pay Date *</label>
        <input type="date" id="pay_date" name="pay_date" required>
      </div>

      <!-- Status -->
      <div class="form-group">
        <label for="periodStatus">Status *</label>
        <select id="periodStatus" name="status" required>
          <option value="Open">Open</option>
          <option value="Closed">Closed</option>
          <option value="Processed">Processed</option>
        </select>
      </div>

      <!-- Processed By -->
      <div class="form-group">
        <label for="processedBy">Processed By</label>
        <input type="text" id="processedBy" name="processed_by" placeholder="Admin/User">
      </div>

      <!-- Processed Date -->
      <div class="form-group">
        <label for="processedDate">Processed Date</label>
        <input type="datetime-local" id="processedDate" name="processed_date">
      </div>

      <!-- Remarks -->
      <div class="form-group">
        <label for="remarks">Remarks</label>
        <textarea id="remarks" name="remarks" rows="2" placeholder="Optional notes"></textarea>
      </div>

      <!-- Modal Footer -->
      <div class="modal-footer">
        <button type="button" class="btn-cancel" id="cancelModalBtn">Cancel</button>
        <button type="submit" class="btn-save" id="modalActionBtn">Save</button>
      </div>
    </form>
  </div>
</div>
