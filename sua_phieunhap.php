<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'db.php';
require_once 'functions.php';

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role_id'], [1, 2])) {
    header("Location: index.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$msg = "";

if ($id <= 0) {
    header("Location: phieunhap.php");
    exit();
}

// Kiểm tra quyền và trạng thái phiếu
$sql_check = "SELECT * FROM phieunhap WHERE maPhieuNhap = $id";
$res_check = $conn->query($sql_check);
if ($res_check->num_rows == 0) {
    header("Location: phieunhap.php");
    exit();
}
$phieu = $res_check->fetch_assoc();

if ($phieu['trangThai'] != 'Chờ duyệt') {
    die("Phiếu này không thể sửa vì đã không còn ở trạng thái Chờ duyệt.");
}

// Nếu không phải admin, chỉ được sửa phiếu của chính mình
if ($_SESSION['role_id'] != 1 && $phieu['maNhanVien'] != $user_id) {
    die("Bạn không có quyền sửa phiếu của người khác.");
}

// === XỬ LÝ LƯU SỬA PHIẾU ===
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

        // Cập nhật thông tin phiếu
        $sql_update = "UPDATE phieunhap SET maNCC=?, soHoaDonNCC=?, nguoiGiaoHang=?, ngayHoaDon=?, ghiChu=?, maKho=? WHERE maPhieuNhap=?";
        $stmt_update = $conn->prepare($sql_update);
        $stmt_update->bind_param("issssii", $maNCC, $soHoaDonNCC, $nguoiGiaoHang, $ngayHoaDon, $ghiChu, $maKho, $id);
        $stmt_update->execute();

        // 1. Xóa tất cả serial cũ của phiếu này
        // Lấy danh sách maSerial trước
        $st_get = $conn->query("SELECT maSerial FROM chitietphieunhap WHERE maPhieuNhap = $id");
        $old_serials = [];
        while($r = $st_get->fetch_assoc()) {
            $old_serials[] = $r['maSerial'];
        }
        
        // Xóa chi tiết phiếu nhập (để gỡ khóa ngoại)
        $conn->query("DELETE FROM chitietphieunhap WHERE maPhieuNhap = $id");
        
        // Xóa từ bảng danserial
        if (!empty($old_serials)) {
            $ids_str = implode(',', $old_serials);
            $conn->query("DELETE FROM danserial WHERE maSerial IN ($ids_str)");
        }

        $tongTien = 0;
        $soLuongThucTe = 0;

        // 2. Thêm mới lại
        for ($i = 0; $i < count($list_serial); $i++) {
            $maMau = $list_mamau[$i]; 
            $serial = trim($list_serial[$i]);
            $giaNhap = floatval(str_replace(',', '', $list_gianhap[$i])); 
            
            if ($giaNhap < 0) {
                throw new Exception("Giá nhập không được nhỏ hơn 0!");
            }
            
            if (empty($serial)) continue;

            // Kiểm tra Serial trùng toàn hệ thống
            $st_check = $conn->prepare("SELECT maSerial FROM danserial WHERE soSerial = ?");
            $st_check->bind_param("s", $serial);
            $st_check->execute();
            if ($st_check->get_result()->num_rows > 0) {
                throw new Exception("Mã Serial [$serial] đã tồn tại trong hệ thống!");
            }

            // Thêm serial
            $st_insert_dan = $conn->prepare("INSERT INTO danserial (soSerial, maMau, maKho, maNCC, tinhTrang, trangThai, giaNhap) VALUES (?, ?, ?, ?, 'Mới', 'Chờ nhập', ?)");
            $st_insert_dan->bind_param("siiid", $serial, $maMau, $maKho, $maNCC, $giaNhap);
            $st_insert_dan->execute();
            $maSerial = $conn->insert_id;

            // Thêm vào chi tiết phiếu
            $conn->query("INSERT INTO chitietphieunhap (maPhieuNhap, maSerial, giaNhap) VALUES ($id, $maSerial, $giaNhap)");

            $tongTien += $giaNhap;
            $soLuongThucTe++;
        }

        // Cập nhật tổng tiền và số lượng thực tế
        $conn->query("UPDATE phieunhap SET tongTienNhap = $tongTien, soLuong = $soLuongThucTe WHERE maPhieuNhap = $id");

        $conn->commit();
        
        writeLog($conn, 'NHẬP KHO', "Đã SỬA phiếu nhập kho #$id ($soLuongThucTe sản phẩm, Tổng: " . number_format($tongTien, 0, ',', '.') . "đ)");

        $_SESSION['flash_success'] = "Đã cập nhật Phiếu nhập #$id thành công!";
        header("Location: phieunhap.php");
        exit();

    } catch (Exception $e) {
        $conn->rollback();
        $msg = "<div class='alert-msg alert-error'><span class='material-symbols-rounded'>error</span><div><strong>Lỗi xử lý!</strong><br>" . $e->getMessage() . "</div></div>";
    }
}

