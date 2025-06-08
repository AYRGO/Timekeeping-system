-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jun 08, 2025 at 07:08 AM
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
  `company` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `employees`
--

INSERT INTO `employees` (`id`, `fname`, `lname`, `email`, `personal_email`, `contact`, `position`, `status`, `created_at`, `username`, `password`, `company`) VALUES
(1, 'Vincent Kevin', 'Santos', 'Vincent.Antonio@entrygroup.com.au', 'vincentvinz17@gmail.com', '0905 919 2943', 'Student Support - Marking', 'active', '2025-04-29 01:04:12', 'vincent.santos', '1mj4i', 'Entry Education'),
(2, 'Shaina Dimayugo', 'Dela Cruz', 'shainadc86@gmail.com', 'shainadc86@gmail.com', '0935 766 5039', 'Student Support Mentoring', 'active', '2025-04-29 01:04:12', 'shaina.dela cruz', 'u6mf3', 'Entry Education'),
(3, 'Renalyn Abamo', 'Josafat', 'reny@entrygroup.com.au', 'rajosafat.cca@gmail.com', '0939 245 6150', 'Student Support Mentoring', 'active', '2025-04-29 01:04:12', 'renalyn.josafat', 'za9lv', 'Entry Education'),
(4, 'Joel Lusung', 'Alimurong', 'Joel.Alimurong@entrygroup.com.au', 'jhei.el1768@gmail.com', '0966 539 4550', 'Student Support - Marking', 'active', '2025-04-29 01:04:12', 'joel.alimurong', 'gj9af', 'Entry Education'),
(5, 'Aizel Santos', 'Castro', 'aizel.castro@entrygroup.com.au', 'aizel.castro01@gmail.com', '0926 215 0722', 'Student Support - Marking', 'active', '2025-04-29 01:04:12', 'aizel.castro', '10kjg', 'Entry Education'),
(6, 'Reymark Bryan Silvano', 'Colis', 'bryan@entrygroup.com.au', 'reymarkbryancolis@gmail.com', '0927 014 4692', 'Technical Student Support - Team Leader', 'active', '2025-04-29 01:04:12', 'reymark.colis', '8vyia', 'Entry Education'),
(7, 'Francis Emmanuel Veloso', 'Fernandez', 'francis@entrygroup.com.au', 'francis2208@gmail.com', '0967 201 4330', 'Student Support - Marking Team Leader', 'active', '2025-04-29 01:04:12', 'francis.fernandez', '6fcrh', 'Entry Education'),
(8, 'Cedrick Cruz', 'Galgo', 'Cedrick.Galgo@entrygroup.com.au', 'cedrickgalgo@gmail.com', '0966 723 9536', 'IT Support', 'active', '2025-04-29 01:04:12', 'cedrick.galgo', '3fez7', 'Entry Education'),
(9, 'Shigeru', 'Centina', 'Shigeru.Otsuka@entrygroup.com.au', 'shigeruslayer12345@gmail.com', '0939 286 1648', 'Instructional Designer - Technical Specialist', 'active', '2025-04-29 01:04:12', 'shigeru.centina', 'rxg6z', 'Entry Education'),
(10, 'Rhegene', 'Ingat Ronquillo', 'reggie@entrygroup.com.au', 'rronquillo0727@gmail.com', '0916 936 6370', 'Technical Student Support', 'active', '2025-04-29 01:04:12', 'rhegene.ronquillo', '1rodf', 'Entry Education'),
(11, 'Mary Ann', 'Vallejos Soriano', 'mary@entrygroup.com.au', 'habibisoriano@yahoo.com', '0906 255 2990', 'Sales New Student Enquiries', 'active', '2025-04-29 01:04:12', 'mary.soriano', '0bz7u', 'Entry Education'),
(12, 'Beverly', 'Taloban Gatbonton', 'beverly.gatbonton@entrygroup.com.au', 'beverlygatbonton29@gmail.com', '0920 403 7997', 'Team Leader Sales - New Student Enquiries', 'active', '2025-04-29 01:04:12', 'beverly.gatbonton', 'pw3zj', 'Entry Education'),
(13, 'Rogelio', 'Dela Peña Malinao', 'rogelio.malinao@entrygroup.com.au', 'rogelio.malinao@gmail.com', '0976 212 6539', 'Student Support - Marking', 'active', '2025-04-29 01:04:12', 'rogelio.malinao', 'vski1', 'Entry Education'),
(15, 'Evanel', 'Caacbay Navalon', 'evanel.navalon@entrygroup.com.au', 'evanelnavalon@gmail.com', '0946 882 2198', 'Technical Student Support', 'active', '2025-04-29 01:04:12', 'evanel.navalon', 'movch', 'Entry Education'),
(16, 'Ian Myco', 'Aguilar', 'ian.aguilar@entrygroup.com.au', 'iammycovital@gmail.com', '0976 198 9787', 'Student Support - Marking', 'active', '2025-04-29 01:04:12', 'ian.aguilar', 'uveib', 'Entry Education'),
(17, 'Reneeca', 'Villapaña Benalla', 'Reneeca@entrygroup.com.au', 'reneeca.benalla@gmail.com', '0909 204 3758', 'Content Writer & Instructional Designer', 'active', '2025-04-29 01:04:12', 'reneeca.benalla', 'rpycf', 'Entry Education'),
(18, 'Edith', 'David Mataga', 'Edith@entrygroup.com.au', 'edghie03@gmail.com', '0935 563 9451', 'Sales New Student Enquiries', 'active', '2025-04-29 01:04:12', 'edith.mataga', 'v2w4m', 'Entry Education'),
(20, 'Alfred Naguit', 'Ocampo', 'Alfred@entrygroup.com.au', 'derfla61@gmail.com', '0963 256 7621', 'Conveyancing Client Support', 'active', '2025-04-29 01:04:12', 'alfred.ocampo', 'xs9nq', 'Entry Education'),
(21, 'Jennifer', 'Mangitngit', 'jennifer.trinidad@entrygroup.com.au', 'jennifertrinidad0103@gmail.com', '0928 225 5869', 'Student Support - Marking', 'active', '2023-08-07 01:04:12', 'jennifer.trinidad', 'xt0rd', 'Entry Education'),
(22, 'Sean Justine', 'Francisco Mendoza', 'sean.mendoza@entrygroup.com.au', 'mendozaseanjustine@gmail.com', '0977 019 9064', 'Student Support - Marking', 'active', '2023-08-16 01:04:12', 'sean.mendoza', '71l6y', 'Entry Education'),
(24, 'Elritz', 'Tongson Crisanto', 'elritz.crisanto@entrygroup.com.au', 'ritztiong@gmail.com', '0961 289 3349', 'Student Support - Marking', 'active', '2023-08-21 01:04:12', 'elritz.crisanto', '96ewj', 'Entry Education'),
(25, 'Analiza', 'Taloban Gatbonton', 'analiza.gatbonton@entrygroup.com.au', 'analizagatbonton05@gmail.com', '0961 820 1167', 'Student Support - Marking', 'active', '2023-08-21 01:04:12', 'analiza.gatbonton', 'cjfvk', 'Entry Education'),
(26, 'Franklin Roos', 'Cinco Pabillano', 'estimating@empirewestelectrical.com.au', 'frankpabillano@gmail.com', '0961 498 0228', 'Electrical Estimator', 'active', '2023-11-03 01:04:12', 'franklin.pabillano', 'd54hj', 'onn one'),
(27, 'Kristian David', 'Bansil', 'ITsupport@mtunderground.com', 'ian_pudz@icloud.com', '0939 905 0288', 'Web Developer / Admin & IT Support', 'active', '2024-02-12 01:04:12', 'kristian.bansil', 'c9zqn', 'Maintenance Tech'),
(28, 'Louis Fernand', 'Baluyot Austria', 'louis.austria@entrygroup.com.au', 'louisaustria0@gmail.com', '0998 423 4020', 'Graphic Designer', 'active', '2024-04-04 01:04:12', 'louis.austria', 'r14xu', 'Entry Education'),
(29, 'Johana Rose', 'Perez Gueco', 'johanarose.gueco@entrygroup.com.au', 'johanagueco@gmail.com', '0906 213 7926', 'Student Support - Marking', 'active', '2024-04-04 01:04:12', 'johana.gueco', 'u1o3l', 'Entry Education'),
(30, 'Erika', 'Seriosa Pineda', 'erika.seriosa@entrygroup.com.au', 'rickzseriosa@gmail.com', '0926 355 6900', 'Student Support - Marking', 'active', '2024-04-04 01:04:12', 'erika.pineda', 'agsrw', 'Entry Education'),
(31, 'Jhunel Carlo', 'Traifalgar Samodio', 'jhunelcarlo.samodio@entrygroup.com.au', 'gun.lazuli@gmail.com', '0995 483 5711', 'Student Support - Marking', 'active', '2024-04-04 01:04:12', 'jhunel.samodio', '70twd', 'Entry Education'),
(33, 'Aldwin John', 'Arceo Lozano', 'aldwinjohn.lozano@entrygroup.com.au', 'imaj.lozano@gmail.com', '0949 369 7174', 'Sales New Student Enquiries', 'active', '2024-04-08 01:04:12', 'aldwin.lozano', 'r2hsw', 'Entry Education'),
(34, 'Yris Gaelle', 'Parreñas Camerino', 'yyrish@gmail.com', 'yyrish@gmail.com', '0912 937 9482', 'Student Support - Marking', 'active', '2024-05-01 01:04:12', 'yris.camerino', 'vijg7', 'Entry Education'),
(35, 'Nika', 'Nueva Bacongallo', 'nika.bacongallo@gmail.com', 'nika.bacongallo@gmail.com', '0919 263 0516', 'Student Support - Marking', 'active', '2024-05-20 01:04:12', 'nika.bacongallo', 't80da', 'Entry Education'),
(36, 'Denver Orlanda', 'Castillano', 'dcstudio.creative@gmail.com', 'dcstudio.creative@gmail.com', '0991 933 2312', 'Draftsman', 'active', '2024-06-10 01:04:12', 'denver.castillano', 'brcio', 'DNA Furniture & Cabinets'),
(37, 'Marnie', 'Perez Catalogo', 'marniecatalogo99@gmail.com', 'marniecatalogo99@gmail.com', '0905 453 8974', 'Technical Student Support', 'active', '2024-06-10 01:04:12', 'marnie.catalogo', '2p658', 'Entry Education'),
(38, 'Ryan Rex', 'Patrimonio', 'rexryanpatrimonio@gmail.com', 'rexryanpatrimonio@gmail.com', '0916 189 2527', 'Technical Student Support', 'active', '2024-06-10 01:04:12', 'ryan.patrimonio', 'e2gdf', 'Entry Education'),
(41, 'Ma. Charisma S.', 'Platero', 'charisma.platero@gmail.com', 'charisma.platero@gmail.com', 'N/A', 'Estimator', 'active', '2024-07-22 01:04:12', 'charisma.platero', 'ia9vr', 'Fratelli Homes'),
(43, 'Lovelaine', 'Gudoy Celeste', 'lovelaineceleste@yahoo.com', 'lovelaineceleste@yahoo.com', 'N/A', 'Sales New Student Enquiries', 'active', '2024-08-12 01:04:12', 'lovelaine.celeste', '91urk', 'Entry Education'),
(46, 'Ivy', 'Nuñez', 'ivynunez26@gmail.com', 'ivynunez26@gmail.com', 'N/A', 'Student Support - Marking', 'active', '2024-08-12 01:04:12', 'ivy.nuñez', 'f1hqu', 'Entry Education'),
(47, 'Glory Ann', 'Garcia Balderas', 'Glory@fratellihomeswa.com.au', 'gloryannbalderas@gmail.com', '0981 107 2866', 'Estimator', 'active', '2024-09-16 01:04:12', 'glory.balderas', '2i8ql', 'Fratelli Homes'),
(49, 'Julie Anne', 'Guinto Maclang', 'macjulg08@gmail.com', 'macjulg08@gmail.com', 'N/A', 'Student Support - Marking', 'active', '2024-10-14 01:04:12', 'julie.maclang', 'rvlft', 'Entry Education'),
(50, 'Francis Eugene Aguhayon', 'Bondoc', 'francis.bondoc22@gmail.com', 'francis.bondoc22@gmail.com', '629-683-416-000', 'Draftsman', 'active', '2025-04-29 14:02:24', 'francis.bondoc', 'vby8e', 'Entry Education'),
(52, 'Althea Tansingco', 'Makabenta', 'document.control@bugardi.com.au', 'atansingcomakabenta@yahoo.com', '165-661-778-000', 'Document Controller', 'active', '2025-04-29 14:02:24', 'althea.makabenta', '6i8to', 'Bugardi Contracting'),
(53, 'Christian Nioda', 'Mar', 'christianmar673@gmail.com', 'christianmar673@gmail.com', '387-307-553-000', 'Sales New Student Enquiries', 'active', '2025-04-29 14:02:24', 'christian.mar', 'lzmb2', 'Entry Education'),
(55, 'Jeffry Tuazon', 'Macapagal', 'jeff.macapagal017@gmail.com', 'jeff.macapagal017@gmail.com', '513-015-013-000', 'Operations Administrator', 'active', '2025-04-29 14:02:24', 'jeffry.macapagal', 'nzkyl', 'TRSWA'),
(56, 'Allen Sobrepeña', 'Capati', 'allen.capati@entrygroup.com.au', 'allen.capati95@gmail.com', '468-497-485-000', 'Student Support - Marking', 'active', '2025-04-29 14:02:24', 'allen.capati', 'n02or', 'Entry Education'),
(57, 'Angelica Rosario', 'Estanio', 'angelica.estanio@entrygroup.com.au', 'angelica.estanio4@gmail.com', '338-546-988-000', 'Student Support - Marking', 'active', '2025-04-29 14:02:24', 'angelica.estanio', 'qm6fh', 'Entry Education'),
(58, 'Adonis Del Mundo', 'Jabinal', 'adonis.jabinal@bugardi.com.au', 'donjabinal@gmail.com', '175-092-008-000', 'Project Coordinator', 'active', '2025-04-29 14:02:24', 'adonis.jabinal', 'aoqit', 'Bugardi Contracting'),
(59, 'Joshwea Mercado', 'Monis', 'joshwea.monis@entrygroup.com.au', 'mjoshwea@gmail.com', '332-760-833-000', 'Student Support - Marking', 'active', '2025-04-29 14:02:24', 'joshwea.monis', 'bjor0', 'Entry Education'),
(60, 'Janeth Sedon', 'Solayao', 'janeth.solayao@entrygroup.com.au', 'janethsolayao32@gmail.com', 'TO FOLLOW', 'Student Support - Marking', 'active', '2025-04-29 14:02:24', 'janeth.solayao', 'i4yzp', 'Entry Education'),
(61, 'Ray Jinder Villena', 'Singh', 'rvschk@gmail.com', 'rvschk@gmail.com', '350-760-267-000', 'Executive Assistant', 'active', '2025-04-29 14:02:24', 'rj.singh', 'lpzom', 'Rowland Plumbing & Gas'),
(62, 'Shirmiley Canlas', 'Quizon', 'shirmiley.quizon@bugardi.com.au', 'shirmiley.quizon@gmail.com', '210-283-638-000', 'Recruitment Mobilization Officer', 'active', '2025-04-29 14:02:24', 'shirmiley.quizon', 'm2g7l', 'Bugardi Contracting'),
(63, 'Maria Ñina Dizon', 'Dollentes', 'nina.dollentes@entrygroup.com.au', 'marianinadollentes@gmail.com', '337-370-586-000', 'Accountant', 'active', '2025-04-29 14:02:24', 'Nina.dollentes', 'qp4i9', 'Entry Education'),
(64, 'Jerzi Chezka Medel', 'Libatique', 'jerzi.libatique@entrygroup.com.au', 'jerzichezkamedel@gmail.com', '396-119-405-000', 'Accountant', 'active', '2025-04-29 14:02:24', 'jerzi.libatique', '9ta18', 'Entry Education'),
(65, 'Sabando', 'Nuñeza Dou Lester', 'lester.nuneza@bugardi.com.au', 'lesternuneza@gmail.com', '264-711-363-000', 'HSEQ Assistant Manager', 'active', '2025-04-29 14:02:24', 'Lester.nuñeza ', '3407m', 'Entry Education'),
(66, 'Dionicio', 'Ocampo Godwin', 'tgodbtg04@gmail.com', 'tgodbtg04@gmail.com', '743-363-945-000', 'Tax Accountant', 'active', '2025-04-29 14:02:24', 'godwin.ocampo ', 'udlag', 'Entry Education'),
(67, 'Apryl Ordonio', 'Pasion', 'apryl.pasion@bugardi.com.au', 'aprylpolicarpio@gmail.com', 'TO FOLLOW', 'Recruitment Mobilization Officer', 'active', '2025-04-29 14:02:24', 'apryl.pasion', 'ovcp0', 'Bugardi Contracting'),
(68, 'Christine Khlaryss', 'Angeles', 'christinekhlaryss@gmail.com', 'christinekhlaryss@gmail.com', '351-635-569-000', 'Tax Accountant', 'active', '2025-04-29 14:02:24', 'christine.angeles', 'bnl8k', 'Denning'),
(69, 'Trisha Mae Adriano', 'McGregor', 'trisha_mcgregor@yahoo.com', 'trisha_mcgregor@yahoo.com', '486-881-956-00000', 'Renovation Draftsman', 'active', '2025-04-29 14:02:24', 'trisha.mcgregor', 'biv1r', 'Ridge Renovation'),
(70, 'John Michael Comprado', 'Briones', 'jamenabriones14@gmail.com', 'jamenabriones14@gmail.com', '620-743-947-000', 'Commercial Estimator', 'active', '2025-04-29 14:02:24', 'jm.briones', 'w1g89', 'TRSWA'),
(71, 'Precious Zahra Cortez', 'Cabusao', 'zahracortez95@gmail.com', 'zahracortez95@gmail.com', '326-526-766-000', 'Hydraulics Estimator', 'active', '2025-04-29 14:02:24', 'zahra.cabusao', 'dwzgl', 'Leeway Group'),
(72, 'Milbert ', 'Sambile', 'milbert@millersroofing.com.au', 'milbert.sambile@gmail.com', '', 'Estimator', 'active', '2025-06-03 01:08:23', 'Milbert.Sambile', 'Milbert', 'Miller\'s Roofing'),
(1002, 'Neil Anthony', 'Costelloe', 'Neil.Costelloe@resourcestaff.com.ph', 'neilcosetelloe@gmail.com', NULL, 'General Manager', 'active', '2024-05-19 16:00:00', 'neil.costelloe', 'ypv9h', 'RSS'),
(1003, 'Cristina Miranda', 'Pangan', 'Tina.Pangan@resourcestaff.com.ph', 'thine2miranda@gmail.com', '0915 056 1780', 'Executive Assistant to the General Manager', 'active', '2024-03-31 16:00:00', 'cristina.pangan', '85ivt', 'RSS'),
(1004, 'Rica Joy Viray', 'Tolomia', 'Rica.Tolomia@resourcestaff.com.ph', 'Rica.Tolomia@resourcestaff.com.ph', '0917 389 7962', 'TA/HR Specialist', 'active', '2024-08-11 16:00:00', 'rj.tolomia', 'gojwd', 'RSS'),
(1005, 'Johsua Torninos', 'Dimla', 'johsua.dimla1986@gmail.com', 'johsua.dimla1986@gmail.com', '0933 430 3081', 'Facilities and Admin Support', 'active', '2024-09-29 16:00:00', 'johsua.dimla', 'r9em0', 'RSS'),
(1006, 'Cedrick', 'Arnigo', 'IT@resourcestaff.com.ph', 'cedrickarnigo1723@gmail.com', '09938642974', 'IT Support Specialist', 'active', '2025-05-27 23:09:46', 'Cedrick.Arnigo', 'Gr33n$$wRf', 'RSS'),
(1007, 'Peach', 'Herrera', 'herrerafelicci@gmail.com', 'herrerafelicci@gmail.com', '0903323232', 'Admin', 'active', '2025-06-02 06:19:09', 'Peach.Herrera', 'Gh0920', 'RSS');

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
  `leave_type` enum('VL','SL','SPL','Half_SL','Half_VL','LWOP','Maternity','Paternity') DEFAULT NULL,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `reason` text DEFAULT NULL,
  `status` enum('pending','approved','rejected') DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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
  `status` enum('pending','approved','rejected') DEFAULT NULL,
  `requested_effective_date` date DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
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
-- Table structure for table `work_schedules`
--

CREATE TABLE `work_schedules` (
  `id` int(11) NOT NULL,
  `name` varchar(255) DEFAULT NULL,
  `time_in` time DEFAULT NULL,
  `time_out` time DEFAULT NULL
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
-- Indexes for table `approved_overtime_schedule`
--
ALTER TABLE `approved_overtime_schedule`
  ADD PRIMARY KEY (`id`);

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
-- AUTO_INCREMENT for table `admin`
--
ALTER TABLE `admin`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `approved_overtime_schedule`
--
ALTER TABLE `approved_overtime_schedule`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `employees`
--
ALTER TABLE `employees`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1010;

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `overtime_requests`
--
ALTER TABLE `overtime_requests`
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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

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
-- AUTO_INCREMENT for table `time_logs`
--
ALTER TABLE `time_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

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
