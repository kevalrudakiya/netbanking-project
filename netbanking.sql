-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Sep 21, 2026 at 06:51 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `netbanking`
--

-- --------------------------------------------------------

--
-- Table structure for table `accounts`
--

CREATE TABLE `accounts` (
  `account_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `account_number` varchar(20) NOT NULL,
  `balance` decimal(15,2) NOT NULL DEFAULT 0.00,
  `pin` varchar(255) NOT NULL,
  `status` enum('active','frozen','closed') DEFAULT 'active',
  `account_type` enum('savings','checking','current') DEFAULT 'savings',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `accounts`
--

INSERT INTO `accounts` (`account_id`, `user_id`, `account_number`, `balance`, `pin`, `status`, `account_type`, `created_at`, `updated_at`) VALUES
(1, 1, '1008481208', 9999999999999.99, '$2y$10$VbyKeD6bANNSu9Q4YRop7eP5zkIeE1IBNhBdW39ZnHhj4dCIqe5tu', 'active', 'savings', '2026-06-23 23:12:56', '2026-08-03 17:13:42'),
(2, 4, '1004525426', 4994400.00, '$2y$10$EBa7LOgCmJeepRh30//a3uojPFXeXeW6DYghrlX0cTTrgCW/.PFV6', 'active', 'savings', '2026-07-01 18:12:35', '2026-07-29 15:55:55'),
(3, 5, '1008780990', 9999999900.00, '$2y$10$BChD1uFrCgERhPwFXiDEP.XXat9ytsv.SWIeRS19kPAYNfwJIcBw2', 'active', 'savings', '2026-07-06 01:13:08', '2026-07-29 15:55:55'),
(4, 6, '1007395710', 35000.00, '$2y$10$IERFA9pMgk1EgbxMrrAX3u5h02aZUhMBlDqo8PeFrgaoveTL3tVhG', 'active', 'savings', '2026-08-11 16:22:38', '2026-08-11 16:23:38'),
(5, 7, '1001002001', 15000.00, '$2y$10$72a8iOJVeaT.E.KsplNX7u3TYzRdlhGrrtySuKRGpxb2.yePO2D4.', 'active', 'savings', '2026-08-12 15:53:03', '2026-08-12 15:53:03'),
(6, 7, '1001002002', 5000.00, '$2y$10$rjAlQHTDhGxmy/VuV769suBjzvqVQi2fcJmgkDh7AmkVleXlsyz5O', 'active', 'current', '2026-08-12 15:53:03', '2026-08-12 15:53:03'),
(7, 8, '1002003001', 75000.50, '$2y$10$QrYScFhmM0U6a3Vbd88JSO7tuorZnr.jCNCUlfj/Hezb2E22zTEIe', 'active', 'savings', '2026-08-12 15:53:03', '2026-08-12 15:53:03'),
(8, 9, '1009998888', 1000.00, '$2y$10$gVc/mGUIIBsl84GItXj/EeeM5He6wiUQGV3Mif1Fx39L9gl1bxImO', 'active', 'savings', '2026-08-12 15:53:03', '2026-08-12 15:53:03'),
(9, 10, '1004509123', 4521.50, '$2y$10$ixF3/EU5T9jRlmYw8fV2XesNGUV81NPcBabd0fF8jDpGeH.QZ/Ddm', 'active', 'savings', '2026-07-13 16:01:48', '2026-08-12 16:01:48'),
(10, 10, '1004509124', 20500.00, '$2y$10$PCdf8TVSnSxhaAWnDj6Cb.Ih6Gi9fJy.k1FiEUESlEOMqyfrNpag.', 'active', 'savings', '2026-07-13 16:01:48', '2026-08-12 16:01:48'),
(11, 11, '1008892244', 12890.75, '$2y$10$w.HEO8W0VKRv6nDlU6jvGem4Zu0mmSbuMTmTj9gXzRJx0To6Qp/9K', 'active', 'current', '2026-07-13 16:01:48', '2026-08-12 16:01:48'),
(12, 12, '1007739090', 842.10, '$2y$10$JJ//xwgU3a50eRMUTD9KIuzm4FoPbBP2XTnsCoCOtz3hG21OOJ8J6', 'active', 'savings', '2026-07-13 16:01:48', '2026-08-12 16:01:48');

-- --------------------------------------------------------

--
-- Table structure for table `admins`
--

CREATE TABLE `admins` (
  `admin_id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `role` enum('superadmin','admin') DEFAULT 'admin',
  `failed_attempts` int(11) DEFAULT 0,
  `locked_until` timestamp NULL DEFAULT NULL,
  `last_login` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `admins`
--

INSERT INTO `admins` (`admin_id`, `username`, `password`, `full_name`, `role`, `failed_attempts`, `locked_until`, `last_login`, `created_at`, `updated_at`) VALUES
(1, 'superadmin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Super Admin', 'superadmin', 0, NULL, '2026-08-27 15:45:39', '2026-06-23 22:59:25', '2026-08-27 15:45:39');

-- --------------------------------------------------------

--
-- Table structure for table `audit_logs`
--

CREATE TABLE `audit_logs` (
  `id` int(11) NOT NULL,
  `actor_type` enum('user','admin') NOT NULL DEFAULT 'admin',
  `actor_id` int(11) DEFAULT NULL,
  `action` varchar(255) NOT NULL,
  `details` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` varchar(255) DEFAULT NULL,
  `related_account_id` int(11) DEFAULT NULL,
  `related_transaction_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `audit_logs`
--

INSERT INTO `audit_logs` (`id`, `actor_type`, `actor_id`, `action`, `details`, `ip_address`, `user_agent`, `related_account_id`, `related_transaction_id`, `created_at`) VALUES
(1, 'admin', 1, 'admin_login_success', 'Admin logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0', NULL, NULL, '2026-08-11 17:12:35'),
(2, 'user', NULL, 'user_login_failed', 'Failed login attempt for username: ss', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0', NULL, NULL, '2026-08-12 06:02:49'),
(3, 'user', NULL, 'user_login_failed', 'Failed login attempt for username: keval123', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0', NULL, NULL, '2026-08-12 06:03:14'),
(4, '', NULL, 'admin_login_failed', 'Failed login attempt for unknown user: sd', '::1', NULL, NULL, NULL, '2026-08-12 06:05:37'),
(5, 'user', 1, 'user_login', 'User logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0', NULL, NULL, '2026-08-12 15:43:32'),
(6, 'admin', 1, 'admin_login_failed', 'Failed login attempt', '::1', NULL, NULL, NULL, '2026-08-12 15:59:29'),
(7, 'admin', 1, 'admin_login_success', 'Admin logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0', NULL, NULL, '2026-08-12 15:59:39'),
(8, 'user', 1, 'user_login', 'User logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0', NULL, NULL, '2026-08-17 16:05:00'),
(9, 'user', NULL, 'user_login_failed', 'Failed login attempt for username: KEVAL123', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0', NULL, NULL, '2026-08-19 18:09:51'),
(10, 'user', NULL, 'user_login_failed', 'Failed login attempt for username: KEVAL123', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0', NULL, NULL, '2026-08-19 18:09:59'),
(11, 'user', 1, 'user_login', 'User logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0', NULL, NULL, '2026-08-19 18:10:15'),
(12, 'user', 1, 'user_logout', 'User logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0', NULL, NULL, '2026-08-19 18:10:43'),
(13, '', NULL, 'admin_login_failed', 'Failed login attempt for unknown user: admin', '::1', NULL, NULL, NULL, '2026-08-19 18:11:12'),
(14, 'admin', 1, 'admin_login_success', 'Admin logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0', NULL, NULL, '2026-08-19 18:11:31'),
(15, 'admin', 1, 'admin_logout', 'Admin logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0', NULL, NULL, '2026-08-19 18:12:43'),
(16, 'user', 1, 'user_login', 'User logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0', NULL, NULL, '2026-08-19 18:13:06'),
(17, 'user', 1, 'user_login', 'User logged in successfully', '192.168.0.20', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Mobile Safari/537.36', NULL, NULL, '2026-08-21 18:38:30'),
(18, 'user', NULL, 'user_login_failed', 'Failed login attempt for username: pathyo', '192.168.0.20', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', NULL, NULL, '2026-08-21 18:41:20'),
(19, 'admin', 1, 'admin_login_failed', 'Failed login attempt', '192.168.0.20', NULL, NULL, NULL, '2026-08-21 18:43:10'),
(20, 'admin', 1, 'admin_login_success', 'Admin logged in successfully', '192.168.0.20', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Mobile Safari/537.36', NULL, NULL, '2026-08-21 18:43:20'),
(21, 'admin', 1, 'admin_login_failed', 'Failed login attempt', '192.168.0.20', NULL, NULL, NULL, '2026-08-21 18:44:31'),
(22, 'admin', 1, 'admin_login_success', 'Admin logged in successfully', '192.168.0.20', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', NULL, NULL, '2026-08-21 18:45:06'),
(23, 'user', NULL, 'user_login_failed', 'Failed login attempt for username: pathyo06@gmail.com', '192.168.0.20', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', NULL, NULL, '2026-08-21 18:50:51'),
(24, 'user', NULL, 'user_login_failed', 'Failed login attempt for username: pathyo bagda', '192.168.0.20', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', NULL, NULL, '2026-08-21 18:51:31'),
(25, 'user', NULL, 'user_login_failed', 'Failed login attempt for username: Maulik', '192.168.0.20', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', NULL, NULL, '2026-08-21 18:52:30'),
(26, 'user', NULL, 'user_login_failed', 'Failed login attempt for username: keval123', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0', NULL, NULL, '2026-08-21 23:56:44'),
(27, 'user', 1, 'user_login', 'User logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0', NULL, NULL, '2026-08-21 23:56:53'),
(28, 'user', 1, 'user_login', 'User logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0', NULL, NULL, '2026-08-24 15:35:10'),
(29, 'user', 1, 'user_login', 'User logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0', NULL, NULL, '2026-08-26 15:29:04'),
(30, 'user', 1, 'user_profile_update', 'User updated their personal details', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0', NULL, NULL, '2026-08-26 15:29:49'),
(31, 'user', 1, 'user_profile_update', 'User updated their personal details', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0', NULL, NULL, '2026-08-26 15:30:13'),
(32, 'user', 1, 'user_profile_update', 'User updated their personal details', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0', NULL, NULL, '2026-08-26 15:30:21'),
(33, 'user', 1, 'user_login', 'User logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0', NULL, NULL, '2026-08-27 15:28:25'),
(34, 'user', 1, 'user_logout', 'User logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0', NULL, NULL, '2026-08-27 15:44:35'),
(35, 'admin', 1, 'admin_login_success', 'Admin logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0', NULL, NULL, '2026-08-27 15:45:39'),
(36, 'user', NULL, 'user_login_failed', 'Failed login attempt for username: KEVAL123', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36 OPR/135.0.0.0', NULL, NULL, '2026-09-09 00:13:47'),
(37, 'user', 1, 'user_login', 'User logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36 OPR/135.0.0.0', NULL, NULL, '2026-09-09 00:13:55'),
(38, 'user', 1, 'user_profile_update', 'User updated their personal details', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36 OPR/135.0.0.0', NULL, NULL, '2026-09-09 00:14:33'),
(39, 'user', 1, 'user_profile_update', 'User updated their personal details', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36 OPR/135.0.0.0', NULL, NULL, '2026-09-09 00:15:04'),
(40, 'user', 1, 'user_profile_update', 'User updated their personal details', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36 OPR/135.0.0.0', NULL, NULL, '2026-09-09 00:15:14'),
(41, 'user', 1, 'user_login', 'User logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36 OPR/135.0.0.0', NULL, NULL, '2026-09-10 15:58:29');

-- --------------------------------------------------------

--
-- Table structure for table `beneficiaries`
--

CREATE TABLE `beneficiaries` (
  `beneficiary_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `account_number` varchar(20) NOT NULL,
  `beneficiary_name` varchar(100) NOT NULL,
  `nickname` varchar(50) DEFAULT NULL,
  `is_favorite` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `login_attempts`
--

CREATE TABLE `login_attempts` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `ip_address` varchar(45) NOT NULL,
  `attempted_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `login_attempts`
--

INSERT INTO `login_attempts` (`id`, `username`, `ip_address`, `attempted_at`) VALUES
(51, '1234567890', '::1', '2026-06-24 22:56:07'),
(9, 'keval', '::1', '2026-06-23 23:25:46'),
(10, 'keval', '::1', '2026-06-23 23:25:54'),
(11, 'keval', '::1', '2026-06-23 23:29:43'),
(13, 'keval', '::1', '2026-06-23 23:29:58'),
(34, 'keval', '::1', '2026-06-24 02:41:46'),
(35, 'keval', '::1', '2026-06-24 02:41:52'),
(36, 'keval', '::1', '2026-06-24 02:42:17'),
(1, 'keval123', '::1', '2026-06-23 23:24:10'),
(2, 'keval123', '::1', '2026-06-23 23:24:20'),
(3, 'keval123', '::1', '2026-06-23 23:25:07'),
(4, 'keval123', '::1', '2026-06-23 23:25:12'),
(5, 'keval123', '::1', '2026-06-23 23:25:13'),
(6, 'keval123', '::1', '2026-06-23 23:25:21'),
(7, 'keval123', '::1', '2026-06-23 23:25:27'),
(8, 'keval123', '::1', '2026-06-23 23:25:32'),
(12, 'keval123', '::1', '2026-06-23 23:29:51'),
(14, 'keval123', '::1', '2026-06-24 00:08:28'),
(15, 'keval123', '::1', '2026-06-24 00:08:42'),
(16, 'keval123', '::1', '2026-06-24 00:13:24'),
(17, 'keval123', '::1', '2026-06-24 00:15:51'),
(18, 'keval123', '::1', '2026-06-24 00:24:01'),
(19, 'keval123', '::1', '2026-06-24 00:24:03'),
(20, 'keval123', '::1', '2026-06-24 00:24:03'),
(21, 'keval123', '::1', '2026-06-24 00:24:04'),
(22, 'keval123', '::1', '2026-06-24 00:24:04'),
(23, 'keval123', '::1', '2026-06-24 00:24:04'),
(24, 'keval123', '::1', '2026-06-24 00:24:04'),
(25, 'keval123', '::1', '2026-06-24 00:24:04'),
(26, 'keval123', '::1', '2026-06-24 00:24:05'),
(27, 'keval123', '::1', '2026-06-24 00:24:05'),
(28, 'keval123', '::1', '2026-06-24 00:24:05'),
(29, 'keval123', '::1', '2026-06-24 00:24:05'),
(30, 'keval123', '::1', '2026-06-24 00:24:05'),
(31, 'keval123', '::1', '2026-06-24 00:36:34'),
(32, 'keval123', '::1', '2026-06-24 01:20:54'),
(33, 'keval123', '::1', '2026-06-24 02:41:33'),
(37, 'keval123', '::1', '2026-06-24 02:42:25'),
(38, 'keval123', '::1', '2026-06-24 02:42:33'),
(39, 'keval123', '::1', '2026-06-24 02:42:41'),
(40, 'keval123', '::1', '2026-06-24 02:45:44'),
(41, 'keval123', '::1', '2026-06-24 02:58:52'),
(42, 'keval123', '::1', '2026-06-24 02:58:55'),
(43, 'keval123', '::1', '2026-06-24 02:58:55'),
(44, 'keval123', '::1', '2026-06-24 02:58:55'),
(45, 'keval123', '::1', '2026-06-24 02:58:56'),
(46, 'keval123', '::1', '2026-06-24 02:58:56'),
(47, 'keval123', '::1', '2026-06-24 03:01:14'),
(48, 'keval123', '::1', '2026-06-24 22:54:17'),
(49, 'keval123', '::1', '2026-06-24 22:54:35'),
(50, 'keval123', '::1', '2026-06-24 22:54:59'),
(52, 'keval123', '::1', '2026-06-24 22:59:55'),
(53, 'keval123', '::1', '2026-06-24 23:09:49'),
(56, 'keval123', '::1', '2026-06-24 23:20:36'),
(61, 'keval123', '::1', '2026-07-29 15:53:44'),
(62, 'keval123', '::1', '2026-08-10 15:54:53'),
(63, 'keval123', '::1', '2026-08-10 17:01:49'),
(64, 'keval123', '::1', '2026-08-10 21:29:41'),
(66, 'keval123', '::1', '2026-08-12 06:03:14'),
(67, 'KEVAL123', '::1', '2026-08-19 18:09:51'),
(68, 'KEVAL123', '::1', '2026-08-19 18:09:59'),
(73, 'keval123', '::1', '2026-08-21 23:56:44'),
(74, 'KEVAL123', '::1', '2026-09-09 00:13:47'),
(59, 'maulik', '::1', '2026-07-06 01:13:29'),
(72, 'Maulik', '192.168.0.20', '2026-08-21 18:52:30'),
(57, 'parthyo123', '::1', '2026-07-01 18:12:57'),
(58, 'parthyo123', '::1', '2026-07-01 18:13:30'),
(69, 'pathyo', '192.168.0.20', '2026-08-21 18:41:20'),
(71, 'pathyo bagda', '192.168.0.20', '2026-08-21 18:51:31'),
(70, 'pathyo06@gmail.com', '192.168.0.20', '2026-08-21 18:50:51'),
(60, 'pathyo123', '::1', '2026-07-06 16:47:45'),
(65, 'ss', '::1', '2026-08-12 06:02:49'),
(54, 'superadmin', '::1', '2026-06-24 23:09:50'),
(55, 'superadmin', '::1', '2026-06-24 23:20:24');

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `id` int(11) NOT NULL,
  `type` enum('user','admin') NOT NULL DEFAULT 'user',
  `user_id` int(11) NOT NULL,
  `title` varchar(150) NOT NULL,
  `message` text NOT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `notifications`
--

INSERT INTO `notifications` (`id`, `type`, `user_id`, `title`, `message`, `is_read`, `created_at`) VALUES
(1, 'user', 1, 'New Login', 'A new login was detected on your account from IP ::1.', 0, '2026-08-12 15:43:32'),
(2, 'user', 1, 'New Login', 'A new login was detected on your account from IP ::1.', 0, '2026-08-17 16:05:00'),
(3, 'user', 1, 'New Login', 'A new login was detected on your account from IP ::1.', 0, '2026-08-19 18:10:15'),
(4, 'user', 1, 'New Login', 'A new login was detected on your account from IP ::1.', 0, '2026-08-19 18:13:06'),
(5, 'user', 1, 'New Login', 'A new login was detected on your account from IP 192.168.0.20.', 0, '2026-08-21 18:38:30'),
(6, 'user', 1, 'New Login', 'A new login was detected on your account from IP ::1.', 0, '2026-08-21 23:56:53'),
(7, 'user', 1, 'New Login', 'A new login was detected on your account from IP ::1.', 0, '2026-08-24 15:35:10'),
(8, 'user', 1, 'New Login', 'A new login was detected on your account from IP ::1.', 0, '2026-08-26 15:29:04'),
(9, 'user', 1, 'New Login', 'A new login was detected on your account from IP ::1.', 0, '2026-08-27 15:28:25'),
(10, 'user', 1, 'New Login', 'A new login was detected on your account from IP ::1.', 0, '2026-09-09 00:13:55'),
(11, 'user', 1, 'New Login', 'A new login was detected on your account from IP ::1.', 0, '2026-09-10 15:58:29');

-- --------------------------------------------------------

--
-- Table structure for table `password_resets`
--

CREATE TABLE `password_resets` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `reset_token` varchar(255) NOT NULL,
  `expires_at` datetime NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `password_reset_requests`
--

CREATE TABLE `password_reset_requests` (
  `request_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `account_number` varchar(20) NOT NULL,
  `phone` varchar(15) NOT NULL,
  `status` enum('pending','approved','rejected') DEFAULT 'pending',
  `admin_id` int(11) DEFAULT NULL,
  `temporary_password` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `processed_at` timestamp NULL DEFAULT NULL,
  `new_password` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `password_reset_requests`
--

INSERT INTO `password_reset_requests` (`request_id`, `user_id`, `account_number`, `phone`, `status`, `admin_id`, `temporary_password`, `created_at`, `processed_at`, `new_password`) VALUES
(1, 1, '1008481208', '1234567890', 'rejected', 1, NULL, '2026-06-24 22:33:45', NULL, NULL),
(2, 1, '1008481208', '1234567890', 'approved', 1, '420952', '2026-06-24 22:44:43', NULL, NULL),
(3, 1, '1008481208', '1234567890', 'approved', 1, '838421', '2026-06-24 23:12:23', '2026-06-24 23:12:34', NULL),
(4, 1, '1008481208', '1234567890', 'approved', NULL, NULL, '2026-06-24 23:32:38', NULL, NULL),
(5, 1, '1008481208', '1234567890', 'approved', NULL, NULL, '2026-07-06 16:49:36', NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `transactions`
--

CREATE TABLE `transactions` (
  `transaction_id` int(11) NOT NULL,
  `account_id` int(11) NOT NULL,
  `type` enum('deposit','withdraw','transfer_in','transfer_out') NOT NULL,
  `amount` decimal(15,2) NOT NULL,
  `balance_after` decimal(15,2) NOT NULL,
  `reference_no` varchar(30) NOT NULL,
  `note` varchar(255) DEFAULT NULL,
  `related_account_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `transactions`
--

INSERT INTO `transactions` (`transaction_id`, `account_id`, `type`, `amount`, `balance_after`, `reference_no`, `note`, `related_account_id`, `created_at`) VALUES
(1, 1, 'deposit', 50000.00, 50000.00, 'TXN202606249B435F', 'keval123', NULL, '2026-06-23 23:19:37'),
(2, 1, 'withdraw', 45000.00, 5000.00, 'TXN20260624008C79', 'keval123', NULL, '2026-06-23 23:20:00'),
(3, 1, 'deposit', 100000000.00, 100005000.00, 'TXN202606255B238C', 'superadmin', NULL, '2026-06-24 23:36:53'),
(4, 1, 'withdraw', 12124.00, 99992876.00, 'TXN202606257D09AF', 'superadmin', NULL, '2026-06-24 23:37:59'),
(5, 1, 'withdraw', 12220000.00, 87772876.00, 'TXN202607018C0C0C', 'keval123', NULL, '2026-07-01 17:45:12'),
(6, 1, 'deposit', 5000000000.00, 5087772876.00, 'TXN202607016085BD', '', NULL, '2026-07-01 17:45:42'),
(7, 2, 'deposit', 5000000.00, 5000000.00, 'TXN2026070182FFBF', '', NULL, '2026-07-01 18:14:00'),
(8, 2, 'withdraw', 55599.00, 4944401.00, 'TXN202607013659B1', '', NULL, '2026-07-01 18:14:27'),
(9, 3, 'deposit', 10000000000.00, 10000000000.00, 'TXN20260706BBE2B0', '', NULL, '2026-07-06 01:24:43'),
(10, 3, 'withdraw', 100.00, 9999999900.00, 'TXN2026070696F940', '', NULL, '2026-07-06 01:25:45'),
(11, 1, 'transfer_out', 99999.00, 5087672877.00, 'TXN202607065BF97E', 'loda', NULL, '2026-07-06 16:46:29'),
(12, 2, 'transfer_in', 99999.00, 5044400.00, 'TXN202607065C1417', 'loda', NULL, '2026-07-06 16:46:29'),
(13, 2, 'withdraw', 50000.00, 4994400.00, 'TXN20260706910B14', '', NULL, '2026-07-06 16:48:41'),
(14, 1, 'deposit', 9999999999999.99, 9999999999999.99, 'TXN202608036F3AEB', '', NULL, '2026-08-03 17:13:43'),
(15, 4, 'deposit', 35000.00, 35000.00, 'TXN20260811A79CA0', 'salary', NULL, '2026-08-11 16:23:38'),
(16, 5, 'deposit', 20000.00, 20000.00, 'TXN20260812FA1AF9', 'Initial deposit', NULL, '2026-08-12 15:53:03'),
(17, 5, 'withdraw', 5000.00, 15000.00, 'TXN20260812FA254E', 'ATM Withdrawal', NULL, '2026-08-12 15:53:03'),
(18, 6, 'deposit', 5000.00, 5000.00, 'TXN20260812FA3084', 'Initial deposit', NULL, '2026-08-12 15:53:03'),
(19, 7, 'deposit', 80000.00, 80000.00, 'TXN20260812FA65E7', 'Salary', NULL, '2026-08-12 15:53:03'),
(20, 7, 'withdraw', 4999.50, 75000.50, 'TXN20260812FA6B5E', 'Rent Payment', NULL, '2026-08-12 15:53:03'),
(21, 8, 'deposit', 1000.00, 1000.00, 'TXN20260812FA7B6E', 'Demo deposit', NULL, '2026-08-12 15:53:03'),
(22, 9, 'deposit', 5200.00, 5200.00, 'TXN20260718C48760', 'ACH Deposit - TechCorp Inc. Payroll', NULL, '2026-07-18 16:01:48'),
(23, 9, 'withdraw', 1200.00, 4000.00, 'TXN20260722C48D86', 'Rent Payment - Skyline Apartments', NULL, '2026-07-22 16:01:48'),
(24, 9, 'withdraw', 125.50, 3874.50, 'TXN20260726C49C90', 'POS - Whole Foods Market', NULL, '2026-07-26 16:01:48'),
(25, 9, 'withdraw', 45.00, 3829.50, 'TXN20260727C4A288', 'POS - Shell Gas Station', NULL, '2026-07-27 16:01:48'),
(26, 9, 'deposit', 750.00, 4579.50, 'TXN20260730C4A8CF', 'Zelle Transfer from Sarah J.', NULL, '2026-07-30 16:01:48'),
(27, 9, 'withdraw', 58.00, 4521.50, 'TXN20260803C4AEA7', 'POS - Starbucks Coffee', NULL, '2026-08-03 16:01:48'),
(28, 10, 'deposit', 20000.00, 20000.00, 'TXN20260718C4BBCD', 'Wire Transfer - Fidelity Investments', NULL, '2026-07-18 16:01:48'),
(29, 10, 'deposit', 500.00, 20500.00, 'TXN20260721C4C19B', 'Interest Payment - Aug 2026', NULL, '2026-07-21 16:01:48'),
(30, 11, 'deposit', 15000.00, 15000.00, 'TXN20260718C4D812', 'Invoice Payment - Studio X', NULL, '2026-07-18 16:01:48'),
(31, 11, 'withdraw', 350.00, 14650.00, 'TXN20260719C4DF23', 'Adobe Creative Cloud Subscription', NULL, '2026-07-19 16:01:48'),
(32, 11, 'withdraw', 1200.00, 13450.00, 'TXN20260721C4E57C', 'Equipment Purchase - Apple Store', NULL, '2026-07-21 16:01:48'),
(33, 11, 'withdraw', 85.25, 13364.75, 'TXN20260724C4EC45', 'POS - Blue Bottle Coffee', NULL, '2026-07-24 16:01:48'),
(34, 11, 'withdraw', 474.00, 12890.75, 'TXN20260727C4F2A9', 'Payment - WeWork Co-working space', NULL, '2026-07-27 16:01:48'),
(35, 12, 'deposit', 2100.00, 2100.00, 'TXN20260718C506CB', 'Payroll - Miami Logistics', NULL, '2026-07-18 16:01:48'),
(36, 12, 'withdraw', 600.00, 1500.00, 'TXN20260722C50D59', 'Auto Loan Payment - Chase Auto', NULL, '2026-07-22 16:01:48'),
(37, 12, 'withdraw', 145.20, 1354.80, 'TXN20260725C513B8', 'Utility Bill - Florida Power', NULL, '2026-07-25 16:01:48'),
(38, 12, 'withdraw', 89.00, 1265.80, 'TXN20260729C51A73', 'AT&T Mobile Bill', NULL, '2026-07-29 16:01:48'),
(39, 12, 'withdraw', 200.00, 1065.80, 'TXN20260801C52193', 'ATM Cash Withdrawal', NULL, '2026-08-01 16:01:48'),
(40, 12, 'withdraw', 223.70, 842.10, 'TXN20260803C527F3', 'POS - Target', NULL, '2026-08-03 16:01:48');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `user_id` int(11) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `email` varchar(150) NOT NULL,
  `phone` varchar(15) NOT NULL,
  `address` text DEFAULT NULL,
  `dob` date DEFAULT NULL,
  `profile_photo` varchar(255) DEFAULT 'default.png',
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `status` enum('active','blocked') DEFAULT 'active',
  `last_login` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `full_name`, `email`, `phone`, `address`, `dob`, `profile_photo`, `username`, `password`, `status`, `last_login`, `created_at`, `updated_at`) VALUES
(1, 'keval', 'keval@gmail.com', '1234567890', '', '2005-06-07', 'profile_6aa0a512f01b3.jpeg', 'keval123', '$2y$10$L5dudU.M1EZCqVrVJDrFQ.tSafYidOh5bL8JypqLYH9QkYOOJlKIS', 'active', NULL, '2026-06-23 23:12:56', '2026-09-09 00:15:14'),
(4, 'pathyo bagda', 'pathyo6@gmail.com', '9023123771', NULL, NULL, 'default.png', 'pathyo123', '$2y$10$8Ir10UmWuLbA55LcTG08/.KtvEqKjLx4M2G7/z1W9zHqW2pjVcS8G', 'active', NULL, '2026-07-01 18:12:35', '2026-07-29 15:55:55'),
(5, 'maulik', 'maulik1234@gmail.com', '1234567891', NULL, NULL, 'default.png', 'maulik', '$2y$10$qhhdLgWHkt9g745QWaDONeUhVQXVQb5H8v/DtRRklSgA6kPmn3TZC', 'active', NULL, '2026-07-06 01:13:08', '2026-07-29 15:55:55'),
(6, 'sampel', 'sample@gmail.com', '9081702410', NULL, NULL, 'default.png', 'sample123', '$2y$10$htlw8dqJJbTCX9rAvymjg.BY1O4.o2NkDCTDxX58HHycmigDE0OsS', 'active', NULL, '2026-08-11 16:22:38', '2026-08-11 16:22:38'),
(7, 'John Doe', 'john.doe@example.com', '5551234567', NULL, NULL, 'default.png', 'johndoe', '$2y$10$9yYsXX7EWq/dyUhRqu.xdORnek3Ic8eetloQqWmiT8mKD5AV8Wte2', 'active', NULL, '2026-08-12 15:53:03', '2026-08-12 15:53:03'),
(8, 'Jane Smith', 'jane.smith@example.com', '5559876543', NULL, NULL, 'default.png', 'janesmith', '$2y$10$iOITPDK6nmElgCHaQoU69.izlwQIL0TxrV9Ef7X1qtkExm5d19jAG', 'active', NULL, '2026-08-12 15:53:03', '2026-08-12 15:53:03'),
(9, 'Demo User', 'demo@example.com', '5550000000', NULL, NULL, 'default.png', 'demo', '$2y$10$EVCX0PP28vQwX2LiKoopyeSSIr4fXcOfavoqkfsp74cQFosWuMjbO', 'active', NULL, '2026-08-12 15:53:03', '2026-08-12 15:53:03'),
(10, 'Michael Johnson', 'mjohnson.professional@gmail.com', '2125550198', NULL, NULL, 'default.png', 'mjohnson88', '$2y$10$92/aG2iAVdwYq7sqjFuvsO.zYhTfTReUbeeSmWY8wYo4ESe7z9spu', 'active', NULL, '2026-07-13 16:01:48', '2026-08-12 16:01:48'),
(11, 'Emily Chen', 'echen.design@outlook.com', '4155550231', NULL, NULL, 'default.png', 'emilyc_design', '$2y$10$HsdIYBcSYFXvcc8/eflIIeJTjnm9PwndGwjl/wYq5QzuFzQvX.Tg2', 'active', NULL, '2026-07-13 16:01:48', '2026-08-12 16:01:48'),
(12, 'David Martinez', 'dmartinez1975@yahoo.com', '3055550882', NULL, NULL, 'default.png', 'davidm_75', '$2y$10$SH9CCB7upciqZdOIPO9sYe4l43355.05djQ0sc.Qp9oM0AT6skvLu', 'active', NULL, '2026-07-13 16:01:48', '2026-08-12 16:01:48');

-- --------------------------------------------------------

--
-- Table structure for table `user_login_history`
--

CREATE TABLE `user_login_history` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `ip_address` varchar(45) NOT NULL,
  `user_agent` varchar(255) NOT NULL,
  `status` enum('success','failed') NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `user_login_history`
--

INSERT INTO `user_login_history` (`id`, `user_id`, `ip_address`, `user_agent`, `status`, `created_at`) VALUES
(1, 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0', 'failed', '2026-08-12 06:03:14'),
(2, 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0', 'success', '2026-08-12 15:43:32'),
(3, 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0', 'success', '2026-08-17 16:05:00'),
(4, 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0', 'failed', '2026-08-19 18:09:51'),
(5, 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0', 'failed', '2026-08-19 18:09:59'),
(6, 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0', 'success', '2026-08-19 18:10:15'),
(7, 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0', 'success', '2026-08-19 18:13:06'),
(8, 1, '192.168.0.20', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Mobile Safari/537.36', 'success', '2026-08-21 18:38:30'),
(9, 5, '192.168.0.20', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', 'failed', '2026-08-21 18:52:30'),
(10, 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0', 'failed', '2026-08-21 23:56:44'),
(11, 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0', 'success', '2026-08-21 23:56:53'),
(12, 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0', 'success', '2026-08-24 15:35:10'),
(13, 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0', 'success', '2026-08-26 15:29:04'),
(14, 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 OPR/134.0.0.0', 'success', '2026-08-27 15:28:25'),
(15, 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36 OPR/135.0.0.0', 'failed', '2026-09-09 00:13:47'),
(16, 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36 OPR/135.0.0.0', 'success', '2026-09-09 00:13:55'),
(17, 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36 OPR/135.0.0.0', 'success', '2026-09-10 15:58:29');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `accounts`
--
ALTER TABLE `accounts`
  ADD PRIMARY KEY (`account_id`),
  ADD UNIQUE KEY `account_number` (`account_number`),
  ADD KEY `idx_accounts_user_status` (`user_id`,`status`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_account_type` (`account_type`);

--
-- Indexes for table `admins`
--
ALTER TABLE `admins`
  ADD PRIMARY KEY (`admin_id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- Indexes for table `audit_logs`
--
ALTER TABLE `audit_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_audit_actor` (`actor_type`,`actor_id`),
  ADD KEY `idx_audit_action` (`action`),
  ADD KEY `idx_audit_created` (`created_at`);

--
-- Indexes for table `beneficiaries`
--
ALTER TABLE `beneficiaries`
  ADD PRIMARY KEY (`beneficiary_id`),
  ADD UNIQUE KEY `uk_user_beneficiary` (`user_id`,`account_number`),
  ADD UNIQUE KEY `unique_user_beneficiary` (`user_id`,`account_number`),
  ADD KEY `idx_beneficiary_user` (`user_id`);

--
-- Indexes for table `login_attempts`
--
ALTER TABLE `login_attempts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_login_attempt_lookup` (`username`,`ip_address`,`attempted_at`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `password_resets`
--
ALTER TABLE `password_resets`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `password_reset_requests`
--
ALTER TABLE `password_reset_requests`
  ADD PRIMARY KEY (`request_id`),
  ADD KEY `admin_id` (`admin_id`),
  ADD KEY `idx_reset_user_status` (`user_id`,`status`);

--
-- Indexes for table `transactions`
--
ALTER TABLE `transactions`
  ADD PRIMARY KEY (`transaction_id`),
  ADD UNIQUE KEY `reference_no` (`reference_no`),
  ADD KEY `idx_txn_acc_date` (`account_id`,`created_at`),
  ADD KEY `idx_txn_acc_type` (`account_id`,`type`),
  ADD KEY `fk_txn_related_account` (`related_account_id`),
  ADD KEY `idx_type` (`type`),
  ADD KEY `idx_amount` (`amount`),
  ADD KEY `idx_created_at` (`created_at`),
  ADD KEY `idx_reference_no` (`reference_no`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD UNIQUE KEY `phone` (`phone`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `username_2` (`username`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_username` (`username`),
  ADD KEY `idx_email` (`email`);

--
-- Indexes for table `user_login_history`
--
ALTER TABLE `user_login_history`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_created_at` (`created_at`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `accounts`
--
ALTER TABLE `accounts`
  MODIFY `account_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `admins`
--
ALTER TABLE `admins`
  MODIFY `admin_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `audit_logs`
--
ALTER TABLE `audit_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=42;

--
-- AUTO_INCREMENT for table `beneficiaries`
--
ALTER TABLE `beneficiaries`
  MODIFY `beneficiary_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `login_attempts`
--
ALTER TABLE `login_attempts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=75;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `password_resets`
--
ALTER TABLE `password_resets`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `password_reset_requests`
--
ALTER TABLE `password_reset_requests`
  MODIFY `request_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `transactions`
--
ALTER TABLE `transactions`
  MODIFY `transaction_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=41;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `user_login_history`
--
ALTER TABLE `user_login_history`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `accounts`
--
ALTER TABLE `accounts`
  ADD CONSTRAINT `accounts_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `beneficiaries`
--
ALTER TABLE `beneficiaries`
  ADD CONSTRAINT `beneficiaries_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `password_reset_requests`
--
ALTER TABLE `password_reset_requests`
  ADD CONSTRAINT `password_reset_requests_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `password_reset_requests_ibfk_2` FOREIGN KEY (`admin_id`) REFERENCES `admins` (`admin_id`) ON DELETE SET NULL;

--
-- Constraints for table `transactions`
--
ALTER TABLE `transactions`
  ADD CONSTRAINT `fk_txn_related_account` FOREIGN KEY (`related_account_id`) REFERENCES `accounts` (`account_id`) ON DELETE SET NULL,
  ADD CONSTRAINT `transactions_ibfk_1` FOREIGN KEY (`account_id`) REFERENCES `accounts` (`account_id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
