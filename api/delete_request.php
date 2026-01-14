<?php
session_start();
require_once '../db_connect.php';

// ฟังก์ชันสำหรับ Redirect พร้อมข้อความ
function redirect_with_message($url, $message, $type = 'success') {
    $_SESSION['flash_message'] = [
        'type' => $type,
        'text' => $message
    ];
    header("Location: " . $url);
    exit();
}

if (!isset($_SESSION['admin_id'])) {
    redirect_with_message('../admin/login.php', 'กรุณา login ก่อนทำรายการ', 'error');
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect_with_message('../admin/requests_list.php', 'Invalid request.', 'error');
}

if (empty($_POST['request_id'])) {
    redirect_with_message('../admin/requests_list.php', 'ไม่พบรหัสรายการที่ต้องการลบ', 'error');
}

$request_id = (int)$_POST['request_id'];

try {
    $stmt = $conn->prepare("DELETE FROM requests WHERE id = ?");
    $stmt->bind_param("i", $request_id);

    if ($stmt->execute()) {
        if ($stmt->affected_rows > 0) {
            redirect_with_message('../admin/requests_list.php', 'ลบรายการแจ้งซ่อมสำเร็จแล้ว', 'success');
        } else {
            throw new Exception('ไม่พบรายการที่ต้องการลบ (อาจถูกลบไปแล้ว)');
        }
    } else {
        throw new Exception('ไม่สามารถลบข้อมูลได้: ' . $stmt->error);
    }
    $stmt->close();

} catch (Exception $e) {
    redirect_with_message('../admin/requests_list.php', $e->getMessage(), 'error');
}

$conn->close();
?>
