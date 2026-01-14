<?php
session_start();
header('Content-Type: application/json');
require_once '../db_connect.php';

$events = [];

// ถ้าไม่ได้ระบุ admin_id ให้ใช้ id ของคนที่ login อยู่
$admin_id = isset($_GET['admin_id']) && !empty($_GET['admin_id']) 
            ? (int)$_GET['admin_id'] 
            : (isset($_SESSION['admin_id']) ? (int)$_SESSION['admin_id'] : 0);

if ($admin_id > 0) {
    try {
        // 1. ดึงข้อมูล "งานซ่อม" (requests)
       $sql_requests = "
            SELECT 
                r.id, r.problem_description, 
                r.request_date, -- ดึงเวลาที่แจ้ง
                r.repair_date,  -- ดึงเวลาที่ซ่อมเสร็จ
                l.location_name, p.reporter_name
            FROM requests r
            LEFT JOIN locations l ON r.location_id = l.id
            LEFT JOIN reporters p ON r.reporter_id = p.id
            WHERE r.admin_id = ? AND r.repair_date IS NOT NULL
        ";
        $stmt_req = $conn->prepare($sql_requests);
        $stmt_req->bind_param("i", $admin_id);
        $stmt_req->execute();
        $result_req = $stmt_req->get_result();
        while ($row = $result_req->fetch_assoc()) {
            $events[] = [
                'id'        => 'req_' . $row['id'],
                'title'     => '[ซ่อม] ' . $row['problem_description'],
                'start'     => $row['request_date'], // เวลาเริ่มต้นคือเวลาที่แจ้ง
                'end'       => $row['repair_date'],  // เวลาสิ้นสุดคือเวลาที่ซ่อมเสร็จ
                'color'     => '#ffc107', 
                'textColor' => '#000',
                'extendedProps' => [
                    'type'      => 'งานซ่อม',
                    'problem'   => $row['problem_description'],
                    'location'  => $row['location_name'],
                    'reporter'  => $row['reporter_name']
                ]
            ];
        }
        $stmt_req->close();

        // 2. ดึงข้อมูล "กิจกรรมอื่นๆ" (activities)
        $sql_activities = "
            SELECT 
                a.id, a.title, a.start_time, a.end_time, a.duration_minutes,
                t.type_name, t.color 
            FROM activities a
            JOIN activity_types t ON a.activity_type_id = t.id
            WHERE a.admin_id = ?
        ";
        $stmt_act = $conn->prepare($sql_activities);
        $stmt_act->bind_param("i", $admin_id);
        $stmt_act->execute();
        $result_act = $stmt_act->get_result();
        while ($row = $result_act->fetch_assoc()) {
            $events[] = [
                'id'        => 'act_' . $row['id'],
                'title'     => $row['title'],
                'start'     => $row['start_time'],
                'end'       => $row['end_time'],
                'color'     => $row['color'],
                'textColor' => '#fff',
                'extendedProps' => [
                    'type'      => $row['type_name'],
                    'duration'  => $row['duration_minutes']
                ]
            ];
        }
        $stmt_act->close();

    } catch (Exception $e) {
        // Handle error if needed
    }
}

$conn->close();
echo json_encode($events);
?>
