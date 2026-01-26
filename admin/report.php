<?php
require_once 'partials/header.php';
require_once '../db_connect.php';

// ดึงข้อมูลสำหรับ Dropdown
$locations = $conn->query("SELECT id, location_name FROM locations ORDER BY location_name ASC");
$admins = $conn->query("SELECT id, full_name FROM admins ORDER BY full_name ASC");
$categories = $conn->query("SELECT id, category_name FROM categories ORDER BY category_name ASC");
$statuses = $conn->query("SELECT id, status_name FROM statuses ORDER BY status_name ASC");

// Filter parameters
$start_date = isset($_GET['start_date']) && !empty($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-01');
$end_date = isset($_GET['end_date']) && !empty($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');
$location_id_filter = isset($_GET['location_id']) ? (int) $_GET['location_id'] : 0;
$admin_id_filter = isset($_GET['admin_id']) ? (int) $_GET['admin_id'] : 0;
$category_id_filter = isset($_GET['category_id']) ? (int) $_GET['category_id'] : 0;
$status_id_filter = isset($_GET['status_id']) ? (int) $_GET['status_id'] : 0;
$sla_filter = isset($_GET['sla_filter']) ? $_GET['sla_filter'] : '';

// --- เตรียม Query ---
$sql_base = "
    SELECT 
        r.id, r.request_date, r.problem_description, l.location_name, p.reporter_name,
        r.repair_date, r.resolution_time_minutes, a.full_name as admin_name, 
        c.category_name, c.is_sla, 
        r.cause, r.solution, s_final.status_name as final_status_name,
        l.department_type, r.satisfaction_rating, r.satisfaction_comment,
        s_current.status_name as current_status_name
    FROM requests r
    LEFT JOIN locations l ON r.location_id = l.id
    LEFT JOIN reporters p ON r.reporter_id = p.id
    LEFT JOIN admins a ON r.admin_id = a.id
    LEFT JOIN categories c ON r.category_id = c.id
    LEFT JOIN statuses s_final ON r.final_status_id = s_final.id
    LEFT JOIN statuses s_current ON r.current_status_id = s_current.id
";
$where_clauses = [];
$params = [];
$param_types = '';

$where_clauses[] = "r.request_date BETWEEN ? AND ?";
$params[] = $start_date . ' 00:00:00';
$params[] = $end_date . ' 23:59:59';
$param_types .= 'ss';

if ($location_id_filter > 0) {
    $where_clauses[] = "r.location_id = ?";
    $params[] = $location_id_filter;
    $param_types .= 'i';
}
if ($admin_id_filter > 0) {
    $where_clauses[] = "r.admin_id = ?";
    $params[] = $admin_id_filter;
    $param_types .= 'i';
}
if ($category_id_filter > 0) {
    $where_clauses[] = "r.category_id = ?";
    $params[] = $category_id_filter;
    $param_types .= 'i';
}
if ($status_id_filter > 0) {
    $where_clauses[] = "(r.current_status_id = ? OR r.final_status_id = ?)";
    $params[] = $status_id_filter;
    $params[] = $status_id_filter;
    $param_types .= 'ii';
}

$sql_final = $sql_base . " WHERE " . implode(" AND ", $where_clauses) . " ORDER BY r.request_date DESC";
$stmt = $conn->prepare($sql_final);
$stmt->bind_param($param_types, ...$params);
$stmt->execute();
$result = $stmt->get_result();

// คำนวณสถิติสำหรับ Summary Cards
$all_requests = [];
$total_requests = 0;
$sla_pass = 0;
$sla_fail = 0;
$total_rating = 0;
$rating_count = 0;
$total_resolution_time = 0;
$resolution_count = 0;
$category_stats = [];
$location_stats = [];

while ($row = $result->fetch_assoc()) {
    // ตรวจสอบว่ารายการนี้มี SLA หรือไม่ (is_sla != 0 หมายถึงมี SLA)
    $has_sla = (!isset($row['is_sla']) || $row['is_sla'] != 0);
    $row['has_sla'] = $has_sla;

    $all_requests[] = $row;
    $total_requests++;

    // คำนวณ SLA
    if (isset($row['resolution_time_minutes']) && $has_sla) {
        $sla_time = ($row['department_type'] == 'หน่วยงานให้บริการ') ? 15 : 30;
        if ($row['resolution_time_minutes'] <= $sla_time) {
            $sla_pass++;
        } else {
            $sla_fail++;
        }
    }

    // คำนวณคะแนนเฉลี่ย
    if (isset($row['satisfaction_rating']) && $row['satisfaction_rating'] > 0) {
        $total_rating += $row['satisfaction_rating'];
        $rating_count++;
    }

    // คำนวณเวลาแก้ไขเฉลี่ย
    if (isset($row['resolution_time_minutes']) && $row['resolution_time_minutes'] > 0) {
        $total_resolution_time += $row['resolution_time_minutes'];
        $resolution_count++;
    }

    // สถิติตามประเภท
    $cat_name = $row['category_name'] ?? 'ไม่ระบุ';
    if (!isset($category_stats[$cat_name]))
        $category_stats[$cat_name] = 0;
    $category_stats[$cat_name]++;

    // สถิติตามสถานที่
    $loc_name = $row['location_name'] ?? 'ไม่ระบุ';
    if (!isset($location_stats[$loc_name]))
        $location_stats[$loc_name] = 0;
    $location_stats[$loc_name]++;
}

$avg_rating = $rating_count > 0 ? round($total_rating / $rating_count, 1) : 0;
$avg_resolution = $resolution_count > 0 ? round($total_resolution_time / $resolution_count, 0) : 0;
$sla_percentage = ($sla_pass + $sla_fail) > 0 ? round(($sla_pass / ($sla_pass + $sla_fail)) * 100, 1) : 0;

// จัดเรียงสถิติตามประเภท (มากไปน้อย)
arsort($category_stats);

// จัดเรียงสถิติตามสถานที่ (Top 5)
arsort($location_stats);
$top_locations = array_slice($location_stats, 0, 5, true);

// กรองข้อมูลตาม SLA filter (มี SLA / ไม่มี SLA)
if ($sla_filter !== '') {
    $all_requests = array_filter($all_requests, function ($r) use ($sla_filter) {
        if ($sla_filter === 'has_sla') {
            return isset($r['has_sla']) && $r['has_sla'] === true;
        } else if ($sla_filter === 'no_sla') {
            return isset($r['has_sla']) && $r['has_sla'] === false;
        }
        return true;
    });
    // Reindex array
    $all_requests = array_values($all_requests);
    // Recalculate total
    $total_requests = count($all_requests);
}

// นับจำนวน filter ที่ใช้งาน
$active_filters = 0;
if ($location_id_filter > 0)
    $active_filters++;
if ($admin_id_filter > 0)
    $active_filters++;
if ($category_id_filter > 0)
    $active_filters++;
if ($status_id_filter > 0)
    $active_filters++;
if ($sla_filter !== '')
    $active_filters++;
?>

<!-- Page Header -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="mb-1"><i class="bi bi-file-earmark-bar-graph text-primary"></i> รายงานการแจ้งซ่อม</h2>
        <p class="text-muted mb-0">วันที่ <?php echo date('d/m/Y', strtotime($start_date)); ?> ถึง
            <?php echo date('d/m/Y', strtotime($end_date)); ?>
        </p>
    </div>
</div>

<!-- Summary Cards -->
<div class="row g-3 mb-4">
    <div class="col-md-6 col-lg-3">
        <div class="card border-0 shadow-sm h-100"
            style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
            <div class="card-body text-white">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <h6 class="card-subtitle mb-2 opacity-75">รายการทั้งหมด</h6>
                        <h2 class="mb-0 fw-bold"><?php echo number_format($total_requests); ?></h2>
                        <small class="opacity-75">รายการ</small>
                    </div>
                    <div class="bg-white bg-opacity-25 rounded-circle p-3">
                        <i class="bi bi-clipboard-data fs-4"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-6 col-lg-3">
        <div class="card border-0 shadow-sm h-100"
            style="background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);">
            <div class="card-body text-white">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <h6 class="card-subtitle mb-2 opacity-75">SLA ผ่านเกณฑ์</h6>
                        <h2 class="mb-0 fw-bold"><?php echo $sla_percentage; ?>%</h2>
                        <small class="opacity-75"><?php echo $sla_pass; ?> จาก <?php echo ($sla_pass + $sla_fail); ?>
                            รายการ</small>
                    </div>
                    <div class="bg-white bg-opacity-25 rounded-circle p-3">
                        <i class="bi bi-speedometer2 fs-4"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-6 col-lg-3">
        <div class="card border-0 shadow-sm h-100"
            style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);">
            <div class="card-body text-white">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <h6 class="card-subtitle mb-2 opacity-75">คะแนนความพึงพอใจ</h6>
                        <h2 class="mb-0 fw-bold"><?php echo $avg_rating; ?> <small class="fs-6">/ 5</small></h2>
                        <small class="opacity-75">จาก <?php echo $rating_count; ?> การประเมิน</small>
                    </div>
                    <div class="bg-white bg-opacity-25 rounded-circle p-3">
                        <i class="bi bi-star-fill fs-4"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-6 col-lg-3">
        <div class="card border-0 shadow-sm h-100"
            style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);">
            <div class="card-body text-white">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <h6 class="card-subtitle mb-2 opacity-75">เวลาแก้ไขเฉลี่ย</h6>
                        <h2 class="mb-0 fw-bold"><?php echo $avg_resolution; ?> <small class="fs-6">นาที</small></h2>
                        <small class="opacity-75">จาก <?php echo $resolution_count; ?> รายการ</small>
                    </div>
                    <div class="bg-white bg-opacity-25 rounded-circle p-3">
                        <i class="bi bi-clock-history fs-4"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Charts Row -->
