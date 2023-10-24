DROP DATABASE `chow_chooser`;
CREATE DATABASE `chow_chooser` /*!40100 DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci */ /*!80016 DEFAULT ENCRYPTION='N' */;
USE `chow_chooser`;
CREATE TABLE `lobbies` (
  `lobby_id` int NOT NULL AUTO_INCREMENT,
  `access_key` varchar(45) NOT NULL,
  `confirmation_code` varchar(45) DEFAULT NULL,
  PRIMARY KEY (`lobby_id`)
) ENGINE=InnoDB AUTO_INCREMENT=125 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
CREATE TABLE `food_items` (
  `food_id` int NOT NULL AUTO_INCREMENT,
  `item_description` varchar(100) DEFAULT NULL,
  `price` decimal(10,0) DEFAULT NULL,
  `lobby_id_idx` int NOT NULL,
  PRIMARY KEY (`food_id`),
  KEY `lobby_id_idx` (`lobby_id_idx`),
  CONSTRAINT `lobby_id` FOREIGN KEY (`lobby_id_idx`) REFERENCES `lobbies` (`lobby_id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
CREATE USER 'chowChooserAdmin'@'localhost' IDENTIFIED BY 'OexDTUj@xd#^3CZm';
GRANT ALL PRIVILEGES ON `chow_chooser`.* To 'chowChooserAdmin'@'localhost';
