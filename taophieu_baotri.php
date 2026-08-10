<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'db.php';
require_once 'functions.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$msg = "";

// ==========================================
// AJAX: Lấy danh sách serial theo mã hóa đơn
// ==========================================
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'get_serials') {
    header('Content-Type: application/json');
    $maHoaDon = intval($_POST['maHoaDon'] ?? 0);
    
    if ($maHoaDon <= 0) {
        echo json_encode(['success' => false, 'error' => 'Mã hóa đơn không hợp lệ']);
        exit();
    }
    
    // Kiểm tra hóa đơn
    $st_hd = $conn->prepare("SELECT maKhachHang, trangThai FROM hoadon WHERE maHoaDon = ?");
    $st_hd->bind_param("i", $maHoaDon);
    $st_hd->execute();
    $res_hd = $st_hd->get_result();
    
    if ($res_hd->num_rows == 0) {
        echo json_encode(['success' => false, 'error' => 'Không tìm thấy hóa đơn này trong hệ thống']);
        exit();
    }
    $hd = $res_hd->fetch_assoc();
    if ($hd['trangThai'] == 'Đã hủy') {
        echo json_encode(['success' => false, 'error' => 'Hóa đơn này đã bị hủy']);
        exit();
    }
    
    // Lấy thông tin khách hàng
    $maKhachHang = $hd['maKhachHang'];
    $st_kh = $conn->query("SELECT hoTen, soDienThoai FROM khachhang WHERE maKhachHang = $maKhachHang");
    $kh = $st_kh->fetch_assoc();
    
    // Lấy chi tiết serials
    $st_ct = $conn->prepare("
        SELECT ct.maSerial, ds.soSerial, md.tenMau, hd.tenHang
        FROM chitiethoadon ct
        JOIN danserial ds ON ct.maSerial = ds.maSerial
        JOIN maudan md ON ds.maMau = md.maMau
        LEFT JOIN hangdan hd ON md.maHang = hd.maHang
        WHERE ct.maHoaDon = ?
    ");
    $st_ct->bind_param("i", $maHoaDon);
    $st_ct->execute();
    $res_ct = $st_ct->get_result();
    
    $serials = [];
    while ($row = $res_ct->fetch_assoc()) {
        $serials[] = $row;
    }
    
    if (count($serials) == 0) {
        echo json_encode(['success' => false, 'error' => 'Hóa đơn này không có sản phẩm nào']);
        exit();
    }
    
    echo json_encode([
        'success' => true,
        'maKhachHang' => $maKhachHang,
        'tenKH' => $kh['hoTen'],
        'sdtKH' => $kh['soDienThoai'],
        'serials' => $serials
    ]);
    exit();
}

// ==========================================
// POST: Lưu phiếu tiếp nhận
// ==========================================
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['btnSave'])) {
    $maHoaDon = intval($_POST['maHoaDon']);
    $maKhachHang = intval($_POST['maKhachHang']);
    $maSerial = intval($_POST['maSerial']);
    $moTaLoi = trim($_POST['moTaLoi']);
    
    if ($maHoaDon > 0 && $maKhachHang > 0 && $maSerial > 0 && !empty($moTaLoi)) {
        // Kiểm tra xem đàn này có đang được bảo trì chưa hoàn thành không
        $check_bt = $conn->query("SELECT maPhieuBT FROM phieubaotri WHERE maSerial = $maSerial AND trangThai NOT IN ('Hoàn thành', 'Đã hủy')");
        if ($check_bt->num_rows > 0) {
            $msg = "<div class='alert alert-danger'>Lỗi: Sản phẩm này đang trong quá trình bảo trì khác!</div>";
        } else {
            $conn->begin_transaction();
            try {
                $sql = "INSERT INTO phieubaotri (maHoaDon, maKhachHang, maSerial, maNhanVienLap, ngayTiepNhan, moTaLoi, trangThai, ngayCapNhat) 
                        VALUES (?, ?, ?, ?, NOW(), ?, 'Chờ duyệt tiếp nhận', NOW())";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("iiiis", $maHoaDon, $maKhachHang, $maSerial, $user_id, $moTaLoi);
                
                if ($stmt->execute()) {
                    $maPhieuBT = $conn->insert_id;
                    writeLog($conn, 'TẠO BẢO TRÍ', "Lập phiếu tiếp nhận bảo trì #$maPhieuBT cho hóa đơn #$maHoaDon");
                    $conn->commit();
                    $_SESSION['flash_success'] = "Lập phiếu tiếp nhận thành công! Phiếu #$maPhieuBT đang chờ duyệt.";
                    header("Location: quanly_baotri.php");
                    exit();
                } else {
                    throw new Exception("Lỗi khi lưu vào DB");
                }
            } catch (Exception $e) {
                $conn->rollback();
                $msg = "<div class='alert alert-danger'>Lỗi: " . $e->getMessage() . "</div>";
            }
        }
    } else {
        $msg = "<div class='alert alert-danger'>Vui lòng điền đầy đủ thông tin hợp lệ!</div>";
    }
}
?>

