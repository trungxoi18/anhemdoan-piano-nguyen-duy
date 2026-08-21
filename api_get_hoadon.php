<?php
session_start();
require_once 'db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$id = intval($_GET['id'] ?? 0);
if (!$id) {
    echo json_encode(['success' => false]);
    exit();
}

// Lấy thông tin hóa đơn và khách hàng
$sql = "SELECT hd.maHoaDon, hd.ngayLap, hd.tenDonVi, kh.hoTen 
        FROM hoadon hd 
        LEFT JOIN khachhang kh ON hd.maKhachHang = kh.maKhachHang 
        WHERE hd.maHoaDon = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);
$stmt->execute();
$res = $stmt->get_result();

if ($res->num_rows == 0) {
    echo json_encode(['success' => false]);
    exit();
}

$row = $res->fetch_assoc();

// Lấy mã serial thuộc hóa đơn này
$serials = [];
$sql_serials = "SELECT ds.soSerial FROM chitiethoadon ct 
                JOIN danserial ds ON ct.maSerial = ds.maSerial 
                WHERE ct.maHoaDon = ?";
$stmt_serials = $conn->prepare($sql_serials);
$stmt_serials->bind_param("i", $id);
$stmt_serials->execute();
$res_serials = $stmt_serials->get_result();
while ($s = $res_serials->fetch_assoc()) {
    $serials[] = $s['soSerial'];
}

echo json_encode([
    'success' => true,
    'khachHang' => $row['hoTen'],
    'donVi' => $row['tenDonVi'],
    'ngayLap' => date('Y-m-d', strtotime($row['ngayLap'])),
    'serials' => $serials
]);
