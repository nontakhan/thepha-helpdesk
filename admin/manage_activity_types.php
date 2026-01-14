<?php
require_once 'partials/header.php';
require_once '../db_connect.php';

$sql = "SELECT id, type_name, color FROM activity_types ORDER BY type_name ASC";
$result = $conn->query($sql);
?>

<div class="row">
    <!-- Form for adding/editing -->
    <div class="col-lg-4 mb-4">
        <div class="card">
            <div class="card-header"><h5 class="card-title mb-0" id="form-title">เพิ่ม/แก้ไข ประเภทกิจกรรม</h5></div>
            <div class="card-body">
                <form id="activityTypeForm" method="POST" action="../api/master_data/activity_type_actions.php">
                    <input type="hidden" name="action" id="form-action" value="add">
                    <input type="hidden" name="id" id="type-id" value="">
                    <div class="mb-3">
                        <label for="type-name" class="form-label">ชื่อประเภทกิจกรรม</label>
                        <input type="text" class="form-control" id="type-name" name="type_name" required placeholder="เช่น ประชุม, งานพัฒนา">
                    </div>
                    <div class="mb-3">
                        <label for="type-color" class="form-label">สีสำหรับปฏิทิน</label>
                        <input type="color" class="form-control form-control-color" id="type-color" name="color" value="#3788d8" title="เลือกสี">
                    </div>
                    <button type="submit" class="btn btn-primary w-100" id="submit-button">เพิ่มข้อมูล</button>
                    <button type="button" class="btn btn-secondary w-100 mt-2 d-none" id="cancel-button">ยกเลิก</button>
                </form>
            </div>
        </div>
    </div>

    <!-- Table for displaying data -->
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header"><h5 class="card-title mb-0">ประเภทกิจกรรมทั้งหมด</h5></div>
            <div class="card-body">
                <table id="activityTypesTable" class="table table-striped table-bordered" style="width:100%">
                    <thead>
                        <tr>
                            <th>ชื่อประเภท</th>
                            <th class="text-center">สีตัวอย่าง</th>
                            <th class="text-center" style="width: 25%;">จัดการ</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($result && $result->num_rows > 0): ?>
                            <?php while($row = $result->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($row['type_name']); ?></td>
                                    <td class="text-center">
                                        <span class="badge" style="background-color: <?php echo $row['color']; ?>; color: white; text-shadow: 1px 1px 2px black;"><?php echo $row['color']; ?></span>
                                    </td>
                                    <td class="text-center">
                                        <button class="btn btn-sm btn-warning edit-btn" 
                                                data-id="<?php echo $row['id']; ?>" 
                                                data-name="<?php echo htmlspecialchars($row['type_name']); ?>"
                                                data-color="<?php echo htmlspecialchars($row['color']); ?>">
                                            แก้ไข
                                        </button>
                                        <button class="btn btn-sm btn-danger delete-btn" 
                                                data-id="<?php echo $row['id']; ?>" 
                                                data-name="<?php echo htmlspecialchars(addslashes($row['type_name'])); ?>">
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
    $('#activityTypesTable').DataTable({
        language: { url: 'https://cdn.datatables.net/plug-ins/2.0.8/i18n/th.json' }
    });

    const form = $('#activityTypeForm');
    const formTitle = $('#form-title');
    const formAction = $('#form-action');
    const typeId = $('#type-id');
    const typeNameInput = $('#type-name');
    const typeColorInput = $('#type-color');
    const submitButton = $('#submit-button');
    const cancelButton = $('#cancel-button');

    function resetForm() {
        formTitle.text('เพิ่ม/แก้ไข ประเภทกิจกรรม');
        formAction.val('add');
        typeId.val('');
        form[0].reset();
        typeColorInput.val('#3788d8');
        submitButton.html('เพิ่มข้อมูล').removeClass('btn-warning').addClass('btn-primary');
        cancelButton.addClass('d-none');
    }

    $('#activityTypesTable tbody').on('click', '.edit-btn', function() {
        const button = $(this);
        formTitle.text('แก้ไขประเภทกิจกรรม');
        formAction.val('update');
        typeId.val(button.data('id'));
        typeNameInput.val(button.data('name'));
        typeColorInput.val(button.data('color'));
        submitButton.html('บันทึกการแก้ไข').removeClass('btn-primary').addClass('btn-warning');
        cancelButton.removeClass('d-none');
        typeNameInput.focus();
    });

    $('#activityTypesTable tbody').on('click', '.delete-btn', function() {
        const button = $(this);
        deleteActivityType(button.data('id'), button.data('name'));
    });

    cancelButton.on('click', resetForm);
});

function deleteActivityType(id, name) {
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
            $('<form>', {
                "method": "POST",
                "action": "../api/master_data/activity_type_actions.php"
            }).append(
                $('<input>', {"name": "action", "value": "delete", "type": "hidden"}),
                $('<input>', {"name": "id", "value": id, "type": "hidden"})
            ).appendTo('body').submit();
        }
    });
}
</script>
