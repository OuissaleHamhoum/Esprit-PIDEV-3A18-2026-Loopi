-- phpMyAdmin SQL Dump
-- version 4.1.14
-- http://www.phpmyadmin.net
--
-- Host: 127.0.0.1
-- Generation Time: Apr 18, 2026 at 07:33 PM
-- Server version: 5.6.17
-- PHP Version: 5.5.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;

--
-- Database: `loopi_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `category_produit`
--

CREATE TABLE IF NOT EXISTS `category_produit` (
  `id_cat` int(11) NOT NULL AUTO_INCREMENT,
  `nom_cat` varchar(100) NOT NULL,
  PRIMARY KEY (`id_cat`),
  UNIQUE KEY `nom_cat` (`nom_cat`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=5 ;

--
-- Dumping data for table `category_produit`
--

INSERT INTO `category_produit` (`id_cat`, `nom_cat`) VALUES
(2, 'Art mural '),
(4, 'Installations artistiques'),
(3, 'Mobilier artistique'),
(1, 'Objets décoratifs');

-- --------------------------------------------------------

--
-- Table structure for table `collection`
--

CREATE TABLE IF NOT EXISTS `collection` (
  `id_collection` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `material_type` varchar(255) NOT NULL,
  `image_collection` varchar(255) NOT NULL,
  `goal_amount` double NOT NULL,
  `current_amount` double DEFAULT '0',
  `unit` varchar(50) NOT NULL,
  `status` varchar(50) DEFAULT 'active',
  `id_user` int(11) NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_collection`),
  KEY `idx_user` (`id_user`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=10 ;

--
-- Dumping data for table `collection`
--

INSERT INTO `collection` (`id_collection`, `title`, `material_type`, `image_collection`, `goal_amount`, `current_amount`, `unit`, `status`, `id_user`, `created_at`, `updated_at`) VALUES
(4, 'metals', 'Métal', 'coll_69d4823045eca.jpg', 1000, 1197.22, 'kg', 'active', 2, '2026-04-07 03:04:00', '2026-04-07 05:05:33'),
(6, 'plastic', 'Plastique', 'coll_69d49e84bf2c6.jpg', 5000, 0, 'kg', 'active', 2, '2026-04-07 05:04:52', '2026-04-07 05:04:52'),
(8, 'glass collection', 'Verre', 'coll_69d5067c6efc9.jpg', 9999, 50, 'kg', 'active', 2, '2026-04-07 12:28:28', '2026-04-07 13:07:53'),
(9, 'collection paper', 'Papier', 'coll_69d50f397616d.jpg', 1000, 55, 'kg', 'active', 2, '2026-04-07 13:05:45', '2026-04-18 15:45:50');

-- --------------------------------------------------------

--
-- Table structure for table `content`
--

CREATE TABLE IF NOT EXISTS `content` (
  `id_content` int(11) NOT NULL AUTO_INCREMENT,
  `id_commande` int(11) NOT NULL,
  `id_produit` int(11) NOT NULL,
  `quantite` int(11) NOT NULL,
  `prix_unitaire` decimal(10,2) NOT NULL,
  PRIMARY KEY (`id_content`),
  UNIQUE KEY `unique_commande_produit` (`id_commande`,`id_produit`),
  KEY `id_produit` (`id_produit`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `coupon`
--

CREATE TABLE IF NOT EXISTS `coupon` (
  `id_coupon` int(11) NOT NULL AUTO_INCREMENT,
  `code` varchar(50) NOT NULL,
  `discount_percent` decimal(5,2) NOT NULL,
  `donation_date` date DEFAULT NULL,
  `used` tinyint(1) DEFAULT '0',
  `id_user` int(11) DEFAULT NULL,
  `id_donation` int(11) DEFAULT NULL,
  `expiration_date` date DEFAULT NULL,
  `min_amount` decimal(10,2) DEFAULT '0.00',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_coupon`),
  UNIQUE KEY `code` (`code`),
  KEY `id_user` (`id_user`),
  KEY `id_donation` (`id_donation`),
  KEY `idx_code` (`code`),
  KEY `idx_used` (`used`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=3 ;

--
-- Dumping data for table `coupon`
--

INSERT INTO `coupon` (`id_coupon`, `code`, `discount_percent`, `donation_date`, `used`, `id_user`, `id_donation`, `expiration_date`, `min_amount`, `created_at`) VALUES
(1, 'ECO10', '10.00', '2026-02-08', 0, 3, 1, '2026-03-10', '0.00', '2026-02-08 13:08:00'),
(2, 'GREEN15', '15.00', '2026-02-08', 0, 4, 2, '2026-04-09', '0.00', '2026-02-08 13:08:00');

-- --------------------------------------------------------

--
-- Table structure for table `donation`
--

CREATE TABLE IF NOT EXISTS `donation` (
  `id_donation` int(11) NOT NULL AUTO_INCREMENT,
  `id_user` int(11) NOT NULL,
  `id_collection` int(11) NOT NULL,
  `amount` double NOT NULL,
  `donation_date` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `status` enum('en_attente','confirmé','annulé') NOT NULL DEFAULT 'en_attente',
  PRIMARY KEY (`id_donation`),
  KEY `fk_donation_user` (`id_user`),
  KEY `fk_donation_collection` (`id_collection`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=15 ;

--
-- Dumping data for table `donation`
--

INSERT INTO `donation` (`id_donation`, `id_user`, `id_collection`, `amount`, `donation_date`, `status`) VALUES
(1, 3, 1, 2.4, '2026-02-11 18:07:31', 'confirmé'),
(2, 3, 1, 5.8, '2026-02-11 20:20:38', 'confirmé'),
(4, 3, 4, 10, '2026-04-07 03:17:21', 'confirmé'),
(6, 3, 4, 0.22, '2026-04-07 04:13:39', 'confirmé'),
(7, 3, 4, 50, '2026-04-07 04:14:42', 'confirmé'),
(8, 3, 4, 41, '2026-04-07 04:21:24', 'confirmé'),
(10, 3, 4, 44, '2026-04-07 04:27:10', 'confirmé'),
(12, 3, 4, 52, '2026-04-07 04:38:33', 'confirmé'),
(13, 3, 4, 1000, '2026-04-07 05:05:33', 'confirmé'),
(14, 3, 9, 55, '2026-04-18 15:45:50', 'confirmé');

-- --------------------------------------------------------

--
-- Table structure for table `evenement`
--

CREATE TABLE IF NOT EXISTS `evenement` (
  `id_evenement` int(11) NOT NULL AUTO_INCREMENT,
  `titre` varchar(200) NOT NULL,
  `description` text,
  `date_evenement` datetime NOT NULL,
  `lieu` varchar(200) DEFAULT NULL,
  `id_organisateur` int(11) NOT NULL,
  `capacite_max` int(11) DEFAULT NULL,
  `image_evenement` varchar(255) DEFAULT NULL,
  `statut` varchar(20) NOT NULL DEFAULT 'en_attente',
  `statut_validation` varchar(20) DEFAULT 'en_attente',
  `date_soumission` datetime DEFAULT NULL,
  `date_validation` datetime DEFAULT NULL,
  `commentaire_validation` text DEFAULT NULL,
  `latitude` decimal(10,8) DEFAULT NULL,
  `longitude` decimal(11,8) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_evenement`),
  KEY `id_organisateur` (`id_organisateur`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=3 ;

--
-- Dumping data for table `evenement`
--

INSERT INTO `evenement` (`id_evenement`, `titre`, `description`, `date_evenement`, `lieu`, `id_organisateur`, `capacite_max`, `image_evenement`, `statut`, `statut_validation`, `created_at`) VALUES
(1, 'Nettoyage de plage', 'Journée de nettoyage', '2026-06-15 09:00:00', 'Plage Sousse', 2, 50, NULL, 'en_attente', 'valide', '2026-02-08 13:08:00'),
(2, 'Atelier recyclage', 'Apprenez à recycler', '2026-06-20 14:00:00', 'Centre Tunis', 2, 30, NULL, 'en_attente', 'valide', '2026-02-08 13:08:00');

-- --------------------------------------------------------

--
-- Table structure for table `favoris`
--

CREATE TABLE IF NOT EXISTS `favoris` (
  `id_favoris` int(11) NOT NULL AUTO_INCREMENT,
  `id_user` int(11) NOT NULL,
  `id_produit` int(11) NOT NULL,
  `date_ajout` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_favoris`),
  UNIQUE KEY `unique_favoris` (`id_user`,`id_produit`),
  KEY `id_produit` (`id_produit`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=33 ;

--
-- Dumping data for table `favoris`
--

INSERT INTO `favoris` (`id_favoris`, `id_user`, `id_produit`, `date_ajout`) VALUES
(20, 7, 5, '2026-02-19 16:38:01'),
(24, 3, 2, '2026-02-20 14:23:00'),
(25, 7, 2, '2026-02-20 14:25:47'),
(28, 7, 11, '2026-02-20 15:03:08'),
(29, 3, 5, '2026-02-20 17:58:55'),
(30, 3, 3, '2026-02-20 17:59:14'),
(31, 3, 11, '2026-02-28 05:17:23'),
(32, 3, 8, '2026-03-02 00:28:19');

-- --------------------------------------------------------

--
-- Table structure for table `feedback`
--

CREATE TABLE IF NOT EXISTS `feedback` (
  `id_feedback` int(11) NOT NULL AUTO_INCREMENT,
  `id_user` int(11) NOT NULL,
  `note` int(11) NOT NULL,
  `commentaire` varchar(255) NOT NULL,
  `date_commentaire` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `id_produit` int(11) NOT NULL,
  PRIMARY KEY (`id_feedback`),
  KEY `idx_user` (`id_user`),
  KEY `idx_date` (`date_commentaire`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=39 ;

--
-- Dumping data for table `feedback`
--

INSERT INTO `feedback` (`id_feedback`, `id_user`, `note`, `commentaire`, `date_commentaire`, `id_produit`) VALUES
(14, 4, 5, 'BEHYA BARCHA', '2026-02-15 21:45:05', 1),
(17, 3, 4, 'hlouwa <3 <3', '2026-02-15 21:48:05', 2),
(19, 3, 4, 'GOOD', '2026-02-17 15:40:48', 3),
(20, 3, 4, 'JOOOOOLIE', '2026-02-20 14:09:32', 11),
(21, 3, 4, 'GOODDDD', '2026-02-20 14:12:09', 3),
(22, 7, 3, 'BEHYA', '2026-02-20 14:19:43', 5),
(23, 3, 4, 'azeaze', '2026-02-20 14:55:03', 1),
(24, 3, 3, 'hlou', '2026-02-20 14:56:58', 11),
(25, 7, 5, 'WOOWWWWWWWWWW', '2026-02-20 15:03:17', 11),
(26, 3, 4, 'yaahah', '2026-02-20 15:21:05', 8),
(27, 3, 4, 'AZEAEZ', '2026-02-20 16:29:00', 8),
(28, 3, 3, 'joli', '2026-02-21 20:23:25', 11),
(29, 3, 5, 'tres tres joli et magnefique produit', '2026-02-21 20:57:48', 11),
(31, 3, 1, 'très mauvais produit', '2026-02-21 21:04:03', 11),
(33, 3, 5, 'tres bon produit', '2026-02-21 21:04:59', 11),
(34, 3, 5, 'super', '2026-02-21 21:06:24', 11),
(36, 3, 2, 'shit', '2026-02-28 06:08:08', 5),
(37, 3, 4, 'fuck', '2026-02-28 06:08:21', 11),
(38, 3, 3, 'what', '2026-03-02 00:52:25', 5);

-- --------------------------------------------------------

--
-- Table structure for table `genre`
--

CREATE TABLE IF NOT EXISTS `genre` (
  `id_genre` int(11) NOT NULL AUTO_INCREMENT,
  `sexe` varchar(50) NOT NULL,
  PRIMARY KEY (`id_genre`),
  UNIQUE KEY `sexe` (`sexe`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=4 ;

--
-- Dumping data for table `genre`
--

INSERT INTO `genre` (`id_genre`, `sexe`) VALUES
(2, 'Femme'),
(1, 'Homme'),
(3, 'Non spécifié');

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE IF NOT EXISTS `notifications` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `id_user` int(11) NOT NULL,
  `type` varchar(50) NOT NULL,
  `titre` varchar(200) NOT NULL,
  `message` text NOT NULL,
  `is_read` tinyint(1) DEFAULT '0',
  `id_evenement` int(11) DEFAULT NULL,
  `id_participation` int(11) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `id_evenement` (`id_evenement`),
  KEY `idx_user_read` (`id_user`,`is_read`),
  KEY `idx_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE IF NOT EXISTS `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nom` varchar(100) NOT NULL,
  `prenom` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(100) NOT NULL,
  `photo` varchar(255) DEFAULT 'default.jpg',
  `role` enum('admin','organisateur','participant') DEFAULT 'participant',
  `id_genre` int(11) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `has_donated_first_time` tinyint(1) DEFAULT '0',
  `total_plastic` double DEFAULT '0',
  `total_paper` double DEFAULT '0',
  `total_glass` double DEFAULT '0',
  `total_metal` double DEFAULT '0',
  `total_cardboard` double DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`),
  KEY `id_genre` (`id_genre`),
  KEY `idx_email` (`email`),
  KEY `idx_role` (`role`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=7 ;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `nom`, `prenom`, `email`, `password`, `photo`, `role`, `id_genre`, `created_at`, `updated_at`, `has_donated_first_time`, `total_plastic`, `total_paper`, `total_glass`, `total_metal`, `total_cardboard`) VALUES
(1, 'Admin', 'System', 'admin@loopi.tn', 'admin123', 'default.jpg', 'admin', 1, '2026-02-08 12:08:00', '2026-02-08 12:08:00', 0, 0, 0, 0, 0, 0),
(2, 'Organisateur', 'Eco', 'organisateur@loopi.tn', 'org123', 'default.jpg', 'organisateur', 1, '2026-02-08 12:08:00', '2026-04-18 17:30:44', 0, 0, 0, 0, 0, 0),
(3, 'Participant', 'Test', 'participant@loopi.tn', 'part123', 'default.jpg', 'participant', 2, '2026-02-08 12:08:00', '2026-04-07 05:09:16', 0, 0, 0, 0, 0, 0),
(4, 'Ben', 'Ali', 'ben.ali@email.com', 'ben123', 'default.jpg', 'participant', 1, '2026-02-08 12:08:00', '2026-02-08 12:08:00', 0, 0, 0, 0, 0, 0),
(5, 'Dupont', 'Marie', 'marie.dupont@email.com', 'marie123', 'default.jpg', 'participant', 2, '2026-02-08 12:08:00', '2026-02-08 12:08:00', 0, 0, 0, 0, 0, 0),
(6, 'Martin', 'Pierre', 'pierre@email.com', 'pierre123', 'default.jpg', 'organisateur', 1, '2026-02-08 12:08:00', '2026-02-09 23:16:58', 0, 0, 0, 0, 0, 0);

--
-- Constraints for dumped tables
--

--
-- Constraints for table `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`id_user`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `notifications_ibfk_2` FOREIGN KEY (`id_evenement`) REFERENCES `evenement` (`id_evenement`) ON DELETE CASCADE;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
