<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'db.php';
require_once 'functions.php';

// Chỉ Admin mới được truy cập
if (!isset($_SESSION['user_id']) || $_SESSION['role_id'] != 1) {
    header("Location: index.php");
    exit();
}

$msg = "";
if (isset($_SESSION['flash_success'])) {
    $msg = "<div class='alert alert-success' style='background: rgba(52, 211, 153, 0.1); color: #34d399; border: 1px solid rgba(52, 211, 153, 0.3); padding:15px; border-radius:12px; margin-bottom:24px; display: flex; align-items: center; gap: 10px;'><span class='material-symbols-rounded'>check_circle</span> " . $_SESSION['flash_success'] . "</div>";
    unset($_SESSION['flash_success']);
}
if (isset($_SESSION['flash_error'])) {
    $msg .= "<div class='alert alert-danger' style='background: rgba(248, 113, 113, 0.1); color: #f87171; border: 1px solid rgba(248, 113, 113, 0.3); padding:15px; border-radius:12px; margin-bottom:24px; display: flex; align-items: center; gap: 10px;'><span class='material-symbols-rounded'>error</span> " . $_SESSION['flash_error'] . "</div>";
    unset($_SESSION['flash_error']);
}

// Lấy danh sách Phiếu Nhập chờ duyệt
$sql_pn = "SELECT pn.*, ncc.tenNCC, nv.hoTen as tenNV FROM phieunhap pn LEFT JOIN nhacungcap ncc ON pn.maNCC = ncc.maNCC LEFT JOIN nhanvien nv ON pn.maNhanVien = nv.maNhanVien WHERE pn.trangThai = 'Chờ duyệt' ORDER BY pn.ngayNhap DESC";
$pn_list = $conn->query($sql_pn);

// Lấy danh sách Phiếu Xuất chờ duyệt
$sql_px = "SELECT px.*, k.tenKho, nv.hoTen as tenNV FROM phieuxuat px LEFT JOIN kho k ON px.maKho = k.maKho LEFT JOIN nhanvien nv ON px.maNhanVien = nv.maNhanVien WHERE px.trangThai = 'Chờ duyệt' ORDER BY px.ngayXuat DESC";
$px_list = $conn->query($sql_px);

// Lấy danh sách Phiếu Điều chuyển chờ duyệt
$sql_dc = "SELECT dc.*, k1.tenKho as khoXuat, k2.tenKho as khoNhap, nv.hoTen as tenNV FROM phieudieuchuyen dc LEFT JOIN kho k1 ON dc.maKhoXuat = k1.maKho LEFT JOIN kho k2 ON dc.maKhoNhap = k2.maKho LEFT JOIN nhanvien nv ON dc.maNhanVienLap = nv.maNhanVien WHERE dc.trangThai = 'Chờ duyệt' ORDER BY dc.ngayTao DESC";
$dc_list = $conn->query($sql_dc);

