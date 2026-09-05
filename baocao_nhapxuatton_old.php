<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'db.php';
require_once 'functions.php';

// Kiểm tra quyền (Admin, Kế toán, Thủ kho)
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role_id'], [1, 2])) {
    header("Location: index.php");
    exit();
}

$title = 'Báo cáo Nhập Xuất Tồn';

// Mặc định là tháng hiện tại
$start_of_month = date('Y-m-01');
$end_of_month = date('Y-m-t');

$startDate = isset($_GET['tu_ngay']) ? trim($_GET['tu_ngay']) : $start_of_month;
$endDate = isset($_GET['den_ngay']) ? trim($_GET['den_ngay']) : $end_of_month;

// Validate dates (Y-m-d)
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $startDate)) {
    $startDate = $start_of_month;
}
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $endDate)) {
    $endDate = $end_of_month;
}

$startDate = $conn->real_escape_string($startDate);
$endDate = $conn->real_escape_string($endDate);

$maKhoFilter = isset($_GET['ma_kho']) ? intval($_GET['ma_kho']) : 0;
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

// Lấy danh sách kho để hiển thị vào select
$khos = $conn->query("SELECT * FROM kho");

// Xây dựng câu truy vấn Lấy danh sách Mẫu Đàn
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
$tongTonDau = 0;
$tongNhap = 0;
$tongXuat = 0;
$tongTonCuoi = 0;

