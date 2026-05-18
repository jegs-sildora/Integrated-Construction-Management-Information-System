CREATE TABLE users (
  user_id SERIAL PRIMARY KEY,
  full_name VARCHAR(100) NOT NULL,
  email VARCHAR(100) UNIQUE NOT NULL,
  password VARCHAR(255) NOT NULL,
  role VARCHAR(50) DEFAULT 'Staff' CHECK (role IN ('Admin','Manager','Staff','Budget_Officer','Procurement_Officer')),
  avatar VARCHAR(255),
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
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

INSERT INTO users (full_name, email, password, role) VALUES ('John Doe', 'john.doe@icmis.com', '$2y$12$5F22dg8qdkuCcDgKPVzmPu41YGzA2whYUq7ddtXUTsxtWESuaVmJ2', 'Admin');
