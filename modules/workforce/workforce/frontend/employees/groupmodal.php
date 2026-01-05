<!-- GROUP MODAL -->
<div id="groupModal" class="modal">
  <div class="modal-content">
    <div class="modal-header">
      <h3 id="groupModalTitle">Create Group</h3>
      <span class="close-btn" id="closeGroupModal">&times;</span>
    </div>

    <form id="groupForm">
      <div class="form-group">
        <label for="group_id">Group ID</label>
        <input type="text" id="group_id" name="group_id" readonly class="input-readonly-bg">
      </div>

      <div class="form-group">
        <label for="group_name">Group Name</label>
        <input type="text" id="group_name" name="group_name" placeholder="Enter group name" required>
      </div>

      <div class="form-group">
        <label for="description">Description</label>
        <textarea id="description" name="description" placeholder="Enter description"></textarea>
      </div>

      <div class="form-group">
        <label for="groupLeaderSelect">Select Leader</label>
        <select id="groupLeaderSelect" name="group_leader_id" required>
          <option value="">Select Leader</option>
          <!-- Dynamic options for employee leaders will be populated here -->
        </select>
      </div>

      <div id="group_members_container" class="form-group">
        <label>Group Members</label>
        <!-- Dynamic checkboxes for group members will be populated here -->
      </div>

      <div class="modal-footer">
        <button type="button" class="btn-cancel" id="cancelGroupModal">Cancel</button>
        <button type="submit" class="btn-save" id="groupModalBtnText">Create Group</button>
      </div>
    </form>
  </div>
</div>
