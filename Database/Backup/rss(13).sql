-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3306
-- Generation Time: Jul 18, 2025 at 06:43 AM
-- Server version: 10.11.10-MariaDB-log
-- PHP Version: 7.2.34

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `u816220874_rss_db`
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

--
-- Dumping data for table `admin`
--

INSERT INTO `admin` (`id`, `admin_username`, `admin_password`) VALUES
(1, 'admin_hr', 'rss_admin');

-- --------------------------------------------------------

--
-- Table structure for table `announcements`
--

CREATE TABLE `announcements` (
  `announcement_id` int(11) NOT NULL,
  `content` text NOT NULL,
  `admin_name` varchar(100) NOT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `image` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `announcements`
--

INSERT INTO `announcements` (`announcement_id`, `content`, `admin_name`, `created_at`, `image`) VALUES
(37, '🎉 Welcome to Harley!\r\n\r\nWe’re excited to have you on board! This is your go-to spot for updates, time log-ins, leave filing, and Change Work Schedule (CWS) requests.\r\n\r\nStay tuned—more exciting announcements and updates are coming your way soon! 🚀\r\n\r\nCheers,\r\n\r\nHarley', 'Admin', '2025-07-01 13:56:22', NULL),
(39, 'Hi All,\r\n\r\nMSN! :)\r\n\r\nHappy Friday, everyone!\r\n\r\nCheers,\r\n\r\nHarley', 'Admin', '2025-07-04 16:01:23', NULL),
(40, 'Hi Team,\r\n\r\nThere have been several major cyber security incidents in the news recently, and we want to take this opportunity to remind everyone to stay vigilant and keep your data secure.\r\n\r\nHere are a few simple ways to avoid security breaches:\r\n\r\n🔐 Use strong, unique passwords and update them regularly\r\n📧 Be cautious of suspicious emails – don’t click on unknown links or attachments\r\n🛑 Avoid downloading unverified software or files\r\n🔄 Keep your systems and software up to date\r\n⚠️ If you\'re about to install a software and WatchGuard flags it, please notify the IT team first so we can check if it\'s safe\r\n🔍 Report anything unusual immediately\r\n\r\nAs always, our IT Team is here to support you. If you think you’ve accidentally downloaded something harmful or noticed signs of malware, please reach out to us right away.\r\n\r\nThank you for helping keep our systems safe!\r\n\r\nBest regards,\r\nIT Team', 'Admin', '2025-07-09 07:59:52', NULL),
(42, 'Hope you are all safe and dry! ☀️\r\n\r\nWe’ve been experiencing frequent and unpredictable change in our weather lately. With your safety and well-being in mind, we want to keep you informed and prepared.\r\n\r\nAs of July 11, 2025, Friday:\r\nWeather Forecast: Moderate to heavy rainshowers with lightning ⛈️\r\nWind Speed: Light to Moderate 🌬️\r\nAffected Areas: Pampanga, Tarlac, Bulacan, Metro Manila, Cavite, Batangas, Rizal, Laguna and Nueva Ecija \r\n\r\nEmergency Hotlines ☎️ 🚨\r\nAngeles City: 0917-851-9581 or 0998-842- 7746 \r\nCSFP: 0939-936-2423 or 0939-961-4357 \r\nMabalacat City: 0998-999-4357\r\n\r\nIf you’re having trouble getting to work because of the weather, please let your supervisor know as soon as you can. You may reach out via email, chat, or any usual way to communicate. Your safety comes first!', 'Admin', '2025-07-11 11:02:02', NULL),
(43, 'Hi All,\r\n\r\nJust a quick reminder that our payroll cut-off is tomorrow. Kindly ensure that all adjustments, leave requests, and overtime entries are filed on time, complete with the necessary attachments and approvals.\r\n\r\nPlease note that overtime requests without prior approval will not be processed.\r\n\r\nThank you for your cooperation!\r\n\r\nCheers,\r\nHR', 'Admin', '2025-07-15 07:24:07', NULL),
(58, 'Hi Team,\r\n\r\nAt approximately 10:38 AM today, July 15, 2025, a magnitude 5.8 earthquake was recorded in Ilocos Norte. Below are the reported intensities in affected areas:\r\n\r\n- Intensity IV – Claveria (Cagayan), San Nicolas & Solsona (Ilocos Norte), Sinait & Vigan City (Ilocos Sur)\r\n- Intensity III – Gonzaga (Cagayan)\r\n- Intensity II – Bangued (Abra), Peñablanca (Cagayan), Narvacan (Ilocos Sur)\r\n- Intensity I – Candon (Ilocos Sur), Ilagan (Isabela)\r\n\r\nWhile we are located far from the epicenter, a mild tremor was still felt in Pampanga.\r\n\r\nOur IT Specialist, Cedrick, has checked in with all team members, and we are glad to report that everyone is safe and no damage has been observed in the building. Nevertheless, we encourage everyone to remain alert and vigilant, especially in case of aftershocks.\r\n\r\nPlease be reminded to take note of the nearest fire exits and follow standard safety protocols at all times.\r\n\r\nStay safe, everyone, and thank you for your cooperation.\r\n\r\nRegards,\r\nHR Department', 'Admin', '2025-07-15 10:58:18', NULL),
(67, 'Hi All,\n\nJust an update regarding our HMO provider, Cocolife:\n\nWe were informed that some clinics and hospitals are currently on hold due to ongoing reconciliation of claims. This involves discrepancies between the amounts being charged by the facilities and the actual amounts covered by Cocolife. They advised that approximately 75% of these reconciliations are expected to be resolved by August.\n\nIn the meantime, please see the attached updated list of available and alternative clinics/facilities that you may reach out to.\n\nThank you, and rest assured we will continue to monitor this closely with Cocolife.\n\nRegards,\nHR', 'Admin', '2025-07-15 14:46:17', '[\"uploads\\/file_6875f938f3b7a8.31042859.xlsx\",\"uploads\\/file_6875f938f3e122.16187353.xlsx\",\"uploads\\/file_6875f938f40044.46915340.xlsx\",\"uploads\\/file_6875f939000767.06844388.xlsx\"]'),
(74, 'Hi Team,\r\n\r\nThis is a final reminder to review your attendance history and file any necessary adjustments with Harley as soon as possible.\r\n\r\nFor Overtime (OT) submissions, please send them to the HR email with client or Team Lead approval attached.\r\n\r\n🕒 Cut-off for all submissions is tomorrow, July 17, 2025, at 7:00 AM.\r\n\r\nAny late endorsements will be processed on the next payroll cut-off.\r\n\r\nThank you for your strict compliance.\r\n\r\nBest regards,\r\nHR', 'Admin', '2025-07-16 14:53:43', NULL),
(75, 'Hi Team,\r\n\r\nHappy Friday! We hope everything is going well with you.\r\n\r\nDraft payslips for the July 1–15, 2025 cut-off are scheduled to be released today. If you have any concerns or discrepancies with your payslip computation, please email us using the following subject line: Payroll Dispute.\r\nThis format will also be used for future payroll concerns. Kindly follow this subject line format moving forward.\r\n\r\nThank you!', 'Admin', '2025-07-18 09:52:32', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `announcement_reactions`
--

CREATE TABLE `announcement_reactions` (
  `reaction_id` int(11) NOT NULL,
  `announcement_id` int(11) NOT NULL,
  `employee_id` int(11) NOT NULL,
  `reaction_type` enum('like') NOT NULL DEFAULT 'like',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `announcement_reactions`
--

INSERT INTO `announcement_reactions` (`reaction_id`, `announcement_id`, `employee_id`, `reaction_type`, `created_at`) VALUES
(3, 39, 1009, 'like', '2025-07-07 06:12:55'),
(6, 37, 1009, 'like', '2025-07-07 06:12:59'),
(7, 39, 63, 'like', '2025-07-08 04:38:55'),
(8, 40, 1004, 'like', '2025-07-09 00:00:17'),
(9, 40, 56, 'like', '2025-07-09 00:02:06'),
(10, 40, 7, 'like', '2025-07-09 00:05:00'),
(11, 40, 64, 'like', '2025-07-09 00:20:08'),
(12, 39, 1006, 'like', '2025-07-09 01:04:45'),
(14, 40, 6, 'like', '2025-07-09 01:09:21'),
(15, 40, 28, 'like', '2025-07-09 01:10:05'),
(16, 40, 12, 'like', '2025-07-09 01:48:39');

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
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `comments`
--

INSERT INTO `comments` (`comment_id`, `announcement_id`, `employee_id`, `content`, `created_at`) VALUES
(123, 37, 1009, 'exciting', '2025-07-01 06:10:29'),
(124, 37, 28, 'Awesome mate', '2025-07-01 07:00:58'),
(125, 37, 7, 'Amazing', '2025-07-01 07:03:38'),
(126, 37, 64, 'Exciting!', '2025-07-01 07:04:44'),
(127, 37, 9, 'Nice!', '2025-07-01 07:06:56'),
(128, 37, 67, 'Great!', '2025-07-01 07:10:51'),
(129, 37, 6, 'Cool', '2025-07-01 07:13:17'),
(135, 37, 1009, 'test', '2025-07-01 23:32:17'),
(136, 37, 1009, '.', '2025-07-02 02:03:12'),
(137, 37, 1009, '.', '2025-07-02 02:03:31'),
(143, 37, 72, 'Great work IT team! More powers', '2025-07-04 07:35:37'),
(144, 39, 1004, 'Yay', '2025-07-07 00:45:38');

-- --------------------------------------------------------

--
-- Table structure for table `emojis`
--

CREATE TABLE `emojis` (
  `emoji` char(4) NOT NULL,
  `label` varchar(20) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `emojis`
--

INSERT INTO `emojis` (`emoji`, `label`) VALUES
('❤️', 'Love'),
('👍', 'Like');

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
  `profile_picture` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `employees`
--

INSERT INTO `employees` (`id`, `fname`, `lname`, `email`, `personal_email`, `contact`, `position`, `status`, `created_at`, `username`, `password`, `company`, `profile_image`, `profile_picture`) VALUES
(1, 'Vincent Kevin', 'Santos', 'Vincent.Antonio@entrygroup.com.au', 'vincentvinz17@gmail.com', '0905 919 2943', 'Student Support - Marking', 'active', '2025-04-29 01:04:12', 'vincent.santos', '1mj4i', 'Entry Education', NULL, 'profile_1_1750742776.png'),
(2, 'Shaina Dimayugo', 'Dela Cruz', 'shainadc86@gmail.com', 'shainadc86@gmail.com', '0935 766 5039', 'Student Support Mentoring', 'active', '2025-04-29 01:04:12', 'shaina.dela cruz', 'u6mf3', 'Entry Education', NULL, 'profile_2_1749605234.jpeg'),
(3, 'Renalyn Abamo', 'Josafat', 'reny@entrygroup.com.au', 'rajosafat.cca@gmail.com', '0939 245 6150', 'Student Support Mentoring', 'active', '2025-04-29 01:04:12', 'renalyn.josafat', 'za9lv', 'Entry Education', NULL, 'profile_3_1752052468.png'),
(4, 'Joel Lusung', 'Alimurong', 'Joel.Alimurong@entrygroup.com.au', 'jhei.el1768@gmail.com', '0966 539 4550', 'Student Support - Marking', 'active', '2025-04-29 01:04:12', 'joel.alimurong', 'gj9af', 'Entry Education', NULL, 'profile_4_1749606383.jpeg'),
(5, 'Aizel Santos', 'Castro', 'aizel.castro@entrygroup.com.au', 'aizel.castro01@gmail.com', '0926 215 0722', 'Student Support - Marking', 'active', '2025-04-29 01:04:12', 'aizel.castro', '10kjg', 'Entry Education', NULL, NULL),
(6, 'Reymark Bryan Silvano', 'Colis', 'bryan@entrygroup.com.au', 'reymarkbryancolis@gmail.com', '0927 014 4692', 'Technical Student Support - Team Leader', 'active', '2025-04-29 01:04:12', 'reymark.colis', '8vyia', 'Entry Education', NULL, 'profile_6_1751354099.png'),
(7, 'Francis Emmanuel Veloso', 'Fernandez', 'francis@entrygroup.com.au', 'francis2208@gmail.com', '0967 201 4330', 'Student Support - Marking Team Leader', 'active', '2025-04-29 01:04:12', 'francis.fernandez', '6fcrh', 'Entry Education', NULL, 'profile_7_1751353606.png'),
(8, 'Cedrick Cruz', 'Galgo', 'Cedrick.Galgo@entrygroup.com.au', 'cedrickgalgo@gmail.com', '0948 914 9733', 'IT Support', 'active', '2025-04-29 01:04:12', 'cedrick.galgo', '3fez7', 'Entry Education', NULL, NULL),
(9, 'Shigeru', 'Centina', 'Shigeru.Otsuka@entrygroup.com.au', 'shigeruslayer12345@gmail.com', '0939 286 1648', 'Instructional Designer - Technical Specialist', 'active', '2025-04-29 01:04:12', 'shigeru.centina', 'rxg6z', 'Entry Education', NULL, 'profile_9_1751354113.jpg'),
(10, 'Rhegene', 'Ingat Ronquillo', 'reggie@entrygroup.com.au', 'rronquillo0727@gmail.com', '0916 936 6370', 'Technical Student Support', 'active', '2025-04-29 01:04:12', 'rhegene.ronquillo', '1rodf', 'Entry Education', NULL, 'profile_10_1751353416.JPG'),
(11, 'Mary Ann', 'Vallejos Soriano', 'mary@entrygroup.com.au', 'habibisoriano@yahoo.com', '0906 255 2990', 'Sales New Student Enquiries', 'active', '2025-04-29 01:04:12', 'mary.soriano', '0bz7u', 'Entry Education', NULL, 'profile_11_1749624970.jpeg'),
(12, 'Beverly', 'Taloban Gatbonton', 'beverly.gatbonton@entrygroup.com.au', 'beverlygatbonton29@gmail.com', '0920 403 7997', 'Team Leader Sales - New Student Enquiries', 'active', '2025-04-29 01:04:12', 'beverly.gatbonton', 'pw3zj', 'Entry Education', NULL, NULL),
(13, 'Rogelio', 'Dela Peña Malinao Jr', 'rogelio.malinao@entrygroup.com.au', 'rogelio.malinao@gmail.com', '0976 212 6539', 'Student Support - Marking', 'active', '2025-04-29 01:04:12', 'rogelio.malinao', 'vski1', 'Entry Education', NULL, NULL),
(14, 'Jillian', 'Agas', 'jillian.agas@entrygroup.com.au', 'jillian052089@gmail.com', '0915 546 4289', 'Technical Student Support', 'active', '2025-06-11 04:25:36', 'Jillian.Agas', '3ntry', 'Entry Education', NULL, NULL),
(15, 'Evanel', 'Caacbay Navalon', 'evanel.navalon@entrygroup.com.au', 'evanelnavalon@gmail.com', '0946 882 2198', 'Technical Student Support', 'active', '2025-04-29 01:04:12', 'evanel.navalon', 'movch', 'Entry Education', NULL, 'profile_15_1752052633.png'),
(16, 'Ian Myco', 'Aguilar', 'ian.aguilar@entrygroup.com.au', 'iammycovital@gmail.com', '0976 198 9787', 'Student Support - Marking', 'active', '2025-04-29 01:04:12', 'ian.aguilar', 'uveib', 'Entry Education', NULL, NULL),
(17, 'Reneeca', 'Villapaña Benalla', 'Reneeca@entrygroup.com.au', 'reneeca.benalla@gmail.com', '0909 204 3758', 'Content Writer & Instructional Designer', 'active', '2025-04-29 01:04:12', 'reneeca.benalla', 'rpycf', 'Entry Education', NULL, NULL),
(18, 'Edith', 'David Mataga', 'Edith@entrygroup.com.au', 'edghie03@gmail.com', '0935 563 9451', 'Sales New Student Enquiries', 'active', '2025-04-29 01:04:12', 'edith.mataga', 'v2w4m', 'Entry Education', NULL, NULL),
(20, 'Alfred Naguit', 'Ocampo', 'Alfred@entrygroup.com.au', 'derfla61@gmail.com', '0963 256 7621', 'Conveyancing Client Support', 'active', '2025-04-29 01:04:12', 'alfred.ocampo', 'xs9nq', 'Entry Education', NULL, NULL),
(21, 'Jennifer', 'Trinidad', 'jennifer.trinidad@entrygroup.com.au', 'jennifertrinidad0103@gmail.com', '0928 225 5869', 'Student Support - Marking', 'active', '2023-08-07 01:04:12', 'jennifer.trinidad', 'xt0rd', 'Entry Education', NULL, 'profile_21_1752047143.png'),
(22, 'Sean Justine', 'Mendoza', 'sean.mendoza@entrygroup.com.au', 'mendozaseanjustine@gmail.com', '0977 019 9064', 'Student Support - Marking', 'active', '2023-08-16 01:04:12', 'sean.mendoza', '71l6y', 'Entry Education', NULL, 'profile_22_1751353445.png'),
(24, 'Elritz', 'Crisanto', 'elritz.crisanto@entrygroup.com.au', 'ritztiong@gmail.com', '0961 289 3349', 'Student Support - Marking', 'active', '2023-08-21 01:04:12', 'elritz.crisanto', '96ewj', 'Entry Education', NULL, 'profile_24_1751354386.png'),
(25, 'Analiza', 'Taloban Gatbonton', 'analiza.gatbonton@entrygroup.com.au', 'analizagatbonton05@gmail.com', '0961 820 1167', 'Student Support - Marking', 'active', '2023-08-21 01:04:12', 'analiza.gatbonton', 'cjfvk', 'Entry Education', NULL, 'profile_25_1751353994.JPG'),
(26, 'Franklin Roos', 'Cinco Pabillano', 'estimating@empirewestelectrical.com.au', 'frankpabillano@gmail.com', '0961 498 0228', 'Electrical Estimator', 'active', '2023-11-03 01:04:12', 'franklin.pabillano', 'd54hj', 'onn one', NULL, NULL),
(27, 'Kristian David', 'Bansil', 'ITsupport@mtunderground.com', 'ian_pudz@icloud.com', '0939 905 0288', 'Web Developer / Admin & IT Support', 'active', '2024-02-12 01:04:12', 'kristian.bansil', 'c9zqn', 'Maintenance Tech', NULL, NULL),
(28, 'Louis Fernand', 'Austria', 'louis.austria@entrygroup.com.au', 'louisaustria0@gmail.com', '0998 423 4020', 'Graphic Designer', 'active', '2024-04-04 01:04:12', 'louis.austria', 'r14xu', 'Entry Education', NULL, 'profile_28_1751353318.jpeg'),
(29, 'Johana Rose', 'Perez Gueco', 'johanarose.gueco@entrygroup.com.au', 'johanagueco@gmail.com', '0906 213 7926', 'Student Support - Marking', 'active', '2024-04-04 01:04:12', 'johana.gueco', 'u1o3l', 'Entry Education', NULL, 'profile_29_1751450128.png'),
(30, 'Erika', 'Seriosa Pineda', 'erika.seriosa@entrygroup.com.au', 'rickzseriosa@gmail.com', '0926 355 6900', 'Student Support - Marking', 'active', '2024-04-04 01:04:12', 'erika.pineda', 'agsrw', 'Entry Education', NULL, 'profile_30_1751450142.png'),
(31, 'Jhunel Carlo', 'Traifalgar Samodio', 'jhunelcarlo.samodio@entrygroup.com.au', 'gun.lazuli@gmail.com', '0995 483 5711', 'Student Support - Marking', 'active', '2024-04-04 01:04:12', 'jhunel.samodio', '70twd', 'Entry Education', NULL, 'profile_31_1751598183.png'),
(33, 'Aldwin John', 'Arceo Lozano', 'aldwinjohn.lozano@entrygroup.com.au', 'imaj.lozano@gmail.com', '0949 369 7174', 'Sales New Student Enquiries', 'active', '2024-04-08 01:04:12', 'aldwin.lozano', 'r2hsw', 'Entry Education', NULL, 'profile_33_1751407949.jpg'),
(34, 'Yris Gaelle', 'Parreñas Camerino', 'yyrish@gmail.com', 'yyrish@gmail.com', '0912 937 9482', 'Student Support - Marking', 'active', '2024-05-01 01:04:12', 'yris.camerino', 'vijg7', 'Entry Education', NULL, NULL),
(35, 'Nika', 'Nueva Bacongallo', 'nika.bacongallo@gmail.com', 'nika.bacongallo@gmail.com', '0919 263 0516', 'Student Support - Marking', 'active', '2024-05-20 01:04:12', 'nika.bacongallo', 't80da', 'Entry Education', NULL, NULL),
(36, 'Denver Orlanda', 'Castillano', 'dcstudio.creative@gmail.com', 'dcstudio.creative@gmail.com', '0991 933 2312', 'Draftsman', 'active', '2024-06-10 01:04:12', 'denver.castillano', 'brcio', 'DNA Furniture & Cabinets', NULL, NULL),
(37, 'Marnie', 'Perez Catalogo', 'marniecatalogo99@gmail.com', 'marniecatalogo99@gmail.com', '0905 453 8974', 'Technical Student Support', 'active', '2024-06-10 01:04:12', 'marnie.catalogo', '2p658', 'Entry Education', NULL, NULL),
(38, 'Ryan Rex', 'Patrimonio', 'rexryanpatrimonio@gmail.com', 'rexryanpatrimonio@gmail.com', '0916 189 2527', 'Technical Student Support', 'active', '2024-06-10 01:04:12', 'ryan.patrimonio', 'e2gdf', 'Entry Education', NULL, NULL),
(41, 'Ma. Charisma S.', 'Platero', 'charisma.platero@gmail.com', 'charisma.platero@gmail.com', '09566998110', 'Estimator', 'active', '2024-07-22 01:04:12', 'charisma.platero', 'ia9vr', 'Fratelli Homes', NULL, NULL),
(43, 'Lovelaine', 'Gudoy Celeste', 'lovelaineceleste@yahoo.com', 'lovelaineceleste@yahoo.com', '09761349029', 'Sales New Student Enquiries', 'active', '2024-08-12 01:04:12', 'lovelaine.celeste', '91urk', 'Entry Education', NULL, NULL),
(46, 'Ivy', 'Nuñez', 'ivynunez26@gmail.com', 'ivynunez26@gmail.com', 'N/A', 'Student Support - Marking', 'active', '2024-08-12 01:04:12', 'ivy.nuñez', 'f1hqu', 'Entry Education', NULL, NULL),
(47, 'Glory Ann', 'Garcia Balderas', 'Glory@fratellihomeswa.com.au', 'gloryannbalderas@gmail.com', '0981 107 2866', 'Estimator', 'active', '2024-09-16 01:04:12', 'glory.balderas', '2i8ql', 'Fratelli Homes', NULL, 'profile_47_1751848664.png'),
(49, 'Julie Anne', 'Guinto Maclang', 'macjulg08@gmail.com', 'macjulg08@gmail.com', 'N/A', 'Student Support - Marking', 'active', '2024-10-14 01:04:12', 'julie.maclang', 'rvlft', 'Entry Education', NULL, NULL),
(50, 'Francis Eugene Aguhayon', 'Bondoc', 'francis.bondoc22@gmail.com', 'francis.bondoc22@gmail.com', '629-683-416-000', 'Draftsman', 'active', '2025-04-29 14:02:24', 'francis.bondoc', 'vby8e', 'Entry Education', NULL, 'profile_50_1751860113.jpg'),
(52, 'Althea Tansingco', 'Makabenta', 'document.control@bugardi.com.au', 'atansingcomakabenta@yahoo.com', '165-661-778-000', 'Document Controller', 'active', '2025-04-29 14:02:24', 'althea.makabenta', '6i8to', 'Bugardi Contracting', NULL, NULL),
(53, 'Christian Nioda', 'Mar', 'christianmar673@gmail.com', 'christianmar673@gmail.com', '387-307-553-000', 'Sales New Student Enquiries', 'active', '2025-04-29 14:02:24', 'christian.mar', 'lzmb2', 'Entry Education', NULL, 'profile_53_1751526003.jpg'),
(55, 'Jeffry Tuazon', 'Macapagal', 'jeff.macapagal017@gmail.com', 'jeff.macapagal017@gmail.com', '513-015-013-000', 'Operations Administrator', 'active', '2025-04-29 14:02:24', 'jeffry.macapagal', 'nzkyl', 'TRSWA', NULL, NULL),
(56, 'Allen Sobrepeña', 'Capati', 'allen.capati@entrygroup.com.au', 'allen.capati95@gmail.com', '468-497-485-000', 'Student Support - Marking', 'active', '2025-04-29 14:02:24', 'allen.capati', 'n02or', 'Entry Education', NULL, 'profile_56_1751353871.png'),
(57, 'Angelica Rosario', 'Estanio', 'angelica.estanio@entrygroup.com.au', 'angelica.estanio4@gmail.com', '09666483316', 'Student Support - Marking', 'active', '2025-04-29 14:02:24', 'angelica.estanio', 'qm6fh', 'Entry Education', NULL, NULL),
(58, 'Adonis Del Mundo', 'Jabinal', 'adonis.jabinal@bugardi.com.au', 'donjabinal@gmail.com', '175-092-008-000', 'Project Coordinator', 'active', '2025-04-29 14:02:24', 'adonis.jabinal', 'aoqit', 'Bugardi Contracting', NULL, NULL),
(59, 'Joshwea Mercado', 'Monis', 'joshwea.monis@entrygroup.com.au', 'mjoshwea@gmail.com', '332-760-833-000', 'Student Support - Marking', 'active', '2025-04-29 14:02:24', 'joshwea.monis', 'bjor0', 'Entry Education', NULL, NULL),
(60, 'Janeth Sedon', 'Solayao', 'janeth.solayao@entrygroup.com.au', 'janethsolayao32@gmail.com', 'TO FOLLOW', 'Student Support - Marking', 'active', '2025-04-29 14:02:24', 'janeth.solayao', 'i4yzp', 'Entry Education', NULL, NULL),
(61, 'Ray Jinder Villena', 'Singh', 'rvschk@gmail.com', 'rvschk@gmail.com', '350-760-267-000', 'Executive Assistant', 'active', '2025-04-29 14:02:24', 'rj.singh', 'lpzom', 'Rowland Plumbing & Gas', NULL, NULL),
(62, 'Shirmiley Canlas', 'Quizon', 'shirmiley.quizon@bugardi.com.au', 'shirmiley.quizon@gmail.com', '210-283-638-000', 'Recruitment Mobilization Officer', 'active', '2025-04-29 14:02:24', 'shirmiley.quizon', 'm2g7l', 'Bugardi Contracting', NULL, NULL),
(63, 'Maria Ñina Dizon', 'Dollentes', 'nina.dollentes@entrygroup.com.au', 'marianinadollentes@gmail.com', '09122208976', 'Accountant', 'active', '2025-04-29 14:02:24', 'Nina.dollentes', 'qp4i9', 'Entry Education', NULL, 'profile_63_1752186681.jpeg'),
(64, 'Jerzi Chezka Medel', 'Libatique', 'jerzi.libatique@entrygroup.com.au', 'jerzichezkamedel@gmail.com', '396-119-405-000', 'Accountant', 'active', '2025-04-29 14:02:24', 'jerzi.libatique', '9ta18', 'Entry Education', NULL, 'profile_64_1751353441.jpg'),
(65, 'Sabando', 'Nuñeza Dou Lester', 'lester.nuneza@bugardi.com.au', 'lesternuneza@gmail.com', '+639618436508', 'HSEQ Assistant Manager', 'active', '2025-04-29 14:02:24', 'Lester.nuñeza ', '3407m', 'Bugardi', NULL, NULL),
(66, 'Dionicio', 'Ocampo Godwin', 'tgodbtg04@gmail.com', 'tgodbtg04@gmail.com', '09603985500', 'Tax Accountant', 'active', '2025-04-29 14:02:24', 'godwin.ocampo ', 'udlag', 'Denning & Associates', NULL, NULL),
(67, 'Apryl Ordonio', 'Pasion', 'apryl.pasion@bugardi.com.au', 'aprylpolicarpio@gmail.com', 'TO FOLLOW', 'Recruitment Mobilization Officer', 'active', '2025-04-29 14:02:24', 'apryl.pasion', 'ovcp0', 'Bugardi Contracting', NULL, NULL),
(68, 'Christine Khlaryss', 'Angeles', 'christinekhlaryss@gmail.com', 'christinekhlaryss@gmail.com', '351-635-569-000', 'Tax Accountant', 'active', '2025-04-29 14:02:24', 'christine.angeles', 'bnl8k', 'Denning', NULL, NULL),
(69, 'Trisha Mae Adriano', 'McGregor', 'trisha_mcgregor@yahoo.com', 'trisha_mcgregor@yahoo.com', '09289852739', 'Renovation Draftsman', 'active', '2025-04-29 14:02:24', 'trisha.mcgregor', 'biv1r', 'Ridge Renovation', NULL, NULL),
(70, 'John Michael Comprado', 'Briones', 'jamenabriones14@gmail.com', 'jamenabriones14@gmail.com', '620-743-947-000', 'Commercial Estimator', 'active', '2025-04-29 14:02:24', 'jm.briones', 'w1g89', 'TRSWA', NULL, NULL),
(71, 'Precious Zahra Cortez', 'Cabusao', 'zahracortez95@gmail.com', 'zahracortez95@gmail.com', '326-526-766-000', 'Hydraulics Estimator', 'active', '2025-04-29 14:02:24', 'zahra.cabusao', 'dwzgl', 'Leeway Group', NULL, NULL),
(72, 'Milbert ', 'Sambile', 'milbert@millersroofing.com.au', 'milbert.sambile@gmail.com', '09663995493', 'Estimator', 'active', '2025-06-03 01:08:23', 'Milbert.Sambile', 'Milbert', 'Miller\'s Roofing', NULL, 'profile_72_1751614448.png'),
(73, 'Ryan Arwin', 'David', 'davidryarwin@gmail.com', 'davidryarwin@gmail.com', NULL, 'Operations Admin', 'active', '2025-06-24 00:11:24', 'Ryan.David', 'TTS_Ryan', 'TTS', NULL, NULL),
(74, 'John Bryan', 'Alvarez', 'johnalvarez930@gmail.com', 'johnalvarez930@gmail.com', NULL, 'Operations Admin', 'active', '2025-06-24 00:11:24', 'John.Alvarez', 'TTS_John', 'TTS', NULL, NULL),
(75, 'Oliva', 'Bautista', 'olivesantos.bautista@gmail.com', 'olivesantos.bautista@gmail.com', '', 'Operations Admin', 'active', '2025-06-24 00:11:24', 'Oliva.Bautista', 'TTS_Oliva', 'TTS', NULL, NULL),
(76, 'Sarah', 'Caraan', 'sarahcaraan31@gmail.com', 'sarahcaraan31@gmail.com', '', 'Operations Admin', 'active', '2025-06-24 00:11:24', 'Sarah.Caraan', 'TTS_Sarah', 'TTS', NULL, NULL),
(77, 'Alfie', 'Guillermo', 'alfie.guillermo0219@gmail.com', 'alfie.guillermo0219@gmail.com', NULL, 'Estimator', 'active', '2025-06-24 00:11:24', 'Alfie.Guillermo', 'TTS_Alfie', 'TTS', NULL, NULL),
(78, 'Brittany', 'Yulo', 'yulobrittany@gmail.com', 'brittany@trswa.net.au', '09201337394', 'Commercial Assistant Administrator', 'active', '2025-04-29 14:02:24', 'Brittany.Yulo', 'Trswabrit', 'TRSWA', NULL, NULL),
(79, 'John ', 'Sanoza', 'sanozajohn@gmail.com', 'sanozajohn@gmail.com', NULL, 'Senior Full Stack Developer ', NULL, '2025-07-03 05:24:31', 'John.Sanoza', 'Viper_john', 'Viper', NULL, NULL),
(80, 'Marianne Jae ', 'Fernandez', 'mjae.fernandez@yahoo.com', 'mjae.fernandez@yahoo.com', '09610912234', 'Administrative Assistant', 'active', '2025-07-04 07:37:19', 'Jae.Fernandez', 'Millers_Mj', 'Miller\'s Roofing', NULL, NULL),
(81, 'Sherry Rose Ann', 'Patawaran', 'marketing@hammerhire.com.au', 'sherryrosepatawaran@gmail.com', '09398522815', 'Marketing Coordinator', 'active', '2025-07-04 07:37:19', 'Sherry.Patawaran', 'Hammerhire_Sherry', 'HammerHire', NULL, NULL),
(82, 'Gabriel', 'Capiral', 'capiralgabriel@gmail.com', NULL, '', '', 'active', '2025-07-13 23:16:10', 'Gabriel.Capiral', 'Fratelli_Gabriel', 'Fratelli Homes', NULL, NULL),
(83, 'Joshua', 'Manalili', '', NULL, '', ' Network Controller', 'active', '2025-07-13 23:18:58', 'Joshua.Manalili', 'busqld_Josh', 'BUSQLD', NULL, NULL),
(84, 'Roi Dane', 'Pangilinan', '', NULL, '', ' Network Controller', 'active', '2025-07-13 23:19:28', 'Roi.Pangilinan', 'busqld_roi', 'BUSQLD', NULL, NULL),
(85, 'Paul', 'Pasion', '', NULL, '', ' Network Controller', 'active', '2025-07-13 23:20:02', 'Paul.Pasion', 'busqld_paul', 'BUSQLD', NULL, NULL),
(86, 'Jonas', 'Dela Cruz', '', NULL, '', 'Draftsman', 'active', '2025-07-13 23:18:11', 'Jonas.Delacruz', 'alpha_jonas', 'Alpha Industry', NULL, NULL),
(1002, 'Neil Anthony', 'Costelloe', 'Neil.Costelloe@resourcestaff.com.ph', 'neilcosetelloe@gmail.com', NULL, 'General Manager', 'active', '2024-05-19 16:00:00', 'neil.costelloe', 'ypv9h', 'RSS', NULL, NULL),
(1003, 'Cristina Miranda', 'Pangan', 'Tina.Pangan@resourcestaff.com.ph', 'thine2miranda@gmail.com', '0915 056 1780', 'Executive Assistant to the General Manager', 'active', '2024-03-31 16:00:00', 'tina.pangan', 'Maganda', 'RSS', NULL, NULL),
(1004, 'Rica Joy Viray', 'Tolomia', 'Rica.Tolomia@resourcestaff.com.ph', 'Rica.Tolomia@resourcestaff.com.ph', '0917 389 7962', 'TA/HR Specialist', 'active', '2024-08-11 16:00:00', 'rj.tolomia', 'gojwd', 'RSS', NULL, 'profile_1004_1751339641.png'),
(1005, 'Johsua Torninos', 'Dimla', 'johsua.dimla1986@gmail.com', 'johsua.dimla1986@gmail.com', '0933 430 3081', 'Facilities and Admin Support', 'active', '2024-09-29 16:00:00', 'johsua.dimla', 'r9em0', 'RSS', NULL, NULL),
(1006, 'Cedrick', 'Arnigo', 'Cedrick.Arnigo@resourcestaff.com.ph', 'cedrickarnigo1723@gmail.com', '09938642974', 'IT Support Specialist', 'active', '2025-05-27 23:09:46', 'Cedrick.Arnigo', 'Gr33n$$wRf', 'RSS', NULL, 'profile_1006_1751347603.jpg'),
(1007, 'Peach', 'Herrera', 'herrerafelicci@gmail.com', 'herrerafelicci@gmail.com', '0903323232', 'Admin', 'active', '2025-06-02 06:19:09', 'Peach.Herrera', 'Gh0920', 'RSS', NULL, 'profile_1007.jpg'),
(1009, 'Resty', 'Nazareno', 'rjmanago@gmail.com', 'rjmanago@gmail.com', '09763659773', 'IT Intern', 'active', '2025-06-10 02:48:29', 'Kiras001', 'vosfows12', 'RSS', NULL, 'profile_1009_1749770448.jpeg'),
(1017, 'abcd', 'xyz', '', NULL, '', '', 'active', '2025-07-14 03:42:36', 'admin_hr', '$2y$10$beMGoLrUEW5JPB7qxpb12.5aaQthrgZzwxm7KCsFEqwJZliieTv6e', NULL, NULL, NULL),
(1019, 'SON ', 'GOKU ', 'son.goku@gmail.com', NULL, 'to follow', 'IT Intern', 'active', '2025-07-18 05:54:27', 'son.goku', '$2y$10$tsGKC81HFsPn6sYg0XR4puEr1wGcOihBpJinEx8DMCFjz8u5DNjrO', NULL, NULL, NULL),
(1020, 'Lance', 'Libo', 'lancejomerlibo@gmail.com', NULL, '0951106693', 'Front-end Flutter Developer', 'active', '2025-07-18 06:06:34', 'lance.libo', '$2y$10$IuB/ZIoRmpNZSZywPHgPAuaANLiLvQbOVdH.cpFAYoFrzxrsgWtuO', NULL, NULL, NULL);

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
  `pagibig` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `employee_checklist`
--

INSERT INTO `employee_checklist` (`id`, `employee_id`, `letter_offer`, `employment_contract`, `medical`, `nbi_clearance`, `diploma_tor`, `psa`, `sss`, `tin`, `philhealth`, `coe`, `created_at`, `updated_at`, `pagibig`) VALUES
(4, 1009, 'letter_offer_6874b5471ea52_Smiley.svg.png', NULL, NULL, 'nbi_clearance_6875b8b361499_Smiley.svg.png', NULL, NULL, NULL, NULL, NULL, NULL, '2025-07-14 07:31:22', '2025-07-15 02:12:37', 'pagibig_6875b91572260_Smiley.svg.png'),
(5, 1, NULL, 'employment_contract_6875ad940f09e_Employment Contract -  Vincent Antonio.pdf', 'medical_6875ad940f3ab_Medical Result - Vincent Antonio.pdf', NULL, NULL, NULL, 'sss_6875ad940ffd2_SSS  - Vincent Antonio.jpeg', 'tin_6875ad941013c_TIN- Vincent Antonio.jpg', 'philhealth_6875ad94118f2_PhilHealth - Vincent Antonio.HEIC', NULL, '2025-07-14 07:41:55', '2025-07-15 02:30:37', 'pagibig_6875bd4d27db8_Pagibig  - Vincent Antonio.HEIC'),
(6, 76, 'letter_offer_6874b5deb7928_CARAAN SARAH - Job Offer.pdf', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-07-14 07:46:38', '2025-07-14 07:46:38', NULL),
(7, 73, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-07-14 07:47:49', '2025-07-14 07:47:49', NULL),
(8, 27, NULL, 'employment_contract_6875c07608806_Kristian Bansil - Employment Contract.pdf', 'medical_6875c07608cc1_Medical Result - Kristian Bansil.pdf', 'nbi_clearance_6875c07609e0e_NBI Clearance -  Kristian Bansil.pdf', NULL, NULL, NULL, NULL, NULL, NULL, '2025-07-14 07:55:20', '2025-07-15 02:44:06', NULL),
(9, 63, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-07-14 08:00:05', '2025-07-14 08:00:05', NULL),
(10, 69, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-07-14 08:00:05', '2025-07-14 08:00:05', NULL),
(11, 4, NULL, NULL, 'medical_6875a0ad51019_Medical Result - Joel Alimurong.pdf', 'nbi_clearance_6875a0ad51dd6_NBI Clearance - Joel Alimurong.pdf', NULL, NULL, 'sss_6875a0ad52672_SSS - Joel Alimurong.PNG', 'tin_6875a0ad529c6_TIN - Joel Alimurong.PNG', 'philhealth_6875a0ad52d3f_Philhealth - Joel Alimurong.PNG', NULL, '2025-07-14 08:00:10', '2025-07-15 00:28:29', NULL),
(12, 66, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-07-14 08:00:19', '2025-07-14 08:00:19', NULL),
(13, 9, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-07-14 08:00:24', '2025-07-14 08:00:24', NULL),
(14, 41, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-07-14 08:00:50', '2025-07-14 08:00:50', NULL),
(15, 70, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-07-14 08:00:51', '2025-07-14 08:00:51', NULL),
(16, 55, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-07-14 08:01:23', '2025-07-14 08:01:23', NULL),
(17, 20, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-07-14 08:01:44', '2025-07-14 08:01:44', NULL),
(18, 31, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-07-14 08:01:58', '2025-07-14 08:01:58', NULL),
(19, 47, 'letter_offer_6875c8b12d216_Letter of Offer - Glory Ann Balderas.pdf', 'employment_contract_6875c8b130ed1_Employment Contract - Balderas, Glory Ann G.pdf', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-07-14 08:02:55', '2025-07-15 03:19:13', NULL),
(20, 64, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-07-14 08:03:31', '2025-07-14 08:03:31', NULL),
(21, 86, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-07-14 08:03:40', '2025-07-14 08:03:40', NULL),
(22, 1007, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-07-14 08:03:45', '2025-07-14 08:03:45', NULL),
(23, 72, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-07-14 08:04:09', '2025-07-14 08:04:09', NULL),
(24, 1006, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-07-14 08:05:28', '2025-07-14 08:05:28', NULL),
(25, 17, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-07-14 08:05:51', '2025-07-14 08:05:51', NULL),
(26, 29, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-07-14 08:09:30', '2025-07-14 08:09:30', NULL),
(27, 74, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-07-14 08:11:12', '2025-07-14 08:11:12', NULL),
(28, 26, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-07-14 08:14:23', '2025-07-14 08:14:23', NULL),
(29, 7, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-07-14 08:23:41', '2025-07-14 08:23:41', NULL),
(30, 75, 'letter_offer_6875cb2dca206_Signed-BAUTISTA, OLIVA  - Job Offer.pdf', 'employment_contract_6875cb2dca523_Signed Employment Contract - Oliva Bautista (1).pdf', 'medical_6875cb2dca7e5_Medical Result - Oliva Bautista.pdf', 'nbi_clearance_6875cb2dcb89c_NBICLEARANCE_OLIVABAUTISTA (1).pdf', 'diploma_tor_6875cb2dcbd10_DIPLOMA_OLIVABAUTISTA (1).pdf', 'psa_6875cb2dcc069_BC_OLIVABAUTISTA (1).pdf', 'sss_6875cb2dcc535_SSS_OLIVABAUTISTA (1).JPG', 'tin_6875cb2dcc65a_TIN_OLIVABAUTISTA (1).JPG', 'philhealth_6875cb2dcc72d_OLIVABAUTISTA_PHILHEALTH (1).jpg', 'coe_6875cb2dcc8e1_COE_OLIVABAUTISTA (1).pdf', '2025-07-14 08:27:29', '2025-07-15 03:29:49', 'pagibig_6875cb2dcc7fa_PAGIBIG_OLIVABAUTISTA (1).JPG'),
(31, 77, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-07-14 08:30:15', '2025-07-14 08:30:15', NULL),
(32, 12, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-07-14 08:33:11', '2025-07-14 08:33:11', NULL),
(33, 49, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-07-14 08:34:31', '2025-07-14 08:34:31', NULL),
(34, 82, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-07-14 08:35:32', '2025-07-14 08:35:32', NULL),
(35, 1003, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-07-14 08:36:31', '2025-07-14 08:36:31', NULL),
(36, 8, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-07-14 08:49:53', '2025-07-14 08:49:53', NULL),
(37, 57, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-07-14 08:57:37', '2025-07-14 08:57:37', NULL),
(38, 78, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-07-14 09:01:59', '2025-07-14 09:01:59', NULL),
(39, 50, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-07-14 09:04:07', '2025-07-14 09:04:07', NULL),
(40, 61, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-07-14 09:13:16', '2025-07-14 09:13:16', NULL),
(41, 18, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-07-14 09:25:08', '2025-07-14 09:25:08', NULL),
(42, 53, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-07-14 09:39:57', '2025-07-14 09:39:57', NULL),
(43, 3, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-07-14 09:40:01', '2025-07-14 09:40:01', NULL),
(44, 56, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-07-14 09:40:12', '2025-07-14 09:40:12', NULL),
(45, 46, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-07-14 09:51:19', '2025-07-14 09:51:19', NULL),
(46, 21, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-07-14 09:56:18', '2025-07-14 09:56:18', NULL),
(47, 34, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-07-14 09:58:02', '2025-07-14 09:58:02', NULL),
(48, 5, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-07-14 10:00:02', '2025-07-14 10:00:02', NULL),
(49, 35, NULL, 'employment_contract_6875c67d29bf2_Employment Contract -  - Nika Bacongallo.pdf', 'medical_6875c67d29e9c_Medical Result - NIka Bacongallo.pdf', 'nbi_clearance_6875c67d2adff_NBI Clearance - Nika Bacongallo.pdf', NULL, NULL, 'sss_6875c67d2bd6b_SSS - Nika Bacongallo.jpg', 'tin_6875c67d2e0b0_TIN - Nika Bacongallo.jpg', 'philhealth_6875c67d2fe35_Philhealth - Nika Bacongallo.jpg', NULL, '2025-07-14 10:00:08', '2025-07-15 03:09:49', 'pagibig_6875c67d31320_Pag-ibig - Nika Bacongallo.jpg'),
(50, 28, NULL, 'employment_contract_6875b9ccc7ac5_Austria, Louis - Employment Contract.pdf', 'medical_6875b9ccc7d4d_Medical Result - Louis Austria.pdf', 'nbi_clearance_6875b9ccc8d2b_NBI CLEARANCE -  Louis Austria.pdf', NULL, NULL, NULL, 'tin_6875b9ccc9bc1_TIN - Louis Austria.pdf', NULL, 'coe_6875b9cccb006_COE - Louis Austria.pdf', '2025-07-14 10:00:24', '2025-07-15 02:15:40', NULL),
(51, 22, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-07-14 10:00:31', '2025-07-14 10:00:31', NULL),
(52, 37, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-07-14 10:01:22', '2025-07-14 10:01:22', NULL),
(53, 67, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-07-14 10:01:47', '2025-07-14 10:01:47', NULL),
(54, 52, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-07-14 10:02:45', '2025-07-14 10:02:45', NULL),
(55, 71, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-07-14 10:06:12', '2025-07-14 10:06:12', NULL),
(56, 62, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-07-14 10:21:13', '2025-07-14 10:21:13', NULL),
(57, 25, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-07-14 10:57:11', '2025-07-14 10:57:11', NULL),
(58, 24, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-07-14 10:57:17', '2025-07-14 10:57:17', NULL),
(59, 13, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-07-14 10:57:18', '2025-07-14 10:57:18', NULL),
(60, 38, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-07-14 10:59:02', '2025-07-14 10:59:02', NULL),
(61, 43, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-07-14 11:01:36', '2025-07-14 11:01:36', NULL),
(62, 60, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-07-14 11:02:00', '2025-07-14 11:02:00', NULL),
(63, 1005, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-07-14 11:03:20', '2025-07-14 11:03:20', NULL),
(64, 65, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-07-14 11:05:33', '2025-07-14 11:05:33', NULL),
(65, 58, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-07-14 12:05:01', '2025-07-14 12:05:01', NULL),
(66, 81, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-07-14 21:51:29', '2025-07-14 21:51:29', NULL),
(67, 68, 'letter_offer_6875a7c5e5a59_Letter of Offer - Christine Angeles.pdf', 'employment_contract_6875a7c5e878d_Employment Contract - Christine Angeles.pdf', 'medical_6875a7c5ef28d_Medical Result - Christine Angeles.pdf', NULL, NULL, NULL, 'sss_6875a7c5eff0c_SSS - Christine Angeles.pdf', 'tin_6875a7c5f12d9_TIN - Christine Angeles.pdf', 'philhealth_6875a7c5f212a_Philhealth - Christine Angeles.pdf', 'coe_6875a7c5f2f61_COE - Christine Angeles.pdf', '2025-07-14 21:53:50', '2025-07-15 02:24:03', 'pagibig_6875bbc315ce2_Pagibig - Christine Angeles.pdf'),
(68, 36, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-07-14 21:55:24', '2025-07-14 21:55:24', NULL),
(69, 80, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-07-14 21:57:53', '2025-07-14 21:57:53', NULL),
(70, 6, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-07-14 22:08:20', '2025-07-14 22:08:20', NULL),
(71, 33, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-07-14 22:10:18', '2025-07-14 22:10:18', NULL),
(72, 1004, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-07-14 22:52:21', '2025-07-14 22:52:21', NULL),
(73, 10, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-07-14 22:52:26', '2025-07-14 22:52:26', NULL),
(74, 85, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-07-14 23:07:45', '2025-07-14 23:07:45', NULL),
(75, 83, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-07-14 23:09:18', '2025-07-14 23:09:18', NULL),
(76, 84, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-07-14 23:09:40', '2025-07-14 23:09:40', NULL),
(77, 14, NULL, 'employment_contract_687596face054_Employee Contract - Jillian Agas.pdf', 'medical_687596bf793d2_Medical Result - Jillian Agas.pdf', 'nbi_clearance_687596bf7a4d2_NBI Clearance - Jillian Agas.pdf', NULL, NULL, 'sss_687596bf7b46c_SSS - Jillian Agas.pdf', 'tin_687596bf7cb23_TIN - Jillian Agas.pdf', 'philhealth_687596bf7e1a1_Philhealth - Jillian Agas.pdf', NULL, '2025-07-14 23:46:07', '2025-07-15 02:18:31', 'pagibig_6875ba774142c_Pagibig - Jillian Agas.pdf'),
(78, 15, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-07-15 00:00:53', '2025-07-15 00:00:53', NULL),
(79, 16, NULL, 'employment_contract_68759d41611a2_Employment Contract - Ian Myco Aguilar.pdf', 'medical_68759d41659a9_Medical Result - Ian Myco Aguilar.pdf', 'nbi_clearance_68759d41668d4_NBI Clearance - Ian Myco Aguilar.pdf', NULL, NULL, 'sss_68759d416740a_SSS - Ian Myco Aguilar.pdf', 'tin_68759d4168692_TIN - Ian Myco Aguilar.pdf', 'philhealth_68759d41699cc_Philhealth - Ian Myco Aguilar.pdf', 'coe_68759d416aa82_COE - Ian Myco Aguilar.pdf', '2025-07-15 00:13:53', '2025-07-15 02:21:07', 'pagibig_6875bb1358d90_Pagibig - Ian Myco Aguilar.pdf'),
(80, 79, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-07-15 06:53:07', '2025-07-15 06:53:07', NULL),
(81, 59, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-07-15 22:54:53', '2025-07-15 22:54:53', NULL),
(82, 30, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-07-15 23:02:56', '2025-07-15 23:02:56', NULL),
(83, 11, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-07-16 23:12:24', '2025-07-16 23:12:24', NULL);

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
  `leave_type` enum('VL','SL','SPL','Half_SL','Half_VL') NOT NULL,
  `balance` decimal(5,2) DEFAULT 0.00,
  `year` year(4) NOT NULL
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
  `explanation` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `leave_requests`
--

INSERT INTO `leave_requests` (`id`, `employee_id`, `leave_type`, `start_date`, `end_date`, `reason`, `status`, `created_at`, `leave_dates`, `attachment_lr`, `notified`, `explanation`) VALUES
(106, 33, '', '2025-07-28', '2025-07-28', 'Important business to attend to', 'rejected', '2025-07-01 06:59:27', '', NULL, 1, 'you must upload an attachment'),
(107, 35, '', '2025-07-02', '2025-07-02', 'Family Holiday', 'approved', '2025-07-01 07:09:43', '', 'lr_686389b7093ab.png', 1, NULL),
(108, 35, '', '2025-07-06', '2025-07-09', 'Family Holiday', 'approved', '2025-07-01 07:10:37', '', 'lr_686389ed5026e.png', 1, NULL),
(109, 24, '', '2025-07-07', '2025-07-07', 'I would be attending a family gathering', 'approved', '2025-07-01 07:13:07', '', 'lr_68638a838f920.png', 1, NULL),
(110, 67, '', '2025-07-08', '2025-07-08', 'approved by client', 'rejected', '2025-07-01 07:13:34', '', 'lr_68638a9ea1d4b.png', 0, 'This is tagged as LWOP as you are not eligible to use vacation leave credits yet.'),
(111, 24, '', '2025-07-16', '2025-07-17', 'I would be attending my brother\'s graduation. Francis was able to approve my leave on the said dates. I have attached a screenshot of his approval.', 'approved', '2025-07-01 07:14:36', '', 'lr_68638adcbaf11.png', 1, NULL),
(118, 13, '', '2025-07-02', '2025-07-02', 'Body pain from falling down the stairs', 'approved', '2025-07-04 01:14:18', '', 'lr_68672aea471a2.png', 1, NULL),
(119, 1, '', '2025-07-02', '2025-07-02', 'I was not able to attend work on 02 July 2025, due to not feeling well stomach ache.\r\n\r\nKindly see the attached file.\r\nThank you.', 'approved', '2025-07-04 02:29:46', '', 'lr_68673c9a61957.png', 1, NULL),
(120, 21, '', '2025-07-01', '2025-07-02', 'Dysmenorrhea', 'approved', '2025-07-05 00:26:01', '', NULL, 1, NULL),
(121, 43, '', '2025-07-04', '2025-07-04', 'Persistent cough for more than 2 weeks needed medical advice and diagnosis from a doctor.', 'approved', '2025-07-06 06:39:10', '', 'lr_686a1a0e1f281.png', 1, NULL),
(122, 1, 'lwop', '2025-07-03', '2025-07-03', 'Unable to attend work due to an emergency matter.', 'approved', '2025-07-07 04:23:14', '', 'lr_686b4bb22898d.png', 1, NULL),
(123, 1, '', '2025-07-23', '2025-07-25', 'Writing to formally request leave from July 23 to July 25 as I will be travelling internationally during this period.', 'approved', '2025-07-07 04:25:33', '', 'lr_686b4c3d8d152.png', 1, NULL),
(124, 8, '', '2025-07-09', '2025-07-09', 'I’ve already talked HR about this. I’ll use up my leave since my last day at Entry Ed is on July 20.', 'approved', '2025-07-07 22:59:55', '', 'lr_686c516b39c33.pdf', 0, NULL),
(125, 8, '', '2025-07-11', '2025-07-11', 'I’ve already emailed HR about this. I’ll use up my leave since my last day at Entry Ed is on July 20.', 'approved', '2025-07-07 23:00:21', '', 'lr_686c5185881ee.pdf', 0, NULL),
(126, 8, '', '2025-07-16', '2025-07-16', 'I’ve already emailed HR about this. I’ll use up my leave since my last day at Entry Ed is on July 20.', 'approved', '2025-07-07 23:00:37', '', 'lr_686c5195eb4b3.pdf', 0, NULL),
(127, 8, '', '2025-07-18', '2025-07-18', 'I’ve already emailed HR about this. I’ll use up my leave since my last day at Entry Ed is on July 20.', 'approved', '2025-07-07 23:00:52', '', 'lr_686c51a498e84.pdf', 0, NULL),
(128, 8, '', '2025-07-10', '2025-07-10', 'I’ve already emailed HR about this. I’ll use up my leave since my last day at Entry Ed is on July 20.', 'approved', '2025-07-07 23:01:26', '', 'lr_686c51c62a717.pdf', 1, NULL),
(129, 8, '', '2025-07-15', '2025-07-15', 'I’ve already emailed HR about this. I’ll use up my leave since my last day at Entry Ed is on July 20.', 'approved', '2025-07-07 23:01:39', '', 'lr_686c51d352b17.pdf', 1, NULL),
(130, 8, '', '2025-07-17', '2025-07-17', 'I’ve already emailed HR about this. I’ll use up my leave since my last day at Entry Ed is on July 20.', 'approved', '2025-07-07 23:02:00', '', 'lr_686c51e876601.pdf', 1, NULL),
(131, 8, '', '2025-07-04', '2025-07-04', 'I’ve already emailed HR about this. I’ll use up my leave since my last day at Entry Ed is on July 20.', 'approved', '2025-07-07 23:06:06', '', 'lr_686c52de3b484.pdf', 1, NULL),
(132, 8, '', '2025-07-02', '2025-07-03', 'I’ve already emailed HR about this. I’ll use up my leave since my last day at Entry Ed is on July 20.', 'approved', '2025-07-07 23:06:24', '', 'lr_686c52f0a8f9d.pdf', 1, NULL),
(133, 56, '', '2025-08-15', '2025-08-15', 'Travel abroad', 'approved', '2025-07-08 00:48:10', '', NULL, 0, NULL),
(134, 56, '', '2025-08-19', '2025-08-19', 'Travel abroad', 'approved', '2025-07-08 00:48:41', '', NULL, 1, NULL),
(135, 56, '', '2025-08-20', '2025-08-20', 'Travel abroad', 'approved', '2025-07-08 00:50:08', '', NULL, 1, NULL),
(136, 29, '', '2025-07-23', '2025-07-26', 'International travel.', 'approved', '2025-07-08 23:06:09', '', 'lr_686da461a8ebe.png', 1, NULL),
(137, 31, '', '2025-07-23', '2025-07-25', 'International Flight', 'approved', '2025-07-08 23:09:31', '', 'lr_686da52b75168.png', 1, NULL),
(138, 30, '', '2025-07-19', '2025-07-19', 'International Travel', 'approved', '2025-07-08 23:48:40', '', 'lr_686dae5888252.png', 1, NULL),
(139, 30, '', '2025-07-23', '2025-07-26', 'International Travel', 'approved', '2025-07-08 23:49:09', '', 'lr_686dae751dd02.png', 1, NULL),
(140, 6, '', '2025-07-09', '2025-07-09', 'Accompany my wife at the airport', 'approved', '2025-07-09 00:00:51', '', 'lr_686db133e4795.png', 1, NULL),
(141, 27, '', '2025-07-09', '2025-07-09', 'Too much pain with my back.', 'approved', '2025-07-09 02:22:52', '', NULL, 1, NULL),
(142, 4, '', '2025-07-22', '2025-07-25', 'I will be traveling overseas to Taiwan for a personal vacation.', 'approved', '2025-07-09 06:20:19', '', 'lr_686e0a23bdb96.png', 0, NULL),
(143, 53, 'vacation', '2025-07-31', '2025-07-31', 'Leave Request for Sister’s Graduation.', 'approved', '2025-07-10 01:15:39', '', 'lr_686f143b3a841.png', 1, NULL),
(144, 33, 'vacation', '2025-07-28', '2025-07-28', 'Important Matter to Attend to.', 'approved', '2025-07-10 04:16:43', '', 'lr_686f3eab58f48.png', 1, NULL),
(145, 49, 'sick', '2025-07-13', '2025-07-13', 'check up of my mom', 'rejected', '2025-07-10 21:52:48', '', 'lr_68703630de8b9.jpg', 1, 'The issue has been settled. July 14, 2025 has been tagged as undertime.'),
(146, 57, 'sick', '2025-07-10', '2025-07-10', 'Not feeling well because of sickness', 'approved', '2025-07-11 10:01:43', '', 'lr_6870e1071902b.png', 1, NULL),
(147, 11, 'vacation', '2025-07-10', '2025-07-10', 'To process some documents', 'approved', '2025-07-13 06:31:57', '', 'lr_687352dd623b0.png', 1, NULL),
(148, 1004, 'vacation', '2025-07-14', '2025-07-15', 'Test', 'approved', '2025-07-14 00:25:56', '', 'lr_68744e94070d3.jpg', 1, NULL),
(149, 38, 'vacation', '2025-07-16', '2025-07-16', 'To celebrate mom\'s birthday. (approved by Team Leader, please see attached snippet below)', 'rejected', '2025-07-15 02:31:30', '', NULL, 1, 'This is acknowledged. However, since there is no attachment included on our end we will be kindly asking you to file a leave request again.'),
(150, 15, 'sick', '2025-07-01', '2025-07-01', 'Due to a slight fever and cold.', 'approved', '2025-07-15 02:34:51', '', 'lr_6875be4b07564.pdf', 1, NULL),
(151, 15, 'sick', '2025-07-14', '2025-07-14', 'Due to sudden bout of acute diarrhea', 'approved', '2025-07-15 02:35:33', '', 'lr_6875be750095e.pdf', 1, NULL),
(152, 49, 'vacation', '2025-07-02', '2025-07-02', 'Extending my leave today, July 2, 2025, due to flooding in my area. Apologies for the short notice. I’ll return once conditions improve.', 'approved', '2025-07-15 03:07:00', '', 'lr_6875c5d490ea1.png', 1, NULL),
(153, 20, 'vacation', '2025-07-21', '2025-07-25', 'Travel to Taiwan', 'approved', '2025-07-15 06:28:21', '', 'lr_6875f505ef577.png', 1, NULL),
(154, 12, 'vacation', '2025-07-04', '2025-07-04', 'Please see the attached file of my approved leave. July 04, 2025', 'approved', '2025-07-15 08:24:56', '', 'lr_687610589d9a6.pdf', 1, NULL),
(155, 5, 'vacation', '2025-07-07', '2025-07-07', 'Family gathering', 'approved', '2025-07-16 07:11:40', '', 'lr_687750acda117.png', 1, NULL),
(156, 16, 'vacation', '2025-07-01', '2025-07-01', 'Family Event', 'approved', '2025-07-16 07:13:13', '', 'lr_6877510913a4a.png', 1, NULL),
(157, 16, 'sick', '2025-07-02', '2025-07-02', 'Sick Leave', 'approved', '2025-07-16 07:14:56', '', 'lr_687751708af68.png', 1, NULL),
(158, 59, 'sick', '2025-07-15', '2025-07-15', 'SICK LEAVE', 'approved', '2025-07-16 07:24:45', '', 'lr_687753bd13f37.png', 1, NULL),
(159, 16, 'lwop', '2025-07-15', '2025-07-15', 'Sick Leave', 'approved', '2025-07-16 23:21:41', '', 'lr_6878340596c6c.png', 1, NULL),
(160, 43, 'sick', '2025-07-16', '2025-07-16', 'Not feeling well.', 'approved', '2025-07-18 00:12:49', '', 'lr_6879918101327.png', 1, NULL),
(161, 1004, 'lwop', '2025-07-18', '2025-07-18', 'Test', 'approved', '2025-07-18 01:23:04', '', 'lr_6879a1f809c74.png', 1, NULL);

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
  `explanation` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `overtime_requests`
--

INSERT INTO `overtime_requests` (`id`, `employee_id`, `date`, `start_time`, `end_time`, `duration_hours`, `reason`, `status`, `attachment_ot`, `created_at`, `time_in`, `time_out`, `notified`, `explanation`) VALUES
(1, 58, '2025-07-09', '20:04:53', '20:05:20', 0, 'Additional Task outside scope of work', '', NULL, '2025-07-09 12:05:58', '06:48:54', '20:03:26', 0, 'Hi Don, please attached your client\'s approval. Thank you!'),
(2, 1009, '2025-07-15', '16:02:07', '16:02:12', 0, 'test', 'Pending', NULL, '2025-07-15 08:02:21', '07:23:44', '16:01:59', 0, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `reactions`
--

CREATE TABLE `reactions` (
  `id` int(11) NOT NULL,
  `announcement_id` int(11) NOT NULL,
  `employee_id` int(11) NOT NULL,
  `emoji` varchar(10) NOT NULL,
  `reacted_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `reactions`
--

INSERT INTO `reactions` (`id`, `announcement_id`, `employee_id`, `emoji`, `reacted_at`) VALUES
(46, 40, 43, '👍', '2025-07-09 08:10:01'),
(48, 42, 28, '👍', '2025-07-11 07:59:01'),
(50, 42, 84, '👍', '2025-07-14 23:11:05'),
(52, 42, 1006, '❤️', '2025-07-14 23:36:14'),
(53, 40, 1006, '❤️', '2025-07-14 23:36:18'),
(54, 39, 1006, '❤️', '2025-07-14 23:36:21'),
(55, 37, 1006, '❤️', '2025-07-14 23:36:23'),
(56, 43, 1004, '❤️', '2025-07-15 00:18:28'),
(57, 58, 28, '👍', '2025-07-15 03:01:35'),
(58, 58, 6, '👍', '2025-07-15 03:03:44'),
(59, 43, 6, '👍', '2025-07-15 03:03:56'),
(60, 43, 12, '❤️', '2025-07-15 03:50:25'),
(63, 67, 1009, '❤️', '2025-07-16 02:02:30'),
(67, 58, 1009, '❤️', '2025-07-16 07:35:34'),
(68, 43, 1009, '👍', '2025-07-16 07:35:37'),
(69, 37, 1009, '❤️', '2025-07-16 07:34:55'),
(70, 74, 1009, '❤️', '2025-07-16 07:36:08'),
(71, 42, 1009, '❤️', '2025-07-16 07:35:46'),
(72, 40, 1009, '❤️', '2025-07-16 07:35:50'),
(73, 39, 1009, '❤️', '2025-07-16 07:35:54'),
(74, 74, 84, '❤️', '2025-07-17 01:29:27'),
(75, 75, 81, '👍', '2025-07-18 02:32:20'),
(76, 75, 64, '❤️', '2025-07-18 02:33:02'),
(77, 75, 84, '❤️', '2025-07-18 04:45:37'),
(78, 75, 59, '👍', '2025-07-18 05:14:32');

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
  `attachment_scr` varchar(255) DEFAULT NULL,
  `notified` tinyint(1) DEFAULT 0,
  `explanation` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `schedule_change_requests`
--

INSERT INTO `schedule_change_requests` (`id`, `employee_id`, `requested_schedule_id`, `reason`, `work_schedule`, `status`, `created_at`, `start_date`, `end_date`, `confirmed_rcs`, `declined_rcs`, `work_schedule_id`, `attachment_scr`, `notified`, `explanation`) VALUES
(81, 1009, NULL, 'test', NULL, 'Declined', '2025-07-02 00:18:18', '2025-07-02', '2025-08-01', NULL, NULL, 6, NULL, 1, 'Test'),
(82, 63, NULL, 'Change schedule already approved by Client. Reason : I have an important errand that I need to attend after work.', NULL, 'Approved', '2025-07-02 05:14:21', '2025-07-04', '2025-07-04', NULL, NULL, 8, 'scr_6864c02d48e07.jpg', 1, NULL),
(83, 1009, NULL, 'test123', NULL, 'Approved', '2025-07-02 06:55:20', '2025-07-02', '2025-07-19', NULL, NULL, 5, 'scr_6864d7d8624c3.png', 1, NULL),
(84, 68, NULL, 'This had already been approved since this date :)', NULL, 'Approved', '2025-07-02 21:59:33', '2025-04-14', '2025-04-14', NULL, NULL, 8, NULL, 1, NULL),
(85, 65, NULL, 'My regular working hours as per contract and client requirements', NULL, 'Approved', '2025-07-03 07:24:01', '1970-01-01', '1970-01-01', NULL, NULL, 5, NULL, 1, NULL),
(86, 1004, NULL, 'Test', NULL, 'Declined', '2025-07-07 00:40:42', '2025-07-01', '2025-07-31', NULL, NULL, 8, NULL, 1, 'Test'),
(87, 80, NULL, 'Original Schedule', NULL, 'Approved', '2025-07-07 01:19:30', '2025-07-07', '2025-07-31', NULL, NULL, 8, NULL, 1, NULL),
(88, 81, NULL, 'Original schedule', NULL, 'Approved', '2025-07-07 02:21:37', '2025-07-07', '2025-07-31', NULL, NULL, 8, NULL, 1, NULL),
(89, 8, NULL, 'This has been approved by Jenny. See attachment', NULL, 'Approved', '2025-07-08 00:49:18', '2025-07-14', '2025-07-14', NULL, NULL, 6, 'scr_686c6b0edfb1a.png', 1, NULL),
(90, 8, NULL, 'This has been approved by Jenny. See attachment', NULL, 'Approved', '2025-07-08 00:49:41', '2025-07-07', '2025-07-07', NULL, NULL, 6, 'scr_686c6b2562cf6.png', 1, NULL),
(91, 12, NULL, '7:30 am - 4:30 am starting today July 9, 2025 until further notice. Please see the attached file approved by Hary.', NULL, 'Approved', '2025-07-09 01:59:09', '2025-07-09', '2025-07-31', NULL, NULL, 4, 'scr_686dcced05a29.png', 1, NULL),
(92, 67, NULL, 'As per Manager\'s request', NULL, 'Approved', '2025-07-09 08:09:08', '2025-07-10', '2025-12-31', NULL, NULL, 6, NULL, 1, NULL),
(93, 1009, NULL, 'default', NULL, 'Approved', '2025-07-10 00:09:23', '2025-07-10', '2025-07-31', NULL, NULL, 4, NULL, 1, NULL),
(94, 76, NULL, 'Please change my schedule from 8:00 AM to 4:30 PM Monday to Friday per client schedule agreement', NULL, 'Approved', '2025-07-10 02:07:38', '2025-07-01', '2025-07-31', NULL, NULL, 5, NULL, 1, NULL),
(95, 75, NULL, 'Our schedule is 8am to 4:30pm as per client\'s approval. Dashboard schedule is showing 7am-4pm, kindly refer to the screenshot attached in this request. Also, please change the \"Schedule today\" on the RSS Dashboard to 8am-4:30pm. Thank you.', NULL, 'Approved', '2025-07-10 02:11:38', '2025-07-01', '2025-07-31', NULL, NULL, 5, 'scr_686f215a7a842.png', 1, NULL),
(96, 74, NULL, '8:00 AM - 4:30 PM Scheduled approved by client wherein lunch is only 30 mins', NULL, 'Approved', '2025-07-10 02:15:41', '2025-07-01', '2025-07-31', NULL, NULL, 5, NULL, 1, NULL),
(97, 67, NULL, 'Approved just for today - urgent work needs to be done', NULL, 'Declined', '2025-07-11 00:39:24', '2025-07-10', '2025-07-10', NULL, NULL, 4, NULL, 1, 'This is acknowledged. However, we will be asking you to file a time adjustment request once again since there is no attachment included previously.  Thank you!'),
(98, 43, NULL, 'Monday-Tuesday-Thursday (10am-7pm)\r\nWednesday (7am - 4pm)\r\nFriday (9am-6pm)', NULL, 'Declined', '2025-07-12 05:32:43', '2025-07-07', '2025-08-01', NULL, NULL, 7, NULL, 1, 'This is acknowledged. However, kindly file a request once again and include an attachment.'),
(99, 71, NULL, 'Client confirmed flexibility in logging hours (e.g., 8am to 5pm). Refer to attached screenshot. Thank you!', NULL, 'Approved', '2025-07-13 22:56:23', '2025-07-01', '2025-07-31', NULL, NULL, 5, NULL, 1, NULL),
(100, 1004, NULL, 'Test', NULL, 'Approved', '2025-07-14 00:25:18', '2025-07-01', '2025-07-31', NULL, NULL, 5, NULL, 1, NULL),
(101, 46, NULL, 'Will work at 7:00 am - 6:00 pm (10 hours) for the training', NULL, 'Declined', '2025-07-14 06:01:51', '2025-07-15', '2025-07-15', NULL, NULL, 4, NULL, 1, 'This is acknowledged. However, please file this one again to include an attachment.'),
(102, 57, NULL, '7am-6pm (10hours) - QA Metrics qualified for additional Work From Home (WFH) for the week of July 14 to July 20.', NULL, 'Declined', '2025-07-14 09:21:03', '2025-07-17', '2025-07-17', NULL, NULL, 4, NULL, 1, 'This is acknowledged. However, we will be asking you to file a time adjustment request once again since there is no attachment included previously.  Thank you!'),
(103, 1, NULL, 'Will work from 7am to 6pm (10 Hours).\r\nDue to an important appointment/commitment.', NULL, 'Declined', '2025-07-14 23:09:56', '2025-07-15', '2025-07-15', NULL, NULL, 4, NULL, 1, 'This is acknowledged. However, we will be asking you to file a time adjustment request once again since there is no attachment included previously.  Thank you!'),
(104, 49, NULL, 'I’m eligible for an additional work-from-home day, so I’ll be working from home tomorrow 16/07/2025 thanks.', NULL, 'Declined', '2025-07-15 00:28:19', '2025-07-16', '2025-07-16', NULL, NULL, 4, NULL, 1, 'This is acknowledged. However, we will be asking you to file a time adjustment request once again since there is no attachment included previously.  Thank you!'),
(105, 75, NULL, 'As per client approval, schedule is from 8am to 4:30pm. Thank you. :)', NULL, 'Approved', '2025-07-15 08:01:17', '2025-07-01', '2025-07-31', NULL, NULL, 9, NULL, 1, NULL),
(106, 74, NULL, 'Default sched', NULL, 'Approved', '2025-07-15 08:02:20', '2025-07-01', '2025-07-31', NULL, NULL, 9, NULL, 1, NULL),
(107, 77, NULL, '8-4:30', NULL, 'Approved', '2025-07-15 08:04:26', '2025-07-01', '2025-07-31', NULL, NULL, 9, NULL, 1, NULL),
(108, 76, NULL, 'Per client schedule agreement', NULL, 'Approved', '2025-07-15 08:05:51', '2025-07-01', '2025-07-31', NULL, NULL, 9, NULL, 1, NULL),
(109, 21, NULL, 'Personal Matter', NULL, 'Declined', '2025-07-15 22:59:07', '2025-07-18', '2025-07-18', NULL, NULL, 8, NULL, 1, 'Please provide an attachment.'),
(110, 1004, NULL, 'Original schedule.', NULL, 'Approved', '2025-07-15 23:00:54', '2025-07-01', '2025-07-31', NULL, NULL, 4, NULL, 1, NULL),
(111, 73, NULL, 'default sched', NULL, 'Approved', '2025-07-16 03:20:48', '2025-07-01', '2025-07-31', NULL, NULL, 9, NULL, 1, NULL),
(112, 52, NULL, 'This is my assigned work schedule.', NULL, 'Declined', '2025-07-16 05:05:22', '2025-07-17', '2026-12-31', NULL, NULL, 6, NULL, 1, 'No  attachment provided.'),
(113, 1009, NULL, 'test', NULL, 'Pending', '2025-07-16 05:58:29', '2025-07-16', '2025-07-26', NULL, NULL, 9, NULL, 0, NULL),
(114, 50, NULL, 'This was the agreed schedule given by my client when i started', NULL, 'Declined', '2025-07-16 06:00:35', '2025-07-16', '2026-07-16', NULL, NULL, 9, NULL, 1, 'No provided attachment.'),
(115, 71, NULL, 'my client allows me to work flexible hours (e.g., anytime between 7:00 AM to 4:00 PM/8:00 AM to 5:00 PM)', NULL, 'Declined', '2025-07-17 00:14:15', '2025-07-08', '2025-07-08', NULL, NULL, 4, NULL, 1, 'No attachment provided.'),
(116, 65, NULL, 'My regular shift starts from 8AM to 5PM and Rest day every Tuesday and Wednesday as per agreed Work Schedule', NULL, 'Approved', '2025-07-17 00:26:15', '2025-07-01', '2026-07-01', NULL, NULL, 5, NULL, 1, NULL),
(117, 71, NULL, 'My client allows me to work flexible hours (e.g., anytime between 7:00AM to 4:00 PM/8:00 AM to 5:00 PM), which is why my login and logout times may vary. Please find the attached snippet for your reference.', NULL, 'Pending', '2025-07-17 02:59:27', '2025-07-08', '2025-07-08', NULL, NULL, 4, NULL, 0, NULL),
(118, 31, NULL, 'Eligible for one (1) additional Work From Home.\r\n\r\nWorking Hours: 6:00 AM to 5:00PM', NULL, 'Pending', '2025-07-17 06:24:41', '2025-07-18', '2025-07-18', NULL, NULL, 4, NULL, 0, NULL),
(119, 29, NULL, 'Eligible for WFH tomorrow. (July 18, 2025, from 6:00am to 5:00pm)', NULL, 'Pending', '2025-07-17 07:08:21', '2025-07-18', '2025-07-18', NULL, NULL, 4, NULL, 0, NULL),
(120, 55, NULL, 'This my actual working time as per my Contract', NULL, 'Approved', '2025-07-17 07:24:52', '2025-07-16', '2028-11-17', NULL, NULL, 3, NULL, 1, NULL),
(121, 55, NULL, 'As per my contract', NULL, 'Approved', '2025-07-17 07:40:09', '2025-07-16', '2025-07-16', NULL, NULL, 3, NULL, 1, NULL),
(122, 55, NULL, 'my default sched upon contract', NULL, 'Approved', '2025-07-17 07:44:48', '2025-07-17', '2025-11-11', NULL, NULL, 3, NULL, 1, NULL),
(123, 1009, NULL, 'test', NULL, 'Pending', '2025-07-17 07:49:08', '2025-07-18', '2025-07-18', NULL, NULL, 3, NULL, 0, NULL),
(124, 55, NULL, 'this is my normal hours', NULL, 'Approved', '2025-07-17 07:49:52', '2025-07-17', '2025-11-11', '2025-07-17 00:00:00', NULL, 10, NULL, 1, NULL),
(125, 57, NULL, '7AM-6PM (10 hours) - QA metrics qualified', NULL, 'Pending', '2025-07-17 09:27:19', '2025-07-17', '2025-07-17', NULL, NULL, 4, 'scr_6878c1f75a74b.png', 0, NULL),
(126, 86, NULL, 'Client wants to start early as 6am as he don`t like to stay late at office. He told me that he will email RJ about this matter.', NULL, 'Pending', '2025-07-17 22:03:39', '2025-07-18', '2026-07-18', NULL, NULL, 8, 'scr_6879733bb01f3.png', 0, NULL),
(127, 49, NULL, 'Eligible for one (1) additional Work From Home (WFH) day.\r\ntime in 6am to 5pm', NULL, 'Pending', '2025-07-17 23:01:21', '2025-07-16', '2025-07-16', NULL, NULL, 4, 'scr_687980c17fe17.png', 0, NULL),
(128, 46, NULL, 'will work from 7:00 am to 6:00 pm at the office for the training', NULL, 'Pending', '2025-07-17 23:12:58', '2025-07-15', '2025-07-15', NULL, NULL, 4, 'scr_6879837a0f003.png', 0, NULL),
(129, 1, NULL, 'Due to an important appointment/commitment.\r\n\r\nTo take 19 July 2025 as a Day-off and work on 15 July 2025 in exchange. Time: 7:00am to 6:00pm', NULL, 'Pending', '2025-07-17 23:14:10', '2025-07-15', '2025-07-18', NULL, NULL, 4, 'scr_687983c226023.png', 0, NULL),
(130, 21, NULL, 'Change Shift from Saturday(WFH) 07/19/2025 to 07/18/2025 (OFF)\r\nFamily Matter', NULL, 'Pending', '2025-07-18 00:20:30', '2025-07-18', '2025-07-19', NULL, NULL, 8, 'scr_6879934eeb4b0.png', 0, NULL),
(131, 1004, NULL, 'Test', NULL, 'Pending', '2025-07-18 01:20:15', '2025-07-21', '2025-07-31', NULL, NULL, 5, 'scr_6879a14fbf83d.png', 0, NULL),
(132, 52, NULL, 'My standard working hours are from 9:00 AM to 6:00 PM, whereas Harley’s schedule runs from 7:00 AM to 4:00 PM. As a result, my attendance status in Harley appears as \'late.\' This working arrangement was requested by Bugardi several months ago week after I joined the team.', NULL, 'Pending', '2025-07-18 01:49:17', '2025-07-21', '2026-12-31', NULL, NULL, 6, 'scr_6879a81d9623d.png', 0, NULL),
(133, 43, NULL, 'worked pre-shift 8:30 am-10 am (1.5 hrs)', NULL, 'Approved', '2025-07-18 06:19:56', '2025-07-08', '2025-07-08', NULL, NULL, 7, 'scr_6879e78c2e0c8.png', 0, NULL),
(134, 43, NULL, 'worked pre-shift 1 hr \r\n\r\n9-6 pm usual shift .', NULL, 'Approved', '2025-07-18 06:31:14', '2025-07-11', '2025-07-11', NULL, NULL, 5, 'scr_6879ea326c6d7.png', 0, NULL),
(135, 43, NULL, 'worked pre-shift 2 hrs \r\n\r\n10-7 pm usual shift', NULL, 'Approved', '2025-07-18 06:31:57', '2025-07-14', '2025-07-14', NULL, NULL, 5, 'scr_6879ea5dcd2a2.png', 0, NULL),
(136, 43, NULL, 'worked pre-shift 2 hrs \r\n\r\n10-7 pm usual shift', NULL, 'Approved', '2025-07-18 06:32:31', '2025-07-15', '2025-07-15', NULL, NULL, 5, 'scr_6879ea7f9802b.png', 0, NULL),
(137, 43, NULL, 'worked pre-shift 1.5 hrs (8:30 am -10am)\r\n\r\n10-7 pm usual shift', NULL, 'Approved', '2025-07-18 06:34:57', '2025-07-08', '2025-07-08', NULL, NULL, 5, 'scr_6879eb11abcf7.png', 0, NULL),
(138, 1009, NULL, '740 to 440', NULL, 'Approved', '2025-07-18 06:40:47', '2025-07-18', '2025-07-18', NULL, NULL, 10, 'scr_6879ec6ff17ae.jpg', 0, NULL);

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

--
-- Dumping data for table `test_sql`
--

INSERT INTO `test_sql` (`id`, `employee_id`, `content`, `created_date`, `created_at`) VALUES
(1, 1009, 'test', '2025-06-25', '2025-06-25 07:29:30');

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
  `notified` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `time_adjustment_requests`
--

INSERT INTO `time_adjustment_requests` (`id`, `employee_id`, `log_date`, `current_time_in`, `current_time_out`, `requested_time_in`, `requested_time_out`, `reason`, `status`, `submitted_at`, `attachment`, `created_at`, `notified`) VALUES
(14, 1009, '2025-07-09', '15:35:28', '15:35:33', '07:13:00', '16:08:00', 'test', 'Approved', '2025-07-09 07:48:28', NULL, '2025-07-09 07:48:28', 1),
(15, 1003, '2025-07-09', '09:03:05', NULL, '07:00:00', NULL, 'Forgot to Time in', 'Approved', '2025-07-09 08:01:54', 'uploads/attach_686e21f2691565.28229793.JPG', '2025-07-09 08:01:54', 1),
(16, 43, '2025-07-07', '09:06:28', NULL, '09:06:00', '19:00:00', 'Forgot to time out and worked pre-shift (MARKING) for 1 hr 30 mins.', 'Approved', '2025-07-09 08:06:05', NULL, '2025-07-09 08:06:05', 1),
(17, 1003, '2025-07-03', '07:34:00', '16:09:13', '07:00:00', NULL, 'Forgot to Time In', 'Approved', '2025-07-09 22:52:48', NULL, '2025-07-09 22:52:48', 1),
(18, 41, '2025-07-10', '07:20:59', NULL, '06:30:00', NULL, 'Forgot to log in', 'Approved', '2025-07-09 23:23:07', NULL, '2025-07-09 23:23:07', 1),
(19, 74, '2025-07-02', '08:00:33', '16:30:09', '08:00:00', '16:30:00', 'default sched', 'Approved', '2025-07-10 02:04:42', NULL, '2025-07-10 02:04:42', 0),
(20, 74, '2025-07-02', '08:00:33', '16:30:09', '08:00:00', '16:30:00', 'Default sched', 'Approved', '2025-07-10 02:06:18', NULL, '2025-07-10 02:06:18', 0),
(21, 74, '2025-07-03', '07:50:33', '16:30:12', '08:00:00', '16:30:00', 'Default Sched', 'Approved', '2025-07-10 02:06:46', NULL, '2025-07-10 02:06:46', 0),
(22, 74, '2025-07-04', '07:51:04', '16:30:10', '08:00:00', '16:30:00', 'Default sched', 'Approved', '2025-07-10 02:07:18', NULL, '2025-07-10 02:07:18', 0),
(23, 74, '2025-07-07', '07:51:25', '16:30:09', '08:00:00', '16:30:00', 'Default sched', 'Approved', '2025-07-10 02:08:02', NULL, '2025-07-10 02:08:02', 0),
(24, 74, '2025-07-08', '07:51:19', '16:30:15', '08:00:00', '16:30:00', 'Default sched', 'Approved', '2025-07-10 02:08:43', NULL, '2025-07-10 02:08:43', 1),
(25, 73, '2025-07-07', '07:53:06', '16:30:05', '08:08:00', '16:30:00', 'default pic', 'Approved', '2025-07-10 02:09:19', NULL, '2025-07-10 02:09:19', 0),
(26, 74, '2025-07-09', '07:57:51', '16:30:10', '08:00:00', '16:30:00', 'Default sched', 'Approved', '2025-07-10 02:09:20', NULL, '2025-07-10 02:09:20', 1),
(27, 74, '2025-07-10', '07:50:37', NULL, '08:00:00', '16:30:00', 'Default sched', 'Approved', '2025-07-10 02:09:52', NULL, '2025-07-10 02:09:52', 1),
(28, 73, '2025-07-07', '07:53:06', '16:30:05', '08:00:00', '16:30:00', 'default sched', 'Approved', '2025-07-10 02:10:13', NULL, '2025-07-10 02:10:13', 0),
(29, 73, '2025-07-08', '07:52:49', '16:30:18', '08:00:00', '16:30:00', 'default sched', 'Approved', '2025-07-10 02:11:17', NULL, '2025-07-10 02:11:17', 1),
(30, 73, '2025-07-09', '07:55:17', '16:30:04', '08:00:00', '16:30:00', 'default sched', 'Approved', '2025-07-10 02:12:01', NULL, '2025-07-10 02:12:01', 1),
(31, 73, '2025-07-10', '07:56:35', NULL, '08:00:00', '16:30:00', 'default sched', 'Approved', '2025-07-10 02:12:28', NULL, '2025-07-10 02:12:28', 1),
(32, 74, '2025-07-02', '08:00:33', '16:30:09', '08:00:00', '16:30:00', 'Default sched', 'Approved', '2025-07-10 02:12:48', NULL, '2025-07-10 02:12:48', 1),
(33, 74, '2025-07-03', '07:50:33', '16:30:12', '08:00:00', '16:30:00', 'Default sched', 'Approved', '2025-07-10 02:13:17', NULL, '2025-07-10 02:13:17', 1),
(34, 73, '2025-07-02', '07:57:13', '16:30:12', '08:00:00', '16:30:00', 'default sched', 'Approved', '2025-07-10 02:14:55', NULL, '2025-07-10 02:14:55', 1),
(35, 73, '2025-07-03', '07:58:34', '16:30:15', '08:00:00', '16:30:00', 'default sched', 'Approved', '2025-07-10 02:17:09', NULL, '2025-07-10 02:17:09', 1),
(36, 73, '2025-07-04', '07:52:56', '16:30:05', '08:00:00', '16:30:00', 'default sched', 'Approved', '2025-07-10 03:48:21', NULL, '2025-07-10 03:48:21', 1),
(37, 73, '2025-07-07', '07:53:06', '16:30:05', '08:00:00', '16:30:00', 'default sched', 'Approved', '2025-07-10 03:49:02', NULL, '2025-07-10 03:49:02', 1),
(38, 56, '2025-07-10', '13:55:29', NULL, '07:42:00', '18:00:00', 'Forgot to clock in.', 'Approved', '2025-07-10 06:05:01', 'uploads/attach_686f580dad6ce7.43180334.png', '2025-07-10 06:05:01', 1),
(39, 78, '2025-07-10', '15:18:08', NULL, '08:00:00', NULL, 'Forgot to time in - Already in office since 7:30AM', 'Approved', '2025-07-10 07:20:17', NULL, '2025-07-10 07:20:17', 1),
(42, 55, '2025-07-11', '08:00:29', NULL, '07:00:00', NULL, 'Forgot to log in', 'Approved', '2025-07-11 00:01:25', NULL, '2025-07-11 00:01:25', 1),
(47, 1009, '2025-07-11', '07:38:25', NULL, '07:00:00', '16:00:00', 'testing purposes', 'Pending', '2025-07-11 02:00:09', 'attach_68707029412d20.84126505.jpg', '2025-07-11 02:00:09', 0),
(48, 1004, '2025-07-14', '08:20:39', NULL, '07:00:00', NULL, 'Test', 'Approved', '2025-07-14 00:22:32', 'attach_68744dc86095a3.59644972.jpg', '2025-07-14 00:22:32', 1),
(49, 8, '2025-07-07', '08:06:26', '16:00:54', '09:00:00', '18:00:00', 'My shift every Monday is 9:00 AM to 6:00 PM. Already sent it to HR email\r\n\r\nSubject: Schedule Change Request - Cedrick Galgo\r\n04/14/2025', 'Approved', '2025-07-14 00:25:21', 'attach_68744e7174d504.07117223.png', '2025-07-14 00:25:21', 0),
(50, 8, '2025-07-07', '08:06:26', '16:00:54', '09:00:00', '18:00:00', 'My shift every Monday is 9:00 AM to 6:00 PM. Already sent it to HR email\r\n\r\nSubject: Schedule Change Request - Cedrick Galgo\r\n04/14/2025', 'Approved', '2025-07-14 00:27:23', 'attach_68744eeb5c35e1.72277080.png', '2025-07-14 00:27:23', 0),
(51, 8, '2025-07-14', '08:17:15', NULL, '09:00:00', '18:00:00', 'My shift every Monday is 9:00 AM to 6:00 PM. Already sent it to HR email\r\n\r\nSubject: Schedule Change Request - Cedrick Galgo\r\n04/14/2025', 'Approved', '2025-07-14 00:28:25', 'attach_68744f29115699.05298508.png', '2025-07-14 00:28:25', 0),
(52, 86, '2025-07-14', '09:44:20', NULL, '07:00:00', NULL, 'Introduction', 'Approved', '2025-07-14 01:49:42', 'attach_68746236a89d35.81523779.jpg', '2025-07-14 01:49:42', 0),
(53, 74, '2025-07-11', '16:28:47', '16:30:11', '08:00:00', '16:30:00', 'Missing login | Decline Reason: Please send attachment of your activity ( e.g conversation with client)', 'Declined', '2025-07-14 08:16:25', 'attach_6874bcd9851123.42532956.docx', '2025-07-14 08:16:25', 1),
(54, 82, '2025-07-14', '15:11:01', '16:35:51', '07:00:00', '16:36:00', 'Day One onboarding', 'Approved', '2025-07-14 08:38:52', 'attach_6874c21cc581e0.11282769.png', '2025-07-14 08:38:52', 0),
(55, 1004, '2025-07-14', '08:20:39', NULL, NULL, '16:00:00', 'Forgot to time out.', 'Approved', '2025-07-14 22:54:51', 'attach_68758abb5a3139.28990737.png', '2025-07-14 22:54:51', 1),
(56, 37, '2025-07-15', '09:00:36', NULL, '07:00:00', '18:00:00', 'Forgot to log in', 'Approved', '2025-07-15 01:03:40', 'attach_6875a8ec331d60.68048663.jpg', '2025-07-15 01:03:40', 1),
(57, 20, '2025-07-15', '14:33:21', NULL, '06:30:00', '16:00:00', 'unable to log in to the Harley portal. The page is not loading properly, and login attempts are unsuccessfu', 'Approved', '2025-07-15 06:37:12', 'attach_6875f7182c6107.68984425.png', '2025-07-15 06:37:12', 1),
(58, 74, '2025-07-11', '16:28:47', '16:30:11', '08:00:00', NULL, 'Forgot to login', 'Approved', '2025-07-15 07:55:06', 'attach_6876095a56a8c8.10591392.png', '2025-07-15 07:55:06', 1),
(59, 52, '2025-07-09', '08:42:03', NULL, NULL, '17:47:00', 'Missed to log out', 'Approved', '2025-07-15 10:00:00', 'attach_687626a0b99cd6.47644010.pdf', '2025-07-15 10:00:00', 1),
(60, 14, '2025-07-02', '07:31:19', '18:00:04', '06:55:00', NULL, 'Unable to log in because of system issue', 'Approved', '2025-07-15 22:58:47', 'attach_6876dd27ea6eb0.23884542.png', '2025-07-15 22:58:47', 1),
(61, 41, '2025-07-16', '08:02:14', NULL, '06:30:00', NULL, 'Late logging in', 'Pending', '2025-07-16 00:10:05', 'attach_6876edddaad6c6.39075886.docx', '2025-07-16 00:10:05', 0),
(62, 81, '2025-07-07', '10:20:39', '16:00:23', '07:00:00', '16:00:00', 'Onboarding', 'Approved', '2025-07-16 04:19:13', 'attach_68772841e95a23.63517961.jpg', '2025-07-16 04:19:13', 1),
(63, 80, '2025-07-07', '09:19:41', '16:00:08', '19:00:00', NULL, 'First Day, Onboarding', 'Approved', '2025-07-16 04:20:07', 'attach_6877287746a5c7.81731113.png', '2025-07-16 04:20:07', 1),
(64, 80, '2025-07-07', '09:19:41', '16:00:08', '07:00:00', NULL, 'First Day, Onboarding', 'Approved', '2025-07-16 04:21:10', 'attach_687728b6d1c562.95087074.png', '2025-07-16 04:21:10', 1),
(65, 80, '2025-07-08', '06:07:39', '15:00:40', '06:00:00', NULL, 'Unable to enter the production floor as my biometrics have not been enrolled yet.', 'Approved', '2025-07-16 04:22:31', 'attach_687729074633b3.16294908.png', '2025-07-16 04:22:31', 1),
(66, 81, '2025-07-08', '06:08:36', '15:01:03', '06:00:00', '15:00:00', 'Late logged in on Harley. Not yet registered on biometrics to enter prod.', 'Approved', '2025-07-16 04:23:05', 'attach_6877292949cd57.24557499.jpg', '2025-07-16 04:23:05', 1),
(67, 1009, '2025-07-16', '07:19:04', NULL, '07:13:00', '16:03:00', 'test', 'Pending', '2025-07-16 06:41:58', 'attach_687749b620af06.71729137.png', '2025-07-16 06:41:58', 0),
(68, 81, '2025-07-16', '05:50:40', NULL, '05:50:00', '15:00:00', 'I thought my log out went through but unfortunately it didn’t. Sorry about that, I didn’t realize until after I had signed out.', 'Pending', '2025-07-16 22:03:46', 'attach_687821c237b109.87382767.png', '2025-07-16 22:03:46', 0),
(69, 1004, '2025-07-14', '08:20:39', NULL, '07:00:00', NULL, 'Onboarding', 'Pending', '2025-07-16 22:55:57', 'attach_68782dfdd13060.88127484.png', '2025-07-16 22:55:57', 0),
(70, 74, '2025-07-16', '07:50:58', '07:51:05', NULL, '16:30:00', 'I accidentally got logged out yesterday and couldn\'t log back in', 'Pending', '2025-07-17 00:19:42', 'attach_6878419e3b7556.00114011.docx', '2025-07-17 00:19:42', 0),
(71, 15, '2025-07-06', '07:50:37', NULL, NULL, '19:00:00', 'Forgot to log out.', 'Pending', '2025-07-17 03:44:00', 'attach_68787180579934.40173117.PNG', '2025-07-17 03:44:00', 0),
(72, 15, '2025-07-07', '08:03:11', NULL, NULL, '19:00:00', 'Forgot to log out', 'Pending', '2025-07-17 03:44:26', 'attach_6878719ab43635.35035703.PNG', '2025-07-17 03:44:26', 0),
(73, 15, '2025-07-13', '07:54:44', NULL, NULL, '19:00:00', 'Forgot to log out', 'Pending', '2025-07-17 03:44:51', 'attach_687871b38c5c64.68824216.PNG', '2025-07-17 03:44:51', 0),
(74, 20, '2025-07-01', NULL, NULL, '06:30:00', '16:00:00', 'refer to old time log and biometrics', 'Pending', '2025-07-17 06:38:46', 'attach_68789a760bee63.25124354.jpg', '2025-07-17 06:38:46', 0),
(75, 20, '2025-07-02', NULL, NULL, '06:30:00', '16:00:00', 'Please refer to the attachment', 'Pending', '2025-07-17 06:39:24', 'attach_68789a9c4e08c9.54634970.jpg', '2025-07-17 06:39:24', 0),
(76, 20, '2025-07-03', NULL, NULL, '06:30:00', '16:00:00', 'please refer to the attachment', 'Pending', '2025-07-17 06:40:16', 'attach_68789ad0769509.82536217.jpg', '2025-07-17 06:40:16', 0),
(77, 63, '2025-07-07', '06:59:09', NULL, '06:59:00', '16:00:00', 'I forgot to log out in Harley.', 'Approved', '2025-07-17 06:41:42', 'attach_68789b26e9cb78.24907701.jpg', '2025-07-17 06:41:42', 1),
(78, 80, '2025-07-07', '09:19:41', '16:00:08', '07:00:00', '16:00:00', 'First Day, Onboarding', 'Approved', '2025-07-17 06:50:53', 'attach_68789d4dee0962.36565265.png', '2025-07-17 06:50:53', 1),
(79, 80, '2025-07-08', '06:07:39', '15:00:40', '06:00:00', '15:00:00', 'Unable to enter production floor due to no biometrics yet.', 'Approved', '2025-07-17 06:51:22', 'attach_68789d6a5fba97.46887684.png', '2025-07-17 06:51:22', 1),
(80, 79, '2025-07-01', NULL, NULL, '07:00:00', '16:00:00', 'NO access', 'Approved', '2025-07-17 07:24:09', 'attach_6878a519a1acc4.42930039.png', '2025-07-17 07:24:09', 0),
(81, 79, '2025-07-02', NULL, NULL, '07:00:00', '16:00:00', 'no harley access yet', 'Approved', '2025-07-17 07:24:40', 'attach_6878a538ef7686.89000551.png', '2025-07-17 07:24:40', 0),
(82, 79, '2025-07-03', NULL, NULL, '07:00:00', '16:00:00', 'no harley access yet', 'Approved', '2025-07-17 07:25:23', 'attach_6878a563b575c4.76274368.png', '2025-07-17 07:25:23', 1),
(83, 79, '2025-07-04', '08:24:32', NULL, '08:24:00', '18:25:00', 'ss', 'Approved', '2025-07-17 07:27:16', 'attach_6878a5d4937794.90054541.png', '2025-07-17 07:27:16', 1),
(84, 79, '2025-07-08', '08:03:51', NULL, '08:03:00', '18:06:00', 'ss', 'Approved', '2025-07-17 07:28:06', 'attach_6878a606865df5.55870160.png', '2025-07-17 07:28:06', 1),
(85, 79, '2025-07-09', '00:48:53', NULL, '00:48:00', '16:00:00', 'ss', 'Approved', '2025-07-17 07:28:34', 'attach_6878a622374277.21650633.png', '2025-07-17 07:28:34', 1),
(86, 79, '2025-07-10', '10:27:34', NULL, '10:27:00', '20:00:00', 'ss', 'Approved', '2025-07-17 07:28:55', 'attach_6878a63750b109.99641702.png', '2025-07-17 07:28:55', 1),
(87, 79, '2025-07-11', '08:05:04', NULL, '08:05:00', '18:06:00', 'ss', 'Pending', '2025-07-17 07:29:39', 'attach_6878a663e4ced0.97637788.png', '2025-07-17 07:29:39', 0),
(88, 79, '2025-07-14', '07:56:17', NULL, '07:56:00', '17:00:00', 'ss', 'Pending', '2025-07-17 07:30:25', 'attach_6878a69167a291.95288390.png', '2025-07-17 07:30:25', 0),
(89, 79, '2025-07-15', '14:53:14', NULL, '14:53:00', '22:00:00', 'ss', 'Pending', '2025-07-17 07:30:49', 'attach_6878a6a921baa3.33954063.png', '2025-07-17 07:30:49', 0),
(90, 79, '2025-07-16', '08:13:57', NULL, '08:13:00', '18:06:00', 'ss', 'Pending', '2025-07-17 07:31:15', 'attach_6878a6c366b4f4.18606252.png', '2025-07-17 07:31:15', 0),
(91, 47, '2025-07-17', '11:06:40', NULL, '07:00:00', '16:00:00', 'Forgot to Log-in and Log-out', 'Pending', '2025-07-17 23:35:38', 'attach_687988cab69d09.65524741.docx', '2025-07-17 23:35:38', 0),
(92, 64, '2025-07-18', NULL, NULL, '06:56:00', NULL, 'Harley issue cannot open earlier, snipped sent to IT and HR', 'Pending', '2025-07-18 01:08:49', 'attach_68799ea1810de9.02907784.jpg', '2025-07-18 01:08:49', 0),
(93, 64, '2025-07-18', NULL, NULL, '06:56:00', NULL, 'Harley issue', 'Pending', '2025-07-18 01:09:27', 'attach_68799ec7934656.22359128.jpg', '2025-07-18 01:09:27', 0),
(94, 1004, '2025-07-18', '06:55:23', NULL, '15:16:00', NULL, 'Test', 'Pending', '2025-07-18 01:17:37', 'attach_6879a0b1177042.65950041.png', '2025-07-18 01:17:37', 0),
(95, 64, '2025-07-16', NULL, NULL, '06:45:00', '16:02:00', 'Didn\'t know when you work on site I will need to login on Harley', 'Pending', '2025-07-18 01:28:31', 'attach_6879a33fc21aa4.15764300.pdf', '2025-07-18 01:28:31', 0),
(96, 64, '2025-07-17', NULL, NULL, '06:56:00', '16:04:00', 'Didn\'t know when you work on site I will need to login on Harley', 'Pending', '2025-07-18 01:29:24', 'attach_6879a374f1ae72.77380589.pdf', '2025-07-18 01:29:24', 0),
(97, 43, '2025-07-08', '08:25:57', '19:01:28', '08:30:00', '19:00:00', 'WORKED PRE-SHIFT TO ASSIST WITH MARKING. 8:30 AM-10 AM (1.5 HRS)', 'Pending', '2025-07-18 03:09:15', 'attach_6879badbcab8b4.08203939.png', '2025-07-18 03:09:15', 0),
(98, 43, '2025-07-11', '08:02:37', '18:01:27', '08:02:00', '18:01:00', 'WORKED PRE-SHIFT TO ASSIST WITH MARKING. 8AM-9AM (1 HR)', 'Pending', '2025-07-18 03:10:53', 'attach_6879bb3d4456a2.10194621.png', '2025-07-18 03:10:53', 0),
(99, 43, '2025-07-14', '08:00:01', '19:01:39', '08:00:00', '19:00:00', 'WORKED PRE-SHIFT TO ASSIST WITH THE MARKING . 8AM-10AM (2 HRS)', 'Pending', '2025-07-18 03:12:09', 'attach_6879bb89540d79.07427982.png', '2025-07-18 03:12:09', 0),
(100, 43, '2025-07-15', '07:51:11', '19:01:30', '07:58:00', '19:00:00', 'WORKED PRE-SHIFT TO ASSIST WITH THE MARKING . 8 AM-10 AM (2 HRS)', 'Pending', '2025-07-18 03:13:02', 'attach_6879bbbe703140.78034045.png', '2025-07-18 03:13:02', 0),
(101, 1009, '2025-07-18', '11:42:55', NULL, '11:00:00', NULL, 'test', 'Pending', '2025-07-18 05:58:50', 'attach_6879e29a84c2f9.61024942.jpg', '2025-07-18 05:58:50', 0),
(102, 1009, '2025-07-18', '11:42:55', NULL, '10:04:00', NULL, 'test', 'Pending', '2025-07-18 06:04:18', 'attach_6879e3e2336581.85714652.jpg', '2025-07-18 06:04:18', 0),
(103, 1009, '2025-07-18', '11:42:55', NULL, '10:11:00', '16:11:00', 'test', 'Pending', '2025-07-18 06:11:37', 'attach_6879e599400716.07467875.jpg', '2025-07-18 06:11:37', 0),
(104, 1009, '2025-07-18', '11:42:55', NULL, '10:13:00', '16:14:00', 'test', 'Pending', '2025-07-18 06:14:22', 'attach_6879e63ed2a114.61454633.jpg', '2025-07-18 06:14:22', 0);

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

--
-- Dumping data for table `time_logs`
--

INSERT INTO `time_logs` (`id`, `employee_id`, `log_date`, `time_in`, `time_out`, `is_late_in`, `is_early_out`) VALUES
(47, 7, '2025-07-01', '14:57:18', '18:00:15', 0, 0),
(48, 22, '2025-07-01', '15:00:32', '18:00:10', 0, 0),
(49, 4, '2025-07-01', '15:01:13', '18:00:13', 0, 0),
(50, 30, '2025-07-01', '15:01:43', '15:01:57', 0, 0),
(51, 37, '2025-07-01', '15:03:38', '18:01:18', 0, 0),
(52, 53, '2025-07-01', '15:14:28', '15:14:31', 0, 0),
(53, 5, '2025-07-01', '15:15:37', '19:00:25', 0, 0),
(54, 26, '2025-07-01', '15:26:06', '15:26:08', 0, 0),
(55, 28, '2025-07-01', '15:45:23', '15:45:34', 0, 0),
(56, 35, '2025-07-01', '16:18:06', '18:00:08', 0, 0),
(57, 13, '2025-07-01', '16:45:58', '19:00:09', 0, 0),
(58, 46, '2025-07-01', '16:47:31', '17:02:44', 0, 0),
(59, 24, '2025-07-01', '17:57:56', '19:00:05', 0, 0),
(60, 25, '2025-07-01', '17:58:13', '19:00:03', 0, 0),
(61, 67, '2025-07-02', '05:43:39', '16:00:48', 0, 0),
(62, 25, '2025-07-02', '06:00:16', '18:00:05', 0, 0),
(63, 5, '2025-07-02', '06:02:40', '17:08:52', 0, 0),
(64, 33, '2025-07-02', '06:07:16', '15:00:42', 0, 0),
(65, 6, '2025-07-02', '06:13:18', '15:12:06', 0, 0),
(66, 34, '2025-07-02', '06:20:26', '17:41:30', 0, 0),
(67, 64, '2025-07-02', '06:22:37', '16:06:39', 0, 0),
(68, 1004, '2025-07-02', '06:49:12', '16:26:28', 0, 0),
(69, 9, '2025-07-02', '06:49:29', '16:00:49', 0, 0),
(70, 1006, '2025-07-02', '06:50:01', '16:26:28', 0, 0),
(71, 55, '2025-07-02', '06:50:02', '16:30:11', 0, 0),
(72, 58, '2025-07-02', '06:50:45', '20:03:29', 0, 0),
(73, 71, '2025-07-02', '06:51:15', '18:19:33', 0, 0),
(74, 43, '2025-07-02', '06:51:25', '16:00:19', 0, 0),
(75, 60, '2025-07-02', '06:51:36', '18:00:42', 0, 0),
(76, 4, '2025-07-02', '06:52:54', '18:00:14', 0, 0),
(77, 63, '2025-07-02', '06:53:04', '16:01:43', 0, 0),
(78, 7, '2025-07-02', '06:54:13', '18:01:05', 0, 0),
(79, 37, '2025-07-02', '06:57:15', '18:00:04', 0, 0),
(80, 70, '2025-07-02', '06:57:55', '16:00:28', 0, 0),
(81, 17, '2025-07-02', '06:57:57', '16:26:02', 0, 0),
(82, 10, '2025-07-02', '06:58:39', '16:04:35', 0, 0),
(83, 59, '2025-07-02', '06:59:20', '18:01:22', 0, 0),
(84, 31, '2025-07-02', '06:59:37', '18:00:02', 0, 0),
(85, 3, '2025-07-02', '06:59:49', '17:38:56', 0, 0),
(86, 22, '2025-07-02', '07:00:05', '18:00:22', 0, 0),
(87, 30, '2025-07-02', '07:02:07', '18:00:03', 0, 0),
(88, 29, '2025-07-02', '07:02:36', '18:00:07', 0, 0),
(89, 1003, '2025-07-02', '07:08:26', '16:06:35', 0, 0),
(90, 1009, '2025-07-02', '07:24:32', '15:01:03', 0, 0),
(91, 56, '2025-07-02', '07:25:18', '18:00:11', 0, 0),
(92, 46, '2025-07-02', '07:26:02', '18:18:17', 0, 0),
(93, 14, '2025-07-02', '07:31:19', '18:00:04', 0, 0),
(94, 26, '2025-07-02', '07:41:49', '16:06:53', 0, 0),
(95, 78, '2025-07-02', '07:43:06', '17:00:24', 0, 0),
(96, 12, '2025-07-02', '07:51:42', '19:00:39', 0, 0),
(97, 53, '2025-07-02', '07:52:03', '19:01:34', 0, 0),
(98, 77, '2025-07-02', '07:53:06', '16:30:53', 0, 0),
(99, 24, '2025-07-02', '07:54:26', '19:00:10', 0, 0),
(100, 73, '2025-07-02', '07:57:13', '16:30:12', 0, 0),
(101, 15, '2025-07-02', '07:57:17', '19:01:10', 0, 0),
(102, 74, '2025-07-02', '08:00:33', '16:30:09', 0, 0),
(103, 62, '2025-07-02', '08:16:09', '18:23:55', 0, 0),
(104, 28, '2025-07-02', '09:01:59', '18:00:19', 0, 0),
(105, 65, '2025-07-02', '09:04:21', '16:04:32', 0, 0),
(106, 52, '2025-07-02', '09:05:41', '18:17:49', 0, 0),
(107, 68, '2025-07-03', '05:55:26', '15:04:10', 0, 0),
(108, 67, '2025-07-03', '05:58:05', '16:00:17', 0, 0),
(109, 6, '2025-07-03', '06:10:01', '15:22:35', 0, 0),
(110, 25, '2025-07-03', '06:11:19', '19:00:13', 0, 0),
(111, 33, '2025-07-03', '06:14:42', '15:05:42', 0, 0),
(112, 3, '2025-07-03', '06:30:17', '17:31:50', 0, 0),
(113, 61, '2025-07-03', '06:35:40', '16:10:02', 0, 0),
(114, 59, '2025-07-03', '06:41:58', '18:01:57', 0, 0),
(115, 58, '2025-07-03', '06:49:21', '20:08:44', 0, 0),
(116, 60, '2025-07-03', '06:51:03', '18:06:07', 0, 0),
(117, 4, '2025-07-03', '06:52:09', '18:00:11', 0, 0),
(118, 34, '2025-07-03', '06:52:21', '18:00:07', 0, 0),
(119, 9, '2025-07-03', '06:52:30', '16:00:40', 0, 0),
(120, 17, '2025-07-03', '06:53:23', '16:13:03', 0, 0),
(121, 70, '2025-07-03', '06:55:25', '16:01:42', 0, 0),
(122, 63, '2025-07-03', '06:56:19', '16:02:50', 0, 0),
(123, 64, '2025-07-03', '06:56:31', '16:03:46', 0, 0),
(124, 1006, '2025-07-03', '06:57:36', '16:02:12', 0, 0),
(125, 14, '2025-07-03', '06:58:54', '18:00:20', 0, 0),
(126, 10, '2025-07-03', '06:59:31', '16:01:09', 0, 0),
(127, 30, '2025-07-03', '07:00:15', '18:00:10', 0, 0),
(128, 72, '2025-07-03', '07:00:23', '16:01:54', 0, 0),
(129, 1004, '2025-07-03', '07:00:36', '16:09:35', 0, 0),
(130, 57, '2025-07-03', '07:01:01', '18:00:20', 0, 0),
(131, 46, '2025-07-03', '07:01:08', '18:05:12', 0, 0),
(132, 31, '2025-07-03', '07:01:43', '18:00:09', 0, 0),
(133, 29, '2025-07-03', '07:02:19', '18:00:08', 0, 0),
(134, 5, '2025-07-03', '07:02:50', '18:01:06', 0, 0),
(135, 22, '2025-07-03', '07:11:54', '18:00:33', 0, 0),
(136, 55, '2025-07-03', '07:18:55', '16:30:04', 0, 0),
(137, 11, '2025-07-03', '07:27:52', '18:01:56', 0, 0),
(138, 43, '2025-07-03', '07:28:26', '19:00:09', 0, 0),
(139, 26, '2025-07-03', '07:29:30', '16:03:46', 0, 0),
(140, 56, '2025-07-03', '07:30:43', '18:02:21', 0, 0),
(141, 1003, '2025-07-03', '07:34:00', '16:09:13', 0, 0),
(142, 13, '2025-07-03', '07:45:41', '19:04:50', 0, 0),
(143, 78, '2025-07-03', '07:47:11', '17:10:33', 0, 0),
(144, 71, '2025-07-03', '07:47:18', '17:38:55', 0, 0),
(145, 77, '2025-07-03', '07:48:01', '16:30:26', 0, 0),
(146, 24, '2025-07-03', '07:48:32', '19:00:19', 0, 0),
(147, 74, '2025-07-03', '07:50:33', '16:30:12', 0, 0),
(148, 65, '2025-07-03', '07:55:57', '19:06:25', 0, 0),
(149, 73, '2025-07-03', '07:58:34', '16:30:15', 0, 0),
(150, 12, '2025-07-03', '08:09:29', '19:03:15', 0, 0),
(151, 62, '2025-07-03', '08:21:02', '18:16:33', 0, 0),
(152, 52, '2025-07-03', '08:37:09', '21:54:15', 0, 0),
(153, 28, '2025-07-03', '08:39:15', '18:00:04', 0, 0),
(154, 37, '2025-07-03', '08:46:05', '20:00:38', 0, 0),
(155, 7, '2025-07-03', '09:12:02', '20:17:22', 0, 0),
(156, 53, '2025-07-03', '14:57:00', '18:00:07', 0, 0),
(157, 38, '2025-07-03', '20:02:42', '20:02:45', 0, 0),
(158, 36, '2025-07-04', '05:41:57', '12:46:46', 0, 0),
(159, 63, '2025-07-04', '05:46:49', '15:04:08', 0, 0),
(160, 7, '2025-07-04', '05:56:09', '17:03:20', 0, 0),
(161, 49, '2025-07-04', '06:00:06', '17:01:11', 0, 0),
(162, 68, '2025-07-04', '06:00:58', '15:01:06', 0, 0),
(163, 33, '2025-07-04', '06:08:30', '15:01:15', 0, 0),
(164, 21, '2025-07-04', '06:09:57', '17:10:58', 0, 0),
(165, 59, '2025-07-04', '06:11:20', '17:07:08', 0, 0),
(166, 20, '2025-07-04', '06:16:09', '16:03:05', 0, 0),
(167, 41, '2025-07-04', '06:16:48', '16:02:19', 0, 0),
(168, 67, '2025-07-04', '06:17:56', '16:22:13', 0, 0),
(169, 6, '2025-07-04', '06:24:26', '15:07:47', 0, 0),
(170, 61, '2025-07-04', '06:34:28', '18:22:40', 0, 0),
(171, 64, '2025-07-04', '06:43:21', '16:02:45', 0, 0),
(172, 58, '2025-07-04', '06:46:10', '20:07:19', 0, 0),
(173, 1007, '2025-07-04', '06:52:38', '16:03:04', 0, 0),
(174, 60, '2025-07-04', '06:53:39', '18:01:03', 0, 0),
(175, 1003, '2025-07-04', '06:54:01', '16:04:01', 0, 0),
(176, 10, '2025-07-04', '06:54:42', '16:01:54', 0, 0),
(177, 57, '2025-07-04', '06:54:43', '18:00:04', 0, 0),
(178, 27, '2025-07-04', '06:55:30', '16:12:05', 0, 0),
(179, 4, '2025-07-04', '06:55:48', '18:00:21', 0, 0),
(180, 1004, '2025-07-04', '06:56:38', '16:00:26', 0, 0),
(181, 34, '2025-07-04', '06:56:44', '18:00:06', 0, 0),
(182, 1, '2025-07-04', '06:57:52', '18:00:34', 0, 0),
(183, 9, '2025-07-04', '06:57:58', '16:01:03', 0, 0),
(184, 70, '2025-07-04', '06:58:36', '16:00:19', 0, 0),
(185, 30, '2025-07-04', '06:59:33', '18:00:07', 0, 0),
(186, 28, '2025-07-04', '06:59:51', '16:01:04', 0, 0),
(187, 31, '2025-07-04', '06:59:58', '18:00:11', 0, 0),
(188, 46, '2025-07-04', '07:00:58', '18:00:17', 0, 0),
(189, 16, '2025-07-04', '07:01:33', '16:00:44', 0, 0),
(190, 14, '2025-07-04', '07:02:17', '18:00:39', 0, 0),
(191, 29, '2025-07-04', '07:02:33', '18:00:06', 0, 0),
(192, 17, '2025-07-04', '07:03:02', '17:06:08', 0, 0),
(193, 72, '2025-07-04', '07:03:54', '16:03:48', 0, 0),
(194, 55, '2025-07-04', '07:14:11', '16:30:04', 0, 0),
(195, 71, '2025-07-04', '07:22:46', '19:19:32', 0, 0),
(196, 11, '2025-07-04', '07:23:33', '18:00:40', 0, 0),
(197, 78, '2025-07-04', '07:28:20', '17:00:17', 0, 0),
(198, 1006, '2025-07-04', '07:00:45', '16:02:12', 0, 0),
(199, 26, '2025-07-04', '07:34:14', '16:19:03', 0, 0),
(200, 77, '2025-07-04', '07:43:17', '16:30:25', 0, 0),
(201, 38, '2025-07-04', '07:49:46', '11:50:20', 0, 0),
(202, 74, '2025-07-04', '07:51:04', '16:30:10', 0, 0),
(203, 76, '2025-07-04', '07:51:16', '16:30:28', 0, 0),
(204, 73, '2025-07-04', '07:52:56', '16:30:05', 0, 0),
(205, 65, '2025-07-04', '07:53:08', '20:03:45', 0, 0),
(206, 18, '2025-07-04', '07:55:47', '17:10:32', 0, 0),
(207, 75, '2025-07-04', '07:55:55', '16:30:12', 0, 0),
(208, 56, '2025-07-04', '08:00:04', '19:06:07', 0, 0),
(209, 24, '2025-07-04', '08:05:03', '13:05:13', 0, 0),
(210, 52, '2025-07-04', '08:19:33', '18:03:15', 0, 0),
(211, 37, '2025-07-04', '08:20:02', '19:23:03', 0, 0),
(212, 62, '2025-07-04', '08:20:57', '18:17:52', 0, 0),
(213, 79, '2025-07-04', '08:24:32', NULL, 0, 0),
(214, 69, '2025-07-04', '10:14:03', '16:00:06', 0, 0),
(215, 29, '2025-07-05', '05:50:05', '17:06:57', 0, 0),
(216, 21, '2025-07-05', '05:51:35', '17:03:55', 0, 0),
(217, 30, '2025-07-05', '05:51:58', '17:01:02', 0, 0),
(218, 31, '2025-07-05', '05:52:23', '17:01:28', 0, 0),
(219, 49, '2025-07-05', '05:55:33', '17:01:29', 0, 0),
(220, 5, '2025-07-05', '06:03:40', '17:19:18', 0, 0),
(221, 67, '2025-07-05', '06:18:08', '16:00:52', 0, 0),
(222, 34, '2025-07-05', '06:49:52', '18:00:06', 0, 0),
(223, 4, '2025-07-05', '06:51:03', '18:00:11', 0, 0),
(224, 58, '2025-07-05', '06:51:09', '16:02:41', 0, 0),
(225, 10, '2025-07-05', '06:51:25', '18:00:32', 0, 0),
(226, 14, '2025-07-05', '06:51:53', '18:00:13', 0, 0),
(227, 60, '2025-07-05', '06:53:00', '18:00:32', 0, 0),
(228, 25, '2025-07-05', '06:53:40', '18:00:05', 0, 0),
(229, 1, '2025-07-05', '06:53:50', '18:01:02', 0, 0),
(230, 57, '2025-07-05', '07:14:08', '18:00:05', 0, 0),
(231, 11, '2025-07-05', '07:15:04', NULL, 0, 0),
(232, 62, '2025-07-05', '07:42:16', '19:41:34', 0, 0),
(233, 65, '2025-07-05', '08:01:49', '20:03:19', 0, 0),
(234, 13, '2025-07-05', '08:26:44', '14:44:08', 0, 0),
(235, 38, '2025-07-05', '14:52:26', '19:05:45', 0, 0),
(236, 34, '2025-07-06', '05:49:52', '17:00:32', 0, 0),
(237, 58, '2025-07-06', '06:38:30', '18:40:18', 0, 0),
(238, 7, '2025-07-06', '06:41:54', '18:01:32', 0, 0),
(239, 25, '2025-07-06', '06:44:57', '18:00:03', 0, 0),
(240, 57, '2025-07-06', '06:45:43', '18:00:04', 0, 0),
(241, 37, '2025-07-06', '06:48:27', '18:14:37', 0, 0),
(242, 30, '2025-07-06', '07:19:29', '17:22:33', 0, 0),
(243, 11, '2025-07-06', '07:23:14', '18:00:20', 0, 0),
(244, 13, '2025-07-06', '07:33:03', '11:34:11', 0, 0),
(245, 15, '2025-07-06', '07:50:37', NULL, 0, 0),
(246, 65, '2025-07-06', '08:02:10', '17:13:15', 0, 0),
(247, 36, '2025-07-06', '08:48:20', '14:21:55', 0, 0),
(248, 62, '2025-07-06', '08:58:07', '17:04:22', 0, 0),
(249, 68, '2025-07-07', '05:56:46', '15:01:35', 0, 0),
(250, 36, '2025-07-07', '05:56:59', NULL, 0, 0),
(251, 6, '2025-07-07', '05:59:01', '15:03:16', 0, 0),
(252, 20, '2025-07-07', '06:07:54', '16:03:00', 0, 0),
(253, 41, '2025-07-07', '06:14:57', '16:01:08', 0, 0),
(254, 33, '2025-07-07', '06:19:56', '15:01:26', 0, 0),
(255, 34, '2025-07-07', '06:23:21', '17:36:31', 0, 0),
(256, 3, '2025-07-07', '06:31:49', '17:31:56', 0, 0),
(257, 69, '2025-07-07', '06:43:32', '16:00:07', 0, 0),
(258, 64, '2025-07-07', '06:43:50', '16:05:59', 0, 0),
(259, 9, '2025-07-07', '06:49:58', '16:00:15', 0, 0),
(260, 47, '2025-07-07', '06:50:57', '16:00:50', 0, 0),
(261, 1003, '2025-07-07', '06:52:04', '16:33:55', 0, 0),
(262, 21, '2025-07-07', '06:52:45', '18:00:45', 0, 0),
(263, 1, '2025-07-07', '06:53:04', '18:01:08', 0, 0),
(264, 17, '2025-07-07', '06:54:24', '16:10:00', 0, 0),
(265, 58, '2025-07-07', '06:55:01', '21:18:54', 0, 0),
(266, 37, '2025-07-07', '06:55:01', '18:00:32', 0, 0),
(267, 49, '2025-07-07', '06:55:07', '18:00:07', 0, 0),
(268, 60, '2025-07-07', '06:55:27', NULL, 0, 0),
(269, 53, '2025-07-07', '06:55:58', '18:00:15', 0, 0),
(270, 61, '2025-07-07', '06:56:03', '16:00:22', 0, 0),
(271, 7, '2025-07-07', '06:56:23', '18:00:04', 0, 0),
(272, 1006, '2025-07-07', '06:57:20', '16:02:12', 0, 0),
(273, 63, '2025-07-07', '06:59:09', NULL, 0, 0),
(274, 56, '2025-07-07', '06:59:50', '18:01:11', 0, 0),
(275, 66, '2025-07-07', '06:59:56', '16:04:25', 0, 0),
(276, 55, '2025-07-07', '06:59:58', '16:30:06', 0, 0),
(277, 27, '2025-07-07', '07:00:47', '16:03:20', 0, 0),
(278, 22, '2025-07-07', '07:01:24', NULL, 0, 0),
(279, 1007, '2025-07-07', '07:02:24', '16:03:37', 0, 0),
(280, 70, '2025-07-07', '07:03:01', '16:01:33', 0, 0),
(281, 72, '2025-07-07', '07:08:24', '16:00:57', 0, 0),
(282, 29, '2025-07-07', '07:20:04', '16:23:27', 0, 0),
(283, 26, '2025-07-07', '07:20:14', '16:09:22', 0, 0),
(284, 71, '2025-07-07', '07:20:32', '18:07:48', 0, 0),
(285, 1004, '2025-07-07', '07:26:52', '16:33:34', 0, 0),
(286, 18, '2025-07-07', '07:38:53', '17:14:22', 0, 0),
(287, 78, '2025-07-07', '07:46:57', '17:30:04', 0, 0),
(288, 25, '2025-07-07', '07:49:47', '19:00:10', 0, 0),
(289, 12, '2025-07-07', '07:50:14', '17:01:16', 0, 0),
(290, 75, '2025-07-07', '07:50:39', '16:30:08', 0, 0),
(291, 74, '2025-07-07', '07:51:25', '16:30:09', 0, 0),
(292, 73, '2025-07-07', '07:53:06', '16:30:05', 0, 0),
(293, 31, '2025-07-07', '07:53:59', '16:56:25', 0, 0),
(294, 76, '2025-07-07', '07:55:41', '16:30:06', 0, 0),
(295, 24, '2025-07-07', '07:58:29', '13:00:05', 0, 0),
(296, 65, '2025-07-07', '07:59:06', '20:07:29', 0, 0),
(297, 13, '2025-07-07', '08:00:38', '19:00:08', 0, 0),
(298, 38, '2025-07-07', '08:02:29', '19:01:20', 0, 0),
(299, 15, '2025-07-07', '08:03:11', NULL, 0, 0),
(300, 8, '2025-07-07', '08:06:26', '16:00:54', 0, 0),
(301, 62, '2025-07-07', '08:20:57', '18:11:42', 0, 0),
(302, 79, '2025-07-07', '08:27:28', '20:23:08', 0, 0),
(303, 52, '2025-07-07', '08:38:23', '18:01:17', 0, 0),
(304, 28, '2025-07-07', '08:47:17', '18:00:32', 0, 0),
(305, 43, '2025-07-07', '09:06:28', NULL, 0, 0),
(306, 80, '2025-07-07', '09:19:41', '16:00:08', 0, 0),
(307, 50, '2025-07-07', '09:56:51', '20:45:12', 0, 0),
(308, 81, '2025-07-07', '10:20:39', '16:00:23', 0, 0),
(309, 77, '2025-07-07', '11:03:48', '16:30:24', 0, 0),
(310, 1009, '2025-07-07', '14:49:48', NULL, 0, 0),
(311, 36, '2025-07-08', '05:55:56', '14:37:20', 0, 0),
(312, 68, '2025-07-08', '05:56:35', '15:01:04', 0, 0),
(313, 46, '2025-07-08', '05:59:14', '17:48:16', 0, 0),
(314, 80, '2025-07-08', '06:07:39', '15:00:40', 0, 0),
(315, 81, '2025-07-08', '06:08:36', '15:01:03', 0, 0),
(316, 3, '2025-07-08', '06:21:15', '17:32:41', 0, 0),
(317, 71, '2025-07-08', '06:25:25', '16:27:38', 0, 0),
(318, 29, '2025-07-08', '06:33:05', '12:33:50', 0, 0),
(319, 41, '2025-07-08', '06:36:56', '16:01:45', 0, 0),
(320, 69, '2025-07-08', '06:38:18', '16:00:06', 0, 0),
(321, 58, '2025-07-08', '06:42:31', '20:05:52', 0, 0),
(322, 64, '2025-07-08', '06:43:30', '16:02:23', 0, 0),
(323, 6, '2025-07-08', '06:44:55', '15:07:47', 0, 0),
(324, 66, '2025-07-08', '06:46:01', '16:00:50', 0, 0),
(325, 17, '2025-07-08', '06:46:24', '16:14:33', 0, 0),
(326, 1004, '2025-07-08', '06:50:59', '16:32:24', 0, 0),
(327, 4, '2025-07-08', '06:51:37', '18:00:08', 0, 0),
(328, 10, '2025-07-08', '06:52:06', '16:13:13', 0, 0),
(329, 63, '2025-07-08', '06:52:08', '16:01:08', 0, 0),
(330, 47, '2025-07-08', '06:52:29', '16:00:13', 0, 0),
(331, 21, '2025-07-08', '06:52:51', '18:00:31', 0, 0),
(332, 1007, '2025-07-08', '06:52:54', '16:03:31', 0, 0),
(333, 9, '2025-07-08', '06:53:12', '16:00:05', 0, 0),
(334, 60, '2025-07-08', '06:53:34', NULL, 0, 0),
(335, 49, '2025-07-08', '06:53:51', '18:00:09', 0, 0),
(336, 53, '2025-07-08', '06:53:51', '18:00:10', 0, 0),
(337, 1, '2025-07-08', '06:54:00', '18:00:45', 0, 0),
(338, 27, '2025-07-08', '06:54:16', '16:03:38', 0, 0),
(339, 5, '2025-07-08', '06:54:24', NULL, 0, 0),
(340, 37, '2025-07-08', '06:55:11', '18:00:42', 0, 0),
(341, 56, '2025-07-08', '06:55:15', '18:00:19', 0, 0),
(342, 1003, '2025-07-08', '06:55:16', '16:26:07', 0, 0),
(343, 8, '2025-07-08', '06:55:27', '16:00:09', 0, 0),
(344, 70, '2025-07-08', '06:55:37', '16:01:52', 0, 0),
(345, 20, '2025-07-08', '06:56:15', '16:00:36', 0, 0),
(346, 7, '2025-07-08', '06:57:41', '18:00:17', 0, 0),
(347, 16, '2025-07-08', '07:00:39', '18:00:04', 0, 0),
(348, 22, '2025-07-08', '07:01:04', '18:02:23', 0, 0),
(349, 72, '2025-07-08', '07:06:27', '16:00:36', 0, 0),
(350, 55, '2025-07-08', '07:10:56', '16:30:11', 0, 0),
(351, 61, '2025-07-08', '07:14:23', '16:13:13', 0, 0),
(352, 26, '2025-07-08', '07:29:20', '16:15:58', 0, 0),
(353, 78, '2025-07-08', '07:39:32', '17:00:20', 0, 0),
(354, 76, '2025-07-08', '07:45:38', '16:30:07', 0, 0),
(355, 77, '2025-07-08', '07:47:18', '16:30:19', 0, 0),
(356, 12, '2025-07-08', '07:50:52', '17:01:46', 0, 0),
(357, 74, '2025-07-08', '07:51:19', '16:30:15', 0, 0),
(358, 25, '2025-07-08', '07:52:34', '19:00:09', 0, 0),
(359, 73, '2025-07-08', '07:52:49', '16:30:18', 0, 0),
(360, 24, '2025-07-08', '07:57:06', '19:00:11', 0, 0),
(361, 13, '2025-07-08', '07:57:48', '19:00:06', 0, 0),
(362, 18, '2025-07-08', '07:59:10', '17:06:34', 0, 0),
(363, 75, '2025-07-08', '08:00:06', '16:30:07', 0, 0),
(364, 15, '2025-07-08', '08:00:21', '19:00:59', 0, 0),
(365, 79, '2025-07-08', '08:03:51', NULL, 0, 0),
(366, 57, '2025-07-08', '08:13:21', '17:15:12', 0, 0),
(367, 38, '2025-07-08', '08:13:23', '19:00:31', 0, 0),
(368, 50, '2025-07-08', '08:21:29', '22:05:38', 0, 0),
(369, 43, '2025-07-08', '08:25:57', '19:01:28', 0, 0),
(370, 62, '2025-07-08', '08:26:20', '18:15:08', 0, 0),
(371, 52, '2025-07-08', '08:46:53', '22:09:23', 0, 0),
(372, 28, '2025-07-08', '08:52:34', '18:03:48', 0, 0),
(373, 31, '2025-07-08', '09:50:26', '15:51:20', 0, 0),
(374, 79, '2025-07-09', '00:48:53', NULL, 0, 0),
(375, 80, '2025-07-09', '05:51:24', '15:00:21', 0, 0),
(376, 81, '2025-07-09', '05:53:08', '15:00:42', 0, 0),
(377, 36, '2025-07-09', '05:55:58', NULL, 0, 0),
(378, 25, '2025-07-09', '05:59:00', '19:00:13', 0, 0),
(379, 33, '2025-07-09', '06:09:24', '15:01:05', 0, 0),
(380, 6, '2025-07-09', '06:09:31', '12:01:08', 0, 0),
(381, 41, '2025-07-09', '06:21:28', '16:00:41', 0, 0),
(382, 34, '2025-07-09', '06:24:13', '17:55:28', 0, 0),
(383, 50, '2025-07-09', '06:27:30', '20:05:46', 0, 0),
(384, 3, '2025-07-09', '06:30:06', '17:31:44', 0, 0),
(385, 64, '2025-07-09', '06:32:22', '16:04:55', 0, 0),
(386, 69, '2025-07-09', '06:36:49', '16:00:11', 0, 0),
(387, 71, '2025-07-09', '06:40:20', '17:07:19', 0, 0),
(388, 27, '2025-07-09', '06:45:23', NULL, 0, 0),
(389, 67, '2025-07-09', '06:46:59', '16:17:48', 0, 0),
(390, 17, '2025-07-09', '06:47:08', '17:04:08', 0, 0),
(391, 58, '2025-07-09', '06:48:54', '20:03:26', 0, 0),
(392, 1004, '2025-07-09', '06:49:54', '16:04:22', 0, 0),
(393, 10, '2025-07-09', '06:50:02', '16:17:40', 0, 0),
(394, 43, '2025-07-09', '06:50:27', '16:01:46', 0, 0),
(395, 47, '2025-07-09', '06:50:42', '16:01:07', 0, 0),
(396, 9, '2025-07-09', '06:51:05', '16:00:57', 0, 0),
(397, 14, '2025-07-09', '06:51:35', '18:00:06', 0, 0),
(398, 60, '2025-07-09', '06:51:39', NULL, 0, 0),
(399, 1, '2025-07-09', '06:51:39', '18:01:19', 0, 0),
(400, 63, '2025-07-09', '06:51:56', '16:01:21', 0, 0),
(401, 7, '2025-07-09', '06:52:04', '18:00:45', 0, 0),
(402, 4, '2025-07-09', '06:52:48', '18:01:40', 0, 0),
(403, 49, '2025-07-09', '06:53:18', '18:00:04', 0, 0),
(404, 5, '2025-07-09', '06:53:53', '18:00:10', 0, 0),
(405, 20, '2025-07-09', '06:55:31', '16:04:59', 0, 0),
(406, 66, '2025-07-09', '06:55:32', '16:00:53', 0, 0),
(407, 21, '2025-07-09', '06:55:36', '18:00:35', 0, 0),
(408, 31, '2025-07-09', '06:57:11', '18:00:26', 0, 0),
(409, 70, '2025-07-09', '06:57:30', '16:01:45', 0, 0),
(410, 29, '2025-07-09', '06:57:31', '18:00:20', 0, 0),
(411, 55, '2025-07-09', '06:57:40', '16:30:44', 0, 0),
(412, 57, '2025-07-09', '06:58:46', '16:00:05', 0, 0),
(413, 30, '2025-07-09', '06:59:44', '18:00:32', 0, 0),
(414, 16, '2025-07-09', '07:00:01', '18:00:04', 0, 0),
(415, 37, '2025-07-09', '07:02:01', '18:00:29', 0, 0),
(416, 22, '2025-07-09', '07:02:48', '18:00:47', 0, 0),
(417, 1006, '2025-07-08', '07:04:33', '16:02:12', 0, 0),
(418, 46, '2025-07-09', '07:07:39', '18:01:29', 0, 0),
(419, 61, '2025-07-09', '07:14:59', '16:03:52', 0, 0),
(420, 72, '2025-07-09', '07:16:43', '16:02:53', 0, 0),
(421, 26, '2025-07-09', '07:18:12', '16:18:47', 0, 0),
(422, 12, '2025-07-09', '07:29:52', '16:30:37', 0, 0),
(423, 56, '2025-07-09', '07:31:36', '18:00:47', 0, 0),
(424, 77, '2025-07-09', '07:43:31', '16:30:08', 0, 0),
(425, 76, '2025-07-09', '07:43:56', '16:30:06', 0, 0),
(426, 78, '2025-07-09', '07:44:35', '17:00:30', 0, 0),
(427, 18, '2025-07-09', '07:46:31', '17:03:00', 0, 0),
(428, 75, '2025-07-09', '07:50:26', '16:30:10', 0, 0),
(429, 53, '2025-07-09', '07:50:45', '19:00:09', 0, 0),
(430, 15, '2025-07-09', '07:52:09', '19:00:44', 0, 0),
(431, 73, '2025-07-09', '07:55:17', '16:30:04', 0, 0),
(432, 74, '2025-07-09', '07:57:51', '16:30:10', 0, 0),
(433, 13, '2025-07-09', '07:59:50', '19:00:04', 0, 0),
(434, 24, '2025-07-09', '08:00:32', '19:00:08', 0, 0),
(435, 38, '2025-07-09', '08:08:34', '19:00:34', 0, 0),
(436, 62, '2025-07-09', '08:21:57', '19:32:17', 0, 0),
(437, 28, '2025-07-09', '08:41:04', '18:00:17', 0, 0),
(438, 52, '2025-07-09', '08:42:03', NULL, 0, 0),
(439, 1003, '2025-07-09', '09:03:05', '16:03:02', 0, 0),
(440, 65, '2025-07-09', '09:56:06', '17:24:49', 0, 0),
(441, 1006, '2025-07-09', '07:00:33', '16:00:00', 0, 0),
(442, 1009, '2025-07-09', '15:35:28', '15:35:33', 0, 0),
(443, 7, '2025-07-10', '05:48:05', '17:28:58', 0, 0),
(444, 81, '2025-07-10', '05:49:26', '15:00:41', 0, 0),
(445, 80, '2025-07-10', '05:53:35', '15:00:19', 0, 0),
(446, 37, '2025-07-10', '05:55:30', '17:43:18', 0, 0),
(447, 37, '2025-07-10', '05:55:30', '17:43:18', 0, 0),
(448, 37, '2025-07-10', '05:55:30', '17:43:18', 0, 0),
(449, 67, '2025-07-10', '05:59:00', '16:42:00', 0, 0),
(450, 68, '2025-07-10', '05:59:57', '15:01:08', 0, 0),
(451, 36, '2025-07-10', '06:07:51', '14:30:39', 0, 0),
(452, 33, '2025-07-10', '06:09:57', '15:00:15', 0, 0),
(453, 20, '2025-07-10', '06:12:21', '16:06:43', 0, 0),
(454, 25, '2025-07-10', '06:14:44', '19:00:10', 0, 0),
(455, 6, '2025-07-10', '06:15:08', '15:01:55', 0, 0),
(456, 71, '2025-07-10', '06:25:15', '17:31:19', 0, 0),
(457, 3, '2025-07-10', '06:32:35', '17:36:08', 0, 0),
(458, 69, '2025-07-10', '06:37:52', '16:00:08', 0, 0),
(459, 64, '2025-07-10', '06:42:32', '16:11:21', 0, 0),
(460, 61, '2025-07-10', '06:43:27', '16:56:31', 0, 0),
(461, 63, '2025-07-10', '06:47:35', '16:06:25', 0, 0),
(462, 10, '2025-07-10', '06:49:53', '16:02:30', 0, 0),
(463, 27, '2025-07-10', '06:50:02', '16:14:11', 0, 0),
(464, 1006, '2025-07-10', '06:50:54', '16:02:33', 0, 0),
(465, 9, '2025-07-10', '06:51:00', '16:00:16', 0, 0),
(466, 34, '2025-07-10', '06:51:00', '18:00:05', 0, 0),
(467, 53, '2025-07-10', '06:51:18', '18:00:06', 0, 0),
(468, 31, '2025-07-10', '06:51:28', '18:00:11', 0, 0),
(469, 60, '2025-07-10', '06:51:33', NULL, 0, 0),
(470, 72, '2025-07-10', '06:51:40', '16:00:03', 0, 0),
(471, 1003, '2025-07-10', '06:51:56', '16:02:31', 0, 0),
(472, 5, '2025-07-10', '06:51:59', '18:00:11', 0, 0),
(473, 4, '2025-07-10', '06:52:06', '18:00:07', 0, 0),
(474, 47, '2025-07-10', '06:52:07', '16:02:40', 0, 0),
(475, 29, '2025-07-10', '06:52:23', '18:00:32', 0, 0),
(476, 1004, '2025-07-10', '06:52:39', '16:02:21', 0, 0),
(477, 17, '2025-07-10', '06:53:34', '16:13:23', 0, 0),
(478, 50, '2025-07-10', '06:53:37', '21:36:52', 0, 0),
(479, 1, '2025-07-10', '06:53:53', '18:00:39', 0, 0),
(480, 16, '2025-07-10', '06:54:33', '18:00:03', 0, 0),
(481, 66, '2025-07-10', '06:55:25', '16:00:36', 0, 0),
(482, 70, '2025-07-10', '06:57:23', '16:02:15', 0, 0),
(483, 58, '2025-07-10', '06:58:07', '20:00:18', 0, 0),
(484, 22, '2025-07-10', '06:58:38', '18:00:20', 0, 0),
(485, 14, '2025-07-10', '06:58:40', '18:00:11', 0, 0),
(486, 30, '2025-07-10', '07:04:20', '18:00:34', 0, 0),
(487, 46, '2025-07-10', '07:05:16', '18:01:34', 0, 0),
(488, 1007, '2025-07-10', '07:13:43', '16:02:11', 0, 0),
(489, 55, '2025-07-10', '07:16:32', '16:30:06', 0, 0),
(490, 41, '2025-07-10', '07:20:59', '16:00:17', 0, 0),
(491, 26, '2025-07-10', '07:28:45', '16:08:32', 0, 0),
(492, 38, '2025-07-10', '07:35:03', '20:01:35', 0, 0),
(493, 12, '2025-07-10', '07:36:01', '16:30:39', 0, 0),
(494, 18, '2025-07-10', '07:42:40', '17:19:00', 0, 0),
(495, 77, '2025-07-10', '07:47:03', '16:30:11', 0, 0),
(496, 75, '2025-07-10', '07:49:29', '16:30:17', 0, 0),
(497, 13, '2025-07-10', '07:49:46', '19:00:12', 0, 0),
(498, 74, '2025-07-10', '07:50:37', '16:30:10', 0, 0),
(499, 76, '2025-07-10', '07:50:43', '16:30:06', 0, 0),
(500, 24, '2025-07-10', '07:51:06', '19:00:09', 0, 0),
(501, 65, '2025-07-10', '07:54:15', '19:05:13', 0, 0),
(502, 73, '2025-07-10', '07:56:35', '16:30:04', 0, 0),
(503, 21, '2025-07-10', '08:08:04', '19:09:34', 0, 0),
(504, 62, '2025-07-10', '08:29:43', '18:10:32', 0, 0),
(505, 52, '2025-07-10', '08:44:32', '18:06:24', 0, 0),
(506, 28, '2025-07-10', '08:52:26', '18:00:41', 0, 0),
(507, 1009, '2025-07-10', '09:23:50', '16:01:03', 0, 0),
(508, 43, '2025-07-10', '09:31:02', '19:00:42', 0, 0),
(509, 79, '2025-07-10', '10:27:34', NULL, 0, 0),
(510, 56, '2025-07-10', '13:55:29', '18:01:31', 0, 0),
(511, 78, '2025-07-10', '15:18:08', '17:03:16', 0, 0),
(512, 36, '2025-07-11', '05:51:47', '12:42:19', 0, 0),
(513, 49, '2025-07-11', '05:52:56', '17:01:56', 0, 0),
(514, 81, '2025-07-11', '05:53:57', '15:01:22', 0, 0),
(515, 80, '2025-07-11', '05:56:38', '15:00:45', 0, 0),
(516, 68, '2025-07-11', '05:58:11', '15:01:08', 0, 0),
(517, 33, '2025-07-11', '06:12:24', '15:01:11', 0, 0),
(518, 41, '2025-07-11', '06:22:34', '16:01:23', 0, 0),
(519, 6, '2025-07-11', '06:26:13', '15:09:27', 0, 0),
(520, 63, '2025-07-11', '06:27:17', '16:12:27', 0, 0),
(521, 64, '2025-07-11', '06:32:44', '16:20:10', 0, 0),
(522, 69, '2025-07-11', '06:34:38', '16:00:03', 0, 0),
(523, 71, '2025-07-11', '06:36:38', '17:01:50', 0, 0),
(524, 66, '2025-07-11', '06:42:56', '16:02:30', 0, 0),
(525, 9, '2025-07-11', '06:50:31', '16:00:10', 0, 0),
(526, 1007, '2025-07-11', '06:50:41', '07:41:53', 0, 0),
(527, 28, '2025-07-11', '06:50:51', '16:01:06', 0, 0),
(528, 34, '2025-07-11', '06:51:18', '18:00:04', 0, 0),
(529, 1004, '2025-07-11', '06:51:18', '16:31:37', 0, 0),
(530, 60, '2025-07-11', '06:51:21', '16:03:06', 0, 0),
(531, 20, '2025-07-11', '06:51:25', '16:00:45', 0, 0),
(532, 1003, '2025-07-11', '06:51:38', '16:29:27', 0, 0),
(533, 4, '2025-07-11', '06:52:19', '18:00:07', 0, 0),
(534, 27, '2025-07-11', '06:52:46', '16:01:04', 0, 0),
(535, 1006, '2025-07-11', '06:52:59', '16:35:51', 0, 0),
(536, 17, '2025-07-11', '06:55:14', '16:11:20', 0, 0),
(537, 58, '2025-07-11', '06:55:22', '20:03:55', 0, 0),
(538, 14, '2025-07-11', '06:56:15', '18:00:14', 0, 0),
(539, 57, '2025-07-11', '06:56:56', '18:00:04', 0, 0),
(540, 47, '2025-07-11', '06:57:07', NULL, 0, 0),
(541, 10, '2025-07-11', '06:57:29', '16:02:00', 0, 0),
(542, 31, '2025-07-11', '06:58:29', '18:00:18', 0, 0),
(543, 1, '2025-07-11', '06:58:31', '18:01:15', 0, 0),
(544, 29, '2025-07-11', '06:58:51', '18:00:22', 0, 0),
(545, 70, '2025-07-11', '07:00:19', '16:02:03', 0, 0),
(546, 46, '2025-07-11', '07:01:00', '18:00:58', 0, 0),
(547, 30, '2025-07-11', '07:02:12', '18:00:54', 0, 0),
(548, 61, '2025-07-11', '07:05:23', '16:12:25', 0, 0),
(549, 38, '2025-07-11', '07:05:59', '13:39:10', 0, 0),
(550, 11, '2025-07-11', '07:11:46', NULL, 0, 0),
(551, 72, '2025-07-11', '07:20:10', '16:00:45', 0, 0),
(552, 22, '2025-07-11', '07:20:36', '18:20:28', 0, 0),
(553, 37, '2025-07-11', '07:26:09', '18:37:55', 0, 0),
(554, 7, '2025-07-11', '07:28:25', '18:36:19', 0, 0),
(555, 26, '2025-07-11', '07:29:03', '16:10:05', 0, 0),
(556, 1009, '2025-07-11', '07:38:25', '16:36:36', 0, 0),
(557, 78, '2025-07-11', '07:38:48', '17:00:08', 0, 0),
(558, 18, '2025-07-11', '07:41:42', '17:01:33', 0, 0),
(559, 77, '2025-07-11', '07:44:36', '16:30:08', 0, 0),
(560, 76, '2025-07-11', '07:44:47', '16:30:05', 0, 0),
(561, 73, '2025-07-11', '07:45:20', '16:30:05', 0, 0),
(562, 75, '2025-07-11', '07:50:46', '16:30:07', 0, 0),
(563, 56, '2025-07-11', '07:53:58', '19:16:18', 0, 0),
(564, 65, '2025-07-11', '07:57:40', '18:26:01', 0, 0),
(565, 55, '2025-07-11', '08:00:29', '16:30:04', 0, 0),
(566, 43, '2025-07-11', '08:02:37', '18:01:27', 0, 0),
(567, 79, '2025-07-11', '08:05:04', NULL, 0, 0),
(568, 67, '2025-07-11', '08:11:19', '18:00:15', 0, 0),
(569, 21, '2025-07-11', '08:16:36', '19:16:46', 0, 0),
(570, 50, '2025-07-11', '08:23:18', '18:11:02', 0, 0),
(571, 12, '2025-07-11', '08:26:20', '16:34:55', 0, 0),
(572, 62, '2025-07-11', '08:33:54', '18:10:22', 0, 0),
(573, 52, '2025-07-11', '08:39:53', '18:20:11', 0, 0),
(574, 16, '2025-07-11', '08:46:29', '18:00:32', 0, 0),
(575, 74, '2025-07-11', '16:28:47', '16:30:11', 0, 0),
(576, 29, '2025-07-12', '05:46:31', '17:01:45', 0, 0),
(577, 31, '2025-07-12', '05:48:58', '17:02:19', 0, 0),
(578, 49, '2025-07-12', '05:51:27', '18:00:37', 0, 0),
(579, 21, '2025-07-12', '05:51:44', '17:02:17', 0, 0),
(580, 30, '2025-07-12', '05:52:33', '17:01:29', 0, 0),
(581, 46, '2025-07-12', '06:22:22', '12:45:44', 0, 0),
(582, 67, '2025-07-12', '06:40:57', '18:01:23', 0, 0),
(583, 5, '2025-07-12', '06:41:43', '18:05:16', 0, 0),
(584, 34, '2025-07-12', '06:51:05', '18:00:09', 0, 0),
(585, 4, '2025-07-12', '06:51:29', '16:00:20', 0, 0),
(586, 25, '2025-07-12', '06:53:07', '18:00:04', 0, 0),
(587, 60, '2025-07-12', '06:53:13', '18:01:35', 0, 0),
(588, 1, '2025-07-12', '06:53:19', '18:00:26', 0, 0),
(589, 10, '2025-07-12', '06:53:36', '16:00:18', 0, 0),
(590, 14, '2025-07-12', '06:54:22', '18:00:17', 0, 0),
(591, 57, '2025-07-12', '06:54:35', '18:00:05', 0, 0),
(592, 58, '2025-07-12', '06:58:59', '17:05:51', 0, 0),
(593, 22, '2025-07-12', '07:08:29', '18:02:17', 0, 0),
(594, 11, '2025-07-12', '07:09:23', '18:01:53', 0, 0),
(595, 43, '2025-07-12', '07:10:15', '13:24:05', 0, 0),
(596, 50, '2025-07-12', '07:14:26', '16:12:20', 0, 0),
(597, 65, '2025-07-12', '07:53:51', '19:02:42', 0, 0),
(598, 62, '2025-07-12', '08:32:25', '17:07:49', 0, 0),
(599, 24, '2025-07-12', '12:10:13', '16:10:09', 0, 0),
(600, 13, '2025-07-12', '13:26:22', '17:27:02', 0, 0),
(601, 35, '2025-07-13', '05:40:56', '17:00:11', 0, 0),
(602, 30, '2025-07-13', '05:50:22', '12:00:30', 0, 0),
(603, 34, '2025-07-13', '05:56:00', '18:01:24', 0, 0),
(604, 5, '2025-07-13', '06:03:46', '17:00:17', 0, 0),
(605, 37, '2025-07-13', '06:45:25', '18:01:23', 0, 0),
(606, 7, '2025-07-13', '06:50:38', '22:12:21', 0, 0),
(607, 25, '2025-07-13', '06:53:20', '18:00:03', 0, 0),
(608, 57, '2025-07-13', '06:53:33', '18:00:16', 0, 0),
(609, 11, '2025-07-13', '06:55:20', '18:00:42', 0, 0),
(610, 58, '2025-07-13', '06:55:20', '17:02:25', 0, 0),
(611, 65, '2025-07-13', '07:42:34', '19:06:15', 0, 0),
(612, 15, '2025-07-13', '07:54:44', NULL, 0, 0),
(613, 62, '2025-07-13', '08:46:34', '17:05:08', 0, 0),
(614, 38, '2025-07-13', '12:08:15', '18:13:28', 0, 0),
(615, 6, '2025-07-14', '05:46:41', '15:27:58', 0, 0),
(616, 81, '2025-07-14', '05:52:06', '15:01:27', 0, 0),
(617, 80, '2025-07-14', '05:56:02', '15:01:42', 0, 0),
(618, 36, '2025-07-14', '05:56:03', '14:31:37', 0, 0),
(619, 68, '2025-07-14', '06:04:42', '15:04:04', 0, 0),
(620, 41, '2025-07-14', '06:17:03', '16:01:16', 0, 0),
(621, 33, '2025-07-14', '06:22:00', '15:00:18', 0, 0),
(622, 64, '2025-07-14', '06:26:52', '16:03:49', 0, 0),
(623, 3, '2025-07-14', '06:28:43', '17:40:05', 0, 0),
(624, 69, '2025-07-14', '06:38:55', '16:00:12', 0, 0),
(625, 67, '2025-07-14', '06:45:39', '19:36:19', 0, 0),
(626, 27, '2025-07-14', '06:49:21', '16:14:44', 0, 0),
(627, 27, '2025-07-14', '06:49:22', '16:14:44', 0, 0),
(628, 5, '2025-07-14', '06:49:26', '18:00:16', 0, 0),
(629, 53, '2025-07-14', '06:50:38', '18:00:14', 0, 0),
(630, 1007, '2025-07-14', '06:50:46', '16:03:54', 0, 0),
(631, 35, '2025-07-14', '06:50:48', '18:00:14', 0, 0),
(632, 60, '2025-07-14', '06:51:07', NULL, 0, 0),
(633, 4, '2025-07-14', '06:51:22', '16:00:14', 0, 0),
(634, 21, '2025-07-14', '06:51:46', '18:00:14', 0, 0),
(635, 71, '2025-07-14', '06:52:03', '18:06:18', 0, 0),
(636, 31, '2025-07-14', '06:52:17', '16:02:16', 0, 0),
(637, 7, '2025-07-14', '06:52:22', '18:00:48', 0, 0),
(638, 34, '2025-07-14', '06:52:37', '18:03:04', 0, 0),
(639, 58, '2025-07-14', '06:52:43', '20:05:14', 0, 0),
(640, 37, '2025-07-14', '06:53:12', '18:01:43', 0, 0),
(641, 47, '2025-07-14', '06:53:20', '16:03:02', 0, 0),
(642, 9, '2025-07-14', '06:53:27', '16:00:31', 0, 0),
(643, 17, '2025-07-14', '06:54:05', '16:06:02', 0, 0),
(644, 20, '2025-07-14', '06:54:55', '16:01:55', 0, 0),
(645, 1, '2025-07-14', '06:56:19', '16:01:02', 0, 0),
(646, 1003, '2025-07-14', '06:56:48', '16:36:48', 0, 0),
(647, 63, '2025-07-14', '06:56:49', '16:00:13', 0, 0),
(648, 66, '2025-07-14', '06:57:43', '16:00:23', 0, 0),
(649, 70, '2025-07-14', '06:59:03', '16:00:59', 0, 0),
(650, 22, '2025-07-14', '07:02:01', '18:01:24', 0, 0),
(651, 26, '2025-07-14', '07:03:13', '16:14:31', 0, 0),
(652, 1006, '2025-07-14', '07:04:03', '16:05:33', 0, 0),
(653, 46, '2025-07-14', '07:05:36', '18:01:41', 0, 0),
(654, 1009, '2025-07-14', '07:07:39', '16:01:21', 0, 0),
(655, 1009, '2025-07-14', '07:07:39', '16:01:21', 0, 0),
(656, 29, '2025-07-14', '07:08:11', '16:09:33', 0, 0),
(657, 72, '2025-07-14', '07:09:55', '16:04:12', 0, 0),
(658, 61, '2025-07-14', '07:14:03', '17:13:22', 0, 0),
(659, 12, '2025-07-14', '07:23:10', '16:33:23', 0, 0),
(660, 55, '2025-07-14', '07:25:46', '16:30:09', 0, 0),
(661, 78, '2025-07-14', '07:34:32', '17:02:09', 0, 0),
(662, 50, '2025-07-14', '07:41:50', '17:04:19', 0, 0),
(663, 77, '2025-07-14', '07:46:17', '16:30:19', 0, 0),
(664, 73, '2025-07-14', '07:47:03', '16:30:03', 0, 0),
(665, 74, '2025-07-14', '07:49:27', '16:30:15', 0, 0),
(666, 75, '2025-07-14', '07:49:28', '16:30:13', 0, 0),
(667, 18, '2025-07-14', '07:53:01', '17:25:18', 0, 0),
(668, 57, '2025-07-14', '07:54:46', '17:00:04', 0, 0),
(669, 76, '2025-07-14', '07:55:14', '16:30:17', 0, 0),
(670, 79, '2025-07-14', '07:56:17', NULL, 0, 0),
(671, 24, '2025-07-14', '07:58:42', '19:00:05', 0, 0),
(672, 65, '2025-07-14', '07:59:08', '19:05:42', 0, 0),
(673, 43, '2025-07-14', '08:00:01', '19:01:39', 0, 0),
(674, 13, '2025-07-14', '08:00:06', '19:00:05', 0, 0),
(675, 25, '2025-07-14', '08:13:01', '19:00:04', 0, 0),
(676, 8, '2025-07-14', '08:17:15', '18:00:02', 0, 0),
(677, 1004, '2025-07-14', '08:20:39', NULL, 0, 0),
(678, 62, '2025-07-14', '08:23:15', '18:21:25', 0, 0),
(679, 56, '2025-07-14', '08:29:14', '19:34:02', 0, 0),
(680, 38, '2025-07-14', '08:39:40', '18:59:23', 0, 0),
(681, 52, '2025-07-14', '08:46:22', '18:03:03', 0, 0),
(682, 28, '2025-07-14', '08:51:26', '18:01:50', 0, 0),
(683, 49, '2025-07-14', '09:00:28', '18:00:05', 0, 0),
(684, 86, '2025-07-14', '09:44:20', '16:03:57', 0, 0),
(685, 1005, '2025-07-14', '09:55:20', NULL, 0, 0),
(686, 82, '2025-07-14', '15:11:01', '16:35:51', 0, 0),
(687, 81, '2025-07-15', '05:51:38', '15:00:34', 0, 0),
(688, 68, '2025-07-15', '05:54:05', '15:00:22', 0, 0),
(689, 36, '2025-07-15', '05:55:28', '14:31:08', 0, 0),
(690, 80, '2025-07-15', '05:58:00', '15:00:38', 0, 0),
(691, 6, '2025-07-15', '06:08:26', '15:09:15', 0, 0),
(692, 33, '2025-07-15', '06:10:25', '15:00:20', 0, 0),
(693, 66, '2025-07-15', '06:16:39', '16:00:28', 0, 0),
(694, 3, '2025-07-15', '06:23:09', '17:32:49', 0, 0),
(695, 69, '2025-07-15', '06:38:13', '16:00:05', 0, 0),
(696, 63, '2025-07-15', '06:38:31', '16:14:14', 0, 0),
(697, 82, '2025-07-15', '06:38:34', '16:01:45', 0, 0),
(698, 64, '2025-07-15', '06:41:59', '16:18:04', 0, 0),
(699, 86, '2025-07-15', '06:46:54', '16:05:16', 0, 0),
(700, 86, '2025-07-15', '06:46:54', '16:05:16', 0, 0),
(701, 58, '2025-07-15', '06:50:32', '20:04:33', 0, 0),
(702, 1003, '2025-07-15', '06:51:18', '16:31:47', 0, 0),
(703, 1006, '2025-07-15', '06:51:38', '16:01:03', 0, 0),
(704, 41, '2025-07-15', '06:52:13', '16:00:38', 0, 0),
(705, 10, '2025-07-15', '06:52:33', '16:01:48', 0, 0),
(706, 1004, '2025-07-15', '06:52:37', '16:31:28', 0, 0),
(707, 4, '2025-07-15', '06:52:45', '18:00:10', 0, 0),
(708, 4, '2025-07-15', '06:52:45', '18:00:10', 0, 0),
(709, 4, '2025-07-15', '06:52:47', '18:00:10', 0, 0),
(710, 4, '2025-07-15', '06:52:49', '18:00:10', 0, 0),
(711, 4, '2025-07-15', '06:52:49', '18:00:10', 0, 0),
(712, 7, '2025-07-15', '06:52:59', '18:00:07', 0, 0),
(713, 35, '2025-07-15', '06:53:23', '18:00:05', 0, 0),
(714, 9, '2025-07-15', '06:53:27', '16:00:12', 0, 0),
(715, 27, '2025-07-15', '06:53:37', '16:05:29', 0, 0),
(716, 70, '2025-07-15', '06:54:14', '16:04:33', 0, 0),
(717, 17, '2025-07-15', '06:54:16', '16:10:09', 0, 0),
(718, 60, '2025-07-15', '06:54:18', '18:00:37', 0, 0),
(719, 1, '2025-07-15', '06:54:32', '18:00:40', 0, 0),
(720, 56, '2025-07-15', '06:54:54', '18:00:53', 0, 0),
(721, 1007, '2025-07-15', '06:55:10', '16:05:18', 0, 0),
(722, 53, '2025-07-15', '06:55:17', '18:01:26', 0, 0),
(723, 49, '2025-07-15', '06:55:34', '18:00:02', 0, 0),
(724, 21, '2025-07-15', '06:55:48', '18:00:41', 0, 0),
(725, 22, '2025-07-15', '06:59:46', '18:00:41', 0, 0),
(726, 5, '2025-07-15', '06:59:57', '18:00:10', 0, 0),
(727, 61, '2025-07-15', '07:00:00', '16:07:20', 0, 0),
(728, 46, '2025-07-15', '07:03:16', '18:09:38', 0, 0),
(729, 55, '2025-07-15', '07:06:44', '16:30:20', 0, 0),
(730, 85, '2025-07-15', '07:07:57', '16:02:04', 0, 0),
(731, 83, '2025-07-15', '07:09:36', '16:00:13', 0, 0),
(732, 84, '2025-07-15', '07:10:01', '16:01:30', 0, 0),
(733, 72, '2025-07-15', '07:13:03', '16:01:07', 0, 0),
(734, 47, '2025-07-15', '07:15:25', '16:00:06', 0, 0),
(735, 1009, '2025-07-15', '07:23:44', '16:01:59', 0, 0),
(736, 26, '2025-07-15', '07:23:58', '16:03:08', 0, 0),
(737, 29, '2025-07-15', '07:27:24', '13:31:55', 0, 0),
(738, 31, '2025-07-15', '07:28:30', '13:32:06', 0, 0),
(739, 71, '2025-07-15', '07:34:46', '16:40:02', 0, 0),
(740, 77, '2025-07-15', '07:42:48', '16:30:11', 0, 0),
(741, 78, '2025-07-15', '07:46:42', '17:00:22', 0, 0),
(742, 74, '2025-07-15', '07:48:28', '16:30:09', 0, 0),
(743, 76, '2025-07-15', '07:49:18', '16:30:07', 0, 0),
(744, 43, '2025-07-15', '07:51:11', '19:01:30', 0, 0),
(745, 73, '2025-07-15', '07:52:06', '16:30:05', 0, 0),
(746, 18, '2025-07-15', '07:53:44', '17:09:35', 0, 0),
(747, 57, '2025-07-15', '07:54:15', '17:00:16', 0, 0),
(748, 67, '2025-07-15', '07:57:03', '18:32:38', 0, 0),
(749, 13, '2025-07-15', '07:58:12', '19:00:07', 0, 0),
(750, 24, '2025-07-15', '07:58:50', '19:00:05', 0, 0),
(751, 15, '2025-07-15', '08:01:01', '19:01:07', 0, 0),
(752, 75, '2025-07-15', '08:02:57', '16:30:08', 0, 0),
(753, 50, '2025-07-15', '08:04:17', '18:13:20', 0, 0),
(754, 25, '2025-07-15', '08:14:05', '19:00:05', 0, 0),
(755, 62, '2025-07-15', '08:19:51', '18:06:29', 0, 0),
(756, 38, '2025-07-15', '08:28:42', '18:59:36', 0, 0),
(757, 52, '2025-07-15', '08:48:28', '18:03:39', 0, 0),
(758, 37, '2025-07-15', '09:00:36', '18:00:09', 0, 0),
(759, 28, '2025-07-15', '09:06:09', '18:01:19', 0, 0),
(760, 12, '2025-07-15', '09:27:31', '16:31:34', 0, 0),
(761, 20, '2025-07-15', '14:33:21', '16:02:30', 0, 0),
(762, 79, '2025-07-15', '14:53:14', NULL, 0, 0),
(763, 81, '2025-07-16', '05:50:40', NULL, 0, 0),
(764, 49, '2025-07-16', '05:53:21', '17:00:15', 0, 0),
(765, 80, '2025-07-16', '05:54:48', '15:03:46', 0, 0),
(766, 36, '2025-07-16', '05:55:34', '14:31:36', 0, 0),
(767, 68, '2025-07-16', '05:58:48', '15:00:52', 0, 0),
(768, 5, '2025-07-16', '06:04:00', '17:02:27', 0, 0),
(769, 20, '2025-07-16', '06:04:45', '16:12:47', 0, 0),
(770, 33, '2025-07-16', '06:06:27', '11:01:55', 0, 0),
(771, 82, '2025-07-16', '06:09:01', '16:01:53', 0, 0),
(772, 25, '2025-07-16', '06:21:47', '18:00:09', 0, 0),
(773, 3, '2025-07-16', '06:27:55', '17:33:33', 0, 0),
(774, 69, '2025-07-16', '06:36:32', '16:00:14', 0, 0),
(775, 63, '2025-07-16', '06:42:08', '16:18:04', 0, 0),
(776, 7, '2025-07-16', '06:47:14', '18:05:13', 0, 0),
(777, 27, '2025-07-16', '06:47:55', '16:07:04', 0, 0),
(778, 27, '2025-07-16', '06:47:57', '16:07:04', 0, 0),
(779, 27, '2025-07-16', '06:48:00', '16:07:04', 0, 0),
(780, 27, '2025-07-16', '06:48:01', '16:07:04', 0, 0),
(781, 27, '2025-07-16', '06:48:01', '16:07:04', 0, 0),
(782, 86, '2025-07-16', '06:49:37', '16:03:58', 0, 0),
(783, 6, '2025-07-16', '06:50:16', '15:02:21', 0, 0),
(784, 47, '2025-07-16', '06:51:48', '16:00:51', 0, 0),
(785, 58, '2025-07-16', '06:52:03', '20:08:01', 0, 0),
(786, 35, '2025-07-16', '06:52:21', '18:00:06', 0, 0),
(787, 1006, '2025-07-16', '06:52:24', '16:02:00', 0, 0),
(788, 14, '2025-07-16', '06:53:03', '18:01:21', 0, 0),
(789, 31, '2025-07-16', '06:53:27', '18:00:15', 0, 0),
(790, 1003, '2025-07-16', '06:53:42', '16:09:10', 0, 0),
(791, 4, '2025-07-16', '06:53:55', '18:00:03', 0, 0),
(792, 21, '2025-07-16', '06:53:57', '18:00:02', 0, 0),
(793, 9, '2025-07-16', '06:54:28', '16:00:37', 0, 0),
(794, 60, '2025-07-16', '06:54:38', NULL, 0, 0),
(795, 1, '2025-07-16', '06:54:42', '18:00:24', 0, 0),
(796, 59, '2025-07-16', '06:54:59', '19:01:00', 0, 0),
(797, 17, '2025-07-16', '06:55:08', '16:20:03', 0, 0),
(798, 29, '2025-07-16', '06:55:08', '18:00:17', 0, 0),
(799, 66, '2025-07-16', '06:55:29', '16:00:49', 0, 0),
(800, 1004, '2025-07-16', '06:56:17', '16:05:49', 0, 0),
(801, 10, '2025-07-16', '06:56:48', '16:04:50', 0, 0),
(802, 72, '2025-07-16', '06:57:34', '16:00:57', 0, 0),
(803, 37, '2025-07-16', '06:58:07', '18:00:04', 0, 0),
(804, 70, '2025-07-16', '06:59:54', '16:00:49', 0, 0),
(805, 55, '2025-07-16', '07:01:50', '16:30:15', 0, 0),
(806, 1007, '2025-07-16', '07:02:27', '16:04:27', 0, 0),
(807, 16, '2025-07-16', '07:02:54', '18:00:10', 0, 0),
(808, 30, '2025-07-16', '07:03:01', '18:03:03', 0, 0),
(809, 22, '2025-07-16', '07:04:10', '18:01:08', 0, 0),
(810, 46, '2025-07-16', '07:12:20', '18:00:23', 0, 0),
(811, 61, '2025-07-16', '07:14:11', '16:34:32', 0, 0),
(812, 1009, '2025-07-16', '07:19:04', '16:00:53', 0, 0),
(813, 71, '2025-07-16', '07:19:16', '17:08:19', 0, 0),
(814, 56, '2025-07-16', '07:20:00', '18:01:10', 0, 0),
(815, 26, '2025-07-16', '07:34:05', '16:17:34', 0, 0),
(816, 12, '2025-07-16', '07:34:37', '16:30:51', 0, 0),
(817, 18, '2025-07-16', '07:46:22', '17:12:48', 0, 0),
(818, 75, '2025-07-16', '07:48:42', '16:30:14', 0, 0),
(819, 77, '2025-07-16', '07:49:45', '16:30:11', 0, 0),
(820, 76, '2025-07-16', '07:49:55', NULL, 0, 0),
(821, 74, '2025-07-16', '07:50:58', '07:51:05', 0, 0),
(822, 15, '2025-07-16', '07:51:45', '19:01:13', 0, 0),
(823, 53, '2025-07-16', '07:52:12', '19:00:14', 0, 0),
(824, 78, '2025-07-16', '07:52:15', '17:01:51', 0, 0),
(825, 50, '2025-07-16', '07:52:29', '16:34:32', 0, 0),
(826, 73, '2025-07-16', '08:00:58', '16:30:03', 0, 0),
(827, 41, '2025-07-16', '08:02:14', '16:00:40', 0, 0),
(828, 67, '2025-07-16', '08:10:20', '18:51:58', 0, 0),
(829, 79, '2025-07-16', '08:13:57', NULL, 0, 0),
(830, 62, '2025-07-16', '08:14:40', '18:11:19', 0, 0),
(831, 34, '2025-07-16', '08:24:15', '19:39:20', 0, 0),
(832, 52, '2025-07-16', '08:47:37', '18:01:37', 0, 0),
(833, 28, '2025-07-16', '08:51:17', '18:00:42', 0, 0),
(834, 1005, '2025-07-16', '09:51:11', '19:02:54', 0, 0),
(835, 65, '2025-07-16', '09:53:41', '12:13:43', 0, 0),
(836, 81, '2025-07-17', '05:51:50', '15:00:42', 0, 0),
(837, 36, '2025-07-17', '05:54:09', '14:30:12', 0, 0),
(838, 80, '2025-07-17', '05:55:50', '15:00:40', 0, 0),
(839, 68, '2025-07-17', '05:56:58', '15:00:44', 0, 0),
(840, 49, '2025-07-17', '06:00:07', '17:00:34', 0, 0),
(841, 25, '2025-07-17', '06:13:16', '19:00:27', 0, 0),
(842, 33, '2025-07-17', '06:15:02', '15:00:59', 0, 0),
(843, 41, '2025-07-17', '06:23:58', '16:01:42', 0, 0),
(844, 86, '2025-07-17', '06:26:33', '16:03:23', 0, 0),
(845, 3, '2025-07-17', '06:28:04', '17:31:29', 0, 0),
(846, 6, '2025-07-17', '06:33:16', '15:10:45', 0, 0),
(847, 69, '2025-07-17', '06:39:50', '16:00:04', 0, 0),
(848, 67, '2025-07-17', '06:47:24', '19:14:58', 0, 0),
(849, 66, '2025-07-17', '06:48:58', '16:00:21', 0, 0),
(850, 27, '2025-07-17', '06:50:49', '16:04:34', 0, 0),
(851, 20, '2025-07-17', '06:51:28', '16:01:46', 0, 0),
(852, 85, '2025-07-17', '06:51:53', '10:58:45', 0, 0),
(853, 14, '2025-07-17', '06:52:44', '18:00:06', 0, 0),
(854, 60, '2025-07-17', '06:52:48', '19:02:02', 0, 0),
(855, 1, '2025-07-17', '06:52:58', '18:00:48', 0, 0),
(856, 84, '2025-07-17', '06:53:29', '16:00:55', 0, 0),
(857, 57, '2025-07-17', '06:53:35', '18:00:10', 0, 0),
(858, 4, '2025-07-17', '06:53:48', '18:00:13', 0, 0),
(859, 1004, '2025-07-17', '06:53:52', '16:25:28', 0, 0),
(860, 9, '2025-07-17', '06:53:55', '16:00:26', 0, 0),
(861, 10, '2025-07-17', '06:54:23', '16:13:02', 0, 0),
(862, 53, '2025-07-17', '06:54:32', '18:00:23', 0, 0),
(863, 63, '2025-07-17', '06:54:40', '16:00:24', 0, 0),
(864, 5, '2025-07-17', '06:54:56', '18:06:00', 0, 0),
(865, 1003, '2025-07-17', '06:55:03', '16:25:59', 0, 0),
(866, 82, '2025-07-17', '06:55:03', '16:01:18', 0, 0),
(867, 58, '2025-07-17', '06:56:17', '16:23:31', 0, 0),
(868, 83, '2025-07-17', '06:57:28', '16:00:06', 0, 0),
(869, 17, '2025-07-17', '06:58:01', '16:15:38', 0, 0),
(870, 59, '2025-07-17', '06:58:34', '18:02:51', 0, 0),
(871, 30, '2025-07-17', '06:58:51', '18:00:28', 0, 0),
(872, 31, '2025-07-17', '06:58:53', '18:00:02', 0, 0),
(873, 70, '2025-07-17', '06:59:23', '16:01:20', 0, 0),
(874, 29, '2025-07-17', '06:59:36', '18:00:19', 0, 0),
(875, 16, '2025-07-17', '07:00:38', '18:00:02', 0, 0),
(876, 46, '2025-07-17', '07:01:16', '18:10:12', 0, 0),
(877, 22, '2025-07-17', '07:05:54', '18:00:31', 0, 0),
(878, 1007, '2025-07-17', '07:11:01', '16:25:21', 0, 0),
(879, 61, '2025-07-17', '07:12:20', '16:56:33', 0, 0),
(880, 11, '2025-07-17', '07:12:44', '18:02:20', 0, 0),
(881, 34, '2025-07-17', '07:13:09', '18:00:41', 0, 0),
(882, 55, '2025-07-17', '07:20:38', '16:30:24', 0, 0),
(883, 7, '2025-07-17', '07:22:11', '18:23:21', 0, 0),
(884, 1009, '2025-07-17', '07:30:33', '16:55:37', 0, 0),
(885, 72, '2025-07-17', '07:30:55', '16:00:02', 0, 0),
(886, 18, '2025-07-17', '07:31:38', '12:18:35', 0, 0),
(887, 78, '2025-07-17', '07:32:55', '17:00:16', 0, 0),
(888, 56, '2025-07-17', '07:33:09', '19:00:38', 0, 0),
(889, 26, '2025-07-17', '07:36:29', '16:04:56', 0, 0),
(890, 71, '2025-07-17', '07:48:13', '17:15:26', 0, 0),
(891, 75, '2025-07-17', '07:49:56', '16:30:15', 0, 0),
(892, 73, '2025-07-17', '07:50:30', '16:30:03', 0, 0),
(893, 77, '2025-07-17', '07:51:31', '16:30:22', 0, 0),
(894, 21, '2025-07-17', '07:52:52', '19:08:01', 0, 0),
(895, 76, '2025-07-17', '07:53:20', '16:30:14', 0, 0),
(896, 1006, '2025-07-17', '07:00:10', '16:26:40', 0, 0),
(897, 74, '2025-07-17', '07:57:56', '16:30:11', 0, 0),
(898, 65, '2025-07-17', '07:58:36', '17:09:15', 0, 0),
(899, 50, '2025-07-17', '08:02:50', '16:32:09', 0, 0),
(900, 12, '2025-07-17', '08:13:07', '16:30:21', 0, 0),
(901, 62, '2025-07-17', '08:22:50', '18:09:09', 0, 0),
(902, 52, '2025-07-17', '08:30:39', '18:08:55', 0, 0),
(903, 28, '2025-07-17', '08:48:02', '18:00:10', 0, 0),
(904, 43, '2025-07-17', '09:39:18', '19:00:45', 0, 0),
(905, 1005, '2025-07-17', '09:45:10', NULL, 0, 0),
(906, 37, '2025-07-17', '09:53:51', '21:01:36', 0, 0),
(907, 47, '2025-07-17', '11:06:40', NULL, 0, 0),
(908, 35, '2025-07-17', '12:25:05', '18:25:02', 0, 0),
(909, 79, '2025-07-17', '12:49:24', '22:44:42', 0, 0),
(910, 20, '2025-07-01', NULL, NULL, 0, 0),
(911, 20, '2025-07-02', NULL, NULL, 0, 0),
(912, 20, '2025-07-03', NULL, NULL, 0, 0),
(913, 79, '2025-07-01', NULL, NULL, 0, 0),
(914, 79, '2025-07-02', NULL, NULL, 0, 0),
(915, 79, '2025-07-03', NULL, NULL, 0, 0),
(916, 29, '2025-07-18', '05:47:25', NULL, 0, 0),
(917, 81, '2025-07-18', '05:49:21', NULL, 0, 0),
(918, 86, '2025-07-18', '05:54:02', NULL, 0, 0),
(919, 31, '2025-07-18', '05:55:25', NULL, 0, 0),
(920, 68, '2025-07-18', '05:56:14', NULL, 0, 0),
(921, 21, '2025-07-18', '05:56:34', NULL, 0, 0),
(922, 80, '2025-07-18', '05:57:24', NULL, 0, 0),
(923, 36, '2025-07-18', '06:00:16', '12:18:02', 0, 0),
(924, 49, '2025-07-18', '06:00:45', '11:02:53', 0, 0),
(925, 59, '2025-07-18', '06:05:00', NULL, 0, 0),
(926, 33, '2025-07-18', '06:07:12', NULL, 0, 0),
(927, 20, '2025-07-18', '06:11:18', NULL, 0, 0),
(928, 41, '2025-07-18', '06:13:16', NULL, 0, 0),
(929, 6, '2025-07-18', '06:21:03', NULL, 0, 0),
(930, 28, '2025-07-18', '06:31:35', NULL, 0, 0),
(931, 69, '2025-07-18', '06:34:07', NULL, 0, 0),
(932, 7, '2025-07-18', '06:38:34', NULL, 0, 0),
(933, 84, '2025-07-18', '06:38:51', NULL, 0, 0),
(934, 82, '2025-07-18', '06:42:54', NULL, 0, 0),
(935, 63, '2025-07-18', '06:45:12', NULL, 0, 0),
(936, 85, '2025-07-18', '06:46:07', NULL, 0, 0),
(937, 66, '2025-07-18', '06:47:43', NULL, 0, 0),
(938, 1007, '2025-07-18', '06:50:53', NULL, 0, 0),
(939, 47, '2025-07-18', '06:51:09', NULL, 0, 0),
(940, 83, '2025-07-18', '06:51:19', NULL, 0, 0),
(941, 9, '2025-07-18', '06:51:37', NULL, 0, 0),
(942, 60, '2025-07-18', '06:51:47', NULL, 0, 0),
(943, 14, '2025-07-18', '06:52:12', NULL, 0, 0),
(944, 67, '2025-07-18', '06:52:38', NULL, 0, 0),
(945, 17, '2025-07-18', '06:52:48', NULL, 0, 0),
(946, 16, '2025-07-18', '06:52:54', NULL, 0, 0),
(947, 16, '2025-07-18', '06:52:54', NULL, 0, 0),
(948, 4, '2025-07-18', '06:54:05', NULL, 0, 0),
(949, 10, '2025-07-18', '06:54:11', NULL, 0, 0),
(950, 1, '2025-07-18', '06:54:31', NULL, 0, 0),
(951, 1003, '2025-07-18', '06:54:33', NULL, 0, 0),
(952, 57, '2025-07-18', '06:54:51', NULL, 0, 0),
(953, 1004, '2025-07-18', '06:55:23', NULL, 0, 0),
(954, 27, '2025-07-18', '06:56:57', NULL, 0, 0),
(955, 30, '2025-07-18', '06:58:27', NULL, 0, 0),
(956, 58, '2025-07-18', '06:58:31', NULL, 0, 0),
(957, 61, '2025-07-18', '06:59:57', NULL, 0, 0),
(958, 1006, '2025-07-18', '07:01:59', NULL, 0, 0),
(959, 70, '2025-07-18', '07:02:03', NULL, 0, 0),
(960, 34, '2025-07-18', '07:04:51', NULL, 0, 0),
(961, 46, '2025-07-18', '07:06:29', NULL, 0, 0),
(962, 50, '2025-07-18', '07:07:18', NULL, 0, 0),
(963, 72, '2025-07-18', '07:08:26', NULL, 0, 0),
(964, 11, '2025-07-18', '07:16:06', NULL, 0, 0),
(965, 55, '2025-07-18', '07:20:58', NULL, 0, 0),
(966, 74, '2025-07-18', '07:25:34', NULL, 0, 0),
(967, 77, '2025-07-18', '07:35:33', NULL, 0, 0),
(968, 38, '2025-07-18', '07:36:23', NULL, 0, 0),
(969, 71, '2025-07-18', '07:40:03', NULL, 0, 0),
(970, 26, '2025-07-18', '07:41:08', NULL, 0, 0),
(971, 75, '2025-07-18', '07:49:13', NULL, 0, 0),
(972, 78, '2025-07-18', '07:52:52', NULL, 0, 0),
(973, 65, '2025-07-18', '07:53:20', NULL, 0, 0),
(974, 76, '2025-07-18', '07:54:23', NULL, 0, 0),
(975, 56, '2025-07-18', '07:56:26', NULL, 0, 0),
(976, 12, '2025-07-18', '07:58:47', NULL, 0, 0),
(977, 43, '2025-07-18', '08:05:07', NULL, 0, 0),
(978, 37, '2025-07-18', '08:08:24', NULL, 0, 0),
(979, 79, '2025-07-18', '08:15:51', NULL, 0, 0),
(980, 62, '2025-07-18', '08:24:26', NULL, 0, 0),
(981, 73, '2025-07-18', '08:29:12', NULL, 0, 0),
(982, 52, '2025-07-18', '08:41:19', NULL, 0, 0),
(983, 64, '2025-07-18', NULL, NULL, 0, 0),
(984, 64, '2025-07-18', '09:12:17', NULL, 0, 0),
(985, 64, '2025-07-18', '09:12:25', NULL, 0, 0),
(986, 64, '2025-07-18', '09:12:36', NULL, 0, 0),
(987, 64, '2025-07-18', '09:13:26', NULL, 0, 0),
(988, 64, '2025-07-16', NULL, NULL, 0, 0),
(989, 64, '2025-07-17', NULL, NULL, 0, 0),
(990, 1009, '2025-07-18', '11:42:55', NULL, 0, 0),
(991, 24, '2025-07-18', '14:38:17', NULL, 0, 0);

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
  `day_of_week` enum('Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday') DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `work_schedules`
--

INSERT INTO `work_schedules` (`id`, `name`, `time_in`, `time_out`, `employee_id`, `day_of_week`) VALUES
(3, NULL, '07:30:00', '16:30:00', NULL, NULL),
(4, NULL, '07:00:00', '16:00:00', NULL, NULL),
(5, NULL, '08:00:00', '17:00:00', NULL, NULL),
(6, NULL, '09:00:00', '18:00:00', NULL, NULL),
(7, NULL, '10:00:00', '19:00:00', NULL, NULL),
(8, NULL, '06:00:00', '15:00:00', NULL, NULL),
(9, NULL, '08:00:00', '16:30:00', NULL, NULL),
(10, NULL, '07:40:00', '16:40:00', NULL, NULL);

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
-- Indexes for table `announcement_reactions`
--
ALTER TABLE `announcement_reactions`
  ADD PRIMARY KEY (`reaction_id`),
  ADD UNIQUE KEY `unique_reaction` (`announcement_id`,`employee_id`),
  ADD KEY `employee_id` (`employee_id`);

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
-- Indexes for table `overtime_requests`
--
ALTER TABLE `overtime_requests`
  ADD PRIMARY KEY (`id`),
  ADD KEY `employee_id` (`employee_id`);

--
-- Indexes for table `reactions`
--
ALTER TABLE `reactions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_reaction` (`announcement_id`,`employee_id`,`emoji`),
  ADD KEY `employee_id` (`employee_id`);

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `announcements`
--
ALTER TABLE `announcements`
  MODIFY `announcement_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=76;

--
-- AUTO_INCREMENT for table `announcement_reactions`
--
ALTER TABLE `announcement_reactions`
  MODIFY `reaction_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT for table `approved_overtime_schedule`
--
ALTER TABLE `approved_overtime_schedule`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `comments`
--
ALTER TABLE `comments`
  MODIFY `comment_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=147;

--
-- AUTO_INCREMENT for table `employees`
--
ALTER TABLE `employees`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1021;

--
-- AUTO_INCREMENT for table `employee_checklist`
--
ALTER TABLE `employee_checklist`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=84;

--
-- AUTO_INCREMENT for table `employee_schedules`
--
ALTER TABLE `employee_schedules`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `employee_work_schedule`
--
ALTER TABLE `employee_work_schedule`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `leave_credits`
--
ALTER TABLE `leave_credits`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `leave_requests`
--
ALTER TABLE `leave_requests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=162;

--
-- AUTO_INCREMENT for table `overtime_requests`
--
ALTER TABLE `overtime_requests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `reactions`
--
ALTER TABLE `reactions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=79;

--
-- AUTO_INCREMENT for table `rest_day_overtime_requests`
--
ALTER TABLE `rest_day_overtime_requests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `schedule_change_requests`
--
ALTER TABLE `schedule_change_requests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=139;

--
-- AUTO_INCREMENT for table `schedule_exceptions`
--
ALTER TABLE `schedule_exceptions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `schedule_exception_requests`
--
ALTER TABLE `schedule_exception_requests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `test_sql`
--
ALTER TABLE `test_sql`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `time_adjustment_requests`
--
ALTER TABLE `time_adjustment_requests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=105;

--
-- AUTO_INCREMENT for table `time_logs`
--
ALTER TABLE `time_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=992;

--
-- AUTO_INCREMENT for table `vacation_leaves`
--
ALTER TABLE `vacation_leaves`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `work_schedules`
--
ALTER TABLE `work_schedules`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `announcement_reactions`
--
ALTER TABLE `announcement_reactions`
  ADD CONSTRAINT `announcement_reactions_ibfk_1` FOREIGN KEY (`announcement_id`) REFERENCES `announcements` (`announcement_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `announcement_reactions_ibfk_2` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE;

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
-- Constraints for table `overtime_requests`
--
ALTER TABLE `overtime_requests`
  ADD CONSTRAINT `overtime_requests_ibfk_1` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`);

--
-- Constraints for table `reactions`
--
ALTER TABLE `reactions`
  ADD CONSTRAINT `reactions_ibfk_1` FOREIGN KEY (`announcement_id`) REFERENCES `announcements` (`announcement_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `reactions_ibfk_2` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE;

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
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
