<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'db.php';
require_once 'functions.php';

// Kiểm tra đăng nhập
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$fullname = $_SESSION['fullname'];
$role_id = $_SESSION['role_id'];
$title = "Tồn kho & Kiểm kê hiện tại";

// Lấy danh sách kho
$khos = $conn->query("SELECT * FROM kho ORDER BY maKho ASC");
$selected_kho = isset($_GET['ma_kho']) ? intval($_GET['ma_kho']) : 0;
$search = isset($_GET['search']) ? trim($_GET['search']) : (isset($_GET['q']) ? trim($_GET['q']) : '');
$tab_filter = isset($_GET['tab']) ? trim($_GET['tab']) : 'all'; // all, sales, baotri

// Điều kiện lọc kho cho KPI
$where_kho_kpi = $selected_kho > 0 ? " WHERE ds.maKho = $selected_kho " : " WHERE 1=1 ";

// Thống kê KPI tổng quan theo kho
$kpi_sql = "SELECT 
    SUM(CASE WHEN ds.trangThai = 'Trong kho' AND COALESCE(ds.giaBan, 0) > 0 THEN 1 ELSE 0 END) as qty_sales,
    SUM(CASE WHEN ds.trangThai = 'Đang bảo hành' THEN 1 ELSE 0 END) as qty_baotri,
    SUM(CASE WHEN ds.trangThai IN ('Trong kho', 'Đang bảo hành') THEN 1 ELSE 0 END) as qty_total
FROM danserial ds " . $where_kho_kpi;
$kpi_res = $conn->query($kpi_sql)->fetch_assoc();
$qty_sales = intval($kpi_res['qty_sales'] ?? 0);
$qty_baotri = intval($kpi_res['qty_baotri'] ?? 0);
$qty_total = intval($kpi_res['qty_total'] ?? 0);

// Lấy dữ liệu tồn kho theo Mẫu Đàn
$sql = "SELECT md.maMau, md.tenMau, md.hinhAnh, hd.tenHang, ld.tenLoai, 
        SUM(CASE WHEN ds.trangThai = 'Trong kho' AND COALESCE(ds.giaBan, 0) > 0 THEN 1 ELSE 0 END) as soLuongBan,
        SUM(CASE WHEN ds.trangThai = 'Trong kho' AND COALESCE(ds.giaBan, 0) <= 0 THEN 1 ELSE 0 END) as soLuongChuaNiemYet,
        SUM(CASE WHEN ds.trangThai = 'Đang bảo hành' THEN 1 ELSE 0 END) as soLuongBaoTri,
        COUNT(ds.maSerial) as tongSoLuongTon, k.maKho, k.tenKho
        FROM MauDan md
        JOIN HangDan hd ON md.maHang = hd.maHang
        JOIN LoaiDan ld ON md.maLoai = ld.maLoai
        JOIN danserial ds ON ds.maMau = md.maMau AND ds.trangThai IN ('Trong kho', 'Đang bảo hành')
        JOIN kho k ON ds.maKho = k.maKho
        WHERE 1=1";

if ($selected_kho > 0) {
    $sql .= " AND k.maKho = $selected_kho";
}

if (!empty($search)) {
    $searchEscaped = $conn->real_escape_string($search);
    $sql .= " AND (md.tenMau LIKE '%$searchEscaped%' OR hd.tenHang LIKE '%$searchEscaped%' OR md.maMau IN (SELECT maMau FROM danserial WHERE soSerial LIKE '%$searchEscaped%'))";
}

if ($tab_filter === 'sales') {
    $sql .= " AND ds.trangThai = 'Trong kho' AND COALESCE(ds.giaBan, 0) > 0";
} elseif ($tab_filter === 'baotri') {
    $sql .= " AND ds.trangThai = 'Đang bảo hành'";
}

$sql .= " GROUP BY md.maMau, k.maKho ORDER BY md.tenMau ASC";
$result = $conn->query($sql);

$inventory_items = [];
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $inventory_items[] = $row;
    }
}
?>

<?php include 'includes/header.php'; ?>
<?php include 'includes/sidebar.php'; ?>