<div class="row g-3 mb-4">
    <div class="col-lg-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white border-0 pb-0">
                <h6 class="card-title mb-0"><i class="bi bi-pie-chart text-success me-2"></i>ผลการทำงาน (SLA)</h6>
            </div>
            <div class="card-body">
                <canvas id="slaChart" style="max-height: 220px;"></canvas>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white border-0 pb-0">
                <h6 class="card-title mb-0"><i class="bi bi-tags text-primary me-2"></i>งานตามประเภท</h6>
            </div>
            <div class="card-body">
                <canvas id="categoryChart" style="max-height: 220px;"></canvas>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white border-0 pb-0">
                <h6 class="card-title mb-0"><i class="bi bi-geo-alt text-danger me-2"></i>สถานที่ Top 5</h6>
            </div>
            <div class="card-body">
                <canvas id="locationChart" style="max-height: 220px;"></canvas>
            </div>
        </div>
    </div>
</div>

<!-- Filter Card -->
<div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-white d-flex justify-content-between align-items-center">
        <h6 class="mb-0">
            <i class="bi bi-funnel text-secondary me-2"></i>กรองข้อมูล
            <?php if ($active_filters > 0): ?>
                <span class="badge bg-primary ms-2"><?php echo $active_filters; ?> ตัวกรอง</span>
            <?php endif; ?>
        </h6>
        <button class="btn btn-sm btn-outline-secondary" type="button" data-bs-toggle="collapse"
            data-bs-target="#filterCollapse">
            <i class="bi bi-chevron-down"></i>
        </button>
    </div>
    <div class="collapse show" id="filterCollapse">
        <div class="card-body">
            <form method="GET" action="report.php" class="row g-3">
                <div class="col-md-6 col-lg-2">
                    <label class="form-label small text-muted"><i
                            class="bi bi-calendar-event me-1"></i>ตั้งแต่วันที่</label>
                    <input type="date" class="form-control" name="start_date" value="<?php echo $start_date; ?>">
                </div>
                <div class="col-md-6 col-lg-2">
                    <label class="form-label small text-muted"><i
                            class="bi bi-calendar-check me-1"></i>ถึงวันที่</label>
                    <input type="date" class="form-control" name="end_date" value="<?php echo $end_date; ?>">
                </div>
                <div class="col-md-6 col-lg-2">
                    <label class="form-label small text-muted"><i class="bi bi-geo-alt me-1"></i>สถานที่</label>
                    <select class="form-select" name="location_id">
                        <option value="">ทั้งหมด</option>
                        <?php while ($row = $locations->fetch_assoc()) {
                            echo "<option value='{$row['id']}' " . ($location_id_filter == $row['id'] ? 'selected' : '') . ">" . htmlspecialchars($row['location_name']) . "</option>";
                        }
                        mysqli_data_seek($locations, 0); ?>
                    </select>
                </div>
                <div class="col-md-6 col-lg-2">
                    <label class="form-label small text-muted"><i class="bi bi-tag me-1"></i>ประเภท</label>
                    <select class="form-select" name="category_id">
                        <option value="">ทั้งหมด</option>
                        <?php while ($row = $categories->fetch_assoc()) {
                            echo "<option value='{$row['id']}' " . ($category_id_filter == $row['id'] ? 'selected' : '') . ">" . htmlspecialchars($row['category_name']) . "</option>";
                        }
                        mysqli_data_seek($categories, 0); ?>
                    </select>
                </div>
                <div class="col-md-6 col-lg-2">
                    <label class="form-label small text-muted"><i class="bi bi-person me-1"></i>ผู้ดำเนินการ</label>
                    <select class="form-select" name="admin_id">
                        <option value="">ทั้งหมด</option>
                        <?php while ($row = $admins->fetch_assoc()) {
                            echo "<option value='{$row['id']}' " . ($admin_id_filter == $row['id'] ? 'selected' : '') . ">" . htmlspecialchars($row['full_name']) . "</option>";
                        }
                        mysqli_data_seek($admins, 0); ?>
                    </select>
                </div>
                <div class="col-md-6 col-lg-2">
                    <label class="form-label small text-muted"><i class="bi bi-flag me-1"></i>สถานะ</label>
                    <select class="form-select" name="status_id">
                        <option value="">ทั้งหมด</option>
                        <?php while ($row = $statuses->fetch_assoc()) {
                            echo "<option value='{$row['id']}' " . ($status_id_filter == $row['id'] ? 'selected' : '') . ">" . htmlspecialchars($row['status_name']) . "</option>";
                        }
                        mysqli_data_seek($statuses, 0); ?>
                    </select>
                </div>
                <div class="col-md-6 col-lg-2">
                    <label class="form-label small text-muted"><i class="bi bi-speedometer2 me-1"></i>การกำหนด
                        SLA</label>
                    <select class="form-select" name="sla_filter">
                        <option value="">ทั้งหมด</option>
                        <option value="has_sla" <?php echo ($sla_filter == 'has_sla') ? 'selected' : ''; ?>>มี SLA
                        </option>
                        <option value="no_sla" <?php echo ($sla_filter == 'no_sla') ? 'selected' : ''; ?>>ไม่มี SLA
                        </option>
                    </select>
                </div>
                <div class="col-12">
                    <hr class="my-2">
                    <div class="d-flex justify-content-end gap-2">
                        <a href="report.php" class="btn btn-outline-secondary"><i
                                class="bi bi-x-circle me-1"></i>ล้างตัวกรอง</a>
                        <button type="submit" class="btn btn-primary"><i
                                class="bi bi-search me-1"></i>แสดงรายงาน</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Data Table Card -->
