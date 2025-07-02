-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jun 26, 2025 at 02:16 AM
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
  `id` int(11) NOT NULL,
  `admin_name` varchar(100) DEFAULT NULL,
  `content` text NOT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `announcements`
--

INSERT INTO `announcements` (`id`, `admin_name`, `content`, `created_at`) VALUES
(1, 'Admin', 'test', '2025-06-25 12:35:20'),
(2, 'Admin', 'try', '2025-06-25 12:36:27'),
(3, NULL, 'test', '2025-06-25 13:11:07');

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
(3, 'Renalyn Abamo', 'Josafat', 'reny@entrygroup.com.au', 'rajosafat.cca@gmail.com', '0939 245 6150', 'Student Support Mentoring', 'active', '2025-04-29 01:04:12', 'renalyn.josafat', 'za9lv', 'Entry Education', NULL, 'profile_3_1750811272.png'),
(4, 'Joel Lusung', 'Alimurong', 'Joel.Alimurong@entrygroup.com.au', 'jhei.el1768@gmail.com', '0966 539 4550', 'Student Support - Marking', 'active', '2025-04-29 01:04:12', 'joel.alimurong', 'gj9af', 'Entry Education', NULL, 'profile_4_1749606383.jpeg'),
(5, 'Aizel Santos', 'Castro', 'aizel.castro@entrygroup.com.au', 'aizel.castro01@gmail.com', '0926 215 0722', 'Student Support - Marking', 'active', '2025-04-29 01:04:12', 'aizel.castro', '10kjg', 'Entry Education', NULL, NULL),
(6, 'Reymark Bryan Silvano', 'Colis', 'bryan@entrygroup.com.au', 'reymarkbryancolis@gmail.com', '0927 014 4692', 'Technical Student Support - Team Leader', 'active', '2025-04-29 01:04:12', 'reymark.colis', '8vyia', 'Entry Education', NULL, 'profile_6_1749606888.jpeg'),
(7, 'Francis Emmanuel Veloso', 'Fernandez', 'francis@entrygroup.com.au', 'francis2208@gmail.com', '0967 201 4330', 'Student Support - Marking Team Leader', 'active', '2025-04-29 01:04:12', 'francis.fernandez', '6fcrh', 'Entry Education', NULL, NULL),
(8, 'Cedrick Cruz', 'Galgo', 'Cedrick.Galgo@entrygroup.com.au', 'cedrickgalgo@gmail.com', '0966 723 9536', 'IT Support', 'active', '2025-04-29 01:04:12', 'cedrick.galgo', '3fez7', 'Entry Education', NULL, NULL),
(9, 'Shigeru', 'Centina', 'Shigeru.Otsuka@entrygroup.com.au', 'shigeruslayer12345@gmail.com', '0939 286 1648', 'Instructional Designer - Technical Specialist', 'active', '2025-04-29 01:04:12', 'shigeru.centina', 'rxg6z', 'Entry Education', NULL, 'profile_9_1749618682.jpg'),
(10, 'Rhegene', 'Ingat Ronquillo', 'reggie@entrygroup.com.au', 'rronquillo0727@gmail.com', '0916 936 6370', 'Technical Student Support', 'active', '2025-04-29 01:04:12', 'rhegene.ronquillo', '1rodf', 'Entry Education', NULL, NULL),
(11, 'Mary Ann', 'Vallejos Soriano', 'mary@entrygroup.com.au', 'habibisoriano@yahoo.com', '0906 255 2990', 'Sales New Student Enquiries', 'active', '2025-04-29 01:04:12', 'mary.soriano', '0bz7u', 'Entry Education', NULL, 'profile_11_1749624970.jpeg'),
(12, 'Beverly', 'Taloban Gatbonton', 'beverly.gatbonton@entrygroup.com.au', 'beverlygatbonton29@gmail.com', '0920 403 7997', 'Team Leader Sales - New Student Enquiries', 'active', '2025-04-29 01:04:12', 'beverly.gatbonton', 'pw3zj', 'Entry Education', NULL, NULL),
(13, 'Rogelio', 'Dela Peña Malinao', 'rogelio.malinao@entrygroup.com.au', 'rogelio.malinao@gmail.com', '0976 212 6539', 'Student Support - Marking', 'active', '2025-04-29 01:04:12', 'rogelio.malinao', 'vski1', 'Entry Education', NULL, NULL),
(15, 'Evanel', 'Caacbay Navalon', 'evanel.navalon@entrygroup.com.au', 'evanelnavalon@gmail.com', '0946 882 2198', 'Technical Student Support', 'active', '2025-04-29 01:04:12', 'evanel.navalon', 'movch', 'Entry Education', NULL, NULL),
(16, 'Ian Myco', 'Aguilar', 'ian.aguilar@entrygroup.com.au', 'iammycovital@gmail.com', '0976 198 9787', 'Student Support - Marking', 'active', '2025-04-29 01:04:12', 'ian.aguilar', 'uveib', 'Entry Education', NULL, NULL),
(17, 'Reneeca', 'Villapaña Benalla', 'Reneeca@entrygroup.com.au', 'reneeca.benalla@gmail.com', '0909 204 3758', 'Content Writer & Instructional Designer', 'active', '2025-04-29 01:04:12', 'reneeca.benalla', 'rpycf', 'Entry Education', NULL, NULL),
(18, 'Edith', 'David Mataga', 'Edith@entrygroup.com.au', 'edghie03@gmail.com', '0935 563 9451', 'Sales New Student Enquiries', 'active', '2025-04-29 01:04:12', 'edith.mataga', 'v2w4m', 'Entry Education', NULL, NULL),
(20, 'Alfred Naguit', 'Ocampo', 'Alfred@entrygroup.com.au', 'derfla61@gmail.com', '0963 256 7621', 'Conveyancing Client Support', 'active', '2025-04-29 01:04:12', 'alfred.ocampo', 'xs9nq', 'Entry Education', NULL, NULL),
(21, 'Jennifer', 'Mangitngit', 'jennifer.trinidad@entrygroup.com.au', 'jennifertrinidad0103@gmail.com', '0928 225 5869', 'Student Support - Marking', 'active', '2023-08-07 01:04:12', 'jennifer.trinidad', 'xt0rd', 'Entry Education', NULL, NULL),
(22, 'Sean Justine', 'Francisco Mendoza', 'sean.mendoza@entrygroup.com.au', 'mendozaseanjustine@gmail.com', '0977 019 9064', 'Student Support - Marking', 'active', '2023-08-16 01:04:12', 'sean.mendoza', '71l6y', 'Entry Education', NULL, NULL),
(24, 'Elritz', 'Tongson Crisanto', 'elritz.crisanto@entrygroup.com.au', 'ritztiong@gmail.com', '0961 289 3349', 'Student Support - Marking', 'active', '2023-08-21 01:04:12', 'elritz.crisanto', '96ewj', 'Entry Education', NULL, NULL),
(25, 'Analiza', 'Taloban Gatbonton', 'analiza.gatbonton@entrygroup.com.au', 'analizagatbonton05@gmail.com', '0961 820 1167', 'Student Support - Marking', 'active', '2023-08-21 01:04:12', 'analiza.gatbonton', 'cjfvk', 'Entry Education', NULL, NULL),
(26, 'Franklin Roos', 'Cinco Pabillano', 'estimating@empirewestelectrical.com.au', 'frankpabillano@gmail.com', '0961 498 0228', 'Electrical Estimator', 'active', '2023-11-03 01:04:12', 'franklin.pabillano', 'd54hj', 'onn one', NULL, NULL),
(27, 'Kristian David', 'Bansil', 'ITsupport@mtunderground.com', 'ian_pudz@icloud.com', '0939 905 0288', 'Web Developer / Admin & IT Support', 'active', '2024-02-12 01:04:12', 'kristian.bansil', 'c9zqn', 'Maintenance Tech', NULL, NULL),
(28, 'Louis Fernand', 'Baluyot Austria', 'louis.austria@entrygroup.com.au', 'louisaustria0@gmail.com', '0998 423 4020', 'Graphic Designer', 'active', '2024-04-04 01:04:12', 'louis.austria', 'r14xu', 'Entry Education', NULL, NULL),
(29, 'Johana Rose', 'Perez Gueco', 'johanarose.gueco@entrygroup.com.au', 'johanagueco@gmail.com', '0906 213 7926', 'Student Support - Marking', 'active', '2024-04-04 01:04:12', 'johana.gueco', 'u1o3l', 'Entry Education', NULL, NULL),
(30, 'Erika', 'Seriosa Pineda', 'erika.seriosa@entrygroup.com.au', 'rickzseriosa@gmail.com', '0926 355 6900', 'Student Support - Marking', 'active', '2024-04-04 01:04:12', 'erika.pineda', 'agsrw', 'Entry Education', NULL, NULL),
(31, 'Jhunel Carlo', 'Traifalgar Samodio', 'jhunelcarlo.samodio@entrygroup.com.au', 'gun.lazuli@gmail.com', '0995 483 5711', 'Student Support - Marking', 'active', '2024-04-04 01:04:12', 'jhunel.samodio', '70twd', 'Entry Education', NULL, NULL),
(33, 'Aldwin John', 'Arceo Lozano', 'aldwinjohn.lozano@entrygroup.com.au', 'imaj.lozano@gmail.com', '0949 369 7174', 'Sales New Student Enquiries', 'active', '2024-04-08 01:04:12', 'aldwin.lozano', 'r2hsw', 'Entry Education', NULL, NULL),
(34, 'Yris Gaelle', 'Parreñas Camerino', 'yyrish@gmail.com', 'yyrish@gmail.com', '0912 937 9482', 'Student Support - Marking', 'active', '2024-05-01 01:04:12', 'yris.camerino', 'vijg7', 'Entry Education', NULL, NULL),
(35, 'Nika', 'Nueva Bacongallo', 'nika.bacongallo@gmail.com', 'nika.bacongallo@gmail.com', '0919 263 0516', 'Student Support - Marking', 'active', '2024-05-20 01:04:12', 'nika.bacongallo', 't80da', 'Entry Education', NULL, NULL),
(36, 'Denver Orlanda', 'Castillano', 'dcstudio.creative@gmail.com', 'dcstudio.creative@gmail.com', '0991 933 2312', 'Draftsman', 'active', '2024-06-10 01:04:12', 'denver.castillano', 'brcio', 'DNA Furniture & Cabinets', NULL, NULL),
(37, 'Marnie', 'Perez Catalogo', 'marniecatalogo99@gmail.com', 'marniecatalogo99@gmail.com', '0905 453 8974', 'Technical Student Support', 'active', '2024-06-10 01:04:12', 'marnie.catalogo', '2p658', 'Entry Education', NULL, NULL),
(38, 'Ryan Rex', 'Patrimonio', 'rexryanpatrimonio@gmail.com', 'rexryanpatrimonio@gmail.com', '0916 189 2527', 'Technical Student Support', 'active', '2024-06-10 01:04:12', 'ryan.patrimonio', 'e2gdf', 'Entry Education', NULL, NULL),
(41, 'Ma. Charisma S.', 'Platero', 'charisma.platero@gmail.com', 'charisma.platero@gmail.com', 'N/A', 'Estimator', 'active', '2024-07-22 01:04:12', 'charisma.platero', 'ia9vr', 'Fratelli Homes', NULL, NULL),
(43, 'Lovelaine', 'Gudoy Celeste', 'lovelaineceleste@yahoo.com', 'lovelaineceleste@yahoo.com', 'N/A', 'Sales New Student Enquiries', 'active', '2024-08-12 01:04:12', 'lovelaine.celeste', '91urk', 'Entry Education', NULL, NULL),
(46, 'Ivy', 'Nuñez', 'ivynunez26@gmail.com', 'ivynunez26@gmail.com', 'N/A', 'Student Support - Marking', 'active', '2024-08-12 01:04:12', 'ivy.nuñez', 'f1hqu', 'Entry Education', NULL, NULL),
(47, 'Glory Ann', 'Garcia Balderas', 'Glory@fratellihomeswa.com.au', 'gloryannbalderas@gmail.com', '0981 107 2866', 'Estimator', 'active', '2024-09-16 01:04:12', 'glory.balderas', '2i8ql', 'Fratelli Homes', NULL, NULL),
(49, 'Julie Anne', 'Guinto Maclang', 'macjulg08@gmail.com', 'macjulg08@gmail.com', 'N/A', 'Student Support - Marking', 'active', '2024-10-14 01:04:12', 'julie.maclang', 'rvlft', 'Entry Education', NULL, NULL),
(50, 'Francis Eugene Aguhayon', 'Bondoc', 'francis.bondoc22@gmail.com', 'francis.bondoc22@gmail.com', '629-683-416-000', 'Draftsman', 'active', '2025-04-29 14:02:24', 'francis.bondoc', 'vby8e', 'Entry Education', NULL, NULL),
(52, 'Althea Tansingco', 'Makabenta', 'document.control@bugardi.com.au', 'atansingcomakabenta@yahoo.com', '165-661-778-000', 'Document Controller', 'active', '2025-04-29 14:02:24', 'althea.makabenta', '6i8to', 'Bugardi Contracting', NULL, NULL),
(53, 'Christian Nioda', 'Mar', 'christianmar673@gmail.com', 'christianmar673@gmail.com', '387-307-553-000', 'Sales New Student Enquiries', 'active', '2025-04-29 14:02:24', 'christian.mar', 'lzmb2', 'Entry Education', NULL, NULL),
(55, 'Jeffry Tuazon', 'Macapagal', 'jeff.macapagal017@gmail.com', 'jeff.macapagal017@gmail.com', '513-015-013-000', 'Operations Administrator', 'active', '2025-04-29 14:02:24', 'jeffry.macapagal', 'nzkyl', 'TRSWA', NULL, NULL),
(56, 'Allen Sobrepeña', 'Capati', 'allen.capati@entrygroup.com.au', 'allen.capati95@gmail.com', '468-497-485-000', 'Student Support - Marking', 'active', '2025-04-29 14:02:24', 'allen.capati', 'n02or', 'Entry Education', NULL, NULL),
(57, 'Angelica Rosario', 'Estanio', 'angelica.estanio@entrygroup.com.au', 'angelica.estanio4@gmail.com', '338-546-988-000', 'Student Support - Marking', 'active', '2025-04-29 14:02:24', 'angelica.estanio', 'qm6fh', 'Entry Education', NULL, NULL),
(58, 'Adonis Del Mundo', 'Jabinal', 'adonis.jabinal@bugardi.com.au', 'donjabinal@gmail.com', '175-092-008-000', 'Project Coordinator', 'active', '2025-04-29 14:02:24', 'adonis.jabinal', 'aoqit', 'Bugardi Contracting', NULL, NULL),
(59, 'Joshwea Mercado', 'Monis', 'joshwea.monis@entrygroup.com.au', 'mjoshwea@gmail.com', '332-760-833-000', 'Student Support - Marking', 'active', '2025-04-29 14:02:24', 'joshwea.monis', 'bjor0', 'Entry Education', NULL, NULL),
(60, 'Janeth Sedon', 'Solayao', 'janeth.solayao@entrygroup.com.au', 'janethsolayao32@gmail.com', 'TO FOLLOW', 'Student Support - Marking', 'active', '2025-04-29 14:02:24', 'janeth.solayao', 'i4yzp', 'Entry Education', NULL, NULL),
(61, 'Ray Jinder Villena', 'Singh', 'rvschk@gmail.com', 'rvschk@gmail.com', '350-760-267-000', 'Executive Assistant', 'active', '2025-04-29 14:02:24', 'rj.singh', 'lpzom', 'Rowland Plumbing & Gas', NULL, NULL),
(62, 'Shirmiley Canlas', 'Quizon', 'shirmiley.quizon@bugardi.com.au', 'shirmiley.quizon@gmail.com', '210-283-638-000', 'Recruitment Mobilization Officer', 'active', '2025-04-29 14:02:24', 'shirmiley.quizon', 'm2g7l', 'Bugardi Contracting', NULL, NULL),
(63, 'Maria Ñina Dizon', 'Dollentes', 'nina.dollentes@entrygroup.com.au', 'marianinadollentes@gmail.com', '337-370-586-000', 'Accountant', 'active', '2025-04-29 14:02:24', 'Nina.dollentes', 'qp4i9', 'Entry Education', NULL, NULL),
(64, 'Jerzi Chezka Medel', 'Libatique', 'jerzi.libatique@entrygroup.com.au', 'jerzichezkamedel@gmail.com', '396-119-405-000', 'Accountant', 'active', '2025-04-29 14:02:24', 'jerzi.libatique', '9ta18', 'Entry Education', NULL, NULL),
(65, 'Sabando', 'Nuñeza Dou Lester', 'lester.nuneza@bugardi.com.au', 'lesternuneza@gmail.com', '264-711-363-000', 'HSEQ Assistant Manager', 'active', '2025-04-29 14:02:24', 'Lester.nuñeza ', '3407m', 'Entry Education', NULL, NULL),
(66, 'Dionicio', 'Ocampo Godwin', 'tgodbtg04@gmail.com', 'tgodbtg04@gmail.com', '743-363-945-000', 'Tax Accountant', 'active', '2025-04-29 14:02:24', 'godwin.ocampo ', 'udlag', 'Entry Education', NULL, NULL),
(67, 'Apryl Ordonio', 'Pasion', 'apryl.pasion@bugardi.com.au', 'aprylpolicarpio@gmail.com', 'TO FOLLOW', 'Recruitment Mobilization Officer', 'active', '2025-04-29 14:02:24', 'apryl.pasion', 'ovcp0', 'Bugardi Contracting', NULL, NULL),
(68, 'Christine Khlaryss', 'Angeles', 'christinekhlaryss@gmail.com', 'christinekhlaryss@gmail.com', '351-635-569-000', 'Tax Accountant', 'active', '2025-04-29 14:02:24', 'christine.angeles', 'bnl8k', 'Denning', NULL, NULL),
(69, 'Trisha Mae Adriano', 'McGregor', 'trisha_mcgregor@yahoo.com', 'trisha_mcgregor@yahoo.com', '486-881-956-00000', 'Renovation Draftsman', 'active', '2025-04-29 14:02:24', 'trisha.mcgregor', 'biv1r', 'Ridge Renovation', NULL, NULL),
(70, 'John Michael Comprado', 'Briones', 'jamenabriones14@gmail.com', 'jamenabriones14@gmail.com', '620-743-947-000', 'Commercial Estimator', 'active', '2025-04-29 14:02:24', 'jm.briones', 'w1g89', 'TRSWA', NULL, NULL),
(71, 'Precious Zahra Cortez', 'Cabusao', 'zahracortez95@gmail.com', 'zahracortez95@gmail.com', '326-526-766-000', 'Hydraulics Estimator', 'active', '2025-04-29 14:02:24', 'zahra.cabusao', 'dwzgl', 'Leeway Group', NULL, NULL),
(72, 'Milbert ', 'Sambile', 'milbert@millersroofing.com.au', 'milbert.sambile@gmail.com', '', 'Estimator', 'active', '2025-06-03 01:08:23', 'Milbert.Sambile', 'Milbert', 'Miller\'s Roofing', NULL, NULL),
(1002, 'Neil Anthony', 'Costelloe', 'Neil.Costelloe@resourcestaff.com.ph', 'neilcosetelloe@gmail.com', NULL, 'General Manager', 'active', '2024-05-19 16:00:00', 'neil.costelloe', 'ypv9h', 'RSS', NULL, NULL),
(1003, 'Cristina Miranda', 'Pangan', 'Tina.Pangan@resourcestaff.com.ph', 'thine2miranda@gmail.com', '0915 056 1780', 'Executive Assistant to the General Manager', 'active', '2024-03-31 16:00:00', 'cristina.pangan', '85ivt', 'RSS', NULL, NULL),
(1004, 'Rica Joy Viray', 'Tolomia', 'Rica.Tolomia@resourcestaff.com.ph', 'Rica.Tolomia@resourcestaff.com.ph', '0917 389 7962', 'TA/HR Specialist', 'active', '2024-08-11 16:00:00', 'rj.tolomia', 'gojwd', 'RSS', NULL, NULL),
(1005, 'Johsua Torninos', 'Dimla', 'johsua.dimla1986@gmail.com', 'johsua.dimla1986@gmail.com', '0933 430 3081', 'Facilities and Admin Support', 'active', '2024-09-29 16:00:00', 'johsua.dimla', 'r9em0', 'RSS', NULL, NULL),
(1006, 'Cedrick', 'Arnigo', 'IT@resourcestaff.com.ph', 'cedrickarnigo1723@gmail.com', '09938642974', 'IT Support Specialist', 'active', '2025-05-27 23:09:46', 'Cedrick.Arnigo', 'Gr33n$$wRf', 'RSS', NULL, NULL),
(1007, 'Peach', 'Herrera', 'herrerafelicci@gmail.com', 'herrerafelicci@gmail.com', '0903323232', 'Admin', 'active', '2025-06-02 06:19:09', 'Peach.Herrera', 'Gh0920', 'RSS', NULL, 'profile_1007.jpg'),
(1009, 'Resty', 'Nazareno', 'rjmanago@gmail.com', 'rjmanago@gmail.com', '09763659773', 'IT Intern', 'active', '2025-06-10 02:48:29', 'Kiras001', 'vosfows12', 'RSS', NULL, 'profile_1009_1749770448.jpeg');

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
  `leave_type` enum('VL','SL','SPL','Half_SL','Half_VL','LWOP','Maternity','Paternity') DEFAULT NULL,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `reason` text DEFAULT NULL,
  `status` enum('pending','approved','rejected') DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `leave_dates` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `leave_requests`
