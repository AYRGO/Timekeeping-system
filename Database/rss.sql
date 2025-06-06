-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: May 01, 2025 at 01:34 AM
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
-- Table structure for table `employees`
--

CREATE TABLE `employees` (
  `id` int(11) NOT NULL,
  `fname` varchar(255) DEFAULT NULL,
  `lname` varchar(255) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `contact` varchar(255) DEFAULT NULL,
  `position` varchar(255) DEFAULT NULL,
  `status` enum('active','inactive') DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `username` varchar(100) DEFAULT NULL,
  `password` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `employees`
--

INSERT INTO `employees` (`id`, `fname`, `lname`, `email`, `contact`, `position`, `status`, `created_at`, `username`, `password`) VALUES
(1, 'Vincent Kevin', 'Santos', 'Vincent.Antonio@entrygroup.com.au', '0905 919 2943', 'Student Support - Marking', 'active', '2025-04-29 01:04:12', 'vincent kevin.santos', '1mj4i'),
(2, 'Shaina Dimayugo', 'Dela Cruz', 'shainadc86@gmail.com', '0935 766 5039', 'Student Support Mentoring', 'active', '2025-04-29 01:04:12', 'shaina dimayugo.dela cruz', 'u6mf3'),
(3, 'Renalyn Abamo', 'Josafat', 'reny@entrygroup.com.au', '0939 245 6150', 'Student Support Mentoring', 'active', '2025-04-29 01:04:12', 'renalyn abamo.josafat', 'za9lv'),
(4, 'Joel Lusung', 'Alimurong', 'Joel.Alimurong@entrygroup.com.au', '0966 539 4550', 'Student Support - Marking', 'active', '2025-04-29 01:04:12', 'joel lusung.alimurong', 'gj9af'),
(5, 'Aizel Santos', 'Castro', 'aizel.castro@entrygroup.com.au', '0926 215 0722', 'Student Support - Marking', 'active', '2025-04-29 01:04:12', 'aizel santos.castro', '10kjg'),
(6, 'Reymark Bryan Silvano', 'Colis', 'bryan@entrygroup.com.au', '0927 014 4692', 'Technical Student Support - Team Leader', 'active', '2025-04-29 01:04:12', 'reymark bryan silvano.colis', '8vyia'),
(7, 'Francis Emmanuel Veloso', 'Fernandez', 'francis@entrygroup.com.au', '0967 201 4330', 'Student Support - Marking Team Leader', 'active', '2025-04-29 01:04:12', 'francis emmanuel veloso.fernandez', '6fcrh'),
(8, 'Cedrick Cruz', 'Galgo', 'Cedrick.Galgo@entrygroup.com.au', '0966 723 9536', 'IT Support', 'active', '2025-04-29 01:04:12', 'cedrick cruz.galgo', '3fez7'),
(9, 'Shigeru', 'Centina', 'Shigeru.Otsuka@entrygroup.com.au', '0939 286 1648', 'Instructional Designer - Technical Specialist', 'active', '2025-04-29 01:04:12', 'shigeru.centina', 'rxg6z'),
(10, 'Rhegene', 'Ingat Ronquillo', 'reggie@entrygroup.com.au', '0916 936 6370', 'Technical Student Support', 'active', '2025-04-29 01:04:12', 'rhegene.ingat ronquillo', '1rodf'),
(11, 'Mary Ann', 'Vallejos Soriano', 'mary@entrygroup.com.au', '0906 255 2990', 'Sales New Student Enquiries', 'active', '2025-04-29 01:04:12', 'mary ann.vallejos soriano', '0bz7u'),
(12, 'Beverly', 'Taloban Gatbonton', 'beverly.gatbonton@entrygroup.com.au', '0920 403 7997', 'Team Leader Sales - New Student Enquiries', 'active', '2025-04-29 01:04:12', 'beverly.taloban gatbonton', 'pw3zj'),
(13, 'Rogelio', 'Dela Peña Malinao', 'rogelio.malinao@entrygroup.com.au', '0976 212 6539', 'Student Support - Marking', 'active', '2025-04-29 01:04:12', 'rogelio.dela peña malinao', 'vski1'),
(15, 'Evanel', 'Caacbay Navalon', 'evanel.navalon@entrygroup.com.au', '0946 882 2198', 'Technical Student Support', 'active', '2025-04-29 01:04:12', 'evanel.caacbay navalon', 'movch'),
(16, 'Ian Myco', 'Aguilar', 'ian.aguilar@entrygroup.com.au', '0976 198 9787', 'Student Support - Marking', 'active', '2025-04-29 01:04:12', 'ian myco.aguilar', 'uveib'),
(17, 'Reneeca', 'Villapaña Benalla', 'Reneeca@entrygroup.com.au', '0909 204 3758', 'Content Writer & Instructional Designer', 'active', '2025-04-29 01:04:12', 'reneeca.villapaña benalla', 'rpycf'),
(18, 'Edith', 'David Mataga', 'Edith@entrygroup.com.au', '0935 563 9451', 'Sales New Student Enquiries', 'active', '2025-04-29 01:04:12', 'edith.david mataga', 'v2w4m'),
(20, 'Alfred Naguit', 'Ocampo', 'Alfred@entrygroup.com.au', '0963 256 7621', 'Conveyancing Client Support', 'active', '2025-04-29 01:04:12', 'alfred naguit.ocampo', 'xs9nq'),
(21, 'Jennifer', 'Mangitngit', 'jennifer.trinidad@entrygroup.com.au', '0928 225 5869', 'Student Support - Marking', 'active', '2023-08-07 01:04:12', 'jennifer.mangitngit', 'xt0rd'),
(22, 'Sean Justine', 'Francisco Mendoza', 'sean.mendoza@entrygroup.com.au', '0977 019 9064', 'Student Support - Marking', 'active', '2023-08-16 01:04:12', 'sean justine.francisco mendoza', '71l6y'),
(24, 'Elritz', 'Tongson Crisanto', 'elritz.crisanto@entrygroup.com.au', '0961 289 3349', 'Student Support - Marking', 'active', '2023-08-21 01:04:12', 'elritz.tongson crisanto', '96ewj'),
(25, 'Analiza', 'Taloban Gatbonton', 'analiza.gatbonton@entrygroup.com.au', '0961 820 1167', 'Student Support - Marking', 'active', '2023-08-21 01:04:12', 'analiza.taloban gatbonton', 'cjfvk'),
(26, 'Franklin Roos', 'Cinco Pabillano', 'estimating@empirewestelectrical.com.au', '0961 498 0228', 'Electrical Estimator', 'active', '2023-11-03 01:04:12', 'franklin roos.cinco pabillano', 'd54hj'),
(27, 'Kristian David', 'Bansil', 'ITsupport@mtunderground.com', '0939 905 0288', 'Web Developer / Admin & IT Support', 'active', '2024-02-12 01:04:12', 'kristian david.bansil', 'c9zqn'),
(28, 'Louis Fernand', 'Baluyot Austria', 'louis.austria@entrygroup.com.au', '0998 423 4020', 'Graphic Designer', 'active', '2024-04-04 01:04:12', 'louis fernand.baluyot austria', 'r14xu'),
(29, 'Johana Rose', 'Perez Gueco', 'johanarose.gueco@entrygroup.com.au', '0906 213 7926', 'Student Support - Marking', 'active', '2024-04-04 01:04:12', 'johana rose.perez gueco', 'u1o3l'),
(30, 'Erika', 'Seriosa Pineda', 'erika.seriosa@entrygroup.com.au', '0926 355 6900', 'Student Support - Marking', 'active', '2024-04-04 01:04:12', 'erika.seriosa pineda', 'agsrw'),
(31, 'Jhunel Carlo', 'Traifalgar Samodio', 'jhunelcarlo.samodio@entrygroup.com.au', '0995 483 5711', 'Student Support - Marking', 'active', '2024-04-04 01:04:12', 'jhunel carlo.traifalgar samodio', '70twd'),
(33, 'Aldwin John', 'Arceo Lozano', 'aldwinjohn.lozano@entrygroup.com.au', '0949 369 7174', 'Sales New Student Enquiries', 'active', '2024-04-08 01:04:12', 'aldwin john.arceo lozano', 'r2hsw'),
(34, 'Yris Gaelle', 'Parreñas Camerino', 'yyrish@gmail.com', '0912 937 9482', 'Student Support - Marking', 'active', '2024-05-01 01:04:12', 'yris gaelle.parreñas camerino', 'vijg7'),
(35, 'Nika', 'Nueva Bacongallo', 'nika.bacongallo@gmail.com', '0919 263 0516', 'Student Support - Marking', 'active', '2024-05-20 01:04:12', 'nika.nueva bacongallo', 't80da'),
(36, 'Denver Orlanda', 'Castillano', 'dcstudio.creative@gmail.com', '0991 933 2312', 'Draftsman', 'active', '2024-06-10 01:04:12', 'denver orlanda.castillano', 'brcio'),
(37, 'Marnie', 'Perez Catalogo', 'marniecatalogo99@gmail.com', '0905 453 8974', 'Technical Student Support', 'active', '2024-06-10 01:04:12', 'marnie.perez catalogo', '2p658'),
(38, 'Ryan Rex', 'Patrimonio', 'rexryanpatrimonio@gmail.com', '0916 189 2527', 'Technical Student Support', 'active', '2024-06-10 01:04:12', 'ryan rex.patrimonio', 'e2gdf'),
(41, 'Ma. Charisma S.', 'Platero', 'charisma.platero@gmail.com', 'N/A', 'Estimator', 'active', '2024-07-22 01:04:12', 'ma. charisma s..platero', 'ia9vr'),
(43, 'Lovelaine', 'Gudoy Celeste', 'lovelaineceleste@yahoo.com', 'N/A', 'Sales New Student Enquiries', 'active', '2024-08-12 01:04:12', 'lovelaine.gudoy celeste', '91urk'),
(46, 'Ivy', 'Nuñez', 'ivynunez26@gmail.com', 'N/A', 'Student Support - Marking', 'active', '2024-08-12 01:04:12', 'ivy.nuñez', 'f1hqu'),
(47, 'Glory Ann', 'Garcia Balderas', 'Glory@fratellihomeswa.com.au', '0981 107 2866', 'Estimator', 'active', '2024-09-16 01:04:12', 'glory ann.garcia balderas', '2i8ql'),
(49, 'Julie Anne', 'Guinto Maclang', 'macjulg08@gmail.com', 'N/A', 'Student Support - Marking', 'active', '2024-10-14 01:04:12', 'julie anne.guinto maclang', 'rvlft'),
(50, 'Francis Eugene Aguhayon', 'Bondoc', 'francis.bondoc22@gmail.com', '629-683-416-000', 'Draftsman', 'active', '2025-04-29 14:02:24', 'francis eugene aguhayon.bondoc', 'vby8e'),
(52, 'Althea Tansingco', 'Makabenta', 'document.control@bugardi.com.au', '165-661-778-000', 'Document Controller', 'active', '2025-04-29 14:02:24', 'althea tansingco.makabenta', '6i8to'),
(53, 'Christian Nioda', 'Mar', 'christianmar673@gmail.com', '387-307-553-000', 'Sales New Student Enquiries', 'active', '2025-04-29 14:02:24', 'christian nioda.mar', 'lzmb2'),
(55, 'Jeffry Tuazon', 'Macapagal', 'jeff.macapagal017@gmail.com', '513-015-013-000', 'Operations Administrator', 'active', '2025-04-29 14:02:24', 'jeffry tuazon.macapagal', 'nzkyl'),
(56, 'Allen Sobrepeña', 'Capati', 'allen.capati@entrygroup.com.au', '468-497-485-000', 'Student Support - Marking', 'active', '2025-04-29 14:02:24', 'allen sobrepeña.capati', 'n02or'),
(57, 'Angelica Rosario', 'Estanio', 'angelica.estanio@entrygroup.com.au', '338-546-988-000', 'Student Support - Marking', 'active', '2025-04-29 14:02:24', 'angelica rosario.estanio', 'qm6fh'),
(58, 'Adonis Del Mundo', 'Jabinal', 'adonis.jabinal@bugardi.com.au', '175-092-008-000', 'Project Coordinator', 'active', '2025-04-29 14:02:24', 'adonis del mundo.jabinal', 'aoqit'),
(59, 'Joshwea Mercado', 'Monis', 'joshwea.monis@entrygroup.com.au', '332-760-833-000', 'Student Support - Marking', 'active', '2025-04-29 14:02:24', 'joshwea mercado.monis', 'bjor0'),
(60, 'Janeth Sedon', 'Solayao', 'janeth.solayao@entrygroup.com.au', 'TO FOLLOW', 'Student Support - Marking', 'active', '2025-04-29 14:02:24', 'janeth sedon.solayao', 'i4yzp'),
(61, 'Ray Jinder Villena', 'Singh', 'rvschk@gmail.com', '350-760-267-000', 'Executive Assistant', 'active', '2025-04-29 14:02:24', 'ray jinder villena.singh', 'lpzom'),
(62, 'Shirmiley Canlas', 'Quizon', 'shirmiley.quizon@bugardi.com.au', '210-283-638-000', 'Recruitment Mobilization Officer', 'active', '2025-04-29 14:02:24', 'shirmiley canlas.quizon', 'm2g7l'),
(63, 'Maria Ñina Dizon', 'Dollentes', 'nina.dollentes@entrygroup.com.au', '337-370-586-000', 'Accountant', 'active', '2025-04-29 14:02:24', 'maria Ñina dizon.dollentes', 'qp4i9'),
(64, 'Jerzi Chezka Medel', 'Libatique', 'jerzi.libatique@entrygroup.com.au', '396-119-405-000', 'Accountant', 'active', '2025-04-29 14:02:24', 'jerzi chezka medel.libatique', '9ta18'),
(65, 'Sabando', 'Nuñeza Dou Lester', 'lester.nuneza@bugardi.com.au', '264-711-363-000', 'HSEQ Assistant Manager', 'active', '2025-04-29 14:02:24', 'sabando.nuñeza dou lester', '3407m'),
(66, 'Dionicio', 'Ocampo Godwin', 'tgodbtg04@gmail.com', '743-363-945-000', 'Tax Accountant', 'active', '2025-04-29 14:02:24', 'dionicio.ocampo godwin', 'udlag'),
(67, 'Apryl Ordonio', 'Pasion', 'apryl.pasion@bugardi.com.au', 'TO FOLLOW', 'Recruitment Mobilization Officer', 'active', '2025-04-29 14:02:24', 'apryl ordonio.pasion', 'ovcp0'),
(68, 'Christine Khlaryss', 'Angeles', 'christinekhlaryss@gmail.com', '351-635-569-000', 'Tax Accountant', 'active', '2025-04-29 14:02:24', 'christine khlaryss.angeles', 'bnl8k'),
(69, 'Trisha Mae Adriano', 'McGregor', 'trisha_mcgregor@yahoo.com', '486-881-956-00000', 'Renovation Draftsman', 'active', '2025-04-29 14:02:24', 'trisha mae adriano.mcgregor', 'biv1r'),
(70, 'John Michael Comprado', 'Briones', 'jamenabriones14@gmail.com', '620-743-947-000', 'Commercial Estimator', 'active', '2025-04-29 14:02:24', 'john michael comprado.briones', 'w1g89'),
(71, 'Precious Zahra Cortez', 'Cabusao', 'zahracortez95@gmail.com', '326-526-766-000', 'Hydraulics Estimator', 'active', '2025-04-29 14:02:24', 'precious zahra cortez.cabusao', 'dwzgl'),
(1001, 'Neil Anthony', 'Costello', 'Neil.Costelloe@resourcestaff.com.ph', NULL, 'General Manager', 'active', '2024-05-19 16:00:00', 'neil anthony.costello', 'ypv9h'),
(1002, 'Cristina Miranda', 'Pangan', 'Tina.Pangan@resourcestaff.com.ph', '0915 056 1780', 'Executive Assistant to the General Manager', 'active', '2024-03-31 16:00:00', 'cristina miranda.pangan', '85ivt'),
(1003, 'Rica Joy Viray', 'Tolomia', NULL, '0917 389 7962', 'TA/HR Specialist', 'active', '2024-08-11 16:00:00', 'rica joy viray.tolomia', 'gojwd'),
(1004, 'Johsua Torninos', 'Dimla', NULL, '0933 430 3081', 'Facilities and Admin Support', 'active', '2024-09-29 16:00:00', 'johsua torninos.dimla', 'r9em0');

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

--
-- Dumping data for table `employee_work_schedule`
--

INSERT INTO `employee_work_schedule` (`id`, `employee_id`, `work_schedule_id`, `effective_date`) VALUES
(1, 1, 2, '2025-04-30');

-- --------------------------------------------------------

--
-- Table structure for table `leave_requests`
--

CREATE TABLE `leave_requests` (
  `id` int(11) NOT NULL,
  `employee_id` int(11) DEFAULT NULL,
  `leave_type` enum('VL','SL','Emergency','Other') DEFAULT NULL,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `reason` text DEFAULT NULL,
  `status` enum('pending','approved','rejected') DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `leave_requests`
--

INSERT INTO `leave_requests` (`id`, `employee_id`, `leave_type`, `start_date`, `end_date`, `reason`, `status`, `created_at`) VALUES
(1, 1, '', '2025-05-13', '2025-05-13', 'Test', 'approved', '2025-04-29 12:34:24'),
(2, 1, '', '2025-05-13', '2025-05-13', 'Test', 'approved', '2025-04-29 12:36:21'),
(3, 1, '', '2025-05-13', '2025-05-13', 'Test', 'approved', '2025-04-29 12:36:24'),
(4, 1, '', '2025-05-13', '2025-05-13', 'Test', 'approved', '2025-04-29 12:37:40'),
(5, 2, '', '2025-04-25', '2025-04-25', 'test', 'approved', '2025-04-29 12:39:13'),
(6, 2, '', '2025-04-25', '2025-04-25', 'test', 'approved', '2025-04-29 12:40:32'),
(7, 2, '', '2025-04-01', '2025-04-08', 'test', 'approved', '2025-04-29 12:40:48'),
(8, 2, '', '2025-04-01', '2025-04-08', 'test', 'approved', '2025-04-29 12:41:23'),
(9, 2, 'Emergency', '2025-04-10', '2025-04-10', 'test', 'approved', '2025-04-29 12:42:16'),
(10, 1, '', '2025-05-02', '2025-05-02', 'test', 'rejected', '2025-04-29 12:43:53'),
(11, 1, '', '2025-05-01', '2025-05-01', 'test', 'rejected', '2025-04-29 12:47:54'),
(12, 1, 'VL', '2025-05-01', '2025-05-01', 'test', 'rejected', '2025-04-29 12:50:16');

-- --------------------------------------------------------

--
-- Table structure for table `overtime_requests`
--

CREATE TABLE `overtime_requests` (
  `id` int(11) NOT NULL,
  `employee_id` int(11) DEFAULT NULL,
  `ot_date` date DEFAULT NULL,
  `expected_time_out` time DEFAULT NULL,
  `reason` text DEFAULT NULL,
  `status` enum('pending','approved','rejected') DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `overtime_requests`
--

INSERT INTO `overtime_requests` (`id`, `employee_id`, `ot_date`, `expected_time_out`, `reason`, `status`, `created_at`) VALUES
(1, 1, '2025-04-30', '08:55:00', 'test', 'approved', '2025-04-29 23:55:15'),
(2, 4, '2025-04-30', '09:09:00', 'test', 'rejected', '2025-04-30 00:09:21');

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

--
-- Dumping data for table `rest_day_overtime_requests`
--

INSERT INTO `rest_day_overtime_requests` (`id`, `employee_id`, `rest_day_date`, `expected_time_in`, `expected_time_out`, `reason`, `status`, `created_at`) VALUES
(1, 1, '2025-05-03', '07:00:00', '09:00:00', 'test', 'approved', '2025-04-30 00:17:50'),
(2, 1, '2025-05-03', '12:18:00', '16:18:00', 'test', 'rejected', '2025-04-30 00:18:17'),
(3, 1, '2025-05-03', '12:18:00', '16:18:00', 'test', 'approved', '2025-04-30 00:26:49'),
(4, 1, '2025-04-23', '08:26:00', '08:26:00', 'test\r\n', 'approved', '2025-04-30 00:27:04'),
(5, 1, '2025-04-23', '08:26:00', '08:26:00', 'test\r\n', 'approved', '2025-04-30 00:27:53'),
(6, 1, '2025-04-30', '01:27:00', '01:28:00', '1', 'rejected', '2025-04-30 00:28:03'),
(7, 1, '2025-04-30', '01:27:00', '01:28:00', '1', 'rejected', '2025-04-30 00:28:21'),
(8, 1, '2025-05-01', '10:28:00', '01:28:00', 'test', 'rejected', '2025-04-30 00:28:31');

-- --------------------------------------------------------

--
-- Table structure for table `schedule_change_requests`
--

CREATE TABLE `schedule_change_requests` (
  `id` int(11) NOT NULL,
  `employee_id` int(11) DEFAULT NULL,
  `requested_schedule_id` int(11) DEFAULT NULL,
  `reason` text DEFAULT NULL,
  `status` enum('pending','approved','rejected') DEFAULT NULL,
  `requested_effective_date` date DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `schedule_change_requests`
--

INSERT INTO `schedule_change_requests` (`id`, `employee_id`, `requested_schedule_id`, `reason`, `status`, `requested_effective_date`, `created_at`) VALUES
(4, 1, 1, 'test', 'approved', '2025-04-30', '2025-04-30 02:27:52'),
(5, 1, 2, 'test', 'approved', '2025-04-30', '2025-04-30 02:30:38');

-- --------------------------------------------------------

--
-- Table structure for table `schedule_exceptions`
--

CREATE TABLE `schedule_exceptions` (
  `id` int(11) NOT NULL,
  `employee_id` int(11) DEFAULT NULL,
  `exception_date` date DEFAULT NULL,
  `time_in` time DEFAULT NULL,
  `time_out` time DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `schedule_exceptions`
--

INSERT INTO `schedule_exceptions` (`id`, `employee_id`, `exception_date`, `time_in`, `time_out`) VALUES
(1, 1, '2025-05-03', '07:00:00', '16:43:00');

-- --------------------------------------------------------

--
-- Table structure for table `schedule_exception_requests`
--

CREATE TABLE `schedule_exception_requests` (
  `id` int(11) NOT NULL,
  `employee_id` int(11) DEFAULT NULL,
  `requested_time_in` time DEFAULT NULL,
  `requested_time_out` time DEFAULT NULL,
  `exception_date` date DEFAULT NULL,
  `reason` text DEFAULT NULL,
  `status` enum('pending','approved','rejected') DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `schedule_exception_requests`
--

INSERT INTO `schedule_exception_requests` (`id`, `employee_id`, `requested_time_in`, `requested_time_out`, `exception_date`, `reason`, `status`, `created_at`) VALUES
(1, 1, '07:00:00', '16:43:00', '2025-05-03', 'test', 'approved', '2025-04-30 05:43:08');

-- --------------------------------------------------------

--
-- Table structure for table `time_logs`
--

CREATE TABLE `time_logs` (
  `id` int(11) NOT NULL,
  `employee_id` int(11) DEFAULT NULL,
  `log_date` date DEFAULT NULL,
  `time_in` time DEFAULT NULL,
  `time_out` time DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `work_schedules`
--

CREATE TABLE `work_schedules` (
  `id` int(11) NOT NULL,
  `name` varchar(255) DEFAULT NULL,
  `time_in` time DEFAULT NULL,
  `time_out` time DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `work_schedules`
--

INSERT INTO `work_schedules` (`id`, `name`, `time_in`, `time_out`) VALUES
(1, '7AM - 4PM', '07:00:00', '16:00:00'),
(2, '9AM - 6PM', '09:00:00', '18:00:00');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `employees`
--
ALTER TABLE `employees`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `employee_work_schedule`
--
ALTER TABLE `employee_work_schedule`
  ADD PRIMARY KEY (`id`),
  ADD KEY `employee_id` (`employee_id`),
  ADD KEY `work_schedule_id` (`work_schedule_id`);

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
  ADD KEY `requested_schedule_id` (`requested_schedule_id`);

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
-- Indexes for table `time_logs`
--
ALTER TABLE `time_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `employee_id` (`employee_id`);

--
-- Indexes for table `work_schedules`
--
ALTER TABLE `work_schedules`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `employee_work_schedule`
--
ALTER TABLE `employee_work_schedule`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `leave_requests`
--
ALTER TABLE `leave_requests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `overtime_requests`
--
ALTER TABLE `overtime_requests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `rest_day_overtime_requests`
--
ALTER TABLE `rest_day_overtime_requests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `schedule_change_requests`
--
ALTER TABLE `schedule_change_requests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `schedule_exceptions`
--
ALTER TABLE `schedule_exceptions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `schedule_exception_requests`
--
ALTER TABLE `schedule_exception_requests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `time_logs`
--
ALTER TABLE `time_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `work_schedules`
--
ALTER TABLE `work_schedules`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `employee_work_schedule`
--
ALTER TABLE `employee_work_schedule`
  ADD CONSTRAINT `employee_work_schedule_ibfk_1` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`),
  ADD CONSTRAINT `employee_work_schedule_ibfk_2` FOREIGN KEY (`work_schedule_id`) REFERENCES `work_schedules` (`id`);

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
-- Constraints for table `rest_day_overtime_requests`
--
ALTER TABLE `rest_day_overtime_requests`
  ADD CONSTRAINT `rest_day_overtime_requests_ibfk_1` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`);

--
-- Constraints for table `schedule_change_requests`
--
ALTER TABLE `schedule_change_requests`
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
-- Constraints for table `time_logs`
--
ALTER TABLE `time_logs`
  ADD CONSTRAINT `time_logs_ibfk_1` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
