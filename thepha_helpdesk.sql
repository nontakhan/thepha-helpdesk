/*
 Navicat Premium Data Transfer

 Source Server         : localhost
 Source Server Type    : MySQL
 Source Server Version : 100432
 Source Host           : localhost:3306
 Source Schema         : thepha_helpdesk

 Target Server Type    : MySQL
 Target Server Version : 100432
 File Encoding         : 65001

 Date: 26/06/2025 22:37:47
*/

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ----------------------------
-- Table structure for admins
-- ----------------------------
DROP TABLE IF EXISTS `admins`;
CREATE TABLE `admins`  (
  `id` int NOT NULL AUTO_INCREMENT,
  `username` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `password` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `full_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE INDEX `username`(`username` ASC) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 2 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of admins
-- ----------------------------
INSERT INTO `admins` VALUES (1, 'admin', '$2y$10$fc1/JRjS6z9C9u2ZSdxxZeuULipJLnjpIcMed0WdT4/ReU.LLPlfm', 'ผู้ดูแลระบบ');

-- ----------------------------
-- Table structure for categories
-- ----------------------------
DROP TABLE IF EXISTS `categories`;
CREATE TABLE `categories`  (
  `id` int NOT NULL AUTO_INCREMENT,
  `category_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 5 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of categories
-- ----------------------------
INSERT INTO `categories` VALUES (1, 'คอมพิวเตอร์');
INSERT INTO `categories` VALUES (2, 'โทรศัพท์ภายใน');
INSERT INTO `categories` VALUES (3, 'HOSxP');

-- ----------------------------
-- Table structure for locations
-- ----------------------------
DROP TABLE IF EXISTS `locations`;
CREATE TABLE `locations`  (
  `id` int NOT NULL AUTO_INCREMENT,
  `location_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `department_type` enum('หน่วยงานให้บริการ','หน่วยงานสนับสนุน') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'หน่วยงานสนับสนุน',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 9 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of locations
-- ----------------------------
INSERT INTO `locations` VALUES (1, 'ห้อง IT', 'หน่วยงานสนับสนุน');
INSERT INTO `locations` VALUES (2, 'ห้องหัวหน้าพยาบาล', 'หน่วยงานสนับสนุน');
INSERT INTO `locations` VALUES (3, 'ห้องผู้อำนวยการ', 'หน่วยงานสนับสนุน');
INSERT INTO `locations` VALUES (4, 'ห้องผอ.', 'หน่วยงานสนับสนุน');
INSERT INTO `locations` VALUES (5, 'ห้องสมุด', 'หน่วยงานสนับสนุน');
INSERT INTO `locations` VALUES (6, 'ห้องพัสดุ', 'หน่วยงานสนับสนุน');
INSERT INTO `locations` VALUES (8, 'xray', 'หน่วยงานให้บริการ');

-- ----------------------------
-- Table structure for reporters
-- ----------------------------
DROP TABLE IF EXISTS `reporters`;
CREATE TABLE `reporters`  (
  `id` int NOT NULL AUTO_INCREMENT,
  `reporter_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 6 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of reporters
-- ----------------------------
INSERT INTO `reporters` VALUES (1, 'นายแวอาลี แวสะแม');
INSERT INTO `reporters` VALUES (2, 'นายยศพิชา มุเด็น');
INSERT INTO `reporters` VALUES (3, 'นายสุวิทย์ อิสโร');
INSERT INTO `reporters` VALUES (4, 'นายภูริพัฒน์ กะมิง');

-- ----------------------------
-- Table structure for requests
-- ----------------------------
DROP TABLE IF EXISTS `requests`;
CREATE TABLE `requests`  (
  `id` int NOT NULL AUTO_INCREMENT,
  `request_date` datetime NOT NULL,
  `problem_description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `location_id` int NOT NULL,
  `reporter_id` int NOT NULL,
  `current_status_id` int NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp,
  `repair_date` datetime NULL DEFAULT NULL,
  `resolution_time_minutes` int NULL DEFAULT NULL,
  `admin_id` int NULL DEFAULT NULL,
  `category_id` int NULL DEFAULT NULL,
  `cause` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL,
  `solution` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL,
  `final_status_id` int NULL DEFAULT NULL,
  `is_phone_call` tinyint(1) NOT NULL DEFAULT 0,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `location_id`(`location_id` ASC) USING BTREE,
  INDEX `reporter_id`(`reporter_id` ASC) USING BTREE,
  INDEX `current_status_id`(`current_status_id` ASC) USING BTREE,
  INDEX `admin_id`(`admin_id` ASC) USING BTREE,
  INDEX `category_id`(`category_id` ASC) USING BTREE,
  INDEX `final_status_id`(`final_status_id` ASC) USING BTREE,
  CONSTRAINT `requests_ibfk_1` FOREIGN KEY (`location_id`) REFERENCES `locations` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `requests_ibfk_2` FOREIGN KEY (`reporter_id`) REFERENCES `reporters` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `requests_ibfk_3` FOREIGN KEY (`current_status_id`) REFERENCES `statuses` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `requests_ibfk_4` FOREIGN KEY (`admin_id`) REFERENCES `admins` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `requests_ibfk_5` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `requests_ibfk_6` FOREIGN KEY (`final_status_id`) REFERENCES `statuses` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE = InnoDB AUTO_INCREMENT = 12 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of requests
-- ----------------------------
INSERT INTO `requests` VALUES (2, '2025-06-24 21:42:00', 'คอมค้าง', 3, 1, 3, '2025-06-24 21:42:20', '2025-06-24 22:54:00', NULL, 1, 1, 'ค้าง', 'restart', 3, 0, '2025-06-24 22:54:54');
INSERT INTO `requests` VALUES (3, '2025-06-25 21:52:00', 'hosxp ใช้ไม่ได้', 2, 1, 2, '2025-06-25 21:53:04', NULL, NULL, 1, NULL, NULL, NULL, NULL, 0, '2025-06-26 21:04:31');
INSERT INTO `requests` VALUES (4, '2025-06-25 21:58:00', 'เน็ตช้ามากกก', 6, 3, 1, '2025-06-25 21:59:11', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, '2025-06-25 21:59:11');
INSERT INTO `requests` VALUES (5, '2025-06-25 22:01:00', 'wifi ช้า', 3, 2, 2, '2025-06-25 22:01:37', NULL, NULL, 1, NULL, NULL, NULL, NULL, 0, '2025-06-26 21:07:09');
INSERT INTO `requests` VALUES (6, '2025-06-25 22:05:00', 'โปรแกรม word ใช้งานไม่ได้', 5, 2, 2, '2025-06-25 22:06:10', NULL, NULL, 1, NULL, NULL, NULL, NULL, 0, '2025-06-26 22:15:43');
INSERT INTO `requests` VALUES (7, '2025-06-26 21:34:00', 'ทดสอบแจ้ง', 5, 4, 3, '2025-06-26 21:35:00', '2025-06-26 22:16:00', 42, 1, 1, 'สาเหตุ', 'restart', 3, 0, '2025-06-26 22:17:28');
INSERT INTO `requests` VALUES (8, '2025-06-26 21:35:00', '111', 4, 1, 1, '2025-06-26 21:35:31', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, '2025-06-26 21:35:31');
INSERT INTO `requests` VALUES (9, '2025-06-26 21:35:00', '2222', 3, 3, 1, '2025-06-26 21:35:47', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, '2025-06-26 21:35:47');
INSERT INTO `requests` VALUES (10, '2025-06-26 21:35:00', '333', 3, 1, 1, '2025-06-26 21:36:09', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, '2025-06-26 21:36:09');
INSERT INTO `requests` VALUES (11, '2025-06-26 21:36:00', '88888', 4, 3, 1, '2025-06-26 21:36:31', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, '2025-06-26 21:36:31');

-- ----------------------------
-- Table structure for statuses
-- ----------------------------
DROP TABLE IF EXISTS `statuses`;
CREATE TABLE `statuses`  (
  `id` int NOT NULL AUTO_INCREMENT,
  `status_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 6 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of statuses
-- ----------------------------
INSERT INTO `statuses` VALUES (1, 'รอรับเรื่อง');
INSERT INTO `statuses` VALUES (2, 'กำลังดำเนินการ');
INSERT INTO `statuses` VALUES (3, 'เสร็จสิ้น (ซ่อมได้)');
INSERT INTO `statuses` VALUES (4, 'ซ่อมไม่ได้ (รอจำหน่าย)');
INSERT INTO `statuses` VALUES (5, 'ส่งซ่อมภายนอก');

SET FOREIGN_KEY_CHECKS = 1;
