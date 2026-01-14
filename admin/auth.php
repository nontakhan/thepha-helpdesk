<?php
session_start();
require_once '../db_connect.php';

// ตรวจสอบว่ามีการส่งข้อมูลมาจากฟอร์มหรือไม่
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];

    // ป้องกัน SQL Injection
    $stmt = $conn->prepare("SELECT id, password, full_name FROM admins WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $admin = $result->fetch_assoc();

        // ตรวจสอบรหัสผ่านที่เข้ารหัสไว้
        if (password_verify($password, $admin['password'])) {
            // รหัสผ่านถูกต้อง, สร้าง session
            $_SESSION['admin_id'] = $admin['id'];
            $_SESSION['admin_full_name'] = $admin['full_name'];
            
            // ส่งไปหน้า dashboard
            header('Location: dashboard.php');
            exit();
        }
    }

    // ถ้า username หรือ password ผิด
    $_SESSION['error_message'] = 'Username หรือ Password ไม่ถูกต้อง!';
    header('Location: login.php');
    exit();

} else {
    // ถ้าไม่ได้เข้ามาหน้านี้ผ่าน POST method
    header('Location: login.php');
    exit();
}
?>
