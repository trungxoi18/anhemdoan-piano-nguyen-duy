<?php
// Kiểm tra session để tránh lỗi Notice
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'db.php';
require_once 'functions.php';

// Kiểm tra quyền
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role_id'], [1, 2, 3])) {
    header("Location: index.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$role_id = $_SESSION['role_id'];
$msg = "";

// Xử lý lưu phiếu xuất
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['btnLuuPhieuXuat'])) {
    $maHoaDon = !empty($_POST['maHoaDon']) ? $_POST['maHoaDon'] : NULL;
    $maKho = $_POST['maKho'];
    $lyDoXuat = $_POST['lyDoXuat'];
    $trangThai = 'Chờ duyệt'; // Chờ admin duyệt
    
    $nguoiNhanHang = trim($_POST['nguoiNhanHang'] ?? '');
    $soChungTu = trim($_POST['soChungTu'] ?? '');
    $ngayChungTu = !empty($_POST['ngayChungTu']) ? $_POST['ngayChungTu'] : NULL;
    $donViNhan = trim($_POST['donViNhan'] ?? '');
    
    $list_serial = $_POST['soSerial'];

    $conn->begin_transaction();
    try {
        $sql_phieu = "INSERT INTO phieuxuat (maNhanVien, maHoaDon, nguoiNhanHang, soChungTu, ngayChungTu, donViNhan, ngayXuat, lyDoXuat, maKho, trangThai) VALUES (?, ?, ?, ?, ?, ?, NOW(), ?, ?, ?)";
        $stmt = $conn->prepare($sql_phieu);
        $stmt->bind_param("iisssssis", $user_id, $maHoaDon, $nguoiNhanHang, $soChungTu, $ngayChungTu, $donViNhan, $lyDoXuat, $maKho, $trangThai);
        $stmt->execute();
        $maPhieuXuat = $conn->insert_id;

        $soLuongXuat = 0;
        foreach ($list_serial as $serial) {
            $serial = trim($serial);
            if(empty($serial)) continue;
            
            // Bước 1: Kiểm tra serial có tồn tại trong kho đã chọn không
            $st_check_kho = $conn->prepare("SELECT maSerial, trangThai FROM danserial WHERE soSerial = ? AND maKho = ?");
            $st_check_kho->bind_param("si", $serial, $maKho);
            $st_check_kho->execute();
            $res_kho = $st_check_kho->get_result();
            if ($res_kho->num_rows == 0) {
                // Kiểm tra serial có tồn tại ở kho khác không để đưa ra thông báo rõ hơn
                $st_any = $conn->prepare("SELECT k.tenKho FROM danserial ds JOIN kho k ON ds.maKho = k.maKho WHERE ds.soSerial = ?");
                $st_any->bind_param("s", $serial);
                $st_any->execute();
                $res_any = $st_any->get_result();
                if ($res_any->num_rows > 0) {
                    $r_any = $res_any->fetch_assoc();
                    throw new Exception("Mã Serial [$serial] không thuộc kho đã chọn! (Serial đang ở: {$r_any['tenKho']})");
                }
                throw new Exception("Mã Serial [$serial] không tồn tại trong hệ thống!");
            }
            $row_kho = $res_kho->fetch_assoc();

            // Bước 2: Kiểm tra trạng thái serial
            if ($maHoaDon && !in_array($row_kho['trangThai'], ['Trong kho', 'Chờ giao'])) {
                throw new Exception("Mã Serial [$serial] không thuộc hóa đơn hoặc không sẵn sàng (Trạng thái: {$row_kho['trangThai']})!");
            } elseif (!$maHoaDon && $row_kho['trangThai'] !== 'Trong kho') {
                throw new Exception("Mã Serial [$serial] hiện không sẵn sàng để xuất (Trạng thái: {$row_kho['trangThai']})!");
            }
            
            $maSerial = $row_kho['maSerial'];
            
            $conn->query("INSERT INTO chitietphieuxuat (maPhieuXuat, maSerial) VALUES ($maPhieuXuat, $maSerial)");
            
            // Cập nhật trạng thái đàn sang "Chờ xuất" thay vì Đã bán
            $conn->query("UPDATE danserial SET trangThai = 'Chờ xuất' WHERE maSerial = $maSerial");
            $soLuongXuat++;
        }

        if ($soLuongXuat == 0) {
            throw new Exception("Phiếu xuất phải có ít nhất 1 sản phẩm hợp lệ!");
        }

        // Cập nhật trạng thái hóa đơn nếu có liên kết
        if ($maHoaDon) {
            $conn->query("UPDATE hoadon SET trangThai = 'Đang giao' WHERE maHoaDon = $maHoaDon");
        }
        $conn->commit();
        
        // Ghi log
        writeLog($conn, 'XUẤT KHO', "Đã lập phiếu xuất kho #$maPhieuXuat - Xuất $soLuongXuat sản phẩm");

        // Thông báo cho người xuất
        $msg_xuat = "Lập phiếu xuất thành công (Chờ duyệt)! Phiếu #$maPhieuXuat - $soLuongXuat sản phẩm";
        $conn->query("INSERT INTO ThongBao (maTaiKhoan, noiDung, link) VALUES ($user_id, '$msg_xuat', 'phieuxuat.php')");

        // Thông báo cho Admin (role 1)
        $msg_admin = "Có phiếu xuất kho mới #$maPhieuXuat cần được phê duyệt ($soLuongXuat SP)";
        $conn->query("INSERT INTO ThongBao (maVaiTro, noiDung, link) VALUES (1, '$msg_admin', 'phieuxuat.php')");

        $_SESSION['flash_success'] = "Lập phiếu xuất thành công! Phiếu #$maPhieuXuat đang chờ phê duyệt.";
        header("Location: xuat_pdf.php?type=phieuxuat&id=$maPhieuXuat");
        exit();
    } catch (Exception $e) {
        $conn->rollback();
        $msg = "<div class='alert alert-danger' style='background: var(--danger-bg); color: var(--danger); border: 1px solid rgba(248,113,113,0.3); padding:15px; border-radius:12px; margin-bottom:24px; display: flex; align-items: center; gap: 10px;'><span class='material-symbols-rounded'>error</span> Lỗi: " . $e->getMessage() . "</div>";
    }
}

