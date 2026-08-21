<?php
session_start();
require_once 'db.php'; 

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

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
             FROM DanSerial 
             WHERE trangThai LIKE '%Trong kho%'";
$res_tong = $conn->query($sql_tong);
if($res_tong && $row = $res_tong->fetch_assoc()) { 
    $tong_dan = $row['total'] ?? 0; 
    $tong_gia_tri_kho = $row['total_value'] ?? 0;
}

// 2. Sản phẩm sắp hết
$sql_sap_het = "SELECT COUNT(*) AS total_sap_het FROM (
                    SELECT maMau, COUNT(maSerial) as SL 
                    FROM DanSerial 
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
           FROM HoaDon 
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
                <div class="premium-kpi-card" style="--card-color: #ef4444; --card-bg-light: #fef2f2; --card-shadow-hover: rgba(239, 68, 68, 0.2);">
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

        <!-- 3. Featured Products (Premium UI) -->
        <div class="section-title-premium">
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

        <!-- 4. Quick Access Grid (Premium UI) -->
        <div class="section-title-premium">
            <span class="material-symbols-rounded" style="color: var(--accent-secondary, #8b5cf6);">bolt</span> 
            Truy cập nhanh
        </div>
        <div class="premium-quick-grid">
            
            <?php if ($role_id == 2 || $role_id == 1) {  ?>
                <a href="hoadon_moi.php" class="premium-quick-card staff">
                    <div class="premium-quick-icon-wrapper">
                        <span class="material-symbols-rounded">add_shopping_cart</span>
                    </div>
                    <span class="premium-quick-label"><?php echo __('new_invoice'); ?></span>
                </a>
                <a href="khachhang.php" class="premium-quick-card staff">
                    <div class="premium-quick-icon-wrapper">
                        <span class="material-symbols-rounded">groups</span>
                    </div>
                    <span class="premium-quick-label"><?php echo __('manage_customers'); ?></span>
                </a>
                <a href="quanly_baotri.php" class="premium-quick-card staff">
                    <div class="premium-quick-icon-wrapper">
                        <span class="material-symbols-rounded">build_circle</span>
                    </div>
                    <span class="premium-quick-label"><?php echo __('warranty_receive'); ?></span>
                </a>
            <?php } ?>

            <?php if ($role_id == 1 || $role_id == 2) { ?>
                <a href="phieunhap.php" class="premium-quick-card staff">
                    <div class="premium-quick-icon-wrapper">
                        <span class="material-symbols-rounded">input</span>
                    </div>
                    <span class="premium-quick-label"><?php echo __('import_ticket'); ?></span>
                </a>
                <a href="phieuxuat.php" class="premium-quick-card staff">
                    <div class="premium-quick-icon-wrapper">
                        <span class="material-symbols-rounded">output</span>
                    </div>
                    <span class="premium-quick-label"><?php echo __('export_ticket'); ?></span>
                </a>
                <a href="dieuchuyen.php" class="premium-quick-card staff">
                    <div class="premium-quick-icon-wrapper">
                        <span class="material-symbols-rounded">local_shipping</span>
                    </div>
                    <span class="premium-quick-label"><?php echo __('internal_transfer'); ?></span>
                </a>
                <a href="baocao_nhapxuatton.php" class="premium-quick-card staff">
                    <div class="premium-quick-icon-wrapper">
                        <span class="material-symbols-rounded">summarize</span>
                    </div>
                    <span class="premium-quick-label"><?php echo __('import_export_report'); ?></span>
                </a>
            <?php } ?>

            <?php if ($role_id == 1) { ?>
                <a href="quanly_hanghoa.php" class="premium-quick-card admin">
                    <div class="premium-quick-icon-wrapper">
                        <span class="material-symbols-rounded">piano</span>
                    </div>
                    <span class="premium-quick-label"><?php echo __('manage_goods'); ?></span>
                </a>
                <a href="duyet_phieu.php" class="premium-quick-card admin">
                    <div class="premium-quick-icon-wrapper">
                        <span class="material-symbols-rounded">fact_check</span>
                    </div>
                    <span class="premium-quick-label"><?php echo __('approve_tickets'); ?></span>
                </a>
            <?php } ?>

        </div>

        <!-- 5. Active Promotions -->
        <?php if (count($active_promos) > 0): ?>
        <div class="section-header" style="margin-top: 40px; display: flex; align-items: center;">
            <span class="material-symbols-rounded" style="color: #f472b6; margin-right: 10px; font-size: 28px;">redeem</span> 
            <span style="font-size: 18px;">Chương trình khuyến mãi đang diễn ra</span>
            <a href="cs_khuyenmai.php" style="margin-left: auto; font-size: 14px; color: var(--text-muted); text-decoration: none; display: flex; align-items: center; gap: 4px; transition: 0.2s;">Xem tất cả <span class="material-symbols-rounded" style="font-size: 18px;">arrow_forward</span></a>
        </div>
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(450px, 1fr)); gap: 24px;">
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
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(450px, 1fr)); gap: 24px;">
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