<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'db.php';
require_once 'functions.php';

// Kiểm tra đăng nhập
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$role_id = $_SESSION['role_id']; // 1 là Admin
$is_admin = ($role_id == 1);
$msg = "";

// ==========================================
// XỬ LÝ BACKEND (Chỉ Admin mới có quyền)
// ==========================================
if ($is_admin && $_SERVER['REQUEST_METHOD'] == 'POST') {
    
    // THÊM MỚI
    if (isset($_POST['btnAdd'])) {
        $tenCT = trim($_POST['tenChuongTrinh']);
        $moTa = trim($_POST['moTa']);
        $phanTramGiam = intval($_POST['phanTramGiam']);
        $ngayBatDau = $_POST['ngayBatDau'];
        $ngayKetThuc = $_POST['ngayKetThuc'];
        $trangThai = $_POST['trangThai'];
        
        $stmt = $conn->prepare("INSERT INTO chuongtrinhkhuyenmai (tenChuongTrinh, moTa, phanTramGiam, ngayBatDau, ngayKetThuc, trangThai, ngayTao) VALUES (?, ?, ?, ?, ?, ?, NOW())");
        $stmt->bind_param("ssisss", $tenCT, $moTa, $phanTramGiam, $ngayBatDau, $ngayKetThuc, $trangThai);
        
        if ($stmt->execute()) {
            $_SESSION['flash_success'] = "Thêm chương trình khuyến mãi thành công!";
        } else {
            $_SESSION['flash_error'] = "Lỗi: " . $conn->error;
        }
    }
    
    // SỬA
    if (isset($_POST['btnEdit'])) {
        $maKM = intval($_POST['maKM']);
        $tenCT = trim($_POST['tenChuongTrinh']);
        $moTa = trim($_POST['moTa']);
        $phanTramGiam = intval($_POST['phanTramGiam']);
        $ngayBatDau = $_POST['ngayBatDau'];
        $ngayKetThuc = $_POST['ngayKetThuc'];
        $trangThai = $_POST['trangThai'];
        
        $stmt = $conn->prepare("UPDATE chuongtrinhkhuyenmai SET tenChuongTrinh = ?, moTa = ?, phanTramGiam = ?, ngayBatDau = ?, ngayKetThuc = ?, trangThai = ? WHERE maKM = ?");
        $stmt->bind_param("ssisssi", $tenCT, $moTa, $phanTramGiam, $ngayBatDau, $ngayKetThuc, $trangThai, $maKM);
        
        if ($stmt->execute()) {
            $_SESSION['flash_success'] = "Cập nhật chương trình khuyến mãi thành công!";
        } else {
            $_SESSION['flash_error'] = "Lỗi: " . $conn->error;
        }
    }

    // XÓA
    if (isset($_POST['btnDelete'])) {
        $maKM = intval($_POST['maKM']);
        
        $stmt = $conn->prepare("DELETE FROM chuongtrinhkhuyenmai WHERE maKM = ?");
        $stmt->bind_param("i", $maKM);
        
        if ($stmt->execute()) {
            $_SESSION['flash_success'] = "Đã xóa chương trình khuyến mãi!";
        } else {
            $_SESSION['flash_error'] = "Lỗi: " . $conn->error;
        }
    }

    header("Location: cs_khuyenmai.php");
    exit();
}

$msg = "";
if (isset($_SESSION['flash_success'])) {
    $msg = "<div class='alert-msg alert-success'><span class='material-symbols-rounded'>check_circle</span> " . htmlspecialchars($_SESSION['flash_success']) . "</div>";
    unset($_SESSION['flash_success']);
}
if (isset($_SESSION['flash_error'])) {
    $msg = "<div class='alert-msg alert-error'><span class='material-symbols-rounded'>error</span> " . htmlspecialchars($_SESSION['flash_error']) . "</div>";
    unset($_SESSION['flash_error']);
}

// Lấy danh sách khuyến mãi
$promotions = $conn->query("SELECT * FROM chuongtrinhkhuyenmai ORDER BY ngayTao DESC");
?>

<?php include 'includes/header.php'; ?>
<?php include 'includes/sidebar.php'; ?>


