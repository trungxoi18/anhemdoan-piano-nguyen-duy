<?php
// Kiểm tra session để tránh lỗi Notice
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'db.php';

// ==========================================
// Hàm chuyển số tiền sang chữ tiếng Việt
// ==========================================
$GLOBALS['_donVi'] = ['', 'một', 'hai', 'ba', 'bốn', 'năm', 'sáu', 'bảy', 'tám', 'chín'];

function _doc3chu($n) {
    $donVi = $GLOBALS['_donVi'];
    $tram = intdiv($n, 100);
    $chuc = intdiv($n % 100, 10);
    $donvi = $n % 10;
    $s = '';
    if ($tram > 0) {
        $s .= $donVi[$tram] . ' trăm';
        if ($chuc == 0 && $donvi > 0) $s .= ' linh';
    }
    if ($chuc > 1) {
        $s .= ($s ? ' ' : '') . $donVi[$chuc] . ' mươi';
        if ($donvi == 5) $s .= ' lăm';
        elseif ($donvi > 0) $s .= ' ' . $donVi[$donvi];
    } elseif ($chuc == 1) {
        $s .= ($s ? ' ' : '') . 'mười';
        if ($donvi == 5) $s .= ' lăm';
        elseif ($donvi > 0) $s .= ' ' . $donVi[$donvi];
    } elseif ($donvi > 0) {
        $s .= ($s ? ' ' : '') . $donVi[$donvi];
    }
    return trim($s);
}

function soTienBangChu($so) {
    $so = intval($so);
    if ($so == 0) return 'Không đồng';

    $parts = [];
    $tySo    = intdiv($so, 1000000000);
    $trieuSo = intdiv($so % 1000000000, 1000000);
    $nghinSo = intdiv($so % 1000000, 1000);
    $tramSo  = $so % 1000;

    if ($tySo    > 0) $parts[] = _doc3chu($tySo)    . ' tỷ';
    if ($trieuSo > 0) $parts[] = _doc3chu($trieuSo) . ' triệu';
    if ($nghinSo > 0) $parts[] = _doc3chu($nghinSo) . ' nghìn';
    if ($tramSo  > 0) $parts[] = _doc3chu($tramSo);

    $result = trim(implode(' ', $parts));
    $result = mb_strtoupper(mb_substr($result, 0, 1)) . mb_substr($result, 1);
    return $result . ' đồng';
}

// Kiểm tra quyền
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$type = $_GET['type'] ?? '';
$id = intval($_GET['id'] ?? 0);

if (!$type || (!$id && $type !== 'baocao_nhapxuatton')) {
    echo "Dữ liệu không hợp lệ!";
    exit();
}

$title = "Tài Liệu";
$data = [];
$details = [];

