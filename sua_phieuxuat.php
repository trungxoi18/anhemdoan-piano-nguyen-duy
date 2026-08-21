<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'db.php';
require_once 'functions.php';

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role_id'], [1, 2, 3])) {
    header("Location: index.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$msg = "";

if ($id <= 0) {
    header("Location: phieuxuat.php");
    exit();
}

$sql_check = "SELECT * FROM phieuxuat WHERE maPhieuXuat = $id";
$res_check = $conn->query($sql_check);
if ($res_check->num_rows == 0) {
    header("Location: phieuxuat.php");
    exit();
}
$phieu = $res_check->fetch_assoc();

if ($phieu['trangThai'] != 'Chờ duyệt') {
    die("Phiếu này không thể sửa vì đã không còn ở trạng thái Chờ duyệt.");
}

if ($_SESSION['role_id'] != 1 && $phieu['maNhanVien'] != $user_id) {
    die("Bạn không có quyền sửa phiếu của người khác.");
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['btnLuuPhieuXuat'])) {
    $lyDoXuat = trim($_POST['lyDoXuat']);
    $maHoaDon = !empty($_POST['maHoaDon']) ? $_POST['maHoaDon'] : NULL;
    $nguoiNhanHang = trim($_POST['nguoiNhanHang']);
    $soChungTu = trim($_POST['soChungTu']);
    $ngayChungTu = !empty($_POST['ngayChungTu']) ? $_POST['ngayChungTu'] : NULL;
    $donViNhan = trim($_POST['donViNhan']);
    
    $list_serial = $_POST['soSerial'] ?? [];

    $conn->begin_transaction();
    try {
        // Cập nhật thông tin chung phiếu xuất
        $sql_update = "UPDATE phieuxuat SET lyDoXuat=?, maKho=NULL, maHoaDon=?, nguoiNhanHang=?, donViNhan=?, soChungTu=?, ngayChungTu=? WHERE maPhieuXuat=?";
        $stmt_update = $conn->prepare($sql_update);
        $stmt_update->bind_param("sissssi", $lyDoXuat, $maHoaDon, $nguoiNhanHang, $donViNhan, $soChungTu, $ngayChungTu, $id);
        $stmt_update->execute();

        // Hoàn trả trạng thái các serial cũ
        $conn->query("UPDATE danserial ds JOIN chitietphieuxuat ct ON ds.maSerial = ct.maSerial SET ds.trangThai = 'Trong kho' WHERE ct.maPhieuXuat = $id");
        $conn->query("DELETE FROM chitietphieuxuat WHERE maPhieuXuat = $id");
        
        // Nếu hóa đơn cũ đổi, đưa nó về Chờ giao (Nếu cần, ở đây bỏ qua cho đơn giản hoặc check kỹ hơn, tạm bỏ qua việc đổi hóa đơn hoặc coi như Hóa đơn cũ về Chờ giao)
        if ($phieu['maHoaDon'] && $phieu['maHoaDon'] != $maHoaDon) {
            $conn->query("UPDATE hoadon SET trangThai = 'Chờ giao' WHERE maHoaDon = {$phieu['maHoaDon']}");
        }

        $list_serial = $_POST['soSerial'] ?? [];
        $list_makho = $_POST['maKhoList'] ?? [];
        $soLuongXuat = 0;
        
        foreach ($list_serial as $index => $serial) {
            $serial = trim($serial);
            if (empty($serial)) continue;

            $maKhoSelected = intval($list_makho[$index]);

            $st_kho = $conn->prepare("SELECT maSerial, trangThai FROM danserial WHERE soSerial = ? AND maKho = ?");
            $st_kho->bind_param("si", $serial, $maKhoSelected);
            $st_kho->execute();
            $res_kho = $st_kho->get_result();
            if ($res_kho->num_rows == 0) {
                // Check where it actually is to show a better error
                $st_any = $conn->prepare("SELECT k.tenKho FROM danserial ds JOIN kho k ON ds.maKho = k.maKho WHERE ds.soSerial = ?");
                $st_any->bind_param("s", $serial);
                $st_any->execute();
                $res_any = $st_any->get_result();
                if ($res_any->num_rows > 0) {
                    $r_any = $res_any->fetch_assoc();
                    throw new Exception("Mã Serial [$serial] không thuộc kho đã chọn! (Đang ở: {$r_any['tenKho']})");
                }
                throw new Exception("Mã Serial [$serial] không tồn tại trong hệ thống!");
            }
            $row_kho = $res_kho->fetch_assoc();

            if ($maHoaDon && !in_array($row_kho['trangThai'], ['Trong kho', 'Chờ giao'])) {
                throw new Exception("Mã Serial [$serial] không thuộc hóa đơn hoặc không sẵn sàng!");
            } elseif (!$maHoaDon && $row_kho['trangThai'] !== 'Trong kho') {
                throw new Exception("Mã Serial [$serial] hiện không sẵn sàng để xuất!");
            }
            
            $maSerial = $row_kho['maSerial'];
            $conn->query("INSERT INTO chitietphieuxuat (maPhieuXuat, maSerial) VALUES ($id, $maSerial)");
            $conn->query("UPDATE danserial SET trangThai = 'Chờ xuất' WHERE maSerial = $maSerial");
            $soLuongXuat++;
        }

        if ($soLuongXuat == 0) {
            throw new Exception("Phiếu xuất phải có ít nhất 1 sản phẩm hợp lệ!");
        }

        if ($maHoaDon) {
            $conn->query("UPDATE hoadon SET trangThai = 'Đang giao' WHERE maHoaDon = $maHoaDon");
        }
        
        $conn->commit();
        writeLog($conn, 'XUẤT KHO', "Đã SỬA phiếu xuất kho #$id - Xuất $soLuongXuat sản phẩm");

        $_SESSION['flash_success'] = "Đã cập nhật Phiếu xuất #$id thành công!";
        header("Location: phieuxuat.php");
        exit();

    } catch (Exception $e) {
        $conn->rollback();
        $msg = "<div class='alert-msg alert-error'><span class='material-symbols-rounded'>error</span><div><strong>Lỗi xử lý!</strong><br>" . $e->getMessage() . "</div></div>";
    }
}

