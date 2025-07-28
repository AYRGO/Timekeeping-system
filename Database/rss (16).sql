-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jul 28, 2025 at 03:29 AM
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
  `title` varchar(255) DEFAULT NULL,
  `admin_name` varchar(100) NOT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `image` varchar(255) DEFAULT NULL,
  `deleted` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `announcements`
--

INSERT INTO `announcements` (`announcement_id`, `content`, `title`, `admin_name`, `created_at`, `image`, `deleted`) VALUES
(15, 'test', NULL, 'Admin', '2025-07-01 11:34:49', 'uploads/img_68635759012194.25638175.png', 0),
(23, 'asd', NULL, 'Admin', '2025-07-15 09:49:47', 'uploads/file_6875b3bb9bcd18.90333177.xls', 0),
(24, 'rsd', NULL, 'Admin', '2025-07-15 09:51:41', NULL, 0),
(27, '123213', NULL, 'Admin', '2025-07-15 14:24:10', '[\"uploads\\/file_6875f40a4654a7.39506471.docx\"]', 0),
(28, 'sdsa', NULL, 'Admin', '2025-07-15 14:25:41', '[\"uploads\\/file_6875f46547da33.34601607.xls\"]', 0),
(30, 'test', NULL, 'Admin', '2025-07-16 09:28:59', '[\"uploads\\/file_6877005b8cb9a4.83615042.docx\"]', 0),
(31, '13123', NULL, 'Admin', '2025-07-16 09:29:24', '[\"uploads\\/file_68770074d914f6.82081938.xlsx\"]', 0),
(32, 'test', NULL, 'Admin', '2025-07-16 10:13:32', '[\"uploads\\/file_68770acc313190.25408647.xlsx\"]', 0),
(33, 'tet', NULL, 'Admin', '2025-07-16 10:16:55', '[{\"original\":\"Time_Logs_Report (3).xlsx\",\"stored\":\"uploads\\/file_68770b97b6a3c2.36984329.xlsx\"}]', 0),
(34, 'test', NULL, 'Admin', '2025-07-24 10:33:02', NULL, 0),
(35, 'test', 'HARLEY', 'Admin', '2025-07-25 15:53:28', NULL, 0),
(36, 'If you\'re having difficulty getting to work due to the weather, please inform your supervisor as soon as possible. You may contact them via email, chat, or your usual communication channel. You may also contact the internal team directly using the numbers provided below.\r\n\r\nCedrick Arnigo: 0993-864-2974\r\n\r\nRJ Tolomia: 0919-099-3036\r\n\r\nFollowing the declaration of a State of Calamity in Pampanga, you may contact the numbers listed below for any emergency assistance.\r\n\r\n🚨 Emergency Hotlines\r\n\r\nAngeles City: 0917-851-9581 / 0998-842-7746\r\nApalit: 0998-180-9077\r\nArayat: 0922-906-2071\r\nBacolor: 0966-136-5586\r\nCandaba: 0915-110-6306\r\nCity of San Fernando (CSFP): 0939-936-2423 / 0939-961-4357\r\nFloridablanca: 0962-126-6282\r\nGuagua: 0999-805-0357\r\nLubao: 0917-812-5607\r\nMabalacat City: 0998-999-4357\r\nMacabebe: 0917-608-6038\r\nMagalang: 0917-146-9432\r\nMasantol: 0906-189-3021\r\nMexico: 0970-824-8622 / 0905-918-7574\r\nMinalin: 0926-500-2119 / 0917-137-8081\r\nPorac: 0929-441-6188 / 0953-694-2079\r\nSan Luis 0948-993-8163\r\nSan Simon 0994-245-5764\r\nSasuan 0961-645-6149\r\nSta. Ana 0997-788-4690\r\nSta. Rita – 0960-341-2399\r\nSto. Tomas: 0917-505-9197', '📢 Important Reminder', 'Admin', '2025-07-25 16:05:07', NULL, 0),
(37, 'res', 'test', 'Admin', '2025-07-28 09:23:01', NULL, 0);

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

--
-- Dumping data for table `comments`
--

INSERT INTO `comments` (`comment_id`, `announcement_id`, `employee_id`, `content`, `created_at`, `deleted`) VALUES
(126, 33, 1009, 'test', '2025-07-16 10:38:57', 0),
(127, 33, 1009, 'testtt', '2025-07-17 10:32:59', 0),
(128, 32, 1009, 'testttt', '2025-07-17 10:33:17', 0),
(129, 35, 1009, 'TEST', '2025-07-25 15:59:20', 0);

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
  `profile_picture` varchar(255) DEFAULT NULL,
  `official_sched` int(11) DEFAULT NULL,
  `role` enum('employee','internal') NOT NULL DEFAULT 'employee'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `employees`
--