while ($mau = $resMau->fetch_assoc()) {
    $maMau = $mau['maMau'];
    
    // 1. Tồn hiện tại (Trong DB)
    $q_ton = "SELECT COUNT(*) as ton FROM danserial WHERE maMau = $maMau AND trangThai IN ('Trong kho', 'Chờ xuất', 'Chờ giao', 'Đang điều chuyển')";
    if ($maKhoFilter > 0) $q_ton .= " AND maKho = $maKhoFilter";
    $tonHienTai = $conn->query($q_ton)->fetch_assoc()['ton'];
    
    // 2. Xuất trong kỳ
    $q_xuat = "SELECT COUNT(ct.maSerial) as sl 
               FROM chitietphieuxuat ct 
               JOIN phieuxuat px ON ct.maPhieuXuat = px.maPhieuXuat 
               JOIN danserial ds ON ct.maSerial = ds.maSerial 
               WHERE px.trangThai = 'Hoàn thành' 
               AND DATE(px.ngayXuat) BETWEEN '$startDate' AND '$endDate' 
               AND ds.maMau = $maMau";
    if ($maKhoFilter > 0) $q_xuat .= " AND px.maKho = $maKhoFilter";
    $xuatTrongKy = $conn->query($q_xuat)->fetch_assoc()['sl'];
    
    $q_xuat_dc = "SELECT COUNT(ct.maSerial) as sl 
                  FROM chitietdieuchuyen ct 
                  JOIN phieudieuchuyen dc ON ct.maPhieuDC = dc.maPhieuDC 
                  JOIN danserial ds ON ct.maSerial = ds.maSerial 
                  WHERE dc.trangThai = 'Hoàn thành' 
                  AND DATE(dc.ngayTao) BETWEEN '$startDate' AND '$endDate' 
                  AND ds.maMau = $maMau";
    if ($maKhoFilter > 0) $q_xuat_dc .= " AND dc.maKhoXuat = $maKhoFilter";
    $xuatTrongKy += $conn->query($q_xuat_dc)->fetch_assoc()['sl'];
    
    // 3. Nhập trong kỳ
    $q_nhap = "SELECT COUNT(ct.maSerial) as sl 
               FROM chitietphieunhap ct 
               JOIN phieunhap pn ON ct.maPhieuNhap = pn.maPhieuNhap 
               JOIN danserial ds ON ct.maSerial = ds.maSerial 
               WHERE pn.trangThai = 'Hoàn thành' 
               AND DATE(pn.ngayNhap) BETWEEN '$startDate' AND '$endDate' 
               AND ds.maMau = $maMau";
    if ($maKhoFilter > 0) $q_nhap .= " AND pn.maKho = $maKhoFilter";
    $nhapTrongKy = $conn->query($q_nhap)->fetch_assoc()['sl'];
    
    $q_nhap_dc = "SELECT COUNT(ct.maSerial) as sl 
                  FROM chitietdieuchuyen ct 
                  JOIN phieudieuchuyen dc ON ct.maPhieuDC = dc.maPhieuDC 
                  JOIN danserial ds ON ct.maSerial = ds.maSerial 
                  WHERE dc.trangThai = 'Hoàn thành' 
                  AND DATE(dc.ngayTao) BETWEEN '$startDate' AND '$endDate' 
                  AND ds.maMau = $maMau";
    if ($maKhoFilter > 0) $q_nhap_dc .= " AND dc.maKhoNhap = $maKhoFilter";
    $nhapTrongKy += $conn->query($q_nhap_dc)->fetch_assoc()['sl'];
    
    // 4. Xuất sau kỳ
    $q_xuat_sau = "SELECT COUNT(ct.maSerial) as sl 
                   FROM chitietphieuxuat ct 
                   JOIN phieuxuat px ON ct.maPhieuXuat = px.maPhieuXuat 
                   JOIN danserial ds ON ct.maSerial = ds.maSerial 
                   WHERE px.trangThai = 'Hoàn thành' 
                   AND DATE(px.ngayXuat) > '$endDate' 
                   AND ds.maMau = $maMau";
    if ($maKhoFilter > 0) $q_xuat_sau .= " AND px.maKho = $maKhoFilter";
    $xuatSauKy = $conn->query($q_xuat_sau)->fetch_assoc()['sl'];
    
    $q_xuat_dc_sau = "SELECT COUNT(ct.maSerial) as sl 
                      FROM chitietdieuchuyen ct 
                      JOIN phieudieuchuyen dc ON ct.maPhieuDC = dc.maPhieuDC 
                      JOIN danserial ds ON ct.maSerial = ds.maSerial 
                      WHERE dc.trangThai = 'Hoàn thành' 
                      AND DATE(dc.ngayTao) > '$endDate' 
                      AND ds.maMau = $maMau";
    if ($maKhoFilter > 0) $q_xuat_dc_sau .= " AND dc.maKhoXuat = $maKhoFilter";
    $xuatSauKy += $conn->query($q_xuat_dc_sau)->fetch_assoc()['sl'];
    
    // 5. Nhập sau kỳ
    $q_nhap_sau = "SELECT COUNT(ct.maSerial) as sl 
                   FROM chitietphieunhap ct 
                   JOIN phieunhap pn ON ct.maPhieuNhap = pn.maPhieuNhap 
                   JOIN danserial ds ON ct.maSerial = ds.maSerial 
                   WHERE pn.trangThai = 'Hoàn thành' 
                   AND DATE(pn.ngayNhap) > '$endDate' 
                   AND ds.maMau = $maMau";
    if ($maKhoFilter > 0) $q_nhap_sau .= " AND pn.maKho = $maKhoFilter";
    $nhapSauKy = $conn->query($q_nhap_sau)->fetch_assoc()['sl'];
    
    $q_nhap_dc_sau = "SELECT COUNT(ct.maSerial) as sl 
                      FROM chitietdieuchuyen ct 
                      JOIN phieudieuchuyen dc ON ct.maPhieuDC = dc.maPhieuDC 
                      JOIN danserial ds ON ct.maSerial = ds.maSerial 
                      WHERE dc.trangThai = 'Hoàn thành' 
                      AND DATE(dc.ngayTao) > '$endDate' 
                      AND ds.maMau = $maMau";
    if ($maKhoFilter > 0) $q_nhap_dc_sau .= " AND dc.maKhoNhap = $maKhoFilter";
    $nhapSauKy += $conn->query($q_nhap_dc_sau)->fetch_assoc()['sl'];
    
    // TÍNH TOÁN
    $tonCuoiKy = $tonHienTai + $xuatSauKy - $nhapSauKy;
    $tonDauKy = $tonCuoiKy + $xuatTrongKy - $nhapTrongKy;
    
    if ($tonDauKy > 0 || $nhapTrongKy > 0 || $xuatTrongKy > 0 || $tonCuoiKy > 0) {
        $report[] = [
            'maMau' => $maMau,
            'tenMau' => $mau['tenMau'],
            'tenLoai' => $mau['tenLoai'],
            'tenHang' => $mau['tenHang'],
            'giaBan' => $mau['giaBan'],
            'tonDauKy' => $tonDauKy,
            'nhapTrongKy' => $nhapTrongKy,
            'xuatTrongKy' => $xuatTrongKy,
            'tonCuoiKy' => $tonCuoiKy,
        ];
        
        $tongTonDau += $tonDauKy;
        $tongNhap += $nhapTrongKy;
        $tongXuat += $xuatTrongKy;
        $tongTonCuoi += $tonCuoiKy;
    }
}
?>
<?php include 'includes/header.php'; ?>
<?php include 'includes/sidebar.php'; ?>

