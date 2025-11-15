-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Nov 15, 2025 at 12:15 PM
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
-- Database: `studycollab`
--

-- --------------------------------------------------------

--
-- Table structure for table `ai_chat_history`
--

CREATE TABLE `ai_chat_history` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `role` enum('user','ai') NOT NULL,
  `message` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `course` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `assessments`
--

CREATE TABLE `assessments` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `type` enum('Exam','Assignment') NOT NULL,
  `title` varchar(255) NOT NULL,
  `course` varchar(255) DEFAULT NULL,
  `due_date` datetime NOT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `assessments`
--

INSERT INTO `assessments` (`id`, `user_id`, `type`, `title`, `course`, `due_date`, `notes`, `created_at`) VALUES
(1, 7, 'Exam', 'MIS Exam', 'Management Information System', '2025-12-10 08:25:00', '', '2025-11-15 01:25:32');

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `calendar_events`
--

INSERT INTO `calendar_events` (`id`, `user_id`, `group_id`, `title`, `description`, `event_type`, `due_date`, `created_at`, `updated_at`) VALUES
(1, 3, NULL, 'Codeit BootCamp', 'A camp for the team to strategize for the new year (2026).', 'task', '2025-11-22', '2025-11-08 15:45:17', '2025-11-08 15:45:17'),
(2, 3, NULL, 'Final Examanination Starts', 'I will be starting my final year exam.', 'task', '2025-12-08', '2025-11-08 18:33:23', '2025-11-08 18:33:23'),
(3, 5, NULL, 'Codeit BootCamp', 'Ouir annual general meeting.', 'task', '2025-11-11', '2025-11-11 11:39:44', '2025-11-11 11:39:44'),
(4, 5, NULL, 'Good Day', 'Hello world', 'task', '2025-11-21', '2025-11-12 08:10:32', '2025-11-12 08:10:32'),
(5, 3, NULL, 'Movie Date with Ruth & Jimmy - Manda Hill', 'A movie outing just to relax and have fun', 'task', '2025-12-20', '2025-11-12 16:49:10', '2025-11-12 16:49:10');

-- --------------------------------------------------------

--
-- Table structure for table `discussion_replies`
--

