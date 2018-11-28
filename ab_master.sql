-- MySQL dump 10.16  Distrib 10.1.12-MariaDB, for debian-linux-gnu (x86_64)
--
-- Host: localhost    Database: ab_master
-- ------------------------------------------------------
-- Server version	10.1.12-MariaDB-1~trusty

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
-- Table structure for table `AggregateTokens`
--

DROP TABLE IF EXISTS `AggregateTokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `AggregateTokens` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `value` varchar(64) COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_4232BC51A76ED395` (`user_id`),
  CONSTRAINT `FK_4232BC51A76ED395` FOREIGN KEY (`user_id`) REFERENCES `Users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `AggregateTokens`
--

LOCK TABLES `AggregateTokens` WRITE;
/*!40000 ALTER TABLE `AggregateTokens` DISABLE KEYS */;
/*!40000 ALTER TABLE `AggregateTokens` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ApiAccounts`
--

DROP TABLE IF EXISTS `ApiAccounts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ApiAccounts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `application_id` int(11) NOT NULL,
  `revoked` tinyint(1) NOT NULL DEFAULT '0',
  `api_version` varchar(16) COLLATE utf8_unicode_ci NOT NULL,
  `created` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `api_account_user_id` (`user_id`),
  KEY `api_account_application_id` (`application_id`),
  CONSTRAINT `FK_2C54E6373E030ACD` FOREIGN KEY (`application_id`) REFERENCES `ApiApplications` (`id`),
  CONSTRAINT `FK_2C54E637A76ED395` FOREIGN KEY (`user_id`) REFERENCES `Users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ApiAccounts`
--

LOCK TABLES `ApiAccounts` WRITE;
/*!40000 ALTER TABLE `ApiAccounts` DISABLE KEYS */;
/*!40000 ALTER TABLE `ApiAccounts` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ApiApplications`
--

DROP TABLE IF EXISTS `ApiApplications`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ApiApplications` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `creator_id` int(11) DEFAULT NULL,
  `type` varchar(128) COLLATE utf8_unicode_ci NOT NULL,
  `name` varchar(128) COLLATE utf8_unicode_ci NOT NULL,
  `description` longtext COLLATE utf8_unicode_ci NOT NULL,
  `website` varchar(128) COLLATE utf8_unicode_ci NOT NULL,
  `created` datetime NOT NULL,
  `updated` datetime NOT NULL,
  `client_id` varchar(128) COLLATE utf8_unicode_ci NOT NULL,
  `client_secret` varchar(128) COLLATE utf8_unicode_ci NOT NULL,
  `nonce` varchar(64) COLLATE utf8_unicode_ci NOT NULL,
  `redirect_uri` varchar(128) COLLATE utf8_unicode_ci NOT NULL,
  `activated` tinyint(1) NOT NULL,
  `grant_password` tinyint(1) NOT NULL,
  `webhook_url` varchar(1024) COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `client_id` (`client_id`),
  KEY `api_application_creator_id` (`creator_id`),
  CONSTRAINT `FK_53F7BBE661220EA6` FOREIGN KEY (`creator_id`) REFERENCES `Users` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ApiApplications`
--

