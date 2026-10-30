-- MySQL dump 10.13  Distrib 8.4.3, for Win64 (x86_64)
--
-- Host: 127.0.0.1    Database: projectsystem
-- ------------------------------------------------------
-- Server version	8.4.3

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!50503 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `access_events`
--

DROP TABLE IF EXISTS `access_events`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `access_events` (
  `event_id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `event_datetime` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `direction` enum('in','out') COLLATE utf8mb4_unicode_ci NOT NULL,
  `access_method` enum('rfid','qr','manual') COLLATE utf8mb4_unicode_ci NOT NULL,
  `gate_id` int unsigned NOT NULL,
  `reader_id` int unsigned DEFAULT NULL,
  `rfid_id` bigint unsigned DEFAULT NULL,
  `rfid_assignment_id` bigint unsigned DEFAULT NULL,
  `qr_pass_id` bigint unsigned DEFAULT NULL,
  `user_id` bigint unsigned DEFAULT NULL,
  `visitor_id` bigint unsigned DEFAULT NULL,
  `vehicle_id` bigint unsigned DEFAULT NULL,
  `auth_result` enum('granted','denied') COLLATE utf8mb4_unicode_ci NOT NULL,
  `denial_reason` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `raw_payload` json DEFAULT NULL,
  `processed_by_user_id` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`event_id`),
  KEY `access_events_event_datetime_index` (`event_datetime`),
  KEY `access_events_gate_id_foreign` (`gate_id`),
  KEY `access_events_reader_id_foreign` (`reader_id`),
  KEY `access_events_rfid_id_foreign` (`rfid_id`),
  KEY `access_events_rfid_assignment_id_foreign` (`rfid_assignment_id`),
  KEY `access_events_qr_pass_id_foreign` (`qr_pass_id`),
  KEY `access_events_user_id_foreign` (`user_id`),
  KEY `access_events_visitor_id_foreign` (`visitor_id`),
  KEY `access_events_vehicle_id_foreign` (`vehicle_id`),
  KEY `access_events_processed_by_user_id_foreign` (`processed_by_user_id`),
  CONSTRAINT `access_events_gate_id_foreign` FOREIGN KEY (`gate_id`) REFERENCES `access_gates` (`gate_id`),
  CONSTRAINT `access_events_processed_by_user_id_foreign` FOREIGN KEY (`processed_by_user_id`) REFERENCES `users` (`user_id`),
  CONSTRAINT `access_events_qr_pass_id_foreign` FOREIGN KEY (`qr_pass_id`) REFERENCES `qr_passes` (`qr_pass_id`),
  CONSTRAINT `access_events_reader_id_foreign` FOREIGN KEY (`reader_id`) REFERENCES `rfid_readers` (`reader_id`),
  CONSTRAINT `access_events_rfid_assignment_id_foreign` FOREIGN KEY (`rfid_assignment_id`) REFERENCES `rfid_assignments` (`assignment_id`),
  CONSTRAINT `access_events_rfid_id_foreign` FOREIGN KEY (`rfid_id`) REFERENCES `rfid_tags` (`rfid_id`),
  CONSTRAINT `access_events_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`),
  CONSTRAINT `access_events_vehicle_id_foreign` FOREIGN KEY (`vehicle_id`) REFERENCES `vehicles` (`vehicle_id`),
  CONSTRAINT `access_events_visitor_id_foreign` FOREIGN KEY (`visitor_id`) REFERENCES `visitors` (`visitor_id`)
) ENGINE=InnoDB AUTO_INCREMENT=454 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `access_events`
--

LOCK TABLES `access_events` WRITE;
/*!40000 ALTER TABLE `access_events` DISABLE KEYS */;
INSERT INTO `access_events` VALUES (370,'2026-10-13 07:30:00','in','rfid',8,NULL,4,4,NULL,36,NULL,55,'granted',NULL,'{\"method\": \"rfid\", \"credential\": \"SAMPLE-RFID-1005\"}',1,'2026-10-12 23:30:00','2026-10-12 23:30:00'),(371,'2026-10-13 07:45:00','in','rfid',8,NULL,1,1,NULL,32,NULL,51,'granted',NULL,'{\"method\": \"rfid\", \"credential\": \"SAMPLE-RFID-1001\"}',1,'2026-10-12 23:45:00','2026-10-12 23:45:00'),(372,'2026-10-13 08:10:00','in','rfid',8,NULL,3,3,NULL,35,NULL,76,'granted',NULL,'{\"method\": \"rfid\", \"credential\": \"SAMPLE-RFID-2004\"}',1,'2026-10-13 00:10:00','2026-10-13 00:10:00'),(373,'2026-10-13 15:00:00','in','qr',8,NULL,NULL,NULL,63,NULL,63,137,'granted',NULL,'{\"method\": \"qr\", \"credential\": \"808565a1-9ce6-4be0-b246-0f4d61649f56\"}',1,'2026-10-13 07:00:00','2026-10-13 07:00:00'),(374,'2026-10-13 16:40:00','out','rfid',8,NULL,3,3,NULL,35,NULL,76,'granted',NULL,'{\"method\": \"rfid\", \"credential\": \"SAMPLE-RFID-2004\"}',1,'2026-10-13 08:40:00','2026-10-13 08:40:00'),(375,'2026-10-13 17:10:00','out','rfid',8,NULL,1,1,NULL,32,NULL,51,'granted',NULL,'{\"method\": \"rfid\", \"credential\": \"SAMPLE-RFID-1001\"}',1,'2026-10-13 09:10:00','2026-10-13 09:10:00'),(376,'2026-10-14 00:15:35','out','rfid',8,NULL,4,4,NULL,36,NULL,55,'granted',NULL,'{\"method\": \"rfid\", \"credential\": \"SAMPLE-RFID-1005\"}',1,'2026-10-13 16:15:35','2026-10-13 16:15:35'),(377,'2026-10-14 00:31:11','in','rfid',8,NULL,1,1,NULL,32,NULL,51,'granted',NULL,'{\"method\": \"rfid\", \"credential\": \"SAMPLE-RFID-1001\"}',1,'2026-10-13 16:31:11','2026-10-13 16:31:11'),(378,'2026-10-14 01:02:23','in','qr',8,NULL,NULL,NULL,62,NULL,62,136,'granted',NULL,'{\"method\": \"qr\", \"credential\": \"c87f7994-809c-4c78-92e8-2d0fd1fea75b\"}',1,'2026-10-13 17:02:23','2026-10-13 17:02:23'),(379,'2026-10-14 01:33:35','in','manual',8,NULL,NULL,NULL,NULL,33,NULL,52,'denied','Vehicle authorization is pending review.','{\"method\": \"manual\", \"credential\": \"SMP 1002\"}',1,'2026-10-13 17:33:35','2026-10-13 17:33:35'),(380,'2026-10-14 01:49:11','out','rfid',8,NULL,1,1,NULL,32,NULL,51,'granted',NULL,'{\"method\": \"rfid\", \"credential\": \"SAMPLE-RFID-1001\"}',1,'2026-10-13 17:49:11','2026-10-13 17:49:11'),(381,'2026-10-14 02:04:47','in','rfid',8,NULL,2,2,NULL,34,NULL,53,'denied','Vehicle authorization is suspended.','{\"method\": \"rfid\", \"credential\": \"SAMPLE-RFID-1003\"}',1,'2026-10-13 18:04:47','2026-10-13 18:04:47'),(382,'2026-10-14 02:20:23','in','rfid',8,NULL,4,4,NULL,36,NULL,55,'granted',NULL,'{\"method\": \"rfid\", \"credential\": \"SAMPLE-RFID-1005\"}',1,'2026-10-13 18:20:23','2026-10-13 18:20:23'),(383,'2026-10-14 02:35:59','out','qr',8,NULL,NULL,NULL,62,NULL,62,136,'granted',NULL,'{\"method\": \"qr\", \"credential\": \"c87f7994-809c-4c78-92e8-2d0fd1fea75b\"}',1,'2026-10-13 18:35:59','2026-10-13 18:35:59'),(384,'2026-10-14 02:51:35','in','qr',8,NULL,NULL,NULL,61,NULL,61,135,'granted',NULL,'{\"method\": \"qr\", \"credential\": \"45109880-5ced-457e-82f2-4aafd13532c7\"}',1,'2026-10-13 18:51:35','2026-10-13 18:51:35'),(385,'2026-10-14 03:07:11','in','rfid',8,NULL,1,1,NULL,32,NULL,51,'granted',NULL,'{\"method\": \"rfid\", \"credential\": \"SAMPLE-RFID-1001\"}',1,'2026-10-13 19:07:11','2026-10-13 19:07:11'),(386,'2026-10-14 03:13:25','in','rfid',8,NULL,3,3,NULL,35,NULL,76,'granted',NULL,'{\"method\": \"rfid\", \"credential\": \"SAMPLE-RFID-2004\"}',1,'2026-10-13 19:13:25','2026-10-13 19:13:25'),(387,'2026-10-14 03:22:47','in','manual',8,NULL,NULL,NULL,NULL,35,NULL,54,'denied','Vehicle authorization was rejected.','{\"method\": \"manual\", \"credential\": \"SMP 1004\"}',1,'2026-10-13 19:22:47','2026-10-13 19:22:47'),(388,'2026-10-14 03:38:23','in','qr',8,NULL,NULL,NULL,64,NULL,64,138,'denied','Visitor QR pass has expired.','{\"method\": \"qr\", \"credential\": \"ee6fda2b-b8d5-4d63-9560-b98257904ec9\"}',1,'2026-10-13 19:38:23','2026-10-13 19:38:23'),(422,'2026-10-15 05:00:55','out','rfid',8,NULL,1,1,NULL,32,NULL,51,'granted',NULL,'{\"method\": \"rfid\", \"credential\": \"SAMPLE-RFID-1001\"}',1,'2026-10-14 21:00:55','2026-10-14 21:00:55'),(423,'2026-10-15 05:01:11','in','rfid',8,NULL,1,1,NULL,32,NULL,51,'granted',NULL,'{\"method\": \"rfid\", \"credential\": \"SAMPLE-RFID-1001\"}',1,'2026-10-14 21:01:11','2026-10-14 21:01:11'),(424,'2026-10-15 05:01:31','in','rfid',8,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'denied','Unknown RFID tag.','{\"method\": \"rfid\", \"credential\": \"NOT-A-REAL-TAG\"}',1,'2026-10-14 21:01:31','2026-10-14 21:01:31'),(425,'2026-10-15 05:01:39','out','rfid',8,NULL,3,3,NULL,35,NULL,76,'granted',NULL,'{\"method\": \"rfid\", \"credential\": \"SAMPLE-RFID-2004\"}',1,'2026-10-14 21:01:39','2026-10-14 21:01:39'),(426,'2026-10-15 05:01:47','in','rfid',8,NULL,3,3,NULL,35,NULL,76,'denied','Driver\'s license is waiting for staff verification.','{\"method\": \"rfid\", \"credential\": \"SAMPLE-RFID-2004\"}',1,'2026-10-14 21:01:47','2026-10-14 21:01:47'),(427,'2026-10-15 05:02:11','in','manual',8,NULL,NULL,NULL,NULL,35,NULL,76,'denied','Driver\'s license is waiting for staff verification.','{\"method\": \"manual\", \"credential\": \"SMP 2004\", \"staff_override\": true}',1,'2026-10-14 21:02:11','2026-10-14 21:02:11'),(428,'2026-10-15 05:02:19','in','rfid',8,NULL,2,2,NULL,34,NULL,53,'denied','Vehicle authorization is suspended.','{\"method\": \"rfid\", \"credential\": \"SAMPLE-RFID-1003\"}',1,'2026-10-14 21:02:19','2026-10-14 21:02:19'),(429,'2026-10-15 05:16:45','out','rfid',8,NULL,4,4,NULL,36,NULL,55,'granted',NULL,'{\"method\": \"rfid\", \"source\": \"simulator\", \"credential\": \"SAMPLE-RFID-1005\"}',1,'2026-10-14 21:16:45','2026-10-14 21:16:45'),(430,'2026-10-15 05:16:48','in','rfid',8,NULL,4,4,NULL,36,NULL,55,'granted',NULL,'{\"method\": \"rfid\", \"source\": \"simulator\", \"credential\": \"SAMPLE-RFID-1005\"}',1,'2026-10-14 21:16:48','2026-10-14 21:16:48'),(431,'2026-10-15 05:16:51','in','rfid',8,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'denied','Unknown RFID tag.','{\"method\": \"rfid\", \"source\": \"simulator\", \"credential\": \"NOT-A-REAL-TAG\"}',1,'2026-10-14 21:16:51','2026-10-14 21:16:51'),(432,'2026-10-15 05:21:43','in','qr',8,NULL,NULL,NULL,68,NULL,65,139,'granted',NULL,'{\"method\": \"qr\", \"source\": \"simulator\", \"credential\": \"0de7fd57-c280-4731-b96c-02f7439ab305\"}',1,'2026-10-14 21:21:43','2026-10-14 21:21:43'),(433,'2026-10-15 05:22:53','in','qr',8,NULL,NULL,NULL,69,NULL,65,141,'granted',NULL,'{\"method\": \"qr\", \"source\": \"staff_gate\", \"credential\": \"c6852210-1beb-472b-a293-6aea5827afe7\"}',1,'2026-10-14 21:22:53','2026-10-14 21:22:53'),(434,'2026-10-15 05:23:37','out','qr',8,NULL,NULL,NULL,69,NULL,65,141,'granted',NULL,'{\"method\": \"qr\", \"source\": \"staff_gate\", \"credential\": \"c6852210-1beb-472b-a293-6aea5827afe7\"}',1,'2026-10-14 21:23:37','2026-10-14 21:23:37'),(435,'2026-10-15 05:23:40','in','qr',8,NULL,NULL,NULL,69,NULL,65,141,'denied','Visitor QR pass has already been used.','{\"method\": \"qr\", \"source\": \"simulator\", \"credential\": \"c6852210-1beb-472b-a293-6aea5827afe7\"}',1,'2026-10-14 21:23:40','2026-10-14 21:23:40'),(436,'2026-10-15 05:23:46','out','qr',8,NULL,NULL,NULL,68,NULL,65,139,'granted',NULL,'{\"method\": \"qr\", \"source\": \"staff_gate\", \"credential\": \"0de7fd57-c280-4731-b96c-02f7439ab305\"}',1,'2026-10-14 21:23:46','2026-10-14 21:23:46'),(437,'2026-10-15 05:23:49','in','qr',8,NULL,NULL,NULL,68,NULL,65,139,'denied','Visitor QR pass has already been used.','{\"method\": \"qr\", \"source\": \"simulator\", \"credential\": \"0de7fd57-c280-4731-b96c-02f7439ab305\"}',1,'2026-10-14 21:23:49','2026-10-14 21:23:49'),(438,'2026-10-15 05:25:38','in','qr',8,NULL,NULL,NULL,70,NULL,65,142,'granted',NULL,'{\"method\": \"qr\", \"source\": \"staff_gate\", \"credential\": \"6a2a3317-5adb-4178-ae5a-73978244459d\"}',1,'2026-10-14 21:25:38','2026-10-14 21:25:38'),(439,'2026-10-15 05:26:20','out','qr',8,NULL,NULL,NULL,70,NULL,65,142,'granted',NULL,'{\"method\": \"qr\", \"source\": \"staff_gate\", \"credential\": \"6a2a3317-5adb-4178-ae5a-73978244459d\"}',1,'2026-10-14 21:26:20','2026-10-14 21:26:20'),(440,'2026-10-15 05:26:24','in','qr',8,NULL,NULL,NULL,70,NULL,65,142,'denied','Visitor QR pass was revoked.','{\"method\": \"qr\", \"source\": \"simulator\", \"credential\": \"6a2a3317-5adb-4178-ae5a-73978244459d\"}',1,'2026-10-14 21:26:24','2026-10-14 21:26:24'),(441,'2026-10-15 08:28:50','in','qr',8,NULL,NULL,NULL,71,NULL,67,144,'granted',NULL,'{\"method\": \"qr\", \"source\": \"simulator\", \"credential\": \"645c55b7-df4d-48e6-b843-89b5d8a26c0d\"}',1,'2026-10-15 00:28:50','2026-10-15 00:28:50'),(442,'2026-10-15 08:29:41','out','qr',8,NULL,NULL,NULL,71,NULL,67,144,'granted',NULL,'{\"method\": \"qr\", \"source\": \"simulator\", \"credential\": \"645c55b7-df4d-48e6-b843-89b5d8a26c0d\"}',1,'2026-10-15 00:29:41','2026-10-15 00:29:41'),(443,'2026-10-15 08:29:51','in','qr',8,NULL,NULL,NULL,71,NULL,67,144,'denied','Visitor QR pass has already been used.','{\"method\": \"qr\", \"source\": \"simulator\", \"credential\": \"645c55b7-df4d-48e6-b843-89b5d8a26c0d\"}',1,'2026-10-15 00:29:51','2026-10-15 00:29:51'),(444,'2026-10-15 11:04:00','out','rfid',8,NULL,4,4,NULL,36,NULL,55,'granted',NULL,'{\"method\": \"rfid\", \"source\": \"simulator\", \"credential\": \"SAMPLE-RFID-1005\"}',1,'2026-10-15 03:04:00','2026-10-15 03:04:00'),(445,'2026-10-15 11:04:25','in','rfid',8,NULL,4,4,NULL,36,NULL,55,'granted',NULL,'{\"method\": \"rfid\", \"source\": \"simulator\", \"credential\": \"SAMPLE-RFID-1005\"}',1,'2026-10-15 03:04:25','2026-10-15 03:04:25'),(446,'2026-10-15 11:07:25','out','rfid',8,NULL,4,4,NULL,36,NULL,55,'granted',NULL,'{\"method\": \"rfid\", \"source\": \"simulator\", \"credential\": \"SAMPLE-RFID-1005\"}',1,'2026-10-15 03:07:25','2026-10-15 03:07:25'),(447,'2026-10-15 11:17:27','in','rfid',8,NULL,4,4,NULL,36,NULL,55,'granted',NULL,'{\"method\": \"rfid\", \"source\": \"simulator\", \"credential\": \"SAMPLE-RFID-1005\"}',1,'2026-10-15 03:17:27','2026-10-15 03:17:27'),(448,'2026-10-15 11:21:59','out','rfid',8,NULL,4,4,NULL,36,NULL,55,'granted',NULL,'{\"method\": \"rfid\", \"source\": \"simulator\", \"credential\": \"SAMPLE-RFID-1005\"}',1,'2026-10-15 03:21:59','2026-10-15 03:21:59'),(449,'2026-10-15 11:22:24','in','rfid',8,NULL,4,4,NULL,36,NULL,55,'granted',NULL,'{\"method\": \"rfid\", \"source\": \"simulator\", \"credential\": \"SAMPLE-RFID-1005\"}',1,'2026-10-15 03:22:24','2026-10-15 03:22:24'),(450,'2026-10-15 11:35:22','in','qr',8,NULL,NULL,NULL,72,NULL,69,147,'granted',NULL,'{\"method\": \"qr\", \"source\": \"simulator\", \"credential\": \"ac40cdae-3d16-4396-a14b-1a3a004adf9f\"}',1,'2026-10-15 03:35:22','2026-10-15 03:35:22'),(451,'2026-10-15 11:35:23','out','qr',8,NULL,NULL,NULL,72,NULL,69,147,'granted',NULL,'{\"method\": \"qr\", \"source\": \"simulator\", \"credential\": \"ac40cdae-3d16-4396-a14b-1a3a004adf9f\"}',1,'2026-10-15 03:35:23','2026-10-15 03:35:23'),(452,'2026-10-15 11:35:24','in','qr',8,NULL,NULL,NULL,72,NULL,69,147,'denied','Visitor QR pass has already been used.','{\"method\": \"qr\", \"source\": \"simulator\", \"credential\": \"ac40cdae-3d16-4396-a14b-1a3a004adf9f\"}',1,'2026-10-15 03:35:24','2026-10-15 03:35:24'),(453,'2026-10-15 17:15:40','out','rfid',8,NULL,1,1,NULL,32,NULL,51,'granted',NULL,'{\"method\": \"rfid\", \"source\": \"simulator\", \"credential\": \"SAMPLE-RFID-1001\"}',1,'2026-10-15 09:15:40','2026-10-15 09:15:40');
/*!40000 ALTER TABLE `access_events` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `access_gates`
--

DROP TABLE IF EXISTS `access_gates`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `access_gates` (
  `gate_id` int unsigned NOT NULL AUTO_INCREMENT,
  `parking_area_id` int unsigned DEFAULT NULL,
  `gate_code` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL,
  `gate_name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `gate_type` enum('entry','exit','both') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'both',
  `gate_status` enum('online','offline','maintenance') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'online',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`gate_id`),
  UNIQUE KEY `access_gates_gate_code_unique` (`gate_code`),
  KEY `access_gates_parking_area_id_foreign` (`parking_area_id`),
  CONSTRAINT `access_gates_parking_area_id_foreign` FOREIGN KEY (`parking_area_id`) REFERENCES `parking_areas` (`parking_area_id`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `access_gates`
--

LOCK TABLES `access_gates` WRITE;
/*!40000 ALTER TABLE `access_gates` DISABLE KEYS */;
INSERT INTO `access_gates` VALUES (8,NULL,'MAIN-GATE','Main Entrance / Exit','both','online',NULL,'2026-10-14 16:56:54');
/*!40000 ALTER TABLE `access_gates` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `audit_logs`
--

DROP TABLE IF EXISTS `audit_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `audit_logs` (
  `log_id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned DEFAULT NULL,
  `actor_label` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `action` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `auditable_type` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `auditable_id` bigint unsigned DEFAULT NULL,
  `old_values` json DEFAULT NULL,
  `new_values` json DEFAULT NULL,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`log_id`),
  KEY `audit_logs_auditable_type_auditable_id_index` (`auditable_type`,`auditable_id`),
  KEY `audit_logs_user_id_foreign` (`user_id`),
  CONSTRAINT `audit_logs_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=276 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `audit_logs`
--

LOCK TABLES `audit_logs` WRITE;
/*!40000 ALTER TABLE `audit_logs` DISABLE KEYS */;
INSERT INTO `audit_logs` VALUES (167,NULL,'seeder:sample-data','parking_slot_maintenance','parking_slot',50,'{\"slot_status\": \"available\"}','{\"reason\": \"SAMPLE: line repainting (test data)\", \"slot_status\": \"maintenance\"}',NULL,NULL,'2026-10-14 04:02:01'),(168,NULL,'seeder:sample-data','parking_slot_maintenance','parking_slot',135,'{\"slot_status\": \"available\"}','{\"reason\": \"SAMPLE: line repainting (test data)\", \"slot_status\": \"maintenance\"}',NULL,NULL,'2026-10-14 04:02:01'),(246,NULL,'system:license-check','license_auto_pending_review','App\\Models\\DriverLicense',44,'{\"expiry_date\": \"2030-03-15\", \"reviewed_by\": null, \"license_number\": \"Z01-26-000101\", \"original_file_name\": \"sample-stu-001-sample-license.png\", \"verification_reason\": null, \"verification_source\": \"automatic\", \"verification_status\": \"processing\"}','{\"expiry_date\": \"2030-03-15\", \"reviewed_by\": null, \"license_number\": \"Z01-26-000101\", \"original_file_name\": \"sample-stu-001-sample-license.png\", \"verification_reason\": null, \"verification_source\": \"automatic\", \"verification_status\": \"pending_review\"}',NULL,NULL,'2026-10-14 05:12:00'),(247,NULL,'seeder:sample-data (as SEC-DEMO-001)','license_review_verified','App\\Models\\DriverLicense',44,'{\"reviewed_by\": null, \"verification_reason\": null, \"verification_source\": \"automatic\", \"verification_status\": \"pending_review\"}','{\"reviewed_by\": 1, \"verification_reason\": null, \"verification_source\": \"staff\", \"verification_status\": \"verified\"}',NULL,NULL,'2026-10-14 05:12:00'),(248,NULL,'system:license-check','license_auto_pending_review','App\\Models\\DriverLicense',45,'{\"expiry_date\": \"2027-07-02\", \"reviewed_by\": null, \"license_number\": \"Z02-26-000202\", \"original_file_name\": \"sample-stu-002-sample-license.png\", \"verification_reason\": null, \"verification_source\": \"automatic\", \"verification_status\": \"processing\"}','{\"expiry_date\": \"2027-07-02\", \"reviewed_by\": null, \"license_number\": \"Z02-26-000202\", \"original_file_name\": \"sample-stu-002-sample-license.png\", \"verification_reason\": null, \"verification_source\": \"automatic\", \"verification_status\": \"pending_review\"}',NULL,NULL,'2026-10-14 05:12:01'),(249,NULL,'system:license-check','license_auto_needs_correction','App\\Models\\DriverLicense',46,'{\"expiry_date\": \"2031-11-20\", \"reviewed_by\": null, \"license_number\": \"Z03-26-000303\", \"original_file_name\": \"sample-fac-001-sample-license.png\", \"verification_reason\": null, \"verification_source\": \"automatic\", \"verification_status\": \"processing\"}','{\"expiry_date\": \"2031-11-20\", \"reviewed_by\": null, \"license_number\": \"Z03-26-000303\", \"original_file_name\": \"sample-fac-001-sample-license.png\", \"verification_reason\": \"The license number on the document (Z03-26-000330) does not match the number you entered (Z03-26-000303). Check the number or upload the correct license.\", \"verification_source\": \"automatic\", \"verification_status\": \"needs_correction\"}',NULL,NULL,'2026-10-14 05:12:02'),(250,NULL,'system:license-check','license_auto_pending_review','App\\Models\\DriverLicense',47,'{\"expiry_date\": \"2029-01-08\", \"reviewed_by\": null, \"license_number\": \"Z04-26-000404\", \"original_file_name\": \"sample-fac-002-sample-license.png\", \"verification_reason\": null, \"verification_source\": \"automatic\", \"verification_status\": \"processing\"}','{\"expiry_date\": \"2029-01-08\", \"reviewed_by\": null, \"license_number\": \"Z04-26-000404\", \"original_file_name\": \"sample-fac-002-sample-license.png\", \"verification_reason\": null, \"verification_source\": \"automatic\", \"verification_status\": \"pending_review\"}',NULL,NULL,'2026-10-14 05:12:03'),(251,NULL,'system:license-check','license_auto_pending_review','App\\Models\\DriverLicense',48,'{\"expiry_date\": \"2036-05-27\", \"reviewed_by\": null, \"license_number\": \"Z05-26-000505\", \"original_file_name\": \"sample-emp-001-sample-license.png\", \"verification_reason\": null, \"verification_source\": \"automatic\", \"verification_status\": \"processing\"}','{\"expiry_date\": \"2036-05-27\", \"reviewed_by\": null, \"license_number\": \"Z05-26-000505\", \"original_file_name\": \"sample-emp-001-sample-license.png\", \"verification_reason\": null, \"verification_source\": \"automatic\", \"verification_status\": \"pending_review\"}',NULL,NULL,'2026-10-14 05:12:04'),(252,NULL,'seeder:sample-data (as SEC-DEMO-001)','license_review_verified','App\\Models\\DriverLicense',48,'{\"reviewed_by\": null, \"verification_reason\": null, \"verification_source\": \"automatic\", \"verification_status\": \"pending_review\"}','{\"reviewed_by\": 1, \"verification_reason\": null, \"verification_source\": \"staff\", \"verification_status\": \"verified\"}',NULL,NULL,'2026-10-14 05:12:04'),(253,1,'SEC-DEMO-001','parking_slot_maintenance','parking_slot',5,'{\"slot_status\": \"available\"}','{\"reason\": \"Layout verification test\", \"slot_status\": \"maintenance\"}','127.0.0.1','Mozilla/5.0 (Windows NT; Windows NT 10.0; en-PH) WindowsPowerShell/5.1.26100.9444','2026-10-15 02:51:27'),(254,1,'SEC-DEMO-001','parking_slot_available','parking_slot',5,'{\"slot_status\": \"maintenance\"}','{\"reason\": \"Layout verification test finished\", \"slot_status\": \"available\"}','127.0.0.1','Mozilla/5.0 (Windows NT; Windows NT 10.0; en-PH) WindowsPowerShell/5.1.26100.9444','2026-10-15 02:52:06'),(255,1,'SEC-DEMO-001','visitor_request_created','visitor_request',1,NULL,'{\"plate_number\": \"DEMO 4821\", \"request_status\": \"pending\"}','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/155.0.0.0 Safari/537.36 Edg/155.0.0.0','2026-10-15 05:17:59'),(256,1,'SEC-DEMO-001','visitor_request_approved','visitor_request',1,'{\"request_status\": \"pending\"}','{\"qr_pass_id\": 68, \"request_status\": \"approved\"}','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/155.0.0.0 Safari/537.36 Edg/155.0.0.0','2026-10-15 05:20:24'),(257,1,'SEC-DEMO-001','visitor_request_created','visitor_request',2,NULL,'{\"plate_number\": \"DEMO 4822\", \"request_status\": \"pending\"}','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/155.0.0.0 Safari/537.36 Edg/155.0.0.0','2026-10-15 05:22:32'),(258,1,'SEC-DEMO-001','visitor_request_rejected','visitor_request',2,'{\"request_status\": \"pending\"}','{\"remarks\": \"No appointment on record\", \"request_status\": \"rejected\"}','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/155.0.0.0 Safari/537.36 Edg/155.0.0.0','2026-10-15 05:22:36'),(259,1,'SEC-DEMO-001','visitor_request_created','visitor_request',3,NULL,'{\"plate_number\": \"DEMO 4823\", \"request_status\": \"pending\"}','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/155.0.0.0 Safari/537.36 Edg/155.0.0.0','2026-10-15 05:22:41'),(260,1,'SEC-DEMO-001','visitor_request_approved','visitor_request',3,'{\"request_status\": \"pending\"}','{\"qr_pass_id\": 69, \"request_status\": \"approved\"}','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/155.0.0.0 Safari/537.36 Edg/155.0.0.0','2026-10-15 05:22:45'),(261,1,'SEC-DEMO-001','visitor_request_created','visitor_request',4,NULL,'{\"plate_number\": \"DEMO 4824\", \"request_status\": \"pending\"}','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/155.0.0.0 Safari/537.36 Edg/155.0.0.0','2026-10-15 05:25:27'),(262,1,'SEC-DEMO-001','visitor_request_approved','visitor_request',4,'{\"request_status\": \"pending\"}','{\"qr_pass_id\": 70, \"request_status\": \"approved\"}','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/155.0.0.0 Safari/537.36 Edg/155.0.0.0','2026-10-15 05:25:31'),(263,1,'SEC-DEMO-001','qr_pass_revoked','qr_pass',70,'{\"pass_status\": \"active\"}','{\"reason\": \"Interview cancelled\", \"pass_status\": \"revoked\"}','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/155.0.0.0 Safari/537.36 Edg/155.0.0.0','2026-10-15 05:25:47'),(264,47,'GST-DEMO-001','visitor_request_created','visitor_request',5,NULL,'{\"source\": \"demo_seeder\", \"plate_number\": \"GUEST 001\", \"request_status\": \"pending\"}','127.0.0.1','Symfony','2026-10-15 08:15:48'),(265,48,'GST-M9SC2UDB','visitor_request_created','visitor_request',6,NULL,'{\"source\": \"guest_account\", \"plate_number\": \"ROSA 2026\", \"request_status\": \"pending\"}','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/155.0.0.0 Safari/537.36 Edg/155.0.0.0','2026-10-15 08:24:58'),(266,48,'GST-M9SC2UDB','visitor_request_created','visitor_request',7,NULL,'{\"source\": \"guest_account\", \"plate_number\": \"ROSA 2027\", \"request_status\": \"pending\"}','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/155.0.0.0 Safari/537.36 Edg/155.0.0.0','2026-10-15 08:25:10'),(267,46,'ADM-DEMO-001','visitor_request_approved','visitor_request',6,'{\"request_status\": \"pending\"}','{\"qr_pass_id\": 71, \"request_status\": \"approved\"}','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/155.0.0.0 Safari/537.36 Edg/155.0.0.0','2026-10-15 08:27:02'),(268,46,'ADM-DEMO-001','visitor_request_rejected','visitor_request',7,'{\"request_status\": \"pending\"}','{\"remarks\": \"The Registrar is closed for a holiday\", \"request_status\": \"rejected\"}','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/155.0.0.0 Safari/537.36 Edg/155.0.0.0','2026-10-15 08:27:06'),(269,49,'GST-20UF3BER','visitor_request_created','visitor_request',8,NULL,'{\"source\": \"guest_account\", \"plate_number\": \"CAR 123\", \"request_status\": \"pending\"}','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Code/1.141.2 Chrome/150.0.7871.250 Electron/43.7.7 Safari/537.36','2026-10-15 10:15:56'),(270,50,'GST-BW3KZLQ4','visitor_request_created','visitor_request',9,NULL,'{\"source\": \"guest_account\", \"plate_number\": \"LARA 3101\", \"request_status\": \"pending\"}','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/155.0.0.0 Safari/537.36 Edg/155.0.0.0','2026-10-15 11:33:30'),(271,46,'ADM-DEMO-001','visitor_request_approved','visitor_request',9,'{\"request_status\": \"pending\"}','{\"qr_pass_id\": 72, \"request_status\": \"approved\"}','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/155.0.0.0 Safari/537.36 Edg/155.0.0.0','2026-10-15 11:33:51'),(272,1,'SEC-DEMO-001','suspended','App\\Models\\VehicleAuthorization',35,'{\"remarks\": null, \"reviewed_at\": \"2026-09-03 14:00:00\", \"reviewed_by\": 1, \"authorization_status\": \"approved\"}','{\"remarks\": \"wrong parking\", \"reviewed_at\": \"2026-10-15T11:49:11.287088Z\", \"reviewed_by\": 1, \"authorization_status\": \"suspended\"}','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Code/1.141.2 Chrome/150.0.7871.250 Electron/43.7.7 Safari/537.36','2026-10-15 11:49:11'),(273,1,'SEC-DEMO-001','violation_acknowledged','parking_violation',26,'{\"violation_status\": \"open\"}','{\"remarks\": null, \"violation_status\": \"under_review\"}','127.0.0.1','node','2026-10-15 17:19:01'),(274,1,'SEC-DEMO-001','violation_resolved','parking_violation',26,'{\"violation_status\": \"under_review\"}','{\"remarks\": \"QA verification complete\", \"violation_status\": \"resolved\"}','127.0.0.1','node','2026-10-15 17:19:02'),(275,1,'SEC-DEMO-001','violation_dismissed','parking_violation',27,'{\"violation_status\": \"open\"}','{\"remarks\": \"QA test record\", \"violation_status\": \"dismissed\"}','127.0.0.1','node','2026-10-15 17:19:06');
/*!40000 ALTER TABLE `audit_logs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `departments`
--

DROP TABLE IF EXISTS `departments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `departments` (
  `department_id` int unsigned NOT NULL AUTO_INCREMENT,
  `department_code` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL,
  `department_name` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`department_id`),
  UNIQUE KEY `departments_department_code_unique` (`department_code`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `departments`
--

LOCK TABLES `departments` WRITE;
/*!40000 ALTER TABLE `departments` DISABLE KEYS */;
INSERT INTO `departments` VALUES (1,'COE','College of Engineering',1),(2,'COS','College of Science',1),(3,'COB','College of Business',1),(4,'COA','College of Arts',1),(5,'CON','College of Nursing',1),(6,'REG','Registrar Office',1),(7,'HR','HR Department',1),(8,'IT','IT Department',1),(9,'SEC','Security Office',1);
/*!40000 ALTER TABLE `departments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `driver_licenses`
--

DROP TABLE IF EXISTS `driver_licenses`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `driver_licenses` (
  `license_id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `driver_id` bigint unsigned NOT NULL,
  `license_number` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `license_type` enum('student_permit','non_professional','professional','international') COLLATE utf8mb4_unicode_ci NOT NULL,
  `restriction_codes` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `issue_date` date DEFAULT NULL,
  `expiry_date` date DEFAULT NULL,
  `issuing_agency` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `license_image_path` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `original_file_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `license_status` enum('valid','expired','suspended','revoked') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'valid',
  `verification_status` enum('processing','needs_correction','pending_review','verified') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending_review',
  `verification_source` enum('automatic','staff') COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `verification_reason` text COLLATE utf8mb4_unicode_ci,
  `ocr_result` json DEFAULT NULL,
  `verification_run_id` char(36) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `submitted_at` datetime DEFAULT NULL,
  `checked_at` datetime DEFAULT NULL,
  `reviewed_by` bigint unsigned DEFAULT NULL,
  `reviewed_at` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `current_flag` tinyint GENERATED ALWAYS AS (if((`license_status` = _utf8mb4'valid'),1,NULL)) STORED,
  PRIMARY KEY (`license_id`),
  UNIQUE KEY `driver_licenses_license_number_unique` (`license_number`),
  UNIQUE KEY `uq_driver_one_current_license` (`driver_id`,`current_flag`),
  KEY `driver_licenses_verification_status_index` (`verification_status`),
  KEY `driver_licenses_reviewed_by_foreign` (`reviewed_by`),
  CONSTRAINT `driver_licenses_driver_id_foreign` FOREIGN KEY (`driver_id`) REFERENCES `drivers` (`driver_id`) ON DELETE CASCADE,
  CONSTRAINT `driver_licenses_reviewed_by_foreign` FOREIGN KEY (`reviewed_by`) REFERENCES `users` (`user_id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=57 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `driver_licenses`
--

LOCK TABLES `driver_licenses` WRITE;
/*!40000 ALTER TABLE `driver_licenses` DISABLE KEYS */;
INSERT INTO `driver_licenses` (`license_id`, `driver_id`, `license_number`, `license_type`, `restriction_codes`, `issue_date`, `expiry_date`, `issuing_agency`, `license_image_path`, `original_file_name`, `license_status`, `verification_status`, `verification_source`, `verification_reason`, `ocr_result`, `verification_run_id`, `submitted_at`, `checked_at`, `reviewed_by`, `reviewed_at`, `created_at`, `updated_at`) VALUES (44,44,'Z01-26-000101','non_professional',NULL,NULL,'2030-03-15','SAMPLE TEST DATA','driver-licenses/sample/sample-stu-001.png','sample-stu-001-sample-license.png','valid','verified','staff',NULL,'{\"text\": \"SAMPLE DRIVER LICENSE - TEST DATA ONLY\\nNOT A GOVERNMENT-ISSUED DOCUMENT. FOR PASS SYSTEM TESTING.\\nLast Name, First Name\\nVILLAMOR, ANDREA\\nLicense No.\\nZ01-26-000101\\nExpiration Date\\n2030/03/15\\nDate of Birth\\n2004/03/15\\nLicense Type\\nNON-PROFESSIONAL\\nSAMPLE * TEST DATAONLY * NOT VALID FOR ANY PURPOSE\", \"notes\": [\"Preliminary validation only. Authenticity has not been confirmed: no official LTO verification service is integrated, so PASS staff must review the document.\"], \"width\": 1600, \"checks\": {\"name\": {\"detail\": \"The registered first and last name appear on the document.\", \"result\": \"pass\"}, \"expiry_date\": {\"detail\": \"The expiration date on the document matches the submitted date.\", \"result\": \"pass\"}, \"document_type\": {\"detail\": \"Matched 3 LTO license marker group(s).\", \"result\": \"pass\"}, \"image_quality\": {\"detail\": \"Resolution 1600x1000, sharpness 1463.9.\", \"result\": \"pass\"}, \"license_number\": {\"detail\": \"The license number on the document matches the submitted number.\", \"result\": \"pass\"}, \"text_extraction\": {\"detail\": \"236 readable characters, mean confidence 95%.\", \"result\": \"pass\"}}, \"engine\": \"tesseract\", \"height\": 1000, \"reasons\": [], \"decision\": \"pending_review\", \"extracted\": {\"dates\": [\"2030-03-15\", \"2004-03-15\"], \"expiry_date\": \"2030-03-15\", \"license_numbers\": [\"Z01-26-000101\"], \"name_tokens_found\": [\"ANDREA\", \"VILLAMOR\"]}, \"sharpness\": 1463.9, \"confidence\": 95, \"preliminary\": true, \"document_score\": 3, \"official_verification\": \"unavailable\"}','1dc5f5a5-5e05-450a-96e8-bddfd3156e44','2026-09-24 05:11:59','2026-10-14 05:12:00',1,'2026-09-25 05:11:59','2026-10-13 17:16:34','2026-10-13 21:12:00'),(45,45,'Z02-26-000202','student_permit',NULL,NULL,'2027-07-02','SAMPLE TEST DATA','driver-licenses/sample/sample-stu-002.png','sample-stu-002-sample-license.png','valid','pending_review','automatic',NULL,'{\"text\": \"SAMPLE DRIVER LICENSE - TEST DATA ONLY\\nNOT A GOVERNMENT-ISSUED DOCUMENT. FOR PASS SYSTEM TESTING.\\nLast Name, First Name\\nCASTILLO, BRYAN\\nLicense No.\\nZ02-26-000202\\nExpiration Date\\n2027/07/02\\nDate of Birth\\n2005/07/02\\nLicense Type\\nSTUDENT-PERMIT\\nSAMPLE * TEST DATAONLY * NOT VALID FOR ANY PURPOSE\", \"notes\": [\"Preliminary validation only. Authenticity has not been confirmed: no official LTO verification service is integrated, so PASS staff must review the document.\"], \"width\": 1600, \"checks\": {\"name\": {\"detail\": \"The registered first and last name appear on the document.\", \"result\": \"pass\"}, \"expiry_date\": {\"detail\": \"The expiration date on the document matches the submitted date.\", \"result\": \"pass\"}, \"document_type\": {\"detail\": \"Matched 3 LTO license marker group(s).\", \"result\": \"pass\"}, \"image_quality\": {\"detail\": \"Resolution 1600x1000, sharpness 1433.6.\", \"result\": \"pass\"}, \"license_number\": {\"detail\": \"The license number on the document matches the submitted number.\", \"result\": \"pass\"}, \"text_extraction\": {\"detail\": \"233 readable characters, mean confidence 95%.\", \"result\": \"pass\"}}, \"engine\": \"tesseract\", \"height\": 1000, \"reasons\": [], \"decision\": \"pending_review\", \"extracted\": {\"dates\": [\"2027-07-02\", \"2005-07-02\"], \"expiry_date\": \"2027-07-02\", \"license_numbers\": [\"Z02-26-000202\"], \"name_tokens_found\": [\"BRYAN\", \"CASTILLO\"]}, \"sharpness\": 1433.6, \"confidence\": 95, \"preliminary\": true, \"document_score\": 3, \"official_verification\": \"unavailable\"}','1f77ff39-6f55-4a1d-99ed-94ccc05540d1','2026-10-13 05:12:00','2026-10-14 05:12:01',NULL,NULL,'2026-10-13 17:16:37','2026-10-13 21:12:01'),(46,46,'Z03-26-000303','professional',NULL,NULL,'2031-11-20','SAMPLE TEST DATA','driver-licenses/sample/sample-fac-001.png','sample-fac-001-sample-license.png','valid','needs_correction','automatic','The license number on the document (Z03-26-000330) does not match the number you entered (Z03-26-000303). Check the number or upload the correct license.','{\"text\": \"SAMPLE DRIVER LICENSE - TEST DATA ONLY\\nNOT A GOVERNMENT-ISSUED DOCUMENT. FOR PASS SYSTEM TESTING.\\nLast Name, First Name\\nNAVARRO, CELIA\\nLicense No.\\nZ03-26-000330\\nExpiration Date\\n2031/11/20\\nDate of Birth\\n1986/11/20\\nLicense Type\\nPROFESSIONAL\\nSAMPLE * TEST DATAONLY * NOT VALID FOR ANY PURPOSE\", \"notes\": [\"Preliminary validation only. Authenticity has not been confirmed: no official LTO verification service is integrated, so PASS staff must review the document.\"], \"width\": 1600, \"checks\": {\"name\": {\"detail\": \"The registered first and last name appear on the document.\", \"result\": \"pass\"}, \"expiry_date\": {\"detail\": \"The expiration date on the document matches the submitted date.\", \"result\": \"pass\"}, \"document_type\": {\"detail\": \"Matched 3 LTO license marker group(s).\", \"result\": \"pass\"}, \"image_quality\": {\"detail\": \"Resolution 1600x1000, sharpness 1403.1.\", \"result\": \"pass\"}, \"license_number\": {\"detail\": \"Document shows Z03-26-000330.\", \"result\": \"fail\"}, \"text_extraction\": {\"detail\": \"231 readable characters, mean confidence 95.2%.\", \"result\": \"pass\"}}, \"engine\": \"tesseract\", \"height\": 1000, \"reasons\": [\"The license number on the document (Z03-26-000330) does not match the number you entered (Z03-26-000303). Check the number or upload the correct license.\"], \"decision\": \"needs_correction\", \"extracted\": {\"dates\": [\"2031-11-20\", \"1986-11-20\"], \"expiry_date\": \"2031-11-20\", \"license_numbers\": [\"Z03-26-000330\"], \"name_tokens_found\": [\"CELIA\", \"NAVARRO\"]}, \"sharpness\": 1403.1, \"confidence\": 95.2, \"preliminary\": true, \"document_score\": 3, \"official_verification\": \"unavailable\"}','c6370b24-397c-4d47-a233-ed519486fd23','2026-10-11 05:12:01','2026-10-14 05:12:02',NULL,NULL,'2026-10-13 17:16:39','2026-10-13 21:12:02'),(47,47,'Z04-26-000404','non_professional',NULL,NULL,'2029-01-08','SAMPLE TEST DATA','driver-licenses/sample/sample-fac-002.png','sample-fac-002-sample-license.png','valid','pending_review','automatic',NULL,'{\"text\": \"SAMPLE DRIVER LICENSE - TEST DATA ONLY\\nNOT A GOVERNMENT-ISSUED DOCUMENT. FOR PASS SYSTEM TESTING.\\nLast Name, First Name\\nAQUINO, DENNIS\\nLicense No.\\nZ04-26-000404\\nExpiration Date\\n2029/01/08\\nDate of Birth\\n1990/01/08\\nLicense Type\\nNON-PROFESSIONAL\\nSAMPLE * TEST DATAONLY * NOT VALID FOR ANY PURPOSE\", \"notes\": [\"Preliminary validation only. Authenticity has not been confirmed: no official LTO verification service is integrated, so PASS staff must review the document.\"], \"width\": 1600, \"checks\": {\"name\": {\"detail\": \"The registered first and last name appear on the document.\", \"result\": \"pass\"}, \"expiry_date\": {\"detail\": \"The expiration date on the document matches the submitted date.\", \"result\": \"pass\"}, \"document_type\": {\"detail\": \"Matched 3 LTO license marker group(s).\", \"result\": \"pass\"}, \"image_quality\": {\"detail\": \"Resolution 1600x1000, sharpness 1458.6.\", \"result\": \"pass\"}, \"license_number\": {\"detail\": \"The license number on the document matches the submitted number.\", \"result\": \"pass\"}, \"text_extraction\": {\"detail\": \"234 readable characters, mean confidence 95%.\", \"result\": \"pass\"}}, \"engine\": \"tesseract\", \"height\": 1000, \"reasons\": [], \"decision\": \"pending_review\", \"extracted\": {\"dates\": [\"2029-01-08\", \"1990-01-08\"], \"expiry_date\": \"2029-01-08\", \"license_numbers\": [\"Z04-26-000404\"], \"name_tokens_found\": [\"DENNIS\", \"AQUINO\"]}, \"sharpness\": 1458.6, \"confidence\": 95, \"preliminary\": true, \"document_score\": 3, \"official_verification\": \"unavailable\"}','443ec318-1538-4773-9c86-2680669378a4','2026-10-13 05:12:02','2026-10-14 05:12:03',NULL,NULL,'2026-10-13 17:16:41','2026-10-13 21:12:03'),(48,48,'Z05-26-000505','non_professional',NULL,NULL,'2036-05-27','SAMPLE TEST DATA','driver-licenses/sample/sample-emp-001.png','sample-emp-001-sample-license.png','valid','verified','staff',NULL,'{\"text\": \"SAMPLE DRIVER LICENSE - TEST DATA ONLY\\nNOT A GOVERNMENT-ISSUED DOCUMENT. FOR PASS SYSTEM TESTING.\\nLast Name, First Name\\nBAUTISTA, ELENA\\nLicense No.\\nZ05-26-000505\\nExpiration Date\\n2036/05/27\\nDate of Birth\\n1993/05/27\\nLicense Type\\nNON-PROFESSIONAL\\nSAMPLE * TEST DATAONLY * NOT VALID FOR ANY PURPOSE\", \"notes\": [\"Preliminary validation only. Authenticity has not been confirmed: no official LTO verification service is integrated, so PASS staff must review the document.\"], \"width\": 1600, \"checks\": {\"name\": {\"detail\": \"The registered first and last name appear on the document.\", \"result\": \"pass\"}, \"expiry_date\": {\"detail\": \"The expiration date on the document matches the submitted date.\", \"result\": \"pass\"}, \"document_type\": {\"detail\": \"Matched 3 LTO license marker group(s).\", \"result\": \"pass\"}, \"image_quality\": {\"detail\": \"Resolution 1600x1000, sharpness 1464.8.\", \"result\": \"pass\"}, \"license_number\": {\"detail\": \"The license number on the document matches the submitted number.\", \"result\": \"pass\"}, \"text_extraction\": {\"detail\": \"235 readable characters, mean confidence 95.1%.\", \"result\": \"pass\"}}, \"engine\": \"tesseract\", \"height\": 1000, \"reasons\": [], \"decision\": \"pending_review\", \"extracted\": {\"dates\": [\"2036-05-27\", \"1993-05-27\"], \"expiry_date\": \"2036-05-27\", \"license_numbers\": [\"Z05-26-000505\"], \"name_tokens_found\": [\"ELENA\", \"BAUTISTA\"]}, \"sharpness\": 1464.8, \"confidence\": 95.1, \"preliminary\": true, \"document_score\": 3, \"official_verification\": \"unavailable\"}','dc8f6912-cf71-4c69-a556-867a71e0bf92','2026-09-24 05:12:03','2026-10-14 05:12:04',1,'2026-09-25 05:12:03','2026-10-13 17:16:44','2026-10-13 21:12:04');
/*!40000 ALTER TABLE `driver_licenses` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `drivers`
--

DROP TABLE IF EXISTS `drivers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `drivers` (
  `driver_id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned DEFAULT NULL,
  `first_name` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `middle_name` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `last_name` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `contact_number` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `driver_status` enum('active','inactive') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`driver_id`),
  UNIQUE KEY `drivers_user_id_unique` (`user_id`),
  CONSTRAINT `drivers_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=57 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `drivers`
--

LOCK TABLES `drivers` WRITE;
/*!40000 ALTER TABLE `drivers` DISABLE KEYS */;
INSERT INTO `drivers` VALUES (44,32,'Andrea',NULL,'Villamor','0900-000--001','active','2026-08-29 16:00:00','2026-08-29 16:00:00'),(45,33,'Bryan',NULL,'Castillo','0900-000--002','active','2026-08-29 16:00:00','2026-08-29 16:00:00'),(46,34,'Celia',NULL,'Navarro','0900-000--001','active','2026-08-29 16:00:00','2026-08-29 16:00:00'),(47,35,'Dennis',NULL,'Aquino','0900-000--002','active','2026-08-29 16:00:00','2026-08-29 16:00:00'),(48,36,'Elena',NULL,'Bautista','0900-000--001','active','2026-08-29 16:00:00','2026-08-29 16:00:00');
/*!40000 ALTER TABLE `drivers` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `login_attempts`
--

DROP TABLE IF EXISTS `login_attempts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `login_attempts` (
  `attempt_id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned DEFAULT NULL,
  `identifier` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `was_successful` tinyint(1) NOT NULL DEFAULT '0',
  `failure_reason` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `attempted_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`attempt_id`),
  KEY `login_attempts_user_id_foreign` (`user_id`),
  CONSTRAINT `login_attempts_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=95 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `login_attempts`
--

LOCK TABLES `login_attempts` WRITE;
/*!40000 ALTER TABLE `login_attempts` DISABLE KEYS */;
INSERT INTO `login_attempts` VALUES (1,NULL,'security.demo@pass.local','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36',0,'invalid_credentials','2026-10-13 06:55:16'),(2,NULL,'security.demo@pass.local','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36',0,'invalid_credentials','2026-10-13 06:55:19'),(3,NULL,'security.demo@pass.local','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36',0,'invalid_credentials','2026-10-13 07:12:05'),(4,NULL,'security.demo@pass.local','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36',0,'invalid_credentials','2026-10-13 07:12:07'),(5,1,'security.demo@pass.local','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36',1,NULL,'2026-10-13 07:17:10'),(6,1,'security.demo@pass.local','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Code/1.141.0 Chrome/150.0.7871.250 Electron/43.7.7 Safari/537.36',0,'invalid_credentials','2026-10-13 12:54:18'),(7,1,'security.demo@pass.local','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Code/1.141.0 Chrome/150.0.7871.250 Electron/43.7.7 Safari/537.36',1,NULL,'2026-10-13 12:54:34'),(8,1,'security.demo@pass.local','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Code/1.141.0 Chrome/150.0.7871.250 Electron/43.7.7 Safari/537.36',1,NULL,'2026-10-13 15:23:58'),(9,1,'security.demo@pass.local','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36',1,NULL,'2026-10-14 00:06:25'),(10,1,'security.demo@pass.local','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36',1,NULL,'2026-10-14 00:23:32'),(18,1,'SEC-DEMO-001','127.0.0.1','GuzzleHttp/7',1,NULL,'2026-10-14 00:25:40'),(19,1,'security.demo@pass.local','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Code/1.141.0 Chrome/150.0.7871.250 Electron/43.7.7 Safari/537.36',1,NULL,'2026-10-14 00:31:26'),(27,1,'SEC-DEMO-001','127.0.0.1','GuzzleHttp/7',1,NULL,'2026-10-14 00:34:40'),(28,1,'SEC-DEMO-001','127.0.0.1','GuzzleHttp/7',1,NULL,'2026-10-14 01:17:15'),(29,1,'security.demo@pass.local','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Code/1.141.0 Chrome/150.0.7871.250 Electron/43.7.7 Safari/537.36',1,NULL,'2026-10-14 02:00:23'),(30,1,'security.demo@pass.local','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36',1,NULL,'2026-10-14 02:02:28'),(31,1,'security.demo@pass.local','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Code/1.141.0 Chrome/150.0.7871.250 Electron/43.7.7 Safari/537.36',1,NULL,'2026-10-14 04:03:30'),(32,1,'SEC-DEMO-001','127.0.0.1','GuzzleHttp/7',1,NULL,'2026-10-14 04:08:09'),(33,36,'SAMPLE-EMP-001','127.0.0.1','GuzzleHttp/7',1,NULL,'2026-10-14 04:08:10'),(34,1,'SEC-DEMO-001','127.0.0.1','GuzzleHttp/7',1,NULL,'2026-10-14 04:09:49'),(35,36,'SAMPLE-EMP-001','127.0.0.1','GuzzleHttp/7',1,NULL,'2026-10-14 04:09:49'),(36,32,'SAMPLE-STU-001','127.0.0.1','GuzzleHttp/7',1,NULL,'2026-10-14 04:10:14'),(37,33,'SAMPLE-STU-002','127.0.0.1','GuzzleHttp/7',1,NULL,'2026-10-14 04:10:14'),(38,34,'SAMPLE-FAC-001','127.0.0.1','GuzzleHttp/7',1,NULL,'2026-10-14 04:10:15'),(39,35,'SAMPLE-FAC-002','127.0.0.1','GuzzleHttp/7',1,NULL,'2026-10-14 04:10:15'),(40,36,'SAMPLE-EMP-001','127.0.0.1','GuzzleHttp/7',1,NULL,'2026-10-14 04:10:16'),(41,1,'SEC-DEMO-001','127.0.0.1','GuzzleHttp/7',1,NULL,'2026-10-14 05:10:31'),(42,36,'SAMPLE-EMP-001','127.0.0.1','GuzzleHttp/7',1,NULL,'2026-10-14 05:10:31'),(43,33,'SAMPLE-STU-002','127.0.0.1','GuzzleHttp/7',1,NULL,'2026-10-14 05:10:55'),(44,1,'security.demo@pass.local','127.0.0.1','PostmanRuntime/2.11.2',1,NULL,'2026-10-14 21:07:37'),(45,1,'security.demo@pass.local','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36 Edg/155.0.0.0',1,NULL,'2026-10-14 21:30:51'),(46,1,'security.demo@pass.local','127.0.0.1','PostmanRuntime/2.11.2',1,NULL,'2026-10-14 21:52:55'),(47,1,'security.demo@pass.local','127.0.0.1','PostmanRuntime/2.11.2',1,NULL,'2026-10-14 21:53:39'),(48,1,'security.demo@pass.local','127.0.0.1','PostmanRuntime/2.11.2',1,NULL,'2026-10-14 21:54:07'),(49,1,'security.demo@pass.local','127.0.0.1','PostmanRuntime/2.11.2',1,NULL,'2026-10-14 21:54:09'),(50,NULL,'x@x','127.0.0.1','curl/8.18.0',0,'invalid_credentials','2026-10-14 21:57:49'),(51,1,'security.demo@pass.local','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36 Edg/155.0.0.0',1,NULL,'2026-10-14 22:00:15'),(52,1,'security.demo@pass.local','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36 Edg/155.0.0.0',1,NULL,'2026-10-14 22:08:43'),(53,1,'security.demo@pass.local','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Code/1.141.2 Chrome/150.0.7871.250 Electron/43.7.7 Safari/537.36',1,NULL,'2026-10-14 23:49:08'),(54,1,'security.demo@pass.local','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36',1,NULL,'2026-10-14 23:51:36'),(55,1,'security.demo@pass.local','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36',1,NULL,'2026-10-15 00:17:18'),(56,NULL,'ecurity.demo@pass.local','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Code/1.141.2 Chrome/150.0.7871.250 Electron/43.7.7 Safari/537.36',0,'invalid_credentials','2026-10-15 00:35:13'),(57,1,'security.demo@pass.local','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Code/1.141.2 Chrome/150.0.7871.250 Electron/43.7.7 Safari/537.36',1,NULL,'2026-10-15 00:35:21'),(58,1,'security.demo@pass.local','127.0.0.1','Mozilla/5.0 (Windows NT; Windows NT 10.0; en-PH) WindowsPowerShell/5.1.26100.9444',1,NULL,'2026-10-15 01:30:59'),(59,1,'security.demo@pass.local','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Code/1.141.2 Chrome/150.0.7871.250 Electron/43.7.7 Safari/537.36',1,NULL,'2026-10-15 01:31:42'),(60,1,'security.demo@pass.local','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Code/1.141.2 Chrome/150.0.7871.250 Electron/43.7.7 Safari/537.36',1,NULL,'2026-10-15 02:04:13'),(61,1,'security.demo@pass.local','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Code/1.141.2 Chrome/150.0.7871.250 Electron/43.7.7 Safari/537.36',1,NULL,'2026-10-15 02:36:21'),(62,1,'security.demo@pass.local','127.0.0.1','Mozilla/5.0 (Windows NT; Windows NT 10.0; en-PH) WindowsPowerShell/5.1.26100.9444',1,NULL,'2026-10-15 02:51:24'),(63,1,'security.demo@pass.local','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36',1,NULL,'2026-10-15 03:05:19'),(64,1,'security.demo@pass.local','127.0.0.1','Mozilla/5.0 (Windows NT; Windows NT 10.0; en-PH) WindowsPowerShell/5.1.26100.9444',1,NULL,'2026-10-15 04:59:46'),(65,1,'security.demo@pass.local','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36',0,'invalid_credentials','2026-10-15 05:08:41'),(66,1,'security.demo@pass.local','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36',0,'invalid_credentials','2026-10-15 05:08:55'),(67,1,'security.demo@pass.local','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36',0,'invalid_credentials','2026-10-15 05:08:59'),(68,1,'security.demo@pass.local','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36',1,NULL,'2026-10-15 05:09:10'),(69,NULL,'mali@gmail.com','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36',0,'invalid_credentials','2026-10-15 05:59:21'),(70,NULL,'mali@gmail.com','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36',0,'invalid_credentials','2026-10-15 06:00:21'),(71,NULL,'mali@gmail.com','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36',0,'invalid_credentials','2026-10-15 07:47:05'),(72,48,'rosa.villanueva.test@example.com','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/155.0.0.0 Safari/537.36 Edg/155.0.0.0',1,NULL,'2026-10-15 08:24:26'),(73,46,'admin.demo@pass.local','127.0.0.1','Mozilla/5.0 (Windows NT; Windows NT 10.0; en-PH) WindowsPowerShell/5.1.26100.9444',0,'invalid_credentials','2026-10-15 08:25:42'),(74,46,'admin.demo@pass.local','127.0.0.1','Mozilla/5.0 (Windows NT; Windows NT 10.0; en-PH) WindowsPowerShell/5.1.26100.9444',1,NULL,'2026-10-15 08:26:45'),(75,48,'rosa.villanueva.test@example.com','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/155.0.0.0 Safari/537.36 Edg/155.0.0.0',1,NULL,'2026-10-15 08:27:34'),(76,1,'security.demo@pass.local','127.0.0.1','Mozilla/5.0 (Windows NT; Windows NT 10.0; en-PH) WindowsPowerShell/5.1.26100.9444',1,NULL,'2026-10-15 08:28:32'),(77,48,'rosa.villanueva.test@example.com','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/155.0.0.0 Safari/537.36 Edg/155.0.0.0',1,NULL,'2026-10-15 08:30:28'),(78,49,'guest@pass.com','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Code/1.141.2 Chrome/150.0.7871.250 Electron/43.7.7 Safari/537.36',1,NULL,'2026-10-15 09:39:01'),(79,NULL,'admin@pass.edu','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36',0,'invalid_credentials','2026-10-15 10:18:09'),(80,NULL,'admin@pass.edu','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36',0,'invalid_credentials','2026-10-15 10:18:17'),(81,NULL,'admin@pass.edu','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36',0,'invalid_credentials','2026-10-15 10:18:31'),(82,1,'security.demo@pass.local','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36',1,NULL,'2026-10-15 10:42:48'),(83,1,'security.demo@pass.local','127.0.0.1','Mozilla/5.0 (Windows NT; Windows NT 10.0; en-PH) WindowsPowerShell/5.1.26100.9444',1,NULL,'2026-10-15 11:03:28'),(84,1,'security.demo@pass.local','127.0.0.1','Mozilla/5.0 (Windows NT; Windows NT 10.0; en-PH) WindowsPowerShell/5.1.26100.9444',1,NULL,'2026-10-15 11:03:28'),(85,1,'security.demo@pass.local','127.0.0.1','Mozilla/5.0 (Windows NT; Windows NT 10.0; en-PH) WindowsPowerShell/5.1.26100.9444',1,NULL,'2026-10-15 11:03:29'),(86,50,'lara.gomez.test@example.com','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/155.0.0.0 Safari/537.36 Edg/155.0.0.0',1,NULL,'2026-10-15 11:33:19'),(87,46,'admin.demo@pass.local','127.0.0.1','Mozilla/5.0 (Windows NT; Windows NT 10.0; en-PH) WindowsPowerShell/5.1.26100.9444',1,NULL,'2026-10-15 11:33:35'),(88,50,'lara.gomez.test@example.com','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/155.0.0.0 Safari/537.36 Edg/155.0.0.0',1,NULL,'2026-10-15 11:34:05'),(89,50,'lara.gomez.test@example.com','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/155.0.0.0 Safari/537.36 Edg/155.0.0.0',1,NULL,'2026-10-15 11:35:35'),(90,1,'security.demo@pass.local','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Code/1.141.2 Chrome/150.0.7871.250 Electron/43.7.7 Safari/537.36',1,NULL,'2026-10-15 11:46:24'),(91,1,'security.demo@pass.local','127.0.0.1','Mozilla/5.0 (Windows NT; Windows NT 10.0; en-PH) WindowsPowerShell/5.1.26100.9444',1,NULL,'2026-10-15 16:33:20'),(92,1,'security.demo@pass.local','127.0.0.1','Mozilla/5.0 (Windows NT; Windows NT 10.0; en-PH) WindowsPowerShell/5.1.26100.9444',1,NULL,'2026-10-15 16:38:30'),(93,46,'admin.demo@pass.local','127.0.0.1','Mozilla/5.0 (Windows NT; Windows NT 10.0; en-PH) WindowsPowerShell/5.1.26100.9444',1,NULL,'2026-10-15 16:38:57'),(94,1,'security.demo@pass.local','127.0.0.1','Mozilla/5.0 (Windows NT; Windows NT 10.0; en-PH) WindowsPowerShell/5.1.26100.9444',1,NULL,'2026-10-15 17:12:56');
/*!40000 ALTER TABLE `login_attempts` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `migrations`
--

DROP TABLE IF EXISTS `migrations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `migrations` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `migration` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `batch` int NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=45 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `migrations`
--

LOCK TABLES `migrations` WRITE;
/*!40000 ALTER TABLE `migrations` DISABLE KEYS */;
INSERT INTO `migrations` VALUES (1,'2026_10_07_154030_create_departments_table',1),(2,'2026_10_07_154030_create_roles_table',1),(3,'2026_10_07_154031_create_permissions_table',1),(4,'2026_10_07_154042_create_vehicle_types_table',1),(5,'2026_10_07_154722_create_users_table',1),(6,'2026_10_07_154725_create_user_profiles_table',1),(7,'2026_10_07_154727_create_user_roles_table',1),(8,'2026_10_07_154730_create_role_permissions_table',1),(9,'2026_10_07_154815_create_login_attempts_table',1),(10,'2026_10_08_021551_create_parking_areas_table',1),(11,'2026_10_08_021553_create_visitors_table',1),(12,'2026_10_08_021556_create_drivers_table',1),(13,'2026_10_08_021558_create_driver_licenses_table',1),(14,'2026_10_08_024200_create_vehicles_table',1),(15,'2026_10_08_024203_create_vehicle_authorizations_table',1),(16,'2026_10_08_024205_create_vehicle_driver_table',1),(17,'2026_10_08_024210_add_owner_check_to_vehicles_table',1),(18,'2026_10_08_024526_create_access_gates_table',1),(19,'2026_10_08_024529_create_rfid_tags_table',1),(20,'2026_10_08_024531_create_rfid_readers_table',1),(21,'2026_10_08_024542_create_rfid_assignments_table',1),(22,'2026_10_08_024801_create_parking_zones_table',1),(23,'2026_10_08_024804_create_parking_slots_table',1),(24,'2026_10_08_024806_create_visitor_requests_table',1),(25,'2026_10_08_024809_create_qr_passes_table',1),(26,'2026_10_08_024940_create_access_events_table',1),(27,'2026_10_08_024942_create_parking_transactions_table',1),(28,'2026_10_08_025057_create_mobile_devices_table',1),(29,'2026_10_08_025059_create_notifications_table',1),(30,'2026_10_08_025102_create_violation_types_table',1),(31,'2026_10_08_025104_create_parking_violations_table',1),(32,'2026_10_08_025107_create_violation_evidences_table',1),(33,'2026_10_08_025109_create_audit_logs_table',1),(34,'2026_10_08_025113_create_system_settings_table',1),(35,'2026_10_08_030212_add_safeguards_to_tables',1),(36,'2026_10_08_031024_create_personal_access_tokens_table',1),(37,'2026_10_12_162535_add_school_id_document_path_to_user_profiles_table',1),(38,'2026_10_12_185503_create_password_reset_tokens_table',1),(39,'2026_10_13_132221_create_testings_table',2),(40,'2026_10_13_142010_add_suspended_status_to_vehicle_authorizations_table',3),(41,'2026_10_13_150336_add_verification_columns_to_driver_licenses_table',4),(42,'2026_10_14_035209_add_parking_operations_support',5),(43,'2026_10_14_050156_allow_shared_access_gates',6),(44,'2026_10_15_120000_link_visitors_to_guest_accounts',7);
/*!40000 ALTER TABLE `migrations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `mobile_devices`
--

DROP TABLE IF EXISTS `mobile_devices`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `mobile_devices` (
  `device_id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `device_uuid` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `platform` enum('android','ios') COLLATE utf8mb4_unicode_ci NOT NULL,
  `device_model` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `os_version` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `app_version` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `push_token` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `last_seen_at` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`device_id`),
  UNIQUE KEY `mobile_devices_device_uuid_unique` (`device_uuid`),
  KEY `mobile_devices_user_id_foreign` (`user_id`),
  CONSTRAINT `mobile_devices_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `mobile_devices`
--

LOCK TABLES `mobile_devices` WRITE;
/*!40000 ALTER TABLE `mobile_devices` DISABLE KEYS */;
/*!40000 ALTER TABLE `mobile_devices` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `notifications`
--

DROP TABLE IF EXISTS `notifications`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `notifications` (
  `notification_id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `notification_type` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `body` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `payload` json DEFAULT NULL,
  `related_type` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `related_id` bigint unsigned DEFAULT NULL,
  `channel` enum('push','in_app','email','sms') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'in_app',
  `is_read` tinyint(1) NOT NULL DEFAULT '0',
  `read_at` datetime DEFAULT NULL,
  `sent_at` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`notification_id`),
  KEY `notifications_user_id_foreign` (`user_id`),
  CONSTRAINT `notifications_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=232 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `notifications`
--

LOCK TABLES `notifications` WRITE;
/*!40000 ALTER TABLE `notifications` DISABLE KEYS */;
INSERT INTO `notifications` VALUES (188,1,'parking_access_denied','Entry denied','Entry denied for SMP 1002 at Oct 14, 2026 1:33 AM: Vehicle authorization is pending review.','{\"reason\": \"Vehicle authorization is pending review.\", \"dedupe_key\": \"vehicle:52\", \"plate_number\": \"SMP 1002\", \"access_event_id\": 379}','access_event',379,'in_app',0,NULL,'2026-10-14 05:11:58','2026-10-13 21:11:58','2026-10-13 21:11:58'),(189,1,'parking_access_denied','Entry denied','Entry denied for SMP 1003 at Oct 14, 2026 2:04 AM: Vehicle authorization is suspended.','{\"reason\": \"Vehicle authorization is suspended.\", \"dedupe_key\": \"vehicle:53\", \"plate_number\": \"SMP 1003\", \"access_event_id\": 381}','access_event',381,'in_app',0,NULL,'2026-10-14 05:11:58','2026-10-13 21:11:58','2026-10-13 21:11:58'),(190,1,'parking_access_denied','Entry denied','Entry denied for SMP 1004 at Oct 14, 2026 3:22 AM: Vehicle authorization was rejected.','{\"reason\": \"Vehicle authorization was rejected.\", \"dedupe_key\": \"vehicle:54\", \"plate_number\": \"SMP 1004\", \"access_event_id\": 387}','access_event',387,'in_app',0,NULL,'2026-10-14 05:11:59','2026-10-13 21:11:59','2026-10-13 21:11:59'),(191,1,'parking_access_denied','Entry denied','Entry denied for SMP V004 at Oct 14, 2026 3:38 AM: Visitor QR pass has expired.','{\"reason\": \"Visitor QR pass has expired.\", \"dedupe_key\": \"vehicle:138\", \"plate_number\": \"SMP V004\", \"access_event_id\": 388}','access_event',388,'in_app',0,NULL,'2026-10-14 05:11:59','2026-10-13 21:11:59','2026-10-13 21:11:59'),(192,1,'wrong_slot','Parking Slot Violation','Parking Slot Violation: Vehicle SMP 1001 was assigned to C-04 but was detected at C-20. Reported Oct 14, 2026 5:11 AM. Please verify the vehicle\'s location.','{\"dedupe_key\": \"parking_violation:20\", \"plate_number\": \"SMP 1001\", \"violation_id\": 20, \"assigned_slot\": \"C-04\", \"observed_slot\": \"C-20\"}','parking_violation',20,'in_app',0,NULL,'2026-10-14 05:11:59','2026-10-13 21:11:59','2026-10-13 21:11:59'),(193,1,'wrong_area','Wrong Parking Area','Wrong Parking Area: Vehicle SMP 2004 was assigned to M-01 in the Motorcycle Parking Area but was detected at C-30 in the Car Parking Area. Reported Oct 14, 2026 5:11 AM. Please verify the vehicle\'s location.','{\"dedupe_key\": \"parking_violation:21\", \"plate_number\": \"SMP 2004\", \"violation_id\": 21, \"assigned_slot\": \"M-01\", \"observed_slot\": \"C-30\"}','parking_violation',21,'in_app',0,NULL,'2026-10-14 05:11:59','2026-10-13 21:11:59','2026-10-13 21:11:59'),(194,32,'license_pending_review','Driver\'s license submitted for review','Your driver\'s license passed the preliminary document check and is waiting for review by PASS staff.','{\"screen\": \"driver_license\", \"license_id\": 44}','App\\Models\\DriverLicense',44,'in_app',0,NULL,'2026-10-14 05:12:00','2026-10-13 21:12:00','2026-10-13 21:12:00'),(195,33,'license_pending_review','Driver\'s license submitted for review','Your driver\'s license passed the preliminary document check and is waiting for review by PASS staff.','{\"screen\": \"driver_license\", \"license_id\": 45}','App\\Models\\DriverLicense',45,'in_app',0,NULL,'2026-10-14 05:12:01','2026-10-13 21:12:01','2026-10-13 21:12:01'),(196,34,'license_needs_correction','Driver\'s license needs correction','Your driver\'s license was automatically returned because it did not pass the document check. Please correct the following and resubmit: The license number on the document (Z03-26-000330) does not match the number you entered (Z03-26-000303). Check the number or upload the correct license.','{\"screen\": \"driver_license\", \"license_id\": 46}','App\\Models\\DriverLicense',46,'in_app',0,NULL,'2026-10-14 05:12:02','2026-10-13 21:12:02','2026-10-13 21:12:02'),(197,35,'license_pending_review','Driver\'s license submitted for review','Your driver\'s license passed the preliminary document check and is waiting for review by PASS staff.','{\"screen\": \"driver_license\", \"license_id\": 47}','App\\Models\\DriverLicense',47,'in_app',0,NULL,'2026-10-14 05:12:03','2026-10-13 21:12:03','2026-10-13 21:12:03'),(198,36,'license_pending_review','Driver\'s license submitted for review','Your driver\'s license passed the preliminary document check and is waiting for review by PASS staff.','{\"screen\": \"driver_license\", \"license_id\": 48}','App\\Models\\DriverLicense',48,'in_app',0,NULL,'2026-10-14 05:12:04','2026-10-13 21:12:04','2026-10-13 21:12:04'),(213,1,'parking_access_denied','Entry denied','Entry denied for credential NOT-A-REAL-TAG at Oct 15, 2026 5:01 AM: Unknown RFID tag.','{\"reason\": \"Unknown RFID tag.\", \"dedupe_key\": \"vehicle:NOT-A-REAL-TAG\", \"plate_number\": null, \"access_event_id\": 424}','access_event',424,'in_app',0,NULL,'2026-10-15 05:01:31','2026-10-14 21:01:31','2026-10-14 21:01:31'),(214,1,'parking_access_denied','Entry denied','Entry denied for SMP 2004 at Oct 15, 2026 5:01 AM: Driver\'s license is waiting for staff verification.','{\"reason\": \"Driver\'s license is waiting for staff verification.\", \"dedupe_key\": \"vehicle:76\", \"plate_number\": \"SMP 2004\", \"access_event_id\": 426}','access_event',426,'in_app',0,NULL,'2026-10-15 05:01:47','2026-10-14 21:01:47','2026-10-14 21:01:47'),(215,1,'parking_access_denied','Entry denied','Entry denied for SMP 1003 at Oct 15, 2026 5:02 AM: Vehicle authorization is suspended.','{\"reason\": \"Vehicle authorization is suspended.\", \"dedupe_key\": \"vehicle:53\", \"plate_number\": \"SMP 1003\", \"access_event_id\": 428}','access_event',428,'in_app',0,NULL,'2026-10-15 05:02:19','2026-10-14 21:02:19','2026-10-14 21:02:19'),(216,1,'parking_access_denied','Entry denied','Entry denied for credential NOT-A-REAL-TAG at Oct 15, 2026 1:16 PM: Unknown RFID tag.','{\"reason\": \"Unknown RFID tag.\", \"dedupe_key\": \"vehicle:NOT-A-REAL-TAG\", \"plate_number\": null, \"access_event_id\": 431}','access_event',431,'in_app',0,NULL,'2026-10-15 05:16:51','2026-10-14 21:16:51','2026-10-14 21:16:51'),(217,1,'parking_access_denied','Entry denied','Entry denied for DEMO 4823 at Oct 15, 2026 1:23 PM: Visitor QR pass has already been used.','{\"reason\": \"Visitor QR pass has already been used.\", \"dedupe_key\": \"vehicle:141\", \"plate_number\": \"DEMO 4823\", \"access_event_id\": 435}','access_event',435,'in_app',0,NULL,'2026-10-15 05:23:40','2026-10-14 21:23:40','2026-10-14 21:23:40'),(218,1,'parking_access_denied','Entry denied','Entry denied for DEMO 4821 at Oct 15, 2026 1:23 PM: Visitor QR pass has already been used.','{\"reason\": \"Visitor QR pass has already been used.\", \"dedupe_key\": \"vehicle:139\", \"plate_number\": \"DEMO 4821\", \"access_event_id\": 437}','access_event',437,'in_app',0,NULL,'2026-10-15 05:23:49','2026-10-14 21:23:49','2026-10-14 21:23:49'),(219,1,'parking_access_denied','Entry denied','Entry denied for DEMO 4824 at Oct 15, 2026 1:26 PM: Visitor QR pass was revoked.','{\"reason\": \"Visitor QR pass was revoked.\", \"dedupe_key\": \"vehicle:142\", \"plate_number\": \"DEMO 4824\", \"access_event_id\": 440}','access_event',440,'in_app',1,'2026-10-15 17:02:48','2026-10-15 05:26:24','2026-10-14 21:26:24','2026-10-15 09:02:48'),(220,46,'visitor_request_submitted','Visitor parking request awaiting approval','Maria Santos (GUEST 001) requested visitor parking for Oct 15, 2026 4:00 PM – Oct 16, 2026 12:00 AM.','{\"dedupe_key\": \"visitor_request:5\", \"request_id\": 5, \"plate_number\": \"GUEST 001\"}','visitor_request',5,'in_app',0,NULL,'2026-10-15 08:15:48','2026-10-15 00:15:48','2026-10-15 00:15:48'),(221,46,'visitor_request_submitted','Visitor parking request awaiting approval','Rosa Villanueva (ROSA 2026) requested visitor parking for Oct 15, 2026 4:14 PM – Oct 15, 2026 7:24 PM.','{\"dedupe_key\": \"visitor_request:6\", \"request_id\": 6, \"plate_number\": \"ROSA 2026\"}','visitor_request',6,'in_app',0,NULL,'2026-10-15 08:24:58','2026-10-15 00:24:58','2026-10-15 00:24:58'),(222,46,'visitor_request_submitted','Visitor parking request awaiting approval','Rosa Villanueva (ROSA 2027) requested visitor parking for Oct 15, 2026 4:15 PM – Oct 15, 2026 7:25 PM.','{\"dedupe_key\": \"visitor_request:7\", \"request_id\": 7, \"plate_number\": \"ROSA 2027\"}','visitor_request',7,'in_app',0,NULL,'2026-10-15 08:25:10','2026-10-15 00:25:10','2026-10-15 00:25:10'),(223,48,'visitor_request_approved','Visitor parking request approved','Your visitor parking request for ROSA 2026 was approved. Your QR pass is ready in your PASS account; show it at the Main Entrance / Exit.','{\"screen\": \"guest_dashboard\", \"dedupe_key\": \"visitor_request:6\", \"request_id\": 6}','visitor_request',6,'in_app',0,NULL,'2026-10-15 08:27:02','2026-10-15 00:27:02','2026-10-15 00:27:02'),(224,48,'visitor_request_rejected','Visitor parking request not approved','Your visitor parking request for ROSA 2027 was not approved. Reason: The Registrar is closed for a holiday','{\"screen\": \"guest_dashboard\", \"dedupe_key\": \"visitor_request:7\", \"request_id\": 7}','visitor_request',7,'in_app',0,NULL,'2026-10-15 08:27:06','2026-10-15 00:27:06','2026-10-15 00:27:06'),(225,1,'parking_access_denied','Entry denied','Entry denied for ROSA 2026 at Oct 15, 2026 4:29 PM: Visitor QR pass has already been used.','{\"reason\": \"Visitor QR pass has already been used.\", \"dedupe_key\": \"vehicle:144\", \"plate_number\": \"ROSA 2026\", \"access_event_id\": 443}','access_event',443,'in_app',1,'2026-10-15 17:01:40','2026-10-15 08:29:51','2026-10-15 00:29:51','2026-10-15 09:01:40'),(226,46,'parking_access_denied','Entry denied','Entry denied for ROSA 2026 at Oct 15, 2026 4:29 PM: Visitor QR pass has already been used.','{\"reason\": \"Visitor QR pass has already been used.\", \"dedupe_key\": \"vehicle:144\", \"plate_number\": \"ROSA 2026\", \"access_event_id\": 443}','access_event',443,'in_app',0,NULL,'2026-10-15 08:29:51','2026-10-15 00:29:51','2026-10-15 00:29:51'),(227,46,'visitor_request_submitted','Visitor parking request awaiting approval','guestito visitor (CAR 123) requested visitor parking for Oct 29, 2026 8:30 AM – Oct 29, 2026 10:20 AM.','{\"dedupe_key\": \"visitor_request:8\", \"request_id\": 8, \"plate_number\": \"CAR 123\"}','visitor_request',8,'in_app',0,NULL,'2026-10-15 10:15:56','2026-10-15 02:15:56','2026-10-15 02:15:56'),(228,46,'visitor_request_submitted','Visitor parking request awaiting approval','Lara Gomez (LARA 3101) requested visitor parking for Oct 15, 2026 7:23 PM – Oct 15, 2026 10:33 PM.','{\"dedupe_key\": \"visitor_request:9\", \"request_id\": 9, \"plate_number\": \"LARA 3101\"}','visitor_request',9,'in_app',0,NULL,'2026-10-15 11:33:30','2026-10-15 03:33:30','2026-10-15 03:33:30'),(229,50,'visitor_request_approved','Visitor parking request approved','Your visitor parking request for LARA 3101 was approved. Your QR pass is ready in your PASS account; show it at the Main Entrance / Exit.','{\"screen\": \"guest_dashboard\", \"dedupe_key\": \"visitor_request:9\", \"request_id\": 9}','visitor_request',9,'in_app',0,NULL,'2026-10-15 11:33:51','2026-10-15 03:33:51','2026-10-15 03:33:51'),(230,1,'parking_access_denied','Denied visitor QR access','Entry denied for LARA 3101 at Oct 15, 2026 7:35 PM: Visitor QR pass has already been used.','{\"method\": \"qr\", \"reason\": \"Visitor QR pass has already been used.\", \"dedupe_key\": \"vehicle:147\", \"plate_number\": \"LARA 3101\", \"access_event_id\": 452}','access_event',452,'in_app',0,NULL,'2026-10-15 11:35:24','2026-10-15 03:35:24','2026-10-15 03:35:24'),(231,46,'parking_access_denied','Denied visitor QR access','Entry denied for LARA 3101 at Oct 15, 2026 7:35 PM: Visitor QR pass has already been used.','{\"method\": \"qr\", \"reason\": \"Visitor QR pass has already been used.\", \"dedupe_key\": \"vehicle:147\", \"plate_number\": \"LARA 3101\", \"access_event_id\": 452}','access_event',452,'in_app',0,NULL,'2026-10-15 11:35:24','2026-10-15 03:35:24','2026-10-15 03:35:24');
/*!40000 ALTER TABLE `notifications` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `parking_areas`
--

DROP TABLE IF EXISTS `parking_areas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `parking_areas` (
  `parking_area_id` int unsigned NOT NULL AUTO_INCREMENT,
  `area_code` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL,
  `area_name` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `building` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `floor_level` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `description` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `latitude` decimal(10,7) DEFAULT NULL,
  `longitude` decimal(10,7) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`parking_area_id`),
  UNIQUE KEY `parking_areas_area_code_unique` (`area_code`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `parking_areas`
--

LOCK TABLES `parking_areas` WRITE;
/*!40000 ALTER TABLE `parking_areas` DISABLE KEYS */;
INSERT INTO `parking_areas` VALUES (6,'CAR','Car Parking Area',NULL,NULL,'Cars, SUVs, vans and pickups',NULL,NULL,1),(7,'MC','Motorcycle Parking Area',NULL,NULL,'Motorcycles only',NULL,NULL,1);
/*!40000 ALTER TABLE `parking_areas` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `parking_slots`
--

DROP TABLE IF EXISTS `parking_slots`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `parking_slots` (
  `slot_id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `zone_id` int unsigned NOT NULL,
  `slot_code` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL,
  `vehicle_type_id` tinyint unsigned DEFAULT NULL,
  `slot_status` enum('available','maintenance','disabled') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'available',
  `is_pwd_slot` tinyint(1) NOT NULL DEFAULT '0',
  `reserved_for_user_id` bigint unsigned DEFAULT NULL,
  `pos_x` smallint DEFAULT NULL,
  `pos_y` smallint DEFAULT NULL,
  `slot_width` smallint unsigned DEFAULT NULL,
  `slot_height` smallint unsigned DEFAULT NULL,
  `rotation_deg` smallint NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`slot_id`),
  UNIQUE KEY `parking_slots_zone_id_slot_code_unique` (`zone_id`,`slot_code`),
  KEY `parking_slots_vehicle_type_id_foreign` (`vehicle_type_id`),
  KEY `parking_slots_reserved_for_user_id_foreign` (`reserved_for_user_id`),
  CONSTRAINT `parking_slots_reserved_for_user_id_foreign` FOREIGN KEY (`reserved_for_user_id`) REFERENCES `users` (`user_id`),
  CONSTRAINT `parking_slots_vehicle_type_id_foreign` FOREIGN KEY (`vehicle_type_id`) REFERENCES `vehicle_types` (`vehicle_type_id`),
  CONSTRAINT `parking_slots_zone_id_foreign` FOREIGN KEY (`zone_id`) REFERENCES `parking_zones` (`zone_id`)
) ENGINE=InnoDB AUTO_INCREMENT=140 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `parking_slots`
--

LOCK TABLES `parking_slots` WRITE;
/*!40000 ALTER TABLE `parking_slots` DISABLE KEYS */;
INSERT INTO `parking_slots` VALUES (1,1,'C-01',NULL,'available',0,NULL,3349,493,170,240,9,'2026-10-13 19:53:01','2026-10-13 19:53:01'),(2,1,'C-02',NULL,'available',0,NULL,3524,521,170,240,9,'2026-10-13 19:53:01','2026-10-13 19:53:01'),(3,1,'C-03',NULL,'available',0,NULL,3700,549,170,240,9,'2026-10-13 19:53:01','2026-10-13 19:53:01'),(4,1,'C-04',NULL,'available',0,NULL,3875,577,170,240,9,'2026-10-13 19:53:01','2026-10-13 19:53:01'),(5,1,'C-05',NULL,'available',0,NULL,4051,604,170,240,9,'2026-10-13 19:53:01','2026-10-14 18:52:06'),(6,1,'C-06',NULL,'available',0,NULL,4226,632,170,240,9,'2026-10-13 19:53:01','2026-10-13 21:10:40'),(7,1,'C-07',NULL,'available',0,NULL,4401,660,170,240,9,'2026-10-13 19:53:01','2026-10-13 19:53:01'),(8,1,'C-08',NULL,'available',0,NULL,4577,688,170,240,9,'2026-10-13 19:53:01','2026-10-13 19:53:01'),(9,1,'C-09',NULL,'available',0,NULL,4752,716,170,240,9,'2026-10-13 19:53:01','2026-10-13 19:53:01'),(10,1,'C-10',NULL,'available',0,NULL,4928,744,170,240,9,'2026-10-13 19:53:01','2026-10-13 19:53:01'),(11,1,'C-11',NULL,'available',0,NULL,5103,772,170,240,9,'2026-10-13 19:53:01','2026-10-13 19:53:01'),(12,1,'C-12',NULL,'available',0,NULL,5278,799,170,240,9,'2026-10-13 19:53:01','2026-10-13 19:53:01'),(13,1,'C-13',NULL,'available',0,NULL,5454,827,170,240,9,'2026-10-13 19:53:01','2026-10-13 19:53:01'),(14,1,'C-14',NULL,'available',0,NULL,5629,855,170,240,9,'2026-10-13 19:53:01','2026-10-13 19:53:01'),(15,1,'C-15',NULL,'available',0,NULL,5805,883,170,240,9,'2026-10-13 19:53:01','2026-10-13 19:53:01'),(16,1,'C-16',NULL,'available',0,NULL,5980,911,170,240,9,'2026-10-13 19:53:01','2026-10-13 19:53:01'),(17,1,'C-17',NULL,'available',0,NULL,6155,939,170,240,9,'2026-10-13 19:53:01','2026-10-13 19:53:01'),(18,1,'C-18',NULL,'available',0,NULL,6331,966,170,240,9,'2026-10-13 19:53:01','2026-10-13 19:53:01'),(19,1,'C-19',NULL,'available',0,NULL,6506,994,170,240,9,'2026-10-13 19:53:01','2026-10-13 19:53:01'),(20,1,'C-20',NULL,'available',0,NULL,6682,1022,170,240,9,'2026-10-13 19:53:01','2026-10-13 19:53:01'),(21,1,'C-21',NULL,'available',0,NULL,6857,1050,170,240,9,'2026-10-13 19:53:01','2026-10-13 19:53:01'),(22,1,'C-22',NULL,'available',0,NULL,3557,1064,172,240,10,'2026-10-13 19:53:01','2026-10-13 19:53:01'),(23,1,'C-23',NULL,'available',0,NULL,3736,1096,172,240,10,'2026-10-13 19:53:01','2026-10-13 19:53:01'),(24,1,'C-24',NULL,'available',0,NULL,3914,1129,172,240,10,'2026-10-13 19:53:01','2026-10-13 19:53:01'),(25,1,'C-25',NULL,'available',0,NULL,4093,1161,172,240,10,'2026-10-13 19:53:01','2026-10-13 19:53:01'),(26,1,'C-26',NULL,'available',0,NULL,4271,1193,172,240,10,'2026-10-13 19:53:01','2026-10-13 19:53:01'),(27,1,'C-27',NULL,'available',0,NULL,4450,1226,172,240,10,'2026-10-13 19:53:01','2026-10-13 19:53:01'),(28,1,'C-28',NULL,'available',0,NULL,4628,1258,172,240,10,'2026-10-13 19:53:01','2026-10-13 19:53:01'),(29,1,'C-29',NULL,'available',0,NULL,4807,1291,172,240,10,'2026-10-13 19:53:01','2026-10-13 19:53:01'),(30,1,'C-30',NULL,'available',0,NULL,4986,1323,172,240,10,'2026-10-13 19:53:01','2026-10-13 19:53:01'),(31,1,'C-31',NULL,'available',0,NULL,5164,1355,172,240,10,'2026-10-13 19:53:01','2026-10-13 19:53:01'),(32,1,'C-32',NULL,'available',0,NULL,5343,1388,172,240,10,'2026-10-13 19:53:01','2026-10-13 19:53:01'),(33,1,'C-33',NULL,'available',0,NULL,5521,1420,172,240,10,'2026-10-13 19:53:01','2026-10-13 19:53:01'),(34,1,'C-34',NULL,'available',0,NULL,5700,1452,172,240,10,'2026-10-13 19:53:01','2026-10-13 19:53:01'),(35,1,'C-35',NULL,'available',0,NULL,5879,1485,172,240,10,'2026-10-13 19:53:01','2026-10-13 19:53:01'),(36,1,'C-36',NULL,'available',0,NULL,6057,1517,172,240,10,'2026-10-13 19:53:01','2026-10-13 19:53:01'),(37,1,'C-37',NULL,'available',0,NULL,6236,1550,172,240,10,'2026-10-13 19:53:01','2026-10-13 19:53:01'),(38,1,'C-38',NULL,'available',0,NULL,NULL,NULL,NULL,NULL,0,'2026-10-13 19:53:01','2026-10-13 19:53:01'),(39,1,'C-39',NULL,'available',0,NULL,6414,1582,172,240,10,'2026-10-13 19:53:01','2026-10-13 19:53:01'),(40,1,'C-40',NULL,'available',0,NULL,NULL,NULL,NULL,NULL,0,'2026-10-13 19:53:01','2026-10-13 19:53:01'),(41,1,'C-41',NULL,'available',0,NULL,6593,1614,172,240,10,'2026-10-13 19:53:01','2026-10-13 19:53:01'),(42,1,'C-42',NULL,'available',1,NULL,6771,1647,172,240,10,'2026-10-13 19:53:01','2026-10-13 19:53:01'),(43,1,'C-43',NULL,'available',0,NULL,3829,1671,186,240,11,'2026-10-13 19:53:01','2026-10-13 19:53:01'),(44,1,'C-44',NULL,'available',0,NULL,4023,1709,186,240,11,'2026-10-13 19:53:01','2026-10-13 19:53:01'),(45,1,'C-45',NULL,'available',0,NULL,4218,1748,186,240,11,'2026-10-13 19:53:01','2026-10-13 19:53:01'),(46,1,'C-46',NULL,'available',0,NULL,4412,1786,186,240,11,'2026-10-13 19:53:01','2026-10-13 19:53:01'),(47,1,'C-47',NULL,'available',0,NULL,4607,1825,186,240,11,'2026-10-13 19:53:01','2026-10-13 19:53:01'),(48,1,'C-48',NULL,'available',0,NULL,4801,1863,186,240,11,'2026-10-13 19:53:01','2026-10-13 19:53:01'),(49,1,'C-49',NULL,'available',0,NULL,4996,1902,186,240,11,'2026-10-13 19:53:01','2026-10-13 19:53:01'),(50,1,'C-50',NULL,'maintenance',0,NULL,5190,1940,186,240,11,'2026-10-13 19:53:01','2026-10-13 20:02:01'),(51,1,'C-51',NULL,'available',0,NULL,5385,1979,186,240,11,'2026-10-13 19:53:01','2026-10-13 19:53:01'),(52,1,'C-52',NULL,'available',0,NULL,5579,2017,186,240,11,'2026-10-13 19:53:01','2026-10-13 19:53:01'),(53,1,'C-53',NULL,'available',0,NULL,5774,2056,186,240,11,'2026-10-13 19:53:01','2026-10-13 19:53:01'),(54,1,'C-54',NULL,'available',0,NULL,5968,2094,186,240,11,'2026-10-13 19:53:01','2026-10-13 19:53:01'),(55,1,'C-55',NULL,'available',0,NULL,6357,2171,186,240,11,'2026-10-13 19:53:01','2026-10-13 19:53:01'),(56,2,'M-01',6,'available',0,NULL,875,1350,90,150,-21,'2026-10-13 19:53:01','2026-10-13 19:53:01'),(57,2,'M-02',6,'available',0,NULL,965,1316,90,150,-21,'2026-10-13 19:53:01','2026-10-13 19:53:01'),(58,2,'M-03',6,'available',0,NULL,1055,1281,90,150,-21,'2026-10-13 19:53:01','2026-10-13 19:53:01'),(59,2,'M-04',6,'available',0,NULL,1146,1247,90,150,-21,'2026-10-13 19:53:01','2026-10-13 19:53:01'),(60,2,'M-05',6,'available',0,NULL,1236,1213,90,150,-21,'2026-10-13 19:53:01','2026-10-13 19:53:01'),(61,2,'M-06',6,'available',0,NULL,1326,1179,90,150,-21,'2026-10-13 19:53:01','2026-10-13 19:53:01'),(62,2,'M-07',6,'available',0,NULL,1416,1144,90,150,-21,'2026-10-13 19:53:01','2026-10-13 19:53:01'),(63,2,'M-08',6,'available',0,NULL,1507,1110,90,150,-21,'2026-10-13 19:53:01','2026-10-13 19:53:01'),(64,2,'M-09',6,'available',0,NULL,1597,1076,90,150,-21,'2026-10-13 19:53:01','2026-10-13 19:53:01'),(65,2,'M-10',6,'available',0,NULL,1687,1041,90,150,-21,'2026-10-13 19:53:01','2026-10-13 19:53:01'),(66,2,'M-11',6,'available',0,NULL,1777,1007,90,150,-21,'2026-10-13 19:53:01','2026-10-13 19:53:01'),(67,2,'M-12',6,'available',0,NULL,1868,973,90,150,-21,'2026-10-13 19:53:01','2026-10-13 19:53:01'),(68,2,'M-13',6,'available',0,NULL,1958,939,90,150,-21,'2026-10-13 19:53:01','2026-10-13 19:53:01'),(69,2,'M-14',6,'available',0,NULL,2048,904,90,150,-21,'2026-10-13 19:53:01','2026-10-13 19:53:01'),(70,2,'M-15',6,'available',0,NULL,2138,870,90,150,-21,'2026-10-13 19:53:01','2026-10-13 19:53:01'),(71,2,'M-16',6,'available',0,NULL,2229,836,90,150,-21,'2026-10-13 19:53:01','2026-10-13 19:53:01'),(72,2,'M-17',6,'available',0,NULL,2319,801,90,150,-21,'2026-10-13 19:53:01','2026-10-13 19:53:01'),(73,2,'M-18',6,'available',0,NULL,2409,767,90,150,-21,'2026-10-13 19:53:01','2026-10-13 19:53:01'),(74,2,'M-19',6,'available',0,NULL,2499,733,90,150,-21,'2026-10-13 19:53:01','2026-10-13 19:53:01'),(75,2,'M-20',6,'available',0,NULL,2590,699,90,150,-21,'2026-10-13 19:53:01','2026-10-13 19:53:01'),(76,2,'M-21',6,'available',0,NULL,2680,664,90,150,-21,'2026-10-13 19:53:01','2026-10-13 19:53:01'),(77,2,'M-22',6,'available',0,NULL,2770,630,90,150,-21,'2026-10-13 19:53:01','2026-10-13 19:53:01'),(78,2,'M-23',6,'available',0,NULL,NULL,NULL,NULL,NULL,0,'2026-10-13 19:53:01','2026-10-13 19:53:01'),(79,2,'M-24',6,'available',0,NULL,NULL,NULL,NULL,NULL,0,'2026-10-13 19:53:01','2026-10-13 19:53:01'),(80,2,'M-25',6,'available',0,NULL,NULL,NULL,NULL,NULL,0,'2026-10-13 19:53:01','2026-10-13 19:53:01'),(81,2,'M-26',6,'available',0,NULL,NULL,NULL,NULL,NULL,0,'2026-10-13 19:53:01','2026-10-13 19:53:01'),(82,2,'M-27',6,'available',0,NULL,NULL,NULL,NULL,NULL,0,'2026-10-13 19:53:01','2026-10-13 19:53:01'),(83,2,'M-28',6,'available',0,NULL,NULL,NULL,NULL,NULL,0,'2026-10-13 19:53:01','2026-10-13 19:53:01'),(84,2,'M-29',6,'available',0,NULL,NULL,NULL,NULL,NULL,0,'2026-10-13 19:53:01','2026-10-13 19:53:01'),(85,2,'M-30',6,'available',0,NULL,NULL,NULL,NULL,NULL,0,'2026-10-13 19:53:01','2026-10-13 19:53:01'),(86,2,'M-31',6,'available',0,NULL,NULL,NULL,NULL,NULL,0,'2026-10-13 19:53:01','2026-10-13 19:53:01'),(87,2,'M-32',6,'available',0,NULL,NULL,NULL,NULL,NULL,0,'2026-10-13 19:53:01','2026-10-13 19:53:01'),(88,2,'M-33',6,'available',0,NULL,NULL,NULL,NULL,NULL,0,'2026-10-13 19:53:01','2026-10-13 19:53:01'),(89,2,'M-34',6,'available',0,NULL,NULL,NULL,NULL,NULL,0,'2026-10-13 19:53:01','2026-10-13 19:53:01'),(90,2,'M-35',6,'available',0,NULL,NULL,NULL,NULL,NULL,0,'2026-10-13 19:53:01','2026-10-13 19:53:01'),(91,2,'M-36',6,'available',0,NULL,NULL,NULL,NULL,NULL,0,'2026-10-13 19:53:01','2026-10-13 19:53:01'),(92,2,'M-37',6,'available',0,NULL,NULL,NULL,NULL,NULL,0,'2026-10-13 19:53:01','2026-10-13 19:53:01'),(93,2,'M-38',6,'available',0,NULL,NULL,NULL,NULL,NULL,0,'2026-10-13 19:53:01','2026-10-13 19:53:01'),(94,2,'M-39',6,'available',0,NULL,NULL,NULL,NULL,NULL,0,'2026-10-13 19:53:01','2026-10-13 19:53:01'),(95,2,'M-40',6,'available',0,NULL,NULL,NULL,NULL,NULL,0,'2026-10-13 19:53:01','2026-10-13 19:53:01'),(96,2,'M-41',6,'available',0,NULL,NULL,NULL,NULL,NULL,0,'2026-10-13 19:53:01','2026-10-13 19:53:01'),(97,2,'M-42',6,'available',0,NULL,NULL,NULL,NULL,NULL,0,'2026-10-13 19:53:01','2026-10-13 19:53:01'),(98,2,'M-43',6,'available',0,NULL,1338,1495,76,400,70,'2026-10-13 19:53:01','2026-10-13 19:53:01'),(99,2,'M-44',6,'available',0,NULL,1367,1573,76,400,70,'2026-10-13 19:53:01','2026-10-13 19:53:01'),(100,2,'M-45',6,'available',0,NULL,1395,1650,76,400,70,'2026-10-13 19:53:01','2026-10-13 19:53:01'),(101,2,'M-46',6,'available',0,NULL,1424,1728,76,400,70,'2026-10-13 19:53:01','2026-10-13 19:53:01'),(102,2,'M-47',6,'available',0,NULL,1452,1805,76,400,70,'2026-10-13 19:53:01','2026-10-13 19:53:01'),(103,2,'M-48',6,'available',0,NULL,1481,1883,76,400,70,'2026-10-13 19:53:01','2026-10-13 19:53:01'),(104,2,'M-49',6,'available',0,NULL,1509,1960,76,400,70,'2026-10-13 19:53:01','2026-10-13 19:53:01'),(105,2,'M-50',6,'available',0,NULL,1538,2038,76,400,70,'2026-10-13 19:53:01','2026-10-13 19:53:01'),(106,2,'M-51',6,'available',0,NULL,1567,2115,76,400,70,'2026-10-13 19:53:01','2026-10-13 19:53:01'),(107,2,'M-52',6,'available',0,NULL,1595,2193,76,400,70,'2026-10-13 19:53:01','2026-10-13 19:53:01'),(108,2,'M-53',6,'available',0,NULL,1624,2270,76,400,70,'2026-10-13 19:53:01','2026-10-13 19:53:01'),(109,2,'M-54',6,'available',0,NULL,1652,2348,76,400,70,'2026-10-13 19:53:01','2026-10-13 19:53:01'),(110,2,'M-55',6,'available',0,NULL,1681,2425,76,400,70,'2026-10-13 19:53:01','2026-10-13 19:53:01'),(111,2,'M-56',6,'available',0,NULL,1709,2503,76,400,70,'2026-10-13 19:53:01','2026-10-13 19:53:01'),(112,2,'M-57',6,'available',0,NULL,1995,1263,79,420,68,'2026-10-13 19:53:01','2026-10-13 19:53:01'),(113,2,'M-58',6,'available',0,NULL,2027,1343,79,420,68,'2026-10-13 19:53:01','2026-10-13 19:53:01'),(114,2,'M-59',6,'available',0,NULL,2058,1423,79,420,68,'2026-10-13 19:53:01','2026-10-13 19:53:01'),(115,2,'M-60',6,'available',0,NULL,2090,1502,79,420,68,'2026-10-13 19:53:01','2026-10-13 19:53:01'),(116,2,'M-61',6,'available',0,NULL,2122,1582,79,420,68,'2026-10-13 19:53:01','2026-10-13 19:53:01'),(117,2,'M-62',6,'available',0,NULL,2153,1662,79,420,68,'2026-10-13 19:53:01','2026-10-13 19:53:01'),(118,2,'M-63',6,'available',0,NULL,2185,1742,79,420,68,'2026-10-13 19:53:01','2026-10-13 19:53:01'),(119,2,'M-64',6,'available',0,NULL,2217,1822,79,420,68,'2026-10-13 19:53:01','2026-10-13 19:53:01'),(120,2,'M-65',6,'available',0,NULL,2248,1901,79,420,68,'2026-10-13 19:53:01','2026-10-13 19:53:01'),(121,2,'M-66',6,'available',0,NULL,2280,1981,79,420,68,'2026-10-13 19:53:01','2026-10-13 19:53:01'),(122,2,'M-67',6,'available',0,NULL,2343,2141,79,420,68,'2026-10-13 19:53:01','2026-10-13 19:53:01'),(123,2,'M-68',6,'available',0,NULL,2375,2220,79,420,68,'2026-10-13 19:53:01','2026-10-13 19:53:01'),(124,2,'M-69',6,'available',0,NULL,2406,2300,79,420,68,'2026-10-13 19:53:01','2026-10-13 19:53:01'),(125,2,'M-70',6,'available',0,NULL,2438,2380,79,420,68,'2026-10-13 19:53:01','2026-10-13 19:53:01'),(126,2,'M-71',6,'available',0,NULL,2693,1025,76,360,68,'2026-10-13 19:53:01','2026-10-13 19:53:01'),(127,2,'M-72',6,'available',0,NULL,2723,1102,76,360,68,'2026-10-13 19:53:01','2026-10-13 19:53:01'),(128,2,'M-73',6,'available',0,NULL,2754,1179,76,360,68,'2026-10-13 19:53:01','2026-10-13 19:53:01'),(129,2,'M-74',6,'available',0,NULL,2784,1256,76,360,68,'2026-10-13 19:53:01','2026-10-13 19:53:01'),(130,2,'M-75',6,'available',0,NULL,2815,1333,76,360,68,'2026-10-13 19:53:01','2026-10-13 19:53:01'),(131,2,'M-76',6,'available',0,NULL,2845,1410,76,360,68,'2026-10-13 19:53:01','2026-10-13 19:53:01'),(132,2,'M-77',6,'available',0,NULL,2876,1487,76,360,68,'2026-10-13 19:53:01','2026-10-13 19:53:01'),(133,2,'M-78',6,'available',0,NULL,2937,1641,76,360,68,'2026-10-13 19:53:01','2026-10-13 19:53:01'),(134,2,'M-79',6,'available',0,NULL,2967,1718,76,360,68,'2026-10-13 19:53:01','2026-10-13 19:53:01'),(135,2,'M-80',6,'maintenance',0,NULL,2998,1795,76,360,68,'2026-10-13 19:53:01','2026-10-13 20:02:01'),(136,2,'M-81',6,'available',0,NULL,3028,1872,76,360,68,'2026-10-13 19:53:01','2026-10-13 19:53:01'),(137,2,'M-82',6,'available',0,NULL,3089,2026,76,360,68,'2026-10-13 19:53:01','2026-10-13 19:53:01'),(138,2,'M-83',6,'available',0,NULL,3120,2103,76,360,68,'2026-10-13 19:53:01','2026-10-13 19:53:01'),(139,2,'M-84',6,'available',0,NULL,3150,2180,76,360,68,'2026-10-13 19:53:01','2026-10-13 19:53:01');
/*!40000 ALTER TABLE `parking_slots` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `parking_transactions`
--

DROP TABLE IF EXISTS `parking_transactions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `parking_transactions` (
  `transaction_id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `ticket_number` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `vehicle_id` bigint unsigned NOT NULL,
  `driver_id` bigint unsigned DEFAULT NULL,
  `user_id` bigint unsigned DEFAULT NULL,
  `visitor_id` bigint unsigned DEFAULT NULL,
  `parking_area_id` int unsigned NOT NULL,
  `slot_id` bigint unsigned DEFAULT NULL,
  `slot_confirmed_at` datetime DEFAULT NULL,
  `slot_confirmed_by` bigint unsigned DEFAULT NULL,
  `access_method` enum('rfid','qr','manual') COLLATE utf8mb4_unicode_ci NOT NULL,
  `entry_event_id` bigint unsigned DEFAULT NULL,
  `exit_event_id` bigint unsigned DEFAULT NULL,
  `entry_datetime` datetime NOT NULL,
  `exit_datetime` datetime DEFAULT NULL,
  `transaction_status` enum('active','completed','force_closed','cancelled') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'active',
  `recorded_by` bigint unsigned DEFAULT NULL,
  `remarks` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `active_vehicle_flag` tinyint GENERATED ALWAYS AS (if((`transaction_status` = _utf8mb4'active'),1,NULL)) STORED,
  `active_slot_flag` tinyint GENERATED ALWAYS AS (if(((`transaction_status` = _utf8mb4'active') and (`slot_id` is not null)),1,NULL)) STORED,
  `duration_minutes` int GENERATED ALWAYS AS (if((`exit_datetime` is null),NULL,timestampdiff(MINUTE,`entry_datetime`,`exit_datetime`))) STORED,
  PRIMARY KEY (`transaction_id`),
  UNIQUE KEY `parking_transactions_ticket_number_unique` (`ticket_number`),
  UNIQUE KEY `parking_transactions_entry_event_id_unique` (`entry_event_id`),
  UNIQUE KEY `parking_transactions_exit_event_id_unique` (`exit_event_id`),
  UNIQUE KEY `uq_vehicle_one_active_stay` (`vehicle_id`,`active_vehicle_flag`),
  UNIQUE KEY `uq_slot_one_active_stay` (`slot_id`,`active_slot_flag`),
  KEY `parking_transactions_entry_datetime_index` (`entry_datetime`),
  KEY `parking_transactions_driver_id_foreign` (`driver_id`),
  KEY `parking_transactions_user_id_foreign` (`user_id`),
  KEY `parking_transactions_visitor_id_foreign` (`visitor_id`),
  KEY `parking_transactions_parking_area_id_foreign` (`parking_area_id`),
  KEY `parking_transactions_recorded_by_foreign` (`recorded_by`),
  KEY `parking_transactions_slot_confirmed_by_foreign` (`slot_confirmed_by`),
  CONSTRAINT `parking_transactions_driver_id_foreign` FOREIGN KEY (`driver_id`) REFERENCES `drivers` (`driver_id`),
  CONSTRAINT `parking_transactions_entry_event_id_foreign` FOREIGN KEY (`entry_event_id`) REFERENCES `access_events` (`event_id`),
  CONSTRAINT `parking_transactions_exit_event_id_foreign` FOREIGN KEY (`exit_event_id`) REFERENCES `access_events` (`event_id`),
  CONSTRAINT `parking_transactions_parking_area_id_foreign` FOREIGN KEY (`parking_area_id`) REFERENCES `parking_areas` (`parking_area_id`),
  CONSTRAINT `parking_transactions_recorded_by_foreign` FOREIGN KEY (`recorded_by`) REFERENCES `users` (`user_id`),
  CONSTRAINT `parking_transactions_slot_confirmed_by_foreign` FOREIGN KEY (`slot_confirmed_by`) REFERENCES `users` (`user_id`) ON DELETE SET NULL,
  CONSTRAINT `parking_transactions_slot_id_foreign` FOREIGN KEY (`slot_id`) REFERENCES `parking_slots` (`slot_id`),
  CONSTRAINT `parking_transactions_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`),
  CONSTRAINT `parking_transactions_vehicle_id_foreign` FOREIGN KEY (`vehicle_id`) REFERENCES `vehicles` (`vehicle_id`),
  CONSTRAINT `parking_transactions_visitor_id_foreign` FOREIGN KEY (`visitor_id`) REFERENCES `visitors` (`visitor_id`),
  CONSTRAINT `chk_txn_one_party` CHECK (((`user_id` is null) <> (`visitor_id` is null)))
) ENGINE=InnoDB AUTO_INCREMENT=211 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `parking_transactions`
--

LOCK TABLES `parking_transactions` WRITE;
/*!40000 ALTER TABLE `parking_transactions` DISABLE KEYS */;
INSERT INTO `parking_transactions` (`transaction_id`, `ticket_number`, `vehicle_id`, `driver_id`, `user_id`, `visitor_id`, `parking_area_id`, `slot_id`, `slot_confirmed_at`, `slot_confirmed_by`, `access_method`, `entry_event_id`, `exit_event_id`, `entry_datetime`, `exit_datetime`, `transaction_status`, `recorded_by`, `remarks`, `created_at`, `updated_at`) VALUES (183,'PT-20261013-LOBV2S',55,48,36,NULL,6,1,NULL,NULL,'rfid',370,376,'2026-10-13 07:30:00','2026-10-14 00:15:35','completed',1,'SAMPLE TEST DATA','2026-10-12 23:30:00','2026-10-13 16:15:35'),(184,'PT-20261013-JSIORQ',51,44,32,NULL,6,2,NULL,NULL,'rfid',371,375,'2026-10-13 07:45:00','2026-10-13 17:10:00','completed',1,'SAMPLE TEST DATA','2026-10-12 23:45:00','2026-10-13 09:10:00'),(185,'PT-20261013-GMBOSQ',76,47,35,NULL,7,56,NULL,NULL,'rfid',372,374,'2026-10-13 08:10:00','2026-10-13 16:40:00','completed',1,'SAMPLE TEST DATA','2026-10-13 00:10:00','2026-10-13 08:40:00'),(186,'PT-20261013-UHPOAS',137,NULL,NULL,63,6,3,NULL,NULL,'qr',373,NULL,'2026-10-13 15:00:00',NULL,'active',1,'SAMPLE TEST DATA','2026-10-13 07:00:00','2026-10-13 07:00:00'),(187,'PT-20261014-85VCVE',51,44,32,NULL,6,1,NULL,NULL,'rfid',377,380,'2026-10-14 00:31:11','2026-10-14 01:49:11','completed',1,'SAMPLE TEST DATA','2026-10-13 16:31:11','2026-10-13 17:49:11'),(188,'PT-20261014-FVP2WD',136,NULL,NULL,62,6,2,NULL,NULL,'qr',378,383,'2026-10-14 01:02:23','2026-10-14 02:35:59','completed',1,'SAMPLE TEST DATA','2026-10-13 17:02:23','2026-10-13 18:35:59'),(189,'PT-20261014-URP5QB',55,48,36,NULL,6,1,'2026-10-14 05:11:59',1,'rfid',382,429,'2026-10-14 02:20:23','2026-10-15 05:16:45','completed',1,'SAMPLE TEST DATA','2026-10-13 18:20:23','2026-10-14 21:16:45'),(190,'PT-20261014-P8RPQU',135,NULL,NULL,61,6,2,'2026-10-14 05:11:59',1,'qr',384,NULL,'2026-10-14 02:51:35',NULL,'active',1,'SAMPLE TEST DATA','2026-10-13 18:51:35','2026-10-13 21:11:59'),(191,'PT-20261014-0IWTXI',51,44,32,NULL,6,4,NULL,NULL,'rfid',385,422,'2026-10-14 03:07:11','2026-10-15 05:00:55','completed',1,'SAMPLE TEST DATA','2026-10-13 19:07:11','2026-10-14 21:00:55'),(192,'PT-20261014-LNNBAR',76,47,35,NULL,7,56,'2026-10-14 05:11:59',1,'rfid',386,425,'2026-10-14 03:13:25','2026-10-15 05:01:39','completed',1,'SAMPLE TEST DATA','2026-10-13 19:13:25','2026-10-14 21:01:39'),(201,'PT-20261015-ITLLGJ',51,44,32,NULL,6,4,NULL,NULL,'rfid',423,453,'2026-10-15 05:01:11','2026-10-15 17:15:40','completed',1,NULL,'2026-10-14 21:01:11','2026-10-15 09:15:40'),(202,'PT-20261015-4INHWK',55,48,36,NULL,6,1,NULL,NULL,'rfid',430,444,'2026-10-15 05:16:48','2026-10-15 11:04:00','completed',1,NULL,'2026-10-14 21:16:48','2026-10-15 03:04:00'),(203,'PT-20261015-YSWPTH',139,NULL,NULL,65,6,5,NULL,NULL,'qr',432,436,'2026-10-15 05:21:43','2026-10-15 05:23:46','completed',1,NULL,'2026-10-14 21:21:43','2026-10-14 21:23:46'),(204,'PT-20261015-MNW6XE',141,NULL,NULL,65,6,6,NULL,NULL,'qr',433,434,'2026-10-15 05:22:53','2026-10-15 05:23:37','completed',1,NULL,'2026-10-14 21:22:53','2026-10-14 21:23:37'),(205,'PT-20261015-UMBQ4E',142,NULL,NULL,65,6,5,NULL,NULL,'qr',438,439,'2026-10-15 05:25:38','2026-10-15 05:26:20','completed',1,NULL,'2026-10-14 21:25:38','2026-10-14 21:26:20'),(206,'PT-20261015-AV8V2Z',144,NULL,NULL,67,6,5,NULL,NULL,'qr',441,442,'2026-10-15 08:28:50','2026-10-15 08:29:41','completed',1,NULL,'2026-10-15 00:28:50','2026-10-15 00:29:41'),(207,'PT-20261015-FLNOOO',55,48,36,NULL,6,1,NULL,NULL,'rfid',445,446,'2026-10-15 11:04:25','2026-10-15 11:07:25','completed',1,NULL,'2026-10-15 03:04:25','2026-10-15 03:07:25'),(208,'PT-20261015-AER0O6',55,48,36,NULL,6,1,NULL,NULL,'rfid',447,448,'2026-10-15 11:17:27','2026-10-15 11:21:59','completed',1,NULL,'2026-10-15 03:17:27','2026-10-15 03:21:59'),(209,'PT-20261015-VPBGQS',55,48,36,NULL,6,1,NULL,NULL,'rfid',449,NULL,'2026-10-15 11:22:24',NULL,'active',1,NULL,'2026-10-15 03:22:24','2026-10-15 03:22:24'),(210,'PT-20261015-G0CBZW',147,NULL,NULL,69,6,5,NULL,NULL,'qr',450,451,'2026-10-15 11:35:22','2026-10-15 11:35:23','completed',1,NULL,'2026-10-15 03:35:22','2026-10-15 03:35:23');
/*!40000 ALTER TABLE `parking_transactions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `parking_violations`
--

DROP TABLE IF EXISTS `parking_violations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `parking_violations` (
  `violation_id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `violation_type_id` int unsigned NOT NULL,
  `parking_transaction_id` bigint unsigned DEFAULT NULL,
  `vehicle_id` bigint unsigned DEFAULT NULL,
  `user_id` bigint unsigned DEFAULT NULL,
  `visitor_id` bigint unsigned DEFAULT NULL,
  `slot_id` bigint unsigned DEFAULT NULL,
  `expected_slot_id` bigint unsigned DEFAULT NULL,
  `parking_area_id` int unsigned DEFAULT NULL,
  `occurred_at` datetime NOT NULL,
  `reported_by` bigint unsigned DEFAULT NULL,
  `reported_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `description` text COLLATE utf8mb4_unicode_ci,
  `penalty_amount` decimal(10,2) DEFAULT NULL,
  `violation_status` enum('open','under_review','resolved','dismissed','escalated') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'open',
  `resolved_by` bigint unsigned DEFAULT NULL,
  `resolved_at` datetime DEFAULT NULL,
  `resolution_remarks` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`violation_id`),
  KEY `parking_violations_violation_type_id_foreign` (`violation_type_id`),
  KEY `parking_violations_parking_transaction_id_foreign` (`parking_transaction_id`),
  KEY `parking_violations_vehicle_id_foreign` (`vehicle_id`),
  KEY `parking_violations_user_id_foreign` (`user_id`),
  KEY `parking_violations_visitor_id_foreign` (`visitor_id`),
  KEY `parking_violations_slot_id_foreign` (`slot_id`),
  KEY `parking_violations_parking_area_id_foreign` (`parking_area_id`),
  KEY `parking_violations_reported_by_foreign` (`reported_by`),
  KEY `parking_violations_resolved_by_foreign` (`resolved_by`),
  KEY `parking_violations_expected_slot_id_foreign` (`expected_slot_id`),
  CONSTRAINT `parking_violations_expected_slot_id_foreign` FOREIGN KEY (`expected_slot_id`) REFERENCES `parking_slots` (`slot_id`) ON DELETE SET NULL,
  CONSTRAINT `parking_violations_parking_area_id_foreign` FOREIGN KEY (`parking_area_id`) REFERENCES `parking_areas` (`parking_area_id`),
  CONSTRAINT `parking_violations_parking_transaction_id_foreign` FOREIGN KEY (`parking_transaction_id`) REFERENCES `parking_transactions` (`transaction_id`),
  CONSTRAINT `parking_violations_reported_by_foreign` FOREIGN KEY (`reported_by`) REFERENCES `users` (`user_id`),
  CONSTRAINT `parking_violations_resolved_by_foreign` FOREIGN KEY (`resolved_by`) REFERENCES `users` (`user_id`),
  CONSTRAINT `parking_violations_slot_id_foreign` FOREIGN KEY (`slot_id`) REFERENCES `parking_slots` (`slot_id`),
  CONSTRAINT `parking_violations_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`),
  CONSTRAINT `parking_violations_vehicle_id_foreign` FOREIGN KEY (`vehicle_id`) REFERENCES `vehicles` (`vehicle_id`),
  CONSTRAINT `parking_violations_violation_type_id_foreign` FOREIGN KEY (`violation_type_id`) REFERENCES `violation_types` (`violation_type_id`),
  CONSTRAINT `parking_violations_visitor_id_foreign` FOREIGN KEY (`visitor_id`) REFERENCES `visitors` (`visitor_id`)
) ENGINE=InnoDB AUTO_INCREMENT=28 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `parking_violations`
--

LOCK TABLES `parking_violations` WRITE;
/*!40000 ALTER TABLE `parking_violations` DISABLE KEYS */;
INSERT INTO `parking_violations` VALUES (20,1,191,51,32,NULL,20,4,6,'2026-10-14 05:11:59',1,'2026-10-14 05:11:59','Vehicle SMP 1001 was assigned to C-04 but was detected at C-20.',NULL,'open',NULL,NULL,NULL,'2026-10-13 21:11:59','2026-10-13 21:11:59'),(21,2,192,76,35,NULL,30,56,6,'2026-10-14 05:11:59',1,'2026-10-14 05:11:59','Vehicle SMP 2004 was assigned to M-01 in the Motorcycle Parking Area but was detected at C-30 in the Car Parking Area.',NULL,'resolved',1,'2026-10-14 05:11:59','Vehicle moved back to its assigned slot (confirmed). SAMPLE','2026-10-13 21:11:59','2026-10-13 21:11:59'),(26,3,NULL,51,32,NULL,NULL,NULL,NULL,'2026-10-15 17:18:59',1,'2026-10-15 17:18:59','[QA test] Violations module verification - safe to ignore',NULL,'resolved',1,'2026-10-15 17:19:02','QA verification complete','2026-10-15 09:18:59','2026-10-15 09:19:02'),(27,3,NULL,51,32,NULL,NULL,NULL,NULL,'2026-10-15 17:19:04',1,'2026-10-15 17:19:04','[QA test] dismiss-path verification - safe to ignore',NULL,'dismissed',1,'2026-10-15 17:19:06','QA test record','2026-10-15 09:19:04','2026-10-15 09:19:06');
/*!40000 ALTER TABLE `parking_violations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `parking_zones`
--

DROP TABLE IF EXISTS `parking_zones`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `parking_zones` (
  `zone_id` int unsigned NOT NULL AUTO_INCREMENT,
  `parking_area_id` int unsigned NOT NULL,
  `zone_code` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL,
  `zone_name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `vehicle_type_id` tinyint unsigned DEFAULT NULL,
  `declared_capacity` smallint unsigned NOT NULL DEFAULT '0',
  `layout_width` smallint unsigned DEFAULT NULL,
  `layout_height` smallint unsigned DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`zone_id`),
  KEY `parking_zones_parking_area_id_foreign` (`parking_area_id`),
  KEY `parking_zones_vehicle_type_id_foreign` (`vehicle_type_id`),
  CONSTRAINT `parking_zones_parking_area_id_foreign` FOREIGN KEY (`parking_area_id`) REFERENCES `parking_areas` (`parking_area_id`),
  CONSTRAINT `parking_zones_vehicle_type_id_foreign` FOREIGN KEY (`vehicle_type_id`) REFERENCES `vehicle_types` (`vehicle_type_id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `parking_zones`
--

LOCK TABLES `parking_zones` WRITE;
/*!40000 ALTER TABLE `parking_zones` DISABLE KEYS */;
INSERT INTO `parking_zones` VALUES (1,6,'C','Car Slots',NULL,55,11,5,1,NULL,'2026-10-14 16:56:54'),(2,7,'M','Motorcycle Slots',6,84,12,7,1,NULL,'2026-10-14 16:56:54');
/*!40000 ALTER TABLE `parking_zones` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `password_reset_tokens`
--

DROP TABLE IF EXISTS `password_reset_tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `password_reset_tokens` (
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `token` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `password_reset_tokens`
--

LOCK TABLES `password_reset_tokens` WRITE;
/*!40000 ALTER TABLE `password_reset_tokens` DISABLE KEYS */;
/*!40000 ALTER TABLE `password_reset_tokens` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `permissions`
--

DROP TABLE IF EXISTS `permissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `permissions` (
  `permission_id` int unsigned NOT NULL AUTO_INCREMENT,
  `permission_code` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `permission_name` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `module_group` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`permission_id`),
  UNIQUE KEY `permissions_permission_code_unique` (`permission_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `permissions`
--

LOCK TABLES `permissions` WRITE;
/*!40000 ALTER TABLE `permissions` DISABLE KEYS */;
/*!40000 ALTER TABLE `permissions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `personal_access_tokens`
--

DROP TABLE IF EXISTS `personal_access_tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `personal_access_tokens` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `tokenable_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `tokenable_id` bigint unsigned NOT NULL,
  `name` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `token` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  `abilities` text COLLATE utf8mb4_unicode_ci,
  `last_used_at` timestamp NULL DEFAULT NULL,
  `expires_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `personal_access_tokens_token_unique` (`token`),
  KEY `personal_access_tokens_tokenable_type_tokenable_id_index` (`tokenable_type`,`tokenable_id`),
  KEY `personal_access_tokens_expires_at_index` (`expires_at`)
) ENGINE=InnoDB AUTO_INCREMENT=126 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `personal_access_tokens`
--

LOCK TABLES `personal_access_tokens` WRITE;
/*!40000 ALTER TABLE `personal_access_tokens` DISABLE KEYS */;
INSERT INTO `personal_access_tokens` VALUES (1,'App\\Models\\User',1,'web','5564610aeb3844c29ad7f497e577e4faa7f1a3a893056a641076e3f9fb88de0f','[\"*\"]',NULL,NULL,'2026-10-12 23:17:10','2026-10-12 23:17:10'),(2,'App\\Models\\User',1,'web','3acca44f4439d07dc621f0a1f0bfde324409e4235be4fee73ff14f460b93eafa','[\"*\"]','2026-10-13 07:13:01',NULL,'2026-10-13 04:54:34','2026-10-13 07:13:01'),(23,'App\\Models\\User',1,'web','e081d75e8ca4a3483e3228190ea99cbf6e543fd5eb6401bb3d56b243d64541f7','[\"*\"]','2026-10-13 09:31:07',NULL,'2026-10-13 07:23:58','2026-10-13 09:31:07'),(24,'App\\Models\\User',1,'web','06a99ca7d273436182e5e106198f56359e64f28d7fff8f04bac56ab38f8c1822','[\"*\"]','2026-10-13 16:23:21',NULL,'2026-10-13 16:06:25','2026-10-13 16:23:21'),(25,'App\\Models\\User',1,'web','df00256849cd676c6e115dfddde88600ef7d6a4097d6a23484a562b1071b35b6','[\"*\"]','2026-10-13 17:59:22',NULL,'2026-10-13 16:23:32','2026-10-13 17:59:22'),(32,'App\\Models\\User',1,'web','9a6d04450ae0fef584609f342e742f8d5c6f79f9a9878a959c6a4a24ccdaeaa1','[\"*\"]','2026-10-13 16:26:48',NULL,'2026-10-13 16:25:40','2026-10-13 16:26:48'),(33,'App\\Models\\User',1,'web','ed337696a71a388c7a15bb2ba13967e33eb538cc332c5cf3840bed97bb34806e','[\"*\"]','2026-10-13 17:59:13',NULL,'2026-10-13 16:31:26','2026-10-13 17:59:13'),(47,'App\\Models\\User',1,'web','193c99993d9f6c6c8d5b73a4d389aec7461a69eec57122087b5049822da41652','[\"*\"]','2026-10-13 16:36:01',NULL,'2026-10-13 16:34:40','2026-10-13 16:36:01'),(48,'App\\Models\\User',1,'web','afcc247a8bcb78355c2e158bfb7e5b5ad4404dc1ca331f3705270593e2c87939','[\"*\"]','2026-10-13 17:17:48',NULL,'2026-10-13 17:17:15','2026-10-13 17:17:48'),(49,'App\\Models\\User',1,'web','f205976f8c5ac25bd19a72ec5f55ed3a4eb94dbcb90b09e525ab3c56fd161997','[\"*\"]','2026-10-13 20:02:49',NULL,'2026-10-13 18:00:23','2026-10-13 20:02:49'),(50,'App\\Models\\User',1,'web','1ec4d2171bf682ec70ed31038ae6720a5ed373aeaff65c66967ea63c9a72ce79','[\"*\"]','2026-10-14 04:39:15',NULL,'2026-10-13 18:02:28','2026-10-14 04:39:15'),(51,'App\\Models\\User',1,'web','af3978d5d4dfb177475b664921fdc62653b869ca41a05b0d29216e7602e79fac','[\"*\"]','2026-10-14 04:02:51',NULL,'2026-10-13 20:03:30','2026-10-14 04:02:51'),(52,'App\\Models\\User',1,'web','417d37d9039d4a18c3710b89e7247b35394a1aa8c8801384113a2d481cfe5224','[\"*\"]','2026-10-13 20:08:20',NULL,'2026-10-13 20:08:09','2026-10-13 20:08:20'),(53,'App\\Models\\User',36,'web','b059ad87adc182600a6440a6ad9af2a4d278553198864772855bdc9f0554689b','[\"*\"]','2026-10-13 20:08:15',NULL,'2026-10-13 20:08:10','2026-10-13 20:08:15'),(54,'App\\Models\\User',1,'web','0af0c161f28ae9fed310b3620ffafd0e295ab77598ceb87f8e8e46d3b332363c','[\"*\"]','2026-10-13 20:10:12',NULL,'2026-10-13 20:09:49','2026-10-13 20:10:12'),(55,'App\\Models\\User',36,'web','7ca09dd74153610c3de6a6e17f6bbcdfdf03b7d7e9ba9335318817fc3db1d3d5','[\"*\"]','2026-10-13 20:09:55',NULL,'2026-10-13 20:09:49','2026-10-13 20:09:55'),(56,'App\\Models\\User',32,'web','b9b7ccb3ada02efb8a2409ae64d39be5a7be0516c209489988fe8b090fc1f495','[\"*\"]',NULL,NULL,'2026-10-13 20:10:14','2026-10-13 20:10:14'),(57,'App\\Models\\User',33,'web','72381ff356c5a78da0dce276a7633138d16081e19aa81dee1027f29c9ada08dd','[\"*\"]',NULL,NULL,'2026-10-13 20:10:14','2026-10-13 20:10:14'),(58,'App\\Models\\User',34,'web','695d6b11392b518d814018fc4f6450431d1fe4510495b9ad71e0ac2d748f0cf1','[\"*\"]',NULL,NULL,'2026-10-13 20:10:15','2026-10-13 20:10:15'),(59,'App\\Models\\User',35,'web','f27411d020e25864d6bd308f99aa03fb2d3323435aa5428e41ddc3cc45d87a4e','[\"*\"]',NULL,NULL,'2026-10-13 20:10:15','2026-10-13 20:10:15'),(60,'App\\Models\\User',36,'web','a616f997f26380da571aa1610f470952b3f87d7c5057eab72a17844ad1477cfb','[\"*\"]',NULL,NULL,'2026-10-13 20:10:16','2026-10-13 20:10:16'),(78,'App\\Models\\User',1,'web','ce2dbfbb6a990a9f30920eeaec97dfcfd19e9985a71fe7d5ab7e88ee02b314ab','[\"*\"]','2026-10-13 21:10:53',NULL,'2026-10-13 21:10:31','2026-10-13 21:10:53'),(79,'App\\Models\\User',36,'web','bc8aaa9789dfe41c513769bf00d2f94c7319fabd5ba3b66751a7844df7319ab9','[\"*\"]','2026-10-13 21:10:36',NULL,'2026-10-13 21:10:32','2026-10-13 21:10:36'),(80,'App\\Models\\User',33,'web','a6779703e3e5c6c54a997b7ac019e8c1e3f598a9504bb3c7657196885774e9df','[\"*\"]','2026-10-13 21:10:57',NULL,'2026-10-13 21:10:55','2026-10-13 21:10:57'),(87,'App\\Models\\User',1,'web','325b038f590690742f53ee698981011d0022b026a3738806fa0668f27b60fe33','[\"*\"]',NULL,NULL,'2026-10-14 13:07:37','2026-10-14 13:07:37'),(88,'App\\Models\\User',1,'web','4424b63351f26fac08670b0182b5931119a869e4fd6095a0591f03ab1332099f','[\"*\"]','2026-10-14 13:57:58',NULL,'2026-10-14 13:30:53','2026-10-14 13:57:58'),(89,'App\\Models\\User',1,'web','e40f48436a8695ae05e6466e701abf14f0ba8a2fc968e4f076b2fc0a11df74a4','[\"*\"]',NULL,NULL,'2026-10-14 13:52:55','2026-10-14 13:52:55'),(90,'App\\Models\\User',1,'web','3ad0b6210b4ecfb39388f9ad654bb1206ca3d52c80e95a7a8d53b1887d1823ab','[\"*\"]',NULL,NULL,'2026-10-14 13:53:39','2026-10-14 13:53:39'),(91,'App\\Models\\User',1,'web','345d24f767a2870d39a8978530e611815b588d693b7330d174862707887c270e','[\"*\"]',NULL,NULL,'2026-10-14 13:54:07','2026-10-14 13:54:07'),(92,'App\\Models\\User',1,'web','a24d573ada5e326e619a48e91e851b0aa3cc95ae238597c1fb8a452f9c8899f9','[\"*\"]',NULL,NULL,'2026-10-14 13:54:09','2026-10-14 13:54:09'),(93,'App\\Models\\User',1,'web','d70f80b3936cde62a5c1a7efa0134b92d2da8825ed99143187c4298c4314ddd3','[\"*\"]','2026-10-14 14:08:26',NULL,'2026-10-14 14:00:15','2026-10-14 14:08:26'),(94,'App\\Models\\User',1,'web','9a0984a5f3428c5342349e713cef05eb7f64e9ee2d2cc6e55601a35af4c2d1b4','[\"*\"]','2026-10-14 14:30:58',NULL,'2026-10-14 14:08:43','2026-10-14 14:30:58'),(95,'App\\Models\\User',1,'web','6a876c46f9d981f4a6f630b1ad5479a0d0d427c2faefe1b37a0a330491b1e635','[\"*\"]','2026-10-14 16:34:31',NULL,'2026-10-14 15:49:08','2026-10-14 16:34:31'),(96,'App\\Models\\User',1,'web','5256e91bea6d9d59367ef47064f02cd5385cc3fb93c213bade15294c232b3d3a','[\"*\"]','2026-10-14 16:11:22',NULL,'2026-10-14 15:51:36','2026-10-14 16:11:22'),(97,'App\\Models\\User',1,'web','bc1cb8b16e7c0d6e030480c490602d65664513bf8dc397e5edf514a56790868d','[\"*\"]','2026-10-14 19:05:09',NULL,'2026-10-14 16:17:18','2026-10-14 19:05:09'),(98,'App\\Models\\User',1,'web','1a785f206519de508171b0e57cf6bc870a4a6e1813356ae98eaf99710952bea5','[\"*\"]','2026-10-14 17:29:46',NULL,'2026-10-14 16:35:21','2026-10-14 17:29:46'),(100,'App\\Models\\User',1,'web','4932e7ecd07910606b5a088151fb361ae4e19a27ec7543d3cc33c5c0edfd51be','[\"*\"]','2026-10-14 18:03:51',NULL,'2026-10-14 17:31:42','2026-10-14 18:03:51'),(101,'App\\Models\\User',1,'web','ff19a2358ca0e69f4abccecf339eb7ebd9891c9d3ef95fb841736e8f27e7cd8e','[\"*\"]','2026-10-14 18:34:15',NULL,'2026-10-14 18:04:13','2026-10-14 18:34:15'),(102,'App\\Models\\User',1,'web','1cdab05fd596b825f70f05eae4aa351e530a24cc17c1f3920a512218e050688a','[\"*\"]','2026-10-15 01:27:54',NULL,'2026-10-14 18:36:21','2026-10-15 01:27:54'),(104,'App\\Models\\User',1,'web','18e2209e89cff6728badf2cf2124aec0ebc9f9a348d70c285d38189d380dc45e','[\"*\"]','2026-10-14 20:35:35',NULL,'2026-10-14 19:05:19','2026-10-14 20:35:35'),(107,'App\\Models\\User',48,'web','c01e4bbb529f85cded5dc61b7948ab826f840d1fc00ec9d896be0e4fc308c066','[\"*\"]','2026-10-15 00:25:10',NULL,'2026-10-15 00:24:26','2026-10-15 00:25:10'),(109,'App\\Models\\User',48,'web','2f3f04bee1500a6bd9a0816d026586f4d1b53b872d5f6ccdce1b9e6d6d42620e','[\"*\"]','2026-10-15 00:27:35',NULL,'2026-10-15 00:27:34','2026-10-15 00:27:35'),(111,'App\\Models\\User',48,'web','059283c1568b6a75ff2c094b04db2c3f29404cfe3c3b99758533fcf0f486c474','[\"*\"]','2026-10-15 00:30:43',NULL,'2026-10-15 00:30:28','2026-10-15 00:30:43'),(113,'App\\Models\\User',1,'web','d440536529646bc587e8ee0fb923e263ea4642a34eef9a2c1cc697ea9ebefb8d','[\"*\"]','2026-10-15 09:28:39',NULL,'2026-10-15 02:42:48','2026-10-15 09:28:39'),(117,'App\\Models\\User',50,'web','769133fd8e186cc2951522ed1aabd76e329c8951a058070e3cc12a6d6fd1e0fc','[\"*\"]','2026-10-15 03:33:31',NULL,'2026-10-15 03:33:19','2026-10-15 03:33:31'),(119,'App\\Models\\User',50,'web','bbe6c5117418b5939ae2ebfba3c65a30c91199302f14fb25d2f149939904f4ee','[\"*\"]','2026-10-15 03:34:53',NULL,'2026-10-15 03:34:05','2026-10-15 03:34:53'),(120,'App\\Models\\User',50,'web','29e7e2730b36d5609c4b9347515124fa3d4e7a3aedce92952157a618a6743f51','[\"*\"]','2026-10-15 03:36:28',NULL,'2026-10-15 03:35:35','2026-10-15 03:36:28'),(121,'App\\Models\\User',1,'web','4e29aa08ab97d240789b9dec320eb27f07521be250f0e8693369d4493bd85dbb','[\"*\"]','2026-10-15 05:24:09',NULL,'2026-10-15 03:46:24','2026-10-15 05:24:09');
/*!40000 ALTER TABLE `personal_access_tokens` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `qr_passes`
--

DROP TABLE IF EXISTS `qr_passes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `qr_passes` (
  `qr_pass_id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `pass_code` char(36) COLLATE utf8mb4_unicode_ci NOT NULL,
  `token_hash` varchar(128) COLLATE utf8mb4_unicode_ci NOT NULL,
  `pass_type` enum('visitor','user_temporary') COLLATE utf8mb4_unicode_ci NOT NULL,
  `request_id` bigint unsigned DEFAULT NULL,
  `visitor_id` bigint unsigned DEFAULT NULL,
  `user_id` bigint unsigned DEFAULT NULL,
  `vehicle_id` bigint unsigned DEFAULT NULL,
  `valid_from` datetime NOT NULL,
  `valid_until` datetime NOT NULL,
  `max_uses` tinyint unsigned NOT NULL DEFAULT '2',
  `use_count` tinyint unsigned NOT NULL DEFAULT '0',
  `pass_status` enum('active','used','expired','revoked') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'active',
  `issued_by` bigint unsigned DEFAULT NULL,
  `issued_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `revoked_at` datetime DEFAULT NULL,
  `revoke_reason` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`qr_pass_id`),
  UNIQUE KEY `qr_passes_pass_code_unique` (`pass_code`),
  UNIQUE KEY `qr_passes_token_hash_unique` (`token_hash`),
  KEY `qr_passes_request_id_foreign` (`request_id`),
  KEY `qr_passes_visitor_id_foreign` (`visitor_id`),
  KEY `qr_passes_user_id_foreign` (`user_id`),
  KEY `qr_passes_vehicle_id_foreign` (`vehicle_id`),
  KEY `qr_passes_issued_by_foreign` (`issued_by`),
  CONSTRAINT `qr_passes_issued_by_foreign` FOREIGN KEY (`issued_by`) REFERENCES `users` (`user_id`),
  CONSTRAINT `qr_passes_request_id_foreign` FOREIGN KEY (`request_id`) REFERENCES `visitor_requests` (`request_id`),
  CONSTRAINT `qr_passes_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`),
  CONSTRAINT `qr_passes_vehicle_id_foreign` FOREIGN KEY (`vehicle_id`) REFERENCES `vehicles` (`vehicle_id`),
  CONSTRAINT `qr_passes_visitor_id_foreign` FOREIGN KEY (`visitor_id`) REFERENCES `visitors` (`visitor_id`)
) ENGINE=InnoDB AUTO_INCREMENT=73 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `qr_passes`
--

LOCK TABLES `qr_passes` WRITE;
/*!40000 ALTER TABLE `qr_passes` DISABLE KEYS */;
INSERT INTO `qr_passes` VALUES (61,'45109880-5ced-457e-82f2-4aafd13532c7','2cb59f8717ca1326f342c25569dc87670a333fdcb8543e0a03764e6bed3fca8f','visitor',NULL,61,NULL,135,'2026-10-13 00:00:00','2026-10-14 23:59:59',2,1,'used',1,'2026-10-13 00:00:00',NULL,NULL,'2026-10-12 16:00:00','2026-10-13 21:11:58'),(62,'c87f7994-809c-4c78-92e8-2d0fd1fea75b','0691cd5ec3d25491487bfca627ca8585ec7ad14ff2f4d9205b59eb6f83549edd','visitor',NULL,62,NULL,136,'2026-10-13 00:00:00','2026-10-14 23:59:59',2,2,'used',1,'2026-10-13 00:00:00',NULL,NULL,'2026-10-12 16:00:00','2026-10-13 21:11:58'),(63,'808565a1-9ce6-4be0-b246-0f4d61649f56','77f5d3a88caf9bcce247bc815aaf94ec02f0b4a1ac8f07cd58144dc3b5fea433','visitor',NULL,63,NULL,137,'2026-10-13 00:00:00','2026-10-14 23:59:59',2,1,'used',1,'2026-10-13 00:00:00',NULL,NULL,'2026-10-12 16:00:00','2026-10-13 21:11:58'),(64,'ee6fda2b-b8d5-4d63-9560-b98257904ec9','26a9b9f4b4e4106370a52abc3e7b98ce743121cccb0f3e03e16559efdfb4c7b2','visitor',NULL,64,NULL,138,'2026-10-12 00:00:00','2026-10-13 00:00:00',2,0,'expired',1,'2026-10-13 00:00:00',NULL,NULL,'2026-10-12 16:00:00','2026-10-12 16:00:00'),(68,'0de7fd57-c280-4731-b96c-02f7439ab305','4046e97e37df1dd1000dd5fa186e55a7331f958a82c34687c68a1e8054fdc38d','visitor',1,65,NULL,139,'2026-10-15 05:17:00','2026-10-15 09:17:00',2,2,'used',1,'2026-10-15 05:20:24',NULL,NULL,'2026-10-14 21:20:24','2026-10-14 21:23:46'),(69,'c6852210-1beb-472b-a293-6aea5827afe7','e197121c0e8b773638beac81265488b4372cc840a6d91969e73d385181317ab9','visitor',3,65,NULL,141,'2026-10-15 05:22:00','2026-10-15 09:22:00',2,2,'used',1,'2026-10-15 05:22:45',NULL,NULL,'2026-10-14 21:22:45','2026-10-14 21:23:37'),(70,'6a2a3317-5adb-4178-ae5a-73978244459d','f25a1d5a06ba431d6c139a3c8f600e322b1f1a2f82032d0576803c929e0a3038','visitor',4,65,NULL,142,'2026-10-15 05:25:00','2026-10-15 09:25:00',2,2,'revoked',1,'2026-10-15 05:25:31','2026-10-15 05:25:47','Interview cancelled','2026-10-14 21:25:31','2026-10-14 21:26:20'),(71,'645c55b7-df4d-48e6-b843-89b5d8a26c0d','74a18b07ad2ea3cf44c6399275ab46805e5be6a3c6f3c988aa473b95c914fe75','visitor',6,67,NULL,144,'2026-10-15 08:14:00','2026-10-15 11:24:00',2,2,'used',46,'2026-10-15 08:27:02',NULL,NULL,'2026-10-15 00:27:02','2026-10-15 00:29:41'),(72,'ac40cdae-3d16-4396-a14b-1a3a004adf9f','5c4f30169bd826ad4fedeeeab74cba20862860a043e6b3b5154980d5f896ef22','visitor',9,69,NULL,147,'2026-10-15 11:23:00','2026-10-15 14:33:00',2,2,'used',46,'2026-10-15 11:33:51',NULL,NULL,'2026-10-15 03:33:51','2026-10-15 03:35:23');
/*!40000 ALTER TABLE `qr_passes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `rfid_assignments`
--

DROP TABLE IF EXISTS `rfid_assignments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `rfid_assignments` (
  `assignment_id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `rfid_id` bigint unsigned NOT NULL,
  `user_id` bigint unsigned NOT NULL,
  `vehicle_id` bigint unsigned DEFAULT NULL,
  `assigned_by` bigint unsigned DEFAULT NULL,
  `assigned_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `valid_until` date DEFAULT NULL,
  `unassigned_at` datetime DEFAULT NULL,
  `assignment_status` enum('active','expired','revoked','replaced') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'active',
  `remarks` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `active_flag` tinyint GENERATED ALWAYS AS (if((`assignment_status` = _utf8mb4'active'),1,NULL)) STORED,
  PRIMARY KEY (`assignment_id`),
  UNIQUE KEY `uq_tag_one_active_assignment` (`rfid_id`,`active_flag`),
  KEY `rfid_assignments_user_id_foreign` (`user_id`),
  KEY `rfid_assignments_vehicle_id_foreign` (`vehicle_id`),
  KEY `rfid_assignments_assigned_by_foreign` (`assigned_by`),
  CONSTRAINT `rfid_assignments_assigned_by_foreign` FOREIGN KEY (`assigned_by`) REFERENCES `users` (`user_id`),
  CONSTRAINT `rfid_assignments_rfid_id_foreign` FOREIGN KEY (`rfid_id`) REFERENCES `rfid_tags` (`rfid_id`),
  CONSTRAINT `rfid_assignments_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`),
  CONSTRAINT `rfid_assignments_vehicle_id_foreign` FOREIGN KEY (`vehicle_id`) REFERENCES `vehicles` (`vehicle_id`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `rfid_assignments`
--

LOCK TABLES `rfid_assignments` WRITE;
/*!40000 ALTER TABLE `rfid_assignments` DISABLE KEYS */;
INSERT INTO `rfid_assignments` (`assignment_id`, `rfid_id`, `user_id`, `vehicle_id`, `assigned_by`, `assigned_at`, `valid_until`, `unassigned_at`, `assignment_status`, `remarks`, `created_at`, `updated_at`) VALUES (1,1,32,51,1,'2026-09-04 00:00:00','2027-09-04',NULL,'active','SAMPLE TEST DATA','2026-09-03 16:00:00','2026-09-03 16:00:00'),(2,2,34,53,1,'2026-09-04 00:00:00','2027-09-04',NULL,'active','SAMPLE TEST DATA','2026-09-03 16:00:00','2026-09-03 16:00:00'),(3,3,35,76,1,'2026-09-04 00:00:00','2027-09-04',NULL,'active','SAMPLE TEST DATA','2026-09-03 16:00:00','2026-09-03 16:00:00'),(4,4,36,55,1,'2026-09-04 00:00:00','2027-09-04',NULL,'active','SAMPLE TEST DATA','2026-09-03 16:00:00','2026-09-03 16:00:00');
/*!40000 ALTER TABLE `rfid_assignments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `rfid_readers`
--

DROP TABLE IF EXISTS `rfid_readers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `rfid_readers` (
  `reader_id` int unsigned NOT NULL AUTO_INCREMENT,
  `reader_code` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL,
  `gate_id` int unsigned NOT NULL,
  `device_name` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `hardware_serial` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `firmware_version` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `mac_address` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `api_key_hash` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `reader_direction` enum('in','out','both') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'both',
  `reader_status` enum('online','offline','maintenance') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'offline',
  `last_heartbeat_at` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`reader_id`),
  UNIQUE KEY `rfid_readers_reader_code_unique` (`reader_code`),
  KEY `rfid_readers_gate_id_foreign` (`gate_id`),
  CONSTRAINT `rfid_readers_gate_id_foreign` FOREIGN KEY (`gate_id`) REFERENCES `access_gates` (`gate_id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `rfid_readers`
--

LOCK TABLES `rfid_readers` WRITE;
/*!40000 ALTER TABLE `rfid_readers` DISABLE KEYS */;
/*!40000 ALTER TABLE `rfid_readers` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `rfid_tags`
--

DROP TABLE IF EXISTS `rfid_tags`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `rfid_tags` (
  `rfid_id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `tag_uid` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `tag_type` enum('card','sticker','keyfob') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'card',
  `serial_number` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `batch_code` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `tag_status` enum('available','assigned','lost','damaged','deactivated') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'available',
  `acquired_at` date DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`rfid_id`),
  UNIQUE KEY `rfid_tags_tag_uid_unique` (`tag_uid`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `rfid_tags`
--

LOCK TABLES `rfid_tags` WRITE;
/*!40000 ALTER TABLE `rfid_tags` DISABLE KEYS */;
INSERT INTO `rfid_tags` VALUES (1,'SAMPLE-RFID-1001','sticker','SAMPLE-RFID-1001','SAMPLE TEST DATA','assigned','2026-09-04','2026-09-03 16:00:00','2026-09-03 16:00:00'),(2,'SAMPLE-RFID-1003','sticker','SAMPLE-RFID-1003','SAMPLE TEST DATA','assigned','2026-09-04','2026-09-03 16:00:00','2026-09-03 16:00:00'),(3,'SAMPLE-RFID-2004','sticker','SAMPLE-RFID-2004','SAMPLE TEST DATA','assigned','2026-09-04','2026-09-03 16:00:00','2026-09-03 16:00:00'),(4,'SAMPLE-RFID-1005','sticker','SAMPLE-RFID-1005','SAMPLE TEST DATA','assigned','2026-09-04','2026-09-03 16:00:00','2026-09-03 16:00:00');
/*!40000 ALTER TABLE `rfid_tags` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `role_permissions`
--

DROP TABLE IF EXISTS `role_permissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `role_permissions` (
  `role_id` int unsigned NOT NULL,
  `permission_id` int unsigned NOT NULL,
  `granted_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`role_id`,`permission_id`),
  KEY `role_permissions_permission_id_foreign` (`permission_id`),
  CONSTRAINT `role_permissions_permission_id_foreign` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`permission_id`) ON DELETE CASCADE,
  CONSTRAINT `role_permissions_role_id_foreign` FOREIGN KEY (`role_id`) REFERENCES `roles` (`role_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `role_permissions`
--

LOCK TABLES `role_permissions` WRITE;
/*!40000 ALTER TABLE `role_permissions` DISABLE KEYS */;
/*!40000 ALTER TABLE `role_permissions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `roles`
--

DROP TABLE IF EXISTS `roles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `roles` (
  `role_id` int unsigned NOT NULL AUTO_INCREMENT,
  `role_code` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `role_name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_system` tinyint(1) NOT NULL DEFAULT '0',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`role_id`),
  UNIQUE KEY `roles_role_code_unique` (`role_code`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `roles`
--

LOCK TABLES `roles` WRITE;
/*!40000 ALTER TABLE `roles` DISABLE KEYS */;
INSERT INTO `roles` VALUES (1,'admin','System Administrator','Overall system administrator',1,1),(2,'parking_admin','Parking Administrator','Manages parking operations',1,1),(3,'security','Security / Parking Personnel','Gate and parking staff',1,1),(4,'student','Student','Registered student user',1,1),(5,'faculty','Faculty','Registered faculty user',1,1),(6,'employee','Employee','Registered employee user',1,1),(7,'guest','Guest / Visitor','Visitor account: requests visitor parking and uses a Visitor QR pass',1,1);
/*!40000 ALTER TABLE `roles` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `system_settings`
--

DROP TABLE IF EXISTS `system_settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `system_settings` (
  `setting_id` int unsigned NOT NULL AUTO_INCREMENT,
  `setting_key` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `setting_value` text COLLATE utf8mb4_unicode_ci,
  `data_type` enum('string','integer','decimal','boolean','json','time','date') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'string',
  `setting_group` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `description` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_public` tinyint(1) NOT NULL DEFAULT '0',
  `updated_by` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`setting_id`),
  UNIQUE KEY `system_settings_setting_key_unique` (`setting_key`),
  KEY `system_settings_updated_by_foreign` (`updated_by`),
  CONSTRAINT `system_settings_updated_by_foreign` FOREIGN KEY (`updated_by`) REFERENCES `users` (`user_id`) ON DELETE SET NULL
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
-- Table structure for table `testings`
--

DROP TABLE IF EXISTS `testings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `testings` (
  `testing_id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`testing_id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `testings`
--

LOCK TABLES `testings` WRITE;
/*!40000 ALTER TABLE `testings` DISABLE KEYS */;
/*!40000 ALTER TABLE `testings` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `user_profiles`
--

DROP TABLE IF EXISTS `user_profiles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `user_profiles` (
  `profile_id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `department_id` int unsigned DEFAULT NULL,
  `first_name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `middle_name` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `last_name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `suffix` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `sex` enum('male','female') COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `birth_date` date DEFAULT NULL,
  `contact_number` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `address_line` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `city` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `province` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `program_or_position` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `year_level` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `photo_path` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `school_id_document_path` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `emergency_contact_name` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `emergency_contact_number` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`profile_id`),
  UNIQUE KEY `user_profiles_user_id_unique` (`user_id`),
  KEY `user_profiles_department_id_foreign` (`department_id`),
  CONSTRAINT `user_profiles_department_id_foreign` FOREIGN KEY (`department_id`) REFERENCES `departments` (`department_id`) ON DELETE SET NULL,
  CONSTRAINT `user_profiles_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=51 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user_profiles`
--

LOCK TABLES `user_profiles` WRITE;
/*!40000 ALTER TABLE `user_profiles` DISABLE KEYS */;
INSERT INTO `user_profiles` VALUES (1,1,9,'Demo',NULL,'Parking Staff',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Parking Staff',NULL,NULL,NULL,NULL,NULL,'2026-10-12 23:17:03','2026-10-12 23:17:03'),(32,32,1,'Andrea',NULL,'Villamor',NULL,'female','2004-03-15','0900-000--001','SAMPLE TEST DATA',NULL,NULL,'BS Computer Engineering','3rd Year',NULL,NULL,'SAMPLE TEST DATA',NULL,'2026-10-13 21:11:57','2026-10-13 21:11:57'),(33,33,2,'Bryan',NULL,'Castillo',NULL,'male','2005-07-02','0900-000--002','SAMPLE TEST DATA',NULL,NULL,'BS Biology','2nd Year',NULL,NULL,'SAMPLE TEST DATA',NULL,'2026-10-13 21:11:57','2026-10-13 21:11:57'),(34,34,3,'Celia',NULL,'Navarro',NULL,'female','1986-11-20','0900-000--001','SAMPLE TEST DATA',NULL,NULL,'Assistant Professor',NULL,NULL,NULL,'SAMPLE TEST DATA',NULL,'2026-10-13 21:11:58','2026-10-13 21:11:58'),(35,35,4,'Dennis',NULL,'Aquino',NULL,'male','1990-01-08','0900-000--002','SAMPLE TEST DATA',NULL,NULL,'Instructor',NULL,NULL,NULL,'SAMPLE TEST DATA',NULL,'2026-10-13 21:11:58','2026-10-13 21:11:58'),(36,36,7,'Elena',NULL,'Bautista',NULL,'female','1993-05-27','0900-000--001','SAMPLE TEST DATA',NULL,NULL,'HR Assistant',NULL,NULL,NULL,'SAMPLE TEST DATA',NULL,'2026-10-13 21:11:58','2026-10-13 21:11:58'),(46,46,NULL,'Demo',NULL,'Administrator',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'PASS Administrator',NULL,NULL,NULL,NULL,NULL,'2026-10-15 00:15:47','2026-10-15 00:15:47'),(47,47,NULL,'Maria',NULL,'Santos',NULL,NULL,NULL,'09171230001',NULL,NULL,NULL,'Guest / Visitor',NULL,NULL,NULL,NULL,NULL,'2026-10-15 00:15:48','2026-10-15 00:15:48'),(48,48,NULL,'Rosa',NULL,'Villanueva',NULL,NULL,NULL,'09187654321',NULL,NULL,NULL,'Guest / Visitor',NULL,NULL,NULL,NULL,NULL,'2026-10-15 00:24:20','2026-10-15 00:24:20'),(49,49,NULL,'guestito',NULL,'visitor',NULL,NULL,NULL,'09128012356',NULL,NULL,NULL,'Guest / Visitor',NULL,NULL,NULL,NULL,NULL,'2026-10-15 01:38:19','2026-10-15 01:38:19'),(50,50,NULL,'Lara',NULL,'Gomez',NULL,NULL,NULL,'09175550144',NULL,NULL,NULL,'Guest / Visitor',NULL,NULL,NULL,NULL,NULL,'2026-10-15 03:33:13','2026-10-15 03:33:13');
/*!40000 ALTER TABLE `user_profiles` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `user_roles`
--

DROP TABLE IF EXISTS `user_roles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `user_roles` (
  `user_id` bigint unsigned NOT NULL,
  `role_id` int unsigned NOT NULL,
  `assigned_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `assigned_by` bigint unsigned DEFAULT NULL,
  PRIMARY KEY (`user_id`,`role_id`),
  KEY `user_roles_role_id_foreign` (`role_id`),
  KEY `user_roles_assigned_by_foreign` (`assigned_by`),
  CONSTRAINT `user_roles_assigned_by_foreign` FOREIGN KEY (`assigned_by`) REFERENCES `users` (`user_id`) ON DELETE SET NULL,
  CONSTRAINT `user_roles_role_id_foreign` FOREIGN KEY (`role_id`) REFERENCES `roles` (`role_id`) ON DELETE CASCADE,
  CONSTRAINT `user_roles_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user_roles`
--

LOCK TABLES `user_roles` WRITE;
/*!40000 ALTER TABLE `user_roles` DISABLE KEYS */;
INSERT INTO `user_roles` VALUES (1,3,'2026-10-12 23:17:03',NULL),(32,4,'2026-10-13 21:11:57',NULL),(33,4,'2026-10-13 21:11:57',NULL),(34,5,'2026-10-13 21:11:58',NULL),(35,5,'2026-10-13 21:11:58',NULL),(36,6,'2026-10-13 21:11:58',NULL),(46,1,'2026-10-15 00:15:47',NULL),(47,7,'2026-10-15 00:15:48',NULL),(48,7,'2026-10-15 00:24:20',NULL),(49,7,'2026-10-15 01:38:19',NULL),(50,7,'2026-10-15 03:33:13',NULL);
/*!40000 ALTER TABLE `user_roles` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `users` (
  `user_id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_code` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_type` enum('student','faculty','employee','security','admin','other') COLLATE utf8mb4_unicode_ci NOT NULL,
  `account_status` enum('pending','active','suspended','deactivated') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `last_login_at` timestamp NULL DEFAULT NULL,
  `remember_token` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`user_id`),
  UNIQUE KEY `users_user_code_unique` (`user_code`),
  UNIQUE KEY `users_email_unique` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=51 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (1,'SEC-DEMO-001','security.demo@pass.local','$2y$12$AeS/Pm2HyfAMaSOLmeX9guUvsR777iivJWnQSjfT41EJFvHQ3.UWi','security','active','2026-10-12 23:17:03','2026-10-15 09:12:56',NULL,'2026-10-12 23:17:03','2026-10-15 09:12:56',NULL),(32,'SAMPLE-STU-001','sample-stu-001@sample.pass.test','$2y$12$VAhhMImSAyaSqqfErxDZFu8p/t6FBPgEt9yVfSzFCIbRIAb7N0Bxe','student','active','2026-10-13 21:11:57','2026-10-13 20:10:14',NULL,'2026-10-13 17:16:32','2026-10-13 21:11:57',NULL),(33,'SAMPLE-STU-002','sample-stu-002@sample.pass.test','$2y$12$/kTbisV.1me7gc4zzHEZDuWwIWu1SJEQBUD6ICt51D1dnBv/Klqgq','student','active','2026-10-13 21:11:57','2026-10-13 21:10:55',NULL,'2026-10-13 17:16:32','2026-10-13 21:11:57',NULL),(34,'SAMPLE-FAC-001','sample-fac-001@sample.pass.test','$2y$12$ahReY/7.hjWo2TFW0CqKz.AjEV6uIE0TtNvJX1mNNnaVTFHU9okUC','faculty','active','2026-10-13 21:11:58','2026-10-13 20:10:15',NULL,'2026-10-13 17:16:33','2026-10-13 21:11:58',NULL),(35,'SAMPLE-FAC-002','sample-fac-002@sample.pass.test','$2y$12$a5xS4eoa118KUW3RFSzB3erVNDU/xze.pUVm35fHjFIoZH10284WG','faculty','active','2026-10-13 21:11:58','2026-10-13 20:10:15',NULL,'2026-10-13 17:16:33','2026-10-13 21:11:58',NULL),(36,'SAMPLE-EMP-001','sample-emp-001@sample.pass.test','$2y$12$Whi99Tuqxu0nFDvmP8k5J.SpR3y9iT31yDIZgO3WYNdCAMbLb7GU.','employee','active','2026-10-13 21:11:58','2026-10-13 21:10:31',NULL,'2026-10-13 17:16:34','2026-10-13 21:11:58',NULL),(46,'ADM-DEMO-001','admin.demo@pass.local','$2y$12$wW8WuY/gykeVTDk62SoBEuURUy3td8fV2mKxeJeX9zOFTVpFDXNXO','admin','active','2026-10-15 00:15:47','2026-10-15 08:38:57',NULL,'2026-10-15 00:15:47','2026-10-15 08:38:57',NULL),(47,'GST-DEMO-001','guest.demo@pass.test','$2y$12$mZwxVEj2jqhEEnTtGX8cveDuBD3V2loLERC1kr2Jw4gF4Bjg0M2Wa','other','active','2026-10-15 00:15:48',NULL,NULL,'2026-10-15 00:15:48','2026-10-15 00:15:48',NULL),(48,'GST-M9SC2UDB','rosa.villanueva.test@example.com','$2y$12$4rGtriSCeX.ju.PxerMu4.lJGa.ZDT9H9kGaiTolJ//NK3fkKuKmS','other','active',NULL,'2026-10-15 00:30:28',NULL,'2026-10-15 00:24:20','2026-10-15 00:30:28',NULL),(49,'GST-20UF3BER','guest@pass.com','$2y$12$bm21ym.MXBnY7I0HN3Twle4WYEghqD5VLv2IJcLEd2oOfYREw9M0G','other','active',NULL,'2026-10-15 01:39:01',NULL,'2026-10-15 01:38:19','2026-10-15 01:39:01',NULL),(50,'GST-BW3KZLQ4','lara.gomez.test@example.com','$2y$12$2fVDYxhwgrrRRtfMQ1Xjs.f8eGG3Y11xzRBN3mHz8WyrmxbuV5PXi','other','active',NULL,'2026-10-15 03:35:35',NULL,'2026-10-15 03:33:13','2026-10-15 03:35:35',NULL);
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vehicle_authorizations`
--

DROP TABLE IF EXISTS `vehicle_authorizations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vehicle_authorizations` (
  `authorization_id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `vehicle_id` bigint unsigned NOT NULL,
  `parking_area_id` int unsigned DEFAULT NULL,
  `sticker_number` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `authorization_status` enum('pending','approved','rejected','revoked','expired','suspended') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `valid_from` date DEFAULT NULL,
  `valid_until` date DEFAULT NULL,
  `requested_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `reviewed_by` bigint unsigned DEFAULT NULL,
  `reviewed_at` datetime DEFAULT NULL,
  `remarks` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `active_flag` tinyint GENERATED ALWAYS AS (if((`authorization_status` = _utf8mb4'approved'),1,NULL)) STORED,
  PRIMARY KEY (`authorization_id`),
  UNIQUE KEY `vehicle_authorizations_sticker_number_unique` (`sticker_number`),
  UNIQUE KEY `uq_vehicle_one_active_auth` (`vehicle_id`,`active_flag`),
  KEY `vehicle_authorizations_parking_area_id_foreign` (`parking_area_id`),
  KEY `vehicle_authorizations_reviewed_by_foreign` (`reviewed_by`),
  CONSTRAINT `vehicle_authorizations_parking_area_id_foreign` FOREIGN KEY (`parking_area_id`) REFERENCES `parking_areas` (`parking_area_id`) ON DELETE SET NULL,
  CONSTRAINT `vehicle_authorizations_reviewed_by_foreign` FOREIGN KEY (`reviewed_by`) REFERENCES `users` (`user_id`) ON DELETE SET NULL,
  CONSTRAINT `vehicle_authorizations_vehicle_id_foreign` FOREIGN KEY (`vehicle_id`) REFERENCES `vehicles` (`vehicle_id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=75 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vehicle_authorizations`
--

LOCK TABLES `vehicle_authorizations` WRITE;
/*!40000 ALTER TABLE `vehicle_authorizations` DISABLE KEYS */;
INSERT INTO `vehicle_authorizations` (`authorization_id`, `vehicle_id`, `parking_area_id`, `sticker_number`, `authorization_status`, `valid_from`, `valid_until`, `requested_at`, `reviewed_by`, `reviewed_at`, `remarks`, `created_at`, `updated_at`) VALUES (35,51,NULL,'SAMPLE-STK-1001','suspended','2026-09-04','2027-08-14','2026-08-31 09:00:00',1,'2026-10-15 11:49:11','wrong parking','2026-08-30 16:00:00','2026-10-15 03:49:11'),(36,52,NULL,NULL,'pending',NULL,NULL,'2026-10-12 09:00:00',NULL,NULL,NULL,'2026-08-30 16:00:00','2026-09-02 16:00:00'),(37,53,NULL,'SAMPLE-STK-1003','suspended','2026-09-04','2027-08-14','2026-08-31 09:00:00',1,'2026-10-11 14:00:00','SAMPLE: Suspended pending review of a parking violation (test data).','2026-08-30 16:00:00','2026-10-10 16:00:00'),(38,54,NULL,NULL,'rejected',NULL,NULL,'2026-08-31 09:00:00',1,'2026-09-03 14:00:00','SAMPLE: OR/CR details did not match the vehicle (test data).','2026-08-30 16:00:00','2026-09-02 16:00:00'),(39,55,NULL,'SAMPLE-STK-1005','approved','2026-09-04','2027-08-14','2026-08-31 09:00:00',1,'2026-09-03 14:00:00',NULL,'2026-08-30 16:00:00','2026-09-02 16:00:00'),(40,76,NULL,'SAMPLE-STK-2004','approved','2026-09-04','2027-08-14','2026-08-31 09:00:00',1,'2026-09-03 14:00:00',NULL,'2026-08-30 16:00:00','2026-09-02 16:00:00');
/*!40000 ALTER TABLE `vehicle_authorizations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vehicle_driver`
--

DROP TABLE IF EXISTS `vehicle_driver`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vehicle_driver` (
  `vehicle_id` bigint unsigned NOT NULL,
  `driver_id` bigint unsigned NOT NULL,
  `relationship` enum('owner','authorized_driver','family','company') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'owner',
  `is_primary` tinyint(1) NOT NULL DEFAULT '0',
  `authorized_from` date DEFAULT NULL,
  `authorized_until` date DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`vehicle_id`,`driver_id`),
  KEY `vehicle_driver_driver_id_foreign` (`driver_id`),
  CONSTRAINT `vehicle_driver_driver_id_foreign` FOREIGN KEY (`driver_id`) REFERENCES `drivers` (`driver_id`) ON DELETE CASCADE,
  CONSTRAINT `vehicle_driver_vehicle_id_foreign` FOREIGN KEY (`vehicle_id`) REFERENCES `vehicles` (`vehicle_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vehicle_driver`
--

LOCK TABLES `vehicle_driver` WRITE;
/*!40000 ALTER TABLE `vehicle_driver` DISABLE KEYS */;
INSERT INTO `vehicle_driver` VALUES (51,44,'owner',1,NULL,NULL,'2026-08-29 16:00:00','2026-08-29 16:00:00'),(52,45,'owner',1,NULL,NULL,'2026-08-29 16:00:00','2026-08-29 16:00:00'),(53,46,'owner',1,NULL,NULL,'2026-08-29 16:00:00','2026-08-29 16:00:00'),(54,47,'owner',1,NULL,NULL,'2026-08-29 16:00:00','2026-08-29 16:00:00'),(55,48,'owner',1,NULL,NULL,'2026-08-29 16:00:00','2026-08-29 16:00:00'),(76,47,'owner',1,NULL,NULL,'2026-08-29 16:00:00','2026-08-29 16:00:00');
/*!40000 ALTER TABLE `vehicle_driver` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vehicle_types`
--

DROP TABLE IF EXISTS `vehicle_types`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vehicle_types` (
  `vehicle_type_id` tinyint unsigned NOT NULL AUTO_INCREMENT,
  `type_code` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL,
  `type_name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `requires_assigned_slot` tinyint(1) NOT NULL DEFAULT '0',
  `parking_area_id` int unsigned DEFAULT NULL,
  PRIMARY KEY (`vehicle_type_id`),
  UNIQUE KEY `vehicle_types_type_code_unique` (`type_code`),
  KEY `vehicle_types_parking_area_id_foreign` (`parking_area_id`),
  CONSTRAINT `vehicle_types_parking_area_id_foreign` FOREIGN KEY (`parking_area_id`) REFERENCES `parking_areas` (`parking_area_id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vehicle_types`
--

LOCK TABLES `vehicle_types` WRITE;
/*!40000 ALTER TABLE `vehicle_types` DISABLE KEYS */;
INSERT INTO `vehicle_types` VALUES (1,'sedan','Sedan',0,6),(2,'suv','SUV',0,6),(3,'hatchback','Hatchback',0,6),(4,'pickup','Pickup',0,6),(5,'van','Van',0,6),(6,'motorcycle','Motorcycle',0,7);
/*!40000 ALTER TABLE `vehicle_types` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vehicles`
--

DROP TABLE IF EXISTS `vehicles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vehicles` (
  `vehicle_id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `owner_user_id` bigint unsigned DEFAULT NULL,
  `owner_visitor_id` bigint unsigned DEFAULT NULL,
  `vehicle_type_id` tinyint unsigned NOT NULL,
  `plate_number` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `make` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `model` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `color` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `year_model` smallint unsigned DEFAULT NULL,
  `or_number` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `cr_number` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `chassis_number` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `registration_status` enum('registered','archived') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'registered',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`vehicle_id`),
  UNIQUE KEY `vehicles_plate_number_unique` (`plate_number`),
  KEY `vehicles_owner_user_id_foreign` (`owner_user_id`),
  KEY `vehicles_owner_visitor_id_foreign` (`owner_visitor_id`),
  KEY `vehicles_vehicle_type_id_foreign` (`vehicle_type_id`),
  CONSTRAINT `vehicles_owner_user_id_foreign` FOREIGN KEY (`owner_user_id`) REFERENCES `users` (`user_id`),
  CONSTRAINT `vehicles_owner_visitor_id_foreign` FOREIGN KEY (`owner_visitor_id`) REFERENCES `visitors` (`visitor_id`),
  CONSTRAINT `vehicles_vehicle_type_id_foreign` FOREIGN KEY (`vehicle_type_id`) REFERENCES `vehicle_types` (`vehicle_type_id`),
  CONSTRAINT `chk_vehicle_one_owner` CHECK (((`owner_user_id` is null) <> (`owner_visitor_id` is null)))
) ENGINE=InnoDB AUTO_INCREMENT=148 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vehicles`
--

LOCK TABLES `vehicles` WRITE;
/*!40000 ALTER TABLE `vehicles` DISABLE KEYS */;
INSERT INTO `vehicles` VALUES (51,32,NULL,1,'SMP 1001','Toyota','Vios','White',2021,'SAMPLE-OR-1001','SAMPLE-CR-1001',NULL,'registered','2026-08-29 16:00:00','2026-08-29 16:00:00',NULL),(52,33,NULL,6,'SMP 1002','Honda','Click 125i','Red',2023,'SAMPLE-OR-1002','SAMPLE-CR-1002',NULL,'registered','2026-08-29 16:00:00','2026-08-29 16:00:00',NULL),(53,34,NULL,2,'SMP 1003','Mitsubishi','Montero Sport','Gray',2019,'SAMPLE-OR-1003','SAMPLE-CR-1003',NULL,'registered','2026-08-29 16:00:00','2026-08-29 16:00:00',NULL),(54,35,NULL,4,'SMP 1004','Ford','Ranger','Blue',2020,'SAMPLE-OR-1004','SAMPLE-CR-1004',NULL,'registered','2026-08-29 16:00:00','2026-08-29 16:00:00',NULL),(55,36,NULL,5,'SMP 1005','Toyota','Innova','Silver',2022,'SAMPLE-OR-1005','SAMPLE-CR-1005',NULL,'registered','2026-08-29 16:00:00','2026-08-29 16:00:00',NULL),(76,35,NULL,6,'SMP 2004','Yamaha','Mio Gear','Black',2024,'SAMPLE-OR-2004','SAMPLE-CR-2004',NULL,'registered','2026-08-29 16:00:00','2026-08-29 16:00:00',NULL),(135,NULL,61,4,'SMP V001','Isuzu','Traviz',NULL,NULL,NULL,NULL,NULL,'registered','2026-10-12 16:00:00','2026-10-12 16:00:00',NULL),(136,NULL,62,1,'SMP V002','Nissan','Almera',NULL,NULL,NULL,NULL,NULL,'registered','2026-10-12 16:00:00','2026-10-12 16:00:00',NULL),(137,NULL,63,1,'SMP V003','Hyundai','Accent',NULL,NULL,NULL,NULL,NULL,'registered','2026-10-12 16:00:00','2026-10-12 16:00:00',NULL),(138,NULL,64,6,'SMP V004','Yamaha','NMAX',NULL,NULL,NULL,NULL,NULL,'registered','2026-10-12 16:00:00','2026-10-12 16:00:00',NULL),(139,NULL,65,1,'DEMO 4821','Toyota',NULL,NULL,NULL,NULL,NULL,NULL,'registered','2026-10-14 21:17:59','2026-10-14 21:17:59',NULL),(140,NULL,65,1,'DEMO 4822',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'registered','2026-10-14 21:22:32','2026-10-14 21:22:32',NULL),(141,NULL,65,1,'DEMO 4823',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'registered','2026-10-14 21:22:41','2026-10-14 21:22:41',NULL),(142,NULL,65,1,'DEMO 4824',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'registered','2026-10-14 21:25:27','2026-10-14 21:25:27',NULL),(143,NULL,66,1,'GUEST 001','Toyota','Vios','White',NULL,NULL,NULL,NULL,'registered','2026-10-15 00:15:48','2026-10-15 00:15:48',NULL),(144,NULL,67,1,'ROSA 2026','Honda','City',NULL,NULL,NULL,NULL,NULL,'registered','2026-10-15 00:24:38','2026-10-15 00:24:38',NULL),(145,NULL,67,1,'ROSA 2027','Honda','City',NULL,NULL,NULL,NULL,NULL,'registered','2026-10-15 00:24:47','2026-10-15 00:24:47',NULL),(146,NULL,68,2,'CAR 123','suzuki',NULL,NULL,NULL,NULL,NULL,NULL,'registered','2026-10-15 02:12:58','2026-10-15 02:12:58',NULL),(147,NULL,69,1,'LARA 3101','Honda','City',NULL,NULL,NULL,NULL,NULL,'registered','2026-10-15 03:33:25','2026-10-15 03:33:25',NULL);
/*!40000 ALTER TABLE `vehicles` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `violation_evidences`
--

DROP TABLE IF EXISTS `violation_evidences`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `violation_evidences` (
  `evidence_id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `violation_id` bigint unsigned NOT NULL,
  `file_path` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `file_type` enum('image','video','document') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'image',
  `caption` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `uploaded_by` bigint unsigned DEFAULT NULL,
  `uploaded_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`evidence_id`),
  KEY `violation_evidences_violation_id_foreign` (`violation_id`),
  KEY `violation_evidences_uploaded_by_foreign` (`uploaded_by`),
  CONSTRAINT `violation_evidences_uploaded_by_foreign` FOREIGN KEY (`uploaded_by`) REFERENCES `users` (`user_id`),
  CONSTRAINT `violation_evidences_violation_id_foreign` FOREIGN KEY (`violation_id`) REFERENCES `parking_violations` (`violation_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `violation_evidences`
--

LOCK TABLES `violation_evidences` WRITE;
/*!40000 ALTER TABLE `violation_evidences` DISABLE KEYS */;
/*!40000 ALTER TABLE `violation_evidences` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `violation_types`
--

DROP TABLE IF EXISTS `violation_types`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `violation_types` (
  `violation_type_id` int unsigned NOT NULL AUTO_INCREMENT,
  `violation_code` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL,
  `violation_name` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `default_penalty` decimal(10,2) NOT NULL DEFAULT '0.00',
  `severity` enum('minor','major','severe') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'minor',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`violation_type_id`),
  UNIQUE KEY `violation_types_violation_code_unique` (`violation_code`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `violation_types`
--

LOCK TABLES `violation_types` WRITE;
/*!40000 ALTER TABLE `violation_types` DISABLE KEYS */;
INSERT INTO `violation_types` VALUES (1,'WRONG_SLOT','Parked in Wrong Slot','Vehicle was observed in a slot other than the one assigned at entry.',0.00,'minor',1),(2,'WRONG_AREA','Parked in Wrong Parking Area','Vehicle was observed in a parking area not meant for its vehicle type.',0.00,'major',1),(3,'UNAUTHORIZED_PARKING','Unauthorized Parking','Vehicle parked without a valid authorization or pass.',0.00,'major',1),(4,'OVERSTAYING','Overstaying','Vehicle stayed beyond the permitted time.',0.00,'minor',1),(5,'INVALID_AUTHORIZATION','Invalid Vehicle Authorization','Vehicle used an expired, suspended or rejected authorization.',0.00,'major',1),(6,'TAILGATING','Tailgating / Improper Gate Access','Vehicle followed another through the gate without its own access check.',0.00,'severe',1),(7,'BLOCKING_LANE','Blocking Access Lane','Vehicle obstructed a driveway, lane or gate.',0.00,'minor',1),(8,'OTHER','Other','Other parking incident.',0.00,'minor',1);
/*!40000 ALTER TABLE `violation_types` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `visitor_requests`
--

DROP TABLE IF EXISTS `visitor_requests`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `visitor_requests` (
  `request_id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `visitor_id` bigint unsigned NOT NULL,
  `host_user_id` bigint unsigned DEFAULT NULL,
  `vehicle_id` bigint unsigned DEFAULT NULL,
  `purpose` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `destination_area_id` int unsigned DEFAULT NULL,
  `requested_valid_from` datetime NOT NULL,
  `requested_valid_until` datetime NOT NULL,
  `request_status` enum('pending','approved','rejected','cancelled','expired') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `reviewed_by` bigint unsigned DEFAULT NULL,
  `reviewed_at` datetime DEFAULT NULL,
  `review_remarks` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`request_id`),
  KEY `visitor_requests_visitor_id_foreign` (`visitor_id`),
  KEY `visitor_requests_host_user_id_foreign` (`host_user_id`),
  KEY `visitor_requests_vehicle_id_foreign` (`vehicle_id`),
  KEY `visitor_requests_destination_area_id_foreign` (`destination_area_id`),
  KEY `visitor_requests_reviewed_by_foreign` (`reviewed_by`),
  CONSTRAINT `visitor_requests_destination_area_id_foreign` FOREIGN KEY (`destination_area_id`) REFERENCES `parking_areas` (`parking_area_id`),
  CONSTRAINT `visitor_requests_host_user_id_foreign` FOREIGN KEY (`host_user_id`) REFERENCES `users` (`user_id`),
  CONSTRAINT `visitor_requests_reviewed_by_foreign` FOREIGN KEY (`reviewed_by`) REFERENCES `users` (`user_id`),
  CONSTRAINT `visitor_requests_vehicle_id_foreign` FOREIGN KEY (`vehicle_id`) REFERENCES `vehicles` (`vehicle_id`),
  CONSTRAINT `visitor_requests_visitor_id_foreign` FOREIGN KEY (`visitor_id`) REFERENCES `visitors` (`visitor_id`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `visitor_requests`
--

LOCK TABLES `visitor_requests` WRITE;
/*!40000 ALTER TABLE `visitor_requests` DISABLE KEYS */;
INSERT INTO `visitor_requests` VALUES (1,65,NULL,139,'Campus tour · Visiting: Admissions Office',6,'2026-10-15 05:17:00','2026-10-15 09:17:00','approved',1,'2026-10-15 05:20:24',NULL,'2026-10-14 21:17:59','2026-10-14 21:20:24'),(2,65,NULL,140,'Delivery · Visiting: Accounting',6,'2026-10-15 05:22:00','2026-10-15 09:22:00','rejected',1,'2026-10-15 05:22:36','No appointment on record','2026-10-14 21:22:32','2026-10-14 21:22:36'),(3,65,NULL,141,'Meeting · Visiting: Dean',6,'2026-10-15 05:22:00','2026-10-15 09:22:00','approved',1,'2026-10-15 05:22:45',NULL,'2026-10-14 21:22:41','2026-10-14 21:22:45'),(4,65,NULL,142,'Interview · Visiting: HR Office',6,'2026-10-15 05:25:00','2026-10-15 09:25:00','approved',1,'2026-10-15 05:25:31',NULL,'2026-10-14 21:25:27','2026-10-14 21:25:31'),(5,66,NULL,143,'Campus visit (demo request) · Visiting: Admissions Office',6,'2026-10-15 08:00:00','2026-10-15 16:00:00','pending',NULL,NULL,NULL,'2026-10-15 00:15:48','2026-10-15 00:15:48'),(6,67,NULL,144,'Document pickup at the Registrar · Visiting: Registrar Office',6,'2026-10-15 08:14:00','2026-10-15 11:24:00','approved',46,'2026-10-15 08:27:02',NULL,'2026-10-15 00:24:58','2026-10-15 00:27:02'),(7,67,NULL,145,'Second visit for rejection test · Visiting: Registrar Office',6,'2026-10-15 08:15:00','2026-10-15 11:25:00','rejected',46,'2026-10-15 08:27:06','The Registrar is closed for a holiday','2026-10-15 00:25:10','2026-10-15 00:27:06'),(8,68,NULL,146,'guest speaker · Visiting: convention',6,'2026-10-29 00:30:00','2026-10-29 02:20:00','pending',NULL,NULL,NULL,'2026-10-15 02:15:56','2026-10-15 02:15:56'),(9,69,NULL,147,'Thesis consultation · Visiting: Registrar Office',6,'2026-10-15 11:23:00','2026-10-15 14:33:00','approved',46,'2026-10-15 11:33:51',NULL,'2026-10-15 03:33:30','2026-10-15 03:33:51');
/*!40000 ALTER TABLE `visitor_requests` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `visitors`
--

DROP TABLE IF EXISTS `visitors`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `visitors` (
  `visitor_id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned DEFAULT NULL,
  `first_name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `middle_name` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `last_name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `contact_number` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `id_type` enum('drivers_license','national_id','company_id','school_id','passport','other') COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `id_number` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `affiliation` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_blacklisted` tinyint(1) NOT NULL DEFAULT '0',
  `blacklist_reason` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`visitor_id`),
  UNIQUE KEY `visitors_user_id_unique` (`user_id`),
  CONSTRAINT `visitors_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=70 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `visitors`
--

LOCK TABLES `visitors` WRITE;
/*!40000 ALTER TABLE `visitors` DISABLE KEYS */;
INSERT INTO `visitors` VALUES (61,NULL,'Liza',NULL,'Ramos','0900-111-0001','visitor.liza.ramos@sample.pass.test','other','SAMPLE-ID-V1','Sample Supplier Co. (test)',0,NULL,'2026-10-12 16:00:00','2026-10-12 16:00:00'),(62,NULL,'Marco',NULL,'Dizon','0900-111-0002','visitor.marco.dizon@sample.pass.test','other','SAMPLE-ID-V2','Parent visitor (test)',0,NULL,'2026-10-12 16:00:00','2026-10-12 16:00:00'),(63,NULL,'Nina',NULL,'Ocampo','0900-111-0003','visitor.nina.ocampo@sample.pass.test','other','SAMPLE-ID-V3','Guest lecturer (test)',0,NULL,'2026-10-12 16:00:00','2026-10-12 16:00:00'),(64,NULL,'Oscar',NULL,'Velasco','0900-111-0004','visitor.oscar.velasco@sample.pass.test','other','SAMPLE-ID-V4','Delivery rider (test)',0,NULL,'2026-10-12 16:00:00','2026-10-12 16:00:00'),(65,NULL,'Carla',NULL,'Mendoza','09175550101',NULL,NULL,NULL,'PASS browser test',0,NULL,'2026-10-14 21:17:59','2026-10-14 21:17:59'),(66,47,'Maria',NULL,'Santos','09171230001','guest.demo@pass.test',NULL,NULL,NULL,0,NULL,'2026-10-15 00:15:48','2026-10-15 00:15:48'),(67,48,'Rosa',NULL,'Villanueva','09187654321','rosa.villanueva.test@example.com',NULL,NULL,NULL,0,NULL,'2026-10-15 00:24:20','2026-10-15 00:24:20'),(68,49,'guestito',NULL,'visitor','09128012356','guest@pass.com',NULL,NULL,NULL,0,NULL,'2026-10-15 01:38:19','2026-10-15 01:38:19'),(69,50,'Lara',NULL,'Gomez','09175550144','lara.gomez.test@example.com',NULL,NULL,NULL,0,NULL,'2026-10-15 03:33:13','2026-10-15 03:33:13');
/*!40000 ALTER TABLE `visitors` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping routines for database 'projectsystem'
--
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-10-23 15:29:29
