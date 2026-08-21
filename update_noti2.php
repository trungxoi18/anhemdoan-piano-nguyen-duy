<?php
require 'db.php';
$conn->query("UPDATE ThongBao SET link = CONCAT('hoadon_action.php?id=', TRIM(SUBSTRING_INDEX(noiDung, '#', -1))) WHERE noiDung LIKE 'Đã lập hóa đơn thành công #%' AND link = 'hoadon_moi.php'");
echo 'Affected: ' . $conn->affected_rows;
?>