<div class="card border-0 shadow-sm">
    <div class="card-header bg-white">
        <h6 class="mb-0"><i class="bi bi-table text-primary me-2"></i>รายละเอียดการแจ้งซ่อม</h6>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table id="reportTable" class="table table-hover align-middle" style="width:100%">
                <thead class="table-light">
                    <tr>
                        <th style="width: 50px;">ลำดับ</th>
                        <th>วันที่แจ้ง</th>
                        <th>ปัญหา</th>
                        <th>สถานที่</th>
                        <th>ประเภท</th>
                        <th>สถานะ</th>
                        <th class="text-center">SLA</th>
                        <th class="text-center">คะแนน</th>
                        <th class="text-center" style="width: 80px;">รายละเอียด</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $i = 1;
                    foreach ($all_requests as $row):
                        // เตรียมข้อมูล SLA
                        $sla_text = '-';
                        $sla_class = 'bg-secondary';
                        if (isset($row['resolution_time_minutes'])) {
                            if (isset($row['is_sla']) && $row['is_sla'] == 0) {
                                $sla_text = '-';
                            } else {
                                $time_used = $row['resolution_time_minutes'];
                                $sla_time = ($row['department_type'] == 'หน่วยงานให้บริการ') ? 15 : 30;
                                if ($time_used <= $sla_time) {
                                    $sla_text = 'ผ่าน';
                                    $sla_class = 'bg-success';
                                } else {
                                    $sla_text = 'ไม่ผ่าน';
                                    $sla_class = 'bg-danger';
                                }
                            }
                        }

                        // สถานะ badge
                        $status_name = $row['final_status_name'] ?? $row['current_status_name'] ?? 'N/A';
                        $status_class = 'bg-secondary';
                        if (strpos($status_name, 'รอ') !== false)
                            $status_class = 'bg-warning text-dark';
                        elseif (strpos($status_name, 'กำลัง') !== false)
                            $status_class = 'bg-info';
                        elseif (strpos($status_name, 'เสร็จ') !== false)
                            $status_class = 'bg-success';
                        elseif (strpos($status_name, 'ไม่ได้') !== false)
                            $status_class = 'bg-danger';

                        $rating_text = isset($row['satisfaction_rating']) ? $row['satisfaction_rating'] : 'N/A';

                        // กำหนด class สำหรับแถวที่ไม่มี SLA
                        $row_class = '';
                        if (isset($row['has_sla']) && $row['has_sla'] === false) {
                            $row_class = 'table-secondary';
                        }
                        ?>
                        <tr class="<?php echo $row_class; ?>">
                            <td class="text-center text-muted"><?php echo $i; ?></td>
                            <td>
                                <div class="fw-medium"><?php echo date('d/m/Y', strtotime($row['request_date'])); ?></div>
                                <small
                                    class="text-muted"><?php echo date('H:i น.', strtotime($row['request_date'])); ?></small>
                            </td>
                            <td>
                                <div class="text-truncate" style="max-width: 200px;"
                                    title="<?php echo htmlspecialchars($row['problem_description']); ?>">
                                    <?php echo htmlspecialchars($row['problem_description']); ?>
                                </div>
                            </td>
                            <td><?php echo htmlspecialchars($row['location_name']); ?></td>
                            <td>
                                <?php if ($row['category_name']): ?>
                                    <span
                                        class="badge bg-light text-dark"><?php echo htmlspecialchars($row['category_name']); ?></span>
                                <?php else: ?>
                                    <span class="text-muted">-</span>
                                <?php endif; ?>
                            </td>
                            <td><span
                                    class="badge <?php echo $status_class; ?>"><?php echo htmlspecialchars($status_name); ?></span>
                            </td>
                            <td class="text-center" data-order="<?php echo $sla_text; ?>">
                                <span class="badge <?php echo $sla_class; ?>"><?php echo $sla_text; ?></span>
                                <span style="display:none;"><?php echo $sla_text; ?></span>
                            </td>
                            <td class="text-center" data-order="<?php echo $rating_text; ?>">
                                <?php if (isset($row['satisfaction_rating'])): ?>
                                    <span class="text-warning">
                                        <?php for ($s = 1; $s <= $row['satisfaction_rating']; $s++): ?>
                                            <i class="bi bi-star-fill"></i>
                                        <?php endfor; ?>
                                    </span>
                                <?php else: ?>
                                    <span class="text-muted">-</span>
                                <?php endif; ?>
                                <span style="display:none;"><?php echo $rating_text; ?></span>
                            </td>
                            <td class="text-center">
                                <button type="button" class="btn btn-sm btn-outline-primary view-detail-btn"
                                    data-id="<?php echo $row['id']; ?>"
                                    data-request-date="<?php echo date('d/m/Y H:i', strtotime($row['request_date'])); ?>"
                                    data-repair-date="<?php echo $row['repair_date'] ? date('d/m/Y H:i', strtotime($row['repair_date'])) : '-'; ?>"
                                    data-problem="<?php echo htmlspecialchars($row['problem_description']); ?>"
                                    data-location="<?php echo htmlspecialchars($row['location_name']); ?>"
                                    data-reporter="<?php echo htmlspecialchars($row['reporter_name']); ?>"
                                    data-admin="<?php echo htmlspecialchars($row['admin_name'] ?? '-'); ?>"
                                    data-category="<?php echo htmlspecialchars($row['category_name'] ?? '-'); ?>"
                                    data-cause="<?php echo htmlspecialchars($row['cause'] ?? '-'); ?>"
                                    data-solution="<?php echo htmlspecialchars($row['solution'] ?? '-'); ?>"
                                    data-status="<?php echo htmlspecialchars($status_name); ?>"
                                    data-resolution-time="<?php echo $row['resolution_time_minutes'] ?? '-'; ?>"
                                    data-sla="<?php echo $sla_text; ?>"
                                    data-rating="<?php echo $row['satisfaction_rating'] ?? '0'; ?>"
                                    data-comment="<?php echo htmlspecialchars($row['satisfaction_comment'] ?? '-'); ?>">
                                    <i class="bi bi-eye"></i>
                                </button>
                            </td>
                        </tr>
                        <?php
                        $i++;
                    endforeach;
                    ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Detail Modal -->
