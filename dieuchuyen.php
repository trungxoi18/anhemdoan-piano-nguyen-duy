<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'db.php';
require_once 'functions.php';

// Kiểm tra quyền (Admin và Thủ kho)
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role_id'], [1, 3])) {
    header("Location: index.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$role_id = $_SESSION['role_id'];
$msg = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['btnLuuPhieuDC'])) {
    $maKhoXuat = $_POST['maKhoXuat'];
    $maKhoNhap = $_POST['maKhoNhap'];
    $ghiChu = $_POST['ghiChu'];
    $list_serial = $_POST['soSerial'];

    if ($maKhoXuat == $maKhoNhap) {
        $msg = "<div class='alert alert-danger'>Lỗi: Kho xuất và Kho nhập không được trùng nhau!</div>";
    } else {
        $conn->begin_transaction();
        try {
            $sql_phieu = "INSERT INTO phieudieuchuyen (maKhoXuat, maKhoNhap, maNhanVienLap, ghiChu, trangThai) VALUES (?, ?, ?, ?, 'Chờ duyệt')";
            $stmt = $conn->prepare($sql_phieu);
            $stmt->bind_param("iiis", $maKhoXuat, $maKhoNhap, $user_id, $ghiChu);
            $stmt->execute();
            $maPhieuDC = $conn->insert_id;

            $soLuong = 0;
            foreach ($list_serial as $serial) {
                $serial = trim($serial);
                if(empty($serial)) continue;
                
                $st_check = $conn->prepare("SELECT maSerial, trangThai FROM danserial WHERE soSerial = ? AND maKho = ?");
                $st_check->bind_param("si", $serial, $maKhoXuat);
                $st_check->execute();
                $res = $st_check->get_result();
                
                if ($res->num_rows == 0) throw new Exception("Mã Serial [$serial] không có trong Kho Xuất!");
                
                $row = $res->fetch_assoc();
                $maSerial = $row['maSerial'];
                if ($row['trangThai'] != 'Trong kho') {
                    throw new Exception("Mã Serial [$serial] đang ở trạng thái '".$row['trangThai']."', không thể điều chuyển!");
                }
                
                $conn->query("INSERT INTO chitietdieuchuyen (maPhieuDC, maSerial) VALUES ($maPhieuDC, $maSerial)");
                
                // Cập nhật trạng thái đàn sang Đang điều chuyển
                $conn->query("UPDATE danserial SET trangThai = 'Đang điều chuyển' WHERE maSerial = $maSerial");
                $soLuong++;
            }

            if ($soLuong == 0) {
                throw new Exception("Phiếu điều chuyển phải có ít nhất 1 sản phẩm!");
            }

            $conn->commit();
            
            writeLog($conn, 'ĐIỀU CHUYỂN', "Đã lập phiếu điều chuyển #$maPhieuDC - Chuyển $soLuong sản phẩm");

            // Thông báo
            $user_link = ($_SESSION['role_id'] == 1) ? 'duyet_phieu.php' : 'dieuchuyen.php';
            $msg_dc = "Lập phiếu điều chuyển thành công (Chờ duyệt)! Phiếu #$maPhieuDC - $soLuong SP";
            $conn->query("INSERT INTO ThongBao (maTaiKhoan, noiDung, link) VALUES ($user_id, '$msg_dc', '$user_link')");

            $msg_admin = "Có phiếu điều chuyển mới #$maPhieuDC cần phê duyệt ($soLuong SP)";
            $conn->query("INSERT INTO ThongBao (maVaiTro, noiDung, link) VALUES (1, '$msg_admin', 'duyet_phieu.php')");

            $_SESSION['flash_success'] = "Lập phiếu điều chuyển thành công! Phiếu #$maPhieuDC đang chờ phê duyệt.";
            header("Location: dieuchuyen.php");
            exit();
        } catch (Exception $e) {
            $conn->rollback();
            $msg = "<div class='alert alert-danger' style='background: var(--danger-bg); color: var(--danger); border: 1px solid rgba(248,113,113,0.3); padding:15px; border-radius:12px; margin-bottom:24px;'><span class='material-symbols-rounded'>error</span> Lỗi: " . $e->getMessage() . "</div>";
        }
    }
}

