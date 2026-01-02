-- phpMyAdmin SQL Dump
-- version 6.0.0-dev+20250914.f72491a1c0
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Jan 02, 2026 at 06:46 PM
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
-- Database: `icmis_procurement_inventory_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `inventory`
--

CREATE TABLE `inventory` (
  `itemID` int NOT NULL,
  `item_name` varchar(255) NOT NULL,
  `category` varchar(100) DEFAULT 'Uncategorized',
  `quantity` decimal(10,2) NOT NULL DEFAULT '0.00',
  `unit` varchar(50) DEFAULT NULL,
  `unit_cost` decimal(15,2) DEFAULT '0.00',
  `last_updated` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `inventory`
--

INSERT INTO `inventory` (`itemID`, `item_name`, `category`, `quantity`, `unit`, `unit_cost`, `last_updated`) VALUES
(1, 'Assorted Common Wire Nails (kg)', 'General', 15.00, 'pcs', 85.00, '2026-01-02 18:35:40');

-- --------------------------------------------------------

--
-- Table structure for table `inventory_receiving_logs`
--

CREATE TABLE `inventory_receiving_logs` (
  `id` int NOT NULL,
  `po_id` int NOT NULL,
  `item_id_ref` int NOT NULL COMMENT 'ID from purchase_order_items',
  `item_name` varchar(255) NOT NULL,
  `quantity_received` decimal(10,2) NOT NULL,
  `received_by` int NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `inventory_receiving_logs`
--

INSERT INTO `inventory_receiving_logs` (`id`, `po_id`, `item_id_ref`, `item_name`, `quantity_received`, `received_by`, `created_at`) VALUES
(1, 1, 6, 'Assorted Common Wire Nails (kg)', 15.00, 7, '2026-01-02 18:35:40');

-- --------------------------------------------------------

--
-- Table structure for table `purchase_orders`
--

CREATE TABLE `purchase_orders` (
  `po_id` int NOT NULL,
  `po_reference` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `project_id` int NOT NULL,
  `supplier_id` int NOT NULL,
  `phase` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `order_title` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `order_date` date NOT NULL DEFAULT (curdate()),
  `total_amount` decimal(15,2) NOT NULL DEFAULT '0.00',
  `status` enum('PENDING','APPROVED','REJECTED','COMPLETED') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'PENDING',
  `created_by` int DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `purchase_orders`
--

INSERT INTO `purchase_orders` (`po_id`, `po_reference`, `project_id`, `supplier_id`, `phase`, `order_title`, `order_date`, `total_amount`, `status`, `created_by`, `created_at`, `updated_at`) VALUES
(1, 'PO-2026-0001', 1, 3, 'Phase 1: Mobilization', 'Phase 1 Materials', '2026-01-03', 1275.00, 'COMPLETED', 1, '2026-01-02 17:51:18', '2026-01-02 18:35:40');

-- --------------------------------------------------------

--
-- Table structure for table `purchase_order_items`
--

CREATE TABLE `purchase_order_items` (
  `id` int NOT NULL,
  `po_id` int NOT NULL,
  `item_name` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `quantity` decimal(10,2) NOT NULL,
  `unit_cost` decimal(15,2) NOT NULL,
  `total_cost` decimal(15,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `purchase_order_items`
--

INSERT INTO `purchase_order_items` (`id`, `po_id`, `item_name`, `quantity`, `unit_cost`, `total_cost`) VALUES
(6, 1, 'Assorted Common Wire Nails (kg)', 15.00, 85.00, 1275.00);

-- --------------------------------------------------------

--
-- Table structure for table `stock_in`
--

CREATE TABLE `stock_in` (
  `stockInID` int NOT NULL,
  `referenceNo` varchar(50) DEFAULT NULL,
  `po_id` int NOT NULL,
  `itemName` varchar(100) NOT NULL,
  `quantityReceived` int NOT NULL,
  `unit` varchar(20) DEFAULT NULL,
  `receivedBy` varchar(100) DEFAULT NULL,
  `dateReceived` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `stock_out`
--

CREATE TABLE `stock_out` (
  `id` int NOT NULL,
  `refNo` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `itemID` int NOT NULL,
  `quantity` int NOT NULL,
  `issuedTo` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `dateIssued` date DEFAULT NULL,
  `notes` text COLLATE utf8mb4_general_ci
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `suppliers`
--

CREATE TABLE `suppliers` (
  `supplierID` int NOT NULL,
  `supplierName` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `contactPerson` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `contactNumber` varchar(20) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `email` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `address` text COLLATE utf8mb4_general_ci,
  `status` varchar(20) COLLATE utf8mb4_general_ci DEFAULT 'Active'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `suppliers`
--

INSERT INTO `suppliers` (`supplierID`, `supplierName`, `contactPerson`, `contactNumber`, `email`, `address`, `status`) VALUES
(1, 'CitiHardware', 'Michael Tan', '0917-123-4567', 'sales@citi.ph', 'Bacolod City', 'Active'),
(2, 'Wilcon Depot', 'Sarah Lee', '0918-987-6543', 'sales@wilcon.ph', 'Talisay City', 'Active'),
(3, 'Bacolod Steel & Roofing', 'Roberto Gomez', '0917-555-0101', 'sales@bacolodsteel.com', 'Lacson St, Bacolod City', 'Active'),
(4, 'Negros Electrical Supply', 'Elena Cruz', '0922-123-9988', 'contact@negroselectrical.com', 'Singcang-Airport, Bacolod', 'Active'),
(5, 'TopNotch Plumbing', 'Mario Santos', '0918-777-6655', 'mario@topnotch.ph', 'Mandalagan, Bacolod City', 'Active'),
(6, 'Global Construction Corp', 'Vivian Lim', '0917-888-2233', 'vlim@globalconst.com', 'Talisay City', 'Inactive');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `inventory`
--
ALTER TABLE `inventory`
  ADD PRIMARY KEY (`itemID`),
  ADD UNIQUE KEY `unique_item` (`item_name`);

--
-- Indexes for table `inventory_receiving_logs`
--
ALTER TABLE `inventory_receiving_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_po_id` (`po_id`);

--
-- Indexes for table `purchase_orders`
--
ALTER TABLE `purchase_orders`
  ADD PRIMARY KEY (`po_id`),
  ADD KEY `idx_project` (`project_id`),
  ADD KEY `idx_supplier` (`supplier_id`);

--
-- Indexes for table `purchase_order_items`
--
ALTER TABLE `purchase_order_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_po_items` (`po_id`);

--
-- Indexes for table `stock_in`
--
ALTER TABLE `stock_in`
  ADD PRIMARY KEY (`stockInID`),
  ADD KEY `fk_stockin_po_new` (`po_id`);

--
-- Indexes for table `stock_out`
--
ALTER TABLE `stock_out`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_stockout_inventory` (`itemID`);

