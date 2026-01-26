<?php
session_start();
header('Content-Type: application/json');
require_once '../db_connect.php';

$events = [];

// ถ้าไม่ได้ระบุ admin_id ให้ใช้ id ของคนที่ login อยู่
$admin_id = isset($_GET['admin_id']) && !empty($_GET['admin_id'])
    ? (int) $_GET['admin_id']
    : (isset($_SESSION['admin_id']) ? (int) $_SESSION['admin_id'] : 0);

if ($admin_id > 0) {
    try {
        // 1. ดึงข้อมูล "งานซ่อม" (requests) พร้อมรายละเอียดเพิ่มเติม
        $sql_requests = "
            SELECT 
                r.id, r.problem_description, r.solution, r.cause,
                r.request_date, r.repair_date,
                r.resolution_time_minutes,
                l.location_name, l.department_type,
                p.reporter_name,
                c.category_name,
                adm.full_name as admin_name
            FROM requests r
            LEFT JOIN locations l ON r.location_id = l.id
            LEFT JOIN reporters p ON r.reporter_id = p.id
            LEFT JOIN categories c ON r.category_id = c.id
            LEFT JOIN admins adm ON r.admin_id = adm.id
            WHERE r.admin_id = ? AND r.repair_date IS NOT NULL
        ";
        $stmt_req = $conn->prepare($sql_requests);
        $stmt_req->bind_param("i", $admin_id);
        $stmt_req->execute();
        $result_req = $stmt_req->get_result();
        while ($row = $result_req->fetch_assoc()) {
            // คำนวณ SLA
            $sla_status = '-';
            $sla_class = 'secondary';
            if ($row['resolution_time_minutes']) {
                $sla_time = ($row['department_type'] == 'หน่วยงานให้บริการ') ? 15 : 30;
                if ($row['resolution_time_minutes'] <= $sla_time) {
                    $sla_status = 'ผ่าน';
                    $sla_class = 'success';
                } else {
                    $sla_status = 'ไม่ผ่าน';
                    $sla_class = 'danger';
                }
            }

            $events[] = [
                'id' => 'req_' . $row['id'],
                'title' => '[ซ่อม] ' . $row['problem_description'],
                'start' => $row['request_date'],
                'end' => $row['repair_date'],
                'color' => '#ffc107',
                'textColor' => '#000',
                'extendedProps' => [
                    'type' => 'งานซ่อม',
                    'problem' => $row['problem_description'],
                    'solution' => $row['solution'],
                    'cause' => $row['cause'],
                    'location' => $row['location_name'],
                    'reporter' => $row['reporter_name'],
                    'admin_name' => $row['admin_name'],
                    'category' => $row['category_name'],
                    'request_date' => $row['request_date'],
                    'repair_date' => $row['repair_date'],
                    'resolution_time' => $row['resolution_time_minutes'],
                    'sla_status' => $sla_status,
                    'sla_class' => $sla_class,
                    'request_id' => $row['id']
                ]
            ];
        }
        $stmt_req->close();

        // 2. ดึงข้อมูล "กิจกรรมอื่นๆ" (activities)
        $sql_activities = "
            SELECT 
                a.id, a.title, a.start_time, a.end_time, a.duration_minutes,
                a.activity_type_id, t.type_name, t.color 
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
                'id' => 'act_' . $row['id'],
                'title' => $row['title'],
                'start' => $row['start_time'],
                'end' => $row['end_time'],
                'color' => $row['color'],
                'textColor' => '#fff',
                'extendedProps' => [
                    'type' => $row['type_name'],
                    'type_id' => $row['activity_type_id'],
                    'duration' => $row['duration_minutes']
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