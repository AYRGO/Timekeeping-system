-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jul 14, 2025 at 09:25 AM
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
  `admin_name` varchar(100) NOT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `image` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `announcements`
--

INSERT INTO `announcements` (`announcement_id`, `content`, `admin_name`, `created_at`, `image`) VALUES
(15, 'test', 'Admin', '2025-07-01 11:34:49', 'uploads/img_68635759012194.25638175.png');

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
(22, 'Sean Justine', 'Francisco Mendoza', 'sean.mendoza@entrygroup.com.au', 'mendozaseanjustine@gmail.com', '0977 019 9064', 'Student Support - Marking', 'active', '2023-08-16 01:04:12', 'sean.mendoza', '71l6y', 'Entry Education', NULL, 'profile_22_1750917016.png'),
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
(63, 'Maria Ñina Dizon', 'Dollentes', 'nina.dollentes@entrygroup.com.au', 'marianinadollentes@gmail.com', '337-370-586-000', 'Accountant', 'active', '2025-04-29 14:02:24', 'Nina.dollentes', 'qp4i9', 'Entry Education', NULL, 'profile_63_1751009955.png'),
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
(1006, 'Cedrick', 'Arnigo', 'IT@resourcestaff.com.ph', 'cedrickarnigo1723@gmail.com', '09938642974', 'IT Support Specialist', 'active', '2025-05-27 23:09:46', 'Cedrick.Arnigo', 'Gr33n$$wRf', 'RSS', NULL, 'profile_1006_1751960992.png'),
(1007, 'Peach', 'Herrera', 'herrerafelicci@gmail.com', 'herrerafelicci@gmail.com', '0903323232', 'Admin', 'active', '2025-06-02 06:19:09', 'Peach.Herrera', 'Gh0920', 'RSS', NULL, 'profile_1007.jpg'),
(1009, 'Resty James', 'Nazareno', 'rjmanago@gmail.com', 'rjmanago@gmail.com', '09763659773', 'IT Intern', 'active', '2025-06-10 02:48:29', 'Kiras001', 'vosfows12', 'RSS', NULL, 'profile_1009_1752025010.png'),
(1013, 'John ', 'Mungcal', 'John.Mungcal@gmail.com', NULL, '123123123', 'IT Intern', 'active', '2025-07-14 03:34:27', 'John.Mungcal', '123456', NULL, NULL, NULL);

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
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `employee_checklist`
--

