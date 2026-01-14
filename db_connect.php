<?php
/**
 * db_connect.php
 * ไฟล์สำหรับเชื่อมต่อฐานข้อมูล MySQL ของระบบ Thepha Helpdesk
 * * PHP version 7.0 or higher
 */

// --- 1. ตั้งค่าการเชื่อมต่อฐานข้อมูล ---
// กรุณาแก้ไขค่าเหล่านี้ให้ตรงกับการตั้งค่าเซิร์ฟเวอร์ของคุณ
$servername = "thephahospital.go.th";      // โดยทั่วไปคือ "localhost"
$username = "thephaho_helpdesk";             // ชื่อผู้ใช้สำหรับฐานข้อมูล (default ของ XAMPP คือ "root")
$password = "_Computer0";                 // รหัสผ่านสำหรับฐานข้อมูล (default ของ XAMPP คือ "")
$dbname = "thephaho_helpdesk";    // ชื่อฐานข้อมูลที่เราจะใช้

// --- 2. ตั้งค่า Timezone และ Character Set ของ PHP ---
// เพื่อให้แน่ใจว่าเวลาและภาษาไทยถูกต้องเสมอ
date_default_timezone_set('Asia/Bangkok');

// --- 3. สร้างการเชื่อมต่อด้วย mysqli (Object-oriented style) ---
try {
    // สร้าง object ของ mysqli
    $conn = new mysqli($servername, $username, $password, $dbname);

    // ตรวจสอบว่าการเชื่อมต่อมีข้อผิดพลาดหรือไม่
    if ($conn->connect_error) {
        // หากมีข้อผิดพลาด ให้โยน Exception ออกไปเพื่อหยุดการทำงาน
        throw new Exception("Connection failed: " . $conn->connect_error);
    }

    // ตั้งค่า Character Set ของการเชื่อมต่อเป็น utf8mb4 เพื่อรองรับภาษาไทยและ emoji
    if (!$conn->set_charset("utf8mb4")) {
        // หากตั้งค่า charset ไม่สำเร็จ ให้แสดงข้อผิดพลาด
        throw new Exception("Error loading character set utf8mb4: " . $conn->error);
    }

} catch (Exception $e) {
    // จัดการกับข้อผิดพลาดทั้งหมดที่เกิดขึ้นใน try block
    // แสดงข้อความข้อผิดพลาดที่เข้าใจง่าย และหยุดการทำงานของสคริปต์
    // ในระบบจริง (Production) ควรจะเขียน error ลง log file แทนการแสดงผลหน้าจอ
    http_response_code(500); // Internal Server Error
    die("ERROR: ไม่สามารถเชื่อมต่อกับฐานข้อมูลได้ กรุณาตรวจสอบการตั้งค่า " . $e->getMessage());
}

// ไม่ต้องปิดการเชื่อมต่อ ($conn->close();) ในไฟล์นี้
// เพราะเราจะนำไฟล์นี้ไป include ในไฟล์อื่นๆ เพื่อใช้งานตัวแปร $conn ต่อไป
?>