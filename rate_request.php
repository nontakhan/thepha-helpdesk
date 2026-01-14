<?php
require_once 'db_connect.php';

// ตรวจสอบว่ามี reporter_id ส่งมาทาง URL หรือไม่
if (!isset($_GET['reporter_id']) || empty($_GET['reporter_id'])) {
    die("<h1>เกิดข้อผิดพลาด: ไม่พบรหัสผู้ใช้งาน</h1><p>กรุณากลับไปที่หน้าแรกและลองอีกครั้ง</p>");
}

$reporter_id = (int)$_GET['reporter_id'];

// ดึงชื่อผู้ใช้เพื่อมาแสดงผล
$reporter_name_result = $conn->query("SELECT reporter_name FROM reporters WHERE id = $reporter_id");
$reporter_name = ($reporter_name_result->num_rows > 0) ? $reporter_name_result->fetch_assoc()['reporter_name'] : "ไม่พบชื่อ";
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ให้คะแนนความพึงพอใจ - Thepha Helpdesk</title>

    <!-- Google Fonts: Sarabun -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;500;700&display=swap" rel="stylesheet">
    
    <!-- Libraries CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet">
    
    <style>
        body {
            font-family: 'Sarabun', sans-serif;
            background-color: #f4f7f6;
        }
        .rating-container {
            max-width: 800px;
            margin: 2rem auto;
        }
        .rating-stars {
            display: inline-flex;
            flex-direction: row-reverse;
            justify-content: center;
        }
        .rating-stars input { display: none; }
        .rating-stars label {
            font-size: 2.5rem;
            color: #ddd;
            cursor: pointer;
            transition: color 0.2s;
        }
        .rating-stars input:checked ~ label,
        .rating-stars label:hover,
        .rating-stars label:hover ~ label {
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
    <div class="container rating-container">
        <div class="card shadow-sm">
            <div class="card-header bg-primary text-white">
                <h4 class="mb-0">ให้คะแนนความพึงพอใจ</h4>
                <p class="mb-0">สำหรับ: <?php echo htmlspecialchars($reporter_name); ?></p>
            </div>
            <div class="card-body">
                <div id="request-list-container">
                    <!-- รายการจะถูกโหลดมาแสดงที่นี่ -->
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.all.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
    
    <script>
        $(document).ready(function() {
            const reporterId = <?php echo $reporter_id; ?>;
            const container = $('#request-list-container');

            function loadUserRequests() {
                container.html('<p class="text-center">กำลังโหลดข้อมูล...</p>');

                axios.get(`api/get_user_requests.php?reporter_id=${reporterId}`)
                    .then(function(response) {
                        if (response.data && response.data.success) {
                            if(response.data.requests.length > 0) {
                                renderRequestList(response.data.requests);
                            } else {
                                container.html('<p class="text-center text-muted">ไม่พบรายการแจ้งซ่อมที่เสร็จสิ้นแล้ว หรือคุณได้ให้คะแนนไปหมดแล้ว</p>');
                            }
                        } else {
                            const errorMessage = response.data.message || 'เกิดข้อผิดพลาดในการดึงข้อมูล';
                            container.html(`<div class="alert alert-danger"><b>ผิดพลาด:</b> ${errorMessage}</div>`);
                        }
                    })
                    .catch(function(error) {
                        console.error("Axios Error:", error);
                        container.html('<div class="alert alert-danger"><b>ผิดพลาด:</b> ไม่สามารถเชื่อมต่อกับเซิร์ฟเวอร์ได้</div>');
                    });
            }

            function renderRequestList(requests) {
                let listHtml = '<ul class="list-group list-group-flush">';
                requests.forEach(req => {
                    const repairDate = req.repair_date ? new Date(req.repair_date).toLocaleDateString('th-TH') : 'N/A';
                    const hasRated = req.satisfaction_rating !== null;
                    
                    // ===== ส่วนที่แก้ไข: เพิ่ม data-* สำหรับข้อมูลการซ่อม =====
                    const buttonHtml = hasRated 
                        ? `<button class="btn btn-sm btn-success disabled"><i class="bi bi-check-circle"></i> ให้คะแนนแล้ว</button>`
                        : `<button class="btn btn-sm btn-primary rate-btn" 
                                    data-request-id="${req.id}" 
                                    data-problem="${req.problem_description}"
                                    data-solution="${req.solution || ''}"
                                    data-cause="${req.cause || ''}"
                                    data-admin="${req.admin_name || 'N/A'}">ให้คะแนน</button>`;

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
                container.html(listHtml);
            }

            // ===== ส่วนที่แก้ไข: ดึงข้อมูลการซ่อมจาก data-* =====
            container.on('click', '.rate-btn', function() {
                const button = $(this);
                const requestId = button.data('request-id');
                const problem = button.data('problem');
                const solution = button.data('solution');
                const cause = button.data('cause');
                const admin = button.data('admin');
                showRatingForm(requestId, problem, solution, cause, admin);
            });

            // ===== ส่วนที่แก้ไข: เพิ่มพารามิเตอร์และแสดงผลใน Pop-up =====
            function showRatingForm(requestId, problem, solution, cause, admin) {
                Swal.fire({
                    title: 'ให้คะแนนความพึงพอใจ',
                    html: `
                        <div class="text-start p-2" style="background-color: #f8f9fa; border-radius: 8px;">
                            <p class="mb-1"><strong>ปัญหา:</strong> ${problem}</p>
                            <hr class="my-2">
                            <p class="mb-1"><strong>ผู้ดำเนินการ:</strong> ${admin}</p>
                            <p class="mb-1"><strong>สาเหตุ:</strong> ${cause || 'ไม่ได้ระบุ'}</p>
                            <p class="mb-1"><strong>วิธีแก้ไข:</strong> ${solution || 'ไม่ได้ระบุ'}</p>
                        </div>
                        <hr>
                        <div class="rating-stars mb-3">
                            <input type="radio" name="rating" id="rs5" value="5"><label for="rs5">★</label>
                            <input type="radio" name="rating" id="rs4" value="4"><label for="rs4">★</label>
                            <input type="radio" name="rating" id="rs3" value="3"><label for="rs3">★</label>
                            <input type="radio" name="rating" id="rs2" value="2"><label for="rs2">★</label>
                            <input type="radio" name="rating" id="rs1" value="1"><label for="rs1">★</label>
                        </div>
                        <textarea id="swal-comment" class="form-control" placeholder="ความคิดเห็นเพิ่มเติม (ถ้ามี)"></textarea>
                    `,
                    confirmButtonText: 'ส่งคะแนน',
                    showCancelButton: true,
                    cancelButtonText: 'ยกเลิก',
                    stopKeydownPropagation: false,
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
                            .then(function(response) {
                                if (response.data.success) {
                                    Swal.fire('สำเร็จ!', response.data.message, 'success');
                                    loadUserRequests(); // รีเฟรชรายการ
                                } else {
                                    Swal.fire('เกิดข้อผิดพลาด!', response.data.message, 'error');
                                }
                            });
                    }
                });
            }
            
            // โหลดรายการครั้งแรกเมื่อเปิดหน้า
            loadUserRequests();
        });
    </script>
</body>
</html>
