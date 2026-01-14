<?php
require_once 'partials/header.php';
require_once '../db_connect.php';

// --- ดึงข้อมูลสำหรับ Dropdowns (ดึงเจ้าหน้าที่ทั้งหมด) ---
$admins = $conn->query("SELECT id, full_name FROM admins ORDER BY full_name ASC");

// --- รับค่าจากฟอร์มตัวกรอง ---
$start_date = isset($_GET['start_date']) && !empty($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-01');
$end_date = isset($_GET['end_date']) && !empty($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');
$admin_id_filter = isset($_GET['admin_id']) ? (int)$_GET['admin_id'] : 0; // 0 = ทั้งหมด

// --- เตรียมตัวแปร ---
$tasks = [];
$total_minutes = 0;

$start_datetime = $start_date . ' 00:00:00';
$end_datetime = $end_date . ' 23:59:59';
$params = [$start_datetime, $end_datetime];
$types = "ss";

// สร้างเงื่อนไข SQL ตามตัวกรอง
$admin_condition = "";
if ($admin_id_filter > 0) {
    $admin_condition = " AND r.admin_id = ?";
    $params[] = $admin_id_filter;
    $types .= "i";
}

// 1. ดึงข้อมูล "งานซ่อม" (requests)
$sql_requests = "
    SELECT 
        r.id, 'ซ่อม' as source, r.repair_date as task_start_date, r.repair_date as task_end_date,
        'งานซ่อม' as task_type, r.problem_description as task_title, 
        r.resolution_time_minutes as duration_minutes, a.full_name as owner_name
    FROM requests r
    LEFT JOIN admins a ON r.admin_id = a.id
    WHERE r.repair_date BETWEEN ? AND ? AND r.repair_date IS NOT NULL
";
if ($admin_id_filter > 0) {
    $sql_requests .= " AND r.admin_id = ?";
}

$stmt_req = $conn->prepare($sql_requests);
$stmt_req->bind_param($types, ...$params);
$stmt_req->execute();
$result_req = $stmt_req->get_result();
while ($row = $result_req->fetch_assoc()) {
    $tasks[] = $row;
    $total_minutes += (int)$row['duration_minutes'];
}
$stmt_req->close();

// 2. ดึงข้อมูล "กิจกรรมอื่นๆ" (activities)
$sql_activities = "
    SELECT 
        a.id, 'กิจกรรม' as source, a.start_time as task_start_date, a.end_time as task_end_date,
        t.type_name as task_type, a.title as task_title, 
        a.duration_minutes, a.activity_type_id, ad.full_name as owner_name
    FROM activities a
    JOIN activity_types t ON a.activity_type_id = t.id
    LEFT JOIN admins ad ON a.admin_id = ad.id
    WHERE a.start_time BETWEEN ? AND ? AND a.end_time IS NOT NULL
";
if ($admin_id_filter > 0) {
    $sql_activities .= " AND a.admin_id = ?";
}

$stmt_act = $conn->prepare($sql_activities);
$stmt_act->bind_param($types, ...$params);
$stmt_act->execute();
$result_act = $stmt_act->get_result();
while ($row = $result_act->fetch_assoc()) {
    $tasks[] = $row;
    if ($row['task_type'] !== 'ลา') {
        $total_minutes += (int)$row['duration_minutes'];
    }
}
$stmt_act->close();

// 3. เรียงลำดับข้อมูลทั้งหมดตามวันที่เริ่มต้น
if (!empty($tasks)) {
    usort($tasks, function($a, $b) {
        return strtotime($b['task_start_date']) - strtotime($a['task_start_date']);
    });
}

$hours = floor($total_minutes / 60);
$minutes = $total_minutes % 60;
?>

<!-- Form สำหรับกรองข้อมูล -->
<div class="card mb-4">
    <div class="card-header"><h5 class="card-title mb-0">รายงานกิจกรรมเจ้าหน้าที่</h5></div>
    <div class="card-body">
        <form id="reportForm" method="GET" action="activity_report.php" class="row g-3 align-items-end">
            <div class="col-md-3"><label for="start_date" class="form-label">ตั้งแต่วันที่</label><input type="date" class="form-control" name="start_date" value="<?php echo $start_date; ?>"></div>
            <div class="col-md-3"><label for="end_date" class="form-label">ถึงวันที่</label><input type="date" class="form-control" name="end_date" value="<?php echo $end_date; ?>"></div>
            <div class="col-md-3">
                <label for="admin_id" class="form-label">เจ้าหน้าที่</label>
                <!-- Dropdown เลือกได้ทุกคน (value="" คือทั้งหมด) -->
                <select class="form-select" name="admin_id">
                    <option value="0">-- ทั้งหมด --</option>
                    <?php while($row = $admins->fetch_assoc()) { echo "<option value='{$row['id']}' ".($admin_id_filter == $row['id'] ? 'selected' : '').">".htmlspecialchars($row['full_name'])."</option>"; } ?>
                </select>
            </div>
            <div class="col-md-3 text-end d-flex justify-content-end gap-2">
                <button type="submit" class="btn btn-primary">แสดงรายงาน</button>
                
                <!-- ปุ่ม Dropdown สำหรับเลือกดาวน์โหลด -->
                <div class="btn-group">
                    <button type="button" class="btn btn-success dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="bi bi-download"></i> ส่งออก
                    </button>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="#" id="downloadWordBtn"><i class="bi bi-file-earmark-word"></i> Word (สำหรับเซ็นชื่อ)</a></li>
                        <li><a class="dropdown-item" href="#" id="downloadExcelBtn"><i class="bi bi-file-earmark-excel"></i> Excel (ข้อมูลดิบ)</a></li>
                    </ul>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- ตารางแสดงผลรายงาน -->
<div class="card">
    <div class="card-header d-flex justify-content-between">
         <h5 class="card-title mb-0">สรุปผลงาน</h5>
         <h5 class="mb-0">รวมเวลาทำงาน (ไม่รวมวันลา): <span class="text-success"><?php echo $hours; ?> ชั่วโมง <?php echo $minutes; ?> นาที</span></h5>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table id="activityReportTable" class="table table-striped table-bordered" style="width:100%">
                <thead>
                    <tr>
                        <th>วันที่</th>
                        <th>ผู้ปฏิบัติงาน</th> <!-- เพิ่มคอลัมน์นี้เมื่อดูรวม -->
                        <th>ประเภท</th>
                        <th>รายละเอียดงาน</th>
                        <th class="text-center">เวลาที่ใช้ (นาที)</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($tasks as $task): ?>
                    <tr>
                        <td><?php echo date('d/m/Y H:i', strtotime($task['task_start_date'])); ?></td>
                        <td><?php echo htmlspecialchars($task['owner_name']); ?></td>
                        <td><?php echo htmlspecialchars($task['task_type']); ?></td>
                        <td><?php echo htmlspecialchars($task['task_title']); ?></td>
                        <td class="text-center">
                            <?php
                            if ($task['task_type'] == 'ลา') {
                                echo 'N/A';
                            } else {
                                echo $task['duration_minutes'] ?? 'N/A';
                            }
                            ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php
$conn->close();
require_once 'partials/footer.php';
?>

<script>
$(document).ready(function() {
    $('#activityReportTable').DataTable({
        language: { url: 'https://cdn.datatables.net/plug-ins/2.0.8/i18n/th.json' },
        order: [[0, 'desc']]
    });

    // ฟังก์ชันสำหรับดึงค่าจากฟอร์มและสร้าง URL
    function getExportUrl(filename) {
        var form = $('#reportForm');
        var startDate = form.find('input[name="start_date"]').val();
        var endDate = form.find('input[name="end_date"]').val();
        var adminId = form.find('select[name="admin_id"]').val();
        return `${filename}?start_date=${startDate}&end_date=${endDate}&reporter_id=${adminId}`; // ใช้ reporter_id ให้ตรงกับไฟล์ export
    }

    // ปุ่มดาวน์โหลด Word
    $('#downloadWordBtn').on('click', function(e){
        e.preventDefault();
        // เช็คว่าเลือกเจ้าหน้าที่หรือยัง (Word เหมาะสำหรับรายบุคคล)
        var adminId = $('#reportForm').find('select[name="admin_id"]').val();
        if(adminId == 0) {
            Swal.fire('แจ้งเตือน', 'กรุณาเลือกเจ้าหน้าที่รายบุคคลสำหรับรายงาน Word', 'warning');
            return;
        }
        window.location.href = getExportUrl('export_word_report.php');
    });

    // ปุ่มดาวน์โหลด Excel
    $('#downloadExcelBtn').on('click', function(e){
        e.preventDefault();
        window.location.href = getExportUrl('export_excel_report.php');
    });
});
</script>