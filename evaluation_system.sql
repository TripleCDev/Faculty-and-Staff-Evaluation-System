-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Oct 07, 2025 at 10:26 AM
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
-- Database: `evaluation_system`
--

-- --------------------------------------------------------

--
-- Table structure for table `criteria`
--

CREATE TABLE `criteria` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `type` enum('Likert','Numeric','Boolean','Essay','Frequency') NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `criteria`
--

INSERT INTO `criteria` (`id`, `name`, `type`, `created_at`) VALUES
(1, 'Likert Scale', 'Likert', '2025-10-03 14:41:17'),
(2, 'Frequency Scale', 'Frequency', '2025-10-03 14:41:17'),
(3, 'Numeric Scale', 'Numeric', '2025-10-03 14:41:17'),
(4, 'Boolean Scale', 'Boolean', '2025-10-03 14:41:17');

-- --------------------------------------------------------

--
-- Table structure for table `criteria_options`
--

CREATE TABLE `criteria_options` (
  `id` int(11) NOT NULL,
  `criteria_id` int(11) NOT NULL,
  `scale_type` enum('Likert','Numeric','Boolean','Frequency') NOT NULL,
  `option_text` varchar(255) NOT NULL,
  `option_point` decimal(10,2) DEFAULT NULL,
  `option_order` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `criteria_options`
--

INSERT INTO `criteria_options` (`id`, `criteria_id`, `scale_type`, `option_text`, `option_point`, `option_order`) VALUES
(6, 1, 'Likert', 'Excellent', 5.00, 1),
(7, 1, 'Likert', 'Very Satisfactory', 4.00, 2),
(8, 1, 'Likert', 'Satisfactory', 3.00, 3),
(9, 1, 'Likert', 'Fair', 2.00, 4),
(10, 1, 'Likert', 'Poor', 1.00, 5),
(11, 2, 'Frequency', 'Always', 4.00, 1),
(12, 2, 'Frequency', 'Often', 3.00, 2),
(13, 2, 'Frequency', 'Seldom', 2.00, 3),
(14, 2, 'Frequency', 'Never', 1.00, 4),
(15, 3, 'Numeric', '1', 1.00, 1),
(16, 3, 'Numeric', '2', 2.00, 2),
(17, 3, 'Numeric', '3', 3.00, 3),
(18, 3, 'Numeric', '4', 4.00, 4),
(19, 3, 'Numeric', '5', 5.00, 5),
(20, 4, 'Boolean', 'Yes', 1.00, 1),
(21, 4, 'Boolean', 'No', 0.00, 2);

-- --------------------------------------------------------

--
-- Table structure for table `curriculum`
--

CREATE TABLE `curriculum` (
  `curriculum_id` int(11) NOT NULL,
  `curriculum_title` varchar(100) NOT NULL,
  `curriculum_year_start` year(4) NOT NULL,
  `curriculum_year_end` year(4) NOT NULL,
  `semester` enum('1st','2nd','Summer') NOT NULL,
  `description` text DEFAULT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `date_created` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `curriculum`
--

INSERT INTO `curriculum` (`curriculum_id`, `curriculum_title`, `curriculum_year_start`, `curriculum_year_end`, `semester`, `description`, `status`, `date_created`) VALUES
(4, 'curriculum title', '2025', '2026', '1st', '', 'active', '2025-10-04 17:16:04'),
(5, 'curriculum title123', '2026', '2027', '1st', '', 'inactive', '2025-10-04 17:52:21');

-- --------------------------------------------------------

--
-- Table structure for table `departments`
--

CREATE TABLE `departments` (
  `id` int(11) NOT NULL,
  `department_name` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `departments`
--

INSERT INTO `departments` (`id`, `department_name`) VALUES
(55, 'College'),
(144, 'Human Resources');

-- --------------------------------------------------------

--
-- Table structure for table `evaluation_responses`
--

CREATE TABLE `evaluation_responses` (
  `id` int(11) NOT NULL,
  `evaluation_id` int(11) NOT NULL,
  `question_id` int(11) NOT NULL,
  `answer` varchar(255) NOT NULL,
  `evaluator_id` int(11) DEFAULT NULL,
  `evaluated_id` int(11) DEFAULT NULL,
  `questionnaire_id` int(11) DEFAULT NULL,
  `curriculum_id` int(11) DEFAULT NULL,
  `score` decimal(10,2) DEFAULT NULL,
  `comments` text DEFAULT NULL,
  `status` enum('pending','completed') DEFAULT 'pending',
  `evaluated_date` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `evaluation_responses`
--

INSERT INTO `evaluation_responses` (`id`, `evaluation_id`, `question_id`, `answer`, `evaluator_id`, `evaluated_id`, `questionnaire_id`, `curriculum_id`, `score`, `comments`, `status`, `evaluated_date`) VALUES
(266, 1, 88, 'Excellent', 236, 15100315, 33, 4, 5.00, 'test', 'completed', '2025-10-06 20:20:56'),
(267, 1, 89, 'Excellent', 236, 15100315, 33, 4, 5.00, 'test', 'completed', '2025-10-06 20:20:56'),
(268, 2, 88, 'Excellent', 231, 15100133, 33, 4, 5.00, 'RWER', 'completed', '2025-10-06 20:21:26'),
(269, 2, 89, 'Poor', 231, 15100133, 33, 4, 1.00, 'RWER', 'completed', '2025-10-06 20:21:26'),
(270, 3, 90, 'Excellent', 231, 11111, 34, 4, 5.00, 'TEST', 'completed', '2025-10-06 20:21:35'),
(271, 3, 91, 'Very Satisfactory', 231, 11111, 34, 4, 4.00, 'TEST', 'completed', '2025-10-06 20:21:35'),
(272, 3, 92, 'Very Satisfactory', 231, 11111, 34, 4, 4.00, 'TEST', 'completed', '2025-10-06 20:21:35'),
(273, 3, 93, 'Very Satisfactory', 231, 11111, 34, 4, 4.00, 'TEST', 'completed', '2025-10-06 20:21:35'),
(274, 3, 94, 'Excellent', 231, 11111, 34, 4, 5.00, 'TEST', 'completed', '2025-10-06 20:21:35'),
(275, 4, 90, 'Excellent', 231, 15100331, 34, 4, 5.00, 'TEST', 'completed', '2025-10-06 20:21:44'),
(276, 4, 91, 'Very Satisfactory', 231, 15100331, 34, 4, 4.00, 'TEST', 'completed', '2025-10-06 20:21:44'),
(277, 4, 92, 'Very Satisfactory', 231, 15100331, 34, 4, 4.00, 'TEST', 'completed', '2025-10-06 20:21:44'),
(278, 4, 93, 'Very Satisfactory', 231, 15100331, 34, 4, 4.00, 'TEST', 'completed', '2025-10-06 20:21:44'),
(279, 4, 94, 'Poor', 231, 15100331, 34, 4, 1.00, 'TEST', 'completed', '2025-10-06 20:21:44'),
(280, 5, 90, 'Poor', 231, 33333, 34, 4, 1.00, 'TEARQWERQWQW', 'completed', '2025-10-06 20:21:52'),
(281, 5, 91, 'Fair', 231, 33333, 34, 4, 2.00, 'TEARQWERQWQW', 'completed', '2025-10-06 20:21:52'),
(282, 5, 92, 'Satisfactory', 231, 33333, 34, 4, 3.00, 'TEARQWERQWQW', 'completed', '2025-10-06 20:21:52'),
(283, 5, 93, 'Very Satisfactory', 231, 33333, 34, 4, 4.00, 'TEARQWERQWQW', 'completed', '2025-10-06 20:21:52'),
(284, 5, 94, 'Excellent', 231, 33333, 34, 4, 5.00, 'TEARQWERQWQW', 'completed', '2025-10-06 20:21:52'),
(285, 6, 90, 'Poor', 231, 22222, 34, 4, 1.00, 'TEST', 'completed', '2025-10-06 20:21:59'),
(286, 6, 91, 'Poor', 231, 22222, 34, 4, 1.00, 'TEST', 'completed', '2025-10-06 20:21:59'),
(287, 6, 92, 'Poor', 231, 22222, 34, 4, 1.00, 'TEST', 'completed', '2025-10-06 20:21:59'),
(288, 6, 93, 'Poor', 231, 22222, 34, 4, 1.00, 'TEST', 'completed', '2025-10-06 20:21:59'),
(289, 6, 94, 'Poor', 231, 22222, 34, 4, 1.00, 'TEST', 'completed', '2025-10-06 20:21:59'),
(290, 7, 88, 'Excellent', 235, 12, 33, 4, 5.00, NULL, 'completed', '2025-10-06 20:27:12'),
(291, 7, 89, 'Very Satisfactory', 235, 12, 33, 4, 4.00, NULL, 'completed', '2025-10-06 20:27:12'),
(292, 8, 90, 'Excellent', 235, 11111, 34, 4, 5.00, 'test', 'completed', '2025-10-06 20:30:57'),
(293, 8, 91, 'Excellent', 235, 11111, 34, 4, 5.00, 'test', 'completed', '2025-10-06 20:30:57'),
(294, 8, 92, 'Very Satisfactory', 235, 11111, 34, 4, 4.00, 'test', 'completed', '2025-10-06 20:30:57'),
(295, 8, 93, 'Very Satisfactory', 235, 11111, 34, 4, 4.00, 'test', 'completed', '2025-10-06 20:30:57'),
(296, 8, 94, 'Very Satisfactory', 235, 11111, 34, 4, 4.00, 'test', 'completed', '2025-10-06 20:30:57'),
(297, 9, 90, 'Excellent', 235, 22222, 34, 4, 5.00, 'teqarqw', 'completed', '2025-10-06 20:31:05'),
(298, 9, 91, 'Very Satisfactory', 235, 22222, 34, 4, 4.00, 'teqarqw', 'completed', '2025-10-06 20:31:05'),
(299, 9, 92, 'Very Satisfactory', 235, 22222, 34, 4, 4.00, 'teqarqw', 'completed', '2025-10-06 20:31:05'),
(300, 9, 93, 'Excellent', 235, 22222, 34, 4, 5.00, 'teqarqw', 'completed', '2025-10-06 20:31:05'),
(301, 9, 94, 'Very Satisfactory', 235, 22222, 34, 4, 4.00, 'teqarqw', 'completed', '2025-10-06 20:31:05'),
(302, 10, 90, 'Very Satisfactory', 235, 15100331, 34, 4, 4.00, NULL, 'completed', '2025-10-06 20:31:12'),
(303, 10, 91, 'Satisfactory', 235, 15100331, 34, 4, 3.00, NULL, 'completed', '2025-10-06 20:31:12'),
(304, 10, 92, 'Fair', 235, 15100331, 34, 4, 2.00, NULL, 'completed', '2025-10-06 20:31:12'),
(305, 10, 93, 'Poor', 235, 15100331, 34, 4, 1.00, NULL, 'completed', '2025-10-06 20:31:12'),
(306, 10, 94, 'Fair', 235, 15100331, 34, 4, 2.00, NULL, 'completed', '2025-10-06 20:31:12'),
(307, 11, 88, 'Excellent', 230, 11111, 33, 4, 5.00, 'QWE', 'completed', '2025-10-06 20:42:20'),
(308, 11, 89, 'Very Satisfactory', 230, 11111, 33, 4, 4.00, 'QWE', 'completed', '2025-10-06 20:42:20'),
(309, 12, 95, 'Excellent', 230, 15100133, 35, 4, 5.00, NULL, 'completed', '2025-10-06 20:42:50'),
(310, 12, 96, 'Very Satisfactory', 230, 15100133, 35, 4, 4.00, NULL, 'completed', '2025-10-06 20:42:50'),
(311, 13, 95, 'Excellent', 230, 15100331, 35, 4, 5.00, 'QWE', 'completed', '2025-10-06 20:42:55'),
(312, 13, 96, 'Very Satisfactory', 230, 15100331, 35, 4, 4.00, 'QWE', 'completed', '2025-10-06 20:42:55'),
(313, 14, 95, 'Excellent', 230, 33333, 35, 4, 5.00, 'QWE', 'completed', '2025-10-06 20:43:00'),
(314, 14, 96, 'Poor', 230, 33333, 35, 4, 1.00, 'QWE', 'completed', '2025-10-06 20:43:00'),
(315, 15, 95, 'Poor', 230, 22222, 35, 4, 1.00, NULL, 'completed', '2025-10-06 20:43:05'),
(316, 15, 96, 'Poor', 230, 22222, 35, 4, 1.00, NULL, 'completed', '2025-10-06 20:43:05'),
(317, 16, 90, 'Excellent', 232, 11111, 34, 4, 5.00, 'QWE', 'completed', '2025-10-06 20:43:57'),
(318, 16, 91, 'Excellent', 232, 11111, 34, 4, 5.00, 'QWE', 'completed', '2025-10-06 20:43:57'),
(319, 16, 92, 'Excellent', 232, 11111, 34, 4, 5.00, 'QWE', 'completed', '2025-10-06 20:43:57'),
(320, 16, 93, 'Excellent', 232, 11111, 34, 4, 5.00, 'QWE', 'completed', '2025-10-06 20:43:57'),
(321, 16, 94, 'Excellent', 232, 11111, 34, 4, 5.00, 'QWE', 'completed', '2025-10-06 20:43:57'),
(322, 17, 88, 'Poor', 232, 22222, 33, 4, 1.00, 'W', 'completed', '2025-10-06 20:44:03'),
(323, 17, 89, 'Poor', 232, 22222, 33, 4, 1.00, 'W', 'completed', '2025-10-06 20:44:03'),
(324, 18, 90, 'Excellent', 232, 33333, 34, 4, 5.00, 'TTTEST', 'completed', '2025-10-06 20:44:12'),
(325, 18, 91, 'Very Satisfactory', 232, 33333, 34, 4, 4.00, 'TTTEST', 'completed', '2025-10-06 20:44:12'),
(326, 18, 92, 'Satisfactory', 232, 33333, 34, 4, 3.00, 'TTTEST', 'completed', '2025-10-06 20:44:12'),
(327, 18, 93, 'Fair', 232, 33333, 34, 4, 2.00, 'TTTEST', 'completed', '2025-10-06 20:44:12'),
(328, 18, 94, 'Poor', 232, 33333, 34, 4, 1.00, 'TTTEST', 'completed', '2025-10-06 20:44:12');

-- --------------------------------------------------------

--
-- Table structure for table `faculty`
--

CREATE TABLE `faculty` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `faculty_id` int(11) NOT NULL,
  `first_name` varchar(50) DEFAULT NULL,
  `middle_name` varchar(50) DEFAULT NULL,
  `last_name` varchar(50) DEFAULT NULL,
  `department_id` int(11) DEFAULT NULL,
  `program_id` int(11) DEFAULT NULL,
  `role` enum('HR','Faculty','Staff','Program Head') NOT NULL DEFAULT 'Faculty'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `faculty`
--

INSERT INTO `faculty` (`id`, `user_id`, `faculty_id`, `first_name`, `middle_name`, `last_name`, `department_id`, `program_id`, `role`) VALUES
(135, 230, 11111, 'Rogelio', '', 'Diez', 55, 1, 'Program Head'),
(136, 231, 15100133, 'Clarence', '', 'Cabero', 55, 1, 'Faculty'),
(137, 232, 22222, 'JB', '', 'Sanoria', 55, 1, 'Faculty'),
(138, 233, 33333, 'Kivron', '', 'Uy', 55, 1, 'Faculty'),
(140, 237, 15100331, 'Alexander', 'Besin', 'Fajardo', 55, 1, 'Faculty');

-- --------------------------------------------------------

--
-- Table structure for table `programs`
--

CREATE TABLE `programs` (
  `program_id` int(11) NOT NULL,
  `program_name` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `programs`
--

INSERT INTO `programs` (`program_id`, `program_name`) VALUES
(1, 'BSIT');

-- --------------------------------------------------------

--
-- Table structure for table `questionnaires`
--

CREATE TABLE `questionnaires` (
  `id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `category` varchar(100) DEFAULT NULL,
  `criteria_id` int(11) DEFAULT NULL,
  `status` enum('Active','Inactive') DEFAULT 'Active',
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `weight_percentage` decimal(5,2) NOT NULL DEFAULT 100.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `questionnaires`
--

INSERT INTO `questionnaires` (`id`, `title`, `description`, `category`, `criteria_id`, `status`, `created_at`, `updated_at`, `weight_percentage`) VALUES
(33, 'Self', '', 'Self Evaluation', 1, 'Active', '2025-10-06 19:15:25', '2025-10-07 01:20:12', 70.00),
(34, 'Peer to peer', '', 'Personal Characteristics and Working Attitudes', 1, 'Active', '2025-10-06 19:38:25', '2025-10-06 19:38:25', 70.00),
(35, 'Head staff to staff', '', 'test', 1, 'Active', '2025-10-06 20:21:58', '2025-10-07 01:19:48', 40.00),
(36, 'Attendance', '', '', 2, 'Active', '2025-10-07 02:25:49', '2025-10-07 02:25:49', 30.00);

-- --------------------------------------------------------

--
-- Table structure for table `questionnaire_assignments`
--

CREATE TABLE `questionnaire_assignments` (
  `id` int(11) NOT NULL,
  `questionnaire_id` int(11) NOT NULL,
  `faculty_id` int(10) UNSIGNED DEFAULT NULL,
  `staff_id` int(11) DEFAULT NULL,
  `department_id` int(11) DEFAULT NULL,
  `program_id` int(11) DEFAULT NULL,
  `curriculum_id` int(11) DEFAULT NULL,
  `assigned_at` datetime DEFAULT current_timestamp(),
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `evaluation_type` enum('Self','Peer','ProgramHeadToFaculty','Admin','Staff','HeadToStaff') DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `questionnaire_assignments`
--

INSERT INTO `questionnaire_assignments` (`id`, `questionnaire_id`, `faculty_id`, `staff_id`, `department_id`, `program_id`, `curriculum_id`, `assigned_at`, `status`, `evaluation_type`) VALUES
(112, 34, NULL, NULL, NULL, NULL, NULL, '2025-10-06 21:18:07', 'active', NULL),
(113, 34, NULL, NULL, NULL, NULL, NULL, '2025-10-06 21:18:13', 'active', NULL),
(114, 34, NULL, NULL, NULL, NULL, 4, '2025-10-06 21:19:06', 'active', NULL),
(118, 34, NULL, NULL, 55, 1, NULL, '2025-10-06 21:20:35', 'active', 'Peer'),
(129, 35, NULL, NULL, NULL, 1, NULL, '2025-10-07 02:08:57', 'active', NULL),
(130, 35, NULL, NULL, 55, NULL, NULL, '2025-10-07 02:08:58', 'active', NULL),
(131, 35, NULL, NULL, NULL, NULL, 4, '2025-10-07 02:09:00', 'active', 'HeadToStaff'),
(134, 33, NULL, NULL, NULL, NULL, 4, '2025-10-07 02:20:19', 'active', 'Self'),
(135, 36, NULL, NULL, 55, 1, 4, '2025-10-07 02:26:00', 'active', 'Admin'),
(136, 35, NULL, NULL, NULL, NULL, NULL, '2025-10-07 02:42:36', 'active', 'ProgramHeadToFaculty');

-- --------------------------------------------------------

--
-- Table structure for table `questions`
--

CREATE TABLE `questions` (
  `id` int(11) NOT NULL,
  `questionnaire_id` int(11) NOT NULL,
  `question_text` text NOT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `questions`
--

INSERT INTO `questions` (`id`, `questionnaire_id`, `question_text`, `created_at`) VALUES
(88, 33, 'katigo mo sud', '2025-10-06 19:15:25'),
(89, 33, 'katigo mo kaon', '2025-10-06 19:15:25'),
(90, 34, 'Knows the details of his/her job', '2025-10-06 19:38:25'),
(91, 34, 'Knows the details of his/her related duties', '2025-10-06 19:38:25'),
(92, 34, 'Works accurately', '2025-10-06 19:38:25'),
(93, 34, 'Works completely', '2025-10-06 19:38:25'),
(94, 34, 'Puts works in an orderly manner', '2025-10-06 19:38:25'),
(95, 35, 'test', '2025-10-06 20:21:58'),
(96, 35, 'test', '2025-10-06 20:21:58'),
(97, 36, 'Monday Morning Devotion', '2025-10-07 02:25:49'),
(98, 36, 'Foundation Day, Founder’s Day, Christmas Get-together, Commencements, Retreats, F/S Day,', '2025-10-07 02:25:49'),
(99, 36, 'Convocations, etc.', '2025-10-07 02:25:49');

-- --------------------------------------------------------

--
-- Table structure for table `staff`
--

CREATE TABLE `staff` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `staff_id` int(11) NOT NULL,
  `first_name` varchar(255) NOT NULL,
  `middle_name` varchar(255) NOT NULL,
  `last_name` varchar(255) NOT NULL,
  `role` varchar(255) NOT NULL,
  `department_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `staff`
--

INSERT INTO `staff` (`id`, `user_id`, `staff_id`, `first_name`, `middle_name`, `last_name`, `role`, `department_id`) VALUES
(10, 236, 15100315, 'Rafael', 'Fajardo', 'Sanoria', 'Staff', 55),
(11, 238, 12, 'Fritzie', '', 'Labial', 'HR', 144);

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `userName` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `userType` enum('Admin','Regular') NOT NULL,
  `first_name` varchar(100) DEFAULT NULL,
  `middle_name` varchar(100) DEFAULT NULL,
  `last_name` varchar(100) DEFAULT NULL,
  `role` enum('HR','Faculty','Head Staff','Staff','Program Head') DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `enrollment_status` enum('Enrolled','Unenrolled','Active','Inactive') NOT NULL DEFAULT 'Enrolled',
  `status` enum('Active','Inactive') DEFAULT 'Active'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `userName`, `password`, `userType`, `first_name`, `middle_name`, `last_name`, `role`, `created_at`, `enrollment_status`, `status`) VALUES
(230, '11111', 'TCM2025CAP', 'Regular', 'Rogelio', '', 'Diez', 'Program Head', '2025-10-04 04:54:35', 'Active', 'Active'),
(231, '15100133', 'TCM2025CAP', 'Regular', 'Clarence', '', 'Cabero', 'Faculty', '2025-10-04 04:54:49', 'Active', 'Active'),
(232, '22222', 'TCM2025CAP', 'Regular', 'JB', '', 'Sanoria', 'Faculty', '2025-10-04 04:55:04', 'Active', 'Active'),
(233, '33333', 'TCM2025CAP', 'Regular', 'Kivron', '', 'Uy', 'Faculty', '2025-10-04 04:55:23', 'Active', 'Active'),
(234, '123456', 'TCM2025CAP', 'Admin', 'Aubrey', '', 'Sanchez', '', '2025-10-04 08:16:19', 'Active', 'Active'),
(236, '15100315', 'TCM2025CAP', 'Regular', 'Rafael', 'Fajardo', 'Sanoria', 'Staff', '2025-10-06 00:59:12', 'Active', 'Active'),
(237, '15100331', 'TCM2025CAP', 'Regular', 'Alexander', 'Besin', 'Fajardo', 'Faculty', '2025-10-06 01:29:21', 'Active', 'Active'),
(238, '12', 'TCM2025CAP', 'Regular', 'Fritzie', '', 'Labial', 'HR', '2025-10-06 13:19:35', 'Active', 'Active');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `criteria`
--
ALTER TABLE `criteria`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `criteria_options`
--
ALTER TABLE `criteria_options`
  ADD PRIMARY KEY (`id`),
  ADD KEY `criteria_id` (`criteria_id`);

--
-- Indexes for table `curriculum`
--
ALTER TABLE `curriculum`
  ADD PRIMARY KEY (`curriculum_id`);

--
-- Indexes for table `departments`
--
ALTER TABLE `departments`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `department_name` (`department_name`);

--
-- Indexes for table `evaluation_responses`
--
ALTER TABLE `evaluation_responses`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_evalresp_evaluator` (`evaluator_id`),
  ADD KEY `idx_evalresp_evaluated` (`evaluated_id`),
  ADD KEY `idx_evalresp_questionnaire` (`questionnaire_id`),
  ADD KEY `fk_evalresp_question` (`question_id`),
  ADD KEY `idx_evaluation_group` (`evaluator_id`,`evaluated_id`,`questionnaire_id`),
  ADD KEY `evaluation_id` (`evaluation_id`),
  ADD KEY `fk_evalresponses_curriculum` (`curriculum_id`);

--
-- Indexes for table `faculty`
--
ALTER TABLE `faculty`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `faculty_id` (`faculty_id`),
  ADD UNIQUE KEY `faculty_id_2` (`faculty_id`),
  ADD KEY `fk_department_id` (`department_id`),
  ADD KEY `fk_user` (`user_id`),
  ADD KEY `fk_program` (`program_id`);

--
-- Indexes for table `programs`
--
ALTER TABLE `programs`
  ADD PRIMARY KEY (`program_id`),
  ADD UNIQUE KEY `program_name` (`program_name`);

--
-- Indexes for table `questionnaires`
--
ALTER TABLE `questionnaires`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `questionnaire_assignments`
--
ALTER TABLE `questionnaire_assignments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `questionnaire_id` (`questionnaire_id`),
  ADD KEY `faculty_id` (`faculty_id`),
  ADD KEY `department_id` (`department_id`),
  ADD KEY `program_id` (`program_id`),
  ADD KEY `questionnaire_assignments_ibfk_5` (`curriculum_id`);

--
-- Indexes for table `questions`
--
ALTER TABLE `questions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `questionnaire_id` (`questionnaire_id`);

--
-- Indexes for table `staff`
--
ALTER TABLE `staff`
  ADD PRIMARY KEY (`id`),
  ADD KEY `Fk_user_id` (`user_id`),
  ADD KEY `department_id` (`department_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `id` (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `criteria`
--
ALTER TABLE `criteria`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `criteria_options`
--
ALTER TABLE `criteria_options`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;

--
-- AUTO_INCREMENT for table `curriculum`
--
ALTER TABLE `curriculum`
  MODIFY `curriculum_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `departments`
--
ALTER TABLE `departments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=145;

--
-- AUTO_INCREMENT for table `evaluation_responses`
--
ALTER TABLE `evaluation_responses`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=329;

--
-- AUTO_INCREMENT for table `faculty`
--
ALTER TABLE `faculty`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=142;

--
-- AUTO_INCREMENT for table `programs`
--
ALTER TABLE `programs`
  MODIFY `program_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=74;

--
-- AUTO_INCREMENT for table `questionnaires`
--
ALTER TABLE `questionnaires`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=37;

--
-- AUTO_INCREMENT for table `questionnaire_assignments`
--
ALTER TABLE `questionnaire_assignments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=137;

--
-- AUTO_INCREMENT for table `questions`
--
ALTER TABLE `questions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=100;

--
-- AUTO_INCREMENT for table `staff`
--
ALTER TABLE `staff`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=239;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `criteria_options`
--
ALTER TABLE `criteria_options`
  ADD CONSTRAINT `criteria_options_ibfk_1` FOREIGN KEY (`criteria_id`) REFERENCES `criteria` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `evaluation_responses`
--
ALTER TABLE `evaluation_responses`
  ADD CONSTRAINT `fk_evalresp_question` FOREIGN KEY (`question_id`) REFERENCES `questions` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_evalresp_questionnaire` FOREIGN KEY (`questionnaire_id`) REFERENCES `questionnaires` (`id`),
  ADD CONSTRAINT `fk_evalresponses_curriculum` FOREIGN KEY (`curriculum_id`) REFERENCES `curriculum` (`curriculum_id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `faculty`
--
ALTER TABLE `faculty`
  ADD CONSTRAINT `fk_department_id` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_program` FOREIGN KEY (`program_id`) REFERENCES `programs` (`program_id`),
  ADD CONSTRAINT `fk_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `questionnaire_assignments`
--
ALTER TABLE `questionnaire_assignments`
  ADD CONSTRAINT `questionnaire_assignments_ibfk_1` FOREIGN KEY (`questionnaire_id`) REFERENCES `questionnaires` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `questionnaire_assignments_ibfk_2` FOREIGN KEY (`faculty_id`) REFERENCES `faculty` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `questionnaire_assignments_ibfk_3` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `questionnaire_assignments_ibfk_4` FOREIGN KEY (`program_id`) REFERENCES `programs` (`program_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `questionnaire_assignments_ibfk_5` FOREIGN KEY (`curriculum_id`) REFERENCES `curriculum` (`curriculum_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `questions`
--
ALTER TABLE `questions`
  ADD CONSTRAINT `questions_ibfk_1` FOREIGN KEY (`questionnaire_id`) REFERENCES `questionnaires` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `staff`
--
ALTER TABLE `staff`
  ADD CONSTRAINT `Fk_user_id` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
