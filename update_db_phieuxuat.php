<?php
require 'db.php';
$conn->query("ALTER TABLE phieuxuat ADD COLUMN nguoiNhanHang VARCHAR(100) AFTER maHoaDon;");
$conn->query("ALTER TABLE phieuxuat ADD COLUMN soChungTu VARCHAR(100) AFTER nguoiNhanHang;");
$conn->query("ALTER TABLE phieuxuat ADD COLUMN ngayChungTu DATE AFTER soChungTu;");
$conn->query("ALTER TABLE phieuxuat ADD COLUMN donViNhan VARCHAR(200) AFTER ngayChungTu;");
echo "Done";
?>
