<?php
require 'db.php';

$sql1 = "CREATE TABLE IF NOT EXISTS chinhsachbaohanh (
    maCS INT AUTO_INCREMENT PRIMARY KEY,
    tenChinhSach VARCHAR(255) NOT NULL,
    noiDung TEXT NOT NULL,
    ngayTao DATETIME DEFAULT CURRENT_TIMESTAMP,
    ngayCapNhat DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

$sql2 = "CREATE TABLE IF NOT EXISTS chuongtrinhkhuyenmai (
    maKM INT AUTO_INCREMENT PRIMARY KEY,
    tenChuongTrinh VARCHAR(255) NOT NULL,
    moTa TEXT,
    phanTramGiam INT DEFAULT 0,
    ngayBatDau DATE NOT NULL,
    ngayKetThuc DATE NOT NULL,
    trangThai ENUM('Đang diễn ra', 'Sắp tới', 'Đã kết thúc') DEFAULT 'Đang diễn ra',
    ngayTao DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

if ($conn->query($sql1) === TRUE) {
    echo "Bảng chinhsachbaohanh đã được tạo thành công.\n";
} else {
    echo "Lỗi tạo bảng chinhsachbaohanh: " . $conn->error . "\n";
}

if ($conn->query($sql2) === TRUE) {
    echo "Bảng chuongtrinhkhuyenmai đã được tạo thành công.\n";
} else {
    echo "Lỗi tạo bảng chuongtrinhkhuyenmai: " . $conn->error . "\n";
}
?>
