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

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($id <= 0) {
    header("Location: tracuu.php");
    exit();
}

// Lấy thông tin đàn
$sql = "SELECT md.*, hd.tenHang, ld.tenLoai 
        FROM MauDan md
        JOIN HangDan hd ON md.maHang = hd.maHang
        JOIN LoaiDan ld ON md.maLoai = ld.maLoai
        WHERE md.maMau = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);
$stmt->execute();
$product = $stmt->get_result()->fetch_assoc();

if (!$product) {
    header("Location: tracuu.php");
    exit();
}

$title = "Chi tiết: " . htmlspecialchars($product['tenMau']);

// Tính toán giá bán và số lượng tồn
$sql_price = "SELECT MIN(giaBan) as minPrice, MAX(giaBan) as maxPrice, COUNT(*) as totalQty 
              FROM DanSerial 
              WHERE maMau = ? AND trangThai = 'Trong kho'";
$stmt_price = $conn->prepare($sql_price);
$stmt_price->bind_param("i", $id);
$stmt_price->execute();
$price_data = $stmt_price->get_result()->fetch_assoc();

$minPrice = $price_data['minPrice'] ?? 0;
$maxPrice = $price_data['maxPrice'] ?? 0;
$totalQty = $price_data['totalQty'] ?? 0;

// Lấy danh sách tồn kho theo từng kho
$sql_stock = "SELECT k.tenKho, COUNT(ds.maSerial) as soLuong
              FROM kho k
              LEFT JOIN danserial ds ON k.maKho = ds.maKho AND ds.maMau = ? AND ds.trangThai = 'Trong kho'
              GROUP BY k.maKho, k.tenKho
              HAVING soLuong > 0";
$stmt_stock = $conn->prepare($sql_stock);
$stmt_stock->bind_param("i", $id);
$stmt_stock->execute();
$stock_result = $stmt_stock->get_result();
$stocks = [];
while ($row = $stock_result->fetch_assoc()) {
    $stocks[] = $row;
}
?>

<?php include 'includes/header.php'; ?>
<?php include 'includes/sidebar.php'; ?>


