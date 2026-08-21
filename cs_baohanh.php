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
        $tenCS = trim($_POST['tenChinhSach']);
        $noiDung = trim($_POST['noiDung']);
        
        $stmt = $conn->prepare("INSERT INTO chinhsachbaohanh (tenChinhSach, noiDung, ngayTao, ngayCapNhat) VALUES (?, ?, NOW(), NOW())");
        $stmt->bind_param("ss", $tenCS, $noiDung);
        
        if ($stmt->execute()) {
            $msg = "<div class='alert-msg alert-success'><span class='material-symbols-rounded'>check_circle</span> Thêm chính sách bảo hành thành công!</div>";
        } else {
            $msg = "<div class='alert-msg alert-error'><span class='material-symbols-rounded'>error</span> Lỗi: " . $conn->error . "</div>";
        }
    }
    
    // SỬA
    if (isset($_POST['btnEdit'])) {
        $maCS = intval($_POST['maCS']);
        $tenCS = trim($_POST['tenChinhSach']);
        $noiDung = trim($_POST['noiDung']);
        
        $stmt = $conn->prepare("UPDATE chinhsachbaohanh SET tenChinhSach = ?, noiDung = ?, ngayCapNhat = NOW() WHERE maCS = ?");
        $stmt->bind_param("ssi", $tenCS, $noiDung, $maCS);
        
        if ($stmt->execute()) {
            $msg = "<div class='alert-msg alert-success'><span class='material-symbols-rounded'>check_circle</span> Cập nhật chính sách thành công!</div>";
        } else {
            $msg = "<div class='alert-msg alert-error'><span class='material-symbols-rounded'>error</span> Lỗi: " . $conn->error . "</div>";
        }
    }

    // XÓA
    if (isset($_POST['btnDelete'])) {
        $maCS = intval($_POST['maCS']);
        
        $stmt = $conn->prepare("DELETE FROM chinhsachbaohanh WHERE maCS = ?");
        $stmt->bind_param("i", $maCS);
        
        if ($stmt->execute()) {
            $msg = "<div class='alert-msg alert-success'><span class='material-symbols-rounded'>check_circle</span> Đã xóa chính sách!</div>";
        } else {
            $msg = "<div class='alert-msg alert-error'><span class='material-symbols-rounded'>error</span> Lỗi: " . $conn->error . "</div>";
        }
    }
}

// Lấy danh sách chính sách
$policies = $conn->query("SELECT * FROM chinhsachbaohanh ORDER BY ngayTao DESC");
?>

<?php include 'includes/header.php'; ?>
<?php include 'includes/sidebar.php'; ?>


