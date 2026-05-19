CREATE TABLE suppliers (
  supplier_id SERIAL PRIMARY KEY,
  supplier_name VARCHAR(100) NOT NULL,
  contact_person VARCHAR(100),
  contact_number VARCHAR(20),
  email VARCHAR(100),
  address TEXT,
  status VARCHAR(50) DEFAULT 'Active' CHECK (status IN ('Active','Inactive'))
);

CREATE TABLE inventory (
  item_id SERIAL PRIMARY KEY,
  item_name VARCHAR(255) NOT NULL,
  category VARCHAR(100) DEFAULT 'Uncategorized',
  quantity NUMERIC(10,2) DEFAULT 0.00,
  unit VARCHAR(50),
  project_id INT, -- Soft FK to Project
  phase_id INT, -- Soft FK to Project
  unit_cost NUMERIC(15,2) DEFAULT 0.00,
  last_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE purchase_orders (
  po_id SERIAL PRIMARY KEY,
  po_reference VARCHAR(50) NOT NULL,
  project_id INT NOT NULL, -- Soft FK to Project
  phase_id INT, -- Soft FK to Project
  supplier_id INT REFERENCES suppliers(supplier_id) ON DELETE CASCADE,
  order_title VARCHAR(255),
  order_date DATE DEFAULT CURRENT_DATE,
  total_amount NUMERIC(15,2) DEFAULT 0.00,
  status VARCHAR(50) DEFAULT 'PENDING' CHECK (status IN ('PENDING','APPROVED','REJECTED','COMPLETED')),
  created_by_user_id INT -- Soft FK to Auth
);

CREATE TABLE purchase_order_items (
  po_item_id SERIAL PRIMARY KEY,
  po_id INT REFERENCES purchase_orders(po_id) ON DELETE CASCADE,
  inventory_item_id INT REFERENCES inventory(item_id) ON DELETE SET NULL,
  item_name VARCHAR(255) NOT NULL,
  quantity NUMERIC(10,2) NOT NULL,
  unit_cost NUMERIC(15,2) NOT NULL,
  total_cost NUMERIC(15,2) NOT NULL
);

CREATE TABLE stock_in (
  stock_in_id SERIAL PRIMARY KEY,
  po_id INT REFERENCES purchase_orders(po_id) ON DELETE CASCADE,
  item_id INT REFERENCES inventory(item_id) ON DELETE CASCADE,
  quantity_received INT NOT NULL,
  unit_cost NUMERIC(15,2) DEFAULT 0.00,
  total_cost NUMERIC(15,2) DEFAULT 0.00,
  date_received DATE DEFAULT CURRENT_DATE
);

CREATE TABLE stock_out (
  stock_out_id SERIAL PRIMARY KEY,
  item_id INT REFERENCES inventory(item_id) ON DELETE CASCADE,
  quantity INT NOT NULL,
  issued_to_employee_id INT, -- Soft FK to Workforce
  project_id INT, -- Soft FK to Project
  date_issued DATE DEFAULT CURRENT_DATE
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

