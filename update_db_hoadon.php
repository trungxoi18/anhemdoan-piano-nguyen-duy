<?php
require 'db.php';

// Add new columns to hoadon
$sql1 = "ALTER TABLE hoadon ADD COLUMN tenDonVi VARCHAR(255) NULL AFTER maKhachHang";
$sql2 = "ALTER TABLE hoadon ADD COLUMN maSoThue VARCHAR(50) NULL AFTER tenDonVi";
$sql3 = "ALTER TABLE hoadon ADD COLUMN thueGTGT INT DEFAULT 0 AFTER hinhThucThanhToan";

$success = true;

if ($conn->query($sql1) === TRUE) {
    echo "Added tenDonVi successfully.\n";
} else {
    echo "Error adding tenDonVi: " . $conn->error . "\n";
    $success = false;
}

if ($conn->query($sql2) === TRUE) {
    echo "Added maSoThue successfully.\n";
} else {
    echo "Error adding maSoThue: " . $conn->error . "\n";
    $success = false;
}

if ($conn->query($sql3) === TRUE) {
    echo "Added thueGTGT successfully.\n";
} else {
    echo "Error adding thueGTGT: " . $conn->error . "\n";
    $success = false;
}

if ($success) {
    echo "Database updated successfully!\n";
}
?>
