<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'db.php';
require_once 'functions.php';

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role_id'], [1, 2, 3])) {
    header("Location: index.php");
    exit();
}

$title = 'Báo cáo Nhập Xuất Tồn (Tối ưu hóa)';

// Export CSV handler
if (isset($_GET['export']) && $_GET['export'] == 'csv') {
    // Note: To avoid header already sent error, the logic needs to be run before HTML output.
}

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

$khos = $conn->query("SELECT * FROM kho");

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
        'giaBan' => $mau['giaBan'],
        'tonHienTai' => 0,
        'xuatTrongKy' => 0,
        'nhapTrongKy' => 0,
        'xuatSauKy' => 0,
        'nhapSauKy' => 0
    ];
}

// Điều kiện kho
$khoFilter = $maKhoFilter > 0 ? "AND ds.maKho = $maKhoFilter" : "";
$khoXuatFilter = $maKhoFilter > 0 ? "AND dc.maKhoXuat = $maKhoFilter" : "";
$khoNhapFilter = $maKhoFilter > 0 ? "AND dc.maKhoNhap = $maKhoFilter" : "";
$pxKhoFilter = $maKhoFilter > 0 ? "AND px.maKho = $maKhoFilter" : "";
$pnKhoFilter = $maKhoFilter > 0 ? "AND pn.maKho = $maKhoFilter" : "";

// --- TỐI ƯU HÓA TRUY VẤN (Gộp theo Mẫu Đàn) ---

// 1. Tồn hiện tại
$res = $conn->query("SELECT maMau, COUNT(*) as sl FROM danserial ds WHERE trangThai IN ('Trong kho', 'Chờ xuất', 'Chờ giao', 'Đang điều chuyển') $khoFilter GROUP BY maMau");
while ($r = $res->fetch_assoc()) if (isset($report[$r['maMau']])) $report[$r['maMau']]['tonHienTai'] = $r['sl'];

// 2. Xuất trong kỳ (Phiếu Xuất)
$res = $conn->query("SELECT ds.maMau, COUNT(ct.maSerial) as sl FROM chitietphieuxuat ct JOIN phieuxuat px ON ct.maPhieuXuat = px.maPhieuXuat JOIN danserial ds ON ct.maSerial = ds.maSerial WHERE px.trangThai = 'Hoàn thành' AND DATE(px.ngayXuat) BETWEEN '$startDate' AND '$endDate' $pxKhoFilter GROUP BY ds.maMau");
while ($r = $res->fetch_assoc()) if (isset($report[$r['maMau']])) $report[$r['maMau']]['xuatTrongKy'] += $r['sl'];

// Xuất trong kỳ (Điều chuyển)
$res = $conn->query("SELECT ds.maMau, COUNT(ct.maSerial) as sl FROM chitietdieuchuyen ct JOIN phieudieuchuyen dc ON ct.maPhieuDC = dc.maPhieuDC JOIN danserial ds ON ct.maSerial = ds.maSerial WHERE dc.trangThai = 'Hoàn thành' AND DATE(dc.ngayTao) BETWEEN '$startDate' AND '$endDate' $khoXuatFilter GROUP BY ds.maMau");
while ($r = $res->fetch_assoc()) if (isset($report[$r['maMau']])) $report[$r['maMau']]['xuatTrongKy'] += $r['sl'];

// 3. Nhập trong kỳ (Phiếu Nhập)
$res = $conn->query("SELECT ds.maMau, COUNT(ct.maSerial) as sl FROM chitietphieunhap ct JOIN phieunhap pn ON ct.maPhieuNhap = pn.maPhieuNhap JOIN danserial ds ON ct.maSerial = ds.maSerial WHERE pn.trangThai = 'Hoàn thành' AND DATE(pn.ngayNhap) BETWEEN '$startDate' AND '$endDate' $pnKhoFilter GROUP BY ds.maMau");
while ($r = $res->fetch_assoc()) if (isset($report[$r['maMau']])) $report[$r['maMau']]['nhapTrongKy'] += $r['sl'];

// Nhập trong kỳ (Điều chuyển)
$res = $conn->query("SELECT ds.maMau, COUNT(ct.maSerial) as sl FROM chitietdieuchuyen ct JOIN phieudieuchuyen dc ON ct.maPhieuDC = dc.maPhieuDC JOIN danserial ds ON ct.maSerial = ds.maSerial WHERE dc.trangThai = 'Hoàn thành' AND DATE(dc.ngayTao) BETWEEN '$startDate' AND '$endDate' $khoNhapFilter GROUP BY ds.maMau");
while ($r = $res->fetch_assoc()) if (isset($report[$r['maMau']])) $report[$r['maMau']]['nhapTrongKy'] += $r['sl'];

