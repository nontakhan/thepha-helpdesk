<?php
require_once 'partials/header.php';
require_once '../db_connect.php';

// ดึงข้อมูลประเภทกิจกรรมสำหรับสร้างปุ่ม
$activity_types_sql = "SELECT id, type_name, color FROM activity_types ORDER BY type_name ASC";
$activity_types_result = $conn->query($activity_types_sql);

// ดึงข้อมูลกิจกรรมที่ยังไม่เสร็จสิ้นของ User ที่ Login อยู่
$current_admin_id = $_SESSION['admin_id'];
$unfinished_sql = "
    SELECT a.id, a.title, a.start_time, t.type_name 
    FROM activities a
    JOIN activity_types t ON a.activity_type_id = t.id
    WHERE a.admin_id = ? AND a.end_time IS NULL 
    ORDER BY a.start_time DESC
";
$stmt_unfinished = $conn->prepare($unfinished_sql);
$stmt_unfinished->bind_param("i", $current_admin_id);
$stmt_unfinished->execute();
$unfinished_result = $stmt_unfinished->get_result();
?>
<style>
    /* CSS สำหรับปุ่มเลือกประเภทกิจกรรม */
    .activity-type-buttons .btn {
        color: white;
        text-shadow: 1px 1px 2px rgba(0,0,0,0.4);
        border: 3px solid transparent;
        transition: all 0.2s ease-in-out;
    }
    .activity-type-buttons .btn.active {
        border-color: #000;
        transform: scale(1.05);
        box-shadow: 0 4px 15px rgba(0,0,0,0.2);
    }
</style>

<div class="row justify-content-center">
    <!-- Form บันทึกกิจกรรม -->
    <div class="col-lg-8 mb-4">
        <div class="card">
            <div class="card-header"><h5 class="card-title mb-0">บันทึกกิจกรรมการทำงาน</h5></div>
            <div class="card-body">
                <form id="logActivityForm">
                    <input type="hidden" name="action" value="create">
                    <div class="mb-3">
                        <label class="form-label">ประเภทกิจกรรม</label>
                        <div id="activity-type-selector" class="d-flex flex-wrap gap-2 activity-type-buttons">
                            <?php while($row = $activity_types_result->fetch_assoc()): ?>
                                <button type="button" class="btn" style="background-color: <?php echo htmlspecialchars($row['color']); ?>;" data-type-id="<?php echo $row['id']; ?>">
                                    <?php echo htmlspecialchars($row['type_name']); ?>
                                </button>
                            <?php endwhile; ?>
                        </div>
                        <input type="hidden" name="activity_type_id" id="activity_type_id" required>
                    </div>
                    <div class="mb-3">
                        <label for="title" class="form-label">ชื่องาน / รายละเอียด</label>
                        <textarea class="form-control" id="title" name="title" rows="3" required placeholder="ระบุชื่องานที่ทำ..."></textarea>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="start_time" class="form-label">เวลาเริ่มต้น</label>
                            <input type="datetime-local" class="form-control" id="start_time" name="start_time" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="end_time" class="form-label">เวลาสิ้นสุด (ไม่บังคับ)</label>
                            <input type="datetime-local" class="form-control" id="end_time" name="end_time">
                        </div>
                    </div>
                    <div class="d-grid"><button type="submit" class="btn btn-primary">บันทึกกิจกรรม</button></div>
                </form>
            </div>
        </div>
    </div>

    <!-- รายการที่ยังไม่เสร็จสิ้น -->
    <?php if ($unfinished_result->num_rows > 0): ?>
    <div class="col-lg-8">
        <div class="card border-warning">
            <div class="card-header bg-warning"><h5 class="card-title mb-0">รายการที่ยังไม่เสร็จสิ้น</h5></div>
            <div class="card-body">
                <ul class="list-group list-group-flush" id="unfinished-list">
                    <?php while($row = $unfinished_result->fetch_assoc()): ?>
                    <li class="list-group-item d-flex justify-content-between align-items-center" id="activity-<?php echo $row['id']; ?>">
                        <div>
                            <h6 class="mb-1"><?php echo htmlspecialchars($row['title']); ?></h6>
                            <small class="text-muted">ประเภท: <?php echo htmlspecialchars($row['type_name']); ?> | เริ่มเมื่อ: <?php echo date('d/m/Y H:i', strtotime($row['start_time'])); ?></small>
                        </div>
                        <button class="btn btn-success btn-sm finish-btn" data-id="<?php echo $row['id']; ?>">สิ้นสุดงานนี้</button>
                    </li>
                    <?php endwhile; ?>
                </ul>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<?php
