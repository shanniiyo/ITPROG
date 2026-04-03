-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Generation Time: Apr 03, 2026 at 07:20 AM
-- Server version: 10.4.28-MariaDB
-- PHP Version: 8.2.4

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `locker_reservation`
--

-- --------------------------------------------------------

--
-- Table structure for table `admin_user`
--

CREATE TABLE `admin_user` (
  `admin_id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `full_name` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `locker_access`
--

CREATE TABLE `locker_access` (
  `access_id` int(11) NOT NULL,
  `rsvp_id` int(11) NOT NULL,
  `access_code` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `locker_rsvp`
--

CREATE TABLE `locker_rsvp` (
  `locker_id` int(11) NOT NULL,
  `size` enum('small','medium','large') NOT NULL,
  `location` varchar(100) NOT NULL,
  `status` enum('available','occupied','out_of_service') NOT NULL DEFAULT 'available',
  `is_active` tinyint(1) DEFAULT 1,
  `pricer_per_hr` decimal(5,2) NOT NULL DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `locker_rsvp`
--

INSERT INTO `locker_rsvp` (`locker_id`, `size`, `location`, `status`, `is_active`, `pricer_per_hr`) VALUES
(2, 'small', 'Manila', 'available', 1, 70.00);

-- --------------------------------------------------------

--
-- Table structure for table `payments`
--

CREATE TABLE `payments` (
  `payment_id` int(11) NOT NULL,
  `rsvp_id` int(11) NOT NULL,
  `amount_paid` decimal(10,2) NOT NULL,
  `payment_method` enum('card','ewallet') NOT NULL,
  `payment_status` enum('pending','paid','failed','refunded') DEFAULT 'pending',
  `transaction_ref` varchar(100) DEFAULT NULL,
  `rate_type` enum('hourly','daily') DEFAULT 'hourly',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `rsvp_details`
--

CREATE TABLE `rsvp_details` (
  `rsvp_id` int(11) NOT NULL,
  `locker_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `start_time` datetime NOT NULL,
  `end_time` datetime NOT NULL,
  `duration_select` int(11) NOT NULL,
  `extn_count` int(11) DEFAULT 0,
  `status` enum('active','completed','expired','cancelled') NOT NULL DEFAULT 'active',
  `penalty_fee` decimal(10,2) DEFAULT 0.00,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `user_id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `email` varchar(255) NOT NULL,
  `phone_number` varchar(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `username`, `password`, `full_name`, `email`, `phone_number`) VALUES
(1, 'shanjdipatuan@gmail.com', '$2y$10$2ygCK8nLYweeNk4S8rzfRusYR5z.yeokSbdR8A6HTlyI5fBnhDk5q', 'Shan Junaid Dipatuan', 'shanjdipatuan@gmail.com', '09167112520'),
(2, 'brandonvera@gmail.com', '$2y$10$0KkJ1TORxwlMTkxyCamCmuyQY/Jt/5LrE.wqVjMTvVc/gxKjuii1O', 'Brandon Vera', 'brandonvera@gmail.com', '09167112529'),
(3, 'johndoe@yahoo.com', '$2y$10$H43DQ5iV7OxprHGoSUDs1.D2ZEHl10fa78QI8TjI3RD24aZS.UDV6', 'John Doe', 'johndoe@yahoo.com', '09167112527');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admin_user`
--
ALTER TABLE `admin_user`
  ADD PRIMARY KEY (`admin_id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- Indexes for table `locker_access`
--
ALTER TABLE `locker_access`
  ADD PRIMARY KEY (`access_id`),
  ADD KEY `rsvp_id` (`rsvp_id`);

--
-- Indexes for table `locker_rsvp`
--
ALTER TABLE `locker_rsvp`
  ADD PRIMARY KEY (`locker_id`);

--
-- Indexes for table `payments`
--
ALTER TABLE `payments`
  ADD PRIMARY KEY (`payment_id`),
  ADD KEY `rsvp_id` (`rsvp_id`);

--
-- Indexes for table `rsvp_details`
--
ALTER TABLE `rsvp_details`
  ADD PRIMARY KEY (`rsvp_id`),
  ADD KEY `locker_id` (`locker_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admin_user`
--
ALTER TABLE `admin_user`
  MODIFY `admin_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `locker_access`
--
ALTER TABLE `locker_access`
  MODIFY `access_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `locker_rsvp`
--
ALTER TABLE `locker_rsvp`
  MODIFY `locker_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `payments`
--
ALTER TABLE `payments`
  MODIFY `payment_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `rsvp_details`
--
ALTER TABLE `rsvp_details`
  MODIFY `rsvp_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `locker_access`
--
ALTER TABLE `locker_access`
  ADD CONSTRAINT `fk_access_rsvp` FOREIGN KEY (`rsvp_id`) REFERENCES `rsvp_details` (`rsvp_id`) ON DELETE CASCADE;

--
-- Constraints for table `payments`
--
ALTER TABLE `payments`
  ADD CONSTRAINT `fk_payment_rsvp` FOREIGN KEY (`rsvp_id`) REFERENCES `rsvp_details` (`rsvp_id`) ON DELETE CASCADE;

--
-- Constraints for table `rsvp_details`
--
ALTER TABLE `rsvp_details`
  ADD CONSTRAINT `fk_locker` FOREIGN KEY (`locker_id`) REFERENCES `locker_rsvp` (`locker_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
