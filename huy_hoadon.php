<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'db.php';
require_once 'functions.php';

// Chỉ Admin và Quản lý (Sales có thể tùy chỉnh) mới được phép hủy
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role_id'], [1, 2])) {
    $_SESSION['flash_error'] = "Bạn không có quyền thực hiện thao tác này!";
    header("Location: index.php");
    exit();
}

$maHoaDon = intval($_GET['id'] ?? 0);
if (!$maHoaDon) {
    die("Mã hóa đơn không hợp lệ.");
}

$conn->begin_transaction();
try {
    // 1. Lock Hóa đơn để kiểm tra trạng thái
    $st_hd = $conn->prepare("SELECT trangThai FROM hoadon WHERE maHoaDon = ? FOR UPDATE");
    $st_hd->bind_param("i", $maHoaDon);
    $st_hd->execute();
    $res_hd = $st_hd->get_result();
    
    if ($res_hd->num_rows == 0) {
        throw new Exception("Hóa đơn không tồn tại.");
    }
    $hd = $res_hd->fetch_assoc();
    if ($hd['trangThai'] === 'Đã hủy') {
        throw new Exception("Hóa đơn này đã được hủy trước đó!");
    }

    // 2. Cập nhật Hóa đơn thành Đã hủy
    $conn->query("UPDATE hoadon SET trangThai = 'Đã hủy' WHERE maHoaDon = $maHoaDon");

    // 3. Nếu có Phiếu xuất liên kết với Hóa đơn này, hủy Phiếu xuất luôn
    $st_px = $conn->prepare("SELECT maPhieuXuat, trangThai FROM phieuxuat WHERE maHoaDon = ? FOR UPDATE");
    $st_px->bind_param("i", $maHoaDon);
    $st_px->execute();
    $res_px = $st_px->get_result();
    while ($px = $res_px->fetch_assoc()) {
        if ($px['trangThai'] !== 'Đã hủy') {
            $maPhieuXuat = $px['maPhieuXuat'];
            $conn->query("UPDATE phieuxuat SET trangThai = 'Đã hủy' WHERE maPhieuXuat = $maPhieuXuat");
            writeLog($conn, 'HỦY PHIẾU XUẤT', "Tự động hủy phiếu xuất #$maPhieuXuat do hóa đơn #$maHoaDon bị hủy.");
        }
    }

    // 4. Hoàn trả trạng thái các cây đàn về 'Trong kho'
    $st_ct = $conn->prepare("SELECT maSerial FROM chitiethoadon WHERE maHoaDon = ?");
    $st_ct->bind_param("i", $maHoaDon);
    $st_ct->execute();
    $res_ct = $st_ct->get_result();
    
    $serials_hoan_tra = [];
    while ($ct = $res_ct->fetch_assoc()) {
        $maSerial = $ct['maSerial'];
        // Lock dòng Serial
        $conn->query("SELECT maSerial FROM danserial WHERE maSerial = $maSerial FOR UPDATE");
        // Update
        $conn->query("UPDATE danserial SET trangThai = 'Trong kho' WHERE maSerial = $maSerial");
        $serials_hoan_tra[] = $maSerial;
    }

    // Ghi log
    writeLog($conn, 'HỦY HÓA ĐƠN', "Người dùng {$_SESSION['username']} đã hủy Hóa đơn #$maHoaDon. Đã hoàn trả " . count($serials_hoan_tra) . " sản phẩm về kho.");

    $conn->commit();
    $_SESSION['flash_success'] = "Đã hủy Hóa đơn #$maHoaDon và hoàn trả sản phẩm về kho thành công!";
    header("Location: hoadon_action.php?id=$maHoaDon");
    exit();

} catch (Exception $e) {
    $conn->rollback();
    $_SESSION['flash_error'] = "Lỗi khi hủy hóa đơn: " . $e->getMessage();
    header("Location: hoadon_action.php?id=$maHoaDon");
    exit();
}
?>