INSERT INTO `employees` (`id`, `fname`, `lname`, `email`, `personal_email`, `contact`, `position`, `status`, `created_at`, `username`, `password`, `company`, `profile_image`, `profile_picture`, `official_sched`, `role`) VALUES
(1, 'Vincent Kevin', 'Santos', 'Vincent.Antonio@entrygroup.com.au', 'vincentvinz17@gmail.com', '0905 919 2943', 'Student Support - Marking', 'active', '2025-04-29 01:04:12', 'vincent.santos', '1mj4i', 'Entry Education', NULL, 'profile_1_1750742776.png', 10, 'employee'),
(2, 'Shaina Dimayugo', 'Dela Cruz', 'shainadc86@gmail.com', 'shainadc86@gmail.com', '0935 766 5039', 'Student Support Mentoring', 'active', '2025-04-29 01:04:12', 'shaina.dela cruz', 'u6mf3', 'Entry Education', NULL, 'profile_2_1749605234.jpeg', 4, 'employee'),
(3, 'Renalyn Abamo', 'Josafat', 'reny@entrygroup.com.au', 'rajosafat.cca@gmail.com', '0939 245 6150', 'Student Support Mentoring', 'active', '2025-04-29 01:04:12', 'renalyn.josafat', 'za9lv', 'Entry Education', NULL, 'profile_3_1750811272.png', 4, 'employee'),
(4, 'Joel Lusung', 'Alimurong', 'Joel.Alimurong@entrygroup.com.au', 'jhei.el1768@gmail.com', '0966 539 4550', 'Student Support - Marking', 'active', '2025-04-29 01:04:12', 'joel.alimurong', 'gj9af', 'Entry Education', NULL, 'profile_4_1749606383.jpeg', 4, 'employee'),
(5, 'Aizel Santos', 'Castro', 'aizel.castro@entrygroup.com.au', 'aizel.castro01@gmail.com', '0926 215 0722', 'Student Support - Marking', 'active', '2025-04-29 01:04:12', 'aizel.castro', '10kjg', 'Entry Education', NULL, NULL, 4, 'employee'),
(6, 'Reymark Bryan Silvano', 'Colis', 'bryan@entrygroup.com.au', 'reymarkbryancolis@gmail.com', '0927 014 4692', 'Technical Student Support - Team Leader', 'active', '2025-04-29 01:04:12', 'reymark.colis', '8vyia', 'Entry Education', NULL, 'profile_6_1749606888.jpeg', 4, 'employee'),
(7, 'Francis Emmanuel Veloso', 'Fernandez', 'francis@entrygroup.com.au', 'francis2208@gmail.com', '0967 201 4330', 'Student Support - Marking Team Leader', 'active', '2025-04-29 01:04:12', 'francis.fernandez', '6fcrh', 'Entry Education', NULL, NULL, 4, 'employee'),
(8, 'Cedrick Cruz', 'Galgo', 'Cedrick.Galgo@entrygroup.com.au', 'cedrickgalgo@gmail.com', '0966 723 9536', 'IT Support', 'active', '2025-04-29 01:04:12', 'cedrick.galgo', '3fez7', 'Entry Education', NULL, NULL, 4, 'employee'),
(9, 'Shigeru', 'Centina', 'Shigeru.Otsuka@entrygroup.com.au', 'shigeruslayer12345@gmail.com', '0939 286 1648', 'Instructional Designer - Technical Specialist', 'active', '2025-04-29 01:04:12', 'shigeru.centina', 'rxg6z', 'Entry Education', NULL, 'profile_9_1749618682.jpg', 4, 'employee'),
(10, 'Rhegene', 'Ingat Ronquillo', 'reggie@entrygroup.com.au', 'rronquillo0727@gmail.com', '0916 936 6370', 'Technical Student Support', 'active', '2025-04-29 01:04:12', 'rhegene.ronquillo', '1rodf', 'Entry Education', NULL, NULL, 4, 'employee'),
(11, 'Mary Ann', 'Vallejos Soriano', 'mary@entrygroup.com.au', 'habibisoriano@yahoo.com', '0906 255 2990', 'Sales New Student Enquiries', 'active', '2025-04-29 01:04:12', 'mary.soriano', '0bz7u', 'Entry Education', NULL, 'profile_11_1749624970.jpeg', 4, 'employee'),
(12, 'Beverly', 'Taloban Gatbonton', 'beverly.gatbonton@entrygroup.com.au', 'beverlygatbonton29@gmail.com', '0920 403 7997', 'Team Leader Sales - New Student Enquiries', 'active', '2025-04-29 01:04:12', 'beverly.gatbonton', 'pw3zj', 'Entry Education', NULL, NULL, 4, 'employee'),
(13, 'Rogelio', 'Dela Peña Malinao', 'rogelio.malinao@entrygroup.com.au', 'rogelio.malinao@gmail.com', '0976 212 6539', 'Student Support - Marking', 'active', '2025-04-29 01:04:12', 'rogelio.malinao', 'vski1', 'Entry Education', NULL, NULL, 4, 'employee'),
(15, 'Evanel', 'Caacbay Navalon', 'evanel.navalon@entrygroup.com.au', 'evanelnavalon@gmail.com', '0946 882 2198', 'Technical Student Support', 'active', '2025-04-29 01:04:12', 'evanel.navalon', 'movch', 'Entry Education', NULL, NULL, 4, 'employee'),
(16, 'Ian Myco', 'Aguilar', 'ian.aguilar@entrygroup.com.au', 'iammycovital@gmail.com', '0976 198 9787', 'Student Support - Marking', 'active', '2025-04-29 01:04:12', 'ian.aguilar', 'uveib', 'Entry Education', NULL, NULL, 4, 'employee'),
(17, 'Reneeca', 'Villapaña Benalla', 'Reneeca@entrygroup.com.au', 'reneeca.benalla@gmail.com', '0909 204 3758', 'Content Writer & Instructional Designer', 'active', '2025-04-29 01:04:12', 'reneeca.benalla', 'rpycf', 'Entry Education', NULL, NULL, 4, 'employee'),
(18, 'Edith', 'David Mataga', 'Edith@entrygroup.com.au', 'edghie03@gmail.com', '0935 563 9451', 'Sales New Student Enquiries', 'active', '2025-04-29 01:04:12', 'edith.mataga', 'v2w4m', 'Entry Education', NULL, NULL, 4, 'employee'),
(20, 'Alfred Naguit', 'Ocampo', 'Alfred@entrygroup.com.au', 'derfla61@gmail.com', '0963 256 7621', 'Conveyancing Client Support', 'active', '2025-04-29 01:04:12', 'alfred.ocampo', 'xs9nq', 'Entry Education', NULL, NULL, 4, 'employee'),
(21, 'Jennifer', 'Mangitngit', 'jennifer.trinidad@entrygroup.com.au', 'jennifertrinidad0103@gmail.com', '0928 225 5869', 'Student Support - Marking', 'active', '2023-08-07 01:04:12', 'jennifer.trinidad', 'xt0rd', 'Entry Education', NULL, NULL, 4, 'employee'),
(22, 'Sean Justine', 'Francisco Mendoza', 'sean.mendoza@entrygroup.com.au', 'mendozaseanjustine@gmail.com', '0977 019 9064', 'Student Support - Marking', 'active', '2023-08-16 01:04:12', 'sean.mendoza', '71l6y', 'Entry Education', NULL, 'profile_22_1750917016.png', 4, 'employee'),
(24, 'Elritz', 'Tongson Crisanto', 'elritz.crisanto@entrygroup.com.au', 'ritztiong@gmail.com', '0961 289 3349', 'Student Support - Marking', 'active', '2023-08-21 01:04:12', 'elritz.crisanto', '96ewj', 'Entry Education', NULL, NULL, 4, 'employee'),
(25, 'Analiza', 'Taloban Gatbonton', 'analiza.gatbonton@entrygroup.com.au', 'analizagatbonton05@gmail.com', '0961 820 1167', 'Student Support - Marking', 'active', '2023-08-21 01:04:12', 'analiza.gatbonton', 'cjfvk', 'Entry Education', NULL, NULL, 4, 'employee'),
(26, 'Franklin Roos', 'Cinco Pabillano', 'estimating@empirewestelectrical.com.au', 'frankpabillano@gmail.com', '0961 498 0228', 'Electrical Estimator', 'active', '2023-11-03 01:04:12', 'franklin.pabillano', 'd54hj', 'onn one', NULL, NULL, 4, 'employee'),
(27, 'Kristian David', 'Bansil', 'ITsupport@mtunderground.com', 'ian_pudz@icloud.com', '0939 905 0288', 'Web Developer / Admin & IT Support', 'active', '2024-02-12 01:04:12', 'kristian.bansil', 'c9zqn', 'Maintenance Tech', NULL, NULL, 4, 'employee'),
(28, 'Louis Fernand', 'Baluyot Austria', 'louis.austria@entrygroup.com.au', 'louisaustria0@gmail.com', '0998 423 4020', 'Graphic Designer', 'active', '2024-04-04 01:04:12', 'louis.austria', 'r14xu', 'Entry Education', NULL, NULL, 4, 'employee'),
(29, 'Johana Rose', 'Perez Gueco', 'johanarose.gueco@entrygroup.com.au', 'johanagueco@gmail.com', '0906 213 7926', 'Student Support - Marking', 'active', '2024-04-04 01:04:12', 'johana.gueco', 'u1o3l', 'Entry Education', NULL, NULL, 4, 'employee'),
(30, 'Erika', 'Seriosa Pineda', 'erika.seriosa@entrygroup.com.au', 'rickzseriosa@gmail.com', '0926 355 6900', 'Student Support - Marking', 'active', '2024-04-04 01:04:12', 'erika.pineda', 'agsrw', 'Entry Education', NULL, NULL, 4, 'employee'),
(31, 'Jhunel Carlo', 'Traifalgar Samodio', 'jhunelcarlo.samodio@entrygroup.com.au', 'gun.lazuli@gmail.com', '0995 483 5711', 'Student Support - Marking', 'active', '2024-04-04 01:04:12', 'jhunel.samodio', '70twd', 'Entry Education', NULL, NULL, 4, 'employee'),
(33, 'Aldwin John', 'Arceo Lozano', 'aldwinjohn.lozano@entrygroup.com.au', 'imaj.lozano@gmail.com', '0949 369 7174', 'Sales New Student Enquiries', 'active', '2024-04-08 01:04:12', 'aldwin.lozano', 'r2hsw', 'Entry Education', NULL, NULL, 4, 'employee'),
(34, 'Yris Gaelle', 'Parreñas Camerino', 'yyrish@gmail.com', 'yyrish@gmail.com', '0912 937 9482', 'Student Support - Marking', 'active', '2024-05-01 01:04:12', 'yris.camerino', 'vijg7', 'Entry Education', NULL, NULL, 4, 'employee'),
(35, 'Nika', 'Nueva Bacongallo', 'nika.bacongallo@gmail.com', 'nika.bacongallo@gmail.com', '0919 263 0516', 'Student Support - Marking', 'active', '2024-05-20 01:04:12', 'nika.bacongallo', 't80da', 'Entry Education', NULL, NULL, 4, 'employee'),
(36, 'Denver Orlanda', 'Castillano', 'dcstudio.creative@gmail.com', 'dcstudio.creative@gmail.com', '0991 933 2312', 'Draftsman', 'active', '2024-06-10 01:04:12', 'denver.castillano', 'brcio', 'DNA Furniture & Cabinets', NULL, NULL, 4, 'employee'),
(37, 'Marnie', 'Perez Catalogo', 'marniecatalogo99@gmail.com', 'marniecatalogo99@gmail.com', '0905 453 8974', 'Technical Student Support', 'active', '2024-06-10 01:04:12', 'marnie.catalogo', '2p658', 'Entry Education', NULL, NULL, 4, 'employee'),
(38, 'Ryan Rex', 'Patrimonio', 'rexryanpatrimonio@gmail.com', 'rexryanpatrimonio@gmail.com', '0916 189 2527', 'Technical Student Support', 'active', '2024-06-10 01:04:12', 'ryan.patrimonio', 'e2gdf', 'Entry Education', NULL, NULL, 4, 'employee'),
(41, 'Ma. Charisma S.', 'Platero', 'charisma.platero@gmail.com', 'charisma.platero@gmail.com', 'N/A', 'Estimator', 'active', '2024-07-22 01:04:12', 'charisma.platero', 'ia9vr', 'Fratelli Homes', NULL, NULL, 4, 'employee'),
(43, 'Lovelaine', 'Gudoy Celeste', 'lovelaineceleste@yahoo.com', 'lovelaineceleste@yahoo.com', 'N/A', 'Sales New Student Enquiries', 'active', '2024-08-12 01:04:12', 'lovelaine.celeste', '91urk', 'Entry Education', NULL, NULL, 4, 'employee'),
(46, 'Ivy', 'Nuñez', 'ivynunez26@gmail.com', 'ivynunez26@gmail.com', 'N/A', 'Student Support - Marking', 'active', '2024-08-12 01:04:12', 'ivy.nuñez', 'f1hqu', 'Entry Education', NULL, NULL, 4, 'employee'),
(47, 'Glory Ann', 'Garcia Balderas', 'Glory@fratellihomeswa.com.au', 'gloryannbalderas@gmail.com', '0981 107 2866', 'Estimator', 'active', '2024-09-16 01:04:12', 'glory.balderas', '2i8ql', 'Fratelli Homes', NULL, NULL, 4, 'employee'),
(49, 'Julie Anne', 'Guinto Maclang', 'macjulg08@gmail.com', 'macjulg08@gmail.com', 'N/A', 'Student Support - Marking', 'active', '2024-10-14 01:04:12', 'julie.maclang', 'rvlft', 'Entry Education', NULL, NULL, 4, 'employee'),
(50, 'Francis Eugene Aguhayon', 'Bondoc', 'francis.bondoc22@gmail.com', 'francis.bondoc22@gmail.com', '629-683-416-000', 'Draftsman', 'active', '2025-04-29 14:02:24', 'francis.bondoc', 'vby8e', 'Entry Education', NULL, NULL, 4, 'employee'),
(52, 'Althea Tansingco', 'Makabenta', 'document.control@bugardi.com.au', 'atansingcomakabenta@yahoo.com', '165-661-778-000', 'Document Controller', 'active', '2025-04-29 14:02:24', 'althea.makabenta', '6i8to', 'Bugardi Contracting', NULL, NULL, 4, 'employee'),
(53, 'Christian Nioda', 'Mar', 'christianmar673@gmail.com', 'christianmar673@gmail.com', '387-307-553-000', 'Sales New Student Enquiries', 'active', '2025-04-29 14:02:24', 'christian.mar', 'lzmb2', 'Entry Education', NULL, NULL, 4, 'employee'),
(55, 'Jeffry Tuazon', 'Macapagal', 'jeff.macapagal017@gmail.com', 'jeff.macapagal017@gmail.com', '513-015-013-000', 'Operations Administrator', 'active', '2025-04-29 14:02:24', 'jeffry.macapagal', 'nzkyl', 'TRSWA', NULL, NULL, 4, 'employee'),
(56, 'Allen Sobrepeña', 'Capati', 'allen.capati@entrygroup.com.au', 'allen.capati95@gmail.com', '468-497-485-000', 'Student Support - Marking', 'active', '2025-04-29 14:02:24', 'allen.capati', 'n02or', 'Entry Education', NULL, NULL, 4, 'employee'),
(57, 'Angelica Rosario', 'Estanio', 'angelica.estanio@entrygroup.com.au', 'angelica.estanio4@gmail.com', '338-546-988-000', 'Student Support - Marking', 'active', '2025-04-29 14:02:24', 'angelica.estanio', 'qm6fh', 'Entry Education', NULL, NULL, 4, 'employee'),
(58, 'Adonis Del Mundo', 'Jabinal', 'adonis.jabinal@bugardi.com.au', 'donjabinal@gmail.com', '175-092-008-000', 'Project Coordinator', 'active', '2025-04-29 14:02:24', 'adonis.jabinal', 'aoqit', 'Bugardi Contracting', NULL, NULL, 4, 'employee'),
(59, 'Joshwea Mercado', 'Monis', 'joshwea.monis@entrygroup.com.au', 'mjoshwea@gmail.com', '332-760-833-000', 'Student Support - Marking', 'active', '2025-04-29 14:02:24', 'joshwea.monis', 'bjor0', 'Entry Education', NULL, NULL, 4, 'employee'),
(60, 'Janeth Sedon', 'Solayao', 'janeth.solayao@entrygroup.com.au', 'janethsolayao32@gmail.com', 'TO FOLLOW', 'Student Support - Marking', 'active', '2025-04-29 14:02:24', 'janeth.solayao', 'i4yzp', 'Entry Education', NULL, NULL, 4, 'employee'),
(61, 'Ray Jinder Villena', 'Singh', 'rvschk@gmail.com', 'rvschk@gmail.com', '350-760-267-000', 'Executive Assistant', 'active', '2025-04-29 14:02:24', 'rj.singh', 'lpzom', 'Rowland Plumbing & Gas', NULL, NULL, 4, 'employee'),
(62, 'Shirmiley Canlas', 'Quizon', 'shirmiley.quizon@bugardi.com.au', 'shirmiley.quizon@gmail.com', '210-283-638-000', 'Recruitment Mobilization Officer', 'active', '2025-04-29 14:02:24', 'shirmiley.quizon', 'm2g7l', 'Bugardi Contracting', NULL, NULL, 4, 'employee'),
(63, 'Maria Ñina Dizon', 'Dollentes', 'nina.dollentes@entrygroup.com.au', 'marianinadollentes@gmail.com', '337-370-586-000', 'Accountant', 'active', '2025-04-29 14:02:24', 'Nina.dollentes', 'qp4i9', 'Entry Education', NULL, 'profile_63_1751009955.png', 4, 'employee'),
(64, 'Jerzi Chezka Medel', 'Libatique', 'jerzi.libatique@entrygroup.com.au', 'jerzichezkamedel@gmail.com', '396-119-405-000', 'Accountant', 'active', '2025-04-29 14:02:24', 'jerzi.libatique', '9ta18', 'Entry Education', NULL, NULL, 4, 'employee'),
(65, 'Sabando', 'Nuñeza Dou Lester', 'lester.nuneza@bugardi.com.au', 'lesternuneza@gmail.com', '264-711-363-000', 'HSEQ Assistant Manager', 'active', '2025-04-29 14:02:24', 'Lester.nuñeza ', '3407m', 'Entry Education', NULL, NULL, 4, 'employee'),
(66, 'Dionicio', 'Ocampo Godwin', 'tgodbtg04@gmail.com', 'tgodbtg04@gmail.com', '743-363-945-000', 'Tax Accountant', 'active', '2025-04-29 14:02:24', 'godwin.ocampo ', 'udlag', 'Entry Education', NULL, NULL, 4, 'employee'),
(67, 'Apryl Ordonio', 'Pasion', 'apryl.pasion@bugardi.com.au', 'aprylpolicarpio@gmail.com', 'TO FOLLOW', 'Recruitment Mobilization Officer', 'active', '2025-04-29 14:02:24', 'apryl.pasion', 'ovcp0', 'Bugardi Contracting', NULL, NULL, 4, 'employee'),
(68, 'Christine Khlaryss', 'Angeles', 'christinekhlaryss@gmail.com', 'christinekhlaryss@gmail.com', '351-635-569-000', 'Tax Accountant', 'active', '2025-04-29 14:02:24', 'christine.angeles', 'bnl8k', 'Denning', NULL, NULL, 4, 'employee'),
(69, 'Trisha Mae Adriano', 'McGregor', 'trisha_mcgregor@yahoo.com', 'trisha_mcgregor@yahoo.com', '486-881-956-00000', 'Renovation Draftsman', 'active', '2025-04-29 14:02:24', 'trisha.mcgregor', 'biv1r', 'Ridge Renovation', NULL, NULL, 4, 'employee'),
(70, 'John Michael Comprado', 'Briones', 'jamenabriones14@gmail.com', 'jamenabriones14@gmail.com', '620-743-947-000', 'Commercial Estimator', 'active', '2025-04-29 14:02:24', 'jm.briones', 'w1g89', 'TRSWA', NULL, NULL, 4, 'employee'),
(71, 'Precious Zahra Cortez', 'Cabusao', 'zahracortez95@gmail.com', 'zahracortez95@gmail.com', '326-526-766-000', 'Hydraulics Estimator', 'active', '2025-04-29 14:02:24', 'zahra.cabusao', 'dwzgl', 'Leeway Group', NULL, NULL, 4, 'employee'),
(72, 'Milbert ', 'Sambile', 'milbert@millersroofing.com.au', 'milbert.sambile@gmail.com', '', 'Estimator', 'active', '2025-06-03 01:08:23', 'Milbert.Sambile', 'Milbert', 'Miller\'s Roofing', NULL, NULL, 4, 'employee'),
(1002, 'Neil Anthony', 'Costelloe', 'Neil.Costelloe@resourcestaff.com.ph', 'neilcosetelloe@gmail.com', NULL, 'General Manager', 'active', '2024-05-19 16:00:00', 'neil.costelloe', 'ypv9h', 'RSS', NULL, NULL, 4, 'employee'),
(1003, 'Cristina Miranda', 'Pangan', 'Tina.Pangan@resourcestaff.com.ph', 'thine2miranda@gmail.com', '0915 056 1780', 'Executive Assistant to the General Manager', 'active', '2024-03-31 16:00:00', 'cristina.pangan', '85ivt', 'RSS', NULL, NULL, 4, 'employee'),
(1004, 'Rica Joy Viray', 'Tolomia', 'Rica.Tolomia@resourcestaff.com.ph', 'Rica.Tolomia@resourcestaff.com.ph', '0917 389 7962', 'TA/HR Specialist', 'active', '2024-08-11 16:00:00', 'rj.tolomia', 'gojwd', 'RSS', NULL, NULL, 4, 'employee'),
(1005, 'Johsua Torninos', 'Dimla', 'johsua.dimla1986@gmail.com', 'johsua.dimla1986@gmail.com', '0933 430 3081', 'Facilities and Admin Support', 'active', '2024-09-29 16:00:00', 'johsua.dimla', 'r9em0', 'RSS', NULL, NULL, 4, 'employee'),
(1006, 'Cedrick', 'Arnigo', 'IT@resourcestaff.com.ph', 'cedrickarnigo1723@gmail.com', '09938642974', 'IT Support Specialist', 'active', '2025-05-27 23:09:46', 'Cedrick.Arnigo', 'Gr33n$$wRf', 'RSS', NULL, 'profile_1006_1751960992.png', 4, 'internal'),
(1007, 'Peach', 'Herrera', 'herrerafelicci@gmail.com', 'herrerafelicci@gmail.com', '0903323232', 'Admin', 'active', '2025-06-02 06:19:09', 'Peach.Herrera', 'Gh0920', 'RSS', NULL, 'profile_1007.jpg', 4, 'employee'),
(1009, 'Resty James', 'Nazareno', 'rjmanago@gmail.com', 'rjmanago@gmail.com', '09763659773', 'IT Intern', 'active', '2025-06-10 02:48:29', 'Kiras001', 'vosfows12', 'RSS', NULL, 'profile_1009_1752025010.png', 6, 'internal'),
(1013, 'John ', 'Mungcal', 'John.Mungcal@gmail.com', NULL, '123123123', 'IT Intern', 'active', '2025-07-14 03:34:27', 'John.Mungcal', '123456', NULL, NULL, NULL, 4, 'internal'),
(1014, 'SON ', 'GOKU', 'son.goku@gmail.com', NULL, '123123123', 'IT Intern', 'active', '2025-07-18 04:26:40', 'son.goku', '$2y$10$TRebBG0TgyghCEdQ.4BjC.dU3worTEHQ9vC3c.2mu91HWThqoiUiK', NULL, NULL, NULL, 4, 'employee'),
(1015, 'SON ', 'GOKU', 'son.goku@gmail.com', NULL, '123123123', 'IT Intern', 'active', '2025-07-18 04:31:53', 'son.goku', '$2y$10$z6fWn1CU3HqXC5qEME2W7Ogr0SLHhwYOhy5vjuibSONgwQjtBW0wa', NULL, NULL, NULL, 4, 'employee'),
(1016, 'SON ', 'GOKUsss', 'son.goku@gmail.com', NULL, '123123123', 'IT Intern', 'active', '2025-07-18 04:32:02', 'son.goku', '$2y$10$GPGPakmemXqAyzgqXE.j5emenpcnfJtQ62/9tMzUUuqtNLIyj.8Ui', NULL, NULL, NULL, 4, 'employee'),
(1017, 'te', 'GOKU', 'rjmanago2@gmail.com', NULL, '09763659711', 'BOSS', 'active', '2025-07-18 05:24:16', 'kiras002', '$2y$10$iZeaam5H4XSaAkz.pQaWU.QtQdeYqoE642/eOao2Cd6EurlGEsmci', NULL, NULL, NULL, 4, 'employee'),
(1018, 'te3', 'GOKU', 'rjmanago2@gmail.com', NULL, '09763659711', 'BOSS', 'active', '2025-07-18 05:25:35', 'kiras002', '$2y$10$bABxeiO1L9gid2pYzQh84eOjRjnnQgw/7eInLn3lKHkiOAgmjQjQC', NULL, NULL, NULL, 4, 'employee'),
(1019, 'te4', 'GOKU', 'rjmanago2@gmail.com', NULL, '09763659711', 'BOSS', 'active', '2025-07-18 05:26:09', 'kiras002', '$2y$10$pROwdSeN5EYwSEpjhNtxhOOM6pVYZjqZtlq8URAzS2wPDlrlXHHem', NULL, NULL, NULL, 4, 'employee'),
(1020, 'te8', 'GOKU', 'rjmanago2@gmail.com', NULL, '09763659711', 'BOSS', 'active', '2025-07-18 05:26:31', 'kiras002', '$2y$10$z6G4AH/RKue3J.eb.csAmuf4xUEfOJCoQajCdi0njo.xk52RBO/yW', NULL, NULL, NULL, 4, 'employee'),
(1021, 'te8s', 'GOKU', 'rjmanago2@gmail.com', NULL, '09763659711', 'BOSS', 'active', '2025-07-18 05:45:20', 'kiras002', '$2y$10$FwTufaGoOF6XtqZwu0KNE.N0hrODC9hc58c5C/lR/6YAY3pmd1Lea', NULL, NULL, NULL, 4, 'employee'),
(1022, 'te8sas', 'GOKU', 'rjmanago2@gmail.com', NULL, '09763659711', 'BOSS', 'active', '2025-07-18 05:47:49', 'kiras002', '$2y$10$OOYX6QEgDosiOfqDbTdET.hpngxmVx.OqmRp5jIszX50z75LufOxy', NULL, NULL, NULL, 4, 'employee'),
(1023, 'Vincent Kevins', ' Kevins', 'rjmanagosd@gmail.com', NULL, '0976365911', 'IT Intern', 'active', '2025-07-18 05:49:10', 'Vincent.Kevins', '$2y$10$bXGFxLEtYcaS1J5wBiFMauf8fYuLB8qCdt4OW92ZH7/QfvuSMWLC2', NULL, NULL, NULL, 4, 'employee');

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

