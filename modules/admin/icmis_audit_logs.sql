-- ==========================================================
-- ICMIS Audit Logs Schema
-- ==========================================================
-- Creates the icmis_audit_logs table for tracking user actions
-- across all modules in the system.
-- 
-- Usage: Run this SQL in phpMyAdmin or MySQL CLI
-- Note: The Logger class will auto-create this table if missing
-- ==========================================================

CREATE TABLE IF NOT EXISTS `icmis_audit_logs` (
    `log_id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id` INT(11) DEFAULT NULL COMMENT 'Reference to icmis_users table',
    `user_name` VARCHAR(100) DEFAULT 'System' COMMENT 'Cached username for faster display',
    `action` VARCHAR(50) NOT NULL COMMENT 'Action type: CREATE, UPDATE, DELETE, LOGIN, LOGOUT, VIEW, EXPORT, APPROVE, REJECT',
    `module` VARCHAR(50) NOT NULL COMMENT 'Module name: Project, Budget, Procurement, Workforce, Auth, Reports',
    `details` TEXT DEFAULT NULL COMMENT 'Human-readable description of the action',
    `record_id` INT(11) DEFAULT NULL COMMENT 'ID of the affected record (project_id, po_id, employee_id, etc.)',
    `ip_address` VARCHAR(45) DEFAULT NULL COMMENT 'Client IP address (supports IPv6)',
    `user_agent` VARCHAR(255) DEFAULT NULL COMMENT 'Browser/client user agent string',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT 'Timestamp when the action occurred',
    
    PRIMARY KEY (`log_id`),
    INDEX `idx_user_id` (`user_id`),
    INDEX `idx_action` (`action`),
    INDEX `idx_module` (`module`),
    INDEX `idx_created_at` (`created_at`),
    INDEX `idx_record_id` (`record_id`),
    
    CONSTRAINT `fk_audit_log_user` FOREIGN KEY (`user_id`) 
        REFERENCES `icmis_users` (`user_id`) 
        ON DELETE SET NULL 
        ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Audit trail for tracking user actions across all ICMIS modules';

-- ==========================================================
-- Sample Query: View Recent Activity
-- ==========================================================
-- SELECT 
--     l.created_at, l.user_name, l.action, l.module, l.details
-- FROM icmis_audit_logs l
-- ORDER BY l.created_at DESC
-- LIMIT 50;

-- ==========================================================
-- Sample Query: Activity by Module
-- ==========================================================
-- SELECT 
--     module, 
--     action, 
--     COUNT(*) as count 
-- FROM icmis_audit_logs 
-- GROUP BY module, action 
-- ORDER BY module, count DESC;

-- ==========================================================
-- Sample Query: User Activity Report
-- ==========================================================
-- SELECT 
--     user_name,
--     COUNT(*) as total_actions,
--     DATE(created_at) as date
-- FROM icmis_audit_logs
-- WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
-- GROUP BY user_id, DATE(created_at)
-- ORDER BY date DESC, total_actions DESC;