<style>
    .filter-card {
        background: var(--bg-card);
        padding: 24px;
        border-radius: var(--radius-xl);
        border: 1px solid var(--glass-border);
        margin-bottom: 24px;
        display: flex;
        flex-wrap: wrap;
        gap: 20px;
        align-items: flex-end;
    }
    
    .filter-group { display: flex; flex-direction: column; gap: 8px; flex: 1; min-width: 200px; }
    .filter-group label { font-size: 13px; font-weight: 600; color: var(--text-secondary); text-transform: uppercase; }
    
    .filter-input {
        padding: 12px 16px;
        border-radius: var(--radius-md);
        border: 1px solid var(--glass-border);
        background: rgba(0,0,0,0.1);
        color: var(--text-primary);
        font-family: inherit;
        outline: none;
    }
    .filter-input:focus { border-color: var(--accent); }
    .filter-input option { background: var(--bg-secondary); }
    
    .btn-filter {
        background: var(--accent);
        color: white;
        padding: 12px 24px;
        border: none;
        border-radius: var(--radius-md);
        font-weight: 600;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        height: 44px;
        transition: 0.3s;
    }
    .btn-filter:hover { background: var(--accent-secondary); box-shadow: 0 4px 12px rgba(99, 102, 241, 0.4); }
    
    .btn-export {
        background: rgba(16, 185, 129, 0.1);
        color: #10b981;
        border: 1px solid rgba(16, 185, 129, 0.3);
        padding: 12px 24px;
        border-radius: var(--radius-md);
        font-weight: 600;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        height: 44px;
        transition: 0.3s;
    }
    .btn-export:hover { background: rgba(16, 185, 129, 0.2); }
    
    .report-table th.number, .report-table td.number { text-align: center; }
    .report-table th.money, .report-table td.money { text-align: right; }
    
    .summary-cards { display: grid; grid-template-columns: repeat(4, 1fr); gap: 20px; margin-bottom: 24px; }
    .summary-card {
        background: var(--bg-card);
        padding: 24px;
        border-radius: var(--radius-xl);
        border: 1px solid var(--glass-border);
        display: flex; flex-direction: column; gap: 8px;
    }
    .summary-card span { font-size: 14px; font-weight: 600; color: var(--text-secondary); text-transform: uppercase; }
    .summary-card strong { font-size: 28px; font-weight: 700; color: var(--text-primary); }
    .summary-card.accent { border-bottom: 4px solid var(--accent); }
</style>

