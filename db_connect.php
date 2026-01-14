<?php
/**
 * db_connect.php
 * ไฟล์สำหรับเชื่อมต่อฐานข้อมูล MySQL ของระบบ Thepha Helpdesk
 * PHP version 7.0 or higher
 */

// โหลด environment variables จาก .env
require_once __DIR__ . '/env_loader.php';

// --- 1. ตั้งค่าการเชื่อมต่อฐานข้อมูลจาก .env ---
$servername = env('DB_HOST', 'localhost');
$username = env('DB_USERNAME', 'root');
$password = env('DB_PASSWORD', '');
$dbname = env('DB_NAME', 'thepha_helpdesk');

// --- 2. ตั้งค่า Timezone และ Character Set ของ PHP ---
date_default_timezone_set('Asia/Bangkok');

// --- 3. สร้างการเชื่อมต่อด้วย mysqli (Object-oriented style) ---
try {
    $conn = new mysqli($servername, $username, $password, $dbname);

    if ($conn->connect_error) {
        throw new Exception("Connection failed: " . $conn->connect_error);
    }

    if (!$conn->set_charset("utf8mb4")) {
        throw new Exception("Error loading character set utf8mb4: " . $conn->error);
    }

} catch (Exception $e) {
    http_response_code(500);
    die("ERROR: ไม่สามารถเชื่อมต่อกับฐานข้อมูลได้ กรุณาตรวจสอบการตั้งค่า");
}

// ไม่ต้องปิดการเชื่อมต่อ ($conn->close();) ในไฟล์นี้
// เพราะเราจะนำไฟล์นี้ไป include ในไฟล์อื่นๆ เพื่อใช้งานตัวแปร $conn ต่อไป
// ไม่ใส่ ?>