<div class="main-wrapper">
    <?php include 'includes/topbar.php'; ?>

    <div class="content">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;">
            <a href="tracuu.php" style="display: inline-flex; align-items: center; gap: 6px; color: var(--text-secondary); text-decoration: none; font-weight: 600; transition: 0.2s;" onmouseover="this.style.color='var(--accent)'" onmouseout="this.style.color='var(--text-secondary)'">
                <span class="material-symbols-rounded" style="font-size: 20px;">arrow_back</span>
                Quay lại Tra cứu
            </a>
            
            <?php if (isset($_SESSION['role_id']) && $_SESSION['role_id'] == 1): ?>
            <a href="sua_sanpham.php?id=<?= $id ?>" style="display: inline-flex; align-items: center; gap: 8px; background: rgba(255, 255, 255, 0.05); color: var(--text-primary); border: 1px solid var(--glass-border); padding: 10px 20px; border-radius: var(--radius-full); font-weight: 600; text-decoration: none; transition: 0.3s cubic-bezier(0.4, 0, 0.2, 1); backdrop-filter: blur(8px);" onmouseover="this.style.background='var(--accent)'; this.style.color='white'; this.style.borderColor='var(--accent)'; this.style.transform='translateY(-2px)'; this.style.boxShadow='0 6px 20px rgba(124, 92, 252, 0.4)';" onmouseout="this.style.background='rgba(255, 255, 255, 0.05)'; this.style.color='var(--text-primary)'; this.style.borderColor='var(--glass-border)'; this.style.transform='none'; this.style.boxShadow='none';">
                <span class="material-symbols-rounded" style="font-size: 20px;">edit_square</span>
                Chỉnh sửa sản phẩm
            </a>
            <?php endif; ?>
        </div>

        <div class="product-detail-container">
            <div class="pd-image-wrapper">
                <?php if($totalQty > 0): ?>
                    <div class="pd-badge">Còn hàng (<?= $totalQty ?>)</div>
                <?php else: ?>
                    <div class="pd-badge" style="background: var(--danger);">Hết hàng</div>
                <?php endif; ?>

                <?php if(!empty($product['hinhAnh'])): ?>
                    <img src="images/<?php echo htmlspecialchars($product['hinhAnh']); ?>" alt="<?php echo htmlspecialchars($product['tenMau']); ?>">
                <?php else: ?>
                    <span class="material-symbols-rounded" style="font-size: 120px; color: rgba(255,255,255,0.1);">piano</span>
                <?php endif; ?>
            </div>

            <div class="pd-info">
                <div class="pd-brand"><?= htmlspecialchars($product['tenHang']) ?></div>
                <h1 class="pd-name"><?= htmlspecialchars($product['tenMau']) ?></h1>
                <div><span class="pd-category"><?= htmlspecialchars($product['tenLoai']) ?></span></div>
                
                <div class="pd-price-section">
                    <div class="pd-price-label">Mức giá tham khảo</div>
                    <div class="pd-price">
                        <?php if ($totalQty > 0): ?>
                            <?php if ($minPrice == $maxPrice): ?>
                                <?= number_format($minPrice) ?>đ
                            <?php else: ?>
                                <?= number_format($minPrice) ?>đ - <?= number_format($maxPrice) ?>đ
                            <?php endif; ?>
                        <?php else: ?>
                            <span style="color: var(--text-muted); font-size: 20px;">Liên hệ</span>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="pd-specs-title">
                    <span class="material-symbols-rounded" style="color: var(--accent);">info</span>
                    Thông số kỹ thuật
                </div>
                <table class="pd-specs-table">
                    <tr>
                        <td>Thương hiệu</td>
                        <td><?= htmlspecialchars($product['tenHang']) ?></td>
                    </tr>
                    <tr>
                        <td>Loại đàn</td>
                        <td><?= htmlspecialchars($product['tenLoai']) ?></td>
                    </tr>
                    <tr>
                        <td>Xuất xứ</td>
                        <td><?= !empty($product['xuatXu']) ? htmlspecialchars($product['xuatXu']) : 'Đang cập nhật' ?></td>
                    </tr>
                    <tr>
                        <td>Chất liệu</td>
                        <td><?= !empty($product['chatLieu']) ? htmlspecialchars($product['chatLieu']) : 'Đang cập nhật' ?></td>
                    </tr>
                    <tr>
                        <td>Màu sắc</td>
                        <td><?= !empty($product['mauSac']) ? htmlspecialchars($product['mauSac']) : 'Đang cập nhật' ?></td>
                    </tr>
                    <tr>
                        <td>Kích thước</td>
                        <td><?= !empty($product['kichThuoc']) ? htmlspecialchars($product['kichThuoc']) : 'Đang cập nhật' ?></td>
                    </tr>
                    <tr>
                        <td>Trọng lượng</td>
                        <td><?= !empty($product['trongLuong']) ? htmlspecialchars($product['trongLuong']) : 'Đang cập nhật' ?></td>
                    </tr>
                    <tr>
                        <td>Bảo hành</td>
                        <td><?= !empty($product['baoHanh']) ? htmlspecialchars($product['baoHanh']) : '5 năm' ?></td>
                    </tr>
                </table>

                <div class="pd-specs-title">
                    <span class="material-symbols-rounded" style="color: var(--accent);">warehouse</span>
                    Tình trạng kho
                </div>
                
                <?php if (count($stocks) > 0): ?>
                    <div class="pd-stock-list">
                        <?php foreach($stocks as $stock): ?>
                            <div class="pd-stock-item">
                                <div class="pd-stock-name">
                                    <span class="material-symbols-rounded" style="font-size: 20px; color: var(--text-muted);">storefront</span>
                                    <?= htmlspecialchars($stock['tenKho']) ?>
                                </div>
                                <div class="pd-stock-qty"><?= $stock['soLuong'] ?> chiếc</div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div style="padding: 20px; background: rgba(0,0,0,0.1); border-radius: var(--radius-md); text-align: center; color: var(--text-secondary);">
                        Mặt hàng này hiện đang tạm hết trong tất cả các kho.
                    </div>
                <?php endif; ?>

            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