--
-- Indexes for table `suppliers`
--
ALTER TABLE `suppliers`
  ADD PRIMARY KEY (`supplierID`),
  ADD UNIQUE KEY `supplierName` (`supplierName`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `inventory`
--
ALTER TABLE `inventory`
  MODIFY `itemID` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `inventory_receiving_logs`
--
ALTER TABLE `inventory_receiving_logs`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `purchase_orders`
--
ALTER TABLE `purchase_orders`
  MODIFY `po_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `purchase_order_items`
--
ALTER TABLE `purchase_order_items`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `stock_in`
--
ALTER TABLE `stock_in`
  MODIFY `stockInID` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `stock_out`
--
ALTER TABLE `stock_out`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `suppliers`
--
ALTER TABLE `suppliers`
  MODIFY `supplierID` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `purchase_order_items`
--
ALTER TABLE `purchase_order_items`
  ADD CONSTRAINT `fk_po_items` FOREIGN KEY (`po_id`) REFERENCES `purchase_orders` (`po_id`) ON DELETE CASCADE;

--
-- Constraints for table `stock_in`
--
ALTER TABLE `stock_in`
  ADD CONSTRAINT `fk_stockin_po_new` FOREIGN KEY (`po_id`) REFERENCES `purchase_orders` (`po_id`) ON DELETE CASCADE;

--
-- Constraints for table `stock_out`
--
ALTER TABLE `stock_out`
  ADD CONSTRAINT `fk_stockout_inventory` FOREIGN KEY (`itemID`) REFERENCES `inventory` (`itemID`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
