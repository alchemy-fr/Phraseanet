-- MySQL dump 10.16  Distrib 10.1.12-MariaDB, for debian-linux-gnu (x86_64)
--
-- Host: localhost    Database: db_master
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
  `asciiname` varchar(50) COLLATE utf8_unicode_ci NOT NULL,
  `label_en` char(50) COLLATE utf8_unicode_ci DEFAULT NULL,
  `label_fr` char(50) COLLATE utf8_unicode_ci DEFAULT NULL,
  `label_de` char(50) COLLATE utf8_unicode_ci DEFAULT NULL,
  `label_nl` char(50) COLLATE utf8_unicode_ci DEFAULT NULL,
  `prefs` text COLLATE utf8_unicode_ci NOT NULL,
  `logo` longblob NOT NULL,
  `majLogo` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `pub_wm` enum('none','wm','stamp') CHARACTER SET ascii COLLATE ascii_bin NOT NULL DEFAULT 'none',
  PRIMARY KEY (`coll_id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `coll`
--

LOCK TABLES `coll` WRITE;
/*!40000 ALTER TABLE `coll` DISABLE KEYS */;
INSERT INTO `coll` VALUES (1,'test',NULL,NULL,NULL,NULL,'<?xml version=\"1.0\" encoding=\"UTF-8\"?>\r\n<baseprefs>\r\n    <status>0</status>\r\n    <sugestedValues></sugestedValues>\r\n</baseprefs>','','0000-00-00 00:00:00','none');
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
  `mask_and` int(11) unsigned NOT NULL DEFAULT '0',
  `mask_xor` int(11) unsigned NOT NULL DEFAULT '0',
  `ord` int(11) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`site`,`usr_id`,`coll_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `collusr`
--

LOCK TABLES `collusr` WRITE;
/*!40000 ALTER TABLE `collusr` DISABLE KEYS */;
INSERT INTO `collusr` VALUES ('LPel+kDbqDgAKPI2',1,1,0,0,0);
/*!40000 ALTER TABLE `collusr` ENABLE KEYS */;
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
  KEY `sit_session` (`sit_session`),
  KEY `user` (`user`),
  KEY `date` (`date`),
  KEY `nav` (`nav`),
  KEY `os` (`os`),
  KEY `res` (`res`),
  KEY `version` (`version`),
  KEY `os_nav` (`os`,`nav`),
  KEY `date_site` (`site`,`date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `log`
--

LOCK TABLES `log` WRITE;
/*!40000 ALTER TABLE `log` DISABLE KEYS */;
/*!40000 ALTER TABLE `log` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `log_colls`
--

DROP TABLE IF EXISTS `log_colls`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `log_colls` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `log_id` int(11) unsigned NOT NULL,
  `coll_id` int(11) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `couple` (`log_id`,`coll_id`),
  KEY `log_id` (`log_id`),
  KEY `coll_id` (`coll_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `log_colls`
--

LOCK TABLES `log_colls` WRITE;
/*!40000 ALTER TABLE `log_colls` DISABLE KEYS */;
/*!40000 ALTER TABLE `log_colls` ENABLE KEYS */;
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
  `final` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `comment` text COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`id`),
  KEY `log_id` (`log_id`),
  KEY `record_id` (`record_id`),
  KEY `action` (`action`),
  KEY `date` (`date`),
  KEY `final` (`final`)
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
  `search` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `results` int(11) unsigned DEFAULT '0',
  `coll_id` text COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`id`),
  KEY `log_id` (`log_id`),
  KEY `search` (`search`),
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
  `referrer` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `site_id` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`id`),
  KEY `referrer` (`referrer`),
  KEY `log_id` (`log_id`),
  KEY `date` (`date`),
  KEY `record_id` (`record_id`),
  KEY `site_id` (`site_id`)
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
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
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
  `aggregable` int(5) NOT NULL DEFAULT '0',
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
  `label_en` char(128) COLLATE utf8_unicode_ci DEFAULT NULL,
  `label_fr` char(128) COLLATE utf8_unicode_ci DEFAULT NULL,
  `label_de` char(128) COLLATE utf8_unicode_ci DEFAULT NULL,
  `label_nl` char(128) COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`),
  UNIQUE KEY `dces_element` (`dces_element`),
  KEY `indexable` (`indexable`),
  KEY `readonly` (`readonly`),
  KEY `required` (`required`)
) ENGINE=InnoDB AUTO_INCREMENT=22 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `metadatas_structure`
--

LOCK TABLES `metadatas_structure` WRITE;
/*!40000 ALTER TABLE `metadatas_structure` DISABLE KEYS */;
INSERT INTO `metadatas_structure` VALUES (1,'Object','IPTC:ObjectName',0,0,0,0,1,'string','','','0',0,1,1,NULL,NULL,0,NULL,NULL,NULL,NULL),(2,'Category','IPTC:Category',0,0,0,0,1,'string','','','0',0,1,2,NULL,NULL,0,NULL,NULL,NULL,NULL),(3,'SupplCategory','IPTC:SupplementalCategories',0,0,0,0,1,'string','',';','0',1,1,3,NULL,NULL,0,NULL,NULL,NULL,NULL),(4,'Keywords','IPTC:Keywords',0,0,0,0,1,'string','',';','0',1,1,4,NULL,NULL,0,NULL,NULL,NULL,NULL),(5,'SpecialInstruct','IPTC:SpecialInstructions',0,0,0,0,1,'string','','','0',0,1,5,NULL,NULL,0,NULL,NULL,NULL,NULL),(6,'Date','IPTC:DateCreated',0,0,0,0,1,'date','','','0',0,1,6,NULL,NULL,0,NULL,NULL,NULL,NULL),(7,'Byline','IPTC:By-line',0,0,0,0,1,'string','','','0',0,1,7,NULL,NULL,0,NULL,NULL,NULL,NULL),(8,'BylineTitle','IPTC:By-lineTitle',0,0,0,0,1,'string','','','0',0,1,8,NULL,NULL,0,NULL,NULL,NULL,NULL),(9,'City','IPTC:City',0,0,0,0,1,'string','','','0',0,1,9,NULL,NULL,0,NULL,NULL,NULL,NULL),(10,'Province','IPTC:Province-State',0,0,0,0,1,'string','','','0',0,1,10,NULL,NULL,0,NULL,NULL,NULL,NULL),(11,'Country','IPTC:Country-PrimaryLocationName',0,0,0,0,1,'string','','','0',0,1,11,NULL,NULL,0,NULL,NULL,NULL,NULL),(12,'OriginalRef','IPTC:OriginalTransmissionReference',0,0,0,0,1,'string','','','0',0,1,12,NULL,NULL,0,NULL,NULL,NULL,NULL),(13,'Headline','IPTC:Headline',0,0,0,0,1,'string','','','1',0,1,13,NULL,NULL,0,NULL,NULL,NULL,NULL),(14,'Credit','IPTC:Credit',0,0,0,0,1,'string','','','0',0,1,14,NULL,NULL,0,NULL,NULL,NULL,NULL),(15,'Source','IPTC:Source',0,0,0,0,1,'string','','','0',0,1,15,NULL,NULL,0,NULL,NULL,NULL,NULL),(16,'Caption','IPTC:Caption-Abstract',0,0,0,0,1,'string','','','0',0,1,16,NULL,NULL,0,NULL,NULL,NULL,NULL),(17,'CaptionWriter','IPTC:Writer-Editor',0,0,0,0,1,'string','','','0',0,1,17,NULL,NULL,0,NULL,NULL,NULL,NULL),(18,'Longitude','GPS:GPSLongitude',1,0,0,0,1,'string','','','0',0,1,18,NULL,NULL,0,NULL,NULL,NULL,NULL),(19,'Latitude','GPS:GPSLatitude',1,0,0,0,1,'string','','','0',0,1,19,NULL,NULL,0,NULL,NULL,NULL,NULL),(20,'CameraModel','IFD0:Model',1,0,0,0,1,'string','','','0',0,1,20,NULL,NULL,0,NULL,NULL,NULL,NULL),(21,'FileName','Phraseanet:tf-basename',1,0,0,0,1,'text','','','0',0,1,21,NULL,NULL,0,NULL,NULL,NULL,NULL);
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
INSERT INTO `pref` VALUES (1,'thesaurus','','','0000-00-00 00:00:00','2018-10-22 06:03:10'),(2,'structure','<?xml version=\"1.0\" encoding=\"UTF-8\" standalone=\"yes\"?>\n<record modification_date=\"20181022060416\">\n  <path>/vagrant/datas/db_master/documents</path>\n  <subdefs>\n    <subdefgroup name=\"image\">\n      <subdef class=\"preview\" name=\"preview\" downloadable=\"true\">\n        <path>/vagrant/datas/db_master/subdefs</path>\n        <size>1024</size>\n        <method>resample</method>\n        <dpi>72</dpi>\n        <strip>no</strip>\n        <quality>75</quality>\n        <meta>yes</meta>\n        <devices>screen</devices>\n        <mediatype>image</mediatype>\n        <label lang=\"fr\">Prévisualisation</label>\n        <label lang=\"en\">Preview</label>\n      </subdef>\n      <subdef class=\"thumbnail\" name=\"thumbnail\" downloadable=\"true\">\n        <path>/vagrant/datas/db_master/subdefs</path>\n        <size>240</size>\n        <method>resample</method>\n        <dpi>72</dpi>\n        <strip>yes</strip>\n        <quality>75</quality>\n        <meta>no</meta>\n        <devices>screen</devices>\n        <mediatype>image</mediatype>\n        <label lang=\"fr\">Vignette</label>\n        <label lang=\"en\">Thumbnail</label>\n      </subdef>\n      <subdef class=\"preview\" name=\"preview_mobile\" downloadable=\"false\">\n        <size>480</size>\n        <resolution>72</resolution>\n        <strip>yes</strip>\n        <quality>75</quality>\n        <path>/vagrant/datas/db_master/subdefs</path>\n        <mediatype>image</mediatype>\n        <meta>no</meta>\n        <devices>handheld</devices>\n        <label lang=\"fr\">Prévisualisation Mobile</label>\n        <label lang=\"en\">Mobile Preview</label>\n      </subdef>\n      <subdef class=\"thumbnail\" name=\"thumbnail_mobile\" downloadable=\"false\">\n        <size>150</size>\n        <resolution>72</resolution>\n        <strip>yes</strip>\n        <quality>75</quality>\n        <path>/vagrant/datas/db_master/subdefs</path>\n        <mediatype>image</mediatype>\n        <meta>no</meta>\n        <devices>handheld</devices>\n        <label lang=\"fr\">Vignette mobile</label>\n        <label lang=\"en\">Mobile Thumbnail</label>\n      </subdef>\n    </subdefgroup>\n    <subdefgroup name=\"video\">\n      <subdef class=\"thumbnail\" name=\"thumbnail\" downloadable=\"false\">\n        <path>/vagrant/datas/db_master/subdefs</path>\n        <size>240</size>\n        <devices>screen</devices>\n        <mediatype>image</mediatype>\n        <writeDatas>no</writeDatas>\n        <label lang=\"fr\">Vignette</label>\n        <label lang=\"en\">Thumbnail</label>\n      </subdef>\n      <subdef class=\"thumbnail\" name=\"thumbnailgif\" downloadable=\"false\">\n        <path>/vagrant/datas/db_master/subdefs</path>\n        <size>240</size>\n        <mediatype>gif</mediatype>\n        <delay>150</delay>\n        <devices>screen</devices>\n        <writeDatas>no</writeDatas>\n        <label lang=\"fr\">Animation GIF</label>\n        <label lang=\"en\">GIF Animation</label>\n      </subdef>\n      <subdef class=\"preview\" name=\"preview\" downloadable=\"true\">\n        <path>/vagrant/datas/db_master/subdefs</path>\n        <size>748</size>\n        <mediatype>video</mediatype>\n        <writeDatas>yes</writeDatas>\n        <acodec>libfaac</acodec>\n        <vcodec>libx264</vcodec>\n        <devices>screen</devices>\n        <bitrate>1000</bitrate>\n        <audiobitrate>128</audiobitrate>\n        <audiosamplerate>48000</audiosamplerate>\n        <fps>25</fps>\n        <GOPsize>25</GOPsize>\n        <label lang=\"fr\">Prévisualisation</label>\n        <label lang=\"en\">Preview</label>\n      </subdef>\n      <subdef class=\"preview\" name=\"preview_webm\" downloadable=\"false\">\n        <path>/vagrant/datas/db_master/subdefs</path>\n        <size>748</size>\n        <mediatype>video</mediatype>\n        <devices>screen</devices>\n        <bitrate>1000</bitrate>\n        <audiobitrate>128</audiobitrate>\n        <audiosamplerate>48000</audiosamplerate>\n        <acodec>libvorbis</acodec>\n        <fps>25</fps>\n        <GOPsize>25</GOPsize>\n        <vcodec>libvpx</vcodec>\n        <label lang=\"fr\">Prévisualisation WebM</label>\n        <label lang=\"en\">WebM Preview</label>\n      </subdef>\n    </subdefgroup>\n    <subdefgroup name=\"audio\">\n      <subdef class=\"thumbnail\" name=\"thumbnail\" downloadable=\"true\">\n        <path>/vagrant/datas/db_master/subdefs</path>\n        <mediatype>image</mediatype>\n        <size>240</size>\n        <devices>screen</devices>\n        <writeDatas>no</writeDatas>\n        <label lang=\"fr\">Vignette</label>\n        <label lang=\"en\">Thumbnail</label>\n      </subdef>\n      <subdef class=\"preview\" name=\"preview\" downloadable=\"true\">\n        <path>/vagrant/datas/db_master/subdefs</path>\n        <mediatype>audio</mediatype>\n        <writeDatas>yes</writeDatas>\n        <audiobitrate>128</audiobitrate>\n        <audiosamplerate>48000</audiosamplerate>\n        <devices>screen</devices>\n        <label lang=\"fr\">Prévisualisation</label>\n        <label lang=\"en\">Preview</label>\n      </subdef>\n      <subdef class=\"preview\" name=\"preview_mobile\" downloadable=\"false\">\n        <path>/vagrant/datas/db_master/subdefs</path>\n        <mediatype>audio</mediatype>\n        <devices>handheld</devices>\n        <label lang=\"fr\">Prévisualisation Mobile</label>\n        <label lang=\"en\">Mobile Preview</label>\n      </subdef>\n    </subdefgroup>\n    <subdefgroup name=\"document\">\n      <subdef class=\"thumbnail\" name=\"thumbnail\" downloadable=\"false\">\n        <path>/vagrant/datas/db_master/subdefs</path>\n        <mediatype>image</mediatype>\n        <method>resample</method>\n        <dpi>72</dpi>\n        <size>240</size>\n        <writeDatas>no</writeDatas>\n        <devices>screen</devices>\n        <label lang=\"fr\">Vignette</label>\n        <label lang=\"en\">Thumbnail</label>\n      </subdef>\n      <subdef class=\"preview\" name=\"preview\" downloadable=\"false\">\n        <path>/vagrant/datas/db_master/subdefs</path>\n        <mediatype>flexpaper</mediatype>\n        <writeDatas>no</writeDatas>\n        <devices>screen</devices>\n        <label lang=\"fr\">Prévisualisation</label>\n        <label lang=\"en\">Preview</label>\n      </subdef>\n    </subdefgroup>\n    <subdefgroup name=\"flash\">\n      <subdef class=\"thumbnail\" name=\"thumbnail\" downloadable=\"false\">\n        <path>/vagrant/datas/db_master/subdefs</path>\n        <mediatype>image</mediatype>\n        <size>240</size>\n        <writeDatas>no</writeDatas>\n        <method>resample</method>\n        <dpi>72</dpi>\n        <devices>screen</devices>\n        <label lang=\"fr\">Vignette</label>\n        <label lang=\"en\">Thumbnail</label>\n      </subdef>\n      <subdef class=\"preview\" name=\"preview\" downloadable=\"false\">\n        <path>/vagrant/datas/db_master/subdefs</path>\n        <mediatype>image</mediatype>\n        <size>800</size>\n        <writeDatas>no</writeDatas>\n        <method>resample</method>\n        <dpi>72</dpi>\n        <devices>screen</devices>\n        <label lang=\"fr\">Prévisualisation</label>\n        <label lang=\"en\">Preview</label>\n      </subdef>\n    </subdefgroup>\n  </subdefs>\n  <description>\n    <Object src=\"IPTC:ObjectName\" index=\"1\" readonly=\"0\" required=\"0\" multi=\"0\" report=\"1\" business=\"0\" aggregable=\"0\" type=\"string\" tbranch=\"\" thumbtitle=\"0\" meta_id=\"1\" sorter=\"1\"/>\n    <Category src=\"IPTC:Category\" index=\"1\" readonly=\"0\" required=\"0\" multi=\"0\" report=\"1\" business=\"0\" aggregable=\"0\" type=\"string\" tbranch=\"\" thumbtitle=\"0\" meta_id=\"2\" sorter=\"2\"/>\n    <SupplCategory src=\"IPTC:SupplementalCategories\" index=\"1\" readonly=\"0\" required=\"0\" multi=\"1\" report=\"1\" business=\"0\" aggregable=\"0\" type=\"string\" tbranch=\"\" separator=\";\" thumbtitle=\"0\" meta_id=\"3\" sorter=\"3\"/>\n    <Keywords src=\"IPTC:Keywords\" index=\"1\" readonly=\"0\" required=\"0\" multi=\"1\" report=\"1\" business=\"0\" aggregable=\"0\" type=\"string\" tbranch=\"\" separator=\";\" thumbtitle=\"0\" meta_id=\"4\" sorter=\"4\"/>\n    <SpecialInstruct src=\"IPTC:SpecialInstructions\" index=\"1\" readonly=\"0\" required=\"0\" multi=\"0\" report=\"1\" business=\"0\" aggregable=\"0\" type=\"string\" tbranch=\"\" thumbtitle=\"0\" meta_id=\"5\" sorter=\"5\"/>\n    <Date src=\"IPTC:DateCreated\" index=\"1\" readonly=\"0\" required=\"0\" multi=\"0\" report=\"1\" business=\"0\" aggregable=\"0\" type=\"date\" tbranch=\"\" thumbtitle=\"0\" meta_id=\"6\" sorter=\"6\"/>\n    <Byline src=\"IPTC:By-line\" index=\"1\" readonly=\"0\" required=\"0\" multi=\"0\" report=\"1\" business=\"0\" aggregable=\"0\" type=\"string\" tbranch=\"\" thumbtitle=\"0\" meta_id=\"7\" sorter=\"7\"/>\n    <BylineTitle src=\"IPTC:By-lineTitle\" index=\"1\" readonly=\"0\" required=\"0\" multi=\"0\" report=\"1\" business=\"0\" aggregable=\"0\" type=\"string\" tbranch=\"\" thumbtitle=\"0\" meta_id=\"8\" sorter=\"8\"/>\n    <City src=\"IPTC:City\" index=\"1\" readonly=\"0\" required=\"0\" multi=\"0\" report=\"1\" business=\"0\" aggregable=\"0\" type=\"string\" tbranch=\"\" thumbtitle=\"0\" meta_id=\"9\" sorter=\"9\"/>\n    <Province src=\"IPTC:Province-State\" index=\"1\" readonly=\"0\" required=\"0\" multi=\"0\" report=\"1\" business=\"0\" aggregable=\"0\" type=\"string\" tbranch=\"\" thumbtitle=\"0\" meta_id=\"10\" sorter=\"10\"/>\n    <Country src=\"IPTC:Country-PrimaryLocationName\" index=\"1\" readonly=\"0\" required=\"0\" multi=\"0\" report=\"1\" business=\"0\" aggregable=\"0\" type=\"string\" tbranch=\"\" thumbtitle=\"0\" meta_id=\"11\" sorter=\"11\"/>\n    <OriginalRef src=\"IPTC:OriginalTransmissionReference\" index=\"1\" readonly=\"0\" required=\"0\" multi=\"0\" report=\"1\" business=\"0\" aggregable=\"0\" type=\"string\" tbranch=\"\" thumbtitle=\"0\" meta_id=\"12\" sorter=\"12\"/>\n    <Headline src=\"IPTC:Headline\" index=\"1\" readonly=\"0\" required=\"0\" multi=\"0\" report=\"1\" business=\"0\" aggregable=\"0\" type=\"string\" tbranch=\"\" thumbtitle=\"1\" meta_id=\"13\" sorter=\"13\"/>\n    <Credit src=\"IPTC:Credit\" index=\"1\" readonly=\"0\" required=\"0\" multi=\"0\" report=\"1\" business=\"0\" aggregable=\"0\" type=\"string\" tbranch=\"\" thumbtitle=\"0\" meta_id=\"14\" sorter=\"14\"/>\n    <Source src=\"IPTC:Source\" index=\"1\" readonly=\"0\" required=\"0\" multi=\"0\" report=\"1\" business=\"0\" aggregable=\"0\" type=\"string\" tbranch=\"\" thumbtitle=\"0\" meta_id=\"15\" sorter=\"15\"/>\n    <Caption src=\"IPTC:Caption-Abstract\" index=\"1\" readonly=\"0\" required=\"0\" multi=\"0\" report=\"1\" business=\"0\" aggregable=\"0\" type=\"string\" tbranch=\"\" thumbtitle=\"0\" meta_id=\"16\" sorter=\"16\"/>\n    <CaptionWriter src=\"IPTC:Writer-Editor\" index=\"1\" readonly=\"0\" required=\"0\" multi=\"0\" report=\"1\" business=\"0\" aggregable=\"0\" type=\"string\" tbranch=\"\" thumbtitle=\"0\" meta_id=\"17\" sorter=\"17\"/>\n    <Longitude src=\"GPS:GPSLongitude\" index=\"1\" readonly=\"1\" required=\"0\" multi=\"0\" report=\"1\" business=\"0\" aggregable=\"0\" type=\"string\" tbranch=\"\" thumbtitle=\"0\" meta_id=\"18\" sorter=\"18\"/>\n    <Latitude src=\"GPS:GPSLatitude\" index=\"1\" readonly=\"1\" required=\"0\" multi=\"0\" report=\"1\" business=\"0\" aggregable=\"0\" type=\"string\" tbranch=\"\" thumbtitle=\"0\" meta_id=\"19\" sorter=\"19\"/>\n    <CameraModel src=\"IFD0:Model\" index=\"1\" readonly=\"1\" required=\"0\" multi=\"0\" report=\"1\" business=\"0\" aggregable=\"0\" type=\"string\" tbranch=\"\" thumbtitle=\"0\" meta_id=\"20\" sorter=\"20\"/>\n    <FileName src=\"Phraseanet:tf-basename\" index=\"1\" readonly=\"1\" required=\"0\" multi=\"0\" report=\"1\" business=\"0\" aggregable=\"0\" type=\"text\" tbranch=\"\" thumbtitle=\"0\" meta_id=\"21\" sorter=\"21\"/>\n  </description>\n  <statbits>\n    <bit n=\"4\">Online</bit>\n  </statbits>\n</record>\n','','2018-10-22 06:04:16','2018-10-22 06:03:10'),(3,'cterms','','','0000-00-00 00:00:00','2018-10-22 06:03:11'),(4,'indexes','1','','2018-10-22 06:03:11','2018-10-22 06:03:11'),(5,'ToU','','fr_FR','0000-00-00 00:00:00','2018-10-22 06:03:12'),(6,'ToU','','ar_SA','0000-00-00 00:00:00','2018-10-22 06:03:12'),(7,'ToU','','de_DE','0000-00-00 00:00:00','2018-10-22 06:03:12'),(8,'ToU','','en_GB','0000-00-00 00:00:00','2018-10-22 06:03:12'),(9,'version','4.1.0-alpha.12','','2018-10-22 06:03:31','0000-00-00 00:00:00');
/*!40000 ALTER TABLE `pref` ENABLE KEYS */;
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
  `status` int(10) unsigned NOT NULL DEFAULT '0',
  `sha256` char(64) CHARACTER SET ascii COLLATE ascii_bin DEFAULT NULL,
  `uuid` char(36) CHARACTER SET ascii DEFAULT NULL,
  `xml` longblob NOT NULL,
  `moddate` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `credate` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `work` int(1) unsigned NOT NULL DEFAULT '0',
  `mime` char(255) COLLATE utf8_unicode_ci DEFAULT NULL,
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
  `width` int(10) unsigned NOT NULL DEFAULT '0',
  `height` int(10) unsigned NOT NULL DEFAULT '0',
  `mime` char(255) COLLATE utf8_unicode_ci NOT NULL,
  `size` bigint(20) unsigned NOT NULL DEFAULT '0',
  `substit` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `etag` char(32) COLLATE utf8_unicode_ci DEFAULT NULL,
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
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2018-10-22  6:45:54
