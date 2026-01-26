<?php
require_once 'partials/header.php';
require_once '../db_connect.php';

// ดึงข้อมูลสำหรับ Dropdowns
$admins = $conn->query("SELECT id, full_name FROM admins ORDER BY full_name ASC");
$activity_types = $conn->query("SELECT id, type_name, color FROM activity_types ORDER BY type_name ASC");

$current_admin_id = isset($_SESSION['admin_id']) ? (int) $_SESSION['admin_id'] : 0;
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
                    <?php while ($admin = $admins->fetch_assoc()): ?>
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
// เตรียมข้อมูลประเภทกิจกรรมสำหรับ JavaScript
$activity_types_arr = [];
mysqli_data_seek($activity_types, 0);
while ($at = $activity_types->fetch_assoc()) {
    $activity_types_arr[] = $at;
}
$conn->close();
require_once 'partials/footer.php';
?>

<!-- FullCalendar JS -->
<script src='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.14/index.global.min.js'></script>
<!-- Axios for AJAX requests -->
<script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>

<script>
    $(document).ready(function () {
        var calendarEl = document.getElementById('calendar');
        var adminFilter = $('#admin_filter');
        var eventModal = new bootstrap.Modal(document.getElementById('eventModal'));
        var currentEventId = null;

        // รายการประเภทกิจกรรมสำหรับ dropdown
        var activityTypes = <?php echo json_encode($activity_types_arr); ?>;

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

            eventClick: function (info) {
                info.jsEvent.preventDefault();
                var event = info.event;
                var props = event.extendedProps;

                $('#eventModalLabel').text("รายละเอียด: " + event.title);
                currentEventId = event.id.replace('act_', '');

                if (props.type !== 'งานซ่อม') {
                    // ===== สร้างปุ่มสีสำหรับประเภทกิจกรรม =====
                    var currentTypeId = props.type_id || 0;
                    var activityTypeButtons = activityTypes.map(function (t) {
                        var activeClass = (t.id == currentTypeId) ? 'active' : '';
                        return `<button type="button" class="btn ${activeClass}" style="background-color: ${t.color}; color: white; text-shadow: 1px 1px 2px rgba(0,0,0,0.4);" data-type-id="${t.id}">${t.type_name}</button>`;
                    }).join('');

                    // ===== ฟอร์มแก้ไขกิจกรรม =====
                    var formHtml = `
                    <style>
                        .activity-type-buttons .btn {
                            border: 3px solid transparent;
                            transition: all 0.2s ease-in-out;
                        }
                        .activity-type-buttons .btn.active {
                            border-color: #000;
                            transform: scale(1.05);
                            box-shadow: 0 4px 15px rgba(0,0,0,0.2);
                        }
                        .activity-type-buttons .btn:hover {
                            transform: scale(1.03);
                        }
                    </style>
                    <form id="editActivityForm">
                        <input type="hidden" name="action" value="update">
                        <input type="hidden" name="activity_id" value="${currentEventId}">
                        <input type="hidden" name="activity_type_id" id="edit_activity_type_id" value="${currentTypeId}">
                        
                        <div class="mb-3">
                            <label class="form-label">ประเภทกิจกรรม</label>
                            <div id="edit-activity-type-selector" class="d-flex flex-wrap gap-2 activity-type-buttons">
                                ${activityTypeButtons}
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

                    // นำข้อมูลไปใส่ในฟอร์ม
                    const formatDateTimeLocal = (date) => date ? new Date(date.getTime() - (date.getTimezoneOffset() * 60000)).toISOString().slice(0, 16) : '';
                    $('#edit_start_time').val(formatDateTimeLocal(event.start));
                    $('#edit_end_time').val(formatDateTimeLocal(event.end));

                    // Handler สำหรับปุ่มเลือกประเภท
                    $('#edit-activity-type-selector').on('click', '.btn', function () {
                        $('#edit-activity-type-selector .btn').removeClass('active');
                        $(this).addClass('active');
                        $('#edit_activity_type_id').val($(this).data('type-id'));
                    });

                } else {
                    // ===== งานซ่อม: แสดงข้อมูลพร้อมเวลาแจ้ง/เวลาซ่อม และปุ่มแก้ไข =====
                    var requestId = event.id.replace('req_', '');

                    // Format dates for display
                    var formatThaiDate = function (dateStr) {
                        if (!dateStr) return '-';
                        var d = new Date(dateStr);
                        return d.toLocaleDateString('th-TH', {
                            year: 'numeric', month: 'short', day: 'numeric',
                            hour: '2-digit', minute: '2-digit'
                        });
                    };

                    var infoHtml = `
                    <div class="row g-3">
                        <!-- เวลา -->
                        <div class="col-md-6">
                            <div class="card bg-light border-0 h-100">
                                <div class="card-body">
                                    <h6 class="text-primary mb-3"><i class="bi bi-clock-history me-2"></i>เวลา</h6>
                                    <table class="table table-sm table-borderless mb-0">
                                        <tr>
                                            <td class="text-muted" style="width: 90px;">เวลาแจ้ง</td>
                                            <td class="fw-medium">${formatThaiDate(props.request_date)}</td>
                                        </tr>
                                        <tr>
                                            <td class="text-muted">เวลาเสร็จ</td>
                                            <td class="fw-medium">${formatThaiDate(props.repair_date)}</td>
                                        </tr>
                                        <tr>
                                            <td class="text-muted">ใช้เวลา</td>
                                            <td class="fw-medium">${props.resolution_time ? props.resolution_time + ' นาที' : '-'}</td>
                                        </tr>
                                    </table>
                                </div>
                            </div>
                        </div>
                        <!-- ข้อมูลทั่วไป -->
                        <div class="col-md-6">
                            <div class="card bg-light border-0 h-100">
                                <div class="card-body">
                                    <h6 class="text-success mb-3"><i class="bi bi-info-circle me-2"></i>ข้อมูลทั่วไป</h6>
                                    <table class="table table-sm table-borderless mb-0">
                                        <tr>
                                            <td class="text-muted" style="width: 90px;">ผู้รับซ่อม</td>
                                            <td class="fw-medium">${props.admin_name || '-'}</td>
                                        </tr>
                                        <tr>
                                            <td class="text-muted">ประเภท</td>
                                            <td class="fw-medium">${props.category || '-'}</td>
                                        </tr>
                                        <tr>
                                            <td class="text-muted">ผล SLA</td>
                                            <td><span class="badge bg-${props.sla_class}">${props.sla_status}</span></td>
                                        </tr>
                                    </table>
                                </div>
                            </div>
                        </div>
                        <!-- ผู้แจ้งและสถานที่ -->
                        <div class="col-12">
                            <div class="card bg-light border-0">
                                <div class="card-body py-2">
                                    <div class="row">
                                        <div class="col-md-6"><small class="text-muted">สถานที่:</small> <span class="fw-medium">${props.location || '-'}</span></div>
                                        <div class="col-md-6"><small class="text-muted">ผู้แจ้ง:</small> <span class="fw-medium">${props.reporter || '-'}</span></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <!-- ปัญหา -->
                        <div class="col-12">
                            <div class="card border">
                                <div class="card-body">
                                    <h6 class="text-danger"><i class="bi bi-exclamation-triangle me-2"></i>ปัญหาที่แจ้ง</h6>
                                    <p class="mb-0 bg-light p-2 rounded">${props.problem || '-'}</p>
                                </div>
                            </div>
                        </div>
                        <!-- สาเหตุและวิธีแก้ไข -->
                        <div class="col-md-6">
                            <div class="card border h-100">
                                <div class="card-body">
                                    <h6 class="text-warning"><i class="bi bi-question-circle me-2"></i>สาเหตุ</h6>
                                    <p class="mb-0 bg-light p-2 rounded small">${props.cause || '-'}</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card border h-100">
                                <div class="card-body">
                                    <h6 class="text-info"><i class="bi bi-check-circle me-2"></i>วิธีแก้ไข</h6>
                                    <p class="mb-0 bg-light p-2 rounded small">${props.solution || '-'}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                `;
                    $('#eventModalBody').html(infoHtml);
                    $('#eventModalFooter').html(`
                    <a href="request_view.php?id=${requestId}" class="btn btn-primary"><i class="bi bi-pencil me-1"></i>แก้ไขใบงาน</a>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ปิด</button>
                `);
                }
                eventModal.show();
            },

            events: {
                url: '../api/get_calendar_events.php',
                extraParams: function () { return { admin_id: adminFilter.val() }; }
            }
        });

        calendar.render();
        adminFilter.on('change', () => calendar.refetchEvents());

        // ใช้ jQuery Event Delegation สำหรับจัดการปุ่มใน Modal
        $('#eventModalFooter').on('click', '#saveActivityChangesBtn', function () {
            var formData = new FormData(document.getElementById('editActivityForm'));
            axios.post('../api/save_activity.php', formData).then(handleResponse).catch(handleError);
        });

        $('#eventModalFooter').on('click', '#deleteActivityBtn', function () {
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