LOCK TABLES `ApiApplications` WRITE;
/*!40000 ALTER TABLE `ApiApplications` DISABLE KEYS */;
INSERT INTO `ApiApplications` VALUES (1,NULL,'desktop','phraseanet-navigator','','http://www.phraseanet.com','2018-10-22 06:04:29','2018-10-22 06:04:29','\\alchemy\\phraseanet\\id\\4f981093aebb66.06844599','\\alchemy\\phraseanet\\secret\\4f9810d4b09799.51622662','RRMl7FZhtBR8C7j6jipLAGbIYj8t4+bxU9Pjho1C/fXdC9+pxk6G+zKfrzWdmviE','urn:ietf:wg:oauth:2.0:oob',1,1,NULL),(2,NULL,'desktop','office-plugin','','http://www.phraseanet.com','2018-10-22 06:04:30','2018-10-22 06:04:30','\\alchemy\\phraseanet\\id\\999585175b5fbb6e140efbdfea86c561','\\alchemy\\phraseanet\\secret\\6d53d0bc74e6c8c1a325541f71da1ea5','2DbK72rpdtHqyTCsjxFHz6txxKU/LldUVfOoR7Ftwy78m5nUJnJ1OYrdgA/RN+4b','urn:ietf:wg:oauth:2.0:oob',1,1,NULL),(3,NULL,'desktop','adobe_cc-plugin','','http://www.phraseanet.com','2018-10-22 06:04:30','2018-10-22 06:04:30','\\alchemy\\phraseanet\\id\\YZWUTqNyq8ObG4b0o4sp7NX50ScudqiV','\\alchemy\\phraseanet\\secret\\nEpZd3O6Mk2ijQWiXsm7wPNKnFrbv7MO','cP+oGyupKpIuPIEDskcvx7z1JSGINNOCF1qvrxQEC/7NqMVoz2A0LWAvvmp0BjQm','urn:ietf:wg:oauth:2.0:oob',1,1,NULL);
/*!40000 ALTER TABLE `ApiApplications` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ApiLogs`
--

DROP TABLE IF EXISTS `ApiLogs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ApiLogs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `account_id` int(11) NOT NULL,
  `route` varchar(128) COLLATE utf8_unicode_ci DEFAULT NULL,
  `method` varchar(16) COLLATE utf8_unicode_ci DEFAULT NULL,
  `created` datetime NOT NULL,
  `status_code` int(11) DEFAULT NULL,
  `format` varchar(64) COLLATE utf8_unicode_ci DEFAULT NULL,
  `resource` varchar(64) COLLATE utf8_unicode_ci DEFAULT NULL,
  `general` varchar(64) COLLATE utf8_unicode_ci DEFAULT NULL,
  `aspect` varchar(64) COLLATE utf8_unicode_ci DEFAULT NULL,
  `action` varchar(64) COLLATE utf8_unicode_ci DEFAULT NULL,
  `error_code` int(11) DEFAULT NULL,
  `error_message` longtext COLLATE utf8_unicode_ci,
  PRIMARY KEY (`id`),
  KEY `api_log_account_id` (`account_id`),
  CONSTRAINT `FK_91E90F309B6B5FBA` FOREIGN KEY (`account_id`) REFERENCES `ApiAccounts` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ApiLogs`
--

LOCK TABLES `ApiLogs` WRITE;
/*!40000 ALTER TABLE `ApiLogs` DISABLE KEYS */;
/*!40000 ALTER TABLE `ApiLogs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ApiOauthCodes`
--

DROP TABLE IF EXISTS `ApiOauthCodes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ApiOauthCodes` (
  `code` varchar(128) COLLATE utf8_unicode_ci NOT NULL,
  `account_id` int(11) NOT NULL,
  `redirect_uri` varchar(128) COLLATE utf8_unicode_ci NOT NULL,
  `expires` int(11) NOT NULL,
  `scope` varchar(128) COLLATE utf8_unicode_ci DEFAULT NULL,
  `created` datetime NOT NULL,
  `updated` datetime NOT NULL,
  PRIMARY KEY (`code`),
  KEY `api_oauth_code_account_id` (`account_id`),
  CONSTRAINT `FK_BE6B11809B6B5FBA` FOREIGN KEY (`account_id`) REFERENCES `ApiAccounts` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ApiOauthCodes`
--

LOCK TABLES `ApiOauthCodes` WRITE;
/*!40000 ALTER TABLE `ApiOauthCodes` DISABLE KEYS */;
/*!40000 ALTER TABLE `ApiOauthCodes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ApiOauthRefreshTokens`
--

DROP TABLE IF EXISTS `ApiOauthRefreshTokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ApiOauthRefreshTokens` (
  `refresh_token` varchar(128) COLLATE utf8_unicode_ci NOT NULL,
  `account_id` int(11) NOT NULL,
  `expires` int(11) NOT NULL,
  `scope` varchar(128) COLLATE utf8_unicode_ci DEFAULT NULL,
  `created` datetime NOT NULL,
  `updated` datetime NOT NULL,
  PRIMARY KEY (`refresh_token`),
  KEY `api_oauth_refresh_token_account_id` (`account_id`),
  CONSTRAINT `FK_7DA42A5A9B6B5FBA` FOREIGN KEY (`account_id`) REFERENCES `ApiAccounts` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ApiOauthRefreshTokens`
--

LOCK TABLES `ApiOauthRefreshTokens` WRITE;
/*!40000 ALTER TABLE `ApiOauthRefreshTokens` DISABLE KEYS */;
/*!40000 ALTER TABLE `ApiOauthRefreshTokens` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ApiOauthTokens`
--

DROP TABLE IF EXISTS `ApiOauthTokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ApiOauthTokens` (
  `oauth_token` varchar(128) COLLATE utf8_unicode_ci NOT NULL,
  `account_id` int(11) NOT NULL,
  `session_id` int(11) DEFAULT NULL,
  `expires` int(11) DEFAULT NULL,
  `last_used` datetime NOT NULL,
  `scope` varchar(128) COLLATE utf8_unicode_ci DEFAULT NULL,
  `created` datetime NOT NULL,
  `updated` datetime NOT NULL,
  PRIMARY KEY (`oauth_token`),
  KEY `api_oauth_token_account_id` (`account_id`),
  KEY `api_oauth_token_session_id` (`session_id`),
  CONSTRAINT `FK_4FD469539B6B5FBA` FOREIGN KEY (`account_id`) REFERENCES `ApiAccounts` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ApiOauthTokens`
--

LOCK TABLES `ApiOauthTokens` WRITE;
/*!40000 ALTER TABLE `ApiOauthTokens` DISABLE KEYS */;
/*!40000 ALTER TABLE `ApiOauthTokens` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `AuthFailures`
--

DROP TABLE IF EXISTS `AuthFailures`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `AuthFailures` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(128) COLLATE utf8_unicode_ci NOT NULL,
  `ip` varchar(128) COLLATE utf8_unicode_ci DEFAULT NULL,
  `locked` tinyint(1) NOT NULL,
  `created` datetime NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `AuthFailures`
--

LOCK TABLES `AuthFailures` WRITE;
/*!40000 ALTER TABLE `AuthFailures` DISABLE KEYS */;
/*!40000 ALTER TABLE `AuthFailures` ENABLE KEYS */;
UNLOCK TABLES;

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
  CONSTRAINT `FK_C0B7ECB71BE1FB52` FOREIGN KEY (`basket_id`) REFERENCES `Baskets` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
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
  `user_id` int(11) NOT NULL,
  `pusher_id` int(11) DEFAULT NULL,
  `name` varchar(128) COLLATE utf8_unicode_ci NOT NULL,
  `description` longtext COLLATE utf8_unicode_ci,
  `is_read` tinyint(1) NOT NULL DEFAULT '0',
  `archived` tinyint(1) NOT NULL DEFAULT '0',
  `created` datetime NOT NULL,
  `updated` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_13461873A76ED395` (`user_id`),
  KEY `IDX_13461873C2D98306` (`pusher_id`),
  CONSTRAINT `FK_13461873A76ED395` FOREIGN KEY (`user_id`) REFERENCES `Users` (`id`),
  CONSTRAINT `FK_13461873C2D98306` FOREIGN KEY (`pusher_id`) REFERENCES `Users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `Baskets`
--