CREATE TABLE `discussion_replies` (
  `id` int(11) NOT NULL,
  `discussion_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `sender_name` varchar(100) DEFAULT NULL,
  `message` text NOT NULL,
  `sent_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `discussion_replies`
--

INSERT INTO `discussion_replies` (`id`, `discussion_id`, `user_id`, `sender_name`, `message`, `sent_at`) VALUES
(1, 2, 3, NULL, 'Good morning guys. How are we doing today?', '2025-11-08 15:43:16');

-- --------------------------------------------------------

--
-- Table structure for table `groups`
--

CREATE TABLE `groups` (
  `id` int(11) NOT NULL,
  `group_name` varchar(255) NOT NULL,
  `leader_id` int(11) NOT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `groups`
--

INSERT INTO `groups` (`id`, `group_name`, `leader_id`, `created_at`) VALUES
(1, 'Project Alpha', 1, '2025-11-06 04:32:56'),
(2, 'Group 8', 3, '2025-11-06 04:59:43'),
(3, 'ghas', 3, '2025-11-06 05:26:39'),
(4, 'Codeit', 3, '2025-11-08 12:11:07'),
(5, 'Aliens', 3, '2025-11-08 12:42:50'),
(6, 'group6', 3, '2025-11-12 12:32:55'),
(7, 'group6', 5, '2025-11-12 13:33:48'),
(8, 'group6', 7, '2025-11-13 10:26:17');

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `group_members`
--

INSERT INTO `group_members` (`id`, `group_name`, `leader_id`, `group_id`, `user_id`, `role`, `added_at`) VALUES
(2, '', 0, 1, 1, 'Leader', '2025-11-06 10:20:51'),
(3, '', 0, 4, 3, 'Member', '2025-11-08 12:11:07'),
(4, '', 0, 5, 3, 'Member', '2025-11-08 12:42:50'),
(5, 'group6', 3, 6, 3, 'Leader', '2025-11-12 12:32:55'),
(6, 'group6', 5, 7, 5, 'Leader', '2025-11-12 13:33:48'),
(7, 'group6', 7, 8, 7, 'Leader', '2025-11-13 10:26:17');

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `group_tasks`
--

INSERT INTO `group_tasks` (`id`, `group_name`, `leader_id`, `group_id`, `title`, `description`, `due_date`, `status`, `created_at`, `updated_at`) VALUES
(19, 'Group 8', 3, NULL, 'Web Development', 'We are designing a Form using HTML, CSS and PHP.', '2025-11-12', 'Pending', '2025-11-07 06:47:22', '2025-11-07 06:58:51'),
(21, 'Codeit', 3, NULL, 'Packaged Enterprise Application Software PEAS', 'Draft a research paper on Packaged Enterprise Application Software (PEAS), highlighting their benefits to business organisations.', '2025-11-17', 'Completed', '2025-11-07 13:59:39', '2025-11-07 14:00:14'),
(24, 'group6', 3, 6, 'coding 123h', 'hello', '2025-11-15', 'Pending', '2025-11-12 12:32:55', '2025-11-12 12:32:55'),
(25, 'group6', 5, 7, 'kahjahdja', 'skfjkhfjkahf', '2025-11-02', 'Pending', '2025-11-12 13:33:48', '2025-11-12 13:33:48');

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `notes`
--

INSERT INTO `notes` (`id`, `user_id`, `course`, `group_id`, `title`, `content`, `shared`, `created_at`) VALUES
(1, 7, 'Programming', NULL, 'On Decisions Trees', '<p>Coding</p>', 0, '2025-11-14 15:57:25'),
(2, 7, 'Cyber Security', NULL, 'coding world', '<p><u>coding</u></p>', 0, '2025-11-14 16:01:46'),
(3, 7, 'Management Information System', NULL, 'MIS', '<p class=\"ql-align-justify\"><strong>Introduction</strong></p><p class=\"ql-align-justify\"><strong>Manager-</strong>a person who controls an organisation or part of the organisation or a person who looks after the business affairs</p><p class=\"ql-align-justify\"><strong>Management</strong>- It is the control of the organisation or something</p><p class=\"ql-align-justify\"><strong>System-</strong>a set of rules or ideas for organising something or set of modules that are integrated or designed to achieve a particular purpose</p><p class=\"ql-align-justify\">MIS is a system or process or process that provides the information necessary to manage an organisation effectively. MIS intersects Technology and business, information is said to be the “Life Blood” of all organisation. It is considered essential components of prudent and reasonable decision making. It is referred to as computer based systems that provides managers with tools for organising, evaluating and effectively running their departments.</p><p class=\"ql-align-justify\">MIS is viewed at many levels by management; it must be supportive of the organisations long term strategies.</p><p class=\"ql-align-justify\">MIS should be designed to achieve the following</p><p class=\"ql-align-justify\">ü&nbsp;Enhance communication among employees</p><p class=\"ql-align-justify\">ü&nbsp;Deliver complex materials throughout the organisation</p><p class=\"ql-align-justify\">ü&nbsp;Provide an objective system for recording and aggregating information</p><p class=\"ql-align-justify\">ü&nbsp;Reduce expenses related to manual activities</p><p class=\"ql-align-justify\">ü&nbsp;Support the organisation’s strategic goals and direction</p><p class=\"ql-align-justify\">ü&nbsp;MIS supplies decision makers with facts, it supports and enhances the overall decision making process and that enhance job performance</p><p class=\"ql-align-justify\">Within companies and large organisations the departments responsible for computer systems is sometimes called MIS</p><p class=\"ql-align-justify\">MIS consists of hardware and software that are used to process information automatically. Simple example of MIS:-</p><p class=\"ql-align-justify\">ü&nbsp;A computer system that process orders for a business can be considered MIS because it is assisting users of the system in automatically processing information related to the orders.</p><p class=\"ql-align-justify\">ü&nbsp;Websites that processes transactions&nbsp;for an organisations even those that serve support request to customers are modern MIS</p><p class=\"ql-align-justify\">ü&nbsp;Support websites&nbsp;for a products could be considered MIS</p><p class=\"ql-align-justify\">ü&nbsp;Online bill pay</p><p class=\"ql-align-justify\"><strong>Management Information System and Information System</strong></p><p class=\"ql-align-justify\"><em>Management Information System</em><strong>:</strong>&nbsp;it is a system that converts data from internal and external sources into information and to communicate that information in an appropriate form to managers at all levels&nbsp;in all functions to enable them to make timely and effective decisions for planning,&nbsp;directing and controlling the activities for which they are responsible.</p><p class=\"ql-align-justify\"> &nbsp;</p><p class=\"ql-align-justify\"><em>Information System</em>: is any organised combination of people, hardware, software, data resources, communication networks, policies and procedures that store, retrieve and disseminate information in an organisation.</p><p><strong>ROLE OF MANAGEMENT INFORMATION SYSTEM</strong></p><p class=\"ql-align-justify\">The role of the MIS in an organization can be compared to the role of heart in the body. The information is the blood and MIS is the heart. In the body the heart plays the role of supplying pure blood to all the elements of the body including the brain. The heart work faster and supplies more blood when needed. It regulates and controls the incoming impure blood, processed it and sends it to the destination in the quantity needed. It fulfils the needs of blood supply to human body in normal course and also in crisis.</p><p class=\"ql-align-justify\">The MIS plays exactly the same role in the organization. The system ensures that an appropriate data is collected from the various sources, processed and send further to all the needy destinations. The system is expected to fulfil the information needs of an individual, a group of individuals, the management functionaries: the managers and top management.</p><p class=\"ql-align-justify\">Here are some of the important roles of the Management Information System:</p><p class=\"ql-align-justify\">i. The MIS satisfies the diverse needs through variety of systems such as query system, analysis system, modelling system and decision support system.</p><p class=\"ql-align-justify\">ii. The MIS helps in strategic planning, management control, operational control and transaction processing. The MIS helps in the clerical personal in the transaction processing and answers the queries on the data pertaining to the transaction, the status of a particular record and reference on a variety of documents.</p><p class=\"ql-align-justify\">iii. The MIS helps the junior management personnel by providing the operational data for planning, scheduling and control , and helps them further in decision-making at the operation level to correct an out of control situation.</p><p class=\"ql-align-justify\">iv. The MIS helps the middle management in short term planning, target setting and controlling the business functions. It is supported by the use of the management tools of planning and control.</p><p class=\"ql-align-justify\">v. The MIS helps the top level management in goal setting, strategic planning and evolving the business plans and their implementation.</p><p class=\"ql-align-justify\">vi. The MIS plays the role of information generation, communication, problem identification and helps in the process of decision-making. The MIS, therefore, plays a vital role in the management, administration and operation of an organization.</p><p class=\"ql-align-justify\"><strong>IMPACT OF THE MANAGEMENT INFORMATION SYSTEM</strong></p><p class=\"ql-align-justify\">MIS plays a very important role in the organization; it creates an impact on the organization’s functions, performance and productivity.</p><p class=\"ql-align-justify\">The impact of MIS on the functions is in its management, a good MIS supports the management of marketing, finance, and production and personnel becomes more efficient. The tracking and monitoring of the functional targets becomes easy. The functional managers are informed about the progress, achievements and shortfalls in the activity and the targets. The manager is kept alert by providing certain information indicating and probable trends in the various aspects of business. This helps in forecasting and long-term perspective planning. The manager’s attention is brought to a situation which is expected in nature, inducing him to take an action or a decision in the matter. Disciplined information reporting system creates structure database and a knowledge base for all the people in the organization. The information is available in such a form that it can be used straight away by blending and analysis, saving the manager’s valuable time.</p><p class=\"ql-align-justify\">The MIS creates another impact in the organization which relates to the understanding of the business itself. The MIS begins with the definition of data, entity and its attributes. It uses a dictionary of data, entity and attributes, respectively, designed for information generation in the organization. Since all the information systems use the dictionary, there is common understanding of terms and terminology in the organization bringing clarity in the communication and a similar understanding of an event in the organization.</p><p class=\"ql-align-justify\">The MIS calls for a systematization of the business operations for an effective system design. This leads to streaming of the operations which complicates the system design. It improves the administration of the business by bringing a discipline in its operations as everybody is required to follow and use systems and procedures. This process brings a high degree of professionalism in the business operations.</p><p class=\"ql-align-justify\">The goals and objectives of the MIS are the products of business goals and objectives. It helps indirectly to pull the entire organization in one direction towards the corporate goals and objectives by providing the relevant information to the organization.</p><p class=\"ql-align-justify\">A well designed system with a focus on the manager makes an impact on the managerial efficiency. The fund of information motivates an enlightened manager to use a variety of tools of the management. It helps him to resort to such exercises as experimentation and modelling. The use of computers enables him to use the tools and techniques which are impossible to use manually. The ready-made packages make this task simple. The impact is on the managerial ability to perform. It improves decision-making ability considerably high.</p><p class=\"ql-align-justify\">Since, the MIS work on the basic system such as transaction processing and database, the clerical work is transferred to the computerized system, relieving the human mind for better work. It will be observed that lot of manpower is engaged in this activity in the organization. Seventy (70) percent of the time is spent in recording, searching, processing and communicating. This MIS has a direct impact on this overhead. It creates information –based working culture in the organization. </p><p class=\"ql-align-justify\"><strong>IMPORTANCE OF MANAGEMENT INFORMATION SYSTEM</strong></p><p class=\"ql-align-justify\">It goes without saying that all managerial functions are performed through decision-making; for taking rational decision, timely and reliable information is essential and is procured through a logical and well-structured method of information collecting, processing and disseminating to decision makers. Such a method in the field of management is widely known as MIS. In today’s world of ever increasing complexities of business as well as business organization, in order to service and grow , must have a properly planned, analysed, designed and maintained MIS so that it provides timely, reliable and useful information to enable the management to take speedy and rational decisions.</p><p class=\"ql-align-justify\">MIS has assumed all the more important role in today’s environment because a manager has to take decisions under two main challenges:</p><p class=\"ql-align-justify\">First, because of the liberalization and globalization, in which organizations are required to compete not locally but globally, a manager has to take quick decisions, otherwise his business will be taken away by his competitors. This has further enhanced the necessity for such a system.</p><p class=\"ql-align-justify\">Second, in this information age wherein information is doubling up every two or three years, a manager has to process a large voluminous data; failing which he may end up taking a strong decision that may prove to be very costly to the company.</p><p class=\"ql-align-justify\">In such a situation managers must be equipped with some tools or a system, which can assist them in their challenging role of decision-making. It is because of the above cited reasons, that today MIS is considered to be of permanent importance, sometimes regarded as the name centre of an organization. Such system assist decision makers in organizations by providing information at various stages of decision making and thus greatly help the organizations to achieve their predetermined goals and objectives. On the other hand, the MIS which is not adequately planned for analyzed, designed, implemented or is poorly maintained may provide developed inaccurate, irrelevant or obsolete information which may prove fatal for the organization. In other words, organizations today just cannot survive and grow without properly planned, designed, implemented and maintained MIS. It has been well understood that MIS enables even small organizations to more than offset the economies of scale enjoyed by their bigger competitors and thus helps in providing a competitive edge over other organizations.</p><p class=\"ql-align-justify\"><strong>Problems with Management Information System (MIS)</strong></p><p class=\"ql-align-justify\">i.&nbsp;Lack of Management involvement in the design MIS.</p><p class=\"ql-align-justify\">ii.&nbsp;Lack of top Management Support in the development of MIS. E.g financing MIS projects.</p><p class=\"ql-align-justify\">iii.&nbsp;Narrow and/or inappropriate emphasis of computer system</p><p class=\"ql-align-justify\">iv.&nbsp;Undue concentration on Low-level data processing applications particularly in accounting.</p><p class=\"ql-align-justify\">v.&nbsp;Lack of Management knowledge of computers particularly in small and medium size enterprises (SMEs).</p><p class=\"ql-align-justify\">vi.&nbsp;Poor appreciation by Information specialists of management’s true information need.</p><p class=\"ql-align-justify\"><br></p>', 0, '2025-11-15 02:14:10');

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `resources`
--

INSERT INTO `resources` (`id`, `user_id`, `course`, `group_id`, `title`, `description`, `file_path`, `uploaded_at`) VALUES
(1, 7, '', 8, 'MIS_LECTURE_NOTE', '', '/studyCollab/uploads/resources/MIS_LECTURE_NOTE_1763125976_b8285ac48631.pdf', '2025-11-14 15:12:56');

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tasks`
--

INSERT INTO `tasks` (`id`, `user_id`, `title`, `description`, `due_date`, `status`, `created_at`, `updated_at`) VALUES
(4, 3, 'Management Information System', 'This assignment is based on the PEAS (Packaged Enterprise Application Software), highlighting their benefits to business organisations.', '2025-11-10', 'Completed', '2025-11-05 18:17:43', '2025-11-06 09:19:22'),
(8, 3, 'Management Information System', 'This assignment focuses on the PEAS (Packaged Enterprise Application Software), highlighting their benefits to business organisations.', '2025-11-17', 'Pending', '2025-11-06 10:51:24', '2025-11-12 09:56:57'),
(10, 3, 'Database Technology', 'We are focusing on the Entity Relationship Diagram (ERD) and Cardinality.', '2025-11-04', 'Completed', '2025-11-07 11:00:08', '2025-11-08 05:45:11'),
(11, 3, 'Architecture II', 'Adders and latches. Add diagrams.', '2025-11-16', 'Pending', '2025-11-08 05:46:51', '2025-11-08 05:47:01'),
(12, 5, 'Management Information System', 'Discussing the Packaged Enterprise Application Software (PEAS).', '2025-12-23', 'Pending', '2025-11-10 15:19:05', '2025-11-10 19:38:59'),
(13, 5, 'Programming', 'Data Structures', '2025-11-16', 'Completed', '2025-11-10 20:03:06', '2025-11-10 20:03:13'),
(14, 6, 'coding123', 'i loe', '2025-11-21', 'Pending', '2025-11-11 12:15:05', '2025-11-11 12:15:37'),
(15, 5, 'MIS Assignment 4', 'studying systems', '2025-11-14', 'Completed', '2025-11-12 09:38:16', '2025-11-12 09:39:03');

-- --------------------------------------------------------

--
-- Table structure for table `timetable`
--

CREATE TABLE `timetable` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `course` varchar(255) DEFAULT NULL,
  `location` varchar(255) DEFAULT NULL,
  `day` enum('Mon','Tue','Wed','Thu','Fri') NOT NULL,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `timetable`
--

INSERT INTO `timetable` (`id`, `user_id`, `title`, `course`, `location`, `day`, `start_time`, `end_time`, `created_at`) VALUES
(1, 7, 'Morning Lecture', 'Management Information System', 'Lecture Theatre', 'Mon', '08:00:00', '10:30:00', '2025-11-15 00:32:11');

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `fullname`, `email`, `password`, `user_type`, `course`, `created_at`, `updated_at`, `image`, `reset_token`, `reset_expires`, `remember_token`, `remember_expiry`) VALUES
(1, 'Dr. Smith', 'dr.smith@example.com', '482c811da5d5b4bc6d497ffa98491e38', 'lecturer', NULL, '2025-11-05 17:02:48', '2025-11-05 17:02:48', 'default-avatar.png', NULL, NULL, NULL, NULL),
(2, 'John Doe', 'john.doe@example.com', '482c811da5d5b4bc6d497ffa98491e38', 'student', 'Information Technology', '2025-11-05 17:02:48', '2025-11-05 17:02:48', 'default-avatar.png', NULL, NULL, NULL, NULL),
(3, 'Fewdays Chibwe', 'fewdays8chibwe@gmail.com', '$2y$10$4uvwUjjRBUBJZ.Lt8o5Ms.t0zHNWL2jXyqgr8tML.BoW4/BMI/LYe', 'student', 'Information Technology', '2025-11-05 18:11:37', '2025-11-12 11:35:38', 'user_3_1762519918.jpg', '2f6315d01a95dab18407c138f0e84dd86f9ddc15dc4b7c82c9e67aab02463726', '2025-11-12 14:05:38', 'c667a79075216c9dfcf1cfa6be8c9458', '2025-12-12 10:56:30'),
(4, 'SUWILANJI MFUNGO', 'bethelrealtorslimited@gmail.com', '$2y$10$hPx3NoHDy4EyknYFYHQa2.oQEviwygey2Rd8TiwVT8T08xL47VNzG', 'student', NULL, '2025-11-09 04:42:18', '2025-11-09 04:42:18', 'default-avatar.png', NULL, NULL, NULL, NULL),
(5, 'Nelly Tembo', 'nellytembo@gmail.com', '$2y$10$CEpWSw.rpXjVKPZnaLTQtewIh6yfX4cZWP/Klvt.9BeTpR66WCYgq', 'student', NULL, '2025-11-09 05:00:15', '2025-11-09 05:19:14', 'default-avatar.png', NULL, NULL, '7b31ec56aa6b67a7c26350cdcf248b34', '2025-12-09 06:19:14'),
(6, 'Diran Sai', 'dirantechie@gmail.com', '$2y$10$av12wNgsuOiQfL1FJcDizeEAOgnBcKlCz./b.bGZXcKS5YWM.4cTa', 'student', NULL, '2025-11-11 12:03:06', '2025-11-11 12:03:06', 'default-avatar.png', NULL, NULL, NULL, NULL),
(7, 'Muna Chizyuka', 'munachizyuka58@gmail.com', '$2y$10$QN6SWrcEIGiThV43zDjq1.SiTqPBI/I/p7OXy0nb1htht4QViJhSC', 'student', NULL, '2025-11-13 08:23:23', '2025-11-13 08:23:23', 'default-avatar.png', NULL, NULL, NULL, NULL);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `ai_chat_history`
--
ALTER TABLE `ai_chat_history`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `assessments`
--
ALTER TABLE `assessments`
  ADD PRIMARY KEY (`id`);

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
-- Indexes for table `timetable`
--
ALTER TABLE `timetable`
  ADD PRIMARY KEY (`id`);

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
-- AUTO_INCREMENT for table `ai_chat_history`
--
ALTER TABLE `ai_chat_history`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `assessments`
--
ALTER TABLE `assessments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `calendar_events`
--
ALTER TABLE `calendar_events`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `discussion_replies`
--
ALTER TABLE `discussion_replies`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `groups`
--
ALTER TABLE `groups`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `group_resources`
--
ALTER TABLE `group_resources`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `group_tasks`
--
ALTER TABLE `group_tasks`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=27;

--
-- AUTO_INCREMENT for table `notes`
--
ALTER TABLE `notes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `resources`
--
ALTER TABLE `resources`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `tasks`
--
ALTER TABLE `tasks`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `timetable`
--
ALTER TABLE `timetable`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `ai_chat_history`
--
ALTER TABLE `ai_chat_history`
  ADD CONSTRAINT `ai_chat_history_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

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
