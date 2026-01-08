-- phpMyAdmin SQL Dump
-- version 6.0.0-dev+20250914.f72491a1c0
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Jan 08, 2026 at 08:29 AM
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
  `category` enum('MATERIALS','LABOR','EQUIPMENT') COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `description` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `amount` decimal(15,2) NOT NULL,
  `expense_date` date NOT NULL,
  `status` enum('PENDING','APPROVED','REJECTED') COLLATE utf8mb4_unicode_ci DEFAULT 'PENDING',
  `created_by` int DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `budget_expenses`
--

INSERT INTO `budget_expenses` (`expense_id`, `project_id`, `phase_id`, `supplier_id`, `category`, `description`, `amount`, `expense_date`, `status`, `created_by`) VALUES
(1, 1, 1, 7, 'MATERIALS', 'PO-2026-0001 - Phase 1 Materials', 1344.00, '2026-01-08', 'APPROVED', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `budget_generated_reports`
--

CREATE TABLE `budget_generated_reports` (
  `report_id` int NOT NULL,
  `project_id` int DEFAULT NULL,
  `report_type` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `report_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `generated_by` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `budget_generated_reports`
--

INSERT INTO `budget_generated_reports` (`report_id`, `project_id`, `report_type`, `report_name`, `generated_by`, `created_at`) VALUES
(1, 1, 'budget-summary', 'Budget Summary', 'John Doe', '2026-01-07 00:14:20'),
(2, 1, 'budget-summary', 'Budget Summary', 'John Doe', '2026-01-07 00:14:26'),
(3, 1, 'budget-summary', 'Budget Summary', 'John Doe', '2026-01-07 00:15:48'),
(4, 1, 'budget-summary', 'Budget Summary', 'John Doe', '2026-01-07 00:15:50'),
(5, 1, 'budget-summary', 'Budget Summary', 'John Doe', '2026-01-07 00:15:56'),
(6, 1, 'budget-summary', 'Budget Summary', 'John Doe', '2026-01-07 00:17:28'),
(7, 1, 'budget-summary', 'Budget Summary', 'John Doe', '2026-01-07 00:17:28'),
(8, 1, 'budget-summary', 'Budget Summary', 'John Doe', '2026-01-07 00:21:46'),
(9, 1, 'budget-summary', 'Budget Summary', 'John Doe', '2026-01-07 00:31:24'),
(10, 1, 'budget-summary', 'Budget Summary', 'John Doe', '2026-01-07 00:38:01'),
(11, 1, 'budget-summary', 'Budget Summary', 'John Doe', '2026-01-07 00:41:17'),
(12, 1, 'budget-summary', 'Budget Summary', 'John Doe', '2026-01-07 00:41:31'),
(13, 1, 'budget-summary', 'Budget Summary', 'John Doe', '2026-01-07 00:45:27'),
(14, 1, 'budget-summary', 'Budget Summary', 'John Doe', '2026-01-07 00:53:23'),
(15, 1, 'budget-summary', 'Budget Summary', 'John Doe', '2026-01-07 01:04:19'),
(16, 1, 'budget-summary', 'Budget Summary', 'John Doe', '2026-01-07 01:18:00'),
(17, 1, 'phase-analysis', 'Phase Analysis (Phase 1)', 'John Doe', '2026-01-07 01:27:17'),
(18, 1, 'budget-summary', 'Budget Summary', 'John Doe', '2026-01-07 01:27:38'),
(19, 1, 'labor-analysis', 'Labor Cost Analysis', 'John Doe', '2026-01-07 01:27:42'),
(20, 1, 'expense-log', 'Expense Log', 'John Doe', '2026-01-07 01:28:01'),
(21, 1, 'budget-summary', 'Budget Summary', 'John Doe', '2026-01-07 04:33:06'),
(22, 1, 'expense-log', 'Expense Log', 'John Doe', '2026-01-07 04:34:48');

-- --------------------------------------------------------

--
-- Table structure for table `budget_line_items`
--

CREATE TABLE `budget_line_items` (
  `line_item_id` int NOT NULL,
  `proposal_id` int NOT NULL,
  `category` enum('MATERIAL','LABOR','EQUIPMENT') COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `item_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
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
  `code` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `total_amount` decimal(15,2) NOT NULL,
  `status` enum('DRAFT','PENDING','APPROVED','REJECTED') COLLATE utf8mb4_unicode_ci DEFAULT 'DRAFT',
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
-- Table structure for table `icmis_projects`
--

CREATE TABLE `icmis_projects` (
  `project_id` int NOT NULL,
  `project_code` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `project_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `location` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` enum('Planning','In Progress','Active','On Hold','Completed','Cancelled') COLLATE utf8mb4_unicode_ci DEFAULT 'Planning',
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
  `phase_name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `duration` int DEFAULT '0' COMMENT 'Duration in days',
  `status` enum('Not Started','In Progress','Completed') COLLATE utf8mb4_unicode_ci DEFAULT 'Not Started'
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
  `task_name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `assigned_to_employee_id` int DEFAULT NULL,
  `start_date` date DEFAULT NULL,
  `due_date` date DEFAULT NULL,
  `status` enum('Not Started','In Progress','Completed','On Hold') COLLATE utf8mb4_unicode_ci DEFAULT 'Not Started',
  `priority` enum('Low','Medium','High','Urgent') COLLATE utf8mb4_unicode_ci DEFAULT 'Medium'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `icmis_tasks`
--

INSERT INTO `icmis_tasks` (`task_id`, `project_id`, `phase_id`, `task_name`, `description`, `assigned_to_employee_id`, `start_date`, `due_date`, `status`, `priority`) VALUES
(1, 1, 1, 'Make a Budget Proposal', 'Make a Budget Proposal', NULL, '2026-01-07', '2026-01-14', 'Not Started', 'High');

-- --------------------------------------------------------

--
-- Table structure for table `icmis_users`
--

CREATE TABLE `icmis_users` (
  `user_id` int NOT NULL,
  `full_name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `role` enum('Admin','Manager','Staff','Budget_Officer','Procurement_Officer') COLLATE utf8mb4_unicode_ci DEFAULT 'Staff',
  `avatar` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
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
  `item_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `category` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT 'Uncategorized',
  `quantity` decimal(10,2) DEFAULT '0.00',
  `unit` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `project_id` int DEFAULT NULL,
  `phase_id` int DEFAULT NULL,
  `unit_cost` decimal(15,2) DEFAULT '0.00',
  `last_updated` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `procurement_inventory`
--

INSERT INTO `procurement_inventory` (`item_id`, `item_name`, `category`, `quantity`, `unit`, `project_id`, `phase_id`, `unit_cost`, `last_updated`) VALUES
(3, 'Tie Wire #16 (kg)', 'General', 112.00, 'pcs', 1, 1, 12.00, '2026-01-08 05:38:26');

-- --------------------------------------------------------

--
-- Table structure for table `procurement_purchase_orders`
--

CREATE TABLE `procurement_purchase_orders` (
  `po_id` int NOT NULL,
  `po_reference` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `project_id` int NOT NULL,
  `phase_id` int DEFAULT NULL,
  `supplier_id` int NOT NULL,
  `order_title` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `order_date` date DEFAULT (curdate()),
  `total_amount` decimal(15,2) DEFAULT '0.00',
  `status` enum('PENDING','APPROVED','REJECTED','COMPLETED') COLLATE utf8mb4_unicode_ci DEFAULT 'PENDING',
  `created_by_user_id` int DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `procurement_purchase_orders`
--

INSERT INTO `procurement_purchase_orders` (`po_id`, `po_reference`, `project_id`, `phase_id`, `supplier_id`, `order_title`, `order_date`, `total_amount`, `status`, `created_by_user_id`) VALUES
(1, 'PO-2026-0001', 1, 1, 7, 'Phase 1 Materials', '2026-01-08', 1344.00, 'COMPLETED', 30);

-- --------------------------------------------------------

--
-- Table structure for table `procurement_purchase_order_items`
--

CREATE TABLE `procurement_purchase_order_items` (
  `po_item_id` int NOT NULL,
  `po_id` int NOT NULL,
  `inventory_item_id` int DEFAULT NULL,
  `item_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `quantity` decimal(10,2) NOT NULL,
  `unit_cost` decimal(15,2) NOT NULL,
  `total_cost` decimal(15,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `procurement_purchase_order_items`
--

INSERT INTO `procurement_purchase_order_items` (`po_item_id`, `po_id`, `inventory_item_id`, `item_name`, `quantity`, `unit_cost`, `total_cost`) VALUES
(2, 1, NULL, 'Tie Wire #16 (kg)', 112.00, 12.00, 1344.00);

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
(1, 1, 3, 112, 12.00, 1344.00, '2026-01-08');

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

-- --------------------------------------------------------

--
-- Table structure for table `procurement_suppliers`
--

CREATE TABLE `procurement_suppliers` (
  `supplier_id` int NOT NULL,
  `supplier_name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `contact_person` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `contact_number` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `address` text COLLATE utf8mb4_unicode_ci,
  `status` enum('Active','Inactive') COLLATE utf8mb4_unicode_ci DEFAULT 'Active'
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
  `role` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `task_description` text COLLATE utf8mb4_unicode_ci,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `status` enum('Active','Completed','Cancelled') COLLATE utf8mb4_unicode_ci DEFAULT 'Active'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `workforce_assignments`
--

INSERT INTO `workforce_assignments` (`assignment_id`, `employee_id`, `project_id`, `phase_id`, `role`, `task_description`, `start_date`, `end_date`, `status`) VALUES
(26, 26, 1, NULL, 'Glazier', NULL, '2026-01-08', NULL, 'Active'),
(27, 27, 1, NULL, 'Skilled Laborer', NULL, '2026-01-08', NULL, 'Active'),
(28, 28, 1, NULL, 'Site Helper', NULL, '2026-01-08', NULL, 'Active');

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
  `status` enum('Present','Absent','Late','On Leave') COLLATE utf8mb4_unicode_ci NOT NULL,
  `remarks` text COLLATE utf8mb4_unicode_ci
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `workforce_employees`
--

CREATE TABLE `workforce_employees` (
  `employee_id` int NOT NULL,
  `employee_code` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_id` int DEFAULT NULL,
  `job_title_id` int DEFAULT NULL,
  `employment_type` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT 'Full-time',
  `payment_type` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT 'Monthly',
  `daily_rate` decimal(10,2) DEFAULT '0.00',
  `monthly_salary` decimal(15,2) DEFAULT '0.00',
  `bank_name` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `bank_account` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `emergency_contact_name` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `emergency_contact_phone` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `supervisor_id` int DEFAULT NULL,
  `notes` text COLLATE utf8mb4_unicode_ci,
  `first_name` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `last_name` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `suffix` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `gender` enum('Male','Female','Other','Prefer not to say') COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `birthday` date DEFAULT NULL,
  `email` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `phone` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `address` text COLLATE utf8mb4_unicode_ci,
  `status` enum('Active','Inactive','Terminated') COLLATE utf8mb4_unicode_ci DEFAULT 'Active',
  `hire_date` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `workforce_employees`
--

INSERT INTO `workforce_employees` (`employee_id`, `employee_code`, `user_id`, `job_title_id`, `employment_type`, `payment_type`, `daily_rate`, `monthly_salary`, `bank_name`, `bank_account`, `emergency_contact_name`, `emergency_contact_phone`, `supervisor_id`, `notes`, `first_name`, `last_name`, `suffix`, `gender`, `birthday`, `email`, `phone`, `address`, `status`, `hire_date`) VALUES
(1, 'EMP-2026-001', 2, 1, NULL, NULL, 0.00, 0.00, NULL, NULL, NULL, NULL, NULL, NULL, 'Marco', 'Villar', NULL, NULL, NULL, 'marco.v@icmis.com', '0917-100-0001', NULL, 'Active', '2026-01-05'),
(2, 'EMP-2026-002', 3, 2, NULL, NULL, 0.00, 0.00, NULL, NULL, NULL, NULL, NULL, NULL, 'Diana', 'Lim', NULL, NULL, NULL, 'diana.l@icmis.com', '0917-100-0002', NULL, 'Active', '2026-01-05'),
(3, 'EMP-2026-003', 4, 3, NULL, NULL, 0.00, 0.00, NULL, NULL, NULL, NULL, NULL, NULL, 'Rico', 'Morales', NULL, NULL, NULL, 'rico.m@icmis.com', '0917-100-0003', NULL, 'Active', '2026-01-06'),
(4, 'EMP-2026-004', 5, 4, NULL, NULL, 0.00, 0.00, NULL, NULL, NULL, NULL, NULL, NULL, 'Carla', 'Sandoval', NULL, NULL, NULL, 'carla.s@icmis.com', '0917-100-0004', NULL, 'Active', '2026-01-02'),
(5, 'EMP-2026-005', 8, 5, NULL, NULL, 0.00, 0.00, NULL, NULL, NULL, NULL, NULL, NULL, 'Luis', 'Ortega', NULL, NULL, NULL, 'luis.o@icmis.com', '0917-100-0005', NULL, 'Active', '2026-01-05'),
(6, 'EMP-2026-006', 6, 6, NULL, NULL, 0.00, 0.00, NULL, NULL, NULL, NULL, NULL, NULL, 'Gina', 'Reyes', NULL, NULL, NULL, 'gina.r@icmis.com', '0917-100-0006', NULL, 'Active', '2026-01-03'),
(7, 'EMP-2026-007', 7, 7, NULL, NULL, 0.00, 0.00, NULL, NULL, NULL, NULL, NULL, NULL, 'Paulo', 'Santos', NULL, NULL, NULL, 'paulo.s@icmis.com', '0917-100-0007', NULL, 'Active', '2026-01-03'),
(8, 'EMP-2026-008', 9, 8, NULL, NULL, 0.00, 0.00, NULL, NULL, NULL, NULL, NULL, NULL, 'Tess', 'Garcia', NULL, NULL, NULL, 'tess.g@icmis.com', '0917-100-0008', NULL, 'Active', '2026-01-04'),
(9, 'EMP-2026-009', 10, 9, NULL, NULL, 0.00, 0.00, NULL, NULL, NULL, NULL, NULL, NULL, 'Jun', 'Abad', NULL, NULL, NULL, 'jun.a@icmis.com', '0917-100-0009', NULL, 'Active', '2026-01-05'),
(10, 'EMP-2026-010', 11, 10, NULL, NULL, 0.00, 0.00, NULL, NULL, NULL, NULL, NULL, NULL, 'Bert', 'Torres', NULL, NULL, NULL, 'bert.t@icmis.com', '0917-100-0010', NULL, 'Active', '2026-01-05'),
(11, 'EMP-2026-011', 12, 11, NULL, NULL, 0.00, 0.00, NULL, NULL, NULL, NULL, NULL, NULL, 'Ricardo', 'Dalisay', NULL, NULL, NULL, 'ricardo.d@icmis.com', '0917-200-0011', NULL, 'Active', '2026-01-10'),
(12, 'EMP-2026-012', 13, 12, NULL, NULL, 0.00, 0.00, NULL, NULL, NULL, NULL, NULL, NULL, 'Efren', 'Bata', NULL, NULL, NULL, 'efren.b@icmis.com', '0917-200-0012', NULL, 'Active', '2026-01-10'),
(13, 'EMP-2026-013', 14, 13, NULL, NULL, 0.00, 0.00, NULL, NULL, NULL, NULL, NULL, NULL, 'Joel', 'Cruz', NULL, NULL, NULL, 'joel.c@icmis.com', '0917-200-0013', NULL, 'Active', '2026-01-12'),
(14, 'EMP-2026-014', 15, 14, NULL, NULL, 0.00, 0.00, NULL, NULL, NULL, NULL, NULL, NULL, 'Mark', 'Go', NULL, NULL, NULL, 'mark.g@icmis.com', '0917-200-0014', NULL, 'Active', '2026-01-12'),
(15, 'EMP-2026-015', 16, 15, NULL, NULL, 0.00, 0.00, NULL, NULL, NULL, NULL, NULL, NULL, 'Romy', 'Diaz', NULL, NULL, NULL, 'romy.d@icmis.com', '0917-200-0015', NULL, 'Active', '2026-01-12'),
(16, 'EMP-2026-016', 17, 16, NULL, NULL, 0.00, 0.00, NULL, NULL, NULL, NULL, NULL, NULL, 'Dante', 'Alip', NULL, NULL, NULL, 'dante.a@icmis.com', '0917-200-0016', NULL, 'Active', '2026-01-11'),
(17, 'EMP-2026-017', 18, 17, NULL, NULL, 0.00, 0.00, NULL, NULL, NULL, NULL, NULL, NULL, 'Steve', 'Paz', NULL, NULL, NULL, 'steve.p@icmis.com', '0917-300-0017', NULL, 'Active', '2026-01-15'),
(18, 'EMP-2026-018', 19, 18, NULL, NULL, 0.00, 0.00, NULL, NULL, NULL, NULL, NULL, NULL, 'Ryan', 'Yap', NULL, NULL, NULL, 'ryan.y@icmis.com', '0917-300-0018', NULL, 'Active', '2026-01-15'),
(19, 'EMP-2026-019', 20, 19, NULL, NULL, 0.00, 0.00, NULL, NULL, NULL, NULL, NULL, NULL, 'Mario', 'Pineda', NULL, NULL, NULL, 'mario.p@icmis.com', '0917-300-0019', NULL, 'Active', '2026-01-15'),
(20, 'EMP-2026-020', 21, 20, NULL, NULL, 0.00, 0.00, NULL, NULL, NULL, NULL, NULL, NULL, 'Luigi', 'Pineda', NULL, NULL, NULL, 'luigi.p@icmis.com', '0917-300-0020', NULL, 'Active', '2026-01-15'),
(21, 'EMP-2026-021', 22, 21, NULL, NULL, 0.00, 0.00, NULL, NULL, NULL, NULL, NULL, NULL, 'Ken', 'Sy', NULL, NULL, NULL, 'ken.s@icmis.com', '0917-300-0021', NULL, 'Active', '2026-01-15'),
(22, 'EMP-2026-022', 23, 22, NULL, NULL, 0.00, 0.00, NULL, NULL, NULL, NULL, NULL, NULL, 'Manny', 'Wood', NULL, NULL, NULL, 'manny.w@icmis.com', '0917-400-0022', NULL, 'Active', '2026-02-01'),
(23, 'EMP-2026-023', 24, 23, NULL, NULL, 0.00, 0.00, NULL, NULL, NULL, NULL, NULL, NULL, 'Jack', 'Solis', NULL, NULL, NULL, 'jack.s@icmis.com', '0917-400-0023', NULL, 'Active', '2026-01-10'),
(24, 'EMP-2026-024', 25, 24, NULL, NULL, 0.00, 0.00, NULL, NULL, NULL, NULL, NULL, NULL, 'Peter', 'Vega', NULL, NULL, NULL, 'peter.v@icmis.com', '0917-400-0024', NULL, 'Active', '2026-02-05'),
(25, 'EMP-2026-025', 26, 25, NULL, NULL, 0.00, 0.00, NULL, NULL, NULL, NULL, NULL, NULL, 'Tyler', 'Tan', NULL, NULL, NULL, 'tyler.t@icmis.com', '0917-400-0025', NULL, 'Active', '2026-02-05'),
(26, 'EMP-2026-026', 27, 26, NULL, NULL, 0.00, 0.00, NULL, NULL, NULL, NULL, NULL, NULL, 'Jose', 'Glas', NULL, NULL, NULL, 'jose.g@icmis.com', '0917-400-0026', NULL, 'Active', '2026-02-10'),
(27, 'EMP-2026-027', 28, 27, NULL, NULL, 0.00, 0.00, NULL, NULL, NULL, NULL, NULL, NULL, 'Boyet', 'Labos', NULL, NULL, NULL, 'boyet.l@icmis.com', '0917-500-0027', NULL, 'Active', '2026-01-05'),
(28, 'EMP-2026-028', 29, 28, NULL, NULL, 0.00, 0.00, NULL, NULL, NULL, NULL, NULL, NULL, 'Juan', 'Dela Cruz', NULL, NULL, NULL, 'juan.d@icmis.com', '0917-500-0028', NULL, 'Active', '2026-01-05');

-- --------------------------------------------------------

--
-- Table structure for table `workforce_employee_groups`
--

CREATE TABLE `workforce_employee_groups` (
  `group_id` int NOT NULL,
  `group_code` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `group_name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `group_leader_id` int DEFAULT NULL,
  `description` text COLLATE utf8mb4_unicode_ci
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `workforce_employee_groups`
--

INSERT INTO `workforce_employee_groups` (`group_id`, `group_code`, `group_name`, `group_leader_id`, `description`) VALUES
(7, 'GRP-2026-7', 'Test Team', 11, '');

-- --------------------------------------------------------

--
-- Table structure for table `workforce_employee_skills`
--

CREATE TABLE `workforce_employee_skills` (
  `employee_skill_id` int NOT NULL,
  `employee_id` int NOT NULL,
  `skill_id` int NOT NULL,
  `proficiency_level` enum('Beginner','Intermediate','Advanced','Expert') COLLATE utf8mb4_unicode_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `workforce_group_memberships`
--

CREATE TABLE `workforce_group_memberships` (
  `membership_id` int NOT NULL,
  `employee_id` int NOT NULL,
  `group_id` int NOT NULL,
  `role_in_group` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `joined_date` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `workforce_group_memberships`
--

INSERT INTO `workforce_group_memberships` (`membership_id`, `employee_id`, `group_id`, `role_in_group`, `joined_date`) VALUES
(36, 9, 7, 'Timekeeper', '2026-01-08'),
(37, 16, 7, 'Heavy Equipment Operator', '2026-01-08'),
(38, 12, 7, 'Master Mason', '2026-01-08');

-- --------------------------------------------------------

--
-- Table structure for table `workforce_job_titles`
--

CREATE TABLE `workforce_job_titles` (
  `job_title_id` int NOT NULL,
  `title_name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `department` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
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
-- Table structure for table `workforce_leave_balances`
--

CREATE TABLE `workforce_leave_balances` (
  `balance_id` int NOT NULL,
  `employee_id` int NOT NULL,
  `leave_type_id` int NOT NULL,
  `balance` decimal(5,2) DEFAULT '0.00'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `workforce_leave_requests`
--

CREATE TABLE `workforce_leave_requests` (
  `request_id` int NOT NULL,
  `employee_id` int NOT NULL,
  `leave_type_id` int NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `reason` text COLLATE utf8mb4_unicode_ci,
  `status` enum('Pending','Approved','Rejected') COLLATE utf8mb4_unicode_ci DEFAULT 'Pending',
  `reviewed_by` int DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `workforce_leave_types`
--

CREATE TABLE `workforce_leave_types` (
  `leave_type_id` int NOT NULL,
  `leave_name` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `max_days_per_year` int DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `workforce_notifications`
--

CREATE TABLE `workforce_notifications` (
  `notification_id` int NOT NULL,
  `recipient_user_id` int DEFAULT NULL,
  `title` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `message` text COLLATE utf8mb4_unicode_ci,
  `is_read` tinyint(1) DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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
  `status` enum('Calculated','Approved','Processed') COLLATE utf8mb4_unicode_ci DEFAULT 'Calculated'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `workforce_payroll_config`
--

CREATE TABLE `workforce_payroll_config` (
  `config_id` int NOT NULL,
  `config_name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `config_value` text COLLATE utf8mb4_unicode_ci,
  `config_type` enum('Tax','Contribution','Overtime','Allowance') COLLATE utf8mb4_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `workforce_payroll_periods`
--

CREATE TABLE `workforce_payroll_periods` (
  `period_id` int NOT NULL,
  `period_name` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `pay_date` date DEFAULT NULL,
  `status` enum('Open','Closed','Processed') COLLATE utf8mb4_unicode_ci DEFAULT 'Open'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `workforce_skills`
--

CREATE TABLE `workforce_skills` (
  `skill_id` int NOT NULL,
  `skill_name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `category` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `description` text COLLATE utf8mb4_unicode_ci
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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
-- Indexes for table `workforce_employee_skills`
--
ALTER TABLE `workforce_employee_skills`
  ADD PRIMARY KEY (`employee_skill_id`),
  ADD KEY `fk_skill_emp` (`employee_id`),
  ADD KEY `fk_skill_id` (`skill_id`);

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
-- Indexes for table `workforce_leave_balances`
--
ALTER TABLE `workforce_leave_balances`
  ADD PRIMARY KEY (`balance_id`),
  ADD KEY `fk_leave_emp` (`employee_id`),
  ADD KEY `fk_leave_type` (`leave_type_id`);

--
-- Indexes for table `workforce_leave_requests`
--
ALTER TABLE `workforce_leave_requests`
  ADD PRIMARY KEY (`request_id`),
  ADD KEY `fk_req_emp` (`employee_id`),
  ADD KEY `fk_req_type` (`leave_type_id`),
  ADD KEY `fk_req_reviewer` (`reviewed_by`);

--
-- Indexes for table `workforce_leave_types`
--
ALTER TABLE `workforce_leave_types`
  ADD PRIMARY KEY (`leave_type_id`);

--
-- Indexes for table `workforce_notifications`
--
ALTER TABLE `workforce_notifications`
  ADD PRIMARY KEY (`notification_id`),
  ADD KEY `fk_notif_user` (`recipient_user_id`);

--
-- Indexes for table `workforce_payroll`
--
ALTER TABLE `workforce_payroll`
  ADD PRIMARY KEY (`payroll_id`),
  ADD KEY `fk_payroll_emp` (`employee_id`),
  ADD KEY `fk_payroll_period` (`period_id`);

--
-- Indexes for table `workforce_payroll_config`
--
ALTER TABLE `workforce_payroll_config`
  ADD PRIMARY KEY (`config_id`);

--
-- Indexes for table `workforce_payroll_periods`
--
ALTER TABLE `workforce_payroll_periods`
  ADD PRIMARY KEY (`period_id`);

--
-- Indexes for table `workforce_skills`
--
ALTER TABLE `workforce_skills`
  ADD PRIMARY KEY (`skill_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `budget_expenses`
--
ALTER TABLE `budget_expenses`
  MODIFY `expense_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `budget_generated_reports`
--
ALTER TABLE `budget_generated_reports`
  MODIFY `report_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

--
-- AUTO_INCREMENT for table `budget_line_items`
--
ALTER TABLE `budget_line_items`
  MODIFY `line_item_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `budget_proposals`
--
ALTER TABLE `budget_proposals`
  MODIFY `proposal_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `icmis_projects`
--
ALTER TABLE `icmis_projects`
  MODIFY `project_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `icmis_project_phases`
--
ALTER TABLE `icmis_project_phases`
  MODIFY `phase_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

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
  MODIFY `item_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `procurement_purchase_orders`
--
ALTER TABLE `procurement_purchase_orders`
  MODIFY `po_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `procurement_purchase_order_items`
--
ALTER TABLE `procurement_purchase_order_items`
  MODIFY `po_item_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `procurement_stock_in`
--
ALTER TABLE `procurement_stock_in`
  MODIFY `stock_in_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `procurement_stock_out`
--
ALTER TABLE `procurement_stock_out`
  MODIFY `stock_out_id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `procurement_suppliers`
--
ALTER TABLE `procurement_suppliers`
  MODIFY `supplier_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `workforce_assignments`
--
ALTER TABLE `workforce_assignments`
  MODIFY `assignment_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=32;

--
-- AUTO_INCREMENT for table `workforce_attendance`
--
ALTER TABLE `workforce_attendance`
  MODIFY `attendance_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;

--
-- AUTO_INCREMENT for table `workforce_employees`
--
ALTER TABLE `workforce_employees`
  MODIFY `employee_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=39;

--
-- AUTO_INCREMENT for table `workforce_employee_groups`
--
ALTER TABLE `workforce_employee_groups`
  MODIFY `group_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `workforce_employee_skills`
--
ALTER TABLE `workforce_employee_skills`
  MODIFY `employee_skill_id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `workforce_group_memberships`
--
ALTER TABLE `workforce_group_memberships`
  MODIFY `membership_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=39;

--
-- AUTO_INCREMENT for table `workforce_job_titles`
--
ALTER TABLE `workforce_job_titles`
  MODIFY `job_title_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=29;

--
-- AUTO_INCREMENT for table `workforce_leave_balances`
--
ALTER TABLE `workforce_leave_balances`
  MODIFY `balance_id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `workforce_leave_requests`
--
ALTER TABLE `workforce_leave_requests`
  MODIFY `request_id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `workforce_leave_types`
--
ALTER TABLE `workforce_leave_types`
  MODIFY `leave_type_id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `workforce_notifications`
--
ALTER TABLE `workforce_notifications`
  MODIFY `notification_id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `workforce_payroll`
--
ALTER TABLE `workforce_payroll`
  MODIFY `payroll_id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `workforce_payroll_config`
--
ALTER TABLE `workforce_payroll_config`
  MODIFY `config_id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `workforce_payroll_periods`
--
ALTER TABLE `workforce_payroll_periods`
  MODIFY `period_id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `workforce_skills`
--
ALTER TABLE `workforce_skills`
  MODIFY `skill_id` int NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `budget_expenses`
--
ALTER TABLE `budget_expenses`
  ADD CONSTRAINT `fk_exp_creator` FOREIGN KEY (`created_by`) REFERENCES `icmis_users` (`user_id`),
  ADD CONSTRAINT `fk_exp_phase` FOREIGN KEY (`phase_id`) REFERENCES `icmis_project_phases` (`phase_id`),
  ADD CONSTRAINT `fk_exp_project` FOREIGN KEY (`project_id`) REFERENCES `icmis_projects` (`project_id`),
  ADD CONSTRAINT `fk_exp_supplier` FOREIGN KEY (`supplier_id`) REFERENCES `procurement_suppliers` (`supplier_id`);

--
-- Constraints for table `budget_generated_reports`
--
ALTER TABLE `budget_generated_reports`
  ADD CONSTRAINT `fk_rep_project` FOREIGN KEY (`project_id`) REFERENCES `icmis_projects` (`project_id`);

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
  ADD CONSTRAINT `fk_prop_phase` FOREIGN KEY (`phase_id`) REFERENCES `icmis_project_phases` (`phase_id`),
  ADD CONSTRAINT `fk_prop_project` FOREIGN KEY (`project_id`) REFERENCES `icmis_projects` (`project_id`);

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
  ADD CONSTRAINT `fk_po_creator` FOREIGN KEY (`created_by_user_id`) REFERENCES `icmis_users` (`user_id`),
  ADD CONSTRAINT `fk_po_phase` FOREIGN KEY (`phase_id`) REFERENCES `icmis_project_phases` (`phase_id`),
  ADD CONSTRAINT `fk_po_project` FOREIGN KEY (`project_id`) REFERENCES `icmis_projects` (`project_id`),
  ADD CONSTRAINT `fk_po_supplier` FOREIGN KEY (`supplier_id`) REFERENCES `procurement_suppliers` (`supplier_id`);

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
  ADD CONSTRAINT `fk_stockin_item` FOREIGN KEY (`item_id`) REFERENCES `procurement_inventory` (`item_id`),
  ADD CONSTRAINT `fk_stockin_po` FOREIGN KEY (`po_id`) REFERENCES `procurement_purchase_orders` (`po_id`);

--
-- Constraints for table `procurement_stock_out`
--
ALTER TABLE `procurement_stock_out`
  ADD CONSTRAINT `fk_stockout_emp` FOREIGN KEY (`issued_to_employee_id`) REFERENCES `workforce_employees` (`employee_id`),
  ADD CONSTRAINT `fk_stockout_item` FOREIGN KEY (`item_id`) REFERENCES `procurement_inventory` (`item_id`),
  ADD CONSTRAINT `fk_stockout_proj` FOREIGN KEY (`project_id`) REFERENCES `icmis_projects` (`project_id`);

--
-- Constraints for table `workforce_assignments`
--
ALTER TABLE `workforce_assignments`
  ADD CONSTRAINT `fk_assign_emp` FOREIGN KEY (`employee_id`) REFERENCES `workforce_employees` (`employee_id`),
  ADD CONSTRAINT `fk_assign_phase` FOREIGN KEY (`phase_id`) REFERENCES `icmis_project_phases` (`phase_id`),
  ADD CONSTRAINT `fk_assign_proj` FOREIGN KEY (`project_id`) REFERENCES `icmis_projects` (`project_id`);

--
-- Constraints for table `workforce_attendance`
--
ALTER TABLE `workforce_attendance`
  ADD CONSTRAINT `fk_att_emp` FOREIGN KEY (`employee_id`) REFERENCES `workforce_employees` (`employee_id`),
  ADD CONSTRAINT `fk_att_proj` FOREIGN KEY (`project_id`) REFERENCES `icmis_projects` (`project_id`);

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
-- Constraints for table `workforce_employee_skills`
--
ALTER TABLE `workforce_employee_skills`
  ADD CONSTRAINT `fk_skill_emp` FOREIGN KEY (`employee_id`) REFERENCES `workforce_employees` (`employee_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_skill_id` FOREIGN KEY (`skill_id`) REFERENCES `workforce_skills` (`skill_id`) ON DELETE CASCADE;

--
-- Constraints for table `workforce_group_memberships`
--
ALTER TABLE `workforce_group_memberships`
  ADD CONSTRAINT `fk_mem_emp` FOREIGN KEY (`employee_id`) REFERENCES `workforce_employees` (`employee_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_mem_group` FOREIGN KEY (`group_id`) REFERENCES `workforce_employee_groups` (`group_id`) ON DELETE CASCADE;

--
-- Constraints for table `workforce_leave_balances`
--
ALTER TABLE `workforce_leave_balances`
  ADD CONSTRAINT `fk_leave_emp` FOREIGN KEY (`employee_id`) REFERENCES `workforce_employees` (`employee_id`),
  ADD CONSTRAINT `fk_leave_type` FOREIGN KEY (`leave_type_id`) REFERENCES `workforce_leave_types` (`leave_type_id`);

--
-- Constraints for table `workforce_leave_requests`
--
ALTER TABLE `workforce_leave_requests`
  ADD CONSTRAINT `fk_req_emp` FOREIGN KEY (`employee_id`) REFERENCES `workforce_employees` (`employee_id`),
  ADD CONSTRAINT `fk_req_reviewer` FOREIGN KEY (`reviewed_by`) REFERENCES `workforce_employees` (`employee_id`),
  ADD CONSTRAINT `fk_req_type` FOREIGN KEY (`leave_type_id`) REFERENCES `workforce_leave_types` (`leave_type_id`);

--
-- Constraints for table `workforce_notifications`
--
ALTER TABLE `workforce_notifications`
  ADD CONSTRAINT `fk_notif_user` FOREIGN KEY (`recipient_user_id`) REFERENCES `icmis_users` (`user_id`);

--
-- Constraints for table `workforce_payroll`
--
ALTER TABLE `workforce_payroll`
  ADD CONSTRAINT `fk_payroll_emp` FOREIGN KEY (`employee_id`) REFERENCES `workforce_employees` (`employee_id`),
  ADD CONSTRAINT `fk_payroll_period` FOREIGN KEY (`period_id`) REFERENCES `workforce_payroll_periods` (`period_id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
