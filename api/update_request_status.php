<?php
session_start();
header('Content-Type: application/json');

require_once '../db_connect.php';
require_once '../config.php'; // เรียกใช้ไฟล์ config สำหรับ Telegram
require_once 'telegram_sender.php'; // เรียกใช้ฟังก์ชันส่งข้อความ

$response = ['success' => false, 'message' => ''];

// ตรวจสอบว่า Admin login อยู่หรือไม่
if (!isset($_SESSION['admin_id'])) {
    $response['message'] = 'กรุณา login ก่อนทำรายการ';
    echo json_encode($response);
    exit();
}

// ตรวจสอบว่ามี request_id ส่งมาหรือไม่
if (!isset($_POST['request_id'])) {
    $response['message'] = 'ไม่พบรหัสรายการที่ต้องการอัปเดต';
    echo json_encode($response);
    exit();
}

$requestId = (int)$_POST['request_id'];
$adminId = (int)$_SESSION['admin_id'];
$adminName = $_SESSION['admin_full_name'];
$newStatusId = 2; // 2 คือสถานะ "กำลังดำเนินการ"

try {
    // เตรียมคำสั่ง SQL เพื่ออัปเดต
    $stmt = $conn->prepare("UPDATE requests SET current_status_id = ?, admin_id = ? WHERE id = ? AND current_status_id = 1");
    if (!$stmt) {
        throw new Exception("Prepare failed: " . $conn->error);
    }
    
    $stmt->bind_param("iii", $newStatusId, $adminId, $requestId);

    // ประมวลผล
    if ($stmt->execute()) {
        if ($stmt->affected_rows > 0) {
            $response['success'] = true;

            // ===== ส่วนที่เพิ่มเข้ามา: ส่งการแจ้งเตือนผ่าน Telegram =====
            try {
                // ดึงข้อมูลเพิ่มเติมเพื่อสร้างข้อความ
                $info_stmt = $conn->prepare("
                    SELECT r.problem_description, l.location_name 
                    FROM requests r 
                    JOIN locations l ON r.location_id = l.id 
                    WHERE r.id = ?
                ");
                $info_stmt->bind_param("i", $requestId);
                $info_stmt->execute();
                $info_result = $info_stmt->get_result()->fetch_assoc();
                
                $problem = $info_result['problem_description'];
                $location = $info_result['location_name'];

                // สร้างข้อความ
                $message = "✅ <b>รับเรื่องแล้ว</b>\n\n";
                $message .= "<b>รหัส:</b> " . $requestId . "\n";
                $message .= "<b>ปัญหา:</b> " . htmlspecialchars($problem) . "\n";
                $message .= "<b>สถานที่:</b> " . htmlspecialchars($location) . "\n";
                $message .= "<b>ผู้รับเรื่อง:</b> " . htmlspecialchars($adminName);

                // ส่งข้อความ
                if (defined('TELEGRAM_BOT_TOKEN') && TELEGRAM_BOT_TOKEN !== 'YOUR_BOT_TOKEN_HERE') {
                    sendTelegramMessage(TELEGRAM_BOT_TOKEN, TELEGRAM_CHAT_ID, $message);
                }
                $info_stmt->close();

            } catch (Exception $e) {
                // หากการส่ง telegram มีปัญหา ก็ไม่ต้องทำอะไร ปล่อยให้ flow หลักทำงานต่อไป
                // error_log("Telegram notification on accept failed: " . $e->getMessage());
            }
            // ===== สิ้นสุดส่วนของ Telegram =====

        } else {
            throw new Exception("ไม่สามารถรับเรื่องได้ อาจมีผู้รับเรื่องไปแล้ว");
        }
    } else {
        throw new Exception("Execute failed: " . $stmt->error);
    }

    $stmt->close();

} catch (Exception $e) {
    $response['message'] = $e->getMessage();
}

$conn->close();
echo json_encode($response);
?>