$khos = $conn->query("SELECT * FROM kho");
$khos_arr = $khos->fetch_all(MYSQLI_ASSOC);

// Lấy lịch sử điều chuyển
$sql_history = "SELECT p.*, kx.tenKho as khoXuat, kn.tenKho as khoNhap, nv.hoTen,
                (SELECT COUNT(*) FROM chitietdieuchuyen ct WHERE ct.maPhieuDC = p.maPhieuDC) as soLuong 
                FROM phieudieuchuyen p 
                LEFT JOIN kho kx ON p.maKhoXuat = kx.maKho
                LEFT JOIN kho kn ON p.maKhoNhap = kn.maKho
                LEFT JOIN nhanvien nv ON p.maNhanVienLap = nv.maNhanVien ";
if ($_SESSION['role_id'] != 1) {
    $sql_history .= " WHERE p.maNhanVienLap = $user_id ";
}
$sql_history .= " ORDER BY p.ngayTao DESC LIMIT 50";
$history_result = $conn->query($sql_history);
?>

<?php include 'includes/header.php'; ?>
<?php include 'includes/sidebar.php'; ?>


<div class="main-wrapper">
    <?php include 'includes/topbar.php'; ?>

    <div class="content">
        <div class="form-center-container">
            <div class="welcome-banner-transfer">
                <h1><span class="material-symbols-rounded">local_shipping</span> Lập Phiếu Điều Chuyển Kho</h1>
                <p>Chuyển hàng hóa từ kho này sang kho khác.</p>
            </div>

            <?php echo $msg; ?>
            <?php if (isset($_SESSION['flash_success'])): ?>
                <div class="alert alert-success" style="background: rgba(16, 185, 129, 0.1); color: #10b981; border: 1px solid rgba(16, 185, 129, 0.3); padding:15px; border-radius:12px; margin-bottom:24px;">
                    <span class="material-symbols-rounded">check_circle</span> <?php echo $_SESSION['flash_success']; unset($_SESSION['flash_success']); ?>
                </div>
            <?php endif; ?>

            <div class="nav-tabs">
                <div class="nav-tab active" onclick="switchTab('new')">
                    <span class="material-symbols-rounded">add_circle</span> Lập phiếu mới
                </div>
                <div class="nav-tab" onclick="switchTab('history')">
                    <span class="material-symbols-rounded">history</span> Lịch sử lập phiếu
                </div>
            </div>

            <div class="tab-pane active" id="tab-new">
                <form method="POST" onsubmit="return confirm('Xác nhận lập phiếu điều chuyển?');">
                    <div class="form-card">
                    <div class="card-title"><span class="material-symbols-rounded" style="color: #f59e0b;">route</span> 1. Thông tin Kho</div>
                    <div class="input-grid">
                        <div class="form-group">
                            <label>Từ Kho (Kho Xuất):</label>
                            <select name="maKhoXuat" class="custom-input" required>
                                <?php foreach($khos_arr as $k) echo "<option value='{$k['maKho']}'>{$k['tenKho']}</option>"; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Đến Kho (Kho Nhập):</label>
                            <select name="maKhoNhap" class="custom-input" required>
                                <?php foreach($khos_arr as $k) echo "<option value='{$k['maKho']}'>{$k['tenKho']}</option>"; ?>
                            </select>
                        </div>
                    </div>
                    <div class="form-group" style="margin-top: 16px;">
                        <label>Ghi chú / Lý do điều chuyển:</label>
                        <input type="text" name="ghiChu" class="custom-input" placeholder="VD: Bổ sung hàng hóa cho chi nhánh...">
                    </div>
                </div>

                <div class="form-card">
                    <div class="card-title"><span class="material-symbols-rounded" style="color: #f59e0b;">qr_code_scanner</span> 2. Danh sách Mã đàn xuất (Serial)</div>
                    <div id="serial-container">
                        <div class="serial-row">
                            <input type="text" name="soSerial[]" class="custom-input" placeholder="Nhập/Quét mã Serial" required>
                            <button type="button" class="btn-action btn-add" onclick="addSerialRow()">
                                <span class="material-symbols-rounded">add</span>
                            </button>
                        </div>
                    </div>
                    <button type="button" class="btn-action btn-add" onclick="addSerialRow()" style="margin-top: 16px;">
                        <span class="material-symbols-rounded">add</span> Thêm mã Serial
                    </button>
                </div>

                <div style="text-align: right; padding-bottom: 50px;">
                    <button type="submit" name="btnLuuPhieuDC" class="btn-action btn-submit">
                        <span class="material-symbols-rounded">send</span> GỬI YÊU CẦU ĐIỀU CHUYỂN
                    </button>
                </div>
            </form>
            </div> <!-- End tab-new -->

            <!-- TAB LỊCH SỬ -->
            <div class="tab-pane" id="tab-history">
                <div class="form-card">
                    <div class="card-title">
                        <span class="material-symbols-rounded">history</span> 
                        Lịch sử lập phiếu điều chuyển
                    </div>
                    <?php if ($history_result && $history_result->num_rows > 0): ?>
                    <table class="history-table">
                        <thead>
                            <tr>
                                <th>Mã Phiếu</th>
                                <th>Thời gian</th>
                                <th>Từ Kho</th>
                                <th>Đến Kho</th>
                                <th>Lý do</th>
                                <th>Sản phẩm</th>
                                <th>Người lập</th>
                                <th>Trạng thái</th>
                                <th>Thao tác</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while($row = $history_result->fetch_assoc()): ?>
                            <tr>
                                <td><strong>#<?= $row['maPhieuDC'] ?></strong></td>
                                <td><?= date('d/m/Y H:i', strtotime($row['ngayLap'])) ?></td>
                                <td><?= htmlspecialchars($row['khoXuat'] ?? 'N/A') ?></td>
                                <td><?= htmlspecialchars($row['khoNhap'] ?? 'N/A') ?></td>
                                <td><?= htmlspecialchars($row['ghiChu'] ?? 'N/A') ?></td>
                                <td><?= $row['soLuong'] ?> SP</td>
                                <td><?= htmlspecialchars($row['hoTen'] ?? 'N/A') ?></td>
                                <td><span class="status-badge <?= str_replace(' ', '.', $row['trangThai']) ?>"><?= $row['trangThai'] ?></span></td>
                                <td>
                                    <?php if ($row['trangThai'] == 'Chờ duyệt'): ?>
                                        <a href="sua_dieuchuyen.php?id=<?= $row['maPhieuDC'] ?>" class="btn-action" style="padding: 6px 12px; background: rgba(245, 158, 11, 0.1); color: #f59e0b; font-size: 13px;">Sửa phiếu</a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                    <?php else: ?>
                        <div style="text-align: center; padding: 40px; color: var(--text-muted);">
                            <span class="material-symbols-rounded" style="font-size: 48px; opacity: 0.5;">inbox</span>
                            <p>Chưa có lịch sử lập phiếu điều chuyển nào.</p>
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

function addSerialRow() {
    const container = document.getElementById('serial-container');
    const newRow = document.createElement('div');
    newRow.className = 'serial-row';
    newRow.innerHTML = `
        <div style="flex: 1;">
            <input type="text" name="soSerial[]" class="custom-input" placeholder="Nhập hoặc quét mã Serial..." required>
        </div>
        <button type="button" class="btn-action btn-remove" onclick="this.parentElement.remove()" title="Xóa"><span class="material-symbols-rounded">delete</span></button>
    `;
    container.appendChild(newRow);
}
</script>

<?php include 'includes/footer.php'; ?>
