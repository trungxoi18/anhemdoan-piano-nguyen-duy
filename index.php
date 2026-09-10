<?php
session_start();
require_once 'db.php'; 

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$fullname = $_SESSION['fullname'];
$role_id = $_SESSION['role_id'];

// KPI queries
$doanh_thu = 0;
$doanh_thu_thuc_te = 0;
$yeu_cau_cho = 0;
$tong_dan = 0;
$tong_gia_tri_kho = 0;
$sap_het = 0;
$don_dang_xu_ly = 0;

// 1. Tổng đàn trong kho và Tổng giá trị
$sql_tong = "SELECT COUNT(maSerial) AS total, SUM(giaNhap) as total_value 
             FROM danserial 
             WHERE trangThai LIKE '%Trong kho%'";
$res_tong = $conn->query($sql_tong);
if($res_tong && $row = $res_tong->fetch_assoc()) { 
    $tong_dan = $row['total'] ?? 0; 
    $tong_gia_tri_kho = $row['total_value'] ?? 0;
}

// 2. Sản phẩm sắp hết
$sql_sap_het = "SELECT COUNT(*) AS total_sap_het FROM (
                    SELECT maMau, COUNT(maSerial) as SL 
                    FROM danserial 
                    WHERE trangThai LIKE '%Trong kho%' 
                    GROUP BY maMau 
                    HAVING SL <= 5
                ) AS bang_tam";
$res_sap_het = $conn->query($sql_sap_het);
if($res_sap_het && $row = $res_sap_het->fetch_assoc()) { 
    $sap_het = $row['total_sap_het'] ?? 0; 
}

// 3. Doanh thu (Tổng doanh thu và Doanh thu đã thu/hoàn thành)
$thang_ht = date('m');
$nam_ht = date('Y');
$sql_dt = "SELECT 
            SUM(CASE WHEN trangThai != 'Đã hủy' THEN tongTien ELSE 0 END) AS total,
            SUM(CASE WHEN trangThai = 'Hoàn thành' OR trangThai = 'Đã giao' THEN tongTien ELSE 0 END) AS total_thuc_te
           FROM hoadon 
           WHERE MONTH(ngayLap) = '$thang_ht' AND YEAR(ngayLap) = '$nam_ht'";
$res_dt = $conn->query($sql_dt);
if($res_dt && $row = $res_dt->fetch_assoc()) { 
    $doanh_thu = $row['total'] ?? 0; 
    $doanh_thu_thuc_te = $row['total_thuc_te'] ?? 0;
}

// 4. Yêu cầu chờ duyệt (Bao gồm Hóa đơn, Phiếu nhập, Phiếu xuất)
$sql_yc = "SELECT 
            (SELECT COUNT(*) FROM HoaDon WHERE trangThai = 'Chờ duyệt') +
            (SELECT COUNT(*) FROM PhieuNhap WHERE trangThai = 'Chờ duyệt') + 
            (SELECT COUNT(*) FROM PhieuXuat WHERE trangThai = 'Chờ duyệt') AS total";
$res_yc = $conn->query($sql_yc);
if($res_yc && $row = $res_yc->fetch_assoc()) { 
    $yeu_cau_cho = $row['total'] ?? 0; 
}

// 5. Đơn hàng đang xử lý (Chờ duyệt, Chờ giao, Đang giao)
$sql_dh = "SELECT COUNT(maHoaDon) AS total FROM HoaDon WHERE trangThai IN ('Chờ duyệt', 'Chờ giao', 'Đang giao', 'ðang giao')";
$res_dh = $conn->query($sql_dh);
if($res_dh && $row = $res_dh->fetch_assoc()) { 
    $don_dang_xu_ly = $row['total'] ?? 0; 
}

// 6. Badges cho Quick Access
$badge_duyet_phieu = $yeu_cau_cho;
$badge_hoadon_cho = 0;
$sql_hd_cho = "SELECT COUNT(*) as total FROM HoaDon WHERE trangThai = 'Chờ giao'";
$res_hd_cho = $conn->query($sql_hd_cho);
if($res_hd_cho && $row = $res_hd_cho->fetch_assoc()) { 
    $badge_hoadon_cho = $row['total'] ?? 0; 
}

// 7. Dữ liệu Biểu đồ Đa chiều Dashboard (Multi-dimensional Analytics)
// 7.1. Doanh thu 6 tháng gần nhất
$chart_rev_labels = [];
$chart_rev_data = [];
$chart_order_counts = [];
$monthly_stats = [];

for ($i = 5; $i >= 0; $i--) {
    $time = strtotime("-$i months");
    $m = date('n', $time);
    $y = date('Y', $time);
    $key = "$y-$m";
    $label = "Tháng $m/$y";
    $chart_rev_labels[] = $label;
    $monthly_stats[$key] = [
        'label' => $label,
        'revenue' => 0,
        'orders' => 0
    ];
}

$sql_rev_6m = "SELECT MONTH(ngayLap) as m, YEAR(ngayLap) as y, SUM(tongTien) as total_rev, COUNT(maHoaDon) as total_count 
               FROM hoadon 
               WHERE trangThai != 'Đã hủy' AND ngayLap >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
               GROUP BY y, m";
$res_rev_6m = $conn->query($sql_rev_6m);
if ($res_rev_6m) {
    while ($r = $res_rev_6m->fetch_assoc()) {
        $key = "{$r['y']}-{$r['m']}";
        if (isset($monthly_stats[$key])) {
            $monthly_stats[$key]['revenue'] = (float)$r['total_rev'];
            $monthly_stats[$key]['orders'] = (int)$r['total_count'];
        }
    }
}

$total_rev_sum = 0;
$max_rev_month = '';
$max_rev_val = 0;
foreach ($monthly_stats as $st) {
    $chart_rev_data[] = $st['revenue'];
    $chart_order_counts[] = $st['orders'];
    $total_rev_sum += $st['revenue'];
    if ($st['revenue'] > $max_rev_val) {
        $max_rev_val = $st['revenue'];
        $max_rev_month = $st['label'];
    }
}

