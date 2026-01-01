-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Dec 22, 2025 at 05:14 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

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
  `itemID` int(11) NOT NULL,
  `itemName` varchar(100) NOT NULL,
  `quantity` int(11) NOT NULL DEFAULT 0,
  `unit` varchar(20) DEFAULT 'pcs',
  `status` varchar(20) DEFAULT 'In Stock',
  `lastUpdated` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `inventory`
--

INSERT INTO `inventory` (`itemID`, `itemName`, `quantity`, `unit`, `status`, `lastUpdated`) VALUES
(1, 'Plywood', 100, 'pcs', 'In Stock', '2025-12-22 04:08:31'),
(2, 'Portland Cement', 100, 'bags', 'In Stock', '2025-12-22 03:59:30'),
(3, 'Steel Rebar', 1000, 'pcs', 'In Stock', '2025-12-22 03:59:30'),
(4, 'Paint Latex', 50, 'gals', 'In Stock', '2025-12-22 03:59:30'),
(5, 'G.I. Sheet (Corrugated)', 150, 'pcs', 'In Stock', '2025-12-22 04:11:36'),
(6, 'Electrical Wire (THHN 3.5mm)', 15, 'rolls', 'Low Stock', '2025-12-22 04:11:36'),
(7, 'PVC Pipe (4 inch)', 8, 'pcs', 'Low Stock', '2025-12-22 04:11:36'),
(8, 'Sand (Vibro)', 0, 'cu.m', 'Out of Stock', '2025-12-22 04:11:36'),
(9, 'Gravel (3/4)', 40, 'cu.m', 'In Stock', '2025-12-22 04:11:36'),
(10, 'Safety Helmet (White)', 25, 'pcs', 'In Stock', '2025-12-22 04:11:36'),
(11, 'Angle Bar 2x2', 200, 'pcs', 'In Stock', '2025-12-22 04:11:36');

-- --------------------------------------------------------

--
-- Table structure for table `purchaseorders`
--

