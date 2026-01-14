<?php
require_once 'partials/header.php';
require_once '../db_connect.php';

// --- สร้างเงื่อนไขสำหรับกรองข้อมูลตามสถานะ ---
$page_title = 'รายการแจ้งซ่อมทั้งหมด';
$params = [];
$param_types = '';
$sql_where = '';

if (isset($_GET['status']) && !empty($_GET['status'])) {
    $status_id = (int)$_GET['status'];
    $sql_where = " WHERE r.current_status_id = ?";
    $params[] = $status_id;
    $param_types .= 'i';
    
    // ดึงชื่อสถานะมาแสดงเป็น Title
    $status_name_stmt = $conn->prepare("SELECT status_name FROM statuses WHERE id = ?");
    $status_name_stmt->bind_param("i", $status_id);
    $status_name_stmt->execute();
    $status_result = $status_name_stmt->get_result();
    if($status_row = $status_result->fetch_assoc()) {
        $page_title = 'รายการ: ' . htmlspecialchars($status_row['status_name']);
    }
    $status_name_stmt->close();
}

// ===== ส่วนที่แก้ไข: เพิ่ม l.department_type และ r.resolution_time_minutes =====
$requests_sql = "
    SELECT 
        r.id, r.request_date, r.problem_description, r.current_status_id,
        l.location_name, p.reporter_name, s_current.status_name as current_status_name,
        r.repair_date, a.full_name as admin_name, c.category_name, r.cause, r.solution,
        s_final.status_name as final_status_name, r.is_phone_call,
        l.department_type, r.resolution_time_minutes
    FROM requests r
    LEFT JOIN locations l ON r.location_id = l.id
    LEFT JOIN reporters p ON r.reporter_id = p.id
    LEFT JOIN statuses s_current ON r.current_status_id = s_current.id
    LEFT JOIN admins a ON r.admin_id = a.id
    LEFT JOIN categories c ON r.category_id = c.id
    LEFT JOIN statuses s_final ON r.final_status_id = s_final.id
    $sql_where
    ORDER BY r.request_date DESC
";

$stmt = $conn->prepare($requests_sql);
if (!empty($params)) {
    $stmt->bind_param($param_types, ...$params);
}
$stmt->execute();
$requests_result = $stmt->get_result();
?>

<div class="card">
    <div class="card-header">
        <h5 class="card-title mb-0"><?php echo $page_title; ?></h5>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover table-bordered datatable" style="width:100%;">
                <thead class="table-light">
                    <tr>
                        <th style="width:15%;">วันที่แจ้ง</th>
                        <th style="width:35%;">ปัญหา</th>
                        <th style="width:15%;">สถานที่</th>
                        <th style="width:15%;">ผู้แจ้ง</th>
                        <th style="width:10%;">สถานะ</th>
                        <th style="width:10%;" class="text-center">จัดการ</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($requests_result && $requests_result->num_rows > 0): ?>
                        <?php while($row = $requests_result->fetch_assoc()): ?>
                            <tr id="request-row-<?php echo $row['id']; ?>">
                                <td><?php echo date('d/m/Y H:i', strtotime($row['request_date'])); ?></td>
                                <td><?php echo htmlspecialchars($row['problem_description']); ?></td>
                                <td><?php echo htmlspecialchars($row['location_name']); ?></td>
                                <td><?php echo htmlspecialchars($row['reporter_name']); ?></td>
                                <td><span class="badge bg-primary"><?php echo htmlspecialchars($row['current_status_name']); ?></span></td>
                                <td class="text-center">
                                    <?php
                                    // --- เงื่อนไขการแสดงปุ่ม ---
                                    if ($row['current_status_id'] == 1) { // 1 = รอรับเรื่อง
                                        echo '<button class="btn btn-primary btn-sm" onclick="acceptRequest('.$row['id'].')">รับเรื่อง</button>';
                                    } elseif ($row['current_status_id'] == 2) { // 2 = กำลังดำเนินการ
                                        echo '<a href="request_view.php?id='.$row['id'].'" class="btn btn-warning btn-sm">ดู/แก้ไข</a>';
                                    } else {
                                        echo '<button type="button" class="btn btn-success btn-sm view-details-btn" 
                                                    data-bs-toggle="modal" 
                                                    data-bs-target="#requestDetailModal"
                                                    data-request=\''.htmlspecialchars(json_encode($row), ENT_QUOTES, 'UTF-8').'\'>
                                                    ดูรายละเอียด
                                              </button>';
                                    }
                                    ?>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal for Request Details -->
<div class="modal fade" id="requestDetailModal" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">รายละเอียดการแจ้งซ่อม</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body" id="modal-content-wrapper"></div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ปิด</button>
      </div>
    </div>
  </div>
</div>

<!-- Axios for AJAX requests -->
<script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>

<?php
$stmt->close();
$conn->close();
require_once 'partials/footer.php';
?>

