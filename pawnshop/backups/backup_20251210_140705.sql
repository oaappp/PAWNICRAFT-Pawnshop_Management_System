-- MariaDB dump 10.19  Distrib 10.4.32-MariaDB, for Win64 (AMD64)
--
-- Host: 127.0.0.1    Database: pawnshop_db
-- ------------------------------------------------------
-- Server version	10.4.32-MariaDB

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `auctions`
--

DROP TABLE IF EXISTS `auctions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `auctions` (
  `auction_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `transaction_id` int(10) unsigned NOT NULL,
  `auction_date` date NOT NULL,
  `starting_price` decimal(12,2) NOT NULL,
  `winning_bid` decimal(12,2) DEFAULT NULL,
  `bidder_name` varchar(150) DEFAULT NULL,
  `bidder_contact` varchar(100) DEFAULT NULL,
  `status` enum('pending','sold','cancelled') NOT NULL DEFAULT 'pending',
  `processed_by` int(10) unsigned NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`auction_id`),
  KEY `fk_auctions_transaction` (`transaction_id`),
  KEY `fk_auctions_user` (`processed_by`),
  KEY `idx_auction_status` (`status`),
  CONSTRAINT `fk_auctions_transaction` FOREIGN KEY (`transaction_id`) REFERENCES `pawn_transactions` (`transaction_id`),
  CONSTRAINT `fk_auctions_user` FOREIGN KEY (`processed_by`) REFERENCES `users` (`user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `auctions`
--

LOCK TABLES `auctions` WRITE;
/*!40000 ALTER TABLE `auctions` DISABLE KEYS */;
INSERT INTO `auctions` VALUES (1,22,'2025-12-09',17000.00,18000.00,'Miguel San Jose','09393927373','pending',1,'2025-12-09 22:57:44');
/*!40000 ALTER TABLE `auctions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `audit_logs`
--

DROP TABLE IF EXISTS `audit_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `audit_logs` (
  `log_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(10) unsigned DEFAULT NULL,
  `action_type` varchar(50) NOT NULL,
  `table_affected` varchar(50) NOT NULL,
  `record_id` int(10) unsigned NOT NULL,
  `old_values` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`old_values`)),
  `new_values` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`new_values`)),
  `ip_address` varchar(45) DEFAULT NULL,
  `timestamp` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`log_id`),
  KEY `fk_audit_user` (`user_id`),
  KEY `idx_audit_table_record` (`table_affected`,`record_id`),
  CONSTRAINT `fk_audit_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=18 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `audit_logs`
--

LOCK TABLES `audit_logs` WRITE;
/*!40000 ALTER TABLE `audit_logs` DISABLE KEYS */;
INSERT INTO `audit_logs` VALUES (1,1,'insert','customers',8,NULL,'{\"customer_code\":\"CUST-2025-00001\"}','::1','2025-12-09 22:20:40'),(2,1,'insert','customers',9,NULL,'{\"customer_code\":\"CUST-2025-00002\"}','::1','2025-12-09 22:21:58'),(3,1,'insert','customers',10,NULL,'{\"customer_code\":\"CUST-2025-00003\"}','::1','2025-12-09 22:23:14'),(4,1,'insert','customers',11,NULL,'{\"customer_code\":\"CUST-2025-00004\"}','::1','2025-12-09 22:24:17'),(5,1,'insert','customers',12,NULL,'{\"customer_code\":\"CUST-2025-00005\"}','::1','2025-12-09 22:25:20'),(6,1,'insert','pawn_transactions',20,NULL,'{\"pawn_ticket_number\":\"PT-20251209-00001\"}','::1','2025-12-09 22:33:54'),(7,1,'insert','pawn_transactions',21,NULL,'{\"pawn_ticket_number\":\"PT-20251209-00002\"}','::1','2025-12-09 22:34:47'),(8,1,'insert','pawn_transactions',22,NULL,'{\"pawn_ticket_number\":\"PT-20251209-00003\"}','::1','2025-12-09 22:35:11'),(9,1,'insert','pawn_transactions',23,NULL,'{\"pawn_ticket_number\":\"PT-20251209-00004\"}','::1','2025-12-09 22:36:47'),(10,1,'insert','pawn_transactions',24,NULL,'{\"pawn_ticket_number\":\"PT-20251209-00005\"}','::1','2025-12-09 22:53:21'),(11,1,'insert','pawn_transactions',25,NULL,'{\"pawn_ticket_number\":\"PT-20251209-00006\"}','::1','2025-12-09 22:54:09'),(12,1,'update','pawn_transactions',20,NULL,'{\"status\":\"redeemed\"}','::1','2025-12-09 22:55:31'),(13,1,'update','pawn_transactions',21,NULL,'{\"status\":\"renewed\",\"maturity_date\":\"2026-02-07\",\"grace_period_end\":\"2026-02-22\"}','::1','2025-12-09 22:56:36'),(14,1,'update','auctions',1,NULL,'{\"auction_status\":\"pending\",\"transaction_status\":\"auctioned\",\"winning_bid\":18000}','::1','2025-12-09 22:58:57'),(15,1,'insert','users',5,NULL,'{\"username\":\"cashier\",\"role\":\"cashier\",\"status\":\"active\"}','::1','2025-12-09 23:00:41'),(16,1,'insert','users',6,NULL,'{\"username\":\"appra\",\"role\":\"appraiser\",\"status\":\"active\"}','::1','2025-12-10 09:14:11'),(17,1,'update','users',6,'{\"user_id\":6,\"username\":\"appra\",\"password_hash\":\"$2y$10$rtqE\\/c4PT278JilfUcEbGetrcfEYszKXkcxLIz67FuVgSUYxN.Yf6\",\"full_name\":\"Drei Herrera\",\"email\":\"drei@gmail.com\",\"role\":\"appraiser\",\"status\":\"active\",\"last_login\":null,\"created_at\":\"2025-12-10 09:14:11\"}','{\"username\":\"appraiser\",\"full_name\":\"Drei Herrera\",\"email\":\"drei@gmail.com\",\"role\":\"appraiser\",\"status\":\"active\"}','::1','2025-12-10 09:14:31');
/*!40000 ALTER TABLE `audit_logs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `customers`
--

