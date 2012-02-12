-- MySQL dump 10.13  Distrib 5.1.41, for debian-linux-gnu (x86_64)
--
-- Host: localhost    Database: cc_prod
-- ------------------------------------------------------
-- Server version	5.1.41-3ubuntu12.10

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
-- Table structure for table `courses`
--

DROP TABLE IF EXISTS `courses`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `courses` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `stream_id` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `courses.stream_id` (`stream_id`),
  CONSTRAINT `courses.stream_id` FOREIGN KEY (`stream_id`) REFERENCES `streams` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION
) ENGINE=InnoDB AUTO_INCREMENT=19 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `courses`
--

LOCK TABLES `courses` WRITE;
/*!40000 ALTER TABLE `courses` DISABLE KEYS */;
INSERT INTO `courses` VALUES (1,'Introduction to Artificial Intelligence',1),(2,'Introduction to Machine Learning',1),(3,'Introduction to Databases',1),(4,'CS 101',1),(5,'Software as a Service',1),(6,'Human-Computer Interaction',1),(7,'Natural Language Processing',1),(8,'Game Theory',1),(9,'Probabilistic Graphical Models',1),(10,'Cryptography',1),(11,'Design and Analysis of Algorithms I',1),(12,'Lean Launchpad',2),(13,'Technology Entrepreneurship',2),(14,'Anatomy',5),(15,'Making Green Buildings',3),(16,'Information Theory',4),(17,'Model Thinking',6),(18,'Computer Security',1);
/*!40000 ALTER TABLE `courses` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `initiatives`
--

DROP TABLE IF EXISTS `initiatives`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `initiatives` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL,
  `url` text,
  `description` text,
  `tooltip` varchar(255) DEFAULT NULL,
  `code` varchar(20) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `initiatives`
--

LOCK TABLES `initiatives` WRITE;
/*!40000 ALTER TABLE `initiatives` DISABLE KEYS */;
INSERT INTO `initiatives` VALUES (1,'Coursera','http://www.coursera.org',NULL,'Coursera by Stanford University','COURSERA'),(2,'MITx','http://mitx.mit.edu/',NULL,'MITx by MIT','MITX'),(3,'Udacity','http://www.udacity.com/',NULL,'Udacity by Know Labs','UDACITY');
/*!40000 ALTER TABLE `initiatives` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `instructors`
--

DROP TABLE IF EXISTS `instructors`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `instructors` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `homepage` text,
  `university_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `university_id` (`university_id`),
  CONSTRAINT `university_id` FOREIGN KEY (`university_id`) REFERENCES `universities` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION
) ENGINE=InnoDB AUTO_INCREMENT=25 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `instructors`
--

LOCK TABLES `instructors` WRITE;
/*!40000 ALTER TABLE `instructors` DISABLE KEYS */;
INSERT INTO `instructors` VALUES (1,'Sebastian Thrun',NULL,NULL),(2,'Peter Norvig',NULL,NULL),(3,'Jennifer Widom',NULL,NULL),(4,'Andrew Ng',NULL,NULL),(5,'Nick Parlante',NULL,NULL),(6,'Armando Fox',NULL,NULL),(7,'David Patterson',NULL,NULL),(8,'Scott Klemmer',NULL,NULL),(9,'Chris Manning',NULL,NULL),(10,'Dan Jurafsky',NULL,NULL),(11,'Matthew Jackson',NULL,NULL),(12,'Yoav Shoham',NULL,NULL),(13,'Daphne Koller',NULL,NULL),(14,'Dan Boneh',NULL,NULL),(15,'Tim Roughgarden',NULL,NULL),(16,'Steve Blank',NULL,NULL),(17,'Chuck Eesley',NULL,NULL),(18,'Sakti Srivastava',NULL,NULL),(19,'Martin Fischer',NULL,NULL),(20,'Tsachy Weissman',NULL,NULL),(21,'Scott E Page',NULL,NULL),(22,'John Mitchell',NULL,NULL),(23,'Dawn Song',NULL,NULL),(24,'David Evans',NULL,NULL);
/*!40000 ALTER TABLE `instructors` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `migration_versions`
--

DROP TABLE IF EXISTS `migration_versions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `migration_versions` (
  `version` varchar(255) NOT NULL,
  PRIMARY KEY (`version`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `migration_versions`
--

LOCK TABLES `migration_versions` WRITE;
/*!40000 ALTER TABLE `migration_versions` DISABLE KEYS */;
INSERT INTO `migration_versions` VALUES ('20111124063306'),('20111201043223'),('20111205030316'),('20120119010131'),('20120126014843');
/*!40000 ALTER TABLE `migration_versions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `offerings`
--

DROP TABLE IF EXISTS `offerings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `offerings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `course_id` int(11) NOT NULL,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `exact_dates_know` tinyint(1) DEFAULT NULL,
  `created` timestamp NULL DEFAULT NULL,
  `modified` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `url` text,
  `video_intro` text,
  `length` int(11) DEFAULT NULL,
  `name` varchar(255) DEFAULT NULL,
  `initiative_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `offerings.course_id` (`course_id`),
  KEY `initiative_id` (`initiative_id`),
  CONSTRAINT `initiative_id` FOREIGN KEY (`initiative_id`) REFERENCES `initiatives` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  CONSTRAINT `offerings.course_id` FOREIGN KEY (`course_id`) REFERENCES `courses` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION
) ENGINE=InnoDB AUTO_INCREMENT=23 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `offerings`
--

LOCK TABLES `offerings` WRITE;
/*!40000 ALTER TABLE `offerings` DISABLE KEYS */;
INSERT INTO `offerings` VALUES (1,1,'2011-10-01',NULL,0,NULL,'2012-01-27 04:19:34','https://www.ai-class.com/','http://www.youtube.com/watch?feature=player_embedded&v=BnIJ7Ba5Sr4',10,NULL,3),(2,2,'2011-10-01',NULL,0,NULL,'2012-01-27 04:19:34','http://www.ml-class.org/','http://www.youtube.com/watch?v=e0WKJLovaZg&feature=player_embedded',10,NULL,1),(3,3,'2011-10-01',NULL,0,NULL,'2012-01-27 04:19:34','http://www.db-class.org/','http://www.youtube.com/watch?v=ShjrtAQmIVg&feature=player_embedded',9,NULL,1),(4,4,'2012-02-26',NULL,0,NULL,'2012-01-27 04:19:34','http://www.cs101-class.org/','http://www.youtube.com/watch?v=nnBbf8FG5Hw&feature=player_embedded',NULL,NULL,1),(6,2,'2012-02-26',NULL,0,NULL,'2012-01-27 04:19:34','http://jan2012.ml-class.org/','http://www.youtube.com/watch?v=e0WKJLovaZg&feature=player_embedded',10,NULL,1),(7,5,'2012-02-20',NULL,1,NULL,'2012-01-27 04:19:34','http://www.saas-class.org/','http://www.youtube.com/watch?v=4PZD0rOlWH8&feature=player_embedded',5,NULL,1),(8,6,'2012-02-26',NULL,0,NULL,'2012-01-27 04:19:34','http://www.hci-class.org/','http://www.youtube.com/watch?v=GBwLAqOjbrA&feature=player_embedded',NULL,NULL,1),(9,7,'2012-02-26',NULL,0,NULL,'2012-01-27 04:19:34','http://www.nlp-class.org/','http://www.youtube.com/watch?v=Fnr4A0tcU-M&feature=player_embedded',8,NULL,1),(10,8,'2012-02-26',NULL,0,NULL,'2012-01-30 19:55:37','http://www.game-theory-class.org/','http://www.youtube.com/watch?v=_UcRbnJoDKc&feature=player_embedded',NULL,NULL,1),(11,9,'2012-02-26',NULL,0,NULL,'2012-01-27 04:19:34','http://www.pgm-class.org/','http://www.youtube.com/watch?v=S1r6nZjMQC8&feature=player_embedded',10,NULL,1),(12,10,'2012-02-26',NULL,0,NULL,'2012-01-27 04:19:34','http://www.crypto-class.org/','http://www.youtube.com/watch?v=QVL1gjS20XU&feature=player_embedded',NULL,NULL,1),(13,11,'2012-02-26',NULL,0,NULL,'2012-01-27 04:19:34','http://www.algo-class.org/','http://www.youtube.com/watch?v=_gr7o5ynhnw&feature=player_embedded',5,NULL,1),(14,12,'2012-02-26',NULL,0,NULL,'2012-01-30 19:55:47','http://www.launchpad-class.org/','http://www.youtube.com/watch?v=AINJpHoefDc&feature=player_embedded',NULL,NULL,1),(15,13,'2012-02-26',NULL,0,NULL,'2012-01-27 04:19:34','http://www.venture-class.org/','http://www.youtube.com/watch?v=Muy9vyHPUAM&feature=player_embedded',NULL,NULL,1),(16,14,'2012-03-05',NULL,1,NULL,'2012-01-27 04:19:34','http://www.anatomy-class.org/','http://www.youtube.com/watch?v=mvXOZK5IdDs&feature=player_embedded',NULL,NULL,1),(17,15,'2012-02-26',NULL,0,NULL,'2012-01-27 04:19:34','http://www.greenbuilding-class.org/','http://www.youtube.com/watch?v=uoyzbgx3iTo&feature=player_embedded',NULL,NULL,1),(18,16,'2012-03-20',NULL,0,NULL,'2012-01-27 04:19:34','http://www.infotheory-class.org/','http://www.youtube.com/watch?v=6M3Ych6nkTk&feature=player_embedded',NULL,NULL,1),(19,17,'2012-02-26',NULL,0,NULL,'2012-01-27 04:19:34','http://www.modelthinker-class.org/','http://www.youtube.com/watch?v=y7CPoSeYQaQ&feature=player_embedded',NULL,NULL,1),(20,18,'2012-02-26',NULL,0,NULL,'2012-01-30 19:55:16','http://www.security-class.org/','http://www.youtube.com/watch?v=esxpFYJqEUg&feature=player_embedded',NULL,NULL,1),(21,1,'2012-02-20',NULL,1,NULL,'2012-02-01 15:22:30','http://www.udacity.com/cs#373','http://www.youtube.com/watch?v=bdCnb0EFAzk',7,'CS 373: Programming a Robotic Car',3),(22,4,'2012-02-20',NULL,1,NULL,'2012-02-01 15:21:32','http://www.udacity.com/cs#101','http://www.youtube.com/watch?v=BQHMLD9bwq4',7,'CS 101: Building a Search Engine',3);
/*!40000 ALTER TABLE `offerings` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `offerings_instructors`
--

DROP TABLE IF EXISTS `offerings_instructors`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `offerings_instructors` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `offering_id` int(11) NOT NULL,
  `instructor_id` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `offerings_instructors.offering_id` (`offering_id`),
  KEY `offerings_instructors.instructor_id` (`instructor_id`),
  CONSTRAINT `offerings_instructors.offering_id` FOREIGN KEY (`offering_id`) REFERENCES `offerings` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  CONSTRAINT `offerings_instructors.instructor_id` FOREIGN KEY (`instructor_id`) REFERENCES `instructors` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION
) ENGINE=InnoDB AUTO_INCREMENT=29 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `offerings_instructors`
--

LOCK TABLES `offerings_instructors` WRITE;
/*!40000 ALTER TABLE `offerings_instructors` DISABLE KEYS */;
INSERT INTO `offerings_instructors` VALUES (1,1,1),(2,1,2),(3,2,4),(4,6,4),(5,3,3),(6,4,5),(7,7,6),(8,7,7),(9,8,8),(10,9,9),(11,9,10),(12,10,11),(13,10,12),(14,11,13),(15,12,14),(16,13,15),(17,14,16),(18,15,17),(19,16,18),(20,17,19),(21,18,20),(22,19,21),(23,20,14),(24,20,22),(25,20,23),(26,21,1),(27,22,24),(28,22,1);
/*!40000 ALTER TABLE `offerings_instructors` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `streams`
--

DROP TABLE IF EXISTS `streams`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `streams` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `streams`
--

LOCK TABLES `streams` WRITE;
/*!40000 ALTER TABLE `streams` DISABLE KEYS */;
INSERT INTO `streams` VALUES (1,'Computer Science'),(2,'Entrepreneurship'),(3,'Civil Engineering'),(4,'Electrical Engineering'),(5,'Medicine'),(6,'Complex Systems');
/*!40000 ALTER TABLE `streams` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `universities`
--

DROP TABLE IF EXISTS `universities`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `universities` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `url` text,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `universities`
--

LOCK TABLES `universities` WRITE;
/*!40000 ALTER TABLE `universities` DISABLE KEYS */;
/*!40000 ALTER TABLE `universities` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2012-02-12  3:57:27
