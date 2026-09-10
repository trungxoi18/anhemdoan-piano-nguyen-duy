<?php
// 1. Chỉ khởi tạo session nếu nó chưa được bật (tránh lỗi trùng lặp session)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Tự động nhận diện môi trường: Localhost (XAMPP) hay Hosting Online (InfinityFree)
if (isset($_SERVER['HTTP_HOST']) && ($_SERVER['HTTP_HOST'] === 'localhost' || strpos($_SERVER['HTTP_HOST'], '127.0.0.1') !== false)) {
    // Cấu hình máy tính cá nhân (XAMPP)
    $host = "localhost";
    $username = "root"; 
    $password = "";   
    $database = "quanlybandan";
} else {
    // Cấu hình Hosting InfinityFree
    $host = "sql103.infinityfree.com";
    $username = "if0_42879752"; 
    $password = "pianonguyenduy1";   
    $database = "if0_42879752_quanlybandan";
}

// 2. Kích hoạt chế độ báo lỗi Exception cho MySQLi để dễ debug
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {
    // Thực hiện kết nối
    $conn = new mysqli($host, $username, $password, $database);
    
    // 3. Set charset utf8mb4 để hỗ trợ tối đa tiếng Việt có dấu
    $conn->set_charset("utf8mb4");
    
} catch (Exception $e) {
    // Nếu lỗi kết nối, dừng hệ thống và báo lỗi
    die("Kết nối cơ sở dữ liệu thất bại: Vui lòng kiểm tra lại cấu hình XAMPP/MySQL.");
    // Dùng code dưới để xem chi tiết lỗi khi đang code (tắt đi khi chạy thực tế):
    // die("Lỗi chi tiết: " . $e->getMessage());
}