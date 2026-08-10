<?php
require 'db.php';

$sql = "CREATE TABLE IF NOT EXISTS vieccanlam (
    maViec INT AUTO_INCREMENT PRIMARY KEY,
    maTaiKhoan INT NOT NULL,
    noiDung VARCHAR(255) NOT NULL,
    trangThai TINYINT(1) DEFAULT 0,
    ngayTao DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

if ($conn->query($sql) === TRUE) {
    echo "Bảng vieccanlam đã được tạo thành công.\n";
} else {
    echo "Lỗi tạo bảng: " . $conn->error . "\n";
}
?>
