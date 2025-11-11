-- phpMyAdmin SQL Dump
-- version 5.1.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Nov 11, 2025 at 01:29 PM
-- Server version: 10.4.22-MariaDB
-- PHP Version: 8.1.2

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `studcollab`
--

-- --------------------------------------------------------

--
-- Table structure for table `calendar_events`
--

CREATE TABLE `calendar_events` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `group_id` int(11) DEFAULT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `event_type` enum('task','group-task','discussion') NOT NULL,
  `due_date` date NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `calendar_events`
--

INSERT INTO `calendar_events` (`id`, `user_id`, `group_id`, `title`, `description`, `event_type`, `due_date`, `created_at`, `updated_at`) VALUES
(1, 3, NULL, 'Codeit BootCamp', 'A camp for the team to strategize for the new year (2026).', 'task', '2025-11-22', '2025-11-08 15:45:17', '2025-11-08 15:45:17'),
(2, 3, NULL, 'Final Examanination Starts', 'I will be starting my final year exam.', 'task', '2025-12-08', '2025-11-08 18:33:23', '2025-11-08 18:33:23'),
(3, 5, NULL, 'Codeit BootCamp', 'Ouir annual general meeting.', 'task', '2025-11-11', '2025-11-11 11:39:44', '2025-11-11 11:39:44');

-- --------------------------------------------------------

--
-- Table structure for table `discussion_replies`
--

