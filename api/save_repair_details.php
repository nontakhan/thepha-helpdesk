<?php
session_start();
// เริ่ม Output Buffering
ob_start();

header('Content-Type: application/json');
require_once '../db_connect.php';
require_once '../config.php';
require_once 'telegram_sender.php';

$response = [
    'success' => false,
    'message' => '',
    'redirect_url' => ''
];

if (!isset($_SESSION['admin_id'])) {
    $response['message'] = 'เซสชั่นหมดอายุ, กรุณาเข้าสู่ระบบใหม่อีกครั้ง';
    ob_end_clean();
    echo json_encode($response);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $response['message'] = 'Invalid request method.';
    ob_end_clean();
    echo json_encode($response);
    exit();
}

// ตรวจสอบ action
$action = $_POST['action'] ?? 'update_full';

try {
    // ==========================================
    // กรณีที่ 1: บันทึก/แก้ไขข้อมูล (Update)
    // ==========================================
    if ($action == 'update_full') {
        // ตรวจสอบ ID
        if (empty($_POST['request_id'])) {
            throw new Exception("ไม่พบรหัสรายการ (request_id)");
        }

        // 1. รับค่าจากฟอร์ม
        $request_id = (int)$_POST['request_id'];
        $request_date_str = $_POST['request_date'];
        $reporter_id = (int)$_POST['reporter_id'];
        $location_id = (int)$_POST['location_id'];
        $problem_description = trim($_POST['problem_description']);
        
        $repair_date_str = $_POST['repair_date'];
        $category_id = (int)$_POST['category_id'];
        $cause = trim($_POST['cause']);
        $solution = trim($_POST['solution']);
        $final_status_id = (int)$_POST['final_status_id'];
        $is_phone_call = isset($_POST['is_phone_call']) ? 1 : 0;
        $admin_id = (int)$_SESSION['admin_id'];
        $admin_name = $_SESSION['admin_full_name'];

        // 2. คำนวณเวลาที่ใช้ในการแก้ไข
        $request_datetime = new DateTime($request_date_str);
        $repair_datetime = new DateTime($repair_date_str);
        $interval = $request_datetime->diff($repair_datetime);
        $resolution_time_minutes = ($interval->days * 24 * 60) + ($interval->h * 60) + $interval->i;

        // 3. อัปเดตข้อมูล
        $sql = "UPDATE requests SET
                    request_date = ?, reporter_id = ?, location_id = ?, problem_description = ?,
                    repair_date = ?, resolution_time_minutes = ?, admin_id = ?, category_id = ?,
                    cause = ?, solution = ?, final_status_id = ?, is_phone_call = ?, current_status_id = ? 
                WHERE id = ?";

        $stmt = $conn->prepare($sql);
        if (!$stmt) { throw new Exception("Prepare failed: " . $conn->error); }

        $stmt->bind_param("siissiiissiiii",
            $request_date_str, $reporter_id, $location_id, $problem_description,
            $repair_date_str, $resolution_time_minutes, $admin_id, $category_id,
            $cause, $solution, $final_status_id, $is_phone_call, $final_status_id, 
            $request_id
        );

        if ($stmt->execute()) {
            $response['success'] = true;
            $response['message'] = 'บันทึกข้อมูลเรียบร้อยแล้ว';
            $response['redirect_url'] = 'requests_list.php'; // กลับไปหน้ารายการ

            // 4. ส่ง Telegram (เฉพาะตอนบันทึก)
            try {
                $status_stmt = $conn->prepare("SELECT status_name FROM statuses WHERE id = ?");
                $status_stmt->bind_param("i", $final_status_id);
                $status_stmt->execute();
                $status_res = $status_stmt->get_result()->fetch_assoc();
                $status_name = $status_res['status_name'] ?? '-';
                $status_stmt->close();

                $msg = "✅ <b>งานซ่อมเสร็จสิ้น/อัปเดต</b>\n\n";
                $msg .= "<b>รหัส:</b> " . $request_id . "\n";
                $msg .= "<b>ปัญหา:</b> " . htmlspecialchars($problem_description) . "\n";
                $msg .= "<b>สถานะ:</b> " . htmlspecialchars($status_name) . "\n";
                $msg .= "<b>ผู้ดำเนินการ:</b> " . htmlspecialchars($admin_name) . "\n";
                $msg .= "<b>วิธีแก้ไข:</b> " . htmlspecialchars($solution);

                if (defined('TELEGRAM_BOT_TOKEN') && TELEGRAM_BOT_TOKEN !== 'YOUR_BOT_TOKEN_HERE') {
                    sendTelegramMessage(TELEGRAM_BOT_TOKEN, TELEGRAM_CHAT_ID, $msg);
                }
            } catch (Exception $e) {}
        } else {
            throw new Exception("Execute failed: " . $stmt->error);
        }
        $stmt->close();
    }
    // ==========================================
    // กรณีที่ 2: ลบข้อมูล (Delete) - เพิ่มส่วนนี้เข้ามา
    // ==========================================
    elseif ($action == 'delete') {
        if (empty($_POST['request_id'])) {
            throw new Exception("ไม่พบรหัสรายการที่ต้องการลบ");
        }

        $request_id = (int)$_POST['request_id'];

        $stmt = $conn->prepare("DELETE FROM requests WHERE id = ?");
        $stmt->bind_param("i", $request_id);

        if ($stmt->execute()) {
            if ($stmt->affected_rows > 0) {
                $response['success'] = true;
                $response['message'] = 'ลบรายการเรียบร้อยแล้ว';
                $response['redirect_url'] = 'requests_list.php'; // ลบเสร็จกลับไปหน้ารายการ
            } else {
                throw new Exception('ไม่พบรายการที่ต้องการลบ (อาจถูกลบไปแล้ว)');
            }
        } else {
            throw new Exception("Execute failed: " . $stmt->error);
        }
        $stmt->close();
    }
    else {
        throw new Exception("Unknown action: " . $action);
    }

} catch (Exception $e) {
    $response['message'] = $e->getMessage();
}

$conn->close();
ob_end_clean();
echo json_encode($response);
?>