// ==========================================
// 1. LẤY DỮ LIỆU THEO TỪNG LOẠI
// ==========================================
if ($type == 'hoadon') {
    $title = "HÓA ĐƠN BÁN HÀNG";
    // Lấy thông tin hóa đơn
    $sql_hd = "SELECT hd.*, kh.hoTen as tenKH, kh.soDienThoai as sdtKH, kh.diaChi as diaChiKH, nv.hoTen as tenNV, km.phanTramGiam, km.tenChuongTrinh 
               FROM hoadon hd 
               LEFT JOIN khachhang kh ON hd.maKhachHang = kh.maKhachHang 
               LEFT JOIN nhanvien nv ON hd.maNhanVien = nv.maNhanVien 
               LEFT JOIN chuongtrinhkhuyenmai km ON hd.maKM = km.maKM 
               WHERE hd.maHoaDon = ?";
    $stmt = $conn->prepare($sql_hd);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($res->num_rows == 0) { die("Không tìm thấy Hóa Đơn!"); }
    $data = $res->fetch_assoc();

    // Lấy chi tiết
    $sql_ct = "SELECT ct.*, ds.soSerial, md.tenMau, md.baoHanh, hd_hang.tenHang 
               FROM chitiethoadon ct 
               JOIN danserial ds ON ct.maSerial = ds.maSerial 
               JOIN maudan md ON ds.maMau = md.maMau 
               JOIN hangdan hd_hang ON md.maHang = hd_hang.maHang 
               WHERE ct.maHoaDon = ?";
    $stmt_ct = $conn->prepare($sql_ct);
    $stmt_ct->bind_param("i", $id);
    $stmt_ct->execute();
    $res_ct = $stmt_ct->get_result();
    while($row = $res_ct->fetch_assoc()){ $details[] = $row; }

} elseif ($type == 'baocao_nhapxuatton') {
    $title = "BÁO CÁO NHẬP XUẤT TỒN KHO";
    
    $start_of_month = date('Y-m-01');
    $end_of_month = date('Y-m-t');

    $startDate = isset($_GET['tu_ngay']) ? trim($_GET['tu_ngay']) : $start_of_month;
    $endDate = isset($_GET['den_ngay']) ? trim($_GET['den_ngay']) : $end_of_month;
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $startDate)) $startDate = $start_of_month;
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $endDate)) $endDate = $end_of_month;
    if ($startDate > $endDate) {
        $tmp = $startDate;
        $startDate = $endDate;
        $endDate = $tmp;
    }

    $startDate = $conn->real_escape_string($startDate);
    $endDate = $conn->real_escape_string($endDate);

    $maKhoFilter = isset($_GET['ma_kho']) ? intval($_GET['ma_kho']) : 0;
    $search = isset($_GET['search']) ? trim($_GET['search']) : '';

    $tenKhoBaoCao = "Toàn bộ hệ thống kho";
    $diaChiKhoBaoCao = "Số 53 đường Lạch Tray, Quận Ngô Quyền, TP Hải Phòng";
    if ($maKhoFilter > 0) {
        $st_k = $conn->query("SELECT tenKho, diaChi FROM kho WHERE maKho = $maKhoFilter");
        if ($st_k && $k_info = $st_k->fetch_assoc()) {
            $tenKhoBaoCao = $k_info['tenKho'];
            $diaChiKhoBaoCao = $k_info['diaChi'] ?: $diaChiKhoBaoCao;
        }
    }

    // 1. Lấy danh sách Mẫu Đàn
    $sql_mau = "SELECT md.maMau, md.tenMau, loai.tenLoai, hang.tenHang,
            (SELECT MAX(giaBan) FROM danserial WHERE maMau = md.maMau) as giaBan
            FROM maudan md 
            LEFT JOIN loaidan loai ON md.maLoai = loai.maLoai 
            LEFT JOIN hangdan hang ON md.maHang = hang.maHang
            WHERE 1=1";
    if (!empty($search)) {
        $sql_mau .= " AND md.tenMau LIKE '%" . $conn->real_escape_string($search) . "%'";
    }
    $sql_mau .= " ORDER BY md.tenMau ASC";
    $resMau = $conn->query($sql_mau);

    $report = [];
    while ($mau = $resMau->fetch_assoc()) {
        $maMau = $mau['maMau'];
        $report[$maMau] = [
            'maMau' => $maMau,
            'tenMau' => $mau['tenMau'],
            'tenLoai' => $mau['tenLoai'],
            'tenHang' => $mau['tenHang'],
            'giaBan' => (float)($mau['giaBan'] ?? 0),
            'tonHienTai' => 0,
            'xuatTrongKy' => 0,
            'nhapTrongKy' => 0,
            'xuatSauKy' => 0,
            'nhapSauKy' => 0
        ];
    }

    $khoFilter = $maKhoFilter > 0 ? "AND ds.maKho = $maKhoFilter" : "";
    $khoXuatFilter = $maKhoFilter > 0 ? "AND dc.maKhoXuat = $maKhoFilter" : "";
    $khoNhapFilter = $maKhoFilter > 0 ? "AND dc.maKhoNhap = $maKhoFilter" : "";
    $pxKhoFilter = $maKhoFilter > 0 ? "AND px.maKho = $maKhoFilter" : "";
    $pnKhoFilter = $maKhoFilter > 0 ? "AND pn.maKho = $maKhoFilter" : "";

    // 1. Tồn hiện tại
    $res = $conn->query("SELECT maMau, COUNT(*) as sl FROM danserial ds WHERE trangThai IN ('Trong kho', 'Chờ xuất', 'Chờ giao', 'Đang điều chuyển') $khoFilter GROUP BY maMau");
    while ($r = $res->fetch_assoc()) if (isset($report[$r['maMau']])) $report[$r['maMau']]['tonHienTai'] = (int)$r['sl'];

    // 2. Xuất trong kỳ
    $res = $conn->query("SELECT ds.maMau, COUNT(ct.maSerial) as sl FROM chitietphieuxuat ct JOIN phieuxuat px ON ct.maPhieuXuat = px.maPhieuXuat JOIN danserial ds ON ct.maSerial = ds.maSerial WHERE px.trangThai = 'Hoàn thành' AND DATE(px.ngayXuat) BETWEEN '$startDate' AND '$endDate' $pxKhoFilter GROUP BY ds.maMau");
    while ($r = $res->fetch_assoc()) if (isset($report[$r['maMau']])) $report[$r['maMau']]['xuatTrongKy'] += (int)$r['sl'];

    $res = $conn->query("SELECT ds.maMau, COUNT(ct.maSerial) as sl FROM chitietdieuchuyen ct JOIN phieudieuchuyen dc ON ct.maPhieuDC = dc.maPhieuDC JOIN danserial ds ON ct.maSerial = ds.maSerial WHERE dc.trangThai = 'Hoàn thành' AND DATE(dc.ngayTao) BETWEEN '$startDate' AND '$endDate' $khoXuatFilter GROUP BY ds.maMau");
    while ($r = $res->fetch_assoc()) if (isset($report[$r['maMau']])) $report[$r['maMau']]['xuatTrongKy'] += (int)$r['sl'];

    // 3. Nhập trong kỳ
    $res = $conn->query("SELECT ds.maMau, COUNT(ct.maSerial) as sl FROM chitietphieunhap ct JOIN phieunhap pn ON ct.maPhieuNhap = pn.maPhieuNhap JOIN danserial ds ON ct.maSerial = ds.maSerial WHERE pn.trangThai = 'Hoàn thành' AND DATE(pn.ngayNhap) BETWEEN '$startDate' AND '$endDate' $pnKhoFilter GROUP BY ds.maMau");
    while ($r = $res->fetch_assoc()) if (isset($report[$r['maMau']])) $report[$r['maMau']]['nhapTrongKy'] += (int)$r['sl'];

    $res = $conn->query("SELECT ds.maMau, COUNT(ct.maSerial) as sl FROM chitietdieuchuyen ct JOIN phieudieuchuyen dc ON ct.maPhieuDC = dc.maPhieuDC JOIN danserial ds ON ct.maSerial = ds.maSerial WHERE dc.trangThai = 'Hoàn thành' AND DATE(dc.ngayTao) BETWEEN '$startDate' AND '$endDate' $khoNhapFilter GROUP BY ds.maMau");
    while ($r = $res->fetch_assoc()) if (isset($report[$r['maMau']])) $report[$r['maMau']]['nhapTrongKy'] += (int)$r['sl'];

    // 4. Xuất sau kỳ
    $res = $conn->query("SELECT ds.maMau, COUNT(ct.maSerial) as sl FROM chitietphieuxuat ct JOIN phieuxuat px ON ct.maPhieuXuat = px.maPhieuXuat JOIN danserial ds ON ct.maSerial = ds.maSerial WHERE px.trangThai = 'Hoàn thành' AND DATE(px.ngayXuat) > '$endDate' $pxKhoFilter GROUP BY ds.maMau");
    while ($r = $res->fetch_assoc()) if (isset($report[$r['maMau']])) $report[$r['maMau']]['xuatSauKy'] += (int)$r['sl'];

    $res = $conn->query("SELECT ds.maMau, COUNT(ct.maSerial) as sl FROM chitietdieuchuyen ct JOIN phieudieuchuyen dc ON ct.maPhieuDC = dc.maPhieuDC JOIN danserial ds ON ct.maSerial = ds.maSerial WHERE dc.trangThai = 'Hoàn thành' AND DATE(dc.ngayTao) > '$endDate' $khoXuatFilter GROUP BY ds.maMau");
    while ($r = $res->fetch_assoc()) if (isset($report[$r['maMau']])) $report[$r['maMau']]['xuatSauKy'] += (int)$r['sl'];

    // 5. Nhập sau kỳ
    $res = $conn->query("SELECT ds.maMau, COUNT(ct.maSerial) as sl FROM chitietphieunhap ct JOIN phieunhap pn ON ct.maPhieuNhap = pn.maPhieuNhap JOIN danserial ds ON ct.maSerial = ds.maSerial WHERE pn.trangThai = 'Hoàn thành' AND DATE(pn.ngayNhap) > '$endDate' $pnKhoFilter GROUP BY ds.maMau");
    while ($r = $res->fetch_assoc()) if (isset($report[$r['maMau']])) $report[$r['maMau']]['nhapSauKy'] += (int)$r['sl'];

    $res = $conn->query("SELECT ds.maMau, COUNT(ct.maSerial) as sl FROM chitietdieuchuyen ct JOIN phieudieuchuyen dc ON ct.maPhieuDC = dc.maPhieuDC JOIN danserial ds ON ct.maSerial = ds.maSerial WHERE dc.trangThai = 'Hoàn thành' AND DATE(dc.ngayTao) > '$endDate' $khoNhapFilter GROUP BY ds.maMau");
    while ($r = $res->fetch_assoc()) if (isset($report[$r['maMau']])) $report[$r['maMau']]['nhapSauKy'] += (int)$r['sl'];

    // Tổng hợp kết quả
    $final_report = [];
    $tongTonDau = $tongNhap = $tongXuat = $tongTonCuoi = 0;
    $tongGiaTriTon = 0;

    foreach ($report as $r) {
        $tonCuoiKy = $r['tonHienTai'] + $r['xuatSauKy'] - $r['nhapSauKy'];
        $tonDauKy = $tonCuoiKy + $r['xuatTrongKy'] - $r['nhapTrongKy'];
        $giaBan = $r['giaBan'];
        $thanhTienTon = $tonCuoiKy * $giaBan;
        
        if ($tonDauKy > 0 || $r['nhapTrongKy'] > 0 || $r['xuatTrongKy'] > 0 || $tonCuoiKy > 0) {
            $final_report[] = [
                'maMau' => $r['maMau'],
                'tenMau' => $r['tenMau'],
                'tenLoai' => $r['tenLoai'],
                'tenHang' => $r['tenHang'],
                'giaBan' => $giaBan,
                'tonDauKy' => $tonDauKy,
                'nhapTrongKy' => $r['nhapTrongKy'],
                'xuatTrongKy' => $r['xuatTrongKy'],
                'tonCuoiKy' => $tonCuoiKy,
                'thanhTienTon' => $thanhTienTon
            ];
            
            $tongTonDau += $tonDauKy;
            $tongNhap += $r['nhapTrongKy'];
            $tongXuat += $r['xuatTrongKy'];
            $tongTonCuoi += $tonCuoiKy;
            $tongGiaTriTon += $thanhTienTon;
        }
    }

    // Danh sách đàn bảo trì đang lưu kho
    $sql_bt = "SELECT ds.soSerial, md.tenMau, hd.tenHang, k.tenKho, pb.maPhieuBT, kh.hoTen as tenKH, pb.moTaLoi
               FROM danserial ds
               JOIN maudan md ON ds.maMau = md.maMau
               JOIN hangdan hd ON md.maHang = hd.maHang
               LEFT JOIN kho k ON ds.maKho = k.maKho
               LEFT JOIN phieubaotri pb ON ds.maSerial = pb.maSerial AND pb.trangThai = 'Đã nhập kho'
               LEFT JOIN khachhang kh ON pb.maKhachHang = kh.maKhachHang
               WHERE ds.trangThai = 'Đang bảo hành' $khoFilter
               ORDER BY ds.soSerial ASC";
    $res_bt = $conn->query($sql_bt);
    $danhSachBaoTri = [];
    if ($res_bt) {
        while($bt_row = $res_bt->fetch_assoc()) {
            $danhSachBaoTri[] = $bt_row;
        }
    }
} elseif ($type == 'phieuxuat') {
    $title = "PHIẾU XUẤT KHO";
    // Lấy thông tin phiếu xuất
    $sql_px = "SELECT px.*, nv.hoTen as tenNV 
               FROM phieuxuat px 
               LEFT JOIN nhanvien nv ON px.maNhanVien = nv.maNhanVien 
               WHERE px.maPhieuXuat = ?";
    $stmt = $conn->prepare($sql_px);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($res->num_rows == 0) { die("Không tìm thấy Phiếu Xuất!"); }
    $data = $res->fetch_assoc();

    // Lấy chi tiết
    $sql_ct = "SELECT ct.*, ds.soSerial, ds.giaBan, ds.maKho, md.tenMau, hd_hang.tenHang 
               FROM chitietphieuxuat ct 
               JOIN danserial ds ON ct.maSerial = ds.maSerial 
               JOIN maudan md ON ds.maMau = md.maMau 
               JOIN hangdan hd_hang ON md.maHang = hd_hang.maHang 
               WHERE ct.maPhieuXuat = ?";
    $stmt_ct = $conn->prepare($sql_ct);
    $stmt_ct->bind_param("i", $id);
    $stmt_ct->execute();
    $res_ct = $stmt_ct->get_result();
    while($row = $res_ct->fetch_assoc()){ $details[] = $row; }
    
    // Lấy thông tin Kho từ sản phẩm đầu tiên
    $tenKho = '';
    $diaChiKho = '';
    if (count($details) > 0 && !empty($details[0]['maKho'])) {
        $maKho_first = intval($details[0]['maKho']);
        $st_kho = $conn->query("SELECT tenKho, diaChi FROM kho WHERE maKho = $maKho_first");
        if ($st_kho && $k = $st_kho->fetch_assoc()) {
            $tenKho = $k['tenKho'];
            $diaChiKho = $k['diaChi'];
        }
    }

} elseif ($type == 'phieunhap') {
    $title = "PHIẾU NHẬP KHO";
    // Lấy thông tin phiếu nhập
    $sql_pn = "SELECT pn.*, ncc.tenNCC, ncc.soDienThoai as sdtNCC, ncc.diaChi as diaChiNCC, nv.hoTen as tenNV 
               FROM phieunhap pn 
               LEFT JOIN nhacungcap ncc ON pn.maNCC = ncc.maNCC 
               LEFT JOIN nhanvien nv ON pn.maNhanVien = nv.maNhanVien 
               WHERE pn.maPhieuNhap = ?";
    $stmt = $conn->prepare($sql_pn);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($res->num_rows == 0) { die("Không tìm thấy Phiếu Nhập!"); }
    $data = $res->fetch_assoc();

    // Lấy chi tiết
    $sql_ct = "SELECT ct.*, ds.soSerial, ds.maKho, md.tenMau, hd_hang.tenHang 
               FROM chitietphieunhap ct 
               JOIN danserial ds ON ct.maSerial = ds.maSerial 
               JOIN maudan md ON ds.maMau = md.maMau 
               JOIN hangdan hd_hang ON md.maHang = hd_hang.maHang 
               WHERE ct.maPhieuNhap = ?";
    $stmt_ct = $conn->prepare($sql_ct);
    $stmt_ct->bind_param("i", $id);
    $stmt_ct->execute();
    $res_ct = $stmt_ct->get_result();
    while($row = $res_ct->fetch_assoc()){ $details[] = $row; }
    
    // Get Kho info from the first item
    $tenKho = '';
    $diaChiKho = '';
    if(count($details) > 0 && !empty($details[0]['maKho'])) {
        $maKho_first = intval($details[0]['maKho']);
        $st_kho = $conn->query("SELECT tenKho, diaChi FROM kho WHERE maKho = $maKho_first");
        if($st_kho && $k = $st_kho->fetch_assoc()) {
            $tenKho = $k['tenKho'];
            $diaChiKho = $k['diaChi'];
        }
    }
} elseif (in_array($type, ['baotri_tiepnhan', 'baotri_nhap', 'baotri_xuat'])) {
    if ($type == 'baotri_tiepnhan') $title = "PHIẾU TIẾP NHẬN BẢO TRÍ";
    elseif ($type == 'baotri_nhap') $title = "PHIẾU NHẬP KHO BẢO TRÍ";
    elseif ($type == 'baotri_xuat') $title = "PHIẾU XUẤT HÃNG BẢO TRÍ";

    $sql = "SELECT pb.*, kh.hoTen as tenKH, kh.soDienThoai as sdtKH, kh.diaChi as diaChiKH, 
            ds.soSerial, md.tenMau, hd.tenHang as tenHangSanXuat, nv.hoTen as tenNVLap,
            k.tenKho, k.diaChi as diaChiKho
            FROM phieubaotri pb
            JOIN khachhang kh ON pb.maKhachHang = kh.maKhachHang
            JOIN danserial ds ON pb.maSerial = ds.maSerial
            JOIN maudan md ON ds.maMau = md.maMau
            LEFT JOIN hangdan hd ON pb.maHang = hd.maHang
            LEFT JOIN kho k ON (pb.maKho = k.maKho OR (pb.maKho IS NULL AND ds.maKho = k.maKho))
            JOIN nhanvien nv ON pb.maNhanVienLap = nv.maNhanVien
            WHERE pb.maPhieuBT = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($res->num_rows == 0) { die("Không tìm thấy Phiếu Bảo Trì!"); }
    $data = $res->fetch_assoc();
} else {
    die("Loại tài liệu không hợp lệ!");
}