<div class="main-wrapper">
    <?php include 'includes/topbar.php'; ?>

    <div class="content">
        <!-- Header & KPI Cards -->
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; flex-wrap: wrap; gap: 16px;">
            <div>
                <h2 style="margin: 0 0 6px 0; display: flex; align-items: center; gap: 12px; font-size: 24px; font-weight: 800;">
                    <span class="material-symbols-rounded" style="color: var(--accent); font-size: 32px;">inventory</span>
                    Tồn Kho & Kiểm Kê Thực Tế
                </h2>
                <div style="color: var(--text-secondary); font-size: 14px;">
                  
                </div>
            </div>
            <div style="display: flex; gap: 10px;">
                <a href="xuat_pdf.php?type=baocao_nhapxuatton&ma_kho=<?= $selected_kho ?>&search=<?= urlencode($search) ?>" target="_blank" style="background: linear-gradient(135deg, #ec4899, #f43f5e); color: white; border: none; text-decoration: none; display: inline-flex; align-items: center; gap: 6px; padding: 10px 18px; border-radius: 8px; font-weight: 600; box-shadow: 0 4px 12px rgba(244, 63, 94, 0.3); font-size: 14px; transition: transform 0.2s;">
                    <span class="material-symbols-rounded">print</span> In Báo Cáo Nhập Xuất Tồn
                </a>
            </div>
        </div>

        <?php 
        $buildTabUrl = function($tab) use ($selected_kho, $search) {
            $params = ['tab' => $tab];
            if ($selected_kho > 0) $params['ma_kho'] = $selected_kho;
            if (!empty($search)) $params['search'] = $search;
            return 'tonkho_hientai.php?' . http_build_query($params);
        };
        ?>

        <!-- 3 KPI Cards (Corporate & Clean UI) -->
        <div class="premium-kpi-row" style="margin-bottom: 24px;">
            <!-- KPI 1: Hàng sẵn sàng bán -->
            <div class="premium-kpi-card" style="--card-color: #10b981; --card-bg-light: #ecfdf5; --card-shadow-hover: rgba(16, 185, 129, 0.2);" onclick="window.location.href='<?= $buildTabUrl('sales') ?>'">
                <div class="kpi-card-header">
                    <p class="kpi-title">HÀNG SẴN SÀNG BÁN</p>
                </div>
                <div class="kpi-card-body">
                    <div class="kpi-card-val-wrap">
                        <h3><?= number_format($qty_sales) ?><span class="kpi-unit">cây</span></h3>
                    </div>
                    <div class="premium-kpi-icon">
                        <span class="material-symbols-rounded">check_circle</span>
                    </div>
                </div>
                <div class="kpi-card-footer">
                    <span class="kpi-sub-left">
                        <span class="material-symbols-rounded" style="font-size: 15px; color: #10b981;">inventory_2</span>
                        Trạng thái:
                    </span>
                    <span class="kpi-sub-right" style="color: #10b981;">
                        Trong kho & sẵn sàng
                    </span>
                </div>
            </div>

            <!-- KPI 2: Hàng bảo trì lưu kho -->
            <div class="premium-kpi-card" style="--card-color: #f59e0b; --card-bg-light: #fffbeb; --card-shadow-hover: rgba(245, 158, 11, 0.2);" onclick="window.location.href='<?= $buildTabUrl('baotri') ?>'">
                <div class="kpi-card-header">
                    <p class="kpi-title">HÀNG BẢO TRÌ LƯU KHO</p>
                </div>
                <div class="kpi-card-body">
                    <div class="kpi-card-val-wrap">
                        <h3 style="<?= $qty_baotri > 0 ? 'color: #f59e0b;' : '' ?>">
                            <?= number_format($qty_baotri) ?><span class="kpi-unit">cây</span>
                        </h3>
                    </div>
                    <div class="premium-kpi-icon">
                        <span class="material-symbols-rounded">build</span>
                    </div>
                </div>
                <div class="kpi-card-footer">
                    <span class="kpi-sub-left">
                        <span class="material-symbols-rounded" style="font-size: 15px; color: #f59e0b;">home_repair_service</span>
                        Dịch vụ:
                    </span>
                    <span class="kpi-sub-right" style="color: #f59e0b;">
                        Khách gửi sửa chữa / BH
                    </span>
                </div>
            </div>

            <!-- KPI 3: Tổng hiện diện vật lý -->
            <div class="premium-kpi-card" style="--card-color: #3b82f6; --card-bg-light: #eff6ff; --card-shadow-hover: rgba(59, 130, 246, 0.2);" onclick="window.location.href='<?= $buildTabUrl('all') ?>'">
                <div class="kpi-card-header">
                    <p class="kpi-title">TỔNG THỰC TẾ TRONG KHO</p>
                </div>
                <div class="kpi-card-body">
                    <div class="kpi-card-val-wrap">
                        <h3><?= number_format($qty_total) ?><span class="kpi-unit">cây</span></h3>
                    </div>
                    <div class="premium-kpi-icon">
                        <span class="material-symbols-rounded">warehouse</span>
                    </div>
                </div>
                <div class="kpi-card-footer">
                    <span class="kpi-sub-left">
                        <span class="material-symbols-rounded" style="font-size: 15px; color: #3b82f6;">inventory</span>
                        Kiểm kho:
                    </span>
                    <span class="kpi-sub-right" style="color: #3b82f6;">
                        Số lượng đếm thực tế
                    </span>
                </div>
            </div>
        </div>

        <!-- Filter Card -->
        <form method="GET" class="filter-card" style="margin-bottom: 20px;">
            <input type="hidden" name="tab" value="<?= htmlspecialchars($tab_filter) ?>">
            <div class="filter-group">
                <label>Chọn kho kiểm tra:</label>
                <select name="ma_kho" class="filter-input" onchange="this.form.submit()">
                    <option value="0">-- Tất cả các kho --</option>
                    <?php 
                    $khos->data_seek(0);
                    while($k = $khos->fetch_assoc()): 
                    ?>
                        <option value="<?= $k['maKho'] ?>" <?= $selected_kho == $k['maKho'] ? 'selected' : '' ?>><?= htmlspecialchars($k['tenKho']) ?></option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div class="filter-group">
                <label>Tìm kiếm sản phẩm / Serial:</label>
                <input type="text" name="search" class="filter-input" placeholder="Nhập tên mẫu đàn, hãng, hoặc Serial..." value="<?= htmlspecialchars($search) ?>">
            </div>
            <button type="submit" class="btn-filter"><span class="material-symbols-rounded">search</span> Lọc kết quả</button>
            <?php if ($selected_kho > 0 || !empty($search) || $tab_filter !== 'all'): ?>
                <a href="tonkho_hientai.php" style="color: var(--danger); text-decoration: none; padding: 14px; font-weight: 600; display: inline-flex; align-items: center; gap: 4px;">
                    <span class="material-symbols-rounded" style="font-size: 18px;">restart_alt</span> Xóa bộ lọc
                </a>
            <?php endif; ?>
        </form>

        <!-- Navigation Tabs -->
        <div style="display: flex; gap: 12px; margin-bottom: 24px; border-bottom: 1px solid var(--glass-border); padding-bottom: 12px; flex-wrap: wrap;">
            <?php 
            $buildTabUrl = function($tab) use ($selected_kho, $search) {
                $params = ['tab' => $tab];
                if ($selected_kho > 0) $params['ma_kho'] = $selected_kho;
                if (!empty($search)) $params['search'] = $search;
                return 'tonkho_hientai.php?' . http_build_query($params);
            };
            ?>
            <a href="<?= $buildTabUrl('all') ?>" 
               style="padding: 10px 18px; border-radius: 10px; font-size: 14px; font-weight: 600; text-decoration: none; display: inline-flex; align-items: center; gap: 8px; transition: 0.2s; <?= $tab_filter === 'all' ? 'background: var(--accent); color: white;' : 'background: rgba(255,255,255,0.05); color: var(--text-secondary);' ?>">
                <span class="material-symbols-rounded" style="font-size: 18px;">apps</span> Tất cả hiện diện (<?= $qty_total ?>)
            </a>
            <a href="<?= $buildTabUrl('sales') ?>" 
               style="padding: 10px 18px; border-radius: 10px; font-size: 14px; font-weight: 600; text-decoration: none; display: inline-flex; align-items: center; gap: 8px; transition: 0.2s; <?= $tab_filter === 'sales' ? 'background: #10b981; color: white;' : 'background: rgba(255,255,255,0.05); color: var(--text-secondary);' ?>">
                <span class="material-symbols-rounded" style="font-size: 18px;">shopping_bag</span> Sẵn sàng bán (<?= $qty_sales ?>)
            </a>
            <a href="<?= $buildTabUrl('baotri') ?>" 
               style="padding: 10px 18px; border-radius: 10px; font-size: 14px; font-weight: 600; text-decoration: none; display: inline-flex; align-items: center; gap: 8px; transition: 0.2s; <?= $tab_filter === 'baotri' ? 'background: #f59e0b; color: white;' : 'background: rgba(255,255,255,0.05); color: var(--text-secondary);' ?>">
                <span class="material-symbols-rounded" style="font-size: 18px;">build</span> Khách gửi bảo trì (<?= $qty_baotri ?>)
            </a>
        </div>

        <!-- Bảng chi tiết Tra cứu Serial (Nếu có tìm kiếm hoặc lọc tab bảo trì) -->
        <?php if(!empty($search) || $tab_filter === 'baotri'): ?>
            <?php
            $sql_serial = "SELECT ds.soSerial, md.tenMau, hd.tenHang, k.tenKho, ds.trangThai, ds.tinhTrang, ds.giaBan,
                                  pb.maPhieuBT, kh.hoTen as tenKH, pb.moTaLoi
                           FROM danserial ds
                           JOIN MauDan md ON ds.maMau = md.maMau
                           JOIN HangDan hd ON md.maHang = hd.maHang
                           LEFT JOIN kho k ON ds.maKho = k.maKho
                           LEFT JOIN phieubaotri pb ON ds.maSerial = pb.maSerial AND pb.trangThai = 'Đã nhập kho'
                           LEFT JOIN khachhang kh ON pb.maKhachHang = kh.maKhachHang
                           WHERE 1=1";
            
            if ($selected_kho > 0) {
                $sql_serial .= " AND k.maKho = $selected_kho";
            }
            if (!empty($search)) {
                $search_param = "%" . $search . "%";
                $sql_serial .= " AND (ds.soSerial LIKE '$searchEscaped%' OR md.tenMau LIKE '%$searchEscaped%' OR hd.tenHang LIKE '%$searchEscaped%')";
            }
            if ($tab_filter === 'sales') {
                $sql_serial .= " AND ds.trangThai = 'Trong kho' AND COALESCE(ds.giaBan, 0) > 0";
            } elseif ($tab_filter === 'baotri') {
                $sql_serial .= " AND ds.trangThai = 'Đang bảo hành'";
            } else {
                $sql_serial .= " AND ds.trangThai IN ('Trong kho', 'Đang bảo hành')";
            }

            $sql_serial .= " ORDER BY ds.trangThai ASC, ds.soSerial ASC";
            $res_serial = $conn->query($sql_serial);
            if($res_serial && $res_serial->num_rows > 0):
            ?>
            <h3 style="margin-bottom: 16px; display: flex; align-items: center; gap: 8px;">
                <span class="material-symbols-rounded" style="color: var(--accent);">qr_code_2</span>
                Danh sách chi tiết từng cây đàn (Serial) trong kho
            </h3>
            <div style="background: var(--bg-card); border-radius: var(--radius-xl); border: 1px solid var(--glass-border); overflow: hidden; margin-bottom: 32px;">
                <table style="width: 100%; border-collapse: collapse; text-align: left;">
                    <thead>
                        <tr style="background: rgba(0,0,0,0.2); border-bottom: 1px solid var(--glass-border);">
                            <th style="padding: 16px; color: var(--text-secondary); font-weight: 600;">Mã Serial</th>
                            <th style="padding: 16px; color: var(--text-secondary); font-weight: 600;">Sản phẩm</th>
                            <th style="padding: 16px; color: var(--text-secondary); font-weight: 600;">Hãng</th>
                            <th style="padding: 16px; color: var(--text-secondary); font-weight: 600;">Vị trí Kho</th>
                            <th style="padding: 16px; color: var(--text-secondary); font-weight: 600;">Phân loại / Trạng thái</th>
                            <th style="padding: 16px; color: var(--text-secondary); font-weight: 600;">Ghi chú bảo trì / Khách hàng</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($s_row = $res_serial->fetch_assoc()): 
                            $st = $s_row['trangThai'];
                            $isBT = ($st === 'Đang bảo hành');
                            $isReadyToSell = ($st === 'Trong kho' && (float)($s_row['giaBan'] ?? 0) > 0);
                        ?>
                        <tr style="border-bottom: 1px solid var(--glass-border); transition: 0.2s; <?= $isBT ? 'background: rgba(245, 158, 11, 0.03);' : '' ?>" onmouseover="this.style.background='rgba(124, 92, 252, 0.05)'" onmouseout="this.style.background='<?= $isBT ? 'rgba(245, 158, 11, 0.03)' : 'transparent' ?>'">
                            <td style="padding: 16px; font-weight: 700; color: var(--accent);"><?php echo htmlspecialchars($s_row['soSerial']); ?></td>
                            <td style="padding: 16px; font-weight: 600; color: var(--text-primary);"><?php echo htmlspecialchars($s_row['tenMau']); ?></td>
                            <td style="padding: 16px; color: var(--text-primary);"><?php echo htmlspecialchars($s_row['tenHang']); ?></td>
                            <td style="padding: 16px; color: var(--text-primary);">
                                <span style="display: inline-flex; align-items: center; gap: 4px; background: rgba(255,255,255,0.05); padding: 4px 8px; border-radius: 6px; font-size: 13px;">
                                    <span class="material-symbols-rounded" style="font-size: 15px;">warehouse</span> <?= htmlspecialchars($s_row['tenKho'] ?? 'Chưa gán kho') ?>
                                </span>
                            </td>
                            <td style="padding: 16px;">
                                <?php if($isBT): ?>
                                    <span style="color: #f59e0b; font-weight: 700; padding: 5px 12px; border-radius: 20px; background: rgba(245, 158, 11, 0.15); border: 1px solid rgba(245, 158, 11, 0.3); font-size: 13px; display: inline-flex; align-items: center; gap: 5px;">
                                        <span class="material-symbols-rounded" style="font-size: 14px;">build</span> Đang bảo hành
                                    </span>
                                <?php elseif($isReadyToSell): ?>
                                    <span style="color: #10b981; font-weight: 700; padding: 5px 12px; border-radius: 20px; background: rgba(16, 185, 129, 0.15); border: 1px solid rgba(16, 185, 129, 0.3); font-size: 13px; display: inline-flex; align-items: center; gap: 5px;">
                                        <span class="material-symbols-rounded" style="font-size: 14px;">check_circle</span> Sẵn sàng bán
                                    </span>
                                <?php else: ?>
                                    <span style="color: #f59e0b; font-weight: 700; padding: 5px 12px; border-radius: 20px; background: rgba(245, 158, 11, 0.15); border: 1px solid rgba(245, 158, 11, 0.3); font-size: 13px; display: inline-flex; align-items: center; gap: 5px;">
                                        <span class="material-symbols-rounded" style="font-size: 14px;">sell</span> Chưa niêm yết giá
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td style="padding: 16px; font-size: 13px; color: var(--text-secondary);">
                                <?php if($isBT && !empty($s_row['maPhieuBT'])): ?>
                                    <div><strong>Phiếu BT:</strong> #<?= $s_row['maPhieuBT'] ?> · <strong>Khách:</strong> <?= htmlspecialchars($s_row['tenKH'] ?? '') ?></div>
                                    <div style="color: var(--text-muted); font-style: italic;"><?= htmlspecialchars($s_row['moTaLoi'] ?? '') ?></div>
                                <?php elseif($isBT): ?>
                                    <span style="color: var(--text-muted);">Hàng gửi bảo hành</span>
                                <?php elseif($isReadyToSell): ?>
                                    <span style="color: var(--text-muted);">Hàng mới trong kho</span>
                                <?php else: ?>
                                    <span style="color: var(--text-muted);">Cần nhập giá bán trước khi sẵn sàng bán</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
        <?php endif; ?>

        <!-- Inventory Grid theo Mẫu Đàn -->
        <?php if(!empty($inventory_items)): ?>
            <div class="inventory-grid">
                <?php foreach($inventory_items as $row): 
                    $ban = intval($row['soLuongBan']);
                    $bt = intval($row['soLuongBaoTri']);
                    $chuaNiemYet = intval($row['soLuongChuaNiemYet']);
                    $tong = $ban + $bt + $chuaNiemYet;
                ?>
                    <div class="inventory-card" style="position: relative;">
                        <div class="inventory-img">
                            <?php if(!empty($row['hinhAnh'])): ?>
                                <img src="images/<?php echo htmlspecialchars($row['hinhAnh']); ?>" alt="<?php echo htmlspecialchars($row['tenMau']); ?>">
                            <?php else: ?>
                                <span class="material-symbols-rounded" style="font-size: 32px; color: rgba(255,255,255,0.1);">piano</span>
                            <?php endif; ?>
                        </div>
                        <div class="inventory-info">
                            <div class="inv-brand"><?= htmlspecialchars($row['tenHang']) ?></div>
                            <div class="inv-name"><?= htmlspecialchars($row['tenMau']) ?></div>
                            <div class="inv-category"><?= htmlspecialchars($row['tenLoai']) ?></div>
                            <div class="inv-warehouse" style="margin-top: 6px;">
                                <span class="material-symbols-rounded" style="font-size: 14px; vertical-align: text-bottom; margin-right: 2px;">warehouse</span> <?= htmlspecialchars($row['tenKho']) ?>
                            </div>
                        </div>
                        <div class="inv-qty" style="min-width: 110px;">
                            <div style="font-size: 24px; font-weight: 800; color: var(--text-primary); text-align: right;">
                                <?= $tong ?> <span style="font-size: 13px; font-weight: 500; color: var(--text-muted);">cây</span>
                            </div>
                            <div style="margin-top: 6px; display: flex; flex-direction: column; gap: 3px; align-items: flex-end;">
                                <?php if($ban > 0): ?>
                                    <span style="font-size: 11px; font-weight: 700; color: #10b981; background: rgba(16, 185, 129, 0.12); padding: 2px 6px; border-radius: 4px;">
                                        <?= $ban ?> sẵn sàng bán
                                    </span>
                                <?php endif; ?>
                                <?php if($bt > 0): ?>
                                    <span style="font-size: 11px; font-weight: 700; color: #f59e0b; background: rgba(245, 158, 11, 0.15); padding: 2px 6px; border-radius: 4px;">
                                        <?= $bt ?> đang bảo trì
                                    </span>
                                <?php endif; ?>
                                <?php if($chuaNiemYet > 0): ?>
                                    <span style="font-size: 11px; font-weight: 700; color: #f59e0b; background: rgba(245, 158, 11, 0.15); padding: 2px 6px; border-radius: 4px;">
                                        <?= $chuaNiemYet ?> chưa niêm yết giá
                                    </span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div style="background: var(--bg-card); border-radius: var(--radius-xl); padding: 48px; text-align: center; border: 1px dashed var(--glass-border);">
                <span class="material-symbols-rounded" style="font-size: 64px; color: var(--text-muted); opacity: 0.5; margin-bottom: 16px; display: block;">inventory_2</span>
                <h3 style="color: var(--text-primary); margin-bottom: 8px;">Không có dữ liệu</h3>
                <p style="color: var(--text-secondary);">Không tìm thấy sản phẩm nào trong kho theo điều kiện lọc.</p>
            </div>
        <?php endif; ?>

    </div>
</div>

<?php include 'includes/footer.php'; ?>