--
-- Dumping data for table `employee_checklist`
--

INSERT INTO `employee_checklist` (`id`, `employee_id`, `letter_offer`, `employment_contract`, `medical`, `nbi_clearance`, `diploma_tor`, `psa`, `sss`, `tin`, `philhealth`, `coe`, `created_at`, `updated_at`, `pagibig`, `valid_id`, `valid_id_2`, `solo_parent_id`, `employment_adjustment_form`) VALUES
(1, 1, NULL, NULL, NULL, NULL, NULL, 'psa_687ddae327992_adssad.png', NULL, NULL, 'philhealth_687ddbe995bf6_adssad.png', 'coe_687ddb345ee00_Screenshot 2025-06-16 153720.png', '2025-07-14 10:25:11', '2025-07-24 08:06:44', 'pagibig_6875b8e885a6e_0266554465.jpeg', NULL, 'Valid_ID_2_687ddb13a2a48_adssad.png', 'solo_parent_id_687de29d02323_Smiley.svg.png,solo_parent_id_687de29d02a99_0266554465.jpeg,solo_parent_id_687de29d02f48_6a56fe8b12977b94a646525fb5200566.jpg', 'employment_adjustment_form_6881791461140_Satoru_Gojo_arrives_on_the_battlefield_29.webp'),
(2, 1009, 'letter_offer_6875c10ce642a_JYEXpJURGks76oHVBc5cik-1200-80.jpg', NULL, 'medical_6875db736474f_Satoru_Gojo_arrives_on_the_battlefield_29.webp', NULL, NULL, NULL, NULL, 'tin_6875e0d891648_RSS-logo-colour.png', NULL, NULL, '2025-07-14 11:22:29', '2025-07-15 13:02:16', NULL, NULL, NULL, NULL, NULL),
(3, 1013, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-07-14 11:35:32', '2025-07-14 11:35:32', NULL, NULL, NULL, NULL, NULL),
(4, 1006, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-07-18 15:14:51', '2025-07-18 15:14:51', NULL, NULL, NULL, NULL, NULL),
(5, 3, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-07-28 08:37:22', '2025-07-28 08:37:22', NULL, NULL, NULL, NULL, NULL);

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
  `leave_type` varchar(50) NOT NULL,
  `balance` decimal(5,2) DEFAULT NULL,
  `carry_over` float DEFAULT NULL,
  `carried_over` decimal(5,2) DEFAULT NULL,
  `monthly_increment` decimal(5,2) DEFAULT NULL,
  `year` int(11) NOT NULL,
  `updated_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `leave_credits`
--

INSERT INTO `leave_credits` (`id`, `employee_id`, `leave_type`, `balance`, `carry_over`, `carried_over`, `monthly_increment`, `year`, `updated_at`) VALUES
(1, 1, 'sick', 7.56, NULL, NULL, 0.42, 2025, '2025-07-25 12:59:29'),
(2, 1, 'vacation', 15.00, 1.25, NULL, 1.25, 2025, '2025-07-25 12:59:29'),
(3, 1, 'paternity', 22.00, NULL, NULL, 0.00, 2025, '2025-07-25 12:59:29'),
(4, 1, 'maternity', 23.00, NULL, NULL, 0.00, 2025, '2025-07-25 12:59:29'),
(5, 1, 'solo_parent', 12.00, NULL, NULL, 0.00, 2025, '2025-07-25 12:59:29'),
(6, 1, 'halfday', 0.00, NULL, NULL, 0.00, 2025, '2025-07-25 12:59:29'),
(7, 1, 'halfday_sick', 3.00, NULL, NULL, 0.00, 2025, '2025-07-25 12:59:29'),
(8, 1, 'lwop', NULL, NULL, NULL, 0.00, 2025, '2025-07-25 12:59:29'),
(9, 1, 'bereavement', NULL, NULL, NULL, 0.00, 2025, '2025-07-25 12:59:29'),
(10, 2, 'sick', 7.56, NULL, NULL, 0.42, 2025, '2025-07-17 15:05:52'),
(11, 2, 'vacation', 15.00, 1.25, NULL, 1.25, 2025, '2025-07-17 15:05:52'),
(12, 2, 'paternity', NULL, NULL, NULL, NULL, 2025, NULL),
(13, 2, 'maternity', NULL, NULL, NULL, NULL, 2025, NULL),
(14, 2, 'solo_parent', NULL, NULL, NULL, NULL, 2025, NULL),
(15, 2, 'halfday', NULL, NULL, NULL, NULL, 2025, NULL),
(16, 2, 'halfday_sick', NULL, NULL, NULL, NULL, 2025, NULL),
(17, 2, 'lwop', NULL, NULL, NULL, NULL, 2025, NULL),
(18, 2, 'bereavement', NULL, NULL, NULL, NULL, 2025, NULL),
(19, 3, 'sick', 7.56, NULL, NULL, 0.42, 2025, '2025-07-17 15:05:52'),
(20, 3, 'vacation', 15.00, 1.25, NULL, 1.25, 2025, '2025-07-17 15:05:52'),
(21, 3, 'paternity', NULL, NULL, NULL, NULL, 2025, NULL),
(22, 3, 'maternity', NULL, NULL, NULL, NULL, 2025, NULL),
(23, 3, 'solo_parent', NULL, NULL, NULL, NULL, 2025, NULL),
(24, 3, 'halfday', NULL, NULL, NULL, NULL, 2025, NULL),
(25, 3, 'halfday_sick', NULL, NULL, NULL, NULL, 2025, NULL),
(26, 3, 'lwop', NULL, NULL, NULL, NULL, 2025, NULL),
(27, 3, 'bereavement', NULL, NULL, NULL, NULL, 2025, NULL),
(28, 4, 'sick', 7.56, NULL, NULL, 0.42, 2025, '2025-07-17 15:05:52'),
(29, 4, 'vacation', 15.00, 1.25, NULL, 1.25, 2025, '2025-07-17 15:05:52'),
(30, 4, 'paternity', NULL, NULL, NULL, NULL, 2025, NULL),
(31, 4, 'maternity', NULL, NULL, NULL, NULL, 2025, NULL),
(32, 4, 'solo_parent', NULL, NULL, NULL, NULL, 2025, NULL),
(33, 4, 'halfday', NULL, NULL, NULL, NULL, 2025, NULL),
(34, 4, 'halfday_sick', NULL, NULL, NULL, NULL, 2025, NULL),
(35, 4, 'lwop', NULL, NULL, NULL, NULL, 2025, NULL),
(36, 4, 'bereavement', NULL, NULL, NULL, NULL, 2025, NULL),
(37, 5, 'sick', 7.56, NULL, NULL, 0.42, 2025, '2025-07-17 15:05:52'),
(38, 5, 'vacation', 15.00, 1.25, NULL, 1.25, 2025, '2025-07-17 15:05:52'),
(39, 5, 'paternity', NULL, NULL, NULL, NULL, 2025, NULL),
(40, 5, 'maternity', NULL, NULL, NULL, NULL, 2025, NULL),
(41, 5, 'solo_parent', NULL, NULL, NULL, NULL, 2025, NULL),
(42, 5, 'halfday', NULL, NULL, NULL, NULL, 2025, NULL),
(43, 5, 'halfday_sick', NULL, NULL, NULL, NULL, 2025, NULL),
(44, 5, 'lwop', NULL, NULL, NULL, NULL, 2025, NULL),
(45, 5, 'bereavement', NULL, NULL, NULL, NULL, 2025, NULL),
(46, 6, 'sick', 7.56, NULL, NULL, 0.42, 2025, '2025-07-17 15:05:52'),
(47, 6, 'vacation', 15.00, 1.25, NULL, 1.25, 2025, '2025-07-17 15:05:52'),
(48, 6, 'paternity', NULL, NULL, NULL, NULL, 2025, NULL),
(49, 6, 'maternity', NULL, NULL, NULL, NULL, 2025, NULL),
(50, 6, 'solo_parent', NULL, NULL, NULL, NULL, 2025, NULL),
(51, 6, 'halfday', NULL, NULL, NULL, NULL, 2025, NULL),
(52, 6, 'halfday_sick', NULL, NULL, NULL, NULL, 2025, NULL),
(53, 6, 'lwop', NULL, NULL, NULL, NULL, 2025, NULL),
(54, 6, 'bereavement', NULL, NULL, NULL, NULL, 2025, NULL),
(55, 7, 'sick', 7.56, NULL, NULL, 0.42, 2025, '2025-07-17 15:05:52'),
(56, 7, 'vacation', 15.00, 1.25, NULL, 1.25, 2025, '2025-07-17 15:05:52'),
(57, 7, 'paternity', NULL, NULL, NULL, NULL, 2025, NULL),
(58, 7, 'maternity', NULL, NULL, NULL, NULL, 2025, NULL),
(59, 7, 'solo_parent', NULL, NULL, NULL, NULL, 2025, NULL),
(60, 7, 'halfday', NULL, NULL, NULL, NULL, 2025, NULL),
(61, 7, 'halfday_sick', NULL, NULL, NULL, NULL, 2025, NULL),
(62, 7, 'lwop', NULL, NULL, NULL, NULL, 2025, NULL),
(63, 7, 'bereavement', NULL, NULL, NULL, NULL, 2025, NULL),
(64, 8, 'sick', 7.56, NULL, NULL, 0.42, 2025, '2025-07-17 15:05:52'),
(65, 8, 'vacation', 15.00, 1.25, NULL, 1.25, 2025, '2025-07-17 15:05:52'),
(66, 8, 'paternity', NULL, NULL, NULL, NULL, 2025, NULL),
(67, 8, 'maternity', NULL, NULL, NULL, NULL, 2025, NULL),
(68, 8, 'solo_parent', NULL, NULL, NULL, NULL, 2025, NULL),
(69, 8, 'halfday', NULL, NULL, NULL, NULL, 2025, NULL),
(70, 8, 'halfday_sick', NULL, NULL, NULL, NULL, 2025, NULL),
(71, 8, 'lwop', NULL, NULL, NULL, NULL, 2025, NULL),
(72, 8, 'bereavement', NULL, NULL, NULL, NULL, 2025, NULL),
(73, 9, 'sick', 7.56, NULL, NULL, 0.42, 2025, '2025-07-17 15:05:52'),
(74, 9, 'vacation', 15.00, 1.25, NULL, 1.25, 2025, '2025-07-17 15:05:52'),
(75, 9, 'paternity', NULL, NULL, NULL, NULL, 2025, NULL),
(76, 9, 'maternity', NULL, NULL, NULL, NULL, 2025, NULL),
(77, 9, 'solo_parent', NULL, NULL, NULL, NULL, 2025, NULL),
(78, 9, 'halfday', NULL, NULL, NULL, NULL, 2025, NULL),
(79, 9, 'halfday_sick', NULL, NULL, NULL, NULL, 2025, NULL),
(80, 9, 'lwop', NULL, NULL, NULL, NULL, 2025, NULL),
(81, 9, 'bereavement', NULL, NULL, NULL, NULL, 2025, NULL),
(82, 10, 'sick', 7.56, NULL, NULL, 0.42, 2025, '2025-07-17 15:05:52'),
(83, 10, 'vacation', 15.00, 1.25, NULL, 1.25, 2025, '2025-07-17 15:05:52'),
(84, 10, 'paternity', NULL, NULL, NULL, NULL, 2025, NULL),
(85, 10, 'maternity', NULL, NULL, NULL, NULL, 2025, NULL),
(86, 10, 'solo_parent', NULL, NULL, NULL, NULL, 2025, NULL),
(87, 10, 'halfday', NULL, NULL, NULL, NULL, 2025, NULL),
(88, 10, 'halfday_sick', NULL, NULL, NULL, NULL, 2025, NULL),
(89, 10, 'lwop', NULL, NULL, NULL, NULL, 2025, NULL),
(90, 10, 'bereavement', NULL, NULL, NULL, NULL, 2025, NULL),
(91, 11, 'sick', 7.56, NULL, NULL, 0.42, 2025, '2025-07-17 15:05:52'),
(92, 11, 'vacation', 15.00, 1.25, NULL, 1.25, 2025, '2025-07-17 15:05:52'),
(93, 11, 'paternity', NULL, NULL, NULL, NULL, 2025, NULL),
(94, 11, 'maternity', NULL, NULL, NULL, NULL, 2025, NULL),
(95, 11, 'solo_parent', NULL, NULL, NULL, NULL, 2025, NULL),
(96, 11, 'halfday', NULL, NULL, NULL, NULL, 2025, NULL),
(97, 11, 'halfday_sick', NULL, NULL, NULL, NULL, 2025, NULL),
(98, 11, 'lwop', NULL, NULL, NULL, NULL, 2025, NULL),
(99, 11, 'bereavement', NULL, NULL, NULL, NULL, 2025, NULL),
(100, 12, 'sick', 7.56, NULL, NULL, 0.42, 2025, '2025-07-17 15:05:52'),
(101, 12, 'vacation', 15.00, 1.25, NULL, 1.25, 2025, '2025-07-17 15:05:52'),
(102, 12, 'paternity', NULL, NULL, NULL, NULL, 2025, NULL),
(103, 12, 'maternity', NULL, NULL, NULL, NULL, 2025, NULL),
(104, 12, 'solo_parent', NULL, NULL, NULL, NULL, 2025, NULL),
(105, 12, 'halfday', NULL, NULL, NULL, NULL, 2025, NULL),
(106, 12, 'halfday_sick', NULL, NULL, NULL, NULL, 2025, NULL),
(107, 12, 'lwop', NULL, NULL, NULL, NULL, 2025, NULL),
(108, 12, 'bereavement', NULL, NULL, NULL, NULL, 2025, NULL),
(109, 13, 'sick', 7.56, NULL, NULL, 0.42, 2025, '2025-07-17 15:05:52'),
(110, 13, 'vacation', 15.00, 1.25, NULL, 1.25, 2025, '2025-07-17 15:05:52'),
(111, 13, 'paternity', NULL, NULL, NULL, NULL, 2025, NULL),
(112, 13, 'maternity', NULL, NULL, NULL, NULL, 2025, NULL),
(113, 13, 'solo_parent', NULL, NULL, NULL, NULL, 2025, NULL),
(114, 13, 'halfday', NULL, NULL, NULL, NULL, 2025, NULL),
(115, 13, 'halfday_sick', NULL, NULL, NULL, NULL, 2025, NULL),
(116, 13, 'lwop', NULL, NULL, NULL, NULL, 2025, NULL),
(117, 13, 'bereavement', NULL, NULL, NULL, NULL, 2025, NULL),
(118, 15, 'sick', 7.56, NULL, NULL, 0.42, 2025, '2025-07-17 15:05:52'),
(119, 15, 'vacation', 15.00, 1.25, NULL, 1.25, 2025, '2025-07-17 15:05:52'),
(120, 15, 'paternity', NULL, NULL, NULL, NULL, 2025, NULL),
(121, 15, 'maternity', NULL, NULL, NULL, NULL, 2025, NULL),
(122, 15, 'solo_parent', NULL, NULL, NULL, NULL, 2025, NULL),
(123, 15, 'halfday', NULL, NULL, NULL, NULL, 2025, NULL),
(124, 15, 'halfday_sick', NULL, NULL, NULL, NULL, 2025, NULL),
(125, 15, 'lwop', NULL, NULL, NULL, NULL, 2025, NULL),
(126, 15, 'bereavement', NULL, NULL, NULL, NULL, 2025, NULL),
(127, 16, 'sick', 7.56, NULL, NULL, 0.42, 2025, '2025-07-17 15:05:52'),
(128, 16, 'vacation', 15.00, 1.25, NULL, 1.25, 2025, '2025-07-17 15:05:52'),
(129, 16, 'paternity', NULL, NULL, NULL, NULL, 2025, NULL),
(130, 16, 'maternity', NULL, NULL, NULL, NULL, 2025, NULL),
(131, 16, 'solo_parent', NULL, NULL, NULL, NULL, 2025, NULL),
(132, 16, 'halfday', NULL, NULL, NULL, NULL, 2025, NULL),
(133, 16, 'halfday_sick', NULL, NULL, NULL, NULL, 2025, NULL),
(134, 16, 'lwop', NULL, NULL, NULL, NULL, 2025, NULL),
(135, 16, 'bereavement', NULL, NULL, NULL, NULL, 2025, NULL),
(136, 17, 'sick', 7.56, NULL, NULL, 0.42, 2025, '2025-07-17 15:05:52'),
(137, 17, 'vacation', 15.00, 1.25, NULL, 1.25, 2025, '2025-07-17 15:05:52'),
(138, 17, 'paternity', NULL, NULL, NULL, NULL, 2025, NULL),
(139, 17, 'maternity', NULL, NULL, NULL, NULL, 2025, NULL),
(140, 17, 'solo_parent', NULL, NULL, NULL, NULL, 2025, NULL),
(141, 17, 'halfday', NULL, NULL, NULL, NULL, 2025, NULL),
(142, 17, 'halfday_sick', NULL, NULL, NULL, NULL, 2025, NULL),
(143, 17, 'lwop', NULL, NULL, NULL, NULL, 2025, NULL),
(144, 17, 'bereavement', NULL, NULL, NULL, NULL, 2025, NULL),
(145, 18, 'sick', 7.56, NULL, NULL, 0.42, 2025, '2025-07-17 15:05:52'),
(146, 18, 'vacation', 15.00, 1.25, NULL, 1.25, 2025, '2025-07-17 15:05:52'),
(147, 18, 'paternity', NULL, NULL, NULL, NULL, 2025, NULL),
(148, 18, 'maternity', NULL, NULL, NULL, NULL, 2025, NULL),
(149, 18, 'solo_parent', NULL, NULL, NULL, NULL, 2025, NULL),
(150, 18, 'halfday', NULL, NULL, NULL, NULL, 2025, NULL),
(151, 18, 'halfday_sick', NULL, NULL, NULL, NULL, 2025, NULL),
(152, 18, 'lwop', NULL, NULL, NULL, NULL, 2025, NULL),
(153, 18, 'bereavement', NULL, NULL, NULL, NULL, 2025, NULL),
(154, 20, 'sick', 7.56, NULL, NULL, 0.42, 2025, '2025-07-17 15:05:52'),
(155, 20, 'vacation', 15.00, 1.25, NULL, 1.25, 2025, '2025-07-17 15:05:52'),
(156, 20, 'paternity', NULL, NULL, NULL, NULL, 2025, NULL),
(157, 20, 'maternity', NULL, NULL, NULL, NULL, 2025, NULL),
(158, 20, 'solo_parent', NULL, NULL, NULL, NULL, 2025, NULL),
(159, 20, 'halfday', NULL, NULL, NULL, NULL, 2025, NULL),
(160, 20, 'halfday_sick', NULL, NULL, NULL, NULL, 2025, NULL),
(161, 20, 'lwop', NULL, NULL, NULL, NULL, 2025, NULL),
(162, 20, 'bereavement', NULL, NULL, NULL, NULL, 2025, NULL),
(163, 21, 'sick', 7.56, NULL, NULL, 0.42, 2025, '2025-07-17 15:05:52'),
(164, 21, 'vacation', 15.00, 1.25, NULL, 1.25, 2025, '2025-07-17 15:05:52'),
(165, 21, 'paternity', NULL, NULL, NULL, NULL, 2025, NULL),
(166, 21, 'maternity', NULL, NULL, NULL, NULL, 2025, NULL),
(167, 21, 'solo_parent', NULL, NULL, NULL, NULL, 2025, NULL),
(168, 21, 'halfday', NULL, NULL, NULL, NULL, 2025, NULL),
(169, 21, 'halfday_sick', NULL, NULL, NULL, NULL, 2025, NULL),
(170, 21, 'lwop', NULL, NULL, NULL, NULL, 2025, NULL),
(171, 21, 'bereavement', NULL, NULL, NULL, NULL, 2025, NULL),
(172, 22, 'sick', 7.56, NULL, NULL, 0.42, 2025, '2025-07-17 15:05:52'),
(173, 22, 'vacation', 15.00, 1.25, NULL, 1.25, 2025, '2025-07-17 15:05:52'),
(174, 22, 'paternity', NULL, NULL, NULL, NULL, 2025, NULL),
(175, 22, 'maternity', NULL, NULL, NULL, NULL, 2025, NULL),
(176, 22, 'solo_parent', NULL, NULL, NULL, NULL, 2025, NULL),
(177, 22, 'halfday', NULL, NULL, NULL, NULL, 2025, NULL),
(178, 22, 'halfday_sick', NULL, NULL, NULL, NULL, 2025, NULL),
(179, 22, 'lwop', NULL, NULL, NULL, NULL, 2025, NULL),
(180, 22, 'bereavement', NULL, NULL, NULL, NULL, 2025, NULL),
(181, 24, 'sick', 7.56, NULL, NULL, 0.42, 2025, '2025-07-17 15:05:52'),
(182, 24, 'vacation', 15.00, 1.25, NULL, 1.25, 2025, '2025-07-17 15:05:52'),
(183, 24, 'paternity', NULL, NULL, NULL, NULL, 2025, NULL),
(184, 24, 'maternity', NULL, NULL, NULL, NULL, 2025, NULL),
(185, 24, 'solo_parent', NULL, NULL, NULL, NULL, 2025, NULL),
(186, 24, 'halfday', NULL, NULL, NULL, NULL, 2025, NULL),
(187, 24, 'halfday_sick', NULL, NULL, NULL, NULL, 2025, NULL),
(188, 24, 'lwop', NULL, NULL, NULL, NULL, 2025, NULL),
(189, 24, 'bereavement', NULL, NULL, NULL, NULL, 2025, NULL),
(190, 25, 'sick', 7.56, NULL, NULL, 0.42, 2025, '2025-07-17 15:05:52'),
(191, 25, 'vacation', 15.00, 1.25, NULL, 1.25, 2025, '2025-07-17 15:05:52'),
(192, 25, 'paternity', NULL, NULL, NULL, NULL, 2025, NULL),
(193, 25, 'maternity', NULL, NULL, NULL, NULL, 2025, NULL),
(194, 25, 'solo_parent', NULL, NULL, NULL, NULL, 2025, NULL),
(195, 25, 'halfday', NULL, NULL, NULL, NULL, 2025, NULL),
(196, 25, 'halfday_sick', NULL, NULL, NULL, NULL, 2025, NULL),
(197, 25, 'lwop', NULL, NULL, NULL, NULL, 2025, NULL),
(198, 25, 'bereavement', NULL, NULL, NULL, NULL, 2025, NULL),
(199, 26, 'sick', 7.56, NULL, NULL, 0.42, 2025, '2025-07-17 15:05:52'),
(200, 26, 'vacation', 15.00, 1.25, NULL, 1.25, 2025, '2025-07-17 15:05:52'),
(201, 26, 'paternity', NULL, NULL, NULL, NULL, 2025, NULL),
(202, 26, 'maternity', NULL, NULL, NULL, NULL, 2025, NULL),
(203, 26, 'solo_parent', NULL, NULL, NULL, NULL, 2025, NULL),
(204, 26, 'halfday', NULL, NULL, NULL, NULL, 2025, NULL),
(205, 26, 'halfday_sick', NULL, NULL, NULL, NULL, 2025, NULL),
(206, 26, 'lwop', NULL, NULL, NULL, NULL, 2025, NULL),
(207, 26, 'bereavement', NULL, NULL, NULL, NULL, 2025, NULL),
(208, 27, 'sick', 7.56, NULL, NULL, 0.42, 2025, '2025-07-17 15:05:52'),
(209, 27, 'vacation', 15.00, 1.25, NULL, 1.25, 2025, '2025-07-17 15:05:52'),
(210, 27, 'paternity', NULL, NULL, NULL, NULL, 2025, NULL),
(211, 27, 'maternity', NULL, NULL, NULL, NULL, 2025, NULL),
(212, 27, 'solo_parent', NULL, NULL, NULL, NULL, 2025, NULL),
(213, 27, 'halfday', NULL, NULL, NULL, NULL, 2025, NULL),
(214, 27, 'halfday_sick', NULL, NULL, NULL, NULL, 2025, NULL),
(215, 27, 'lwop', NULL, NULL, NULL, NULL, 2025, NULL),
(216, 27, 'bereavement', NULL, NULL, NULL, NULL, 2025, NULL),
(217, 28, 'sick', 7.56, NULL, NULL, 0.42, 2025, '2025-07-17 15:05:52'),
(218, 28, 'vacation', 15.00, 1.25, NULL, 1.25, 2025, '2025-07-17 15:05:52'),
(219, 28, 'paternity', NULL, NULL, NULL, NULL, 2025, NULL),
(220, 28, 'maternity', NULL, NULL, NULL, NULL, 2025, NULL),
(221, 28, 'solo_parent', NULL, NULL, NULL, NULL, 2025, NULL),
(222, 28, 'halfday', NULL, NULL, NULL, NULL, 2025, NULL),
(223, 28, 'halfday_sick', NULL, NULL, NULL, NULL, 2025, NULL),
(224, 28, 'lwop', NULL, NULL, NULL, NULL, 2025, NULL),
(225, 28, 'bereavement', NULL, NULL, NULL, NULL, 2025, NULL),
(226, 29, 'sick', 7.56, NULL, NULL, 0.42, 2025, '2025-07-17 15:05:52'),
(227, 29, 'vacation', 15.00, 1.25, NULL, 1.25, 2025, '2025-07-17 15:05:52'),
(228, 29, 'paternity', NULL, NULL, NULL, NULL, 2025, NULL),
(229, 29, 'maternity', NULL, NULL, NULL, NULL, 2025, NULL),
(230, 29, 'solo_parent', NULL, NULL, NULL, NULL, 2025, NULL),
(231, 29, 'halfday', NULL, NULL, NULL, NULL, 2025, NULL),
(232, 29, 'halfday_sick', NULL, NULL, NULL, NULL, 2025, NULL),
(233, 29, 'lwop', NULL, NULL, NULL, NULL, 2025, NULL),
(234, 29, 'bereavement', NULL, NULL, NULL, NULL, 2025, NULL),
(235, 30, 'sick', 7.56, NULL, NULL, 0.42, 2025, '2025-07-17 15:05:52'),
(236, 30, 'vacation', 15.00, 1.25, NULL, 1.25, 2025, '2025-07-17 15:05:52'),
(237, 30, 'paternity', NULL, NULL, NULL, NULL, 2025, NULL),
(238, 30, 'maternity', NULL, NULL, NULL, NULL, 2025, NULL),
(239, 30, 'solo_parent', NULL, NULL, NULL, NULL, 2025, NULL),
(240, 30, 'halfday', NULL, NULL, NULL, NULL, 2025, NULL),
(241, 30, 'halfday_sick', NULL, NULL, NULL, NULL, 2025, NULL),
(242, 30, 'lwop', NULL, NULL, NULL, NULL, 2025, NULL),
(243, 30, 'bereavement', NULL, NULL, NULL, NULL, 2025, NULL),
(244, 31, 'sick', 7.56, NULL, NULL, 0.42, 2025, '2025-07-17 15:05:52'),
(245, 31, 'vacation', 15.00, 1.25, NULL, 1.25, 2025, '2025-07-17 15:05:52'),
(246, 31, 'paternity', NULL, NULL, NULL, NULL, 2025, NULL),
(247, 31, 'maternity', NULL, NULL, NULL, NULL, 2025, NULL),
(248, 31, 'solo_parent', NULL, NULL, NULL, NULL, 2025, NULL),
(249, 31, 'halfday', NULL, NULL, NULL, NULL, 2025, NULL),
(250, 31, 'halfday_sick', NULL, NULL, NULL, NULL, 2025, NULL),
(251, 31, 'lwop', NULL, NULL, NULL, NULL, 2025, NULL),
(252, 31, 'bereavement', NULL, NULL, NULL, NULL, 2025, NULL),
(253, 33, 'sick', 7.56, NULL, NULL, 0.42, 2025, '2025-07-17 15:05:52'),
(254, 33, 'vacation', 15.00, 1.25, NULL, 1.25, 2025, '2025-07-17 15:05:52'),
(255, 33, 'paternity', NULL, NULL, NULL, NULL, 2025, NULL),
(256, 33, 'maternity', NULL, NULL, NULL, NULL, 2025, NULL),
(257, 33, 'solo_parent', NULL, NULL, NULL, NULL, 2025, NULL),
(258, 33, 'halfday', NULL, NULL, NULL, NULL, 2025, NULL),
(259, 33, 'halfday_sick', NULL, NULL, NULL, NULL, 2025, NULL),
(260, 33, 'lwop', NULL, NULL, NULL, NULL, 2025, NULL),
(261, 33, 'bereavement', NULL, NULL, NULL, NULL, 2025, NULL),
(262, 34, 'sick', 7.56, NULL, NULL, 0.42, 2025, '2025-07-17 15:05:52'),
(263, 34, 'vacation', 15.00, 1.25, NULL, 1.25, 2025, '2025-07-17 15:05:52'),
(264, 34, 'paternity', NULL, NULL, NULL, NULL, 2025, NULL),
(265, 34, 'maternity', NULL, NULL, NULL, NULL, 2025, NULL),
(266, 34, 'solo_parent', NULL, NULL, NULL, NULL, 2025, NULL),
(267, 34, 'halfday', NULL, NULL, NULL, NULL, 2025, NULL),
(268, 34, 'halfday_sick', NULL, NULL, NULL, NULL, 2025, NULL),
(269, 34, 'lwop', NULL, NULL, NULL, NULL, 2025, NULL),
(270, 34, 'bereavement', NULL, NULL, NULL, NULL, 2025, NULL),
(271, 35, 'sick', 7.56, NULL, NULL, 0.42, 2025, '2025-07-17 15:05:52'),
(272, 35, 'vacation', 15.00, 1.25, NULL, 1.25, 2025, '2025-07-17 15:05:52'),
(273, 35, 'paternity', NULL, NULL, NULL, NULL, 2025, NULL),
(274, 35, 'maternity', NULL, NULL, NULL, NULL, 2025, NULL),
(275, 35, 'solo_parent', NULL, NULL, NULL, NULL, 2025, NULL),
(276, 35, 'halfday', NULL, NULL, NULL, NULL, 2025, NULL),
(277, 35, 'halfday_sick', NULL, NULL, NULL, NULL, 2025, NULL),
(278, 35, 'lwop', NULL, NULL, NULL, NULL, 2025, NULL),
(279, 35, 'bereavement', NULL, NULL, NULL, NULL, 2025, NULL),
(280, 36, 'sick', 7.56, NULL, NULL, 0.42, 2025, '2025-07-17 15:05:52'),
(281, 36, 'vacation', 15.00, 1.25, NULL, 1.25, 2025, '2025-07-17 15:05:52'),
(282, 36, 'paternity', NULL, NULL, NULL, NULL, 2025, NULL),
(283, 36, 'maternity', NULL, NULL, NULL, NULL, 2025, NULL),
(284, 36, 'solo_parent', NULL, NULL, NULL, NULL, 2025, NULL),
(285, 36, 'halfday', NULL, NULL, NULL, NULL, 2025, NULL),
(286, 36, 'halfday_sick', NULL, NULL, NULL, NULL, 2025, NULL),
(287, 36, 'lwop', NULL, NULL, NULL, NULL, 2025, NULL),
(288, 36, 'bereavement', NULL, NULL, NULL, NULL, 2025, NULL),
(289, 37, 'sick', 7.56, NULL, NULL, 0.42, 2025, '2025-07-17 15:05:52'),
(290, 37, 'vacation', 15.00, 1.25, NULL, 1.25, 2025, '2025-07-17 15:05:52'),
(291, 37, 'paternity', NULL, NULL, NULL, NULL, 2025, NULL),
(292, 37, 'maternity', NULL, NULL, NULL, NULL, 2025, NULL),
(293, 37, 'solo_parent', NULL, NULL, NULL, NULL, 2025, NULL),
(294, 37, 'halfday', NULL, NULL, NULL, NULL, 2025, NULL),
(295, 37, 'halfday_sick', NULL, NULL, NULL, NULL, 2025, NULL),
(296, 37, 'lwop', NULL, NULL, NULL, NULL, 2025, NULL),
(297, 37, 'bereavement', NULL, NULL, NULL, NULL, 2025, NULL),
(298, 38, 'sick', 7.56, NULL, NULL, 0.42, 2025, '2025-07-17 15:05:52'),
(299, 38, 'vacation', 15.00, 1.25, NULL, 1.25, 2025, '2025-07-17 15:05:52'),
(300, 38, 'paternity', NULL, NULL, NULL, NULL, 2025, NULL),
(301, 38, 'maternity', NULL, NULL, NULL, NULL, 2025, NULL),
(302, 38, 'solo_parent', NULL, NULL, NULL, NULL, 2025, NULL),
(303, 38, 'halfday', NULL, NULL, NULL, NULL, 2025, NULL),
(304, 38, 'halfday_sick', NULL, NULL, NULL, NULL, 2025, NULL),
(305, 38, 'lwop', NULL, NULL, NULL, NULL, 2025, NULL),
(306, 38, 'bereavement', NULL, NULL, NULL, NULL, 2025, NULL),
(307, 41, 'sick', 7.56, NULL, NULL, 0.42, 2025, '2025-07-17 15:05:52'),
(308, 41, 'vacation', 15.00, 1.25, NULL, 1.25, 2025, '2025-07-17 15:05:52'),
(309, 41, 'paternity', NULL, NULL, NULL, NULL, 2025, NULL),
(310, 41, 'maternity', NULL, NULL, NULL, NULL, 2025, NULL),
(311, 41, 'solo_parent', NULL, NULL, NULL, NULL, 2025, NULL),
(312, 41, 'halfday', NULL, NULL, NULL, NULL, 2025, NULL),
(313, 41, 'halfday_sick', NULL, NULL, NULL, NULL, 2025, NULL),
(314, 41, 'lwop', NULL, NULL, NULL, NULL, 2025, NULL),
(315, 41, 'bereavement', NULL, NULL, NULL, NULL, 2025, NULL),
(316, 43, 'sick', 7.56, NULL, NULL, 0.42, 2025, '2025-07-17 15:05:52'),
(317, 43, 'vacation', 15.00, 1.25, NULL, 1.25, 2025, '2025-07-17 15:05:52'),
(318, 43, 'paternity', NULL, NULL, NULL, NULL, 2025, NULL),
(319, 43, 'maternity', NULL, NULL, NULL, NULL, 2025, NULL),
(320, 43, 'solo_parent', NULL, NULL, NULL, NULL, 2025, NULL),
(321, 43, 'halfday', NULL, NULL, NULL, NULL, 2025, NULL),
(322, 43, 'halfday_sick', NULL, NULL, NULL, NULL, 2025, NULL),
(323, 43, 'lwop', NULL, NULL, NULL, NULL, 2025, NULL),
(324, 43, 'bereavement', NULL, NULL, NULL, NULL, 2025, NULL),
(325, 46, 'sick', 7.56, NULL, NULL, 0.42, 2025, '2025-07-17 15:05:52'),
(326, 46, 'vacation', 15.00, 1.25, NULL, 1.25, 2025, '2025-07-17 15:05:52'),
(327, 46, 'paternity', NULL, NULL, NULL, NULL, 2025, NULL),
(328, 46, 'maternity', NULL, NULL, NULL, NULL, 2025, NULL),
(329, 46, 'solo_parent', NULL, NULL, NULL, NULL, 2025, NULL),
(330, 46, 'halfday', NULL, NULL, NULL, NULL, 2025, NULL),
(331, 46, 'halfday_sick', NULL, NULL, NULL, NULL, 2025, NULL),
(332, 46, 'lwop', NULL, NULL, NULL, NULL, 2025, NULL),
(333, 46, 'bereavement', NULL, NULL, NULL, NULL, 2025, NULL),
(334, 47, 'sick', 7.56, NULL, NULL, 0.42, 2025, '2025-07-17 15:05:52'),
(335, 47, 'vacation', 15.00, 1.25, NULL, 1.25, 2025, '2025-07-17 15:05:52'),
(336, 47, 'paternity', NULL, NULL, NULL, NULL, 2025, NULL),
(337, 47, 'maternity', NULL, NULL, NULL, NULL, 2025, NULL),
(338, 47, 'solo_parent', NULL, NULL, NULL, NULL, 2025, NULL),
(339, 47, 'halfday', NULL, NULL, NULL, NULL, 2025, NULL),
(340, 47, 'halfday_sick', NULL, NULL, NULL, NULL, 2025, NULL),
(341, 47, 'lwop', NULL, NULL, NULL, NULL, 2025, NULL),
(342, 47, 'bereavement', NULL, NULL, NULL, NULL, 2025, NULL),
(343, 49, 'sick', 7.56, NULL, NULL, 0.42, 2025, '2025-07-17 15:05:52'),
(344, 49, 'vacation', 15.00, 1.25, NULL, 1.25, 2025, '2025-07-17 15:05:52'),
(345, 49, 'paternity', NULL, NULL, NULL, NULL, 2025, NULL),
(346, 49, 'maternity', NULL, NULL, NULL, NULL, 2025, NULL),
(347, 49, 'solo_parent', NULL, NULL, NULL, NULL, 2025, NULL),
(348, 49, 'halfday', NULL, NULL, NULL, NULL, 2025, NULL),
(349, 49, 'halfday_sick', NULL, NULL, NULL, NULL, 2025, NULL),
(350, 49, 'lwop', NULL, NULL, NULL, NULL, 2025, NULL),
(351, 49, 'bereavement', NULL, NULL, NULL, NULL, 2025, NULL),
(352, 50, 'sick', 7.56, NULL, NULL, 0.42, 2025, '2025-07-17 15:05:52'),
(353, 50, 'vacation', 15.00, 1.25, NULL, 1.25, 2025, '2025-07-17 15:05:52'),
(354, 50, 'paternity', NULL, NULL, NULL, NULL, 2025, NULL),
(355, 50, 'maternity', NULL, NULL, NULL, NULL, 2025, NULL),
(356, 50, 'solo_parent', NULL, NULL, NULL, NULL, 2025, NULL),
(357, 50, 'halfday', NULL, NULL, NULL, NULL, 2025, NULL),
(358, 50, 'halfday_sick', NULL, NULL, NULL, NULL, 2025, NULL),
(359, 50, 'lwop', NULL, NULL, NULL, NULL, 2025, NULL),
(360, 50, 'bereavement', NULL, NULL, NULL, NULL, 2025, NULL),
(361, 52, 'sick', 7.56, NULL, NULL, 0.42, 2025, '2025-07-17 15:05:52'),
(362, 52, 'vacation', 15.00, 1.25, NULL, 1.25, 2025, '2025-07-17 15:05:52'),
(363, 52, 'paternity', NULL, NULL, NULL, NULL, 2025, NULL),
(364, 52, 'maternity', NULL, NULL, NULL, NULL, 2025, NULL),
(365, 52, 'solo_parent', NULL, NULL, NULL, NULL, 2025, NULL),
(366, 52, 'halfday', NULL, NULL, NULL, NULL, 2025, NULL),
(367, 52, 'halfday_sick', NULL, NULL, NULL, NULL, 2025, NULL),
(368, 52, 'lwop', NULL, NULL, NULL, NULL, 2025, NULL),
(369, 52, 'bereavement', NULL, NULL, NULL, NULL, 2025, NULL),
(370, 53, 'sick', 7.56, NULL, NULL, 0.42, 2025, '2025-07-17 15:05:52'),
(371, 53, 'vacation', 15.00, 1.25, NULL, 1.25, 2025, '2025-07-17 15:05:52'),
(372, 53, 'paternity', NULL, NULL, NULL, NULL, 2025, NULL),
(373, 53, 'maternity', NULL, NULL, NULL, NULL, 2025, NULL),
(374, 53, 'solo_parent', NULL, NULL, NULL, NULL, 2025, NULL),
(375, 53, 'halfday', NULL, NULL, NULL, NULL, 2025, NULL),
(376, 53, 'halfday_sick', NULL, NULL, NULL, NULL, 2025, NULL),
(377, 53, 'lwop', NULL, NULL, NULL, NULL, 2025, NULL),
(378, 53, 'bereavement', NULL, NULL, NULL, NULL, 2025, NULL),
(379, 55, 'sick', 7.56, NULL, NULL, 0.42, 2025, '2025-07-17 15:05:52'),
(380, 55, 'vacation', 15.00, 1.25, NULL, 1.25, 2025, '2025-07-17 15:05:52'),
(381, 55, 'paternity', NULL, NULL, NULL, NULL, 2025, NULL),
(382, 55, 'maternity', NULL, NULL, NULL, NULL, 2025, NULL),
(383, 55, 'solo_parent', NULL, NULL, NULL, NULL, 2025, NULL),
(384, 55, 'halfday', NULL, NULL, NULL, NULL, 2025, NULL),
(385, 55, 'halfday_sick', NULL, NULL, NULL, NULL, 2025, NULL),
(386, 55, 'lwop', NULL, NULL, NULL, NULL, 2025, NULL),
(387, 55, 'bereavement', NULL, NULL, NULL, NULL, 2025, NULL),
(388, 56, 'sick', 7.56, NULL, NULL, 0.42, 2025, '2025-07-17 15:05:52'),
(389, 56, 'vacation', 15.00, 1.25, NULL, 1.25, 2025, '2025-07-17 15:05:52'),
(390, 56, 'paternity', NULL, NULL, NULL, NULL, 2025, NULL),
(391, 56, 'maternity', NULL, NULL, NULL, NULL, 2025, NULL),
(392, 56, 'solo_parent', NULL, NULL, NULL, NULL, 2025, NULL),
(393, 56, 'halfday', NULL, NULL, NULL, NULL, 2025, NULL),
(394, 56, 'halfday_sick', NULL, NULL, NULL, NULL, 2025, NULL),
(395, 56, 'lwop', NULL, NULL, NULL, NULL, 2025, NULL),
(396, 56, 'bereavement', NULL, NULL, NULL, NULL, 2025, NULL),
(397, 57, 'sick', 7.56, NULL, NULL, 0.42, 2025, '2025-07-17 15:05:52'),
(398, 57, 'vacation', 15.00, 1.25, NULL, 1.25, 2025, '2025-07-17 15:05:52'),
(399, 57, 'paternity', NULL, NULL, NULL, NULL, 2025, NULL),
(400, 57, 'maternity', NULL, NULL, NULL, NULL, 2025, NULL),
(401, 57, 'solo_parent', NULL, NULL, NULL, NULL, 2025, NULL),
(402, 57, 'halfday', NULL, NULL, NULL, NULL, 2025, NULL),
(403, 57, 'halfday_sick', NULL, NULL, NULL, NULL, 2025, NULL),
(404, 57, 'lwop', NULL, NULL, NULL, NULL, 2025, NULL),
(405, 57, 'bereavement', NULL, NULL, NULL, NULL, 2025, NULL),
(406, 58, 'sick', 7.56, NULL, NULL, 0.42, 2025, '2025-07-17 15:05:52'),
(407, 58, 'vacation', 15.00, 1.25, NULL, 1.25, 2025, '2025-07-17 15:05:52'),
(408, 58, 'paternity', NULL, NULL, NULL, NULL, 2025, NULL),
(409, 58, 'maternity', NULL, NULL, NULL, NULL, 2025, NULL),
(410, 58, 'solo_parent', NULL, NULL, NULL, NULL, 2025, NULL),
(411, 58, 'halfday', NULL, NULL, NULL, NULL, 2025, NULL),
(412, 58, 'halfday_sick', NULL, NULL, NULL, NULL, 2025, NULL),
(413, 58, 'lwop', NULL, NULL, NULL, NULL, 2025, NULL),
(414, 58, 'bereavement', NULL, NULL, NULL, NULL, 2025, NULL),
(415, 59, 'sick', 7.56, NULL, NULL, 0.42, 2025, '2025-07-17 15:05:52'),
(416, 59, 'vacation', 15.00, 1.25, NULL, 1.25, 2025, '2025-07-17 15:05:52'),
(417, 59, 'paternity', NULL, NULL, NULL, NULL, 2025, NULL),
(418, 59, 'maternity', NULL, NULL, NULL, NULL, 2025, NULL),
(419, 59, 'solo_parent', NULL, NULL, NULL, NULL, 2025, NULL),
(420, 59, 'halfday', NULL, NULL, NULL, NULL, 2025, NULL),
(421, 59, 'halfday_sick', NULL, NULL, NULL, NULL, 2025, NULL),
(422, 59, 'lwop', NULL, NULL, NULL, NULL, 2025, NULL),
(423, 59, 'bereavement', NULL, NULL, NULL, NULL, 2025, NULL),
(424, 60, 'sick', 7.56, NULL, NULL, 0.42, 2025, '2025-07-17 15:05:52'),
(425, 60, 'vacation', 15.00, 1.25, NULL, 1.25, 2025, '2025-07-17 15:05:52'),
(426, 60, 'paternity', NULL, NULL, NULL, NULL, 2025, NULL),
(427, 60, 'maternity', NULL, NULL, NULL, NULL, 2025, NULL),
(428, 60, 'solo_parent', NULL, NULL, NULL, NULL, 2025, NULL),
(429, 60, 'halfday', NULL, NULL, NULL, NULL, 2025, NULL),
(430, 60, 'halfday_sick', NULL, NULL, NULL, NULL, 2025, NULL),
(431, 60, 'lwop', NULL, NULL, NULL, NULL, 2025, NULL),
(432, 60, 'bereavement', NULL, NULL, NULL, NULL, 2025, NULL),
(433, 61, 'sick', 7.56, NULL, NULL, 0.42, 2025, '2025-07-17 15:05:52'),
(434, 61, 'vacation', 15.00, 1.25, NULL, 1.25, 2025, '2025-07-17 15:05:52'),
(435, 61, 'paternity', NULL, NULL, NULL, NULL, 2025, NULL),
(436, 61, 'maternity', NULL, NULL, NULL, NULL, 2025, NULL),
(437, 61, 'solo_parent', NULL, NULL, NULL, NULL, 2025, NULL),
(438, 61, 'halfday', NULL, NULL, NULL, NULL, 2025, NULL),
(439, 61, 'halfday_sick', NULL, NULL, NULL, NULL, 2025, NULL),
(440, 61, 'lwop', NULL, NULL, NULL, NULL, 2025, NULL),
(441, 61, 'bereavement', NULL, NULL, NULL, NULL, 2025, NULL),
(442, 62, 'sick', 7.56, NULL, NULL, 0.42, 2025, '2025-07-17 15:05:52'),
(443, 62, 'vacation', 15.00, 1.25, NULL, 1.25, 2025, '2025-07-17 15:05:52'),
(444, 62, 'paternity', NULL, NULL, NULL, NULL, 2025, NULL),
(445, 62, 'maternity', NULL, NULL, NULL, NULL, 2025, NULL),
(446, 62, 'solo_parent', NULL, NULL, NULL, NULL, 2025, NULL),
(447, 62, 'halfday', NULL, NULL, NULL, NULL, 2025, NULL),
(448, 62, 'halfday_sick', NULL, NULL, NULL, NULL, 2025, NULL),
(449, 62, 'lwop', NULL, NULL, NULL, NULL, 2025, NULL),
(450, 62, 'bereavement', NULL, NULL, NULL, NULL, 2025, NULL),
(451, 63, 'sick', 7.56, NULL, NULL, 0.42, 2025, '2025-07-17 15:05:52'),
(452, 63, 'vacation', 15.00, 1.25, NULL, 1.25, 2025, '2025-07-17 15:05:52'),
(453, 63, 'paternity', NULL, NULL, NULL, NULL, 2025, NULL),
(454, 63, 'maternity', NULL, NULL, NULL, NULL, 2025, NULL),
(455, 63, 'solo_parent', NULL, NULL, NULL, NULL, 2025, NULL),
(456, 63, 'halfday', NULL, NULL, NULL, NULL, 2025, NULL),
(457, 63, 'halfday_sick', NULL, NULL, NULL, NULL, 2025, NULL),
(458, 63, 'lwop', NULL, NULL, NULL, NULL, 2025, NULL),
(459, 63, 'bereavement', NULL, NULL, NULL, NULL, 2025, NULL),
(460, 64, 'sick', 7.56, NULL, NULL, 0.42, 2025, '2025-07-17 15:05:52'),
(461, 64, 'vacation', 15.00, 1.25, NULL, 1.25, 2025, '2025-07-17 15:05:52'),
(462, 64, 'paternity', NULL, NULL, NULL, NULL, 2025, NULL),
(463, 64, 'maternity', NULL, NULL, NULL, NULL, 2025, NULL),
(464, 64, 'solo_parent', NULL, NULL, NULL, NULL, 2025, NULL),
(465, 64, 'halfday', NULL, NULL, NULL, NULL, 2025, NULL),
(466, 64, 'halfday_sick', NULL, NULL, NULL, NULL, 2025, NULL),
(467, 64, 'lwop', NULL, NULL, NULL, NULL, 2025, NULL),
(468, 64, 'bereavement', NULL, NULL, NULL, NULL, 2025, NULL),
(469, 65, 'sick', 7.56, NULL, NULL, 0.42, 2025, '2025-07-17 15:05:52'),
(470, 65, 'vacation', 15.00, 1.25, NULL, 1.25, 2025, '2025-07-17 15:05:52'),
(471, 65, 'paternity', NULL, NULL, NULL, NULL, 2025, NULL),
(472, 65, 'maternity', NULL, NULL, NULL, NULL, 2025, NULL),
(473, 65, 'solo_parent', NULL, NULL, NULL, NULL, 2025, NULL),
(474, 65, 'halfday', NULL, NULL, NULL, NULL, 2025, NULL),
(475, 65, 'halfday_sick', NULL, NULL, NULL, NULL, 2025, NULL),
(476, 65, 'lwop', NULL, NULL, NULL, NULL, 2025, NULL),
(477, 65, 'bereavement', NULL, NULL, NULL, NULL, 2025, NULL),
(478, 66, 'sick', 7.56, NULL, NULL, 0.42, 2025, '2025-07-17 15:05:52'),
(479, 66, 'vacation', 15.00, 1.25, NULL, 1.25, 2025, '2025-07-17 15:05:52'),
(480, 66, 'paternity', NULL, NULL, NULL, NULL, 2025, NULL),
(481, 66, 'maternity', NULL, NULL, NULL, NULL, 2025, NULL),
(482, 66, 'solo_parent', NULL, NULL, NULL, NULL, 2025, NULL),
(483, 66, 'halfday', NULL, NULL, NULL, NULL, 2025, NULL),
(484, 66, 'halfday_sick', NULL, NULL, NULL, NULL, 2025, NULL),
(485, 66, 'lwop', NULL, NULL, NULL, NULL, 2025, NULL),
(486, 66, 'bereavement', NULL, NULL, NULL, NULL, 2025, NULL),
(487, 67, 'sick', 7.56, NULL, NULL, 0.42, 2025, '2025-07-17 15:05:52'),
(488, 67, 'vacation', 15.00, 1.25, NULL, 1.25, 2025, '2025-07-17 15:05:52'),
(489, 67, 'paternity', NULL, NULL, NULL, NULL, 2025, NULL),
(490, 67, 'maternity', NULL, NULL, NULL, NULL, 2025, NULL),
(491, 67, 'solo_parent', NULL, NULL, NULL, NULL, 2025, NULL),
(492, 67, 'halfday', NULL, NULL, NULL, NULL, 2025, NULL),
(493, 67, 'halfday_sick', NULL, NULL, NULL, NULL, 2025, NULL),
(494, 67, 'lwop', NULL, NULL, NULL, NULL, 2025, NULL),
(495, 67, 'bereavement', NULL, NULL, NULL, NULL, 2025, NULL),
(496, 68, 'sick', 7.56, NULL, NULL, 0.42, 2025, '2025-07-17 15:05:52'),
(497, 68, 'vacation', 15.00, 1.25, NULL, 1.25, 2025, '2025-07-17 15:05:52'),
(498, 68, 'paternity', NULL, NULL, NULL, NULL, 2025, NULL),
(499, 68, 'maternity', NULL, NULL, NULL, NULL, 2025, NULL),
(500, 68, 'solo_parent', NULL, NULL, NULL, NULL, 2025, NULL),
(501, 68, 'halfday', NULL, NULL, NULL, NULL, 2025, NULL),
(502, 68, 'halfday_sick', NULL, NULL, NULL, NULL, 2025, NULL),
(503, 68, 'lwop', NULL, NULL, NULL, NULL, 2025, NULL),
(504, 68, 'bereavement', NULL, NULL, NULL, NULL, 2025, NULL),
(505, 69, 'sick', 7.56, NULL, NULL, 0.42, 2025, '2025-07-17 15:05:52'),
(506, 69, 'vacation', 15.00, 1.25, NULL, 1.25, 2025, '2025-07-17 15:05:52'),
(507, 69, 'paternity', NULL, NULL, NULL, NULL, 2025, NULL),
(508, 69, 'maternity', NULL, NULL, NULL, NULL, 2025, NULL),
(509, 69, 'solo_parent', NULL, NULL, NULL, NULL, 2025, NULL),
(510, 69, 'halfday', NULL, NULL, NULL, NULL, 2025, NULL),
(511, 69, 'halfday_sick', NULL, NULL, NULL, NULL, 2025, NULL),
(512, 69, 'lwop', NULL, NULL, NULL, NULL, 2025, NULL),
(513, 69, 'bereavement', NULL, NULL, NULL, NULL, 2025, NULL),
(514, 70, 'sick', 7.56, NULL, NULL, 0.42, 2025, '2025-07-17 15:05:52'),
(515, 70, 'vacation', 15.00, 1.25, NULL, 1.25, 2025, '2025-07-17 15:05:52'),
(516, 70, 'paternity', NULL, NULL, NULL, NULL, 2025, NULL),
(517, 70, 'maternity', NULL, NULL, NULL, NULL, 2025, NULL),
(518, 70, 'solo_parent', NULL, NULL, NULL, NULL, 2025, NULL),
(519, 70, 'halfday', NULL, NULL, NULL, NULL, 2025, NULL),
(520, 70, 'halfday_sick', NULL, NULL, NULL, NULL, 2025, NULL),
(521, 70, 'lwop', NULL, NULL, NULL, NULL, 2025, NULL),
(522, 70, 'bereavement', NULL, NULL, NULL, NULL, 2025, NULL),
(523, 71, 'sick', 7.56, NULL, NULL, 0.42, 2025, '2025-07-17 15:05:52'),
(524, 71, 'vacation', 15.00, 1.25, NULL, 1.25, 2025, '2025-07-17 15:05:52'),
(525, 71, 'paternity', NULL, NULL, NULL, NULL, 2025, NULL),
(526, 71, 'maternity', NULL, NULL, NULL, NULL, 2025, NULL),
(527, 71, 'solo_parent', NULL, NULL, NULL, NULL, 2025, NULL),
(528, 71, 'halfday', NULL, NULL, NULL, NULL, 2025, NULL),
(529, 71, 'halfday_sick', NULL, NULL, NULL, NULL, 2025, NULL),
(530, 71, 'lwop', NULL, NULL, NULL, NULL, 2025, NULL),
(531, 71, 'bereavement', NULL, NULL, NULL, NULL, 2025, NULL),
(532, 72, 'sick', 7.56, NULL, NULL, 0.42, 2025, '2025-07-17 15:05:52'),
(533, 72, 'vacation', 15.00, 1.25, NULL, 1.25, 2025, '2025-07-17 15:05:52'),
(534, 72, 'paternity', NULL, NULL, NULL, NULL, 2025, NULL),
(535, 72, 'maternity', NULL, NULL, NULL, NULL, 2025, NULL),
(536, 72, 'solo_parent', NULL, NULL, NULL, NULL, 2025, NULL),
(537, 72, 'halfday', NULL, NULL, NULL, NULL, 2025, NULL),
(538, 72, 'halfday_sick', NULL, NULL, NULL, NULL, 2025, NULL),
(539, 72, 'lwop', NULL, NULL, NULL, NULL, 2025, NULL),
(540, 72, 'bereavement', NULL, NULL, NULL, NULL, 2025, NULL),
(541, 1002, 'sick', 7.56, NULL, NULL, 0.42, 2025, '2025-07-17 15:05:52'),
(542, 1002, 'vacation', 15.00, 3.25, NULL, 1.25, 2025, '2025-07-17 15:05:52'),
(543, 1002, 'paternity', NULL, NULL, NULL, 0.00, 2025, '2025-07-17 14:50:02'),
(544, 1002, 'maternity', NULL, NULL, NULL, 0.00, 2025, '2025-07-17 14:50:02'),
(545, 1002, 'solo_parent', NULL, NULL, NULL, 0.00, 2025, '2025-07-17 14:50:02'),
(546, 1002, 'halfday', NULL, NULL, NULL, 0.00, 2025, '2025-07-17 14:50:02'),
(547, 1002, 'halfday_sick', NULL, NULL, NULL, 0.00, 2025, '2025-07-17 14:50:02'),
(548, 1002, 'lwop', NULL, NULL, NULL, 0.00, 2025, '2025-07-17 14:50:02'),
(549, 1002, 'bereavement', NULL, NULL, NULL, 0.00, 2025, '2025-07-17 14:50:02'),
(550, 1003, 'sick', 7.56, NULL, NULL, 0.42, 2025, '2025-07-17 15:05:52'),
(551, 1003, 'vacation', 15.00, 1.25, NULL, 1.25, 2025, '2025-07-17 15:05:52'),
(552, 1003, 'paternity', NULL, NULL, NULL, NULL, 2025, NULL),
(553, 1003, 'maternity', NULL, NULL, NULL, NULL, 2025, NULL),
(554, 1003, 'solo_parent', NULL, NULL, NULL, NULL, 2025, NULL),
(555, 1003, 'halfday', NULL, NULL, NULL, NULL, 2025, NULL),
(556, 1003, 'halfday_sick', NULL, NULL, NULL, NULL, 2025, NULL),
(557, 1003, 'lwop', NULL, NULL, NULL, NULL, 2025, NULL),
(558, 1003, 'bereavement', NULL, NULL, NULL, NULL, 2025, NULL),
(559, 1004, 'sick', 7.56, NULL, NULL, 0.42, 2025, '2025-07-17 15:05:52'),
(560, 1004, 'vacation', 15.00, 1.25, NULL, 1.25, 2025, '2025-07-17 15:05:52'),
(561, 1004, 'paternity', NULL, NULL, NULL, NULL, 2025, NULL),
(562, 1004, 'maternity', NULL, NULL, NULL, NULL, 2025, NULL),
(563, 1004, 'solo_parent', NULL, NULL, NULL, NULL, 2025, NULL),
(564, 1004, 'halfday', NULL, NULL, NULL, NULL, 2025, NULL),
(565, 1004, 'halfday_sick', NULL, NULL, NULL, NULL, 2025, NULL),
(566, 1004, 'lwop', NULL, NULL, NULL, NULL, 2025, NULL),
(567, 1004, 'bereavement', NULL, NULL, NULL, NULL, 2025, NULL),
(568, 1005, 'sick', 7.56, NULL, NULL, 0.42, 2025, '2025-07-17 15:05:52'),
(569, 1005, 'vacation', 15.00, 1.25, NULL, 1.25, 2025, '2025-07-17 15:05:52'),
(570, 1005, 'paternity', NULL, NULL, NULL, NULL, 2025, NULL),
(571, 1005, 'maternity', NULL, NULL, NULL, NULL, 2025, NULL),
(572, 1005, 'solo_parent', NULL, NULL, NULL, NULL, 2025, NULL),
(573, 1005, 'halfday', NULL, NULL, NULL, NULL, 2025, NULL),
(574, 1005, 'halfday_sick', NULL, NULL, NULL, NULL, 2025, NULL),
(575, 1005, 'lwop', NULL, NULL, NULL, NULL, 2025, NULL),
(576, 1005, 'bereavement', NULL, NULL, NULL, NULL, 2025, NULL),
(577, 1006, 'sick', 7.56, NULL, NULL, 0.42, 2025, '2025-07-17 15:05:52'),
(578, 1006, 'vacation', 15.00, 1.25, NULL, 1.25, 2025, '2025-07-17 15:05:52'),
(579, 1006, 'paternity', NULL, NULL, NULL, NULL, 2025, NULL),
(580, 1006, 'maternity', NULL, NULL, NULL, NULL, 2025, NULL),
(581, 1006, 'solo_parent', NULL, NULL, NULL, NULL, 2025, NULL),
(582, 1006, 'halfday', NULL, NULL, NULL, NULL, 2025, NULL),
(583, 1006, 'halfday_sick', NULL, NULL, NULL, NULL, 2025, NULL),
(584, 1006, 'lwop', NULL, NULL, NULL, NULL, 2025, NULL),
(585, 1006, 'bereavement', NULL, NULL, NULL, NULL, 2025, NULL),
(586, 1007, 'sick', 7.56, NULL, NULL, 0.42, 2025, '2025-07-17 15:05:52'),
(587, 1007, 'vacation', 15.00, 1.25, NULL, 1.25, 2025, '2025-07-17 15:05:52'),
(588, 1007, 'paternity', NULL, NULL, NULL, NULL, 2025, NULL),
(589, 1007, 'maternity', NULL, NULL, NULL, NULL, 2025, NULL),
(590, 1007, 'solo_parent', NULL, NULL, NULL, NULL, 2025, NULL),
(591, 1007, 'halfday', NULL, NULL, NULL, NULL, 2025, NULL),
(592, 1007, 'halfday_sick', NULL, NULL, NULL, NULL, 2025, NULL),
(593, 1007, 'lwop', NULL, NULL, NULL, NULL, 2025, NULL),
(594, 1007, 'bereavement', NULL, NULL, NULL, NULL, 2025, NULL),
(595, 1009, 'sick', 10.14, NULL, NULL, 0.42, 2025, '2025-07-17 15:21:12'),
(596, 1009, 'vacation', 5.25, 5, NULL, 1.25, 2025, '2025-07-17 15:21:12'),
(597, 1009, 'paternity', 0.00, NULL, NULL, 0.00, 2025, '2025-07-17 15:21:12'),
(598, 1009, 'maternity', 0.00, NULL, NULL, 0.00, 2025, '2025-07-17 15:21:12'),
(599, 1009, 'solo_parent', 3.00, NULL, NULL, 0.00, 2025, '2025-07-17 15:21:12'),
(600, 1009, 'halfday', 4.00, NULL, NULL, 0.00, 2025, '2025-07-17 15:21:12'),
(601, 1009, 'halfday_sick', 1.00, NULL, NULL, 0.00, 2025, '2025-07-17 15:21:12'),
(602, 1009, 'lwop', 3.00, NULL, NULL, 0.00, 2025, '2025-07-17 15:21:12'),
(603, 1009, 'bereavement', 1.00, NULL, NULL, 0.00, 2025, '2025-07-17 15:21:12'),
(604, 1013, 'sick', 7.56, NULL, NULL, 0.42, 2025, '2025-07-17 15:05:52'),
(605, 1013, 'vacation', 15.00, 1.25, NULL, 1.25, 2025, '2025-07-17 15:05:52'),
(606, 1013, 'paternity', NULL, NULL, NULL, NULL, 2025, NULL),
(607, 1013, 'maternity', NULL, NULL, NULL, NULL, 2025, NULL),
(608, 1013, 'solo_parent', NULL, NULL, NULL, NULL, 2025, NULL),
(609, 1013, 'halfday', NULL, NULL, NULL, NULL, 2025, NULL),
(610, 1013, 'halfday_sick', NULL, NULL, NULL, NULL, 2025, NULL),
(611, 1013, 'lwop', NULL, NULL, NULL, NULL, 2025, NULL),
(612, 1013, 'bereavement', NULL, NULL, NULL, NULL, 2025, NULL);

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

--
-- Dumping data for table `leave_requests`
--

INSERT INTO `leave_requests` (`id`, `employee_id`, `leave_type`, `start_date`, `end_date`, `reason`, `status`, `created_at`, `leave_dates`, `attachment_lr`, `notified`, `explanation`, `deleted`) VALUES
(106, 1009, 'solo_parent', '2025-07-07', '2025-07-07', 'test', 'approved', '2025-07-07 02:46:44', '', NULL, 0, NULL, 0),
(107, 1009, '', '2025-07-07', '2025-07-26', 'test', 'pending', '2025-07-07 06:56:46', '', 'lr_686b6fae1f6a2.jpeg', 0, NULL, 0),
(108, 1009, '', '2025-07-08', '2025-07-19', 'SADDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDD', 'pending', '2025-07-08 02:55:01', '', NULL, 0, NULL, 0),
(109, 1009, 'paternity', '2025-07-08', '2025-07-08', 'tesssssssssssssssssssdssssssssssssssssssssssssssssssssssssssssssssssssssssssssssssssssssssssssssssssssssssssssssssssssssssssssssssssssssssssssssssssssssssssssssssssssssssssssssssssssssssssssssssssssssssss', 'pending', '2025-07-08 03:02:55', '', NULL, 0, NULL, 0),
(110, 1009, '', '2025-07-08', '2025-07-08', 'hiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiii', 'pending', '2025-07-08 03:03:29', '', NULL, 0, NULL, 0),
(111, 1009, 'halfday', '2025-07-08', '2025-07-08', 'asdsad', 'approved', '2025-07-08 03:08:36', '', NULL, 1, NULL, 0),
(112, 1009, 'bereavement', '2025-07-08', '2025-07-08', 'asdas', 'pending', '2025-07-08 03:09:19', '', NULL, 0, NULL, 0),
(113, 1009, 'halfday', '2025-07-08', '2025-07-08', 'test', 'approved', '2025-07-08 05:40:54', '', NULL, 1, NULL, 0),
(114, 1009, 'halfday', '2025-07-11', '2025-07-26', 'sdasds', 'pending', '2025-07-08 05:42:13', '', NULL, 0, NULL, 0),
(115, 1006, 'halfday', '2025-07-09', '2025-07-09', 'test', 'pending', '2025-07-09 01:49:06', '', NULL, 0, NULL, 0),
(116, 1006, 'lwop', '2025-07-09', '2025-07-09', 'asdaasda', 'pending', '2025-07-09 01:50:17', '', NULL, 0, NULL, 0),
(117, 1009, 'sick', '2025-07-21', '2025-07-21', 'test', 'approved', '2025-07-21 00:27:55', '', 'lr_687d898bb23b3.jpg', 1, NULL, 0),
(118, 1009, 'sick', '2025-07-21', '2025-07-26', 'tet', 'pending', '2025-07-21 00:38:00', '', 'lr_687d8be807aca.jpg', 0, NULL, 0),
(119, 1009, 'bereavement', '2025-07-26', '2025-07-26', 'test', 'approved', '2025-07-21 00:46:23', '', 'lr_687d8ddf83b80.jpg', 1, NULL, 0),
(120, 1009, 'vacation', '2025-07-24', '2025-07-25', 'TEST', 'pending', '2025-07-24 05:27:57', '', 'lr_6881c45dcd97f.jpg', 0, NULL, 0);

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

--
-- Dumping data for table `overtime_requests`
--

INSERT INTO `overtime_requests` (`id`, `employee_id`, `date`, `start_time`, `end_time`, `duration_hours`, `reason`, `status`, `attachment_ot`, `created_at`, `time_in`, `time_out`, `notified`, `explanation`, `deleted`) VALUES
(10, 3, '2025-07-07', '01:22:12', '01:22:19', 0, 'sad', 'Pending', NULL, '2025-07-07 09:22:21', '07:17:14', '16:17:11', 0, NULL, 0),
(11, 1009, '2025-07-07', '01:46:21', '01:46:27', 0, 'adasd', 'Pending', 'uploads/1751852795_0266554465.jpeg', '2025-07-07 09:46:35', '07:15:13', '16:15:14', 0, NULL, 0),
(13, 20, '2025-07-07', '10:10:09', '11:10:10', 0, 'asd', 'Pending', NULL, '2025-07-07 10:10:14', '07:09:24', '16:09:25', 0, NULL, 0),
(14, 1009, '2025-07-08', '10:23:27', '10:23:29', 0, 'test', 'Approved', NULL, '2025-07-08 10:23:34', '06:58:22', '16:01:23', 1, NULL, 0),
(15, 1006, '2025-07-08', '15:45:49', '15:45:59', 0, 'restyy', 'Approved', 'uploads/1751960776_Smiley.svg.png', '2025-07-08 15:46:16', '07:00:00', '16:00:00', 1, NULL, 0),
(16, 5, '2025-07-11', '10:54:45', '10:54:55', 0, 'asdas', 'Pending', NULL, '2025-07-11 10:55:01', '01:53:52', '10:54:33', 0, NULL, 0);

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

--
-- Dumping data for table `schedule_change_requests`
--

INSERT INTO `schedule_change_requests` (`id`, `employee_id`, `requested_schedule_id`, `reason`, `work_schedule`, `status`, `created_at`, `start_date`, `end_date`, `confirmed_rcs`, `declined_rcs`, `work_schedule_id`, `current_work_schedule_id`, `attachment_scr`, `notified`, `explanation`, `deleted`) VALUES
(101, 1009, NULL, 'test', NULL, 'Approved', '2025-07-25 07:00:50', '2025-07-25', '2025-07-25', NULL, NULL, 8, 6, 'scr_68832ba2caa8e.jpg', 1, NULL, 0),
(102, 1009, NULL, 'test', NULL, 'Pending', '2025-07-25 07:01:42', '2025-07-25', '2025-07-26', NULL, NULL, 10, 8, 'scr_68832bd6b05f5.png', 0, NULL, 0);

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
  `notified` tinyint(1) DEFAULT 0,
  `deleted` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `time_adjustment_requests`
--

INSERT INTO `time_adjustment_requests` (`id`, `employee_id`, `log_date`, `current_time_in`, `current_time_out`, `requested_time_in`, `requested_time_out`, `reason`, `status`, `submitted_at`, `attachment`, `created_at`, `notified`, `deleted`) VALUES
(1, 1009, '2025-07-02', '13:03:46', NULL, '10:27:00', '00:27:00', 'TEST', 'Pending', '2025-07-03 10:27:21', NULL, '2025-07-07 10:31:40', 0, 0),
(2, 1009, '2025-07-03', '10:21:30', NULL, '11:01:00', '00:01:00', 'TEST', 'Pending', '2025-07-03 11:01:53', 'uploads/attach_6865f2a1c8c246.69129186.jpeg', '2025-07-07 10:31:40', 0, 0),
(3, 1009, '2025-07-03', '10:21:30', NULL, '11:01:00', '00:01:00', 'TEST', 'Pending', '2025-07-03 11:06:18', 'uploads/attach_6865f3aad2b2b0.13271836.jpeg', '2025-07-07 10:31:40', 0, 0),
(4, 1009, '2025-07-03', '10:21:30', NULL, '11:01:00', '00:01:00', 'TEST', 'Pending', '2025-07-03 12:54:37', 'uploads/attach_68660d0d7f9bf7.17194240.jpeg', '2025-07-07 10:31:40', 0, 0),
(5, 1009, '2025-07-03', '10:21:30', NULL, '11:01:00', '00:01:00', 'TEST', 'Pending', '2025-07-03 12:55:51', 'uploads/attach_68660d57475d81.72712397.jpeg', '2025-07-07 10:31:40', 0, 0),
(6, 1009, '2025-07-03', '07:00:00', '15:00:00', '12:31:00', '14:31:00', 'test | Decline Reason: no po', 'Declined', '2025-07-03 15:32:01', NULL, '2025-07-07 10:31:40', 0, 0),
(7, 20, '2025-07-07', '07:09:24', '16:09:25', '10:15:00', '10:16:00', 'tetst', 'Approved', '2025-07-07 10:15:30', NULL, '2025-07-07 10:31:40', 1, 0),
(8, 1009, '2025-07-07', '07:15:13', '16:15:14', '09:44:00', '17:44:00', 'hii | Decline Reason: bawal rj', 'Declined', '2025-07-07 10:44:28', NULL, '2025-07-07 10:44:28', 1, 0),
(9, 1009, '2025-07-08', '06:58:22', '16:01:23', '10:31:00', '15:32:00', 'nalete', 'Approved', '2025-07-08 08:32:18', NULL, '2025-07-08 08:32:18', 1, 0),
(10, 1009, '2025-07-08', '06:58:22', '16:01:23', '16:06:00', '17:07:00', 'test', 'Pending', '2025-07-08 13:06:57', NULL, '2025-07-08 13:06:57', 0, 0),
(11, 1009, '2025-07-08', '06:58:22', '16:01:23', '07:00:00', '16:00:00', 'late', 'Approved', '2025-07-08 14:48:59', NULL, '2025-07-08 14:48:59', 1, 0),
(12, 1009, '2025-07-02', '13:03:46', NULL, '19:00:00', '16:00:00', 'try', 'Pending', '2025-07-08 15:12:03', NULL, '2025-07-08 15:12:03', 0, 0),
(13, 1009, '2025-07-02', '13:03:46', NULL, '07:14:00', '16:15:00', 'iris', 'Approved', '2025-07-08 15:14:39', NULL, '2025-07-08 15:14:39', 1, 0),
(14, 1009, '2025-07-10', '06:04:31', '15:04:40', '10:47:00', '01:47:00', 'sdasd', 'Pending', '2025-07-10 10:47:51', 'uploads/attach_686f29d7d7c988.55377236.png', '2025-07-10 10:47:51', 0, 0),
(15, 1009, '2025-07-11', '07:59:44', NULL, '07:59:00', '16:00:00', 'test | Decline Reason: asd', 'Declined', '2025-07-11 08:00:24', 'uploads/attach_68705418b1db90.86569623.png', '2025-07-11 08:00:24', 1, 0),
(16, 1009, '2025-07-11', '07:59:44', NULL, '07:08:00', '16:04:00', 'test', 'Pending', '2025-07-11 08:09:13', 'uploads/attach_687056297c51a7.19618068.png', '2025-07-11 08:09:13', 0, 0),
(17, 1009, '2025-07-11', '07:59:44', NULL, NULL, '20:10:00', 'atest', 'Pending', '2025-07-11 08:10:59', 'uploads/attach_68705693d2ff73.53024131.png', '2025-07-11 08:10:59', 0, 0),
(18, 1009, '2025-07-11', '07:59:44', NULL, '10:12:00', '22:12:00', 'testt', 'Pending', '2025-07-11 08:12:55', 'uploads/attach_68705707a89472.72113196.png', '2025-07-11 08:12:55', 0, 0),
(19, 1009, '2025-07-11', '07:59:44', NULL, NULL, '20:15:00', 'dragon', 'Pending', '2025-07-11 08:15:52', 'uploads/attach_687057b875e643.60075388.jpg', '2025-07-11 08:15:52', 0, 0),
(20, 1009, '2025-07-11', '07:59:44', NULL, '10:22:00', '16:22:00', 'sdads', 'Pending', '2025-07-11 08:22:32', 'uploads/attach_68705948090a27.16966569.jpg', '2025-07-11 08:22:32', 0, 0),
(21, 1009, '2025-07-11', '07:59:44', NULL, '08:34:00', '20:34:00', 'dragon-test', 'Pending', '2025-07-11 08:35:03', 'time_adjustments/attach_68705c376e25a9.91211883.jpg', '2025-07-11 08:35:03', 0, 0),
(22, 1009, '2025-07-11', '07:59:44', NULL, '08:46:00', '20:46:00', 'testing lng dr', 'Pending', '2025-07-11 08:46:28', 'attach_68705ee486e766.13651844.jpg', '2025-07-11 08:46:28', 0, 0),
(23, 1009, '2025-07-11', '07:59:44', NULL, '08:57:00', '20:57:00', 'test dr', 'Pending', '2025-07-11 08:57:15', 'module/time_adjustments/attach_6870616bccef76.09217913.jpg', '2025-07-11 08:57:15', 0, 0),
(24, 1009, '2025-07-11', '07:59:44', NULL, '08:57:00', '20:57:00', 'test dr', 'Pending', '2025-07-11 09:06:05', 'attach_6870637d870a64.62269674.jpg', '2025-07-11 09:06:05', 0, 0),
(25, 1009, '2025-07-11', '07:59:44', NULL, '07:06:00', '19:06:00', 'testtt -DRRRR', 'Pending', '2025-07-11 09:06:39', 'attach_6870639f70fd02.94601544.jpg', '2025-07-11 09:06:39', 0, 0),
(37, 1009, '2025-07-01', NULL, NULL, '08:44:00', '19:44:00', 'test', 'Approved', '2025-07-17 07:47:31', 'attach_68783a137bc335.55997696.docx', '2025-07-17 07:47:31', 1, 0),
(38, 1009, '2025-07-02', NULL, NULL, '08:50:00', '21:50:00', 'test', 'Approved', '2025-07-17 07:51:01', 'attach_68783ae5e47c01.23673604.docx', '2025-07-17 07:51:01', 1, 0),
(39, 1009, '2025-07-18', '12:22:42', NULL, '07:00:00', '19:00:00', 'test', 'Pending', '2025-07-18 14:00:59', 'attach_6879e31b76df69.26954096.jpg', '2025-07-18 14:00:59', 0, 0),
(40, 1009, '2025-07-18', '12:22:42', NULL, '07:07:00', '16:08:00', 'test', 'Pending', '2025-07-18 14:08:29', 'attach_6879e4dd315323.94196177.docx', '2025-07-18 14:08:29', 0, 0);

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
(12, 67, '2025-06-11', '09:04:44', '09:08:23', 0, 0),
(14, 1, '2025-06-11', '09:21:00', '09:21:10', 0, 0),
(15, 2, '2025-06-11', '09:26:27', '09:26:33', 0, 0),
(16, 4, '2025-06-11', '09:46:28', '09:46:31', 0, 0),
(17, 6, '2025-06-11', '09:54:40', '09:54:45', 0, 0),
(18, 9, '2025-06-11', '10:09:11', '10:09:22', 0, 0),
(19, 11, '2025-06-11', '14:55:51', NULL, 0, 0),
(21, 2, '2025-06-13', '07:24:57', '07:26:04', 0, 0),
(24, 2, '2025-06-16', '09:04:37', '09:04:51', 0, 0),
(25, 3, '2025-06-16', '15:05:05', '15:05:12', 0, 0),
(29, 1, '2025-06-24', '12:56:57', '12:57:03', 0, 0),
(30, 2, '2025-06-24', '15:18:56', '15:19:01', 0, 0),
(32, 3, '2025-06-25', '09:32:48', '09:32:51', 0, 0),
(33, 63, '2025-06-26', '09:09:12', '09:09:30', 0, 0),
(34, 13, '2025-06-26', '09:43:38', '09:43:48', 0, 0),
(35, 11, '2025-06-26', '10:29:54', NULL, 0, 0),
(37, 1006, '2025-06-27', '14:29:24', NULL, 0, 0),
(38, 1002, '2025-06-27', '14:32:08', '14:32:45', 0, 0),
(39, 72, '2025-06-27', '14:44:00', NULL, 0, 0),
(40, 63, '2025-06-27', '15:54:04', '15:54:12', 0, 0),
(41, 5, '2025-06-30', '09:24:45', '09:24:50', 0, 0),
(42, 1006, '2025-06-30', '10:34:45', '11:46:08', 0, 0),
(50, 1006, '2025-07-07', '08:34:39', '18:34:39', 0, 0),
(51, 15, '2025-07-07', '07:12:20', '16:12:21', 0, 0),
(52, 3, '2025-07-07', '07:17:14', '16:17:11', 0, 0),
(53, 4, '2025-07-07', '07:24:34', '16:24:36', 0, 0),
(54, 7, '2025-07-07', '07:59:26', '16:59:27', 0, 0),
(55, 20, '2025-07-07', '07:09:24', '16:09:25', 0, 0),
(56, 11, '2025-07-07', '14:23:29', '14:23:32', 0, 0),
(57, 1009, '2025-07-08', '06:58:22', '16:01:23', 0, 0),
(58, 1006, '2025-07-08', '07:00:00', '16:00:00', 0, 0),
(59, 1009, '2025-07-09', '07:25:13', '09:47:09', 0, 0),
(60, 17, '2025-07-09', '10:09:05', NULL, 0, 0),
(61, 1006, '2025-07-09', '07:00:00', '16:00:00', 0, 0),
(63, 1009, '2025-07-10', '06:04:31', '15:04:40', 0, 0),
(65, 1009, '2025-07-11', '02:00:03', '10:22:05', 0, 0),
(66, 1006, '2025-07-11', '01:00:22', '09:00:09', 0, 0),
(67, 4, '2025-07-11', '01:50:20', '10:50:21', 0, 0),
(68, 5, '2025-07-11', '01:53:52', '10:54:33', 0, 0),
(69, 6, '2025-07-11', '02:01:56', '11:02:40', 0, 0),
(70, 1009, '2025-07-14', '07:18:49', NULL, 0, 0),
(71, 1009, '2025-07-16', '10:18:35', '12:43:35', 0, 0),
(72, 1009, '2025-07-01', NULL, NULL, 0, 0),
(73, 1009, '2025-07-02', NULL, NULL, 0, 0),
(74, 1009, '2025-07-17', '08:06:42', NULL, 0, 0),
(75, 1009, '2025-07-18', '12:22:42', NULL, 0, 0),
(76, 1009, '2025-07-24', '15:03:47', NULL, 0, 0),
(77, 1009, '2025-07-25', '05:50:42', '15:42:12', 0, 0);

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
(4, NULL, '07:00:00', '16:00:00', NULL, NULL),
(5, NULL, '08:00:00', '17:00:00', NULL, NULL),
(6, NULL, '09:00:00', '18:00:00', NULL, NULL),
(7, NULL, '10:00:00', '19:00:00', NULL, NULL),
(8, NULL, '06:00:00', '15:00:00', NULL, NULL),
(9, NULL, '07:30:00', '16:30:00', NULL, NULL),
(10, NULL, '08:30:00', '17:30:00', NULL, NULL);

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
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `post_reactions`
--
ALTER TABLE `post_reactions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_user_post` (`announcement_id`,`employee_id`);

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
  MODIFY `announcement_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=38;

--
-- AUTO_INCREMENT for table `approved_overtime_schedule`
--
ALTER TABLE `approved_overtime_schedule`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `comments`
--
ALTER TABLE `comments`
  MODIFY `comment_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=130;

--
-- AUTO_INCREMENT for table `employees`
--
ALTER TABLE `employees`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1024;

--
-- AUTO_INCREMENT for table `employee_checklist`
--
ALTER TABLE `employee_checklist`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=613;

--
-- AUTO_INCREMENT for table `leave_requests`
--
ALTER TABLE `leave_requests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=121;

--
-- AUTO_INCREMENT for table `overtime_requests`
--
ALTER TABLE `overtime_requests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `post_reactions`
--
ALTER TABLE `post_reactions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `rest_day_overtime_requests`
--
ALTER TABLE `rest_day_overtime_requests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `schedule_change_requests`
--
ALTER TABLE `schedule_change_requests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=103;

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=41;

--
-- AUTO_INCREMENT for table `time_logs`
--
ALTER TABLE `time_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=78;

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
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
