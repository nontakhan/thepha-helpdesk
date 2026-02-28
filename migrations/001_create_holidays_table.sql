-- สร้างตาราง holidays สำหรับเก็บข้อมูลวันหยุด
CREATE TABLE IF NOT EXISTS `holidays` (
  `id` int NOT NULL AUTO_INCREMENT,
  `holiday_date` date NOT NULL,
  `holiday_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp,
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE INDEX `holiday_date_unique`(`holiday_date` ASC) USING BTREE
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COLLATE utf8mb4_unicode_ci ROW_FORMAT = Dynamic;

-- เพิ่มฟิลด์ status ในตาราง admins สำหรับ enable/disable
ALTER TABLE `admins` ADD COLUMN IF NOT EXISTS `status` enum('Y','N') NOT NULL DEFAULT 'Y' COMMENT 'Y=ใช้งาน, N=ปิดใช้งาน';
