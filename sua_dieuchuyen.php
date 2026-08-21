<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'db.php';
require_once 'functions.php';

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role_id'], [1, 3])) {
    header("Location: index.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$msg = "";

if ($id <= 0) {
    header("Location: dieuchuyen.php");
    exit();
}

$sql_check = "SELECT * FROM phieudieuchuyen WHERE maPhieuDC = $id";
$res_check = $conn->query($sql_check);
if ($res_check->num_rows == 0) {
    header("Location: dieuchuyen.php");
    exit();
}
$phieu = $res_check->fetch_assoc();

if ($phieu['trangThai'] != 'Chờ duyệt') {
    die("Phiếu này không thể sửa vì đã không còn ở trạng thái Chờ duyệt.");
}

if ($_SESSION['role_id'] != 1 && $phieu['maNhanVienLap'] != $user_id) {
    die("Bạn không có quyền sửa phiếu của người khác.");
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['btnLuuPhieuDC'])) {
    $maKhoXuat = $_POST['maKhoXuat'];
    $maKhoNhap = $_POST['maKhoNhap'];
    $ghiChu = $_POST['ghiChu'];
    $list_serial = $_POST['soSerial'] ?? [];

    if ($maKhoXuat == $maKhoNhap) {
        $msg = "<div class='alert-msg alert-error'><span class='material-symbols-rounded'>error</span> Lỗi: Kho xuất và Kho nhập không được trùng nhau!</div>";
    } else {
        $conn->begin_transaction();
        try {
            // Cập nhật thông tin phiếu điều chuyển
            $sql_update = "UPDATE phieudieuchuyen SET maKhoXuat=?, maKhoNhap=?, ghiChu=? WHERE maPhieuDC=?";
            $stmt_update = $conn->prepare($sql_update);
            $stmt_update->bind_param("iisi", $maKhoXuat, $maKhoNhap, $ghiChu, $id);
            $stmt_update->execute();

            // Hoàn trả trạng thái các serial cũ
            $conn->query("UPDATE danserial ds JOIN chitietdieuchuyen ct ON ds.maSerial = ct.maSerial SET ds.trangThai = 'Trong kho' WHERE ct.maPhieuDC = $id");
            $conn->query("DELETE FROM chitietdieuchuyen WHERE maPhieuDC = $id");
            
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
                
                $conn->query("INSERT INTO chitietdieuchuyen (maPhieuDC, maSerial) VALUES ($id, $maSerial)");
                $conn->query("UPDATE danserial SET trangThai = 'Đang điều chuyển' WHERE maSerial = $maSerial");
                $soLuong++;
            }

            if ($soLuong == 0) {
                throw new Exception("Phiếu điều chuyển phải có ít nhất 1 sản phẩm!");
            }

            $conn->commit();
            writeLog($conn, 'ĐIỀU CHUYỂN', "Đã SỬA phiếu điều chuyển #$id - Chuyển $soLuong sản phẩm");

            $_SESSION['flash_success'] = "Đã cập nhật Phiếu điều chuyển #$id thành công!";
            header("Location: dieuchuyen.php");
            exit();

        } catch (Exception $e) {
            $conn->rollback();
            $msg = "<div class='alert-msg alert-error'><span class='material-symbols-rounded'>error</span> Lỗi: " . $e->getMessage() . "</div>";
        }
    }
}

// Dữ liệu dropdown
$khos = $conn->query("SELECT * FROM kho");
$khos_arr = $khos->fetch_all(MYSQLI_ASSOC);

// Chi tiết phiếu
$chitiet_sql = "SELECT ct.*, ds.soSerial FROM chitietdieuchuyen ct JOIN danserial ds ON ct.maSerial = ds.maSerial WHERE ct.maPhieuDC = $id";
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
            <div class="welcome-banner-transfer">
                <h1><span class="material-symbols-rounded">local_shipping</span> Sửa Phiếu Điều Chuyển #<?= $id ?></h1>
            </div>

            <?php echo $msg; ?>

            <form method="POST" onsubmit="return confirm('Xác nhận lưu thay đổi phiếu điều chuyển?');">
                <div class="form-card">
                    <div class="card-title"><span class="material-symbols-rounded" style="color: #f59e0b;">route</span> 1. Thông tin Kho</div>
                    <div class="input-grid">
                        <div class="form-group">
                            <label>Từ Kho (Kho Xuất):</label>
                            <select name="maKhoXuat" class="custom-input" required>
                                <?php foreach($khos_arr as $k): ?>
                                    <option value="<?= $k['maKho'] ?>" <?= $phieu['maKhoXuat'] == $k['maKho'] ? 'selected' : '' ?>><?= htmlspecialchars($k['tenKho']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Đến Kho (Kho Nhập):</label>
                            <select name="maKhoNhap" class="custom-input" required>
                                <?php foreach($khos_arr as $k): ?>
                                    <option value="<?= $k['maKho'] ?>" <?= $phieu['maKhoNhap'] == $k['maKho'] ? 'selected' : '' ?>><?= htmlspecialchars($k['tenKho']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="form-group" style="margin-top: 16px;">
                        <label>Ghi chú / Lý do điều chuyển:</label>
                        <input type="text" name="ghiChu" class="custom-input" value="<?= htmlspecialchars($phieu['ghiChu']) ?>">
                    </div>
                </div>

                <div class="form-card">
                    <div class="card-title"><span class="material-symbols-rounded" style="color: #f59e0b;">qr_code_scanner</span> 2. Danh sách Mã đàn xuất (Serial)</div>
                    <div id="serial-container">
                        <?php foreach($chitiets as $idx => $ct): ?>
                        <div class="serial-row">
                            <div style="flex: 1;">
                                <input type="text" name="soSerial[]" class="custom-input" value="<?= htmlspecialchars($ct['soSerial']) ?>" required>
                            </div>
                            <button type="button" class="btn-action btn-remove" onclick="this.parentElement.remove()" title="Xóa">
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
                    <a href="dieuchuyen.php" class="btn-action" style="background: rgba(255,255,255,0.05); color: var(--text-secondary); text-decoration: none;">Quay lại</a>
                    <button type="submit" name="btnLuuPhieuDC" class="btn-action btn-submit">
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