// Lấy danh sách chi tiết cũ
$chitiet_sql = "SELECT ct.*, ds.soSerial, ds.maMau FROM chitietphieunhap ct JOIN danserial ds ON ct.maSerial = ds.maSerial WHERE ct.maPhieuNhap = $id";
$chitiet_result = $conn->query($chitiet_sql);
$chitiets = [];
while ($row = $chitiet_result->fetch_assoc()) {
    $chitiets[] = $row;
}

// Dữ liệu dropdown
$nhacungcaps = $conn->query("SELECT * FROM nhacungcap ORDER BY tenNCC ASC");
$khos = $conn->query("SELECT * FROM kho ORDER BY tenKho ASC");
$maudans = $conn->query("SELECT maMau, tenMau FROM maudan ORDER BY tenMau ASC");
$maudans_arr = [];
while($d = $maudans->fetch_assoc()) {
    $maudans_arr[] = $d;
}
?>

<?php include 'includes/header.php'; ?>
<?php include 'includes/sidebar.php'; ?>


<div class="main-wrapper">
    <?php include 'includes/topbar.php'; ?>
    <div class="content">
        <div class="form-center-container">
            <div class="welcome-banner-import">
                <div class="banner-content">
                    <div class="banner-left">
                        <h1>✏️ Sửa Phiếu Nhập #<?= $id ?></h1>
                        <p>Chỉnh sửa nội dung phiếu trước khi được duyệt.</p>
                    </div>
                </div>
            </div>

            <?php echo $msg; ?>

            <form method="POST" id="formPhieuNhap" onsubmit="return validateAndSubmit();">
                <div class="form-card">
                    <div class="card-title">
                        <span class="material-symbols-rounded">description</span> Thông tin chứng từ nhập
                    </div>
                    <div class="input-grid">
                        <div class="form-group">
                            <label>Nhà cung cấp <span class="label-required">*</span></label>
                            <select name="maNCC" id="maNCC" class="custom-input" required>
                                <?php while($ncc = $nhacungcaps->fetch_assoc()): ?>
                                    <option value="<?= $ncc['maNCC'] ?>" <?= $phieu['maNCC'] == $ncc['maNCC'] ? 'selected' : '' ?>><?= htmlspecialchars($ncc['tenNCC']) ?></option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Nhập vào Kho <span class="label-required">*</span></label>
                            <select name="maKho" id="maKho" class="custom-input" required>
                                <?php while($k = $khos->fetch_assoc()): ?>
                                    <option value="<?= $k['maKho'] ?>" <?= $phieu['maKho'] == $k['maKho'] ? 'selected' : '' ?>><?= htmlspecialchars($k['tenKho']) ?></option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                    </div>
                    <div class="input-grid" style="margin-top: 20px;">
                        <div class="form-group">
                            <label>Họ tên người giao</label>
                            <input type="text" name="nguoiGiaoHang" class="custom-input" value="<?= htmlspecialchars($phieu['nguoiGiaoHang']) ?>">
                        </div>
                        <div class="form-group">
                            <label>Theo chứng từ số</label>
                            <input type="text" name="soHoaDonNCC" class="custom-input" value="<?= htmlspecialchars($phieu['soHoaDonNCC']) ?>">
                        </div>
                        <div class="form-group">
                            <label>Ngày chứng từ</label>
                            <input type="date" name="ngayHoaDon" class="custom-input" value="<?= $phieu['ngayHoaDon'] ?>">
                        </div>
                    </div>
                    <div style="margin-top: 20px;">
                        <div class="form-group">
                            <label>Lý do nhập kho</label>
                            <input type="text" name="ghiChu" class="custom-input" value="<?= htmlspecialchars($phieu['ghiChu']) ?>">
                        </div>
                    </div>
                </div>

                <div class="form-card">
                    <div class="card-title">
                        <span class="material-symbols-rounded">piano</span> Chi tiết đàn nhập kho
                    </div>
                    
                    <select id="product-template" style="display: none;">
                        <?php foreach($maudans_arr as $d): ?>
                            <option value="<?= $d['maMau'] ?>"><?= htmlspecialchars($d['tenMau']) ?></option>
                        <?php endforeach; ?>
                    </select>

                    <div id="product-container">
                        <?php foreach($chitiets as $idx => $ct): ?>
                        <div class="product-row" data-row="<?= $idx + 1 ?>">
                            <span class="row-number"><?= $idx + 1 ?></span>
                            <div>
                                <span class="product-row-label">Mẫu đàn (Model)</span>
                                <select name="maMau[]" class="custom-input" required>
                                    <?php foreach($maudans_arr as $d): ?>
                                        <option value="<?= $d['maMau'] ?>" <?= $ct['maMau'] == $d['maMau'] ? 'selected' : '' ?>><?= htmlspecialchars($d['tenMau']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-serial">
                                <span class="product-row-label">Số Serial</span>
                                <input type="text" name="soSerial[]" class="custom-input serial-input" value="<?= htmlspecialchars($ct['soSerial']) ?>" required>
                            </div>
                            <div class="col-price">
                                <span class="product-row-label">Giá nhập (VNĐ)</span>
                                <input type="number" name="giaNhap[]" class="custom-input price-input" value="<?= $ct['giaNhap'] ?>" required oninput="updateSummary()">
                            </div>
                            <button type="button" class="btn-action btn-remove" onclick="removeRow(this)">
                                <span class="material-symbols-rounded">delete</span>
                            </button>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <div style="margin-top: 16px;">
                        <button type="button" class="btn-action btn-add" onclick="addProductRow()">
                            <span class="material-symbols-rounded">add</span> Thêm sản phẩm
                        </button>
                    </div>

                    <div class="summary-box">
                        <div class="summary-grid">
                            <div class="summary-item">
                                <div class="label">Số lượng sản phẩm</div>
                                <div class="value" id="summaryQty">0</div>
                            </div>
                            <div class="summary-item">
                                <div class="label">Tổng tiền nhập</div>
                                <div class="value" id="summaryTotal" style="color: #f59e0b;">0 đ</div>
                            </div>
                            <div class="summary-item">
                                <div class="label">Giá trung bình</div>
                                <div class="value" id="summaryAvg">0 đ</div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="action-bar">
                    <div class="action-bar-left">
                        <a href="phieunhap.php" class="btn-action btn-reset" style="text-decoration: none;">Hủy & Quay lại</a>
                    </div>
                    <div class="action-bar-right">
                        <button type="submit" name="btnLuuPhieuNhap" class="btn-action btn-submit" id="btnSubmit">
                            <span class="material-symbols-rounded">save</span> LƯU THAY ĐỔI
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
let rowCounter = <?= count($chitiets) ?>;

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
            <select name="maMau[]" class="custom-input" required>
                ${options}
            </select>
        </div>
        <div class="col-serial">
            <span class="product-row-label">Số Serial</span>
            <input type="text" name="soSerial[]" class="custom-input serial-input" required>
        </div>
        <div class="col-price">
            <span class="product-row-label">Giá nhập (VNĐ)</span>
            <input type="number" name="giaNhap[]" class="custom-input price-input" value="0" required oninput="updateSummary()">
        </div>
        <button type="button" class="btn-action btn-remove" onclick="removeRow(this)">
            <span class="material-symbols-rounded">delete</span>
        </button>
    `;
    container.appendChild(newRow);
    updateSummary();
}

function removeRow(btn) {
    const rows = document.querySelectorAll('.product-row');
    if (rows.length > 1) {
        btn.closest('.product-row').remove();
        reNumberRows();
        updateSummary();
    } else {
        alert('Phải có ít nhất 1 sản phẩm!');
    }
}

function reNumberRows() {
    const rows = document.querySelectorAll('.product-row');
    rows.forEach((row, index) => {
        row.querySelector('.row-number').textContent = index + 1;
    });
}

function updateSummary() {
    const rows = document.querySelectorAll('.product-row');
    let totalQty = rows.length;
    let totalPrice = 0;
    
    rows.forEach(row => {
        const price = parseFloat(row.querySelector('.price-input').value) || 0;
        totalPrice += price;
    });

    document.getElementById('summaryQty').textContent = totalQty;
    document.getElementById('summaryTotal').textContent = totalPrice.toLocaleString('en-US') + ' đ';
    document.getElementById('summaryAvg').textContent = totalQty > 0 ? (totalPrice / totalQty).toLocaleString('en-US') + ' đ' : '0 đ';
}

function validateAndSubmit() {
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
        alert('Có mã Serial bị trùng trong phiếu!');
        return false;
    }
    return confirm('Bạn có chắc chắn muốn lưu thay đổi phiếu này?');
}

updateSummary();
</script>
<?php include 'includes/footer.php'; ?>
