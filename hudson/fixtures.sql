-- MySQL dump 10.14  Distrib 5.3.7-MariaDB, for apple-darwin11.4.0 (i386)
--
-- Host: localhost    Database: ab_test31
-- ------------------------------------------------------
-- Server version	5.3.7-MariaDB

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

CREATE DATABASE /*!32312 IF NOT EXISTS*/ `ab_test` /*!40100 DEFAULT CHARACTER SET utf8 */;

USE `ab_test`;

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
  `pwd` varchar(64) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL,
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
  `base_id` int(11) unsigned NOT NULL DEFAULT '0',
  `ord` int(11) unsigned NOT NULL DEFAULT '0',
  `active` tinyint(4) unsigned NOT NULL DEFAULT '0',
  `server_coll_id` bigint(20) NOT NULL DEFAULT '0',
  `aliases` text COLLATE utf8_unicode_ci NOT NULL,
  `sbas_id` int(11) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`base_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `bas`
--

LOCK TABLES `bas` WRITE;
/*!40000 ALTER TABLE `bas` DISABLE KEYS */;
INSERT INTO `bas` VALUES (2,0,1,2,'',1);
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
  `canpreview` int(1) unsigned NOT NULL DEFAULT '0',
  `canhd` int(1) unsigned NOT NULL DEFAULT '0',
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
  `canmodifrecord` int(1) unsigned NOT NULL DEFAULT '0',
  `candeleterecord` int(1) unsigned NOT NULL DEFAULT '0',
  `chgstatus` int(1) unsigned NOT NULL DEFAULT '0',
  `lastconn` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `imgtools` int(1) unsigned NOT NULL DEFAULT '0',
  `manage` int(1) unsigned NOT NULL DEFAULT '0',
  `modify_struct` int(1) unsigned NOT NULL DEFAULT '0',
  `bas_manage` int(1) unsigned NOT NULL DEFAULT '0',
  `bas_modify_struct` int(1) unsigned NOT NULL DEFAULT '0',
  `needwatermark` int(1) unsigned NOT NULL DEFAULT '0',
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
INSERT INTO `basusr` VALUES (1,2,4,1,1,1,1,0,1,1,1,1,1,1,'2012-10-02 09:46:45','',0,0,0,0,0,0,'0000-00-00 00:00:00','0000-00-00 00:00:00',1,1,1,1,'0000-00-00 00:00:00',1,1,1,1,0,0);
/*!40000 ALTER TABLE `basusr` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `bmodel`
--

DROP TABLE IF EXISTS `bmodel`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `bmodel` (
  `bmodel_id` int(11) unsigned NOT NULL DEFAULT '0',
  `name` varchar(64) COLLATE utf8_unicode_ci NOT NULL,
  `prefstruct` text COLLATE utf8_unicode_ci NOT NULL,
  `collprefs` text COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`bmodel_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `bmodel`
--

LOCK TABLES `bmodel` WRITE;
/*!40000 ALTER TABLE `bmodel` DISABLE KEYS */;
/*!40000 ALTER TABLE `bmodel` ENABLE KEYS */;
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
  `lastaccess` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `answers` longblob NOT NULL,
  `spots` longblob NOT NULL,
  `session` longblob NOT NULL,
  `dist_logid` text COLLATE utf8_unicode_ci NOT NULL,
  `thesaurus` longtext COLLATE utf8_unicode_ci NOT NULL,
  `app` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT 'a:1:{i:0;i:0;}',
  `appinf` longtext COLLATE utf8_unicode_ci NOT NULL,
  `query` varchar(128) COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`session_id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cache`
--

LOCK TABLES `cache` WRITE;
/*!40000 ALTER TABLE `cache` DISABLE KEYS */;
INSERT INTO `cache` VALUES (1,4,1,'2012-10-02 09:46:52','','','\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0','','','a:1:{i:0;i:0;}','','');
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
  `id` int(11) unsigned NOT NULL DEFAULT '0',
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
  `sendermail` char(255) COLLATE utf8_unicode_ci NOT NULL,
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
-- Table structure for table `order_masters`
--