?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title><?= $title ?> #<?= $id ?></title>
    <!-- Thêm font chữ chuyên nghiệp -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Rounded:opsz,wght,FILL,GRAD@24,400,0,0" rel="stylesheet" />
    <style>
        :root {
            --brand-color: #7c5cfc;
            --text-dark: #1f2937;
            --text-muted: #6b7280;
            --border-color: #e5e7eb;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: #f3f4f6;
            margin: 0;
            padding: 20px;
            color: var(--text-dark);
            -webkit-print-color-adjust: exact; 
            print-color-adjust: exact;
        }

        /* Giao diện trang A4 */
        .page-a4 {
            background: white;
            width: 210mm;
            min-height: 297mm;
            margin: 0 auto;
            padding: 20mm;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            border-radius: 8px;
            position: relative;
            box-sizing: border-box;
        }

        /* Header của trang in */
        .doc-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            border-bottom: 2px solid var(--brand-color);
            padding-bottom: 20px;
            margin-bottom: 30px;
        }
        
        .company-info h1 {
            margin: 0 0 5px 0;
            color: var(--brand-color);
            font-size: 24px;
            font-weight: 800;
        }
        
        .company-info p {
            margin: 2px 0;
            font-size: 13px;
            color: var(--text-muted);
        }

        .doc-meta {
            text-align: right;
        }

        .doc-meta h2 {
            margin: 0 0 10px 0;
            font-size: 22px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        /* Phần Thông Tin Bên Liên Quan */
        .info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 40px;
            margin-bottom: 30px;
        }
        
        .info-box h3 {
            font-size: 14px;
            text-transform: uppercase;
            color: var(--text-muted);
            border-bottom: 1px solid var(--border-color);
            padding-bottom: 8px;
            margin-bottom: 12px;
        }

        .info-box p {
            margin: 5px 0;
            font-size: 14px;
        }
        .info-box strong {
            font-weight: 600;
        }

        /* Bảng Chi Tiết */
        .table-items {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
        }
        
        .table-items th {
            background: var(--brand-color);
            color: white;
            padding: 12px;
            text-align: left;
            font-size: 13px;
            text-transform: uppercase;
        }

        .table-items td {
            padding: 12px;
            border-bottom: 1px solid var(--border-color);
            font-size: 14px;
        }
        
        .table-items tr:last-child td {
            border-bottom: 2px solid var(--border-color);
        }
        
        .table-items th.text-right,
        .table-items td.text-right {
            text-align: right;
        }
        
        /* Tổng tiền */
        .total-box {
            width: 300px;
            margin-left: auto;
            border: 1px solid var(--border-color);
            border-radius: 8px;
            padding: 20px;
            background: #fafafa;
        }
        
        .total-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
            font-size: 14px;
        }

        .total-row.grand-total {
            font-weight: 800;
            font-size: 18px;
            color: var(--brand-color);
            margin-bottom: 0;
            padding-top: 10px;
            border-top: 1px dashed var(--border-color);
        }

        /* Chữ ký */
        .signatures {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
            text-align: center;
            margin-top: 50px;
        }
        .signatures div p {
            font-size: 14px;
            font-weight: 600;
            margin-bottom: 80px;
        }

        /* Toolbar Actions */
        .action-bar {
            text-align: center;
            margin-bottom: 20px;
        }

        .btn {
            background: var(--brand-color);
            color: white;
            border: none;
            padding: 12px 24px;
            font-family: 'Inter', sans-serif;
            font-size: 15px;
            font-weight: 600;
            border-radius: 8px;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: 0.2s;
            box-shadow: 0 4px 12px rgba(124, 92, 252, 0.3);
        }
        
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(124, 92, 252, 0.4);
        }

        .btn-outline {
            background: white;
            color: var(--text-dark);
            border: 1px solid var(--border-color);
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
        }
        .btn-outline:hover {
            background: #f9fafb;
            box-shadow: 0 4px 10px rgba(0,0,0,0.1);
        }

        /* Khi In thì ẩn Toolbar và bỏ shadow/margin của trang A4 */
        @media print {
            body {
                background: white;
                padding: 0;
            }
            .action-bar {
                display: none;
            }
            .page-a4 {
                width: 100%;
                min-height: auto;
                margin: 0;
                padding: 0;
                box-shadow: none;
                border-radius: 0;
            }
            /* Cố định mỗi tài liệu in trên 1 trang */
            @page {
                size: A4;
                margin: 15mm;
            }
        }
    </style>
