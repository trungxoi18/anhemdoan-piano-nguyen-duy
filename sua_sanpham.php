<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'db.php';
require_once 'functions.php';

// Kiểm tra đăng nhập và quyền (chỉ admin)
if (!isset($_SESSION['user_id']) || $_SESSION['role_id'] != 1) {
    header("Location: index.php");
    exit();
}

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($id <= 0) {
    header("Location: tracuu.php");
    exit();
}

$error = '';
$success = '';

// Lấy thông tin đàn
$sql = "SELECT md.* FROM MauDan md WHERE md.maMau = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);
$stmt->execute();
$product = $stmt->get_result()->fetch_assoc();

if (!$product) {
    header("Location: tracuu.php");
    exit();
}

// Xử lý submit
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $tenMau = trim($_POST['tenMau'] ?? '');
    $maLoai = intval($_POST['maLoai'] ?? 0);
    $maHang = intval($_POST['maHang'] ?? 0);
    $moTa = trim($_POST['moTa'] ?? '');
    $xuatXu = trim($_POST['xuatXu'] ?? '');
    $chatLieu = trim($_POST['chatLieu'] ?? '');
    $kichThuoc = trim($_POST['kichThuoc'] ?? '');
    $trongLuong = trim($_POST['trongLuong'] ?? '');
    $mauSac = trim($_POST['mauSac'] ?? '');
    $baoHanh = trim($_POST['baoHanh'] ?? '');
    
    // Cập nhật giá bán hàng loạt (nếu có nhập)
    $giaBanMoi = trim($_POST['giaBanMoi'] ?? '');

    if (empty($tenMau) || $maLoai <= 0 || $maHang <= 0) {
        $error = "Vui lòng nhập đầy đủ Tên mẫu đàn, Hãng sản xuất và Loại đàn.";
    } else {
        $conn->begin_transaction();
        try {
            // Xử lý ảnh (nếu có upload mới)
            $hinhAnh = $product['hinhAnh'];
            if (isset($_FILES['hinhAnh']) && $_FILES['hinhAnh']['error'] == 0) {
                $allowed = ['jpg', 'jpeg', 'png', 'webp'];
                $filename = $_FILES['hinhAnh']['name'];
                $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
                if (in_array($ext, $allowed)) {
                    $new_filename = uniqid() . '.' . $ext;
                    $upload_path = 'images/' . $new_filename;
                    if (move_uploaded_file($_FILES['hinhAnh']['tmp_name'], $upload_path)) {
                        // Xóa ảnh cũ nếu có (tùy chọn)
                        $hinhAnh = $new_filename;
                    }
                }
            }

            // Cập nhật bảng MauDan
            $sql_update = "UPDATE MauDan SET 
                            tenMau = ?, maLoai = ?, maHang = ?, moTa = ?, hinhAnh = ?,
                            xuatXu = ?, chatLieu = ?, kichThuoc = ?, trongLuong = ?, mauSac = ?, baoHanh = ?
                           WHERE maMau = ?";
            $stmt_up = $conn->prepare($sql_update);
            $stmt_up->bind_param("siissssssssi", $tenMau, $maLoai, $maHang, $moTa, $hinhAnh,
                                                  $xuatXu, $chatLieu, $kichThuoc, $trongLuong, $mauSac, $baoHanh, $id);
            $stmt_up->execute();

            // Cập nhật giá bán hàng loạt
            if ($giaBanMoi !== '' && is_numeric($giaBanMoi)) {
                $giaBanMoiVal = intval($giaBanMoi);
                $sql_price = "UPDATE danserial SET giaBan = ? WHERE maMau = ? AND trangThai = 'Trong kho'";
                $stmt_price = $conn->prepare($sql_price);
                $stmt_price->bind_param("ii", $giaBanMoiVal, $id);
                $stmt_price->execute();
            }

            $conn->commit();
            $success = "Cập nhật thông tin sản phẩm thành công.";
            
            // Cập nhật lại biến $product để form hiển thị giá trị mới
            $product['tenMau'] = $tenMau;
            $product['maLoai'] = $maLoai;
            $product['maHang'] = $maHang;
            $product['moTa'] = $moTa;
            $product['hinhAnh'] = $hinhAnh;
            $product['xuatXu'] = $xuatXu;
            $product['chatLieu'] = $chatLieu;
            $product['kichThuoc'] = $kichThuoc;
            $product['trongLuong'] = $trongLuong;
            $product['mauSac'] = $mauSac;
            $product['baoHanh'] = $baoHanh;
            
        } catch (Exception $e) {
            $conn->rollback();
            $error = "Đã xảy ra lỗi: " . $e->getMessage();
        }
    }
}

$title = "Chỉnh sửa: " . htmlspecialchars($product['tenMau']);

// Lấy danh sách hãng và loại
$hangs = $conn->query("SELECT * FROM HangDan ORDER BY tenHang ASC")->fetch_all(MYSQLI_ASSOC);
$loais = $conn->query("SELECT * FROM LoaiDan ORDER BY tenLoai ASC")->fetch_all(MYSQLI_ASSOC);
?>

<?php include 'includes/header.php'; ?>
<?php include 'includes/sidebar.php'; ?>



