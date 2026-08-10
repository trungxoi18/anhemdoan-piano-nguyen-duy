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
            $msg = "<div class='alert-msg alert-success'><span class='material-symbols-rounded'>check_circle</span> Thêm chương trình khuyến mãi thành công!</div>";
        } else {
            $msg = "<div class='alert-msg alert-error'><span class='material-symbols-rounded'>error</span> Lỗi: " . $conn->error . "</div>";
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
            $msg = "<div class='alert-msg alert-success'><span class='material-symbols-rounded'>check_circle</span> Cập nhật chương trình khuyến mãi thành công!</div>";
        } else {
            $msg = "<div class='alert-msg alert-error'><span class='material-symbols-rounded'>error</span> Lỗi: " . $conn->error . "</div>";
        }
    }

    // XÓA
    if (isset($_POST['btnDelete'])) {
        $maKM = intval($_POST['maKM']);
        
        $stmt = $conn->prepare("DELETE FROM chuongtrinhkhuyenmai WHERE maKM = ?");
        $stmt->bind_param("i", $maKM);
        
        if ($stmt->execute()) {
            $msg = "<div class='alert-msg alert-success'><span class='material-symbols-rounded'>check_circle</span> Đã xóa chương trình khuyến mãi!</div>";
        } else {
            $msg = "<div class='alert-msg alert-error'><span class='material-symbols-rounded'>error</span> Lỗi: " . $conn->error . "</div>";
        }
    }
}

// Lấy danh sách khuyến mãi
$promotions = $conn->query("SELECT * FROM chuongtrinhkhuyenmai ORDER BY ngayTao DESC");
?>

<?php include 'includes/header.php'; ?>
<?php include 'includes/sidebar.php'; ?>

