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

// Lấy lịch sử lập phiếu bảo trì
$sql_history = "SELECT pb.*, kh.hoTen as tenKH, ds.soSerial, md.tenMau, nv.hoTen as tenNVLap
        FROM phieubaotri pb
        JOIN khachhang kh ON pb.maKhachHang = kh.maKhachHang
        JOIN danserial ds ON pb.maSerial = ds.maSerial
        JOIN maudan md ON ds.maMau = md.maMau
        JOIN nhanvien nv ON pb.maNhanVienLap = nv.maNhanVien ";
if ($role_id != 1) {
    $sql_history .= " WHERE pb.maNhanVienLap = $user_id ";
}
$sql_history .= " ORDER BY pb.ngayTiepNhan DESC LIMIT 50";
$history_result = $conn->query($sql_history);

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



<div class="main-wrapper">
    <?php include 'includes/topbar.php'; ?>

    <div class="content">
        <div class="page-content-wrapper">
            <?php if (isset($_SESSION['flash_success'])): ?>
                <div class="alert alert-success" style="background: rgba(16, 185, 129, 0.1); color: #10b981; border: 1px solid rgba(16, 185, 129, 0.3); padding:15px; border-radius:12px; margin-bottom:24px;">
                    <span class="material-symbols-rounded">check_circle</span> <?php echo $_SESSION['flash_success']; unset($_SESSION['flash_success']); ?>
                </div>
            <?php endif; ?>
            
            <div class="nav-tabs">
                <div class="nav-tab active" onclick="switchTab('new')">
                    <span class="material-symbols-rounded">add_circle</span> Lập phiếu bảo trì mới
                </div>
                <div class="nav-tab" onclick="switchTab('history')">
                    <span class="material-symbols-rounded">history</span> Lịch sử lập phiếu
                </div>
            </div>

            <div class="tab-pane active" id="tab-new">
            <div class="form-card">
                <h2 class="card-title"><span class="material-symbols-rounded">support_agent</span> Lập Phiếu Tiếp Nhận Bảo Trì</h2>
                
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
            </div> <!-- End tab-new -->

            <div class="tab-pane" id="tab-history">
                <div class="form-card">
                    <div class="card-title" style="border-bottom:none; margin-bottom: 20px;">
                        <span class="material-symbols-rounded">history</span> Lịch sử tiếp nhận bảo trì
                    </div>
                    <?php if ($history_result && $history_result->num_rows > 0): ?>
                    <table class="history-table">
                        <thead>
                            <tr>
                                <th>Mã Phiếu</th>
                                <th>Thời gian</th>
                                <th>Khách hàng</th>
                                <th>Sản phẩm (Serial)</th>
                                <th>Người lập</th>
                                <th>Trạng thái</th>
                                <th>Thao tác</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while($row = $history_result->fetch_assoc()): 
                                $statusClass = 'badge-info';
                                if(strpos($row['trangThai'], 'Chờ duyệt') !== false) $statusClass = 'badge-warning';
                                elseif($row['trangThai'] == 'Đã xuất hãng') $statusClass = 'badge-primary';
                                elseif($row['trangThai'] == 'Hoàn thành') $statusClass = 'badge-success';
                                elseif($row['trangThai'] == 'Đã hủy') $statusClass = 'badge-danger';
                            ?>
                            <tr>
                                <td><strong>#<?= $row['maPhieuBT'] ?></strong></td>
                                <td><?= date('d/m/Y H:i', strtotime($row['ngayTiepNhan'])) ?></td>
                                <td><?= htmlspecialchars($row['tenKH'] ?? 'N/A') ?></td>
                                <td><?= htmlspecialchars($row['tenMau']) ?> (<?= htmlspecialchars($row['soSerial']) ?>)</td>
                                <td><?= htmlspecialchars($row['tenNVLap'] ?? 'N/A') ?></td>
                                <td><span class="badge-pill <?= $statusClass ?>"><?= $row['trangThai'] ?></span></td>
                                <td>
                                    <?php if ($row['trangThai'] == 'Chờ duyệt tiếp nhận'): ?>
                                        <a href="sua_baotri.php?id=<?= $row['maPhieuBT'] ?>" class="btn btn-secondary"><span class="material-symbols-rounded" style="font-size:16px;">edit</span> Sửa</a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                    <?php else: ?>
                        <div style="text-align: center; padding: 40px; color: var(--text-muted);">
                            <span class="material-symbols-rounded" style="font-size: 48px; opacity: 0.5;">inbox</span>
                            <p>Chưa có lịch sử lập phiếu bảo trì nào.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div> <!-- End tab-history -->
        </div>
    </div>
</div>

<script>
function switchTab(tabId) {
    document.querySelectorAll('.nav-tab').forEach(t => t.classList.remove('active'));
    document.querySelectorAll('.tab-pane').forEach(p => p.classList.remove('active'));
    
    document.querySelector('.nav-tab[onclick*="' + tabId + '"]').classList.add('active');
    document.getElementById('tab-' + tabId).classList.add('active');
}

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
