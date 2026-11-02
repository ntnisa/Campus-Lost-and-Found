
CREATE DATABASE IF NOT EXISTS `campus_lost_and_found` 
DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE `campus_lost_and_found`;

CREATE TABLE IF NOT EXISTS `users` (
  `user_id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(100) NOT NULL,
  `email` VARCHAR(150) NOT NULL UNIQUE,
  `phone` VARCHAR(20) DEFAULT NULL,
  `password` VARCHAR(255) NOT NULL,
  `user_type` ENUM('Student', 'Teacher', 'Staff', 'Admin') NOT NULL DEFAULT 'Student',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `categories` (
  `category_id` INT AUTO_INCREMENT PRIMARY KEY,
  `category_name` VARCHAR(100) NOT NULL UNIQUE,
  `description` TEXT DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `locations` (
  `location_id` INT AUTO_INCREMENT PRIMARY KEY,
  `location_name` VARCHAR(150) NOT NULL UNIQUE,
  `description` TEXT DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `items` (
  `item_id` INT AUTO_INCREMENT PRIMARY KEY,
  `item_name` VARCHAR(150) NOT NULL,
  `description` TEXT DEFAULT NULL,
  `color` VARCHAR(50) DEFAULT NULL,
  `category_id` INT NOT NULL,
  `status` ENUM('Lost', 'Found', 'Claimed', 'Returned') NOT NULL DEFAULT 'Lost',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX (`category_id`),
  CONSTRAINT `fk_items_category` FOREIGN KEY (`category_id`) REFERENCES `categories` (`category_id`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `lost_reports` (
  `lost_report_id` INT AUTO_INCREMENT PRIMARY KEY,
  `item_id` INT NOT NULL UNIQUE,
  `user_id` INT NOT NULL,
  `location_id` INT NOT NULL,
  `lost_date` DATE NOT NULL,
  `report_date` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `additional_details` TEXT DEFAULT NULL,
  INDEX (`user_id`),
  INDEX (`location_id`),
  CONSTRAINT `fk_lost_item` FOREIGN KEY (`item_id`) REFERENCES `items` (`item_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_lost_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_lost_location` FOREIGN KEY (`location_id`) REFERENCES `locations` (`location_id`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `found_reports` (
  `found_report_id` INT AUTO_INCREMENT PRIMARY KEY,
  `item_id` INT NOT NULL UNIQUE,
  `user_id` INT NOT NULL,
  `location_id` INT NOT NULL,
  `found_date` DATE NOT NULL,
  `report_date` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `additional_details` TEXT DEFAULT NULL,
  INDEX (`user_id`),
  INDEX (`location_id`),
  CONSTRAINT `fk_found_item` FOREIGN KEY (`item_id`) REFERENCES `items` (`item_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_found_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_found_location` FOREIGN KEY (`location_id`) REFERENCES `locations` (`location_id`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `claims` (
  `claim_id` INT AUTO_INCREMENT PRIMARY KEY,
  `item_id` INT NOT NULL,
  `user_id` INT NOT NULL,
  `claim_date` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `identification_details` TEXT NOT NULL,
  `status` ENUM('Pending', 'Approved', 'Rejected') NOT NULL DEFAULT 'Pending',
  `verified_by` INT DEFAULT NULL,
  INDEX (`item_id`),
  INDEX (`user_id`),
  INDEX (`verified_by`),
  CONSTRAINT `fk_claims_item` FOREIGN KEY (`item_id`) REFERENCES `items` (`item_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_claims_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_claims_verifier` FOREIGN KEY (`verified_by`) REFERENCES `users` (`user_id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `users` (`user_id`, `name`, `email`, `phone`, `password`, `user_type`) VALUES
(1, 'Student User', 'student@example.com', '01711000001', '$2y$10$7Z8M67d64H2m6i7qF8KquO89u3D0Vw12T.qM9vA91U1uW5Z67G6/e', 'Student'),
(2, 'Tanvir Hasan', 'tanvir@example.com', '01711000002', '$2y$10$7Z8M67d64H2m6i7qF8KquO89u3D0Vw12T.qM9vA91U1uW5Z67G6/e', 'Student'),
(3, 'Rahim Ahmed', 'rahim@example.com', '01811000003', '$2y$10$7Z8M67d64H2m6i7qF8KquO89u3D0Vw12T.qM9vA91U1uW5Z67G6/e', 'Teacher'),
(4, 'Administrator', 'admin@example.com', '01911000004', '$2y$10$7Z8M67d64H2m6i7qF8KquO89u3D0Vw12T.qM9vA91U1uW5Z67G6/e', 'Admin'),
(5, 'Karim Ullah', 'karim@example.com', '01611000005', '$2y$10$7Z8M67d64H2m6i7qF8KquO89u3D0Vw12T.qM9vA91U1uW5Z67G6/e', 'Staff')
ON DUPLICATE KEY UPDATE `name`=VALUES(`name`);

INSERT INTO `categories` (`category_id`, `category_name`, `description`) VALUES
(1, 'Electronics', 'Phones, laptops, chargers, flash drives, and headphones'),
(2, 'Documents', 'University ID cards, admit cards, certificates, and files'),
(3, 'Bags', 'Backpacks, side bags, purse, and pencil cases'),
(4, 'Accessories', 'Watches, glasses, keys, water bottles, and umbrellas'),
(5, 'Books', 'Textbooks, lab manuals, class notebooks, and stationery'),
(6, 'Others', 'Clothing, sports equipment, and miscellaneous campus belongings')
ON DUPLICATE KEY UPDATE `category_name`=VALUES(`category_name`);

INSERT INTO `locations` (`location_id`, `location_name`, `description`) VALUES
(1, 'Academic Building - Corridors', 'Corridors and staircases across floors 1-5'),
(2, 'Central Library', 'Main study hall, reading section, and digital resource center'),
(3, 'University Cafeteria', 'Student cafeteria and dining hall area'),
(4, 'Computer Lab - Lab 03', 'CSE Department Programming & DBMS Laboratory'),
(5, 'University Auditorium', 'Main stage, auditorium lobby, and seminar room'),
(6, 'Campus Playground & Sports Complex', 'Outdoor sports field, pavilion, and open lounge')
ON DUPLICATE KEY UPDATE `location_name`=VALUES(`location_name`);

INSERT INTO `items` (`item_id`, `item_name`, `description`, `color`, `category_id`, `status`) VALUES
(1, 'Samsung Galaxy Phone', 'Black Samsung smartphone with a transparent cover and cracked screen protector', 'Black', 1, 'Lost'),
(2, 'University Student ID Card', 'Metropolitan University student identity card of CSE 62nd Batch', 'White', 2, 'Lost'),
(3, 'Navy Blue Backpack', 'Navy blue backpack containing notebooks and a pencil case', 'Blue', 3, 'Found'),
(4, 'Casio Silver Wrist Watch', 'Silver metallic analog wrist watch with minor scratch on back', 'Silver', 4, 'Claimed'),
(5, 'Database Systems Textbook', 'Fundamentals of Database Systems by Elmasri & Navathe (7th Edition)', 'Blue', 5, 'Lost'),
(6, 'SanDisk 32GB Flash Drive', 'Red and black swivel SanDisk USB 3.0 flash drive containing lab work', 'Red', 1, 'Found'),
(7, 'Wireless Bluetooth Earbuds', 'White charging case with wireless stereo earbuds inside', 'White', 1, 'Found'),
(8, 'HP Laptop Charger 65W', 'Original HP blue-pin laptop power adapter with power cable', 'Black', 1, 'Found')
ON DUPLICATE KEY UPDATE `item_name`=VALUES(`item_name`);

INSERT INTO `lost_reports` (`lost_report_id`, `item_id`, `user_id`, `location_id`, `lost_date`, `additional_details`) VALUES
(1, 1, 1, 1, '2026-10-25', 'Lost around 2:30 PM after CSE 224 lecture. Phone is switched off.'),
(2, 2, 2, 2, '2026-10-26', 'Left on the third table from the library entrance during study period.'),
(3, 5, 1, 4, '2026-10-27', 'Forgotten on PC station 14 in Computer Lab 03 after database practical.')
ON DUPLICATE KEY UPDATE `item_id`=VALUES(`item_id`);

INSERT INTO `found_reports` (`found_report_id`, `item_id`, `user_id`, `location_id`, `found_date`, `additional_details`) VALUES
(1, 3, 3, 2, '2026-10-26', 'Found unattended on a chair in the central library reading zone.'),
(2, 4, 3, 3, '2026-10-27', 'Found on the counter table in the cafeteria around noon.'),
(3, 6, 2, 4, '2026-10-28', 'Plugged in and left behind on workstation 07 in Computer Lab 03.'),
(4, 7, 5, 5, '2026-10-29', 'Found under seat C12 after the orientation ceremony in the auditorium.'),
(5, 8, 3, 1, '2026-10-30', 'Found plugged into wall socket in Room 302, Academic Building.')
ON DUPLICATE KEY UPDATE `item_id`=VALUES(`item_id`);

INSERT INTO `claims` (`claim_id`, `item_id`, `user_id`, `claim_date`, `identification_details`, `status`, `verified_by`) VALUES
(1, 3, 1, '2026-10-30 10:15:00', 'The backpack contains a university notebook with personal study notes on the first page.', 'Pending', NULL),
(2, 4, 2, '2026-10-30 11:30:00', 'The watch has a Casio logo, silver strap, and small scratch near the 4 o clock marker.', 'Approved', 4),
(3, 7, 1, '2026-10-31 14:00:00', 'The earbud charging case has a tiny cat sticker on the bottom and connects to device named Nisa Earbuds.', 'Pending', NULL)
ON DUPLICATE KEY UPDATE `item_id`=VALUES(`item_id`);

