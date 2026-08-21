<?php
require 'db.php';

$sql = "ALTER TABLE MauDan 
        ADD COLUMN xuatXu VARCHAR(100) DEFAULT NULL,
        ADD COLUMN chatLieu VARCHAR(255) DEFAULT NULL,
        ADD COLUMN kichThuoc VARCHAR(100) DEFAULT NULL,
        ADD COLUMN trongLuong VARCHAR(50) DEFAULT NULL,
        ADD COLUMN mauSac VARCHAR(100) DEFAULT NULL,
        ADD COLUMN baoHanh VARCHAR(50) DEFAULT '5 năm'";

if ($conn->query($sql) === TRUE) {
    echo "Thêm các cột thành công vào bảng MauDan.";
} else {
    // Nếu lỗi có thể do cột đã tồn tại
    echo "Lỗi hoặc cột đã tồn tại: " . $conn->error;
}
?>
