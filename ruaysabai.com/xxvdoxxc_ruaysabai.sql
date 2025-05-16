-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: May 15, 2025 at 06:39 AM
-- Server version: 10.6.20-MariaDB-cll-lve-log
-- PHP Version: 8.1.32

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `xxvdoxxc_ruaysabai`
--

-- --------------------------------------------------------

--
-- Table structure for table `deposits`
--

CREATE TABLE `deposits` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `method` enum('bank','promptpay','truemoney') NOT NULL,
  `reference` varchar(50) NOT NULL,
  `bank_name` varchar(50) DEFAULT NULL,
  `bank_account` varchar(50) DEFAULT NULL,
  `transfer_date` date NOT NULL,
  `transfer_time` time NOT NULL,
  `status` enum('pending','success','failed') NOT NULL DEFAULT 'pending',
  `admin_id` int(11) DEFAULT NULL,
  `approved_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `deposits`
--

INSERT INTO `deposits` (`id`, `user_id`, `amount`, `method`, `reference`, `bank_name`, `bank_account`, `transfer_date`, `transfer_time`, `status`, `admin_id`, `approved_at`, `created_at`, `updated_at`) VALUES
(1, 2, 1000.00, 'bank', 'TX12345678', '??????????????', '123-4-56789-0', '2025-05-13', '09:15:42', 'success', 1, '2025-05-13 10:00:00', '2025-05-13 19:00:11', '2025-05-13 19:00:11'),
(2, 3, 500.00, 'promptpay', 'PP98765432', NULL, NULL, '2025-05-10', '14:30:22', 'success', 1, '2025-05-10 15:00:00', '2025-05-13 19:00:11', '2025-05-13 19:00:11');

-- --------------------------------------------------------

--
-- Table structure for table `lotteries`
--

CREATE TABLE `lotteries` (
  `id` int(11) NOT NULL,
  `date` date NOT NULL,
  `first_prize` varchar(6) DEFAULT NULL,
  `front_three` varchar(3) DEFAULT NULL,
  `back_three` varchar(3) DEFAULT NULL,
  `back_two` varchar(2) DEFAULT NULL,
  `status` enum('pending','completed') NOT NULL DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `lotteries`
--

INSERT INTO `lotteries` (`id`, `date`, `first_prize`, `front_three`, `back_three`, `back_two`, `status`, `created_at`, `updated_at`) VALUES
(1, '2025-05-01', '123456', '789', '456', '56', 'completed', '2025-05-13 19:00:11', '2025-05-13 19:00:11'),
(2, '2025-05-16', NULL, NULL, NULL, NULL, 'pending', '2025-05-13 19:00:11', '2025-05-13 19:00:11');

-- --------------------------------------------------------

--
-- Table structure for table `purchases`
--

CREATE TABLE `purchases` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `lottery_id` int(11) NOT NULL,
  `lottery_type` enum('firstPrize','frontThree','backThree','backTwo') NOT NULL,
  `number` varchar(6) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `potential_win` decimal(10,2) NOT NULL,
  `status` enum('pending','win','lose') NOT NULL DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `purchases`
--

INSERT INTO `purchases` (`id`, `user_id`, `lottery_id`, `lottery_type`, `number`, `amount`, `potential_win`, `status`, `created_at`) VALUES
(1, 2, 1, 'firstPrize', '123456', 100.00, 90000.00, 'win', '2025-05-13 19:00:11'),
(2, 2, 1, 'backTwo', '56', 50.00, 4500.00, 'win', '2025-05-13 19:00:11'),
(3, 3, 1, 'frontThree', '123', 200.00, 100000.00, 'lose', '2025-05-13 19:00:11');

-- --------------------------------------------------------

--
-- Table structure for table `rates`
--

CREATE TABLE `rates` (
  `id` int(11) NOT NULL,
  `lottery_id` int(11) NOT NULL,
  `first_prize` decimal(10,2) NOT NULL,
  `front_three` decimal(10,2) NOT NULL,
  `back_three` decimal(10,2) NOT NULL,
  `back_two` decimal(10,2) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `rates`
--

INSERT INTO `rates` (`id`, `lottery_id`, `first_prize`, `front_three`, `back_three`, `back_two`, `created_at`, `updated_at`) VALUES
(1, 1, 900.00, 500.00, 500.00, 90.00, '2025-05-13 19:00:11', '2025-05-13 19:00:11'),
(2, 2, 900.00, 500.00, 500.00, 90.00, '2025-05-13 19:00:11', '2025-05-13 19:00:11');

-- --------------------------------------------------------

--
-- Table structure for table `settings`
--

CREATE TABLE `settings` (
  `id` int(11) NOT NULL,
  `setting_key` varchar(50) NOT NULL,
  `setting_value` text NOT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `settings`
--

INSERT INTO `settings` (`id`, `setting_key`, `setting_value`, `updated_at`) VALUES
(1, 'site_name', '??????????????', '2025-05-13 19:00:11'),
(2, 'site_description', '??????????????????????????? ??????? ????????', '2025-05-13 19:00:11'),
(3, 'admin_email', 'admin@lotterythai.com', '2025-05-13 19:00:11'),
(4, 'support_email', 'support@lotterythai.com', '2025-05-13 19:00:11'),
(5, 'contact_phone', '02-123-4567', '2025-05-13 19:00:11'),
(6, 'line_id', '@lotterythai', '2025-05-13 19:00:11');

-- --------------------------------------------------------

--
-- Table structure for table `transactions`
--

CREATE TABLE `transactions` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `type` enum('deposit','purchase','win','admin') NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `balance_before` decimal(10,2) NOT NULL,
  `balance_after` decimal(10,2) NOT NULL,
  `reference_id` int(11) DEFAULT NULL,
  `reference_type` varchar(20) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `transactions`
--

INSERT INTO `transactions` (`id`, `user_id`, `type`, `amount`, `balance_before`, `balance_after`, `reference_id`, `reference_type`, `description`, `created_at`) VALUES
(1, 2, 'deposit', 1000.00, 0.00, 1000.00, 1, 'deposits', '????????????????????????', '2025-05-13 19:00:11'),
(2, 3, 'deposit', 500.00, 0.00, 500.00, 2, 'deposits', '?????????????????????', '2025-05-13 19:00:11'),
(3, 2, 'purchase', -100.00, 1000.00, 900.00, 1, 'purchases', '????????????? 1 ??? 123456', '2025-05-13 19:00:11'),
(4, 2, 'purchase', -50.00, 900.00, 850.00, 2, 'purchases', '??????????? 2 ??? ??? 56', '2025-05-13 19:00:11'),
(5, 3, 'purchase', -200.00, 500.00, 300.00, 3, 'purchases', '??????????? 3 ??? ??? 123', '2025-05-13 19:00:11'),
(6, 2, 'win', 90000.00, 850.00, 90850.00, 1, 'purchases', '???????????? 1 ??? 123456', '2025-05-13 19:00:11'),
(7, 2, 'win', 4500.00, 90850.00, 95350.00, 2, 'purchases', '?????????? 2 ??? ??? 56', '2025-05-13 19:00:11');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `balance` decimal(10,2) NOT NULL DEFAULT 0.00,
  `is_admin` tinyint(1) NOT NULL DEFAULT 0,
  `status` enum('active','blocked') NOT NULL DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `password`, `name`, `email`, `phone`, `balance`, `is_admin`, `status`, `created_at`, `updated_at`) VALUES
(1, 'admin', '$2y$10$BG79cgvR1eIxlRt4Rzki6O30j9A/HoC5kQ5Ou42/z4.VY1h.i4FvS', '???????????', 'admin@lotterythai.com', NULL, 0.00, 1, 'active', '2025-05-13 19:00:11', '2025-05-14 15:14:41'),
(2, 'user1', '$2y$10$oe8X3QVrOucLi.Q0XG6MZek8wgKoFbwVaD6tP8JNL4q9yXXHOCCOq', '?????? 1', 'user1@example.com', NULL, 0.00, 1, 'active', '2025-05-13 19:00:11', '2025-05-13 19:21:14'),
(3, 'user2', '$2y$10$7h3HPphK7cCLYZXTe9UfIeu9PwYEJpZeL5fj.gHRZgLJBqDsC0BJa', '?????? 2', 'user2@example.com', NULL, 0.00, 0, 'active', '2025-05-13 19:00:11', '2025-05-13 19:00:11');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `deposits`
--
ALTER TABLE `deposits`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `admin_id` (`admin_id`);

--
-- Indexes for table `lotteries`
--
ALTER TABLE `lotteries`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `purchases`
--
ALTER TABLE `purchases`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `lottery_id` (`lottery_id`);

--
-- Indexes for table `rates`
--
ALTER TABLE `rates`
  ADD PRIMARY KEY (`id`),
  ADD KEY `lottery_id` (`lottery_id`);

--
-- Indexes for table `settings`
--
ALTER TABLE `settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `setting_key` (`setting_key`);

--
-- Indexes for table `transactions`
--
ALTER TABLE `transactions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `deposits`
--
ALTER TABLE `deposits`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `lotteries`
--
ALTER TABLE `lotteries`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `purchases`
--
ALTER TABLE `purchases`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `rates`
--
ALTER TABLE `rates`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `settings`
--
ALTER TABLE `settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `transactions`
--
ALTER TABLE `transactions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
