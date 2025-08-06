-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jul 30, 2025 at 09:44 AM
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
-- Database: `rss`
--

-- --------------------------------------------------------

--
-- Table structure for table `admin`
--

CREATE TABLE `admin` (
  `id` int(11) NOT NULL,
  `admin_username` varchar(100) NOT NULL,
  `admin_password` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `announcements`
--

CREATE TABLE `announcements` (
  `announcement_id` int(11) NOT NULL,
  `content` text NOT NULL,
  `title` varchar(255) DEFAULT NULL,
  `admin_name` varchar(100) NOT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `image` varchar(255) DEFAULT NULL,
  `deleted` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `approved_overtime_schedule`
--

CREATE TABLE `approved_overtime_schedule` (
  `id` int(11) NOT NULL,
  `employee_id` int(11) NOT NULL,
  `ot_date` date NOT NULL,
  `extended_time_out` time NOT NULL,
  `reason` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `comments`
--

CREATE TABLE `comments` (
  `comment_id` int(11) NOT NULL,
  `announcement_id` int(11) NOT NULL,
  `employee_id` int(11) NOT NULL,
  `content` text NOT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `deleted` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `emojis`
--

CREATE TABLE `emojis` (
  `emoji` char(4) NOT NULL,
  `label` varchar(20) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `employees`
--

CREATE TABLE `employees` (
  `id` int(11) NOT NULL,
  `fname` varchar(255) DEFAULT NULL,
  `lname` varchar(255) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `personal_email` varchar(255) DEFAULT NULL,
  `contact` varchar(255) DEFAULT NULL,
  `position` varchar(255) DEFAULT NULL,
  `status` enum('active','inactive') DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `username` varchar(100) DEFAULT NULL,
  `password` varchar(255) DEFAULT NULL,
  `company` varchar(255) DEFAULT NULL,
  `profile_image` varchar(255) DEFAULT NULL,
  `profile_picture` varchar(255) DEFAULT NULL,
  `official_sched` int(11) DEFAULT NULL,
  `role` enum('employee','internal') NOT NULL DEFAULT 'employee'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `employee_checklist`
--

CREATE TABLE `employee_checklist` (
  `id` int(11) NOT NULL,
  `employee_id` int(11) NOT NULL,
  `letter_offer` varchar(255) DEFAULT NULL,
  `employment_contract` varchar(255) DEFAULT NULL,
  `medical` varchar(255) DEFAULT NULL,
  `nbi_clearance` varchar(255) DEFAULT NULL,
  `diploma_tor` varchar(255) DEFAULT NULL,
  `psa` varchar(255) DEFAULT NULL,
  `sss` varchar(255) DEFAULT NULL,
  `tin` varchar(255) DEFAULT NULL,
  `philhealth` varchar(255) DEFAULT NULL,
  `coe` varchar(255) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `pagibig` varchar(255) DEFAULT NULL,
  `valid_id` text DEFAULT NULL,
  `valid_id_2` text DEFAULT NULL,
  `solo_parent_id` text DEFAULT NULL,
  `employment_adjustment_form` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `employee_schedules`
--

CREATE TABLE `employee_schedules` (
  `id` int(11) NOT NULL,
  `employee_id` int(11) NOT NULL,
  `work_schedule_id` int(11) NOT NULL,
  `day_of_week` enum('Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday') NOT NULL,
  `effective_date` date NOT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `employee_schedule_history`
--

CREATE TABLE `employee_schedule_history` (
  `id` int(11) NOT NULL,
  `employee_id` int(11) NOT NULL,
  `schedule_id` int(11) NOT NULL,
  `effective_date` date NOT NULL,
  `changed_by` int(11) DEFAULT NULL,
  `reason` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `employee_work_schedule`
--

CREATE TABLE `employee_work_schedule` (
  `id` int(11) NOT NULL,
  `employee_id` int(11) DEFAULT NULL,
  `work_schedule_id` int(11) DEFAULT NULL,
  `effective_date` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `leave_credits`
--

CREATE TABLE `leave_credits` (
  `id` int(11) NOT NULL,
  `employee_id` int(11) NOT NULL,
  `leave_type` varchar(50) NOT NULL,
  `balance` decimal(5,2) DEFAULT NULL,
  `carry_over` float DEFAULT NULL,
  `carried_over` decimal(5,2) DEFAULT NULL,
  `monthly_increment` decimal(5,2) DEFAULT NULL,
  `year` int(11) NOT NULL,
  `updated_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `leave_requests`
--

CREATE TABLE `leave_requests` (
  `id` int(11) NOT NULL,
  `employee_id` int(11) DEFAULT NULL,
  `leave_type` enum('sick','vacation','paternity','maternity','solo_parent','halfday','halfday_sick','lwop','bereavement') DEFAULT NULL,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `reason` text DEFAULT NULL,
  `status` enum('pending','approved','rejected') DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `leave_dates` varchar(255) NOT NULL,
  `attachment_lr` varchar(255) DEFAULT NULL,
  `notified` tinyint(1) DEFAULT 0,
  `explanation` text DEFAULT NULL,
  `deleted` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `id` int(11) NOT NULL,
  `employee_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `type` varchar(50) DEFAULT 'info',
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `overtime_requests`
--

CREATE TABLE `overtime_requests` (
  `id` int(11) NOT NULL,
  `employee_id` int(11) NOT NULL,
  `date` date NOT NULL,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  `duration_hours` float NOT NULL,
  `reason` text NOT NULL,
  `status` enum('Pending','Approved','Rejected') DEFAULT 'Pending',
  `attachment_ot` varchar(255) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `time_in` time DEFAULT NULL,
  `time_out` time DEFAULT NULL,
  `notified` tinyint(1) DEFAULT 0,
  `explanation` text DEFAULT NULL,
  `deleted` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `post_leave_requests`
--

CREATE TABLE `post_leave_requests` (
  `id` int(11) NOT NULL,
  `employee_id` int(11) NOT NULL,
  `leave_type` varchar(100) NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `reason` text DEFAULT NULL,
  `status` enum('pending','approved','rejected') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `attachment_lr` varchar(255) DEFAULT NULL,
  `notified` tinyint(1) DEFAULT 0,
  `explanation` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `post_overtime_requests`
--

CREATE TABLE `post_overtime_requests` (
  `id` int(11) NOT NULL,
  `employee_id` int(11) NOT NULL,
  `date` date NOT NULL,
  `start_time` time DEFAULT NULL,
  `end_time` time DEFAULT NULL,
  `duration_hours` decimal(5,2) DEFAULT NULL,
  `reason` text DEFAULT NULL,
  `status` enum('Pending','Approved','Rejected') DEFAULT 'Pending',
  `attachment_ot` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `time_in` time DEFAULT NULL,
  `time_out` time DEFAULT NULL,
  `notified` tinyint(1) DEFAULT 0,
  `explanation` text DEFAULT NULL,
  `deleted` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `post_reactions`
--

CREATE TABLE `post_reactions` (
  `id` int(11) NOT NULL,
  `announcement_id` int(11) NOT NULL,
  `employee_id` int(11) NOT NULL,
  `reaction_type` enum('like','love','laugh','wow','sad','angry') NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `post_schedule_change_requests`
--

CREATE TABLE `post_schedule_change_requests` (
  `id` int(11) NOT NULL,
  `employee_id` int(11) NOT NULL,
  `reason` text DEFAULT NULL,
  `status` enum('Pending','Approved','Declined') DEFAULT 'Pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `work_schedule_id` int(11) DEFAULT NULL,
  `current_work_schedule_id` int(11) DEFAULT NULL,
  `attachment_scr` varchar(255) DEFAULT NULL,
  `notified` tinyint(1) DEFAULT 0,
  `explanation` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `post_time_adjustment_requests`
--

CREATE TABLE `post_time_adjustment_requests` (
  `id` int(11) NOT NULL,
  `employee_id` int(11) NOT NULL,
  `log_date` date NOT NULL,
  `current_time_in` time DEFAULT NULL,
  `current_time_out` time DEFAULT NULL,
  `requested_time_in` time DEFAULT NULL,
  `requested_time_out` time DEFAULT NULL,
  `reason` text DEFAULT NULL,
  `status` enum('Pending','Approved','Declined') DEFAULT 'Pending',
  `submitted_at` datetime DEFAULT NULL,
  `attachment` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `notified` tinyint(1) DEFAULT 0,
  `deleted` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `rest_day_overtime_requests`
--

CREATE TABLE `rest_day_overtime_requests` (
  `id` int(11) NOT NULL,
  `employee_id` int(11) DEFAULT NULL,
  `rest_day_date` date DEFAULT NULL,
  `expected_time_in` time DEFAULT NULL,
  `expected_time_out` time DEFAULT NULL,
  `reason` text DEFAULT NULL,
  `status` enum('pending','approved','rejected') DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `schedule_change_requests`
--

CREATE TABLE `schedule_change_requests` (
  `id` int(11) NOT NULL,
  `employee_id` int(11) DEFAULT NULL,
  `requested_schedule_id` int(11) DEFAULT NULL,
  `reason` text DEFAULT NULL,
  `work_schedule` varchar(255) DEFAULT NULL,
  `status` enum('Pending','Approved','Declined') DEFAULT 'Pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `confirmed_rcs` datetime DEFAULT NULL,
  `declined_rcs` datetime DEFAULT NULL,
  `work_schedule_id` int(11) DEFAULT NULL,
  `current_work_schedule_id` int(11) DEFAULT NULL,
  `attachment_scr` varchar(255) DEFAULT NULL,
  `notified` tinyint(1) DEFAULT 0,
  `explanation` text DEFAULT NULL,
  `deleted` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `schedule_exceptions`
--

CREATE TABLE `schedule_exceptions` (
  `id` int(11) NOT NULL,
  `employee_id` int(11) DEFAULT NULL,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `time_in` time DEFAULT NULL,
  `time_out` time DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `schedule_exception_requests`
--

CREATE TABLE `schedule_exception_requests` (
  `id` int(11) NOT NULL,
  `employee_id` int(11) DEFAULT NULL,
  `requested_time_in` time DEFAULT NULL,
  `requested_time_out` time DEFAULT NULL,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `reason` text DEFAULT NULL,
  `status` enum('pending','approved','rejected') DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `test_sql`
--

CREATE TABLE `test_sql` (
  `id` int(11) NOT NULL,
  `employee_id` int(11) NOT NULL,
  `content` text NOT NULL,
  `created_date` date NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `time_adjustment_requests`
--

CREATE TABLE `time_adjustment_requests` (
  `id` int(11) NOT NULL,
  `employee_id` int(11) NOT NULL,
  `log_date` date NOT NULL,
  `current_time_in` time DEFAULT NULL,
  `current_time_out` time DEFAULT NULL,
  `requested_time_in` time DEFAULT NULL,
  `requested_time_out` time DEFAULT NULL,
  `reason` text DEFAULT NULL,
  `status` enum('Pending','Approved','Declined') DEFAULT 'Pending',
  `submitted_at` datetime DEFAULT current_timestamp(),
  `attachment` varchar(255) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `notified` tinyint(1) DEFAULT 0,
  `deleted` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `time_logs`
--

CREATE TABLE `time_logs` (
  `id` int(11) NOT NULL,
  `employee_id` int(11) DEFAULT NULL,
  `log_date` date DEFAULT NULL,
  `time_in` time DEFAULT NULL,
  `time_out` time DEFAULT NULL,
  `is_late_in` tinyint(1) DEFAULT 0,
  `is_early_out` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `user_read_announcements`
--

CREATE TABLE `user_read_announcements` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `announcement_id` int(11) NOT NULL,
  `read_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `vacation_leaves`
--

CREATE TABLE `vacation_leaves` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `reason` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `work_schedules`
--

CREATE TABLE `work_schedules` (
  `id` int(11) NOT NULL,
  `name` varchar(255) DEFAULT NULL,
  `time_in` time DEFAULT NULL,
  `time_out` time DEFAULT NULL,
  `employee_id` int(11) DEFAULT NULL,
  `day_of_week` enum('Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday') DEFAULT NULL,
  `full_text` varchar(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admin`
--
ALTER TABLE `admin`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `announcements`
--
ALTER TABLE `announcements`
  ADD PRIMARY KEY (`announcement_id`);

--
-- Indexes for table `approved_overtime_schedule`
--
ALTER TABLE `approved_overtime_schedule`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `comments`
--
ALTER TABLE `comments`
  ADD PRIMARY KEY (`comment_id`),
  ADD KEY `announcement_id` (`announcement_id`),
  ADD KEY `employee_id` (`employee_id`);

--
-- Indexes for table `emojis`
--
ALTER TABLE `emojis`
  ADD PRIMARY KEY (`emoji`);

--
-- Indexes for table `employees`
--
ALTER TABLE `employees`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `employee_checklist`
--
ALTER TABLE `employee_checklist`
  ADD PRIMARY KEY (`id`),
  ADD KEY `employee_id` (`employee_id`);

--
-- Indexes for table `employee_schedules`
--
ALTER TABLE `employee_schedules`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_emp_sched_employee` (`employee_id`),
  ADD KEY `fk_emp_sched_schedule` (`work_schedule_id`);

--
-- Indexes for table `employee_schedule_history`
--
ALTER TABLE `employee_schedule_history`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_employee_date` (`employee_id`,`effective_date`),
  ADD KEY `idx_employee_id` (`employee_id`),
  ADD KEY `idx_effective_date` (`effective_date`);

--
-- Indexes for table `employee_work_schedule`
--
ALTER TABLE `employee_work_schedule`
  ADD PRIMARY KEY (`id`),
  ADD KEY `employee_id` (`employee_id`),
  ADD KEY `work_schedule_id` (`work_schedule_id`);

--
-- Indexes for table `leave_credits`
--
ALTER TABLE `leave_credits`
  ADD PRIMARY KEY (`id`),
  ADD KEY `employee_id` (`employee_id`);

--
-- Indexes for table `leave_requests`
--
ALTER TABLE `leave_requests`
  ADD PRIMARY KEY (`id`),
  ADD KEY `employee_id` (`employee_id`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `employee_id` (`employee_id`);

--
-- Indexes for table `overtime_requests`
--
ALTER TABLE `overtime_requests`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `post_leave_requests`
--
ALTER TABLE `post_leave_requests`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_employee_id` (`employee_id`);

--
-- Indexes for table `post_overtime_requests`
--
ALTER TABLE `post_overtime_requests`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_employee_id` (`employee_id`);

--
-- Indexes for table `post_reactions`
--
ALTER TABLE `post_reactions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_user_post` (`announcement_id`,`employee_id`);

--
-- Indexes for table `post_schedule_change_requests`
--
ALTER TABLE `post_schedule_change_requests`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_employee_id` (`employee_id`),
  ADD KEY `idx_work_schedule_id` (`work_schedule_id`),
  ADD KEY `idx_current_work_schedule_id` (`current_work_schedule_id`);

--
-- Indexes for table `post_time_adjustment_requests`
--
ALTER TABLE `post_time_adjustment_requests`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_employee_id` (`employee_id`);

--
-- Indexes for table `rest_day_overtime_requests`
--
ALTER TABLE `rest_day_overtime_requests`
  ADD PRIMARY KEY (`id`),
  ADD KEY `employee_id` (`employee_id`);

--
-- Indexes for table `schedule_change_requests`
--
ALTER TABLE `schedule_change_requests`
  ADD PRIMARY KEY (`id`),
  ADD KEY `employee_id` (`employee_id`),
  ADD KEY `requested_schedule_id` (`requested_schedule_id`),
  ADD KEY `fk_work_schedule` (`work_schedule_id`);

--
-- Indexes for table `schedule_exceptions`
--
ALTER TABLE `schedule_exceptions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `employee_id` (`employee_id`);

--
-- Indexes for table `schedule_exception_requests`
--
ALTER TABLE `schedule_exception_requests`
  ADD PRIMARY KEY (`id`),
  ADD KEY `employee_id` (`employee_id`);

--
-- Indexes for table `test_sql`
--
ALTER TABLE `test_sql`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_request` (`employee_id`,`created_date`);

--
-- Indexes for table `time_adjustment_requests`
--
ALTER TABLE `time_adjustment_requests`
  ADD PRIMARY KEY (`id`),
  ADD KEY `employee_id` (`employee_id`);

--
-- Indexes for table `time_logs`
--
ALTER TABLE `time_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `employee_id` (`employee_id`);

--
-- Indexes for table `user_read_announcements`
--
ALTER TABLE `user_read_announcements`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_user_announcement` (`user_id`,`announcement_id`),
  ADD KEY `announcement_id` (`announcement_id`);

--
-- Indexes for table `vacation_leaves`
--
ALTER TABLE `vacation_leaves`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `work_schedules`
--
ALTER TABLE `work_schedules`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admin`
--
ALTER TABLE `admin`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `announcements`
--
ALTER TABLE `announcements`
  MODIFY `announcement_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `approved_overtime_schedule`
--
ALTER TABLE `approved_overtime_schedule`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `comments`
--
ALTER TABLE `comments`
  MODIFY `comment_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `employees`
--
ALTER TABLE `employees`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `employee_checklist`
--
ALTER TABLE `employee_checklist`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `employee_schedules`
--
ALTER TABLE `employee_schedules`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `employee_schedule_history`
--
ALTER TABLE `employee_schedule_history`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `employee_work_schedule`
--
ALTER TABLE `employee_work_schedule`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `leave_credits`
--
ALTER TABLE `leave_credits`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `leave_requests`
--
ALTER TABLE `leave_requests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `overtime_requests`
--
ALTER TABLE `overtime_requests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `post_leave_requests`
--
ALTER TABLE `post_leave_requests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `post_overtime_requests`
--
ALTER TABLE `post_overtime_requests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `post_reactions`
--
ALTER TABLE `post_reactions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `post_schedule_change_requests`
--
ALTER TABLE `post_schedule_change_requests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `post_time_adjustment_requests`
--
ALTER TABLE `post_time_adjustment_requests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `rest_day_overtime_requests`
--
ALTER TABLE `rest_day_overtime_requests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `schedule_change_requests`
--
ALTER TABLE `schedule_change_requests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `schedule_exceptions`
--
ALTER TABLE `schedule_exceptions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `schedule_exception_requests`
--
ALTER TABLE `schedule_exception_requests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `test_sql`
--
ALTER TABLE `test_sql`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `time_adjustment_requests`
--
ALTER TABLE `time_adjustment_requests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `time_logs`
--
ALTER TABLE `time_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `user_read_announcements`
--
ALTER TABLE `user_read_announcements`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `vacation_leaves`
--
ALTER TABLE `vacation_leaves`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `work_schedules`
--
ALTER TABLE `work_schedules`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `comments`
--
ALTER TABLE `comments`
  ADD CONSTRAINT `comments_ibfk_1` FOREIGN KEY (`announcement_id`) REFERENCES `announcements` (`announcement_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `comments_ibfk_2` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `employee_checklist`
--
ALTER TABLE `employee_checklist`
  ADD CONSTRAINT `employee_checklist_ibfk_1` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `employee_schedules`
--
ALTER TABLE `employee_schedules`
  ADD CONSTRAINT `fk_emp_sched_employee` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_emp_sched_schedule` FOREIGN KEY (`work_schedule_id`) REFERENCES `work_schedules` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `employee_work_schedule`
--
ALTER TABLE `employee_work_schedule`
  ADD CONSTRAINT `employee_work_schedule_ibfk_1` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`),
  ADD CONSTRAINT `employee_work_schedule_ibfk_2` FOREIGN KEY (`work_schedule_id`) REFERENCES `work_schedules` (`id`);

--
-- Constraints for table `leave_credits`
--
ALTER TABLE `leave_credits`
  ADD CONSTRAINT `leave_credits_ibfk_1` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`);

--
-- Constraints for table `leave_requests`
--
ALTER TABLE `leave_requests`
  ADD CONSTRAINT `leave_requests_ibfk_1` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`);

--
-- Constraints for table `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `post_reactions`
--
ALTER TABLE `post_reactions`
  ADD CONSTRAINT `post_reactions_ibfk_1` FOREIGN KEY (`announcement_id`) REFERENCES `announcements` (`announcement_id`) ON DELETE CASCADE;

--
-- Constraints for table `rest_day_overtime_requests`
--
ALTER TABLE `rest_day_overtime_requests`
  ADD CONSTRAINT `rest_day_overtime_requests_ibfk_1` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`);

--
-- Constraints for table `schedule_change_requests`
--
ALTER TABLE `schedule_change_requests`
  ADD CONSTRAINT `fk_work_schedule` FOREIGN KEY (`work_schedule_id`) REFERENCES `work_schedules` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `schedule_change_requests_ibfk_1` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`),
  ADD CONSTRAINT `schedule_change_requests_ibfk_2` FOREIGN KEY (`requested_schedule_id`) REFERENCES `work_schedules` (`id`);

--
-- Constraints for table `schedule_exceptions`
--
ALTER TABLE `schedule_exceptions`
  ADD CONSTRAINT `schedule_exceptions_ibfk_1` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`);

--
-- Constraints for table `schedule_exception_requests`
--
ALTER TABLE `schedule_exception_requests`
  ADD CONSTRAINT `schedule_exception_requests_ibfk_1` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`);

--
-- Constraints for table `time_adjustment_requests`
--
ALTER TABLE `time_adjustment_requests`
  ADD CONSTRAINT `time_adjustment_requests_ibfk_1` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`);

--
-- Constraints for table `time_logs`
--
ALTER TABLE `time_logs`
  ADD CONSTRAINT `time_logs_ibfk_1` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`);

--
-- Constraints for table `user_read_announcements`
--
ALTER TABLE `user_read_announcements`
  ADD CONSTRAINT `user_read_announcements_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `user_read_announcements_ibfk_2` FOREIGN KEY (`announcement_id`) REFERENCES `announcements` (`announcement_id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
