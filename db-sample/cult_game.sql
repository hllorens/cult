-- phpMyAdmin SQL Dump
-- version 3.3.10.4
-- http://www.phpmyadmin.net
--
-- Host: mysql.cognitionis.com
-- Generation Time: Nov 13, 2015 at 11:18 AM
-- Server version: 5.1.56
-- PHP Version: 5.3.29

SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;

--
-- Database: `cult_game`
--

-- --------------------------------------------------------

--
-- Table structure for table `sessions`
--

CREATE TABLE IF NOT EXISTS `sessions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user` varchar(100) NOT NULL,
  `type` varchar(100) NOT NULL,
  `level` varchar(50) NOT NULL DEFAULT 'normal',
  `num_correct` varchar(10) NOT NULL,
  `timestamp` varchar(16) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=50 ;

--
-- Dumping data for table `sessions`
--

INSERT INTO `sessions` (`id`, `user`, `type`, `level`, `num_correct`, `timestamp`) VALUES
(44, 'hectorlm1983@gmail.com', 'qa', 'difficult', '0', '2015-11-01 13:18'),
(36, 'hector.llorens.martinez@gmail.com', 'qa', 'normal', '34', '2015-10-27 00:41'),
(43, 'hectorlm1983@gmail.com', 'qa', 'normal', '32', '2015-10-22 10:06'),
(42, 'hectorlm1983@gmail.com', 'qa', 'normal', '30', '2015-10-22 10:00'),
(41, 'hectorlm1983@gmail.com', 'qa', 'easy', '0', '2015-11-01 12:47'),
(49, 'hectorlm1983@gmail.com', 'qa', 'difficult', '13', '2015-11-13 11:30'),
(48, 'hectorlm1983@gmail.com', 'qa', 'normal', '18', '2015-11-12 19:11');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE IF NOT EXISTS `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `email` varchar(100) NOT NULL,
  `access_level` varchar(100) NOT NULL,
  `display_name` varchar(100) NOT NULL,
  `password` varchar(50) NOT NULL,
  `avatar` varchar(100) NOT NULL,
  `last_login` varchar(19) NOT NULL,
  `last_provider` varchar(100) NOT NULL,
  `creation_timestamp` varchar(19) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=13 ;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `email`, `access_level`, `display_name`, `password`, `avatar`, `last_login`, `last_provider`, `creation_timestamp`) VALUES
(2, 'hectorlm1983@gmail.com', 'admin', 'Héctor Admin', '', '', '2015-11-03 20:22:20', 'google', '2015-10-27 00:41:11'),
(8, 'hector.llorens.martinez@gmail.com', 'normal', 'Hector Llorens', '', '', '2015-10-27 00:41:11', 'google', '2015-10-27 00:41:11');
