<?php
require_once '../../db_connect.php';

if (isset($_POST['action'])) {

    // --- จัดการการเพิ่มข้อมูล ---
    if ($_POST['action'] == 'add') {
        if (!empty($_POST['holiday_date']) && !empty($_POST['holiday_name'])) {
            $holidayDate = $_POST['holiday_date'];
            $holidayName = $_POST['holiday_name'];
            $stmt = $conn->prepare("INSERT INTO holidays (holiday_date, holiday_name) VALUES (?, ?)");
            $stmt->bind_param("ss", $holidayDate, $holidayName);
            $stmt->execute();
            $stmt->close();
        }
    }

    // --- จัดการการแก้ไขข้อมูล ---
    if ($_POST['action'] == 'update') {
        if (!empty($_POST['id']) && !empty($_POST['holiday_date']) && !empty($_POST['holiday_name'])) {
            $holidayId = $_POST['id'];
            $holidayDate = $_POST['holiday_date'];
            $holidayName = $_POST['holiday_name'];
            $stmt = $conn->prepare("UPDATE holidays SET holiday_date = ?, holiday_name = ? WHERE id = ?");
            $stmt->bind_param("ssi", $holidayDate, $holidayName, $holidayId);
            $stmt->execute();
            $stmt->close();
        }
    }

    // --- จัดการการลบข้อมูล ---
    if ($_POST['action'] == 'delete') {
        if (!empty($_POST['id'])) {
            $holidayId = $_POST['id'];
            $stmt = $conn->prepare("DELETE FROM holidays WHERE id = ?");
            $stmt->bind_param("i", $holidayId);
            $stmt->execute();
            $stmt->close();
        }
    }
}

$conn->close();

// เมื่อประมวลผลเสร็จ ให้ redirect กลับไปหน้าเดิม
header('Location: ../../admin/manage_holidays.php');
exit();
?>
