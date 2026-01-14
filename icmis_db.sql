-- phpMyAdmin SQL Dump
-- version 6.0.0-dev+20250914.f72491a1c0
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Jan 14, 2026 at 01:42 AM
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
CREATE DATABASE IF NOT EXISTS `icmis_db` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci;
USE `icmis_db`;

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

-- --------------------------------------------------------

--
-- Table structure for table `icmis_audit_logs`
--

CREATE TABLE `icmis_audit_logs` (
  `log_id` int UNSIGNED NOT NULL,
  `user_id` int DEFAULT NULL,
  `user_name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'System',
  `action` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `module` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `details` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `record_id` int DEFAULT NULL,
  `ip_address` varchar(45) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `icmis_audit_logs`
--

INSERT INTO `icmis_audit_logs` (`log_id`, `user_id`, `user_name`, `action`, `module`, `details`, `record_id`, `ip_address`, `user_agent`, `created_at`) VALUES
(1, 1, 'John Doe', 'LOGIN', 'Auth', 'User logged in successfully', NULL, '0', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-13 07:24:09'),
(2, 1, 'John Doe', 'CREATE', 'Project', 'Created new project: Vista Verde Residential Complex (PRJ-2026-001)', 1, '0', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-13 07:27:50');

-- --------------------------------------------------------

--
-- Table structure for table `icmis_generated_reports`
--

CREATE TABLE `icmis_generated_reports` (
  `id` int NOT NULL,
  `report_name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `category` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `project_id` int DEFAULT NULL,
  `project_name` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `generated_by` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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
(1, 'PRJ-2026-001', 'Vista Verde Residential Complex', 'Construction of a 3-storey residential building with 12 units, including parking and perimeter fencing.', 'Brgy. Estefania, Bacolod City', 'Planning', '2026-01-13', '2026-11-13', 0.00, 1, 10000000.00);

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
  `duration` int DEFAULT '0',
  `status` enum('Not Started','In Progress','Completed') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'Not Started'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `icmis_project_phases`
--

INSERT INTO `icmis_project_phases` (`phase_id`, `project_id`, `phase_name`, `description`, `start_date`, `end_date`, `duration`, `status`) VALUES
(1, 1, 'Phase 1: Mobilization', 'Clearing, fencing, and temp facilities.', '2026-01-13', '2026-01-20', 7, 'Not Started');

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

--
-- Dumping data for table `icmis_tasks`
--

INSERT INTO `icmis_tasks` (`task_id`, `project_id`, `phase_id`, `task_name`, `description`, `assigned_to_employee_id`, `start_date`, `due_date`, `status`, `priority`) VALUES
(1, 1, 1, 'Prep', 'Clearing, fencing, and temp facilities.', 22, '2026-01-13', '2026-01-20', 'Not Started', 'High');

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
(1, 'John Doe', 'john.doe@icmis.com', '$2y$12$OJh7QI0xBUojAMT1oFnNd.fktGOwvoNN8G1wftJVXxkS/4AtYy3Yy', 'Admin', NULL, '2026-01-13 07:24:05');

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
(1, 40, 2, '2026-01-11', '08:00:00', '17:00:00', 'Present', ''),
(2, 34, 2, '2026-01-11', '08:00:00', '17:00:00', 'Present', ''),
(3, 16, 2, '2026-01-11', '08:00:00', '17:00:00', 'Present', ''),
(4, 46, 2, '2026-01-11', '08:00:00', '17:00:00', 'Present', ''),
(5, 51, 2, '2026-01-11', '08:00:00', '17:00:00', 'Present', ''),
(6, 39, 2, '2026-01-11', '08:00:00', '17:00:00', 'Present', ''),
(7, 9, 2, '2026-01-11', '08:00:00', '17:00:00', 'Present', ''),
(8, 38, 2, '2026-01-11', '08:00:00', '17:00:00', 'Present', ''),
(9, 33, 2, '2026-01-11', '08:00:00', '17:00:00', 'Present', ''),
(10, 1, 2, '2026-01-11', '08:00:00', '17:00:00', 'Present', ''),
(11, 2, 2, '2026-01-11', '08:00:00', '17:00:00', 'Present', ''),
(12, 3, 2, '2026-01-11', '08:00:00', '17:00:00', 'Present', ''),
(13, 4, 2, '2026-01-11', '08:00:00', '17:00:00', 'Present', ''),
(14, 5, 2, '2026-01-11', '08:00:00', '17:00:00', 'Present', ''),
(15, 6, 2, '2026-01-11', '08:00:00', '17:00:00', 'Present', ''),
(16, 7, 2, '2026-01-11', '08:00:00', '17:00:00', 'Present', ''),
(17, 8, 2, '2026-01-11', '08:00:00', '17:00:00', 'Present', ''),
(18, 10, 2, '2026-01-11', '08:00:00', '17:00:00', 'Present', ''),
(19, 11, 2, '2026-01-11', '08:00:00', '17:00:00', 'Present', ''),
(20, 12, 2, '2026-01-11', '08:00:00', '17:00:00', 'Present', ''),
(21, 13, 2, '2026-01-11', '08:00:00', '17:00:00', 'Present', ''),
(22, 14, 2, '2026-01-11', '08:00:00', '17:00:00', 'Present', ''),
(23, 15, 2, '2026-01-11', '08:00:00', '17:00:00', 'Present', ''),
(24, 17, 2, '2026-01-11', '08:00:00', '17:00:00', 'Present', ''),
(25, 18, 2, '2026-01-11', '08:00:00', '17:00:00', 'Present', ''),
(26, 19, 2, '2026-01-11', '08:00:00', '17:00:00', 'Present', ''),
(27, 20, 2, '2026-01-11', '08:00:00', '17:00:00', 'Present', ''),
(28, 21, 2, '2026-01-11', '08:00:00', '17:00:00', 'Present', ''),
(29, 22, 2, '2026-01-11', '08:00:00', '17:00:00', 'Present', ''),
(30, 23, 2, '2026-01-11', '08:00:00', '17:00:00', 'Present', ''),
(31, 24, 2, '2026-01-11', '08:00:00', '17:00:00', 'Present', ''),
(32, 25, 2, '2026-01-11', '08:00:00', '17:00:00', 'Present', ''),
(33, 26, 2, '2026-01-11', '08:00:00', '17:00:00', 'Present', ''),
(34, 27, 2, '2026-01-11', '08:00:00', '17:00:00', 'Present', ''),
(35, 28, 2, '2026-01-11', '08:00:00', '17:00:00', 'Present', ''),
(36, 29, 2, '2026-01-11', '08:00:00', '17:00:00', 'Present', ''),
(37, 30, 2, '2026-01-11', '08:00:00', '17:00:00', 'Present', ''),
(38, 31, 2, '2026-01-11', '08:00:00', '17:00:00', 'Present', ''),
(39, 32, 2, '2026-01-11', '08:00:00', '17:00:00', 'Present', ''),
(40, 35, 2, '2026-01-11', '08:00:00', '17:00:00', 'Present', ''),
(41, 36, 2, '2026-01-11', '08:00:00', '17:00:00', 'Present', ''),
(42, 37, 2, '2026-01-11', '08:00:00', '17:00:00', 'Present', ''),
(43, 41, 2, '2026-01-11', '08:00:00', '17:00:00', 'Present', ''),
(44, 42, 2, '2026-01-11', '08:00:00', '17:00:00', 'Present', ''),
(45, 43, 2, '2026-01-11', '08:00:00', '17:00:00', 'Present', ''),
(46, 44, 2, '2026-01-11', '08:00:00', '17:00:00', 'Present', ''),
(47, 45, 2, '2026-01-11', '08:00:00', '17:00:00', 'Present', ''),
(48, 47, 2, '2026-01-11', '08:00:00', '17:00:00', 'Present', ''),
(49, 48, 2, '2026-01-11', '08:00:00', '17:00:00', 'Present', ''),
(50, 49, 2, '2026-01-11', '08:00:00', '17:00:00', 'Present', ''),
(51, 50, 2, '2026-01-11', '08:00:00', '17:00:00', 'Present', '');

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
(1, 'EMP-2026-001', NULL, 1, 'Regular', 'Monthly', 2500.00, 65000.00, NULL, NULL, NULL, NULL, NULL, NULL, 'Eduardo', 'Castillo', NULL, 'Male', NULL, 'eduardo.c@const.com', '0917-111-0001', 'Makati City', 'Active', '2023-01-10'),
(2, 'EMP-2026-002', NULL, 1, 'Regular', 'Monthly', 2500.00, 65000.00, NULL, NULL, NULL, NULL, NULL, NULL, 'Theresa', 'Mendoza', NULL, 'Female', NULL, 'theresa.m@const.com', '0917-111-0002', 'Taguig City', 'Active', '2023-02-15'),
(3, 'EMP-2026-003', NULL, 2, 'Regular', 'Monthly', 1800.00, 46800.00, NULL, NULL, NULL, NULL, 1, NULL, 'Marlon', 'Santos', NULL, 'Male', NULL, 'marlon.s@const.com', '0917-222-0003', 'Quezon City', 'Active', '2023-03-01'),
(4, 'EMP-2026-004', NULL, 2, 'Regular', 'Monthly', 1800.00, 46800.00, NULL, NULL, NULL, NULL, 2, NULL, 'Jenny', 'Reyes', NULL, 'Female', NULL, 'jenny.r@const.com', '0917-222-0004', 'Pasig City', 'Active', '2023-03-05'),
(5, 'EMP-2026-005', NULL, 3, 'Regular', 'Monthly', 2200.00, 57200.00, NULL, NULL, NULL, NULL, 1, NULL, 'Carlo', 'Dizon', NULL, 'Male', NULL, 'carlo.d@const.com', '0917-222-0005', 'Manila', 'Active', '2023-03-10'),
(6, 'EMP-2026-006', NULL, 3, 'Regular', 'Monthly', 2200.00, 57200.00, NULL, NULL, NULL, NULL, 2, NULL, 'Grace', 'Lim', NULL, 'Female', NULL, 'grace.l@const.com', '0917-222-0006', 'San Juan', 'Active', '2023-03-12'),
(7, 'EMP-2026-007', NULL, 4, 'Regular', 'Monthly', 2500.00, 65000.00, NULL, NULL, NULL, NULL, 1, NULL, 'Archie', 'Valdez', NULL, 'Male', NULL, 'archie.v@const.com', '0917-222-0007', 'Makati', 'Active', '2023-02-20'),
(8, 'EMP-2026-008', NULL, 4, 'Regular', 'Monthly', 2500.00, 65000.00, NULL, NULL, NULL, NULL, 2, NULL, 'Melissa', 'Co', NULL, 'Female', NULL, 'melissa.c@const.com', '0917-222-0008', 'BGC', 'Active', '2023-02-25'),
(9, 'EMP-2026-009', NULL, 5, 'Project-based', 'Monthly', 1500.00, 39000.00, NULL, NULL, NULL, NULL, 3, NULL, 'Ramon', 'Bautista', NULL, 'Male', NULL, 'ramon.b@const.com', '0917-333-0009', 'Marikina', 'Active', '2023-04-01'),
(10, 'EMP-2026-010', NULL, 5, 'Project-based', 'Monthly', 1500.00, 39000.00, NULL, NULL, NULL, NULL, 4, NULL, 'Sarah', 'Geronimo', NULL, 'Female', NULL, 'sarah.g@const.com', '0917-333-0010', 'Cainta', 'Active', '2023-04-05'),
(11, 'EMP-2026-011', NULL, 6, 'Regular', 'Monthly', 1800.00, 46800.00, NULL, NULL, NULL, NULL, 1, NULL, 'Ken', 'Go', NULL, 'Male', NULL, 'ken.g@const.com', '0917-444-0011', 'Manila', 'Active', '2023-01-20'),
(12, 'EMP-2026-012', NULL, 6, 'Regular', 'Monthly', 1800.00, 46800.00, NULL, NULL, NULL, NULL, 1, NULL, 'Barbie', 'Imperial', NULL, 'Female', NULL, 'barbie.i@const.com', '0917-444-0012', 'QC', 'Active', '2023-01-22'),
(13, 'EMP-2026-013', NULL, 7, 'Regular', 'Monthly', 1800.00, 46800.00, NULL, NULL, NULL, NULL, 1, NULL, 'Luis', 'Manzano', NULL, 'Male', NULL, 'luis.m@const.com', '0917-444-0013', 'Pasay', 'Active', '2023-02-01'),
(14, 'EMP-2026-014', NULL, 7, 'Regular', 'Monthly', 1800.00, 46800.00, NULL, NULL, NULL, NULL, 2, NULL, 'Jessy', 'Mendiola', NULL, 'Female', NULL, 'jessy.m@const.com', '0917-444-0014', 'Pasay', 'Active', '2023-02-05'),
(15, 'EMP-2026-015', NULL, 8, 'Project-based', 'Monthly', 1800.00, 46800.00, NULL, NULL, NULL, NULL, 3, NULL, 'John', 'Lloyd', NULL, 'Male', NULL, 'john.l@const.com', '0917-555-0015', 'Cebu', 'Active', '2023-03-15'),
(16, 'EMP-2026-016', NULL, 8, 'Project-based', 'Monthly', 1800.00, 46800.00, NULL, NULL, NULL, NULL, 4, NULL, 'Bea', 'Alonzo', NULL, 'Female', NULL, 'bea.a@const.com', '0917-555-0016', 'Cebu', 'Active', '2023-03-18'),
(17, 'EMP-2026-017', NULL, 9, 'Probationary', 'Daily', 800.00, 20800.00, NULL, NULL, NULL, NULL, 3, NULL, 'Vhong', 'Navarro', NULL, 'Male', NULL, 'vhong.n@const.com', '0917-666-0017', 'Cavite', 'Active', '2024-01-05'),
(18, 'EMP-2026-018', NULL, 9, 'Probationary', 'Daily', 800.00, 20800.00, NULL, NULL, NULL, NULL, 4, NULL, 'Anne', 'Curtis', NULL, 'Female', NULL, 'anne.c@const.com', '0917-666-0018', 'Cavite', 'Active', '2024-01-08'),
(19, 'EMP-2026-019', NULL, 10, 'Contractual', 'Daily', 700.00, 18200.00, NULL, NULL, NULL, NULL, 3, NULL, 'Jhong', 'Hilario', NULL, 'Male', NULL, 'jhong.h@const.com', '0917-777-0019', 'Rizal', 'Active', '2024-01-10'),
(20, 'EMP-2026-020', NULL, 10, 'Contractual', 'Daily', 700.00, 18200.00, NULL, NULL, NULL, NULL, 4, NULL, 'Karylle', 'Tatlonghari', NULL, 'Female', NULL, 'karylle.t@const.com', '0917-777-0020', 'Rizal', 'Active', '2024-01-12'),
(21, 'EMP-2026-021', NULL, 11, 'Project-based', 'Daily', 1200.00, 31200.00, NULL, NULL, NULL, NULL, 3, NULL, 'Binoe', 'Padilla', NULL, 'Male', NULL, NULL, '0919-888-0021', 'Tondo', 'Active', '2023-02-01'),
(22, 'EMP-2026-022', NULL, 11, 'Project-based', 'Daily', 1200.00, 31200.00, NULL, NULL, NULL, NULL, 4, NULL, 'Coco', 'Martin', NULL, 'Male', NULL, NULL, '0919-888-0022', 'Quiapo', 'Active', '2023-02-05'),
(23, 'EMP-2026-023', NULL, 12, 'Contractual', 'Daily', 900.00, 23400.00, NULL, NULL, NULL, NULL, 21, NULL, 'Lito', 'Lapid', NULL, 'Male', NULL, NULL, '0919-999-0023', 'Pampanga', 'Active', '2023-05-01'),
(24, 'EMP-2026-024', NULL, 12, 'Contractual', 'Daily', 900.00, 23400.00, NULL, NULL, NULL, NULL, 22, NULL, 'Mark', 'Lapid', NULL, 'Male', NULL, NULL, '0919-999-0024', 'Pampanga', 'Active', '2023-05-02'),
(25, 'EMP-2026-025', NULL, 13, 'Contractual', 'Daily', 750.00, 19500.00, NULL, NULL, NULL, NULL, 21, NULL, 'Joko', 'Diaz', NULL, 'Male', NULL, NULL, '0919-000-0025', 'Laguna', 'Active', '2023-06-01'),
(26, 'EMP-2026-026', NULL, 13, 'Contractual', 'Daily', 750.00, 19500.00, NULL, NULL, NULL, NULL, 22, NULL, 'Kiko', 'Estrada', NULL, 'Male', NULL, NULL, '0919-000-0026', 'Laguna', 'Active', '2023-06-05'),
(27, 'EMP-2026-027', NULL, 14, 'Contractual', 'Daily', 700.00, 18200.00, NULL, NULL, NULL, NULL, 21, NULL, 'Monsour', 'Del Rosario', NULL, 'Male', NULL, NULL, '0919-000-0027', 'Batangas', 'Active', '2023-06-10'),
(28, 'EMP-2026-028', NULL, 14, 'Contractual', 'Daily', 700.00, 18200.00, NULL, NULL, NULL, NULL, 22, NULL, 'Richard', 'Gomez', NULL, 'Male', NULL, NULL, '0919-000-0028', 'Ormoc', 'Active', '2023-06-12'),
(29, 'EMP-2026-029', NULL, 15, 'Contractual', 'Daily', 850.00, 22100.00, NULL, NULL, NULL, NULL, 21, NULL, 'Gardo', 'Versoza', NULL, 'Male', NULL, NULL, '0919-000-0029', 'Bulacan', 'Active', '2023-06-15'),
(30, 'EMP-2026-030', NULL, 15, 'Contractual', 'Daily', 850.00, 22100.00, NULL, NULL, NULL, NULL, 22, NULL, 'Raymart', 'Santiago', NULL, 'Male', NULL, NULL, '0919-000-0030', 'Caloocan', 'Active', '2023-06-18'),
(31, 'EMP-2026-031', NULL, 16, 'Contractual', 'Daily', 1000.00, 26000.00, NULL, NULL, NULL, NULL, 21, NULL, 'Benjie', 'Paras', NULL, 'Male', NULL, NULL, '0919-000-0031', 'Pasig', 'Active', '2023-07-01'),
(32, 'EMP-2026-032', NULL, 16, 'Contractual', 'Daily', 1000.00, 26000.00, NULL, NULL, NULL, NULL, 22, NULL, 'Alvin', 'Patrimonio', NULL, 'Male', NULL, NULL, '0919-000-0032', 'Pasig', 'Active', '2023-07-05'),
(33, 'EMP-2026-033', NULL, 17, 'Project-based', 'Daily', 1100.00, 28600.00, NULL, NULL, NULL, NULL, 3, NULL, 'Bitoy', 'Bunagan', NULL, 'Male', NULL, 'bitoy.b@gmail.com', '0919-111-0033', 'QC', 'Active', '2023-04-10'),
(34, 'EMP-2026-034', NULL, 17, 'Project-based', 'Daily', 1100.00, 28600.00, NULL, NULL, NULL, NULL, 4, NULL, 'Ogie', 'Alcasid', NULL, 'Male', NULL, 'ogie.a@gmail.com', '0919-111-0034', 'QC', 'Active', '2023-04-12'),
(35, 'EMP-2026-035', NULL, 18, 'Contractual', 'Daily', 800.00, 20800.00, NULL, NULL, NULL, NULL, 33, NULL, 'Teddy', 'Corpuz', NULL, 'Male', NULL, NULL, '0919-111-0035', 'QC', 'Active', '2023-04-15'),
(36, 'EMP-2026-036', NULL, 18, 'Contractual', 'Daily', 800.00, 20800.00, NULL, NULL, NULL, NULL, 34, NULL, 'Jugs', 'Jugueta', NULL, 'Male', NULL, NULL, '0919-111-0036', 'QC', 'Active', '2023-04-18'),
(37, 'EMP-2026-037', NULL, 19, 'Project-based', 'Daily', 1100.00, 28600.00, NULL, NULL, NULL, NULL, 3, NULL, 'Jose', 'Manalo', NULL, 'Male', NULL, NULL, '0919-222-0037', 'Bulacan', 'Active', '2023-05-01'),
(38, 'EMP-2026-038', NULL, 19, 'Project-based', 'Daily', 1100.00, 28600.00, NULL, NULL, NULL, NULL, 4, NULL, 'Wally', 'Bayola', NULL, 'Male', NULL, NULL, '0919-222-0038', 'Bulacan', 'Active', '2023-05-05'),
(39, 'EMP-2026-039', NULL, 20, 'Contractual', 'Daily', 750.00, 19500.00, NULL, NULL, NULL, NULL, 37, NULL, 'Paolo', 'Ballesteros', NULL, 'Male', NULL, NULL, '0919-222-0039', 'Antipolo', 'Active', '2023-05-10'),
(40, 'EMP-2026-040', NULL, 20, 'Contractual', 'Daily', 750.00, 19500.00, NULL, NULL, NULL, NULL, 38, NULL, 'Ryan', 'Agoncillo', NULL, 'Male', NULL, NULL, '0919-222-0040', 'Alabang', 'Active', '2023-05-12'),
(41, 'EMP-2026-041', NULL, 21, 'Contractual', 'Daily', 900.00, 23400.00, NULL, NULL, NULL, NULL, 3, NULL, 'Dingdong', 'Dantes', NULL, 'Male', NULL, NULL, '0919-333-0041', 'QC', 'Active', '2023-08-01'),
(42, 'EMP-2026-042', NULL, 21, 'Contractual', 'Daily', 900.00, 23400.00, NULL, NULL, NULL, NULL, 4, NULL, 'Dennis', 'Trillo', NULL, 'Male', NULL, NULL, '0919-333-0042', 'QC', 'Active', '2023-08-05'),
(43, 'EMP-2026-043', NULL, 22, 'Contractual', 'Daily', 900.00, 23400.00, NULL, NULL, NULL, NULL, 21, NULL, 'Derek', 'Ramsay', NULL, 'Male', NULL, NULL, '0919-444-0043', 'Alabang', 'Active', '2023-09-01'),
(44, 'EMP-2026-044', NULL, 22, 'Contractual', 'Daily', 900.00, 23400.00, NULL, NULL, NULL, NULL, 22, NULL, 'Piolo', 'Pascual', NULL, 'Male', NULL, NULL, '0919-444-0044', 'Batangas', 'Active', '2023-09-05'),
(45, 'EMP-2026-045', NULL, 23, 'Contractual', 'Daily', 700.00, 18200.00, NULL, NULL, NULL, NULL, 21, NULL, 'Sam', 'Milby', NULL, 'Male', NULL, NULL, '0919-444-0045', 'Bulacan', 'Active', '2023-09-10'),
(46, 'EMP-2026-046', NULL, 23, 'Contractual', 'Daily', 700.00, 18200.00, NULL, NULL, NULL, NULL, 22, NULL, 'Gerald', 'Anderson', NULL, 'Male', NULL, NULL, '0919-444-0046', 'GenSan', 'Active', '2023-09-12'),
(47, 'EMP-2026-047', NULL, 24, 'Contractual', 'Daily', 700.00, 18200.00, NULL, NULL, NULL, NULL, 21, NULL, 'Enchong', 'Dee', NULL, 'Male', NULL, NULL, '0919-555-0047', 'Naga', 'Active', '2023-10-01'),
(48, 'EMP-2026-048', NULL, 24, 'Contractual', 'Daily', 700.00, 18200.00, NULL, NULL, NULL, NULL, 22, NULL, 'Xian', 'Lim', NULL, 'Male', NULL, NULL, '0919-555-0048', 'Mandaluyong', 'Active', '2023-10-05'),
(49, 'EMP-2026-049', NULL, 25, 'Contractual', 'Daily', 850.00, 22100.00, NULL, NULL, NULL, NULL, 21, NULL, 'Joshua', 'Garcia', NULL, 'Male', NULL, NULL, '0919-666-0049', 'Batangas', 'Active', '2023-11-01'),
(50, 'EMP-2026-050', NULL, 25, 'Contractual', 'Daily', 850.00, 22100.00, NULL, NULL, NULL, NULL, 22, NULL, 'Daniel', 'Padilla', NULL, 'Male', NULL, NULL, '0919-666-0050', 'QC', 'Active', '2023-11-05'),
(51, 'EMP-2026-051', NULL, 24, 'Full-time', 'Monthly', 700.00, 18200.00, 'Bank of the Philippine Islands (BPI)', '1231231231231', '123123123', '+63 1212121212', NULL, 'asdfasdfasd', 'asdfasdf', 'asdfasdf', '', 'Male', '2005-12-12', 'asdfasdf.asdfasdf@icmis.com', '+63 1212121212', '123sdas', 'Active', '2026-01-11');

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
  `processed_by` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
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
-- Indexes for table `icmis_audit_logs`
--
ALTER TABLE `icmis_audit_logs`
  ADD PRIMARY KEY (`log_id`),
  ADD KEY `idx_user_id` (`user_id`);

--
-- Indexes for table `icmis_generated_reports`
--
ALTER TABLE `icmis_generated_reports`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_project_id` (`project_id`);

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
  ADD KEY `fk_emp_job` (`job_title_id`),
  ADD KEY `fk_emp_supervisor` (`supervisor_id`);

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
  MODIFY `expense_id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `budget_generated_reports`
--
ALTER TABLE `budget_generated_reports`
  MODIFY `report_id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `budget_line_items`
--
ALTER TABLE `budget_line_items`
  MODIFY `line_item_id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `budget_proposals`
--
ALTER TABLE `budget_proposals`
  MODIFY `proposal_id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `icmis_audit_logs`
--
ALTER TABLE `icmis_audit_logs`
  MODIFY `log_id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `icmis_generated_reports`
--
ALTER TABLE `icmis_generated_reports`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

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
  MODIFY `user_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `procurement_inventory`
--
ALTER TABLE `procurement_inventory`
  MODIFY `item_id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `procurement_purchase_orders`
--
ALTER TABLE `procurement_purchase_orders`
  MODIFY `po_id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `procurement_purchase_order_items`
--
ALTER TABLE `procurement_purchase_order_items`
  MODIFY `po_item_id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `procurement_stock_in`
--
ALTER TABLE `procurement_stock_in`
  MODIFY `stock_in_id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `procurement_stock_out`
--
ALTER TABLE `procurement_stock_out`
  MODIFY `stock_out_id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `procurement_suppliers`
--
ALTER TABLE `procurement_suppliers`
  MODIFY `supplier_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `workforce_assignments`
--
ALTER TABLE `workforce_assignments`
  MODIFY `assignment_id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `workforce_attendance`
--
ALTER TABLE `workforce_attendance`
  MODIFY `attendance_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=52;

--
-- AUTO_INCREMENT for table `workforce_employees`
--
ALTER TABLE `workforce_employees`
  MODIFY `employee_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=52;

--
-- AUTO_INCREMENT for table `workforce_employee_groups`
--
ALTER TABLE `workforce_employee_groups`
  MODIFY `group_id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `workforce_generated_reports`
--
ALTER TABLE `workforce_generated_reports`
  MODIFY `report_id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `workforce_group_memberships`
--
ALTER TABLE `workforce_group_memberships`
  MODIFY `membership_id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `workforce_job_titles`
--
ALTER TABLE `workforce_job_titles`
  MODIFY `job_title_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=29;

--
-- AUTO_INCREMENT for table `workforce_payroll`
--
ALTER TABLE `workforce_payroll`
  MODIFY `payroll_id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `workforce_payroll_periods`
--
ALTER TABLE `workforce_payroll_periods`
  MODIFY `period_id` int NOT NULL AUTO_INCREMENT;

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
-- Constraints for table `icmis_generated_reports`
--
ALTER TABLE `icmis_generated_reports`
  ADD CONSTRAINT `fk_icmis_rep_project` FOREIGN KEY (`project_id`) REFERENCES `icmis_projects` (`project_id`) ON DELETE SET NULL;

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
  ADD CONSTRAINT `fk_po_phase` FOREIGN KEY (`phase_id`) REFERENCES `icmis_project_phases` (`phase_id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_po_project` FOREIGN KEY (`project_id`) REFERENCES `icmis_projects` (`project_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_po_supplier` FOREIGN KEY (`supplier_id`) REFERENCES `procurement_suppliers` (`supplier_id`) ON DELETE CASCADE;

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
  ADD CONSTRAINT `fk_stockout_proj` FOREIGN KEY (`project_id`) REFERENCES `icmis_projects` (`project_id`) ON DELETE SET NULL;

--
-- Constraints for table `workforce_assignments`
--
ALTER TABLE `workforce_assignments`
  ADD CONSTRAINT `fk_assign_emp` FOREIGN KEY (`employee_id`) REFERENCES `workforce_employees` (`employee_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_assign_phase` FOREIGN KEY (`phase_id`) REFERENCES `icmis_project_phases` (`phase_id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_assign_proj` FOREIGN KEY (`project_id`) REFERENCES `icmis_projects` (`project_id`) ON DELETE CASCADE;

--
-- Constraints for table `workforce_attendance`
--
ALTER TABLE `workforce_attendance`
  ADD CONSTRAINT `fk_att_emp` FOREIGN KEY (`employee_id`) REFERENCES `workforce_employees` (`employee_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_att_proj` FOREIGN KEY (`project_id`) REFERENCES `icmis_projects` (`project_id`) ON DELETE SET NULL;

--
-- Constraints for table `workforce_employees`
--
ALTER TABLE `workforce_employees`
  ADD CONSTRAINT `fk_emp_job` FOREIGN KEY (`job_title_id`) REFERENCES `workforce_job_titles` (`job_title_id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_emp_supervisor` FOREIGN KEY (`supervisor_id`) REFERENCES `workforce_employees` (`employee_id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_emp_user` FOREIGN KEY (`user_id`) REFERENCES `icmis_users` (`user_id`) ON DELETE SET NULL;

--
-- Constraints for table `workforce_employee_groups`
--
ALTER TABLE `workforce_employee_groups`
  ADD CONSTRAINT `fk_group_leader` FOREIGN KEY (`group_leader_id`) REFERENCES `workforce_employees` (`employee_id`) ON DELETE SET NULL;

--
-- Constraints for table `workforce_generated_reports`
--
ALTER TABLE `workforce_generated_reports`
  ADD CONSTRAINT `fk_workforce_rep_project` FOREIGN KEY (`project_id`) REFERENCES `icmis_projects` (`project_id`) ON DELETE CASCADE;

--
-- Constraints for table `workforce_group_memberships`
--
ALTER TABLE `workforce_group_memberships`
  ADD CONSTRAINT `fk_mem_emp` FOREIGN KEY (`employee_id`) REFERENCES `workforce_employees` (`employee_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_mem_group` FOREIGN KEY (`group_id`) REFERENCES `workforce_employee_groups` (`group_id`) ON DELETE CASCADE;

--
-- Constraints for table `workforce_payroll`
--
ALTER TABLE `workforce_payroll`
  ADD CONSTRAINT `fk_payroll_emp` FOREIGN KEY (`employee_id`) REFERENCES `workforce_employees` (`employee_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_payroll_period` FOREIGN KEY (`period_id`) REFERENCES `workforce_payroll_periods` (`period_id`) ON DELETE CASCADE;

--
-- Constraints for table `workforce_payroll_periods`
--
ALTER TABLE `workforce_payroll_periods`
  ADD CONSTRAINT `fk_period_processor` FOREIGN KEY (`processed_by`) REFERENCES `workforce_employees` (`employee_id`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