DROP TABLE IF EXISTS `customers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `customers` (
  `customer_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `customer_code` varchar(20) NOT NULL,
  `first_name` varchar(100) NOT NULL,
  `middle_name` varchar(100) DEFAULT NULL,
  `last_name` varchar(100) NOT NULL,
  `date_of_birth` date DEFAULT NULL,
  `gender` enum('male','female','other') DEFAULT NULL,
  `contact_number` varchar(20) NOT NULL,
  `email` varchar(150) DEFAULT NULL,
  `address_line1` varchar(255) DEFAULT NULL,
  `address_line2` varchar(255) DEFAULT NULL,
  `city` varchar(100) DEFAULT NULL,
  `province` varchar(100) DEFAULT NULL,
  `postal_code` varchar(20) DEFAULT NULL,
  `id_type` varchar(50) NOT NULL,
  `id_number` varchar(100) NOT NULL,
  `id_image_path` varchar(255) DEFAULT NULL,
  `registration_date` date NOT NULL,
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`customer_id`),
  UNIQUE KEY `customer_code` (`customer_code`),
  UNIQUE KEY `id_number` (`id_number`),
  KEY `idx_customer_name` (`last_name`,`first_name`),
  KEY `idx_customer_contact` (`contact_number`)
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `customers`
--

