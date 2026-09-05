<?php
// Chỉ bật qua trình duyệt hoặc terminal
require_once 'db.php';

// Các index cần tạo
$indexes = [
    // Bảng hoadon
    "ALTER TABLE hoadon ADD INDEX idx_hoadon_ngayLap (ngayLap)",
    "ALTER TABLE hoadon ADD INDEX idx_hoadon_trangThai (trangThai)",
    
    // Bảng danserial
    "ALTER TABLE danserial ADD INDEX idx_ds_trangThai (trangThai)",
    "ALTER TABLE danserial ADD INDEX idx_ds_giaBan (giaBan)",
    
    // Bảng phieuxuat
    "ALTER TABLE phieuxuat ADD INDEX idx_px_ngayXuat (ngayXuat)",
    "ALTER TABLE phieuxuat ADD INDEX idx_px_trangThai (trangThai)",
    
    // Bảng phieunhap
    "ALTER TABLE phieunhap ADD INDEX idx_pn_ngayNhap (ngayNhap)",
    "ALTER TABLE phieunhap ADD INDEX idx_pn_trangThai (trangThai)",
    
    // Bảng nhatkyhethong
    "ALTER TABLE nhatkyhethong ADD INDEX idx_log_thoiGian (thoiGian)"
];

echo "<h2>Bắt đầu tối ưu hóa cơ sở dữ liệu (Tạo Index)</h2>";
echo "<ul>";

foreach ($indexes as $sql) {
    try {
        $conn->query($sql);
        echo "<li style='color: green;'>Thành công: <code>$sql</code></li>";
    } catch (Exception $e) {
        // Lỗi 1061 là Duplicate key name, nghĩa là index đã tồn tại.
        if ($conn->errno == 1061) {
            echo "<li style='color: orange;'>Đã tồn tại (Bỏ qua): <code>$sql</code></li>";
        } else {
            echo "<li style='color: red;'>Lỗi: <code>$sql</code> - " . $e->getMessage() . "</li>";
        }
    }
}

echo "</ul>";
echo "<h3>Hoàn thành tối ưu hóa! Bạn có thể xóa file này sau khi chạy xong.</h3>";
?>
