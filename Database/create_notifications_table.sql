CREATE TABLE `notifications` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `employee_id` int(11) NOT NULL,
  `message` text NOT NULL,
  `type` varchar(50) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `read_at` timestamp NULL DEFAULT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `employee_id` (`employee_id`),
  KEY `created_at` (`created_at`),
  CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
