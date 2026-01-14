<?php
ob_start(); // Start output buffering
require_once '../db_connect.php';

// --- รับค่าจากฟอร์มตัวกรอง ---
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-01');
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');
$admin_id = isset($_GET['admin_id']) ? (int) $_GET['admin_id'] : 0;

if ($admin_id === 0) {
    die("กรุณาเลือกเจ้าหน้าที่ก่อนทำการ Export");
}

// --- ดึงข้อมูลเจ้าหน้าที่ ---
$admin_stmt = $conn->prepare("SELECT full_name FROM admins WHERE id = ?");
$admin_stmt->bind_param("i", $admin_id);
$admin_stmt->execute();
$admin_result = $admin_stmt->get_result();
$admin_info = $admin_result->fetch_assoc();
$admin_name = $admin_info['full_name'] ?? 'ไม่พบข้อมูล';
$admin_stmt->close();

// --- ดึงข้อมูลกิจกรรมและงานซ่อม ---
$tasks = [];
$start_datetime = $start_date . ' 00:00:00';
$end_datetime = $end_date . ' 23:59:59';

// 1. ดึงงานซ่อม
$sql_requests = "SELECT repair_date as start_time, repair_date as end_time, problem_description as title FROM requests WHERE admin_id = ? AND repair_date BETWEEN ? AND ?";
$stmt_req = $conn->prepare($sql_requests);
$stmt_req->bind_param("iss", $admin_id, $start_datetime, $end_datetime);
$stmt_req->execute();
$result_req = $stmt_req->get_result();
while ($row = $result_req->fetch_assoc()) {
    $row['title'] = '[ซ่อม] ' . $row['title'];
    $tasks[] = $row;
}
$stmt_req->close();

// 2. ดึงกิจกรรมอื่นๆ
$sql_activities = "SELECT start_time, end_time, title FROM activities WHERE admin_id = ? AND start_time BETWEEN ? AND ?";
$stmt_act = $conn->prepare($sql_activities);
$stmt_act->bind_param("iss", $admin_id, $start_datetime, $end_datetime);
$stmt_act->execute();
$result_act = $stmt_act->get_result();
while ($row = $result_act->fetch_assoc()) {
    $tasks[] = $row;
}
$conn->close();

// --- จัดกลุ่มข้อมูลตามวัน ---
$grouped_tasks = [];
foreach ($tasks as $task) {
    $date = date('Y-m-d', strtotime($task['start_time']));
    if (!isset($grouped_tasks[$date])) {
        $grouped_tasks[$date] = [];
    }
    $grouped_tasks[$date][] = $task;
}
ksort($grouped_tasks); // เรียงวันที่จากน้อยไปมาก

// ===== ส่วนที่แก้ไข: เรียงลำดับงานภายในแต่ละวันตามเวลา =====
foreach ($grouped_tasks as $date => &$day_tasks) {
    usort($day_tasks, function ($a, $b) {
        return strtotime($a['start_time']) - strtotime($b['start_time']);
    });
}
unset($day_tasks); // ตัด Reference เพื่อความปลอดภัย
// =======================================================

// --- สร้างเนื้อหา HTML สำหรับไฟล์ Word ---
$report_month_th = [
    'January' => 'มกราคม',
    'February' => 'กุมภาพันธ์',
    'March' => 'มีนาคม',
    'April' => 'เมษายน',
    'May' => 'พฤษภาคม',
    'June' => 'มิถุนายน',
    'July' => 'กรกฎาคม',
    'August' => 'สิงหาคม',
    'September' => 'กันยายน',
    'October' => 'ตุลาคม',
    'November' => 'พฤศจิกายน',
    'December' => 'ธันวาคม'
];
$report_month_en = date('F', strtotime($start_date));
$report_month = $report_month_th[$report_month_en];
$report_year = date('Y', strtotime($start_date)) + 543;
$filename = "Report-" . $admin_id . "-" . date("Y-m-d") . ".doc";

// --- Clear output buffer and set headers for download ---
ob_end_clean();
header("Content-Type: application/vnd.ms-word; charset=UTF-8");
header("Content-Disposition: attachment; filename=\"$filename\"");
header("Cache-Control: no-cache, must-revalidate");
header("Expires: 0");

?>
<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <title>รายงานการปฏิบัติงาน</title>
    <style>
        @page {
            size: A4;
            margin: 2.5cm 2cm 2.5cm 3cm;
            /* บน ขวา ล่าง ซ้าย */
        }

        body {
            font-family: 'TH SarabunPSK', 'Angsana New', sans-serif;
            font-size: 16pt;
        }

        .report-table {
            border-collapse: collapse;
            width: 100%;
        }

        .report-table th,
        .report-table td {
            border: 1px solid black;
            padding: 8px;
            vertical-align: top;
        }

        .header-text {
            text-align: center;
        }

        .info-text {
            text-align: left;
            padding-left: 50px;
            line-height: 1.5;
        }

        .signature-col {
            width: 25%;
        }

        .date-col {
            width: 15%;
            text-align: center;
        }

        .time-col {
            width: 20%;
            text-align: center;
        }

        .task-col {
            width: 40%;
        }
    </style>
</head>

<body>
    <div class="header-text">
        <h3>บัญชีการปฏิบัติงานประจำของเจ้าหน้าที่จ้างเหมา</h3>
    </div>
    <div class="info-text">
        <p>
            <b>ชื่อ - สกุล:</b>&nbsp;&nbsp;&nbsp;<?php echo htmlspecialchars($admin_name); ?><br>
            <b>ตำแหน่ง:</b>&nbsp;&nbsp;&nbsp;&nbsp;นักวิชาการคอมพิวเตอร์<br>
            <b>ประจำเดือน:</b>&nbsp;<?php echo $report_month; ?>&nbsp;&nbsp;&nbsp;<b>พ.ศ.</b>&nbsp;<?php echo $report_year; ?>
        </p>
    </div>

    <table class="report-table">
        <thead>
            <tr>
                <th class="date-col">ว/ด/ป</th>
                <th class="time-col">ระหว่างเวลา</th>
                <th class="task-col">งานในหน้าที่</th>
                <th class="signature-col">ผู้ควบคุมการปฏิบัติงาน</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($grouped_tasks as $date => $tasks_on_day): ?>
                <?php $first_task = true; ?>
                <?php foreach ($tasks_on_day as $task): ?>
                    <tr>
                        <?php if ($first_task): ?>
                            <td class="date-col" rowspan="<?php echo count($tasks_on_day); ?>">
                                <?php echo date('d/m/Y', strtotime($date)); ?>
                            </td>
                        <?php endif; ?>

                        <td class="time-col">
                            <?php echo date('H:i', strtotime($task['start_time'])); ?> -
                            <?php echo date('H:i', strtotime($task['end_time'])); ?>
                        </td>

                        <td class="task-col">
                            <?php echo htmlspecialchars($task['title']); ?>
                        </td>

                        <?php if ($first_task): ?>
                            <td class="signature-col" rowspan="<?php echo count($tasks_on_day); ?>"></td>
                            <?php $first_task = false; ?>
                        <?php endif; ?>
                    </tr>
                <?php endforeach; ?>
            <?php endforeach; ?>
            <?php if (empty($grouped_tasks)): ?>
                <tr>
                    <td colspan="4" style="text-align: center;">ไม่พบข้อมูลกิจกรรมในช่วงวันที่ที่เลือก</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</body>

</html>