// Fallback nếu 6 tháng từ NOW() trống, tự động lấy 6 mốc thời gian có dữ liệu trong CSDL
if ($total_rev_sum == 0) {
    $chart_rev_labels = [];
    $chart_rev_data = [];
    $chart_order_counts = [];
    $sql_fb = "SELECT DATE_FORMAT(ngayLap, 'Tháng %m/%Y') as lbl, SUM(tongTien) as total_rev, COUNT(maHoaDon) as total_count, MAX(ngayLap) as max_date
               FROM hoadon 
               WHERE trangThai != 'Đã hủy'
               GROUP BY DATE_FORMAT(ngayLap, '%Y-%m')
               ORDER BY max_date ASC
               LIMIT 6";
    $res_fb = $conn->query($sql_fb);
    if ($res_fb && $res_fb->num_rows > 0) {
        while ($rf = $res_fb->fetch_assoc()) {
            $chart_rev_labels[] = $rf['lbl'];
            $chart_rev_data[] = (float)$rf['total_rev'];
            $chart_order_counts[] = (int)$rf['total_count'];
            $total_rev_sum += (float)$rf['total_rev'];
            if ((float)$rf['total_rev'] > $max_rev_val) {
                $max_rev_val = (float)$rf['total_rev'];
                $max_rev_month = $rf['lbl'];
            }
        }
    }
}

// 7.2. Tỷ trọng Tồn kho theo Hãng sản xuất
$chart_brand_labels = [];
$chart_brand_data = [];
$chart_brand_colors = ['#6366f1', '#f59e0b', '#ec4899', '#10b981', '#06b6d4', '#8b5cf6', '#f97316', '#64748b'];
$brand_stock_list = [];

