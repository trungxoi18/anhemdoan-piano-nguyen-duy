<?php
// Script tự động thêm cột anhDaiDien nếu chưa có
require 'db.php';

$result = $conn->query("SHOW COLUMNS FROM NhanVien LIKE 'anhDaiDien'");
if ($result->num_rows == 0) {
    $conn->query("ALTER TABLE NhanVien ADD COLUMN anhDaiDien VARCHAR(255) DEFAULT NULL AFTER emailNV");
    echo "Đã thêm cột anhDaiDien vào bảng NhanVien.\n";
} else {
    echo "Cột anhDaiDien đã tồn tại.\n";
}
