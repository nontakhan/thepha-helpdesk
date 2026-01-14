<?php
require_once 'partials/header.php';
require_once '../db_connect.php';

// ตรวจสอบว่ามี ID ส่งมาหรือไม่
if (!isset($_GET['id']) || empty($_GET['id'])) {
    echo "<div class='alert alert-danger'>ไม่พบรหัสรายการที่ต้องการ</div>";
    require_once 'partials/footer.php';
    exit();
}
$request_id = (int)$_GET['id'];

// ดึงข้อมูลหลักของใบงาน
$sql = "SELECT * FROM requests WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $request_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo "<div class='alert alert-danger'>ไม่พบข้อมูลใบงาน ID: $request_id</div>";
    require_once 'partials/footer.php';
    exit();
}
$request = $result->fetch_assoc();
$stmt->close();

// --- ดึงข้อมูลสำหรับ Dropdowns ---
$locations = $conn->query("SELECT id, location_name FROM locations ORDER BY location_name ASC");
$reporters = $conn->query("SELECT id, reporter_name FROM reporters WHERE status = 'Y' ORDER BY reporter_name ASC");
$categories = $conn->query("SELECT id, category_name FROM categories ORDER BY category_name ASC");
$statuses = $conn->query("SELECT id, status_name FROM statuses ORDER BY status_name");
?>

<form id="repairForm">
<input type="hidden" name="request_id" value="<?php echo $request['id']; ?>">

