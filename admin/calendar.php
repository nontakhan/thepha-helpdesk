<?php
require_once 'partials/header.php';
require_once '../db_connect.php';

// ดึงข้อมูลสำหรับ Dropdowns
$admins = $conn->query("SELECT id, full_name FROM admins ORDER BY full_name ASC");
$activity_types = $conn->query("SELECT id, type_name FROM activity_types ORDER BY type_name ASC");

$current_admin_id = isset($_SESSION['admin_id']) ? (int)$_SESSION['admin_id'] : 0;
?>

<div class="card">
    <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
        <h5 class="card-title mb-0">ปฏิทินการทำงาน</h5>
        <!-- ===== ส่วนที่แก้ไข: เพิ่มปุ่มและจัดกลุ่ม ===== -->
        <div class="d-flex align-items-center gap-2">
            <a href="log_activity.php" class="btn btn-primary btn-sm">
                <i class="bi bi-plus-circle-fill"></i> เพิ่มกิจกรรม
            </a>
            <div class="d-flex align-items-center">
                <label for="admin_filter" class="form-label me-2 mb-0 d-none d-sm-inline">ดูของ:</label>
                <select id="admin_filter" class="form-select form-select-sm" style="width: 200px;">
                    <?php while($admin = $admins->fetch_assoc()): ?>
                        <option value="<?php echo $admin['id']; ?>" <?php echo ($admin['id'] == $current_admin_id) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($admin['full_name']); ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>
        </div>
        <!-- ===== สิ้นสุดส่วนที่แก้ไข ===== -->
    </div>
    <div class="card-body">
        <div id="calendar"></div>
    </div>
</div>

<!-- Modal for Editing/Viewing Activity -->
<div class="modal fade" id="eventModal" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="eventModalLabel">รายละเอียดกิจกรรม</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body" id="eventModalBody">
        <!-- Content will be injected by JavaScript -->
      </div>
      <div class="modal-footer" id="eventModalFooter">
        <!-- Buttons will be injected by JavaScript -->
      </div>
    </div>
  </div>
</div>

<?php
$conn->close();
require_once 'partials/footer.php';
?>

<!-- FullCalendar JS -->
<script src='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.14/index.global.min.js'></script>
<!-- Axios for AJAX requests -->
<script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>