INSERT INTO `employee_checklist` (`id`, `employee_id`, `letter_offer`, `employment_contract`, `medical`, `nbi_clearance`, `diploma_tor`, `psa`, `sss`, `tin`, `philhealth`, `coe`, `created_at`, `updated_at`) VALUES
(1, 1, 'letter_offer_68746a875a728_images.jpg', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'coe_6874755e0c8b8_Smiley.svg.png', '2025-07-14 10:25:11', '2025-07-14 11:11:26'),
(2, 1009, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-07-14 11:22:29', '2025-07-14 11:22:29'),
(3, 1013, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-07-14 11:35:32', '2025-07-14 11:35:32');

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
  `leave_type` enum('sick','vacation','paternity','maternity','solo_parent','halfday','halfday_sick','lwop','bereavement') NOT NULL,
  `balance` decimal(5,2) DEFAULT 0.00,
  `year` year(4) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `leave_credits`
--

INSERT INTO `leave_credits` (`id`, `employee_id`, `leave_type`, `balance`, `year`) VALUES
(1, 1, '', 1.68, '2025'),
(2, 1, '', 5.00, '2025'),
(3, 1, '', 7.00, '2025'),
(4, 1, '', 2.00, '2025'),
(5, 1, '', 2.00, '2025'),
(6, 2, '', 1.68, '2025'),
(7, 2, '', 5.00, '2025'),
(8, 2, '', 7.00, '2025'),
(9, 2, '', 2.00, '2025'),
(10, 2, '', 2.00, '2025'),
(11, 3, '', 1.68, '2025'),
(12, 3, '', 5.00, '2025'),
(13, 3, '', 7.00, '2025'),
(14, 3, '', 2.00, '2025'),
(15, 3, '', 2.00, '2025'),
(16, 4, '', 1.68, '2025'),
(17, 4, '', 5.00, '2025'),
(18, 4, '', 7.00, '2025'),
(19, 4, '', 2.00, '2025'),
(20, 4, '', 2.00, '2025'),
(21, 5, '', 1.68, '2025'),
(22, 5, '', 5.00, '2025'),
(23, 5, '', 7.00, '2025'),
(24, 5, '', 2.00, '2025'),
(25, 5, '', 2.00, '2025'),
(26, 6, '', 1.68, '2025'),
(27, 6, '', 5.00, '2025'),
(28, 6, '', 7.00, '2025'),
(29, 6, '', 2.00, '2025'),
(30, 6, '', 2.00, '2025'),
(31, 7, '', 1.68, '2025'),
(32, 7, '', 5.00, '2025'),
(33, 7, '', 7.00, '2025'),
(34, 7, '', 2.00, '2025'),
(35, 7, '', 2.00, '2025'),
(36, 8, '', 1.68, '2025'),
(37, 8, '', 5.00, '2025'),
(38, 8, '', 7.00, '2025'),
(39, 8, '', 2.00, '2025'),
(40, 8, '', 2.00, '2025'),
(41, 9, '', 1.68, '2025'),
(42, 9, '', 5.00, '2025'),
(43, 9, '', 7.00, '2025'),
(44, 9, '', 2.00, '2025'),
(45, 9, '', 2.00, '2025'),
(46, 10, '', 1.68, '2025'),
(47, 10, '', 5.00, '2025'),
(48, 10, '', 7.00, '2025'),
(49, 10, '', 2.00, '2025'),
(50, 10, '', 2.00, '2025'),
(51, 11, '', 1.68, '2025'),
(52, 11, '', 5.00, '2025'),
(53, 11, '', 7.00, '2025'),
(54, 11, '', 2.00, '2025'),
(55, 11, '', 2.00, '2025'),
(56, 12, '', 1.68, '2025'),
(57, 12, '', 5.00, '2025'),
(58, 12, '', 7.00, '2025'),
(59, 12, '', 2.00, '2025'),
(60, 12, '', 2.00, '2025'),
(61, 13, '', 1.68, '2025'),
(62, 13, '', 5.00, '2025'),
(63, 13, '', 7.00, '2025'),
(64, 13, '', 2.00, '2025'),
(65, 13, '', 2.00, '2025'),
(66, 15, '', 1.68, '2025'),
(67, 15, '', 5.00, '2025'),
(68, 15, '', 7.00, '2025'),
(69, 15, '', 2.00, '2025'),
(70, 15, '', 2.00, '2025'),
(71, 16, '', 1.68, '2025'),
(72, 16, '', 5.00, '2025'),
(73, 16, '', 7.00, '2025'),
(74, 16, '', 2.00, '2025'),
(75, 16, '', 2.00, '2025'),
(76, 17, '', 1.68, '2025'),
(77, 17, '', 5.00, '2025'),
(78, 17, '', 7.00, '2025'),
(79, 17, '', 2.00, '2025'),
(80, 17, '', 2.00, '2025'),
(81, 18, '', 1.68, '2025'),
(82, 18, '', 5.00, '2025'),
(83, 18, '', 7.00, '2025'),
(84, 18, '', 2.00, '2025'),
(85, 18, '', 2.00, '2025'),
(86, 20, '', 1.68, '2025'),
(87, 20, '', 5.00, '2025'),
(88, 20, '', 7.00, '2025'),
(89, 20, '', 2.00, '2025'),
(90, 20, '', 2.00, '2025'),
(91, 21, '', 1.68, '2025'),
(92, 21, '', 5.00, '2025'),
(93, 21, '', 7.00, '2025'),
(94, 21, '', 2.00, '2025'),
(95, 21, '', 2.00, '2025'),
(96, 22, '', 1.68, '2025'),
(97, 22, '', 5.00, '2025'),
(98, 22, '', 7.00, '2025'),
(99, 22, '', 2.00, '2025'),
(100, 22, '', 2.00, '2025'),
(101, 24, '', 1.68, '2025'),
(102, 24, '', 5.00, '2025'),
(103, 24, '', 7.00, '2025'),
(104, 24, '', 2.00, '2025'),
(105, 24, '', 2.00, '2025'),
(106, 25, '', 1.68, '2025'),
(107, 25, '', 5.00, '2025'),
(108, 25, '', 7.00, '2025'),
(109, 25, '', 2.00, '2025'),
(110, 25, '', 2.00, '2025'),
(111, 26, '', 1.68, '2025'),
(112, 26, '', 5.00, '2025'),
(113, 26, '', 7.00, '2025'),
(114, 26, '', 2.00, '2025'),
(115, 26, '', 2.00, '2025'),
(116, 27, '', 1.68, '2025'),
(117, 27, '', 5.00, '2025'),
(118, 27, '', 7.00, '2025'),
(119, 27, '', 2.00, '2025'),
(120, 27, '', 2.00, '2025'),
(121, 28, '', 1.68, '2025'),
(122, 28, '', 5.00, '2025'),
(123, 28, '', 7.00, '2025'),
(124, 28, '', 2.00, '2025'),
(125, 28, '', 2.00, '2025'),
(126, 29, '', 1.68, '2025'),
(127, 29, '', 5.00, '2025'),
(128, 29, '', 7.00, '2025'),
(129, 29, '', 2.00, '2025'),
(130, 29, '', 2.00, '2025'),
(131, 30, '', 1.68, '2025'),
(132, 30, '', 5.00, '2025'),
(133, 30, '', 7.00, '2025'),
(134, 30, '', 2.00, '2025'),
(135, 30, '', 2.00, '2025'),
(136, 31, '', 1.68, '2025'),
(137, 31, '', 5.00, '2025'),
(138, 31, '', 7.00, '2025'),
(139, 31, '', 2.00, '2025'),
(140, 31, '', 2.00, '2025'),
(141, 33, '', 1.68, '2025'),
(142, 33, '', 5.00, '2025'),
(143, 33, '', 7.00, '2025'),
(144, 33, '', 2.00, '2025'),
(145, 33, '', 2.00, '2025'),
(146, 34, '', 1.68, '2025'),
(147, 34, '', 5.00, '2025'),
(148, 34, '', 7.00, '2025'),
(149, 34, '', 2.00, '2025'),
(150, 34, '', 2.00, '2025'),
(151, 35, '', 1.68, '2025'),
(152, 35, '', 5.00, '2025'),
(153, 35, '', 7.00, '2025'),
(154, 35, '', 2.00, '2025'),
(155, 35, '', 2.00, '2025'),
(156, 36, '', 1.68, '2025'),
(157, 36, '', 5.00, '2025'),
(158, 36, '', 7.00, '2025'),
(159, 36, '', 2.00, '2025'),
(160, 36, '', 2.00, '2025'),
(161, 37, '', 1.68, '2025'),
(162, 37, '', 5.00, '2025'),
(163, 37, '', 7.00, '2025'),
(164, 37, '', 2.00, '2025'),
(165, 37, '', 2.00, '2025'),
(166, 38, '', 1.68, '2025'),
(167, 38, '', 5.00, '2025'),
(168, 38, '', 7.00, '2025'),
(169, 38, '', 2.00, '2025'),
(170, 38, '', 2.00, '2025'),
(171, 41, '', 1.68, '2025'),
(172, 41, '', 5.00, '2025'),
(173, 41, '', 7.00, '2025'),
(174, 41, '', 2.00, '2025'),
(175, 41, '', 2.00, '2025'),
(176, 43, '', 1.68, '2025'),
(177, 43, '', 5.00, '2025'),
(178, 43, '', 7.00, '2025'),
(179, 43, '', 2.00, '2025'),
(180, 43, '', 2.00, '2025'),
(181, 46, '', 1.68, '2025'),
(182, 46, '', 5.00, '2025'),
(183, 46, '', 7.00, '2025'),
(184, 46, '', 2.00, '2025'),
(185, 46, '', 2.00, '2025'),
(186, 47, '', 1.68, '2025'),
(187, 47, '', 5.00, '2025'),
(188, 47, '', 7.00, '2025'),
(189, 47, '', 2.00, '2025'),
(190, 47, '', 2.00, '2025'),
(191, 49, '', 1.68, '2025'),
(192, 49, '', 5.00, '2025'),
(193, 49, '', 7.00, '2025'),
(194, 49, '', 2.00, '2025'),
(195, 49, '', 2.00, '2025'),
(196, 50, '', 1.68, '2025'),
(197, 50, '', 5.00, '2025'),
(198, 50, '', 7.00, '2025'),
(199, 50, '', 2.00, '2025'),
(200, 50, '', 2.00, '2025'),
(201, 52, '', 1.68, '2025'),
(202, 52, '', 5.00, '2025'),
(203, 52, '', 7.00, '2025'),
(204, 52, '', 2.00, '2025'),
(205, 52, '', 2.00, '2025'),
(206, 53, '', 1.68, '2025'),
(207, 53, '', 5.00, '2025'),
(208, 53, '', 7.00, '2025'),
(209, 53, '', 2.00, '2025'),
(210, 53, '', 2.00, '2025'),
(211, 55, '', 1.68, '2025'),
(212, 55, '', 5.00, '2025'),
(213, 55, '', 7.00, '2025'),
(214, 55, '', 2.00, '2025'),
(215, 55, '', 2.00, '2025'),
(216, 56, '', 1.68, '2025'),
(217, 56, '', 5.00, '2025'),
(218, 56, '', 7.00, '2025'),
(219, 56, '', 2.00, '2025'),
(220, 56, '', 2.00, '2025'),
(221, 57, '', 1.68, '2025'),
(222, 57, '', 5.00, '2025'),
(223, 57, '', 7.00, '2025'),
(224, 57, '', 2.00, '2025'),
(225, 57, '', 2.00, '2025'),
(226, 58, '', 1.68, '2025'),
(227, 58, '', 5.00, '2025'),
(228, 58, '', 7.00, '2025'),
(229, 58, '', 2.00, '2025'),
(230, 58, '', 2.00, '2025'),
(231, 59, '', 1.68, '2025'),
(232, 59, '', 5.00, '2025'),
(233, 59, '', 7.00, '2025'),
(234, 59, '', 2.00, '2025'),
(235, 59, '', 2.00, '2025'),
(236, 60, '', 1.68, '2025'),
(237, 60, '', 5.00, '2025'),
(238, 60, '', 7.00, '2025'),
(239, 60, '', 2.00, '2025'),
(240, 60, '', 2.00, '2025'),
(241, 61, '', 1.68, '2025'),
(242, 61, '', 5.00, '2025'),
(243, 61, '', 7.00, '2025'),
(244, 61, '', 2.00, '2025'),
(245, 61, '', 2.00, '2025'),
(246, 62, '', 1.68, '2025'),
(247, 62, '', 5.00, '2025'),
(248, 62, '', 7.00, '2025'),
(249, 62, '', 2.00, '2025'),
(250, 62, '', 2.00, '2025'),
(251, 63, '', 1.68, '2025'),
(252, 63, '', 5.00, '2025'),
(253, 63, '', 7.00, '2025'),
(254, 63, '', 2.00, '2025'),
(255, 63, '', 2.00, '2025'),
(256, 64, '', 1.68, '2025'),
(257, 64, '', 5.00, '2025'),
(258, 64, '', 7.00, '2025'),
(259, 64, '', 2.00, '2025'),
(260, 64, '', 2.00, '2025'),
(261, 65, '', 1.68, '2025'),
(262, 65, '', 5.00, '2025'),
(263, 65, '', 7.00, '2025'),
(264, 65, '', 2.00, '2025'),
(265, 65, '', 2.00, '2025'),
(266, 66, '', 1.68, '2025'),
(267, 66, '', 5.00, '2025'),
(268, 66, '', 7.00, '2025'),
(269, 66, '', 2.00, '2025'),
(270, 66, '', 2.00, '2025'),
(271, 67, '', 1.68, '2025'),
(272, 67, '', 5.00, '2025'),
(273, 67, '', 7.00, '2025'),
(274, 67, '', 2.00, '2025'),
(275, 67, '', 2.00, '2025'),
(276, 68, '', 1.68, '2025'),
(277, 68, '', 5.00, '2025'),
(278, 68, '', 7.00, '2025'),
(279, 68, '', 2.00, '2025'),
(280, 68, '', 2.00, '2025'),
(281, 69, '', 1.68, '2025'),
(282, 69, '', 5.00, '2025'),
(283, 69, '', 7.00, '2025'),
(284, 69, '', 2.00, '2025'),
(285, 69, '', 2.00, '2025'),
(286, 70, '', 1.68, '2025'),
(287, 70, '', 5.00, '2025'),
(288, 70, '', 7.00, '2025'),
(289, 70, '', 2.00, '2025'),
(290, 70, '', 2.00, '2025'),
(291, 71, '', 1.68, '2025'),
(292, 71, '', 5.00, '2025'),
(293, 71, '', 7.00, '2025'),
(294, 71, '', 2.00, '2025'),
(295, 71, '', 2.00, '2025'),
(296, 72, '', 1.68, '2025'),
(297, 72, '', 5.00, '2025'),
(298, 72, '', 7.00, '2025'),
(299, 72, '', 2.00, '2025'),
(300, 72, '', 2.00, '2025'),
(301, 1002, '', 1.68, '2025'),
(302, 1002, '', 5.00, '2025'),
(303, 1002, '', 7.00, '2025'),
(304, 1002, '', 2.00, '2025'),
(305, 1002, '', 2.00, '2025'),
(306, 1003, '', 1.68, '2025'),
(307, 1003, '', 5.00, '2025'),
(308, 1003, '', 7.00, '2025'),
(309, 1003, '', 2.00, '2025'),
(310, 1003, '', 2.00, '2025'),
(311, 1004, '', 1.68, '2025'),
(312, 1004, '', 5.00, '2025'),
(313, 1004, '', 7.00, '2025'),
(314, 1004, '', 2.00, '2025'),
(315, 1004, '', 2.00, '2025'),
(316, 1005, '', 1.68, '2025'),
(317, 1005, '', 5.00, '2025'),
(318, 1005, '', 7.00, '2025'),
(319, 1005, '', 2.00, '2025'),
(320, 1005, '', 2.00, '2025'),
(321, 1006, '', 1.68, '2025'),
(322, 1006, '', 5.00, '2025'),
(323, 1006, '', 7.00, '2025'),
(324, 1006, '', 2.00, '2025'),
(325, 1006, '', 2.00, '2025'),
(326, 1007, '', 1.68, '2025'),
(327, 1007, '', 5.00, '2025'),
(328, 1007, '', 7.00, '2025'),
(329, 1007, '', 2.00, '2025'),
(330, 1007, '', 2.00, '2025'),
(331, 1009, '', 1.68, '2025'),
(332, 1009, '', 5.00, '2025'),
(333, 1009, '', 7.00, '2025'),
(334, 1009, '', 2.00, '2025'),
(335, 1009, '', 2.00, '2025'),
(336, 1, 'sick', 5.00, '2025'),
(337, 2, 'sick', 5.00, '2025'),
(338, 3, 'sick', 5.00, '2025'),
(339, 4, 'sick', 5.00, '2025'),
(340, 5, 'sick', 5.00, '2025'),
(341, 6, 'sick', 5.00, '2025'),
(342, 7, 'sick', 5.00, '2025'),
(343, 8, 'sick', 5.00, '2025'),
(344, 9, 'sick', 5.00, '2025'),
(345, 10, 'sick', 5.00, '2025'),
(346, 11, 'sick', 5.00, '2025'),
(347, 12, 'sick', 5.00, '2025'),
(348, 13, 'sick', 5.00, '2025'),
(349, 15, 'sick', 5.00, '2025'),
(350, 16, 'sick', 5.00, '2025'),
(351, 17, 'sick', 5.00, '2025'),
(352, 18, 'sick', 5.00, '2025'),
(353, 20, 'sick', 5.00, '2025'),
(354, 21, 'sick', 5.00, '2025'),
(355, 22, 'sick', 5.00, '2025'),
(356, 24, 'sick', 5.00, '2025'),
(357, 25, 'sick', 5.00, '2025'),
(358, 26, 'sick', 5.00, '2025'),
(359, 27, 'sick', 5.00, '2025'),
(360, 28, 'sick', 5.00, '2025'),
(361, 29, 'sick', 5.00, '2025'),
(362, 30, 'sick', 5.00, '2025'),
(363, 31, 'sick', 5.00, '2025'),
(364, 33, 'sick', 5.00, '2025'),
(365, 34, 'sick', 5.00, '2025'),
(366, 35, 'sick', 5.00, '2025'),
(367, 36, 'sick', 5.00, '2025'),
(368, 37, 'sick', 5.00, '2025'),
(369, 38, 'sick', 5.00, '2025'),
(370, 41, 'sick', 5.00, '2025'),
(371, 43, 'sick', 5.00, '2025'),
(372, 46, 'sick', 5.00, '2025'),
(373, 47, 'sick', 5.00, '2025'),
(374, 49, 'sick', 5.00, '2025'),
(375, 50, 'sick', 5.00, '2025'),
(376, 52, 'sick', 5.00, '2025'),
(377, 53, 'sick', 5.00, '2025'),
(378, 55, 'sick', 5.00, '2025'),
(379, 56, 'sick', 5.00, '2025'),
(380, 57, 'sick', 5.00, '2025'),
(381, 58, 'sick', 5.00, '2025'),
(382, 59, 'sick', 5.00, '2025'),
(383, 60, 'sick', 5.00, '2025'),
(384, 61, 'sick', 5.00, '2025'),
(385, 62, 'sick', 5.00, '2025'),
(386, 63, 'sick', 5.00, '2025'),
(387, 64, 'sick', 5.00, '2025'),
(388, 65, 'sick', 5.00, '2025'),
(389, 66, 'sick', 5.00, '2025'),
(390, 67, 'sick', 5.00, '2025'),
(391, 68, 'sick', 5.00, '2025'),
(392, 69, 'sick', 5.00, '2025'),
(393, 70, 'sick', 5.00, '2025'),
(394, 71, 'sick', 5.00, '2025'),
(395, 72, 'sick', 5.00, '2025'),
(396, 1002, 'sick', 5.00, '2025'),
(397, 1003, 'sick', 5.00, '2025'),
(398, 1004, 'sick', 5.00, '2025'),
(399, 1005, 'sick', 5.00, '2025'),
(400, 1006, 'sick', 5.00, '2025'),
(401, 1007, 'sick', 5.00, '2025'),
(402, 1009, 'sick', 5.00, '2025'),
(403, 1, 'vacation', 15.00, '2025'),
(404, 2, 'vacation', 15.00, '2025'),
(405, 3, 'vacation', 15.00, '2025'),
(406, 4, 'vacation', 15.00, '2025'),
(407, 5, 'vacation', 15.00, '2025'),
(408, 6, 'vacation', 15.00, '2025'),
(409, 7, 'vacation', 15.00, '2025'),
(410, 8, 'vacation', 15.00, '2025'),
(411, 9, 'vacation', 15.00, '2025'),
(412, 10, 'vacation', 15.00, '2025'),
(413, 11, 'vacation', 15.00, '2025'),
(414, 12, 'vacation', 15.00, '2025'),
(415, 13, 'vacation', 15.00, '2025'),
(416, 15, 'vacation', 15.00, '2025'),
(417, 16, 'vacation', 15.00, '2025'),
(418, 17, 'vacation', 15.00, '2025'),
(419, 18, 'vacation', 15.00, '2025'),
(420, 20, 'vacation', 15.00, '2025'),
(421, 21, 'vacation', 15.00, '2025'),
(422, 22, 'vacation', 15.00, '2025'),
(423, 24, 'vacation', 15.00, '2025'),
(424, 25, 'vacation', 15.00, '2025'),
(425, 26, 'vacation', 15.00, '2025'),
(426, 27, 'vacation', 15.00, '2025'),
(427, 28, 'vacation', 15.00, '2025'),
(428, 29, 'vacation', 15.00, '2025'),
(429, 30, 'vacation', 15.00, '2025'),
(430, 31, 'vacation', 15.00, '2025'),
(431, 33, 'vacation', 15.00, '2025'),
(432, 34, 'vacation', 15.00, '2025'),
(433, 35, 'vacation', 15.00, '2025'),
(434, 36, 'vacation', 15.00, '2025'),
(435, 37, 'vacation', 15.00, '2025'),
(436, 38, 'vacation', 15.00, '2025'),
(437, 41, 'vacation', 15.00, '2025'),
(438, 43, 'vacation', 15.00, '2025'),
(439, 46, 'vacation', 15.00, '2025'),
(440, 47, 'vacation', 15.00, '2025'),
(441, 49, 'vacation', 15.00, '2025'),
(442, 50, 'vacation', 15.00, '2025'),
(443, 52, 'vacation', 15.00, '2025'),
(444, 53, 'vacation', 15.00, '2025'),
(445, 55, 'vacation', 15.00, '2025'),
(446, 56, 'vacation', 15.00, '2025'),
(447, 57, 'vacation', 15.00, '2025'),
(448, 58, 'vacation', 15.00, '2025'),
(449, 59, 'vacation', 15.00, '2025'),
(450, 60, 'vacation', 15.00, '2025'),
(451, 61, 'vacation', 15.00, '2025'),
(452, 62, 'vacation', 15.00, '2025'),
(453, 63, 'vacation', 15.00, '2025'),
(454, 64, 'vacation', 15.00, '2025'),
(455, 65, 'vacation', 15.00, '2025'),
(456, 66, 'vacation', 15.00, '2025'),
(457, 67, 'vacation', 15.00, '2025'),
(458, 68, 'vacation', 15.00, '2025'),
(459, 69, 'vacation', 15.00, '2025'),
(460, 70, 'vacation', 15.00, '2025'),
(461, 71, 'vacation', 15.00, '2025'),
(462, 72, 'vacation', 15.00, '2025'),
(463, 1002, 'vacation', 15.00, '2025'),
(464, 1003, 'vacation', 15.00, '2025'),
(465, 1004, 'vacation', 15.00, '2025'),
(466, 1005, 'vacation', 15.00, '2025'),
(467, 1006, 'vacation', 15.00, '2025'),
(468, 1007, 'vacation', 15.00, '2025'),
(469, 1009, 'vacation', 15.00, '2025'),
(470, 1, 'paternity', 7.00, '2025'),
(471, 2, 'paternity', 7.00, '2025'),
(472, 3, 'paternity', 7.00, '2025'),
(473, 4, 'paternity', 7.00, '2025'),
(474, 5, 'paternity', 7.00, '2025'),
(475, 6, 'paternity', 7.00, '2025'),
(476, 7, 'paternity', 7.00, '2025'),
(477, 8, 'paternity', 7.00, '2025'),
(478, 9, 'paternity', 7.00, '2025'),
(479, 10, 'paternity', 7.00, '2025'),
(480, 11, 'paternity', 7.00, '2025'),
(481, 12, 'paternity', 7.00, '2025'),
(482, 13, 'paternity', 7.00, '2025'),
(483, 15, 'paternity', 7.00, '2025'),
(484, 16, 'paternity', 7.00, '2025'),
(485, 17, 'paternity', 7.00, '2025'),
(486, 18, 'paternity', 7.00, '2025'),
(487, 20, 'paternity', 7.00, '2025'),
(488, 21, 'paternity', 7.00, '2025'),
(489, 22, 'paternity', 7.00, '2025'),
(490, 24, 'paternity', 7.00, '2025'),
(491, 25, 'paternity', 7.00, '2025'),
(492, 26, 'paternity', 7.00, '2025'),
(493, 27, 'paternity', 7.00, '2025'),
(494, 28, 'paternity', 7.00, '2025'),
(495, 29, 'paternity', 7.00, '2025'),
(496, 30, 'paternity', 7.00, '2025'),
(497, 31, 'paternity', 7.00, '2025'),
(498, 33, 'paternity', 7.00, '2025'),
(499, 34, 'paternity', 7.00, '2025'),
(500, 35, 'paternity', 7.00, '2025'),
(501, 36, 'paternity', 7.00, '2025'),
(502, 37, 'paternity', 7.00, '2025'),
(503, 38, 'paternity', 7.00, '2025'),
(504, 41, 'paternity', 7.00, '2025'),
(505, 43, 'paternity', 7.00, '2025'),
(506, 46, 'paternity', 7.00, '2025'),
(507, 47, 'paternity', 7.00, '2025'),
(508, 49, 'paternity', 7.00, '2025'),
(509, 50, 'paternity', 7.00, '2025'),
(510, 52, 'paternity', 7.00, '2025'),
(511, 53, 'paternity', 7.00, '2025'),
(512, 55, 'paternity', 7.00, '2025'),
(513, 56, 'paternity', 7.00, '2025'),
(514, 57, 'paternity', 7.00, '2025'),
(515, 58, 'paternity', 7.00, '2025'),
(516, 59, 'paternity', 7.00, '2025'),
(517, 60, 'paternity', 7.00, '2025'),
(518, 61, 'paternity', 7.00, '2025'),
(519, 62, 'paternity', 7.00, '2025'),
(520, 63, 'paternity', 7.00, '2025'),
(521, 64, 'paternity', 7.00, '2025'),
(522, 65, 'paternity', 7.00, '2025'),
(523, 66, 'paternity', 7.00, '2025'),
(524, 67, 'paternity', 7.00, '2025'),
(525, 68, 'paternity', 7.00, '2025'),
(526, 69, 'paternity', 7.00, '2025'),
(527, 70, 'paternity', 7.00, '2025'),
(528, 71, 'paternity', 7.00, '2025'),
(529, 72, 'paternity', 7.00, '2025'),
(530, 1002, 'paternity', 7.00, '2025'),
(531, 1003, 'paternity', 7.00, '2025'),
(532, 1004, 'paternity', 7.00, '2025'),
(533, 1005, 'paternity', 7.00, '2025'),
(534, 1006, 'paternity', 7.00, '2025'),
(535, 1007, 'paternity', 7.00, '2025'),
(536, 1009, 'paternity', 7.00, '2025'),
(537, 1, 'maternity', 105.00, '2025'),
(538, 2, 'maternity', 105.00, '2025'),
(539, 3, 'maternity', 105.00, '2025'),
(540, 4, 'maternity', 105.00, '2025'),
(541, 5, 'maternity', 105.00, '2025'),
(542, 6, 'maternity', 105.00, '2025'),
(543, 7, 'maternity', 105.00, '2025'),
(544, 8, 'maternity', 105.00, '2025'),
(545, 9, 'maternity', 105.00, '2025'),
(546, 10, 'maternity', 105.00, '2025'),
(547, 11, 'maternity', 105.00, '2025'),
(548, 12, 'maternity', 105.00, '2025'),
(549, 13, 'maternity', 105.00, '2025'),
(550, 15, 'maternity', 105.00, '2025'),
(551, 16, 'maternity', 105.00, '2025'),
(552, 17, 'maternity', 105.00, '2025'),
(553, 18, 'maternity', 105.00, '2025'),
(554, 20, 'maternity', 105.00, '2025'),
(555, 21, 'maternity', 105.00, '2025'),
(556, 22, 'maternity', 105.00, '2025'),
(557, 24, 'maternity', 105.00, '2025'),
(558, 25, 'maternity', 105.00, '2025'),
(559, 26, 'maternity', 105.00, '2025'),
(560, 27, 'maternity', 105.00, '2025'),
(561, 28, 'maternity', 105.00, '2025'),
(562, 29, 'maternity', 105.00, '2025'),
(563, 30, 'maternity', 105.00, '2025'),
(564, 31, 'maternity', 105.00, '2025'),
(565, 33, 'maternity', 105.00, '2025'),
(566, 34, 'maternity', 105.00, '2025'),
(567, 35, 'maternity', 105.00, '2025'),
(568, 36, 'maternity', 105.00, '2025'),
(569, 37, 'maternity', 105.00, '2025'),
(570, 38, 'maternity', 105.00, '2025'),
(571, 41, 'maternity', 105.00, '2025'),
(572, 43, 'maternity', 105.00, '2025'),
(573, 46, 'maternity', 105.00, '2025'),
(574, 47, 'maternity', 105.00, '2025'),
(575, 49, 'maternity', 105.00, '2025'),
(576, 50, 'maternity', 105.00, '2025'),
(577, 52, 'maternity', 105.00, '2025'),
(578, 53, 'maternity', 105.00, '2025'),
(579, 55, 'maternity', 105.00, '2025'),
(580, 56, 'maternity', 105.00, '2025'),
(581, 57, 'maternity', 105.00, '2025'),
(582, 58, 'maternity', 105.00, '2025'),
(583, 59, 'maternity', 105.00, '2025'),
(584, 60, 'maternity', 105.00, '2025'),
(585, 61, 'maternity', 105.00, '2025'),
(586, 62, 'maternity', 105.00, '2025'),
(587, 63, 'maternity', 105.00, '2025'),
(588, 64, 'maternity', 105.00, '2025'),
(589, 65, 'maternity', 105.00, '2025'),
(590, 66, 'maternity', 105.00, '2025'),
(591, 67, 'maternity', 105.00, '2025'),
(592, 68, 'maternity', 105.00, '2025'),
(593, 69, 'maternity', 105.00, '2025'),
(594, 70, 'maternity', 105.00, '2025'),
(595, 71, 'maternity', 105.00, '2025'),
(596, 72, 'maternity', 105.00, '2025'),
(597, 1002, 'maternity', 105.00, '2025'),
(598, 1003, 'maternity', 105.00, '2025'),
(599, 1004, 'maternity', 105.00, '2025'),
(600, 1005, 'maternity', 105.00, '2025'),
(601, 1006, 'maternity', 105.00, '2025'),
(602, 1007, 'maternity', 105.00, '2025'),
(603, 1009, 'maternity', 105.00, '2025'),
(604, 1, 'solo_parent', 6.00, '2025'),
(605, 2, 'solo_parent', 6.00, '2025'),
(606, 3, 'solo_parent', 6.00, '2025'),
(607, 4, 'solo_parent', 6.00, '2025'),
(608, 5, 'solo_parent', 6.00, '2025'),
(609, 6, 'solo_parent', 6.00, '2025'),
(610, 7, 'solo_parent', 6.00, '2025'),
(611, 8, 'solo_parent', 6.00, '2025'),
(612, 9, 'solo_parent', 6.00, '2025'),
(613, 10, 'solo_parent', 6.00, '2025'),
(614, 11, 'solo_parent', 6.00, '2025'),
(615, 12, 'solo_parent', 6.00, '2025'),
(616, 13, 'solo_parent', 6.00, '2025'),
(617, 15, 'solo_parent', 6.00, '2025'),
(618, 16, 'solo_parent', 6.00, '2025'),
(619, 17, 'solo_parent', 6.00, '2025'),
(620, 18, 'solo_parent', 6.00, '2025'),
(621, 20, 'solo_parent', 6.00, '2025'),
(622, 21, 'solo_parent', 6.00, '2025'),
(623, 22, 'solo_parent', 6.00, '2025'),
(624, 24, 'solo_parent', 6.00, '2025'),
(625, 25, 'solo_parent', 6.00, '2025'),
(626, 26, 'solo_parent', 6.00, '2025'),
(627, 27, 'solo_parent', 6.00, '2025'),
(628, 28, 'solo_parent', 6.00, '2025'),
(629, 29, 'solo_parent', 6.00, '2025'),
(630, 30, 'solo_parent', 6.00, '2025'),
(631, 31, 'solo_parent', 6.00, '2025'),
(632, 33, 'solo_parent', 6.00, '2025'),
(633, 34, 'solo_parent', 6.00, '2025'),
(634, 35, 'solo_parent', 6.00, '2025'),
(635, 36, 'solo_parent', 6.00, '2025'),
(636, 37, 'solo_parent', 6.00, '2025'),
(637, 38, 'solo_parent', 6.00, '2025'),
(638, 41, 'solo_parent', 6.00, '2025'),
(639, 43, 'solo_parent', 6.00, '2025'),
(640, 46, 'solo_parent', 6.00, '2025'),
(641, 47, 'solo_parent', 6.00, '2025'),
(642, 49, 'solo_parent', 6.00, '2025'),
(643, 50, 'solo_parent', 6.00, '2025'),
(644, 52, 'solo_parent', 6.00, '2025'),
(645, 53, 'solo_parent', 6.00, '2025'),
(646, 55, 'solo_parent', 6.00, '2025'),
(647, 56, 'solo_parent', 6.00, '2025'),
(648, 57, 'solo_parent', 6.00, '2025'),
(649, 58, 'solo_parent', 6.00, '2025'),
(650, 59, 'solo_parent', 6.00, '2025'),
(651, 60, 'solo_parent', 6.00, '2025'),
(652, 61, 'solo_parent', 6.00, '2025'),
(653, 62, 'solo_parent', 6.00, '2025'),
(654, 63, 'solo_parent', 6.00, '2025'),
(655, 64, 'solo_parent', 6.00, '2025'),
(656, 65, 'solo_parent', 6.00, '2025'),
(657, 66, 'solo_parent', 6.00, '2025'),
(658, 67, 'solo_parent', 6.00, '2025'),
(659, 68, 'solo_parent', 6.00, '2025'),
(660, 69, 'solo_parent', 6.00, '2025'),
(661, 70, 'solo_parent', 6.00, '2025'),
(662, 71, 'solo_parent', 6.00, '2025'),
(663, 72, 'solo_parent', 6.00, '2025'),
(664, 1002, 'solo_parent', 6.00, '2025'),
(665, 1003, 'solo_parent', 6.00, '2025'),
(666, 1004, 'solo_parent', 6.00, '2025'),
(667, 1005, 'solo_parent', 6.00, '2025'),
(668, 1006, 'solo_parent', 6.00, '2025'),
(669, 1007, 'solo_parent', 6.00, '2025'),
(670, 1009, 'solo_parent', 6.00, '2025'),
(671, 1, 'halfday', 0.00, '2025'),
(672, 2, 'halfday', 0.00, '2025'),
(673, 3, 'halfday', 0.00, '2025'),
(674, 4, 'halfday', 0.00, '2025'),
(675, 5, 'halfday', 0.00, '2025'),
(676, 6, 'halfday', 0.00, '2025'),
(677, 7, 'halfday', 0.00, '2025'),
(678, 8, 'halfday', 0.00, '2025'),
(679, 9, 'halfday', 0.00, '2025'),
(680, 10, 'halfday', 0.00, '2025'),
(681, 11, 'halfday', 0.00, '2025'),
(682, 12, 'halfday', 0.00, '2025'),
(683, 13, 'halfday', 0.00, '2025'),
(684, 15, 'halfday', 0.00, '2025'),
(685, 16, 'halfday', 0.00, '2025'),
(686, 17, 'halfday', 0.00, '2025'),
(687, 18, 'halfday', 0.00, '2025'),
(688, 20, 'halfday', 0.00, '2025'),
(689, 21, 'halfday', 0.00, '2025'),
(690, 22, 'halfday', 0.00, '2025'),
(691, 24, 'halfday', 0.00, '2025'),
(692, 25, 'halfday', 0.00, '2025'),
(693, 26, 'halfday', 0.00, '2025'),
(694, 27, 'halfday', 0.00, '2025'),
(695, 28, 'halfday', 0.00, '2025'),
(696, 29, 'halfday', 0.00, '2025'),
(697, 30, 'halfday', 0.00, '2025'),
(698, 31, 'halfday', 0.00, '2025'),
(699, 33, 'halfday', 0.00, '2025'),
(700, 34, 'halfday', 0.00, '2025'),
(701, 35, 'halfday', 0.00, '2025'),
(702, 36, 'halfday', 0.00, '2025'),
(703, 37, 'halfday', 0.00, '2025'),
(704, 38, 'halfday', 0.00, '2025'),
(705, 41, 'halfday', 0.00, '2025'),
(706, 43, 'halfday', 0.00, '2025'),
(707, 46, 'halfday', 0.00, '2025'),
(708, 47, 'halfday', 0.00, '2025'),
(709, 49, 'halfday', 0.00, '2025'),
(710, 50, 'halfday', 0.00, '2025'),
(711, 52, 'halfday', 0.00, '2025'),
(712, 53, 'halfday', 0.00, '2025'),
(713, 55, 'halfday', 0.00, '2025'),
(714, 56, 'halfday', 0.00, '2025'),
(715, 57, 'halfday', 0.00, '2025'),
(716, 58, 'halfday', 0.00, '2025'),
(717, 59, 'halfday', 0.00, '2025'),
(718, 60, 'halfday', 0.00, '2025'),
(719, 61, 'halfday', 0.00, '2025'),
(720, 62, 'halfday', 0.00, '2025'),
(721, 63, 'halfday', 0.00, '2025'),
(722, 64, 'halfday', 0.00, '2025'),
(723, 65, 'halfday', 0.00, '2025'),
(724, 66, 'halfday', 0.00, '2025'),
(725, 67, 'halfday', 0.00, '2025'),
(726, 68, 'halfday', 0.00, '2025'),
(727, 69, 'halfday', 0.00, '2025'),
(728, 70, 'halfday', 0.00, '2025'),
(729, 71, 'halfday', 0.00, '2025'),
(730, 72, 'halfday', 0.00, '2025'),
(731, 1002, 'halfday', 0.00, '2025'),
(732, 1003, 'halfday', 0.00, '2025'),
(733, 1004, 'halfday', 0.00, '2025'),
(734, 1005, 'halfday', 0.00, '2025'),
(735, 1006, 'halfday', 0.00, '2025'),
(736, 1007, 'halfday', 0.00, '2025'),
(737, 1009, 'halfday', 0.00, '2025'),
(738, 1, 'halfday_sick', 0.00, '2025'),
(739, 2, 'halfday_sick', 0.00, '2025'),
(740, 3, 'halfday_sick', 0.00, '2025'),
(741, 4, 'halfday_sick', 0.00, '2025'),
(742, 5, 'halfday_sick', 0.00, '2025'),
(743, 6, 'halfday_sick', 0.00, '2025'),
(744, 7, 'halfday_sick', 0.00, '2025'),
(745, 8, 'halfday_sick', 0.00, '2025'),
(746, 9, 'halfday_sick', 0.00, '2025'),
(747, 10, 'halfday_sick', 0.00, '2025'),
(748, 11, 'halfday_sick', 0.00, '2025'),
(749, 12, 'halfday_sick', 0.00, '2025'),
(750, 13, 'halfday_sick', 0.00, '2025'),
(751, 15, 'halfday_sick', 0.00, '2025'),
(752, 16, 'halfday_sick', 0.00, '2025'),
(753, 17, 'halfday_sick', 0.00, '2025'),
(754, 18, 'halfday_sick', 0.00, '2025'),
(755, 20, 'halfday_sick', 0.00, '2025'),
(756, 21, 'halfday_sick', 0.00, '2025'),
(757, 22, 'halfday_sick', 0.00, '2025'),
(758, 24, 'halfday_sick', 0.00, '2025'),
(759, 25, 'halfday_sick', 0.00, '2025'),
(760, 26, 'halfday_sick', 0.00, '2025'),
(761, 27, 'halfday_sick', 0.00, '2025'),
(762, 28, 'halfday_sick', 0.00, '2025'),
(763, 29, 'halfday_sick', 0.00, '2025'),
(764, 30, 'halfday_sick', 0.00, '2025'),
(765, 31, 'halfday_sick', 0.00, '2025'),
(766, 33, 'halfday_sick', 0.00, '2025'),
(767, 34, 'halfday_sick', 0.00, '2025'),
(768, 35, 'halfday_sick', 0.00, '2025'),
(769, 36, 'halfday_sick', 0.00, '2025'),
(770, 37, 'halfday_sick', 0.00, '2025'),
(771, 38, 'halfday_sick', 0.00, '2025'),
(772, 41, 'halfday_sick', 0.00, '2025'),
(773, 43, 'halfday_sick', 0.00, '2025'),
(774, 46, 'halfday_sick', 0.00, '2025'),
(775, 47, 'halfday_sick', 0.00, '2025'),
(776, 49, 'halfday_sick', 0.00, '2025'),
(777, 50, 'halfday_sick', 0.00, '2025'),
(778, 52, 'halfday_sick', 0.00, '2025'),
(779, 53, 'halfday_sick', 0.00, '2025'),
(780, 55, 'halfday_sick', 0.00, '2025'),
(781, 56, 'halfday_sick', 0.00, '2025'),
(782, 57, 'halfday_sick', 0.00, '2025'),
(783, 58, 'halfday_sick', 0.00, '2025'),
(784, 59, 'halfday_sick', 0.00, '2025'),
(785, 60, 'halfday_sick', 0.00, '2025'),
(786, 61, 'halfday_sick', 0.00, '2025'),
(787, 62, 'halfday_sick', 0.00, '2025'),
(788, 63, 'halfday_sick', 0.00, '2025'),
(789, 64, 'halfday_sick', 0.00, '2025'),
(790, 65, 'halfday_sick', 0.00, '2025'),
(791, 66, 'halfday_sick', 0.00, '2025'),
(792, 67, 'halfday_sick', 0.00, '2025'),
(793, 68, 'halfday_sick', 0.00, '2025'),
(794, 69, 'halfday_sick', 0.00, '2025'),
(795, 70, 'halfday_sick', 0.00, '2025'),
(796, 71, 'halfday_sick', 0.00, '2025'),
(797, 72, 'halfday_sick', 0.00, '2025'),
(798, 1002, 'halfday_sick', 0.00, '2025'),
(799, 1003, 'halfday_sick', 0.00, '2025'),
(800, 1004, 'halfday_sick', 0.00, '2025'),
(801, 1005, 'halfday_sick', 0.00, '2025'),
(802, 1006, 'halfday_sick', 0.00, '2025'),
(803, 1007, 'halfday_sick', 0.00, '2025'),
(804, 1009, 'halfday_sick', 0.00, '2025'),
(805, 1, 'lwop', NULL, '2025'),
(806, 2, 'lwop', NULL, '2025'),
(807, 3, 'lwop', NULL, '2025'),
(808, 4, 'lwop', NULL, '2025'),
(809, 5, 'lwop', NULL, '2025'),
(810, 6, 'lwop', NULL, '2025'),
(811, 7, 'lwop', NULL, '2025'),
(812, 8, 'lwop', NULL, '2025'),
(813, 9, 'lwop', NULL, '2025'),
(814, 10, 'lwop', NULL, '2025'),
(815, 11, 'lwop', NULL, '2025'),
(816, 12, 'lwop', NULL, '2025'),
(817, 13, 'lwop', NULL, '2025'),
(818, 15, 'lwop', NULL, '2025'),
(819, 16, 'lwop', NULL, '2025'),
(820, 17, 'lwop', NULL, '2025'),
(821, 18, 'lwop', NULL, '2025'),
(822, 20, 'lwop', NULL, '2025'),
(823, 21, 'lwop', NULL, '2025'),
(824, 22, 'lwop', NULL, '2025'),
(825, 24, 'lwop', NULL, '2025'),
(826, 25, 'lwop', NULL, '2025'),
(827, 26, 'lwop', NULL, '2025'),
(828, 27, 'lwop', NULL, '2025'),
(829, 28, 'lwop', NULL, '2025'),
(830, 29, 'lwop', NULL, '2025'),
(831, 30, 'lwop', NULL, '2025'),
(832, 31, 'lwop', NULL, '2025'),
(833, 33, 'lwop', NULL, '2025'),
(834, 34, 'lwop', NULL, '2025'),
(835, 35, 'lwop', NULL, '2025'),
(836, 36, 'lwop', NULL, '2025'),
(837, 37, 'lwop', NULL, '2025'),
(838, 38, 'lwop', NULL, '2025'),
(839, 41, 'lwop', NULL, '2025'),
(840, 43, 'lwop', NULL, '2025'),
(841, 46, 'lwop', NULL, '2025'),
(842, 47, 'lwop', NULL, '2025'),
(843, 49, 'lwop', NULL, '2025'),
(844, 50, 'lwop', NULL, '2025'),
(845, 52, 'lwop', NULL, '2025'),
(846, 53, 'lwop', NULL, '2025'),
(847, 55, 'lwop', NULL, '2025'),
(848, 56, 'lwop', NULL, '2025'),
(849, 57, 'lwop', NULL, '2025'),
(850, 58, 'lwop', NULL, '2025'),
(851, 59, 'lwop', NULL, '2025'),
(852, 60, 'lwop', NULL, '2025'),
(853, 61, 'lwop', NULL, '2025'),
(854, 62, 'lwop', NULL, '2025'),
(855, 63, 'lwop', NULL, '2025'),
(856, 64, 'lwop', NULL, '2025'),
(857, 65, 'lwop', NULL, '2025'),
(858, 66, 'lwop', NULL, '2025'),
(859, 67, 'lwop', NULL, '2025'),
(860, 68, 'lwop', NULL, '2025'),
(861, 69, 'lwop', NULL, '2025'),
(862, 70, 'lwop', NULL, '2025'),
(863, 71, 'lwop', NULL, '2025'),
(864, 72, 'lwop', NULL, '2025'),
(865, 1002, 'lwop', NULL, '2025'),
(866, 1003, 'lwop', NULL, '2025'),
(867, 1004, 'lwop', NULL, '2025'),
(868, 1005, 'lwop', NULL, '2025'),
(869, 1006, 'lwop', NULL, '2025'),
(870, 1007, 'lwop', NULL, '2025'),
(871, 1009, 'lwop', NULL, '2025'),
(872, 1, 'bereavement', 3.00, '2025'),
(873, 2, 'bereavement', 3.00, '2025'),
(874, 3, 'bereavement', 3.00, '2025'),
(875, 4, 'bereavement', 3.00, '2025'),
(876, 5, 'bereavement', 3.00, '2025'),
(877, 6, 'bereavement', 3.00, '2025'),
(878, 7, 'bereavement', 3.00, '2025'),
(879, 8, 'bereavement', 3.00, '2025'),
(880, 9, 'bereavement', 3.00, '2025'),
(881, 10, 'bereavement', 3.00, '2025'),
(882, 11, 'bereavement', 3.00, '2025'),
(883, 12, 'bereavement', 3.00, '2025'),
(884, 13, 'bereavement', 3.00, '2025'),
(885, 15, 'bereavement', 3.00, '2025'),
(886, 16, 'bereavement', 3.00, '2025'),
(887, 17, 'bereavement', 3.00, '2025'),
(888, 18, 'bereavement', 3.00, '2025'),
(889, 20, 'bereavement', 3.00, '2025'),
(890, 21, 'bereavement', 3.00, '2025'),
(891, 22, 'bereavement', 3.00, '2025'),
(892, 24, 'bereavement', 3.00, '2025'),
(893, 25, 'bereavement', 3.00, '2025'),
(894, 26, 'bereavement', 3.00, '2025'),
(895, 27, 'bereavement', 3.00, '2025'),
(896, 28, 'bereavement', 3.00, '2025'),
(897, 29, 'bereavement', 3.00, '2025'),
(898, 30, 'bereavement', 3.00, '2025'),
(899, 31, 'bereavement', 3.00, '2025'),
(900, 33, 'bereavement', 3.00, '2025'),
(901, 34, 'bereavement', 3.00, '2025'),
(902, 35, 'bereavement', 3.00, '2025'),
(903, 36, 'bereavement', 3.00, '2025'),
(904, 37, 'bereavement', 3.00, '2025'),
(905, 38, 'bereavement', 3.00, '2025'),
(906, 41, 'bereavement', 3.00, '2025'),
(907, 43, 'bereavement', 3.00, '2025'),
(908, 46, 'bereavement', 3.00, '2025'),
(909, 47, 'bereavement', 3.00, '2025'),
(910, 49, 'bereavement', 3.00, '2025'),
(911, 50, 'bereavement', 3.00, '2025'),
(912, 52, 'bereavement', 3.00, '2025'),
(913, 53, 'bereavement', 3.00, '2025'),
(914, 55, 'bereavement', 3.00, '2025'),
(915, 56, 'bereavement', 3.00, '2025'),
(916, 57, 'bereavement', 3.00, '2025'),
(917, 58, 'bereavement', 3.00, '2025'),
(918, 59, 'bereavement', 3.00, '2025'),
(919, 60, 'bereavement', 3.00, '2025'),
(920, 61, 'bereavement', 3.00, '2025'),
(921, 62, 'bereavement', 3.00, '2025'),
(922, 63, 'bereavement', 3.00, '2025'),
(923, 64, 'bereavement', 3.00, '2025'),
(924, 65, 'bereavement', 3.00, '2025'),
(925, 66, 'bereavement', 3.00, '2025'),
(926, 67, 'bereavement', 3.00, '2025'),
(927, 68, 'bereavement', 3.00, '2025'),
(928, 69, 'bereavement', 3.00, '2025'),
(929, 70, 'bereavement', 3.00, '2025'),
(930, 71, 'bereavement', 3.00, '2025'),
(931, 72, 'bereavement', 3.00, '2025'),
(932, 1002, 'bereavement', 3.00, '2025'),
(933, 1003, 'bereavement', 3.00, '2025'),
(934, 1004, 'bereavement', 3.00, '2025'),
(935, 1005, 'bereavement', 3.00, '2025'),
(936, 1006, 'bereavement', 3.00, '2025'),
(937, 1007, 'bereavement', 3.00, '2025'),
(938, 1009, 'bereavement', 3.00, '2025');

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
(106, 1009, 'sick', '2025-07-07', '2025-07-07', 'test', 'approved', '2025-07-07 02:46:44', '', NULL, 0, NULL),
(107, 1009, '', '2025-07-07', '2025-07-26', 'test', 'pending', '2025-07-07 06:56:46', '', 'lr_686b6fae1f6a2.jpeg', 0, NULL),
(108, 1009, '', '2025-07-08', '2025-07-19', 'SADDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDD', 'pending', '2025-07-08 02:55:01', '', NULL, 0, NULL),
(109, 1009, 'paternity', '2025-07-08', '2025-07-08', 'tesssssssssssssssssssdssssssssssssssssssssssssssssssssssssssssssssssssssssssssssssssssssssssssssssssssssssssssssssssssssssssssssssssssssssssssssssssssssssssssssssssssssssssssssssssssssssssssssssssssssssss', 'pending', '2025-07-08 03:02:55', '', NULL, 0, NULL),
(110, 1009, '', '2025-07-08', '2025-07-08', 'hiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiii', 'pending', '2025-07-08 03:03:29', '', NULL, 0, NULL),
(111, 1009, 'halfday', '2025-07-08', '2025-07-08', 'asdsad', 'approved', '2025-07-08 03:08:36', '', NULL, 1, NULL),
(112, 1009, 'bereavement', '2025-07-08', '2025-07-08', 'asdas', 'pending', '2025-07-08 03:09:19', '', NULL, 0, NULL),
(113, 1009, 'halfday', '2025-07-08', '2025-07-08', 'test', 'approved', '2025-07-08 05:40:54', '', NULL, 1, NULL),
(114, 1009, 'halfday', '2025-07-11', '2025-07-26', 'sdasds', 'pending', '2025-07-08 05:42:13', '', NULL, 0, NULL),
(115, 1006, 'halfday', '2025-07-09', '2025-07-09', 'test', 'pending', '2025-07-09 01:49:06', '', NULL, 0, NULL),
(116, 1006, 'lwop', '2025-07-09', '2025-07-09', 'asdaasda', 'pending', '2025-07-09 01:50:17', '', NULL, 0, NULL);

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
(10, 3, '2025-07-07', '01:22:12', '01:22:19', 0, 'sad', 'Pending', NULL, '2025-07-07 09:22:21', '07:17:14', '16:17:11', 0, NULL),
(11, 1009, '2025-07-07', '01:46:21', '01:46:27', 0, 'adasd', 'Pending', 'uploads/1751852795_0266554465.jpeg', '2025-07-07 09:46:35', '07:15:13', '16:15:14', 0, NULL),
(13, 20, '2025-07-07', '10:10:09', '11:10:10', 0, 'asd', 'Pending', NULL, '2025-07-07 10:10:14', '07:09:24', '16:09:25', 0, NULL),
(14, 1009, '2025-07-08', '10:23:27', '10:23:29', 0, 'test', 'Approved', NULL, '2025-07-08 10:23:34', '06:58:22', '16:01:23', 1, NULL),
(15, 1006, '2025-07-08', '15:45:49', '15:45:59', 0, 'restyy', 'Approved', 'uploads/1751960776_Smiley.svg.png', '2025-07-08 15:46:16', '07:00:00', '16:00:00', 1, NULL),
(16, 5, '2025-07-11', '10:54:45', '10:54:55', 0, 'asdas', 'Pending', NULL, '2025-07-11 10:55:01', '01:53:52', '10:54:33', 0, NULL);

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
(40, 15, 1006, '👍', '2025-07-09 06:14:43'),
(46, 15, 1009, '❤️', '2025-07-09 07:30:26');

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
(75, 1009, NULL, 'test', NULL, 'Approved', '2025-06-30 07:03:53', '2025-06-30', '2025-06-30', NULL, NULL, 7, 'scr_686236d917c2c.png', 1, NULL),
(76, 1009, NULL, 'tanatamad', NULL, 'Approved', '2025-06-30 07:10:09', '2025-06-30', '2025-07-04', NULL, NULL, 7, 'scr_6862385141b86.jpeg', 1, NULL),
(77, 1009, NULL, '6 to 3 po', NULL, 'Declined', '2025-06-30 23:18:31', '2025-07-01', '2025-07-02', NULL, NULL, 8, 'scr_68631b47b3b62.png', 1, 'bawal'),
(78, 1009, NULL, 'test', NULL, 'Pending', '2025-07-01 01:27:04', '2025-07-01', '2025-07-01', NULL, NULL, 7, 'scr_686339689b626.png', 0, NULL),
(79, 1009, NULL, 'test', NULL, 'Approved', '2025-07-02 05:00:08', '2025-07-02', '2025-07-02', NULL, NULL, 6, NULL, 0, NULL),
(80, 1009, NULL, 'test', NULL, 'Approved', '2025-07-02 05:00:38', '2025-07-02', '2025-07-02', NULL, NULL, 6, NULL, 1, NULL),
(81, 1009, NULL, 'test', NULL, 'Approved', '2025-07-07 01:05:43', '2025-08-06', '2025-08-06', NULL, NULL, 5, NULL, 1, NULL),
(82, 1009, NULL, 'hi po 12', NULL, 'Approved', '2025-07-08 02:33:35', '2025-07-08', '2025-07-19', NULL, NULL, 6, NULL, 1, NULL),
(83, 1009, NULL, 'test', NULL, 'Approved', '2025-07-08 05:47:08', '2025-07-25', '2025-08-09', NULL, NULL, 4, NULL, 1, NULL),
(84, 1009, NULL, 'test', NULL, 'Approved', '2025-07-08 05:47:43', '2025-07-26', '2025-08-09', NULL, NULL, 7, NULL, 1, NULL),
(85, 1006, NULL, '9 -6 ced', NULL, 'Approved', '2025-07-09 23:58:22', '2025-07-10', '2025-07-10', NULL, NULL, 6, NULL, 1, NULL),
(86, 1009, NULL, 'heyy', NULL, 'Approved', '2025-07-10 00:03:46', '2025-07-10', '2025-07-12', NULL, NULL, 8, NULL, 1, NULL);

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
(1, 1009, '2025-07-02', '13:03:46', NULL, '10:27:00', '00:27:00', 'TEST', 'Pending', '2025-07-03 10:27:21', NULL, '2025-07-07 10:31:40', 0),
(2, 1009, '2025-07-03', '10:21:30', NULL, '11:01:00', '00:01:00', 'TEST', 'Pending', '2025-07-03 11:01:53', 'uploads/attach_6865f2a1c8c246.69129186.jpeg', '2025-07-07 10:31:40', 0),
(3, 1009, '2025-07-03', '10:21:30', NULL, '11:01:00', '00:01:00', 'TEST', 'Pending', '2025-07-03 11:06:18', 'uploads/attach_6865f3aad2b2b0.13271836.jpeg', '2025-07-07 10:31:40', 0),
(4, 1009, '2025-07-03', '10:21:30', NULL, '11:01:00', '00:01:00', 'TEST', 'Pending', '2025-07-03 12:54:37', 'uploads/attach_68660d0d7f9bf7.17194240.jpeg', '2025-07-07 10:31:40', 0),
(5, 1009, '2025-07-03', '10:21:30', NULL, '11:01:00', '00:01:00', 'TEST', 'Pending', '2025-07-03 12:55:51', 'uploads/attach_68660d57475d81.72712397.jpeg', '2025-07-07 10:31:40', 0),
(6, 1009, '2025-07-03', '07:00:00', '15:00:00', '12:31:00', '14:31:00', 'test | Decline Reason: no po', 'Declined', '2025-07-03 15:32:01', NULL, '2025-07-07 10:31:40', 0),
(7, 20, '2025-07-07', '07:09:24', '16:09:25', '10:15:00', '10:16:00', 'tetst', 'Approved', '2025-07-07 10:15:30', NULL, '2025-07-07 10:31:40', 1),
(8, 1009, '2025-07-07', '07:15:13', '16:15:14', '09:44:00', '17:44:00', 'hii | Decline Reason: bawal rj', 'Declined', '2025-07-07 10:44:28', NULL, '2025-07-07 10:44:28', 1),
(9, 1009, '2025-07-08', '06:58:22', '16:01:23', '10:31:00', '15:32:00', 'nalete', 'Approved', '2025-07-08 08:32:18', NULL, '2025-07-08 08:32:18', 1),
(10, 1009, '2025-07-08', '06:58:22', '16:01:23', '16:06:00', '17:07:00', 'test', 'Pending', '2025-07-08 13:06:57', NULL, '2025-07-08 13:06:57', 0),
(11, 1009, '2025-07-08', '06:58:22', '16:01:23', '07:00:00', '16:00:00', 'late', 'Approved', '2025-07-08 14:48:59', NULL, '2025-07-08 14:48:59', 1),
(12, 1009, '2025-07-02', '13:03:46', NULL, '19:00:00', '16:00:00', 'try', 'Pending', '2025-07-08 15:12:03', NULL, '2025-07-08 15:12:03', 0),
(13, 1009, '2025-07-02', '13:03:46', NULL, '07:14:00', '16:15:00', 'iris', 'Approved', '2025-07-08 15:14:39', NULL, '2025-07-08 15:14:39', 1),
(14, 1009, '2025-07-10', '06:04:31', '15:04:40', '10:47:00', '01:47:00', 'sdasd', 'Pending', '2025-07-10 10:47:51', 'uploads/attach_686f29d7d7c988.55377236.png', '2025-07-10 10:47:51', 0),
(15, 1009, '2025-07-11', '07:59:44', NULL, '07:59:00', '16:00:00', 'test | Decline Reason: asd', 'Declined', '2025-07-11 08:00:24', 'uploads/attach_68705418b1db90.86569623.png', '2025-07-11 08:00:24', 1),
(16, 1009, '2025-07-11', '07:59:44', NULL, '07:08:00', '16:04:00', 'test', 'Pending', '2025-07-11 08:09:13', 'uploads/attach_687056297c51a7.19618068.png', '2025-07-11 08:09:13', 0),
(17, 1009, '2025-07-11', '07:59:44', NULL, NULL, '20:10:00', 'atest', 'Pending', '2025-07-11 08:10:59', 'uploads/attach_68705693d2ff73.53024131.png', '2025-07-11 08:10:59', 0),
(18, 1009, '2025-07-11', '07:59:44', NULL, '10:12:00', '22:12:00', 'testt', 'Pending', '2025-07-11 08:12:55', 'uploads/attach_68705707a89472.72113196.png', '2025-07-11 08:12:55', 0),
(19, 1009, '2025-07-11', '07:59:44', NULL, NULL, '20:15:00', 'dragon', 'Pending', '2025-07-11 08:15:52', 'uploads/attach_687057b875e643.60075388.jpg', '2025-07-11 08:15:52', 0),
(20, 1009, '2025-07-11', '07:59:44', NULL, '10:22:00', '16:22:00', 'sdads', 'Pending', '2025-07-11 08:22:32', 'uploads/attach_68705948090a27.16966569.jpg', '2025-07-11 08:22:32', 0),
(21, 1009, '2025-07-11', '07:59:44', NULL, '08:34:00', '20:34:00', 'dragon-test', 'Pending', '2025-07-11 08:35:03', 'time_adjustments/attach_68705c376e25a9.91211883.jpg', '2025-07-11 08:35:03', 0),
(22, 1009, '2025-07-11', '07:59:44', NULL, '08:46:00', '20:46:00', 'testing lng dr', 'Pending', '2025-07-11 08:46:28', 'attach_68705ee486e766.13651844.jpg', '2025-07-11 08:46:28', 0),
(23, 1009, '2025-07-11', '07:59:44', NULL, '08:57:00', '20:57:00', 'test dr', 'Pending', '2025-07-11 08:57:15', 'module/time_adjustments/attach_6870616bccef76.09217913.jpg', '2025-07-11 08:57:15', 0),
(24, 1009, '2025-07-11', '07:59:44', NULL, '08:57:00', '20:57:00', 'test dr', 'Pending', '2025-07-11 09:06:05', 'attach_6870637d870a64.62269674.jpg', '2025-07-11 09:06:05', 0),
(25, 1009, '2025-07-11', '07:59:44', NULL, '07:06:00', '19:06:00', 'testtt -DRRRR', 'Pending', '2025-07-11 09:06:39', 'attach_6870639f70fd02.94601544.jpg', '2025-07-11 09:06:39', 0);

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
(70, 1009, '2025-07-14', '07:18:49', NULL, 0, 0);

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
(8, NULL, '06:00:00', '15:00:00', NULL, NULL);

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
  MODIFY `announcement_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `approved_overtime_schedule`
--
ALTER TABLE `approved_overtime_schedule`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `comments`
--
ALTER TABLE `comments`
  MODIFY `comment_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=126;

--
-- AUTO_INCREMENT for table `employees`
--
ALTER TABLE `employees`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1014;

--
-- AUTO_INCREMENT for table `employee_checklist`
--
ALTER TABLE `employee_checklist`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1359;

--
-- AUTO_INCREMENT for table `leave_requests`
--
ALTER TABLE `leave_requests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=117;

--
-- AUTO_INCREMENT for table `overtime_requests`
--
ALTER TABLE `overtime_requests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `reactions`
--
ALTER TABLE `reactions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=49;

--
-- AUTO_INCREMENT for table `rest_day_overtime_requests`
--
ALTER TABLE `rest_day_overtime_requests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `schedule_change_requests`
--
ALTER TABLE `schedule_change_requests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=87;

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=37;

--
-- AUTO_INCREMENT for table `time_logs`
--
ALTER TABLE `time_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=71;

--
-- AUTO_INCREMENT for table `vacation_leaves`
--
ALTER TABLE `vacation_leaves`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `work_schedules`
--
ALTER TABLE `work_schedules`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

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