<div class="main-wrapper">
    <?php include 'includes/topbar.php'; ?>

    <div class="content">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;">
            <h2 style="margin: 0;">Báo Cáo Nhập Xuất Tồn</h2>
            <button class="btn-export" onclick="window.print()"><span class="material-symbols-rounded">print</span> In báo cáo</button>
        </div>

        <form method="GET" class="filter-card">
            <div class="filter-group">
                <label>Từ ngày</label>
                <input type="date" name="tu_ngay" class="filter-input" value="<?= $startDate ?>" max="<?= $endDate ?>">
            </div>
            <div class="filter-group">
                <label>Đến ngày</label>
                <input type="date" name="den_ngay" class="filter-input" value="<?= $endDate ?>" min="<?= $startDate ?>">
            </div>
            <div class="filter-group">
                <label>Kho hàng</label>
                <select name="ma_kho" class="filter-input">
                    <option value="0">-- Tất cả Kho --</option>
                    <?php while($k = $khos->fetch_assoc()): ?>
                        <option value="<?= $k['maKho'] ?>" <?= $maKhoFilter == $k['maKho'] ? 'selected' : '' ?>><?= $k['tenKho'] ?></option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div class="filter-group">
                <label>Tìm kiếm sản phẩm</label>
                <input type="text" name="search" class="filter-input" placeholder="Tên sản phẩm..." value="<?= htmlspecialchars($search) ?>">
            </div>
            <button type="submit" class="btn-filter"><span class="material-symbols-rounded">filter_list</span> Lọc dữ liệu</button>
        </form>

        <div class="summary-cards">
            <div class="summary-card">
                <span>Tổng Tồn Đầu Kỳ</span>
                <strong><?= number_format($tongTonDau) ?></strong>
            </div>
            <div class="summary-card">
                <span>Tổng Nhập Trong Kỳ</span>
                <strong style="color: #34d399;"><?= number_format($tongNhap) ?></strong>
            </div>
            <div class="summary-card">
                <span>Tổng Xuất Trong Kỳ</span>
                <strong style="color: #f87171;"><?= number_format($tongXuat) ?></strong>
            </div>
            <div class="summary-card accent">
                <span>Tổng Tồn Cuối Kỳ</span>
                <strong><?= number_format($tongTonCuoi) ?></strong>
            </div>
        </div>

        <div class="table-card">
            <table class="data-table report-table">
                <thead>
                    <tr>
                        <th>STT</th>
                        <th>Mã Đàn</th>
                        <th>Tên Mẫu Đàn</th>
                        <th>Thương hiệu / Loại</th>
                        <th class="money">Giá bán HT</th>
                        <th class="number" style="background: rgba(255,255,255,0.02);">Tồn Đầu</th>
                        <th class="number" style="color: #34d399; background: rgba(52,211,153,0.05);">Nhập</th>
                        <th class="number" style="color: #f87171; background: rgba(248,113,113,0.05);">Xuất</th>
                        <th class="number" style="background: rgba(99,102,241,0.05); color: var(--accent);">Tồn Cuối</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(count($report) > 0): ?>
                        <?php foreach($report as $index => $row): ?>
                        <tr>
                            <td><?= $index + 1 ?></td>
                            <td><span class="badge" style="background: rgba(255,255,255,0.1); color: white;">#M<?= $row['maMau'] ?></span></td>
                            <td style="font-weight: 500; color: white;"><?= htmlspecialchars($row['tenMau']) ?></td>
                            <td style="font-size: 13px; color: var(--text-muted);"><?= htmlspecialchars($row['tenHang'] . ' / ' . $row['tenLoai']) ?></td>
                            <td class="money"><?= number_format($row['giaBan']) ?>đ</td>
                            
                            <td class="number" style="background: rgba(255,255,255,0.02); font-weight: 600;"><?= $row['tonDauKy'] ?></td>
                            <td class="number" style="color: #34d399; background: rgba(52,211,153,0.05); font-weight: 600;"><?= $row['nhapTrongKy'] ?></td>
                            <td class="number" style="color: #f87171; background: rgba(248,113,113,0.05); font-weight: 600;"><?= $row['xuatTrongKy'] ?></td>
                            <td class="number" style="background: rgba(99,102,241,0.05); color: var(--accent); font-weight: 700; font-size: 16px;"><?= $row['tonCuoiKy'] ?></td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="9" style="text-align: center; padding: 40px; color: var(--text-muted);">
                                <span class="material-symbols-rounded" style="font-size: 48px; opacity: 0.5; margin-bottom: 16px; display: block;">analytics</span>
                                Không có phát sinh dữ liệu nhập xuất tồn trong khoảng thời gian này.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
