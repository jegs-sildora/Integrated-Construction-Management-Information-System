-- phpMyAdmin SQL Dump
-- version 6.0.0-dev+20250914.f72491a1c0
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Jan 11, 2026 at 08:15 AM
-- Server version: 8.4.3
-- PHP Version: 8.4.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `icmis_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `budget_expenses`
--

CREATE TABLE `budget_expenses` (
  `expense_id` int NOT NULL,
  `project_id` int NOT NULL,
  `phase_id` int DEFAULT NULL,
  `supplier_id` int DEFAULT NULL,
  `category` enum('MATERIALS','LABOR','EQUIPMENT') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `description` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `amount` decimal(15,2) NOT NULL,
  `expense_date` date NOT NULL,
  `status` enum('PENDING','APPROVED','REJECTED') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'PENDING',
  `created_by` int DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `budget_expenses`
--

INSERT INTO `budget_expenses` (`expense_id`, `project_id`, `phase_id`, `supplier_id`, `category`, `description`, `amount`, `expense_date`, `status`, `created_by`) VALUES
(8, 1, 1, 1, 'MATERIALS', 'PO-2026-0001 - Phase 1 Materials', 1344.00, '2026-01-10', 'APPROVED', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `budget_generated_reports`
--

CREATE TABLE `budget_generated_reports` (
  `report_id` int NOT NULL,
  `project_id` int DEFAULT NULL,
  `report_type` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `report_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `generated_by` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `budget_generated_reports`
--

INSERT INTO `budget_generated_reports` (`report_id`, `project_id`, `report_type`, `report_name`, `generated_by`, `created_at`) VALUES
(31, 1, 'budget-summary', ' - Davao', 'John Doe', '2026-01-10 08:41:02'),
(32, 1, 'budget-summary', 'Budget Summary Report - Davao', 'John Doe', '2026-01-10 08:42:15'),
(33, 1, 'budget-summary', 'Budget Summary Report - Davao', 'System', '2026-01-10 18:19:39'),
(34, 1, 'budget-summary', 'Budget Summary Report - Davao', 'System', '2026-01-10 18:19:39'),
(35, 1, 'budget-summary', 'Budget Summary Report - Davao', 'System', '2026-01-10 18:19:41'),
(36, 1, 'budget-summary', 'Budget Summary Report - Davao', 'System', '2026-01-10 18:19:42'),
(37, 1, 'budget', 'Budget Summary', 'Admin', '2026-01-10 18:22:59'),
(38, 1, 'inventory', 'Inventory Status', 'Admin', '2026-01-10 18:24:04'),
(39, 1, 'budget', 'Budget Summary', 'Admin', '2026-01-10 18:25:57'),
(40, 1, 'inventory', 'Inventory Status Report', 'Admin', '2026-01-10 19:03:38'),
(41, 1, 'purchase', 'Purchase Orders Report', 'Admin', '2026-01-10 19:03:44'),
(42, 1, 'purchase', 'Purchase Orders Report', 'Admin', '2026-01-10 23:26:14'),
(43, 1, 'purchase', 'Purchase Orders Report', 'Admin', '2026-01-11 07:05:34');

-- --------------------------------------------------------

--
-- Table structure for table `budget_line_items`
--

