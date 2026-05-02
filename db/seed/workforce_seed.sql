-- --- SEED DATA: PHILIPPINES CONSTRUCTION ---

INSERT INTO job_titles (title_name, department, description, default_daily_rate, default_monthly_salary) VALUES
('Project Manager', 'Management', 'Oversees construction projects from planning to completion.', 3000.00, 80000.00),
('Civil Engineer', 'Engineering', 'Designs and supervises construction projects.', 1500.00, 40000.00),
('Foreman', 'Operations', 'Supervises workers and operations on the construction site.', 900.00, 24000.00),
('Safety Officer', 'Safety', 'Ensures site compliance with occupational health and safety regulations.', 1000.00, 26000.00),
('Heavy Equipment Operator', 'Operations', 'Operates heavy machinery like cranes and excavators.', 800.00, 20800.00),
('Skilled Carpenter', 'Labor', 'Constructs and repairs building frameworks and structures.', 700.00, 18200.00),
('Mason', 'Labor', 'Lays building materials like brick, concrete block, and stone.', 700.00, 18200.00),
('Electrician', 'Labor', 'Installs and maintains electrical systems.', 750.00, 19500.00),
('General Laborer', 'Labor', 'Performs physical tasks like cleaning, digging, and carrying materials.', 500.00, 13000.00);

INSERT INTO employees (employee_code, first_name, last_name, gender, birthday, email, phone, address, job_title_id, employment_type, payment_type, daily_rate, monthly_salary, bank_name, bank_account, emergency_contact_name, emergency_contact_phone, status, hire_date) VALUES
('EMP-2024-001', 'Juan', 'Dela Cruz', 'Male', '1985-04-12', 'juan.delacruz@example.ph', '09171234567', 'Quezon City, Metro Manila', 1, 'Full-time', 'Monthly', 3000.00, 80000.00, 'BDO', '001234567890', 'Maria Dela Cruz', '09181234567', 'Active', '2020-01-15'),
('EMP-2024-002', 'Jose', 'Rizal', 'Male', '1990-06-19', 'jose.rizal@example.ph', '09172345678', 'Calamba, Laguna', 2, 'Full-time', 'Monthly', 1500.00, 40000.00, 'BPI', '009876543210', 'Francisco Mercado', '09182345678', 'Active', '2021-03-10'),
('EMP-2024-003', 'Andres', 'Bonifacio', 'Male', '1982-11-30', 'andres.bonifacio@example.ph', '09173456789', 'Tondo, Manila', 3, 'Full-time', 'Daily', 900.00, 24000.00, 'Metrobank', '002345678901', 'Gregoria de Jesus', '09183456789', 'Active', '2019-05-20'),
('EMP-2024-004', 'Gabriela', 'Silang', 'Female', '1993-03-19', 'gabriela.silang@example.ph', '09174567890', 'Santa Cruz, Manila', 4, 'Full-time', 'Monthly', 1000.00, 26000.00, 'UnionBank', '003456789012', 'Diego Silang', '09184567890', 'Active', '2022-02-01'),
('EMP-2024-005', 'Apolinario', 'Mabini', 'Male', '1988-07-23', 'apolinario.mabini@example.ph', '09175678901', 'Tanauan, Batangas', 5, 'Full-time', 'Daily', 800.00, 20800.00, 'Security Bank', '004567890123', 'Inocencio Mabini', '09185678901', 'Active', '2021-08-15'),
('EMP-2024-006', 'Melchora', 'Aquino', 'Female', '1995-01-06', 'melchora.aquino@example.ph', '09176789012', 'Caloocan, Metro Manila', 6, 'Contract', 'Daily', 700.00, 18200.00, 'RCBC', '005678901234', 'Fulgencio Ramos', '09186789012', 'Active', '2023-01-10'),
('EMP-2024-007', 'Emilio', 'Aguinaldo', 'Male', '1992-03-22', 'emilio.aguinaldo@example.ph', '09177890123', 'Kawit, Cavite', 7, 'Contract', 'Daily', 700.00, 18200.00, 'PNB', '006789012345', 'Carlos Aguinaldo', '09187890123', 'Active', '2022-11-05'),
('EMP-2024-008', 'Marcelo', 'Del Pilar', 'Male', '1989-08-30', 'marcelo.delpilar@example.ph', '09178901234', 'Bulacan, Bulacan', 8, 'Contract', 'Daily', 750.00, 19500.00, 'EastWest', '007890123456', 'Julian Del Pilar', '09188901234', 'Active', '2020-09-12'),
('EMP-2024-009', 'Gregorio', 'Del Pilar', 'Male', '1998-11-14', 'gregorio.delpilar@example.ph', '09179012345', 'Bulacan, Bulacan', 9, 'Contract', 'Daily', 500.00, 13000.00, 'LandBank', '008901234567', 'Fernando Del Pilar', '09189012345', 'Active', '2023-05-20'),
('EMP-2024-010', 'Teresa', 'Magbanua', 'Female', '1996-10-13', 'teresa.magbanua@example.ph', '09170123456', 'Pototan, Iloilo', 9, 'Contract', 'Daily', 500.00, 13000.00, 'BDO', '009012345678', 'Juan Magbanua', '09180123456', 'Active', '2023-06-01');