<div class="main-wrapper">
    <?php include 'includes/topbar.php'; ?>

    <div class="content">
        <a href="chi_tiet_san_pham.php?id=<?= $id ?>" style="display: inline-flex; align-items: center; gap: 6px; color: var(--text-secondary); text-decoration: none; margin-bottom: 24px; font-weight: 600; transition: 0.2s;" onmouseover="this.style.color='var(--accent)'" onmouseout="this.style.color='var(--text-secondary)'">
            <span class="material-symbols-rounded" style="font-size: 20px;">arrow_back</span>
            Trở về trang chi tiết
        </a>

        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;">
            <h2 style="margin: 0; display: flex; align-items: center; gap: 12px;">
                <span class="material-symbols-rounded" style="color: var(--accent); font-size: 32px;">edit_document</span>
                Chỉnh sửa sản phẩm
            </h2>
        </div>

        <?php if($error): ?>
            <div class="alert alert-danger"><span class="material-symbols-rounded">error</span> <?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        <?php if($success): ?>
            <div class="alert alert-success"><span class="material-symbols-rounded">check_circle</span> <?= htmlspecialchars($success) ?></div>
        <?php endif; ?>

        <form method="POST" enctype="multipart/form-data" class="form-card">
            <div class="grid-2">
                <div class="form-group">
                    <label>Tên sản phẩm *</label>
                    <input type="text" name="tenMau" class="custom-input" value="<?= htmlspecialchars($product['tenMau']) ?>" required>
                </div>
                <div class="form-group">
                    <label>Hình ảnh (Để trống nếu không muốn đổi)</label>
                    <div style="display: flex; gap: 16px; align-items: center;">
                        <?php if(!empty($product['hinhAnh'])): ?>
                            <img src="images/<?= htmlspecialchars($product['hinhAnh']) ?>" class="current-image" alt="Current Image">
                        <?php else: ?>
                            <div class="current-image" style="display: flex; align-items: center; justify-content: center;">
                                <span class="material-symbols-rounded" style="font-size: 40px; color: rgba(255,255,255,0.2);">image</span>
                            </div>
                        <?php endif; ?>
                        <input type="file" name="hinhAnh" class="custom-input" accept="image/*" style="flex: 1;">
                    </div>
                </div>
            </div>

            <div class="grid-2">
                <div class="form-group">
                    <label>Hãng sản xuất *</label>
                    <select name="maHang" class="custom-input" required>
                        <option value="">-- Chọn hãng --</option>
                        <?php foreach($hangs as $h): ?>
                            <option value="<?= $h['maHang'] ?>" <?= $product['maHang'] == $h['maHang'] ? 'selected' : '' ?>><?= htmlspecialchars($h['tenHang']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Loại đàn *</label>
                    <select name="maLoai" class="custom-input" required>
                        <option value="">-- Chọn loại --</option>
                        <?php foreach($loais as $l): ?>
                            <option value="<?= $l['maLoai'] ?>" <?= $product['maLoai'] == $l['maLoai'] ? 'selected' : '' ?>><?= htmlspecialchars($l['tenLoai']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="form-group">
                <label>Mô tả sản phẩm</label>
                <textarea name="moTa" class="custom-input" rows="4"><?= htmlspecialchars($product['moTa']) ?></textarea>
            </div>

            <h3 style="margin: 32px 0 20px 0; color: var(--text-primary); border-bottom: 1px solid var(--glass-border); padding-bottom: 12px; font-size: 18px; display: flex; align-items: center; gap: 8px;">
                <span class="material-symbols-rounded" style="color: var(--accent);">info</span> Thông số kỹ thuật
            </h3>
            
            <div class="grid-2">
                <div class="form-group">
                    <label>Xuất xứ</label>
                    <input type="text" name="xuatXu" class="custom-input" value="<?= htmlspecialchars($product['xuatXu']) ?>">
                </div>
                <div class="form-group">
                    <label>Chất liệu</label>
                    <input type="text" name="chatLieu" class="custom-input" value="<?= htmlspecialchars($product['chatLieu']) ?>">
                </div>
                <div class="form-group">
                    <label>Màu sắc</label>
                    <input type="text" name="mauSac" class="custom-input" value="<?= htmlspecialchars($product['mauSac']) ?>">
                </div>
                <div class="form-group">
                    <label>Bảo hành</label>
                    <input type="text" name="baoHanh" class="custom-input" value="<?= htmlspecialchars($product['baoHanh'] ?? '5 năm') ?>">
                </div>
                <div class="form-group">
                    <label>Kích thước</label>
                    <input type="text" name="kichThuoc" class="custom-input" value="<?= htmlspecialchars($product['kichThuoc']) ?>">
                </div>
                <div class="form-group">
                    <label>Trọng lượng</label>
                    <input type="text" name="trongLuong" class="custom-input" value="<?= htmlspecialchars($product['trongLuong']) ?>">
                </div>
            </div>

            <h3 style="margin: 32px 0 20px 0; color: var(--text-primary); border-bottom: 1px solid var(--glass-border); padding-bottom: 12px; font-size: 18px; display: flex; align-items: center; gap: 8px;">
                <span class="material-symbols-rounded" style="color: var(--success);">payments</span> Cập nhật giá bán
            </h3>
            
            <div class="form-group" style="background: rgba(16, 185, 129, 0.05); padding: 20px; border-radius: var(--radius-md); border: 1px solid rgba(16, 185, 129, 0.2);">
                <label style="color: var(--success);">Cập nhật Giá bán cho toàn bộ sản phẩm này đang ở trong kho</label>
                <p style="font-size: 13px; color: var(--text-secondary); margin-bottom: 12px;">Nhập giá mới để đồng bộ thay đổi giá bán cho tất cả các đàn thuộc mẫu này đang tồn kho. Để trống nếu không muốn thay đổi giá.</p>
                <input type="number" name="giaBanMoi" class="custom-input" placeholder="Ví dụ: 15000000">
            </div>

            <div style="text-align: right; margin-top: 32px;">
                <button type="submit" class="btn-action btn-submit">
                    <span class="material-symbols-rounded">save</span>
                    Lưu thay đổi
                </button>
            </div>
        </form>

    </div>
</div>

<?php include 'includes/footer.php'; ?>
