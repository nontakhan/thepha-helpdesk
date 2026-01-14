<?php
header('Content-Type: application/json');

require_once '../db_connect.php';
require_once '../config.php';
require_once 'telegram_sender.php';

$response = ['success' => false, 'message' => ''];

try {
    if (!isset($_POST['problem_description']) || !isset($_POST['location_id']) || !isset($_POST['reporter_id'])) {
        throw new Exception('กรุณากรอกข้อมูลที่จำเป็นให้ครบถ้วน');
    }

    $request_date = $_POST['request_date'];
    $request_time = $_POST['request_time'];
    $problem_description = trim($_POST['problem_description']);
    $location_id = (int)$_POST['location_id'];
    $reporter_id = (int)$_POST['reporter_id'];

    if (empty($request_date) || empty($request_time) || empty($problem_description) || empty($location_id) || empty($reporter_id)) {
        throw new Exception('ข้อมูลบางอย่างว่างเปล่า กรุณาตรวจสอบอีกครั้ง');
    }

    $request_datetime = $request_date . ' ' . $request_time . ':00';
    $initial_status_id = 1;

    $stmt = $conn->prepare(
        "INSERT INTO requests (request_date, problem_description, location_id, reporter_id, current_status_id) 
         VALUES (?, ?, ?, ?, ?)"
    );

    if ($stmt === false) {
        throw new Exception('Prepare statement failed: ' . $conn->error);
    }
    
    $stmt->bind_param("ssiii", $request_datetime, $problem_description, $location_id, $reporter_id, $initial_status_id);

    if ($stmt->execute()) {
        $response['success'] = true;
        $new_request_id = $stmt->insert_id;

        // --- ส่วนของการส่งข้อความไปที่ Telegram ---
        try {
            // ดึงชื่อสถานที่ (แบบ Prepared Statement)
            $stmt_loc = $conn->prepare("SELECT location_name FROM locations WHERE id = ?");
            $stmt_loc->bind_param("i", $location_id);
            $stmt_loc->execute();
            $result_loc = $stmt_loc->get_result();
            $location_name = ($result_loc->num_rows > 0) ? $result_loc->fetch_assoc()['location_name'] : "N/A";
            $stmt_loc->close();

            // ดึงชื่อผู้แจ้ง (แบบ Prepared Statement)
            $stmt_rep = $conn->prepare("SELECT reporter_name FROM reporters WHERE id = ?");
            $stmt_rep->bind_param("i", $reporter_id);
            $stmt_rep->execute();
            $result_rep = $stmt_rep->get_result();
            $reporter_name = ($result_rep->num_rows > 0) ? $result_rep->fetch_assoc()['reporter_name'] : "N/A";
            $stmt_rep->close();
            
            // สร้างข้อความที่จะส่ง
            $message = "<b>🔔 แจ้งซ่อมรายการใหม่!</b>\n\n";
            $message .= "<b>รหัส:</b> " . $new_request_id . "\n";
            $message .= "<b>วันที่:</b> " . date('d/m/Y H:i', strtotime($request_datetime)) . "\n";
            $message .= "<b>สถานที่:</b> " . htmlspecialchars($location_name) . "\n";
            $message .= "<b>ผู้แจ้ง:</b> " . htmlspecialchars($reporter_name) . "\n\n";
            $message .= "<b>ปัญหา:</b>\n" . htmlspecialchars($problem_description);

            // ส่งข้อความ
            if (defined('TELEGRAM_BOT_TOKEN') && TELEGRAM_BOT_TOKEN !== 'YOUR_BOT_TOKEN_HERE') {
                sendTelegramMessage(TELEGRAM_BOT_TOKEN, TELEGRAM_CHAT_ID, $message);
            }

        } catch (Exception $e) {
            // หากการส่ง telegram มีปัญหา ก็ไม่ต้องทำอะไร ปล่อยให้ flow หลักทำงานต่อไป
            // error_log("Telegram sending failed: " . $e->getMessage());
        }
        // --- สิ้นสุดส่วนของ Telegram ---

    } else {
        throw new Exception('Execute statement failed: ' . $stmt->error);
    }

    $stmt->close();

} catch (Exception $e) {
    $response['message'] = $e->getMessage();
}

$conn->close();

echo json_encode($response);
?>