<script>
$(document).ready(function() {
    var calendarEl = document.getElementById('calendar');
    var adminFilter = $('#admin_filter');
    var eventModal = new bootstrap.Modal(document.getElementById('eventModal'));
    var currentEventId = null;

    var calendar = new FullCalendar.Calendar(calendarEl, {
        initialView: 'timeGridWeek',
        headerToolbar: {
            left: 'prev,next today',
            center: 'title',
            right: 'dayGridMonth,timeGridWeek,timeGridDay'
        },
        locale: 'th',
        buttonText: { today: 'วันนี้', month: 'เดือน', week: 'สัปดาห์', day: 'วัน' },
        allDaySlot: false,
        nowIndicator: true,
        slotMinTime: '06:00:00',
        slotMaxTime: '20:00:00',
        
        eventClick: function(info) {
            info.jsEvent.preventDefault();
            var event = info.event;
            var props = event.extendedProps;
            
            $('#eventModalLabel').text("รายละเอียด: " + event.title);
            currentEventId = event.id.replace('act_', '');

            if (props.type !== 'งานซ่อม') {
                // ===== ส่วนที่แก้ไข: ปรับปรุงฟอร์มแก้ไข =====
                var formHtml = `
                    <form id="editActivityForm">
                        <input type="hidden" name="action" value="update">
                        <input type="hidden" name="activity_id" value="${currentEventId}">
                        
                        <div class="mb-3">
                            <label class="form-label">ประเภทกิจกรรม (ไม่สามารถแก้ไขได้)</label>
                            <div>
                                <span class="btn" style="background-color: ${event.backgroundColor}; color: ${event.textColor}; cursor: default;">
                                    ${props.type}
                                </span>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="edit_title" class="form-label">ชื่องาน / รายละเอียด</label>
                            <textarea class="form-control" id="edit_title" name="title" rows="3" required>${event.title}</textarea>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="edit_start_time" class="form-label">เวลาเริ่มต้น</label>
                                <input type="datetime-local" class="form-control" id="edit_start_time" name="start_time" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="edit_end_time" class="form-label">เวลาสิ้นสุด</label>
                                <input type="datetime-local" class="form-control" id="edit_end_time" name="end_time" required>
                            </div>
                        </div>
                    </form>
                `;
                $('#eventModalBody').html(formHtml);
                $('#eventModalFooter').html(`
                    <button type="button" class="btn btn-danger me-auto" id="deleteActivityBtn">ลบกิจกรรมนี้</button>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ยกเลิก</button>
                    <button type="button" class="btn btn-primary" id="saveActivityChangesBtn">บันทึกการเปลี่ยนแปลง</button>
                `);
                
                // นำข้อมูลไปใส่ในฟอร์ม (ยกเว้นประเภทที่แสดงผลอย่างเดียว)
                const formatDateTimeLocal = (date) => date ? new Date(date.getTime() - (date.getTimezoneOffset() * 60000)).toISOString().slice(0,16) : '';
                $('#edit_start_time').val(formatDateTimeLocal(event.start));
                $('#edit_end_time').val(formatDateTimeLocal(event.end));

            } else {
                var infoHtml = `
                    <ul class="list-group list-group-flush">
                        <li class="list-group-item"><strong>ประเภท:</strong> ${props.type}</li>
                        <li class="list-group-item"><strong>ปัญหา:</strong> ${props.problem}</li>
                        <li class="list-group-item"><strong>สถานที่:</strong> ${props.location}</li>
                        <li class="list-group-item"><strong>ผู้แจ้ง:</strong> ${props.reporter}</li>
                    </ul>`;
                $('#eventModalBody').html(infoHtml);
                $('#eventModalFooter').html(`<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ปิด</button>`);
            }
            eventModal.show();
        },
        
        events: {
            url: '../api/get_calendar_events.php',
            extraParams: function() { return { admin_id: adminFilter.val() }; }
        }
    });

    calendar.render();
    adminFilter.on('change', () => calendar.refetchEvents());

    // ใช้ jQuery Event Delegation สำหรับจัดการปุ่มใน Modal
    $('#eventModalFooter').on('click', '#saveActivityChangesBtn', function() {
        var formData = new FormData(document.getElementById('editActivityForm'));
        axios.post('../api/save_activity.php', formData).then(handleResponse).catch(handleError);
    });

    $('#eventModalFooter').on('click', '#deleteActivityBtn', function() {
        Swal.fire({
            title: 'ยืนยันการลบ?', text: "คุณต้องการลบกิจกรรมนี้ใช่หรือไม่?",
            icon: 'warning', showCancelButton: true, confirmButtonColor: '#d33',
            cancelButtonColor: '#6c757d', confirmButtonText: 'ใช่, ลบเลย!', cancelButtonText: 'ยกเลิก'
        }).then((result) => {
            if (result.isConfirmed) {
                var formData = new FormData();
                formData.append('action', 'delete');
                formData.append('activity_id', currentEventId);
                axios.post('../api/save_activity.php', formData).then(handleResponse).catch(handleError);
            }
        });
    });

    function handleResponse(response) {
        if (response.data.success) {
            eventModal.hide();
            Swal.fire('สำเร็จ!', response.data.message, 'success');
            calendar.refetchEvents();
        } else {
            Swal.fire('ผิดพลาด!', response.data.message, 'error');
        }
    }

    function handleError() {
        Swal.fire('ผิดพลาด!', 'ไม่สามารถเชื่อมต่อเซิร์ฟเวอร์ได้', 'error');
    }
});
</script>
