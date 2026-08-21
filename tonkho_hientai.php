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
$title = "Tồn kho hiện tại";

// Lấy danh sách kho
$khos = $conn->query("SELECT * FROM kho");
$selected_kho = isset($_GET['ma_kho']) ? intval($_GET['ma_kho']) : 0;
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

// Lấy dữ liệu tồn kho
$sql = "SELECT md.maMau, md.tenMau, md.hinhAnh, hd.tenHang, ld.tenLoai, 
        COUNT(ds.maSerial) as soLuongTon, k.tenKho
        FROM MauDan md
        JOIN HangDan hd ON md.maHang = hd.maHang
        JOIN LoaiDan ld ON md.maLoai = ld.maLoai
        JOIN danserial ds ON ds.maMau = md.maMau AND ds.trangThai = 'Trong kho'
        JOIN kho k ON ds.maKho = k.maKho
        WHERE 1=1";

if ($selected_kho > 0) {
    $sql .= " AND k.maKho = $selected_kho";
}

if (!empty($search)) {
    $searchEscaped = $conn->real_escape_string($search);
    $sql .= " AND (md.tenMau LIKE '%$searchEscaped%' OR hd.tenHang LIKE '%$searchEscaped%' OR md.maMau IN (SELECT maMau FROM danserial WHERE soSerial LIKE '%$searchEscaped%'))";
}

$sql .= " GROUP BY md.maMau, k.maKho ORDER BY md.tenMau ASC";
$result = $conn->query($sql);

$inventory_items = [];
$total_stock_quantity = 0;
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $inventory_items[] = $row;
        $total_stock_quantity += $row['soLuongTon'];
    }
}
?>

<?php include 'includes/header.php'; ?>
<?php include 'includes/sidebar.php'; ?>


