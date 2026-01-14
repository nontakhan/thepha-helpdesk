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

    $reporter_id = (int)$_GET['reporter_id'];

    // ===== ส่วนที่แก้ไข: เพิ่มการ SELECT ข้อมูลการซ่อม =====
    $sql = "
        SELECT 
            r.id, 
            r.request_date, 
            r.problem_description,
            r.repair_date,
            s.status_name as final_status_name,
            r.satisfaction_rating,
            a.full_name as admin_name,
            r.cause,
            r.solution
        FROM requests r
        LEFT JOIN statuses s ON r.final_status_id = s.id
        LEFT JOIN admins a ON r.admin_id = a.id
        WHERE r.reporter_id = ? 
        AND r.final_status_id IS NOT NULL
        ORDER BY r.repair_date DESC
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