<?php include 'includes/header.php'; ?>
<?php include 'includes/sidebar.php'; ?>

<style>
    .page-container { max-width: 800px; margin: 40px auto; animation: fadeInUp 0.5s ease; }
    .form-card { background: var(--bg-card); padding: 40px; border-radius: var(--radius-xl); border: 1px solid var(--glass-border); box-shadow: 0 10px 30px rgba(0,0,0,0.1); }
    .form-title { color: var(--accent); display: flex; align-items: center; gap: 10px; margin-top: 0; margin-bottom: 30px; font-size: 24px; padding-bottom: 15px; border-bottom: 1px dashed var(--glass-border); }
    
    .form-group { margin-bottom: 24px; }
    .form-group label { display: block; margin-bottom: 10px; font-weight: 600; color: var(--text-secondary); text-transform: uppercase; font-size: 13px; }
    
    .custom-input { width: 100%; padding: 14px 16px; background: rgba(0,0,0,0.1); border: 1px solid var(--glass-border); border-radius: var(--radius-md); color: var(--text-primary); font-family: inherit; font-size: 15px; transition: 0.3s; }
    .custom-input:focus { border-color: var(--accent); outline: none; background: rgba(124, 92, 252, 0.05); }
    .custom-input[readonly] { background: rgba(0,0,0,0.2); opacity: 0.7; cursor: not-allowed; }
    select.custom-input { background-color: var(--bg-secondary); }
    
    .search-box { display: flex; gap: 10px; }
    .search-box button { padding: 0 24px; background: var(--accent); color: white; border: none; border-radius: var(--radius-md); font-weight: 600; cursor: pointer; transition: 0.2s; white-space: nowrap; }
    .search-box button:hover { background: #6b4cf0; }
    
    .customer-info-box { background: rgba(16, 185, 129, 0.05); border: 1px solid rgba(16, 185, 129, 0.2); padding: 15px 20px; border-radius: var(--radius-md); display: none; margin-bottom: 24px; }
    .customer-info-box.active { display: block; animation: fadeIn 0.4s ease; }
    .customer-info-box p { margin: 5px 0; color: var(--text-primary); }
    .customer-info-box p strong { color: var(--success); }
    
    .btn-submit { width: 100%; padding: 16px; background: linear-gradient(135deg, var(--accent), #a78bfa); color: white; border: none; border-radius: var(--radius-md); font-size: 16px; font-weight: 700; cursor: pointer; transition: 0.3s; margin-top: 10px; }
    .btn-submit:hover { transform: translateY(-2px); box-shadow: 0 8px 20px var(--accent-glow); }
</style>

<div class="main-wrapper">
    <?php include 'includes/topbar.php'; ?>

    <div class="content">
        <div class="page-container">
            <div class="form-card">
                <h2 class="form-title"><span class="material-symbols-rounded">support_agent</span> Tiếp Nhận Bảo Trì</h2>
                
                <?php echo $msg; ?>
                
                <form method="POST" id="btForm">
                    <input type="hidden" name="maKhachHang" id="maKhachHang">
                    
                    <div class="form-group">
                        <label>Mã Hóa Đơn đã mua</label>
                        <div class="search-box">
                            <input type="number" name="maHoaDon" id="maHoaDon" class="custom-input" placeholder="Nhập mã hóa đơn..." required>
                            <button type="button" onclick="checkInvoice()"><span class="material-symbols-rounded">search</span> Kiểm tra</button>
                        </div>
                        <small style="color:var(--text-muted); display:block; margin-top:8px;">Nhập mã hóa đơn và bấm Kiểm tra để tải danh sách sản phẩm.</small>
                    </div>
                    
                    <div class="customer-info-box" id="customerInfo">
                        <p><strong>Khách hàng:</strong> <span id="txtTenKH"></span></p>
                        <p><strong>Số điện thoại:</strong> <span id="txtSdtKH"></span></p>
                    </div>
                    
                    <div class="form-group">
                        <label>Sản phẩm cần bảo trì (Serial)</label>
                        <select name="maSerial" id="maSerial" class="custom-input" required>
                            <option value="">-- Vui lòng kiểm tra hóa đơn trước --</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label>Tình trạng / Mô tả lỗi</label>
                        <textarea name="moTaLoi" class="custom-input" rows="4" placeholder="Ghi nhận phản ánh của khách hàng về lỗi sản phẩm..." required></textarea>
                    </div>
                    
                    <button type="submit" name="btnSave" class="btn-submit">
                        LẬP PHIẾU TIẾP NHẬN BẢO TRÍ
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
async function checkInvoice() {
    const maHoaDon = document.getElementById('maHoaDon').value;
    if (!maHoaDon) {
        alert("Vui lòng nhập mã hóa đơn!");
        return;
    }
    
    const btn = document.querySelector('.search-box button');
    btn.innerHTML = '<span class="material-symbols-rounded" style="animation: spin 1s linear infinite;">autorenew</span> Đang tải...';
    
    try {
        const fd = new FormData();
        fd.append('action', 'get_serials');
        fd.append('maHoaDon', maHoaDon);
        
        const resp = await fetch(window.location.href, { method: 'POST', body: fd });
        const data = await resp.json();
        
        const sel = document.getElementById('maSerial');
        sel.innerHTML = '';
        
        if (data.success) {
            document.getElementById('maKhachHang').value = data.maKhachHang;
            document.getElementById('txtTenKH').innerText = data.tenKH;
            document.getElementById('txtSdtKH').innerText = data.sdtKH;
            document.getElementById('customerInfo').classList.add('active');
            
            data.serials.forEach(s => {
                const opt = document.createElement('option');
                opt.value = s.maSerial;
                opt.textContent = `${s.soSerial} - ${s.tenMau} ${s.tenHang ? '('+s.tenHang+')' : ''}`;
                sel.appendChild(opt);
            });
        } else {
            document.getElementById('customerInfo').classList.remove('active');
            document.getElementById('maKhachHang').value = '';
            sel.innerHTML = '<option value="">-- Lỗi: ' + data.error + ' --</option>';
            alert(data.error);
        }
    } catch(e) {
        alert("Lỗi kết nối máy chủ");
    }
    
    btn.innerHTML = '<span class="material-symbols-rounded">search</span> Kiểm tra';
}

// Thêm keyframes spin
const style = document.createElement('style');
style.innerHTML = `@keyframes spin { 100% { transform: rotate(360deg); } }`;
document.head.appendChild(style);
</script>

<?php include 'includes/footer.php'; ?>