<div class="main-wrapper">
    <?php include 'includes/topbar.php'; ?>

    <div class="content">
        <div class="page-container">
            
            <div class="hero-banner-promo">
                <div>
                    <h1><span class="material-symbols-rounded" style="font-size: 32px;">redeem</span> Chương trình khuyến mãi</h1>
                    <p>Cập nhật và tra cứu các chương trình khuyến mãi, sự kiện giảm giá đang diễn ra tại cửa hàng để áp dụng cho khách hàng.</p>
                </div>
                <?php if ($is_admin): ?>
                <div>
                    <button class="btn-action btn-primary" onclick="openModal('addModal')">
                        <span class="material-symbols-rounded">add</span> Thêm khuyến mãi
                    </button>
                </div>
                <?php endif; ?>
            </div>

            <?php echo $msg; ?>

            <div class="promo-grid">
                <?php if ($promotions && $promotions->num_rows > 0): ?>
                    <?php while($p = $promotions->fetch_assoc()): ?>
                    <?php 
                        $status_class = 'status-ended';
                        if ($p['trangThai'] == 'Đang diễn ra') $status_class = 'status-active';
                        else if ($p['trangThai'] == 'Sắp tới') $status_class = 'status-upcoming';
                    ?>
                    <div class="promo-card">
                        <div class="promo-status <?php echo $status_class; ?>"><?php echo htmlspecialchars($p['trangThai']); ?></div>
                        
                        <?php if ($p['phanTramGiam'] > 0): ?>
                        <div class="promo-discount">-<?php echo $p['phanTramGiam']; ?>%</div>
                        <?php else: ?>
                        <div class="promo-discount" style="font-size: 14px; display: flex; flex-direction: column; line-height: 1.2;"><span>HOT</span><span>DEAL</span></div>
                        <?php endif; ?>

                        <h3 class="promo-title"><?php echo htmlspecialchars($p['tenChuongTrinh']); ?></h3>
                        
                        <div class="promo-dates">
                            <span class="material-symbols-rounded">calendar_month</span>
                            <?php echo date('d/m/Y', strtotime($p['ngayBatDau'])); ?> - <?php echo date('d/m/Y', strtotime($p['ngayKetThuc'])); ?>
                        </div>
                        
                        <div class="promo-content"><?php echo htmlspecialchars($p['moTa']); ?></div>

                        <?php if ($is_admin): ?>
                        <div class="promo-actions">
                            <button class="btn-action btn-secondary" onclick="openEditModal(
                                <?php echo $p['maKM']; ?>, 
                                '<?php echo htmlspecialchars(addslashes($p['tenChuongTrinh'])); ?>', 
                                '<?php echo htmlspecialchars(addslashes(str_replace(["\r\n", "\r", "\n"], "\\n", $p['moTa']))); ?>',
                                <?php echo $p['phanTramGiam']; ?>,
                                '<?php echo $p['ngayBatDau']; ?>',
                                '<?php echo $p['ngayKetThuc']; ?>',
                                '<?php echo $p['trangThai']; ?>'
                            )">
                                <span class="material-symbols-rounded">edit</span> Sửa
                            </button>
                            <form method="POST" style="display:inline; flex: 1;" onsubmit="return confirm('Bạn có chắc chắn muốn xóa chương trình này?');">
                                <input type="hidden" name="maKM" value="<?php echo $p['maKM']; ?>">
                                <button type="submit" name="btnDelete" class="btn-action btn-danger" style="width: 100%;">
                                    <span class="material-symbols-rounded">delete</span> Xóa
                                </button>
                            </form>
                        </div>
                        <?php endif; ?>
                    </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div style="grid-column: 1 / -1; text-align: center; padding: 60px 20px; color: var(--text-muted); background: var(--bg-card); border-radius: var(--radius-xl); border: 1px dashed var(--glass-border);">
                        <span class="material-symbols-rounded" style="font-size: 48px; margin-bottom: 16px; opacity: 0.5;">loyalty</span>
                        <h3>Chưa có chương trình nào</h3>
                        <p>Hệ thống hiện tại chưa có thông tin chương trình khuyến mãi.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php if ($is_admin): ?>
