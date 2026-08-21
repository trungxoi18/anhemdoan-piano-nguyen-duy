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
$role_id = $_SESSION['role_id'];
$msg = "";

if (!isset($_GET['id'])) {
    header("Location: taophieu_baotri.php");
    exit();
}
$maPhieuBT = intval($_GET['id']);

// Lấy thông tin phiếu
$stmt = $conn->prepare("SELECT * FROM phieubaotri WHERE maPhieuBT = ?");
$stmt->bind_param("i", $maPhieuBT);
$stmt->execute();
$res = $stmt->get_result();

if ($res->num_rows === 0) {
    die("Không tìm thấy phiếu bảo trì.");
}

$phieu = $res->fetch_assoc();

// Kiểm tra quyền
if ($role_id != 1 && $phieu['maNhanVienLap'] != $user_id) {
    die("Bạn không có quyền sửa phiếu này.");
}

// Kiểm tra trạng thái
if ($phieu['trangThai'] != 'Chờ duyệt tiếp nhận') {
    die("Phiếu đã được duyệt hoặc xử lý, không thể sửa.");
}

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
// POST: Lưu cập nhật
// ==========================================
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['btnUpdate'])) {
    $maHoaDon = intval($_POST['maHoaDon']);
    $maKhachHang = intval($_POST['maKhachHang']);
    $maSerial = intval($_POST['maSerial']);
    $moTaLoi = trim($_POST['moTaLoi']);
    
    if ($maHoaDon > 0 && $maKhachHang > 0 && $maSerial > 0 && !empty($moTaLoi)) {
        // Kiểm tra xem đàn này có đang được bảo trì chưa hoàn thành (trừ phiếu hiện tại)
        $check_bt = $conn->query("SELECT maPhieuBT FROM phieubaotri WHERE maSerial = $maSerial AND maPhieuBT != $maPhieuBT AND trangThai NOT IN ('Hoàn thành', 'Đã hủy')");
        if ($check_bt->num_rows > 0) {
            $msg = "<div class='alert alert-danger'>Lỗi: Sản phẩm này đang trong quá trình bảo trì khác!</div>";
        } else {
            $sql = "UPDATE phieubaotri SET maHoaDon = ?, maKhachHang = ?, maSerial = ?, moTaLoi = ?, ngayCapNhat = NOW() WHERE maPhieuBT = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("iiisi", $maHoaDon, $maKhachHang, $maSerial, $moTaLoi, $maPhieuBT);
            
            if ($stmt->execute()) {
                writeLog($conn, 'SỬA BẢO TRÍ', "Sửa phiếu tiếp nhận bảo trì #$maPhieuBT");
                $_SESSION['flash_success'] = "Cập nhật phiếu tiếp nhận bảo trì #$maPhieuBT thành công!";
                header("Location: taophieu_baotri.php");
                exit();
            } else {
                $msg = "<div class='alert alert-danger'>Lỗi khi lưu vào DB</div>";
            }
        }
    } else {
        $msg = "<div class='alert alert-danger'>Vui lòng điền đầy đủ thông tin hợp lệ!</div>";
    }
}
?>

<?php include 'includes/header.php'; ?>
<?php include 'includes/sidebar.php'; ?>


<div class="main-wrapper">
    <?php include 'includes/topbar.php'; ?>

    <div class="content">
        <div class="form-center-container">
            <a href="taophieu_baotri.php" style="display:inline-flex; align-items:center; gap:5px; color:var(--text-secondary); text-decoration:none; margin-bottom:15px; font-weight:600;"><span class="material-symbols-rounded">arrow_back</span> Quay lại</a>
            
            <div class="form-card">
                <h2 class="card-title"><span class="material-symbols-rounded">edit</span> Sửa Phiếu Tiếp Nhận #<?= $maPhieuBT ?></h2>
                
                <?php echo $msg; ?>
                
                <form method="POST" id="btForm">
                    <input type="hidden" name="maKhachHang" id="maKhachHang" value="<?= $phieu['maKhachHang'] ?>">
                    
                    <div class="form-group">
                        <label>Mã Hóa Đơn đã mua</label>
                        <div class="search-box">
                            <input type="number" name="maHoaDon" id="maHoaDon" class="custom-input" value="<?= $phieu['maHoaDon'] ?>" required>
                            <button type="button" onclick="checkInvoice()"><span class="material-symbols-rounded">search</span> Kiểm tra</button>
                        </div>
                    </div>
                    
                    <div class="customer-info-box" id="customerInfo">
                        <p><strong>Khách hàng:</strong> <span id="txtTenKH"></span></p>
                        <p><strong>Số điện thoại:</strong> <span id="txtSdtKH"></span></p>
                    </div>
                    
                    <div class="form-group">
                        <label>Sản phẩm cần bảo trì (Serial)</label>
                        <select name="maSerial" id="maSerial" class="custom-input" required>
                            <!-- Options will be loaded via JS -->
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label>Tình trạng / Mô tả lỗi</label>
                        <textarea name="moTaLoi" class="custom-input" rows="4" required><?= htmlspecialchars($phieu['moTaLoi']) ?></textarea>
                    </div>
                    
                    <button type="submit" name="btnUpdate" class="btn-submit">
                        LƯU THAY ĐỔI
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
async function checkInvoice(autoSelectSerial = null) {
    const maHoaDon = document.getElementById('maHoaDon').value;
    if (!maHoaDon) return;
    
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
                if (autoSelectSerial && s.maSerial == autoSelectSerial) {
                    opt.selected = true;
                }
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

// Auto-load invoice data on page load
window.addEventListener('DOMContentLoaded', () => {
    checkInvoice(<?= $phieu['maSerial'] ?>);
});
</script>

<?php include 'includes/footer.php'; ?>
