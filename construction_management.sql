-- phpMyAdmin SQL Dump
-- version 4.1.6
-- http://www.phpmyadmin.net
--
-- Host: 127.0.0.1
-- Generation Time: Oct 07, 2025 at 10:42 AM
-- Server version: 5.6.16
-- PHP Version: 5.5.9

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;

--
-- Database: `construction_management`
--

-- --------------------------------------------------------

--
-- Table structure for table `complaints`
--

CREATE TABLE IF NOT EXISTS `complaints` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `project_id` int(11) NOT NULL,
  `client_id` int(11) NOT NULL,
  `complaint_text` text NOT NULL,
  `status` varchar(100) NOT NULL,
  `response` text NOT NULL,
  `complaint_date` date NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `payment`
--

CREATE TABLE IF NOT EXISTS `payment` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `project_id` int(11) NOT NULL,
  `client_id` int(11) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `card_type` varchar(20) NOT NULL,
  `card_name` varchar(100) NOT NULL,
  `card_no` varchar(16) NOT NULL,
  `cvv` varchar(3) NOT NULL,
  `payment_date` datetime NOT NULL,
  `status` varchar(20) DEFAULT 'completed',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `progress_updates`
--

CREATE TABLE IF NOT EXISTS `progress_updates` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `project_id` int(11) NOT NULL,
  `project_manager_id` int(11) NOT NULL,
  `update_date` date NOT NULL,
  `description` text NOT NULL,
  `progress_percentage` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `projects`
--

CREATE TABLE IF NOT EXISTS `projects` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `project_manager_id` int(11) DEFAULT NULL,
  `name` varchar(150) NOT NULL,
  `location` varchar(255) DEFAULT NULL,
  `description` text,
  `requirements` text,
  `client_id` int(11) NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date DEFAULT NULL,
  `project_type` varchar(100) DEFAULT NULL,
  `budget` varchar(150) NOT NULL,
  `duration` int(11) DEFAULT NULL,
  `status` varchar(150) NOT NULL,
  `image` tinytext NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `fk_project_manager` (`project_manager_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `project_managers`
--

CREATE TABLE IF NOT EXISTS `project_managers` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `phone` varchar(15) NOT NULL,
  `address` text,
  `image` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=5 ;

--
-- Dumping data for table `project_managers`
--

INSERT INTO `project_managers` (`id`, `name`, `email`, `password`, `phone`, `address`, `image`, `created_at`) VALUES
(1, 'Thomas Albin', 'thomasalbin@yandex.com', 'Thomas Albin@123', '9786467423', '123 Main Street\r\nSmithville, OH 45489', 'pm_1_1759005852.jpg', '2025-09-24 16:53:26'),
(2, 'James Wilson', 'jameswilson@yandex.com', 'James Wilson@123', '9786467423', 'The Book Nook\r\n45 Oak Avenue\r\nFairview, CA 94585', 'pm_2_1759005993.jpg', '2025-09-24 16:59:34'),
(3, 'Sarah Chen', 'sarahchen@yandex.com', 'Sarah Chen@123', '9563274759', 'RR 2 Box 15\r\nElk Creek, CO 81635', 'pm_3_1759006031.jpg', '2025-09-24 17:00:23'),
(4, 'Marcus Johnson', 'marcusjohnson@yandex.com', 'Marcus Johnson@123', '6767483922', 'P.O. Box 100\r\nGreenwood, ME 04462', 'pm_4_1759006076.jpg', '2025-09-24 17:09:30');

-- --------------------------------------------------------

--
-- Table structure for table `project_manager_assignments`
--

CREATE TABLE IF NOT EXISTS `project_manager_assignments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `project_id` int(11) NOT NULL,
  `project_manager_id` int(11) NOT NULL,
  `assigned_date` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `project_progress_logs`
--

CREATE TABLE IF NOT EXISTS `project_progress_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `project_id` int(11) DEFAULT NULL,
  `progress` int(11) DEFAULT NULL,
  `status` varchar(50) DEFAULT NULL,
  `file_name` varchar(255) DEFAULT NULL,
  `updated_by` int(11) DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `project_id` (`project_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `resources`
--

CREATE TABLE IF NOT EXISTS `resources` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `project_id` int(11) NOT NULL,
  `type` varchar(100) NOT NULL,
  `name` varchar(100) NOT NULL,
  `quantity` int(11) NOT NULL,
  `cost` varchar(100) NOT NULL,
  `status` varchar(50) DEFAULT 'pending',
  `requested_by` int(11) DEFAULT NULL,
  `request_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `urgency` varchar(20) DEFAULT 'normal',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `support_tickets`
--

CREATE TABLE IF NOT EXISTS `support_tickets` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `client_id` int(11) NOT NULL,
  `subject` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `priority` varchar(20) DEFAULT 'medium',
  `status` varchar(20) DEFAULT 'open',
  `admin_response` text,
  `responded_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE IF NOT EXISTS `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `phone` varchar(100) NOT NULL,
  `password` varchar(100) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=16 ;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `phone`, `password`) VALUES
(11, 'Freddy', 'freddy@yandex.com', '9678564319', 'Freddy@123'),
(12, 'Alex', 'alex@yandex.com', '8796472340', 'Alex@123'),
(13, 'Trevor', 'trevor@yandex.com', '8796472354', 'Trevor@123'),
(14, 'Michael', 'michael@yandex.com', '8796474557', 'Michael@123'),
(15, 'Franklin', 'franklin@yandex.com', '9581298754', 'Franklin@123');

--
-- Constraints for dumped tables
--

--
-- Constraints for table `projects`
--
ALTER TABLE `projects`
  ADD CONSTRAINT `fk_project_manager` FOREIGN KEY (`project_manager_id`) REFERENCES `project_managers` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `project_progress_logs`
--
ALTER TABLE `project_progress_logs`
  ADD CONSTRAINT `project_progress_logs_ibfk_1` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`);

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
