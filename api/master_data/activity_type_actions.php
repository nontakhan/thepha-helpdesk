<?php
require_once '../../db_connect.php';

if (isset($_POST['action'])) {
    
    if ($_POST['action'] == 'add') {
        if (!empty($_POST['type_name']) && !empty($_POST['color'])) {
            $typeName = $_POST['type_name'];
            $color = $_POST['color'];
            $stmt = $conn->prepare("INSERT INTO activity_types (type_name, color) VALUES (?, ?)");
            $stmt->bind_param("ss", $typeName, $color);
            $stmt->execute();
            $stmt->close();
        }
    }

    if ($_POST['action'] == 'update') {
        if (!empty($_POST['id']) && !empty($_POST['type_name']) && !empty($_POST['color'])) {
            $typeId = $_POST['id'];
            $typeName = $_POST['type_name'];
            $color = $_POST['color'];
            $stmt = $conn->prepare("UPDATE activity_types SET type_name = ?, color = ? WHERE id = ?");
            $stmt->bind_param("ssi", $typeName, $color, $typeId);
            $stmt->execute();
            $stmt->close();
        }
    }

    if ($_POST['action'] == 'delete') {
        if (!empty($_POST['id'])) {
            $typeId = $_POST['id'];
            $stmt = $conn->prepare("DELETE FROM activity_types WHERE id = ?");
            $stmt->bind_param("i", $typeId);
            $stmt->execute();
            $stmt->close();
        }
    }
}

$conn->close();
header('Location: ../../admin/manage_activity_types.php');
exit();
?>
