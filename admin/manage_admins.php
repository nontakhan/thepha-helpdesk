<?php
require_once 'partials/header.php';
require_once '../db_connect.php';

// ดึงข้อมูลเจ้าหน้าที่ทั้งหมดจากฐานข้อมูล
$sql = "SELECT id, full_name, username, status FROM admins ORDER BY full_name ASC";
$result = $conn->query($sql);

// แสดง error message ถ้ามี
$error_message = '';
if (isset($_SESSION['error_message'])) {
    $error_message = $_SESSION['error_message'];
    unset($_SESSION['error_message']);
}
?>

<?php if ($error_message): ?>
<div class="alert alert-danger alert-dismissible fade show" role="alert">
    <?php echo htmlspecialchars($error_message); ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<div class="row">
    <!-- ส่วนฟอร์มสำหรับเพิ่ม/แก้ไข -->
    <div class="col-lg-4 mb-4">
        <div class="card">
            <div class="card-header"><h5 class="card-title mb-0" id="form-title">เพิ่ม/แก้ไข เจ้าหน้าที่ซ่อม</h5></div>
            <div class="card-body">
                <form id="adminForm" method="POST" action="../api/master_data/admin_actions.php">
                    <input type="hidden" name="action" id="form-action" value="add">
                    <input type="hidden" name="id" id="admin-id" value="">
                    <div class="mb-3">
                        <label for="full-name" class="form-label">ชื่อ-นามสกุล</label>
                        <input type="text" class="form-control" id="full-name" name="full_name" required placeholder="เช่น นายสมชาย ใจดี">
                    </div>
                    <div class="mb-3">
                        <label for="username" class="form-label">ชื่อผู้ใช้ (Username)</label>
                        <input type="text" class="form-control" id="username" name="username" required placeholder="เช่น somchai">
                    </div>
                    <div class="mb-3">
                        <label for="password" class="form-label">รหัสผ่าน</label>
                        <input type="password" class="form-control" id="password" name="password" placeholder="กรอกรหัสผ่าน">
                        <small class="text-muted d-none" id="password-hint">เว้นว่างหากไม่ต้องการเปลี่ยนรหัสผ่าน</small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">สถานะ</label>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="status" id="status_y" value="Y" checked>
                            <label class="form-check-label" for="status_y">ใช้งาน (Y)</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="status" id="status_n" value="N">
                            <label class="form-check-label" for="status_n">ปิดใช้งาน (N)</label>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary w-100" id="submit-button">เพิ่มข้อมูล</button>
                    <button type="button" class="btn btn-secondary w-100 mt-2 d-none" id="cancel-button">ยกเลิกการแก้ไข</button>
                </form>
            </div>
        </div>
    </div>

    <!-- ส่วนตารางแสดงข้อมูล -->
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header"><h5 class="card-title mb-0">รายชื่อเจ้าหน้าที่ซ่อมทั้งหมด</h5></div>
            <div class="card-body">
                <table id="adminsTable" class="table table-striped table-bordered" style="width:100%">
                    <thead>
                        <tr>
                            <th>ชื่อ-นามสกุล</th>
                            <th>Username</th>
                            <th class="text-center">สถานะ</th>
                            <th class="text-center" style="width: 25%;">จัดการ</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($result && $result->num_rows > 0): ?>
                            <?php while($row = $result->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($row['full_name']); ?></td>
                                    <td><?php echo htmlspecialchars($row['username']); ?></td>
                                    <td class="text-center">
                                        <?php if ($row['status'] == 'Y'): ?>
                                            <span class="badge bg-success">ใช้งาน</span>
                                        <?php else: ?>
                                            <span class="badge bg-danger">ปิดใช้งาน</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center">
                                        <button class="btn btn-sm btn-warning edit-btn" 
                                                data-id="<?php echo $row['id']; ?>" 
                                                data-fullname="<?php echo htmlspecialchars($row['full_name']); ?>"
                                                data-username="<?php echo htmlspecialchars($row['username']); ?>"
                                                data-status="<?php echo $row['status']; ?>">
                                            แก้ไข
                                        </button>
                                        <button class="btn btn-sm btn-danger delete-btn" 
                                                data-id="<?php echo $row['id']; ?>" 
                                                data-name="<?php echo htmlspecialchars(addslashes($row['full_name'])); ?>">
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
    $('#adminsTable').DataTable({
        language: { url: 'https://cdn.datatables.net/plug-ins/2.0.8/i18n/th.json' }
    });

    const form = document.getElementById('adminForm');
    const formTitle = document.getElementById('form-title');
    const formAction = document.getElementById('form-action');
    const adminId = document.getElementById('admin-id');
    const fullNameInput = document.getElementById('full-name');
    const usernameInput = document.getElementById('username');
    const passwordInput = document.getElementById('password');
    const passwordHint = document.getElementById('password-hint');
    const submitButton = document.getElementById('submit-button');
    const cancelButton = document.getElementById('cancel-button');

    function resetForm() {
        formTitle.textContent = 'เพิ่ม/แก้ไข เจ้าหน้าที่ซ่อม';
        formAction.value = 'add';
        adminId.value = '';
        form.reset();
        passwordInput.required = true;
        passwordHint.classList.add('d-none');
        document.getElementById('status_y').checked = true;
        submitButton.innerHTML = 'เพิ่มข้อมูล';
        submitButton.classList.remove('btn-warning');
        submitButton.classList.add('btn-primary');
        cancelButton.classList.add('d-none');
    }

    $('#adminsTable tbody').on('click', '.edit-btn', function() {
        const button = $(this);
        formTitle.textContent = 'แก้ไขเจ้าหน้าที่ซ่อม';
        formAction.value = 'update';
        adminId.value = button.data('id');
        fullNameInput.value = button.data('fullname');
        usernameInput.value = button.data('username');
        passwordInput.value = '';
        passwordInput.required = false;
        passwordHint.classList.remove('d-none');
        const status = button.data('status');
        if (status === 'Y') {
            document.getElementById('status_y').checked = true;
        } else {
            document.getElementById('status_n').checked = true;
        }
        submitButton.innerHTML = 'บันทึกการแก้ไข';
        submitButton.classList.remove('btn-primary');
        submitButton.classList.add('btn-warning');
        cancelButton.classList.remove('d-none');
        fullNameInput.focus();
    });

    $('#adminsTable tbody').on('click', '.delete-btn', function() {
        const button = $(this);
        deleteAdmin(button.data('id'), button.data('name'));
    });

    cancelButton.addEventListener('click', function() {
        resetForm();
    });
});

function deleteAdmin(id, name) {
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
            form.action = '../api/master_data/admin_actions.php';
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
    });
}
</script>