<div class="row">
    <!-- ส่วนข้อมูลการแจ้งซ่อมที่แก้ไขได้ -->
    <div class="col-lg-5">
        <div class="card border-primary mb-3">
            <div class="card-header bg-primary text-white"><h5 class="mb-0">ข้อมูลการแจ้งซ่อม (ID: <?php echo $request['id']; ?>)</h5></div>
            <div class="card-body">
                <div class="mb-3">
                    <label class="form-label">วันที่แจ้ง</label>
                    <input type="datetime-local" class="form-control" name="request_date" value="<?php echo date('Y-m-d\TH:i', strtotime($request['request_date'])); ?>" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">ผู้แจ้ง</label>
                    <select class="form-select" id="reporter_id_select" name="reporter_id" required>
                        <?php while($row = $reporters->fetch_assoc()): ?>
                            <option value="<?php echo $row['id']; ?>" <?php echo ($request['reporter_id'] == $row['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($row['reporter_name']); ?>
                            </option>
                        <?php endwhile; mysqli_data_seek($reporters, 0); ?>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">สถานที่</label>
                    <select class="form-select" id="location_id_select" name="location_id" required>
                        <?php while($row = $locations->fetch_assoc()): ?>
                            <option value="<?php echo $row['id']; ?>" <?php echo ($request['location_id'] == $row['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($row['location_name']); ?>
                            </option>
                        <?php endwhile; mysqli_data_seek($locations, 0); ?>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">รายละเอียดปัญหา</label>
                    <textarea class="form-control" name="problem_description" rows="4" required><?php echo htmlspecialchars($request['problem_description']); ?></textarea>
                </div>
            </div>
        </div>
    </div>

    <!-- ส่วนที่ Admin บันทึกการแก้ไข -->
    <div class="col-lg-7">
        <div class="card border-success">
            <div class="card-header bg-success text-white"><h5 class="mb-0">บันทึกผลการดำเนินงาน</h5></div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="repair_date" class="form-label">วันที่แก้ไข</label>
                        <input type="datetime-local" class="form-control" name="repair_date" id="repair_date" value="<?php echo $request['repair_date'] ? date('Y-m-d\TH:i', strtotime($request['repair_date'])) : date('Y-m-d\TH:i'); ?>" required>
                    </div>
                     <div class="col-md-6 mb-3">
                        <label for="category_id" class="form-label">ประเภทงานซ่อม</label>
                        <select name="category_id" id="category_id" class="form-select" required>
                            <option value="">-- เลือกประเภท --</option>
                            <?php while($cat = $categories->fetch_assoc()): ?>
                                <option value="<?php echo $cat['id']; ?>" <?php echo ($request['category_id'] == $cat['id']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($cat['category_name']); ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                </div>
                <div class="mb-3">
                    <label for="cause" class="form-label">สาเหตุของปัญหา</label>
                    <textarea name="cause" id="cause" rows="3" class="form-control"><?php echo htmlspecialchars($request['cause']); ?></textarea>
                </div>
                 <div class="mb-3">
                    <label for="solution" class="form-label">วิธีการแก้ไข</label>
                    <textarea name="solution" id="solution" rows="3" class="form-control" required><?php echo htmlspecialchars($request['solution']); ?></textarea>
                </div>
                <div class="row align-items-end">
                    <div class="col-md-8 mb-3">
                        <label for="final_status_id" class="form-label">สถานะหลังการแก้ไข</label>
                         <select name="final_status_id" id="final_status_id" class="form-select" required>
                            <option value="">-- เลือกสถานะ --</option>
                            <?php while($stat = $statuses->fetch_assoc()): ?>
                                <?php if ($stat['id'] != 1): ?>
                                     <option value="<?php echo $stat['id']; ?>" <?php echo ($request['final_status_id'] == $stat['id']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($stat['status_name']); ?></option>
                                <?php endif; ?>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="col-md-4 mb-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="is_phone_call" value="1" id="is_phone_call" <?php echo ($request['is_phone_call'] == 1) ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="is_phone_call">มีการโทรศัพท์</label>
                        </div>
                    </div>
                </div>
            </div>
            <!-- ส่วนท้ายการ์ด: ปุ่มลบ และ บันทึก -->
            <div class="card-footer bg-transparent">
                <div class="d-flex justify-content-between">
                    <button type="button" class="btn btn-outline-danger" id="deleteRequestBtn">
                        <i class="bi bi-trash"></i> ลบรายการ
                    </button>
                    <button type="submit" class="btn btn-success">
                        <i class="bi bi-save"></i> บันทึกการซ่อม
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
</form>

<?php
$conn->close();
require_once 'partials/footer.php';
?>

<script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
<script>
$(document).ready(function() {
    // เริ่มต้นการทำงานของ Select2
    $('#reporter_id_select').select2({ theme: 'bootstrap-5' });
    $('#location_id_select').select2({ theme: 'bootstrap-5' });

    // 1. กรณีบันทึก (Update)
    $('#repairForm').on('submit', function(e) {
        e.preventDefault();
        var formData = new FormData(this);
        formData.append('action', 'update_full'); // กำหนด action เป็น update_full

        // ใช้การแจ้งเตือนแบบ Loading
        Swal.fire({
            title: 'กำลังบันทึกข้อมูล...',
            allowOutsideClick: false,
            didOpen: () => { Swal.showLoading(); }
        });

        axios.post('../api/save_repair_details.php', formData)
            .then(function(response) {
                if(response.data.success) {
                    Swal.fire({ icon: 'success', title: 'สำเร็จ!', text: response.data.message })
                    .then(() => { window.location.href = response.data.redirect_url; });
                } else {
                    Swal.fire({ icon: 'error', title: 'ผิดพลาด!', text: response.data.message });
                }
            })
            .catch(function(error) {
                console.error(error);
                Swal.fire({ icon: 'error', title: 'ผิดพลาด!', text: 'ไม่สามารถเชื่อมต่อเซิร์ฟเวอร์ได้' });
            });
    });

    // 2. กรณีลบ (Delete)
    $('#deleteRequestBtn').on('click', function() {
        const requestId = <?php echo $request_id; ?>;
        
        Swal.fire({
            title: 'ยืนยันการลบ?',
            text: `คุณต้องการลบรายการแจ้งซ่อม ID: ${requestId} นี้อย่างถาวรใช่หรือไม่?`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'ใช่, ลบเลย!',
            cancelButtonText: 'ยกเลิก'
        }).then((result) => {
            if (result.isConfirmed) {
                var formData = new FormData();
                formData.append('action', 'delete'); // กำหนด action เป็น delete
                formData.append('request_id', requestId);
                
                // ใช้ API ตัวเดียวกับ Save
                axios.post('../api/save_repair_details.php', formData)
                    .then(function(response) {
                        if (response.data.success) {
                            Swal.fire('ลบสำเร็จ!', 'รายการถูกลบออกจากระบบแล้ว', 'success')
                            .then(() => { window.location.href = response.data.redirect_url; });
                        } else {
                            Swal.fire('ผิดพลาด!', response.data.message, 'error');
                        }
                    })
                    .catch(function() {
                        Swal.fire('ผิดพลาด!', 'ไม่สามารถเชื่อมต่อเซิร์ฟเวอร์ได้', 'error');
                    });
            }
        });
    });
});
</script>