$hoadons = $conn->query("SELECT maHoaDon, ngayLap FROM hoadon WHERE trangThai = 'Chờ giao'");
$khos = $conn->query("SELECT * FROM kho");

$prefill_serials = [];
if (isset($_GET['maHoaDon']) && !empty($_GET['maHoaDon'])) {
    $maHoaDon = intval($_GET['maHoaDon']);
    $sql_serials = "SELECT ds.soSerial FROM chitiethoadon cthd 
                    JOIN danserial ds ON cthd.maSerial = ds.maSerial 
                    WHERE cthd.maHoaDon = ?";
    $stmt_serials = $conn->prepare($sql_serials);
    $stmt_serials->bind_param("i", $maHoaDon);
    $stmt_serials->execute();
    $res_serials = $stmt_serials->get_result();
    while ($r = $res_serials->fetch_assoc()) {
        $prefill_serials[] = $r['soSerial'];
    }
}
?>

<?php include 'includes/header.php'; ?>
<?php include 'includes/sidebar.php'; ?>

<style>
    /* Dark Mode Premium Form Styles */
    .form-center-container { max-width: 1100px; margin: 0 auto; animation: fadeInUp 0.5s ease; }
    
    .welcome-banner { 
        background: linear-gradient(135deg, rgba(124, 92, 252, 0.2), rgba(94, 234, 212, 0.05)); 
        border: 1px solid rgba(124, 92, 252, 0.3);
        color: var(--text-primary); 
        padding: 28px 32px; 
        border-radius: var(--radius-xl); 
        margin-bottom: 28px; 
        position: relative;
        overflow: hidden;
    }
    
    .welcome-banner::before {
        content: ''; position: absolute; top: -50%; right: -20%; width: 50%; height: 200%;
        background: radial-gradient(circle, rgba(124, 92, 252, 0.1) 0%, transparent 70%); pointer-events: none;
    }

    .form-card { 
        background: var(--bg-card); 
        backdrop-filter: blur(12px);
        padding: 32px; 
        border-radius: var(--radius-xl); 
        margin-bottom: 24px; 
        border: 1px solid var(--glass-border); 
        transition: 0.3s;
    }
    .form-card:hover { border-color: rgba(124, 92, 252, 0.3); box-shadow: 0 4px 20px rgba(0,0,0,0.2); }
    
    .card-title { 
        font-size: 1.15rem; 
        font-weight: 700; 
        color: var(--text-primary); 
        margin-bottom: 24px; 
        padding-bottom: 12px; 
        border-bottom: 1px dashed var(--glass-border); 
        display: flex; align-items: center; gap: 8px;
    }
    
    .input-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 24px; }
    .form-group label { display: block; font-weight: 600; color: var(--text-secondary); margin-bottom: 10px; font-size: 13px; text-transform: uppercase; letter-spacing: 0.5px; }
    
    .custom-input { 
        width: 100%; padding: 14px 16px; 
        background: rgba(0, 0, 0, 0.2);
        border: 1px solid var(--glass-border); 
        border-radius: var(--radius-md); 
        outline: none; transition: 0.3s; 
        color: var(--text-primary);
        font-family: inherit; font-size: 14px;
    }
    .custom-input:focus { border-color: var(--accent); background: rgba(124, 92, 252, 0.05); box-shadow: 0 0 0 3px rgba(124, 92, 252, 0.15); }
    .custom-input option { background: var(--bg-secondary); color: var(--text-primary); }
    
    .serial-row { 
        display: flex; gap: 16px; margin-bottom: 16px; align-items: center; 
        background: rgba(255,255,255,0.02); padding: 20px; 
        border-radius: var(--radius-md); border: 1px solid var(--glass-border); 
        transition: 0.3s;
    }
    .serial-row:hover { border-color: rgba(255,255,255,0.1); background: rgba(255,255,255,0.04); }
    
    .btn-action { padding: 14px 24px; border-radius: var(--radius-md); font-weight: 600; cursor: pointer; border: none; transition: 0.3s; display: inline-flex; align-items: center; gap: 8px; font-family: inherit;}
    .btn-submit { background: linear-gradient(135deg, var(--accent), #a78bfa); color: white; font-size: 1rem; box-shadow: 0 4px 12px var(--accent-glow); }
    .btn-submit:hover { transform: translateY(-2px); box-shadow: 0 6px 20px var(--accent-glow); }
    .btn-add { background: var(--bg-tertiary); color: var(--text-primary); border: 1px dashed var(--glass-border); }
    .btn-add:hover { border-color: var(--accent); color: var(--accent); background: rgba(124, 92, 252, 0.05); }
    .btn-remove { background: var(--danger-bg); color: var(--danger); padding: 14px; height: 48px; border: 1px solid rgba(248,113,113,0.2); }
    .btn-remove:hover { background: rgba(248, 113, 113, 0.2); }
</style>

<div class="main-wrapper">
    <?php include 'includes/topbar.php'; ?>

    <div class="content">
        <div class="form-center-container">
            <div class="welcome-banner">
                <h1 style="margin: 0 0 8px 0; font-size: 1.6rem; color: var(--accent-secondary);">Lập Phiếu Xuất Kho</h1>
                <p style="margin: 0; opacity: 0.8; font-size: 14px;">Nhân viên thực hiện: <b style="color: white;"><?php echo htmlspecialchars($_SESSION['fullname'] ?? 'Admin'); ?></b></p>
            </div>

            <?php echo $msg; ?>

            <form method="POST" onsubmit="return confirm('Xác nhận lưu phiếu xuất này?');">
                <div class="form-card">
                    <div class="card-title"><span class="material-symbols-rounded" style="color: var(--accent-secondary);">route</span> 1. Thông tin điều hướng xuất kho</div>
                    <div class="input-grid">
                        <div class="form-group">
                            <label>Liên kết Hóa đơn (Sales):</label>
                            <select name="maHoaDon" class="custom-input">
                                <option value="">-- Xuất nội bộ / Lý do khác --</option>
                                <?php while($hd = $hoadons->fetch_assoc()): ?>
                                    <option value="<?= $hd['maHoaDon'] ?>" <?= (isset($_GET['maHoaDon']) && $_GET['maHoaDon'] == $hd['maHoaDon']) ? 'selected' : '' ?>>Đơn #<?= $hd['maHoaDon'] ?> - Ngày <?= date('d/m/Y', strtotime($hd['ngayLap'])) ?></option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Chọn Kho xuất:</label>
                            <select name="maKho" class="custom-input" required>
                                <?php while($k = $khos->fetch_assoc()) echo "<option value='{$k['maKho']}'>{$k['tenKho']}</option>"; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Lý do xuất:</label>
                            <input type="text" name="lyDoXuat" class="custom-input" placeholder="VD: Xuất bán hàng..." required>
                        </div>
                    </div>
                    <div class="input-grid" style="margin-top: 16px;">
                        <div class="form-group">
                            <label><span class="material-symbols-rounded">person</span> Họ tên người nhận hàng</label>
                            <input type="text" name="nguoiNhanHang" class="custom-input" placeholder="Tên người nhận hàng...">
                        </div>
                        <div class="form-group">
                            <label><span class="material-symbols-rounded">apartment</span> Đơn vị nhận (Của)</label>
                            <input type="text" name="donViNhan" class="custom-input" placeholder="Tên đơn vị/bộ phận nhận...">
                        </div>
                        <div class="form-group">
                            <label><span class="material-symbols-rounded">receipt</span> Theo chứng từ số</label>
                            <input type="text" name="soChungTu" class="custom-input" placeholder="Số chứng từ gốc...">
                        </div>
                        <div class="form-group">
                            <label><span class="material-symbols-rounded">calendar_today</span> Ngày chứng từ</label>
                            <input type="date" name="ngayChungTu" class="custom-input">
                        </div>
                    </div>
                </div>

                <div class="form-card">
                    <div class="card-title" style="margin-top: 30px;"><span class="material-symbols-rounded" style="color: var(--accent-secondary);">qr_code_scanner</span> 3. Danh sách Mã đàn xuất (Serial)</div>
                    <div id="serial-container">
                        <?php if(empty($prefill_serials)): ?>
                            <div class="serial-row">
                                <input type="text" name="soSerial[]" class="custom-input" placeholder="Nhập/Quét mã Serial" required>
                                <button type="button" class="btn-action btn-add" onclick="addSerialRow()">
                                    <span class="material-symbols-rounded">add</span>
                                </button>
                            </div>
                        <?php else: ?>
                            <?php foreach($prefill_serials as $index => $serial): ?>
                            <div class="serial-row">
                                <input type="text" name="soSerial[]" class="custom-input" placeholder="Nhập/Quét mã Serial" value="<?= htmlspecialchars($serial) ?>" required>
                                <?php if($index == 0): ?>
                                <button type="button" class="btn-action btn-add" onclick="addSerialRow()">
                                    <span class="material-symbols-rounded">add</span>
                                </button>
                                <?php else: ?>
                                <button type="button" class="btn-action btn-remove" onclick="this.parentElement.remove()">
                                    <span class="material-symbols-rounded">delete</span>
                                </button>
                                <?php endif; ?>
                            </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                    <button type="button" class="btn-action btn-add" onclick="addSerialRow()" style="margin-top: 16px;">
                        <span class="material-symbols-rounded">add</span> Thêm mã Serial
                    </button>
                </div>

                <div style="text-align: right; padding-bottom: 50px;">
                    <button type="submit" name="btnLuuPhieuXuat" class="btn-action btn-submit">
                        <span class="material-symbols-rounded">done_all</span> XÁC NHẬN XUẤT KHO
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function addSerialRow() {
    const container = document.getElementById('serial-container');
    const newRow = document.createElement('div');
    newRow.className = 'serial-row';
    newRow.innerHTML = `
        <div style="flex: 1;">
            <input type="text" name="soSerial[]" class="custom-input" placeholder="Nhập hoặc quét mã Serial..." required>
        </div>
        <button type="button" class="btn-action btn-remove" onclick="removeSerialRow(this)" title="Xóa"><span class="material-symbols-rounded">delete</span></button>
    `;
    container.appendChild(newRow);
    
    // Animation
    newRow.style.opacity = '0';
    newRow.style.transform = 'translateY(10px)';
    setTimeout(() => {
        newRow.style.transition = '0.3s ease';
        newRow.style.opacity = '1';
        newRow.style.transform = 'translateY(0)';
    }, 10);
}

function removeSerialRow(btn) {
    const row = btn.parentElement;
    row.style.opacity = '0';
    row.style.transform = 'scale(0.95)';
    setTimeout(() => row.remove(), 300);
}
</script>

<?php include 'includes/footer.php'; ?>