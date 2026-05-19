CREATE TABLE projects (
  project_id SERIAL PRIMARY KEY,
  project_code VARCHAR(20) UNIQUE NOT NULL,
  project_name VARCHAR(255) NOT NULL,
  description TEXT,
  location VARCHAR(255),
  status VARCHAR(50) DEFAULT 'Planning' CHECK (status IN ('Planning','In Progress','Active','On Hold','Completed','Cancelled')),
  start_date DATE,
  end_date DATE,
  completion_rate NUMERIC(5,2) DEFAULT 0.00,
  project_manager_id INT, -- Soft FK to Workforce
  total_budget NUMERIC(15,2) DEFAULT 0.00
);

CREATE TABLE project_phases (
  phase_id SERIAL PRIMARY KEY,
  project_id INT REFERENCES projects(project_id) ON DELETE CASCADE,
  phase_name VARCHAR(100) NOT NULL,
  description TEXT,
  start_date DATE,
  end_date DATE,
  duration INT DEFAULT 0,
  status VARCHAR(50) DEFAULT 'Not Started' CHECK (status IN ('Not Started','In Progress','Completed'))
);

CREATE TABLE tasks (
  task_id SERIAL PRIMARY KEY,
  project_id INT REFERENCES projects(project_id) ON DELETE CASCADE,
  phase_id INT REFERENCES project_phases(phase_id) ON DELETE SET NULL,
  task_name VARCHAR(100) NOT NULL,
  description TEXT,
  assigned_to_employee_id INT, -- Soft FK to Workforce
  start_date DATE,
  due_date DATE,
  status VARCHAR(50) DEFAULT 'Not Started' CHECK (status IN ('Not Started','In Progress','Completed','On Hold')),
  priority VARCHAR(50) DEFAULT 'Medium' CHECK (priority IN ('Low','Medium','High','Urgent'))
);

CREATE TABLE audit_logs (
  log_id SERIAL PRIMARY KEY,
  user_id INT,
  project_id INT,
  user_name VARCHAR(100) DEFAULT 'System',
  action VARCHAR(50) NOT NULL,
  module VARCHAR(50) NOT NULL,
  details TEXT,
  record_id INT,
  ip_address VARCHAR(45),
  user_agent VARCHAR(255),
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

