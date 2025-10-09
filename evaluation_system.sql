-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Oct 09, 2025 at 04:21 PM
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
(6, 'curriculum 2025-2026', '2025', '2026', '1st', '', 'active', '2025-10-07 10:31:41');

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
(149, 'College'),
(159, 'HR'),
(154, 'Maintenance Staff');

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
(2254, 1, 1103, 'Very Satisfactory', 247, 15100133, 40, 6, 4.00, NULL, 'completed', '2025-10-09 04:54:12'),
(2255, 1, 1104, 'Very Satisfactory', 247, 15100133, 40, 6, 4.00, NULL, 'completed', '2025-10-09 04:54:12'),
(2256, 1, 1105, 'Very Satisfactory', 247, 15100133, 40, 6, 4.00, NULL, 'completed', '2025-10-09 04:54:12'),
(2257, 1, 1106, 'Satisfactory', 247, 15100133, 40, 6, 3.00, NULL, 'completed', '2025-10-09 04:54:12'),
(2258, 1, 1107, 'Very Satisfactory', 247, 15100133, 40, 6, 4.00, NULL, 'completed', '2025-10-09 04:54:12'),
(2259, 1, 1108, 'Satisfactory', 247, 15100133, 40, 6, 3.00, NULL, 'completed', '2025-10-09 04:54:12'),
(2260, 1, 1109, 'Very Satisfactory', 247, 15100133, 40, 6, 4.00, NULL, 'completed', '2025-10-09 04:54:12'),
(2261, 1, 1110, 'Very Satisfactory', 247, 15100133, 40, 6, 4.00, NULL, 'completed', '2025-10-09 04:54:12'),
(2262, 1, 1111, 'Satisfactory', 247, 15100133, 40, 6, 3.00, NULL, 'completed', '2025-10-09 04:54:12'),
(2263, 1, 1112, 'Very Satisfactory', 247, 15100133, 40, 6, 4.00, NULL, 'completed', '2025-10-09 04:54:12'),
(2264, 2, 1103, 'Excellent', 247, 20221237, 40, 6, 5.00, NULL, 'completed', '2025-10-09 04:54:22'),
(2265, 2, 1104, 'Very Satisfactory', 247, 20221237, 40, 6, 4.00, NULL, 'completed', '2025-10-09 04:54:22'),
(2266, 2, 1105, 'Very Satisfactory', 247, 20221237, 40, 6, 4.00, NULL, 'completed', '2025-10-09 04:54:22'),
(2267, 2, 1106, 'Very Satisfactory', 247, 20221237, 40, 6, 4.00, NULL, 'completed', '2025-10-09 04:54:22'),
(2268, 2, 1107, 'Very Satisfactory', 247, 20221237, 40, 6, 4.00, NULL, 'completed', '2025-10-09 04:54:22'),
(2269, 2, 1108, 'Excellent', 247, 20221237, 40, 6, 5.00, NULL, 'completed', '2025-10-09 04:54:22'),
(2270, 2, 1109, 'Satisfactory', 247, 20221237, 40, 6, 3.00, NULL, 'completed', '2025-10-09 04:54:22'),
(2271, 2, 1110, 'Very Satisfactory', 247, 20221237, 40, 6, 4.00, NULL, 'completed', '2025-10-09 04:54:22'),
(2272, 2, 1111, 'Satisfactory', 247, 20221237, 40, 6, 3.00, NULL, 'completed', '2025-10-09 04:54:22'),
(2273, 2, 1112, 'Very Satisfactory', 247, 20221237, 40, 6, 4.00, NULL, 'completed', '2025-10-09 04:54:22'),
(2274, 3, 1103, 'Very Satisfactory', 247, 16100494, 40, 6, 4.00, NULL, 'completed', '2025-10-09 04:54:28'),
(2275, 3, 1104, 'Very Satisfactory', 247, 16100494, 40, 6, 4.00, NULL, 'completed', '2025-10-09 04:54:28'),
(2276, 3, 1105, 'Very Satisfactory', 247, 16100494, 40, 6, 4.00, NULL, 'completed', '2025-10-09 04:54:28'),
(2277, 3, 1106, 'Very Satisfactory', 247, 16100494, 40, 6, 4.00, NULL, 'completed', '2025-10-09 04:54:28'),
(2278, 3, 1107, 'Very Satisfactory', 247, 16100494, 40, 6, 4.00, NULL, 'completed', '2025-10-09 04:54:28'),
(2279, 3, 1108, 'Satisfactory', 247, 16100494, 40, 6, 3.00, NULL, 'completed', '2025-10-09 04:54:28'),
(2280, 3, 1109, 'Very Satisfactory', 247, 16100494, 40, 6, 4.00, NULL, 'completed', '2025-10-09 04:54:28'),
(2281, 3, 1110, 'Satisfactory', 247, 16100494, 40, 6, 3.00, NULL, 'completed', '2025-10-09 04:54:28'),
(2282, 3, 1111, 'Very Satisfactory', 247, 16100494, 40, 6, 4.00, NULL, 'completed', '2025-10-09 04:54:28'),
(2283, 3, 1112, 'Very Satisfactory', 247, 16100494, 40, 6, 4.00, NULL, 'completed', '2025-10-09 04:54:28'),
(2284, 4, 1103, 'Very Satisfactory', 247, 20220294, 40, 6, 4.00, NULL, 'completed', '2025-10-09 04:54:38'),
(2285, 4, 1104, 'Very Satisfactory', 247, 20220294, 40, 6, 4.00, NULL, 'completed', '2025-10-09 04:54:38'),
(2286, 4, 1105, 'Very Satisfactory', 247, 20220294, 40, 6, 4.00, NULL, 'completed', '2025-10-09 04:54:38'),
(2287, 4, 1106, 'Satisfactory', 247, 20220294, 40, 6, 3.00, NULL, 'completed', '2025-10-09 04:54:38'),
(2288, 4, 1107, 'Very Satisfactory', 247, 20220294, 40, 6, 4.00, NULL, 'completed', '2025-10-09 04:54:38'),
(2289, 4, 1108, 'Satisfactory', 247, 20220294, 40, 6, 3.00, NULL, 'completed', '2025-10-09 04:54:38'),
(2290, 4, 1109, 'Very Satisfactory', 247, 20220294, 40, 6, 4.00, NULL, 'completed', '2025-10-09 04:54:38'),
(2291, 4, 1110, 'Very Satisfactory', 247, 20220294, 40, 6, 4.00, NULL, 'completed', '2025-10-09 04:54:38'),
(2292, 4, 1111, 'Satisfactory', 247, 20220294, 40, 6, 3.00, NULL, 'completed', '2025-10-09 04:54:38'),
(2293, 4, 1112, 'Very Satisfactory', 247, 20220294, 40, 6, 4.00, NULL, 'completed', '2025-10-09 04:54:38'),
(2294, 5, 1123, 'Often', 247, 11111, 41, 6, 3.00, NULL, 'completed', '2025-10-09 04:54:47'),
(2295, 5, 1124, 'Often', 247, 11111, 41, 6, 3.00, NULL, 'completed', '2025-10-09 04:54:47'),
(2296, 5, 1125, 'Often', 247, 11111, 41, 6, 3.00, NULL, 'completed', '2025-10-09 04:54:47'),
(2297, 5, 1126, 'Seldom', 247, 11111, 41, 6, 2.00, NULL, 'completed', '2025-10-09 04:54:47'),
(2298, 5, 1127, 'Seldom', 247, 11111, 41, 6, 2.00, NULL, 'completed', '2025-10-09 04:54:47'),
(2299, 5, 1128, 'Often', 247, 11111, 41, 6, 3.00, NULL, 'completed', '2025-10-09 04:54:47'),
(2300, 5, 1129, 'Seldom', 247, 11111, 41, 6, 2.00, NULL, 'completed', '2025-10-09 04:54:47'),
(2301, 5, 1130, 'Often', 247, 11111, 41, 6, 3.00, NULL, 'completed', '2025-10-09 04:54:47'),
(2302, 5, 1131, 'Often', 247, 11111, 41, 6, 3.00, NULL, 'completed', '2025-10-09 04:54:47'),
(2303, 5, 1132, 'Seldom', 247, 11111, 41, 6, 2.00, NULL, 'completed', '2025-10-09 04:54:47'),
(2304, 6, 1103, 'Very Satisfactory', 246, 15100133, 40, 6, 4.00, NULL, 'completed', '2025-10-09 04:55:22'),
(2305, 6, 1104, 'Very Satisfactory', 246, 15100133, 40, 6, 4.00, NULL, 'completed', '2025-10-09 04:55:22'),
(2306, 6, 1105, 'Very Satisfactory', 246, 15100133, 40, 6, 4.00, NULL, 'completed', '2025-10-09 04:55:22'),
(2307, 6, 1106, 'Very Satisfactory', 246, 15100133, 40, 6, 4.00, NULL, 'completed', '2025-10-09 04:55:22'),
(2308, 6, 1107, 'Very Satisfactory', 246, 15100133, 40, 6, 4.00, NULL, 'completed', '2025-10-09 04:55:22'),
(2309, 6, 1108, 'Very Satisfactory', 246, 15100133, 40, 6, 4.00, NULL, 'completed', '2025-10-09 04:55:22'),
(2310, 6, 1109, 'Very Satisfactory', 246, 15100133, 40, 6, 4.00, NULL, 'completed', '2025-10-09 04:55:22'),
(2311, 6, 1110, 'Very Satisfactory', 246, 15100133, 40, 6, 4.00, NULL, 'completed', '2025-10-09 04:55:22'),
(2312, 6, 1111, 'Very Satisfactory', 246, 15100133, 40, 6, 4.00, NULL, 'completed', '2025-10-09 04:55:22'),
(2313, 6, 1112, 'Very Satisfactory', 246, 15100133, 40, 6, 4.00, NULL, 'completed', '2025-10-09 04:55:22'),
(2314, 7, 1103, 'Very Satisfactory', 246, 11111, 40, 6, 4.00, NULL, 'completed', '2025-10-09 04:55:31'),
(2315, 7, 1104, 'Very Satisfactory', 246, 11111, 40, 6, 4.00, NULL, 'completed', '2025-10-09 04:55:31'),
(2316, 7, 1105, 'Very Satisfactory', 246, 11111, 40, 6, 4.00, NULL, 'completed', '2025-10-09 04:55:31'),
(2317, 7, 1106, 'Very Satisfactory', 246, 11111, 40, 6, 4.00, NULL, 'completed', '2025-10-09 04:55:31'),
(2318, 7, 1107, 'Satisfactory', 246, 11111, 40, 6, 3.00, NULL, 'completed', '2025-10-09 04:55:31'),
(2319, 7, 1108, 'Very Satisfactory', 246, 11111, 40, 6, 4.00, NULL, 'completed', '2025-10-09 04:55:31'),
(2320, 7, 1109, 'Very Satisfactory', 246, 11111, 40, 6, 4.00, NULL, 'completed', '2025-10-09 04:55:31'),
(2321, 7, 1110, 'Satisfactory', 246, 11111, 40, 6, 3.00, NULL, 'completed', '2025-10-09 04:55:31'),
(2322, 7, 1111, 'Very Satisfactory', 246, 11111, 40, 6, 4.00, NULL, 'completed', '2025-10-09 04:55:31'),
(2323, 7, 1112, 'Very Satisfactory', 246, 11111, 40, 6, 4.00, NULL, 'completed', '2025-10-09 04:55:31'),
(2324, 8, 1103, 'Very Satisfactory', 246, 16100494, 40, 6, 4.00, NULL, 'completed', '2025-10-09 04:55:37'),
(2325, 8, 1104, 'Very Satisfactory', 246, 16100494, 40, 6, 4.00, NULL, 'completed', '2025-10-09 04:55:37'),
(2326, 8, 1105, 'Satisfactory', 246, 16100494, 40, 6, 3.00, NULL, 'completed', '2025-10-09 04:55:37'),
(2327, 8, 1106, 'Very Satisfactory', 246, 16100494, 40, 6, 4.00, NULL, 'completed', '2025-10-09 04:55:37'),
(2328, 8, 1107, 'Satisfactory', 246, 16100494, 40, 6, 3.00, NULL, 'completed', '2025-10-09 04:55:37'),
(2329, 8, 1108, 'Very Satisfactory', 246, 16100494, 40, 6, 4.00, NULL, 'completed', '2025-10-09 04:55:37'),
(2330, 8, 1109, 'Very Satisfactory', 246, 16100494, 40, 6, 4.00, NULL, 'completed', '2025-10-09 04:55:37'),
(2331, 8, 1110, 'Very Satisfactory', 246, 16100494, 40, 6, 4.00, NULL, 'completed', '2025-10-09 04:55:37'),
(2332, 8, 1111, 'Satisfactory', 246, 16100494, 40, 6, 3.00, NULL, 'completed', '2025-10-09 04:55:37'),
(2333, 8, 1112, 'Very Satisfactory', 246, 16100494, 40, 6, 4.00, NULL, 'completed', '2025-10-09 04:55:37'),
(2334, 9, 1103, 'Excellent', 246, 20220294, 40, 6, 5.00, NULL, 'completed', '2025-10-09 04:55:43'),
(2335, 9, 1104, 'Very Satisfactory', 246, 20220294, 40, 6, 4.00, NULL, 'completed', '2025-10-09 04:55:43'),
(2336, 9, 1105, 'Very Satisfactory', 246, 20220294, 40, 6, 4.00, NULL, 'completed', '2025-10-09 04:55:43'),
(2337, 9, 1106, 'Satisfactory', 246, 20220294, 40, 6, 3.00, NULL, 'completed', '2025-10-09 04:55:43'),
(2338, 9, 1107, 'Very Satisfactory', 246, 20220294, 40, 6, 4.00, NULL, 'completed', '2025-10-09 04:55:43'),
(2339, 9, 1108, 'Very Satisfactory', 246, 20220294, 40, 6, 4.00, NULL, 'completed', '2025-10-09 04:55:43'),
(2340, 9, 1109, 'Satisfactory', 246, 20220294, 40, 6, 3.00, NULL, 'completed', '2025-10-09 04:55:43'),
(2341, 9, 1110, 'Very Satisfactory', 246, 20220294, 40, 6, 4.00, NULL, 'completed', '2025-10-09 04:55:43'),
(2342, 9, 1111, 'Very Satisfactory', 246, 20220294, 40, 6, 4.00, NULL, 'completed', '2025-10-09 04:55:43'),
(2343, 9, 1112, 'Satisfactory', 246, 20220294, 40, 6, 3.00, NULL, 'completed', '2025-10-09 04:55:43'),
(2344, 10, 1123, 'Often', 246, 20221237, 41, 6, 3.00, NULL, 'completed', '2025-10-09 04:55:52'),
(2345, 10, 1124, 'Often', 246, 20221237, 41, 6, 3.00, NULL, 'completed', '2025-10-09 04:55:52'),
(2346, 10, 1125, 'Often', 246, 20221237, 41, 6, 3.00, NULL, 'completed', '2025-10-09 04:55:52'),
(2347, 10, 1126, 'Often', 246, 20221237, 41, 6, 3.00, NULL, 'completed', '2025-10-09 04:55:52'),
(2348, 10, 1127, 'Often', 246, 20221237, 41, 6, 3.00, NULL, 'completed', '2025-10-09 04:55:52'),
(2349, 10, 1128, 'Seldom', 246, 20221237, 41, 6, 2.00, NULL, 'completed', '2025-10-09 04:55:52'),
(2350, 10, 1129, 'Often', 246, 20221237, 41, 6, 3.00, NULL, 'completed', '2025-10-09 04:55:52'),
(2351, 10, 1130, 'Seldom', 246, 20221237, 41, 6, 2.00, NULL, 'completed', '2025-10-09 04:55:52'),
(2352, 10, 1131, 'Often', 246, 20221237, 41, 6, 3.00, NULL, 'completed', '2025-10-09 04:55:52'),
(2353, 10, 1132, 'Often', 246, 20221237, 41, 6, 3.00, NULL, 'completed', '2025-10-09 04:55:52'),
(2354, 11, 100, 'Often', 253, 15100133, 37, 6, 3.00, NULL, 'completed', '2025-10-09 05:06:31'),
(2355, 11, 101, 'Often', 253, 15100133, 37, 6, 3.00, NULL, 'completed', '2025-10-09 05:06:31'),
(2356, 12, 100, 'Often', 253, 20220294, 37, 6, 3.00, NULL, 'completed', '2025-10-09 05:06:35'),
(2357, 12, 101, 'Often', 253, 20220294, 37, 6, 3.00, NULL, 'completed', '2025-10-09 05:06:35'),
(2358, 13, 100, 'Often', 253, 16100494, 37, 6, 3.00, NULL, 'completed', '2025-10-09 05:06:38'),
(2359, 13, 101, 'Often', 253, 16100494, 37, 6, 3.00, NULL, 'completed', '2025-10-09 05:06:38'),
(2360, 14, 100, 'Often', 253, 20221237, 37, 6, 3.00, NULL, 'completed', '2025-10-09 05:06:42'),
(2361, 14, 101, 'Often', 253, 20221237, 37, 6, 3.00, NULL, 'completed', '2025-10-09 05:06:42'),
(2362, 15, 100, 'Often', 253, 11111, 37, 6, 3.00, NULL, 'completed', '2025-10-09 05:06:46'),
(2363, 15, 101, 'Seldom', 253, 11111, 37, 6, 2.00, NULL, 'completed', '2025-10-09 05:06:46'),
(2364, 16, 100, 'Often', 253, 3333, 37, 6, 3.00, NULL, 'completed', '2025-10-09 05:06:49'),
(2365, 16, 101, 'Often', 253, 3333, 37, 6, 3.00, NULL, 'completed', '2025-10-09 05:06:49'),
(2366, 17, 100, 'Often', 253, 21, 37, 6, 3.00, NULL, 'completed', '2025-10-09 05:06:52'),
(2367, 17, 101, 'Often', 253, 21, 37, 6, 3.00, NULL, 'completed', '2025-10-09 05:06:52'),
(2368, 18, 100, 'Often', 253, 22, 37, 6, 3.00, NULL, 'completed', '2025-10-09 05:06:55'),
(2369, 18, 101, 'Often', 253, 22, 37, 6, 3.00, NULL, 'completed', '2025-10-09 05:06:55'),
(2370, 19, 100, 'Often', 253, 23, 37, 6, 3.00, NULL, 'completed', '2025-10-09 05:06:58'),
(2371, 19, 101, 'Often', 253, 23, 37, 6, 3.00, NULL, 'completed', '2025-10-09 05:06:58'),
(2372, 20, 100, 'Often', 253, 24, 37, 6, 3.00, NULL, 'completed', '2025-10-09 05:07:01'),
(2373, 20, 101, 'Often', 253, 24, 37, 6, 3.00, NULL, 'completed', '2025-10-09 05:07:01'),
(2374, 21, 1123, 'Always', 253, 44, 41, 6, 4.00, NULL, 'completed', '2025-10-09 05:07:15'),
(2375, 21, 1124, 'Often', 253, 44, 41, 6, 3.00, NULL, 'completed', '2025-10-09 05:07:15'),
(2376, 21, 1125, 'Often', 253, 44, 41, 6, 3.00, NULL, 'completed', '2025-10-09 05:07:15'),
(2377, 21, 1126, 'Often', 253, 44, 41, 6, 3.00, NULL, 'completed', '2025-10-09 05:07:15'),
(2378, 21, 1127, 'Often', 253, 44, 41, 6, 3.00, NULL, 'completed', '2025-10-09 05:07:15'),
(2379, 21, 1128, 'Often', 253, 44, 41, 6, 3.00, NULL, 'completed', '2025-10-09 05:07:15'),
(2380, 21, 1129, 'Often', 253, 44, 41, 6, 3.00, NULL, 'completed', '2025-10-09 05:07:15'),
(2381, 21, 1130, 'Often', 253, 44, 41, 6, 3.00, NULL, 'completed', '2025-10-09 05:07:15'),
(2382, 21, 1131, 'Often', 253, 44, 41, 6, 3.00, NULL, 'completed', '2025-10-09 05:07:15'),
(2383, 21, 1132, 'Often', 253, 44, 41, 6, 3.00, NULL, 'completed', '2025-10-09 05:07:15'),
(2384, 22, 1123, 'Often', 252, 3333, 41, 6, 3.00, NULL, 'completed', '2025-10-09 05:07:53'),
(2385, 22, 1124, 'Often', 252, 3333, 41, 6, 3.00, NULL, 'completed', '2025-10-09 05:07:53'),
(2386, 22, 1125, 'Often', 252, 3333, 41, 6, 3.00, NULL, 'completed', '2025-10-09 05:07:53'),
(2387, 22, 1126, 'Often', 252, 3333, 41, 6, 3.00, NULL, 'completed', '2025-10-09 05:07:53'),
(2388, 22, 1127, 'Often', 252, 3333, 41, 6, 3.00, NULL, 'completed', '2025-10-09 05:07:53'),
(2389, 22, 1128, 'Often', 252, 3333, 41, 6, 3.00, NULL, 'completed', '2025-10-09 05:07:53'),
(2390, 22, 1129, 'Often', 252, 3333, 41, 6, 3.00, NULL, 'completed', '2025-10-09 05:07:53'),
(2391, 22, 1130, 'Often', 252, 3333, 41, 6, 3.00, NULL, 'completed', '2025-10-09 05:07:53'),
(2392, 22, 1131, 'Often', 252, 3333, 41, 6, 3.00, NULL, 'completed', '2025-10-09 05:07:53'),
(2393, 22, 1132, 'Often', 252, 3333, 41, 6, 3.00, NULL, 'completed', '2025-10-09 05:07:53'),
(2394, 23, 1083, 'Very Satisfactory', 251, 22, 42, 6, 4.00, NULL, 'completed', '2025-10-09 05:08:27'),
(2395, 23, 1084, 'Very Satisfactory', 251, 22, 42, 6, 4.00, NULL, 'completed', '2025-10-09 05:08:27'),
(2396, 23, 1085, 'Very Satisfactory', 251, 22, 42, 6, 4.00, NULL, 'completed', '2025-10-09 05:08:27'),
(2397, 23, 1086, 'Very Satisfactory', 251, 22, 42, 6, 4.00, NULL, 'completed', '2025-10-09 05:08:27'),
(2398, 23, 1087, 'Very Satisfactory', 251, 22, 42, 6, 4.00, NULL, 'completed', '2025-10-09 05:08:27'),
(2399, 23, 1088, 'Very Satisfactory', 251, 22, 42, 6, 4.00, NULL, 'completed', '2025-10-09 05:08:27'),
(2400, 23, 1089, 'Very Satisfactory', 251, 22, 42, 6, 4.00, NULL, 'completed', '2025-10-09 05:08:27'),
(2401, 23, 1090, 'Very Satisfactory', 251, 22, 42, 6, 4.00, NULL, 'completed', '2025-10-09 05:08:27'),
(2402, 23, 1091, 'Very Satisfactory', 251, 22, 42, 6, 4.00, NULL, 'completed', '2025-10-09 05:08:27'),
(2403, 23, 1092, 'Very Satisfactory', 251, 22, 42, 6, 4.00, NULL, 'completed', '2025-10-09 05:08:27'),
(2404, 24, 1083, 'Very Satisfactory', 251, 21, 42, 6, 4.00, NULL, 'completed', '2025-10-09 05:08:31'),
(2405, 24, 1084, 'Very Satisfactory', 251, 21, 42, 6, 4.00, NULL, 'completed', '2025-10-09 05:08:31'),
(2406, 24, 1085, 'Very Satisfactory', 251, 21, 42, 6, 4.00, NULL, 'completed', '2025-10-09 05:08:31'),
(2407, 24, 1086, 'Very Satisfactory', 251, 21, 42, 6, 4.00, NULL, 'completed', '2025-10-09 05:08:31'),
(2408, 24, 1087, 'Very Satisfactory', 251, 21, 42, 6, 4.00, NULL, 'completed', '2025-10-09 05:08:31'),
(2409, 24, 1088, 'Very Satisfactory', 251, 21, 42, 6, 4.00, NULL, 'completed', '2025-10-09 05:08:31'),
(2410, 24, 1089, 'Very Satisfactory', 251, 21, 42, 6, 4.00, NULL, 'completed', '2025-10-09 05:08:31'),
(2411, 24, 1090, 'Very Satisfactory', 251, 21, 42, 6, 4.00, NULL, 'completed', '2025-10-09 05:08:31'),
(2412, 24, 1091, 'Very Satisfactory', 251, 21, 42, 6, 4.00, NULL, 'completed', '2025-10-09 05:08:31'),
(2413, 24, 1092, 'Very Satisfactory', 251, 21, 42, 6, 4.00, NULL, 'completed', '2025-10-09 05:08:31'),
(2414, 25, 1123, 'Often', 251, 24, 41, 6, 3.00, NULL, 'completed', '2025-10-09 05:08:50'),
(2415, 25, 1124, 'Often', 251, 24, 41, 6, 3.00, NULL, 'completed', '2025-10-09 05:08:50'),
(2416, 25, 1125, 'Often', 251, 24, 41, 6, 3.00, NULL, 'completed', '2025-10-09 05:08:50'),
(2417, 25, 1126, 'Often', 251, 24, 41, 6, 3.00, NULL, 'completed', '2025-10-09 05:08:50'),
(2418, 25, 1127, 'Often', 251, 24, 41, 6, 3.00, NULL, 'completed', '2025-10-09 05:08:50'),
(2419, 25, 1128, 'Often', 251, 24, 41, 6, 3.00, NULL, 'completed', '2025-10-09 05:08:50'),
(2420, 25, 1129, 'Often', 251, 24, 41, 6, 3.00, NULL, 'completed', '2025-10-09 05:08:50'),
(2421, 25, 1130, 'Often', 251, 24, 41, 6, 3.00, NULL, 'completed', '2025-10-09 05:08:50'),
(2422, 25, 1131, 'Often', 251, 24, 41, 6, 3.00, NULL, 'completed', '2025-10-09 05:08:50'),
(2423, 25, 1132, 'Often', 251, 24, 41, 6, 3.00, NULL, 'completed', '2025-10-09 05:08:50'),
(2424, 26, 1123, 'Often', 250, 23, 41, 6, 3.00, NULL, 'completed', '2025-10-09 05:09:21'),
(2425, 26, 1124, 'Often', 250, 23, 41, 6, 3.00, NULL, 'completed', '2025-10-09 05:09:21'),
(2426, 26, 1125, 'Seldom', 250, 23, 41, 6, 2.00, NULL, 'completed', '2025-10-09 05:09:21'),
(2427, 26, 1126, 'Often', 250, 23, 41, 6, 3.00, NULL, 'completed', '2025-10-09 05:09:21'),
(2428, 26, 1127, 'Often', 250, 23, 41, 6, 3.00, NULL, 'completed', '2025-10-09 05:09:21'),
(2429, 26, 1128, 'Often', 250, 23, 41, 6, 3.00, NULL, 'completed', '2025-10-09 05:09:21'),
(2430, 26, 1129, 'Seldom', 250, 23, 41, 6, 2.00, NULL, 'completed', '2025-10-09 05:09:21'),
(2431, 26, 1130, 'Often', 250, 23, 41, 6, 3.00, NULL, 'completed', '2025-10-09 05:09:21'),
(2432, 26, 1131, 'Often', 250, 23, 41, 6, 3.00, NULL, 'completed', '2025-10-09 05:09:21'),
(2433, 26, 1132, 'Often', 250, 23, 41, 6, 3.00, NULL, 'completed', '2025-10-09 05:09:21'),
(2434, 27, 1083, 'Very Satisfactory', 249, 21, 42, 6, 4.00, NULL, 'completed', '2025-10-09 05:09:45'),
(2435, 27, 1084, 'Very Satisfactory', 249, 21, 42, 6, 4.00, NULL, 'completed', '2025-10-09 05:09:45'),
(2436, 27, 1085, 'Very Satisfactory', 249, 21, 42, 6, 4.00, NULL, 'completed', '2025-10-09 05:09:45'),
(2437, 27, 1086, 'Very Satisfactory', 249, 21, 42, 6, 4.00, NULL, 'completed', '2025-10-09 05:09:45'),
(2438, 27, 1087, 'Very Satisfactory', 249, 21, 42, 6, 4.00, NULL, 'completed', '2025-10-09 05:09:45'),
(2439, 27, 1088, 'Very Satisfactory', 249, 21, 42, 6, 4.00, NULL, 'completed', '2025-10-09 05:09:45'),
(2440, 27, 1089, 'Very Satisfactory', 249, 21, 42, 6, 4.00, NULL, 'completed', '2025-10-09 05:09:45'),
(2441, 27, 1090, 'Very Satisfactory', 249, 21, 42, 6, 4.00, NULL, 'completed', '2025-10-09 05:09:45'),
(2442, 27, 1091, 'Very Satisfactory', 249, 21, 42, 6, 4.00, NULL, 'completed', '2025-10-09 05:09:45'),
(2443, 27, 1092, 'Very Satisfactory', 249, 21, 42, 6, 4.00, NULL, 'completed', '2025-10-09 05:09:45'),
(2444, 28, 1083, 'Very Satisfactory', 249, 24, 42, 6, 4.00, NULL, 'completed', '2025-10-09 05:09:58'),
(2445, 28, 1084, 'Very Satisfactory', 249, 24, 42, 6, 4.00, NULL, 'completed', '2025-10-09 05:09:58'),
(2446, 28, 1085, 'Very Satisfactory', 249, 24, 42, 6, 4.00, NULL, 'completed', '2025-10-09 05:09:58'),
(2447, 28, 1086, 'Very Satisfactory', 249, 24, 42, 6, 4.00, NULL, 'completed', '2025-10-09 05:09:58'),
(2448, 28, 1087, 'Very Satisfactory', 249, 24, 42, 6, 4.00, NULL, 'completed', '2025-10-09 05:09:58'),
(2449, 28, 1088, 'Very Satisfactory', 249, 24, 42, 6, 4.00, NULL, 'completed', '2025-10-09 05:09:58'),
(2450, 28, 1089, 'Very Satisfactory', 249, 24, 42, 6, 4.00, NULL, 'completed', '2025-10-09 05:09:58'),
(2451, 28, 1090, 'Very Satisfactory', 249, 24, 42, 6, 4.00, NULL, 'completed', '2025-10-09 05:09:58'),
(2452, 28, 1091, 'Very Satisfactory', 249, 24, 42, 6, 4.00, NULL, 'completed', '2025-10-09 05:09:58'),
(2453, 28, 1092, 'Very Satisfactory', 249, 24, 42, 6, 4.00, NULL, 'completed', '2025-10-09 05:09:58'),
(2454, 29, 1123, 'Always', 249, 22, 41, 6, 4.00, NULL, 'completed', '2025-10-09 05:10:08'),
(2455, 29, 1124, 'Often', 249, 22, 41, 6, 3.00, NULL, 'completed', '2025-10-09 05:10:08'),
(2456, 29, 1125, 'Often', 249, 22, 41, 6, 3.00, NULL, 'completed', '2025-10-09 05:10:08'),
(2457, 29, 1126, 'Often', 249, 22, 41, 6, 3.00, NULL, 'completed', '2025-10-09 05:10:08'),
(2458, 29, 1127, 'Often', 249, 22, 41, 6, 3.00, NULL, 'completed', '2025-10-09 05:10:08'),
(2459, 29, 1128, 'Often', 249, 22, 41, 6, 3.00, NULL, 'completed', '2025-10-09 05:10:08'),
(2460, 29, 1129, 'Often', 249, 22, 41, 6, 3.00, NULL, 'completed', '2025-10-09 05:10:08'),
(2461, 29, 1130, 'Often', 249, 22, 41, 6, 3.00, NULL, 'completed', '2025-10-09 05:10:08'),
(2462, 29, 1131, 'Often', 249, 22, 41, 6, 3.00, NULL, 'completed', '2025-10-09 05:10:08'),
(2463, 29, 1132, 'Often', 249, 22, 41, 6, 3.00, NULL, 'completed', '2025-10-09 05:10:08'),
(2464, 30, 1083, 'Very Satisfactory', 248, 22, 42, 6, 4.00, NULL, 'completed', '2025-10-09 05:10:39'),
(2465, 30, 1084, 'Very Satisfactory', 248, 22, 42, 6, 4.00, NULL, 'completed', '2025-10-09 05:10:39'),
(2466, 30, 1085, 'Very Satisfactory', 248, 22, 42, 6, 4.00, NULL, 'completed', '2025-10-09 05:10:39'),
(2467, 30, 1086, 'Very Satisfactory', 248, 22, 42, 6, 4.00, NULL, 'completed', '2025-10-09 05:10:39'),
(2468, 30, 1087, 'Very Satisfactory', 248, 22, 42, 6, 4.00, NULL, 'completed', '2025-10-09 05:10:39'),
(2469, 30, 1088, 'Very Satisfactory', 248, 22, 42, 6, 4.00, NULL, 'completed', '2025-10-09 05:10:39'),
(2470, 30, 1089, 'Very Satisfactory', 248, 22, 42, 6, 4.00, NULL, 'completed', '2025-10-09 05:10:39'),
(2471, 30, 1090, 'Very Satisfactory', 248, 22, 42, 6, 4.00, NULL, 'completed', '2025-10-09 05:10:39'),
(2472, 30, 1091, 'Very Satisfactory', 248, 22, 42, 6, 4.00, NULL, 'completed', '2025-10-09 05:10:39'),
(2473, 30, 1092, 'Very Satisfactory', 248, 22, 42, 6, 4.00, NULL, 'completed', '2025-10-09 05:10:39'),
(2474, 31, 1083, 'Very Satisfactory', 248, 24, 42, 6, 4.00, NULL, 'completed', '2025-10-09 05:10:44'),
(2475, 31, 1084, 'Very Satisfactory', 248, 24, 42, 6, 4.00, NULL, 'completed', '2025-10-09 05:10:44'),
(2476, 31, 1085, 'Very Satisfactory', 248, 24, 42, 6, 4.00, NULL, 'completed', '2025-10-09 05:10:44'),
(2477, 31, 1086, 'Very Satisfactory', 248, 24, 42, 6, 4.00, NULL, 'completed', '2025-10-09 05:10:44'),
(2478, 31, 1087, 'Very Satisfactory', 248, 24, 42, 6, 4.00, NULL, 'completed', '2025-10-09 05:10:44'),
(2479, 31, 1088, 'Very Satisfactory', 248, 24, 42, 6, 4.00, NULL, 'completed', '2025-10-09 05:10:44'),
(2480, 31, 1089, 'Very Satisfactory', 248, 24, 42, 6, 4.00, NULL, 'completed', '2025-10-09 05:10:44'),
(2481, 31, 1090, 'Very Satisfactory', 248, 24, 42, 6, 4.00, NULL, 'completed', '2025-10-09 05:10:44'),
(2482, 31, 1091, 'Very Satisfactory', 248, 24, 42, 6, 4.00, NULL, 'completed', '2025-10-09 05:10:44'),
(2483, 31, 1092, 'Very Satisfactory', 248, 24, 42, 6, 4.00, NULL, 'completed', '2025-10-09 05:10:44'),
(2484, 32, 1123, 'Often', 248, 21, 41, 6, 3.00, NULL, 'completed', '2025-10-09 05:10:54'),
(2485, 32, 1124, 'Often', 248, 21, 41, 6, 3.00, NULL, 'completed', '2025-10-09 05:10:54'),
(2486, 32, 1125, 'Often', 248, 21, 41, 6, 3.00, NULL, 'completed', '2025-10-09 05:10:54'),
(2487, 32, 1126, 'Often', 248, 21, 41, 6, 3.00, NULL, 'completed', '2025-10-09 05:10:54'),
(2488, 32, 1127, 'Often', 248, 21, 41, 6, 3.00, NULL, 'completed', '2025-10-09 05:10:54'),
(2489, 32, 1128, 'Often', 248, 21, 41, 6, 3.00, NULL, 'completed', '2025-10-09 05:10:54'),
(2490, 32, 1129, 'Often', 248, 21, 41, 6, 3.00, NULL, 'completed', '2025-10-09 05:10:54'),
(2491, 32, 1130, 'Often', 248, 21, 41, 6, 3.00, NULL, 'completed', '2025-10-09 05:10:54'),
(2492, 32, 1131, 'Often', 248, 21, 41, 6, 3.00, NULL, 'completed', '2025-10-09 05:10:54'),
(2493, 32, 1132, 'Often', 248, 21, 41, 6, 3.00, NULL, 'completed', '2025-10-09 05:10:54'),
(2494, 33, 1103, 'Very Satisfactory', 244, 15100133, 40, 6, 4.00, NULL, 'completed', '2025-10-09 05:11:26'),
(2495, 33, 1104, 'Very Satisfactory', 244, 15100133, 40, 6, 4.00, NULL, 'completed', '2025-10-09 05:11:26'),
(2496, 33, 1105, 'Very Satisfactory', 244, 15100133, 40, 6, 4.00, NULL, 'completed', '2025-10-09 05:11:26'),
(2497, 33, 1106, 'Very Satisfactory', 244, 15100133, 40, 6, 4.00, NULL, 'completed', '2025-10-09 05:11:26'),
(2498, 33, 1107, 'Very Satisfactory', 244, 15100133, 40, 6, 4.00, NULL, 'completed', '2025-10-09 05:11:26'),
(2499, 33, 1108, 'Very Satisfactory', 244, 15100133, 40, 6, 4.00, NULL, 'completed', '2025-10-09 05:11:26'),
(2500, 33, 1109, 'Very Satisfactory', 244, 15100133, 40, 6, 4.00, NULL, 'completed', '2025-10-09 05:11:26'),
(2501, 33, 1110, 'Very Satisfactory', 244, 15100133, 40, 6, 4.00, NULL, 'completed', '2025-10-09 05:11:26'),
(2502, 33, 1111, 'Very Satisfactory', 244, 15100133, 40, 6, 4.00, NULL, 'completed', '2025-10-09 05:11:26'),
(2503, 33, 1112, 'Very Satisfactory', 244, 15100133, 40, 6, 4.00, NULL, 'completed', '2025-10-09 05:11:26'),
(2504, 34, 1103, 'Very Satisfactory', 244, 11111, 40, 6, 4.00, NULL, 'completed', '2025-10-09 05:11:36'),
(2505, 34, 1104, 'Very Satisfactory', 244, 11111, 40, 6, 4.00, NULL, 'completed', '2025-10-09 05:11:36'),
(2506, 34, 1105, 'Very Satisfactory', 244, 11111, 40, 6, 4.00, NULL, 'completed', '2025-10-09 05:11:36'),
(2507, 34, 1106, 'Very Satisfactory', 244, 11111, 40, 6, 4.00, NULL, 'completed', '2025-10-09 05:11:36'),
(2508, 34, 1107, 'Very Satisfactory', 244, 11111, 40, 6, 4.00, NULL, 'completed', '2025-10-09 05:11:36'),
(2509, 34, 1108, 'Very Satisfactory', 244, 11111, 40, 6, 4.00, NULL, 'completed', '2025-10-09 05:11:36'),
(2510, 34, 1109, 'Very Satisfactory', 244, 11111, 40, 6, 4.00, NULL, 'completed', '2025-10-09 05:11:36'),
(2511, 34, 1110, 'Very Satisfactory', 244, 11111, 40, 6, 4.00, NULL, 'completed', '2025-10-09 05:11:36'),
(2512, 34, 1111, 'Very Satisfactory', 244, 11111, 40, 6, 4.00, NULL, 'completed', '2025-10-09 05:11:36'),
(2513, 34, 1112, 'Very Satisfactory', 244, 11111, 40, 6, 4.00, NULL, 'completed', '2025-10-09 05:11:36'),
(2514, 35, 1103, 'Very Satisfactory', 244, 20221237, 40, 6, 4.00, NULL, 'completed', '2025-10-09 05:11:40'),
(2515, 35, 1104, 'Very Satisfactory', 244, 20221237, 40, 6, 4.00, NULL, 'completed', '2025-10-09 05:11:40'),
(2516, 35, 1105, 'Very Satisfactory', 244, 20221237, 40, 6, 4.00, NULL, 'completed', '2025-10-09 05:11:40'),
(2517, 35, 1106, 'Very Satisfactory', 244, 20221237, 40, 6, 4.00, NULL, 'completed', '2025-10-09 05:11:40'),
(2518, 35, 1107, 'Very Satisfactory', 244, 20221237, 40, 6, 4.00, NULL, 'completed', '2025-10-09 05:11:40'),
(2519, 35, 1108, 'Very Satisfactory', 244, 20221237, 40, 6, 4.00, NULL, 'completed', '2025-10-09 05:11:40'),
(2520, 35, 1109, 'Very Satisfactory', 244, 20221237, 40, 6, 4.00, NULL, 'completed', '2025-10-09 05:11:40'),
(2521, 35, 1110, 'Very Satisfactory', 244, 20221237, 40, 6, 4.00, NULL, 'completed', '2025-10-09 05:11:40'),
(2522, 35, 1111, 'Very Satisfactory', 244, 20221237, 40, 6, 4.00, NULL, 'completed', '2025-10-09 05:11:40'),
(2523, 35, 1112, 'Very Satisfactory', 244, 20221237, 40, 6, 4.00, NULL, 'completed', '2025-10-09 05:11:40'),
(2524, 36, 1103, 'Excellent', 244, 16100494, 40, 6, 5.00, NULL, 'completed', '2025-10-09 05:11:45'),
(2525, 36, 1104, 'Very Satisfactory', 244, 16100494, 40, 6, 4.00, NULL, 'completed', '2025-10-09 05:11:45'),
(2526, 36, 1105, 'Very Satisfactory', 244, 16100494, 40, 6, 4.00, NULL, 'completed', '2025-10-09 05:11:45'),
(2527, 36, 1106, 'Very Satisfactory', 244, 16100494, 40, 6, 4.00, NULL, 'completed', '2025-10-09 05:11:45'),
(2528, 36, 1107, 'Very Satisfactory', 244, 16100494, 40, 6, 4.00, NULL, 'completed', '2025-10-09 05:11:45'),
(2529, 36, 1108, 'Very Satisfactory', 244, 16100494, 40, 6, 4.00, NULL, 'completed', '2025-10-09 05:11:45'),
(2530, 36, 1109, 'Very Satisfactory', 244, 16100494, 40, 6, 4.00, NULL, 'completed', '2025-10-09 05:11:45'),
(2531, 36, 1110, 'Very Satisfactory', 244, 16100494, 40, 6, 4.00, NULL, 'completed', '2025-10-09 05:11:45'),
(2532, 36, 1111, 'Very Satisfactory', 244, 16100494, 40, 6, 4.00, NULL, 'completed', '2025-10-09 05:11:45'),
(2533, 36, 1112, 'Very Satisfactory', 244, 16100494, 40, 6, 4.00, NULL, 'completed', '2025-10-09 05:11:45'),
(2534, 37, 1123, 'Often', 244, 20220294, 41, 6, 3.00, NULL, 'completed', '2025-10-09 05:11:53'),
(2535, 37, 1124, 'Often', 244, 20220294, 41, 6, 3.00, NULL, 'completed', '2025-10-09 05:11:53'),
(2536, 37, 1125, 'Often', 244, 20220294, 41, 6, 3.00, NULL, 'completed', '2025-10-09 05:11:53'),
(2537, 37, 1126, 'Often', 244, 20220294, 41, 6, 3.00, NULL, 'completed', '2025-10-09 05:11:53'),
(2538, 37, 1127, 'Often', 244, 20220294, 41, 6, 3.00, NULL, 'completed', '2025-10-09 05:11:53'),
(2539, 37, 1128, 'Often', 244, 20220294, 41, 6, 3.00, NULL, 'completed', '2025-10-09 05:11:53'),
(2540, 37, 1129, 'Often', 244, 20220294, 41, 6, 3.00, NULL, 'completed', '2025-10-09 05:11:53'),
(2541, 37, 1130, 'Often', 244, 20220294, 41, 6, 3.00, NULL, 'completed', '2025-10-09 05:11:53'),
(2542, 37, 1131, 'Often', 244, 20220294, 41, 6, 3.00, NULL, 'completed', '2025-10-09 05:11:53'),
(2543, 37, 1132, 'Often', 244, 20220294, 41, 6, 3.00, NULL, 'completed', '2025-10-09 05:11:53'),
(2544, 38, 1103, 'Very Satisfactory', 245, 15100133, 40, 6, 4.00, NULL, 'completed', '2025-10-09 05:12:36'),
(2545, 38, 1104, 'Very Satisfactory', 245, 15100133, 40, 6, 4.00, NULL, 'completed', '2025-10-09 05:12:36'),
(2546, 38, 1105, 'Very Satisfactory', 245, 15100133, 40, 6, 4.00, NULL, 'completed', '2025-10-09 05:12:36'),
(2547, 38, 1106, 'Very Satisfactory', 245, 15100133, 40, 6, 4.00, NULL, 'completed', '2025-10-09 05:12:36'),
(2548, 38, 1107, 'Very Satisfactory', 245, 15100133, 40, 6, 4.00, NULL, 'completed', '2025-10-09 05:12:36'),
(2549, 38, 1108, 'Very Satisfactory', 245, 15100133, 40, 6, 4.00, NULL, 'completed', '2025-10-09 05:12:36'),
(2550, 38, 1109, 'Very Satisfactory', 245, 15100133, 40, 6, 4.00, NULL, 'completed', '2025-10-09 05:12:36'),
(2551, 38, 1110, 'Very Satisfactory', 245, 15100133, 40, 6, 4.00, NULL, 'completed', '2025-10-09 05:12:36'),
(2552, 38, 1111, 'Very Satisfactory', 245, 15100133, 40, 6, 4.00, NULL, 'completed', '2025-10-09 05:12:36'),
(2553, 38, 1112, 'Very Satisfactory', 245, 15100133, 40, 6, 4.00, NULL, 'completed', '2025-10-09 05:12:36'),
(2554, 39, 1103, 'Very Satisfactory', 245, 20221237, 40, 6, 4.00, NULL, 'completed', '2025-10-09 05:12:40'),
(2555, 39, 1104, 'Very Satisfactory', 245, 20221237, 40, 6, 4.00, NULL, 'completed', '2025-10-09 05:12:40'),
(2556, 39, 1105, 'Very Satisfactory', 245, 20221237, 40, 6, 4.00, NULL, 'completed', '2025-10-09 05:12:40'),
(2557, 39, 1106, 'Very Satisfactory', 245, 20221237, 40, 6, 4.00, NULL, 'completed', '2025-10-09 05:12:40'),
(2558, 39, 1107, 'Very Satisfactory', 245, 20221237, 40, 6, 4.00, NULL, 'completed', '2025-10-09 05:12:40'),
(2559, 39, 1108, 'Very Satisfactory', 245, 20221237, 40, 6, 4.00, NULL, 'completed', '2025-10-09 05:12:40'),
(2560, 39, 1109, 'Very Satisfactory', 245, 20221237, 40, 6, 4.00, NULL, 'completed', '2025-10-09 05:12:40'),
(2561, 39, 1110, 'Very Satisfactory', 245, 20221237, 40, 6, 4.00, NULL, 'completed', '2025-10-09 05:12:40'),
(2562, 39, 1111, 'Very Satisfactory', 245, 20221237, 40, 6, 4.00, NULL, 'completed', '2025-10-09 05:12:40'),
(2563, 39, 1112, 'Very Satisfactory', 245, 20221237, 40, 6, 4.00, NULL, 'completed', '2025-10-09 05:12:40'),
(2564, 40, 1103, 'Excellent', 245, 11111, 40, 6, 5.00, NULL, 'completed', '2025-10-09 05:12:55'),
(2565, 40, 1104, 'Satisfactory', 245, 11111, 40, 6, 3.00, NULL, 'completed', '2025-10-09 05:12:55'),
(2566, 40, 1105, 'Very Satisfactory', 245, 11111, 40, 6, 4.00, NULL, 'completed', '2025-10-09 05:12:55'),
(2567, 40, 1106, 'Very Satisfactory', 245, 11111, 40, 6, 4.00, NULL, 'completed', '2025-10-09 05:12:55'),
(2568, 40, 1107, 'Very Satisfactory', 245, 11111, 40, 6, 4.00, NULL, 'completed', '2025-10-09 05:12:55'),
(2569, 40, 1108, 'Very Satisfactory', 245, 11111, 40, 6, 4.00, NULL, 'completed', '2025-10-09 05:12:55'),
(2570, 40, 1109, 'Very Satisfactory', 245, 11111, 40, 6, 4.00, NULL, 'completed', '2025-10-09 05:12:55'),
(2571, 40, 1110, 'Very Satisfactory', 245, 11111, 40, 6, 4.00, NULL, 'completed', '2025-10-09 05:12:55'),
(2572, 40, 1111, 'Very Satisfactory', 245, 11111, 40, 6, 4.00, NULL, 'completed', '2025-10-09 05:12:55'),
(2573, 40, 1112, 'Very Satisfactory', 245, 11111, 40, 6, 4.00, NULL, 'completed', '2025-10-09 05:12:55'),
(2574, 41, 1103, 'Very Satisfactory', 245, 20220294, 40, 6, 4.00, NULL, 'completed', '2025-10-09 05:13:01'),
(2575, 41, 1104, 'Very Satisfactory', 245, 20220294, 40, 6, 4.00, NULL, 'completed', '2025-10-09 05:13:01'),
(2576, 41, 1105, 'Very Satisfactory', 245, 20220294, 40, 6, 4.00, NULL, 'completed', '2025-10-09 05:13:01'),
(2577, 41, 1106, 'Very Satisfactory', 245, 20220294, 40, 6, 4.00, NULL, 'completed', '2025-10-09 05:13:01'),
(2578, 41, 1107, 'Very Satisfactory', 245, 20220294, 40, 6, 4.00, NULL, 'completed', '2025-10-09 05:13:01'),
(2579, 41, 1108, 'Very Satisfactory', 245, 20220294, 40, 6, 4.00, NULL, 'completed', '2025-10-09 05:13:01'),
(2580, 41, 1109, 'Very Satisfactory', 245, 20220294, 40, 6, 4.00, NULL, 'completed', '2025-10-09 05:13:01'),
(2581, 41, 1110, 'Very Satisfactory', 245, 20220294, 40, 6, 4.00, NULL, 'completed', '2025-10-09 05:13:01'),
(2582, 41, 1111, 'Very Satisfactory', 245, 20220294, 40, 6, 4.00, NULL, 'completed', '2025-10-09 05:13:01'),
(2583, 41, 1112, 'Very Satisfactory', 245, 20220294, 40, 6, 4.00, NULL, 'completed', '2025-10-09 05:13:01'),
(2584, 42, 1123, 'Often', 245, 16100494, 41, 6, 3.00, NULL, 'completed', '2025-10-09 05:13:10'),
(2585, 42, 1124, 'Often', 245, 16100494, 41, 6, 3.00, NULL, 'completed', '2025-10-09 05:13:10'),
(2586, 42, 1125, 'Often', 245, 16100494, 41, 6, 3.00, NULL, 'completed', '2025-10-09 05:13:10'),
(2587, 42, 1126, 'Often', 245, 16100494, 41, 6, 3.00, NULL, 'completed', '2025-10-09 05:13:10'),
(2588, 42, 1127, 'Often', 245, 16100494, 41, 6, 3.00, NULL, 'completed', '2025-10-09 05:13:10'),
(2589, 42, 1128, 'Often', 245, 16100494, 41, 6, 3.00, NULL, 'completed', '2025-10-09 05:13:10'),
(2590, 42, 1129, 'Often', 245, 16100494, 41, 6, 3.00, NULL, 'completed', '2025-10-09 05:13:10'),
(2591, 42, 1130, 'Often', 245, 16100494, 41, 6, 3.00, NULL, 'completed', '2025-10-09 05:13:10'),
(2592, 42, 1131, 'Often', 245, 16100494, 41, 6, 3.00, NULL, 'completed', '2025-10-09 05:13:10'),
(2593, 42, 1132, 'Often', 245, 16100494, 41, 6, 3.00, NULL, 'completed', '2025-10-09 05:13:10'),
(2594, 43, 1103, 'Very Satisfactory', 243, 20220294, 40, 6, 4.00, NULL, 'completed', '2025-10-09 05:13:47'),
(2595, 43, 1104, 'Very Satisfactory', 243, 20220294, 40, 6, 4.00, NULL, 'completed', '2025-10-09 05:13:47'),
(2596, 43, 1105, 'Very Satisfactory', 243, 20220294, 40, 6, 4.00, NULL, 'completed', '2025-10-09 05:13:47'),
(2597, 43, 1106, 'Very Satisfactory', 243, 20220294, 40, 6, 4.00, NULL, 'completed', '2025-10-09 05:13:47'),
(2598, 43, 1107, 'Very Satisfactory', 243, 20220294, 40, 6, 4.00, NULL, 'completed', '2025-10-09 05:13:47'),
(2599, 43, 1108, 'Very Satisfactory', 243, 20220294, 40, 6, 4.00, NULL, 'completed', '2025-10-09 05:13:47'),
(2600, 43, 1109, 'Very Satisfactory', 243, 20220294, 40, 6, 4.00, NULL, 'completed', '2025-10-09 05:13:47'),
(2601, 43, 1110, 'Very Satisfactory', 243, 20220294, 40, 6, 4.00, NULL, 'completed', '2025-10-09 05:13:47'),
(2602, 43, 1111, 'Very Satisfactory', 243, 20220294, 40, 6, 4.00, NULL, 'completed', '2025-10-09 05:13:47'),
(2603, 43, 1112, 'Very Satisfactory', 243, 20220294, 40, 6, 4.00, NULL, 'completed', '2025-10-09 05:13:47'),
(2604, 44, 1103, 'Very Satisfactory', 243, 16100494, 40, 6, 4.00, NULL, 'completed', '2025-10-09 05:13:51'),
(2605, 44, 1104, 'Very Satisfactory', 243, 16100494, 40, 6, 4.00, NULL, 'completed', '2025-10-09 05:13:51'),
(2606, 44, 1105, 'Very Satisfactory', 243, 16100494, 40, 6, 4.00, NULL, 'completed', '2025-10-09 05:13:51'),
(2607, 44, 1106, 'Very Satisfactory', 243, 16100494, 40, 6, 4.00, NULL, 'completed', '2025-10-09 05:13:51'),
(2608, 44, 1107, 'Very Satisfactory', 243, 16100494, 40, 6, 4.00, NULL, 'completed', '2025-10-09 05:13:51'),
(2609, 44, 1108, 'Very Satisfactory', 243, 16100494, 40, 6, 4.00, NULL, 'completed', '2025-10-09 05:13:51'),
(2610, 44, 1109, 'Very Satisfactory', 243, 16100494, 40, 6, 4.00, NULL, 'completed', '2025-10-09 05:13:51'),
(2611, 44, 1110, 'Very Satisfactory', 243, 16100494, 40, 6, 4.00, NULL, 'completed', '2025-10-09 05:13:51'),
(2612, 44, 1111, 'Very Satisfactory', 243, 16100494, 40, 6, 4.00, NULL, 'completed', '2025-10-09 05:13:51'),
(2613, 44, 1112, 'Very Satisfactory', 243, 16100494, 40, 6, 4.00, NULL, 'completed', '2025-10-09 05:13:51'),
(2614, 45, 1103, 'Very Satisfactory', 243, 20221237, 40, 6, 4.00, NULL, 'completed', '2025-10-09 05:13:57'),
(2615, 45, 1104, 'Very Satisfactory', 243, 20221237, 40, 6, 4.00, NULL, 'completed', '2025-10-09 05:13:57'),
(2616, 45, 1105, 'Very Satisfactory', 243, 20221237, 40, 6, 4.00, NULL, 'completed', '2025-10-09 05:13:57'),
(2617, 45, 1106, 'Very Satisfactory', 243, 20221237, 40, 6, 4.00, NULL, 'completed', '2025-10-09 05:13:57'),
(2618, 45, 1107, 'Very Satisfactory', 243, 20221237, 40, 6, 4.00, NULL, 'completed', '2025-10-09 05:13:57'),
(2619, 45, 1108, 'Very Satisfactory', 243, 20221237, 40, 6, 4.00, NULL, 'completed', '2025-10-09 05:13:57'),
(2620, 45, 1109, 'Very Satisfactory', 243, 20221237, 40, 6, 4.00, NULL, 'completed', '2025-10-09 05:13:57'),
(2621, 45, 1110, 'Very Satisfactory', 243, 20221237, 40, 6, 4.00, NULL, 'completed', '2025-10-09 05:13:57'),
(2622, 45, 1111, 'Very Satisfactory', 243, 20221237, 40, 6, 4.00, NULL, 'completed', '2025-10-09 05:13:57'),
(2623, 45, 1112, 'Very Satisfactory', 243, 20221237, 40, 6, 4.00, NULL, 'completed', '2025-10-09 05:13:57'),
(2624, 46, 1103, 'Very Satisfactory', 243, 11111, 40, 6, 4.00, NULL, 'completed', '2025-10-09 05:14:03'),
(2625, 46, 1104, 'Very Satisfactory', 243, 11111, 40, 6, 4.00, NULL, 'completed', '2025-10-09 05:14:03'),
(2626, 46, 1105, 'Very Satisfactory', 243, 11111, 40, 6, 4.00, NULL, 'completed', '2025-10-09 05:14:03'),
(2627, 46, 1106, 'Very Satisfactory', 243, 11111, 40, 6, 4.00, NULL, 'completed', '2025-10-09 05:14:03'),
(2628, 46, 1107, 'Very Satisfactory', 243, 11111, 40, 6, 4.00, NULL, 'completed', '2025-10-09 05:14:03'),
(2629, 46, 1108, 'Very Satisfactory', 243, 11111, 40, 6, 4.00, NULL, 'completed', '2025-10-09 05:14:03'),
(2630, 46, 1109, 'Very Satisfactory', 243, 11111, 40, 6, 4.00, NULL, 'completed', '2025-10-09 05:14:03'),
(2631, 46, 1110, 'Very Satisfactory', 243, 11111, 40, 6, 4.00, NULL, 'completed', '2025-10-09 05:14:03'),
(2632, 46, 1111, 'Very Satisfactory', 243, 11111, 40, 6, 4.00, NULL, 'completed', '2025-10-09 05:14:03'),
(2633, 46, 1112, 'Very Satisfactory', 243, 11111, 40, 6, 4.00, NULL, 'completed', '2025-10-09 05:14:03'),
(2634, 47, 1123, 'Often', 243, 15100133, 41, 6, 3.00, NULL, 'completed', '2025-10-09 05:14:10'),
(2635, 47, 1124, 'Often', 243, 15100133, 41, 6, 3.00, NULL, 'completed', '2025-10-09 05:14:10'),
(2636, 47, 1125, 'Often', 243, 15100133, 41, 6, 3.00, NULL, 'completed', '2025-10-09 05:14:10'),
(2637, 47, 1126, 'Often', 243, 15100133, 41, 6, 3.00, NULL, 'completed', '2025-10-09 05:14:10'),
(2638, 47, 1127, 'Often', 243, 15100133, 41, 6, 3.00, NULL, 'completed', '2025-10-09 05:14:10'),
(2639, 47, 1128, 'Often', 243, 15100133, 41, 6, 3.00, NULL, 'completed', '2025-10-09 05:14:10'),
(2640, 47, 1129, 'Often', 243, 15100133, 41, 6, 3.00, NULL, 'completed', '2025-10-09 05:14:10'),
(2641, 47, 1130, 'Often', 243, 15100133, 41, 6, 3.00, NULL, 'completed', '2025-10-09 05:14:10'),
(2642, 47, 1131, 'Often', 243, 15100133, 41, 6, 3.00, NULL, 'completed', '2025-10-09 05:14:10'),
(2643, 47, 1132, 'Often', 243, 15100133, 41, 6, 3.00, NULL, 'completed', '2025-10-09 05:14:10');

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
(143, 243, 15100133, 'Clarence', '', 'Cabero', 149, 75, 'Faculty'),
(144, 244, 20220294, 'Aubrey', 'Camance', 'Sanchez', 149, 75, 'Faculty'),
(145, 245, 16100494, 'Kivron Shem', 'Tenio', 'Uy', 149, 75, 'Faculty'),
(146, 246, 20221237, 'Jb', 'Samalca', 'Sanoria', 149, 75, 'Faculty'),
(147, 247, 11111, 'Rogelio', '', 'Diez', 149, 75, 'Program Head'),
(148, 252, 3333, 'Rafael', '', 'Sanoria', 149, 80, 'Faculty');

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
(75, 'BSIT'),
(80, 'LAEd');

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
(37, 'Attendance in school activities', '', '', 2, 'Active', '2025-10-07 00:00:00', '2025-10-07 18:10:14', 30.00),
(40, 'peer to peer', '', 'Personal Characteristics and Working Attitudes', 1, 'Active', '2025-10-07 00:00:00', '2025-10-09 06:01:19', 70.00),
(41, 'self evaluation', '', '', 2, 'Active', '2025-10-08 00:00:00', '2025-10-09 06:01:12', 70.00),
(42, 'maintenance staff performance evaluation rating', '', 'Personal nga kinaiya ug pagpatuman sa trabaho', 1, 'Active', '2025-10-09 00:00:00', '2025-10-09 07:32:45', 70.00);

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
  `evaluation_type` enum('Self','HRtoFaculty','HRtoStaff','ProgramHeadToFaculty','FacultyPeer','HeadToStaff','StaffPeer') NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `questionnaire_assignments`
