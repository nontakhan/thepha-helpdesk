<?php
require_once 'partials/header.php';
require_once '../db_connect.php';

// ดึงข้อมูลผู้แจ้งทั้งหมดจากฐานข้อมูล
$sql = "SELECT id, reporter_name, status FROM reporters ORDER BY reporter_name ASC";
$result = $conn->query($sql);
?>

<div class="row">
    <!-- ส่วนฟอร์มสำหรับเพิ่ม/แก้ไข -->
    <div class="col-lg-4 mb-4">
        <div class="card">
            <div class="card-header"><h5 class="card-title mb-0" id="form-title">เพิ่ม/แก้ไข รายชื่อผู้แจ้ง</h5></div>
            <div class="card-body">
                <form id="reporterForm" method="POST" action="../api/master_data/reporter_actions.php">
                    <input type="hidden" name="action" id="form-action" value="add">
                    <input type="hidden" name="id" id="reporter-id" value="">
                    <div class="mb-3">
                        <label for="reporter-name" class="form-label">ชื่อผู้แจ้ง</label>
                        <input type="text" class="form-control" id="reporter-name" name="reporter_name" required placeholder="เช่น นายสมชาย ใจดี">
                    </div>
                    <!-- ===== ส่วนที่เพิ่มเข้ามา ===== -->
                    <div class="mb-3">
                        <label class="form-label">สถานะ</label>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="status" id="status_y" value="Y" checked>
                            <label class="form-check-label" for="status_y">ใช้งาน (Y)</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="status" id="status_n" value="N">
                            <label class="form-check-label" for="status_n">ไม่ใช้งาน (N)</label>
                        </div>
                    </div>
                    <!-- ===== สิ้นสุดส่วนที่เพิ่ม ===== -->
                    <button type="submit" class="btn btn-primary w-100" id="submit-button">เพิ่มข้อมูล</button>
                    <button type="button" class="btn btn-secondary w-100 mt-2 d-none" id="cancel-button">ยกเลิกการแก้ไข</button>
                </form>
            </div>
        </div>
    </div>

    <!-- ส่วนตารางแสดงข้อมูล -->
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header"><h5 class="card-title mb-0">รายชื่อผู้แจ้งทั้งหมด</h5></div>
            <div class="card-body">
                <table id="reportersTable" class="table table-striped table-bordered" style="width:100%">
                    <thead>
                        <tr>
                            <th>ชื่อผู้แจ้ง</th>
                            <th class="text-center">สถานะ</th>
                            <th class="text-center" style="width: 25%;">จัดการ</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($result && $result->num_rows > 0): ?>
                            <?php while($row = $result->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($row['reporter_name']); ?></td>
                                    <td class="text-center">
                                        <?php if ($row['status'] == 'Y'): ?>
                                            <span class="badge bg-success">ใช้งาน</span>
                                        <?php else: ?>
                                            <span class="badge bg-danger">ไม่ใช้งาน</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center">
                                        <button class="btn btn-sm btn-warning edit-btn" 
                                                data-id="<?php echo $row['id']; ?>" 
                                                data-name="<?php echo htmlspecialchars($row['reporter_name']); ?>"
                                                data-status="<?php echo $row['status']; ?>">
                                            แก้ไข
                                        </button>
                                        <button class="btn btn-sm btn-danger delete-btn" data-id="<?php echo $row['id']; ?>" data-name="<?php echo htmlspecialchars(addslashes($row['reporter_name'])); ?>">
                                            ลบ
                                        </button>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php
$conn->close();
require_once 'partials/footer.php';
?>

<script>
$(document).ready(function() {
    $('#reportersTable').DataTable({
        language: { url: 'https://cdn.datatables.net/plug-ins/2.0.8/i18n/th.json' }
    });

    const form = document.getElementById('reporterForm');
    const formTitle = document.getElementById('form-title');
    const formAction = document.getElementById('form-action');
    const reporterId = document.getElementById('reporter-id');
    const reporterNameInput = document.getElementById('reporter-name');
    const submitButton = document.getElementById('submit-button');
    const cancelButton = document.getElementById('cancel-button');

    function resetForm() {
        formTitle.textContent = 'เพิ่ม/แก้ไข รายชื่อผู้แจ้ง';
        formAction.value = 'add';
        reporterId.value = '';
        form.reset();
        document.getElementById('status_y').checked = true; // ตั้งค่าเริ่มต้น
        submitButton.innerHTML = 'เพิ่มข้อมูล';
        submitButton.classList.remove('btn-warning');
        submitButton.classList.add('btn-primary');
        cancelButton.classList.add('d-none');
    }

    $('#reportersTable tbody').on('click', '.edit-btn', function() {
        const button = $(this);
        const id = button.data('id');
        const name = button.data('name');
        const status = button.data('status'); // ดึงค่า status
        
        formTitle.textContent = 'แก้ไขรายชื่อผู้แจ้ง';
        formAction.value = 'update';
        reporterId.value = id;
        reporterNameInput.value = name;
        // ตั้งค่า radio button ตาม status ที่ได้มา
        if (status === 'Y') {
            document.getElementById('status_y').checked = true;
        } else {
            document.getElementById('status_n').checked = true;
        }
        submitButton.innerHTML = 'บันทึกการแก้ไข';
        submitButton.classList.remove('btn-primary');
        submitButton.classList.add('btn-warning');
        cancelButton.classList.remove('d-none');
        reporterNameInput.focus();
    });

    $('#reportersTable tbody').on('click', '.delete-btn', function() {
        const button = $(this);
        const id = button.data('id');
        const name = button.data('name');
        deleteReporter(id, name);
    });

    cancelButton.addEventListener('click', function() {
        resetForm();
    });
});

function deleteReporter(id, name) {
    Swal.fire({
        title: 'ยืนยันการลบ?',
        text: `คุณต้องการลบ "${name}" ใช่หรือไม่?`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'ใช่, ลบเลย!',
        cancelButtonText: 'ยกเลิก'
    }).then((result) => {
        if (result.isConfirmed) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = '../api/master_data/reporter_actions.php';
            const hiddenFieldAction = document.createElement('input');
            hiddenFieldAction.type = 'hidden';
            hiddenFieldAction.name = 'action';
            hiddenFieldAction.value = 'delete';
            form.appendChild(hiddenFieldAction);
            const hiddenFieldId = document.createElement('input');
            hiddenFieldId.type = 'hidden';
            hiddenFieldId.name = 'id';
            hiddenFieldId.value = id;
            form.appendChild(hiddenFieldId);
            document.body.appendChild(form);
            form.submit();
        }
    })
}
</script>
