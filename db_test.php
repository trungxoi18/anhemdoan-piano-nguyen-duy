<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once 'db.php';

echo "<div style='font-family: Arial, sans-serif; padding: 25px; max-width: 600px; margin: 30px auto; background: #fff; border: 1px solid #ddd; border-radius: 12px; box-shadow: 0 4px 12px rgba(0,0,0,0.1);'>";
echo "<h2 style='margin-top:0;'>Kiểm tra Kết Nối Máy Chủ & CSDL</h2>";

if (isset($conn) && !$conn->connect_error) {
    echo "<p style='color: #10b981; font-weight: bold; font-size: 16px;'>✅ Kết nối MySQL thành công!</p>";
    echo "<p><strong>Máy chủ:</strong> " . htmlspecialchars($host) . "<br>";
    echo "<strong>Cơ sở dữ liệu:</strong> " . htmlspecialchars($database) . "</p>";
    
    $res = $conn->query("SHOW TABLES");
    if ($res && $res->num_rows > 0) {
        echo "<p style='color: #2563eb;'><strong>Số lượng bảng dữ liệu đã nạp:</strong> " . $res->num_rows . " bảng</p>";
        echo "<div style='max-height: 200px; overflow-y: auto; background: #f8fafc; padding: 10px; border-radius: 6px; border: 1px solid #e2e8f0;'>";
        echo "<ul style='margin:0; padding-left: 20px;'>";
        while ($r = $res->fetch_array()) {
            echo "<li>" . htmlspecialchars($r[0]) . "</li>";
        }
        echo "</ul></div>";
        echo "<p style='margin-top: 15px;'><a href='login.php' style='display: inline-block; padding: 10px 18px; background: #0ea5e9; color: white; text-decoration: none; border-radius: 6px; font-weight: bold;'>👉 Đến trang Đăng Nhập</a></p>";
    } else {
        echo "<div style='background: #fffbeb; border: 1px solid #fde68a; color: #b45309; padding: 12px; border-radius: 6px; margin-top: 10px;'>";
        echo "<strong>⚠️ Cảnh báo:</strong> CSDL hiện đang TRỐNG (chưa có bảng nào).<br>";
        echo "Bạn cần vào tab <strong>phpMyAdmin</strong> trên InfinityFree và Import file <strong>quanlybandan.sql</strong> để nạp dữ liệu!";
        echo "</div>";
    }
} else {
    echo "<p style='color: #ef4444; font-weight: bold;'>❌ Kết nối MySQL thất bại!</p>";
}
echo "</div>";
