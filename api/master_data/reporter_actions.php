<?php
require_once '../../db_connect.php';

if (isset($_POST['action'])) {
    
    // --- จัดการการเพิ่มข้อมูล ---
    if ($_POST['action'] == 'add') {
        if (!empty($_POST['reporter_name']) && isset($_POST['status'])) {
            $reporterName = $_POST['reporter_name'];
            $status = $_POST['status'];
            $stmt = $conn->prepare("INSERT INTO reporters (reporter_name, status) VALUES (?, ?)");
            $stmt->bind_param("ss", $reporterName, $status);
            $stmt->execute();
            $stmt->close();
        }
    }

    // --- จัดการการแก้ไขข้อมูล ---
    if ($_POST['action'] == 'update') {
        if (!empty($_POST['id']) && !empty($_POST['reporter_name']) && isset($_POST['status'])) {
            $reporterId = $_POST['id'];
            $reporterName = $_POST['reporter_name'];
            $status = $_POST['status'];
            $stmt = $conn->prepare("UPDATE reporters SET reporter_name = ?, status = ? WHERE id = ?");
            $stmt->bind_param("ssi", $reporterName, $status, $reporterId);
            $stmt->execute();
            $stmt->close();
        }
    }

    // --- จัดการการลบข้อมูล (เหมือนเดิม) ---
    if ($_POST['action'] == 'delete') {
        if (!empty($_POST['id'])) {
            $reporterId = $_POST['id'];
            $stmt = $conn->prepare("DELETE FROM reporters WHERE id = ?");
            $stmt->bind_param("i", $reporterId);
            $stmt->execute();
            $stmt->close();
        }
    }
}

$conn->close();

// เมื่อประมวลผลเสร็จ ให้ redirect กลับไปหน้าเดิม
header('Location: ../../admin/manage_reporters.php');
exit();
?>