--

INSERT INTO `questionnaire_assignments` (`id`, `questionnaire_id`, `faculty_id`, `staff_id`, `department_id`, `program_id`, `curriculum_id`, `assigned_at`, `status`, `evaluation_type`) VALUES
(215, 42, NULL, NULL, NULL, NULL, 6, '2025-10-09 04:25:12', 'active', 'HeadToStaff'),
(216, 42, NULL, NULL, NULL, NULL, NULL, '2025-10-09 04:25:15', 'active', 'StaffPeer'),
(219, 41, NULL, NULL, NULL, NULL, 6, '2025-10-09 04:33:46', 'active', 'Self'),
(222, 40, NULL, NULL, NULL, 75, 6, '2025-10-09 04:34:34', 'active', 'ProgramHeadToFaculty'),
(223, 40, NULL, NULL, NULL, NULL, NULL, '2025-10-09 04:34:36', 'active', 'FacultyPeer'),
(224, 37, NULL, NULL, NULL, NULL, 6, '2025-10-09 04:38:14', 'active', 'HRtoFaculty'),
(225, 37, NULL, NULL, NULL, NULL, NULL, '2025-10-09 04:38:16', 'active', 'HRtoStaff');

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
(100, 37, 'Monday Morning Devotion', '2025-10-07 18:10:14'),
(101, 37, 'Foundation Day, Founder’s Day, Christmas Get-together, Commencements, Retreats, F/S Day, Convocations, etc.', '2025-10-07 18:10:14'),
(1083, 42, 'Nakahibalo sa detalye sa iyang trabaho.', '2025-10-09 07:34:55'),
(1084, 42, 'Nakahibalo sa detalye sa laing trabaho nga kalambigit sa iyang trabaho.', '2025-10-09 07:34:55'),
(1085, 42, 'Walay sayop kon magtrabaho.', '2025-10-09 07:34:55'),
(1086, 42, 'Kompletohon ang trabaho.', '2025-10-09 07:34:55'),
(1087, 42, 'Ibutang ang trabaho sa pinahiluna nga pamaagi.', '2025-10-09 07:34:55'),
(1088, 42, 'Hinlo nga agi sa trabaho.', '2025-10-09 07:34:55'),
(1089, 42, 'Epektibo nga planohon ang trabaho-on.', '2025-10-09 07:34:55'),
(1090, 42, 'Epektibong organisado ang pagtrabaho.', '2025-10-09 07:34:55'),
(1091, 42, 'Mopatuman sa iyang trabaho nga dili kinahanglan sultihan.', '2025-10-09 07:34:55'),
(1092, 42, 'Ihatag ang dugang panahon sa pagpatuman sa mga buluhaton.', '2025-10-09 07:34:55'),
(1103, 40, 'Knows the details of his/her job.', '2025-10-09 07:35:12'),
(1104, 40, 'Knows the details of his/her related duties', '2025-10-09 07:35:12'),
(1105, 40, 'Works accurately', '2025-10-09 07:35:12'),
(1106, 40, 'Works completely', '2025-10-09 07:35:12'),
(1107, 40, 'Puts works in an orderly manner', '2025-10-09 07:35:12'),
(1108, 40, 'Works neatly', '2025-10-09 07:35:12'),
(1109, 40, 'Plans works effectively', '2025-10-09 07:35:12'),
(1110, 40, 'Organizes works effectively', '2025-10-09 07:35:12'),
(1111, 40, 'Performs his/her duties without being told', '2025-10-09 07:35:12'),
(1112, 40, 'Concentrates more time in performing the job.', '2025-10-09 07:35:12'),
(1123, 41, 'I know the details of my job.', '2025-10-09 07:36:23'),
(1124, 41, 'I know the details of my related duties.', '2025-10-09 07:36:23'),
(1125, 41, 'I work accurately in all assigned tasks.', '2025-10-09 07:36:23'),
(1126, 41, 'I complete my work thoroughly and on time.', '2025-10-09 07:36:23'),
(1127, 41, 'I arrange my work in an orderly manner.', '2025-10-09 07:36:23'),
(1128, 41, 'I maintain neatness in my work.', '2025-10-09 07:36:23'),
(1129, 41, 'I plan my work effectively to meet deadlines.', '2025-10-09 07:36:23'),
(1130, 41, 'I organize my tasks efficiently.', '2025-10-09 07:36:23'),
(1131, 41, 'I perform my duties without being told.', '2025-10-09 07:36:23'),
(1132, 41, 'I concentrate my time and effort on performing my job.', '2025-10-09 07:36:23');

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
(15, 248, 21, 'Ambo', '', 'Royo', 'Head Staff', 154),
(16, 249, 22, 'Lady', '', 'Guard', 'Staff', 154),
(17, 250, 23, 'Kadi', '', 'Uy', 'Staff', NULL),
(18, 251, 24, 'Lilit', '', 'Royo', 'Staff', 154),
(20, 253, 44, 'fritzie', '', 'labial', 'HR', NULL);

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
(234, '123456', 'TCM2025CAP', 'Admin', 'Aubrey', '', 'Sanchez', '', '2025-10-04 08:16:19', 'Active', 'Active'),
(243, '15100133', 'TCM2025CAP', 'Regular', 'Clarence', '', 'Cabero', 'Faculty', '2025-10-08 08:35:58', 'Active', 'Active'),
(244, '20220294', 'TCM2025CAP', 'Regular', 'Aubrey', 'Camance', 'Sanchez', 'Faculty', '2025-10-08 08:37:24', 'Active', 'Active'),
(245, '16100494', 'TCM2025CAP', 'Regular', 'Kivron Shem', 'Tenio', 'Uy', 'Faculty', '2025-10-08 08:39:02', 'Active', 'Active'),
(246, '20221237', 'TCM2025CAP', 'Regular', 'Jb', 'Samalca', 'Sanoria', 'Faculty', '2025-10-08 08:40:01', 'Active', 'Active'),
(247, '11111', 'TCM2025CAP', 'Regular', 'Rogelio', '', 'Diez', 'Program Head', '2025-10-08 08:40:36', 'Active', 'Active'),
(248, '21', 'TCM2025CAP', 'Regular', 'Ambo', '', 'Royo', 'Head Staff', '2025-10-08 08:55:35', 'Active', 'Active'),
(249, '22', 'TCM2025CAP', 'Regular', 'Lady', '', 'Guard', 'Staff', '2025-10-08 09:02:06', 'Active', 'Active'),
(250, '23', 'TCM2025CAP', 'Regular', 'Kadi', '', 'Uy', 'Staff', '2025-10-08 09:02:27', 'Active', 'Active'),
(251, '24', 'TCM2025CAP', 'Regular', 'Lilit', '', 'Royo', 'Staff', '2025-10-08 09:02:46', 'Active', 'Active'),
(252, '3333', 'TCM2025CAP', 'Regular', 'Rafael', '', 'Sanoria', 'Faculty', '2025-10-08 09:23:51', 'Active', 'Active'),
(253, '44', 'TCM2025CAP', 'Regular', 'fritzie', '', 'labial', 'HR', '2025-10-08 09:28:22', 'Active', 'Active');

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
  MODIFY `curriculum_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `departments`
--
ALTER TABLE `departments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=160;

--
-- AUTO_INCREMENT for table `evaluation_responses`
--
ALTER TABLE `evaluation_responses`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2644;

--
-- AUTO_INCREMENT for table `faculty`
--
ALTER TABLE `faculty`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=149;

--
-- AUTO_INCREMENT for table `programs`
--
ALTER TABLE `programs`
  MODIFY `program_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=81;

--
-- AUTO_INCREMENT for table `questionnaires`
--
ALTER TABLE `questionnaires`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=43;

--
-- AUTO_INCREMENT for table `questionnaire_assignments`
--
ALTER TABLE `questionnaire_assignments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=226;

--
-- AUTO_INCREMENT for table `questions`
--
ALTER TABLE `questions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1133;

--
-- AUTO_INCREMENT for table `staff`
--
ALTER TABLE `staff`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=254;

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