--

INSERT INTO `leave_requests` (`id`, `employee_id`, `leave_type`, `start_date`, `end_date`, `reason`, `status`, `created_at`, `leave_dates`) VALUES
(17, 1, 'Maternity', NULL, NULL, 'test', NULL, '2025-06-24 06:05:46', '2025-06-21 to 2025-06-28'),
(18, 1, '', NULL, NULL, 'yes na yes', NULL, '2025-06-24 06:06:16', '2025-06-24 to 2025-07-12'),
(19, 1, '', NULL, NULL, 'tinatamad', NULL, '2025-06-24 06:08:16', '2025-06-24 to 2025-07-05'),
(20, 1, '', NULL, NULL, 'testing lng', NULL, '2025-06-24 06:13:51', '2025-06-24 to 2025-06-28'),
(21, 1, '', NULL, NULL, 'testingss', NULL, '2025-06-24 06:19:51', '2025-06-24 to 2025-06-30'),
(22, 1, 'Paternity', NULL, NULL, 'tet', NULL, '2025-06-24 06:21:53', '2025-06-24 to 2025-07-01'),
(23, 1, '', NULL, NULL, 'test', NULL, '2025-06-24 06:23:30', '2025-06-24 to 2025-07-01'),
(24, 1, '', '2025-06-24', '2025-06-28', 'TEST', NULL, '2025-06-24 06:30:07', ''),
(25, 1, 'Maternity', '2025-06-24', '2025-07-05', 'TRY LNG PO', NULL, '2025-06-24 06:30:20', ''),
(26, 1009, '', '2025-06-24', '2025-06-24', 'testing lng', NULL, '2025-06-24 06:31:32', ''),
(27, 1009, '', '2025-06-24', '2025-07-12', 'vovos', NULL, '2025-06-24 06:32:13', ''),
(28, 1009, '', '2025-06-24', '2025-07-11', 'yesasdasd', NULL, '2025-06-24 06:36:05', ''),
(29, 1009, 'Maternity', '2025-06-24', '2025-07-10', 'jontis', NULL, '2025-06-24 06:36:17', ''),
(30, 1009, '', '2025-06-24', '2025-06-28', 'testing boss', NULL, '2025-06-24 06:43:15', ''),
(31, 2, '', '2025-06-24', '2025-06-27', 'testt', NULL, '2025-06-24 07:12:35', ''),
(32, 2, '', '0000-00-00', '0000-00-00', 'test', NULL, '2025-06-24 07:19:34', ''),
(33, 2, '', '2025-06-28', '2025-07-03', 'testsadasd', NULL, '2025-06-24 07:20:12', ''),
(34, 2, '', NULL, NULL, 'sda', NULL, '2025-06-24 07:28:33', '2025-06-13 to 2025-06-28'),
(35, 1009, '', '2025-06-25', '2025-06-26', 'sad', NULL, '2025-06-24 23:41:50', ''),
(36, 1009, '', '2025-06-25', '2025-06-27', 'asd', NULL, '2025-06-25 00:23:23', ''),
(37, 3, 'Paternity', '2025-06-25', '2025-06-28', '1234567', 'pending', '2025-06-25 00:31:31', '');

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
  `work_schedule` varchar(255) DEFAULT NULL,
  `status` enum('pending','approved','rejected') DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `confirmed_rcs` datetime DEFAULT NULL,
  `declined_rcs` datetime DEFAULT NULL,
  `work_schedule_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `schedule_change_requests`
--

INSERT INTO `schedule_change_requests` (`id`, `employee_id`, `requested_schedule_id`, `reason`, `work_schedule`, `status`, `created_at`, `start_date`, `end_date`, `confirmed_rcs`, `declined_rcs`, `work_schedule_id`) VALUES
(18, 1009, 5, 'sdasa', NULL, 'pending', '2025-06-22 23:50:32', '2025-06-21', '2025-06-23', NULL, NULL, NULL),
(19, 1009, 7, 'testss', NULL, 'pending', '2025-06-23 00:14:20', '2025-06-23', '2025-06-27', NULL, NULL, NULL),
(20, 1009, 6, 'sdasd', NULL, 'pending', '2025-06-23 01:56:20', '2025-06-26', '2025-07-04', NULL, NULL, NULL),
(21, 1009, 6, 'test', NULL, 'pending', '2025-06-23 06:33:39', '2025-06-14', '2025-06-23', NULL, NULL, NULL),
(22, 1009, 6, 'trest', NULL, 'pending', '2025-06-23 06:33:59', '2025-06-28', '2025-07-04', NULL, NULL, NULL),
(23, 1009, 6, 'sda', NULL, 'pending', '2025-06-24 01:57:12', '2025-06-28', '2025-07-04', NULL, NULL, NULL),
(24, 1009, 7, 'test', NULL, 'pending', '2025-06-24 06:51:13', '2025-06-28', '2025-07-03', NULL, NULL, NULL),
(25, 1009, 7, 'test', NULL, 'pending', '2025-06-24 07:03:45', '2025-06-27', '2025-07-04', NULL, NULL, NULL),
(26, 2, 6, 'sda', NULL, 'pending', '2025-06-24 07:18:53', '2025-06-28', '2025-07-03', NULL, NULL, NULL),
(27, 2, 7, 'sda', NULL, 'pending', '2025-06-24 07:24:26', '2025-06-27', '2025-07-05', NULL, NULL, NULL),
(28, 2, 7, 'asdsdadsdsdsadasdddddddddddddd', NULL, 'pending', '2025-06-24 07:51:09', '2025-06-24', '2025-07-03', NULL, NULL, NULL),
(29, 2, 7, 'test20000', NULL, 'pending', '2025-06-24 07:51:24', '2025-06-26', '2025-07-03', NULL, NULL, NULL),
(30, 2, 7, 'test20000', NULL, 'pending', '2025-06-24 07:51:28', '2025-06-26', '2025-07-03', NULL, NULL, NULL),
(31, 2, 7, 'test sad', NULL, 'pending', '2025-06-24 07:51:36', '2025-06-26', '2025-07-03', NULL, NULL, NULL),
(32, 2, 7, 'test fda', NULL, 'pending', '2025-06-24 07:51:43', '2025-06-26', '2025-07-03', NULL, NULL, NULL),
(33, 2, 6, 'asd', NULL, 'pending', '2025-06-24 07:52:35', '2025-06-24', '2025-07-03', NULL, NULL, NULL),
(34, 2, 7, 'laptop1212', NULL, 'pending', '2025-06-24 07:52:45', '2025-06-27', '2025-07-04', NULL, NULL, NULL),
(35, 1009, 7, 'asd', NULL, 'pending', '2025-06-25 00:15:19', '2025-06-25', '2025-06-30', NULL, NULL, NULL),
(36, 1009, 7, 'asd', NULL, 'approved', '2025-06-25 00:17:21', '2025-06-28', '2025-07-05', NULL, NULL, NULL),
(37, 1009, 7, 'asd', NULL, 'approved', '2025-06-25 00:17:30', '2025-06-25', '2025-07-04', NULL, NULL, NULL),
(38, 3, 7, 'test', NULL, 'approved', '2025-06-25 01:57:53', '2025-06-25', '2025-07-04', NULL, NULL, NULL),
(39, 11, NULL, 'shoppee', NULL, 'pending', '2025-06-25 06:23:36', '2025-06-25', '2025-07-02', NULL, NULL, NULL),
(40, 1006, NULL, 'test', NULL, 'approved', '2025-06-25 06:39:17', '2025-06-25', '2025-07-03', NULL, NULL, 6),
(41, 15, NULL, '10 to 7 works for me', NULL, 'approved', '2025-06-25 07:17:38', '2025-06-25', '2025-07-03', NULL, NULL, 7);

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
(11, 1009, '2025-06-10', '11:15:35', NULL, 0, 0),
(12, 67, '2025-06-11', '09:04:44', '09:08:23', 0, 0),
(13, 1009, '2025-06-11', '09:10:21', '09:12:13', 0, 0),
(14, 1, '2025-06-11', '09:21:00', '09:21:10', 0, 0),
(15, 2, '2025-06-11', '09:26:27', '09:26:33', 0, 0),
(16, 4, '2025-06-11', '09:46:28', '09:46:31', 0, 0),
(17, 6, '2025-06-11', '09:54:40', '09:54:45', 0, 0),
(18, 9, '2025-06-11', '10:09:11', '10:09:22', 0, 0),
(19, 11, '2025-06-11', '14:55:51', NULL, 0, 0),
(20, 1009, '2025-06-13', '07:23:04', '07:23:13', 0, 0),
(21, 2, '2025-06-13', '07:24:57', '07:26:04', 0, 0),
(22, 1009, '2025-06-16', '08:21:49', '08:25:16', 0, 0),
(23, 1009, '2025-06-16', '08:21:53', '08:25:16', 0, 0),
(24, 2, '2025-06-16', '09:04:37', '09:04:51', 0, 0),
(25, 3, '2025-06-16', '15:05:05', '15:05:12', 0, 0),
(26, 1009, '2025-06-20', '08:49:30', '09:52:39', 0, 0),
(27, 1009, '2025-06-23', '08:04:16', '08:04:17', 0, 0),
(28, 1009, '2025-06-24', '07:41:32', '08:14:44', 0, 0),
(29, 1, '2025-06-24', '12:56:57', '12:57:03', 0, 0),
(30, 2, '2025-06-24', '15:18:56', '15:19:01', 0, 0),
(31, 1009, '2025-06-25', '07:09:50', '07:09:58', 0, 0),
(32, 3, '2025-06-25', '09:32:48', '09:32:51', 0, 0);

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
(7, NULL, '10:00:00', '19:00:00', NULL, NULL);

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=38;

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=42;

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
-- AUTO_INCREMENT for table `time_logs`
--
ALTER TABLE `time_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=33;

--
-- AUTO_INCREMENT for table `vacation_leaves`
--
ALTER TABLE `vacation_leaves`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `work_schedules`
--
ALTER TABLE `work_schedules`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- Constraints for dumped tables
--

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
-- Constraints for table `time_logs`
--
ALTER TABLE `time_logs`
  ADD CONSTRAINT `time_logs_ibfk_1` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
