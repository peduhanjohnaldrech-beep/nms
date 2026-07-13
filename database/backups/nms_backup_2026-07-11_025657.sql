-- MariaDB dump 10.19  Distrib 10.4.32-MariaDB, for Win64 (AMD64)
--
-- Host: localhost    Database: nms
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
-- Table structure for table `activity_logs`
--

DROP TABLE IF EXISTS `activity_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `activity_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `user_name` varchar(200) DEFAULT NULL,
  `action` varchar(200) NOT NULL,
  `description` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `activity_logs`
--

LOCK TABLES `activity_logs` WRITE;
/*!40000 ALTER TABLE `activity_logs` DISABLE KEYS */;
INSERT INTO `activity_logs` VALUES (1,1,'System Administrator','login','User logged in: System Administrator (admin)','::1','2026-06-30 01:28:27'),(2,1,'System Administrator','beneficiary_create','Added beneficiary ID 1: Peduhan, Erwin','::1','2026-06-30 01:28:50'),(3,1,'System Administrator','login','User logged in: System Administrator (admin)','::1','2026-07-06 09:38:58');
/*!40000 ALTER TABLE `activity_logs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `api_tokens`
--

DROP TABLE IF EXISTS `api_tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `api_tokens` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `token` varchar(255) NOT NULL,
  `device_name` varchar(200) DEFAULT NULL,
  `expires_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `token` (`token`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `api_tokens_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `api_tokens`
--

LOCK TABLES `api_tokens` WRITE;
/*!40000 ALTER TABLE `api_tokens` DISABLE KEYS */;
INSERT INTO `api_tokens` VALUES (1,1,'4a19bf2dc9428d57f08294a0ca005b86579d30f6eaa252f777915be8c3f54214','Mobile','2026-07-30 15:59:44','2026-06-30 07:59:44'),(2,1,'2c18e6eed0b47f18139829da098ef5043798b980f4d81a0933279ac5449401e7','NMS Mobile','2026-07-30 16:19:58','2026-06-30 08:19:58'),(3,1,'5ea90eab0ca3381f0199ff91ccd88c76a5dfa3eb0f096b9f259d1cbd4078058d','NMS Mobile','2026-07-31 11:01:08','2026-07-01 03:01:08'),(7,1,'cafe74ff89fce5b43bf88caaa6ec266f3352a6658e45af3929f2f3ad5a37445a','NMS Mobile','2026-08-06 21:06:24','2026-07-07 13:06:24'),(9,2,'7497bba66b179596e73f17c4c09182a3197b571301e4c5ba54e73f5cd91bd1a1','NMS Mobile','2026-08-07 14:01:16','2026-07-08 06:01:16');
/*!40000 ALTER TABLE `api_tokens` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `assessments`
--

DROP TABLE IF EXISTS `assessments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `assessments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `beneficiary_id` int(11) NOT NULL,
  `assessment_date` date NOT NULL,
  `age_in_months` int(11) NOT NULL,
  `weight_kg` decimal(5,2) NOT NULL,
  `height_cm` decimal(5,2) DEFAULT NULL,
  `muac_cm` decimal(5,2) DEFAULT NULL,
  `weight_for_age_zscore` decimal(6,3) DEFAULT NULL,
  `height_for_age_zscore` decimal(6,3) DEFAULT NULL,
  `hfa_status` varchar(20) DEFAULT NULL,
  `wflh_zscore` decimal(6,3) DEFAULT NULL,
  `wflh_status` varchar(20) DEFAULT NULL,
  `nutritional_status` varchar(20) NOT NULL DEFAULT 'Normal',
  `period` varchar(20) NOT NULL,
  `assessment_year` int(11) NOT NULL,
  `assessed_by` varchar(200) DEFAULT NULL,
  `remarks` text DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `validation_status` varchar(20) NOT NULL DEFAULT 'pending',
  `validated_by` int(11) DEFAULT NULL,
  `validated_at` datetime DEFAULT NULL,
  `rejection_note` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `created_by` (`created_by`),
  KEY `idx_assess_bene` (`beneficiary_id`),
  KEY `idx_assess_year` (`assessment_year`,`period`),
  KEY `idx_assess_status` (`nutritional_status`),
  CONSTRAINT `assessments_ibfk_1` FOREIGN KEY (`beneficiary_id`) REFERENCES `beneficiaries` (`id`) ON DELETE CASCADE,
  CONSTRAINT `assessments_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `assessments`
--

LOCK TABLES `assessments` WRITE;
/*!40000 ALTER TABLE `assessments` DISABLE KEYS */;
INSERT INTO `assessments` VALUES (1,1,'2026-06-30',0,12.50,85.00,NULL,NULL,NULL,NULL,-0.111,'Normal','Normal','January',2026,'System Administrator',NULL,1,'2026-06-30 12:56:40','pending',NULL,NULL,NULL);
/*!40000 ALTER TABLE `assessments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `beneficiaries`
--

DROP TABLE IF EXISTS `beneficiaries`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `beneficiaries` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `last_name` varchar(100) NOT NULL,
  `first_name` varchar(100) NOT NULL,
  `middle_name` varchar(100) DEFAULT NULL,
  `suffix` varchar(20) DEFAULT NULL,
  `date_of_birth` date NOT NULL,
  `sex` varchar(10) NOT NULL,
  `place_of_birth` varchar(200) DEFAULT NULL,
  `region` varchar(100) DEFAULT NULL,
  `province` varchar(100) DEFAULT NULL,
  `city_municipality` varchar(100) DEFAULT NULL,
  `barangay` varchar(100) NOT NULL,
  `purok_zone` varchar(100) DEFAULT NULL,
  `household_no` varchar(50) DEFAULT NULL,
  `incode` varchar(50) DEFAULT NULL,
  `mother_name` varchar(200) DEFAULT NULL,
  `father_name` varchar(200) DEFAULT NULL,
  `guardian_name` varchar(200) DEFAULT NULL,
  `guardian_relationship` varchar(100) DEFAULT NULL,
  `contact_number` varchar(50) DEFAULT NULL,
  `income_classification` varchar(100) DEFAULT NULL,
  `household_monthly_income` decimal(12,2) DEFAULT NULL,
  `income_source` varchar(200) DEFAULT NULL,
  `philhealth_status` varchar(100) DEFAULT NULL,
  `is_4ps_member` tinyint(1) NOT NULL DEFAULT 0,
  `nhts_pr_status` varchar(100) DEFAULT NULL,
  `is_pwd_household` tinyint(1) NOT NULL DEFAULT 0,
  `is_indigenous_people` tinyint(1) NOT NULL DEFAULT 0,
  `ip_group` varchar(100) DEFAULT NULL,
  `photo` varchar(255) DEFAULT NULL,
  `source` varchar(100) DEFAULT NULL,
  `validation_status` varchar(20) NOT NULL DEFAULT 'validated',
  `validated_by` int(11) DEFAULT NULL,
  `validated_at` datetime DEFAULT NULL,
  `rejection_note` text DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `deleted_at` datetime DEFAULT NULL,
  `submitted_at` datetime DEFAULT NULL,
  `submitted_by` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `created_by` (`created_by`),
  KEY `idx_bene_barangay` (`barangay`),
  KEY `idx_bene_name` (`last_name`,`first_name`),
  KEY `idx_bene_dob` (`date_of_birth`),
  KEY `idx_bene_deleted_at` (`deleted_at`),
  KEY `idx_bene_source` (`source`),
  CONSTRAINT `beneficiaries_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `beneficiaries`
--

LOCK TABLES `beneficiaries` WRITE;
/*!40000 ALTER TABLE `beneficiaries` DISABLE KEYS */;
INSERT INTO `beneficiaries` VALUES (1,'Peduhan','Erwin',NULL,NULL,'2026-06-16','Male',NULL,NULL,NULL,NULL,'1',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,NULL,0,0,NULL,NULL,'Walk-in','validated',NULL,NULL,NULL,1,'2026-06-30 01:28:50','2026-06-30 01:28:50',NULL,NULL,NULL),(2,'Test','User','','','2023-01-01','Male',NULL,NULL,NULL,NULL,'1',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,NULL,0,0,NULL,NULL,'Mobile','validated',NULL,NULL,NULL,1,'2026-06-30 10:53:38','2026-06-30 10:55:52','2026-06-30 18:55:52',NULL,NULL);
/*!40000 ALTER TABLE `beneficiaries` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `dispensing_records`
--

DROP TABLE IF EXISTS `dispensing_records`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `dispensing_records` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `beneficiary_id` int(11) NOT NULL,
  `enrollment_id` int(11) DEFAULT NULL,
  `program` varchar(50) NOT NULL,
  `supplement_type` varchar(100) NOT NULL,
  `quantity` decimal(10,2) NOT NULL DEFAULT 1.00,
  `unit` varchar(50) NOT NULL DEFAULT 'piece(s)',
  `date_dispensed` date NOT NULL,
  `dispensed_by` int(11) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `enrollment_id` (`enrollment_id`),
  KEY `dispensed_by` (`dispensed_by`),
  KEY `idx_disp_bene` (`beneficiary_id`),
  KEY `idx_disp_prog` (`program`),
  KEY `idx_disp_date` (`date_dispensed`),
  KEY `idx_disp_type` (`supplement_type`),
  CONSTRAINT `dispensing_records_ibfk_1` FOREIGN KEY (`beneficiary_id`) REFERENCES `beneficiaries` (`id`) ON DELETE CASCADE,
  CONSTRAINT `dispensing_records_ibfk_2` FOREIGN KEY (`enrollment_id`) REFERENCES `program_enrollments` (`id`) ON DELETE SET NULL,
  CONSTRAINT `dispensing_records_ibfk_3` FOREIGN KEY (`dispensed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `dispensing_records`
--

LOCK TABLES `dispensing_records` WRITE;
/*!40000 ALTER TABLE `dispensing_records` DISABLE KEYS */;
/*!40000 ALTER TABLE `dispensing_records` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `import_logs`
--

DROP TABLE IF EXISTS `import_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `import_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `filename` varchar(255) DEFAULT NULL,
  `saved_filename` varchar(255) DEFAULT NULL,
  `imported_by` int(11) DEFAULT NULL,
  `import_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `total_rows` int(11) NOT NULL DEFAULT 0,
  `success_count` int(11) NOT NULL DEFAULT 0,
  `error_count` int(11) NOT NULL DEFAULT 0,
  `error_details` text DEFAULT NULL,
  `folder` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `imported_by` (`imported_by`),
  CONSTRAINT `import_logs_ibfk_1` FOREIGN KEY (`imported_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `import_logs`
--

LOCK TABLES `import_logs` WRITE;
/*!40000 ALTER TABLE `import_logs` DISABLE KEYS */;
/*!40000 ALTER TABLE `import_logs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `lns_sq_records`
--

DROP TABLE IF EXISTS `lns_sq_records`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `lns_sq_records` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `beneficiary_id` int(11) NOT NULL,
  `given_by` int(11) DEFAULT NULL,
  `date_given` date NOT NULL,
  `year` int(11) NOT NULL,
  `age_group` varchar(50) NOT NULL,
  `completed_routine` tinyint(1) NOT NULL DEFAULT 0,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `given_by` (`given_by`),
  KEY `idx_lns_bene` (`beneficiary_id`),
  KEY `idx_lns_year` (`year`),
  CONSTRAINT `lns_sq_records_ibfk_1` FOREIGN KEY (`beneficiary_id`) REFERENCES `beneficiaries` (`id`) ON DELETE CASCADE,
  CONSTRAINT `lns_sq_records_ibfk_2` FOREIGN KEY (`given_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `lns_sq_records`
--

LOCK TABLES `lns_sq_records` WRITE;
/*!40000 ALTER TABLE `lns_sq_records` DISABLE KEYS */;
INSERT INTO `lns_sq_records` VALUES (1,1,1,'2026-07-01',2026,'6-23 months',0,NULL,'2026-07-01 22:43:40');
/*!40000 ALTER TABLE `lns_sq_records` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `mnp_records`
--

DROP TABLE IF EXISTS `mnp_records`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `mnp_records` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `beneficiary_id` int(11) NOT NULL,
  `given_by` int(11) DEFAULT NULL,
  `date_given` date NOT NULL,
  `year` int(11) NOT NULL,
  `age_group` varchar(50) NOT NULL,
  `completed_routine` tinyint(1) NOT NULL DEFAULT 0,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `given_by` (`given_by`),
  KEY `idx_mnp_bene` (`beneficiary_id`),
  KEY `idx_mnp_year` (`year`),
  CONSTRAINT `mnp_records_ibfk_1` FOREIGN KEY (`beneficiary_id`) REFERENCES `beneficiaries` (`id`) ON DELETE CASCADE,
  CONSTRAINT `mnp_records_ibfk_2` FOREIGN KEY (`given_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `mnp_records`
--

LOCK TABLES `mnp_records` WRITE;
/*!40000 ALTER TABLE `mnp_records` DISABLE KEYS */;
INSERT INTO `mnp_records` VALUES (1,1,1,'2026-07-01',2026,'6-11 months',0,NULL,'2026-07-01 22:43:40');
/*!40000 ALTER TABLE `mnp_records` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `program_enrollments`
--

DROP TABLE IF EXISTS `program_enrollments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `program_enrollments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `beneficiary_id` int(11) NOT NULL,
  `program` varchar(20) NOT NULL,
  `enrollment_date` date NOT NULL,
  `end_date` date DEFAULT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'Active',
  `cycle_year` int(11) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `intervention_type` varchar(100) DEFAULT NULL,
  `pre_weight_kg` decimal(5,2) DEFAULT NULL,
  `post_weight_kg` decimal(5,2) DEFAULT NULL,
  `enrolled_by` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `enrolled_by` (`enrolled_by`),
  KEY `idx_enroll_bene` (`beneficiary_id`),
  KEY `idx_enroll_program` (`program`,`status`),
  KEY `idx_enroll_year` (`cycle_year`),
  CONSTRAINT `program_enrollments_ibfk_1` FOREIGN KEY (`beneficiary_id`) REFERENCES `beneficiaries` (`id`) ON DELETE CASCADE,
  CONSTRAINT `program_enrollments_ibfk_2` FOREIGN KEY (`enrolled_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `program_enrollments`
--

LOCK TABLES `program_enrollments` WRITE;
/*!40000 ALTER TABLE `program_enrollments` DISABLE KEYS */;
INSERT INTO `program_enrollments` VALUES (1,1,'DSP','2026-07-01','2026-07-06','Active',2026,'',NULL,12.50,17.00,1);
/*!40000 ALTER TABLE `program_enrollments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `programs`
--

DROP TABLE IF EXISTS `programs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `programs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `code` varchar(20) NOT NULL,
  `name` varchar(200) NOT NULL,
  `description` text DEFAULT NULL,
  `icon` varchar(100) NOT NULL DEFAULT 'bi-clipboard-check',
  `color` varchar(50) NOT NULL DEFAULT 'primary',
  `type` varchar(50) NOT NULL DEFAULT 'generic',
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `code` (`code`),
  KEY `idx_prog_active` (`is_active`),
  KEY `idx_prog_sort` (`sort_order`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `programs`
--

LOCK TABLES `programs` WRITE;
/*!40000 ALTER TABLE `programs` DISABLE KEYS */;
INSERT INTO `programs` VALUES (1,'OPT','Operation Timbang','Nutritional screening and assessment of children 0-59 months','bi-scale','success','assessment',1,1,'2026-06-30 01:26:44'),(2,'DSP','Dietary Supplementation Program','Supplementary feeding and nutrition intervention for malnourished children','bi-egg-fried','warning','supplementation',1,2,'2026-06-30 01:26:44'),(3,'MNS','Micronutrient Supplementation','Vitamin A, MNP, and LNS-SQ distribution for children','bi-capsule','primary','micronutrient',1,3,'2026-06-30 01:26:44');
/*!40000 ALTER TABLE `programs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `stored_files`
--

DROP TABLE IF EXISTS `stored_files`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `stored_files` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `original_filename` varchar(255) NOT NULL,
  `saved_filename` varchar(255) NOT NULL,
  `folder` varchar(100) DEFAULT NULL,
  `file_size` int(11) NOT NULL DEFAULT 0,
  `mime_type` varchar(100) DEFAULT NULL,
  `uploaded_by` int(11) DEFAULT NULL,
  `uploaded_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `uploaded_by` (`uploaded_by`),
  KEY `idx_sf_folder` (`folder`),
  CONSTRAINT `stored_files_ibfk_1` FOREIGN KEY (`uploaded_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `stored_files`
--

LOCK TABLES `stored_files` WRITE;
/*!40000 ALTER TABLE `stored_files` DISABLE KEYS */;
/*!40000 ALTER TABLE `stored_files` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(100) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `full_name` varchar(200) DEFAULT NULL,
  `role` varchar(20) NOT NULL DEFAULT 'encoder',
  `barangay` varchar(200) DEFAULT NULL,
  `permissions` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`permissions`)),
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`),
  KEY `idx_users_role` (`role`),
  KEY `idx_users_barangay` (`barangay`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (1,'admin','$2y$10$lS/ypt1lraAqatclBLxup.zZZ86EsanIYLhRqtFqlyt0MGFRSzzrS','System Administrator','admin',NULL,NULL,1,'2026-06-30 01:26:44'),(2,'ana','$2y$10$u7hpLAk.VlZA/UnLu8J9B.Hdfr.4ZU6QAI38U7MXtSO8KvZorvjtu','anabele','midwife',NULL,'[\"validation\"]',1,'2026-07-06 15:39:55');
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vitamin_a_records`
--

DROP TABLE IF EXISTS `vitamin_a_records`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `vitamin_a_records` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `beneficiary_id` int(11) NOT NULL,
  `distribution_date` date NOT NULL,
  `round` varchar(20) NOT NULL,
  `year` int(11) NOT NULL,
  `dosage_iu` int(11) NOT NULL,
  `capsule_color` varchar(20) NOT NULL,
  `administered_by` varchar(200) DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `created_by` (`created_by`),
  KEY `idx_vita_bene` (`beneficiary_id`),
  KEY `idx_vita_round` (`round`,`year`),
  CONSTRAINT `vitamin_a_records_ibfk_1` FOREIGN KEY (`beneficiary_id`) REFERENCES `beneficiaries` (`id`) ON DELETE CASCADE,
  CONSTRAINT `vitamin_a_records_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vitamin_a_records`
--

LOCK TABLES `vitamin_a_records` WRITE;
/*!40000 ALTER TABLE `vitamin_a_records` DISABLE KEYS */;
INSERT INTO `vitamin_a_records` VALUES (1,1,'2026-07-01','1st',2026,100000,'Blue','System Administrator',1,'2026-07-01 22:43:40');
/*!40000 ALTER TABLE `vitamin_a_records` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `who_growth_standards`
--

DROP TABLE IF EXISTS `who_growth_standards`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `who_growth_standards` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `sex` varchar(10) NOT NULL,
  `age_months` int(11) NOT NULL,
  `measurement_type` varchar(10) NOT NULL,
  `l_value` decimal(10,6) NOT NULL,
  `m_value` decimal(10,6) NOT NULL,
  `s_value` decimal(10,6) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_who` (`sex`,`age_months`,`measurement_type`),
  KEY `idx_who_lookup` (`sex`,`measurement_type`,`age_months`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `who_growth_standards`
--

LOCK TABLES `who_growth_standards` WRITE;
/*!40000 ALTER TABLE `who_growth_standards` DISABLE KEYS */;
/*!40000 ALTER TABLE `who_growth_standards` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping routines for database 'nms'
--
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-07-11 10:56:57