LOCK TABLES `Baskets` WRITE;
/*!40000 ALTER TABLE `Baskets` DISABLE KEYS */;
/*!40000 ALTER TABLE `Baskets` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `FeedEntries`
--

DROP TABLE IF EXISTS `FeedEntries`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `FeedEntries` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `publisher_id` int(11) DEFAULT NULL,
  `feed_id` int(11) DEFAULT NULL,
  `title` varchar(128) COLLATE utf8_unicode_ci NOT NULL,
  `subtitle` varchar(1024) COLLATE utf8_unicode_ci NOT NULL,
  `author_name` varchar(128) COLLATE utf8_unicode_ci NOT NULL,
  `author_email` varchar(128) COLLATE utf8_unicode_ci NOT NULL,
  `created_on` datetime NOT NULL,
  `updated_on` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_5FC892F940C86FCE` (`publisher_id`),
  KEY `IDX_5FC892F951A5BC03` (`feed_id`),
  CONSTRAINT `FK_5FC892F940C86FCE` FOREIGN KEY (`publisher_id`) REFERENCES `FeedPublishers` (`id`),
  CONSTRAINT `FK_5FC892F951A5BC03` FOREIGN KEY (`feed_id`) REFERENCES `Feeds` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `FeedEntries`
--

LOCK TABLES `FeedEntries` WRITE;
/*!40000 ALTER TABLE `FeedEntries` DISABLE KEYS */;
/*!40000 ALTER TABLE `FeedEntries` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `FeedItems`
--

DROP TABLE IF EXISTS `FeedItems`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `FeedItems` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `entry_id` int(11) DEFAULT NULL,
  `record_id` int(11) NOT NULL,
  `sbas_id` int(11) NOT NULL,
  `ord` int(11) NOT NULL,
  `created_on` datetime NOT NULL,
  `updated_on` datetime NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `lookup_unique_idx` (`entry_id`,`sbas_id`,`record_id`),
  KEY `IDX_7F9CDFA6BA364942` (`entry_id`),
  CONSTRAINT `FK_7F9CDFA6BA364942` FOREIGN KEY (`entry_id`) REFERENCES `FeedEntries` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `FeedItems`
--

LOCK TABLES `FeedItems` WRITE;
/*!40000 ALTER TABLE `FeedItems` DISABLE KEYS */;
/*!40000 ALTER TABLE `FeedItems` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `FeedPublishers`
--

DROP TABLE IF EXISTS `FeedPublishers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `FeedPublishers` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `feed_id` int(11) DEFAULT NULL,
  `owner` tinyint(1) NOT NULL DEFAULT '0',
  `created_on` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_31AFAB2A76ED395` (`user_id`),
  KEY `IDX_31AFAB251A5BC03` (`feed_id`),
  CONSTRAINT `FK_31AFAB251A5BC03` FOREIGN KEY (`feed_id`) REFERENCES `Feeds` (`id`),
  CONSTRAINT `FK_31AFAB2A76ED395` FOREIGN KEY (`user_id`) REFERENCES `Users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `FeedPublishers`
--

LOCK TABLES `FeedPublishers` WRITE;
/*!40000 ALTER TABLE `FeedPublishers` DISABLE KEYS */;
/*!40000 ALTER TABLE `FeedPublishers` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `FeedTokens`
--

DROP TABLE IF EXISTS `FeedTokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `FeedTokens` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `feed_id` int(11) DEFAULT NULL,
  `value` varchar(64) COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_9D1CA848A76ED395` (`user_id`),
  KEY `IDX_9D1CA84851A5BC03` (`feed_id`),
  CONSTRAINT `FK_9D1CA84851A5BC03` FOREIGN KEY (`feed_id`) REFERENCES `Feeds` (`id`),
  CONSTRAINT `FK_9D1CA848A76ED395` FOREIGN KEY (`user_id`) REFERENCES `Users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `FeedTokens`
--

LOCK TABLES `FeedTokens` WRITE;
/*!40000 ALTER TABLE `FeedTokens` DISABLE KEYS */;
/*!40000 ALTER TABLE `FeedTokens` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `Feeds`
--

DROP TABLE IF EXISTS `Feeds`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `Feeds` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `public` tinyint(1) NOT NULL DEFAULT '0',
  `icon_url` tinyint(1) NOT NULL DEFAULT '0',
  `base_id` int(11) DEFAULT NULL,
  `title` varchar(128) COLLATE utf8_unicode_ci NOT NULL,
  `subtitle` varchar(1024) COLLATE utf8_unicode_ci DEFAULT NULL,
  `created_on` datetime NOT NULL,
  `updated_on` datetime NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `Feeds`
--

LOCK TABLES `Feeds` WRITE;
/*!40000 ALTER TABLE `Feeds` DISABLE KEYS */;
/*!40000 ALTER TABLE `Feeds` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `FtpCredential`
--

DROP TABLE IF EXISTS `FtpCredential`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `FtpCredential` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `active` tinyint(1) NOT NULL DEFAULT '0',
  `address` varchar(128) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `login` varchar(128) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `password` varchar(128) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `reception_folder` varchar(128) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `repository_prefix_name` varchar(128) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `passive` tinyint(1) NOT NULL DEFAULT '0',
  `tls` tinyint(1) NOT NULL DEFAULT '0',
  `max_retry` int(11) NOT NULL DEFAULT '5',
  `updated` datetime NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `UNIQ_62DA9661A76ED395` (`user_id`),
  CONSTRAINT `FK_62DA9661A76ED395` FOREIGN KEY (`user_id`) REFERENCES `Users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `FtpCredential`
--

LOCK TABLES `FtpCredential` WRITE;
/*!40000 ALTER TABLE `FtpCredential` DISABLE KEYS */;
/*!40000 ALTER TABLE `FtpCredential` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `FtpExportElements`
--

DROP TABLE IF EXISTS `FtpExportElements`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `FtpExportElements` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `export_id` int(11) DEFAULT NULL,
  `record_id` int(11) NOT NULL,
  `base_id` int(11) NOT NULL,
  `subdef` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `filename` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `folder` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `error` tinyint(1) NOT NULL DEFAULT '0',
  `done` tinyint(1) NOT NULL DEFAULT '0',
  `businessfields` tinyint(1) NOT NULL DEFAULT '0',
  `created` datetime NOT NULL,
  `updated` datetime NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_ftp_export` (`export_id`,`base_id`,`record_id`,`subdef`),
  KEY `IDX_7BF0AE1264CDAF82` (`export_id`),
  KEY `ftp_export_element_done` (`done`),
  KEY `ftp_export_element_error` (`error`),
  CONSTRAINT `FK_7BF0AE1264CDAF82` FOREIGN KEY (`export_id`) REFERENCES `FtpExports` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `FtpExportElements`
--

LOCK TABLES `FtpExportElements` WRITE;
/*!40000 ALTER TABLE `FtpExportElements` DISABLE KEYS */;
/*!40000 ALTER TABLE `FtpExportElements` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `FtpExports`
--

DROP TABLE IF EXISTS `FtpExports`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `FtpExports` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `crash` int(11) NOT NULL DEFAULT '0',
  `nbretry` int(11) NOT NULL DEFAULT '3',
  `mail` varchar(128) COLLATE utf8_unicode_ci DEFAULT NULL,
  `addr` varchar(128) COLLATE utf8_unicode_ci NOT NULL,
  `use_ssl` tinyint(1) NOT NULL DEFAULT '0',
  `login` varchar(128) COLLATE utf8_unicode_ci DEFAULT NULL,
  `pwd` varchar(128) COLLATE utf8_unicode_ci DEFAULT NULL,
  `passif` tinyint(1) NOT NULL DEFAULT '0',
  `destfolder` varchar(128) COLLATE utf8_unicode_ci NOT NULL DEFAULT '/',
  `sendermail` varchar(128) COLLATE utf8_unicode_ci DEFAULT '1',
  `text_mail_sender` varchar(128) COLLATE utf8_unicode_ci DEFAULT NULL,
  `text_mail_receiver` varchar(128) COLLATE utf8_unicode_ci DEFAULT NULL,
  `foldertocreate` varchar(128) COLLATE utf8_unicode_ci DEFAULT NULL,
  `logfile` tinyint(1) NOT NULL DEFAULT '0',
  `created` datetime NOT NULL,
  `updated` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_CFCEEE7AA76ED395` (`user_id`),
  CONSTRAINT `FK_CFCEEE7AA76ED395` FOREIGN KEY (`user_id`) REFERENCES `Users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `FtpExports`
--

LOCK TABLES `FtpExports` WRITE;
/*!40000 ALTER TABLE `FtpExports` DISABLE KEYS */;
/*!40000 ALTER TABLE `FtpExports` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `LazaretAttributes`
--

DROP TABLE IF EXISTS `LazaretAttributes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `LazaretAttributes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `lazaret_file_id` int(11) DEFAULT NULL,
  `name` varchar(64) COLLATE utf8_unicode_ci NOT NULL,
  `value` varchar(2048) COLLATE utf8_unicode_ci NOT NULL,
  `created` datetime NOT NULL,
  `updated` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_5FF72F9B4CF84ADD` (`lazaret_file_id`),
  CONSTRAINT `FK_5FF72F9B4CF84ADD` FOREIGN KEY (`lazaret_file_id`) REFERENCES `LazaretFiles` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `LazaretAttributes`
--

LOCK TABLES `LazaretAttributes` WRITE;
/*!40000 ALTER TABLE `LazaretAttributes` DISABLE KEYS */;
/*!40000 ALTER TABLE `LazaretAttributes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `LazaretChecks`
--

DROP TABLE IF EXISTS `LazaretChecks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `LazaretChecks` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `lazaret_file_id` int(11) DEFAULT NULL,
  `checkClassname` varchar(512) COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_CE873ED44CF84ADD` (`lazaret_file_id`),
  CONSTRAINT `FK_CE873ED44CF84ADD` FOREIGN KEY (`lazaret_file_id`) REFERENCES `LazaretFiles` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `LazaretChecks`
--

LOCK TABLES `LazaretChecks` WRITE;
/*!40000 ALTER TABLE `LazaretChecks` DISABLE KEYS */;
/*!40000 ALTER TABLE `LazaretChecks` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `LazaretFiles`
--

DROP TABLE IF EXISTS `LazaretFiles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `LazaretFiles` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `lazaret_session_id` int(11) DEFAULT NULL,
  `filename` varchar(512) COLLATE utf8_unicode_ci NOT NULL,
  `thumbFilename` varchar(512) COLLATE utf8_unicode_ci NOT NULL,
  `originalName` varchar(256) COLLATE utf8_unicode_ci NOT NULL,
  `base_id` int(11) NOT NULL,
  `uuid` varchar(36) COLLATE utf8_unicode_ci NOT NULL,
  `sha256` varchar(64) COLLATE utf8_unicode_ci NOT NULL,
  `forced` tinyint(1) NOT NULL DEFAULT '0',
  `created` datetime NOT NULL,
  `updated` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_D30BD768EE187C01` (`lazaret_session_id`),
  CONSTRAINT `FK_D30BD768EE187C01` FOREIGN KEY (`lazaret_session_id`) REFERENCES `LazaretSessions` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `LazaretFiles`
--

LOCK TABLES `LazaretFiles` WRITE;
/*!40000 ALTER TABLE `LazaretFiles` DISABLE KEYS */;
/*!40000 ALTER TABLE `LazaretFiles` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `LazaretSessions`
--

DROP TABLE IF EXISTS `LazaretSessions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `LazaretSessions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `created` datetime NOT NULL,
  `updated` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_40A81317A76ED395` (`user_id`),
  CONSTRAINT `FK_40A81317A76ED395` FOREIGN KEY (`user_id`) REFERENCES `Users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `LazaretSessions`
--

LOCK TABLES `LazaretSessions` WRITE;
/*!40000 ALTER TABLE `LazaretSessions` DISABLE KEYS */;
/*!40000 ALTER TABLE `LazaretSessions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `OrderElements`
--

DROP TABLE IF EXISTS `OrderElements`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `OrderElements` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `order_master` int(11) DEFAULT NULL,
  `order_id` int(11) DEFAULT NULL,
  `base_id` int(11) NOT NULL,
  `record_id` int(11) NOT NULL,
  `deny` tinyint(1) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_ordercle` (`base_id`,`record_id`,`order_id`),
  KEY `IDX_8C7066C8EE86B303` (`order_master`),
  KEY `IDX_8C7066C88D9F6D38` (`order_id`),
  CONSTRAINT `FK_8C7066C88D9F6D38` FOREIGN KEY (`order_id`) REFERENCES `Orders` (`id`),
  CONSTRAINT `FK_8C7066C8EE86B303` FOREIGN KEY (`order_master`) REFERENCES `Users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `OrderElements`
--

LOCK TABLES `OrderElements` WRITE;
/*!40000 ALTER TABLE `OrderElements` DISABLE KEYS */;
/*!40000 ALTER TABLE `OrderElements` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `Orders`
--

DROP TABLE IF EXISTS `Orders`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `Orders` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `basket_id` int(11) DEFAULT NULL,
  `order_usage` varchar(2048) COLLATE utf8_unicode_ci NOT NULL,
  `todo` int(11) DEFAULT NULL,
  `deadline` datetime NOT NULL,
  `created_on` datetime NOT NULL,
  `notification_method` varchar(32) COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `UNIQ_E283F8D81BE1FB52` (`basket_id`),
  KEY `IDX_E283F8D8A76ED395` (`user_id`),
  CONSTRAINT `FK_E283F8D81BE1FB52` FOREIGN KEY (`basket_id`) REFERENCES `Baskets` (`id`),
  CONSTRAINT `FK_E283F8D8A76ED395` FOREIGN KEY (`user_id`) REFERENCES `Users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `Orders`
--

LOCK TABLES `Orders` WRITE;
/*!40000 ALTER TABLE `Orders` DISABLE KEYS */;
/*!40000 ALTER TABLE `Orders` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `Presets`
--

DROP TABLE IF EXISTS `Presets`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `Presets` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `sbas_id` int(11) NOT NULL,
  `title` varchar(128) COLLATE utf8_unicode_ci NOT NULL,
  `data` longtext COLLATE utf8_unicode_ci NOT NULL,
  `created` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_1C48F8F3A76ED395` (`user_id`),
  CONSTRAINT `FK_1C48F8F3A76ED395` FOREIGN KEY (`user_id`) REFERENCES `Users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `Presets`
--

LOCK TABLES `Presets` WRITE;
/*!40000 ALTER TABLE `Presets` DISABLE KEYS */;
/*!40000 ALTER TABLE `Presets` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `Registrations`
--

DROP TABLE IF EXISTS `Registrations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `Registrations` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `base_id` int(11) NOT NULL,
  `pending` tinyint(1) NOT NULL DEFAULT '1',
  `rejected` tinyint(1) NOT NULL DEFAULT '0',
  `created` datetime NOT NULL,
  `updated` datetime NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_registration` (`user_id`,`base_id`,`pending`),
  KEY `IDX_E0A01A12A76ED395` (`user_id`),
  CONSTRAINT `FK_E0A01A12A76ED395` FOREIGN KEY (`user_id`) REFERENCES `Users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `Registrations`
--

LOCK TABLES `Registrations` WRITE;
/*!40000 ALTER TABLE `Registrations` DISABLE KEYS */;
/*!40000 ALTER TABLE `Registrations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `Secrets`
--

DROP TABLE IF EXISTS `Secrets`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `Secrets` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `creator_id` int(11) NOT NULL,
  `created` datetime NOT NULL,
  `token` varchar(40) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_48F428861220EA6` (`creator_id`),
  CONSTRAINT `FK_48F428861220EA6` FOREIGN KEY (`creator_id`) REFERENCES `Users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `Secrets`
--

LOCK TABLES `Secrets` WRITE;
/*!40000 ALTER TABLE `Secrets` DISABLE KEYS */;
/*!40000 ALTER TABLE `Secrets` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `SessionModules`
--

DROP TABLE IF EXISTS `SessionModules`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `SessionModules` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `session_id` int(11) DEFAULT NULL,
  `module_id` int(11) NOT NULL,
  `created` datetime NOT NULL,
  `updated` datetime NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_module` (`session_id`,`module_id`),
  KEY `IDX_BA36EF49613FECDF` (`session_id`),
  CONSTRAINT `FK_BA36EF49613FECDF` FOREIGN KEY (`session_id`) REFERENCES `Sessions` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `SessionModules`
--

LOCK TABLES `SessionModules` WRITE;
/*!40000 ALTER TABLE `SessionModules` DISABLE KEYS */;
/*!40000 ALTER TABLE `SessionModules` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `Sessions`
--

DROP TABLE IF EXISTS `Sessions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `Sessions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `user_agent` varchar(512) COLLATE utf8_unicode_ci NOT NULL,
  `ip_address` varchar(40) COLLATE utf8_unicode_ci DEFAULT NULL,
  `platform` varchar(128) COLLATE utf8_unicode_ci DEFAULT NULL,
  `browser_name` varchar(128) COLLATE utf8_unicode_ci DEFAULT NULL,
  `browser_version` varchar(32) COLLATE utf8_unicode_ci DEFAULT NULL,
  `screen_width` int(11) DEFAULT NULL,
  `screen_height` int(11) DEFAULT NULL,
  `token` varchar(128) COLLATE utf8_unicode_ci DEFAULT NULL,
  `nonce` varchar(64) COLLATE utf8_unicode_ci DEFAULT NULL,
  `created` datetime NOT NULL,
  `updated` datetime NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `UNIQ_6316FF455F37A13B` (`token`),
  KEY `IDX_6316FF45A76ED395` (`user_id`),
  CONSTRAINT `FK_6316FF45A76ED395` FOREIGN KEY (`user_id`) REFERENCES `Users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `Sessions`
--

LOCK TABLES `Sessions` WRITE;
/*!40000 ALTER TABLE `Sessions` DISABLE KEYS */;
/*!40000 ALTER TABLE `Sessions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `StoryWZ`
--

DROP TABLE IF EXISTS `StoryWZ`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `StoryWZ` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `sbas_id` int(11) NOT NULL,
  `record_id` int(11) NOT NULL,
  `created` datetime NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_story` (`user_id`,`sbas_id`,`record_id`),
  KEY `IDX_E0D2CBAEA76ED395` (`user_id`),
  CONSTRAINT `FK_E0D2CBAEA76ED395` FOREIGN KEY (`user_id`) REFERENCES `Users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `StoryWZ`
--

LOCK TABLES `StoryWZ` WRITE;
/*!40000 ALTER TABLE `StoryWZ` DISABLE KEYS */;
/*!40000 ALTER TABLE `StoryWZ` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `Tasks`
--

DROP TABLE IF EXISTS `Tasks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `Tasks` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `jobId` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `settings` longtext COLLATE utf8_unicode_ci NOT NULL,
  `completed` tinyint(1) NOT NULL DEFAULT '0',
  `status` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT 'started',
  `crashed` int(11) NOT NULL DEFAULT '0',
  `single_run` tinyint(1) NOT NULL DEFAULT '0',
  `created` datetime NOT NULL,
  `updated` datetime NOT NULL,
  `last_execution` datetime DEFAULT NULL,
  `period` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `Tasks`
--

LOCK TABLES `Tasks` WRITE;
/*!40000 ALTER TABLE `Tasks` DISABLE KEYS */;
INSERT INTO `Tasks` VALUES (1,'Subviews creation','Subdefs','<?xml version=\"1.0\" encoding=\"UTF-8\"?>\r\n<tasksettings>\r\n  <embedded>1</embedded>\r\n  <sbas/>\r\n  <type_image>1</type_image>\r\n  <type_video>1</type_video>\r\n  <type_audio>1</type_audio>\r\n  <type_document>1</type_document>\r\n  <type_flash>1</type_flash>\r\n  <type_unknown>1</type_unknown>\r\n  <flush>5</flush>\r\n  <maxrecs>20</maxrecs>\r\n  <maxmegs>256</maxmegs>\r\n  <maxduration>3600</maxduration>\r\n</tasksettings>',0,'started',0,0,'2018-10-22 06:04:28','2018-10-22 06:04:28',NULL,10),(2,'Write metadatas','WriteMetadata','<?xml version=\"1.0\" encoding=\"UTF-8\"?>\r\n<tasksettings>\r\n    <cleardoc>0</cleardoc>\r\n    <mwg>0</mwg>\r\n</tasksettings>',0,'started',0,0,'2018-10-22 06:04:28','2018-10-22 06:04:28',NULL,10);
/*!40000 ALTER TABLE `Tasks` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `Tokens`
--

DROP TABLE IF EXISTS `Tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `Tokens` (
  `value` varchar(128) COLLATE utf8_unicode_ci NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `type` varchar(32) COLLATE utf8_unicode_ci NOT NULL,
  `data` longtext COLLATE utf8_unicode_ci,
  `created` datetime NOT NULL,
  `updated` datetime NOT NULL,
  `expiration` datetime DEFAULT NULL,
  PRIMARY KEY (`value`),
  KEY `IDX_ADF614B8A76ED395` (`user_id`),
  CONSTRAINT `FK_ADF614B8A76ED395` FOREIGN KEY (`user_id`) REFERENCES `Users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `Tokens`
--

LOCK TABLES `Tokens` WRITE;
/*!40000 ALTER TABLE `Tokens` DISABLE KEYS */;
/*!40000 ALTER TABLE `Tokens` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `UserNotificationSettings`
--

DROP TABLE IF EXISTS `UserNotificationSettings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `UserNotificationSettings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `name` varchar(64) COLLATE utf8_unicode_ci NOT NULL,
  `value` varchar(128) COLLATE utf8_unicode_ci DEFAULT NULL,
  `created` datetime NOT NULL,
  `updated` datetime NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_index` (`user_id`,`name`),
  KEY `IDX_CFF041AAA76ED395` (`user_id`),
  CONSTRAINT `FK_CFF041AAA76ED395` FOREIGN KEY (`user_id`) REFERENCES `Users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `UserNotificationSettings`
--

LOCK TABLES `UserNotificationSettings` WRITE;
/*!40000 ALTER TABLE `UserNotificationSettings` DISABLE KEYS */;
/*!40000 ALTER TABLE `UserNotificationSettings` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `UserQueries`
--

DROP TABLE IF EXISTS `UserQueries`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `UserQueries` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `query` varchar(256) COLLATE utf8_unicode_ci NOT NULL,
  `created` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_5FB80D87A76ED395` (`user_id`),
  CONSTRAINT `FK_5FB80D87A76ED395` FOREIGN KEY (`user_id`) REFERENCES `Users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `UserQueries`
--

LOCK TABLES `UserQueries` WRITE;
/*!40000 ALTER TABLE `UserQueries` DISABLE KEYS */;
/*!40000 ALTER TABLE `UserQueries` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `UserSettings`
--

DROP TABLE IF EXISTS `UserSettings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `UserSettings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `name` varchar(64) COLLATE utf8_unicode_ci NOT NULL,
  `value` text COLLATE utf8_unicode_ci NOT NULL,
  `created` datetime NOT NULL,
  `updated` datetime NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_setting` (`user_id`,`name`),
  KEY `IDX_2847E61CA76ED395` (`user_id`),
  CONSTRAINT `FK_2847E61CA76ED395` FOREIGN KEY (`user_id`) REFERENCES `Users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `UserSettings`
--

LOCK TABLES `UserSettings` WRITE;
/*!40000 ALTER TABLE `UserSettings` DISABLE KEYS */;
/*!40000 ALTER TABLE `UserSettings` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `Users`
--

DROP TABLE IF EXISTS `Users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `Users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `last_model` int(11) DEFAULT NULL,
  `model_of` int(11) DEFAULT NULL,
  `login` varchar(128) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL,
  `email` varchar(128) COLLATE utf8_unicode_ci DEFAULT NULL,
  `password` varchar(128) CHARACTER SET utf8 COLLATE utf8_bin DEFAULT NULL,
  `nonce` varchar(64) CHARACTER SET utf8 COLLATE utf8_bin DEFAULT NULL,
  `salted_password` tinyint(1) NOT NULL DEFAULT '1',
  `first_name` varchar(64) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `last_name` varchar(64) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `gender` smallint(6) DEFAULT NULL,
  `address` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `city` varchar(64) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `country` varchar(64) COLLATE utf8_unicode_ci DEFAULT '',
  `zip_code` varchar(32) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `geoname_id` int(11) DEFAULT NULL,
  `locale` varchar(8) COLLATE utf8_unicode_ci DEFAULT NULL,
  `timezone` varchar(128) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `job` varchar(128) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `activity` varchar(256) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `company` varchar(64) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `phone` varchar(32) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `fax` varchar(32) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `admin` tinyint(1) NOT NULL DEFAULT '0',
  `guest` tinyint(1) NOT NULL DEFAULT '0',
  `mail_notifications` tinyint(1) NOT NULL DEFAULT '0',
  `request_notifications` tinyint(1) NOT NULL DEFAULT '0',
  `ldap_created` tinyint(1) NOT NULL DEFAULT '0',
  `push_list` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `can_change_profil` tinyint(1) NOT NULL DEFAULT '1',
  `can_change_ftp_profil` tinyint(1) NOT NULL DEFAULT '1',
  `last_connection` datetime DEFAULT NULL,
  `mail_locked` tinyint(1) NOT NULL DEFAULT '0',
  `deleted` tinyint(1) NOT NULL DEFAULT '0',
  `created` datetime NOT NULL,
  `updated` datetime NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `login_unique` (`login`),
  UNIQUE KEY `email_unique` (`email`),
  KEY `IDX_D5428AEDB5DE44C2` (`last_model`),
  KEY `user_model_of` (`model_of`),
  KEY `user_salted_password` (`salted_password`),
  KEY `user_admin` (`admin`),
  KEY `user_guest` (`guest`),
  CONSTRAINT `FK_D5428AEDB5DE44C2` FOREIGN KEY (`last_model`) REFERENCES `Users` (`id`),
  CONSTRAINT `FK_D5428AEDC121714D` FOREIGN KEY (`model_of`) REFERENCES `Users` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `Users`
--

LOCK TABLES `Users` WRITE;
/*!40000 ALTER TABLE `Users` DISABLE KEYS */;
INSERT INTO `Users` VALUES (1,NULL,NULL,'milos.milosavljevic@gmail.com','milos.milosavljevic@gmail.com','20cb8f284eca626bd961b8f7e54c9afa8d82a4063de95b23fb3748c24580468affc8f8a488d2964e1adcd367268658bce8d43d17835507c7f167ddf9c7c3eb4d','hJAfRgUKrP+Y3sHCFWkqPrR7s0uhnZg4P+CRI4AkkyFd8oDG90deNZO0TNhXvYa5',1,'','',NULL,'','','','',NULL,NULL,'','','','','','',1,0,0,0,0,'',1,1,NULL,0,0,'2018-10-22 06:02:21','2018-10-22 06:02:21'),(2,NULL,NULL,'autoregister',NULL,'a0933e16d2c18bef2ba79c60e74da15bff42ceae9955f9ac107d1607d1c79e160adc7217e56909e5b20c553fa1c30df0b3ec838e963bfc856efb15698abbd2f0','sOXGgY7PA1i8tXKMvxcKUK+r9XWH16TSmnNoLem/1d5i9taWVW5rJrtSCeRKOqhF',1,'','',NULL,'','','','',NULL,NULL,'','','','','','',0,0,0,0,0,'',1,1,NULL,0,0,'2018-10-22 06:02:21','2018-10-22 06:02:21'),(3,NULL,NULL,'guest',NULL,'170319f9325072f29f39590d997778f238aead738bdf477b9880dfbf331c7c3aa62053a8b8ba3115ea5eeeb067395447074b0f7eb240a0cc018666f75f7f6ef6','pjYZuumvTDnHFPFTAzvwcfLx2WthAFpA1Y9C5wdjN9YlSJOe7SzHUNOfBoM9I+GZ',1,'','',NULL,'','','','',NULL,NULL,'','','','','','',0,0,0,0,0,'',1,1,NULL,0,0,'2018-10-22 06:02:22','2018-10-22 06:02:22');
/*!40000 ALTER TABLE `Users` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `UsrAuthProviders`
--

DROP TABLE IF EXISTS `UsrAuthProviders`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `UsrAuthProviders` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `provider` varchar(32) COLLATE utf8_unicode_ci NOT NULL,
  `distant_id` varchar(192) COLLATE utf8_unicode_ci NOT NULL,
  `created` datetime NOT NULL,
  `updated` datetime NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_provider_per_user` (`user_id`,`provider`),
  UNIQUE KEY `provider_ids` (`provider`,`distant_id`),
  KEY `IDX_947F003FA76ED395` (`user_id`),
  CONSTRAINT `FK_947F003FA76ED395` FOREIGN KEY (`user_id`) REFERENCES `Users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `UsrAuthProviders`
--

LOCK TABLES `UsrAuthProviders` WRITE;
/*!40000 ALTER TABLE `UsrAuthProviders` DISABLE KEYS */;
/*!40000 ALTER TABLE `UsrAuthProviders` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `UsrListOwners`
--

DROP TABLE IF EXISTS `UsrListOwners`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `UsrListOwners` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `list_id` int(11) DEFAULT NULL,
  `role` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `created` datetime NOT NULL,
  `updated` datetime NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_owner` (`user_id`,`id`),
  KEY `IDX_54E9FE23A76ED395` (`user_id`),
  KEY `IDX_54E9FE233DAE168B` (`list_id`),
  CONSTRAINT `FK_54E9FE233DAE168B` FOREIGN KEY (`list_id`) REFERENCES `UsrLists` (`id`),
  CONSTRAINT `FK_54E9FE23A76ED395` FOREIGN KEY (`user_id`) REFERENCES `Users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
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
  `name` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `created` datetime NOT NULL,
  `updated` datetime NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
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
  `user_id` int(11) NOT NULL,
  `list_id` int(11) DEFAULT NULL,
  `created` datetime NOT NULL,
  `updated` datetime NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_usr_per_list` (`user_id`,`list_id`),
  KEY `IDX_661B8B9A76ED395` (`user_id`),
  KEY `IDX_661B8B93DAE168B` (`list_id`),
  CONSTRAINT `FK_661B8B93DAE168B` FOREIGN KEY (`list_id`) REFERENCES `UsrLists` (`id`),
  CONSTRAINT `FK_661B8B9A76ED395` FOREIGN KEY (`user_id`) REFERENCES `Users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
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
  `note` longtext COLLATE utf8_unicode_ci,
  `updated` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_70E84DDC9D1C3019` (`participant_id`),
  KEY `IDX_70E84DDCE989605` (`basket_element_id`),
  CONSTRAINT `FK_70E84DDC9D1C3019` FOREIGN KEY (`participant_id`) REFERENCES `ValidationParticipants` (`id`),
  CONSTRAINT `FK_70E84DDCE989605` FOREIGN KEY (`basket_element_id`) REFERENCES `BasketElements` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
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
  `validation_session_id` int(11) DEFAULT NULL,
  `user_id` int(11) NOT NULL,
  `is_aware` tinyint(1) NOT NULL DEFAULT '0',
  `is_confirmed` tinyint(1) NOT NULL DEFAULT '0',
  `can_agree` tinyint(1) NOT NULL DEFAULT '0',
  `can_see_others` tinyint(1) NOT NULL DEFAULT '0',
  `reminded` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_17850D7BF9669572` (`validation_session_id`),
  KEY `IDX_17850D7BA76ED395` (`user_id`),
  CONSTRAINT `FK_17850D7BA76ED395` FOREIGN KEY (`user_id`) REFERENCES `Users` (`id`),
  CONSTRAINT `FK_17850D7BF9669572` FOREIGN KEY (`validation_session_id`) REFERENCES `ValidationSessions` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
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
  `initiator_id` int(11) NOT NULL,
  `basket_id` int(11) DEFAULT NULL,
  `created` datetime NOT NULL,
  `updated` datetime NOT NULL,
  `expires` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `UNIQ_5B9DFB061BE1FB52` (`basket_id`),
  KEY `IDX_5B9DFB067DB3B714` (`initiator_id`),
  CONSTRAINT `FK_5B9DFB061BE1FB52` FOREIGN KEY (`basket_id`) REFERENCES `Baskets` (`id`),
  CONSTRAINT `FK_5B9DFB067DB3B714` FOREIGN KEY (`initiator_id`) REFERENCES `Users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ValidationSessions`
--

LOCK TABLES `ValidationSessions` WRITE;
/*!40000 ALTER TABLE `ValidationSessions` DISABLE KEYS */;
/*!40000 ALTER TABLE `ValidationSessions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `WebhookEventDeliveries`
--

DROP TABLE IF EXISTS `WebhookEventDeliveries`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `WebhookEventDeliveries` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `application_id` int(11) NOT NULL,
  `event_id` int(11) NOT NULL,
  `delivered` tinyint(1) NOT NULL DEFAULT '0',
  `deliveryTries` int(11) NOT NULL DEFAULT '0',
  `created` datetime NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_app_delivery` (`application_id`,`event_id`),
  KEY `IDX_B4A8823A3E030ACD` (`application_id`),
  KEY `IDX_B4A8823A71F7E88B` (`event_id`),
  CONSTRAINT `FK_B4A8823A3E030ACD` FOREIGN KEY (`application_id`) REFERENCES `ApiApplications` (`id`),
  CONSTRAINT `FK_B4A8823A71F7E88B` FOREIGN KEY (`event_id`) REFERENCES `WebhookEvents` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `WebhookEventDeliveries`
--

LOCK TABLES `WebhookEventDeliveries` WRITE;
/*!40000 ALTER TABLE `WebhookEventDeliveries` DISABLE KEYS */;
/*!40000 ALTER TABLE `WebhookEventDeliveries` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `WebhookEventPayloads`
--

DROP TABLE IF EXISTS `WebhookEventPayloads`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `WebhookEventPayloads` (
  `id` char(36) COLLATE utf8_unicode_ci NOT NULL COMMENT '(DC2Type:guid)',
  `delivery_id` int(11) DEFAULT NULL,
  `request` longtext COLLATE utf8_unicode_ci NOT NULL,
  `response` longtext COLLATE utf8_unicode_ci NOT NULL,
  `status` int(11) NOT NULL,
  `headers` longtext COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `UNIQ_B949629612136921` (`delivery_id`),
  CONSTRAINT `FK_B949629612136921` FOREIGN KEY (`delivery_id`) REFERENCES `WebhookEventDeliveries` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `WebhookEventPayloads`
--

LOCK TABLES `WebhookEventPayloads` WRITE;
/*!40000 ALTER TABLE `WebhookEventPayloads` DISABLE KEYS */;
/*!40000 ALTER TABLE `WebhookEventPayloads` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `WebhookEvents`
--

DROP TABLE IF EXISTS `WebhookEvents`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `WebhookEvents` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(64) COLLATE utf8_unicode_ci NOT NULL,
  `type` varchar(64) COLLATE utf8_unicode_ci NOT NULL,
  `data` longtext COLLATE utf8_unicode_ci NOT NULL COMMENT '(DC2Type:json_array)',
  `processed` tinyint(1) NOT NULL DEFAULT '0',
  `created` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `webhook_event_name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `WebhookEvents`
--

LOCK TABLES `WebhookEvents` WRITE;
/*!40000 ALTER TABLE `WebhookEvents` DISABLE KEYS */;
/*!40000 ALTER TABLE `WebhookEvents` ENABLE KEYS */;
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
  `server_coll_id` int(11) unsigned NOT NULL DEFAULT '0',
  `aliases` text COLLATE utf8_unicode_ci NOT NULL,
  `sbas_id` int(11) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`base_id`),
  UNIQUE KEY `collection` (`sbas_id`,`server_coll_id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `bas`
--

LOCK TABLES `bas` WRITE;
/*!40000 ALTER TABLE `bas` DISABLE KEYS */;
INSERT INTO `bas` VALUES (1,1,1,1,'',1);
/*!40000 ALTER TABLE `bas` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `basusr`
--

DROP TABLE IF EXISTS `basusr`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `basusr` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
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
  `mask_and` int(11) unsigned NOT NULL DEFAULT '0',
  `mask_xor` int(11) unsigned NOT NULL DEFAULT '0',
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
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `basusr`
--

LOCK TABLES `basusr` WRITE;
/*!40000 ALTER TABLE `basusr` DISABLE KEYS */;
INSERT INTO `basusr` VALUES (1,1,1,1,1,0,1,1,1,1,1,1,'0000-00-00 00:00:00','',0,0,0,0,0,0,'0000-00-00 00:00:00','0000-00-00 00:00:00',1,0,1,1,1,'2018-10-22 06:04:24',1,1,1,0,1);
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
INSERT INTO `bridge_apis` VALUES (1,'youtube',0,NULL,'2018-10-22 06:01:49','2018-10-22 06:01:49'),(2,'flickr',0,NULL,'2018-10-22 06:01:49','2018-10-22 06:01:49'),(3,'dailymotion',0,NULL,'2018-10-22 06:01:49','2018-10-22 06:01:49');
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
  `sbas_id` int(11) unsigned NOT NULL,
  `usr_id` int(11) unsigned NOT NULL,
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
  `pwd` char(64) COLLATE utf8_unicode_ci DEFAULT NULL,
  `viewname` char(128) COLLATE utf8_unicode_ci DEFAULT NULL,
  `indexable` tinyint(1) unsigned NOT NULL DEFAULT '1',
  `label_en` char(50) COLLATE utf8_unicode_ci DEFAULT NULL,
  `label_fr` char(50) COLLATE utf8_unicode_ci DEFAULT NULL,
  `label_de` char(50) COLLATE utf8_unicode_ci DEFAULT NULL,
  `label_nl` char(50) COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`sbas_id`),
  UNIQUE KEY `server` (`host`,`port`,`dbname`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sbas`
--

LOCK TABLES `sbas` WRITE;
/*!40000 ALTER TABLE `sbas` DISABLE KEYS */;
INSERT INTO `sbas` VALUES (1,1,'localhost',3306,'db_master','MYSQL','phraseanet','phraseanet',NULL,1,NULL,NULL,NULL,NULL);
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
INSERT INTO `sbasusr` VALUES (1,1,1,1,1,1,1);
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
INSERT INTO `sitepreff` VALUES (1,'<?xml version=\"1.0\" encoding=\"UTF-8\" standalone=\"yes\"?>                                <paramsite>                                    <statuschu>                                        <bit n=\"-1\" link=\"1\" order=\"0\" view=\"0\" label=\"\" wmprev=\"0\" thumbLimit=\"0\"/>                                    </statuschu>                                </paramsite>','4.1.0-alpha.12','0000-00-00 00:00:00','2018-10-22 06:04:16','stopped','0000-00-00 00:00:00',NULL,NULL);
/*!40000 ALTER TABLE `sitepreff` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2018-10-22  6:45:26
