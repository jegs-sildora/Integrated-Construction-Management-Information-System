-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Dec 26, 2025 at 12:08 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

-- DISABLE FOREIGN KEY CHECKS TO PREVENT ERRORS DURING DROP/CREATE
SET FOREIGN_KEY_CHECKS = 0;

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `workforce_db`
--
CREATE DATABASE IF NOT EXISTS `workforce_db` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `workforce_db`;

-- --------------------------------------------------------

--
-- Table structure for table `assignments`
--

DROP TABLE IF EXISTS `assignments`;
CREATE TABLE `assignments` (
  `assignment_id` varchar(20) NOT NULL,
  `employee_id` varchar(20) DEFAULT NULL,
  `project_id` varchar(20) DEFAULT NULL,
  `phase_id` varchar(20) DEFAULT NULL,
  `role` varchar(100) DEFAULT NULL,
  `task_description` text DEFAULT NULL,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `status` enum('Active','Completed','Cancelled') DEFAULT 'Active',
  `notes` text DEFAULT NULL,
  `assigned_by` varchar(20) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `assignments`
--

INSERT INTO `assignments` (`assignment_id`, `employee_id`, `project_id`, `phase_id`, `role`, `task_description`, `start_date`, `end_date`, `status`, `notes`, `assigned_by`, `created_at`, `updated_at`) VALUES
('ASG001', 'EMP003', 'PRJ001', 'PH001', 'Foreman', 'Supervise foundation excavation', '2024-01-15', '2024-03-15', 'Completed', 'All works completed', 'EMP001', '2025-12-25 22:30:17', '2025-12-25 22:30:17'),
('ASG002', 'EMP004', 'PRJ001', 'PH001', 'Carpenter', 'Assist in foundation formwork', '2024-01-15', '2024-03-15', 'Completed', 'No issues', 'EMP001', '2025-12-25 22:30:17', '2025-12-25 22:30:17'),
('ASG003', 'EMP003', 'PRJ001', 'PH002', 'Foreman', 'Manage main structure construction', '2024-03-16', '2024-09-15', 'Active', 'Ongoing works', 'EMP001', '2025-12-25 22:30:17', '2025-12-25 22:30:17'),
('ASG004', 'EMP005', 'PRJ001', 'PH002', 'Welder', 'Welding steel structures', '2024-03-16', '2024-09-15', 'Active', 'Some delays', 'EMP001', '2025-12-25 22:30:17', '2025-12-25 22:30:17'),
('ASG005', 'EMP006', 'PRJ001', 'PH003', 'Electrician', 'Install electrical systems', '2024-09-16', '2025-01-15', 'Active', 'Waiting materials', 'EMP001', '2025-12-25 22:30:17', '2025-12-25 22:31:39'),
('ASG006', 'EMP007', 'PRJ002', 'PH004', 'Plumber', 'Site preparation plumbing', '2024-02-01', '2024-04-01', 'Completed', 'Finished ahead of schedule', 'EMP029', '2025-12-25 22:30:17', '2025-12-25 22:30:17'),
('ASG007', 'EMP014', 'PRJ002', 'PH005', 'Laborer', 'Assist in construction of residential units', '2024-04-02', '2024-12-15', 'Active', 'Ongoing works', 'EMP029', '2025-12-25 22:30:17', '2025-12-25 22:30:17'),
('ASG008', 'EMP004', 'PRJ002', 'PH006', 'Carpenter', 'Install doors and frames', '2024-12-16', '2025-02-01', 'Active', 'Phase not started', 'EMP029', '2025-12-25 22:30:17', '2025-12-25 22:31:39'),
('ASG009', 'EMP003', 'PRJ003', 'PH007', 'Foreman', 'Plan and coordinate mall expansion', '2024-03-05', '2024-05-01', 'Completed', 'Approved design', 'EMP001', '2025-12-25 22:30:17', '2025-12-25 22:30:17'),
('ASG010', 'EMP005', 'PRJ003', 'PH008', 'Welder', 'Steel framework construction', '2024-05-02', '2024-11-30', 'Active', 'Materials on order', 'EMP001', '2025-12-25 22:30:17', '2025-12-25 22:31:39'),
('ASG011', 'EMP006', 'PRJ003', 'PH009', 'Electrician', 'Install electrical wiring', '2024-12-01', '2024-12-20', 'Active', 'Phase not started', 'EMP001', '2025-12-25 22:30:17', '2025-12-25 22:31:39'),
('ASG012', 'EMP003', 'PRJ004', 'PH010', 'Foreman', 'Bridge design supervision', '2024-04-10', '2024-06-10', 'Completed', 'Design approved', 'EMP012', '2025-12-25 22:30:17', '2025-12-25 22:30:17'),
('ASG013', 'EMP018', 'PRJ004', 'PH011', 'Crane Operator', 'Construct bridge deck', '2024-06-11', '2025-03-15', 'Active', 'Ongoing', 'EMP012', '2025-12-25 22:30:17', '2025-12-25 22:30:17'),
('ASG014', 'EMP004', 'PRJ004', 'PH012', 'Carpenter', 'Install bridge railing', '2025-03-16', '2025-06-30', 'Active', 'Waiting materials', 'EMP012', '2025-12-25 22:30:17', '2025-12-25 22:31:39'),
('ASG015', 'EMP014', 'PRJ005', 'PH013', 'Laborer', 'Park design work', '2024-05-01', '2024-06-01', 'Completed', 'Completed on schedule', 'EMP028', '2025-12-25 22:30:17', '2025-12-25 22:30:17'),
('ASG016', 'EMP004', 'PRJ005', 'PH014', 'Carpenter', 'Groundwork tasks', '2024-06-02', '2024-10-15', 'Completed', 'Completed', 'EMP028', '2025-12-25 22:30:17', '2025-12-25 22:30:17'),
('ASG017', 'EMP005', 'PRJ005', 'PH015', 'Welder', 'Install benches and lighting', '2024-10-16', '2024-11-30', 'Completed', 'All installed', 'EMP028', '2025-12-25 22:30:17', '2025-12-25 22:30:17'),
('ASG018', 'EMP003', 'PRJ006', 'PH016', 'Foreman', 'Plan office layout', '2024-06-15', '2024-08-01', 'Completed', 'Approved design', 'EMP029', '2025-12-25 22:30:17', '2025-12-25 22:30:17'),
('ASG019', 'EMP006', 'PRJ006', 'PH017', 'Electrician', 'Install office wiring', '2024-08-02', '2024-12-15', 'Active', 'Ongoing', 'EMP029', '2025-12-25 22:30:17', '2025-12-25 22:30:17'),
('ASG020', 'EMP005', 'PRJ006', 'PH018', 'Welder', 'Metal finishing', '2024-12-16', '2025-01-15', 'Active', 'Waiting materials', 'EMP029', '2025-12-25 22:30:17', '2025-12-25 22:31:39'),
('ASG021', 'EMP007', 'PRJ007', 'PH019', 'Plumber', 'Plan site piping', '2024-07-01', '2024-08-01', 'Completed', 'Approved', 'EMP001', '2025-12-25 22:30:17', '2025-12-25 22:30:17'),
('ASG022', 'EMP004', 'PRJ007', 'PH020', 'Carpenter', 'Warehouse construction', '2024-08-02', '2024-12-15', 'Active', 'Not started', 'EMP001', '2025-12-25 22:30:17', '2025-12-25 22:31:39'),
('ASG023', 'EMP005', 'PRJ007', 'PH021', 'Welder', 'Install metal fixtures', '2024-12-16', '2024-12-31', 'Active', 'Not started', 'EMP001', '2025-12-25 22:30:17', '2025-12-25 22:31:39'),
('ASG024', 'EMP003', 'PRJ008', 'PH022', 'Foreman', 'Coordinate condo construction', '2024-08-05', '2024-10-01', 'Completed', 'Approved', 'EMP029', '2025-12-25 22:30:17', '2025-12-25 22:30:17'),
('ASG025', 'EMP018', 'PRJ008', 'PH023', 'Crane Operator', 'Lift materials', '2024-10-02', '2025-06-30', 'Active', 'Ongoing', 'EMP029', '2025-12-25 22:30:17', '2025-12-25 22:30:17'),
('ASG026', 'EMP004', 'PRJ008', 'PH024', 'Carpenter', 'Install doors and frames', '2025-07-01', '2025-08-05', 'Active', 'Pending', 'EMP029', '2025-12-25 22:30:17', '2025-12-25 22:31:39'),
('ASG027', 'EMP003', 'PRJ009', 'PH025', 'Foreman', 'Coordinate hospital wing', '2024-09-01', '2024-10-15', 'Completed', 'Approved', 'EMP011', '2025-12-25 22:30:17', '2025-12-25 22:30:17'),
('ASG028', 'EMP006', 'PRJ009', 'PH026', 'Electrician', 'Install wiring', '2024-10-16', '2025-02-28', 'Active', 'Pending', 'EMP011', '2025-12-25 22:30:17', '2025-12-25 22:31:39'),
('ASG029', 'EMP005', 'PRJ009', 'PH027', 'Welder', 'Metal works', '2025-03-01', '2025-03-01', 'Active', 'Pending', 'EMP011', '2025-12-25 22:30:17', '2025-12-25 22:31:39'),
('ASG030', 'EMP003', 'PRJ010', 'PH028', 'Foreman', 'Coordinate construction', '2024-10-01', '2024-12-01', 'Completed', 'Approved', 'EMP001', '2025-12-25 22:30:17', '2025-12-25 22:30:17'),
('ASG031', 'EMP018', 'PRJ010', 'PH029', 'Crane Operator', 'Lift heavy equipment', '2024-12-02', '2025-06-30', 'Active', 'Ongoing', 'EMP001', '2025-12-25 22:30:17', '2025-12-25 22:30:17'),
('ASG032', 'EMP004', 'PRJ010', 'PH030', 'Carpenter', 'Install fixtures', '2025-07-01', '2025-07-15', 'Active', 'Pending', 'EMP001', '2025-12-25 22:30:17', '2025-12-25 22:31:39'),
('ASG033', 'EMP003', 'PRJ011', 'PH031', 'Foreman', 'Coordinate resort construction', '2024-11-05', '2024-12-15', 'Completed', 'Approved', 'EMP029', '2025-12-25 22:30:17', '2025-12-25 22:30:17'),
('ASG034', 'EMP005', 'PRJ011', 'PH032', 'Welder', 'Structural metal work', '2024-12-16', '2025-04-30', 'Active', 'Pending', 'EMP029', '2025-12-25 22:30:17', '2025-12-25 22:31:39'),
('ASG035', 'EMP006', 'PRJ011', 'PH033', 'Electrician', 'Install electrical systems', '2025-05-01', '2025-05-30', 'Active', 'Pending', 'EMP029', '2025-12-25 22:30:17', '2025-12-25 22:31:39'),
('ASG036', 'EMP003', 'PRJ012', 'PH034', 'Foreman', 'Coordinate apartment construction', '2024-12-01', '2025-02-01', 'Completed', 'Approved', 'EMP012', '2025-12-25 22:30:17', '2025-12-25 22:30:17'),
('ASG037', 'EMP018', 'PRJ012', 'PH035', 'Crane Operator', 'Lift materials', '2025-02-02', '2025-09-30', 'Active', 'Ongoing', 'EMP012', '2025-12-25 22:30:17', '2025-12-25 22:30:17'),
('ASG038', 'EMP004', 'PRJ012', 'PH036', 'Carpenter', 'Install doors and frames', '2025-10-01', '2025-11-30', 'Active', 'Pending', 'EMP012', '2025-12-25 22:30:17', '2025-12-25 22:31:39'),
('ASG039', 'EMP003', 'PRJ013', 'PH037', 'Foreman', 'Coordinate sports complex', '2025-01-10', '2025-03-01', 'Active', 'Pending', 'EMP028', '2025-12-25 22:30:17', '2025-12-25 22:31:39'),
('ASG040', 'EMP005', 'PRJ013', 'PH038', 'Welder', 'Construct metal fixtures', '2025-03-02', '2025-10-31', 'Active', 'Pending', 'EMP028', '2025-12-25 22:30:17', '2025-12-25 22:31:39'),
('ASG041', 'EMP006', 'PRJ013', 'PH039', 'Electrician', 'Install wiring and lighting', '2025-11-01', '2025-12-15', 'Active', 'Pending', 'EMP028', '2025-12-25 22:30:17', '2025-12-25 22:31:39'),
('ASG042', 'EMP003', 'PRJ014', 'PH040', 'Foreman', 'Coordinate mall construction', '2025-02-15', '2025-05-01', 'Active', 'Pending', 'EMP001', '2025-12-25 22:30:17', '2025-12-25 22:31:39'),
('ASG043', 'EMP018', 'PRJ014', 'PH041', 'Crane Operator', 'Lift heavy equipment', '2025-05-02', '2025-11-30', 'Active', 'Pending', 'EMP001', '2025-12-25 22:30:17', '2025-12-25 22:31:39'),
('ASG044', 'EMP004', 'PRJ014', 'PH042', 'Carpenter', 'Install fixtures and furniture', '2025-12-01', '2025-12-31', 'Active', 'Pending', 'EMP001', '2025-12-25 22:30:17', '2025-12-25 22:31:39'),
('ASG045', 'EMP003', 'PRJ015', 'PH043', 'Foreman', 'Coordinate university building', '2025-03-01', '2025-05-01', 'Active', 'Pending', 'EMP029', '2025-12-25 22:30:17', '2025-12-25 22:31:39'),
('ASG046', 'EMP018', 'PRJ015', 'PH044', 'Crane Operator', 'Lift construction materials', '2025-05-02', '2025-11-30', 'Active', 'Ongoing', 'EMP029', '2025-12-25 22:30:17', '2025-12-25 22:30:17'),
('ASG047', 'EMP006', 'PRJ015', 'PH045', 'Electrician', 'Install wiring and labs', '2025-12-01', '2025-12-15', 'Active', 'Pending', 'EMP029', '2025-12-25 22:30:17', '2025-12-25 22:31:39');

-- --------------------------------------------------------

--
-- Table structure for table `attendance`
--

DROP TABLE IF EXISTS `attendance`;
CREATE TABLE `attendance` (
  `attendance_id` int(11) NOT NULL,
  `employee_id` varchar(20) DEFAULT NULL,
  `attendance_date` date NOT NULL,
  `time_in` time DEFAULT NULL,
  `time_out` time DEFAULT NULL,
  `status` enum('Present','Absent','Late','On Leave','Unassigned') NOT NULL,
  `project_id` varchar(20) DEFAULT NULL,
  `remarks` text DEFAULT NULL,
  `recorded_by` varchar(20) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `attendance`
--

INSERT INTO `attendance` (`attendance_id`, `employee_id`, `attendance_date`, `time_in`, `time_out`, `status`, `project_id`, `remarks`, `recorded_by`, `created_at`, `updated_at`) VALUES
(69, 'EMP003', '2025-01-02', '08:00:00', '17:00:00', 'Present', 'PRJ001', 'On time', 'EMP001', '2025-12-25 22:50:41', '2025-12-25 22:50:41'),
(70, 'EMP004', '2025-01-02', '08:15:00', '17:00:00', 'Late', 'PRJ001', 'Traffic delay', 'EMP001', '2025-12-25 22:50:41', '2025-12-25 22:50:41'),
(71, 'EMP005', '2025-01-02', '08:00:00', '17:00:00', 'Present', 'PRJ001', 'On time', 'EMP001', '2025-12-25 22:50:41', '2025-12-25 22:50:41'),
(72, 'EMP006', '2025-01-02', '08:00:00', '17:00:00', 'Present', 'PRJ002', 'On time', 'EMP029', '2025-12-25 22:50:41', '2025-12-25 22:50:41'),
(73, 'EMP007', '2025-01-02', '08:30:00', '17:00:00', 'Late', 'PRJ002', 'Late arrival', 'EMP029', '2025-12-25 22:50:41', '2025-12-25 22:50:41'),
(74, 'EMP014', '2025-01-02', '08:00:00', '17:00:00', 'Present', 'PRJ002', 'On time', 'EMP029', '2025-12-25 22:50:41', '2025-12-25 22:50:41'),
(75, 'EMP003', '2025-01-03', '08:00:00', '17:00:00', 'Present', 'PRJ003', 'On time', 'EMP001', '2025-12-25 22:50:41', '2025-12-25 22:50:41'),
(76, 'EMP005', '2025-01-03', '08:00:00', '17:00:00', 'Present', 'PRJ003', 'On time', 'EMP001', '2025-12-25 22:50:41', '2025-12-25 22:50:41'),
(77, 'EMP004', '2025-01-03', '08:10:00', '17:00:00', 'Late', 'PRJ003', 'Traffic', 'EMP001', '2025-12-25 22:50:41', '2025-12-25 22:50:41'),
(78, 'EMP003', '2025-01-04', '08:00:00', '17:00:00', 'Present', 'PRJ004', 'On time', 'EMP012', '2025-12-25 22:50:41', '2025-12-25 22:50:41'),
(79, 'EMP018', '2025-01-04', '08:00:00', '17:00:00', 'Present', 'PRJ004', 'On time', 'EMP012', '2025-12-25 22:50:41', '2025-12-25 22:50:41'),
(80, 'EMP004', '2025-01-04', '08:00:00', '17:00:00', 'Present', 'PRJ004', 'On time', 'EMP012', '2025-12-25 22:50:41', '2025-12-25 22:50:41'),
(81, 'EMP014', '2025-01-05', '08:05:00', '17:00:00', 'Late', 'PRJ005', 'Slight delay', 'EMP028', '2025-12-25 22:50:41', '2025-12-25 22:50:41'),
(82, 'EMP005', '2025-01-05', '08:00:00', '17:00:00', 'Present', 'PRJ005', 'On time', 'EMP028', '2025-12-25 22:50:41', '2025-12-25 22:50:41'),
(83, 'EMP004', '2025-01-05', '00:00:00', '00:00:00', 'Absent', 'PRJ005', 'Sick leave', 'EMP028', '2025-12-25 22:50:41', '2025-12-25 22:50:41'),
(84, 'EMP003', '2025-01-06', '08:00:00', '17:00:00', 'Present', 'PRJ006', 'On time', 'EMP029', '2025-12-25 22:50:41', '2025-12-25 22:50:41'),
(85, 'EMP006', '2025-01-06', '08:15:00', '17:00:00', 'Late', 'PRJ006', 'Traffic', 'EMP029', '2025-12-25 22:50:41', '2025-12-25 22:50:41'),
(86, 'EMP005', '2025-01-06', '08:00:00', '17:00:00', 'Present', 'PRJ006', 'On time', 'EMP029', '2025-12-25 22:50:41', '2025-12-25 22:50:41'),
(87, 'EMP007', '2025-01-07', '08:00:00', '17:00:00', 'Present', 'PRJ007', 'On time', 'EMP001', '2025-12-25 22:50:41', '2025-12-25 22:50:41'),
(88, 'EMP004', '2025-01-07', '08:00:00', '17:00:00', 'Present', 'PRJ007', 'On time', 'EMP001', '2025-12-25 22:50:41', '2025-12-25 22:50:41'),
(89, 'EMP003', '2025-01-08', '08:00:00', '17:00:00', 'Present', 'PRJ008', 'On time', 'EMP029', '2025-12-25 22:50:41', '2025-12-25 22:50:41'),
(90, 'EMP018', '2025-01-08', '08:00:00', '17:00:00', 'Present', 'PRJ008', 'On time', 'EMP029', '2025-12-25 22:50:41', '2025-12-25 22:50:41'),
(91, 'EMP004', '2025-01-08', '08:05:00', '17:00:00', 'Late', 'PRJ008', 'Traffic', 'EMP029', '2025-12-25 22:50:41', '2025-12-25 22:50:41'),
(92, 'EMP003', '2025-01-09', '08:00:00', '17:00:00', 'Present', 'PRJ009', 'On time', 'EMP011', '2025-12-25 22:50:41', '2025-12-25 22:50:41'),
(93, 'EMP006', '2025-01-09', '08:00:00', '17:00:00', 'Present', 'PRJ009', 'On time', 'EMP011', '2025-12-25 22:50:41', '2025-12-25 22:50:41'),
(94, 'EMP005', '2025-01-09', '00:00:00', '00:00:00', 'Absent', 'PRJ009', 'Sick leave', 'EMP011', '2025-12-25 22:50:41', '2025-12-25 22:50:41'),
(95, 'EMP003', '2025-01-10', '08:00:00', '17:00:00', 'Present', 'PRJ010', 'On time', 'EMP001', '2025-12-25 22:50:41', '2025-12-25 22:50:41'),
(96, 'EMP018', '2025-01-10', '08:15:00', '17:00:00', 'Late', 'PRJ010', 'Late arrival', 'EMP001', '2025-12-25 22:50:41', '2025-12-25 22:50:41'),
(97, 'EMP004', '2025-01-10', '08:00:00', '17:00:00', 'Present', 'PRJ010', 'On time', 'EMP001', '2025-12-25 22:50:41', '2025-12-25 22:50:41'),
(98, 'EMP003', '2025-01-11', '08:00:00', '17:00:00', 'Present', 'PRJ011', 'On time', 'EMP029', '2025-12-25 22:50:41', '2025-12-25 22:50:41');

-- --------------------------------------------------------

--
-- Table structure for table `audit_logs`
--

DROP TABLE IF EXISTS `audit_logs`;
CREATE TABLE `audit_logs` (
  `log_id` int(11) NOT NULL,
  `user_id` varchar(20) DEFAULT NULL,
  `action_type` varchar(50) DEFAULT NULL,
  `table_name` varchar(50) DEFAULT NULL,
  `record_id` varchar(50) DEFAULT NULL,
  `old_values` text DEFAULT NULL,
  `new_values` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `employees`
--

DROP TABLE IF EXISTS `employees`;
CREATE TABLE `employees` (
  `employee_id` varchar(20) NOT NULL,
  `first_name` varchar(50) NOT NULL,
  `last_name` varchar(50) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `emergency_contact_name` varchar(100) DEFAULT NULL,
  `emergency_contact_phone` varchar(20) DEFAULT NULL,
  `position` varchar(100) DEFAULT NULL,
  `skill_type` varchar(50) DEFAULT NULL,
  `employment_type` enum('Daily','Regular','Contractual') NOT NULL,
  `daily_rate` decimal(10,2) DEFAULT NULL,
  `monthly_salary` decimal(10,2) DEFAULT NULL,
  `start_date` date DEFAULT NULL,
  `status` enum('Active','Inactive','Terminated') DEFAULT 'Active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `employees`
--

INSERT INTO `employees` (`employee_id`, `first_name`, `last_name`, `email`, `phone`, `address`, `emergency_contact_name`, `emergency_contact_phone`, `position`, `skill_type`, `employment_type`, `daily_rate`, `monthly_salary`, `start_date`, `status`, `created_at`, `updated_at`) VALUES
('EMP001', 'John', 'Doe', 'john.doe@example.com', '09171234567', '123 Main St, Manila', 'Jane Doe', '09179876543', 'Project Manager', 'Management', 'Regular', 0.00, 80000.00, '2023-01-10', 'Active', '2025-12-25 22:10:42', '2025-12-25 22:10:42'),
('EMP002', 'Michael', 'Smith', 'michael.smith@example.com', '09171234568', '45 Rizal St, Makati', 'Laura Smith', '09179876544', 'Site Engineer', 'Engineering', 'Regular', 0.00, 50000.00, '2023-02-15', 'Active', '2025-12-25 22:10:42', '2025-12-25 22:10:42'),
('EMP003', 'Anna', 'Johnson', 'anna.johnson@example.com', '09171234569', '78 Quezon Ave, Quezon City', 'Peter Johnson', '09179876545', 'Foreman', 'Construction', 'Regular', 2500.00, 0.00, '2023-03-01', 'Active', '2025-12-25 22:10:42', '2025-12-25 22:10:42'),
('EMP004', 'David', 'Brown', 'david.brown@example.com', '09171234570', '22 Taft Ave, Manila', 'Mary Brown', '09179876546', 'Carpenter', 'Skilled Labor', 'Daily', 2000.00, 0.00, '2023-03-05', 'Active', '2025-12-25 22:10:42', '2025-12-25 22:10:42'),
('EMP005', 'Sarah', 'Davis', 'sarah.davis@example.com', '09171234571', '9 EDSA, Mandaluyong', 'James Davis', '09179876547', 'Welder', 'Skilled Labor', 'Daily', 2200.00, 0.00, '2023-04-10', 'Active', '2025-12-25 22:10:42', '2025-12-25 22:10:42'),
('EMP006', 'Chris', 'Miller', 'chris.miller@example.com', '09171234572', '11 Pasig Blvd, Pasig', 'Emily Miller', '09179876548', 'Electrician', 'Skilled Labor', 'Daily', 2300.00, 0.00, '2023-04-12', 'Active', '2025-12-25 22:10:42', '2025-12-25 22:10:42'),
('EMP007', 'Jessica', 'Wilson', 'jessica.wilson@example.com', '09171234573', '33 Banawe St, Quezon City', 'Tom Wilson', '09179876549', 'Plumber', 'Skilled Labor', 'Daily', 2100.00, 0.00, '2023-05-01', 'Active', '2025-12-25 22:10:42', '2025-12-25 22:10:42'),
('EMP008', 'Daniel', 'Moore', 'daniel.moore@example.com', '09171234574', '56 Ortigas Ave, Pasig', 'Sophie Moore', '09179876550', 'Safety Officer', 'Management', 'Regular', 0.00, 45000.00, '2023-05-10', 'Active', '2025-12-25 22:10:42', '2025-12-25 22:10:42'),
('EMP009', 'Emily', 'Taylor', 'emily.taylor@example.com', '09171234575', '77 Shaw Blvd, Mandaluyong', 'Kevin Taylor', '09179876551', 'Mason', 'Skilled Labor', 'Daily', 2100.00, 0.00, '2023-06-01', 'Active', '2025-12-25 22:10:42', '2025-12-25 22:10:42'),
('EMP010', 'Matthew', 'Anderson', 'matthew.anderson@example.com', '09171234576', '12 Katipunan Ave, Quezon City', 'Linda Anderson', '09179876552', 'Heavy Equipment Operator', 'Operator', 'Daily', 2500.00, 0.00, '2023-06-05', 'Active', '2025-12-25 22:10:42', '2025-12-25 22:10:42'),
('EMP011', 'Olivia', 'Thomas', 'olivia.thomas@example.com', '09171234577', '19 Gilmore St, Quezon City', 'Paul Thomas', '09179876553', 'Project Engineer', 'Engineering', 'Regular', 0.00, 55000.00, '2023-06-15', 'Active', '2025-12-25 22:10:42', '2025-12-25 22:10:42'),
('EMP012', 'James', 'Jackson', 'james.jackson@example.com', '09171234578', '28 Commonwealth Ave, Quezon City', 'Rachel Jackson', '09179876554', 'Site Supervisor', 'Management', 'Regular', 0.00, 60000.00, '2023-07-01', 'Active', '2025-12-25 22:10:42', '2025-12-25 22:10:42'),
('EMP013', 'Sophia', 'White', 'sophia.white@example.com', '09171234579', '35 Bonifacio St, Manila', 'Henry White', '09179876555', 'Painter', 'Skilled Labor', 'Daily', 2000.00, 0.00, '2023-07-10', 'Active', '2025-12-25 22:10:42', '2025-12-25 22:10:42'),
('EMP014', 'Ethan', 'Harris', 'ethan.harris@example.com', '09171234580', '44 EDSA, Quezon City', 'Grace Harris', '09179876556', 'Laborer', 'Unskilled Labor', 'Daily', 1800.00, 0.00, '2023-07-20', 'Active', '2025-12-25 22:10:42', '2025-12-25 22:10:42'),
('EMP015', 'Mia', 'Martin', 'mia.martin@example.com', '09171234581', '50 Katipunan Ave, QC', 'Jack Martin', '09179876557', 'Architect', 'Engineering', 'Regular', 0.00, 65000.00, '2023-08-01', 'Active', '2025-12-25 22:10:42', '2025-12-25 22:10:42'),
('EMP016', 'Alexander', 'Thompson', 'alex.thompson@example.com', '09171234582', '60 Makati Ave, Makati', 'Laura Thompson', '09179876558', 'Quantity Surveyor', 'Engineering', 'Regular', 0.00, 58000.00, '2023-08-05', 'Active', '2025-12-25 22:10:42', '2025-12-25 22:10:42'),
('EMP017', 'Isabella', 'Garcia', 'isabella.garcia@example.com', '09171234583', '73 Shaw Blvd, Mandaluyong', 'Carlos Garcia', '09179876559', 'Steel Fixer', 'Skilled Labor', 'Daily', 2200.00, 0.00, '2023-08-10', 'Active', '2025-12-25 22:10:42', '2025-12-25 22:10:42'),
('EMP018', 'Benjamin', 'Martinez', 'benjamin.martinez@example.com', '09171234584', '85 Ortigas Ave, Pasig', 'Sophia Martinez', '09179876560', 'Crane Operator', 'Operator', 'Daily', 2700.00, 0.00, '2023-08-15', 'Active', '2025-12-25 22:10:42', '2025-12-25 22:10:42'),
('EMP019', 'Charlotte', 'Robinson', 'charlotte.robinson@example.com', '09171234585', '90 EDSA, Mandaluyong', 'David Robinson', '09179876561', 'Surveyor', 'Engineering', 'Regular', 0.00, 50000.00, '2023-08-20', 'Active', '2025-12-25 22:10:42', '2025-12-25 22:10:42'),
('EMP020', 'Henry', 'Clark', 'henry.clark@example.com', '09171234586', '100 Taft Ave, Manila', 'Emma Clark', '09179876562', 'Forklift Operator', 'Operator', 'Daily', 2400.00, 0.00, '2023-08-25', 'Active', '2025-12-25 22:10:42', '2025-12-25 22:10:42'),
('EMP021', 'Amelia', 'Rodriguez', 'amelia.rodriguez@example.com', '09171234587', '110 Commonwealth Ave, QC', 'Oscar Rodriguez', '09179876563', 'Plumber', 'Skilled Labor', 'Daily', 2100.00, 0.00, '2023-09-01', 'Active', '2025-12-25 22:10:42', '2025-12-25 22:10:42'),
('EMP022', 'Logan', 'Lewis', 'logan.lewis@example.com', '09171234588', '120 Katipunan Ave, QC', 'Lily Lewis', '09179876564', 'Electrician', 'Skilled Labor', 'Daily', 2300.00, 0.00, '2023-09-05', 'Active', '2025-12-25 22:10:42', '2025-12-25 22:10:42'),
('EMP023', 'Ella', 'Lee', 'ella.lee@example.com', '09171234589', '130 Makati Ave, Makati', 'Ryan Lee', '09179876565', 'Carpenter', 'Skilled Labor', 'Daily', 2000.00, 0.00, '2023-09-10', 'Active', '2025-12-25 22:10:42', '2025-12-25 22:10:42'),
('EMP024', 'Lucas', 'Walker', 'lucas.walker@example.com', '09171234590', '140 Ortigas Ave, Pasig', 'Nina Walker', '09179876566', 'Welder', 'Skilled Labor', 'Daily', 2200.00, 0.00, '2023-09-15', 'Active', '2025-12-25 22:10:42', '2025-12-25 22:10:42'),
('EMP025', 'Grace', 'Hall', 'grace.hall@example.com', '09171234591', '150 Shaw Blvd, Mandaluyong', 'Evan Hall', '09179876567', 'Laborer', 'Unskilled Labor', 'Daily', 1800.00, 0.00, '2023-09-20', 'Active', '2025-12-25 22:10:42', '2025-12-25 22:10:42'),
('EMP026', 'Jack', 'Allen', 'jack.allen@example.com', '09171234592', '160 EDSA, Mandaluyong', 'Olivia Allen', '09179876568', 'Safety Officer', 'Management', 'Regular', 0.00, 45000.00, '2023-09-25', 'Active', '2025-12-25 22:10:42', '2025-12-25 22:10:42'),
('EMP027', 'Lily', 'Young', 'lily.young@example.com', '09171234593', '170 Taft Ave, Manila', 'Henry Young', '09179876569', 'Mason', 'Skilled Labor', 'Daily', 2100.00, 0.00, '2023-10-01', 'Active', '2025-12-25 22:10:42', '2025-12-25 22:10:42'),
('EMP028', 'Owen', 'Hernandez', 'owen.hernandez@example.com', '09171234594', '180 Katipunan Ave, QC', 'Sophia Hernandez', '09179876570', 'Site Supervisor', 'Management', 'Regular', 0.00, 60000.00, '2023-10-05', 'Active', '2025-12-25 22:10:42', '2025-12-25 22:10:42'),
('EMP029', 'Zoe', 'King', 'zoe.king@example.com', '09171234595', '190 Makati Ave, Makati', 'Ethan King', '09179876571', 'Project Manager', 'Management', 'Regular', 0.00, 80000.00, '2023-10-10', 'Active', '2025-12-25 22:10:42', '2025-12-25 22:10:42'),
('EMP030', 'Nathan', 'Wright', 'nathan.wright@example.com', '09171234596', '200 Ortigas Ave, Pasig', 'Grace Wright', '09179876572', 'Heavy Equipment Operator', 'Operator', 'Daily', 2500.00, 0.00, '2023-10-15', 'Active', '2025-12-25 22:10:42', '2025-12-25 22:10:42');

-- --------------------------------------------------------

--
-- Table structure for table `employee_groups`
--

DROP TABLE IF EXISTS `employee_groups`;
CREATE TABLE `employee_groups` (
  `group_id` varchar(20) NOT NULL,
  `group_name` varchar(100) NOT NULL,
  `group_leader_id` varchar(20) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `status` enum('Active','Inactive') DEFAULT 'Active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `employee_skills`
--

DROP TABLE IF EXISTS `employee_skills`;
CREATE TABLE `employee_skills` (
  `employee_skill_id` int(11) NOT NULL,
  `employee_id` varchar(20) DEFAULT NULL,
  `skill_id` varchar(20) DEFAULT NULL,
  `proficiency_level` enum('Beginner','Intermediate','Advanced','Expert') DEFAULT NULL,
  `certified` tinyint(1) DEFAULT 0,
  `certification_date` date DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `employee_skills`
--

INSERT INTO `employee_skills` (`employee_skill_id`, `employee_id`, `skill_id`, `proficiency_level`, `certified`, `certification_date`, `created_at`) VALUES
(1, 'EMP001', 'SKL001', 'Expert', 0, NULL, '2025-12-22 05:21:39'),
(2, 'EMP002', 'SKL002', 'Advanced', 0, NULL, '2025-12-22 05:21:39'),
(3, 'EMP003', 'SKL003', 'Expert', 0, NULL, '2025-12-22 05:21:39'),
(4, 'EMP004', 'SKL004', 'Intermediate', 0, NULL, '2025-12-22 05:21:39');

-- --------------------------------------------------------

--
-- Table structure for table `group_assignments`
--

DROP TABLE IF EXISTS `group_assignments`;
CREATE TABLE `group_assignments` (
  `group_assignment_id` varchar(20) NOT NULL,
  `group_id` varchar(20) DEFAULT NULL,
  `project_id` varchar(20) DEFAULT NULL,
  `phase_id` varchar(20) DEFAULT NULL,
  `role` varchar(100) DEFAULT NULL,
  `task_description` text DEFAULT NULL,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `status` enum('Active','Completed','Cancelled') DEFAULT 'Active',
  `notes` text DEFAULT NULL,
  `assigned_by` varchar(20) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `group_memberships`
--

DROP TABLE IF EXISTS `group_memberships`;
CREATE TABLE `group_memberships` (
  `membership_id` int(11) NOT NULL,
  `employee_id` varchar(20) DEFAULT NULL,
  `group_id` varchar(20) DEFAULT NULL,
  `role_in_group` varchar(100) DEFAULT NULL,
  `joined_date` date DEFAULT NULL,
  `left_date` date DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `job_titles`
--

DROP TABLE IF EXISTS `job_titles`;
CREATE TABLE `job_titles` (
  `job_title_id` varchar(20) NOT NULL,
  `title_name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `default_daily_rate` decimal(10,2) DEFAULT NULL,
  `default_monthly_salary` decimal(10,2) DEFAULT NULL,
  `department` varchar(50) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `job_titles`
--

INSERT INTO `job_titles` (`job_title_id`, `title_name`, `description`, `default_daily_rate`, `default_monthly_salary`, `department`, `is_active`, `created_at`, `updated_at`) VALUES
('JBT001', 'Project Manager', NULL, NULL, 5000.00, 'Management', 1, '2025-12-22 05:21:39', '2025-12-22 05:21:39'),
('JBT002', 'Software Developer', NULL, NULL, 4500.00, 'IT', 1, '2025-12-22 05:21:39', '2025-12-22 05:21:39'),
('JBT003', 'Construction Worker', NULL, NULL, NULL, 'Operations', 1, '2025-12-22 05:21:39', '2025-12-22 05:21:39'),
('JBT004', 'HR Specialist', NULL, NULL, 4000.00, 'Human Resources', 1, '2025-12-22 05:21:39', '2025-12-22 05:21:39');

-- --------------------------------------------------------

--
-- Table structure for table `leave_balances`
--

DROP TABLE IF EXISTS `leave_balances`;
CREATE TABLE `leave_balances` (
  `balance_id` int(11) NOT NULL,
  `employee_id` varchar(20) DEFAULT NULL,
  `leave_type_id` varchar(20) DEFAULT NULL,
  `total_allocated` decimal(5,2) DEFAULT 0.00,
  `used_leaves` decimal(5,2) DEFAULT 0.00,
  `balance` decimal(5,2) DEFAULT 0.00,
  `as_of_date` date DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `leave_requests`
--

DROP TABLE IF EXISTS `leave_requests`;
CREATE TABLE `leave_requests` (
  `leave_request_id` varchar(20) NOT NULL,
  `employee_id` varchar(20) DEFAULT NULL,
  `leave_type_id` varchar(20) DEFAULT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `total_days` decimal(5,2) DEFAULT NULL,
  `reason` text DEFAULT NULL,
  `status` enum('Pending','Approved','Rejected','Cancelled') DEFAULT 'Pending',
  `applied_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `reviewed_date` timestamp NULL DEFAULT NULL,
  `reviewed_by` varchar(20) DEFAULT NULL,
  `reviewer_comments` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `leave_types`
--

DROP TABLE IF EXISTS `leave_types`;
CREATE TABLE `leave_types` (
  `leave_type_id` varchar(20) NOT NULL,
  `leave_name` varchar(50) NOT NULL,
  `description` text DEFAULT NULL,
  `max_days_per_year` int(11) DEFAULT NULL,
  `accrual_rate` decimal(5,2) DEFAULT NULL,
  `carry_over_allowed` tinyint(1) DEFAULT 0,
  `max_carry_over_days` int(11) DEFAULT 0,
  `eligibility_criteria` text DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `leave_types`
--

INSERT INTO `leave_types` (`leave_type_id`, `leave_name`, `description`, `max_days_per_year`, `accrual_rate`, `carry_over_allowed`, `max_carry_over_days`, `eligibility_criteria`, `is_active`, `created_at`, `updated_at`) VALUES
('LVT001', 'Vacation Leave', 'Annual vacation leave', 15, 1.25, 0, 0, NULL, 1, '2025-12-22 05:21:39', '2025-12-22 05:21:39'),
('LVT002', 'Sick Leave', 'Medical leave for illness', 10, 0.83, 0, 0, NULL, 1, '2025-12-22 05:21:39', '2025-12-22 05:21:39'),
('LVT003', 'Emergency Leave', 'Unplanned urgent leave', 5, 0.42, 0, 0, NULL, 1, '2025-12-22 05:21:39', '2025-12-22 05:21:39');

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

DROP TABLE IF EXISTS `notifications`;
CREATE TABLE `notifications` (
  `notification_id` varchar(20) NOT NULL,
  `recipient_id` varchar(20) DEFAULT NULL,
  `sender_id` varchar(20) DEFAULT NULL,
  `subject` varchar(200) DEFAULT NULL,
  `message` text DEFAULT NULL,
  `notification_type` enum('Info','Warning','Alert','Success') DEFAULT 'Info',
  `is_read` tinyint(1) DEFAULT 0,
  `read_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `payroll`
--

DROP TABLE IF EXISTS `payroll`;
CREATE TABLE `payroll` (
  `payroll_id` varchar(20) NOT NULL,
  `employee_id` varchar(20) DEFAULT NULL,
  `period_id` varchar(20) DEFAULT NULL,
  `hours_worked` decimal(5,2) DEFAULT NULL,
  `overtime_hours` decimal(5,2) DEFAULT NULL,
  `gross_pay` decimal(10,2) DEFAULT NULL,
  `tax_deduction` decimal(10,2) DEFAULT NULL,
  `sss_contribution` decimal(10,2) DEFAULT NULL,
  `philhealth_contribution` decimal(10,2) DEFAULT NULL,
  `pagibig_contribution` decimal(10,2) DEFAULT NULL,
  `other_deductions` decimal(10,2) DEFAULT NULL,
  `net_pay` decimal(10,2) DEFAULT NULL,
  `status` enum('Calculated','Approved','Processed') DEFAULT 'Calculated',
  `calculated_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `approved_date` timestamp NULL DEFAULT NULL,
  `processed_date` timestamp NULL DEFAULT NULL,
  `approved_by` varchar(20) DEFAULT NULL,
  `processed_by` varchar(20) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `payroll`
--

INSERT INTO `payroll` (`payroll_id`, `employee_id`, `period_id`, `hours_worked`, `overtime_hours`, `gross_pay`, `tax_deduction`, `sss_contribution`, `philhealth_contribution`, `pagibig_contribution`, `other_deductions`, `net_pay`, `status`, `calculated_date`, `approved_date`, `processed_date`, `approved_by`, `processed_by`, `created_at`, `updated_at`) VALUES
('PR-694dc050dbc9a', 'EMP001', 'PP001', 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 'Calculated', '2025-12-25 22:53:04', NULL, NULL, NULL, NULL, '2025-12-25 22:53:04', '2025-12-25 22:53:04'),
('PR-694dc050dd2f4', 'EMP002', 'PP001', 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 'Calculated', '2025-12-25 22:53:04', NULL, NULL, NULL, NULL, '2025-12-25 22:53:04', '2025-12-25 22:53:04'),
('PR-694dc050df008', 'EMP003', 'PP001', 72.00, 0.00, 22500.00, 0.00, 816.75, 337.50, 450.00, 0.00, 20895.75, 'Calculated', '2025-12-25 22:53:04', NULL, NULL, NULL, NULL, '2025-12-25 22:53:04', '2025-12-25 22:53:04'),
('PR-694dc050dfdbd', 'EMP004', 'PP001', 53.50, 26.50, 21656.25, 0.00, 786.12, 324.84, 433.13, 0.00, 20112.16, 'Calculated', '2025-12-25 22:53:04', NULL, NULL, NULL, NULL, '2025-12-25 22:53:04', '2025-12-25 22:53:04'),
('PR-694dc050e0b93', 'EMP005', 'PP001', 36.00, 0.00, 9900.00, 0.00, 359.37, 148.50, 198.00, 0.00, 9194.13, 'Calculated', '2025-12-25 22:53:04', NULL, NULL, NULL, NULL, '2025-12-25 22:53:04', '2025-12-25 22:53:04'),
('PR-694dc050e1a8b', 'EMP006', 'PP001', 26.75, 8.75, 10835.16, 0.00, 393.32, 162.53, 216.70, 0.00, 10062.61, 'Calculated', '2025-12-25 22:53:04', NULL, NULL, NULL, NULL, '2025-12-25 22:53:04', '2025-12-25 22:53:04'),
('PR-694dc050e2791', 'EMP007', 'PP001', 17.50, 8.50, 7382.81, 0.00, 268.00, 110.74, 147.66, 0.00, 6856.42, 'Calculated', '2025-12-25 22:53:04', NULL, NULL, NULL, NULL, '2025-12-25 22:53:04', '2025-12-25 22:53:04'),
('PR-694dc050e3405', 'EMP008', 'PP001', 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 'Calculated', '2025-12-25 22:53:04', NULL, NULL, NULL, NULL, '2025-12-25 22:53:04', '2025-12-25 22:53:04'),
('PR-694dc050e41c7', 'EMP009', 'PP001', 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 'Calculated', '2025-12-25 22:53:04', NULL, NULL, NULL, NULL, '2025-12-25 22:53:04', '2025-12-25 22:53:04'),
('PR-694dc050e4ebf', 'EMP010', 'PP001', 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 'Calculated', '2025-12-25 22:53:04', NULL, NULL, NULL, NULL, '2025-12-25 22:53:04', '2025-12-25 22:53:04'),
('PR-694dc050e5be0', 'EMP011', 'PP001', 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 'Calculated', '2025-12-25 22:53:04', NULL, NULL, NULL, NULL, '2025-12-25 22:53:04', '2025-12-25 22:53:04'),
('PR-694dc050e682d', 'EMP012', 'PP001', 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 'Calculated', '2025-12-25 22:53:04', NULL, NULL, NULL, NULL, '2025-12-25 22:53:04', '2025-12-25 22:53:04'),
('PR-694dc050e757c', 'EMP013', 'PP001', 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 'Calculated', '2025-12-25 22:53:04', NULL, NULL, NULL, NULL, '2025-12-25 22:53:04', '2025-12-25 22:53:04'),
('PR-694dc050e827a', 'EMP014', 'PP001', 17.92, 8.92, 6539.08, 0.00, 237.37, 98.09, 130.78, 0.00, 6072.84, 'Calculated', '2025-12-25 22:53:04', NULL, NULL, NULL, NULL, '2025-12-25 22:53:04', '2025-12-25 22:53:04'),
('PR-694dc050e8f33', 'EMP015', 'PP001', 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 'Calculated', '2025-12-25 22:53:04', NULL, NULL, NULL, NULL, '2025-12-25 22:53:04', '2025-12-25 22:53:04'),
('PR-694dc050e9c06', 'EMP016', 'PP001', 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 'Calculated', '2025-12-25 22:53:04', NULL, NULL, NULL, NULL, '2025-12-25 22:53:04', '2025-12-25 22:53:04'),
('PR-694dc050ea921', 'EMP017', 'PP001', 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 'Calculated', '2025-12-25 22:53:04', NULL, NULL, NULL, NULL, '2025-12-25 22:53:04', '2025-12-25 22:53:04'),
('PR-694dc050eb62f', 'EMP018', 'PP001', 26.75, 8.75, 12719.53, 0.00, 461.72, 190.79, 254.39, 0.00, 11812.63, 'Calculated', '2025-12-25 22:53:04', NULL, NULL, NULL, NULL, '2025-12-25 22:53:04', '2025-12-25 22:53:04'),
('PR-694dc050ec31a', 'EMP019', 'PP001', 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 'Calculated', '2025-12-25 22:53:04', NULL, NULL, NULL, NULL, '2025-12-25 22:53:04', '2025-12-25 22:53:04'),
('PR-694dc050eceff', 'EMP020', 'PP001', 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 'Calculated', '2025-12-25 22:53:04', NULL, NULL, NULL, NULL, '2025-12-25 22:53:04', '2025-12-25 22:53:04'),
('PR-694dc050edc85', 'EMP021', 'PP001', 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 'Calculated', '2025-12-25 22:53:04', NULL, NULL, NULL, NULL, '2025-12-25 22:53:04', '2025-12-25 22:53:04'),
('PR-694dc050ee92d', 'EMP022', 'PP001', 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 'Calculated', '2025-12-25 22:53:04', NULL, NULL, NULL, NULL, '2025-12-25 22:53:04', '2025-12-25 22:53:04'),
('PR-694dc050ef539', 'EMP023', 'PP001', 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 'Calculated', '2025-12-25 22:53:04', NULL, NULL, NULL, NULL, '2025-12-25 22:53:04', '2025-12-25 22:53:04'),
('PR-694dc050f0276', 'EMP024', 'PP001', 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 'Calculated', '2025-12-25 22:53:04', NULL, NULL, NULL, NULL, '2025-12-25 22:53:04', '2025-12-25 22:53:04'),
('PR-694dc050f0fbc', 'EMP025', 'PP001', 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 'Calculated', '2025-12-25 22:53:04', NULL, NULL, NULL, NULL, '2025-12-25 22:53:04', '2025-12-25 22:53:04'),
('PR-694dc050f1ce7', 'EMP026', 'PP001', 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 'Calculated', '2025-12-25 22:53:04', NULL, NULL, NULL, NULL, '2025-12-25 22:53:04', '2025-12-25 22:53:04'),
('PR-694dc050f29d3', 'EMP027', 'PP001', 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 'Calculated', '2025-12-25 22:53:04', NULL, NULL, NULL, NULL, '2025-12-25 22:53:04', '2025-12-25 22:53:04'),
('PR-694dc050f3587', 'EMP028', 'PP001', 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 'Calculated', '2025-12-25 22:53:04', NULL, NULL, NULL, NULL, '2025-12-25 22:53:04', '2025-12-25 22:53:04'),
('PR-694dc050f41c4', 'EMP029', 'PP001', 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 'Calculated', '2025-12-25 22:53:04', NULL, NULL, NULL, NULL, '2025-12-25 22:53:04', '2025-12-25 22:53:04'),
('PR-694dc05100b60', 'EMP030', 'PP001', 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 'Calculated', '2025-12-25 22:53:05', NULL, NULL, NULL, NULL, '2025-12-25 22:53:05', '2025-12-25 22:53:05');

-- --------------------------------------------------------

--
-- Table structure for table `payroll_config`
--

DROP TABLE IF EXISTS `payroll_config`;
CREATE TABLE `payroll_config` (
  `config_id` varchar(20) NOT NULL,
  `config_name` varchar(100) NOT NULL,
  `config_value` text DEFAULT NULL,
  `config_type` enum('Tax','Contribution','Overtime','Allowance','Deduction') NOT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `payroll_config`
--

INSERT INTO `payroll_config` (`config_id`, `config_name`, `config_value`, `config_type`, `is_active`, `created_at`, `updated_at`) VALUES
('CFG001', 'SSS Contribution Rate', '0.0363', 'Contribution', 1, '2025-12-22 05:21:39', '2025-12-22 05:21:39'),
('CFG002', 'PhilHealth Contribution Rate', '0.015', 'Contribution', 1, '2025-12-22 05:21:39', '2025-12-22 05:21:39'),
('CFG003', 'PagIBIG Contribution Rate', '0.02', 'Contribution', 1, '2025-12-22 05:21:39', '2025-12-22 05:21:39'),
('CFG004', 'Overtime Multiplier', '1.25', 'Overtime', 1, '2025-12-22 05:21:39', '2025-12-22 05:21:39');

-- --------------------------------------------------------

--
-- Table structure for table `payroll_periods`
--

DROP TABLE IF EXISTS `payroll_periods`;
CREATE TABLE `payroll_periods` (
  `period_id` varchar(20) NOT NULL,
  `period_name` varchar(100) DEFAULT NULL,
  `period_start` date NOT NULL,
  `period_end` date NOT NULL,
  `pay_date` date NOT NULL,
  `status` enum('Open','Closed','Processed') DEFAULT 'Open',
  `processed_date` timestamp NULL DEFAULT NULL,
  `processed_by` varchar(20) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `remarks` text DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `payroll_periods`
--

INSERT INTO `payroll_periods` (`period_id`, `period_name`, `period_start`, `period_end`, `pay_date`, `status`, `processed_date`, `processed_by`, `created_at`, `remarks`, `updated_at`) VALUES
('PP001', 'Jan 1 - Jan 15 Period', '2025-01-01', '2025-01-15', '2025-01-20', 'Open', NULL, NULL, '2025-12-25 22:52:04', 'First half of January payroll', '2025-12-25 22:52:40');

-- --------------------------------------------------------

--
-- Table structure for table `projects`
--

DROP TABLE IF EXISTS `projects`;
CREATE TABLE `projects` (
  `project_id` varchar(20) NOT NULL,
  `project_name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `status` enum('Planning','In Progress','On Hold','Completed','Cancelled') DEFAULT 'Planning',
  `project_manager_id` varchar(20) DEFAULT NULL,
  `budget` decimal(12,2) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `projects`
--

INSERT INTO `projects` (`project_id`, `project_name`, `description`, `start_date`, `end_date`, `status`, `project_manager_id`, `budget`, `created_at`, `updated_at`) VALUES
('PRJ001', 'Skyline Tower', 'Construction of a 30-story commercial building', '2024-01-15', '2025-01-15', 'In Progress', 'EMP001', 50000000.00, '2025-12-25 22:16:09', '2025-12-25 22:16:09'),
('PRJ002', 'Greenfield Residences', 'Residential housing development', '2024-02-01', '2025-02-01', 'In Progress', 'EMP029', 30000000.00, '2025-12-25 22:16:09', '2025-12-25 22:16:09'),
('PRJ003', 'Sunset Mall Expansion', 'Expansion of existing shopping mall', '2024-03-05', '2024-12-20', 'Planning', 'EMP001', 20000000.00, '2025-12-25 22:16:09', '2025-12-25 22:16:09'),
('PRJ004', 'Riverside Bridge', 'Bridge construction over river', '2024-04-10', '2025-06-30', 'In Progress', 'EMP012', 15000000.00, '2025-12-25 22:16:09', '2025-12-25 22:16:09'),
('PRJ005', 'Central Park Landscaping', 'Park and recreational area development', '2024-05-01', '2024-11-30', 'Completed', 'EMP028', 8000000.00, '2025-12-25 22:16:09', '2025-12-25 22:16:09'),
('PRJ006', 'Tech Hub Office', 'Construction of tech company offices', '2024-06-15', '2025-01-15', 'In Progress', 'EMP029', 12000000.00, '2025-12-25 22:16:09', '2025-12-25 22:16:09'),
('PRJ007', 'Harbor Warehouse', 'New warehouse facility at the harbor', '2024-07-01', '2024-12-31', 'Planning', 'EMP001', 10000000.00, '2025-12-25 22:16:09', '2025-12-25 22:16:09'),
('PRJ008', 'Lakeside Condos', 'Luxury condominium project', '2024-08-05', '2025-08-05', 'In Progress', 'EMP029', 25000000.00, '2025-12-25 22:16:09', '2025-12-25 22:16:09'),
('PRJ009', 'City Hospital Wing', 'Additional wing for city hospital', '2024-09-01', '2025-03-01', 'Planning', 'EMP011', 18000000.00, '2025-12-25 22:16:09', '2025-12-25 22:16:09'),
('PRJ010', 'Industrial Park', 'Development of industrial units', '2024-10-01', '2025-07-15', 'In Progress', 'EMP001', 40000000.00, '2025-12-25 22:16:09', '2025-12-25 22:16:09'),
('PRJ011', 'Mountain Resort', 'Construction of mountain resort facilities', '2024-11-05', '2025-05-30', 'Planning', 'EMP029', 22000000.00, '2025-12-25 22:16:09', '2025-12-25 22:16:09'),
('PRJ012', 'Riverside Apartments', 'Mid-rise apartment project', '2024-12-01', '2025-11-30', 'In Progress', 'EMP012', 27000000.00, '2025-12-25 22:16:09', '2025-12-25 22:16:09'),
('PRJ013', 'City Sports Complex', 'Development of sports complex', '2025-01-10', '2025-12-15', 'On Hold', 'EMP028', 15000000.00, '2025-12-25 22:16:09', '2025-12-25 22:16:09'),
('PRJ014', 'Eastside Mall', 'New shopping mall construction', '2025-02-15', '2025-12-31', 'Planning', 'EMP001', 35000000.00, '2025-12-25 22:16:09', '2025-12-25 22:16:09'),
('PRJ015', 'University Science Building', 'Construction of science labs and classrooms', '2025-03-01', '2025-12-15', 'In Progress', 'EMP029', 20000000.00, '2025-12-25 22:16:09', '2025-12-25 22:16:09');

-- --------------------------------------------------------

--
-- Table structure for table `project_phases`
--

DROP TABLE IF EXISTS `project_phases`;
CREATE TABLE `project_phases` (
  `phase_id` varchar(20) NOT NULL,
  `project_id` varchar(20) DEFAULT NULL,
  `phase_name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `status` enum('Not Started','In Progress','Completed') DEFAULT 'Not Started',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `priority` enum('Low','Medium','High','Critical') DEFAULT 'Medium',
  `budget` decimal(12,2) DEFAULT 0.00,
  `actual_spent` decimal(12,2) DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `project_phases`
--

INSERT INTO `project_phases` (`phase_id`, `project_id`, `phase_name`, `description`, `start_date`, `end_date`, `status`, `created_at`, `updated_at`, `priority`, `budget`, `actual_spent`) VALUES
('PH001', 'PRJ001', 'Foundation', 'Excavation and foundation work', '2024-01-15', '2024-03-15', 'Completed', '2025-12-25 22:18:32', '2025-12-25 22:18:32', 'High', 8000000.00, 7900000.00),
('PH002', 'PRJ001', 'Structure', 'Main structure construction', '2024-03-16', '2024-09-15', 'In Progress', '2025-12-25 22:18:32', '2025-12-25 22:18:32', 'Critical', 20000000.00, 15000000.00),
('PH003', 'PRJ001', 'Finishing', 'Interior and exterior finishing', '2024-09-16', '2025-01-15', 'Not Started', '2025-12-25 22:18:32', '2025-12-25 22:18:32', 'Medium', 22000000.00, 0.00),
('PH004', 'PRJ002', 'Site Preparation', 'Clearing and leveling', '2024-02-01', '2024-04-01', 'Completed', '2025-12-25 22:18:32', '2025-12-25 22:18:32', 'High', 5000000.00, 4800000.00),
('PH005', 'PRJ002', 'Construction', 'Building residential units', '2024-04-02', '2024-12-15', 'In Progress', '2025-12-25 22:18:32', '2025-12-25 22:18:32', 'Critical', 18000000.00, 12000000.00),
('PH006', 'PRJ002', 'Landscaping', 'Gardens and amenities', '2024-12-16', '2025-02-01', 'Not Started', '2025-12-25 22:18:32', '2025-12-25 22:18:32', 'Medium', 7000000.00, 0.00),
('PH007', 'PRJ003', 'Planning', 'Design and permits', '2024-03-05', '2024-05-01', 'Completed', '2025-12-25 22:18:32', '2025-12-25 22:18:32', 'Medium', 3000000.00, 2900000.00),
('PH008', 'PRJ003', 'Construction', 'Structural expansion', '2024-05-02', '2024-11-30', 'Not Started', '2025-12-25 22:18:32', '2025-12-25 22:18:32', 'High', 12000000.00, 0.00),
('PH009', 'PRJ003', 'Finishing', 'Interiors and systems', '2024-12-01', '2024-12-20', 'Not Started', '2025-12-25 22:18:32', '2025-12-25 22:18:32', 'Medium', 5000000.00, 0.00),
('PH010', 'PRJ004', 'Design', 'Bridge design and approvals', '2024-04-10', '2024-06-10', 'Completed', '2025-12-25 22:18:32', '2025-12-25 22:18:32', 'High', 2000000.00, 1800000.00),
('PH011', 'PRJ004', 'Construction', 'Bridge pillars and deck', '2024-06-11', '2025-03-15', 'In Progress', '2025-12-25 22:18:32', '2025-12-25 22:18:32', 'Critical', 10000000.00, 6000000.00),
('PH012', 'PRJ004', 'Finishing', 'Road surface and safety systems', '2025-03-16', '2025-06-30', 'Not Started', '2025-12-25 22:18:32', '2025-12-25 22:18:32', 'Medium', 3000000.00, 0.00),
('PH013', 'PRJ005', 'Design', 'Park layout and features', '2024-05-01', '2024-06-01', 'Completed', '2025-12-25 22:18:32', '2025-12-25 22:18:32', 'Medium', 2000000.00, 1950000.00),
('PH014', 'PRJ005', 'Groundwork', 'Soil, planting, and irrigation', '2024-06-02', '2024-10-15', 'Completed', '2025-12-25 22:18:32', '2025-12-25 22:18:32', 'High', 4000000.00, 3900000.00),
('PH015', 'PRJ005', 'Amenities', 'Benches, paths, and lighting', '2024-10-16', '2024-11-30', 'Completed', '2025-12-25 22:18:32', '2025-12-25 22:18:32', 'Medium', 2000000.00, 1980000.00),
('PH016', 'PRJ006', 'Planning', 'Design and permits', '2024-06-15', '2024-08-01', 'Completed', '2025-12-25 22:18:32', '2025-12-25 22:18:32', 'Medium', 2000000.00, 1950000.00),
('PH017', 'PRJ006', 'Construction', 'Office construction', '2024-08-02', '2024-12-15', 'In Progress', '2025-12-25 22:18:32', '2025-12-25 22:18:32', 'High', 8000000.00, 4000000.00),
('PH018', 'PRJ006', 'Finishing', 'Interiors and fit-out', '2024-12-16', '2025-01-15', 'Not Started', '2025-12-25 22:18:32', '2025-12-25 22:18:32', 'Medium', 2000000.00, 0.00),
('PH019', 'PRJ007', 'Planning', 'Site evaluation and permits', '2024-07-01', '2024-08-01', 'Completed', '2025-12-25 22:18:32', '2025-12-25 22:18:32', 'Medium', 1000000.00, 950000.00),
('PH020', 'PRJ007', 'Construction', 'Warehouse construction', '2024-08-02', '2024-12-15', 'Not Started', '2025-12-25 22:18:32', '2025-12-25 22:18:32', 'High', 7000000.00, 0.00),
('PH021', 'PRJ007', 'Finishing', 'Interior setup and utilities', '2024-12-16', '2024-12-31', 'Not Started', '2025-12-25 22:18:32', '2025-12-25 22:18:32', 'Medium', 2000000.00, 0.00),
('PH022', 'PRJ008', 'Planning', 'Design and permits', '2024-08-05', '2024-10-01', 'Completed', '2025-12-25 22:18:32', '2025-12-25 22:18:32', 'High', 4000000.00, 3800000.00),
('PH023', 'PRJ008', 'Construction', 'Building condos', '2024-10-02', '2025-06-30', 'In Progress', '2025-12-25 22:18:32', '2025-12-25 22:18:32', 'Critical', 15000000.00, 8000000.00),
('PH024', 'PRJ008', 'Finishing', 'Interiors and landscaping', '2025-07-01', '2025-08-05', 'Not Started', '2025-12-25 22:18:32', '2025-12-25 22:18:32', 'Medium', 6000000.00, 0.00),
('PH025', 'PRJ009', 'Planning', 'Design and approvals', '2024-09-01', '2024-10-15', 'Completed', '2025-12-25 22:18:32', '2025-12-25 22:18:32', 'High', 3000000.00, 2900000.00),
('PH026', 'PRJ009', 'Construction', 'Wing structure', '2024-10-16', '2025-02-28', 'Not Started', '2025-12-25 22:18:32', '2025-12-25 22:18:32', 'Critical', 12000000.00, 0.00),
('PH027', 'PRJ009', 'Finishing', 'Interiors and systems', '2025-03-01', '2025-03-01', 'Not Started', '2025-12-25 22:18:32', '2025-12-25 22:18:32', 'Medium', 3000000.00, 0.00),
('PH028', 'PRJ010', 'Planning', 'Layout and zoning', '2024-10-01', '2024-12-01', 'Completed', '2025-12-25 22:18:32', '2025-12-25 22:18:32', 'Medium', 5000000.00, 4900000.00),
('PH029', 'PRJ010', 'Construction', 'Industrial units', '2024-12-02', '2025-06-30', 'In Progress', '2025-12-25 22:18:32', '2025-12-25 22:18:32', 'Critical', 25000000.00, 12000000.00),
('PH030', 'PRJ010', 'Infrastructure', 'Roads, utilities, and services', '2025-07-01', '2025-07-15', 'Not Started', '2025-12-25 22:18:32', '2025-12-25 22:18:32', 'High', 10000000.00, 0.00),
('PH031', 'PRJ011', 'Planning', 'Design and permits', '2024-11-05', '2024-12-15', 'Completed', '2025-12-25 22:18:32', '2025-12-25 22:18:32', 'Medium', 3000000.00, 2900000.00),
('PH032', 'PRJ011', 'Construction', 'Resort building', '2024-12-16', '2025-04-30', 'Not Started', '2025-12-25 22:18:32', '2025-12-25 22:18:32', 'High', 15000000.00, 0.00),
('PH033', 'PRJ011', 'Finishing', 'Interiors and landscaping', '2025-05-01', '2025-05-30', 'Not Started', '2025-12-25 22:18:32', '2025-12-25 22:18:32', 'Medium', 4000000.00, 0.00),
('PH034', 'PRJ012', 'Planning', 'Design and approvals', '2024-12-01', '2025-02-01', 'Completed', '2025-12-25 22:18:32', '2025-12-25 22:18:32', 'Medium', 4000000.00, 3900000.00),
('PH035', 'PRJ012', 'Construction', 'Apartment units', '2025-02-02', '2025-09-30', 'In Progress', '2025-12-25 22:18:32', '2025-12-25 22:18:32', 'High', 20000000.00, 12000000.00),
('PH036', 'PRJ012', 'Finishing', 'Landscaping and interiors', '2025-10-01', '2025-11-30', 'Not Started', '2025-12-25 22:18:32', '2025-12-25 22:18:32', 'Medium', 3000000.00, 0.00),
('PH037', 'PRJ013', 'Planning', 'Design and permits', '2025-01-10', '2025-03-01', 'Not Started', '2025-12-25 22:18:32', '2025-12-25 22:18:32', 'Medium', 2000000.00, 0.00),
('PH038', 'PRJ013', 'Construction', 'Sports facilities', '2025-03-02', '2025-10-31', 'Not Started', '2025-12-25 22:18:32', '2025-12-25 22:18:32', 'High', 10000000.00, 0.00),
('PH039', 'PRJ013', 'Finishing', 'Landscaping and amenities', '2025-11-01', '2025-12-15', 'Not Started', '2025-12-25 22:18:32', '2025-12-25 22:18:32', 'Medium', 3000000.00, 0.00),
('PH040', 'PRJ014', 'Planning', 'Design and permits', '2025-02-15', '2025-05-01', 'Not Started', '2025-12-25 22:18:32', '2025-12-25 22:18:32', 'Medium', 5000000.00, 0.00),
('PH041', 'PRJ014', 'Construction', 'Mall structure', '2025-05-02', '2025-11-30', 'Not Started', '2025-12-25 22:18:32', '2025-12-25 22:18:32', 'Critical', 25000000.00, 0.00),
('PH042', 'PRJ014', 'Finishing', 'Interiors and systems', '2025-12-01', '2025-12-31', 'Not Started', '2025-12-25 22:18:32', '2025-12-25 22:18:32', 'High', 5000000.00, 0.00),
('PH043', 'PRJ015', 'Planning', 'Design and permits', '2025-03-01', '2025-05-01', 'Not Started', '2025-12-25 22:18:32', '2025-12-25 22:18:32', 'Medium', 3000000.00, 0.00),
('PH044', 'PRJ015', 'Construction', 'Science labs and classrooms', '2025-05-02', '2025-11-30', 'In Progress', '2025-12-25 22:18:32', '2025-12-25 22:18:32', 'Critical', 15000000.00, 8000000.00),
('PH045', 'PRJ015', 'Finishing', 'Interiors and installations', '2025-12-01', '2025-12-15', 'Not Started', '2025-12-25 22:18:32', '2025-12-25 22:18:32', 'High', 2000000.00, 0.00);

-- --------------------------------------------------------

--
-- Table structure for table `skills`
--

DROP TABLE IF EXISTS `skills`;
CREATE TABLE `skills` (
  `skill_id` varchar(20) NOT NULL,
  `skill_name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `category` varchar(50) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `skills`
--

INSERT INTO `skills` (`skill_id`, `skill_name`, `description`, `category`, `is_active`, `created_at`, `updated_at`) VALUES
('SKL001', 'Project Management', NULL, 'Management', 1, '2025-12-22 05:21:39', '2025-12-22 05:21:39'),
('SKL002', 'JavaScript', NULL, 'Programming', 1, '2025-12-22 05:21:39', '2025-12-22 05:21:39'),
('SKL003', 'Masonry', NULL, 'Construction', 1, '2025-12-22 05:21:39', '2025-12-22 05:21:39'),
('SKL004', 'Human Resources', NULL, 'Administration', 1, '2025-12-22 05:21:39', '2025-12-22 05:21:39');

-- --------------------------------------------------------

--
-- Table structure for table `tasks`
--

DROP TABLE IF EXISTS `tasks`;
CREATE TABLE `tasks` (
  `task_id` varchar(20) NOT NULL,
  `task_name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `estimated_hours` decimal(5,2) DEFAULT NULL,
  `department` varchar(50) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `assignments`
--
ALTER TABLE `assignments`
  ADD PRIMARY KEY (`assignment_id`),
  ADD KEY `phase_id` (`phase_id`),
  ADD KEY `assigned_by` (`assigned_by`),
  ADD KEY `idx_assignment_employee` (`employee_id`),
  ADD KEY `idx_assignment_project` (`project_id`);

--
-- Indexes for table `attendance`
--
ALTER TABLE `attendance`
  ADD PRIMARY KEY (`attendance_id`),
  ADD UNIQUE KEY `unique_attendance` (`employee_id`,`attendance_date`),
  ADD KEY `project_id` (`project_id`),
  ADD KEY `recorded_by` (`recorded_by`),
  ADD KEY `idx_attendance_date` (`attendance_date`),
  ADD KEY `idx_attendance_employee` (`employee_id`);

--
-- Indexes for table `audit_logs`
--
ALTER TABLE `audit_logs`
  ADD PRIMARY KEY (`log_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `employees`
--
ALTER TABLE `employees`
  ADD PRIMARY KEY (`employee_id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `idx_employee_status` (`status`);

--
-- Indexes for table `employee_groups`
--
ALTER TABLE `employee_groups`
  ADD PRIMARY KEY (`group_id`),
  ADD KEY `group_leader_id` (`group_leader_id`);

--
-- Indexes for table `employee_skills`
--
ALTER TABLE `employee_skills`
  ADD PRIMARY KEY (`employee_skill_id`),
  ADD KEY `employee_id` (`employee_id`),
  ADD KEY `skill_id` (`skill_id`);

--
-- Indexes for table `group_assignments`
--
ALTER TABLE `group_assignments`
  ADD PRIMARY KEY (`group_assignment_id`),
  ADD KEY `project_id` (`project_id`),
  ADD KEY `phase_id` (`phase_id`),
  ADD KEY `assigned_by` (`assigned_by`),
  ADD KEY `group_assignments_ibfk_1` (`group_id`);

--
-- Indexes for table `group_memberships`
--
ALTER TABLE `group_memberships`
  ADD PRIMARY KEY (`membership_id`),
  ADD KEY `employee_id` (`employee_id`),
  ADD KEY `group_id` (`group_id`);

--
-- Indexes for table `job_titles`
--
ALTER TABLE `job_titles`
  ADD PRIMARY KEY (`job_title_id`);

--
-- Indexes for table `leave_balances`
--
ALTER TABLE `leave_balances`
  ADD PRIMARY KEY (`balance_id`),
  ADD KEY `employee_id` (`employee_id`),
  ADD KEY `leave_type_id` (`leave_type_id`);

--
-- Indexes for table `leave_requests`
--
ALTER TABLE `leave_requests`
  ADD PRIMARY KEY (`leave_request_id`),
  ADD KEY `leave_type_id` (`leave_type_id`),
  ADD KEY `reviewed_by` (`reviewed_by`),
  ADD KEY `idx_leave_request_employee` (`employee_id`),
  ADD KEY `idx_leave_request_status` (`status`);

--
-- Indexes for table `leave_types`
--
ALTER TABLE `leave_types`
  ADD PRIMARY KEY (`leave_type_id`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`notification_id`),
  ADD KEY `sender_id` (`sender_id`),
  ADD KEY `idx_notification_recipient` (`recipient_id`),
  ADD KEY `idx_notification_unread` (`recipient_id`,`is_read`);

--
-- Indexes for table `payroll`
--
ALTER TABLE `payroll`
  ADD PRIMARY KEY (`payroll_id`),
  ADD UNIQUE KEY `uniq_period_employee` (`period_id`,`employee_id`),
  ADD KEY `employee_id` (`employee_id`),
  ADD KEY `approved_by` (`approved_by`),
  ADD KEY `processed_by` (`processed_by`),
  ADD KEY `idx_payroll_period` (`period_id`);

--
-- Indexes for table `payroll_config`
--
ALTER TABLE `payroll_config`
  ADD PRIMARY KEY (`config_id`);

--
-- Indexes for table `payroll_periods`
--
ALTER TABLE `payroll_periods`
  ADD PRIMARY KEY (`period_id`);

--
-- Indexes for table `projects`
--
ALTER TABLE `projects`
  ADD PRIMARY KEY (`project_id`),
  ADD KEY `project_manager_id` (`project_manager_id`),
  ADD KEY `idx_project_status` (`status`);

--
-- Indexes for table `project_phases`
--
ALTER TABLE `project_phases`
  ADD PRIMARY KEY (`phase_id`),
  ADD KEY `project_id` (`project_id`);

--
-- Indexes for table `skills`
--
ALTER TABLE `skills`
  ADD PRIMARY KEY (`skill_id`);

--
-- Indexes for table `tasks`
--
ALTER TABLE `tasks`
  ADD PRIMARY KEY (`task_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `attendance`
--
ALTER TABLE `attendance`
  MODIFY `attendance_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=99;

--
-- AUTO_INCREMENT for table `audit_logs`
--
ALTER TABLE `audit_logs`
  MODIFY `log_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `employee_skills`
--
ALTER TABLE `employee_skills`
  MODIFY `employee_skill_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `group_memberships`
--
ALTER TABLE `group_memberships`
  MODIFY `membership_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `leave_balances`
--
ALTER TABLE `leave_balances`
  MODIFY `balance_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `assignments`
--
ALTER TABLE `assignments`
  ADD CONSTRAINT `assignments_ibfk_1` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`employee_id`),
  ADD CONSTRAINT `assignments_ibfk_2` FOREIGN KEY (`project_id`) REFERENCES `projects` (`project_id`),
  ADD CONSTRAINT `assignments_ibfk_3` FOREIGN KEY (`phase_id`) REFERENCES `project_phases` (`phase_id`),
  ADD CONSTRAINT `assignments_ibfk_4` FOREIGN KEY (`assigned_by`) REFERENCES `employees` (`employee_id`);

--
-- Constraints for table `attendance`
--
ALTER TABLE `attendance`
  ADD CONSTRAINT `attendance_ibfk_1` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`employee_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `attendance_ibfk_2` FOREIGN KEY (`project_id`) REFERENCES `projects` (`project_id`),
  ADD CONSTRAINT `attendance_ibfk_3` FOREIGN KEY (`recorded_by`) REFERENCES `employees` (`employee_id`);

--
-- Constraints for table `audit_logs`
--
ALTER TABLE `audit_logs`
  ADD CONSTRAINT `audit_logs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `employees` (`employee_id`);

--
-- Constraints for table `employee_groups`
--
ALTER TABLE `employee_groups`
  ADD CONSTRAINT `employee_groups_ibfk_1` FOREIGN KEY (`group_leader_id`) REFERENCES `employees` (`employee_id`);

--
-- Constraints for table `employee_skills`
--
ALTER TABLE `employee_skills`
  ADD CONSTRAINT `employee_skills_ibfk_1` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`employee_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `employee_skills_ibfk_2` FOREIGN KEY (`skill_id`) REFERENCES `skills` (`skill_id`) ON DELETE CASCADE;

--
-- Constraints for table `group_assignments`
--
ALTER TABLE `group_assignments`
  ADD CONSTRAINT `group_assignments_ibfk_1` FOREIGN KEY (`group_id`) REFERENCES `employee_groups` (`group_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `group_assignments_ibfk_2` FOREIGN KEY (`project_id`) REFERENCES `projects` (`project_id`),
  ADD CONSTRAINT `group_assignments_ibfk_3` FOREIGN KEY (`phase_id`) REFERENCES `project_phases` (`phase_id`),
  ADD CONSTRAINT `group_assignments_ibfk_4` FOREIGN KEY (`assigned_by`) REFERENCES `employees` (`employee_id`);

--
-- Constraints for table `group_memberships`
--
ALTER TABLE `group_memberships`
  ADD CONSTRAINT `group_memberships_ibfk_1` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`employee_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `group_memberships_ibfk_2` FOREIGN KEY (`group_id`) REFERENCES `employee_groups` (`group_id`) ON DELETE CASCADE;

--
-- Constraints for table `leave_balances`
--
ALTER TABLE `leave_balances`
  ADD CONSTRAINT `leave_balances_ibfk_1` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`employee_id`),
  ADD CONSTRAINT `leave_balances_ibfk_2` FOREIGN KEY (`leave_type_id`) REFERENCES `leave_types` (`leave_type_id`);

--
-- Constraints for table `leave_requests`
--
ALTER TABLE `leave_requests`
  ADD CONSTRAINT `leave_requests_ibfk_1` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`employee_id`),
  ADD CONSTRAINT `leave_requests_ibfk_2` FOREIGN KEY (`leave_type_id`) REFERENCES `leave_types` (`leave_type_id`),
  ADD CONSTRAINT `leave_requests_ibfk_3` FOREIGN KEY (`reviewed_by`) REFERENCES `employees` (`employee_id`);

--
-- Constraints for table `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`recipient_id`) REFERENCES `employees` (`employee_id`),
  ADD CONSTRAINT `notifications_ibfk_2` FOREIGN KEY (`sender_id`) REFERENCES `employees` (`employee_id`);

--
-- Constraints for table `payroll`
--
ALTER TABLE `payroll`
  ADD CONSTRAINT `payroll_ibfk_1` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`employee_id`),
  ADD CONSTRAINT `payroll_ibfk_2` FOREIGN KEY (`period_id`) REFERENCES `payroll_periods` (`period_id`),
  ADD CONSTRAINT `payroll_ibfk_3` FOREIGN KEY (`approved_by`) REFERENCES `employees` (`employee_id`),
  ADD CONSTRAINT `payroll_ibfk_4` FOREIGN KEY (`processed_by`) REFERENCES `employees` (`employee_id`);

--
-- Constraints for table `projects`
--
ALTER TABLE `projects`
  ADD CONSTRAINT `projects_ibfk_1` FOREIGN KEY (`project_manager_id`) REFERENCES `employees` (`employee_id`);

--
-- Constraints for table `project_phases`
--
ALTER TABLE `project_phases`
  ADD CONSTRAINT `project_phases_ibfk_1` FOREIGN KEY (`project_id`) REFERENCES `projects` (`project_id`) ON DELETE CASCADE;
COMMIT;

-- RE-ENABLE FOREIGN KEY CHECKS
SET FOREIGN_KEY_CHECKS = 1;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;