<?php
require_once 'partials/header.php';
require_once '../db_connect.php';

// ... (ส่วนดึงข้อมูล Dropdown และ Filter เหมือนเดิม ไม่ต้องแก้) ...
$locations = $conn->query("SELECT id, location_name FROM locations ORDER BY location_name ASC");
$admins = $conn->query("SELECT id, full_name FROM admins ORDER BY full_name ASC");
$categories = $conn->query("SELECT id, category_name FROM categories ORDER BY category_name ASC");
$statuses = $conn->query("SELECT id, status_name FROM statuses ORDER BY status_name ASC");

$start_date = isset($_GET['start_date']) && !empty($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-01');
$end_date = isset($_GET['end_date']) && !empty($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');
$location_id_filter = isset($_GET['location_id']) ? (int)$_GET['location_id'] : 0;
$admin_id_filter = isset($_GET['admin_id']) ? (int)$_GET['admin_id'] : 0;
$category_id_filter = isset($_GET['category_id']) ? (int)$_GET['category_id'] : 0;
$status_id_filter = isset($_GET['status_id']) ? (int)$_GET['status_id'] : 0;

// --- เตรียม Query ---
$sql_base = "
    SELECT 
        r.id, r.request_date, r.problem_description, l.location_name, p.reporter_name,
        r.repair_date, r.resolution_time_minutes, a.full_name as admin_name, 
        c.category_name, c.is_sla, 
        r.cause, r.solution, s_final.status_name as final_status_name,
        l.department_type, r.satisfaction_rating, r.satisfaction_comment
    FROM requests r
    LEFT JOIN locations l ON r.location_id = l.id
    LEFT JOIN reporters p ON r.reporter_id = p.id
    LEFT JOIN admins a ON r.admin_id = a.id
    LEFT JOIN categories c ON r.category_id = c.id
    LEFT JOIN statuses s_final ON r.final_status_id = s_final.id
";
$where_clauses = [];
$params = [];
$param_types = '';

$where_clauses[] = "r.request_date BETWEEN ? AND ?";
$params[] = $start_date . ' 00:00:00';
$params[] = $end_date . ' 23:59:59';
$param_types .= 'ss';

if ($location_id_filter > 0) { $where_clauses[] = "r.location_id = ?"; $params[] = $location_id_filter; $param_types .= 'i'; }
if ($admin_id_filter > 0) { $where_clauses[] = "r.admin_id = ?"; $params[] = $admin_id_filter; $param_types .= 'i'; }
if ($category_id_filter > 0) { $where_clauses[] = "r.category_id = ?"; $params[] = $category_id_filter; $param_types .= 'i'; }
if ($status_id_filter > 0) { $where_clauses[] = "r.final_status_id = ?"; $params[] = $status_id_filter; $param_types .= 'i'; }

$sql_final = $sql_base . " WHERE " . implode(" AND ", $where_clauses) . " ORDER BY r.request_date DESC";
$stmt = $conn->prepare($sql_final);
$stmt->bind_param($param_types, ...$params);
$stmt->execute();
$result = $stmt->get_result();
?>

<!-- ... (ส่วน Form กรองข้อมูล เหมือนเดิม) ... -->
<div class="card mb-4">
    <div class="card-header"><h5 class="card-title mb-0">กรองข้อมูลรายงาน</h5></div>
    <div class="card-body">
        <form method="GET" action="report.php" class="row g-3">
             <!-- ... Inputs เหมือนเดิม ... -->
             <div class="col-md-4 col-lg-2"><label for="start_date" class="form-label">ตั้งแต่วันที่</label><input type="date" class="form-control" name="start_date" value="<?php echo $start_date; ?>"></div>
            <div class="col-md-4 col-lg-2"><label for="end_date" class="form-label">ถึงวันที่</label><input type="date" class="form-control" name="end_date" value="<?php echo $end_date; ?>"></div>
            <div class="col-md-4 col-lg-2"><label for="location_id" class="form-label">สถานที่</label><select class="form-select" name="location_id"><option value="">ทั้งหมด</option><?php while($row = $locations->fetch_assoc()) { echo "<option value='{$row['id']}' ".($location_id_filter == $row['id'] ? 'selected' : '').">".htmlspecialchars($row['location_name'])."</option>"; } mysqli_data_seek($locations, 0); ?></select></div>
            <div class="col-md-4 col-lg-2"><label for="category_id" class="form-label">ประเภท</label><select class="form-select" name="category_id"><option value="">ทั้งหมด</option><?php while($row = $categories->fetch_assoc()) { echo "<option value='{$row['id']}' ".($category_id_filter == $row['id'] ? 'selected' : '').">".htmlspecialchars($row['category_name'])."</option>"; } mysqli_data_seek($categories, 0); ?></select></div>
            <div class="col-md-4 col-lg-2"><label for="admin_id" class="form-label">ผู้ดำเนินการ</label><select class="form-select" name="admin_id"><option value="">ทั้งหมด</option><?php while($row = $admins->fetch_assoc()) { echo "<option value='{$row['id']}' ".($admin_id_filter == $row['id'] ? 'selected' : '').">".htmlspecialchars($row['full_name'])."</option>"; } mysqli_data_seek($admins, 0); ?></select></div>
            <div class="col-md-4 col-lg-2"><label for="status_id" class="form-label">สถานะ</label><select class="form-select" name="status_id"><option value="">ทั้งหมด</option><?php while($row = $statuses->fetch_assoc()) { echo "<option value='{$row['id']}' ".($status_id_filter == $row['id'] ? 'selected' : '').">".htmlspecialchars($row['status_name'])."</option>"; } mysqli_data_seek($statuses, 0); ?></select></div>
            <div class="col-12"><hr><div class="d-flex justify-content-end gap-2"><button type="submit" class="btn btn-primary">แสดงรายงาน</button><a href="report.php" class="btn btn-outline-secondary">ล้างตัวกรอง</a></div></div>
        </form>
    </div>
</div>


<!-- ตารางแสดงผลรายงาน -->
<div class="card">
    <div class="card-header"><h5 class="card-title mb-0">รายงานการแจ้งซ่อม วันที่ <?php echo date('d/m/Y', strtotime($start_date)); ?> ถึง <?php echo date('d/m/Y', strtotime($end_date)); ?></h5></div>
    <div class="card-body">
        <div class="table-responsive">
            <table id="reportTable" class="table table-striped table-bordered" style="width:100%">
                <thead>
                    <tr>
                        <th>ลำดับ</th>
                        <th>วันที่แจ้ง</th>
                        <th>ปัญหา</th>
                        <th>สถานที่</th>
                        <th>ผู้แจ้ง</th>
                        <th>ผู้ดำเนินการ</th>
                        <th>ประเภท</th>
                        <th>สาเหตุ</th>
                        <th>วิธีแก้ไข</th>
                        <th>เวลาที่ใช้ (นาที)</th>
                        <th>เป้าหมาย SLA</th>
                        <th>สถานะ</th>
                        <th>ผล SLA</th>
                        <th>คะแนน</th>
                        <th>ความคิดเห็น</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $i = 1; 
                    while ($row = $result->fetch_assoc()): 
                        // เตรียมข้อมูลสำหรับ Export (Text Only)
                        $sla_text = '-';
                        if (isset($row['resolution_time_minutes'])) {
                            if (isset($row['is_sla']) && $row['is_sla'] == 0) {
                                $sla_text = '-';
                            } else {
                                $time_used = $row['resolution_time_minutes'];
                                $sla_time = ($row['department_type'] == 'หน่วยงานให้บริการ') ? 15 : 30;
                                $sla_text = ($time_used <= $sla_time) ? 'ผ่าน' : 'ไม่ผ่าน';
                            }
                        }
                        $rating_text = isset($row['satisfaction_rating']) ? $row['satisfaction_rating'] : 'N/A';
                    ?>
                    <tr>
                        <td><?php echo $i; ?></td>
                        <td><?php echo date('d/m/Y H:i', strtotime($row['request_date'])); ?></td>
                        <td><?php echo htmlspecialchars($row['problem_description']); ?></td>
                        <td><?php echo htmlspecialchars($row['location_name']); ?></td>
                        <td><?php echo htmlspecialchars($row['reporter_name']); ?></td>
                        <td><?php echo htmlspecialchars($row['admin_name'] ?? 'N/A'); ?></td>
                        <td><?php echo htmlspecialchars($row['category_name'] ?? 'N/A'); ?></td>
                        <td><?php echo htmlspecialchars($row['cause'] ?? 'N/A'); ?></td>
                        <td><?php echo htmlspecialchars($row['solution'] ?? 'N/A'); ?></td>
                        <td class="text-center"><?php echo $row['resolution_time_minutes'] ?? 'N/A'; ?></td>
                        <td class="text-center">
                            <?php 
                            if (isset($row['is_sla']) && $row['is_sla'] == 0) {
                                echo '-';
                            } else {
                                echo ($row['department_type'] == 'หน่วยงานให้บริการ') ? '15' : '30';
                            }
                            ?>
                        </td>
                        <td><?php echo htmlspecialchars($row['final_status_name'] ?? 'N/A'); ?></td>
                        
                        <!-- คอลัมน์ผล SLA: แสดง Icon ในเว็บ แต่ซ่อน Text ไว้สำหรับ Export -->
                        <td class="text-center" data-order="<?php echo $sla_text; ?>" data-search="<?php echo $sla_text; ?>">
                            <?php
                            if ($sla_text == 'ผ่าน') {
                                echo '<span class="badge bg-success"><i class="bi bi-check-circle-fill"></i> ผ่าน</span>';
                            } elseif ($sla_text == 'ไม่ผ่าน') {
                                echo '<span class="badge bg-danger"><i class="bi bi-x-circle-fill"></i> ไม่ผ่าน</span>';
                            } else {
                                echo '<span class="badge bg-secondary">-</span>';
                            }
                            ?>
                            <!-- ข้อมูลสำหรับ Export (ซ่อนไว้) -->
                            <span style="display:none;"><?php echo $sla_text; ?></span>
                        </td>
                        
                        <!-- คอลัมน์คะแนน: แสดงดาวในเว็บ แต่ซ่อนตัวเลขไว้สำหรับ Export -->
                        <td class="text-center" data-order="<?php echo $rating_text; ?>">
                            <?php 
                            if(isset($row['satisfaction_rating'])) {
                                for($s = 1; $s <= 5; $s++) {
                                    echo '<i class="bi bi-star-fill '.($s <= $row['satisfaction_rating'] ? 'text-warning' : 'text-secondary').'"></i>';
                                }
                            } else {
                                echo 'N/A';
                            }
                            ?>
                            <!-- ข้อมูลสำหรับ Export (ซ่อนไว้) -->
                            <span style="display:none;"><?php echo $rating_text; ?></span>
                        </td>
                        
                        <td><?php echo htmlspecialchars($row['satisfaction_comment'] ?? 'N/A'); ?></td>
                    </tr>
                    <?php 
                    $i++; 
                    endwhile; 
                    ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php
$stmt->close();
$conn->close();
require_once 'partials/footer.php';
?>

<script>
$(document).ready(function() {
    var reportTitle = 'รายงานการแจ้งซ่อม วันที่ <?php echo date('d/m/Y', strtotime($start_date)); ?> ถึง <?php echo date('d/m/Y', strtotime($end_date)); ?>';
    
    $('#reportTable').DataTable({
        dom: 'lBfrtip',
        buttons: [
            { 
                extend: 'copy', 
                title: reportTitle, 
                className: 'btn btn-secondary btn-sm me-1',
                exportOptions: {
                    // ใช้ฟังก์ชัน format เพื่อดึงข้อมูล text ที่ซ่อนอยู่ออกมา
                    format: {
                        body: function (data, row, column, node) {
                            // ถ้าเป็นคอลัมน์ SLA หรือ คะแนน (เช็คจาก index หรือ data attribute ก็ได้)
                            // ในที่นี้เราใช้วิธีง่ายๆ คือถ้ามี span ที่ซ่อนอยู่ ให้เอา text ในนั้นมา
                            var hiddenSpan = $(node).find('span[style*="display:none"]');
                            if (hiddenSpan.length > 0) {
                                return hiddenSpan.text();
                            }
                            // ลบ tag html อื่นๆ ออก (เช่น badge)
                            return data.replace(/<.*?>/ig, "");
                        }
                    }
                }
            },
            { 
                extend: 'csv', 
                title: reportTitle, 
                className: 'btn btn-secondary btn-sm me-1', 
                charset: 'utf-8', 
                bom: true,
                exportOptions: {
                    format: {
                        body: function (data, row, column, node) {
                            var hiddenSpan = $(node).find('span[style*="display:none"]');
                            if (hiddenSpan.length > 0) { return hiddenSpan.text(); }
                            return data.replace(/<.*?>/ig, "");
                        }
                    }
                }
            },
            { 
                extend: 'excel', 
                title: reportTitle, 
                className: 'btn btn-success btn-sm me-1',
                exportOptions: {
                    format: {
                        body: function (data, row, column, node) {
                            var hiddenSpan = $(node).find('span[style*="display:none"]');
                            if (hiddenSpan.length > 0) { return hiddenSpan.text(); }
                            return data.replace(/<.*?>/ig, "");
                        }
                    }
                }
            },
            { 
                extend: 'print', 
                title: reportTitle, 
                className: 'btn btn-info btn-sm' 
            }
        ],
        responsive: true,
        language: { url: 'https://cdn.datatables.net/plug-ins/2.0.8/i18n/th.json' },
        order: [[1, 'desc']]
    });
});
</script>