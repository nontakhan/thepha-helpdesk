<?php
require_once 'partials/header.php';
require_once '../db_connect.php';

// ดึงข้อมูลสถานที่ทั้งหมดจากฐานข้อมูล
$sql = "SELECT id, location_name, department_type FROM locations ORDER BY location_name ASC";
$result = $conn->query($sql);
?>

<div class="row">
    <!-- ส่วนฟอร์มสำหรับเพิ่ม/แก้ไข -->
    <div class="col-lg-4 mb-4">
        <div class="card">
            <div class="card-header"><h5 class="card-title mb-0" id="form-title">เพิ่ม/แก้ไข สถานที่</h5></div>
            <div class="card-body">
                <form id="locationForm" method="POST" action="../api/master_data/location_actions.php">
                    <input type="hidden" name="action" id="form-action" value="add">
                    <input type="hidden" name="id" id="location-id" value="">
                    <div class="mb-3">
                        <label for="location-name" class="form-label">ชื่อสถานที่</label>
                        <input type="text" class="form-control" id="location-name" name="location_name" required placeholder="เช่น ตึกผู้ป่วยใน ชั้น 1">
                    </div>
                    <div class="mb-3">
                        <label for="department-type" class="form-label">ประเภทหน่วยงาน (SLA)</label>
                        <select class="form-select" id="department-type" name="department_type" required>
                            <option value="หน่วยงานสนับสนุน">หน่วยงานสนับสนุน (SLA 30 นาที)</option>
                            <option value="หน่วยงานให้บริการ">หน่วยงานให้บริการ (SLA 15 นาที)</option>
                        </select>
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
            <div class="card-header"><h5 class="card-title mb-0">รายการสถานที่ทั้งหมด</h5></div>
            <div class="card-body">
                <table id="locationsTable" class="table table-striped table-bordered" style="width:100%">
                    <thead>
                        <tr>
                            <th>ชื่อสถานที่</th>
                            <th>ประเภทหน่วยงาน (SLA)</th>
                            <th class="text-center" style="width: 25%;">จัดการ</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($result && $result->num_rows > 0): ?>
                            <?php while($row = $result->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($row['location_name']); ?></td>
                                    <td>
                                        <?php if ($row['department_type'] == 'หน่วยงานให้บริการ'): ?>
                                            <span class="badge bg-danger">ให้บริการ (15 นาที)</span>
                                        <?php else: ?>
                                            <span class="badge bg-primary">สนับสนุน (30 นาที)</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center">
                                        <button class="btn btn-sm btn-warning edit-btn" 
                                                data-id="<?php echo $row['id']; ?>" 
                                                data-name="<?php echo htmlspecialchars($row['location_name']); ?>"
                                                data-type="<?php echo htmlspecialchars($row['department_type']); ?>">
                                            แก้ไข
                                        </button>
                                        <button class="btn btn-sm btn-danger delete-btn" 
                                                data-id="<?php echo $row['id']; ?>"
                                                data-name="<?php echo htmlspecialchars(addslashes($row['location_name'])); ?>">
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

<!-- สคริปต์สำหรับหน้านี้โดยเฉพาะ -->
<script>
$(document).ready(function() {
    // 1. เริ่มต้นการทำงานของ DataTables
    var table = $('#locationsTable').DataTable({
        language: { url: 'https://cdn.datatables.net/plug-ins/2.0.8/i18n/th.json' }
    });

    const form = document.getElementById('locationForm');
    const formTitle = document.getElementById('form-title');
    const formAction = document.getElementById('form-action');
    const locationId = document.getElementById('location-id');
    const locationNameInput = document.getElementById('location-name');
    const departmentTypeSelect = document.getElementById('department-type');
    const submitButton = document.getElementById('submit-button');
    const cancelButton = document.getElementById('cancel-button');

    function resetForm() {
        formTitle.textContent = 'เพิ่ม/แก้ไข สถานที่';
        formAction.value = 'add';
        locationId.value = '';
        form.reset();
        submitButton.innerHTML = 'เพิ่มข้อมูล';
        submitButton.classList.remove('btn-warning');
        submitButton.classList.add('btn-primary');
        cancelButton.classList.add('d-none');
    }

    // 2. ใช้ event delegation สำหรับจัดการปุ่มในตาราง
    $('#locationsTable tbody').on('click', '.edit-btn', function() {
        const button = $(this);
        const id = button.data('id');
        const name = button.data('name');
        const type = button.data('type');

        formTitle.textContent = 'แก้ไขสถานที่';
        formAction.value = 'update';
        locationId.value = id;
        locationNameInput.value = name;
        departmentTypeSelect.value = type;
        submitButton.innerHTML = 'บันทึกการแก้ไข';
        submitButton.classList.remove('btn-primary');
        submitButton.classList.add('btn-warning');
        cancelButton.classList.remove('d-none');
        locationNameInput.focus();
    });
    
    $('#locationsTable tbody').on('click', '.delete-btn', function() {
        const button = $(this);
        const id = button.data('id');
        const name = button.data('name');
        deleteLocation(id, name);
    });

    cancelButton.addEventListener('click', function() {
        resetForm();
    });
});

function deleteLocation(id, name) {
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
            form.action = '../api/master_data/location_actions.php';
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
