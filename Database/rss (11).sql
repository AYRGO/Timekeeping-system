-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jul 15, 2025 at 09:59 AM
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
(15, 'test', 'Admin', '2025-07-01 11:34:49', 'uploads/img_68635759012194.25638175.png'),
(23, 'asd', 'Admin', '2025-07-15 09:49:47', 'uploads/file_6875b3bb9bcd18.90333177.xls'),
(24, 'rsd', 'Admin', '2025-07-15 09:51:41', NULL),
(27, '123213', 'Admin', '2025-07-15 14:24:10', '[\"uploads\\/file_6875f40a4654a7.39506471.docx\"]'),
(28, 'sdsa', 'Admin', '2025-07-15 14:25:41', '[\"uploads\\/file_6875f46547da33.34601607.xls\"]');

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
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `pagibig` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `employee_checklist`
--

INSERT INTO `employee_checklist` (`id`, `employee_id`, `letter_offer`, `employment_contract`, `medical`, `nbi_clearance`, `diploma_tor`, `psa`, `sss`, `tin`, `philhealth`, `coe`, `created_at`, `updated_at`, `pagibig`) VALUES
(1, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-07-14 10:25:11', '2025-07-15 10:11:52', 'pagibig_6875b8e885a6e_0266554465.jpeg'),
(2, 1009, 'letter_offer_6875c10ce642a_JYEXpJURGks76oHVBc5cik-1200-80.jpg', NULL, 'medical_6875db736474f_Satoru_Gojo_arrives_on_the_battlefield_29.webp', NULL, NULL, NULL, NULL, 'tin_6875e0d891648_RSS-logo-colour.png', NULL, NULL, '2025-07-14 11:22:29', '2025-07-15 13:02:16', NULL),
(3, 1013, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-07-14 11:35:32', '2025-07-14 11:35:32', NULL);

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
  `balance` decimal(5,2) DEFAULT NULL,
  `monthly_increment` decimal(5,2) DEFAULT 0.00,
  `carry_over` decimal(5,2) DEFAULT 0.00,
  `year` year(4) NOT NULL,
  `carried_over` decimal(5,2) DEFAULT 0.00,
  `updated_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `leave_credits`
--

INSERT INTO `leave_credits` (`id`, `employee_id`, `leave_type`, `balance`, `monthly_increment`, `carry_over`, `year`, `carried_over`, `updated_at`) VALUES
(1, 1, 'sick', 23.00, 0.00, 0.00, '2025', NULL, NULL),
(2, 1, 'vacation', 0.00, 0.00, 0.00, '2025', NULL, NULL),
(3, 1, 'paternity', 0.00, 0.00, 0.00, '2025', NULL, NULL),
(4, 1, 'maternity', 0.00, 0.00, 0.00, '2025', NULL, NULL),
(5, 1, 'solo_parent', 0.00, 0.00, 0.00, '2025', NULL, NULL),
(6, 1, 'halfday', 0.00, 0.00, 0.00, '2025', NULL, NULL),
(7, 1, 'halfday_sick', 0.00, 0.00, 0.00, '2025', NULL, NULL),
(8, 1, 'lwop', 0.00, 0.00, 0.00, '2025', NULL, NULL),
(9, 1, 'bereavement', 0.00, 0.00, 0.00, '2025', NULL, NULL),
(10, 2, 'sick', NULL, 0.00, 0.00, '2025', NULL, NULL),
(11, 2, 'vacation', NULL, 0.00, 0.00, '2025', NULL, NULL),
(12, 2, 'paternity', NULL, 0.00, 0.00, '2025', NULL, NULL),
(13, 2, 'maternity', NULL, 0.00, 0.00, '2025', NULL, NULL),
(14, 2, 'solo_parent', NULL, 0.00, 0.00, '2025', NULL, NULL),
(15, 2, 'halfday', NULL, 0.00, 0.00, '2025', NULL, NULL),
(16, 2, 'halfday_sick', NULL, 0.00, 0.00, '2025', NULL, NULL),
(17, 2, 'lwop', NULL, 0.00, 0.00, '2025', NULL, NULL),
(18, 2, 'bereavement', NULL, 0.00, 0.00, '2025', NULL, NULL),
(19, 3, 'sick', NULL, 0.00, 0.00, '2025', NULL, NULL),
(20, 3, 'vacation', NULL, 0.00, 0.00, '2025', NULL, NULL),
(21, 3, 'paternity', NULL, 0.00, 0.00, '2025', NULL, NULL),
(22, 3, 'maternity', NULL, 0.00, 0.00, '2025', NULL, NULL),
(23, 3, 'solo_parent', NULL, 0.00, 0.00, '2025', NULL, NULL),
(24, 3, 'halfday', NULL, 0.00, 0.00, '2025', NULL, NULL),
(25, 3, 'halfday_sick', NULL, 0.00, 0.00, '2025', NULL, NULL),
(26, 3, 'lwop', NULL, 0.00, 0.00, '2025', NULL, NULL),
(27, 3, 'bereavement', NULL, 0.00, 0.00, '2025', NULL, NULL),
(28, 4, 'sick', NULL, 0.00, 0.00, '2025', NULL, NULL),
(29, 4, 'vacation', NULL, 0.00, 0.00, '2025', NULL, NULL),
(30, 4, 'paternity', NULL, 0.00, 0.00, '2025', NULL, NULL),
(31, 4, 'maternity', NULL, 0.00, 0.00, '2025', NULL, NULL),
(32, 4, 'solo_parent', NULL, 0.00, 0.00, '2025', NULL, NULL),
(33, 4, 'halfday', NULL, 0.00, 0.00, '2025', NULL, NULL),
(34, 4, 'halfday_sick', NULL, 0.00, 0.00, '2025', NULL, NULL),
(35, 4, 'lwop', NULL, 0.00, 0.00, '2025', NULL, NULL),
(36, 4, 'bereavement', NULL, 0.00, 0.00, '2025', NULL, NULL),
(37, 5, 'sick', NULL, 0.00, 0.00, '2025', NULL, NULL),
(38, 5, 'vacation', NULL, 0.00, 0.00, '2025', NULL, NULL),
(39, 5, 'paternity', NULL, 0.00, 0.00, '2025', NULL, NULL),
(40, 5, 'maternity', NULL, 0.00, 0.00, '2025', NULL, NULL),
(41, 5, 'solo_parent', NULL, 0.00, 0.00, '2025', NULL, NULL),
(42, 5, 'halfday', NULL, 0.00, 0.00, '2025', NULL, NULL),
(43, 5, 'halfday_sick', NULL, 0.00, 0.00, '2025', NULL, NULL),
(44, 5, 'lwop', NULL, 0.00, 0.00, '2025', NULL, NULL),
(45, 5, 'bereavement', NULL, 0.00, 0.00, '2025', NULL, NULL),
(46, 6, 'sick', NULL, 0.00, 0.00, '2025', NULL, NULL),
(47, 6, 'vacation', NULL, 0.00, 0.00, '2025', NULL, NULL),
(48, 6, 'paternity', NULL, 0.00, 0.00, '2025', NULL, NULL),
(49, 6, 'maternity', NULL, 0.00, 0.00, '2025', NULL, NULL),
(50, 6, 'solo_parent', NULL, 0.00, 0.00, '2025', NULL, NULL),
(51, 6, 'halfday', NULL, 0.00, 0.00, '2025', NULL, NULL),
(52, 6, 'halfday_sick', NULL, 0.00, 0.00, '2025', NULL, NULL),
(53, 6, 'lwop', NULL, 0.00, 0.00, '2025', NULL, NULL),
(54, 6, 'bereavement', NULL, 0.00, 0.00, '2025', NULL, NULL),
(55, 7, 'sick', NULL, 0.00, 0.00, '2025', NULL, NULL),
(56, 7, 'vacation', NULL, 0.00, 0.00, '2025', NULL, NULL),
(57, 7, 'paternity', NULL, 0.00, 0.00, '2025', NULL, NULL),
(58, 7, 'maternity', NULL, 0.00, 0.00, '2025', NULL, NULL),
(59, 7, 'solo_parent', NULL, 0.00, 0.00, '2025', NULL, NULL),
(60, 7, 'halfday', NULL, 0.00, 0.00, '2025', NULL, NULL),
(61, 7, 'halfday_sick', NULL, 0.00, 0.00, '2025', NULL, NULL),
(62, 7, 'lwop', NULL, 0.00, 0.00, '2025', NULL, NULL),
(63, 7, 'bereavement', NULL, 0.00, 0.00, '2025', NULL, NULL),
(64, 8, 'sick', NULL, 0.00, 0.00, '2025', NULL, NULL),
(65, 8, 'vacation', NULL, 0.00, 0.00, '2025', NULL, NULL),
(66, 8, 'paternity', NULL, 0.00, 0.00, '2025', NULL, NULL),
(67, 8, 'maternity', NULL, 0.00, 0.00, '2025', NULL, NULL),
(68, 8, 'solo_parent', NULL, 0.00, 0.00, '2025', NULL, NULL),
(69, 8, 'halfday', NULL, 0.00, 0.00, '2025', NULL, NULL),
(70, 8, 'halfday_sick', NULL, 0.00, 0.00, '2025', NULL, NULL),
(71, 8, 'lwop', NULL, 0.00, 0.00, '2025', NULL, NULL),
(72, 8, 'bereavement', NULL, 0.00, 0.00, '2025', NULL, NULL),
(73, 9, 'sick', NULL, 0.00, 0.00, '2025', NULL, NULL),
(74, 9, 'vacation', NULL, 0.00, 0.00, '2025', NULL, NULL),
(75, 9, 'paternity', NULL, 0.00, 0.00, '2025', NULL, NULL),
(76, 9, 'maternity', NULL, 0.00, 0.00, '2025', NULL, NULL),
(77, 9, 'solo_parent', NULL, 0.00, 0.00, '2025', NULL, NULL),
(78, 9, 'halfday', NULL, 0.00, 0.00, '2025', NULL, NULL),
(79, 9, 'halfday_sick', NULL, 0.00, 0.00, '2025', NULL, NULL),
(80, 9, 'lwop', NULL, 0.00, 0.00, '2025', NULL, NULL),
(81, 9, 'bereavement', NULL, 0.00, 0.00, '2025', NULL, NULL),
(82, 10, 'sick', NULL, 0.00, 0.00, '2025', NULL, NULL),
(83, 10, 'vacation', NULL, 0.00, 0.00, '2025', NULL, NULL),
(84, 10, 'paternity', NULL, 0.00, 0.00, '2025', NULL, NULL),
(85, 10, 'maternity', NULL, 0.00, 0.00, '2025', NULL, NULL),
(86, 10, 'solo_parent', NULL, 0.00, 0.00, '2025', NULL, NULL),
(87, 10, 'halfday', NULL, 0.00, 0.00, '2025', NULL, NULL),
(88, 10, 'halfday_sick', NULL, 0.00, 0.00, '2025', NULL, NULL),
(89, 10, 'lwop', NULL, 0.00, 0.00, '2025', NULL, NULL),
(90, 10, 'bereavement', NULL, 0.00, 0.00, '2025', NULL, NULL),
(91, 11, 'sick', NULL, 0.00, 0.00, '2025', NULL, NULL),
(92, 11, 'vacation', NULL, 0.00, 0.00, '2025', NULL, NULL),
(93, 11, 'paternity', NULL, 0.00, 0.00, '2025', NULL, NULL),
(94, 11, 'maternity', NULL, 0.00, 0.00, '2025', NULL, NULL),
(95, 11, 'solo_parent', NULL, 0.00, 0.00, '2025', NULL, NULL),
(96, 11, 'halfday', NULL, 0.00, 0.00, '2025', NULL, NULL),
(97, 11, 'halfday_sick', NULL, 0.00, 0.00, '2025', NULL, NULL),
(98, 11, 'lwop', NULL, 0.00, 0.00, '2025', NULL, NULL),
(99, 11, 'bereavement', NULL, 0.00, 0.00, '2025', NULL, NULL),
(100, 12, 'sick', NULL, 0.00, 0.00, '2025', NULL, NULL),
(101, 12, 'vacation', NULL, 0.00, 0.00, '2025', NULL, NULL),
(102, 12, 'paternity', NULL, 0.00, 0.00, '2025', NULL, NULL),
(103, 12, 'maternity', NULL, 0.00, 0.00, '2025', NULL, NULL),
(104, 12, 'solo_parent', NULL, 0.00, 0.00, '2025', NULL, NULL),
(105, 12, 'halfday', NULL, 0.00, 0.00, '2025', NULL, NULL),
(106, 12, 'halfday_sick', NULL, 0.00, 0.00, '2025', NULL, NULL),
(107, 12, 'lwop', NULL, 0.00, 0.00, '2025', NULL, NULL),
(108, 12, 'bereavement', NULL, 0.00, 0.00, '2025', NULL, NULL),
(109, 13, 'sick', NULL, 0.00, 0.00, '2025', NULL, NULL),
(110, 13, 'vacation', NULL, 0.00, 0.00, '2025', NULL, NULL),
(111, 13, 'paternity', NULL, 0.00, 0.00, '2025', NULL, NULL),
(112, 13, 'maternity', NULL, 0.00, 0.00, '2025', NULL, NULL),
(113, 13, 'solo_parent', NULL, 0.00, 0.00, '2025', NULL, NULL),
(114, 13, 'halfday', NULL, 0.00, 0.00, '2025', NULL, NULL),
(115, 13, 'halfday_sick', NULL, 0.00, 0.00, '2025', NULL, NULL),
(116, 13, 'lwop', NULL, 0.00, 0.00, '2025', NULL, NULL),
(117, 13, 'bereavement', NULL, 0.00, 0.00, '2025', NULL, NULL),
(118, 15, 'sick', NULL, 0.00, 0.00, '2025', NULL, NULL),
(119, 15, 'vacation', NULL, 0.00, 0.00, '2025', NULL, NULL),
(120, 15, 'paternity', NULL, 0.00, 0.00, '2025', NULL, NULL),
(121, 15, 'maternity', NULL, 0.00, 0.00, '2025', NULL, NULL),
(122, 15, 'solo_parent', NULL, 0.00, 0.00, '2025', NULL, NULL),
(123, 15, 'halfday', NULL, 0.00, 0.00, '2025', NULL, NULL),
(124, 15, 'halfday_sick', NULL, 0.00, 0.00, '2025', NULL, NULL),
(125, 15, 'lwop', NULL, 0.00, 0.00, '2025', NULL, NULL),
(126, 15, 'bereavement', NULL, 0.00, 0.00, '2025', NULL, NULL),
(127, 16, 'sick', NULL, 0.00, 0.00, '2025', NULL, NULL),
(128, 16, 'vacation', NULL, 0.00, 0.00, '2025', NULL, NULL),
(129, 16, 'paternity', NULL, 0.00, 0.00, '2025', NULL, NULL),
(130, 16, 'maternity', NULL, 0.00, 0.00, '2025', NULL, NULL),
(131, 16, 'solo_parent', NULL, 0.00, 0.00, '2025', NULL, NULL),
(132, 16, 'halfday', NULL, 0.00, 0.00, '2025', NULL, NULL),
(133, 16, 'halfday_sick', NULL, 0.00, 0.00, '2025', NULL, NULL),
(134, 16, 'lwop', NULL, 0.00, 0.00, '2025', NULL, NULL),
(135, 16, 'bereavement', NULL, 0.00, 0.00, '2025', NULL, NULL),
(136, 17, 'sick', NULL, 0.00, 0.00, '2025', NULL, NULL),
(137, 17, 'vacation', NULL, 0.00, 0.00, '2025', NULL, NULL),
(138, 17, 'paternity', NULL, 0.00, 0.00, '2025', NULL, NULL),
(139, 17, 'maternity', NULL, 0.00, 0.00, '2025', NULL, NULL),
(140, 17, 'solo_parent', NULL, 0.00, 0.00, '2025', NULL, NULL),
(141, 17, 'halfday', NULL, 0.00, 0.00, '2025', NULL, NULL),
(142, 17, 'halfday_sick', NULL, 0.00, 0.00, '2025', NULL, NULL),
(143, 17, 'lwop', NULL, 0.00, 0.00, '2025', NULL, NULL),
(144, 17, 'bereavement', NULL, 0.00, 0.00, '2025', NULL, NULL),
(145, 18, 'sick', NULL, 0.00, 0.00, '2025', NULL, NULL),
(146, 18, 'vacation', NULL, 0.00, 0.00, '2025', NULL, NULL),
(147, 18, 'paternity', NULL, 0.00, 0.00, '2025', NULL, NULL),
(148, 18, 'maternity', NULL, 0.00, 0.00, '2025', NULL, NULL),
(149, 18, 'solo_parent', NULL, 0.00, 0.00, '2025', NULL, NULL),
(150, 18, 'halfday', NULL, 0.00, 0.00, '2025', NULL, NULL),
(151, 18, 'halfday_sick', NULL, 0.00, 0.00, '2025', NULL, NULL),
(152, 18, 'lwop', NULL, 0.00, 0.00, '2025', NULL, NULL),
(153, 18, 'bereavement', NULL, 0.00, 0.00, '2025', NULL, NULL),
(154, 20, 'sick', NULL, 0.00, 0.00, '2025', NULL, NULL),
(155, 20, 'vacation', NULL, 0.00, 0.00, '2025', NULL, NULL),
(156, 20, 'paternity', NULL, 0.00, 0.00, '2025', NULL, NULL),
(157, 20, 'maternity', NULL, 0.00, 0.00, '2025', NULL, NULL),
(158, 20, 'solo_parent', NULL, 0.00, 0.00, '2025', NULL, NULL),
(159, 20, 'halfday', NULL, 0.00, 0.00, '2025', NULL, NULL),
(160, 20, 'halfday_sick', NULL, 0.00, 0.00, '2025', NULL, NULL),
(161, 20, 'lwop', NULL, 0.00, 0.00, '2025', NULL, NULL),
(162, 20, 'bereavement', NULL, 0.00, 0.00, '2025', NULL, NULL),
(163, 21, 'sick', NULL, 0.00, 0.00, '2025', NULL, NULL),
(164, 21, 'vacation', NULL, 0.00, 0.00, '2025', NULL, NULL),
(165, 21, 'paternity', NULL, 0.00, 0.00, '2025', NULL, NULL),
(166, 21, 'maternity', NULL, 0.00, 0.00, '2025', NULL, NULL),
(167, 21, 'solo_parent', NULL, 0.00, 0.00, '2025', NULL, NULL),
(168, 21, 'halfday', NULL, 0.00, 0.00, '2025', NULL, NULL),
(169, 21, 'halfday_sick', NULL, 0.00, 0.00, '2025', NULL, NULL),
(170, 21, 'lwop', NULL, 0.00, 0.00, '2025', NULL, NULL),
(171, 21, 'bereavement', NULL, 0.00, 0.00, '2025', NULL, NULL),
(172, 22, 'sick', NULL, 0.00, 0.00, '2025', NULL, NULL),
(173, 22, 'vacation', NULL, 0.00, 0.00, '2025', NULL, NULL),
(174, 22, 'paternity', NULL, 0.00, 0.00, '2025', NULL, NULL),
(175, 22, 'maternity', NULL, 0.00, 0.00, '2025', NULL, NULL),
(176, 22, 'solo_parent', NULL, 0.00, 0.00, '2025', NULL, NULL),
(177, 22, 'halfday', NULL, 0.00, 0.00, '2025', NULL, NULL),
(178, 22, 'halfday_sick', NULL, 0.00, 0.00, '2025', NULL, NULL),
(179, 22, 'lwop', NULL, 0.00, 0.00, '2025', NULL, NULL),
(180, 22, 'bereavement', NULL, 0.00, 0.00, '2025', NULL, NULL),
(181, 24, 'sick', NULL, 0.00, 0.00, '2025', NULL, NULL),
(182, 24, 'vacation', NULL, 0.00, 0.00, '2025', NULL, NULL),
(183, 24, 'paternity', NULL, 0.00, 0.00, '2025', NULL, NULL),
(184, 24, 'maternity', NULL, 0.00, 0.00, '2025', NULL, NULL),
(185, 24, 'solo_parent', NULL, 0.00, 0.00, '2025', NULL, NULL),
(186, 24, 'halfday', NULL, 0.00, 0.00, '2025', NULL, NULL),
(187, 24, 'halfday_sick', NULL, 0.00, 0.00, '2025', NULL, NULL),
(188, 24, 'lwop', NULL, 0.00, 0.00, '2025', NULL, NULL),
(189, 24, 'bereavement', NULL, 0.00, 0.00, '2025', NULL, NULL),
(190, 25, 'sick', NULL, 0.00, 0.00, '2025', NULL, NULL),
(191, 25, 'vacation', NULL, 0.00, 0.00, '2025', NULL, NULL),
(192, 25, 'paternity', NULL, 0.00, 0.00, '2025', NULL, NULL),
(193, 25, 'maternity', NULL, 0.00, 0.00, '2025', NULL, NULL),
(194, 25, 'solo_parent', NULL, 0.00, 0.00, '2025', NULL, NULL),
(195, 25, 'halfday', NULL, 0.00, 0.00, '2025', NULL, NULL),
(196, 25, 'halfday_sick', NULL, 0.00, 0.00, '2025', NULL, NULL),
(197, 25, 'lwop', NULL, 0.00, 0.00, '2025', NULL, NULL),
(198, 25, 'bereavement', NULL, 0.00, 0.00, '2025', NULL, NULL),
(199, 26, 'sick', NULL, 0.00, 0.00, '2025', NULL, NULL),
(200, 26, 'vacation', NULL, 0.00, 0.00, '2025', NULL, NULL),
(201, 26, 'paternity', NULL, 0.00, 0.00, '2025', NULL, NULL),
(202, 26, 'maternity', NULL, 0.00, 0.00, '2025', NULL, NULL),
(203, 26, 'solo_parent', NULL, 0.00, 0.00, '2025', NULL, NULL),
(204, 26, 'halfday', NULL, 0.00, 0.00, '2025', NULL, NULL),
(205, 26, 'halfday_sick', NULL, 0.00, 0.00, '2025', NULL, NULL),
(206, 26, 'lwop', NULL, 0.00, 0.00, '2025', NULL, NULL),
(207, 26, 'bereavement', NULL, 0.00, 0.00, '2025', NULL, NULL),
(208, 27, 'sick', NULL, 0.00, 0.00, '2025', NULL, NULL),
(209, 27, 'vacation', NULL, 0.00, 0.00, '2025', NULL, NULL),
(210, 27, 'paternity', NULL, 0.00, 0.00, '2025', NULL, NULL),
(211, 27, 'maternity', NULL, 0.00, 0.00, '2025', NULL, NULL),
(212, 27, 'solo_parent', NULL, 0.00, 0.00, '2025', NULL, NULL),
(213, 27, 'halfday', NULL, 0.00, 0.00, '2025', NULL, NULL),
(214, 27, 'halfday_sick', NULL, 0.00, 0.00, '2025', NULL, NULL),
(215, 27, 'lwop', NULL, 0.00, 0.00, '2025', NULL, NULL),
(216, 27, 'bereavement', NULL, 0.00, 0.00, '2025', NULL, NULL),
(217, 28, 'sick', NULL, 0.00, 0.00, '2025', NULL, NULL),
(218, 28, 'vacation', NULL, 0.00, 0.00, '2025', NULL, NULL),
(219, 28, 'paternity', NULL, 0.00, 0.00, '2025', NULL, NULL),
(220, 28, 'maternity', NULL, 0.00, 0.00, '2025', NULL, NULL),
(221, 28, 'solo_parent', NULL, 0.00, 0.00, '2025', NULL, NULL),
(222, 28, 'halfday', NULL, 0.00, 0.00, '2025', NULL, NULL),
(223, 28, 'halfday_sick', NULL, 0.00, 0.00, '2025', NULL, NULL),
(224, 28, 'lwop', NULL, 0.00, 0.00, '2025', NULL, NULL),
(225, 28, 'bereavement', NULL, 0.00, 0.00, '2025', NULL, NULL),
(226, 29, 'sick', NULL, 0.00, 0.00, '2025', NULL, NULL),
(227, 29, 'vacation', NULL, 0.00, 0.00, '2025', NULL, NULL),
(228, 29, 'paternity', NULL, 0.00, 0.00, '2025', NULL, NULL),
(229, 29, 'maternity', NULL, 0.00, 0.00, '2025', NULL, NULL),
(230, 29, 'solo_parent', NULL, 0.00, 0.00, '2025', NULL, NULL),
(231, 29, 'halfday', NULL, 0.00, 0.00, '2025', NULL, NULL),
(232, 29, 'halfday_sick', NULL, 0.00, 0.00, '2025', NULL, NULL),
(233, 29, 'lwop', NULL, 0.00, 0.00, '2025', NULL, NULL),
(234, 29, 'bereavement', NULL, 0.00, 0.00, '2025', NULL, NULL),
(235, 30, 'sick', NULL, 0.00, 0.00, '2025', NULL, NULL),
(236, 30, 'vacation', NULL, 0.00, 0.00, '2025', NULL, NULL),
(237, 30, 'paternity', NULL, 0.00, 0.00, '2025', NULL, NULL),
(238, 30, 'maternity', NULL, 0.00, 0.00, '2025', NULL, NULL),
(239, 30, 'solo_parent', NULL, 0.00, 0.00, '2025', NULL, NULL),
(240, 30, 'halfday', NULL, 0.00, 0.00, '2025', NULL, NULL),
(241, 30, 'halfday_sick', NULL, 0.00, 0.00, '2025', NULL, NULL),
(242, 30, 'lwop', NULL, 0.00, 0.00, '2025', NULL, NULL),
(243, 30, 'bereavement', NULL, 0.00, 0.00, '2025', NULL, NULL),
(244, 31, 'sick', NULL, 0.00, 0.00, '2025', NULL, NULL),
(245, 31, 'vacation', NULL, 0.00, 0.00, '2025', NULL, NULL),
(246, 31, 'paternity', NULL, 0.00, 0.00, '2025', NULL, NULL),
(247, 31, 'maternity', NULL, 0.00, 0.00, '2025', NULL, NULL),
(248, 31, 'solo_parent', NULL, 0.00, 0.00, '2025', NULL, NULL),
(249, 31, 'halfday', NULL, 0.00, 0.00, '2025', NULL, NULL),
(250, 31, 'halfday_sick', NULL, 0.00, 0.00, '2025', NULL, NULL),
(251, 31, 'lwop', NULL, 0.00, 0.00, '2025', NULL, NULL),
(252, 31, 'bereavement', NULL, 0.00, 0.00, '2025', NULL, NULL),
(253, 33, 'sick', NULL, 0.00, 0.00, '2025', NULL, NULL),
(254, 33, 'vacation', NULL, 0.00, 0.00, '2025', NULL, NULL),
(255, 33, 'paternity', NULL, 0.00, 0.00, '2025', NULL, NULL),
(256, 33, 'maternity', NULL, 0.00, 0.00, '2025', NULL, NULL),
(257, 33, 'solo_parent', NULL, 0.00, 0.00, '2025', NULL, NULL),
(258, 33, 'halfday', NULL, 0.00, 0.00, '2025', NULL, NULL),
(259, 33, 'halfday_sick', NULL, 0.00, 0.00, '2025', NULL, NULL),
(260, 33, 'lwop', NULL, 0.00, 0.00, '2025', NULL, NULL),
(261, 33, 'bereavement', NULL, 0.00, 0.00, '2025', NULL, NULL),
(262, 34, 'sick', NULL, 0.00, 0.00, '2025', NULL, NULL),
(263, 34, 'vacation', NULL, 0.00, 0.00, '2025', NULL, NULL),
(264, 34, 'paternity', NULL, 0.00, 0.00, '2025', NULL, NULL),
(265, 34, 'maternity', NULL, 0.00, 0.00, '2025', NULL, NULL),
(266, 34, 'solo_parent', NULL, 0.00, 0.00, '2025', NULL, NULL),
(267, 34, 'halfday', NULL, 0.00, 0.00, '2025', NULL, NULL),
(268, 34, 'halfday_sick', NULL, 0.00, 0.00, '2025', NULL, NULL),
(269, 34, 'lwop', NULL, 0.00, 0.00, '2025', NULL, NULL),
(270, 34, 'bereavement', NULL, 0.00, 0.00, '2025', NULL, NULL),
(271, 35, 'sick', NULL, 0.00, 0.00, '2025', NULL, NULL),
(272, 35, 'vacation', NULL, 0.00, 0.00, '2025', NULL, NULL),
(273, 35, 'paternity', NULL, 0.00, 0.00, '2025', NULL, NULL),
(274, 35, 'maternity', NULL, 0.00, 0.00, '2025', NULL, NULL),
(275, 35, 'solo_parent', NULL, 0.00, 0.00, '2025', NULL, NULL),
(276, 35, 'halfday', NULL, 0.00, 0.00, '2025', NULL, NULL),
(277, 35, 'halfday_sick', NULL, 0.00, 0.00, '2025', NULL, NULL),
(278, 35, 'lwop', NULL, 0.00, 0.00, '2025', NULL, NULL),
(279, 35, 'bereavement', NULL, 0.00, 0.00, '2025', NULL, NULL),
(280, 36, 'sick', NULL, 0.00, 0.00, '2025', NULL, NULL),
(281, 36, 'vacation', NULL, 0.00, 0.00, '2025', NULL, NULL),
(282, 36, 'paternity', NULL, 0.00, 0.00, '2025', NULL, NULL),
(283, 36, 'maternity', NULL, 0.00, 0.00, '2025', NULL, NULL),
(284, 36, 'solo_parent', NULL, 0.00, 0.00, '2025', NULL, NULL),
(285, 36, 'halfday', NULL, 0.00, 0.00, '2025', NULL, NULL),
(286, 36, 'halfday_sick', NULL, 0.00, 0.00, '2025', NULL, NULL),
(287, 36, 'lwop', NULL, 0.00, 0.00, '2025', NULL, NULL),
(288, 36, 'bereavement', NULL, 0.00, 0.00, '2025', NULL, NULL),
(289, 37, 'sick', NULL, 0.00, 0.00, '2025', NULL, NULL),
(290, 37, 'vacation', NULL, 0.00, 0.00, '2025', NULL, NULL),
(291, 37, 'paternity', NULL, 0.00, 0.00, '2025', NULL, NULL),
(292, 37, 'maternity', NULL, 0.00, 0.00, '2025', NULL, NULL),
(293, 37, 'solo_parent', NULL, 0.00, 0.00, '2025', NULL, NULL),
(294, 37, 'halfday', NULL, 0.00, 0.00, '2025', NULL, NULL),
(295, 37, 'halfday_sick', NULL, 0.00, 0.00, '2025', NULL, NULL),
(296, 37, 'lwop', NULL, 0.00, 0.00, '2025', NULL, NULL),
(297, 37, 'bereavement', NULL, 0.00, 0.00, '2025', NULL, NULL),
(298, 38, 'sick', NULL, 0.00, 0.00, '2025', NULL, NULL),
(299, 38, 'vacation', NULL, 0.00, 0.00, '2025', NULL, NULL),
(300, 38, 'paternity', NULL, 0.00, 0.00, '2025', NULL, NULL),
(301, 38, 'maternity', NULL, 0.00, 0.00, '2025', NULL, NULL),
(302, 38, 'solo_parent', NULL, 0.00, 0.00, '2025', NULL, NULL),
(303, 38, 'halfday', NULL, 0.00, 0.00, '2025', NULL, NULL),
(304, 38, 'halfday_sick', NULL, 0.00, 0.00, '2025', NULL, NULL),
(305, 38, 'lwop', NULL, 0.00, 0.00, '2025', NULL, NULL),
(306, 38, 'bereavement', NULL, 0.00, 0.00, '2025', NULL, NULL),
(307, 41, 'sick', NULL, 0.00, 0.00, '2025', NULL, NULL),
(308, 41, 'vacation', NULL, 0.00, 0.00, '2025', NULL, NULL),
(309, 41, 'paternity', NULL, 0.00, 0.00, '2025', NULL, NULL),
(310, 41, 'maternity', NULL, 0.00, 0.00, '2025', NULL, NULL),
(311, 41, 'solo_parent', NULL, 0.00, 0.00, '2025', NULL, NULL),
(312, 41, 'halfday', NULL, 0.00, 0.00, '2025', NULL, NULL),
(313, 41, 'halfday_sick', NULL, 0.00, 0.00, '2025', NULL, NULL),
(314, 41, 'lwop', NULL, 0.00, 0.00, '2025', NULL, NULL),
(315, 41, 'bereavement', NULL, 0.00, 0.00, '2025', NULL, NULL),
(316, 43, 'sick', NULL, 0.00, 0.00, '2025', NULL, NULL),
(317, 43, 'vacation', NULL, 0.00, 0.00, '2025', NULL, NULL),
(318, 43, 'paternity', NULL, 0.00, 0.00, '2025', NULL, NULL),
(319, 43, 'maternity', NULL, 0.00, 0.00, '2025', NULL, NULL),
(320, 43, 'solo_parent', NULL, 0.00, 0.00, '2025', NULL, NULL),
(321, 43, 'halfday', NULL, 0.00, 0.00, '2025', NULL, NULL),
(322, 43, 'halfday_sick', NULL, 0.00, 0.00, '2025', NULL, NULL),
(323, 43, 'lwop', NULL, 0.00, 0.00, '2025', NULL, NULL),
(324, 43, 'bereavement', NULL, 0.00, 0.00, '2025', NULL, NULL),
(325, 46, 'sick', NULL, 0.00, 0.00, '2025', NULL, NULL),
(326, 46, 'vacation', NULL, 0.00, 0.00, '2025', NULL, NULL),
(327, 46, 'paternity', NULL, 0.00, 0.00, '2025', NULL, NULL),
(328, 46, 'maternity', NULL, 0.00, 0.00, '2025', NULL, NULL),
(329, 46, 'solo_parent', NULL, 0.00, 0.00, '2025', NULL, NULL),
(330, 46, 'halfday', NULL, 0.00, 0.00, '2025', NULL, NULL),
(331, 46, 'halfday_sick', NULL, 0.00, 0.00, '2025', NULL, NULL),
(332, 46, 'lwop', NULL, 0.00, 0.00, '2025', NULL, NULL),
(333, 46, 'bereavement', NULL, 0.00, 0.00, '2025', NULL, NULL),
(334, 47, 'sick', NULL, 0.00, 0.00, '2025', NULL, NULL),
(335, 47, 'vacation', NULL, 0.00, 0.00, '2025', NULL, NULL),
(336, 47, 'paternity', NULL, 0.00, 0.00, '2025', NULL, NULL),
(337, 47, 'maternity', NULL, 0.00, 0.00, '2025', NULL, NULL),
(338, 47, 'solo_parent', NULL, 0.00, 0.00, '2025', NULL, NULL),
(339, 47, 'halfday', NULL, 0.00, 0.00, '2025', NULL, NULL),
(340, 47, 'halfday_sick', NULL, 0.00, 0.00, '2025', NULL, NULL),
(341, 47, 'lwop', NULL, 0.00, 0.00, '2025', NULL, NULL),
(342, 47, 'bereavement', NULL, 0.00, 0.00, '2025', NULL, NULL),
(343, 49, 'sick', NULL, 0.00, 0.00, '2025', NULL, NULL),
(344, 49, 'vacation', NULL, 0.00, 0.00, '2025', NULL, NULL),
(345, 49, 'paternity', NULL, 0.00, 0.00, '2025', NULL, NULL),
(346, 49, 'maternity', NULL, 0.00, 0.00, '2025', NULL, NULL),
(347, 49, 'solo_parent', NULL, 0.00, 0.00, '2025', NULL, NULL),
(348, 49, 'halfday', NULL, 0.00, 0.00, '2025', NULL, NULL),
(349, 49, 'halfday_sick', NULL, 0.00, 0.00, '2025', NULL, NULL),
(350, 49, 'lwop', NULL, 0.00, 0.00, '2025', NULL, NULL),
(351, 49, 'bereavement', NULL, 0.00, 0.00, '2025', NULL, NULL),
(352, 50, 'sick', NULL, 0.00, 0.00, '2025', NULL, NULL),
(353, 50, 'vacation', NULL, 0.00, 0.00, '2025', NULL, NULL),
(354, 50, 'paternity', NULL, 0.00, 0.00, '2025', NULL, NULL),
(355, 50, 'maternity', NULL, 0.00, 0.00, '2025', NULL, NULL),
(356, 50, 'solo_parent', NULL, 0.00, 0.00, '2025', NULL, NULL),
(357, 50, 'halfday', NULL, 0.00, 0.00, '2025', NULL, NULL),
(358, 50, 'halfday_sick', NULL, 0.00, 0.00, '2025', NULL, NULL),
(359, 50, 'lwop', NULL, 0.00, 0.00, '2025', NULL, NULL),
(360, 50, 'bereavement', NULL, 0.00, 0.00, '2025', NULL, NULL),
(361, 52, 'sick', NULL, 0.00, 0.00, '2025', NULL, NULL),
(362, 52, 'vacation', NULL, 0.00, 0.00, '2025', NULL, NULL),
(363, 52, 'paternity', NULL, 0.00, 0.00, '2025', NULL, NULL),
(364, 52, 'maternity', NULL, 0.00, 0.00, '2025', NULL, NULL),
(365, 52, 'solo_parent', NULL, 0.00, 0.00, '2025', NULL, NULL),
(366, 52, 'halfday', NULL, 0.00, 0.00, '2025', NULL, NULL),
(367, 52, 'halfday_sick', NULL, 0.00, 0.00, '2025', NULL, NULL),
(368, 52, 'lwop', NULL, 0.00, 0.00, '2025', NULL, NULL),
(369, 52, 'bereavement', NULL, 0.00, 0.00, '2025', NULL, NULL),
(370, 53, 'sick', NULL, 0.00, 0.00, '2025', NULL, NULL),
(371, 53, 'vacation', NULL, 0.00, 0.00, '2025', NULL, NULL),
(372, 53, 'paternity', NULL, 0.00, 0.00, '2025', NULL, NULL),
(373, 53, 'maternity', NULL, 0.00, 0.00, '2025', NULL, NULL),
(374, 53, 'solo_parent', NULL, 0.00, 0.00, '2025', NULL, NULL),
(375, 53, 'halfday', NULL, 0.00, 0.00, '2025', NULL, NULL),
(376, 53, 'halfday_sick', NULL, 0.00, 0.00, '2025', NULL, NULL),
(377, 53, 'lwop', NULL, 0.00, 0.00, '2025', NULL, NULL),
(378, 53, 'bereavement', NULL, 0.00, 0.00, '2025', NULL, NULL),
(379, 55, 'sick', NULL, 0.00, 0.00, '2025', NULL, NULL),
(380, 55, 'vacation', NULL, 0.00, 0.00, '2025', NULL, NULL),
(381, 55, 'paternity', NULL, 0.00, 0.00, '2025', NULL, NULL),
(382, 55, 'maternity', NULL, 0.00, 0.00, '2025', NULL, NULL),
(383, 55, 'solo_parent', NULL, 0.00, 0.00, '2025', NULL, NULL),
(384, 55, 'halfday', NULL, 0.00, 0.00, '2025', NULL, NULL),
(385, 55, 'halfday_sick', NULL, 0.00, 0.00, '2025', NULL, NULL),
(386, 55, 'lwop', NULL, 0.00, 0.00, '2025', NULL, NULL),
(387, 55, 'bereavement', NULL, 0.00, 0.00, '2025', NULL, NULL),
(388, 56, 'sick', NULL, 0.00, 0.00, '2025', NULL, NULL),
(389, 56, 'vacation', NULL, 0.00, 0.00, '2025', NULL, NULL),
(390, 56, 'paternity', NULL, 0.00, 0.00, '2025', NULL, NULL),
(391, 56, 'maternity', NULL, 0.00, 0.00, '2025', NULL, NULL),
(392, 56, 'solo_parent', NULL, 0.00, 0.00, '2025', NULL, NULL),
(393, 56, 'halfday', NULL, 0.00, 0.00, '2025', NULL, NULL),
(394, 56, 'halfday_sick', NULL, 0.00, 0.00, '2025', NULL, NULL),
(395, 56, 'lwop', NULL, 0.00, 0.00, '2025', NULL, NULL),
(396, 56, 'bereavement', NULL, 0.00, 0.00, '2025', NULL, NULL),
(397, 57, 'sick', NULL, 0.00, 0.00, '2025', NULL, NULL),
(398, 57, 'vacation', NULL, 0.00, 0.00, '2025', NULL, NULL),
(399, 57, 'paternity', NULL, 0.00, 0.00, '2025', NULL, NULL),
(400, 57, 'maternity', NULL, 0.00, 0.00, '2025', NULL, NULL),
(401, 57, 'solo_parent', NULL, 0.00, 0.00, '2025', NULL, NULL),
(402, 57, 'halfday', NULL, 0.00, 0.00, '2025', NULL, NULL),
(403, 57, 'halfday_sick', NULL, 0.00, 0.00, '2025', NULL, NULL),
(404, 57, 'lwop', NULL, 0.00, 0.00, '2025', NULL, NULL),
(405, 57, 'bereavement', NULL, 0.00, 0.00, '2025', NULL, NULL),
(406, 58, 'sick', NULL, 0.00, 0.00, '2025', NULL, NULL),
(407, 58, 'vacation', NULL, 0.00, 0.00, '2025', NULL, NULL),
(408, 58, 'paternity', NULL, 0.00, 0.00, '2025', NULL, NULL),
(409, 58, 'maternity', NULL, 0.00, 0.00, '2025', NULL, NULL),
(410, 58, 'solo_parent', NULL, 0.00, 0.00, '2025', NULL, NULL),
(411, 58, 'halfday', NULL, 0.00, 0.00, '2025', NULL, NULL),
(412, 58, 'halfday_sick', NULL, 0.00, 0.00, '2025', NULL, NULL),
(413, 58, 'lwop', NULL, 0.00, 0.00, '2025', NULL, NULL),
(414, 58, 'bereavement', NULL, 0.00, 0.00, '2025', NULL, NULL),
(415, 59, 'sick', NULL, 0.00, 0.00, '2025', NULL, NULL),
(416, 59, 'vacation', NULL, 0.00, 0.00, '2025', NULL, NULL),
(417, 59, 'paternity', NULL, 0.00, 0.00, '2025', NULL, NULL),
(418, 59, 'maternity', NULL, 0.00, 0.00, '2025', NULL, NULL),
(419, 59, 'solo_parent', NULL, 0.00, 0.00, '2025', NULL, NULL),
(420, 59, 'halfday', NULL, 0.00, 0.00, '2025', NULL, NULL),
(421, 59, 'halfday_sick', NULL, 0.00, 0.00, '2025', NULL, NULL),
(422, 59, 'lwop', NULL, 0.00, 0.00, '2025', NULL, NULL),
(423, 59, 'bereavement', NULL, 0.00, 0.00, '2025', NULL, NULL),
(424, 60, 'sick', NULL, 0.00, 0.00, '2025', NULL, NULL),
(425, 60, 'vacation', NULL, 0.00, 0.00, '2025', NULL, NULL),
(426, 60, 'paternity', NULL, 0.00, 0.00, '2025', NULL, NULL),
(427, 60, 'maternity', NULL, 0.00, 0.00, '2025', NULL, NULL),
(428, 60, 'solo_parent', NULL, 0.00, 0.00, '2025', NULL, NULL),
(429, 60, 'halfday', NULL, 0.00, 0.00, '2025', NULL, NULL),
(430, 60, 'halfday_sick', NULL, 0.00, 0.00, '2025', NULL, NULL),
(431, 60, 'lwop', NULL, 0.00, 0.00, '2025', NULL, NULL),
(432, 60, 'bereavement', NULL, 0.00, 0.00, '2025', NULL, NULL),
(433, 61, 'sick', NULL, 0.00, 0.00, '2025', NULL, NULL),
(434, 61, 'vacation', NULL, 0.00, 0.00, '2025', NULL, NULL),
(435, 61, 'paternity', NULL, 0.00, 0.00, '2025', NULL, NULL),
(436, 61, 'maternity', NULL, 0.00, 0.00, '2025', NULL, NULL),
(437, 61, 'solo_parent', NULL, 0.00, 0.00, '2025', NULL, NULL),
(438, 61, 'halfday', NULL, 0.00, 0.00, '2025', NULL, NULL),
(439, 61, 'halfday_sick', NULL, 0.00, 0.00, '2025', NULL, NULL),
(440, 61, 'lwop', NULL, 0.00, 0.00, '2025', NULL, NULL),
(441, 61, 'bereavement', NULL, 0.00, 0.00, '2025', NULL, NULL),
(442, 62, 'sick', NULL, 0.00, 0.00, '2025', NULL, NULL),
(443, 62, 'vacation', NULL, 0.00, 0.00, '2025', NULL, NULL),
(444, 62, 'paternity', NULL, 0.00, 0.00, '2025', NULL, NULL),
(445, 62, 'maternity', NULL, 0.00, 0.00, '2025', NULL, NULL),
(446, 62, 'solo_parent', NULL, 0.00, 0.00, '2025', NULL, NULL),
(447, 62, 'halfday', NULL, 0.00, 0.00, '2025', NULL, NULL),
(448, 62, 'halfday_sick', NULL, 0.00, 0.00, '2025', NULL, NULL),
(449, 62, 'lwop', NULL, 0.00, 0.00, '2025', NULL, NULL),
(450, 62, 'bereavement', NULL, 0.00, 0.00, '2025', NULL, NULL),
(451, 63, 'sick', NULL, 0.00, 0.00, '2025', NULL, NULL),
(452, 63, 'vacation', NULL, 0.00, 0.00, '2025', NULL, NULL),
(453, 63, 'paternity', NULL, 0.00, 0.00, '2025', NULL, NULL),
(454, 63, 'maternity', NULL, 0.00, 0.00, '2025', NULL, NULL),
(455, 63, 'solo_parent', NULL, 0.00, 0.00, '2025', NULL, NULL),
(456, 63, 'halfday', NULL, 0.00, 0.00, '2025', NULL, NULL),
(457, 63, 'halfday_sick', NULL, 0.00, 0.00, '2025', NULL, NULL),
(458, 63, 'lwop', NULL, 0.00, 0.00, '2025', NULL, NULL),
(459, 63, 'bereavement', NULL, 0.00, 0.00, '2025', NULL, NULL),
(460, 64, 'sick', NULL, 0.00, 0.00, '2025', NULL, NULL),
(461, 64, 'vacation', NULL, 0.00, 0.00, '2025', NULL, NULL),
(462, 64, 'paternity', NULL, 0.00, 0.00, '2025', NULL, NULL),
(463, 64, 'maternity', NULL, 0.00, 0.00, '2025', NULL, NULL),
(464, 64, 'solo_parent', NULL, 0.00, 0.00, '2025', NULL, NULL),
(465, 64, 'halfday', NULL, 0.00, 0.00, '2025', NULL, NULL),
(466, 64, 'halfday_sick', NULL, 0.00, 0.00, '2025', NULL, NULL),
(467, 64, 'lwop', NULL, 0.00, 0.00, '2025', NULL, NULL),
(468, 64, 'bereavement', NULL, 0.00, 0.00, '2025', NULL, NULL),
(469, 65, 'sick', NULL, 0.00, 0.00, '2025', NULL, NULL),
(470, 65, 'vacation', NULL, 0.00, 0.00, '2025', NULL, NULL),
(471, 65, 'paternity', NULL, 0.00, 0.00, '2025', NULL, NULL),
(472, 65, 'maternity', NULL, 0.00, 0.00, '2025', NULL, NULL),
(473, 65, 'solo_parent', NULL, 0.00, 0.00, '2025', NULL, NULL),
(474, 65, 'halfday', NULL, 0.00, 0.00, '2025', NULL, NULL),
(475, 65, 'halfday_sick', NULL, 0.00, 0.00, '2025', NULL, NULL),
(476, 65, 'lwop', NULL, 0.00, 0.00, '2025', NULL, NULL),
(477, 65, 'bereavement', NULL, 0.00, 0.00, '2025', NULL, NULL),
(478, 66, 'sick', NULL, 0.00, 0.00, '2025', NULL, NULL),
(479, 66, 'vacation', NULL, 0.00, 0.00, '2025', NULL, NULL),
(480, 66, 'paternity', NULL, 0.00, 0.00, '2025', NULL, NULL),
(481, 66, 'maternity', NULL, 0.00, 0.00, '2025', NULL, NULL),
(482, 66, 'solo_parent', NULL, 0.00, 0.00, '2025', NULL, NULL),
(483, 66, 'halfday', NULL, 0.00, 0.00, '2025', NULL, NULL),
(484, 66, 'halfday_sick', NULL, 0.00, 0.00, '2025', NULL, NULL),
(485, 66, 'lwop', NULL, 0.00, 0.00, '2025', NULL, NULL),
(486, 66, 'bereavement', NULL, 0.00, 0.00, '2025', NULL, NULL),
(487, 67, 'sick', NULL, 0.00, 0.00, '2025', NULL, NULL),
(488, 67, 'vacation', NULL, 0.00, 0.00, '2025', NULL, NULL),
(489, 67, 'paternity', NULL, 0.00, 0.00, '2025', NULL, NULL),
(490, 67, 'maternity', NULL, 0.00, 0.00, '2025', NULL, NULL),
(491, 67, 'solo_parent', NULL, 0.00, 0.00, '2025', NULL, NULL),
(492, 67, 'halfday', NULL, 0.00, 0.00, '2025', NULL, NULL),
(493, 67, 'halfday_sick', NULL, 0.00, 0.00, '2025', NULL, NULL),
(494, 67, 'lwop', NULL, 0.00, 0.00, '2025', NULL, NULL),
(495, 67, 'bereavement', NULL, 0.00, 0.00, '2025', NULL, NULL),
(496, 68, 'sick', NULL, 0.00, 0.00, '2025', NULL, NULL),
(497, 68, 'vacation', NULL, 0.00, 0.00, '2025', NULL, NULL),
(498, 68, 'paternity', NULL, 0.00, 0.00, '2025', NULL, NULL),
(499, 68, 'maternity', NULL, 0.00, 0.00, '2025', NULL, NULL),
(500, 68, 'solo_parent', NULL, 0.00, 0.00, '2025', NULL, NULL),
(501, 68, 'halfday', NULL, 0.00, 0.00, '2025', NULL, NULL),
(502, 68, 'halfday_sick', NULL, 0.00, 0.00, '2025', NULL, NULL),
(503, 68, 'lwop', NULL, 0.00, 0.00, '2025', NULL, NULL),
(504, 68, 'bereavement', NULL, 0.00, 0.00, '2025', NULL, NULL),
(505, 69, 'sick', NULL, 0.00, 0.00, '2025', NULL, NULL),
(506, 69, 'vacation', NULL, 0.00, 0.00, '2025', NULL, NULL),
(507, 69, 'paternity', NULL, 0.00, 0.00, '2025', NULL, NULL),
(508, 69, 'maternity', NULL, 0.00, 0.00, '2025', NULL, NULL),
(509, 69, 'solo_parent', NULL, 0.00, 0.00, '2025', NULL, NULL),
(510, 69, 'halfday', NULL, 0.00, 0.00, '2025', NULL, NULL),
(511, 69, 'halfday_sick', NULL, 0.00, 0.00, '2025', NULL, NULL),
(512, 69, 'lwop', NULL, 0.00, 0.00, '2025', NULL, NULL),
(513, 69, 'bereavement', NULL, 0.00, 0.00, '2025', NULL, NULL),
(514, 70, 'sick', NULL, 0.00, 0.00, '2025', NULL, NULL),
(515, 70, 'vacation', NULL, 0.00, 0.00, '2025', NULL, NULL),
(516, 70, 'paternity', NULL, 0.00, 0.00, '2025', NULL, NULL),
(517, 70, 'maternity', NULL, 0.00, 0.00, '2025', NULL, NULL),
(518, 70, 'solo_parent', NULL, 0.00, 0.00, '2025', NULL, NULL),
(519, 70, 'halfday', NULL, 0.00, 0.00, '2025', NULL, NULL),
(520, 70, 'halfday_sick', NULL, 0.00, 0.00, '2025', NULL, NULL),
(521, 70, 'lwop', NULL, 0.00, 0.00, '2025', NULL, NULL),
(522, 70, 'bereavement', NULL, 0.00, 0.00, '2025', NULL, NULL),
(523, 71, 'sick', NULL, 0.00, 0.00, '2025', NULL, NULL),
(524, 71, 'vacation', NULL, 0.00, 0.00, '2025', NULL, NULL),
(525, 71, 'paternity', NULL, 0.00, 0.00, '2025', NULL, NULL),
(526, 71, 'maternity', NULL, 0.00, 0.00, '2025', NULL, NULL),
(527, 71, 'solo_parent', NULL, 0.00, 0.00, '2025', NULL, NULL),
(528, 71, 'halfday', NULL, 0.00, 0.00, '2025', NULL, NULL),
(529, 71, 'halfday_sick', NULL, 0.00, 0.00, '2025', NULL, NULL),
(530, 71, 'lwop', NULL, 0.00, 0.00, '2025', NULL, NULL),
(531, 71, 'bereavement', NULL, 0.00, 0.00, '2025', NULL, NULL),
(532, 72, 'sick', NULL, 0.00, 0.00, '2025', NULL, NULL),
(533, 72, 'vacation', NULL, 0.00, 0.00, '2025', NULL, NULL),
(534, 72, 'paternity', NULL, 0.00, 0.00, '2025', NULL, NULL),
(535, 72, 'maternity', NULL, 0.00, 0.00, '2025', NULL, NULL),
(536, 72, 'solo_parent', NULL, 0.00, 0.00, '2025', NULL, NULL),
(537, 72, 'halfday', NULL, 0.00, 0.00, '2025', NULL, NULL),
(538, 72, 'halfday_sick', NULL, 0.00, 0.00, '2025', NULL, NULL),
(539, 72, 'lwop', NULL, 0.00, 0.00, '2025', NULL, NULL),
(540, 72, 'bereavement', NULL, 0.00, 0.00, '2025', NULL, NULL),
(541, 1002, 'sick', NULL, 0.00, 0.00, '2025', NULL, NULL),
(542, 1002, 'vacation', NULL, 0.00, 0.00, '2025', NULL, NULL),
(543, 1002, 'paternity', NULL, 0.00, 0.00, '2025', NULL, NULL),
(544, 1002, 'maternity', NULL, 0.00, 0.00, '2025', NULL, NULL),
(545, 1002, 'solo_parent', NULL, 0.00, 0.00, '2025', NULL, NULL),
(546, 1002, 'halfday', NULL, 0.00, 0.00, '2025', NULL, NULL),
(547, 1002, 'halfday_sick', NULL, 0.00, 0.00, '2025', NULL, NULL),
(548, 1002, 'lwop', NULL, 0.00, 0.00, '2025', NULL, NULL),
(549, 1002, 'bereavement', NULL, 0.00, 0.00, '2025', NULL, NULL),
(550, 1003, 'sick', NULL, 0.00, 0.00, '2025', NULL, NULL),
(551, 1003, 'vacation', NULL, 0.00, 0.00, '2025', NULL, NULL),
(552, 1003, 'paternity', NULL, 0.00, 0.00, '2025', NULL, NULL),
(553, 1003, 'maternity', NULL, 0.00, 0.00, '2025', NULL, NULL),
(554, 1003, 'solo_parent', NULL, 0.00, 0.00, '2025', NULL, NULL),
(555, 1003, 'halfday', NULL, 0.00, 0.00, '2025', NULL, NULL),
(556, 1003, 'halfday_sick', NULL, 0.00, 0.00, '2025', NULL, NULL),
(557, 1003, 'lwop', NULL, 0.00, 0.00, '2025', NULL, NULL),
(558, 1003, 'bereavement', NULL, 0.00, 0.00, '2025', NULL, NULL),
(559, 1004, 'sick', NULL, 0.00, 0.00, '2025', NULL, NULL),
(560, 1004, 'vacation', NULL, 0.00, 0.00, '2025', NULL, NULL),
(561, 1004, 'paternity', NULL, 0.00, 0.00, '2025', NULL, NULL),
(562, 1004, 'maternity', NULL, 0.00, 0.00, '2025', NULL, NULL),
(563, 1004, 'solo_parent', NULL, 0.00, 0.00, '2025', NULL, NULL),
(564, 1004, 'halfday', NULL, 0.00, 0.00, '2025', NULL, NULL),
(565, 1004, 'halfday_sick', NULL, 0.00, 0.00, '2025', NULL, NULL),
(566, 1004, 'lwop', NULL, 0.00, 0.00, '2025', NULL, NULL),
(567, 1004, 'bereavement', NULL, 0.00, 0.00, '2025', NULL, NULL),
(568, 1005, 'sick', NULL, 0.00, 0.00, '2025', NULL, NULL),
(569, 1005, 'vacation', NULL, 0.00, 0.00, '2025', NULL, NULL),
(570, 1005, 'paternity', NULL, 0.00, 0.00, '2025', NULL, NULL),
(571, 1005, 'maternity', NULL, 0.00, 0.00, '2025', NULL, NULL),
(572, 1005, 'solo_parent', NULL, 0.00, 0.00, '2025', NULL, NULL),
(573, 1005, 'halfday', NULL, 0.00, 0.00, '2025', NULL, NULL),
(574, 1005, 'halfday_sick', NULL, 0.00, 0.00, '2025', NULL, NULL),
(575, 1005, 'lwop', NULL, 0.00, 0.00, '2025', NULL, NULL),
(576, 1005, 'bereavement', NULL, 0.00, 0.00, '2025', NULL, NULL),
(577, 1006, 'sick', NULL, 0.00, 0.00, '2025', NULL, NULL),
(578, 1006, 'vacation', NULL, 0.00, 0.00, '2025', NULL, NULL),
(579, 1006, 'paternity', NULL, 0.00, 0.00, '2025', NULL, NULL),
(580, 1006, 'maternity', NULL, 0.00, 0.00, '2025', NULL, NULL),
(581, 1006, 'solo_parent', NULL, 0.00, 0.00, '2025', NULL, NULL),
(582, 1006, 'halfday', NULL, 0.00, 0.00, '2025', NULL, NULL),
(583, 1006, 'halfday_sick', NULL, 0.00, 0.00, '2025', NULL, NULL),
(584, 1006, 'lwop', NULL, 0.00, 0.00, '2025', NULL, NULL),
(585, 1006, 'bereavement', NULL, 0.00, 0.00, '2025', NULL, NULL),
(586, 1007, 'sick', NULL, 0.00, 0.00, '2025', NULL, NULL),
(587, 1007, 'vacation', NULL, 0.00, 0.00, '2025', NULL, NULL),
(588, 1007, 'paternity', NULL, 0.00, 0.00, '2025', NULL, NULL),
(589, 1007, 'maternity', NULL, 0.00, 0.00, '2025', NULL, NULL),
(590, 1007, 'solo_parent', NULL, 0.00, 0.00, '2025', NULL, NULL),
(591, 1007, 'halfday', NULL, 0.00, 0.00, '2025', NULL, NULL),
(592, 1007, 'halfday_sick', NULL, 0.00, 0.00, '2025', NULL, NULL),
(593, 1007, 'lwop', NULL, 0.00, 0.00, '2025', NULL, NULL),
(594, 1007, 'bereavement', NULL, 0.00, 0.00, '2025', NULL, NULL),
(595, 1009, 'sick', 23.00, 0.00, NULL, '2025', NULL, '2025-07-15 13:02:47'),
(596, 1009, 'vacation', 23.00, 0.00, 0.00, '2025', NULL, '2025-07-15 13:02:47'),
(597, 1009, 'paternity', 13.00, 0.00, NULL, '2025', NULL, '2025-07-15 13:02:47'),
(598, 1009, 'maternity', 0.00, 0.00, NULL, '2025', NULL, '2025-07-15 13:02:47'),
(599, 1009, 'solo_parent', 23.00, 0.00, NULL, '2025', NULL, '2025-07-15 13:02:47'),
(600, 1009, 'halfday', 0.00, 0.00, NULL, '2025', NULL, '2025-07-15 13:02:47'),
(601, 1009, 'halfday_sick', 23.00, 0.00, NULL, '2025', NULL, '2025-07-15 13:02:47'),
(602, 1009, 'lwop', 23.00, 0.00, NULL, '2025', NULL, '2025-07-15 13:02:47'),
(603, 1009, 'bereavement', 3.00, 0.00, NULL, '2025', NULL, '2025-07-15 13:02:47'),
(604, 1013, 'sick', NULL, 0.00, 0.00, '2025', NULL, NULL),
(605, 1013, 'vacation', NULL, 0.00, 0.00, '2025', NULL, NULL),
(606, 1013, 'paternity', NULL, 0.00, 0.00, '2025', NULL, NULL),
(607, 1013, 'maternity', NULL, 0.00, 0.00, '2025', NULL, NULL),
(608, 1013, 'solo_parent', NULL, 0.00, 0.00, '2025', NULL, NULL),
(609, 1013, 'halfday', NULL, 0.00, 0.00, '2025', NULL, NULL),
(610, 1013, 'halfday_sick', NULL, 0.00, 0.00, '2025', NULL, NULL),
(611, 1013, 'lwop', NULL, 0.00, 0.00, '2025', NULL, NULL),
(612, 1013, 'bereavement', NULL, 0.00, 0.00, '2025', NULL, NULL);

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
  MODIFY `announcement_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=29;

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1024;

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=51;

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
