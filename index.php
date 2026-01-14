<?php
session_start();
require_once 'db_connect.php';

// ดึงข้อมูลสำหรับ Dropdowns (ใช้ทั้งในฟอร์มหลักและใน Modal)
$locations_sql = "SELECT id, location_name FROM locations ORDER BY location_name ASC";
$locations_result = $conn->query($locations_sql);
$reporters_sql = "SELECT id, reporter_name FROM reporters WHERE status = 'Y' ORDER BY reporter_name ASC";
$reporters_result = $conn->query($reporters_sql);
?>
<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Thepha Helpdesk - แจ้งซ่อม</title>

    <!-- Favicon and PWA settings -->
    <link rel="icon" href="assets/images/favicon.ico" type="image/x-icon">
    <link rel="apple-touch-icon" href="assets/images/logo192.png">
    <link rel="manifest" href="manifest.json">

    <!-- Google Fonts: Sarabun -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;500;700&display=swap" rel="stylesheet">

    <!-- Libraries CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link rel="stylesheet"
        href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" />
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet">

    <style>
        :root {
            --primary-color: #3498db;
            --secondary-color: #5dade2;
            --accent-color: #2980b9;
            --success-color: #48cae4;
            --warning-color: #74c0fc;
            --danger-color: #6c5ce7;
            --light-bg: #f8f9fc;
            --card-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            --gradient-bg: linear-gradient(135deg, #74b9ff 0%, #0984e3 50%, #00b4d8 100%);
        }

        body {
            font-family: 'Sarabun', sans-serif;
            background: var(--gradient-bg);
            min-height: 100vh;
            font-weight: 400;
        }

        .main-container {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            box-shadow: var(--card-shadow);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .header-section {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            border-radius: 20px 20px 0 0;
            padding: 2rem;
            text-align: center;
            position: relative;
            overflow: hidden;
        }

        .header-section::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(255, 255, 255, 0.1) 0%, transparent 70%);
            animation: float 6s ease-in-out infinite;
        }

        @keyframes float {

            0%,
            100% {
                transform: translateY(0px) rotate(0deg);
            }

            50% {
                transform: translateY(-20px) rotate(180deg);
            }
        }

        .logo-container {
            position: relative;
            z-index: 2;
        }

        .logo-img {
            width: 80px;
            height: 80px;
            object-fit: contain;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 50%;
            padding: 15px;
            margin-bottom: 1rem;
            transition: transform 0.3s ease;
        }

        .logo-img:hover {
            transform: scale(1.1) rotate(5deg);
        }

        .form-section {
            padding: 2rem;
        }

        .form-label {
            font-weight: 500;
            color: #495057;
            margin-bottom: 0.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .form-control,
        .form-select {
            border: 2px solid #e9ecef;
            border-radius: 12px;
            padding: 0.75rem 1rem;
            font-size: 1rem;
            transition: all 0.3s ease;
            background: #fff;
        }

        .form-control:focus,
        .form-select:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.2rem rgba(52, 152, 219, 0.25);
            transform: translateY(-2px);
        }

        .btn-submit {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            border: none;
            border-radius: 12px;
            padding: 1rem 2rem;
            font-size: 1.1rem;
            font-weight: 500;
            color: white;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .btn-submit::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
            transition: left 0.5s;
        }

        .btn-submit:hover::before {
            left: 100%;
        }

        .btn-submit:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 25px rgba(52, 152, 219, 0.4);
        }

        .admin-link {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            color: var(--accent-color);
            text-decoration: none;
            font-weight: 500;
            padding: 0.5rem 1rem;
            border-radius: 25px;
            background: rgba(52, 152, 219, 0.1);
            transition: all 0.3s ease;
        }

        .admin-link:hover {
            background: var(--accent-color);
            color: white;
            transform: translateY(-2px);
        }

        .select2-container--bootstrap-5 .select2-selection {
            min-height: calc(1.5em + 1.5rem + 4px);
            border: 2px solid #e9ecef;
            border-radius: 12px;
            font-family: 'Sarabun', sans-serif;
        }

        .select2-container--bootstrap-5.select2-container--focus .select2-selection {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.2rem rgba(52, 152, 219, 0.25);
        }

        .floating-elements {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            pointer-events: none;
            z-index: -1;
        }

        .floating-circle {
            position: absolute;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.1);
            animation: floatCircle 8s infinite ease-in-out;
        }

        .floating-circle:nth-child(1) {
            width: 100px;
            height: 100px;
            top: 20%;
            left: 10%;
            animation-delay: 0s;
        }

        .floating-circle:nth-child(2) {
            width: 150px;
            height: 150px;
            top: 60%;
            right: 10%;
            animation-delay: 2s;
        }

        .floating-circle:nth-child(3) {
            width: 80px;
            height: 80px;
            bottom: 20%;
            left: 20%;
            animation-delay: 4s;
        }

        @keyframes floatCircle {

            0%,
            100% {
                transform: translateY(0px) scale(1);
            }

            50% {
                transform: translateY(-30px) scale(1.1);
            }
        }

        @media (max-width: 576px) {
            .form-section {
                padding: 1.5rem 1rem;
            }

            .header-section {
                padding: 1.5rem 1rem;
            }
        }

        .required-star {
            color: var(--danger-color);
            font-weight: 600;
        }

        .card-title {
            font-weight: 600;
            font-size: 1.5rem;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .text-subtitle {
            opacity: 0.9;
            font-weight: 300;
        }

        .rating-stars {
            display: inline-flex;
            flex-direction: row-reverse;
            justify-content: center;
        }

        .rating-stars input {
            display: none;
        }

        .rating-stars label {
            font-size: 2.5rem;
            color: #ddd;
            cursor: pointer;
            transition: color 0.2s;
        }

        .rating-stars input:checked~label,
        .rating-stars label:hover,
        .rating-stars label:hover~label {
            color: #ffca08;
        }

        .list-group-item .btn {
            transition: all 0.2s ease-in-out;
        }

        .list-group-item .btn:hover {
            transform: scale(1.05);
        }
    </style>
</head>

<body>
    <!-- Floating Background Elements -->
    <div class="floating-elements">
        <div class="floating-circle"></div>
        <div class="floating-circle"></div>
        <div class="floating-circle"></div>
    </div>

    <div class="container py-4">
        <div class="row justify-content-center">
            <div class="col-12 col-md-10 col-lg-9 col-xl-8">
                <div class="main-container">
                    <!-- Header Section -->
                    <div class="header-section">
                        <div class="logo-container">
                            <img src="assets/images/it-helpdesk.png" alt="Logo" class="logo-img">
                            <h1 class="card-title mb-2">ระบบแจ้งซ่อม Thepha Helpdesk</h1>
                            <p class="text-subtitle mb-0">กรุณากรอกข้อมูลเพื่อแจ้งปัญหาที่พบ</p>
                            <p class="text-white-50 small mt-2 mb-0 fst-italic">** รับแจ้งเฉพาะปัญหาที่เกี่ยวข้องกับงาน
                                IT เท่านั้น **</p>
                        </div>
                    </div>

                    <!-- Form Section -->
                    <div class="form-section">
                        <form id="requestForm">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="request_date" class="form-label">
                                        <i class="bi bi-calendar-date"></i> วันที่แจ้ง <span
                                            class="required-star">*</span>
                                    </label>
                                    <input type="date" class="form-control" id="request_date" name="request_date"
                                        required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="request_time" class="form-label">
                                        <i class="bi bi-clock"></i> เวลาที่แจ้ง <span class="required-star">*</span>
                                    </label>
                                    <input type="time" class="form-control" id="request_time" name="request_time"
                                        required>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label for="problem_description" class="form-label">
                                    <i class="bi bi-exclamation-triangle"></i> ปัญหาที่พบ <span
                                        class="required-star">*</span>
                                </label>
                                <textarea class="form-control" id="problem_description" name="problem_description"
                                    rows="4" required placeholder="อธิบายปัญหาที่พบโดยละเอียด..."></textarea>
                            </div>
                            <div class="mb-3">
                                <label for="location_id" class="form-label">
                                    <i class="bi bi-geo-alt"></i> สถานที่ <span class="required-star">*</span>
                                </label>
                                <select class="form-select" id="location_id" name="location_id" required>
                                    <option value="">-- พิมพ์เพื่อค้นหาหรือเลือกสถานที่ --</option>
                                    <?php if ($locations_result->num_rows > 0) {
                                        mysqli_data_seek($locations_result, 0);
                                    } ?>
                                    <?php while ($row = $locations_result->fetch_assoc()): ?>
                                        <option value="<?php echo $row['id']; ?>">
                                            <?php echo htmlspecialchars($row['location_name']); ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                            <div class="mb-4">
                                <label for="reporter_id" class="form-label">
                                    <i class="bi bi-person"></i> ผู้แจ้ง <span class="required-star">*</span>
                                </label>
                                <select class="form-select" id="reporter_id" name="reporter_id" required>
                                    <option value="">-- พิมพ์เพื่อค้นหาหรือเลือกชื่อผู้แจ้ง --</option>
                                    <?php if ($reporters_result->num_rows > 0) {
                                        mysqli_data_seek($reporters_result, 0);
                                    } ?>
                                    <?php while ($row = $reporters_result->fetch_assoc()): ?>
                                        <option value="<?php echo $row['id']; ?>">
                                            <?php echo htmlspecialchars($row['reporter_name']); ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                            <div class="d-grid mb-4">
                                <button type="submit" class="btn btn-submit">
                                    <i class="bi bi-send me-2"></i> ส่งข้อมูลการแจ้งซ่อม
                                </button>
                            </div>
                        </form>

                        <div class="text-center mt-4 d-flex justify-content-center align-items-center gap-3">
                            <?php
                            $admin_link = isset($_SESSION['admin_id']) ? 'admin/dashboard.php' : 'admin/login.php';
                            ?>
                            <a href="<?php echo $admin_link; ?>" class="admin-link">
                                <i class="bi bi-gear"></i> สำหรับผู้ดูแลระบบ
                            </a>
                            <button type="button" class="btn btn-outline-info rounded-pill" data-bs-toggle="modal"
                                data-bs-target="#statusCheckSelectModal">
                                <i class="bi bi-list-check"></i> ติดตามสถานะ
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal 1: Select Reporter -->
    <div class="modal fade" id="reporterSelectModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">ติดตามและให้คะแนน</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>กรุณาเลือกชื่อของคุณเพื่อดูรายการแจ้งซ่อมที่เสร็จสิ้นแล้ว</p>
                    <select class="form-select" id="reporter_select_for_rating" style="width: 100%;">
                        <option value="">-- เลือกชื่อผู้แจ้ง --</option>
                        <?php mysqli_data_seek($reporters_result, 0); ?>
                        <?php while ($row = $reporters_result->fetch_assoc()): ?>
                            <option value="<?php echo $row['id']; ?>"><?php echo htmlspecialchars($row['reporter_name']); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-primary" id="viewMyRequestsBtn">แสดงรายการ</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal 2: List User's Requests & Rating Form -->
    <div class="modal fade" id="ratingModal" tabindex="-1">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="ratingModalTitle">รายการแจ้งซ่อมของคุณ</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="ratingModalBody">
                    <!-- Content is dynamically loaded here -->
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ปิด</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal: Select Reporter for Status Check -->
    <div class="modal fade" id="statusCheckSelectModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-info text-white">
                    <h5 class="modal-title"><i class="bi bi-search me-2"></i>ตรวจสอบสถานะการแจ้งซ่อม</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>กรุณาเลือกชื่อของคุณเพื่อดูสถานะการแจ้งซ่อมทั้งหมด</p>
                    <select class="form-select" id="reporter_select_for_status" style="width: 100%;">
                        <option value="">-- เลือกชื่อผู้แจ้ง --</option>
                        <?php mysqli_data_seek($reporters_result, 0); ?>
                        <?php while ($row = $reporters_result->fetch_assoc()): ?>
                            <option value="<?php echo $row['id']; ?>"><?php echo htmlspecialchars($row['reporter_name']); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-info" id="viewMyStatusBtn"><i
                            class="bi bi-list-check me-1"></i>แสดงสถานะ</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal: Show All Requests with Status -->
    <div class="modal fade" id="statusListModal" tabindex="-1">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header bg-info text-white">
                    <h5 class="modal-title" id="statusListModalTitle"><i
                            class="bi bi-list-ul me-2"></i>สถานะการแจ้งซ่อมของคุณ</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="statusListModalBody">
                    <!-- Content is dynamically loaded here -->
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ปิด</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.all.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>

    <script>
        function padTo2Digits(num) {
            return num.toString().padStart(2, '0');
        }

        function setCurrentDateTime() {
            const now = new Date();
            const dateValue = [
                now.getFullYear(),
                padTo2Digits(now.getMonth() + 1),
                padTo2Digits(now.getDate())
            ].join('-');

            const timeValue = [
                padTo2Digits(now.getHours()),
                padTo2Digits(now.getMinutes())
            ].join(':');

            document.getElementById('request_date').value = dateValue;
            document.getElementById('request_time').value = timeValue;
        }

        $(document).ready(function () {
            setCurrentDateTime();
            $('#location_id').select2({ theme: 'bootstrap-5', placeholder: 'พิมพ์เพื่อค้นหาสถานที่...' });
            $('#reporter_id').select2({ theme: 'bootstrap-5', placeholder: 'พิมพ์เพื่อค้นหาชื่อผู้แจ้ง...' });

            $('#reporter_select_for_rating').select2({
                theme: 'bootstrap-5',
                dropdownParent: $('#reporterSelectModal')
            });

            $('#reporter_select_for_status').select2({
                theme: 'bootstrap-5',
                dropdownParent: $('#statusCheckSelectModal')
            });

            const ratingModal = new bootstrap.Modal(document.getElementById('ratingModal'));

            $('#viewMyRequestsBtn').on('click', function () {
                const reporterId = $('#reporter_select_for_rating').val();
                if (!reporterId) {
                    Swal.fire('กรุณาเลือกชื่อ', 'โปรดเลือกชื่อของคุณก่อน', 'warning');
                    return;
                }
                loadUserRequests(reporterId);
            });

            // ===== Status Check Feature =====
            const statusListModal = new bootstrap.Modal(document.getElementById('statusListModal'));

            $('#viewMyStatusBtn').on('click', function () {
                const reporterId = $('#reporter_select_for_status').val();
                if (!reporterId) {
                    Swal.fire('กรุณาเลือกชื่อ', 'โปรดเลือกชื่อของคุณก่อน', 'warning');
                    return;
                }
                loadUserRequestStatus(reporterId);
            });

            function loadUserRequestStatus(reporterId) {
                const modalBody = $('#statusListModalBody');
                modalBody.html('<p class="text-center"><i class="bi bi-hourglass-split"></i> กำลังโหลดข้อมูล...</p>');
                $('#statusCheckSelectModal').modal('hide');
                statusListModal.show();

                axios.get(`api/get_all_user_requests.php?reporter_id=${reporterId}`)
                    .then(function (response) {
                        if (response.data.success) {
                            renderStatusList(response.data.requests);
                        } else {
                            modalBody.html(`<p class="text-center text-danger">${response.data.message}</p>`);
                        }
                    })
                    .catch(function () {
                        modalBody.html('<p class="text-center text-danger">ไม่สามารถโหลดข้อมูลได้</p>');
                    });
            }

            function getStatusBadge(statusId, statusName) {
                const badges = {
                    1: 'bg-warning text-dark',   // รอรับเรื่อง
                    2: 'bg-info text-white',     // กำลังดำเนินการ
                    3: 'bg-success',             // เสร็จสิ้น (ซ่อมได้)
                    4: 'bg-danger',              // ซ่อมไม่ได้ (รอจำหน่าย)
                    5: 'bg-secondary'            // ส่งซ่อมภายนอก
                };
                const badgeClass = badges[statusId] || 'bg-secondary';
                return `<span class="badge ${badgeClass}">${statusName}</span>`;
            }

            function renderStatusList(requests) {
                const modalBody = $('#statusListModalBody');
                if (requests.length === 0) {
                    modalBody.html('<p class="text-center text-muted"><i class="bi bi-inbox"></i> ไม่พบรายการแจ้งซ่อม</p>');
                    return;
                }

                let listHtml = '<div class="table-responsive"><table class="table table-hover align-middle">';
                listHtml += '<thead class="table-light"><tr><th>#</th><th>วันที่แจ้ง</th><th>ปัญหา</th><th>สถานะ</th><th>ผู้รับผิดชอบ</th><th class="text-center">การดำเนินการ</th></tr></thead><tbody>';

                requests.forEach(req => {
                    const requestDate = req.request_date ? new Date(req.request_date).toLocaleDateString('th-TH', { day: '2-digit', month: 'short', year: 'numeric' }) : 'N/A';
                    const statusName = req.final_status_name || req.current_status_name || 'ไม่ระบุ';
                    const statusId = req.final_status_id || req.current_status_id || 0;
                    const adminName = req.admin_name || '<span class="text-muted">ยังไม่มีผู้รับ</span>';
                    const isCompleted = req.final_status_id !== null;
                    const hasRated = req.satisfaction_rating !== null;

                    let actionHtml = '-';
                    if (isCompleted && !hasRated) {
                        actionHtml = `<button class="btn btn-sm btn-warning rate-status-btn" data-request-id="${req.id}" data-problem="${req.problem_description}"><i class="bi bi-star"></i> ให้คะแนน</button>`;
                    } else if (isCompleted && hasRated) {
                        actionHtml = `<span class="badge bg-success"><i class="bi bi-check-circle"></i> ให้คะแนนแล้ว (${req.satisfaction_rating}★)</span>`;
                    }

                    listHtml += `
                        <tr>
                            <td><strong>${req.id}</strong></td>
                            <td><small>${requestDate}</small></td>
                            <td>${req.problem_description}</td>
                            <td>${getStatusBadge(statusId, statusName)}</td>
                            <td>${adminName}</td>
                            <td class="text-center">${actionHtml}</td>
                        </tr>`;
                });
                listHtml += '</tbody></table></div>';
                modalBody.html(listHtml);
            }

            function loadUserRequests(reporterId) {
                const modalBody = $('#ratingModalBody');
                modalBody.html('<p class="text-center">กำลังโหลดข้อมูล...</p>');
                $('#reporterSelectModal').modal('hide');
                ratingModal.show();

                axios.get(`api/get_user_requests.php?reporter_id=${reporterId}`)
                    .then(function (response) {
                        if (response.data.success) {
                            renderRequestList(response.data.requests);
                        } else {
                            modalBody.html(`<p class="text-center text-danger">${response.data.message}</p>`);
                        }
                    })
                    .catch(function () {
                        modalBody.html('<p class="text-center text-danger">ไม่สามารถโหลดข้อมูลได้</p>');
                    });
            }

            function renderRequestList(requests) {
                const modalBody = $('#ratingModalBody');
                if (requests.length === 0) {
                    modalBody.html('<p class="text-center text-muted">ไม่พบรายการแจ้งซ่อมที่เสร็จสิ้นแล้ว หรือคุณได้ให้คะแนนไปหมดแล้ว</p>');
                    return;
                }

                let listHtml = '<ul class="list-group list-group-flush">';
                requests.forEach(req => {
                    const repairDate = req.repair_date ? new Date(req.repair_date).toLocaleDateString('th-TH') : 'N/A';
                    const hasRated = req.satisfaction_rating !== null;
                    const buttonHtml = hasRated
                        ? `<button class="btn btn-sm btn-success disabled"><i class="bi bi-check-circle"></i> ให้คะแนนแล้ว</button>`
                        : `<button class="btn btn-sm btn-primary rate-btn" data-request-id="${req.id}" data-problem="${req.problem_description}">ให้คะแนน</button>`;

                    listHtml += `
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="mb-1">ปัญหา: ${req.problem_description}</h6>
                                <small class="text-muted">ID: ${req.id} | วันที่ซ่อม: ${repairDate}</small>
                            </div>
                            ${buttonHtml}
                        </li>`;
                });
                listHtml += '</ul>';
                modalBody.html(listHtml);
            }

            $('#ratingModalBody').on('click', '.rate-btn', function () {
                const requestId = $(this).data('request-id');
                const problem = $(this).data('problem');
                showRatingForm(requestId, problem, function () {
                    const reporterId = $('#reporter_select_for_rating').val();
                    loadUserRequests(reporterId);
                });
            });

            // Event handler for rating button in status table
            $('#statusListModalBody').on('click', '.rate-status-btn', function () {
                const requestId = $(this).data('request-id');
                const problem = $(this).data('problem');
                showRatingForm(requestId, problem, function () {
                    const reporterId = $('#reporter_select_for_status').val();
                    loadUserRequestStatus(reporterId);
                });
            });

            function showRatingForm(requestId, problem, onSuccess) {
                // Close the status modal first to avoid z-index issues
                $('#statusListModal').modal('hide');

                setTimeout(() => {
                    Swal.fire({
                        title: 'ให้คะแนนความพึงพอใจ',
                        html: `
                            <p class="mb-2"><strong>ปัญหา:</strong> ${problem}</p>
                            <div class="rating-stars mb-3">
                                <input type="radio" name="rating" id="rs5" value="5"><label for="rs5">★</label>
                                <input type="radio" name="rating" id="rs4" value="4"><label for="rs4">★</label>
                                <input type="radio" name="rating" id="rs3" value="3"><label for="rs3">★</label>
                                <input type="radio" name="rating" id="rs2" value="2"><label for="rs2">★</label>
                                <input type="radio" name="rating" id="rs1" value="1"><label for="rs1">★</label>
                            </div>
                            <textarea id="swal-comment" class="form-control" rows="3" placeholder="ความคิดเห็นเพิ่มเติม (ถ้ามี)"></textarea>
                        `,
                        confirmButtonText: 'ส่งคะแนน',
                        showCancelButton: true,
                        cancelButtonText: 'ยกเลิก',
                        didOpen: () => {
                            // Ensure textarea is clickable
                            const textarea = document.getElementById('swal-comment');
                            if (textarea) textarea.focus();
                        },
                        preConfirm: () => {
                            const rating = document.querySelector('input[name="rating"]:checked');
                            if (!rating) {
                                Swal.showValidationMessage('กรุณาเลือกดาวเพื่อให้คะแนน');
                                return false;
                            }
                            return {
                                rating: rating.value,
                                comment: document.getElementById('swal-comment').value
                            }
                        }
                    }).then((result) => {
                        if (result.isConfirmed) {
                            const formData = new FormData();
                            formData.append('request_id', requestId);
                            formData.append('rating', result.value.rating);
                            formData.append('comment', result.value.comment);

                            axios.post('api/save_satisfaction.php', formData)
                                .then(function (response) {
                                    if (response.data.success) {
                                        Swal.fire('สำเร็จ!', response.data.message, 'success').then(() => {
                                            // Reopen status modal and refresh
                                            statusListModal.show();
                                            if (onSuccess) onSuccess();
                                        });
                                    } else {
                                        Swal.fire('เกิดข้อผิดพลาด!', response.data.message, 'error');
                                    }
                                });
                        } else {
                            // User cancelled, reopen the status modal
                            statusListModal.show();
                        }
                    });
                }, 300); // Wait for modal to fully hide
            }

            // ===== ส่วนที่แก้ไข: จัดการการส่งฟอร์ม =====
            document.getElementById('requestForm').addEventListener('submit', function (event) {
                event.preventDefault();
                const form = this; // เก็บ form ที่ถูก submit ไว้

                // แสดง Pop-up ยืนยันก่อน
                Swal.fire({
                    title: 'ยืนยันการแจ้งปัญหา',
                    text: "ระบบนี้สำหรับแจ้งปัญหาที่เกี่ยวข้องกับงาน IT เท่านั้น กรุณายืนยันเพื่อดำเนินการต่อ",
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonColor: '#3085d6',
                    cancelButtonColor: '#d33',
                    confirmButtonText: 'ยืนยัน, เป็นปัญหา IT',
                    cancelButtonText: 'ยกเลิก'
                }).then((result) => {
                    // ถ้าผู้ใช้กดยืนยัน
                    if (result.isConfirmed) {
                        const formData = new FormData(form);

                        // แสดงการโหลด
                        Swal.fire({
                            title: 'กำลังส่งข้อมูล...',
                            text: 'กรุณารอสักครู่',
                            allowOutsideClick: false,
                            showConfirmButton: false,
                            didOpen: () => { Swal.showLoading(); }
                        });

                        // ส่งข้อมูลไปยัง API
                        axios.post('api/save_request.php', formData)
                            .then(function (response) {
                                if (response.data.success) {
                                    Swal.fire({
                                        icon: 'success',
                                        title: 'แจ้งซ่อมสำเร็จ!',
                                        text: 'เจ้าหน้าที่จะดำเนินการตรวจสอบโดยเร็วที่สุด',
                                        timer: 3000,
                                        showConfirmButton: false
                                    })
                                        .then(() => {
                                            form.reset();
                                            $('#location_id').val(null).trigger('change');
                                            $('#reporter_id').val(null).trigger('change');
                                            setCurrentDateTime();
                                        });
                                } else {
                                    Swal.fire({
                                        icon: 'error',
                                        title: 'เกิดข้อผิดพลาด!',
                                        text: response.data.message || 'ไม่สามารถบันทึกข้อมูลได้'
                                    });
                                }
                            })
                            .catch(function (error) {
                                Swal.fire({
                                    icon: 'error',
                                    title: 'เกิดข้อผิดพลาดในการเชื่อมต่อ',
                                    text: 'กรุณาลองใหม่อีกครั้ง'
                                });
                            });
                    }
                });
            });

        });
    </script>
</body>

</html>