<?php
session_start();
header('Content-Type: application/json');
require_once '../db_connect.php';

$response = ['success' => false, 'message' => ''];

if (!isset($_SESSION['admin_id'])) {
    $response['message'] = 'กรุณา login ก่อนทำรายการ (Session expired)';
    echo json_encode($response);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['action'])) {
    $response['message'] = 'Invalid request method or missing action.';
    echo json_encode($response);
    exit();
}

$admin_id = (int) $_SESSION['admin_id'];
$action = $_POST['action'];

try {
    // Action: สร้างกิจกรรมใหม่
    if ($action == 'create') {
        $activity_type_id = (int) $_POST['activity_type_id'];
        $title = trim($_POST['title']);
        $start_time_str = $_POST['start_time'];
        $end_time_str = !empty($_POST['end_time']) ? $_POST['end_time'] : null;
        if (empty($activity_type_id) || empty($title) || empty($start_time_str)) {
            throw new Exception('กรุณากรอกข้อมูลประเภท, รายละเอียด และเวลาเริ่มต้นให้ครบถ้วน');
        }
        $duration_minutes = null;
        if ($end_time_str) {
            $startTime = new DateTime($start_time_str);
            $endTime = new DateTime($end_time_str);
            if ($endTime < $startTime) {
                throw new Exception('เวลาสิ้นสุดต้องไม่น้อยกว่าเวลาเริ่มต้น');
            }
            $interval = $startTime->diff($endTime);
            $duration_minutes = ($interval->days * 24 * 60) + ($interval->h * 60) + $interval->i;
        }
        $stmt = $conn->prepare("INSERT INTO activities (admin_id, activity_type_id, title, start_time, end_time, duration_minutes) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("iisssi", $admin_id, $activity_type_id, $title, $start_time_str, $end_time_str, $duration_minutes);
        if ($stmt->execute()) {
            $response['success'] = true;
            $response['message'] = 'บันทึกกิจกรรมเรียบร้อยแล้ว';
        } else {
            throw new Exception('ไม่สามารถบันทึกข้อมูลได้: ' . $stmt->error);
        }
        $stmt->close();
    }
    // Action: สิ้นสุดกิจกรรมเก่า
    elseif ($action == 'finish') {
        $activity_id = (int) $_POST['activity_id'];
        $end_time_str = $_POST['end_time'];
        $stmt_get_start = $conn->prepare("SELECT start_time FROM activities WHERE id = ? AND admin_id = ?");
        $stmt_get_start->bind_param("ii", $activity_id, $admin_id);
        $stmt_get_start->execute();
        $result = $stmt_get_start->get_result();
        if ($result->num_rows === 0) {
            throw new Exception('ไม่พบกิจกรรมนี้ หรือคุณไม่มีสิทธิ์แก้ไข');
        }
        $activity = $result->fetch_assoc();
        $stmt_get_start->close();
        $startTime = new DateTime($activity['start_time']);
        $endTime = new DateTime($end_time_str);
        if ($endTime < $startTime) {
            throw new Exception('เวลาสิ้นสุดต้องไม่น้อยกว่าเวลาเริ่มต้น');
        }
        $interval = $startTime->diff($endTime);
        $duration_minutes = ($interval->days * 24 * 60) + ($interval->h * 60) + $interval->i;
        $stmt_update = $conn->prepare("UPDATE activities SET end_time = ?, duration_minutes = ? WHERE id = ?");
        $stmt_update->bind_param("sii", $end_time_str, $duration_minutes, $activity_id);
        if ($stmt_update->execute()) {
            $response['success'] = true;
            $response['message'] = 'อัปเดตเวลาสิ้นสุดเรียบร้อยแล้ว';
        } else {
            throw new Exception('ไม่สามารถอัปเดตข้อมูลได้: ' . $stmt_update->error);
        }
        $stmt_update->close();
    }
    // Action: แก้ไขกิจกรรม
    elseif ($action == 'update') {
        $activity_id = (int) $_POST['activity_id'];
        $activity_type_id = isset($_POST['activity_type_id']) ? (int) $_POST['activity_type_id'] : 0;
        $title = trim($_POST['title']);
        $start_time_str = $_POST['start_time'];
        $end_time_str = $_POST['end_time'];

        if (empty($activity_id) || empty($title) || empty($start_time_str) || empty($end_time_str)) {
            throw new Exception('กรุณากรอกข้อมูลในฟอร์มแก้ไขให้ครบถ้วน');
        }

        $startTime = new DateTime($start_time_str);
        $endTime = new DateTime($end_time_str);
        if ($endTime < $startTime) {
            throw new Exception('เวลาสิ้นสุดต้องไม่น้อยกว่าเวลาเริ่มต้น');
        }
        $interval = $startTime->diff($endTime);
        $duration_minutes = ($interval->days * 24 * 60) + ($interval->h * 60) + $interval->i;

        // อัปเดตรวมถึงประเภทกิจกรรมด้วย
        if ($activity_type_id > 0) {
            $stmt_update = $conn->prepare("UPDATE activities SET activity_type_id = ?, title = ?, start_time = ?, end_time = ?, duration_minutes = ? WHERE id = ? AND admin_id = ?");
            $stmt_update->bind_param("isssiii", $activity_type_id, $title, $start_time_str, $end_time_str, $duration_minutes, $activity_id, $admin_id);
        } else {
            $stmt_update = $conn->prepare("UPDATE activities SET title = ?, start_time = ?, end_time = ?, duration_minutes = ? WHERE id = ? AND admin_id = ?");
            $stmt_update->bind_param("sssiii", $title, $start_time_str, $end_time_str, $duration_minutes, $activity_id, $admin_id);
        }

        if ($stmt_update->execute()) {
            if ($stmt_update->affected_rows > 0) {
                $response['success'] = true;
                $response['message'] = 'แก้ไขกิจกรรมเรียบร้อยแล้ว';
            } else {
                // ถ้าไม่มีการเปลี่ยนแปลงก็ถือว่าสำเร็จ (ข้อมูลเหมือนเดิม)
                $response['success'] = true;
                $response['message'] = 'บันทึกข้อมูลเรียบร้อยแล้ว';
            }
        } else {
            throw new Exception('ไม่สามารถแก้ไขข้อมูลได้: ' . $stmt_update->error);
        }
        $stmt_update->close();
    }
    // Action: ลบกิจกรรม
    elseif ($action == 'delete') {
        if (empty($_POST['activity_id'])) {
            throw new Exception('ไม่พบรหัสกิจกรรมที่ต้องการลบ');
        }
        $activity_id = (int) $_POST['activity_id'];
        $stmt = $conn->prepare("DELETE FROM activities WHERE id = ?");
        $stmt->bind_param("i", $activity_id);
        if ($stmt->execute()) {
            if ($stmt->affected_rows > 0) {
                $response['success'] = true;
                $response['message'] = 'ลบกิจกรรมเรียบร้อยแล้ว';
            } else {
                throw new Exception('ไม่พบกิจกรรมที่ต้องการลบ (อาจถูกลบไปแล้ว)');
            }
        } else {
            throw new Exception('ไม่สามารถลบข้อมูลได้: ' . $stmt->error);
        }
        $stmt->close();
    } else {
        throw new Exception("Unknown action: '$action'");
    }

} catch (Exception $e) {
    $response['success'] = false;
    $response['message'] = $e->getMessage();
}

$conn->close();
echo json_encode($response);
?>