<script>
$(document).ready(function() {
    // ---- 1. เริ่มต้นการทำงานของ DataTables ----
    $('.datatable').DataTable({
        language: { url: 'https://cdn.datatables.net/plug-ins/2.0.8/i18n/th.json' },
        order: [[0, 'desc']] // เรียงตามวันที่แจ้งล่าสุด
    });

    // ---- 2. สคริปต์สำหรับเปิด Modal ----
    const modal = document.getElementById('requestDetailModal');
    if(modal) {
        modal.addEventListener('show.bs.modal', function (event) {
            const button = event.relatedTarget;
            const requestData = JSON.parse(button.getAttribute('data-request'));
            const modalTitle = modal.querySelector('.modal-title');
            const modalBody = modal.querySelector('#modal-content-wrapper');

            modalTitle.textContent = 'รายละเอียดการแจ้งซ่อม ID: ' + requestData.id;
            const formatDate = (dateString) => {
                if (!dateString) return 'N/A';
                const date = new Date(dateString);
                return date.toLocaleDateString('th-TH', { year: 'numeric', month: 'long', day: 'numeric', hour: '2-digit', minute: '2-digit' });
            };

            // ===== ส่วนที่แก้ไข: เพิ่มการคำนวณและแสดงผล SLA =====
            let sla_status_html = '<span class="badge bg-secondary">รอปิดงาน</span>';
            if (requestData.resolution_time_minutes !== null) {
                const time_used = parseInt(requestData.resolution_time_minutes);
                const sla_time = (requestData.department_type === 'หน่วยงานให้บริการ') ? 15 : 30;
                
                if (time_used <= sla_time) {
                    sla_status_html = `<span class="badge bg-success"><i class="bi bi-check-circle-fill"></i> ผ่าน</span>`;
                } else {
                    sla_status_html = `<span class="badge bg-danger"><i class="bi bi-x-circle-fill"></i> ไม่ผ่าน</span>`;
                }
            }
            
            let content = `
                <h5>ข้อมูลการแจ้ง</h5>
                <table class="table table-sm table-bordered">
                    <tr><th style="width: 30%;">วันที่แจ้ง</th><td>${formatDate(requestData.request_date)}</td></tr>
                    <tr><th>ผู้แจ้ง</th><td>${requestData.reporter_name || 'N/A'}</td></tr>
                    <tr><th>สถานที่</th><td>${requestData.location_name || 'N/A'}</td></tr>
                    <tr><th>ปัญหา</th><td>${requestData.problem_description || 'N/A'}</td></tr>
                </table>
                <hr>
                <h5>ผลการดำเนินงาน</h5>
                <table class="table table-sm table-bordered">
                    <tr><th style="width: 30%;">สถานะล่าสุด</th><td><span class="badge bg-success">${requestData.final_status_name || requestData.current_status_name}</span></td></tr>
                    <tr><th>วันที่แก้ไข</th><td>${formatDate(requestData.repair_date)}</td></tr>
                    <tr><th>ผู้ดำเนินการ</th><td>${requestData.admin_name || 'N/A'}</td></tr>
                    <tr><th>ประเภทงาน</th><td>${requestData.category_name || 'N/A'}</td></tr>
                    <tr><th>สาเหตุ</th><td>${requestData.cause || 'N/A'}</td></tr>
                    <tr><th>วิธีแก้ไข</th><td>${requestData.solution || 'N/A'}</td></tr>
                    <tr><th>มีการโทรศัพท์</th><td>${requestData.is_phone_call == 1 ? 'ใช่' : 'ไม่'}</td></tr>
                    <tr><th>เวลาที่ใช้ (นาที)</th><td>${requestData.resolution_time_minutes ?? 'N/A'}</td></tr>
                    <tr><th>SLA</th><td>${sla_status_html}</td></tr>
                </table>
            `;
            modalBody.innerHTML = content;
        });
    }
});

// ---- 3. ฟังก์ชันสำหรับรับเรื่อง (เหมือนใน Dashboard) ----
function acceptRequest(requestId) {
    Swal.fire({
        title: 'ยืนยันการรับเรื่อง?',
        text: "คุณต้องการรับงานแจ้งซ่อมนี้ใช่หรือไม่",
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        confirmButtonText: 'ใช่, รับเรื่องเลย!',
        cancelButtonText: 'ยกเลิก'
    }).then((result) => {
        if (result.isConfirmed) {
            const formData = new FormData();
            formData.append('request_id', requestId);

            axios.post('../api/update_request_status.php', formData)
                .then(function(response) {
                    if (response.data.success) {
                        Swal.fire('รับเรื่องสำเร็จ!', 'รายการถูกย้ายไป "กำลังดำเนินการ" แล้ว', 'success')
                        .then(() => {
                            location.reload(); 
                        });
                    } else {
                        Swal.fire('เกิดข้อผิดพลาด!', response.data.message, 'error');
                    }
                })
                .catch(function(error) {
                    Swal.fire('เกิดข้อผิดพลาด!', 'ไม่สามารถเชื่อมต่อกับเซิร์ฟเวอร์ได้', 'error');
                });
        }
    });
}
</script>
