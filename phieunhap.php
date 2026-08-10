<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'db.php';
require_once 'functions.php';

// Kiểm tra quyền (Chỉ Admin và Thủ kho)
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role_id'], [1, 2, 3])) {
    header("Location: index.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$msg = "";

// === API: Kiểm tra Serial trùng (AJAX) ===
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'check_serial') {
    header('Content-Type: application/json');
    $serial = trim($_POST['serial'] ?? '');
    if (empty($serial)) {
        echo json_encode(['exists' => false]);
        exit();
    }
    $st = $conn->prepare("SELECT ds.maSerial, md.tenMau FROM danserial ds JOIN maudan md ON ds.maMau = md.maMau WHERE ds.soSerial = ?");
    $st->bind_param("s", $serial);
    $st->execute();
    $res = $st->get_result();
    if ($res->num_rows > 0) {
        $row = $res->fetch_assoc();
        echo json_encode(['exists' => true, 'tenMau' => $row['tenMau']]);
    } else {
        echo json_encode(['exists' => false]);
    }
    exit();
}

// === API: Lấy thông tin giá gợi ý theo mẫu đàn ===
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'get_price_hint') {
    header('Content-Type: application/json');
    $maMau = intval($_POST['maMau'] ?? 0);
    // Lấy giá nhập gần nhất của mẫu đàn này
    $st = $conn->prepare("SELECT ct.giaNhap FROM chitietphieunhap ct 
                          JOIN danserial ds ON ct.maSerial = ds.maSerial 
                          WHERE ds.maMau = ? 
                          ORDER BY ct.maChiTiet DESC LIMIT 1");
    $st->bind_param("i", $maMau);
    $st->execute();
    $res = $st->get_result();
    if ($res->num_rows > 0) {
        $row = $res->fetch_assoc();
        echo json_encode(['found' => true, 'giaNhap' => $row['giaNhap']]);
    } else {
        echo json_encode(['found' => false]);
    }
    exit();
}

// === XỬ LÝ BACKEND: Lưu phiếu nhập ===
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['btnLuuPhieuNhap'])) {
    $maNCC = $_POST['maNCC'];
    $maKho = $_POST['maKho'];
    $ghiChu = trim($_POST['ghiChu'] ?? '');
    
    $nguoiGiaoHang = trim($_POST['nguoiGiaoHang'] ?? '');
    $soHoaDonNCC = trim($_POST['soHoaDonNCC'] ?? '');
    $ngayHoaDon = !empty($_POST['ngayHoaDon']) ? $_POST['ngayHoaDon'] : NULL;
    
    $list_mamau = $_POST['maMau']; 
    $list_serial = $_POST['soSerial'];
    $list_gianhap = $_POST['giaNhap'];

    $conn->begin_transaction();
    try {
        $soLuongNhap = 0;
        foreach ($list_serial as $s) {
            if (trim($s) !== '') $soLuongNhap++;
        }

        if ($soLuongNhap == 0) {
            throw new Exception("Phiếu nhập phải có ít nhất 1 sản phẩm hợp lệ!");
        }

        // 1. Tạo phiếu nhập mới
        $sql_phieu = "INSERT INTO phieunhap (maNCC, soHoaDonNCC, nguoiGiaoHang, ngayHoaDon, ghiChu, maNhanVien, maKho, ngayNhap, soLuong, trangThai, tongTienNhap) VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), ?, 'Chờ duyệt', 0)";
        $stmt = $conn->prepare($sql_phieu);
        $stmt->bind_param("issssiii", $maNCC, $soHoaDonNCC, $nguoiGiaoHang, $ngayHoaDon, $ghiChu, $user_id, $maKho, $soLuongNhap);
        $stmt->execute();
        $maPhieuNhap = $conn->insert_id;

        $tongTien = 0;
        $soLuongThucTe = 0;

        // 2. Xử lý từng sản phẩm nhập vào
        for ($i = 0; $i < count($list_serial); $i++) {
            $maMau = $list_mamau[$i]; 
            $serial = trim($list_serial[$i]);
            $giaNhap = str_replace(',', '', $list_gianhap[$i]); 
            
            if (empty($serial)) continue;

            // Kiểm tra Serial trùng
            $st_check = $conn->prepare("SELECT maSerial FROM danserial WHERE soSerial = ?");
            $st_check->bind_param("s", $serial);
            $st_check->execute();
            if ($st_check->get_result()->num_rows > 0) {
                throw new Exception("Mã Serial [$serial] đã tồn tại trong hệ thống!");
            }

            // Thêm serial mới vào bảng danserial
            $st_insert_dan = $conn->prepare("INSERT INTO danserial (soSerial, maMau, maKho, maNCC, tinhTrang, trangThai, giaNhap) VALUES (?, ?, ?, ?, 'Mới', 'Chờ nhập', ?)");
            $st_insert_dan->bind_param("siiid", $serial, $maMau, $maKho, $maNCC, $giaNhap);
            $st_insert_dan->execute();
            $maSerial = $conn->insert_id;

            // Thêm vào chi tiết phiếu
            $conn->query("INSERT INTO chitietphieunhap (maPhieuNhap, maSerial, giaNhap) VALUES ($maPhieuNhap, $maSerial, $giaNhap)");

            $tongTien += $giaNhap;
            $soLuongThucTe++;
        }

        // Cập nhật tổng tiền và số lượng thực tế
        $conn->query("UPDATE phieunhap SET tongTienNhap = $tongTien, soLuong = $soLuongThucTe WHERE maPhieuNhap = $maPhieuNhap");

        $conn->commit();
        
        // Ghi log
        writeLog($conn, 'NHẬP KHO', "Đã lập phiếu nhập kho #$maPhieuNhap ($soLuongThucTe sản phẩm, Tổng: " . number_format($tongTien, 0, ',', '.') . "đ)");

        // Thông báo cho người nhập
        $msg_user = "Lập phiếu nhập thành công (Chờ duyệt)! Phiếu #$maPhieuNhap - $soLuongThucTe sản phẩm";
        $conn->query("INSERT INTO ThongBao (maTaiKhoan, noiDung, link) VALUES ($user_id, '$msg_user', 'phieunhap.php')");

        // Thông báo cho Admin (role 1) nếu người lập không phải admin
        if ($_SESSION['role_id'] != 1) {
            $msg_admin = "Có phiếu nhập kho mới #$maPhieuNhap cần được phê duyệt ($soLuongThucTe SP)";
            $conn->query("INSERT INTO ThongBao (maVaiTro, noiDung, link) VALUES (1, '$msg_admin', 'phieunhap.php')");
        }

        $_SESSION['flash_success'] = "Nhập kho thành công! Phiếu #$maPhieuNhap đã nhập $soLuongThucTe sản phẩm vào kho.";
        header("Location: xuat_pdf.php?type=phieunhap&id=$maPhieuNhap");
        exit();

    } catch (Exception $e) {
        $conn->rollback();
        $msg = "<div class='alert-msg alert-error'><span class='material-symbols-rounded'>error</span><div><strong>Lỗi xử lý!</strong><br>" . $e->getMessage() . "</div></div>";
    }
}

