<?php
require_once '../../db_connect.php';

if (isset($_POST['action'])) {

    // --- จัดการการเพิ่มข้อมูล ---
    if ($_POST['action'] == 'add') {
        if (!empty($_POST['full_name']) && !empty($_POST['username']) && !empty($_POST['password']) && isset($_POST['status'])) {
            $fullName = $_POST['full_name'];
            $username = $_POST['username'];
            $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
            $status = $_POST['status'];
            $stmt = $conn->prepare("INSERT INTO admins (full_name, username, password, status) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("ssss", $fullName, $username, $password, $status);
            $stmt->execute();
            $stmt->close();
        }
    }

    // --- จัดการการแก้ไขข้อมูล ---
    if ($_POST['action'] == 'update') {
        if (!empty($_POST['id']) && !empty($_POST['full_name']) && !empty($_POST['username']) && isset($_POST['status'])) {
            $adminId = $_POST['id'];
            $fullName = $_POST['full_name'];
            $username = $_POST['username'];
            $status = $_POST['status'];

            // ถ้ามีการกรอกรหัสผ่านใหม่ ให้อัปเดตด้วย
            if (!empty($_POST['password'])) {
                $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
                $stmt = $conn->prepare("UPDATE admins SET full_name = ?, username = ?, password = ?, status = ? WHERE id = ?");
                $stmt->bind_param("ssssi", $fullName, $username, $password, $status, $adminId);
            } else {
                $stmt = $conn->prepare("UPDATE admins SET full_name = ?, username = ?, status = ? WHERE id = ?");
                $stmt->bind_param("sssi", $fullName, $username, $status, $adminId);
            }
            $stmt->execute();
            $stmt->close();
        }
    }

    // --- จัดการการลบข้อมูล ---
    if ($_POST['action'] == 'delete') {
        if (!empty($_POST['id'])) {
            $adminId = (int) $_POST['id'];
            // ตรวจสอบว่ามีงานที่ผูกกับ admin นี้หรือไม่
            $check = $conn->prepare("SELECT COUNT(*) as cnt FROM requests WHERE admin_id = ?");
            $check->bind_param("i", $adminId);
            $check->execute();
            $checkResult = $check->get_result()->fetch_assoc();
            $check->close();

            if ($checkResult['cnt'] > 0) {
                // มีงานผูกอยู่ ไม่สามารถลบได้
                session_start();
                $_SESSION['error_message'] = 'ไม่สามารถลบได้ เนื่องจากมีงานซ่อมที่ผูกกับเจ้าหน้าที่คนนี้อยู่';
            } else {
                $stmt = $conn->prepare("DELETE FROM admins WHERE id = ?");
                $stmt->bind_param("i", $adminId);
                $stmt->execute();
                $stmt->close();
            }
        }
    }
}

$conn->close();

// เมื่อประมวลผลเสร็จ ให้ redirect กลับไปหน้าเดิม
header('Location: ../../admin/manage_admins.php');
exit();
?>
