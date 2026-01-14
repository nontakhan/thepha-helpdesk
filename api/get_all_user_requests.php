<?php
header('Content-Type: application/json');
require_once '../db_connect.php';

$response = [
    'success' => false,
    'requests' => [],
    'message' => 'เกิดข้อผิดพลาดที่ไม่รู้จัก'
];

try {
    if (!isset($_GET['reporter_id']) || empty($_GET['reporter_id'])) {
        throw new Exception('ไม่พบรหัสผู้แจ้ง (Missing reporter_id)');
    }

    $reporter_id = (int) $_GET['reporter_id'];

    // ดึงรายการแจ้งซ่อมทั้งหมด (ทุกสถานะ)
    $sql = "
        SELECT 
            r.id, 
            r.request_date, 
            r.problem_description,
            r.created_at,
            r.repair_date,
            r.satisfaction_rating,
            cs.status_name as current_status_name,
            cs.id as current_status_id,
            fs.status_name as final_status_name,
            fs.id as final_status_id,
            a.full_name as admin_name,
            l.location_name
        FROM requests r
        LEFT JOIN statuses cs ON r.current_status_id = cs.id
        LEFT JOIN statuses fs ON r.final_status_id = fs.id
        LEFT JOIN admins a ON r.admin_id = a.id
        LEFT JOIN locations l ON r.location_id = l.id
        WHERE r.reporter_id = ? 
        ORDER BY r.request_date DESC
    ";

    $stmt = $conn->prepare($sql);
    if ($stmt === false) {
        throw new Exception("SQL Syntax Error: " . $conn->error);
    }

    $stmt->bind_param("i", $reporter_id);

    if (!$stmt->execute()) {
        throw new Exception("SQL Execution Error: " . $stmt->error);
    }

    $result = $stmt->get_result();
    $requests_data = [];
    while ($row = $result->fetch_assoc()) {
        $requests_data[] = $row;
    }

    $response['success'] = true;
    $response['requests'] = $requests_data;
    $response['message'] = 'ดึงข้อมูลสำเร็จ';

    $stmt->close();

} catch (Exception $e) {
    $response['message'] = "API Error: " . $e->getMessage();
}

$conn->close();
echo json_encode($response);
?>