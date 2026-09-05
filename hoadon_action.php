<?php
// Kiểm tra session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'db.php';

// Kiểm tra quyền
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$id = intval($_GET['id'] ?? 0);
if (!$id) {
    die("Hóa đơn không hợp lệ.");
}

// Lấy thông tin hóa đơn
$sql_hd = "SELECT hd.maHoaDon, hd.ngayLap, hd.tongTien, hd.trangThai, kh.hoTen as tenKH 
           FROM hoadon hd 
           LEFT JOIN khachhang kh ON hd.maKhachHang = kh.maKhachHang 
           WHERE hd.maHoaDon = ?";
$stmt = $conn->prepare($sql_hd);
$stmt->bind_param("i", $id);
$stmt->execute();
$res = $stmt->get_result();
if ($res->num_rows == 0) {
    die("Không tìm thấy Hóa đơn!");
}
$hd = $res->fetch_assoc();

// Lấy chi tiết hóa đơn
$sql_ct = "SELECT ct.*, ds.soSerial, md.tenMau, md.baoHanh, hd_hang.tenHang 
           FROM chitiethoadon ct 
           JOIN danserial ds ON ct.maSerial = ds.maSerial 
           JOIN maudan md ON ds.maMau = md.maMau 
           LEFT JOIN hangdan hd_hang ON md.maHang = hd_hang.maHang 
           WHERE ct.maHoaDon = ?";
$stmt_ct = $conn->prepare($sql_ct);
$stmt_ct->bind_param("i", $id);
$stmt_ct->execute();
$res_ct = $stmt_ct->get_result();
$details = [];
while($row = $res_ct->fetch_assoc()){ $details[] = $row; }
?>
<?php include 'includes/header.php'; ?>
<?php include 'includes/sidebar.php'; ?>



<div class="main-wrapper">
    <?php include 'includes/topbar.php'; ?>

    <div class="content">
        <div class="action-container">
            <span class="material-symbols-rounded action-icon">receipt_long</span>
            <div class="action-title">Xử Lý Hóa Đơn #<?php echo $id; ?></div>
            <div class="action-desc">
                Khách hàng: <strong><?php echo htmlspecialchars($hd['tenKH'] ?? 'Khách Lẻ'); ?></strong><br>
                Tổng tiền: <strong style="color: var(--success); font-size: 18px;"><?php echo number_format($hd['tongTien'], 0, ',', '.'); ?> đ</strong><br>
                Ngày lập: <?php echo date('d/m/Y H:i', strtotime($hd['ngayLap'])); ?>
            </div>
            
            <div class="invoice-details">
                <h3>Chi tiết sản phẩm</h3>
                <table>
                    <thead>
                        <tr>
                            <th>STT</th>
                            <th>Tên sản phẩm</th>
                            <th>Serial</th>
                            <th style="text-align: right;">Đơn giá</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $stt = 1;
                        foreach($details as $item): 
                        ?>
                        <tr>
                            <td><?= $stt++ ?></td>
                            <td>
                                <strong><?= htmlspecialchars($item['tenMau'] . ' - ' . ($item['tenHang'] ?? '')) ?></strong><br>
                                <span class="item-warranty">Bảo hành: <?= htmlspecialchars($item['baoHanh'] ?? 'Không') ?></span>
                            </td>
                            <td><span style="font-family: monospace; background: rgba(0,0,0,0.2); padding: 3px 6px; border-radius: 4px;"><?= htmlspecialchars($item['soSerial']) ?></span></td>
                            <td style="text-align: right; font-weight: 600;"><?= number_format($item['donGia'] ?? 0, 0, ',', '.') ?> đ</td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <div class="action-buttons">
                <a href="xuat_pdf.php?type=hoadon&id=<?php echo $id; ?>" class="btn-large btn-print">
                    <span class="material-symbols-rounded">print</span> In Hóa Đơn
                </a>
                <?php if ($hd['trangThai'] == 'Chờ giao'): ?>
                    <a href="phieuxuat.php?maHoaDon=<?php echo $id; ?>" class="btn-large btn-export">
                        <span class="material-symbols-rounded">local_shipping</span> Lập Phiếu Xuất
                    </a>
                <?php else: ?>
                    <button class="btn-large btn-export" style="background: rgba(255,255,255,0.1); color: var(--text-muted); border: 1px solid var(--border); cursor: not-allowed;" title="Hóa đơn này đã được xử lý" disabled>
                        <span class="material-symbols-rounded">check_circle</span> Đã xử lý (<?= htmlspecialchars($hd['trangThai']) ?>)
                    </button>
                <?php endif; ?>
                
                <?php if (in_array($_SESSION['role_id'], [1, 2]) && $hd['trangThai'] !== 'Đã hủy'): ?>
                    <a href="huy_hoadon.php?id=<?php echo $id; ?>" class="btn-large" style="background: rgba(239, 68, 68, 0.1); color: #ef4444; border: 1px solid rgba(239,68,68,0.3);" onclick="return confirm('Bạn có CHẮC CHẮN muốn hủy hóa đơn này? Mọi phiếu xuất liên quan (nếu có) cũng sẽ bị hủy và hàng hóa được trả về Kho!');">
                        <span class="material-symbols-rounded">cancel</span> Hủy Hóa Đơn
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
