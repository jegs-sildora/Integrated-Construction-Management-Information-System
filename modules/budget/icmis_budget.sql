-- phpMyAdmin SQL Dump
-- version 6.0.0-dev+20250914.f72491a1c0
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Dec 23, 2025 at 04:59 AM
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
-- Database: `icmis_budget`
--

-- --------------------------------------------------------

--
-- Table structure for table `budget_expenses`
--

CREATE TABLE `budget_expenses` (
  `expense_id` int NOT NULL,
  `project_id` int NOT NULL,
  `phase` enum('Phase 1: Mobilization','Phase 2: Structural','Phase 3: MEPFS','Phase 4: Finishing') DEFAULT 'Phase 1: Mobilization',
  `supplier_id` int DEFAULT NULL,
  `category` enum('MATERIALS','LABOR','EQUIPMENT') NOT NULL,
  `description` varchar(500) NOT NULL,
  `quantity` decimal(10,2) DEFAULT '1.00',
  `unit_cost` decimal(15,2) DEFAULT '0.00',
  `amount` decimal(15,2) NOT NULL,
  `expense_date` date NOT NULL,
  `receipt_path` varchar(500) DEFAULT NULL,
  `status` enum('PENDING','APPROVED','REJECTED') DEFAULT 'PENDING',
  `notes` text,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `budget_line_items`
--

CREATE TABLE `budget_line_items` (
  `line_item_id` int NOT NULL,
  `proposal_id` int NOT NULL,
  `category` enum('MATERIAL','LABOR','EQUIPMENT') NOT NULL,
  `item_name` varchar(255) NOT NULL,
  `quantity` decimal(10,2) DEFAULT '0.00',
  `unit_cost` decimal(15,2) DEFAULT '0.00',
  `duration` decimal(10,2) DEFAULT '1.00',
  `subtotal` decimal(15,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `budget_proposals`
--

CREATE TABLE `budget_proposals` (
  `proposal_id` int NOT NULL,
  `project_id` int NOT NULL,
  `phase` enum('Phase 1: Mobilization','Phase 2: Structural','Phase 3: MEPFS','Phase 4: Finishing') DEFAULT 'Phase 1: Mobilization',
  `code` varchar(20) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` varchar(255) NOT NULL,
  `target_phase` varchar(100) DEFAULT NULL,
  `phase_start_date` date DEFAULT NULL,
  `phase_end_date` date DEFAULT NULL,
  `scope_description` text,
  `total_amount` decimal(15,2) NOT NULL,
  `status` enum('DRAFT','PENDING','APPROVED','REJECTED') NOT NULL,
  `user_name` enum('JOHN DOE') NOT NULL,
  `created_at` timestamp NOT NULL,
  `updated_at` timestamp NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `budget_suppliers`
--

CREATE TABLE `budget_suppliers` (
  `supplier_id` int NOT NULL,
  `name` varchar(255) NOT NULL,
  `contact_person` varchar(100) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `address` text,
  `status` enum('ACTIVE','INACTIVE') DEFAULT 'ACTIVE',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `budget_suppliers`
--

INSERT INTO `budget_suppliers` (`supplier_id`, `name`, `contact_person`, `phone`, `email`, `address`, `status`, `created_at`) VALUES
(1, 'ABC Construction Supplies', 'Maria Santos', '0917-123-4567', 'maria@abcsupplies.ph', '123 Quezon Ave, Quezon City', 'ACTIVE', '2025-12-08 01:26:09'),
(2, 'Metro Hardware Trading', 'Juan Dela Cruz', '0918-234-5678', 'juan@metrohardware.ph', '456 EDSA, Mandaluyong City', 'ACTIVE', '2025-12-08 01:26:09'),
(3, 'Pacific Steel Corporation', 'Robert Lim', '0919-345-6789', 'robert@pacificsteel.ph', '789 C5 Road, Taguig City', 'ACTIVE', '2025-12-08 01:26:09'),
(4, 'Skilled Labor Solutions Inc.', 'Anna Reyes', '0920-456-7890', 'anna@skilledlabor.ph', '321 Shaw Blvd, Pasig City', 'ACTIVE', '2025-12-08 01:26:09'),
(5, 'Heavy Equipment Rentals Co.', 'Carlos Mendoza', '0921-567-8901', 'carlos@heavyequipment.ph', '654 Marcos Highway, Antipolo', 'ACTIVE', '2025-12-08 01:26:09'),
(6, 'Heavy Materials', NULL, NULL, NULL, NULL, 'ACTIVE', '2025-12-09 10:25:48'),
(7, 'Heavy Materials Inc.', NULL, NULL, NULL, NULL, 'ACTIVE', '2025-12-09 22:26:57'),
(8, 'asdf', NULL, NULL, NULL, NULL, 'ACTIVE', '2025-12-23 03:48:05');

-- --------------------------------------------------------

--
-- Table structure for table `generated_reports`
--

CREATE TABLE `generated_reports` (
  `id` int NOT NULL,
  `project_id` int NOT NULL,
  `report_type` varchar(50) NOT NULL,
  `report_name` varchar(255) NOT NULL,
  `context` varchar(100) DEFAULT 'Overall',
  `generated_by` varchar(100) DEFAULT 'System',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `projects`
--

CREATE TABLE `projects` (
  `project_id` int NOT NULL,
  `project_code` varchar(20) NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` varchar(255) NOT NULL,
  `location` varchar(255) NOT NULL,
  `status` enum('PLANNING','ACTIVE','ON_HOLD','COMPLETED','CANCELLED') NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `completion_rate` decimal(5,2) NOT NULL,
  `created_at` timestamp NOT NULL,
  `updated_at` timestamp NOT NULL,
  `project_manager_name` varchar(100) NOT NULL,
  `total_budget` decimal(15,2) DEFAULT '0.00'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `projects`
--

INSERT INTO `projects` (`project_id`, `project_code`, `name`, `description`, `location`, `status`, `start_date`, `end_date`, `completion_rate`, `created_at`, `updated_at`, `project_manager_name`, `total_budget`) VALUES
(1, 'PRJ-2025-001', 'Makati Skyline Mixed-Use Tower', 'Construction of a 30-storey commercial and residential building, including basement parking and roof deck amenities.', 'Salcedo Village, Makati City, Metro Manila', 'ACTIVE', '2025-01-15', '2027-06-30', 12.50, '2025-12-06 09:23:07', '2025-12-06 09:23:07', 'Engr. Antonio Reyes', 15000000.00),
(2, 'PRJ-2025-002', 'Cebu IT Park BPO Complex', 'Structural framework and electrical system installation for the new 15,000 sqm BPO facility.', 'Lahug, Cebu City, Cebu', 'PLANNING', '2025-05-01', '2026-11-20', 0.00, '2025-12-06 09:23:07', '2025-12-06 09:23:07', 'Arch. Sofia Mendoza', 8500000.00),
(3, 'PRJ-2025-003', 'Davao Coastal Subdivision Expansion', 'Land development, drainage system installation, and road concreting for 500 residential lots.', 'Matina Aplaya, Davao City', 'ON_HOLD', '2024-09-10', '2025-12-15', 45.75, '2025-12-06 09:23:07', '2025-12-06 09:23:07', 'Engr. Luis Gonzales', 12000000.00);

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
  ADD KEY `idx_expenses_phase` (`phase`);

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
  ADD KEY `idx_budget_proposals_phase` (`phase`);

--
-- Indexes for table `budget_suppliers`
--
ALTER TABLE `budget_suppliers`
  ADD PRIMARY KEY (`supplier_id`);

--
-- Indexes for table `generated_reports`
--
ALTER TABLE `generated_reports`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `projects`
--
ALTER TABLE `projects`
  ADD PRIMARY KEY (`project_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `budget_expenses`
--
ALTER TABLE `budget_expenses`
  MODIFY `expense_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `budget_line_items`
--
ALTER TABLE `budget_line_items`
  MODIFY `line_item_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=31;

--
-- AUTO_INCREMENT for table `budget_proposals`
--
ALTER TABLE `budget_proposals`
  MODIFY `proposal_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `budget_suppliers`
--
ALTER TABLE `budget_suppliers`
  MODIFY `supplier_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `generated_reports`
--
ALTER TABLE `generated_reports`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `projects`
--
ALTER TABLE `projects`
  MODIFY `project_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `budget_expenses`
--
ALTER TABLE `budget_expenses`
  ADD CONSTRAINT `budget_expenses_ibfk_1` FOREIGN KEY (`project_id`) REFERENCES `projects` (`project_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `budget_expenses_ibfk_2` FOREIGN KEY (`supplier_id`) REFERENCES `budget_suppliers` (`supplier_id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `budget_line_items`
--
ALTER TABLE `budget_line_items`
  ADD CONSTRAINT `budget_line_items_ibfk_1` FOREIGN KEY (`proposal_id`) REFERENCES `budget_proposals` (`proposal_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `budget_proposals`
--
ALTER TABLE `budget_proposals`
  ADD CONSTRAINT `budget_proposals_ibfk_1` FOREIGN KEY (`project_id`) REFERENCES `projects` (`project_id`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
