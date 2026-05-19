CREATE TABLE budget_proposals (
  proposal_id SERIAL PRIMARY KEY,
  project_id INT NOT NULL, -- Soft FK to Project
  phase_id INT, -- Soft FK to Project
  code VARCHAR(20),
  title VARCHAR(255) NOT NULL,
  description TEXT,
  total_amount NUMERIC(15,2) NOT NULL,
  status VARCHAR(50) DEFAULT 'DRAFT' CHECK (status IN ('DRAFT','PENDING','APPROVED','REJECTED')),
  created_by INT, -- Soft FK to Auth
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE budget_line_items (
  line_item_id SERIAL PRIMARY KEY,
  proposal_id INT REFERENCES budget_proposals(proposal_id) ON DELETE CASCADE,
  category VARCHAR(50) CHECK (category IN ('MATERIAL','LABOR','EQUIPMENT')),
  item_name VARCHAR(255) NOT NULL,
  quantity NUMERIC(10,2),
  unit_cost NUMERIC(15,2),
  duration NUMERIC(10,2) DEFAULT 1.00,
  subtotal NUMERIC(15,2)
);

CREATE TABLE budget_expenses (
  expense_id SERIAL PRIMARY KEY,
  project_id INT NOT NULL, -- Soft FK to Project
  phase_id INT, -- Soft FK to Project
  supplier_id INT, -- Soft FK to Procurement
  category VARCHAR(50) CHECK (category IN ('MATERIALS','LABOR','EQUIPMENT')),
  description TEXT,
  amount NUMERIC(15,2) NOT NULL,
  expense_date DATE NOT NULL,
  status VARCHAR(50) DEFAULT 'PENDING' CHECK (status IN ('PENDING','APPROVED','REJECTED')),
  created_by INT -- Soft FK to Auth
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