// Lấy dữ liệu cho Form
$nhacungcaps = $conn->query("SELECT * FROM nhacungcap ORDER BY tenNCC ASC");
$khos = $conn->query("SELECT * FROM kho ORDER BY tenKho ASC");
$maudans = $conn->query("SELECT maMau, tenMau FROM maudan ORDER BY tenMau ASC");

// Đếm số phiếu nhập hôm nay
$today_count = 0;
$res_today = $conn->query("SELECT COUNT(*) as cnt FROM phieunhap WHERE DATE(ngayNhap) = CURDATE()");
if ($res_today && $r = $res_today->fetch_assoc()) $today_count = $r['cnt'];

// Đếm tổng serial trong kho
$total_stock = 0;
$res_stock = $conn->query("SELECT COUNT(*) as cnt FROM danserial WHERE trangThai = 'Trong kho'");
if ($res_stock && $r = $res_stock->fetch_assoc()) $total_stock = $r['cnt'];
?>

<?php include 'includes/header.php'; ?>
<?php include 'includes/sidebar.php'; ?>

<style>
    /* =============================================
       PHIẾU NHẬP KHO - PREMIUM DARK MODE
       Color Theme: Emerald Green (#34d399)
       ============================================= */

    .form-center-container { 
        max-width: 1100px; 
        margin: 0 auto; 
        animation: fadeInUp 0.5s ease; 
    }
    
    /* === WELCOME BANNER === */
    .welcome-banner-import { 
        background: linear-gradient(135deg, rgba(52, 211, 153, 0.15), rgba(16, 185, 129, 0.05)); 
        border: 1px solid rgba(52, 211, 153, 0.25);
        color: var(--text-primary); 
        padding: 32px 36px; 
        border-radius: var(--radius-xl); 
        margin-bottom: 24px; 
        position: relative;
        overflow: hidden;
    }
    
    .welcome-banner-import::before {
        content: ''; position: absolute; top: -50%; right: -20%; width: 50%; height: 200%;
        background: radial-gradient(circle, rgba(52, 211, 153, 0.08) 0%, transparent 70%); pointer-events: none;
    }

    .welcome-banner-import::after {
        content: ''; position: absolute; bottom: -60%; left: -10%; width: 40%; height: 200%;
        background: radial-gradient(circle, rgba(16, 185, 129, 0.06) 0%, transparent 70%); pointer-events: none;
    }

    .banner-content { display: flex; justify-content: space-between; align-items: center; position: relative; z-index: 1; }
    .banner-left h1 { margin: 0 0 8px 0; font-size: 1.6rem; color: var(--staff-text); font-weight: 800; }
    .banner-left p { margin: 0; opacity: 0.8; font-size: 14px; }

    .banner-stats { display: flex; gap: 20px; }
    .stat-pill { 
        display: flex; align-items: center; gap: 8px; 
        background: rgba(0,0,0,0.2); padding: 10px 18px; 
        border-radius: var(--radius-full); 
        border: 1px solid rgba(255,255,255,0.06);
        font-size: 13px; color: var(--text-secondary);
    }
    .stat-pill .material-symbols-rounded { font-size: 18px; color: var(--staff-text); }
    .stat-pill strong { color: var(--text-primary); font-weight: 700; }

    /* === STEP INDICATOR === */
    .step-indicator {
        display: flex; gap: 8px; margin-bottom: 24px; 
        background: var(--bg-card); border: 1px solid var(--glass-border);
        border-radius: var(--radius-lg); padding: 16px 20px;
    }
    .step-item {
        display: flex; align-items: center; gap: 8px; flex: 1;
        padding: 10px 16px; border-radius: var(--radius-md);
        font-size: 13px; font-weight: 600; color: var(--text-muted);
        transition: all 0.3s ease; cursor: default;
    }
    .step-item .step-num {
        width: 28px; height: 28px; border-radius: 50%;
        display: flex; align-items: center; justify-content: center;
        background: rgba(255,255,255,0.05); font-size: 12px; font-weight: 700;
        border: 1px solid var(--glass-border); transition: all 0.3s;
        flex-shrink: 0;
    }
    .step-item.active { color: var(--staff-text); background: rgba(52, 211, 153, 0.08); }
    .step-item.active .step-num { 
        background: var(--staff-text); color: #000; 
        border-color: var(--staff-text); 
        box-shadow: 0 0 12px rgba(52, 211, 153, 0.3); 
    }
    .step-item.completed { color: var(--text-secondary); }
    .step-item.completed .step-num { 
        background: rgba(52, 211, 153, 0.15); color: var(--staff-text); 
        border-color: rgba(52, 211, 153, 0.3); 
    }
    .step-divider { display: flex; align-items: center; color: var(--text-muted); font-size: 16px; padding: 0 4px; }

    /* === FORM CARD === */
    .form-card { 
        background: var(--bg-card); 
        backdrop-filter: blur(12px);
        padding: 32px; 
        border-radius: var(--radius-xl); 
        margin-bottom: 24px; 
        border: 1px solid var(--glass-border); 
        transition: 0.3s;
    }
    .form-card:hover { border-color: rgba(52, 211, 153, 0.2); box-shadow: 0 4px 24px rgba(0,0,0,0.15); }
    
    .card-title { 
        font-size: 1.1rem; font-weight: 700; color: var(--text-primary); 
        margin-bottom: 24px; padding-bottom: 14px; 
        border-bottom: 1px dashed var(--glass-border); 
        display: flex; align-items: center; gap: 10px;
    }
    .card-title .material-symbols-rounded { color: var(--staff-text); font-size: 22px; }
    .card-subtitle { font-size: 12px; color: var(--text-muted); font-weight: 400; margin-left: auto; }
    
    /* === INPUT STYLES === */
    .input-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 24px; }
    .form-group { position: relative; }
    .form-group label { 
        display: flex; align-items: center; gap: 6px;
        font-weight: 600; color: var(--text-secondary); 
        margin-bottom: 10px; font-size: 12px; 
        text-transform: uppercase; letter-spacing: 0.8px; 
    }
    .form-group label .material-symbols-rounded { font-size: 16px; color: var(--staff-text); }
    .label-required { color: var(--danger); font-weight: 700; }
    
    .custom-input { 
        width: 100%; padding: 14px 16px; 
        background: rgba(0, 0, 0, 0.2);
        border: 1px solid var(--glass-border); 
        border-radius: var(--radius-md); 
        outline: none; transition: 0.3s; 
        color: var(--text-primary);
        font-family: inherit; font-size: 14px;
    }
    .custom-input:focus { 
        border-color: var(--staff-text); 
        background: rgba(52, 211, 153, 0.05); 
        box-shadow: 0 0 0 3px rgba(52, 211, 153, 0.12); 
    }
    .custom-input option { background: var(--bg-secondary); color: var(--text-primary); }
    .custom-input::placeholder { color: var(--text-muted); }

    textarea.custom-input { resize: vertical; min-height: 80px; }

    .input-hint { font-size: 11px; color: var(--text-muted); margin-top: 6px; display: flex; align-items: center; gap: 4px; }
    .input-hint .material-symbols-rounded { font-size: 14px; }
    
    /* === PRODUCT ROWS === */
    .product-row { 
        display: flex; gap: 12px; margin-bottom: 12px; align-items: flex-end; 
        background: rgba(255,255,255,0.02); padding: 20px; 
        border-radius: var(--radius-md); border: 1px solid var(--glass-border); 
        transition: all 0.3s ease; position: relative;
    }
    .product-row:hover { border-color: rgba(52, 211, 153, 0.15); background: rgba(255,255,255,0.035); }
    .product-row > div { flex: 1; }
    .product-row .col-serial { flex: 1.3; }
    .product-row .col-price { max-width: 200px; }
    
    .row-number {
        position: absolute; top: -10px; left: -10px;
        width: 24px; height: 24px; border-radius: 50%;
        background: var(--staff-text); color: #000;
        display: flex; align-items: center; justify-content: center;
        font-size: 11px; font-weight: 800;
        box-shadow: 0 2px 8px rgba(52, 211, 153, 0.3);
    }

    .serial-status {
        font-size: 11px; margin-top: 5px; 
        display: flex; align-items: center; gap: 4px;
        min-height: 18px;
    }
    .serial-status.ok { color: var(--success); }
    .serial-status.error { color: var(--danger); }
    .serial-status .material-symbols-rounded { font-size: 14px; }

    .product-row-label { 
        font-weight: 600; font-size: 0.75rem; text-transform: uppercase; 
        margin-bottom: 8px; display: block; color: var(--text-secondary); 
        letter-spacing: 0.5px;
    }

    /* === SUMMARY BOX === */
    .summary-box {
        background: linear-gradient(135deg, rgba(52, 211, 153, 0.08), rgba(16, 185, 129, 0.04));
        border: 1px solid rgba(52, 211, 153, 0.2);
        border-radius: var(--radius-lg); padding: 24px; margin-top: 24px;
    }
    .summary-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; }
    .summary-item { text-align: center; }
    .summary-item .label { font-size: 11px; color: var(--text-muted); text-transform: uppercase; letter-spacing: 1px; font-weight: 600; margin-bottom: 6px; }
    .summary-item .value { font-size: 24px; font-weight: 800; color: var(--text-primary); }
    .summary-item .value.highlight { color: var(--staff-text); }
    
    /* === BUTTONS === */
    .btn-action { 
        padding: 14px 24px; border-radius: var(--radius-md); 
        font-weight: 600; cursor: pointer; border: none; 
        transition: all 0.3s ease; display: inline-flex; 
        align-items: center; gap: 8px; font-family: inherit;
    }
    .btn-submit { 
        background: linear-gradient(135deg, #34d399, #10b981); 
        color: #000; font-size: 1rem; 
        box-shadow: 0 4px 16px rgba(52, 211, 153, 0.3);
        padding: 16px 36px; font-weight: 700;
    }
    .btn-submit:hover { 
        transform: translateY(-2px); 
        box-shadow: 0 8px 28px rgba(52, 211, 153, 0.4); 
    }
    .btn-submit:active { transform: translateY(0); }
    
    .btn-add { 
        background: rgba(52, 211, 153, 0.06); color: var(--staff-text); 
        border: 1px dashed rgba(52, 211, 153, 0.3); 
    }
    .btn-add:hover { 
        border-color: var(--staff-text); 
        background: rgba(52, 211, 153, 0.1);
        transform: translateY(-1px); 
    }
    
    .btn-remove { 
        background: var(--danger-bg); color: var(--danger); 
        padding: 14px; min-width: 48px; height: 48px; 
        border: 1px solid rgba(248,113,113,0.15);
        justify-content: center;
    }
    .btn-remove:hover { 
        background: rgba(248, 113, 113, 0.2); 
        border-color: rgba(248, 113, 113, 0.3); 
    }

    .btn-reset {
        background: rgba(255,255,255,0.04); color: var(--text-secondary);
        border: 1px solid var(--glass-border); padding: 16px 28px;
    }
    .btn-reset:hover { background: rgba(255,255,255,0.08); color: var(--text-primary); }
    
    /* === ALERT MESSAGES === */
    .alert-msg {
        padding: 16px 20px; border-radius: var(--radius-lg); 
        margin-bottom: 24px; display: flex; align-items: center; gap: 12px;
        animation: fadeInUp 0.4s ease;
    }
    .alert-msg .material-symbols-rounded { font-size: 24px; flex-shrink: 0; }
    .alert-error { 
        background: var(--danger-bg); color: var(--danger); 
        border: 1px solid rgba(248,113,113,0.2); 
    }
    .alert-success { 
        background: var(--success-bg); color: var(--success); 
        border: 1px solid rgba(52,211,153,0.2); 
    }

    /* === ACTION BAR === */
    .action-bar {
        display: flex; justify-content: space-between; align-items: center;
        padding: 20px 0 40px 0;
    }
    .action-bar-left { display: flex; gap: 12px; align-items: center; }
    .action-bar-right { display: flex; gap: 12px; align-items: center; }

    .keyboard-hint {
        display: flex; align-items: center; gap: 6px;
        font-size: 12px; color: var(--text-muted);
    }
    .kbd { 
        display: inline-flex; align-items: center; justify-content: center;
        padding: 3px 8px; background: rgba(255,255,255,0.06); 
        border: 1px solid rgba(255,255,255,0.1);
        border-radius: 6px; font-size: 11px; font-weight: 600;
        color: var(--text-secondary); font-family: inherit;
        min-width: 22px;
    }

    /* === ANIMATIONS === */
    @keyframes rowSlideIn {
        from { opacity: 0; transform: translateY(12px) scale(0.98); }
        to { opacity: 1; transform: translateY(0) scale(1); }
    }
    @keyframes rowSlideOut {
        from { opacity: 1; transform: translateY(0) scale(1); }
        to { opacity: 0; transform: translateX(20px) scale(0.95); }
    }
    @keyframes pulseGreen {
        0%, 100% { box-shadow: 0 0 0 0 rgba(52, 211, 153, 0); }
        50% { box-shadow: 0 0 0 4px rgba(52, 211, 153, 0.15); }
    }

    /* === RESPONSIVE === */
    @media (max-width: 768px) {
        .banner-content { flex-direction: column; gap: 16px; }
        .banner-stats { flex-wrap: wrap; }
        .product-row { flex-direction: column; align-items: stretch; gap: 12px; }
        .product-row .col-price { max-width: 100%; }
        .summary-grid { grid-template-columns: 1fr; }
        .step-indicator { flex-direction: column; }
        .step-divider { display: none; }
        .action-bar { flex-direction: column; gap: 16px; }
        .keyboard-hint { display: none; }
    }
</style>

<div class="main-wrapper">
    <?php include 'includes/topbar.php'; ?>

    <div class="content">
        <div class="form-center-container">
            
            <!-- WELCOME BANNER -->
            <div class="welcome-banner-import">
                <div class="banner-content">
                    <div class="banner-left">
                        <h1>📦 Lập Phiếu Nhập Kho</h1>
                        <p>Nhân viên thực hiện: <b style="color: white;"><?php echo htmlspecialchars($_SESSION['fullname'] ?? 'Admin'); ?></b> · <?php echo date('d/m/Y H:i'); ?></p>
                    </div>
                    <div class="banner-stats">
                        <div class="stat-pill">
                            <span class="material-symbols-rounded">today</span>
                            Hôm nay: <strong><?php echo $today_count; ?> phiếu</strong>
                        </div>
                        <div class="stat-pill">
                            <span class="material-symbols-rounded">inventory_2</span>
                            Tồn kho: <strong><?php echo number_format($total_stock, 0, ',', '.'); ?></strong>
                        </div>
                    </div>
                </div>
            </div>

            <?php echo $msg; ?>

            <!-- STEP INDICATOR -->
            <div class="step-indicator">
                <div class="step-item active" id="step-1">
                    <span class="step-num">1</span> Thông tin chứng từ
                </div>
                <div class="step-divider">
                    <span class="material-symbols-rounded" style="font-size: 18px;">chevron_right</span>
                </div>
                <div class="step-item" id="step-2">
                    <span class="step-num">2</span> Chi tiết sản phẩm
                </div>
                <div class="step-divider">
                    <span class="material-symbols-rounded" style="font-size: 18px;">chevron_right</span>
                </div>
                <div class="step-item" id="step-3">
                    <span class="step-num">3</span> Xác nhận nhập kho
                </div>
            </div>

            <form method="POST" id="formPhieuNhap" onsubmit="return validateAndSubmit();">
                
                <!-- CARD 1: Thông tin chứng từ -->
                <div class="form-card" id="card-info">
                    <div class="card-title">
                        <span class="material-symbols-rounded">description</span> 
                        Thông tin chứng từ nhập
                        <span class="card-subtitle">Điền đầy đủ thông tin bắt buộc (*)</span>
                    </div>
                    <div class="input-grid">
                        <div class="form-group">
                            <label>
                                <span class="material-symbols-rounded">business</span>
                                Nhà cung cấp <span class="label-required">*</span>
                            </label>
                            <select name="maNCC" id="maNCC" class="custom-input" required onchange="updateSteps()">
                                <option value="">-- Chọn nhà cung cấp --</option>
                                <?php while($ncc = $nhacungcaps->fetch_assoc()): ?>
                                    <option value="<?= $ncc['maNCC'] ?>"><?= htmlspecialchars($ncc['tenNCC']) ?></option>
                                <?php endwhile; ?>
                            </select>
                            <div class="input-hint">
                                <span class="material-symbols-rounded">info</span>
                                Đơn vị cung cấp hàng hóa
                            </div>
                        </div>
                        <div class="form-group">
                            <label>
                                <span class="material-symbols-rounded">warehouse</span>
                                Nhập vào Kho <span class="label-required">*</span>
                            </label>
                            <select name="maKho" id="maKho" class="custom-input" required onchange="updateSteps()">
                                <?php while($k = $khos->fetch_assoc()) echo "<option value='{$k['maKho']}'>" . htmlspecialchars($k['tenKho']) . "</option>"; ?>
                            </select>
                        </div>
                    </div>
                    <div class="input-grid" style="margin-top: 20px;">
                        <div class="form-group">
                            <label>
                                <span class="material-symbols-rounded">person</span>
                                Họ tên người giao
                            </label>
                            <input type="text" name="nguoiGiaoHang" class="custom-input" placeholder="Tên người giao hàng...">
                        </div>
                        <div class="form-group">
                            <label>
                                <span class="material-symbols-rounded">receipt</span>
                                Theo chứng từ số
                            </label>
                            <input type="text" name="soHoaDonNCC" class="custom-input" placeholder="Số chứng từ gốc/Hóa đơn...">
                        </div>
                        <div class="form-group">
                            <label>
                                <span class="material-symbols-rounded">calendar_today</span>
                                Ngày chứng từ
                            </label>
                            <input type="date" name="ngayHoaDon" class="custom-input">
                        </div>
                    </div>
                    <div style="margin-top: 20px;">
                        <div class="form-group">
                            <label>
                                <span class="material-symbols-rounded">sticky_note_2</span>
                                Lý do nhập kho
                            </label>
                            <input type="text" name="ghiChu" id="ghiChu" class="custom-input" placeholder="Lý do nhập kho..." onchange="updateSteps()">
                        </div>
                    </div>
                </div>

                <!-- CARD 2: Chi tiết sản phẩm -->
                <div class="form-card" id="card-products">
                    <div class="card-title">
                        <span class="material-symbols-rounded">piano</span> 
                        Chi tiết đàn nhập kho
                        <span class="card-subtitle" id="productCount">0 sản phẩm</span>
                    </div>
                    
                    <!-- Hidden select cho JS clone -->
                    <select id="product-template" style="display: none;">
                        <?php 
                        if ($maudans) {
                            $maudans->data_seek(0);
                            while($d = $maudans->fetch_assoc()) {
                                echo "<option value='{$d['maMau']}'>" . htmlspecialchars($d['tenMau']) . "</option>";
                            }
                        }
                        ?>
                    </select>

                    <div id="product-container">
                        <div class="product-row" data-row="1">
                            <span class="row-number">1</span>
                            <div>
                                <span class="product-row-label">Mẫu đàn (Model)</span>
                                <select name="maMau[]" class="custom-input dan-select" required onchange="fetchPriceHint(this)">
                                    <?php 
                                    if ($maudans) {
                                        $maudans->data_seek(0);
                                        while($d = $maudans->fetch_assoc()) echo "<option value='{$d['maMau']}'>" . htmlspecialchars($d['tenMau']) . "</option>"; 
                                    }
                                    ?>
                                </select>
                            </div>
                            <div class="col-serial">
                                <span class="product-row-label">Số Serial</span>
                                <input type="text" name="soSerial[]" class="custom-input serial-input" placeholder="Quét hoặc nhập mã Serial..." required oninput="checkSerial(this)">
                                <div class="serial-status" id="serial-status-1"></div>
                            </div>
                            <div class="col-price">
                                <span class="product-row-label">Giá nhập (VNĐ)</span>
                                <input type="number" name="giaNhap[]" class="custom-input price-input" placeholder="0" min="0" required oninput="updateSummary()">
                            </div>
                            <button type="button" class="btn-action btn-remove" onclick="removeRow(this)" title="Xóa dòng này">
                                <span class="material-symbols-rounded">delete</span>
                            </button>
                        </div>
                    </div>
                    
                    <div style="display: flex; gap: 12px; margin-top: 16px; flex-wrap: wrap;">
                        <button type="button" class="btn-action btn-add" onclick="addProductRow()">
                            <span class="material-symbols-rounded">add</span> Thêm sản phẩm
                        </button>
                        <button type="button" class="btn-action btn-add" onclick="addMultipleRows()" style="border-style: solid; border-color: rgba(52, 211, 153, 0.15);">
                            <span class="material-symbols-rounded">playlist_add</span> Thêm 5 dòng
                        </button>
                    </div>

                    <!-- SUMMARY BOX -->
                    <div class="summary-box">
                        <div class="summary-grid">
                            <div class="summary-item">
                                <div class="label">Số lượng sản phẩm</div>
                                <div class="value" id="summaryQty">1</div>
                            </div>
                            <div class="summary-item">
                                <div class="label">Tổng tiền nhập</div>
                                <div class="value highlight" id="summaryTotal">0 đ</div>
                            </div>
                            <div class="summary-item">
                                <div class="label">Giá trung bình</div>
                                <div class="value" id="summaryAvg">0 đ</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ACTION BAR -->
                <div class="action-bar">
                    <div class="action-bar-left">
                        <button type="button" class="btn-action btn-reset" onclick="resetForm()">
                            <span class="material-symbols-rounded">restart_alt</span> Làm mới
                        </button>
                        <div class="keyboard-hint">
                            <span class="kbd">Ctrl</span>+<span class="kbd">Enter</span> để gửi · 
                            <span class="kbd">Ctrl</span>+<span class="kbd">D</span> thêm dòng
                        </div>
                    </div>
                    <div class="action-bar-right">
                        <a href="index.php" class="btn-action btn-reset" style="text-decoration: none;">
                            <span class="material-symbols-rounded">arrow_back</span> Quay lại
                        </a>
                        <button type="submit" name="btnLuuPhieuNhap" class="btn-action btn-submit" id="btnSubmit">
                            <span class="material-symbols-rounded">done_all</span> XÁC NHẬN NHẬP KHO
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// ==========================================
// PHIẾU NHẬP KHO - JAVASCRIPT LOGIC
// ==========================================

let rowCounter = 1;
let serialCheckTimers = {};

// === Thêm dòng sản phẩm ===
function addProductRow() {
    rowCounter++;
    const container = document.getElementById('product-container');
    const options = document.getElementById('product-template').innerHTML;
    
    const newRow = document.createElement('div');
    newRow.className = 'product-row';
    newRow.setAttribute('data-row', rowCounter);
    newRow.innerHTML = `
        <span class="row-number">${rowCounter}</span>
        <div>
            <span class="product-row-label">Mẫu đàn (Model)</span>
            <select name="maMau[]" class="custom-input dan-select" required onchange="fetchPriceHint(this)">
                ${options}
            </select>
        </div>
        <div class="col-serial">
            <span class="product-row-label">Số Serial</span>
            <input type="text" name="soSerial[]" class="custom-input serial-input" placeholder="Quét hoặc nhập mã Serial..." required oninput="checkSerial(this)">
            <div class="serial-status" id="serial-status-${rowCounter}"></div>
        </div>
        <div class="col-price">
            <span class="product-row-label">Giá nhập (VNĐ)</span>
            <input type="number" name="giaNhap[]" class="custom-input price-input" placeholder="0" min="0" required oninput="updateSummary()">
        </div>
        <button type="button" class="btn-action btn-remove" onclick="removeRow(this)" title="Xóa dòng này">
            <span class="material-symbols-rounded">delete</span>
        </button>
    `;
    container.appendChild(newRow);
    
    // Animate in
    newRow.style.animation = 'rowSlideIn 0.35s ease forwards';
    
    // Focus vào ô serial mới
    setTimeout(() => {
        newRow.querySelector('.serial-input').focus();
    }, 100);
    
    updateSummary();
    updateSteps();
}

// === Thêm 5 dòng cùng lúc ===
function addMultipleRows() {
    for (let i = 0; i < 5; i++) {
        setTimeout(() => addProductRow(), i * 80);
    }
}

// === Xóa dòng ===
function removeRow(btn) {
    const rows = document.querySelectorAll('.product-row');
    if (rows.length > 1) {
        const row = btn.closest('.product-row');
        row.style.animation = 'rowSlideOut 0.3s ease forwards';
        setTimeout(() => {
            row.remove();
            reNumberRows();
            updateSummary();
            updateSteps();
        }, 300);
    } else {
        // Rung lắc nếu cố xóa dòng cuối
        const row = btn.closest('.product-row');
        row.style.animation = 'none';
        row.offsetHeight; // force reflow
        row.style.animation = 'pulseGreen 0.6s ease';
        showToast('Phải có ít nhất 1 sản phẩm!', 'warning');
    }
}

// === Đánh lại số thứ tự ===
function reNumberRows() {
    const rows = document.querySelectorAll('.product-row');
    rows.forEach((row, index) => {
        const num = index + 1;
        row.setAttribute('data-row', num);
        row.querySelector('.row-number').textContent = num;
    });
    rowCounter = rows.length;
}

// === Kiểm tra serial trùng (debounced AJAX) ===
function checkSerial(input) {
    const row = input.closest('.product-row');
    const rowNum = row.getAttribute('data-row');
    const statusEl = row.querySelector('.serial-status');
    const serial = input.value.trim();
    
    // Clear previous timer
    if (serialCheckTimers[rowNum]) clearTimeout(serialCheckTimers[rowNum]);
    
    if (serial.length < 2) {
        statusEl.innerHTML = '';
        statusEl.className = 'serial-status';
        input.style.borderColor = '';
        return;
    }

    // Kiểm tra trùng trong form hiện tại
    const allSerials = document.querySelectorAll('.serial-input');
    let localDuplicate = false;
    allSerials.forEach(s => {
        if (s !== input && s.value.trim() === serial && serial !== '') {
            localDuplicate = true;
        }
    });

    if (localDuplicate) {
        statusEl.innerHTML = '<span class="material-symbols-rounded">warning</span> Serial trùng trong form!';
        statusEl.className = 'serial-status error';
        input.style.borderColor = 'var(--danger)';
        return;
    }
    
    // Debounce AJAX check
    serialCheckTimers[rowNum] = setTimeout(() => {
        statusEl.innerHTML = '<span style="font-size:12px; color: var(--text-muted);">Đang kiểm tra...</span>';
        
        const formData = new FormData();
        formData.append('action', 'check_serial');
        formData.append('serial', serial);
        
        fetch('phieunhap.php', { method: 'POST', body: formData })
            .then(r => r.json())
            .then(data => {
                if (data.exists) {
                    statusEl.innerHTML = `<span class="material-symbols-rounded">error</span> Đã tồn tại (${data.tenMau})`;
                    statusEl.className = 'serial-status error';
                    input.style.borderColor = 'var(--danger)';
                } else {
                    statusEl.innerHTML = '<span class="material-symbols-rounded">check_circle</span> Serial hợp lệ';
                    statusEl.className = 'serial-status ok';
                    input.style.borderColor = 'var(--success)';
                    setTimeout(() => { input.style.borderColor = ''; }, 2000);
                }
            })
            .catch(() => {
                statusEl.innerHTML = '';
                statusEl.className = 'serial-status';
            });
    }, 400);
    
    updateSteps();
}

// === Lấy giá gợi ý theo mẫu đàn ===
function fetchPriceHint(select) {
    const row = select.closest('.product-row');
    const priceInput = row.querySelector('.price-input');
    const maMau = select.value;
    
    if (!maMau) return;
    
    const formData = new FormData();
    formData.append('action', 'get_price_hint');
    formData.append('maMau', maMau);
    
    fetch('phieunhap.php', { method: 'POST', body: formData })
        .then(r => r.json())
        .then(data => {
            if (data.found && !priceInput.value) {
                priceInput.value = data.giaNhap;
                priceInput.style.animation = 'pulseGreen 0.5s ease';
                updateSummary();
            }
        })
        .catch(() => {});
}

// === Cập nhật bảng tổng kết ===
function updateSummary() {
    const priceInputs = document.querySelectorAll('.price-input');
    let total = 0;
    let count = 0;
    
    priceInputs.forEach(input => {
        count++;
        const val = parseFloat(input.value);
        if (!isNaN(val)) total += val;
    });
    
    const avg = count > 0 ? Math.round(total / count) : 0;
    const formatter = new Intl.NumberFormat('vi-VN');
    
    document.getElementById('summaryQty').textContent = count;
    document.getElementById('summaryTotal').textContent = formatter.format(total) + ' đ';
    document.getElementById('summaryAvg').textContent = formatter.format(avg) + ' đ';
    document.getElementById('productCount').textContent = count + ' sản phẩm';
    
    updateSteps();
}

// === Cập nhật step indicator ===
function updateSteps() {
    const ncc = document.getElementById('maNCC').value;
    const serials = document.querySelectorAll('.serial-input');
    
    const step1 = document.getElementById('step-1');
    const step2 = document.getElementById('step-2');
    const step3 = document.getElementById('step-3');
    
    // Step 1: completed if NCC filled
    const step1Done = ncc;
    step1.className = step1Done ? 'step-item completed' : 'step-item active';
    if (step1Done) step1.querySelector('.step-num').innerHTML = '<span class="material-symbols-rounded" style="font-size:16px;">check</span>';
    else step1.querySelector('.step-num').textContent = '1';
    
    // Step 2: active/completed if has serials filled
    let hasSerial = false;
    serials.forEach(s => { if (s.value.trim()) hasSerial = true; });
    
    if (step1Done && hasSerial) {
        step2.className = 'step-item completed';
        step2.querySelector('.step-num').innerHTML = '<span class="material-symbols-rounded" style="font-size:16px;">check</span>';
        step3.className = 'step-item active';
    } else if (step1Done) {
        step2.className = 'step-item active';
        step2.querySelector('.step-num').textContent = '2';
        step3.className = 'step-item';
        step3.querySelector('.step-num').textContent = '3';
    } else {
        step2.className = 'step-item';
        step2.querySelector('.step-num').textContent = '2';
        step3.className = 'step-item';
        step3.querySelector('.step-num').textContent = '3';
    }
}

// === Validate trước khi submit ===
function validateAndSubmit() {
    // Kiểm tra serial trùng trong form
    const serials = document.querySelectorAll('.serial-input');
    const serialValues = [];
    let hasDuplicate = false;
    
    serials.forEach(s => {
        const val = s.value.trim();
        if (val && serialValues.includes(val)) {
            hasDuplicate = true;
            s.style.borderColor = 'var(--danger)';
        }
        if (val) serialValues.push(val);
    });
    
    if (hasDuplicate) {
        showToast('Có mã Serial bị trùng trong phiếu!', 'error');
        return false;
    }
    
    // Kiểm tra có serial bị lỗi trong DB
    const errorStatuses = document.querySelectorAll('.serial-status.error');
    if (errorStatuses.length > 0) {
        showToast('Có mã Serial đã tồn tại trong hệ thống! Vui lòng kiểm tra lại.', 'error');
        return false;
    }
    
    return confirm('Bạn có chắc chắn muốn nhập số hàng này vào kho?\n\nSố lượng: ' + document.getElementById('summaryQty').textContent + ' sản phẩm\nTổng tiền: ' + document.getElementById('summaryTotal').textContent);
}

// === Reset form ===
function resetForm() {
    if (confirm('Bạn có muốn xóa toàn bộ thông tin đã nhập?')) {
        document.getElementById('formPhieuNhap').reset();
        // Xóa các dòng thừa, giữ lại 1
        const rows = document.querySelectorAll('.product-row');
        rows.forEach((row, index) => {
            if (index > 0) row.remove();
        });
        rowCounter = 1;
        reNumberRows();
        updateSummary();
        updateSteps();
        // Clear serial statuses
        document.querySelectorAll('.serial-status').forEach(s => { s.innerHTML = ''; s.className = 'serial-status'; });
    }
}

// === Toast notification ===
function showToast(message, type = 'info') {
    const existing = document.querySelector('.toast-notification');
    if (existing) existing.remove();
    
    const toast = document.createElement('div');
    toast.className = 'toast-notification';
    
    const colors = {
        error: { bg: 'var(--danger-bg)', color: 'var(--danger)', border: 'rgba(248,113,113,0.3)', icon: 'error' },
        warning: { bg: 'var(--warning-bg)', color: 'var(--warning)', border: 'rgba(251,191,36,0.3)', icon: 'warning' },
        success: { bg: 'var(--success-bg)', color: 'var(--success)', border: 'rgba(52,211,153,0.3)', icon: 'check_circle' },
        info: { bg: 'var(--info-bg)', color: 'var(--info)', border: 'rgba(96,165,250,0.3)', icon: 'info' }
    };
    
    const c = colors[type] || colors.info;
    toast.style.cssText = `
        position: fixed; bottom: 24px; right: 24px; z-index: 9999;
        background: ${c.bg}; color: ${c.color}; border: 1px solid ${c.border};
        padding: 14px 24px; border-radius: 12px; font-size: 14px; font-weight: 600;
        display: flex; align-items: center; gap: 10px;
        animation: fadeInUp 0.3s ease; font-family: 'Inter', sans-serif;
        backdrop-filter: blur(16px); box-shadow: 0 8px 32px rgba(0,0,0,0.3);
    `;
    toast.innerHTML = `<span class="material-symbols-rounded">${c.icon}</span> ${message}`;
    document.body.appendChild(toast);
    
    setTimeout(() => {
        toast.style.opacity = '0';
        toast.style.transform = 'translateY(10px)';
        toast.style.transition = '0.3s ease';
        setTimeout(() => toast.remove(), 300);
    }, 3500);
}

// === Keyboard shortcuts ===
document.addEventListener('keydown', function(e) {
    // Ctrl + Enter = Submit
    if (e.ctrlKey && e.key === 'Enter') {
        e.preventDefault();
        document.getElementById('btnSubmit').click();
    }
    // Ctrl + D = Add row
    if (e.ctrlKey && e.key === 'd') {
        e.preventDefault();
        addProductRow();
    }
});

// Init summary
updateSummary();
</script>

<?php include 'includes/footer.php'; ?>