$stmt_unfinished->close();
$conn->close();
require_once 'partials/footer.php';
?>

<script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const activitySelector = document.getElementById('activity-type-selector');
    const hiddenInput = document.getElementById('activity_type_id');

    activitySelector.addEventListener('click', function(event) {
        if (event.target.tagName === 'BUTTON') {
            activitySelector.querySelectorAll('.btn').forEach(btn => btn.classList.remove('active'));
            event.target.classList.add('active');
            hiddenInput.value = event.target.dataset.typeId;
        }
    });

    document.getElementById('logActivityForm').addEventListener('submit', function(event) {
        event.preventDefault();
        const form = this;
        const formData = new FormData(form);
        if (!formData.get('activity_type_id')) {
            Swal.fire('ข้อมูลไม่ครบถ้วน', 'กรุณาเลือกประเภทกิจกรรม', 'warning');
            return;
        }
        if (formData.get('end_time')) {
            const startTime = new Date(formData.get('start_time'));
            const endTime = new Date(formData.get('end_time'));
            if (endTime < startTime) {
                Swal.fire('ข้อมูลผิดพลาด', 'เวลาสิ้นสุดต้องไม่น้อยกว่าเวลาเริ่มต้น', 'error');
                return;
            }
        }
        axios.post('../api/save_activity.php', formData)
            .then(function(response) {
                if (response.data.success) {
                    Swal.fire({ icon: 'success', title: 'บันทึกสำเร็จ!', text: response.data.message, timer: 1500, showConfirmButton: false })
                    // ===== ส่วนที่แก้ไข: เปลี่ยนเป็น Redirect =====
                    .then(() => { window.location.href = 'calendar.php'; });
                } else {
                    Swal.fire('เกิดข้อผิดพลาด', response.data.message, 'error');
                }
            });
    });

    document.getElementById('unfinished-list')?.addEventListener('click', function(event) {
        if (event.target.classList.contains('finish-btn')) {
            const activityId = event.target.dataset.id;
            const now = new Date();
            const year = now.getFullYear();
            const month = String(now.getMonth() + 1).padStart(2, '0');
            const day = String(now.getDate()).padStart(2, '0');
            const hours = String(now.getHours()).padStart(2, '0');
            const minutes = String(now.getMinutes()).padStart(2, '0');
            const defaultEndTime = `${year}-${month}-${day}T${hours}:${minutes}`;

            Swal.fire({
                title: 'ยืนยันเวลาสิ้นสุด',
                html: `<p>กรุณายืนยันหรือแก้ไขเวลาสิ้นสุดของกิจกรรมนี้</p>
                       <input type="datetime-local" id="swal-end-time" class="form-control" value="${defaultEndTime}">`,
                confirmButtonText: 'ยืนยัน',
                showCancelButton: true,
                cancelButtonText: 'ยกเลิก',
                preConfirm: () => {
                    return document.getElementById('swal-end-time').value;
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    const formData = new FormData();
                    formData.append('action', 'finish');
                    formData.append('activity_id', activityId);
                    formData.append('end_time', result.value);

                    axios.post('../api/save_activity.php', formData)
                        .then(function(response) {
                            if (response.data.success) {
                                Swal.fire({ icon: 'success', title: 'สำเร็จ!', text: response.data.message, timer: 1500, showConfirmButton: false })
                                // ===== ส่วนที่แก้ไข: เปลี่ยนเป็น Redirect =====
                                .then(() => { window.location.href = 'calendar.php'; });
                            } else {
                                Swal.fire('เกิดข้อผิดพลาด', response.data.message, 'error');
                            }
                        });
                }
            });
        }
    });
});
</script>
