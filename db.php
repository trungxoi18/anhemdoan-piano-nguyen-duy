<?php
// Bật hiển thị lỗi để gỡ lỗi khi có sự cố trên Hosting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

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

// 2. Tắt chế độ Strict Exception để các truy vấn không làm sập cả trang web
mysqli_report(MYSQLI_REPORT_OFF);

try {
    // Thực hiện kết nối
    $conn = new mysqli($host, $username, $password, $database);
    
    // 3. Set charset utf8mb4 để hỗ trợ tối đa tiếng Việt có dấu
    $conn->set_charset("utf8mb4");
    
} catch (Throwable $e) {
    // Nếu lỗi kết nối, hiển thị chi tiết để biết chính xác nguyên nhân
    die("<div style='font-family: Arial; padding: 20px; background: #fff3f3; color: #d32f2f; border: 1px solid #f44336; border-radius: 8px; margin: 30px auto; max-width: 600px;'>
            <h3 style='margin-top: 0;'>⚠️ Lỗi kết nối Cơ sở dữ liệu:</h3>
            <p><strong>Chi tiết:</strong> " . htmlspecialchars($e->getMessage()) . "</p>
            <p>Vui lòng kiểm tra lại trạng thái database trên hosting InfinityFree.</p>
         </div>");
}