<style>
    /* Premium Dark Mode Styles for Promotions */
    .page-container {
        max-width: 1200px;
        margin: 0 auto;
        animation: fadeInUp 0.5s ease;
    }
    
    .hero-banner-promo { 
        background: linear-gradient(135deg, rgba(244, 114, 182, 0.15), rgba(236, 72, 153, 0.05)); 
        border: 1px solid rgba(244, 114, 182, 0.25);
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
    
    .hero-banner-promo::before {
        content: ''; position: absolute; top: -50%; right: -10%; width: 50%; height: 200%;
        background: radial-gradient(circle, rgba(244, 114, 182, 0.1) 0%, transparent 70%); pointer-events: none;
    }
    
    .hero-banner-promo h1 { margin: 0 0 10px 0; font-size: 1.8rem; color: #f472b6; font-weight: 800; display: flex; align-items: center; gap: 12px; }
    .hero-banner-promo p { margin: 0; font-size: 15px; color: var(--text-secondary); max-width: 600px; line-height: 1.6; }

    /* Buttons */
    .btn-action { 
        padding: 12px 24px; border-radius: var(--radius-md); 
        font-weight: 600; cursor: pointer; border: none; 
        transition: all 0.3s ease; display: inline-flex; 
        align-items: center; gap: 8px; font-family: inherit; font-size: 14px;
    }
    .btn-primary { 
        background: linear-gradient(135deg, #f472b6, #db2777); 
        color: #fff; box-shadow: 0 4px 16px rgba(244, 114, 182, 0.3);
    }
    .btn-primary:hover { transform: translateY(-2px); box-shadow: 0 8px 24px rgba(244, 114, 182, 0.4); }
    
    .btn-secondary { background: rgba(255,255,255,0.05); color: var(--text-primary); border: 1px solid var(--glass-border); }
    .btn-secondary:hover { background: rgba(255,255,255,0.1); }

    .btn-danger { background: rgba(248, 113, 113, 0.1); color: var(--danger); border: 1px solid rgba(248, 113, 113, 0.3); }
    .btn-danger:hover { background: rgba(248, 113, 113, 0.2); }

    /* Cards */
    .promo-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(350px, 1fr)); gap: 24px; }
    
    .promo-card {
        background: var(--bg-card);
        backdrop-filter: blur(12px);
        border: 1px solid var(--glass-border);
        border-radius: var(--radius-xl);
        padding: 24px;
        transition: 0.3s;
        position: relative;
        overflow: hidden;
        display: flex;
        flex-direction: column;
    }
    
    .promo-card:hover { 
        border-color: rgba(244, 114, 182, 0.3); 
        box-shadow: 0 8px 32px rgba(0,0,0,0.2); 
        transform: translateY(-4px);
    }

    .promo-status { position: absolute; top: 16px; right: 16px; padding: 4px 12px; border-radius: var(--radius-full); font-size: 11px; font-weight: 700; text-transform: uppercase; }
    .status-active { background: rgba(52, 211, 153, 0.15); color: #34d399; }
    .status-upcoming { background: rgba(96, 165, 250, 0.15); color: #60a5fa; }
    .status-ended { background: rgba(156, 163, 175, 0.15); color: #9ca3af; }

    .promo-title { font-size: 1.15rem; font-weight: 700; color: var(--text-primary); margin: 16px 0 12px 0; padding-right: 80px; }
    
    .promo-discount { 
        display: inline-flex; align-items: center; justify-content: center;
        background: linear-gradient(135deg, #f472b6, #db2777);
        color: #fff; font-size: 20px; font-weight: 800;
        width: 60px; height: 60px; border-radius: 50%;
        margin-bottom: 12px; box-shadow: 0 4px 12px rgba(244, 114, 182, 0.4);
    }

    .promo-dates { display: flex; align-items: center; gap: 8px; font-size: 13px; color: var(--text-secondary); margin-bottom: 16px; }
    .promo-dates .material-symbols-rounded { font-size: 16px; color: #f472b6; }
    
    .promo-content { 
        color: var(--text-secondary); line-height: 1.6; font-size: 13.5px;
        background: rgba(0,0,0,0.2); padding: 16px; border-radius: var(--radius-lg);
        border: 1px solid var(--glass-border);
        white-space: pre-line; flex: 1; margin-bottom: 20px;
    }

    .promo-actions { display: flex; gap: 10px; margin-top: auto; border-top: 1px solid var(--border); padding-top: 16px; }
    .promo-actions button { flex: 1; justify-content: center; }

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
        max-height: 90vh; overflow-y: auto;
    }
    .modal-overlay.active .modal-card { transform: translateY(0); opacity: 1; }

    .modal-header {
        padding: 24px 32px; border-bottom: 1px solid var(--border);
        display: flex; justify-content: space-between; align-items: center;
        position: sticky; top: 0; background: var(--bg-secondary); z-index: 10;
    }
    .modal-title { font-size: 1.2rem; font-weight: 700; color: #f472b6; margin: 0; display: flex; align-items: center; gap: 10px; }
    .modal-close {
        background: transparent; border: none; color: var(--text-muted);
        cursor: pointer; display: flex; align-items: center; justify-content: center;
        transition: 0.2s; padding: 4px; border-radius: 50%;
    }
    .modal-close:hover { background: rgba(255,255,255,0.1); color: var(--text-primary); }

    .modal-body { padding: 32px; }
    
    .input-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }

    .form-group { margin-bottom: 20px; }
    .form-group label { display: block; font-weight: 600; color: var(--text-secondary); margin-bottom: 10px; font-size: 13px; text-transform: uppercase; letter-spacing: 0.5px; }
    .custom-input { 
        width: 100%; padding: 14px 16px; background: rgba(0, 0, 0, 0.2);
        border: 1px solid var(--glass-border); border-radius: var(--radius-md); 
        outline: none; transition: 0.3s; color: var(--text-primary); font-family: inherit; font-size: 14px;
    }
    .custom-input:focus { border-color: #f472b6; background: rgba(244, 114, 182, 0.05); box-shadow: 0 0 0 3px rgba(244, 114, 182, 0.15); }
    .custom-input option { background: var(--bg-secondary); color: var(--text-primary); }
    
    /* Date input specific fixes for dark mode */
    input[type="date"]::-webkit-calendar-picker-indicator {
        filter: invert(1); opacity: 0.5; cursor: pointer; transition: 0.2s;
    }
    input[type="date"]::-webkit-calendar-picker-indicator:hover { opacity: 0.8; }

    .modal-footer {
        padding: 24px 32px; border-top: 1px solid var(--border);
        display: flex; justify-content: flex-end; gap: 12px; background: rgba(0,0,0,0.1);
        border-radius: 0 0 var(--radius-xl) var(--radius-xl);
        position: sticky; bottom: 0;
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
