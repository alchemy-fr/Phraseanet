-- MySQL dump 10.14  Distrib 5.3.5-MariaDB-ga, for apple-darwin11.3.0 (i386)
--
-- Host: localhost    Database: ab_test
-- ------------------------------------------------------
-- Server version	5.3.5-MariaDB-ga

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Current Database: `ab_test`
--

CREATE DATABASE /*!32312 IF NOT EXISTS*/ `ab_test` /*!40100 DEFAULT CHARACTER SET latin1 */;

USE `ab_test`;

--
-- Table structure for table `BasketElements`
--

DROP TABLE IF EXISTS `BasketElements`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `BasketElements` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `basket_id` int(11) DEFAULT NULL,
  `record_id` int(11) NOT NULL,
  `sbas_id` int(11) NOT NULL,
  `ord` int(11) NOT NULL,
  `created` datetime NOT NULL,
  `updated` datetime NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_recordcle` (`basket_id`,`sbas_id`,`record_id`),
  KEY `IDX_C0B7ECB71BE1FB52` (`basket_id`),
  CONSTRAINT `FK_C0B7ECB71BE1FB52` FOREIGN KEY (`basket_id`) REFERENCES `baskets` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `BasketElements`
--

LOCK TABLES `BasketElements` WRITE;
/*!40000 ALTER TABLE `BasketElements` DISABLE KEYS */;
/*!40000 ALTER TABLE `BasketElements` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `Baskets`
--

DROP TABLE IF EXISTS `Baskets`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `Baskets` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(128) NOT NULL,
  `description` longtext,
  `usr_id` int(11) NOT NULL,
  `is_read` tinyint(1) NOT NULL,
  `pusher_id` int(11) DEFAULT NULL,
  `archived` tinyint(1) NOT NULL,
  `created` datetime NOT NULL,
  `updated` datetime NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `Baskets`
--

LOCK TABLES `Baskets` WRITE;
/*!40000 ALTER TABLE `Baskets` DISABLE KEYS */;
/*!40000 ALTER TABLE `Baskets` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `StoryWZ`
--

DROP TABLE IF EXISTS `StoryWZ`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `StoryWZ` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `sbas_id` int(11) NOT NULL,
  `record_id` int(11) NOT NULL,
  `usr_id` int(11) NOT NULL,
  `created` datetime NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_story` (`usr_id`,`sbas_id`,`record_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `StoryWZ`
--

LOCK TABLES `StoryWZ` WRITE;
/*!40000 ALTER TABLE `StoryWZ` DISABLE KEYS */;
/*!40000 ALTER TABLE `StoryWZ` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `UsrListOwners`
--

DROP TABLE IF EXISTS `UsrListOwners`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `UsrListOwners` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `list_id` int(11) DEFAULT NULL,
  `usr_id` int(11) NOT NULL,
  `role` varchar(255) NOT NULL,
  `created` datetime NOT NULL,
  `updated` datetime NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_owner` (`usr_id`,`id`),
  KEY `IDX_54E9FE233DAE168B` (`list_id`),
  CONSTRAINT `FK_54E9FE233DAE168B` FOREIGN KEY (`list_id`) REFERENCES `usrlists` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `UsrListOwners`
--

LOCK TABLES `UsrListOwners` WRITE;
/*!40000 ALTER TABLE `UsrListOwners` DISABLE KEYS */;
/*!40000 ALTER TABLE `UsrListOwners` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `UsrLists`
--

DROP TABLE IF EXISTS `UsrLists`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `UsrLists` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `created` datetime NOT NULL,
  `updated` datetime NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `UsrLists`
--

LOCK TABLES `UsrLists` WRITE;
/*!40000 ALTER TABLE `UsrLists` DISABLE KEYS */;
/*!40000 ALTER TABLE `UsrLists` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `UsrListsContent`
--

DROP TABLE IF EXISTS `UsrListsContent`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `UsrListsContent` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `list_id` int(11) DEFAULT NULL,
  `usr_id` int(11) NOT NULL,
  `created` datetime NOT NULL,
  `updated` datetime NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_usr_per_list` (`usr_id`,`list_id`),
  KEY `IDX_661B8B93DAE168B` (`list_id`),
  CONSTRAINT `FK_661B8B93DAE168B` FOREIGN KEY (`list_id`) REFERENCES `usrlists` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `UsrListsContent`
--

LOCK TABLES `UsrListsContent` WRITE;
/*!40000 ALTER TABLE `UsrListsContent` DISABLE KEYS */;
/*!40000 ALTER TABLE `UsrListsContent` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ValidationDatas`
--

DROP TABLE IF EXISTS `ValidationDatas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ValidationDatas` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `participant_id` int(11) DEFAULT NULL,
  `basket_element_id` int(11) DEFAULT NULL,
  `agreement` tinyint(1) DEFAULT NULL,
  `note` longtext,
  `updated` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_70E84DDC9D1C3019` (`participant_id`),
  KEY `IDX_70E84DDCE989605` (`basket_element_id`),
  CONSTRAINT `FK_70E84DDCE989605` FOREIGN KEY (`basket_element_id`) REFERENCES `basketelements` (`id`),
  CONSTRAINT `FK_70E84DDC9D1C3019` FOREIGN KEY (`participant_id`) REFERENCES `validationparticipants` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ValidationDatas`
--

LOCK TABLES `ValidationDatas` WRITE;
/*!40000 ALTER TABLE `ValidationDatas` DISABLE KEYS */;
/*!40000 ALTER TABLE `ValidationDatas` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ValidationParticipants`
--

DROP TABLE IF EXISTS `ValidationParticipants`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ValidationParticipants` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `usr_id` int(11) NOT NULL,
  `is_aware` tinyint(1) NOT NULL,
  `is_confirmed` tinyint(1) NOT NULL,
  `can_agree` tinyint(1) NOT NULL,
  `can_see_others` tinyint(1) NOT NULL,
  `reminded` datetime DEFAULT NULL,
  `ValidationSession_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_17850D7BF25B0F5B` (`ValidationSession_id`),
  CONSTRAINT `FK_17850D7BF25B0F5B` FOREIGN KEY (`ValidationSession_id`) REFERENCES `validationsessions` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ValidationParticipants`
--

LOCK TABLES `ValidationParticipants` WRITE;
/*!40000 ALTER TABLE `ValidationParticipants` DISABLE KEYS */;
/*!40000 ALTER TABLE `ValidationParticipants` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ValidationSessions`
--

DROP TABLE IF EXISTS `ValidationSessions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ValidationSessions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `basket_id` int(11) DEFAULT NULL,
  `initiator_id` int(11) NOT NULL,
  `created` datetime NOT NULL,
  `updated` datetime NOT NULL,
  `expires` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `UNIQ_5B9DFB061BE1FB52` (`basket_id`),
  CONSTRAINT `FK_5B9DFB061BE1FB52` FOREIGN KEY (`basket_id`) REFERENCES `baskets` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ValidationSessions`
--

LOCK TABLES `ValidationSessions` WRITE;
/*!40000 ALTER TABLE `ValidationSessions` DISABLE KEYS */;
/*!40000 ALTER TABLE `ValidationSessions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `api_accounts`
--

DROP TABLE IF EXISTS `api_accounts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `api_accounts` (
  `api_account_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `usr_id` int(11) unsigned NOT NULL,
  `revoked` int(1) NOT NULL,
  `api_version` char(16) COLLATE utf8_unicode_ci NOT NULL,
  `application_id` int(11) NOT NULL,
  `created` datetime NOT NULL,
  PRIMARY KEY (`api_account_id`),
  KEY `usr_id` (`usr_id`),
  KEY `application_id` (`application_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `api_accounts`
--

LOCK TABLES `api_accounts` WRITE;
/*!40000 ALTER TABLE `api_accounts` DISABLE KEYS */;
/*!40000 ALTER TABLE `api_accounts` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `api_applications`
--

DROP TABLE IF EXISTS `api_applications`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `api_applications` (
  `application_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `creator` int(11) unsigned DEFAULT NULL,
  `type` enum('web','desktop') COLLATE utf8_unicode_ci NOT NULL,
  `name` varchar(64) COLLATE utf8_unicode_ci NOT NULL,
  `description` text COLLATE utf8_unicode_ci NOT NULL,
  `website` varchar(120) COLLATE utf8_unicode_ci NOT NULL,
  `created_on` datetime NOT NULL,
  `last_modified` datetime NOT NULL,
  `client_id` char(128) COLLATE utf8_unicode_ci NOT NULL,
  `client_secret` char(128) COLLATE utf8_unicode_ci NOT NULL,
  `nonce` char(64) COLLATE utf8_unicode_ci NOT NULL,
  `redirect_uri` varchar(128) COLLATE utf8_unicode_ci NOT NULL,
  `activated` int(1) NOT NULL,
  `grant_password` int(1) NOT NULL,
  PRIMARY KEY (`application_id`),
  UNIQUE KEY `client_id` (`client_id`),
  KEY `creator` (`creator`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `api_applications`
--

LOCK TABLES `api_applications` WRITE;
/*!40000 ALTER TABLE `api_applications` DISABLE KEYS */;
INSERT INTO `api_applications` VALUES (1,NULL,'desktop','phraseanet-navigator','','http://www.phraseanet.com','2012-04-27 02:06:13','2012-04-27 02:06:13','\\alchemy\\phraseanet\\id\\4f981093aebb66.06844599','\\alchemy\\phraseanet\\secret\\4f9810d4b09799.51622662','jAbrFiUYhRnt5hh0','urn:ietf:wg:oauth:2.0:oob',1,1);
/*!40000 ALTER TABLE `api_applications` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `api_logs`
--

DROP TABLE IF EXISTS `api_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `api_logs` (
  `api_log_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `api_account_id` int(11) unsigned DEFAULT NULL,
  `api_log_route` varchar(256) COLLATE utf8_unicode_ci DEFAULT NULL,
  `api_log_date` datetime DEFAULT NULL,
  `api_log_status_code` int(11) unsigned DEFAULT NULL,
  `api_log_format` varchar(64) COLLATE utf8_unicode_ci DEFAULT NULL,
  `api_log_ressource` varchar(64) COLLATE utf8_unicode_ci DEFAULT NULL,
  `api_log_general` varchar(64) COLLATE utf8_unicode_ci DEFAULT NULL,
  `api_log_aspect` varchar(64) COLLATE utf8_unicode_ci DEFAULT NULL,
  `api_log_action` varchar(64) COLLATE utf8_unicode_ci DEFAULT NULL,
  `api_log_error_code` int(11) unsigned DEFAULT NULL,
  `api_log_error_message` varchar(256) COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`api_log_id`),
  KEY `api_account_id` (`api_account_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `api_logs`
--

LOCK TABLES `api_logs` WRITE;
/*!40000 ALTER TABLE `api_logs` DISABLE KEYS */;
/*!40000 ALTER TABLE `api_logs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `api_oauth_codes`
--

DROP TABLE IF EXISTS `api_oauth_codes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `api_oauth_codes` (
  `code` char(128) COLLATE utf8_unicode_ci NOT NULL,
  `api_account_id` int(11) unsigned NOT NULL,
  `redirect_uri` varchar(256) COLLATE utf8_unicode_ci NOT NULL,
  `expires` datetime DEFAULT NULL,
  `scope` varchar(200) COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`code`),
  KEY `api_account_id` (`api_account_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `api_oauth_codes`
--

LOCK TABLES `api_oauth_codes` WRITE;
/*!40000 ALTER TABLE `api_oauth_codes` DISABLE KEYS */;
/*!40000 ALTER TABLE `api_oauth_codes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `api_oauth_refresh_tokens`
--

DROP TABLE IF EXISTS `api_oauth_refresh_tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `api_oauth_refresh_tokens` (
  `refresh_token` char(128) COLLATE utf8_unicode_ci NOT NULL,
  `api_account_id` int(11) NOT NULL,
  `expires` datetime NOT NULL,
  `scope` varchar(200) COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`refresh_token`),
  KEY `api_account_id` (`api_account_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `api_oauth_refresh_tokens`
--

LOCK TABLES `api_oauth_refresh_tokens` WRITE;
/*!40000 ALTER TABLE `api_oauth_refresh_tokens` DISABLE KEYS */;
/*!40000 ALTER TABLE `api_oauth_refresh_tokens` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `api_oauth_tokens`
--

DROP TABLE IF EXISTS `api_oauth_tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `api_oauth_tokens` (
  `oauth_token` char(128) COLLATE utf8_unicode_ci NOT NULL,
  `session_id` int(6) DEFAULT NULL,
  `api_account_id` int(11) NOT NULL,
  `expires` datetime DEFAULT NULL,
  `scope` varchar(200) COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`oauth_token`),
  KEY `api_account_id` (`api_account_id`),
  KEY `session_id` (`session_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `api_oauth_tokens`
--

LOCK TABLES `api_oauth_tokens` WRITE;
/*!40000 ALTER TABLE `api_oauth_tokens` DISABLE KEYS */;
/*!40000 ALTER TABLE `api_oauth_tokens` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `badlog`
--

DROP TABLE IF EXISTS `badlog`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `badlog` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `date` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `login` varchar(64) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL,
  `pwd` varchar(128) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL,
  `ip` varchar(64) COLLATE utf8_unicode_ci NOT NULL,
  `info1` mediumtext COLLATE utf8_unicode_ci NOT NULL,
  `info2` mediumtext COLLATE utf8_unicode_ci NOT NULL,
  `locked` tinyint(1) unsigned NOT NULL DEFAULT '0',
  UNIQUE KEY `id` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `badlog`
--

LOCK TABLES `badlog` WRITE;
/*!40000 ALTER TABLE `badlog` DISABLE KEYS */;
/*!40000 ALTER TABLE `badlog` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `bas`
--

DROP TABLE IF EXISTS `bas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `bas` (
  `base_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `ord` int(11) unsigned NOT NULL DEFAULT '0',
  `active` tinyint(4) unsigned NOT NULL DEFAULT '0',
  `server_coll_id` bigint(20) NOT NULL DEFAULT '0',
  `aliases` text COLLATE utf8_unicode_ci NOT NULL,
  `sbas_id` int(11) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`base_id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `bas`
--

LOCK TABLES `bas` WRITE;
/*!40000 ALTER TABLE `bas` DISABLE KEYS */;
INSERT INTO `bas` VALUES (1,0,1,1,'',1),(2,0,1,2,'',1);
/*!40000 ALTER TABLE `bas` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `basusr`
--

DROP TABLE IF EXISTS `basusr`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `basusr` (
  `id` bigint(11) unsigned NOT NULL AUTO_INCREMENT,
  `base_id` int(11) unsigned NOT NULL DEFAULT '0',
  `usr_id` int(11) unsigned NOT NULL DEFAULT '0',
  `canputinalbum` int(1) unsigned NOT NULL DEFAULT '0',
  `candwnldhd` int(1) unsigned NOT NULL DEFAULT '0',
  `candwnldsubdef` int(1) unsigned NOT NULL DEFAULT '0',
  `candwnldpreview` int(1) unsigned NOT NULL DEFAULT '0',
  `cancmd` int(1) unsigned NOT NULL DEFAULT '0',
  `canadmin` int(1) unsigned NOT NULL DEFAULT '0',
  `actif` int(1) unsigned NOT NULL DEFAULT '0',
  `canreport` int(1) unsigned NOT NULL DEFAULT '0',
  `canpush` int(1) unsigned NOT NULL DEFAULT '0',
  `creationdate` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `basusr_infousr` text COLLATE utf8_unicode_ci NOT NULL,
  `mask_and` bigint(20) unsigned NOT NULL DEFAULT '0',
  `mask_xor` bigint(20) unsigned NOT NULL DEFAULT '0',
  `restrict_dwnld` int(1) unsigned NOT NULL DEFAULT '0',
  `month_dwnld_max` int(10) unsigned NOT NULL DEFAULT '0',
  `remain_dwnld` int(10) unsigned NOT NULL DEFAULT '0',
  `time_limited` int(1) unsigned NOT NULL DEFAULT '0',
  `limited_from` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `limited_to` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `canaddrecord` int(1) unsigned NOT NULL DEFAULT '0',
  `order_master` int(1) unsigned NOT NULL DEFAULT '0',
  `canmodifrecord` int(1) unsigned NOT NULL DEFAULT '0',
  `candeleterecord` int(1) unsigned NOT NULL DEFAULT '0',
  `chgstatus` int(1) unsigned NOT NULL DEFAULT '0',
  `lastconn` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `imgtools` int(1) unsigned NOT NULL DEFAULT '0',
  `manage` int(1) unsigned NOT NULL DEFAULT '0',
  `modify_struct` int(1) unsigned NOT NULL DEFAULT '0',
  `bas_modify_struct` int(1) unsigned NOT NULL DEFAULT '0',
  `nowatermark` int(1) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `unik` (`base_id`,`usr_id`),
  KEY `basid` (`base_id`),
  KEY `usrid` (`usr_id`),
  KEY `canadmin` (`canadmin`),
  KEY `actif` (`actif`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `basusr`
--

LOCK TABLES `basusr` WRITE;
/*!40000 ALTER TABLE `basusr` DISABLE KEYS */;
INSERT INTO `basusr` VALUES (1,1,3,1,1,0,1,1,1,1,1,1,'0000-00-00 00:00:00','0',0,0,0,0,0,0,'0000-00-00 00:00:00','0000-00-00 00:00:00',1,0,1,1,1,'2012-04-27 02:06:55',1,1,1,0,1),(2,2,3,1,1,0,1,1,1,1,1,1,'0000-00-00 00:00:00','0',0,0,0,0,0,0,'0000-00-00 00:00:00','0000-00-00 00:00:00',1,0,1,1,1,'2012-04-27 02:06:55',1,1,1,0,1);
/*!40000 ALTER TABLE `basusr` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `bridge_account_settings`
--

DROP TABLE IF EXISTS `bridge_account_settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `bridge_account_settings` (
  `account_id` int(11) unsigned NOT NULL,
  `key` varchar(120) COLLATE utf8_unicode_ci NOT NULL,
  `value` varchar(500) COLLATE utf8_unicode_ci DEFAULT NULL,
  `updated_on` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`account_id`,`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `bridge_account_settings`
--

LOCK TABLES `bridge_account_settings` WRITE;
/*!40000 ALTER TABLE `bridge_account_settings` DISABLE KEYS */;
/*!40000 ALTER TABLE `bridge_account_settings` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `bridge_accounts`
--

DROP TABLE IF EXISTS `bridge_accounts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `bridge_accounts` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `api_id` int(11) unsigned NOT NULL,
  `dist_id` char(45) COLLATE utf8_unicode_ci DEFAULT NULL,
  `usr_id` int(11) unsigned NOT NULL,
  `name` varchar(256) COLLATE utf8_unicode_ci DEFAULT NULL,
  `created_on` datetime NOT NULL,
  `updated_on` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `api_id` (`api_id`),
  KEY `dist_id` (`dist_id`),
  KEY `usr_id` (`usr_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `bridge_accounts`
--

LOCK TABLES `bridge_accounts` WRITE;
/*!40000 ALTER TABLE `bridge_accounts` DISABLE KEYS */;
/*!40000 ALTER TABLE `bridge_accounts` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `bridge_apis`
--

DROP TABLE IF EXISTS `bridge_apis`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `bridge_apis` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `name` char(45) COLLATE utf8_unicode_ci NOT NULL,
  `disable` tinyint(1) NOT NULL DEFAULT '0',
  `disable_time` datetime DEFAULT NULL,
  `created_on` datetime NOT NULL,
  `updated_on` datetime NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `bridge_apis`
--

LOCK TABLES `bridge_apis` WRITE;
/*!40000 ALTER TABLE `bridge_apis` DISABLE KEYS */;
INSERT INTO `bridge_apis` VALUES (1,'youtube',0,NULL,'0000-00-00 00:00:00','0000-00-00 00:00:00'),(2,'flickr',0,NULL,'0000-00-00 00:00:00','0000-00-00 00:00:00'),(3,'dailymotion',0,NULL,'0000-00-00 00:00:00','0000-00-00 00:00:00');
/*!40000 ALTER TABLE `bridge_apis` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `bridge_elements`
--

DROP TABLE IF EXISTS `bridge_elements`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `bridge_elements` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `account_id` int(11) unsigned NOT NULL,
  `sbas_id` int(11) unsigned NOT NULL,
  `record_id` int(11) unsigned NOT NULL,
  `dist_id` char(35) COLLATE utf8_unicode_ci DEFAULT NULL,
  `status` varchar(35) COLLATE utf8_unicode_ci DEFAULT NULL,
  `connector_status` varchar(35) COLLATE utf8_unicode_ci DEFAULT NULL,
  `title` varchar(80) COLLATE utf8_unicode_ci DEFAULT NULL,
  `type` varchar(80) COLLATE utf8_unicode_ci NOT NULL,
  `serialized_datas` text COLLATE utf8_unicode_ci NOT NULL,
  `created_on` datetime NOT NULL,
  `updated_on` datetime NOT NULL,
  `uploaded_on` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `sbas_id` (`sbas_id`),
  KEY `record_id` (`record_id`),
  KEY `dist_id` (`dist_id`),
  KEY `account_id` (`account_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `bridge_elements`
--

LOCK TABLES `bridge_elements` WRITE;
/*!40000 ALTER TABLE `bridge_elements` DISABLE KEYS */;
/*!40000 ALTER TABLE `bridge_elements` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cache`
--

DROP TABLE IF EXISTS `cache`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cache` (
  `session_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `usr_id` int(11) unsigned NOT NULL,
  `nact` int(11) unsigned NOT NULL DEFAULT '0',
  `created_on` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `lastaccess` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `session` longblob NOT NULL,
  `app` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT 'a:1:{i:0;i:0;}',
  `user_agent` varchar(1024) COLLATE utf8_unicode_ci NOT NULL,
  `ip` char(40) COLLATE utf8_unicode_ci NOT NULL,
  `platform` char(128) COLLATE utf8_unicode_ci NOT NULL,
  `browser` char(128) COLLATE utf8_unicode_ci NOT NULL,
  `browser_version` char(16) COLLATE utf8_unicode_ci NOT NULL,
  `screen` char(16) COLLATE utf8_unicode_ci NOT NULL,
  `query` varchar(128) COLLATE utf8_unicode_ci NOT NULL,
  `query_time` datetime NOT NULL,
  `token` char(128) CHARACTER SET ascii COLLATE ascii_bin DEFAULT NULL,
  `nonce` char(16) CHARACTER SET ascii COLLATE ascii_bin DEFAULT NULL,
  PRIMARY KEY (`session_id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cache`
--

LOCK TABLES `cache` WRITE;
/*!40000 ALTER TABLE `cache` DISABLE KEYS */;
INSERT INTO `cache` VALUES (3,3,3,'2012-04-27 00:06:55','2012-04-27 02:06:56','\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0db_test\0localhost\0\0\0\0\0\0\0�\0\0\0\0\0\0root\0\0\0\0toor\0\0\0\0db_test\0<?xml version=\"1.0\" encoding=\"UTF-8\" standalone=\"yes\"?>\n<record modification_date=\"20120427020634\">\n  <path>/tmp/db_test/documents</path>\n  <subdefs>\n    <subdefgroup name=\"image\">\n      <subdef class=\"preview\" name=\"preview\" downloadable=\"true\">\n        <path>/tmp/db_test/subdefs</path>\n        <size>800</size>\n        <method>resample</method>\n        <dpi>72</dpi>\n        <strip>no</strip>\n        <quality>75</quality>\n        <meta>yes</meta>\n        <baseurl/>\n        <mediatype>image</mediatype>\n        <label lang=\"fr\">Prévisualisation</label>\n        <label lang=\"en\">Preview</label>\n      </subdef>\n      <subdef class=\"thumbnail\" name=\"thumbnail\" downloadable=\"true\">\n        <path>/tmp/db_test/subdefs</path>\n        <size>200</size>\n        <method>resample</method>\n        <dpi>72</dpi>\n        <strip>yes</strip>\n        <quality>75</quality>\n        <meta>no</meta>\n        <mediatype>image</mediatype>\n        <baseurl>web//db_test/subdefs</baseurl>\n        <label lang=\"fr\">Imagette</label>\n        <label lang=\"en\">Thumbnail</label>\n      </subdef>\n    </subdefgroup>\n    <subdefgroup name=\"video\">\n      <subdef class=\"preview\" name=\"preview\" downloadable=\"true\">\n        <path>/tmp/db_test/subdefs</path>\n        <size>800</size>\n        <mediatype>video</mediatype>\n        <writeDatas>yes</writeDatas>\n        <baseurl/>\n        <acodec>faac</acodec>\n        <vcodec>libx264</vcodec>\n        <bitrate>1000</bitrate>\n        <threads>8</threads>\n        <fps>15</fps>\n        <label lang=\"fr\">Prévisualisation</label>\n        <label lang=\"en\">Preview</label>\n      </subdef>\n      <subdef class=\"thumbnail\" name=\"thumbnailgif\" downloadable=\"true\">\n        <path>/tmp/db_test/subdefs</path>\n        <size>200</size>\n        <mediatype>gif</mediatype>\n        <delay>500</delay>\n        <writeDatas>no</writeDatas>\n        <baseurl>web//db_test/subdefs</baseurl>\n        <label lang=\"fr\">Animation GIF</label>\n        <label lang=\"en\">GIF Animation</label>\n      </subdef>\n      <subdef class=\"thumbnail\" name=\"thumbnail\" downloadable=\"true\">\n        <path>/tmp/db_test/subdefs</path>\n        <size>200</size>\n        <mediatype>image</mediatype>\n        <writeDatas>no</writeDatas>\n        <baseurl>web//db_test/subdefs</baseurl>\n        <label lang=\"fr\">Imagette</label>\n        <label lang=\"en\">Thumbnail</label>\n      </subdef>\n    </subdefgroup>\n    <subdefgroup name=\"audio\">\n      <subdef class=\"preview\" name=\"preview\" downloadable=\"true\">\n        <path>/tmp/db_test/subdefs</path>\n        <mediatype>audio</mediatype>\n        <writeDatas>yes</writeDatas>\n        <baseurl/>\n        <label lang=\"fr\">Prévisualisation</label>\n        <label lang=\"en\">Preview</label>\n      </subdef>\n      <subdef class=\"thumbnail\" name=\"thumbnail\" downloadable=\"true\">\n        <path>/tmp/db_test/subdefs</path>\n        <mediatype>image</mediatype>\n        <writeDatas>no</writeDatas>\n        <baseurl>web//db_test/subdefs</baseurl>\n        <label lang=\"fr\">Imagette</label>\n        <label lang=\"en\">Thumbnail</label>\n      </subdef>\n    </subdefgroup>\n    <subdefgroup name=\"document\">\n      <subdef class=\"preview\" name=\"preview\" downloadable=\"false\">\n        <path>/tmp/db_test/subdefs</path>\n        <mediatype>flexpaper</mediatype>\n        <writeDatas>no</writeDatas>\n        <baseurl/>\n        <label lang=\"fr\">Prévisualisation</label>\n        <label lang=\"en\">Preview</label>\n      </subdef>\n      <subdef class=\"thumbnail\" name=\"thumbnail\" downloadable=\"false\">\n        <path>/tmp/db_test/subdefs</path>\n        <mediatype>image</mediatype>\n        <method>resample</method>\n        <dpi>72</dpi>\n        <size>200</size>\n        <writeDatas>no</writeDatas>\n        <baseurl>web//db_test/subdefs</baseurl>\n        <label lang=\"fr\">Imagette</label>\n        <label lang=\"en\">Thumbnail</label>\n      </subdef>\n    </subdefgroup>\n    <subdefgroup name=\"flash\">\n      <subdef class=\"preview\" name=\"preview\" downloadable=\"false\">\n        <path>/tmp/db_test/subdefs</path>\n        <mediatype>image</mediatype>\n        <size>200</size>\n        <writeDatas>no</writeDatas>\n        <method>resample</method>\n        <dpi>72</dpi>\n        <baseurl/>\n        <label lang=\"fr\">Prévisualisation</label>\n        <label lang=\"en\">Preview</label>\n      </subdef>\n      <subdef class=\"thumbnail\" name=\"thumbnail\" downloadable=\"false\">\n        <path>/tmp/db_test/subdefs</path>\n        <mediatype>image</mediatype>\n        <writeDatas>no</writeDatas>\n        <method>resample</method>\n        <dpi>72</dpi>\n        <baseurl>web//db_test/subdefs</baseurl>\n        <label lang=\"fr\">Imagette</label>\n        <label lang=\"en\">Thumbnail</label>\n      </subdef>\n    </subdefgroup>\n  </subdefs>\n  <description>\n    <!-- 2:5 -->\n    <!-- 2:15 -->\n    <!-- 2:20 -->\n    <!-- 2:25 -->\n    <!-- 2:40 -->\n    <!-- 2:55 -->\n    <!-- 2:80 -->\n    <!-- 2:85 -->\n    <!-- 2:90 -->\n    <!-- 2:95 -->\n    <!-- 2:101 -->\n    <!-- 2:103 -->\n    <!-- 2:105 -->\n    <!-- 2:110 -->\n    <!-- 2:115 -->\n    <!-- 2:120 -->\n    <!-- 2:122 -->\n    <!-- 2:25 -->\n    <!-- 2:25 -->\n    <!-- 2:25 -->\n    <!-- Technical Fields -->\n    <Object src=\"/rdf:RDF/rdf:Description/IPTC:ObjectName\" index=\"1\" readonly=\"0\" required=\"0\" multi=\"0\" report=\"1\" type=\"string\" tbranch=\"\" thumbtitle=\"0\" meta_id=\"1\"/>\n    <Category src=\"/rdf:RDF/rdf:Description/IPTC:Category\" index=\"1\" readonly=\"0\" required=\"0\" multi=\"0\" report=\"1\" type=\"string\" tbranch=\"\" thumbtitle=\"0\" meta_id=\"2\"/>\n    <SupplCategory src=\"/rdf:RDF/rdf:Description/IPTC:SupplementalCategories\" index=\"1\" readonly=\"0\" required=\"0\" multi=\"0\" report=\"1\" type=\"string\" tbranch=\"\" thumbtitle=\"0\" meta_id=\"3\"/>\n    <Keywords src=\"/rdf:RDF/rdf:Description/IPTC:Keywords\" index=\"1\" readonly=\"0\" required=\"0\" multi=\"1\" report=\"1\" type=\"string\" tbranch=\"\" separator=\";\" thumbtitle=\"0\" meta_id=\"4\"/>\n    <SpecialInstruct src=\"/rdf:RDF/rdf:Description/IPTC:SpecialInstructions\" index=\"1\" readonly=\"0\" required=\"0\" multi=\"0\" report=\"1\" type=\"string\" tbranch=\"\" thumbtitle=\"0\" meta_id=\"5\"/>\n    <Date src=\"/rdf:RDF/rdf:Description/IPTC:DateCreated\" index=\"1\" readonly=\"0\" required=\"0\" multi=\"0\" report=\"1\" type=\"date\" tbranch=\"\" thumbtitle=\"0\" meta_id=\"6\"/>\n    <Byline src=\"/rdf:RDF/rdf:Description/IPTC:By-line\" index=\"1\" readonly=\"0\" required=\"0\" multi=\"0\" report=\"1\" type=\"string\" tbranch=\"\" thumbtitle=\"0\" meta_id=\"7\"/>\n    <BylineTitle src=\"/rdf:RDF/rdf:Description/IPTC:By-lineTitle\" index=\"1\" readonly=\"0\" required=\"0\" multi=\"0\" report=\"1\" type=\"string\" tbranch=\"\" thumbtitle=\"0\" meta_id=\"8\"/>\n    <City src=\"/rdf:RDF/rdf:Description/IPTC:City\" index=\"1\" readonly=\"0\" required=\"0\" multi=\"0\" report=\"1\" type=\"string\" tbranch=\"\" thumbtitle=\"0\" meta_id=\"9\"/>\n    <Province src=\"/rdf:RDF/rdf:Description/IPTC:Province-State\" index=\"1\" readonly=\"0\" required=\"0\" multi=\"0\" report=\"1\" type=\"string\" tbranch=\"\" thumbtitle=\"0\" meta_id=\"10\"/>\n    <Country src=\"/rdf:RDF/rdf:Description/IPTC:Country-PrimaryLocationName\" index=\"1\" readonly=\"0\" required=\"0\" multi=\"0\" report=\"1\" type=\"string\" tbranch=\"\" thumbtitle=\"0\" meta_id=\"11\"/>\n    <OriginalRef src=\"/rdf:RDF/rdf:Description/IPTC:OriginalTransmissionReference\" index=\"1\" readonly=\"0\" required=\"0\" multi=\"0\" report=\"1\" type=\"string\" tbranch=\"\" thumbtitle=\"0\" meta_id=\"12\"/>\n    <Headline src=\"/rdf:RDF/rdf:Description/IPTC:Headline\" index=\"1\" readonly=\"0\" required=\"0\" multi=\"0\" report=\"1\" type=\"string\" tbranch=\"\" thumbtitle=\"0\" meta_id=\"13\"/>\n    <Credit src=\"/rdf:RDF/rdf:Description/IPTC:Credit\" index=\"1\" readonly=\"0\" required=\"0\" multi=\"0\" report=\"1\" type=\"string\" tbranch=\"\" thumbtitle=\"0\" meta_id=\"14\"/>\n    <Source src=\"/rdf:RDF/rdf:Description/IPTC:Source\" index=\"1\" readonly=\"0\" required=\"0\" multi=\"0\" report=\"1\" type=\"string\" tbranch=\"\" thumbtitle=\"0\" meta_id=\"15\"/>\n    <Caption src=\"/rdf:RDF/rdf:Description/IPTC:Caption-Abstract\" index=\"1\" readonly=\"0\" required=\"0\" multi=\"0\" report=\"1\" type=\"string\" tbranch=\"\" thumbtitle=\"0\" meta_id=\"16\"/>\n    <CaptionWriter src=\"/rdf:RDF/rdf:Description/IPTC:Writer-Editor\" index=\"1\" readonly=\"0\" required=\"0\" multi=\"0\" report=\"1\" type=\"string\" tbranch=\"\" thumbtitle=\"0\" meta_id=\"17\"/>\n    <Longitude src=\"/rdf:RDF/rdf:Description/GPS:GPSLongitude\" index=\"1\" readonly=\"1\" required=\"0\" multi=\"0\" report=\"1\" type=\"string\" tbranch=\"\" thumbtitle=\"0\" meta_id=\"18\"/>\n    <Latitude src=\"/rdf:RDF/rdf:Description/GPS:GPSLatitude\" index=\"1\" readonly=\"1\" required=\"0\" multi=\"0\" report=\"1\" type=\"string\" tbranch=\"\" thumbtitle=\"0\" meta_id=\"19\"/>\n    <CameraModel src=\"/rdf:RDF/rdf:Description/IFD0:Model\" index=\"1\" readonly=\"1\" required=\"0\" multi=\"0\" report=\"1\" type=\"string\" tbranch=\"\" thumbtitle=\"0\" meta_id=\"20\"/>\n    <FileName src=\"/rdf:RDF/rdf:Description/PHRASEANET:tf-filename\" index=\"1\" readonly=\"1\" required=\"0\" multi=\"0\" report=\"1\" type=\"text\" tbranch=\"\" thumbtitle=\"0\" meta_id=\"21\"/>\n    <FilePath src=\"/rdf:RDF/rdf:Description/PHRASEANET:tf-filepath\" index=\"1\" readonly=\"1\" required=\"0\" multi=\"0\" report=\"1\" type=\"text\" tbranch=\"\" thumbtitle=\"0\" meta_id=\"22\"/>\n    <Recordid src=\"/rdf:RDF/rdf:Description/PHRASEANET:tf-recordid\" index=\"1\" readonly=\"1\" required=\"0\" multi=\"0\" report=\"1\" type=\"number\" tbranch=\"\" thumbtitle=\"0\" meta_id=\"23\"/>\n    <MimeType src=\"/rdf:RDF/rdf:Description/PHRASEANET:tf-mimetype\" index=\"1\" readonly=\"1\" required=\"0\" multi=\"0\" report=\"1\" type=\"text\" tbranch=\"\" thumbtitle=\"0\" meta_id=\"24\"/>\n    <Size src=\"/rdf:RDF/rdf:Description/PHRASEANET:tf-size\" index=\"1\" readonly=\"1\" required=\"0\" multi=\"0\" report=\"1\" type=\"number\" tbranch=\"\" thumbtitle=\"0\" meta_id=\"25\"/>\n    <Extension src=\"/rdf:RDF/rdf:Description/PHRASEANET:tf-extension\" index=\"1\" readonly=\"1\" required=\"0\" multi=\"0\" report=\"1\" type=\"text\" tbranch=\"\" thumbtitle=\"0\" meta_id=\"26\"/>\n    <Width src=\"/rdf:RDF/rdf:Description/PHRASEANET:tf-width\" index=\"1\" readonly=\"1\" required=\"0\" multi=\"0\" report=\"1\" type=\"number\" tbranch=\"\" thumbtitle=\"0\" meta_id=\"27\"/>\n    <Height src=\"/rdf:RDF/rdf:Description/PHRASEANET:tf-height\" index=\"1\" readonly=\"1\" required=\"0\" multi=\"0\" report=\"1\" type=\"number\" tbranch=\"\" thumbtitle=\"0\" meta_id=\"28\"/>\n    <Bits src=\"/rdf:RDF/rdf:Description/PHRASEANET:tf-bits\" index=\"1\" readonly=\"1\" required=\"0\" multi=\"0\" report=\"1\" type=\"number\" tbranch=\"\" thumbtitle=\"0\" meta_id=\"29\"/>\n    <Channels src=\"/rdf:RDF/rdf:Description/PHRASEANET:tf-channels\" index=\"1\" readonly=\"1\" required=\"0\" multi=\"0\" report=\"1\" type=\"number\" tbranch=\"\" thumbtitle=\"0\" meta_id=\"30\"/>\n  </description>\n  <statbits>\n    <bit n=\"4\">Online</bit>\n  </statbits>\n</record>\n\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0test\0\0\0\0<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n            <baseprefs>\n                <status>0</status>\n                <sugestedValues>\n                </sugestedValues>\n            </baseprefs>\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0coll2\0\0\0<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n            <baseprefs>\n                <status>0</status>\n                <sugestedValues>\n                </sugestedValues>\n            </baseprefs>\0\0\0','a:1:{i:0;i:0;}','Mozilla/5.0 (Macintosh; Intel Mac OS X 10.7; rv:12.0) Gecko/20100101 Firefox/12.0','127.0.0.1','Apple','Firefox','12.0','1280x800','','0000-00-00 00:00:00',NULL,NULL);
/*!40000 ALTER TABLE `cache` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `demand`
--

DROP TABLE IF EXISTS `demand`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `demand` (
  `date_modif` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `usr_id` int(11) unsigned NOT NULL DEFAULT '0',
  `base_id` int(11) unsigned NOT NULL DEFAULT '0',
  `en_cours` tinyint(3) NOT NULL DEFAULT '0',
  `refuser` tinyint(3) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`usr_id`,`base_id`,`en_cours`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `demand`
--

LOCK TABLES `demand` WRITE;
/*!40000 ALTER TABLE `demand` DISABLE KEYS */;
/*!40000 ALTER TABLE `demand` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `dsel`
--

DROP TABLE IF EXISTS `dsel`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `dsel` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `name` char(50) COLLATE utf8_unicode_ci NOT NULL,
  `usr_id` int(11) unsigned NOT NULL DEFAULT '0',
  `query` char(255) COLLATE utf8_unicode_ci NOT NULL,
  `bases` char(255) COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`usr_id`,`name`),
  UNIQUE KEY `id` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `dsel`
--

LOCK TABLES `dsel` WRITE;
/*!40000 ALTER TABLE `dsel` DISABLE KEYS */;
/*!40000 ALTER TABLE `dsel` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `edit_presets`
--

DROP TABLE IF EXISTS `edit_presets`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `edit_presets` (
  `edit_preset_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `sbas_id` bigint(11) unsigned NOT NULL,
  `usr_id` bigint(11) unsigned NOT NULL,
  `creation_date` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `title` char(100) COLLATE utf8_unicode_ci NOT NULL,
  `xml` blob NOT NULL,
  PRIMARY KEY (`edit_preset_id`),
  KEY `sbas_id` (`sbas_id`),
  KEY `usr_id` (`usr_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `edit_presets`
--

LOCK TABLES `edit_presets` WRITE;
/*!40000 ALTER TABLE `edit_presets` DISABLE KEYS */;
/*!40000 ALTER TABLE `edit_presets` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `feed_entries`
--

DROP TABLE IF EXISTS `feed_entries`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `feed_entries` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `feed_id` int(11) unsigned NOT NULL,
  `publisher` int(11) unsigned NOT NULL,
  `title` varchar(128) COLLATE utf8_unicode_ci NOT NULL,
  `author_name` varchar(128) COLLATE utf8_unicode_ci NOT NULL,
  `author_email` varchar(128) COLLATE utf8_unicode_ci NOT NULL,
  `description` varchar(8192) COLLATE utf8_unicode_ci NOT NULL,
  `created_on` datetime NOT NULL,
  `updated_on` datetime NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `feed_entries`
--

LOCK TABLES `feed_entries` WRITE;
/*!40000 ALTER TABLE `feed_entries` DISABLE KEYS */;
/*!40000 ALTER TABLE `feed_entries` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `feed_entry_elements`
--

DROP TABLE IF EXISTS `feed_entry_elements`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `feed_entry_elements` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `entry_id` int(11) unsigned NOT NULL,
  `sbas_id` int(11) unsigned NOT NULL,
  `record_id` int(11) unsigned NOT NULL,
  `ord` int(11) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `element` (`sbas_id`,`record_id`),
  KEY `sbas_id` (`sbas_id`),
  KEY `record_id` (`record_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `feed_entry_elements`
--

LOCK TABLES `feed_entry_elements` WRITE;
/*!40000 ALTER TABLE `feed_entry_elements` DISABLE KEYS */;
/*!40000 ALTER TABLE `feed_entry_elements` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `feed_publishers`
--

DROP TABLE IF EXISTS `feed_publishers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `feed_publishers` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `usr_id` int(11) unsigned NOT NULL,
  `feed_id` int(11) unsigned NOT NULL,
  `owner` tinyint(11) unsigned DEFAULT NULL,
  `created_on` datetime NOT NULL,
  `added_by` int(11) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `couple` (`usr_id`,`feed_id`),
  UNIQUE KEY `owners` (`owner`,`feed_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `feed_publishers`
--

LOCK TABLES `feed_publishers` WRITE;
/*!40000 ALTER TABLE `feed_publishers` DISABLE KEYS */;
/*!40000 ALTER TABLE `feed_publishers` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `feed_tokens`
--

DROP TABLE IF EXISTS `feed_tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `feed_tokens` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `token` varchar(12) COLLATE utf8_unicode_ci NOT NULL,
  `feed_id` int(11) unsigned DEFAULT NULL,
  `usr_id` int(11) unsigned NOT NULL,
  `aggregated` tinyint(1) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unikaggregated` (`aggregated`,`usr_id`),
  UNIQUE KEY `unikfeed` (`feed_id`,`usr_id`),
  KEY `token` (`token`),
  KEY `usr_id` (`usr_id`),
  KEY `feed_id` (`feed_id`),
  KEY `aggregated` (`aggregated`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `feed_tokens`
--

LOCK TABLES `feed_tokens` WRITE;
/*!40000 ALTER TABLE `feed_tokens` DISABLE KEYS */;
/*!40000 ALTER TABLE `feed_tokens` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `feeds`
--

DROP TABLE IF EXISTS `feeds`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `feeds` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(128) COLLATE utf8_unicode_ci NOT NULL,
  `subtitle` varchar(8192) COLLATE utf8_unicode_ci NOT NULL,
  `created_on` datetime NOT NULL,
  `updated_on` datetime NOT NULL,
  `base_id` int(11) unsigned DEFAULT NULL,
  `public` tinyint(1) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `base_id` (`base_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `feeds`
--

LOCK TABLES `feeds` WRITE;
/*!40000 ALTER TABLE `feeds` DISABLE KEYS */;
/*!40000 ALTER TABLE `feeds` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ftp_export`
--

DROP TABLE IF EXISTS `ftp_export`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ftp_export` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `crash` tinyint(3) unsigned NOT NULL DEFAULT '0',
  `nbretry` tinyint(3) unsigned NOT NULL DEFAULT '5',
  `mail` char(255) COLLATE utf8_unicode_ci NOT NULL,
  `addr` char(255) COLLATE utf8_unicode_ci NOT NULL,
  `ssl` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `login` char(255) COLLATE utf8_unicode_ci NOT NULL,
  `pwd` char(255) COLLATE utf8_unicode_ci NOT NULL,
  `passif` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `destfolder` char(255) COLLATE utf8_unicode_ci NOT NULL,
  `sendermail` char(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `text_mail_sender` longtext COLLATE utf8_unicode_ci NOT NULL,
  `text_mail_receiver` longtext COLLATE utf8_unicode_ci NOT NULL,
  `usr_id` int(11) unsigned NOT NULL DEFAULT '0',
  `date` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `foldertocreate` char(255) COLLATE utf8_unicode_ci NOT NULL,
  `logfile` tinyint(1) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `usr_id` (`usr_id`),
  KEY `crash` (`crash`),
  KEY `nbretry` (`nbretry`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ftp_export`
--

LOCK TABLES `ftp_export` WRITE;
/*!40000 ALTER TABLE `ftp_export` DISABLE KEYS */;
/*!40000 ALTER TABLE `ftp_export` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ftp_export_elements`
--

DROP TABLE IF EXISTS `ftp_export_elements`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ftp_export_elements` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `ftp_export_id` int(11) unsigned NOT NULL DEFAULT '0',
  `base_id` int(11) unsigned NOT NULL DEFAULT '0',
  `record_id` int(11) unsigned NOT NULL DEFAULT '0',
  `subdef` char(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '0',
  `filename` char(255) COLLATE utf8_unicode_ci NOT NULL,
  `folder` char(255) COLLATE utf8_unicode_ci NOT NULL,
  `businessfields` tinyint(1) unsigned DEFAULT NULL,
  `error` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `done` tinyint(1) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `ftp_export_id` (`ftp_export_id`),
  KEY `done` (`done`),
  KEY `error` (`error`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ftp_export_elements`
--

LOCK TABLES `ftp_export_elements` WRITE;
/*!40000 ALTER TABLE `ftp_export_elements` DISABLE KEYS */;
/*!40000 ALTER TABLE `ftp_export_elements` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `lazaret`
--

DROP TABLE IF EXISTS `lazaret`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `lazaret` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `filename` char(255) COLLATE utf8_unicode_ci NOT NULL,
  `filepath` varchar(512) COLLATE utf8_unicode_ci NOT NULL,
  `base_id` int(11) unsigned NOT NULL DEFAULT '0',
  `uuid` char(36) COLLATE utf8_unicode_ci DEFAULT NULL,
  `sha256` char(64) CHARACTER SET ascii COLLATE ascii_bin DEFAULT NULL,
  `errors` text COLLATE utf8_unicode_ci NOT NULL,
  `status` bigint(20) unsigned NOT NULL DEFAULT '0',
  `created_on` datetime NOT NULL,
  `usr_id` int(11) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `lazaret`
--

LOCK TABLES `lazaret` WRITE;
/*!40000 ALTER TABLE `lazaret` DISABLE KEYS */;
/*!40000 ALTER TABLE `lazaret` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `notifications`
--

DROP TABLE IF EXISTS `notifications`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `notifications` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `usr_id` int(11) unsigned NOT NULL DEFAULT '0',
  `type` char(32) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `unread` tinyint(1) unsigned NOT NULL DEFAULT '1',
  `mailed` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `datas` longblob NOT NULL,
  `created_on` datetime NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `notifications`
--

LOCK TABLES `notifications` WRITE;
/*!40000 ALTER TABLE `notifications` DISABLE KEYS */;
/*!40000 ALTER TABLE `notifications` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `order`
--

DROP TABLE IF EXISTS `order`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `order` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `usr_id` int(11) unsigned NOT NULL,
  `created_on` datetime NOT NULL,
  `usage` longtext COLLATE utf8_unicode_ci NOT NULL,
  `deadline` datetime NOT NULL,
  `ssel_id` int(11) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `usr` (`usr_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `order`
--

LOCK TABLES `order` WRITE;
/*!40000 ALTER TABLE `order` DISABLE KEYS */;
/*!40000 ALTER TABLE `order` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `order_elements`
--

DROP TABLE IF EXISTS `order_elements`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `order_elements` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `order_id` int(11) unsigned NOT NULL,
  `base_id` int(11) unsigned NOT NULL,
  `record_id` int(11) unsigned NOT NULL,
  `order_master_id` int(11) unsigned DEFAULT NULL,
  `deny` tinyint(1) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `triplet` (`order_id`,`base_id`,`record_id`),
  KEY `order_id` (`order_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `order_elements`
--

LOCK TABLES `order_elements` WRITE;
/*!40000 ALTER TABLE `order_elements` DISABLE KEYS */;
/*!40000 ALTER TABLE `order_elements` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `records_rights`
--

DROP TABLE IF EXISTS `records_rights`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `records_rights` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `usr_id` int(11) unsigned NOT NULL,
  `sbas_id` int(11) unsigned NOT NULL,
  `record_id` int(11) unsigned NOT NULL,
  `document` tinyint(1) unsigned NOT NULL,
  `preview` tinyint(1) unsigned NOT NULL,
  `case` char(32) COLLATE utf8_unicode_ci NOT NULL,
  `pusher_usr_id` int(11) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `sbas_record_user` (`sbas_id`,`record_id`,`usr_id`),
  KEY `pusher_usr_id` (`pusher_usr_id`),
  KEY `document` (`document`),
  KEY `preview` (`preview`),
  KEY `usr_id` (`usr_id`),
  KEY `sbas_record` (`sbas_id`,`record_id`),
  KEY `sbas_record_document` (`sbas_id`,`record_id`,`document`),
  KEY `sbas_record_preview` (`sbas_id`,`record_id`,`preview`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `records_rights`
--

LOCK TABLES `records_rights` WRITE;
/*!40000 ALTER TABLE `records_rights` DISABLE KEYS */;
/*!40000 ALTER TABLE `records_rights` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `registry`
--

DROP TABLE IF EXISTS `registry`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `registry` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `key` char(32) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `value` varchar(1024) COLLATE utf8_unicode_ci NOT NULL,
  `type` enum('string','boolean','array','integer') COLLATE utf8_unicode_ci NOT NULL DEFAULT 'string',
  PRIMARY KEY (`id`),
  UNIQUE KEY `UNIQUE` (`key`)
) ENGINE=InnoDB AUTO_INCREMENT=123 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `registry`
--

LOCK TABLES `registry` WRITE;
/*!40000 ALTER TABLE `registry` DISABLE KEYS */;
INSERT INTO `registry` VALUES (1,'GV_timezone','Europe/Paris','string'),(2,'GV_sit','c51b8b3501fb45447c359fda7d3784d4','string'),(3,'GV_default_lng','fr_FR','string'),(4,'GV_STATIC_URL','','string'),(5,'GV_message','May the force be with you','string'),(6,'GV_message_on','0','boolean'),(7,'GV_log_errors','0','boolean'),(8,'GV_google_api','0','boolean'),(9,'GV_i18n_service','http://localization.webservice.alchemyasp.com/','string'),(10,'GV_bitly_user','','string'),(11,'GV_bitly_key','','string'),(12,'GV_captchas','0','boolean'),(13,'GV_captcha_public_key','','string'),(14,'GV_captcha_private_key','','string'),(15,'GV_youtube_api','0','boolean'),(16,'GV_youtube_client_id','','string'),(17,'GV_youtube_client_secret','','string'),(18,'GV_youtube_dev_key','','string'),(19,'GV_flickr_api','0','boolean'),(20,'GV_flickr_client_id','','string'),(21,'GV_flickr_client_secret','','string'),(22,'GV_dailymotion_api','0','boolean'),(23,'GV_dailymotion_client_id','','string'),(24,'GV_dailymotion_client_secret','','string'),(25,'GV_client_navigator','0','boolean'),(26,'GV_events','a:1:{i:0;s:24:\"eventsmanager_event_test\";}','array'),(27,'GV_notifications','a:12:{i:0;s:33:\"eventsmanager_notify_autoregister\";i:1;s:37:\"eventsmanager_notify_bridgeuploadfail\";i:2;s:37:\"eventsmanager_notify_downloadmailfail\";i:3;s:25:\"eventsmanager_notify_feed\";i:4;s:26:\"eventsmanager_notify_order\";i:5;s:33:\"eventsmanager_notify_orderdeliver\";i:6;s:38:\"eventsmanager_notify_ordernotdelivered\";i:7;s:25:\"eventsmanager_notify_push\";i:8;s:29:\"eventsmanager_notify_register\";i:9;s:29:\"eventsmanager_notify_validate\";i:10;s:35:\"eventsmanager_notify_validationdone\";i:11;s:39:\"eventsmanager_notify_validationreminder\";}','array'),(28,'GV_appletAllowedFileExt','jpg,jpeg,bmp,tif,gif,png,pdf,doc,odt,mpg,mpeg,mov,avi,xls,flv,mp3,mp2','string'),(32,'GV_sphinx','0','boolean'),(33,'GV_sphinx_host','127.0.0.1','string'),(34,'GV_sphinx_port','9306','integer'),(35,'GV_sphinx_rt_host','127.0.0.1','string'),(36,'GV_sphinx_rt_port','9308','integer'),(37,'GV_phrasea_sort','','string'),(38,'GV_modxsendfile','0','boolean'),(39,'GV_X_Accel_Redirect','','string'),(40,'GV_X_Accel_Redirect_mount_point','noweb','string'),(41,'GV_h264_streaming','0','boolean'),(42,'GV_mod_auth_token_directory','','string'),(43,'GV_mod_auth_token_directory_path','','string'),(44,'GV_mod_auth_token_passphrase','','string'),(46,'GV_PHP_INI','','string'),(58,'GV_pdfmaxpages','5','integer'),(59,'GV_filesOwner','','string'),(60,'GV_filesGroup','','string'),(61,'GV_adminMail','support@alchemy.fr','string'),(62,'GV_view_bas_and_coll','0','boolean'),(63,'GV_choose_export_title','0','boolean'),(64,'GV_default_export_title','support@alchemy.fr','string'),(65,'GV_social_tools','none','string'),(66,'GV_home_publi','COOLIRIS','string'),(67,'GV_min_letters_truncation','1','integer'),(68,'GV_defaultQuery','all','string'),(69,'GV_defaultQuery_type','0','string'),(70,'GV_anonymousReport','0','boolean'),(71,'GV_thesaurus','0','boolean'),(72,'GV_multiAndReport','0','boolean'),(73,'GV_seeOngChgDoc','0','boolean'),(74,'GV_seeNewThumb','0','boolean'),(75,'GV_defaulmailsenderaddr','phraseanet@example.com','string'),(76,'GV_smtp','0','boolean'),(77,'GV_smtp_auth','0','boolean'),(78,'GV_smtp_host','','string'),(79,'GV_smtp_port','','string'),(80,'GV_smtp_secure','0','boolean'),(81,'GV_smtp_user','','string'),(82,'GV_smtp_password','','string'),(83,'GV_activeFTP','0','boolean'),(84,'GV_ftp_for_user','0','boolean'),(85,'GV_download_max','120','integer'),(86,'GV_ong_search','1','integer'),(87,'GV_ong_advsearch','2','integer'),(88,'GV_ong_topics','0','integer'),(89,'GV_ong_actif','1','integer'),(90,'GV_client_render_topics','tree','string'),(91,'GV_rollover_reg_preview','0','boolean'),(92,'GV_rollover_chu','0','boolean'),(93,'GV_client_coll_ckbox','checkbox','string'),(94,'GV_viewSizeBaket','0','boolean'),(95,'GV_clientAutoShowProposals','0','boolean'),(96,'GV_needAuth2DL','0','boolean'),(97,'GV_autoselectDB','0','boolean'),(98,'GV_autoregister','0','boolean'),(99,'GV_validation_reminder','2','integer'),(100,'GV_val_expiration','10','integer'),(101,'GV_homeTitle','Phraseanet','string'),(102,'GV_metaKeywords','','string'),(103,'GV_metaDescription','','string'),(104,'GV_googleAnalytics','','string'),(105,'GV_allow_search_engine','0','boolean'),(106,'GV_display_gcf','0','boolean'),(107,'GV_base_datapath_noweb','/tmp/','string'),(108,'GV_base_datapath_web','/tmp/','string'),(109,'GV_base_dataurl','web/','string'),(110,'GV_ServerName','http://local.phrasea/','string'),(111,'GV_cli','/usr/local/bin/php','string'),(112,'GV_imagick','/usr/local/bin/gm','string'),(113,'GV_pathcomposite','/usr/local/bin/gm','string'),(114,'GV_exiftool','/home/vagrant/builds/alchemy-fr/Phraseanet/lib/vendor/exiftool/exiftool','string'),(115,'GV_swf_extract','','string'),(116,'GV_pdf2swf','','string'),(117,'GV_swf_render','','string'),(118,'GV_unoconv','','string'),(119,'GV_ffmpeg','','string'),(120,'GV_mp4box','','string'),(121,'GV_mplayer','','string'),(122,'GV_pdftotext','','string');
/*!40000 ALTER TABLE `registry` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sbas`
--

DROP TABLE IF EXISTS `sbas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `sbas` (
  `sbas_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `ord` int(11) unsigned NOT NULL DEFAULT '0',
  `host` char(64) COLLATE utf8_unicode_ci NOT NULL,
  `port` int(11) unsigned NOT NULL DEFAULT '0',
  `dbname` char(64) COLLATE utf8_unicode_ci NOT NULL,
  `sqlengine` char(20) COLLATE utf8_unicode_ci NOT NULL,
  `user` char(64) COLLATE utf8_unicode_ci NOT NULL,
  `pwd` char(64) COLLATE utf8_unicode_ci NOT NULL,
  `thesaurus_id` int(11) unsigned NOT NULL DEFAULT '0',
  `viewname` char(128) COLLATE utf8_unicode_ci NOT NULL,
  `indexable` tinyint(1) unsigned NOT NULL DEFAULT '1',
  PRIMARY KEY (`sbas_id`),
  UNIQUE KEY `server` (`host`,`port`,`dbname`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sbas`
--

LOCK TABLES `sbas` WRITE;
/*!40000 ALTER TABLE `sbas` DISABLE KEYS */;
INSERT INTO `sbas` VALUES (1,1,'localhost',3306,'db_test','MYSQL','root','',0,'',1);
/*!40000 ALTER TABLE `sbas` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sbasusr`
--

DROP TABLE IF EXISTS `sbasusr`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `sbasusr` (
  `sbasusr_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `sbas_id` int(11) unsigned NOT NULL DEFAULT '0',
  `usr_id` int(11) unsigned NOT NULL DEFAULT '0',
  `bas_manage` tinyint(3) unsigned NOT NULL DEFAULT '0',
  `bas_modify_struct` tinyint(3) unsigned NOT NULL DEFAULT '0',
  `bas_modif_th` tinyint(3) unsigned NOT NULL DEFAULT '0',
  `bas_chupub` tinyint(3) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`sbasusr_id`),
  UNIQUE KEY `unikid` (`usr_id`,`sbas_id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sbasusr`
--

LOCK TABLES `sbasusr` WRITE;
/*!40000 ALTER TABLE `sbasusr` DISABLE KEYS */;
INSERT INTO `sbasusr` VALUES (1,1,3,1,1,1,1);
/*!40000 ALTER TABLE `sbasusr` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sitepreff`
--

DROP TABLE IF EXISTS `sitepreff`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `sitepreff` (
  `id` int(11) unsigned NOT NULL DEFAULT '0',
  `preffs` longtext COLLATE utf8_unicode_ci NOT NULL,
  `version` varchar(64) COLLATE utf8_unicode_ci DEFAULT NULL,
  `maj` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `memcached_update` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `schedstatus` enum('stopped','stopping','started','tostop') CHARACTER SET ascii COLLATE ascii_bin NOT NULL DEFAULT 'stopped',
  `schedqtime` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `schedpid` int(11) DEFAULT NULL,
  `schedulerkey` char(20) COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sitepreff`
--

LOCK TABLES `sitepreff` WRITE;
/*!40000 ALTER TABLE `sitepreff` DISABLE KEYS */;
INSERT INTO `sitepreff` VALUES (1,'<?xml version=\"1.0\" encoding=\"UTF-8\" standalone=\"yes\"?>                                <paramsite>                                    <statuschu>                                        <bit n=\"-1\" link=\"1\" order=\"0\" view=\"0\" label=\"\" wmprev=\"0\" thumbLimit=\"0\"/>                                    </statuschu>                                </paramsite>','3.7.0.0.a2','0000-00-00 00:00:00','2012-04-27 02:06:56','stopped','0000-00-00 00:00:00',0,'aiuaLF8sxKx78WbCHXjN');
/*!40000 ALTER TABLE `sitepreff` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ssel`
--

DROP TABLE IF EXISTS `ssel`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ssel` (
  `ssel_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `usr_id` int(11) unsigned NOT NULL DEFAULT '0',
  `name` char(50) COLLATE utf8_unicode_ci NOT NULL DEFAULT '0',
  `pushFrom` int(11) unsigned NOT NULL DEFAULT '0',
  `date` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `descript` text COLLATE utf8_unicode_ci NOT NULL,
  `temporaryType` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `rid` int(11) NOT NULL DEFAULT '0',
  `sbas_id` int(11) unsigned NOT NULL DEFAULT '0',
  `status` int(4) unsigned NOT NULL DEFAULT '0',
  `updater` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`ssel_id`),
  KEY `usr` (`usr_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ssel`
--

LOCK TABLES `ssel` WRITE;
/*!40000 ALTER TABLE `ssel` DISABLE KEYS */;
/*!40000 ALTER TABLE `ssel` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sselcont`
--

DROP TABLE IF EXISTS `sselcont`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `sselcont` (
  `sselcont_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `ssel_id` int(11) unsigned NOT NULL DEFAULT '0',
  `base_id` int(11) unsigned NOT NULL DEFAULT '0',
  `record_id` int(11) unsigned NOT NULL DEFAULT '0',
  `ord` bigint(11) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`sselcont_id`),
  UNIQUE KEY `ssel_ssel_id` (`ssel_id`,`base_id`,`record_id`),
  KEY `ssel_id` (`ssel_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sselcont`
--

LOCK TABLES `sselcont` WRITE;
/*!40000 ALTER TABLE `sselcont` DISABLE KEYS */;
/*!40000 ALTER TABLE `sselcont` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sselnew`
--

DROP TABLE IF EXISTS `sselnew`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `sselnew` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `ssel_id` int(11) unsigned NOT NULL DEFAULT '0',
  `usr_id` int(11) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `couple` (`ssel_id`,`usr_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sselnew`
--

LOCK TABLES `sselnew` WRITE;
/*!40000 ALTER TABLE `sselnew` DISABLE KEYS */;
/*!40000 ALTER TABLE `sselnew` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `task2`
--

DROP TABLE IF EXISTS `task2`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `task2` (
  `task_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `usr_id_owner` int(11) unsigned NOT NULL DEFAULT '0',
  `pid` int(11) unsigned DEFAULT NULL,
  `status` enum('stopped','started','starting','stopping','tostart','tostop','manual','torestart') CHARACTER SET ascii COLLATE ascii_bin NOT NULL DEFAULT 'stopped',
  `crashed` int(2) unsigned NOT NULL DEFAULT '0',
  `active` int(1) unsigned NOT NULL DEFAULT '0',
  `name` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
  `last_exec_time` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `class` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
  `settings` text COLLATE utf8_unicode_ci NOT NULL,
  `completed` tinyint(4) NOT NULL DEFAULT '-1',
  `runner` char(20) COLLATE utf8_unicode_ci NOT NULL DEFAULT '-1',
  PRIMARY KEY (`task_id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `task2`
--

LOCK TABLES `task2` WRITE;
/*!40000 ALTER TABLE `task2` DISABLE KEYS */;
INSERT INTO `task2` VALUES (1,0,NULL,'stopped',0,1,'Write meta-datas','0000-00-00 00:00:00','task_period_writemeta','<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n<tasksettings>\n</tasksettings>',-1,'-1'),(2,0,NULL,'stopped',0,1,'Subviews creation','0000-00-00 00:00:00','task_period_subdef','<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n<tasksettings>\n</tasksettings>',-1,'-1'),(3,0,NULL,'stopped',0,1,'Indexation','0000-00-00 00:00:00','task_period_cindexer','<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n<tasksettings>\n<binpath>/usr/local/bin</binpath><host>localhost</host><port>3306</port><base>ab_test</base><user>root</user><password>toor</password><socket>25200</socket><use_sbas>1</use_sbas><nolog>0</nolog><clng></clng><winsvc_run>0</winsvc_run><charset>utf8</charset></tasksettings>',-1,'-1');
/*!40000 ALTER TABLE `task2` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tokens`
--

DROP TABLE IF EXISTS `tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tokens` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `value` char(16) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `type` enum('FEED_ENTRY','view','validate','password','rss','email','download') CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `usr_id` int(11) unsigned NOT NULL,
  `datas` longtext COLLATE utf8_unicode_ci,
  `created_on` datetime NOT NULL,
  `expire_on` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `value` (`value`),
  KEY `expire` (`expire_on`),
  KEY `type` (`type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tokens`
--

LOCK TABLES `tokens` WRITE;
/*!40000 ALTER TABLE `tokens` DISABLE KEYS */;
/*!40000 ALTER TABLE `tokens` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `uplbatch`
--

DROP TABLE IF EXISTS `uplbatch`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `uplbatch` (
  `uplbatch_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `base_id` int(11) unsigned NOT NULL,
  `nfiles` int(5) unsigned NOT NULL,
  `complete` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `error` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `action` text COLLATE utf8_unicode_ci NOT NULL,
  `usr_id` int(11) unsigned NOT NULL,
  PRIMARY KEY (`uplbatch_id`),
  KEY `complete` (`complete`),
  KEY `error` (`error`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `uplbatch`
--

LOCK TABLES `uplbatch` WRITE;
/*!40000 ALTER TABLE `uplbatch` DISABLE KEYS */;
/*!40000 ALTER TABLE `uplbatch` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `uplfile`
--

DROP TABLE IF EXISTS `uplfile`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `uplfile` (
  `uplbatch_id` int(11) unsigned NOT NULL,
  `idx` int(5) unsigned NOT NULL,
  `filename` char(255) COLLATE utf8_unicode_ci NOT NULL,
  `error` tinyint(1) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`uplbatch_id`,`idx`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `uplfile`
--

LOCK TABLES `uplfile` WRITE;
/*!40000 ALTER TABLE `uplfile` DISABLE KEYS */;
/*!40000 ALTER TABLE `uplfile` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `usr`
--

DROP TABLE IF EXISTS `usr`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `usr` (
  `usr_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `ldap_created` int(1) unsigned NOT NULL DEFAULT '0',
  `usr_sexe` int(1) unsigned NOT NULL DEFAULT '0',
  `usr_nom` varchar(50) COLLATE utf8_unicode_ci NOT NULL,
  `usr_prenom` varchar(50) COLLATE utf8_unicode_ci NOT NULL,
  `usr_login` varchar(50) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL,
  `usr_password` varchar(128) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL,
  `usr_mail` varchar(64) COLLATE utf8_unicode_ci DEFAULT NULL,
  `usr_creationdate` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `usr_modificationdate` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `adresse` text COLLATE utf8_unicode_ci NOT NULL,
  `ville` varchar(64) COLLATE utf8_unicode_ci NOT NULL,
  `cpostal` varchar(32) COLLATE utf8_unicode_ci NOT NULL,
  `tel` varchar(32) COLLATE utf8_unicode_ci NOT NULL,
  `fax` varchar(32) COLLATE utf8_unicode_ci NOT NULL,
  `fonction` varchar(64) COLLATE utf8_unicode_ci NOT NULL,
  `societe` varchar(64) COLLATE utf8_unicode_ci NOT NULL,
  `activite` varchar(200) COLLATE utf8_unicode_ci NOT NULL,
  `code8` int(10) unsigned NOT NULL DEFAULT '0',
  `pays` varchar(64) COLLATE utf8_unicode_ci NOT NULL,
  `last_conn` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `model_of` int(11) unsigned NOT NULL DEFAULT '0',
  `create_db` tinyint(3) NOT NULL DEFAULT '0',
  `activeFTP` tinyint(1) NOT NULL DEFAULT '0',
  `addrFTP` varchar(128) COLLATE utf8_unicode_ci NOT NULL,
  `sslFTP` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `loginFTP` varchar(128) COLLATE utf8_unicode_ci NOT NULL,
  `pwdFTP` varchar(128) COLLATE utf8_unicode_ci NOT NULL,
  `destFTP` varchar(128) COLLATE utf8_unicode_ci NOT NULL,
  `passifFTP` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `retryFTP` tinyint(1) unsigned NOT NULL DEFAULT '5',
  `defaultftpdatasent` bigint(20) unsigned NOT NULL DEFAULT '0',
  `prefixFTPfolder` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
  `lastModel` varchar(50) COLLATE utf8_unicode_ci NOT NULL,
  `canchgprofil` tinyint(1) NOT NULL DEFAULT '1',
  `canchgftpprofil` tinyint(1) unsigned NOT NULL DEFAULT '1',
  `invite` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `mail_locked` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `geonameid` int(7) unsigned DEFAULT NULL,
  `updated` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `locale` enum('fr_FR','en_GB','en_US','ar_SA','de_DE','es_LA','zh_CN','nb_NO') CHARACTER SET ascii COLLATE ascii_bin DEFAULT NULL,
  `mail_notifications` tinyint(1) unsigned NOT NULL DEFAULT '1',
  `request_notifications` tinyint(1) unsigned NOT NULL DEFAULT '1',
  `push_list` text COLLATE utf8_unicode_ci NOT NULL,
  `timezone` varchar(128) COLLATE utf8_unicode_ci NOT NULL,
  `salted_password` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `nonce` char(16) CHARACTER SET utf8 COLLATE utf8_bin DEFAULT NULL,
  PRIMARY KEY (`usr_id`),
  UNIQUE KEY `usr_login` (`usr_login`),
  UNIQUE KEY `unique_usr_mail` (`usr_mail`),
  KEY `usr_mail` (`usr_mail`),
  KEY `model_of` (`model_of`),
  KEY `salted_password` (`salted_password`),
  KEY `invite` (`invite`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `usr`
--

LOCK TABLES `usr` WRITE;
/*!40000 ALTER TABLE `usr` DISABLE KEYS */;
INSERT INTO `usr` VALUES (1,2,0,'','','autoregister','b47d685868192373e44f55ec0e980d7e16dc87320cfde9a1fa09aab5138f41505d3ae96cd1a5769ed6a8b74a7d98670791a2e889176b720a2a88488079c41047',NULL,'2012-04-27 02:06:22','2012-04-27 02:06:22','','','','','','','','',0,'','0000-00-00 00:00:00',0,0,0,'',0,'','','',0,5,5,'','',0,0,0,0,NULL,0,NULL,1,1,'','',0,'62flCJhZkvmkmeAS'),(2,2,0,'','','invite','b8db5b3c2befc379ce28014f7ddd78549830b68308f128234ea492f0d2f60f4beb3eb58c28d7f954e7d940f420d7ed701270e160cc11df88ab82cbfc83a065ec',NULL,'2012-04-27 02:06:22','2012-04-27 02:06:22','','','','','','','','',0,'','0000-00-00 00:00:00',0,0,0,'',0,'','','',0,5,5,'','',0,0,0,0,NULL,0,NULL,1,1,'','',0,'VpeoogXiBhw9IH1c'),(3,0,0,'','','imprec@gmail.com','a18b29ac22e63b5d6f0a08f6a8928ec9d1ffaab0fdd11285589e41044afea6f3090f253c24039ad65d54063b17d96f9a5cb7ebf3a91dd1d55d1402a149f41245','imprec@gmail.com','2012-04-27 02:06:27','0000-00-00 00:00:00','','','','','','','','',0,'','2012-04-27 02:06:27',0,1,0,'',0,'','','',0,5,0,'','',1,1,0,0,NULL,0,'en_GB',1,1,'','',1,'AWvXWGkKikExclqj');
/*!40000 ALTER TABLE `usr` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `usr_settings`
--

DROP TABLE IF EXISTS `usr_settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `usr_settings` (
  `usr_id` int(11) unsigned NOT NULL,
  `prop` char(128) COLLATE utf8_unicode_ci NOT NULL DEFAULT '0',
  `value` varbinary(1024) DEFAULT '0',
  UNIQUE KEY `couple` (`prop`,`usr_id`),
  KEY `usr_id` (`usr_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `usr_settings`
--

LOCK TABLES `usr_settings` WRITE;
/*!40000 ALTER TABLE `usr_settings` DISABLE KEYS */;
/*!40000 ALTER TABLE `usr_settings` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `usrlist`
--

DROP TABLE IF EXISTS `usrlist`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `usrlist` (
  `list_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `usr_id` int(11) unsigned NOT NULL DEFAULT '0',
  `label` varchar(60) COLLATE utf8_unicode_ci NOT NULL DEFAULT '0',
  UNIQUE KEY `couple` (`list_id`,`usr_id`),
  UNIQUE KEY `label` (`label`,`usr_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `usrlist`
--

LOCK TABLES `usrlist` WRITE;
/*!40000 ALTER TABLE `usrlist` DISABLE KEYS */;
/*!40000 ALTER TABLE `usrlist` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `usrlistusers`
--

DROP TABLE IF EXISTS `usrlistusers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `usrlistusers` (
  `list_id` int(11) unsigned NOT NULL,
  `usr_id` int(11) unsigned NOT NULL DEFAULT '0',
  UNIQUE KEY `couple` (`list_id`,`usr_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `usrlistusers`
--

LOCK TABLES `usrlistusers` WRITE;
/*!40000 ALTER TABLE `usrlistusers` DISABLE KEYS */;
/*!40000 ALTER TABLE `usrlistusers` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `validate`
--

DROP TABLE IF EXISTS `validate`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `validate` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `ssel_id` int(11) unsigned NOT NULL DEFAULT '0',
  `created_on` datetime DEFAULT NULL,
  `updated_on` datetime DEFAULT NULL,
  `expires_on` datetime DEFAULT NULL,
  `last_reminder` datetime DEFAULT NULL,
  `usr_id` int(11) unsigned NOT NULL,
  `confirmed` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `can_agree` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `can_see_others` tinyint(1) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `couple` (`ssel_id`,`usr_id`),
  KEY `ssel_id` (`ssel_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `validate`
--

LOCK TABLES `validate` WRITE;
/*!40000 ALTER TABLE `validate` DISABLE KEYS */;
/*!40000 ALTER TABLE `validate` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `validate_datas`
--

DROP TABLE IF EXISTS `validate_datas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `validate_datas` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `validate_id` int(11) unsigned NOT NULL DEFAULT '0',
  `sselcont_id` int(11) unsigned NOT NULL DEFAULT '0',
  `updated_on` datetime DEFAULT NULL,
  `agreement` tinyint(1) NOT NULL DEFAULT '0',
  `note` text COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `couple` (`validate_id`,`sselcont_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `validate_datas`
--

LOCK TABLES `validate_datas` WRITE;
/*!40000 ALTER TABLE `validate_datas` DISABLE KEYS */;
/*!40000 ALTER TABLE `validate_datas` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Current Database: `db_test`
--

CREATE DATABASE /*!32312 IF NOT EXISTS*/ `db_test` /*!40100 DEFAULT CHARACTER SET latin1 */;

USE `db_test`;

--
-- Table structure for table `clients`
--

DROP TABLE IF EXISTS `clients`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `clients` (
  `site_id` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  UNIQUE KEY `site_id` (`site_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `clients`
--

LOCK TABLES `clients` WRITE;
/*!40000 ALTER TABLE `clients` DISABLE KEYS */;
/*!40000 ALTER TABLE `clients` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `coll`
--

DROP TABLE IF EXISTS `coll`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `coll` (
  `coll_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `htmlname` varchar(50) COLLATE utf8_unicode_ci NOT NULL,
  `asciiname` varchar(50) COLLATE utf8_unicode_ci NOT NULL,
  `prefs` text COLLATE utf8_unicode_ci NOT NULL,
  `logo` longblob NOT NULL,
  `majLogo` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `pub_wm` enum('none','wm','stamp') CHARACTER SET ascii COLLATE ascii_bin NOT NULL DEFAULT 'none',
  PRIMARY KEY (`coll_id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `coll`
--

LOCK TABLES `coll` WRITE;
/*!40000 ALTER TABLE `coll` DISABLE KEYS */;
INSERT INTO `coll` VALUES (1,'test','test','<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n            <baseprefs>\n                <status>0</status>\n                <sugestedValues>\n                </sugestedValues>\n            </baseprefs>','','0000-00-00 00:00:00','none'),(2,'coll2','coll2','<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n            <baseprefs>\n                <status>0</status>\n                <sugestedValues>\n                </sugestedValues>\n            </baseprefs>','','0000-00-00 00:00:00','none');
/*!40000 ALTER TABLE `coll` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `collusr`
--

DROP TABLE IF EXISTS `collusr`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `collusr` (
  `site` char(32) COLLATE utf8_unicode_ci NOT NULL,
  `usr_id` int(11) unsigned NOT NULL DEFAULT '0',
  `coll_id` int(11) unsigned NOT NULL DEFAULT '0',
  `mask_and` bigint(20) unsigned NOT NULL DEFAULT '0',
  `mask_xor` bigint(20) unsigned NOT NULL DEFAULT '0',
  `ord` int(11) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`site`,`usr_id`,`coll_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `collusr`
--

LOCK TABLES `collusr` WRITE;
/*!40000 ALTER TABLE `collusr` DISABLE KEYS */;
INSERT INTO `collusr` VALUES ('c51b8b3501fb45447c359fda7d3784d4',3,1,0,0,0),('c51b8b3501fb45447c359fda7d3784d4',3,2,0,0,1);
/*!40000 ALTER TABLE `collusr` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `emptyw`
--

DROP TABLE IF EXISTS `emptyw`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `emptyw` (
  `emptyw_id` int(11) unsigned NOT NULL DEFAULT '0',
  `word` varchar(64) COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`emptyw_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `emptyw`
--

LOCK TABLES `emptyw` WRITE;
/*!40000 ALTER TABLE `emptyw` DISABLE KEYS */;
/*!40000 ALTER TABLE `emptyw` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `idx`
--

DROP TABLE IF EXISTS `idx`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `idx` (
  `idx_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `c2` char(2) COLLATE utf8_unicode_ci NOT NULL,
  `record_id` int(11) unsigned NOT NULL DEFAULT '0',
  `kword_id` int(11) unsigned NOT NULL DEFAULT '0',
  `iw` int(6) NOT NULL DEFAULT '0',
  `xpath_id` int(11) unsigned NOT NULL DEFAULT '0',
  `hit` varchar(14) COLLATE utf8_unicode_ci NOT NULL,
  `hitstart` int(10) unsigned NOT NULL DEFAULT '0',
  `hitlen` smallint(5) unsigned NOT NULL DEFAULT '0',
  `business` tinyint(1) unsigned NOT NULL DEFAULT '1',
  PRIMARY KEY (`idx_id`),
  KEY `record_id` (`record_id`),
  KEY `kword_id` (`kword_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `idx`
--

LOCK TABLES `idx` WRITE;
/*!40000 ALTER TABLE `idx` DISABLE KEYS */;
/*!40000 ALTER TABLE `idx` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `kword`
--

DROP TABLE IF EXISTS `kword`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `kword` (
  `kword_id` int(11) unsigned NOT NULL DEFAULT '0',
  `k2` char(2) COLLATE utf8_unicode_ci NOT NULL,
  `keyword` varchar(64) COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`kword_id`),
  UNIQUE KEY `keyword` (`keyword`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `kword`
--

LOCK TABLES `kword` WRITE;
/*!40000 ALTER TABLE `kword` DISABLE KEYS */;
/*!40000 ALTER TABLE `kword` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `log`
--

DROP TABLE IF EXISTS `log`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `log` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `date` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `sit_session` int(11) unsigned DEFAULT NULL,
  `user` varchar(32) COLLATE utf8_unicode_ci DEFAULT NULL,
  `site` varchar(64) COLLATE utf8_unicode_ci NOT NULL,
  `usrid` int(11) unsigned DEFAULT NULL,
  `coll_list` text COLLATE utf8_unicode_ci NOT NULL,
  `nav` varchar(64) COLLATE utf8_unicode_ci NOT NULL,
  `version` varchar(64) COLLATE utf8_unicode_ci NOT NULL,
  `os` varchar(64) COLLATE utf8_unicode_ci NOT NULL,
  `res` varchar(16) COLLATE utf8_unicode_ci NOT NULL,
  `ip` varchar(64) COLLATE utf8_unicode_ci NOT NULL,
  `user_agent` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `appli` varbinary(1024) NOT NULL,
  `fonction` varchar(64) COLLATE utf8_unicode_ci DEFAULT NULL,
  `societe` varchar(64) COLLATE utf8_unicode_ci DEFAULT NULL,
  `activite` varchar(200) COLLATE utf8_unicode_ci DEFAULT NULL,
  `pays` varchar(64) COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `site` (`site`),
  KEY `user` (`user`),
  KEY `date` (`date`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `log`
--

LOCK TABLES `log` WRITE;
/*!40000 ALTER TABLE `log` DISABLE KEYS */;
INSERT INTO `log` VALUES (1,'2012-04-27 02:06:34',2,'imprec@gmail.com','c51b8b3501fb45447c359fda7d3784d4',3,'1','Firefox','12.0','Apple','1280x800','127.0.0.1','Mozilla/5.0 (Macintosh; Intel Mac OS X 10.7; rv:12.0) Gecko/20100101 Firefox/12.0','a:1:{i:0;i:3;}','','','','');
/*!40000 ALTER TABLE `log` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `log_docs`
--

DROP TABLE IF EXISTS `log_docs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `log_docs` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `log_id` int(11) unsigned NOT NULL,
  `date` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `record_id` int(11) unsigned NOT NULL,
  `action` enum('push','add','validate','edit','collection','status','print','substit','publish','download','mail','ftp','delete') CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `final` varchar(256) COLLATE utf8_unicode_ci NOT NULL,
  `comment` text COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`id`),
  KEY `log_id` (`log_id`),
  KEY `record_id` (`record_id`),
  KEY `action` (`action`),
  KEY `date` (`date`),
  KEY `final` (`final`(255))
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `log_docs`
--

LOCK TABLES `log_docs` WRITE;
/*!40000 ALTER TABLE `log_docs` DISABLE KEYS */;
/*!40000 ALTER TABLE `log_docs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `log_search`
--

DROP TABLE IF EXISTS `log_search`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `log_search` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `log_id` int(11) unsigned NOT NULL,
  `date` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `search` varchar(256) COLLATE utf8_unicode_ci NOT NULL,
  `results` int(11) unsigned DEFAULT '0',
  `coll_id` text COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`id`),
  KEY `log_id` (`log_id`),
  KEY `search` (`search`(255)),
  KEY `date` (`date`),
  KEY `results` (`results`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `log_search`
--

LOCK TABLES `log_search` WRITE;
/*!40000 ALTER TABLE `log_search` DISABLE KEYS */;
/*!40000 ALTER TABLE `log_search` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `log_thumb`
--

DROP TABLE IF EXISTS `log_thumb`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `log_thumb` (
  `date` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `record_id` int(11) unsigned NOT NULL,
  `site_id` varchar(256) COLLATE utf8_unicode_ci NOT NULL,
  `counter` int(11) unsigned NOT NULL DEFAULT '1',
  KEY `date` (`date`),
  KEY `record_id` (`record_id`),
  KEY `site_id` (`record_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `log_thumb`
--

LOCK TABLES `log_thumb` WRITE;
/*!40000 ALTER TABLE `log_thumb` DISABLE KEYS */;
/*!40000 ALTER TABLE `log_thumb` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `log_view`
--

DROP TABLE IF EXISTS `log_view`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `log_view` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `log_id` int(11) unsigned DEFAULT NULL,
  `date` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `record_id` int(11) unsigned NOT NULL,
  `referrer` varchar(256) COLLATE utf8_unicode_ci NOT NULL,
  `site_id` varchar(256) COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`id`),
  KEY `referrer` (`referrer`(255)),
  KEY `log_id` (`log_id`),
  KEY `date` (`date`),
  KEY `record_id` (`record_id`),
  KEY `site_id` (`site_id`(255))
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `log_view`
--

LOCK TABLES `log_view` WRITE;
/*!40000 ALTER TABLE `log_view` DISABLE KEYS */;
/*!40000 ALTER TABLE `log_view` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `memcached`
--

DROP TABLE IF EXISTS `memcached`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `memcached` (
  `type` char(64) COLLATE utf8_unicode_ci NOT NULL,
  `value` int(11) unsigned NOT NULL,
  `site_id` char(255) COLLATE utf8_unicode_ci NOT NULL,
  UNIQUE KEY `unique` (`type`,`value`,`site_id`),
  KEY `type` (`type`),
  KEY `value` (`value`),
  KEY `site_id` (`site_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `memcached`
--

LOCK TABLES `memcached` WRITE;
/*!40000 ALTER TABLE `memcached` DISABLE KEYS */;
/*!40000 ALTER TABLE `memcached` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `metadatas`
--

DROP TABLE IF EXISTS `metadatas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `metadatas` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `record_id` int(11) unsigned NOT NULL DEFAULT '0',
  `meta_struct_id` int(11) unsigned NOT NULL,
  `value` longtext COLLATE utf8_unicode_ci NOT NULL,
  `VocabularyType` char(16) COLLATE utf8_unicode_ci DEFAULT NULL,
  `VocabularyId` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `meta_struct_id` (`meta_struct_id`),
  KEY `record_id` (`record_id`),
  KEY `index_meta` (`record_id`,`meta_struct_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `metadatas`
--

LOCK TABLES `metadatas` WRITE;
/*!40000 ALTER TABLE `metadatas` DISABLE KEYS */;
/*!40000 ALTER TABLE `metadatas` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `metadatas_structure`
--

DROP TABLE IF EXISTS `metadatas_structure`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `metadatas_structure` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `name` char(128) COLLATE utf8_unicode_ci NOT NULL DEFAULT '0',
  `src` varchar(512) COLLATE utf8_unicode_ci NOT NULL,
  `readonly` int(1) unsigned NOT NULL,
  `required` int(1) unsigned NOT NULL,
  `business` int(1) unsigned NOT NULL,
  `indexable` int(1) unsigned NOT NULL,
  `type` enum('string','text','date','number') COLLATE utf8_unicode_ci NOT NULL,
  `tbranch` varchar(512) COLLATE utf8_unicode_ci NOT NULL,
  `separator` char(12) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `thumbtitle` char(3) COLLATE utf8_unicode_ci DEFAULT NULL,
  `multi` tinyint(1) unsigned NOT NULL,
  `report` tinyint(1) unsigned NOT NULL,
  `sorter` int(3) unsigned NOT NULL,
  `dces_element` char(12) COLLATE utf8_unicode_ci DEFAULT NULL,
  `VocabularyControlType` char(16) COLLATE utf8_unicode_ci DEFAULT NULL,
  `RestrictToVocabularyControl` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`),
  UNIQUE KEY `sorter` (`sorter`),
  KEY `indexable` (`indexable`),
  KEY `readonly` (`readonly`),
  KEY `required` (`required`)
) ENGINE=InnoDB AUTO_INCREMENT=31 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `metadatas_structure`
--

LOCK TABLES `metadatas_structure` WRITE;
/*!40000 ALTER TABLE `metadatas_structure` DISABLE KEYS */;
INSERT INTO `metadatas_structure` VALUES (1,'Object','/rdf:RDF/rdf:Description/IPTC:ObjectName',0,0,0,1,'string','','','0',0,1,0,NULL,NULL,0),(2,'Category','/rdf:RDF/rdf:Description/IPTC:Category',0,0,0,1,'string','','','0',0,1,1,NULL,NULL,0),(3,'SupplCategory','/rdf:RDF/rdf:Description/IPTC:SupplementalCategories',0,0,0,1,'string','','','0',0,1,2,NULL,NULL,0),(4,'Keywords','/rdf:RDF/rdf:Description/IPTC:Keywords',0,0,0,1,'string','',';','0',1,1,3,NULL,NULL,0),(5,'SpecialInstruct','/rdf:RDF/rdf:Description/IPTC:SpecialInstructions',0,0,0,1,'string','','','0',0,1,4,NULL,NULL,0),(6,'Date','/rdf:RDF/rdf:Description/IPTC:DateCreated',0,0,0,1,'date','','','0',0,1,5,NULL,NULL,0),(7,'Byline','/rdf:RDF/rdf:Description/IPTC:By-line',0,0,0,1,'string','','','0',0,1,6,NULL,NULL,0),(8,'BylineTitle','/rdf:RDF/rdf:Description/IPTC:By-lineTitle',0,0,0,1,'string','','','0',0,1,7,NULL,NULL,0),(9,'City','/rdf:RDF/rdf:Description/IPTC:City',0,0,0,1,'string','','','0',0,1,8,NULL,NULL,0),(10,'Province','/rdf:RDF/rdf:Description/IPTC:Province-State',0,0,0,1,'string','','','0',0,1,9,NULL,NULL,0),(11,'Country','/rdf:RDF/rdf:Description/IPTC:Country-PrimaryLocationName',0,0,0,1,'string','','','0',0,1,10,NULL,NULL,0),(12,'OriginalRef','/rdf:RDF/rdf:Description/IPTC:OriginalTransmissionReference',0,0,0,1,'string','','','0',0,1,11,NULL,NULL,0),(13,'Headline','/rdf:RDF/rdf:Description/IPTC:Headline',0,0,0,1,'string','','','0',0,1,12,NULL,NULL,0),(14,'Credit','/rdf:RDF/rdf:Description/IPTC:Credit',0,0,0,1,'string','','','0',0,1,13,NULL,NULL,0),(15,'Source','/rdf:RDF/rdf:Description/IPTC:Source',0,0,0,1,'string','','','0',0,1,14,NULL,NULL,0),(16,'Caption','/rdf:RDF/rdf:Description/IPTC:Caption-Abstract',0,0,0,1,'string','','','0',0,1,15,NULL,NULL,0),(17,'CaptionWriter','/rdf:RDF/rdf:Description/IPTC:Writer-Editor',0,0,0,1,'string','','','0',0,1,16,NULL,NULL,0),(18,'Longitude','/rdf:RDF/rdf:Description/GPS:GPSLongitude',1,0,0,1,'string','','','0',0,1,17,NULL,NULL,0),(19,'Latitude','/rdf:RDF/rdf:Description/GPS:GPSLatitude',1,0,0,1,'string','','','0',0,1,18,NULL,NULL,0),(20,'CameraModel','/rdf:RDF/rdf:Description/IFD0:Model',1,0,0,1,'string','','','0',0,1,19,NULL,NULL,0),(21,'FileName','/rdf:RDF/rdf:Description/PHRASEANET:tf-filename',1,0,0,1,'text','','','0',0,1,20,NULL,NULL,0),(22,'FilePath','/rdf:RDF/rdf:Description/PHRASEANET:tf-filepath',1,0,0,1,'text','','','0',0,1,21,NULL,NULL,0),(23,'Recordid','/rdf:RDF/rdf:Description/PHRASEANET:tf-recordid',1,0,0,1,'number','','','0',0,1,22,NULL,NULL,0),(24,'MimeType','/rdf:RDF/rdf:Description/PHRASEANET:tf-mimetype',1,0,0,1,'text','','','0',0,1,23,NULL,NULL,0),(25,'Size','/rdf:RDF/rdf:Description/PHRASEANET:tf-size',1,0,0,1,'number','','','0',0,1,24,NULL,NULL,0),(26,'Extension','/rdf:RDF/rdf:Description/PHRASEANET:tf-extension',1,0,0,1,'text','','','0',0,1,25,NULL,NULL,0),(27,'Width','/rdf:RDF/rdf:Description/PHRASEANET:tf-width',1,0,0,1,'number','','','0',0,1,26,NULL,NULL,0),(28,'Height','/rdf:RDF/rdf:Description/PHRASEANET:tf-height',1,0,0,1,'number','','','0',0,1,27,NULL,NULL,0),(29,'Bits','/rdf:RDF/rdf:Description/PHRASEANET:tf-bits',1,0,0,1,'number','','','0',0,1,28,NULL,NULL,0),(30,'Channels','/rdf:RDF/rdf:Description/PHRASEANET:tf-channels',1,0,0,1,'number','','','0',0,1,29,NULL,NULL,0);
/*!40000 ALTER TABLE `metadatas_structure` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `permalinks`
--

DROP TABLE IF EXISTS `permalinks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `permalinks` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `subdef_id` int(11) unsigned NOT NULL,
  `token` char(64) COLLATE utf8_unicode_ci NOT NULL,
  `label` char(64) COLLATE utf8_unicode_ci NOT NULL,
  `activated` tinyint(1) unsigned NOT NULL DEFAULT '1',
  `created_on` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `last_modified` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`id`),
  UNIQUE KEY `subdef_id` (`subdef_id`),
  UNIQUE KEY `token` (`token`),
  KEY `activated` (`activated`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `permalinks`
--

LOCK TABLES `permalinks` WRITE;
/*!40000 ALTER TABLE `permalinks` DISABLE KEYS */;
/*!40000 ALTER TABLE `permalinks` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `pref`
--

DROP TABLE IF EXISTS `pref`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `pref` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `prop` char(64) COLLATE utf8_unicode_ci NOT NULL,
  `value` longtext COLLATE utf8_unicode_ci NOT NULL,
  `locale` char(8) COLLATE utf8_unicode_ci DEFAULT NULL,
  `updated_on` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `created_on` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`id`),
  UNIQUE KEY `UNIQUE` (`prop`,`locale`),
  KEY `prop` (`prop`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pref`
--

LOCK TABLES `pref` WRITE;
/*!40000 ALTER TABLE `pref` DISABLE KEYS */;
INSERT INTO `pref` VALUES (1,'thesaurus','','','0000-00-00 00:00:00','2012-04-27 02:06:31'),(2,'structure','<?xml version=\"1.0\" encoding=\"UTF-8\" standalone=\"yes\"?>\n<record modification_date=\"20120427020634\">\n  <path>/tmp/db_test/documents</path>\n  <subdefs>\n    <subdefgroup name=\"image\">\n      <subdef class=\"preview\" name=\"preview\" downloadable=\"true\">\n        <path>/tmp/db_test/subdefs</path>\n        <size>800</size>\n        <method>resample</method>\n        <dpi>72</dpi>\n        <strip>no</strip>\n        <quality>75</quality>\n        <meta>yes</meta>\n        <baseurl/>\n        <mediatype>image</mediatype>\n        <label lang=\"fr\">Prévisualisation</label>\n        <label lang=\"en\">Preview</label>\n      </subdef>\n      <subdef class=\"thumbnail\" name=\"thumbnail\" downloadable=\"true\">\n        <path>/tmp/db_test/subdefs</path>\n        <size>200</size>\n        <method>resample</method>\n        <dpi>72</dpi>\n        <strip>yes</strip>\n        <quality>75</quality>\n        <meta>no</meta>\n        <mediatype>image</mediatype>\n        <baseurl>web//db_test/subdefs</baseurl>\n        <label lang=\"fr\">Imagette</label>\n        <label lang=\"en\">Thumbnail</label>\n      </subdef>\n    </subdefgroup>\n    <subdefgroup name=\"video\">\n      <subdef class=\"preview\" name=\"preview\" downloadable=\"true\">\n        <path>/tmp/db_test/subdefs</path>\n        <size>800</size>\n        <mediatype>video</mediatype>\n        <writeDatas>yes</writeDatas>\n        <baseurl/>\n        <acodec>faac</acodec>\n        <vcodec>libx264</vcodec>\n        <bitrate>1000</bitrate>\n        <threads>8</threads>\n        <fps>15</fps>\n        <label lang=\"fr\">Prévisualisation</label>\n        <label lang=\"en\">Preview</label>\n      </subdef>\n      <subdef class=\"thumbnail\" name=\"thumbnailgif\" downloadable=\"true\">\n        <path>/tmp/db_test/subdefs</path>\n        <size>200</size>\n        <mediatype>gif</mediatype>\n        <delay>500</delay>\n        <writeDatas>no</writeDatas>\n        <baseurl>web//db_test/subdefs</baseurl>\n        <label lang=\"fr\">Animation GIF</label>\n        <label lang=\"en\">GIF Animation</label>\n      </subdef>\n      <subdef class=\"thumbnail\" name=\"thumbnail\" downloadable=\"true\">\n        <path>/tmp/db_test/subdefs</path>\n        <size>200</size>\n        <mediatype>image</mediatype>\n        <writeDatas>no</writeDatas>\n        <baseurl>web//db_test/subdefs</baseurl>\n        <label lang=\"fr\">Imagette</label>\n        <label lang=\"en\">Thumbnail</label>\n      </subdef>\n    </subdefgroup>\n    <subdefgroup name=\"audio\">\n      <subdef class=\"preview\" name=\"preview\" downloadable=\"true\">\n        <path>/tmp/db_test/subdefs</path>\n        <mediatype>audio</mediatype>\n        <writeDatas>yes</writeDatas>\n        <baseurl/>\n        <label lang=\"fr\">Prévisualisation</label>\n        <label lang=\"en\">Preview</label>\n      </subdef>\n      <subdef class=\"thumbnail\" name=\"thumbnail\" downloadable=\"true\">\n        <path>/tmp/db_test/subdefs</path>\n        <mediatype>image</mediatype>\n        <writeDatas>no</writeDatas>\n        <baseurl>web//db_test/subdefs</baseurl>\n        <label lang=\"fr\">Imagette</label>\n        <label lang=\"en\">Thumbnail</label>\n      </subdef>\n    </subdefgroup>\n    <subdefgroup name=\"document\">\n      <subdef class=\"preview\" name=\"preview\" downloadable=\"false\">\n        <path>/tmp/db_test/subdefs</path>\n        <mediatype>flexpaper</mediatype>\n        <writeDatas>no</writeDatas>\n        <baseurl/>\n        <label lang=\"fr\">Prévisualisation</label>\n        <label lang=\"en\">Preview</label>\n      </subdef>\n      <subdef class=\"thumbnail\" name=\"thumbnail\" downloadable=\"false\">\n        <path>/tmp/db_test/subdefs</path>\n        <mediatype>image</mediatype>\n        <method>resample</method>\n        <dpi>72</dpi>\n        <size>200</size>\n        <writeDatas>no</writeDatas>\n        <baseurl>web//db_test/subdefs</baseurl>\n        <label lang=\"fr\">Imagette</label>\n        <label lang=\"en\">Thumbnail</label>\n      </subdef>\n    </subdefgroup>\n    <subdefgroup name=\"flash\">\n      <subdef class=\"preview\" name=\"preview\" downloadable=\"false\">\n        <path>/tmp/db_test/subdefs</path>\n        <mediatype>image</mediatype>\n        <size>200</size>\n        <writeDatas>no</writeDatas>\n        <method>resample</method>\n        <dpi>72</dpi>\n        <baseurl/>\n        <label lang=\"fr\">Prévisualisation</label>\n        <label lang=\"en\">Preview</label>\n      </subdef>\n      <subdef class=\"thumbnail\" name=\"thumbnail\" downloadable=\"false\">\n        <path>/tmp/db_test/subdefs</path>\n        <mediatype>image</mediatype>\n        <writeDatas>no</writeDatas>\n        <method>resample</method>\n        <dpi>72</dpi>\n        <baseurl>web//db_test/subdefs</baseurl>\n        <label lang=\"fr\">Imagette</label>\n        <label lang=\"en\">Thumbnail</label>\n      </subdef>\n    </subdefgroup>\n  </subdefs>\n  <description>\n    <!-- 2:5 -->\n    <!-- 2:15 -->\n    <!-- 2:20 -->\n    <!-- 2:25 -->\n    <!-- 2:40 -->\n    <!-- 2:55 -->\n    <!-- 2:80 -->\n    <!-- 2:85 -->\n    <!-- 2:90 -->\n    <!-- 2:95 -->\n    <!-- 2:101 -->\n    <!-- 2:103 -->\n    <!-- 2:105 -->\n    <!-- 2:110 -->\n    <!-- 2:115 -->\n    <!-- 2:120 -->\n    <!-- 2:122 -->\n    <!-- 2:25 -->\n    <!-- 2:25 -->\n    <!-- 2:25 -->\n    <!-- Technical Fields -->\n    <Object src=\"/rdf:RDF/rdf:Description/IPTC:ObjectName\" index=\"1\" readonly=\"0\" required=\"0\" multi=\"0\" report=\"1\" type=\"string\" tbranch=\"\" thumbtitle=\"0\" meta_id=\"1\"/>\n    <Category src=\"/rdf:RDF/rdf:Description/IPTC:Category\" index=\"1\" readonly=\"0\" required=\"0\" multi=\"0\" report=\"1\" type=\"string\" tbranch=\"\" thumbtitle=\"0\" meta_id=\"2\"/>\n    <SupplCategory src=\"/rdf:RDF/rdf:Description/IPTC:SupplementalCategories\" index=\"1\" readonly=\"0\" required=\"0\" multi=\"0\" report=\"1\" type=\"string\" tbranch=\"\" thumbtitle=\"0\" meta_id=\"3\"/>\n    <Keywords src=\"/rdf:RDF/rdf:Description/IPTC:Keywords\" index=\"1\" readonly=\"0\" required=\"0\" multi=\"1\" report=\"1\" type=\"string\" tbranch=\"\" separator=\";\" thumbtitle=\"0\" meta_id=\"4\"/>\n    <SpecialInstruct src=\"/rdf:RDF/rdf:Description/IPTC:SpecialInstructions\" index=\"1\" readonly=\"0\" required=\"0\" multi=\"0\" report=\"1\" type=\"string\" tbranch=\"\" thumbtitle=\"0\" meta_id=\"5\"/>\n    <Date src=\"/rdf:RDF/rdf:Description/IPTC:DateCreated\" index=\"1\" readonly=\"0\" required=\"0\" multi=\"0\" report=\"1\" type=\"date\" tbranch=\"\" thumbtitle=\"0\" meta_id=\"6\"/>\n    <Byline src=\"/rdf:RDF/rdf:Description/IPTC:By-line\" index=\"1\" readonly=\"0\" required=\"0\" multi=\"0\" report=\"1\" type=\"string\" tbranch=\"\" thumbtitle=\"0\" meta_id=\"7\"/>\n    <BylineTitle src=\"/rdf:RDF/rdf:Description/IPTC:By-lineTitle\" index=\"1\" readonly=\"0\" required=\"0\" multi=\"0\" report=\"1\" type=\"string\" tbranch=\"\" thumbtitle=\"0\" meta_id=\"8\"/>\n    <City src=\"/rdf:RDF/rdf:Description/IPTC:City\" index=\"1\" readonly=\"0\" required=\"0\" multi=\"0\" report=\"1\" type=\"string\" tbranch=\"\" thumbtitle=\"0\" meta_id=\"9\"/>\n    <Province src=\"/rdf:RDF/rdf:Description/IPTC:Province-State\" index=\"1\" readonly=\"0\" required=\"0\" multi=\"0\" report=\"1\" type=\"string\" tbranch=\"\" thumbtitle=\"0\" meta_id=\"10\"/>\n    <Country src=\"/rdf:RDF/rdf:Description/IPTC:Country-PrimaryLocationName\" index=\"1\" readonly=\"0\" required=\"0\" multi=\"0\" report=\"1\" type=\"string\" tbranch=\"\" thumbtitle=\"0\" meta_id=\"11\"/>\n    <OriginalRef src=\"/rdf:RDF/rdf:Description/IPTC:OriginalTransmissionReference\" index=\"1\" readonly=\"0\" required=\"0\" multi=\"0\" report=\"1\" type=\"string\" tbranch=\"\" thumbtitle=\"0\" meta_id=\"12\"/>\n    <Headline src=\"/rdf:RDF/rdf:Description/IPTC:Headline\" index=\"1\" readonly=\"0\" required=\"0\" multi=\"0\" report=\"1\" type=\"string\" tbranch=\"\" thumbtitle=\"0\" meta_id=\"13\"/>\n    <Credit src=\"/rdf:RDF/rdf:Description/IPTC:Credit\" index=\"1\" readonly=\"0\" required=\"0\" multi=\"0\" report=\"1\" type=\"string\" tbranch=\"\" thumbtitle=\"0\" meta_id=\"14\"/>\n    <Source src=\"/rdf:RDF/rdf:Description/IPTC:Source\" index=\"1\" readonly=\"0\" required=\"0\" multi=\"0\" report=\"1\" type=\"string\" tbranch=\"\" thumbtitle=\"0\" meta_id=\"15\"/>\n    <Caption src=\"/rdf:RDF/rdf:Description/IPTC:Caption-Abstract\" index=\"1\" readonly=\"0\" required=\"0\" multi=\"0\" report=\"1\" type=\"string\" tbranch=\"\" thumbtitle=\"0\" meta_id=\"16\"/>\n    <CaptionWriter src=\"/rdf:RDF/rdf:Description/IPTC:Writer-Editor\" index=\"1\" readonly=\"0\" required=\"0\" multi=\"0\" report=\"1\" type=\"string\" tbranch=\"\" thumbtitle=\"0\" meta_id=\"17\"/>\n    <Longitude src=\"/rdf:RDF/rdf:Description/GPS:GPSLongitude\" index=\"1\" readonly=\"1\" required=\"0\" multi=\"0\" report=\"1\" type=\"string\" tbranch=\"\" thumbtitle=\"0\" meta_id=\"18\"/>\n    <Latitude src=\"/rdf:RDF/rdf:Description/GPS:GPSLatitude\" index=\"1\" readonly=\"1\" required=\"0\" multi=\"0\" report=\"1\" type=\"string\" tbranch=\"\" thumbtitle=\"0\" meta_id=\"19\"/>\n    <CameraModel src=\"/rdf:RDF/rdf:Description/IFD0:Model\" index=\"1\" readonly=\"1\" required=\"0\" multi=\"0\" report=\"1\" type=\"string\" tbranch=\"\" thumbtitle=\"0\" meta_id=\"20\"/>\n    <FileName src=\"/rdf:RDF/rdf:Description/PHRASEANET:tf-filename\" index=\"1\" readonly=\"1\" required=\"0\" multi=\"0\" report=\"1\" type=\"text\" tbranch=\"\" thumbtitle=\"0\" meta_id=\"21\"/>\n    <FilePath src=\"/rdf:RDF/rdf:Description/PHRASEANET:tf-filepath\" index=\"1\" readonly=\"1\" required=\"0\" multi=\"0\" report=\"1\" type=\"text\" tbranch=\"\" thumbtitle=\"0\" meta_id=\"22\"/>\n    <Recordid src=\"/rdf:RDF/rdf:Description/PHRASEANET:tf-recordid\" index=\"1\" readonly=\"1\" required=\"0\" multi=\"0\" report=\"1\" type=\"number\" tbranch=\"\" thumbtitle=\"0\" meta_id=\"23\"/>\n    <MimeType src=\"/rdf:RDF/rdf:Description/PHRASEANET:tf-mimetype\" index=\"1\" readonly=\"1\" required=\"0\" multi=\"0\" report=\"1\" type=\"text\" tbranch=\"\" thumbtitle=\"0\" meta_id=\"24\"/>\n    <Size src=\"/rdf:RDF/rdf:Description/PHRASEANET:tf-size\" index=\"1\" readonly=\"1\" required=\"0\" multi=\"0\" report=\"1\" type=\"number\" tbranch=\"\" thumbtitle=\"0\" meta_id=\"25\"/>\n    <Extension src=\"/rdf:RDF/rdf:Description/PHRASEANET:tf-extension\" index=\"1\" readonly=\"1\" required=\"0\" multi=\"0\" report=\"1\" type=\"text\" tbranch=\"\" thumbtitle=\"0\" meta_id=\"26\"/>\n    <Width src=\"/rdf:RDF/rdf:Description/PHRASEANET:tf-width\" index=\"1\" readonly=\"1\" required=\"0\" multi=\"0\" report=\"1\" type=\"number\" tbranch=\"\" thumbtitle=\"0\" meta_id=\"27\"/>\n    <Height src=\"/rdf:RDF/rdf:Description/PHRASEANET:tf-height\" index=\"1\" readonly=\"1\" required=\"0\" multi=\"0\" report=\"1\" type=\"number\" tbranch=\"\" thumbtitle=\"0\" meta_id=\"28\"/>\n    <Bits src=\"/rdf:RDF/rdf:Description/PHRASEANET:tf-bits\" index=\"1\" readonly=\"1\" required=\"0\" multi=\"0\" report=\"1\" type=\"number\" tbranch=\"\" thumbtitle=\"0\" meta_id=\"29\"/>\n    <Channels src=\"/rdf:RDF/rdf:Description/PHRASEANET:tf-channels\" index=\"1\" readonly=\"1\" required=\"0\" multi=\"0\" report=\"1\" type=\"number\" tbranch=\"\" thumbtitle=\"0\" meta_id=\"30\"/>\n  </description>\n  <statbits>\n    <bit n=\"4\">Online</bit>\n  </statbits>\n</record>\n','','2012-04-27 02:06:34','2012-04-27 02:06:31'),(3,'cterms','','','0000-00-00 00:00:00','2012-04-27 02:06:31'),(4,'indexes','1','','2012-04-27 02:06:31','2012-04-27 02:06:31'),(5,'ToU','','fr_FR','0000-00-00 00:00:00','2012-04-27 02:06:31'),(6,'ToU','','ar_SA','0000-00-00 00:00:00','2012-04-27 02:06:31'),(7,'ToU','','de_DE','0000-00-00 00:00:00','2012-04-27 02:06:31'),(8,'ToU','','en_GB','0000-00-00 00:00:00','2012-04-27 02:06:31'),(9,'version','3.7.0.0.a2','','2012-04-27 02:06:33','0000-00-00 00:00:00');
/*!40000 ALTER TABLE `pref` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `prop`
--

DROP TABLE IF EXISTS `prop`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `prop` (
  `prop_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `record_id` int(11) unsigned NOT NULL DEFAULT '0',
  `xpath_id` int(11) unsigned NOT NULL DEFAULT '0',
  `name` char(32) CHARACTER SET ascii NOT NULL,
  `value` char(100) COLLATE utf8_unicode_ci NOT NULL,
  `type` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `business` tinyint(1) unsigned NOT NULL DEFAULT '1',
  PRIMARY KEY (`prop_id`),
  KEY `xpath_id` (`xpath_id`),
  KEY `record_id` (`record_id`),
  KEY `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `prop`
--

LOCK TABLES `prop` WRITE;
/*!40000 ALTER TABLE `prop` DISABLE KEYS */;
/*!40000 ALTER TABLE `prop` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `record`
--

DROP TABLE IF EXISTS `record`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `record` (
  `coll_id` int(11) unsigned NOT NULL DEFAULT '0',
  `record_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `parent_record_id` int(11) unsigned NOT NULL DEFAULT '0',
  `status` bigint(20) unsigned NOT NULL DEFAULT '0',
  `sha256` char(64) CHARACTER SET ascii COLLATE ascii_bin DEFAULT NULL,
  `uuid` char(36) CHARACTER SET ascii DEFAULT NULL,
  `xml` longblob NOT NULL,
  `moddate` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `credate` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `work` int(1) unsigned NOT NULL DEFAULT '0',
  `mime` char(40) COLLATE utf8_unicode_ci DEFAULT NULL,
  `type` enum('image','video','audio','document','flash','unknown') CHARACTER SET ascii COLLATE ascii_bin NOT NULL DEFAULT 'unknown',
  `jeton` int(11) unsigned NOT NULL DEFAULT '0',
  `bitly` char(10) COLLATE utf8_unicode_ci DEFAULT NULL,
  `originalname` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`record_id`),
  KEY `coll_id` (`coll_id`),
  KEY `type` (`type`),
  KEY `record_status` (`status`),
  KEY `jeton` (`jeton`),
  KEY `sha256` (`sha256`),
  KEY `uuid` (`uuid`),
  KEY `bitly` (`bitly`),
  KEY `parent_record_id` (`parent_record_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `record`
--

LOCK TABLES `record` WRITE;
/*!40000 ALTER TABLE `record` DISABLE KEYS */;
/*!40000 ALTER TABLE `record` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `regroup`
--

DROP TABLE IF EXISTS `regroup`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `regroup` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `rid_parent` int(11) unsigned NOT NULL DEFAULT '0',
  `rid_child` int(11) unsigned NOT NULL DEFAULT '0',
  `dateadd` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `ord` int(11) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `lien` (`rid_child`,`rid_parent`),
  KEY `rid_parent` (`rid_parent`),
  KEY `rid_child` (`rid_child`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `regroup`
--

LOCK TABLES `regroup` WRITE;
/*!40000 ALTER TABLE `regroup` DISABLE KEYS */;
/*!40000 ALTER TABLE `regroup` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `status`
--

DROP TABLE IF EXISTS `status`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `status` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `record_id` int(11) unsigned NOT NULL DEFAULT '0',
  `name` int(2) unsigned NOT NULL,
  `value` tinyint(1) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique` (`record_id`,`name`),
  KEY `value` (`value`),
  KEY `record_id` (`record_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `status`
--

LOCK TABLES `status` WRITE;
/*!40000 ALTER TABLE `status` DISABLE KEYS */;
/*!40000 ALTER TABLE `status` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `subdef`
--

DROP TABLE IF EXISTS `subdef`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `subdef` (
  `subdef_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `record_id` int(11) unsigned NOT NULL DEFAULT '0',
  `name` char(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `path` char(255) COLLATE utf8_unicode_ci NOT NULL,
  `file` char(255) COLLATE utf8_unicode_ci NOT NULL,
  `baseurl` char(255) COLLATE utf8_unicode_ci NOT NULL,
  `width` int(10) unsigned NOT NULL DEFAULT '0',
  `height` int(10) unsigned NOT NULL DEFAULT '0',
  `mime` char(64) COLLATE utf8_unicode_ci NOT NULL,
  `size` bigint(20) unsigned NOT NULL DEFAULT '0',
  `substit` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `dispatched` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `created_on` datetime NOT NULL,
  `updated_on` datetime NOT NULL,
  PRIMARY KEY (`subdef_id`),
  UNIQUE KEY `unicite` (`record_id`,`name`),
  KEY `name` (`name`),
  KEY `record_id` (`record_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `subdef`
--

LOCK TABLES `subdef` WRITE;
/*!40000 ALTER TABLE `subdef` DISABLE KEYS */;
/*!40000 ALTER TABLE `subdef` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `suggest`
--

DROP TABLE IF EXISTS `suggest`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `suggest` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `keyword` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `trigrams` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `freq` int(11) unsigned NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `suggest`
--

LOCK TABLES `suggest` WRITE;
/*!40000 ALTER TABLE `suggest` DISABLE KEYS */;
/*!40000 ALTER TABLE `suggest` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `technical_datas`
--

DROP TABLE IF EXISTS `technical_datas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `technical_datas` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `record_id` int(11) unsigned NOT NULL DEFAULT '0',
  `name` char(32) CHARACTER SET ascii NOT NULL,
  `value` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unicite` (`record_id`,`name`),
  KEY `record_index` (`record_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `technical_datas`
--

LOCK TABLES `technical_datas` WRITE;
/*!40000 ALTER TABLE `technical_datas` DISABLE KEYS */;
/*!40000 ALTER TABLE `technical_datas` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `thit`
--

DROP TABLE IF EXISTS `thit`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `thit` (
  `thit_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `record_id` int(11) unsigned NOT NULL DEFAULT '0',
  `xpath_id` int(11) unsigned NOT NULL DEFAULT '0',
  `name` char(32) CHARACTER SET ascii NOT NULL,
  `value` char(100) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `hitstart` int(10) unsigned NOT NULL DEFAULT '0',
  `hitlen` smallint(5) unsigned NOT NULL DEFAULT '0',
  `business` tinyint(1) unsigned NOT NULL DEFAULT '1',
  PRIMARY KEY (`thit_id`),
  KEY `value` (`value`),
  KEY `name` (`name`),
  KEY `xpath_id` (`xpath_id`),
  KEY `record_id` (`record_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `thit`
--

LOCK TABLES `thit` WRITE;
/*!40000 ALTER TABLE `thit` DISABLE KEYS */;
/*!40000 ALTER TABLE `thit` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `uids`
--

DROP TABLE IF EXISTS `uids`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `uids` (
  `uid` int(11) unsigned NOT NULL DEFAULT '1',
  `name` char(16) COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `uids`
--

LOCK TABLES `uids` WRITE;
/*!40000 ALTER TABLE `uids` DISABLE KEYS */;
INSERT INTO `uids` VALUES (1,'KEYWORDS'),(1,'XPATH');
/*!40000 ALTER TABLE `uids` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `xpath`
--

DROP TABLE IF EXISTS `xpath`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `xpath` (
  `xpath_id` int(11) unsigned NOT NULL DEFAULT '0',
  `xpath` char(150) CHARACTER SET ascii NOT NULL,
  PRIMARY KEY (`xpath_id`),
  UNIQUE KEY `xpath` (`xpath`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `xpath`
--

LOCK TABLES `xpath` WRITE;
/*!40000 ALTER TABLE `xpath` DISABLE KEYS */;
/*!40000 ALTER TABLE `xpath` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2012-04-27  2:07:51
