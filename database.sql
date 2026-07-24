-- MariaDB dump 10.19  Distrib 10.4.32-MariaDB, for Win64 (AMD64)
--
-- Host: localhost    Database: teklu_getachew_erp
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
-- Current Database: `teklu_getachew_erp`
--

CREATE DATABASE /*!32312 IF NOT EXISTS*/ `teklu_getachew_erp` /*!40100 DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci */;

USE `teklu_getachew_erp`;

--
-- Table structure for table `animals`
--

DROP TABLE IF EXISTS `animals`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `animals` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `animal_id` varchar(20) NOT NULL COMMENT 'Unique tag ID',
  `qr_code` text DEFAULT NULL COMMENT 'QR code data',
  `photo_path` varchar(255) DEFAULT NULL,
  `name` varchar(100) DEFAULT NULL,
  `breed` varchar(100) DEFAULT NULL,
  `breed_am` varchar(100) DEFAULT NULL,
  `gender` enum('male','female') NOT NULL,
  `birth_date` date DEFAULT NULL,
  `ethiopian_birth_date` varchar(20) DEFAULT NULL,
  `age_months` int(11) DEFAULT 0 COMMENT 'Age in months - calculated by application',
  `status` enum('active','sick','pregnant','sold','dead') DEFAULT 'active',
  `weight` decimal(8,2) DEFAULT NULL COMMENT 'Weight in KG',
  `color` varchar(50) DEFAULT NULL,
  `purchase_date` date DEFAULT NULL,
  `purchase_price` decimal(10,2) DEFAULT NULL,
  `purchase_from` varchar(200) DEFAULT NULL,
  `current_value` decimal(10,2) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `animal_id` (`animal_id`),
  KEY `created_by` (`created_by`),
  CONSTRAINT `animals_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `animals`
--

LOCK TABLES `animals` WRITE;
/*!40000 ALTER TABLE `animals` DISABLE KEYS */;
/*!40000 ALTER TABLE `animals` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `asset_maintenance`
--

DROP TABLE IF EXISTS `asset_maintenance`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `asset_maintenance` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `asset_id` int(11) NOT NULL,
  `maintenance_type` enum('repair','service','inspection','replacement') NOT NULL,
  `description` text DEFAULT NULL,
  `cost` decimal(10,2) DEFAULT 0.00,
  `performed_by` varchar(200) DEFAULT NULL,
  `ethiopian_date` varchar(20) DEFAULT NULL,
  `maintenance_date` date NOT NULL,
  `next_maintenance_date` date DEFAULT NULL,
  `status` enum('scheduled','in_progress','completed','cancelled') DEFAULT 'scheduled',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `asset_id` (`asset_id`),
  CONSTRAINT `asset_maintenance_ibfk_1` FOREIGN KEY (`asset_id`) REFERENCES `assets` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `asset_maintenance`
--

LOCK TABLES `asset_maintenance` WRITE;
/*!40000 ALTER TABLE `asset_maintenance` DISABLE KEYS */;
/*!40000 ALTER TABLE `asset_maintenance` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `assets`
--

DROP TABLE IF EXISTS `assets`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `assets` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `asset_code` varchar(20) NOT NULL,
  `asset_name` varchar(200) NOT NULL,
  `asset_name_am` varchar(200) DEFAULT NULL,
  `category` enum('equipment','machinery','vehicle','building','land','furniture','electronics','other') NOT NULL,
  `purchase_date` date DEFAULT NULL,
  `purchase_price` decimal(15,2) DEFAULT NULL,
  `current_value` decimal(15,2) DEFAULT NULL,
  `depreciation_rate` decimal(5,2) DEFAULT 0.00,
  `condition_status` enum('excellent','good','fair','poor','damaged','disposed') DEFAULT 'good',
  `location` varchar(200) DEFAULT NULL,
  `assigned_to` int(11) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `photo_path` varchar(255) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `asset_code` (`asset_code`),
  KEY `assigned_to` (`assigned_to`),
  KEY `created_by` (`created_by`),
  CONSTRAINT `assets_ibfk_1` FOREIGN KEY (`assigned_to`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `assets_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `assets`
--