<!-- MODAL THÊM -->
<div class="modal-overlay" id="addModal">
    <div class="modal-card">
        <div class="modal-header">
            <h3 class="modal-title"><span class="material-symbols-rounded">add_circle</span> Thêm khuyến mãi mới</h3>
            <button class="modal-close" onclick="closeModal('addModal')"><span class="material-symbols-rounded">close</span></button>
        </div>
        <form method="POST">
            <div class="modal-body">
                <div class="form-group">
                    <label>Tên chương trình</label>
                    <input type="text" name="tenChuongTrinh" class="custom-input" placeholder="VD: Khuyến mãi mùa tựu trường..." required>
                </div>
                
                <div class="input-grid">
                    <div class="form-group">
                        <label>% Giảm giá (nếu có)</label>
                        <input type="number" name="phanTramGiam" class="custom-input" placeholder="0 - 100" min="0" max="100" value="0">
                    </div>
                    <div class="form-group">
                        <label>Trạng thái</label>
                        <select name="trangThai" class="custom-input" required>
                            <option value="Đang diễn ra">Đang diễn ra</option>
                            <option value="Sắp tới">Sắp tới</option>
                            <option value="Đã kết thúc">Đã kết thúc</option>
                        </select>
                    </div>
                </div>

                <div class="input-grid">
                    <div class="form-group">
                        <label>Ngày bắt đầu</label>
                        <input type="date" name="ngayBatDau" class="custom-input" required>
                    </div>
                    <div class="form-group">
                        <label>Ngày kết thúc</label>
                        <input type="date" name="ngayKetThuc" class="custom-input" required>
                    </div>
                </div>

                <div class="form-group">
                    <label>Nội dung, thể lệ chi tiết</label>
                    <textarea name="moTa" class="custom-input" rows="5" placeholder="Mô tả chi tiết thể lệ khuyến mãi..." required></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-action btn-secondary" onclick="closeModal('addModal')">Hủy bỏ</button>
                <button type="submit" name="btnAdd" class="btn-action btn-primary"><span class="material-symbols-rounded">save</span> Lưu chương trình</button>
            </div>
        </form>
    </div>
</div>

<!-- MODAL SỬA -->
<div class="modal-overlay" id="editModal">
    <div class="modal-card">
        <div class="modal-header">
            <h3 class="modal-title"><span class="material-symbols-rounded">edit_square</span> Cập nhật khuyến mãi</h3>
            <button class="modal-close" onclick="closeModal('editModal')"><span class="material-symbols-rounded">close</span></button>
        </div>
        <form method="POST">
            <input type="hidden" name="maKM" id="edit_maKM">
            <div class="modal-body">
                <div class="form-group">
                    <label>Tên chương trình</label>
                    <input type="text" name="tenChuongTrinh" id="edit_tenChuongTrinh" class="custom-input" required>
                </div>
                
                <div class="input-grid">
                    <div class="form-group">
                        <label>% Giảm giá (nếu có)</label>
                        <input type="number" name="phanTramGiam" id="edit_phanTramGiam" class="custom-input" min="0" max="100">
                    </div>
                    <div class="form-group">
                        <label>Trạng thái</label>
                        <select name="trangThai" id="edit_trangThai" class="custom-input" required>
                            <option value="Đang diễn ra">Đang diễn ra</option>
                            <option value="Sắp tới">Sắp tới</option>
                            <option value="Đã kết thúc">Đã kết thúc</option>
                        </select>
                    </div>
                </div>

                <div class="input-grid">
                    <div class="form-group">
                        <label>Ngày bắt đầu</label>
                        <input type="date" name="ngayBatDau" id="edit_ngayBatDau" class="custom-input" required>
                    </div>
                    <div class="form-group">
                        <label>Ngày kết thúc</label>
                        <input type="date" name="ngayKetThuc" id="edit_ngayKetThuc" class="custom-input" required>
                    </div>
                </div>

                <div class="form-group">
                    <label>Nội dung, thể lệ chi tiết</label>
                    <textarea name="moTa" id="edit_moTa" class="custom-input" rows="5" required></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-action btn-secondary" onclick="closeModal('editModal')">Hủy bỏ</button>
                <button type="submit" name="btnEdit" class="btn-action btn-primary"><span class="material-symbols-rounded">update</span> Lưu thay đổi</button>
            </div>
        </form>
    </div>
</div>

<script>
    function openModal(id) {
        document.getElementById(id).classList.add('active');
    }
    
    function closeModal(id) {
        document.getElementById(id).classList.remove('active');
    }

    function openEditModal(id, title, desc, percent, startDate, endDate, status) {
        document.getElementById('edit_maKM').value = id;
        document.getElementById('edit_tenChuongTrinh').value = title;
        document.getElementById('edit_phanTramGiam').value = percent;
        document.getElementById('edit_ngayBatDau').value = startDate;
        document.getElementById('edit_ngayKetThuc').value = endDate;
        document.getElementById('edit_trangThai').value = status;
        
        // Handle line breaks
        const formattedDesc = desc.replace(/\\n/g, '\n');
        document.getElementById('edit_moTa').value = formattedDesc;
        
        openModal('editModal');
    }
    
    // Close modal on click outside
    window.onclick = function(event) {
        if (event.target.classList.contains('modal-overlay')) {
            event.target.classList.remove('active');
        }
    }
</script>
<?php endif; ?>

<?php include 'includes/footer.php'; ?>