DROP TABLE IF EXISTS `order_masters`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `order_masters` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `usr_id` int(11) unsigned NOT NULL,
  `base_id` int(11) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `couple` (`usr_id`,`base_id`),
  KEY `usr_id` (`usr_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `order_masters`
--

LOCK TABLES `order_masters` WRITE;
/*!40000 ALTER TABLE `order_masters` DISABLE KEYS */;
/*!40000 ALTER TABLE `order_masters` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `publi_settings`
--

DROP TABLE IF EXISTS `publi_settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `publi_settings` (
  `publi_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `usr_id` int(11) unsigned DEFAULT NULL,
  `name` varchar(128) COLLATE utf8_unicode_ci NOT NULL,
  `url` varchar(256) COLLATE utf8_unicode_ci NOT NULL,
  `login` varchar(256) COLLATE utf8_unicode_ci NOT NULL,
  `password` varchar(256) COLLATE utf8_unicode_ci NOT NULL,
  `appkey` varchar(256) COLLATE utf8_unicode_ci DEFAULT NULL,
  `created_on` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `updated_on` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `type` varchar(256) COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`publi_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `publi_settings`
--

LOCK TABLES `publi_settings` WRITE;
/*!40000 ALTER TABLE `publi_settings` DISABLE KEYS */;
/*!40000 ALTER TABLE `publi_settings` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `published`
--

DROP TABLE IF EXISTS `published`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `published` (
  `publi_id` int(11) unsigned NOT NULL,
  `ssel_id` int(11) unsigned NOT NULL,
  `post_id` int(11) unsigned NOT NULL,
  UNIQUE KEY `unique` (`publi_id`,`ssel_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `published`
--

LOCK TABLES `published` WRITE;
/*!40000 ALTER TABLE `published` DISABLE KEYS */;
/*!40000 ALTER TABLE `published` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `recusr`
--

DROP TABLE IF EXISTS `recusr`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `recusr` (
  `record_id` int(11) unsigned NOT NULL DEFAULT '0',
  `sbas_id` int(11) unsigned NOT NULL DEFAULT '0',
  `usr_id` int(11) unsigned NOT NULL DEFAULT '0',
  `rate` int(11) unsigned NOT NULL DEFAULT '0',
  `comment` text COLLATE utf8_unicode_ci NOT NULL,
  `recusr_datmaj` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`record_id`,`sbas_id`,`usr_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `recusr`
--

LOCK TABLES `recusr` WRITE;
/*!40000 ALTER TABLE `recusr` DISABLE KEYS */;
/*!40000 ALTER TABLE `recusr` ENABLE KEYS */;
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
INSERT INTO `sbasusr` VALUES (1,1,4,1,1,1,1);
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
  `schedpid` int(11) NOT NULL DEFAULT '0',
  `schedulerkey` char(20) COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sitepreff`
--

LOCK TABLES `sitepreff` WRITE;
/*!40000 ALTER TABLE `sitepreff` DISABLE KEYS */;
INSERT INTO `sitepreff` VALUES (1,'<?xml version=\"1.0\" encoding=\"UTF-8\" standalone=\"yes\"?><paramsite><statuschu><bit n=\"-1\" link=\"1\" order=\"0\" view=\"0\" label=\"\" wmprev=\"0\" thumbLimit=\"0\"/></statuschu></paramsite>','3.1.21','0000-00-00 00:00:00','0000-00-00 00:00:00','stopped','0000-00-00 00:00:00',0,NULL);
/*!40000 ALTER TABLE `sitepreff` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ssel`
--

DROP TABLE IF EXISTS `ssel`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ssel` (
  `ssel_id` int(11) unsigned NOT NULL DEFAULT '0',
  `usr_id` int(11) unsigned NOT NULL DEFAULT '0',
  `name` char(50) COLLATE utf8_unicode_ci NOT NULL DEFAULT '0',
  `pushFrom` int(11) unsigned NOT NULL DEFAULT '0',
  `date` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `descript` text COLLATE utf8_unicode_ci NOT NULL,
  `temporaryType` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `rid` int(11) NOT NULL DEFAULT '0',
  `sbas_id` int(11) unsigned NOT NULL DEFAULT '0',
  `status` int(4) unsigned NOT NULL DEFAULT '0',
  `public` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `pub_restrict` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `homelink` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `homelink_update` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `pub_date` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `updater` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `deleted` tinyint(1) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`ssel_id`),
  KEY `homelink` (`homelink`),
  KEY `public` (`public`),
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
  `sselcont_id` int(11) unsigned NOT NULL DEFAULT '0',
  `ssel_id` int(11) unsigned NOT NULL DEFAULT '0',
  `base_id` int(11) unsigned NOT NULL DEFAULT '0',
  `record_id` int(11) unsigned NOT NULL DEFAULT '0',
  `canHD` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `ord` bigint(11) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`sselcont_id`),
  UNIQUE KEY `ssel_ssel_id` (`ssel_id`,`base_id`,`record_id`),
  KEY `ssel_id` (`ssel_id`),
  KEY `canHD` (`canHD`)
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
-- Table structure for table `sselcontusr`
--

DROP TABLE IF EXISTS `sselcontusr`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `sselcontusr` (
  `sselcont_id` int(11) unsigned NOT NULL DEFAULT '0',
  `usr_id` int(11) unsigned NOT NULL DEFAULT '0',
  `agree` tinyint(1) NOT NULL DEFAULT '0',
  `date_maj` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `canHD` binary(1) NOT NULL DEFAULT '0',
  `canRate` binary(1) NOT NULL DEFAULT '0',
  `canAgree` binary(1) NOT NULL DEFAULT '0',
  `canSeeOther` binary(1) NOT NULL DEFAULT '0',
  `canZone` binary(1) NOT NULL DEFAULT '0',
  `dateFin` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`sselcont_id`,`usr_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sselcontusr`
--

LOCK TABLES `sselcontusr` WRITE;
/*!40000 ALTER TABLE `sselcontusr` DISABLE KEYS */;
/*!40000 ALTER TABLE `sselcontusr` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sselcontusrzone`
--

DROP TABLE IF EXISTS `sselcontusrzone`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `sselcontusrzone` (
  `sselcont_id` int(11) unsigned NOT NULL DEFAULT '0',
  `zone_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `usr_id` int(11) unsigned NOT NULL DEFAULT '0',
  `height` float NOT NULL DEFAULT '0',
  `width` float NOT NULL DEFAULT '0',
  `top` float NOT NULL DEFAULT '0',
  `left_` float NOT NULL DEFAULT '0',
  `titre` mediumtext COLLATE utf8_unicode_ci NOT NULL,
  `comment` mediumtext COLLATE utf8_unicode_ci NOT NULL,
  `date_maj` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `deleted` binary(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`zone_id`),
  UNIQUE KEY `unique` (`sselcont_id`,`usr_id`,`zone_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sselcontusrzone`
--

LOCK TABLES `sselcontusrzone` WRITE;
/*!40000 ALTER TABLE `sselcontusrzone` DISABLE KEYS */;
/*!40000 ALTER TABLE `sselcontusrzone` ENABLE KEYS */;
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
  `task_id` int(11) unsigned NOT NULL DEFAULT '0',
  `usr_id_owner` int(11) unsigned NOT NULL DEFAULT '0',
  `pid` int(11) unsigned NOT NULL DEFAULT '0',
  `status` enum('stopped','started','starting','stopping','tostart','tostop','manual','torestart') CHARACTER SET ascii COLLATE ascii_bin NOT NULL DEFAULT 'stopped',
  `crashed` int(2) unsigned NOT NULL DEFAULT '0',
  `active` int(1) unsigned NOT NULL DEFAULT '0',
  `name` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
  `last_exec_time` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `last_change` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `class` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
  `settings` text COLLATE utf8_unicode_ci NOT NULL,
  `completed` tinyint(4) NOT NULL DEFAULT '-1',
  PRIMARY KEY (`task_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `task2`
--

LOCK TABLES `task2` WRITE;
/*!40000 ALTER TABLE `task2` DISABLE KEYS */;
INSERT INTO `task2` VALUES (2,0,0,'stopped',0,1,'Metadatas Reading','0000-00-00 00:00:00','0000-00-00 00:00:00','task_readmeta','<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n<tasksettings>\n<period>10</period><flush>10</flush><autodie>1</autodie><maxrecs></maxrecs><maxmegs></maxmegs></tasksettings>\n',-1),(3,0,0,'stopped',0,1,'Metadatas Writing','0000-00-00 00:00:00','0000-00-00 00:00:00','task_writemeta','<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n<tasksettings>\n	<period>10</period>\n	<autodie>1</autodie>\n	<maxrecs></maxrecs>\n	<maxmegs></maxmegs>\n	<cleardoc>0</cleardoc>\n</tasksettings>\n',-1),(4,0,0,'stopped',0,1,'Subdefs','0000-00-00 00:00:00','0000-00-00 00:00:00','task_subdef','<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n<tasksettings>\n<period>10</period><flush>10</flush><autodie>1</autodie><maxrecs></maxrecs><maxmegs></maxmegs></tasksettings>\n',-1),(5,0,0,'stopped',0,1,'Indexer','0000-00-00 00:00:00','0000-00-00 00:00:00','task_cindexer','<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n<tasksettings>\n<binpath>/usr/local/bin</binpath><host>localhost</host><port>3306</port><base>ab_test31</base><user>root</user><password></password><socket>25200</socket><use_sbas>1</use_sbas><nolog>0</nolog><clng></clng><winsvc_run>0</winsvc_run><charset>utf8</charset></tasksettings>\n',-1);
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
  `type` enum('view','validate','password','rss','email','download') CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
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
-- Table structure for table `uids`
--

DROP TABLE IF EXISTS `uids`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `uids` (
  `uid` int(11) unsigned NOT NULL DEFAULT '0',
  `name` char(16) COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `uids`
--

LOCK TABLES `uids` WRITE;
/*!40000 ALTER TABLE `uids` DISABLE KEYS */;
INSERT INTO `uids` VALUES (2,'BAS'),(1,'DSEL'),(1,'SESSION'),(1,'SSEL'),(1,'SSELCONT'),(5,'TASK'),(1,'THESAURUS'),(4,'USR');
/*!40000 ALTER TABLE `uids` ENABLE KEYS */;
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
  `usr_id` int(11) unsigned NOT NULL DEFAULT '0',
  `ldap_created` int(1) unsigned NOT NULL DEFAULT '0',
  `desktop` text COLLATE utf8_unicode_ci NOT NULL,
  `usr_sexe` int(1) unsigned NOT NULL DEFAULT '0',
  `usr_nom` varchar(50) COLLATE utf8_unicode_ci NOT NULL,
  `usr_prenom` varchar(50) COLLATE utf8_unicode_ci NOT NULL,
  `usr_login` varchar(50) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL,
  `usr_password` varchar(64) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL,
  `last_query` varchar(128) COLLATE utf8_unicode_ci NOT NULL,
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
  `issuperu` int(1) unsigned NOT NULL DEFAULT '0',
  `code8` int(10) unsigned NOT NULL DEFAULT '0',
  `pays` varchar(64) COLLATE utf8_unicode_ci NOT NULL,
  `last_conn` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `model_of` int(11) unsigned NOT NULL DEFAULT '0',
  `seepwd` tinyint(2) NOT NULL DEFAULT '0',
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
  `forceClient` tinyint(1) unsigned NOT NULL DEFAULT '0',
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
  PRIMARY KEY (`usr_id`),
  UNIQUE KEY `usr_login` (`usr_login`),
  KEY `usr_mail` (`usr_mail`),
  KEY `model_of` (`model_of`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `usr`
--

LOCK TABLES `usr` WRITE;
/*!40000 ALTER TABLE `usr` DISABLE KEYS */;
INSERT INTO `usr` VALUES (1,2,'',0,'','','autoregister','autoregister','',NULL,'2012-10-02 09:45:22','2012-10-02 09:45:22','','','','','','','','',0,0,'','0000-00-00 00:00:00',0,0,0,0,'',0,'','','',0,5,5,'',1,'',0,0,0,0,NULL,0,NULL,1,1,'',''),(2,2,'',0,'','','invite','invite','',NULL,'2012-10-02 09:45:22','2012-10-02 09:45:22','','','','','','','','',0,0,'','0000-00-00 00:00:00',0,0,0,0,'',0,'','','',0,5,5,'',1,'',0,0,0,0,NULL,0,NULL,1,1,'',''),(4,0,'',0,'','','admin','45babdcc5e8b40d2c28eafbfc0968f82ddda762d6b3a079d1b2d0d19f1176830','','imprec@gmail.com','2012-10-02 09:46:40','2012-10-02 09:46:40','','','','','','','','',0,0,'','0000-00-00 00:00:00',0,0,1,0,'',0,'','','',0,5,0,'',0,'',1,1,0,0,NULL,0,NULL,1,1,'','');
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
  `created_on` datetime NOT NULL,
  `updated_on` datetime NOT NULL,
  `expires_on` datetime DEFAULT NULL,
  `last_reminder` datetime DEFAULT NULL,
  `usr_id` int(11) unsigned NOT NULL,
  `confirmed` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `can_agree` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `can_see_others` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `can_hd` tinyint(1) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `couple` (`ssel_id`,`usr_id`),
  KEY `ssel_id` (`ssel_id`),
  KEY `can_hd` (`can_hd`)
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

CREATE DATABASE /*!32312 IF NOT EXISTS*/ `db_test` /*!40100 DEFAULT CHARACTER SET utf8 */;

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
  `coll_id` int(11) unsigned NOT NULL DEFAULT '0',
  `htmlname` varchar(50) COLLATE utf8_unicode_ci NOT NULL,
  `asciiname` varchar(50) COLLATE utf8_unicode_ci NOT NULL,
  `prefs` text COLLATE utf8_unicode_ci NOT NULL,
  `logo` longblob NOT NULL,
  `majLogo` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `pub_wm` enum('none','wm','stamp') CHARACTER SET ascii COLLATE ascii_bin NOT NULL DEFAULT 'none',
  PRIMARY KEY (`coll_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `coll`
--

LOCK TABLES `coll` WRITE;
/*!40000 ALTER TABLE `coll` DISABLE KEYS */;
INSERT INTO `coll` VALUES (2,'test','test','<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n			<baseprefs>\n				<status>0</status>\n				<sugestedValues>\n				</sugestedValues>\n			</baseprefs>','','0000-00-00 00:00:00','none');
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
-- Table structure for table `exports`
--

DROP TABLE IF EXISTS `exports`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `exports` (
  `id` int(11) unsigned NOT NULL DEFAULT '0',
  `logid` int(11) unsigned NOT NULL DEFAULT '0',
  `date` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `rid` int(11) unsigned NOT NULL DEFAULT '0',
  `collid` int(11) unsigned NOT NULL DEFAULT '0',
  `weight` bigint(20) unsigned NOT NULL DEFAULT '0',
  `type` varchar(64) COLLATE utf8_unicode_ci NOT NULL,
  `shortXml` longtext COLLATE utf8_unicode_ci NOT NULL,
  `exportType` int(3) unsigned NOT NULL DEFAULT '0',
  `comment1` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`id`),
  KEY `logid` (`logid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `exports`
--

LOCK TABLES `exports` WRITE;
/*!40000 ALTER TABLE `exports` DISABLE KEYS */;
/*!40000 ALTER TABLE `exports` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `histo`
--

DROP TABLE IF EXISTS `histo`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `histo` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `logid` int(11) unsigned NOT NULL DEFAULT '0',
  `act` int(3) unsigned NOT NULL DEFAULT '0',
  `date` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `record` int(11) unsigned NOT NULL DEFAULT '0',
  `origdate` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `origcoll` int(11) unsigned NOT NULL DEFAULT '0',
  `size` bigint(20) unsigned NOT NULL DEFAULT '0',
  `name` char(64) COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `histo`
--

LOCK TABLES `histo` WRITE;
/*!40000 ALTER TABLE `histo` DISABLE KEYS */;
/*!40000 ALTER TABLE `histo` ENABLE KEYS */;
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
  `id` int(11) unsigned NOT NULL DEFAULT '0',
  `date` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `sit_session` int(11) unsigned NOT NULL DEFAULT '0',
  `user` varchar(32) COLLATE utf8_unicode_ci NOT NULL,
  `site` varchar(64) COLLATE utf8_unicode_ci NOT NULL,
  `usrid` int(11) unsigned NOT NULL DEFAULT '0',
  `coll_list` text COLLATE utf8_unicode_ci NOT NULL,
  `nav` varchar(64) COLLATE utf8_unicode_ci NOT NULL,
  `version` varchar(64) COLLATE utf8_unicode_ci NOT NULL,
  `os` varchar(64) COLLATE utf8_unicode_ci NOT NULL,
  `res` varchar(16) COLLATE utf8_unicode_ci NOT NULL,
  `ip` varchar(64) COLLATE utf8_unicode_ci NOT NULL,
  `user_agent` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `appli` varbinary(1024) NOT NULL,
  `fonction` varchar(64) COLLATE utf8_unicode_ci NOT NULL,
  `societe` varchar(64) COLLATE utf8_unicode_ci NOT NULL,
  `activite` varchar(200) COLLATE utf8_unicode_ci NOT NULL,
  `pays` varchar(64) COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`id`),
  KEY `site` (`site`),
  KEY `user` (`user`),
  KEY `date` (`date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
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
  `results` int(11) unsigned NOT NULL DEFAULT '0',
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
  KEY `site_id` (`record_id`)
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
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pref`
--

LOCK TABLES `pref` WRITE;
/*!40000 ALTER TABLE `pref` DISABLE KEYS */;
INSERT INTO `pref` VALUES (1,'thesaurus','','','0000-00-00 00:00:00','2012-04-27 02:06:31'),(2,'structure','<?xml version=\"1.0\" encoding=\"UTF-8\" standalone=\"yes\"?>\n<record modification_date=\"20120427020634\">\n  <path>/tmp/db_test/documents</path>\n  <subdefs>\n    <subdefgroup name=\"image\">\n      <subdef class=\"preview\" name=\"preview\" downloadable=\"true\">\n        <path>/tmp/db_test/subdefs</path>\n        <size>800</size>\n        <method>resample</method>\n        <dpi>72</dpi>\n        <strip>no</strip>\n        <quality>75</quality>\n        <meta>yes</meta>\n        <mediatype>image</mediatype>\n        <label lang=\"fr\">Prévisualisation</label>\n        <label lang=\"en\">Preview</label>\n      </subdef>\n      <subdef class=\"thumbnail\" name=\"thumbnail\" downloadable=\"true\">\n        <path>/tmp/db_test/subdefs</path>\n        <size>200</size>\n        <method>resample</method>\n        <dpi>72</dpi>\n        <strip>yes</strip>\n        <quality>75</quality>\n        <meta>no</meta>\n        <mediatype>image</mediatype>\n        <label lang=\"fr\">Imagette</label>\n        <label lang=\"en\">Thumbnail</label>\n      </subdef>\n    </subdefgroup>\n    <subdefgroup name=\"video\">\n      <subdef class=\"preview\" name=\"preview\" downloadable=\"true\">\n        <path>/tmp/db_test/subdefs</path>\n        <size>800</size>\n        <mediatype>video</mediatype>\n        <writeDatas>yes</writeDatas>\n        <acodec>libvo_aacenc</acodec>\n        <vcodec>libx264</vcodec>\n        <bitrate>1000</bitrate>\n        <threads>8</threads>\n        <fps>15</fps>\n        <label lang=\"fr\">Prévisualisation</label>\n        <label lang=\"en\">Preview</label>\n      </subdef>\n      <subdef class=\"thumbnail\" name=\"thumbnailgif\" downloadable=\"true\">\n        <path>/tmp/db_test/subdefs</path>\n        <size>200</size>\n        <mediatype>gif</mediatype>\n        <delay>500</delay>\n        <writeDatas>no</writeDatas>\n        <label lang=\"fr\">Animation GIF</label>\n        <label lang=\"en\">GIF Animation</label>\n      </subdef>\n      <subdef class=\"thumbnail\" name=\"thumbnail\" downloadable=\"true\">\n        <path>/tmp/db_test/subdefs</path>\n        <size>200</size>\n        <mediatype>image</mediatype>\n        <writeDatas>no</writeDatas>\n        <label lang=\"fr\">Imagette</label>\n        <label lang=\"en\">Thumbnail</label>\n      </subdef>\n    </subdefgroup>\n    <subdefgroup name=\"audio\">\n      <subdef class=\"preview\" name=\"preview\" downloadable=\"true\">\n        <path>/tmp/db_test/subdefs</path>\n        <mediatype>audio</mediatype>\n        <writeDatas>yes</writeDatas>\n        <label lang=\"fr\">Prévisualisation</label>\n        <label lang=\"en\">Preview</label>\n      </subdef>\n      <subdef class=\"thumbnail\" name=\"thumbnail\" downloadable=\"true\">\n        <path>/tmp/db_test/subdefs</path>\n        <mediatype>image</mediatype>\n        <writeDatas>no</writeDatas>\n        <label lang=\"fr\">Imagette</label>\n        <label lang=\"en\">Thumbnail</label>\n      </subdef>\n    </subdefgroup>\n    <subdefgroup name=\"document\">\n      <subdef class=\"preview\" name=\"preview\" downloadable=\"false\">\n        <path>/tmp/db_test/subdefs</path>\n        <mediatype>flexpaper</mediatype>\n        <writeDatas>no</writeDatas>\n        <label lang=\"fr\">Prévisualisation</label>\n        <label lang=\"en\">Preview</label>\n      </subdef>\n      <subdef class=\"thumbnail\" name=\"thumbnail\" downloadable=\"false\">\n        <path>/tmp/db_test/subdefs</path>\n        <mediatype>image</mediatype>\n        <method>resample</method>\n        <dpi>72</dpi>\n        <size>200</size>\n        <writeDatas>no</writeDatas>\n        <label lang=\"fr\">Imagette</label>\n        <label lang=\"en\">Thumbnail</label>\n      </subdef>\n    </subdefgroup>\n    <subdefgroup name=\"flash\">\n      <subdef class=\"preview\" name=\"preview\" downloadable=\"false\">\n        <path>/tmp/db_test/subdefs</path>\n        <mediatype>image</mediatype>\n        <size>200</size>\n        <writeDatas>no</writeDatas>\n        <method>resample</method>\n        <dpi>72</dpi>\n        <label lang=\"fr\">Prévisualisation</label>\n        <label lang=\"en\">Preview</label>\n      </subdef>\n      <subdef class=\"thumbnail\" name=\"thumbnail\" downloadable=\"false\">\n        <path>/tmp/db_test/subdefs</path>\n        <mediatype>image</mediatype>\n        <writeDatas>no</writeDatas>\n        <method>resample</method>\n        <dpi>72</dpi>\n        <label lang=\"fr\">Imagette</label>\n        <label lang=\"en\">Thumbnail</label>\n      </subdef>\n    </subdefgroup>\n  </subdefs>\n  <description>\n    <!-- 2:5 -->\n    <!-- 2:15 -->\n    <!-- 2:20 -->\n    <!-- 2:25 -->\n    <!-- 2:40 -->\n    <!-- 2:55 -->\n    <!-- 2:80 -->\n    <!-- 2:85 -->\n    <!-- 2:90 -->\n    <!-- 2:95 -->\n    <!-- 2:101 -->\n    <!-- 2:103 -->\n    <!-- 2:105 -->\n    <!-- 2:110 -->\n    <!-- 2:115 -->\n    <!-- 2:120 -->\n    <!-- 2:122 -->\n    <!-- 2:25 -->\n    <!-- 2:25 -->\n    <!-- 2:25 -->\n    <!-- Technical Fields -->\n    <Object src=\"IPTC:ObjectName\" index=\"1\" readonly=\"0\" required=\"0\" multi=\"0\" report=\"1\" type=\"string\" tbranch=\"\" thumbtitle=\"0\" meta_id=\"1\"/>\n    <Category src=\"IPTC:Category\" index=\"1\" readonly=\"0\" required=\"0\" multi=\"0\" report=\"1\" type=\"string\" tbranch=\"\" thumbtitle=\"0\" meta_id=\"2\"/>\n    <SupplCategory src=\"IPTC:SupplementalCategories\" index=\"1\" readonly=\"0\" required=\"0\" multi=\"0\" report=\"1\" type=\"string\" tbranch=\"\" thumbtitle=\"0\" meta_id=\"3\"/>\n    <Keywords src=\"IPTC:Keywords\" index=\"1\" readonly=\"0\" required=\"0\" multi=\"1\" report=\"1\" type=\"string\" tbranch=\"\" separator=\";\" thumbtitle=\"0\" meta_id=\"4\"/>\n    <SpecialInstruct src=\"IPTC:SpecialInstructions\" index=\"1\" readonly=\"0\" required=\"0\" multi=\"0\" report=\"1\" type=\"string\" tbranch=\"\" thumbtitle=\"0\" meta_id=\"5\"/>\n    <Date src=\"IPTC:DateCreated\" index=\"1\" readonly=\"0\" required=\"0\" multi=\"0\" report=\"1\" type=\"date\" tbranch=\"\" thumbtitle=\"0\" meta_id=\"6\"/>\n    <Byline src=\"IPTC:By-line\" index=\"1\" readonly=\"0\" required=\"0\" multi=\"0\" report=\"1\" type=\"string\" tbranch=\"\" thumbtitle=\"0\" meta_id=\"7\"/>\n    <BylineTitle src=\"IPTC:By-lineTitle\" index=\"1\" readonly=\"0\" required=\"0\" multi=\"0\" report=\"1\" type=\"string\" tbranch=\"\" thumbtitle=\"0\" meta_id=\"8\"/>\n    <City src=\"IPTC:City\" index=\"1\" readonly=\"0\" required=\"0\" multi=\"0\" report=\"1\" type=\"string\" tbranch=\"\" thumbtitle=\"0\" meta_id=\"9\"/>\n    <Province src=\"IPTC:Province-State\" index=\"1\" readonly=\"0\" required=\"0\" multi=\"0\" report=\"1\" type=\"string\" tbranch=\"\" thumbtitle=\"0\" meta_id=\"10\"/>\n    <Country src=\"IPTC:Country-PrimaryLocationName\" index=\"1\" readonly=\"0\" required=\"0\" multi=\"0\" report=\"1\" type=\"string\" tbranch=\"\" thumbtitle=\"0\" meta_id=\"11\"/>\n    <OriginalRef src=\"IPTC:OriginalTransmissionReference\" index=\"1\" readonly=\"0\" required=\"0\" multi=\"0\" report=\"1\" type=\"string\" tbranch=\"\" thumbtitle=\"0\" meta_id=\"12\"/>\n    <Headline src=\"IPTC:Headline\" index=\"1\" readonly=\"0\" required=\"0\" multi=\"0\" report=\"1\" type=\"string\" tbranch=\"\" thumbtitle=\"0\" meta_id=\"13\"/>\n    <Credit src=\"IPTC:Credit\" index=\"1\" readonly=\"0\" required=\"0\" multi=\"0\" report=\"1\" type=\"string\" tbranch=\"\" thumbtitle=\"0\" meta_id=\"14\"/>\n    <Source src=\"IPTC:Source\" index=\"1\" readonly=\"0\" required=\"0\" multi=\"0\" report=\"1\" type=\"string\" tbranch=\"\" thumbtitle=\"0\" meta_id=\"15\"/>\n    <Caption src=\"IPTC:Caption-Abstract\" index=\"1\" readonly=\"0\" required=\"0\" multi=\"0\" report=\"1\" type=\"string\" tbranch=\"\" thumbtitle=\"0\" meta_id=\"16\"/>\n    <CaptionWriter src=\"IPTC:Writer-Editor\" index=\"1\" readonly=\"0\" required=\"0\" multi=\"0\" report=\"1\" type=\"string\" tbranch=\"\" thumbtitle=\"0\" meta_id=\"17\"/>\n    <Longitude src=\"GPS:GPSLongitude\" index=\"1\" readonly=\"1\" required=\"0\" multi=\"0\" report=\"1\" type=\"string\" tbranch=\"\" thumbtitle=\"0\" meta_id=\"18\"/>\n    <Latitude src=\"GPS:GPSLatitude\" index=\"1\" readonly=\"1\" required=\"0\" multi=\"0\" report=\"1\" type=\"string\" tbranch=\"\" thumbtitle=\"0\" meta_id=\"19\"/>\n    <CameraModel src=\"IFD0:Model\" index=\"1\" readonly=\"1\" required=\"0\" multi=\"0\" report=\"1\" type=\"string\" tbranch=\"\" thumbtitle=\"0\" meta_id=\"20\"/>\n    <FileName src=\"Phraseanet:tf-filename\" index=\"1\" readonly=\"1\" required=\"0\" multi=\"0\" report=\"1\" type=\"text\" tbranch=\"\" thumbtitle=\"0\" meta_id=\"21\"/>\n    <FilePath src=\"Phraseanet:tf-filepath\" index=\"1\" readonly=\"1\" required=\"0\" multi=\"0\" report=\"1\" type=\"text\" tbranch=\"\" thumbtitle=\"0\" meta_id=\"22\"/>\n    <Recordid src=\"Phraseanet:tf-recordid\" index=\"1\" readonly=\"1\" required=\"0\" multi=\"0\" report=\"1\" type=\"number\" tbranch=\"\" thumbtitle=\"0\" meta_id=\"23\"/>\n    <MimeType src=\"Phraseanet:tf-mimetype\" index=\"1\" readonly=\"1\" required=\"0\" multi=\"0\" report=\"1\" type=\"text\" tbranch=\"\" thumbtitle=\"0\" meta_id=\"24\"/>\n    <Size src=\"Phraseanet:tf-size\" index=\"1\" readonly=\"1\" required=\"0\" multi=\"0\" report=\"1\" type=\"number\" tbranch=\"\" thumbtitle=\"0\" meta_id=\"25\"/>\n    <Extension src=\"Phraseanet:tf-extension\" index=\"1\" readonly=\"1\" required=\"0\" multi=\"0\" report=\"1\" type=\"text\" tbranch=\"\" thumbtitle=\"0\" meta_id=\"26\"/>\n    <Width src=\"Phraseanet:tf-width\" index=\"1\" readonly=\"1\" required=\"0\" multi=\"0\" report=\"1\" type=\"number\" tbranch=\"\" thumbtitle=\"0\" meta_id=\"27\"/>\n    <Height src=\"Phraseanet:tf-height\" index=\"1\" readonly=\"1\" required=\"0\" multi=\"0\" report=\"1\" type=\"number\" tbranch=\"\" thumbtitle=\"0\" meta_id=\"28\"/>\n    <Bits src=\"Phraseanet:tf-bits\" index=\"1\" readonly=\"1\" required=\"0\" multi=\"0\" report=\"1\" type=\"number\" tbranch=\"\" thumbtitle=\"0\" meta_id=\"29\"/>\n    <Channels src=\"Phraseanet:tf-channels\" index=\"1\" readonly=\"1\" required=\"0\" multi=\"0\" report=\"1\" type=\"number\" tbranch=\"\" thumbtitle=\"0\" meta_id=\"30\"/>\n  </description>\n  <statbits>\n    <bit n=\"4\">Online</bit>\n  </statbits>\n</record>\n','','2012-04-27 02:06:34','2012-04-27 02:06:31'),(3,'cterms','','','0000-00-00 00:00:00','2012-04-27 02:06:31'),(4,'indexes','1','','2012-04-27 02:06:31','2012-04-27 02:06:31'),(5,'ToU','','fr_FR','0000-00-00 00:00:00','2012-04-27 02:06:31'),(6,'ToU','','ar_SA','0000-00-00 00:00:00','2012-04-27 02:06:31'),(7,'ToU','','de_DE','0000-00-00 00:00:00','2012-04-27 02:06:31'),(8,'ToU','','en_GB','0000-00-00 00:00:00','2012-04-27 02:06:31'),(9,'version','3.7.0.0.a2','','2012-04-27 02:06:33','0000-00-00 00:00:00');
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
-- Table structure for table `quest`
--

DROP TABLE IF EXISTS `quest`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `quest` (
  `id` int(11) unsigned NOT NULL DEFAULT '0',
  `logid` int(11) unsigned NOT NULL DEFAULT '0',
  `date` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `askquest` varchar(128) COLLATE utf8_unicode_ci NOT NULL,
  `nbrep` int(11) unsigned NOT NULL DEFAULT '0',
  `coll_id` text COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `quest`
--

LOCK TABLES `quest` WRITE;
/*!40000 ALTER TABLE `quest` DISABLE KEYS */;
/*!40000 ALTER TABLE `quest` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `record`
--

DROP TABLE IF EXISTS `record`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `record` (
  `coll_id` int(11) unsigned NOT NULL DEFAULT '0',
  `record_id` int(11) unsigned NOT NULL DEFAULT '0',
  `parent_record_id` int(11) unsigned NOT NULL DEFAULT '0',
  `status` bigint(20) unsigned NOT NULL DEFAULT '0',
  `sha256` char(64) CHARACTER SET ascii COLLATE ascii_bin DEFAULT NULL,
  `uuid` char(36) CHARACTER SET ascii DEFAULT NULL,
  `xml` longblob NOT NULL,
  `moddate` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `credate` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `work` int(1) unsigned NOT NULL DEFAULT '0',
  `subdefwork` int(11) unsigned NOT NULL DEFAULT '0',
  `subdeftime` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `type` enum('image','video','audio','document','flash','unknown') CHARACTER SET ascii COLLATE ascii_bin NOT NULL DEFAULT 'unknown',
  `jeton` int(11) unsigned NOT NULL DEFAULT '0',
  `bitly` char(10) COLLATE utf8_unicode_ci DEFAULT NULL,
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
-- Table structure for table `scptinfo`
--

DROP TABLE IF EXISTS `scptinfo`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `scptinfo` (
  `bitid` int(11) unsigned NOT NULL DEFAULT '0',
  `lastpassage` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`bitid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `scptinfo`
--

LOCK TABLES `scptinfo` WRITE;
/*!40000 ALTER TABLE `scptinfo` DISABLE KEYS */;
/*!40000 ALTER TABLE `scptinfo` ENABLE KEYS */;
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
  `inbase` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `width` int(10) unsigned NOT NULL DEFAULT '0',
  `height` int(10) unsigned NOT NULL DEFAULT '0',
  `mime` char(64) COLLATE utf8_unicode_ci NOT NULL,
  `size` bigint(20) unsigned NOT NULL DEFAULT '0',
  `cd_idx` int(11) unsigned NOT NULL DEFAULT '0',
  `substit` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `dispatched` tinyint(1) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`subdef_id`),
  UNIQUE KEY `unicite` (`record_id`,`name`),
  KEY `name` (`name`),
  KEY `record_id` (`record_id`),
  KEY `cd_idx` (`cd_idx`)
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
INSERT INTO `uids` VALUES (1,'BASE'),(2,'COLL'),(1,'CURRENT_CD'),(1,'EXPORTS'),(1,'IDX'),(1,'KEYWORDS'),(1,'LOG'),(1,'PROP'),(1,'QUEST'),(1,'RECORD'),(1,'THESAURUS'),(1,'XPATH');
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

-- Dump completed on 2012-10-02 11:10:10
