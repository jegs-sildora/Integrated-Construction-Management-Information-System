-- db/init/reports.sql
CREATE TABLE generated_reports (
  report_id SERIAL PRIMARY KEY,
  project_id INT,
  report_type VARCHAR(50),
  category VARCHAR(50),
  report_name VARCHAR(255),
  generated_by VARCHAR(100),
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Optional: Insert dummy data for testing
-- INSERT INTO generated_reports (project_id, report_type, category, report_name, generated_by) 
-- VALUES (1, 'budget-summary', 'Budget', 'Project Budget Summary', 'Admin');
