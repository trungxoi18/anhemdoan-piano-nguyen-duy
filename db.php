<?php
// 1. Chỉ khởi tạo session nếu nó chưa được bật (tránh lỗi trùng lặp session)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$host = "localhost";
$username = "root"; 
$password = "";   
$database = "quanlybandan"; // Viết hoa đúng với tên CSDL bạn vừa chạy trong phpMyAdmin

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