<div class="modal fade" id="detailModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                <h5 class="modal-title text-white"><i class="bi bi-clipboard-data me-2"></i>รายละเอียดใบงาน #<span
                        id="modal-id"></span></h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                    aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <!-- Request Info -->
                <div class="row mb-4">
                    <div class="col-md-6">
                        <div class="card bg-light border-0 h-100">
                            <div class="card-body">
                                <h6 class="text-primary mb-3"><i class="bi bi-send me-2"></i>ข้อมูลการแจ้ง</h6>
                                <table class="table table-sm table-borderless mb-0">
                                    <tr>
                                        <td class="text-muted" style="width: 100px;">วันที่แจ้ง</td>
                                        <td id="modal-request-date" class="fw-medium"></td>
                                    </tr>
                                    <tr>
                                        <td class="text-muted">ผู้แจ้ง</td>
                                        <td id="modal-reporter" class="fw-medium"></td>
                                    </tr>
                                    <tr>
                                        <td class="text-muted">สถานที่</td>
                                        <td id="modal-location" class="fw-medium"></td>
                                    </tr>
                                </table>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card bg-light border-0 h-100">
                            <div class="card-body">
                                <h6 class="text-success mb-3"><i class="bi bi-wrench me-2"></i>ข้อมูลการซ่อม</h6>
                                <table class="table table-sm table-borderless mb-0">
                                    <tr>
                                        <td class="text-muted" style="width: 100px;">วันที่ซ่อม</td>
                                        <td id="modal-repair-date" class="fw-medium"></td>
                                    </tr>
                                    <tr>
                                        <td class="text-muted">ผู้ดำเนินการ</td>
                                        <td id="modal-admin" class="fw-medium"></td>
                                    </tr>
                                    <tr>
                                        <td class="text-muted">ประเภทงาน</td>
                                        <td id="modal-category" class="fw-medium"></td>
                                    </tr>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Problem & Solution -->
                <div class="card border mb-4">
                    <div class="card-body">
                        <div class="row">
                            <div class="col-12 mb-3">
                                <h6 class="text-danger"><i class="bi bi-exclamation-triangle me-2"></i>ปัญหาที่แจ้ง</h6>
                                <p id="modal-problem" class="mb-0 bg-light p-3 rounded"></p>
                            </div>
                            <div class="col-md-6">
                                <h6 class="text-warning"><i class="bi bi-search me-2"></i>สาเหตุ</h6>
                                <p id="modal-cause" class="mb-0 bg-light p-3 rounded"></p>
                            </div>
                            <div class="col-md-6">
                                <h6 class="text-success"><i class="bi bi-check-circle me-2"></i>วิธีแก้ไข</h6>
                                <p id="modal-solution" class="mb-0 bg-light p-3 rounded"></p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Status & SLA -->
                <div class="row">
                    <div class="col-md-4">
                        <div class="text-center p-3 bg-light rounded">
                            <small class="text-muted d-block mb-1">สถานะ</small>
                            <span id="modal-status" class="badge fs-6"></span>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="text-center p-3 bg-light rounded">
                            <small class="text-muted d-block mb-1">ผล SLA</small>
                            <span id="modal-sla" class="badge fs-6"></span>
                            <div class="small mt-1" id="modal-resolution-time"></div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="text-center p-3 bg-light rounded">
                            <small class="text-muted d-block mb-1">ความพึงพอใจ</small>
                            <div id="modal-rating" class="text-warning fs-5"></div>
                        </div>
                    </div>
                </div>

                <!-- Comment -->
                <div id="modal-comment-section" class="mt-4 d-none">
                    <h6 class="text-info"><i class="bi bi-chat-quote me-2"></i>ความคิดเห็น</h6>
                    <div class="bg-light p-3 rounded">
                        <p id="modal-comment" class="mb-0 fst-italic"></p>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <a id="modal-edit-link" href="#" class="btn btn-outline-primary"><i
                        class="bi bi-pencil me-1"></i>แก้ไข</a>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ปิด</button>
            </div>
        </div>
    </div>
