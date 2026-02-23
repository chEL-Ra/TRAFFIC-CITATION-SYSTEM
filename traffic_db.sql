-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Feb 05, 2026 at 06:05 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.1.25

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `traffic_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `offenses`
--

CREATE TABLE `offenses` (
  `id` int(11) NOT NULL,
  `driver_name` varchar(100) NOT NULL,
  `license_number` varchar(50) NOT NULL,
  `offense_type` varchar(100) NOT NULL,
  `fine_amount` decimal(10,2) NOT NULL,
  `offense_date` date NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `officers`
--

CREATE TABLE `officers` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `position` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `officers`
--

INSERT INTO `officers` (`id`, `name`, `position`) VALUES
(1, 'John Carter', 'officer'),
(2, 'Michael Smith', 'officer'),
(3, 'Emma Johnson', 'officer'),
(4, 'Olivia Brown', 'officer'),
(5, 'William Davis', 'officer'),
(6, 'Ava Wilson', 'officer'),
(7, 'James Miller', 'officer'),
(8, 'Sophia Moore', 'officer'),
(9, 'Benjamin Taylor', 'officer'),
(10, 'Isabella Anderson', 'officer');
-- --------------------------------------------------------

--
-- Table structure for table `payments`
--

CREATE TABLE `payments` (
  `id` int(11) NOT NULL,
  `offense_id` int(11) NOT NULL,
  `amount_paid` decimal(10,2) NOT NULL,
  `status` enum('Paid','Unpaid') DEFAULT 'Unpaid',
  `date_paid` date DEFAULT NULL,
  FOREIGN KEY (`offense_id`) REFERENCES `offenses`(`id`) 
) ;

--
-- Indexes for dumped tables
--
REATE TABLE `users` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  'username' varchar(50) NOT NULL,
  'pass' varchar(150) NOT NULL,
  `position` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
--
-- Indexes for table `offenses`
--
ALTER TABLE `offenses`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `officers`
--
ALTER TABLE `officers`
  ADD PRIMARY KEY (`id`);
  ADD KEY 'officers_id' ('officers_id')

--
-- Indexes for table `payments`
--
ALTER TABLE `payments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `offense_id` (`offense_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `offenses`
--
ALTER TABLE `offenses`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `officers`
--
ALTER TABLE `officers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `payments`
--
ALTER TABLE `payments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `payments`
--
ALTER TABLE `payments` 
ADD COLUMN IF NOT EXISTS `or_number` VARCHAR(50) NOT NULL AFTER `offense_id`,
ADD COLUMN IF NOT EXISTS `recorded_by` VARCHAR(100) NOT NULL AFTER `date_paid`;

-- Optional: Add index for faster searching
ALTER TABLE `payments` ADD UNIQUE IF NOT EXISTS (`or_number`);