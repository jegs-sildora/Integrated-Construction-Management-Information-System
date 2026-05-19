CREATE TABLE job_titles (
  job_title_id SERIAL PRIMARY KEY,
  title_name VARCHAR(100) NOT NULL,
  department VARCHAR(50),
  description TEXT,
  default_daily_rate NUMERIC(10,2),
  default_monthly_salary NUMERIC(15,2),
  is_active BOOLEAN DEFAULT TRUE,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE employees (
  employee_id SERIAL PRIMARY KEY,
  employee_code VARCHAR(20) UNIQUE NOT NULL,
  user_id INT UNIQUE, -- Soft FK to Auth
  job_title_id INT REFERENCES job_titles(job_title_id) ON DELETE SET NULL,
  employment_type VARCHAR(50) DEFAULT 'Full-time',
  payment_type VARCHAR(50) DEFAULT 'Monthly',
  daily_rate NUMERIC(10,2) DEFAULT 0.00,
  monthly_salary NUMERIC(15,2) DEFAULT 0.00,
  bank_name VARCHAR(100),
  bank_account VARCHAR(50),
  emergency_contact_name VARCHAR(100),
  emergency_contact_phone VARCHAR(20),
  supervisor_id INT REFERENCES employees(employee_id) ON DELETE SET NULL,
  notes TEXT,
  first_name VARCHAR(50) NOT NULL,
  last_name VARCHAR(50) NOT NULL,
  suffix VARCHAR(10),
  gender VARCHAR(50) CHECK (gender IN ('Male','Female','Other','Prefer not to say')),
  birthday DATE,
  email VARCHAR(100),
  phone VARCHAR(20),
  address TEXT,
  status VARCHAR(50) DEFAULT 'Active' CHECK (status IN ('Active','Inactive','Terminated')),
  hire_date DATE
);

CREATE TABLE employee_groups (
  group_id SERIAL PRIMARY KEY,
  group_code VARCHAR(50),
  group_name VARCHAR(100) NOT NULL,
  group_leader_id INT REFERENCES employees(employee_id) ON DELETE SET NULL,
  description TEXT
);

CREATE TABLE group_memberships (
  membership_id SERIAL PRIMARY KEY,
  employee_id INT REFERENCES employees(employee_id) ON DELETE CASCADE,
  group_id INT REFERENCES employee_groups(group_id) ON DELETE CASCADE,
  role_in_group VARCHAR(100),
  joined_date DATE
);

CREATE TABLE assignments (
  assignment_id SERIAL PRIMARY KEY,
  employee_id INT REFERENCES employees(employee_id) ON DELETE CASCADE,
  project_id INT NOT NULL, -- Soft FK to Project
  phase_id INT, -- Soft FK to Project
  role VARCHAR(100),
  task_description TEXT,
  start_date DATE,
  end_date DATE,
  status VARCHAR(50) DEFAULT 'Active' CHECK (status IN ('Active','Completed','Cancelled'))
);

CREATE TABLE attendance (
  attendance_id SERIAL PRIMARY KEY,
  employee_id INT REFERENCES employees(employee_id) ON DELETE CASCADE,
  project_id INT, -- Soft FK to Project
  attendance_date DATE NOT NULL,
  time_in TIME,
  time_out TIME,
  status VARCHAR(50) NOT NULL CHECK (status IN ('Present','Absent','Late','On Leave')),
  remarks TEXT
);

CREATE TABLE payroll_periods (
  period_id SERIAL PRIMARY KEY,
  start_date DATE NOT NULL,
  end_date DATE NOT NULL,
  pay_date DATE,
  status VARCHAR(50) DEFAULT 'Open' CHECK (status IN ('Open','Closed','Processing')),
  processed_by INT REFERENCES employees(employee_id) ON DELETE SET NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE payroll (
  payroll_id SERIAL PRIMARY KEY,
  employee_id INT REFERENCES employees(employee_id) ON DELETE CASCADE,
  period_id INT REFERENCES payroll_periods(period_id) ON DELETE CASCADE,
  hours_worked NUMERIC(5,2),
  gross_pay NUMERIC(10,2),
  net_pay NUMERIC(10,2),
  status VARCHAR(50) DEFAULT 'Calculated' CHECK (status IN ('Calculated','Approved','Processed'))
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

