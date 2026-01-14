<?php
header('Content-Type: application/json');
require_once '../db_connect.php';

// เตรียมโครงสร้างข้อมูลเริ่มต้น
$response = [
    'monthlyRequests' => ['labels' => [], 'data' => []],
    'categoryRequests' => ['labels' => [], 'data' => []],
    'slaPerformance' => ['labels' => ['ผ่าน', 'ไม่ผ่าน'], 'data' => [0, 0]],
    'locationMonthly' => ['labels' => [], 'datasets' => []] // <-- เพิ่มส่วนใหม่สำหรับกราฟสถานที่รายเดือน
];

try {
    // --- 1. ข้อมูลสำหรับกราฟแท่ง: จำนวนการแจ้งซ่อมรายเดือน ---
    $monthly_sql = "
        SELECT DATE_FORMAT(request_date, '%Y-%m') as month, COUNT(id) as total 
        FROM requests WHERE request_date >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
        GROUP BY month ORDER BY month ASC";
    $monthly_result = $conn->query($monthly_sql);
    while ($row = $monthly_result->fetch_assoc()) {
        $response['monthlyRequests']['labels'][] = $row['month'];
        $response['monthlyRequests']['data'][] = (int)$row['total'];
    }

    // --- 2. ข้อมูลสำหรับกราฟวงกลม: สรุปงานตามประเภท ---
    $category_sql = "
        SELECT c.category_name, COUNT(r.id) as total FROM requests r
        JOIN categories c ON r.category_id = c.id WHERE r.category_id IS NOT NULL
        GROUP BY c.category_name ORDER BY total DESC";
    $category_result = $conn->query($category_sql);
    while ($row = $category_result->fetch_assoc()) {
        $response['categoryRequests']['labels'][] = $row['category_name'];
        $response['categoryRequests']['data'][] = (int)$row['total'];
    }

    // --- 3. ข้อมูลสำหรับกราฟ SLA ---
    $sla_sql = "
        SELECT CASE WHEN r.resolution_time_minutes <= IF(l.department_type = 'หน่วยงานให้บริการ', 15, 30) THEN 'Pass' ELSE 'Fail' END as sla_status, COUNT(r.id) as total
        FROM requests r JOIN locations l ON r.location_id = l.id
        WHERE r.resolution_time_minutes IS NOT NULL GROUP BY sla_status";
    $sla_result = $conn->query($sla_sql);
    while($row = $sla_result->fetch_assoc()){
        if($row['sla_status'] == 'Pass'){
            $response['slaPerformance']['data'][0] = (int)$row['total'];
        } else {
            $response['slaPerformance']['data'][1] = (int)$row['total'];
        }
    }

    // --- 4. ข้อมูลสำหรับกราฟแท่งแบบซ้อน: สถานที่รายเดือน (6 เดือนย้อนหลัง) ---
    $location_monthly_sql = "
        SELECT DATE_FORMAT(r.request_date, '%Y-%m') AS month, l.location_name, COUNT(r.id) AS total
        FROM requests r JOIN locations l ON r.location_id = l.id
        WHERE r.request_date >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
        GROUP BY month, l.location_name ORDER BY month, l.location_name";
    
    $location_monthly_result = $conn->query($location_monthly_sql);
    $data_pivot = [];
    $months = [];
    $locations = [];

    while($row = $location_monthly_result->fetch_assoc()){
        $data_pivot[$row['month']][$row['location_name']] = $row['total'];
        if(!in_array($row['month'], $months)) $months[] = $row['month'];
        if(!in_array($row['location_name'], $locations)) $locations[] = $row['location_name'];
    }
    sort($months);

    $response['locationMonthly']['labels'] = $months;
    $colors = ['rgba(255, 99, 132, 0.5)', 'rgba(54, 162, 235, 0.5)', 'rgba(255, 206, 86, 0.5)', 'rgba(75, 192, 192, 0.5)', 'rgba(153, 102, 255, 0.5)', 'rgba(255, 159, 64, 0.5)'];
    $color_index = 0;

    foreach($locations as $location){
        $dataset = [
            'label' => $location,
            'data' => [],
            'backgroundColor' => $colors[$color_index % count($colors)]
        ];
        foreach($months as $month){
            $dataset['data'][] = isset($data_pivot[$month][$location]) ? (int)$data_pivot[$month][$location] : 0;
        }
        $response['locationMonthly']['datasets'][] = $dataset;
        $color_index++;
    }

} catch (Exception $e) {
    // Handle error
}

$conn->close();
echo json_encode($response);
?>
