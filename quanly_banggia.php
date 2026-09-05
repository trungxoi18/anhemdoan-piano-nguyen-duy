<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'db.php';
require_once 'functions.php';

// Phân quyền: Chỉ Quản trị viên (Admin - role 1) mới có quyền truy cập
if (!isset($_SESSION['user_id']) || $_SESSION['role_id'] != 1) {
    header("Location: index.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$fullname = $_SESSION['fullname'];
$title = "Quản lý Bảng Giá & Niêm Yết";

// === XỬ LÝ BACKEND: CẬP NHẬT GIÁ BÁN ===
$msg = "";
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // 1. Cập nhật giá bán cho 1 Serial cụ thể
    if ($action === 'update_single_price') {
        $maSerial = intval($_POST['maSerial'] ?? 0);
        $giaBan = floatval(str_replace([',', '.'], '', $_POST['giaBan'] ?? '0'));

        if ($maSerial > 0 && $giaBan >= 0) {
            $stmt = $conn->prepare("UPDATE danserial SET giaBan = ? WHERE maSerial = ?");
            $stmt->bind_param("di", $giaBan, $maSerial);
            if ($stmt->execute()) {
                // Lấy thông tin serial để ghi log
                $info = $conn->query("SELECT ds.soSerial, md.tenMau FROM danserial ds JOIN maudan md ON ds.maMau = md.maMau WHERE ds.maSerial = $maSerial")->fetch_assoc();
                $serialText = $info ? "{$info['tenMau']} (Serial: {$info['soSerial']})" : "Serial #$maSerial";
                writeLog($conn, 'NIÊM YẾT GIÁ', "Admin cập nhật giá bán $serialText thành " . number_format($giaBan, 0, ',', '.') . " đ");
                
                $_SESSION['flash_success'] = "Cập nhật giá bán thành công cho $serialText!";
            } else {
                $_SESSION['flash_error'] = "Lỗi khi lưu giá bán: " . $conn->error;
            }
        } else {
            $_SESSION['flash_error'] = "Giá bán không hợp lệ!";
        }
        header("Location: quanly_banggia.php" . (!empty($_SERVER['QUERY_STRING']) ? '?' . $_SERVER['QUERY_STRING'] : ''));
        exit();
    }

    // 2. Cập nhật giá bán đồng loạt theo Mẫu Đàn
    if ($action === 'update_model_price') {
        $maMau = intval($_POST['maMau'] ?? 0);
        $giaBan = floatval(str_replace([',', '.'], '', $_POST['giaBan'] ?? '0'));
        $applyToAll = isset($_POST['applyToAll']) && $_POST['applyToAll'] === '1';

        if ($maMau > 0 && $giaBan >= 0) {
            $query = "UPDATE danserial SET giaBan = ? WHERE maMau = ? AND trangThai = 'Trong kho'";
            if (!$applyToAll) {
                // Chỉ áp dụng cho các cây chưa có giá
                $query .= " AND (giaBan IS NULL OR giaBan = 0)";
            }
            $stmt = $conn->prepare($query);
            $stmt->bind_param("di", $giaBan, $maMau);
            if ($stmt->execute()) {
                $affected = $stmt->affected_rows;
                $mauInfo = $conn->query("SELECT tenMau FROM maudan WHERE maMau = $maMau")->fetch_assoc();
                $tenMau = $mauInfo['tenMau'] ?? "Mã #$maMau";
                writeLog($conn, 'NIÊM YẾT GIÁ', "Admin cập nhật giá bán hàng loạt cho mẫu $tenMau ($affected sản phẩm) thành " . number_format($giaBan, 0, ',', '.') . " đ");
                
                $_SESSION['flash_success'] = "Đã cập nhật giá bán " . number_format($giaBan, 0, ',', '.') . " đ cho $affected cây đàn mẫu '$tenMau'!";
            } else {
                $_SESSION['flash_error'] = "Lỗi khi cập nhật giá hàng loạt: " . $conn->error;
            }
        }
        header("Location: quanly_banggia.php" . (!empty($_SERVER['QUERY_STRING']) ? '?' . $_SERVER['QUERY_STRING'] : ''));
        exit();
    }

    // 3. Tự động tính giá bán theo % Lợi nhuận (Markup Margin)
    if ($action === 'auto_margin_price') {
        $marginPercent = floatval($_POST['marginPercent'] ?? 0);
        $onlyUnpriced = isset($_POST['onlyUnpriced']) && $_POST['onlyUnpriced'] === '1';

        if ($marginPercent > 0) {
            $multiplier = 1 + ($marginPercent / 100);
            $sqlAuto = "UPDATE danserial SET giaBan = ROUND(giaNhap * $multiplier, -4) WHERE trangThai = 'Trong kho' AND giaNhap > 0";
            if ($onlyUnpriced) {
                $sqlAuto .= " AND (giaBan IS NULL OR giaBan = 0)";
            }
            if ($conn->query($sqlAuto)) {
                $affected = $conn->affected_rows;
                writeLog($conn, 'NIÊM YẾT GIÁ', "Admin áp dụng tỷ suất lợi nhuận $marginPercent% cho $affected cây đàn");
                $_SESSION['flash_success'] = "Đã tự động niêm yết giá theo tỷ suất lợi nhuận +$marginPercent% cho $affected sản phẩm!";
            } else {
                $_SESSION['flash_error'] = "Lỗi khi tính giá tự động: " . $conn->error;
            }
        }
        header("Location: quanly_banggia.php" . (!empty($_SERVER['QUERY_STRING']) ? '?' . $_SERVER['QUERY_STRING'] : ''));
        exit();
    }
}

// Flash messages
if (isset($_SESSION['flash_success'])) {
    $msg = "<div class='alert alert-success'><span class='material-symbols-rounded'>check_circle</span> " . htmlspecialchars($_SESSION['flash_success']) . "</div>";
    unset($_SESSION['flash_success']);
}
if (isset($_SESSION['flash_error'])) {
    $msg = "<div class='alert alert-danger'><span class='material-symbols-rounded'>error</span> " . htmlspecialchars($_SESSION['flash_error']) . "</div>";
    unset($_SESSION['flash_error']);
}

// === THỐNG KÊ TỔNG QUAN VỀ BẢNG GIÁ ===
$stat_total_stock = 0;
$stat_unpriced_count = 0;
$stat_priced_count = 0;
$stat_total_retail_val = 0;

$stat_sql = "SELECT 
                COUNT(maSerial) as total_stock,
                SUM(CASE WHEN giaBan IS NULL OR giaBan = 0 THEN 1 ELSE 0 END) as unpriced_count,
                SUM(CASE WHEN giaBan > 0 THEN 1 ELSE 0 END) as priced_count,
                SUM(CASE WHEN giaBan > 0 THEN giaBan ELSE 0 END) as total_retail
             FROM danserial 
             WHERE trangThai = 'Trong kho'";
$res_stat = $conn->query($stat_sql);
if ($res_stat && $row = $res_stat->fetch_assoc()) {
    $stat_total_stock = $row['total_stock'] ?? 0;
    $stat_unpriced_count = $row['unpriced_count'] ?? 0;
    $stat_priced_count = $row['priced_count'] ?? 0;
    $stat_total_retail_val = $row['total_retail'] ?? 0;
}

// === BỘ LỌC TÌM KIẾM ===
$filter_status = $_GET['status'] ?? 'all'; // all, unpriced, priced
$filter_hang = intval($_GET['hang'] ?? 0);
$filter_loai = intval($_GET['loai'] ?? 0);
$filter_kho = intval($_GET['kho'] ?? 0);
$filter_search = trim($_GET['search'] ?? '');

$where_clauses = ["ds.trangThai = 'Trong kho'"];

if ($filter_status === 'unpriced') {
    $where_clauses[] = "(ds.giaBan IS NULL OR ds.giaBan = 0)";
} elseif ($filter_status === 'priced') {
    $where_clauses[] = "ds.giaBan > 0";
}

if ($filter_hang > 0) {
    $where_clauses[] = "md.maHang = $filter_hang";
}
if ($filter_loai > 0) {
    $where_clauses[] = "md.maLoai = $filter_loai";
}
if ($filter_kho > 0) {
    $where_clauses[] = "ds.maKho = $filter_kho";
}
if (!empty($filter_search)) {
    $searchEscaped = $conn->real_escape_string($filter_search);
    $where_clauses[] = "(ds.soSerial LIKE '%$searchEscaped%' OR md.tenMau LIKE '%$searchEscaped%' OR hd.tenHang LIKE '%$searchEscaped%')";
}

$where_sql = implode(' AND ', $where_clauses);

// Lấy danh sách sản phẩm theo bộ lọc
$sql_list = "SELECT ds.maSerial, ds.soSerial, ds.giaNhap, ds.giaBan, ds.tinhTrang,
                    md.maMau, md.tenMau, md.hinhAnh,
                    hd.tenHang, ld.tenLoai, k.tenKho
             FROM danserial ds
             JOIN maudan md ON ds.maMau = md.maMau
             JOIN hangdan hd ON md.maHang = hd.maHang
             JOIN loaidan ld ON md.maLoai = ld.maLoai
             JOIN kho k ON ds.maKho = k.maKho
             WHERE $where_sql
             ORDER BY (ds.giaBan IS NULL OR ds.giaBan = 0) DESC, ds.maSerial DESC";
$res_products = $conn->query($sql_list);

// Danh sách danh mục để lọc
$list_hangs = $conn->query("SELECT * FROM hangdan ORDER BY tenHang ASC");
$list_loais = $conn->query("SELECT * FROM loaidan ORDER BY tenLoai ASC");
$list_khos = $conn->query("SELECT * FROM kho ORDER BY tenKho ASC");
$list_maus = $conn->query("SELECT maMau, tenMau FROM maudan ORDER BY tenMau ASC");
?>

<?php include 'includes/header.php'; ?>
<?php include 'includes/sidebar.php'; ?>

<div class="main-wrapper">
    <?php include 'includes/topbar.php'; ?>

    <div class="content">
        <!-- Header Banner -->
        <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 16px; margin-bottom: 24px;">
            <div>
                <h1 style="font-size: 24px; font-weight: 800; color: var(--text-primary); margin: 0; display: flex; align-items: center; gap: 10px;">
                    <span class="material-symbols-rounded" style="color: var(--accent); font-size: 32px; background: var(--info-bg); padding: 8px; border-radius: 12px;">sell</span>
                    Quản Lý Bảng Giá & Niêm Yết
                </h1>
                <p style="color: var(--text-secondary); margin: 6px 0 0 0; font-size: 14px;">
                    Thẩm quyền Quản trị viên: Thiết lập, điều chỉnh và niêm yết giá bán chính thức cho từng cây đàn trong kho.
                </p>
            </div>
            
            <div style="display: flex; gap: 10px; flex-wrap: wrap;">
                <button type="button" class="btn btn-action" onclick="openBulkModal()" style="background: var(--bg-card); border: 1px solid var(--border); color: var(--text-primary);">
                    <span class="material-symbols-rounded" style="color: var(--accent-secondary);">style</span>
                    Áp giá theo Mẫu đàn
                </button>
                <button type="button" class="btn btn-action btn-add-new" onclick="openMarginModal()">
                    <span class="material-symbols-rounded">percent</span>
                    Tự động tính % Lợi nhuận
                </button>
            </div>
        </div>

        <?php echo $msg; ?>

        <!-- KPI Summary Cards -->
        <div class="premium-kpi-row" style="margin-bottom: 24px;">
            <div class="premium-kpi-card" style="--card-color: #3b82f6; --card-bg-light: #eff6ff; --card-shadow-hover: rgba(59, 130, 246, 0.2);">
                <div class="premium-kpi-icon">
                    <span class="material-symbols-rounded">inventory_2</span>
                </div>
                <div class="premium-kpi-info">
                    <p>Tổng đàn trong kho</p>
                    <h3><?= number_format($stat_total_stock) ?> <span style="font-size: 14px; font-weight: 500; color: var(--text-muted);">cây</span></h3>
                    <span class="kpi-sub">
                        <span class="material-symbols-rounded" style="font-size: 16px; color: #3b82f6;">warehouse</span>
                        Tất cả các kho
                    </span>
                </div>
            </div>

            <div class="premium-kpi-card" style="--card-color: <?= $stat_unpriced_count > 0 ? '#ef4444' : '#10b981' ?>; --card-bg-light: <?= $stat_unpriced_count > 0 ? '#fef2f2' : '#ecfdf5' ?>; --card-shadow-hover: rgba(239, 68, 68, 0.2);">
                <div class="premium-kpi-icon">
                    <span class="material-symbols-rounded"><?= $stat_unpriced_count > 0 ? 'warning' : 'verified' ?></span>
                </div>
                <div class="premium-kpi-info">
                    <p>Chưa niêm yết giá</p>
                    <h3 style="color: <?= $stat_unpriced_count > 0 ? '#ef4444' : '#10b981' ?>;">
                        <?= number_format($stat_unpriced_count) ?> <span style="font-size: 14px; font-weight: 500; color: var(--text-muted);">cây</span>
                    </h3>
                    <span class="kpi-sub">
                        <span class="material-symbols-rounded" style="font-size: 16px; color: <?= $stat_unpriced_count > 0 ? '#ef4444' : '#10b981' ?>;">
                            <?= $stat_unpriced_count > 0 ? 'priority_high' : 'check_circle' ?>
                        </span>
                        <?= $stat_unpriced_count > 0 ? 'Cần Admin nhập giá ngay' : 'Đã niêm yết 100%' ?>
                    </span>
                </div>
            </div>

            <div class="premium-kpi-card" style="--card-color: #10b981; --card-bg-light: #ecfdf5; --card-shadow-hover: rgba(16, 185, 129, 0.2);">
                <div class="premium-kpi-icon">
                    <span class="material-symbols-rounded">price_check</span>
                </div>
                <div class="premium-kpi-info">
                    <p>Đã niêm yết giá</p>
                    <h3><?= number_format($stat_priced_count) ?> <span style="font-size: 14px; font-weight: 500; color: var(--text-muted);">cây</span></h3>
                    <span class="kpi-sub">
                        <span class="material-symbols-rounded" style="font-size: 16px; color: #10b981;">shopping_cart_checkout</span>
                        Sẵn sàng xuất hóa đơn bán
                    </span>
                </div>
            </div>

            <div class="premium-kpi-card" style="--card-color: #8b5cf6; --card-bg-light: #f5f3ff; --card-shadow-hover: rgba(139, 92, 246, 0.2);">
                <div class="premium-kpi-icon">
                    <span class="material-symbols-rounded">monetization_on</span>
                </div>
                <div class="premium-kpi-info">
                    <p>Tổng giá trị niêm yết</p>
                    <h3 style="font-size: 1.4rem;"><?= number_format($stat_total_retail_val, 0, ',', '.') ?> <span style="font-size: 13px; font-weight: 500;">đ</span></h3>
                    <span class="kpi-sub">
                        <span class="material-symbols-rounded" style="font-size: 16px; color: #8b5cf6;">trending_up</span>
                        Dự thu bán lẻ toàn kho
                    </span>
                </div>
            </div>
        </div>

        <!-- Filter & Search Bar -->
        <div style="background: var(--bg-card); border: 1px solid var(--border); border-radius: var(--radius-xl); padding: 20px; margin-bottom: 24px; box-shadow: var(--shadow-sm);">
            <form method="GET" action="quanly_banggia.php" style="display: flex; flex-wrap: wrap; gap: 14px; align-items: center;">
                
                <!-- Status Filter (Tabs) -->
                <div style="display: flex; background: var(--bg-tertiary); padding: 4px; border-radius: var(--radius-md); border: 1px solid var(--border);">
                    <a href="quanly_banggia.php?status=all" class="btn" style="padding: 6px 14px; font-size: 13px; border: none; border-radius: 6px; <?= $filter_status === 'all' ? 'background: var(--accent); color: white;' : 'background: transparent; color: var(--text-secondary);' ?>">
                        Tất cả (<?= $stat_total_stock ?>)
                    </a>
                    <a href="quanly_banggia.php?status=unpriced" class="btn" style="padding: 6px 14px; font-size: 13px; border: none; border-radius: 6px; <?= $filter_status === 'unpriced' ? 'background: #ef4444; color: white;' : 'background: transparent; color: #ef4444;' ?>">
                        ⚠️ Chưa có giá (<?= $stat_unpriced_count ?>)
                    </a>
                    <a href="quanly_banggia.php?status=priced" class="btn" style="padding: 6px 14px; font-size: 13px; border: none; border-radius: 6px; <?= $filter_status === 'priced' ? 'background: #10b981; color: white;' : 'background: transparent; color: #10b981;' ?>">
                        Đã có giá (<?= $stat_priced_count ?>)
                    </a>
                </div>

                <!-- Hidden Status to preserve when filtering -->
                <input type="hidden" name="status" value="<?= htmlspecialchars($filter_status) ?>">

                <!-- Brand Filter -->
                <select name="hang" class="custom-input" style="padding: 8px 12px; border-radius: var(--radius-md); border: 1px solid var(--border); background: var(--bg-secondary); color: var(--text-primary); font-size: 13px; outline: none;" onchange="this.form.submit()">
                    <option value="0">-- Tất cả Hãng --</option>
                    <?php while ($h = $list_hangs->fetch_assoc()): ?>
                        <option value="<?= $h['maHang'] ?>" <?= $filter_hang == $h['maHang'] ? 'selected' : '' ?>><?= htmlspecialchars($h['tenHang']) ?></option>
                    <?php endwhile; ?>
                </select>

                <!-- Category Filter -->
                <select name="loai" class="custom-input" style="padding: 8px 12px; border-radius: var(--radius-md); border: 1px solid var(--border); background: var(--bg-secondary); color: var(--text-primary); font-size: 13px; outline: none;" onchange="this.form.submit()">
                    <option value="0">-- Tất cả Phân loại --</option>
                    <?php while ($l = $list_loais->fetch_assoc()): ?>
                        <option value="<?= $l['maLoai'] ?>" <?= $filter_loai == $l['maLoai'] ? 'selected' : '' ?>><?= htmlspecialchars($l['tenLoai']) ?></option>
                    <?php endwhile; ?>
                </select>

                <!-- Warehouse Filter -->
                <select name="kho" class="custom-input" style="padding: 8px 12px; border-radius: var(--radius-md); border: 1px solid var(--border); background: var(--bg-secondary); color: var(--text-primary); font-size: 13px; outline: none;" onchange="this.form.submit()">
                    <option value="0">-- Tất cả Kho --</option>
                    <?php while ($k = $list_khos->fetch_assoc()): ?>
                        <option value="<?= $k['maKho'] ?>" <?= $filter_kho == $k['maKho'] ? 'selected' : '' ?>><?= htmlspecialchars($k['tenKho']) ?></option>
                    <?php endwhile; ?>
                </select>

                <!-- Search Input -->
                <div style="position: relative; flex: 1; min-width: 200px;">
                    <input type="text" name="search" value="<?= htmlspecialchars($filter_search) ?>" placeholder="Tìm số Serial, tên mẫu đàn..." class="custom-input" style="width: 100%; padding: 8px 12px 8px 36px; border-radius: var(--radius-md); border: 1px solid var(--border); font-size: 13px; background: var(--bg-secondary); color: var(--text-primary); outline: none;">
                    <span class="material-symbols-rounded" style="position: absolute; left: 10px; top: 50%; transform: translateY(-50%); font-size: 18px; color: var(--text-muted);">search</span>
                </div>

                <button type="submit" class="btn btn-primary" style="padding: 8px 16px; font-size: 13px;">
                    Lọc dữ liệu
                </button>

                <?php if ($filter_hang > 0 || $filter_loai > 0 || $filter_kho > 0 || !empty($filter_search) || $filter_status !== 'all'): ?>
                    <a href="quanly_banggia.php" class="btn" style="padding: 8px 12px; font-size: 13px; color: var(--text-muted); text-decoration: none;">
                        Xóa lọc
                    </a>
                <?php endif; ?>
            </form>
        </div>

        <!-- Table Listing -->
        <div class="table-card" style="margin-bottom: 40px;">
            <div style="overflow-x: auto;">
                <table style="width: 100%; border-collapse: collapse; text-align: left; font-size: 14px;">
                    <thead>
                        <tr style="background: var(--bg-tertiary); border-bottom: 1px solid var(--border); color: var(--text-secondary); font-weight: 600;">
                            <th style="padding: 16px 20px;">Sản phẩm / Mẫu đàn</th>
                            <th style="padding: 16px;">Số Serial</th>
                            <th style="padding: 16px;">Kho lưu trữ</th>
                            <th style="padding: 16px;">Giá vốn (Giá nhập)</th>
                            <th style="padding: 16px;">Giá bán niêm yết</th>
                            <th style="padding: 16px;">Biên lợi nhuận</th>
                            <th style="padding: 16px; text-align: center;">Trạng thái</th>
                            <th style="padding: 16px 20px; text-align: right;">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($res_products && $res_products->num_rows > 0): ?>
                            <?php while ($row = $res_products->fetch_assoc()): 
                                $isPriced = ($row['giaBan'] > 0);
                                $giaNhap = (float)$row['giaNhap'];
                                $giaBan = (float)$row['giaBan'];
                                $profit = $giaBan - $giaNhap;
                                $marginPct = ($giaNhap > 0 && $giaBan > 0) ? round(($profit / $giaNhap) * 100, 1) : 0;
                            ?>
                            <tr style="border-bottom: 1px solid var(--border); transition: 0.2s;" onmouseover="this.style.background='var(--bg-card-hover)'" onmouseout="this.style.background='transparent'">
                                <!-- Sản phẩm -->
                                <td style="padding: 16px 20px;">
                                    <div style="display: flex; align-items: center; gap: 12px;">
                                        <div style="width: 44px; height: 44px; border-radius: 8px; background: var(--bg-tertiary); display: flex; align-items: center; justify-content: center; flex-shrink: 0; overflow: hidden; border: 1px solid var(--border);">
                                            <?php if (!empty($row['hinhAnh'])): ?>
                                                <img src="images/<?= htmlspecialchars($row['hinhAnh']) ?>" alt="Piano" style="width: 100%; height: 100%; object-fit: contain;">
                                            <?php else: ?>
                                                <span class="material-symbols-rounded" style="color: var(--text-muted); font-size: 22px;">piano</span>
                                            <?php endif; ?>
                                        </div>
                                        <div>
                                            <div style="font-weight: 700; color: var(--text-primary);"><?= htmlspecialchars($row['tenMau']) ?></div>
                                            <div style="font-size: 12px; color: var(--text-muted); margin-top: 2px;">
                                                <span style="color: var(--accent); font-weight: 600;"><?= htmlspecialchars($row['tenHang']) ?></span> • <?= htmlspecialchars($row['tenLoai']) ?>
                                            </div>
                                        </div>
                                    </div>
                                </td>

                                <!-- Serial -->
                                <td style="padding: 16px;">
                                    <span style="font-family: monospace; font-weight: 700; font-size: 13.5px; background: var(--bg-tertiary); padding: 4px 8px; border-radius: 6px; border: 1px solid var(--border);">
                                        <?= htmlspecialchars($row['soSerial']) ?>
                                    </span>
                                </td>

                                <!-- Kho -->
                                <td style="padding: 16px; color: var(--text-secondary);">
                                    <span class="material-symbols-rounded" style="font-size: 16px; vertical-align: middle; color: var(--text-muted);">store</span>
                                    <?= htmlspecialchars($row['tenKho']) ?>
                                </td>

                                <!-- Giá nhập -->
                                <td style="padding: 16px; font-weight: 600; color: var(--text-secondary);">
                                    <?= number_format($giaNhap, 0, ',', '.') ?> đ
                                </td>

                                <!-- Giá bán niêm yết -->
                                <td style="padding: 16px;">
                                    <?php if ($isPriced): ?>
                                        <div style="font-weight: 800; color: #10b981; font-size: 15px;">
                                            <?= number_format($giaBan, 0, ',', '.') ?> đ
                                        </div>
                                    <?php else: ?>
                                        <div style="color: #ef4444; font-weight: 700; font-size: 13px; display: flex; align-items: center; gap: 4px;">
                                            <span class="material-symbols-rounded" style="font-size: 16px;">error</span>
                                            Chưa niêm yết
                                        </div>
                                    <?php endif; ?>
                                </td>

                                <!-- Biên lợi nhuận -->
                                <td style="padding: 16px;">
                                    <?php if ($isPriced && $giaNhap > 0): ?>
                                        <div style="font-size: 13px; font-weight: 700; color: <?= $profit >= 0 ? '#10b981' : '#ef4444' ?>;">
                                            <?= $profit >= 0 ? '+' : '' ?><?= number_format($profit, 0, ',', '.') ?> đ
                                            <span style="font-size: 11px; padding: 2px 6px; border-radius: 4px; background: <?= $profit >= 0 ? 'rgba(16,185,129,0.1)' : 'rgba(239,68,68,0.1)' ?>; margin-left: 4px;">
                                                <?= $profit >= 0 ? '+' : '' ?><?= $marginPct ?>%
                                            </span>
                                        </div>
                                    <?php else: ?>
                                        <span style="color: var(--text-muted); font-size: 13px;">--</span>
                                    <?php endif; ?>
                                </td>

                                <!-- Trạng thái Badge -->
                                <td style="padding: 16px; text-align: center;">
                                    <?php if ($isPriced): ?>
                                        <span class="badge-pill badge-success" style="font-size: 11px;">Đã có giá</span>
                                    <?php else: ?>
                                        <span class="badge-pill badge-danger" style="font-size: 11px;">Cần nhập giá</span>
                                    <?php endif; ?>
                                </td>

                                <!-- Thao tác -->
                                <td style="padding: 16px 20px; text-align: right;">
                                    <button type="button" class="btn" 
                                            data-maserial="<?= $row['maSerial'] ?>"
                                            data-tenmau="<?= htmlspecialchars($row['tenMau'], ENT_QUOTES, 'UTF-8') ?>"
                                            data-soserial="<?= htmlspecialchars($row['soSerial'], ENT_QUOTES, 'UTF-8') ?>"
                                            data-gianhap="<?= $giaNhap ?>"
                                            data-giaban="<?= $giaBan ?>"
                                            onclick="openSinglePriceModal(this)" 
                                            style="padding: 6px 12px; font-size: 13px; font-weight: 600; <?= $isPriced ? 'background: var(--bg-tertiary); color: var(--accent);' : 'background: linear-gradient(135deg, #10b981, #059669); color: white;' ?>">
                                        <span class="material-symbols-rounded" style="font-size: 16px;"><?= $isPriced ? 'edit' : 'add_circle' ?></span>
                                        <?= $isPriced ? 'Sửa giá' : 'Niêm yết giá' ?>
                                    </button>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="8" style="padding: 40px; text-align: center; color: var(--text-muted);">
                                    <span class="material-symbols-rounded" style="font-size: 48px; color: #cbd5e1; display: block; margin-bottom: 8px;">inventory</span>
                                    Không tìm thấy sản phẩm nào phù hợp với điều kiện lọc.
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- =========================================================
     MODAL 1: CẬP NHẬT GIÁ BÁN TỪNG CÂY ĐÀN (Single Serial)
     ========================================================= -->
<div id="modalSinglePrice" class="modal-overlay">
    <div class="modal-card" style="max-width: 480px; width: 90%; background: var(--bg-card); border-radius: var(--radius-xl); padding: 28px; box-shadow: var(--shadow-lg); border: 1px solid var(--border);">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
            <h3 style="margin: 0; font-size: 18px; font-weight: 800; color: var(--text-primary); display: flex; align-items: center; gap: 8px;">
                <span class="material-symbols-rounded" style="color: var(--accent);">price_change</span>
                Niêm Yết Giá Bán
            </h3>
            <button type="button" onclick="closeModal('modalSinglePrice')" style="background: none; border: none; font-size: 24px; cursor: pointer; color: var(--text-muted); line-height: 1;">&times;</button>
        </div>

        <form method="POST" action="quanly_banggia.php<?= !empty($_SERVER['QUERY_STRING']) ? '?' . htmlspecialchars($_SERVER['QUERY_STRING']) : '' ?>">
            <input type="hidden" name="action" value="update_single_price">
            <input type="hidden" name="maSerial" id="single_maSerial">

            <div style="background: var(--bg-tertiary); padding: 14px 16px; border-radius: var(--radius-md); margin-bottom: 20px; font-size: 13.5px; border: 1px solid var(--border);">
                <div>Mẫu đàn: <strong id="single_tenMau" style="color: var(--text-primary);">--</strong></div>
                <div style="margin-top: 4px;">Số Serial: <code id="single_soSerial" style="color: var(--accent); font-weight: 700;">--</code></div>
                <div style="margin-top: 4px;">Giá vốn nhập: <strong id="single_giaNhap" style="color: var(--text-secondary);">--</strong></div>
            </div>

            <div style="margin-bottom: 20px;">
                <label style="display: block; font-weight: 700; font-size: 13px; margin-bottom: 8px; color: var(--text-primary);">
                    Giá bán niêm yết chính thức (VNĐ) <span style="color: #ef4444;">*</span>
                </label>
                <div style="position: relative;">
                    <input type="number" name="giaBan" id="single_giaBan" step="10000" min="0" required placeholder="Nhập giá bán (VD: 85000000)" class="custom-input" style="width: 100%; padding: 12px 14px; font-size: 16px; font-weight: 700; border-radius: var(--radius-md); border: 2px solid var(--accent); color: var(--text-primary); outline: none;" oninput="calcSingleProfit()">
                </div>
                <div id="single_calc_preview" style="font-size: 12.5px; color: var(--text-muted); margin-top: 8px;">
                    Dự kiến lợi nhuận: <span id="single_profit_text">--</span>
                </div>
            </div>

            <!-- Gợi ý nhanh % lợi nhuận -->
            <div style="margin-bottom: 24px;">
                <div style="font-size: 12px; font-weight: 600; color: var(--text-muted); margin-bottom: 6px;">Gợi ý nhanh theo % lợi nhuận:</div>
                <div style="display: flex; gap: 6px; flex-wrap: wrap;">
                    <button type="button" class="btn" onclick="applySingleMargin(15)" style="padding: 4px 10px; font-size: 12px;">+15%</button>
                    <button type="button" class="btn" onclick="applySingleMargin(20)" style="padding: 4px 10px; font-size: 12px;">+20%</button>
                    <button type="button" class="btn" onclick="applySingleMargin(25)" style="padding: 4px 10px; font-size: 12px;">+25%</button>
                    <button type="button" class="btn" onclick="applySingleMargin(30)" style="padding: 4px 10px; font-size: 12px;">+30%</button>
                    <button type="button" class="btn" onclick="applySingleMargin(40)" style="padding: 4px 10px; font-size: 12px;">+40%</button>
                </div>
            </div>

            <div style="display: flex; gap: 10px; justify-content: flex-end;">
                <button type="button" class="btn" onclick="closeModal('modalSinglePrice')" style="padding: 10px 18px;">Hủy</button>
                <button type="submit" class="btn btn-primary" style="padding: 10px 22px; font-weight: 700;">
                    <span class="material-symbols-rounded" style="font-size: 18px;">save</span>
                    Lưu giá niêm yết
                </button>
            </div>
        </form>
    </div>
</div>

<!-- =========================================================
     MODAL 2: ÁP DỤNG GIÁ ĐỒNG LOẠT THEO MẪU ĐÀN (Bulk Model)
     ========================================================= -->
<div id="modalBulkPrice" class="modal-overlay">
    <div class="modal-card" style="max-width: 500px; width: 90%; background: var(--bg-card); border-radius: var(--radius-xl); padding: 28px; box-shadow: var(--shadow-lg); border: 1px solid var(--border);">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
            <h3 style="margin: 0; font-size: 18px; font-weight: 800; color: var(--text-primary); display: flex; align-items: center; gap: 8px;">
                <span class="material-symbols-rounded" style="color: var(--accent-secondary);">style</span>
                Niêm Yết Giá Đồng Loạt Theo Mẫu
            </h3>
            <button type="button" onclick="closeModal('modalBulkPrice')" style="background: none; border: none; font-size: 24px; cursor: pointer; color: var(--text-muted); line-height: 1;">&times;</button>
        </div>

        <form method="POST" action="quanly_banggia.php<?= !empty($_SERVER['QUERY_STRING']) ? '?' . htmlspecialchars($_SERVER['QUERY_STRING']) : '' ?>">
            <input type="hidden" name="action" value="update_model_price">

            <div style="margin-bottom: 16px;">
                <label style="display: block; font-weight: 700; font-size: 13px; margin-bottom: 6px; color: var(--text-primary);">
                    Chọn Mẫu Đàn <span style="color: #ef4444;">*</span>
                </label>
                <select name="maMau" required class="custom-input" style="width: 100%; padding: 10px 12px; border-radius: var(--radius-md); border: 1px solid var(--border); font-size: 14px; background: var(--bg-secondary); color: var(--text-primary);">
                    <option value="">-- Chọn mẫu đàn --</option>
                    <?php 
                    $list_maus->data_seek(0);
                    while ($m = $list_maus->fetch_assoc()): 
                    ?>
                        <option value="<?= $m['maMau'] ?>"><?= htmlspecialchars($m['tenMau']) ?></option>
                    <?php endwhile; ?>
                </select>
            </div>

            <div style="margin-bottom: 16px;">
                <label style="display: block; font-weight: 700; font-size: 13px; margin-bottom: 6px; color: var(--text-primary);">
                    Giá bán niêm yết áp dụng (VNĐ) <span style="color: #ef4444;">*</span>
                </label>
                <input type="number" name="giaBan" step="10000" min="0" required placeholder="Nhập giá bán chung cho mẫu này" class="custom-input" style="width: 100%; padding: 12px; font-size: 15px; font-weight: 700; border-radius: var(--radius-md); border: 1px solid var(--border); background: var(--bg-secondary); color: var(--text-primary);">
            </div>

            <div style="margin-bottom: 24px; background: var(--bg-tertiary); padding: 12px; border-radius: var(--radius-md);">
                <label style="display: flex; align-items: center; gap: 8px; font-size: 13px; cursor: pointer; color: var(--text-primary);">
                    <input type="checkbox" name="applyToAll" value="1" style="width: 16px; height: 16px; accent-color: var(--accent);">
                    <span>Ghi đè giá cho cả những cây đàn <strong>đã có giá</strong> trước đó</span>
                </label>
                <small style="display: block; color: var(--text-muted); margin-top: 4px; padding-left: 24px;">(Nếu không chọn, hệ thống chỉ cập nhật cho các cây đàn chưa có giá)</small>
            </div>

            <div style="display: flex; gap: 10px; justify-content: flex-end;">
                <button type="button" class="btn" onclick="closeModal('modalBulkPrice')" style="padding: 10px 18px;">Hủy</button>
                <button type="submit" class="btn btn-primary" style="padding: 10px 22px; font-weight: 700;">
                    Áp dụng cho toàn bộ mẫu
                </button>
            </div>
        </form>
    </div>
</div>

<!-- =========================================================
     MODAL 3: TỰ ĐỘNG TÍNH GIÁ THEO TỶ SUẤT % LỢI NHUẬN
     ========================================================= -->
<div id="modalMarginPrice" class="modal-overlay">
    <div class="modal-card" style="max-width: 500px; width: 90%; background: var(--bg-card); border-radius: var(--radius-xl); padding: 28px; box-shadow: var(--shadow-lg); border: 1px solid var(--border);">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
            <h3 style="margin: 0; font-size: 18px; font-weight: 800; color: var(--text-primary); display: flex; align-items: center; gap: 8px;">
                <span class="material-symbols-rounded" style="color: #10b981;">percent</span>
                Tự Động Tính Giá Theo % Lợi Nhuận
            </h3>
            <button type="button" onclick="closeModal('modalMarginPrice')" style="background: none; border: none; font-size: 24px; cursor: pointer; color: var(--text-muted); line-height: 1;">&times;</button>
        </div>

        <form method="POST" action="quanly_banggia.php<?= !empty($_SERVER['QUERY_STRING']) ? '?' . htmlspecialchars($_SERVER['QUERY_STRING']) : '' ?>" onsubmit="return confirm('Bạn có chắc chắn muốn áp dụng công thức tự động định giá cho toàn kho không?');">
            <input type="hidden" name="action" value="auto_margin_price">

            <div style="background: rgba(16,185,129,0.08); border: 1px solid rgba(16,185,129,0.2); border-radius: var(--radius-md); padding: 14px; margin-bottom: 20px; font-size: 13.5px; color: var(--text-secondary);">
                Công thức: <strong>Giá bán = Giá vốn nhập &times; (1 + % Lợi nhuận)</strong><br>
                <small style="color: var(--text-muted);">(Hệ thống tự động làm tròn số đến hàng chục nghìn đồng để giá đẹp)</small>
            </div>

            <div style="margin-bottom: 20px;">
                <label style="display: block; font-weight: 700; font-size: 13px; margin-bottom: 6px; color: var(--text-primary);">
                    Tỷ suất lợi nhuận mong muốn (%) <span style="color: #ef4444;">*</span>
                </label>
                <div style="display: flex; align-items: center; gap: 10px;">
                    <input type="number" name="marginPercent" min="1" max="500" step="0.5" value="25" required class="custom-input" style="width: 140px; padding: 12px; font-size: 16px; font-weight: 700; border-radius: var(--radius-md); border: 2px solid #10b981; text-align: center;">
                    <span style="font-weight: 700; font-size: 16px; color: var(--text-primary);">%</span>
                </div>
            </div>

            <div style="margin-bottom: 24px; background: var(--bg-tertiary); padding: 12px; border-radius: var(--radius-md);">
                <label style="display: flex; align-items: center; gap: 8px; font-size: 13px; cursor: pointer; color: var(--text-primary);">
                    <input type="checkbox" name="onlyUnpriced" value="1" checked style="width: 16px; height: 16px; accent-color: #10b981;">
                    <span>Chỉ áp dụng cho các cây đàn <strong>chưa có giá</strong></span>
                </label>
            </div>

            <div style="display: flex; gap: 10px; justify-content: flex-end;">
                <button type="button" class="btn" onclick="closeModal('modalMarginPrice')" style="padding: 10px 18px;">Hủy</button>
                <button type="submit" class="btn" style="background: linear-gradient(135deg, #10b981, #059669); color: white; padding: 10px 22px; font-weight: 700; border: none; cursor: pointer;">
                    Tính & Lưu giá tự động
                </button>
            </div>
        </form>
    </div>
</div>

<script>
let currentGiaNhap = 0;

function openSinglePriceModal(btn) {
    const maSerial = btn.getAttribute('data-maserial');
    const tenMau = btn.getAttribute('data-tenmau');
    const soSerial = btn.getAttribute('data-soserial');
    const giaNhap = parseFloat(btn.getAttribute('data-gianhap')) || 0;
    const giaBan = parseFloat(btn.getAttribute('data-giaban')) || 0;

    document.getElementById('single_maSerial').value = maSerial;
    document.getElementById('single_tenMau').textContent = tenMau;
    document.getElementById('single_soSerial').textContent = soSerial;
    document.getElementById('single_giaNhap').textContent = new Intl.NumberFormat('vi-VN').format(giaNhap) + ' đ';
    document.getElementById('single_giaBan').value = giaBan > 0 ? giaBan : '';
    currentGiaNhap = giaNhap;
    calcSingleProfit();

    const modal = document.getElementById('modalSinglePrice');
    if (modal) {
        modal.classList.add('active');
        document.getElementById('single_giaBan').focus();
    }
}

function calcSingleProfit() {
    const giaBan = parseFloat(document.getElementById('single_giaBan').value) || 0;
    const profitElem = document.getElementById('single_profit_text');
    if (giaBan > 0 && currentGiaNhap > 0) {
        const profit = giaBan - currentGiaNhap;
        const pct = ((profit / currentGiaNhap) * 100).toFixed(1);
        const color = profit >= 0 ? '#10b981' : '#ef4444';
        profitElem.innerHTML = `<strong style="color: ${color};">${profit >= 0 ? '+' : ''}${new Intl.NumberFormat('vi-VN').format(profit)} đ (${pct}%)</strong>`;
    } else {
        profitElem.textContent = '--';
    }
}

function applySingleMargin(pct) {
    if (currentGiaNhap > 0) {
        const calculated = Math.round((currentGiaNhap * (1 + pct / 100)) / 10000) * 10000;
        document.getElementById('single_giaBan').value = calculated;
        calcSingleProfit();
    }
}

function openBulkModal() {
    const modal = document.getElementById('modalBulkPrice');
    if (modal) modal.classList.add('active');
}

function openMarginModal() {
    const modal = document.getElementById('modalMarginPrice');
    if (modal) modal.classList.add('active');
}

function closeModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) modal.classList.remove('active');
}

// Đóng modal khi click ra ngoài overlay
window.addEventListener('click', function(event) {
    if (event.target.classList.contains('modal-overlay')) {
        event.target.classList.remove('active');
    }
});
</script>

<?php include 'includes/footer.php'; ?>
