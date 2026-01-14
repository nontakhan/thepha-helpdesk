<?php
require_once '../db_connect.php';

// --- รับค่าจากฟอร์มตัวกรอง ---
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-01');
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');
// รับค่า reporter_id (ซึ่งในบริบทนี้คือ admin_id ที่เราส่งมาจากหน้า report)
$admin_id = isset($_GET['reporter_id']) ? (int)$_GET['reporter_id'] : 0;

// --- ดึงข้อมูลชื่อเจ้าหน้าที่ (สำหรับแสดงหัวกระดาษ) ---
$header_admin_name = "ทั้งหมด";
if ($admin_id > 0) {
    $admin_stmt = $conn->prepare("SELECT full_name FROM admins WHERE id = ?");
    $admin_stmt->bind_param("i", $admin_id);
    $admin_stmt->execute();
    $admin_result = $admin_stmt->get_result();
    $admin_info = $admin_result->fetch_assoc();
    $header_admin_name = $admin_info['full_name'] ?? 'ไม่พบข้อมูล';
    $admin_stmt->close();
}

// --- ดึงข้อมูลกิจกรรมและงานซ่อม ---
$tasks = [];
$start_datetime = $start_date . ' 00:00:00';
$end_datetime = $end_date . ' 23:59:59';
$params = [$start_datetime, $end_datetime];
$types = "ss";

// สร้างเงื่อนไขเพิ่มเติมถ้าเลือกเจ้าหน้าที่
$admin_condition_req = "";
$admin_condition_act = "";
if ($admin_id > 0) {
    $admin_condition_req = " AND r.admin_id = ?";
    $admin_condition_act = " AND a.admin_id = ?";
    $params[] = $admin_id;
    $types .= "i";
}

// 1. งานซ่อม (จากตาราง requests)
// เพิ่มการ JOIN กับตาราง admins เพื่อเอาชื่อผู้ปฏิบัติงาน (owner_name)
$sql_requests = "
    SELECT 
        r.repair_date as task_date, 
        'งานซ่อม' as task_type, 
        r.problem_description as task_title, 
        r.resolution_time_minutes as duration_minutes,
        adm.full_name as owner_name 
    FROM requests r
    LEFT JOIN admins adm ON r.admin_id = adm.id
    WHERE r.repair_date BETWEEN ? AND ? AND r.repair_date IS NOT NULL
    $admin_condition_req
";

$stmt_req = $conn->prepare($sql_requests);
$stmt_req->bind_param($types, ...$params);
$stmt_req->execute();
$result_req = $stmt_req->get_result();
while ($row = $result_req->fetch_assoc()) {
    $tasks[] = $row;
}
$stmt_req->close();


// 2. กิจกรรมอื่นๆ (activities)
// เพิ่มการ JOIN กับตาราง admins เพื่อเอาชื่อผู้ปฏิบัติงาน (owner_name)
$sql_activities = "
    SELECT 
        a.start_time as task_date, 
        t.type_name as task_type, 
        a.title as task_title, 
        a.duration_minutes,
        adm.full_name as owner_name
    FROM activities a
    JOIN activity_types t ON a.activity_type_id = t.id
    LEFT JOIN admins adm ON a.admin_id = adm.id
    WHERE a.start_time BETWEEN ? AND ? AND a.end_time IS NOT NULL
    $admin_condition_act
";

$stmt_act = $conn->prepare($sql_activities);
$stmt_act->bind_param($types, ...$params);
$stmt_act->execute();
$result_act = $stmt_act->get_result();
while ($row = $result_act->fetch_assoc()) {
    // ไม่นับเวลาลา (แต่ยังแสดงใน Excel โดยมีขีด -)
    $tasks[] = $row;
}
$stmt_act->close();

// เรียงลำดับตามเวลา
usort($tasks, function($a, $b) {
    return strtotime($a['task_date']) - strtotime($b['task_date']);
});

// --- ตั้งค่า Header สำหรับดาวน์โหลด ---
$filename = "Activity_Report_" . date("Y-m-d") . ".xls";
header("Content-Type: application/vnd.ms-excel");
header("Content-Disposition: attachment; filename=\"$filename\"");
header("Pragma: no-cache");
header("Expires: 0");

?>
<!DOCTYPE html>
<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<style>
    body { font-family: 'Sarabun', sans-serif; }
    table { border-collapse: collapse; width: 100%; }
    th, td { border: 1px solid #000; padding: 5px; vertical-align: top; }
    th { background-color: #f2f2f2; text-align: center; }
</style>
</head>
<body>
    <h3 style="text-align: center;">รายงานกิจกรรมเจ้าหน้าที่</h3>
    <p>
        <b>เจ้าหน้าที่:</b> <?php echo htmlspecialchars($header_admin_name); ?><br>
        <b>วันที่:</b> <?php echo date('d/m/Y', strtotime($start_date)); ?> ถึง <?php echo date('d/m/Y', strtotime($end_date)); ?>
    </p>

    <table>
        <thead>
            <tr>
                <th>ลำดับ</th>
                <th>วันที่/เวลา</th>
                <th>ผู้ปฏิบัติงาน</th> <!-- เพิ่มคอลัมน์นี้ -->
                <th>ประเภทกิจกรรม</th>
                <th>รายละเอียด</th>
                <th>เวลาที่ใช้ (นาที)</th>
            </tr>
        </thead>
        <tbody>
            <?php 
            $i = 1;
            foreach ($tasks as $task): 
                $duration = ($task['task_type'] == 'ลา' || ($task['task_type'] == 'งานซ่อม' && !isset($task['duration_minutes']))) ? '-' : $task['duration_minutes'];
            ?>
            <tr>
                <td style="text-align: center;"><?php echo $i++; ?></td>
                <td><?php echo date('d/m/Y H:i', strtotime($task['task_date'])); ?></td>
                <td><?php echo htmlspecialchars($task['owner_name'] ?? '-'); ?></td> <!-- แสดงชื่อเจ้าหน้าที่ -->
                <td><?php echo htmlspecialchars($task['task_type']); ?></td>
                <td><?php echo htmlspecialchars($task['task_title']); ?></td>
                <td style="text-align: center;"><?php echo $duration; ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</body>
</html>