</div>

<?php
$stmt->close();
$conn->close();
require_once 'partials/footer.php';
?>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    $(document).ready(function () {
        var reportTitle = 'รายงานการแจ้งซ่อม วันที่ <?php echo date('d/m/Y', strtotime($start_date)); ?> ถึง <?php echo date('d/m/Y', strtotime($end_date)); ?>';

        $('#reportTable').DataTable({
            dom: '<"row"<"col-md-6"l><"col-md-6"f>>rt<"row"<"col-md-6"i><"col-md-6"p>>B',
            buttons: [
                {
                    extend: 'copy', title: reportTitle, className: 'btn btn-sm btn-outline-secondary me-1',
                    exportOptions: {
                        format: {
                            body: function (data, row, column, node) {
                                var hiddenSpan = $(node).find('span[style*="display:none"]');
                                if (hiddenSpan.length > 0) return hiddenSpan.text();
                                return data.replace(/<.*?>/ig, "");
                            }
                        }
                    }
                },
                {
                    extend: 'csv', title: reportTitle, className: 'btn btn-sm btn-outline-secondary me-1', charset: 'utf-8', bom: true,
                    exportOptions: {
                        format: {
                            body: function (data, row, column, node) {
                                var hiddenSpan = $(node).find('span[style*="display:none"]');
                                if (hiddenSpan.length > 0) return hiddenSpan.text();
                                return data.replace(/<.*?>/ig, "");
                            }
                        }
                    }
                },
                {
                    extend: 'excel', title: reportTitle, className: 'btn btn-sm btn-success me-1',
                    exportOptions: {
                        format: {
                            body: function (data, row, column, node) {
                                var hiddenSpan = $(node).find('span[style*="display:none"]');
                                if (hiddenSpan.length > 0) return hiddenSpan.text();
                                return data.replace(/<.*?>/ig, "");
                            }
                        }
                    }
                },
                { extend: 'print', title: reportTitle, className: 'btn btn-sm btn-info' }
            ],
            responsive: true,
            language: { url: 'https://cdn.datatables.net/plug-ins/2.0.8/i18n/th.json' },
            order: [[1, 'desc']],
            pageLength: 25
        });

        // Detail Modal Handler
        $('.view-detail-btn').on('click', function () {
            var btn = $(this);
            $('#modal-id').text(btn.data('id'));
            $('#modal-request-date').text(btn.data('request-date'));
            $('#modal-repair-date').text(btn.data('repair-date'));
            $('#modal-problem').text(btn.data('problem'));
            $('#modal-location').text(btn.data('location'));
            $('#modal-reporter').text(btn.data('reporter'));
            $('#modal-admin').text(btn.data('admin'));
            $('#modal-category').text(btn.data('category'));
            $('#modal-cause').text(btn.data('cause'));
            $('#modal-solution').text(btn.data('solution'));

            // Status badge
            var statusName = btn.data('status');
            var statusClass = 'bg-secondary';
            if (statusName.indexOf('รอ') !== -1) statusClass = 'bg-warning text-dark';
            else if (statusName.indexOf('กำลัง') !== -1) statusClass = 'bg-info';
            else if (statusName.indexOf('เสร็จ') !== -1) statusClass = 'bg-success';
            else if (statusName.indexOf('ไม่ได้') !== -1) statusClass = 'bg-danger';
            $('#modal-status').attr('class', 'badge fs-6 ' + statusClass).text(statusName);

            // SLA badge
            var slaText = btn.data('sla');
            var slaClass = 'bg-secondary';
            if (slaText === 'ผ่าน') slaClass = 'bg-success';
            else if (slaText === 'ไม่ผ่าน') slaClass = 'bg-danger';
            $('#modal-sla').attr('class', 'badge fs-6 ' + slaClass).text(slaText);

            var resTime = btn.data('resolution-time');
            $('#modal-resolution-time').text(resTime !== '-' ? 'ใช้เวลา ' + resTime + ' นาที' : '');

            // Rating stars
            var rating = parseInt(btn.data('rating')) || 0;
            var stars = '';
            for (var i = 1; i <= 5; i++) {
                stars += '<i class="bi bi-star' + (i <= rating ? '-fill' : '') + '"></i>';
            }
            $('#modal-rating').html(rating > 0 ? stars : '<span class="text-muted">-</span>');

            // Comment
            var comment = btn.data('comment');
            if (comment && comment !== '-') {
                $('#modal-comment').text(comment);
                $('#modal-comment-section').removeClass('d-none');
            } else {
                $('#modal-comment-section').addClass('d-none');
            }

            // Edit link
            $('#modal-edit-link').attr('href', 'request_view.php?id=' + btn.data('id'));

            new bootstrap.Modal(document.getElementById('detailModal')).show();
        });

        // Charts
        <?php if (($sla_pass + $sla_fail) > 0): ?>
            new Chart(document.getElementById('slaChart'), {
                type: 'doughnut',
                data: {
                    labels: ['ผ่าน SLA', 'ไม่ผ่าน SLA'],
                    datasets: [{
                        data: [<?php echo $sla_pass; ?>, <?php echo $sla_fail; ?>],
                        backgroundColor: ['rgba(40, 167, 69, 0.8)', 'rgba(220, 53, 69, 0.8)'],
                        borderWidth: 0
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { position: 'bottom' }
                    }
                }
            });
        <?php else: ?>
            document.getElementById('slaChart').parentElement.innerHTML = '<div class="text-center text-muted py-5"><i class="bi bi-pie-chart fs-1"></i><p class="mt-2">ไม่มีข้อมูล SLA</p></div>';
        <?php endif; ?>

        <?php if (!empty($category_stats)): ?>
            new Chart(document.getElementById('categoryChart'), {
                type: 'doughnut',
                data: {
                    labels: <?php echo json_encode(array_keys($category_stats)); ?>,
                    datasets: [{
                        data: <?php echo json_encode(array_values($category_stats)); ?>,
                        backgroundColor: ['rgba(102, 126, 234, 0.8)', 'rgba(118, 75, 162, 0.8)', 'rgba(240, 147, 251, 0.8)', 'rgba(79, 172, 254, 0.8)', 'rgba(17, 153, 142, 0.8)'],
                        borderWidth: 0
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { position: 'bottom' }
                    }
                }
            });
        <?php else: ?>
            document.getElementById('categoryChart').parentElement.innerHTML = '<div class="text-center text-muted py-5"><i class="bi bi-tags fs-1"></i><p class="mt-2">ไม่มีข้อมูลประเภท</p></div>';
        <?php endif; ?>

        <?php if (!empty($top_locations)): ?>
            new Chart(document.getElementById('locationChart'), {
                type: 'bar',
                data: {
                    labels: <?php echo json_encode(array_keys($top_locations)); ?>,
                    datasets: [{
                        label: 'จำนวนรายการ',
                        data: <?php echo json_encode(array_values($top_locations)); ?>,
                        backgroundColor: 'rgba(220, 53, 69, 0.7)',
                        borderRadius: 5
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    indexAxis: 'y',
                    plugins: {
                        legend: { display: false }
                    },
                    scales: {
                        x: { beginAtZero: true }
                    }
                }
            });
        <?php else: ?>
            document.getElementById('locationChart').parentElement.innerHTML = '<div class="text-center text-muted py-5"><i class="bi bi-geo-alt fs-1"></i><p class="mt-2">ไม่มีข้อมูลสถานที่</p></div>';
        <?php endif; ?>
    });
</script>