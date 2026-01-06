-- phpMyAdmin SQL Dump
-- version 6.0.0-dev+20250914.f72491a1c0
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Jan 06, 2026 at 02:35 AM
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
  `category` enum('MATERIALS','LABOR','EQUIPMENT') DEFAULT NULL,
  `description` varchar(500) DEFAULT NULL,
  `amount` decimal(15,2) NOT NULL,
  `expense_date` date NOT NULL,
  `status` enum('PENDING','APPROVED','REJECTED') DEFAULT 'PENDING',
  `created_by` int DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `budget_expenses`
--

INSERT INTO `budget_expenses` (`expense_id`, `project_id`, `phase_id`, `supplier_id`, `category`, `description`, `amount`, `expense_date`, `status`, `created_by`) VALUES
(1, 1, NULL, 1, 'MATERIALS', 'Purchase of Cement for Fence base', 11500.00, '2025-01-16', 'APPROVED', NULL),
(2, 5, NULL, 4, 'MATERIALS', 'Payment for PO-2026-088 (Rebars)', 32000.00, '2026-01-06', 'APPROVED', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `budget_generated_reports`
--

CREATE TABLE `budget_generated_reports` (
  `report_id` int NOT NULL,
  `project_id` int DEFAULT NULL,
  `report_type` varchar(50) DEFAULT NULL,
  `report_name` varchar(255) DEFAULT NULL,
  `generated_by` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `budget_generated_reports`
--

INSERT INTO `budget_generated_reports` (`report_id`, `project_id`, `report_type`, `report_name`, `generated_by`, `created_at`) VALUES
(1, 1, 'Variance', 'Jan 2025 Variance Report', 'Maria Santos', '2026-01-05 12:13:38'),
(2, 5, 'Cost Variance', 'Feb 2026 Variance Report', 'System Admin', '2026-01-05 23:42:43'),
(3, 7, 'budget-summary', 'Budget Summary', 'John Doe', '2026-01-06 01:50:53');

-- --------------------------------------------------------

--
-- Table structure for table `budget_line_items`
--

CREATE TABLE `budget_line_items` (
  `line_item_id` int NOT NULL,
  `proposal_id` int NOT NULL,
  `category` enum('MATERIAL','LABOR','EQUIPMENT') DEFAULT NULL,
  `item_name` varchar(255) NOT NULL,
  `quantity` decimal(10,2) DEFAULT NULL,
  `unit_cost` decimal(15,2) DEFAULT NULL,
  `duration` decimal(10,2) DEFAULT '1.00',
  `subtotal` decimal(15,2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `budget_line_items`
--

INSERT INTO `budget_line_items` (`line_item_id`, `proposal_id`, `category`, `item_name`, `quantity`, `unit_cost`, `duration`, `subtotal`) VALUES
(1, 1, 'MATERIAL', 'Temporary Fence', 100.00, 450.00, 1.00, 45000.00),
(2, 1, 'LABOR', 'Labor for Fencing', 1.00, 15000.00, 1.00, 15000.00),
(6, 8, 'MATERIAL', '16mm Rebars', 100.00, 320.00, 1.00, 32000.00),
(7, 8, 'LABOR', 'Rebar Fabrication Labor', 1.00, 18000.00, 1.00, 18000.00),
(16, 12, 'MATERIAL', 'Coco Lumber (bd.ft)', 12.00, 1212.00, 1.00, 14544.00);

-- --------------------------------------------------------

--
-- Table structure for table `budget_proposals`
--

CREATE TABLE `budget_proposals` (
  `proposal_id` int NOT NULL,
  `project_id` int NOT NULL,
  `phase_id` int DEFAULT NULL,
  `code` varchar(20) DEFAULT NULL,
  `title` varchar(255) NOT NULL,
  `description` text,
  `total_amount` decimal(15,2) NOT NULL,
  `status` enum('DRAFT','PENDING','APPROVED','REJECTED') DEFAULT 'DRAFT',
  `created_by` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `budget_proposals`
--

INSERT INTO `budget_proposals` (`proposal_id`, `project_id`, `phase_id`, `code`, `title`, `description`, `total_amount`, `status`, `created_by`, `created_at`) VALUES
(1, 1, 1, 'BP-001', 'Mobilization Fund', 'Budget for temp facilities', 250000.00, 'APPROVED', NULL, '2026-01-05 12:13:38'),
(2, 2, 1, 'BP-002', 'Initial Survey', 'Budget for land survey', 50000.00, 'PENDING', NULL, '2026-01-05 12:13:38'),
(8, 5, NULL, 'BP-2026-05', 'Steel Reinforcement Top-up', NULL, 50000.00, 'APPROVED', NULL, '2026-01-05 23:42:43'),
(12, 7, NULL, 'BP-2026-0004', 'Phase 1: Mobilization Budget Proposal', 'asdffasd', 14544.00, 'APPROVED', 5, '2026-01-06 02:27:19');

-- --------------------------------------------------------

--
-- Table structure for table `icmis_projects`
--

CREATE TABLE `icmis_projects` (
  `project_id` int NOT NULL,
  `project_code` varchar(20) NOT NULL,
  `project_name` varchar(255) NOT NULL,
  `description` text,
  `location` varchar(255) DEFAULT NULL,
  `status` enum('Planning','In Progress','Active','On Hold','Completed','Cancelled') DEFAULT 'Planning',
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `completion_rate` decimal(5,2) DEFAULT '0.00',
  `project_manager_id` int DEFAULT NULL,
  `total_budget` decimal(15,2) DEFAULT '0.00'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `icmis_projects`
--

INSERT INTO `icmis_projects` (`project_id`, `project_code`, `project_name`, `description`, `location`, `status`, `start_date`, `end_date`, `completion_rate`, `project_manager_id`, `total_budget`) VALUES
(1, 'PRJ-2025-001', 'Makati Skyline Tower', NULL, 'Makati City', 'Active', '2025-01-15', '2027-06-30', 0.00, 1, 15000000.00),
(2, 'PRJ-2025-002', 'Bacolod Gov Center Annex', NULL, 'Bacolod City', 'Planning', '2025-06-01', '2026-12-15', 0.00, 1, 8500000.00),
(5, 'PRJ-2026-101', 'MegaWorld Retail Complex', '3-Storey commercial building with basement parking.', 'Lacson St., Bacolod City', 'Active', '2025-11-01', '2027-05-30', 15.50, 7, 45000000.00),
(6, 'PRJ-2026-102', 'Villa Angela Clubhouse', 'Community clubhouse renovation and pool tiling.', 'Villa Angela Subdivision', 'Planning', '2026-03-01', '2026-09-15', 0.00, 7, 3500000.00),
(7, 'PRJ-2026-103', 'Davao', 'asdfasdf', 'Davao City', 'Planning', '2026-01-06', '2027-01-06', 0.00, 4, 10000000.00);

-- --------------------------------------------------------

--
-- Table structure for table `icmis_project_phases`
--

CREATE TABLE `icmis_project_phases` (
  `phase_id` int NOT NULL,
  `project_id` int NOT NULL,
  `phase_name` varchar(100) NOT NULL,
  `description` text,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `duration` int DEFAULT '0' COMMENT 'Duration in days',
  `status` enum('Not Started','In Progress','Completed') DEFAULT 'Not Started'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `icmis_project_phases`
--

INSERT INTO `icmis_project_phases` (`phase_id`, `project_id`, `phase_name`, `description`, `start_date`, `end_date`, `duration`, `status`) VALUES
(1, 1, 'Phase 1: Mobilization', 'Site prep and fencing', '2025-01-15', '2025-02-14', 30, 'Completed'),
(2, 1, 'Phase 2: Structural', 'Foundation and framing', '2025-02-15', '2026-06-30', 500, 'In Progress'),
(3, 2, 'Phase 1: Planning', 'Blueprints and permits', '2025-06-01', '2026-03-01', 273, 'Not Started'),
(4, 5, 'Phase 1: Excavation & Piling', 'Deep excavation for basement parking and foundation piles.', '2025-11-01', '2026-01-30', 90, 'Completed'),
(5, 5, 'Phase 2: Substructure', 'Basement concreting, retaining walls, and waterproofing.', '2026-02-01', '2026-05-30', 120, 'In Progress'),
(6, 5, 'Phase 3: Superstructure', 'Columns, beams, and slab works for 1st-3rd floors.', '2026-06-01', '2026-12-15', 198, 'Not Started'),
(7, 6, 'Phase 1: Demolition', 'Removal of old roofing and floor tiles.', '2026-03-01', '2026-03-15', 15, 'Not Started'),
(9, 7, 'Phase 1: Mobilization', 'asdfadsfasdf', '2026-01-06', '2026-02-06', 31, 'Not Started');

-- --------------------------------------------------------

--
-- Table structure for table `icmis_tasks`
--

CREATE TABLE `icmis_tasks` (
  `task_id` int NOT NULL,
  `project_id` int NOT NULL,
  `phase_id` int DEFAULT NULL,
  `task_name` varchar(100) NOT NULL,
  `description` text,
  `assigned_to_employee_id` int DEFAULT NULL,
  `start_date` date DEFAULT NULL,
  `due_date` date DEFAULT NULL,
  `status` enum('Not Started','In Progress','Completed','On Hold') DEFAULT 'Not Started',
  `priority` enum('Low','Medium','High','Urgent') DEFAULT 'Medium'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `icmis_tasks`
--

INSERT INTO `icmis_tasks` (`task_id`, `project_id`, `phase_id`, `task_name`, `description`, `assigned_to_employee_id`, `start_date`, `due_date`, `status`, `priority`) VALUES
(1, 1, 1, 'Install Perimeter Fence', 'Secure the site', 3, NULL, NULL, 'Completed', 'High'),
(2, 1, 2, 'Pour Foundation', 'Cement pouring for main bldg', 3, NULL, NULL, 'In Progress', 'Urgent'),
(3, 5, 4, 'Soil Testing', 'Standard penetration test (SPT) for soil bearing capacity.', 8, '2025-11-05', '2025-11-10', 'Completed', 'High'),
(4, 5, 4, 'Hauling of Debris', 'Disposal of excavated soil to designated dumping site.', 8, '2025-11-15', '2025-12-20', 'Completed', 'Medium'),
(5, 5, 5, 'Rebar Installation (Retaining Wall)', 'Fabrication and installation of 16mm/20mm rebars.', 9, '2026-02-05', '2026-02-28', 'In Progress', 'Urgent'),
(6, 5, 5, 'Waterproofing Application', 'Apply bituminous membrane on basement exterior walls.', 9, '2026-03-05', '2026-03-15', 'Not Started', 'High'),
(7, 5, 5, 'Pouring Request Inspection', 'Submit pouring request to QA/QC before concrete pouring.', 8, '2026-02-25', '2026-02-27', 'Not Started', 'Urgent'),
(8, 6, 7, 'Safety Net Installation', 'Install safety nets around the clubhouse perimeter.', 8, '2026-02-28', '2026-03-02', 'Not Started', 'Medium'),
(9, 7, 9, 'asdfasdf', 'asdfasdf', 4, '2026-01-06', '2026-01-13', 'In Progress', 'Medium');

-- --------------------------------------------------------

--
-- Table structure for table `icmis_users`
--

CREATE TABLE `icmis_users` (
  `user_id` int NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('Admin','Manager','Staff','Budget_Officer','Procurement_Officer') DEFAULT 'Staff',
  `avatar` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `icmis_users`
--

INSERT INTO `icmis_users` (`user_id`, `full_name`, `email`, `password`, `role`, `avatar`, `created_at`) VALUES
(1, 'Admin System', 'admin@icmis.com', '$2y$10$hash', 'Admin', NULL, '2026-01-05 12:13:38'),
(2, 'Engr. Antonio Reyes', 'antonio.reyes@icmis.com', '$2y$10$hash', 'Manager', NULL, '2026-01-05 12:13:38'),
(3, 'Maria Santos', 'maria.santos@icmis.com', '$2y$10$hash', 'Budget_Officer', NULL, '2026-01-05 12:13:38'),
(4, 'Juan Cruz', 'juan.cruz@icmis.com', '$2y$10$hash', 'Staff', NULL, '2026-01-05 12:13:38'),
(5, 'John Doe', 'john.doe@icmis.com', '$2y$12$TOFbSmvIu1gf4smOHNKQxuV2vshzPpklWmNmesmRODc4PfFJHCJbK', 'Admin', NULL, '2026-01-05 12:17:43'),
(6, 'Engr. Michael Tan', 'mike.tan@icmis.com', '$2y$10$hashedpasswordplaceholder', 'Manager', 'avatar_mike.jpg', '2026-01-05 23:36:51'),
(7, 'Sarah Mendoza', 'sarah.m@icmis.com', '$2y$10$hashedpasswordplaceholder', 'Budget_Officer', 'avatar_sarah.jpg', '2026-01-05 23:36:51'),
(8, 'Ramon Bautista', 'ramon.b@icmis.com', '$2y$10$hashedpasswordplaceholder', 'Staff', 'avatar_ramon.jpg', '2026-01-05 23:36:51');

-- --------------------------------------------------------

--
-- Table structure for table `procurement_inventory`
--

CREATE TABLE `procurement_inventory` (
  `item_id` int NOT NULL,
  `item_name` varchar(255) NOT NULL,
  `category` varchar(100) DEFAULT 'Uncategorized',
  `quantity` decimal(10,2) DEFAULT '0.00',
  `unit` varchar(50) DEFAULT NULL,
  `unit_cost` decimal(15,2) DEFAULT '0.00',
  `last_updated` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `procurement_inventory`
--

INSERT INTO `procurement_inventory` (`item_id`, `item_name`, `category`, `quantity`, `unit`, `unit_cost`, `last_updated`) VALUES
(1, 'Portland Cement', 'Materials', 450.00, 'bags', 230.00, '2026-01-05 12:13:38'),
(2, '10mm Deformed Bar', 'Materials', 1000.00, 'pcs', 180.00, '2026-01-05 12:13:38'),
(3, 'Safety Helmet (Yellow)', 'PPE', 48.00, 'pcs', 350.00, '2026-01-05 12:13:38'),
(4, 'Gravel G1', 'Aggregates', 50.00, 'cu.m', 1200.00, '2026-01-05 12:13:38'),
(5, '16mm Deformed Bar', 'Steel', 500.00, 'pcs', 320.00, '2026-01-05 23:42:43'),
(6, 'Welding Rod (6013)', 'Consumables', 20.00, 'boxes', 1500.00, '2026-01-05 23:42:43'),
(7, 'Portland Cement (Type 1)', 'Cement', 100.00, 'bags', 245.00, '2026-01-05 23:42:43');

-- --------------------------------------------------------

--
-- Table structure for table `procurement_purchase_orders`
--

CREATE TABLE `procurement_purchase_orders` (
  `po_id` int NOT NULL,
  `po_reference` varchar(50) NOT NULL,
  `project_id` int NOT NULL,
  `supplier_id` int NOT NULL,
  `phase` varchar(100) DEFAULT NULL,
  `order_title` varchar(255) DEFAULT NULL,
  `order_date` date DEFAULT (curdate()),
  `total_amount` decimal(15,2) DEFAULT '0.00',
  `status` enum('PENDING','APPROVED','REJECTED','COMPLETED') DEFAULT 'PENDING',
  `created_by_user_id` int DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `procurement_purchase_orders`
--

INSERT INTO `procurement_purchase_orders` (`po_id`, `po_reference`, `project_id`, `supplier_id`, `phase`, `order_title`, `order_date`, `total_amount`, `status`, `created_by_user_id`) VALUES
(1, 'PO-2025-001', 1, 1, 'Mobilization', NULL, '2026-01-05', 11500.00, 'COMPLETED', 2),
(2, 'PO-2025-002', 1, 3, 'Structural', NULL, '2026-01-05', 18000.00, 'APPROVED', 2),
(3, 'PO-2026-088', 5, 4, NULL, 'Additional Rebars for Cols', '2026-01-06', 32000.00, 'APPROVED', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `procurement_purchase_order_items`
--

CREATE TABLE `procurement_purchase_order_items` (
  `po_item_id` int NOT NULL,
  `po_id` int NOT NULL,
  `inventory_item_id` int DEFAULT NULL,
  `item_name` varchar(255) NOT NULL,
  `quantity` decimal(10,2) NOT NULL,
  `unit_cost` decimal(15,2) NOT NULL,
  `total_cost` decimal(15,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `procurement_purchase_order_items`
--

INSERT INTO `procurement_purchase_order_items` (`po_item_id`, `po_id`, `inventory_item_id`, `item_name`, `quantity`, `unit_cost`, `total_cost`) VALUES
(1, 1, 1, 'Portland Cement', 50.00, 230.00, 11500.00),
(2, 2, 2, '10mm Deformed Bar', 100.00, 180.00, 18000.00),
(3, 3, 5, '16mm Deformed Bar', 100.00, 320.00, 32000.00);

-- --------------------------------------------------------

--
-- Table structure for table `procurement_stock_in`
--

CREATE TABLE `procurement_stock_in` (
  `stock_in_id` int NOT NULL,
  `po_id` int NOT NULL,
  `item_name` varchar(100) NOT NULL,
  `quantity_received` int NOT NULL,
  `date_received` date DEFAULT (curdate())
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `procurement_stock_in`
--

INSERT INTO `procurement_stock_in` (`stock_in_id`, `po_id`, `item_name`, `quantity_received`, `date_received`) VALUES
(1, 1, 'Portland Cement', 50, '2025-01-16'),
(2, 3, '16mm Deformed Bar', 100, '2026-01-06');

-- --------------------------------------------------------

--
-- Table structure for table `procurement_stock_out`
--

CREATE TABLE `procurement_stock_out` (
  `stock_out_id` int NOT NULL,
  `item_id` int NOT NULL,
  `quantity` int NOT NULL,
  `issued_to` varchar(100) DEFAULT NULL,
  `date_issued` date DEFAULT (curdate()),
  `project_id` int DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `procurement_stock_out`
--

INSERT INTO `procurement_stock_out` (`stock_out_id`, `item_id`, `quantity`, `issued_to`, `date_issued`, `project_id`) VALUES
(1, 1, 20, 'Pedro Penduko (Foreman)', '2026-01-05', 1),
(2, 5, 50, 'Ramon (Foreman)', '2026-01-06', 5);

-- --------------------------------------------------------

--
-- Table structure for table `procurement_suppliers`
--

CREATE TABLE `procurement_suppliers` (
  `supplier_id` int NOT NULL,
  `supplier_name` varchar(100) NOT NULL,
  `contact_person` varchar(100) DEFAULT NULL,
  `contact_number` varchar(20) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `address` text,
  `status` enum('Active','Inactive') DEFAULT 'Active'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `procurement_suppliers`
--

INSERT INTO `procurement_suppliers` (`supplier_id`, `supplier_name`, `contact_person`, `contact_number`, `email`, `address`, `status`) VALUES
(1, 'CitiHardware', 'Mike Tan', NULL, 'sales@citi.ph', 'Bacolod City', 'Active'),
(2, 'Wilcon Depot', 'Sarah Lee', NULL, 'b2b@wilcon.ph', 'Talisay City', 'Active'),
(3, 'Negros Steel', 'Rudy Go', NULL, 'orders@negrossteel.com', 'Silay City', 'Active'),
(4, 'Negros Cement Corp', 'Dina V.', '0917-111-2222', NULL, NULL, 'Active'),
(5, 'Bacolod Steel Center', 'Mr. Lim', '0918-333-4444', NULL, NULL, 'Active');

-- --------------------------------------------------------

--
-- Table structure for table `workforce_assignments`
--

CREATE TABLE `workforce_assignments` (
  `assignment_id` int NOT NULL,
  `employee_id` int NOT NULL,
  `project_id` int NOT NULL,
  `phase_id` int DEFAULT NULL,
  `role` varchar(100) DEFAULT NULL,
  `task_description` text,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `status` enum('Active','Completed','Cancelled') DEFAULT 'Active'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `workforce_assignments`
--

INSERT INTO `workforce_assignments` (`assignment_id`, `employee_id`, `project_id`, `phase_id`, `role`, `task_description`, `start_date`, `end_date`, `status`) VALUES
(1, 3, 1, 2, 'Site Foreman', NULL, NULL, NULL, 'Active'),
(2, 4, 1, 2, 'Lead Mason', NULL, NULL, NULL, 'Active'),
(3, 5, 1, 2, 'Helper', NULL, NULL, NULL, 'Active'),
(4, 10, 5, NULL, 'Structural Welder', NULL, '2026-02-01', NULL, 'Active');

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
  `status` enum('Present','Absent','Late','On Leave') NOT NULL,
  `remarks` text
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `workforce_attendance`
--

INSERT INTO `workforce_attendance` (`attendance_id`, `employee_id`, `project_id`, `attendance_date`, `time_in`, `time_out`, `status`, `remarks`) VALUES
(1, 3, 1, '2025-01-20', '07:55:00', '17:00:00', 'Present', NULL),
(2, 4, 1, '2025-01-20', '08:00:00', '17:00:00', 'Present', NULL),
(3, 5, 1, '2025-01-20', '08:30:00', '17:00:00', 'Late', NULL),
(4, 10, 5, '2026-02-02', '08:00:00', '17:00:00', 'Present', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `workforce_employees`
--

CREATE TABLE `workforce_employees` (
  `employee_id` int NOT NULL,
  `employee_code` varchar(20) NOT NULL,
  `user_id` int DEFAULT NULL,
  `job_title_id` int DEFAULT NULL,
  `first_name` varchar(50) NOT NULL,
  `last_name` varchar(50) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `status` enum('Active','Inactive','Terminated') DEFAULT 'Active',
  `hire_date` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `workforce_employees`
--

INSERT INTO `workforce_employees` (`employee_id`, `employee_code`, `user_id`, `job_title_id`, `first_name`, `last_name`, `email`, `phone`, `status`, `hire_date`) VALUES
(1, 'EMP-2024-001', 2, 1, 'Antonio', 'Reyes', 'antonio.reyes@icmis.com', NULL, 'Active', '2024-01-10'),
(2, 'EMP-2024-002', 3, 6, 'Maria', 'Santos', 'maria.santos@icmis.com', NULL, 'Active', '2024-02-15'),
(3, 'EMP-2024-003', NULL, 3, 'Pedro', 'Penduko', 'pedro.p@email.com', NULL, 'Active', '2024-03-01'),
(4, 'EMP-2024-004', NULL, 4, 'Cardo', 'Dalisay', NULL, NULL, 'Active', '2024-03-05'),
(5, 'EMP-2024-005', NULL, 5, 'Jose', 'Manalo', NULL, NULL, 'Active', '2024-03-10'),
(6, 'EMP-2024-006', 4, 2, 'Juan', 'Cruz', 'juan.cruz@icmis.com', NULL, 'Active', '2024-04-01'),
(7, 'EMP-2026-050', 6, 1, 'Michael', 'Tan', 'mike.tan@icmis.com', NULL, 'Active', '2023-05-10'),
(8, 'EMP-2026-051', 8, 3, 'Ramon', 'Bautista', 'ramon.b@icmis.com', NULL, 'Active', '2024-02-15'),
(9, 'EMP-2026-052', NULL, 4, 'Carlos', 'Dela Cruz', 'carlos.dc@email.com', NULL, 'Active', '2024-06-20'),
(10, 'EMP-2026-060', NULL, 10, 'Jun', 'Cortez', NULL, NULL, 'Active', '2025-08-01');

-- --------------------------------------------------------

--
-- Table structure for table `workforce_employee_groups`
--

CREATE TABLE `workforce_employee_groups` (
  `group_id` int NOT NULL,
  `group_name` varchar(100) NOT NULL,
  `group_leader_id` int DEFAULT NULL,
  `description` text
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `workforce_employee_groups`
--

INSERT INTO `workforce_employee_groups` (`group_id`, `group_name`, `group_leader_id`, `description`) VALUES
(1, 'Team Alpha - Structural', 3, 'Core structural team handling concrete and rebar works'),
(2, 'Team Bravo - Steel Works', 8, 'Specialized team for rebar fabrication and welding.');

-- --------------------------------------------------------

--
-- Table structure for table `workforce_employee_skills`
--

CREATE TABLE `workforce_employee_skills` (
  `employee_skill_id` int NOT NULL,
  `employee_id` int NOT NULL,
  `skill_id` int NOT NULL,
  `proficiency_level` enum('Beginner','Intermediate','Advanced','Expert') DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `workforce_employee_skills`
--

INSERT INTO `workforce_employee_skills` (`employee_skill_id`, `employee_id`, `skill_id`, `proficiency_level`) VALUES
(1, 1, 1, 'Expert'),
(2, 3, 2, 'Expert'),
(3, 4, 2, 'Advanced'),
(4, 10, 5, 'Expert');

-- --------------------------------------------------------

--
-- Table structure for table `workforce_group_memberships`
--

CREATE TABLE `workforce_group_memberships` (
  `membership_id` int NOT NULL,
  `employee_id` int NOT NULL,
  `group_id` int NOT NULL,
  `role_in_group` varchar(100) DEFAULT NULL,
  `joined_date` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `workforce_group_memberships`
--

INSERT INTO `workforce_group_memberships` (`membership_id`, `employee_id`, `group_id`, `role_in_group`, `joined_date`) VALUES
(1, 3, 1, 'Team Leader', '2025-01-10'),
(2, 4, 1, 'Mason', '2025-01-10'),
(3, 5, 1, 'Laborer', '2025-01-10'),
(4, 10, 2, 'Lead Welder', '2026-01-06');

-- --------------------------------------------------------

--
-- Table structure for table `workforce_job_titles`
--

CREATE TABLE `workforce_job_titles` (
  `job_title_id` int NOT NULL,
  `title_name` varchar(100) NOT NULL,
  `description` text,
  `department` varchar(50) DEFAULT NULL,
  `default_daily_rate` decimal(10,2) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT '1'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `workforce_job_titles`
--

INSERT INTO `workforce_job_titles` (`job_title_id`, `title_name`, `description`, `department`, `default_daily_rate`, `is_active`) VALUES
(1, 'Project Manager', NULL, 'Operations', 2500.00, 1),
(2, 'Site Engineer', NULL, 'Engineering', 1800.00, 1),
(3, 'Foreman', NULL, 'Construction', 1200.00, 1),
(4, 'Skilled Mason', NULL, 'Construction', 900.00, 1),
(5, 'Laborer', NULL, 'Construction', 600.00, 1),
(6, 'Budget Officer', NULL, 'Finance', 1500.00, 1),
(7, 'Procurement Officer', NULL, 'Logistics', 1500.00, 1),
(8, 'Safety Officer', NULL, 'Safety', 1200.00, 1),
(9, 'Timekeeper', NULL, 'Admin', 800.00, 1),
(10, 'Welder', NULL, 'Construction', 950.00, 1);

-- --------------------------------------------------------

--
-- Table structure for table `workforce_leave_balances`
--

CREATE TABLE `workforce_leave_balances` (
  `balance_id` int NOT NULL,
  `employee_id` int NOT NULL,
  `leave_type_id` int NOT NULL,
  `balance` decimal(5,2) DEFAULT '0.00'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `workforce_leave_balances`
--

INSERT INTO `workforce_leave_balances` (`balance_id`, `employee_id`, `leave_type_id`, `balance`) VALUES
(1, 1, 1, 15.00),
(2, 3, 2, 10.00),
(3, 8, 2, 10.00);

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
  `reason` text,
  `status` enum('Pending','Approved','Rejected') DEFAULT 'Pending',
  `reviewed_by` int DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `workforce_leave_requests`
--

INSERT INTO `workforce_leave_requests` (`request_id`, `employee_id`, `leave_type_id`, `start_date`, `end_date`, `reason`, `status`, `reviewed_by`) VALUES
(1, 4, 2, '2025-01-22', '2025-01-23', 'Flu', 'Approved', NULL),
(2, 8, 2, '2026-02-10', '2026-02-11', 'Flu symptoms', 'Pending', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `workforce_leave_types`
--

CREATE TABLE `workforce_leave_types` (
  `leave_type_id` int NOT NULL,
  `leave_name` varchar(50) NOT NULL,
  `max_days_per_year` int DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `workforce_leave_types`
--

INSERT INTO `workforce_leave_types` (`leave_type_id`, `leave_name`, `max_days_per_year`) VALUES
(1, 'Vacation Leave', 15),
(2, 'Sick Leave', 10),
(3, 'Vacation Leave', 12),
(4, 'Sick Leave', 10),
(5, 'Emergency Leave', 3);

-- --------------------------------------------------------

--
-- Table structure for table `workforce_notifications`
--

CREATE TABLE `workforce_notifications` (
  `notification_id` int NOT NULL,
  `recipient_user_id` int DEFAULT NULL,
  `title` varchar(200) DEFAULT NULL,
  `message` text,
  `is_read` tinyint(1) DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `workforce_notifications`
--

INSERT INTO `workforce_notifications` (`notification_id`, `recipient_user_id`, `title`, `message`, `is_read`, `created_at`) VALUES
(1, 2, 'Budget Approved', 'The mobilization fund for Makati Tower has been approved.', 0, '2026-01-05 12:13:38'),
(2, 6, 'Low Stock Alert', 'Steel bars inventory is below threshold.', 0, '2026-01-05 23:42:43');

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
  `status` enum('Calculated','Approved','Processed') DEFAULT 'Calculated'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `workforce_payroll`
--

INSERT INTO `workforce_payroll` (`payroll_id`, `employee_id`, `period_id`, `hours_worked`, `gross_pay`, `net_pay`, `status`) VALUES
(1, 3, 1, 88.00, 13200.00, 11880.00, 'Processed'),
(2, 10, 2, 8.00, 950.00, 850.00, 'Calculated');

-- --------------------------------------------------------

--
-- Table structure for table `workforce_payroll_config`
--

CREATE TABLE `workforce_payroll_config` (
  `config_id` int NOT NULL,
  `config_name` varchar(100) NOT NULL,
  `config_value` text,
  `config_type` enum('Tax','Contribution','Overtime','Allowance') NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `workforce_payroll_config`
--

INSERT INTO `workforce_payroll_config` (`config_id`, `config_name`, `config_value`, `config_type`) VALUES
(1, 'Tax Rate', '0.10', 'Tax'),
(2, 'SSS Rate', '0.04', 'Contribution'),
(3, 'Tax Deduction', '0.10', 'Tax'),
(4, 'SSS Contribution', '500.00', 'Contribution');

-- --------------------------------------------------------

--
-- Table structure for table `workforce_payroll_periods`
--

CREATE TABLE `workforce_payroll_periods` (
  `period_id` int NOT NULL,
  `period_name` varchar(100) DEFAULT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `pay_date` date DEFAULT NULL,
  `status` enum('Open','Closed','Processed') DEFAULT 'Open'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `workforce_payroll_periods`
--

INSERT INTO `workforce_payroll_periods` (`period_id`, `period_name`, `start_date`, `end_date`, `pay_date`, `status`) VALUES
(1, 'Jan 1-15 2025', '2025-01-01', '2025-01-15', '2025-01-20', 'Closed'),
(2, 'Feb 1-15, 2026', '2026-02-01', '2026-02-15', NULL, 'Open');

-- --------------------------------------------------------

--
-- Table structure for table `workforce_skills`
--

CREATE TABLE `workforce_skills` (
  `skill_id` int NOT NULL,
  `skill_name` varchar(100) NOT NULL,
  `category` varchar(50) DEFAULT NULL,
  `description` text
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `workforce_skills`
--

INSERT INTO `workforce_skills` (`skill_id`, `skill_name`, `category`, `description`) VALUES
(1, 'Project Management', 'Management', NULL),
(2, 'Masonry', 'Construction', NULL),
(3, 'Plumbing', 'Construction', NULL),
(4, 'Blueprint Reading', 'Engineering', NULL),
(5, 'Arc Welding', 'Technical', NULL),
(6, 'Scaffolding Installation', 'Safety', NULL),
(7, 'Heavy Equipment Driving', 'Operations', NULL);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `budget_expenses`
--
ALTER TABLE `budget_expenses`
  ADD PRIMARY KEY (`expense_id`),
  ADD KEY `project_id` (`project_id`),
  ADD KEY `supplier_id` (`supplier_id`),
  ADD KEY `fk_expense_phase` (`phase_id`),
  ADD KEY `created_by` (`created_by`);

--
-- Indexes for table `budget_generated_reports`
--
ALTER TABLE `budget_generated_reports`
  ADD PRIMARY KEY (`report_id`),
  ADD KEY `project_id` (`project_id`);

--
-- Indexes for table `budget_line_items`
--
ALTER TABLE `budget_line_items`
  ADD PRIMARY KEY (`line_item_id`),
  ADD KEY `proposal_id` (`proposal_id`);

--
-- Indexes for table `budget_proposals`
--
ALTER TABLE `budget_proposals`
  ADD PRIMARY KEY (`proposal_id`),
  ADD KEY `project_id` (`project_id`),
  ADD KEY `fk_budget_phase` (`phase_id`),
  ADD KEY `created_by` (`created_by`);

--
-- Indexes for table `icmis_projects`
--
ALTER TABLE `icmis_projects`
  ADD PRIMARY KEY (`project_id`),
  ADD UNIQUE KEY `project_code` (`project_code`),
  ADD KEY `project_manager_id` (`project_manager_id`);

--
-- Indexes for table `icmis_project_phases`
--
ALTER TABLE `icmis_project_phases`
  ADD PRIMARY KEY (`phase_id`),
  ADD KEY `project_id` (`project_id`);

--
-- Indexes for table `icmis_tasks`
--
ALTER TABLE `icmis_tasks`
  ADD PRIMARY KEY (`task_id`),
  ADD KEY `project_id` (`project_id`),
  ADD KEY `phase_id` (`phase_id`),
  ADD KEY `assigned_to_employee_id` (`assigned_to_employee_id`);

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
  ADD PRIMARY KEY (`item_id`);

--
-- Indexes for table `procurement_purchase_orders`
--
ALTER TABLE `procurement_purchase_orders`
  ADD PRIMARY KEY (`po_id`),
  ADD KEY `project_id` (`project_id`),
  ADD KEY `supplier_id` (`supplier_id`),
  ADD KEY `created_by_user_id` (`created_by_user_id`);

--
-- Indexes for table `procurement_purchase_order_items`
--
ALTER TABLE `procurement_purchase_order_items`
  ADD PRIMARY KEY (`po_item_id`),
  ADD KEY `po_id` (`po_id`),
  ADD KEY `inventory_item_id` (`inventory_item_id`);

--
-- Indexes for table `procurement_stock_in`
--
ALTER TABLE `procurement_stock_in`
  ADD PRIMARY KEY (`stock_in_id`),
  ADD KEY `po_id` (`po_id`);

--
-- Indexes for table `procurement_stock_out`
--
ALTER TABLE `procurement_stock_out`
  ADD PRIMARY KEY (`stock_out_id`),
  ADD KEY `item_id` (`item_id`),
  ADD KEY `project_id` (`project_id`);

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
  ADD KEY `employee_id` (`employee_id`),
  ADD KEY `project_id` (`project_id`),
  ADD KEY `phase_id` (`phase_id`);

--
-- Indexes for table `workforce_attendance`
--
ALTER TABLE `workforce_attendance`
  ADD PRIMARY KEY (`attendance_id`),
  ADD KEY `employee_id` (`employee_id`),
  ADD KEY `project_id` (`project_id`);

--
-- Indexes for table `workforce_employees`
--
ALTER TABLE `workforce_employees`
  ADD PRIMARY KEY (`employee_id`),
  ADD UNIQUE KEY `employee_code` (`employee_code`),
  ADD UNIQUE KEY `user_id` (`user_id`),
  ADD KEY `job_title_id` (`job_title_id`);

--
-- Indexes for table `workforce_employee_groups`
--
ALTER TABLE `workforce_employee_groups`
  ADD PRIMARY KEY (`group_id`),
  ADD KEY `group_leader_id` (`group_leader_id`);

--
-- Indexes for table `workforce_employee_skills`
--
ALTER TABLE `workforce_employee_skills`
  ADD PRIMARY KEY (`employee_skill_id`),
  ADD KEY `employee_id` (`employee_id`),
  ADD KEY `skill_id` (`skill_id`);

--
-- Indexes for table `workforce_group_memberships`
--
ALTER TABLE `workforce_group_memberships`
  ADD PRIMARY KEY (`membership_id`),
  ADD KEY `employee_id` (`employee_id`),
  ADD KEY `group_id` (`group_id`);

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
  ADD KEY `employee_id` (`employee_id`),
  ADD KEY `leave_type_id` (`leave_type_id`);

--
-- Indexes for table `workforce_leave_requests`
--
ALTER TABLE `workforce_leave_requests`
  ADD PRIMARY KEY (`request_id`),
  ADD KEY `employee_id` (`employee_id`),
  ADD KEY `leave_type_id` (`leave_type_id`),
  ADD KEY `reviewed_by` (`reviewed_by`);

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
  ADD KEY `recipient_user_id` (`recipient_user_id`);

--
-- Indexes for table `workforce_payroll`
--
ALTER TABLE `workforce_payroll`
  ADD PRIMARY KEY (`payroll_id`),
  ADD KEY `employee_id` (`employee_id`),
  ADD KEY `period_id` (`period_id`);

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
  MODIFY `expense_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `budget_generated_reports`
--
ALTER TABLE `budget_generated_reports`
  MODIFY `report_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `budget_line_items`
--
ALTER TABLE `budget_line_items`
  MODIFY `line_item_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `budget_proposals`
--
ALTER TABLE `budget_proposals`
  MODIFY `proposal_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `icmis_projects`
--
ALTER TABLE `icmis_projects`
  MODIFY `project_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `icmis_project_phases`
--
ALTER TABLE `icmis_project_phases`
  MODIFY `phase_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `icmis_tasks`
--
ALTER TABLE `icmis_tasks`
  MODIFY `task_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `icmis_users`
--
ALTER TABLE `icmis_users`
  MODIFY `user_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `procurement_inventory`
--
ALTER TABLE `procurement_inventory`
  MODIFY `item_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `procurement_purchase_orders`
--
ALTER TABLE `procurement_purchase_orders`
  MODIFY `po_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `procurement_purchase_order_items`
--
ALTER TABLE `procurement_purchase_order_items`
  MODIFY `po_item_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `procurement_stock_in`
--
ALTER TABLE `procurement_stock_in`
  MODIFY `stock_in_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `procurement_stock_out`
--
ALTER TABLE `procurement_stock_out`
  MODIFY `stock_out_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `procurement_suppliers`
--
ALTER TABLE `procurement_suppliers`
  MODIFY `supplier_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `workforce_assignments`
--
ALTER TABLE `workforce_assignments`
  MODIFY `assignment_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `workforce_attendance`
--
ALTER TABLE `workforce_attendance`
  MODIFY `attendance_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `workforce_employees`
--
ALTER TABLE `workforce_employees`
  MODIFY `employee_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `workforce_employee_groups`
--
ALTER TABLE `workforce_employee_groups`
  MODIFY `group_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `workforce_employee_skills`
--
ALTER TABLE `workforce_employee_skills`
  MODIFY `employee_skill_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `workforce_group_memberships`
--
ALTER TABLE `workforce_group_memberships`
  MODIFY `membership_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `workforce_job_titles`
--
ALTER TABLE `workforce_job_titles`
  MODIFY `job_title_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `workforce_leave_balances`
--
ALTER TABLE `workforce_leave_balances`
  MODIFY `balance_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `workforce_leave_requests`
--
ALTER TABLE `workforce_leave_requests`
  MODIFY `request_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `workforce_leave_types`
--
ALTER TABLE `workforce_leave_types`
  MODIFY `leave_type_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `workforce_notifications`
--
ALTER TABLE `workforce_notifications`
  MODIFY `notification_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `workforce_payroll`
--
ALTER TABLE `workforce_payroll`
  MODIFY `payroll_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `workforce_payroll_config`
--
ALTER TABLE `workforce_payroll_config`
  MODIFY `config_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `workforce_payroll_periods`
--
ALTER TABLE `workforce_payroll_periods`
  MODIFY `period_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `workforce_skills`
--
ALTER TABLE `workforce_skills`
  MODIFY `skill_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `budget_expenses`
--
ALTER TABLE `budget_expenses`
  ADD CONSTRAINT `budget_expenses_ibfk_1` FOREIGN KEY (`project_id`) REFERENCES `icmis_projects` (`project_id`),
  ADD CONSTRAINT `budget_expenses_ibfk_2` FOREIGN KEY (`supplier_id`) REFERENCES `procurement_suppliers` (`supplier_id`),
  ADD CONSTRAINT `budget_expenses_ibfk_3` FOREIGN KEY (`created_by`) REFERENCES `icmis_users` (`user_id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_expense_phase` FOREIGN KEY (`phase_id`) REFERENCES `icmis_project_phases` (`phase_id`) ON DELETE SET NULL;

--
-- Constraints for table `budget_generated_reports`
--
ALTER TABLE `budget_generated_reports`
  ADD CONSTRAINT `budget_generated_reports_ibfk_1` FOREIGN KEY (`project_id`) REFERENCES `icmis_projects` (`project_id`);

--
-- Constraints for table `budget_line_items`
--
ALTER TABLE `budget_line_items`
  ADD CONSTRAINT `budget_line_items_ibfk_1` FOREIGN KEY (`proposal_id`) REFERENCES `budget_proposals` (`proposal_id`) ON DELETE CASCADE;

--
-- Constraints for table `budget_proposals`
--
ALTER TABLE `budget_proposals`
  ADD CONSTRAINT `budget_proposals_ibfk_1` FOREIGN KEY (`project_id`) REFERENCES `icmis_projects` (`project_id`),
  ADD CONSTRAINT `budget_proposals_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `icmis_users` (`user_id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_budget_phase` FOREIGN KEY (`phase_id`) REFERENCES `icmis_project_phases` (`phase_id`) ON DELETE SET NULL;

--
-- Constraints for table `icmis_projects`
--
ALTER TABLE `icmis_projects`
  ADD CONSTRAINT `icmis_projects_ibfk_1` FOREIGN KEY (`project_manager_id`) REFERENCES `workforce_employees` (`employee_id`) ON DELETE SET NULL;

--
-- Constraints for table `icmis_project_phases`
--
ALTER TABLE `icmis_project_phases`
  ADD CONSTRAINT `icmis_project_phases_ibfk_1` FOREIGN KEY (`project_id`) REFERENCES `icmis_projects` (`project_id`) ON DELETE CASCADE;

--
-- Constraints for table `icmis_tasks`
--
ALTER TABLE `icmis_tasks`
  ADD CONSTRAINT `icmis_tasks_ibfk_1` FOREIGN KEY (`project_id`) REFERENCES `icmis_projects` (`project_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `icmis_tasks_ibfk_2` FOREIGN KEY (`phase_id`) REFERENCES `icmis_project_phases` (`phase_id`),
  ADD CONSTRAINT `icmis_tasks_ibfk_3` FOREIGN KEY (`assigned_to_employee_id`) REFERENCES `workforce_employees` (`employee_id`);

--
-- Constraints for table `procurement_purchase_orders`
--
ALTER TABLE `procurement_purchase_orders`
  ADD CONSTRAINT `procurement_purchase_orders_ibfk_1` FOREIGN KEY (`project_id`) REFERENCES `icmis_projects` (`project_id`),
  ADD CONSTRAINT `procurement_purchase_orders_ibfk_2` FOREIGN KEY (`supplier_id`) REFERENCES `procurement_suppliers` (`supplier_id`),
  ADD CONSTRAINT `procurement_purchase_orders_ibfk_3` FOREIGN KEY (`created_by_user_id`) REFERENCES `icmis_users` (`user_id`);

--
-- Constraints for table `procurement_purchase_order_items`
--
ALTER TABLE `procurement_purchase_order_items`
  ADD CONSTRAINT `procurement_purchase_order_items_ibfk_1` FOREIGN KEY (`po_id`) REFERENCES `procurement_purchase_orders` (`po_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `procurement_purchase_order_items_ibfk_2` FOREIGN KEY (`inventory_item_id`) REFERENCES `procurement_inventory` (`item_id`);

--
-- Constraints for table `procurement_stock_in`
--
ALTER TABLE `procurement_stock_in`
  ADD CONSTRAINT `procurement_stock_in_ibfk_1` FOREIGN KEY (`po_id`) REFERENCES `procurement_purchase_orders` (`po_id`);

--
-- Constraints for table `procurement_stock_out`
--
ALTER TABLE `procurement_stock_out`
  ADD CONSTRAINT `procurement_stock_out_ibfk_1` FOREIGN KEY (`item_id`) REFERENCES `procurement_inventory` (`item_id`),
  ADD CONSTRAINT `procurement_stock_out_ibfk_2` FOREIGN KEY (`project_id`) REFERENCES `icmis_projects` (`project_id`);

--
-- Constraints for table `workforce_assignments`
--
ALTER TABLE `workforce_assignments`
  ADD CONSTRAINT `workforce_assignments_ibfk_1` FOREIGN KEY (`employee_id`) REFERENCES `workforce_employees` (`employee_id`),
  ADD CONSTRAINT `workforce_assignments_ibfk_2` FOREIGN KEY (`project_id`) REFERENCES `icmis_projects` (`project_id`),
  ADD CONSTRAINT `workforce_assignments_ibfk_3` FOREIGN KEY (`phase_id`) REFERENCES `icmis_project_phases` (`phase_id`);

--
-- Constraints for table `workforce_attendance`
--
ALTER TABLE `workforce_attendance`
  ADD CONSTRAINT `workforce_attendance_ibfk_1` FOREIGN KEY (`employee_id`) REFERENCES `workforce_employees` (`employee_id`),
  ADD CONSTRAINT `workforce_attendance_ibfk_2` FOREIGN KEY (`project_id`) REFERENCES `icmis_projects` (`project_id`);

--
-- Constraints for table `workforce_employees`
--
ALTER TABLE `workforce_employees`
  ADD CONSTRAINT `workforce_employees_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `icmis_users` (`user_id`) ON DELETE SET NULL,
  ADD CONSTRAINT `workforce_employees_ibfk_2` FOREIGN KEY (`job_title_id`) REFERENCES `workforce_job_titles` (`job_title_id`);

--
-- Constraints for table `workforce_employee_groups`
--
ALTER TABLE `workforce_employee_groups`
  ADD CONSTRAINT `workforce_employee_groups_ibfk_1` FOREIGN KEY (`group_leader_id`) REFERENCES `workforce_employees` (`employee_id`);

--
-- Constraints for table `workforce_employee_skills`
--
ALTER TABLE `workforce_employee_skills`
  ADD CONSTRAINT `workforce_employee_skills_ibfk_1` FOREIGN KEY (`employee_id`) REFERENCES `workforce_employees` (`employee_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `workforce_employee_skills_ibfk_2` FOREIGN KEY (`skill_id`) REFERENCES `workforce_skills` (`skill_id`) ON DELETE CASCADE;

--
-- Constraints for table `workforce_group_memberships`
--
ALTER TABLE `workforce_group_memberships`
  ADD CONSTRAINT `workforce_group_memberships_ibfk_1` FOREIGN KEY (`employee_id`) REFERENCES `workforce_employees` (`employee_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `workforce_group_memberships_ibfk_2` FOREIGN KEY (`group_id`) REFERENCES `workforce_employee_groups` (`group_id`) ON DELETE CASCADE;

--
-- Constraints for table `workforce_leave_balances`
--
ALTER TABLE `workforce_leave_balances`
  ADD CONSTRAINT `workforce_leave_balances_ibfk_1` FOREIGN KEY (`employee_id`) REFERENCES `workforce_employees` (`employee_id`),
  ADD CONSTRAINT `workforce_leave_balances_ibfk_2` FOREIGN KEY (`leave_type_id`) REFERENCES `workforce_leave_types` (`leave_type_id`);

--
-- Constraints for table `workforce_leave_requests`
--
ALTER TABLE `workforce_leave_requests`
  ADD CONSTRAINT `workforce_leave_requests_ibfk_1` FOREIGN KEY (`employee_id`) REFERENCES `workforce_employees` (`employee_id`),
  ADD CONSTRAINT `workforce_leave_requests_ibfk_2` FOREIGN KEY (`leave_type_id`) REFERENCES `workforce_leave_types` (`leave_type_id`),
  ADD CONSTRAINT `workforce_leave_requests_ibfk_3` FOREIGN KEY (`reviewed_by`) REFERENCES `workforce_employees` (`employee_id`);

--
-- Constraints for table `workforce_notifications`
--
ALTER TABLE `workforce_notifications`
  ADD CONSTRAINT `workforce_notifications_ibfk_1` FOREIGN KEY (`recipient_user_id`) REFERENCES `icmis_users` (`user_id`);

--
-- Constraints for table `workforce_payroll`
--
ALTER TABLE `workforce_payroll`
  ADD CONSTRAINT `workforce_payroll_ibfk_1` FOREIGN KEY (`employee_id`) REFERENCES `workforce_employees` (`employee_id`),
  ADD CONSTRAINT `workforce_payroll_ibfk_2` FOREIGN KEY (`period_id`) REFERENCES `workforce_payroll_periods` (`period_id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
