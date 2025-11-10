-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Oct 10, 2025 at 01:49 AM
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
-- Database: `gpassdb`
--

-- --------------------------------------------------------

--
-- Table structure for table `about`
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
-- Table structure for table `admin_sessions`
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

CREATE TABLE `archived_attendance_logs` (
  `id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `id_number` varchar(50) NOT NULL,
  `time_in` datetime NOT NULL,
  `time_out` datetime DEFAULT NULL,
  `department` varchar(255) NOT NULL,
  `location` varchar(255) NOT NULL,
  `instructor_id` varchar(9) NOT NULL,
  `status` varchar(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `archived_instructor_logs`
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

CREATE TABLE `attendance_logs` (
  `id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `id_number` varchar(50) NOT NULL,
  `time_in` datetime NOT NULL,
  `time_out` datetime DEFAULT NULL,
  `department` varchar(255) NOT NULL,
  `location` varchar(255) NOT NULL,
  `instructor_id` varchar(9) NOT NULL,
  `status` varchar(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `attendance_logs`
--

INSERT INTO `attendance_logs` (`id`, `student_id`, `id_number`, `time_in`, `time_out`, `department`, `location`, `instructor_id`, `status`) VALUES
(322, 75, '2024-1570', '2025-10-04 14:19:59', NULL, 'Department', 'Location', '', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `department`
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
(56, 'BSBA', 'Bachelor in Science and Business Administration'),
(60, 'BSHRM', 'Bachelor of Science in Hotel and Restaurant Manage'),
(62, 'BEED', 'Bachelor of Elementary Education'),
(63, 'BSED', 'Bachelor of Secondary Education'),
(165, 'Main', 'adada');

-- --------------------------------------------------------

--
-- Table structure for table `gate_alerts`
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

CREATE TABLE `gate_logs` (
  `id` int(11) NOT NULL,
  `person_type` enum('student','instructor','personell','visitor') NOT NULL,
  `person_id` int(11) NOT NULL,
  `id_number` varchar(50) NOT NULL,
  `name` varchar(255) NOT NULL,
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

INSERT INTO `gate_logs` (`id`, `person_type`, `person_id`, `id_number`, `name`, `action`, `time_in`, `time_out`, `date`, `location`, `date_logged`, `time_in_am`, `time_out_am`, `time_in_pm`, `time_out_pm`, `department`, `photo`, `created_at`, `direction`) VALUES
(1, 'student', 75, '2024-1570', 'John Cyrus Pescante', 'OUT', '14:28:05', '17:36:27', '2025-09-27', 'Gate', NULL, NULL, NULL, NULL, NULL, 'Main', NULL, '2025-09-27 06:28:05', 'OUT'),
(2, 'instructor', 17, '0001-0005', 'Mr.Richard Bracero', 'OUT', '14:28:09', '17:36:11', '2025-09-27', 'Gate', NULL, NULL, NULL, NULL, NULL, 'Main', NULL, '2025-09-27 06:28:09', 'OUT'),
(3, 'instructor', 13, '0001-0003', 'Mr.Danilo Villarino', 'OUT', '14:28:13', '14:44:26', '2025-09-27', 'Gate', NULL, NULL, NULL, NULL, NULL, 'Main', NULL, '2025-09-27 06:28:13', 'OUT'),
(4, 'instructor', 16, '2024-0117', 'Ms.Jessica Alcazar', 'OUT', '14:28:40', '14:28:49', '2025-09-27', 'Gate', NULL, NULL, NULL, NULL, NULL, 'Main', NULL, '2025-09-27 06:28:40', 'OUT'),
(5, 'instructor', 19, '0001-0007', 'Mr.GlennFord Buncal', 'OUT', '17:40:44', '17:47:01', '2025-09-27', 'Gate', NULL, NULL, NULL, NULL, NULL, 'Main', NULL, '2025-09-27 09:40:44', 'OUT');

-- --------------------------------------------------------

--
-- Table structure for table `gate_statistics`
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
-- Table structure for table `instructor`
--

CREATE TABLE `instructor` (
  `id` int(11) NOT NULL,
  `photo` varchar(25) NOT NULL,
  `fullname` varchar(50) NOT NULL,
  `id_number` varchar(9) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `department_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `instructor`
--

INSERT INTO `instructor` (`id`, `photo`, `fullname`, `id_number`, `created_at`, `updated_at`, `department_id`) VALUES
(11, '', 'Mr.Kurt Alegre', '0001-0001', '2025-06-28 11:52:22', '2025-07-14 22:16:12', 33),
(12, '', 'Mr.Alvin Billiones', '0001-0004', '2025-07-08 08:26:38', '2025-07-14 22:19:33', 33),
(13, '', 'Mr.Danilo Villarino', '0001-0003', '2025-07-09 00:38:05', '2025-07-14 22:18:53', 33),
(16, '', 'Ms.Jessica Alcazar', '2024-0117', '2025-07-14 22:18:35', '2025-07-14 22:47:34', 33),
(17, '', 'Mr.Richard Bracero', '0001-0005', '2025-07-14 22:20:02', '2025-07-14 22:20:02', 33),
(18, '', 'Mrs.Emily Forrosuelo', '0001-0006', '2025-07-14 22:20:39', '2025-07-14 22:20:39', 33),
(19, '', 'Mr.GlennFord Buncal', '0001-0007', '2025-07-14 22:21:14', '2025-07-14 22:21:14', 33);

-- --------------------------------------------------------

--
-- Table structure for table `instructor_accounts`
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
(1, 13, 'Danilo', '$2y$10$0oqoE/tgXBvzmz.WcZ0Dpe3E7QdrBlrAqxHIAWmXG/mqC46GVgJyO', '2025-09-08 08:26:12', '2025-09-19 05:32:50', '2025-09-19 ', 'BSIT', 'Mr. Danilo Villariono'),
(2, 16, 'jessica', '$2y$10$5WTQH1ItwPa8PT8Dq3MLPuWwkQEbfYoAK5R9wWqU2KeLsyOl/QA0i', '2025-09-11 23:12:18', '2025-09-27 08:12:10', '2025-09-27 ', 'BSIT', 'Ms.Jessica Alcazar'),
(3, 12, 'alvin', '$2y$10$bxLgIrb/Y216/EbgHWGyFuT9OBEWMwpXQ5ZrWmMrRH71fDaOsmWjq', '2025-09-11 23:36:14', '2025-09-19 05:41:19', '2025-09-19 ', '', '');

-- --------------------------------------------------------

--
-- Table structure for table `instructor_attendance`
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
-- Table structure for table `instructor_attendance_records`
--

CREATE TABLE `instructor_attendance_records` (
  `id` int(11) NOT NULL,
  `instructor_id` int(11) NOT NULL,
  `student_id_number` varchar(50) NOT NULL,
  `student_name` varchar(255) NOT NULL,
  `section` varchar(50) NOT NULL,
  `year` varchar(50) NOT NULL,
  `department` varchar(255) DEFAULT NULL,
  `status` enum('Present','Absent','Late','Excused') NOT NULL,
  `date` date NOT NULL,
  `subject_id` int(11) DEFAULT NULL,
  `room_id` int(11) DEFAULT NULL,
  `semester` enum('1st Semester','2nd Semester','Summer') DEFAULT NULL,
  `academic_year` varchar(20) DEFAULT NULL,
  `time_in` time DEFAULT NULL,
  `time_out` time DEFAULT NULL,
  `remarks` text DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `instructor_glogs`
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
(1, 16, '2024-0117', '', 'IN', '10:15:37', '00:00:00', '0000-00-00', 'AM', 'Gate', 'Main', NULL, '2025-09-22 02:15:37', '2025-09-22'),
(2, 12, '0001-0004', '', 'IN', '10:18:43', '00:00:00', '0000-00-00', 'AM', 'Gate', 'Main', NULL, '2025-09-22 02:18:43', '2025-09-22'),
(3, 13, '0001-0003', '', 'IN', '10:22:46', '00:00:00', '0000-00-00', 'AM', 'Gate', 'Main', NULL, '2025-09-22 02:22:46', '2025-09-22'),
(4, 18, '0001-0006', '', 'IN', '12:00:47', '00:00:00', '0000-00-00', 'AM', 'Gate', 'Main', NULL, '2025-09-22 04:00:47', '2025-09-22'),
(5, 17, '0001-0005', 'Mr.Richard Bracero', 'OUT', '14:28:09', '17:36:11', '2025-09-27', 'PM', 'Gate', 'Main', NULL, '2025-09-27 06:28:09', '2025-09-27'),
(6, 13, '0001-0003', 'Mr.Danilo Villarino', 'OUT', '14:28:13', '14:44:26', '2025-09-27', 'PM', 'Gate', 'Main', NULL, '2025-09-27 06:28:13', '2025-09-27'),
(7, 16, '2024-0117', 'Ms.Jessica Alcazar', 'OUT', '14:28:40', '14:28:49', '2025-09-27', 'PM', 'Gate', 'Main', NULL, '2025-09-27 06:28:40', '2025-09-27'),
(8, 19, '0001-0007', 'Mr.GlennFord Buncal', 'OUT', '17:40:44', '17:47:01', '2025-09-27', 'PM', 'Gate', 'Main', NULL, '2025-09-27 09:40:44', '2025-09-27');

-- --------------------------------------------------------

--
-- Table structure for table `instructor_logs`
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
(109, 16, '2024-0117', '2025-09-22 09:39:49', NULL, NULL, NULL, 'BSIT', 'ComLab2', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `login_attempts`
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

CREATE TABLE `personell` (
  `id` int(11) NOT NULL,
  `id_number` varchar(10) NOT NULL,
  `last_name` varchar(255) NOT NULL,
  `first_name` varchar(255) NOT NULL,
  `middle_name` varchar(255) DEFAULT NULL,
  `date_of_birth` date NOT NULL,
  `role` varchar(255) NOT NULL,
  `sex` varchar(10) NOT NULL,
  `civil_status` varchar(10) NOT NULL,
  `contact_number` varchar(11) DEFAULT NULL,
  `email_address` varchar(255) DEFAULT NULL,
  `department` varchar(255) NOT NULL,
  `section` varchar(255) NOT NULL,
  `status` varchar(255) NOT NULL,
  `complete_address` varchar(255) NOT NULL,
  `photo` varchar(255) DEFAULT NULL,
  `place_of_birth` varchar(50) NOT NULL,
  `category` varchar(255) DEFAULT NULL,
  `date_added` timestamp NULL DEFAULT current_timestamp(),
  `deleted` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `personell`
--

INSERT INTO `personell` (`id`, `id_number`, `last_name`, `first_name`, `middle_name`, `date_of_birth`, `role`, `sex`, `civil_status`, `contact_number`, `email_address`, `department`, `section`, `status`, `complete_address`, `photo`, `place_of_birth`, `category`, `date_added`, `deleted`) VALUES
(68, '12121212', 'Tuff', 'Ace', NULL, '1991-01-30', 'Security Personnel', '', '', NULL, NULL, 'BSIT', '', 'Active', '', '68cc082d524a5.png', '', 'Regular', '2025-09-18 13:25:01', 0),
(69, '11111111', 'Bantay', 'Tig', NULL, '1986-03-20', 'Security Personnel', '', '', NULL, NULL, 'BSIT', '', 'Active', '', '68c37551339fa.png', '', 'Regular', '2025-09-12 01:20:17', 0);

-- --------------------------------------------------------

--
-- Table structure for table `personell_glogs`
--

CREATE TABLE `personell_glogs` (
  `id` int(11) NOT NULL,
  `personnel_id` int(11) NOT NULL,
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
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `personell_logs`
--

CREATE TABLE `personell_logs` (
  `id` int(11) NOT NULL,
  `photo` varchar(255) NOT NULL,
  `rfid_number` varchar(50) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `time_in_am` varchar(100) NOT NULL,
  `time_out_am` varchar(100) NOT NULL,
  `date_logged` date NOT NULL,
  `time_in_pm` varchar(100) NOT NULL,
  `time_out_pm` varchar(100) NOT NULL,
  `role` varchar(50) NOT NULL,
  `department` varchar(100) NOT NULL,
  `status` varchar(100) NOT NULL,
  `time_in` varchar(255) DEFAULT NULL,
  `time_out` varchar(255) DEFAULT NULL,
  `location` varchar(255) DEFAULT NULL,
  `personnel_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `role`
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
(23, 'Executive'),
(31, 'Maintenance'),
(32, 'Developer'),
(37, 'Designer'),
(100, 'Service Manager'),
(113, 'Operator');

-- --------------------------------------------------------

--
-- Table structure for table `rooms`
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
(150, 'BSHM 01', 'BSHRM', 'BSHM01', NULL, 'Kitchen Lab', 'Instructor'),
(151, 'ComLab2', 'BSIT', 'comlab2', NULL, 'IT lab1', 'Instructor'),
(152, 'ComLab1', 'BSIT', 'comlab1', NULL, 'comlab1', 'Instructor'),
(153, 'Gate', 'Main', 'gate123', NULL, 'gilugewqe', 'Security Personnel'),
(154, 'ComLab3', 'BSIT', 'comlab3', NULL, 'IT lab 3', 'Instructor'),
(155, 'IT-LEC1', 'BSIT', 'itlec1', NULL, 'IT LECTURE 1', 'Instructor'),
(156, 'IT-LEC2', 'BSIT', 'itlec2', NULL, 'IT Lecture 2', 'Instructor');

-- --------------------------------------------------------

--
-- Table structure for table `rooms_backup`
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
('BSIT', 13, 'ComLab1', NULL, NULL, 'Networking', 'West', '1st', '11:31:00', '13:47:00', 'Friday', 'Mr.Danilo Villariono', 13),
('BSIT', 14, 'ComLab1', NULL, NULL, 'Web Development', 'West', '4th', '09:20:00', '11:20:00', 'Monday', 'Mr.Alvin Billiones', 12),
('BSIT', 15, 'ComLab1', NULL, NULL, 'Sub1', 'west', '4th', '09:00:00', '10:30:00', 'Tuesday', 'Ms.Jessica Alcazar', 16),
('BSIT', 16, 'ComLab1', NULL, NULL, 'Sub2', 'west', '4th', '13:00:00', '14:30:00', 'Tuesday', 'Ms.Jessica Alcazar', 16),
('BSIT', 17, 'ComLab1', NULL, NULL, 'Sub3', 'West', '4th', '15:10:00', '16:15:00', 'Tuesday', 'Ms.Jessica Alcazar', 16),
('BSIT', 18, 'ComLab1', NULL, NULL, 'Sub4', 'West', '4th', '17:20:00', '19:15:00', 'Tuesday', 'Ms.Jessica Alcazar', 16),
('BSIT', 20, 'ComLab2', NULL, NULL, 'Web', 'west', '4th', '13:40:00', '15:40:00', 'Tuesday', 'Ms.Jessica Alcazar', 16),
('BSIT', 21, 'IT-LEC1', NULL, NULL, 'Sub1', 'West', '4th', '11:40:00', '02:40:00', 'Monday', 'Mr.Kurt Alegre', 11),
('BSIT', 22, 'ComLab1', NULL, NULL, 'SUB2-01', 'West', '2nd Year', '00:00:00', '15:00:00', 'Wednesday', 'Mr.Kurt Alegre', 11),
('BSIT', 23, 'IT-LEC1', NULL, NULL, 'SUB1', 'West', '1st Year', '13:20:00', '15:20:00', 'Friday', 'Mr.Kurt Alegre', 11),
('BSIT', 24, 'ComLab2', NULL, NULL, 'SUB4-01', 'West', '4th Year', '14:00:00', '15:00:00', 'Saturday', 'Ms.Jessica Alcazar', 16),
('BSIT', 25, 'ComLab2', NULL, NULL, 'ITE PROF ELECT 4', 'East', '4th Year', '16:00:00', '17:00:00', 'Saturday', 'Mr.Alvin Billiones', 12),
('BSIT', 26, 'ComLab2', NULL, NULL, 'Philippine Popular Culture', 'West', '2nd Year', '00:17:00', '13:11:00', 'Monday', 'Mr.Alvin Billiones', NULL),
('BSIT', 27, 'ComLab1', NULL, NULL, 'Program Logic Formulation', 'West', '1st Year', '18:15:00', '19:15:00', 'Monday', 'Ms.Jessica Alcazar', NULL),
('BSIT', 29, 'ComLab3', NULL, NULL, 'Program Logic Formulation', 'West', '1st Year', '20:18:00', '23:18:00', 'Wednesday', 'Ms.Jessica Alcazar', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `security_logs`
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
(75, '2024-1570', 'John Cyrus Pescante', 'West', '1st Year', '', '2025-09-03 03:40:12', '2025-09-03 04:53:21', '33', '68b7b89cc0097_2024-1570.j', '2025-09-03 03:40:12'),
(77, '2024-1697', 'Rose Ann V. Forrosuelo', 'West', '1st Year', '', '2025-09-03 03:41:47', '2025-09-03 04:53:08', '33', '68b7b8fb407f5_2024-1697.j', '2025-09-03 03:41:47'),
(78, '0000-0001', 'Truy', 'West', '1st Year', '', '2025-09-10 01:45:14', '2025-09-10 01:45:14', '33', '68c0d82a10aff_0000-0001.p', '2025-09-10 01:45:14'),
(79, '1212-1111', 'Try', 'West', '1st Year', '', '2025-09-27 07:33:37', '2025-09-27 07:33:37', '33', '68d7935108118_1212-1111.p', '2025-09-27 07:33:37');

-- --------------------------------------------------------

--
-- Table structure for table `students_glogs`
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
(1, 75, '2024-1570', '', 'IN', '10:14:52', '00:00:00', '0000-00-00', 'AM', 'Gate', 'Main', NULL, '2025-09-22 02:14:52', '2025-09-22'),
(2, 77, '2024-1697', '', 'IN', '10:15:19', '00:00:00', '0000-00-00', 'AM', 'Gate', 'Main', NULL, '2025-09-22 02:15:19', '2025-09-22'),
(3, 78, '0000-0001', '', 'IN', '12:01:08', '00:00:00', '0000-00-00', 'AM', 'Gate', 'Main', NULL, '2025-09-22 04:01:08', '2025-09-22'),
(4, 75, '2024-1570', 'John Cyrus Pescante', 'OUT', '14:28:05', '17:36:27', '2025-09-27', 'PM', 'Gate', 'Main', NULL, '2025-09-27 06:28:05', '2025-09-27');

-- --------------------------------------------------------

--
-- Table structure for table `subjects`
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
(2, '09560379350', 'kyebejeanu@gmail.com', 'admin', '$2y$10$jcAd4HtKBXyVxRRGNf39sOmX6FzsDb4hOcu6DGRnISwPGNSs6YM4.', '1234567899', 0, NULL, NULL, 0, 1, '2025-09-24 07:52:42'),
(69, '09954716547', 'joshuapastorpide10@gmail.com', 'wawa123', '$2y$10$NW03M1sYgVI6AQMfLB1c7Oiy1GtHynjHByMrG4l5G0S/UJgj1prva', '', 0, NULL, NULL, 0, 1, '2025-10-09 22:40:36'),
(2025, '09954716547', 'joshuapastorpide10@gmail.com', 'joshu@', '$2y$10$oB2ziqgEFL8mAn/y.y4cpuX/h4sV.K7vGiLGxmkBUzIzjnVCJhLoG', '', 0, NULL, NULL, 0, 1, '2025-10-09 18:48:27');

-- --------------------------------------------------------

--
-- Table structure for table `visitor`
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

-- --------------------------------------------------------

--
-- Table structure for table `visitor_glogs`
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

CREATE TABLE `visitor_logs` (
  `id` int(11) NOT NULL,
  `photo` varchar(255) NOT NULL,
  `v_code` varchar(11) NOT NULL,
  `rfid_number` varchar(11) NOT NULL,
  `date_logged` date NOT NULL,
  `department` varchar(100) NOT NULL,
  `sex` varchar(11) NOT NULL,
  `contact_number` varchar(100) NOT NULL,
  `address` varchar(225) NOT NULL,
  `purpose` varchar(225) NOT NULL,
  `time_in_am` varchar(100) NOT NULL,
  `time_out_am` varchar(100) NOT NULL,
  `role` varchar(100) NOT NULL,
  `time_in_pm` varchar(50) NOT NULL,
  `time_out_pm` varchar(50) NOT NULL,
  `civil_status` varchar(100) NOT NULL,
  `name` varchar(100) NOT NULL,
  `time_in` varchar(255) DEFAULT NULL,
  `time_out` varchar(255) DEFAULT NULL,
  `location` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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
  ADD PRIMARY KEY (`id`),
  ADD KEY `student_id` (`student_id`);

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
-- Indexes for table `instructor_attendance_records`
--
ALTER TABLE `instructor_attendance_records`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_attendance` (`instructor_id`,`student_id_number`,`date`,`year`,`section`,`subject_id`),
  ADD KEY `idx_instructor_date` (`instructor_id`,`date`),
  ADD KEY `idx_student_date` (`student_id_number`,`date`),
  ADD KEY `idx_section_year` (`section`,`year`),
  ADD KEY `subject_id` (`subject_id`),
  ADD KEY `room_id` (`room_id`);

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
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `personell_glogs`
--
ALTER TABLE `personell_glogs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_personnel_id` (`personnel_id`),
  ADD KEY `idx_id_number` (`id_number`),
  ADD KEY `idx_date` (`date`),
  ADD KEY `idx_action` (`action`),
  ADD KEY `idx_period` (`period`);

--
-- Indexes for table `personell_logs`
--
ALTER TABLE `personell_logs`
  ADD PRIMARY KEY (`id`);

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=321;

--
-- AUTO_INCREMENT for table `archived_instructor_logs`
--
ALTER TABLE `archived_instructor_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=109;

--
-- AUTO_INCREMENT for table `attendance_logs`
--
ALTER TABLE `attendance_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=323;

--
-- AUTO_INCREMENT for table `department`
--
ALTER TABLE `department`
  MODIFY `department_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=169;

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `gate_statistics`
--
ALTER TABLE `gate_statistics`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `instructor`
--
ALTER TABLE `instructor`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT for table `instructor_accounts`
--
ALTER TABLE `instructor_accounts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `instructor_attendance`
--
ALTER TABLE `instructor_attendance`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `instructor_attendance_records`
--
ALTER TABLE `instructor_attendance_records`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `instructor_glogs`
--
ALTER TABLE `instructor_glogs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `instructor_logs`
--
ALTER TABLE `instructor_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=110;

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `personell`
--
ALTER TABLE `personell`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2147483648;

--
-- AUTO_INCREMENT for table `personell_glogs`
--
ALTER TABLE `personell_glogs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `personell_logs`
--
ALTER TABLE `personell_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=170;

--
-- AUTO_INCREMENT for table `role`
--
ALTER TABLE `role`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=140;

--
-- AUTO_INCREMENT for table `rooms`
--
ALTER TABLE `rooms`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=159;

--
-- AUTO_INCREMENT for table `room_logs`
--
ALTER TABLE `room_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=91;

--
-- AUTO_INCREMENT for table `room_schedules`
--
ALTER TABLE `room_schedules`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=30;

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=80;

--
-- AUTO_INCREMENT for table `students_glogs`
--
ALTER TABLE `students_glogs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=129;

--
-- AUTO_INCREMENT for table `visitor_glogs`
--
ALTER TABLE `visitor_glogs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `visitor_logs`
--
ALTER TABLE `visitor_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=114;

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
-- Constraints for table `instructor_attendance_records`
--
ALTER TABLE `instructor_attendance_records`
  ADD CONSTRAINT `instructor_attendance_records_ibfk_1` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `instructor_attendance_records_ibfk_2` FOREIGN KEY (`room_id`) REFERENCES `rooms` (`id`) ON DELETE SET NULL;

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
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