// 4. Xuất sau kỳ
$res = $conn->query("SELECT ds.maMau, COUNT(ct.maSerial) as sl FROM chitietphieuxuat ct JOIN phieuxuat px ON ct.maPhieuXuat = px.maPhieuXuat JOIN danserial ds ON ct.maSerial = ds.maSerial WHERE px.trangThai = 'Hoàn thành' AND DATE(px.ngayXuat) > '$endDate' $pxKhoFilter GROUP BY ds.maMau");
while ($r = $res->fetch_assoc()) if (isset($report[$r['maMau']])) $report[$r['maMau']]['xuatSauKy'] += $r['sl'];

$res = $conn->query("SELECT ds.maMau, COUNT(ct.maSerial) as sl FROM chitietdieuchuyen ct JOIN phieudieuchuyen dc ON ct.maPhieuDC = dc.maPhieuDC JOIN danserial ds ON ct.maSerial = ds.maSerial WHERE dc.trangThai = 'Hoàn thành' AND DATE(dc.ngayTao) > '$endDate' $khoXuatFilter GROUP BY ds.maMau");
while ($r = $res->fetch_assoc()) if (isset($report[$r['maMau']])) $report[$r['maMau']]['xuatSauKy'] += $r['sl'];

// 5. Nhập sau kỳ
$res = $conn->query("SELECT ds.maMau, COUNT(ct.maSerial) as sl FROM chitietphieunhap ct JOIN phieunhap pn ON ct.maPhieuNhap = pn.maPhieuNhap JOIN danserial ds ON ct.maSerial = ds.maSerial WHERE pn.trangThai = 'Hoàn thành' AND DATE(pn.ngayNhap) > '$endDate' $pnKhoFilter GROUP BY ds.maMau");
while ($r = $res->fetch_assoc()) if (isset($report[$r['maMau']])) $report[$r['maMau']]['nhapSauKy'] += $r['sl'];

$res = $conn->query("SELECT ds.maMau, COUNT(ct.maSerial) as sl FROM chitietdieuchuyen ct JOIN phieudieuchuyen dc ON ct.maPhieuDC = dc.maPhieuDC JOIN danserial ds ON ct.maSerial = ds.maSerial WHERE dc.trangThai = 'Hoàn thành' AND DATE(dc.ngayTao) > '$endDate' $khoNhapFilter GROUP BY ds.maMau");
while ($r = $res->fetch_assoc()) if (isset($report[$r['maMau']])) $report[$r['maMau']]['nhapSauKy'] += $r['sl'];

// Tổng hợp
$final_report = [];
$tongTonDau = $tongNhap = $tongXuat = $tongTonCuoi = 0;

$chartData_labels = [];
$chartData_nhap = [];
$chartData_xuat = [];
$chartData_ton = [];

// Dữ liệu cho Pie Chart (Theo Hãng)
$brand_inventory = [];

foreach ($report as $r) {
    $tonCuoiKy = $r['tonHienTai'] + $r['xuatSauKy'] - $r['nhapSauKy'];
    $tonDauKy = $tonCuoiKy + $r['xuatTrongKy'] - $r['nhapTrongKy'];
    
    if ($tonDauKy > 0 || $r['nhapTrongKy'] > 0 || $r['xuatTrongKy'] > 0 || $tonCuoiKy > 0) {
        $final_report[] = [
            'maMau' => $r['maMau'],
            'tenMau' => $r['tenMau'],
            'tenLoai' => $r['tenLoai'],
            'tenHang' => $r['tenHang'],
            'giaBan' => $r['giaBan'],
            'tonDauKy' => $tonDauKy,
            'nhapTrongKy' => $r['nhapTrongKy'],
            'xuatTrongKy' => $r['xuatTrongKy'],
            'tonCuoiKy' => $tonCuoiKy,
        ];
        
        $tongTonDau += $tonDauKy;
        $tongNhap += $r['nhapTrongKy'];
        $tongXuat += $r['xuatTrongKy'];
        $tongTonCuoi += $tonCuoiKy;

        // Cho biểu đồ Bar (chỉ lấy những sản phẩm có phát sinh giao dịch)
        if ($r['nhapTrongKy'] > 0 || $r['xuatTrongKy'] > 0) {
            $chartData_labels[] = $r['tenMau'];
            $chartData_nhap[] = $r['nhapTrongKy'];
            $chartData_xuat[] = $r['xuatTrongKy'];
            $chartData_ton[] = $tonCuoiKy;
        }

        // Cho biểu đồ Pie
        if ($tonCuoiKy > 0) {
            $hang = $r['tenHang'] ? $r['tenHang'] : 'Khác';
            if (!isset($brand_inventory[$hang])) $brand_inventory[$hang] = 0;
            $brand_inventory[$hang] += $tonCuoiKy;
        }
    }
}