LOCK TABLES `customers` WRITE;
/*!40000 ALTER TABLE `customers` DISABLE KEYS */;
INSERT INTO `customers` VALUES (8,'CUST-2025-00001','Rick','Santos','Dela Cruz','2000-02-14','male','09365353523','rick@gmail.com','Barangay San Miguel','','Puerto Princesa City','Palawan','5300','National ID','01-010-0101','assets/uploads/ids/ID_1765290040_9f91d8d5.jpg','2025-12-09','active','2025-12-09 22:20:40','2025-12-09 22:20:40'),(9,'CUST-2025-00002','Maria','David','Fernandez','1995-06-13','female','09999272722','maria@gmail.com','Barangay Santa Monica','','Puerto Princesa City','Palawan','5300','National ID','02-020-0202','assets/uploads/ids/ID_1765290118_07fca190.jpg','2025-12-09','active','2025-12-09 22:21:58','2025-12-09 22:21:58'),(10,'CUST-2025-00003','Vincent','Lopez','Navarro','2003-06-23','male','0937838372','vincent@gmail.com','Barangay San Manuel','','Puerto Princesa City','Palawan','5300','National ID','03-030-0303','assets/uploads/ids/ID_1765290194_c2c6823e.jpg','2025-12-09','active','2025-12-09 22:23:14','2025-12-09 22:23:14'),(11,'CUST-2025-00004','Christian Paul','Garcia','Mendoza','2000-01-22','female','09362628183','christian@gmail.com','Barangay San Miguel','','Puerto Princesa City','Palawan','5300','National ID','04-040-0404','assets/uploads/ids/ID_1765290257_dcef6258.jpg','2025-12-09','active','2025-12-09 22:24:17','2025-12-09 22:24:17'),(12,'CUST-2025-00005','Anne','Cruz','Aguilar','2001-05-11','female','09837173723','anne@gmail.com','Barangay Bancao Bancao','','Puerto Princesa City','Palawan','5300','National ID','05-050-0505','assets/uploads/ids/ID_1765290320_dc91e676.jpg','2025-12-09','active','2025-12-09 22:25:20','2025-12-09 22:25:20');
/*!40000 ALTER TABLE `customers` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `pawn_transactions`
--

DROP TABLE IF EXISTS `pawn_transactions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `pawn_transactions` (
  `transaction_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `pawn_ticket_number` varchar(30) NOT NULL,
  `customer_id` int(10) unsigned NOT NULL,
  `appraiser_id` int(10) unsigned NOT NULL,
  `transaction_date` date NOT NULL,
  `loan_amount` decimal(12,2) NOT NULL,
  `interest_rate` decimal(5,2) NOT NULL,
  `maturity_date` date NOT NULL,
  `grace_period_end` date NOT NULL,
  `status` enum('active','redeemed','renewed','expired','auctioned','sold','inventory') NOT NULL DEFAULT 'active',
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`transaction_id`),
  UNIQUE KEY `pawn_ticket_number` (`pawn_ticket_number`),
  KEY `fk_pawn_appraiser` (`appraiser_id`),
  KEY `idx_pawn_customer` (`customer_id`),
  KEY `idx_pawn_status` (`status`),
  KEY `idx_pawn_maturity` (`maturity_date`),
  CONSTRAINT `fk_pawn_appraiser` FOREIGN KEY (`appraiser_id`) REFERENCES `users` (`user_id`),
  CONSTRAINT `fk_pawn_customer` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`customer_id`)
) ENGINE=InnoDB AUTO_INCREMENT=26 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pawn_transactions`
--

LOCK TABLES `pawn_transactions` WRITE;
/*!40000 ALTER TABLE `pawn_transactions` DISABLE KEYS */;
INSERT INTO `pawn_transactions` VALUES (20,'PT-20251209-00001',8,1,'2025-12-09',14400.00,3.00,'2026-01-08','2026-01-23','redeemed','2025-12-09 22:33:54','2025-12-09 22:55:31'),(21,'PT-20251209-00002',8,1,'2025-12-09',8000.00,3.00,'2026-02-07','2026-02-22','renewed','2025-12-09 22:34:47','2025-12-09 22:56:36'),(22,'PT-20251209-00003',8,1,'2025-12-09',16000.00,3.00,'2026-01-08','2026-01-23','auctioned','2025-12-09 22:35:11','2025-12-09 22:57:44'),(23,'PT-20251209-00004',9,1,'2025-12-09',24000.00,3.00,'2026-01-08','2026-01-23','active','2025-12-09 22:36:47','2025-12-09 22:36:47'),(24,'PT-20251209-00005',9,1,'2025-12-09',16000.00,3.00,'2026-01-08','2026-01-23','active','2025-12-09 22:53:21','2025-12-09 22:53:21'),(25,'PT-20251209-00006',9,1,'2025-12-09',24000.00,3.00,'2026-01-08','2026-01-23','active','2025-12-09 22:54:09','2025-12-09 22:54:09');
/*!40000 ALTER TABLE `pawn_transactions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `pawned_items`
--

DROP TABLE IF EXISTS `pawned_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `pawned_items` (
  `item_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `transaction_id` int(10) unsigned NOT NULL,
  `item_category` varchar(50) NOT NULL,
  `item_subcategory` varchar(50) DEFAULT NULL,
  `item_description` text DEFAULT NULL,
  `brand` varchar(100) DEFAULT NULL,
  `model` varchar(100) DEFAULT NULL,
  `appraised_value` decimal(12,2) NOT NULL,
  `item_image_path` varchar(255) DEFAULT NULL,
  `serial_number` varchar(100) DEFAULT NULL,
  `condition_notes` text DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`item_id`),
  KEY `idx_item_transaction` (`transaction_id`),
  CONSTRAINT `fk_items_transaction` FOREIGN KEY (`transaction_id`) REFERENCES `pawn_transactions` (`transaction_id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pawned_items`
--

LOCK TABLES `pawned_items` WRITE;
/*!40000 ALTER TABLE `pawned_items` DISABLE KEYS */;
INSERT INTO `pawned_items` VALUES (1,20,'jewelry',NULL,'gold braclet',NULL,NULL,18000.00,'assets/uploads/items/ITEM_1765290834_91e079db.jpg',NULL,NULL,'2025-12-09 22:33:54'),(2,21,'electronics',NULL,'Android smartphone',NULL,NULL,10000.00,NULL,NULL,NULL,'2025-12-09 22:34:47'),(3,22,'electronics',NULL,'Laptop',NULL,NULL,20000.00,NULL,NULL,NULL,'2025-12-09 22:35:11'),(4,23,'jewelry',NULL,'Gold necklace',NULL,NULL,30000.00,NULL,NULL,NULL,'2025-12-09 22:36:47'),(5,24,'jewelry',NULL,'Silver bracelet',NULL,NULL,20000.00,'assets/uploads/items/ITEM_1765292001_9613292d.jpg',NULL,NULL,'2025-12-09 22:53:21'),(6,25,'electronics',NULL,'Laptop - ROG Asus',NULL,NULL,30000.00,NULL,NULL,NULL,'2025-12-09 22:54:09');
/*!40000 ALTER TABLE `pawned_items` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `payments`
--

DROP TABLE IF EXISTS `payments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `payments` (
  `payment_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `transaction_id` int(10) unsigned NOT NULL,
  `payment_type` enum('redemption','renewal','partial') NOT NULL,
  `payment_date` datetime NOT NULL,
  `principal_amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `interest_amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `penalty_amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `total_amount` decimal(12,2) NOT NULL,
  `payment_method` enum('cash','bank_transfer','other') NOT NULL,
  `processed_by` int(10) unsigned NOT NULL,
  `receipt_number` varchar(50) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`payment_id`),
  UNIQUE KEY `receipt_number` (`receipt_number`),
  KEY `fk_payments_user` (`processed_by`),
  KEY `idx_payments_transaction` (`transaction_id`),
  CONSTRAINT `fk_payments_transaction` FOREIGN KEY (`transaction_id`) REFERENCES `pawn_transactions` (`transaction_id`),
  CONSTRAINT `fk_payments_user` FOREIGN KEY (`processed_by`) REFERENCES `users` (`user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `payments`
--

LOCK TABLES `payments` WRITE;
/*!40000 ALTER TABLE `payments` DISABLE KEYS */;
INSERT INTO `payments` VALUES (1,20,'redemption','2025-12-09 22:55:31',14400.00,432.00,0.00,14832.00,'cash',1,'R-20251209155531-7957','2025-12-09 22:55:31'),(2,21,'renewal','2025-12-09 22:56:36',0.00,240.00,0.00,240.00,'cash',1,'RN-20251209155636-4479','2025-12-09 22:56:36');
/*!40000 ALTER TABLE `payments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `renewals`
--

DROP TABLE IF EXISTS `renewals`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `renewals` (
  `renewal_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `transaction_id` int(10) unsigned NOT NULL,
  `renewal_date` date NOT NULL,
  `old_maturity_date` date NOT NULL,
  `new_maturity_date` date NOT NULL,
  `interest_paid` decimal(12,2) NOT NULL,
  `processed_by` int(10) unsigned NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`renewal_id`),
  KEY `fk_renewals_user` (`processed_by`),
  KEY `idx_renewals_transaction` (`transaction_id`),
  CONSTRAINT `fk_renewals_transaction` FOREIGN KEY (`transaction_id`) REFERENCES `pawn_transactions` (`transaction_id`),
  CONSTRAINT `fk_renewals_user` FOREIGN KEY (`processed_by`) REFERENCES `users` (`user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `renewals`
--

LOCK TABLES `renewals` WRITE;
/*!40000 ALTER TABLE `renewals` DISABLE KEYS */;
INSERT INTO `renewals` VALUES (1,21,'2025-12-09','2026-01-08','2026-02-07',240.00,1,'2025-12-09 22:56:36');
/*!40000 ALTER TABLE `renewals` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `system_settings`
--

DROP TABLE IF EXISTS `system_settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `system_settings` (
  `setting_key` varchar(100) NOT NULL,
  `setting_value` varchar(255) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `system_settings`
--

LOCK TABLES `system_settings` WRITE;
/*!40000 ALTER TABLE `system_settings` DISABLE KEYS */;
INSERT INTO `system_settings` VALUES ('default_interest_rate','3.00','Default monthly interest rate (%)'),('default_loan_percentage','0.80','Loan % of appraised value'),('default_loan_term_days','30','Loan term in days'),('grace_period_days','15','Grace period after maturity'),('penalty_rate','5.00','Penalty % on total due beyond grace period');
/*!40000 ALTER TABLE `system_settings` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `users` (
  `user_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `full_name` varchar(150) NOT NULL,
  `email` varchar(150) DEFAULT NULL,
  `role` enum('admin','cashier','appraiser') NOT NULL,
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `last_login` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`user_id`),
  UNIQUE KEY `username` (`username`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (1,'admin','$2y$10$5LhEIj4XsRATVXeMEYlZ1.r0tf57zTmWyDnXqs7Kj7VbElX3mwlW2','System Admin','admin@example.com','admin','active','2025-12-10 20:46:24','2025-11-30 18:43:38'),(5,'cashier','$2y$10$NI1jZH4F7HtpVeSLcLZFw.YjD1O1QFAxcFd7YVjMB3FEJRTr1n6m6','Joice Dela Torre','joice@gmail.com','cashier','active','2025-12-10 17:15:05','2025-12-09 23:00:41'),(6,'appraiser','$2y$10$k4W4VgDFPP1zPFRk45CTw.hFYr6dYaJfWakXgN0.2ltH543wAmNeK','Drei Herrera','drei@gmail.com','appraiser','active','2025-12-10 17:08:18','2025-12-10 09:14:11');
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2025-12-10 21:07:06
