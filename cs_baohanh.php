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

<style>
    /* Premium Dark Mode Styles for Policies */
    .page-container {
        max-width: 1200px;
        margin: 0 auto;
        animation: fadeInUp 0.5s ease;
    }
    
    .hero-banner-policy { 
        background: linear-gradient(135deg, rgba(94, 234, 212, 0.15), rgba(52, 211, 153, 0.05)); 
        border: 1px solid rgba(94, 234, 212, 0.25);
        color: var(--text-primary); 
        padding: 36px 40px; 
        border-radius: var(--radius-xl); 
        margin-bottom: 32px; 
        position: relative;
        overflow: hidden;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    
    .hero-banner-policy::before {
        content: ''; position: absolute; top: -50%; right: -10%; width: 50%; height: 200%;
        background: radial-gradient(circle, rgba(94, 234, 212, 0.1) 0%, transparent 70%); pointer-events: none;
    }
    
    .hero-banner-policy h1 { margin: 0 0 10px 0; font-size: 1.8rem; color: #5eead4; font-weight: 800; display: flex; align-items: center; gap: 12px; }
    .hero-banner-policy p { margin: 0; font-size: 15px; color: var(--text-secondary); max-width: 600px; line-height: 1.6; }

    /* Buttons */
    .btn-action { 
        padding: 12px 24px; border-radius: var(--radius-md); 
        font-weight: 600; cursor: pointer; border: none; 
        transition: all 0.3s ease; display: inline-flex; 
        align-items: center; gap: 8px; font-family: inherit; font-size: 14px;
    }
    .btn-primary { 
        background: linear-gradient(135deg, #34d399, #10b981); 
        color: #000; box-shadow: 0 4px 16px rgba(52, 211, 153, 0.3);
    }
    .btn-primary:hover { transform: translateY(-2px); box-shadow: 0 8px 24px rgba(52, 211, 153, 0.4); }
    
    .btn-secondary { background: rgba(255,255,255,0.05); color: var(--text-primary); border: 1px solid var(--glass-border); }
    .btn-secondary:hover { background: rgba(255,255,255,0.1); }

    .btn-danger { background: rgba(248, 113, 113, 0.1); color: var(--danger); border: 1px solid rgba(248, 113, 113, 0.3); }
    .btn-danger:hover { background: rgba(248, 113, 113, 0.2); }

    /* Cards */
    .policy-grid { display: grid; gap: 24px; }
    
    .policy-card {
        background: var(--bg-card);
        backdrop-filter: blur(12px);
        border: 1px solid var(--glass-border);
        border-radius: var(--radius-xl);
        padding: 32px;
        transition: 0.3s;
        position: relative;
        overflow: hidden;
    }
    
    .policy-card:hover { 
        border-color: rgba(94, 234, 212, 0.3); 
        box-shadow: 0 8px 32px rgba(0,0,0,0.2); 
        transform: translateY(-2px);
    }

    .policy-card::before {
        content: ''; position: absolute; top: 0; left: 0; width: 4px; height: 100%;
        background: linear-gradient(to bottom, #5eead4, #34d399);
    }

    .policy-header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 20px; }
    .policy-title { font-size: 1.25rem; font-weight: 700; color: var(--text-primary); margin: 0; }
    .policy-meta { font-size: 12px; color: var(--text-muted); display: flex; align-items: center; gap: 6px; margin-top: 8px; }
    
    .policy-actions { display: flex; gap: 10px; }
    .policy-actions button { padding: 8px 16px; font-size: 13px; }

    .policy-content { 
        color: var(--text-secondary); line-height: 1.7; font-size: 14.5px;
        background: rgba(0,0,0,0.2); padding: 24px; border-radius: var(--radius-lg);
        border: 1px solid var(--glass-border);
        white-space: pre-line;
    }

    /* Modal Form */
    .modal-overlay {
        position: fixed; top: 0; left: 0; width: 100%; height: 100%;
        background: rgba(15, 13, 26, 0.85); backdrop-filter: blur(8px);
        display: none; justify-content: center; align-items: center;
        z-index: 9999; padding: 20px;
    }
    .modal-overlay.active { display: flex; animation: fadeIn 0.3s ease; }
    
    .modal-card {
        background: var(--bg-secondary); border: 1px solid var(--border-hover);
        border-radius: var(--radius-xl); width: 100%; max-width: 600px;
        box-shadow: 0 20px 40px rgba(0,0,0,0.5);
        transform: translateY(20px); opacity: 0;
        transition: 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }
    .modal-overlay.active .modal-card { transform: translateY(0); opacity: 1; }

    .modal-header {
        padding: 24px 32px; border-bottom: 1px solid var(--border);
        display: flex; justify-content: space-between; align-items: center;
    }
    .modal-title { font-size: 1.2rem; font-weight: 700; color: #5eead4; margin: 0; display: flex; align-items: center; gap: 10px; }
    .modal-close {
        background: transparent; border: none; color: var(--text-muted);
        cursor: pointer; display: flex; align-items: center; justify-content: center;
        transition: 0.2s; padding: 4px; border-radius: 50%;
    }
    .modal-close:hover { background: rgba(255,255,255,0.1); color: var(--text-primary); }

    .modal-body { padding: 32px; }
    
    .form-group { margin-bottom: 20px; }
    .form-group label { display: block; font-weight: 600; color: var(--text-secondary); margin-bottom: 10px; font-size: 13px; text-transform: uppercase; letter-spacing: 0.5px; }
    .custom-input { 
        width: 100%; padding: 14px 16px; background: rgba(0, 0, 0, 0.2);
        border: 1px solid var(--glass-border); border-radius: var(--radius-md); 
        outline: none; transition: 0.3s; color: var(--text-primary); font-family: inherit; font-size: 14px;
    }
    .custom-input:focus { border-color: #5eead4; background: rgba(94, 234, 212, 0.05); box-shadow: 0 0 0 3px rgba(94, 234, 212, 0.15); }

    .modal-footer {
        padding: 24px 32px; border-top: 1px solid var(--border);
        display: flex; justify-content: flex-end; gap: 12px; background: rgba(0,0,0,0.1);
        border-radius: 0 0 var(--radius-xl) var(--radius-xl);
    }

    /* Alert */
    .alert-msg { padding: 16px 20px; border-radius: var(--radius-lg); margin-bottom: 24px; display: flex; align-items: center; gap: 12px; animation: fadeInUp 0.4s ease; }
    .alert-msg .material-symbols-rounded { font-size: 24px; }
    .alert-success { background: var(--success-bg); color: var(--success); border: 1px solid rgba(52,211,153,0.3); }
    .alert-error { background: var(--danger-bg); color: var(--danger); border: 1px solid rgba(248,113,113,0.3); }

</style>

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