// Dữ liệu cho form
$hoadons = $conn->query("SELECT maHoaDon, ngayLap FROM hoadon WHERE trangThai = 'Chờ giao' OR maHoaDon = " . ($phieu['maHoaDon'] ? $phieu['maHoaDon'] : 0));
$khos = $conn->query("SELECT * FROM kho");
$khos_data = [];
while($k = $khos->fetch_assoc()) {
    $khos_data[] = $k;
}
$kho_options_html = "";
foreach($khos_data as $k) {
    $kho_options_html .= "<option value='{$k['maKho']}'>".htmlspecialchars($k['tenKho'])."</option>";
}

// Chi tiết phiếu
$chitiet_sql = "SELECT ct.*, ds.soSerial, ds.maKho FROM chitietphieuxuat ct JOIN danserial ds ON ct.maSerial = ds.maSerial WHERE ct.maPhieuXuat = $id";
$chitiet_result = $conn->query($chitiet_sql);
$chitiets = [];
while ($row = $chitiet_result->fetch_assoc()) {
    $chitiets[] = $row;
}
?>

<?php include 'includes/header.php'; ?>
<?php include 'includes/sidebar.php'; ?>


<div class="main-wrapper">
    <?php include 'includes/topbar.php'; ?>
    <div class="content">
        <div class="form-center-container">
            <div class="welcome-banner-export">
                <h1>Sửa Phiếu Xuất Kho #<?= $id ?></h1>
            </div>

            <?php echo $msg; ?>

            <form method="POST" onsubmit="return confirm('Xác nhận lưu thay đổi?');">
                <div class="form-card">
                    <div class="card-title"><span class="material-symbols-rounded" style="color: var(--accent-secondary);">route</span> 1. Thông tin điều hướng xuất kho</div>
                    <div class="input-grid">
                        <div class="form-group">
                            <label>Liên kết Hóa đơn (Sales):</label>
                            <select name="maHoaDon" class="custom-input">
                                <option value="">-- Xuất nội bộ / Lý do khác --</option>
                                <?php while($hd = $hoadons->fetch_assoc()): ?>
                                    <option value="<?= $hd['maHoaDon'] ?>" <?= ($phieu['maHoaDon'] == $hd['maHoaDon']) ? 'selected' : '' ?>>Đơn #<?= $hd['maHoaDon'] ?> - Ngày <?= date('d/m/Y', strtotime($hd['ngayLap'])) ?></option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <div class="form-group" style="display: none;">
                            <label>Kho xuất:</label>
                            <input type="text" class="custom-input" value="Tự động theo sản phẩm" disabled>
                        </div>
                        <div class="form-group">
                            <label>Lý do xuất:</label>
                            <input type="text" name="lyDoXuat" class="custom-input" value="<?= htmlspecialchars($phieu['lyDoXuat']) ?>" required>
                        </div>
                    </div>
                    <div class="input-grid" style="margin-top: 16px;">
                        <div class="form-group">
                            <label><span class="material-symbols-rounded">person</span> Họ tên người nhận hàng</label>
                            <input type="text" name="nguoiNhanHang" class="custom-input" value="<?= htmlspecialchars($phieu['nguoiNhanHang']) ?>">
                        </div>
                        <div class="form-group">
                            <label><span class="material-symbols-rounded">apartment</span> Đơn vị nhận (Của)</label>
                            <input type="text" name="donViNhan" class="custom-input" value="<?= htmlspecialchars($phieu['donViNhan']) ?>">
                        </div>
                    </div>
                </div>

                <div class="form-card">
                    <div class="card-title"><span class="material-symbols-rounded" style="color: var(--accent-secondary);">receipt_long</span> 2. Chứng từ kèm theo (Nếu có)</div>
                    <div class="input-grid">
                        <div class="form-group">
                            <label>Số chứng từ:</label>
                            <input type="text" name="soChungTu" class="custom-input" value="<?= htmlspecialchars($phieu['soChungTu']) ?>">
                        </div>
                        <div class="form-group">
                            <label>Ngày chứng từ:</label>
                            <input type="date" name="ngayChungTu" class="custom-input" value="<?= $phieu['ngayChungTu'] ?>">
                        </div>
                    </div>
                </div>

                <div class="form-card">
                    <div class="card-title"><span class="material-symbols-rounded" style="color: var(--accent-secondary);">barcode_scanner</span> 3. Chọn mã Serial xuất kho</div>
                    <div id="serial-container">
                        <?php foreach($chitiets as $idx => $ct): ?>
                        <div class="serial-row" style="display: flex; gap: 10px; align-items: center;">
                            <input type="text" name="soSerial[]" class="custom-input" value="<?= htmlspecialchars($ct['soSerial']) ?>" style="flex: 2;" required>
                            <select name="maKhoList[]" class="custom-input" style="flex: 1;" required>
                                <option value="">-- Chọn Kho --</option>
                                <?php foreach($khos_data as $k): ?>
                                    <option value="<?= $k['maKho'] ?>" <?= ($ct['maKho'] == $k['maKho']) ? 'selected' : '' ?>><?= htmlspecialchars($k['tenKho']) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <button type="button" class="btn-action btn-remove" onclick="removeSerialRow(this)">
                                <span class="material-symbols-rounded">delete</span>
                            </button>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <button type="button" class="btn-action btn-add" onclick="addSerialRow()" style="margin-top: 16px;">
                        <span class="material-symbols-rounded">add</span> Thêm mã Serial
                    </button>
                </div>

                <div style="display: flex; justify-content: space-between; padding-bottom: 50px;">
                    <a href="phieuxuat.php" class="btn-action" style="background: rgba(255,255,255,0.05); color: var(--text-secondary); text-decoration: none;">Quay lại</a>
                    <button type="submit" name="btnLuuPhieuXuat" class="btn-action btn-submit">
                        <span class="material-symbols-rounded">save</span> LƯU THAY ĐỔI
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
    newRow.style.display = 'flex';
    newRow.style.gap = '10px';
    newRow.style.alignItems = 'center';
    
    const khoOptions = `<?= $kho_options_html ?>`;
    
    newRow.innerHTML = `
        <input type="text" name="soSerial[]" class="custom-input" placeholder="Nhập hoặc quét mã Serial..." style="flex: 2;" required>
        <select name="maKhoList[]" class="custom-input" style="flex: 1;" required>
            <option value="">-- Chọn Kho --</option>
            ${khoOptions}
        </select>
        <button type="button" class="btn-action btn-remove" onclick="removeSerialRow(this)" title="Xóa"><span class="material-symbols-rounded">delete</span></button>
    `;
    container.appendChild(newRow);
}

function removeSerialRow(btn) {
    btn.parentElement.remove();
}
</script>
<?php include 'includes/footer.php'; ?>