CREATE TABLE `purchaseorders` (
  `orderID` varchar(20) NOT NULL,
  `orderDate` date DEFAULT NULL,
  `itemName` varchar(100) NOT NULL,
  `itemSubtext` varchar(100) DEFAULT NULL,
  `quantity` int(11) NOT NULL,
  `unit` varchar(20) NOT NULL,
  `totalCost` decimal(10,2) DEFAULT NULL,
  `supplierName` varchar(100) DEFAULT NULL,
  `location` varchar(100) DEFAULT NULL,
  `status` varchar(20) DEFAULT 'Pending'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `purchaseorders`
--

INSERT INTO `purchaseorders` (`orderID`, `orderDate`, `itemName`, `itemSubtext`, `quantity`, `unit`, `totalCost`, `supplierName`, `location`, `status`) VALUES
('PO-2025-001', '2025-01-10', 'Plywood', 'asd', 100, 'pcs', 45000.00, 'CitiHardware', 'asd', 'Approved'),
('PO-2025-002', '2025-01-12', 'Portland Cement', NULL, 50, 'bags', 12500.00, 'Wilcon Depot', NULL, 'Approved'),
('PO-2025-003', '2025-01-15', 'Electrical Wire (THHN 3.5mm)', 'Phelps Dodge', 20, 'rolls', 56000.00, 'Negros Electrical Supply', 'Project Alpha', 'Pending'),
('PO-2025-004', '2025-01-18', 'G.I. Sheet (Corrugated)', 'Gauge 26', 100, 'pcs', 35000.00, 'Bacolod Steel & Roofing', 'Warehouse', 'Approved'),
('PO-2025-005', '2025-01-19', 'Safety Shoes', 'Size 9 - Caterpillar', 10, 'pairs', 25000.00, 'CitiHardware', 'HR Dept', 'Rejected'),
('PO-2025-006', '2025-01-20', 'PVC Pipe (4 inch)', 'Neltex Blue', 50, 'pcs', 12500.00, 'TopNotch Plumbing', 'Project Beta', 'Approved'),
('PO-2025-007', '2025-01-22', 'Paint Thinner', 'Gallon Size', 10, 'gals', 3500.00, 'Wilcon Depot', 'Painting Team', 'Pending');

-- --------------------------------------------------------

--
-- Table structure for table `stock_in`
--

CREATE TABLE `stock_in` (
  `stockInID` int(11) NOT NULL,
  `referenceNo` varchar(50) DEFAULT NULL,
  `poID` varchar(20) NOT NULL,
  `itemName` varchar(100) NOT NULL,
  `quantityReceived` int(11) NOT NULL,
  `unit` varchar(20) DEFAULT NULL,
  `receivedBy` varchar(100) DEFAULT NULL,
  `dateReceived` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `stock_in`
--

INSERT INTO `stock_in` (`stockInID`, `referenceNo`, `poID`, `itemName`, `quantityReceived`, `unit`, `receivedBy`, `dateReceived`) VALUES
(1, 'SI-2025-001', 'PO-2025-001', 'Plywood', 100, 'pcs', 'Admin User', '2025-01-15'),
(2, 'SI-2025-101', 'PO-2025-004', 'G.I. Sheet (Corrugated)', 100, 'pcs', 'Admin User', '2025-01-20'),
(3, 'SI-2025-102', 'PO-2025-001', 'Plywood', 50, 'pcs', 'Admin User', '2025-01-21'),
(4, 'SI-2025-103', 'PO-2025-006', 'PVC Pipe (4 inch)', 30, 'pcs', 'Warehouse Keeper', '2025-01-23');

-- --------------------------------------------------------

--
-- Table structure for table `stock_out`
--

CREATE TABLE `stock_out` (
  `id` int(11) NOT NULL,
  `refNo` varchar(50) NOT NULL,
  `itemID` int(11) NOT NULL,
  `quantity` int(11) NOT NULL,
  `issuedTo` varchar(100) DEFAULT NULL,
  `dateIssued` date DEFAULT NULL,
  `notes` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `stock_out`
--

INSERT INTO `stock_out` (`id`, `refNo`, `itemID`, `quantity`, `issuedTo`, `dateIssued`, `notes`) VALUES
(1, 'OUT-2025-001', 1, 20, 'Project Alpha', '2025-02-01', 'Formworks'),
(2, 'OUT-2025-002', 2, 10, 'Project Beta', '2025-02-05', 'Foundation'),
(3, 'OUT-2025-7050', 1, 400, 'Foreman', '2025-12-22', 'asdasd'),
(4, 'OUT-2025-8801', 1, 15, 'Site A - Framing Team', '2025-01-20', 'Urgent request for wall partitioning'),
(5, 'OUT-2025-8802', 2, 20, 'Foundation Crew', '2025-01-22', 'Pouring for storage room extension'),
(6, 'OUT-2025-8803', 4, 5, 'Maintenance Dept', '2025-01-25', 'Repainting lobby walls'),
(7, 'OUT-2025-8804', 3, 50, 'Project Alpha', '2025-01-26', 'Column reinforcement'),
(8, 'OUT-2025-8805', 1, 10, 'Carpentry Workshop', '2025-01-28', 'Fabricating office cabinets'),
(9, 'OUT-2025-8806', 2, 12, 'Site B - Masonry', '2025-01-29', 'Perimeter fence repairs'),
(10, 'OUT-2025-8807', 4, 3, 'Finishing Team', '2025-01-30', 'Touch-ups for conference room');

-- --------------------------------------------------------

--
-- Table structure for table `suppliers`
--

CREATE TABLE `suppliers` (
  `supplierID` int(11) NOT NULL,
  `supplierName` varchar(100) NOT NULL,
  `contactPerson` varchar(100) DEFAULT NULL,
  `contactNumber` varchar(20) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `status` varchar(20) DEFAULT 'Active'
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
  ADD UNIQUE KEY `itemName` (`itemName`);

--
-- Indexes for table `purchaseorders`
--
ALTER TABLE `purchaseorders`
  ADD PRIMARY KEY (`orderID`);

--
-- Indexes for table `stock_in`
--
ALTER TABLE `stock_in`
  ADD PRIMARY KEY (`stockInID`),
  ADD KEY `fk_stockin_po` (`poID`);

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
  MODIFY `itemID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `stock_in`
--
ALTER TABLE `stock_in`
  MODIFY `stockInID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `stock_out`
--
ALTER TABLE `stock_out`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `suppliers`
--
ALTER TABLE `suppliers`
  MODIFY `supplierID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `stock_in`
--
ALTER TABLE `stock_in`
  ADD CONSTRAINT `fk_stockin_po` FOREIGN KEY (`poID`) REFERENCES `purchaseorders` (`orderID`) ON DELETE CASCADE;

--
-- Constraints for table `stock_out`
--
ALTER TABLE `stock_out`
  ADD CONSTRAINT `fk_stockout_inventory` FOREIGN KEY (`itemID`) REFERENCES `inventory` (`itemID`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