<div class="main-wrapper">
    <?php include 'includes/topbar.php'; ?>

    <div class="content">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;">
            <h2 style="margin: 0; display: flex; align-items: center; gap: 12px;">
                <span class="material-symbols-rounded" style="color: var(--accent); font-size: 32px;">inventory</span>
                Tồn Kho Hiện Tại
            </h2>
            <div style="background: rgba(124, 92, 252, 0.1); padding: 8px 16px; border-radius: var(--radius-full); border: 1px solid rgba(124, 92, 252, 0.2); display: flex; align-items: center; gap: 8px;">
                <span style="color: var(--text-secondary); font-size: 14px; font-weight: 600;">Tổng số lượng:</span>
                <span style="color: var(--accent); font-size: 18px; font-weight: 800;"><?= $total_stock_quantity ?></span>
            </div>
        </div>

        <form method="GET" class="filter-card">
            <div class="filter-group">
                <label>Chọn kho cần kiểm tra</label>
                <select name="ma_kho" class="filter-input">
                    <option value="0">-- Tất cả kho --</option>
                    <?php while($k = $khos->fetch_assoc()): ?>
                        <option value="<?= $k['maKho'] ?>" <?= $selected_kho == $k['maKho'] ? 'selected' : '' ?>><?= $k['tenKho'] ?></option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div class="filter-group">
                <label>Tìm kiếm sản phẩm</label>
                <input type="text" name="search" class="filter-input" placeholder="Nhập tên mẫu đàn, hãng sản xuất..." value="<?= htmlspecialchars($search) ?>">
            </div>
            <button type="submit" class="btn-filter"><span class="material-symbols-rounded">search</span> Hiển thị</button>
            <?php if ($selected_kho > 0 || !empty($search)): ?>
                <a href="tonkho_hientai.php" style="color: var(--danger); text-decoration: none; padding: 14px; font-weight: 600;">Xóa bộ lọc</a>
            <?php endif; ?>
        </form>

        <?php if(!empty($search)): ?>
            <?php
            $search_param = "%" . $search . "%";
            $sql_serial = "SELECT ds.soSerial, md.tenMau, hd.tenHang, k.tenKho, ds.trangThai, ds.tinhTrang
                           FROM danserial ds
                           JOIN MauDan md ON ds.maMau = md.maMau
                           JOIN HangDan hd ON md.maHang = hd.maHang
                           LEFT JOIN kho k ON ds.maKho = k.maKho
                           WHERE ds.soSerial LIKE ?";
            // Nếu có chọn kho, thì chỉ tìm serial trong kho đó (hoặc đã từng ở kho đó, hiện tại `maKho` trong bảng danserial phản ánh vị trí hiện tại)
            if ($selected_kho > 0) {
                $sql_serial .= " AND k.maKho = $selected_kho";
            }
            $stmt_serial = $conn->prepare($sql_serial);
            $stmt_serial->bind_param("s", $search_param);
            $stmt_serial->execute();
            $res_serial = $stmt_serial->get_result();
            if($res_serial->num_rows > 0):
            ?>
            <h3 style="margin-bottom: 16px; display: flex; align-items: center; gap: 8px;">
                <span class="material-symbols-rounded" style="color: var(--accent);">qr_code_2</span>
                Kết quả chi tiết Mã Đàn (Serial)
            </h3>
            <div style="background: var(--bg-card); border-radius: var(--radius-xl); border: 1px solid var(--glass-border); overflow: hidden; margin-bottom: 32px;">
                <table style="width: 100%; border-collapse: collapse; text-align: left;">
                    <thead>
                        <tr style="background: rgba(0,0,0,0.2); border-bottom: 1px solid var(--glass-border);">
                            <th style="padding: 16px; color: var(--text-secondary); font-weight: 600;">Mã Serial</th>
                            <th style="padding: 16px; color: var(--text-secondary); font-weight: 600;">Sản phẩm</th>
                            <th style="padding: 16px; color: var(--text-secondary); font-weight: 600;">Hãng</th>
                            <th style="padding: 16px; color: var(--text-secondary); font-weight: 600;">Vị trí (Kho)</th>
                            <th style="padding: 16px; color: var(--text-secondary); font-weight: 600;">Tình trạng</th>
                            <th style="padding: 16px; color: var(--text-secondary); font-weight: 600;">Trạng thái</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($s_row = $res_serial->fetch_assoc()): ?>
                        <tr style="border-bottom: 1px solid var(--glass-border); transition: 0.2s;" onmouseover="this.style.background='rgba(124, 92, 252, 0.05)'" onmouseout="this.style.background='transparent'">
                            <td style="padding: 16px; font-weight: 700; color: var(--accent);"><?php echo htmlspecialchars($s_row['soSerial']); ?></td>
                            <td style="padding: 16px; color: var(--text-primary);"><?php echo htmlspecialchars($s_row['tenMau']); ?></td>
                            <td style="padding: 16px; color: var(--text-primary);"><?php echo htmlspecialchars($s_row['tenHang']); ?></td>
                            <td style="padding: 16px; color: var(--text-primary);"><?php echo htmlspecialchars($s_row['tenKho'] ?? 'Không có'); ?></td>
                            <td style="padding: 16px; color: var(--text-primary);"><?php echo htmlspecialchars($s_row['tinhTrang']); ?></td>
                            <td style="padding: 16px;">
                                <?php 
                                $statusColor = 'var(--text-secondary)';
                                $st = $s_row['trangThai'];
                                if($st == 'Trong kho') $statusColor = 'var(--success)';
                                elseif($st == 'Đã bán') $statusColor = 'var(--accent)';
                                elseif($st == 'Đã xuất hãng' || $st == 'Đã hủy') $statusColor = 'var(--danger)';
                                elseif($st == 'Đang bảo hành') $statusColor = '#f59e0b';
                                ?>
                                <span style="color: <?php echo $statusColor; ?>; font-weight: 600; padding: 4px 12px; border-radius: 20px; background: rgba(255,255,255,0.05); font-size: 13px;">
                                    <?php echo htmlspecialchars($st); ?>
                                </span>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
        <?php endif; ?>

        <?php if(!empty($inventory_items)): ?>
            <div class="inventory-grid">
                <?php foreach($inventory_items as $row): ?>
                    <div class="inventory-card">
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
                        </div>
                        <div class="inv-qty">
                            <div class="inv-qty-number"><?= $row['soLuongTon'] ?></div>
                            <div class="inv-warehouse"><span class="material-symbols-rounded" style="font-size: 14px; vertical-align: text-bottom; margin-right: 2px;">warehouse</span> <?= htmlspecialchars($row['tenKho']) ?></div>
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
