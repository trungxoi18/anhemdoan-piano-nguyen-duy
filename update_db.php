<?php
require 'db.php';
$sql1 = "ALTER TABLE phieunhap ADD COLUMN nguoiGiaoHang VARCHAR(100) AFTER soHoaDonNCC";
$sql2 = "ALTER TABLE phieunhap ADD COLUMN ngayHoaDon DATE AFTER nguoiGiaoHang";
$conn->query($sql1);
$conn->query($sql2);
echo "Done";
?>
