<?php
require_once 'partials/header.php';
require_once '../db_connect.php';

// --- นับจำนวนรายการในการ์ด (เหมือนเดิม) ---
$sql_pending = "SELECT COUNT(id) as total_pending FROM requests WHERE current_status_id = 1";
$result_pending = $conn->query($sql_pending);
$pending_count = $result_pending->fetch_assoc()['total_pending'];

$sql_processing = "SELECT COUNT(id) as total_processing FROM requests WHERE current_status_id = 2";
$result_processing = $conn->query($sql_processing);
$processing_count = $result_processing->fetch_assoc()['total_processing'];

// นับจำนวนงานที่เสร็จสิ้นในเดือนนี้
$current_month = date('Y-m');
$sql_completed = "SELECT COUNT(id) as total_completed FROM requests WHERE final_status_id = 3 AND DATE_FORMAT(repair_date, '%Y-%m') = '$current_month'";
$result_completed = $conn->query($sql_completed);
$completed_count_month = $result_completed->fetch_assoc()['total_completed'];

?>

<!-- ส่วนของการ์ดสรุปข้อมูล (ปรับปรุงใหม่) -->
<div class="row mb-4">
    <div class="col-md-4">
        <div class="card text-white bg-warning mb-3">
            <div class="card-body"><h5 class="card-title">รอรับเรื่อง</h5><p class="card-text fs-2"><?php echo $pending_count; ?> รายการ</p></div>
            <div class="card-footer text-center"><a href="requests_list.php?status=1" class="text-white">ดูทั้งหมด <i class="bi bi-arrow-right-circle"></i></a></div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card text-white bg-info mb-3">
            <div class="card-body"><h5 class="card-title">กำลังดำเนินการ</h5><p class="card-text fs-2"><?php echo $processing_count; ?> รายการ</p></div>
            <div class="card-footer text-center"><a href="requests_list.php?status=2" class="text-white">ดูทั้งหมด <i class="bi bi-arrow-right-circle"></i></a></div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card text-white bg-success mb-3">
            <div class="card-body"><h5 class="card-title">งานที่เสร็จสิ้น (เดือนนี้)</h5><p class="card-text fs-2"><?php echo $completed_count_month; ?> รายการ</p></div>
            <div class="card-footer text-center"><a href="report.php" class="text-white">ไปที่หน้ารายงาน <i class="bi bi-arrow-right-circle"></i></a></div>
        </div>
    </div>
</div>

<!-- ===== ส่วนของกราฟ (ปรับปรุง Layout และเพิ่มกราฟ SLA) ===== -->
<div class="row">
    <!-- กราฟแท่ง (จำนวนแจ้งซ่อมรายเดือน) -->
    <div class="col-lg-12 mb-4">
        <div class="card">
            <div class="card-header"><h5 class="card-title mb-0">จำนวนการแจ้งซ่อมรายเดือน (12 เดือนย้อนหลัง)</h5></div>
            <div class="card-body" style="height: 300px;">
                <canvas id="monthlyRequestsChart"></canvas>
            </div>
        </div>
    </div>
    <!-- กราฟวงกลม สัดส่วนงานตามประเภท -->
    <div class="col-lg-6 mb-4">
        <div class="card">
            <div class="card-header"><h5 class="card-title mb-0">สัดส่วนงานตามประเภท</h5></div>
            <div class="card-body" style="height: 300px;">
                 <canvas id="categoryRequestsChart"></canvas>
            </div>
        </div>
    </div>
    <!-- กราฟวงกลม สรุปผล SLA -->
    <div class="col-lg-6 mb-4">
        <div class="card">
            <div class="card-header"><h5 class="card-title mb-0">สรุปผลการทำงาน (SLA)</h5></div>
            <div class="card-body" style="height: 300px;">
                 <canvas id="slaPerformanceChart"></canvas>
            </div>
        </div>
    </div>
    <!-- กราฟแท่งแบบซ้อน (สถานที่รายเดือน) -->
    <div class="col-lg-12 mb-4">
        <div class="card">
            <div class="card-header"><h5 class="card-title mb-0">สถิติการแจ้งซ่อมตามสถานที่ (6 เดือนย้อนหลัง)</h5></div>
            <div class="card-body" style="height: 350px;">
                 <canvas id="locationMonthlyChart"></canvas>
            </div>
        </div>
    </div>
</div>


<!-- เรียกใช้ Chart.js และ Axios -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function () {
    // ดึงข้อมูลจาก API
    axios.get('../api/get_chart_data.php')
        .then(function (response) {
            const chartData = response.data;

            // --- 1. สร้างกราฟแท่ง ---
            new Chart(document.getElementById('monthlyRequestsChart'), {
                type: 'bar', data: { labels: chartData.monthlyRequests.labels, datasets: [{ label: 'จำนวนเรื่อง', data: chartData.monthlyRequests.data, backgroundColor: 'rgba(54, 162, 235, 0.6)', borderColor: 'rgba(54, 162, 235, 1)', borderWidth: 1 }] },
                options: { scales: { y: { beginAtZero: true } }, responsive: true, maintainAspectRatio: false }
            });

            // --- 2. สร้างกราฟวงกลม (ประเภทงาน) ---
            new Chart(document.getElementById('categoryRequestsChart'), {
                type: 'doughnut', data: { labels: chartData.categoryRequests.labels, datasets: [{ label: 'จำนวนเรื่อง', data: chartData.categoryRequests.data, backgroundColor: ['rgba(255, 99, 132, 0.7)', 'rgba(54, 162, 235, 0.7)','rgba(255, 206, 86, 0.7)', 'rgba(75, 192, 192, 0.7)','rgba(153, 102, 255, 0.7)', 'rgba(255, 159, 64, 0.7)'] }] },
                options: { responsive: true, maintainAspectRatio: false }
            });

            // --- 3. สร้างกราฟวงกลม (SLA) ---
            new Chart(document.getElementById('slaPerformanceChart'), {
                type: 'pie', data: { labels: chartData.slaPerformance.labels, datasets: [{ label: 'จำนวนเรื่อง', data: chartData.slaPerformance.data, backgroundColor: ['rgba(75, 192, 192, 0.7)', 'rgba(255, 99, 132, 0.7)'] }] },
                options: { responsive: true, maintainAspectRatio: false }
            });

            // --- 4. สร้างกราฟแท่งแบบซ้อน (สถานที่รายเดือน) ---
            new Chart(document.getElementById('locationMonthlyChart'), {
                type: 'bar',
                data: chartData.locationMonthly,
                options: {
                    responsive: true, maintainAspectRatio: false,
                    scales: {
                        x: { stacked: true },
                        y: { stacked: true, beginAtZero: true }
                    }
                }
            });

        })
        .catch(function (error) {
            console.error("Error fetching chart data:", error);
        });
});
</script>

<?php
$conn->close();
require_once 'partials/footer.php';
?>