CREATE TABLE `budget_line_items` (
  `line_item_id` int NOT NULL,
  `proposal_id` int NOT NULL,
  `category` enum('MATERIAL','LABOR','EQUIPMENT') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `item_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `quantity` decimal(10,2) DEFAULT NULL,
  `unit_cost` decimal(15,2) DEFAULT NULL,
  `duration` decimal(10,2) DEFAULT '1.00',
  `subtotal` decimal(15,2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `budget_line_items`
--

INSERT INTO `budget_line_items` (`line_item_id`, `proposal_id`, `category`, `item_name`, `quantity`, `unit_cost`, `duration`, `subtotal`) VALUES
(2, 1, 'MATERIAL', 'Tie Wire #16 (kg)', 112.00, 12.00, 1.00, 1344.00);

-- --------------------------------------------------------

--
-- Table structure for table `budget_proposals`
--

CREATE TABLE `budget_proposals` (
  `proposal_id` int NOT NULL,
  `project_id` int NOT NULL,
  `phase_id` int DEFAULT NULL,
  `code` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `title` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `total_amount` decimal(15,2) NOT NULL,
  `status` enum('DRAFT','PENDING','APPROVED','REJECTED') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'DRAFT',
  `created_by` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `budget_proposals`
--

INSERT INTO `budget_proposals` (`proposal_id`, `project_id`, `phase_id`, `code`, `title`, `description`, `total_amount`, `status`, `created_by`, `created_at`) VALUES
(1, 1, 1, 'BP-2026-0001', 'Phase 1: Mobilization — Budget Proposal', 'asd', 1344.00, 'APPROVED', 30, '2026-01-08 05:36:48');

-- --------------------------------------------------------

--
-- Table structure for table `icmis_audit_logs`
--

CREATE TABLE `icmis_audit_logs` (
  `log_id` int UNSIGNED NOT NULL,
  `user_id` int DEFAULT NULL COMMENT 'Reference to icmis_users table',
  `user_name` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT 'System' COMMENT 'Cached username for faster display',
  `action` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Action type: CREATE, UPDATE, DELETE, LOGIN, LOGOUT, VIEW, EXPORT, APPROVE, REJECT',
  `module` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Module name: Project, Budget, Procurement, Workforce, Auth, Reports',
  `details` text COLLATE utf8mb4_unicode_ci COMMENT 'Human-readable description of the action',
  `record_id` int DEFAULT NULL COMMENT 'ID of the affected record (project_id, po_id, employee_id, etc.)',
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Client IP address (supports IPv6)',
  `user_agent` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Browser/client user agent string',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Timestamp when the action occurred'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Audit trail for tracking user actions across all ICMIS modules';

--
-- Dumping data for table `icmis_audit_logs`
--

INSERT INTO `icmis_audit_logs` (`log_id`, `user_id`, `user_name`, `action`, `module`, `details`, `record_id`, `ip_address`, `user_agent`, `created_at`) VALUES
(1, 30, 'John Doe', 'CREATE', 'Project', 'Created new project: Davaoasdfasdf (PRJ-2026-003)', 5, '0', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-10 20:21:24'),
(2, NULL, 'System', 'EXPORT', 'Reports', 'Generated Report: Purchase Orders Report for Davao', 1, '0', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-10 23:26:14'),
(3, NULL, 'System', 'EXPORT', 'Reports', 'Generated Report: Purchase Orders Report for Davao', 1, '0', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-11 07:05:34'),
(4, 30, 'John Doe', 'LOGIN', 'Auth', 'User logged in successfully', NULL, '0', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-11 07:07:00'),
(5, 30, 'John Doe', 'CREATE', 'Project', 'Created new project: Villa Angela Clubhouseasdf (PRJ-2026-004)', 7, '0', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-11 07:07:26'),
(6, 30, 'John Doe', 'CREATE', 'Project', 'Created new project: asdfasdf (PRJ-2026-005)', 8, '0', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-11 07:10:46'),
(7, 30, 'John Doe', 'CREATE', 'Project', 'Created new project: asdfasdfasdfasdf (PRJ-2026-006)', 9, '0', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-11 07:11:42'),
(8, 30, 'John Doe', 'DELETE', 'Project', 'Deleted project ID: 9', 9, '0', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-11 07:11:47'),
(9, 30, 'John Doe', 'DELETE', 'Project', 'Deleted project ID: 8', 8, '0', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-11 07:11:49'),
(10, 30, 'John Doe', 'DELETE', 'Project', 'Deleted project ID: 7', 7, '0', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-11 07:11:51'),
(11, 30, 'John Doe', 'UPDATE', 'Project', 'Updated project: Davaoasdfasdf (PRJ-2026-003)', 5, '0', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-11 08:12:33'),
(12, 30, 'John Doe', 'DELETE', 'Project', 'Deleted project: Davaoasdfasdf (PRJ-2026-003)', 5, '0', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-11 08:12:41'),
(13, 30, 'John Doe', 'DELETE', 'Project', 'Deleted project: Villa Angela Clubhouse (PRJ-2026-002)', 4, '0', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-11 08:12:43');

-- --------------------------------------------------------

--
-- Table structure for table `icmis_generated_reports`
--

CREATE TABLE `icmis_generated_reports` (
  `id` int NOT NULL,
  `report_name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `category` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'budget, procurement, project, workforce',
  `project_id` int DEFAULT NULL,
  `project_name` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `generated_by` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `icmis_generated_reports`
--

INSERT INTO `icmis_generated_reports` (`id`, `report_name`, `category`, `project_id`, `project_name`, `generated_by`, `created_at`) VALUES
(1, 'Inventory Status Report', 'inventory', 1, 'Davao', 'Admin', '2026-01-10 19:03:38'),
(2, 'Purchase Orders Report', 'purchase', 1, 'Davao', 'Admin', '2026-01-10 19:03:44'),
(3, 'Purchase Orders Report', 'purchase', 1, 'Davao', 'Admin', '2026-01-10 23:26:14'),
(4, 'Purchase Orders Report', 'purchase', 1, 'Davao', 'Admin', '2026-01-11 07:05:34');

-- --------------------------------------------------------

--
-- Table structure for table `icmis_projects`
--

CREATE TABLE `icmis_projects` (
  `project_id` int NOT NULL,
  `project_code` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `project_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `location` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` enum('Planning','In Progress','Active','On Hold','Completed','Cancelled') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'Planning',
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `completion_rate` decimal(5,2) DEFAULT '0.00',
  `project_manager_id` int DEFAULT NULL,
  `total_budget` decimal(15,2) DEFAULT '0.00'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `icmis_projects`
--

INSERT INTO `icmis_projects` (`project_id`, `project_code`, `project_name`, `description`, `location`, `status`, `start_date`, `end_date`, `completion_rate`, `project_manager_id`, `total_budget`) VALUES
(1, 'PRJ-2026-001', 'Davao', 'Sample Construction Project', 'Davao City', 'Planning', '2026-01-07', '2027-01-07', 0.00, NULL, 50000000.00);

-- --------------------------------------------------------

--
-- Table structure for table `icmis_project_phases`
--

CREATE TABLE `icmis_project_phases` (
  `phase_id` int NOT NULL,
  `project_id` int NOT NULL,
  `phase_name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `duration` int DEFAULT '0' COMMENT 'Duration in days',
  `status` enum('Not Started','In Progress','Completed') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'Not Started'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `icmis_project_phases`
--

INSERT INTO `icmis_project_phases` (`phase_id`, `project_id`, `phase_name`, `description`, `start_date`, `end_date`, `duration`, `status`) VALUES
(1, 1, 'Phase 1: Mobilization', 'Phase 1', '2026-01-07', '2026-03-07', 59, 'Not Started');

-- --------------------------------------------------------

--
-- Table structure for table `icmis_tasks`
--

CREATE TABLE `icmis_tasks` (
  `task_id` int NOT NULL,
  `project_id` int NOT NULL,
  `phase_id` int DEFAULT NULL,
  `task_name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `assigned_to_employee_id` int DEFAULT NULL,
  `start_date` date DEFAULT NULL,
  `due_date` date DEFAULT NULL,
  `status` enum('Not Started','In Progress','Completed','On Hold') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'Not Started',
  `priority` enum('Low','Medium','High','Urgent') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'Medium'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `icmis_users`
--

CREATE TABLE `icmis_users` (
  `user_id` int NOT NULL,
  `full_name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `password` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `role` enum('Admin','Manager','Staff','Budget_Officer','Procurement_Officer') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'Staff',
  `avatar` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `icmis_users`
--

INSERT INTO `icmis_users` (`user_id`, `full_name`, `email`, `password`, `role`, `avatar`, `created_at`) VALUES
(1, 'System Admin', 'admin@icmis.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Admin', 'default_avatar.jpg', '2026-01-07 20:40:04'),
(2, 'Marco Villar', 'marco.v@icmis.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Manager', 'default_avatar.jpg', '2026-01-07 20:40:04'),
(3, 'Diana Lim', 'diana.l@icmis.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Manager', 'default_avatar.jpg', '2026-01-07 20:40:04'),
(4, 'Rico Morales', 'rico.m@icmis.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Manager', 'default_avatar.jpg', '2026-01-07 20:40:04'),
(5, 'Carla Sandoval', 'carla.s@icmis.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Manager', 'default_avatar.jpg', '2026-01-07 20:40:04'),
(6, 'Gina Reyes', 'gina.r@icmis.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Budget_Officer', 'default_avatar.jpg', '2026-01-07 20:40:04'),
(7, 'Paulo Santos', 'paulo.s@icmis.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Procurement_Officer', 'default_avatar.jpg', '2026-01-07 20:40:04'),
(8, 'Luis Ortega', 'luis.o@icmis.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Staff', 'default_avatar.jpg', '2026-01-07 20:40:04'),
(9, 'Tess Garcia', 'tess.g@icmis.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Staff', 'default_avatar.jpg', '2026-01-07 20:40:04'),
(10, 'Jun Abad', 'jun.a@icmis.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Staff', 'default_avatar.jpg', '2026-01-07 20:40:04'),
(11, 'Bert Torres', 'bert.t@icmis.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Staff', 'default_avatar.jpg', '2026-01-07 20:40:04'),
(12, 'Ricardo Dalisay', 'ricardo.d@icmis.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Staff', 'default_avatar.jpg', '2026-01-07 20:40:04'),
(13, 'Efren Bata', 'efren.b@icmis.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Staff', 'default_avatar.jpg', '2026-01-07 20:40:04'),
(14, 'Joel Cruz', 'joel.c@icmis.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Staff', 'default_avatar.jpg', '2026-01-07 20:40:04'),
(15, 'Mark Go', 'mark.g@icmis.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Staff', 'default_avatar.jpg', '2026-01-07 20:40:04'),
(16, 'Romy Diaz', 'romy.d@icmis.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Staff', 'default_avatar.jpg', '2026-01-07 20:40:04'),
(17, 'Dante Alip', 'dante.a@icmis.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Staff', 'default_avatar.jpg', '2026-01-07 20:40:04'),
(18, 'Steve Paz', 'steve.p@icmis.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Staff', 'default_avatar.jpg', '2026-01-07 20:40:04'),
(19, 'Ryan Yap', 'ryan.y@icmis.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Staff', 'default_avatar.jpg', '2026-01-07 20:40:04'),
(20, 'Mario Pineda', 'mario.p@icmis.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Staff', 'default_avatar.jpg', '2026-01-07 20:40:04'),
(21, 'Luigi Pineda', 'luigi.p@icmis.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Staff', 'default_avatar.jpg', '2026-01-07 20:40:04'),
(22, 'Ken Sy', 'ken.s@icmis.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Staff', 'default_avatar.jpg', '2026-01-07 20:40:04'),
(23, 'Manny Wood', 'manny.w@icmis.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Staff', 'default_avatar.jpg', '2026-01-07 20:40:04'),
(24, 'Jack Solis', 'jack.s@icmis.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Staff', 'default_avatar.jpg', '2026-01-07 20:40:04'),
(25, 'Peter Vega', 'peter.v@icmis.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Staff', 'default_avatar.jpg', '2026-01-07 20:40:04'),
(26, 'Tyler Tan', 'tyler.t@icmis.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Staff', 'default_avatar.jpg', '2026-01-07 20:40:04'),
(27, 'Jose Glas', 'jose.g@icmis.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Staff', 'default_avatar.jpg', '2026-01-07 20:40:04'),
(28, 'Boyet Labos', 'boyet.l@icmis.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Staff', 'default_avatar.jpg', '2026-01-07 20:40:04'),
(29, 'Juan Dela Cruz', 'juan.d@icmis.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Staff', 'default_avatar.jpg', '2026-01-07 20:40:04'),
(30, 'John Doe', 'john.doe@icmis.com', '$2y$12$pl4NIXnb43GJgd88tKaBweOT56kgORWvZ7qnU3bhAO0ZzUH0/rY/q', 'Admin', NULL, '2026-01-07 23:52:33');

-- --------------------------------------------------------

--
-- Table structure for table `procurement_inventory`
--

CREATE TABLE `procurement_inventory` (
  `item_id` int NOT NULL,
  `item_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `category` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'Uncategorized',
  `quantity` decimal(10,2) DEFAULT '0.00',
  `unit` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `project_id` int DEFAULT NULL,
  `phase_id` int DEFAULT NULL,
  `unit_cost` decimal(15,2) DEFAULT '0.00',
  `last_updated` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `procurement_inventory`
--

INSERT INTO `procurement_inventory` (`item_id`, `item_name`, `category`, `quantity`, `unit`, `project_id`, `phase_id`, `unit_cost`, `last_updated`) VALUES
(4, 'Tie Wire #16 (kg)', 'General', 0.00, 'pcs', 1, 1, 12.00, '2026-01-10 03:41:25');

-- --------------------------------------------------------

--
-- Table structure for table `procurement_purchase_orders`
--

CREATE TABLE `procurement_purchase_orders` (
  `po_id` int NOT NULL,
  `po_reference` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `project_id` int NOT NULL,
  `phase_id` int DEFAULT NULL,
  `supplier_id` int NOT NULL,
  `order_title` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `order_date` date DEFAULT (curdate()),
  `total_amount` decimal(15,2) DEFAULT '0.00',
  `status` enum('PENDING','APPROVED','REJECTED','COMPLETED') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'PENDING',
  `created_by_user_id` int DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `procurement_purchase_orders`
--

INSERT INTO `procurement_purchase_orders` (`po_id`, `po_reference`, `project_id`, `phase_id`, `supplier_id`, `order_title`, `order_date`, `total_amount`, `status`, `created_by_user_id`) VALUES
(6, 'PO-2026-0001', 1, 1, 1, 'Phase 1 Materials', '2026-01-10', 1344.00, 'COMPLETED', 30);

-- --------------------------------------------------------

--
-- Table structure for table `procurement_purchase_order_items`
--

CREATE TABLE `procurement_purchase_order_items` (
  `po_item_id` int NOT NULL,
  `po_id` int NOT NULL,
  `inventory_item_id` int DEFAULT NULL,
  `item_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `quantity` decimal(10,2) NOT NULL,
  `unit_cost` decimal(15,2) NOT NULL,
  `total_cost` decimal(15,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `procurement_purchase_order_items`
--

INSERT INTO `procurement_purchase_order_items` (`po_item_id`, `po_id`, `inventory_item_id`, `item_name`, `quantity`, `unit_cost`, `total_cost`) VALUES
(15, 6, NULL, 'Tie Wire #16 (kg)', 112.00, 12.00, 1344.00);

-- --------------------------------------------------------

--
-- Table structure for table `procurement_stock_in`
--

CREATE TABLE `procurement_stock_in` (
  `stock_in_id` int NOT NULL,
  `po_id` int NOT NULL,
  `item_id` int DEFAULT NULL,
  `quantity_received` int NOT NULL,
  `unit_cost` decimal(15,2) DEFAULT '0.00',
  `total_cost` decimal(15,2) DEFAULT '0.00',
  `date_received` date DEFAULT (curdate())
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `procurement_stock_in`
--

INSERT INTO `procurement_stock_in` (`stock_in_id`, `po_id`, `item_id`, `quantity_received`, `unit_cost`, `total_cost`, `date_received`) VALUES
(8, 6, 4, 112, 12.00, 1344.00, '2026-01-10');

-- --------------------------------------------------------

--
-- Table structure for table `procurement_stock_out`
--

CREATE TABLE `procurement_stock_out` (
  `stock_out_id` int NOT NULL,
  `item_id` int NOT NULL,
  `quantity` int NOT NULL,
  `issued_to_employee_id` int DEFAULT NULL,
  `project_id` int DEFAULT NULL,
  `date_issued` date DEFAULT (curdate())
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `procurement_stock_out`
--

INSERT INTO `procurement_stock_out` (`stock_out_id`, `item_id`, `quantity`, `issued_to_employee_id`, `project_id`, `date_issued`) VALUES
(2, 4, 112, 110, NULL, '2026-01-10');

-- --------------------------------------------------------

--
-- Table structure for table `procurement_suppliers`
--

CREATE TABLE `procurement_suppliers` (
  `supplier_id` int NOT NULL,
  `supplier_name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `contact_person` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `contact_number` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `address` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `status` enum('Active','Inactive') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'Active'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `procurement_suppliers`
--

INSERT INTO `procurement_suppliers` (`supplier_id`, `supplier_name`, `contact_person`, `contact_number`, `email`, `address`, `status`) VALUES
(1, 'CitiHardware Bacolod - Tangub', 'Ms. Jenelyn Dy', '0917-555-0101', 'sales.bacolod@citihardware.com', 'Araneta St, Tangub, Bacolod City, Negros Occidental', 'Active'),
(2, 'Wilcon Depot (Talisay/Bacolod)', 'Mr. Ryan Uy', '0998-999-0202', 'corporate@wilcon.com.ph', 'Zone 15, Talisay City (Near Bacolod Border), Negros Occidental', 'Active'),
(3, 'Bacolod Steel Centre Corp.', 'Engr. Lim', '(034) 433-1234', 'orders@bacolodsteel.com', 'Lopez Jaena St, Bacolod City', 'Active'),
(4, 'Solid Build Aggregates & Concrete', 'Mr. Roberto Tan', '0917-888-7777', 'dispatch@solidbuild.ph', 'Bacolod Reclamation Area, Bacolod City', 'Active'),
(5, 'Negros Forest Lumber', 'Mrs. Anita Chua', '(034) 434-5678', 'sales@negrosforest.com', 'Lacson Extension, Bacolod City', 'Active'),
(6, 'Negros Navigation & Manpower Services', 'HR Manager Rico', '(034) 432-1010', 'manpower@nenaco.ph', 'Bredco Port, Bacolod City', 'Active'),
(7, 'Bacolod Skilled Workers Coop', 'Pedro Penduko', '0920-111-2222', 'coop@bacolodworkers.org', 'Libertad Market Area, Bacolod City', 'Active'),
(8, 'Dynamic Builders Subcon Division', 'Arch. Miguel Lopez', '(034) 446-1234', 'subcon@dynamicbuilders.ph', 'Alijis Road, Bacolod City', 'Active'),
(9, 'R.U. Foundry and Machine Shop', 'Mr. Ramon Uy', '(034) 446-3829', 'sales@rufoundry.com', 'Hylton Subd, Mansilingan, Bacolod City', 'Active'),
(10, 'Civic Merchandising Bacolod', 'Engr. Castro', '0917-300-4000', 'bacolod.sales@civic.com.ph', 'Singcang-Airport, Bacolod City', 'Active'),
(11, 'Hastings Motor Corporation', 'Sales Dept', '(034) 433-9999', 'rentals@hastingsmotor.com', 'Lacson Street, Mandalagan, Bacolod City', 'Active');

-- --------------------------------------------------------

--
-- Table structure for table `workforce_assignments`
--

CREATE TABLE `workforce_assignments` (
  `assignment_id` int NOT NULL,
  `employee_id` int NOT NULL,
  `project_id` int NOT NULL,
  `phase_id` int DEFAULT NULL,
  `role` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `task_description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `status` enum('Active','Completed','Cancelled') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'Active'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `workforce_assignments`
--

INSERT INTO `workforce_assignments` (`assignment_id`, `employee_id`, `project_id`, `phase_id`, `role`, `task_description`, `start_date`, `end_date`, `status`) VALUES
(1, 101, 1, 1, 'Project Manager', 'Overall supervision of Phase 1 mobilization and structural works.', '2026-01-05', '2026-06-30', 'Active'),
(2, 102, 1, 1, 'Site Engineer', 'Supervise daily structural integrity and compliance with blueprints.', '2026-01-05', '2026-06-30', 'Active'),
(3, 103, 1, 1, 'General Foreman', 'Manage workforce schedules and daily site deliverables.', '2026-01-05', '2026-06-30', 'Active'),
(4, 104, 1, 1, 'Master Mason', 'Lead masonry works for perimeter fencing and foundation.', '2026-01-07', '2026-04-15', 'Active'),
(5, 105, 1, 1, 'Mason', 'Concrete mixing and hollow block laying for Phase 1.', '2026-01-07', '2026-04-15', 'Active'),
(6, 106, 1, 1, 'Construction Helper', 'Hauling materials and assisting masonry team.', '2026-01-07', '2026-04-15', 'Active'),
(7, 107, 1, 1, 'Timekeeper', 'Record daily attendance and monitor work hours.', '2026-01-05', '2026-12-31', 'Active'),
(8, 108, 1, 1, 'Safety Officer', 'Implement site safety protocols and conduct daily toolbox meetings.', '2026-01-05', '2026-12-31', 'Active'),
(9, 109, 1, 1, 'Welder', 'Fabrication of steel reinforcement bars and temporary gates.', '2026-01-10', '2026-03-30', 'Active'),
(10, 110, 1, 1, 'Lead Electrician', 'Installation of temporary power supply for construction equipment.', '2026-01-10', '2026-03-30', 'Active');

-- --------------------------------------------------------

--
-- Table structure for table `workforce_attendance`
--

CREATE TABLE `workforce_attendance` (
  `attendance_id` int NOT NULL,
  `employee_id` int NOT NULL,
  `project_id` int DEFAULT NULL,
  `attendance_date` date NOT NULL,
  `time_in` time DEFAULT NULL,
  `time_out` time DEFAULT NULL,
  `status` enum('Present','Absent','Late','On Leave') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `remarks` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `workforce_attendance`
--

INSERT INTO `workforce_attendance` (`attendance_id`, `employee_id`, `project_id`, `attendance_date`, `time_in`, `time_out`, `status`, `remarks`) VALUES
(1, 101, 1, '2026-01-02', '07:45:00', '17:00:00', 'Present', 'Site Inspection'),
(2, 101, 1, '2026-01-03', '08:00:00', '17:00:00', 'Present', 'Regular'),
(3, 101, 1, '2026-01-05', '07:30:00', '18:00:00', 'Present', 'Manpower Meeting (1hr OT)'),
(4, 101, 1, '2026-01-06', '08:00:00', '17:00:00', 'Present', 'Regular'),
(5, 101, 1, '2026-01-07', '08:00:00', '17:00:00', 'Present', 'Regular'),
(6, 101, 1, '2026-01-08', '08:15:00', '17:15:00', 'Late', 'Traffic'),
(7, 101, 1, '2026-01-09', '08:00:00', '17:00:00', 'Present', 'Regular'),
(8, 102, 1, '2026-01-02', '08:00:00', '17:00:00', 'Present', 'Regular'),
(9, 102, 1, '2026-01-03', '08:00:00', '17:00:00', 'Present', 'Regular'),
(10, 102, 1, '2026-01-05', '07:55:00', '17:00:00', 'Present', 'Regular'),
(11, 102, 1, '2026-01-06', '08:00:00', '17:00:00', 'Present', 'Regular'),
(12, 102, 1, '2026-01-07', '08:00:00', '17:00:00', 'Present', 'Regular'),
(13, 102, 1, '2026-01-08', '08:00:00', '17:00:00', 'Present', 'Regular'),
(14, 102, 1, '2026-01-09', '08:00:00', '17:00:00', 'Present', 'Regular'),
(15, 103, 1, '2026-01-02', '07:30:00', '17:00:00', 'Present', 'Regular'),
(16, 103, 1, '2026-01-03', '07:30:00', '17:00:00', 'Present', 'Regular'),
(17, 103, 1, '2026-01-05', '07:00:00', '18:00:00', 'Present', 'Heavy Pouring (2hr OT)'),
(18, 103, 1, '2026-01-06', '07:30:00', '17:30:00', 'Present', '30min OT'),
(19, 103, 1, '2026-01-07', '07:30:00', '17:30:00', 'Present', '30min OT'),
(20, 103, 1, '2026-01-08', '07:30:00', '17:30:00', 'Present', '30min OT'),
(21, 103, 1, '2026-01-09', '07:30:00', '17:00:00', 'Present', 'Regular'),
(22, 104, 1, '2026-01-02', '08:00:00', '17:00:00', 'Present', 'Regular'),
(23, 104, 1, '2026-01-03', '08:00:00', '17:00:00', 'Present', 'Regular'),
(24, 104, 1, '2026-01-05', '08:00:00', '17:00:00', 'Present', 'Regular'),
(25, 104, 1, '2026-01-06', '08:00:00', '17:00:00', 'Present', 'Regular'),
(26, 104, 1, '2026-01-07', '08:00:00', '17:00:00', 'Present', 'Regular'),
(27, 104, 1, '2026-01-08', '08:00:00', '17:00:00', 'Present', 'Regular'),
(28, 104, 1, '2026-01-09', '08:00:00', '17:00:00', 'Present', 'Regular'),
(29, 105, 1, '2026-01-02', '07:45:00', '17:00:00', 'Present', 'Regular'),
(30, 105, 1, '2026-01-03', '07:50:00', '17:00:00', 'Present', 'Regular'),
(31, 105, 1, '2026-01-05', '07:45:00', '17:00:00', 'Present', 'Regular'),
(32, 105, 1, '2026-01-06', '07:55:00', '17:00:00', 'Present', 'Regular'),
(33, 105, 1, '2026-01-07', '08:00:00', '17:00:00', 'Present', 'Regular'),
(34, 105, 1, '2026-01-08', '07:45:00', '17:00:00', 'Present', 'Regular'),
(35, 105, 1, '2026-01-09', '07:45:00', '17:00:00', 'Present', 'Regular'),
(36, 106, 1, '2026-01-02', '08:15:00', '17:00:00', 'Late', 'Overslept'),
(37, 106, 1, '2026-01-03', '08:00:00', '17:00:00', 'Present', 'Regular'),
(38, 106, 1, '2026-01-05', '08:30:00', '17:00:00', 'Late', 'Traffic'),
(39, 106, 1, '2026-01-06', '08:00:00', '17:00:00', 'Present', 'Regular'),
(40, 106, 1, '2026-01-07', '08:00:00', '17:00:00', 'Present', 'Regular'),
(41, 106, 1, '2026-01-08', '08:00:00', '17:00:00', 'Present', 'Regular'),
(42, 106, 1, '2026-01-09', '08:00:00', '17:00:00', 'Present', 'Regular'),
(43, 107, 1, '2026-01-02', '07:15:00', '17:15:00', 'Present', 'Early In'),
(44, 107, 1, '2026-01-03', '07:20:00', '17:00:00', 'Present', 'Early In'),
(45, 107, 1, '2026-01-05', '07:10:00', '17:30:00', 'Present', 'Payroll Prep'),
(46, 107, 1, '2026-01-06', '07:15:00', '17:15:00', 'Present', 'Regular'),
(47, 107, 1, '2026-01-07', '07:15:00', '17:15:00', 'Present', 'Regular'),
(48, 107, 1, '2026-01-08', '07:15:00', '17:15:00', 'Present', 'Regular'),
(49, 107, 1, '2026-01-09', '07:15:00', '17:15:00', 'Present', 'Regular'),
(50, 108, 1, '2026-01-02', '08:00:00', '17:00:00', 'Present', 'Safety Patrol'),
(51, 108, 1, '2026-01-03', '08:00:00', '17:00:00', 'Present', 'Toolbox Meeting'),
(52, 108, 1, '2026-01-05', '08:00:00', '17:00:00', 'Present', 'Regular'),
(53, 108, 1, '2026-01-06', '08:00:00', '17:00:00', 'Present', 'Regular'),
(54, 108, 1, '2026-01-07', '08:00:00', '17:00:00', 'Present', 'Regular'),
(55, 108, 1, '2026-01-08', '08:00:00', '17:00:00', 'Present', 'Regular'),
(56, 108, 1, '2026-01-09', '08:00:00', '17:00:00', 'Present', 'Regular'),
(57, 109, 1, '2026-01-02', '08:00:00', '17:00:00', 'Present', 'Gate Fabrication'),
(58, 109, 1, '2026-01-03', '08:00:00', '17:00:00', 'Present', 'Regular'),
(59, 109, 1, '2026-01-05', '07:30:00', '17:00:00', 'Present', 'Early Start'),
(60, 109, 1, '2026-01-06', '08:00:00', '17:00:00', 'Present', 'Regular'),
(61, 109, 1, '2026-01-07', '08:00:00', '18:00:00', 'Present', 'Structural Welding (1hr OT)'),
(62, 109, 1, '2026-01-08', '08:00:00', '18:00:00', 'Present', 'Structural Welding (1hr OT)'),
(63, 109, 1, '2026-01-09', '08:00:00', '17:00:00', 'Present', 'Regular'),
(64, 110, 1, '2026-01-02', '08:00:00', '17:00:00', 'Present', 'Site Survey'),
(65, 110, 1, '2026-01-03', '08:00:00', '17:00:00', 'Present', 'Regular'),
(66, 110, 1, '2026-01-05', '08:00:00', '17:00:00', 'Present', 'Regular'),
(67, 110, 1, '2026-01-06', '08:00:00', '17:00:00', 'Present', 'Regular'),
(68, 110, 1, '2026-01-07', '08:00:00', '17:00:00', 'Present', 'Regular'),
(69, 110, 1, '2026-01-08', '08:00:00', '17:00:00', 'Present', 'Regular'),
(70, 110, 1, '2026-01-09', '08:00:00', '17:00:00', 'Present', 'Regular'),
(71, 101, 1, '2026-01-10', '08:00:00', '17:00:00', 'Present', ''),
(72, 109, 1, '2026-01-10', '08:00:00', '17:00:00', 'Present', ''),
(73, 107, 1, '2026-01-10', '08:00:00', '17:00:00', 'Present', ''),
(74, 104, 1, '2026-01-10', '08:00:00', '17:00:00', 'Present', ''),
(75, 102, 1, '2026-01-10', '08:00:00', '17:00:00', 'Present', ''),
(76, 108, 1, '2026-01-10', '08:00:00', '17:00:00', 'Present', ''),
(77, 110, 1, '2026-01-10', '08:00:00', '17:00:00', 'Present', ''),
(78, 103, 1, '2026-01-10', '08:00:00', '17:00:00', 'Present', ''),
(79, 105, 1, '2026-01-10', '08:00:00', '17:00:00', 'Present', ''),
(80, 106, 1, '2026-01-10', '08:00:00', '17:00:00', 'Present', '');

-- --------------------------------------------------------

--
-- Table structure for table `workforce_employees`
--

CREATE TABLE `workforce_employees` (
  `employee_id` int NOT NULL,
  `employee_code` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_id` int DEFAULT NULL,
  `job_title_id` int DEFAULT NULL,
  `employment_type` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'Full-time',
  `payment_type` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'Monthly',
  `daily_rate` decimal(10,2) DEFAULT '0.00',
  `monthly_salary` decimal(15,2) DEFAULT '0.00',
  `bank_name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `bank_account` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `emergency_contact_name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `emergency_contact_phone` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `supervisor_id` int DEFAULT NULL,
  `notes` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `first_name` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `last_name` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `suffix` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `gender` enum('Male','Female','Other','Prefer not to say') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `birthday` date DEFAULT NULL,
  `email` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `phone` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `address` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `status` enum('Active','Inactive','Terminated') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'Active',
  `hire_date` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `workforce_employees`
--

INSERT INTO `workforce_employees` (`employee_id`, `employee_code`, `user_id`, `job_title_id`, `employment_type`, `payment_type`, `daily_rate`, `monthly_salary`, `bank_name`, `bank_account`, `emergency_contact_name`, `emergency_contact_phone`, `supervisor_id`, `notes`, `first_name`, `last_name`, `suffix`, `gender`, `birthday`, `email`, `phone`, `address`, `status`, `hire_date`) VALUES
(101, 'EMP-2026-001', 1, 1, 'Regular', 'Monthly', 2500.00, 65000.00, 'BPI', '1010-2020-30', 'Elena Bautista', '0917-000-0001', 101, 'Project Lead', 'Ramon', 'Bautista', 'None', 'Male', '1980-05-15', 'ramon.b@icmis.com', '0917-111-2222', 'Block 5 Lot 2, Davao City', 'Active', '2025-12-01'),
(102, 'EMP-2026-002', 2, 2, 'Regular', 'Monthly', 1800.00, 45000.00, 'BDO', '0011-2233-44', 'Mark Geran', '0917-000-0002', 101, 'License No. 54321', 'Sarah', 'Geran', 'None', 'Female', '1992-08-20', 'sarah.g@icmis.com', '0917-333-4444', 'Downtown Area, Davao City', 'Active', '2026-01-05'),
(103, 'EMP-2026-003', 3, 11, 'Project-Based', 'Daily', 1200.00, 31200.00, 'Metrobank', '3333-4444-55', 'Juana Peñaflorida', '0918-000-0003', 101, 'Senior Foreman', 'Efren', 'Peñaflorida', 'Jr.', 'Male', '1975-11-30', 'efren.p@icmis.com', '0918-555-6666', 'Toril, Davao City', 'Active', '2026-01-07'),
(104, 'EMP-2026-004', 4, 12, 'Contractual', 'Daily', 900.00, 23400.00, 'Landbank', '5555-6666-77', 'Lola Flora', '0919-000-0004', 101, 'Skill Lvl 3', 'Cardo', 'Dalisay', 'None', 'Male', '1985-01-10', 'cardo.d@icmis.com', '0919-777-8888', 'Matina, Davao City', 'Active', '2026-01-07'),
(105, 'EMP-2026-005', 5, 13, 'Contractual', 'Daily', 750.00, 19500.00, 'Cash Card', 'CC-9988-77', 'Maria Penduko', '0920-000-0005', 101, 'Masonry A', 'Pedro', 'Penduko', 'None', 'Male', '1988-03-25', 'pedro.p@icmis.com', '0920-999-0000', 'Buhangin, Davao City', 'Active', '2026-01-08'),
(106, 'EMP-2026-006', 6, 28, 'Contractual', 'Daily', 500.00, 13000.00, 'Cash Card', 'CC-1122-33', 'Nanay Tamad', '0921-000-0006', 101, 'General Labor', 'Juan', 'Tamad', 'None', 'Male', '1995-06-12', 'juan.t@icmis.com', '0921-123-4567', 'Agdao, Davao City', 'Active', '2026-01-08'),
(107, 'EMP-2026-007', 7, 9, 'Regular', 'Daily', 800.00, 20800.00, 'BPI', '8888-9999-00', 'Padre Damaso', '0922-000-0007', 101, 'Admin Support', 'Maria', 'Clara', 'None', 'Female', '1993-02-14', 'maria.c@icmis.com', '0922-234-5678', 'Ecoland, Davao City', 'Active', '2026-01-05'),
(108, 'EMP-2026-008', 8, 5, 'Regular', 'Monthly', 1500.00, 35000.00, 'BDO', '7777-8888-99', 'Elias Ibarra', '0923-000-0008', 101, 'COSH Certified', 'Crisostomo', 'Ibarra', 'None', 'Male', '1982-12-30', 'cris.i@icmis.com', '0923-345-6789', 'Lanang, Davao City', 'Active', '2026-01-05'),
(109, 'EMP-2026-009', 9, 15, 'Contractual', 'Daily', 850.00, 22100.00, 'Metrobank', '2222-1111-00', 'Gregoria De Jesus', '0924-000-0009', 101, 'SMAW NCII', 'Andres', 'Bonifacio', 'None', 'Male', '1983-11-30', 'andres.b@icmis.com', '0924-456-7890', 'Panacan, Davao City', 'Active', '2026-01-10'),
(110, 'EMP-2026-010', 10, 17, 'Project-Based', 'Daily', 1100.00, 28600.00, 'Landbank', '4444-5555-66', 'Ina Mabini', '0925-000-0010', 101, 'Lic. Master Electrician', 'Apolinario', 'Mabini', 'None', 'Male', '1984-07-23', 'pol.m@icmis.com', '0925-567-8901', 'Talomo, Davao City', 'Active', '2026-01-10');

-- --------------------------------------------------------

--
-- Table structure for table `workforce_employee_groups`
--

CREATE TABLE `workforce_employee_groups` (
  `group_id` int NOT NULL,
  `group_code` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `group_name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `group_leader_id` int DEFAULT NULL,
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `workforce_employee_groups`
--

INSERT INTO `workforce_employee_groups` (`group_id`, `group_code`, `group_name`, `group_leader_id`, `description`) VALUES
(1, 'GRP-2026-001', 'Civil Works Team Alpha', 103, 'Primary structural team responsible for foundation and column erection.'),
(2, 'GRP-2026-002', 'Masonry Unit 1', 104, 'Specialized team for hollow block laying and wall plastering.'),
(3, 'GRP-2026-003', 'Electrical Installation', 110, 'Responsible for all roughing-ins, wiring, and panel board termination.'),
(4, 'GRP-2026-004', 'Site Safety Committee', 108, 'Oversight group for Occupational Safety and Health (OSH) compliance.'),
(5, 'GRP-2026-005', 'Logistics and Support', 107, 'Handles material receiving, inventory monitoring, and timekeeping.'),
(6, 'GRP-2026-006', 'Steel Fabrication', 109, 'Responsible for cutting, bending, and installing rebar reinforcements.'),
(7, 'GRP-2026-007', 'Project Management', 101, 'Top-level supervision and stakeholder coordination team.'),
(8, 'GRP-2026-008', 'Survey and Layout', 102, 'Ensures technical alignment and elevation accuracy based on blueprints.'),
(9, 'GRP-2026-009', 'General Labor Force', 106, 'Support team for hauling, mixing, and site cleanliness maintenance.'),
(10, 'GRP-2026-010', 'Emergency Response', 108, 'Special designated team for handling site accidents and first aid.');

-- --------------------------------------------------------

--
-- Table structure for table `workforce_generated_reports`
--

CREATE TABLE `workforce_generated_reports` (
  `report_id` int NOT NULL,
  `project_id` int DEFAULT NULL,
  `report_type` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `report_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `generated_by` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `workforce_generated_reports`
--

INSERT INTO `workforce_generated_reports` (`report_id`, `project_id`, `report_type`, `report_name`, `generated_by`, `created_at`) VALUES
(16, 1, 'employee-directory', 'Employee Directory', 'System', '2026-01-10 18:22:33');

-- --------------------------------------------------------

--
-- Table structure for table `workforce_group_memberships`
--

CREATE TABLE `workforce_group_memberships` (
  `membership_id` int NOT NULL,
  `employee_id` int NOT NULL,
  `group_id` int NOT NULL,
  `role_in_group` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `joined_date` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `workforce_group_memberships`
--

INSERT INTO `workforce_group_memberships` (`membership_id`, `employee_id`, `group_id`, `role_in_group`, `joined_date`) VALUES
(1, 103, 1, 'Team Leader', '2026-01-07'),
(2, 104, 1, 'Senior Technical Member', '2026-01-07'),
(3, 104, 2, 'Team Leader', '2026-01-07'),
(4, 105, 2, 'Mason', '2026-01-08'),
(5, 106, 2, 'Assistant', '2026-01-08'),
(6, 110, 3, 'Head Electrician', '2026-01-10'),
(7, 108, 4, 'Safety Chairman', '2026-01-05'),
(8, 107, 5, 'Logistics Coordinator', '2026-01-05'),
(9, 109, 6, 'Fabrication Lead', '2026-01-10'),
(10, 101, 7, 'Project Director', '2026-01-05'),
(11, 102, 7, 'Technical Supervisor', '2026-01-05'),
(12, 102, 8, 'Chief Surveyor', '2026-01-05'),
(13, 106, 9, 'Head Laborer', '2026-01-08'),
(14, 103, 10, 'First Aid Responder', '2026-01-07'),
(15, 108, 10, 'Response Coordinator', '2026-01-05');

-- --------------------------------------------------------

--
-- Table structure for table `workforce_job_titles`
--

CREATE TABLE `workforce_job_titles` (
  `job_title_id` int NOT NULL,
  `title_name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `department` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `default_daily_rate` decimal(10,2) DEFAULT NULL,
  `default_monthly_salary` decimal(15,2) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `workforce_job_titles`
--

INSERT INTO `workforce_job_titles` (`job_title_id`, `title_name`, `department`, `description`, `default_daily_rate`, `default_monthly_salary`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'Project Manager', 'Operations', 'Responsible for overall project planning, execution, and delivery.', 2500.00, 65000.00, 1, '2026-01-07 20:37:34', '2026-01-07 20:37:34'),
(2, 'Site Engineer', 'Engineering', 'Supervises daily site operations and technical compliance.', 1800.00, 46800.00, 1, '2026-01-07 20:37:34', '2026-01-07 20:37:34'),
(3, 'Project Engineer', 'Engineering', 'Handles technical documentation and structural integrity checks.', 2200.00, 57200.00, 1, '2026-01-07 20:37:34', '2026-01-07 20:37:34'),
(4, 'Senior Architect', 'Design', 'Ensures design specifications are followed during construction.', 2500.00, 65000.00, 1, '2026-01-07 20:37:34', '2026-01-07 20:37:34'),
(5, 'Safety Officer', 'Safety', 'Enforces Occupational Safety and Health (OSH) standards on site.', 1500.00, 39000.00, 1, '2026-01-07 20:37:34', '2026-01-07 20:37:34'),
(6, 'Budget Officer', 'Finance', 'Manages project budget proposals and expense tracking.', 1800.00, 46800.00, 1, '2026-01-07 20:37:34', '2026-01-07 20:37:34'),
(7, 'Procurement Officer', 'Logistics', 'Handles supplier sourcing, purchasing, and delivery logistics.', 1800.00, 46800.00, 1, '2026-01-07 20:37:34', '2026-01-07 20:37:34'),
(8, 'Quantity Surveyor', 'Cost Control', 'Estimates material costs and manages project bills of quantities.', 1800.00, 46800.00, 1, '2026-01-07 20:37:34', '2026-01-07 20:37:34'),
(9, 'Timekeeper', 'Administrative', 'Records daily attendance and work hours of site personnel.', 800.00, 20800.00, 1, '2026-01-07 20:37:34', '2026-01-07 20:37:34'),
(10, 'Warehouseman', 'Inventory', 'Manages inventory stock-in, stock-out, and material storage.', 700.00, 18200.00, 1, '2026-01-07 20:37:34', '2026-01-07 20:37:34'),
(11, 'General Foreman', 'Construction', 'Direct supervisor of all skilled and unskilled labor on site.', 1200.00, 31200.00, 1, '2026-01-07 20:37:34', '2026-01-07 20:37:34'),
(12, 'Master Mason', 'Masonry', 'Specialist in complex concrete works and bricklaying.', 900.00, 23400.00, 1, '2026-01-07 20:37:34', '2026-01-07 20:37:34'),
(13, 'Mason', 'Masonry', 'Performs standard concrete mixing, pouring, and block laying.', 750.00, 19500.00, 1, '2026-01-07 20:37:34', '2026-01-07 20:37:34'),
(14, 'Steel Fixer', 'Reinforcement', 'Cuts, bends, and ties steel reinforcement bars (rebars).', 700.00, 18200.00, 1, '2026-01-07 20:37:34', '2026-01-07 20:37:34'),
(15, 'Welder', 'Metalwork', 'Joins metal parts for structural frameworks and supports.', 850.00, 22100.00, 1, '2026-01-07 20:37:34', '2026-01-07 20:37:34'),
(16, 'Heavy Equipment Operator', 'Machinery', 'Operates excavators, cranes, and backhoes for site works.', 1000.00, 26000.00, 1, '2026-01-07 20:37:34', '2026-01-07 20:37:34'),
(17, 'Master Electrician', 'Electrical', 'Reads electrical plans and supervises wiring installations.', 1100.00, 28600.00, 1, '2026-01-07 20:37:34', '2026-01-07 20:37:34'),
(18, 'Electrician', 'Electrical', 'Installs electrical wiring, fixtures, and control equipment.', 800.00, 20800.00, 1, '2026-01-07 20:37:34', '2026-01-07 20:37:34'),
(19, 'Master Plumber', 'Plumbing', 'Supervises installation of water supply and sanitary systems.', 1100.00, 28600.00, 1, '2026-01-07 20:37:34', '2026-01-07 20:37:34'),
(20, 'Pipe Fitter', 'Plumbing', 'Cuts and installs pipes for water, gas, or steam systems.', 750.00, 19500.00, 1, '2026-01-07 20:37:34', '2026-01-07 20:37:34'),
(21, 'HVAC Technician', 'Mechanical', 'Installs and maintains heating, ventilation, and air conditioning.', 900.00, 23400.00, 1, '2026-01-07 20:37:34', '2026-01-07 20:37:34'),
(22, 'Finishing Carpenter', 'Carpentry', 'Installs cabinetry, moldings, doors, and detailed woodworks.', 900.00, 23400.00, 1, '2026-01-07 20:37:34', '2026-01-07 20:37:34'),
(23, 'Rough Carpenter', 'Carpentry', 'Builds temporary structures like formworks and scaffolding.', 700.00, 18200.00, 1, '2026-01-07 20:37:34', '2026-01-07 20:37:34'),
(24, 'Painter', 'Finishing', 'Prepares surfaces and applies paint, varnish, or sealants.', 700.00, 18200.00, 1, '2026-01-07 20:37:34', '2026-01-07 20:37:34'),
(25, 'Tile Setter', 'Tiling', 'Lays tiles on floors and walls with precision alignment.', 850.00, 22100.00, 1, '2026-01-07 20:37:34', '2026-01-07 20:37:34'),
(26, 'Glazier', 'Installation', 'Cuts and installs glass for windows, doors, and mirrors.', 800.00, 20800.00, 1, '2026-01-07 20:37:34', '2026-01-07 20:37:34'),
(27, 'Skilled Laborer', 'General Labor', 'Assists tradesmen with tasks requiring some technical knowledge.', 600.00, 15600.00, 1, '2026-01-07 20:37:34', '2026-01-07 20:37:34'),
(28, 'Site Helper', 'General Labor', 'Performs manual labor such as digging, cleaning, and hauling.', 500.00, 13000.00, 1, '2026-01-07 20:37:34', '2026-01-07 20:37:34');

-- --------------------------------------------------------

--
-- Table structure for table `workforce_payroll`
--

CREATE TABLE `workforce_payroll` (
  `payroll_id` int NOT NULL,
  `employee_id` int NOT NULL,
  `period_id` int NOT NULL,
  `hours_worked` decimal(5,2) DEFAULT NULL,
  `gross_pay` decimal(10,2) DEFAULT NULL,
  `net_pay` decimal(10,2) DEFAULT NULL,
  `status` enum('Calculated','Approved','Processed') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'Calculated'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `workforce_payroll`
--

INSERT INTO `workforce_payroll` (`payroll_id`, `employee_id`, `period_id`, `hours_worked`, `gross_pay`, `net_pay`, `status`) VALUES
(11, 101, 9, 65.75, 33183.59, 30008.59, 'Processed'),
(12, 102, 9, 64.08, 22521.63, 19846.63, 'Processed'),
(13, 103, 9, 70.50, 10818.75, 9104.12, 'Processed'),
(14, 104, 9, 64.00, 7200.00, 5992.00, 'Processed'),
(15, 105, 9, 65.25, 6146.48, 5085.98, 'Processed'),
(16, 106, 9, 63.25, 3953.13, 3241.56, 'Processed'),
(17, 107, 9, 71.00, 7275.00, 6056.50, 'Processed'),
(18, 108, 9, 64.00, 17500.00, 15075.00, 'Processed'),
(19, 109, 9, 66.50, 7132.03, 5933.55, 'Processed'),
(20, 110, 9, 64.00, 8800.00, 7368.00, 'Processed'),
(21, 101, 10, 65.75, 33183.59, 30008.59, 'Processed'),
(22, 102, 10, 64.08, 22521.63, 19846.63, 'Processed'),
(23, 103, 10, 70.50, 10818.75, 9104.13, 'Processed'),
(24, 104, 10, 64.00, 7200.00, 5992.00, 'Processed'),
(25, 105, 10, 65.25, 6146.48, 5085.98, 'Processed'),
(26, 106, 10, 63.25, 3953.13, 3241.56, 'Processed'),
(27, 107, 10, 71.00, 7275.00, 6056.50, 'Processed'),
(28, 108, 10, 64.00, 17500.00, 15075.00, 'Processed'),
(29, 109, 10, 66.50, 7132.03, 5933.55, 'Processed'),
(30, 110, 10, 64.00, 8800.00, 7368.00, 'Processed');

-- --------------------------------------------------------

--
-- Table structure for table `workforce_payroll_periods`
--

CREATE TABLE `workforce_payroll_periods` (
  `period_id` int NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `pay_date` date DEFAULT NULL,
  `status` enum('Open','Closed','Processing') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'Open',
  `processed_by` int DEFAULT NULL COMMENT 'Employee ID of the person who processed this',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `workforce_payroll_periods`
--

INSERT INTO `workforce_payroll_periods` (`period_id`, `start_date`, `end_date`, `pay_date`, `status`, `processed_by`, `created_at`) VALUES
(10, '2026-01-01', '2026-01-15', '2026-01-10', 'Closed', NULL, '2026-01-10 04:26:51');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `budget_expenses`
--
ALTER TABLE `budget_expenses`
  ADD PRIMARY KEY (`expense_id`),
  ADD KEY `fk_exp_project` (`project_id`),
  ADD KEY `fk_exp_phase` (`phase_id`),
  ADD KEY `fk_exp_supplier` (`supplier_id`),
  ADD KEY `fk_exp_creator` (`created_by`);

--
-- Indexes for table `budget_generated_reports`
--
ALTER TABLE `budget_generated_reports`
  ADD PRIMARY KEY (`report_id`),
  ADD KEY `fk_rep_project` (`project_id`);

--
-- Indexes for table `budget_line_items`
--
ALTER TABLE `budget_line_items`
  ADD PRIMARY KEY (`line_item_id`),
  ADD KEY `fk_line_prop` (`proposal_id`);

--
-- Indexes for table `budget_proposals`
--
ALTER TABLE `budget_proposals`
  ADD PRIMARY KEY (`proposal_id`),
  ADD KEY `fk_prop_project` (`project_id`),
  ADD KEY `fk_prop_phase` (`phase_id`),
  ADD KEY `fk_prop_creator` (`created_by`);

--
-- Indexes for table `icmis_audit_logs`
--
ALTER TABLE `icmis_audit_logs`
  ADD PRIMARY KEY (`log_id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_action` (`action`),
  ADD KEY `idx_module` (`module`),
  ADD KEY `idx_created_at` (`created_at`),
  ADD KEY `idx_record_id` (`record_id`);

--
-- Indexes for table `icmis_generated_reports`
--
ALTER TABLE `icmis_generated_reports`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_category` (`category`),
  ADD KEY `idx_project_id` (`project_id`),
  ADD KEY `idx_created_at` (`created_at`);

--
-- Indexes for table `icmis_projects`
--
ALTER TABLE `icmis_projects`
  ADD PRIMARY KEY (`project_id`),
  ADD UNIQUE KEY `project_code` (`project_code`),
  ADD KEY `fk_project_manager` (`project_manager_id`);

--
-- Indexes for table `icmis_project_phases`
--
ALTER TABLE `icmis_project_phases`
  ADD PRIMARY KEY (`phase_id`),
  ADD KEY `fk_phase_project` (`project_id`);

--
-- Indexes for table `icmis_tasks`
--
ALTER TABLE `icmis_tasks`
  ADD PRIMARY KEY (`task_id`),
  ADD KEY `fk_task_project` (`project_id`),
  ADD KEY `fk_task_phase` (`phase_id`),
  ADD KEY `fk_task_assignee` (`assigned_to_employee_id`);

--
-- Indexes for table `icmis_users`
--
ALTER TABLE `icmis_users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `procurement_inventory`
--
ALTER TABLE `procurement_inventory`
  ADD PRIMARY KEY (`item_id`),
  ADD KEY `fk_inv_project` (`project_id`),
  ADD KEY `fk_inv_phase` (`phase_id`);

--
-- Indexes for table `procurement_purchase_orders`
--
ALTER TABLE `procurement_purchase_orders`
  ADD PRIMARY KEY (`po_id`),
  ADD KEY `fk_po_project` (`project_id`),
  ADD KEY `fk_po_phase` (`phase_id`),
  ADD KEY `fk_po_supplier` (`supplier_id`),
  ADD KEY `fk_po_creator` (`created_by_user_id`);

--
-- Indexes for table `procurement_purchase_order_items`
--
ALTER TABLE `procurement_purchase_order_items`
  ADD PRIMARY KEY (`po_item_id`),
  ADD KEY `fk_po_item_header` (`po_id`),
  ADD KEY `fk_po_item_inv` (`inventory_item_id`);

--
-- Indexes for table `procurement_stock_in`
--
ALTER TABLE `procurement_stock_in`
  ADD PRIMARY KEY (`stock_in_id`),
  ADD KEY `fk_stockin_po` (`po_id`),
  ADD KEY `fk_stockin_item` (`item_id`);

--
-- Indexes for table `procurement_stock_out`
--
ALTER TABLE `procurement_stock_out`
  ADD PRIMARY KEY (`stock_out_id`),
  ADD KEY `fk_stockout_item` (`item_id`),
  ADD KEY `fk_stockout_emp` (`issued_to_employee_id`),
  ADD KEY `fk_stockout_proj` (`project_id`);

--
-- Indexes for table `procurement_suppliers`
--
ALTER TABLE `procurement_suppliers`
  ADD PRIMARY KEY (`supplier_id`);

--
-- Indexes for table `workforce_assignments`
--
ALTER TABLE `workforce_assignments`
  ADD PRIMARY KEY (`assignment_id`),
  ADD KEY `fk_assign_emp` (`employee_id`),
  ADD KEY `fk_assign_proj` (`project_id`),
  ADD KEY `fk_assign_phase` (`phase_id`);

--
-- Indexes for table `workforce_attendance`
--
ALTER TABLE `workforce_attendance`
  ADD PRIMARY KEY (`attendance_id`),
  ADD KEY `fk_att_emp` (`employee_id`),
  ADD KEY `fk_att_proj` (`project_id`);

--
-- Indexes for table `workforce_employees`
--
ALTER TABLE `workforce_employees`
  ADD PRIMARY KEY (`employee_id`),
  ADD UNIQUE KEY `employee_code` (`employee_code`),
  ADD UNIQUE KEY `user_id` (`user_id`),
  ADD KEY `fk_emp_job` (`job_title_id`);

--
-- Indexes for table `workforce_employee_groups`
--
ALTER TABLE `workforce_employee_groups`
  ADD PRIMARY KEY (`group_id`),
  ADD KEY `fk_group_leader` (`group_leader_id`);

--
-- Indexes for table `workforce_generated_reports`
--
ALTER TABLE `workforce_generated_reports`
  ADD PRIMARY KEY (`report_id`),
  ADD KEY `idx_workforce_rep_project` (`project_id`);

--
-- Indexes for table `workforce_group_memberships`
--
ALTER TABLE `workforce_group_memberships`
  ADD PRIMARY KEY (`membership_id`),
  ADD KEY `fk_mem_emp` (`employee_id`),
  ADD KEY `fk_mem_group` (`group_id`);

--
-- Indexes for table `workforce_job_titles`
--
ALTER TABLE `workforce_job_titles`
  ADD PRIMARY KEY (`job_title_id`);

--
-- Indexes for table `workforce_payroll`
--
ALTER TABLE `workforce_payroll`
  ADD PRIMARY KEY (`payroll_id`),
  ADD KEY `fk_payroll_emp` (`employee_id`),
  ADD KEY `fk_payroll_period` (`period_id`);

--
-- Indexes for table `workforce_payroll_periods`
--
ALTER TABLE `workforce_payroll_periods`
  ADD PRIMARY KEY (`period_id`),
  ADD KEY `fk_period_processor` (`processed_by`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `budget_expenses`
--
ALTER TABLE `budget_expenses`
  MODIFY `expense_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `budget_generated_reports`
--
ALTER TABLE `budget_generated_reports`
  MODIFY `report_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=44;

--
-- AUTO_INCREMENT for table `budget_line_items`
--
ALTER TABLE `budget_line_items`
  MODIFY `line_item_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `budget_proposals`
--
ALTER TABLE `budget_proposals`
  MODIFY `proposal_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `icmis_audit_logs`
--
ALTER TABLE `icmis_audit_logs`
  MODIFY `log_id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `icmis_generated_reports`
--
ALTER TABLE `icmis_generated_reports`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `icmis_projects`
--
ALTER TABLE `icmis_projects`
  MODIFY `project_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `icmis_project_phases`
--
ALTER TABLE `icmis_project_phases`
  MODIFY `phase_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `icmis_tasks`
--
ALTER TABLE `icmis_tasks`
  MODIFY `task_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `icmis_users`
--
ALTER TABLE `icmis_users`
  MODIFY `user_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=31;

--
-- AUTO_INCREMENT for table `procurement_inventory`
--
ALTER TABLE `procurement_inventory`
  MODIFY `item_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `procurement_purchase_orders`
--
ALTER TABLE `procurement_purchase_orders`
  MODIFY `po_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `procurement_purchase_order_items`
--
ALTER TABLE `procurement_purchase_order_items`
  MODIFY `po_item_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `procurement_stock_in`
--
ALTER TABLE `procurement_stock_in`
  MODIFY `stock_in_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `procurement_stock_out`
--
ALTER TABLE `procurement_stock_out`
  MODIFY `stock_out_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `procurement_suppliers`
--
ALTER TABLE `procurement_suppliers`
  MODIFY `supplier_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `workforce_assignments`
--
ALTER TABLE `workforce_assignments`
  MODIFY `assignment_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `workforce_attendance`
--
ALTER TABLE `workforce_attendance`
  MODIFY `attendance_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=81;

--
-- AUTO_INCREMENT for table `workforce_employees`
--
ALTER TABLE `workforce_employees`
  MODIFY `employee_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=111;

--
-- AUTO_INCREMENT for table `workforce_employee_groups`
--
ALTER TABLE `workforce_employee_groups`
  MODIFY `group_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `workforce_generated_reports`
--
ALTER TABLE `workforce_generated_reports`
  MODIFY `report_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `workforce_group_memberships`
--
ALTER TABLE `workforce_group_memberships`
  MODIFY `membership_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `workforce_job_titles`
--
ALTER TABLE `workforce_job_titles`
  MODIFY `job_title_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=29;

--
-- AUTO_INCREMENT for table `workforce_payroll`
--
ALTER TABLE `workforce_payroll`
  MODIFY `payroll_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=31;

--
-- AUTO_INCREMENT for table `workforce_payroll_periods`
--
ALTER TABLE `workforce_payroll_periods`
  MODIFY `period_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `budget_expenses`
--
ALTER TABLE `budget_expenses`
  ADD CONSTRAINT `fk_exp_creator` FOREIGN KEY (`created_by`) REFERENCES `icmis_users` (`user_id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_exp_phase` FOREIGN KEY (`phase_id`) REFERENCES `icmis_project_phases` (`phase_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_exp_project` FOREIGN KEY (`project_id`) REFERENCES `icmis_projects` (`project_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_exp_supplier` FOREIGN KEY (`supplier_id`) REFERENCES `procurement_suppliers` (`supplier_id`) ON DELETE SET NULL;

--
-- Constraints for table `budget_generated_reports`
--
ALTER TABLE `budget_generated_reports`
  ADD CONSTRAINT `fk_rep_project` FOREIGN KEY (`project_id`) REFERENCES `icmis_projects` (`project_id`) ON DELETE CASCADE;

--
-- Constraints for table `budget_line_items`
--
ALTER TABLE `budget_line_items`
  ADD CONSTRAINT `fk_line_prop` FOREIGN KEY (`proposal_id`) REFERENCES `budget_proposals` (`proposal_id`) ON DELETE CASCADE;

--
-- Constraints for table `budget_proposals`
--
ALTER TABLE `budget_proposals`
  ADD CONSTRAINT `fk_prop_creator` FOREIGN KEY (`created_by`) REFERENCES `icmis_users` (`user_id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_prop_phase` FOREIGN KEY (`phase_id`) REFERENCES `icmis_project_phases` (`phase_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_prop_project` FOREIGN KEY (`project_id`) REFERENCES `icmis_projects` (`project_id`) ON DELETE CASCADE;

--
-- Constraints for table `icmis_audit_logs`
--
ALTER TABLE `icmis_audit_logs`
  ADD CONSTRAINT `fk_audit_log_user` FOREIGN KEY (`user_id`) REFERENCES `icmis_users` (`user_id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `icmis_projects`
--
ALTER TABLE `icmis_projects`
  ADD CONSTRAINT `fk_project_manager` FOREIGN KEY (`project_manager_id`) REFERENCES `workforce_employees` (`employee_id`) ON DELETE SET NULL;

--
-- Constraints for table `icmis_project_phases`
--
ALTER TABLE `icmis_project_phases`
  ADD CONSTRAINT `fk_phase_project` FOREIGN KEY (`project_id`) REFERENCES `icmis_projects` (`project_id`) ON DELETE CASCADE;

--
-- Constraints for table `icmis_tasks`
--
ALTER TABLE `icmis_tasks`
  ADD CONSTRAINT `fk_task_assignee` FOREIGN KEY (`assigned_to_employee_id`) REFERENCES `workforce_employees` (`employee_id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_task_phase` FOREIGN KEY (`phase_id`) REFERENCES `icmis_project_phases` (`phase_id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_task_project` FOREIGN KEY (`project_id`) REFERENCES `icmis_projects` (`project_id`) ON DELETE CASCADE;

--
-- Constraints for table `procurement_inventory`
--
ALTER TABLE `procurement_inventory`
  ADD CONSTRAINT `fk_inv_phase` FOREIGN KEY (`phase_id`) REFERENCES `icmis_project_phases` (`phase_id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_inv_project` FOREIGN KEY (`project_id`) REFERENCES `icmis_projects` (`project_id`) ON DELETE CASCADE;

--
-- Constraints for table `procurement_purchase_orders`
--
ALTER TABLE `procurement_purchase_orders`
  ADD CONSTRAINT `fk_po_creator` FOREIGN KEY (`created_by_user_id`) REFERENCES `icmis_users` (`user_id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_po_phase` FOREIGN KEY (`phase_id`) REFERENCES `icmis_project_phases` (`phase_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_po_project` FOREIGN KEY (`project_id`) REFERENCES `icmis_projects` (`project_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_po_supplier` FOREIGN KEY (`supplier_id`) REFERENCES `procurement_suppliers` (`supplier_id`) ON DELETE SET NULL;

--
-- Constraints for table `procurement_purchase_order_items`
--
ALTER TABLE `procurement_purchase_order_items`
  ADD CONSTRAINT `fk_po_item_header` FOREIGN KEY (`po_id`) REFERENCES `procurement_purchase_orders` (`po_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_po_item_inv` FOREIGN KEY (`inventory_item_id`) REFERENCES `procurement_inventory` (`item_id`) ON DELETE SET NULL;

--
-- Constraints for table `procurement_stock_in`
--
ALTER TABLE `procurement_stock_in`
  ADD CONSTRAINT `fk_stockin_item` FOREIGN KEY (`item_id`) REFERENCES `procurement_inventory` (`item_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_stockin_po` FOREIGN KEY (`po_id`) REFERENCES `procurement_purchase_orders` (`po_id`) ON DELETE CASCADE;

--
-- Constraints for table `procurement_stock_out`
--
ALTER TABLE `procurement_stock_out`
  ADD CONSTRAINT `fk_stockout_emp` FOREIGN KEY (`issued_to_employee_id`) REFERENCES `workforce_employees` (`employee_id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_stockout_item` FOREIGN KEY (`item_id`) REFERENCES `procurement_inventory` (`item_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_stockout_proj` FOREIGN KEY (`project_id`) REFERENCES `icmis_projects` (`project_id`) ON DELETE CASCADE;

--
-- Constraints for table `workforce_assignments`
--
ALTER TABLE `workforce_assignments`
  ADD CONSTRAINT `fk_assign_emp` FOREIGN KEY (`employee_id`) REFERENCES `workforce_employees` (`employee_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_assign_phase` FOREIGN KEY (`phase_id`) REFERENCES `icmis_project_phases` (`phase_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_assign_proj` FOREIGN KEY (`project_id`) REFERENCES `icmis_projects` (`project_id`) ON DELETE CASCADE;

--
-- Constraints for table `workforce_attendance`
--
ALTER TABLE `workforce_attendance`
  ADD CONSTRAINT `fk_att_emp` FOREIGN KEY (`employee_id`) REFERENCES `workforce_employees` (`employee_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_att_proj` FOREIGN KEY (`project_id`) REFERENCES `icmis_projects` (`project_id`) ON DELETE CASCADE;

--
-- Constraints for table `workforce_employees`
--
ALTER TABLE `workforce_employees`
  ADD CONSTRAINT `fk_emp_job` FOREIGN KEY (`job_title_id`) REFERENCES `workforce_job_titles` (`job_title_id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_emp_user` FOREIGN KEY (`user_id`) REFERENCES `icmis_users` (`user_id`) ON DELETE SET NULL;

--
-- Constraints for table `workforce_employee_groups`
--
ALTER TABLE `workforce_employee_groups`
  ADD CONSTRAINT `fk_group_leader` FOREIGN KEY (`group_leader_id`) REFERENCES `workforce_employees` (`employee_id`);

--
-- Constraints for table `workforce_generated_reports`
--
ALTER TABLE `workforce_generated_reports`
  ADD CONSTRAINT `fk_workforce_rep_project` FOREIGN KEY (`project_id`) REFERENCES `icmis_projects` (`project_id`) ON DELETE SET NULL;

--
-- Constraints for table `workforce_group_memberships`
--
ALTER TABLE `workforce_group_memberships`
  ADD CONSTRAINT `fk_mem_emp` FOREIGN KEY (`employee_id`) REFERENCES `workforce_employees` (`employee_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_mem_group` FOREIGN KEY (`group_id`) REFERENCES `workforce_employee_groups` (`group_id`) ON DELETE CASCADE;

--
-- Constraints for table `workforce_payroll_periods`
--
ALTER TABLE `workforce_payroll_periods`
  ADD CONSTRAINT `fk_period_processor` FOREIGN KEY (`processed_by`) REFERENCES `workforce_employees` (`employee_id`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
