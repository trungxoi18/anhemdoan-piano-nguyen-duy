<?php
// Tải các cấu hình và hàm dùng chung
require_once 'db.php';
require_once 'functions.php';

// Kiểm tra đăng nhập
checkLogin();

// Lấy thông tin từ Session
$fullname = $_SESSION['fullname'];
$role = $_SESSION['role_id'];

// Map role_id với Tên vai trò và Class CSS
if ($role == 1) { 
    $role_name = "Quản trị viên"; 
    $role_class = "admin"; 
} else { 
    $role_name = "Thủ kho"; 
    $role_class = "staff"; 
}

// Lấy chữ cái đầu tiên của tên để làm Avatar
$avatar_letter = mb_substr($fullname, 0, 1, "UTF-8");

// Lấy ảnh đại diện nếu có
$user_avatar = null;
$uid_header = $_SESSION['user_id'] ?? 0;
$stmt_avatar = $conn->prepare("SELECT nv.anhDaiDien FROM nhanvien nv JOIN taikhoan tk ON tk.maNhanVien = nv.maNhanVien WHERE tk.maTaiKhoan = ?");
if ($stmt_avatar) {
    $stmt_avatar->bind_param("i", $uid_header);
    $stmt_avatar->execute();
    $res_avatar = $stmt_avatar->get_result();
    if ($row_avatar = $res_avatar->fetch_assoc()) {
        if (!empty($row_avatar['anhDaiDien']) && file_exists($row_avatar['anhDaiDien'])) {
            $user_avatar = $row_avatar['anhDaiDien'];
        }
    }
    $stmt_avatar->close();
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
    <meta name="theme-color" content="#0ea5e9">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <title>Dashboard - KHO ĐÀN PRO</title>
    
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Rounded:opsz,wght,FILL,GRAD@24,400,0,0" />
    
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <link rel="stylesheet" href="assets/css/style.css?v=<?= filemtime(__DIR__ . '/../assets/css/style.css') ?>">
</head>
<body>