USE classorbit;

-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jan 14, 2026 at 10:11 AM
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
-- Database: `classorbit`
--

-- --------------------------------------------------------

--
-- Table structure for table `admin`
--

CREATE TABLE `admin` (
  `id` int(10) NOT NULL,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `pic` varchar(255) DEFAULT 'default.jpg'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admin`
--

INSERT INTO `admin` (`id`, `name`, `email`, `password`, `pic`) VALUES
(1, 'Default Admin', 'admin@classorbit.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'default.jpg');

-- --------------------------------------------------------

--
-- Table structure for table `chat_box`
--

CREATE TABLE `chat_box` (
  `id` int(10) NOT NULL,
  `message` varchar(255) DEFAULT NULL,
  `time` time NOT NULL,
  `admin_id` int(10) DEFAULT NULL,
  `student_id` int(11) DEFAULT NULL,
  `club_id` int(11) DEFAULT NULL,
  `faculty_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `checking`
--

CREATE TABLE `checking` (
  `id` int(10) NOT NULL,
  `start_time` datetime NOT NULL,
  `end_time` datetime NOT NULL,
  `is_available` tinyint(1) NOT NULL DEFAULT 1,
  `class_room_id` int(11) NOT NULL,
  `admin_id` int(10) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `checking`
--

INSERT INTO `checking` (`id`, `start_time`, `end_time`, `is_available`, `class_room_id`, `admin_id`, `created_at`) VALUES
(9, '2026-01-07 14:00:00', '2026-01-07 15:30:00', 1, 2, 1, '2026-01-05 08:25:27'),
(10, '2026-01-07 16:00:00', '2026-01-07 17:30:00', 1, 2, 1, '2026-01-05 08:25:27'),
(11, '2026-01-08 10:00:00', '2026-01-08 11:30:00', 1, 3, 1, '2026-01-05 08:25:27'),
(12, '2026-01-08 13:00:00', '2026-01-08 14:30:00', 1, 3, 1, '2026-01-05 08:25:27'),
(161, '2026-01-07 18:30:00', '2026-01-05 19:30:00', 0, 9, 1, '2026-01-05 12:36:16'),
(204, '2026-01-12 10:00:00', '2026-01-12 11:30:00', 1, 14, 1, '2026-01-05 12:40:09'),
(296, '2026-01-06 09:00:00', '2026-01-06 10:30:00', 0, 1, 1, '2026-01-05 12:44:39'),
(297, '2026-01-07 14:00:00', '2026-01-07 15:30:00', 0, 7, 1, '2026-01-05 12:44:39'),
(309, '2026-01-06 09:00:00', '2026-01-06 10:30:00', 0, 1, 1, '2026-01-05 12:51:42'),
(310, '2026-01-06 14:00:00', '2026-01-06 15:30:00', 1, 2, 1, '2026-01-05 12:51:42'),
(311, '2026-01-06 18:00:00', '2026-01-06 19:30:00', 1, 3, 1, '2026-01-05 12:51:42'),
(312, '2026-01-07 09:00:00', '2026-01-07 10:30:00', 1, 4, 1, '2026-01-05 12:51:42'),
(313, '2026-01-07 14:00:00', '2026-01-07 15:30:00', 1, 5, 1, '2026-01-05 12:51:42'),
(314, '2026-01-07 18:00:00', '2026-01-07 19:30:00', 1, 6, 1, '2026-01-05 12:51:42'),
(315, '2026-01-08 09:00:00', '2026-01-08 10:30:00', 0, 7, 1, '2026-01-05 12:51:42'),
(316, '2026-01-08 14:00:00', '2026-01-08 15:30:00', 1, 8, 1, '2026-01-05 12:51:42'),
(317, '2026-01-08 18:00:00', '2026-01-08 19:30:00', 1, 9, 1, '2026-01-05 12:51:42'),
(318, '2026-01-09 09:00:00', '2026-01-09 10:30:00', 1, 10, 1, '2026-01-05 12:51:42'),
(319, '2026-01-09 14:00:00', '2026-01-09 15:30:00', 1, 11, 1, '2026-01-05 12:51:42'),
(320, '2026-01-09 18:00:00', '2026-01-09 19:30:00', 1, 12, 1, '2026-01-05 12:51:42'),
(321, '2026-01-10 09:00:00', '2026-01-10 10:30:00', 1, 13, 1, '2026-01-05 12:51:42'),
(322, '2026-01-10 14:00:00', '2026-01-10 15:30:00', 1, 14, 1, '2026-01-05 12:51:42'),
(323, '2026-01-10 18:00:00', '2026-01-10 19:30:00', 0, 15, 1, '2026-01-05 12:51:42'),
(324, '2026-01-11 09:00:00', '2026-01-11 10:30:00', 1, 16, 1, '2026-01-05 12:51:42'),
(325, '2026-01-11 14:00:00', '2026-01-11 15:30:00', 1, 17, 1, '2026-01-05 12:51:42'),
(326, '2026-01-11 18:00:00', '2026-01-11 19:30:00', 1, 18, 1, '2026-01-05 12:51:42'),
(327, '2026-01-05 20:05:00', '2026-01-05 20:06:00', 0, 15, 1, '2026-01-05 14:02:57');

-- --------------------------------------------------------

--
-- Table structure for table `class_room`
--

CREATE TABLE `class_room` (
  `id` int(11) NOT NULL,
  `room_num` int(10) NOT NULL,
  `floor_num` int(10) NOT NULL,
  `capacity` int(10) NOT NULL,
  `type_name` varchar(255) NOT NULL,
  `projector` varchar(255) NOT NULL,
  `AC` varchar(255) NOT NULL,
  `speaker` varchar(255) NOT NULL,
  `any_prob` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `class_room`
--

INSERT INTO `class_room` (`id`, `room_num`, `floor_num`, `capacity`, `type_name`, `projector`, `AC`, `speaker`, `any_prob`) VALUES
(1, 101, 1, 50, 'Lecture Hall', 'Yes', 'Yes', 'Yes', NULL),
(2, 205, 2, 30, 'Seminar Room', 'Yes', 'No', 'Yes', 'Door lock faulty'),
(3, 312, 3, 25, 'Computer Lab', 'No', 'Yes', 'No', NULL),
(7, 401, 4, 60, 'Lecture Hall', 'Yes', 'Yes', 'Yes', NULL),
(8, 106, 1, 40, 'Seminar Room', 'No', 'Yes', 'No', 'Projector not working'),
(9, 213, 2, 35, 'Computer Lab', 'Yes', 'No', 'Yes', NULL),
(10, 305, 3, 80, 'Lecture Hall', 'Yes', 'Yes', 'No', 'AC leaking'),
(11, 109, 1, 25, 'Seminar Room', 'No', 'No', 'Yes', NULL),
(12, 420, 4, 50, 'Computer Lab', 'Yes', 'Yes', 'Yes', 'Door hinge broken'),
(13, 207, 2, 30, 'Seminar Room', 'No', 'Yes', 'No', NULL),
(14, 314, 3, 70, 'Lecture Hall', 'Yes', 'No', 'Yes', 'Speaker crackling'),
(15, 503, 5, 45, 'Computer Lab', 'No', 'Yes', 'No', NULL),
(16, 112, 1, 55, 'Lecture Hall', 'Yes', 'Yes', 'Yes', 'Window stuck'),
(17, 316, 3, 20, 'Seminar Room', 'No', 'No', 'No', NULL),
(18, 408, 4, 65, 'Computer Lab', 'Yes', 'Yes', 'No', 'Cable damage'),
(19, 204, 2, 40, 'Seminar Room', 'Yes', 'No', 'Yes', NULL),
(20, 515, 5, 90, 'Lecture Hall', 'Yes', 'Yes', 'Yes', 'Light flickering'),
(21, 307, 3, 28, 'Computer Lab', 'No', 'Yes', 'Yes', NULL),
(22, 701, 7, 40, 'Computer Lab', 'Yes', 'Yes', 'Yes', 'No'),
(23, 704, 7, 50, 'Lecture Hall', 'Yes', 'Yes', 'Yes', ''),
(24, 102, 1, 40, 'Lecture Hall', 'Yes', 'Yes', 'Yes', '');

-- --------------------------------------------------------

--
-- Table structure for table `club`
--

CREATE TABLE `club` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `pic` varchar(255) DEFAULT 'default.jpg',
  `clubname` varchar(255) NOT NULL,
  `priority_id` int(10) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `faculty`
--

CREATE TABLE `faculty` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `pic` varchar(255) DEFAULT 'default.jpg',
  `dept` varchar(255) NOT NULL,
  `priority_id` int(10) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `faculty`
--

INSERT INTO `faculty` (`id`, `name`, `email`, `password`, `pic`, `dept`, `priority_id`) VALUES
(1, 'Will', 'will@gmail.com', '$2y$10$TBSyUL4jJO9c9VzX4IUKvOowypb2RgnVJZeN2W5QrM8VQbsCERqK.', '1_1767630439_bloodbank.png', 'CSE', 1),
(2, 'Shadow Monstar', 'shadow@gmail.com', '$2y$10$7JpKTyR0XePVidoLt55aguBl9xwNtYq5WFtFRJJVzgKBq1dKYuciS', 'default.jpg', 'CSE', 1),
(3, 'Shadow Monstar', 'shadow1@gmail.com', '$2y$10$Q1nbBdTgUuqxIRKA8PjMEO4qNCvvQrdZGBbzu1yZuWYw3Mq/aMDYS', 'user_695c0d4cb8290.jpg', 'CSE', 1),
(4, 'Yasmin', 'yasmin@gmail.com', '$2y$10$VPngfZLm5apDmdBManLKKOWBwQ682Y/vdkDiZk4UI7nAXYsgOfpyC', 'default.jpg', 'CSE', 1),
(5, 'KK', 'k@gmail.com', '$2y$10$4WrWw6JFWdzslUE575Lywe2MqnIRx8wROeF.qrqJ1.HRJ2wM9jCNy', 'capture_25076.jpg', 'CSE', 1),
(6, 'Professor. Salma khatun', 'pska@gmail.com', '$2y$10$8JTwyIj.6Krx36zyWxYNcuvkI7GTQGs361bA.dYSVat3tz9oCaIHC', 'IMG_20241010_201232.jpg', 'CSE', 1);

-- --------------------------------------------------------

--
-- Table structure for table `priority`
--

CREATE TABLE `priority` (
  `priority_id` int(10) NOT NULL,
  `description` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `priority`
--

INSERT INTO `priority` (`priority_id`, `description`) VALUES
(1, 'High (Faculty)'),
(2, 'Medium (Club)'),
(3, 'Low (Student)');

-- --------------------------------------------------------

--
-- Table structure for table `room_booking`
--

CREATE TABLE `room_booking` (
  `id` int(10) NOT NULL,
  `user_id` int(10) NOT NULL,
  `dept` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `reason` varchar(255) NOT NULL,
  `checking_id` int(10) NOT NULL,
  `priority_id` int(10) NOT NULL,
  `urgent_needs` varchar(255) DEFAULT NULL,
  `cancel_reason` text DEFAULT NULL,
  `status` enum('pending','approved','rejected','cancelled') NOT NULL DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `room_booking`
--

INSERT INTO `room_booking` (`id`, `user_id`, `dept`, `email`, `reason`, `checking_id`, `priority_id`, `urgent_needs`, `cancel_reason`, `status`, `created_at`) VALUES
(9, 2, 'CSE', 'vecna@gmail.com', 'Group work', 9, 3, 'project setup', NULL, 'cancelled', '2026-01-05 11:59:39'),
(10, 1, 'CSE', 'will@gmail.com', 'lecture', 9, 1, '', NULL, 'approved', '2026-01-05 11:59:42'),
(11, 2, 'CSE', 'vecna@gmail.com', 'reading', 10, 3, '', NULL, 'cancelled', '2026-01-05 12:07:56'),
(12, 1, 'CSE', 'will@gmail.com', 'meeting', 10, 1, 'UV close', NULL, 'cancelled', '2026-01-05 12:08:06'),
(13, 2, 'CSE', 'vecna@gmail.com', 'Group study', 323, 3, 'project setup', NULL, 'approved', '2026-01-05 12:56:05'),
(14, 2, 'CSE', 'vecna@gmail.com', 'group work', 296, 3, 'project setup', NULL, 'approved', '2026-01-05 13:25:19'),
(15, 2, 'CSE', 'vecna@gmail.com', 'lab', 327, 3, '', NULL, 'approved', '2026-01-05 14:04:39'),
(18, 4, 'CSE', 'shurav@gmail.com', 'extra class', 309, 3, '', NULL, 'cancelled', '2026-01-05 18:10:48'),
(19, 1, 'CSE', 'will@gmail.com', 'makeup class', 309, 1, 'University is off, due some reason.', NULL, 'cancelled', '2026-01-05 18:11:03'),
(20, 17, 'CSE', 'a@gmail.com', 'Group work', 161, 3, 'Project Show', 'room nai ja bolod', 'cancelled', '2026-01-07 06:01:53'),
(21, 6, 'CSE', 'suvra1@gmail.com', 'Group Study', 297, 1, 'Preparation for upcoming midtarm exam.', NULL, 'approved', '2026-01-14 08:12:26'),
(22, 20, 'CSE', 'suvra1@gmail.com', 'CT preparation', 315, 3, '', NULL, 'cancelled', '2026-01-14 08:16:12'),
(23, 6, 'CSE', 'pska@gmail.com', 'Lecture', 315, 1, 'Makeup class', NULL, 'approved', '2026-01-14 08:16:14');

-- --------------------------------------------------------

--
-- Table structure for table `student`
--

CREATE TABLE `student` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `pic` varchar(255) DEFAULT 'default.jpg',
  `dept` varchar(255) NOT NULL,
  `priority_id` int(10) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `student`
--

INSERT INTO `student` (`id`, `name`, `email`, `password`, `pic`, `dept`, `priority_id`) VALUES
(2, 'Vecna', 'vecna@gmail.com', '$2y$10$KRqa.PTmvZsZTVs4eLcWHOw.4wIcumF3pGboTSsKqySSe82oCnAiK', 'Screenshot 2025-12-05 201614.png', 'CSE', 3),
(3, 'Shejuti Rani', 'sejuti@gmail.com', '$2y$10$bpCo/qE48OPk2ARlJmPuGeSM0ywotkE9tpTEi1P3ZnZPUUz82nDHy', '3_1767634484_0 BpJ63ERmMLAdV6cM.png', 'CSE', 3),
(4, 'Shurav Das', 'shurav@gmail.com', '$2y$10$e8b7bdQC30/7WhtwUWOg5udbJjPRRcSYfa2vM1/5/lh4M6.4bFaw.', 'uploads/users/user_695bf674330b6.png', 'CSE', 3),
(5, 'milo', 'milo@gmail.com', '$2y$10$hou4NiwXl91NwqCIQAuq.uuMSm9yuCR5ah1QEmditPi1PX4uR1SBu', 'user_695c0630a7ce3.png', 'CSE', 3),
(6, 'Suvra', 'suvra@gmail.com', '$2y$10$1HeaHMzyboO23BnBwtEUCOJLkDXq9s.Nm29VoyXMqbXrDFCKdwh8O', 'default-img.jpg', 'CSE', 3),
(7, 'Fatema yasim', 'fatema@gmail.com', '$2y$10$TENoW3sKSRcsvaniPQl8MOMgB14OflJgNbADU8Hz2g8HsCXt0/A4W', 'Doctor_Portrait_Blue_Background-removebg-preview.png', 'CSE', 3),
(8, 'milo', 'milo2@gmail.com', '$2y$10$0Hj.kKtuCw2WFSUtk6qOCei.liHqWcI/WF7FQ3U8PN8M3mEHQSEea', '01.png', 'CSE', 3),
(9, 'Hasan Ali', 'rupa@gmail.com', '$2y$10$d8YoXxO61HxLthQyIMQuRedx.826egtqRMDpHU6L4ZjP6J4B3ycGW', 'uploads/users/', 'CSE', 3),
(10, 'Hasan Ali', 'shurav1@gmail.com', '$2y$10$xi3a97UpiuN2veCGodzRue1O746zKDjzsVKQKiqOnHOJMR2JkbvVq', 'user_695c0b9ad99fa.jpg', 'CSE', 3),
(11, 'Vecna', 'vecna8@gmail.com', '$2y$10$bqYWz5tsE01FNJM1J3Gt/OsfkBoWXSb6PMx9jEfHZdEgYR1JKnHb.', 'user_695c0d9d802c4.jpeg', 'CSE', 3),
(12, 'Shejuti Rani', 'milo3@gmail.com', '$2y$10$wYA3d45kk0E3cVtcounAJu1xiztf5ewhM5V/OXGo5LR9LIeMZFpNe', '01.png', 'CSE', 3),
(13, 'sexa', 'sexa@gmail.com', '$2y$10$JSLsV4NeGqXgtnxcqD9Pm.CCq6Qvb7B1CB6Y4tw1yxTPy/vJi5kna', 'default-img.jpg', 'CSE', 3),
(14, 'Shejuti Rani', 'fatema1@gmail.com', '$2y$10$mvIrfWI/dFO6.H9EzNIZ6eKxaGwwcjNIZgO6iAwaAV/DlWhsuBxNG', 'uploads/users/', 'CSE', 3),
(15, 'Shejuti Rani', 'shurav3@gmail.com', '$2y$10$Vn.3xAXXIT9Lc13BlVSnbueX/wYm8sVyJWzG7RtmcBTUrUgTQJpvq', NULL, 'CSE', 3),
(16, 'Shejuti Rani', 'shuravdas2211@gmail.com', '$2y$10$gd8PS8nfB36F9G40VUQRN.EsxXBk/OmzPu7/9jAPFcUB0ZrGUIUAG', '01.png', 'CSE', 3),
(17, 'a', 'a@gmail.com', '$2y$10$xNn6SB/w1VxnMn5fPHSBOuFtNGQUGfsuDfVxpjGSGZ6KPhzQuZDGu', 'user_695df014c766e2.80317446.png', 'CSE', 3),
(18, 'a', 'x@gmail.com', '$2y$10$Yc5rGnTjSgbHjwhFAqJukOVOaseewbo4/OLtGj8BdzagQw1yv/P16', 'Doctor_Portrait_Blue_Background-removebg-preview_1.png', 'CSE', 3),
(19, 'ji', 'ji@gmail.com', '$2y$10$hverWIC6P.GN2VXpqI5B6eyWdvEgBx977PYS/klqTYcK4aW9gsLTm', 'Doctor_Portrait_Blue_Background.png', 'CSE', 3),
(20, 'Suvra', 'suvra1@gmail.com', '$2y$10$uBfm1dmVHa1tA73TD36XV.pGgmdPPfeawndfcdmP2x7gi6Lvv9pAO', 'Screenshot_2025-11-25_151125-removebg-preview.png', 'CSE', 3);

-- --------------------------------------------------------

--
-- Table structure for table `type`
--

CREATE TABLE `type` (
  `type_name` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `type`
--

INSERT INTO `type` (`type_name`) VALUES
('Computer Lab'),
('Lecture Hall'),
('Seminar Room');

-- --------------------------------------------------------

--
-- Table structure for table `system_settings`
--

CREATE TABLE IF NOT EXISTS `system_settings` (
    `id` INT PRIMARY KEY AUTO_INCREMENT,
    `max_booking_hours` INT DEFAULT 4,
    `advance_booking_days` INT DEFAULT 30,
    `auto_cancel_hours` INT DEFAULT 48,
    `auto_approve_faculty` BOOLEAN DEFAULT TRUE,
    `send_conflict_alerts` BOOLEAN DEFAULT TRUE,
    `allow_overtime` BOOLEAN DEFAULT TRUE,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admin`
--
ALTER TABLE `admin`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `chat_box`
--
ALTER TABLE `chat_box`
  ADD PRIMARY KEY (`id`),
  ADD KEY `FK_chat_box_admin` (`admin_id`),
  ADD KEY `FK_chat_box_student` (`student_id`),
  ADD KEY `FK_chat_box_club` (`club_id`),
  ADD KEY `FK_chat_box_faculty` (`faculty_id`);

--
-- Indexes for table `checking`
--
ALTER TABLE `checking`
  ADD PRIMARY KEY (`id`),
  ADD KEY `FK_Checking_admin` (`admin_id`);

--
-- Indexes for table `class_room`
--
ALTER TABLE `class_room`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `room_num` (`room_num`),
  ADD KEY `FK_Class_room_type` (`type_name`);

--
-- Indexes for table `club`
--
ALTER TABLE `club`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `FK_Club_priority` (`priority_id`);

--
-- Indexes for table `faculty`
--
ALTER TABLE `faculty`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `FK_Faculty_priority` (`priority_id`);

--
-- Indexes for table `priority`
--
ALTER TABLE `priority`
  ADD PRIMARY KEY (`priority_id`);

--
-- Indexes for table `room_booking`
--
ALTER TABLE `room_booking`
  ADD PRIMARY KEY (`id`),
  ADD KEY `FK_Room_Booking_checking` (`checking_id`),
  ADD KEY `FK_Room_Booking_priority` (`priority_id`);

--
-- Indexes for table `student`
--
ALTER TABLE `student`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `FK_Student_priority` (`priority_id`);

--
-- Indexes for table `type`
--
ALTER TABLE `type`
  ADD PRIMARY KEY (`type_name`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admin`
--
ALTER TABLE `admin`
  MODIFY `id` int(10) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `chat_box`
--
ALTER TABLE `chat_box`
  MODIFY `id` int(10) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `checking`
--
ALTER TABLE `checking`
  MODIFY `id` int(10) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=328;

--
-- AUTO_INCREMENT for table `class_room`
--
ALTER TABLE `class_room`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=25;

--
-- AUTO_INCREMENT for table `club`
--
ALTER TABLE `club`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `faculty`
--
ALTER TABLE `faculty`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `priority`
--
ALTER TABLE `priority`
  MODIFY `priority_id` int(10) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `room_booking`
--
ALTER TABLE `room_booking`
  MODIFY `id` int(10) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

--
-- AUTO_INCREMENT for table `student`
--
ALTER TABLE `student`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `chat_box`
--
ALTER TABLE `chat_box`
  ADD CONSTRAINT `FK_chat_box_admin` FOREIGN KEY (`admin_id`) REFERENCES `admin` (`id`),
  ADD CONSTRAINT `FK_chat_box_club` FOREIGN KEY (`club_id`) REFERENCES `club` (`id`),
  ADD CONSTRAINT `FK_chat_box_faculty` FOREIGN KEY (`faculty_id`) REFERENCES `faculty` (`id`),
  ADD CONSTRAINT `FK_chat_box_student` FOREIGN KEY (`student_id`) REFERENCES `student` (`id`);

--
-- Constraints for table `checking`
--
ALTER TABLE `checking`
  ADD CONSTRAINT `FK_Checking_admin` FOREIGN KEY (`admin_id`) REFERENCES `admin` (`id`);

--
-- Constraints for table `class_room`
--
ALTER TABLE `class_room`
  ADD CONSTRAINT `FK_Class_room_type` FOREIGN KEY (`type_name`) REFERENCES `type` (`type_name`);

--
-- Constraints for table `club`
--
ALTER TABLE `club`
  ADD CONSTRAINT `FK_Club_priority` FOREIGN KEY (`priority_id`) REFERENCES `priority` (`priority_id`);

--
-- Constraints for table `faculty`
--
ALTER TABLE `faculty`
  ADD CONSTRAINT `FK_Faculty_priority` FOREIGN KEY (`priority_id`) REFERENCES `priority` (`priority_id`);

--
-- Constraints for table `room_booking`
--
ALTER TABLE `room_booking`
  ADD CONSTRAINT `FK_Room_Booking_checking` FOREIGN KEY (`checking_id`) REFERENCES `checking` (`id`),
  ADD CONSTRAINT `FK_Room_Booking_priority` FOREIGN KEY (`priority_id`) REFERENCES `priority` (`priority_id`);

--
-- Constraints for table `student`
--
ALTER TABLE `student`
  ADD CONSTRAINT `FK_Student_priority` FOREIGN KEY (`priority_id`) REFERENCES `priority` (`priority_id`);
COMMIT;


ALTER TABLE `class_room` 
ADD COLUMN `whiteboard` VARCHAR(255) DEFAULT 'No',
ADD COLUMN `computer_lab` VARCHAR(255) DEFAULT 'No';



/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

-- --------------------------------------------------------
-- Additional Data Insertions (Clean Version)
-- --------------------------------------------------------

-- Insert initial system settings
INSERT IGNORE INTO `system_settings` (`max_booking_hours`, `advance_booking_days`, `auto_cancel_hours`, `auto_approve_faculty`, `send_conflict_alerts`, `allow_overtime`) VALUES
(4, 30, 48, 1, 1, 1);

-- Insert additional faculty members
INSERT INTO `faculty` (`name`, `email`, `password`, `pic`, `dept`, `priority_id`) VALUES
('faculty1', 'faculty1@university.edu', '$2y$10$TBSyUL4jJO9c9VzX4IUKvOowypb2RgnVJZeN2W5QrM8VQbsCERqK.', 'faculty1.jpg', 'CSE', 1),
('faculty2', 'faculty2@university.edu', '$2y$10$TBSyUL4jJO9c9VzX4IUKvOowypb2RgnVJZeN2W5QrM8VQbsCERqK.', 'faculty2.jpg', 'EEE', 1),
('faculty3', 'faculty3@university.edu', '$2y$10$TBSyUL4jJO9c9VzX4IUKvOowypb2RgnVJZeN2W5QrM8VQbsCERqK.', 'faculty3.jpg', 'Civil', 1),
('faculty4', 'faculty4@university.edu', '$2y$10$TBSyUL4jJO9c9VzX4IUKvOowypb2RgnVJZeN2W5QrM8VQbsCERqK.', 'faculty4.jpg', 'Mechanical', 1),
('faculty5', 'faculty5@university.edu', '$2y$10$TBSyUL4jJO9c9VzX4IUKvOowypb2RgnVJZeN2W5QrM8VQbsCERqK.', 'faculty5.jpg', 'Architecture', 1),
('faculty6', 'faculty6@university.edu', '$2y$10$TBSyUL4jJO9c9VzX4IUKvOowypb2RgnVJZeN2W5QrM8VQbsCERqK.', 'faculty6.jpg', 'BBA', 1),
('faculty7', 'faculty7@university.edu', '$2y$10$TBSyUL4jJO9c9VzX4IUKvOowypb2RgnVJZeN2W5QrM8VQbsCERqK.', 'faculty7.jpg', 'Law', 1),
('faculty8', 'faculty8@university.edu', '$2y$10$TBSyUL4jJO9c9VzX4IUKvOowypb2RgnVJZeN2W5QrM8VQbsCERqK.', 'faculty8.jpg', 'Pharmacy', 1),
('faculty9', 'faculty9@university.edu', '$2y$10$TBSyUL4jJO9c9VzX4IUKvOowypb2RgnVJZeN2W5QrM8VQbsCERqK.', 'faculty9.jpg', 'English', 1),
('faculty10', 'faculty10@university.edu', '$2y$10$TBSyUL4jJO9c9VzX4IUKvOowypb2RgnVJZeN2W5QrM8VQbsCERqK.', 'faculty10.jpg', 'Economics', 1),
('faculty11', 'faculty11@university.edu', '$2y$10$TBSyUL4jJO9c9VzX4IUKvOowypb2RgnVJZeN2W5QrM8VQbsCERqK.', 'faculty11.jpg', 'Physics', 1),
('faculty12', 'faculty12@university.edu', '$2y$10$TBSyUL4jJO9c9VzX4IUKvOowypb2RgnVJZeN2W5QrM8VQbsCERqK.', 'faculty12.jpg', 'Chemistry', 1),
('faculty13', 'faculty13@university.edu', '$2y$10$TBSyUL4jJO9c9VzX4IUKvOowypb2RgnVJZeN2W5QrM8VQbsCERqK.', 'faculty13.jpg', 'Mathematics', 1),
('faculty14', 'faculty14@university.edu', '$2y$10$TBSyUL4jJO9c9VzX4IUKvOowypb2RgnVJZeN2W5QrM8VQbsCERqK.', 'faculty14.jpg', 'Biology', 1),
('faculty15', 'faculty15@university.edu', '$2y$10$TBSyUL4jJO9c9VzX4IUKvOowypb2RgnVJZeN2W5QrM8VQbsCERqK.', 'faculty15.jpg', 'Sociology', 1),
('faculty16', 'faculty16@university.edu', '$2y$10$TBSyUL4jJO9c9VzX4IUKvOowypb2RgnVJZeN2W5QrM8VQbsCERqK.', 'faculty16.jpg', 'Psychology', 1),
('faculty17', 'faculty17@university.edu', '$2y$10$TBSyUL4jJO9c9VzX4IUKvOowypb2RgnVJZeN2W5QrM8VQbsCERqK.', 'faculty17.jpg', 'History', 1),
('faculty18', 'faculty18@university.edu', '$2y$10$TBSyUL4jJO9c9VzX4IUKvOowypb2RgnVJZeN2W5QrM8VQbsCERqK.', 'faculty18.jpg', 'Political Science', 1),
('faculty19', 'faculty19@university.edu', '$2y$10$TBSyUL4jJO9c9VzX4IUKvOowypb2RgnVJZeN2W5QrM8VQbsCERqK.', 'faculty19.jpg', 'Fine Arts', 1),
('faculty20', 'faculty20@university.edu', '$2y$10$TBSyUL4jJO9c9VzX4IUKvOowypb2RgnVJZeN2W5QrM8VQbsCERqK.', 'faculty20.jpg', 'Music', 1),
('faculty21', 'faculty21@university.edu', '$2y$10$TBSyUL4jJO9c9VzX4IUKvOowypb2RgnVJZeN2W5QrM8VQbsCERqK.', 'faculty21.jpg', 'Drama', 1),
('faculty22', 'faculty22@university.edu', '$2y$10$TBSyUL4jJO9c9VzX4IUKvOowypb2RgnVJZeN2W5QrM8VQbsCERqK.', 'faculty22.jpg', 'Philosophy', 1),
('faculty23', 'faculty23@university.edu', '$2y$10$TBSyUL4jJO9c9VzX4IUKvOowypb2RgnVJZeN2W5QrM8VQbsCERqK.', 'faculty23.jpg', 'Sports Science', 1),
('faculty24', 'faculty24@university.edu', '$2y$10$TBSyUL4jJO9c9VzX4IUKvOowypb2RgnVJZeN2W5QrM8VQbsCERqK.', 'faculty24.jpg', 'Anthropology', 1),
('faculty25', 'faculty25@university.edu', '$2y$10$TBSyUL4jJO9c9VzX4IUKvOowypb2RgnVJZeN2W5QrM8VQbsCERqK.', 'faculty25.jpg', 'Linguistics', 1);

-- Insert additional students
INSERT INTO `student` (`name`, `email`, `password`, `pic`, `dept`, `priority_id`) VALUES
('Student One', 'student1@university.edu', '$2y$10$KRqa.PTmvZsZTVs4eLcWHOw.4wIcumF3pGboTSsKqySSe82oCnAiK', 'student1.jpg', 'CSE', 3),
('Student Two', 'student2@university.edu', '$2y$10$KRqa.PTmvZsZTVs4eLcWHOw.4wIcumF3pGboTSsKqySSe82oCnAiK', 'student2.jpg', 'EEE', 3),
('Student Three', 'student3@university.edu', '$2y$10$KRqa.PTmvZsZTVs4eLcWHOw.4wIcumF3pGboTSsKqySSe82oCnAiK', 'student3.jpg', 'Civil', 3),
('Student Four', 'student4@university.edu', '$2y$10$KRqa.PTmvZsZTVs4eLcWHOw.4wIcumF3pGboTSsKqySSe82oCnAiK', 'student4.jpg', 'Mechanical', 3),
('Student Five', 'student5@university.edu', '$2y$10$KRqa.PTmvZsZTVs4eLcWHOw.4wIcumF3pGboTSsKqySSe82oCnAiK', 'student5.jpg', 'Architecture', 3),
('Student Six', 'student6@university.edu', '$2y$10$KRqa.PTmvZsZTVs4eLcWHOw.4wIcumF3pGboTSsKqySSe82oCnAiK', 'student6.jpg', 'BBA', 3),
('Student Seven', 'student7@university.edu', '$2y$10$KRqa.PTmvZsZTVs4eLcWHOw.4wIcumF3pGboTSsKqySSe82oCnAiK', 'student7.jpg', 'Law', 3),
('Student Eight', 'student8@university.edu', '$2y$10$KRqa.PTmvZsZTVs4eLcWHOw.4wIcumF3pGboTSsKqySSe82oCnAiK', 'student8.jpg', 'Pharmacy', 3),
('Student Nine', 'student9@university.edu', '$2y$10$KRqa.PTmvZsZTVs4eLcWHOw.4wIcumF3pGboTSsKqySSe82oCnAiK', 'student9.jpg', 'English', 3),
('Student Ten', 'student10@university.edu', '$2y$10$KRqa.PTmvZsZTVs4eLcWHOw.4wIcumF3pGboTSsKqySSe82oCnAiK', 'student10.jpg', 'Economics', 3),
('Student Eleven', 'student11@university.edu', '$2y$10$KRqa.PTmvZsZTVs4eLcWHOw.4wIcumF3pGboTSsKqySSe82oCnAiK', 'student11.jpg', 'Physics', 3),
('Student Twelve', 'student12@university.edu', '$2y$10$KRqa.PTmvZsZTVs4eLcWHOw.4wIcumF3pGboTSsKqySSe82oCnAiK', 'student12.jpg', 'Chemistry', 3),
('Student Thirteen', 'student13@university.edu', '$2y$10$KRqa.PTmvZsZTVs4eLcWHOw.4wIcumF3pGboTSsKqySSe82oCnAiK', 'student13.jpg', 'Mathematics', 3),
('Student Fourteen', 'student14@university.edu', '$2y$10$KRqa.PTmvZsZTVs4eLcWHOw.4wIcumF3pGboTSsKqySSe82oCnAiK', 'student14.jpg', 'Biology', 3),
('Student Fifteen', 'student15@university.edu', '$2y$10$KRqa.PTmvZsZTVs4eLcWHOw.4wIcumF3pGboTSsKqySSe82oCnAiK', 'student15.jpg', 'Sociology', 3),
('Student Sixteen', 'student16@university.edu', '$2y$10$KRqa.PTmvZsZTVs4eLcWHOw.4wIcumF3pGboTSsKqySSe82oCnAiK', 'student16.jpg', 'Psychology', 3),
('Student Seventeen', 'student17@university.edu', '$2y$10$KRqa.PTmvZsZTVs4eLcWHOw.4wIcumF3pGboTSsKqySSe82oCnAiK', 'student17.jpg', 'History', 3),
('Student Eighteen', 'student18@university.edu', '$2y$10$KRqa.PTmvZsZTVs4eLcWHOw.4wIcumF3pGboTSsKqySSe82oCnAiK', 'student18.jpg', 'Political Science', 3),
('Student Nineteen', 'student19@university.edu', '$2y$10$KRqa.PTmvZsZTVs4eLcWHOw.4wIcumF3pGboTSsKqySSe82oCnAiK', 'student19.jpg', 'Fine Arts', 3),
('Student Twenty', 'student20@university.edu', '$2y$10$KRqa.PTmvZsZTVs4eLcWHOw.4wIcumF3pGboTSsKqySSe82oCnAiK', 'student20.jpg', 'Music', 3),
('Student Twenty One', 'student21@university.edu', '$2y$10$KRqa.PTmvZsZTVs4eLcWHOw.4wIcumF3pGboTSsKqySSe82oCnAiK', 'student21.jpg', 'Drama', 3),
('Student Twenty Two', 'student22@university.edu', '$2y$10$KRqa.PTmvZsZTVs4eLcWHOw.4wIcumF3pGboTSsKqySSe82oCnAiK', 'student22.jpg', 'Philosophy', 3),
('Student Twenty Three', 'student23@university.edu', '$2y$10$KRqa.PTmvZsZTVs4eLcWHOw.4wIcumF3pGboTSsKqySSe82oCnAiK', 'student23.jpg', 'Sports Science', 3),
('Student Twenty Four', 'student24@university.edu', '$2y$10$KRqa.PTmvZsZTVs4eLcWHOw.4wIcumF3pGboTSsKqySSe82oCnAiK', 'student24.jpg', 'Anthropology', 3),
('Student Twenty Five', 'student25@university.edu', '$2y$10$KRqa.PTmvZsZTVs4eLcWHOw.4wIcumF3pGboTSsKqySSe82oCnAiK', 'student25.jpg', 'Linguistics', 3),
('Student Twenty Six', 'student26@university.edu', '$2y$10$KRqa.PTmvZsZTVs4eLcWHOw.4wIcumF3pGboTSsKqySSe82oCnAiK', 'student26.jpg', 'CSE', 3),
('Student Twenty Seven', 'student27@university.edu', '$2y$10$KRqa.PTmvZsZTVs4eLcWHOw.4wIcumF3pGboTSsKqySSe82oCnAiK', 'student27.jpg', 'EEE', 3),
('Student Twenty Eight', 'student28@university.edu', '$2y$10$KRqa.PTmvZsZTVs4eLcWHOw.4wIcumF3pGboTSsKqySSe82oCnAiK', 'student28.jpg', 'Civil', 3),
('Student Twenty Nine', 'student29@university.edu', '$2y$10$KRqa.PTmvZsZTVs4eLcWHOw.4wIcumF3pGboTSsKqySSe82oCnAiK', 'student29.jpg', 'Mechanical', 3),
('Student Thirty', 'student30@university.edu', '$2y$10$KRqa.PTmvZsZTVs4eLcWHOw.4wIcumF3pGboTSsKqySSe82oCnAiK', 'student30.jpg', 'Architecture', 3),
('Student Thirty One', 'student31@university.edu', '$2y$10$KRqa.PTmvZsZTVs4eLcWHOw.4wIcumF3pGboTSsKqySSe82oCnAiK', 'student31.jpg', 'BBA', 3),
('Student Thirty Two', 'student32@university.edu', '$2y$10$KRqa.PTmvZsZTVs4eLcWHOw.4wIcumF3pGboTSsKqySSe82oCnAiK', 'student32.jpg', 'Law', 3),
('Student Thirty Three', 'student33@university.edu', '$2y$10$KRqa.PTmvZsZTVs4eLcWHOw.4wIcumF3pGboTSsKqySSe82oCnAiK', 'student33.jpg', 'Pharmacy', 3),
('Student Thirty Four', 'student34@university.edu', '$2y$10$KRqa.PTmvZsZTVs4eLcWHOw.4wIcumF3pGboTSsKqySSe82oCnAiK', 'student34.jpg', 'English', 3),
('Student Thirty Five', 'student35@university.edu', '$2y$10$KRqa.PTmvZsZTVs4eLcWHOw.4wIcumF3pGboTSsKqySSe82oCnAiK', 'student35.jpg', 'Economics', 3);

-- Insert clubs
INSERT INTO `club` (`name`, `email`, `password`, `pic`, `clubname`, `priority_id`) VALUES
('Club President One', 'club1@university.edu', '$2y$10$TBSyUL4jJO9c9VzX4IUKvOowypb2RgnVJZeN2W5QrM8VQbsCERqK.', 'club1.jpg', 'Debating Club', 2),
('Club President Two', 'club2@university.edu', '$2y$10$TBSyUL4jJO9c9VzX4IUKvOowypb2RgnVJZeN2W5QrM8VQbsCERqK.', 'club2.jpg', 'Programming Club', 2),
('Club President Three', 'club3@university.edu', '$2y$10$TBSyUL4jJO9c9VzX4IUKvOowypb2RgnVJZeN2W5QrM8VQbsCERqK.', 'club3.jpg', 'Robotics Club', 2),
('Club President Four', 'club4@university.edu', '$2y$10$TBSyUL4jJO9c9VzX4IUKvOowypb2RgnVJZeN2W5QrM8VQbsCERqK.', 'club4.jpg', 'Cultural Club', 2),
('Club President Five', 'club5@university.edu', '$2y$10$TBSyUL4jJO9c9VzX4IUKvOowypb2RgnVJZeN2W5QrM8VQbsCERqK.', 'club5.jpg', 'Sports Club', 2),
('Club President Six', 'club6@university.edu', '$2y$10$TBSyUL4jJO9c9VzX4IUKvOowypb2RgnVJZeN2W5QrM8VQbsCERqK.', 'club6.jpg', 'Photography Club', 2),
('Club President Seven', 'club7@university.edu', '$2y$10$TBSyUL4jJO9c9VzX4IUKvOowypb2RgnVJZeN2W5QrM8VQbsCERqK.', 'club7.jpg', 'Music Club', 2),
('Club President Eight', 'club8@university.edu', '$2y$10$TBSyUL4jJO9c9VzX4IUKvOowypb2RgnVJZeN2W5QrM8VQbsCERqK.', 'club8.jpg', 'Drama Club', 2),
('Club President Nine', 'club9@university.edu', '$2y$10$TBSyUL4jJO9c9VzX4IUKvOowypb2RgnVJZeN2W5QrM8VQbsCERqK.', 'club9.jpg', 'Entrepreneurship Club', 2),
('Club President Ten', 'club10@university.edu', '$2y$10$TBSyUL4jJO9c9VzX4IUKvOowypb2RgnVJZeN2W5QrM8VQbsCERqK.', 'club10.jpg', 'Environmental Club', 2),
('Club President Eleven', 'club11@university.edu', '$2y$10$TBSyUL4jJO9c9VzX4IUKvOowypb2RgnVJZeN2W5QrM8VQbsCERqK.', 'club11.jpg', 'Literary Club', 2),
('Club President Twelve', 'club12@university.edu', '$2y$10$TBSyUL4jJO9c9VzX4IUKvOowypb2RgnVJZeN2W5QrM8VQbsCERqK.', 'club12.jpg', 'Science Club', 2),
('Club President Thirteen', 'club13@university.edu', '$2y$10$TBSyUL4jJO9c9VzX4IUKvOowypb2RgnVJZeN2W5QrM8VQbsCERqK.', 'club13.jpg', 'Film Club', 2),
('Club President Fourteen', 'club14@university.edu', '$2y$10$TBSyUL4jJO9c9VzX4IUKvOowypb2RgnVJZeN2W5QrM8VQbsCERqK.', 'club14.jpg', 'Dance Club', 2),
('Club President Fifteen', 'club15@university.edu', '$2y$10$TBSyUL4jJO9c9VzX4IUKvOowypb2RgnVJZeN2W5QrM8VQbsCERqK.', 'club15.jpg', 'Chess Club', 2),
('Club President Sixteen', 'club16@university.edu', '$2y$10$TBSyUL4jJO9c9VzX4IUKvOowypb2RgnVJZeN2W5QrM8VQbsCERqK.', 'club16.jpg', 'Volunteer Club', 2),
('Club President Seventeen', 'club17@university.edu', '$2y$10$TBSyUL4jJO9c9VzX4IUKvOowypb2RgnVJZeN2W5QrM8VQbsCERqK.', 'club17.jpg', 'AI & ML Club', 2),
('Club President Eighteen', 'club18@university.edu', '$2y$10$TBSyUL4jJO9c9VzX4IUKvOowypb2RgnVJZeN2W5QrM8VQbsCERqK.', 'club18.jpg', 'Cybersecurity Club', 2),
('Club President Nineteen', 'club19@university.edu', '$2y$10$TBSyUL4jJO9c9VzX4IUKvOowypb2RgnVJZeN2W5QrM8VQbsCERqK.', 'club19.jpg', 'Business Club', 2),
('Club President Twenty', 'club20@university.edu', '$2y$10$TBSyUL4jJO9c9VzX4IUKvOowypb2RgnVJZeN2W5QrM8VQbsCERqK.', 'club20.jpg', 'Arts & Crafts Club', 2),
('Club President Twenty One', 'club21@university.edu', '$2y$10$TBSyUL4jJO9c9VzX4IUKvOowypb2RgnVJZeN2W5QrM8VQbsCERqK.', 'club21.jpg', 'Meditation Club', 2),
('Club President Twenty Two', 'club22@university.edu', '$2y$10$TBSyUL4jJO9c9VzX4IUKvOowypb2RgnVJZeN2W5QrM8VQbsCERqK.', 'club22.jpg', 'Language Club', 2),
('Club President Twenty Three', 'club23@university.edu', '$2y$10$TBSyUL4jJO9c9VzX4IUKvOowypb2RgnVJZeN2W5QrM8VQbsCERqK.', 'club23.jpg', 'Astronomy Club', 2),
('Club President Twenty Four', 'club24@university.edu', '$2y$10$TBSyUL4jJO9c9VzX4IUKvOowypb2RgnVJZeN2W5QrM8VQbsCERqK.', 'club24.jpg', 'Gaming Club', 2),
('Club President Twenty Five', 'club25@university.edu', '$2y$10$TBSyUL4jJO9c9VzX4IUKvOowypb2RgnVJZeN2W5QrM8VQbsCERqK.', 'club25.jpg', 'Fitness Club', 2);

-- Insert additional admin accounts
INSERT IGNORE INTO `admin` (`id`, `name`, `email`, `password`, `pic`) VALUES
(2, 'CSE Department Admin', 'cse.admin@classorbit.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'cse_admin.jpg'),
(3, 'EEE Department Admin', 'eee.admin@classorbit.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'eee_admin.jpg'),
(4, 'University Admin', 'university.admin@classorbit.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'university_admin.jpg');

-- Create additional checking slots for new bookings
INSERT INTO `checking` (`start_time`, `end_time`, `is_available`, `class_room_id`, `admin_id`, `created_at`) VALUES
('2026-01-10 09:00:00', '2026-01-10 10:30:00', 0, 2, 1, NOW()),
('2026-01-10 10:00:00', '2026-01-10 11:30:00', 0, 3, 1, NOW()),
('2026-01-10 11:00:00', '2026-01-10 12:30:00', 0, 4, 1, NOW()),
('2026-01-10 14:00:00', '2026-01-10 15:30:00', 0, 5, 1, NOW()),
('2026-01-10 15:00:00', '2026-01-10 16:30:00', 0, 6, 1, NOW()),
('2026-01-10 16:00:00', '2026-01-10 17:30:00', 0, 6, 1, NOW()),
('2026-01-12 09:00:00', '2026-01-12 10:30:00', 0, 2, 1, NOW()),
('2026-01-12 10:00:00', '2026-01-12 11:30:00', 0, 3, 1, NOW()),
('2026-01-12 11:00:00', '2026-01-12 12:30:00', 0, 4, 1, NOW()),
('2026-01-12 14:00:00', '2026-01-12 15:30:00', 0, 5, 1, NOW()),
('2026-01-12 15:00:00', '2026-01-12 16:30:00', 0, 6, 1, NOW());

-- Insert additional room bookings
INSERT INTO `room_booking` (`user_id`, `dept`, `email`, `reason`, `checking_id`, `priority_id`, `urgent_needs`, `status`, `created_at`) VALUES
-- Faculty bookings
(1, 'CSE', 'will@gmail.com', 'Machine Learning Lecture', 328, 1, 'Need projector and internet', 'approved', '2026-01-10 09:00:00'),
(2, 'EEE', 'shadow@gmail.com', 'Circuit Design Lab', 329, 1, 'Oscilloscope required', 'approved', '2026-01-10 10:00:00'),
(3, 'Civil', 'shadow1@gmail.com', 'Structural Analysis Class', 330, 1, 'Whiteboard markers', 'pending', '2026-01-10 11:00:00'),
(4, 'Mechanical', 'yasmin@gmail.com', 'Thermodynamics Tutorial', 331, 1, 'AC must be working', 'approved', '2026-01-10 14:00:00'),
(5, 'Architecture', 'k@gmail.com', 'Architectural Design Class', 332, 1, 'Drawing boards needed', 'approved', '2026-01-10 15:00:00'),
(6, 'CSE', 'pska@gmail.com', 'Database Systems Lecture', 333, 1, 'Computer lab required', 'approved', '2026-01-10 16:00:00'),

-- Student bookings
(2, 'CSE', 'vecna@gmail.com', 'Group Project Meeting', 334, 3, 'Need multiple power outlets', 'pending', '2026-01-11 09:00:00'),
(3, 'EEE', 'sejuti@gmail.com', 'Study Group Session', 335, 3, 'Quiet environment needed', 'approved', '2026-01-11 10:00:00'),
(4, 'Civil', 'shurav@gmail.com', 'Assignment Discussion', 336, 3, 'Large table required', 'pending', '2026-01-11 11:00:00'),
(5, 'Mechanical', 'milo@gmail.com', 'Exam Preparation', 337, 3, 'Whiteboard needed', 'approved', '2026-01-11 14:00:00'),
(6, 'CSE', 'suvra@gmail.com', 'Programming Practice', 338, 3, 'Need computers', 'pending', '2026-01-11 15:00:00'),

-- Club bookings
(1, 'CSE', 'club1@university.edu', 'Debate Competition Practice', 339, 2, 'Timer and microphone needed', 'approved', '2026-01-12 09:00:00'),
(2, 'CSE', 'club2@university.edu', 'Programming Workshop', 340, 2, 'Projector and computers', 'approved', '2026-01-12 10:00:00'),
(3, 'CSE', 'club3@university.edu', 'Robot Building Workshop', 341, 2, 'Need multiple tables', 'approved', '2026-01-12 11:00:00'),
(4, 'CSE', 'club4@university.edu', 'Cultural Program Rehearsal', 342, 2, 'Need sound system', 'pending', '2026-01-12 14:00:00'),
(5, 'CSE', 'club5@university.edu', 'Sports Meeting', 343, 2, 'Open space needed', 'approved', '2026-01-12 15:00:00'),

-- Conflict examples
(1, 'CSE', 'will@gmail.com', 'Faculty Meeting', 328, 1, 'Conference setup', 'approved', '2026-01-10 09:30:00'),
(2, 'CSE', 'vecna@gmail.com', 'Student Group Study', 328, 3, 'Need quiet space', 'approved', '2026-01-10 09:30:00');




