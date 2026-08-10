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
$sql_hd = "SELECT hd.maHoaDon, hd.ngayLap, hd.tongTien, kh.hoTen as tenKH 
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
?>
<?php include 'includes/header.php'; ?>
<?php include 'includes/sidebar.php'; ?>

<style>
    .action-container {
        max-width: 600px;
        margin: 50px auto;
        background: var(--bg-card);
        padding: 40px;
        border-radius: var(--radius-xl);
        text-align: center;
        border: 1px solid var(--glass-border);
        box-shadow: 0 10px 30px rgba(0,0,0,0.2);
    }
    .action-icon {
        font-size: 64px;
        color: var(--accent);
        margin-bottom: 20px;
    }
    .action-title {
        font-size: 24px;
        color: var(--text-primary);
        margin-bottom: 10px;
        font-weight: 700;
    }
    .action-desc {
        color: var(--text-secondary);
        margin-bottom: 30px;
        font-size: 15px;
    }
    .action-buttons {
        display: flex;
        gap: 20px;
        justify-content: center;
    }
    .btn-large {
        padding: 15px 30px;
        font-size: 16px;
        font-weight: 600;
        border-radius: var(--radius-md);
        display: inline-flex;
        align-items: center;
        gap: 10px;
        text-decoration: none;
        transition: 0.3s;
    }
    .btn-print {
        background: var(--accent);
        color: white;
        border: none;
    }
    .btn-print:hover {
        background: var(--accent-secondary);
        transform: translateY(-2px);
    }
    .btn-export {
        background: var(--success-bg);
        color: var(--success);
        border: 1px solid rgba(16, 185, 129, 0.3);
    }
    .btn-export:hover {
        background: rgba(16, 185, 129, 0.2);
        transform: translateY(-2px);
    }
</style>

<div class="main-wrapper">
    <?php include 'includes/topbar.php'; ?>

    <div class="content">
        <div class="action-container">
            <span class="material-symbols-rounded action-icon">receipt_long</span>
            <div class="action-title">Xử Lý Hóa Đơn #<?php echo $id; ?></div>
            <div class="action-desc">
                Khách hàng: <strong><?php echo htmlspecialchars($hd['tenKH'] ?? 'Khách Lẻ'); ?></strong><br>
                Tổng tiền: <strong><?php echo number_format($hd['tongTien'], 0, ',', '.'); ?> đ</strong><br>
                Ngày lập: <?php echo date('d/m/Y H:i', strtotime($hd['ngayLap'])); ?>
            </div>
            
            <div class="action-buttons">
                <a href="xuat_pdf.php?type=hoadon&id=<?php echo $id; ?>" class="btn-large btn-print">
                    <span class="material-symbols-rounded">print</span> In Hóa Đơn
                </a>
                <a href="phieuxuat.php?maHoaDon=<?php echo $id; ?>" class="btn-large btn-export">
                    <span class="material-symbols-rounded">local_shipping</span> Lập Phiếu Xuất
                </a>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
