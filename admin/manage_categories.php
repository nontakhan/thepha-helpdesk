<?php
require_once 'partials/header.php';
require_once '../db_connect.php';

// ดึงข้อมูลประเภททั้งหมดจากฐานข้อมูล
$sql = "SELECT id, category_name, is_sla FROM categories ORDER BY category_name ASC";
$result = $conn->query($sql);
?>

<div class="row">
    <!-- ส่วนฟอร์มสำหรับเพิ่ม/แก้ไข -->
    <div class="col-lg-4 mb-4">
        <div class="card">
            <div class="card-header"><h5 class="card-title mb-0" id="form-title">เพิ่ม/แก้ไข ประเภทงานซ่อม</h5></div>
            <div class="card-body">
                <form id="categoryForm" method="POST" action="../api/master_data/category_actions.php">
                    <input type="hidden" name="action" id="form-action" value="add">
                    <input type="hidden" name="id" id="category-id" value="">
                    
                    <div class="mb-3">
                        <label for="category-name" class="form-label">ชื่อประเภท</label>
                        <input type="text" class="form-control" id="category-name" name="category_name" required placeholder="เช่น Hardware, Software">
                    </div>

                    <!-- ===== ส่วนที่เพิ่มเข้ามา: Checkbox SLA ===== -->
                    <div class="mb-3 form-check">
                        <input type="checkbox" class="form-check-input" id="is-sla" name="is_sla" value="1" checked>
                        <label class="form-check-label" for="is-sla">นำไปคำนวณ SLA (15/30 นาที)</label>
                        <div class="form-text">หากไม่ติ๊ก ระบบจะไม่นำงานประเภทนี้ไปประเมินว่า ผ่าน/ไม่ผ่าน</div>
                    </div>
                    <!-- ======================================= -->

                    <button type="submit" class="btn btn-primary w-100" id="submit-button">เพิ่มข้อมูล</button>
                    <button type="button" class="btn btn-secondary w-100 mt-2 d-none" id="cancel-button">ยกเลิกการแก้ไข</button>
                </form>
            </div>
        </div>
    </div>

    <!-- ส่วนตารางแสดงข้อมูล -->
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header"><h5 class="card-title mb-0">ประเภทงานซ่อมทั้งหมด</h5></div>
            <div class="card-body">
                <table id="categoriesTable" class="table table-striped table-bordered" style="width:100%">
                    <thead>
                        <tr>
                            <th>ชื่อประเภท</th>
                            <th class="text-center">สถานะ SLA</th>
                            <th class="text-center" style="width: 25%;">จัดการ</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($result && $result->num_rows > 0): ?>
                            <?php while($row = $result->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($row['category_name']); ?></td>
                                    <td class="text-center">
                                        <?php if ($row['is_sla'] == 1): ?>
                                            <span class="badge bg-success">คำนวณ SLA</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary">ไม่คำนวณ</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center">
                                        <button class="btn btn-sm btn-warning edit-btn" 
                                                data-id="<?php echo $row['id']; ?>" 
                                                data-name="<?php echo htmlspecialchars($row['category_name']); ?>"
                                                data-sla="<?php echo $row['is_sla']; ?>">
                                            แก้ไข
                                        </button>
                                        <button class="btn btn-sm btn-danger delete-btn" 
                                                data-id="<?php echo $row['id']; ?>" 
                                                data-name="<?php echo htmlspecialchars(addslashes($row['category_name'])); ?>">
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
    $('#categoriesTable').DataTable({
        language: { url: 'https://cdn.datatables.net/plug-ins/2.0.8/i18n/th.json' }
    });

    const form = document.getElementById('categoryForm');
    const formTitle = document.getElementById('form-title');
    const formAction = document.getElementById('form-action');
    const categoryId = document.getElementById('category-id');
    const categoryNameInput = document.getElementById('category-name');
    const isSlaCheckbox = document.getElementById('is-sla');
    const submitButton = document.getElementById('submit-button');
    const cancelButton = document.getElementById('cancel-button');

    function resetForm() {
        formTitle.textContent = 'เพิ่ม/แก้ไข ประเภทงานซ่อม';
        formAction.value = 'add';
        categoryId.value = '';
        form.reset();
        isSlaCheckbox.checked = true; // Default checked
        submitButton.innerHTML = 'เพิ่มข้อมูล';
        submitButton.classList.remove('btn-warning');
        submitButton.classList.add('btn-primary');
        cancelButton.classList.add('d-none');
    }

    // Event Delegation
    $('#categoriesTable tbody').on('click', '.edit-btn', function() {
        const button = $(this);
        const id = button.data('id');
        const name = button.data('name');
        const sla = button.data('sla');
        
        formTitle.textContent = 'แก้ไขประเภทงานซ่อม';
        formAction.value = 'update';
        categoryId.value = id;
        categoryNameInput.value = name;
        isSlaCheckbox.checked = (sla == 1); // Set checkbox status

        submitButton.innerHTML = 'บันทึกการแก้ไข';
        submitButton.classList.remove('btn-primary');
        submitButton.classList.add('btn-warning');
        cancelButton.classList.remove('d-none');
        categoryNameInput.focus();
    });

    $('#categoriesTable tbody').on('click', '.delete-btn', function() {
        const button = $(this);
        deleteCategory(button.data('id'), button.data('name'));
    });

    cancelButton.addEventListener('click', function() {
        resetForm();
    });
});

function deleteCategory(id, name) {
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
            form.action = '../api/master_data/category_actions.php';
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