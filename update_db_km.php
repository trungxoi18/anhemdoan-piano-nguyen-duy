<?php
require 'db.php';
$sql = "ALTER TABLE hoadon ADD COLUMN maKM INT NULL AFTER maKhachHang";
if ($conn->query($sql) === TRUE) {
    echo "Thêm cột maKM thành công.";
} else {
    echo "Lỗi: " . $conn->error;
}
?>
