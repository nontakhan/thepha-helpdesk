<?php
header('Content-Type: application/json');
require_once '../db_connect.php';

$response = [
    'success' => false,
    'message' => ''
];

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $response['message'] = 'Invalid request method.';
    echo json_encode($response);
    exit();
}

// ตรวจสอบข้อมูลที่ส่งมา
if (!isset($_POST['request_id']) || !isset($_POST['rating'])) {
    $response['message'] = 'ข้อมูลไม่ครบถ้วน';
    echo json_encode($response);
    exit();
}

$request_id = (int)$_POST['request_id'];
$rating = (int)$_POST['rating'];
$comment = isset($_POST['comment']) ? trim($_POST['comment']) : '';
$rated_at = date('Y-m-d H:i:s');

try {
    // ใช้ Prepared Statement เพื่อความปลอดภัย
    $sql = "UPDATE requests SET 
                satisfaction_rating = ?, 
                satisfaction_comment = ?,
                rated_at = ?
            WHERE id = ? AND satisfaction_rating IS NULL"; // อัปเดตเฉพาะรายการที่ยังไม่มีการให้คะแนน

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("issi", $rating, $comment, $rated_at, $request_id);
    
    if ($stmt->execute()) {
        // ตรวจสอบว่ามีการอัปเดตแถวข้อมูลหรือไม่
        if ($stmt->affected_rows > 0) {
            $response['success'] = true;
            $response['message'] = 'ขอบคุณสำหรับคะแนนและความคิดเห็นครับ';
        } else {
            $response['message'] = 'รายการนี้ได้รับการให้คะแนนไปแล้ว';
        }
    } else {
        throw new Exception("Execute failed: " . $stmt->error);
    }

    $stmt->close();

} catch (Exception $e) {
    $response['message'] = "เกิดข้อผิดพลาด: " . $e->getMessage();
}

$conn->close();
echo json_encode($response);
?>
