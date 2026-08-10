<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'db.php';
require_once 'functions.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role_id'] != 1) {
    header("Location: index.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $type = $_POST['type'] ?? '';
    $id = intval($_POST['id'] ?? 0);
    $action = $_POST['action'] ?? '';

    if (!$id || !in_array($type, ['nhap', 'xuat', 'dieuchuyen']) || !in_array($action, ['approve', 'reject'])) {
        $_SESSION['flash_error'] = "Yêu cầu không hợp lệ.";
        header("Location: duyet_phieu.php");
        exit();
    }

    $conn->begin_transaction();
    try {
        if ($type == 'nhap') {
            // Kiểm tra trạng thái hiện tại
            $res = $conn->query("SELECT trangThai FROM phieunhap WHERE maPhieuNhap = $id");
            if ($res->num_rows == 0) throw new Exception("Không tìm thấy Phiếu Nhập!");
            $pt = $res->fetch_assoc()['trangThai'];
            if ($pt != 'Chờ duyệt') throw new Exception("Phiếu Nhập này không ở trạng thái Chờ duyệt.");

            if ($action == 'approve') {
                $conn->query("UPDATE phieunhap SET trangThai = 'Hoàn thành' WHERE maPhieuNhap = $id");
                // Cập nhật serial thành 'Trong kho'
                $conn->query("UPDATE danserial ds JOIN chitietphieunhap ct ON ds.maSerial = ct.maSerial SET ds.trangThai = 'Trong kho' WHERE ct.maPhieuNhap = $id");
                writeLog($conn, 'DUYỆT PHIẾU', "Admin đã DUYỆT Phiếu nhập #$id");
                $_SESSION['flash_success'] = "Đã DUYỆT Phiếu Nhập #$id thành công. Tồn kho đã được cập nhật.";
            } else {
                $conn->query("UPDATE phieunhap SET trangThai = 'Đã hủy' WHERE maPhieuNhap = $id");
                // Cập nhật serial thành 'Đã hủy'
                $conn->query("UPDATE danserial ds JOIN chitietphieunhap ct ON ds.maSerial = ct.maSerial SET ds.trangThai = 'Đã hủy' WHERE ct.maPhieuNhap = $id");
                writeLog($conn, 'DUYỆT PHIẾU', "Admin đã TỪ CHỐI Phiếu nhập #$id");
                $_SESSION['flash_success'] = "Đã TỪ CHỐI Phiếu Nhập #$id.";
            }
        } elseif ($type == 'xuat') {
            // Kiểm tra trạng thái hiện tại
            $res = $conn->query("SELECT trangThai, maHoaDon FROM phieuxuat WHERE maPhieuXuat = $id");
            if ($res->num_rows == 0) throw new Exception("Không tìm thấy Phiếu Xuất!");
            $row = $res->fetch_assoc();
            $pt = $row['trangThai'];
            $maHoaDon = $row['maHoaDon'];
            if ($pt != 'Chờ duyệt') throw new Exception("Phiếu Xuất này không ở trạng thái Chờ duyệt.");

            if ($action == 'approve') {
                $conn->query("UPDATE phieuxuat SET trangThai = 'Hoàn thành' WHERE maPhieuXuat = $id");
                // Cập nhật serial thành 'Đã bán'
                $conn->query("UPDATE danserial ds JOIN chitietphieuxuat ct ON ds.maSerial = ct.maSerial SET ds.trangThai = 'Đã bán' WHERE ct.maPhieuXuat = $id");
                if ($maHoaDon) {
                    $conn->query("UPDATE hoadon SET trangThai = 'Hoàn thành' WHERE maHoaDon = $maHoaDon");
                }
                writeLog($conn, 'DUYỆT PHIẾU', "Admin đã DUYỆT Phiếu xuất #$id");
                $_SESSION['flash_success'] = "Đã DUYỆT Phiếu Xuất #$id thành công. Hàng đã được trừ khỏi kho.";
            } else {
                $conn->query("UPDATE phieuxuat SET trangThai = 'Đã hủy' WHERE maPhieuXuat = $id");
                // Cập nhật serial về lại 'Trong kho'
                $conn->query("UPDATE danserial ds JOIN chitietphieuxuat ct ON ds.maSerial = ct.maSerial SET ds.trangThai = 'Trong kho' WHERE ct.maPhieuXuat = $id");
                if ($maHoaDon) {
                    $conn->query("UPDATE hoadon SET trangThai = 'Chờ giao' WHERE maHoaDon = $maHoaDon");
                }
                writeLog($conn, 'DUYỆT PHIẾU', "Admin đã TỪ CHỐI Phiếu xuất #$id");
                $_SESSION['flash_success'] = "Đã TỪ CHỐI Phiếu Xuất #$id. Trạng thái kho đã được hoàn lại.";
            }
        } elseif ($type == 'dieuchuyen') {
            // Kiểm tra trạng thái hiện tại
            $res = $conn->query("SELECT trangThai, maKhoNhap FROM phieudieuchuyen WHERE maPhieuDC = $id");
            if ($res->num_rows == 0) throw new Exception("Không tìm thấy Phiếu Điều Chuyển!");
            $row = $res->fetch_assoc();
            $pt = $row['trangThai'];
            $maKhoNhap = $row['maKhoNhap'];
            if ($pt != 'Chờ duyệt') throw new Exception("Phiếu Điều Chuyển này không ở trạng thái Chờ duyệt.");

            if ($action == 'approve') {
                $conn->query("UPDATE phieudieuchuyen SET trangThai = 'Hoàn thành' WHERE maPhieuDC = $id");
                // Cập nhật serial: đổi maKho và set trangThai = 'Trong kho'
                $conn->query("UPDATE danserial ds JOIN chitietdieuchuyen ct ON ds.maSerial = ct.maSerial SET ds.maKho = $maKhoNhap, ds.trangThai = 'Trong kho' WHERE ct.maPhieuDC = $id");
                writeLog($conn, 'DUYỆT PHIẾU', "Admin đã DUYỆT Phiếu điều chuyển #$id");
                $_SESSION['flash_success'] = "Đã DUYỆT Phiếu Điều Chuyển #$id thành công. Hàng đã chuyển kho.";
            } else {
                $conn->query("UPDATE phieudieuchuyen SET trangThai = 'Đã hủy' WHERE maPhieuDC = $id");
                // Cập nhật serial về lại 'Trong kho' (kho xuất cũ)
                $conn->query("UPDATE danserial ds JOIN chitietdieuchuyen ct ON ds.maSerial = ct.maSerial SET ds.trangThai = 'Trong kho' WHERE ct.maPhieuDC = $id");
                writeLog($conn, 'DUYỆT PHIẾU', "Admin đã TỪ CHỐI Phiếu điều chuyển #$id");
                $_SESSION['flash_success'] = "Đã TỪ CHỐI Phiếu Điều Chuyển #$id. Trạng thái kho đã được hoàn lại.";
            }
        }
        $conn->commit();
    } catch (Exception $e) {
        $conn->rollback();
        $_SESSION['flash_error'] = $e->getMessage();
    }
    
    header("Location: duyet_phieu.php");
    exit();
} else {
    header("Location: duyet_phieu.php");
    exit();
}
?>
