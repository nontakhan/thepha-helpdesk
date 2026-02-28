<?php
require_once 'partials/header.php';
require_once '../db_connect.php';

// ดึงข้อมูลวันหยุดทั้งหมดจากฐานข้อมูล
$sql = "SELECT id, holiday_date, holiday_name FROM holidays ORDER BY holiday_date DESC";
$result = $conn->query($sql);
?>

<div class="row">
    <!-- ส่วนฟอร์มสำหรับเพิ่ม/แก้ไข -->
    <div class="col-lg-4 mb-4">
        <div class="card">
            <div class="card-header"><h5 class="card-title mb-0" id="form-title">เพิ่ม/แก้ไข วันหยุด</h5></div>
            <div class="card-body">
                <form id="holidayForm" method="POST" action="../api/master_data/holiday_actions.php">
                    <input type="hidden" name="action" id="form-action" value="add">
                    <input type="hidden" name="id" id="holiday-id" value="">
                    <div class="mb-3">
                        <label for="holiday-date" class="form-label">วันที่หยุด</label>
                        <input type="date" class="form-control" id="holiday-date" name="holiday_date" required>
                    </div>
                    <div class="mb-3">
                        <label for="holiday-name" class="form-label">ชื่อวันหยุด</label>
                        <input type="text" class="form-control" id="holiday-name" name="holiday_name" required placeholder="เช่น วันสงกรานต์, วันขึ้นปีใหม่">
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
            <div class="card-header"><h5 class="card-title mb-0">วันหยุดทั้งหมด</h5></div>
            <div class="card-body">
                <table id="holidaysTable" class="table table-striped table-bordered" style="width:100%">
                    <thead>
                        <tr>
                            <th>วันที่</th>
                            <th>ชื่อวันหยุด</th>
                            <th class="text-center" style="width: 25%;">จัดการ</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($result && $result->num_rows > 0): ?>
                            <?php while($row = $result->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo date('d/m/Y', strtotime($row['holiday_date'])); ?></td>
                                    <td><?php echo htmlspecialchars($row['holiday_name']); ?></td>
                                    <td class="text-center">
                                        <button class="btn btn-sm btn-warning edit-btn" 
                                                data-id="<?php echo $row['id']; ?>" 
                                                data-date="<?php echo $row['holiday_date']; ?>"
                                                data-name="<?php echo htmlspecialchars($row['holiday_name']); ?>">
                                            แก้ไข
                                        </button>
                                        <button class="btn btn-sm btn-danger delete-btn" 
                                                data-id="<?php echo $row['id']; ?>" 
                                                data-name="<?php echo htmlspecialchars(addslashes($row['holiday_name'])); ?>">
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
    $('#holidaysTable').DataTable({
        language: { url: 'https://cdn.datatables.net/plug-ins/2.0.8/i18n/th.json' },
        order: [[0, 'desc']]
    });

    const form = document.getElementById('holidayForm');
    const formTitle = document.getElementById('form-title');
    const formAction = document.getElementById('form-action');
    const holidayId = document.getElementById('holiday-id');
    const holidayDateInput = document.getElementById('holiday-date');
    const holidayNameInput = document.getElementById('holiday-name');
    const submitButton = document.getElementById('submit-button');
    const cancelButton = document.getElementById('cancel-button');

    function resetForm() {
        formTitle.textContent = 'เพิ่ม/แก้ไข วันหยุด';
        formAction.value = 'add';
        holidayId.value = '';
        form.reset();
        submitButton.innerHTML = 'เพิ่มข้อมูล';
        submitButton.classList.remove('btn-warning');
        submitButton.classList.add('btn-primary');
        cancelButton.classList.add('d-none');
    }

    $('#holidaysTable tbody').on('click', '.edit-btn', function() {
        const button = $(this);
        formTitle.textContent = 'แก้ไขวันหยุด';
        formAction.value = 'update';
        holidayId.value = button.data('id');
        holidayDateInput.value = button.data('date');
        holidayNameInput.value = button.data('name');
        submitButton.innerHTML = 'บันทึกการแก้ไข';
        submitButton.classList.remove('btn-primary');
        submitButton.classList.add('btn-warning');
        cancelButton.classList.remove('d-none');
        holidayDateInput.focus();
    });

    $('#holidaysTable tbody').on('click', '.delete-btn', function() {
        const button = $(this);
        deleteHoliday(button.data('id'), button.data('name'));
    });

    cancelButton.addEventListener('click', function() {
        resetForm();
    });
});

function deleteHoliday(id, name) {
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
            form.action = '../api/master_data/holiday_actions.php';
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
