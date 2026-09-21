<?php
// API Tìm kiếm nhanh đa năng (Global Quick Search)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/db.php';

// Kiểm tra đăng nhập
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$query = trim($_GET['q'] ?? '');
if (mb_strlen($query, 'UTF-8') < 1) {
    echo json_encode([
        'success' => true,
        'query' => '',
        'total' => 0,
        'results' => [
            'models' => [],
            'serials' => [],
            'invoices' => [],
            'import_slips' => [],
            'export_slips' => [],
            'customers' => []
        ]
    ]);
    exit();
}

$like = '%' . $query . '%';
$cleanNum = preg_replace('/[^0-9]/', '', $query);
$numVal = !empty($cleanNum) ? intval($cleanNum) : 0;

$results = [
    'models' => [],
    'serials' => [],
    'invoices' => [],
    'import_slips' => [],
    'export_slips' => [],
    'customers' => []
];
$total_count = 0;

// 1. Tìm kiếm Mẫu đàn (MauDan + HangDan + LoaiDan)
$sql_models = "SELECT md.maMau, md.tenMau, md.moTa, md.hinhAnh, hd.tenHang, ld.tenLoai,
               (SELECT COUNT(*) FROM danserial ds WHERE ds.maMau = md.maMau AND ds.trangThai = 'Trong kho') AS soLuongTon,
               (SELECT MIN(ds.giaBan) FROM danserial ds WHERE ds.maMau = md.maMau) AS giaMin
               FROM maudan md
               LEFT JOIN hangdan hd ON md.maHang = hd.maHang
               LEFT JOIN loaidan ld ON md.maLoai = ld.maLoai
               WHERE md.tenMau LIKE ? OR hd.tenHang LIKE ? OR ld.tenLoai LIKE ? OR md.moTa LIKE ?
               LIMIT 5";
$stmt = $conn->prepare($sql_models);
if ($stmt) {
    $stmt->bind_param("ssss", $like, $like, $like, $like);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($row = $res->fetch_assoc()) {
        $results['models'][] = [
            'id' => $row['maMau'],
            'title' => $row['tenMau'],
            'brand' => $row['tenHang'] ?? 'N/A',
            'category' => $row['tenLoai'] ?? 'N/A',
            'image' => !empty($row['hinhAnh']) ? 'images/' . $row['hinhAnh'] : null,
            'stock' => intval($row['soLuongTon']),
            'price' => floatval($row['giaMin'] ?? 0),
            'url' => 'chi_tiet_san_pham.php?id=' . $row['maMau']
        ];
        $total_count++;
    }
    $stmt->close();
}

// 2. Tìm kiếm Serial đàn (danserial)
$sql_serials = "SELECT ds.maSerial, ds.soSerial, ds.tinhTrang, ds.trangThai, ds.giaBan, md.maMau, md.tenMau, k.tenKho
                FROM danserial ds
                LEFT JOIN maudan md ON ds.maMau = md.maMau
                LEFT JOIN kho k ON ds.maKho = k.maKho
                WHERE ds.soSerial LIKE ?
                LIMIT 5";
$stmt = $conn->prepare($sql_serials);
if ($stmt) {
    $stmt->bind_param("s", $like);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($row = $res->fetch_assoc()) {
        $results['serials'][] = [
            'id' => $row['maSerial'],
            'serial' => $row['soSerial'],
            'model' => $row['tenMau'] ?? 'N/A',
            'warehouse' => $row['tenKho'] ?? 'Kho chung',
            'status' => $row['trangThai'] ?? 'Trong kho',
            'price' => floatval($row['giaBan'] ?? 0),
            'url' => 'tonkho_hientai.php?q=' . urlencode($row['soSerial'])
        ];
        $total_count++;
    }
    $stmt->close();
}

// 3. Tìm kiếm Hóa đơn (hoadon)
$sql_invoices = "SELECT hd.maHoaDon, hd.ngayLap, hd.tongTien, hd.trangThai, kh.hoTen, kh.soDienThoai
                 FROM hoadon hd
                 LEFT JOIN khachhang kh ON hd.maKhachHang = kh.maKhachHang
                 WHERE hd.maHoaDon = ? OR kh.hoTen LIKE ? OR kh.soDienThoai LIKE ?
                 ORDER BY hd.maHoaDon DESC
                 LIMIT 4";