LOCK TABLES `assets` WRITE;
/*!40000 ALTER TABLE `assets` DISABLE KEYS */;
/*!40000 ALTER TABLE `assets` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `audit_logs`
--

DROP TABLE IF EXISTS `audit_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `audit_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `action_type` enum('create','update','delete','login','logout','export','approve','reject') NOT NULL,
  `table_name` varchar(100) NOT NULL,
  `record_id` int(11) DEFAULT NULL,
  `old_values` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`old_values`)),
  `new_values` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`new_values`)),
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `ethiopian_date` varchar(20) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `audit_logs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `audit_logs`
--

LOCK TABLES `audit_logs` WRITE;
/*!40000 ALTER TABLE `audit_logs` DISABLE KEYS */;
INSERT INTO `audit_logs` VALUES (1,2,'update','users',2,NULL,'{\"password_changed\":true}','::1',NULL,'2019-05-14','2026-05-14 19:27:53'),(2,9,'update','users',9,NULL,'{\"password_changed\":true}','::1',NULL,'2019-05-16','2026-05-16 08:53:41'),(3,5,'update','users',5,NULL,'{\"password_changed\":true}','::1',NULL,'2019-05-16','2026-05-16 09:25:21'),(4,6,'update','users',6,NULL,'{\"password_changed\":true}','::1',NULL,'2019-05-16','2026-05-16 09:27:52'),(5,7,'update','users',7,NULL,'{\"password_changed\":true}','::1',NULL,'2019-05-16','2026-05-16 09:29:23'),(6,8,'update','users',8,NULL,'{\"password_changed\":true}','::1',NULL,'2019-05-16','2026-05-16 09:48:06');
/*!40000 ALTER TABLE `audit_logs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `backup_history`
--

DROP TABLE IF EXISTS `backup_history`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `backup_history` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `backup_file` varchar(255) NOT NULL,
  `backup_size` bigint(20) DEFAULT NULL,
  `backup_type` enum('full','partial','automatic') NOT NULL,
  `status` enum('success','failed') NOT NULL,
  `created_by` int(11) DEFAULT NULL,
  `ethiopian_date` varchar(20) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `created_by` (`created_by`),
  CONSTRAINT `backup_history_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `backup_history`
--

LOCK TABLES `backup_history` WRITE;
/*!40000 ALTER TABLE `backup_history` DISABLE KEYS */;
/*!40000 ALTER TABLE `backup_history` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `bank_accounts`
--

DROP TABLE IF EXISTS `bank_accounts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `bank_accounts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `bank_name` varchar(100) NOT NULL,
  `bank_name_am` varchar(100) DEFAULT NULL,
  `account_number` varchar(50) NOT NULL,
  `account_type` enum('savings','checking','fixed') NOT NULL,
  `branch` varchar(100) DEFAULT NULL,
  `balance` decimal(15,2) DEFAULT 0.00,
  `initial_balance` decimal(15,2) DEFAULT 0.00,
  `currency` varchar(3) DEFAULT 'ETB',
  `interest_rate` decimal(5,2) DEFAULT 0.00,
  `is_active` tinyint(1) DEFAULT 1,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `account_number` (`account_number`),
  KEY `created_by` (`created_by`),
  CONSTRAINT `bank_accounts_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `bank_accounts`
--

LOCK TABLES `bank_accounts` WRITE;
/*!40000 ALTER TABLE `bank_accounts` DISABLE KEYS */;
INSERT INTO `bank_accounts` VALUES (1,'Commercial Bank of Ethiopia','ßï¿ßèóßë╡ßï«ßî╡ßï½ ßèòßîìßï╡ ßëúßèòßè¡','1000123456789','checking','Bole Branch',2500000.00,2500000.00,'ETB',0.00,1,NULL,'2026-05-14 18:07:55','2026-05-14 18:07:55'),(2,'Dashen Bank','ßï│ßê╜ßèò ßëúßèòßè¡','2001234567890','savings','Megenagna Branch',1500000.00,1500000.00,'ETB',0.00,1,NULL,'2026-05-14 18:07:55','2026-05-14 18:07:55'),(3,'Awash Bank','ßèáßïïßê╜ ßëúßèòßè¡','3002345678901','checking','Piassa Branch',1000000.00,1000000.00,'ETB',0.00,1,NULL,'2026-05-14 18:07:55','2026-05-14 18:07:55'),(4,'Abyssinia Bank','ßèáßëóßê▓ßèÆßï½ ßëúßèòßè¡','4003456789012','savings','Mexico Branch',750000.00,750000.00,'ETB',0.00,1,NULL,'2026-05-14 18:07:55','2026-05-14 18:07:55'),(5,'Wegagen Bank','ßïêßîïßîêßèò ßëúßèòßè¡','5004567890123','fixed','Arat Kilo Branch',500000.00,500000.00,'ETB',0.00,1,NULL,'2026-05-14 18:07:55','2026-05-14 18:07:55'),(6,'Nib International Bank','ßèòßëÑ ßèóßèòßë░ßê¡ßèôßê╜ßèôßêì ßëúßèòßè¡','6005678901234','checking','Merkato Branch',300000.00,300000.00,'ETB',0.00,1,NULL,'2026-05-14 18:07:55','2026-05-14 18:07:55'),(7,'Cooperative Bank of Oromia','ßï¿ßèªßê«ßêÜßï½ ßêàßëÑßê¿ßë╡ ßêÑßê½ ßëúßèòßè¡','7006789012345','savings','Adama Branch',450000.00,450000.00,'ETB',0.00,1,NULL,'2026-05-14 18:07:55','2026-05-14 18:07:55'),(8,'Lion International Bank','ßêèßï«ßèò ßèóßèòßë░ßê¡ßèôßê╜ßèôßêì ßëúßèòßè¡','8007890123456','checking','Gofa Branch',200000.00,200000.00,'ETB',0.00,1,NULL,'2026-05-14 18:07:55','2026-05-14 18:07:55'),(9,'Zemen Bank','ßïÿßêÿßèò ßëúßèòßè¡','9008901234567','savings','Bole Medhaniyalem',850000.00,850000.00,'ETB',0.00,1,NULL,'2026-05-14 18:07:55','2026-05-14 18:07:55'),(10,'Berhan Bank','ßëÑßê¡ßêâßèò ßëúßèòßè¡','0109012345678','checking','Saris Branch',600000.00,600000.00,'ETB',0.00,1,NULL,'2026-05-14 18:07:55','2026-05-14 18:07:55');
/*!40000 ALTER TABLE `bank_accounts` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cash_register`
--

DROP TABLE IF EXISTS `cash_register`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cash_register` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `current_balance` decimal(15,2) DEFAULT 0.00,
  `last_transaction_id` int(11) DEFAULT NULL,
  `last_updated` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `updated_by` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `updated_by` (`updated_by`),
  CONSTRAINT `cash_register_ibfk_1` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cash_register`
--

LOCK TABLES `cash_register` WRITE;
/*!40000 ALTER TABLE `cash_register` DISABLE KEYS */;
INSERT INTO `cash_register` VALUES (1,150000.00,NULL,'2026-05-14 18:07:55',NULL);
/*!40000 ALTER TABLE `cash_register` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `egg_production`
--

DROP TABLE IF EXISTS `egg_production`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `egg_production` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `batch_id` int(11) NOT NULL,
  `total_eggs` int(11) NOT NULL,
  `broken_eggs` int(11) DEFAULT 0,
  `good_eggs` int(11) GENERATED ALWAYS AS (`total_eggs` - `broken_eggs`) STORED,
  `egg_grade` enum('A','B','C') DEFAULT 'A',
  `sold_eggs` int(11) DEFAULT 0,
  `price_per_egg` decimal(10,2) DEFAULT NULL,
  `total_sales` decimal(15,2) GENERATED ALWAYS AS (`sold_eggs` * `price_per_egg`) STORED,
  `ethiopian_date` varchar(20) DEFAULT NULL,
  `collection_date` date NOT NULL,
  `collected_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_daily_egg` (`batch_id`,`collection_date`),
  KEY `collected_by` (`collected_by`),
  CONSTRAINT `egg_production_ibfk_1` FOREIGN KEY (`batch_id`) REFERENCES `poultry_batches` (`id`) ON DELETE CASCADE,
  CONSTRAINT `egg_production_ibfk_2` FOREIGN KEY (`collected_by`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `egg_production`
--

LOCK TABLES `egg_production` WRITE;
/*!40000 ALTER TABLE `egg_production` DISABLE KEYS */;
/*!40000 ALTER TABLE `egg_production` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `feed_inventory`
--

DROP TABLE IF EXISTS `feed_inventory`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `feed_inventory` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `feed_name` varchar(100) NOT NULL,
  `feed_name_am` varchar(100) DEFAULT NULL,
  `feed_type` enum('hay','silage','concentrate','mineral','supplement','other') NOT NULL,
  `quantity_kg` decimal(10,2) NOT NULL DEFAULT 0.00,
  `unit_price` decimal(10,2) DEFAULT NULL,
  `total_value` decimal(15,2) GENERATED ALWAYS AS (`quantity_kg` * `unit_price`) STORED,
  `minimum_threshold` decimal(10,2) DEFAULT 100.00,
  `storage_location` varchar(100) DEFAULT NULL,
  `supplier` varchar(200) DEFAULT NULL,
  `purchase_date` date DEFAULT NULL,
  `expiry_date` date DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `feed_inventory`
--

LOCK TABLES `feed_inventory` WRITE;
/*!40000 ALTER TABLE `feed_inventory` DISABLE KEYS */;
INSERT INTO `feed_inventory` VALUES (1,'Alfalfa Hay','ßèáßêìßìïßêìßìï ßï╡ßê¡ßëåßê╜','hay',5000.00,15.00,75000.00,500.00,'Warehouse A','Green Feed Supplier',NULL,NULL,1,'2026-05-14 18:07:55','2026-05-14 18:07:55'),(2,'Maize Silage','ßï¿ßëáßëåßêÄ ßê▓ßêîßîà','silage',8000.00,12.00,96000.00,1000.00,'Silo 1','Farm Direct',NULL,NULL,1,'2026-05-14 18:07:55','2026-05-14 18:07:55'),(3,'Dairy Concentrate','ßï¿ßïêßë░ßë╡ ßê¢ßîÄßêìßëáßë╗','concentrate',3000.00,45.00,135000.00,300.00,'Warehouse B','Agri Inputs Ltd',NULL,NULL,1,'2026-05-14 18:07:55','2026-05-14 18:07:55'),(4,'Mineral Mix','ßê¢ßïòßï╡ßèò ßëàßêìßëàßêì','mineral',500.00,80.00,40000.00,50.00,'Store Room','Vet Suppliers',NULL,NULL,1,'2026-05-14 18:07:55','2026-05-14 18:07:55'),(5,'Wheat Bran','ßï¿ßê╡ßèòßï┤ ßììßê¡ßìïßê¬','supplement',2000.00,20.00,40000.00,200.00,'Warehouse A','Mill Factory',NULL,NULL,1,'2026-05-14 18:07:55','2026-05-14 18:07:55'),(6,'Oil Seed Cake','ßï¿ßëàßëúßë╡ ßèÑßêàßêì ßîêßèòßìÄ','supplement',1500.00,35.00,52500.00,150.00,'Warehouse B','Oil Factory',NULL,NULL,1,'2026-05-14 18:07:55','2026-05-14 18:07:55'),(7,'Chicken Layer Feed','ßï¿ßï╢ßê« ßêÿßèû','concentrate',2000.00,50.00,100000.00,200.00,'Poultry House','Feed Company',NULL,NULL,1,'2026-05-14 18:07:55','2026-05-14 18:07:55'),(8,'Fish Feed Pellets','ßï¿ßèáßê│ ßêÿßèû','concentrate',1000.00,55.00,55000.00,100.00,'Fish Area','Aqua Feeds',NULL,NULL,1,'2026-05-14 18:07:55','2026-05-14 18:07:55'),(9,'Oat Hay','ßèªßë╡ ßï╡ßê¡ßëåßê╜','hay',3000.00,18.00,54000.00,300.00,'Warehouse C','Farm Direct',NULL,NULL,1,'2026-05-14 18:07:55','2026-05-14 18:07:55'),(10,'Salt Lick Block','ßï¿ßî¿ßïì ßêèßè¡','mineral',200.00,120.00,24000.00,20.00,'Store Room','Mineral Co.',NULL,NULL,1,'2026-05-14 18:07:55','2026-05-14 18:07:55');
/*!40000 ALTER TABLE `feed_inventory` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `feed_records`
--

DROP TABLE IF EXISTS `feed_records`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `feed_records` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `feed_inventory_id` int(11) NOT NULL,
  `animal_id` int(11) DEFAULT NULL,
  `quantity_kg` decimal(10,2) NOT NULL,
  `distribution_type` enum('individual','group','all') NOT NULL,
  `notes` text DEFAULT NULL,
  `ethiopian_date` varchar(20) DEFAULT NULL,
  `distribution_date` date NOT NULL,
  `distributed_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `feed_inventory_id` (`feed_inventory_id`),
  KEY `animal_id` (`animal_id`),
  KEY `distributed_by` (`distributed_by`),
  CONSTRAINT `feed_records_ibfk_1` FOREIGN KEY (`feed_inventory_id`) REFERENCES `feed_inventory` (`id`),
  CONSTRAINT `feed_records_ibfk_2` FOREIGN KEY (`animal_id`) REFERENCES `animals` (`id`) ON DELETE SET NULL,
  CONSTRAINT `feed_records_ibfk_3` FOREIGN KEY (`distributed_by`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `feed_records`
--

LOCK TABLES `feed_records` WRITE;
/*!40000 ALTER TABLE `feed_records` DISABLE KEYS */;
/*!40000 ALTER TABLE `feed_records` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `fertilizer_records`
--

DROP TABLE IF EXISTS `fertilizer_records`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `fertilizer_records` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `field_id` int(11) NOT NULL,
  `fertilizer_name` varchar(200) NOT NULL,
  `fertilizer_type` enum('organic','chemical','pesticide','herbicide','other') NOT NULL,
  `quantity_kg` decimal(10,2) NOT NULL,
  `cost_per_kg` decimal(10,2) DEFAULT NULL,
  `total_cost` decimal(15,2) GENERATED ALWAYS AS (`quantity_kg` * `cost_per_kg`) STORED,
  `application_method` varchar(100) DEFAULT NULL,
  `ethiopian_date` varchar(20) DEFAULT NULL,
  `application_date` date NOT NULL,
  `applied_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `field_id` (`field_id`),
  KEY `applied_by` (`applied_by`),
  CONSTRAINT `fertilizer_records_ibfk_1` FOREIGN KEY (`field_id`) REFERENCES `irrigation_fields` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fertilizer_records_ibfk_2` FOREIGN KEY (`applied_by`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `fertilizer_records`
--

LOCK TABLES `fertilizer_records` WRITE;
/*!40000 ALTER TABLE `fertilizer_records` DISABLE KEYS */;
/*!40000 ALTER TABLE `fertilizer_records` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `fish_feed_records`
--

DROP TABLE IF EXISTS `fish_feed_records`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `fish_feed_records` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `pond_id` int(11) NOT NULL,
  `feed_type` varchar(100) NOT NULL,
  `quantity_kg` decimal(10,2) NOT NULL,
  `feeding_time` enum('morning','afternoon','evening') NOT NULL,
  `cost_per_kg` decimal(10,2) DEFAULT NULL,
  `total_cost` decimal(15,2) GENERATED ALWAYS AS (`quantity_kg` * `cost_per_kg`) STORED,
  `ethiopian_date` varchar(20) DEFAULT NULL,
  `feed_date` date NOT NULL,
  `fed_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `pond_id` (`pond_id`),
  KEY `fed_by` (`fed_by`),
  CONSTRAINT `fish_feed_records_ibfk_1` FOREIGN KEY (`pond_id`) REFERENCES `fish_ponds` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fish_feed_records_ibfk_2` FOREIGN KEY (`fed_by`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `fish_feed_records`
--

LOCK TABLES `fish_feed_records` WRITE;
/*!40000 ALTER TABLE `fish_feed_records` DISABLE KEYS */;
/*!40000 ALTER TABLE `fish_feed_records` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `fish_harvest`
--

DROP TABLE IF EXISTS `fish_harvest`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `fish_harvest` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `pond_id` int(11) NOT NULL,
  `harvest_code` varchar(20) NOT NULL,
  `quantity_kg` decimal(10,2) NOT NULL,
  `fish_count` int(11) DEFAULT NULL,
  `average_weight_g` decimal(10,2) DEFAULT NULL,
  `quality_grade` enum('A','B','C') DEFAULT 'B',
  `sold_quantity_kg` decimal(10,2) DEFAULT 0.00,
  `price_per_kg` decimal(10,2) DEFAULT NULL,
  `total_sales` decimal(15,2) GENERATED ALWAYS AS (`sold_quantity_kg` * `price_per_kg`) STORED,
  `buyer_name` varchar(200) DEFAULT NULL,
  `harvest_date` date NOT NULL,
  `ethiopian_date` varchar(20) DEFAULT NULL,
  `harvested_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `harvest_code` (`harvest_code`),
  KEY `pond_id` (`pond_id`),
  KEY `harvested_by` (`harvested_by`),
  CONSTRAINT `fish_harvest_ibfk_1` FOREIGN KEY (`pond_id`) REFERENCES `fish_ponds` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fish_harvest_ibfk_2` FOREIGN KEY (`harvested_by`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `fish_harvest`
--

LOCK TABLES `fish_harvest` WRITE;
/*!40000 ALTER TABLE `fish_harvest` DISABLE KEYS */;
/*!40000 ALTER TABLE `fish_harvest` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `fish_mortality`
--

DROP TABLE IF EXISTS `fish_mortality`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `fish_mortality` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `pond_id` int(11) NOT NULL,
  `mortality_count` int(11) NOT NULL,
  `estimated_weight_kg` decimal(10,2) DEFAULT NULL,
  `cause` varchar(200) DEFAULT NULL,
  `ethiopian_date` varchar(20) DEFAULT NULL,
  `mortality_date` date NOT NULL,
  `reported_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `pond_id` (`pond_id`),
  KEY `reported_by` (`reported_by`),
  CONSTRAINT `fish_mortality_ibfk_1` FOREIGN KEY (`pond_id`) REFERENCES `fish_ponds` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fish_mortality_ibfk_2` FOREIGN KEY (`reported_by`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `fish_mortality`
--

LOCK TABLES `fish_mortality` WRITE;
/*!40000 ALTER TABLE `fish_mortality` DISABLE KEYS */;
/*!40000 ALTER TABLE `fish_mortality` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `fish_ponds`
--

DROP TABLE IF EXISTS `fish_ponds`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `fish_ponds` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `pond_code` varchar(20) NOT NULL,
  `pond_name` varchar(100) DEFAULT NULL,
  `pond_name_am` varchar(100) DEFAULT NULL,
  `size_sqm` decimal(10,2) DEFAULT NULL,
  `water_source` varchar(200) DEFAULT NULL,
  `fish_species` varchar(100) DEFAULT NULL,
  `stocking_date` date DEFAULT NULL,
  `initial_count` int(11) DEFAULT NULL,
  `current_count` int(11) DEFAULT NULL,
  `average_weight_g` decimal(10,2) DEFAULT NULL,
  `water_quality_status` enum('excellent','good','fair','poor') DEFAULT 'good',
  `status` enum('active','harvested','maintenance','inactive') DEFAULT 'active',
  `notes` text DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `pond_code` (`pond_code`),
  KEY `created_by` (`created_by`),
  CONSTRAINT `fish_ponds_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `fish_ponds`
--

LOCK TABLES `fish_ponds` WRITE;
/*!40000 ALTER TABLE `fish_ponds` DISABLE KEYS */;
/*!40000 ALTER TABLE `fish_ponds` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `harvest_records`
--

DROP TABLE IF EXISTS `harvest_records`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `harvest_records` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `field_id` int(11) NOT NULL,
  `harvest_code` varchar(20) NOT NULL,
  `crop_type` varchar(100) DEFAULT NULL,
  `quantity_kg` decimal(15,2) NOT NULL,
  `quality_grade` enum('A','B','C') DEFAULT 'B',
  `labor_count` int(11) DEFAULT NULL,
  `labor_cost` decimal(10,2) DEFAULT NULL,
  `equipment_cost` decimal(10,2) DEFAULT NULL,
  `total_harvest_cost` decimal(15,2) DEFAULT NULL,
  `sold_quantity_kg` decimal(15,2) DEFAULT 0.00,
  `price_per_kg` decimal(10,2) DEFAULT NULL,
  `total_sales` decimal(15,2) GENERATED ALWAYS AS (`sold_quantity_kg` * `price_per_kg`) STORED,
  `buyer_name` varchar(200) DEFAULT NULL,
  `storage_location` varchar(100) DEFAULT NULL,
  `ethiopian_date` varchar(20) DEFAULT NULL,
  `harvest_date` date NOT NULL,
  `harvested_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `harvest_code` (`harvest_code`),
  KEY `field_id` (`field_id`),
  KEY `harvested_by` (`harvested_by`),
  CONSTRAINT `harvest_records_ibfk_1` FOREIGN KEY (`field_id`) REFERENCES `irrigation_fields` (`id`) ON DELETE CASCADE,
  CONSTRAINT `harvest_records_ibfk_2` FOREIGN KEY (`harvested_by`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `harvest_records`
--

LOCK TABLES `harvest_records` WRITE;
/*!40000 ALTER TABLE `harvest_records` DISABLE KEYS */;
/*!40000 ALTER TABLE `harvest_records` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `irrigation_fields`
--

DROP TABLE IF EXISTS `irrigation_fields`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `irrigation_fields` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `field_code` varchar(20) NOT NULL,
  `field_name` varchar(100) DEFAULT NULL,
  `field_name_am` varchar(100) DEFAULT NULL,
  `size_hectares` decimal(10,2) DEFAULT NULL,
  `crop_type` varchar(100) DEFAULT NULL,
  `crop_type_am` varchar(100) DEFAULT NULL,
  `planting_date` date DEFAULT NULL,
  `expected_harvest_date` date DEFAULT NULL,
  `water_source` varchar(200) DEFAULT NULL,
  `irrigation_method` enum('drip','sprinkler','flood','furrow','pivot','other') NOT NULL,
  `status` enum('preparation','planted','growing','harvesting','harvested','fallow') DEFAULT 'preparation',
  `notes` text DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `field_code` (`field_code`),
  KEY `created_by` (`created_by`),
  CONSTRAINT `irrigation_fields_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `irrigation_fields`
--

LOCK TABLES `irrigation_fields` WRITE;
/*!40000 ALTER TABLE `irrigation_fields` DISABLE KEYS */;
/*!40000 ALTER TABLE `irrigation_fields` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `login_history`
--

DROP TABLE IF EXISTS `login_history`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `login_history` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `login_time` timestamp NOT NULL DEFAULT current_timestamp(),
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `ethiopian_date` varchar(20) DEFAULT NULL,
  `status` enum('success','failed') NOT NULL,
  `failure_reason` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `login_history_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=17 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `login_history`
--

LOCK TABLES `login_history` WRITE;
/*!40000 ALTER TABLE `login_history` DISABLE KEYS */;
INSERT INTO `login_history` VALUES (1,2,'2026-05-14 19:27:34','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36','2019-05-14','success',NULL),(2,9,'2026-05-16 08:53:28','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36','2019-05-16','success',NULL),(3,9,'2026-05-16 09:11:55','::1',NULL,'2019-05-16','',NULL),(4,2,'2026-05-16 09:13:08','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36','2019-05-16','success',NULL),(5,2,'2026-05-16 09:24:26','::1',NULL,'2019-05-16','',NULL),(6,5,'2026-05-16 09:25:09','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36','2019-05-16','success',NULL),(7,5,'2026-05-16 09:27:17','::1',NULL,'2019-05-16','',NULL),(8,6,'2026-05-16 09:27:44','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36','2019-05-16','success',NULL),(9,6,'2026-05-16 09:28:51','::1',NULL,'2019-05-16','',NULL),(10,7,'2026-05-16 09:29:14','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36','2019-05-16','success',NULL),(11,7,'2026-05-16 09:46:52','::1',NULL,'2019-05-16','',NULL),(12,7,'2026-05-16 09:47:26','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36','2019-05-16','success',NULL),(13,7,'2026-05-16 09:47:33','::1',NULL,'2019-05-16','',NULL),(14,8,'2026-05-16 09:47:53','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36','2019-05-16','success',NULL),(15,8,'2026-05-16 09:48:17','::1',NULL,'2019-05-16','',NULL),(16,2,'2026-05-16 10:13:40','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36','2019-05-16','success',NULL);
/*!40000 ALTER TABLE `login_history` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `medicine_inventory`
--

DROP TABLE IF EXISTS `medicine_inventory`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `medicine_inventory` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `medicine_name` varchar(200) NOT NULL,
  `medicine_name_am` varchar(200) DEFAULT NULL,
  `medicine_type` varchar(100) DEFAULT NULL,
  `manufacturer` varchar(200) DEFAULT NULL,
  `batch_number` varchar(100) DEFAULT NULL,
  `quantity` int(11) NOT NULL DEFAULT 0,
  `unit` varchar(50) DEFAULT NULL,
  `unit_price` decimal(10,2) DEFAULT NULL,
  `total_value` decimal(15,2) GENERATED ALWAYS AS (`quantity` * `unit_price`) STORED,
  `minimum_threshold` int(11) DEFAULT 10,
  `expiry_date` date DEFAULT NULL,
  `storage_conditions` varchar(200) DEFAULT NULL,
  `supplier` varchar(200) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `medicine_inventory`
--

LOCK TABLES `medicine_inventory` WRITE;
/*!40000 ALTER TABLE `medicine_inventory` DISABLE KEYS */;
INSERT INTO `medicine_inventory` VALUES (1,'Penicillin','ßìößèÆßê▓ßêèßèò','Antibiotic','Ethiopian Pharma','PEN-2024-001',500,'vial',45.00,22500.00,50,'2025-12-31',NULL,'Pharma Supply',1,'2026-05-14 18:07:56','2026-05-14 18:07:56'),(2,'Oxytocin','ßèªßè¡ßê▓ßë╢ßê▓ßèò','Hormone','Vet Med Ltd','OXY-2024-002',200,'vial',65.00,13000.00,20,'2025-06-30',NULL,'Vet Wholesale',1,'2026-05-14 18:07:56','2026-05-14 18:07:56'),(3,'Dewormer','ßï¿ßë╡ßêì ßêÿßï╡ßêÇßèÆßë╡','Dewormer','Animal Health Co','DEW-2024-003',1000,'tablet',15.00,15000.00,100,'2026-03-15',NULL,'Pharma Supply',1,'2026-05-14 18:07:56','2026-05-14 18:07:56'),(4,'Vaccine FMD','ßï¿ßèÑßîìßê¡ßèô ßèáßìì ßëáßê╜ßë│ ßè¡ßë╡ßëúßë╡','Vaccine','National Vet Institute','FMD-2024-004',300,'dose',120.00,36000.00,50,'2025-01-31',NULL,'Vet Wholesale',1,'2026-05-14 18:07:56','2026-05-14 18:07:56'),(5,'Calcium Injection','ßè½ßêìßê╜ßï¿ßê¥ ßêÿßê¡ßìî','Supplement','Ethiopian Pharma','CAL-2024-005',400,'bottle',35.00,14000.00,40,'2025-09-30',NULL,'Pharma Supply',1,'2026-05-14 18:07:56','2026-05-14 18:07:56'),(6,'Antibiotic Spray','ßèáßèòßë▓ßëúßï«ßë▓ßè¡ ßê╡ßìòßê¼','Antibiotic','Vet Med Ltd','ABS-2024-006',150,'bottle',85.00,12750.00,15,'2025-03-31',NULL,'Vet Wholesale',1,'2026-05-14 18:07:56','2026-05-14 18:07:56'),(7,'Vaccine LSD','ßï¿ßêïßê¥ ßê░ßê¥ ßëáßê╜ßë│ ßè¡ßë╡ßëúßë╡','Vaccine','National Vet Institute','LSD-2024-007',250,'dose',100.00,25000.00,30,'2025-02-28',NULL,'Vet Wholesale',1,'2026-05-14 18:07:56','2026-05-14 18:07:56'),(8,'Wound Dressing','ßëüßê╡ßêì ßêÿßê╕ßìêßè¢','Supply','Medical Supply Co','WDS-2024-008',200,'pack',25.00,5000.00,20,'2026-01-31',NULL,'Medical Supply',1,'2026-05-14 18:07:56','2026-05-14 18:07:56'),(9,'Electrolytes','ßèñßêîßè¡ßë╡ßê«ßêïßï¡ßë╡ßê╡','Supplement','Animal Health Co','ELE-2024-009',300,'sachet',40.00,12000.00,30,'2025-08-31',NULL,'Pharma Supply',1,'2026-05-14 18:07:56','2026-05-14 18:07:56'),(10,'Iron Supplement','ßëÑßê¿ßë╡ ßê¢ßêƒßï½','Supplement','Ethiopian Pharma','IRN-2024-010',250,'bottle',55.00,13750.00,25,'2025-11-30',NULL,'Pharma Supply',1,'2026-05-14 18:07:56','2026-05-14 18:07:56');
/*!40000 ALTER TABLE `medicine_inventory` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `milk_records`
--

DROP TABLE IF EXISTS `milk_records`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `milk_records` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `animal_id` int(11) NOT NULL,
  `morning_milk` decimal(8,2) DEFAULT 0.00,
  `evening_milk` decimal(8,2) DEFAULT 0.00,
  `total_milk` decimal(8,2) GENERATED ALWAYS AS (`morning_milk` + `evening_milk`) STORED,
  `milk_quality` enum('A','B','C') DEFAULT 'A',
  `notes` text DEFAULT NULL,
  `ethiopian_date` varchar(20) DEFAULT NULL,
  `record_date` date NOT NULL,
  `recorded_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_daily_record` (`animal_id`,`record_date`),
  KEY `recorded_by` (`recorded_by`),
  CONSTRAINT `milk_records_ibfk_1` FOREIGN KEY (`animal_id`) REFERENCES `animals` (`id`) ON DELETE CASCADE,
  CONSTRAINT `milk_records_ibfk_2` FOREIGN KEY (`recorded_by`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `milk_records`
--

LOCK TABLES `milk_records` WRITE;
/*!40000 ALTER TABLE `milk_records` DISABLE KEYS */;
/*!40000 ALTER TABLE `milk_records` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `milk_sales`
--

DROP TABLE IF EXISTS `milk_sales`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `milk_sales` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `sale_code` varchar(20) NOT NULL,
  `buyer_name` varchar(200) DEFAULT NULL,
  `buyer_phone` varchar(20) DEFAULT NULL,
  `quantity_liters` decimal(10,2) NOT NULL,
  `price_per_liter` decimal(10,2) NOT NULL,
  `total_amount` decimal(15,2) GENERATED ALWAYS AS (`quantity_liters` * `price_per_liter`) STORED,
  `payment_status` enum('paid','pending','partial') DEFAULT 'paid',
  `ethiopian_date` varchar(20) DEFAULT NULL,
  `sale_date` date NOT NULL,
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `sale_code` (`sale_code`),
  KEY `created_by` (`created_by`),
  CONSTRAINT `milk_sales_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `milk_sales`
--

LOCK TABLES `milk_sales` WRITE;
/*!40000 ALTER TABLE `milk_sales` DISABLE KEYS */;
/*!40000 ALTER TABLE `milk_sales` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `notifications`
--

DROP TABLE IF EXISTS `notifications`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `notifications` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `notification_type` enum('alert','warning','info','success','approval') NOT NULL,
  `title` varchar(200) NOT NULL,
  `title_am` varchar(200) DEFAULT NULL,
  `message` text NOT NULL,
  `message_am` text DEFAULT NULL,
  `link` varchar(255) DEFAULT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `ethiopian_date` varchar(20) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `notifications`
--

LOCK TABLES `notifications` WRITE;
/*!40000 ALTER TABLE `notifications` DISABLE KEYS */;
/*!40000 ALTER TABLE `notifications` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `password_resets`
--

DROP TABLE IF EXISTS `password_resets`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `password_resets` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `token` varchar(255) NOT NULL,
  `expires_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `used` tinyint(1) DEFAULT 0,
  `ethiopian_date` varchar(20) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `token` (`token`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `password_resets_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `password_resets`
--

LOCK TABLES `password_resets` WRITE;
/*!40000 ALTER TABLE `password_resets` DISABLE KEYS */;
/*!40000 ALTER TABLE `password_resets` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `poultry_batches`
--

DROP TABLE IF EXISTS `poultry_batches`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `poultry_batches` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `batch_code` varchar(20) NOT NULL,
  `breed` varchar(100) DEFAULT NULL,
  `breed_am` varchar(100) DEFAULT NULL,
  `batch_type` enum('layers','broilers','dual_purpose','indigenous') NOT NULL,
  `initial_count` int(11) NOT NULL,
  `current_count` int(11) NOT NULL,
  `start_date` date NOT NULL,
  `ethiopian_start_date` varchar(20) DEFAULT NULL,
  `expected_end_date` date DEFAULT NULL,
  `age_weeks` int(11) DEFAULT 0 COMMENT 'Age in weeks - calculated by application',
  `housing_unit` varchar(100) DEFAULT NULL,
  `status` enum('active','completed','cancelled') DEFAULT 'active',
  `notes` text DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `batch_code` (`batch_code`),
  KEY `created_by` (`created_by`),
  CONSTRAINT `poultry_batches_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `poultry_batches`
--

LOCK TABLES `poultry_batches` WRITE;
/*!40000 ALTER TABLE `poultry_batches` DISABLE KEYS */;
/*!40000 ALTER TABLE `poultry_batches` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `poultry_feed_records`
--

DROP TABLE IF EXISTS `poultry_feed_records`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `poultry_feed_records` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `batch_id` int(11) NOT NULL,
  `feed_type` varchar(100) NOT NULL,
  `quantity_kg` decimal(10,2) NOT NULL,
  `cost_per_kg` decimal(10,2) DEFAULT NULL,
  `total_cost` decimal(15,2) GENERATED ALWAYS AS (`quantity_kg` * `cost_per_kg`) STORED,
  `ethiopian_date` varchar(20) DEFAULT NULL,
  `feed_date` date NOT NULL,
  `fed_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `batch_id` (`batch_id`),
  KEY `fed_by` (`fed_by`),
  CONSTRAINT `poultry_feed_records_ibfk_1` FOREIGN KEY (`batch_id`) REFERENCES `poultry_batches` (`id`) ON DELETE CASCADE,
  CONSTRAINT `poultry_feed_records_ibfk_2` FOREIGN KEY (`fed_by`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `poultry_feed_records`
--

LOCK TABLES `poultry_feed_records` WRITE;
/*!40000 ALTER TABLE `poultry_feed_records` DISABLE KEYS */;
/*!40000 ALTER TABLE `poultry_feed_records` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `poultry_sales`
--

DROP TABLE IF EXISTS `poultry_sales`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `poultry_sales` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `sale_code` varchar(20) NOT NULL,
  `batch_id` int(11) NOT NULL,
  `sale_type` enum('eggs','live_birds','meat','other') NOT NULL,
  `quantity` int(11) NOT NULL,
  `unit_price` decimal(10,2) NOT NULL,
  `total_amount` decimal(15,2) GENERATED ALWAYS AS (`quantity` * `unit_price`) STORED,
  `buyer_name` varchar(200) DEFAULT NULL,
  `buyer_phone` varchar(20) DEFAULT NULL,
  `payment_status` enum('paid','pending','partial') DEFAULT 'paid',
  `ethiopian_date` varchar(20) DEFAULT NULL,
  `sale_date` date NOT NULL,
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `sale_code` (`sale_code`),
  KEY `batch_id` (`batch_id`),
  KEY `created_by` (`created_by`),
  CONSTRAINT `poultry_sales_ibfk_1` FOREIGN KEY (`batch_id`) REFERENCES `poultry_batches` (`id`) ON DELETE CASCADE,
  CONSTRAINT `poultry_sales_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `poultry_sales`
--

LOCK TABLES `poultry_sales` WRITE;
/*!40000 ALTER TABLE `poultry_sales` DISABLE KEYS */;
/*!40000 ALTER TABLE `poultry_sales` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `procurement_requests`
--

DROP TABLE IF EXISTS `procurement_requests`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `procurement_requests` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `request_code` varchar(20) NOT NULL,
  `department` varchar(100) NOT NULL,
  `requester_id` int(11) NOT NULL,
  `item_name` varchar(200) NOT NULL,
  `item_name_am` varchar(200) DEFAULT NULL,
  `quantity` int(11) NOT NULL,
  `unit` varchar(50) DEFAULT NULL,
  `estimated_cost` decimal(15,2) DEFAULT NULL,
  `priority` enum('low','medium','high','urgent') DEFAULT 'medium',
  `justification` text DEFAULT NULL,
  `status` enum('draft','pending','approved','rejected','purchased') DEFAULT 'draft',
  `ethiopian_request_date` varchar(20) DEFAULT NULL,
  `ethiopian_required_date` varchar(20) DEFAULT NULL,
  `approved_by` int(11) DEFAULT NULL,
  `approved_date` timestamp NULL DEFAULT NULL,
  `rejection_reason` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `request_code` (`request_code`),
  KEY `requester_id` (`requester_id`),
  KEY `approved_by` (`approved_by`),
  CONSTRAINT `procurement_requests_ibfk_1` FOREIGN KEY (`requester_id`) REFERENCES `users` (`id`),
  CONSTRAINT `procurement_requests_ibfk_2` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `procurement_requests`
--

LOCK TABLES `procurement_requests` WRITE;
/*!40000 ALTER TABLE `procurement_requests` DISABLE KEYS */;
/*!40000 ALTER TABLE `procurement_requests` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `purchase_orders`
--

DROP TABLE IF EXISTS `purchase_orders`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `purchase_orders` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `po_number` varchar(20) NOT NULL,
  `request_id` int(11) DEFAULT NULL,
  `supplier_name` varchar(200) DEFAULT NULL,
  `supplier_phone` varchar(20) DEFAULT NULL,
  `total_amount` decimal(15,2) NOT NULL,
  `paid_amount` decimal(15,2) DEFAULT 0.00,
  `payment_status` enum('unpaid','partial','paid') DEFAULT 'unpaid',
  `receipt_path` varchar(255) DEFAULT NULL,
  `invoice_path` varchar(255) DEFAULT NULL,
  `ethiopian_date` varchar(20) DEFAULT NULL,
  `status` enum('ordered','received','cancelled') DEFAULT 'ordered',
  `received_by` int(11) DEFAULT NULL,
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `po_number` (`po_number`),
  KEY `request_id` (`request_id`),
  KEY `received_by` (`received_by`),
  KEY `created_by` (`created_by`),
  CONSTRAINT `purchase_orders_ibfk_1` FOREIGN KEY (`request_id`) REFERENCES `procurement_requests` (`id`) ON DELETE SET NULL,
  CONSTRAINT `purchase_orders_ibfk_2` FOREIGN KEY (`received_by`) REFERENCES `users` (`id`),
  CONSTRAINT `purchase_orders_ibfk_3` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `purchase_orders`
--

LOCK TABLES `purchase_orders` WRITE;
/*!40000 ALTER TABLE `purchase_orders` DISABLE KEYS */;
/*!40000 ALTER TABLE `purchase_orders` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `roles`
--

DROP TABLE IF EXISTS `roles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `roles` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `role_name` varchar(50) NOT NULL,
  `role_name_am` varchar(100) DEFAULT NULL COMMENT 'Role name in Amharic',
  `role_name_or` varchar(100) DEFAULT NULL COMMENT 'Role name in Afaan Oromo',
  `permissions` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`permissions`)),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `role_name` (`role_name`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `roles`
--

LOCK TABLES `roles` WRITE;
/*!40000 ALTER TABLE `roles` DISABLE KEYS */;
INSERT INTO `roles` VALUES (1,'Owner','ßëúßêêßëñßë╡','Abbaa Qabeenyaa','{\"all\": true, \"dashboard\": true, \"finance\": true, \"property\": true, \"livestock\": true, \"poultry\": true, \"fish\": true, \"irrigation\": true, \"procurement\": true, \"medicine\": true, \"production\": true, \"reports\": true, \"users\": true, \"backup\": true, \"audit\": true, \"settings\": true}','2026-05-14 18:07:54','2026-05-14 18:07:54'),(2,'SuperAdmin','ßïïßèô ßèáßê╡ßë░ßï│ßï│ßê¬','Super Admin','{\"all\": true, \"dashboard\": true, \"finance\": true, \"property\": true, \"livestock\": true, \"poultry\": true, \"fish\": true, \"irrigation\": true, \"procurement\": true, \"medicine\": true, \"production\": true, \"reports\": true, \"users\": true, \"audit\": true}','2026-05-14 18:07:54','2026-05-14 18:07:54'),(3,'Admin','ßèáßê╡ßë░ßï│ßï│ßê¬','Admin','{\"dashboard\": true, \"finance\": false, \"property\": false, \"livestock\": true, \"poultry\": true, \"fish\": true, \"irrigation\": true, \"procurement\": false, \"medicine\": true, \"production\": true, \"reports\": true, \"users\": false}','2026-05-14 18:07:54','2026-05-14 18:07:54'),(4,'Finance','ßìïßï¡ßèôßèòßê╡','Faayinaansii','{\"dashboard\": true, \"finance\": true, \"property\": true, \"procurement\": true, \"reports\": true}','2026-05-14 18:07:54','2026-05-14 18:07:54'),(5,'Irrigation Supervisor','ßï¿ßêÿßê╡ßèû ßë░ßëåßîúßîúßê¬','To\'ataa Jallisii','{\"dashboard\": true, \"irrigation\": true, \"reports\": false}','2026-05-14 18:07:54','2026-05-14 18:07:54'),(6,'Poultry Supervisor','ßï¿ßï╢ßê« ßèÑßê¡ßëúßë│ ßë░ßëåßîúßîúßê¬','To\'ataa Lukkuu','{\"dashboard\": true, \"poultry\": true, \"reports\": false}','2026-05-14 18:07:54','2026-05-14 18:07:54'),(7,'Cattle Supervisor','ßï¿ßè¿ßëÑßë╡ ßèÑßê¡ßëúßë│ ßë░ßëåßîúßîúßê¬','To\'ataa Loonii','{\"dashboard\": true, \"livestock\": true, \"production\": true, \"reports\": false}','2026-05-14 18:07:54','2026-05-14 18:07:54'),(8,'Fish Supervisor','ßï¿ßèáßê│ ßèÑßê¡ßëúßë│ ßë░ßëåßîúßîúßê¬','To\'ataa Qurtummii','{\"dashboard\": true, \"fish\": true, \"reports\": false}','2026-05-14 18:07:54','2026-05-14 18:07:54'),(9,'Doctor','ßï╢ßè¡ßë░ßê¡','Dooktarii','{\"dashboard\": true, \"medicine\": true, \"livestock\": true, \"poultry\": true, \"reports\": false}','2026-05-14 18:07:54','2026-05-14 18:07:54');
/*!40000 ALTER TABLE `roles` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sessions`
--

DROP TABLE IF EXISTS `sessions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `sessions` (
  `id` varchar(128) NOT NULL,
  `user_id` int(11) NOT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `last_activity` int(11) NOT NULL,
  `data` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `sessions_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sessions`
--

LOCK TABLES `sessions` WRITE;
/*!40000 ALTER TABLE `sessions` DISABLE KEYS */;
/*!40000 ALTER TABLE `sessions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `system_settings`
--

DROP TABLE IF EXISTS `system_settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `system_settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `setting_key` varchar(100) NOT NULL,
  `setting_value` text DEFAULT NULL,
  `setting_type` enum('text','number','boolean','json','file') DEFAULT 'text',
  `description` varchar(255) DEFAULT NULL,
  `updated_by` int(11) DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `setting_key` (`setting_key`),
  KEY `updated_by` (`updated_by`),
  CONSTRAINT `system_settings_ibfk_1` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `system_settings`
--

LOCK TABLES `system_settings` WRITE;
/*!40000 ALTER TABLE `system_settings` DISABLE KEYS */;
/*!40000 ALTER TABLE `system_settings` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `transaction_categories`
--

DROP TABLE IF EXISTS `transaction_categories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `transaction_categories` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `category_name` varchar(100) NOT NULL,
  `category_name_am` varchar(100) DEFAULT NULL,
  `category_type` enum('income','expense') NOT NULL,
  `icon` varchar(50) DEFAULT NULL,
  `color` varchar(7) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `transaction_categories`
--

LOCK TABLES `transaction_categories` WRITE;
/*!40000 ALTER TABLE `transaction_categories` DISABLE KEYS */;
INSERT INTO `transaction_categories` VALUES (1,'Feed Purchase','ßï¿ßêÿßèû ßîìßïÑ','expense','fa-seedling','#e74c3c',1,'2026-05-14 18:07:55'),(2,'Medicine Purchase','ßï¿ßêÿßï╡ßêÇßèÆßë╡ ßîìßïÑ','expense','fa-pills','#e74c3c',1,'2026-05-14 18:07:55'),(3,'Equipment Purchase','ßï¿ßêÿßê│ßê¬ßï½ ßîìßïÑ','expense','fa-tools','#e74c3c',1,'2026-05-14 18:07:55'),(4,'Salary Payment','ßï░ßê₧ßï¥ ßè¡ßììßï½','expense','fa-money-bill','#e74c3c',1,'2026-05-14 18:07:55'),(5,'Utility Bills','ßï¿ßììßîåßë│ ßè¡ßììßï½','expense','fa-bolt','#e74c3c',1,'2026-05-14 18:07:55'),(6,'Milk Sales','ßï¿ßïêßë░ßë╡ ßê╜ßï½ßî¡','income','fa-glass-whiskey','#2ecc71',1,'2026-05-14 18:07:55'),(7,'Cattle Sales','ßï¿ßè¿ßëÑßë╡ ßê╜ßï½ßî¡','income','fa-cow','#2ecc71',1,'2026-05-14 18:07:55'),(8,'Egg Sales','ßï¿ßèÑßèòßëüßêïßêì ßê╜ßï½ßî¡','income','fa-egg','#2ecc71',1,'2026-05-14 18:07:55'),(9,'Fish Sales','ßï¿ßèáßê│ ßê╜ßï½ßî¡','income','fa-fish','#2ecc71',1,'2026-05-14 18:07:55'),(10,'Crop Sales','ßï¿ßê░ßëÑßêì ßê╜ßï½ßî¡','income','fa-wheat','#2ecc71',1,'2026-05-14 18:07:55');
/*!40000 ALTER TABLE `transaction_categories` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `transactions`
--

DROP TABLE IF EXISTS `transactions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `transactions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `transaction_code` varchar(20) NOT NULL,
  `transaction_type` enum('income','expense','bank_deposit','bank_withdrawal','transfer','adjustment') NOT NULL,
  `bank_account_id` int(11) DEFAULT NULL,
  `category_id` int(11) DEFAULT NULL,
  `amount` decimal(15,2) NOT NULL,
  `description` text DEFAULT NULL,
  `description_am` text DEFAULT NULL,
  `receipt_path` varchar(255) DEFAULT NULL,
  `ethiopian_date` varchar(20) DEFAULT NULL,
  `gregorian_date` date NOT NULL,
  `status` enum('pending','approved','rejected','completed') DEFAULT 'completed',
  `approved_by` int(11) DEFAULT NULL,
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `transaction_code` (`transaction_code`),
  KEY `bank_account_id` (`bank_account_id`),
  KEY `category_id` (`category_id`),
  KEY `approved_by` (`approved_by`),
  KEY `created_by` (`created_by`),
  CONSTRAINT `transactions_ibfk_1` FOREIGN KEY (`bank_account_id`) REFERENCES `bank_accounts` (`id`) ON DELETE SET NULL,
  CONSTRAINT `transactions_ibfk_2` FOREIGN KEY (`category_id`) REFERENCES `transaction_categories` (`id`) ON DELETE SET NULL,
  CONSTRAINT `transactions_ibfk_3` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `transactions_ibfk_4` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `transactions`
--

LOCK TABLES `transactions` WRITE;
/*!40000 ALTER TABLE `transactions` DISABLE KEYS */;
/*!40000 ALTER TABLE `transactions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `full_name` varchar(100) NOT NULL,
  `full_name_am` varchar(100) DEFAULT NULL COMMENT 'Full name in Amharic',
  `phone` varchar(20) DEFAULT NULL,
  `role_id` int(11) NOT NULL,
  `force_password_change` tinyint(1) DEFAULT 1,
  `is_active` tinyint(1) DEFAULT 1,
  `last_login` timestamp NULL DEFAULT NULL,
  `login_attempts` int(11) DEFAULT 0,
  `locked_until` timestamp NULL DEFAULT NULL,
  `language_preference` enum('en','am','or') DEFAULT 'am',
  `ethiopian_date_registered` varchar(20) DEFAULT NULL,
  `profile_photo` varchar(255) DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`),
  UNIQUE KEY `email` (`email`),
  KEY `role_id` (`role_id`),
  KEY `created_by` (`created_by`),
  CONSTRAINT `users_ibfk_1` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`),
  CONSTRAINT `users_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (1,'owner','$2y$10$2jLJuwcN6F8N6xhsq3cEVOk8XrOxTVyqjfNPG.HRbjvx/Y7Jdv3Ne','owner@teklu.com','Teklu Getachew','ßë░ßè¡ßêë ßîîßë│ßë╕ßïì','+251911234567',1,0,1,NULL,1,NULL,'am',NULL,NULL,NULL,'2026-05-14 18:07:55','2026-05-14 19:27:04'),(2,'superadmin','$2y$10$2mT4KwoVtzEiVIFzpGFigeFWPgZiHyiNs7vVGS3K5yhKjSbMMc8lO','superadmin@teklu.com','Abebe Kebede','ßèáßëáßëá ßè¿ßëáßï░','+251922345678',2,0,1,'2026-05-16 10:13:40',0,NULL,'am',NULL,NULL,NULL,'2026-05-14 18:07:55','2026-05-16 10:13:40'),(3,'admin','$2y$10$2jLJuwcN6F8N6xhsq3cEVOk8XrOxTVyqjfNPG.HRbjvx/Y7Jdv3Ne','admin@teklu.com','Tigist Haile','ßë╡ßîìßê╡ßë╡ ßèâßï¡ßêî','+251933456789',3,1,1,NULL,1,NULL,'am',NULL,NULL,NULL,'2026-05-14 18:07:55','2026-05-14 19:27:04'),(4,'finance1','$2y$10$2jLJuwcN6F8N6xhsq3cEVOk8XrOxTVyqjfNPG.HRbjvx/Y7Jdv3Ne','finance@teklu.com','Dawit Tadesse','ßï│ßïèßë╡ ßë│ßï░ßê░','+251944567890',4,1,1,NULL,0,NULL,'am',NULL,NULL,NULL,'2026-05-14 18:07:55','2026-05-14 19:27:04'),(5,'irrigation1','$2y$10$vYvCidRWAoKApkw9bhZe3O3nbd7vc4waOLl3fnNAWUpiwnSH/hlWW','irrigation@teklu.com','Solomon Alemu','ßê░ßêÄßê₧ßèò ßèáßêêßêÖ','+251955678901',5,0,1,'2026-05-16 09:25:09',0,NULL,'am',NULL,NULL,NULL,'2026-05-14 18:07:55','2026-05-16 09:25:20'),(6,'poultry1','$2y$10$Z4a4Q8erEl8BhZdJNkWmdehBsp9./mCz37hmdAByr3FMZSSCuCQt.','poultry@teklu.com','Meseret Dereje','ßêÿßê░ßê¿ßë╡ ßï░ßê¿ßîÇ','+251966789012',6,0,1,'2026-05-16 09:27:44',0,NULL,'am',NULL,NULL,NULL,'2026-05-14 18:07:55','2026-05-16 09:27:52'),(7,'cattle1','$2y$10$vtBUzOojzwObP6GHPMReoes6hwqOl7Cmbj0AE.dD4.ASMVi.IJbPu','cattle@teklu.com','Getachew Mulugeta','ßîîßë│ßë╕ßïì ßêÖßêëßîîßë│','+251977890123',7,0,1,'2026-05-16 09:47:25',0,NULL,'am',NULL,NULL,NULL,'2026-05-14 18:07:55','2026-05-16 09:47:25'),(8,'fish1','$2y$10$C67y6m7rplox3CjEwSmcnOEl1J07jhyEbSmX0tpBc6RJEO1ZJdn2u','fish@teklu.com','Daniel Worku','ßï│ßèòßèñßêì ßïêßê¡ßëü','+251988901234',8,0,1,'2026-05-16 09:47:53',0,NULL,'am',NULL,NULL,NULL,'2026-05-14 18:07:55','2026-05-16 09:48:06'),(9,'doctor1','$2y$10$Yuvf57wcILhyUBw.tHVe0uaHEoFtV39uzxy6km2r5l2XtFbdQr.YW','doctor@teklu.com','Dr. Yohannes Bekele','ßï╢/ßê¡ ßï«ßêÉßèòßê╡ ßëáßëÇßêê','+251999012345',9,0,1,'2026-05-16 08:53:27',0,NULL,'am',NULL,NULL,NULL,'2026-05-14 18:07:55','2026-05-16 08:53:41'),(10,'finance2','$2y$10$2jLJuwcN6F8N6xhsq3cEVOk8XrOxTVyqjfNPG.HRbjvx/Y7Jdv3Ne','finance2@teklu.com','Meron Girma','ßê£ßê«ßèò ßîìßê¡ßê¢','+251910123456',4,1,1,NULL,0,NULL,'am',NULL,NULL,NULL,'2026-05-14 18:07:55','2026-05-14 19:27:04');
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `water_usage`
--

DROP TABLE IF EXISTS `water_usage`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `water_usage` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `field_id` int(11) NOT NULL,
  `water_amount_cubic` decimal(10,2) NOT NULL,
  `usage_hours` decimal(5,2) DEFAULT NULL,
  `pump_electricity_cost` decimal(10,2) DEFAULT NULL,
  `ethiopian_date` varchar(20) DEFAULT NULL,
  `usage_date` date NOT NULL,
  `operator_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `field_id` (`field_id`),
  KEY `operator_id` (`operator_id`),
  CONSTRAINT `water_usage_ibfk_1` FOREIGN KEY (`field_id`) REFERENCES `irrigation_fields` (`id`) ON DELETE CASCADE,
  CONSTRAINT `water_usage_ibfk_2` FOREIGN KEY (`operator_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `water_usage`
--

LOCK TABLES `water_usage` WRITE;
/*!40000 ALTER TABLE `water_usage` DISABLE KEYS */;
/*!40000 ALTER TABLE `water_usage` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping routines for database 'teklu_getachew_erp'
--
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-07-25  2:07:32
