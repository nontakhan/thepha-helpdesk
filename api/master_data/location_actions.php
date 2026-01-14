<?php
require_once '../../db_connect.php';

if (isset($_POST['action'])) {
    
    // --- จัดการการเพิ่มข้อมูล ---
    if ($_POST['action'] == 'add') {
        if (!empty($_POST['location_name']) && !empty($_POST['department_type'])) {
            $locationName = $_POST['location_name'];
            $departmentType = $_POST['department_type'];

            $stmt = $conn->prepare("INSERT INTO locations (location_name, department_type) VALUES (?, ?)");
            $stmt->bind_param("ss", $locationName, $departmentType);
            $stmt->execute();
            $stmt->close();
        }
    }

    // --- จัดการการแก้ไขข้อมูล ---
    if ($_POST['action'] == 'update') {
        if (!empty($_POST['id']) && !empty($_POST['location_name']) && !empty($_POST['department_type'])) {
            $locationId = $_POST['id'];
            $locationName = $_POST['location_name'];
            $departmentType = $_POST['department_type'];

            $stmt = $conn->prepare("UPDATE locations SET location_name = ?, department_type = ? WHERE id = ?");
            $stmt->bind_param("ssi", $locationName, $departmentType, $locationId);
            $stmt->execute();
            $stmt->close();
        }
    }

    // --- จัดการการลบข้อมูล (เหมือนเดิม) ---
    if ($_POST['action'] == 'delete') {
        if (!empty($_POST['id'])) {
            $locationId = $_POST['id'];
            $stmt = $conn->prepare("DELETE FROM locations WHERE id = ?");
            $stmt->bind_param("i", $locationId);
            $stmt->execute();
            $stmt->close();
        }
    }
}

$conn->close();

// เมื่อประมวลผลเสร็จ ให้ redirect กลับไปหน้าเดิม
header('Location: ../../admin/manage_locations.php');
exit();
?>