CREATE TABLE `discussion_replies` (
  `id` int(11) NOT NULL,
  `discussion_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `message` text NOT NULL,
  `sent_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `discussion_replies`
--

INSERT INTO `discussion_replies` (`id`, `discussion_id`, `user_id`, `message`, `sent_at`) VALUES
(1, 2, 3, 'Good morning guys. How are we doing today?', '2025-11-08 15:43:16');

-- --------------------------------------------------------

--
-- Table structure for table `groups`
--

CREATE TABLE `groups` (
  `id` int(11) NOT NULL,
  `group_name` varchar(255) NOT NULL,
  `leader_id` int(11) NOT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `groups`
--

INSERT INTO `groups` (`id`, `group_name`, `leader_id`, `created_at`) VALUES
(1, 'Project Alpha', 1, '2025-11-06 04:32:56'),
(2, 'Group 8', 3, '2025-11-06 04:59:43'),
(3, 'ghas', 3, '2025-11-06 05:26:39'),
(4, 'Codeit', 3, '2025-11-08 12:11:07'),
(5, 'Aliens', 3, '2025-11-08 12:42:50');

-- --------------------------------------------------------

--
-- Table structure for table `group_comments`
--

CREATE TABLE `group_comments` (
  `id` int(11) NOT NULL,
  `resource_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `comment` text NOT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `group_discussions`
--

CREATE TABLE `group_discussions` (
  `id` int(11) NOT NULL,
  `group_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `content` text NOT NULL,
  `message` text NOT NULL,
  `sent_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `group_discussions`
--

INSERT INTO `group_discussions` (`id`, `group_id`, `user_id`, `title`, `content`, `message`, `sent_at`) VALUES
(1, 4, 3, 'Management Information System', 'We are discussing the benefits of PEAS to business organizations.', '', '2025-11-08 13:43:41'),
(2, 4, 3, 'MIS', 'Intranets and Extranets.', '', '2025-11-08 15:41:44');

-- --------------------------------------------------------

--
-- Table structure for table `group_discussions_messages`
--

CREATE TABLE `group_discussions_messages` (
  `id` int(11) NOT NULL,
  `discussion_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `message` text NOT NULL,
  `sent_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `group_members`
--

CREATE TABLE `group_members` (
  `id` int(11) NOT NULL,
  `group_name` varchar(255) NOT NULL,
  `leader_id` int(11) NOT NULL,
  `group_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `role` enum('Leader','Member') DEFAULT 'Member',
  `added_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `group_members`
--

INSERT INTO `group_members` (`id`, `group_name`, `leader_id`, `group_id`, `user_id`, `role`, `added_at`) VALUES
(2, '', 0, 1, 1, 'Leader', '2025-11-06 10:20:51'),
(3, '', 0, 4, 3, 'Member', '2025-11-08 12:11:07'),
(4, '', 0, 5, 3, 'Member', '2025-11-08 12:42:50');

-- --------------------------------------------------------

--
-- Table structure for table `group_resources`
--

CREATE TABLE `group_resources` (
  `id` int(11) NOT NULL,
  `group_id` int(11) NOT NULL,
  `uploaded_by` int(11) NOT NULL,
  `file_name` varchar(255) NOT NULL,
  `file_path` varchar(255) NOT NULL,
  `uploaded_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `group_tasks`
--

CREATE TABLE `group_tasks` (
  `id` int(11) NOT NULL,
  `group_name` varchar(255) NOT NULL,
  `leader_id` int(11) NOT NULL,
  `group_id` int(11) DEFAULT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `due_date` date NOT NULL,
  `status` enum('Pending','Completed') DEFAULT 'Pending',
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `group_tasks`
--

INSERT INTO `group_tasks` (`id`, `group_name`, `leader_id`, `group_id`, `title`, `description`, `due_date`, `status`, `created_at`, `updated_at`) VALUES
(19, 'Group 8', 3, NULL, 'Web Development', 'We are designing a Form using HTML, CSS and PHP.', '2025-11-12', 'Pending', '2025-11-07 06:47:22', '2025-11-07 06:58:51'),
(21, 'Codeit', 3, NULL, 'Packaged Enterprise Application Software PEAS', 'Draft a research paper on Packaged Enterprise Application Software (PEAS), highlighting their benefits to business organisations.', '2025-11-17', 'Completed', '2025-11-07 13:59:39', '2025-11-07 14:00:14'),
(22, '', 0, 4, 'Web Development', 'Creating an HTML form using HTML and PHP.', '2025-11-20', 'Completed', '2025-11-08 12:11:07', '2025-11-08 12:12:07'),
(23, '', 0, 5, 'Programming', 'Coding.', '2025-11-11', 'Pending', '2025-11-08 12:42:50', '2025-11-08 12:42:50');

-- --------------------------------------------------------

--
-- Table structure for table `notes`
--

CREATE TABLE `notes` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `course` varchar(255) NOT NULL,
  `group_id` int(11) DEFAULT NULL,
  `title` varchar(255) NOT NULL,
  `content` longtext DEFAULT NULL,
  `shared` tinyint(1) DEFAULT 0,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `resources`
--

CREATE TABLE `resources` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `course` varchar(255) NOT NULL,
  `group_id` int(11) DEFAULT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `file_path` varchar(255) NOT NULL,
  `uploaded_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `tasks`
--

CREATE TABLE `tasks` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `due_date` date DEFAULT NULL,
  `status` enum('Pending','Completed') DEFAULT 'Pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `tasks`
--

INSERT INTO `tasks` (`id`, `user_id`, `title`, `description`, `due_date`, `status`, `created_at`, `updated_at`) VALUES
(4, 3, 'Management Information System', 'This assignment is based on the PEAS (Packaged Enterprise Application Software), highlighting their benefits to business organisations.', '2025-11-10', 'Completed', '2025-11-05 18:17:43', '2025-11-06 09:19:22'),
(8, 3, 'Management Information System', 'This assignment focuses on the PEAS (Packaged Enterprise Application Software), highlighting their benefits to business organisations.', '2025-11-17', 'Completed', '2025-11-06 10:51:24', '2025-11-08 05:45:27'),
(10, 3, 'Database Technology', 'We are focusing on the Entity Relationship Diagram (ERD) and Cardinality.', '2025-11-04', 'Completed', '2025-11-07 11:00:08', '2025-11-08 05:45:11'),
(11, 3, 'Architecture II', 'Adders and latches. Add diagrams.', '2025-11-16', 'Pending', '2025-11-08 05:46:51', '2025-11-08 05:47:01'),
(12, 5, 'Management Information System', 'Discussing the Packaged Enterprise Application Software (PEAS).', '2025-12-23', 'Pending', '2025-11-10 15:19:05', '2025-11-10 19:38:59'),
(13, 5, 'Programming', 'Data Structures', '2025-11-16', 'Completed', '2025-11-10 20:03:06', '2025-11-10 20:03:13'),
(14, 6, 'coding123', 'i loe', '2025-11-21', 'Pending', '2025-11-11 12:15:05', '2025-11-11 12:15:37');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `fullname` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `user_type` enum('student','lecturer') NOT NULL,
  `course` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `image` varchar(255) DEFAULT 'default-avatar.png',
  `reset_token` varchar(64) DEFAULT NULL,
  `reset_expires` datetime DEFAULT NULL,
  `remember_token` varchar(255) DEFAULT NULL,
  `remember_expiry` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `fullname`, `email`, `password`, `user_type`, `course`, `created_at`, `updated_at`, `image`, `reset_token`, `reset_expires`, `remember_token`, `remember_expiry`) VALUES
(1, 'Dr. Smith', 'dr.smith@example.com', '482c811da5d5b4bc6d497ffa98491e38', 'lecturer', NULL, '2025-11-05 17:02:48', '2025-11-05 17:02:48', 'default-avatar.png', NULL, NULL, NULL, NULL),
(2, 'John Doe', 'john.doe@example.com', '482c811da5d5b4bc6d497ffa98491e38', 'student', 'Information Technology', '2025-11-05 17:02:48', '2025-11-05 17:02:48', 'default-avatar.png', NULL, NULL, NULL, NULL),
(3, 'Fewdays Chibwe', 'fewdays8chibwe@gmail.com', '$2y$10$4uvwUjjRBUBJZ.Lt8o5Ms.t0zHNWL2jXyqgr8tML.BoW4/BMI/LYe', 'student', 'Information Technology', '2025-11-05 18:11:37', '2025-11-10 13:21:09', 'user_3_1762519918.jpg', 'a74d9a17345c688375747383f1b6473d495850c1a7f9d204b54ed1e7fd58077a', '2025-11-10 15:51:09', NULL, NULL),
(4, 'SUWILANJI MFUNGO', 'bethelrealtorslimited@gmail.com', '$2y$10$hPx3NoHDy4EyknYFYHQa2.oQEviwygey2Rd8TiwVT8T08xL47VNzG', 'student', NULL, '2025-11-09 04:42:18', '2025-11-09 04:42:18', 'default-avatar.png', NULL, NULL, NULL, NULL),
(5, 'Nelly Tembo', 'nellytembo@gmail.com', '$2y$10$CEpWSw.rpXjVKPZnaLTQtewIh6yfX4cZWP/Klvt.9BeTpR66WCYgq', 'student', NULL, '2025-11-09 05:00:15', '2025-11-09 05:19:14', 'default-avatar.png', NULL, NULL, '7b31ec56aa6b67a7c26350cdcf248b34', '2025-12-09 06:19:14'),
(6, 'Diran Sai', 'dirantechie@gmail.com', '$2y$10$av12wNgsuOiQfL1FJcDizeEAOgnBcKlCz./b.bGZXcKS5YWM.4cTa', 'student', NULL, '2025-11-11 12:03:06', '2025-11-11 12:03:06', 'default-avatar.png', NULL, NULL, NULL, NULL);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `calendar_events`
--
ALTER TABLE `calendar_events`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `group_id` (`group_id`);

--
-- Indexes for table `discussion_replies`
--
ALTER TABLE `discussion_replies`
  ADD PRIMARY KEY (`id`),
  ADD KEY `discussion_id` (`discussion_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `groups`
--
ALTER TABLE `groups`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `group_comments`
--
ALTER TABLE `group_comments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `resource_id` (`resource_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `group_discussions`
--
ALTER TABLE `group_discussions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `group_id` (`group_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `group_discussions_messages`
--
ALTER TABLE `group_discussions_messages`
  ADD PRIMARY KEY (`id`),
  ADD KEY `discussion_id` (`discussion_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `group_members`
--
ALTER TABLE `group_members`
  ADD PRIMARY KEY (`id`),
  ADD KEY `group_id` (`group_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `group_resources`
--
ALTER TABLE `group_resources`
  ADD PRIMARY KEY (`id`),
  ADD KEY `group_id` (`group_id`),
  ADD KEY `uploaded_by` (`uploaded_by`);

--
-- Indexes for table `group_tasks`
--
ALTER TABLE `group_tasks`
  ADD PRIMARY KEY (`id`),
  ADD KEY `group_id` (`group_id`);

--
-- Indexes for table `notes`
--
ALTER TABLE `notes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `group_id` (`group_id`);

--
-- Indexes for table `resources`
--
ALTER TABLE `resources`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `group_id` (`group_id`);

--
-- Indexes for table `tasks`
--
ALTER TABLE `tasks`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `calendar_events`
--
ALTER TABLE `calendar_events`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `discussion_replies`
--
ALTER TABLE `discussion_replies`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `groups`
--
ALTER TABLE `groups`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `group_comments`
--
ALTER TABLE `group_comments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `group_discussions`
--
ALTER TABLE `group_discussions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `group_discussions_messages`
--
ALTER TABLE `group_discussions_messages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `group_members`
--
ALTER TABLE `group_members`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `group_resources`
--
ALTER TABLE `group_resources`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `group_tasks`
--
ALTER TABLE `group_tasks`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

--
-- AUTO_INCREMENT for table `notes`
--
ALTER TABLE `notes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `resources`
--
ALTER TABLE `resources`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tasks`
--
ALTER TABLE `tasks`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `calendar_events`
--
ALTER TABLE `calendar_events`
  ADD CONSTRAINT `calendar_events_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `calendar_events_ibfk_2` FOREIGN KEY (`group_id`) REFERENCES `groups` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `discussion_replies`
--
ALTER TABLE `discussion_replies`
  ADD CONSTRAINT `discussion_replies_ibfk_1` FOREIGN KEY (`discussion_id`) REFERENCES `group_discussions` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `discussion_replies_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `group_comments`
--
ALTER TABLE `group_comments`
  ADD CONSTRAINT `group_comments_ibfk_1` FOREIGN KEY (`resource_id`) REFERENCES `group_resources` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `group_comments_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `group_discussions`
--
ALTER TABLE `group_discussions`
  ADD CONSTRAINT `group_discussions_ibfk_1` FOREIGN KEY (`group_id`) REFERENCES `groups` (`id`),
  ADD CONSTRAINT `group_discussions_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `group_members`
--
ALTER TABLE `group_members`
  ADD CONSTRAINT `group_members_ibfk_1` FOREIGN KEY (`group_id`) REFERENCES `groups` (`id`),
  ADD CONSTRAINT `group_members_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `group_resources`
--
ALTER TABLE `group_resources`
  ADD CONSTRAINT `group_resources_ibfk_1` FOREIGN KEY (`group_id`) REFERENCES `groups` (`id`),
  ADD CONSTRAINT `group_resources_ibfk_2` FOREIGN KEY (`uploaded_by`) REFERENCES `users` (`id`);

--
-- Constraints for table `group_tasks`
--
ALTER TABLE `group_tasks`
  ADD CONSTRAINT `group_tasks_ibfk_1` FOREIGN KEY (`group_id`) REFERENCES `groups` (`id`);

--
-- Constraints for table `notes`
--
ALTER TABLE `notes`
  ADD CONSTRAINT `notes_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `notes_ibfk_2` FOREIGN KEY (`group_id`) REFERENCES `groups` (`id`);

--
-- Constraints for table `resources`
--
ALTER TABLE `resources`
  ADD CONSTRAINT `resources_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `resources_ibfk_2` FOREIGN KEY (`group_id`) REFERENCES `groups` (`id`);

--
-- Constraints for table `tasks`
--
ALTER TABLE `tasks`
  ADD CONSTRAINT `tasks_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
