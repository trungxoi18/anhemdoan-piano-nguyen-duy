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

if (!$type || !$id) {
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
    if (count($details) > 0) {
        $maKho_first = $details[0]['maKho'];
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
    if(count($details) > 0) {
        $maKho_first = $details[0]['maKho'];
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
            ds.soSerial, md.tenMau, hd.tenHang as tenHangSanXuat, nv.hoTen as tenNVLap
            FROM phieubaotri pb
            JOIN khachhang kh ON pb.maKhachHang = kh.maKhachHang
            JOIN danserial ds ON pb.maSerial = ds.maSerial
            JOIN maudan md ON ds.maMau = md.maMau
            LEFT JOIN hangdan hd ON pb.maHang = hd.maHang
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

    <?php if ($type == 'phieunhap'): ?>
    
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
                <p><strong>Ghi chú Nhập Kho:</strong> Nhập kho để tiến hành bảo hành/bảo trì.</p>
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
