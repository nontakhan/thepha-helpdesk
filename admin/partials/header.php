<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Thepha Helpdesk - Admin</title>

    <!-- Favicon and PWA Manifest -->
    <link rel="icon" href="../assets/images/icons/favicon.ico" type="image/x-icon">
    <link rel="apple-touch-icon" href="../assets/images/icons/apple-touch-icon.png">
    <meta name="theme-color" content="#343a40">
    <link rel="manifest" href="../manifest.json">

    <!-- Google Fonts: Sarabun -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@400;500;700&display=swap" rel="stylesheet">

    <!-- Libraries CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" />
    <link rel="stylesheet" href="https://cdn.datatables.net/2.0.8/css/dataTables.bootstrap5.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/buttons/3.0.2/css/buttons.bootstrap5.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/responsive/3.0.2/css/responsive.bootstrap5.css">
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
</head>
<body class="bg-light">

<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
  <div class="container-fluid">
    <a class="navbar-brand" href="dashboard.php">Thepha Helpdesk</a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="navbarNav">
      <ul class="navbar-nav me-auto">
        <li class="nav-item">
          <a class="nav-link" href="dashboard.php">Dashboard</a>
        </li>
        <li class="nav-item">
          <a class="nav-link" href="calendar.php">ปฏิทินการทำงาน</a>
        </li>
        <li class="nav-item">
          <a class="nav-link" href="requests_list.php">รายการแจ้งซ่อม</a>
        </li>
        <li class="nav-item dropdown">
            <a class="nav-link dropdown-toggle" href="#" id="reportDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                รายงาน
            </a>
            <ul class="dropdown-menu" aria-labelledby="reportDropdown">
                <li><a class="dropdown-item" href="report.php">รายงานการแจ้งซ่อม</a></li>
                <li><a class="dropdown-item" href="activity_report.php">รายงานกิจกรรมเจ้าหน้าที่</a></li>
            </ul>
        </li>
        <li class="nav-item dropdown">
            <a class="nav-link dropdown-toggle" href="#" id="masterDataDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                จัดการข้อมูลหลัก
            </a>
            <ul class="dropdown-menu" aria-labelledby="masterDataDropdown">
                <li><a class="dropdown-item" href="manage_locations.php">จัดการสถานที่</a></li>
                <li><a class="dropdown-item" href="manage_reporters.php">จัดการผู้แจ้ง</a></li>
                <li><a class="dropdown-item" href="manage_categories.php">จัดการประเภทงานซ่อม</a></li>
                <li><hr class="dropdown-divider"></li>
                <li><a class="dropdown-item" href="manage_activity_types.php">จัดการประเภทกิจกรรม</a></li>
            </ul>
        </li>
      </ul>
      <ul class="navbar-nav">
        <li class="nav-item me-3">
          <a class="btn btn-outline-success" href="../index.php" target="_blank">ไปที่หน้าแจ้งซ่อม</a>
        </li>
        <li class="nav-item dropdown">
            <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                สวัสดี, <?php echo htmlspecialchars($_SESSION['admin_full_name']); ?>
            </a>
            <ul class="dropdown-menu dropdown-menu-end">
                <li><a class="dropdown-item" href="logout.php">ออกจากระบบ</a></li>
            </ul>
        </li>
      </ul>
    </div>
  </div>
</nav>

<div class="container mt-4">