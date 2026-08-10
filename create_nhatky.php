<?php
require 'db.php';

$sql = "CREATE TABLE IF NOT EXISTS NhatKyHeThong (
    maNhatKy INT AUTO_INCREMENT PRIMARY KEY,
    maTaiKhoan INT NOT NULL,
    loaiHanhDong VARCHAR(100) NOT NULL,
    chiTiet TEXT NOT NULL,
    ngayTao DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (maTaiKhoan) REFERENCES TaiKhoan(maTaiKhoan) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";

if ($conn->query($sql) === TRUE) {
    echo "Bảng NhatKyHeThong đã được tạo thành công.";
} else {
    echo "Lỗi: " . $conn->error;
}
?>
