<?php
require 'db.php';

// 1. Cập nhật ENUM của danserial nếu chưa có 'Đã xuất hãng'
$res = $conn->query("SHOW COLUMNS FROM danserial LIKE 'trangThai'");
$row = $res->fetch_assoc();
$type = $row['Type'];
if (strpos($type, "'Đã xuất hãng'") === false) {
    $new_enum = "ENUM('Trong kho','Đã bán','Đang bảo hành','Lỗi/Khấu hao','Chờ nhập','Chờ xuất','Đã hủy','Đang điều chuyển','Đã xuất hãng')";
    $conn->query("ALTER TABLE danserial MODIFY COLUMN trangThai $new_enum");
    echo "Đã cập nhật ENUM cho danserial.trangThai<br>\n";
} else {
    echo "ENUM danserial.trangThai đã chứa 'Đã xuất hãng'<br>\n";
}

// 2. Tạo bảng phieubaotri
$sql_create = "CREATE TABLE IF NOT EXISTS phieubaotri (
    maPhieuBT INT AUTO_INCREMENT PRIMARY KEY,
    maHoaDon INT NOT NULL,
    maKhachHang INT NOT NULL,
    maSerial INT NOT NULL,
    maNhanVienLap INT NOT NULL,
    ngayTiepNhan DATETIME NOT NULL,
    moTaLoi TEXT,
    maHang INT, 
    trangThai ENUM('Chờ duyệt tiếp nhận', 'Đã tiếp nhận', 'Chờ duyệt nhập kho', 'Đã nhập kho', 'Chờ duyệt xuất hãng', 'Đã xuất hãng', 'Hoàn thành', 'Đã hủy') NOT NULL DEFAULT 'Chờ duyệt tiếp nhận',
    ngayCapNhat DATETIME
)";
if ($conn->query($sql_create) === TRUE) {
    echo "Tạo bảng phieubaotri thành công!<br>\n";
} else {
    echo "Lỗi tạo bảng phieubaotri: " . $conn->error . "<br>\n";
}

// 3. Thêm cột maKho nếu chưa có
$res_kho = $conn->query("SHOW COLUMNS FROM phieubaotri LIKE 'maKho'");
if ($res_kho->num_rows == 0) {
    $conn->query("ALTER TABLE phieubaotri ADD COLUMN maKho INT NULL AFTER maHang");
    echo "Đã thêm cột maKho vào bảng phieubaotri<br>\n";
} else {
    echo "Cột maKho đã tồn tại trong phieubaotri<br>\n";
}
?>
