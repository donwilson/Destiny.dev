-- MySQL dump 10.13  Distrib 5.5.56, for Linux (x86_64)
--
-- Host: localhost    Database: dev_destiny
-- ------------------------------------------------------
-- Server version	5.5.56

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
-- Table structure for table `setting`
--

DROP TABLE IF EXISTS `setting`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `setting` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `meta_key` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `meta_value` longtext COLLATE utf8_unicode_ci,
  PRIMARY KEY (`id`),
  UNIQUE KEY `meta_key` (`meta_key`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `setting`
--

LOCK TABLES `setting` WRITE;
/*!40000 ALTER TABLE `setting` DISABLE KEYS */;
INSERT INTO `setting` VALUES (1,'current_manifest','{\"pulled\":1498670297,\"version\":\"56578.17.04.12.1251-6\",\"data\":{\"version\":\"56578.17.04.12.1251-6\",\"mobileAssetContentPath\":\"\\/common\\/destiny_content\\/sqlite\\/asset\\/asset_sql_content_fd24e5f944d34a326e0e21817ea353ea.content\",\"mobileGearAssetDataBases\":[{\"version\":0,\"path\":\"\\/common\\/destiny_content\\/sqlite\\/asset\\/asset_sql_content_fd24e5f944d34a326e0e21817ea353ea.content\"},{\"version\":1,\"path\":\"\\/common\\/destiny_content\\/sqlite\\/asset\\/asset_sql_content_e563ae481f48cf708da20f4ab86ba8ed.content\"},{\"version\":2,\"path\":\"\\/common\\/destiny_content\\/sqlite\\/asset\\/asset_sql_content_e563ae481f48cf708da20f4ab86ba8ed.content\"}],\"mobileWorldContentPaths\":{\"en\":\"\\/common\\/destiny_content\\/sqlite\\/en\\/world_sql_content_966ad56a56415cee23a0050d20773546.content\",\"fr\":\"\\/common\\/destiny_content\\/sqlite\\/fr\\/world_sql_content_08385e1e997e9b35f110db5fcfe80d40.content\",\"es\":\"\\/common\\/destiny_content\\/sqlite\\/es\\/world_sql_content_4efa12d201879a8533031c899ddc1d47.content\",\"de\":\"\\/common\\/destiny_content\\/sqlite\\/de\\/world_sql_content_ad9fe4eebff3780e6ff8e8ffec0bda7d.content\",\"it\":\"\\/common\\/destiny_content\\/sqlite\\/it\\/world_sql_content_c96616abe595df1511270263a74bb32e.content\",\"ja\":\"\\/common\\/destiny_content\\/sqlite\\/ja\\/world_sql_content_55d16946eed15235aa53665d6c2016c6.content\",\"pt-br\":\"\\/common\\/destiny_content\\/sqlite\\/pt-br\\/world_sql_content_79f839ab26affc3e035a64c6870700f4.content\"},\"mobileGearCDN\":{\"Geometry\":\"\\/common\\/destiny_content\\/geometry\\/platform\\/mobile\\/geometry\",\"Texture\":\"\\/common\\/destiny_content\\/geometry\\/platform\\/mobile\\/textures\",\"PlateRegion\":\"\\/common\\/destiny_content\\/geometry\\/platform\\/mobile\\/plated_textures\",\"Gear\":\"\\/common\\/destiny_content\\/geometry\\/gear\",\"Shader\":\"\\/common\\/destiny_content\\/geometry\\/platform\\/mobile\\/shaders\"}}}');
/*!40000 ALTER TABLE `setting` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2017-07-06 14:08:28