</head>
<body>

    <div class="action-bar">
        <button onclick="if(window.history.length > 1 && document.referrer) window.history.back(); else window.close();" class="btn btn-outline" style="font-family: inherit; font-size: 15px; cursor: pointer;">
            <span class="material-symbols-rounded">arrow_back</span> Quay lại
        </button>
        <button onclick="window.print()" class="btn">
            <span class="material-symbols-rounded">print</span> In / Xuất PDF
        </button>
        <?php if(isset($_SESSION['flash_success'])): ?>
            <div style="margin-top: 15px; color: #10b981; font-weight: 600; background: #d1fae5; display: inline-block; padding: 10px 20px; border-radius: 8px; border: 1px solid #34d399;">
                <span class="material-symbols-rounded" style="vertical-align: middle; margin-right: 5px;">check_circle</span>
                <?php echo $_SESSION['flash_success']; unset($_SESSION['flash_success']); ?>
            </div>
        <?php endif; ?>
    </div>

    <?php if ($type == 'baocao_nhapxuatton'): ?>
    
    <style>
        @media print {
            @page {
                size: A4 landscape;
                margin: 8mm 10mm;
            }
            body {
                background: #fff !important;
                padding: 0 !important;
            }
            .action-bar { display: none !important; }
            .page-landscape {
                width: 100% !important;
                min-height: auto !important;
                padding: 0 !important;
                box-shadow: none !important;
                border-radius: 0 !important;
            }
        }
        .page-landscape {
            background: white;
            width: 285mm;
            min-height: 195mm;
            margin: 0 auto;
            padding: 12mm 15mm;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            border-radius: 8px;
            position: relative;
            box-sizing: border-box;
            color: #000;
            font-family: "Times New Roman", Times, serif;
        }
        .bc-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 16px;
        }
        .bc-left { width: 44%; }
        .bc-left p { margin: 2px 0; font-size: 13px; line-height: 1.35; }
        .bc-center { width: 36%; text-align: center; }
        .bc-center h1 {
            font-size: 19px;
            font-weight: bold;
            text-transform: uppercase;
            margin: 0 0 4px 0;
            color: #000;
            letter-spacing: 0.5px;
        }
        .bc-center p { margin: 2px 0; font-size: 13px; }
        .bc-right { width: 20%; text-align: center; font-size: 12.5px; }
        .bc-right p { margin: 2px 0; }

        /* KPI Bar */
        .bc-summary-bar {
            display: flex;
            justify-content: space-around;
            background: #f8fafc;
            border: 1px solid #000;
            border-radius: 4px;
            padding: 8px 12px;
            margin-bottom: 14px;
            font-size: 13px;
        }
        .bc-summary-item { text-align: center; }
        .bc-summary-item span { font-size: 11.5px; color: #444; text-transform: uppercase; font-weight: 600; }
        .bc-summary-item strong { display: block; font-size: 15px; color: #000; margin-top: 2px; }

        /* Table */
        .bc-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 12px;
            margin-bottom: 14px;
        }
        .bc-table th, .bc-table td {
            border: 1px solid #000;
            padding: 5px 5px;
            text-align: center;
        }
        .bc-table th {
            background-color: #f1f5f9;
            font-weight: bold;
        }
        .bc-table td.text-left { text-align: left; }
        .bc-table td.text-right { text-align: right; }
        .bc-table tr.total-row td {
            font-weight: bold;
            background-color: #f8fafc;
        }

        /* Phụ lục */
        .bc-appendix {
            margin-top: 10px;
            margin-bottom: 14px;
            border: 1px dashed #666;
            padding: 8px 12px;
            border-radius: 4px;
            font-size: 12px;
            background: #fffbeb;
        }
        .bc-appendix h4 { margin: 0 0 4px 0; font-size: 12.5px; color: #b45309; }

        /* Signatures */
        .bc-signs {
            display: flex;
            justify-content: space-between;
            text-align: center;
            margin-top: 20px;
            page-break-inside: avoid;
        }
        .bc-signs > div { width: 23%; }
        .bc-signs strong { display: block; font-size: 13px; text-transform: uppercase; }
        .bc-signs em { display: block; font-size: 11.5px; margin-bottom: 50px; color: #555; }
        .bc-signs span { font-weight: bold; font-size: 13px; display: block; }
    </style>

    <div class="page-landscape">
        <div class="bc-header">
            <div class="bc-left">
                <p><strong>CÔNG TY TNHH NGHỆ THUẬT VÀ NHẠC CỤ PIANO NGUYỄN DUY</strong></p>
                <p><strong>Địa chỉ:</strong> Số 53 đường Lạch Tray, Quận Ngô Quyền, TP Hải Phòng</p>
                <p><strong>Điện thoại:</strong> 0123.456.789 &nbsp;|&nbsp; <strong>Mã số thuế:</strong> 0201234567</p>
                <p><strong>Bộ phận:</strong> Quản lý Kho & Kế toán hàng tồn kho</p>
            </div>
            <div class="bc-center">
                <h1>BÁO CÁO NHẬP - XUẤT - TỒN KHO</h1>
                <p style="font-style: italic;">Từ ngày <?= date('d/m/Y', strtotime($startDate)) ?> đến ngày <?= date('d/m/Y', strtotime($endDate)) ?></p>
                <p><strong>Kho báo cáo:</strong> <?= htmlspecialchars($tenKhoBaoCao) ?></p>
                <p style="font-size: 11.5px; color: #444;"><?= htmlspecialchars($diaChiKhoBaoCao) ?></p>
            </div>
            <div class="bc-right">
                <p><strong>Mẫu số: S10-DN</strong></p>
                <p style="font-style: italic; font-size: 10.5px;">(Ban hành theo Thông tư số 200/2014/TT-BTC & TT 133/2016/TT-BTC của BTC)</p>
                <p style="margin-top: 4px; font-size: 11.5px;">Ngày lập: <?= date('d/m/Y') ?></p>
            </div>
        </div>

        <!-- Tóm tắt số liệu trọng yếu -->
        <div class="bc-summary-bar">
            <div class="bc-summary-item">
                <span>TỒN ĐẦU KỲ</span>
                <strong><?= number_format($tongTonDau) ?> cây</strong>
            </div>
            <div class="bc-summary-item">
                <span>NHẬP TRONG KỲ</span>
                <strong style="color: #059669;">+<?= number_format($tongNhap) ?> cây</strong>
            </div>
            <div class="bc-summary-item">
                <span>XUẤT TRONG KỲ</span>
                <strong style="color: #dc2626;">-<?= number_format($tongXuat) ?> cây</strong>
            </div>
            <div class="bc-summary-item">
                <span>TỒN CUỐI KỲ</span>
                <strong style="color: #2563eb;"><?= number_format($tongTonCuoi) ?> cây</strong>
            </div>
            <div class="bc-summary-item">
                <span>TỔNG GIÁ TRỊ TỒN CUỐI (NIÊM YẾT)</span>
                <strong style="color: #7c3aed;"><?= number_format($tongGiaTriTon, 0, ',', '.') ?> đ</strong>
            </div>
        </div>

        <!-- Bảng chi tiết Nhập Xuất Tồn -->
        <table class="bc-table">
            <thead>
                <tr>
                    <th rowspan="2" width="4%">STT</th>
                    <th rowspan="2" width="9%">Mã Mẫu</th>
                    <th rowspan="2" width="23%">Tên Hàng Hóa, Nhãn Hiệu, Quy Cách</th>
                    <th rowspan="2" width="10%">Hãng</th>
                    <th rowspan="2" width="5%">ĐVT</th>
                    <th rowspan="2" width="8%">Tồn Đầu Kỳ</th>
                    <th colspan="2" width="16%">Phát Sinh Trong Kỳ</th>
                    <th colspan="2" width="25%">Tồn Cuối Kỳ</th>
                </tr>
                <tr>
                    <th width="8%">Nhập</th>
                    <th width="8%">Xuất</th>
                    <th width="8%">Số Lượng</th>
                    <th width="17%">Thành Tiền (VNĐ)</th>
                </tr>
                <tr style="font-size: 10.5px; background: #fafafa;">
                    <th>A</th>
                    <th>B</th>
                    <th>C</th>
                    <th>D</th>
                    <th>E</th>
                    <th>1</th>
                    <th>2</th>
                    <th>3</th>
                    <th>4 = 1+2-3</th>
                    <th>5 = 4 x Đơn giá</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($final_report)): ?>
                    <?php $stt = 1; foreach ($final_report as $row): ?>
                    <tr>
                        <td><?= $stt++ ?></td>
                        <td><strong>MD<?= str_pad($row['maMau'], 3, '0', STR_PAD_LEFT) ?></strong></td>
                        <td class="text-left"><?= htmlspecialchars($row['tenMau']) ?> (<?= htmlspecialchars($row['tenLoai']) ?>)</td>
                        <td><?= htmlspecialchars($row['tenHang']) ?></td>
                        <td>Cây</td>
                        <td><?= number_format($row['tonDauKy']) ?></td>
                        <td style="color: #059669; font-weight: <?= $row['nhapTrongKy'] > 0 ? 'bold' : 'normal' ?>;"><?= $row['nhapTrongKy'] > 0 ? '+' . number_format($row['nhapTrongKy']) : '-' ?></td>
                        <td style="color: #dc2626; font-weight: <?= $row['xuatTrongKy'] > 0 ? 'bold' : 'normal' ?>;"><?= $row['xuatTrongKy'] > 0 ? '-' . number_format($row['xuatTrongKy']) : '-' ?></td>
                        <td style="font-weight: bold;"><?= number_format($row['tonCuoiKy']) ?></td>
                        <td class="text-right" style="font-weight: bold;"><?= $row['thanhTienTon'] > 0 ? number_format($row['thanhTienTon'], 0, ',', '.') : '-' ?></td>
                    </tr>
                    <?php endforeach; ?>
                    <tr class="total-row">
                        <td colspan="5" style="text-align: center; font-weight: bold;">TỔNG CỘNG</td>
                        <td><?= number_format($tongTonDau) ?></td>
                        <td style="color: #059669;"><?= number_format($tongNhap) ?></td>
                        <td style="color: #dc2626;"><?= number_format($tongXuat) ?></td>
                        <td style="font-weight: bold; color: #2563eb;"><?= number_format($tongTonCuoi) ?></td>
                        <td class="text-right" style="font-weight: bold; color: #7c3aed;"><?= number_format($tongGiaTriTon, 0, ',', '.') ?></td>
                    </tr>
                <?php else: ?>
                    <tr>
                        <td colspan="10" style="padding: 20px; font-style: italic;">Không có phát sinh hoặc tồn kho trong khoảng thời gian đã chọn.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>

        <div style="font-size: 13px; margin-bottom: 8px;">
            <p>- Tổng giá trị tài sản tồn kho (Viết bằng chữ): <strong><?= soTienBangChu($tongGiaTriTon) ?></strong></p>
        </div>

        <!-- Phụ lục hàng bảo trì lưu kho (nếu có) -->
        <?php if (!empty($danhSachBaoTri)): ?>
        <div class="bc-appendix">
            <h4><span class="material-symbols-rounded" style="font-size: 14px; vertical-align: text-bottom;">info</span> PHỤ LỤC: HÀNG CỦA KHÁCH GỬI BẢO TRÌ ĐANG LƯU KHO (KHÔNG TÍNH VÀO TỒN THƯƠNG MẠI)</h4>
            <div style="line-height: 1.45;">
                Hiện tại kho đang lưu giữ tạm thời <strong><?= count($danhSachBaoTri) ?> cây đàn</strong> của khách hàng gửi bảo hành/sửa chữa:
                <ul style="margin: 3px 0 0 18px; padding: 0;">
                    <?php foreach($danhSachBaoTri as $bt): ?>
                        <li>
                            <strong><?= htmlspecialchars($bt['tenMau']) ?></strong> (Serial: <code><?= htmlspecialchars($bt['soSerial']) ?></code>) 
                            - Thuộc Phiếu BT: <strong>#<?= $bt['maPhieuBT'] ?></strong> (Khách: <?= htmlspecialchars($bt['tenKH'] ?? 'Khách lẻ') ?>)
                            <?php if(!empty($bt['moTaLoi'])): ?> <em>- Lỗi: <?= htmlspecialchars($bt['moTaLoi']) ?></em><?php endif; ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
        <?php endif; ?>

        <div style="text-align: right; font-style: italic; font-size: 12.5px; margin-top: 8px; margin-bottom: 4px;">
            Hải Phòng, ngày <?= date('d') ?> tháng <?= date('m') ?> năm <?= date('Y') ?>
        </div>

        <!-- Khối chữ ký 4 bên -->
        <div class="bc-signs">
            <div>
                <strong>Người Lập Biểu</strong>
                <em>(Ký, họ tên)</em>
                <span><?= htmlspecialchars($_SESSION['fullname'] ?? 'Nhân viên') ?></span>
            </div>
            <div>
                <strong>Thủ Kho</strong>
                <em>(Ký, họ tên)</em>
                <span>.....................................</span>
            </div>
            <div>
                <strong>Kế Toán Trưởng</strong>
                <em>(Ký, họ tên)</em>
                <span>.....................................</span>
            </div>
            <div>
                <strong>Giám Đốc Duyệt</strong>
                <em>(Ký, đóng dấu)</em>
                <span>.....................................</span>
            </div>
        </div>
    </div>

    <?php elseif ($type == 'phieunhap'): ?>
    
    <style>
        .pn-header { display: flex; justify-content: space-between; margin-bottom: 20px; font-family: "Times New Roman", Times, serif; }
        .pn-left { width: 45%; }
        .pn-left p { margin: 3px 0; font-size: 14px; }
        .pn-left strong { font-weight: bold; }
        .pn-center { width: 30%; text-align: center; }
        .pn-center h1 { color: #000; font-size: 20px; margin: 0 0 5px 0; text-transform: uppercase; }
        .pn-center p { margin: 3px 0; font-style: italic; font-size: 14px; }
        .pn-right { width: 25%; text-align: center; font-size: 14px; }
        .pn-right p { margin: 3px 0; }
        .pn-info { font-family: "Times New Roman", Times, serif; font-size: 15px; line-height: 1.6; margin-bottom: 15px; }
        .pn-table { width: 100%; border-collapse: collapse; font-family: "Times New Roman", Times, serif; font-size: 14px; margin-bottom: 15px; }
        .pn-table th, .pn-table td { border: 1px solid #000; padding: 6px 4px; text-align: center; }
        .pn-table th { font-weight: bold; }
        .pn-table td.text-left { text-align: left; }
        .pn-table td.text-right { text-align: right; }
        .pn-footer { font-family: "Times New Roman", Times, serif; font-size: 15px; margin-bottom: 15px; }
        .pn-signs { display: flex; justify-content: space-between; text-align: center; font-family: "Times New Roman", Times, serif; margin-top: 10px; }
        .pn-signs > div { width: 20%; }
        .pn-signs strong { display: block; font-size: 14px; }
        .pn-signs em { display: block; font-size: 13px; margin-bottom: 70px; }
    </style>
    
    <div class="page-a4" style="color: #000;">
        <div class="pn-header">
            <div class="pn-left">
                <p><strong>Đơn vị:</strong> CÔNG TY TNHH NGHỆ THUẬT VÀ<br>NHẠC CỤ PIANO NGUYỄN DUY</p>
                <p><strong>Mã số thuế:</strong> ..............................................................</p>
                <p><strong>Địa chỉ:</strong> Số 53 đường Lạch Tray, Phường Lạch Tray, Quận Ngô Quyền, Thành phố Hải Phòng, Việt Nam.</p>
                <p><strong>Điện thoại:</strong> ................................................................</p>
                <p><strong>Bộ phận:</strong> Quản lý Kho hàng</p>
            </div>
            <div class="pn-center">
                <h1>PHIẾU NHẬP KHO</h1>
                <p>Ngày <?= date('d', strtotime($data['ngayNhap'])) ?> tháng <?= date('m', strtotime($data['ngayNhap'])) ?> năm <?= date('Y', strtotime($data['ngayNhap'])) ?></p>
                <p>Số: <?= str_pad($data['maPhieuNhap'], 5, '0', STR_PAD_LEFT) ?></p>
            </div>
            <div class="pn-right">
                <p><strong>Mẫu số: 01 - VT</strong></p>
                <p style="font-style: italic;">(Ban hành theo Thông tư số 133/2016/TT-BTC<br>ngày 26/8/2016 của Bộ trưởng BTC)</p>
                <p style="text-align: left; margin-top: 10px; padding-left: 20px;">Nợ: ....................................</p>
                <p style="text-align: left; padding-left: 20px;">Có: ....................................</p>
            </div>
        </div>

        <div class="pn-info">
            <?php 
                $ngayHDStr = "ngày ..... tháng ..... năm .....";
                if(!empty($data['ngayHoaDon'])) {
                    $ts = strtotime($data['ngayHoaDon']);
                    $ngayHDStr = "ngày " . date('d', $ts) . " tháng " . date('m', $ts) . " năm " . date('Y', $ts);
                }
            ?>
            <div>- Họ và tên người giao: <?= htmlspecialchars($data['nguoiGiaoHang'] ?? '.......................................................................................') ?></div>
            <div>- Theo <?= htmlspecialchars($data['soHoaDonNCC'] ?? '.........................') ?> số <?= htmlspecialchars($data['soHoaDonNCC'] ?? '.........') ?> <?= $ngayHDStr ?> của <?= htmlspecialchars($data['tenNCC'] ?? '...................................................') ?></div>
            <div>- Nhập tại kho: <?= htmlspecialchars($tenKho ?? '.............................................') ?> Địa điểm: <?= htmlspecialchars($diaChiKho ?? '.............................................') ?></div>
            <div>- Lý do nhập kho: <?= htmlspecialchars($data['ghiChu'] ?? '......................................................................................................................') ?></div>
        </div>

        <table class="pn-table">
            <thead>
                <tr>
                    <th rowspan="2">Số<br>thứ<br>tự</th>
                    <th rowspan="2">Tên, nhãn hiệu, quy cách<br><span style="font-weight:normal">(Model, màu sắc, tình trạng...)</span></th>
                    <th rowspan="2">Mã số<br><span style="font-weight:normal">(Serial đàn)</span></th>
                    <th rowspan="2">Đơn vị<br>tính</th>
                    <th colspan="2">Số lượng</th>
                    <th rowspan="2">Đơn giá</th>
                    <th rowspan="2">Thành tiền</th>
                    <th rowspan="2">Ghi chú</th>
                </tr>
                <tr>
                    <th>Theo<br>CT</th>
                    <th>Thực<br>nhập</th>
                </tr>
                <tr>
                    <th>A</th>
                    <th>B</th>
                    <th>C</th>
                    <th>D</th>
                    <th>1</th>
                    <th>2</th>
                    <th>3</th>
                    <th>4</th>
                    <th>E</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                $stt = 1;
                $sum_qty = 0;
                $sum_amount = 0;
                foreach ($details as $row): 
                    $sum_qty++;
                    $amount = $row['giaNhap'] * 1;
                    $sum_amount += $amount;
                ?>
                <tr>
                    <td><?= $stt++ ?></td>
                    <td class="text-left"><?= htmlspecialchars($row['tenMau'] . ' - ' . $row['tenHang']) ?></td>
                    <td><?= htmlspecialchars($row['soSerial']) ?></td>
                    <td>Chiếc</td>
                    <td>1</td>
                    <td>1</td>
                    <td class="text-right"><?= number_format($row['giaNhap'], 0, ',', '.') ?></td>
                    <td class="text-right"><?= number_format($amount, 0, ',', '.') ?></td>
                    <td></td>
                </tr>
                <?php endforeach; ?>
                <!-- Đảm bảo đủ dòng trống nếu ít hàng -->
                <?php for($i=$stt; $i<=8; $i++): ?>
                <tr>
                    <td><?= $i ?></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                </tr>
                <?php endfor; ?>
                <tr>
                    <td colspan="4" style="font-weight: bold; text-align: center;">CỘNG</td>
                    <td>x</td>
                    <td style="font-weight: bold;"><?= $sum_qty ?></td>
                    <td>x</td>
                    <td class="text-right" style="font-weight: bold;"><?= number_format($sum_amount, 0, ',', '.') ?></td>
                    <td>x</td>
                </tr>
            </tbody>
        </table>

        <div class="pn-footer">
            <p>- Tổng số tiền (Viết bằng chữ): <strong><?= soTienBangChu($sum_amount) ?></strong></p>
            <p>- Số chứng từ gốc kèm theo: ................................................................................................................................................................................................</p>
        </div>

        <div style="text-align: right; font-style: italic; font-family: 'Times New Roman', Times, serif; font-size: 14px; margin-bottom: 5px;">
            Ngày <?= date('d', strtotime($data['ngayNhap'])) ?> tháng <?= date('m', strtotime($data['ngayNhap'])) ?> năm <?= date('Y', strtotime($data['ngayNhap'])) ?>
        </div>
        
        <div class="pn-signs">
            <div>
                <strong>Người lập phiếu</strong>
                <em>(Ký, họ tên)</em>
                <span><?= htmlspecialchars($data['tenNV'] ?? '') ?></span>
            </div>
            <div>
                <strong>Người giao hàng</strong>
                <em>(Ký, họ tên)</em>
                <span><?= htmlspecialchars($data['nguoiGiaoHang'] ?? '') ?></span>
            </div>
            <div>
                <strong>Thủ kho</strong>
                <em>(Ký, họ tên)</em>
            </div>
            <div>
                <strong>Kế toán trưởng</strong>
                <em>(Ký, họ tên)</em>
            </div>
            <div>
                <strong>Giám đốc</strong>
                <em>(Ký, họ tên)</em>
            </div>
        </div>
    </div>
    
    <?php elseif ($type == 'phieuxuat'): ?>
    
    <style>
        .px-header { display: flex; justify-content: space-between; margin-bottom: 20px; font-family: "Times New Roman", Times, serif; }
        .px-left { width: 45%; }
        .px-left p { margin: 3px 0; font-size: 14px; }
        .px-left strong { font-weight: bold; }
        .px-center { width: 30%; text-align: center; }
        .px-center h1 { color: #000; font-size: 20px; margin: 0 0 5px 0; text-transform: uppercase; }
        .px-center p { margin: 3px 0; font-style: italic; font-size: 14px; }
        .px-right { width: 25%; text-align: center; font-size: 14px; }
        .px-right p { margin: 3px 0; }
        .px-info { font-family: "Times New Roman", Times, serif; font-size: 15px; line-height: 1.7; margin-bottom: 15px; }
        .px-table { width: 100%; border-collapse: collapse; font-family: "Times New Roman", Times, serif; font-size: 14px; margin-bottom: 15px; }
        .px-table th, .px-table td { border: 1px solid #000; padding: 6px 4px; text-align: center; }
        .px-table th { font-weight: bold; background: #f5f5f5; }
        .px-table td.text-left { text-align: left; }
        .px-table td.text-right { text-align: right; }
        .px-footer { font-family: "Times New Roman", Times, serif; font-size: 15px; margin-bottom: 15px; }
        .px-signs { display: flex; justify-content: space-between; text-align: center; font-family: "Times New Roman", Times, serif; margin-top: 10px; }
        .px-signs > div { width: 20%; }
        .px-signs strong { display: block; font-size: 14px; }
        .px-signs em { display: block; font-size: 13px; margin-bottom: 70px; }
    </style>
    
    <div class="page-a4" style="color: #000;">
        <div class="px-header">
            <div class="px-left">
                <p><strong>Đơn vị:</strong> CÔNG TY TNHH NGHỆ THUẬT VÀ<br>NHẠC CỤ PIANO NGUYỄN DUY</p>
                <p><strong>Mã số thuế:</strong> ..............................................................</p>
                <p><strong>Địa chỉ:</strong> Số 53 đường Lạch Tray, Phường Lạch Tray, Quận Ngô Quyền, Thành phố Hải Phòng, Việt Nam.</p>
                <p><strong>Điện thoại:</strong> ................................................................</p>
                <p><strong>Bộ phận:</strong> Quản lý Kho hàng</p>
            </div>
            <div class="px-center">
                <h1>PHIẾU XUẤT KHO</h1>
                <p>Ngày <?= date('d', strtotime($data['ngayXuat'])) ?> tháng <?= date('m', strtotime($data['ngayXuat'])) ?> năm <?= date('Y', strtotime($data['ngayXuat'])) ?></p>
                <p>Số: <?= str_pad($data['maPhieuXuat'], 5, '0', STR_PAD_LEFT) ?></p>
            </div>
            <div class="px-right">
                <p><strong>Mẫu số: 02 - VT</strong></p>
                <p style="font-style: italic;">(Ban hành theo Thông tư số 133/2016/TT-BTC<br>ngày 26/8/2016 của Bộ trưởng BTC)</p>
                <p style="text-align: left; margin-top: 10px; padding-left: 20px;">Nợ: ....................................</p>
                <p style="text-align: left; padding-left: 20px;">Có: ....................................</p>
            </div>
        </div>

        <div class="px-info">
            <?php 
                $ngayCTStr = "ngày ..... tháng ..... năm .....";
                if(!empty($data['ngayChungTu'])) {
                    $ts = strtotime($data['ngayChungTu']);
                    $ngayCTStr = "ngày " . date('d', $ts) . " tháng " . date('m', $ts) . " năm " . date('Y', $ts);
                }
            ?>
            <div>- Họ và tên người nhận hàng: <?= htmlspecialchars($data['nguoiNhanHang'] ?? '.......................................................................................') ?></div>
            <div>- Của (đơn vị): <?= htmlspecialchars($data['donViNhan'] ?? '..............................................................................................') ?></div>
            <div>- Theo <?= htmlspecialchars($data['soChungTu'] ?? '.........................') ?> số <?= htmlspecialchars($data['soChungTu'] ?? '.........') ?> <?= $ngayCTStr ?></div>
            <div>- Xuất tại kho: <?= htmlspecialchars($tenKho ?? '.............................................') ?> Địa điểm: <?= htmlspecialchars($diaChiKho ?? '.............................................') ?></div>
            <div>- Lý do xuất kho: <?= htmlspecialchars($data['lyDoXuat'] ?? '......................................................................................................................') ?></div>
        </div>

        <table class="px-table">
            <thead>
                <tr>
                    <th rowspan="2">Số<br>thứ<br>tự</th>
                    <th rowspan="2">Tên, nhãn hiệu, quy cách<br><span style="font-weight:normal">(Model, màu sắc, tình trạng...)</span></th>
                    <th rowspan="2">Mã số<br><span style="font-weight:normal">(Serial đàn)</span></th>
                    <th rowspan="2">Đơn vị<br>tính</th>
                    <th colspan="2">Số lượng</th>
                    <th rowspan="2">Đơn giá</th>
                    <th rowspan="2">Thành tiền</th>
                    <th rowspan="2">Ghi chú</th>
                </tr>
                <tr>
                    <th>Yêu<br>cầu</th>
                    <th>Thực<br>xuất</th>
                </tr>
                <tr>
                    <th>A</th>
                    <th>B</th>
                    <th>C</th>
                    <th>D</th>
                    <th>1</th>
                    <th>2</th>
                    <th>3</th>
                    <th>4</th>
                    <th>E</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                $stt = 1;
                $sum_qty = 0;
                $sum_amount_px = 0;
                foreach ($details as $row): 
                    $sum_qty++;
                    // Lấy đơn giá từ danserial (giaBan)
                    $don_gia = $row['giaBan'] ?? 0;
                    $thanh_tien = $don_gia * 1;
                    $sum_amount_px += $thanh_tien;
                ?>
                <tr>
                    <td><?= $stt++ ?></td>
                    <td class="text-left"><?= htmlspecialchars($row['tenMau'] . ' - ' . $row['tenHang']) ?></td>
                    <td><?= htmlspecialchars($row['soSerial']) ?></td>
                    <td>Chiếc</td>
                    <td>1</td>
                    <td>1</td>
                    <td class="text-right"><?= $don_gia > 0 ? number_format($don_gia, 0, ',', '.') : '' ?></td>
                    <td class="text-right"><?= $thanh_tien > 0 ? number_format($thanh_tien, 0, ',', '.') : '' ?></td>
                    <td></td>
                </tr>
                <?php endforeach; ?>
                <!-- Đảm bảo đủ dòng trống nếu ít hàng -->
                <?php for($i=$stt; $i<=8; $i++): ?>
                <tr>
                    <td><?= $i ?></td>
                    <td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td>
                </tr>
                <?php endfor; ?>
                <tr>
                    <td colspan="4" style="font-weight: bold; text-align: center;">CỘNG</td>
                    <td>x</td>
                    <td style="font-weight: bold;"><?= $sum_qty ?></td>
                    <td>x</td>
                    <td class="text-right" style="font-weight: bold;"><?= number_format($sum_amount_px, 0, ',', '.') ?></td>
                    <td>x</td>
                </tr>
            </tbody>
        </table>

        <div class="px-footer">
            <p>- Tổng số tiền (Viết bằng chữ): <strong><?= soTienBangChu($sum_amount_px) ?></strong></p>
            <p>- Số chứng từ gốc kèm theo: ................................................................................................................................................................................................</p>
        </div>

        <div style="text-align: right; font-style: italic; font-family: 'Times New Roman', Times, serif; font-size: 14px; margin-bottom: 5px;">
            Ngày <?= date('d', strtotime($data['ngayXuat'])) ?> tháng <?= date('m', strtotime($data['ngayXuat'])) ?> năm <?= date('Y', strtotime($data['ngayXuat'])) ?>
        </div>
        
        <div class="px-signs">
            <div>
                <strong>Người lập phiếu</strong>
                <em>(Ký, họ tên)</em>
                <span><?= htmlspecialchars($data['tenNV'] ?? '') ?></span>
            </div>
            <div>
                <strong>Người nhận hàng</strong>
                <em>(Ký, họ tên)</em>
                <span><?= htmlspecialchars($data['nguoiNhanHang'] ?? '') ?></span>
            </div>
            <div>
                <strong>Thủ kho</strong>
                <em>(Ký, họ tên)</em>
            </div>
            <div>
                <strong>Kế toán trưởng</strong>
                <em>(Ký, họ tên)</em>
            </div>
            <div>
                <strong>Giám đốc</strong>
                <em>(Ký, họ tên)</em>
            </div>
        </div>
    </div>
    
    <?php elseif (in_array($type, ['baotri_tiepnhan', 'baotri_nhap', 'baotri_xuat'])): ?>
    
    <style>
        .bt-header { display: flex; justify-content: space-between; margin-bottom: 20px; font-family: "Times New Roman", Times, serif; }
        .bt-left { width: 50%; }
        .bt-left p { margin: 3px 0; font-size: 14px; }
        .bt-left strong { font-weight: bold; }
        .bt-center { width: 50%; text-align: center; }
        .bt-center h1 { color: #000; font-size: 22px; margin: 0 0 5px 0; text-transform: uppercase; font-weight: bold; }
        .bt-center p { margin: 3px 0; font-style: italic; font-size: 14px; }
        .bt-info { font-family: "Times New Roman", Times, serif; font-size: 15px; line-height: 1.7; margin-bottom: 20px; border: 1px solid #000; padding: 15px; }
        .bt-info p { margin: 8px 0; }
        .bt-signs { display: flex; justify-content: space-around; text-align: center; font-family: "Times New Roman", Times, serif; margin-top: 40px; }
        .bt-signs strong { display: block; font-size: 15px; }
        .bt-signs em { display: block; font-size: 14px; margin-bottom: 80px; }
    </style>
    
    <div class="page-a4" style="color: #000;">
        <div class="bt-header">
            <div class="bt-left">
                <p><strong>CÔNG TY TNHH NGHỆ THUẬT VÀ NHẠC CỤ PIANO NGUYỄN DUY</strong></p>
                <p>Địa chỉ: Số 53 đường Lạch Tray, Quận Ngô Quyền, TP Hải Phòng</p>
                <p>Điện thoại: 0123.456.789</p>
            </div>
            <div class="bt-center">
                <h1><?= $title ?></h1>
                <p>Ngày <?= date('d/m/Y', strtotime($data['ngayCapNhat'] ?? $data['ngayTiepNhan'])) ?></p>
                <p>Số Phiếu: <?= str_pad($data['maPhieuBT'], 5, '0', STR_PAD_LEFT) ?></p>
            </div>
        </div>
        
        <div class="bt-info">
            <h3 style="margin-top: 0; border-bottom: 1px dashed #ccc; padding-bottom: 10px; font-size: 16px;">THÔNG TIN SẢN PHẨM & KHÁCH HÀNG</h3>
            <p><strong>Khách hàng:</strong> <?= htmlspecialchars($data['tenKH']) ?> - <strong>SĐT:</strong> <?= htmlspecialchars($data['sdtKH']) ?></p>
            <p><strong>Sản phẩm bảo trì:</strong> Đàn Piano <?= htmlspecialchars($data['tenMau']) ?></p>
            <p><strong>Số Serial:</strong> <?= htmlspecialchars($data['soSerial']) ?></p>
            <p><strong>Thuộc Hóa Đơn số:</strong> <?= $data['maHoaDon'] ?></p>
            
            <?php if ($type == 'baotri_tiepnhan'): ?>
                <p><strong>Tình trạng / Lỗi ghi nhận:</strong> <?= nl2br(htmlspecialchars($data['moTaLoi'])) ?></p>
                <p style="margin-top: 15px; font-style: italic; color: #555;">(Lưu ý: Quý khách vui lòng giữ lại phiếu này để đối chiếu khi nhận lại đàn)</p>
            <?php elseif ($type == 'baotri_nhap'): ?>
                <p><strong>Tình trạng / Lỗi ghi nhận:</strong> <?= nl2br(htmlspecialchars($data['moTaLoi'])) ?></p>
                <p><strong>Kho tiếp nhận bảo trì:</strong> <?= htmlspecialchars($data['tenKho'] ?? 'Kho bảo trì') ?><?= !empty($data['diaChiKho']) ? ' (Địa điểm: ' . htmlspecialchars($data['diaChiKho']) . ')' : '' ?></p>
                <p><strong>Ghi chú Nhập Kho:</strong> Nhập kho để tiến hành lưu trữ và thực hiện quy trình bảo hành/bảo trì.</p>
            <?php elseif ($type == 'baotri_xuat'): ?>
                <p><strong>Lỗi cần xử lý:</strong> <?= nl2br(htmlspecialchars($data['moTaLoi'])) ?></p>
                <p><strong>Hãng nhận bảo trì:</strong> <?= htmlspecialchars($data['tenHangSanXuat'] ?? 'Không xác định') ?></p>
                <p><strong>Ghi chú Xuất Hãng:</strong> Bàn giao đàn cho nhà sản xuất/hãng để xử lý lỗi theo tiêu chuẩn bảo hành.</p>
            <?php endif; ?>
        </div>
        
        <div class="bt-signs">
            <?php if ($type == 'baotri_tiepnhan'): ?>
                <div>
                    <strong>Đại diện Cửa Hàng</strong>
                    <em>(Ký, ghi rõ họ tên)</em>
                    <span><?= htmlspecialchars($data['tenNVLap']) ?></span>
                </div>
                <div>
                    <strong>Khách Hàng</strong>
                    <em>(Ký, ghi rõ họ tên)</em>
                    <span><?= htmlspecialchars($data['tenKH']) ?></span>
                </div>
            <?php elseif ($type == 'baotri_nhap'): ?>
                <div>
                    <strong>Thủ Kho</strong>
                    <em>(Ký, ghi rõ họ tên)</em>
                </div>
                <div>
                    <strong>Người Giao</strong>
                    <em>(Ký, ghi rõ họ tên)</em>
                    <span><?= htmlspecialchars($data['tenNVLap']) ?></span>
                </div>
            <?php elseif ($type == 'baotri_xuat'): ?>
                <div>
                    <strong>Thủ Kho</strong>
                    <em>(Ký, ghi rõ họ tên)</em>
                </div>
                <div>
                    <strong>Đại diện Hãng nhận</strong>
                    <em>(Ký, ghi rõ họ tên)</em>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <?php else: ?>
    <!-- Hóa đơn -->
    <style>
        .hd-header { display: flex; justify-content: space-between; margin-bottom: 20px; font-family: "Times New Roman", Times, serif; }
        .hd-left { width: 55%; }
        .hd-left h2 { margin: 0 0 5px 0; color: #1e3a8a; font-size: 16px; font-weight: bold; text-transform: uppercase; }
        .hd-left p { margin: 3px 0; font-size: 14px; }
        .hd-right { width: 40%; text-align: center; }
        .hd-right h1 { color: #dc2626; font-size: 24px; margin: 0 0 5px 0; font-weight: bold; }
        .hd-right p { margin: 3px 0; font-style: italic; font-size: 14px; }
        
        .hd-info { font-family: "Times New Roman", Times, serif; font-size: 15px; line-height: 1.8; margin-bottom: 20px; border-top: 2px solid #1e3a8a; padding-top: 20px; }
        .hd-info .row { display: flex; }
        .hd-info .label { width: 180px; font-weight: bold; }
        .hd-info .value { flex: 1; border-bottom: 1px dotted #ccc; }
        
        .hd-table { width: 100%; border-collapse: collapse; font-family: "Times New Roman", Times, serif; font-size: 14px; margin-bottom: 20px; }
        .hd-table th, .hd-table td { border: 1px solid #000; padding: 8px 6px; text-align: center; }
        .hd-table th { font-weight: bold; background-color: #f0f8ff; color: #1e3a8a; }
        .hd-table td.text-left { text-align: left; }
        .hd-table td.text-right { text-align: right; }
        
        .hd-summary-table { width: 100%; border-collapse: collapse; font-family: "Times New Roman", Times, serif; font-size: 14px; margin-bottom: 15px; }
        .hd-summary-table td { border: 1px solid #000; padding: 8px 6px; }
        .hd-summary-table .label { font-weight: bold; text-align: right; width: 70%; }
        .hd-summary-table .value { text-align: right; font-weight: bold; width: 30%; }
        
        .hd-footer { font-family: "Times New Roman", Times, serif; font-size: 15px; margin-bottom: 15px; }
        .hd-signs { display: flex; justify-content: space-around; text-align: center; font-family: "Times New Roman", Times, serif; margin-top: 30px; }
        .hd-signs strong { display: block; font-size: 15px; }
        .hd-signs em { display: block; font-size: 14px; margin-bottom: 80px; }
    </style>
    
    <div class="page-a4" style="color: #000;">
        <div class="hd-header">
            <div class="hd-left">
                <h2>CÔNG TY TNHH NGHỆ THUẬT VÀ NHẠC CỤ<br>PIANO NGUYỄN DUY</h2>
                <p><strong>Địa chỉ:</strong> Số 53 đường Lạch Tray, Phường Lạch Tray, Quận Ngô Quyền, Thành phố Hải Phòng, Việt Nam</p>
                <p><strong>Mã số thuế:</strong> ..............................................................</p>
                <p><strong>Điện thoại:</strong> ..............................................................</p>
                <p><strong>Số tài khoản:</strong> ..............................................................</p>
            </div>
            <div class="hd-right">
                <h1>HÓA ĐƠN BÁN HÀNG</h1>
                <p>Ngày <?= date('d', strtotime($data['ngayLap'])) ?> tháng <?= date('m', strtotime($data['ngayLap'])) ?> năm <?= date('Y', strtotime($data['ngayLap'])) ?></p>
                <p style="font-weight: bold; font-style: normal; margin-top: 10px;">Số: <?= str_pad($data['maHoaDon'], 7, '0', STR_PAD_LEFT) ?></p>
            </div>
        </div>

        <div class="hd-info">
            <div class="row">
                <div class="label">Họ tên người mua hàng:</div>
                <div class="value"><?= htmlspecialchars($data['tenKH'] ?? '') ?></div>
            </div>
            <div class="row">
                <div class="label">Tên đơn vị:</div>
                <div class="value"><?= htmlspecialchars($data['tenDonVi'] ?? '') ?></div>
            </div>
            <div class="row">
                <div class="label">Mã số thuế:</div>
                <div class="value"><?= htmlspecialchars($data['maSoThue'] ?? '') ?></div>
            </div>
            <div class="row">
                <div class="label">Địa chỉ:</div>
                <div class="value"><?= htmlspecialchars($data['diaChiKH'] ?? '') ?></div>
            </div>
            <div class="row">
                <div class="label">Hình thức thanh toán:</div>
                <div class="value"><?= htmlspecialchars($data['hinhThucThanhToan'] ?? '') ?></div>
            </div>
        </div>

        <table class="hd-table">
            <thead>
                <tr>
                    <th width="5%">STT</th>
                    <th width="40%">Tên hàng hóa, dịch vụ</th>
                    <th width="10%">ĐVT</th>
                    <th width="10%">Số<br>lượng</th>
                    <th width="15%">Đơn giá</th>
                    <th width="20%">Thành tiền</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                $stt = 1;
                $sum_amount = 0;
                foreach ($details as $row): 
                    $don_gia = $row['donGia'] ?? 0;
                    $thanh_tien = $don_gia * 1;
                    $sum_amount += $thanh_tien;
                ?>
                <tr>
                    <td><?= $stt++ ?></td>
                    <td class="text-left">
                        <strong><?= htmlspecialchars($row['tenMau'] . ' - ' . $row['tenHang']) ?></strong><br>
                        <span style="font-size: 13px; font-style: italic; color: #333;">Serial: <?= htmlspecialchars($row['soSerial']) ?> | Bảo hành: <?= htmlspecialchars($row['baoHanh'] ?? 'Không') ?></span>
                    </td>
                    <td>Chiếc</td>
                    <td>1</td>
                    <td class="text-right"><?= number_format($don_gia, 0, ',', '.') ?></td>
                    <td class="text-right"><?= number_format($thanh_tien, 0, ',', '.') ?></td>
                </tr>
                <?php endforeach; ?>
                <!-- Đảm bảo đủ dòng trống -->
                <?php for($i=$stt; $i<=8; $i++): ?>
                <tr>
                    <td><?= $i ?></td>
                    <td></td><td></td><td></td><td></td><td></td>
                </tr>
                <?php endfor; ?>
            </tbody>
        </table>

        <?php
            $phanTramGiam = isset($data['phanTramGiam']) ? floatval($data['phanTramGiam']) : 0;
            $tienGiam = $sum_amount * ($phanTramGiam / 100);
            $sum_sau_giam = $sum_amount - $tienGiam;

            $thueGTGT = intval($data['thueGTGT'] ?? 0);
            $tienThue = $sum_sau_giam * ($thueGTGT / 100);
            $tongCong = $sum_sau_giam + $tienThue;
        ?>
        <table class="hd-summary-table">
            <tr>
                <td class="label">Cộng tiền hàng:</td>
                <td class="value"><?= number_format($sum_amount, 0, ',', '.') ?></td>
            </tr>
            <?php if($phanTramGiam > 0): ?>
            <tr>
                <td class="label">Chiết khấu/Khuyến mãi (<?= $phanTramGiam ?>%):</td>
                <td class="value">-<?= number_format($tienGiam, 0, ',', '.') ?></td>
            </tr>
            <?php endif; ?>
            <tr>
                <td class="label">Thuế suất GTGT (<?= $thueGTGT ?> %):</td>
                <td class="value"><?= number_format($tienThue, 0, ',', '.') ?></td>
            </tr>
            <tr>
                <td class="label">Tổng cộng tiền thanh toán:</td>
                <td class="value"><?= number_format($tongCong, 0, ',', '.') ?></td>
            </tr>
        </table>

        <div class="hd-footer">
            <p><strong>Số tiền viết bằng chữ:</strong> <em><?= soTienBangChu($tongCong) ?></em></p>
            <p style="border-bottom: 1px dotted #ccc; height: 20px; width: 100%;"></p>
        </div>

        <div class="hd-signs">
            <div>
                <strong>NGƯỜI MUA HÀNG</strong>
                <em>(Ký, ghi rõ họ tên)</em>
                <span><?= htmlspecialchars($data['tenKH'] ?? '') ?></span>
            </div>
            <div>
                <strong>NGƯỜI BÁN HÀNG</strong>
                <em>(Ký, đóng dấu, ghi rõ họ tên)</em>
                <span><?= htmlspecialchars($data['tenNV'] ?? '') ?></span>
            </div>
        </div>
    </div>
    <?php endif; ?>

</body>
</html>
