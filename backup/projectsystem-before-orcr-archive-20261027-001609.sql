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
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_unicode_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER `pass_history_access_events_insert` AFTER INSERT ON `access_events` FOR EACH ROW BEGIN INSERT INTO record_history (record_type, record_id, operation, actor_user_id, actor_label, source, ip_address, before_values, after_values, recorded_at)
                    VALUES ('access_events', CONCAT_WS(':', NEW.`event_id`), 'created', @pass_history_actor, LEFT(@pass_history_label, 100), LEFT(@pass_history_source, 150), LEFT(@pass_history_ip, 45), NULL, JSON_OBJECT('event_id', NEW.`event_id`, 'event_datetime', NEW.`event_datetime`, 'direction', NEW.`direction`, 'access_method', NEW.`access_method`, 'gate_id', NEW.`gate_id`, 'reader_id', NEW.`reader_id`, 'rfid_id', NEW.`rfid_id`, 'rfid_assignment_id', NEW.`rfid_assignment_id`, 'qr_pass_id', NEW.`qr_pass_id`, 'user_id', NEW.`user_id`, 'visitor_id', NEW.`visitor_id`, 'vehicle_id', NEW.`vehicle_id`, 'auth_result', NEW.`auth_result`, 'denial_reason', NEW.`denial_reason`, 'processed_by_user_id', NEW.`processed_by_user_id`, 'created_at', NEW.`created_at`, 'updated_at', NEW.`updated_at`), CURRENT_TIMESTAMP(6)); END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_unicode_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER `pass_history_access_events_update` AFTER UPDATE ON `access_events` FOR EACH ROW BEGIN IF NOT (OLD.`event_id` <=> NEW.`event_id`) OR NOT (OLD.`event_datetime` <=> NEW.`event_datetime`) OR NOT (OLD.`direction` <=> NEW.`direction`) OR NOT (OLD.`access_method` <=> NEW.`access_method`) OR NOT (OLD.`gate_id` <=> NEW.`gate_id`) OR NOT (OLD.`reader_id` <=> NEW.`reader_id`) OR NOT (OLD.`rfid_id` <=> NEW.`rfid_id`) OR NOT (OLD.`rfid_assignment_id` <=> NEW.`rfid_assignment_id`) OR NOT (OLD.`qr_pass_id` <=> NEW.`qr_pass_id`) OR NOT (OLD.`user_id` <=> NEW.`user_id`) OR NOT (OLD.`visitor_id` <=> NEW.`visitor_id`) OR NOT (OLD.`vehicle_id` <=> NEW.`vehicle_id`) OR NOT (OLD.`auth_result` <=> NEW.`auth_result`) OR NOT (OLD.`denial_reason` <=> NEW.`denial_reason`) OR NOT (OLD.`processed_by_user_id` <=> NEW.`processed_by_user_id`) OR NOT (OLD.`created_at` <=> NEW.`created_at`) OR NOT (OLD.`updated_at` <=> NEW.`updated_at`) THEN INSERT INTO record_history (record_type, record_id, operation, actor_user_id, actor_label, source, ip_address, before_values, after_values, recorded_at)
                    VALUES ('access_events', CONCAT_WS(':', NEW.`event_id`), 'updated', @pass_history_actor, LEFT(@pass_history_label, 100), LEFT(@pass_history_source, 150), LEFT(@pass_history_ip, 45), JSON_OBJECT('event_id', OLD.`event_id`, 'event_datetime', OLD.`event_datetime`, 'direction', OLD.`direction`, 'access_method', OLD.`access_method`, 'gate_id', OLD.`gate_id`, 'reader_id', OLD.`reader_id`, 'rfid_id', OLD.`rfid_id`, 'rfid_assignment_id', OLD.`rfid_assignment_id`, 'qr_pass_id', OLD.`qr_pass_id`, 'user_id', OLD.`user_id`, 'visitor_id', OLD.`visitor_id`, 'vehicle_id', OLD.`vehicle_id`, 'auth_result', OLD.`auth_result`, 'denial_reason', OLD.`denial_reason`, 'processed_by_user_id', OLD.`processed_by_user_id`, 'created_at', OLD.`created_at`, 'updated_at', OLD.`updated_at`), JSON_OBJECT('event_id', NEW.`event_id`, 'event_datetime', NEW.`event_datetime`, 'direction', NEW.`direction`, 'access_method', NEW.`access_method`, 'gate_id', NEW.`gate_id`, 'reader_id', NEW.`reader_id`, 'rfid_id', NEW.`rfid_id`, 'rfid_assignment_id', NEW.`rfid_assignment_id`, 'qr_pass_id', NEW.`qr_pass_id`, 'user_id', NEW.`user_id`, 'visitor_id', NEW.`visitor_id`, 'vehicle_id', NEW.`vehicle_id`, 'auth_result', NEW.`auth_result`, 'denial_reason', NEW.`denial_reason`, 'processed_by_user_id', NEW.`processed_by_user_id`, 'created_at', NEW.`created_at`, 'updated_at', NEW.`updated_at`), CURRENT_TIMESTAMP(6)); END IF; END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_unicode_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER `pass_history_access_events_delete` AFTER DELETE ON `access_events` FOR EACH ROW BEGIN INSERT INTO record_history (record_type, record_id, operation, actor_user_id, actor_label, source, ip_address, before_values, after_values, recorded_at)
                    VALUES ('access_events', CONCAT_WS(':', OLD.`event_id`), 'deleted', @pass_history_actor, LEFT(@pass_history_label, 100), LEFT(@pass_history_source, 150), LEFT(@pass_history_ip, 45), JSON_OBJECT('event_id', OLD.`event_id`, 'event_datetime', OLD.`event_datetime`, 'direction', OLD.`direction`, 'access_method', OLD.`access_method`, 'gate_id', OLD.`gate_id`, 'reader_id', OLD.`reader_id`, 'rfid_id', OLD.`rfid_id`, 'rfid_assignment_id', OLD.`rfid_assignment_id`, 'qr_pass_id', OLD.`qr_pass_id`, 'user_id', OLD.`user_id`, 'visitor_id', OLD.`visitor_id`, 'vehicle_id', OLD.`vehicle_id`, 'auth_result', OLD.`auth_result`, 'denial_reason', OLD.`denial_reason`, 'processed_by_user_id', OLD.`processed_by_user_id`, 'created_at', OLD.`created_at`, 'updated_at', OLD.`updated_at`), NULL, CURRENT_TIMESTAMP(6)); END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;

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
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_unicode_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER `pass_history_access_gates_insert` AFTER INSERT ON `access_gates` FOR EACH ROW BEGIN INSERT INTO record_history (record_type, record_id, operation, actor_user_id, actor_label, source, ip_address, before_values, after_values, recorded_at)
                    VALUES ('access_gates', CONCAT_WS(':', NEW.`gate_id`), 'created', @pass_history_actor, LEFT(@pass_history_label, 100), LEFT(@pass_history_source, 150), LEFT(@pass_history_ip, 45), NULL, JSON_OBJECT('gate_id', NEW.`gate_id`, 'parking_area_id', NEW.`parking_area_id`, 'gate_code', NEW.`gate_code`, 'gate_name', NEW.`gate_name`, 'gate_type', NEW.`gate_type`, 'gate_status', NEW.`gate_status`, 'created_at', NEW.`created_at`, 'updated_at', NEW.`updated_at`), CURRENT_TIMESTAMP(6)); END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_unicode_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER `pass_history_access_gates_update` AFTER UPDATE ON `access_gates` FOR EACH ROW BEGIN IF NOT (OLD.`gate_id` <=> NEW.`gate_id`) OR NOT (OLD.`parking_area_id` <=> NEW.`parking_area_id`) OR NOT (OLD.`gate_code` <=> NEW.`gate_code`) OR NOT (OLD.`gate_name` <=> NEW.`gate_name`) OR NOT (OLD.`gate_type` <=> NEW.`gate_type`) OR NOT (OLD.`gate_status` <=> NEW.`gate_status`) OR NOT (OLD.`created_at` <=> NEW.`created_at`) OR NOT (OLD.`updated_at` <=> NEW.`updated_at`) THEN INSERT INTO record_history (record_type, record_id, operation, actor_user_id, actor_label, source, ip_address, before_values, after_values, recorded_at)
                    VALUES ('access_gates', CONCAT_WS(':', NEW.`gate_id`), 'updated', @pass_history_actor, LEFT(@pass_history_label, 100), LEFT(@pass_history_source, 150), LEFT(@pass_history_ip, 45), JSON_OBJECT('gate_id', OLD.`gate_id`, 'parking_area_id', OLD.`parking_area_id`, 'gate_code', OLD.`gate_code`, 'gate_name', OLD.`gate_name`, 'gate_type', OLD.`gate_type`, 'gate_status', OLD.`gate_status`, 'created_at', OLD.`created_at`, 'updated_at', OLD.`updated_at`), JSON_OBJECT('gate_id', NEW.`gate_id`, 'parking_area_id', NEW.`parking_area_id`, 'gate_code', NEW.`gate_code`, 'gate_name', NEW.`gate_name`, 'gate_type', NEW.`gate_type`, 'gate_status', NEW.`gate_status`, 'created_at', NEW.`created_at`, 'updated_at', NEW.`updated_at`), CURRENT_TIMESTAMP(6)); END IF; END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_unicode_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER `pass_history_access_gates_delete` AFTER DELETE ON `access_gates` FOR EACH ROW BEGIN INSERT INTO record_history (record_type, record_id, operation, actor_user_id, actor_label, source, ip_address, before_values, after_values, recorded_at)
                    VALUES ('access_gates', CONCAT_WS(':', OLD.`gate_id`), 'deleted', @pass_history_actor, LEFT(@pass_history_label, 100), LEFT(@pass_history_source, 150), LEFT(@pass_history_ip, 45), JSON_OBJECT('gate_id', OLD.`gate_id`, 'parking_area_id', OLD.`parking_area_id`, 'gate_code', OLD.`gate_code`, 'gate_name', OLD.`gate_name`, 'gate_type', OLD.`gate_type`, 'gate_status', OLD.`gate_status`, 'created_at', OLD.`created_at`, 'updated_at', OLD.`updated_at`), NULL, CURRENT_TIMESTAMP(6)); END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;

--
-- Table structure for table `audit_alert_reviews`
--

DROP TABLE IF EXISTS `audit_alert_reviews`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `audit_alert_reviews` (
  `alert_key` char(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  `rule` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  `severity` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `reviewed_by` bigint unsigned NOT NULL,
  `note` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `reviewed_at` timestamp NOT NULL,
  PRIMARY KEY (`alert_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `audit_alert_reviews`
--

LOCK TABLES `audit_alert_reviews` WRITE;
/*!40000 ALTER TABLE `audit_alert_reviews` DISABLE KEYS */;
/*!40000 ALTER TABLE `audit_alert_reviews` ENABLE KEYS */;
UNLOCK TABLES;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_unicode_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER `pass_history_audit_alert_reviews_insert` AFTER INSERT ON `audit_alert_reviews` FOR EACH ROW BEGIN INSERT INTO record_history (record_type, record_id, operation, actor_user_id, actor_label, source, ip_address, before_values, after_values, recorded_at)
                    VALUES ('audit_alert_reviews', CONCAT_WS(':', NEW.`alert_key`), 'created', @pass_history_actor, LEFT(@pass_history_label, 100), LEFT(@pass_history_source, 150), LEFT(@pass_history_ip, 45), NULL, JSON_OBJECT('alert_key', NEW.`alert_key`, 'rule', NEW.`rule`, 'severity', NEW.`severity`, 'reviewed_by', NEW.`reviewed_by`, 'note', NEW.`note`, 'reviewed_at', NEW.`reviewed_at`), CURRENT_TIMESTAMP(6)); END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_unicode_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER `pass_history_audit_alert_reviews_update` AFTER UPDATE ON `audit_alert_reviews` FOR EACH ROW BEGIN IF NOT (OLD.`alert_key` <=> NEW.`alert_key`) OR NOT (OLD.`rule` <=> NEW.`rule`) OR NOT (OLD.`severity` <=> NEW.`severity`) OR NOT (OLD.`reviewed_by` <=> NEW.`reviewed_by`) OR NOT (OLD.`note` <=> NEW.`note`) OR NOT (OLD.`reviewed_at` <=> NEW.`reviewed_at`) THEN INSERT INTO record_history (record_type, record_id, operation, actor_user_id, actor_label, source, ip_address, before_values, after_values, recorded_at)
                    VALUES ('audit_alert_reviews', CONCAT_WS(':', NEW.`alert_key`), 'updated', @pass_history_actor, LEFT(@pass_history_label, 100), LEFT(@pass_history_source, 150), LEFT(@pass_history_ip, 45), JSON_OBJECT('alert_key', OLD.`alert_key`, 'rule', OLD.`rule`, 'severity', OLD.`severity`, 'reviewed_by', OLD.`reviewed_by`, 'note', OLD.`note`, 'reviewed_at', OLD.`reviewed_at`), JSON_OBJECT('alert_key', NEW.`alert_key`, 'rule', NEW.`rule`, 'severity', NEW.`severity`, 'reviewed_by', NEW.`reviewed_by`, 'note', NEW.`note`, 'reviewed_at', NEW.`reviewed_at`), CURRENT_TIMESTAMP(6)); END IF; END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_unicode_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER `pass_history_audit_alert_reviews_delete` AFTER DELETE ON `audit_alert_reviews` FOR EACH ROW BEGIN INSERT INTO record_history (record_type, record_id, operation, actor_user_id, actor_label, source, ip_address, before_values, after_values, recorded_at)
                    VALUES ('audit_alert_reviews', CONCAT_WS(':', OLD.`alert_key`), 'deleted', @pass_history_actor, LEFT(@pass_history_label, 100), LEFT(@pass_history_source, 150), LEFT(@pass_history_ip, 45), JSON_OBJECT('alert_key', OLD.`alert_key`, 'rule', OLD.`rule`, 'severity', OLD.`severity`, 'reviewed_by', OLD.`reviewed_by`, 'note', OLD.`note`, 'reviewed_at', OLD.`reviewed_at`), NULL, CURRENT_TIMESTAMP(6)); END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;

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
) ENGINE=InnoDB AUTO_INCREMENT=281 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `audit_logs`
--

LOCK TABLES `audit_logs` WRITE;
/*!40000 ALTER TABLE `audit_logs` DISABLE KEYS */;
INSERT INTO `audit_logs` VALUES (167,NULL,'seeder:sample-data','parking_slot_maintenance','parking_slot',50,'{\"slot_status\": \"available\"}','{\"reason\": \"SAMPLE: line repainting (test data)\", \"slot_status\": \"maintenance\"}',NULL,NULL,'2026-10-14 04:02:01'),(168,NULL,'seeder:sample-data','parking_slot_maintenance','parking_slot',135,'{\"slot_status\": \"available\"}','{\"reason\": \"SAMPLE: line repainting (test data)\", \"slot_status\": \"maintenance\"}',NULL,NULL,'2026-10-14 04:02:01'),(246,NULL,'system:license-check','license_auto_pending_review','App\\Models\\DriverLicense',44,'{\"expiry_date\": \"2030-03-15\", \"reviewed_by\": null, \"license_number\": \"Z01-26-000101\", \"original_file_name\": \"sample-stu-001-sample-license.png\", \"verification_reason\": null, \"verification_source\": \"automatic\", \"verification_status\": \"processing\"}','{\"expiry_date\": \"2030-03-15\", \"reviewed_by\": null, \"license_number\": \"Z01-26-000101\", \"original_file_name\": \"sample-stu-001-sample-license.png\", \"verification_reason\": null, \"verification_source\": \"automatic\", \"verification_status\": \"pending_review\"}',NULL,NULL,'2026-10-14 05:12:00'),(247,NULL,'seeder:sample-data (as SEC-DEMO-001)','license_review_verified','App\\Models\\DriverLicense',44,'{\"reviewed_by\": null, \"verification_reason\": null, \"verification_source\": \"automatic\", \"verification_status\": \"pending_review\"}','{\"reviewed_by\": 1, \"verification_reason\": null, \"verification_source\": \"staff\", \"verification_status\": \"verified\"}',NULL,NULL,'2026-10-14 05:12:00'),(248,NULL,'system:license-check','license_auto_pending_review','App\\Models\\DriverLicense',45,'{\"expiry_date\": \"2027-07-02\", \"reviewed_by\": null, \"license_number\": \"Z02-26-000202\", \"original_file_name\": \"sample-stu-002-sample-license.png\", \"verification_reason\": null, \"verification_source\": \"automatic\", \"verification_status\": \"processing\"}','{\"expiry_date\": \"2027-07-02\", \"reviewed_by\": null, \"license_number\": \"Z02-26-000202\", \"original_file_name\": \"sample-stu-002-sample-license.png\", \"verification_reason\": null, \"verification_source\": \"automatic\", \"verification_status\": \"pending_review\"}',NULL,NULL,'2026-10-14 05:12:01'),(249,NULL,'system:license-check','license_auto_needs_correction','App\\Models\\DriverLicense',46,'{\"expiry_date\": \"2031-11-20\", \"reviewed_by\": null, \"license_number\": \"Z03-26-000303\", \"original_file_name\": \"sample-fac-001-sample-license.png\", \"verification_reason\": null, \"verification_source\": \"automatic\", \"verification_status\": \"processing\"}','{\"expiry_date\": \"2031-11-20\", \"reviewed_by\": null, \"license_number\": \"Z03-26-000303\", \"original_file_name\": \"sample-fac-001-sample-license.png\", \"verification_reason\": \"The license number on the document (Z03-26-000330) does not match the number you entered (Z03-26-000303). Check the number or upload the correct license.\", \"verification_source\": \"automatic\", \"verification_status\": \"needs_correction\"}',NULL,NULL,'2026-10-14 05:12:02'),(250,NULL,'system:license-check','license_auto_pending_review','App\\Models\\DriverLicense',47,'{\"expiry_date\": \"2029-01-08\", \"reviewed_by\": null, \"license_number\": \"Z04-26-000404\", \"original_file_name\": \"sample-fac-002-sample-license.png\", \"verification_reason\": null, \"verification_source\": \"automatic\", \"verification_status\": \"processing\"}','{\"expiry_date\": \"2029-01-08\", \"reviewed_by\": null, \"license_number\": \"Z04-26-000404\", \"original_file_name\": \"sample-fac-002-sample-license.png\", \"verification_reason\": null, \"verification_source\": \"automatic\", \"verification_status\": \"pending_review\"}',NULL,NULL,'2026-10-14 05:12:03'),(251,NULL,'system:license-check','license_auto_pending_review','App\\Models\\DriverLicense',48,'{\"expiry_date\": \"2036-05-27\", \"reviewed_by\": null, \"license_number\": \"Z05-26-000505\", \"original_file_name\": \"sample-emp-001-sample-license.png\", \"verification_reason\": null, \"verification_source\": \"automatic\", \"verification_status\": \"processing\"}','{\"expiry_date\": \"2036-05-27\", \"reviewed_by\": null, \"license_number\": \"Z05-26-000505\", \"original_file_name\": \"sample-emp-001-sample-license.png\", \"verification_reason\": null, \"verification_source\": \"automatic\", \"verification_status\": \"pending_review\"}',NULL,NULL,'2026-10-14 05:12:04'),(252,NULL,'seeder:sample-data (as SEC-DEMO-001)','license_review_verified','App\\Models\\DriverLicense',48,'{\"reviewed_by\": null, \"verification_reason\": null, \"verification_source\": \"automatic\", \"verification_status\": \"pending_review\"}','{\"reviewed_by\": 1, \"verification_reason\": null, \"verification_source\": \"staff\", \"verification_status\": \"verified\"}',NULL,NULL,'2026-10-14 05:12:04'),(253,1,'SEC-DEMO-001','parking_slot_maintenance','parking_slot',5,'{\"slot_status\": \"available\"}','{\"reason\": \"Layout verification test\", \"slot_status\": \"maintenance\"}','127.0.0.1','Mozilla/5.0 (Windows NT; Windows NT 10.0; en-PH) WindowsPowerShell/5.1.26100.9444','2026-10-15 02:51:27'),(254,1,'SEC-DEMO-001','parking_slot_available','parking_slot',5,'{\"slot_status\": \"maintenance\"}','{\"reason\": \"Layout verification test finished\", \"slot_status\": \"available\"}','127.0.0.1','Mozilla/5.0 (Windows NT; Windows NT 10.0; en-PH) WindowsPowerShell/5.1.26100.9444','2026-10-15 02:52:06'),(255,1,'SEC-DEMO-001','visitor_request_created','visitor_request',1,NULL,'{\"plate_number\": \"DEMO 4821\", \"request_status\": \"pending\"}','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/155.0.0.0 Safari/537.36 Edg/155.0.0.0','2026-10-15 05:17:59'),(256,1,'SEC-DEMO-001','visitor_request_approved','visitor_request',1,'{\"request_status\": \"pending\"}','{\"qr_pass_id\": 68, \"request_status\": \"approved\"}','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/155.0.0.0 Safari/537.36 Edg/155.0.0.0','2026-10-15 05:20:24'),(257,1,'SEC-DEMO-001','visitor_request_created','visitor_request',2,NULL,'{\"plate_number\": \"DEMO 4822\", \"request_status\": \"pending\"}','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/155.0.0.0 Safari/537.36 Edg/155.0.0.0','2026-10-15 05:22:32'),(258,1,'SEC-DEMO-001','visitor_request_rejected','visitor_request',2,'{\"request_status\": \"pending\"}','{\"remarks\": \"No appointment on record\", \"request_status\": \"rejected\"}','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/155.0.0.0 Safari/537.36 Edg/155.0.0.0','2026-10-15 05:22:36'),(259,1,'SEC-DEMO-001','visitor_request_created','visitor_request',3,NULL,'{\"plate_number\": \"DEMO 4823\", \"request_status\": \"pending\"}','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/155.0.0.0 Safari/537.36 Edg/155.0.0.0','2026-10-15 05:22:41'),(260,1,'SEC-DEMO-001','visitor_request_approved','visitor_request',3,'{\"request_status\": \"pending\"}','{\"qr_pass_id\": 69, \"request_status\": \"approved\"}','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/155.0.0.0 Safari/537.36 Edg/155.0.0.0','2026-10-15 05:22:45'),(261,1,'SEC-DEMO-001','visitor_request_created','visitor_request',4,NULL,'{\"plate_number\": \"DEMO 4824\", \"request_status\": \"pending\"}','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/155.0.0.0 Safari/537.36 Edg/155.0.0.0','2026-10-15 05:25:27'),(262,1,'SEC-DEMO-001','visitor_request_approved','visitor_request',4,'{\"request_status\": \"pending\"}','{\"qr_pass_id\": 70, \"request_status\": \"approved\"}','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/155.0.0.0 Safari/537.36 Edg/155.0.0.0','2026-10-15 05:25:31'),(263,1,'SEC-DEMO-001','qr_pass_revoked','qr_pass',70,'{\"pass_status\": \"active\"}','{\"reason\": \"Interview cancelled\", \"pass_status\": \"revoked\"}','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/155.0.0.0 Safari/537.36 Edg/155.0.0.0','2026-10-15 05:25:47'),(264,47,'GST-DEMO-001','visitor_request_created','visitor_request',5,NULL,'{\"source\": \"demo_seeder\", \"plate_number\": \"GUEST 001\", \"request_status\": \"pending\"}','127.0.0.1','Symfony','2026-10-15 08:15:48'),(265,48,'GST-M9SC2UDB','visitor_request_created','visitor_request',6,NULL,'{\"source\": \"guest_account\", \"plate_number\": \"ROSA 2026\", \"request_status\": \"pending\"}','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/155.0.0.0 Safari/537.36 Edg/155.0.0.0','2026-10-15 08:24:58'),(266,48,'GST-M9SC2UDB','visitor_request_created','visitor_request',7,NULL,'{\"source\": \"guest_account\", \"plate_number\": \"ROSA 2027\", \"request_status\": \"pending\"}','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/155.0.0.0 Safari/537.36 Edg/155.0.0.0','2026-10-15 08:25:10'),(267,46,'ADM-DEMO-001','visitor_request_approved','visitor_request',6,'{\"request_status\": \"pending\"}','{\"qr_pass_id\": 71, \"request_status\": \"approved\"}','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/155.0.0.0 Safari/537.36 Edg/155.0.0.0','2026-10-15 08:27:02'),(268,46,'ADM-DEMO-001','visitor_request_rejected','visitor_request',7,'{\"request_status\": \"pending\"}','{\"remarks\": \"The Registrar is closed for a holiday\", \"request_status\": \"rejected\"}','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/155.0.0.0 Safari/537.36 Edg/155.0.0.0','2026-10-15 08:27:06'),(269,49,'GST-20UF3BER','visitor_request_created','visitor_request',8,NULL,'{\"source\": \"guest_account\", \"plate_number\": \"CAR 123\", \"request_status\": \"pending\"}','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Code/1.141.2 Chrome/150.0.7871.250 Electron/43.7.7 Safari/537.36','2026-10-15 10:15:56'),(270,50,'GST-BW3KZLQ4','visitor_request_created','visitor_request',9,NULL,'{\"source\": \"guest_account\", \"plate_number\": \"LARA 3101\", \"request_status\": \"pending\"}','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/155.0.0.0 Safari/537.36 Edg/155.0.0.0','2026-10-15 11:33:30'),(271,46,'ADM-DEMO-001','visitor_request_approved','visitor_request',9,'{\"request_status\": \"pending\"}','{\"qr_pass_id\": 72, \"request_status\": \"approved\"}','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/155.0.0.0 Safari/537.36 Edg/155.0.0.0','2026-10-15 11:33:51'),(272,1,'SEC-DEMO-001','suspended','App\\Models\\VehicleAuthorization',35,'{\"remarks\": null, \"reviewed_at\": \"2026-09-03 14:00:00\", \"reviewed_by\": 1, \"authorization_status\": \"approved\"}','{\"remarks\": \"wrong parking\", \"reviewed_at\": \"2026-10-15T11:49:11.287088Z\", \"reviewed_by\": 1, \"authorization_status\": \"suspended\"}','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Code/1.141.2 Chrome/150.0.7871.250 Electron/43.7.7 Safari/537.36','2026-10-15 11:49:11'),(273,1,'SEC-DEMO-001','violation_acknowledged','parking_violation',26,'{\"violation_status\": \"open\"}','{\"remarks\": null, \"violation_status\": \"under_review\"}','127.0.0.1','node','2026-10-15 17:19:01'),(274,1,'SEC-DEMO-001','violation_resolved','parking_violation',26,'{\"violation_status\": \"under_review\"}','{\"remarks\": \"QA verification complete\", \"violation_status\": \"resolved\"}','127.0.0.1','node','2026-10-15 17:19:02'),(275,1,'SEC-DEMO-001','violation_dismissed','parking_violation',27,'{\"violation_status\": \"open\"}','{\"remarks\": \"QA test record\", \"violation_status\": \"dismissed\"}','127.0.0.1','node','2026-10-15 17:19:06'),(276,1,'SEC-DEMO-001','login','users',1,NULL,NULL,'127.0.0.1','curl/8.18.0','2026-10-23 07:30:06'),(277,1,'SEC-DEMO-001','login','users',1,NULL,NULL,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36 Edg/155.0.0.0','2026-10-23 07:31:02'),(278,1,'SEC-DEMO-001','password_changed','users',1,NULL,NULL,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36 Edg/155.0.0.0','2026-10-23 07:44:39'),(279,1,'SEC-DEMO-001','login','users',1,NULL,NULL,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36 Edg/155.0.0.0','2026-10-23 07:44:58'),(280,1,'SEC-DEMO-001','login','users',1,NULL,NULL,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','2026-10-23 08:54:40');
/*!40000 ALTER TABLE `audit_logs` ENABLE KEYS */;
UNLOCK TABLES;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_unicode_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER `pass_history_audit_logs_insert` AFTER INSERT ON `audit_logs` FOR EACH ROW BEGIN INSERT INTO record_history (record_type, record_id, operation, actor_user_id, actor_label, source, ip_address, before_values, after_values, recorded_at)
                    VALUES ('audit_logs', CONCAT_WS(':', NEW.`log_id`), 'created', @pass_history_actor, LEFT(@pass_history_label, 100), LEFT(@pass_history_source, 150), LEFT(@pass_history_ip, 45), NULL, JSON_OBJECT('log_id', NEW.`log_id`, 'user_id', NEW.`user_id`, 'actor_label', NEW.`actor_label`, 'action', NEW.`action`, 'auditable_type', NEW.`auditable_type`, 'auditable_id', NEW.`auditable_id`, 'ip_address', NEW.`ip_address`, 'user_agent', NEW.`user_agent`, 'created_at', NEW.`created_at`), CURRENT_TIMESTAMP(6)); END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_unicode_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER `pass_history_audit_logs_update` AFTER UPDATE ON `audit_logs` FOR EACH ROW BEGIN IF NOT (OLD.`log_id` <=> NEW.`log_id`) OR NOT (OLD.`user_id` <=> NEW.`user_id`) OR NOT (OLD.`actor_label` <=> NEW.`actor_label`) OR NOT (OLD.`action` <=> NEW.`action`) OR NOT (OLD.`auditable_type` <=> NEW.`auditable_type`) OR NOT (OLD.`auditable_id` <=> NEW.`auditable_id`) OR NOT (OLD.`ip_address` <=> NEW.`ip_address`) OR NOT (OLD.`user_agent` <=> NEW.`user_agent`) OR NOT (OLD.`created_at` <=> NEW.`created_at`) THEN INSERT INTO record_history (record_type, record_id, operation, actor_user_id, actor_label, source, ip_address, before_values, after_values, recorded_at)
                    VALUES ('audit_logs', CONCAT_WS(':', NEW.`log_id`), 'updated', @pass_history_actor, LEFT(@pass_history_label, 100), LEFT(@pass_history_source, 150), LEFT(@pass_history_ip, 45), JSON_OBJECT('log_id', OLD.`log_id`, 'user_id', OLD.`user_id`, 'actor_label', OLD.`actor_label`, 'action', OLD.`action`, 'auditable_type', OLD.`auditable_type`, 'auditable_id', OLD.`auditable_id`, 'ip_address', OLD.`ip_address`, 'user_agent', OLD.`user_agent`, 'created_at', OLD.`created_at`), JSON_OBJECT('log_id', NEW.`log_id`, 'user_id', NEW.`user_id`, 'actor_label', NEW.`actor_label`, 'action', NEW.`action`, 'auditable_type', NEW.`auditable_type`, 'auditable_id', NEW.`auditable_id`, 'ip_address', NEW.`ip_address`, 'user_agent', NEW.`user_agent`, 'created_at', NEW.`created_at`), CURRENT_TIMESTAMP(6)); END IF; END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_unicode_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER `pass_history_audit_logs_delete` AFTER DELETE ON `audit_logs` FOR EACH ROW BEGIN INSERT INTO record_history (record_type, record_id, operation, actor_user_id, actor_label, source, ip_address, before_values, after_values, recorded_at)
                    VALUES ('audit_logs', CONCAT_WS(':', OLD.`log_id`), 'deleted', @pass_history_actor, LEFT(@pass_history_label, 100), LEFT(@pass_history_source, 150), LEFT(@pass_history_ip, 45), JSON_OBJECT('log_id', OLD.`log_id`, 'user_id', OLD.`user_id`, 'actor_label', OLD.`actor_label`, 'action', OLD.`action`, 'auditable_type', OLD.`auditable_type`, 'auditable_id', OLD.`auditable_id`, 'ip_address', OLD.`ip_address`, 'user_agent', OLD.`user_agent`, 'created_at', OLD.`created_at`), NULL, CURRENT_TIMESTAMP(6)); END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;

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
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_unicode_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER `pass_history_departments_insert` AFTER INSERT ON `departments` FOR EACH ROW BEGIN INSERT INTO record_history (record_type, record_id, operation, actor_user_id, actor_label, source, ip_address, before_values, after_values, recorded_at)
                    VALUES ('departments', CONCAT_WS(':', NEW.`department_id`), 'created', @pass_history_actor, LEFT(@pass_history_label, 100), LEFT(@pass_history_source, 150), LEFT(@pass_history_ip, 45), NULL, JSON_OBJECT('department_id', NEW.`department_id`, 'department_code', NEW.`department_code`, 'department_name', NEW.`department_name`, 'is_active', NEW.`is_active`), CURRENT_TIMESTAMP(6)); END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_unicode_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER `pass_history_departments_update` AFTER UPDATE ON `departments` FOR EACH ROW BEGIN IF NOT (OLD.`department_id` <=> NEW.`department_id`) OR NOT (OLD.`department_code` <=> NEW.`department_code`) OR NOT (OLD.`department_name` <=> NEW.`department_name`) OR NOT (OLD.`is_active` <=> NEW.`is_active`) THEN INSERT INTO record_history (record_type, record_id, operation, actor_user_id, actor_label, source, ip_address, before_values, after_values, recorded_at)
                    VALUES ('departments', CONCAT_WS(':', NEW.`department_id`), 'updated', @pass_history_actor, LEFT(@pass_history_label, 100), LEFT(@pass_history_source, 150), LEFT(@pass_history_ip, 45), JSON_OBJECT('department_id', OLD.`department_id`, 'department_code', OLD.`department_code`, 'department_name', OLD.`department_name`, 'is_active', OLD.`is_active`), JSON_OBJECT('department_id', NEW.`department_id`, 'department_code', NEW.`department_code`, 'department_name', NEW.`department_name`, 'is_active', NEW.`is_active`), CURRENT_TIMESTAMP(6)); END IF; END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_unicode_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER `pass_history_departments_delete` AFTER DELETE ON `departments` FOR EACH ROW BEGIN INSERT INTO record_history (record_type, record_id, operation, actor_user_id, actor_label, source, ip_address, before_values, after_values, recorded_at)
                    VALUES ('departments', CONCAT_WS(':', OLD.`department_id`), 'deleted', @pass_history_actor, LEFT(@pass_history_label, 100), LEFT(@pass_history_source, 150), LEFT(@pass_history_ip, 45), JSON_OBJECT('department_id', OLD.`department_id`, 'department_code', OLD.`department_code`, 'department_name', OLD.`department_name`, 'is_active', OLD.`is_active`), NULL, CURRENT_TIMESTAMP(6)); END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;

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
  `license_type` enum('student_permit','non_professional','professional','international') COLLATE utf8mb4_unicode_ci DEFAULT NULL,
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
  `review_status` enum('pending','verified','rejected') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `review_notes` text COLLATE utf8mb4_unicode_ci,
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
INSERT INTO `driver_licenses` (`license_id`, `driver_id`, `license_number`, `license_type`, `restriction_codes`, `issue_date`, `expiry_date`, `issuing_agency`, `license_image_path`, `original_file_name`, `license_status`, `verification_status`, `verification_source`, `verification_reason`, `ocr_result`, `verification_run_id`, `submitted_at`, `checked_at`, `reviewed_by`, `reviewed_at`, `created_at`, `updated_at`, `review_status`, `review_notes`) VALUES (44,44,'Z01-26-000101','non_professional',NULL,NULL,'2030-03-15','SAMPLE TEST DATA','driver-licenses/sample/sample-stu-001.png','sample-stu-001-sample-license.png','valid','verified','staff',NULL,'{\"text\": \"SAMPLE DRIVER LICENSE - TEST DATA ONLY\\nNOT A GOVERNMENT-ISSUED DOCUMENT. FOR PASS SYSTEM TESTING.\\nLast Name, First Name\\nVILLAMOR, ANDREA\\nLicense No.\\nZ01-26-000101\\nExpiration Date\\n2030/03/15\\nDate of Birth\\n2004/03/15\\nLicense Type\\nNON-PROFESSIONAL\\nSAMPLE * TEST DATAONLY * NOT VALID FOR ANY PURPOSE\", \"notes\": [\"Preliminary validation only. Authenticity has not been confirmed: no official LTO verification service is integrated, so PASS staff must review the document.\"], \"width\": 1600, \"checks\": {\"name\": {\"detail\": \"The registered first and last name appear on the document.\", \"result\": \"pass\"}, \"expiry_date\": {\"detail\": \"The expiration date on the document matches the submitted date.\", \"result\": \"pass\"}, \"document_type\": {\"detail\": \"Matched 3 LTO license marker group(s).\", \"result\": \"pass\"}, \"image_quality\": {\"detail\": \"Resolution 1600x1000, sharpness 1463.9.\", \"result\": \"pass\"}, \"license_number\": {\"detail\": \"The license number on the document matches the submitted number.\", \"result\": \"pass\"}, \"text_extraction\": {\"detail\": \"236 readable characters, mean confidence 95%.\", \"result\": \"pass\"}}, \"engine\": \"tesseract\", \"height\": 1000, \"reasons\": [], \"decision\": \"pending_review\", \"extracted\": {\"dates\": [\"2030-03-15\", \"2004-03-15\"], \"expiry_date\": \"2030-03-15\", \"license_numbers\": [\"Z01-26-000101\"], \"name_tokens_found\": [\"ANDREA\", \"VILLAMOR\"]}, \"sharpness\": 1463.9, \"confidence\": 95, \"preliminary\": true, \"document_score\": 3, \"official_verification\": \"unavailable\"}','1dc5f5a5-5e05-450a-96e8-bddfd3156e44','2026-09-24 05:11:59','2026-10-14 05:12:00',1,'2026-09-25 05:11:59','2026-10-13 17:16:34','2026-10-13 21:12:00','pending',NULL),(45,45,'Z02-26-000202','student_permit',NULL,NULL,'2027-07-02','SAMPLE TEST DATA','driver-licenses/sample/sample-stu-002.png','sample-stu-002-sample-license.png','valid','pending_review','automatic',NULL,'{\"text\": \"SAMPLE DRIVER LICENSE - TEST DATA ONLY\\nNOT A GOVERNMENT-ISSUED DOCUMENT. FOR PASS SYSTEM TESTING.\\nLast Name, First Name\\nCASTILLO, BRYAN\\nLicense No.\\nZ02-26-000202\\nExpiration Date\\n2027/07/02\\nDate of Birth\\n2005/07/02\\nLicense Type\\nSTUDENT-PERMIT\\nSAMPLE * TEST DATAONLY * NOT VALID FOR ANY PURPOSE\", \"notes\": [\"Preliminary validation only. Authenticity has not been confirmed: no official LTO verification service is integrated, so PASS staff must review the document.\"], \"width\": 1600, \"checks\": {\"name\": {\"detail\": \"The registered first and last name appear on the document.\", \"result\": \"pass\"}, \"expiry_date\": {\"detail\": \"The expiration date on the document matches the submitted date.\", \"result\": \"pass\"}, \"document_type\": {\"detail\": \"Matched 3 LTO license marker group(s).\", \"result\": \"pass\"}, \"image_quality\": {\"detail\": \"Resolution 1600x1000, sharpness 1433.6.\", \"result\": \"pass\"}, \"license_number\": {\"detail\": \"The license number on the document matches the submitted number.\", \"result\": \"pass\"}, \"text_extraction\": {\"detail\": \"233 readable characters, mean confidence 95%.\", \"result\": \"pass\"}}, \"engine\": \"tesseract\", \"height\": 1000, \"reasons\": [], \"decision\": \"pending_review\", \"extracted\": {\"dates\": [\"2027-07-02\", \"2005-07-02\"], \"expiry_date\": \"2027-07-02\", \"license_numbers\": [\"Z02-26-000202\"], \"name_tokens_found\": [\"BRYAN\", \"CASTILLO\"]}, \"sharpness\": 1433.6, \"confidence\": 95, \"preliminary\": true, \"document_score\": 3, \"official_verification\": \"unavailable\"}','1f77ff39-6f55-4a1d-99ed-94ccc05540d1','2026-10-13 05:12:00','2026-10-14 05:12:01',NULL,NULL,'2026-10-13 17:16:37','2026-10-13 21:12:01','pending',NULL),(46,46,'Z03-26-000303','professional',NULL,NULL,'2031-11-20','SAMPLE TEST DATA','driver-licenses/sample/sample-fac-001.png','sample-fac-001-sample-license.png','valid','needs_correction','automatic','The license number on the document (Z03-26-000330) does not match the number you entered (Z03-26-000303). Check the number or upload the correct license.','{\"text\": \"SAMPLE DRIVER LICENSE - TEST DATA ONLY\\nNOT A GOVERNMENT-ISSUED DOCUMENT. FOR PASS SYSTEM TESTING.\\nLast Name, First Name\\nNAVARRO, CELIA\\nLicense No.\\nZ03-26-000330\\nExpiration Date\\n2031/11/20\\nDate of Birth\\n1986/11/20\\nLicense Type\\nPROFESSIONAL\\nSAMPLE * TEST DATAONLY * NOT VALID FOR ANY PURPOSE\", \"notes\": [\"Preliminary validation only. Authenticity has not been confirmed: no official LTO verification service is integrated, so PASS staff must review the document.\"], \"width\": 1600, \"checks\": {\"name\": {\"detail\": \"The registered first and last name appear on the document.\", \"result\": \"pass\"}, \"expiry_date\": {\"detail\": \"The expiration date on the document matches the submitted date.\", \"result\": \"pass\"}, \"document_type\": {\"detail\": \"Matched 3 LTO license marker group(s).\", \"result\": \"pass\"}, \"image_quality\": {\"detail\": \"Resolution 1600x1000, sharpness 1403.1.\", \"result\": \"pass\"}, \"license_number\": {\"detail\": \"Document shows Z03-26-000330.\", \"result\": \"fail\"}, \"text_extraction\": {\"detail\": \"231 readable characters, mean confidence 95.2%.\", \"result\": \"pass\"}}, \"engine\": \"tesseract\", \"height\": 1000, \"reasons\": [\"The license number on the document (Z03-26-000330) does not match the number you entered (Z03-26-000303). Check the number or upload the correct license.\"], \"decision\": \"needs_correction\", \"extracted\": {\"dates\": [\"2031-11-20\", \"1986-11-20\"], \"expiry_date\": \"2031-11-20\", \"license_numbers\": [\"Z03-26-000330\"], \"name_tokens_found\": [\"CELIA\", \"NAVARRO\"]}, \"sharpness\": 1403.1, \"confidence\": 95.2, \"preliminary\": true, \"document_score\": 3, \"official_verification\": \"unavailable\"}','c6370b24-397c-4d47-a233-ed519486fd23','2026-10-11 05:12:01','2026-10-14 05:12:02',NULL,NULL,'2026-10-13 17:16:39','2026-10-13 21:12:02','pending',NULL),(47,47,'Z04-26-000404','non_professional',NULL,NULL,'2029-01-08','SAMPLE TEST DATA','driver-licenses/sample/sample-fac-002.png','sample-fac-002-sample-license.png','valid','pending_review','automatic',NULL,'{\"text\": \"SAMPLE DRIVER LICENSE - TEST DATA ONLY\\nNOT A GOVERNMENT-ISSUED DOCUMENT. FOR PASS SYSTEM TESTING.\\nLast Name, First Name\\nAQUINO, DENNIS\\nLicense No.\\nZ04-26-000404\\nExpiration Date\\n2029/01/08\\nDate of Birth\\n1990/01/08\\nLicense Type\\nNON-PROFESSIONAL\\nSAMPLE * TEST DATAONLY * NOT VALID FOR ANY PURPOSE\", \"notes\": [\"Preliminary validation only. Authenticity has not been confirmed: no official LTO verification service is integrated, so PASS staff must review the document.\"], \"width\": 1600, \"checks\": {\"name\": {\"detail\": \"The registered first and last name appear on the document.\", \"result\": \"pass\"}, \"expiry_date\": {\"detail\": \"The expiration date on the document matches the submitted date.\", \"result\": \"pass\"}, \"document_type\": {\"detail\": \"Matched 3 LTO license marker group(s).\", \"result\": \"pass\"}, \"image_quality\": {\"detail\": \"Resolution 1600x1000, sharpness 1458.6.\", \"result\": \"pass\"}, \"license_number\": {\"detail\": \"The license number on the document matches the submitted number.\", \"result\": \"pass\"}, \"text_extraction\": {\"detail\": \"234 readable characters, mean confidence 95%.\", \"result\": \"pass\"}}, \"engine\": \"tesseract\", \"height\": 1000, \"reasons\": [], \"decision\": \"pending_review\", \"extracted\": {\"dates\": [\"2029-01-08\", \"1990-01-08\"], \"expiry_date\": \"2029-01-08\", \"license_numbers\": [\"Z04-26-000404\"], \"name_tokens_found\": [\"DENNIS\", \"AQUINO\"]}, \"sharpness\": 1458.6, \"confidence\": 95, \"preliminary\": true, \"document_score\": 3, \"official_verification\": \"unavailable\"}','443ec318-1538-4773-9c86-2680669378a4','2026-10-13 05:12:02','2026-10-14 05:12:03',NULL,NULL,'2026-10-13 17:16:41','2026-10-13 21:12:03','pending',NULL),(48,48,'Z05-26-000505','non_professional',NULL,NULL,'2036-05-27','SAMPLE TEST DATA','driver-licenses/sample/sample-emp-001.png','sample-emp-001-sample-license.png','valid','verified','staff',NULL,'{\"text\": \"SAMPLE DRIVER LICENSE - TEST DATA ONLY\\nNOT A GOVERNMENT-ISSUED DOCUMENT. FOR PASS SYSTEM TESTING.\\nLast Name, First Name\\nBAUTISTA, ELENA\\nLicense No.\\nZ05-26-000505\\nExpiration Date\\n2036/05/27\\nDate of Birth\\n1993/05/27\\nLicense Type\\nNON-PROFESSIONAL\\nSAMPLE * TEST DATAONLY * NOT VALID FOR ANY PURPOSE\", \"notes\": [\"Preliminary validation only. Authenticity has not been confirmed: no official LTO verification service is integrated, so PASS staff must review the document.\"], \"width\": 1600, \"checks\": {\"name\": {\"detail\": \"The registered first and last name appear on the document.\", \"result\": \"pass\"}, \"expiry_date\": {\"detail\": \"The expiration date on the document matches the submitted date.\", \"result\": \"pass\"}, \"document_type\": {\"detail\": \"Matched 3 LTO license marker group(s).\", \"result\": \"pass\"}, \"image_quality\": {\"detail\": \"Resolution 1600x1000, sharpness 1464.8.\", \"result\": \"pass\"}, \"license_number\": {\"detail\": \"The license number on the document matches the submitted number.\", \"result\": \"pass\"}, \"text_extraction\": {\"detail\": \"235 readable characters, mean confidence 95.1%.\", \"result\": \"pass\"}}, \"engine\": \"tesseract\", \"height\": 1000, \"reasons\": [], \"decision\": \"pending_review\", \"extracted\": {\"dates\": [\"2036-05-27\", \"1993-05-27\"], \"expiry_date\": \"2036-05-27\", \"license_numbers\": [\"Z05-26-000505\"], \"name_tokens_found\": [\"ELENA\", \"BAUTISTA\"]}, \"sharpness\": 1464.8, \"confidence\": 95.1, \"preliminary\": true, \"document_score\": 3, \"official_verification\": \"unavailable\"}','dc8f6912-cf71-4c69-a556-867a71e0bf92','2026-09-24 05:12:03','2026-10-14 05:12:04',1,'2026-09-25 05:12:03','2026-10-13 17:16:44','2026-10-13 21:12:04','pending',NULL);
/*!40000 ALTER TABLE `driver_licenses` ENABLE KEYS */;
UNLOCK TABLES;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_unicode_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER `pass_history_driver_licenses_insert` AFTER INSERT ON `driver_licenses` FOR EACH ROW BEGIN INSERT INTO record_history (record_type, record_id, operation, actor_user_id, actor_label, source, ip_address, before_values, after_values, recorded_at)
                    VALUES ('driver_licenses', CONCAT_WS(':', NEW.`license_id`), 'created', @pass_history_actor, LEFT(@pass_history_label, 100), LEFT(@pass_history_source, 150), LEFT(@pass_history_ip, 45), NULL, JSON_OBJECT('license_id', NEW.`license_id`, 'driver_id', NEW.`driver_id`, 'license_number', NEW.`license_number`, 'license_type', NEW.`license_type`, 'restriction_codes', NEW.`restriction_codes`, 'issue_date', NEW.`issue_date`, 'expiry_date', NEW.`expiry_date`, 'issuing_agency', NEW.`issuing_agency`, 'license_image_path', NEW.`license_image_path`, 'original_file_name', NEW.`original_file_name`, 'license_status', NEW.`license_status`, 'verification_status', NEW.`verification_status`, 'verification_source', NEW.`verification_source`, 'verification_reason', NEW.`verification_reason`, 'ocr_result', NEW.`ocr_result`, 'submitted_at', NEW.`submitted_at`, 'checked_at', NEW.`checked_at`, 'reviewed_by', NEW.`reviewed_by`, 'reviewed_at', NEW.`reviewed_at`, 'created_at', NEW.`created_at`, 'updated_at', NEW.`updated_at`, 'current_flag', NEW.`current_flag`, 'review_status', NEW.`review_status`, 'review_notes', NEW.`review_notes`), CURRENT_TIMESTAMP(6)); END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_unicode_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER `pass_history_driver_licenses_update` AFTER UPDATE ON `driver_licenses` FOR EACH ROW BEGIN IF NOT (OLD.`license_id` <=> NEW.`license_id`) OR NOT (OLD.`driver_id` <=> NEW.`driver_id`) OR NOT (OLD.`license_number` <=> NEW.`license_number`) OR NOT (OLD.`license_type` <=> NEW.`license_type`) OR NOT (OLD.`restriction_codes` <=> NEW.`restriction_codes`) OR NOT (OLD.`issue_date` <=> NEW.`issue_date`) OR NOT (OLD.`expiry_date` <=> NEW.`expiry_date`) OR NOT (OLD.`issuing_agency` <=> NEW.`issuing_agency`) OR NOT (OLD.`license_image_path` <=> NEW.`license_image_path`) OR NOT (OLD.`original_file_name` <=> NEW.`original_file_name`) OR NOT (OLD.`license_status` <=> NEW.`license_status`) OR NOT (OLD.`verification_status` <=> NEW.`verification_status`) OR NOT (OLD.`verification_source` <=> NEW.`verification_source`) OR NOT (OLD.`verification_reason` <=> NEW.`verification_reason`) OR NOT (OLD.`ocr_result` <=> NEW.`ocr_result`) OR NOT (OLD.`submitted_at` <=> NEW.`submitted_at`) OR NOT (OLD.`checked_at` <=> NEW.`checked_at`) OR NOT (OLD.`reviewed_by` <=> NEW.`reviewed_by`) OR NOT (OLD.`reviewed_at` <=> NEW.`reviewed_at`) OR NOT (OLD.`created_at` <=> NEW.`created_at`) OR NOT (OLD.`updated_at` <=> NEW.`updated_at`) OR NOT (OLD.`current_flag` <=> NEW.`current_flag`) OR NOT (OLD.`review_status` <=> NEW.`review_status`) OR NOT (OLD.`review_notes` <=> NEW.`review_notes`) THEN INSERT INTO record_history (record_type, record_id, operation, actor_user_id, actor_label, source, ip_address, before_values, after_values, recorded_at)
                    VALUES ('driver_licenses', CONCAT_WS(':', NEW.`license_id`), 'updated', @pass_history_actor, LEFT(@pass_history_label, 100), LEFT(@pass_history_source, 150), LEFT(@pass_history_ip, 45), JSON_OBJECT('license_id', OLD.`license_id`, 'driver_id', OLD.`driver_id`, 'license_number', OLD.`license_number`, 'license_type', OLD.`license_type`, 'restriction_codes', OLD.`restriction_codes`, 'issue_date', OLD.`issue_date`, 'expiry_date', OLD.`expiry_date`, 'issuing_agency', OLD.`issuing_agency`, 'license_image_path', OLD.`license_image_path`, 'original_file_name', OLD.`original_file_name`, 'license_status', OLD.`license_status`, 'verification_status', OLD.`verification_status`, 'verification_source', OLD.`verification_source`, 'verification_reason', OLD.`verification_reason`, 'ocr_result', OLD.`ocr_result`, 'submitted_at', OLD.`submitted_at`, 'checked_at', OLD.`checked_at`, 'reviewed_by', OLD.`reviewed_by`, 'reviewed_at', OLD.`reviewed_at`, 'created_at', OLD.`created_at`, 'updated_at', OLD.`updated_at`, 'current_flag', OLD.`current_flag`, 'review_status', OLD.`review_status`, 'review_notes', OLD.`review_notes`), JSON_OBJECT('license_id', NEW.`license_id`, 'driver_id', NEW.`driver_id`, 'license_number', NEW.`license_number`, 'license_type', NEW.`license_type`, 'restriction_codes', NEW.`restriction_codes`, 'issue_date', NEW.`issue_date`, 'expiry_date', NEW.`expiry_date`, 'issuing_agency', NEW.`issuing_agency`, 'license_image_path', NEW.`license_image_path`, 'original_file_name', NEW.`original_file_name`, 'license_status', NEW.`license_status`, 'verification_status', NEW.`verification_status`, 'verification_source', NEW.`verification_source`, 'verification_reason', NEW.`verification_reason`, 'ocr_result', NEW.`ocr_result`, 'submitted_at', NEW.`submitted_at`, 'checked_at', NEW.`checked_at`, 'reviewed_by', NEW.`reviewed_by`, 'reviewed_at', NEW.`reviewed_at`, 'created_at', NEW.`created_at`, 'updated_at', NEW.`updated_at`, 'current_flag', NEW.`current_flag`, 'review_status', NEW.`review_status`, 'review_notes', NEW.`review_notes`), CURRENT_TIMESTAMP(6)); END IF; END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_unicode_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER `pass_history_driver_licenses_delete` AFTER DELETE ON `driver_licenses` FOR EACH ROW BEGIN INSERT INTO record_history (record_type, record_id, operation, actor_user_id, actor_label, source, ip_address, before_values, after_values, recorded_at)
                    VALUES ('driver_licenses', CONCAT_WS(':', OLD.`license_id`), 'deleted', @pass_history_actor, LEFT(@pass_history_label, 100), LEFT(@pass_history_source, 150), LEFT(@pass_history_ip, 45), JSON_OBJECT('license_id', OLD.`license_id`, 'driver_id', OLD.`driver_id`, 'license_number', OLD.`license_number`, 'license_type', OLD.`license_type`, 'restriction_codes', OLD.`restriction_codes`, 'issue_date', OLD.`issue_date`, 'expiry_date', OLD.`expiry_date`, 'issuing_agency', OLD.`issuing_agency`, 'license_image_path', OLD.`license_image_path`, 'original_file_name', OLD.`original_file_name`, 'license_status', OLD.`license_status`, 'verification_status', OLD.`verification_status`, 'verification_source', OLD.`verification_source`, 'verification_reason', OLD.`verification_reason`, 'ocr_result', OLD.`ocr_result`, 'submitted_at', OLD.`submitted_at`, 'checked_at', OLD.`checked_at`, 'reviewed_by', OLD.`reviewed_by`, 'reviewed_at', OLD.`reviewed_at`, 'created_at', OLD.`created_at`, 'updated_at', OLD.`updated_at`, 'current_flag', OLD.`current_flag`, 'review_status', OLD.`review_status`, 'review_notes', OLD.`review_notes`), NULL, CURRENT_TIMESTAMP(6)); END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;

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
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_unicode_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER `pass_history_drivers_insert` AFTER INSERT ON `drivers` FOR EACH ROW BEGIN INSERT INTO record_history (record_type, record_id, operation, actor_user_id, actor_label, source, ip_address, before_values, after_values, recorded_at)
                    VALUES ('drivers', CONCAT_WS(':', NEW.`driver_id`), 'created', @pass_history_actor, LEFT(@pass_history_label, 100), LEFT(@pass_history_source, 150), LEFT(@pass_history_ip, 45), NULL, JSON_OBJECT('driver_id', NEW.`driver_id`, 'user_id', NEW.`user_id`, 'first_name', NEW.`first_name`, 'middle_name', NEW.`middle_name`, 'last_name', NEW.`last_name`, 'contact_number', NEW.`contact_number`, 'driver_status', NEW.`driver_status`, 'created_at', NEW.`created_at`, 'updated_at', NEW.`updated_at`), CURRENT_TIMESTAMP(6)); END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_unicode_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER `pass_history_drivers_update` AFTER UPDATE ON `drivers` FOR EACH ROW BEGIN IF NOT (OLD.`driver_id` <=> NEW.`driver_id`) OR NOT (OLD.`user_id` <=> NEW.`user_id`) OR NOT (OLD.`first_name` <=> NEW.`first_name`) OR NOT (OLD.`middle_name` <=> NEW.`middle_name`) OR NOT (OLD.`last_name` <=> NEW.`last_name`) OR NOT (OLD.`contact_number` <=> NEW.`contact_number`) OR NOT (OLD.`driver_status` <=> NEW.`driver_status`) OR NOT (OLD.`created_at` <=> NEW.`created_at`) OR NOT (OLD.`updated_at` <=> NEW.`updated_at`) THEN INSERT INTO record_history (record_type, record_id, operation, actor_user_id, actor_label, source, ip_address, before_values, after_values, recorded_at)
                    VALUES ('drivers', CONCAT_WS(':', NEW.`driver_id`), 'updated', @pass_history_actor, LEFT(@pass_history_label, 100), LEFT(@pass_history_source, 150), LEFT(@pass_history_ip, 45), JSON_OBJECT('driver_id', OLD.`driver_id`, 'user_id', OLD.`user_id`, 'first_name', OLD.`first_name`, 'middle_name', OLD.`middle_name`, 'last_name', OLD.`last_name`, 'contact_number', OLD.`contact_number`, 'driver_status', OLD.`driver_status`, 'created_at', OLD.`created_at`, 'updated_at', OLD.`updated_at`), JSON_OBJECT('driver_id', NEW.`driver_id`, 'user_id', NEW.`user_id`, 'first_name', NEW.`first_name`, 'middle_name', NEW.`middle_name`, 'last_name', NEW.`last_name`, 'contact_number', NEW.`contact_number`, 'driver_status', NEW.`driver_status`, 'created_at', NEW.`created_at`, 'updated_at', NEW.`updated_at`), CURRENT_TIMESTAMP(6)); END IF; END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_unicode_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER `pass_history_drivers_delete` AFTER DELETE ON `drivers` FOR EACH ROW BEGIN INSERT INTO record_history (record_type, record_id, operation, actor_user_id, actor_label, source, ip_address, before_values, after_values, recorded_at)
                    VALUES ('drivers', CONCAT_WS(':', OLD.`driver_id`), 'deleted', @pass_history_actor, LEFT(@pass_history_label, 100), LEFT(@pass_history_source, 150), LEFT(@pass_history_ip, 45), JSON_OBJECT('driver_id', OLD.`driver_id`, 'user_id', OLD.`user_id`, 'first_name', OLD.`first_name`, 'middle_name', OLD.`middle_name`, 'last_name', OLD.`last_name`, 'contact_number', OLD.`contact_number`, 'driver_status', OLD.`driver_status`, 'created_at', OLD.`created_at`, 'updated_at', OLD.`updated_at`), NULL, CURRENT_TIMESTAMP(6)); END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;

--
-- Table structure for table `email_verification_codes`
--

DROP TABLE IF EXISTS `email_verification_codes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `email_verification_codes` (
  `user_id` bigint unsigned NOT NULL,
  `code_hash` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `attempts` tinyint unsigned NOT NULL DEFAULT '0',
  `expires_at` timestamp NOT NULL,
  `sent_at` timestamp NOT NULL,
  PRIMARY KEY (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `email_verification_codes`
--

LOCK TABLES `email_verification_codes` WRITE;
/*!40000 ALTER TABLE `email_verification_codes` DISABLE KEYS */;
/*!40000 ALTER TABLE `email_verification_codes` ENABLE KEYS */;
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
) ENGINE=InnoDB AUTO_INCREMENT=99 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `login_attempts`
--

LOCK TABLES `login_attempts` WRITE;
/*!40000 ALTER TABLE `login_attempts` DISABLE KEYS */;
INSERT INTO `login_attempts` VALUES (1,NULL,'security.demo@pass.local','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36',0,'invalid_credentials','2026-10-13 06:55:16'),(2,NULL,'security.demo@pass.local','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36',0,'invalid_credentials','2026-10-13 06:55:19'),(3,NULL,'security.demo@pass.local','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36',0,'invalid_credentials','2026-10-13 07:12:05'),(4,NULL,'security.demo@pass.local','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36',0,'invalid_credentials','2026-10-13 07:12:07'),(5,1,'security.demo@pass.local','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36',1,NULL,'2026-10-13 07:17:10'),(6,1,'security.demo@pass.local','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Code/1.141.0 Chrome/150.0.7871.250 Electron/43.7.7 Safari/537.36',0,'invalid_credentials','2026-10-13 12:54:18'),(7,1,'security.demo@pass.local','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Code/1.141.0 Chrome/150.0.7871.250 Electron/43.7.7 Safari/537.36',1,NULL,'2026-10-13 12:54:34'),(8,1,'security.demo@pass.local','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Code/1.141.0 Chrome/150.0.7871.250 Electron/43.7.7 Safari/537.36',1,NULL,'2026-10-13 15:23:58'),(9,1,'security.demo@pass.local','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36',1,NULL,'2026-10-14 00:06:25'),(10,1,'security.demo@pass.local','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36',1,NULL,'2026-10-14 00:23:32'),(18,1,'SEC-DEMO-001','127.0.0.1','GuzzleHttp/7',1,NULL,'2026-10-14 00:25:40'),(19,1,'security.demo@pass.local','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Code/1.141.0 Chrome/150.0.7871.250 Electron/43.7.7 Safari/537.36',1,NULL,'2026-10-14 00:31:26'),(27,1,'SEC-DEMO-001','127.0.0.1','GuzzleHttp/7',1,NULL,'2026-10-14 00:34:40'),(28,1,'SEC-DEMO-001','127.0.0.1','GuzzleHttp/7',1,NULL,'2026-10-14 01:17:15'),(29,1,'security.demo@pass.local','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Code/1.141.0 Chrome/150.0.7871.250 Electron/43.7.7 Safari/537.36',1,NULL,'2026-10-14 02:00:23'),(30,1,'security.demo@pass.local','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36',1,NULL,'2026-10-14 02:02:28'),(31,1,'security.demo@pass.local','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Code/1.141.0 Chrome/150.0.7871.250 Electron/43.7.7 Safari/537.36',1,NULL,'2026-10-14 04:03:30'),(32,1,'SEC-DEMO-001','127.0.0.1','GuzzleHttp/7',1,NULL,'2026-10-14 04:08:09'),(33,36,'SAMPLE-EMP-001','127.0.0.1','GuzzleHttp/7',1,NULL,'2026-10-14 04:08:10'),(34,1,'SEC-DEMO-001','127.0.0.1','GuzzleHttp/7',1,NULL,'2026-10-14 04:09:49'),(35,36,'SAMPLE-EMP-001','127.0.0.1','GuzzleHttp/7',1,NULL,'2026-10-14 04:09:49'),(36,32,'SAMPLE-STU-001','127.0.0.1','GuzzleHttp/7',1,NULL,'2026-10-14 04:10:14'),(37,33,'SAMPLE-STU-002','127.0.0.1','GuzzleHttp/7',1,NULL,'2026-10-14 04:10:14'),(38,34,'SAMPLE-FAC-001','127.0.0.1','GuzzleHttp/7',1,NULL,'2026-10-14 04:10:15'),(39,35,'SAMPLE-FAC-002','127.0.0.1','GuzzleHttp/7',1,NULL,'2026-10-14 04:10:15'),(40,36,'SAMPLE-EMP-001','127.0.0.1','GuzzleHttp/7',1,NULL,'2026-10-14 04:10:16'),(41,1,'SEC-DEMO-001','127.0.0.1','GuzzleHttp/7',1,NULL,'2026-10-14 05:10:31'),(42,36,'SAMPLE-EMP-001','127.0.0.1','GuzzleHttp/7',1,NULL,'2026-10-14 05:10:31'),(43,33,'SAMPLE-STU-002','127.0.0.1','GuzzleHttp/7',1,NULL,'2026-10-14 05:10:55'),(44,1,'security.demo@pass.local','127.0.0.1','PostmanRuntime/2.11.2',1,NULL,'2026-10-14 21:07:37'),(45,1,'security.demo@pass.local','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36 Edg/155.0.0.0',1,NULL,'2026-10-14 21:30:51'),(46,1,'security.demo@pass.local','127.0.0.1','PostmanRuntime/2.11.2',1,NULL,'2026-10-14 21:52:55'),(47,1,'security.demo@pass.local','127.0.0.1','PostmanRuntime/2.11.2',1,NULL,'2026-10-14 21:53:39'),(48,1,'security.demo@pass.local','127.0.0.1','PostmanRuntime/2.11.2',1,NULL,'2026-10-14 21:54:07'),(49,1,'security.demo@pass.local','127.0.0.1','PostmanRuntime/2.11.2',1,NULL,'2026-10-14 21:54:09'),(50,NULL,'x@x','127.0.0.1','curl/8.18.0',0,'invalid_credentials','2026-10-14 21:57:49'),(51,1,'security.demo@pass.local','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36 Edg/155.0.0.0',1,NULL,'2026-10-14 22:00:15'),(52,1,'security.demo@pass.local','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36 Edg/155.0.0.0',1,NULL,'2026-10-14 22:08:43'),(53,1,'security.demo@pass.local','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Code/1.141.2 Chrome/150.0.7871.250 Electron/43.7.7 Safari/537.36',1,NULL,'2026-10-14 23:49:08'),(54,1,'security.demo@pass.local','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36',1,NULL,'2026-10-14 23:51:36'),(55,1,'security.demo@pass.local','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36',1,NULL,'2026-10-15 00:17:18'),(56,NULL,'ecurity.demo@pass.local','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Code/1.141.2 Chrome/150.0.7871.250 Electron/43.7.7 Safari/537.36',0,'invalid_credentials','2026-10-15 00:35:13'),(57,1,'security.demo@pass.local','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Code/1.141.2 Chrome/150.0.7871.250 Electron/43.7.7 Safari/537.36',1,NULL,'2026-10-15 00:35:21'),(58,1,'security.demo@pass.local','127.0.0.1','Mozilla/5.0 (Windows NT; Windows NT 10.0; en-PH) WindowsPowerShell/5.1.26100.9444',1,NULL,'2026-10-15 01:30:59'),(59,1,'security.demo@pass.local','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Code/1.141.2 Chrome/150.0.7871.250 Electron/43.7.7 Safari/537.36',1,NULL,'2026-10-15 01:31:42'),(60,1,'security.demo@pass.local','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Code/1.141.2 Chrome/150.0.7871.250 Electron/43.7.7 Safari/537.36',1,NULL,'2026-10-15 02:04:13'),(61,1,'security.demo@pass.local','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Code/1.141.2 Chrome/150.0.7871.250 Electron/43.7.7 Safari/537.36',1,NULL,'2026-10-15 02:36:21'),(62,1,'security.demo@pass.local','127.0.0.1','Mozilla/5.0 (Windows NT; Windows NT 10.0; en-PH) WindowsPowerShell/5.1.26100.9444',1,NULL,'2026-10-15 02:51:24'),(63,1,'security.demo@pass.local','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36',1,NULL,'2026-10-15 03:05:19'),(64,1,'security.demo@pass.local','127.0.0.1','Mozilla/5.0 (Windows NT; Windows NT 10.0; en-PH) WindowsPowerShell/5.1.26100.9444',1,NULL,'2026-10-15 04:59:46'),(65,1,'security.demo@pass.local','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36',0,'invalid_credentials','2026-10-15 05:08:41'),(66,1,'security.demo@pass.local','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36',0,'invalid_credentials','2026-10-15 05:08:55'),(67,1,'security.demo@pass.local','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36',0,'invalid_credentials','2026-10-15 05:08:59'),(68,1,'security.demo@pass.local','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36',1,NULL,'2026-10-15 05:09:10'),(69,NULL,'mali@gmail.com','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36',0,'invalid_credentials','2026-10-15 05:59:21'),(70,NULL,'mali@gmail.com','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36',0,'invalid_credentials','2026-10-15 06:00:21'),(71,NULL,'mali@gmail.com','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36',0,'invalid_credentials','2026-10-15 07:47:05'),(72,48,'rosa.villanueva.test@example.com','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/155.0.0.0 Safari/537.36 Edg/155.0.0.0',1,NULL,'2026-10-15 08:24:26'),(73,46,'admin.demo@pass.local','127.0.0.1','Mozilla/5.0 (Windows NT; Windows NT 10.0; en-PH) WindowsPowerShell/5.1.26100.9444',0,'invalid_credentials','2026-10-15 08:25:42'),(74,46,'admin.demo@pass.local','127.0.0.1','Mozilla/5.0 (Windows NT; Windows NT 10.0; en-PH) WindowsPowerShell/5.1.26100.9444',1,NULL,'2026-10-15 08:26:45'),(75,48,'rosa.villanueva.test@example.com','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/155.0.0.0 Safari/537.36 Edg/155.0.0.0',1,NULL,'2026-10-15 08:27:34'),(76,1,'security.demo@pass.local','127.0.0.1','Mozilla/5.0 (Windows NT; Windows NT 10.0; en-PH) WindowsPowerShell/5.1.26100.9444',1,NULL,'2026-10-15 08:28:32'),(77,48,'rosa.villanueva.test@example.com','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/155.0.0.0 Safari/537.36 Edg/155.0.0.0',1,NULL,'2026-10-15 08:30:28'),(78,49,'guest@pass.com','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Code/1.141.2 Chrome/150.0.7871.250 Electron/43.7.7 Safari/537.36',1,NULL,'2026-10-15 09:39:01'),(79,NULL,'admin@pass.edu','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36',0,'invalid_credentials','2026-10-15 10:18:09'),(80,NULL,'admin@pass.edu','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36',0,'invalid_credentials','2026-10-15 10:18:17'),(81,NULL,'admin@pass.edu','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36',0,'invalid_credentials','2026-10-15 10:18:31'),(82,1,'security.demo@pass.local','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36',1,NULL,'2026-10-15 10:42:48'),(83,1,'security.demo@pass.local','127.0.0.1','Mozilla/5.0 (Windows NT; Windows NT 10.0; en-PH) WindowsPowerShell/5.1.26100.9444',1,NULL,'2026-10-15 11:03:28'),(84,1,'security.demo@pass.local','127.0.0.1','Mozilla/5.0 (Windows NT; Windows NT 10.0; en-PH) WindowsPowerShell/5.1.26100.9444',1,NULL,'2026-10-15 11:03:28'),(85,1,'security.demo@pass.local','127.0.0.1','Mozilla/5.0 (Windows NT; Windows NT 10.0; en-PH) WindowsPowerShell/5.1.26100.9444',1,NULL,'2026-10-15 11:03:29'),(86,50,'lara.gomez.test@example.com','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/155.0.0.0 Safari/537.36 Edg/155.0.0.0',1,NULL,'2026-10-15 11:33:19'),(87,46,'admin.demo@pass.local','127.0.0.1','Mozilla/5.0 (Windows NT; Windows NT 10.0; en-PH) WindowsPowerShell/5.1.26100.9444',1,NULL,'2026-10-15 11:33:35'),(88,50,'lara.gomez.test@example.com','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/155.0.0.0 Safari/537.36 Edg/155.0.0.0',1,NULL,'2026-10-15 11:34:05'),(89,50,'lara.gomez.test@example.com','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/155.0.0.0 Safari/537.36 Edg/155.0.0.0',1,NULL,'2026-10-15 11:35:35'),(90,1,'security.demo@pass.local','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Code/1.141.2 Chrome/150.0.7871.250 Electron/43.7.7 Safari/537.36',1,NULL,'2026-10-15 11:46:24'),(91,1,'security.demo@pass.local','127.0.0.1','Mozilla/5.0 (Windows NT; Windows NT 10.0; en-PH) WindowsPowerShell/5.1.26100.9444',1,NULL,'2026-10-15 16:33:20'),(92,1,'security.demo@pass.local','127.0.0.1','Mozilla/5.0 (Windows NT; Windows NT 10.0; en-PH) WindowsPowerShell/5.1.26100.9444',1,NULL,'2026-10-15 16:38:30'),(93,46,'admin.demo@pass.local','127.0.0.1','Mozilla/5.0 (Windows NT; Windows NT 10.0; en-PH) WindowsPowerShell/5.1.26100.9444',1,NULL,'2026-10-15 16:38:57'),(94,1,'security.demo@pass.local','127.0.0.1','Mozilla/5.0 (Windows NT; Windows NT 10.0; en-PH) WindowsPowerShell/5.1.26100.9444',1,NULL,'2026-10-15 17:12:56'),(95,1,'security.demo@pass.local','127.0.0.1','curl/8.18.0',1,NULL,'2026-10-23 07:30:06'),(96,1,'security.demo@pass.local','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36 Edg/155.0.0.0',1,NULL,'2026-10-23 07:31:02'),(97,1,'security.demo@pass.local','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36 Edg/155.0.0.0',1,NULL,'2026-10-23 07:44:58'),(98,1,'security.demo@pass.local','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36',1,NULL,'2026-10-23 08:54:40');
/*!40000 ALTER TABLE `login_attempts` ENABLE KEYS */;
UNLOCK TABLES;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_unicode_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER `pass_history_login_attempts_insert` AFTER INSERT ON `login_attempts` FOR EACH ROW BEGIN INSERT INTO record_history (record_type, record_id, operation, actor_user_id, actor_label, source, ip_address, before_values, after_values, recorded_at)
                    VALUES ('login_attempts', CONCAT_WS(':', NEW.`attempt_id`), 'created', @pass_history_actor, LEFT(@pass_history_label, 100), LEFT(@pass_history_source, 150), LEFT(@pass_history_ip, 45), NULL, JSON_OBJECT('attempt_id', NEW.`attempt_id`, 'user_id', NEW.`user_id`, 'identifier', NEW.`identifier`, 'ip_address', NEW.`ip_address`, 'user_agent', NEW.`user_agent`, 'was_successful', NEW.`was_successful`, 'failure_reason', NEW.`failure_reason`, 'attempted_at', NEW.`attempted_at`), CURRENT_TIMESTAMP(6)); END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_unicode_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER `pass_history_login_attempts_update` AFTER UPDATE ON `login_attempts` FOR EACH ROW BEGIN IF NOT (OLD.`attempt_id` <=> NEW.`attempt_id`) OR NOT (OLD.`user_id` <=> NEW.`user_id`) OR NOT (OLD.`identifier` <=> NEW.`identifier`) OR NOT (OLD.`ip_address` <=> NEW.`ip_address`) OR NOT (OLD.`user_agent` <=> NEW.`user_agent`) OR NOT (OLD.`was_successful` <=> NEW.`was_successful`) OR NOT (OLD.`failure_reason` <=> NEW.`failure_reason`) OR NOT (OLD.`attempted_at` <=> NEW.`attempted_at`) THEN INSERT INTO record_history (record_type, record_id, operation, actor_user_id, actor_label, source, ip_address, before_values, after_values, recorded_at)
                    VALUES ('login_attempts', CONCAT_WS(':', NEW.`attempt_id`), 'updated', @pass_history_actor, LEFT(@pass_history_label, 100), LEFT(@pass_history_source, 150), LEFT(@pass_history_ip, 45), JSON_OBJECT('attempt_id', OLD.`attempt_id`, 'user_id', OLD.`user_id`, 'identifier', OLD.`identifier`, 'ip_address', OLD.`ip_address`, 'user_agent', OLD.`user_agent`, 'was_successful', OLD.`was_successful`, 'failure_reason', OLD.`failure_reason`, 'attempted_at', OLD.`attempted_at`), JSON_OBJECT('attempt_id', NEW.`attempt_id`, 'user_id', NEW.`user_id`, 'identifier', NEW.`identifier`, 'ip_address', NEW.`ip_address`, 'user_agent', NEW.`user_agent`, 'was_successful', NEW.`was_successful`, 'failure_reason', NEW.`failure_reason`, 'attempted_at', NEW.`attempted_at`), CURRENT_TIMESTAMP(6)); END IF; END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_unicode_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER `pass_history_login_attempts_delete` AFTER DELETE ON `login_attempts` FOR EACH ROW BEGIN INSERT INTO record_history (record_type, record_id, operation, actor_user_id, actor_label, source, ip_address, before_values, after_values, recorded_at)
                    VALUES ('login_attempts', CONCAT_WS(':', OLD.`attempt_id`), 'deleted', @pass_history_actor, LEFT(@pass_history_label, 100), LEFT(@pass_history_source, 150), LEFT(@pass_history_ip, 45), JSON_OBJECT('attempt_id', OLD.`attempt_id`, 'user_id', OLD.`user_id`, 'identifier', OLD.`identifier`, 'ip_address', OLD.`ip_address`, 'user_agent', OLD.`user_agent`, 'was_successful', OLD.`was_successful`, 'failure_reason', OLD.`failure_reason`, 'attempted_at', OLD.`attempted_at`), NULL, CURRENT_TIMESTAMP(6)); END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;

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
) ENGINE=InnoDB AUTO_INCREMENT=62 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `migrations`
--

LOCK TABLES `migrations` WRITE;
/*!40000 ALTER TABLE `migrations` DISABLE KEYS */;
INSERT INTO `migrations` VALUES (1,'2026_10_07_154030_create_departments_table',1),(2,'2026_10_07_154030_create_roles_table',1),(3,'2026_10_07_154031_create_permissions_table',1),(4,'2026_10_07_154042_create_vehicle_types_table',1),(5,'2026_10_07_154722_create_users_table',1),(6,'2026_10_07_154725_create_user_profiles_table',1),(7,'2026_10_07_154727_create_user_roles_table',1),(8,'2026_10_07_154730_create_role_permissions_table',1),(9,'2026_10_07_154815_create_login_attempts_table',1),(10,'2026_10_08_021551_create_parking_areas_table',1),(11,'2026_10_08_021553_create_visitors_table',1),(12,'2026_10_08_021556_create_drivers_table',1),(13,'2026_10_08_021558_create_driver_licenses_table',1),(14,'2026_10_08_024200_create_vehicles_table',1),(15,'2026_10_08_024203_create_vehicle_authorizations_table',1),(16,'2026_10_08_024205_create_vehicle_driver_table',1),(17,'2026_10_08_024210_add_owner_check_to_vehicles_table',1),(18,'2026_10_08_024526_create_access_gates_table',1),(19,'2026_10_08_024529_create_rfid_tags_table',1),(20,'2026_10_08_024531_create_rfid_readers_table',1),(21,'2026_10_08_024542_create_rfid_assignments_table',1),(22,'2026_10_08_024801_create_parking_zones_table',1),(23,'2026_10_08_024804_create_parking_slots_table',1),(24,'2026_10_08_024806_create_visitor_requests_table',1),(25,'2026_10_08_024809_create_qr_passes_table',1),(26,'2026_10_08_024940_create_access_events_table',1),(27,'2026_10_08_024942_create_parking_transactions_table',1),(28,'2026_10_08_025057_create_mobile_devices_table',1),(29,'2026_10_08_025059_create_notifications_table',1),(30,'2026_10_08_025102_create_violation_types_table',1),(31,'2026_10_08_025104_create_parking_violations_table',1),(32,'2026_10_08_025107_create_violation_evidences_table',1),(33,'2026_10_08_025109_create_audit_logs_table',1),(34,'2026_10_08_025113_create_system_settings_table',1),(35,'2026_10_08_030212_add_safeguards_to_tables',1),(36,'2026_10_08_031024_create_personal_access_tokens_table',1),(37,'2026_10_12_162535_add_school_id_document_path_to_user_profiles_table',1),(38,'2026_10_12_185503_create_password_reset_tokens_table',1),(39,'2026_10_13_132221_create_testings_table',2),(40,'2026_10_13_142010_add_suspended_status_to_vehicle_authorizations_table',3),(41,'2026_10_13_150336_add_verification_columns_to_driver_licenses_table',4),(42,'2026_10_14_035209_add_parking_operations_support',5),(43,'2026_10_14_050156_allow_shared_access_gates',6),(44,'2026_10_15_120000_link_visitors_to_guest_accounts',7),(45,'2026_10_14_185723_create_parking_sensors_table',8),(46,'2026_10_14_185724_create_parking_sensor_readings_table',8),(47,'2026_10_15_072537_add_review_fields_to_driver_licenses_table',8),(48,'2026_10_16_020000_add_vehicle_photo_path_to_vehicles',9),(49,'2026_10_16_034210_create_parking_slot_simulations_table',9),(50,'2026_10_16_100000_create_email_verification_codes_table',9),(51,'2026_10_16_110000_add_driver_details_to_visitor_requests',9),(52,'2026_10_16_120000_add_private_documents_to_visitor_requests',9),(53,'2026_10_16_130000_create_vehicle_release_requests_table',9),(54,'2026_10_16_140000_create_user_vehicle_selections_table',9),(55,'2026_10_19_020000_add_registration_id_checks_to_user_profiles',9),(56,'2026_10_20_010000_enforce_normalized_identifier_uniqueness',9),(57,'2026_10_20_020000_create_privileged_account_invitations_table',9),(58,'2026_10_20_030000_add_record_history_and_archiving',9),(59,'2026_10_21_010000_add_shs_academic_details',9),(60,'2026_10_21_020000_make_driver_license_type_optional',9),(61,'2026_10_22_010000_add_vehicle_plate_checks',9);
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
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_unicode_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER `pass_history_parking_areas_insert` AFTER INSERT ON `parking_areas` FOR EACH ROW BEGIN INSERT INTO record_history (record_type, record_id, operation, actor_user_id, actor_label, source, ip_address, before_values, after_values, recorded_at)
                    VALUES ('parking_areas', CONCAT_WS(':', NEW.`parking_area_id`), 'created', @pass_history_actor, LEFT(@pass_history_label, 100), LEFT(@pass_history_source, 150), LEFT(@pass_history_ip, 45), NULL, JSON_OBJECT('parking_area_id', NEW.`parking_area_id`, 'area_code', NEW.`area_code`, 'area_name', NEW.`area_name`, 'building', NEW.`building`, 'floor_level', NEW.`floor_level`, 'description', NEW.`description`, 'latitude', NEW.`latitude`, 'longitude', NEW.`longitude`, 'is_active', NEW.`is_active`), CURRENT_TIMESTAMP(6)); END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_unicode_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER `pass_history_parking_areas_update` AFTER UPDATE ON `parking_areas` FOR EACH ROW BEGIN IF NOT (OLD.`parking_area_id` <=> NEW.`parking_area_id`) OR NOT (OLD.`area_code` <=> NEW.`area_code`) OR NOT (OLD.`area_name` <=> NEW.`area_name`) OR NOT (OLD.`building` <=> NEW.`building`) OR NOT (OLD.`floor_level` <=> NEW.`floor_level`) OR NOT (OLD.`description` <=> NEW.`description`) OR NOT (OLD.`latitude` <=> NEW.`latitude`) OR NOT (OLD.`longitude` <=> NEW.`longitude`) OR NOT (OLD.`is_active` <=> NEW.`is_active`) THEN INSERT INTO record_history (record_type, record_id, operation, actor_user_id, actor_label, source, ip_address, before_values, after_values, recorded_at)
                    VALUES ('parking_areas', CONCAT_WS(':', NEW.`parking_area_id`), 'updated', @pass_history_actor, LEFT(@pass_history_label, 100), LEFT(@pass_history_source, 150), LEFT(@pass_history_ip, 45), JSON_OBJECT('parking_area_id', OLD.`parking_area_id`, 'area_code', OLD.`area_code`, 'area_name', OLD.`area_name`, 'building', OLD.`building`, 'floor_level', OLD.`floor_level`, 'description', OLD.`description`, 'latitude', OLD.`latitude`, 'longitude', OLD.`longitude`, 'is_active', OLD.`is_active`), JSON_OBJECT('parking_area_id', NEW.`parking_area_id`, 'area_code', NEW.`area_code`, 'area_name', NEW.`area_name`, 'building', NEW.`building`, 'floor_level', NEW.`floor_level`, 'description', NEW.`description`, 'latitude', NEW.`latitude`, 'longitude', NEW.`longitude`, 'is_active', NEW.`is_active`), CURRENT_TIMESTAMP(6)); END IF; END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_unicode_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER `pass_history_parking_areas_delete` AFTER DELETE ON `parking_areas` FOR EACH ROW BEGIN INSERT INTO record_history (record_type, record_id, operation, actor_user_id, actor_label, source, ip_address, before_values, after_values, recorded_at)
                    VALUES ('parking_areas', CONCAT_WS(':', OLD.`parking_area_id`), 'deleted', @pass_history_actor, LEFT(@pass_history_label, 100), LEFT(@pass_history_source, 150), LEFT(@pass_history_ip, 45), JSON_OBJECT('parking_area_id', OLD.`parking_area_id`, 'area_code', OLD.`area_code`, 'area_name', OLD.`area_name`, 'building', OLD.`building`, 'floor_level', OLD.`floor_level`, 'description', OLD.`description`, 'latitude', OLD.`latitude`, 'longitude', OLD.`longitude`, 'is_active', OLD.`is_active`), NULL, CURRENT_TIMESTAMP(6)); END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;

--
-- Table structure for table `parking_sensor_readings`
--

DROP TABLE IF EXISTS `parking_sensor_readings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `parking_sensor_readings` (
  `reading_id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `sensor_id` bigint unsigned NOT NULL,
  `occupancy_status` enum('occupied','vacant','unknown') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'unknown',
  `detected_at` timestamp NOT NULL,
  `received_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `source` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'sensor',
  `event_reference` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `payload` json DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`reading_id`),
  KEY `parking_sensor_readings_sensor_id_detected_at_index` (`sensor_id`,`detected_at`),
  KEY `parking_sensor_readings_occupancy_status_detected_at_index` (`occupancy_status`,`detected_at`),
  CONSTRAINT `parking_sensor_readings_sensor_id_foreign` FOREIGN KEY (`sensor_id`) REFERENCES `parking_sensors` (`sensor_id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `parking_sensor_readings`
--

LOCK TABLES `parking_sensor_readings` WRITE;
/*!40000 ALTER TABLE `parking_sensor_readings` DISABLE KEYS */;
/*!40000 ALTER TABLE `parking_sensor_readings` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `parking_sensors`
--

DROP TABLE IF EXISTS `parking_sensors`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `parking_sensors` (
  `sensor_id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `slot_id` bigint unsigned NOT NULL,
  `sensor_code` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `sensor_type` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `hardware_model` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `sensor_status` enum('unconfigured','online','offline','maintenance') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'unconfigured',
  `last_seen_at` timestamp NULL DEFAULT NULL,
  `installed_at` timestamp NULL DEFAULT NULL,
  `notes` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`sensor_id`),
  UNIQUE KEY `parking_sensors_sensor_code_unique` (`sensor_code`),
  KEY `parking_sensors_slot_id_sensor_status_index` (`slot_id`,`sensor_status`),
  CONSTRAINT `parking_sensors_slot_id_foreign` FOREIGN KEY (`slot_id`) REFERENCES `parking_slots` (`slot_id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `parking_sensors`
--

LOCK TABLES `parking_sensors` WRITE;
/*!40000 ALTER TABLE `parking_sensors` DISABLE KEYS */;
/*!40000 ALTER TABLE `parking_sensors` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `parking_slot_simulations`
--

DROP TABLE IF EXISTS `parking_slot_simulations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `parking_slot_simulations` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `vehicle_type` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `slot_number` int unsigned NOT NULL,
  `is_occupied` tinyint(1) NOT NULL DEFAULT '0',
  `updated_by` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `parking_slot_simulations_vehicle_type_slot_number_unique` (`vehicle_type`,`slot_number`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `parking_slot_simulations`
--

LOCK TABLES `parking_slot_simulations` WRITE;
/*!40000 ALTER TABLE `parking_slot_simulations` DISABLE KEYS */;
/*!40000 ALTER TABLE `parking_slot_simulations` ENABLE KEYS */;
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
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_unicode_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER `pass_history_parking_slots_insert` AFTER INSERT ON `parking_slots` FOR EACH ROW BEGIN INSERT INTO record_history (record_type, record_id, operation, actor_user_id, actor_label, source, ip_address, before_values, after_values, recorded_at)
                    VALUES ('parking_slots', CONCAT_WS(':', NEW.`slot_id`), 'created', @pass_history_actor, LEFT(@pass_history_label, 100), LEFT(@pass_history_source, 150), LEFT(@pass_history_ip, 45), NULL, JSON_OBJECT('slot_id', NEW.`slot_id`, 'zone_id', NEW.`zone_id`, 'slot_code', NEW.`slot_code`, 'vehicle_type_id', NEW.`vehicle_type_id`, 'slot_status', NEW.`slot_status`, 'is_pwd_slot', NEW.`is_pwd_slot`, 'reserved_for_user_id', NEW.`reserved_for_user_id`, 'pos_x', NEW.`pos_x`, 'pos_y', NEW.`pos_y`, 'slot_width', NEW.`slot_width`, 'slot_height', NEW.`slot_height`, 'rotation_deg', NEW.`rotation_deg`, 'created_at', NEW.`created_at`, 'updated_at', NEW.`updated_at`), CURRENT_TIMESTAMP(6)); END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_unicode_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER `pass_history_parking_slots_update` AFTER UPDATE ON `parking_slots` FOR EACH ROW BEGIN IF NOT (OLD.`slot_id` <=> NEW.`slot_id`) OR NOT (OLD.`zone_id` <=> NEW.`zone_id`) OR NOT (OLD.`slot_code` <=> NEW.`slot_code`) OR NOT (OLD.`vehicle_type_id` <=> NEW.`vehicle_type_id`) OR NOT (OLD.`slot_status` <=> NEW.`slot_status`) OR NOT (OLD.`is_pwd_slot` <=> NEW.`is_pwd_slot`) OR NOT (OLD.`reserved_for_user_id` <=> NEW.`reserved_for_user_id`) OR NOT (OLD.`pos_x` <=> NEW.`pos_x`) OR NOT (OLD.`pos_y` <=> NEW.`pos_y`) OR NOT (OLD.`slot_width` <=> NEW.`slot_width`) OR NOT (OLD.`slot_height` <=> NEW.`slot_height`) OR NOT (OLD.`rotation_deg` <=> NEW.`rotation_deg`) OR NOT (OLD.`created_at` <=> NEW.`created_at`) OR NOT (OLD.`updated_at` <=> NEW.`updated_at`) THEN INSERT INTO record_history (record_type, record_id, operation, actor_user_id, actor_label, source, ip_address, before_values, after_values, recorded_at)
                    VALUES ('parking_slots', CONCAT_WS(':', NEW.`slot_id`), 'updated', @pass_history_actor, LEFT(@pass_history_label, 100), LEFT(@pass_history_source, 150), LEFT(@pass_history_ip, 45), JSON_OBJECT('slot_id', OLD.`slot_id`, 'zone_id', OLD.`zone_id`, 'slot_code', OLD.`slot_code`, 'vehicle_type_id', OLD.`vehicle_type_id`, 'slot_status', OLD.`slot_status`, 'is_pwd_slot', OLD.`is_pwd_slot`, 'reserved_for_user_id', OLD.`reserved_for_user_id`, 'pos_x', OLD.`pos_x`, 'pos_y', OLD.`pos_y`, 'slot_width', OLD.`slot_width`, 'slot_height', OLD.`slot_height`, 'rotation_deg', OLD.`rotation_deg`, 'created_at', OLD.`created_at`, 'updated_at', OLD.`updated_at`), JSON_OBJECT('slot_id', NEW.`slot_id`, 'zone_id', NEW.`zone_id`, 'slot_code', NEW.`slot_code`, 'vehicle_type_id', NEW.`vehicle_type_id`, 'slot_status', NEW.`slot_status`, 'is_pwd_slot', NEW.`is_pwd_slot`, 'reserved_for_user_id', NEW.`reserved_for_user_id`, 'pos_x', NEW.`pos_x`, 'pos_y', NEW.`pos_y`, 'slot_width', NEW.`slot_width`, 'slot_height', NEW.`slot_height`, 'rotation_deg', NEW.`rotation_deg`, 'created_at', NEW.`created_at`, 'updated_at', NEW.`updated_at`), CURRENT_TIMESTAMP(6)); END IF; END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_unicode_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER `pass_history_parking_slots_delete` AFTER DELETE ON `parking_slots` FOR EACH ROW BEGIN INSERT INTO record_history (record_type, record_id, operation, actor_user_id, actor_label, source, ip_address, before_values, after_values, recorded_at)
                    VALUES ('parking_slots', CONCAT_WS(':', OLD.`slot_id`), 'deleted', @pass_history_actor, LEFT(@pass_history_label, 100), LEFT(@pass_history_source, 150), LEFT(@pass_history_ip, 45), JSON_OBJECT('slot_id', OLD.`slot_id`, 'zone_id', OLD.`zone_id`, 'slot_code', OLD.`slot_code`, 'vehicle_type_id', OLD.`vehicle_type_id`, 'slot_status', OLD.`slot_status`, 'is_pwd_slot', OLD.`is_pwd_slot`, 'reserved_for_user_id', OLD.`reserved_for_user_id`, 'pos_x', OLD.`pos_x`, 'pos_y', OLD.`pos_y`, 'slot_width', OLD.`slot_width`, 'slot_height', OLD.`slot_height`, 'rotation_deg', OLD.`rotation_deg`, 'created_at', OLD.`created_at`, 'updated_at', OLD.`updated_at`), NULL, CURRENT_TIMESTAMP(6)); END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;

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
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_unicode_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER `pass_history_parking_transactions_insert` AFTER INSERT ON `parking_transactions` FOR EACH ROW BEGIN INSERT INTO record_history (record_type, record_id, operation, actor_user_id, actor_label, source, ip_address, before_values, after_values, recorded_at)
                    VALUES ('parking_transactions', CONCAT_WS(':', NEW.`transaction_id`), 'created', @pass_history_actor, LEFT(@pass_history_label, 100), LEFT(@pass_history_source, 150), LEFT(@pass_history_ip, 45), NULL, JSON_OBJECT('transaction_id', NEW.`transaction_id`, 'ticket_number', NEW.`ticket_number`, 'vehicle_id', NEW.`vehicle_id`, 'driver_id', NEW.`driver_id`, 'user_id', NEW.`user_id`, 'visitor_id', NEW.`visitor_id`, 'parking_area_id', NEW.`parking_area_id`, 'slot_id', NEW.`slot_id`, 'slot_confirmed_at', NEW.`slot_confirmed_at`, 'slot_confirmed_by', NEW.`slot_confirmed_by`, 'access_method', NEW.`access_method`, 'entry_event_id', NEW.`entry_event_id`, 'exit_event_id', NEW.`exit_event_id`, 'entry_datetime', NEW.`entry_datetime`, 'exit_datetime', NEW.`exit_datetime`, 'transaction_status', NEW.`transaction_status`, 'recorded_by', NEW.`recorded_by`, 'remarks', NEW.`remarks`, 'created_at', NEW.`created_at`, 'updated_at', NEW.`updated_at`, 'active_vehicle_flag', NEW.`active_vehicle_flag`, 'active_slot_flag', NEW.`active_slot_flag`, 'duration_minutes', NEW.`duration_minutes`), CURRENT_TIMESTAMP(6)); END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_unicode_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER `pass_history_parking_transactions_update` AFTER UPDATE ON `parking_transactions` FOR EACH ROW BEGIN IF NOT (OLD.`transaction_id` <=> NEW.`transaction_id`) OR NOT (OLD.`ticket_number` <=> NEW.`ticket_number`) OR NOT (OLD.`vehicle_id` <=> NEW.`vehicle_id`) OR NOT (OLD.`driver_id` <=> NEW.`driver_id`) OR NOT (OLD.`user_id` <=> NEW.`user_id`) OR NOT (OLD.`visitor_id` <=> NEW.`visitor_id`) OR NOT (OLD.`parking_area_id` <=> NEW.`parking_area_id`) OR NOT (OLD.`slot_id` <=> NEW.`slot_id`) OR NOT (OLD.`slot_confirmed_at` <=> NEW.`slot_confirmed_at`) OR NOT (OLD.`slot_confirmed_by` <=> NEW.`slot_confirmed_by`) OR NOT (OLD.`access_method` <=> NEW.`access_method`) OR NOT (OLD.`entry_event_id` <=> NEW.`entry_event_id`) OR NOT (OLD.`exit_event_id` <=> NEW.`exit_event_id`) OR NOT (OLD.`entry_datetime` <=> NEW.`entry_datetime`) OR NOT (OLD.`exit_datetime` <=> NEW.`exit_datetime`) OR NOT (OLD.`transaction_status` <=> NEW.`transaction_status`) OR NOT (OLD.`recorded_by` <=> NEW.`recorded_by`) OR NOT (OLD.`remarks` <=> NEW.`remarks`) OR NOT (OLD.`created_at` <=> NEW.`created_at`) OR NOT (OLD.`updated_at` <=> NEW.`updated_at`) OR NOT (OLD.`active_vehicle_flag` <=> NEW.`active_vehicle_flag`) OR NOT (OLD.`active_slot_flag` <=> NEW.`active_slot_flag`) OR NOT (OLD.`duration_minutes` <=> NEW.`duration_minutes`) THEN INSERT INTO record_history (record_type, record_id, operation, actor_user_id, actor_label, source, ip_address, before_values, after_values, recorded_at)
                    VALUES ('parking_transactions', CONCAT_WS(':', NEW.`transaction_id`), 'updated', @pass_history_actor, LEFT(@pass_history_label, 100), LEFT(@pass_history_source, 150), LEFT(@pass_history_ip, 45), JSON_OBJECT('transaction_id', OLD.`transaction_id`, 'ticket_number', OLD.`ticket_number`, 'vehicle_id', OLD.`vehicle_id`, 'driver_id', OLD.`driver_id`, 'user_id', OLD.`user_id`, 'visitor_id', OLD.`visitor_id`, 'parking_area_id', OLD.`parking_area_id`, 'slot_id', OLD.`slot_id`, 'slot_confirmed_at', OLD.`slot_confirmed_at`, 'slot_confirmed_by', OLD.`slot_confirmed_by`, 'access_method', OLD.`access_method`, 'entry_event_id', OLD.`entry_event_id`, 'exit_event_id', OLD.`exit_event_id`, 'entry_datetime', OLD.`entry_datetime`, 'exit_datetime', OLD.`exit_datetime`, 'transaction_status', OLD.`transaction_status`, 'recorded_by', OLD.`recorded_by`, 'remarks', OLD.`remarks`, 'created_at', OLD.`created_at`, 'updated_at', OLD.`updated_at`, 'active_vehicle_flag', OLD.`active_vehicle_flag`, 'active_slot_flag', OLD.`active_slot_flag`, 'duration_minutes', OLD.`duration_minutes`), JSON_OBJECT('transaction_id', NEW.`transaction_id`, 'ticket_number', NEW.`ticket_number`, 'vehicle_id', NEW.`vehicle_id`, 'driver_id', NEW.`driver_id`, 'user_id', NEW.`user_id`, 'visitor_id', NEW.`visitor_id`, 'parking_area_id', NEW.`parking_area_id`, 'slot_id', NEW.`slot_id`, 'slot_confirmed_at', NEW.`slot_confirmed_at`, 'slot_confirmed_by', NEW.`slot_confirmed_by`, 'access_method', NEW.`access_method`, 'entry_event_id', NEW.`entry_event_id`, 'exit_event_id', NEW.`exit_event_id`, 'entry_datetime', NEW.`entry_datetime`, 'exit_datetime', NEW.`exit_datetime`, 'transaction_status', NEW.`transaction_status`, 'recorded_by', NEW.`recorded_by`, 'remarks', NEW.`remarks`, 'created_at', NEW.`created_at`, 'updated_at', NEW.`updated_at`, 'active_vehicle_flag', NEW.`active_vehicle_flag`, 'active_slot_flag', NEW.`active_slot_flag`, 'duration_minutes', NEW.`duration_minutes`), CURRENT_TIMESTAMP(6)); END IF; END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_unicode_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER `pass_history_parking_transactions_delete` AFTER DELETE ON `parking_transactions` FOR EACH ROW BEGIN INSERT INTO record_history (record_type, record_id, operation, actor_user_id, actor_label, source, ip_address, before_values, after_values, recorded_at)
                    VALUES ('parking_transactions', CONCAT_WS(':', OLD.`transaction_id`), 'deleted', @pass_history_actor, LEFT(@pass_history_label, 100), LEFT(@pass_history_source, 150), LEFT(@pass_history_ip, 45), JSON_OBJECT('transaction_id', OLD.`transaction_id`, 'ticket_number', OLD.`ticket_number`, 'vehicle_id', OLD.`vehicle_id`, 'driver_id', OLD.`driver_id`, 'user_id', OLD.`user_id`, 'visitor_id', OLD.`visitor_id`, 'parking_area_id', OLD.`parking_area_id`, 'slot_id', OLD.`slot_id`, 'slot_confirmed_at', OLD.`slot_confirmed_at`, 'slot_confirmed_by', OLD.`slot_confirmed_by`, 'access_method', OLD.`access_method`, 'entry_event_id', OLD.`entry_event_id`, 'exit_event_id', OLD.`exit_event_id`, 'entry_datetime', OLD.`entry_datetime`, 'exit_datetime', OLD.`exit_datetime`, 'transaction_status', OLD.`transaction_status`, 'recorded_by', OLD.`recorded_by`, 'remarks', OLD.`remarks`, 'created_at', OLD.`created_at`, 'updated_at', OLD.`updated_at`, 'active_vehicle_flag', OLD.`active_vehicle_flag`, 'active_slot_flag', OLD.`active_slot_flag`, 'duration_minutes', OLD.`duration_minutes`), NULL, CURRENT_TIMESTAMP(6)); END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;

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
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_unicode_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER `pass_history_parking_violations_insert` AFTER INSERT ON `parking_violations` FOR EACH ROW BEGIN INSERT INTO record_history (record_type, record_id, operation, actor_user_id, actor_label, source, ip_address, before_values, after_values, recorded_at)
                    VALUES ('parking_violations', CONCAT_WS(':', NEW.`violation_id`), 'created', @pass_history_actor, LEFT(@pass_history_label, 100), LEFT(@pass_history_source, 150), LEFT(@pass_history_ip, 45), NULL, JSON_OBJECT('violation_id', NEW.`violation_id`, 'violation_type_id', NEW.`violation_type_id`, 'parking_transaction_id', NEW.`parking_transaction_id`, 'vehicle_id', NEW.`vehicle_id`, 'user_id', NEW.`user_id`, 'visitor_id', NEW.`visitor_id`, 'slot_id', NEW.`slot_id`, 'expected_slot_id', NEW.`expected_slot_id`, 'parking_area_id', NEW.`parking_area_id`, 'occurred_at', NEW.`occurred_at`, 'reported_by', NEW.`reported_by`, 'reported_at', NEW.`reported_at`, 'description', NEW.`description`, 'penalty_amount', NEW.`penalty_amount`, 'violation_status', NEW.`violation_status`, 'resolved_by', NEW.`resolved_by`, 'resolved_at', NEW.`resolved_at`, 'resolution_remarks', NEW.`resolution_remarks`, 'created_at', NEW.`created_at`, 'updated_at', NEW.`updated_at`), CURRENT_TIMESTAMP(6)); END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_unicode_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER `pass_history_parking_violations_update` AFTER UPDATE ON `parking_violations` FOR EACH ROW BEGIN IF NOT (OLD.`violation_id` <=> NEW.`violation_id`) OR NOT (OLD.`violation_type_id` <=> NEW.`violation_type_id`) OR NOT (OLD.`parking_transaction_id` <=> NEW.`parking_transaction_id`) OR NOT (OLD.`vehicle_id` <=> NEW.`vehicle_id`) OR NOT (OLD.`user_id` <=> NEW.`user_id`) OR NOT (OLD.`visitor_id` <=> NEW.`visitor_id`) OR NOT (OLD.`slot_id` <=> NEW.`slot_id`) OR NOT (OLD.`expected_slot_id` <=> NEW.`expected_slot_id`) OR NOT (OLD.`parking_area_id` <=> NEW.`parking_area_id`) OR NOT (OLD.`occurred_at` <=> NEW.`occurred_at`) OR NOT (OLD.`reported_by` <=> NEW.`reported_by`) OR NOT (OLD.`reported_at` <=> NEW.`reported_at`) OR NOT (OLD.`description` <=> NEW.`description`) OR NOT (OLD.`penalty_amount` <=> NEW.`penalty_amount`) OR NOT (OLD.`violation_status` <=> NEW.`violation_status`) OR NOT (OLD.`resolved_by` <=> NEW.`resolved_by`) OR NOT (OLD.`resolved_at` <=> NEW.`resolved_at`) OR NOT (OLD.`resolution_remarks` <=> NEW.`resolution_remarks`) OR NOT (OLD.`created_at` <=> NEW.`created_at`) OR NOT (OLD.`updated_at` <=> NEW.`updated_at`) THEN INSERT INTO record_history (record_type, record_id, operation, actor_user_id, actor_label, source, ip_address, before_values, after_values, recorded_at)
                    VALUES ('parking_violations', CONCAT_WS(':', NEW.`violation_id`), 'updated', @pass_history_actor, LEFT(@pass_history_label, 100), LEFT(@pass_history_source, 150), LEFT(@pass_history_ip, 45), JSON_OBJECT('violation_id', OLD.`violation_id`, 'violation_type_id', OLD.`violation_type_id`, 'parking_transaction_id', OLD.`parking_transaction_id`, 'vehicle_id', OLD.`vehicle_id`, 'user_id', OLD.`user_id`, 'visitor_id', OLD.`visitor_id`, 'slot_id', OLD.`slot_id`, 'expected_slot_id', OLD.`expected_slot_id`, 'parking_area_id', OLD.`parking_area_id`, 'occurred_at', OLD.`occurred_at`, 'reported_by', OLD.`reported_by`, 'reported_at', OLD.`reported_at`, 'description', OLD.`description`, 'penalty_amount', OLD.`penalty_amount`, 'violation_status', OLD.`violation_status`, 'resolved_by', OLD.`resolved_by`, 'resolved_at', OLD.`resolved_at`, 'resolution_remarks', OLD.`resolution_remarks`, 'created_at', OLD.`created_at`, 'updated_at', OLD.`updated_at`), JSON_OBJECT('violation_id', NEW.`violation_id`, 'violation_type_id', NEW.`violation_type_id`, 'parking_transaction_id', NEW.`parking_transaction_id`, 'vehicle_id', NEW.`vehicle_id`, 'user_id', NEW.`user_id`, 'visitor_id', NEW.`visitor_id`, 'slot_id', NEW.`slot_id`, 'expected_slot_id', NEW.`expected_slot_id`, 'parking_area_id', NEW.`parking_area_id`, 'occurred_at', NEW.`occurred_at`, 'reported_by', NEW.`reported_by`, 'reported_at', NEW.`reported_at`, 'description', NEW.`description`, 'penalty_amount', NEW.`penalty_amount`, 'violation_status', NEW.`violation_status`, 'resolved_by', NEW.`resolved_by`, 'resolved_at', NEW.`resolved_at`, 'resolution_remarks', NEW.`resolution_remarks`, 'created_at', NEW.`created_at`, 'updated_at', NEW.`updated_at`), CURRENT_TIMESTAMP(6)); END IF; END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_unicode_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER `pass_history_parking_violations_delete` AFTER DELETE ON `parking_violations` FOR EACH ROW BEGIN INSERT INTO record_history (record_type, record_id, operation, actor_user_id, actor_label, source, ip_address, before_values, after_values, recorded_at)
                    VALUES ('parking_violations', CONCAT_WS(':', OLD.`violation_id`), 'deleted', @pass_history_actor, LEFT(@pass_history_label, 100), LEFT(@pass_history_source, 150), LEFT(@pass_history_ip, 45), JSON_OBJECT('violation_id', OLD.`violation_id`, 'violation_type_id', OLD.`violation_type_id`, 'parking_transaction_id', OLD.`parking_transaction_id`, 'vehicle_id', OLD.`vehicle_id`, 'user_id', OLD.`user_id`, 'visitor_id', OLD.`visitor_id`, 'slot_id', OLD.`slot_id`, 'expected_slot_id', OLD.`expected_slot_id`, 'parking_area_id', OLD.`parking_area_id`, 'occurred_at', OLD.`occurred_at`, 'reported_by', OLD.`reported_by`, 'reported_at', OLD.`reported_at`, 'description', OLD.`description`, 'penalty_amount', OLD.`penalty_amount`, 'violation_status', OLD.`violation_status`, 'resolved_by', OLD.`resolved_by`, 'resolved_at', OLD.`resolved_at`, 'resolution_remarks', OLD.`resolution_remarks`, 'created_at', OLD.`created_at`, 'updated_at', OLD.`updated_at`), NULL, CURRENT_TIMESTAMP(6)); END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;

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
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_unicode_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER `pass_history_parking_zones_insert` AFTER INSERT ON `parking_zones` FOR EACH ROW BEGIN INSERT INTO record_history (record_type, record_id, operation, actor_user_id, actor_label, source, ip_address, before_values, after_values, recorded_at)
                    VALUES ('parking_zones', CONCAT_WS(':', NEW.`zone_id`), 'created', @pass_history_actor, LEFT(@pass_history_label, 100), LEFT(@pass_history_source, 150), LEFT(@pass_history_ip, 45), NULL, JSON_OBJECT('zone_id', NEW.`zone_id`, 'parking_area_id', NEW.`parking_area_id`, 'zone_code', NEW.`zone_code`, 'zone_name', NEW.`zone_name`, 'vehicle_type_id', NEW.`vehicle_type_id`, 'declared_capacity', NEW.`declared_capacity`, 'layout_width', NEW.`layout_width`, 'layout_height', NEW.`layout_height`, 'is_active', NEW.`is_active`, 'created_at', NEW.`created_at`, 'updated_at', NEW.`updated_at`), CURRENT_TIMESTAMP(6)); END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_unicode_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER `pass_history_parking_zones_update` AFTER UPDATE ON `parking_zones` FOR EACH ROW BEGIN IF NOT (OLD.`zone_id` <=> NEW.`zone_id`) OR NOT (OLD.`parking_area_id` <=> NEW.`parking_area_id`) OR NOT (OLD.`zone_code` <=> NEW.`zone_code`) OR NOT (OLD.`zone_name` <=> NEW.`zone_name`) OR NOT (OLD.`vehicle_type_id` <=> NEW.`vehicle_type_id`) OR NOT (OLD.`declared_capacity` <=> NEW.`declared_capacity`) OR NOT (OLD.`layout_width` <=> NEW.`layout_width`) OR NOT (OLD.`layout_height` <=> NEW.`layout_height`) OR NOT (OLD.`is_active` <=> NEW.`is_active`) OR NOT (OLD.`created_at` <=> NEW.`created_at`) OR NOT (OLD.`updated_at` <=> NEW.`updated_at`) THEN INSERT INTO record_history (record_type, record_id, operation, actor_user_id, actor_label, source, ip_address, before_values, after_values, recorded_at)
                    VALUES ('parking_zones', CONCAT_WS(':', NEW.`zone_id`), 'updated', @pass_history_actor, LEFT(@pass_history_label, 100), LEFT(@pass_history_source, 150), LEFT(@pass_history_ip, 45), JSON_OBJECT('zone_id', OLD.`zone_id`, 'parking_area_id', OLD.`parking_area_id`, 'zone_code', OLD.`zone_code`, 'zone_name', OLD.`zone_name`, 'vehicle_type_id', OLD.`vehicle_type_id`, 'declared_capacity', OLD.`declared_capacity`, 'layout_width', OLD.`layout_width`, 'layout_height', OLD.`layout_height`, 'is_active', OLD.`is_active`, 'created_at', OLD.`created_at`, 'updated_at', OLD.`updated_at`), JSON_OBJECT('zone_id', NEW.`zone_id`, 'parking_area_id', NEW.`parking_area_id`, 'zone_code', NEW.`zone_code`, 'zone_name', NEW.`zone_name`, 'vehicle_type_id', NEW.`vehicle_type_id`, 'declared_capacity', NEW.`declared_capacity`, 'layout_width', NEW.`layout_width`, 'layout_height', NEW.`layout_height`, 'is_active', NEW.`is_active`, 'created_at', NEW.`created_at`, 'updated_at', NEW.`updated_at`), CURRENT_TIMESTAMP(6)); END IF; END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_unicode_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER `pass_history_parking_zones_delete` AFTER DELETE ON `parking_zones` FOR EACH ROW BEGIN INSERT INTO record_history (record_type, record_id, operation, actor_user_id, actor_label, source, ip_address, before_values, after_values, recorded_at)
                    VALUES ('parking_zones', CONCAT_WS(':', OLD.`zone_id`), 'deleted', @pass_history_actor, LEFT(@pass_history_label, 100), LEFT(@pass_history_source, 150), LEFT(@pass_history_ip, 45), JSON_OBJECT('zone_id', OLD.`zone_id`, 'parking_area_id', OLD.`parking_area_id`, 'zone_code', OLD.`zone_code`, 'zone_name', OLD.`zone_name`, 'vehicle_type_id', OLD.`vehicle_type_id`, 'declared_capacity', OLD.`declared_capacity`, 'layout_width', OLD.`layout_width`, 'layout_height', OLD.`layout_height`, 'is_active', OLD.`is_active`, 'created_at', OLD.`created_at`, 'updated_at', OLD.`updated_at`), NULL, CURRENT_TIMESTAMP(6)); END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;

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
) ENGINE=InnoDB AUTO_INCREMENT=130 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `personal_access_tokens`
--

LOCK TABLES `personal_access_tokens` WRITE;
/*!40000 ALTER TABLE `personal_access_tokens` DISABLE KEYS */;
INSERT INTO `personal_access_tokens` VALUES (53,'App\\Models\\User',36,'web','b059ad87adc182600a6440a6ad9af2a4d278553198864772855bdc9f0554689b','[\"*\"]','2026-10-13 20:08:15',NULL,'2026-10-13 20:08:10','2026-10-13 20:08:15'),(55,'App\\Models\\User',36,'web','7ca09dd74153610c3de6a6e17f6bbcdfdf03b7d7e9ba9335318817fc3db1d3d5','[\"*\"]','2026-10-13 20:09:55',NULL,'2026-10-13 20:09:49','2026-10-13 20:09:55'),(56,'App\\Models\\User',32,'web','b9b7ccb3ada02efb8a2409ae64d39be5a7be0516c209489988fe8b090fc1f495','[\"*\"]',NULL,NULL,'2026-10-13 20:10:14','2026-10-13 20:10:14'),(57,'App\\Models\\User',33,'web','72381ff356c5a78da0dce276a7633138d16081e19aa81dee1027f29c9ada08dd','[\"*\"]',NULL,NULL,'2026-10-13 20:10:14','2026-10-13 20:10:14'),(58,'App\\Models\\User',34,'web','695d6b11392b518d814018fc4f6450431d1fe4510495b9ad71e0ac2d748f0cf1','[\"*\"]',NULL,NULL,'2026-10-13 20:10:15','2026-10-13 20:10:15'),(59,'App\\Models\\User',35,'web','f27411d020e25864d6bd308f99aa03fb2d3323435aa5428e41ddc3cc45d87a4e','[\"*\"]',NULL,NULL,'2026-10-13 20:10:15','2026-10-13 20:10:15'),(60,'App\\Models\\User',36,'web','a616f997f26380da571aa1610f470952b3f87d7c5057eab72a17844ad1477cfb','[\"*\"]',NULL,NULL,'2026-10-13 20:10:16','2026-10-13 20:10:16'),(79,'App\\Models\\User',36,'web','bc8aaa9789dfe41c513769bf00d2f94c7319fabd5ba3b66751a7844df7319ab9','[\"*\"]','2026-10-13 21:10:36',NULL,'2026-10-13 21:10:32','2026-10-13 21:10:36'),(80,'App\\Models\\User',33,'web','a6779703e3e5c6c54a997b7ac019e8c1e3f598a9504bb3c7657196885774e9df','[\"*\"]','2026-10-13 21:10:57',NULL,'2026-10-13 21:10:55','2026-10-13 21:10:57'),(107,'App\\Models\\User',48,'web','c01e4bbb529f85cded5dc61b7948ab826f840d1fc00ec9d896be0e4fc308c066','[\"*\"]','2026-10-15 00:25:10',NULL,'2026-10-15 00:24:26','2026-10-15 00:25:10'),(109,'App\\Models\\User',48,'web','2f3f04bee1500a6bd9a0816d026586f4d1b53b872d5f6ccdce1b9e6d6d42620e','[\"*\"]','2026-10-15 00:27:35',NULL,'2026-10-15 00:27:34','2026-10-15 00:27:35'),(111,'App\\Models\\User',48,'web','059283c1568b6a75ff2c094b04db2c3f29404cfe3c3b99758533fcf0f486c474','[\"*\"]','2026-10-15 00:30:43',NULL,'2026-10-15 00:30:28','2026-10-15 00:30:43'),(117,'App\\Models\\User',50,'web','769133fd8e186cc2951522ed1aabd76e329c8951a058070e3cc12a6d6fd1e0fc','[\"*\"]','2026-10-15 03:33:31',NULL,'2026-10-15 03:33:19','2026-10-15 03:33:31'),(119,'App\\Models\\User',50,'web','bbe6c5117418b5939ae2ebfba3c65a30c91199302f14fb25d2f149939904f4ee','[\"*\"]','2026-10-15 03:34:53',NULL,'2026-10-15 03:34:05','2026-10-15 03:34:53'),(120,'App\\Models\\User',50,'web','29e7e2730b36d5609c4b9347515124fa3d4e7a3aedce92952157a618a6743f51','[\"*\"]','2026-10-15 03:36:28',NULL,'2026-10-15 03:35:35','2026-10-15 03:36:28'),(128,'App\\Models\\User',1,'web','7c5fe2dfd6e661d74193ee25dd2d7005f1730a55aa071e8e52da1f22c19bc71f','[\"*\"]','2026-10-26 05:04:20',NULL,'2026-10-22 23:44:58','2026-10-26 05:04:20'),(129,'App\\Models\\User',1,'web','f88c568ce3197e7c17864dc525edcdb67d8b313e2c0b1a3695bef7517d75dca5','[\"*\"]','2026-10-23 09:06:31',NULL,'2026-10-23 00:54:40','2026-10-23 09:06:31');
/*!40000 ALTER TABLE `personal_access_tokens` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `privileged_account_invitations`
--

DROP TABLE IF EXISTS `privileged_account_invitations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `privileged_account_invitations` (
  `user_id` bigint unsigned NOT NULL,
  `created_by` bigint unsigned NOT NULL,
  `generation` char(36) COLLATE utf8mb4_unicode_ci NOT NULL,
  `delivery_id` char(36) COLLATE utf8mb4_unicode_ci NOT NULL,
  `temporary_password_hash` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `code_hash` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `attempts` tinyint unsigned NOT NULL DEFAULT '0',
  `expires_at` timestamp NOT NULL,
  `code_expires_at` timestamp NULL DEFAULT NULL,
  `sent_at` timestamp NULL DEFAULT NULL,
  `delivered_at` timestamp NULL DEFAULT NULL,
  `delivery_failed_at` timestamp NULL DEFAULT NULL,
  `email_activated_at` timestamp NULL DEFAULT NULL,
  `completed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`user_id`),
  KEY `privileged_account_invitations_created_by_foreign` (`created_by`),
  CONSTRAINT `privileged_account_invitations_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`user_id`),
  CONSTRAINT `privileged_account_invitations_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `privileged_account_invitations`
--

LOCK TABLES `privileged_account_invitations` WRITE;
/*!40000 ALTER TABLE `privileged_account_invitations` DISABLE KEYS */;
/*!40000 ALTER TABLE `privileged_account_invitations` ENABLE KEYS */;
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
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_unicode_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER `pass_history_qr_passes_insert` AFTER INSERT ON `qr_passes` FOR EACH ROW BEGIN INSERT INTO record_history (record_type, record_id, operation, actor_user_id, actor_label, source, ip_address, before_values, after_values, recorded_at)
                    VALUES ('qr_passes', CONCAT_WS(':', NEW.`qr_pass_id`), 'created', @pass_history_actor, LEFT(@pass_history_label, 100), LEFT(@pass_history_source, 150), LEFT(@pass_history_ip, 45), NULL, JSON_OBJECT('qr_pass_id', NEW.`qr_pass_id`, 'pass_type', NEW.`pass_type`, 'request_id', NEW.`request_id`, 'visitor_id', NEW.`visitor_id`, 'user_id', NEW.`user_id`, 'vehicle_id', NEW.`vehicle_id`, 'valid_from', NEW.`valid_from`, 'valid_until', NEW.`valid_until`, 'max_uses', NEW.`max_uses`, 'use_count', NEW.`use_count`, 'pass_status', NEW.`pass_status`, 'issued_by', NEW.`issued_by`, 'issued_at', NEW.`issued_at`, 'revoked_at', NEW.`revoked_at`, 'revoke_reason', NEW.`revoke_reason`, 'created_at', NEW.`created_at`, 'updated_at', NEW.`updated_at`), CURRENT_TIMESTAMP(6)); END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_unicode_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER `pass_history_qr_passes_update` AFTER UPDATE ON `qr_passes` FOR EACH ROW BEGIN IF NOT (OLD.`qr_pass_id` <=> NEW.`qr_pass_id`) OR NOT (OLD.`pass_type` <=> NEW.`pass_type`) OR NOT (OLD.`request_id` <=> NEW.`request_id`) OR NOT (OLD.`visitor_id` <=> NEW.`visitor_id`) OR NOT (OLD.`user_id` <=> NEW.`user_id`) OR NOT (OLD.`vehicle_id` <=> NEW.`vehicle_id`) OR NOT (OLD.`valid_from` <=> NEW.`valid_from`) OR NOT (OLD.`valid_until` <=> NEW.`valid_until`) OR NOT (OLD.`max_uses` <=> NEW.`max_uses`) OR NOT (OLD.`use_count` <=> NEW.`use_count`) OR NOT (OLD.`pass_status` <=> NEW.`pass_status`) OR NOT (OLD.`issued_by` <=> NEW.`issued_by`) OR NOT (OLD.`issued_at` <=> NEW.`issued_at`) OR NOT (OLD.`revoked_at` <=> NEW.`revoked_at`) OR NOT (OLD.`revoke_reason` <=> NEW.`revoke_reason`) OR NOT (OLD.`created_at` <=> NEW.`created_at`) OR NOT (OLD.`updated_at` <=> NEW.`updated_at`) THEN INSERT INTO record_history (record_type, record_id, operation, actor_user_id, actor_label, source, ip_address, before_values, after_values, recorded_at)
                    VALUES ('qr_passes', CONCAT_WS(':', NEW.`qr_pass_id`), 'updated', @pass_history_actor, LEFT(@pass_history_label, 100), LEFT(@pass_history_source, 150), LEFT(@pass_history_ip, 45), JSON_OBJECT('qr_pass_id', OLD.`qr_pass_id`, 'pass_type', OLD.`pass_type`, 'request_id', OLD.`request_id`, 'visitor_id', OLD.`visitor_id`, 'user_id', OLD.`user_id`, 'vehicle_id', OLD.`vehicle_id`, 'valid_from', OLD.`valid_from`, 'valid_until', OLD.`valid_until`, 'max_uses', OLD.`max_uses`, 'use_count', OLD.`use_count`, 'pass_status', OLD.`pass_status`, 'issued_by', OLD.`issued_by`, 'issued_at', OLD.`issued_at`, 'revoked_at', OLD.`revoked_at`, 'revoke_reason', OLD.`revoke_reason`, 'created_at', OLD.`created_at`, 'updated_at', OLD.`updated_at`), JSON_OBJECT('qr_pass_id', NEW.`qr_pass_id`, 'pass_type', NEW.`pass_type`, 'request_id', NEW.`request_id`, 'visitor_id', NEW.`visitor_id`, 'user_id', NEW.`user_id`, 'vehicle_id', NEW.`vehicle_id`, 'valid_from', NEW.`valid_from`, 'valid_until', NEW.`valid_until`, 'max_uses', NEW.`max_uses`, 'use_count', NEW.`use_count`, 'pass_status', NEW.`pass_status`, 'issued_by', NEW.`issued_by`, 'issued_at', NEW.`issued_at`, 'revoked_at', NEW.`revoked_at`, 'revoke_reason', NEW.`revoke_reason`, 'created_at', NEW.`created_at`, 'updated_at', NEW.`updated_at`), CURRENT_TIMESTAMP(6)); END IF; END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_unicode_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER `pass_history_qr_passes_delete` AFTER DELETE ON `qr_passes` FOR EACH ROW BEGIN INSERT INTO record_history (record_type, record_id, operation, actor_user_id, actor_label, source, ip_address, before_values, after_values, recorded_at)
                    VALUES ('qr_passes', CONCAT_WS(':', OLD.`qr_pass_id`), 'deleted', @pass_history_actor, LEFT(@pass_history_label, 100), LEFT(@pass_history_source, 150), LEFT(@pass_history_ip, 45), JSON_OBJECT('qr_pass_id', OLD.`qr_pass_id`, 'pass_type', OLD.`pass_type`, 'request_id', OLD.`request_id`, 'visitor_id', OLD.`visitor_id`, 'user_id', OLD.`user_id`, 'vehicle_id', OLD.`vehicle_id`, 'valid_from', OLD.`valid_from`, 'valid_until', OLD.`valid_until`, 'max_uses', OLD.`max_uses`, 'use_count', OLD.`use_count`, 'pass_status', OLD.`pass_status`, 'issued_by', OLD.`issued_by`, 'issued_at', OLD.`issued_at`, 'revoked_at', OLD.`revoked_at`, 'revoke_reason', OLD.`revoke_reason`, 'created_at', OLD.`created_at`, 'updated_at', OLD.`updated_at`), NULL, CURRENT_TIMESTAMP(6)); END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;

--
-- Table structure for table `record_history`
--

DROP TABLE IF EXISTS `record_history`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `record_history` (
  `revision_id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `record_type` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  `record_id` varchar(128) COLLATE utf8mb4_unicode_ci NOT NULL,
  `operation` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `actor_user_id` bigint unsigned DEFAULT NULL,
  `actor_label` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `source` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `before_values` json DEFAULT NULL,
  `after_values` json DEFAULT NULL,
  `recorded_at` datetime(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
  PRIMARY KEY (`revision_id`),
  KEY `record_history_record_type_record_id_revision_id_index` (`record_type`,`record_id`,`revision_id`),
  KEY `record_history_recorded_at_index` (`recorded_at`)
) ENGINE=InnoDB AUTO_INCREMENT=767 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `record_history`
--

LOCK TABLES `record_history` WRITE;
/*!40000 ALTER TABLE `record_history` DISABLE KEYS */;
INSERT INTO `record_history` VALUES (1,'users','1','baseline',NULL,NULL,'history installation',NULL,NULL,'{\"email\": \"security.demo@pass.local\", \"user_id\": 1, \"user_code\": \"SEC-DEMO-001\", \"user_type\": \"security\", \"created_at\": \"2026-10-13 07:17:03.000000\", \"deleted_at\": null, \"updated_at\": \"2026-10-15 17:12:56.000000\", \"last_login_at\": \"2026-10-15 17:12:56.000000\", \"user_code_key\": \"SECDEMO001\", \"account_status\": \"active\", \"email_verified_at\": \"2026-10-13 07:17:03.000000\"}','2026-10-23 15:29:56.344614'),(2,'users','32','baseline',NULL,NULL,'history installation',NULL,NULL,'{\"email\": \"sample-stu-001@sample.pass.test\", \"user_id\": 32, \"user_code\": \"SAMPLE-STU-001\", \"user_type\": \"student\", \"created_at\": \"2026-10-14 01:16:32.000000\", \"deleted_at\": null, \"updated_at\": \"2026-10-14 05:11:57.000000\", \"last_login_at\": \"2026-10-14 04:10:14.000000\", \"user_code_key\": \"SAMPLESTU001\", \"account_status\": \"active\", \"email_verified_at\": \"2026-10-14 05:11:57.000000\"}','2026-10-23 15:29:56.344614'),(3,'users','33','baseline',NULL,NULL,'history installation',NULL,NULL,'{\"email\": \"sample-stu-002@sample.pass.test\", \"user_id\": 33, \"user_code\": \"SAMPLE-STU-002\", \"user_type\": \"student\", \"created_at\": \"2026-10-14 01:16:32.000000\", \"deleted_at\": null, \"updated_at\": \"2026-10-14 05:11:57.000000\", \"last_login_at\": \"2026-10-14 05:10:55.000000\", \"user_code_key\": \"SAMPLESTU002\", \"account_status\": \"active\", \"email_verified_at\": \"2026-10-14 05:11:57.000000\"}','2026-10-23 15:29:56.344614'),(4,'users','34','baseline',NULL,NULL,'history installation',NULL,NULL,'{\"email\": \"sample-fac-001@sample.pass.test\", \"user_id\": 34, \"user_code\": \"SAMPLE-FAC-001\", \"user_type\": \"faculty\", \"created_at\": \"2026-10-14 01:16:33.000000\", \"deleted_at\": null, \"updated_at\": \"2026-10-14 05:11:58.000000\", \"last_login_at\": \"2026-10-14 04:10:15.000000\", \"user_code_key\": \"SAMPLEFAC001\", \"account_status\": \"active\", \"email_verified_at\": \"2026-10-14 05:11:58.000000\"}','2026-10-23 15:29:56.344614'),(5,'users','35','baseline',NULL,NULL,'history installation',NULL,NULL,'{\"email\": \"sample-fac-002@sample.pass.test\", \"user_id\": 35, \"user_code\": \"SAMPLE-FAC-002\", \"user_type\": \"faculty\", \"created_at\": \"2026-10-14 01:16:33.000000\", \"deleted_at\": null, \"updated_at\": \"2026-10-14 05:11:58.000000\", \"last_login_at\": \"2026-10-14 04:10:15.000000\", \"user_code_key\": \"SAMPLEFAC002\", \"account_status\": \"active\", \"email_verified_at\": \"2026-10-14 05:11:58.000000\"}','2026-10-23 15:29:56.344614'),(6,'users','36','baseline',NULL,NULL,'history installation',NULL,NULL,'{\"email\": \"sample-emp-001@sample.pass.test\", \"user_id\": 36, \"user_code\": \"SAMPLE-EMP-001\", \"user_type\": \"employee\", \"created_at\": \"2026-10-14 01:16:34.000000\", \"deleted_at\": null, \"updated_at\": \"2026-10-14 05:11:58.000000\", \"last_login_at\": \"2026-10-14 05:10:31.000000\", \"user_code_key\": \"SAMPLEEMP001\", \"account_status\": \"active\", \"email_verified_at\": \"2026-10-14 05:11:58.000000\"}','2026-10-23 15:29:56.344614'),(7,'users','46','baseline',NULL,NULL,'history installation',NULL,NULL,'{\"email\": \"admin.demo@pass.local\", \"user_id\": 46, \"user_code\": \"ADM-DEMO-001\", \"user_type\": \"admin\", \"created_at\": \"2026-10-15 08:15:47.000000\", \"deleted_at\": null, \"updated_at\": \"2026-10-15 16:38:57.000000\", \"last_login_at\": \"2026-10-15 16:38:57.000000\", \"user_code_key\": \"ADMDEMO001\", \"account_status\": \"active\", \"email_verified_at\": \"2026-10-15 08:15:47.000000\"}','2026-10-23 15:29:56.344614'),(8,'users','47','baseline',NULL,NULL,'history installation',NULL,NULL,'{\"email\": \"guest.demo@pass.test\", \"user_id\": 47, \"user_code\": \"GST-DEMO-001\", \"user_type\": \"other\", \"created_at\": \"2026-10-15 08:15:48.000000\", \"deleted_at\": null, \"updated_at\": \"2026-10-15 08:15:48.000000\", \"last_login_at\": null, \"user_code_key\": \"GSTDEMO001\", \"account_status\": \"active\", \"email_verified_at\": \"2026-10-15 08:15:48.000000\"}','2026-10-23 15:29:56.344614'),(9,'users','48','baseline',NULL,NULL,'history installation',NULL,NULL,'{\"email\": \"rosa.villanueva.test@example.com\", \"user_id\": 48, \"user_code\": \"GST-M9SC2UDB\", \"user_type\": \"other\", \"created_at\": \"2026-10-15 08:24:20.000000\", \"deleted_at\": null, \"updated_at\": \"2026-10-15 08:30:28.000000\", \"last_login_at\": \"2026-10-15 08:30:28.000000\", \"user_code_key\": \"GSTM9SC2UDB\", \"account_status\": \"active\", \"email_verified_at\": null}','2026-10-23 15:29:56.344614'),(10,'users','49','baseline',NULL,NULL,'history installation',NULL,NULL,'{\"email\": \"guest@pass.com\", \"user_id\": 49, \"user_code\": \"GST-20UF3BER\", \"user_type\": \"other\", \"created_at\": \"2026-10-15 09:38:19.000000\", \"deleted_at\": null, \"updated_at\": \"2026-10-15 09:39:01.000000\", \"last_login_at\": \"2026-10-15 09:39:01.000000\", \"user_code_key\": \"GST20UF3BER\", \"account_status\": \"active\", \"email_verified_at\": null}','2026-10-23 15:29:56.344614'),(11,'users','50','baseline',NULL,NULL,'history installation',NULL,NULL,'{\"email\": \"lara.gomez.test@example.com\", \"user_id\": 50, \"user_code\": \"GST-BW3KZLQ4\", \"user_type\": \"other\", \"created_at\": \"2026-10-15 11:33:13.000000\", \"deleted_at\": null, \"updated_at\": \"2026-10-15 11:35:35.000000\", \"last_login_at\": \"2026-10-15 11:35:35.000000\", \"user_code_key\": \"GSTBW3KZLQ4\", \"account_status\": \"active\", \"email_verified_at\": null}','2026-10-23 15:29:56.344614'),(16,'user_profiles','1','baseline',NULL,NULL,'history installation',NULL,NULL,'{\"sex\": null, \"city\": null, \"suffix\": null, \"user_id\": 1, \"province\": null, \"last_name\": \"Parking Staff\", \"birth_date\": null, \"created_at\": \"2026-10-13 07:17:03.000000\", \"first_name\": \"Demo\", \"photo_path\": null, \"profile_id\": 1, \"updated_at\": \"2026-10-13 07:17:03.000000\", \"year_level\": null, \"middle_name\": null, \"address_line\": null, \"department_id\": 9, \"contact_number\": null, \"registration_unit\": null, \"program_or_position\": \"Parking Staff\", \"registration_category\": null, \"emergency_contact_name\": null, \"school_id_check_result\": null, \"school_id_check_status\": null, \"school_id_document_path\": null, \"emergency_contact_number\": null}','2026-10-23 15:29:56.405901'),(17,'user_profiles','32','baseline',NULL,NULL,'history installation',NULL,NULL,'{\"sex\": \"female\", \"city\": null, \"suffix\": null, \"user_id\": 32, \"province\": null, \"last_name\": \"Villamor\", \"birth_date\": \"2004-03-15\", \"created_at\": \"2026-10-14 05:11:57.000000\", \"first_name\": \"Andrea\", \"photo_path\": null, \"profile_id\": 32, \"updated_at\": \"2026-10-14 05:11:57.000000\", \"year_level\": \"3rd Year\", \"middle_name\": null, \"address_line\": \"SAMPLE TEST DATA\", \"department_id\": 1, \"contact_number\": \"0900-000--001\", \"registration_unit\": null, \"program_or_position\": \"BS Computer Engineering\", \"registration_category\": null, \"emergency_contact_name\": \"SAMPLE TEST DATA\", \"school_id_check_result\": null, \"school_id_check_status\": null, \"school_id_document_path\": null, \"emergency_contact_number\": null}','2026-10-23 15:29:56.405901'),(18,'user_profiles','33','baseline',NULL,NULL,'history installation',NULL,NULL,'{\"sex\": \"male\", \"city\": null, \"suffix\": null, \"user_id\": 33, \"province\": null, \"last_name\": \"Castillo\", \"birth_date\": \"2005-07-02\", \"created_at\": \"2026-10-14 05:11:57.000000\", \"first_name\": \"Bryan\", \"photo_path\": null, \"profile_id\": 33, \"updated_at\": \"2026-10-14 05:11:57.000000\", \"year_level\": \"2nd Year\", \"middle_name\": null, \"address_line\": \"SAMPLE TEST DATA\", \"department_id\": 2, \"contact_number\": \"0900-000--002\", \"registration_unit\": null, \"program_or_position\": \"BS Biology\", \"registration_category\": null, \"emergency_contact_name\": \"SAMPLE TEST DATA\", \"school_id_check_result\": null, \"school_id_check_status\": null, \"school_id_document_path\": null, \"emergency_contact_number\": null}','2026-10-23 15:29:56.405901'),(19,'user_profiles','34','baseline',NULL,NULL,'history installation',NULL,NULL,'{\"sex\": \"female\", \"city\": null, \"suffix\": null, \"user_id\": 34, \"province\": null, \"last_name\": \"Navarro\", \"birth_date\": \"1986-11-20\", \"created_at\": \"2026-10-14 05:11:58.000000\", \"first_name\": \"Celia\", \"photo_path\": null, \"profile_id\": 34, \"updated_at\": \"2026-10-14 05:11:58.000000\", \"year_level\": null, \"middle_name\": null, \"address_line\": \"SAMPLE TEST DATA\", \"department_id\": 3, \"contact_number\": \"0900-000--001\", \"registration_unit\": null, \"program_or_position\": \"Assistant Professor\", \"registration_category\": null, \"emergency_contact_name\": \"SAMPLE TEST DATA\", \"school_id_check_result\": null, \"school_id_check_status\": null, \"school_id_document_path\": null, \"emergency_contact_number\": null}','2026-10-23 15:29:56.405901'),(20,'user_profiles','35','baseline',NULL,NULL,'history installation',NULL,NULL,'{\"sex\": \"male\", \"city\": null, \"suffix\": null, \"user_id\": 35, \"province\": null, \"last_name\": \"Aquino\", \"birth_date\": \"1990-01-08\", \"created_at\": \"2026-10-14 05:11:58.000000\", \"first_name\": \"Dennis\", \"photo_path\": null, \"profile_id\": 35, \"updated_at\": \"2026-10-14 05:11:58.000000\", \"year_level\": null, \"middle_name\": null, \"address_line\": \"SAMPLE TEST DATA\", \"department_id\": 4, \"contact_number\": \"0900-000--002\", \"registration_unit\": null, \"program_or_position\": \"Instructor\", \"registration_category\": null, \"emergency_contact_name\": \"SAMPLE TEST DATA\", \"school_id_check_result\": null, \"school_id_check_status\": null, \"school_id_document_path\": null, \"emergency_contact_number\": null}','2026-10-23 15:29:56.405901'),(21,'user_profiles','36','baseline',NULL,NULL,'history installation',NULL,NULL,'{\"sex\": \"female\", \"city\": null, \"suffix\": null, \"user_id\": 36, \"province\": null, \"last_name\": \"Bautista\", \"birth_date\": \"1993-05-27\", \"created_at\": \"2026-10-14 05:11:58.000000\", \"first_name\": \"Elena\", \"photo_path\": null, \"profile_id\": 36, \"updated_at\": \"2026-10-14 05:11:58.000000\", \"year_level\": null, \"middle_name\": null, \"address_line\": \"SAMPLE TEST DATA\", \"department_id\": 7, \"contact_number\": \"0900-000--001\", \"registration_unit\": null, \"program_or_position\": \"HR Assistant\", \"registration_category\": null, \"emergency_contact_name\": \"SAMPLE TEST DATA\", \"school_id_check_result\": null, \"school_id_check_status\": null, \"school_id_document_path\": null, \"emergency_contact_number\": null}','2026-10-23 15:29:56.405901'),(22,'user_profiles','46','baseline',NULL,NULL,'history installation',NULL,NULL,'{\"sex\": null, \"city\": null, \"suffix\": null, \"user_id\": 46, \"province\": null, \"last_name\": \"Administrator\", \"birth_date\": null, \"created_at\": \"2026-10-15 08:15:47.000000\", \"first_name\": \"Demo\", \"photo_path\": null, \"profile_id\": 46, \"updated_at\": \"2026-10-15 08:15:47.000000\", \"year_level\": null, \"middle_name\": null, \"address_line\": null, \"department_id\": null, \"contact_number\": null, \"registration_unit\": null, \"program_or_position\": \"PASS Administrator\", \"registration_category\": null, \"emergency_contact_name\": null, \"school_id_check_result\": null, \"school_id_check_status\": null, \"school_id_document_path\": null, \"emergency_contact_number\": null}','2026-10-23 15:29:56.405901'),(23,'user_profiles','47','baseline',NULL,NULL,'history installation',NULL,NULL,'{\"sex\": null, \"city\": null, \"suffix\": null, \"user_id\": 47, \"province\": null, \"last_name\": \"Santos\", \"birth_date\": null, \"created_at\": \"2026-10-15 08:15:48.000000\", \"first_name\": \"Maria\", \"photo_path\": null, \"profile_id\": 47, \"updated_at\": \"2026-10-15 08:15:48.000000\", \"year_level\": null, \"middle_name\": null, \"address_line\": null, \"department_id\": null, \"contact_number\": \"09171230001\", \"registration_unit\": null, \"program_or_position\": \"Guest / Visitor\", \"registration_category\": null, \"emergency_contact_name\": null, \"school_id_check_result\": null, \"school_id_check_status\": null, \"school_id_document_path\": null, \"emergency_contact_number\": null}','2026-10-23 15:29:56.405901'),(24,'user_profiles','48','baseline',NULL,NULL,'history installation',NULL,NULL,'{\"sex\": null, \"city\": null, \"suffix\": null, \"user_id\": 48, \"province\": null, \"last_name\": \"Villanueva\", \"birth_date\": null, \"created_at\": \"2026-10-15 08:24:20.000000\", \"first_name\": \"Rosa\", \"photo_path\": null, \"profile_id\": 48, \"updated_at\": \"2026-10-15 08:24:20.000000\", \"year_level\": null, \"middle_name\": null, \"address_line\": null, \"department_id\": null, \"contact_number\": \"09187654321\", \"registration_unit\": null, \"program_or_position\": \"Guest / Visitor\", \"registration_category\": null, \"emergency_contact_name\": null, \"school_id_check_result\": null, \"school_id_check_status\": null, \"school_id_document_path\": null, \"emergency_contact_number\": null}','2026-10-23 15:29:56.405901'),(25,'user_profiles','49','baseline',NULL,NULL,'history installation',NULL,NULL,'{\"sex\": null, \"city\": null, \"suffix\": null, \"user_id\": 49, \"province\": null, \"last_name\": \"visitor\", \"birth_date\": null, \"created_at\": \"2026-10-15 09:38:19.000000\", \"first_name\": \"guestito\", \"photo_path\": null, \"profile_id\": 49, \"updated_at\": \"2026-10-15 09:38:19.000000\", \"year_level\": null, \"middle_name\": null, \"address_line\": null, \"department_id\": null, \"contact_number\": \"09128012356\", \"registration_unit\": null, \"program_or_position\": \"Guest / Visitor\", \"registration_category\": null, \"emergency_contact_name\": null, \"school_id_check_result\": null, \"school_id_check_status\": null, \"school_id_document_path\": null, \"emergency_contact_number\": null}','2026-10-23 15:29:56.405901'),(26,'user_profiles','50','baseline',NULL,NULL,'history installation',NULL,NULL,'{\"sex\": null, \"city\": null, \"suffix\": null, \"user_id\": 50, \"province\": null, \"last_name\": \"Gomez\", \"birth_date\": null, \"created_at\": \"2026-10-15 11:33:13.000000\", \"first_name\": \"Lara\", \"photo_path\": null, \"profile_id\": 50, \"updated_at\": \"2026-10-15 11:33:13.000000\", \"year_level\": null, \"middle_name\": null, \"address_line\": null, \"department_id\": null, \"contact_number\": \"09175550144\", \"registration_unit\": null, \"program_or_position\": \"Guest / Visitor\", \"registration_category\": null, \"emergency_contact_name\": null, \"school_id_check_result\": null, \"school_id_check_status\": null, \"school_id_document_path\": null, \"emergency_contact_number\": null}','2026-10-23 15:29:56.405901'),(31,'user_roles','1:3','baseline',NULL,NULL,'history installation',NULL,NULL,'{\"role_id\": 3, \"user_id\": 1, \"assigned_at\": \"2026-10-13 07:17:03.000000\", \"assigned_by\": null}','2026-10-23 15:29:56.466208'),(32,'user_roles','32:4','baseline',NULL,NULL,'history installation',NULL,NULL,'{\"role_id\": 4, \"user_id\": 32, \"assigned_at\": \"2026-10-14 05:11:57.000000\", \"assigned_by\": null}','2026-10-23 15:29:56.466208'),(33,'user_roles','33:4','baseline',NULL,NULL,'history installation',NULL,NULL,'{\"role_id\": 4, \"user_id\": 33, \"assigned_at\": \"2026-10-14 05:11:57.000000\", \"assigned_by\": null}','2026-10-23 15:29:56.466208'),(34,'user_roles','34:5','baseline',NULL,NULL,'history installation',NULL,NULL,'{\"role_id\": 5, \"user_id\": 34, \"assigned_at\": \"2026-10-14 05:11:58.000000\", \"assigned_by\": null}','2026-10-23 15:29:56.466208'),(35,'user_roles','35:5','baseline',NULL,NULL,'history installation',NULL,NULL,'{\"role_id\": 5, \"user_id\": 35, \"assigned_at\": \"2026-10-14 05:11:58.000000\", \"assigned_by\": null}','2026-10-23 15:29:56.466208'),(36,'user_roles','36:6','baseline',NULL,NULL,'history installation',NULL,NULL,'{\"role_id\": 6, \"user_id\": 36, \"assigned_at\": \"2026-10-14 05:11:58.000000\", \"assigned_by\": null}','2026-10-23 15:29:56.466208'),(37,'user_roles','46:1','baseline',NULL,NULL,'history installation',NULL,NULL,'{\"role_id\": 1, \"user_id\": 46, \"assigned_at\": \"2026-10-15 08:15:47.000000\", \"assigned_by\": null}','2026-10-23 15:29:56.466208'),(38,'user_roles','47:7','baseline',NULL,NULL,'history installation',NULL,NULL,'{\"role_id\": 7, \"user_id\": 47, \"assigned_at\": \"2026-10-15 08:15:48.000000\", \"assigned_by\": null}','2026-10-23 15:29:56.466208'),(39,'user_roles','48:7','baseline',NULL,NULL,'history installation',NULL,NULL,'{\"role_id\": 7, \"user_id\": 48, \"assigned_at\": \"2026-10-15 08:24:20.000000\", \"assigned_by\": null}','2026-10-23 15:29:56.466208'),(40,'user_roles','49:7','baseline',NULL,NULL,'history installation',NULL,NULL,'{\"role_id\": 7, \"user_id\": 49, \"assigned_at\": \"2026-10-15 09:38:19.000000\", \"assigned_by\": null}','2026-10-23 15:29:56.466208'),(41,'user_roles','50:7','baseline',NULL,NULL,'history installation',NULL,NULL,'{\"role_id\": 7, \"user_id\": 50, \"assigned_at\": \"2026-10-15 11:33:13.000000\", \"assigned_by\": null}','2026-10-23 15:29:56.466208'),(46,'roles','1','baseline',NULL,NULL,'history installation',NULL,NULL,'{\"role_id\": 1, \"is_active\": 1, \"is_system\": 1, \"role_code\": \"admin\", \"role_name\": \"System Administrator\", \"description\": \"Overall system administrator\"}','2026-10-23 15:29:56.510763'),(47,'roles','2','baseline',NULL,NULL,'history installation',NULL,NULL,'{\"role_id\": 2, \"is_active\": 1, \"is_system\": 1, \"role_code\": \"parking_admin\", \"role_name\": \"Parking Administrator\", \"description\": \"Manages parking operations\"}','2026-10-23 15:29:56.510763'),(48,'roles','3','baseline',NULL,NULL,'history installation',NULL,NULL,'{\"role_id\": 3, \"is_active\": 1, \"is_system\": 1, \"role_code\": \"security\", \"role_name\": \"Security / Parking Personnel\", \"description\": \"Gate and parking staff\"}','2026-10-23 15:29:56.510763'),(49,'roles','4','baseline',NULL,NULL,'history installation',NULL,NULL,'{\"role_id\": 4, \"is_active\": 1, \"is_system\": 1, \"role_code\": \"student\", \"role_name\": \"Student\", \"description\": \"Registered student user\"}','2026-10-23 15:29:56.510763'),(50,'roles','5','baseline',NULL,NULL,'history installation',NULL,NULL,'{\"role_id\": 5, \"is_active\": 1, \"is_system\": 1, \"role_code\": \"faculty\", \"role_name\": \"Faculty\", \"description\": \"Registered faculty user\"}','2026-10-23 15:29:56.510763'),(51,'roles','6','baseline',NULL,NULL,'history installation',NULL,NULL,'{\"role_id\": 6, \"is_active\": 1, \"is_system\": 1, \"role_code\": \"employee\", \"role_name\": \"Employee\", \"description\": \"Registered employee user\"}','2026-10-23 15:29:56.510763'),(52,'roles','7','baseline',NULL,NULL,'history installation',NULL,NULL,'{\"role_id\": 7, \"is_active\": 1, \"is_system\": 1, \"role_code\": \"guest\", \"role_name\": \"Guest / Visitor\", \"description\": \"Visitor account: requests visitor parking and uses a Visitor QR pass\"}','2026-10-23 15:29:56.510763'),(53,'drivers','44','baseline',NULL,NULL,'history installation',NULL,NULL,'{\"user_id\": 32, \"driver_id\": 44, \"last_name\": \"Villamor\", \"created_at\": \"2026-08-30 00:00:00.000000\", \"first_name\": \"Andrea\", \"updated_at\": \"2026-08-30 00:00:00.000000\", \"middle_name\": null, \"driver_status\": \"active\", \"contact_number\": \"0900-000--001\"}','2026-10-23 15:29:56.635126'),(54,'drivers','45','baseline',NULL,NULL,'history installation',NULL,NULL,'{\"user_id\": 33, \"driver_id\": 45, \"last_name\": \"Castillo\", \"created_at\": \"2026-08-30 00:00:00.000000\", \"first_name\": \"Bryan\", \"updated_at\": \"2026-08-30 00:00:00.000000\", \"middle_name\": null, \"driver_status\": \"active\", \"contact_number\": \"0900-000--002\"}','2026-10-23 15:29:56.635126'),(55,'drivers','46','baseline',NULL,NULL,'history installation',NULL,NULL,'{\"user_id\": 34, \"driver_id\": 46, \"last_name\": \"Navarro\", \"created_at\": \"2026-08-30 00:00:00.000000\", \"first_name\": \"Celia\", \"updated_at\": \"2026-08-30 00:00:00.000000\", \"middle_name\": null, \"driver_status\": \"active\", \"contact_number\": \"0900-000--001\"}','2026-10-23 15:29:56.635126'),(56,'drivers','47','baseline',NULL,NULL,'history installation',NULL,NULL,'{\"user_id\": 35, \"driver_id\": 47, \"last_name\": \"Aquino\", \"created_at\": \"2026-08-30 00:00:00.000000\", \"first_name\": \"Dennis\", \"updated_at\": \"2026-08-30 00:00:00.000000\", \"middle_name\": null, \"driver_status\": \"active\", \"contact_number\": \"0900-000--002\"}','2026-10-23 15:29:56.635126'),(57,'drivers','48','baseline',NULL,NULL,'history installation',NULL,NULL,'{\"user_id\": 36, \"driver_id\": 48, \"last_name\": \"Bautista\", \"created_at\": \"2026-08-30 00:00:00.000000\", \"first_name\": \"Elena\", \"updated_at\": \"2026-08-30 00:00:00.000000\", \"middle_name\": null, \"driver_status\": \"active\", \"contact_number\": \"0900-000--001\"}','2026-10-23 15:29:56.635126'),(60,'driver_licenses','44','baseline',NULL,NULL,'history installation',NULL,NULL,'{\"driver_id\": 44, \"checked_at\": \"2026-10-14 05:12:00.000000\", \"created_at\": \"2026-10-14 01:16:34.000000\", \"issue_date\": null, \"license_id\": 44, \"ocr_result\": {\"text\": \"SAMPLE DRIVER LICENSE - TEST DATA ONLY\\nNOT A GOVERNMENT-ISSUED DOCUMENT. FOR PASS SYSTEM TESTING.\\nLast Name, First Name\\nVILLAMOR, ANDREA\\nLicense No.\\nZ01-26-000101\\nExpiration Date\\n2030/03/15\\nDate of Birth\\n2004/03/15\\nLicense Type\\nNON-PROFESSIONAL\\nSAMPLE * TEST DATAONLY * NOT VALID FOR ANY PURPOSE\", \"notes\": [\"Preliminary validation only. Authenticity has not been confirmed: no official LTO verification service is integrated, so PASS staff must review the document.\"], \"width\": 1600, \"checks\": {\"name\": {\"detail\": \"The registered first and last name appear on the document.\", \"result\": \"pass\"}, \"expiry_date\": {\"detail\": \"The expiration date on the document matches the submitted date.\", \"result\": \"pass\"}, \"document_type\": {\"detail\": \"Matched 3 LTO license marker group(s).\", \"result\": \"pass\"}, \"image_quality\": {\"detail\": \"Resolution 1600x1000, sharpness 1463.9.\", \"result\": \"pass\"}, \"license_number\": {\"detail\": \"The license number on the document matches the submitted number.\", \"result\": \"pass\"}, \"text_extraction\": {\"detail\": \"236 readable characters, mean confidence 95%.\", \"result\": \"pass\"}}, \"engine\": \"tesseract\", \"height\": 1000, \"reasons\": [], \"decision\": \"pending_review\", \"extracted\": {\"dates\": [\"2030-03-15\", \"2004-03-15\"], \"expiry_date\": \"2030-03-15\", \"license_numbers\": [\"Z01-26-000101\"], \"name_tokens_found\": [\"ANDREA\", \"VILLAMOR\"]}, \"sharpness\": 1463.9, \"confidence\": 95, \"preliminary\": true, \"document_score\": 3, \"official_verification\": \"unavailable\"}, \"updated_at\": \"2026-10-14 05:12:00.000000\", \"expiry_date\": \"2030-03-15\", \"reviewed_at\": \"2026-09-25 05:11:59.000000\", \"reviewed_by\": 1, \"current_flag\": 1, \"license_type\": \"non_professional\", \"review_notes\": null, \"submitted_at\": \"2026-09-24 05:11:59.000000\", \"review_status\": \"pending\", \"issuing_agency\": \"SAMPLE TEST DATA\", \"license_number\": \"Z01-26-000101\", \"license_status\": \"valid\", \"restriction_codes\": null, \"license_image_path\": \"driver-licenses/sample/sample-stu-001.png\", \"original_file_name\": \"sample-stu-001-sample-license.png\", \"verification_reason\": null, \"verification_source\": \"staff\", \"verification_status\": \"verified\"}','2026-10-23 15:29:56.686125'),(61,'driver_licenses','45','baseline',NULL,NULL,'history installation',NULL,NULL,'{\"driver_id\": 45, \"checked_at\": \"2026-10-14 05:12:01.000000\", \"created_at\": \"2026-10-14 01:16:37.000000\", \"issue_date\": null, \"license_id\": 45, \"ocr_result\": {\"text\": \"SAMPLE DRIVER LICENSE - TEST DATA ONLY\\nNOT A GOVERNMENT-ISSUED DOCUMENT. FOR PASS SYSTEM TESTING.\\nLast Name, First Name\\nCASTILLO, BRYAN\\nLicense No.\\nZ02-26-000202\\nExpiration Date\\n2027/07/02\\nDate of Birth\\n2005/07/02\\nLicense Type\\nSTUDENT-PERMIT\\nSAMPLE * TEST DATAONLY * NOT VALID FOR ANY PURPOSE\", \"notes\": [\"Preliminary validation only. Authenticity has not been confirmed: no official LTO verification service is integrated, so PASS staff must review the document.\"], \"width\": 1600, \"checks\": {\"name\": {\"detail\": \"The registered first and last name appear on the document.\", \"result\": \"pass\"}, \"expiry_date\": {\"detail\": \"The expiration date on the document matches the submitted date.\", \"result\": \"pass\"}, \"document_type\": {\"detail\": \"Matched 3 LTO license marker group(s).\", \"result\": \"pass\"}, \"image_quality\": {\"detail\": \"Resolution 1600x1000, sharpness 1433.6.\", \"result\": \"pass\"}, \"license_number\": {\"detail\": \"The license number on the document matches the submitted number.\", \"result\": \"pass\"}, \"text_extraction\": {\"detail\": \"233 readable characters, mean confidence 95%.\", \"result\": \"pass\"}}, \"engine\": \"tesseract\", \"height\": 1000, \"reasons\": [], \"decision\": \"pending_review\", \"extracted\": {\"dates\": [\"2027-07-02\", \"2005-07-02\"], \"expiry_date\": \"2027-07-02\", \"license_numbers\": [\"Z02-26-000202\"], \"name_tokens_found\": [\"BRYAN\", \"CASTILLO\"]}, \"sharpness\": 1433.6, \"confidence\": 95, \"preliminary\": true, \"document_score\": 3, \"official_verification\": \"unavailable\"}, \"updated_at\": \"2026-10-14 05:12:01.000000\", \"expiry_date\": \"2027-07-02\", \"reviewed_at\": null, \"reviewed_by\": null, \"current_flag\": 1, \"license_type\": \"student_permit\", \"review_notes\": null, \"submitted_at\": \"2026-10-13 05:12:00.000000\", \"review_status\": \"pending\", \"issuing_agency\": \"SAMPLE TEST DATA\", \"license_number\": \"Z02-26-000202\", \"license_status\": \"valid\", \"restriction_codes\": null, \"license_image_path\": \"driver-licenses/sample/sample-stu-002.png\", \"original_file_name\": \"sample-stu-002-sample-license.png\", \"verification_reason\": null, \"verification_source\": \"automatic\", \"verification_status\": \"pending_review\"}','2026-10-23 15:29:56.686125'),(62,'driver_licenses','46','baseline',NULL,NULL,'history installation',NULL,NULL,'{\"driver_id\": 46, \"checked_at\": \"2026-10-14 05:12:02.000000\", \"created_at\": \"2026-10-14 01:16:39.000000\", \"issue_date\": null, \"license_id\": 46, \"ocr_result\": {\"text\": \"SAMPLE DRIVER LICENSE - TEST DATA ONLY\\nNOT A GOVERNMENT-ISSUED DOCUMENT. FOR PASS SYSTEM TESTING.\\nLast Name, First Name\\nNAVARRO, CELIA\\nLicense No.\\nZ03-26-000330\\nExpiration Date\\n2031/11/20\\nDate of Birth\\n1986/11/20\\nLicense Type\\nPROFESSIONAL\\nSAMPLE * TEST DATAONLY * NOT VALID FOR ANY PURPOSE\", \"notes\": [\"Preliminary validation only. Authenticity has not been confirmed: no official LTO verification service is integrated, so PASS staff must review the document.\"], \"width\": 1600, \"checks\": {\"name\": {\"detail\": \"The registered first and last name appear on the document.\", \"result\": \"pass\"}, \"expiry_date\": {\"detail\": \"The expiration date on the document matches the submitted date.\", \"result\": \"pass\"}, \"document_type\": {\"detail\": \"Matched 3 LTO license marker group(s).\", \"result\": \"pass\"}, \"image_quality\": {\"detail\": \"Resolution 1600x1000, sharpness 1403.1.\", \"result\": \"pass\"}, \"license_number\": {\"detail\": \"Document shows Z03-26-000330.\", \"result\": \"fail\"}, \"text_extraction\": {\"detail\": \"231 readable characters, mean confidence 95.2%.\", \"result\": \"pass\"}}, \"engine\": \"tesseract\", \"height\": 1000, \"reasons\": [\"The license number on the document (Z03-26-000330) does not match the number you entered (Z03-26-000303). Check the number or upload the correct license.\"], \"decision\": \"needs_correction\", \"extracted\": {\"dates\": [\"2031-11-20\", \"1986-11-20\"], \"expiry_date\": \"2031-11-20\", \"license_numbers\": [\"Z03-26-000330\"], \"name_tokens_found\": [\"CELIA\", \"NAVARRO\"]}, \"sharpness\": 1403.1, \"confidence\": 95.2, \"preliminary\": true, \"document_score\": 3, \"official_verification\": \"unavailable\"}, \"updated_at\": \"2026-10-14 05:12:02.000000\", \"expiry_date\": \"2031-11-20\", \"reviewed_at\": null, \"reviewed_by\": null, \"current_flag\": 1, \"license_type\": \"professional\", \"review_notes\": null, \"submitted_at\": \"2026-10-11 05:12:01.000000\", \"review_status\": \"pending\", \"issuing_agency\": \"SAMPLE TEST DATA\", \"license_number\": \"Z03-26-000303\", \"license_status\": \"valid\", \"restriction_codes\": null, \"license_image_path\": \"driver-licenses/sample/sample-fac-001.png\", \"original_file_name\": \"sample-fac-001-sample-license.png\", \"verification_reason\": \"The license number on the document (Z03-26-000330) does not match the number you entered (Z03-26-000303). Check the number or upload the correct license.\", \"verification_source\": \"automatic\", \"verification_status\": \"needs_correction\"}','2026-10-23 15:29:56.686125'),(63,'driver_licenses','47','baseline',NULL,NULL,'history installation',NULL,NULL,'{\"driver_id\": 47, \"checked_at\": \"2026-10-14 05:12:03.000000\", \"created_at\": \"2026-10-14 01:16:41.000000\", \"issue_date\": null, \"license_id\": 47, \"ocr_result\": {\"text\": \"SAMPLE DRIVER LICENSE - TEST DATA ONLY\\nNOT A GOVERNMENT-ISSUED DOCUMENT. FOR PASS SYSTEM TESTING.\\nLast Name, First Name\\nAQUINO, DENNIS\\nLicense No.\\nZ04-26-000404\\nExpiration Date\\n2029/01/08\\nDate of Birth\\n1990/01/08\\nLicense Type\\nNON-PROFESSIONAL\\nSAMPLE * TEST DATAONLY * NOT VALID FOR ANY PURPOSE\", \"notes\": [\"Preliminary validation only. Authenticity has not been confirmed: no official LTO verification service is integrated, so PASS staff must review the document.\"], \"width\": 1600, \"checks\": {\"name\": {\"detail\": \"The registered first and last name appear on the document.\", \"result\": \"pass\"}, \"expiry_date\": {\"detail\": \"The expiration date on the document matches the submitted date.\", \"result\": \"pass\"}, \"document_type\": {\"detail\": \"Matched 3 LTO license marker group(s).\", \"result\": \"pass\"}, \"image_quality\": {\"detail\": \"Resolution 1600x1000, sharpness 1458.6.\", \"result\": \"pass\"}, \"license_number\": {\"detail\": \"The license number on the document matches the submitted number.\", \"result\": \"pass\"}, \"text_extraction\": {\"detail\": \"234 readable characters, mean confidence 95%.\", \"result\": \"pass\"}}, \"engine\": \"tesseract\", \"height\": 1000, \"reasons\": [], \"decision\": \"pending_review\", \"extracted\": {\"dates\": [\"2029-01-08\", \"1990-01-08\"], \"expiry_date\": \"2029-01-08\", \"license_numbers\": [\"Z04-26-000404\"], \"name_tokens_found\": [\"DENNIS\", \"AQUINO\"]}, \"sharpness\": 1458.6, \"confidence\": 95, \"preliminary\": true, \"document_score\": 3, \"official_verification\": \"unavailable\"}, \"updated_at\": \"2026-10-14 05:12:03.000000\", \"expiry_date\": \"2029-01-08\", \"reviewed_at\": null, \"reviewed_by\": null, \"current_flag\": 1, \"license_type\": \"non_professional\", \"review_notes\": null, \"submitted_at\": \"2026-10-13 05:12:02.000000\", \"review_status\": \"pending\", \"issuing_agency\": \"SAMPLE TEST DATA\", \"license_number\": \"Z04-26-000404\", \"license_status\": \"valid\", \"restriction_codes\": null, \"license_image_path\": \"driver-licenses/sample/sample-fac-002.png\", \"original_file_name\": \"sample-fac-002-sample-license.png\", \"verification_reason\": null, \"verification_source\": \"automatic\", \"verification_status\": \"pending_review\"}','2026-10-23 15:29:56.686125'),(64,'driver_licenses','48','baseline',NULL,NULL,'history installation',NULL,NULL,'{\"driver_id\": 48, \"checked_at\": \"2026-10-14 05:12:04.000000\", \"created_at\": \"2026-10-14 01:16:44.000000\", \"issue_date\": null, \"license_id\": 48, \"ocr_result\": {\"text\": \"SAMPLE DRIVER LICENSE - TEST DATA ONLY\\nNOT A GOVERNMENT-ISSUED DOCUMENT. FOR PASS SYSTEM TESTING.\\nLast Name, First Name\\nBAUTISTA, ELENA\\nLicense No.\\nZ05-26-000505\\nExpiration Date\\n2036/05/27\\nDate of Birth\\n1993/05/27\\nLicense Type\\nNON-PROFESSIONAL\\nSAMPLE * TEST DATAONLY * NOT VALID FOR ANY PURPOSE\", \"notes\": [\"Preliminary validation only. Authenticity has not been confirmed: no official LTO verification service is integrated, so PASS staff must review the document.\"], \"width\": 1600, \"checks\": {\"name\": {\"detail\": \"The registered first and last name appear on the document.\", \"result\": \"pass\"}, \"expiry_date\": {\"detail\": \"The expiration date on the document matches the submitted date.\", \"result\": \"pass\"}, \"document_type\": {\"detail\": \"Matched 3 LTO license marker group(s).\", \"result\": \"pass\"}, \"image_quality\": {\"detail\": \"Resolution 1600x1000, sharpness 1464.8.\", \"result\": \"pass\"}, \"license_number\": {\"detail\": \"The license number on the document matches the submitted number.\", \"result\": \"pass\"}, \"text_extraction\": {\"detail\": \"235 readable characters, mean confidence 95.1%.\", \"result\": \"pass\"}}, \"engine\": \"tesseract\", \"height\": 1000, \"reasons\": [], \"decision\": \"pending_review\", \"extracted\": {\"dates\": [\"2036-05-27\", \"1993-05-27\"], \"expiry_date\": \"2036-05-27\", \"license_numbers\": [\"Z05-26-000505\"], \"name_tokens_found\": [\"ELENA\", \"BAUTISTA\"]}, \"sharpness\": 1464.8, \"confidence\": 95.1, \"preliminary\": true, \"document_score\": 3, \"official_verification\": \"unavailable\"}, \"updated_at\": \"2026-10-14 05:12:04.000000\", \"expiry_date\": \"2036-05-27\", \"reviewed_at\": \"2026-09-25 05:12:03.000000\", \"reviewed_by\": 1, \"current_flag\": 1, \"license_type\": \"non_professional\", \"review_notes\": null, \"submitted_at\": \"2026-09-24 05:12:03.000000\", \"review_status\": \"pending\", \"issuing_agency\": \"SAMPLE TEST DATA\", \"license_number\": \"Z05-26-000505\", \"license_status\": \"valid\", \"restriction_codes\": null, \"license_image_path\": \"driver-licenses/sample/sample-emp-001.png\", \"original_file_name\": \"sample-emp-001-sample-license.png\", \"verification_reason\": null, \"verification_source\": \"staff\", \"verification_status\": \"verified\"}','2026-10-23 15:29:56.686125'),(67,'vehicles','51','baseline',NULL,NULL,'history installation',NULL,NULL,'{\"make\": \"Toyota\", \"color\": \"White\", \"model\": \"Vios\", \"cr_number\": \"SAMPLE-CR-1001\", \"or_number\": \"SAMPLE-OR-1001\", \"plate_key\": \"SMP1001\", \"created_at\": \"2026-08-30 00:00:00.000000\", \"deleted_at\": null, \"updated_at\": \"2026-08-30 00:00:00.000000\", \"vehicle_id\": 51, \"year_model\": 2021, \"plate_number\": \"SMP 1001\", \"owner_user_id\": 32, \"chassis_number\": null, \"vehicle_type_id\": 1, \"owner_visitor_id\": null, \"vehicle_photo_path\": null, \"registration_status\": \"registered\"}','2026-10-23 15:29:56.750318'),(68,'vehicles','52','baseline',NULL,NULL,'history installation',NULL,NULL,'{\"make\": \"Honda\", \"color\": \"Red\", \"model\": \"Click 125i\", \"cr_number\": \"SAMPLE-CR-1002\", \"or_number\": \"SAMPLE-OR-1002\", \"plate_key\": \"SMP1002\", \"created_at\": \"2026-08-30 00:00:00.000000\", \"deleted_at\": null, \"updated_at\": \"2026-08-30 00:00:00.000000\", \"vehicle_id\": 52, \"year_model\": 2023, \"plate_number\": \"SMP 1002\", \"owner_user_id\": 33, \"chassis_number\": null, \"vehicle_type_id\": 6, \"owner_visitor_id\": null, \"vehicle_photo_path\": null, \"registration_status\": \"registered\"}','2026-10-23 15:29:56.750318'),(69,'vehicles','53','baseline',NULL,NULL,'history installation',NULL,NULL,'{\"make\": \"Mitsubishi\", \"color\": \"Gray\", \"model\": \"Montero Sport\", \"cr_number\": \"SAMPLE-CR-1003\", \"or_number\": \"SAMPLE-OR-1003\", \"plate_key\": \"SMP1003\", \"created_at\": \"2026-08-30 00:00:00.000000\", \"deleted_at\": null, \"updated_at\": \"2026-08-30 00:00:00.000000\", \"vehicle_id\": 53, \"year_model\": 2019, \"plate_number\": \"SMP 1003\", \"owner_user_id\": 34, \"chassis_number\": null, \"vehicle_type_id\": 2, \"owner_visitor_id\": null, \"vehicle_photo_path\": null, \"registration_status\": \"registered\"}','2026-10-23 15:29:56.750318'),(70,'vehicles','54','baseline',NULL,NULL,'history installation',NULL,NULL,'{\"make\": \"Ford\", \"color\": \"Blue\", \"model\": \"Ranger\", \"cr_number\": \"SAMPLE-CR-1004\", \"or_number\": \"SAMPLE-OR-1004\", \"plate_key\": \"SMP1004\", \"created_at\": \"2026-08-30 00:00:00.000000\", \"deleted_at\": null, \"updated_at\": \"2026-08-30 00:00:00.000000\", \"vehicle_id\": 54, \"year_model\": 2020, \"plate_number\": \"SMP 1004\", \"owner_user_id\": 35, \"chassis_number\": null, \"vehicle_type_id\": 4, \"owner_visitor_id\": null, \"vehicle_photo_path\": null, \"registration_status\": \"registered\"}','2026-10-23 15:29:56.750318'),(71,'vehicles','55','baseline',NULL,NULL,'history installation',NULL,NULL,'{\"make\": \"Toyota\", \"color\": \"Silver\", \"model\": \"Innova\", \"cr_number\": \"SAMPLE-CR-1005\", \"or_number\": \"SAMPLE-OR-1005\", \"plate_key\": \"SMP1005\", \"created_at\": \"2026-08-30 00:00:00.000000\", \"deleted_at\": null, \"updated_at\": \"2026-08-30 00:00:00.000000\", \"vehicle_id\": 55, \"year_model\": 2022, \"plate_number\": \"SMP 1005\", \"owner_user_id\": 36, \"chassis_number\": null, \"vehicle_type_id\": 5, \"owner_visitor_id\": null, \"vehicle_photo_path\": null, \"registration_status\": \"registered\"}','2026-10-23 15:29:56.750318'),(72,'vehicles','76','baseline',NULL,NULL,'history installation',NULL,NULL,'{\"make\": \"Yamaha\", \"color\": \"Black\", \"model\": \"Mio Gear\", \"cr_number\": \"SAMPLE-CR-2004\", \"or_number\": \"SAMPLE-OR-2004\", \"plate_key\": \"SMP2004\", \"created_at\": \"2026-08-30 00:00:00.000000\", \"deleted_at\": null, \"updated_at\": \"2026-08-30 00:00:00.000000\", \"vehicle_id\": 76, \"year_model\": 2024, \"plate_number\": \"SMP 2004\", \"owner_user_id\": 35, \"chassis_number\": null, \"vehicle_type_id\": 6, \"owner_visitor_id\": null, \"vehicle_photo_path\": null, \"registration_status\": \"registered\"}','2026-10-23 15:29:56.750318'),(73,'vehicles','135','baseline',NULL,NULL,'history installation',NULL,NULL,'{\"make\": \"Isuzu\", \"color\": null, \"model\": \"Traviz\", \"cr_number\": null, \"or_number\": null, \"plate_key\": \"SMPV001\", \"created_at\": \"2026-10-13 00:00:00.000000\", \"deleted_at\": null, \"updated_at\": \"2026-10-13 00:00:00.000000\", \"vehicle_id\": 135, \"year_model\": null, \"plate_number\": \"SMP V001\", \"owner_user_id\": null, \"chassis_number\": null, \"vehicle_type_id\": 4, \"owner_visitor_id\": 61, \"vehicle_photo_path\": null, \"registration_status\": \"registered\"}','2026-10-23 15:29:56.750318'),(74,'vehicles','136','baseline',NULL,NULL,'history installation',NULL,NULL,'{\"make\": \"Nissan\", \"color\": null, \"model\": \"Almera\", \"cr_number\": null, \"or_number\": null, \"plate_key\": \"SMPV002\", \"created_at\": \"2026-10-13 00:00:00.000000\", \"deleted_at\": null, \"updated_at\": \"2026-10-13 00:00:00.000000\", \"vehicle_id\": 136, \"year_model\": null, \"plate_number\": \"SMP V002\", \"owner_user_id\": null, \"chassis_number\": null, \"vehicle_type_id\": 1, \"owner_visitor_id\": 62, \"vehicle_photo_path\": null, \"registration_status\": \"registered\"}','2026-10-23 15:29:56.750318'),(75,'vehicles','137','baseline',NULL,NULL,'history installation',NULL,NULL,'{\"make\": \"Hyundai\", \"color\": null, \"model\": \"Accent\", \"cr_number\": null, \"or_number\": null, \"plate_key\": \"SMPV003\", \"created_at\": \"2026-10-13 00:00:00.000000\", \"deleted_at\": null, \"updated_at\": \"2026-10-13 00:00:00.000000\", \"vehicle_id\": 137, \"year_model\": null, \"plate_number\": \"SMP V003\", \"owner_user_id\": null, \"chassis_number\": null, \"vehicle_type_id\": 1, \"owner_visitor_id\": 63, \"vehicle_photo_path\": null, \"registration_status\": \"registered\"}','2026-10-23 15:29:56.750318'),(76,'vehicles','138','baseline',NULL,NULL,'history installation',NULL,NULL,'{\"make\": \"Yamaha\", \"color\": null, \"model\": \"NMAX\", \"cr_number\": null, \"or_number\": null, \"plate_key\": \"SMPV004\", \"created_at\": \"2026-10-13 00:00:00.000000\", \"deleted_at\": null, \"updated_at\": \"2026-10-13 00:00:00.000000\", \"vehicle_id\": 138, \"year_model\": null, \"plate_number\": \"SMP V004\", \"owner_user_id\": null, \"chassis_number\": null, \"vehicle_type_id\": 6, \"owner_visitor_id\": 64, \"vehicle_photo_path\": null, \"registration_status\": \"registered\"}','2026-10-23 15:29:56.750318'),(77,'vehicles','139','baseline',NULL,NULL,'history installation',NULL,NULL,'{\"make\": \"Toyota\", \"color\": null, \"model\": null, \"cr_number\": null, \"or_number\": null, \"plate_key\": \"DEMO4821\", \"created_at\": \"2026-10-15 05:17:59.000000\", \"deleted_at\": null, \"updated_at\": \"2026-10-15 05:17:59.000000\", \"vehicle_id\": 139, \"year_model\": null, \"plate_number\": \"DEMO 4821\", \"owner_user_id\": null, \"chassis_number\": null, \"vehicle_type_id\": 1, \"owner_visitor_id\": 65, \"vehicle_photo_path\": null, \"registration_status\": \"registered\"}','2026-10-23 15:29:56.750318'),(78,'vehicles','140','baseline',NULL,NULL,'history installation',NULL,NULL,'{\"make\": null, \"color\": null, \"model\": null, \"cr_number\": null, \"or_number\": null, \"plate_key\": \"DEMO4822\", \"created_at\": \"2026-10-15 05:22:32.000000\", \"deleted_at\": null, \"updated_at\": \"2026-10-15 05:22:32.000000\", \"vehicle_id\": 140, \"year_model\": null, \"plate_number\": \"DEMO 4822\", \"owner_user_id\": null, \"chassis_number\": null, \"vehicle_type_id\": 1, \"owner_visitor_id\": 65, \"vehicle_photo_path\": null, \"registration_status\": \"registered\"}','2026-10-23 15:29:56.750318'),(79,'vehicles','141','baseline',NULL,NULL,'history installation',NULL,NULL,'{\"make\": null, \"color\": null, \"model\": null, \"cr_number\": null, \"or_number\": null, \"plate_key\": \"DEMO4823\", \"created_at\": \"2026-10-15 05:22:41.000000\", \"deleted_at\": null, \"updated_at\": \"2026-10-15 05:22:41.000000\", \"vehicle_id\": 141, \"year_model\": null, \"plate_number\": \"DEMO 4823\", \"owner_user_id\": null, \"chassis_number\": null, \"vehicle_type_id\": 1, \"owner_visitor_id\": 65, \"vehicle_photo_path\": null, \"registration_status\": \"registered\"}','2026-10-23 15:29:56.750318'),(80,'vehicles','142','baseline',NULL,NULL,'history installation',NULL,NULL,'{\"make\": null, \"color\": null, \"model\": null, \"cr_number\": null, \"or_number\": null, \"plate_key\": \"DEMO4824\", \"created_at\": \"2026-10-15 05:25:27.000000\", \"deleted_at\": null, \"updated_at\": \"2026-10-15 05:25:27.000000\", \"vehicle_id\": 142, \"year_model\": null, \"plate_number\": \"DEMO 4824\", \"owner_user_id\": null, \"chassis_number\": null, \"vehicle_type_id\": 1, \"owner_visitor_id\": 65, \"vehicle_photo_path\": null, \"registration_status\": \"registered\"}','2026-10-23 15:29:56.750318'),(81,'vehicles','143','baseline',NULL,NULL,'history installation',NULL,NULL,'{\"make\": \"Toyota\", \"color\": \"White\", \"model\": \"Vios\", \"cr_number\": null, \"or_number\": null, \"plate_key\": \"GUEST001\", \"created_at\": \"2026-10-15 08:15:48.000000\", \"deleted_at\": null, \"updated_at\": \"2026-10-15 08:15:48.000000\", \"vehicle_id\": 143, \"year_model\": null, \"plate_number\": \"GUEST 001\", \"owner_user_id\": null, \"chassis_number\": null, \"vehicle_type_id\": 1, \"owner_visitor_id\": 66, \"vehicle_photo_path\": null, \"registration_status\": \"registered\"}','2026-10-23 15:29:56.750318'),(82,'vehicles','144','baseline',NULL,NULL,'history installation',NULL,NULL,'{\"make\": \"Honda\", \"color\": null, \"model\": \"City\", \"cr_number\": null, \"or_number\": null, \"plate_key\": \"ROSA2026\", \"created_at\": \"2026-10-15 08:24:38.000000\", \"deleted_at\": null, \"updated_at\": \"2026-10-15 08:24:38.000000\", \"vehicle_id\": 144, \"year_model\": null, \"plate_number\": \"ROSA 2026\", \"owner_user_id\": null, \"chassis_number\": null, \"vehicle_type_id\": 1, \"owner_visitor_id\": 67, \"vehicle_photo_path\": null, \"registration_status\": \"registered\"}','2026-10-23 15:29:56.750318'),(83,'vehicles','145','baseline',NULL,NULL,'history installation',NULL,NULL,'{\"make\": \"Honda\", \"color\": null, \"model\": \"City\", \"cr_number\": null, \"or_number\": null, \"plate_key\": \"ROSA2027\", \"created_at\": \"2026-10-15 08:24:47.000000\", \"deleted_at\": null, \"updated_at\": \"2026-10-15 08:24:47.000000\", \"vehicle_id\": 145, \"year_model\": null, \"plate_number\": \"ROSA 2027\", \"owner_user_id\": null, \"chassis_number\": null, \"vehicle_type_id\": 1, \"owner_visitor_id\": 67, \"vehicle_photo_path\": null, \"registration_status\": \"registered\"}','2026-10-23 15:29:56.750318'),(84,'vehicles','146','baseline',NULL,NULL,'history installation',NULL,NULL,'{\"make\": \"suzuki\", \"color\": null, \"model\": null, \"cr_number\": null, \"or_number\": null, \"plate_key\": \"CAR123\", \"created_at\": \"2026-10-15 10:12:58.000000\", \"deleted_at\": null, \"updated_at\": \"2026-10-15 10:12:58.000000\", \"vehicle_id\": 146, \"year_model\": null, \"plate_number\": \"CAR 123\", \"owner_user_id\": null, \"chassis_number\": null, \"vehicle_type_id\": 2, \"owner_visitor_id\": 68, \"vehicle_photo_path\": null, \"registration_status\": \"registered\"}','2026-10-23 15:29:56.750318'),(85,'vehicles','147','baseline',NULL,NULL,'history installation',NULL,NULL,'{\"make\": \"Honda\", \"color\": null, \"model\": \"City\", \"cr_number\": null, \"or_number\": null, \"plate_key\": \"LARA3101\", \"created_at\": \"2026-10-15 11:33:25.000000\", \"deleted_at\": null, \"updated_at\": \"2026-10-15 11:33:25.000000\", \"vehicle_id\": 147, \"year_model\": null, \"plate_number\": \"LARA 3101\", \"owner_user_id\": null, \"chassis_number\": null, \"vehicle_type_id\": 1, \"owner_visitor_id\": 69, \"vehicle_photo_path\": null, \"registration_status\": \"registered\"}','2026-10-23 15:29:56.750318'),(98,'vehicle_authorizations','35','baseline',NULL,NULL,'history installation',NULL,NULL,'{\"remarks\": \"wrong parking\", \"created_at\": \"2026-08-31 00:00:00.000000\", \"updated_at\": \"2026-10-15 11:49:11.000000\", \"valid_from\": \"2026-09-04\", \"vehicle_id\": 51, \"active_flag\": null, \"reviewed_at\": \"2026-10-15 11:49:11.000000\", \"reviewed_by\": 1, \"valid_until\": \"2027-08-14\", \"requested_at\": \"2026-08-31 09:00:00.000000\", \"sticker_number\": \"SAMPLE-STK-1001\", \"parking_area_id\": null, \"authorization_id\": 35, \"authorization_status\": \"suspended\"}','2026-10-23 15:29:56.811187'),(99,'vehicle_authorizations','36','baseline',NULL,NULL,'history installation',NULL,NULL,'{\"remarks\": null, \"created_at\": \"2026-08-31 00:00:00.000000\", \"updated_at\": \"2026-09-03 00:00:00.000000\", \"valid_from\": null, \"vehicle_id\": 52, \"active_flag\": null, \"reviewed_at\": null, \"reviewed_by\": null, \"valid_until\": null, \"requested_at\": \"2026-10-12 09:00:00.000000\", \"sticker_number\": null, \"parking_area_id\": null, \"authorization_id\": 36, \"authorization_status\": \"pending\"}','2026-10-23 15:29:56.811187'),(100,'vehicle_authorizations','37','baseline',NULL,NULL,'history installation',NULL,NULL,'{\"remarks\": \"SAMPLE: Suspended pending review of a parking violation (test data).\", \"created_at\": \"2026-08-31 00:00:00.000000\", \"updated_at\": \"2026-10-11 00:00:00.000000\", \"valid_from\": \"2026-09-04\", \"vehicle_id\": 53, \"active_flag\": null, \"reviewed_at\": \"2026-10-11 14:00:00.000000\", \"reviewed_by\": 1, \"valid_until\": \"2027-08-14\", \"requested_at\": \"2026-08-31 09:00:00.000000\", \"sticker_number\": \"SAMPLE-STK-1003\", \"parking_area_id\": null, \"authorization_id\": 37, \"authorization_status\": \"suspended\"}','2026-10-23 15:29:56.811187'),(101,'vehicle_authorizations','38','baseline',NULL,NULL,'history installation',NULL,NULL,'{\"remarks\": \"SAMPLE: OR/CR details did not match the vehicle (test data).\", \"created_at\": \"2026-08-31 00:00:00.000000\", \"updated_at\": \"2026-09-03 00:00:00.000000\", \"valid_from\": null, \"vehicle_id\": 54, \"active_flag\": null, \"reviewed_at\": \"2026-09-03 14:00:00.000000\", \"reviewed_by\": 1, \"valid_until\": null, \"requested_at\": \"2026-08-31 09:00:00.000000\", \"sticker_number\": null, \"parking_area_id\": null, \"authorization_id\": 38, \"authorization_status\": \"rejected\"}','2026-10-23 15:29:56.811187'),(102,'vehicle_authorizations','39','baseline',NULL,NULL,'history installation',NULL,NULL,'{\"remarks\": null, \"created_at\": \"2026-08-31 00:00:00.000000\", \"updated_at\": \"2026-09-03 00:00:00.000000\", \"valid_from\": \"2026-09-04\", \"vehicle_id\": 55, \"active_flag\": 1, \"reviewed_at\": \"2026-09-03 14:00:00.000000\", \"reviewed_by\": 1, \"valid_until\": \"2027-08-14\", \"requested_at\": \"2026-08-31 09:00:00.000000\", \"sticker_number\": \"SAMPLE-STK-1005\", \"parking_area_id\": null, \"authorization_id\": 39, \"authorization_status\": \"approved\"}','2026-10-23 15:29:56.811187'),(103,'vehicle_authorizations','40','baseline',NULL,NULL,'history installation',NULL,NULL,'{\"remarks\": null, \"created_at\": \"2026-08-31 00:00:00.000000\", \"updated_at\": \"2026-09-03 00:00:00.000000\", \"valid_from\": \"2026-09-04\", \"vehicle_id\": 76, \"active_flag\": 1, \"reviewed_at\": \"2026-09-03 14:00:00.000000\", \"reviewed_by\": 1, \"valid_until\": \"2027-08-14\", \"requested_at\": \"2026-08-31 09:00:00.000000\", \"sticker_number\": \"SAMPLE-STK-2004\", \"parking_area_id\": null, \"authorization_id\": 40, \"authorization_status\": \"approved\"}','2026-10-23 15:29:56.811187'),(105,'vehicle_driver','51:44','baseline',NULL,NULL,'history installation',NULL,NULL,'{\"driver_id\": 44, \"created_at\": \"2026-08-30 00:00:00.000000\", \"is_primary\": 1, \"updated_at\": \"2026-08-30 00:00:00.000000\", \"vehicle_id\": 51, \"relationship\": \"owner\", \"authorized_from\": null, \"authorized_until\": null}','2026-10-23 15:29:56.880515'),(106,'vehicle_driver','52:45','baseline',NULL,NULL,'history installation',NULL,NULL,'{\"driver_id\": 45, \"created_at\": \"2026-08-30 00:00:00.000000\", \"is_primary\": 1, \"updated_at\": \"2026-08-30 00:00:00.000000\", \"vehicle_id\": 52, \"relationship\": \"owner\", \"authorized_from\": null, \"authorized_until\": null}','2026-10-23 15:29:56.880515'),(107,'vehicle_driver','53:46','baseline',NULL,NULL,'history installation',NULL,NULL,'{\"driver_id\": 46, \"created_at\": \"2026-08-30 00:00:00.000000\", \"is_primary\": 1, \"updated_at\": \"2026-08-30 00:00:00.000000\", \"vehicle_id\": 53, \"relationship\": \"owner\", \"authorized_from\": null, \"authorized_until\": null}','2026-10-23 15:29:56.880515'),(108,'vehicle_driver','54:47','baseline',NULL,NULL,'history installation',NULL,NULL,'{\"driver_id\": 47, \"created_at\": \"2026-08-30 00:00:00.000000\", \"is_primary\": 1, \"updated_at\": \"2026-08-30 00:00:00.000000\", \"vehicle_id\": 54, \"relationship\": \"owner\", \"authorized_from\": null, \"authorized_until\": null}','2026-10-23 15:29:56.880515'),(109,'vehicle_driver','55:48','baseline',NULL,NULL,'history installation',NULL,NULL,'{\"driver_id\": 48, \"created_at\": \"2026-08-30 00:00:00.000000\", \"is_primary\": 1, \"updated_at\": \"2026-08-30 00:00:00.000000\", \"vehicle_id\": 55, \"relationship\": \"owner\", \"authorized_from\": null, \"authorized_until\": null}','2026-10-23 15:29:56.880515'),(110,'vehicle_driver','76:47','baseline',NULL,NULL,'history installation',NULL,NULL,'{\"driver_id\": 47, \"created_at\": \"2026-08-30 00:00:00.000000\", \"is_primary\": 1, \"updated_at\": \"2026-08-30 00:00:00.000000\", \"vehicle_id\": 76, \"relationship\": \"owner\", \"authorized_from\": null, \"authorized_until\": null}','2026-10-23 15:29:56.880515'),(112,'user_vehicle_selections','32','baseline',NULL,NULL,'history installation',NULL,NULL,'{\"user_id\": 32, \"updated_at\": \"2026-10-23 07:29:55.000000\", \"vehicle_id\": 51}','2026-10-23 15:29:56.929044'),(113,'user_vehicle_selections','34','baseline',NULL,NULL,'history installation',NULL,NULL,'{\"user_id\": 34, \"updated_at\": \"2026-10-23 07:29:55.000000\", \"vehicle_id\": 53}','2026-10-23 15:29:56.929044'),(114,'user_vehicle_selections','35','baseline',NULL,NULL,'history installation',NULL,NULL,'{\"user_id\": 35, \"updated_at\": \"2026-10-23 07:29:55.000000\", \"vehicle_id\": 76}','2026-10-23 15:29:56.929044'),(115,'user_vehicle_selections','36','baseline',NULL,NULL,'history installation',NULL,NULL,'{\"user_id\": 36, \"updated_at\": \"2026-10-23 07:29:55.000000\", \"vehicle_id\": 55}','2026-10-23 15:29:56.929044'),(119,'rfid_tags','1','baseline',NULL,NULL,'history installation',NULL,NULL,'{\"rfid_id\": 1, \"tag_uid\": \"SAMPLE-RFID-1001\", \"tag_type\": \"sticker\", \"batch_code\": \"SAMPLE TEST DATA\", \"created_at\": \"2026-09-04 00:00:00.000000\", \"tag_status\": \"assigned\", \"updated_at\": \"2026-09-04 00:00:00.000000\", \"acquired_at\": \"2026-09-04\", \"serial_number\": \"SAMPLE-RFID-1001\"}','2026-10-23 15:29:56.972776'),(120,'rfid_tags','2','baseline',NULL,NULL,'history installation',NULL,NULL,'{\"rfid_id\": 2, \"tag_uid\": \"SAMPLE-RFID-1003\", \"tag_type\": \"sticker\", \"batch_code\": \"SAMPLE TEST DATA\", \"created_at\": \"2026-09-04 00:00:00.000000\", \"tag_status\": \"assigned\", \"updated_at\": \"2026-09-04 00:00:00.000000\", \"acquired_at\": \"2026-09-04\", \"serial_number\": \"SAMPLE-RFID-1003\"}','2026-10-23 15:29:56.972776'),(121,'rfid_tags','3','baseline',NULL,NULL,'history installation',NULL,NULL,'{\"rfid_id\": 3, \"tag_uid\": \"SAMPLE-RFID-2004\", \"tag_type\": \"sticker\", \"batch_code\": \"SAMPLE TEST DATA\", \"created_at\": \"2026-09-04 00:00:00.000000\", \"tag_status\": \"assigned\", \"updated_at\": \"2026-09-04 00:00:00.000000\", \"acquired_at\": \"2026-09-04\", \"serial_number\": \"SAMPLE-RFID-2004\"}','2026-10-23 15:29:56.972776'),(122,'rfid_tags','4','baseline',NULL,NULL,'history installation',NULL,NULL,'{\"rfid_id\": 4, \"tag_uid\": \"SAMPLE-RFID-1005\", \"tag_type\": \"sticker\", \"batch_code\": \"SAMPLE TEST DATA\", \"created_at\": \"2026-09-04 00:00:00.000000\", \"tag_status\": \"assigned\", \"updated_at\": \"2026-09-04 00:00:00.000000\", \"acquired_at\": \"2026-09-04\", \"serial_number\": \"SAMPLE-RFID-1005\"}','2026-10-23 15:29:56.972776'),(126,'rfid_assignments','1','baseline',NULL,NULL,'history installation',NULL,NULL,'{\"remarks\": \"SAMPLE TEST DATA\", \"rfid_id\": 1, \"user_id\": 32, \"created_at\": \"2026-09-04 00:00:00.000000\", \"updated_at\": \"2026-09-04 00:00:00.000000\", \"vehicle_id\": 51, \"active_flag\": 1, \"assigned_at\": \"2026-09-04 00:00:00.000000\", \"assigned_by\": 1, \"valid_until\": \"2027-09-04\", \"assignment_id\": 1, \"unassigned_at\": null, \"assignment_status\": \"active\"}','2026-10-23 15:29:57.025073'),(127,'rfid_assignments','2','baseline',NULL,NULL,'history installation',NULL,NULL,'{\"remarks\": \"SAMPLE TEST DATA\", \"rfid_id\": 2, \"user_id\": 34, \"created_at\": \"2026-09-04 00:00:00.000000\", \"updated_at\": \"2026-09-04 00:00:00.000000\", \"vehicle_id\": 53, \"active_flag\": 1, \"assigned_at\": \"2026-09-04 00:00:00.000000\", \"assigned_by\": 1, \"valid_until\": \"2027-09-04\", \"assignment_id\": 2, \"unassigned_at\": null, \"assignment_status\": \"active\"}','2026-10-23 15:29:57.025073'),(128,'rfid_assignments','3','baseline',NULL,NULL,'history installation',NULL,NULL,'{\"remarks\": \"SAMPLE TEST DATA\", \"rfid_id\": 3, \"user_id\": 35, \"created_at\": \"2026-09-04 00:00:00.000000\", \"updated_at\": \"2026-09-04 00:00:00.000000\", \"vehicle_id\": 76, \"active_flag\": 1, \"assigned_at\": \"2026-09-04 00:00:00.000000\", \"assigned_by\": 1, \"valid_until\": \"2027-09-04\", \"assignment_id\": 3, \"unassigned_at\": null, \"assignment_status\": \"active\"}','2026-10-23 15:29:57.025073'),(129,'rfid_assignments','4','baseline',NULL,NULL,'history installation',NULL,NULL,'{\"remarks\": \"SAMPLE TEST DATA\", \"rfid_id\": 4, \"user_id\": 36, \"created_at\": \"2026-09-04 00:00:00.000000\", \"updated_at\": \"2026-09-04 00:00:00.000000\", \"vehicle_id\": 55, \"active_flag\": 1, \"assigned_at\": \"2026-09-04 00:00:00.000000\", \"assigned_by\": 1, \"valid_until\": \"2027-09-04\", \"assignment_id\": 4, \"unassigned_at\": null, \"assignment_status\": \"active\"}','2026-10-23 15:29:57.025073'),(133,'visitors','61','baseline',NULL,NULL,'history installation',NULL,NULL,'{\"email\": \"visitor.liza.ramos@sample.pass.test\", \"id_type\": \"other\", \"user_id\": null, \"id_number\": \"SAMPLE-ID-V1\", \"last_name\": \"Ramos\", \"created_at\": \"2026-10-13 00:00:00.000000\", \"first_name\": \"Liza\", \"updated_at\": \"2026-10-13 00:00:00.000000\", \"visitor_id\": 61, \"affiliation\": \"Sample Supplier Co. (test)\", \"middle_name\": null, \"contact_number\": \"0900-111-0001\", \"is_blacklisted\": 0, \"blacklist_reason\": null}','2026-10-23 15:29:57.130390'),(134,'visitors','62','baseline',NULL,NULL,'history installation',NULL,NULL,'{\"email\": \"visitor.marco.dizon@sample.pass.test\", \"id_type\": \"other\", \"user_id\": null, \"id_number\": \"SAMPLE-ID-V2\", \"last_name\": \"Dizon\", \"created_at\": \"2026-10-13 00:00:00.000000\", \"first_name\": \"Marco\", \"updated_at\": \"2026-10-13 00:00:00.000000\", \"visitor_id\": 62, \"affiliation\": \"Parent visitor (test)\", \"middle_name\": null, \"contact_number\": \"0900-111-0002\", \"is_blacklisted\": 0, \"blacklist_reason\": null}','2026-10-23 15:29:57.130390'),(135,'visitors','63','baseline',NULL,NULL,'history installation',NULL,NULL,'{\"email\": \"visitor.nina.ocampo@sample.pass.test\", \"id_type\": \"other\", \"user_id\": null, \"id_number\": \"SAMPLE-ID-V3\", \"last_name\": \"Ocampo\", \"created_at\": \"2026-10-13 00:00:00.000000\", \"first_name\": \"Nina\", \"updated_at\": \"2026-10-13 00:00:00.000000\", \"visitor_id\": 63, \"affiliation\": \"Guest lecturer (test)\", \"middle_name\": null, \"contact_number\": \"0900-111-0003\", \"is_blacklisted\": 0, \"blacklist_reason\": null}','2026-10-23 15:29:57.130390'),(136,'visitors','64','baseline',NULL,NULL,'history installation',NULL,NULL,'{\"email\": \"visitor.oscar.velasco@sample.pass.test\", \"id_type\": \"other\", \"user_id\": null, \"id_number\": \"SAMPLE-ID-V4\", \"last_name\": \"Velasco\", \"created_at\": \"2026-10-13 00:00:00.000000\", \"first_name\": \"Oscar\", \"updated_at\": \"2026-10-13 00:00:00.000000\", \"visitor_id\": 64, \"affiliation\": \"Delivery rider (test)\", \"middle_name\": null, \"contact_number\": \"0900-111-0004\", \"is_blacklisted\": 0, \"blacklist_reason\": null}','2026-10-23 15:29:57.130390'),(137,'visitors','65','baseline',NULL,NULL,'history installation',NULL,NULL,'{\"email\": null, \"id_type\": null, \"user_id\": null, \"id_number\": null, \"last_name\": \"Mendoza\", \"created_at\": \"2026-10-15 05:17:59.000000\", \"first_name\": \"Carla\", \"updated_at\": \"2026-10-15 05:17:59.000000\", \"visitor_id\": 65, \"affiliation\": \"PASS browser test\", \"middle_name\": null, \"contact_number\": \"09175550101\", \"is_blacklisted\": 0, \"blacklist_reason\": null}','2026-10-23 15:29:57.130390'),(138,'visitors','66','baseline',NULL,NULL,'history installation',NULL,NULL,'{\"email\": \"guest.demo@pass.test\", \"id_type\": null, \"user_id\": 47, \"id_number\": null, \"last_name\": \"Santos\", \"created_at\": \"2026-10-15 08:15:48.000000\", \"first_name\": \"Maria\", \"updated_at\": \"2026-10-15 08:15:48.000000\", \"visitor_id\": 66, \"affiliation\": null, \"middle_name\": null, \"contact_number\": \"09171230001\", \"is_blacklisted\": 0, \"blacklist_reason\": null}','2026-10-23 15:29:57.130390'),(139,'visitors','67','baseline',NULL,NULL,'history installation',NULL,NULL,'{\"email\": \"rosa.villanueva.test@example.com\", \"id_type\": null, \"user_id\": 48, \"id_number\": null, \"last_name\": \"Villanueva\", \"created_at\": \"2026-10-15 08:24:20.000000\", \"first_name\": \"Rosa\", \"updated_at\": \"2026-10-15 08:24:20.000000\", \"visitor_id\": 67, \"affiliation\": null, \"middle_name\": null, \"contact_number\": \"09187654321\", \"is_blacklisted\": 0, \"blacklist_reason\": null}','2026-10-23 15:29:57.130390'),(140,'visitors','68','baseline',NULL,NULL,'history installation',NULL,NULL,'{\"email\": \"guest@pass.com\", \"id_type\": null, \"user_id\": 49, \"id_number\": null, \"last_name\": \"visitor\", \"created_at\": \"2026-10-15 09:38:19.000000\", \"first_name\": \"guestito\", \"updated_at\": \"2026-10-15 09:38:19.000000\", \"visitor_id\": 68, \"affiliation\": null, \"middle_name\": null, \"contact_number\": \"09128012356\", \"is_blacklisted\": 0, \"blacklist_reason\": null}','2026-10-23 15:29:57.130390'),(141,'visitors','69','baseline',NULL,NULL,'history installation',NULL,NULL,'{\"email\": \"lara.gomez.test@example.com\", \"id_type\": null, \"user_id\": 50, \"id_number\": null, \"last_name\": \"Gomez\", \"created_at\": \"2026-10-15 11:33:13.000000\", \"first_name\": \"Lara\", \"updated_at\": \"2026-10-15 11:33:13.000000\", \"visitor_id\": 69, \"affiliation\": null, \"middle_name\": null, \"contact_number\": \"09175550144\", \"is_blacklisted\": 0, \"blacklist_reason\": null}','2026-10-23 15:29:57.130390'),(148,'visitor_requests','1','baseline',NULL,NULL,'history installation',NULL,NULL,'{\"purpose\": \"Campus tour · Visiting: Admissions Office\", \"created_at\": \"2026-10-15 05:17:59.000000\", \"request_id\": 1, \"updated_at\": \"2026-10-15 05:20:24.000000\", \"vehicle_id\": 139, \"visitor_id\": 65, \"driver_name\": null, \"reviewed_at\": \"2026-10-15 05:20:24.000000\", \"reviewed_by\": 1, \"host_user_id\": null, \"request_status\": \"approved\", \"review_remarks\": null, \"passenger_count\": null, \"vehicle_photo_path\": null, \"destination_area_id\": 6, \"driver_is_requester\": null, \"requested_valid_from\": \"2026-10-15 05:17:00.000000\", \"documents_reviewed_at\": null, \"documents_reviewed_by\": null, \"driver_license_expiry\": null, \"requested_valid_until\": \"2026-10-15 09:17:00.000000\", \"driver_license_encrypted\": null, \"driver_license_photo_path\": null}','2026-10-23 15:29:57.182096'),(149,'visitor_requests','2','baseline',NULL,NULL,'history installation',NULL,NULL,'{\"purpose\": \"Delivery · Visiting: Accounting\", \"created_at\": \"2026-10-15 05:22:32.000000\", \"request_id\": 2, \"updated_at\": \"2026-10-15 05:22:36.000000\", \"vehicle_id\": 140, \"visitor_id\": 65, \"driver_name\": null, \"reviewed_at\": \"2026-10-15 05:22:36.000000\", \"reviewed_by\": 1, \"host_user_id\": null, \"request_status\": \"rejected\", \"review_remarks\": \"No appointment on record\", \"passenger_count\": null, \"vehicle_photo_path\": null, \"destination_area_id\": 6, \"driver_is_requester\": null, \"requested_valid_from\": \"2026-10-15 05:22:00.000000\", \"documents_reviewed_at\": null, \"documents_reviewed_by\": null, \"driver_license_expiry\": null, \"requested_valid_until\": \"2026-10-15 09:22:00.000000\", \"driver_license_encrypted\": null, \"driver_license_photo_path\": null}','2026-10-23 15:29:57.182096'),(150,'visitor_requests','3','baseline',NULL,NULL,'history installation',NULL,NULL,'{\"purpose\": \"Meeting · Visiting: Dean\", \"created_at\": \"2026-10-15 05:22:41.000000\", \"request_id\": 3, \"updated_at\": \"2026-10-15 05:22:45.000000\", \"vehicle_id\": 141, \"visitor_id\": 65, \"driver_name\": null, \"reviewed_at\": \"2026-10-15 05:22:45.000000\", \"reviewed_by\": 1, \"host_user_id\": null, \"request_status\": \"approved\", \"review_remarks\": null, \"passenger_count\": null, \"vehicle_photo_path\": null, \"destination_area_id\": 6, \"driver_is_requester\": null, \"requested_valid_from\": \"2026-10-15 05:22:00.000000\", \"documents_reviewed_at\": null, \"documents_reviewed_by\": null, \"driver_license_expiry\": null, \"requested_valid_until\": \"2026-10-15 09:22:00.000000\", \"driver_license_encrypted\": null, \"driver_license_photo_path\": null}','2026-10-23 15:29:57.182096'),(151,'visitor_requests','4','baseline',NULL,NULL,'history installation',NULL,NULL,'{\"purpose\": \"Interview · Visiting: HR Office\", \"created_at\": \"2026-10-15 05:25:27.000000\", \"request_id\": 4, \"updated_at\": \"2026-10-15 05:25:31.000000\", \"vehicle_id\": 142, \"visitor_id\": 65, \"driver_name\": null, \"reviewed_at\": \"2026-10-15 05:25:31.000000\", \"reviewed_by\": 1, \"host_user_id\": null, \"request_status\": \"approved\", \"review_remarks\": null, \"passenger_count\": null, \"vehicle_photo_path\": null, \"destination_area_id\": 6, \"driver_is_requester\": null, \"requested_valid_from\": \"2026-10-15 05:25:00.000000\", \"documents_reviewed_at\": null, \"documents_reviewed_by\": null, \"driver_license_expiry\": null, \"requested_valid_until\": \"2026-10-15 09:25:00.000000\", \"driver_license_encrypted\": null, \"driver_license_photo_path\": null}','2026-10-23 15:29:57.182096'),(152,'visitor_requests','5','baseline',NULL,NULL,'history installation',NULL,NULL,'{\"purpose\": \"Campus visit (demo request) · Visiting: Admissions Office\", \"created_at\": \"2026-10-15 08:15:48.000000\", \"request_id\": 5, \"updated_at\": \"2026-10-15 08:15:48.000000\", \"vehicle_id\": 143, \"visitor_id\": 66, \"driver_name\": null, \"reviewed_at\": null, \"reviewed_by\": null, \"host_user_id\": null, \"request_status\": \"pending\", \"review_remarks\": null, \"passenger_count\": null, \"vehicle_photo_path\": null, \"destination_area_id\": 6, \"driver_is_requester\": null, \"requested_valid_from\": \"2026-10-15 08:00:00.000000\", \"documents_reviewed_at\": null, \"documents_reviewed_by\": null, \"driver_license_expiry\": null, \"requested_valid_until\": \"2026-10-15 16:00:00.000000\", \"driver_license_encrypted\": null, \"driver_license_photo_path\": null}','2026-10-23 15:29:57.182096'),(153,'visitor_requests','6','baseline',NULL,NULL,'history installation',NULL,NULL,'{\"purpose\": \"Document pickup at the Registrar · Visiting: Registrar Office\", \"created_at\": \"2026-10-15 08:24:58.000000\", \"request_id\": 6, \"updated_at\": \"2026-10-15 08:27:02.000000\", \"vehicle_id\": 144, \"visitor_id\": 67, \"driver_name\": null, \"reviewed_at\": \"2026-10-15 08:27:02.000000\", \"reviewed_by\": 46, \"host_user_id\": null, \"request_status\": \"approved\", \"review_remarks\": null, \"passenger_count\": null, \"vehicle_photo_path\": null, \"destination_area_id\": 6, \"driver_is_requester\": null, \"requested_valid_from\": \"2026-10-15 08:14:00.000000\", \"documents_reviewed_at\": null, \"documents_reviewed_by\": null, \"driver_license_expiry\": null, \"requested_valid_until\": \"2026-10-15 11:24:00.000000\", \"driver_license_encrypted\": null, \"driver_license_photo_path\": null}','2026-10-23 15:29:57.182096'),(154,'visitor_requests','7','baseline',NULL,NULL,'history installation',NULL,NULL,'{\"purpose\": \"Second visit for rejection test · Visiting: Registrar Office\", \"created_at\": \"2026-10-15 08:25:10.000000\", \"request_id\": 7, \"updated_at\": \"2026-10-15 08:27:06.000000\", \"vehicle_id\": 145, \"visitor_id\": 67, \"driver_name\": null, \"reviewed_at\": \"2026-10-15 08:27:06.000000\", \"reviewed_by\": 46, \"host_user_id\": null, \"request_status\": \"rejected\", \"review_remarks\": \"The Registrar is closed for a holiday\", \"passenger_count\": null, \"vehicle_photo_path\": null, \"destination_area_id\": 6, \"driver_is_requester\": null, \"requested_valid_from\": \"2026-10-15 08:15:00.000000\", \"documents_reviewed_at\": null, \"documents_reviewed_by\": null, \"driver_license_expiry\": null, \"requested_valid_until\": \"2026-10-15 11:25:00.000000\", \"driver_license_encrypted\": null, \"driver_license_photo_path\": null}','2026-10-23 15:29:57.182096'),(155,'visitor_requests','8','baseline',NULL,NULL,'history installation',NULL,NULL,'{\"purpose\": \"guest speaker · Visiting: convention\", \"created_at\": \"2026-10-15 10:15:56.000000\", \"request_id\": 8, \"updated_at\": \"2026-10-15 10:15:56.000000\", \"vehicle_id\": 146, \"visitor_id\": 68, \"driver_name\": null, \"reviewed_at\": null, \"reviewed_by\": null, \"host_user_id\": null, \"request_status\": \"pending\", \"review_remarks\": null, \"passenger_count\": null, \"vehicle_photo_path\": null, \"destination_area_id\": 6, \"driver_is_requester\": null, \"requested_valid_from\": \"2026-10-29 00:30:00.000000\", \"documents_reviewed_at\": null, \"documents_reviewed_by\": null, \"driver_license_expiry\": null, \"requested_valid_until\": \"2026-10-29 02:20:00.000000\", \"driver_license_encrypted\": null, \"driver_license_photo_path\": null}','2026-10-23 15:29:57.182096'),(156,'visitor_requests','9','baseline',NULL,NULL,'history installation',NULL,NULL,'{\"purpose\": \"Thesis consultation · Visiting: Registrar Office\", \"created_at\": \"2026-10-15 11:33:30.000000\", \"request_id\": 9, \"updated_at\": \"2026-10-15 11:33:51.000000\", \"vehicle_id\": 147, \"visitor_id\": 69, \"driver_name\": null, \"reviewed_at\": \"2026-10-15 11:33:51.000000\", \"reviewed_by\": 46, \"host_user_id\": null, \"request_status\": \"approved\", \"review_remarks\": null, \"passenger_count\": null, \"vehicle_photo_path\": null, \"destination_area_id\": 6, \"driver_is_requester\": null, \"requested_valid_from\": \"2026-10-15 11:23:00.000000\", \"documents_reviewed_at\": null, \"documents_reviewed_by\": null, \"driver_license_expiry\": null, \"requested_valid_until\": \"2026-10-15 14:33:00.000000\", \"driver_license_encrypted\": null, \"driver_license_photo_path\": null}','2026-10-23 15:29:57.182096'),(163,'qr_passes','61','baseline',NULL,NULL,'history installation',NULL,NULL,'{\"user_id\": null, \"max_uses\": 2, \"issued_at\": \"2026-10-13 00:00:00.000000\", \"issued_by\": 1, \"pass_type\": \"visitor\", \"use_count\": 1, \"created_at\": \"2026-10-13 00:00:00.000000\", \"qr_pass_id\": 61, \"request_id\": null, \"revoked_at\": null, \"updated_at\": \"2026-10-14 05:11:58.000000\", \"valid_from\": \"2026-10-13 00:00:00.000000\", \"vehicle_id\": 135, \"visitor_id\": 61, \"pass_status\": \"used\", \"valid_until\": \"2026-10-14 23:59:59.000000\", \"revoke_reason\": null}','2026-10-23 15:29:57.242231'),(164,'qr_passes','62','baseline',NULL,NULL,'history installation',NULL,NULL,'{\"user_id\": null, \"max_uses\": 2, \"issued_at\": \"2026-10-13 00:00:00.000000\", \"issued_by\": 1, \"pass_type\": \"visitor\", \"use_count\": 2, \"created_at\": \"2026-10-13 00:00:00.000000\", \"qr_pass_id\": 62, \"request_id\": null, \"revoked_at\": null, \"updated_at\": \"2026-10-14 05:11:58.000000\", \"valid_from\": \"2026-10-13 00:00:00.000000\", \"vehicle_id\": 136, \"visitor_id\": 62, \"pass_status\": \"used\", \"valid_until\": \"2026-10-14 23:59:59.000000\", \"revoke_reason\": null}','2026-10-23 15:29:57.242231'),(165,'qr_passes','63','baseline',NULL,NULL,'history installation',NULL,NULL,'{\"user_id\": null, \"max_uses\": 2, \"issued_at\": \"2026-10-13 00:00:00.000000\", \"issued_by\": 1, \"pass_type\": \"visitor\", \"use_count\": 1, \"created_at\": \"2026-10-13 00:00:00.000000\", \"qr_pass_id\": 63, \"request_id\": null, \"revoked_at\": null, \"updated_at\": \"2026-10-14 05:11:58.000000\", \"valid_from\": \"2026-10-13 00:00:00.000000\", \"vehicle_id\": 137, \"visitor_id\": 63, \"pass_status\": \"used\", \"valid_until\": \"2026-10-14 23:59:59.000000\", \"revoke_reason\": null}','2026-10-23 15:29:57.242231'),(166,'qr_passes','64','baseline',NULL,NULL,'history installation',NULL,NULL,'{\"user_id\": null, \"max_uses\": 2, \"issued_at\": \"2026-10-13 00:00:00.000000\", \"issued_by\": 1, \"pass_type\": \"visitor\", \"use_count\": 0, \"created_at\": \"2026-10-13 00:00:00.000000\", \"qr_pass_id\": 64, \"request_id\": null, \"revoked_at\": null, \"updated_at\": \"2026-10-13 00:00:00.000000\", \"valid_from\": \"2026-10-12 00:00:00.000000\", \"vehicle_id\": 138, \"visitor_id\": 64, \"pass_status\": \"expired\", \"valid_until\": \"2026-10-13 00:00:00.000000\", \"revoke_reason\": null}','2026-10-23 15:29:57.242231'),(167,'qr_passes','68','baseline',NULL,NULL,'history installation',NULL,NULL,'{\"user_id\": null, \"max_uses\": 2, \"issued_at\": \"2026-10-15 05:20:24.000000\", \"issued_by\": 1, \"pass_type\": \"visitor\", \"use_count\": 2, \"created_at\": \"2026-10-15 05:20:24.000000\", \"qr_pass_id\": 68, \"request_id\": 1, \"revoked_at\": null, \"updated_at\": \"2026-10-15 05:23:46.000000\", \"valid_from\": \"2026-10-15 05:17:00.000000\", \"vehicle_id\": 139, \"visitor_id\": 65, \"pass_status\": \"used\", \"valid_until\": \"2026-10-15 09:17:00.000000\", \"revoke_reason\": null}','2026-10-23 15:29:57.242231'),(168,'qr_passes','69','baseline',NULL,NULL,'history installation',NULL,NULL,'{\"user_id\": null, \"max_uses\": 2, \"issued_at\": \"2026-10-15 05:22:45.000000\", \"issued_by\": 1, \"pass_type\": \"visitor\", \"use_count\": 2, \"created_at\": \"2026-10-15 05:22:45.000000\", \"qr_pass_id\": 69, \"request_id\": 3, \"revoked_at\": null, \"updated_at\": \"2026-10-15 05:23:37.000000\", \"valid_from\": \"2026-10-15 05:22:00.000000\", \"vehicle_id\": 141, \"visitor_id\": 65, \"pass_status\": \"used\", \"valid_until\": \"2026-10-15 09:22:00.000000\", \"revoke_reason\": null}','2026-10-23 15:29:57.242231'),(169,'qr_passes','70','baseline',NULL,NULL,'history installation',NULL,NULL,'{\"user_id\": null, \"max_uses\": 2, \"issued_at\": \"2026-10-15 05:25:31.000000\", \"issued_by\": 1, \"pass_type\": \"visitor\", \"use_count\": 2, \"created_at\": \"2026-10-15 05:25:31.000000\", \"qr_pass_id\": 70, \"request_id\": 4, \"revoked_at\": \"2026-10-15 05:25:47.000000\", \"updated_at\": \"2026-10-15 05:26:20.000000\", \"valid_from\": \"2026-10-15 05:25:00.000000\", \"vehicle_id\": 142, \"visitor_id\": 65, \"pass_status\": \"revoked\", \"valid_until\": \"2026-10-15 09:25:00.000000\", \"revoke_reason\": \"Interview cancelled\"}','2026-10-23 15:29:57.242231'),(170,'qr_passes','71','baseline',NULL,NULL,'history installation',NULL,NULL,'{\"user_id\": null, \"max_uses\": 2, \"issued_at\": \"2026-10-15 08:27:02.000000\", \"issued_by\": 46, \"pass_type\": \"visitor\", \"use_count\": 2, \"created_at\": \"2026-10-15 08:27:02.000000\", \"qr_pass_id\": 71, \"request_id\": 6, \"revoked_at\": null, \"updated_at\": \"2026-10-15 08:29:41.000000\", \"valid_from\": \"2026-10-15 08:14:00.000000\", \"vehicle_id\": 144, \"visitor_id\": 67, \"pass_status\": \"used\", \"valid_until\": \"2026-10-15 11:24:00.000000\", \"revoke_reason\": null}','2026-10-23 15:29:57.242231'),(171,'qr_passes','72','baseline',NULL,NULL,'history installation',NULL,NULL,'{\"user_id\": null, \"max_uses\": 2, \"issued_at\": \"2026-10-15 11:33:51.000000\", \"issued_by\": 46, \"pass_type\": \"visitor\", \"use_count\": 2, \"created_at\": \"2026-10-15 11:33:51.000000\", \"qr_pass_id\": 72, \"request_id\": 9, \"revoked_at\": null, \"updated_at\": \"2026-10-15 11:35:23.000000\", \"valid_from\": \"2026-10-15 11:23:00.000000\", \"vehicle_id\": 147, \"visitor_id\": 69, \"pass_status\": \"used\", \"valid_until\": \"2026-10-15 14:33:00.000000\", \"revoke_reason\": null}','2026-10-23 15:29:57.242231'),(178,'parking_transactions','183','baseline',NULL,NULL,'history installation',NULL,NULL,'{\"remarks\": \"SAMPLE TEST DATA\", \"slot_id\": 1, \"user_id\": 36, \"driver_id\": 48, \"created_at\": \"2026-10-13 07:30:00.000000\", \"updated_at\": \"2026-10-14 00:15:35.000000\", \"vehicle_id\": 55, \"visitor_id\": null, \"recorded_by\": 1, \"access_method\": \"rfid\", \"exit_datetime\": \"2026-10-14 00:15:35.000000\", \"exit_event_id\": 376, \"ticket_number\": \"PT-20261013-LOBV2S\", \"entry_datetime\": \"2026-10-13 07:30:00.000000\", \"entry_event_id\": 370, \"transaction_id\": 183, \"parking_area_id\": 6, \"active_slot_flag\": null, \"duration_minutes\": 1005, \"slot_confirmed_at\": null, \"slot_confirmed_by\": null, \"transaction_status\": \"completed\", \"active_vehicle_flag\": null}','2026-10-23 15:29:57.366047'),(179,'parking_transactions','184','baseline',NULL,NULL,'history installation',NULL,NULL,'{\"remarks\": \"SAMPLE TEST DATA\", \"slot_id\": 2, \"user_id\": 32, \"driver_id\": 44, \"created_at\": \"2026-10-13 07:45:00.000000\", \"updated_at\": \"2026-10-13 17:10:00.000000\", \"vehicle_id\": 51, \"visitor_id\": null, \"recorded_by\": 1, \"access_method\": \"rfid\", \"exit_datetime\": \"2026-10-13 17:10:00.000000\", \"exit_event_id\": 375, \"ticket_number\": \"PT-20261013-JSIORQ\", \"entry_datetime\": \"2026-10-13 07:45:00.000000\", \"entry_event_id\": 371, \"transaction_id\": 184, \"parking_area_id\": 6, \"active_slot_flag\": null, \"duration_minutes\": 565, \"slot_confirmed_at\": null, \"slot_confirmed_by\": null, \"transaction_status\": \"completed\", \"active_vehicle_flag\": null}','2026-10-23 15:29:57.366047'),(180,'parking_transactions','185','baseline',NULL,NULL,'history installation',NULL,NULL,'{\"remarks\": \"SAMPLE TEST DATA\", \"slot_id\": 56, \"user_id\": 35, \"driver_id\": 47, \"created_at\": \"2026-10-13 08:10:00.000000\", \"updated_at\": \"2026-10-13 16:40:00.000000\", \"vehicle_id\": 76, \"visitor_id\": null, \"recorded_by\": 1, \"access_method\": \"rfid\", \"exit_datetime\": \"2026-10-13 16:40:00.000000\", \"exit_event_id\": 374, \"ticket_number\": \"PT-20261013-GMBOSQ\", \"entry_datetime\": \"2026-10-13 08:10:00.000000\", \"entry_event_id\": 372, \"transaction_id\": 185, \"parking_area_id\": 7, \"active_slot_flag\": null, \"duration_minutes\": 510, \"slot_confirmed_at\": null, \"slot_confirmed_by\": null, \"transaction_status\": \"completed\", \"active_vehicle_flag\": null}','2026-10-23 15:29:57.366047'),(181,'parking_transactions','186','baseline',NULL,NULL,'history installation',NULL,NULL,'{\"remarks\": \"SAMPLE TEST DATA\", \"slot_id\": 3, \"user_id\": null, \"driver_id\": null, \"created_at\": \"2026-10-13 15:00:00.000000\", \"updated_at\": \"2026-10-13 15:00:00.000000\", \"vehicle_id\": 137, \"visitor_id\": 63, \"recorded_by\": 1, \"access_method\": \"qr\", \"exit_datetime\": null, \"exit_event_id\": null, \"ticket_number\": \"PT-20261013-UHPOAS\", \"entry_datetime\": \"2026-10-13 15:00:00.000000\", \"entry_event_id\": 373, \"transaction_id\": 186, \"parking_area_id\": 6, \"active_slot_flag\": 1, \"duration_minutes\": null, \"slot_confirmed_at\": null, \"slot_confirmed_by\": null, \"transaction_status\": \"active\", \"active_vehicle_flag\": 1}','2026-10-23 15:29:57.366047'),(182,'parking_transactions','187','baseline',NULL,NULL,'history installation',NULL,NULL,'{\"remarks\": \"SAMPLE TEST DATA\", \"slot_id\": 1, \"user_id\": 32, \"driver_id\": 44, \"created_at\": \"2026-10-14 00:31:11.000000\", \"updated_at\": \"2026-10-14 01:49:11.000000\", \"vehicle_id\": 51, \"visitor_id\": null, \"recorded_by\": 1, \"access_method\": \"rfid\", \"exit_datetime\": \"2026-10-14 01:49:11.000000\", \"exit_event_id\": 380, \"ticket_number\": \"PT-20261014-85VCVE\", \"entry_datetime\": \"2026-10-14 00:31:11.000000\", \"entry_event_id\": 377, \"transaction_id\": 187, \"parking_area_id\": 6, \"active_slot_flag\": null, \"duration_minutes\": 78, \"slot_confirmed_at\": null, \"slot_confirmed_by\": null, \"transaction_status\": \"completed\", \"active_vehicle_flag\": null}','2026-10-23 15:29:57.366047'),(183,'parking_transactions','188','baseline',NULL,NULL,'history installation',NULL,NULL,'{\"remarks\": \"SAMPLE TEST DATA\", \"slot_id\": 2, \"user_id\": null, \"driver_id\": null, \"created_at\": \"2026-10-14 01:02:23.000000\", \"updated_at\": \"2026-10-14 02:35:59.000000\", \"vehicle_id\": 136, \"visitor_id\": 62, \"recorded_by\": 1, \"access_method\": \"qr\", \"exit_datetime\": \"2026-10-14 02:35:59.000000\", \"exit_event_id\": 383, \"ticket_number\": \"PT-20261014-FVP2WD\", \"entry_datetime\": \"2026-10-14 01:02:23.000000\", \"entry_event_id\": 378, \"transaction_id\": 188, \"parking_area_id\": 6, \"active_slot_flag\": null, \"duration_minutes\": 93, \"slot_confirmed_at\": null, \"slot_confirmed_by\": null, \"transaction_status\": \"completed\", \"active_vehicle_flag\": null}','2026-10-23 15:29:57.366047'),(184,'parking_transactions','189','baseline',NULL,NULL,'history installation',NULL,NULL,'{\"remarks\": \"SAMPLE TEST DATA\", \"slot_id\": 1, \"user_id\": 36, \"driver_id\": 48, \"created_at\": \"2026-10-14 02:20:23.000000\", \"updated_at\": \"2026-10-15 05:16:45.000000\", \"vehicle_id\": 55, \"visitor_id\": null, \"recorded_by\": 1, \"access_method\": \"rfid\", \"exit_datetime\": \"2026-10-15 05:16:45.000000\", \"exit_event_id\": 429, \"ticket_number\": \"PT-20261014-URP5QB\", \"entry_datetime\": \"2026-10-14 02:20:23.000000\", \"entry_event_id\": 382, \"transaction_id\": 189, \"parking_area_id\": 6, \"active_slot_flag\": null, \"duration_minutes\": 1616, \"slot_confirmed_at\": \"2026-10-14 05:11:59.000000\", \"slot_confirmed_by\": 1, \"transaction_status\": \"completed\", \"active_vehicle_flag\": null}','2026-10-23 15:29:57.366047'),(185,'parking_transactions','190','baseline',NULL,NULL,'history installation',NULL,NULL,'{\"remarks\": \"SAMPLE TEST DATA\", \"slot_id\": 2, \"user_id\": null, \"driver_id\": null, \"created_at\": \"2026-10-14 02:51:35.000000\", \"updated_at\": \"2026-10-14 05:11:59.000000\", \"vehicle_id\": 135, \"visitor_id\": 61, \"recorded_by\": 1, \"access_method\": \"qr\", \"exit_datetime\": null, \"exit_event_id\": null, \"ticket_number\": \"PT-20261014-P8RPQU\", \"entry_datetime\": \"2026-10-14 02:51:35.000000\", \"entry_event_id\": 384, \"transaction_id\": 190, \"parking_area_id\": 6, \"active_slot_flag\": 1, \"duration_minutes\": null, \"slot_confirmed_at\": \"2026-10-14 05:11:59.000000\", \"slot_confirmed_by\": 1, \"transaction_status\": \"active\", \"active_vehicle_flag\": 1}','2026-10-23 15:29:57.366047'),(186,'parking_transactions','191','baseline',NULL,NULL,'history installation',NULL,NULL,'{\"remarks\": \"SAMPLE TEST DATA\", \"slot_id\": 4, \"user_id\": 32, \"driver_id\": 44, \"created_at\": \"2026-10-14 03:07:11.000000\", \"updated_at\": \"2026-10-15 05:00:55.000000\", \"vehicle_id\": 51, \"visitor_id\": null, \"recorded_by\": 1, \"access_method\": \"rfid\", \"exit_datetime\": \"2026-10-15 05:00:55.000000\", \"exit_event_id\": 422, \"ticket_number\": \"PT-20261014-0IWTXI\", \"entry_datetime\": \"2026-10-14 03:07:11.000000\", \"entry_event_id\": 385, \"transaction_id\": 191, \"parking_area_id\": 6, \"active_slot_flag\": null, \"duration_minutes\": 1553, \"slot_confirmed_at\": null, \"slot_confirmed_by\": null, \"transaction_status\": \"completed\", \"active_vehicle_flag\": null}','2026-10-23 15:29:57.366047'),(187,'parking_transactions','192','baseline',NULL,NULL,'history installation',NULL,NULL,'{\"remarks\": \"SAMPLE TEST DATA\", \"slot_id\": 56, \"user_id\": 35, \"driver_id\": 47, \"created_at\": \"2026-10-14 03:13:25.000000\", \"updated_at\": \"2026-10-15 05:01:39.000000\", \"vehicle_id\": 76, \"visitor_id\": null, \"recorded_by\": 1, \"access_method\": \"rfid\", \"exit_datetime\": \"2026-10-15 05:01:39.000000\", \"exit_event_id\": 425, \"ticket_number\": \"PT-20261014-LNNBAR\", \"entry_datetime\": \"2026-10-14 03:13:25.000000\", \"entry_event_id\": 386, \"transaction_id\": 192, \"parking_area_id\": 7, \"active_slot_flag\": null, \"duration_minutes\": 1548, \"slot_confirmed_at\": \"2026-10-14 05:11:59.000000\", \"slot_confirmed_by\": 1, \"transaction_status\": \"completed\", \"active_vehicle_flag\": null}','2026-10-23 15:29:57.366047'),(188,'parking_transactions','201','baseline',NULL,NULL,'history installation',NULL,NULL,'{\"remarks\": null, \"slot_id\": 4, \"user_id\": 32, \"driver_id\": 44, \"created_at\": \"2026-10-15 05:01:11.000000\", \"updated_at\": \"2026-10-15 17:15:40.000000\", \"vehicle_id\": 51, \"visitor_id\": null, \"recorded_by\": 1, \"access_method\": \"rfid\", \"exit_datetime\": \"2026-10-15 17:15:40.000000\", \"exit_event_id\": 453, \"ticket_number\": \"PT-20261015-ITLLGJ\", \"entry_datetime\": \"2026-10-15 05:01:11.000000\", \"entry_event_id\": 423, \"transaction_id\": 201, \"parking_area_id\": 6, \"active_slot_flag\": null, \"duration_minutes\": 734, \"slot_confirmed_at\": null, \"slot_confirmed_by\": null, \"transaction_status\": \"completed\", \"active_vehicle_flag\": null}','2026-10-23 15:29:57.366047'),(189,'parking_transactions','202','baseline',NULL,NULL,'history installation',NULL,NULL,'{\"remarks\": null, \"slot_id\": 1, \"user_id\": 36, \"driver_id\": 48, \"created_at\": \"2026-10-15 05:16:48.000000\", \"updated_at\": \"2026-10-15 11:04:00.000000\", \"vehicle_id\": 55, \"visitor_id\": null, \"recorded_by\": 1, \"access_method\": \"rfid\", \"exit_datetime\": \"2026-10-15 11:04:00.000000\", \"exit_event_id\": 444, \"ticket_number\": \"PT-20261015-4INHWK\", \"entry_datetime\": \"2026-10-15 05:16:48.000000\", \"entry_event_id\": 430, \"transaction_id\": 202, \"parking_area_id\": 6, \"active_slot_flag\": null, \"duration_minutes\": 347, \"slot_confirmed_at\": null, \"slot_confirmed_by\": null, \"transaction_status\": \"completed\", \"active_vehicle_flag\": null}','2026-10-23 15:29:57.366047'),(190,'parking_transactions','203','baseline',NULL,NULL,'history installation',NULL,NULL,'{\"remarks\": null, \"slot_id\": 5, \"user_id\": null, \"driver_id\": null, \"created_at\": \"2026-10-15 05:21:43.000000\", \"updated_at\": \"2026-10-15 05:23:46.000000\", \"vehicle_id\": 139, \"visitor_id\": 65, \"recorded_by\": 1, \"access_method\": \"qr\", \"exit_datetime\": \"2026-10-15 05:23:46.000000\", \"exit_event_id\": 436, \"ticket_number\": \"PT-20261015-YSWPTH\", \"entry_datetime\": \"2026-10-15 05:21:43.000000\", \"entry_event_id\": 432, \"transaction_id\": 203, \"parking_area_id\": 6, \"active_slot_flag\": null, \"duration_minutes\": 2, \"slot_confirmed_at\": null, \"slot_confirmed_by\": null, \"transaction_status\": \"completed\", \"active_vehicle_flag\": null}','2026-10-23 15:29:57.366047'),(191,'parking_transactions','204','baseline',NULL,NULL,'history installation',NULL,NULL,'{\"remarks\": null, \"slot_id\": 6, \"user_id\": null, \"driver_id\": null, \"created_at\": \"2026-10-15 05:22:53.000000\", \"updated_at\": \"2026-10-15 05:23:37.000000\", \"vehicle_id\": 141, \"visitor_id\": 65, \"recorded_by\": 1, \"access_method\": \"qr\", \"exit_datetime\": \"2026-10-15 05:23:37.000000\", \"exit_event_id\": 434, \"ticket_number\": \"PT-20261015-MNW6XE\", \"entry_datetime\": \"2026-10-15 05:22:53.000000\", \"entry_event_id\": 433, \"transaction_id\": 204, \"parking_area_id\": 6, \"active_slot_flag\": null, \"duration_minutes\": 0, \"slot_confirmed_at\": null, \"slot_confirmed_by\": null, \"transaction_status\": \"completed\", \"active_vehicle_flag\": null}','2026-10-23 15:29:57.366047'),(192,'parking_transactions','205','baseline',NULL,NULL,'history installation',NULL,NULL,'{\"remarks\": null, \"slot_id\": 5, \"user_id\": null, \"driver_id\": null, \"created_at\": \"2026-10-15 05:25:38.000000\", \"updated_at\": \"2026-10-15 05:26:20.000000\", \"vehicle_id\": 142, \"visitor_id\": 65, \"recorded_by\": 1, \"access_method\": \"qr\", \"exit_datetime\": \"2026-10-15 05:26:20.000000\", \"exit_event_id\": 439, \"ticket_number\": \"PT-20261015-UMBQ4E\", \"entry_datetime\": \"2026-10-15 05:25:38.000000\", \"entry_event_id\": 438, \"transaction_id\": 205, \"parking_area_id\": 6, \"active_slot_flag\": null, \"duration_minutes\": 0, \"slot_confirmed_at\": null, \"slot_confirmed_by\": null, \"transaction_status\": \"completed\", \"active_vehicle_flag\": null}','2026-10-23 15:29:57.366047'),(193,'parking_transactions','206','baseline',NULL,NULL,'history installation',NULL,NULL,'{\"remarks\": null, \"slot_id\": 5, \"user_id\": null, \"driver_id\": null, \"created_at\": \"2026-10-15 08:28:50.000000\", \"updated_at\": \"2026-10-15 08:29:41.000000\", \"vehicle_id\": 144, \"visitor_id\": 67, \"recorded_by\": 1, \"access_method\": \"qr\", \"exit_datetime\": \"2026-10-15 08:29:41.000000\", \"exit_event_id\": 442, \"ticket_number\": \"PT-20261015-AV8V2Z\", \"entry_datetime\": \"2026-10-15 08:28:50.000000\", \"entry_event_id\": 441, \"transaction_id\": 206, \"parking_area_id\": 6, \"active_slot_flag\": null, \"duration_minutes\": 0, \"slot_confirmed_at\": null, \"slot_confirmed_by\": null, \"transaction_status\": \"completed\", \"active_vehicle_flag\": null}','2026-10-23 15:29:57.366047'),(194,'parking_transactions','207','baseline',NULL,NULL,'history installation',NULL,NULL,'{\"remarks\": null, \"slot_id\": 1, \"user_id\": 36, \"driver_id\": 48, \"created_at\": \"2026-10-15 11:04:25.000000\", \"updated_at\": \"2026-10-15 11:07:25.000000\", \"vehicle_id\": 55, \"visitor_id\": null, \"recorded_by\": 1, \"access_method\": \"rfid\", \"exit_datetime\": \"2026-10-15 11:07:25.000000\", \"exit_event_id\": 446, \"ticket_number\": \"PT-20261015-FLNOOO\", \"entry_datetime\": \"2026-10-15 11:04:25.000000\", \"entry_event_id\": 445, \"transaction_id\": 207, \"parking_area_id\": 6, \"active_slot_flag\": null, \"duration_minutes\": 3, \"slot_confirmed_at\": null, \"slot_confirmed_by\": null, \"transaction_status\": \"completed\", \"active_vehicle_flag\": null}','2026-10-23 15:29:57.366047'),(195,'parking_transactions','208','baseline',NULL,NULL,'history installation',NULL,NULL,'{\"remarks\": null, \"slot_id\": 1, \"user_id\": 36, \"driver_id\": 48, \"created_at\": \"2026-10-15 11:17:27.000000\", \"updated_at\": \"2026-10-15 11:21:59.000000\", \"vehicle_id\": 55, \"visitor_id\": null, \"recorded_by\": 1, \"access_method\": \"rfid\", \"exit_datetime\": \"2026-10-15 11:21:59.000000\", \"exit_event_id\": 448, \"ticket_number\": \"PT-20261015-AER0O6\", \"entry_datetime\": \"2026-10-15 11:17:27.000000\", \"entry_event_id\": 447, \"transaction_id\": 208, \"parking_area_id\": 6, \"active_slot_flag\": null, \"duration_minutes\": 4, \"slot_confirmed_at\": null, \"slot_confirmed_by\": null, \"transaction_status\": \"completed\", \"active_vehicle_flag\": null}','2026-10-23 15:29:57.366047'),(196,'parking_transactions','209','baseline',NULL,NULL,'history installation',NULL,NULL,'{\"remarks\": null, \"slot_id\": 1, \"user_id\": 36, \"driver_id\": 48, \"created_at\": \"2026-10-15 11:22:24.000000\", \"updated_at\": \"2026-10-15 11:22:24.000000\", \"vehicle_id\": 55, \"visitor_id\": null, \"recorded_by\": 1, \"access_method\": \"rfid\", \"exit_datetime\": null, \"exit_event_id\": null, \"ticket_number\": \"PT-20261015-VPBGQS\", \"entry_datetime\": \"2026-10-15 11:22:24.000000\", \"entry_event_id\": 449, \"transaction_id\": 209, \"parking_area_id\": 6, \"active_slot_flag\": 1, \"duration_minutes\": null, \"slot_confirmed_at\": null, \"slot_confirmed_by\": null, \"transaction_status\": \"active\", \"active_vehicle_flag\": 1}','2026-10-23 15:29:57.366047'),(197,'parking_transactions','210','baseline',NULL,NULL,'history installation',NULL,NULL,'{\"remarks\": null, \"slot_id\": 5, \"user_id\": null, \"driver_id\": null, \"created_at\": \"2026-10-15 11:35:22.000000\", \"updated_at\": \"2026-10-15 11:35:23.000000\", \"vehicle_id\": 147, \"visitor_id\": 69, \"recorded_by\": 1, \"access_method\": \"qr\", \"exit_datetime\": \"2026-10-15 11:35:23.000000\", \"exit_event_id\": 451, \"ticket_number\": \"PT-20261015-G0CBZW\", \"entry_datetime\": \"2026-10-15 11:35:22.000000\", \"entry_event_id\": 450, \"transaction_id\": 210, \"parking_area_id\": 6, \"active_slot_flag\": null, \"duration_minutes\": 0, \"slot_confirmed_at\": null, \"slot_confirmed_by\": null, \"transaction_status\": \"completed\", \"active_vehicle_flag\": null}','2026-10-23 15:29:57.366047'),(209,'access_events','370','baseline',NULL,NULL,'history installation',NULL,NULL,'{\"gate_id\": 8, \"rfid_id\": 4, \"user_id\": 36, \"event_id\": 370, \"direction\": \"in\", \"reader_id\": null, \"created_at\": \"2026-10-13 07:30:00.000000\", \"qr_pass_id\": null, \"updated_at\": \"2026-10-13 07:30:00.000000\", \"vehicle_id\": 55, \"visitor_id\": null, \"auth_result\": \"granted\", \"access_method\": \"rfid\", \"denial_reason\": null, \"event_datetime\": \"2026-10-13 07:30:00.000000\", \"rfid_assignment_id\": 4, \"processed_by_user_id\": 1}','2026-10-23 15:29:57.435409'),(210,'access_events','371','baseline',NULL,NULL,'history installation',NULL,NULL,'{\"gate_id\": 8, \"rfid_id\": 1, \"user_id\": 32, \"event_id\": 371, \"direction\": \"in\", \"reader_id\": null, \"created_at\": \"2026-10-13 07:45:00.000000\", \"qr_pass_id\": null, \"updated_at\": \"2026-10-13 07:45:00.000000\", \"vehicle_id\": 51, \"visitor_id\": null, \"auth_result\": \"granted\", \"access_method\": \"rfid\", \"denial_reason\": null, \"event_datetime\": \"2026-10-13 07:45:00.000000\", \"rfid_assignment_id\": 1, \"processed_by_user_id\": 1}','2026-10-23 15:29:57.435409'),(211,'access_events','372','baseline',NULL,NULL,'history installation',NULL,NULL,'{\"gate_id\": 8, \"rfid_id\": 3, \"user_id\": 35, \"event_id\": 372, \"direction\": \"in\", \"reader_id\": null, \"created_at\": \"2026-10-13 08:10:00.000000\", \"qr_pass_id\": null, \"updated_at\": \"2026-10-13 08:10:00.000000\", \"vehicle_id\": 76, \"visitor_id\": null, \"auth_result\": \"granted\", \"access_method\": \"rfid\", \"denial_reason\": null, \"event_datetime\": \"2026-10-13 08:10:00.000000\", \"rfid_assignment_id\": 3, \"processed_by_user_id\": 1}','2026-10-23 15:29:57.435409'),(212,'access_events','373','baseline',NULL,NULL,'history installation',NULL,NULL,'{\"gate_id\": 8, \"rfid_id\": null, \"user_id\": null, \"event_id\": 373, \"direction\": \"in\", \"reader_id\": null, \"created_at\": \"2026-10-13 15:00:00.000000\", \"qr_pass_id\": 63, \"updated_at\": \"2026-10-13 15:00:00.000000\", \"vehicle_id\": 137, \"visitor_id\": 63, \"auth_result\": \"granted\", \"access_method\": \"qr\", \"denial_reason\": null, \"event_datetime\": \"2026-10-13 15:00:00.000000\", \"rfid_assignment_id\": null, \"processed_by_user_id\": 1}','2026-10-23 15:29:57.435409'),(213,'access_events','374','baseline',NULL,NULL,'history installation',NULL,NULL,'{\"gate_id\": 8, \"rfid_id\": 3, \"user_id\": 35, \"event_id\": 374, \"direction\": \"out\", \"reader_id\": null, \"created_at\": \"2026-10-13 16:40:00.000000\", \"qr_pass_id\": null, \"updated_at\": \"2026-10-13 16:40:00.000000\", \"vehicle_id\": 76, \"visitor_id\": null, \"auth_result\": \"granted\", \"access_method\": \"rfid\", \"denial_reason\": null, \"event_datetime\": \"2026-10-13 16:40:00.000000\", \"rfid_assignment_id\": 3, \"processed_by_user_id\": 1}','2026-10-23 15:29:57.435409'),(214,'access_events','375','baseline',NULL,NULL,'history installation',NULL,NULL,'{\"gate_id\": 8, \"rfid_id\": 1, \"user_id\": 32, \"event_id\": 375, \"direction\": \"out\", \"reader_id\": null, \"created_at\": \"2026-10-13 17:10:00.000000\", \"qr_pass_id\": null, \"updated_at\": \"2026-10-13 17:10:00.000000\", \"vehicle_id\": 51, \"visitor_id\": null, \"auth_result\": \"granted\", \"access_method\": \"rfid\", \"denial_reason\": null, \"event_datetime\": \"2026-10-13 17:10:00.000000\", \"rfid_assignment_id\": 1, \"processed_by_user_id\": 1}','2026-10-23 15:29:57.435409'),(215,'access_events','376','baseline',NULL,NULL,'history installation',NULL,NULL,'{\"gate_id\": 8, \"rfid_id\": 4, \"user_id\": 36, \"event_id\": 376, \"direction\": \"out\", \"reader_id\": null, \"created_at\": \"2026-10-14 00:15:35.000000\", \"qr_pass_id\": null, \"updated_at\": \"2026-10-14 00:15:35.000000\", \"vehicle_id\": 55, \"visitor_id\": null, \"auth_result\": \"granted\", \"access_method\": \"rfid\", \"denial_reason\": null, \"event_datetime\": \"2026-10-14 00:15:35.000000\", \"rfid_assignment_id\": 4, \"processed_by_user_id\": 1}','2026-10-23 15:29:57.435409'),(216,'access_events','377','baseline',NULL,NULL,'history installation',NULL,NULL,'{\"gate_id\": 8, \"rfid_id\": 1, \"user_id\": 32, \"event_id\": 377, \"direction\": \"in\", \"reader_id\": null, \"created_at\": \"2026-10-14 00:31:11.000000\", \"qr_pass_id\": null, \"updated_at\": \"2026-10-14 00:31:11.000000\", \"vehicle_id\": 51, \"visitor_id\": null, \"auth_result\": \"granted\", \"access_method\": \"rfid\", \"denial_reason\": null, \"event_datetime\": \"2026-10-14 00:31:11.000000\", \"rfid_assignment_id\": 1, \"processed_by_user_id\": 1}','2026-10-23 15:29:57.435409'),(217,'access_events','378','baseline',NULL,NULL,'history installation',NULL,NULL,'{\"gate_id\": 8, \"rfid_id\": null, \"user_id\": null, \"event_id\": 378, \"direction\": \"in\", \"reader_id\": null, \"created_at\": \"2026-10-14 01:02:23.000000\", \"qr_pass_id\": 62, \"updated_at\": \"2026-10-14 01:02:23.000000\", \"vehicle_id\": 136, \"visitor_id\": 62, \"auth_result\": \"granted\", \"access_method\": \"qr\", \"denial_reason\": null, \"event_datetime\": \"2026-10-14 01:02:23.000000\", \"rfid_assignment_id\": null, \"processed_by_user_id\": 1}','2026-10-23 15:29:57.435409'),(218,'access_events','379','baseline',NULL,NULL,'history installation',NULL,NULL,'{\"gate_id\": 8, \"rfid_id\": null, \"user_id\": 33, \"event_id\": 379, \"direction\": \"in\", \"reader_id\": null, \"created_at\": \"2026-10-14 01:33:35.000000\", \"qr_pass_id\": null, \"updated_at\": \"2026-10-14 01:33:35.000000\", \"vehicle_id\": 52, \"visitor_id\": null, \"auth_result\": \"denied\", \"access_method\": \"manual\", \"denial_reason\": \"Vehicle authorization is pending review.\", \"event_datetime\": \"2026-10-14 01:33:35.000000\", \"rfid_assignment_id\": null, \"processed_by_user_id\": 1}','2026-10-23 15:29:57.435409'),(219,'access_events','380','baseline',NULL,NULL,'history installation',NULL,NULL,'{\"gate_id\": 8, \"rfid_id\": 1, \"user_id\": 32, \"event_id\": 380, \"direction\": \"out\", \"reader_id\": null, \"created_at\": \"2026-10-14 01:49:11.000000\", \"qr_pass_id\": null, \"updated_at\": \"2026-10-14 01:49:11.000000\", \"vehicle_id\": 51, \"visitor_id\": null, \"auth_result\": \"granted\", \"access_method\": \"rfid\", \"denial_reason\": null, \"event_datetime\": \"2026-10-14 01:49:11.000000\", \"rfid_assignment_id\": 1, \"processed_by_user_id\": 1}','2026-10-23 15:29:57.435409'),(220,'access_events','381','baseline',NULL,NULL,'history installation',NULL,NULL,'{\"gate_id\": 8, \"rfid_id\": 2, \"user_id\": 34, \"event_id\": 381, \"direction\": \"in\", \"reader_id\": null, \"created_at\": \"2026-10-14 02:04:47.000000\", \"qr_pass_id\": null, \"updated_at\": \"2026-10-14 02:04:47.000000\", \"vehicle_id\": 53, \"visitor_id\": null, \"auth_result\": \"denied\", \"access_method\": \"rfid\", \"denial_reason\": \"Vehicle authorization is suspended.\", \"event_datetime\": \"2026-10-14 02:04:47.000000\", \"rfid_assignment_id\": 2, \"processed_by_user_id\": 1}','2026-10-23 15:29:57.435409'),(221,'access_events','382','baseline',NULL,NULL,'history installation',NULL,NULL,'{\"gate_id\": 8, \"rfid_id\": 4, \"user_id\": 36, \"event_id\": 382, \"direction\": \"in\", \"reader_id\": null, \"created_at\": \"2026-10-14 02:20:23.000000\", \"qr_pass_id\": null, \"updated_at\": \"2026-10-14 02:20:23.000000\", \"vehicle_id\": 55, \"visitor_id\": null, \"auth_result\": \"granted\", \"access_method\": \"rfid\", \"denial_reason\": null, \"event_datetime\": \"2026-10-14 02:20:23.000000\", \"rfid_assignment_id\": 4, \"processed_by_user_id\": 1}','2026-10-23 15:29:57.435409'),(222,'access_events','383','baseline',NULL,NULL,'history installation',NULL,NULL,'{\"gate_id\": 8, \"rfid_id\": null, \"user_id\": null, \"event_id\": 383, \"direction\": \"out\", \"reader_id\": null, \"created_at\": \"2026-10-14 02:35:59.000000\", \"qr_pass_id\": 62, \"updated_at\": \"2026-10-14 02:35:59.000000\", \"vehicle_id\": 136, \"visitor_id\": 62, \"auth_result\": \"granted\", \"access_method\": \"qr\", \"denial_reason\": null, \"event_datetime\": \"2026-10-14 02:35:59.000000\", \"rfid_assignment_id\": null, \"processed_by_user_id\": 1}','2026-10-23 15:29:57.435409'),(223,'access_events','384','baseline',NULL,NULL,'history installation',NULL,NULL,'{\"gate_id\": 8, \"rfid_id\": null, \"user_id\": null, \"event_id\": 384, \"direction\": \"in\", \"reader_id\": null, \"created_at\": \"2026-10-14 02:51:35.000000\", \"qr_pass_id\": 61, \"updated_at\": \"2026-10-14 02:51:35.000000\", \"vehicle_id\": 135, \"visitor_id\": 61, \"auth_result\": \"granted\", \"access_method\": \"qr\", \"denial_reason\": null, \"event_datetime\": \"2026-10-14 02:51:35.000000\", \"rfid_assignment_id\": null, \"processed_by_user_id\": 1}','2026-10-23 15:29:57.435409'),(224,'access_events','385','baseline',NULL,NULL,'history installation',NULL,NULL,'{\"gate_id\": 8, \"rfid_id\": 1, \"user_id\": 32, \"event_id\": 385, \"direction\": \"in\", \"reader_id\": null, \"created_at\": \"2026-10-14 03:07:11.000000\", \"qr_pass_id\": null, \"updated_at\": \"2026-10-14 03:07:11.000000\", \"vehicle_id\": 51, \"visitor_id\": null, \"auth_result\": \"granted\", \"access_method\": \"rfid\", \"denial_reason\": null, \"event_datetime\": \"2026-10-14 03:07:11.000000\", \"rfid_assignment_id\": 1, \"processed_by_user_id\": 1}','2026-10-23 15:29:57.435409'),(225,'access_events','386','baseline',NULL,NULL,'history installation',NULL,NULL,'{\"gate_id\": 8, \"rfid_id\": 3, \"user_id\": 35, \"event_id\": 386, \"direction\": \"in\", \"reader_id\": null, \"created_at\": \"2026-10-14 03:13:25.000000\", \"qr_pass_id\": null, \"updated_at\": \"2026-10-14 03:13:25.000000\", \"vehicle_id\": 76, \"visitor_id\": null, \"auth_result\": \"granted\", \"access_method\": \"rfid\", \"denial_reason\": null, \"event_datetime\": \"2026-10-14 03:13:25.000000\", \"rfid_assignment_id\": 3, \"processed_by_user_id\": 1}','2026-10-23 15:29:57.435409'),(226,'access_events','387','baseline',NULL,NULL,'history installation',NULL,NULL,'{\"gate_id\": 8, \"rfid_id\": null, \"user_id\": 35, \"event_id\": 387, \"direction\": \"in\", \"reader_id\": null, \"created_at\": \"2026-10-14 03:22:47.000000\", \"qr_pass_id\": null, \"updated_at\": \"2026-10-14 03:22:47.000000\", \"vehicle_id\": 54, \"visitor_id\": null, \"auth_result\": \"denied\", \"access_method\": \"manual\", \"denial_reason\": \"Vehicle authorization was rejected.\", \"event_datetime\": \"2026-10-14 03:22:47.000000\", \"rfid_assignment_id\": null, \"processed_by_user_id\": 1}','2026-10-23 15:29:57.435409'),(227,'access_events','388','baseline',NULL,NULL,'history installation',NULL,NULL,'{\"gate_id\": 8, \"rfid_id\": null, \"user_id\": null, \"event_id\": 388, \"direction\": \"in\", \"reader_id\": null, \"created_at\": \"2026-10-14 03:38:23.000000\", \"qr_pass_id\": 64, \"updated_at\": \"2026-10-14 03:38:23.000000\", \"vehicle_id\": 138, \"visitor_id\": 64, \"auth_result\": \"denied\", \"access_method\": \"qr\", \"denial_reason\": \"Visitor QR pass has expired.\", \"event_datetime\": \"2026-10-14 03:38:23.000000\", \"rfid_assignment_id\": null, \"processed_by_user_id\": 1}','2026-10-23 15:29:57.435409'),(228,'access_events','422','baseline',NULL,NULL,'history installation',NULL,NULL,'{\"gate_id\": 8, \"rfid_id\": 1, \"user_id\": 32, \"event_id\": 422, \"direction\": \"out\", \"reader_id\": null, \"created_at\": \"2026-10-15 05:00:55.000000\", \"qr_pass_id\": null, \"updated_at\": \"2026-10-15 05:00:55.000000\", \"vehicle_id\": 51, \"visitor_id\": null, \"auth_result\": \"granted\", \"access_method\": \"rfid\", \"denial_reason\": null, \"event_datetime\": \"2026-10-15 05:00:55.000000\", \"rfid_assignment_id\": 1, \"processed_by_user_id\": 1}','2026-10-23 15:29:57.435409'),(229,'access_events','423','baseline',NULL,NULL,'history installation',NULL,NULL,'{\"gate_id\": 8, \"rfid_id\": 1, \"user_id\": 32, \"event_id\": 423, \"direction\": \"in\", \"reader_id\": null, \"created_at\": \"2026-10-15 05:01:11.000000\", \"qr_pass_id\": null, \"updated_at\": \"2026-10-15 05:01:11.000000\", \"vehicle_id\": 51, \"visitor_id\": null, \"auth_result\": \"granted\", \"access_method\": \"rfid\", \"denial_reason\": null, \"event_datetime\": \"2026-10-15 05:01:11.000000\", \"rfid_assignment_id\": 1, \"processed_by_user_id\": 1}','2026-10-23 15:29:57.435409'),(230,'access_events','424','baseline',NULL,NULL,'history installation',NULL,NULL,'{\"gate_id\": 8, \"rfid_id\": null, \"user_id\": null, \"event_id\": 424, \"direction\": \"in\", \"reader_id\": null, \"created_at\": \"2026-10-15 05:01:31.000000\", \"qr_pass_id\": null, \"updated_at\": \"2026-10-15 05:01:31.000000\", \"vehicle_id\": null, \"visitor_id\": null, \"auth_result\": \"denied\", \"access_method\": \"rfid\", \"denial_reason\": \"Unknown RFID tag.\", \"event_datetime\": \"2026-10-15 05:01:31.000000\", \"rfid_assignment_id\": null, \"processed_by_user_id\": 1}','2026-10-23 15:29:57.435409'),(231,'access_events','425','baseline',NULL,NULL,'history installation',NULL,NULL,'{\"gate_id\": 8, \"rfid_id\": 3, \"user_id\": 35, \"event_id\": 425, \"direction\": \"out\", \"reader_id\": null, \"created_at\": \"2026-10-15 05:01:39.000000\", \"qr_pass_id\": null, \"updated_at\": \"2026-10-15 05:01:39.000000\", \"vehicle_id\": 76, \"visitor_id\": null, \"auth_result\": \"granted\", \"access_method\": \"rfid\", \"denial_reason\": null, \"event_datetime\": \"2026-10-15 05:01:39.000000\", \"rfid_assignment_id\": 3, \"processed_by_user_id\": 1}','2026-10-23 15:29:57.435409'),(232,'access_events','426','baseline',NULL,NULL,'history installation',NULL,NULL,'{\"gate_id\": 8, \"rfid_id\": 3, \"user_id\": 35, \"event_id\": 426, \"direction\": \"in\", \"reader_id\": null, \"created_at\": \"2026-10-15 05:01:47.000000\", \"qr_pass_id\": null, \"updated_at\": \"2026-10-15 05:01:47.000000\", \"vehicle_id\": 76, \"visitor_id\": null, \"auth_result\": \"denied\", \"access_method\": \"rfid\", \"denial_reason\": \"Driver\'s license is waiting for staff verification.\", \"event_datetime\": \"2026-10-15 05:01:47.000000\", \"rfid_assignment_id\": 3, \"processed_by_user_id\": 1}','2026-10-23 15:29:57.435409'),(233,'access_events','427','baseline',NULL,NULL,'history installation',NULL,NULL,'{\"gate_id\": 8, \"rfid_id\": null, \"user_id\": 35, \"event_id\": 427, \"direction\": \"in\", \"reader_id\": null, \"created_at\": \"2026-10-15 05:02:11.000000\", \"qr_pass_id\": null, \"updated_at\": \"2026-10-15 05:02:11.000000\", \"vehicle_id\": 76, \"visitor_id\": null, \"auth_result\": \"denied\", \"access_method\": \"manual\", \"denial_reason\": \"Driver\'s license is waiting for staff verification.\", \"event_datetime\": \"2026-10-15 05:02:11.000000\", \"rfid_assignment_id\": null, \"processed_by_user_id\": 1}','2026-10-23 15:29:57.435409'),(234,'access_events','428','baseline',NULL,NULL,'history installation',NULL,NULL,'{\"gate_id\": 8, \"rfid_id\": 2, \"user_id\": 34, \"event_id\": 428, \"direction\": \"in\", \"reader_id\": null, \"created_at\": \"2026-10-15 05:02:19.000000\", \"qr_pass_id\": null, \"updated_at\": \"2026-10-15 05:02:19.000000\", \"vehicle_id\": 53, \"visitor_id\": null, \"auth_result\": \"denied\", \"access_method\": \"rfid\", \"denial_reason\": \"Vehicle authorization is suspended.\", \"event_datetime\": \"2026-10-15 05:02:19.000000\", \"rfid_assignment_id\": 2, \"processed_by_user_id\": 1}','2026-10-23 15:29:57.435409'),(235,'access_events','429','baseline',NULL,NULL,'history installation',NULL,NULL,'{\"gate_id\": 8, \"rfid_id\": 4, \"user_id\": 36, \"event_id\": 429, \"direction\": \"out\", \"reader_id\": null, \"created_at\": \"2026-10-15 05:16:45.000000\", \"qr_pass_id\": null, \"updated_at\": \"2026-10-15 05:16:45.000000\", \"vehicle_id\": 55, \"visitor_id\": null, \"auth_result\": \"granted\", \"access_method\": \"rfid\", \"denial_reason\": null, \"event_datetime\": \"2026-10-15 05:16:45.000000\", \"rfid_assignment_id\": 4, \"processed_by_user_id\": 1}','2026-10-23 15:29:57.435409'),(236,'access_events','430','baseline',NULL,NULL,'history installation',NULL,NULL,'{\"gate_id\": 8, \"rfid_id\": 4, \"user_id\": 36, \"event_id\": 430, \"direction\": \"in\", \"reader_id\": null, \"created_at\": \"2026-10-15 05:16:48.000000\", \"qr_pass_id\": null, \"updated_at\": \"2026-10-15 05:16:48.000000\", \"vehicle_id\": 55, \"visitor_id\": null, \"auth_result\": \"granted\", \"access_method\": \"rfid\", \"denial_reason\": null, \"event_datetime\": \"2026-10-15 05:16:48.000000\", \"rfid_assignment_id\": 4, \"processed_by_user_id\": 1}','2026-10-23 15:29:57.435409'),(237,'access_events','431','baseline',NULL,NULL,'history installation',NULL,NULL,'{\"gate_id\": 8, \"rfid_id\": null, \"user_id\": null, \"event_id\": 431, \"direction\": \"in\", \"reader_id\": null, \"created_at\": \"2026-10-15 05:16:51.000000\", \"qr_pass_id\": null, \"updated_at\": \"2026-10-15 05:16:51.000000\", \"vehicle_id\": null, \"visitor_id\": null, \"auth_result\": \"denied\", \"access_method\": \"rfid\", \"denial_reason\": \"Unknown RFID tag.\", \"event_datetime\": \"2026-10-15 05:16:51.000000\", \"rfid_assignment_id\": null, \"processed_by_user_id\": 1}','2026-10-23 15:29:57.435409'),(238,'access_events','432','baseline',NULL,NULL,'history installation',NULL,NULL,'{\"gate_id\": 8, \"rfid_id\": null, \"user_id\": null, \"event_id\": 432, \"direction\": \"in\", \"reader_id\": null, \"created_at\": \"2026-10-15 05:21:43.000000\", \"qr_pass_id\": 68, \"updated_at\": \"2026-10-15 05:21:43.000000\", \"vehicle_id\": 139, \"visitor_id\": 65, \"auth_result\": \"granted\", \"access_method\": \"qr\", \"denial_reason\": null, \"event_datetime\": \"2026-10-15 05:21:43.000000\", \"rfid_assignment_id\": null, \"processed_by_user_id\": 1}','2026-10-23 15:29:57.435409'),(239,'access_events','433','baseline',NULL,NULL,'history installation',NULL,NULL,'{\"gate_id\": 8, \"rfid_id\": null, \"user_id\": null, \"event_id\": 433, \"direction\": \"in\", \"reader_id\": null, \"created_at\": \"2026-10-15 05:22:53.000000\", \"qr_pass_id\": 69, \"updated_at\": \"2026-10-15 05:22:53.000000\", \"vehicle_id\": 141, \"visitor_id\": 65, \"auth_result\": \"granted\", \"access_method\": \"qr\", \"denial_reason\": null, \"event_datetime\": \"2026-10-15 05:22:53.000000\", \"rfid_assignment_id\": null, \"processed_by_user_id\": 1}','2026-10-23 15:29:57.435409'),(240,'access_events','434','baseline',NULL,NULL,'history installation',NULL,NULL,'{\"gate_id\": 8, \"rfid_id\": null, \"user_id\": null, \"event_id\": 434, \"direction\": \"out\", \"reader_id\": null, \"created_at\": \"2026-10-15 05:23:37.000000\", \"qr_pass_id\": 69, \"updated_at\": \"2026-10-15 05:23:37.000000\", \"vehicle_id\": 141, \"visitor_id\": 65, \"auth_result\": \"granted\", \"access_method\": \"qr\", \"denial_reason\": null, \"event_datetime\": \"2026-10-15 05:23:37.000000\", \"rfid_assignment_id\": null, \"processed_by_user_id\": 1}','2026-10-23 15:29:57.435409'),(241,'access_events','435','baseline',NULL,NULL,'history installation',NULL,NULL,'{\"gate_id\": 8, \"rfid_id\": null, \"user_id\": null, \"event_id\": 435, \"direction\": \"in\", \"reader_id\": null, \"created_at\": \"2026-10-15 05:23:40.000000\", \"qr_pass_id\": 69, \"updated_at\": \"2026-10-15 05:23:40.000000\", \"vehicle_id\": 141, \"visitor_id\": 65, \"auth_result\": \"denied\", \"access_method\": \"qr\", \"denial_reason\": \"Visitor QR pass has already been used.\", \"event_datetime\": \"2026-10-15 05:23:40.000000\", \"rfid_assignment_id\": null, \"processed_by_user_id\": 1}','2026-10-23 15:29:57.435409'),(242,'access_events','436','baseline',NULL,NULL,'history installation',NULL,NULL,'{\"gate_id\": 8, \"rfid_id\": null, \"user_id\": null, \"event_id\": 436, \"direction\": \"out\", \"reader_id\": null, \"created_at\": \"2026-10-15 05:23:46.000000\", \"qr_pass_id\": 68, \"updated_at\": \"2026-10-15 05:23:46.000000\", \"vehicle_id\": 139, \"visitor_id\": 65, \"auth_result\": \"granted\", \"access_method\": \"qr\", \"denial_reason\": null, \"event_datetime\": \"2026-10-15 05:23:46.000000\", \"rfid_assignment_id\": null, \"processed_by_user_id\": 1}','2026-10-23 15:29:57.435409'),(243,'access_events','437','baseline',NULL,NULL,'history installation',NULL,NULL,'{\"gate_id\": 8, \"rfid_id\": null, \"user_id\": null, \"event_id\": 437, \"direction\": \"in\", \"reader_id\": null, \"created_at\": \"2026-10-15 05:23:49.000000\", \"qr_pass_id\": 68, \"updated_at\": \"2026-10-15 05:23:49.000000\", \"vehicle_id\": 139, \"visitor_id\": 65, \"auth_result\": \"denied\", \"access_method\": \"qr\", \"denial_reason\": \"Visitor QR pass has already been used.\", \"event_datetime\": \"2026-10-15 05:23:49.000000\", \"rfid_assignment_id\": null, \"processed_by_user_id\": 1}','2026-10-23 15:29:57.435409'),(244,'access_events','438','baseline',NULL,NULL,'history installation',NULL,NULL,'{\"gate_id\": 8, \"rfid_id\": null, \"user_id\": null, \"event_id\": 438, \"direction\": \"in\", \"reader_id\": null, \"created_at\": \"2026-10-15 05:25:38.000000\", \"qr_pass_id\": 70, \"updated_at\": \"2026-10-15 05:25:38.000000\", \"vehicle_id\": 142, \"visitor_id\": 65, \"auth_result\": \"granted\", \"access_method\": \"qr\", \"denial_reason\": null, \"event_datetime\": \"2026-10-15 05:25:38.000000\", \"rfid_assignment_id\": null, \"processed_by_user_id\": 1}','2026-10-23 15:29:57.435409'),(245,'access_events','439','baseline',NULL,NULL,'history installation',NULL,NULL,'{\"gate_id\": 8, \"rfid_id\": null, \"user_id\": null, \"event_id\": 439, \"direction\": \"out\", \"reader_id\": null, \"created_at\": \"2026-10-15 05:26:20.000000\", \"qr_pass_id\": 70, \"updated_at\": \"2026-10-15 05:26:20.000000\", \"vehicle_id\": 142, \"visitor_id\": 65, \"auth_result\": \"granted\", \"access_method\": \"qr\", \"denial_reason\": null, \"event_datetime\": \"2026-10-15 05:26:20.000000\", \"rfid_assignment_id\": null, \"processed_by_user_id\": 1}','2026-10-23 15:29:57.435409'),(246,'access_events','440','baseline',NULL,NULL,'history installation',NULL,NULL,'{\"gate_id\": 8, \"rfid_id\": null, \"user_id\": null, \"event_id\": 440, \"direction\": \"in\", \"reader_id\": null, \"created_at\": \"2026-10-15 05:26:24.000000\", \"qr_pass_id\": 70, \"updated_at\": \"2026-10-15 05:26:24.000000\", \"vehicle_id\": 142, \"visitor_id\": 65, \"auth_result\": \"denied\", \"access_method\": \"qr\", \"denial_reason\": \"Visitor QR pass was revoked.\", \"event_datetime\": \"2026-10-15 05:26:24.000000\", \"rfid_assignment_id\": null, \"processed_by_user_id\": 1}','2026-10-23 15:29:57.435409'),(247,'access_events','441','baseline',NULL,NULL,'history installation',NULL,NULL,'{\"gate_id\": 8, \"rfid_id\": null, \"user_id\": null, \"event_id\": 441, \"direction\": \"in\", \"reader_id\": null, \"created_at\": \"2026-10-15 08:28:50.000000\", \"qr_pass_id\": 71, \"updated_at\": \"2026-10-15 08:28:50.000000\", \"vehicle_id\": 144, \"visitor_id\": 67, \"auth_result\": \"granted\", \"access_method\": \"qr\", \"denial_reason\": null, \"event_datetime\": \"2026-10-15 08:28:50.000000\", \"rfid_assignment_id\": null, \"processed_by_user_id\": 1}','2026-10-23 15:29:57.435409'),(248,'access_events','442','baseline',NULL,NULL,'history installation',NULL,NULL,'{\"gate_id\": 8, \"rfid_id\": null, \"user_id\": null, \"event_id\": 442, \"direction\": \"out\", \"reader_id\": null, \"created_at\": \"2026-10-15 08:29:41.000000\", \"qr_pass_id\": 71, \"updated_at\": \"2026-10-15 08:29:41.000000\", \"vehicle_id\": 144, \"visitor_id\": 67, \"auth_result\": \"granted\", \"access_method\": \"qr\", \"denial_reason\": null, \"event_datetime\": \"2026-10-15 08:29:41.000000\", \"rfid_assignment_id\": null, \"processed_by_user_id\": 1}','2026-10-23 15:29:57.435409'),(249,'access_events','443','baseline',NULL,NULL,'history installation',NULL,NULL,'{\"gate_id\": 8, \"rfid_id\": null, \"user_id\": null, \"event_id\": 443, \"direction\": \"in\", \"reader_id\": null, \"created_at\": \"2026-10-15 08:29:51.000000\", \"qr_pass_id\": 71, \"updated_at\": \"2026-10-15 08:29:51.000000\", \"vehicle_id\": 144, \"visitor_id\": 67, \"auth_result\": \"denied\", \"access_method\": \"qr\", \"denial_reason\": \"Visitor QR pass has already been used.\", \"event_datetime\": \"2026-10-15 08:29:51.000000\", \"rfid_assignment_id\": null, \"processed_by_user_id\": 1}','2026-10-23 15:29:57.435409'),(250,'access_events','444','baseline',NULL,NULL,'history installation',NULL,NULL,'{\"gate_id\": 8, \"rfid_id\": 4, \"user_id\": 36, \"event_id\": 444, \"direction\": \"out\", \"reader_id\": null, \"created_at\": \"2026-10-15 11:04:00.000000\", \"qr_pass_id\": null, \"updated_at\": \"2026-10-15 11:04:00.000000\", \"vehicle_id\": 55, \"visitor_id\": null, \"auth_result\": \"granted\", \"access_method\": \"rfid\", \"denial_reason\": null, \"event_datetime\": \"2026-10-15 11:04:00.000000\", \"rfid_assignment_id\": 4, \"processed_by_user_id\": 1}','2026-10-23 15:29:57.435409'),(251,'access_events','445','baseline',NULL,NULL,'history installation',NULL,NULL,'{\"gate_id\": 8, \"rfid_id\": 4, \"user_id\": 36, \"event_id\": 445, \"direction\": \"in\", \"reader_id\": null, \"created_at\": \"2026-10-15 11:04:25.000000\", \"qr_pass_id\": null, \"updated_at\": \"2026-10-15 11:04:25.000000\", \"vehicle_id\": 55, \"visitor_id\": null, \"auth_result\": \"granted\", \"access_method\": \"rfid\", \"denial_reason\": null, \"event_datetime\": \"2026-10-15 11:04:25.000000\", \"rfid_assignment_id\": 4, \"processed_by_user_id\": 1}','2026-10-23 15:29:57.435409'),(252,'access_events','446','baseline',NULL,NULL,'history installation',NULL,NULL,'{\"gate_id\": 8, \"rfid_id\": 4, \"user_id\": 36, \"event_id\": 446, \"direction\": \"out\", \"reader_id\": null, \"created_at\": \"2026-10-15 11:07:25.000000\", \"qr_pass_id\": null, \"updated_at\": \"2026-10-15 11:07:25.000000\", \"vehicle_id\": 55, \"visitor_id\": null, \"auth_result\": \"granted\", \"access_method\": \"rfid\", \"denial_reason\": null, \"event_datetime\": \"2026-10-15 11:07:25.000000\", \"rfid_assignment_id\": 4, \"processed_by_user_id\": 1}','2026-10-23 15:29:57.435409'),(253,'access_events','447','baseline',NULL,NULL,'history installation',NULL,NULL,'{\"gate_id\": 8, \"rfid_id\": 4, \"user_id\": 36, \"event_id\": 447, \"direction\": \"in\", \"reader_id\": null, \"created_at\": \"2026-10-15 11:17:27.000000\", \"qr_pass_id\": null, \"updated_at\": \"2026-10-15 11:17:27.000000\", \"vehicle_id\": 55, \"visitor_id\": null, \"auth_result\": \"granted\", \"access_method\": \"rfid\", \"denial_reason\": null, \"event_datetime\": \"2026-10-15 11:17:27.000000\", \"rfid_assignment_id\": 4, \"processed_by_user_id\": 1}','2026-10-23 15:29:57.435409'),(254,'access_events','448','baseline',NULL,NULL,'history installation',NULL,NULL,'{\"gate_id\": 8, \"rfid_id\": 4, \"user_id\": 36, \"event_id\": 448, \"direction\": \"out\", \"reader_id\": null, \"created_at\": \"2026-10-15 11:21:59.000000\", \"qr_pass_id\": null, \"updated_at\": \"2026-10-15 11:21:59.000000\", \"vehicle_id\": 55, \"visitor_id\": null, \"auth_result\": \"granted\", \"access_method\": \"rfid\", \"denial_reason\": null, \"event_datetime\": \"2026-10-15 11:21:59.000000\", \"rfid_assignment_id\": 4, \"processed_by_user_id\": 1}','2026-10-23 15:29:57.435409'),(255,'access_events','449','baseline',NULL,NULL,'history installation',NULL,NULL,'{\"gate_id\": 8, \"rfid_id\": 4, \"user_id\": 36, \"event_id\": 449, \"direction\": \"in\", \"reader_id\": null, \"created_at\": \"2026-10-15 11:22:24.000000\", \"qr_pass_id\": null, \"updated_at\": \"2026-10-15 11:22:24.000000\", \"vehicle_id\": 55, \"visitor_id\": null, \"auth_result\": \"granted\", \"access_method\": \"rfid\", \"denial_reason\": null, \"event_datetime\": \"2026-10-15 11:22:24.000000\", \"rfid_assignment_id\": 4, \"processed_by_user_id\": 1}','2026-10-23 15:29:57.435409'),(256,'access_events','450','baseline',NULL,NULL,'history installation',NULL,NULL,'{\"gate_id\": 8, \"rfid_id\": null, \"user_id\": null, \"event_id\": 450, \"direction\": \"in\", \"reader_id\": null, \"created_at\": \"2026-10-15 11:35:22.000000\", \"qr_pass_id\": 72, \"updated_at\": \"2026-10-15 11:35:22.000000\", \"vehicle_id\": 147, \"visitor_id\": 69, \"auth_result\": \"granted\", \"access_method\": \"qr\", \"denial_reason\": null, \"event_datetime\": \"2026-10-15 11:35:22.000000\", \"rfid_assignment_id\": null, \"processed_by_user_id\": 1}','2026-10-23 15:29:57.435409'),(257,'access_events','451','baseline',NULL,NULL,'history installation',NULL,NULL,'{\"gate_id\": 8, \"rfid_id\": null, \"user_id\": null, \"event_id\": 451, \"direction\": \"out\", \"reader_id\": null, \"created_at\": \"2026-10-15 11:35:23.000000\", \"qr_pass_id\": 72, \"updated_at\": \"2026-10-15 11:35:23.000000\", \"vehicle_id\": 147, \"visitor_id\": 69, \"auth_result\": \"granted\", \"access_method\": \"qr\", \"denial_reason\": null, \"event_datetime\": \"2026-10-15 11:35:23.000000\", \"rfid_assignment_id\": null, \"processed_by_user_id\": 1}','2026-10-23 15:29:57.435409'),(258,'access_events','452','baseline',NULL,NULL,'history installation',NULL,NULL,'{\"gate_id\": 8, \"rfid_id\": null, \"user_id\": null, \"event_id\": 452, \"direction\": \"in\", \"reader_id\": null, \"created_at\": \"2026-10-15 11:35:24.000000\", \"qr_pass_id\": 72, \"updated_at\": \"2026-10-15 11:35:24.000000\", \"vehicle_id\": 147, \"visitor_id\": 69, \"auth_result\": \"denied\", \"access_method\": \"qr\", \"denial_reason\": \"Visitor QR pass has already been used.\", \"event_datetime\": \"2026-10-15 11:35:24.000000\", \"rfid_assignment_id\": null, \"processed_by_user_id\": 1}','2026-10-23 15:29:57.435409'),(259,'access_events','453','baseline',NULL,NULL,'history installation',NULL,NULL,'{\"gate_id\": 8, \"rfid_id\": 1, \"user_id\": 32, \"event_id\": 453, \"direction\": \"out\", \"reader_id\": null, \"created_at\": \"2026-10-15 17:15:40.000000\", \"qr_pass_id\": null, \"updated_at\": \"2026-10-15 17:15:40.000000\", \"vehicle_id\": 51, \"visitor_id\": null, \"auth_result\": \"granted\", \"access_method\": \"rfid\", \"denial_reason\": null, \"event_datetime\": \"2026-10-15 17:15:40.000000\", \"rfid_assignment_id\": 1, \"processed_by_user_id\": 1}','2026-10-23 15:29:57.435409'),(272,'parking_violations','20','baseline',NULL,NULL,'history installation',NULL,NULL,'{\"slot_id\": 20, \"user_id\": 32, \"created_at\": \"2026-10-14 05:11:59.000000\", \"updated_at\": \"2026-10-14 05:11:59.000000\", \"vehicle_id\": 51, \"visitor_id\": null, \"description\": \"Vehicle SMP 1001 was assigned to C-04 but was detected at C-20.\", \"occurred_at\": \"2026-10-14 05:11:59.000000\", \"reported_at\": \"2026-10-14 05:11:59.000000\", \"reported_by\": 1, \"resolved_at\": null, \"resolved_by\": null, \"violation_id\": 20, \"penalty_amount\": null, \"parking_area_id\": 6, \"expected_slot_id\": 4, \"violation_status\": \"open\", \"violation_type_id\": 1, \"resolution_remarks\": null, \"parking_transaction_id\": 191}','2026-10-23 15:29:57.501593'),(273,'parking_violations','21','baseline',NULL,NULL,'history installation',NULL,NULL,'{\"slot_id\": 30, \"user_id\": 35, \"created_at\": \"2026-10-14 05:11:59.000000\", \"updated_at\": \"2026-10-14 05:11:59.000000\", \"vehicle_id\": 76, \"visitor_id\": null, \"description\": \"Vehicle SMP 2004 was assigned to M-01 in the Motorcycle Parking Area but was detected at C-30 in the Car Parking Area.\", \"occurred_at\": \"2026-10-14 05:11:59.000000\", \"reported_at\": \"2026-10-14 05:11:59.000000\", \"reported_by\": 1, \"resolved_at\": \"2026-10-14 05:11:59.000000\", \"resolved_by\": 1, \"violation_id\": 21, \"penalty_amount\": null, \"parking_area_id\": 6, \"expected_slot_id\": 56, \"violation_status\": \"resolved\", \"violation_type_id\": 2, \"resolution_remarks\": \"Vehicle moved back to its assigned slot (confirmed). SAMPLE\", \"parking_transaction_id\": 192}','2026-10-23 15:29:57.501593'),(274,'parking_violations','26','baseline',NULL,NULL,'history installation',NULL,NULL,'{\"slot_id\": null, \"user_id\": 32, \"created_at\": \"2026-10-15 17:18:59.000000\", \"updated_at\": \"2026-10-15 17:19:02.000000\", \"vehicle_id\": 51, \"visitor_id\": null, \"description\": \"[QA test] Violations module verification - safe to ignore\", \"occurred_at\": \"2026-10-15 17:18:59.000000\", \"reported_at\": \"2026-10-15 17:18:59.000000\", \"reported_by\": 1, \"resolved_at\": \"2026-10-15 17:19:02.000000\", \"resolved_by\": 1, \"violation_id\": 26, \"penalty_amount\": null, \"parking_area_id\": null, \"expected_slot_id\": null, \"violation_status\": \"resolved\", \"violation_type_id\": 3, \"resolution_remarks\": \"QA verification complete\", \"parking_transaction_id\": null}','2026-10-23 15:29:57.501593'),(275,'parking_violations','27','baseline',NULL,NULL,'history installation',NULL,NULL,'{\"slot_id\": null, \"user_id\": 32, \"created_at\": \"2026-10-15 17:19:04.000000\", \"updated_at\": \"2026-10-15 17:19:06.000000\", \"vehicle_id\": 51, \"visitor_id\": null, \"description\": \"[QA test] dismiss-path verification - safe to ignore\", \"occurred_at\": \"2026-10-15 17:19:04.000000\", \"reported_at\": \"2026-10-15 17:19:04.000000\", \"reported_by\": 1, \"resolved_at\": \"2026-10-15 17:19:06.000000\", \"resolved_by\": 1, \"violation_id\": 27, \"penalty_amount\": null, \"parking_area_id\": null, \"expected_slot_id\": null, \"violation_status\": \"dismissed\", \"violation_type_id\": 3, \"resolution_remarks\": \"QA test record\", \"parking_transaction_id\": null}','2026-10-23 15:29:57.501593'),(279,'parking_areas','6','baseline',NULL,NULL,'history installation',NULL,NULL,'{\"building\": null, \"latitude\": null, \"area_code\": \"CAR\", \"area_name\": \"Car Parking Area\", \"is_active\": 1, \"longitude\": null, \"description\": \"Cars, SUVs, vans and pickups\", \"floor_level\": null, \"parking_area_id\": 6}','2026-10-23 15:29:57.622683'),(280,'parking_areas','7','baseline',NULL,NULL,'history installation',NULL,NULL,'{\"building\": null, \"latitude\": null, \"area_code\": \"MC\", \"area_name\": \"Motorcycle Parking Area\", \"is_active\": 1, \"longitude\": null, \"description\": \"Motorcycles only\", \"floor_level\": null, \"parking_area_id\": 7}','2026-10-23 15:29:57.622683'),(282,'parking_zones','1','baseline',NULL,NULL,'history installation',NULL,NULL,'{\"zone_id\": 1, \"is_active\": 1, \"zone_code\": \"C\", \"zone_name\": \"Car Slots\", \"created_at\": null, \"updated_at\": \"2026-10-15 00:56:54.000000\", \"layout_width\": 11, \"layout_height\": 5, \"parking_area_id\": 6, \"vehicle_type_id\": null, \"declared_capacity\": 55}','2026-10-23 15:29:57.670212'),(283,'parking_zones','2','baseline',NULL,NULL,'history installation',NULL,NULL,'{\"zone_id\": 2, \"is_active\": 1, \"zone_code\": \"M\", \"zone_name\": \"Motorcycle Slots\", \"created_at\": null, \"updated_at\": \"2026-10-15 00:56:54.000000\", \"layout_width\": 12, \"layout_height\": 7, \"parking_area_id\": 7, \"vehicle_type_id\": 6, \"declared_capacity\": 84}','2026-10-23 15:29:57.670212'),(285,'parking_slots','1','baseline',NULL,NULL,'history installation',NULL,NULL,'{\"pos_x\": 3349, \"pos_y\": 493, \"slot_id\": 1, \"zone_id\": 1, \"slot_code\": \"C-01\", \"created_at\": \"2026-10-14 03:53:01.000000\", \"slot_width\": 170, \"updated_at\": \"2026-10-14 03:53:01.000000\", \"is_pwd_slot\": 0, \"slot_height\": 240, \"slot_status\": \"available\", \"rotation_deg\": 9, \"vehicle_type_id\": null, \"reserved_for_user_id\": null}','2026-10-23 15:29:57.726526'),(286,'parking_slots','2','baseline',NULL,NULL,'history installation',NULL,NULL,'{\"pos_x\": 3524, \"pos_y\": 521, \"slot_id\": 2, \"zone_id\": 1, \"slot_code\": \"C-02\", \"created_at\": \"2026-10-14 03:53:01.000000\", \"slot_width\": 170, \"updated_at\": \"2026-10-14 03:53:01.000000\", \"is_pwd_slot\": 0, \"slot_height\": 240, \"slot_status\": \"available\", \"rotation_deg\": 9, \"vehicle_type_id\": null, \"reserved_for_user_id\": null}','2026-10-23 15:29:57.726526'),(287,'parking_slots','3','baseline',NULL,NULL,'history installation',NULL,NULL,'{\"pos_x\": 3700, \"pos_y\": 549, \"slot_id\": 3, \"zone_id\": 1, \"slot_code\": \"C-03\", \"created_at\": \"2026-10-14 03:53:01.000000\", \"slot_width\": 170, \"updated_at\": \"2026-10-14 03:53:01.000000\", \"is_pwd_slot\": 0, \"slot_height\": 240, \"slot_status\": \"available\", \"rotation_deg\": 9, \"vehicle_type_id\": null, \"reserved_for_user_id\": null}','2026-10-23 15:29:57.726526'),(288,'parking_slots','4','baseline',NULL,NULL,'history installation',NULL,NULL,'{\"pos_x\": 3875, \"pos_y\": 577, \"slot_id\": 4, \"zone_id\": 1, \"slot_code\": \"C-04\", \"created_at\": \"2026-10-14 03:53:01.000000\", \"slot_width\": 170, \"updated_at\": \"2026-10-14 03:53:01.000000\", \"is_pwd_slot\": 0, \"slot_height\": 240, \"slot_status\": \"available\", \"rotation_deg\": 9, \"vehicle_type_id\": null, \"reserved_for_user_id\": null}','2026-10-23 15:29:57.726526'),(289,'parking_slots','5','baseline',NULL,NULL,'history installation',NULL,NULL,'{\"pos_x\": 4051, \"pos_y\": 604, \"slot_id\": 5, \"zone_id\": 1, \"slot_code\": \"C-05\", \"created_at\": \"2026-10-14 03:53:01.000000\", \"slot_width\": 170, \"updated_at\": \"2026-10-15 02:52:06.000000\", \"is_pwd_slot\": 0, \"slot_height\": 240, \"slot_status\": \"available\", \"rotation_deg\": 9, \"vehicle_type_id\": null, \"reserved_for_user_id\": null}','2026-10-23 15:29:57.726526'),(290,'parking_slots','6','baseline',NULL,NULL,'history installation',NULL,NULL,'{\"pos_x\": 4226, \"pos_y\": 632, \"slot_id\": 6, \"zone_id\": 1, \"slot_code\": \"C-06\", \"created_at\": \"2026-10-14 03:53:01.000000\", \"slot_width\": 170, \"updated_at\": \"2026-10-14 05:10:40.000000\", \"is_pwd_slot\": 0, \"slot_height\": 240, \"slot_status\": \"available\", \"rotation_deg\": 9, \"vehicle_type_id\": null, \"reserved_for_user_id\": null}','2026-10-23 15:29:57.726526'),(291,'parking_slots','7','baseline',NULL,NULL,'history installation',NULL,NULL,'{\"pos_x\": 4401, \"pos_y\": 660, \"slot_id\": 7, \"zone_id\": 1, \"slot_code\": \"C-07\", \"created_at\": \"2026-10-14 03:53:01.000000\", \"slot_width\": 170, \"updated_at\": \"2026-10-14 03:53:01.000000\", \"is_pwd_slot\": 0, \"slot_height\": 240, \"slot_status\": \"available\", \"rotation_deg\": 9, \"vehicle_type_id\": null, \"reserved_for_user_id\": null}','2026-10-23 15:29:57.726526'),(292,'parking_slots','8','baseline',NULL,NULL,'history installation',NULL,NULL,'{\"pos_x\": 4577, \"pos_y\": 688, \"slot_id\": 8, \"zone_id\": 1, \"slot_code\": \"C-08\", \"created_at\": \"2026-10-14 03:53:01.000000\", \"slot_width\": 170, \"updated_at\": \"2026-10-14 03:53:01.000000\", \"is_pwd_slot\": 0, \"slot_height\": 240, \"slot_status\": \"available\", \"rotation_deg\": 9, \"vehicle_type_id\": null, \"reserved_for_user_id\": null}','2026-10-23 15:29:57.726526'),(293,'parking_slots','9','baseline',NULL,NULL,'history installation',NULL,NULL,'{\"pos_x\": 4752, \"pos_y\": 716, \"slot_id\": 9, \"zone_id\": 1, \"slot_code\": \"C-09\", \"created_at\": \"2026-10-14 03:53:01.000000\", \"slot_width\": 170, \"updated_at\": \"2026-10-14 03:53:01.000000\", \"is_pwd_slot\": 0, \"slot_height\": 240, \"slot_status\": \"available\", \"rotation_deg\": 9, \"vehicle_type_id\": null, \"reserved_for_user_id\": null}','2026-10-23 15:29:57.726526'),(294,'parking_slots','10','baseline',NULL,NULL,'history installation',NULL,NULL,'{\"pos_x\": 4928, \"pos_y\": 744, \"slot_id\": 10, \"zone_id\": 1, \"slot_code\": \"C-10\", \"created_at\": \"2026-10-14 03:53:01.000000\", \"slot_width\": 170, \"updated_at\": \"2026-10-14 03:53:01.000000\", \"is_pwd_slot\": 0, \"slot_height\": 240, \"slot_status\": \"available\", \"rotation_deg\": 9, \"vehicle_type_id\": null, \"reserved_for_user_id\": null}','2026-10-23 15:29:57.726526'),(295,'parking_slots','11','baseline',NULL,NULL,'history installation',NULL,NULL,'{\"pos_x\": 5103, \"pos_y\": 772, \"slot_id\": 11, \"zone_id\": 1, \"slot_code\": \"C-11\", \"created_at\": \"2026-10-14 03:53:01.000000\", \"slot_width\": 170, \"updated_at\": \"2026-10-14 03:53:01.000000\", \"is_pwd_slot\": 0, \"slot_height\": 240, \"slot_status\": \"available\", \"rotation_deg\": 9, \"vehicle_type_id\": null, \"reserved_for_user_id\": null}','2026-10-23 15:29:57.726526'),(296,'parking_slots','12','baseline',NULL,NULL,'history installation',NULL,NULL,'{\"pos_x\": 5278, \"pos_y\": 799, \"slot_id\": 12, \"zone_id\": 1, \"slot_code\": \"C-12\", \"created_at\": \"2026-10-14 03:53:01.000000\", \"slot_width\": 170, \"updated_at\": \"2026-10-14 03:53:01.000000\", \"is_pwd_slot\": 0, \"slot_height\": 240, \"slot_status\": \"available\", \"rotation_deg\": 9, \"vehicle_type_id\": null, \"reserved_for_user_id\": null}','2026-10-23 15:29:57.726526'),(297,'parking_slots','13','baseline',NULL,NULL,'history installation',NULL,NULL,'{\"pos_x\": 5454, \"pos_y\": 827, \"slot_id\": 13, \"zone_id\": 1, \"slot_code\": \"C-13\", \"created_at\": \"2026-10-14 03:53:01.000000\", \"slot_width\": 170, \"updated_at\": \"2026-10-14 03:53:01.000000\", \"is_pwd_slot\": 0, \"slot_height\": 240, \"slot_status\": \"available\", \"rotation_deg\": 9, \"vehicle_type_id\": null, \"reserved_for_user_id\": null}','2026-10-23 15:29:57.726526'),(298,'parking_slots','14','baseline',NULL,NULL,'history installation',NULL,NULL,'{\"pos_x\": 5629, \"pos_y\": 855, \"slot_id\": 14, \"zone_id\": 1, \"slot_code\": \"C-14\", \"created_at\": \"2026-10-14 03:53:01.000000\", \"slot_width\": 170, \"updated_at\": \"2026-10-14 03:53:01.000000\", \"is_pwd_slot\": 0, \"slot_height\": 240, \"slot_status\": \"available\", \"rotation_deg\": 9, \"vehicle_type_id\": null, \"reserved_for_user_id\": null}','2026-10-23 15:29:57.726526'),(299,'parking_slots','15','baseline',NULL,NULL,'history installation',NULL,NULL,'{\"pos_x\": 5805, \"pos_y\": 883, \"slot_id\": 15, \"zone_id\": 1, \"slot_code\": \"C-15\", \"created_at\": \"2026-10-14 03:53:01.000000\", \"slot_width\": 170, \"updated_at\": \"2026-10-14 03:53:01.000000\", \"is_pwd_slot\": 0, \"slot_height\": 240, \"slot_status\": \"available\", \"rotation_deg\": 9, \"vehicle_type_id\": null, \"reserved_for_user_id\": null}','2026-10-23 15:29:57.726526'),(300,'parking_slots','16','baseline',NULL,NULL,'history installation',NULL,NULL,'{\"pos_x\": 5980, \"pos_y\": 911, \"slot_id\": 16, \"zone_id\": 1, \"slot_code\": \"C-16\", \"created_at\": \"2026-10-14 03:53:01.000000\", \"slot_width\": 170, \"updated_at\": \"2026-10-14 03:53:01.000000\", \"is_pwd_slot\": 0, \"slot_height\": 240, \"slot_status\": \"available\", \"rotation_deg\": 9, \"vehicle_type_id\": null, \"reserved_for_user_id\": null}','2026-10-23 15:29:57.726526'),(301,'parking_slots','17','baseline',NULL,NULL,'history installation',NULL,NULL,'{\"pos_x\": 6155, \"pos_y\": 939, \"slot_id\": 17, \"zone_id\": 1, \"slot_code\": \"C-17\", \"created_at\": \"2026-10-14 03:53:01.000000\", \"slot_width\": 170, \"updated_at\": \"2026-10-14 03:53:01.000000\", \"is_pwd_slot\": 0, \"slot_height\": 240, \"slot_status\": \"available\", \"rotation_deg\": 9, \"vehicle_type_id\": null, \"reserved_for_user_id\": null}','2026-10-23 15:29:57.726526'),(302,'parking_slots','18','baseline',NULL,NULL,'history installation',NULL,NULL,'{\"pos_x\": 6331, \"pos_y\": 966, \"slot_id\": 18, \"zone_id\": 1, \"slot_code\": \"C-18\", \"created_at\": \"2026-10-14 03:53:01.000000\", \"slot_width\": 170, \"updated_at\": \"2026-10-14 03:53:01.000000\", \"is_pwd_slot\": 0, \"slot_height\": 240, \"slot_status\": \"available\", \"rotation_deg\": 9, \"vehicle_type_id\": null, \"reserved_for_user_id\": null}','2026-10-23 15:29:57.726526'),(303,'parking_slots','19','baseline',NULL,NULL,'history installation',NULL,NULL,'{\"pos_x\": 6506, \"pos_y\": 994, \"slot_id\": 19, \"zone_id\": 1, \"slot_code\": \"C-19\", \"created_at\": \"2026-10-14 03:53:01.000000\", \"slot_width\": 170, \"updated_at\": \"2026-10-14 03:53:01.000000\", \"is_pwd_slot\": 0, \"slot_height\": 240, \"slot_status\": \"available\", \"rotation_deg\": 9, \"vehicle_type_id\": null, \"reserved_for_user_id\": null}','2026-10-23 15:29:57.726526'),(304,'parking_slots','20','baseline',NULL,NULL,'history installation',NULL,NULL,'{\"pos_x\": 6682, \"pos_y\": 1022, \"slot_id\": 20, \"zone_id\": 1, \"slot_code\": \"C-20\", \"created_at\": \"2026-10-14 03:53:01.000000\", \"slot_width\": 170, \"updated_at\": \"2026-10-14 03:53:01.000000\", \"is_pwd_slot\": 0, \"slot_height\": 240, \"slot_status\": \"available\", \"rotation_deg\": 9, \"vehicle_type_id\": null, \"reserved_for_user_id\": null}','2026-10-23 15:29:57.726526'),(305,'parking_slots','21','baseline',NULL,NULL,'history installation',NULL,NULL,'{\"pos_x\": 6857, \"pos_y\": 1050, \"slot_id\": 21, \"zone_id\": 1, \"slot_code\": \"C-21\", \"created_at\": \"2026-10-14 03:53:01.000000\", \"slot_width\": 170, \"updated_at\": \"2026-10-14 03:53:01.000000\", \"is_pwd_slot\": 0, \"slot_height\": 240, \"slot_status\": \"available\", \"rotation_deg\": 9, \"vehicle_type_id\": null, \"reserved_for_user_id\": null}','2026-10-23 15:29:57.726526'),(306,'parking_slots','22','baseline',NULL,NULL,'history installation',NULL,NULL,'{\"pos_x\": 3557, \"pos_y\": 1064, \"slot_id\": 22, \"zone_id\": 1, \"slot_code\": \"C-22\", \"created_at\": \"2026-10-14 03:53:01.000000\", \"slot_width\": 172, \"updated_at\": \"2026-10-14 03:53:01.000000\", \"is_pwd_slot\": 0, \"slot_height\": 240, \"slot_status\": \"available\", \"rotation_deg\": 10, \"vehicle_type_id\": null, \"reserved_for_user_id\": null}','2026-10-23 15:29:57.726526'),(307,'parking_slots','23','baseline',NULL,NULL,'history installation',NULL,NULL,'{\"pos_x\": 3736, \"pos_y\": 1096, \"slot_id\": 23, \"zone_id\": 1, \"slot_code\": \"C-23\", \"created_at\": \"2026-10-14 03:53:01.000000\", \"slot_width\": 172, \"updated_at\": \"2026-10-14 03:53:01.000000\", \"is_pwd_slot\": 0, \"slot_height\": 240, \"slot_status\": \"available\", \"rotation_deg\": 10, \"vehicle_type_id\": null, \"reserved_for_user_id\": null}','2026-10-23 15:29:57.726526'),(308,'parking_slots','24','baseline',NULL,NULL,'history installation',NULL,NULL,'{\"pos_x\": 3914, \"pos_y\": 1129, \"slot_id\": 24, \"zone_id\": 1, \"slot_code\": \"C-24\", \"created_at\": \"2026-10-14 03:53:01.000000\", \"slot_width\": 172, \"updated_at\": \"2026-10-14 03:53:01.000000\", \"is_pwd_slot\": 0, \"slot_height\": 240, \"slot_status\": \"available\", \"rotation_deg\": 10, \"vehicle_type_id\": null, \"reserved_for_user_id\": null}','2026-10-23 15:29:57.726526'),(309,'parking_slots','25','baseline',NULL,NULL,'history installation',NULL,NULL,'{\"pos_x\": 4093, \"pos_y\": 1161, \"slot_id\": 25, \"zone_id\": 1, \"slot_code\": \"C-25\", \"created_at\": \"2026-10-14 03:53:01.000000\", \"slot_width\": 172, \"updated_at\": \"2026-10-14 03:53:01.000000\", \"is_pwd_slot\": 0, \"slot_height\": 240, \"slot_status\": \"available\", \"rotation_deg\": 10, \"vehicle_type_id\": null, \"reserved_for_user_id\": null}','2026-10-23 15:29:57.726526'),(310,'parking_slots','26','baseline',NULL,NULL,'history installation',NULL,NULL,'{\"pos_x\": 4271, \"pos_y\": 1193, \"slot_id\": 26, \"zone_id\": 1, \"slot_code\": \"C-26\", \"created_at\": \"2026-10-14 03:53:01.000000\", \"slot_width\": 172, \"updated_at\": \"2026-10-14 03:53:01.000000\", \"is_pwd_slot\": 0, \"slot_height\": 240, \"slot_status\": \"available\", \"rotation_deg\": 10, \"vehicle_type_id\": null, \"reserved_for_user_id\": null}','2026-10-23 15:29:57.726526'),(311,'parking_slots','27','baseline',NULL,NULL,'history installation',NULL,NULL,'{\"pos_x\": 4450, \"pos_y\": 1226, \"slot_id\": 27, \"zone_id\": 1, \"slot_code\": \"C-27\", \"created_at\": \"2026-10-14 03:53:01.000000\", \"slot_width\": 172, \"updated_at\": \"2026-10-14 03:53:01.000000\", \"is_pwd_slot\": 0, \"slot_height\": 240, \"slot_status\": \"available\", \"rotation_deg\": 10, \"vehicle_type_id\": null, \"reserved_for_user_id\": null}','2026-10-23 15:29:57.726526'),(312,'parking_slots','28','baseline',NULL,NULL,'history installation',NULL,NULL,'{\"pos_x\": 4628, \"pos_y\": 1258, \"slot_id\": 28, \"zone_id\": 1, \"slot_code\": \"C-28\", \"created_at\": \"2026-10-14 03:53:01.000000\", \"slot_width\": 172, \"updated_at\": \"2026-10-14 03:53:01.000000\", \"is_pwd_slot\": 0, \"slot_height\": 240, \"slot_status\": \"available\", \"rotation_deg\": 10, \"vehicle_type_id\": null, \"reserved_for_user_id\": null}','2026-10-23 15:29:57.726526'),(313,'parking_slots','29','baseline',NULL,NULL,'history installation',NULL,NULL,'{\"pos_x\": 4807, \"pos_y\": 1291, \"slot_id\": 29, \"zone_id\": 1, \"slot_code\": \"C-29\", \"created_at\": \"2026-10-14 03:53:01.000000\", \"slot_width\": 172, \"updated_at\": \"2026-10-14 03:53:01.000000\", \"is_pwd_slot\": 0, \"slot_height\": 240, \"slot_status\": \"available\", \"rotation_deg\": 10, \"vehicle_type_id\": null, \"reserved_for_user_id\": null}','2026-10-23 15:29:57.726526'),(314,'parking_slots','30','baseline',NULL,NULL,'history installation',NULL,NULL,'{\"pos_x\": 4986, \"pos_y\": 1323, \"slot_id\": 30, \"zone_id\": 1, \"slot_code\": \"C-30\", \"created_at\": \"2026-10-14 03:53:01.000000\", \"slot_width\": 172, \"updated_at\": \"2026-10-14 03:53:01.000000\", \"is_pwd_slot\": 0, \"slot_height\": 240, \"slot_status\": \"available\", \"rotation_deg\": 10, \"vehicle_type_id\": null, \"reserved_for_user_id\": null}','2026-10-23 15:29:57.726526'),(315,'parking_slots','31','baseline',NULL,NULL,'history installation',NULL,NULL,'{\"pos_x\": 5164, \"pos_y\": 1355, \"slot_id\": 31, \"zone_id\": 1, \"slot_code\": \"C-31\", \"created_at\": \"2026-10-14 03:53:01.000000\", \"slot_width\": 172, \"updated_at\": \"2026-10-14 03:53:01.000000\", \"is_pwd_slot\": 0, \"slot_height\": 240, \"slot_status\": \"available\", \"rotation_deg\": 10, \"vehicle_type_id\": null, \"reserved_for_user_id\": null}','2026-10-23 15:29:57.726526'),(316,'parking_slots','32','baseline',NULL,NULL,'history installation',NULL,NULL,'{\"pos_x\": 5343, \"pos_y\": 1388, \"slot_id\": 32, \"zone_id\": 1, \"slot_code\": \"C-32\", \"created_at\": \"2026-10-14 03:53:01.000000\", \"slot_width\": 172, \"updated_at\": \"2026-10-14 03:53:01.000000\", \"is_pwd_slot\": 0, \"slot_height\": 240, \"slot_status\": \"available\", \"rotation_deg\": 10, \"vehicle_type_id\": null, \"reserved_for_user_id\": null}','2026-10-23 15:29:57.726526'),(317,'parking_slots','33','baseline',NULL,NULL,'history installation',NULL,NULL,'{\"pos_x\": 5521, \"pos_y\": 1420, \"slot_id\": 33, \"zone_id\": 1, \"slot_code\": \"C-33\", \"created_at\": \"2026-10-14 03:53:01.000000\", \"slot_width\": 172, \"updated_at\": \"2026-10-14 03:53:01.000000\", \"is_pwd_slot\": 0, \"slot_height\": 240, \"slot_status\": \"available\", \"rotation_deg\": 10, \"vehicle_type_id\": null, \"reserved_for_user_id\": null}','2026-10-23 15:29:57.726526'),(318,'parking_slots','34','baseline',NULL,NULL,'history installation',NULL,NULL,'{\"pos_x\": 5700, \"pos_y\": 1452, \"slot_id\": 34, \"zone_id\": 1, \"slot_code\": \"C-34\", \"created_at\": \"2026-10-14 03:53:01.000000\", \"slot_width\": 172, \"updated_at\": \"2026-10-14 03:53:01.000000\", \"is_pwd_slot\": 0, \"slot_height\": 240, \"slot_status\": \"available\", \"rotation_deg\": 10, \"vehicle_type_id\": null, \"reserved_for_user_id\": null}','2026-10-23 15:29:57.726526'),(319,'parking_slots','35','baseline',NULL,NULL,'history installation',NULL,NULL,'{\"pos_x\": 5879, \"pos_y\": 1485, \"slot_id\": 35, \"zone_id\": 1, \"slot_code\": \"C-35\", \"created_at\": \"2026-10-14 03:53:01.000000\", \"slot_width\": 172, \"updated_at\": \"2026-10-14 03:53:01.000000\", \"is_pwd_slot\": 0, \"slot_height\": 240, \"slot_status\": \"available\", \"rotation_deg\": 10, \"vehicle_type_id\": null, \"reserved_for_user_id\": null}','2026-10-23 15:29:57.726526'),(320,'parking_slots','36','baseline',NULL,NULL,'history installation',NULL,NULL,'{\"pos_x\": 6057, \"pos_y\": 1517, \"slot_id\": 36, \"zone_id\": 1, \"slot_code\": \"C-36\", \"created_at\": \"2026-10-14 03:53:01.000000\", \"slot_width\": 172, \"updated_at\": \"2026-10-14 03:53:01.000000\", \"is_pwd_slot\": 0, \"slot_height\": 240, \"slot_status\": \"available\", \"rotation_deg\": 10, \"vehicle_type_id\": null, \"reserved_for_user_id\": null}','2026-10-23 15:29:57.726526'),(321,'parking_slots','37','baseline',NULL,NULL,'history installation',NULL,NULL,'{\"pos_x\": 6236, \"pos_y\": 1550, \"slot_id\": 37, \"zone_id\": 1, \"slot_code\": \"C-37\", \"created_at\": \"2026-10-14 03:53:01.000000\", \"slot_width\": 172, \"updated_at\": \"2026-10-14 03:53:01.000000\", \"is_pwd_slot\": 0, \"slot_height\": 240, \"slot_status\": \"available\", \"rotation_deg\": 10, \"vehicle_type_id\": null, \"reserved_for_user_id\": null}','2026-10-23 15:29:57.726526'),(322,'parking_slots','38','baseline',NULL,NULL,'history installation',NULL,NULL,'{\"pos_x\": null, \"pos_y\": null, \"slot_id\": 38, \"zone_id\": 1, \"slot_code\": \"C-38\", \"created_at\": \"2026-10-14 03:53:01.000000\", \"slot_width\": null, \"updated_at\": \"2026-10-14 03:53:01.000000\", \"is_pwd_slot\": 0, \"slot_height\": null, \"slot_status\": \"available\", \"rotation_deg\": 0, \"vehicle_type_id\": null, \"reserved_for_user_id\": null}','2026-10-23 15:29:57.726526'),(323,'parking_slots','39','baseline',NULL,NULL,'history installation',NULL,NULL,'{\"pos_x\": 6414, \"pos_y\": 1582, \"slot_id\": 39, \"zone_id\": 1, \"slot_code\": \"C-39\", \"created_at\": \"2026-10-14 03:53:01.000000\", \"slot_width\": 172, \"updated_at\": \"2026-10-14 03:53:01.000000\", \"is_pwd_slot\": 0, \"slot_height\": 240, \"slot_status\": \"available\", \"rotation_deg\": 10, \"vehicle_type_id\": null, \"reserved_for_user_id\": null}','2026-10-23 15:29:57.726526'),(324,'parking_slots','40','baseline',NULL,NULL,'history installation',NULL,NULL,'{\"pos_x\": null, \"pos_y\": null, \"slot_id\": 40, \"zone_id\": 1, \"slot_code\": \"C-40\", \"created_at\": \"2026-10-14 03:53:01.000000\", \"slot_width\": null, \"updated_at\": \"2026-10-14 03:53:01.000000\", \"is_pwd_slot\": 0, \"slot_height\": null, \"slot_status\": \"available\", \"rotation_deg\": 0, \"vehicle_type_id\": null, \"reserved_for_user_id\": null}','2026-10-23 15:29:57.726526'),(325,'parking_slots','41','baseline',NULL,NULL,'history installation',NULL,NULL,'{\"pos_x\": 6593, \"pos_y\": 1614, \"slot_id\": 41, \"zone_id\": 1, \"slot_code\": \"C-41\", \"created_at\": \"2026-10-14 03:53:01.000000\", \"slot_width\": 172, \"updated_at\": \"2026-10-14 03:53:01.000000\", \"is_pwd_slot\": 0, \"slot_height\": 240, \"slot_status\": \"available\", \"rotation_deg\": 10, \"vehicle_type_id\": null, \"reserved_for_user_id\": null}','2026-10-23 15:29:57.726526'),(326,'parking_slots','42','baseline',NULL,NULL,'history installation',NULL,NULL,'{\"pos_x\": 6771, \"pos_y\": 1647, \"slot_id\": 42, \"zone_id\": 1, \"slot_code\": \"C-42\", \"created_at\": \"2026-10-14 03:53:01.000000\", \"slot_width\": 172, \"updated_at\": \"2026-10-14 03:53:01.000000\", \"is_pwd_slot\": 1, \"slot_height\": 240, \"slot_status\": \"available\", \"rotation_deg\": 10, \"vehicle_type_id\": null, \"reserved_for_user_id\": null}','2026-10-23 15:29:57.726526'),(327,'parking_slots','43','baseline',NULL,NULL,'history installation',NULL,NULL,'{\"pos_x\": 3829, \"pos_y\": 1671, \"slot_id\": 43, \"zone_id\": 1, \"slot_code\": \"C-43\", \"created_at\": \"2026-10-14 03:53:01.000000\", \"slot_width\": 186, \"updated_at\": \"2026-10-14 03:53:01.000000\", \"is_pwd_slot\": 0, \"slot_height\": 240, \"slot_status\": \"available\", \"rotation_deg\": 11, \"vehicle_type_id\": null, \"reserved_for_user_id\": null}','2026-10-23 15:29:57.726526'),(328,'parking_slots','44','baseline',NULL,NULL,'history installation',NULL,NULL,'{\"pos_x\": 4023, \"pos_y\": 1709, \"slot_id\": 44, \"zone_id\": 1, \"slot_code\": \"C-44\", \"created_at\": \"2026-10-14 03:53:01.000000\", \"slot_width\": 186, \"updated_at\": \"2026-10-14 03:53:01.000000\", \"is_pwd_slot\": 0, \"slot_height\": 240, \"slot_status\": \"available\", \"rotation_deg\": 11, \"vehicle_type_id\": null, \"reserved_for_user_id\": null}','2026-10-23 15:29:57.726526'),(329,'parking_slots','45','baseline',NULL,NULL,'history installation',NULL,NULL,'{\"pos_x\": 4218, \"pos_y\": 1748, \"slot_id\": 45, \"zone_id\": 1, \"slot_code\": \"C-45\", \"created_at\": \"2026-10-14 03:53:01.000000\", \"slot_width\": 186, \"updated_at\": \"2026-10-14 03:53:01.000000\", \"is_pwd_slot\": 0, \"slot_height\": 240, \"slot_status\": \"available\", \"rotation_deg\": 11, \"vehicle_type_id\": null, \"reserved_for_user_id\": null}','2026-10-23 15:29:57.726526'),(330,'parking_slots','46','baseline',NULL,NULL,'history installation',NULL,NULL,'{\"pos_x\": 4412, \"pos_y\": 1786, \"slot_id\": 46, \"zone_id\": 1, \"slot_code\": \"C-46\", \"created_at\": \"2026-10-14 03:53:01.000000\", \"slot_width\": 186, \"updated_at\": \"2026-10-14 03:53:01.000000\", \"is_pwd_slot\": 0, \"slot_height\": 240, \"slot_status\": \"available\", \"rotation_deg\": 11, \"vehicle_type_id\": null, \"reserved_for_user_id\": null}','2026-10-23 15:29:57.726526'),(331,'parking_slots','47','baseline',NULL,NULL,'history installation',NULL,NULL,'{\"pos_x\": 4607, \"pos_y\": 1825, \"slot_id\": 47, \"zone_id\": 1, \"slot_code\": \"C-47\", \"created_at\": \"2026-10-14 03:53:01.000000\", \"slot_width\": 186, \"updated_at\": \"2026-10-14 03:53:01.000000\", \"is_pwd_slot\": 0, \"slot_height\": 240, \"slot_status\": \"available\", \"rotation_deg\": 11, \"vehicle_type_id\": null, \"reserved_for_user_id\": null}','2026-10-23 15:29:57.726526'),(332,'parking_slots','48','baseline',NULL,NULL,'history installation',NULL,NULL,'{\"pos_x\": 4801, \"pos_y\": 1863, \"slot_id\": 48, \"zone_id\": 1, \"slot_code\": \"C-48\", \"created_at\": \"2026-10-14 03:53:01.000000\", \"slot_width\": 186, \"updated_at\": \"2026-10-14 03:53:01.000000\", \"is_pwd_slot\": 0, \"slot_height\": 240, \"slot_status\": \"available\", \"rotation_deg\": 11, \"vehicle_type_id\": null, \"reserved_for_user_id\": null}','2026-10-23 15:29:57.726526'),(333,'parking_slots','49','baseline',NULL,NULL,'history installation',NULL,NULL,'{\"pos_x\": 4996, \"pos_y\": 1902, \"slot_id\": 49, \"zone_id\": 1, \"slot_code\": \"C-49\", \"created_at\": \"2026-10-14 03:53:01.000000\", \"slot_width\": 186, \"updated_at\": \"2026-10-14 03:53:01.000000\", \"is_pwd_slot\": 0, \"slot_height\": 240, \"slot_status\": \"available\", \"rotation_deg\": 11, \"vehicle_type_id\": null, \"reserved_for_user_id\": null}','2026-10-23 15:29:57.726526'),(334,'parking_slots','50','baseline',NULL,NULL,'history installation',NULL,NULL,'{\"pos_x\": 5190, \"pos_y\": 1940, \"slot_id\": 50, \"zone_id\": 1, \"slot_code\": \"C-50\", \"created_at\": \"2026-10-14 03:53:01.000000\", \"slot_width\": 186, \"updated_at\": \"2026-10-14 04:02:01.000000\", \"is_pwd_slot\": 0, \"slot_height\": 240, \"slot_status\": \"maintenance\", \"rotation_deg\": 11, \"vehicle_type_id\": null, \"reserved_for_user_id\": null}','2026-10-23 15:29:57.726526'),(335,'parking_slots','51','baseline',NULL,NULL,'history installation',NULL,NULL,'{\"pos_x\": 5385, \"pos_y\": 1979, \"slot_id\": 51, \"zone_id\": 1, \"slot_code\": \"C-51\", \"created_at\": \"2026-10-14 03:53:01.000000\", \"slot_width\": 186, \"updated_at\": \"2026-10-14 03:53:01.000000\", \"is_pwd_slot\": 0, \"slot_height\": 240, \"slot_status\": \"available\", \"rotation_deg\": 11, \"vehicle_type_id\": null, \"reserved_for_user_id\": null}','2026-10-23 15:29:57.726526'),(336,'parking_slots','52','baseline',NULL,NULL,'history installation',NULL,NULL,'{\"pos_x\": 5579, \"pos_y\": 2017, \"slot_id\": 52, \"zone_id\": 1, \"slot_code\": \"C-52\", \"created_at\": \"2026-10-14 03:53:01.000000\", \"slot_width\": 186, \"updated_at\": \"2026-10-14 03:53:01.000000\", \"is_pwd_slot\": 0, \"slot_height\": 240, \"slot_status\": \"available\", \"rotation_deg\": 11, \"vehicle_type_id\": null, \"reserved_for_user_id\": null}','2026-10-23 15:29:57.726526'),(337,'parking_slots','53','baseline',NULL,NULL,'history installation',NULL,NULL,'{\"pos_x\": 5774, \"pos_y\": 2056, \"slot_id\": 53, \"zone_id\": 1, \"slot_code\": \"C-53\", \"created_at\": \"2026-10-14 03:53:01.000000\", \"slot_width\": 186, \"updated_at\": \"2026-10-14 03:53:01.000000\", \"is_pwd_slot\": 0, \"slot_height\": 240, \"slot_status\": \"available\", \"rotation_deg\": 11, \"vehicle_type_id\": null, \"reserved_for_user_id\": null}','2026-10-23 15:29:57.726526'),(338,'parking_slots','54','baseline',NULL,NULL,'history installation',NULL,NULL,'{\"pos_x\": 5968, \"pos_y\": 2094, \"slot_id\": 54, \"zone_id\": 1, \"slot_code\": \"C-54\", \"created_at\": \"2026-10-14 03:53:01.000000\", \"slot_width\": 186, \"updated_at\": \"2026-10-14 03:53:01.000000\", \"is_pwd_slot\": 0, \"slot_height\": 240, \"slot_status\": \"available\", \"rotation_deg\": 11, \"vehicle_type_id\": null, \"reserved_for_user_id\": null}','2026-10-23 15:29:57.726526'),(339,'parking_slots','55','baseline',NULL,NULL,'history installation',NULL,NULL,'{\"pos_x\": 6357, \"pos_y\": 2171, \"slot_id\": 55, \"zone_id\": 1, \"slot_code\": \"C-55\", \"created_at\": \"2026-10-14 03:53:01.000000\", \"slot_width\": 186, \"updated_at\": \"2026-10-14 03:53:01.000000\", \"is_pwd_slot\": 0, \"slot_height\": 240, \"slot_status\": \"available\", \"rotation_deg\": 11, \"vehicle_type_id\": null, \"reserved_for_user_id\": null}','2026-10-23 15:29:57.726526'),(340,'parking_slots','56','baseline',NULL,NULL,'history installation',NULL,NULL,'{\"pos_x\": 875, \"pos_y\": 1350, \"slot_id\": 56, \"zone_id\": 2, \"slot_code\": \"M-01\", \"created_at\": \"2026-10-14 03:53:01.000000\", \"slot_width\": 90, \"updated_at\": \"2026-10-14 03:53:01.000000\", \"is_pwd_slot\": 0, \"slot_height\": 150, \"slot_status\": \"available\", \"rotation_deg\": -21, \"vehicle_type_id\": 6, \"reserved_for_user_id\": null}','2026-10-23 15:29:57.726526'),(341,'parking_slots','57','baseline',NULL,NULL,'history installation',NULL,NULL,'{\"pos_x\": 965, \"pos_y\": 1316, \"slot_id\": 57, \"zone_id\": 2, \"slot_code\": \"M-02\", \"created_at\": \"2026-10-14 03:53:01.000000\", \"slot_width\": 90, \"updated_at\": \"2026-10-14 03:53:01.000000\", \"is_pwd_slot\": 0, \"slot_height\": 150, \"slot_status\": \"available\", \"rotation_deg\": -21, \"vehicle_type_id\": 6, \"reserved_for_user_id\": null}','2026-10-23 15:29:57.726526'),(342,'parking_slots','58','baseline',NULL,NULL,'history installation',NULL,NULL,'{\"pos_x\": 1055, \"pos_y\": 1281, \"slot_id\": 58, \"zone_id\": 2, \"slot_code\": \"M-03\", \"created_at\": \"2026-10-14 03:53:01.000000\", \"slot_width\": 90, \"updated_at\": \"2026-10-14 03:53:01.000000\", \"is_pwd_slot\": 0, \"slot_height\": 150, \"slot_status\": \"available\", \"rotation_deg\": -21, \"vehicle_type_id\": 6, \"reserved_for_user_id\": null}','2026-10-23 15:29:57.726526'),(343,'parking_slots','59','baseline',NULL,NULL,'history installation',NULL,NULL,'{\"pos_x\": 1146, \"pos_y\": 1247, \"slot_id\": 59, \"zone_id\": 2, \"slot_code\": \"M-04\", \"created_at\": \"2026-10-14 03:53:01.000000\", \"slot_width\": 90, \"updated_at\": \"2026-10-14 03:53:01.000000\", \"is_pwd_slot\": 0, \"slot_height\": 150, \"slot_status\": \"available\", \"rotation_deg\": -21, \"vehicle_type_id\": 6, \"reserved_for_user_id\": null}','2026-10-23 15:29:57.726526'),(344,'parking_slots','60','baseline',NULL,NULL,'history installation',NULL,NULL,'{\"pos_x\": 1236, \"pos_y\": 1213, \"slot_id\": 60, \"zone_id\": 2, \"slot_code\": \"M-05\", \"created_at\": \"2026-10-14 03:53:01.000000\", \"slot_width\": 90, \"updated_at\": \"2026-10-14 03:53:01.000000\", \"is_pwd_slot\": 0, \"slot_height\": 150, \"slot_status\": \"available\", \"rotation_deg\": -21, \"vehicle_type_id\": 6, \"reserved_for_user_id\": null}','2026-10-23 15:29:57.726526'),(345,'parking_slots','61','baseline',NULL,NULL,'history installation',NULL,NULL,'{\"pos_x\": 1326, \"pos_y\": 1179, \"slot_id\": 61, \"zone_id\": 2, \"slot_code\": \"M-06\", \"created_at\": \"2026-10-14 03:53:01.000000\", \"slot_width\": 90, \"updated_at\": \"2026-10-14 03:53:01.000000\", \"is_pwd_slot\": 0, \"slot_height\": 150, \"slot_status\": \"available\", \"rotation_deg\": -21, \"vehicle_type_id\": 6, \"reserved_for_user_id\": null}','2026-10-23 15:29:57.726526'),(346,'parking_slots','62','baseline',NULL,NULL,'history installation',NULL,NULL,'{\"pos_x\": 1416, \"pos_y\": 1144, \"slot_id\": 62, \"zone_id\": 2, \"slot_code\": \"M-07\", \"created_at\": \"2026-10-14 03:53:01.000000\", \"slot_width\": 90, \"updated_at\": \"2026-10-14 03:53:01.000000\", \"is_pwd_slot\": 0, \"slot_height\": 150, \"slot_status\": \"available\", \"rotation_deg\": -21, \"vehicle_type_id\": 6, \"reserved_for_user_id\": null}','2026-10-23 15:29:57.726526'),(347,'parking_slots','63','baseline',NULL,NULL,'history installation',NULL,NULL,'{\"pos_x\": 1507, \"pos_y\": 1110, \"slot_id\": 63, \"zone_id\": 2, \"slot_code\": \"M-08\", \"created_at\": \"2026-10-14 03:53:01.000000\", \"slot_width\": 90, \"updated_at\": \"2026-10-14 03:53:01.000000\", \"is_pwd_slot\": 0, \"slot_height\": 150, \"slot_status\": \"available\", \"rotation_deg\": -21, \"vehicle_type_id\": 6, \"reserved_for_user_id\": null}','2026-10-23 15:29:57.726526'),(348,'parking_slots','64','baseline',NULL,NULL,'history installation',NULL,NULL,'{\"pos_x\": 1597, \"pos_y\": 1076, \"slot_id\": 64, \"zone_id\": 2, \"slot_code\": \"M-09\", \"created_at\": \"2026-10-14 03:53:01.000000\", \"slot_width\": 90, \"updated_at\": \"2026-10-14 03:53:01.000000\", \"is_pwd_slot\": 0, \"slot_height\": 150, \"slot_status\": \"available\", \"rotation_deg\": -21, \"vehicle_type_id\": 6, \"reserved_for_user_id\": null}','2026-10-23 15:29:57.726526'),(349,'parking_slots','65','baseline',NULL,NULL,'history installation',NULL,NULL,'{\"pos_x\": 1687, \"pos_y\": 1041, \"slot_id\": 65, \"zone_id\": 2, \"slot_code\": \"M-10\", \"created_at\": \"2026-10-14 03:53:01.000000\", \"slot_width\": 90, \"updated_at\": \"2026-10-14 03:53:01.000000\", \"is_pwd_slot\": 0, \"slot_height\": 150, \"slot_status\": \"available\", \"rotation_deg\": -21, \"vehicle_type_id\": 6, \"reserved_for_user_id\": null}','2026-10-23 15:29:57.726526'),(350,'parking_slots','66','baseline',NULL,NULL,'history installation',NULL,NULL,'{\"pos_x\": 1777, \"pos_y\": 1007, \"slot_id\": 66, \"zone_id\": 2, \"slot_code\": \"M-11\", \"created_at\": \"2026-10-14 03:53:01.000000\", \"slot_width\": 90, \"updated_at\": \"2026-10-14 03:53:01.000000\", \"is_pwd_slot\": 0, \"slot_height\": 150, \"slot_status\": \"available\", \"rotation_deg\": -21, \"vehicle_type_id\": 6, \"reserved_for_user_id\": null}','2026-10-23 15:29:57.726526'),(351,'parking_slots','67','baseline',NULL,NULL,'history installation',NULL,NULL,'{\"pos_x\": 1868, \"pos_y\": 973, \"slot_id\": 67, \"zone_id\": 2, \"slot_code\": \"M-12\", \"created_at\": \"2026-10-14 03:53:01.000000\", \"slot_width\": 90, \"updated_at\": \"2026-10-14 03:53:01.000000\", \"is_pwd_slot\": 0, \"slot_height\": 150, \"slot_status\": \"available\", \"rotation_deg\": -21, \"vehicle_type_id\": 6, \"reserved_for_user_id\": null}','2026-10-23 15:29:57.726526'),(352,'parking_slots','68','baseline',NULL,NULL,'history installation',NULL,NULL,'{\"pos_x\": 1958, \"pos_y\": 939, \"slot_id\": 68, \"zone_id\": 2, \"slot_code\": \"M-13\", \"created_at\": \"2026-10-14 03:53:01.000000\", \"slot_width\": 90, \"updated_at\": \"2026-10-14 03:53:01.000000\", \"is_pwd_slot\": 0, \"slot_height\": 150, \"slot_status\": \"available\", \"rotation_deg\": -21, \"vehicle_type_id\": 6, \"reserved_for_user_id\": null}','2026-10-23 15:29:57.726526'),(353,'parking_slots','69','baseline',NULL,NULL,'history installation',NULL,NULL,'{\"pos_x\": 2048, \"pos_y\": 904, \"slot_id\": 69, \"zone_id\": 2, \"slot_code\": \"M-14\", \"created_at\": \"2026-10-14 03:53:01.000000\", \"slot_width\": 90, \"updated_at\": \"2026-10-14 03:53:01.000000\", \"is_pwd_slot\": 0, \"slot_height\": 150, \"slot_status\": \"available\", \"rotation_deg\": -21, \"vehicle_type_id\": 6, \"reserved_for_user_id\": null}','2026-10-23 15:29:57.726526'),(354,'parking_slots','70','baseline',NULL,NULL,'history installation',NULL,NULL,'{\"pos_x\": 2138, \"pos_y\": 870, \"slot_id\": 70, \"zone_id\": 2, \"slot_code\": \"M-15\", \"created_at\": \"2026-10-14 03:53:01.000000\", \"slot_width\": 90, \"updated_at\": \"2026-10-14 03:53:01.000000\", \"is_pwd_slot\": 0, \"slot_height\": 150, \"slot_status\": \"available\", \"rotation_deg\": -21, \"vehicle_type_id\": 6, \"reserved_for_user_id\": null}','2026-10-23 15:29:57.726526'),(355,'parking_slots','71','baseline',NULL,NULL,'history installation',NULL,NULL,'{\"pos_x\": 2229, \"pos_y\": 836, \"slot_id\": 71, \"zone_id\": 2, \"slot_code\": \"M-16\", \"created_at\": \"2026-10-14 03:53:01.000000\", \"slot_width\": 90, \"updated_at\": \"2026-10-14 03:53:01.000000\", \"is_pwd_slot\": 0, \"slot_height\": 150, \"slot_status\": \"available\", \"rotation_deg\": -21, \"vehicle_type_id\": 6, \"reserved_for_user_id\": null}','2026-10-23 15:29:57.726526'),(356,'parking_slots','72','baseline',NULL,NULL,'history installation',NULL,NULL,'{\"pos_x\": 2319, \"pos_y\": 801, \"slot_id\": 72, \"zone_id\": 2, \"slot_code\": \"M-17\", \"created_at\": \"2026-10-14 03:53:01.000000\", \"slot_width\": 90, \"updated_at\": \"2026-10-14 03:53:01.000000\", \"is_pwd_slot\": 0, \"slot_height\": 150, \"slot_status\": \"available\", \"rotation_deg\": -21, \"vehicle_type_id\": 6, \"reserved_for_user_id\": null}','2026-10-23 15:29:57.726526'),(357,'parking_slots','73','baseline',NULL,NULL,'history installation',NULL,NULL,'{\"pos_x\": 2409, \"pos_y\": 767, \"slot_id\": 73, \"zone_id\": 2, \"slot_code\": \"M-18\", \"created_at\": \"2026-10-14 03:53:01.000000\", \"slot_width\": 90, \"updated_at\": \"2026-10-14 03:53:01.000000\", \"is_pwd_slot\": 0, \"slot_height\": 150, \"slot_status\": \"available\", \"rotation_deg\": -21, \"vehicle_type_id\": 6, \"reserved_for_user_id\": null}','2026-10-23 15:29:57.726526'),(358,'parking_slots','74','baseline',NULL,NULL,'history installation',NULL,NULL,'{\"pos_x\": 2499, \"pos_y\": 733, \"slot_id\": 74, \"zone_id\": 2, \"slot_code\": \"M-19\", \"created_at\": \"2026-10-14 03:53:01.000000\", \"slot_width\": 90, \"updated_at\": \"2026-10-14 03:53:01.000000\", \"is_pwd_slot\": 0, \"slot_height\": 150, \"slot_status\": \"available\", \"rotation_deg\": -21, \"vehicle_type_id\": 6, \"reserved_for_user_id\": null}','2026-10-23 15:29:57.726526'),(359,'parking_slots','75','baseline',NULL,NULL,'history installation',NULL,NULL,'{\"pos_x\": 2590, \"pos_y\": 699, \"slot_id\": 75, \"zone_id\": 2, \"slot_code\": \"M-20\", \"created_at\": \"2026-10-14 03:53:01.000000\", \"slot_width\": 90, \"updated_at\": \"2026-10-14 03:53:01.000000\", \"is_pwd_slot\": 0, \"slot_height\": 150, \"slot_status\": \"available\", \"rotation_deg\": -21, \"vehicle_type_id\": 6, \"reserved_for_user_id\": null}','2026-10-23 15:29:57.726526'),(360,'parking_slots','76','baseline',NULL,NULL,'history installation',NULL,NULL,'{\"pos_x\": 2680, \"pos_y\": 664, \"slot_id\": 76, \"zone_id\": 2, \"slot_code\": \"M-21\", \"created_at\": \"2026-10-14 03:53:01.000000\", \"slot_width\": 90, \"updated_at\": \"2026-10-14 03:53:01.000000\", \"is_pwd_slot\": 0, \"slot_height\": 150, \"slot_status\": \"available\", \"rotation_deg\": -21, \"vehicle_type_id\": 6, \"reserved_for_user_id\": null}','2026-10-23 15:29:57.726526'),(361,'parking_slots','77','baseline',NULL,NULL,'history installation',NULL,NULL,'{\"pos_x\": 2770, \"pos_y\": 630, \"slot_id\": 77, \"zone_id\": 2, \"slot_code\": \"M-22\", \"created_at\": \"2026-10-14 03:53:01.000000\", \"slot_width\": 90, \"updated_at\": \"2026-10-14 03:53:01.000000\", \"is_pwd_slot\": 0, \"slot_height\": 150, \"slot_status\": \"available\", \"rotation_deg\": -21, \"vehicle_type_id\": 6, \"reserved_for_user_id\": null}','2026-10-23 15:29:57.726526'),(362,'parking_slots','78','baseline',NULL,NULL,'history installation',NULL,NULL,'{\"pos_x\": null, \"pos_y\": null, \"slot_id\": 78, \"zone_id\": 2, \"slot_code\": \"M-23\", \"created_at\": \"2026-10-14 03:53:01.000000\", \"slot_width\": null, \"updated_at\": \"2026-10-14 03:53:01.000000\", \"is_pwd_slot\": 0, \"slot_height\": null, \"slot_status\": \"available\", \"rotation_deg\": 0, \"vehicle_type_id\": 6, \"reserved_for_user_id\": null}','2026-10-23 15:29:57.726526'),(363,'parking_slots','79','baseline',NULL,NULL,'history installation',NULL,NULL,'{\"pos_x\": null, \"pos_y\": null, \"slot_id\": 79, \"zone_id\": 2, \"slot_code\": \"M-24\", \"created_at\": \"2026-10-14 03:53:01.000000\", \"slot_width\": null, \"updated_at\": \"2026-10-14 03:53:01.000000\", \"is_pwd_slot\": 0, \"slot_height\": null, \"slot_status\": \"available\", \"rotation_deg\": 0, \"vehicle_type_id\": 6, \"reserved_for_user_id\": null}','2026-10-23 15:29:57.726526'),(364,'parking_slots','80','baseline',NULL,NULL,'history installation',NULL,NULL,'{\"pos_x\": null, \"pos_y\": null, \"slot_id\": 80, \"zone_id\": 2, \"slot_code\": \"M-25\", \"created_at\": \"2026-10-14 03:53:01.000000\", \"slot_width\": null, \"updated_at\": \"2026-10-14 03:53:01.000000\", \"is_pwd_slot\": 0, \"slot_height\": null, \"slot_status\": \"available\", \"rotation_deg\": 0, \"vehicle_type_id\": 6, \"reserved_for_user_id\": null}','2026-10-23 15:29:57.726526'),(365,'parking_slots','81','baseline',NULL,NULL,'history installation',NULL,NULL,'{\"pos_x\": null, \"pos_y\": null, \"slot_id\": 81, \"zone_id\": 2, \"slot_code\": \"M-26\", \"created_at\": \"2026-10-14 03:53:01.000000\", \"slot_width\": null, \"updated_at\": \"2026-10-14 03:53:01.000000\", \"is_pwd_slot\": 0, \"slot_height\": null, \"slot_status\": \"available\", \"rotation_deg\": 0, \"vehicle_type_id\": 6, \"reserved_for_user_id\": null}','2026-10-23 15:29:57.726526'),(366,'parking_slots','82','baseline',NULL,NULL,'history installation',NULL,NULL,'{\"pos_x\": null, \"pos_y\": null, \"slot_id\": 82, \"zone_id\": 2, \"slot_code\": \"M-27\", \"created_at\": \"2026-10-14 03:53:01.000000\", \"slot_width\": null, \"updated_at\": \"2026-10-14 03:53:01.000000\", \"is_pwd_slot\": 0, \"slot_height\": null, \"slot_status\": \"available\", \"rotation_deg\": 0, \"vehicle_type_id\": 6, \"reserved_for_user_id\": null}','2026-10-23 15:29:57.726526'),(367,'parking_slots','83','baseline',NULL,NULL,'history installation',NULL,NULL,'{\"pos_x\": null, \"pos_y\": null, \"slot_id\": 83, \"zone_id\": 2, \"slot_code\": \"M-28\", \"created_at\": \"2026-10-14 03:53:01.000000\", \"slot_width\": null, \"updated_at\": \"2026-10-14 03:53:01.000000\", \"is_pwd_slot\": 0, \"slot_height\": null, \"slot_status\": \"available\", \"rotation_deg\": 0, \"vehicle_type_id\": 6, \"reserved_for_user_id\": null}','2026-10-23 15:29:57.726526'),(368,'parking_slots','84','baseline',NULL,NULL,'history installation',NULL,NULL,'{\"pos_x\": null, \"pos_y\": null, \"slot_id\": 84, \"zone_id\": 2, \"slot_code\": \"M-29\", \"created_at\": \"2026-10-14 03:53:01.000000\", \"slot_width\": null, \"updated_at\": \"2026-10-14 03:53:01.000000\", \"is_pwd_slot\": 0, \"slot_height\": null, \"slot_status\": \"available\", \"rotation_deg\": 0, \"vehicle_type_id\": 6, \"reserved_for_user_id\": null}','2026-10-23 15:29:57.726526'),(369,'parking_slots','85','baseline',NULL,NULL,'history installation',NULL,NULL,'{\"pos_x\": null, \"pos_y\": null, \"slot_id\": 85, \"zone_id\": 2, \"slot_code\": \"M-30\", \"created_at\": \"2026-10-14 03:53:01.000000\", \"slot_width\": null, \"updated_at\": \"2026-10-14 03:53:01.000000\", \"is_pwd_slot\": 0, \"slot_height\": null, \"slot_status\": \"available\", \"rotation_deg\": 0, \"vehicle_type_id\": 6, \"reserved_for_user_id\": null}','2026-10-23 15:29:57.726526'),(370,'parking_slots','86','baseline',NULL,NULL,'history installation',NULL,NULL,'{\"pos_x\": null, \"pos_y\": null, \"slot_id\": 86, \"zone_id\": 2, \"slot_code\": \"M-31\", \"created_at\": \"2026-10-14 03:53:01.000000\", \"slot_width\": null, \"updated_at\": \"2026-10-14 03:53:01.000000\", \"is_pwd_slot\": 0, \"slot_height\": null, \"slot_status\": \"available\", \"rotation_deg\": 0, \"vehicle_type_id\": 6, \"reserved_for_user_id\": null}','2026-10-23 15:29:57.726526'),(371,'parking_slots','87','baseline',NULL,NULL,'history installation',NULL,NULL,'{\"pos_x\": null, \"pos_y\": null, \"slot_id\": 87, \"zone_id\": 2, \"slot_code\": \"M-32\", \"created_at\": \"2026-10-14 03:53:01.000000\", \"slot_width\": null, \"updated_at\": \"2026-10-14 03:53:01.000000\", \"is_pwd_slot\": 0, \"slot_height\": null, \"slot_status\": \"available\", \"rotation_deg\": 0, \"vehicle_type_id\": 6, \"reserved_for_user_id\": null}','2026-10-23 15:29:57.726526'),(372,'parking_slots','88','baseline',NULL,NULL,'history installation',NULL,NULL,'{\"pos_x\": null, \"pos_y\": null, \"slot_id\": 88, \"zone_id\": 2, \"slot_code\": \"M-33\", \"created_at\": \"2026-10-14 03:53:01.000000\", \"slot_width\": null, \"updated_at\": \"2026-10-14 03:53:01.000000\", \"is_pwd_slot\": 0, \"slot_height\": null, \"slot_status\": \"available\", \"rotation_deg\": 0, \"vehicle_type_id\": 6, \"reserved_for_user_id\": null}','2026-10-23 15:29:57.726526'),(373,'parking_slots','89','baseline',NULL,NULL,'history installation',NULL,NULL,'{\"pos_x\": null, \"pos_y\": null, \"slot_id\": 89, \"zone_id\": 2, \"slot_code\": \"M-34\", \"created_at\": \"2026-10-14 03:53:01.000000\", \"slot_width\": null, \"updated_at\": \"2026-10-14 03:53:01.000000\", \"is_pwd_slot\": 0, \"slot_height\": null, \"slot_status\": \"available\", \"rotation_deg\": 0, \"vehicle_type_id\": 6, \"reserved_for_user_id\": null}','2026-10-23 15:29:57.726526'),(374,'parking_slots','90','baseline',NULL,NULL,'history installation',NULL,NULL,'{\"pos_x\": null, \"pos_y\": null, \"slot_id\": 90, \"zone_id\": 2, \"slot_code\": \"M-35\", \"created_at\": \"2026-10-14 03:53:01.000000\", \"slot_width\": null, \"updated_at\": \"2026-10-14 03:53:01.000000\", \"is_pwd_slot\": 0, \"slot_height\": null, \"slot_status\": \"available\", \"rotation_deg\": 0, \"vehicle_type_id\": 6, \"reserved_for_user_id\": null}','2026-10-23 15:29:57.726526'),(375,'parking_slots','91','baseline',NULL,NULL,'history installation',NULL,NULL,'{\"pos_x\": null, \"pos_y\": null, \"slot_id\": 91, \"zone_id\": 2, \"slot_code\": \"M-36\", \"created_at\": \"2026-10-14 03:53:01.000000\", \"slot_width\": null, \"updated_at\": \"2026-10-14 03:53:01.000000\", \"is_pwd_slot\": 0, \"slot_height\": null, \"slot_status\": \"available\", \"rotation_deg\": 0, \"vehicle_type_id\": 6, \"reserved_for_user_id\": null}','2026-10-23 15:29:57.726526'),(376,'parking_slots','92','baseline',NULL,NULL,'history installation',NULL,NULL,'{\"pos_x\": null, \"pos_y\": null, \"slot_id\": 92, \"zone_id\": 2, \"slot_code\": \"M-37\", \"created_at\": \"2026-10-14 03:53:01.000000\", \"slot_width\": null, \"updated_at\": \"2026-10-14 03:53:01.000000\", \"is_pwd_slot\": 0, \"slot_height\": null, \"slot_status\": \"available\", \"rotation_deg\": 0, \"vehicle_type_id\": 6, \"reserved_for_user_id\": null}','2026-10-23 15:29:57.726526'),(377,'parking_slots','93','baseline',NULL,NULL,'history installation',NULL,NULL,'{\"pos_x\": null, \"pos_y\": null, \"slot_id\": 93, \"zone_id\": 2, \"slot_code\": \"M-38\", \"created_at\": \"2026-10-14 03:53:01.000000\", \"slot_width\": null, \"updated_at\": \"2026-10-14 03:53:01.000000\", \"is_pwd_slot\": 0, \"slot_height\": null, \"slot_status\": \"available\", \"rotation_deg\": 0, \"vehicle_type_id\": 6, \"reserved_for_user_id\": null}','2026-10-23 15:29:57.726526'),(378,'parking_slots','94','baseline',NULL,NULL,'history installation',NULL,NULL,'{\"pos_x\": null, \"pos_y\": null, \"slot_id\": 94, \"zone_id\": 2, \"slot_code\": \"M-39\", \"created_at\": \"2026-10-14 03:53:01.000000\", \"slot_width\": null, \"updated_at\": \"2026-10-14 03:53:01.000000\", \"is_pwd_slot\": 0, \"slot_height\": null, \"slot_status\": \"available\", \"rotation_deg\": 0, \"vehicle_type_id\": 6, \"reserved_for_user_id\": null}','2026-10-23 15:29:57.726526'),(379,'parking_slots','95','baseline',NULL,NULL,'history installation',NULL,NULL,'{\"pos_x\": null, \"pos_y\": null, \"slot_id\": 95, \"zone_id\": 2, \"slot_code\": \"M-40\", \"created_at\": \"2026-10-14 03:53:01.000000\", \"slot_width\": null, \"updated_at\": \"2026-10-14 03:53:01.000000\", \"is_pwd_slot\": 0, \"slot_height\": null, \"slot_status\": \"available\", \"rotation_deg\": 0, \"vehicle_type_id\": 6, \"reserved_for_user_id\": null}','2026-10-23 15:29:57.726526'),(380,'parking_slots','96','baseline',NULL,NULL,'history installation',NULL,NULL,'{\"pos_x\": null, \"pos_y\": null, \"slot_id\": 96, \"zone_id\": 2, \"slot_code\": \"M-41\", \"created_at\": \"2026-10-14 03:53:01.000000\", \"slot_width\": null, \"updated_at\": \"2026-10-14 03:53:01.000000\", \"is_pwd_slot\": 0, \"slot_height\": null, \"slot_status\": \"available\", \"rotation_deg\": 0, \"vehicle_type_id\": 6, \"reserved_for_user_id\": null}','2026-10-23 15:29:57.726526'),(381,'parking_slots','97','baseline',NULL,NULL,'history installation',NULL,NULL,'{\"pos_x\": null, \"pos_y\": null, \"slot_id\": 97, \"zone_id\": 2, \"slot_code\": \"M-42\", \"created_at\": \"2026-10-14 03:53:01.000000\", \"slot_width\": null, \"updated_at\": \"2026-10-14 03:53:01.000000\", \"is_pwd_slot\": 0, \"slot_height\": null, \"slot_status\": \"available\", \"rotation_deg\": 0, \"vehicle_type_id\": 6, \"reserved_for_user_id\": null}','2026-10-23 15:29:57.726526'),(382,'parking_slots','98','baseline',NULL,NULL,'history installation',NULL,NULL,'{\"pos_x\": 1338, \"pos_y\": 1495, \"slot_id\": 98, \"zone_id\": 2, \"slot_code\": \"M-43\", \"created_at\": \"2026-10-14 03:53:01.000000\", \"slot_width\": 76, \"updated_at\": \"2026-10-14 03:53:01.000000\", \"is_pwd_slot\": 0, \"slot_height\": 400, \"slot_status\": \"available\", \"rotation_deg\": 70, \"vehicle_type_id\": 6, \"reserved_for_user_id\": null}','2026-10-23 15:29:57.726526'),(383,'parking_slots','99','baseline',NULL,NULL,'history installation',NULL,NULL,'{\"pos_x\": 1367, \"pos_y\": 1573, \"slot_id\": 99, \"zone_id\": 2, \"slot_code\": \"M-44\", \"created_at\": \"2026-10-14 03:53:01.000000\", \"slot_width\": 76, \"updated_at\": \"2026-10-14 03:53:01.000000\", \"is_pwd_slot\": 0, \"slot_height\": 400, \"slot_status\": \"available\", \"rotation_deg\": 70, \"vehicle_type_id\": 6, \"reserved_for_user_id\": null}','2026-10-23 15:29:57.726526'),(384,'parking_slots','100','baseline',NULL,NULL,'history installation',NULL,NULL,'{\"pos_x\": 1395, \"pos_y\": 1650, \"slot_id\": 100, \"zone_id\": 2, \"slot_code\": \"M-45\", \"created_at\": \"2026-10-14 03:53:01.000000\", \"slot_width\": 76, \"updated_at\": \"2026-10-14 03:53:01.000000\", \"is_pwd_slot\": 0, \"slot_height\": 400, \"slot_status\": \"available\", \"rotation_deg\": 70, \"vehicle_type_id\": 6, \"reserved_for_user_id\": null}','2026-10-23 15:29:57.726526'),(385,'parking_slots','101','baseline',NULL,NULL,'history installation',NULL,NULL,'{\"pos_x\": 1424, \"pos_y\": 1728, \"slot_id\": 101, \"zone_id\": 2, \"slot_code\": \"M-46\", \"created_at\": \"2026-10-14 03:53:01.000000\", \"slot_width\": 76, \"updated_at\": \"2026-10-14 03:53:01.000000\", \"is_pwd_slot\": 0, \"slot_height\": 400, \"slot_status\": \"available\", \"rotation_deg\": 70, \"vehicle_type_id\": 6, \"reserved_for_user_id\": null}','2026-10-23 15:29:57.726526'),(386,'parking_slots','102','baseline',NULL,NULL,'history installation',NULL,NULL,'{\"pos_x\": 1452, \"pos_y\": 1805, \"slot_id\": 102, \"zone_id\": 2, \"slot_code\": \"M-47\", \"created_at\": \"2026-10-14 03:53:01.000000\", \"slot_width\": 76, \"updated_at\": \"2026-10-14 03:53:01.000000\", \"is_pwd_slot\": 0, \"slot_height\": 400, \"slot_status\": \"available\", \"rotation_deg\": 70, \"vehicle_type_id\": 6, \"reserved_for_user_id\": null}','2026-10-23 15:29:57.726526'),(387,'parking_slots','103','baseline',NULL,NULL,'history installation',NULL,NULL,'{\"pos_x\": 1481, \"pos_y\": 1883, \"slot_id\": 103, \"zone_id\": 2, \"slot_code\": \"M-48\", \"created_at\": \"2026-10-14 03:53:01.000000\", \"slot_width\": 76, \"updated_at\": \"2026-10-14 03:53:01.000000\", \"is_pwd_slot\": 0, \"slot_height\": 400, \"slot_status\": \"available\", \"rotation_deg\": 70, \"vehicle_type_id\": 6, \"reserved_for_user_id\": null}','2026-10-23 15:29:57.726526'),(388,'parking_slots','104','baseline',NULL,NULL,'history installation',NULL,NULL,'{\"pos_x\": 1509, \"pos_y\": 1960, \"slot_id\": 104, \"zone_id\": 2, \"slot_code\": \"M-49\", \"created_at\": \"2026-10-14 03:53:01.000000\", \"slot_width\": 76, \"updated_at\": \"2026-10-14 03:53:01.000000\", \"is_pwd_slot\": 0, \"slot_height\": 400, \"slot_status\": \"available\", \"rotation_deg\": 70, \"vehicle_type_id\": 6, \"reserved_for_user_id\": null}','2026-10-23 15:29:57.726526'),(389,'parking_slots','105','baseline',NULL,NULL,'history installation',NULL,NULL,'{\"pos_x\": 1538, \"pos_y\": 2038, \"slot_id\": 105, \"zone_id\": 2, \"slot_code\": \"M-50\", \"created_at\": \"2026-10-14 03:53:01.000000\", \"slot_width\": 76, \"updated_at\": \"2026-10-14 03:53:01.000000\", \"is_pwd_slot\": 0, \"slot_height\": 400, \"slot_status\": \"available\", \"rotation_deg\": 70, \"vehicle_type_id\": 6, \"reserved_for_user_id\": null}','2026-10-23 15:29:57.726526'),(390,'parking_slots','106','baseline',NULL,NULL,'history installation',NULL,NULL,'{\"pos_x\": 1567, \"pos_y\": 2115, \"slot_id\": 106, \"zone_id\": 2, \"slot_code\": \"M-51\", \"created_at\": \"2026-10-14 03:53:01.000000\", \"slot_width\": 76, \"updated_at\": \"2026-10-14 03:53:01.000000\", \"is_pwd_slot\": 0, \"slot_height\": 400, \"slot_status\": \"available\", \"rotation_deg\": 70, \"vehicle_type_id\": 6, \"reserved_for_user_id\": null}','2026-10-23 15:29:57.726526'),(391,'parking_slots','107','baseline',NULL,NULL,'history installation',NULL,NULL,'{\"pos_x\": 1595, \"pos_y\": 2193, \"slot_id\": 107, \"zone_id\": 2, \"slot_code\": \"M-52\", \"created_at\": \"2026-10-14 03:53:01.000000\", \"slot_width\": 76, \"updated_at\": \"2026-10-14 03:53:01.000000\", \"is_pwd_slot\": 0, \"slot_height\": 400, \"slot_status\": \"available\", \"rotation_deg\": 70, \"vehicle_type_id\": 6, \"reserved_for_user_id\": null}','2026-10-23 15:29:57.726526'),(392,'parking_slots','108','baseline',NULL,NULL,'history installation',NULL,NULL,'{\"pos_x\": 1624, \"pos_y\": 2270, \"slot_id\": 108, \"zone_id\": 2, \"slot_code\": \"M-53\", \"created_at\": \"2026-10-14 03:53:01.000000\", \"slot_width\": 76, \"updated_at\": \"2026-10-14 03:53:01.000000\", \"is_pwd_slot\": 0, \"slot_height\": 400, \"slot_status\": \"available\", \"rotation_deg\": 70, \"vehicle_type_id\": 6, \"reserved_for_user_id\": null}','2026-10-23 15:29:57.726526'),(393,'parking_slots','109','baseline',NULL,NULL,'history installation',NULL,NULL,'{\"pos_x\": 1652, \"pos_y\": 2348, \"slot_id\": 109, \"zone_id\": 2, \"slot_code\": \"M-54\", \"created_at\": \"2026-10-14 03:53:01.000000\", \"slot_width\": 76, \"updated_at\": \"2026-10-14 03:53:01.000000\", \"is_pwd_slot\": 0, \"slot_height\": 400, \"slot_status\": \"available\", \"rotation_deg\": 70, \"vehicle_type_id\": 6, \"reserved_for_user_id\": null}','2026-10-23 15:29:57.726526'),(394,'parking_slots','110','baseline',NULL,NULL,'history installation',NULL,NULL,'{\"pos_x\": 1681, \"pos_y\": 2425, \"slot_id\": 110, \"zone_id\": 2, \"slot_code\": \"M-55\", \"created_at\": \"2026-10-14 03:53:01.000000\", \"slot_width\": 76, \"updated_at\": \"2026-10-14 03:53:01.000000\", \"is_pwd_slot\": 0, \"slot_height\": 400, \"slot_status\": \"available\", \"rotation_deg\": 70, \"vehicle_type_id\": 6, \"reserved_for_user_id\": null}','2026-10-23 15:29:57.726526'),(395,'parking_slots','111','baseline',NULL,NULL,'history installation',NULL,NULL,'{\"pos_x\": 1709, \"pos_y\": 2503, \"slot_id\": 111, \"zone_id\": 2, \"slot_code\": \"M-56\", \"created_at\": \"2026-10-14 03:53:01.000000\", \"slot_width\": 76, \"updated_at\": \"2026-10-14 03:53:01.000000\", \"is_pwd_slot\": 0, \"slot_height\": 400, \"slot_status\": \"available\", \"rotation_deg\": 70, \"vehicle_type_id\": 6, \"reserved_for_user_id\": null}','2026-10-23 15:29:57.726526'),(396,'parking_slots','112','baseline',NULL,NULL,'history installation',NULL,NULL,'{\"pos_x\": 1995, \"pos_y\": 1263, \"slot_id\": 112, \"zone_id\": 2, \"slot_code\": \"M-57\", \"created_at\": \"2026-10-14 03:53:01.000000\", \"slot_width\": 79, \"updated_at\": \"2026-10-14 03:53:01.000000\", \"is_pwd_slot\": 0, \"slot_height\": 420, \"slot_status\": \"available\", \"rotation_deg\": 68, \"vehicle_type_id\": 6, \"reserved_for_user_id\": null}','2026-10-23 15:29:57.726526'),(397,'parking_slots','113','baseline',NULL,NULL,'history installation',NULL,NULL,'{\"pos_x\": 2027, \"pos_y\": 1343, \"slot_id\": 113, \"zone_id\": 2, \"slot_code\": \"M-58\", \"created_at\": \"2026-10-14 03:53:01.000000\", \"slot_width\": 79, \"updated_at\": \"2026-10-14 03:53:01.000000\", \"is_pwd_slot\": 0, \"slot_height\": 420, \"slot_status\": \"available\", \"rotation_deg\": 68, \"vehicle_type_id\": 6, \"reserved_for_user_id\": null}','2026-10-23 15:29:57.726526'),(398,'parking_slots','114','baseline',NULL,NULL,'history installation',NULL,NULL,'{\"pos_x\": 2058, \"pos_y\": 1423, \"slot_id\": 114, \"zone_id\": 2, \"slot_code\": \"M-59\", \"created_at\": \"2026-10-14 03:53:01.000000\", \"slot_width\": 79, \"updated_at\": \"2026-10-14 03:53:01.000000\", \"is_pwd_slot\": 0, \"slot_height\": 420, \"slot_status\": \"available\", \"rotation_deg\": 68, \"vehicle_type_id\": 6, \"reserved_for_user_id\": null}','2026-10-23 15:29:57.726526'),(399,'parking_slots','115','baseline',NULL,NULL,'history installation',NULL,NULL,'{\"pos_x\": 2090, \"pos_y\": 1502, \"slot_id\": 115, \"zone_id\": 2, \"slot_code\": \"M-60\", \"created_at\": \"2026-10-14 03:53:01.000000\", \"slot_width\": 79, \"updated_at\": \"2026-10-14 03:53:01.000000\", \"is_pwd_slot\": 0, \"slot_height\": 420, \"slot_status\": \"available\", \"rotation_deg\": 68, \"vehicle_type_id\": 6, \"reserved_for_user_id\": null}','2026-10-23 15:29:57.726526'),(400,'parking_slots','116','baseline',NULL,NULL,'history installation',NULL,NULL,'{\"pos_x\": 2122, \"pos_y\": 1582, \"slot_id\": 116, \"zone_id\": 2, \"slot_code\": \"M-61\", \"created_at\": \"2026-10-14 03:53:01.000000\", \"slot_width\": 79, \"updated_at\": \"2026-10-14 03:53:01.000000\", \"is_pwd_slot\": 0, \"slot_height\": 420, \"slot_status\": \"available\", \"rotation_deg\": 68, \"vehicle_type_id\": 6, \"reserved_for_user_id\": null}','2026-10-23 15:29:57.726526'),(401,'parking_slots','117','baseline',NULL,NULL,'history installation',NULL,NULL,'{\"pos_x\": 2153, \"pos_y\": 1662, \"slot_id\": 117, \"zone_id\": 2, \"slot_code\": \"M-62\", \"created_at\": \"2026-10-14 03:53:01.000000\", \"slot_width\": 79, \"updated_at\": \"2026-10-14 03:53:01.000000\", \"is_pwd_slot\": 0, \"slot_height\": 420, \"slot_status\": \"available\", \"rotation_deg\": 68, \"vehicle_type_id\": 6, \"reserved_for_user_id\": null}','2026-10-23 15:29:57.726526'),(402,'parking_slots','118','baseline',NULL,NULL,'history installation',NULL,NULL,'{\"pos_x\": 2185, \"pos_y\": 1742, \"slot_id\": 118, \"zone_id\": 2, \"slot_code\": \"M-63\", \"created_at\": \"2026-10-14 03:53:01.000000\", \"slot_width\": 79, \"updated_at\": \"2026-10-14 03:53:01.000000\", \"is_pwd_slot\": 0, \"slot_height\": 420, \"slot_status\": \"available\", \"rotation_deg\": 68, \"vehicle_type_id\": 6, \"reserved_for_user_id\": null}','2026-10-23 15:29:57.726526'),(403,'parking_slots','119','baseline',NULL,NULL,'history installation',NULL,NULL,'{\"pos_x\": 2217, \"pos_y\": 1822, \"slot_id\": 119, \"zone_id\": 2, \"slot_code\": \"M-64\", \"created_at\": \"2026-10-14 03:53:01.000000\", \"slot_width\": 79, \"updated_at\": \"2026-10-14 03:53:01.000000\", \"is_pwd_slot\": 0, \"slot_height\": 420, \"slot_status\": \"available\", \"rotation_deg\": 68, \"vehicle_type_id\": 6, \"reserved_for_user_id\": null}','2026-10-23 15:29:57.726526'),(404,'parking_slots','120','baseline',NULL,NULL,'history installation',NULL,NULL,'{\"pos_x\": 2248, \"pos_y\": 1901, \"slot_id\": 120, \"zone_id\": 2, \"slot_code\": \"M-65\", \"created_at\": \"2026-10-14 03:53:01.000000\", \"slot_width\": 79, \"updated_at\": \"2026-10-14 03:53:01.000000\", \"is_pwd_slot\": 0, \"slot_height\": 420, \"slot_status\": \"available\", \"rotation_deg\": 68, \"vehicle_type_id\": 6, \"reserved_for_user_id\": null}','2026-10-23 15:29:57.726526'),(405,'parking_slots','121','baseline',NULL,NULL,'history installation',NULL,NULL,'{\"pos_x\": 2280, \"pos_y\": 1981, \"slot_id\": 121, \"zone_id\": 2, \"slot_code\": \"M-66\", \"created_at\": \"2026-10-14 03:53:01.000000\", \"slot_width\": 79, \"updated_at\": \"2026-10-14 03:53:01.000000\", \"is_pwd_slot\": 0, \"slot_height\": 420, \"slot_status\": \"available\", \"rotation_deg\": 68, \"vehicle_type_id\": 6, \"reserved_for_user_id\": null}','2026-10-23 15:29:57.726526'),(406,'parking_slots','122','baseline',NULL,NULL,'history installation',NULL,NULL,'{\"pos_x\": 2343, \"pos_y\": 2141, \"slot_id\": 122, \"zone_id\": 2, \"slot_code\": \"M-67\", \"created_at\": \"2026-10-14 03:53:01.000000\", \"slot_width\": 79, \"updated_at\": \"2026-10-14 03:53:01.000000\", \"is_pwd_slot\": 0, \"slot_height\": 420, \"slot_status\": \"available\", \"rotation_deg\": 68, \"vehicle_type_id\": 6, \"reserved_for_user_id\": null}','2026-10-23 15:29:57.726526'),(407,'parking_slots','123','baseline',NULL,NULL,'history installation',NULL,NULL,'{\"pos_x\": 2375, \"pos_y\": 2220, \"slot_id\": 123, \"zone_id\": 2, \"slot_code\": \"M-68\", \"created_at\": \"2026-10-14 03:53:01.000000\", \"slot_width\": 79, \"updated_at\": \"2026-10-14 03:53:01.000000\", \"is_pwd_slot\": 0, \"slot_height\": 420, \"slot_status\": \"available\", \"rotation_deg\": 68, \"vehicle_type_id\": 6, \"reserved_for_user_id\": null}','2026-10-23 15:29:57.726526'),(408,'parking_slots','124','baseline',NULL,NULL,'history installation',NULL,NULL,'{\"pos_x\": 2406, \"pos_y\": 2300, \"slot_id\": 124, \"zone_id\": 2, \"slot_code\": \"M-69\", \"created_at\": \"2026-10-14 03:53:01.000000\", \"slot_width\": 79, \"updated_at\": \"2026-10-14 03:53:01.000000\", \"is_pwd_slot\": 0, \"slot_height\": 420, \"slot_status\": \"available\", \"rotation_deg\": 68, \"vehicle_type_id\": 6, \"reserved_for_user_id\": null}','2026-10-23 15:29:57.726526'),(409,'parking_slots','125','baseline',NULL,NULL,'history installation',NULL,NULL,'{\"pos_x\": 2438, \"pos_y\": 2380, \"slot_id\": 125, \"zone_id\": 2, \"slot_code\": \"M-70\", \"created_at\": \"2026-10-14 03:53:01.000000\", \"slot_width\": 79, \"updated_at\": \"2026-10-14 03:53:01.000000\", \"is_pwd_slot\": 0, \"slot_height\": 420, \"slot_status\": \"available\", \"rotation_deg\": 68, \"vehicle_type_id\": 6, \"reserved_for_user_id\": null}','2026-10-23 15:29:57.726526'),(410,'parking_slots','126','baseline',NULL,NULL,'history installation',NULL,NULL,'{\"pos_x\": 2693, \"pos_y\": 1025, \"slot_id\": 126, \"zone_id\": 2, \"slot_code\": \"M-71\", \"created_at\": \"2026-10-14 03:53:01.000000\", \"slot_width\": 76, \"updated_at\": \"2026-10-14 03:53:01.000000\", \"is_pwd_slot\": 0, \"slot_height\": 360, \"slot_status\": \"available\", \"rotation_deg\": 68, \"vehicle_type_id\": 6, \"reserved_for_user_id\": null}','2026-10-23 15:29:57.726526'),(411,'parking_slots','127','baseline',NULL,NULL,'history installation',NULL,NULL,'{\"pos_x\": 2723, \"pos_y\": 1102, \"slot_id\": 127, \"zone_id\": 2, \"slot_code\": \"M-72\", \"created_at\": \"2026-10-14 03:53:01.000000\", \"slot_width\": 76, \"updated_at\": \"2026-10-14 03:53:01.000000\", \"is_pwd_slot\": 0, \"slot_height\": 360, \"slot_status\": \"available\", \"rotation_deg\": 68, \"vehicle_type_id\": 6, \"reserved_for_user_id\": null}','2026-10-23 15:29:57.726526'),(412,'parking_slots','128','baseline',NULL,NULL,'history installation',NULL,NULL,'{\"pos_x\": 2754, \"pos_y\": 1179, \"slot_id\": 128, \"zone_id\": 2, \"slot_code\": \"M-73\", \"created_at\": \"2026-10-14 03:53:01.000000\", \"slot_width\": 76, \"updated_at\": \"2026-10-14 03:53:01.000000\", \"is_pwd_slot\": 0, \"slot_height\": 360, \"slot_status\": \"available\", \"rotation_deg\": 68, \"vehicle_type_id\": 6, \"reserved_for_user_id\": null}','2026-10-23 15:29:57.726526'),(413,'parking_slots','129','baseline',NULL,NULL,'history installation',NULL,NULL,'{\"pos_x\": 2784, \"pos_y\": 1256, \"slot_id\": 129, \"zone_id\": 2, \"slot_code\": \"M-74\", \"created_at\": \"2026-10-14 03:53:01.000000\", \"slot_width\": 76, \"updated_at\": \"2026-10-14 03:53:01.000000\", \"is_pwd_slot\": 0, \"slot_height\": 360, \"slot_status\": \"available\", \"rotation_deg\": 68, \"vehicle_type_id\": 6, \"reserved_for_user_id\": null}','2026-10-23 15:29:57.726526'),(414,'parking_slots','130','baseline',NULL,NULL,'history installation',NULL,NULL,'{\"pos_x\": 2815, \"pos_y\": 1333, \"slot_id\": 130, \"zone_id\": 2, \"slot_code\": \"M-75\", \"created_at\": \"2026-10-14 03:53:01.000000\", \"slot_width\": 76, \"updated_at\": \"2026-10-14 03:53:01.000000\", \"is_pwd_slot\": 0, \"slot_height\": 360, \"slot_status\": \"available\", \"rotation_deg\": 68, \"vehicle_type_id\": 6, \"reserved_for_user_id\": null}','2026-10-23 15:29:57.726526'),(415,'parking_slots','131','baseline',NULL,NULL,'history installation',NULL,NULL,'{\"pos_x\": 2845, \"pos_y\": 1410, \"slot_id\": 131, \"zone_id\": 2, \"slot_code\": \"M-76\", \"created_at\": \"2026-10-14 03:53:01.000000\", \"slot_width\": 76, \"updated_at\": \"2026-10-14 03:53:01.000000\", \"is_pwd_slot\": 0, \"slot_height\": 360, \"slot_status\": \"available\", \"rotation_deg\": 68, \"vehicle_type_id\": 6, \"reserved_for_user_id\": null}','2026-10-23 15:29:57.726526'),(416,'parking_slots','132','baseline',NULL,NULL,'history installation',NULL,NULL,'{\"pos_x\": 2876, \"pos_y\": 1487, \"slot_id\": 132, \"zone_id\": 2, \"slot_code\": \"M-77\", \"created_at\": \"2026-10-14 03:53:01.000000\", \"slot_width\": 76, \"updated_at\": \"2026-10-14 03:53:01.000000\", \"is_pwd_slot\": 0, \"slot_height\": 360, \"slot_status\": \"available\", \"rotation_deg\": 68, \"vehicle_type_id\": 6, \"reserved_for_user_id\": null}','2026-10-23 15:29:57.726526'),(417,'parking_slots','133','baseline',NULL,NULL,'history installation',NULL,NULL,'{\"pos_x\": 2937, \"pos_y\": 1641, \"slot_id\": 133, \"zone_id\": 2, \"slot_code\": \"M-78\", \"created_at\": \"2026-10-14 03:53:01.000000\", \"slot_width\": 76, \"updated_at\": \"2026-10-14 03:53:01.000000\", \"is_pwd_slot\": 0, \"slot_height\": 360, \"slot_status\": \"available\", \"rotation_deg\": 68, \"vehicle_type_id\": 6, \"reserved_for_user_id\": null}','2026-10-23 15:29:57.726526'),(418,'parking_slots','134','baseline',NULL,NULL,'history installation',NULL,NULL,'{\"pos_x\": 2967, \"pos_y\": 1718, \"slot_id\": 134, \"zone_id\": 2, \"slot_code\": \"M-79\", \"created_at\": \"2026-10-14 03:53:01.000000\", \"slot_width\": 76, \"updated_at\": \"2026-10-14 03:53:01.000000\", \"is_pwd_slot\": 0, \"slot_height\": 360, \"slot_status\": \"available\", \"rotation_deg\": 68, \"vehicle_type_id\": 6, \"reserved_for_user_id\": null}','2026-10-23 15:29:57.726526'),(419,'parking_slots','135','baseline',NULL,NULL,'history installation',NULL,NULL,'{\"pos_x\": 2998, \"pos_y\": 1795, \"slot_id\": 135, \"zone_id\": 2, \"slot_code\": \"M-80\", \"created_at\": \"2026-10-14 03:53:01.000000\", \"slot_width\": 76, \"updated_at\": \"2026-10-14 04:02:01.000000\", \"is_pwd_slot\": 0, \"slot_height\": 360, \"slot_status\": \"maintenance\", \"rotation_deg\": 68, \"vehicle_type_id\": 6, \"reserved_for_user_id\": null}','2026-10-23 15:29:57.726526'),(420,'parking_slots','136','baseline',NULL,NULL,'history installation',NULL,NULL,'{\"pos_x\": 3028, \"pos_y\": 1872, \"slot_id\": 136, \"zone_id\": 2, \"slot_code\": \"M-81\", \"created_at\": \"2026-10-14 03:53:01.000000\", \"slot_width\": 76, \"updated_at\": \"2026-10-14 03:53:01.000000\", \"is_pwd_slot\": 0, \"slot_height\": 360, \"slot_status\": \"available\", \"rotation_deg\": 68, \"vehicle_type_id\": 6, \"reserved_for_user_id\": null}','2026-10-23 15:29:57.726526'),(421,'parking_slots','137','baseline',NULL,NULL,'history installation',NULL,NULL,'{\"pos_x\": 3089, \"pos_y\": 2026, \"slot_id\": 137, \"zone_id\": 2, \"slot_code\": \"M-82\", \"created_at\": \"2026-10-14 03:53:01.000000\", \"slot_width\": 76, \"updated_at\": \"2026-10-14 03:53:01.000000\", \"is_pwd_slot\": 0, \"slot_height\": 360, \"slot_status\": \"available\", \"rotation_deg\": 68, \"vehicle_type_id\": 6, \"reserved_for_user_id\": null}','2026-10-23 15:29:57.726526'),(422,'parking_slots','138','baseline',NULL,NULL,'history installation',NULL,NULL,'{\"pos_x\": 3120, \"pos_y\": 2103, \"slot_id\": 138, \"zone_id\": 2, \"slot_code\": \"M-83\", \"created_at\": \"2026-10-14 03:53:01.000000\", \"slot_width\": 76, \"updated_at\": \"2026-10-14 03:53:01.000000\", \"is_pwd_slot\": 0, \"slot_height\": 360, \"slot_status\": \"available\", \"rotation_deg\": 68, \"vehicle_type_id\": 6, \"reserved_for_user_id\": null}','2026-10-23 15:29:57.726526'),(423,'parking_slots','139','baseline',NULL,NULL,'history installation',NULL,NULL,'{\"pos_x\": 3150, \"pos_y\": 2180, \"slot_id\": 139, \"zone_id\": 2, \"slot_code\": \"M-84\", \"created_at\": \"2026-10-14 03:53:01.000000\", \"slot_width\": 76, \"updated_at\": \"2026-10-14 03:53:01.000000\", \"is_pwd_slot\": 0, \"slot_height\": 360, \"slot_status\": \"available\", \"rotation_deg\": 68, \"vehicle_type_id\": 6, \"reserved_for_user_id\": null}','2026-10-23 15:29:57.726526'),(540,'access_gates','8','baseline',NULL,NULL,'history installation',NULL,NULL,'{\"gate_id\": 8, \"gate_code\": \"MAIN-GATE\", \"gate_name\": \"Main Entrance / Exit\", \"gate_type\": \"both\", \"created_at\": null, \"updated_at\": \"2026-10-15 00:56:54.000000\", \"gate_status\": \"online\", \"parking_area_id\": null}','2026-10-23 15:29:57.788529'),(541,'departments','1','baseline',NULL,NULL,'history installation',NULL,NULL,'{\"is_active\": 1, \"department_id\": 1, \"department_code\": \"COE\", \"department_name\": \"College of Engineering\"}','2026-10-23 15:29:57.845401'),(542,'departments','2','baseline',NULL,NULL,'history installation',NULL,NULL,'{\"is_active\": 1, \"department_id\": 2, \"department_code\": \"COS\", \"department_name\": \"College of Science\"}','2026-10-23 15:29:57.845401'),(543,'departments','3','baseline',NULL,NULL,'history installation',NULL,NULL,'{\"is_active\": 1, \"department_id\": 3, \"department_code\": \"COB\", \"department_name\": \"College of Business\"}','2026-10-23 15:29:57.845401'),(544,'departments','4','baseline',NULL,NULL,'history installation',NULL,NULL,'{\"is_active\": 1, \"department_id\": 4, \"department_code\": \"COA\", \"department_name\": \"College of Arts\"}','2026-10-23 15:29:57.845401'),(545,'departments','5','baseline',NULL,NULL,'history installation',NULL,NULL,'{\"is_active\": 1, \"department_id\": 5, \"department_code\": \"CON\", \"department_name\": \"College of Nursing\"}','2026-10-23 15:29:57.845401'),(546,'departments','6','baseline',NULL,NULL,'history installation',NULL,NULL,'{\"is_active\": 1, \"department_id\": 6, \"department_code\": \"REG\", \"department_name\": \"Registrar Office\"}','2026-10-23 15:29:57.845401'),(547,'departments','7','baseline',NULL,NULL,'history installation',NULL,NULL,'{\"is_active\": 1, \"department_id\": 7, \"department_code\": \"HR\", \"department_name\": \"HR Department\"}','2026-10-23 15:29:57.845401'),(548,'departments','8','baseline',NULL,NULL,'history installation',NULL,NULL,'{\"is_active\": 1, \"department_id\": 8, \"department_code\": \"IT\", \"department_name\": \"IT Department\"}','2026-10-23 15:29:57.845401'),(549,'departments','9','baseline',NULL,NULL,'history installation',NULL,NULL,'{\"is_active\": 1, \"department_id\": 9, \"department_code\": \"SEC\", \"department_name\": \"Security Office\"}','2026-10-23 15:29:57.845401'),(556,'vehicle_types','1','baseline',NULL,NULL,'history installation',NULL,NULL,'{\"type_code\": \"sedan\", \"type_name\": \"Sedan\", \"parking_area_id\": 6, \"vehicle_type_id\": 1, \"requires_assigned_slot\": 0}','2026-10-23 15:29:57.892009'),(557,'vehicle_types','2','baseline',NULL,NULL,'history installation',NULL,NULL,'{\"type_code\": \"suv\", \"type_name\": \"SUV\", \"parking_area_id\": 6, \"vehicle_type_id\": 2, \"requires_assigned_slot\": 0}','2026-10-23 15:29:57.892009'),(558,'vehicle_types','3','baseline',NULL,NULL,'history installation',NULL,NULL,'{\"type_code\": \"hatchback\", \"type_name\": \"Hatchback\", \"parking_area_id\": 6, \"vehicle_type_id\": 3, \"requires_assigned_slot\": 0}','2026-10-23 15:29:57.892009'),(559,'vehicle_types','4','baseline',NULL,NULL,'history installation',NULL,NULL,'{\"type_code\": \"pickup\", \"type_name\": \"Pickup\", \"parking_area_id\": 6, \"vehicle_type_id\": 4, \"requires_assigned_slot\": 0}','2026-10-23 15:29:57.892009'),(560,'vehicle_types','5','baseline',NULL,NULL,'history installation',NULL,NULL,'{\"type_code\": \"van\", \"type_name\": \"Van\", \"parking_area_id\": 6, \"vehicle_type_id\": 5, \"requires_assigned_slot\": 0}','2026-10-23 15:29:57.892009'),(561,'vehicle_types','6','baseline',NULL,NULL,'history installation',NULL,NULL,'{\"type_code\": \"motorcycle\", \"type_name\": \"Motorcycle\", \"parking_area_id\": 7, \"vehicle_type_id\": 6, \"requires_assigned_slot\": 0}','2026-10-23 15:29:57.892009'),(563,'login_attempts','1','baseline',NULL,NULL,'history installation',NULL,NULL,'{\"user_id\": null, \"attempt_id\": 1, \"identifier\": \"security.demo@pass.local\", \"ip_address\": \"127.0.0.1\", \"user_agent\": \"Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36\", \"attempted_at\": \"2026-10-13 06:55:16.000000\", \"failure_reason\": \"invalid_credentials\", \"was_successful\": 0}','2026-10-23 15:29:57.948263'),(564,'login_attempts','2','baseline',NULL,NULL,'history installation',NULL,NULL,'{\"user_id\": null, \"attempt_id\": 2, \"identifier\": \"security.demo@pass.local\", \"ip_address\": \"127.0.0.1\", \"user_agent\": \"Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36\", \"attempted_at\": \"2026-10-13 06:55:19.000000\", \"failure_reason\": \"invalid_credentials\", \"was_successful\": 0}','2026-10-23 15:29:57.948263'),(565,'login_attempts','3','baseline',NULL,NULL,'history installation',NULL,NULL,'{\"user_id\": null, \"attempt_id\": 3, \"identifier\": \"security.demo@pass.local\", \"ip_address\": \"127.0.0.1\", \"user_agent\": \"Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36\", \"attempted_at\": \"2026-10-13 07:12:05.000000\", \"failure_reason\": \"invalid_credentials\", \"was_successful\": 0}','2026-10-23 15:29:57.948263'),(566,'login_attempts','4','baseline',NULL,NULL,'history installation',NULL,NULL,'{\"user_id\": null, \"attempt_id\": 4, \"identifier\": \"security.demo@pass.local\", \"ip_address\": \"127.0.0.1\", \"user_agent\": \"Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36\", \"attempted_at\": \"2026-10-13 07:12:07.000000\", \"failure_reason\": \"invalid_credentials\", \"was_successful\": 0}','2026-10-23 15:29:57.948263'),(567,'login_attempts','5','baseline',NULL,NULL,'history installation',NULL,NULL,'{\"user_id\": 1, \"attempt_id\": 5, \"identifier\": \"security.demo@pass.local\", \"ip_address\": \"127.0.0.1\", \"user_agent\": \"Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36\", \"attempted_at\": \"2026-10-13 07:17:10.000000\", \"failure_reason\": null, \"was_successful\": 1}','2026-10-23 15:29:57.948263'),(568,'login_attempts','6','baseline',NULL,NULL,'history installation',NULL,NULL,'{\"user_id\": 1, \"attempt_id\": 6, \"identifier\": \"security.demo@pass.local\", \"ip_address\": \"127.0.0.1\", \"user_agent\": \"Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Code/1.141.0 Chrome/150.0.7871.250 Electron/43.7.7 Safari/537.36\", \"attempted_at\": \"2026-10-13 12:54:18.000000\", \"failure_reason\": \"invalid_credentials\", \"was_successful\": 0}','2026-10-23 15:29:57.948263'),(569,'login_attempts','7','baseline',NULL,NULL,'history installation',NULL,NULL,'{\"user_id\": 1, \"attempt_id\": 7, \"identifier\": \"security.demo@pass.local\", \"ip_address\": \"127.0.0.1\", \"user_agent\": \"Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Code/1.141.0 Chrome/150.0.7871.250 Electron/43.7.7 Safari/537.36\", \"attempted_at\": \"2026-10-13 12:54:34.000000\", \"failure_reason\": null, \"was_successful\": 1}','2026-10-23 15:29:57.948263'),(570,'login_attempts','8','baseline',NULL,NULL,'history installation',NULL,NULL,'{\"user_id\": 1, \"attempt_id\": 8, \"identifier\": \"security.demo@pass.local\", \"ip_address\": \"127.0.0.1\", \"user_agent\": \"Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Code/1.141.0 Chrome/150.0.7871.250 Electron/43.7.7 Safari/537.36\", \"attempted_at\": \"2026-10-13 15:23:58.000000\", \"failure_reason\": null, \"was_successful\": 1}','2026-10-23 15:29:57.948263'),(571,'login_attempts','9','baseline',NULL,NULL,'history installation',NULL,NULL,'{\"user_id\": 1, \"attempt_id\": 9, \"identifier\": \"security.demo@pass.local\", \"ip_address\": \"127.0.0.1\", \"user_agent\": \"Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36\", \"attempted_at\": \"2026-10-14 00:06:25.000000\", \"failure_reason\": null, \"was_successful\": 1}','2026-10-23 15:29:57.948263'),(572,'login_attempts','10','baseline',NULL,NULL,'history installation',NULL,NULL,'{\"user_id\": 1, \"attempt_id\": 10, \"identifier\": \"security.demo@pass.local\", \"ip_address\": \"127.0.0.1\", \"user_agent\": \"Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36\", \"attempted_at\": \"2026-10-14 00:23:32.000000\", \"failure_reason\": null, \"was_successful\": 1}','2026-10-23 15:29:57.948263'),(573,'login_attempts','18','baseline',NULL,NULL,'history installation',NULL,NULL,'{\"user_id\": 1, \"attempt_id\": 18, \"identifier\": \"SEC-DEMO-001\", \"ip_address\": \"127.0.0.1\", \"user_agent\": \"GuzzleHttp/7\", \"attempted_at\": \"2026-10-14 00:25:40.000000\", \"failure_reason\": null, \"was_successful\": 1}','2026-10-23 15:29:57.948263'),(574,'login_attempts','19','baseline',NULL,NULL,'history installation',NULL,NULL,'{\"user_id\": 1, \"attempt_id\": 19, \"identifier\": \"security.demo@pass.local\", \"ip_address\": \"127.0.0.1\", \"user_agent\": \"Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Code/1.141.0 Chrome/150.0.7871.250 Electron/43.7.7 Safari/537.36\", \"attempted_at\": \"2026-10-14 00:31:26.000000\", \"failure_reason\": null, \"was_successful\": 1}','2026-10-23 15:29:57.948263'),(575,'login_attempts','27','baseline',NULL,NULL,'history installation',NULL,NULL,'{\"user_id\": 1, \"attempt_id\": 27, \"identifier\": \"SEC-DEMO-001\", \"ip_address\": \"127.0.0.1\", \"user_agent\": \"GuzzleHttp/7\", \"attempted_at\": \"2026-10-14 00:34:40.000000\", \"failure_reason\": null, \"was_successful\": 1}','2026-10-23 15:29:57.948263'),(576,'login_attempts','28','baseline',NULL,NULL,'history installation',NULL,NULL,'{\"user_id\": 1, \"attempt_id\": 28, \"identifier\": \"SEC-DEMO-001\", \"ip_address\": \"127.0.0.1\", \"user_agent\": \"GuzzleHttp/7\", \"attempted_at\": \"2026-10-14 01:17:15.000000\", \"failure_reason\": null, \"was_successful\": 1}','2026-10-23 15:29:57.948263'),(577,'login_attempts','29','baseline',NULL,NULL,'history installation',NULL,NULL,'{\"user_id\": 1, \"attempt_id\": 29, \"identifier\": \"security.demo@pass.local\", \"ip_address\": \"127.0.0.1\", \"user_agent\": \"Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Code/1.141.0 Chrome/150.0.7871.250 Electron/43.7.7 Safari/537.36\", \"attempted_at\": \"2026-10-14 02:00:23.000000\", \"failure_reason\": null, \"was_successful\": 1}','2026-10-23 15:29:57.948263'),(578,'login_attempts','30','baseline',NULL,NULL,'history installation',NULL,NULL,'{\"user_id\": 1, \"attempt_id\": 30, \"identifier\": \"security.demo@pass.local\", \"ip_address\": \"127.0.0.1\", \"user_agent\": \"Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36\", \"attempted_at\": \"2026-10-14 02:02:28.000000\", \"failure_reason\": null, \"was_successful\": 1}','2026-10-23 15:29:57.948263'),(579,'login_attempts','31','baseline',NULL,NULL,'history installation',NULL,NULL,'{\"user_id\": 1, \"attempt_id\": 31, \"identifier\": \"security.demo@pass.local\", \"ip_address\": \"127.0.0.1\", \"user_agent\": \"Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Code/1.141.0 Chrome/150.0.7871.250 Electron/43.7.7 Safari/537.36\", \"attempted_at\": \"2026-10-14 04:03:30.000000\", \"failure_reason\": null, \"was_successful\": 1}','2026-10-23 15:29:57.948263'),(580,'login_attempts','32','baseline',NULL,NULL,'history installation',NULL,NULL,'{\"user_id\": 1, \"attempt_id\": 32, \"identifier\": \"SEC-DEMO-001\", \"ip_address\": \"127.0.0.1\", \"user_agent\": \"GuzzleHttp/7\", \"attempted_at\": \"2026-10-14 04:08:09.000000\", \"failure_reason\": null, \"was_successful\": 1}','2026-10-23 15:29:57.948263'),(581,'login_attempts','33','baseline',NULL,NULL,'history installation',NULL,NULL,'{\"user_id\": 36, \"attempt_id\": 33, \"identifier\": \"SAMPLE-EMP-001\", \"ip_address\": \"127.0.0.1\", \"user_agent\": \"GuzzleHttp/7\", \"attempted_at\": \"2026-10-14 04:08:10.000000\", \"failure_reason\": null, \"was_successful\": 1}','2026-10-23 15:29:57.948263'),(582,'login_attempts','34','baseline',NULL,NULL,'history installation',NULL,NULL,'{\"user_id\": 1, \"attempt_id\": 34, \"identifier\": \"SEC-DEMO-001\", \"ip_address\": \"127.0.0.1\", \"user_agent\": \"GuzzleHttp/7\", \"attempted_at\": \"2026-10-14 04:09:49.000000\", \"failure_reason\": null, \"was_successful\": 1}','2026-10-23 15:29:57.948263'),(583,'login_attempts','35','baseline',NULL,NULL,'history installation',NULL,NULL,'{\"user_id\": 36, \"attempt_id\": 35, \"identifier\": \"SAMPLE-EMP-001\", \"ip_address\": \"127.0.0.1\", \"user_agent\": \"GuzzleHttp/7\", \"attempted_at\": \"2026-10-14 04:09:49.000000\", \"failure_reason\": null, \"was_successful\": 1}','2026-10-23 15:29:57.948263'),(584,'login_attempts','36','baseline',NULL,NULL,'history installation',NULL,NULL,'{\"user_id\": 32, \"attempt_id\": 36, \"identifier\": \"SAMPLE-STU-001\", \"ip_address\": \"127.0.0.1\", \"user_agent\": \"GuzzleHttp/7\", \"attempted_at\": \"2026-10-14 04:10:14.000000\", \"failure_reason\": null, \"was_successful\": 1}','2026-10-23 15:29:57.948263'),(585,'login_attempts','37','baseline',NULL,NULL,'history installation',NULL,NULL,'{\"user_id\": 33, \"attempt_id\": 37, \"identifier\": \"SAMPLE-STU-002\", \"ip_address\": \"127.0.0.1\", \"user_agent\": \"GuzzleHttp/7\", \"attempted_at\": \"2026-10-14 04:10:14.000000\", \"failure_reason\": null, \"was_successful\": 1}','2026-10-23 15:29:57.948263'),(586,'login_attempts','38','baseline',NULL,NULL,'history installation',NULL,NULL,'{\"user_id\": 34, \"attempt_id\": 38, \"identifier\": \"SAMPLE-FAC-001\", \"ip_address\": \"127.0.0.1\", \"user_agent\": \"GuzzleHttp/7\", \"attempted_at\": \"2026-10-14 04:10:15.000000\", \"failure_reason\": null, \"was_successful\": 1}','2026-10-23 15:29:57.948263'),(587,'login_attempts','39','baseline',NULL,NULL,'history installation',NULL,NULL,'{\"user_id\": 35, \"attempt_id\": 39, \"identifier\": \"SAMPLE-FAC-002\", \"ip_address\": \"127.0.0.1\", \"user_agent\": \"GuzzleHttp/7\", \"attempted_at\": \"2026-10-14 04:10:15.000000\", \"failure_reason\": null, \"was_successful\": 1}','2026-10-23 15:29:57.948263'),(588,'login_attempts','40','baseline',NULL,NULL,'history installation',NULL,NULL,'{\"user_id\": 36, \"attempt_id\": 40, \"identifier\": \"SAMPLE-EMP-001\", \"ip_address\": \"127.0.0.1\", \"user_agent\": \"GuzzleHttp/7\", \"attempted_at\": \"2026-10-14 04:10:16.000000\", \"failure_reason\": null, \"was_successful\": 1}','2026-10-23 15:29:57.948263'),(589,'login_attempts','41','baseline',NULL,NULL,'history installation',NULL,NULL,'{\"user_id\": 1, \"attempt_id\": 41, \"identifier\": \"SEC-DEMO-001\", \"ip_address\": \"127.0.0.1\", \"user_agent\": \"GuzzleHttp/7\", \"attempted_at\": \"2026-10-14 05:10:31.000000\", \"failure_reason\": null, \"was_successful\": 1}','2026-10-23 15:29:57.948263'),(590,'login_attempts','42','baseline',NULL,NULL,'history installation',NULL,NULL,'{\"user_id\": 36, \"attempt_id\": 42, \"identifier\": \"SAMPLE-EMP-001\", \"ip_address\": \"127.0.0.1\", \"user_agent\": \"GuzzleHttp/7\", \"attempted_at\": \"2026-10-14 05:10:31.000000\", \"failure_reason\": null, \"was_successful\": 1}','2026-10-23 15:29:57.948263'),(591,'login_attempts','43','baseline',NULL,NULL,'history installation',NULL,NULL,'{\"user_id\": 33, \"attempt_id\": 43, \"identifier\": \"SAMPLE-STU-002\", \"ip_address\": \"127.0.0.1\", \"user_agent\": \"GuzzleHttp/7\", \"attempted_at\": \"2026-10-14 05:10:55.000000\", \"failure_reason\": null, \"was_successful\": 1}','2026-10-23 15:29:57.948263'),(592,'login_attempts','44','baseline',NULL,NULL,'history installation',NULL,NULL,'{\"user_id\": 1, \"attempt_id\": 44, \"identifier\": \"security.demo@pass.local\", \"ip_address\": \"127.0.0.1\", \"user_agent\": \"PostmanRuntime/2.11.2\", \"attempted_at\": \"2026-10-14 21:07:37.000000\", \"failure_reason\": null, \"was_successful\": 1}','2026-10-23 15:29:57.948263'),(593,'login_attempts','45','baseline',NULL,NULL,'history installation',NULL,NULL,'{\"user_id\": 1, \"attempt_id\": 45, \"identifier\": \"security.demo@pass.local\", \"ip_address\": \"127.0.0.1\", \"user_agent\": \"Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36 Edg/155.0.0.0\", \"attempted_at\": \"2026-10-14 21:30:51.000000\", \"failure_reason\": null, \"was_successful\": 1}','2026-10-23 15:29:57.948263'),(594,'login_attempts','46','baseline',NULL,NULL,'history installation',NULL,NULL,'{\"user_id\": 1, \"attempt_id\": 46, \"identifier\": \"security.demo@pass.local\", \"ip_address\": \"127.0.0.1\", \"user_agent\": \"PostmanRuntime/2.11.2\", \"attempted_at\": \"2026-10-14 21:52:55.000000\", \"failure_reason\": null, \"was_successful\": 1}','2026-10-23 15:29:57.948263'),(595,'login_attempts','47','baseline',NULL,NULL,'history installation',NULL,NULL,'{\"user_id\": 1, \"attempt_id\": 47, \"identifier\": \"security.demo@pass.local\", \"ip_address\": \"127.0.0.1\", \"user_agent\": \"PostmanRuntime/2.11.2\", \"attempted_at\": \"2026-10-14 21:53:39.000000\", \"failure_reason\": null, \"was_successful\": 1}','2026-10-23 15:29:57.948263'),(596,'login_attempts','48','baseline',NULL,NULL,'history installation',NULL,NULL,'{\"user_id\": 1, \"attempt_id\": 48, \"identifier\": \"security.demo@pass.local\", \"ip_address\": \"127.0.0.1\", \"user_agent\": \"PostmanRuntime/2.11.2\", \"attempted_at\": \"2026-10-14 21:54:07.000000\", \"failure_reason\": null, \"was_successful\": 1}','2026-10-23 15:29:57.948263'),(597,'login_attempts','49','baseline',NULL,NULL,'history installation',NULL,NULL,'{\"user_id\": 1, \"attempt_id\": 49, \"identifier\": \"security.demo@pass.local\", \"ip_address\": \"127.0.0.1\", \"user_agent\": \"PostmanRuntime/2.11.2\", \"attempted_at\": \"2026-10-14 21:54:09.000000\", \"failure_reason\": null, \"was_successful\": 1}','2026-10-23 15:29:57.948263'),(598,'login_attempts','50','baseline',NULL,NULL,'history installation',NULL,NULL,'{\"user_id\": null, \"attempt_id\": 50, \"identifier\": \"x@x\", \"ip_address\": \"127.0.0.1\", \"user_agent\": \"curl/8.18.0\", \"attempted_at\": \"2026-10-14 21:57:49.000000\", \"failure_reason\": \"invalid_credentials\", \"was_successful\": 0}','2026-10-23 15:29:57.948263'),(599,'login_attempts','51','baseline',NULL,NULL,'history installation',NULL,NULL,'{\"user_id\": 1, \"attempt_id\": 51, \"identifier\": \"security.demo@pass.local\", \"ip_address\": \"127.0.0.1\", \"user_agent\": \"Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36 Edg/155.0.0.0\", \"attempted_at\": \"2026-10-14 22:00:15.000000\", \"failure_reason\": null, \"was_successful\": 1}','2026-10-23 15:29:57.948263'),(600,'login_attempts','52','baseline',NULL,NULL,'history installation',NULL,NULL,'{\"user_id\": 1, \"attempt_id\": 52, \"identifier\": \"security.demo@pass.local\", \"ip_address\": \"127.0.0.1\", \"user_agent\": \"Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36 Edg/155.0.0.0\", \"attempted_at\": \"2026-10-14 22:08:43.000000\", \"failure_reason\": null, \"was_successful\": 1}','2026-10-23 15:29:57.948263'),(601,'login_attempts','53','baseline',NULL,NULL,'history installation',NULL,NULL,'{\"user_id\": 1, \"attempt_id\": 53, \"identifier\": \"security.demo@pass.local\", \"ip_address\": \"127.0.0.1\", \"user_agent\": \"Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Code/1.141.2 Chrome/150.0.7871.250 Electron/43.7.7 Safari/537.36\", \"attempted_at\": \"2026-10-14 23:49:08.000000\", \"failure_reason\": null, \"was_successful\": 1}','2026-10-23 15:29:57.948263'),(602,'login_attempts','54','baseline',NULL,NULL,'history installation',NULL,NULL,'{\"user_id\": 1, \"attempt_id\": 54, \"identifier\": \"security.demo@pass.local\", \"ip_address\": \"127.0.0.1\", \"user_agent\": \"Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36\", \"attempted_at\": \"2026-10-14 23:51:36.000000\", \"failure_reason\": null, \"was_successful\": 1}','2026-10-23 15:29:57.948263'),(603,'login_attempts','55','baseline',NULL,NULL,'history installation',NULL,NULL,'{\"user_id\": 1, \"attempt_id\": 55, \"identifier\": \"security.demo@pass.local\", \"ip_address\": \"127.0.0.1\", \"user_agent\": \"Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36\", \"attempted_at\": \"2026-10-15 00:17:18.000000\", \"failure_reason\": null, \"was_successful\": 1}','2026-10-23 15:29:57.948263'),(604,'login_attempts','56','baseline',NULL,NULL,'history installation',NULL,NULL,'{\"user_id\": null, \"attempt_id\": 56, \"identifier\": \"ecurity.demo@pass.local\", \"ip_address\": \"127.0.0.1\", \"user_agent\": \"Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Code/1.141.2 Chrome/150.0.7871.250 Electron/43.7.7 Safari/537.36\", \"attempted_at\": \"2026-10-15 00:35:13.000000\", \"failure_reason\": \"invalid_credentials\", \"was_successful\": 0}','2026-10-23 15:29:57.948263'),(605,'login_attempts','57','baseline',NULL,NULL,'history installation',NULL,NULL,'{\"user_id\": 1, \"attempt_id\": 57, \"identifier\": \"security.demo@pass.local\", \"ip_address\": \"127.0.0.1\", \"user_agent\": \"Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Code/1.141.2 Chrome/150.0.7871.250 Electron/43.7.7 Safari/537.36\", \"attempted_at\": \"2026-10-15 00:35:21.000000\", \"failure_reason\": null, \"was_successful\": 1}','2026-10-23 15:29:57.948263'),(606,'login_attempts','58','baseline',NULL,NULL,'history installation',NULL,NULL,'{\"user_id\": 1, \"attempt_id\": 58, \"identifier\": \"security.demo@pass.local\", \"ip_address\": \"127.0.0.1\", \"user_agent\": \"Mozilla/5.0 (Windows NT; Windows NT 10.0; en-PH) WindowsPowerShell/5.1.26100.9444\", \"attempted_at\": \"2026-10-15 01:30:59.000000\", \"failure_reason\": null, \"was_successful\": 1}','2026-10-23 15:29:57.948263'),(607,'login_attempts','59','baseline',NULL,NULL,'history installation',NULL,NULL,'{\"user_id\": 1, \"attempt_id\": 59, \"identifier\": \"security.demo@pass.local\", \"ip_address\": \"127.0.0.1\", \"user_agent\": \"Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Code/1.141.2 Chrome/150.0.7871.250 Electron/43.7.7 Safari/537.36\", \"attempted_at\": \"2026-10-15 01:31:42.000000\", \"failure_reason\": null, \"was_successful\": 1}','2026-10-23 15:29:57.948263'),(608,'login_attempts','60','baseline',NULL,NULL,'history installation',NULL,NULL,'{\"user_id\": 1, \"attempt_id\": 60, \"identifier\": \"security.demo@pass.local\", \"ip_address\": \"127.0.0.1\", \"user_agent\": \"Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Code/1.141.2 Chrome/150.0.7871.250 Electron/43.7.7 Safari/537.36\", \"attempted_at\": \"2026-10-15 02:04:13.000000\", \"failure_reason\": null, \"was_successful\": 1}','2026-10-23 15:29:57.948263'),(609,'login_attempts','61','baseline',NULL,NULL,'history installation',NULL,NULL,'{\"user_id\": 1, \"attempt_id\": 61, \"identifier\": \"security.demo@pass.local\", \"ip_address\": \"127.0.0.1\", \"user_agent\": \"Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Code/1.141.2 Chrome/150.0.7871.250 Electron/43.7.7 Safari/537.36\", \"attempted_at\": \"2026-10-15 02:36:21.000000\", \"failure_reason\": null, \"was_successful\": 1}','2026-10-23 15:29:57.948263'),(610,'login_attempts','62','baseline',NULL,NULL,'history installation',NULL,NULL,'{\"user_id\": 1, \"attempt_id\": 62, \"identifier\": \"security.demo@pass.local\", \"ip_address\": \"127.0.0.1\", \"user_agent\": \"Mozilla/5.0 (Windows NT; Windows NT 10.0; en-PH) WindowsPowerShell/5.1.26100.9444\", \"attempted_at\": \"2026-10-15 02:51:24.000000\", \"failure_reason\": null, \"was_successful\": 1}','2026-10-23 15:29:57.948263'),(611,'login_attempts','63','baseline',NULL,NULL,'history installation',NULL,NULL,'{\"user_id\": 1, \"attempt_id\": 63, \"identifier\": \"security.demo@pass.local\", \"ip_address\": \"127.0.0.1\", \"user_agent\": \"Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36\", \"attempted_at\": \"2026-10-15 03:05:19.000000\", \"failure_reason\": null, \"was_successful\": 1}','2026-10-23 15:29:57.948263'),(612,'login_attempts','64','baseline',NULL,NULL,'history installation',NULL,NULL,'{\"user_id\": 1, \"attempt_id\": 64, \"identifier\": \"security.demo@pass.local\", \"ip_address\": \"127.0.0.1\", \"user_agent\": \"Mozilla/5.0 (Windows NT; Windows NT 10.0; en-PH) WindowsPowerShell/5.1.26100.9444\", \"attempted_at\": \"2026-10-15 04:59:46.000000\", \"failure_reason\": null, \"was_successful\": 1}','2026-10-23 15:29:57.948263'),(613,'login_attempts','65','baseline',NULL,NULL,'history installation',NULL,NULL,'{\"user_id\": 1, \"attempt_id\": 65, \"identifier\": \"security.demo@pass.local\", \"ip_address\": \"127.0.0.1\", \"user_agent\": \"Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36\", \"attempted_at\": \"2026-10-15 05:08:41.000000\", \"failure_reason\": \"invalid_credentials\", \"was_successful\": 0}','2026-10-23 15:29:57.948263'),(614,'login_attempts','66','baseline',NULL,NULL,'history installation',NULL,NULL,'{\"user_id\": 1, \"attempt_id\": 66, \"identifier\": \"security.demo@pass.local\", \"ip_address\": \"127.0.0.1\", \"user_agent\": \"Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36\", \"attempted_at\": \"2026-10-15 05:08:55.000000\", \"failure_reason\": \"invalid_credentials\", \"was_successful\": 0}','2026-10-23 15:29:57.948263'),(615,'login_attempts','67','baseline',NULL,NULL,'history installation',NULL,NULL,'{\"user_id\": 1, \"attempt_id\": 67, \"identifier\": \"security.demo@pass.local\", \"ip_address\": \"127.0.0.1\", \"user_agent\": \"Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36\", \"attempted_at\": \"2026-10-15 05:08:59.000000\", \"failure_reason\": \"invalid_credentials\", \"was_successful\": 0}','2026-10-23 15:29:57.948263'),(616,'login_attempts','68','baseline',NULL,NULL,'history installation',NULL,NULL,'{\"user_id\": 1, \"attempt_id\": 68, \"identifier\": \"security.demo@pass.local\", \"ip_address\": \"127.0.0.1\", \"user_agent\": \"Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36\", \"attempted_at\": \"2026-10-15 05:09:10.000000\", \"failure_reason\": null, \"was_successful\": 1}','2026-10-23 15:29:57.948263'),(617,'login_attempts','69','baseline',NULL,NULL,'history installation',NULL,NULL,'{\"user_id\": null, \"attempt_id\": 69, \"identifier\": \"mali@gmail.com\", \"ip_address\": \"127.0.0.1\", \"user_agent\": \"Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36\", \"attempted_at\": \"2026-10-15 05:59:21.000000\", \"failure_reason\": \"invalid_credentials\", \"was_successful\": 0}','2026-10-23 15:29:57.948263'),(618,'login_attempts','70','baseline',NULL,NULL,'history installation',NULL,NULL,'{\"user_id\": null, \"attempt_id\": 70, \"identifier\": \"mali@gmail.com\", \"ip_address\": \"127.0.0.1\", \"user_agent\": \"Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36\", \"attempted_at\": \"2026-10-15 06:00:21.000000\", \"failure_reason\": \"invalid_credentials\", \"was_successful\": 0}','2026-10-23 15:29:57.948263'),(619,'login_attempts','71','baseline',NULL,NULL,'history installation',NULL,NULL,'{\"user_id\": null, \"attempt_id\": 71, \"identifier\": \"mali@gmail.com\", \"ip_address\": \"127.0.0.1\", \"user_agent\": \"Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36\", \"attempted_at\": \"2026-10-15 07:47:05.000000\", \"failure_reason\": \"invalid_credentials\", \"was_successful\": 0}','2026-10-23 15:29:57.948263'),(620,'login_attempts','72','baseline',NULL,NULL,'history installation',NULL,NULL,'{\"user_id\": 48, \"attempt_id\": 72, \"identifier\": \"rosa.villanueva.test@example.com\", \"ip_address\": \"127.0.0.1\", \"user_agent\": \"Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/155.0.0.0 Safari/537.36 Edg/155.0.0.0\", \"attempted_at\": \"2026-10-15 08:24:26.000000\", \"failure_reason\": null, \"was_successful\": 1}','2026-10-23 15:29:57.948263'),(621,'login_attempts','73','baseline',NULL,NULL,'history installation',NULL,NULL,'{\"user_id\": 46, \"attempt_id\": 73, \"identifier\": \"admin.demo@pass.local\", \"ip_address\": \"127.0.0.1\", \"user_agent\": \"Mozilla/5.0 (Windows NT; Windows NT 10.0; en-PH) WindowsPowerShell/5.1.26100.9444\", \"attempted_at\": \"2026-10-15 08:25:42.000000\", \"failure_reason\": \"invalid_credentials\", \"was_successful\": 0}','2026-10-23 15:29:57.948263'),(622,'login_attempts','74','baseline',NULL,NULL,'history installation',NULL,NULL,'{\"user_id\": 46, \"attempt_id\": 74, \"identifier\": \"admin.demo@pass.local\", \"ip_address\": \"127.0.0.1\", \"user_agent\": \"Mozilla/5.0 (Windows NT; Windows NT 10.0; en-PH) WindowsPowerShell/5.1.26100.9444\", \"attempted_at\": \"2026-10-15 08:26:45.000000\", \"failure_reason\": null, \"was_successful\": 1}','2026-10-23 15:29:57.948263'),(623,'login_attempts','75','baseline',NULL,NULL,'history installation',NULL,NULL,'{\"user_id\": 48, \"attempt_id\": 75, \"identifier\": \"rosa.villanueva.test@example.com\", \"ip_address\": \"127.0.0.1\", \"user_agent\": \"Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/155.0.0.0 Safari/537.36 Edg/155.0.0.0\", \"attempted_at\": \"2026-10-15 08:27:34.000000\", \"failure_reason\": null, \"was_successful\": 1}','2026-10-23 15:29:57.948263'),(624,'login_attempts','76','baseline',NULL,NULL,'history installation',NULL,NULL,'{\"user_id\": 1, \"attempt_id\": 76, \"identifier\": \"security.demo@pass.local\", \"ip_address\": \"127.0.0.1\", \"user_agent\": \"Mozilla/5.0 (Windows NT; Windows NT 10.0; en-PH) WindowsPowerShell/5.1.26100.9444\", \"attempted_at\": \"2026-10-15 08:28:32.000000\", \"failure_reason\": null, \"was_successful\": 1}','2026-10-23 15:29:57.948263'),(625,'login_attempts','77','baseline',NULL,NULL,'history installation',NULL,NULL,'{\"user_id\": 48, \"attempt_id\": 77, \"identifier\": \"rosa.villanueva.test@example.com\", \"ip_address\": \"127.0.0.1\", \"user_agent\": \"Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/155.0.0.0 Safari/537.36 Edg/155.0.0.0\", \"attempted_at\": \"2026-10-15 08:30:28.000000\", \"failure_reason\": null, \"was_successful\": 1}','2026-10-23 15:29:57.948263'),(626,'login_attempts','78','baseline',NULL,NULL,'history installation',NULL,NULL,'{\"user_id\": 49, \"attempt_id\": 78, \"identifier\": \"guest@pass.com\", \"ip_address\": \"127.0.0.1\", \"user_agent\": \"Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Code/1.141.2 Chrome/150.0.7871.250 Electron/43.7.7 Safari/537.36\", \"attempted_at\": \"2026-10-15 09:39:01.000000\", \"failure_reason\": null, \"was_successful\": 1}','2026-10-23 15:29:57.948263'),(627,'login_attempts','79','baseline',NULL,NULL,'history installation',NULL,NULL,'{\"user_id\": null, \"attempt_id\": 79, \"identifier\": \"admin@pass.edu\", \"ip_address\": \"127.0.0.1\", \"user_agent\": \"Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36\", \"attempted_at\": \"2026-10-15 10:18:09.000000\", \"failure_reason\": \"invalid_credentials\", \"was_successful\": 0}','2026-10-23 15:29:57.948263'),(628,'login_attempts','80','baseline',NULL,NULL,'history installation',NULL,NULL,'{\"user_id\": null, \"attempt_id\": 80, \"identifier\": \"admin@pass.edu\", \"ip_address\": \"127.0.0.1\", \"user_agent\": \"Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36\", \"attempted_at\": \"2026-10-15 10:18:17.000000\", \"failure_reason\": \"invalid_credentials\", \"was_successful\": 0}','2026-10-23 15:29:57.948263'),(629,'login_attempts','81','baseline',NULL,NULL,'history installation',NULL,NULL,'{\"user_id\": null, \"attempt_id\": 81, \"identifier\": \"admin@pass.edu\", \"ip_address\": \"127.0.0.1\", \"user_agent\": \"Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36\", \"attempted_at\": \"2026-10-15 10:18:31.000000\", \"failure_reason\": \"invalid_credentials\", \"was_successful\": 0}','2026-10-23 15:29:57.948263'),(630,'login_attempts','82','baseline',NULL,NULL,'history installation',NULL,NULL,'{\"user_id\": 1, \"attempt_id\": 82, \"identifier\": \"security.demo@pass.local\", \"ip_address\": \"127.0.0.1\", \"user_agent\": \"Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36\", \"attempted_at\": \"2026-10-15 10:42:48.000000\", \"failure_reason\": null, \"was_successful\": 1}','2026-10-23 15:29:57.948263'),(631,'login_attempts','83','baseline',NULL,NULL,'history installation',NULL,NULL,'{\"user_id\": 1, \"attempt_id\": 83, \"identifier\": \"security.demo@pass.local\", \"ip_address\": \"127.0.0.1\", \"user_agent\": \"Mozilla/5.0 (Windows NT; Windows NT 10.0; en-PH) WindowsPowerShell/5.1.26100.9444\", \"attempted_at\": \"2026-10-15 11:03:28.000000\", \"failure_reason\": null, \"was_successful\": 1}','2026-10-23 15:29:57.948263'),(632,'login_attempts','84','baseline',NULL,NULL,'history installation',NULL,NULL,'{\"user_id\": 1, \"attempt_id\": 84, \"identifier\": \"security.demo@pass.local\", \"ip_address\": \"127.0.0.1\", \"user_agent\": \"Mozilla/5.0 (Windows NT; Windows NT 10.0; en-PH) WindowsPowerShell/5.1.26100.9444\", \"attempted_at\": \"2026-10-15 11:03:28.000000\", \"failure_reason\": null, \"was_successful\": 1}','2026-10-23 15:29:57.948263'),(633,'login_attempts','85','baseline',NULL,NULL,'history installation',NULL,NULL,'{\"user_id\": 1, \"attempt_id\": 85, \"identifier\": \"security.demo@pass.local\", \"ip_address\": \"127.0.0.1\", \"user_agent\": \"Mozilla/5.0 (Windows NT; Windows NT 10.0; en-PH) WindowsPowerShell/5.1.26100.9444\", \"attempted_at\": \"2026-10-15 11:03:29.000000\", \"failure_reason\": null, \"was_successful\": 1}','2026-10-23 15:29:57.948263'),(634,'login_attempts','86','baseline',NULL,NULL,'history installation',NULL,NULL,'{\"user_id\": 50, \"attempt_id\": 86, \"identifier\": \"lara.gomez.test@example.com\", \"ip_address\": \"127.0.0.1\", \"user_agent\": \"Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/155.0.0.0 Safari/537.36 Edg/155.0.0.0\", \"attempted_at\": \"2026-10-15 11:33:19.000000\", \"failure_reason\": null, \"was_successful\": 1}','2026-10-23 15:29:57.948263'),(635,'login_attempts','87','baseline',NULL,NULL,'history installation',NULL,NULL,'{\"user_id\": 46, \"attempt_id\": 87, \"identifier\": \"admin.demo@pass.local\", \"ip_address\": \"127.0.0.1\", \"user_agent\": \"Mozilla/5.0 (Windows NT; Windows NT 10.0; en-PH) WindowsPowerShell/5.1.26100.9444\", \"attempted_at\": \"2026-10-15 11:33:35.000000\", \"failure_reason\": null, \"was_successful\": 1}','2026-10-23 15:29:57.948263'),(636,'login_attempts','88','baseline',NULL,NULL,'history installation',NULL,NULL,'{\"user_id\": 50, \"attempt_id\": 88, \"identifier\": \"lara.gomez.test@example.com\", \"ip_address\": \"127.0.0.1\", \"user_agent\": \"Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/155.0.0.0 Safari/537.36 Edg/155.0.0.0\", \"attempted_at\": \"2026-10-15 11:34:05.000000\", \"failure_reason\": null, \"was_successful\": 1}','2026-10-23 15:29:57.948263'),(637,'login_attempts','89','baseline',NULL,NULL,'history installation',NULL,NULL,'{\"user_id\": 50, \"attempt_id\": 89, \"identifier\": \"lara.gomez.test@example.com\", \"ip_address\": \"127.0.0.1\", \"user_agent\": \"Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/155.0.0.0 Safari/537.36 Edg/155.0.0.0\", \"attempted_at\": \"2026-10-15 11:35:35.000000\", \"failure_reason\": null, \"was_successful\": 1}','2026-10-23 15:29:57.948263'),(638,'login_attempts','90','baseline',NULL,NULL,'history installation',NULL,NULL,'{\"user_id\": 1, \"attempt_id\": 90, \"identifier\": \"security.demo@pass.local\", \"ip_address\": \"127.0.0.1\", \"user_agent\": \"Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Code/1.141.2 Chrome/150.0.7871.250 Electron/43.7.7 Safari/537.36\", \"attempted_at\": \"2026-10-15 11:46:24.000000\", \"failure_reason\": null, \"was_successful\": 1}','2026-10-23 15:29:57.948263'),(639,'login_attempts','91','baseline',NULL,NULL,'history installation',NULL,NULL,'{\"user_id\": 1, \"attempt_id\": 91, \"identifier\": \"security.demo@pass.local\", \"ip_address\": \"127.0.0.1\", \"user_agent\": \"Mozilla/5.0 (Windows NT; Windows NT 10.0; en-PH) WindowsPowerShell/5.1.26100.9444\", \"attempted_at\": \"2026-10-15 16:33:20.000000\", \"failure_reason\": null, \"was_successful\": 1}','2026-10-23 15:29:57.948263'),(640,'login_attempts','92','baseline',NULL,NULL,'history installation',NULL,NULL,'{\"user_id\": 1, \"attempt_id\": 92, \"identifier\": \"security.demo@pass.local\", \"ip_address\": \"127.0.0.1\", \"user_agent\": \"Mozilla/5.0 (Windows NT; Windows NT 10.0; en-PH) WindowsPowerShell/5.1.26100.9444\", \"attempted_at\": \"2026-10-15 16:38:30.000000\", \"failure_reason\": null, \"was_successful\": 1}','2026-10-23 15:29:57.948263'),(641,'login_attempts','93','baseline',NULL,NULL,'history installation',NULL,NULL,'{\"user_id\": 46, \"attempt_id\": 93, \"identifier\": \"admin.demo@pass.local\", \"ip_address\": \"127.0.0.1\", \"user_agent\": \"Mozilla/5.0 (Windows NT; Windows NT 10.0; en-PH) WindowsPowerShell/5.1.26100.9444\", \"attempted_at\": \"2026-10-15 16:38:57.000000\", \"failure_reason\": null, \"was_successful\": 1}','2026-10-23 15:29:57.948263'),(642,'login_attempts','94','baseline',NULL,NULL,'history installation',NULL,NULL,'{\"user_id\": 1, \"attempt_id\": 94, \"identifier\": \"security.demo@pass.local\", \"ip_address\": \"127.0.0.1\", \"user_agent\": \"Mozilla/5.0 (Windows NT; Windows NT 10.0; en-PH) WindowsPowerShell/5.1.26100.9444\", \"attempted_at\": \"2026-10-15 17:12:56.000000\", \"failure_reason\": null, \"was_successful\": 1}','2026-10-23 15:29:57.948263'),(690,'audit_logs','167','baseline',NULL,NULL,'history installation',NULL,NULL,'{\"action\": \"parking_slot_maintenance\", \"log_id\": 167, \"user_id\": null, \"created_at\": \"2026-10-14 04:02:01.000000\", \"ip_address\": null, \"user_agent\": null, \"actor_label\": \"seeder:sample-data\", \"auditable_id\": 50, \"auditable_type\": \"parking_slot\"}','2026-10-23 15:29:58.004438'),(691,'audit_logs','168','baseline',NULL,NULL,'history installation',NULL,NULL,'{\"action\": \"parking_slot_maintenance\", \"log_id\": 168, \"user_id\": null, \"created_at\": \"2026-10-14 04:02:01.000000\", \"ip_address\": null, \"user_agent\": null, \"actor_label\": \"seeder:sample-data\", \"auditable_id\": 135, \"auditable_type\": \"parking_slot\"}','2026-10-23 15:29:58.004438'),(692,'audit_logs','246','baseline',NULL,NULL,'history installation',NULL,NULL,'{\"action\": \"license_auto_pending_review\", \"log_id\": 246, \"user_id\": null, \"created_at\": \"2026-10-14 05:12:00.000000\", \"ip_address\": null, \"user_agent\": null, \"actor_label\": \"system:license-check\", \"auditable_id\": 44, \"auditable_type\": \"App\\\\Models\\\\DriverLicense\"}','2026-10-23 15:29:58.004438'),(693,'audit_logs','247','baseline',NULL,NULL,'history installation',NULL,NULL,'{\"action\": \"license_review_verified\", \"log_id\": 247, \"user_id\": null, \"created_at\": \"2026-10-14 05:12:00.000000\", \"ip_address\": null, \"user_agent\": null, \"actor_label\": \"seeder:sample-data (as SEC-DEMO-001)\", \"auditable_id\": 44, \"auditable_type\": \"App\\\\Models\\\\DriverLicense\"}','2026-10-23 15:29:58.004438'),(694,'audit_logs','248','baseline',NULL,NULL,'history installation',NULL,NULL,'{\"action\": \"license_auto_pending_review\", \"log_id\": 248, \"user_id\": null, \"created_at\": \"2026-10-14 05:12:01.000000\", \"ip_address\": null, \"user_agent\": null, \"actor_label\": \"system:license-check\", \"auditable_id\": 45, \"auditable_type\": \"App\\\\Models\\\\DriverLicense\"}','2026-10-23 15:29:58.004438'),(695,'audit_logs','249','baseline',NULL,NULL,'history installation',NULL,NULL,'{\"action\": \"license_auto_needs_correction\", \"log_id\": 249, \"user_id\": null, \"created_at\": \"2026-10-14 05:12:02.000000\", \"ip_address\": null, \"user_agent\": null, \"actor_label\": \"system:license-check\", \"auditable_id\": 46, \"auditable_type\": \"App\\\\Models\\\\DriverLicense\"}','2026-10-23 15:29:58.004438'),(696,'audit_logs','250','baseline',NULL,NULL,'history installation',NULL,NULL,'{\"action\": \"license_auto_pending_review\", \"log_id\": 250, \"user_id\": null, \"created_at\": \"2026-10-14 05:12:03.000000\", \"ip_address\": null, \"user_agent\": null, \"actor_label\": \"system:license-check\", \"auditable_id\": 47, \"auditable_type\": \"App\\\\Models\\\\DriverLicense\"}','2026-10-23 15:29:58.004438'),(697,'audit_logs','251','baseline',NULL,NULL,'history installation',NULL,NULL,'{\"action\": \"license_auto_pending_review\", \"log_id\": 251, \"user_id\": null, \"created_at\": \"2026-10-14 05:12:04.000000\", \"ip_address\": null, \"user_agent\": null, \"actor_label\": \"system:license-check\", \"auditable_id\": 48, \"auditable_type\": \"App\\\\Models\\\\DriverLicense\"}','2026-10-23 15:29:58.004438'),(698,'audit_logs','252','baseline',NULL,NULL,'history installation',NULL,NULL,'{\"action\": \"license_review_verified\", \"log_id\": 252, \"user_id\": null, \"created_at\": \"2026-10-14 05:12:04.000000\", \"ip_address\": null, \"user_agent\": null, \"actor_label\": \"seeder:sample-data (as SEC-DEMO-001)\", \"auditable_id\": 48, \"auditable_type\": \"App\\\\Models\\\\DriverLicense\"}','2026-10-23 15:29:58.004438'),(699,'audit_logs','253','baseline',NULL,NULL,'history installation',NULL,NULL,'{\"action\": \"parking_slot_maintenance\", \"log_id\": 253, \"user_id\": 1, \"created_at\": \"2026-10-15 02:51:27.000000\", \"ip_address\": \"127.0.0.1\", \"user_agent\": \"Mozilla/5.0 (Windows NT; Windows NT 10.0; en-PH) WindowsPowerShell/5.1.26100.9444\", \"actor_label\": \"SEC-DEMO-001\", \"auditable_id\": 5, \"auditable_type\": \"parking_slot\"}','2026-10-23 15:29:58.004438'),(700,'audit_logs','254','baseline',NULL,NULL,'history installation',NULL,NULL,'{\"action\": \"parking_slot_available\", \"log_id\": 254, \"user_id\": 1, \"created_at\": \"2026-10-15 02:52:06.000000\", \"ip_address\": \"127.0.0.1\", \"user_agent\": \"Mozilla/5.0 (Windows NT; Windows NT 10.0; en-PH) WindowsPowerShell/5.1.26100.9444\", \"actor_label\": \"SEC-DEMO-001\", \"auditable_id\": 5, \"auditable_type\": \"parking_slot\"}','2026-10-23 15:29:58.004438'),(701,'audit_logs','255','baseline',NULL,NULL,'history installation',NULL,NULL,'{\"action\": \"visitor_request_created\", \"log_id\": 255, \"user_id\": 1, \"created_at\": \"2026-10-15 05:17:59.000000\", \"ip_address\": \"127.0.0.1\", \"user_agent\": \"Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/155.0.0.0 Safari/537.36 Edg/155.0.0.0\", \"actor_label\": \"SEC-DEMO-001\", \"auditable_id\": 1, \"auditable_type\": \"visitor_request\"}','2026-10-23 15:29:58.004438'),(702,'audit_logs','256','baseline',NULL,NULL,'history installation',NULL,NULL,'{\"action\": \"visitor_request_approved\", \"log_id\": 256, \"user_id\": 1, \"created_at\": \"2026-10-15 05:20:24.000000\", \"ip_address\": \"127.0.0.1\", \"user_agent\": \"Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/155.0.0.0 Safari/537.36 Edg/155.0.0.0\", \"actor_label\": \"SEC-DEMO-001\", \"auditable_id\": 1, \"auditable_type\": \"visitor_request\"}','2026-10-23 15:29:58.004438'),(703,'audit_logs','257','baseline',NULL,NULL,'history installation',NULL,NULL,'{\"action\": \"visitor_request_created\", \"log_id\": 257, \"user_id\": 1, \"created_at\": \"2026-10-15 05:22:32.000000\", \"ip_address\": \"127.0.0.1\", \"user_agent\": \"Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/155.0.0.0 Safari/537.36 Edg/155.0.0.0\", \"actor_label\": \"SEC-DEMO-001\", \"auditable_id\": 2, \"auditable_type\": \"visitor_request\"}','2026-10-23 15:29:58.004438'),(704,'audit_logs','258','baseline',NULL,NULL,'history installation',NULL,NULL,'{\"action\": \"visitor_request_rejected\", \"log_id\": 258, \"user_id\": 1, \"created_at\": \"2026-10-15 05:22:36.000000\", \"ip_address\": \"127.0.0.1\", \"user_agent\": \"Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/155.0.0.0 Safari/537.36 Edg/155.0.0.0\", \"actor_label\": \"SEC-DEMO-001\", \"auditable_id\": 2, \"auditable_type\": \"visitor_request\"}','2026-10-23 15:29:58.004438'),(705,'audit_logs','259','baseline',NULL,NULL,'history installation',NULL,NULL,'{\"action\": \"visitor_request_created\", \"log_id\": 259, \"user_id\": 1, \"created_at\": \"2026-10-15 05:22:41.000000\", \"ip_address\": \"127.0.0.1\", \"user_agent\": \"Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/155.0.0.0 Safari/537.36 Edg/155.0.0.0\", \"actor_label\": \"SEC-DEMO-001\", \"auditable_id\": 3, \"auditable_type\": \"visitor_request\"}','2026-10-23 15:29:58.004438'),(706,'audit_logs','260','baseline',NULL,NULL,'history installation',NULL,NULL,'{\"action\": \"visitor_request_approved\", \"log_id\": 260, \"user_id\": 1, \"created_at\": \"2026-10-15 05:22:45.000000\", \"ip_address\": \"127.0.0.1\", \"user_agent\": \"Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/155.0.0.0 Safari/537.36 Edg/155.0.0.0\", \"actor_label\": \"SEC-DEMO-001\", \"auditable_id\": 3, \"auditable_type\": \"visitor_request\"}','2026-10-23 15:29:58.004438'),(707,'audit_logs','261','baseline',NULL,NULL,'history installation',NULL,NULL,'{\"action\": \"visitor_request_created\", \"log_id\": 261, \"user_id\": 1, \"created_at\": \"2026-10-15 05:25:27.000000\", \"ip_address\": \"127.0.0.1\", \"user_agent\": \"Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/155.0.0.0 Safari/537.36 Edg/155.0.0.0\", \"actor_label\": \"SEC-DEMO-001\", \"auditable_id\": 4, \"auditable_type\": \"visitor_request\"}','2026-10-23 15:29:58.004438'),(708,'audit_logs','262','baseline',NULL,NULL,'history installation',NULL,NULL,'{\"action\": \"visitor_request_approved\", \"log_id\": 262, \"user_id\": 1, \"created_at\": \"2026-10-15 05:25:31.000000\", \"ip_address\": \"127.0.0.1\", \"user_agent\": \"Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/155.0.0.0 Safari/537.36 Edg/155.0.0.0\", \"actor_label\": \"SEC-DEMO-001\", \"auditable_id\": 4, \"auditable_type\": \"visitor_request\"}','2026-10-23 15:29:58.004438'),(709,'audit_logs','263','baseline',NULL,NULL,'history installation',NULL,NULL,'{\"action\": \"qr_pass_revoked\", \"log_id\": 263, \"user_id\": 1, \"created_at\": \"2026-10-15 05:25:47.000000\", \"ip_address\": \"127.0.0.1\", \"user_agent\": \"Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/155.0.0.0 Safari/537.36 Edg/155.0.0.0\", \"actor_label\": \"SEC-DEMO-001\", \"auditable_id\": 70, \"auditable_type\": \"qr_pass\"}','2026-10-23 15:29:58.004438'),(710,'audit_logs','264','baseline',NULL,NULL,'history installation',NULL,NULL,'{\"action\": \"visitor_request_created\", \"log_id\": 264, \"user_id\": 47, \"created_at\": \"2026-10-15 08:15:48.000000\", \"ip_address\": \"127.0.0.1\", \"user_agent\": \"Symfony\", \"actor_label\": \"GST-DEMO-001\", \"auditable_id\": 5, \"auditable_type\": \"visitor_request\"}','2026-10-23 15:29:58.004438'),(711,'audit_logs','265','baseline',NULL,NULL,'history installation',NULL,NULL,'{\"action\": \"visitor_request_created\", \"log_id\": 265, \"user_id\": 48, \"created_at\": \"2026-10-15 08:24:58.000000\", \"ip_address\": \"127.0.0.1\", \"user_agent\": \"Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/155.0.0.0 Safari/537.36 Edg/155.0.0.0\", \"actor_label\": \"GST-M9SC2UDB\", \"auditable_id\": 6, \"auditable_type\": \"visitor_request\"}','2026-10-23 15:29:58.004438'),(712,'audit_logs','266','baseline',NULL,NULL,'history installation',NULL,NULL,'{\"action\": \"visitor_request_created\", \"log_id\": 266, \"user_id\": 48, \"created_at\": \"2026-10-15 08:25:10.000000\", \"ip_address\": \"127.0.0.1\", \"user_agent\": \"Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/155.0.0.0 Safari/537.36 Edg/155.0.0.0\", \"actor_label\": \"GST-M9SC2UDB\", \"auditable_id\": 7, \"auditable_type\": \"visitor_request\"}','2026-10-23 15:29:58.004438'),(713,'audit_logs','267','baseline',NULL,NULL,'history installation',NULL,NULL,'{\"action\": \"visitor_request_approved\", \"log_id\": 267, \"user_id\": 46, \"created_at\": \"2026-10-15 08:27:02.000000\", \"ip_address\": \"127.0.0.1\", \"user_agent\": \"Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/155.0.0.0 Safari/537.36 Edg/155.0.0.0\", \"actor_label\": \"ADM-DEMO-001\", \"auditable_id\": 6, \"auditable_type\": \"visitor_request\"}','2026-10-23 15:29:58.004438'),(714,'audit_logs','268','baseline',NULL,NULL,'history installation',NULL,NULL,'{\"action\": \"visitor_request_rejected\", \"log_id\": 268, \"user_id\": 46, \"created_at\": \"2026-10-15 08:27:06.000000\", \"ip_address\": \"127.0.0.1\", \"user_agent\": \"Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/155.0.0.0 Safari/537.36 Edg/155.0.0.0\", \"actor_label\": \"ADM-DEMO-001\", \"auditable_id\": 7, \"auditable_type\": \"visitor_request\"}','2026-10-23 15:29:58.004438'),(715,'audit_logs','269','baseline',NULL,NULL,'history installation',NULL,NULL,'{\"action\": \"visitor_request_created\", \"log_id\": 269, \"user_id\": 49, \"created_at\": \"2026-10-15 10:15:56.000000\", \"ip_address\": \"127.0.0.1\", \"user_agent\": \"Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Code/1.141.2 Chrome/150.0.7871.250 Electron/43.7.7 Safari/537.36\", \"actor_label\": \"GST-20UF3BER\", \"auditable_id\": 8, \"auditable_type\": \"visitor_request\"}','2026-10-23 15:29:58.004438'),(716,'audit_logs','270','baseline',NULL,NULL,'history installation',NULL,NULL,'{\"action\": \"visitor_request_created\", \"log_id\": 270, \"user_id\": 50, \"created_at\": \"2026-10-15 11:33:30.000000\", \"ip_address\": \"127.0.0.1\", \"user_agent\": \"Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/155.0.0.0 Safari/537.36 Edg/155.0.0.0\", \"actor_label\": \"GST-BW3KZLQ4\", \"auditable_id\": 9, \"auditable_type\": \"visitor_request\"}','2026-10-23 15:29:58.004438'),(717,'audit_logs','271','baseline',NULL,NULL,'history installation',NULL,NULL,'{\"action\": \"visitor_request_approved\", \"log_id\": 271, \"user_id\": 46, \"created_at\": \"2026-10-15 11:33:51.000000\", \"ip_address\": \"127.0.0.1\", \"user_agent\": \"Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/155.0.0.0 Safari/537.36 Edg/155.0.0.0\", \"actor_label\": \"ADM-DEMO-001\", \"auditable_id\": 9, \"auditable_type\": \"visitor_request\"}','2026-10-23 15:29:58.004438'),(718,'audit_logs','272','baseline',NULL,NULL,'history installation',NULL,NULL,'{\"action\": \"suspended\", \"log_id\": 272, \"user_id\": 1, \"created_at\": \"2026-10-15 11:49:11.000000\", \"ip_address\": \"127.0.0.1\", \"user_agent\": \"Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Code/1.141.2 Chrome/150.0.7871.250 Electron/43.7.7 Safari/537.36\", \"actor_label\": \"SEC-DEMO-001\", \"auditable_id\": 35, \"auditable_type\": \"App\\\\Models\\\\VehicleAuthorization\"}','2026-10-23 15:29:58.004438'),(719,'audit_logs','273','baseline',NULL,NULL,'history installation',NULL,NULL,'{\"action\": \"violation_acknowledged\", \"log_id\": 273, \"user_id\": 1, \"created_at\": \"2026-10-15 17:19:01.000000\", \"ip_address\": \"127.0.0.1\", \"user_agent\": \"node\", \"actor_label\": \"SEC-DEMO-001\", \"auditable_id\": 26, \"auditable_type\": \"parking_violation\"}','2026-10-23 15:29:58.004438'),(720,'audit_logs','274','baseline',NULL,NULL,'history installation',NULL,NULL,'{\"action\": \"violation_resolved\", \"log_id\": 274, \"user_id\": 1, \"created_at\": \"2026-10-15 17:19:02.000000\", \"ip_address\": \"127.0.0.1\", \"user_agent\": \"node\", \"actor_label\": \"SEC-DEMO-001\", \"auditable_id\": 26, \"auditable_type\": \"parking_violation\"}','2026-10-23 15:29:58.004438'),(721,'audit_logs','275','baseline',NULL,NULL,'history installation',NULL,NULL,'{\"action\": \"violation_dismissed\", \"log_id\": 275, \"user_id\": 1, \"created_at\": \"2026-10-15 17:19:06.000000\", \"ip_address\": \"127.0.0.1\", \"user_agent\": \"node\", \"actor_label\": \"SEC-DEMO-001\", \"auditable_id\": 27, \"auditable_type\": \"parking_violation\"}','2026-10-23 15:29:58.004438'),(753,'login_attempts','95','created',NULL,NULL,'POST api/login','127.0.0.1',NULL,'{\"user_id\": 1, \"attempt_id\": 95, \"identifier\": \"security.demo@pass.local\", \"ip_address\": \"127.0.0.1\", \"user_agent\": \"curl/8.18.0\", \"attempted_at\": \"2026-10-23 07:30:06.000000\", \"failure_reason\": null, \"was_successful\": 1}','2026-10-23 15:30:06.319085'),(754,'users','1','updated',NULL,NULL,'POST api/login','127.0.0.1','{\"email\": \"security.demo@pass.local\", \"user_id\": 1, \"user_code\": \"SEC-DEMO-001\", \"user_type\": \"security\", \"created_at\": \"2026-10-13 07:17:03.000000\", \"deleted_at\": null, \"updated_at\": \"2026-10-15 17:12:56.000000\", \"last_login_at\": \"2026-10-15 17:12:56.000000\", \"user_code_key\": \"SECDEMO001\", \"account_status\": \"active\", \"email_verified_at\": \"2026-10-13 07:17:03.000000\"}','{\"email\": \"security.demo@pass.local\", \"user_id\": 1, \"user_code\": \"SEC-DEMO-001\", \"user_type\": \"security\", \"created_at\": \"2026-10-13 07:17:03.000000\", \"deleted_at\": null, \"updated_at\": \"2026-10-23 07:30:06.000000\", \"last_login_at\": \"2026-10-23 07:30:06.000000\", \"user_code_key\": \"SECDEMO001\", \"account_status\": \"active\", \"email_verified_at\": \"2026-10-13 07:17:03.000000\"}','2026-10-23 15:30:06.326455'),(755,'audit_logs','276','created',NULL,NULL,'POST api/login','127.0.0.1',NULL,'{\"action\": \"login\", \"log_id\": 276, \"user_id\": 1, \"created_at\": \"2026-10-23 07:30:06.000000\", \"ip_address\": \"127.0.0.1\", \"user_agent\": \"curl/8.18.0\", \"actor_label\": \"SEC-DEMO-001\", \"auditable_id\": 1, \"auditable_type\": \"users\"}','2026-10-23 15:30:06.348992'),(756,'login_attempts','96','created',NULL,NULL,'POST api/login','127.0.0.1',NULL,'{\"user_id\": 1, \"attempt_id\": 96, \"identifier\": \"security.demo@pass.local\", \"ip_address\": \"127.0.0.1\", \"user_agent\": \"Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36 Edg/155.0.0.0\", \"attempted_at\": \"2026-10-23 07:31:02.000000\", \"failure_reason\": null, \"was_successful\": 1}','2026-10-23 15:31:02.463341'),(757,'users','1','updated',NULL,NULL,'POST api/login','127.0.0.1','{\"email\": \"security.demo@pass.local\", \"user_id\": 1, \"user_code\": \"SEC-DEMO-001\", \"user_type\": \"security\", \"created_at\": \"2026-10-13 07:17:03.000000\", \"deleted_at\": null, \"updated_at\": \"2026-10-23 07:30:06.000000\", \"last_login_at\": \"2026-10-23 07:30:06.000000\", \"user_code_key\": \"SECDEMO001\", \"account_status\": \"active\", \"email_verified_at\": \"2026-10-13 07:17:03.000000\"}','{\"email\": \"security.demo@pass.local\", \"user_id\": 1, \"user_code\": \"SEC-DEMO-001\", \"user_type\": \"security\", \"created_at\": \"2026-10-13 07:17:03.000000\", \"deleted_at\": null, \"updated_at\": \"2026-10-23 07:31:02.000000\", \"last_login_at\": \"2026-10-23 07:31:02.000000\", \"user_code_key\": \"SECDEMO001\", \"account_status\": \"active\", \"email_verified_at\": \"2026-10-13 07:17:03.000000\"}','2026-10-23 15:31:02.467677'),(758,'audit_logs','277','created',NULL,NULL,'POST api/login','127.0.0.1',NULL,'{\"action\": \"login\", \"log_id\": 277, \"user_id\": 1, \"created_at\": \"2026-10-23 07:31:02.000000\", \"ip_address\": \"127.0.0.1\", \"user_agent\": \"Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36 Edg/155.0.0.0\", \"actor_label\": \"SEC-DEMO-001\", \"auditable_id\": 1, \"auditable_type\": \"users\"}','2026-10-23 15:31:02.480143'),(759,'users','1','updated',1,'SEC-DEMO-001','POST api/profile/password','127.0.0.1','{\"email\": \"security.demo@pass.local\", \"user_id\": 1, \"user_code\": \"SEC-DEMO-001\", \"user_type\": \"security\", \"created_at\": \"2026-10-13 07:17:03.000000\", \"deleted_at\": null, \"updated_at\": \"2026-10-23 07:31:02.000000\", \"last_login_at\": \"2026-10-23 07:31:02.000000\", \"user_code_key\": \"SECDEMO001\", \"account_status\": \"active\", \"email_verified_at\": \"2026-10-13 07:17:03.000000\"}','{\"email\": \"security.demo@pass.local\", \"user_id\": 1, \"user_code\": \"SEC-DEMO-001\", \"user_type\": \"security\", \"created_at\": \"2026-10-13 07:17:03.000000\", \"deleted_at\": null, \"updated_at\": \"2026-10-23 07:44:39.000000\", \"last_login_at\": \"2026-10-23 07:31:02.000000\", \"user_code_key\": \"SECDEMO001\", \"account_status\": \"active\", \"email_verified_at\": \"2026-10-13 07:17:03.000000\"}','2026-10-23 15:44:39.717580'),(760,'audit_logs','278','created',1,'SEC-DEMO-001','POST api/profile/password','127.0.0.1',NULL,'{\"action\": \"password_changed\", \"log_id\": 278, \"user_id\": 1, \"created_at\": \"2026-10-23 07:44:39.000000\", \"ip_address\": \"127.0.0.1\", \"user_agent\": \"Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36 Edg/155.0.0.0\", \"actor_label\": \"SEC-DEMO-001\", \"auditable_id\": 1, \"auditable_type\": \"users\"}','2026-10-23 15:44:39.729784'),(761,'login_attempts','97','created',NULL,NULL,'POST api/login','127.0.0.1',NULL,'{\"user_id\": 1, \"attempt_id\": 97, \"identifier\": \"security.demo@pass.local\", \"ip_address\": \"127.0.0.1\", \"user_agent\": \"Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36 Edg/155.0.0.0\", \"attempted_at\": \"2026-10-23 07:44:58.000000\", \"failure_reason\": null, \"was_successful\": 1}','2026-10-23 15:44:58.679246'),(762,'users','1','updated',NULL,NULL,'POST api/login','127.0.0.1','{\"email\": \"security.demo@pass.local\", \"user_id\": 1, \"user_code\": \"SEC-DEMO-001\", \"user_type\": \"security\", \"created_at\": \"2026-10-13 07:17:03.000000\", \"deleted_at\": null, \"updated_at\": \"2026-10-23 07:44:39.000000\", \"last_login_at\": \"2026-10-23 07:31:02.000000\", \"user_code_key\": \"SECDEMO001\", \"account_status\": \"active\", \"email_verified_at\": \"2026-10-13 07:17:03.000000\"}','{\"email\": \"security.demo@pass.local\", \"user_id\": 1, \"user_code\": \"SEC-DEMO-001\", \"user_type\": \"security\", \"created_at\": \"2026-10-13 07:17:03.000000\", \"deleted_at\": null, \"updated_at\": \"2026-10-23 07:44:58.000000\", \"last_login_at\": \"2026-10-23 07:44:58.000000\", \"user_code_key\": \"SECDEMO001\", \"account_status\": \"active\", \"email_verified_at\": \"2026-10-13 07:17:03.000000\"}','2026-10-23 15:44:58.686584'),(763,'audit_logs','279','created',NULL,NULL,'POST api/login','127.0.0.1',NULL,'{\"action\": \"login\", \"log_id\": 279, \"user_id\": 1, \"created_at\": \"2026-10-23 07:44:58.000000\", \"ip_address\": \"127.0.0.1\", \"user_agent\": \"Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36 Edg/155.0.0.0\", \"actor_label\": \"SEC-DEMO-001\", \"auditable_id\": 1, \"auditable_type\": \"users\"}','2026-10-23 15:44:58.698914'),(764,'login_attempts','98','created',NULL,NULL,'POST api/login','127.0.0.1',NULL,'{\"user_id\": 1, \"attempt_id\": 98, \"identifier\": \"security.demo@pass.local\", \"ip_address\": \"127.0.0.1\", \"user_agent\": \"Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36\", \"attempted_at\": \"2026-10-23 08:54:40.000000\", \"failure_reason\": null, \"was_successful\": 1}','2026-10-23 16:54:40.668312'),(765,'users','1','updated',NULL,NULL,'POST api/login','127.0.0.1','{\"email\": \"security.demo@pass.local\", \"user_id\": 1, \"user_code\": \"SEC-DEMO-001\", \"user_type\": \"security\", \"created_at\": \"2026-10-13 07:17:03.000000\", \"deleted_at\": null, \"updated_at\": \"2026-10-23 07:44:58.000000\", \"last_login_at\": \"2026-10-23 07:44:58.000000\", \"user_code_key\": \"SECDEMO001\", \"account_status\": \"active\", \"email_verified_at\": \"2026-10-13 07:17:03.000000\"}','{\"email\": \"security.demo@pass.local\", \"user_id\": 1, \"user_code\": \"SEC-DEMO-001\", \"user_type\": \"security\", \"created_at\": \"2026-10-13 07:17:03.000000\", \"deleted_at\": null, \"updated_at\": \"2026-10-23 08:54:40.000000\", \"last_login_at\": \"2026-10-23 08:54:40.000000\", \"user_code_key\": \"SECDEMO001\", \"account_status\": \"active\", \"email_verified_at\": \"2026-10-13 07:17:03.000000\"}','2026-10-23 16:54:40.678364'),(766,'audit_logs','280','created',NULL,NULL,'POST api/login','127.0.0.1',NULL,'{\"action\": \"login\", \"log_id\": 280, \"user_id\": 1, \"created_at\": \"2026-10-23 08:54:40.000000\", \"ip_address\": \"127.0.0.1\", \"user_agent\": \"Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36\", \"actor_label\": \"SEC-DEMO-001\", \"auditable_id\": 1, \"auditable_type\": \"users\"}','2026-10-23 16:54:40.694762');
/*!40000 ALTER TABLE `record_history` ENABLE KEYS */;
UNLOCK TABLES;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_unicode_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER `pass_history_immutable_update` BEFORE UPDATE ON `record_history` FOR EACH ROW SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Record history is append-only' */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_unicode_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER `pass_history_immutable_delete` BEFORE DELETE ON `record_history` FOR EACH ROW SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Record history is append-only' */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;

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
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_unicode_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER `pass_history_rfid_assignments_insert` AFTER INSERT ON `rfid_assignments` FOR EACH ROW BEGIN INSERT INTO record_history (record_type, record_id, operation, actor_user_id, actor_label, source, ip_address, before_values, after_values, recorded_at)
                    VALUES ('rfid_assignments', CONCAT_WS(':', NEW.`assignment_id`), 'created', @pass_history_actor, LEFT(@pass_history_label, 100), LEFT(@pass_history_source, 150), LEFT(@pass_history_ip, 45), NULL, JSON_OBJECT('assignment_id', NEW.`assignment_id`, 'rfid_id', NEW.`rfid_id`, 'user_id', NEW.`user_id`, 'vehicle_id', NEW.`vehicle_id`, 'assigned_by', NEW.`assigned_by`, 'assigned_at', NEW.`assigned_at`, 'valid_until', NEW.`valid_until`, 'unassigned_at', NEW.`unassigned_at`, 'assignment_status', NEW.`assignment_status`, 'remarks', NEW.`remarks`, 'created_at', NEW.`created_at`, 'updated_at', NEW.`updated_at`, 'active_flag', NEW.`active_flag`), CURRENT_TIMESTAMP(6)); END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_unicode_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER `pass_history_rfid_assignments_update` AFTER UPDATE ON `rfid_assignments` FOR EACH ROW BEGIN IF NOT (OLD.`assignment_id` <=> NEW.`assignment_id`) OR NOT (OLD.`rfid_id` <=> NEW.`rfid_id`) OR NOT (OLD.`user_id` <=> NEW.`user_id`) OR NOT (OLD.`vehicle_id` <=> NEW.`vehicle_id`) OR NOT (OLD.`assigned_by` <=> NEW.`assigned_by`) OR NOT (OLD.`assigned_at` <=> NEW.`assigned_at`) OR NOT (OLD.`valid_until` <=> NEW.`valid_until`) OR NOT (OLD.`unassigned_at` <=> NEW.`unassigned_at`) OR NOT (OLD.`assignment_status` <=> NEW.`assignment_status`) OR NOT (OLD.`remarks` <=> NEW.`remarks`) OR NOT (OLD.`created_at` <=> NEW.`created_at`) OR NOT (OLD.`updated_at` <=> NEW.`updated_at`) OR NOT (OLD.`active_flag` <=> NEW.`active_flag`) THEN INSERT INTO record_history (record_type, record_id, operation, actor_user_id, actor_label, source, ip_address, before_values, after_values, recorded_at)
                    VALUES ('rfid_assignments', CONCAT_WS(':', NEW.`assignment_id`), 'updated', @pass_history_actor, LEFT(@pass_history_label, 100), LEFT(@pass_history_source, 150), LEFT(@pass_history_ip, 45), JSON_OBJECT('assignment_id', OLD.`assignment_id`, 'rfid_id', OLD.`rfid_id`, 'user_id', OLD.`user_id`, 'vehicle_id', OLD.`vehicle_id`, 'assigned_by', OLD.`assigned_by`, 'assigned_at', OLD.`assigned_at`, 'valid_until', OLD.`valid_until`, 'unassigned_at', OLD.`unassigned_at`, 'assignment_status', OLD.`assignment_status`, 'remarks', OLD.`remarks`, 'created_at', OLD.`created_at`, 'updated_at', OLD.`updated_at`, 'active_flag', OLD.`active_flag`), JSON_OBJECT('assignment_id', NEW.`assignment_id`, 'rfid_id', NEW.`rfid_id`, 'user_id', NEW.`user_id`, 'vehicle_id', NEW.`vehicle_id`, 'assigned_by', NEW.`assigned_by`, 'assigned_at', NEW.`assigned_at`, 'valid_until', NEW.`valid_until`, 'unassigned_at', NEW.`unassigned_at`, 'assignment_status', NEW.`assignment_status`, 'remarks', NEW.`remarks`, 'created_at', NEW.`created_at`, 'updated_at', NEW.`updated_at`, 'active_flag', NEW.`active_flag`), CURRENT_TIMESTAMP(6)); END IF; END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_unicode_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER `pass_history_rfid_assignments_delete` AFTER DELETE ON `rfid_assignments` FOR EACH ROW BEGIN INSERT INTO record_history (record_type, record_id, operation, actor_user_id, actor_label, source, ip_address, before_values, after_values, recorded_at)
                    VALUES ('rfid_assignments', CONCAT_WS(':', OLD.`assignment_id`), 'deleted', @pass_history_actor, LEFT(@pass_history_label, 100), LEFT(@pass_history_source, 150), LEFT(@pass_history_ip, 45), JSON_OBJECT('assignment_id', OLD.`assignment_id`, 'rfid_id', OLD.`rfid_id`, 'user_id', OLD.`user_id`, 'vehicle_id', OLD.`vehicle_id`, 'assigned_by', OLD.`assigned_by`, 'assigned_at', OLD.`assigned_at`, 'valid_until', OLD.`valid_until`, 'unassigned_at', OLD.`unassigned_at`, 'assignment_status', OLD.`assignment_status`, 'remarks', OLD.`remarks`, 'created_at', OLD.`created_at`, 'updated_at', OLD.`updated_at`, 'active_flag', OLD.`active_flag`), NULL, CURRENT_TIMESTAMP(6)); END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;

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
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_unicode_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER `pass_history_rfid_readers_insert` AFTER INSERT ON `rfid_readers` FOR EACH ROW BEGIN INSERT INTO record_history (record_type, record_id, operation, actor_user_id, actor_label, source, ip_address, before_values, after_values, recorded_at)
                    VALUES ('rfid_readers', CONCAT_WS(':', NEW.`reader_id`), 'created', @pass_history_actor, LEFT(@pass_history_label, 100), LEFT(@pass_history_source, 150), LEFT(@pass_history_ip, 45), NULL, JSON_OBJECT('reader_id', NEW.`reader_id`, 'reader_code', NEW.`reader_code`, 'gate_id', NEW.`gate_id`, 'device_name', NEW.`device_name`, 'hardware_serial', NEW.`hardware_serial`, 'firmware_version', NEW.`firmware_version`, 'ip_address', NEW.`ip_address`, 'mac_address', NEW.`mac_address`, 'reader_direction', NEW.`reader_direction`, 'reader_status', NEW.`reader_status`, 'last_heartbeat_at', NEW.`last_heartbeat_at`, 'created_at', NEW.`created_at`, 'updated_at', NEW.`updated_at`), CURRENT_TIMESTAMP(6)); END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_unicode_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER `pass_history_rfid_readers_update` AFTER UPDATE ON `rfid_readers` FOR EACH ROW BEGIN IF NOT (OLD.`reader_id` <=> NEW.`reader_id`) OR NOT (OLD.`reader_code` <=> NEW.`reader_code`) OR NOT (OLD.`gate_id` <=> NEW.`gate_id`) OR NOT (OLD.`device_name` <=> NEW.`device_name`) OR NOT (OLD.`hardware_serial` <=> NEW.`hardware_serial`) OR NOT (OLD.`firmware_version` <=> NEW.`firmware_version`) OR NOT (OLD.`ip_address` <=> NEW.`ip_address`) OR NOT (OLD.`mac_address` <=> NEW.`mac_address`) OR NOT (OLD.`reader_direction` <=> NEW.`reader_direction`) OR NOT (OLD.`reader_status` <=> NEW.`reader_status`) OR NOT (OLD.`last_heartbeat_at` <=> NEW.`last_heartbeat_at`) OR NOT (OLD.`created_at` <=> NEW.`created_at`) OR NOT (OLD.`updated_at` <=> NEW.`updated_at`) THEN INSERT INTO record_history (record_type, record_id, operation, actor_user_id, actor_label, source, ip_address, before_values, after_values, recorded_at)
                    VALUES ('rfid_readers', CONCAT_WS(':', NEW.`reader_id`), 'updated', @pass_history_actor, LEFT(@pass_history_label, 100), LEFT(@pass_history_source, 150), LEFT(@pass_history_ip, 45), JSON_OBJECT('reader_id', OLD.`reader_id`, 'reader_code', OLD.`reader_code`, 'gate_id', OLD.`gate_id`, 'device_name', OLD.`device_name`, 'hardware_serial', OLD.`hardware_serial`, 'firmware_version', OLD.`firmware_version`, 'ip_address', OLD.`ip_address`, 'mac_address', OLD.`mac_address`, 'reader_direction', OLD.`reader_direction`, 'reader_status', OLD.`reader_status`, 'last_heartbeat_at', OLD.`last_heartbeat_at`, 'created_at', OLD.`created_at`, 'updated_at', OLD.`updated_at`), JSON_OBJECT('reader_id', NEW.`reader_id`, 'reader_code', NEW.`reader_code`, 'gate_id', NEW.`gate_id`, 'device_name', NEW.`device_name`, 'hardware_serial', NEW.`hardware_serial`, 'firmware_version', NEW.`firmware_version`, 'ip_address', NEW.`ip_address`, 'mac_address', NEW.`mac_address`, 'reader_direction', NEW.`reader_direction`, 'reader_status', NEW.`reader_status`, 'last_heartbeat_at', NEW.`last_heartbeat_at`, 'created_at', NEW.`created_at`, 'updated_at', NEW.`updated_at`), CURRENT_TIMESTAMP(6)); END IF; END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_unicode_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER `pass_history_rfid_readers_delete` AFTER DELETE ON `rfid_readers` FOR EACH ROW BEGIN INSERT INTO record_history (record_type, record_id, operation, actor_user_id, actor_label, source, ip_address, before_values, after_values, recorded_at)
                    VALUES ('rfid_readers', CONCAT_WS(':', OLD.`reader_id`), 'deleted', @pass_history_actor, LEFT(@pass_history_label, 100), LEFT(@pass_history_source, 150), LEFT(@pass_history_ip, 45), JSON_OBJECT('reader_id', OLD.`reader_id`, 'reader_code', OLD.`reader_code`, 'gate_id', OLD.`gate_id`, 'device_name', OLD.`device_name`, 'hardware_serial', OLD.`hardware_serial`, 'firmware_version', OLD.`firmware_version`, 'ip_address', OLD.`ip_address`, 'mac_address', OLD.`mac_address`, 'reader_direction', OLD.`reader_direction`, 'reader_status', OLD.`reader_status`, 'last_heartbeat_at', OLD.`last_heartbeat_at`, 'created_at', OLD.`created_at`, 'updated_at', OLD.`updated_at`), NULL, CURRENT_TIMESTAMP(6)); END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;

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
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_unicode_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER `pass_history_rfid_tags_insert` AFTER INSERT ON `rfid_tags` FOR EACH ROW BEGIN INSERT INTO record_history (record_type, record_id, operation, actor_user_id, actor_label, source, ip_address, before_values, after_values, recorded_at)
                    VALUES ('rfid_tags', CONCAT_WS(':', NEW.`rfid_id`), 'created', @pass_history_actor, LEFT(@pass_history_label, 100), LEFT(@pass_history_source, 150), LEFT(@pass_history_ip, 45), NULL, JSON_OBJECT('rfid_id', NEW.`rfid_id`, 'tag_uid', NEW.`tag_uid`, 'tag_type', NEW.`tag_type`, 'serial_number', NEW.`serial_number`, 'batch_code', NEW.`batch_code`, 'tag_status', NEW.`tag_status`, 'acquired_at', NEW.`acquired_at`, 'created_at', NEW.`created_at`, 'updated_at', NEW.`updated_at`), CURRENT_TIMESTAMP(6)); END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_unicode_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER `pass_history_rfid_tags_update` AFTER UPDATE ON `rfid_tags` FOR EACH ROW BEGIN IF NOT (OLD.`rfid_id` <=> NEW.`rfid_id`) OR NOT (OLD.`tag_uid` <=> NEW.`tag_uid`) OR NOT (OLD.`tag_type` <=> NEW.`tag_type`) OR NOT (OLD.`serial_number` <=> NEW.`serial_number`) OR NOT (OLD.`batch_code` <=> NEW.`batch_code`) OR NOT (OLD.`tag_status` <=> NEW.`tag_status`) OR NOT (OLD.`acquired_at` <=> NEW.`acquired_at`) OR NOT (OLD.`created_at` <=> NEW.`created_at`) OR NOT (OLD.`updated_at` <=> NEW.`updated_at`) THEN INSERT INTO record_history (record_type, record_id, operation, actor_user_id, actor_label, source, ip_address, before_values, after_values, recorded_at)
                    VALUES ('rfid_tags', CONCAT_WS(':', NEW.`rfid_id`), 'updated', @pass_history_actor, LEFT(@pass_history_label, 100), LEFT(@pass_history_source, 150), LEFT(@pass_history_ip, 45), JSON_OBJECT('rfid_id', OLD.`rfid_id`, 'tag_uid', OLD.`tag_uid`, 'tag_type', OLD.`tag_type`, 'serial_number', OLD.`serial_number`, 'batch_code', OLD.`batch_code`, 'tag_status', OLD.`tag_status`, 'acquired_at', OLD.`acquired_at`, 'created_at', OLD.`created_at`, 'updated_at', OLD.`updated_at`), JSON_OBJECT('rfid_id', NEW.`rfid_id`, 'tag_uid', NEW.`tag_uid`, 'tag_type', NEW.`tag_type`, 'serial_number', NEW.`serial_number`, 'batch_code', NEW.`batch_code`, 'tag_status', NEW.`tag_status`, 'acquired_at', NEW.`acquired_at`, 'created_at', NEW.`created_at`, 'updated_at', NEW.`updated_at`), CURRENT_TIMESTAMP(6)); END IF; END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_unicode_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER `pass_history_rfid_tags_delete` AFTER DELETE ON `rfid_tags` FOR EACH ROW BEGIN INSERT INTO record_history (record_type, record_id, operation, actor_user_id, actor_label, source, ip_address, before_values, after_values, recorded_at)
                    VALUES ('rfid_tags', CONCAT_WS(':', OLD.`rfid_id`), 'deleted', @pass_history_actor, LEFT(@pass_history_label, 100), LEFT(@pass_history_source, 150), LEFT(@pass_history_ip, 45), JSON_OBJECT('rfid_id', OLD.`rfid_id`, 'tag_uid', OLD.`tag_uid`, 'tag_type', OLD.`tag_type`, 'serial_number', OLD.`serial_number`, 'batch_code', OLD.`batch_code`, 'tag_status', OLD.`tag_status`, 'acquired_at', OLD.`acquired_at`, 'created_at', OLD.`created_at`, 'updated_at', OLD.`updated_at`), NULL, CURRENT_TIMESTAMP(6)); END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;

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
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_unicode_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER `pass_history_role_permissions_insert` AFTER INSERT ON `role_permissions` FOR EACH ROW BEGIN INSERT INTO record_history (record_type, record_id, operation, actor_user_id, actor_label, source, ip_address, before_values, after_values, recorded_at)
                    VALUES ('role_permissions', CONCAT_WS(':', NEW.`role_id`, NEW.`permission_id`), 'created', @pass_history_actor, LEFT(@pass_history_label, 100), LEFT(@pass_history_source, 150), LEFT(@pass_history_ip, 45), NULL, JSON_OBJECT('role_id', NEW.`role_id`, 'permission_id', NEW.`permission_id`, 'granted_at', NEW.`granted_at`), CURRENT_TIMESTAMP(6)); END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_unicode_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER `pass_history_role_permissions_update` AFTER UPDATE ON `role_permissions` FOR EACH ROW BEGIN IF NOT (OLD.`role_id` <=> NEW.`role_id`) OR NOT (OLD.`permission_id` <=> NEW.`permission_id`) OR NOT (OLD.`granted_at` <=> NEW.`granted_at`) THEN INSERT INTO record_history (record_type, record_id, operation, actor_user_id, actor_label, source, ip_address, before_values, after_values, recorded_at)
                    VALUES ('role_permissions', CONCAT_WS(':', NEW.`role_id`, NEW.`permission_id`), 'updated', @pass_history_actor, LEFT(@pass_history_label, 100), LEFT(@pass_history_source, 150), LEFT(@pass_history_ip, 45), JSON_OBJECT('role_id', OLD.`role_id`, 'permission_id', OLD.`permission_id`, 'granted_at', OLD.`granted_at`), JSON_OBJECT('role_id', NEW.`role_id`, 'permission_id', NEW.`permission_id`, 'granted_at', NEW.`granted_at`), CURRENT_TIMESTAMP(6)); END IF; END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_unicode_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER `pass_history_role_permissions_delete` AFTER DELETE ON `role_permissions` FOR EACH ROW BEGIN INSERT INTO record_history (record_type, record_id, operation, actor_user_id, actor_label, source, ip_address, before_values, after_values, recorded_at)
                    VALUES ('role_permissions', CONCAT_WS(':', OLD.`role_id`, OLD.`permission_id`), 'deleted', @pass_history_actor, LEFT(@pass_history_label, 100), LEFT(@pass_history_source, 150), LEFT(@pass_history_ip, 45), JSON_OBJECT('role_id', OLD.`role_id`, 'permission_id', OLD.`permission_id`, 'granted_at', OLD.`granted_at`), NULL, CURRENT_TIMESTAMP(6)); END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;

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
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_unicode_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER `pass_history_roles_insert` AFTER INSERT ON `roles` FOR EACH ROW BEGIN INSERT INTO record_history (record_type, record_id, operation, actor_user_id, actor_label, source, ip_address, before_values, after_values, recorded_at)
                    VALUES ('roles', CONCAT_WS(':', NEW.`role_id`), 'created', @pass_history_actor, LEFT(@pass_history_label, 100), LEFT(@pass_history_source, 150), LEFT(@pass_history_ip, 45), NULL, JSON_OBJECT('role_id', NEW.`role_id`, 'role_code', NEW.`role_code`, 'role_name', NEW.`role_name`, 'description', NEW.`description`, 'is_system', NEW.`is_system`, 'is_active', NEW.`is_active`), CURRENT_TIMESTAMP(6)); END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_unicode_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER `pass_history_roles_update` AFTER UPDATE ON `roles` FOR EACH ROW BEGIN IF NOT (OLD.`role_id` <=> NEW.`role_id`) OR NOT (OLD.`role_code` <=> NEW.`role_code`) OR NOT (OLD.`role_name` <=> NEW.`role_name`) OR NOT (OLD.`description` <=> NEW.`description`) OR NOT (OLD.`is_system` <=> NEW.`is_system`) OR NOT (OLD.`is_active` <=> NEW.`is_active`) THEN INSERT INTO record_history (record_type, record_id, operation, actor_user_id, actor_label, source, ip_address, before_values, after_values, recorded_at)
                    VALUES ('roles', CONCAT_WS(':', NEW.`role_id`), 'updated', @pass_history_actor, LEFT(@pass_history_label, 100), LEFT(@pass_history_source, 150), LEFT(@pass_history_ip, 45), JSON_OBJECT('role_id', OLD.`role_id`, 'role_code', OLD.`role_code`, 'role_name', OLD.`role_name`, 'description', OLD.`description`, 'is_system', OLD.`is_system`, 'is_active', OLD.`is_active`), JSON_OBJECT('role_id', NEW.`role_id`, 'role_code', NEW.`role_code`, 'role_name', NEW.`role_name`, 'description', NEW.`description`, 'is_system', NEW.`is_system`, 'is_active', NEW.`is_active`), CURRENT_TIMESTAMP(6)); END IF; END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_unicode_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER `pass_history_roles_delete` AFTER DELETE ON `roles` FOR EACH ROW BEGIN INSERT INTO record_history (record_type, record_id, operation, actor_user_id, actor_label, source, ip_address, before_values, after_values, recorded_at)
                    VALUES ('roles', CONCAT_WS(':', OLD.`role_id`), 'deleted', @pass_history_actor, LEFT(@pass_history_label, 100), LEFT(@pass_history_source, 150), LEFT(@pass_history_ip, 45), JSON_OBJECT('role_id', OLD.`role_id`, 'role_code', OLD.`role_code`, 'role_name', OLD.`role_name`, 'description', OLD.`description`, 'is_system', OLD.`is_system`, 'is_active', OLD.`is_active`), NULL, CURRENT_TIMESTAMP(6)); END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;

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
  `section` varchar(60) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `photo_path` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `school_id_document_path` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `emergency_contact_name` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `emergency_contact_number` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `registration_category` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `registration_unit` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `school_id_check_status` varchar(32) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `school_id_check_result` json DEFAULT NULL,
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
INSERT INTO `user_profiles` VALUES (1,1,9,'Demo',NULL,'Parking Staff',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Parking Staff',NULL,NULL,NULL,NULL,NULL,NULL,'2026-10-12 23:17:03','2026-10-12 23:17:03',NULL,NULL,NULL,NULL),(32,32,1,'Andrea',NULL,'Villamor',NULL,'female','2004-03-15','0900-000--001','SAMPLE TEST DATA',NULL,NULL,'BS Computer Engineering','3rd Year',NULL,NULL,NULL,'SAMPLE TEST DATA',NULL,'2026-10-13 21:11:57','2026-10-13 21:11:57',NULL,NULL,NULL,NULL),(33,33,2,'Bryan',NULL,'Castillo',NULL,'male','2005-07-02','0900-000--002','SAMPLE TEST DATA',NULL,NULL,'BS Biology','2nd Year',NULL,NULL,NULL,'SAMPLE TEST DATA',NULL,'2026-10-13 21:11:57','2026-10-13 21:11:57',NULL,NULL,NULL,NULL),(34,34,3,'Celia',NULL,'Navarro',NULL,'female','1986-11-20','0900-000--001','SAMPLE TEST DATA',NULL,NULL,'Assistant Professor',NULL,NULL,NULL,NULL,'SAMPLE TEST DATA',NULL,'2026-10-13 21:11:58','2026-10-13 21:11:58',NULL,NULL,NULL,NULL),(35,35,4,'Dennis',NULL,'Aquino',NULL,'male','1990-01-08','0900-000--002','SAMPLE TEST DATA',NULL,NULL,'Instructor',NULL,NULL,NULL,NULL,'SAMPLE TEST DATA',NULL,'2026-10-13 21:11:58','2026-10-13 21:11:58',NULL,NULL,NULL,NULL),(36,36,7,'Elena',NULL,'Bautista',NULL,'female','1993-05-27','0900-000--001','SAMPLE TEST DATA',NULL,NULL,'HR Assistant',NULL,NULL,NULL,NULL,'SAMPLE TEST DATA',NULL,'2026-10-13 21:11:58','2026-10-13 21:11:58',NULL,NULL,NULL,NULL),(46,46,NULL,'Demo',NULL,'Administrator',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'PASS Administrator',NULL,NULL,NULL,NULL,NULL,NULL,'2026-10-15 00:15:47','2026-10-15 00:15:47',NULL,NULL,NULL,NULL),(47,47,NULL,'Maria',NULL,'Santos',NULL,NULL,NULL,'09171230001',NULL,NULL,NULL,'Guest / Visitor',NULL,NULL,NULL,NULL,NULL,NULL,'2026-10-15 00:15:48','2026-10-15 00:15:48',NULL,NULL,NULL,NULL),(48,48,NULL,'Rosa',NULL,'Villanueva',NULL,NULL,NULL,'09187654321',NULL,NULL,NULL,'Guest / Visitor',NULL,NULL,NULL,NULL,NULL,NULL,'2026-10-15 00:24:20','2026-10-15 00:24:20',NULL,NULL,NULL,NULL),(49,49,NULL,'guestito',NULL,'visitor',NULL,NULL,NULL,'09128012356',NULL,NULL,NULL,'Guest / Visitor',NULL,NULL,NULL,NULL,NULL,NULL,'2026-10-15 01:38:19','2026-10-15 01:38:19',NULL,NULL,NULL,NULL),(50,50,NULL,'Lara',NULL,'Gomez',NULL,NULL,NULL,'09175550144',NULL,NULL,NULL,'Guest / Visitor',NULL,NULL,NULL,NULL,NULL,NULL,'2026-10-15 03:33:13','2026-10-15 03:33:13',NULL,NULL,NULL,NULL);
/*!40000 ALTER TABLE `user_profiles` ENABLE KEYS */;
UNLOCK TABLES;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_unicode_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER `pass_history_user_profiles_insert` AFTER INSERT ON `user_profiles` FOR EACH ROW BEGIN INSERT INTO record_history (record_type, record_id, operation, actor_user_id, actor_label, source, ip_address, before_values, after_values, recorded_at)
                VALUES ('user_profiles', NEW.profile_id, 'created', @pass_history_actor, LEFT(@pass_history_label, 100), LEFT(@pass_history_source, 150), LEFT(@pass_history_ip, 45), NULL, JSON_OBJECT('profile_id', NEW.`profile_id`, 'user_id', NEW.`user_id`, 'department_id', NEW.`department_id`, 'first_name', NEW.`first_name`, 'middle_name', NEW.`middle_name`, 'last_name', NEW.`last_name`, 'suffix', NEW.`suffix`, 'sex', NEW.`sex`, 'birth_date', NEW.`birth_date`, 'contact_number', NEW.`contact_number`, 'address_line', NEW.`address_line`, 'city', NEW.`city`, 'province', NEW.`province`, 'program_or_position', NEW.`program_or_position`, 'year_level', NEW.`year_level`, 'section', NEW.`section`, 'photo_path', NEW.`photo_path`, 'school_id_document_path', NEW.`school_id_document_path`, 'emergency_contact_name', NEW.`emergency_contact_name`, 'emergency_contact_number', NEW.`emergency_contact_number`, 'created_at', NEW.`created_at`, 'updated_at', NEW.`updated_at`, 'registration_category', NEW.`registration_category`, 'registration_unit', NEW.`registration_unit`, 'school_id_check_status', NEW.`school_id_check_status`, 'school_id_check_result', NEW.`school_id_check_result`), CURRENT_TIMESTAMP(6)); END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_unicode_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER `pass_history_user_profiles_update` AFTER UPDATE ON `user_profiles` FOR EACH ROW BEGIN IF NOT (OLD.`profile_id` <=> NEW.`profile_id`) OR NOT (OLD.`user_id` <=> NEW.`user_id`) OR NOT (OLD.`department_id` <=> NEW.`department_id`) OR NOT (OLD.`first_name` <=> NEW.`first_name`) OR NOT (OLD.`middle_name` <=> NEW.`middle_name`) OR NOT (OLD.`last_name` <=> NEW.`last_name`) OR NOT (OLD.`suffix` <=> NEW.`suffix`) OR NOT (OLD.`sex` <=> NEW.`sex`) OR NOT (OLD.`birth_date` <=> NEW.`birth_date`) OR NOT (OLD.`contact_number` <=> NEW.`contact_number`) OR NOT (OLD.`address_line` <=> NEW.`address_line`) OR NOT (OLD.`city` <=> NEW.`city`) OR NOT (OLD.`province` <=> NEW.`province`) OR NOT (OLD.`program_or_position` <=> NEW.`program_or_position`) OR NOT (OLD.`year_level` <=> NEW.`year_level`) OR NOT (OLD.`section` <=> NEW.`section`) OR NOT (OLD.`photo_path` <=> NEW.`photo_path`) OR NOT (OLD.`school_id_document_path` <=> NEW.`school_id_document_path`) OR NOT (OLD.`emergency_contact_name` <=> NEW.`emergency_contact_name`) OR NOT (OLD.`emergency_contact_number` <=> NEW.`emergency_contact_number`) OR NOT (OLD.`created_at` <=> NEW.`created_at`) OR NOT (OLD.`updated_at` <=> NEW.`updated_at`) OR NOT (OLD.`registration_category` <=> NEW.`registration_category`) OR NOT (OLD.`registration_unit` <=> NEW.`registration_unit`) OR NOT (OLD.`school_id_check_status` <=> NEW.`school_id_check_status`) OR NOT (OLD.`school_id_check_result` <=> NEW.`school_id_check_result`) THEN INSERT INTO record_history (record_type, record_id, operation, actor_user_id, actor_label, source, ip_address, before_values, after_values, recorded_at)
                VALUES ('user_profiles', NEW.profile_id, 'updated', @pass_history_actor, LEFT(@pass_history_label, 100), LEFT(@pass_history_source, 150), LEFT(@pass_history_ip, 45), JSON_OBJECT('profile_id', OLD.`profile_id`, 'user_id', OLD.`user_id`, 'department_id', OLD.`department_id`, 'first_name', OLD.`first_name`, 'middle_name', OLD.`middle_name`, 'last_name', OLD.`last_name`, 'suffix', OLD.`suffix`, 'sex', OLD.`sex`, 'birth_date', OLD.`birth_date`, 'contact_number', OLD.`contact_number`, 'address_line', OLD.`address_line`, 'city', OLD.`city`, 'province', OLD.`province`, 'program_or_position', OLD.`program_or_position`, 'year_level', OLD.`year_level`, 'section', OLD.`section`, 'photo_path', OLD.`photo_path`, 'school_id_document_path', OLD.`school_id_document_path`, 'emergency_contact_name', OLD.`emergency_contact_name`, 'emergency_contact_number', OLD.`emergency_contact_number`, 'created_at', OLD.`created_at`, 'updated_at', OLD.`updated_at`, 'registration_category', OLD.`registration_category`, 'registration_unit', OLD.`registration_unit`, 'school_id_check_status', OLD.`school_id_check_status`, 'school_id_check_result', OLD.`school_id_check_result`), JSON_OBJECT('profile_id', NEW.`profile_id`, 'user_id', NEW.`user_id`, 'department_id', NEW.`department_id`, 'first_name', NEW.`first_name`, 'middle_name', NEW.`middle_name`, 'last_name', NEW.`last_name`, 'suffix', NEW.`suffix`, 'sex', NEW.`sex`, 'birth_date', NEW.`birth_date`, 'contact_number', NEW.`contact_number`, 'address_line', NEW.`address_line`, 'city', NEW.`city`, 'province', NEW.`province`, 'program_or_position', NEW.`program_or_position`, 'year_level', NEW.`year_level`, 'section', NEW.`section`, 'photo_path', NEW.`photo_path`, 'school_id_document_path', NEW.`school_id_document_path`, 'emergency_contact_name', NEW.`emergency_contact_name`, 'emergency_contact_number', NEW.`emergency_contact_number`, 'created_at', NEW.`created_at`, 'updated_at', NEW.`updated_at`, 'registration_category', NEW.`registration_category`, 'registration_unit', NEW.`registration_unit`, 'school_id_check_status', NEW.`school_id_check_status`, 'school_id_check_result', NEW.`school_id_check_result`), CURRENT_TIMESTAMP(6)); END IF; END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_unicode_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER `pass_history_user_profiles_delete` AFTER DELETE ON `user_profiles` FOR EACH ROW BEGIN INSERT INTO record_history (record_type, record_id, operation, actor_user_id, actor_label, source, ip_address, before_values, after_values, recorded_at)
                VALUES ('user_profiles', OLD.profile_id, 'deleted', @pass_history_actor, LEFT(@pass_history_label, 100), LEFT(@pass_history_source, 150), LEFT(@pass_history_ip, 45), JSON_OBJECT('profile_id', OLD.`profile_id`, 'user_id', OLD.`user_id`, 'department_id', OLD.`department_id`, 'first_name', OLD.`first_name`, 'middle_name', OLD.`middle_name`, 'last_name', OLD.`last_name`, 'suffix', OLD.`suffix`, 'sex', OLD.`sex`, 'birth_date', OLD.`birth_date`, 'contact_number', OLD.`contact_number`, 'address_line', OLD.`address_line`, 'city', OLD.`city`, 'province', OLD.`province`, 'program_or_position', OLD.`program_or_position`, 'year_level', OLD.`year_level`, 'section', OLD.`section`, 'photo_path', OLD.`photo_path`, 'school_id_document_path', OLD.`school_id_document_path`, 'emergency_contact_name', OLD.`emergency_contact_name`, 'emergency_contact_number', OLD.`emergency_contact_number`, 'created_at', OLD.`created_at`, 'updated_at', OLD.`updated_at`, 'registration_category', OLD.`registration_category`, 'registration_unit', OLD.`registration_unit`, 'school_id_check_status', OLD.`school_id_check_status`, 'school_id_check_result', OLD.`school_id_check_result`), NULL, CURRENT_TIMESTAMP(6)); END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;

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
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_unicode_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER `pass_history_user_roles_insert` AFTER INSERT ON `user_roles` FOR EACH ROW BEGIN INSERT INTO record_history (record_type, record_id, operation, actor_user_id, actor_label, source, ip_address, before_values, after_values, recorded_at)
                    VALUES ('user_roles', CONCAT_WS(':', NEW.`user_id`, NEW.`role_id`), 'created', @pass_history_actor, LEFT(@pass_history_label, 100), LEFT(@pass_history_source, 150), LEFT(@pass_history_ip, 45), NULL, JSON_OBJECT('user_id', NEW.`user_id`, 'role_id', NEW.`role_id`, 'assigned_at', NEW.`assigned_at`, 'assigned_by', NEW.`assigned_by`), CURRENT_TIMESTAMP(6)); END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_unicode_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER `pass_history_user_roles_update` AFTER UPDATE ON `user_roles` FOR EACH ROW BEGIN IF NOT (OLD.`user_id` <=> NEW.`user_id`) OR NOT (OLD.`role_id` <=> NEW.`role_id`) OR NOT (OLD.`assigned_at` <=> NEW.`assigned_at`) OR NOT (OLD.`assigned_by` <=> NEW.`assigned_by`) THEN INSERT INTO record_history (record_type, record_id, operation, actor_user_id, actor_label, source, ip_address, before_values, after_values, recorded_at)
                    VALUES ('user_roles', CONCAT_WS(':', NEW.`user_id`, NEW.`role_id`), 'updated', @pass_history_actor, LEFT(@pass_history_label, 100), LEFT(@pass_history_source, 150), LEFT(@pass_history_ip, 45), JSON_OBJECT('user_id', OLD.`user_id`, 'role_id', OLD.`role_id`, 'assigned_at', OLD.`assigned_at`, 'assigned_by', OLD.`assigned_by`), JSON_OBJECT('user_id', NEW.`user_id`, 'role_id', NEW.`role_id`, 'assigned_at', NEW.`assigned_at`, 'assigned_by', NEW.`assigned_by`), CURRENT_TIMESTAMP(6)); END IF; END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_unicode_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER `pass_history_user_roles_delete` AFTER DELETE ON `user_roles` FOR EACH ROW BEGIN INSERT INTO record_history (record_type, record_id, operation, actor_user_id, actor_label, source, ip_address, before_values, after_values, recorded_at)
                    VALUES ('user_roles', CONCAT_WS(':', OLD.`user_id`, OLD.`role_id`), 'deleted', @pass_history_actor, LEFT(@pass_history_label, 100), LEFT(@pass_history_source, 150), LEFT(@pass_history_ip, 45), JSON_OBJECT('user_id', OLD.`user_id`, 'role_id', OLD.`role_id`, 'assigned_at', OLD.`assigned_at`, 'assigned_by', OLD.`assigned_by`), NULL, CURRENT_TIMESTAMP(6)); END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;

--
-- Table structure for table `user_vehicle_selections`
--

DROP TABLE IF EXISTS `user_vehicle_selections`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `user_vehicle_selections` (
  `user_id` bigint unsigned NOT NULL,
  `vehicle_id` bigint unsigned NOT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`user_id`),
  KEY `user_vehicle_selections_vehicle_id_foreign` (`vehicle_id`),
  CONSTRAINT `user_vehicle_selections_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`),
  CONSTRAINT `user_vehicle_selections_vehicle_id_foreign` FOREIGN KEY (`vehicle_id`) REFERENCES `vehicles` (`vehicle_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user_vehicle_selections`
--

LOCK TABLES `user_vehicle_selections` WRITE;
/*!40000 ALTER TABLE `user_vehicle_selections` DISABLE KEYS */;
INSERT INTO `user_vehicle_selections` VALUES (32,51,'2026-10-22 23:29:55'),(34,53,'2026-10-22 23:29:55'),(35,76,'2026-10-22 23:29:55'),(36,55,'2026-10-22 23:29:55');
/*!40000 ALTER TABLE `user_vehicle_selections` ENABLE KEYS */;
UNLOCK TABLES;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_unicode_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER `pass_history_user_vehicle_selections_insert` AFTER INSERT ON `user_vehicle_selections` FOR EACH ROW BEGIN INSERT INTO record_history (record_type, record_id, operation, actor_user_id, actor_label, source, ip_address, before_values, after_values, recorded_at)
                    VALUES ('user_vehicle_selections', CONCAT_WS(':', NEW.`user_id`), 'created', @pass_history_actor, LEFT(@pass_history_label, 100), LEFT(@pass_history_source, 150), LEFT(@pass_history_ip, 45), NULL, JSON_OBJECT('user_id', NEW.`user_id`, 'vehicle_id', NEW.`vehicle_id`, 'updated_at', NEW.`updated_at`), CURRENT_TIMESTAMP(6)); END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_unicode_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER `pass_history_user_vehicle_selections_update` AFTER UPDATE ON `user_vehicle_selections` FOR EACH ROW BEGIN IF NOT (OLD.`user_id` <=> NEW.`user_id`) OR NOT (OLD.`vehicle_id` <=> NEW.`vehicle_id`) OR NOT (OLD.`updated_at` <=> NEW.`updated_at`) THEN INSERT INTO record_history (record_type, record_id, operation, actor_user_id, actor_label, source, ip_address, before_values, after_values, recorded_at)
                    VALUES ('user_vehicle_selections', CONCAT_WS(':', NEW.`user_id`), 'updated', @pass_history_actor, LEFT(@pass_history_label, 100), LEFT(@pass_history_source, 150), LEFT(@pass_history_ip, 45), JSON_OBJECT('user_id', OLD.`user_id`, 'vehicle_id', OLD.`vehicle_id`, 'updated_at', OLD.`updated_at`), JSON_OBJECT('user_id', NEW.`user_id`, 'vehicle_id', NEW.`vehicle_id`, 'updated_at', NEW.`updated_at`), CURRENT_TIMESTAMP(6)); END IF; END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_unicode_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER `pass_history_user_vehicle_selections_delete` AFTER DELETE ON `user_vehicle_selections` FOR EACH ROW BEGIN INSERT INTO record_history (record_type, record_id, operation, actor_user_id, actor_label, source, ip_address, before_values, after_values, recorded_at)
                    VALUES ('user_vehicle_selections', CONCAT_WS(':', OLD.`user_id`), 'deleted', @pass_history_actor, LEFT(@pass_history_label, 100), LEFT(@pass_history_source, 150), LEFT(@pass_history_ip, 45), JSON_OBJECT('user_id', OLD.`user_id`, 'vehicle_id', OLD.`vehicle_id`, 'updated_at', OLD.`updated_at`), NULL, CURRENT_TIMESTAMP(6)); END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;

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
  `user_code_key` varchar(50) COLLATE utf8mb4_unicode_ci GENERATED ALWAYS AS (replace(replace(upper(`user_code`),_utf8mb4'-',_utf8mb4''),_utf8mb4' ',_utf8mb4'')) STORED,
  PRIMARY KEY (`user_id`),
  UNIQUE KEY `users_user_code_unique` (`user_code`),
  UNIQUE KEY `users_email_unique` (`email`),
  UNIQUE KEY `users_user_code_key_unique` (`user_code_key`)
) ENGINE=InnoDB AUTO_INCREMENT=51 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` (`user_id`, `user_code`, `email`, `password`, `user_type`, `account_status`, `email_verified_at`, `last_login_at`, `remember_token`, `created_at`, `updated_at`, `deleted_at`) VALUES (1,'SEC-DEMO-001','security.demo@pass.local','$2y$12$DG/I5gx9ZpQrff1XCVf20.fH5BHLHVITtlRel9zNa/7kjUXPkltHq','security','active','2026-10-12 23:17:03','2026-10-23 00:54:40','nEBFzJiahQI4bKOiH55CQVayp4mIh903NeEUUFYn68NGwBvCXO9NTXA0GP6c','2026-10-12 23:17:03','2026-10-23 00:54:40',NULL),(32,'SAMPLE-STU-001','sample-stu-001@sample.pass.test','$2y$12$VAhhMImSAyaSqqfErxDZFu8p/t6FBPgEt9yVfSzFCIbRIAb7N0Bxe','student','active','2026-10-13 21:11:57','2026-10-13 20:10:14',NULL,'2026-10-13 17:16:32','2026-10-13 21:11:57',NULL),(33,'SAMPLE-STU-002','sample-stu-002@sample.pass.test','$2y$12$/kTbisV.1me7gc4zzHEZDuWwIWu1SJEQBUD6ICt51D1dnBv/Klqgq','student','active','2026-10-13 21:11:57','2026-10-13 21:10:55',NULL,'2026-10-13 17:16:32','2026-10-13 21:11:57',NULL),(34,'SAMPLE-FAC-001','sample-fac-001@sample.pass.test','$2y$12$ahReY/7.hjWo2TFW0CqKz.AjEV6uIE0TtNvJX1mNNnaVTFHU9okUC','faculty','active','2026-10-13 21:11:58','2026-10-13 20:10:15',NULL,'2026-10-13 17:16:33','2026-10-13 21:11:58',NULL),(35,'SAMPLE-FAC-002','sample-fac-002@sample.pass.test','$2y$12$a5xS4eoa118KUW3RFSzB3erVNDU/xze.pUVm35fHjFIoZH10284WG','faculty','active','2026-10-13 21:11:58','2026-10-13 20:10:15',NULL,'2026-10-13 17:16:33','2026-10-13 21:11:58',NULL),(36,'SAMPLE-EMP-001','sample-emp-001@sample.pass.test','$2y$12$Whi99Tuqxu0nFDvmP8k5J.SpR3y9iT31yDIZgO3WYNdCAMbLb7GU.','employee','active','2026-10-13 21:11:58','2026-10-13 21:10:31',NULL,'2026-10-13 17:16:34','2026-10-13 21:11:58',NULL),(46,'ADM-DEMO-001','admin.demo@pass.local','$2y$12$wW8WuY/gykeVTDk62SoBEuURUy3td8fV2mKxeJeX9zOFTVpFDXNXO','admin','active','2026-10-15 00:15:47','2026-10-15 08:38:57',NULL,'2026-10-15 00:15:47','2026-10-15 08:38:57',NULL),(47,'GST-DEMO-001','guest.demo@pass.test','$2y$12$mZwxVEj2jqhEEnTtGX8cveDuBD3V2loLERC1kr2Jw4gF4Bjg0M2Wa','other','active','2026-10-15 00:15:48',NULL,NULL,'2026-10-15 00:15:48','2026-10-15 00:15:48',NULL),(48,'GST-M9SC2UDB','rosa.villanueva.test@example.com','$2y$12$4rGtriSCeX.ju.PxerMu4.lJGa.ZDT9H9kGaiTolJ//NK3fkKuKmS','other','active',NULL,'2026-10-15 00:30:28',NULL,'2026-10-15 00:24:20','2026-10-15 00:30:28',NULL),(49,'GST-20UF3BER','guest@pass.com','$2y$12$bm21ym.MXBnY7I0HN3Twle4WYEghqD5VLv2IJcLEd2oOfYREw9M0G','other','active',NULL,'2026-10-15 01:39:01',NULL,'2026-10-15 01:38:19','2026-10-15 01:39:01',NULL),(50,'GST-BW3KZLQ4','lara.gomez.test@example.com','$2y$12$2fVDYxhwgrrRRtfMQ1Xjs.f8eGG3Y11xzRBN3mHz8WyrmxbuV5PXi','other','active',NULL,'2026-10-15 03:35:35',NULL,'2026-10-15 03:33:13','2026-10-15 03:35:35',NULL);
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_unicode_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER `pass_history_users_insert` AFTER INSERT ON `users` FOR EACH ROW BEGIN INSERT INTO record_history (record_type, record_id, operation, actor_user_id, actor_label, source, ip_address, before_values, after_values, recorded_at)
                    VALUES ('users', CONCAT_WS(':', NEW.`user_id`), 'created', @pass_history_actor, LEFT(@pass_history_label, 100), LEFT(@pass_history_source, 150), LEFT(@pass_history_ip, 45), NULL, JSON_OBJECT('user_id', NEW.`user_id`, 'user_code', NEW.`user_code`, 'email', NEW.`email`, 'user_type', NEW.`user_type`, 'account_status', NEW.`account_status`, 'email_verified_at', NEW.`email_verified_at`, 'last_login_at', NEW.`last_login_at`, 'created_at', NEW.`created_at`, 'updated_at', NEW.`updated_at`, 'deleted_at', NEW.`deleted_at`, 'user_code_key', NEW.`user_code_key`), CURRENT_TIMESTAMP(6)); END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_unicode_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER `pass_history_users_update` AFTER UPDATE ON `users` FOR EACH ROW BEGIN IF NOT (OLD.`user_id` <=> NEW.`user_id`) OR NOT (OLD.`user_code` <=> NEW.`user_code`) OR NOT (OLD.`email` <=> NEW.`email`) OR NOT (OLD.`user_type` <=> NEW.`user_type`) OR NOT (OLD.`account_status` <=> NEW.`account_status`) OR NOT (OLD.`email_verified_at` <=> NEW.`email_verified_at`) OR NOT (OLD.`last_login_at` <=> NEW.`last_login_at`) OR NOT (OLD.`created_at` <=> NEW.`created_at`) OR NOT (OLD.`updated_at` <=> NEW.`updated_at`) OR NOT (OLD.`deleted_at` <=> NEW.`deleted_at`) OR NOT (OLD.`user_code_key` <=> NEW.`user_code_key`) THEN INSERT INTO record_history (record_type, record_id, operation, actor_user_id, actor_label, source, ip_address, before_values, after_values, recorded_at)
                    VALUES ('users', CONCAT_WS(':', NEW.`user_id`), CASE WHEN OLD.deleted_at IS NULL AND NEW.deleted_at IS NOT NULL THEN 'archived' WHEN OLD.deleted_at IS NOT NULL AND NEW.deleted_at IS NULL THEN 'restored' ELSE 'updated' END, @pass_history_actor, LEFT(@pass_history_label, 100), LEFT(@pass_history_source, 150), LEFT(@pass_history_ip, 45), JSON_OBJECT('user_id', OLD.`user_id`, 'user_code', OLD.`user_code`, 'email', OLD.`email`, 'user_type', OLD.`user_type`, 'account_status', OLD.`account_status`, 'email_verified_at', OLD.`email_verified_at`, 'last_login_at', OLD.`last_login_at`, 'created_at', OLD.`created_at`, 'updated_at', OLD.`updated_at`, 'deleted_at', OLD.`deleted_at`, 'user_code_key', OLD.`user_code_key`), JSON_OBJECT('user_id', NEW.`user_id`, 'user_code', NEW.`user_code`, 'email', NEW.`email`, 'user_type', NEW.`user_type`, 'account_status', NEW.`account_status`, 'email_verified_at', NEW.`email_verified_at`, 'last_login_at', NEW.`last_login_at`, 'created_at', NEW.`created_at`, 'updated_at', NEW.`updated_at`, 'deleted_at', NEW.`deleted_at`, 'user_code_key', NEW.`user_code_key`), CURRENT_TIMESTAMP(6)); END IF; END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_unicode_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER `pass_history_users_delete` AFTER DELETE ON `users` FOR EACH ROW BEGIN INSERT INTO record_history (record_type, record_id, operation, actor_user_id, actor_label, source, ip_address, before_values, after_values, recorded_at)
                    VALUES ('users', CONCAT_WS(':', OLD.`user_id`), 'deleted', @pass_history_actor, LEFT(@pass_history_label, 100), LEFT(@pass_history_source, 150), LEFT(@pass_history_ip, 45), JSON_OBJECT('user_id', OLD.`user_id`, 'user_code', OLD.`user_code`, 'email', OLD.`email`, 'user_type', OLD.`user_type`, 'account_status', OLD.`account_status`, 'email_verified_at', OLD.`email_verified_at`, 'last_login_at', OLD.`last_login_at`, 'created_at', OLD.`created_at`, 'updated_at', OLD.`updated_at`, 'deleted_at', OLD.`deleted_at`, 'user_code_key', OLD.`user_code_key`), NULL, CURRENT_TIMESTAMP(6)); END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;

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
  `plate_check_status` varchar(32) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `plate_check_run_id` char(36) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `plate_check_message` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `plate_checked_at` datetime DEFAULT NULL,
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
INSERT INTO `vehicle_authorizations` (`authorization_id`, `vehicle_id`, `parking_area_id`, `sticker_number`, `authorization_status`, `valid_from`, `valid_until`, `requested_at`, `reviewed_by`, `reviewed_at`, `remarks`, `created_at`, `updated_at`, `plate_check_status`, `plate_check_run_id`, `plate_check_message`, `plate_checked_at`) VALUES (35,51,NULL,'SAMPLE-STK-1001','suspended','2026-09-04','2027-08-14','2026-08-31 09:00:00',1,'2026-10-15 11:49:11','wrong parking','2026-08-30 16:00:00','2026-10-15 03:49:11',NULL,NULL,NULL,NULL),(36,52,NULL,NULL,'pending',NULL,NULL,'2026-10-12 09:00:00',NULL,NULL,NULL,'2026-08-30 16:00:00','2026-09-02 16:00:00',NULL,NULL,NULL,NULL),(37,53,NULL,'SAMPLE-STK-1003','suspended','2026-09-04','2027-08-14','2026-08-31 09:00:00',1,'2026-10-11 14:00:00','SAMPLE: Suspended pending review of a parking violation (test data).','2026-08-30 16:00:00','2026-10-10 16:00:00',NULL,NULL,NULL,NULL),(38,54,NULL,NULL,'rejected',NULL,NULL,'2026-08-31 09:00:00',1,'2026-09-03 14:00:00','SAMPLE: OR/CR details did not match the vehicle (test data).','2026-08-30 16:00:00','2026-09-02 16:00:00',NULL,NULL,NULL,NULL),(39,55,NULL,'SAMPLE-STK-1005','approved','2026-09-04','2027-08-14','2026-08-31 09:00:00',1,'2026-09-03 14:00:00',NULL,'2026-08-30 16:00:00','2026-09-02 16:00:00',NULL,NULL,NULL,NULL),(40,76,NULL,'SAMPLE-STK-2004','approved','2026-09-04','2027-08-14','2026-08-31 09:00:00',1,'2026-09-03 14:00:00',NULL,'2026-08-30 16:00:00','2026-09-02 16:00:00',NULL,NULL,NULL,NULL);
/*!40000 ALTER TABLE `vehicle_authorizations` ENABLE KEYS */;
UNLOCK TABLES;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_unicode_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER `pass_history_vehicle_authorizations_insert` AFTER INSERT ON `vehicle_authorizations` FOR EACH ROW BEGIN INSERT INTO record_history (record_type, record_id, operation, actor_user_id, actor_label, source, ip_address, before_values, after_values, recorded_at)
                    VALUES ('vehicle_authorizations', CONCAT_WS(':', NEW.`authorization_id`), 'created', @pass_history_actor, LEFT(@pass_history_label, 100), LEFT(@pass_history_source, 150), LEFT(@pass_history_ip, 45), NULL, JSON_OBJECT('authorization_id', NEW.`authorization_id`, 'vehicle_id', NEW.`vehicle_id`, 'parking_area_id', NEW.`parking_area_id`, 'sticker_number', NEW.`sticker_number`, 'authorization_status', NEW.`authorization_status`, 'valid_from', NEW.`valid_from`, 'valid_until', NEW.`valid_until`, 'requested_at', NEW.`requested_at`, 'reviewed_by', NEW.`reviewed_by`, 'reviewed_at', NEW.`reviewed_at`, 'remarks', NEW.`remarks`, 'created_at', NEW.`created_at`, 'updated_at', NEW.`updated_at`, 'active_flag', NEW.`active_flag`), CURRENT_TIMESTAMP(6)); END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_unicode_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER `pass_history_vehicle_authorizations_update` AFTER UPDATE ON `vehicle_authorizations` FOR EACH ROW BEGIN IF NOT (OLD.`authorization_id` <=> NEW.`authorization_id`) OR NOT (OLD.`vehicle_id` <=> NEW.`vehicle_id`) OR NOT (OLD.`parking_area_id` <=> NEW.`parking_area_id`) OR NOT (OLD.`sticker_number` <=> NEW.`sticker_number`) OR NOT (OLD.`authorization_status` <=> NEW.`authorization_status`) OR NOT (OLD.`valid_from` <=> NEW.`valid_from`) OR NOT (OLD.`valid_until` <=> NEW.`valid_until`) OR NOT (OLD.`requested_at` <=> NEW.`requested_at`) OR NOT (OLD.`reviewed_by` <=> NEW.`reviewed_by`) OR NOT (OLD.`reviewed_at` <=> NEW.`reviewed_at`) OR NOT (OLD.`remarks` <=> NEW.`remarks`) OR NOT (OLD.`created_at` <=> NEW.`created_at`) OR NOT (OLD.`updated_at` <=> NEW.`updated_at`) OR NOT (OLD.`active_flag` <=> NEW.`active_flag`) THEN INSERT INTO record_history (record_type, record_id, operation, actor_user_id, actor_label, source, ip_address, before_values, after_values, recorded_at)
                    VALUES ('vehicle_authorizations', CONCAT_WS(':', NEW.`authorization_id`), 'updated', @pass_history_actor, LEFT(@pass_history_label, 100), LEFT(@pass_history_source, 150), LEFT(@pass_history_ip, 45), JSON_OBJECT('authorization_id', OLD.`authorization_id`, 'vehicle_id', OLD.`vehicle_id`, 'parking_area_id', OLD.`parking_area_id`, 'sticker_number', OLD.`sticker_number`, 'authorization_status', OLD.`authorization_status`, 'valid_from', OLD.`valid_from`, 'valid_until', OLD.`valid_until`, 'requested_at', OLD.`requested_at`, 'reviewed_by', OLD.`reviewed_by`, 'reviewed_at', OLD.`reviewed_at`, 'remarks', OLD.`remarks`, 'created_at', OLD.`created_at`, 'updated_at', OLD.`updated_at`, 'active_flag', OLD.`active_flag`), JSON_OBJECT('authorization_id', NEW.`authorization_id`, 'vehicle_id', NEW.`vehicle_id`, 'parking_area_id', NEW.`parking_area_id`, 'sticker_number', NEW.`sticker_number`, 'authorization_status', NEW.`authorization_status`, 'valid_from', NEW.`valid_from`, 'valid_until', NEW.`valid_until`, 'requested_at', NEW.`requested_at`, 'reviewed_by', NEW.`reviewed_by`, 'reviewed_at', NEW.`reviewed_at`, 'remarks', NEW.`remarks`, 'created_at', NEW.`created_at`, 'updated_at', NEW.`updated_at`, 'active_flag', NEW.`active_flag`), CURRENT_TIMESTAMP(6)); END IF; END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_unicode_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER `pass_history_vehicle_authorizations_delete` AFTER DELETE ON `vehicle_authorizations` FOR EACH ROW BEGIN INSERT INTO record_history (record_type, record_id, operation, actor_user_id, actor_label, source, ip_address, before_values, after_values, recorded_at)
                    VALUES ('vehicle_authorizations', CONCAT_WS(':', OLD.`authorization_id`), 'deleted', @pass_history_actor, LEFT(@pass_history_label, 100), LEFT(@pass_history_source, 150), LEFT(@pass_history_ip, 45), JSON_OBJECT('authorization_id', OLD.`authorization_id`, 'vehicle_id', OLD.`vehicle_id`, 'parking_area_id', OLD.`parking_area_id`, 'sticker_number', OLD.`sticker_number`, 'authorization_status', OLD.`authorization_status`, 'valid_from', OLD.`valid_from`, 'valid_until', OLD.`valid_until`, 'requested_at', OLD.`requested_at`, 'reviewed_by', OLD.`reviewed_by`, 'reviewed_at', OLD.`reviewed_at`, 'remarks', OLD.`remarks`, 'created_at', OLD.`created_at`, 'updated_at', OLD.`updated_at`, 'active_flag', OLD.`active_flag`), NULL, CURRENT_TIMESTAMP(6)); END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;

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
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_unicode_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER `pass_history_vehicle_driver_insert` AFTER INSERT ON `vehicle_driver` FOR EACH ROW BEGIN INSERT INTO record_history (record_type, record_id, operation, actor_user_id, actor_label, source, ip_address, before_values, after_values, recorded_at)
                    VALUES ('vehicle_driver', CONCAT_WS(':', NEW.`vehicle_id`, NEW.`driver_id`), 'created', @pass_history_actor, LEFT(@pass_history_label, 100), LEFT(@pass_history_source, 150), LEFT(@pass_history_ip, 45), NULL, JSON_OBJECT('vehicle_id', NEW.`vehicle_id`, 'driver_id', NEW.`driver_id`, 'relationship', NEW.`relationship`, 'is_primary', NEW.`is_primary`, 'authorized_from', NEW.`authorized_from`, 'authorized_until', NEW.`authorized_until`, 'created_at', NEW.`created_at`, 'updated_at', NEW.`updated_at`), CURRENT_TIMESTAMP(6)); END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_unicode_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER `pass_history_vehicle_driver_update` AFTER UPDATE ON `vehicle_driver` FOR EACH ROW BEGIN IF NOT (OLD.`vehicle_id` <=> NEW.`vehicle_id`) OR NOT (OLD.`driver_id` <=> NEW.`driver_id`) OR NOT (OLD.`relationship` <=> NEW.`relationship`) OR NOT (OLD.`is_primary` <=> NEW.`is_primary`) OR NOT (OLD.`authorized_from` <=> NEW.`authorized_from`) OR NOT (OLD.`authorized_until` <=> NEW.`authorized_until`) OR NOT (OLD.`created_at` <=> NEW.`created_at`) OR NOT (OLD.`updated_at` <=> NEW.`updated_at`) THEN INSERT INTO record_history (record_type, record_id, operation, actor_user_id, actor_label, source, ip_address, before_values, after_values, recorded_at)
                    VALUES ('vehicle_driver', CONCAT_WS(':', NEW.`vehicle_id`, NEW.`driver_id`), 'updated', @pass_history_actor, LEFT(@pass_history_label, 100), LEFT(@pass_history_source, 150), LEFT(@pass_history_ip, 45), JSON_OBJECT('vehicle_id', OLD.`vehicle_id`, 'driver_id', OLD.`driver_id`, 'relationship', OLD.`relationship`, 'is_primary', OLD.`is_primary`, 'authorized_from', OLD.`authorized_from`, 'authorized_until', OLD.`authorized_until`, 'created_at', OLD.`created_at`, 'updated_at', OLD.`updated_at`), JSON_OBJECT('vehicle_id', NEW.`vehicle_id`, 'driver_id', NEW.`driver_id`, 'relationship', NEW.`relationship`, 'is_primary', NEW.`is_primary`, 'authorized_from', NEW.`authorized_from`, 'authorized_until', NEW.`authorized_until`, 'created_at', NEW.`created_at`, 'updated_at', NEW.`updated_at`), CURRENT_TIMESTAMP(6)); END IF; END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_unicode_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER `pass_history_vehicle_driver_delete` AFTER DELETE ON `vehicle_driver` FOR EACH ROW BEGIN INSERT INTO record_history (record_type, record_id, operation, actor_user_id, actor_label, source, ip_address, before_values, after_values, recorded_at)
                    VALUES ('vehicle_driver', CONCAT_WS(':', OLD.`vehicle_id`, OLD.`driver_id`), 'deleted', @pass_history_actor, LEFT(@pass_history_label, 100), LEFT(@pass_history_source, 150), LEFT(@pass_history_ip, 45), JSON_OBJECT('vehicle_id', OLD.`vehicle_id`, 'driver_id', OLD.`driver_id`, 'relationship', OLD.`relationship`, 'is_primary', OLD.`is_primary`, 'authorized_from', OLD.`authorized_from`, 'authorized_until', OLD.`authorized_until`, 'created_at', OLD.`created_at`, 'updated_at', OLD.`updated_at`), NULL, CURRENT_TIMESTAMP(6)); END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;

--
-- Table structure for table `vehicle_release_requests`
--

DROP TABLE IF EXISTS `vehicle_release_requests`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vehicle_release_requests` (
  `release_id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `transaction_id` bigint unsigned NOT NULL,
  `owner_user_id` bigint unsigned NOT NULL,
  `requested_by` bigint unsigned NOT NULL,
  `driver_name` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL,
  `driver_contact` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL,
  `license_encrypted` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `license_expiry` date NOT NULL,
  `reason` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `exception_reason` text COLLATE utf8mb4_unicode_ci,
  `status` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `expires_at` datetime NOT NULL,
  `reviewed_by` bigint unsigned DEFAULT NULL,
  `reviewed_at` datetime DEFAULT NULL,
  `review_remarks` text COLLATE utf8mb4_unicode_ci,
  `released_by` bigint unsigned DEFAULT NULL,
  `released_at` datetime DEFAULT NULL,
  `exit_event_id` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`release_id`),
  KEY `vehicle_release_requests_owner_user_id_foreign` (`owner_user_id`),
  KEY `vehicle_release_requests_requested_by_foreign` (`requested_by`),
  KEY `vehicle_release_requests_reviewed_by_foreign` (`reviewed_by`),
  KEY `vehicle_release_requests_released_by_foreign` (`released_by`),
  KEY `vehicle_release_requests_exit_event_id_foreign` (`exit_event_id`),
  KEY `vehicle_release_requests_transaction_id_status_index` (`transaction_id`,`status`),
  CONSTRAINT `vehicle_release_requests_exit_event_id_foreign` FOREIGN KEY (`exit_event_id`) REFERENCES `access_events` (`event_id`),
  CONSTRAINT `vehicle_release_requests_owner_user_id_foreign` FOREIGN KEY (`owner_user_id`) REFERENCES `users` (`user_id`),
  CONSTRAINT `vehicle_release_requests_released_by_foreign` FOREIGN KEY (`released_by`) REFERENCES `users` (`user_id`),
  CONSTRAINT `vehicle_release_requests_requested_by_foreign` FOREIGN KEY (`requested_by`) REFERENCES `users` (`user_id`),
  CONSTRAINT `vehicle_release_requests_reviewed_by_foreign` FOREIGN KEY (`reviewed_by`) REFERENCES `users` (`user_id`),
  CONSTRAINT `vehicle_release_requests_transaction_id_foreign` FOREIGN KEY (`transaction_id`) REFERENCES `parking_transactions` (`transaction_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vehicle_release_requests`
--

LOCK TABLES `vehicle_release_requests` WRITE;
/*!40000 ALTER TABLE `vehicle_release_requests` DISABLE KEYS */;
/*!40000 ALTER TABLE `vehicle_release_requests` ENABLE KEYS */;
UNLOCK TABLES;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_unicode_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER `pass_history_vehicle_release_requests_insert` AFTER INSERT ON `vehicle_release_requests` FOR EACH ROW BEGIN INSERT INTO record_history (record_type, record_id, operation, actor_user_id, actor_label, source, ip_address, before_values, after_values, recorded_at)
                    VALUES ('vehicle_release_requests', CONCAT_WS(':', NEW.`release_id`), 'created', @pass_history_actor, LEFT(@pass_history_label, 100), LEFT(@pass_history_source, 150), LEFT(@pass_history_ip, 45), NULL, JSON_OBJECT('release_id', NEW.`release_id`, 'transaction_id', NEW.`transaction_id`, 'owner_user_id', NEW.`owner_user_id`, 'requested_by', NEW.`requested_by`, 'driver_name', NEW.`driver_name`, 'driver_contact', NEW.`driver_contact`, 'license_encrypted', NEW.`license_encrypted`, 'license_expiry', NEW.`license_expiry`, 'reason', NEW.`reason`, 'exception_reason', NEW.`exception_reason`, 'status', NEW.`status`, 'expires_at', NEW.`expires_at`, 'reviewed_by', NEW.`reviewed_by`, 'reviewed_at', NEW.`reviewed_at`, 'review_remarks', NEW.`review_remarks`, 'released_by', NEW.`released_by`, 'released_at', NEW.`released_at`, 'exit_event_id', NEW.`exit_event_id`, 'created_at', NEW.`created_at`, 'updated_at', NEW.`updated_at`), CURRENT_TIMESTAMP(6)); END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_unicode_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER `pass_history_vehicle_release_requests_update` AFTER UPDATE ON `vehicle_release_requests` FOR EACH ROW BEGIN IF NOT (OLD.`release_id` <=> NEW.`release_id`) OR NOT (OLD.`transaction_id` <=> NEW.`transaction_id`) OR NOT (OLD.`owner_user_id` <=> NEW.`owner_user_id`) OR NOT (OLD.`requested_by` <=> NEW.`requested_by`) OR NOT (OLD.`driver_name` <=> NEW.`driver_name`) OR NOT (OLD.`driver_contact` <=> NEW.`driver_contact`) OR NOT (OLD.`license_encrypted` <=> NEW.`license_encrypted`) OR NOT (OLD.`license_expiry` <=> NEW.`license_expiry`) OR NOT (OLD.`reason` <=> NEW.`reason`) OR NOT (OLD.`exception_reason` <=> NEW.`exception_reason`) OR NOT (OLD.`status` <=> NEW.`status`) OR NOT (OLD.`expires_at` <=> NEW.`expires_at`) OR NOT (OLD.`reviewed_by` <=> NEW.`reviewed_by`) OR NOT (OLD.`reviewed_at` <=> NEW.`reviewed_at`) OR NOT (OLD.`review_remarks` <=> NEW.`review_remarks`) OR NOT (OLD.`released_by` <=> NEW.`released_by`) OR NOT (OLD.`released_at` <=> NEW.`released_at`) OR NOT (OLD.`exit_event_id` <=> NEW.`exit_event_id`) OR NOT (OLD.`created_at` <=> NEW.`created_at`) OR NOT (OLD.`updated_at` <=> NEW.`updated_at`) THEN INSERT INTO record_history (record_type, record_id, operation, actor_user_id, actor_label, source, ip_address, before_values, after_values, recorded_at)
                    VALUES ('vehicle_release_requests', CONCAT_WS(':', NEW.`release_id`), 'updated', @pass_history_actor, LEFT(@pass_history_label, 100), LEFT(@pass_history_source, 150), LEFT(@pass_history_ip, 45), JSON_OBJECT('release_id', OLD.`release_id`, 'transaction_id', OLD.`transaction_id`, 'owner_user_id', OLD.`owner_user_id`, 'requested_by', OLD.`requested_by`, 'driver_name', OLD.`driver_name`, 'driver_contact', OLD.`driver_contact`, 'license_encrypted', OLD.`license_encrypted`, 'license_expiry', OLD.`license_expiry`, 'reason', OLD.`reason`, 'exception_reason', OLD.`exception_reason`, 'status', OLD.`status`, 'expires_at', OLD.`expires_at`, 'reviewed_by', OLD.`reviewed_by`, 'reviewed_at', OLD.`reviewed_at`, 'review_remarks', OLD.`review_remarks`, 'released_by', OLD.`released_by`, 'released_at', OLD.`released_at`, 'exit_event_id', OLD.`exit_event_id`, 'created_at', OLD.`created_at`, 'updated_at', OLD.`updated_at`), JSON_OBJECT('release_id', NEW.`release_id`, 'transaction_id', NEW.`transaction_id`, 'owner_user_id', NEW.`owner_user_id`, 'requested_by', NEW.`requested_by`, 'driver_name', NEW.`driver_name`, 'driver_contact', NEW.`driver_contact`, 'license_encrypted', NEW.`license_encrypted`, 'license_expiry', NEW.`license_expiry`, 'reason', NEW.`reason`, 'exception_reason', NEW.`exception_reason`, 'status', NEW.`status`, 'expires_at', NEW.`expires_at`, 'reviewed_by', NEW.`reviewed_by`, 'reviewed_at', NEW.`reviewed_at`, 'review_remarks', NEW.`review_remarks`, 'released_by', NEW.`released_by`, 'released_at', NEW.`released_at`, 'exit_event_id', NEW.`exit_event_id`, 'created_at', NEW.`created_at`, 'updated_at', NEW.`updated_at`), CURRENT_TIMESTAMP(6)); END IF; END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_unicode_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER `pass_history_vehicle_release_requests_delete` AFTER DELETE ON `vehicle_release_requests` FOR EACH ROW BEGIN INSERT INTO record_history (record_type, record_id, operation, actor_user_id, actor_label, source, ip_address, before_values, after_values, recorded_at)
                    VALUES ('vehicle_release_requests', CONCAT_WS(':', OLD.`release_id`), 'deleted', @pass_history_actor, LEFT(@pass_history_label, 100), LEFT(@pass_history_source, 150), LEFT(@pass_history_ip, 45), JSON_OBJECT('release_id', OLD.`release_id`, 'transaction_id', OLD.`transaction_id`, 'owner_user_id', OLD.`owner_user_id`, 'requested_by', OLD.`requested_by`, 'driver_name', OLD.`driver_name`, 'driver_contact', OLD.`driver_contact`, 'license_encrypted', OLD.`license_encrypted`, 'license_expiry', OLD.`license_expiry`, 'reason', OLD.`reason`, 'exception_reason', OLD.`exception_reason`, 'status', OLD.`status`, 'expires_at', OLD.`expires_at`, 'reviewed_by', OLD.`reviewed_by`, 'reviewed_at', OLD.`reviewed_at`, 'review_remarks', OLD.`review_remarks`, 'released_by', OLD.`released_by`, 'released_at', OLD.`released_at`, 'exit_event_id', OLD.`exit_event_id`, 'created_at', OLD.`created_at`, 'updated_at', OLD.`updated_at`), NULL, CURRENT_TIMESTAMP(6)); END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;

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
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_unicode_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER `pass_history_vehicle_types_insert` AFTER INSERT ON `vehicle_types` FOR EACH ROW BEGIN INSERT INTO record_history (record_type, record_id, operation, actor_user_id, actor_label, source, ip_address, before_values, after_values, recorded_at)
                    VALUES ('vehicle_types', CONCAT_WS(':', NEW.`vehicle_type_id`), 'created', @pass_history_actor, LEFT(@pass_history_label, 100), LEFT(@pass_history_source, 150), LEFT(@pass_history_ip, 45), NULL, JSON_OBJECT('vehicle_type_id', NEW.`vehicle_type_id`, 'type_code', NEW.`type_code`, 'type_name', NEW.`type_name`, 'requires_assigned_slot', NEW.`requires_assigned_slot`, 'parking_area_id', NEW.`parking_area_id`), CURRENT_TIMESTAMP(6)); END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_unicode_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER `pass_history_vehicle_types_update` AFTER UPDATE ON `vehicle_types` FOR EACH ROW BEGIN IF NOT (OLD.`vehicle_type_id` <=> NEW.`vehicle_type_id`) OR NOT (OLD.`type_code` <=> NEW.`type_code`) OR NOT (OLD.`type_name` <=> NEW.`type_name`) OR NOT (OLD.`requires_assigned_slot` <=> NEW.`requires_assigned_slot`) OR NOT (OLD.`parking_area_id` <=> NEW.`parking_area_id`) THEN INSERT INTO record_history (record_type, record_id, operation, actor_user_id, actor_label, source, ip_address, before_values, after_values, recorded_at)
                    VALUES ('vehicle_types', CONCAT_WS(':', NEW.`vehicle_type_id`), 'updated', @pass_history_actor, LEFT(@pass_history_label, 100), LEFT(@pass_history_source, 150), LEFT(@pass_history_ip, 45), JSON_OBJECT('vehicle_type_id', OLD.`vehicle_type_id`, 'type_code', OLD.`type_code`, 'type_name', OLD.`type_name`, 'requires_assigned_slot', OLD.`requires_assigned_slot`, 'parking_area_id', OLD.`parking_area_id`), JSON_OBJECT('vehicle_type_id', NEW.`vehicle_type_id`, 'type_code', NEW.`type_code`, 'type_name', NEW.`type_name`, 'requires_assigned_slot', NEW.`requires_assigned_slot`, 'parking_area_id', NEW.`parking_area_id`), CURRENT_TIMESTAMP(6)); END IF; END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_unicode_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER `pass_history_vehicle_types_delete` AFTER DELETE ON `vehicle_types` FOR EACH ROW BEGIN INSERT INTO record_history (record_type, record_id, operation, actor_user_id, actor_label, source, ip_address, before_values, after_values, recorded_at)
                    VALUES ('vehicle_types', CONCAT_WS(':', OLD.`vehicle_type_id`), 'deleted', @pass_history_actor, LEFT(@pass_history_label, 100), LEFT(@pass_history_source, 150), LEFT(@pass_history_ip, 45), JSON_OBJECT('vehicle_type_id', OLD.`vehicle_type_id`, 'type_code', OLD.`type_code`, 'type_name', OLD.`type_name`, 'requires_assigned_slot', OLD.`requires_assigned_slot`, 'parking_area_id', OLD.`parking_area_id`), NULL, CURRENT_TIMESTAMP(6)); END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;

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
  `vehicle_photo_path` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `plate_key` varchar(20) COLLATE utf8mb4_unicode_ci GENERATED ALWAYS AS (replace(replace(upper(`plate_number`),_utf8mb4'-',_utf8mb4''),_utf8mb4' ',_utf8mb4'')) STORED,
  PRIMARY KEY (`vehicle_id`),
  UNIQUE KEY `vehicles_plate_number_unique` (`plate_number`),
  UNIQUE KEY `vehicles_plate_key_unique` (`plate_key`),
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
INSERT INTO `vehicles` (`vehicle_id`, `owner_user_id`, `owner_visitor_id`, `vehicle_type_id`, `plate_number`, `make`, `model`, `color`, `year_model`, `or_number`, `cr_number`, `chassis_number`, `registration_status`, `created_at`, `updated_at`, `deleted_at`, `vehicle_photo_path`) VALUES (51,32,NULL,1,'SMP 1001','Toyota','Vios','White',2021,'SAMPLE-OR-1001','SAMPLE-CR-1001',NULL,'registered','2026-08-29 16:00:00','2026-08-29 16:00:00',NULL,NULL),(52,33,NULL,6,'SMP 1002','Honda','Click 125i','Red',2023,'SAMPLE-OR-1002','SAMPLE-CR-1002',NULL,'registered','2026-08-29 16:00:00','2026-08-29 16:00:00',NULL,NULL),(53,34,NULL,2,'SMP 1003','Mitsubishi','Montero Sport','Gray',2019,'SAMPLE-OR-1003','SAMPLE-CR-1003',NULL,'registered','2026-08-29 16:00:00','2026-08-29 16:00:00',NULL,NULL),(54,35,NULL,4,'SMP 1004','Ford','Ranger','Blue',2020,'SAMPLE-OR-1004','SAMPLE-CR-1004',NULL,'registered','2026-08-29 16:00:00','2026-08-29 16:00:00',NULL,NULL),(55,36,NULL,5,'SMP 1005','Toyota','Innova','Silver',2022,'SAMPLE-OR-1005','SAMPLE-CR-1005',NULL,'registered','2026-08-29 16:00:00','2026-08-29 16:00:00',NULL,NULL),(76,35,NULL,6,'SMP 2004','Yamaha','Mio Gear','Black',2024,'SAMPLE-OR-2004','SAMPLE-CR-2004',NULL,'registered','2026-08-29 16:00:00','2026-08-29 16:00:00',NULL,NULL),(135,NULL,61,4,'SMP V001','Isuzu','Traviz',NULL,NULL,NULL,NULL,NULL,'registered','2026-10-12 16:00:00','2026-10-12 16:00:00',NULL,NULL),(136,NULL,62,1,'SMP V002','Nissan','Almera',NULL,NULL,NULL,NULL,NULL,'registered','2026-10-12 16:00:00','2026-10-12 16:00:00',NULL,NULL),(137,NULL,63,1,'SMP V003','Hyundai','Accent',NULL,NULL,NULL,NULL,NULL,'registered','2026-10-12 16:00:00','2026-10-12 16:00:00',NULL,NULL),(138,NULL,64,6,'SMP V004','Yamaha','NMAX',NULL,NULL,NULL,NULL,NULL,'registered','2026-10-12 16:00:00','2026-10-12 16:00:00',NULL,NULL),(139,NULL,65,1,'DEMO 4821','Toyota',NULL,NULL,NULL,NULL,NULL,NULL,'registered','2026-10-14 21:17:59','2026-10-14 21:17:59',NULL,NULL),(140,NULL,65,1,'DEMO 4822',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'registered','2026-10-14 21:22:32','2026-10-14 21:22:32',NULL,NULL),(141,NULL,65,1,'DEMO 4823',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'registered','2026-10-14 21:22:41','2026-10-14 21:22:41',NULL,NULL),(142,NULL,65,1,'DEMO 4824',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'registered','2026-10-14 21:25:27','2026-10-14 21:25:27',NULL,NULL),(143,NULL,66,1,'GUEST 001','Toyota','Vios','White',NULL,NULL,NULL,NULL,'registered','2026-10-15 00:15:48','2026-10-15 00:15:48',NULL,NULL),(144,NULL,67,1,'ROSA 2026','Honda','City',NULL,NULL,NULL,NULL,NULL,'registered','2026-10-15 00:24:38','2026-10-15 00:24:38',NULL,NULL),(145,NULL,67,1,'ROSA 2027','Honda','City',NULL,NULL,NULL,NULL,NULL,'registered','2026-10-15 00:24:47','2026-10-15 00:24:47',NULL,NULL),(146,NULL,68,2,'CAR 123','suzuki',NULL,NULL,NULL,NULL,NULL,NULL,'registered','2026-10-15 02:12:58','2026-10-15 02:12:58',NULL,NULL),(147,NULL,69,1,'LARA 3101','Honda','City',NULL,NULL,NULL,NULL,NULL,'registered','2026-10-15 03:33:25','2026-10-15 03:33:25',NULL,NULL);
/*!40000 ALTER TABLE `vehicles` ENABLE KEYS */;
UNLOCK TABLES;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_unicode_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER `pass_history_vehicles_insert` AFTER INSERT ON `vehicles` FOR EACH ROW BEGIN INSERT INTO record_history (record_type, record_id, operation, actor_user_id, actor_label, source, ip_address, before_values, after_values, recorded_at)
                    VALUES ('vehicles', CONCAT_WS(':', NEW.`vehicle_id`), 'created', @pass_history_actor, LEFT(@pass_history_label, 100), LEFT(@pass_history_source, 150), LEFT(@pass_history_ip, 45), NULL, JSON_OBJECT('vehicle_id', NEW.`vehicle_id`, 'owner_user_id', NEW.`owner_user_id`, 'owner_visitor_id', NEW.`owner_visitor_id`, 'vehicle_type_id', NEW.`vehicle_type_id`, 'plate_number', NEW.`plate_number`, 'make', NEW.`make`, 'model', NEW.`model`, 'color', NEW.`color`, 'year_model', NEW.`year_model`, 'or_number', NEW.`or_number`, 'cr_number', NEW.`cr_number`, 'chassis_number', NEW.`chassis_number`, 'registration_status', NEW.`registration_status`, 'created_at', NEW.`created_at`, 'updated_at', NEW.`updated_at`, 'deleted_at', NEW.`deleted_at`, 'vehicle_photo_path', NEW.`vehicle_photo_path`, 'plate_key', NEW.`plate_key`), CURRENT_TIMESTAMP(6)); END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_unicode_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER `pass_history_vehicles_update` AFTER UPDATE ON `vehicles` FOR EACH ROW BEGIN IF NOT (OLD.`vehicle_id` <=> NEW.`vehicle_id`) OR NOT (OLD.`owner_user_id` <=> NEW.`owner_user_id`) OR NOT (OLD.`owner_visitor_id` <=> NEW.`owner_visitor_id`) OR NOT (OLD.`vehicle_type_id` <=> NEW.`vehicle_type_id`) OR NOT (OLD.`plate_number` <=> NEW.`plate_number`) OR NOT (OLD.`make` <=> NEW.`make`) OR NOT (OLD.`model` <=> NEW.`model`) OR NOT (OLD.`color` <=> NEW.`color`) OR NOT (OLD.`year_model` <=> NEW.`year_model`) OR NOT (OLD.`or_number` <=> NEW.`or_number`) OR NOT (OLD.`cr_number` <=> NEW.`cr_number`) OR NOT (OLD.`chassis_number` <=> NEW.`chassis_number`) OR NOT (OLD.`registration_status` <=> NEW.`registration_status`) OR NOT (OLD.`created_at` <=> NEW.`created_at`) OR NOT (OLD.`updated_at` <=> NEW.`updated_at`) OR NOT (OLD.`deleted_at` <=> NEW.`deleted_at`) OR NOT (OLD.`vehicle_photo_path` <=> NEW.`vehicle_photo_path`) OR NOT (OLD.`plate_key` <=> NEW.`plate_key`) THEN INSERT INTO record_history (record_type, record_id, operation, actor_user_id, actor_label, source, ip_address, before_values, after_values, recorded_at)
                    VALUES ('vehicles', CONCAT_WS(':', NEW.`vehicle_id`), CASE WHEN OLD.deleted_at IS NULL AND NEW.deleted_at IS NOT NULL THEN 'archived' WHEN OLD.deleted_at IS NOT NULL AND NEW.deleted_at IS NULL THEN 'restored' ELSE 'updated' END, @pass_history_actor, LEFT(@pass_history_label, 100), LEFT(@pass_history_source, 150), LEFT(@pass_history_ip, 45), JSON_OBJECT('vehicle_id', OLD.`vehicle_id`, 'owner_user_id', OLD.`owner_user_id`, 'owner_visitor_id', OLD.`owner_visitor_id`, 'vehicle_type_id', OLD.`vehicle_type_id`, 'plate_number', OLD.`plate_number`, 'make', OLD.`make`, 'model', OLD.`model`, 'color', OLD.`color`, 'year_model', OLD.`year_model`, 'or_number', OLD.`or_number`, 'cr_number', OLD.`cr_number`, 'chassis_number', OLD.`chassis_number`, 'registration_status', OLD.`registration_status`, 'created_at', OLD.`created_at`, 'updated_at', OLD.`updated_at`, 'deleted_at', OLD.`deleted_at`, 'vehicle_photo_path', OLD.`vehicle_photo_path`, 'plate_key', OLD.`plate_key`), JSON_OBJECT('vehicle_id', NEW.`vehicle_id`, 'owner_user_id', NEW.`owner_user_id`, 'owner_visitor_id', NEW.`owner_visitor_id`, 'vehicle_type_id', NEW.`vehicle_type_id`, 'plate_number', NEW.`plate_number`, 'make', NEW.`make`, 'model', NEW.`model`, 'color', NEW.`color`, 'year_model', NEW.`year_model`, 'or_number', NEW.`or_number`, 'cr_number', NEW.`cr_number`, 'chassis_number', NEW.`chassis_number`, 'registration_status', NEW.`registration_status`, 'created_at', NEW.`created_at`, 'updated_at', NEW.`updated_at`, 'deleted_at', NEW.`deleted_at`, 'vehicle_photo_path', NEW.`vehicle_photo_path`, 'plate_key', NEW.`plate_key`), CURRENT_TIMESTAMP(6)); END IF; END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_unicode_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER `pass_history_vehicles_delete` AFTER DELETE ON `vehicles` FOR EACH ROW BEGIN INSERT INTO record_history (record_type, record_id, operation, actor_user_id, actor_label, source, ip_address, before_values, after_values, recorded_at)
                    VALUES ('vehicles', CONCAT_WS(':', OLD.`vehicle_id`), 'deleted', @pass_history_actor, LEFT(@pass_history_label, 100), LEFT(@pass_history_source, 150), LEFT(@pass_history_ip, 45), JSON_OBJECT('vehicle_id', OLD.`vehicle_id`, 'owner_user_id', OLD.`owner_user_id`, 'owner_visitor_id', OLD.`owner_visitor_id`, 'vehicle_type_id', OLD.`vehicle_type_id`, 'plate_number', OLD.`plate_number`, 'make', OLD.`make`, 'model', OLD.`model`, 'color', OLD.`color`, 'year_model', OLD.`year_model`, 'or_number', OLD.`or_number`, 'cr_number', OLD.`cr_number`, 'chassis_number', OLD.`chassis_number`, 'registration_status', OLD.`registration_status`, 'created_at', OLD.`created_at`, 'updated_at', OLD.`updated_at`, 'deleted_at', OLD.`deleted_at`, 'vehicle_photo_path', OLD.`vehicle_photo_path`, 'plate_key', OLD.`plate_key`), NULL, CURRENT_TIMESTAMP(6)); END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;

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
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_unicode_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER `pass_history_violation_evidences_insert` AFTER INSERT ON `violation_evidences` FOR EACH ROW BEGIN INSERT INTO record_history (record_type, record_id, operation, actor_user_id, actor_label, source, ip_address, before_values, after_values, recorded_at)
                    VALUES ('violation_evidences', CONCAT_WS(':', NEW.`evidence_id`), 'created', @pass_history_actor, LEFT(@pass_history_label, 100), LEFT(@pass_history_source, 150), LEFT(@pass_history_ip, 45), NULL, JSON_OBJECT('evidence_id', NEW.`evidence_id`, 'violation_id', NEW.`violation_id`, 'file_path', NEW.`file_path`, 'file_type', NEW.`file_type`, 'caption', NEW.`caption`, 'uploaded_by', NEW.`uploaded_by`, 'uploaded_at', NEW.`uploaded_at`), CURRENT_TIMESTAMP(6)); END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_unicode_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER `pass_history_violation_evidences_update` AFTER UPDATE ON `violation_evidences` FOR EACH ROW BEGIN IF NOT (OLD.`evidence_id` <=> NEW.`evidence_id`) OR NOT (OLD.`violation_id` <=> NEW.`violation_id`) OR NOT (OLD.`file_path` <=> NEW.`file_path`) OR NOT (OLD.`file_type` <=> NEW.`file_type`) OR NOT (OLD.`caption` <=> NEW.`caption`) OR NOT (OLD.`uploaded_by` <=> NEW.`uploaded_by`) OR NOT (OLD.`uploaded_at` <=> NEW.`uploaded_at`) THEN INSERT INTO record_history (record_type, record_id, operation, actor_user_id, actor_label, source, ip_address, before_values, after_values, recorded_at)
                    VALUES ('violation_evidences', CONCAT_WS(':', NEW.`evidence_id`), 'updated', @pass_history_actor, LEFT(@pass_history_label, 100), LEFT(@pass_history_source, 150), LEFT(@pass_history_ip, 45), JSON_OBJECT('evidence_id', OLD.`evidence_id`, 'violation_id', OLD.`violation_id`, 'file_path', OLD.`file_path`, 'file_type', OLD.`file_type`, 'caption', OLD.`caption`, 'uploaded_by', OLD.`uploaded_by`, 'uploaded_at', OLD.`uploaded_at`), JSON_OBJECT('evidence_id', NEW.`evidence_id`, 'violation_id', NEW.`violation_id`, 'file_path', NEW.`file_path`, 'file_type', NEW.`file_type`, 'caption', NEW.`caption`, 'uploaded_by', NEW.`uploaded_by`, 'uploaded_at', NEW.`uploaded_at`), CURRENT_TIMESTAMP(6)); END IF; END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_unicode_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER `pass_history_violation_evidences_delete` AFTER DELETE ON `violation_evidences` FOR EACH ROW BEGIN INSERT INTO record_history (record_type, record_id, operation, actor_user_id, actor_label, source, ip_address, before_values, after_values, recorded_at)
                    VALUES ('violation_evidences', CONCAT_WS(':', OLD.`evidence_id`), 'deleted', @pass_history_actor, LEFT(@pass_history_label, 100), LEFT(@pass_history_source, 150), LEFT(@pass_history_ip, 45), JSON_OBJECT('evidence_id', OLD.`evidence_id`, 'violation_id', OLD.`violation_id`, 'file_path', OLD.`file_path`, 'file_type', OLD.`file_type`, 'caption', OLD.`caption`, 'uploaded_by', OLD.`uploaded_by`, 'uploaded_at', OLD.`uploaded_at`), NULL, CURRENT_TIMESTAMP(6)); END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;

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
  `driver_is_requester` tinyint(1) DEFAULT NULL,
  `driver_name` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `driver_license_encrypted` text COLLATE utf8mb4_unicode_ci,
  `driver_license_expiry` date DEFAULT NULL,
  `passenger_count` tinyint unsigned DEFAULT NULL,
  `driver_license_photo_path` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `vehicle_photo_path` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `documents_reviewed_by` bigint unsigned DEFAULT NULL,
  `documents_reviewed_at` timestamp NULL DEFAULT NULL,
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
INSERT INTO `visitor_requests` VALUES (1,65,NULL,139,'Campus tour · Visiting: Admissions Office',6,'2026-10-15 05:17:00','2026-10-15 09:17:00','approved',1,'2026-10-15 05:20:24',NULL,'2026-10-14 21:17:59','2026-10-14 21:20:24',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(2,65,NULL,140,'Delivery · Visiting: Accounting',6,'2026-10-15 05:22:00','2026-10-15 09:22:00','rejected',1,'2026-10-15 05:22:36','No appointment on record','2026-10-14 21:22:32','2026-10-14 21:22:36',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(3,65,NULL,141,'Meeting · Visiting: Dean',6,'2026-10-15 05:22:00','2026-10-15 09:22:00','approved',1,'2026-10-15 05:22:45',NULL,'2026-10-14 21:22:41','2026-10-14 21:22:45',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(4,65,NULL,142,'Interview · Visiting: HR Office',6,'2026-10-15 05:25:00','2026-10-15 09:25:00','approved',1,'2026-10-15 05:25:31',NULL,'2026-10-14 21:25:27','2026-10-14 21:25:31',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(5,66,NULL,143,'Campus visit (demo request) · Visiting: Admissions Office',6,'2026-10-15 08:00:00','2026-10-15 16:00:00','pending',NULL,NULL,NULL,'2026-10-15 00:15:48','2026-10-15 00:15:48',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(6,67,NULL,144,'Document pickup at the Registrar · Visiting: Registrar Office',6,'2026-10-15 08:14:00','2026-10-15 11:24:00','approved',46,'2026-10-15 08:27:02',NULL,'2026-10-15 00:24:58','2026-10-15 00:27:02',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(7,67,NULL,145,'Second visit for rejection test · Visiting: Registrar Office',6,'2026-10-15 08:15:00','2026-10-15 11:25:00','rejected',46,'2026-10-15 08:27:06','The Registrar is closed for a holiday','2026-10-15 00:25:10','2026-10-15 00:27:06',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(8,68,NULL,146,'guest speaker · Visiting: convention',6,'2026-10-29 00:30:00','2026-10-29 02:20:00','pending',NULL,NULL,NULL,'2026-10-15 02:15:56','2026-10-15 02:15:56',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(9,69,NULL,147,'Thesis consultation · Visiting: Registrar Office',6,'2026-10-15 11:23:00','2026-10-15 14:33:00','approved',46,'2026-10-15 11:33:51',NULL,'2026-10-15 03:33:30','2026-10-15 03:33:51',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL);
/*!40000 ALTER TABLE `visitor_requests` ENABLE KEYS */;
UNLOCK TABLES;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_unicode_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER `pass_history_visitor_requests_insert` AFTER INSERT ON `visitor_requests` FOR EACH ROW BEGIN INSERT INTO record_history (record_type, record_id, operation, actor_user_id, actor_label, source, ip_address, before_values, after_values, recorded_at)
                    VALUES ('visitor_requests', CONCAT_WS(':', NEW.`request_id`), 'created', @pass_history_actor, LEFT(@pass_history_label, 100), LEFT(@pass_history_source, 150), LEFT(@pass_history_ip, 45), NULL, JSON_OBJECT('request_id', NEW.`request_id`, 'visitor_id', NEW.`visitor_id`, 'host_user_id', NEW.`host_user_id`, 'vehicle_id', NEW.`vehicle_id`, 'purpose', NEW.`purpose`, 'destination_area_id', NEW.`destination_area_id`, 'requested_valid_from', NEW.`requested_valid_from`, 'requested_valid_until', NEW.`requested_valid_until`, 'request_status', NEW.`request_status`, 'reviewed_by', NEW.`reviewed_by`, 'reviewed_at', NEW.`reviewed_at`, 'review_remarks', NEW.`review_remarks`, 'created_at', NEW.`created_at`, 'updated_at', NEW.`updated_at`, 'driver_is_requester', NEW.`driver_is_requester`, 'driver_name', NEW.`driver_name`, 'driver_license_encrypted', NEW.`driver_license_encrypted`, 'driver_license_expiry', NEW.`driver_license_expiry`, 'passenger_count', NEW.`passenger_count`, 'driver_license_photo_path', NEW.`driver_license_photo_path`, 'vehicle_photo_path', NEW.`vehicle_photo_path`, 'documents_reviewed_by', NEW.`documents_reviewed_by`, 'documents_reviewed_at', NEW.`documents_reviewed_at`), CURRENT_TIMESTAMP(6)); END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_unicode_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER `pass_history_visitor_requests_update` AFTER UPDATE ON `visitor_requests` FOR EACH ROW BEGIN IF NOT (OLD.`request_id` <=> NEW.`request_id`) OR NOT (OLD.`visitor_id` <=> NEW.`visitor_id`) OR NOT (OLD.`host_user_id` <=> NEW.`host_user_id`) OR NOT (OLD.`vehicle_id` <=> NEW.`vehicle_id`) OR NOT (OLD.`purpose` <=> NEW.`purpose`) OR NOT (OLD.`destination_area_id` <=> NEW.`destination_area_id`) OR NOT (OLD.`requested_valid_from` <=> NEW.`requested_valid_from`) OR NOT (OLD.`requested_valid_until` <=> NEW.`requested_valid_until`) OR NOT (OLD.`request_status` <=> NEW.`request_status`) OR NOT (OLD.`reviewed_by` <=> NEW.`reviewed_by`) OR NOT (OLD.`reviewed_at` <=> NEW.`reviewed_at`) OR NOT (OLD.`review_remarks` <=> NEW.`review_remarks`) OR NOT (OLD.`created_at` <=> NEW.`created_at`) OR NOT (OLD.`updated_at` <=> NEW.`updated_at`) OR NOT (OLD.`driver_is_requester` <=> NEW.`driver_is_requester`) OR NOT (OLD.`driver_name` <=> NEW.`driver_name`) OR NOT (OLD.`driver_license_encrypted` <=> NEW.`driver_license_encrypted`) OR NOT (OLD.`driver_license_expiry` <=> NEW.`driver_license_expiry`) OR NOT (OLD.`passenger_count` <=> NEW.`passenger_count`) OR NOT (OLD.`driver_license_photo_path` <=> NEW.`driver_license_photo_path`) OR NOT (OLD.`vehicle_photo_path` <=> NEW.`vehicle_photo_path`) OR NOT (OLD.`documents_reviewed_by` <=> NEW.`documents_reviewed_by`) OR NOT (OLD.`documents_reviewed_at` <=> NEW.`documents_reviewed_at`) THEN INSERT INTO record_history (record_type, record_id, operation, actor_user_id, actor_label, source, ip_address, before_values, after_values, recorded_at)
                    VALUES ('visitor_requests', CONCAT_WS(':', NEW.`request_id`), 'updated', @pass_history_actor, LEFT(@pass_history_label, 100), LEFT(@pass_history_source, 150), LEFT(@pass_history_ip, 45), JSON_OBJECT('request_id', OLD.`request_id`, 'visitor_id', OLD.`visitor_id`, 'host_user_id', OLD.`host_user_id`, 'vehicle_id', OLD.`vehicle_id`, 'purpose', OLD.`purpose`, 'destination_area_id', OLD.`destination_area_id`, 'requested_valid_from', OLD.`requested_valid_from`, 'requested_valid_until', OLD.`requested_valid_until`, 'request_status', OLD.`request_status`, 'reviewed_by', OLD.`reviewed_by`, 'reviewed_at', OLD.`reviewed_at`, 'review_remarks', OLD.`review_remarks`, 'created_at', OLD.`created_at`, 'updated_at', OLD.`updated_at`, 'driver_is_requester', OLD.`driver_is_requester`, 'driver_name', OLD.`driver_name`, 'driver_license_encrypted', OLD.`driver_license_encrypted`, 'driver_license_expiry', OLD.`driver_license_expiry`, 'passenger_count', OLD.`passenger_count`, 'driver_license_photo_path', OLD.`driver_license_photo_path`, 'vehicle_photo_path', OLD.`vehicle_photo_path`, 'documents_reviewed_by', OLD.`documents_reviewed_by`, 'documents_reviewed_at', OLD.`documents_reviewed_at`), JSON_OBJECT('request_id', NEW.`request_id`, 'visitor_id', NEW.`visitor_id`, 'host_user_id', NEW.`host_user_id`, 'vehicle_id', NEW.`vehicle_id`, 'purpose', NEW.`purpose`, 'destination_area_id', NEW.`destination_area_id`, 'requested_valid_from', NEW.`requested_valid_from`, 'requested_valid_until', NEW.`requested_valid_until`, 'request_status', NEW.`request_status`, 'reviewed_by', NEW.`reviewed_by`, 'reviewed_at', NEW.`reviewed_at`, 'review_remarks', NEW.`review_remarks`, 'created_at', NEW.`created_at`, 'updated_at', NEW.`updated_at`, 'driver_is_requester', NEW.`driver_is_requester`, 'driver_name', NEW.`driver_name`, 'driver_license_encrypted', NEW.`driver_license_encrypted`, 'driver_license_expiry', NEW.`driver_license_expiry`, 'passenger_count', NEW.`passenger_count`, 'driver_license_photo_path', NEW.`driver_license_photo_path`, 'vehicle_photo_path', NEW.`vehicle_photo_path`, 'documents_reviewed_by', NEW.`documents_reviewed_by`, 'documents_reviewed_at', NEW.`documents_reviewed_at`), CURRENT_TIMESTAMP(6)); END IF; END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_unicode_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER `pass_history_visitor_requests_delete` AFTER DELETE ON `visitor_requests` FOR EACH ROW BEGIN INSERT INTO record_history (record_type, record_id, operation, actor_user_id, actor_label, source, ip_address, before_values, after_values, recorded_at)
                    VALUES ('visitor_requests', CONCAT_WS(':', OLD.`request_id`), 'deleted', @pass_history_actor, LEFT(@pass_history_label, 100), LEFT(@pass_history_source, 150), LEFT(@pass_history_ip, 45), JSON_OBJECT('request_id', OLD.`request_id`, 'visitor_id', OLD.`visitor_id`, 'host_user_id', OLD.`host_user_id`, 'vehicle_id', OLD.`vehicle_id`, 'purpose', OLD.`purpose`, 'destination_area_id', OLD.`destination_area_id`, 'requested_valid_from', OLD.`requested_valid_from`, 'requested_valid_until', OLD.`requested_valid_until`, 'request_status', OLD.`request_status`, 'reviewed_by', OLD.`reviewed_by`, 'reviewed_at', OLD.`reviewed_at`, 'review_remarks', OLD.`review_remarks`, 'created_at', OLD.`created_at`, 'updated_at', OLD.`updated_at`, 'driver_is_requester', OLD.`driver_is_requester`, 'driver_name', OLD.`driver_name`, 'driver_license_encrypted', OLD.`driver_license_encrypted`, 'driver_license_expiry', OLD.`driver_license_expiry`, 'passenger_count', OLD.`passenger_count`, 'driver_license_photo_path', OLD.`driver_license_photo_path`, 'vehicle_photo_path', OLD.`vehicle_photo_path`, 'documents_reviewed_by', OLD.`documents_reviewed_by`, 'documents_reviewed_at', OLD.`documents_reviewed_at`), NULL, CURRENT_TIMESTAMP(6)); END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;

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
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_unicode_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER `pass_history_visitors_insert` AFTER INSERT ON `visitors` FOR EACH ROW BEGIN INSERT INTO record_history (record_type, record_id, operation, actor_user_id, actor_label, source, ip_address, before_values, after_values, recorded_at)
                    VALUES ('visitors', CONCAT_WS(':', NEW.`visitor_id`), 'created', @pass_history_actor, LEFT(@pass_history_label, 100), LEFT(@pass_history_source, 150), LEFT(@pass_history_ip, 45), NULL, JSON_OBJECT('visitor_id', NEW.`visitor_id`, 'user_id', NEW.`user_id`, 'first_name', NEW.`first_name`, 'middle_name', NEW.`middle_name`, 'last_name', NEW.`last_name`, 'contact_number', NEW.`contact_number`, 'email', NEW.`email`, 'id_type', NEW.`id_type`, 'id_number', NEW.`id_number`, 'affiliation', NEW.`affiliation`, 'is_blacklisted', NEW.`is_blacklisted`, 'blacklist_reason', NEW.`blacklist_reason`, 'created_at', NEW.`created_at`, 'updated_at', NEW.`updated_at`), CURRENT_TIMESTAMP(6)); END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_unicode_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER `pass_history_visitors_update` AFTER UPDATE ON `visitors` FOR EACH ROW BEGIN IF NOT (OLD.`visitor_id` <=> NEW.`visitor_id`) OR NOT (OLD.`user_id` <=> NEW.`user_id`) OR NOT (OLD.`first_name` <=> NEW.`first_name`) OR NOT (OLD.`middle_name` <=> NEW.`middle_name`) OR NOT (OLD.`last_name` <=> NEW.`last_name`) OR NOT (OLD.`contact_number` <=> NEW.`contact_number`) OR NOT (OLD.`email` <=> NEW.`email`) OR NOT (OLD.`id_type` <=> NEW.`id_type`) OR NOT (OLD.`id_number` <=> NEW.`id_number`) OR NOT (OLD.`affiliation` <=> NEW.`affiliation`) OR NOT (OLD.`is_blacklisted` <=> NEW.`is_blacklisted`) OR NOT (OLD.`blacklist_reason` <=> NEW.`blacklist_reason`) OR NOT (OLD.`created_at` <=> NEW.`created_at`) OR NOT (OLD.`updated_at` <=> NEW.`updated_at`) THEN INSERT INTO record_history (record_type, record_id, operation, actor_user_id, actor_label, source, ip_address, before_values, after_values, recorded_at)
                    VALUES ('visitors', CONCAT_WS(':', NEW.`visitor_id`), 'updated', @pass_history_actor, LEFT(@pass_history_label, 100), LEFT(@pass_history_source, 150), LEFT(@pass_history_ip, 45), JSON_OBJECT('visitor_id', OLD.`visitor_id`, 'user_id', OLD.`user_id`, 'first_name', OLD.`first_name`, 'middle_name', OLD.`middle_name`, 'last_name', OLD.`last_name`, 'contact_number', OLD.`contact_number`, 'email', OLD.`email`, 'id_type', OLD.`id_type`, 'id_number', OLD.`id_number`, 'affiliation', OLD.`affiliation`, 'is_blacklisted', OLD.`is_blacklisted`, 'blacklist_reason', OLD.`blacklist_reason`, 'created_at', OLD.`created_at`, 'updated_at', OLD.`updated_at`), JSON_OBJECT('visitor_id', NEW.`visitor_id`, 'user_id', NEW.`user_id`, 'first_name', NEW.`first_name`, 'middle_name', NEW.`middle_name`, 'last_name', NEW.`last_name`, 'contact_number', NEW.`contact_number`, 'email', NEW.`email`, 'id_type', NEW.`id_type`, 'id_number', NEW.`id_number`, 'affiliation', NEW.`affiliation`, 'is_blacklisted', NEW.`is_blacklisted`, 'blacklist_reason', NEW.`blacklist_reason`, 'created_at', NEW.`created_at`, 'updated_at', NEW.`updated_at`), CURRENT_TIMESTAMP(6)); END IF; END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_unicode_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER `pass_history_visitors_delete` AFTER DELETE ON `visitors` FOR EACH ROW BEGIN INSERT INTO record_history (record_type, record_id, operation, actor_user_id, actor_label, source, ip_address, before_values, after_values, recorded_at)
                    VALUES ('visitors', CONCAT_WS(':', OLD.`visitor_id`), 'deleted', @pass_history_actor, LEFT(@pass_history_label, 100), LEFT(@pass_history_source, 150), LEFT(@pass_history_ip, 45), JSON_OBJECT('visitor_id', OLD.`visitor_id`, 'user_id', OLD.`user_id`, 'first_name', OLD.`first_name`, 'middle_name', OLD.`middle_name`, 'last_name', OLD.`last_name`, 'contact_number', OLD.`contact_number`, 'email', OLD.`email`, 'id_type', OLD.`id_type`, 'id_number', OLD.`id_number`, 'affiliation', OLD.`affiliation`, 'is_blacklisted', OLD.`is_blacklisted`, 'blacklist_reason', OLD.`blacklist_reason`, 'created_at', OLD.`created_at`, 'updated_at', OLD.`updated_at`), NULL, CURRENT_TIMESTAMP(6)); END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;

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

-- Dump completed on 2026-10-27  0:16:10
