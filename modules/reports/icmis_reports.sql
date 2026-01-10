-- =====================================================
-- ICMIS Reports Module - Database Schema
-- =====================================================
-- Run this SQL to create the required tables for the Reports module

-- Generated Reports Log Table
CREATE TABLE IF NOT EXISTS `icmis_generated_reports` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `report_name` VARCHAR(100) NOT NULL,
    `category` VARCHAR(50) NOT NULL COMMENT 'budget, procurement, project, workforce',
    `project_id` INT(11) DEFAULT NULL,
    `project_name` VARCHAR(200) DEFAULT NULL,
    `generated_by` VARCHAR(100) NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    INDEX `idx_category` (`category`),
    INDEX `idx_project_id` (`project_id`),
    INDEX `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Sample Data (Optional - Remove in production)
INSERT INTO `generated_reports` (`report_name`, `category`, `project_id`, `project_name`, `generated_by`, `created_at`) VALUES
('Budget Summary', 'budget', 1, 'Sample Project', 'Admin', NOW() - INTERVAL 1 DAY),
('Expense Log', 'budget', 1, 'Sample Project', 'Admin', NOW() - INTERVAL 2 DAY),
('Inventory Status', 'procurement', NULL, 'All Projects', 'Admin', NOW() - INTERVAL 3 DAY),
('Project Summary', 'project', 1, 'Sample Project', 'Admin', NOW() - INTERVAL 4 DAY),
('Employee Roster', 'workforce', NULL, 'All Projects', 'Admin', NOW() - INTERVAL 5 DAY);