$stmt = $conn->prepare($sql_invoices);
if ($stmt) {
    $stmt->bind_param("iss", $numVal, $like, $like);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($row = $res->fetch_assoc()) {
        $results['invoices'][] = [
            'id' => $row['maHoaDon'],
            'code' => 'HD#' . $row['maHoaDon'],
            'customer' => $row['hoTen'] ?? 'Khách lẻ',
            'phone' => $row['soDienThoai'] ?? '',
            'total' => floatval($row['tongTien'] ?? 0),
            'date' => date('d/m/Y H:i', strtotime($row['ngayLap'])),
            'status' => $row['trangThai'] ?? 'Hoàn thành',
            'url' => 'hoadon_action.php?id=' . $row['maHoaDon']
        ];
        $total_count++;
    }
    $stmt->close();
}

// 4. Tìm kiếm Phiếu nhập kho (phieunhap)
$sql_import = "SELECT pn.maPhieuNhap, pn.ngayNhap, pn.tongTienNhap, pn.trangThai, pn.soHoaDonNCC, ncc.tenNCC
               FROM phieunhap pn
               LEFT JOIN nhacungcap ncc ON pn.maNCC = ncc.maNCC
               WHERE pn.maPhieuNhap = ? OR pn.soHoaDonNCC LIKE ? OR ncc.tenNCC LIKE ?
               ORDER BY pn.maPhieuNhap DESC
               LIMIT 4";
$stmt = $conn->prepare($sql_import);
if ($stmt) {
    $stmt->bind_param("iss", $numVal, $like, $like);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($row = $res->fetch_assoc()) {
        $results['import_slips'][] = [
            'id' => $row['maPhieuNhap'],
            'code' => 'PN#' . $row['maPhieuNhap'],
            'supplier' => $row['tenNCC'] ?? 'N/A',
            'invoice_no' => $row['soHoaDonNCC'] ?? '',
            'total' => floatval($row['tongTienNhap'] ?? 0),
            'date' => date('d/m/Y', strtotime($row['ngayNhap'])),
            'status' => $row['trangThai'],
            'url' => 'sua_phieunhap.php?id=' . $row['maPhieuNhap']
        ];
        $total_count++;
    }
    $stmt->close();
}

// 5. Tìm kiếm Phiếu xuất kho (phieuxuat)
$sql_export = "SELECT px.maPhieuXuat, px.ngayXuat, px.lyDoXuat, px.trangThai, px.nguoiNhanHang, px.donViNhan
               FROM phieuxuat px
               WHERE px.maPhieuXuat = ? OR px.nguoiNhanHang LIKE ? OR px.donViNhan LIKE ? OR px.lyDoXuat LIKE ?
               ORDER BY px.maPhieuXuat DESC
               LIMIT 4";
$stmt = $conn->prepare($sql_export);
if ($stmt) {
    $stmt->bind_param("isss", $numVal, $like, $like, $like);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($row = $res->fetch_assoc()) {
        $results['export_slips'][] = [
            'id' => $row['maPhieuXuat'],
            'code' => 'PX#' . $row['maPhieuXuat'],
            'recipient' => !empty($row['nguoiNhanHang']) ? $row['nguoiNhanHang'] : ($row['donViNhan'] ?? 'N/A'),
            'reason' => $row['lyDoXuat'] ?? 'Xuất kho',
            'date' => date('d/m/Y', strtotime($row['ngayXuat'])),
            'status' => $row['trangThai'],
            'url' => 'sua_phieuxuat.php?id=' . $row['maPhieuXuat']
        ];
        $total_count++;
    }
    $stmt->close();
}

// 6. Tìm kiếm Khách hàng (khachhang)
$sql_customers = "SELECT maKhachHang, hoTen, soDienThoai, emailKH, diaChi
                  FROM khachhang
                  WHERE hoTen LIKE ? OR soDienThoai LIKE ? OR emailKH LIKE ?
                  LIMIT 4";
$stmt = $conn->prepare($sql_customers);
if ($stmt) {
    $stmt->bind_param("sss", $like, $like, $like);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($row = $res->fetch_assoc()) {
        $results['customers'][] = [
            'id' => $row['maKhachHang'],
            'name' => $row['hoTen'],
            'phone' => $row['soDienThoai'] ?? '',
            'address' => $row['diaChi'] ?? '',
            'url' => 'khachhang.php?search=' . urlencode($row['soDienThoai'] ?: $row['hoTen'])
        ];
        $total_count++;
    }
    $stmt->close();
}

echo json_encode([
    'success' => true,
    'query' => $query,
    'total' => $total_count,
    'results' => $results
], JSON_UNESCAPED_UNICODE);