<div class="main-wrapper">
    <?php include 'includes/topbar.php'; ?>

    <div class="content">
        <div class="page-container">
            
            <div class="hero-banner-policy">
                <div>
                    <h1><span class="material-symbols-rounded" style="font-size: 32px;">policy</span> Chính sách bảo hành</h1>
                    <p>Cập nhật và tra cứu các chính sách, điều khoản bảo hành sản phẩm đối với khách hàng. Cam kết dịch vụ hậu mãi uy tín.</p>
                </div>
                <?php if ($is_admin): ?>
                <div>
                    <button class="btn-action btn-primary" onclick="openModal('addModal')">
                        <span class="material-symbols-rounded">add</span> Thêm chính sách
                    </button>
                </div>
                <?php endif; ?>
            </div>

            <?php echo $msg; ?>

            <div class="policy-grid">
                <?php if ($policies && $policies->num_rows > 0): ?>
                    <?php while($p = $policies->fetch_assoc()): ?>
                    <div class="policy-card">
                        <div class="policy-header">
                            <div>
                                <h3 class="policy-title"><?php echo htmlspecialchars($p['tenChinhSach']); ?></h3>
                                <div class="policy-meta">
                                    <span class="material-symbols-rounded" style="font-size: 14px;">update</span> 
                                    Cập nhật lần cuối: <?php echo date('d/m/Y H:i', strtotime($p['ngayCapNhat'])); ?>
                                </div>
                            </div>
                            
                            <?php if ($is_admin): ?>
                            <div class="policy-actions">
                                <button class="btn-action btn-secondary" onclick="openEditModal(<?php echo $p['maCS']; ?>, '<?php echo htmlspecialchars(addslashes($p['tenChinhSach'])); ?>', '<?php echo htmlspecialchars(addslashes(str_replace(["\r\n", "\r", "\n"], "\\n", $p['noiDung']))); ?>')">
                                    <span class="material-symbols-rounded">edit</span> Sửa
                                </button>
                                <form method="POST" style="display:inline;" onsubmit="return confirm('Bạn có chắc chắn muốn xóa chính sách này?');">
                                    <input type="hidden" name="maCS" value="<?php echo $p['maCS']; ?>">
                                    <button type="submit" name="btnDelete" class="btn-action btn-danger">
                                        <span class="material-symbols-rounded">delete</span> Xóa
                                    </button>
                                </form>
                            </div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="policy-content"><?php echo htmlspecialchars($p['noiDung']); ?></div>
                    </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div style="text-align: center; padding: 60px 20px; color: var(--text-muted); background: var(--bg-card); border-radius: var(--radius-xl); border: 1px dashed var(--glass-border);">
                        <span class="material-symbols-rounded" style="font-size: 48px; margin-bottom: 16px; opacity: 0.5;">assignment_late</span>
                        <h3>Chưa có chính sách nào</h3>
                        <p>Hệ thống hiện tại chưa có thông tin chính sách bảo hành.</p>
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
            <h3 class="modal-title"><span class="material-symbols-rounded">add_circle</span> Thêm chính sách mới</h3>
            <button class="modal-close" onclick="closeModal('addModal')"><span class="material-symbols-rounded">close</span></button>
        </div>
        <form method="POST">
            <div class="modal-body">
                <div class="form-group">
                    <label>Tên / Tiêu đề chính sách</label>
                    <input type="text" name="tenChinhSach" class="custom-input" placeholder="VD: Chính sách bảo hành Piano Cơ..." required>
                </div>
                <div class="form-group">
                    <label>Nội dung chi tiết</label>
                    <textarea name="noiDung" class="custom-input" rows="8" placeholder="Nhập nội dung các điều khoản..." required></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-action btn-secondary" onclick="closeModal('addModal')">Hủy bỏ</button>
                <button type="submit" name="btnAdd" class="btn-action btn-primary"><span class="material-symbols-rounded">save</span> Lưu chính sách</button>
            </div>
        </form>
    </div>
</div>

<!-- MODAL SỬA -->
<div class="modal-overlay" id="editModal">
    <div class="modal-card">
        <div class="modal-header">
            <h3 class="modal-title"><span class="material-symbols-rounded">edit_square</span> Cập nhật chính sách</h3>
            <button class="modal-close" onclick="closeModal('editModal')"><span class="material-symbols-rounded">close</span></button>
        </div>
        <form method="POST">
            <input type="hidden" name="maCS" id="edit_maCS">
            <div class="modal-body">
                <div class="form-group">
                    <label>Tên / Tiêu đề chính sách</label>
                    <input type="text" name="tenChinhSach" id="edit_tenChinhSach" class="custom-input" required>
                </div>
                <div class="form-group">
                    <label>Nội dung chi tiết</label>
                    <textarea name="noiDung" id="edit_noiDung" class="custom-input" rows="8" required></textarea>
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

    function openEditModal(id, title, content) {
        document.getElementById('edit_maCS').value = id;
        document.getElementById('edit_tenChinhSach').value = title;
        
        // Handle line breaks correctly
        const formattedContent = content.replace(/\\n/g, '\n');
        document.getElementById('edit_noiDung').value = formattedContent;
        
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
