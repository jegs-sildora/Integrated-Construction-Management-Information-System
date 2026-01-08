<!-- ADD TASK SIDE MODAL -->
<div id="addTaskModal" class="side-modal">
  <div class="side-modal-content">
    <div class="side-modal-header">
      <h3>Create New Task</h3>
      <button class="close-modal" id="closeAddTask">&times;</button>
    </div>

    <form id="taskForm">
      <!-- Task Name -->
      <div class="form-group">
        <label for="taskName">Task Name *</label>
        <input type="text" id="taskName" name="task_name" placeholder="e.g. Concrete Pouring" required />
      </div>

      <!-- Associated Project & Phase -->
      <div class="form-row">
        <div class="form-group">
          <label for="associatedProject">Associated Project *</label>
          <select id="associatedProject" name="project_id" required>
            <option value="">Select Project</option>
            <!-- populated via AJAX -->
          </select>
        </div>

        <div class="form-group">
          <label for="associatedPhase">Associated Phase *</label>
          <select id="associatedPhase" name="phase_id" required>
            <option value="">Select Phase</option>
            <!-- populated via AJAX -->
          </select>
        </div>
      </div>

      <!-- Description -->
      <div class="form-group">
        <label for="taskDescription">Task Description</label>
        <textarea id="taskDescription" name="description" rows="3" placeholder="Brief description of the task"></textarea>
      </div>

      <!-- Dates -->
      <div class="form-row">
        <div class="form-group">
          <label for="taskStartDate">Start Date</label>
          <input type="date" id="taskStartDate" name="start_date" />
        </div>

        <div class="form-group">
          <label for="taskEndDate">Expected Completion</label>
          <input type="date" id="taskEndDate" name="end_date" />
        </div>
      </div>

      <!-- Budget & Priority -->
      <div class="form-row">
        <div class="form-group">
          <label for="taskBudget">Budget Allocation (₱)</label>
          <input type="number" id="taskBudget" name="budget" step="1000" min="0" placeholder="e.g. 50000" />
        </div>

        <div class="form-group">
          <label for="taskPriority">Priority</label>
          <select id="taskPriority" name="priority">
            <option value="Low">Low</option>
            <option value="Medium">Medium</option>
            <option value="High">High</option>
            <option value="Critical">Critical</option>
          </select>
        </div>
      </div>

      <!-- Status & Required Skills -->
      <div class="form-row">
        <div class="form-group">
          <label for="taskStatus">Status</label>
          <select id="taskStatus" name="status">
            <option value="Not Started">Not Started</option>
            <option value="In Progress">In Progress</option>
            <option value="On Hold">On Hold</option>
            <option value="Completed">Completed</option>
          </select>
        </div>

        <div class="form-group">
          <label for="requiredSkills">Required Skills</label>
          <select id="requiredSkills" name="skills[]" multiple>
            <option value="Foreman">Foreman</option>
            <option value="Electrician">Electrician</option>
            <option value="Plumber">Plumber</option>
            <option value="Mason">Mason</option>
            <option value="Welder">Welder</option>
          </select>
        </div>
      </div>

      <!-- Submit -->
      <button type="submit" class="add-btn" style="margin-top:10px;">
        <span class="material-symbols-outlined">add</span>
        Create Task
      </button>
    </form>
  </div>
</div>