// Xử lý Xuất CSV
if (isset($_GET['export']) && $_GET['export'] == 'csv') {
    ob_end_clean(); // Clear any existing output
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=baocao_nhapxuatton_'.date('Ymd').'.csv');
    // Add BOM for Excel UTF-8 display
    echo "\xEF\xBB\xBF";
    $output = fopen('php://output', 'w');
    fputcsv($output, ['STT', 'Mã Đàn', 'Tên Đàn', 'Loại', 'Hãng', 'Giá Bán', 'Tồn Đầu Kỳ', 'Nhập Trong Kỳ', 'Xuất Trong Kỳ', 'Tồn Cuối Kỳ']);
    $stt = 1;
    foreach ($final_report as $row) {
        fputcsv($output, [
            $stt++,
            $row['maMau'],
            $row['tenMau'],
            $row['tenLoai'],
            $row['tenHang'],
            $row['giaBan'],
            $row['tonDauKy'],
            $row['nhapTrongKy'],
            $row['xuatTrongKy'],
            $row['tonCuoiKy']
        ]);
    }
    fputcsv($output, ['', '', 'TỔNG CỘNG', '', '', '', $tongTonDau, $tongNhap, $tongXuat, $tongTonCuoi]);
    fclose($output);
    exit();
}
?>

<?php include 'includes/header.php'; ?>
<?php include 'includes/sidebar.php'; ?>

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>


<div class="main-wrapper">
    <?php include 'includes/topbar.php'; ?>

    <div class="content">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;">
            <h2 style="margin: 0; display: flex; align-items: center; gap: 10px;">
                <span class="material-symbols-rounded" style="color: var(--accent); font-size: 32px;">analytics</span> 
                Báo Cáo Tồn Kho
            </h2>
            <div class="btn-action-group">
                <a href="?<?= $_SERVER['QUERY_STRING'] ?>&export=csv" class="btn-custom btn-export-csv">
                    <span class="material-symbols-rounded">download</span> Xuất CSV
                </a>
            </div>
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
            <button type="submit" class="btn-custom btn-filter"><span class="material-symbols-rounded">filter_list</span> Lọc dữ liệu</button>
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

        <div class="charts-container">
            <div class="chart-box">
                <h3><span class="material-symbols-rounded">bar_chart</span> Thống kê Nhập / Xuất theo sản phẩm</h3>
                <canvas id="barChart" style="max-height: 300px;"></canvas>
            </div>
            <div class="chart-box">
                <h3><span class="material-symbols-rounded">pie_chart</span> Cơ cấu Tồn kho theo Hãng</h3>
                <canvas id="pieChart" style="max-height: 300px;"></canvas>
            </div>
        </div>

        <div class="report-table-wrapper">
            <table class="report-table">
                <thead>
                    <tr>
                        <th class="number" style="width: 50px;">STT</th>
                        <th>Sản phẩm</th>
                        <th>Phân loại</th>
                        <th class="number">Đầu kỳ</th>
                        <th class="number cell-nhap">Nhập</th>
                        <th class="number cell-xuat">Xuất</th>
                        <th class="number" style="color: var(--accent);">Cuối kỳ</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $stt = 1;
                    foreach ($final_report as $row): 
                        // Cảnh báo đỏ nếu tồn cuối kỳ < 5
                        $isLow = $row['tonCuoiKy'] > 0 && $row['tonCuoiKy'] < 5;
                        $isZero = $row['tonCuoiKy'] == 0;
                    ?>
                    <tr>
                        <td class="number"><?= $stt++ ?></td>
                        <td>
                            <div style="font-weight: 600;"><?= htmlspecialchars($row['tenMau']) ?></div>
                            <div style="font-size: 12px; color: var(--text-muted);">Mã: <?= $row['maMau'] ?></div>
                        </td>
                        <td>
                            <div><?= htmlspecialchars($row['tenLoai']) ?></div>
                            <div style="font-size: 12px; color: var(--text-muted);"><?= htmlspecialchars($row['tenHang']) ?></div>
                        </td>
                        <td class="number"><?= $row['tonDauKy'] ?></td>
                        <td class="number cell-nhap"><?= $row['nhapTrongKy'] > 0 ? '+'.$row['nhapTrongKy'] : '-' ?></td>
                        <td class="number cell-xuat"><?= $row['xuatTrongKy'] > 0 ? '-'.$row['xuatTrongKy'] : '-' ?></td>
                        <td class="number" style="font-weight: 800; font-size: 16px;">
                            <?= $row['tonCuoiKy'] ?>
                            <?php if ($isLow) echo '<span class="badge-warning">Sắp hết</span>'; ?>
                            <?php if ($isZero) echo '<span class="badge-warning" style="background: rgba(245,158,11,0.15); color: #f59e0b; border-color: rgba(245,158,11,0.3);">Hết hàng</span>'; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    
                    <?php if (count($final_report) == 0): ?>
                    <tr>
                        <td colspan="7" style="text-align: center; padding: 40px; color: var(--text-muted);">
                            <span class="material-symbols-rounded" style="font-size: 48px; opacity: 0.5;">inventory_2</span>
                            <p>Không có dữ liệu nhập xuất tồn trong khoảng thời gian này.</p>
                        </td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