$res_brands = $conn->query("SELECT hd.tenHang, COUNT(ds.maSerial) as total_stock 
                            FROM danserial ds 
                            JOIN maudan md ON ds.maMau = md.maMau 
                            JOIN hangdan hd ON md.maHang = hd.maHang 
                            WHERE ds.trangThai LIKE '%Trong kho%' 
                            GROUP BY hd.tenHang 
                            ORDER BY total_stock DESC 
                            LIMIT 8");
if ($res_brands) {
    while ($rb = $res_brands->fetch_assoc()) {
        $chart_brand_labels[] = $rb['tenHang'];
        $chart_brand_data[] = (int)$rb['total_stock'];
        $brand_stock_list[] = [
            'name' => $rb['tenHang'],
            'stock' => (int)$rb['total_stock']
        ];
    }
}

// 7.3. Top 5 Mẫu đàn bán chạy nhất
$chart_top_labels = [];
$chart_top_sold = [];
$chart_top_revenue = [];
$res_top = $conn->query("SELECT md.tenMau, hd.tenHang, COUNT(ct.maSerial) as total_sold, SUM(ds.giaBan) as total_revenue
                         FROM chitiethoadon ct
                         JOIN danserial ds ON ct.maSerial = ds.maSerial
                         JOIN maudan md ON ds.maMau = md.maMau
                         JOIN hangdan hd ON md.maHang = hd.maHang
                         JOIN hoadon h ON ct.maHoaDon = h.maHoaDon
                         WHERE h.trangThai != 'Đã hủy'
                         GROUP BY md.maMau
                         ORDER BY total_sold DESC, total_revenue DESC
                         LIMIT 5");
if ($res_top) {
    while ($rt = $res_top->fetch_assoc()) {
        $chart_top_labels[] = $rt['tenMau'];
        $chart_top_sold[] = (int)$rt['total_sold'];
        $chart_top_revenue[] = (float)($rt['total_revenue'] ?? 0);
    }
}

// 8. Widget Việc Cần Làm (Lists)
// Cho Admin: Phiếu chờ duyệt
$list_cho_duyet = [];
if ($role_id == 1) {
    $res_cd = $conn->query("(SELECT 'Hóa đơn' as loai, maHoaDon as id, ngayLap as thoiGian FROM hoadon WHERE trangThai = 'Chờ duyệt')
                            UNION ALL
                            (SELECT 'Phiếu xuất' as loai, maPhieuXuat as id, ngayXuat as thoiGian FROM phieuxuat WHERE trangThai = 'Chờ duyệt')
                            UNION ALL
                            (SELECT 'Phiếu nhập' as loai, maPhieuNhap as id, ngayNhap as thoiGian FROM phieunhap WHERE trangThai = 'Chờ duyệt')
                            ORDER BY thoiGian DESC LIMIT 5");
    if ($res_cd) while ($r = $res_cd->fetch_assoc()) $list_cho_duyet[] = $r;
}
// Cho Thủ kho: Hóa đơn chờ giao
$list_hoadon_cho = [];
$res_hc = $conn->query("SELECT maHoaDon, ngayLap, tongTien FROM hoadon WHERE trangThai = 'Chờ giao' ORDER BY ngayLap ASC LIMIT 5");
if ($res_hc) while ($r = $res_hc->fetch_assoc()) $list_hoadon_cho[] = $r;

// 9. Nhật ký hoạt động gần đây (Admin xem tất cả, Thủ kho chỉ xem của chính mình)
$recent_logs = [];
$sql_logs = "SELECT n.loaiHanhDong, n.chiTiet, n.ngayTao, nv.hoTen 
             FROM nhatkyhethong n 
             JOIN taikhoan t ON n.maTaiKhoan = t.maTaiKhoan 
             JOIN nhanvien nv ON t.maNhanVien = nv.maNhanVien ";
if ($role_id != 1) {
    $sql_logs .= " WHERE n.maTaiKhoan = " . intval($user_id) . " ";
}
$sql_logs .= " ORDER BY n.ngayTao DESC LIMIT 8";
$res_logs = $conn->query($sql_logs);
if ($res_logs) while ($r = $res_logs->fetch_assoc()) $recent_logs[] = $r;

// Fetch Featured Products (4 newest)
$featured_products = [];
$sql_featured = "SELECT md.tenMau, md.hinhAnh, hd.tenHang 
                 FROM MauDan md 
                 JOIN HangDan hd ON md.maHang = hd.maHang 
                 ORDER BY md.maMau DESC LIMIT 4";
$res_feat = $conn->query($sql_featured);
if ($res_feat) {
    while ($row = $res_feat->fetch_assoc()) {
        $featured_products[] = $row;
    }
}

// Fetch Active Promotions
$active_promos = [];
$sql_promos = "SELECT * FROM chuongtrinhkhuyenmai WHERE trangThai = 'Đang diễn ra' ORDER BY ngayBatDau DESC LIMIT 3";
$res_promos = $conn->query($sql_promos);
if ($res_promos) {
    while ($row = $res_promos->fetch_assoc()) {
        $active_promos[] = $row;
    }
}

// Fetch Warranty Policies
$recent_policies = [];
$sql_policies = "SELECT * FROM chinhsachbaohanh ORDER BY ngayCapNhat DESC LIMIT 3";
$res_policies = $conn->query($sql_policies);
if ($res_policies) {
    while ($row = $res_policies->fetch_assoc()) {
        $recent_policies[] = $row;
    }
}
?>

<?php include 'includes/header.php'; ?>
<?php include 'includes/sidebar.php'; ?>

<div class="main-wrapper">
    
    <?php include 'includes/topbar.php'; ?>

    <div class="content">
        <!-- 1. Hero Banner -->
        <?php if(isset($_SESSION['flash_success'])): ?>
            <div class="alert alert-success" style="background: var(--success-bg); color: var(--success); border: 1px solid rgba(52,211,153,0.3); padding:15px; border-radius:12px; margin-bottom:24px; display: flex; align-items: center; gap: 10px; animation: fadeInUp 0.5s ease;">
                <span class="material-symbols-rounded">check_circle</span>
                <?php echo $_SESSION['flash_success']; unset($_SESSION['flash_success']); ?>
            </div>
        <?php endif; ?>
        <div class="hero-banner">
            <h1><?php echo __('hello'); ?> <?php echo htmlspecialchars($fullname); ?>!</h1>
            <p>Chào mừng trở lại bảng điều khiển hệ thống quản lý. Tại đây bạn có thể kiểm soát mọi hoạt động kinh doanh, tồn kho và các dịch vụ sau bán hàng một cách trực quan nhất.</p>
        </div>

        <!-- 2. KPI Row (Premium UI) -->
        <div class="premium-kpi-row">
            <?php if ($role_id == 1) { ?>   
                <div class="premium-kpi-card" style="--card-color: #10b981; --card-bg-light: #ecfdf5; --card-shadow-hover: rgba(16, 185, 129, 0.2);">
                    <div class="premium-kpi-icon">
                        <span class="material-symbols-rounded">payments</span>
                    </div>
                    <div class="premium-kpi-info">
                        <p><?php echo __('monthly_revenue'); ?></p>
                        <h3><?php echo number_format($doanh_thu, 0, ',', '.'); ?>đ</h3>
                        <span class="kpi-sub">
                            <span class="material-symbols-rounded" style="font-size: 16px; color: #10b981;">trending_up</span>
                            Thực thu: <?php echo number_format($doanh_thu_thuc_te, 0, ',', '.'); ?>đ
                        </span>
                    </div>
                </div>
                <div class="premium-kpi-card" style="--card-color: #ef4444; --card-bg-light: #fef2f2; --card-shadow-hover: rgba(239, 68, 68, 0.2); cursor: pointer;" onclick="window.location.href='duyet_phieu.php'">
                    <div class="premium-kpi-icon">
                        <span class="material-symbols-rounded">pending_actions</span>
                    </div>
                    <div class="premium-kpi-info">
                        <p><?php echo __('pending_requests'); ?></p>
                        <h3><?php echo str_pad($yeu_cau_cho, 2, '0', STR_PAD_LEFT); ?></h3>
                        <span class="kpi-sub">
                            <span class="material-symbols-rounded" style="font-size: 16px; color: #ef4444;">error</span>
                            Bao gồm hóa đơn & phiếu
                        </span>
                    </div>
                </div>
            <?php } ?>

            <?php if ($role_id == 1 || $role_id == 2) {  ?>
                <div class="premium-kpi-card" style="--card-color: #3b82f6; --card-bg-light: #eff6ff; --card-shadow-hover: rgba(59, 130, 246, 0.2);">
                    <div class="premium-kpi-icon">
                        <span class="material-symbols-rounded">inventory_2</span>
                    </div>
                    <div class="premium-kpi-info">
                        <p><?php echo __('total_pianos'); ?></p>
                        <h3><?php echo number_format($tong_dan, 0, ',', '.'); ?></h3>
                        <span class="kpi-sub">
                            <span class="material-symbols-rounded" style="font-size: 16px; color: #3b82f6;">account_balance_wallet</span>
                            Vốn: <?php echo number_format($tong_gia_tri_kho, 0, ',', '.'); ?>đ
                        </span>
                    </div>
                </div>
                <div class="premium-kpi-card" style="--card-color: #f59e0b; --card-bg-light: #fffbeb; --card-shadow-hover: rgba(245, 158, 11, 0.2);">
                    <div class="premium-kpi-icon">
                        <span class="material-symbols-rounded">priority_high</span>
                    </div>
                    <div class="premium-kpi-info">
                        <p><?php echo __('low_stock'); ?></p>
                        <h3><?php echo str_pad($sap_het, 2, '0', STR_PAD_LEFT); ?></h3>
                        <span class="kpi-sub">
                            <span class="material-symbols-rounded" style="font-size: 16px; color: #f59e0b;">warning</span>
                            Mẫu mã &lt;= 5 serial
                        </span>
                    </div>
                </div>
            <?php } ?>

            <?php if ($role_id == 1 || $role_id == 2) {  ?>
                <div class="premium-kpi-card" style="--card-color: #8b5cf6; --card-bg-light: #f5f3ff; --card-shadow-hover: rgba(139, 92, 246, 0.2);">
                    <div class="premium-kpi-icon">
                        <span class="material-symbols-rounded">local_shipping</span>
                    </div>
                    <div class="premium-kpi-info">
                        <p>Đơn đang xử lý</p>
                        <h3><?php echo str_pad($don_dang_xu_ly, 2, '0', STR_PAD_LEFT); ?></h3>
                        <span class="kpi-sub">
                            <span class="material-symbols-rounded" style="font-size: 16px; color: #8b5cf6;">hourglass_top</span>
                            Chờ duyệt / Đang giao
                        </span>
                    </div>
                </div>
            <?php } ?>
        </div>

        <!-- 3. Real Dashboard (Widgets + Chart + Timeline) -->
        <div class="dashboard-main-grid">
            
            <!-- Cột trái: Chart & Widgets -->
            <div style="display: flex; flex-direction: column; gap: 24px;">
                <!-- Widgets Việc cần làm -->
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px;">
                    <?php if ($role_id == 1): ?>
                    <div style="background: var(--bg-card); border-radius: var(--radius-lg); padding: 20px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); border: 1px solid rgba(139,92,246,0.15); display: flex; flex-direction: column; justify-content: space-between;">
                        <div>
                            <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 14px;">
                                <a href="duyet_phieu.php" style="display: flex; align-items: center; gap: 8px; color: var(--accent-secondary, #8b5cf6); font-weight: 600; text-decoration: none; font-size: 15px;">
                                    <span class="material-symbols-rounded">fact_check</span> Chứng từ chờ duyệt
                                </a>
                                <a href="duyet_phieu.php" style="color: var(--accent-secondary, #8b5cf6); font-size: 12px; font-weight: 600; text-decoration: none; padding: 2px 8px; border-radius: 6px; background: rgba(139,92,246,0.08); transition: background 0.2s;" onmouseover="this.style.background='rgba(139,92,246,0.18)'" onmouseout="this.style.background='rgba(139,92,246,0.08)'">
                                    Tất cả &rarr;
                                </a>
                            </div>
                            <?php if (count($list_cho_duyet) == 0): ?>
                                <a href="duyet_phieu.php" style="text-decoration: none; display: block; padding: 12px; border-radius: 8px; background: rgba(139,92,246,0.03); border: 1px dashed rgba(139,92,246,0.2); text-align: center; color: var(--text-muted); font-size: 13px;">
                                    <span class="material-symbols-rounded" style="font-size: 20px; display: block; margin-bottom: 4px; color: #10b981;">task_alt</span>
                                    Không có chứng từ nào chờ duyệt.
                                </a>
                            <?php else: ?>
                                <ul style="list-style: none; padding: 0; margin: 0; display: flex; flex-direction: column; gap: 8px;">
                                    <?php foreach($list_cho_duyet as $cd): 
                                        $target_tab = 'duyet_phieu.php';
                                        if ($cd['loai'] == 'Phiếu nhập') $target_tab = 'duyet_phieu.php?tab=nhap';
                                        elseif ($cd['loai'] == 'Phiếu xuất') $target_tab = 'duyet_phieu.php?tab=xuat';
                                        elseif ($cd['loai'] == 'Phiếu điều chuyển') $target_tab = 'duyet_phieu.php?tab=dieuchuyen';
                                    ?>
                                        <li>
                                            <a href="<?= $target_tab ?>" style="display: flex; justify-content: space-between; align-items: center; font-size: 13.5px; padding: 8px 12px; border-radius: 8px; text-decoration: none; color: inherit; background: rgba(139,92,246,0.03); border: 1px solid rgba(139,92,246,0.08); transition: all 0.2s ease; cursor: pointer;" onmouseover="this.style.background='rgba(139,92,246,0.12)'; this.style.borderColor='rgba(139,92,246,0.3)'; this.style.transform='translateX(3px)';" onmouseout="this.style.background='rgba(139,92,246,0.03)'; this.style.borderColor='rgba(139,92,246,0.08)'; this.style.transform='translateX(0)';">
                                                <span style="color: var(--text-primary); font-weight: 500; display: flex; align-items: center; gap: 6px;">
                                                    <span class="material-symbols-rounded" style="font-size: 16px; color: var(--accent-secondary, #8b5cf6);">description</span>
                                                    <?= htmlspecialchars($cd['loai']) ?> #<?= htmlspecialchars($cd['id']) ?>
                                                </span>
                                                <span style="color: #ef4444; font-weight: 600; padding: 3px 8px; background: rgba(239,68,68,0.1); border-radius: 4px; font-size: 12px; display: inline-flex; align-items: center; gap: 2px;">
                                                    Duyệt
                                                    <span class="material-symbols-rounded" style="font-size: 14px;">arrow_forward</span>
                                                </span>
                                            </a>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endif; ?>

                    <?php if ($role_id == 2): ?>
                    <div style="background: var(--bg-card); border-radius: var(--radius-lg); padding: 20px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); border: 1px solid rgba(239,68,68,0.15); display: flex; flex-direction: column; justify-content: space-between;">
                        <div>
                            <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 14px;">
                                <a href="phieuxuat.php" style="display: flex; align-items: center; gap: 8px; color: #ef4444; font-weight: 600; text-decoration: none; font-size: 15px;">
                                    <span class="material-symbols-rounded">local_shipping</span> Hóa đơn chờ xuất kho
                                </a>
                                <a href="phieuxuat.php" style="color: #ef4444; font-size: 12px; font-weight: 600; text-decoration: none; padding: 2px 8px; border-radius: 6px; background: rgba(239,68,68,0.08); transition: background 0.2s;" onmouseover="this.style.background='rgba(239,68,68,0.18)'" onmouseout="this.style.background='rgba(239,68,68,0.08)'">
                                    Tất cả &rarr;
                                </a>
                            </div>
                            <?php if (count($list_hoadon_cho) == 0): ?>
                                <a href="phieuxuat.php" style="text-decoration: none; display: block; padding: 12px; border-radius: 8px; background: rgba(239,68,68,0.03); border: 1px dashed rgba(239,68,68,0.2); text-align: center; color: var(--text-muted); font-size: 13px;">
                                    <span class="material-symbols-rounded" style="font-size: 20px; display: block; margin-bottom: 4px; color: #10b981;">task_alt</span>
                                    Tất cả hóa đơn đã xuất.
                                </a>
                            <?php else: ?>
                                <ul style="list-style: none; padding: 0; margin: 0; display: flex; flex-direction: column; gap: 8px;">
                                    <?php foreach($list_hoadon_cho as $hc): ?>
                                        <li>
                                            <a href="phieuxuat.php" style="display: flex; justify-content: space-between; align-items: center; font-size: 13.5px; padding: 8px 12px; border-radius: 8px; text-decoration: none; color: inherit; background: rgba(239,68,68,0.03); border: 1px solid rgba(239,68,68,0.08); transition: all 0.2s ease; cursor: pointer;" onmouseover="this.style.background='rgba(239,68,68,0.12)'; this.style.borderColor='rgba(239,68,68,0.3)'; this.style.transform='translateX(3px)';" onmouseout="this.style.background='rgba(239,68,68,0.03)'; this.style.borderColor='rgba(239,68,68,0.08)'; this.style.transform='translateX(0)';">
                                                <span style="color: var(--text-primary); font-weight: 500;">HĐ #<?= $hc['maHoaDon'] ?></span>
                                                <span style="color: #ef4444; font-weight: 600; padding: 3px 8px; background: rgba(239,68,68,0.1); border-radius: 4px; font-size: 12px; display: inline-flex; align-items: center; gap: 2px;">
                                                    Xuất ngay
                                                    <span class="material-symbols-rounded" style="font-size: 14px;">arrow_forward</span>
                                                </span>
                                            </a>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endif; ?>

                    <div style="background: var(--bg-card); border-radius: var(--radius-lg); padding: 20px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); border: 1px solid rgba(245,158,11,0.15); cursor: pointer; transition: all 0.2s ease;" onclick="window.location.href='tonkho_hientai.php'" onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 6px 12px -2px rgba(245,158,11,0.15)';" onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 4px 6px -1px rgba(0,0,0,0.05)';">
                        <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 12px; color: #f59e0b; font-weight: 600;">
                            <span class="material-symbols-rounded">warning</span> Sản phẩm sắp hết
                        </div>
                        <div style="font-size: 24px; font-weight: 700; color: var(--text); margin-bottom: 8px;"><?= $sap_het ?> <span style="font-size: 14px; font-weight: 400; color: var(--text-muted);">màu đàn ≤ 5 cây</span></div>
                        <a href="tonkho_hientai.php" style="color: #f59e0b; font-size: 13px; text-decoration: none; font-weight: 600; display: inline-flex; align-items: center; gap: 4px;">Xem chi tiết &rarr;</a>
                    </div>
                </div>

                <!-- Biểu đồ phân tích kinh doanh & tồn kho -->
                <div style="background: var(--bg-card); border-radius: var(--radius-lg); padding: 24px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); border: 1px solid var(--border);">
                    <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 14px; margin-bottom: 20px;">
                        <div>
                            <h3 style="margin: 0; color: var(--text-primary); font-size: 16px; font-weight: 700; display: flex; align-items: center; gap: 8px;">
                                <span class="material-symbols-rounded" style="color: #6366f1;">analytics</span> Phân Tích Hoạt Động Kinh Doanh
                            </h3>
                            <p style="margin: 4px 0 0 0; font-size: 13px; color: var(--text-muted);">Tổng quan xu hướng doanh thu và cơ cấu sản phẩm</p>
                        </div>
                        
                        <!-- Tab Selector -->
                        <div style="display: flex; gap: 4px; background: var(--bg-tertiary, #f1f5f9); padding: 4px; border-radius: 10px;">
                            <button type="button" class="chart-tab-btn active" onclick="switchChartTab('revenue')" id="tab-btn-revenue" style="border: none; background: #fff; color: #6366f1; padding: 6px 12px; border-radius: 8px; font-size: 12px; font-weight: 700; cursor: pointer; display: flex; align-items: center; gap: 4px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); transition: all 0.2s;">
                                <span class="material-symbols-rounded" style="font-size: 16px;">bar_chart</span> Doanh thu 6 tháng
                            </button>
                            <button type="button" class="chart-tab-btn" onclick="switchChartTab('brand')" id="tab-btn-brand" style="border: none; background: transparent; color: var(--text-secondary); padding: 6px 12px; border-radius: 8px; font-size: 12px; font-weight: 600; cursor: pointer; display: flex; align-items: center; gap: 4px; transition: all 0.2s;">
                                <span class="material-symbols-rounded" style="font-size: 16px; color: #10b981;">pie_chart</span> Cơ cấu Hãng
                            </button>
                            <button type="button" class="chart-tab-btn" onclick="switchChartTab('top')" id="tab-btn-top" style="border: none; background: transparent; color: var(--text-secondary); padding: 6px 12px; border-radius: 8px; font-size: 12px; font-weight: 600; cursor: pointer; display: flex; align-items: center; gap: 4px; transition: all 0.2s;">
                                <span class="material-symbols-rounded" style="font-size: 16px; color: #f59e0b;">leaderboard</span> Top bán chạy
                            </button>
                        </div>
                    </div>

                    <!-- View 1: Doanh thu 6 tháng (Bar Chart + Line Chart Combo) -->
                    <div id="view-revenue" class="chart-view-panel">
                        <div style="display: flex; gap: 12px; margin-bottom: 16px; flex-wrap: wrap;">
                            <div style="flex: 1; min-width: 130px; background: rgba(99, 102, 241, 0.06); border-radius: 10px; padding: 10px 14px; border: 1px solid rgba(99, 102, 241, 0.15);">
                                <div style="font-size: 11px; color: var(--text-muted); font-weight: 600; text-transform: uppercase;">Tổng DT 6 tháng</div>
                                <div style="font-size: 16px; font-weight: 700; color: #6366f1; margin-top: 2px;"><?= number_format($total_rev_sum, 0, ',', '.') ?> đ</div>
                            </div>
                            <div style="flex: 1; min-width: 130px; background: rgba(16, 185, 129, 0.06); border-radius: 10px; padding: 10px 14px; border: 1px solid rgba(16, 185, 129, 0.15);">
                                <div style="font-size: 11px; color: var(--text-muted); font-weight: 600; text-transform: uppercase;">Tháng đạt đỉnh</div>
                                <div style="font-size: 16px; font-weight: 700; color: #10b981; margin-top: 2px;"><?= !empty($max_rev_month) ? $max_rev_month : 'Chưa có' ?></div>
                            </div>
                            <div style="flex: 1; min-width: 130px; background: rgba(245, 158, 11, 0.06); border-radius: 10px; padding: 10px 14px; border: 1px solid rgba(245, 158, 11, 0.15);">
                                <div style="font-size: 11px; color: var(--text-muted); font-weight: 600; text-transform: uppercase;">Tổng đơn hàng</div>
                                <div style="font-size: 16px; font-weight: 700; color: #f59e0b; margin-top: 2px;"><?= array_sum($chart_order_counts) ?> đơn</div>
                            </div>
                        </div>
                        <div style="position: relative; height: 300px; width: 100%;">
                            <canvas id="revenueMonthlyChart"></canvas>
                        </div>
                    </div>

                    <!-- View 2: Cơ cấu theo Hãng (Doughnut + Breakdown List) -->
                    <div id="view-brand" class="chart-view-panel" style="display: none;">
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; align-items: center; min-height: 350px;">
                            <div style="position: relative; height: 280px;">
                                <canvas id="brandDoughnutChart"></canvas>
                            </div>
                            <div style="display: flex; flex-direction: column; gap: 10px; max-height: 300px; overflow-y: auto; padding-right: 6px;">
                                <div style="font-size: 12px; font-weight: 700; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 2px;">
                                    Tồn kho theo Hãng (Tổng: <?= $tong_dan ?> cây)
                                </div>
                                <?php 
                                $color_idx = 0;
                                foreach($brand_stock_list as $bs): 
                                    $color = $chart_brand_colors[$color_idx % count($chart_brand_colors)];
                                    $pct = $tong_dan > 0 ? round(($bs['stock'] / $tong_dan) * 100, 1) : 0;
                                    $color_idx++;
                                ?>
                                <div style="display: flex; flex-direction: column; gap: 4px;">
                                    <div style="display: flex; align-items: center; justify-content: space-between; font-size: 13px;">
                                        <div style="display: flex; align-items: center; gap: 8px;">
                                            <span style="width: 10px; height: 10px; border-radius: 50%; background: <?= $color ?>; display: inline-block;"></span>
                                            <span style="font-weight: 600; color: var(--text-primary);"><?= htmlspecialchars($bs['name']) ?></span>
                                        </div>
                                        <div style="display: flex; align-items: center; gap: 10px;">
                                            <span style="color: var(--text-muted); font-size: 12px;"><?= $pct ?>%</span>
                                            <strong style="color: var(--text-primary); min-width: 45px; text-align: right;"><?= $bs['stock'] ?> cây</strong>
                                        </div>
                                    </div>
                                    <div style="width: 100%; height: 6px; background: var(--bg-tertiary, #f1f5f9); border-radius: 4px; overflow: hidden;">
                                        <div style="width: <?= $pct ?>%; height: 100%; background: <?= $color ?>; border-radius: 4px;"></div>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>

                    <!-- View 3: Top 5 Bán Chạy -->
                    <div id="view-top" class="chart-view-panel" style="display: none;">
                        <div style="font-size: 12px; font-weight: 700; color: var(--text-muted); text-transform: uppercase; margin-bottom: 12px;">
                            Xếp hạng 5 mẫu đàn bán chạy nhất
                        </div>
                        <div style="position: relative; height: 300px; width: 100%;">
                            <canvas id="topSellingChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Cột phải: Timeline -->
            <div style="background: var(--bg-card); border-radius: var(--radius-lg); padding: 24px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); border: 1px solid var(--border);">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;">
                    <div>
                        <h3 style="margin: 0; color: var(--text-primary); font-size: 16px; font-weight: 700; display: flex; align-items: center; gap: 8px;">
                            <span class="material-symbols-rounded" style="color: #10b981;">history</span> Hoạt động gần đây
                        </h3>
                        <p style="margin: 4px 0 0 0; font-size: 12px; color: var(--text-muted);"><?= ($role_id == 1) ? 'Nhật ký toàn hệ thống' : 'Nhật ký cá nhân của bạn' ?></p>
                    </div>
                    <a href="lichsu_hoatdong.php" style="font-size: 12px; color: var(--accent, #0ea5e9); text-decoration: none; font-weight: 600; display: flex; align-items: center; gap: 2px;">
                        Xem tất cả <span class="material-symbols-rounded" style="font-size: 14px;">arrow_forward</span>
                    </a>
                </div>
                
                <div style="display: flex; flex-direction: column; gap: 20px; position: relative;">
                    <!-- Line doc -->
                    <div style="position: absolute; left: 15px; top: 10px; bottom: 10px; width: 2px; background: var(--border, #e2e8f0); z-index: 0;"></div>
                    
                    <?php if(count($recent_logs) > 0): ?>
                        <?php foreach($recent_logs as $log): ?>
                            <div style="display: flex; gap: 16px; position: relative; z-index: 1;">
                                <div style="width: 32px; height: 32px; border-radius: 50%; background: #fff; border: 2px solid #3b82f6; flex-shrink: 0; display: flex; align-items: center; justify-content: center; box-shadow: 0 0 0 4px var(--bg-card);">
                                    <span class="material-symbols-rounded" style="font-size: 16px; color: #3b82f6;">check</span>
                                </div>
                                <div style="flex-grow: 1; padding-top: 6px;">
                                    <div style="font-size: 14px; font-weight: 600; color: var(--text-primary); margin-bottom: 4px;">
                                        <?= htmlspecialchars($log['hoTen']) ?> <span style="font-weight: 400; color: var(--text-secondary);">- <?= htmlspecialchars($log['loaiHanhDong']) ?></span>
                                    </div>
                                    <div style="font-size: 13px; color: var(--text-muted); margin-bottom: 4px;">
                                        <?= htmlspecialchars($log['chiTiet']) ?>
                                    </div>
                                    <div style="font-size: 12px; color: var(--text-muted);">
                                        <?= date('H:i d/m', strtotime($log['ngayTao'])) ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div style="padding-left: 48px; font-size: 14px; color: var(--text-muted);">Chưa có hoạt động nào gần đây.</div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <script>
        let chartRevenueInstance = null;
        let chartBrandInstance = null;
        let chartTopInstance = null;

        function switchChartTab(tab) {
            // Hide all view panels
            document.querySelectorAll('.chart-view-panel').forEach(el => el.style.display = 'none');
            // Reset tab button styles
            document.querySelectorAll('.chart-tab-btn').forEach(btn => {
                btn.style.background = 'transparent';
                btn.style.color = 'var(--text-secondary)';
                btn.style.boxShadow = 'none';
                btn.classList.remove('active');
            });

            const activeBtn = document.getElementById('tab-btn-' + tab);
            const activeView = document.getElementById('view-' + tab);
            if (activeBtn) {
                activeBtn.style.background = '#fff';
                activeBtn.style.color = (tab === 'revenue' ? '#6366f1' : (tab === 'brand' ? '#10b981' : '#f59e0b'));
                activeBtn.style.boxShadow = '0 1px 3px rgba(0,0,0,0.1)';
                activeBtn.classList.add('active');
            }
            if (activeView) {
                activeView.style.display = 'block';
            }

            if (tab === 'revenue' && chartRevenueInstance) chartRevenueInstance.resize();
            if (tab === 'brand' && chartBrandInstance) chartBrandInstance.resize();
            if (tab === 'top' && chartTopInstance) chartTopInstance.resize();
        }

        document.addEventListener('DOMContentLoaded', function() {
            // 1. Chart Doanh Thu 6 Tháng (Bar + Line Combo)
            const ctxRev = document.getElementById('revenueMonthlyChart');
            if (ctxRev) {
                chartRevenueInstance = new Chart(ctxRev.getContext('2d'), {
                    data: {
                        labels: <?= json_encode($chart_rev_labels) ?>,
                        datasets: [
                            {
                                type: 'bar',
                                label: 'Doanh thu (VNĐ)',
                                data: <?= json_encode($chart_rev_data) ?>,
                                backgroundColor: 'rgba(99, 102, 241, 0.75)',
                                borderColor: '#6366f1',
                                borderWidth: 1.5,
                                borderRadius: 8,
                                hoverBackgroundColor: '#4f46e5',
                                yAxisID: 'y',
                                order: 2
                            },
                            {
                                type: 'line',
                                label: 'Số đơn hàng',
                                data: <?= json_encode($chart_order_counts) ?>,
                                borderColor: '#f59e0b',
                                backgroundColor: '#f59e0b',
                                borderWidth: 3,
                                pointBackgroundColor: '#fff',
                                pointBorderColor: '#f59e0b',
                                pointBorderWidth: 2,
                                pointRadius: 5,
                                pointHoverRadius: 7,
                                tension: 0.35,
                                yAxisID: 'y1',
                                order: 1
                            }
                        ]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        interaction: {
                            mode: 'index',
                            intersect: false
                        },
                        plugins: {
                            legend: {
                                position: 'top',
                                labels: {
                                    usePointStyle: true,
                                    boxWidth: 8,
                                    font: { family: 'Inter', size: 12, weight: '600' }
                                }
                            },
                            tooltip: {
                                backgroundColor: 'rgba(15, 23, 42, 0.9)',
                                padding: 12,
                                titleFont: { family: 'Inter', size: 13, weight: '700' },
                                bodyFont: { family: 'Inter', size: 12 },
                                cornerRadius: 8,
                                callbacks: {
                                    label: function(context) {
                                        if (context.dataset.yAxisID === 'y') {
                                            return ' Doanh thu: ' + new Intl.NumberFormat('vi-VN').format(context.raw) + ' đ';
                                        } else {
                                            return ' Đơn hàng: ' + context.raw + ' đơn';
                                        }
                                    }
                                }
                            }
                        },
                        scales: {
                            y: {
                                type: 'linear',
                                display: true,
                                position: 'left',
                                beginAtZero: true,
                                grid: { borderDash: [4, 4], color: 'rgba(226, 232, 240, 0.6)' },
                                ticks: {
                                    callback: function(value) {
                                        if (value >= 1000000000) return (value / 1000000000).toFixed(1) + ' Tỷ';
                                        if (value >= 1000000) return (value / 1000000).toFixed(0) + ' Tr';
                                        return value;
                                    },
                                    font: { family: 'Inter', size: 11 }
                                }
                            },
                            y1: {
                                type: 'linear',
                                display: true,
                                position: 'right',
                                beginAtZero: true,
                                grid: { drawOnChartArea: false },
                                ticks: {
                                    stepSize: 1,
                                    callback: function(value) { return value + ' đơn'; },
                                    font: { family: 'Inter', size: 11 }
                                }
                            },
                            x: {
                                grid: { display: false },
                                ticks: { font: { family: 'Inter', size: 12, weight: '500' } }
                            }
                        }
                    }
                });
            }

            // 2. Chart Cơ cấu Hãng (Doughnut)
            const ctxBrand = document.getElementById('brandDoughnutChart');
            if (ctxBrand) {
                chartBrandInstance = new Chart(ctxBrand.getContext('2d'), {
                    type: 'doughnut',
                    data: {
                        labels: <?= json_encode($chart_brand_labels) ?>,
                        datasets: [{
                            data: <?= json_encode($chart_brand_data) ?>,
                            backgroundColor: <?= json_encode(array_slice($chart_brand_colors, 0, count($chart_brand_labels))) ?>,
                            borderWidth: 2,
                            borderColor: '#ffffff',
                            hoverOffset: 8
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        cutout: '70%',
                        plugins: {
                            legend: { display: false },
                            tooltip: {
                                backgroundColor: 'rgba(15, 23, 42, 0.9)',
                                padding: 12,
                                cornerRadius: 8,
                                callbacks: {
                                    label: function(context) {
                                        const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                        const val = context.raw;
                                        const pct = total > 0 ? ((val / total) * 100).toFixed(1) : 0;
                                        return ' ' + context.label + ': ' + val + ' cây (' + pct + '%)';
                                    }
                                }
                            }
                        }
                    }
                });
            }

            // 3. Chart Top Bán Chạy (Horizontal Bar Chart)
            const ctxTop = document.getElementById('topSellingChart');
            if (ctxTop) {
                chartTopInstance = new Chart(ctxTop.getContext('2d'), {
                    type: 'bar',
                    data: {
                        labels: <?= json_encode($chart_top_labels) ?>,
                        datasets: [{
                            axis: 'y',
                            label: 'Số lượng đã bán',
                            data: <?= json_encode($chart_top_sold) ?>,
                            backgroundColor: [
                                'rgba(245, 158, 11, 0.85)',
                                'rgba(99, 102, 241, 0.85)',
                                'rgba(16, 185, 129, 0.85)',
                                'rgba(236, 72, 153, 0.85)',
                                'rgba(6, 182, 212, 0.85)'
                            ],
                            borderRadius: 6,
                            borderWidth: 0
                        }]
                    },
                    options: {
                        indexAxis: 'y',
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: { display: false },
                            tooltip: {
                                backgroundColor: 'rgba(15, 23, 42, 0.9)',
                                padding: 12,
                                cornerRadius: 8,
                                callbacks: {
                                    label: function(context) {
                                        return ' Đã bán: ' + context.raw + ' cây';
                                    }
                                }
                            }
                        },
                        scales: {
                            x: {
                                beginAtZero: true,
                                ticks: {
                                    stepSize: 1,
                                    callback: function(v) { return v + ' cây'; }
                                },
                                grid: { borderDash: [4, 4] }
                            },
                            y: {
                                grid: { display: false },
                                ticks: { font: { family: 'Inter', size: 12, weight: '600' } }
                            }
                        }
                    }
                });
            }
        });
        </script>

        <!-- 4. Featured Products (Premium UI) -->
        <div class="section-title-premium" style="margin-top: 40px;">
            <span class="material-symbols-rounded" style="color: var(--accent, #3b82f6);">star</span> 
            Sản phẩm mới nhập
        </div>
        <div class="premium-featured-grid">
            <?php foreach ($featured_products as $p): ?>
            <div class="premium-product-card">
                <div class="premium-product-img-wrapper">
                    <?php if (!empty($p['hinhAnh'])): ?>
                        <img src="images/<?php echo htmlspecialchars($p['hinhAnh']); ?>" alt="Piano">
                    <?php else: ?>
                        <span class="material-symbols-rounded" style="font-size: 48px; color: #cbd5e1;">music_note</span>
                    <?php endif; ?>
                </div>
                <div class="premium-product-info">
                    <div class="premium-product-brand"><?php echo htmlspecialchars($p['tenHang']); ?></div>
                    <div class="premium-product-name"><?php echo htmlspecialchars($p['tenMau']); ?></div>
                </div>
            </div>
            <?php endforeach; ?>
            <?php if (count($featured_products) == 0): ?>
                <p style="color: var(--text-muted); font-size: 14px; grid-column: 1 / -1;">Chưa có sản phẩm nào.</p>
            <?php endif; ?>
        </div>

        <!-- 5. Active Promotions -->
        <?php if (count($active_promos) > 0): ?>
        <div class="section-header" style="margin-top: 40px; display: flex; align-items: center;">
            <span class="material-symbols-rounded" style="color: #f472b6; margin-right: 10px; font-size: 28px;">redeem</span> 
            <span style="font-size: 18px;">Chương trình khuyến mãi đang diễn ra</span>
            <a href="cs_khuyenmai.php" style="margin-left: auto; font-size: 14px; color: var(--text-muted); text-decoration: none; display: flex; align-items: center; gap: 4px; transition: 0.2s;">Xem tất cả <span class="material-symbols-rounded" style="font-size: 18px;">arrow_forward</span></a>
        </div>
        <div class="dashboard-cards-grid">
            <?php foreach ($active_promos as $p): ?>
            <div style="background: var(--bg-card); border: 1px solid rgba(244, 114, 182, 0.2); border-radius: var(--radius-xl); padding: 28px; display: flex; flex-direction: column; transition: 0.3s; position: relative; overflow: hidden; box-shadow: 0 4px 20px rgba(0,0,0,0.1);">
                <div style="position: absolute; top: 0; left: 0; width: 6px; height: 100%; background: linear-gradient(to bottom, #f472b6, #db2777);"></div>
                
                <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 16px; padding-left: 12px;">
                    <h4 style="margin: 0; color: var(--text-primary); font-size: 18px; font-weight: 700; line-height: 1.4; padding-right: 12px;"><?php echo htmlspecialchars($p['tenChuongTrinh']); ?></h4>
                    <?php if ($p['phanTramGiam'] > 0): ?>
                    <span style="background: linear-gradient(135deg, #f472b6, #db2777); color: #fff; font-size: 14px; font-weight: 800; padding: 6px 12px; border-radius: 8px; flex-shrink: 0; box-shadow: 0 4px 12px rgba(244, 114, 182, 0.4);">-<?php echo $p['phanTramGiam']; ?>%</span>
                    <?php endif; ?>
                </div>
                
                <p style="color: var(--text-secondary); font-size: 15px; margin: 0 0 20px 12px; line-height: 1.7; display: -webkit-box; -webkit-line-clamp: 4; -webkit-box-orient: vertical; overflow: hidden; text-overflow: ellipsis; white-space: pre-line;">
                    <?php echo htmlspecialchars($p['moTa']); ?>
                </p>
                
                <div style="margin-top: auto; padding-left: 12px; font-size: 14px; color: var(--text-muted); display: flex; align-items: center; gap: 8px;">
                    <span class="material-symbols-rounded" style="font-size: 18px; color: #f472b6;">timer</span>
                    Thời hạn: <strong style="color: var(--text-secondary);"><?php echo date('d/m/Y', strtotime($p['ngayBatDau'])); ?> - <?php echo date('d/m/Y', strtotime($p['ngayKetThuc'])); ?></strong>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <!-- 6. Warranty Policies -->
        <?php if (count($recent_policies) > 0): ?>
        <div class="section-header" style="margin-top: 50px; display: flex; align-items: center;">
            <span class="material-symbols-rounded" style="color: #34d399; margin-right: 10px; font-size: 28px;">policy</span> 
            <span style="font-size: 18px;">Chính sách bảo hành</span>
            <a href="cs_baohanh.php" style="margin-left: auto; font-size: 14px; color: var(--text-muted); text-decoration: none; display: flex; align-items: center; gap: 4px; transition: 0.2s;">Xem tất cả <span class="material-symbols-rounded" style="font-size: 18px;">arrow_forward</span></a>
        </div>
        <div class="dashboard-cards-grid">
            <?php foreach ($recent_policies as $p): ?>
            <div style="background: var(--bg-card); border: 1px solid rgba(52, 211, 153, 0.2); border-radius: var(--radius-xl); padding: 28px; display: flex; flex-direction: column; transition: 0.3s; position: relative; overflow: hidden; box-shadow: 0 4px 20px rgba(0,0,0,0.1);">
                <div style="position: absolute; top: 0; left: 0; width: 6px; height: 100%; background: linear-gradient(to bottom, #5eead4, #34d399);"></div>
                
                <h4 style="margin: 0 0 16px 12px; color: var(--text-primary); font-size: 18px; font-weight: 700; line-height: 1.4;">
                    <?php echo htmlspecialchars($p['tenChinhSach']); ?>
                </h4>
                
                <p style="color: var(--text-secondary); font-size: 15px; margin: 0 0 20px 12px; line-height: 1.7; display: -webkit-box; -webkit-line-clamp: 4; -webkit-box-orient: vertical; overflow: hidden; text-overflow: ellipsis; white-space: pre-line;">
                    <?php echo htmlspecialchars($p['noiDung']); ?>
                </p>
                
                <div style="margin-top: auto; padding-left: 12px; font-size: 14px; color: var(--text-muted); display: flex; align-items: center; gap: 8px;">
                    <span class="material-symbols-rounded" style="font-size: 18px; color: #34d399;">update</span>
                    Cập nhật: <strong style="color: var(--text-secondary);"><?php echo date('d/m/Y', strtotime($p['ngayCapNhat'])); ?></strong>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php include 'includes/footer.php'; ?>