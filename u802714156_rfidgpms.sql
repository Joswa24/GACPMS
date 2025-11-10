-- phpMyAdmin SQL Dump
-- version 4.9.0.1
-- https://www.phpmyadmin.net/
--
-- Host: sql210.infinityfree.com
-- Generation Time: Nov 07, 2025 at 04:59 AM
-- Server version: 11.4.7-MariaDB
-- PHP Version: 7.2.22

SET FOREIGN_KEY_CHECKS=0;
SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `if0_39846067_gpassdb`
--

-- --------------------------------------------------------

--
-- Table structure for table `about`
--
-- Creation: Oct 11, 2025 at 02:30 PM
--

CREATE TABLE `about` (
  `id` int(11) NOT NULL,
  `logo1` varchar(255) NOT NULL,
  `logo2` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `address` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `about`
--

INSERT INTO `about` (`id`, `logo1`, `logo2`, `name`, `address`) VALUES
(1, 'mcc.png', 'madridejos.png', 'Madridejos Community College', 'Bunakan, Madridejos, Cebu');

-- --------------------------------------------------------

--
-- Table structure for table `access_rules`
--
-- Creation: Oct 11, 2025 at 02:30 PM
--

CREATE TABLE `access_rules` (
  `id` int(11) NOT NULL,
  `rule_name` varchar(100) NOT NULL,
  `rule_type` enum('time_based','day_based','person_type','specific_person') NOT NULL,
  `target_type` enum('all','student','instructor','personell','visitor','specific') DEFAULT 'all',
  `target_id` int(11) DEFAULT NULL,
  `start_time` time DEFAULT NULL,
  `end_time` time DEFAULT NULL,
  `days_of_week` varchar(20) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `access_rules`
--

INSERT INTO `access_rules` (`id`, `rule_name`, `rule_type`, `target_type`, `target_id`, `start_time`, `end_time`, `days_of_week`, `is_active`, `description`, `created_at`) VALUES
(1, 'Office Hours', 'time_based', 'all', NULL, '08:00:00', '17:00:00', '1,2,3,4,5', 1, 'Standard office hours access', '2025-09-27 09:35:17'),
(2, 'Weekend Restrictions', 'day_based', 'visitor', NULL, NULL, NULL, '6,0', 1, 'Visitor restrictions on weekends', '2025-09-27 09:35:17'),
(3, '24/7 Staff Access', 'time_based', 'personell', NULL, '00:00:00', '23:59:59', '1,2,3,4,5,6,0', 1, '24/7 access for security personnel', '2025-09-27 09:35:17');

-- --------------------------------------------------------

--
-- Table structure for table `admin_2fa_codes`
--
-- Creation: Oct 29, 2025 at 06:08 AM
-- Last update: Nov 07, 2025 at 05:24 AM
--

CREATE TABLE `admin_2fa_codes` (
  `id` int(11) NOT NULL,
  `admin_id` int(11) NOT NULL,
  `verification_code` varchar(6) NOT NULL,
  `expires_at` datetime NOT NULL,
  `is_used` tinyint(4) DEFAULT 0,
  `used_at` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `admin_2fa_codes`
--

INSERT INTO `admin_2fa_codes` (`id`, `admin_id`, `verification_code`, `expires_at`, `is_used`, `used_at`, `created_at`) VALUES
(44, 69, '627183', '2025-11-07 13:34:08', 1, '2025-11-06 21:24:45', '2025-11-07 05:24:08');

-- --------------------------------------------------------

--
-- Table structure for table `admin_access_logs`
--
-- Creation: Oct 26, 2025 at 05:15 AM
-- Last update: Nov 07, 2025 at 05:31 AM
--

CREATE TABLE `admin_access_logs` (
  `id` int(11) NOT NULL,
  `admin_id` int(11) DEFAULT NULL,
  `username` varchar(255) DEFAULT NULL,
  `login_time` datetime DEFAULT current_timestamp(),
  `logout_time` datetime DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `location` varchar(255) DEFAULT NULL,
  `location_details` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL
) ;

--
-- Dumping data for table `admin_access_logs`
--

INSERT INTO `admin_access_logs` (`id`, `admin_id`, `username`, `login_time`, `logout_time`, `ip_address`, `user_agent`, `location`, `location_details`, `activity`, `status`, `created_at`) VALUES
(161, 69, 'wawa123', '2025-11-06 21:24:45', '2025-11-06 21:31:39', '175.176.69.181', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'Adlaon, Central Visayas, Philippines', NULL, '2FA Verification', 'success', '2025-11-07 05:24:45'),
(160, 69, 'wawa123', '2025-11-06 21:24:08', NULL, '175.176.69.181', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'Adlaon, Central Visayas, Philippines', NULL, 'Login', 'success', '2025-11-07 05:24:08'),
(159, 0, 'sddfasd', '2025-11-06 21:23:51', NULL, '175.176.69.181', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'Adlaon, Central Visayas, Philippines', NULL, 'Failed Login', 'failed', '2025-11-07 05:23:51'),
(158, 0, 'sddfasd', '2025-11-06 21:18:30', NULL, '175.176.69.181', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'Adlaon, Central Visayas, Philippines', NULL, 'Failed Login', 'failed', '2025-11-07 05:18:30'),
(157, 0, 'sddfasd', '2025-11-06 21:18:25', NULL, '175.176.69.181', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'Adlaon, Central Visayas, Philippines', NULL, 'Failed Login', 'failed', '2025-11-07 05:18:25'),
(107, 69, 'wawa123', '2025-10-30 11:20:32', '2025-10-29 20:20:43', '124.217.19.87', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'Cebu City, Central Visayas, Philippines', '{\"country\":\"Philippines\",\"region\":\"Central Visayas\",\"city\":\"Cebu City\",\"zip\":\"6000\",\"lat\":10.3099000000000007304379323613829910755157470703125,\"lon\":123.893000000000000682121026329696178436279296875,\"timezone\":\"Asia\\/Manila\",\"ip\":\"124.217.19.87\"}', 'Dashboard Access', 'success', '2025-10-30 03:20:32'),
(156, 0, 'sddfasd', '2025-11-06 21:18:21', NULL, '175.176.69.181', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'Adlaon, Central Visayas, Philippines', NULL, 'Failed Login', 'failed', '2025-11-07 05:18:21'),
(155, 69, 'wawa123', '2025-11-06 21:17:51', '2025-11-06 21:18:15', '175.176.69.181', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'Adlaon, Central Visayas, Philippines', NULL, '2FA Verification', 'success', '2025-11-07 05:17:51'),
(154, 69, 'wawa123', '2025-11-06 21:17:14', NULL, '175.176.69.181', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'Adlaon, Central Visayas, Philippines', NULL, 'Login', 'success', '2025-11-07 05:17:14'),
(153, 69, 'wawa123', '2025-11-06 21:00:27', '2025-11-06 21:17:01', '175.176.69.181', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'Adlaon, Central Visayas, Philippines', NULL, '2FA Verification', 'success', '2025-11-07 05:00:27'),
(152, 69, 'wawa123', '2025-11-06 20:58:25', NULL, '175.176.69.181', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'Adlaon, Central Visayas, Philippines', NULL, 'Login', 'success', '2025-11-07 04:58:25'),
(113, 69, 'wawa123', '2025-11-02 01:24:02', NULL, '124.217.19.87', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'Cebu City, Central Visayas, Philippines', NULL, 'Login', 'success', '2025-11-02 08:24:02'),
(114, 0, 'Wawa123', '2025-11-02 01:24:27', NULL, '124.217.19.87', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'Cebu City, Central Visayas, Philippines', NULL, 'Failed Login', 'failed', '2025-11-02 08:24:27'),
(115, 69, 'wawa123', '2025-11-02 01:24:34', NULL, '124.217.19.87', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'Cebu City, Central Visayas, Philippines', NULL, 'Login', 'success', '2025-11-02 08:24:34'),
(116, 69, 'wawa123', '2025-11-02 01:25:28', NULL, '124.217.19.87', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'Cebu City, Central Visayas, Philippines', NULL, 'Login', 'success', '2025-11-02 08:25:28'),
(117, 0, 'wawa123', '2025-11-02 01:40:37', NULL, '124.217.19.87', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'Cebu City, Central Visayas, Philippines', NULL, 'Failed Login', 'failed', '2025-11-02 08:40:37'),
(118, 69, 'wawa123', '2025-11-02 01:40:43', NULL, '124.217.19.87', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'Cebu City, Central Visayas, Philippines', NULL, 'Login', 'success', '2025-11-02 08:40:43'),
(119, 69, 'wawa123', '2025-11-02 01:45:55', NULL, '124.217.19.87', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'Cebu City, Central Visayas, Philippines', NULL, 'Login', 'success', '2025-11-02 08:45:55'),
(120, 69, 'wawa123', '2025-11-02 01:48:35', NULL, '124.217.19.87', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'Cebu City, Central Visayas, Philippines', NULL, 'Login', 'success', '2025-11-02 08:48:35'),
(121, 69, 'wawa123', '2025-11-02 01:48:48', NULL, '124.217.19.87', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'Cebu City, Central Visayas, Philippines', NULL, 'Login', 'success', '2025-11-02 08:48:48'),
(122, 69, 'wawa123', '2025-11-02 01:49:24', NULL, '124.217.19.87', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'Cebu City, Central Visayas, Philippines', NULL, 'Login', 'success', '2025-11-02 08:49:24'),
(123, 69, 'wawa123', '2025-11-02 01:49:51', NULL, '124.217.19.87', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'Cebu City, Central Visayas, Philippines', NULL, 'Login', 'success', '2025-11-02 08:49:51'),
(124, 69, 'wawa123', '2025-11-02 01:55:34', NULL, '124.217.19.87', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'Cebu City, Central Visayas, Philippines', NULL, 'Login', 'success', '2025-11-02 08:55:34'),
(125, 69, 'wawa123', '2025-11-02 01:56:12', NULL, '124.217.19.87', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'Cebu City, Central Visayas, Philippines', NULL, 'Login', 'success', '2025-11-02 08:56:12'),
(126, 69, 'wawa123', '2025-11-02 01:01:10', NULL, '124.217.19.87', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'Cebu City, Central Visayas, Philippines', NULL, 'Login', 'success', '2025-11-02 09:01:10'),
(127, 69, 'wawa123', '2025-11-02 01:01:18', NULL, '124.217.19.87', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'Cebu City, Central Visayas, Philippines', NULL, 'Login', 'success', '2025-11-02 09:01:18'),
(128, 69, 'wawa123', '2025-11-02 01:01:26', NULL, '124.217.19.87', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'Cebu City, Central Visayas, Philippines', NULL, 'Login', 'success', '2025-11-02 09:01:26'),
(129, 69, 'wawa123', '2025-11-02 01:04:35', NULL, '124.217.19.87', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'Cebu City, Central Visayas, Philippines', NULL, 'Login', 'success', '2025-11-02 09:04:35'),
(130, 69, 'wawa123', '2025-11-02 01:07:30', NULL, '124.217.19.87', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'Cebu City, Central Visayas, Philippines', NULL, 'Login', 'success', '2025-11-02 09:07:30'),
(131, 0, 'wawa123', '2025-11-02 01:08:15', NULL, '124.217.19.87', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'Cebu City, Central Visayas, Philippines', NULL, 'Failed Login', 'failed', '2025-11-02 09:08:15'),
(132, 0, 'wawa123', '2025-11-02 01:10:34', NULL, '124.217.19.87', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'Cebu City, Central Visayas, Philippines', NULL, 'Failed Login', 'failed', '2025-11-02 09:10:34'),
(133, 69, 'wawa123', '2025-11-02 01:13:33', NULL, '124.217.19.87', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'Cebu City, Central Visayas, Philippines', NULL, 'Login', 'success', '2025-11-02 09:13:33'),
(134, 0, 'wawa123', '2025-11-02 01:49:29', NULL, '124.217.19.87', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'Cebu City, Central Visayas, Philippines', NULL, 'Failed Login', 'failed', '2025-11-02 09:49:29'),
(135, 0, '123wawa123', '2025-11-02 01:49:37', NULL, '124.217.19.87', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'Cebu City, Central Visayas, Philippines', NULL, 'Failed Login', 'failed', '2025-11-02 09:49:37'),
(136, 69, 'wawa123', '2025-11-02 01:49:50', NULL, '124.217.19.87', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'Cebu City, Central Visayas, Philippines', NULL, 'Login', 'success', '2025-11-02 09:49:50'),
(137, 69, 'wawa123', '2025-11-02 01:54:32', NULL, '124.217.19.87', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'Cebu City, Central Visayas, Philippines', NULL, 'Login', 'success', '2025-11-02 09:54:32'),
(138, 69, 'wawa123', '2025-11-02 02:14:26', NULL, '124.217.19.87', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'Cebu City, Central Visayas, Philippines', NULL, 'Login', 'success', '2025-11-02 10:14:26'),
(139, 69, 'wawa123', '2025-11-02 02:29:00', NULL, '124.217.19.87', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'Cebu City, Central Visayas, Philippines', NULL, 'Login', 'success', '2025-11-02 10:29:00'),
(140, 69, 'wawa123', '2025-11-02 02:29:50', NULL, '124.217.19.87', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'Cebu City, Central Visayas, Philippines', NULL, '2FA Verification', 'success', '2025-11-02 10:29:50'),
(141, 69, 'wawa123', '2025-11-03 03:47:02', '2025-11-02 12:13:06', '124.217.19.87', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'Cebu City, Central Visayas, Philippines', '{\"country\":\"Philippines\",\"region\":\"Central Visayas\",\"city\":\"Cebu City\",\"zip\":\"6000\",\"lat\":10.3099000000000007304379323613829910755157470703125,\"lon\":123.893000000000000682121026329696178436279296875,\"timezone\":\"Asia\\/Manila\",\"ip\":\"124.217.19.87\"}', 'Dashboard Access', 'success', '2025-11-02 19:47:02'),
(142, 69, 'wawa123', '2025-11-02 13:25:49', NULL, '124.217.19.87', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'Cebu City, Central Visayas, Philippines', NULL, 'Login', 'success', '2025-11-02 21:25:49'),
(143, 69, 'wawa123', '2025-11-02 13:26:14', '2025-11-02 13:55:05', '124.217.19.87', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'Cebu City, Central Visayas, Philippines', NULL, '2FA Verification', 'success', '2025-11-02 21:26:14'),
(144, 69, 'wawa123', '2025-11-02 13:55:14', NULL, '124.217.19.87', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'Cebu City, Central Visayas, Philippines', NULL, 'Login', 'success', '2025-11-02 21:55:14'),
(145, 69, 'wawa123', '2025-11-02 13:58:26', NULL, '124.217.19.87', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'Cebu City, Central Visayas, Philippines', NULL, 'Login', 'success', '2025-11-02 21:58:26'),
(146, 69, 'wawa123', '2025-11-02 13:58:47', NULL, '124.217.19.87', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'Cebu City, Central Visayas, Philippines', NULL, '2FA Verification', 'success', '2025-11-02 21:58:47'),
(147, 69, 'wawa123', '2025-11-02 14:54:34', NULL, '124.217.19.87', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'Cebu City, Central Visayas, Philippines', NULL, 'Login', 'success', '2025-11-02 22:54:34'),
(148, 69, 'wawa123', '2025-11-02 14:54:59', '2025-11-02 15:48:20', '124.217.19.87', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'Cebu City, Central Visayas, Philippines', NULL, '2FA Verification', 'success', '2025-11-02 22:54:59'),
(149, 69, 'wawa123', '2025-11-06 07:55:20', NULL, '160.25.231.57', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'Makati City, Metro Manila, Philippines', NULL, 'Login', 'success', '2025-11-06 15:55:20'),
(150, 69, 'wawa123', '2025-11-06 07:55:51', NULL, '160.25.231.57', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'Makati City, Metro Manila, Philippines', NULL, '2FA Verification', 'success', '2025-11-06 15:55:51'),
(151, 69, 'wawa123', '2025-11-06 23:57:59', '2025-11-06 07:58:58', '160.25.231.57', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'Makati City, Metro Manila, Philippines', '{\"country\":\"Philippines\",\"region\":\"Metro Manila\",\"city\":\"Makati City\",\"zip\":\"1209\",\"lat\":14.5585000000000004405364961712621152400970458984375,\"lon\":121.0268999999999977035258780233561992645263671875,\"timezone\":\"Asia\\/Manila\",\"ip\":\"160.25.231.57\"}', 'Dashboard Access', 'success', '2025-11-06 15:57:59');

-- --------------------------------------------------------

--
-- Table structure for table `admin_logs`
--
-- Creation: Oct 25, 2025 at 10:46 PM
-- Last update: Nov 02, 2025 at 07:58 PM
--

CREATE TABLE `admin_logs` (
  `id` int(11) NOT NULL,
  `admin_id` int(11) NOT NULL,
  `action` varchar(500) NOT NULL,
  `timestamp` datetime NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `admin_logs`
--

INSERT INTO `admin_logs` (`id`, `admin_id`, `action`, `timestamp`) VALUES
(1, 69, 'Deleted visitor log - Name: Visitorr11, ID: 1010-1010', '2025-10-26 06:46:26'),
(2, 69, 'Forced time out for visitor: Aian Desucatan', '2025-10-26 06:48:25'),
(3, 69, 'Deleted visitor log - Name: Aian Desucatan, ID: 1010-1010', '2025-10-26 06:52:58'),
(4, 69, 'Forced time out for visitor: Aian Desucatan', '2025-10-28 23:51:30'),
(5, 69, 'Forced time out for visitor: Aswang Kooo', '2025-11-03 03:58:29'),
(6, 69, 'Deleted visitor log - Name: Visitorr11, ID: 1234-1234', '2025-11-03 03:58:42'),
(7, 69, 'Forced time out for visitor: Aian Desucatan', '2025-11-03 03:58:53');

-- --------------------------------------------------------

--
-- Table structure for table `admin_sessions`
--
-- Creation: Oct 11, 2025 at 02:30 PM
--

CREATE TABLE `admin_sessions` (
  `id` int(11) NOT NULL,
  `location` varchar(255) DEFAULT NULL,
  `ip_address` varchar(255) DEFAULT NULL,
  `device` varchar(255) DEFAULT NULL,
  `date_logged` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `admin_sessions`
--

INSERT INTO `admin_sessions` (`id`, `location`, `ip_address`, `device`, `date_logged`) VALUES
(20, 'Bacolod City, Philippines', '2001:fd8:18c3:a83b:bcae:71d0:b449:6826', '753ba0fc779274301e96db88e1da550691cb88122cdf15fbf9d771d52c765514', '2024-11-29 14:01:06'),
(21, 'Bacolod City, Philippines', '2001:fd8:18c3:a83b:bcae:71d0:b449:6826', '753ba0fc779274301e96db88e1da550691cb88122cdf15fbf9d771d52c765514', '2024-11-29 14:09:50'),
(22, 'Bacolod City, Philippines', '2001:fd8:18c3:a83b:bcae:71d0:b449:6826', '753ba0fc779274301e96db88e1da550691cb88122cdf15fbf9d771d52c765514', '2024-11-29 14:18:54'),
(23, 'Bacolod City, Philippines', '2001:fd8:18c3:a83b:bcae:71d0:b449:6826', '753ba0fc779274301e96db88e1da550691cb88122cdf15fbf9d771d52c765514', '2024-11-29 14:25:53'),
(24, 'Iloilo City, Philippines', '2001:fd8:1f57:2c65:6da3:0:d797:cfe4', 'c9690321bd40e98f164cc63addd64118f4a75e816bc917b0ce328eba3fb74f7f', '2024-11-30 04:03:20'),
(25, 'Angeles City, Philippines', '122.54.225.58', '22872a0e8a859fc23f285e3907d8e5393878a719ce2ef7e88710f94493e6caa9', '2024-11-30 06:50:00'),
(26, 'Bacolod City, Philippines', '110.54.230.243', 'afc45245ae7123b7307ff2ff1989351ad9e0f5e60bac421cafe2ef0d9aab1a99', '2024-12-02 07:04:24'),
(27, 'Bacolod City, Philippines', '110.54.182.120', 'f44add62ebd05f224aed9e3d079ad49afceec85155a8244eaf056667eacb6b79', '2024-12-03 08:40:32'),
(28, 'Bacolod City, Philippines', '110.54.182.28', '3a788e4888ec4fcbbf9f69ec6c2823c59b3c1390c78e64ca0a1d33cb68c936a3', '2024-12-04 09:18:21'),
(29, 'Bacolod City, Philippines', '110.54.228.25', '11348ee6f5a578c606f19c74de1f5ee36f0e1a494ea7e30f764b50009b4a8504', '2024-12-05 05:43:46'),
(30, 'Bacolod City, Philippines', '110.54.230.162', '7a6b147004d648d93f28b2b265c254a2343c81696d71208b769289709aa1093a', '2024-12-07 02:36:31'),
(31, 'Bacolod City, Philippines', '2001:fd8:18c6:4380:9909:c752:8176:5271', '1e9690a7d0a0af09696f73282bbb4423b1981a9c1f964af6fb408053cb874d7c', '2024-12-09 07:09:09'),
(32, 'Bacolod City, Philippines', '2001:fd8:18c6:4380:7030:f9e6:e5a6:df31', '8acde11aa109d5974dbfd238e7bc0991fc5e0ee426cce0f82e7c0a9559dedcb5', '2024-12-09 09:02:51'),
(33, 'Bacolod City, Philippines', '2001:fd8:18c6:4380:88af:d1d2:1804:f0e0', '13e52e3b9125f3e06ed6863101f6e9e14f208c55cea37a954eab2bfca55afecc', '2024-12-09 09:17:27'),
(34, 'Bacolod City, Philippines', '2001:fd8:18c6:4380:4525:5da4:de62:f243', '7e3cf50da282f87e90dfb14698bf453a867007f278d7c2c43c80bf39b156b0ed', '2024-12-09 09:48:33'),
(35, 'Bacolod City, Philippines', '110.54.229.117', '6725ee5c2302265ac41df547bb8abbb9e50c8da227d0629b85017bf984e8f7b4', '2024-12-10 05:41:39'),
(36, 'Iloilo City, Philippines', '2001:fd8:1a66:2af3:c0ec:ffd3:e72:27f6', '27696d5cbf6b53924931b141c9a4b756a06a5bbb25606f1aa2dec4c2c4d7b6b5', '2024-12-10 23:55:12'),
(37, 'Iloilo City, Philippines', '2001:fd8:1a66:2af3:60ce:d316:680d:7754', '11e481231cad1adabf6990175fb6b3b16886d0b3e87d7ff22babb16d861ec938', '2024-12-11 00:12:56'),
(38, 'Bacolod City, Philippines', '110.54.231.158', '4a5536c3623284d34d0fe6bc8813f4dd31e77d5b22c79051a0bed0208087fe5b', '2024-12-11 07:27:55'),
(39, 'Bacolod City, Philippines', '110.54.223.255', '46fbf0ab5355c3e7cc6ec6ba345c2b7bf74df9bc253d78146fd2246b70b2f2a4', '2024-12-12 13:34:28'),
(40, 'Iloilo City, Philippines', '2001:fd8:20e0:39ef:95c3:234e:281c:f86b', '1f4d159afb60bdba2c6ebbbebb042b97c3073695511dcf9fe3b0d18986f34422', '2024-12-13 10:04:10'),
(41, 'Iloilo City, Philippines', '2001:fd8:1a66:1aa7:304c:d1bb:3b98:bb06', '95920a6706809e494e97e59646d58527b47a1ddace21beb55a99a31fa8cf1962', '2024-12-13 14:39:45'),
(42, 'Bacolod City, Philippines', '2001:fd8:18c0:81d9:39b2:cc0a:ef43:ba70', '9b2bbfcfec3078ae42e4c234a5f1456b22bd79da735650be1922a223ca65be84', '2024-12-14 03:23:57'),
(43, 'Bacolod City, Philippines', '2001:fd8:18c0:81d9:50f8:fdc5:ced7:4c99', '8fd9e1d497cbe7ca6073d663edf38dd04e0bc4fef045c71509a9a6e2fc00fc66', '2024-12-14 03:26:25'),
(44, 'Bacolod City, Philippines', '110.54.172.98', '9de41373b8d96078056d502111fcb38424758ff502b1347d384e61567e38a303', '2024-12-19 01:55:25'),
(45, 'Iloilo City, Philippines', '112.198.112.224', 'f820dcfc2fef775d9b01ded465a37858c3ba1b6e7937ed96b9bd2d972dc44276', '2024-12-19 06:01:11'),
(46, 'Bacolod City, Philippines', '110.54.230.26', 'e45a99f619de50c4d82d68e445040fb18a76c35916f073f58c1e96049cd25ff4', '2024-12-22 15:07:58'),
(47, 'Bacolod City, Philippines', '110.54.230.62', '5c21a3aef3aaf1d26fd8650e856591cc8245f1174a12026e2a246e9ea696b2b0', '2024-12-23 03:36:16'),
(48, 'Bacolod City, Philippines', '110.54.230.72', '5a1530b0b44e13f9f62c8db91f36525efbb685fac180bd767be8b1ade698f08d', '2024-12-23 05:16:14'),
(49, 'Bacolod City, Philippines', '110.54.223.221', 'a77604ecf57a9d2e19622fe962bdbe140984b8525c2a8f2e9802827a016bfa70', '2025-01-06 13:21:56'),
(50, 'Bacolod City, Philippines', '110.54.223.254', 'b833507879e625e9bdfc0a1e7a804e94b19e88ebb52eecc6c0d22c34add5c6b6', '2025-01-14 07:33:37'),
(51, 'Bacolod City, Philippines', '110.54.223.246', 'c9af022e761e165c33e575a0a13f30d7a0ad066aab0f3c6e49b053275f797e38', '2025-01-14 18:59:16');

-- --------------------------------------------------------

--
-- Table structure for table `archived_attendance_20250727_190813`
--
-- Creation: Oct 11, 2025 at 02:30 PM
--

CREATE TABLE `archived_attendance_20250727_190813` (
  `id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `id_number` varchar(50) NOT NULL,
  `time_in` datetime NOT NULL,
  `time_out` datetime DEFAULT NULL,
  `department` varchar(255) NOT NULL,
  `location` varchar(255) NOT NULL,
  `instructor_id` varchar(9) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `archived_attendance_logs`
--
-- Creation: Oct 27, 2025 at 04:11 AM
-- Last update: Nov 02, 2025 at 08:51 PM
--

CREATE TABLE `archived_attendance_logs` (
  `id` int(11) NOT NULL,
  `student_id` int(11) DEFAULT NULL,
  `id_number` varchar(50) DEFAULT NULL,
  `fullname` varchar(255) DEFAULT NULL,
  `department` varchar(255) DEFAULT NULL,
  `location` varchar(255) DEFAULT NULL,
  `time_in` datetime DEFAULT NULL,
  `time_out` datetime DEFAULT NULL,
  `status` varchar(50) DEFAULT 'Present',
  `instructor_id` int(11) DEFAULT NULL,
  `instructor_name` varchar(255) DEFAULT NULL,
  `archived_at` timestamp NULL DEFAULT current_timestamp(),
  `session_date` date DEFAULT NULL,
  `year_level` varchar(50) DEFAULT NULL,
  `section` varchar(50) DEFAULT NULL,
  `subject_name` varchar(255) DEFAULT NULL,
  `room` varchar(255) DEFAULT NULL,
  `summary_id` int(11) DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `archived_attendance_logs`
--

INSERT INTO `archived_attendance_logs` (`id`, `student_id`, `id_number`, `fullname`, `department`, `location`, `time_in`, `time_out`, `status`, `instructor_id`, `instructor_name`, `archived_at`, `session_date`, `year_level`, `section`, `subject_name`, `room`, `summary_id`) VALUES
(180, 81, '2024-0380', 'Nino Mike S. Zaspa', 'West - 4th Year Year', 'Web Development', '2025-10-27 11:01:50', NULL, 'Present', 12, 'Mr.Alvin Billiones', '2025-10-27 03:26:23', '2025-10-27', '4th Year', 'West', 'Web Development', NULL, NULL),
(179, 75, '2024-1570', 'John Cyrus Pescante', 'West - 1st Year Year', 'Web Development', '2025-10-27 11:01:14', '2025-10-27 11:07:26', 'Present', 12, 'Mr.Alvin Billiones', '2025-10-27 03:26:23', '2025-10-27', '1st Year', 'West', 'Web Development', NULL, NULL),
(178, 80, '0004-0001', 'Angelo Derder', 'West - 4th Year Year', 'Web Development', '2025-10-27 10:54:54', NULL, 'Present', 12, 'Mr.Alvin Billiones', '2025-10-27 03:26:23', '2025-10-27', '4th Year', 'West', 'Web Development', NULL, NULL),
(177, 75, '2024-1570', 'John Cyrus Pescante', 'West - 1st Year Year', 'Program Logic Formulation', '2025-10-27 09:46:28', '2025-10-27 09:46:51', 'Present', 16, 'Ms.Jessica Alcazar', '2025-10-27 01:47:10', '2025-10-27', '1st Year', 'West', 'Program Logic Formulation', NULL, NULL),
(176, 79, '1212-1111', 'Try', 'West - 1st Year Year', 'Program Logic Formulation', '2025-10-27 09:38:28', '2025-10-27 09:38:29', 'Present', 16, 'Ms.Jessica Alcazar', '2025-10-27 01:39:00', '2025-10-27', '1st Year', 'West', 'Program Logic Formulation', NULL, NULL),
(175, 75, '2024-1570', 'John Cyrus Pescante', 'West - 1st Year Year', 'Program Logic Formulation', '2025-10-27 09:33:02', '2025-10-27 09:34:07', 'Present', 16, 'Ms.Jessica Alcazar', '2025-10-27 01:34:47', '2025-10-27', '1st Year', 'West', 'Program Logic Formulation', NULL, NULL),
(174, 75, '2024-1570', 'John Cyrus Pescante', 'West - 1st Year Year', 'Program Logic Formulation', '2025-10-27 09:30:50', NULL, 'Present', 16, 'Ms.Jessica Alcazar', '2025-10-27 01:31:09', '2025-10-27', '1st Year', 'West', 'Program Logic Formulation', NULL, NULL),
(172, 79, '1212-1111', 'Try', 'West - 1st Year Year', 'Program Logic Formulation', '2025-10-27 09:10:24', '2025-10-27 09:10:59', 'Present', 16, 'Ms.Jessica Alcazar', '2025-10-27 01:31:09', '2025-10-27', '1st Year', 'West', 'Program Logic Formulation', NULL, NULL),
(173, 77, '2024-1697', 'Rose Ann V. Forrosuelo', 'West - 1st Year Year', 'Program Logic Formulation', '2025-10-27 09:16:42', '2025-10-27 09:22:32', 'Present', 16, 'Ms.Jessica Alcazar', '2025-10-27 01:31:09', '2025-10-27', '1st Year', 'West', 'Program Logic Formulation', NULL, NULL),
(171, 75, '2024-1570', 'John Cyrus Pescante', 'West - 1st Year Year', 'Program Logic Formulation', '2025-10-27 09:06:57', '2025-10-27 09:16:27', 'Present', 16, 'Ms.Jessica Alcazar', '2025-10-27 01:31:09', '2025-10-27', '1st Year', 'West', 'Program Logic Formulation', NULL, NULL),
(170, 83, '1111-1111', 'ryyyyyy', 'West - 4th Year Year', 'ITE PROF ELECT 4', NULL, NULL, 'Absent', 12, 'Mr.Alvin Billiones', '2025-10-24 02:49:00', '2025-10-24', '4th Year', 'West', 'ITE PROF ELECT 4', NULL, NULL),
(168, 81, '2024-0380', 'Nino Mike S. Zaspa', 'West - 4th Year Year', 'ITE PROF ELECT 4', '2025-10-24 10:40:32', '2025-10-24 10:47:47', 'Present', 12, 'Mr.Alvin Billiones', '2025-10-24 02:49:00', '2025-10-24', '4th Year', 'West', 'ITE PROF ELECT 4', NULL, NULL),
(169, 80, '0004-0001', 'Angelo Derder', 'West - 4th Year Year', 'ITE PROF ELECT 4', '2025-10-24 10:48:11', '2025-10-24 10:48:43', 'Present', 12, 'Mr.Alvin Billiones', '2025-10-24 02:49:00', '2025-10-24', '4th Year', 'West', 'ITE PROF ELECT 4', NULL, NULL),
(167, 81, '2024-0380', 'Nino Mike S. Zaspa', 'West - 4th Year Year', 'ITE PROF ELECT 4', '2025-10-24 10:39:13', NULL, 'Present', 12, 'Mr.Alvin Billiones', '2025-10-24 02:49:00', '2025-10-24', '4th Year', 'West', 'ITE PROF ELECT 4', NULL, NULL),
(166, 83, '1111-1111', 'ryyyyyy', 'West - 4th Year Year', 'ITE PROF ELECT 4', '2025-10-24 10:34:06', NULL, 'Present', 12, 'Mr.Alvin Billiones', '2025-10-24 02:39:04', '2025-10-24', '4th Year', 'West', 'ITE PROF ELECT 4', NULL, NULL),
(165, 80, '0004-0001', 'Angelo Derder', 'West - 4th Year Year', 'ITE PROF ELECT 4', '2025-10-24 10:33:46', NULL, 'Present', 12, 'Mr.Alvin Billiones', '2025-10-24 02:39:04', '2025-10-24', '4th Year', 'West', 'ITE PROF ELECT 4', NULL, NULL),
(164, 80, '0004-0001', 'Angelo Derder', 'West - 4th Year Year', 'ITE PROF ELECT 4', '2025-10-24 10:30:17', NULL, 'Present', 12, 'Mr.Alvin Billiones', '2025-10-24 02:39:04', '2025-10-24', '4th Year', 'West', 'ITE PROF ELECT 4', NULL, NULL),
(163, 81, '2024-0380', 'Nino Mike S. Zaspa', 'West - 4th Year Year', 'ITE PROF ELECT 4', '2025-10-24 10:21:35', NULL, 'Present', 12, 'Mr.Alvin Billiones', '2025-10-24 02:39:04', '2025-10-24', '4th Year', 'West', 'ITE PROF ELECT 4', NULL, NULL),
(162, 82, '0001-0002', 'Tryyy', 'West - 1st Year Year', 'Program Logic Formulation', NULL, NULL, 'Absent', 13, 'Mr.Danilo Villarino', '2025-10-24 02:20:45', '2025-10-24', '1st Year', 'West', 'Program Logic Formulation', NULL, NULL),
(161, 78, '0000-0001', 'Truy', 'West - 1st Year Year', 'Program Logic Formulation', NULL, NULL, 'Absent', 13, 'Mr.Danilo Villarino', '2025-10-24 02:20:45', '2025-10-24', '1st Year', 'West', 'Program Logic Formulation', NULL, NULL),
(160, 75, '2024-1570', 'John Cyrus Pescante', 'West - 1st Year Year', 'Program Logic Formulation', '2025-10-24 10:19:46', NULL, 'Present', 13, 'Mr.Danilo Villarino', '2025-10-24 02:20:45', '2025-10-24', '1st Year', 'West', 'Program Logic Formulation', NULL, NULL),
(159, 75, '2024-1570', 'John Cyrus Pescante', 'West - 1st Year Year', 'Program Logic Formulation', '2025-10-24 10:19:31', NULL, 'Present', 13, 'Mr.Danilo Villarino', '2025-10-24 02:20:45', '2025-10-24', '1st Year', 'West', 'Program Logic Formulation', NULL, NULL),
(158, 77, '2024-1697', 'Rose Ann V. Forrosuelo', 'West - 1st Year Year', 'Program Logic Formulation', '2025-10-24 10:18:00', NULL, 'Present', 13, 'Mr.Danilo Villarino', '2025-10-24 02:20:45', '2025-10-24', '1st Year', 'West', 'Program Logic Formulation', NULL, NULL),
(157, 79, '1212-1111', 'Try', 'West - 1st Year Year', 'Program Logic Formulation', '2025-10-24 10:15:17', NULL, 'Present', 13, 'Mr.Danilo Villarino', '2025-10-24 02:20:45', '2025-10-24', '1st Year', 'West', 'Program Logic Formulation', NULL, NULL),
(156, 81, '2024-0380', 'Nino Mike S. Zaspa', 'West - 4th Year Year', 'Program Logic Formulation', '2025-10-24 10:14:57', NULL, 'Present', 13, 'Mr.Danilo Villarino', '2025-10-24 02:20:45', '2025-10-24', '4th Year', 'West', 'Program Logic Formulation', NULL, NULL),
(155, 81, '2024-0380', 'Nino Mike S. Zaspa', 'West - 4th Year Year', 'Program Logic Formulation', '2025-10-24 10:10:36', NULL, 'Present', 13, 'Mr.Danilo Villarino', '2025-10-24 02:20:45', '2025-10-24', '4th Year', 'West', 'Program Logic Formulation', NULL, NULL),
(154, 81, '2024-0380', 'Nino Mike S. Zaspa', 'West - 4th Year Year', 'Program Logic Formulation', '2025-10-24 09:47:05', NULL, 'Present', 13, 'Mr.Danilo Villarino', '2025-10-24 02:20:45', '2025-10-24', '4th Year', 'West', 'Program Logic Formulation', NULL, NULL),
(153, 81, '2024-0380', 'Nino Mike S. Zaspa', 'West - 4th Year Year', 'Program Logic Formulation', '2025-10-24 09:30:06', NULL, 'Present', 13, 'Mr.Danilo Villarino', '2025-10-24 02:20:45', '2025-10-24', '4th Year', 'West', 'Program Logic Formulation', NULL, NULL),
(152, 75, '2024-1570', 'John Cyrus Pescante', 'West - 1st Year Year', 'Program Logic Formulation', '2025-10-24 09:14:47', NULL, 'Present', 13, 'Mr.Danilo Villarino', '2025-10-24 02:20:45', '2025-10-24', '1st Year', 'West', 'Program Logic Formulation', NULL, NULL),
(151, 75, '2024-1570', 'John Cyrus Pescante', 'West - 1st Year Year', 'Program Logic Formulation', '2025-10-24 08:59:46', NULL, 'Present', 13, 'Mr.Danilo Villarino', '2025-10-24 02:20:45', '2025-10-24', '1st Year', 'West', 'Program Logic Formulation', NULL, NULL),
(150, 75, '2024-1570', 'John Cyrus Pescante', 'West - 1st Year Year', 'Platform Technologies', '2025-10-24 08:38:35', NULL, 'Present', 13, 'Mr.Danilo Villarino', '2025-10-24 00:40:02', '2025-10-24', '1st Year', 'West', 'Platform Technologies', NULL, NULL),
(149, 82, '0001-0002', 'Tryyy', 'West - 1st Year Year', 'Platform Technologies', NULL, NULL, 'Absent', 13, 'Mr.Danilo Villarino', '2025-10-24 00:30:55', '2025-10-24', '1st Year', 'West', 'Platform Technologies', NULL, NULL),
(148, 79, '1212-1111', 'Try', 'West - 1st Year Year', 'Platform Technologies', NULL, NULL, 'Absent', 13, 'Mr.Danilo Villarino', '2025-10-24 00:30:55', '2025-10-24', '1st Year', 'West', 'Platform Technologies', NULL, NULL),
(147, 78, '0000-0001', 'Truy', 'West - 1st Year Year', 'Platform Technologies', NULL, NULL, 'Absent', 13, 'Mr.Danilo Villarino', '2025-10-24 00:30:55', '2025-10-24', '1st Year', 'West', 'Platform Technologies', NULL, NULL),
(146, 77, '2024-1697', 'Rose Ann V. Forrosuelo', 'West - 1st Year Year', 'Platform Technologies', NULL, NULL, 'Absent', 13, 'Mr.Danilo Villarino', '2025-10-24 00:30:55', '2025-10-24', '1st Year', 'West', 'Platform Technologies', NULL, NULL),
(145, 75, '2024-1570', 'John Cyrus Pescante', 'West - 1st Year Year', 'Platform Technologies', NULL, NULL, 'Absent', 13, 'Mr.Danilo Villarino', '2025-10-24 00:30:55', '2025-10-24', '1st Year', 'West', 'Platform Technologies', NULL, NULL),
(144, 82, '0001-0002', 'Tryyy', 'West - 1st Year Year', 'Platform Technologies', NULL, NULL, 'Absent', 13, 'Mr.Danilo Villarino', '2025-10-24 00:12:01', '2025-10-24', '1st Year', 'West', 'Platform Technologies', NULL, NULL),
(143, 79, '1212-1111', 'Try', 'West - 1st Year Year', 'Platform Technologies', NULL, NULL, 'Absent', 13, 'Mr.Danilo Villarino', '2025-10-24 00:12:01', '2025-10-24', '1st Year', 'West', 'Platform Technologies', NULL, NULL),
(142, 78, '0000-0001', 'Truy', 'West - 1st Year Year', 'Platform Technologies', NULL, NULL, 'Absent', 13, 'Mr.Danilo Villarino', '2025-10-24 00:12:01', '2025-10-24', '1st Year', 'West', 'Platform Technologies', NULL, NULL),
(141, 77, '2024-1697', 'Rose Ann V. Forrosuelo', 'West - 1st Year Year', 'Platform Technologies', NULL, NULL, 'Absent', 13, 'Mr.Danilo Villarino', '2025-10-24 00:12:01', '2025-10-24', '1st Year', 'West', 'Platform Technologies', NULL, NULL),
(140, 75, '2024-1570', 'John Cyrus Pescante', 'West - 1st Year Year', 'Platform Technologies', NULL, NULL, 'Absent', 13, 'Mr.Danilo Villarino', '2025-10-24 00:12:01', '2025-10-24', '1st Year', 'West', 'Platform Technologies', NULL, NULL),
(139, 82, '0001-0002', 'Tryyy', 'West - 1st Year Year', 'Platform Technologies', NULL, NULL, 'Absent', 13, 'Mr.Danilo Villarino', '2025-10-23 23:43:59', '2025-10-24', '1st Year', 'West', 'Platform Technologies', NULL, NULL),
(138, 79, '1212-1111', 'Try', 'West - 1st Year Year', 'Platform Technologies', NULL, NULL, 'Absent', 13, 'Mr.Danilo Villarino', '2025-10-23 23:43:59', '2025-10-24', '1st Year', 'West', 'Platform Technologies', NULL, NULL),
(137, 78, '0000-0001', 'Truy', 'West - 1st Year Year', 'Platform Technologies', NULL, NULL, 'Absent', 13, 'Mr.Danilo Villarino', '2025-10-23 23:43:59', '2025-10-24', '1st Year', 'West', 'Platform Technologies', NULL, NULL),
(136, 77, '2024-1697', 'Rose Ann V. Forrosuelo', 'West - 1st Year Year', 'Platform Technologies', NULL, NULL, 'Absent', 13, 'Mr.Danilo Villarino', '2025-10-23 23:43:59', '2025-10-24', '1st Year', 'West', 'Platform Technologies', NULL, NULL),
(135, 75, '2024-1570', 'John Cyrus Pescante', 'West - 1st Year Year', 'Platform Technologies', NULL, NULL, 'Absent', 13, 'Mr.Danilo Villarino', '2025-10-23 23:43:59', '2025-10-24', '1st Year', 'West', 'Platform Technologies', NULL, NULL),
(134, 82, '0001-0002', 'Tryyy', 'West - 1st Year Year', 'ITE PROF ELECT 4', NULL, NULL, 'Absent', 12, 'Mr.Alvin Billiones', '2025-10-23 23:40:43', '2025-10-24', '1st Year', 'West', 'ITE PROF ELECT 4', NULL, NULL),
(133, 79, '1212-1111', 'Try', 'West - 1st Year Year', 'ITE PROF ELECT 4', NULL, NULL, 'Absent', 12, 'Mr.Alvin Billiones', '2025-10-23 23:40:43', '2025-10-24', '1st Year', 'West', 'ITE PROF ELECT 4', NULL, NULL),
(132, 78, '0000-0001', 'Truy', 'West - 1st Year Year', 'ITE PROF ELECT 4', NULL, NULL, 'Absent', 12, 'Mr.Alvin Billiones', '2025-10-23 23:40:43', '2025-10-24', '1st Year', 'West', 'ITE PROF ELECT 4', NULL, NULL),
(131, 77, '2024-1697', 'Rose Ann V. Forrosuelo', 'West - 1st Year Year', 'ITE PROF ELECT 4', NULL, NULL, 'Absent', 12, 'Mr.Alvin Billiones', '2025-10-23 23:40:43', '2025-10-24', '1st Year', 'West', 'ITE PROF ELECT 4', NULL, NULL),
(130, 75, '2024-1570', 'John Cyrus Pescante', 'West - 1st Year Year', 'ITE PROF ELECT 4', NULL, NULL, 'Absent', 12, 'Mr.Alvin Billiones', '2025-10-23 23:40:43', '2025-10-24', '1st Year', 'West', 'ITE PROF ELECT 4', NULL, NULL),
(129, 82, '0001-0002', 'Tryyy', 'West - 1st Year Year', 'Program Logic Formulation', NULL, NULL, 'Absent', 13, 'Mr.Danilo Villarino', '2025-10-23 23:38:59', '2025-10-24', '1st Year', 'West', 'Program Logic Formulation', NULL, NULL),
(128, 79, '1212-1111', 'Try', 'West - 1st Year Year', 'Program Logic Formulation', NULL, NULL, 'Absent', 13, 'Mr.Danilo Villarino', '2025-10-23 23:38:59', '2025-10-24', '1st Year', 'West', 'Program Logic Formulation', NULL, NULL),
(127, 78, '0000-0001', 'Truy', 'West - 1st Year Year', 'Program Logic Formulation', NULL, NULL, 'Absent', 13, 'Mr.Danilo Villarino', '2025-10-23 23:38:59', '2025-10-24', '1st Year', 'West', 'Program Logic Formulation', NULL, NULL),
(126, 77, '2024-1697', 'Rose Ann V. Forrosuelo', 'West - 1st Year Year', 'Program Logic Formulation', NULL, NULL, 'Absent', 13, 'Mr.Danilo Villarino', '2025-10-23 23:38:59', '2025-10-24', '1st Year', 'West', 'Program Logic Formulation', NULL, NULL),
(125, 75, '2024-1570', 'John Cyrus Pescante', 'West - 1st Year Year', 'Program Logic Formulation', NULL, NULL, 'Absent', 13, 'Mr.Danilo Villarino', '2025-10-23 23:38:59', '2025-10-24', '1st Year', 'West', 'Program Logic Formulation', NULL, NULL),
(124, 82, '0001-0002', 'Tryyy', 'West - 1st Year Year', 'ITE PROF ELECT 4', NULL, NULL, 'Absent', 12, 'Mr.Alvin Billiones', '2025-10-23 23:38:15', '2025-10-24', '1st Year', 'West', 'ITE PROF ELECT 4', NULL, NULL),
(123, 79, '1212-1111', 'Try', 'West - 1st Year Year', 'ITE PROF ELECT 4', NULL, NULL, 'Absent', 12, 'Mr.Alvin Billiones', '2025-10-23 23:38:15', '2025-10-24', '1st Year', 'West', 'ITE PROF ELECT 4', NULL, NULL),
(122, 78, '0000-0001', 'Truy', 'West - 1st Year Year', 'ITE PROF ELECT 4', NULL, NULL, 'Absent', 12, 'Mr.Alvin Billiones', '2025-10-23 23:38:15', '2025-10-24', '1st Year', 'West', 'ITE PROF ELECT 4', NULL, NULL),
(121, 75, '2024-1570', 'John Cyrus Pescante', 'West - 1st Year Year', 'ITE PROF ELECT 4', NULL, NULL, 'Absent', 12, 'Mr.Alvin Billiones', '2025-10-23 23:38:15', '2025-10-24', '1st Year', 'West', 'ITE PROF ELECT 4', NULL, NULL),
(120, 77, '2024-1697', 'Rose Ann V. Forrosuelo', 'West - 1st Year Year', 'ITE PROF ELECT 4', '2025-10-24 07:33:52', NULL, 'Present', 12, 'Mr.Alvin Billiones', '2025-10-23 23:38:15', '2025-10-24', '1st Year', 'West', 'ITE PROF ELECT 4', NULL, NULL),
(119, 77, '2024-1697', 'Rose Ann V. Forrosuelo', 'West - 1st Year Year', 'ITE PROF ELECT 4', '2025-10-24 07:33:17', NULL, 'Present', 12, 'Mr.Alvin Billiones', '2025-10-23 23:38:15', '2025-10-24', '1st Year', 'West', 'ITE PROF ELECT 4', NULL, NULL),
(118, 82, '0001-0002', 'Tryyy', 'West - 1st Year Year', 'ITE PROF ELECT 4', NULL, NULL, 'Absent', 12, 'Mr.Alvin Billiones', '2025-10-23 22:59:25', '2025-10-24', '1st Year', 'West', 'ITE PROF ELECT 4', NULL, NULL),
(117, 78, '0000-0001', 'Truy', 'West - 1st Year Year', 'ITE PROF ELECT 4', NULL, NULL, 'Absent', 12, 'Mr.Alvin Billiones', '2025-10-23 22:59:25', '2025-10-24', '1st Year', 'West', 'ITE PROF ELECT 4', NULL, NULL),
(116, 77, '2024-1697', 'Rose Ann V. Forrosuelo', 'West - 1st Year Year', 'ITE PROF ELECT 4', NULL, NULL, 'Absent', 12, 'Mr.Alvin Billiones', '2025-10-23 22:59:25', '2025-10-24', '1st Year', 'West', 'ITE PROF ELECT 4', NULL, NULL),
(115, 75, '2024-1570', 'John Cyrus Pescante', 'West - 1st Year Year', 'ITE PROF ELECT 4', NULL, NULL, 'Absent', 12, 'Mr.Alvin Billiones', '2025-10-23 22:59:25', '2025-10-24', '1st Year', 'West', 'ITE PROF ELECT 4', NULL, NULL),
(114, 79, '1212-1111', 'Try', 'West - 1st Year Year', 'ITE PROF ELECT 4', '2025-10-24 06:46:50', NULL, 'Present', 12, 'Mr.Alvin Billiones', '2025-10-23 22:59:25', '2025-10-24', '1st Year', 'West', 'ITE PROF ELECT 4', NULL, NULL),
(113, 82, '0001-0002', 'Tryyy', 'West - 1st Year Year', 'ITE PROF ELECT 4', NULL, NULL, 'Absent', 12, 'Mr.Alvin Billiones', '2025-10-23 22:45:54', '2025-10-24', '1st Year', 'West', 'ITE PROF ELECT 4', NULL, NULL),
(112, 79, '1212-1111', 'Try', 'West - 1st Year Year', 'ITE PROF ELECT 4', NULL, NULL, 'Absent', 12, 'Mr.Alvin Billiones', '2025-10-23 22:45:54', '2025-10-24', '1st Year', 'West', 'ITE PROF ELECT 4', NULL, NULL),
(111, 78, '0000-0001', 'Truy', 'West - 1st Year Year', 'ITE PROF ELECT 4', NULL, NULL, 'Absent', 12, 'Mr.Alvin Billiones', '2025-10-23 22:45:54', '2025-10-24', '1st Year', 'West', 'ITE PROF ELECT 4', NULL, NULL),
(110, 75, '2024-1570', 'John Cyrus Pescante', 'West - 1st Year Year', 'ITE PROF ELECT 4', NULL, NULL, 'Absent', 12, 'Mr.Alvin Billiones', '2025-10-23 22:45:54', '2025-10-24', '1st Year', 'West', 'ITE PROF ELECT 4', NULL, NULL),
(109, 77, '2024-1697', 'Rose Ann V. Forrosuelo', 'West - 1st Year Year', 'ITE PROF ELECT 4', '2025-10-24 06:35:38', NULL, 'Present', 12, 'Mr.Alvin Billiones', '2025-10-23 22:45:54', '2025-10-24', '1st Year', 'West', 'ITE PROF ELECT 4', NULL, NULL),
(181, 75, '2024-1570', 'John Cyrus Pescante', 'West - 1st Year Year', 'Web Development', '2025-10-27 11:20:03', '2025-10-27 11:25:49', 'Present', 12, 'Mr.Alvin Billiones', '2025-10-27 03:26:23', '2025-10-27', '1st Year', 'West', 'Web Development', NULL, NULL),
(182, 83, '1111-1111', 'ryyyyyy', 'West - 4th Year Year', 'Web Development', '2025-10-27 11:26:07', NULL, 'Present', 12, 'Mr.Alvin Billiones', '2025-10-27 03:26:23', '2025-10-27', '4th Year', 'West', 'Web Development', NULL, NULL),
(183, 75, '2024-1570', 'John Cyrus Pescante', 'West - 1st Year Year', 'Web Development', '2025-10-27 11:27:28', NULL, 'Present', 12, 'Mr.Alvin Billiones', '2025-10-27 03:29:37', '2025-10-27', '1st Year', 'West', 'Web Development', NULL, NULL),
(184, 80, '0004-0001', 'Angelo Derder', 'West - 4th Year Year', 'Web Development', '2025-10-27 11:27:55', NULL, 'Present', 12, 'Mr.Alvin Billiones', '2025-10-27 03:29:37', '2025-10-27', '4th Year', 'West', 'Web Development', NULL, NULL),
(185, 77, '2024-1697', 'Rose Ann V. Forrosuelo', 'West - 1st Year Year', 'Web Development', NULL, NULL, 'Absent', 12, 'Mr.Alvin Billiones', '2025-10-27 03:29:37', '2025-10-27', '1st Year', 'West', 'Web Development', NULL, NULL),
(186, 79, '1212-1111', 'Try', 'West - 1st Year Year', 'Web Development', NULL, NULL, 'Absent', 12, 'Mr.Alvin Billiones', '2025-10-27 03:29:37', '2025-10-27', '1st Year', 'West', 'Web Development', NULL, NULL),
(187, 79, '1212-1111', 'Try', 'West - 1st Year Year', 'Web Development', '2025-10-27 11:29:58', NULL, 'Present', 12, 'Mr.Alvin Billiones', '2025-10-27 03:33:30', '2025-10-27', '1st Year', 'West', 'Web Development', NULL, NULL),
(188, 75, '2024-1570', 'John Cyrus Pescante', 'West - 1st Year Year', 'Web Development', NULL, NULL, 'Absent', 12, 'Mr.Alvin Billiones', '2025-10-27 03:33:30', '2025-10-27', '1st Year', 'West', 'Web Development', NULL, NULL),
(189, 77, '2024-1697', 'Rose Ann V. Forrosuelo', 'West - 1st Year Year', 'Web Development', NULL, NULL, 'Absent', 12, 'Mr.Alvin Billiones', '2025-10-27 03:33:30', '2025-10-27', '1st Year', 'West', 'Web Development', NULL, NULL),
(190, 79, '1212-1111', 'Try', 'West - 1st Year Year', 'Web Development', '2025-10-27 12:10:08', '2025-10-27 12:10:08', 'Present', 12, 'Mr.Alvin Billiones', '2025-10-27 04:11:59', '2025-10-27', '1st Year', 'West', 'Web Development', 'ComLab1', NULL),
(191, 79, '1212-1111', 'Try', 'West - 1st Year Year', 'Web Development', '2025-10-27 12:10:08', '2025-10-27 12:10:08', 'Present', 12, 'Mr.Alvin Billiones', '2025-10-27 04:13:07', '2025-10-27', '1st Year', 'West', 'Web Development', 'ComLab1', NULL),
(192, 79, '1212-1111', 'Try', 'West - 1st Year Year', 'Web Development', '2025-10-27 12:10:08', '2025-10-27 12:10:08', 'Present', 12, 'Mr.Alvin Billiones', '2025-10-27 04:15:32', '2025-10-27', '1st Year', 'West', 'Web Development', 'ComLab1', NULL),
(193, 75, '2024-1570', 'John Cyrus Pescante', 'West - 1st Year Year', 'Web Development', NULL, NULL, 'Absent', 12, 'Mr.Alvin Billiones', '2025-10-27 04:15:32', '2025-10-27', '1st Year', 'West', 'Web Development', 'ComLab1', NULL),
(194, 77, '2024-1697', 'Rose Ann V. Forrosuelo', 'West - 1st Year Year', 'Web Development', NULL, NULL, 'Absent', 12, 'Mr.Alvin Billiones', '2025-10-27 04:15:32', '2025-10-27', '1st Year', 'West', 'Web Development', 'ComLab1', NULL),
(195, 80, '0004-0001', 'Angelo Derder', 'West - 4th Year Year', 'Web Development', '2025-10-27 12:16:13', NULL, 'Present', 12, 'Mr.Alvin Billiones', '2025-10-27 04:16:25', '2025-10-27', '4th Year', 'West', 'Web Development', 'ComLab1', NULL),
(196, 81, '2024-0380', 'Nino Mike S. Zaspa', 'West - 4th Year Year', 'Web Development', NULL, NULL, 'Absent', 12, 'Mr.Alvin Billiones', '2025-10-27 04:16:25', '2025-10-27', '4th Year', 'West', 'Web Development', 'ComLab1', NULL),
(197, 83, '1111-1111', 'ryyyyyy', 'West - 4th Year Year', 'Web Development', NULL, NULL, 'Absent', 12, 'Mr.Alvin Billiones', '2025-10-27 04:16:25', '2025-10-27', '4th Year', 'West', 'Web Development', 'ComLab1', NULL),
(198, 75, '2024-1570', 'John Cyrus Pescante', 'West - 1st Year Year', 'Sub3', '2025-10-28 07:45:40', '2025-10-28 07:45:40', 'Present', 16, 'Ms.Jessica Alcazar', '2025-10-28 00:01:04', '2025-10-28', '1st Year', 'West', 'Sub3', 'ComLab1', NULL),
(199, 77, '2024-1697', 'Rose Ann V. Forrosuelo', 'West - 1st Year Year', 'Sub3', '2025-10-28 07:54:12', '2025-10-28 08:00:21', 'Present', 16, 'Ms.Jessica Alcazar', '2025-10-28 00:01:04', '2025-10-28', '1st Year', 'West', 'Sub3', 'ComLab1', NULL),
(200, 79, '1212-1111', 'Try', 'West - 1st Year Year', 'Sub3', NULL, NULL, 'Absent', 16, 'Ms.Jessica Alcazar', '2025-10-28 00:01:04', '2025-10-28', '1st Year', 'West', 'Sub3', 'ComLab1', NULL),
(201, 75, '2024-1570', 'John Cyrus Pescante', 'West - 1st Year Year', 'Sub3', '2025-10-28 14:01:50', NULL, 'Present', 16, 'Ms.Jessica Alcazar', '2025-10-28 06:03:14', '2025-10-28', '1st Year', 'West', 'Sub3', 'ComLab1', NULL),
(202, 77, '2024-1697', 'Rose Ann V. Forrosuelo', 'West - 1st Year Year', 'Sub3', NULL, NULL, 'Absent', 16, 'Ms.Jessica Alcazar', '2025-10-28 06:03:14', '2025-10-28', '1st Year', 'West', 'Sub3', 'ComLab1', NULL),
(203, 79, '1212-1111', 'Try', 'West - 1st Year Year', 'Sub3', NULL, NULL, 'Absent', 16, 'Ms.Jessica Alcazar', '2025-10-28 06:03:14', '2025-10-28', '1st Year', 'West', 'Sub3', 'ComLab1', NULL),
(210, 77, '2024-1697', 'Rose Ann V. Forrosuelo', 'West - 1st Year Year', 'Program Logic Formulation', '2025-11-03 04:41:47', NULL, 'Present', 16, 'Ms.Jessica Alcazar', '2025-11-02 20:51:31', '2025-11-03', '1st Year', 'West', 'Program Logic Formulation', 'ComLab1', NULL),
(209, 79, '1212-1111', 'Try', 'West - 1st Year Year', 'Program Logic Formulation', '2025-11-03 04:41:09', NULL, 'Present', 16, 'Ms.Jessica Alcazar', '2025-11-02 20:51:31', '2025-11-03', '1st Year', 'West', 'Program Logic Formulation', 'ComLab1', NULL),
(207, 77, '2024-1697', 'Rose Ann V. Forrosuelo', 'West - 1st Year Year', 'Program Logic Formulation', '2025-10-29 23:01:45', '2025-10-29 23:11:59', 'Present', 11, 'Mr.Kurt Alegre', '2025-10-29 15:12:11', '2025-10-29', '1st Year', 'West', 'Program Logic Formulation', 'IT-LEC2', NULL),
(208, 79, '1212-1111', 'Try', 'West - 1st Year Year', 'Program Logic Formulation', '2025-10-29 23:02:21', '2025-10-29 23:05:07', 'Present', 11, 'Mr.Kurt Alegre', '2025-10-29 15:12:11', '2025-10-29', '1st Year', 'West', 'Program Logic Formulation', 'IT-LEC2', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `archived_instructor_logs`
--
-- Creation: Oct 11, 2025 at 02:30 PM
--

CREATE TABLE `archived_instructor_logs` (
  `id` int(11) NOT NULL,
  `instructor_id` int(11) DEFAULT NULL,
  `id_number` varchar(20) DEFAULT NULL,
  `time_in` datetime DEFAULT NULL,
  `time_out` datetime DEFAULT NULL,
  `department` varchar(100) DEFAULT NULL,
  `location` varchar(100) DEFAULT NULL,
  `status` varchar(20) DEFAULT NULL,
  `swapped_with` varchar(255) NOT NULL,
  `swap_time` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `archived_instructor_logs`
--

INSERT INTO `archived_instructor_logs` (`id`, `instructor_id`, `id_number`, `time_in`, `time_out`, `department`, `location`, `status`, `swapped_with`, `swap_time`) VALUES
(56, 16, '2024-0117', '2025-08-06 16:06:23', '2025-08-06 16:06:35', 'BSIT', 'ComLab1', 'saved', '', '2025-09-10 02:18:57'),
(57, 16, '2024-0117', '2025-08-06 16:06:38', NULL, 'BSIT', 'ComLab1', NULL, '', '2025-09-10 02:18:57'),
(58, 11, '0001-0001', '2025-08-06 19:03:48', '2025-08-06 19:09:01', 'BSIT', 'ComLab1', 'saved', '', '2025-09-10 02:18:57'),
(59, 11, '0001-0001', '2025-08-06 19:09:04', '2025-08-06 19:16:16', 'BSIT', 'ComLab1', 'saved', '', '2025-09-10 02:18:57'),
(60, 11, '0001-0001', '2025-08-06 19:16:19', '2025-08-06 19:33:10', 'BSIT', 'ComLab1', 'saved', '', '2025-09-10 02:18:57'),
(61, 11, '0001-0001', '2025-08-06 19:33:14', '2025-08-06 19:41:23', 'BSIT', 'ComLab1', 'saved', '', '2025-09-10 02:18:57'),
(62, 11, '0001-0001', '2025-08-06 19:41:26', '2025-08-06 19:44:44', 'BSIT', 'ComLab1', 'saved', '', '2025-09-10 02:18:57'),
(66, 16, '2024-0117', '2025-08-09 11:45:53', '2025-08-09 11:52:52', 'BSIT', 'ComLab1', 'saved', '', '2025-09-10 02:18:57'),
(68, 16, '2024-0117', '2025-08-12 16:10:31', '2025-08-12 16:58:58', 'BSIT', 'ComLab2', 'saved', '', '2025-09-10 02:18:57'),
(69, 16, '2024-0117', '2025-08-12 16:59:01', '2025-08-12 17:17:58', 'BSIT', 'ComLab1', 'saved', '', '2025-09-10 02:18:57'),
(71, 16, '2024-0117', '2025-08-15 14:51:30', '2025-08-15 15:38:42', 'BSIT', 'ComLab1', 'saved', '', '2025-09-10 02:18:57'),
(72, 16, '2024-0117', '2025-08-15 15:38:45', '2025-08-15 15:46:26', 'BSIT', 'ComLab1', 'saved', '', '2025-09-10 02:18:57'),
(73, 16, '2024-0117', '2025-08-15 15:47:19', '2025-08-15 15:49:08', 'BSIT', 'ComLab1', 'saved', '', '2025-09-10 02:18:57'),
(74, 16, '2024-0117', '2025-08-15 15:49:11', '2025-08-15 15:58:12', 'BSIT', 'ComLab1', 'saved', '', '2025-09-10 02:18:57'),
(75, 16, '2024-0117', '2025-08-15 16:13:30', '2025-08-15 16:30:21', 'BSIT', 'ComLab1', 'saved', '', '2025-09-10 02:18:57'),
(76, 16, '2024-0117', '2025-08-15 16:30:23', '2025-08-15 16:32:00', 'BSIT', 'ComLab1', 'saved', '', '2025-09-10 02:18:57'),
(78, 16, '2024-0117', '2025-08-16 13:39:45', '2025-08-16 13:48:27', 'BSIT', 'ComLab2', 'saved', '', '2025-09-10 02:18:57'),
(79, 16, '2024-0117', '2025-08-16 13:48:30', '2025-08-16 13:52:05', 'BSIT', 'ComLab2', 'saved', '', '2025-09-10 02:18:57'),
(82, 16, '2024-0117', '2025-08-17 10:59:27', '2025-08-17 10:59:32', 'BSIT', 'ComLab2', 'saved', '', '2025-09-10 02:18:57'),
(90, 16, '2024-0117', '2025-09-10 09:34:58', '2025-09-10 10:07:56', 'BSIT', 'ComLab2', 'saved', '', '2025-09-10 02:19:39'),
(93, 16, '2024-0117', '2025-09-12 07:03:20', '2025-09-12 07:03:40', 'BSIT', 'ComLab2', 'saved', '', '2025-09-11 23:03:40'),
(94, 16, '2024-0117', '2025-09-12 07:03:40', '2025-09-12 12:29:22', 'BSIT', 'ComLab2', 'saved', '', '2025-09-12 04:29:23'),
(95, 16, '2024-0117', '2025-09-12 12:29:23', '2025-09-12 12:30:07', 'BSIT', 'ComLab2', 'saved', '', '2025-09-12 04:30:07'),
(96, 16, '2024-0117', '2025-09-12 12:30:07', '2025-09-12 12:30:16', 'BSIT', 'ComLab2', 'saved', '', '2025-09-12 04:30:16'),
(97, 16, '2024-0117', '2025-09-12 12:30:16', '2025-09-12 12:37:49', 'BSIT', 'ComLab2', 'saved', '', '2025-09-12 04:37:49'),
(98, 16, '2024-0117', '2025-09-12 12:37:49', '2025-09-12 12:37:54', 'BSIT', 'ComLab2', 'saved', '', '2025-09-12 04:37:54'),
(99, 16, '2024-0117', '2025-09-12 12:37:54', '2025-09-12 12:38:26', 'BSIT', 'ComLab2', 'saved', '', '2025-09-12 04:38:26'),
(100, 16, '2024-0117', '2025-09-12 12:38:26', '2025-09-12 12:38:58', 'BSIT', 'ComLab2', 'saved', '', '2025-09-12 04:38:58'),
(101, 16, '2024-0117', '2025-09-12 12:38:58', '2025-09-12 12:39:18', 'BSIT', 'ComLab2', 'saved', '', '2025-09-12 04:39:18'),
(102, 16, '2024-0117', '2025-09-12 12:39:18', '2025-09-12 15:29:55', 'BSIT', 'ComLab2', 'saved', '', '2025-09-12 07:29:55'),
(104, 16, '2024-0117', '2025-09-16 18:56:11', '2025-09-16 18:56:24', 'BSIT', 'ComLab3', 'saved', '', '2025-09-16 10:56:24'),
(108, 16, '2024-0117', '2025-09-22 09:39:40', '2025-09-22 09:39:49', 'BSIT', 'ComLab2', 'saved', '', '2025-09-22 01:39:49');

-- --------------------------------------------------------

--
-- Table structure for table `attendance_logs`
--
-- Creation: Oct 29, 2025 at 02:04 PM
--

CREATE TABLE `attendance_logs` (
  `id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `id_number` varchar(50) NOT NULL,
  `time_in` datetime NOT NULL,
  `time_out` datetime DEFAULT NULL,
  `department` varchar(255) NOT NULL,
  `location` varchar(255) NOT NULL,
  `instructor_id` varchar(9) NOT NULL,
  `instructor_id_out` int(58) NOT NULL,
  `status` varchar(20) DEFAULT NULL,
  `session_id` int(58) NOT NULL,
  `location_out` timestamp NOT NULL,
  `room` varchar(58) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `department`
--
-- Creation: Oct 11, 2025 at 02:30 PM
--

CREATE TABLE `department` (
  `department_id` int(11) NOT NULL,
  `department_name` varchar(50) NOT NULL,
  `department_desc` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `department`
--

INSERT INTO `department` (`department_id`, `department_name`, `department_desc`) VALUES
(33, 'BSIT', 'Bachelor in Science and Information Technology'),
(165, 'Main', 'Main Entrance');

-- --------------------------------------------------------

--
-- Table structure for table `gate_alerts`
--
-- Creation: Oct 11, 2025 at 02:30 PM
--

CREATE TABLE `gate_alerts` (
  `id` int(11) NOT NULL,
  `alert_type` enum('security','system','maintenance','access_denied') NOT NULL,
  `alert_level` enum('low','medium','high','critical') DEFAULT 'medium',
  `title` varchar(255) NOT NULL,
  `message` text DEFAULT NULL,
  `person_id` int(11) DEFAULT NULL,
  `person_type` enum('student','instructor','personell','visitor') DEFAULT NULL,
  `id_number` varchar(50) DEFAULT NULL,
  `resolved` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `resolved_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `gate_config`
--
-- Creation: Oct 11, 2025 at 02:30 PM
--

CREATE TABLE `gate_config` (
  `id` int(11) NOT NULL,
  `config_key` varchar(100) NOT NULL,
  `config_value` text DEFAULT NULL,
  `description` varchar(255) DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `gate_config`
--

INSERT INTO `gate_config` (`id`, `config_key`, `config_value`, `description`, `updated_at`) VALUES
(1, 'system_name', 'Gate Access Control System', 'Name of the gate system', '2025-09-27 09:35:17'),
(2, 'auto_timeout_minutes', '30', 'Auto logout after minutes of inactivity', '2025-09-27 09:35:17'),
(3, 'max_manual_entries_per_hour', '10', 'Maximum manual entries allowed per hour', '2025-09-27 09:35:17'),
(4, 'require_photo_verification', 'true', 'Whether to require photo verification', '2025-09-27 09:35:17'),
(5, 'enable_voice_announcements', 'true', 'Enable voice announcements for access', '2025-09-27 09:35:17'),
(6, 'backup_interval_hours', '24', 'Hours between automatic backups', '2025-09-27 09:35:17'),
(7, 'alert_retention_days', '30', 'Days to keep alert records', '2025-09-27 09:35:17'),
(8, 'statistics_retention_days', '365', 'Days to keep statistics records', '2025-09-27 09:35:17');

-- --------------------------------------------------------

--
-- Table structure for table `gate_devices`
--
-- Creation: Oct 11, 2025 at 02:30 PM
--

CREATE TABLE `gate_devices` (
  `id` int(11) NOT NULL,
  `device_name` varchar(100) NOT NULL,
  `device_location` varchar(100) NOT NULL,
  `device_type` enum('main_gate','side_gate','back_gate','emergency_exit') DEFAULT 'main_gate',
  `ip_address` varchar(45) DEFAULT NULL,
  `mac_address` varchar(17) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `last_seen` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `gate_devices`
--

INSERT INTO `gate_devices` (`id`, `device_name`, `device_location`, `device_type`, `ip_address`, `mac_address`, `is_active`, `last_seen`, `created_at`) VALUES
(1, 'Main Entrance Scanner', 'Main Gate', 'main_gate', NULL, NULL, 1, NULL, '2025-09-27 09:35:17'),
(2, 'Faculty Entrance', 'Side Gate', 'side_gate', NULL, NULL, 1, NULL, '2025-09-27 09:35:17'),
(3, 'Emergency Exit', 'Back Gate', 'emergency_exit', NULL, NULL, 0, NULL, '2025-09-27 09:35:17');

-- --------------------------------------------------------

--
-- Table structure for table `gate_logs`
--
-- Creation: Oct 22, 2025 at 01:21 PM
--

CREATE TABLE `gate_logs` (
  `id` int(11) NOT NULL,
  `person_type` enum('student','instructor','personell','visitor') NOT NULL,
  `person_id` int(11) NOT NULL,
  `id_number` varchar(50) NOT NULL,
  `name` varchar(255) NOT NULL,
  `year_level` varchar(25) NOT NULL,
  `action` enum('IN','OUT') NOT NULL,
  `time_in` time NOT NULL,
  `time_out` time NOT NULL,
  `date` date NOT NULL,
  `location` varchar(100) NOT NULL,
  `date_logged` date DEFAULT NULL,
  `time_in_am` time DEFAULT NULL,
  `time_out_am` time DEFAULT NULL,
  `time_in_pm` time DEFAULT NULL,
  `time_out_pm` time DEFAULT NULL,
  `department` varchar(100) NOT NULL,
  `photo` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `direction` varchar(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `gate_logs`
--

INSERT INTO `gate_logs` (`id`, `person_type`, `person_id`, `id_number`, `name`, `year_level`, `action`, `time_in`, `time_out`, `date`, `location`, `date_logged`, `time_in_am`, `time_out_am`, `time_in_pm`, `time_out_pm`, `department`, `photo`, `created_at`, `direction`) VALUES
(73, 'student', 78, '0000-0001', 'Truy', '', 'OUT', '00:00:00', '15:42:39', '2025-10-25', 'Gate', NULL, NULL, NULL, NULL, NULL, 'Main', NULL, '2025-10-25 07:42:39', 'OUT'),
(74, 'student', 82, '0001-0002', 'Tryyy', '1st Year', 'OUT', '15:44:17', '15:45:43', '2025-10-25', 'Gate', NULL, NULL, NULL, NULL, NULL, 'Main', NULL, '2025-10-25 07:44:17', 'OUT'),
(75, 'visitor', 0, '1234-1234', 'Aian Desucatan', 'N/A', 'IN', '15:45:18', '00:00:00', '2025-10-25', 'Gate', NULL, NULL, NULL, NULL, NULL, 'Main', NULL, '2025-10-25 07:45:18', 'IN'),
(76, 'instructor', 17, '0001-0005', 'Mr.Richard Bracero', 'N/A', 'IN', '16:13:59', '00:00:00', '2025-10-25', 'Gate', NULL, NULL, NULL, NULL, NULL, 'Main', NULL, '2025-10-25 08:13:59', 'IN'),
(77, 'student', 77, '2024-1697', 'Rose Ann V. Forrosuelo', '1st Year', 'IN', '18:22:44', '00:00:00', '2025-10-25', 'Gate', NULL, NULL, NULL, NULL, NULL, 'Main', NULL, '2025-10-25 10:22:44', 'IN'),
(78, 'student', 81, '2024-0380', 'Nino Mike S. Zaspa', '4th Year', 'IN', '18:29:54', '00:00:00', '2025-10-25', 'Gate', NULL, NULL, NULL, NULL, NULL, 'Main', NULL, '2025-10-25 10:29:54', 'IN'),
(79, 'student', 83, '1111-1111', 'ryyyyyy', '4th Year', 'OUT', '19:41:34', '19:51:40', '2025-10-25', 'Gate', NULL, NULL, NULL, NULL, NULL, 'Main', NULL, '2025-10-25 11:41:34', 'OUT'),
(80, 'student', 77, '2024-1697', 'Rose Ann V. Forrosuelo', '1st Year', 'OUT', '05:21:21', '09:43:58', '2025-10-26', 'Gate', NULL, NULL, NULL, NULL, NULL, 'Main', NULL, '2025-10-25 21:21:21', 'OUT'),
(81, 'student', 81, '2024-0380', 'Nino Mike S. Zaspa', '4th Year', 'IN', '05:46:20', '00:00:00', '2025-10-26', 'Gate', NULL, NULL, NULL, NULL, NULL, 'Main', NULL, '2025-10-25 21:46:20', 'IN'),
(82, 'visitor', 0, '1010-1010', 'Visitorr11', 'N/A', 'IN', '06:09:41', '00:00:00', '2025-10-26', 'Gate', NULL, NULL, NULL, NULL, NULL, 'Main', NULL, '2025-10-25 22:09:41', 'IN'),
(83, 'student', 75, '2024-1570', 'John Cyrus Pescante', '1st Year', 'OUT', '12:57:04', '15:25:15', '2025-10-26', 'Gate', NULL, NULL, NULL, NULL, NULL, 'Main', NULL, '2025-10-26 04:57:04', 'OUT'),
(84, 'instructor', 16, '2024-0117', 'Ms.Jessica Alcazar', 'N/A', 'IN', '12:57:44', '00:00:00', '2025-10-26', 'Gate', NULL, NULL, NULL, NULL, NULL, 'Main', NULL, '2025-10-26 04:57:44', 'IN'),
(85, 'student', 85, '3333-3333', 'Third Year', '3rd Year', 'OUT', '13:47:56', '13:58:06', '2025-10-26', 'Gate', NULL, NULL, NULL, NULL, NULL, 'Main', NULL, '2025-10-26 05:47:56', 'OUT'),
(86, 'student', 83, '1111-1111', 'ryyyyyy', '4th Year', 'IN', '14:40:34', '00:00:00', '2025-10-26', 'Gate', NULL, NULL, NULL, NULL, NULL, 'Main', NULL, '2025-10-26 06:40:34', 'IN'),
(87, 'instructor', 17, '0001-0005', 'Mr.Richard Bracero', 'N/A', 'OUT', '14:46:45', '16:58:19', '2025-10-26', 'Gate', NULL, NULL, NULL, NULL, NULL, 'Main', NULL, '2025-10-26 06:46:45', 'OUT'),
(88, 'personell', 6, '7777-7777', 'WeeeReer', 'N/A', 'OUT', '15:07:32', '16:59:17', '2025-10-26', 'Gate', NULL, NULL, NULL, NULL, NULL, 'Main', NULL, '2025-10-26 07:07:32', 'OUT'),
(89, 'personell', 5, '9999-9999', 'QueSik', 'N/A', 'IN', '15:26:25', '00:00:00', '2025-10-26', 'Gate', NULL, NULL, NULL, NULL, NULL, 'Main', NULL, '2025-10-26 07:26:25', 'IN'),
(90, 'personell', 4, '6666-6666', 'LomBoy', 'N/A', 'OUT', '15:33:35', '16:59:06', '2025-10-26', 'Gate', NULL, NULL, NULL, NULL, NULL, 'Main', NULL, '2025-10-26 07:33:35', 'OUT'),
(91, 'student', 79, '1212-1111', 'Try', '1st Year', 'IN', '15:35:05', '00:00:00', '2025-10-26', 'Gate', NULL, NULL, NULL, NULL, NULL, 'Main', NULL, '2025-10-26 07:35:05', 'IN'),
(92, 'instructor', 12, '0001-0004', 'Mr.Alvin Billiones', 'N/A', 'IN', '15:51:26', '00:00:00', '2025-10-26', 'Gate', NULL, NULL, NULL, NULL, NULL, 'Main', NULL, '2025-10-26 07:51:26', 'IN'),
(93, 'visitor', 0, '1010-1010', 'Aian Desucatan', 'N/A', 'IN', '16:22:25', '00:00:00', '2025-10-26', 'Gate', NULL, NULL, NULL, NULL, NULL, 'Main', NULL, '2025-10-26 08:22:25', 'IN'),
(94, 'student', 78, '0000-0001', 'Taee Ka', '3rd Year', 'IN', '16:42:48', '00:00:00', '2025-10-26', 'Gate', NULL, NULL, NULL, NULL, NULL, 'Main', NULL, '2025-10-26 08:42:48', 'IN'),
(97, 'instructor', 16, '2024-0117', 'Ms.Jessica Alcazar', 'N/A', 'IN', '19:05:46', '00:00:00', '2025-10-27', 'Gate', NULL, NULL, NULL, NULL, NULL, 'Main', NULL, '2025-10-27 11:05:46', 'IN'),
(98, 'student', 81, '2024-0380', 'Nino Mike S. Zaspa', '4th Year', 'IN', '21:10:42', '00:00:00', '2025-10-28', 'Gate', NULL, NULL, NULL, NULL, NULL, 'Main', NULL, '2025-10-28 13:10:42', 'IN'),
(99, 'student', 80, '0004-0001', 'Angelo Derder', '4th Year', 'IN', '21:11:12', '00:00:00', '2025-10-28', 'Gate', NULL, NULL, NULL, NULL, NULL, 'Main', NULL, '2025-10-28 13:11:12', 'IN'),
(100, 'personell', 5, '9999-9999', 'QueSik', 'N/A', 'OUT', '21:12:40', '21:13:35', '2025-10-28', 'Gate', NULL, NULL, NULL, NULL, NULL, 'Main', NULL, '2025-10-28 13:12:40', 'OUT'),
(101, 'personell', 4, '6666-6666', 'LomBoy', 'N/A', 'OUT', '19:06:01', '20:20:08', '2025-10-29', 'Gate', NULL, NULL, NULL, NULL, NULL, 'Main', NULL, '2025-10-29 11:06:01', 'OUT'),
(102, 'visitor', 0, '1010-1010', 'Aian Desucatan', 'N/A', 'IN', '19:06:44', '00:00:00', '2025-10-29', 'Gate', NULL, NULL, NULL, NULL, NULL, 'Main', NULL, '2025-10-29 11:06:44', 'IN'),
(103, 'visitor', 0, '1234-1234', 'Nino Mike Zaspa', 'N/A', 'OUT', '19:56:17', '20:17:35', '2025-10-29', 'Gate', NULL, NULL, NULL, NULL, NULL, 'Main', NULL, '2025-10-29 11:56:17', 'OUT'),
(104, 'visitor', 0, '1010-1010', 'joshua pastorpide', 'N/A', 'OUT', '20:23:37', '20:23:52', '2025-10-29', 'Gate', NULL, NULL, NULL, NULL, NULL, 'Main', NULL, '2025-10-29 12:23:37', 'OUT'),
(105, 'visitor', 0, '1234-1234', 'Aswang Kooo', 'N/A', 'OUT', '20:53:58', '20:56:12', '2025-10-29', 'Gate', NULL, NULL, NULL, NULL, NULL, 'Main', NULL, '2025-10-29 12:53:58', 'OUT'),
(106, 'student', 75, '2024-1570', 'John Cyrus Pescante', '3rd Year', 'IN', '16:20:28', '00:00:00', '2025-11-02', 'Gate', NULL, NULL, NULL, NULL, NULL, 'Main', NULL, '2025-11-02 08:20:28', 'IN'),
(107, 'instructor', 16, '2024-0117', 'Ms.Jessica Alcazar', 'N/A', 'IN', '16:20:39', '00:00:00', '2025-11-02', 'Gate', NULL, NULL, NULL, NULL, NULL, 'Main', NULL, '2025-11-02 08:20:39', 'IN'),
(108, 'student', 85, '3333-3333', 'Third Year', '3rd Year', 'IN', '16:21:10', '00:00:00', '2025-11-02', 'Gate', NULL, NULL, NULL, NULL, NULL, 'Main', NULL, '2025-11-02 08:21:10', 'IN'),
(109, 'visitor', 0, '1010-1010', 'Aswang Kooo', 'N/A', 'IN', '16:21:48', '00:00:00', '2025-11-02', 'Gate', NULL, NULL, NULL, NULL, NULL, 'Main', NULL, '2025-11-02 08:21:48', 'IN'),
(110, 'student', 83, '1111-1111', 'ryyyyyy', '4th Year', 'IN', '16:22:14', '00:00:00', '2025-11-02', 'Gate', NULL, NULL, NULL, NULL, NULL, 'Main', NULL, '2025-11-02 08:22:14', 'IN'),
(111, 'student', 79, '1212-1111', 'Try', '1st Year', 'IN', '16:22:31', '00:00:00', '2025-11-02', 'Gate', NULL, NULL, NULL, NULL, NULL, 'Main', NULL, '2025-11-02 08:22:31', 'IN'),
(112, 'instructor', 16, '2024-0117', 'Ms.Jessica Alcazar', 'N/A', 'OUT', '06:44:14', '06:55:29', '2025-11-03', 'Gate', NULL, NULL, NULL, NULL, NULL, 'Main', NULL, '2025-11-02 22:44:14', 'OUT'),
(113, 'student', 77, '2024-1697', 'Rose Ann V. Forrosuelo', '1st Year', 'IN', '06:45:23', '00:00:00', '2025-11-03', 'Gate', NULL, NULL, NULL, NULL, NULL, 'Main', NULL, '2025-11-02 22:45:23', 'IN'),
(114, 'student', 79, '1212-1111', 'Try', '1st Year', 'IN', '06:46:10', '00:00:00', '2025-11-03', 'Gate', NULL, NULL, NULL, NULL, NULL, 'Main', NULL, '2025-11-02 22:46:10', 'IN'),
(115, 'student', 81, '2024-0380', 'Nino Mike S. Zaspa', '4th Year', 'OUT', '06:51:18', '07:41:01', '2025-11-03', 'Gate', NULL, NULL, NULL, NULL, NULL, 'Main', NULL, '2025-11-02 22:51:18', 'OUT'),
(116, 'instructor', 17, '0001-0005', 'Mr.Richard Bracero', 'N/A', 'IN', '06:55:46', '00:00:00', '2025-11-03', 'Gate', NULL, NULL, NULL, NULL, NULL, 'Main', NULL, '2025-11-02 22:55:46', 'IN'),
(117, 'instructor', 13, '0001-0003', 'Mr.Danilo Villarino', 'N/A', 'IN', '06:55:54', '00:00:00', '2025-11-03', 'Gate', NULL, NULL, NULL, NULL, NULL, 'Main', NULL, '2025-11-02 22:55:54', 'IN'),
(118, 'instructor', 12, '0001-0004', 'Mr.Alvin Billiones', 'N/A', 'IN', '06:56:12', '00:00:00', '2025-11-03', 'Gate', NULL, NULL, NULL, NULL, NULL, 'Main', NULL, '2025-11-02 22:56:12', 'IN'),
(119, 'instructor', 11, '0001-0001', 'Mr.Kurt Alegre', 'N/A', 'IN', '06:56:21', '00:00:00', '2025-11-03', 'Gate', NULL, NULL, NULL, NULL, NULL, 'Main', NULL, '2025-11-02 22:56:21', 'IN'),
(120, 'student', 82, '0001-0002', 'Tryyy', '2nd Year', 'IN', '06:56:33', '00:00:00', '2025-11-03', 'Gate', NULL, NULL, NULL, NULL, NULL, 'Main', NULL, '2025-11-02 22:56:33', 'IN'),
(121, 'personell', 4, '6666-6666', 'LomBoy', 'N/A', 'IN', '06:57:32', '00:00:00', '2025-11-03', 'Gate', NULL, NULL, NULL, NULL, NULL, 'Main', NULL, '2025-11-02 22:57:32', 'IN'),
(122, 'personell', 7, '1212-9999', 'JohnyBravo', 'N/A', 'IN', '06:58:19', '00:00:00', '2025-11-03', 'Gate', NULL, NULL, NULL, NULL, NULL, 'Main', NULL, '2025-11-02 22:58:19', 'IN'),
(123, 'visitor', 0, '1010-1010', 'Aian Veneracion', 'N/A', 'IN', '07:08:54', '00:00:00', '2025-11-03', 'Gate', NULL, NULL, NULL, NULL, NULL, 'Main', NULL, '2025-11-02 23:08:54', 'IN');

-- --------------------------------------------------------

--
-- Table structure for table `gate_statistics`
--
-- Creation: Oct 11, 2025 at 02:30 PM
--

CREATE TABLE `gate_statistics` (
  `id` int(11) NOT NULL,
  `stat_date` date NOT NULL,
  `total_entries` int(11) DEFAULT 0,
  `entries_in` int(11) DEFAULT 0,
  `entries_out` int(11) DEFAULT 0,
  `unique_people` int(11) DEFAULT 0,
  `students_count` int(11) DEFAULT 0,
  `instructors_count` int(11) DEFAULT 0,
  `personell_count` int(11) DEFAULT 0,
  `visitors_count` int(11) DEFAULT 0,
  `peak_hour` time DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `gate_stats`
--
-- Creation: Oct 19, 2025 at 07:32 AM
--

CREATE TABLE `gate_stats` (
  `id` int(11) NOT NULL,
  `stat_date` date NOT NULL,
  `department` varchar(255) NOT NULL,
  `location` varchar(255) NOT NULL,
  `students_in` int(11) DEFAULT 0,
  `students_out` int(11) DEFAULT 0,
  `instructors_in` int(11) DEFAULT 0,
  `instructors_out` int(11) DEFAULT 0,
  `personnel_in` int(11) DEFAULT 0,
  `personnel_out` int(11) DEFAULT 0,
  `visitors_in` int(11) DEFAULT 0,
  `visitors_out` int(11) DEFAULT 0,
  `total_in` int(11) DEFAULT 0,
  `total_out` int(11) DEFAULT 0,
  `hourly_counts` text DEFAULT '{}',
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `gate_stats`
--

INSERT INTO `gate_stats` (`id`, `stat_date`, `department`, `location`, `students_in`, `students_out`, `instructors_in`, `instructors_out`, `personnel_in`, `personnel_out`, `visitors_in`, `visitors_out`, `total_in`, `total_out`, `hourly_counts`, `created_at`, `updated_at`) VALUES
(1, '2025-10-19', 'Main', 'Gate', 4, 6, 4, 5, 0, 0, 2, 0, 10, 11, '{\"15\":5,\"16\":12,\"17\":2,\"18\":2}', '2025-10-19 00:32:28', '2025-10-19 03:55:45'),
(2, '2025-10-20', 'Main', 'Gate', 0, 0, 0, 0, 0, 0, 1, 0, 1, 0, '{\"16\":1}', '2025-10-20 01:42:31', '2025-10-20 01:42:31'),
(3, '2025-10-22', 'Main', 'Gate', 8, 6, 2, 1, 0, 0, 2, 0, 12, 7, '{\"14\":5,\"15\":4,\"16\":4,\"17\":1,\"20\":4,\"21\":1}', '2025-10-21 23:32:37', '2025-10-22 06:27:05'),
(4, '2025-10-23', 'Main', 'Gate', 4, 1, 1, 1, 0, 0, 0, 0, 5, 2, '{\"09\":1,\"10\":3,\"11\":1,\"17\":1,\"18\":1}', '2025-10-22 18:56:46', '2025-10-23 03:01:09'),
(5, '2025-10-24', 'Main', 'Gate', 0, 0, 1, 1, 0, 0, 0, 0, 1, 1, '{\"05\":1,\"15\":1}', '2025-10-23 14:04:32', '2025-10-24 00:33:33'),
(6, '2025-10-25', 'Main', 'Gate', 10, 8, 4, 1, 0, 0, 3, 0, 17, 9, '{\"14\":13,\"15\":8,\"16\":1,\"18\":2,\"19\":2}', '2025-10-24 23:09:46', '2025-10-25 04:51:40');

-- --------------------------------------------------------

--
-- Table structure for table `holidays`
--
-- Creation: Oct 27, 2025 at 11:55 AM
-- Last update: Oct 27, 2025 at 11:59 AM
--

CREATE TABLE `holidays` (
  `id` int(11) NOT NULL,
  `date` date NOT NULL,
  `type` enum('holiday','suspension') NOT NULL,
  `description` text NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `holidays`
--

INSERT INTO `holidays` (`id`, `date`, `type`, `description`, `created_at`) VALUES
(1, '2025-10-22', 'holiday', 'qweqw', '2025-10-27 11:56:02'),
(2, '2025-10-16', 'holiday', 'Rizal\'s Days', '2025-10-27 11:59:27');

-- --------------------------------------------------------

--
-- Table structure for table `instructor`
--
-- Creation: Oct 26, 2025 at 08:20 AM
--

CREATE TABLE `instructor` (
  `id` int(11) NOT NULL,
  `photo` varchar(25) NOT NULL,
  `fullname` varchar(50) NOT NULL,
  `id_number` varchar(9) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `department_id` int(11) NOT NULL,
  `status` varchar(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `instructor`
--

INSERT INTO `instructor` (`id`, `photo`, `fullname`, `id_number`, `created_at`, `updated_at`, `department_id`, `status`) VALUES
(11, '68ea96b94e082.png', 'Mr.Kurt Alegre', '0001-0001', '2025-06-28 11:52:22', '2025-07-14 22:16:12', 33, ''),
(12, '68eb1d69d8db9.jpg', 'Mr.Alvin Billiones', '0001-0004', '2025-07-08 08:26:38', '2025-07-14 22:19:33', 33, ''),
(13, '68eb1de0093af.png', 'Mr.Danilo Villarino', '0001-0003', '2025-07-09 00:38:05', '2025-07-14 22:18:53', 33, ''),
(16, '68eb20eb563a0.jpg', 'Ms.Jessica Alcazar', '2024-0117', '2025-07-14 22:18:35', '2025-07-14 22:47:34', 33, ''),
(17, '68eb1d2d6e9ad.png', 'Mr.Richard Bracero', '0001-0005', '2025-07-14 22:20:02', '2025-07-14 22:20:02', 33, ''),
(18, '68ff0e82bdf77.jpg', 'Mrs.Emily Forrosuelo', '0001-0009', '2025-07-14 22:20:39', '2025-07-14 22:20:39', 33, ''),
(23, '68ff0fbcafea1.jpg', 'Tryryrt asdad', '0001-0008', '2025-10-27 06:22:52', '2025-10-27 06:22:52', 33, '');

-- --------------------------------------------------------

--
-- Table structure for table `instructor_accounts`
--
-- Creation: Oct 11, 2025 at 02:30 PM
--

CREATE TABLE `instructor_accounts` (
  `id` int(11) NOT NULL,
  `instructor_id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `last_login` varchar(11) NOT NULL,
  `department` varchar(55) NOT NULL,
  `fullname` varchar(55) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `instructor_accounts`
--

INSERT INTO `instructor_accounts` (`id`, `instructor_id`, `username`, `password`, `created_at`, `updated_at`, `last_login`, `department`, `fullname`) VALUES
(1, 13, 'Danilo', '$2y$10$XiNsqThyOgkgtqUwnnUtCOldujLs9A/I1zMtCWJrl6O3SULuToxlq', '2025-09-08 08:26:12', '2025-10-29 08:03:08', '2025-10-23 ', 'BSIT', 'Mr. Danilo Villariono'),
(2, 16, 'jessica', '$2y$10$8H3NpcZ9ObhGduz/37BssOc1ytyclaVK2BagMiBYVBZ6yvbA4ESPy', '2025-09-11 23:12:18', '2025-10-27 01:07:46', '2025-10-26 ', 'BSIT', 'Ms.Jessica Alcazar'),
(3, 12, 'alvin', '$2y$10$bxLgIrb/Y216/EbgHWGyFuT9OBEWMwpXQ5ZrWmMrRH71fDaOsmWjq', '2025-09-11 23:36:14', '2025-10-27 03:30:21', '2025-10-26 ', '', ''),
(5, 11, 'kurt', '$2y$10$PTvB296WA3RxtvnGWKKYq.XbTrye9WYH7RSSGJE5IoI2QSOwRh5zq', '2025-10-11 16:08:33', '2025-11-03 12:32:04', '2025-11-03 ', '', ''),
(6, 17, 'richard', '$2y$10$9njuiuDf3vFUIdpo1xJoq.tcq4Ns8gdeLkcly/MM6.3KZMP7ZBecq', '2025-10-29 08:09:52', '2025-10-29 08:09:52', '', '', ''),
(7, 18, 'emily', '$2y$10$Q4eL0MEX9hCZg/vFDiGvZuQSfoMCo.ouvxZOK3.cFEgt88tbvhUAS', '2025-10-29 08:17:34', '2025-10-29 08:17:34', '', '', '');

-- --------------------------------------------------------

--
-- Table structure for table `instructor_attendance`
--
-- Creation: Oct 11, 2025 at 02:30 PM
--

CREATE TABLE `instructor_attendance` (
  `id` int(11) NOT NULL,
  `date` date NOT NULL,
  `instructor_name` varchar(50) NOT NULL,
  `instructor_id` int(11) NOT NULL,
  `time_in` datetime NOT NULL,
  `time_out` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `instructor_attendance`
--

INSERT INTO `instructor_attendance` (`id`, `date`, `instructor_name`, `instructor_id`, `time_in`, `time_out`) VALUES
(1, '2025-07-26', 'Ms.Jessica Alcazar', 2024, '2025-07-26 17:25:34', '2025-07-26 17:25:34');

-- --------------------------------------------------------

--
-- Table structure for table `instructor_attendance_summary`
--
-- Creation: Oct 27, 2025 at 04:06 AM
--

CREATE TABLE `instructor_attendance_summary` (
  `id` int(11) NOT NULL,
  `instructor_id` int(11) NOT NULL,
  `instructor_name` varchar(255) NOT NULL,
  `subject_name` varchar(255) NOT NULL,
  `year_level` varchar(50) NOT NULL,
  `section` varchar(50) NOT NULL,
  `room` varchar(255) DEFAULT NULL,
  `total_students` int(11) DEFAULT 0,
  `present_count` int(11) DEFAULT 0,
  `absent_count` int(11) DEFAULT 0,
  `attendance_rate` decimal(5,2) DEFAULT 0.00,
  `session_date` date NOT NULL,
  `time_in` datetime NOT NULL,
  `time_out` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `instructor_attendance_summary`
--

INSERT INTO `instructor_attendance_summary` (`id`, `instructor_id`, `instructor_name`, `subject_name`, `year_level`, `section`, `room`, `total_students`, `present_count`, `absent_count`, `attendance_rate`, `session_date`, `time_in`, `time_out`, `created_at`) VALUES
(1, 16, 'Ms.Jessica Alcazar', 'Sub2', '1st Year', 'A', NULL, 0, 0, 0, '0.00', '2025-10-14', '2025-10-26 13:08:28', '2025-10-26 00:00:00', '2025-10-14 05:08:28'),
(2, 16, 'Ms.Jessica Alcazar', 'Sub2', '1st Year', 'A', NULL, 1, 1, 0, '100.00', '2025-10-14', '2025-10-26 13:18:27', '2025-10-26 00:00:00', '2025-10-14 05:18:27'),
(3, 16, 'Ms.Jessica Alcazar', 'Sub2', '4th Year', 'West', NULL, 2, 0, 2, '0.00', '2025-10-13', '2025-10-26 13:52:20', '2025-10-26 22:52:20', '2025-10-14 05:52:20'),
(4, 16, 'Ms.Jessica Alcazar', 'Sub2', '4th Year', 'West', NULL, 2, 0, 2, '0.00', '2025-10-13', '2025-10-26 13:54:44', '2025-10-26 22:54:44', '2025-10-14 05:54:44'),
(5, 16, 'Ms.Jessica Alcazar', 'Sub2', '4th Year', 'West', NULL, 2, 0, 2, '0.00', '2025-10-13', '2025-10-26 13:57:22', '2025-10-26 13:57:22', '2025-10-14 05:57:22'),
(6, 16, 'Ms.Jessica Alcazar', 'Sub2', '4th Year', 'West', NULL, 2, 0, 2, '0.00', '2025-10-13', '2025-10-26 13:58:02', '2025-10-26 13:58:02', '2025-10-14 05:58:02'),
(7, 16, 'Ms.Jessica Alcazar', 'Sub2', '4th Year', 'West', NULL, 2, 0, 2, '0.00', '2025-10-13', '2025-10-26 14:01:29', '2025-10-26 14:01:29', '2025-10-14 06:01:29'),
(8, 16, 'Ms.Jessica Alcazar', 'Sub2', '4th Year', 'West', NULL, 2, 0, 2, '0.00', '2025-10-13', '2025-10-26 14:02:00', '2025-10-26 14:02:00', '2025-10-14 06:02:00'),
(9, 16, 'Ms.Jessica Alcazar', 'Sub2', '4th Year', 'West', NULL, 2, 0, 2, '0.00', '2025-10-13', '2025-10-26 14:02:09', '2025-10-26 14:02:09', '2025-10-14 06:02:09'),
(10, 16, 'Ms.Jessica Alcazar', 'Sub2', '4th Year', 'West', NULL, 2, 0, 2, '0.00', '2025-10-13', '2025-10-26 14:04:09', '2025-10-26 14:04:09', '2025-10-14 06:04:09'),
(11, 16, 'Ms.Jessica Alcazar', 'Sub2', '4th Year', 'West', NULL, 0, 0, 0, '0.00', '2025-10-13', '2025-10-26 14:08:43', '2025-10-26 14:08:43', '2025-10-14 06:08:43'),
(12, 16, 'Ms.Jessica Alcazar', 'Sub2', '4th Year', 'West', NULL, 2, 0, 2, '0.00', '2025-10-13', '2025-10-26 13:18:27', '2025-10-26 14:19:06', '2025-10-14 06:19:06'),
(13, 16, 'Ms.Jessica Alcazar', 'Sub3', '4th Year', 'West', NULL, 2, 2, 0, '100.00', '2025-10-13', '2025-10-26 14:45:15', '2025-10-26 14:49:44', '2025-10-14 06:49:44'),
(14, 16, 'Ms.Jessica Alcazar', 'Sub3', '4th Year', 'West', NULL, 2, 2, 0, '100.00', '2025-10-13', '2025-10-26 14:45:15', '2025-10-26 14:53:45', '2025-10-14 06:53:45'),
(15, 16, 'Ms.Jessica Alcazar', 'Sub3', '4th Year', 'West', NULL, 2, 2, 0, '100.00', '2025-10-13', '2025-10-26 14:45:15', '2025-10-26 14:56:43', '2025-10-14 06:56:43'),
(16, 16, 'Ms.Jessica Alcazar', 'Sub3', '1st Year', 'West', NULL, 4, 1, 3, '25.00', '2025-10-14', '2025-10-26 14:45:15', '2025-10-26 15:11:48', '2025-10-14 07:11:48'),
(17, 16, 'Ms.Jessica Alcazar', 'Sub3', '1st Year', 'West', NULL, 4, 3, 1, '75.00', '2025-10-14', '2025-10-26 14:45:15', '2025-10-26 15:28:37', '2025-10-14 07:28:37'),
(18, 16, 'Ms.Jessica Alcazar', 'Sub3', '4th Year', 'West', NULL, 2, 1, 1, '50.00', '2025-10-14', '2025-10-26 16:03:10', '2025-10-26 16:04:44', '2025-10-14 08:04:44'),
(19, 16, 'Ms.Jessica Alcazar', 'Sub4', '4th Year', 'West', NULL, 2, 1, 1, '50.00', '2025-10-14', '2025-10-26 16:18:43', '2025-10-26 16:19:29', '2025-10-14 08:19:29'),
(20, 16, 'Ms.Jessica Alcazar', 'Information Assurance and Security 2', '1st Year', 'West', NULL, 4, 3, 1, '75.00', '2025-10-14', '2025-10-26 22:19:21', '2025-10-26 22:19:59', '2025-10-14 14:19:59'),
(21, 16, 'Ms.Jessica Alcazar', 'Information Assurance and Security 2', '1st Year', 'West', NULL, 4, 1, 3, '25.00', '2025-10-14', '2025-10-26 22:20:04', '2025-10-26 22:43:15', '2025-10-14 14:43:15'),
(22, 16, 'Ms.Jessica Alcazar', 'Program Logic Formulation', '4th Year', 'West', NULL, 2, 1, 1, '50.00', '2025-10-15', '2025-10-26 03:08:40', '2025-10-26 03:10:02', '2025-10-14 19:10:02'),
(23, 16, 'Ms.Jessica Alcazar', 'Capstone Project 2', '1st Year', 'A', NULL, 0, 0, 0, '0.00', '2025-10-17', '2025-10-26 10:21:40', '2025-10-26 00:00:00', '2025-10-17 02:21:40'),
(24, 16, 'Ms.Jessica Alcazar', 'Capstone Project 2', '1st Year', 'A', NULL, 0, 0, 0, '0.00', '2025-10-17', '2025-10-26 11:07:56', '2025-10-26 00:00:00', '2025-10-17 03:07:56'),
(25, 16, 'Ms.Jessica Alcazar', 'Capstone Project 2', '1st Year', 'West', NULL, 4, 4, 0, '100.00', '2025-10-17', '2025-10-26 11:07:56', '2025-10-26 11:09:22', '2025-10-17 03:09:22'),
(26, 16, 'Ms.Jessica Alcazar', 'Capstone Project 2', '1st Year', 'A', NULL, 0, 0, 0, '0.00', '2025-10-17', '2025-10-26 11:10:11', '2025-10-26 00:00:00', '2025-10-17 03:10:11'),
(27, 16, 'Ms.Jessica Alcazar', 'Capstone Project 2', '1st Year', 'West', NULL, 4, 4, 0, '100.00', '2025-10-17', '2025-10-26 11:10:11', '2025-10-26 11:10:25', '2025-10-17 03:10:25'),
(28, 16, 'Ms.Jessica Alcazar', 'Capstone Project 2', '1st Year', 'West', NULL, 5, 5, 0, '100.00', '2025-10-17', '2025-10-26 11:10:34', '2025-10-26 11:45:25', '2025-10-17 03:45:25'),
(29, 16, 'Ms.Jessica Alcazar', 'Capstone Project 2', '1st Year', 'A', NULL, 0, 0, 0, '0.00', '2025-10-17', '2025-10-26 11:47:46', '2025-10-26 00:00:00', '2025-10-17 03:47:46'),
(30, 16, 'Ms.Jessica Alcazar', 'SUB4-01', '1st Year', 'A', NULL, 0, 0, 0, '0.00', '2025-10-18', '2025-10-26 08:39:17', '2025-10-26 00:00:00', '2025-10-18 00:39:17'),
(31, 16, 'Ms.Jessica Alcazar', 'SUB4-01', '1st Year', 'West', NULL, 5, 1, 4, '20.00', '2025-10-18', '2025-10-26 08:39:17', '2025-10-26 09:43:27', '2025-10-18 01:43:27'),
(32, 16, 'Ms.Jessica Alcazar', 'SUB4-01', '1st Year', 'West', NULL, 5, 2, 3, '40.00', '2025-10-18', '2025-10-26 09:45:56', '2025-10-26 09:51:00', '2025-10-18 01:51:00'),
(33, 16, 'Ms.Jessica Alcazar', 'SUB4-01', '1st Year', 'A', NULL, 0, 0, 0, '0.00', '2025-10-18', '2025-10-26 09:51:24', '2025-10-26 00:00:00', '2025-10-18 01:51:24'),
(34, 16, 'Ms.Jessica Alcazar', 'SUB4-01', '1st Year', 'West', NULL, 5, 5, 0, '100.00', '2025-10-18', '2025-10-26 09:51:24', '2025-10-26 10:03:39', '2025-10-18 02:03:39'),
(35, 16, 'Ms.Jessica Alcazar', 'SUB4-01', '1st Year', 'A', NULL, 0, 0, 0, '0.00', '2025-10-18', '2025-10-26 10:04:05', '2025-10-26 00:00:00', '2025-10-18 02:04:06'),
(36, 16, 'Ms.Jessica Alcazar', 'SUB4-01', '1st Year', 'West', NULL, 5, 5, 0, '100.00', '2025-10-18', '2025-10-26 10:04:05', '2025-10-26 10:06:13', '2025-10-18 02:06:14'),
(37, 16, 'Ms.Jessica Alcazar', 'SUB4-01', '1st Year', 'A', NULL, 0, 0, 0, '0.00', '2025-10-18', '2025-10-26 10:07:45', '2025-10-26 00:00:00', '2025-10-18 02:07:46'),
(38, 16, 'Ms.Jessica Alcazar', 'SUB4-01', '1st Year', 'West', NULL, 5, 5, 0, '100.00', '2025-10-18', '2025-10-26 10:07:45', '2025-10-26 10:16:59', '2025-10-18 02:16:59'),
(39, 12, 'Mr.Alvin Billiones', 'ITE PROF ELECT 4', '1st Year', 'A', NULL, 0, 0, 0, '0.00', '2025-10-18', '2025-10-26 10:17:29', '2025-10-26 00:00:00', '2025-10-18 02:17:29'),
(40, 12, 'Mr.Alvin Billiones', 'ITE PROF ELECT 4', '1st Year', 'West', NULL, 5, 1, 4, '20.00', '2025-10-19', '2025-10-26 10:17:29', '2025-10-26 07:28:23', '2025-10-18 23:28:23'),
(41, 16, 'Ms.Jessica Alcazar', '', '1st Year', 'A', NULL, 0, 0, 0, '0.00', '2025-10-19', '2025-10-26 09:12:48', '2025-10-26 00:00:00', '2025-10-19 01:12:48'),
(42, 16, 'Ms.Jessica Alcazar', '', '1st Year', 'A', NULL, 0, 0, 0, '0.00', '2025-10-19', '2025-10-26 14:37:12', '2025-10-26 00:00:00', '2025-10-19 06:37:12'),
(43, 16, 'Ms.Jessica Alcazar', '', '1st Year', 'A', NULL, 0, 0, 0, '0.00', '2025-10-19', '2025-10-26 14:37:48', '2025-10-26 00:00:00', '2025-10-19 06:37:48'),
(44, 16, 'Ms.Jessica Alcazar', '', '1st Year', 'A', NULL, 0, 0, 0, '0.00', '2025-10-20', '2025-10-26 11:48:42', '2025-10-26 00:00:00', '2025-10-20 03:48:42'),
(45, 16, 'Ms.Jessica Alcazar', '', '1st Year', 'A', NULL, 0, 0, 0, '0.00', '2025-10-20', '2025-10-26 11:48:55', '2025-10-26 00:00:00', '2025-10-20 03:48:55'),
(46, 16, 'Ms.Jessica Alcazar', 'Program Logic Formulation', '1st Year', 'A', NULL, 0, 0, 0, '0.00', '2025-10-20', '2025-10-26 11:49:02', '2025-10-26 00:00:00', '2025-10-20 03:49:02'),
(47, 16, 'Ms.Jessica Alcazar', 'Program Logic Formulation', '1st Year', 'West', NULL, 5, 1, 4, '20.00', '2025-10-20', '2025-10-26 11:49:02', '2025-10-26 12:30:56', '2025-10-20 04:30:56'),
(50, 16, 'Ms.Jessica Alcazar', 'Program Logic Formulation', '1st Year', 'West', NULL, 5, 1, 4, '20.00', '2025-10-20', '2025-10-26 12:31:01', '2025-10-26 12:51:52', '2025-10-20 04:51:52'),
(51, 16, 'Ms.Jessica Alcazar', '', '1st Year', 'A', NULL, 0, 0, 0, '0.00', '2025-10-20', '2025-10-26 13:04:06', '2025-10-26 00:00:00', '2025-10-20 05:04:06'),
(52, 16, 'Ms.Jessica Alcazar', '', '1st Year', 'A', NULL, 0, 0, 0, '0.00', '2025-10-20', '2025-10-26 13:04:39', '2025-10-26 00:00:00', '2025-10-20 05:04:39'),
(53, 11, 'Mr.Kurt Alegre', '', '1st Year', 'A', NULL, 0, 0, 0, '0.00', '2025-10-20', '2025-10-26 13:04:49', '2025-10-26 00:00:00', '2025-10-20 05:04:50'),
(54, 11, 'Mr.Kurt Alegre', 'Program Logic Formulation', '1st Year', 'A', NULL, 0, 0, 0, '0.00', '2025-10-20', '2025-10-26 13:04:55', '2025-10-26 00:00:00', '2025-10-20 05:04:56'),
(55, 11, 'Mr.Kurt Alegre', '', '1st Year', 'A', NULL, 0, 0, 0, '0.00', '2025-10-20', '2025-10-26 14:07:05', '2025-10-26 00:00:00', '2025-10-20 06:07:04'),
(56, 11, 'Mr.Kurt Alegre', 'Program Logic Formulation', '1st Year', 'A', NULL, 0, 0, 0, '0.00', '2025-10-20', '2025-10-26 14:07:17', '2025-10-26 00:00:00', '2025-10-20 06:07:16'),
(57, 11, 'Mr.Kurt Alegre', 'Program Logic Formulation', '4th Year', 'West', NULL, 3, 0, 3, '0.00', '2025-10-20', '2025-10-26 14:07:17', '2025-10-26 14:10:24', '2025-10-20 06:10:23'),
(58, 11, 'Mr.Kurt Alegre', '', '1st Year', 'A', NULL, 0, 0, 0, '0.00', '2025-10-20', '2025-10-26 14:10:44', '2025-10-26 00:00:00', '2025-10-20 06:10:43'),
(59, 11, 'Mr.Kurt Alegre', 'Program Logic Formulation', '1st Year', 'A', NULL, 0, 0, 0, '0.00', '2025-10-20', '2025-10-26 14:10:46', '2025-10-26 00:00:00', '2025-10-20 06:10:45'),
(60, 11, 'Mr.Kurt Alegre', '', '1st Year', 'A', NULL, 0, 0, 0, '0.00', '2025-10-20', '2025-10-26 14:50:26', '2025-10-26 00:00:00', '2025-10-20 06:50:26'),
(61, 11, 'Mr.Kurt Alegre', 'Program Logic Formulation', '1st Year', 'A', NULL, 0, 0, 0, '0.00', '2025-10-20', '2025-10-26 14:50:32', '2025-10-26 00:00:00', '2025-10-20 06:50:32'),
(62, 11, 'Mr.Kurt Alegre', '', '1st Year', 'A', NULL, 0, 0, 0, '0.00', '2025-10-20', '2025-10-26 15:34:06', '2025-10-26 00:00:00', '2025-10-20 07:34:06'),
(63, 11, 'Mr.Kurt Alegre', 'Program Logic Formulation', '1st Year', 'A', NULL, 0, 0, 0, '0.00', '2025-10-20', '2025-10-26 15:34:10', '2025-10-26 00:00:00', '2025-10-20 07:34:10'),
(64, 11, 'Mr.Kurt Alegre', 'Program Logic Formulation', '4th Year', 'West', NULL, 3, 3, 0, '100.00', '2025-10-20', '2025-10-26 15:34:10', '2025-10-26 16:23:50', '2025-10-20 08:23:50'),
(65, 11, 'Mr.Kurt Alegre', '', '1st Year', 'A', NULL, 0, 0, 0, '0.00', '2025-10-20', '2025-10-26 16:25:27', '2025-10-26 00:00:00', '2025-10-20 08:25:27'),
(66, 16, 'Ms.Jessica Alcazar', '', '1st Year', 'A', NULL, 0, 0, 0, '0.00', '2025-10-20', '2025-10-26 16:25:35', '2025-10-26 00:00:00', '2025-10-20 08:25:35'),
(67, 16, 'Ms.Jessica Alcazar', '', '1st Year', 'A', NULL, 0, 0, 0, '0.00', '2025-10-20', '2025-10-26 16:25:50', '2025-10-26 00:00:00', '2025-10-20 08:25:50'),
(68, 11, 'Mr.Kurt Alegre', '', '1st Year', 'A', NULL, 0, 0, 0, '0.00', '2025-10-20', '2025-10-26 16:29:12', '2025-10-26 00:00:00', '2025-10-20 08:29:12'),
(69, 11, 'Mr.Kurt Alegre', 'Program Logic Formulation', '1st Year', 'A', NULL, 0, 0, 0, '0.00', '2025-10-20', '2025-10-26 16:29:22', '2025-10-26 00:00:00', '2025-10-20 08:29:22'),
(70, 11, 'Mr.Kurt Alegre', 'Program Logic Formulation', '1st Year', 'West', NULL, 5, 1, 4, '20.00', '2025-10-20', '2025-10-26 16:29:22', '2025-10-26 16:30:15', '2025-10-20 08:30:15'),
(71, 11, 'Mr.Kurt Alegre', 'Program Logic Formulation', '1st Year', 'West', NULL, 5, 1, 4, '20.00', '2025-10-20', '2025-10-26 16:30:55', '2025-10-26 16:31:48', '2025-10-20 08:31:48'),
(72, 11, 'Mr.Kurt Alegre', 'Program Logic Formulation', '1st Year', 'West', NULL, 5, 1, 4, '20.00', '2025-10-20', '2025-10-26 16:38:39', '2025-10-26 16:39:22', '2025-10-20 08:39:22'),
(73, 16, 'Ms.Jessica Alcazar', '', '1st Year', 'A', NULL, 0, 0, 0, '0.00', '2025-10-22', '2025-10-26 15:41:23', '2025-10-26 00:00:00', '2025-10-22 07:41:23'),
(74, 11, 'Mr.Kurt Alegre', '', '1st Year', 'A', NULL, 0, 0, 0, '0.00', '2025-10-22', '2025-10-26 15:42:04', '2025-10-26 00:00:00', '2025-10-22 07:42:04'),
(75, 12, 'Mr.Alvin Billiones', '', '1st Year', 'A', NULL, 0, 0, 0, '0.00', '2025-10-22', '2025-10-26 15:42:15', '2025-10-26 00:00:00', '2025-10-22 07:42:15'),
(76, 16, 'Ms.Jessica Alcazar', '', '1st Year', 'A', NULL, 0, 0, 0, '0.00', '2025-10-22', '2025-10-26 18:01:34', '2025-10-26 00:00:00', '2025-10-22 10:01:34'),
(77, 16, 'Ms.Jessica Alcazar', 'Program Logic Formulation', '1st Year', 'A', NULL, 0, 0, 0, '0.00', '2025-10-22', '2025-10-26 18:01:37', '2025-10-26 00:00:00', '2025-10-22 10:01:37'),
(78, 16, 'Ms.Jessica Alcazar', 'Program Logic Formulation', '1st Year', 'West', NULL, 5, 3, 2, '60.00', '2025-10-22', '2025-10-26 18:01:37', '2025-10-26 18:05:59', '2025-10-22 10:05:58'),
(79, 13, 'Mr.Danilo Villarino', '', '1st Year', 'A', NULL, 0, 0, 0, '0.00', '2025-10-24', '2025-10-26 04:00:26', '2025-10-26 00:00:00', '2025-10-23 20:00:26'),
(80, 13, 'Mr.Danilo Villarino', '', '1st Year', 'A', NULL, 0, 0, 0, '0.00', '2025-10-24', '2025-10-26 04:02:08', '2025-10-26 00:00:00', '2025-10-23 20:02:08'),
(81, 13, 'Mr.Danilo Villarino', 'Program Logic Formulation', '1st Year', 'A', NULL, 0, 0, 0, '0.00', '2025-10-24', '2025-10-26 04:02:16', '2025-10-26 00:00:00', '2025-10-23 20:02:16'),
(82, 13, 'Mr.Danilo Villarino', '', '1st Year', 'A', NULL, 0, 0, 0, '0.00', '2025-10-24', '2025-10-26 05:06:07', '2025-10-26 00:00:00', '2025-10-23 21:06:07'),
(83, 13, 'Mr.Danilo Villarino', 'Program Logic Formulation', '1st Year', 'A', NULL, 0, 0, 0, '0.00', '2025-10-24', '2025-10-26 05:06:11', '2025-10-26 00:00:00', '2025-10-23 21:06:11'),
(84, 13, 'Mr.Danilo Villarino', '', '1st Year', 'A', NULL, 0, 0, 0, '0.00', '2025-10-24', '2025-10-26 05:10:07', '2025-10-26 00:00:00', '2025-10-23 21:10:07'),
(85, 13, 'Mr.Danilo Villarino', 'Platform Technologies', '1st Year', 'A', NULL, 0, 0, 0, '0.00', '2025-10-24', '2025-10-26 05:10:11', '2025-10-26 00:00:00', '2025-10-23 21:10:11'),
(86, 13, 'Mr.Danilo Villarino', '', '1st Year', 'A', NULL, 0, 0, 0, '0.00', '2025-10-24', '2025-10-26 05:10:41', '2025-10-26 00:00:00', '2025-10-23 21:10:41'),
(87, 13, 'Mr.Danilo Villarino', 'Program Logic Formulation', '1st Year', 'A', NULL, 0, 0, 0, '0.00', '2025-10-24', '2025-10-26 05:10:43', '2025-10-26 00:00:00', '2025-10-23 21:10:43'),
(88, 13, 'Mr.Danilo Villarino', 'Program Logic Formulation', 'N/A', 'N/A', NULL, 0, 0, 0, '0.00', '2025-10-24', '2025-10-26 05:10:43', '2025-10-26 05:11:47', '2025-10-23 21:11:47'),
(89, 12, 'Mr.Alvin Billiones', '', '1st Year', 'A', NULL, 0, 0, 0, '0.00', '2025-10-24', '2025-10-26 05:12:22', '2025-10-26 00:00:00', '2025-10-23 21:12:22'),
(90, 12, 'Mr.Alvin Billiones', 'ITE PROF ELECT 4', '1st Year', 'A', NULL, 0, 0, 0, '0.00', '2025-10-24', '2025-10-26 05:12:28', '2025-10-26 00:00:00', '2025-10-23 21:12:28'),
(91, 12, 'Mr.Alvin Billiones', '', '1st Year', 'A', NULL, 0, 0, 0, '0.00', '2025-10-24', '2025-10-26 05:25:23', '2025-10-26 00:00:00', '2025-10-23 21:25:23'),
(92, 12, 'Mr.Alvin Billiones', 'ITE PROF ELECT 4', '1st Year', 'A', NULL, 0, 0, 0, '0.00', '2025-10-24', '2025-10-26 05:25:26', '2025-10-26 00:00:00', '2025-10-23 21:25:26'),
(93, 12, 'Mr.Alvin Billiones', 'ITE PROF ELECT 4', 'N/A', 'N/A', NULL, 0, 0, 0, '0.00', '2025-10-24', '2025-10-26 05:25:26', '2025-10-26 05:39:41', '2025-10-23 21:39:41'),
(94, 13, 'Mr.Danilo Villarino', '', '1st Year', 'A', NULL, 0, 0, 0, '0.00', '2025-10-24', '2025-10-26 05:52:48', '2025-10-26 00:00:00', '2025-10-23 21:52:48'),
(95, 13, 'Mr.Danilo Villarino', 'Program Logic Formulation', '1st Year', 'A', NULL, 0, 0, 0, '0.00', '2025-10-24', '2025-10-26 05:54:02', '2025-10-26 00:00:00', '2025-10-23 21:54:02'),
(96, 16, 'Ms.Jessica Alcazar', '', '1st Year', 'A', NULL, 0, 0, 0, '0.00', '2025-10-24', '2025-10-26 06:11:15', '2025-10-26 00:00:00', '2025-10-23 22:11:14'),
(97, 16, 'Ms.Jessica Alcazar', 'Capstone Project 2', '1st Year', 'A', NULL, 0, 0, 0, '0.00', '2025-10-24', '2025-10-26 06:11:17', '2025-10-26 00:00:00', '2025-10-23 22:11:16'),
(98, 16, 'Ms.Jessica Alcazar', 'Capstone Project 2', 'N/A', 'N/A', NULL, 0, 0, 0, '0.00', '2025-10-24', '2025-10-26 06:11:17', '2025-10-26 06:12:59', '2025-10-23 22:12:58'),
(99, 12, 'Mr.Alvin Billiones', '', '1st Year', 'A', NULL, 0, 0, 0, '0.00', '2025-10-24', '2025-10-26 06:18:36', '2025-10-26 00:00:00', '2025-10-23 22:18:36'),
(100, 12, 'Mr.Alvin Billiones', 'ITE PROF ELECT 4', '1st Year', 'A', NULL, 0, 0, 0, '0.00', '2025-10-24', '2025-10-26 06:18:39', '2025-10-26 00:00:00', '2025-10-23 22:18:39'),
(101, 12, 'Mr.Alvin Billiones', 'ITE PROF ELECT 4', '1st Year', 'West', NULL, 5, 2, 3, '40.00', '2025-10-24', '2025-10-26 06:18:39', '2025-10-26 06:45:54', '2025-10-23 22:45:54'),
(102, 12, 'Mr.Alvin Billiones', 'ITE PROF ELECT 4', '1st Year', 'West', NULL, 5, 3, 2, '60.00', '2025-10-24', '2025-10-26 06:46:45', '2025-10-26 06:59:25', '2025-10-23 22:59:25'),
(103, 12, 'Mr.Alvin Billiones', '', '1st Year', 'A', NULL, 0, 0, 0, '0.00', '2025-10-24', '2025-10-26 07:01:15', '2025-10-26 00:00:00', '2025-10-23 23:01:15'),
(104, 12, 'Mr.Alvin Billiones', 'ITE PROF ELECT 4', '1st Year', 'A', NULL, 0, 0, 0, '0.00', '2025-10-24', '2025-10-26 07:01:18', '2025-10-26 00:00:00', '2025-10-23 23:01:18'),
(105, 12, 'Mr.Alvin Billiones', 'ITE PROF ELECT 4', '1st Year', 'West', NULL, 5, 4, 1, '80.00', '2025-10-24', '2025-10-26 07:01:18', '2025-10-26 07:38:15', '2025-10-23 23:38:15'),
(106, 13, 'Mr.Danilo Villarino', '', '1st Year', 'A', NULL, 0, 0, 0, '0.00', '2025-10-24', '2025-10-26 07:38:40', '2025-10-26 00:00:00', '2025-10-23 23:38:40'),
(107, 13, 'Mr.Danilo Villarino', 'Program Logic Formulation', '1st Year', 'A', NULL, 0, 0, 0, '0.00', '2025-10-24', '2025-10-26 07:38:42', '2025-10-26 00:00:00', '2025-10-23 23:38:42'),
(108, 13, 'Mr.Danilo Villarino', 'Program Logic Formulation', '1st Year', 'West', NULL, 5, 4, 1, '80.00', '2025-10-24', '2025-10-26 07:38:42', '2025-10-26 07:38:59', '2025-10-23 23:38:59'),
(109, 12, 'Mr.Alvin Billiones', '', '1st Year', 'A', NULL, 0, 0, 0, '0.00', '2025-10-24', '2025-10-26 07:40:16', '2025-10-26 00:00:00', '2025-10-23 23:40:16'),
(110, 12, 'Mr.Alvin Billiones', 'ITE PROF ELECT 4', '1st Year', 'A', NULL, 0, 0, 0, '0.00', '2025-10-24', '2025-10-26 07:40:19', '2025-10-26 00:00:00', '2025-10-23 23:40:19'),
(111, 12, 'Mr.Alvin Billiones', 'ITE PROF ELECT 4', '1st Year', 'West', NULL, 5, 4, 1, '80.00', '2025-10-24', '2025-10-26 07:40:19', '2025-10-26 07:40:43', '2025-10-23 23:40:43'),
(112, 13, 'Mr.Danilo Villarino', '', '1st Year', 'A', NULL, 0, 0, 0, '0.00', '2025-10-24', '2025-10-26 07:43:26', '2025-10-26 00:00:00', '2025-10-23 23:43:26'),
(113, 13, 'Mr.Danilo Villarino', 'Platform Technologies', '1st Year', 'A', NULL, 0, 0, 0, '0.00', '2025-10-24', '2025-10-26 07:43:29', '2025-10-26 00:00:00', '2025-10-23 23:43:29'),
(114, 13, 'Mr.Danilo Villarino', 'Platform Technologies', '1st Year', 'West', NULL, 5, 4, 1, '80.00', '2025-10-24', '2025-10-26 07:43:29', '2025-10-26 07:43:59', '2025-10-23 23:43:59'),
(115, 13, 'Mr.Danilo Villarino', 'Platform Technologies', '1st Year', 'West', NULL, 5, 4, 1, '80.00', '2025-10-24', '2025-10-26 08:12:01', '2025-10-26 08:12:01', '2025-10-24 00:12:01'),
(116, 13, 'Mr.Danilo Villarino', 'Platform Technologies', '1st Year', 'West', NULL, 5, 4, 1, '80.00', '2025-10-24', '2025-10-26 08:30:55', '2025-10-26 08:30:55', '2025-10-24 00:30:55'),
(117, 13, 'Mr.Danilo Villarino', 'Platform Technologies', 'N/A', 'N/A', NULL, 0, 0, 0, '0.00', '2025-10-24', '2025-10-26 08:35:52', '2025-10-26 08:35:52', '2025-10-24 00:35:52'),
(118, 11, 'Mr.Kurt Alegre', '', '1st Year', 'A', NULL, 0, 0, 0, '0.00', '2025-10-24', '2025-10-26 08:36:25', '2025-10-26 00:00:00', '2025-10-24 00:36:25'),
(119, 11, 'Mr.Kurt Alegre', '', '1st Year', 'A', NULL, 0, 0, 0, '0.00', '2025-10-24', '2025-10-26 08:36:41', '2025-10-26 00:00:00', '2025-10-24 00:36:41'),
(120, 16, 'Ms.Jessica Alcazar', '', '1st Year', 'A', NULL, 0, 0, 0, '0.00', '2025-10-24', '2025-10-26 08:37:00', '2025-10-26 00:00:00', '2025-10-24 00:37:00'),
(121, 12, 'Mr.Alvin Billiones', '', '1st Year', 'A', NULL, 0, 0, 0, '0.00', '2025-10-24', '2025-10-26 08:37:22', '2025-10-26 00:00:00', '2025-10-24 00:37:22'),
(122, 13, 'Mr.Danilo Villarino', '', '1st Year', 'A', NULL, 0, 0, 0, '0.00', '2025-10-24', '2025-10-26 08:37:28', '2025-10-26 00:00:00', '2025-10-24 00:37:28'),
(123, 12, 'Mr.Alvin Billiones', '', '1st Year', 'A', NULL, 0, 0, 0, '0.00', '2025-10-24', '2025-10-26 08:37:39', '2025-10-26 00:00:00', '2025-10-24 00:37:39'),
(124, 13, 'Mr.Danilo Villarino', '', '1st Year', 'A', NULL, 0, 0, 0, '0.00', '2025-10-24', '2025-10-26 08:37:48', '2025-10-26 00:00:00', '2025-10-24 00:37:48'),
(125, 13, 'Mr.Danilo Villarino', 'Platform Technologies', '1st Year', 'A', NULL, 0, 0, 0, '0.00', '2025-10-24', '2025-10-26 08:37:54', '2025-10-26 00:00:00', '2025-10-24 00:37:54'),
(126, 13, 'Mr.Danilo Villarino', 'Platform Technologies', 'N/A', 'N/A', NULL, 0, 0, 0, '0.00', '2025-10-24', '2025-10-26 08:37:54', '2025-10-26 08:40:02', '2025-10-24 00:40:02'),
(127, 13, 'Mr.Danilo Villarino', '', '1st Year', 'A', NULL, 0, 0, 0, '0.00', '2025-10-24', '2025-10-26 08:58:45', '2025-10-26 00:00:00', '2025-10-24 00:58:45'),
(128, 13, 'Mr.Danilo Villarino', 'Program Logic Formulation', '1st Year', 'A', NULL, 0, 0, 0, '0.00', '2025-10-24', '2025-10-26 08:59:05', '2025-10-26 00:00:00', '2025-10-24 00:59:05'),
(129, 13, 'Mr.Danilo Villarino', 'Program Logic Formulation', '1st Year', 'West', NULL, 5, 3, 2, '60.00', '2025-10-24', '2025-10-26 08:59:05', '2025-10-26 10:20:45', '2025-10-24 02:20:45'),
(130, 12, 'Mr.Alvin Billiones', '', '1st Year', 'A', NULL, 0, 0, 0, '0.00', '2025-10-24', '2025-10-26 10:21:20', '2025-10-26 00:00:00', '2025-10-24 02:21:20'),
(131, 12, 'Mr.Alvin Billiones', 'ITE PROF ELECT 4', '1st Year', 'A', NULL, 0, 0, 0, '0.00', '2025-10-24', '2025-10-26 10:21:22', '2025-10-26 00:00:00', '2025-10-24 02:21:22'),
(132, 12, 'Mr.Alvin Billiones', 'ITE PROF ELECT 4', '4th Year', 'West', NULL, 3, 3, 0, '100.00', '2025-10-24', '2025-10-26 10:21:22', '2025-10-26 10:39:04', '2025-10-24 02:39:04'),
(133, 12, 'Mr.Alvin Billiones', 'ITE PROF ELECT 4', '4th Year', 'West', NULL, 3, 2, 1, '66.70', '2025-10-24', '2025-10-26 10:39:08', '2025-10-26 10:49:00', '2025-10-24 02:49:00'),
(134, 16, 'Ms.Jessica Alcazar', '', '1st Year', 'A', NULL, 0, 0, 0, '0.00', '2025-10-24', '2025-10-26 15:38:16', '2025-10-26 00:00:00', '2025-10-24 07:38:16'),
(135, 16, 'Ms.Jessica Alcazar', '', '1st Year', 'A', NULL, 0, 0, 0, '0.00', '2025-10-26', '2025-10-26 06:07:48', '2025-10-26 00:00:00', '2025-10-25 22:07:48'),
(136, 16, 'Ms.Jessica Alcazar', '', '1st Year', 'A', NULL, 0, 0, 0, '0.00', '2025-10-27', '2025-10-26 09:06:34', '2025-10-26 00:00:00', '2025-10-27 01:06:34'),
(137, 16, 'Ms.Jessica Alcazar', 'Program Logic Formulation', '1st Year', 'A', NULL, 0, 0, 0, '0.00', '2025-10-27', '2025-10-26 09:06:42', '2025-10-26 00:00:00', '2025-10-27 01:06:42'),
(138, 16, 'Ms.Jessica Alcazar', '', '1st Year', 'A', NULL, 0, 0, 0, '0.00', '2025-10-27', '2025-10-26 09:30:08', '2025-10-26 00:00:00', '2025-10-27 01:30:08'),
(139, 16, 'Ms.Jessica Alcazar', 'Program Logic Formulation', '1st Year', 'A', NULL, 0, 0, 0, '0.00', '2025-10-27', '2025-10-26 09:30:21', '2025-10-26 00:00:00', '2025-10-27 01:30:21'),
(140, 16, 'Ms.Jessica Alcazar', 'Program Logic Formulation', '1st Year', 'West', NULL, 3, 3, 0, '100.00', '2025-10-27', '2025-10-26 09:30:21', '2025-10-26 09:31:09', '2025-10-27 01:31:09'),
(141, 16, 'Ms.Jessica Alcazar', '', '1st Year', 'A', NULL, 0, 0, 0, '0.00', '2025-10-27', '2025-10-26 09:33:42', '2025-10-26 00:00:00', '2025-10-27 01:33:42'),
(142, 16, 'Ms.Jessica Alcazar', 'Program Logic Formulation', '1st Year', 'A', NULL, 0, 0, 0, '0.00', '2025-10-27', '2025-10-26 09:33:45', '2025-10-26 00:00:00', '2025-10-27 01:33:45'),
(143, 16, 'Ms.Jessica Alcazar', 'Program Logic Formulation', 'N/A', 'N/A', NULL, 0, 0, 0, '0.00', '2025-10-27', '2025-10-26 09:33:45', '2025-10-26 09:34:47', '2025-10-27 01:34:47'),
(144, 16, 'Ms.Jessica Alcazar', 'Program Logic Formulation', 'N/A', 'N/A', NULL, 0, 0, 0, '0.00', '2025-10-27', '2025-10-26 09:37:32', '2025-10-26 09:37:32', '2025-10-27 01:37:32'),
(145, 16, 'Ms.Jessica Alcazar', '', '1st Year', 'A', NULL, 0, 0, 0, '0.00', '2025-10-27', '2025-10-26 09:37:54', '2025-10-26 00:00:00', '2025-10-27 01:37:54'),
(146, 16, 'Ms.Jessica Alcazar', 'Program Logic Formulation', '1st Year', 'A', NULL, 0, 0, 0, '0.00', '2025-10-27', '2025-10-26 09:37:58', '2025-10-26 00:00:00', '2025-10-27 01:37:58'),
(147, 16, 'Ms.Jessica Alcazar', 'Program Logic Formulation', 'N/A', 'N/A', NULL, 0, 0, 0, '0.00', '2025-10-27', '2025-10-26 09:37:58', '2025-10-26 09:39:00', '2025-10-27 01:39:00'),
(148, 16, 'Ms.Jessica Alcazar', '', '1st Year', 'A', NULL, 0, 0, 0, '0.00', '2025-10-27', '2025-10-26 09:46:18', '2025-10-26 00:00:00', '2025-10-27 01:46:18'),
(149, 16, 'Ms.Jessica Alcazar', 'Program Logic Formulation', '1st Year', 'A', NULL, 0, 0, 0, '0.00', '2025-10-27', '2025-10-26 09:46:21', '2025-10-26 00:00:00', '2025-10-27 01:46:21'),
(150, 16, 'Ms.Jessica Alcazar', 'Program Logic Formulation', 'N/A', 'N/A', NULL, 0, 0, 0, '0.00', '2025-10-27', '2025-10-26 09:46:21', '2025-10-26 09:47:10', '2025-10-27 01:47:10'),
(151, 16, 'Ms.Jessica Alcazar', '', '1st Year', 'A', NULL, 0, 0, 0, '0.00', '2025-10-27', '2025-10-26 09:47:35', '2025-10-26 00:00:00', '2025-10-27 01:47:35'),
(152, 16, 'Ms.Jessica Alcazar', '', '1st Year', 'A', NULL, 0, 0, 0, '0.00', '2025-10-27', '2025-10-26 09:47:46', '2025-10-26 00:00:00', '2025-10-27 01:47:46'),
(153, 13, 'Mr.Danilo Villarino', '', '1st Year', 'A', NULL, 0, 0, 0, '0.00', '2025-10-27', '2025-10-26 09:48:08', '2025-10-26 00:00:00', '2025-10-27 01:48:08'),
(154, 13, 'Mr.Danilo Villarino', '', '1st Year', 'A', NULL, 0, 0, 0, '0.00', '2025-10-27', '2025-10-26 09:48:27', '2025-10-26 00:00:00', '2025-10-27 01:48:27'),
(155, 13, 'Mr.Danilo Villarino', '', '1st Year', 'A', NULL, 0, 0, 0, '0.00', '2025-10-27', '2025-10-26 09:48:45', '2025-10-26 00:00:00', '2025-10-27 01:48:45'),
(156, 12, 'Mr.Alvin Billiones', '', '1st Year', 'A', NULL, 0, 0, 0, '0.00', '2025-10-27', '2025-10-26 09:48:51', '2025-10-26 00:00:00', '2025-10-27 01:48:51'),
(157, 13, 'Mr.Danilo Villarino', '', '1st Year', 'A', NULL, 0, 0, 0, '0.00', '2025-10-27', '2025-10-26 10:54:38', '2025-10-26 00:00:00', '2025-10-27 02:54:38'),
(158, 12, 'Mr.Alvin Billiones', '', '1st Year', 'A', NULL, 0, 0, 0, '0.00', '2025-10-27', '2025-10-26 10:54:43', '2025-10-26 00:00:00', '2025-10-27 02:54:43'),
(159, 12, 'Mr.Alvin Billiones', 'Web Development', '1st Year', 'A', NULL, 0, 0, 0, '0.00', '2025-10-27', '2025-10-26 10:54:46', '2025-10-26 00:00:00', '2025-10-27 02:54:46'),
(160, 12, 'Mr.Alvin Billiones', 'Web Development', '4th Year', 'West', NULL, 3, 3, 0, '100.00', '2025-10-27', '2025-10-26 10:54:46', '2025-10-26 11:26:23', '2025-10-27 03:26:23'),
(161, 12, 'Mr.Alvin Billiones', 'Web Development', '1st Year', 'West', NULL, 3, 1, 2, '33.30', '2025-10-27', '2025-10-26 11:26:28', '2025-10-26 11:29:37', '2025-10-27 03:29:37'),
(162, 12, 'Mr.Alvin Billiones', 'Web Development', '1st Year', 'West', NULL, 3, 1, 2, '33.30', '2025-10-27', '2025-10-26 11:29:39', '2025-10-26 11:33:30', '2025-10-27 03:33:30'),
(168, 12, 'Mr.Alvin Billiones', 'Web Development', '1st Year', 'West', 'ComLab1', 3, 1, 2, '33.30', '2025-10-27', '0000-00-00 00:00:00', '0000-00-00 00:00:00', '2025-10-27 04:15:32'),
(169, 12, 'Mr.Alvin Billiones', 'Web Development', '4th Year', 'West', 'ComLab1', 3, 1, 2, '33.30', '2025-10-27', '0000-00-00 00:00:00', '0000-00-00 00:00:00', '2025-10-27 04:16:25'),
(170, 13, 'Mr.Danilo Villarino', '', '1st Year', 'A', NULL, 0, 0, 0, '0.00', '2025-10-27', '0000-00-00 00:00:00', '0000-00-00 00:00:00', '2025-10-27 09:14:35'),
(171, 16, 'Ms.Jessica Alcazar', '', '1st Year', 'A', NULL, 0, 0, 0, '0.00', '2025-10-28', '0000-00-00 00:00:00', '0000-00-00 00:00:00', '2025-10-27 23:44:39'),
(172, 16, 'Ms.Jessica Alcazar', 'Sub3', '1st Year', 'A', NULL, 0, 0, 0, '0.00', '2025-10-28', '0000-00-00 00:00:00', '0000-00-00 00:00:00', '2025-10-27 23:44:59'),
(173, 16, 'Ms.Jessica Alcazar', 'Sub3', '1st Year', 'West', 'ComLab1', 3, 2, 1, '66.70', '2025-10-28', '0000-00-00 00:00:00', '2008-01-04 00:00:00', '2025-10-28 00:01:04'),
(174, 16, 'Ms.Jessica Alcazar', '', '1st Year', 'A', NULL, 0, 0, 0, '0.00', '2025-10-28', '0000-00-00 00:00:00', '0000-00-00 00:00:00', '2025-10-28 03:44:08'),
(175, 16, 'Ms.Jessica Alcazar', '', '1st Year', 'A', NULL, 0, 0, 0, '0.00', '2025-10-28', '0000-00-00 00:00:00', '0000-00-00 00:00:00', '2025-10-28 05:59:51'),
(176, 16, 'Ms.Jessica Alcazar', '', '1st Year', 'A', NULL, 0, 0, 0, '0.00', '2025-10-28', '0000-00-00 00:00:00', '0000-00-00 00:00:00', '2025-10-28 06:00:32'),
(177, 16, 'Ms.Jessica Alcazar', '', '1st Year', 'A', NULL, 0, 0, 0, '0.00', '2025-10-28', '0000-00-00 00:00:00', '0000-00-00 00:00:00', '2025-10-28 06:00:35'),
(178, 16, 'Ms.Jessica Alcazar', '', '1st Year', 'A', NULL, 0, 0, 0, '0.00', '2025-10-28', '2014-01-15 00:00:00', '0000-00-00 00:00:00', '2025-10-28 06:01:15'),
(179, 16, 'Ms.Jessica Alcazar', 'Sub3', '1st Year', 'A', NULL, 0, 0, 0, '0.00', '2025-10-28', '2014-01-21 00:00:00', '0000-00-00 00:00:00', '2025-10-28 06:01:21'),
(180, 16, 'Ms.Jessica Alcazar', 'Sub3', '1st Year', 'West', 'ComLab1', 3, 1, 2, '33.30', '2025-10-28', '2014-01-21 00:00:00', '2014-03-13 00:00:00', '2025-10-28 06:03:14'),
(181, 16, 'Ms.Jessica Alcazar', '', '1st Year', 'A', NULL, 0, 0, 0, '0.00', '2025-10-29', '2019-04-07 00:00:00', '0000-00-00 00:00:00', '2025-10-29 11:04:07'),
(182, 16, 'Ms.Jessica Alcazar', '', '1st Year', 'A', NULL, 0, 0, 0, '0.00', '2025-10-29', '2019-04-18 00:00:00', '0000-00-00 00:00:00', '2025-10-29 11:04:18'),
(195, 11, 'Mr.Kurt Alegre', 'Program Logic Formulation', '4th Year', 'West', 'IT-LEC2', 3, 5, 2, '166.67', '2025-10-29', '0000-00-00 00:00:00', '0000-00-00 00:00:00', '2025-10-29 14:56:17'),
(196, 11, 'Mr.Kurt Alegre', 'Program Logic Formulation', '1st Year', 'West', 'IT-LEC2', 2, 2, 0, '100.00', '2025-10-29', '0000-00-00 00:00:00', '2023-12-11 00:00:00', '2025-10-29 15:12:11'),
(197, 16, 'Ms.Jessica Alcazar', '', '1st Year', 'A', NULL, 0, 2, 0, '200.00', '2025-11-03', '0000-00-00 00:00:00', '0000-00-00 00:00:00', '2025-11-02 20:39:48'),
(198, 16, 'Ms.Jessica Alcazar', 'Program Logic Formulation', '1st Year', 'West', NULL, 0, 2, 0, '200.00', '2025-11-03', '0000-00-00 00:00:00', '0000-00-00 00:00:00', '2025-11-02 20:40:03'),
(199, 16, 'Ms.Jessica Alcazar', 'Program Logic Formulation', '1st Year', 'West', 'ComLab1', 2, 2, 0, '100.00', '2025-11-03', '0000-00-00 00:00:00', '0000-00-00 00:00:00', '2025-11-02 20:51:31'),
(200, 16, 'Ms.Jessica Alcazar', '', '1st Year', 'A', NULL, 0, 0, 0, '0.00', '2025-11-03', '2005-09-22 00:00:00', '0000-00-00 00:00:00', '2025-11-02 21:09:22'),
(201, 16, 'Ms.Jessica Alcazar', '', '1st Year', 'A', NULL, 0, 0, 0, '0.00', '2025-11-03', '0000-00-00 00:00:00', '0000-00-00 00:00:00', '2025-11-02 22:48:05');

-- --------------------------------------------------------

--
-- Table structure for table `instructor_glogs`
--
-- Creation: Oct 11, 2025 at 02:30 PM
--

CREATE TABLE `instructor_glogs` (
  `id` int(11) NOT NULL,
  `instructor_id` int(11) NOT NULL,
  `id_number` varchar(50) NOT NULL,
  `name` varchar(255) NOT NULL,
  `action` enum('IN','OUT') NOT NULL,
  `time_in` time NOT NULL,
  `time_out` time NOT NULL,
  `date` date NOT NULL,
  `period` enum('AM','PM') NOT NULL,
  `location` varchar(100) NOT NULL,
  `department` varchar(100) NOT NULL,
  `photo` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `date_logged` date NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `instructor_glogs`
--

INSERT INTO `instructor_glogs` (`id`, `instructor_id`, `id_number`, `name`, `action`, `time_in`, `time_out`, `date`, `period`, `location`, `department`, `photo`, `created_at`, `date_logged`) VALUES
(31, 17, '0001-0005', 'Mr.Richard Bracero', 'IN', '16:13:59', '00:00:00', '2025-10-25', 'PM', 'Gate', 'Main', NULL, '2025-10-25 08:13:59', '2025-10-25'),
(32, 16, '2024-0117', 'Ms.Jessica Alcazar', 'IN', '12:57:44', '00:00:00', '2025-10-26', 'PM', 'Gate', 'Main', NULL, '2025-10-26 04:57:44', '2025-10-26'),
(33, 17, '0001-0005', 'Mr.Richard Bracero', 'OUT', '14:46:45', '16:58:19', '2025-10-26', 'PM', 'Gate', 'Main', NULL, '2025-10-26 06:46:45', '2025-10-26'),
(34, 12, '0001-0004', 'Mr.Alvin Billiones', 'IN', '15:51:26', '00:00:00', '2025-10-26', 'PM', 'Gate', 'Main', NULL, '2025-10-26 07:51:26', '2025-10-26'),
(35, 16, '2024-0117', 'Ms.Jessica Alcazar', 'IN', '19:05:46', '00:00:00', '2025-10-27', 'PM', 'Gate', 'Main', NULL, '2025-10-27 11:05:46', '2025-10-27'),
(36, 16, '2024-0117', 'Ms.Jessica Alcazar', 'IN', '16:20:39', '00:00:00', '2025-11-02', 'PM', 'Gate', 'Main', NULL, '2025-11-02 08:20:39', '2025-11-02'),
(37, 16, '2024-0117', 'Ms.Jessica Alcazar', 'OUT', '06:44:14', '06:55:29', '2025-11-03', 'AM', 'Gate', 'Main', NULL, '2025-11-02 22:44:14', '2025-11-03'),
(38, 17, '0001-0005', 'Mr.Richard Bracero', 'IN', '06:55:46', '00:00:00', '2025-11-03', 'AM', 'Gate', 'Main', NULL, '2025-11-02 22:55:46', '2025-11-03'),
(39, 13, '0001-0003', 'Mr.Danilo Villarino', 'IN', '06:55:54', '00:00:00', '2025-11-03', 'AM', 'Gate', 'Main', NULL, '2025-11-02 22:55:54', '2025-11-03'),
(40, 12, '0001-0004', 'Mr.Alvin Billiones', 'IN', '06:56:12', '00:00:00', '2025-11-03', 'AM', 'Gate', 'Main', NULL, '2025-11-02 22:56:12', '2025-11-03'),
(41, 11, '0001-0001', 'Mr.Kurt Alegre', 'IN', '06:56:21', '00:00:00', '2025-11-03', 'AM', 'Gate', 'Main', NULL, '2025-11-02 22:56:21', '2025-11-03');

-- --------------------------------------------------------

--
-- Table structure for table `instructor_logs`
--
-- Creation: Oct 20, 2025 at 06:23 AM
--

CREATE TABLE `instructor_logs` (
  `id` int(11) NOT NULL,
  `instructor_id` int(11) DEFAULT NULL,
  `id_number` varchar(20) DEFAULT NULL,
  `time_in` datetime DEFAULT NULL,
  `time_out` datetime DEFAULT NULL,
  `swapped_with` int(11) DEFAULT NULL,
  `swap_time` datetime DEFAULT NULL,
  `department` varchar(100) DEFAULT NULL,
  `location` varchar(100) DEFAULT NULL,
  `status` varchar(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `instructor_logs`
--

INSERT INTO `instructor_logs` (`id`, `instructor_id`, `id_number`, `time_in`, `time_out`, `swapped_with`, `swap_time`, `department`, `location`, `status`) VALUES
(38, 16, '2024-0117', '2025-08-03 14:31:21', NULL, NULL, NULL, 'BSIT', 'ComLab2', NULL),
(47, 11, '0001-0001', '2025-08-05 11:41:38', NULL, NULL, NULL, 'BSIT', 'IT-LEC1', NULL),
(63, 11, '0001-0001', '2025-08-06 19:44:47', NULL, NULL, NULL, 'BSIT', 'ComLab1', NULL),
(64, 16, '2024-0117', '2025-08-06 19:45:50', NULL, NULL, NULL, 'BSIT', 'ComLab1', NULL),
(65, 16, '2024-0117', '2025-08-07 06:35:59', NULL, NULL, NULL, 'BSIT', 'ComLab1', NULL),
(67, 16, '2024-0117', '2025-08-09 11:52:59', NULL, NULL, NULL, 'BSIT', 'ComLab1', NULL),
(70, 16, '2024-0117', '2025-08-12 17:18:29', NULL, NULL, NULL, 'BSIT', 'ComLab1', NULL),
(77, 16, '2024-0117', '2025-08-15 16:32:02', NULL, NULL, NULL, 'BSIT', 'ComLab1', NULL),
(80, 16, '2024-0117', '2025-08-16 13:52:09', NULL, NULL, NULL, 'BSIT', 'ComLab2', NULL),
(81, 12, '0001-0004', '2025-08-16 13:56:14', NULL, NULL, NULL, 'BSIT', 'ComLab2', NULL),
(83, 16, '2024-0117', '2025-08-17 10:59:36', NULL, NULL, NULL, 'BSIT', 'ComLab2', NULL),
(84, 16, '2024-0117', '2025-08-19 11:47:01', '2025-08-19 11:47:10', NULL, NULL, 'BSIT', 'ComLab1', 'saved'),
(85, 16, '2024-0117', '2025-08-22 11:08:01', '2025-08-22 11:08:13', NULL, NULL, 'BSIT', 'ComLab2', 'saved'),
(86, 16, '2024-0117', '2025-08-25 11:26:08', '2025-08-25 11:26:13', NULL, NULL, 'BSIT', 'ComLab2', 'saved'),
(87, 16, '2024-0117', '2025-09-02 20:18:24', '2025-09-02 22:59:08', NULL, NULL, 'BSIT', 'ComLab3', 'saved'),
(88, 16, '2024-0117', '2025-09-03 00:27:39', '2025-09-03 09:19:52', NULL, NULL, 'BSIT', 'ComLab3', 'saved'),
(89, 12, '0001-0004', '2025-09-03 12:54:57', '2025-09-03 12:55:01', NULL, NULL, 'BSIT', 'ComLab2', 'saved'),
(91, 16, '2024-0117', '2025-09-10 10:19:39', NULL, NULL, NULL, 'BSIT', 'ComLab2', NULL),
(92, 16, '2024-0117', '2025-09-11 00:40:27', NULL, NULL, NULL, 'BSIT', 'ComLab2', NULL),
(103, 16, '2024-0117', '2025-09-12 15:29:56', NULL, NULL, NULL, 'BSIT', 'ComLab2', NULL),
(105, 16, '2024-0117', '2025-09-16 18:56:24', NULL, NULL, NULL, 'BSIT', 'ComLab3', NULL),
(106, 16, '2024-0117', '2025-09-17 12:45:56', NULL, NULL, NULL, 'BSIT', 'ComLab1', NULL),
(107, 16, '2024-0117', '2025-09-19 13:31:14', NULL, NULL, NULL, 'BSIT', 'ComLab2', NULL),
(109, 16, '2024-0117', '2025-09-22 09:39:49', NULL, NULL, NULL, 'BSIT', 'ComLab2', NULL),
(110, 16, NULL, '2025-10-14 00:32:11', NULL, NULL, NULL, NULL, NULL, 'active'),
(111, 16, NULL, '2025-10-14 12:08:40', NULL, NULL, NULL, NULL, NULL, 'active'),
(112, 16, NULL, '2025-10-16 20:10:34', NULL, NULL, NULL, NULL, NULL, 'active'),
(113, 16, NULL, '2025-10-18 09:45:56', NULL, NULL, NULL, NULL, NULL, 'active'),
(114, 12, NULL, '2025-10-18 16:30:11', NULL, NULL, NULL, NULL, NULL, 'active'),
(115, 16, NULL, '2025-10-19 21:31:01', NULL, NULL, NULL, NULL, NULL, 'active'),
(116, 11, NULL, '2025-10-20 01:30:55', NULL, NULL, NULL, NULL, NULL, 'active'),
(117, 16, NULL, '2025-10-22 03:08:48', NULL, NULL, NULL, NULL, NULL, 'active'),
(118, 12, NULL, '2025-10-23 15:46:45', NULL, NULL, NULL, NULL, NULL, 'active'),
(119, 13, NULL, '2025-10-23 17:57:50', NULL, NULL, NULL, NULL, NULL, 'active'),
(120, 12, NULL, '2025-10-23 19:39:08', NULL, NULL, NULL, NULL, NULL, 'active'),
(121, 16, NULL, '2025-10-26 18:32:58', NULL, NULL, NULL, NULL, NULL, 'active'),
(122, 12, NULL, '2025-10-26 20:26:28', NULL, NULL, NULL, NULL, NULL, 'active'),
(123, 12, NULL, '2025-10-26 20:29:39', NULL, NULL, NULL, NULL, NULL, 'active'),
(124, 12, NULL, '2025-10-26 20:33:33', NULL, NULL, NULL, NULL, NULL, 'active'),
(125, 12, NULL, '2025-10-26 21:16:03', NULL, NULL, NULL, NULL, NULL, 'active'),
(126, 16, NULL, '2025-10-27 23:03:42', NULL, NULL, NULL, NULL, NULL, 'active'),
(127, 11, NULL, '2025-10-29 07:56:19', NULL, NULL, NULL, NULL, NULL, 'active'),
(128, 16, NULL, '2025-11-02 12:51:35', NULL, NULL, NULL, NULL, NULL, 'active');

-- --------------------------------------------------------

--
-- Table structure for table `login_attempts`
--
-- Creation: Oct 11, 2025 at 02:30 PM
--

CREATE TABLE `login_attempts` (
  `id` int(11) NOT NULL,
  `ip_address` varchar(45) NOT NULL,
  `username` varchar(255) NOT NULL,
  `attempted_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `success` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `lostcard`
--
-- Creation: Oct 11, 2025 at 02:30 PM
--

CREATE TABLE `lostcard` (
  `id` int(11) NOT NULL,
  `personnel_id` int(11) NOT NULL,
  `date_requested` datetime NOT NULL,
  `status` int(1) NOT NULL,
  `verification_photo` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `lost_found`
--
-- Creation: Oct 11, 2025 at 02:30 PM
--

CREATE TABLE `lost_found` (
  `id` int(11) NOT NULL,
  `sender` varchar(255) NOT NULL,
  `status` int(11) NOT NULL,
  `department` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `date` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `password_reset_tokens`
--
-- Creation: Oct 11, 2025 at 02:30 PM
--

CREATE TABLE `password_reset_tokens` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `token` varchar(64) NOT NULL,
  `expires_at` datetime NOT NULL,
  `used` tinyint(4) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `personell`
--
-- Creation: Oct 25, 2025 at 10:06 AM
-- Last update: Nov 02, 2025 at 10:37 PM
--

CREATE TABLE `personell` (
  `id` int(11) NOT NULL,
  `id_number` varchar(20) NOT NULL,
  `last_name` varchar(100) NOT NULL,
  `first_name` varchar(100) NOT NULL,
  `date_of_birth` date NOT NULL,
  `role` varchar(50) NOT NULL,
  `category` varchar(50) NOT NULL,
  `department` varchar(100) NOT NULL,
  `status` varchar(20) DEFAULT 'Active',
  `photo` varchar(255) DEFAULT 'default.png',
  `date_added` datetime DEFAULT current_timestamp(),
  `deleted` tinyint(4) DEFAULT 0
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `personell`
--

INSERT INTO `personell` (`id`, `id_number`, `last_name`, `first_name`, `date_of_birth`, `role`, `category`, `department`, `status`, `photo`, `date_added`, `deleted`) VALUES
(1, '2222-2222', 'Select', 'Tanduay', '1996-05-28', 'Staff', 'Regular', 'BSIT', 'Active', '../uploads/personell68fca2febf149.jpg', '2025-10-25 03:14:22', 1),
(2, '1111-1111', 'Secu', 'Rity', '1992-06-24', 'Security Personnel', 'Regular', 'BSIT', 'Active', '68fdc5c278b83.png', '2025-10-25 03:17:10', 0),
(3, '3333-3333', 'Bantay', 'Tig', '1996-10-24', 'Security Personnel', 'Regular', 'BSIT', 'Active', '68fdbf03b55ad.png', '2025-10-25 16:33:36', 0),
(4, '6666-6666', 'Boy', 'Lom', '1996-02-26', 'Staff', 'Regular', 'BSIT', 'Active', '68fdbd3866fc4.png', '2025-10-25 23:16:07', 0),
(5, '9999-9997', 'Sik', 'Que', '2025-10-08', 'Security Personnel', 'Regular', 'BSIT', 'Active', '68fdc43fa66fa.png', '2025-10-25 23:48:31', 0),
(6, '7777-7777', 'Reer', 'Weee', '1999-06-09', 'Security Personnel', 'Regular', 'BSIT', 'Active', '68fdc601e5902.png', '2025-10-25 23:56:01', 1),
(7, '1212-9999', 'Bravo', 'Johny', '2025-10-04', 'Janitor', 'Contractual', 'BSIT', 'Active', '6900db918145f.jpg', '2025-10-28 08:04:49', 0),
(8, '2024-0380', 'Derder', 'Angelo', '1997-01-14', 'Security Personnel', 'Regular', 'BSIT', 'Active', '6907dd19ea334.png', '2025-11-02 14:37:13', 0);

-- --------------------------------------------------------

--
-- Table structure for table `personell_glogs`
--
-- Creation: Oct 26, 2025 at 07:07 AM
-- Last update: Nov 02, 2025 at 10:58 PM
--

CREATE TABLE `personell_glogs` (
  `id` int(11) NOT NULL,
  `personell_id` int(11) DEFAULT NULL,
  `id_number` varchar(50) DEFAULT NULL,
  `name` varchar(255) DEFAULT NULL,
  `action` enum('IN','OUT') DEFAULT NULL,
  `time_in` time DEFAULT NULL,
  `time_out` time DEFAULT NULL,
  `date` date DEFAULT NULL,
  `period` enum('AM','PM') DEFAULT NULL,
  `location` varchar(255) DEFAULT NULL,
  `department` varchar(255) DEFAULT NULL,
  `date_logged` date DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `personell_glogs`
--

INSERT INTO `personell_glogs` (`id`, `personell_id`, `id_number`, `name`, `action`, `time_in`, `time_out`, `date`, `period`, `location`, `department`, `date_logged`, `created_at`) VALUES
(1, 6, '7777-7777', 'WeeeReer', 'OUT', '15:07:32', '16:59:17', '2025-10-26', 'PM', 'Gate', 'Main', '2025-10-26', '2025-10-26 00:07:32'),
(2, 5, '9999-9999', 'QueSik', 'IN', '15:26:25', '00:00:00', '2025-10-26', 'PM', 'Gate', 'Main', '2025-10-26', '2025-10-26 00:26:25'),
(3, 4, '6666-6666', 'LomBoy', 'OUT', '15:33:35', '16:59:06', '2025-10-26', 'PM', 'Gate', 'Main', '2025-10-26', '2025-10-26 00:33:35'),
(4, 5, '9999-9999', 'QueSik', 'OUT', '21:12:40', '21:13:35', '2025-10-28', 'PM', 'Gate', 'Main', '2025-10-28', '2025-10-28 06:12:40'),
(5, 4, '6666-6666', 'LomBoy', 'OUT', '19:06:01', '20:20:08', '2025-10-29', 'PM', 'Gate', 'Main', '2025-10-29', '2025-10-29 04:06:01'),
(6, 4, '6666-6666', 'LomBoy', 'IN', '06:57:32', '00:00:00', '2025-11-03', 'AM', 'Gate', 'Main', '2025-11-03', '2025-11-02 14:57:32'),
(7, 7, '1212-9999', 'JohnyBravo', 'IN', '06:58:19', '00:00:00', '2025-11-03', 'AM', 'Gate', 'Main', '2025-11-03', '2025-11-02 14:58:19');

-- --------------------------------------------------------

--
-- Table structure for table `role`
--
-- Creation: Oct 11, 2025 at 02:30 PM
--

CREATE TABLE `role` (
  `id` int(11) NOT NULL,
  `role` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `role`
--

INSERT INTO `role` (`id`, `role`) VALUES
(5, 'Instructor'),
(6, 'Security Personnel'),
(8, 'Staff'),
(140, 'Janitor'),
(144, 'Brommer');

-- --------------------------------------------------------

--
-- Table structure for table `rooms`
--
-- Creation: Oct 11, 2025 at 02:30 PM
--

CREATE TABLE `rooms` (
  `id` int(11) NOT NULL,
  `room` varchar(255) NOT NULL,
  `department` varchar(255) NOT NULL,
  `password` varchar(255) DEFAULT NULL,
  `desc` varchar(255) DEFAULT NULL,
  `descr` varchar(255) DEFAULT NULL,
  `authorized_personnel` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `rooms`
--

INSERT INTO `rooms` (`id`, `room`, `department`, `password`, `desc`, `descr`, `authorized_personnel`) VALUES
(151, 'ComLab2', 'BSIT', 'comlab2', NULL, 'IT lab1', 'Instructor'),
(152, 'ComLab1', 'BSIT', 'comlab1', NULL, 'comlab1', 'Instructor'),
(153, 'Gate', 'Main', 'gate123', NULL, 'Main Entrance', 'Security Personnel'),
(154, 'ComLab3', 'BSIT', 'comlab3', NULL, 'IT lab 3', 'Instructor'),
(155, 'IT-LEC1', 'BSIT', 'itlec1', NULL, 'IT LECTURE 1', 'Instructor'),
(156, 'IT-LEC2', 'BSIT', 'itlec2', NULL, 'IT Lecture 2', 'Instructor');

-- --------------------------------------------------------

--
-- Table structure for table `rooms_backup`
--
-- Creation: Oct 11, 2025 at 02:30 PM
--

CREATE TABLE `rooms_backup` (
  `id` int(11) NOT NULL DEFAULT 0,
  `room` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `department` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `password` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `desc` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `descr` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `authorized_personnel` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `rooms_backup`
--

INSERT INTO `rooms_backup` (`id`, `room`, `department`, `password`, `desc`, `descr`, `authorized_personnel`) VALUES
(150, 'BSHM 01', 'BSHRM', 'BSHM01', NULL, 'Kitchen Lab', 'Instructor'),
(151, 'ComLab2', 'BSIT', 'comlab2', NULL, 'IT lab1', 'Instructor'),
(152, 'ComLab1', 'BSIT', 'comlab1', NULL, 'comlab1', 'Instructor'),
(153, 'Gate', 'Main', 'gate123', NULL, 'gilugewqe', 'Security Personnel'),
(154, 'ComLab3', 'BSIT', 'comlab3', NULL, 'IT lab 3', 'Instructor'),
(155, 'IT-LEC1', 'BSIT', 'itlec1', NULL, 'IT LECTURE 1', 'Instructor'),
(156, 'IT-LEC2', 'BSIT', 'itlec2', NULL, 'IT Lecture 2', 'Instructor');

-- --------------------------------------------------------

--
-- Table structure for table `room_logs`
--
-- Creation: Oct 11, 2025 at 02:30 PM
--

CREATE TABLE `room_logs` (
  `id` int(11) NOT NULL,
  `date_logged` varchar(255) NOT NULL,
  `location` varchar(255) NOT NULL,
  `time_in` varchar(255) NOT NULL,
  `time_out` varchar(255) NOT NULL,
  `personnel_id` int(11) DEFAULT NULL,
  `log_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `room_schedules`
--
-- Creation: Oct 11, 2025 at 02:30 PM
--

CREATE TABLE `room_schedules` (
  `department` varchar(5155) NOT NULL,
  `id` int(11) NOT NULL,
  `room_name` varchar(100) DEFAULT NULL,
  `room_location` varchar(255) DEFAULT NULL,
  `room_password` varchar(255) DEFAULT NULL,
  `subject` varchar(100) DEFAULT NULL,
  `section` varchar(50) DEFAULT NULL,
  `year_level` varchar(20) DEFAULT NULL,
  `start_time` time DEFAULT NULL,
  `end_time` time DEFAULT NULL,
  `day` varchar(20) DEFAULT NULL,
  `instructor` varchar(58) NOT NULL,
  `instructor_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `room_schedules`
--

INSERT INTO `room_schedules` (`department`, `id`, `room_name`, `room_location`, `room_password`, `subject`, `section`, `year_level`, `start_time`, `end_time`, `day`, `instructor`, `instructor_id`) VALUES
('BSIT', 14, 'ComLab1', NULL, NULL, 'Physical Activity towards Health and Fitness', 'West', '2nd Year', '07:20:00', '08:20:00', 'Saturday', 'Mr.Alvin Billiones', 12),
('BSIT', 15, 'ComLab1', NULL, NULL, 'Mathematics in the Modern Science', 'West', '2nd Year', '09:00:00', '10:30:00', 'Tuesday', 'Mr.Richard Bracero', 16),
('BSIT', 21, 'IT-LEC1', NULL, NULL, 'Fundamentals of Accounting', 'West', '4th Year', '01:59:00', '10:40:00', 'Monday', 'Mr.Kurt Alegre', 11),
('BSIT', 24, 'ComLab2', NULL, NULL, 'SUB4-01', 'West', '4th Year', '14:00:00', '15:00:00', 'Saturday', 'Ms.Jessica Alcazar', 16),
('BSIT', 25, 'ComLab2', NULL, NULL, 'ITE PROF ELECT 4', 'East', '4th Year', '16:00:00', '17:00:00', 'Saturday', 'Mr.Alvin Billiones', 12),
('BSIT', 26, 'ComLab2', NULL, NULL, 'Philippine Popular Culture', 'West', '2nd Year', '00:17:00', '13:11:00', 'Monday', 'Mr.Alvin Billiones', NULL),
('BSIT', 27, 'ComLab1', NULL, NULL, 'Program Logic Formulation', 'West', '1st Year', '18:15:00', '19:15:00', 'Monday', 'Ms.Jessica Alcazar', NULL),
('BSIT', 29, 'ComLab3', NULL, NULL, 'Program Logic Formulation', 'West', '1st Year', '20:18:00', '23:18:00', 'Wednesday', 'Ms.Jessica Alcazar', NULL),
('BSIT', 31, 'IT-LEC1', NULL, NULL, 'Fundamentals of Accounting', 'West', '2nd Year', '01:13:00', '03:12:00', 'Saturday', 'Mr.Kurt Alegre', NULL),
('BSIT', 32, 'IT-LEC1', NULL, NULL, 'Program Logic Formulation', 'West', '1st Year', '16:27:00', '17:26:00', 'Monday', 'Mr.Kurt Alegre', NULL),
('BSIT', 33, 'ComLab1', NULL, NULL, 'Information Assurance and Security 2', 'West', '4th Year', '22:23:00', '23:15:00', 'Tuesday', 'Ms.Jessica Alcazar', NULL),
('BSIT', 34, 'ComLab2', NULL, NULL, 'Capstone Project 2', 'West', '4th Year', '10:20:00', '11:59:00', 'Friday', 'Ms.Jessica Alcazar', NULL),
('BSIT', 35, 'ComLab1', NULL, NULL, 'Information Assurance and Security 2', 'West', '4th Year', '08:35:00', '11:35:00', 'Saturday', 'Ms.Jessica Alcazar', NULL),
('BSIT', 36, 'IT-LEC1', NULL, NULL, 'Program Logic Formulation', 'West', '1st Year', '19:28:00', '23:28:00', 'Monday', 'Mr.Kurt Alegre', NULL),
('BSIT', 37, 'IT-LEC1', NULL, NULL, 'Information Management', 'West', '2nd Year', '21:00:00', '23:00:00', 'Thursday', 'Mrs.Emily Forrosuelo', NULL),
('BSIT', 38, 'IT-LEC1', NULL, NULL, 'Digital Logic Design (Workshop 1)', 'West', '2nd Year', '08:00:00', '10:00:00', 'Thursday', 'Mr.Danilo Villarino', NULL),
('BSIT', 39, 'IT-LEC2', NULL, NULL, 'Program Logic Formulation', 'West', '1st Year', '09:00:00', '11:00:00', 'Friday', 'Mr.Danilo Villarino', NULL),
('BSIT', 40, 'IT-LEC2', NULL, NULL, 'ITE PROF ELECT 4', 'West', '4th Year', '13:00:00', '15:00:00', 'Friday', 'Mr.Alvin Billiones', NULL),
('BSIT', 41, 'IT-LEC2', NULL, NULL, 'Platform Technologies', 'West', '2nd Year', '16:00:00', '17:30:00', 'Friday', 'Mr.Danilo Villarino', NULL),
('BSIT', 42, 'IT-LEC2', NULL, NULL, 'Program Logic Formulation', 'West', '1st Year', '22:10:00', '23:47:00', 'Wednesday', 'Mr.Kurt Alegre', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `schedule_swaps`
--
-- Creation: Oct 23, 2025 at 11:01 AM
-- Last update: Oct 23, 2025 at 11:01 AM
-- Last check: Oct 23, 2025 at 11:01 AM
--

CREATE TABLE `schedule_swaps` (
  `id` int(11) NOT NULL,
  `original_schedule_id` int(11) NOT NULL,
  `temporary_schedule_id` int(11) NOT NULL,
  `instructor_id` int(11) NOT NULL,
  `swapped_instructor_id` int(11) DEFAULT NULL,
  `instructor_name` varchar(255) NOT NULL,
  `swapped_instructor_name` varchar(255) DEFAULT NULL,
  `room_name` varchar(255) NOT NULL,
  `swapped_room_name` varchar(255) DEFAULT NULL,
  `subject` varchar(255) NOT NULL,
  `swapped_subject` varchar(255) DEFAULT NULL,
  `day` varchar(50) NOT NULL,
  `swapped_day` varchar(50) DEFAULT NULL,
  `start_time` time NOT NULL,
  `swapped_start_time` time DEFAULT NULL,
  `end_time` time NOT NULL,
  `swapped_end_time` time DEFAULT NULL,
  `swap_date` date NOT NULL,
  `expires_at` datetime NOT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------

--
-- Table structure for table `security_logs`
--
-- Creation: Oct 11, 2025 at 02:30 PM
--

CREATE TABLE `security_logs` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `event_type` varchar(255) NOT NULL,
  `ip_address` varchar(45) NOT NULL,
  `user_agent` text DEFAULT NULL,
  `timestamp` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `stranger_logs`
--
-- Creation: Oct 11, 2025 at 02:30 PM
--

CREATE TABLE `stranger_logs` (
  `id` int(11) NOT NULL,
  `attempts` int(11) NOT NULL,
  `last_log` varchar(255) DEFAULT NULL,
  `rfid_number` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `stranger_logs`
--

INSERT INTO `stranger_logs` (`id`, `attempts`, `last_log`, `rfid_number`) VALUES
(1, 4, '2024-11-20', '123'),
(2, 1, '2024-10-18', 'dfdfdfd'),
(3, 1, '2024-10-18', 'kadmas'),
(4, 1, '2024-10-20', '0009693526	'),
(5, 1, '2024-11-29', '0009646720'),
(6, 1, '2024-12-11', '0009669869	'),
(7, 1, '2024-12-11', '245635678956'),
(8, 1, '2024-12-11', '2545667747'),
(9, 1, '2024-12-11', '3566799'),
(10, 1, '2024-12-13', '00096935250009693525'),
(11, 2, '2025-09-07', '7777-7888'),
(12, 1, '2025-09-07', '5345'),
(13, 1, '2025-09-07', '213213');

-- --------------------------------------------------------

--
-- Table structure for table `students`
--
-- Creation: Oct 11, 2025 at 02:30 PM
--

CREATE TABLE `students` (
  `id` int(11) NOT NULL,
  `id_number` varchar(9) NOT NULL,
  `fullname` varchar(100) NOT NULL,
  `section` varchar(50) NOT NULL,
  `year` varchar(20) NOT NULL,
  `status` varchar(10) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `department_id` varchar(144) NOT NULL,
  `photo` varchar(25) NOT NULL,
  `date_added` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `students`
--

INSERT INTO `students` (`id`, `id_number`, `fullname`, `section`, `year`, `status`, `created_at`, `updated_at`, `department_id`, `photo`, `date_added`) VALUES
(75, '2024-1570', 'John Cyrus Pescante', 'West', '3rd Year', '', '2025-09-03 03:40:12', '2025-10-29 01:28:26', '33', '../uploads/students/68f1b', '2025-09-03 03:40:12'),
(77, '2024-1697', 'Rose Ann V. Forrosuelo', 'West', '1st Year', '', '2025-09-03 03:41:47', '2025-10-17 03:44:24', '33', '68f1bb988b0d0.jpg', '2025-09-03 03:41:47'),
(78, '0000-0001', 'Taee Ka', 'West', '3rd Year', '', '2025-09-10 01:45:14', '2025-10-26 00:15:46', '33', '68fd683225f58.png', '2025-09-10 01:45:14'),
(79, '1212-1111', 'Try', 'West', '1st Year', '', '2025-09-27 07:33:37', '2025-10-14 04:42:34', '33', '68edd4ba922f8.jpg', '2025-09-27 07:33:37'),
(80, '0004-0001', 'Angelo Derder', 'West', '4th Year', '', '2025-10-14 04:36:10', '2025-10-14 04:36:10', '33', '68edd33ac8682.png', '2025-10-14 04:36:10'),
(81, '2024-0380', 'Nino Mike S. Zaspa', 'West', '4th Year', '', '2025-10-14 04:37:29', '2025-10-14 04:37:29', '33', '68edd389ed91c.png', '2025-10-14 04:37:29'),
(82, '0001-0002', 'Tryyy', 'West', '2nd Year', '', '2025-10-17 03:43:07', '2025-10-25 23:44:53', '33', '68fd60f535d4e.png', '2025-10-17 03:43:07'),
(83, '1111-1111', 'ryyyyyy', 'West', '4th Year', '', '2025-10-19 00:24:06', '2025-10-19 00:24:06', '33', '68f42fa6ea7cc.png', '2025-10-19 00:24:06'),
(84, '4444-4444', 'Aian Gwapo', 'North', '2nd Year', '', '2025-10-19 14:11:23', '2025-10-19 14:11:23', '33', '68f4f18b3f6cc.png', '2025-10-19 14:11:23'),
(85, '3333-3333', 'Third Year', 'West', '3rd Year', '', '2025-10-23 03:02:15', '2025-10-23 03:02:15', '33', '68f99ab7755e8.png', '2025-10-23 03:02:15'),
(86, '0000-0029', 'Hay Nakuu', 'North', '3rd Year', '', '2025-10-26 08:17:42', '2025-10-29 06:49:57', '33', '6901b915ba2a3.png', '2025-10-26 08:17:42');

-- --------------------------------------------------------

--
-- Table structure for table `students_attendance_logs`
--
-- Creation: Oct 29, 2025 at 02:04 PM
--

CREATE TABLE `students_attendance_logs` (
  `id` int(11) NOT NULL,
  `student_id` varchar(20) NOT NULL,
  `full_name` varchar(255) NOT NULL,
  `year_level` varchar(50) DEFAULT NULL,
  `section` varchar(50) DEFAULT NULL,
  `department` varchar(100) NOT NULL,
  `location` varchar(100) NOT NULL,
  `subject_name` varchar(255) DEFAULT NULL,
  `instructor_name` varchar(255) DEFAULT NULL,
  `date` date NOT NULL,
  `time_in` time NOT NULL,
  `time_out` time DEFAULT NULL,
  `total_hours` decimal(5,2) DEFAULT NULL,
  `status` enum('present','absent','late','excused') DEFAULT 'present',
  `late_minutes` int(11) DEFAULT 0,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `students_glogs`
--
-- Creation: Oct 11, 2025 at 02:30 PM
--

CREATE TABLE `students_glogs` (
  `id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `id_number` varchar(50) NOT NULL,
  `name` varchar(255) NOT NULL,
  `action` enum('IN','OUT') NOT NULL,
  `time_in` time NOT NULL,
  `time_out` time NOT NULL,
  `date` date NOT NULL,
  `period` enum('AM','PM') NOT NULL,
  `location` varchar(100) NOT NULL,
  `department` varchar(100) NOT NULL,
  `photo` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `date_logged` date NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `students_glogs`
--

INSERT INTO `students_glogs` (`id`, `student_id`, `id_number`, `name`, `action`, `time_in`, `time_out`, `date`, `period`, `location`, `department`, `photo`, `created_at`, `date_logged`) VALUES
(39, 77, '2024-1697', 'Rose Ann V. Forrosuelo', 'IN', '18:22:44', '00:00:00', '2025-10-25', 'PM', 'Gate', 'Main', NULL, '2025-10-25 10:22:44', '2025-10-25'),
(40, 81, '2024-0380', 'Nino Mike S. Zaspa', 'IN', '18:29:54', '00:00:00', '2025-10-25', 'PM', 'Gate', 'Main', NULL, '2025-10-25 10:29:54', '2025-10-25'),
(41, 83, '1111-1111', 'ryyyyyy', 'OUT', '19:41:34', '19:51:40', '2025-10-25', 'PM', 'Gate', 'Main', NULL, '2025-10-25 11:41:34', '2025-10-25'),
(42, 77, '2024-1697', 'Rose Ann V. Forrosuelo', 'OUT', '05:21:21', '09:43:58', '2025-10-26', 'AM', 'Gate', 'Main', NULL, '2025-10-25 21:21:21', '2025-10-26'),
(43, 81, '2024-0380', 'Nino Mike S. Zaspa', 'IN', '05:46:20', '00:00:00', '2025-10-26', 'AM', 'Gate', 'Main', NULL, '2025-10-25 21:46:20', '2025-10-26'),
(44, 75, '2024-1570', 'John Cyrus Pescante', 'OUT', '12:57:04', '15:25:15', '2025-10-26', 'PM', 'Gate', 'Main', NULL, '2025-10-26 04:57:04', '2025-10-26'),
(45, 85, '3333-3333', 'Third Year', 'OUT', '13:47:56', '13:58:06', '2025-10-26', 'PM', 'Gate', 'Main', NULL, '2025-10-26 05:47:56', '2025-10-26'),
(46, 83, '1111-1111', 'ryyyyyy', 'IN', '14:40:34', '00:00:00', '2025-10-26', 'PM', 'Gate', 'Main', NULL, '2025-10-26 06:40:34', '2025-10-26'),
(47, 79, '1212-1111', 'Try', 'IN', '15:35:05', '00:00:00', '2025-10-26', 'PM', 'Gate', 'Main', NULL, '2025-10-26 07:35:05', '2025-10-26'),
(48, 78, '0000-0001', 'Taee Ka', 'IN', '16:42:48', '00:00:00', '2025-10-26', 'PM', 'Gate', 'Main', NULL, '2025-10-26 08:42:48', '2025-10-26'),
(49, 81, '2024-0380', 'Nino Mike S. Zaspa', 'IN', '21:10:42', '00:00:00', '2025-10-28', 'PM', 'Gate', 'Main', NULL, '2025-10-28 13:10:42', '2025-10-28'),
(50, 80, '0004-0001', 'Angelo Derder', 'IN', '21:11:12', '00:00:00', '2025-10-28', 'PM', 'Gate', 'Main', NULL, '2025-10-28 13:11:12', '2025-10-28'),
(51, 75, '2024-1570', 'John Cyrus Pescante', 'IN', '16:20:28', '00:00:00', '2025-11-02', 'PM', 'Gate', 'Main', NULL, '2025-11-02 08:20:28', '2025-11-02'),
(52, 85, '3333-3333', 'Third Year', 'IN', '16:21:10', '00:00:00', '2025-11-02', 'PM', 'Gate', 'Main', NULL, '2025-11-02 08:21:10', '2025-11-02'),
(53, 83, '1111-1111', 'ryyyyyy', 'IN', '16:22:14', '00:00:00', '2025-11-02', 'PM', 'Gate', 'Main', NULL, '2025-11-02 08:22:14', '2025-11-02'),
(54, 79, '1212-1111', 'Try', 'IN', '16:22:31', '00:00:00', '2025-11-02', 'PM', 'Gate', 'Main', NULL, '2025-11-02 08:22:31', '2025-11-02'),
(55, 77, '2024-1697', 'Rose Ann V. Forrosuelo', 'IN', '06:45:23', '00:00:00', '2025-11-03', 'AM', 'Gate', 'Main', NULL, '2025-11-02 22:45:23', '2025-11-03'),
(56, 79, '1212-1111', 'Try', 'IN', '06:46:10', '00:00:00', '2025-11-03', 'AM', 'Gate', 'Main', NULL, '2025-11-02 22:46:10', '2025-11-03'),
(57, 81, '2024-0380', 'Nino Mike S. Zaspa', 'OUT', '06:51:18', '07:41:01', '2025-11-03', 'AM', 'Gate', 'Main', NULL, '2025-11-02 22:51:18', '2025-11-03'),
(58, 82, '0001-0002', 'Tryyy', 'IN', '06:56:33', '00:00:00', '2025-11-03', 'AM', 'Gate', 'Main', NULL, '2025-11-02 22:56:33', '2025-11-03');

-- --------------------------------------------------------

--
-- Table structure for table `stu_attendance_logs`
--
-- Creation: Oct 29, 2025 at 02:07 PM
--

CREATE TABLE `stu_attendance_logs` (
  `id` int(11) NOT NULL,
  `student_id` varchar(20) NOT NULL,
  `full_name` varchar(255) NOT NULL,
  `year_level` varchar(50) DEFAULT NULL,
  `section` varchar(50) DEFAULT NULL,
  `department` varchar(100) NOT NULL,
  `location` varchar(100) NOT NULL,
  `subject_name` varchar(255) DEFAULT NULL,
  `instructor_name` varchar(255) DEFAULT NULL,
  `date` date NOT NULL,
  `time_in` time NOT NULL,
  `time_out` time DEFAULT NULL,
  `total_hours` decimal(5,2) DEFAULT NULL,
  `status` enum('present','absent','late','excused') DEFAULT 'present',
  `late_minutes` int(11) DEFAULT 0,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `stu_attendance_logs`
--

INSERT INTO `stu_attendance_logs` (`id`, `student_id`, `full_name`, `year_level`, `section`, `department`, `location`, `subject_name`, `instructor_name`, `date`, `time_in`, `time_out`, `total_hours`, `status`, `late_minutes`, `notes`, `created_at`, `updated_at`) VALUES
(1, '1212-1111', 'Unknown Student', NULL, NULL, 'BSIT', 'IT-LEC2', NULL, NULL, '2025-10-29', '22:13:11', '22:13:54', NULL, 'present', 0, NULL, '2025-10-29 14:13:11', '2025-10-29 14:13:54');

-- --------------------------------------------------------

--
-- Table structure for table `subjects`
--
-- Creation: Oct 11, 2025 at 02:30 PM
--

CREATE TABLE `subjects` (
  `id` int(11) NOT NULL,
  `subject_code` varchar(50) NOT NULL,
  `subject_name` varchar(255) NOT NULL,
  `year_level` varchar(20) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `subjects`
--

INSERT INTO `subjects` (`id`, `subject_code`, `subject_name`, `year_level`) VALUES
(1, '111', 'SUB1', '1st Year'),
(2, '444', 'SUB4-01', '4th Year'),
(3, '333', 'SUB3-01', '3rd Year'),
(4, '222', 'SUB2-01', '2nd Year'),
(5, 'PE 1', 'Movement Enhancement', '1st Year'),
(6, 'NSTP 1', 'National Service Training Program 1', '1st Year'),
(7, 'FIL110', 'Komunikasyon sa Akademikong Filipino', '1st Year'),
(8, 'GE ELECT1', 'People and The Earths Ecosystem', '1st Year'),
(9, 'MATH 110', 'Mathematics in the Modern Science', '1st Year'),
(10, 'LIT 110', 'Contemporary World', '1st Year'),
(11, 'ITE112', 'Program Logic Formulation', '1st Year'),
(12, 'ITE111', 'Computer Assembly, Trblshting & Repair', '1st Year'),
(13, 'ITE113', 'Introduction to Computing', '1st Year'),
(14, 'BRIDGING1', 'Precalculus', '1st Year'),
(15, 'BRIDGING2', 'General Biology', '1st Year'),
(16, 'PATHFit3', 'Physical Activity towards Health and Fitness', '2nd Year'),
(17, 'GE ELECT2', 'Philippine Popular Culture', '2nd Year'),
(18, 'IT ELECT1', 'Fundamentals of Accounting', '2nd Year'),
(19, 'ITE215', 'Platform Technologies', '2nd Year'),
(20, 'ITE214', 'Digital Logic Design (Workshop 1)', '2nd Year'),
(21, 'ITE212', 'Graphic Designing', '2nd Year'),
(22, 'ITE211', 'Computer Programming 2', '2nd Year'),
(23, 'ITE213', 'Information Management', '2nd Year'),
(24, 'ITE311', 'Information Management', '3rd Year'),
(25, 'ITE310', 'Platform Technologies', '3rd Year'),
(26, 'ITE312', 'System Integration and Architecture', '3rd Year'),
(27, 'MATH310', 'Linear Algebra', '3rd Year'),
(28, 'AH310', 'Philippine Popular Culture', '3rd Year'),
(29, 'ENGL310', 'Speech Improvement', '3rd Year'),
(30, 'ITE410', 'ITE PROF ELECT 4', '4th Year'),
(31, 'ITE411', 'Information Assurance and Security 2', '4th Year'),
(32, 'ITE412', 'Capstone Project 2', '4th Year');

-- --------------------------------------------------------

--
-- Table structure for table `subjects_backup`
--
-- Creation: Oct 11, 2025 at 02:30 PM
--

CREATE TABLE `subjects_backup` (
  `id` int(11) NOT NULL DEFAULT 0,
  `subject_code` varchar(50) NOT NULL,
  `subject_name` varchar(255) NOT NULL,
  `year_level` varchar(20) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `subjects_backup`
--

INSERT INTO `subjects_backup` (`id`, `subject_code`, `subject_name`, `year_level`) VALUES
(1, '111', 'SUB1', '1st Year'),
(2, '444', 'SUB4-01', '4th Year'),
(3, '333', 'SUB3-01', '3rd Year'),
(4, '222', 'SUB2-01', '2nd Year'),
(5, 'PE 1', 'Movement Enhancement', '1st Year'),
(6, 'NSTP 1', 'National Service Training Program 1', '1st Year'),
(7, 'FIL110', 'Komunikasyon sa Akademikong Filipino', '1st Year'),
(8, 'GE ELECT1', 'People and The Earths Ecosystem', '1st Year'),
(9, 'MATH 110', 'Mathematics in the Modern Science', '1st Year'),
(10, 'LIT 110', 'Contemporary World', '1st Year'),
(11, 'ITE112', 'Program Logic Formulation', '1st Year'),
(12, 'ITE111', 'Computer Assembly, Trblshting & Repair', '1st Year'),
(13, 'ITE113', 'Introduction to Computing', '1st Year'),
(14, 'BRIDGING1', 'Precalculus', '1st Year'),
(15, 'BRIDGING2', 'General Biology', '1st Year'),
(16, 'PATHFit3', 'Physical Activity towards Health and Fitness', '2nd Year'),
(17, 'GE ELECT2', 'Philippine Popular Culture', '2nd Year'),
(18, 'IT ELECT1', 'Fundamentals of Accounting', '2nd Year'),
(19, 'ITE215', 'Platform Technologies', '2nd Year'),
(20, 'ITE214', 'Digital Logic Design (Workshop 1)', '2nd Year'),
(21, 'ITE212', 'Graphic Designing', '2nd Year'),
(22, 'ITE211', 'Computer Programming 2', '2nd Year'),
(23, 'ITE213', 'Information Management', '2nd Year'),
(24, 'ITE311', 'Information Management', '3rd Year'),
(25, 'ITE310', 'Platform Technologies', '3rd Year'),
(26, 'ITE312', 'System Integration and Architecture', '3rd Year'),
(27, 'MATH310', 'Linear Algebra', '3rd Year'),
(28, 'AH310', 'Philippine Popular Culture', '3rd Year'),
(29, 'ENGL310', 'Speech Improvement', '3rd Year'),
(30, 'ITE410', 'ITE PROF ELECT 4', '4th Year'),
(31, 'ITE411', 'Information Assurance and Security 2', '4th Year'),
(32, 'ITE412', 'Capstone Project 2', '4th Year');

-- --------------------------------------------------------

--
-- Table structure for table `swap_requests`
--
-- Creation: Oct 11, 2025 at 02:30 PM
--

CREATE TABLE `swap_requests` (
  `id` int(11) NOT NULL,
  `requester_id` varchar(20) NOT NULL,
  `target_id` varchar(20) NOT NULL,
  `room` varchar(50) NOT NULL,
  `department` varchar(50) NOT NULL,
  `subject` varchar(100) DEFAULT NULL,
  `request_date` datetime NOT NULL DEFAULT current_timestamp(),
  `response_date` datetime DEFAULT NULL,
  `status` enum('pending','approved','rejected','cancelled') NOT NULL DEFAULT 'pending',
  `notes` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `user`
--
-- Creation: Oct 11, 2025 at 02:30 PM
--

CREATE TABLE `user` (
  `id` int(11) NOT NULL,
  `contact` varchar(15) NOT NULL,
  `email` varchar(255) NOT NULL,
  `username` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `rfid_number` varchar(50) NOT NULL,
  `failed_attempts` int(11) DEFAULT 0,
  `last_attempt` timestamp NULL DEFAULT NULL,
  `last_login` timestamp NULL DEFAULT NULL,
  `login_count` int(11) DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user`
--

INSERT INTO `user` (`id`, `contact`, `email`, `username`, `password`, `rfid_number`, `failed_attempts`, `last_attempt`, `last_login`, `login_count`, `is_active`, `created_at`) VALUES
(69, '09954716547', 'joshuapastorpide10@gmail.com', 'wawa123', '$2y$10$cTmVsF3of2sXFGMJapEy.eIJqW6vvMcMBF6turmInapCbdI99v8OO', '', 0, NULL, NULL, 0, 1, '2025-10-09 22:40:36'),
(2025, '09954716547', 'joshuapastorpide10@gmail.com', 'joshu@', '$2y$10$oB2ziqgEFL8mAn/y.y4cpuX/h4sV.K7vGiLGxmkBUzIzjnVCJhLoG', '', 0, NULL, NULL, 0, 1, '2025-10-09 18:48:27');

-- --------------------------------------------------------

--
-- Table structure for table `visitor`
--
-- Creation: Oct 11, 2025 at 02:30 PM
--

CREATE TABLE `visitor` (
  `id` int(11) NOT NULL,
  `name` varchar(50) NOT NULL,
  `department` varchar(50) NOT NULL,
  `contact_number` varchar(100) NOT NULL,
  `address` varchar(100) NOT NULL,
  `purpose` varchar(225) NOT NULL,
  `sex` varchar(11) NOT NULL,
  `photo` varchar(225) NOT NULL,
  `rfid_number` varchar(100) NOT NULL,
  `v_code` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `visitor`
--

INSERT INTO `visitor` (`id`, `name`, `department`, `contact_number`, `address`, `purpose`, `sex`, `photo`, `rfid_number`, `v_code`) VALUES
(129, '', '', '', '', '', '', '', '1010-1010', ''),
(130, '', '', '', '', '', '', '', '1234-1234', ''),
(131, '', '', '', '', '', '', '', '9090-9090', '');

-- --------------------------------------------------------

--
-- Table structure for table `visitor_glogs`
--
-- Creation: Oct 11, 2025 at 02:30 PM
--

CREATE TABLE `visitor_glogs` (
  `id` int(11) NOT NULL,
  `visitor_id` int(11) NOT NULL,
  `id_number` varchar(50) NOT NULL,
  `name` varchar(255) NOT NULL,
  `action` enum('IN','OUT') NOT NULL,
  `time` time NOT NULL,
  `date` date NOT NULL,
  `period` enum('AM','PM') NOT NULL,
  `location` varchar(100) NOT NULL,
  `department` varchar(100) NOT NULL,
  `photo` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `date_logged` date NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `visitor_logs`
--
-- Creation: Oct 25, 2025 at 10:26 PM
-- Last update: Nov 02, 2025 at 11:08 PM
--

CREATE TABLE `visitor_logs` (
  `id` int(11) NOT NULL,
  `visitor_id` varchar(20) NOT NULL,
  `full_name` varchar(255) NOT NULL,
  `contact_number` varchar(50) NOT NULL,
  `purpose` varchar(255) NOT NULL,
  `person_visiting` varchar(255) DEFAULT NULL,
  `department` varchar(100) NOT NULL,
  `location` varchar(100) NOT NULL,
  `time_in` datetime NOT NULL,
  `time_out` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `visitor_logs`
--

INSERT INTO `visitor_logs` (`id`, `visitor_id`, `full_name`, `contact_number`, `purpose`, `person_visiting`, `department`, `location`, `time_in`, `time_out`, `created_at`) VALUES
(1, '1010-1010', 'joshua pastorpide', '09485757555', 'Interview', 'Dino', 'Main', 'Gate', '2025-10-19 03:48:00', NULL, '2025-10-19 10:48:00'),
(2, '1234-1234', 'Aian Desucatan', '09575575777', 'Delivery', 'Doc. Flor', 'Main', 'Gate', '2025-10-19 03:55:45', NULL, '2025-10-19 10:55:45'),
(3, '1010-1010', 'Aian Desucatan', '09575575777', 'Delivery', 'Doc. Flor', 'Main', 'Gate', '2025-10-20 01:42:31', NULL, '2025-10-20 08:42:31'),
(5, '1234-1234', 'Aian Desucatan', '09575575777', 'Maintenance', 'Dino', 'Main', 'Gate', '2025-10-22 00:05:34', '2025-11-03 03:58:53', '2025-10-22 07:05:34'),
(16, '1010-1010', 'Aian Veneracion', '09847541564', 'Meeting', 'Nino Mike', 'Main', 'Gate', '2025-11-02 15:08:54', NULL, '2025-11-02 23:08:54'),
(10, '1010-1010', 'Aian Desucatan', '09575575777', 'Gisugo Hatud Sigarilyo hihi', 'Doc. Flor', 'Main', 'Gate', '2025-10-26 01:22:25', '2025-10-28 23:51:30', '2025-10-26 08:22:25'),
(8, '1234-1234', 'Aian Desucatan', '09575575777', 'Delivery', 'Dino', 'Main', 'Gate', '2025-10-25 00:45:18', '2025-10-26 06:48:25', '2025-10-25 07:45:18'),
(11, '1010-1010', 'Aian Desucatan', '09575575777', 'Gisugo Hatud Sigarilyo hihi', 'joshua', 'Main', 'Gate', '2025-10-29 04:06:44', '2025-10-29 05:17:35', '2025-10-29 11:06:44'),
(12, '1234-1234', 'Nino Mike Zaspa', '09575575777', 'Sugo sang Classmate', 'Classmate', 'Main', 'Gate', '2025-10-29 04:56:17', '2025-10-29 05:05:26', '2025-10-29 11:56:17'),
(13, '1010-1010', 'joshua pastorpide', '09485757555', 'Delivery', 'Dino', 'Main', 'Gate', '2025-10-29 05:23:37', '2025-10-29 05:23:52', '2025-10-29 12:23:37'),
(14, '1234-1234', 'Aswang Kooo', '09343232333', 'Delivery', 'Dino', 'Main', 'Gate', '2025-10-29 05:53:58', '2025-10-29 05:56:12', '2025-10-29 12:53:58'),
(15, '1010-1010', 'Aswang Kooo', '09485757555', 'Sugo sang Classmate', 'Classmate', 'Main', 'Gate', '2025-11-02 01:21:48', '2025-11-03 03:58:29', '2025-11-02 08:21:48');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `about`
--
ALTER TABLE `about`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `access_rules`
--
ALTER TABLE `access_rules`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_rule_type` (`rule_type`),
  ADD KEY `idx_is_active` (`is_active`);

--
-- Indexes for table `admin_2fa_codes`
--
ALTER TABLE `admin_2fa_codes`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `admin_logs`
--
ALTER TABLE `admin_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `admin_id` (`admin_id`);

--
-- Indexes for table `admin_sessions`
--
ALTER TABLE `admin_sessions`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `archived_attendance_20250727_190813`
--
ALTER TABLE `archived_attendance_20250727_190813`
  ADD PRIMARY KEY (`id`),
  ADD KEY `student_id` (`student_id`);

--
-- Indexes for table `archived_attendance_logs`
--
ALTER TABLE `archived_attendance_logs`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `archived_instructor_logs`
--
ALTER TABLE `archived_instructor_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `instructor_id` (`instructor_id`);

--
-- Indexes for table `attendance_logs`
--
ALTER TABLE `attendance_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `student_id` (`student_id`);

--
-- Indexes for table `department`
--
ALTER TABLE `department`
  ADD PRIMARY KEY (`department_id`);

--
-- Indexes for table `gate_alerts`
--
ALTER TABLE `gate_alerts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_alert_type` (`alert_type`),
  ADD KEY `idx_resolved` (`resolved`),
  ADD KEY `idx_created_at` (`created_at`);

--
-- Indexes for table `gate_config`
--
ALTER TABLE `gate_config`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `config_key` (`config_key`);

--
-- Indexes for table `gate_devices`
--
ALTER TABLE `gate_devices`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_device_location` (`device_location`);

--
-- Indexes for table `gate_logs`
--
ALTER TABLE `gate_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_gate_logs_comprehensive` (`person_type`,`person_id`,`created_at`);

--
-- Indexes for table `gate_statistics`
--
ALTER TABLE `gate_statistics`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `stat_date` (`stat_date`),
  ADD KEY `idx_stat_date` (`stat_date`);

--
-- Indexes for table `gate_stats`
--
ALTER TABLE `gate_stats`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_stat` (`stat_date`,`department`,`location`);

--
-- Indexes for table `holidays`
--
ALTER TABLE `holidays`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_date` (`date`);

--
-- Indexes for table `instructor`
--
ALTER TABLE `instructor`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `instructor_accounts`
--
ALTER TABLE `instructor_accounts`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD KEY `instructor_id` (`instructor_id`);

--
-- Indexes for table `instructor_attendance`
--
ALTER TABLE `instructor_attendance`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `instructor_attendance_summary`
--
ALTER TABLE `instructor_attendance_summary`
  ADD PRIMARY KEY (`id`),
  ADD KEY `instructor_id` (`instructor_id`);

--
-- Indexes for table `instructor_glogs`
--
ALTER TABLE `instructor_glogs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_instructor_id` (`instructor_id`),
  ADD KEY `idx_id_number` (`id_number`),
  ADD KEY `idx_date` (`date`),
  ADD KEY `idx_action` (`action`),
  ADD KEY `idx_period` (`period`),
  ADD KEY `idx_instructor_glogs` (`instructor_id`,`date_logged`);

--
-- Indexes for table `instructor_logs`
--
ALTER TABLE `instructor_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `instructor_id` (`instructor_id`);

--
-- Indexes for table `login_attempts`
--
ALTER TABLE `login_attempts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_ip_time` (`ip_address`,`attempted_at`);

--
-- Indexes for table `lostcard`
--
ALTER TABLE `lostcard`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `lost_found`
--
ALTER TABLE `lost_found`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `password_reset_tokens`
--
ALTER TABLE `password_reset_tokens`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `token` (`token`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `idx_token` (`token`),
  ADD KEY `idx_expires` (`expires_at`);

--
-- Indexes for table `personell`
--
ALTER TABLE `personell`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `id_number` (`id_number`);

--
-- Indexes for table `personell_glogs`
--
ALTER TABLE `personell_glogs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `personell_id` (`personell_id`);

--
-- Indexes for table `role`
--
ALTER TABLE `role`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `rooms`
--
ALTER TABLE `rooms`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `room_logs`
--
ALTER TABLE `room_logs`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `room_schedules`
--
ALTER TABLE `room_schedules`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `schedule_swaps`
--
ALTER TABLE `schedule_swaps`
  ADD PRIMARY KEY (`id`),
  ADD KEY `original_schedule_id` (`original_schedule_id`);

--
-- Indexes for table `security_logs`
--
ALTER TABLE `security_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_ip_time` (`ip_address`,`timestamp`),
  ADD KEY `idx_user_time` (`user_id`,`timestamp`);

--
-- Indexes for table `stranger_logs`
--
ALTER TABLE `stranger_logs`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `students`
--
ALTER TABLE `students`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `id_number` (`id_number`);

--
-- Indexes for table `students_attendance_logs`
--
ALTER TABLE `students_attendance_logs`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_student_daily_attendance` (`student_id`,`date`,`department`,`location`),
  ADD KEY `idx_student_id` (`student_id`),
  ADD KEY `idx_date` (`date`),
  ADD KEY `idx_department` (`department`),
  ADD KEY `idx_location` (`location`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_student_date` (`student_id`,`date`),
  ADD KEY `idx_department_date` (`department`,`date`),
  ADD KEY `idx_instructor_date` (`instructor_name`,`date`);

--
-- Indexes for table `students_glogs`
--
ALTER TABLE `students_glogs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_student_id` (`student_id`),
  ADD KEY `idx_id_number` (`id_number`),
  ADD KEY `idx_date` (`date`),
  ADD KEY `idx_action` (`action`),
  ADD KEY `idx_period` (`period`),
  ADD KEY `idx_students_glogs` (`student_id`,`date_logged`),
  ADD KEY `idx_student_glogs` (`student_id`,`date_logged`);

--
-- Indexes for table `stu_attendance_logs`
--
ALTER TABLE `stu_attendance_logs`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_student_daily_attendance` (`student_id`,`date`,`department`,`location`),
  ADD KEY `idx_student_id` (`student_id`),
  ADD KEY `idx_date` (`date`),
  ADD KEY `idx_department` (`department`),
  ADD KEY `idx_location` (`location`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_student_date` (`student_id`,`date`),
  ADD KEY `idx_department_date` (`department`,`date`),
  ADD KEY `idx_instructor_date` (`instructor_name`,`date`);

--
-- Indexes for table `subjects`
--
ALTER TABLE `subjects`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `subject_code` (`subject_code`);

--
-- Indexes for table `swap_requests`
--
ALTER TABLE `swap_requests`
  ADD PRIMARY KEY (`id`),
  ADD KEY `requester_id` (`requester_id`),
  ADD KEY `target_id` (`target_id`);

--
-- Indexes for table `user`
--
ALTER TABLE `user`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `visitor`
--
ALTER TABLE `visitor`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `visitor_glogs`
--
ALTER TABLE `visitor_glogs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_visitor_id` (`visitor_id`),
  ADD KEY `idx_id_number` (`id_number`),
  ADD KEY `idx_date` (`date`),
  ADD KEY `idx_action` (`action`),
  ADD KEY `idx_period` (`period`),
  ADD KEY `idx_visitor_glogs` (`visitor_id`,`date_logged`);

--
-- Indexes for table `visitor_logs`
--
ALTER TABLE `visitor_logs`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `about`
--
ALTER TABLE `about`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `access_rules`
--
ALTER TABLE `access_rules`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `admin_2fa_codes`
--
ALTER TABLE `admin_2fa_codes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=45;

--
-- AUTO_INCREMENT for table `admin_access_logs`
--
ALTER TABLE `admin_access_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `admin_logs`
--
ALTER TABLE `admin_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `admin_sessions`
--
ALTER TABLE `admin_sessions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=52;

--
-- AUTO_INCREMENT for table `archived_attendance_20250727_190813`
--
ALTER TABLE `archived_attendance_20250727_190813`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `archived_attendance_logs`
--
ALTER TABLE `archived_attendance_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=211;

--
-- AUTO_INCREMENT for table `archived_instructor_logs`
--
ALTER TABLE `archived_instructor_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=109;

--
-- AUTO_INCREMENT for table `attendance_logs`
--
ALTER TABLE `attendance_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=440;

--
-- AUTO_INCREMENT for table `department`
--
ALTER TABLE `department`
  MODIFY `department_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=170;

--
-- AUTO_INCREMENT for table `gate_alerts`
--
ALTER TABLE `gate_alerts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `gate_config`
--
ALTER TABLE `gate_config`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `gate_devices`
--
ALTER TABLE `gate_devices`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `gate_logs`
--
ALTER TABLE `gate_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=124;

--
-- AUTO_INCREMENT for table `gate_statistics`
--
ALTER TABLE `gate_statistics`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `gate_stats`
--
ALTER TABLE `gate_stats`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `holidays`
--
ALTER TABLE `holidays`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `instructor`
--
ALTER TABLE `instructor`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

--
-- AUTO_INCREMENT for table `instructor_accounts`
--
ALTER TABLE `instructor_accounts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `instructor_attendance`
--
ALTER TABLE `instructor_attendance`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `instructor_attendance_summary`
--
ALTER TABLE `instructor_attendance_summary`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=202;

--
-- AUTO_INCREMENT for table `instructor_glogs`
--
ALTER TABLE `instructor_glogs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=42;

--
-- AUTO_INCREMENT for table `instructor_logs`
--
ALTER TABLE `instructor_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=129;

--
-- AUTO_INCREMENT for table `login_attempts`
--
ALTER TABLE `login_attempts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `lostcard`
--
ALTER TABLE `lostcard`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=103;

--
-- AUTO_INCREMENT for table `lost_found`
--
ALTER TABLE `lost_found`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `password_reset_tokens`
--
ALTER TABLE `password_reset_tokens`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `personell`
--
ALTER TABLE `personell`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `personell_glogs`
--
ALTER TABLE `personell_glogs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `role`
--
ALTER TABLE `role`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=145;

--
-- AUTO_INCREMENT for table `rooms`
--
ALTER TABLE `rooms`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=160;

--
-- AUTO_INCREMENT for table `room_logs`
--
ALTER TABLE `room_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=91;

--
-- AUTO_INCREMENT for table `room_schedules`
--
ALTER TABLE `room_schedules`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=43;

--
-- AUTO_INCREMENT for table `schedule_swaps`
--
ALTER TABLE `schedule_swaps`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `security_logs`
--
ALTER TABLE `security_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `stranger_logs`
--
ALTER TABLE `stranger_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `students`
--
ALTER TABLE `students`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=88;

--
-- AUTO_INCREMENT for table `students_attendance_logs`
--
ALTER TABLE `students_attendance_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `students_glogs`
--
ALTER TABLE `students_glogs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=59;

--
-- AUTO_INCREMENT for table `stu_attendance_logs`
--
ALTER TABLE `stu_attendance_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `subjects`
--
ALTER TABLE `subjects`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=33;

--
-- AUTO_INCREMENT for table `swap_requests`
--
ALTER TABLE `swap_requests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `user`
--
ALTER TABLE `user`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20240333;

--
-- AUTO_INCREMENT for table `visitor`
--
ALTER TABLE `visitor`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=132;

--
-- AUTO_INCREMENT for table `visitor_glogs`
--
ALTER TABLE `visitor_glogs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `visitor_logs`
--
ALTER TABLE `visitor_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `attendance_logs`
--
ALTER TABLE `attendance_logs`
  ADD CONSTRAINT `attendance_logs_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`);

--
-- Constraints for table `instructor_accounts`
--
ALTER TABLE `instructor_accounts`
  ADD CONSTRAINT `instructor_accounts_ibfk_1` FOREIGN KEY (`instructor_id`) REFERENCES `instructor` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `instructor_attendance_summary`
--
ALTER TABLE `instructor_attendance_summary`
  ADD CONSTRAINT `instructor_attendance_summary_ibfk_1` FOREIGN KEY (`instructor_id`) REFERENCES `instructor` (`id`);

--
-- Constraints for table `instructor_logs`
--
ALTER TABLE `instructor_logs`
  ADD CONSTRAINT `instructor_logs_ibfk_1` FOREIGN KEY (`instructor_id`) REFERENCES `instructor` (`id`);

--
-- Constraints for table `password_reset_tokens`
--
ALTER TABLE `password_reset_tokens`
  ADD CONSTRAINT `password_reset_tokens_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`) ON DELETE CASCADE;
SET FOREIGN_KEY_CHECKS=1;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