// Chart.js Configuration
Chart.defaults.color = '#94a3b8';
Chart.defaults.font.family = "'Inter', sans-serif";

const labels = <?= json_encode($chartData_labels) ?>;
const nhapData = <?= json_encode($chartData_nhap) ?>;
const xuatData = <?= json_encode($chartData_xuat) ?>;

// Limit to top 15 items for the bar chart to avoid crowding
const maxItems = 15;
const slicedLabels = labels.slice(0, maxItems);
const slicedNhap = nhapData.slice(0, maxItems);
const slicedXuat = xuatData.slice(0, maxItems);

if (slicedLabels.length > 0) {
    const ctxBar = document.getElementById('barChart').getContext('2d');
    new Chart(ctxBar, {
        type: 'bar',
        data: {
            labels: slicedLabels,
            datasets: [
                {
                    label: 'Nhập kho',
                    data: slicedNhap,
                    backgroundColor: 'rgba(52, 211, 153, 0.8)',
                    borderRadius: 4
                },
                {
                    label: 'Xuất kho',
                    data: slicedXuat,
                    backgroundColor: 'rgba(248, 113, 113, 0.8)',
                    borderRadius: 4
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: { beginAtZero: true, grid: { color: 'rgba(255,255,255,0.05)' } },
                x: { grid: { display: false } }
            },
            plugins: {
                legend: { position: 'top' }
            }
        }
    });
} else {
    document.getElementById('barChart').parentElement.innerHTML += '<div style="position:absolute; top:50%; left:50%; transform:translate(-50%,-50%); color:var(--text-muted);">Không có giao dịch</div>';
}

// Pie Chart (Tồn kho theo hãng)
const brandLabels = <?= json_encode(array_keys($brand_inventory)) ?>;
const brandData = <?= json_encode(array_values($brand_inventory)) ?>;
const bgColors = [
    'rgba(99, 102, 241, 0.8)',
    'rgba(236, 72, 153, 0.8)',
    'rgba(245, 158, 11, 0.8)',
    'rgba(16, 185, 129, 0.8)',
    'rgba(56, 189, 248, 0.8)',
    'rgba(168, 85, 247, 0.8)'
];

if (brandLabels.length > 0) {
    const ctxPie = document.getElementById('pieChart').getContext('2d');
    new Chart(ctxPie, {
        type: 'doughnut',
        data: {
            labels: brandLabels,
            datasets: [{
                data: brandData,
                backgroundColor: bgColors,
                borderWidth: 0,
                hoverOffset: 4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { position: 'right' }
            },
            cutout: '65%'
        }
    });
} else {
    document.getElementById('pieChart').parentElement.innerHTML += '<div style="position:absolute; top:50%; left:50%; transform:translate(-50%,-50%); color:var(--text-muted);">Không có tồn kho</div>';
}
</script>

<?php include 'includes/footer.php'; ?>