$title = 'Phê Duyệt Phiếu';
?>
<?php include 'includes/header.php'; ?>
<?php include 'includes/sidebar.php'; ?>
<style>
        .tabs { display: flex; gap: 16px; margin-bottom: 24px; border-bottom: 1px solid var(--glass-border); padding-bottom: 16px; }
        .tab-btn { background: transparent; color: var(--text-muted); border: none; font-size: 16px; font-weight: 600; cursor: pointer; padding: 12px 24px; border-radius: 8px; transition: 0.3s; }
        .tab-btn.active { background: rgba(59, 130, 246, 0.1); color: var(--accent); }
        .tab-btn:hover:not(.active) { background: rgba(255,255,255,0.05); color: var(--text-primary); }
        .tab-content { display: none; animation: fadeIn 0.3s ease; }
        .tab-content.active { display: block; }
        
        .phieu-card { background: var(--bg-card); border: 1px solid var(--glass-border); border-radius: 12px; padding: 24px; margin-bottom: 20px; transition: 0.3s; }
        .phieu-card:hover { border-color: rgba(255,255,255,0.1); box-shadow: 0 8px 24px rgba(0,0,0,0.2); }
        .phieu-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px; padding-bottom: 16px; border-bottom: 1px dashed var(--glass-border); }
        .phieu-title { font-size: 18px; font-weight: 700; color: var(--text-primary); display: flex; align-items: center; gap: 8px; }
        .phieu-details { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px; margin-bottom: 24px; }
        .detail-item { font-size: 14px; }
        .detail-item span { color: var(--text-muted); display: block; font-size: 12px; text-transform: uppercase; margin-bottom: 4px; }
        
        .action-buttons { display: flex; gap: 12px; justify-content: flex-end; }
        .btn { padding: 10px 20px; border-radius: 8px; font-weight: 600; border: none; cursor: pointer; transition: 0.3s; display: inline-flex; align-items: center; gap: 8px; font-family: inherit; font-size: 14px; text-decoration: none;}
        .btn-approve { background: rgba(52, 211, 153, 0.15); color: #34d399; border: 1px solid rgba(52, 211, 153, 0.3); }
        .btn-approve:hover { background: rgba(52, 211, 153, 0.3); }
        .btn-reject { background: rgba(248, 113, 113, 0.15); color: #f87171; border: 1px solid rgba(248, 113, 113, 0.3); }
        .btn-reject:hover { background: rgba(248, 113, 113, 0.3); }
        .btn-view { background: rgba(255,255,255,0.05); color: var(--text-primary); border: 1px solid var(--glass-border); }
        .btn-view:hover { background: rgba(255,255,255,0.1); }
        
        @keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
</style>

<div class="main-wrapper">
    <?php include 'includes/topbar.php'; ?>
    
    <div class="content">
                <div class="welcome-banner" style="margin-bottom: 24px;">
                    <h1 style="margin: 0 0 8px 0; font-size: 24px;">Phê Duyệt Phiếu</h1>
                    <p style="margin: 0; color: var(--text-secondary);">Quản lý và phê duyệt các phiếu nhập, xuất kho đang chờ xử lý.</p>
                </div>
                
                <?php echo $msg; ?>

                <div class="tabs">
                    <button class="tab-btn active" onclick="switchTab('nhap')">
                        Phiếu Nhập Chờ Duyệt <span style="background: rgba(255,255,255,0.2); padding: 2px 8px; border-radius: 12px; font-size: 12px; margin-left: 8px;"><?= $pn_list->num_rows ?></span>
                    </button>
                    <button class="tab-btn" onclick="switchTab('xuat')">
                        Phiếu Xuất Chờ Duyệt <span style="background: rgba(255,255,255,0.2); padding: 2px 8px; border-radius: 12px; font-size: 12px; margin-left: 8px;"><?= $px_list->num_rows ?></span>
                    </button>
                    <button class="tab-btn" onclick="switchTab('dieuchuyen')">
                        Phiếu Điều Chuyển <span style="background: rgba(255,255,255,0.2); padding: 2px 8px; border-radius: 12px; font-size: 12px; margin-left: 8px;"><?= $dc_list->num_rows ?></span>
                    </button>
                </div>

                <!-- TAB PHIẾU NHẬP -->
                <div id="tab-nhap" class="tab-content active">
                    <?php if($pn_list->num_rows > 0): ?>
                        <?php while($pn = $pn_list->fetch_assoc()): ?>
                            <div class="phieu-card">
                                <div class="phieu-header">
                                    <div class="phieu-title">
                                        <span class="material-symbols-rounded" style="color: var(--accent);">input</span>
                                        Phiếu Nhập Kho #<?= $pn['maPhieuNhap'] ?>
                                    </div>
                                    <div style="color: var(--text-muted); font-size: 14px;">
                                        Ngày lập: <?= date('d/m/Y H:i', strtotime($pn['ngayNhap'])) ?>
                                    </div>
                                </div>
                                <div class="phieu-details">
                                    <div class="detail-item"><span>Người lập</span><?= htmlspecialchars($pn['tenNV']) ?></div>
                                    <div class="detail-item"><span>Nhà cung cấp</span><?= htmlspecialchars($pn['tenNCC'] ?? 'N/A') ?></div>
                                    <div class="detail-item"><span>Số lượng SP</span><?= $pn['soLuong'] ?> chiếc</div>
                                    <div class="detail-item"><span>Tổng tiền</span><?= number_format($pn['tongTienNhap'], 0, ',', '.') ?> đ</div>
                                </div>
                                <div class="action-buttons">
                                    <a href="xuat_pdf.php?type=phieunhap&id=<?= $pn['maPhieuNhap'] ?>" target="_blank" class="btn btn-view"><span class="material-symbols-rounded">visibility</span> Xem chi tiết</a>
                                    <form method="POST" action="duyet_action.php" style="display:inline;">
                                        <input type="hidden" name="type" value="nhap">
                                        <input type="hidden" name="id" value="<?= $pn['maPhieuNhap'] ?>">
                                        <input type="hidden" name="action" value="reject">
                                        <button type="submit" class="btn btn-reject" onclick="return confirm('Bạn có chắc chắn muốn TỪ CHỐI phiếu này?');"><span class="material-symbols-rounded">cancel</span> Từ chối</button>
                                    </form>
                                    <form method="POST" action="duyet_action.php" style="display:inline;">
                                        <input type="hidden" name="type" value="nhap">
                                        <input type="hidden" name="id" value="<?= $pn['maPhieuNhap'] ?>">
                                        <input type="hidden" name="action" value="approve">
                                        <button type="submit" class="btn btn-approve" onclick="return confirm('Bạn có chắc chắn muốn DUYỆT phiếu này? Số lượng tồn kho sẽ được cập nhật.');"><span class="material-symbols-rounded">check_circle</span> Phê duyệt</button>
                                    </form>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <div style="text-align: center; padding: 40px; color: var(--text-muted);">
                            <span class="material-symbols-rounded" style="font-size: 48px; opacity: 0.5; margin-bottom: 16px; display: block;">inventory_2</span>
                            Không có Phiếu Nhập nào đang chờ duyệt.
                        </div>
                    <?php endif; ?>
                </div>

                <!-- TAB PHIẾU XUẤT -->
                <div id="tab-xuat" class="tab-content">
                    <?php if($px_list->num_rows > 0): ?>
                        <?php while($px = $px_list->fetch_assoc()): ?>
                            <div class="phieu-card">
                                <div class="phieu-header">
                                    <div class="phieu-title">
                                        <span class="material-symbols-rounded" style="color: var(--sales-text);">local_shipping</span>
                                        Phiếu Xuất Kho #<?= $px['maPhieuXuat'] ?>
                                    </div>
                                    <div style="color: var(--text-muted); font-size: 14px;">
                                        Ngày lập: <?= date('d/m/Y H:i', strtotime($px['ngayXuat'])) ?>
                                    </div>
                                </div>
                                <div class="phieu-details">
                                    <div class="detail-item"><span>Người lập</span><?= htmlspecialchars($px['tenNV']) ?></div>
                                    <div class="detail-item"><span>Người nhận</span><?= htmlspecialchars($px['nguoiNhanHang'] ?? 'N/A') ?></div>
                                    <div class="detail-item"><span>Kho xuất</span><?= htmlspecialchars($px['tenKho']) ?></div>
                                    <div class="detail-item"><span>Lý do</span><?= htmlspecialchars($px['lyDoXuat']) ?></div>
                                </div>
                                <div class="action-buttons">
                                    <a href="xuat_pdf.php?type=phieuxuat&id=<?= $px['maPhieuXuat'] ?>" target="_blank" class="btn btn-view"><span class="material-symbols-rounded">visibility</span> Xem chi tiết</a>
                                    <form method="POST" action="duyet_action.php" style="display:inline;">
                                        <input type="hidden" name="type" value="xuat">
                                        <input type="hidden" name="id" value="<?= $px['maPhieuXuat'] ?>">
                                        <input type="hidden" name="action" value="reject">
                                        <button type="submit" class="btn btn-reject" onclick="return confirm('Bạn có chắc chắn muốn TỪ CHỐI phiếu xuất này?');"><span class="material-symbols-rounded">cancel</span> Từ chối</button>
                                    </form>
                                    <form method="POST" action="duyet_action.php" style="display:inline;">
                                        <input type="hidden" name="type" value="xuat">
                                        <input type="hidden" name="id" value="<?= $px['maPhieuXuat'] ?>">
                                        <input type="hidden" name="action" value="approve">
                                        <button type="submit" class="btn btn-approve" onclick="return confirm('Bạn có chắc chắn muốn DUYỆT phiếu xuất này? Hàng sẽ được trừ khỏi kho.');"><span class="material-symbols-rounded">check_circle</span> Phê duyệt</button>
                                    </form>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <div style="text-align: center; padding: 40px; color: var(--text-muted);">
                            <span class="material-symbols-rounded" style="font-size: 48px; opacity: 0.5; margin-bottom: 16px; display: block;">inventory</span>
                            Không có Phiếu Xuất nào đang chờ duyệt.
                        </div>
                    <?php endif; ?>
                </div>

                <!-- TAB PHIẾU ĐIỀU CHUYỂN -->
                <div id="tab-dieuchuyen" class="tab-content">
                    <?php if($dc_list->num_rows > 0): ?>
                        <?php while($dc = $dc_list->fetch_assoc()): ?>
                            <div class="phieu-card">
                                <div class="phieu-header">
                                    <div class="phieu-title">
                                        <span class="material-symbols-rounded" style="color: #f59e0b;">local_shipping</span>
                                        Phiếu Điều Chuyển #<?= $dc['maPhieuDC'] ?>
                                    </div>
                                    <div style="color: var(--text-muted); font-size: 14px;">
                                        Ngày lập: <?= date('d/m/Y H:i', strtotime($dc['ngayTao'])) ?>
                                    </div>
                                </div>
                                <div class="phieu-details">
                                    <div class="detail-item"><span>Người lập</span><?= htmlspecialchars($dc['tenNV']) ?></div>
                                    <div class="detail-item"><span>Kho xuất</span><strong style="color: #f87171;"><?= htmlspecialchars($dc['khoXuat']) ?></strong></div>
                                    <div class="detail-item"><span>Kho nhập</span><strong style="color: #34d399;"><?= htmlspecialchars($dc['khoNhap']) ?></strong></div>
                                    <div class="detail-item"><span>Ghi chú</span><?= htmlspecialchars($dc['ghiChu'] ?? 'N/A') ?></div>
                                </div>
                                <div class="action-buttons">
                                    <form method="POST" action="duyet_action.php" style="display:inline;">
                                        <input type="hidden" name="type" value="dieuchuyen">
                                        <input type="hidden" name="id" value="<?= $dc['maPhieuDC'] ?>">
                                        <input type="hidden" name="action" value="reject">
                                        <button type="submit" class="btn btn-reject" onclick="return confirm('Bạn có chắc chắn muốn TỪ CHỐI phiếu điều chuyển này?');"><span class="material-symbols-rounded">cancel</span> Từ chối</button>
                                    </form>
                                    <form method="POST" action="duyet_action.php" style="display:inline;">
                                        <input type="hidden" name="type" value="dieuchuyen">
                                        <input type="hidden" name="id" value="<?= $dc['maPhieuDC'] ?>">
                                        <input type="hidden" name="action" value="approve">
                                        <button type="submit" class="btn btn-approve" onclick="return confirm('Bạn có chắc chắn muốn DUYỆT phiếu điều chuyển này? Hàng sẽ được chuyển kho.');"><span class="material-symbols-rounded">check_circle</span> Phê duyệt</button>
                                    </form>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <div style="text-align: center; padding: 40px; color: var(--text-muted);">
                            <span class="material-symbols-rounded" style="font-size: 48px; opacity: 0.5; margin-bottom: 16px; display: block;">local_shipping</span>
                            Không có Phiếu Điều Chuyển nào đang chờ duyệt.
                        </div>
                    <?php endif; ?>
                </div>

    </div>
</div>

<script>
        function switchTab(tabId) {
            document.querySelectorAll('.tab-btn').forEach(btn => btn.classList.remove('active'));
            document.querySelectorAll('.tab-content').forEach(content => content.classList.remove('active'));
            
            event.currentTarget.classList.add('active');
            document.getElementById('tab-' + tabId).classList.add('active');
        }
</script>
<?php include 'includes/footer.php'; ?>
