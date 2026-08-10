<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'db.php';
require_once 'functions.php';

// Kiểm tra đăng nhập
checkLogin();

$user_id = $_SESSION['user_id'];
$msg = "";
$msg_type = "";

// Lấy thông tin hiện tại của nhân viên
$sql_info = "SELECT nv.*, tk.tenDangNhap, tk.maVaiTro, vt.tenVaiTro 
             FROM NhanVien nv 
             JOIN TaiKhoan tk ON tk.maNhanVien = nv.maNhanVien 
             LEFT JOIN VaiTro vt ON tk.maVaiTro = vt.maVaiTro
             WHERE tk.maTaiKhoan = ?";
$stmt = $conn->prepare($sql_info);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

if (!$user) {
    header("Location: index.php");
    exit();
}

// === XỬ LÝ CẬP NHẬT THÔNG TIN ===
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['btnCapNhat'])) {
    $hoTen = trim($_POST['hoTen'] ?? '');
    $soDienThoai = trim($_POST['soDienThoai'] ?? '');
    $emailNV = trim($_POST['emailNV'] ?? '');
    
    // Validate
    if (empty($hoTen)) {
        $msg = "Họ tên không được để trống!";
        $msg_type = "error";
    } else {
        // Cập nhật thông tin
        $sql_update = "UPDATE NhanVien SET hoTen = ?, soDienThoai = ?, emailNV = ? WHERE maNhanVien = ?";
        $stmt_update = $conn->prepare($sql_update);
        $stmt_update->bind_param("sssi", $hoTen, $soDienThoai, $emailNV, $user['maNhanVien']);
        
        if ($stmt_update->execute()) {
            // Cập nhật session fullname
            $_SESSION['fullname'] = $hoTen;
            
            // Ghi log
            writeLog($conn, 'CẬP NHẬT HỒ SƠ', "Đã cập nhật thông tin cá nhân");
            
            $msg = "Cập nhật thông tin thành công!";
            $msg_type = "success";
            
            // Refresh lại thông tin
            $stmt->execute();
            $result = $stmt->get_result();
            $user = $result->fetch_assoc();
        } else {
            $msg = "Có lỗi xảy ra! Vui lòng thử lại.";
            $msg_type = "error";
        }
    }
}

// === XỬ LÝ CẬP NHẬT ẢNH ĐẠI DIỆN ===
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['btnCapNhatAnh'])) {
    $anhDaiDien = $user['anhDaiDien']; // Giữ ảnh cũ mặc định
    
    if (isset($_FILES['anhDaiDien']) && $_FILES['anhDaiDien']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['anhDaiDien'];
        $allowed = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        $maxSize = 5 * 1024 * 1024; // 5MB
        
        if (!in_array($file['type'], $allowed)) {
            $msg = "Chỉ chấp nhận file ảnh (JPG, PNG, GIF, WebP)!";
            $msg_type = "error";
        } elseif ($file['size'] > $maxSize) {
            $msg = "Dung lượng ảnh không được vượt quá 5MB!";
            $msg_type = "error";
        } else {
            // Tạo tên file unique
            $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
            $newFileName = 'avatar_' . $user['maNhanVien'] . '_' . time() . '.' . $ext;
            $uploadPath = 'uploads/avatars/' . $newFileName;
            
            if (move_uploaded_file($file['tmp_name'], $uploadPath)) {
                // Xóa ảnh cũ nếu có
                if ($anhDaiDien && file_exists($anhDaiDien)) {
                    unlink($anhDaiDien);
                }
                $anhDaiDien = $uploadPath;
                
                $sql_update = "UPDATE NhanVien SET anhDaiDien = ? WHERE maNhanVien = ?";
                $stmt_update = $conn->prepare($sql_update);
                $stmt_update->bind_param("si", $anhDaiDien, $user['maNhanVien']);
                
                if ($stmt_update->execute()) {
                    writeLog($conn, 'CẬP NHẬT ẢNH ĐẠI DIỆN', "Đã thay đổi ảnh đại diện");
                    $msg = "Cập nhật ảnh đại diện thành công!";
                    $msg_type = "success";
                    $stmt->execute();
                    $result = $stmt->get_result();
                    $user = $result->fetch_assoc();
                } else {
                    $msg = "Có lỗi xảy ra khi cập nhật DB!";
                    $msg_type = "error";
                }
            } else {
                $msg = "Lỗi khi upload ảnh! Vui lòng thử lại.";
                $msg_type = "error";
            }
        }
    } else {
        $msg = "Vui lòng chọn file ảnh để tải lên!";
        $msg_type = "error";
    }
}

// === XỬ LÝ ĐỔI MẬT KHẨU ===
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['btnDoiMatKhau'])) {
    $matKhauCu = $_POST['matKhauCu'];
    $matKhauMoi = $_POST['matKhauMoi'];
    $xacNhanMatKhau = $_POST['xacNhanMatKhau'];
    
    // Kiểm tra mật khẩu cũ
    $sql_check = "SELECT matKhau FROM TaiKhoan WHERE maTaiKhoan = ?";
    $stmt_check = $conn->prepare($sql_check);
    $stmt_check->bind_param("i", $user_id);
    $stmt_check->execute();
    $res_check = $stmt_check->get_result();
    $row_check = $res_check->fetch_assoc();
    
    if ($row_check['matKhau'] !== $matKhauCu) {
        $msg = "Mật khẩu hiện tại không đúng!";
        $msg_type = "error";
    } elseif (strlen($matKhauMoi) < 4) {
        $msg = "Mật khẩu mới phải có ít nhất 4 ký tự!";
        $msg_type = "error";
    } elseif ($matKhauMoi !== $xacNhanMatKhau) {
        $msg = "Xác nhận mật khẩu không khớp!";
        $msg_type = "error";
    } else {
        $sql_pw = "UPDATE TaiKhoan SET matKhau = ? WHERE maTaiKhoan = ?";
        $stmt_pw = $conn->prepare($sql_pw);
        $stmt_pw->bind_param("si", $matKhauMoi, $user_id);
        
        if ($stmt_pw->execute()) {
            writeLog($conn, 'ĐỔI MẬT KHẨU', "Đã thay đổi mật khẩu tài khoản");
            $msg = "Đổi mật khẩu thành công!";
            $msg_type = "success";
        } else {
            $msg = "Có lỗi xảy ra khi đổi mật khẩu!";
            $msg_type = "error";
        }
    }
}

// === XỬ LÝ XÓA ẢNH ĐẠI DIỆN (AJAX) ===
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'remove_avatar') {
    header('Content-Type: application/json');
    
    if ($user['anhDaiDien'] && file_exists($user['anhDaiDien'])) {
        unlink($user['anhDaiDien']);
    }
    
    $conn->query("UPDATE NhanVien SET anhDaiDien = NULL WHERE maNhanVien = " . intval($user['maNhanVien']));
    writeLog($conn, 'CẬP NHẬT HỒ SƠ', "Đã xóa ảnh đại diện");
    
    echo json_encode(['success' => true]);
    exit();
}

// Lấy chữ cái đầu
$avatar_letter = mb_substr($user['hoTen'], 0, 1, "UTF-8");

// Role mapping
$role_map = [1 => 'Quản trị viên', 2 => 'Nhân viên bán hàng', 3 => 'Thủ kho'];
$role_name = $user['tenVaiTro'] ?? ($role_map[$user['maVaiTro']] ?? 'Nhân viên');
?>

<?php include 'includes/header.php'; ?>
<?php include 'includes/sidebar.php'; ?>

<style>
    /* =============================================
       THÔNG TIN CÁ NHÂN - PREMIUM DARK MODE
       Color Theme: Violet/Purple (#a78bfa)
       ============================================= */
    
    .form-center-container { 
        max-width: 900px; 
        margin: 0 auto; 
        animation: fadeInUp 0.5s ease; 
    }

    /* === PROFILE HERO BANNER === */
    .profile-hero {
        background: linear-gradient(135deg, rgba(167, 139, 250, 0.2), rgba(124, 92, 252, 0.08), rgba(94, 234, 212, 0.05));
        border: 1px solid rgba(167, 139, 250, 0.25);
        border-radius: var(--radius-xl);
        padding: 40px;
        margin-bottom: 24px;
        position: relative;
        overflow: hidden;
        display: flex;
        align-items: center;
        gap: 32px;
    }

    .profile-hero::before {
        content: ''; position: absolute; top: -50%; right: -20%; width: 50%; height: 200%;
        background: radial-gradient(circle, rgba(167, 139, 250, 0.1) 0%, transparent 70%); 
        pointer-events: none;
    }

    .profile-hero::after {
        content: ''; position: absolute; bottom: -30%; left: -10%; width: 40%; height: 150%;
        background: radial-gradient(circle, rgba(94, 234, 212, 0.06) 0%, transparent 70%); 
        pointer-events: none;
    }

    /* === AVATAR SECTION === */
    .profile-avatar-wrapper {
        position: relative;
        flex-shrink: 0;
        z-index: 2;
    }

    .profile-avatar {
        width: 110px;
        height: 110px;
        border-radius: 50%;
        background: linear-gradient(135deg, var(--accent), #a78bfa, var(--accent-secondary));
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 42px;
        font-weight: 800;
        color: white;
        text-transform: uppercase;
        box-shadow: 0 0 0 4px var(--bg-secondary), 0 0 0 6px rgba(167, 139, 250, 0.4), 0 8px 32px rgba(124, 92, 252, 0.3);
        overflow: hidden;
        transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
    }

    .profile-avatar img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        transition: transform 0.4s;
    }

    .profile-avatar:hover img {
        transform: scale(1.1);
    }

    .profile-avatar-wrapper .avatar-edit-overlay {
        position: absolute;
        bottom: 0; right: 0;
        width: 36px; height: 36px;
        background: linear-gradient(135deg, var(--accent), #a78bfa);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        box-shadow: 0 2px 8px rgba(0,0,0,0.4);
        border: 3px solid var(--bg-secondary);
        transition: all 0.3s;
        z-index: 3;
    }

    .profile-avatar-wrapper .avatar-edit-overlay:hover {
        transform: scale(1.15);
        box-shadow: 0 4px 16px var(--accent-glow);
    }

    .profile-avatar-wrapper .avatar-edit-overlay .material-symbols-rounded {
        font-size: 16px;
        color: white;
    }

    /* === PROFILE INFO === */
    .profile-hero-info {
        z-index: 2;
        flex: 1;
    }

    .profile-hero-info h1 {
        font-size: 1.8rem;
        font-weight: 800;
        color: var(--text-primary);
        margin-bottom: 6px;
        letter-spacing: -0.5px;
    }

    .profile-hero-info .role-badge {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 5px 14px;
        border-radius: var(--radius-full);
        font-size: 12px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        margin-bottom: 12px;
        background: rgba(167, 139, 250, 0.15);
        color: #a78bfa;
        border: 1px solid rgba(167, 139, 250, 0.3);
    }

    .profile-hero-info .role-badge .material-symbols-rounded {
        font-size: 14px;
    }

    .profile-hero-info .profile-meta {
        display: flex;
        gap: 20px;
        flex-wrap: wrap;
    }

    .profile-meta-item {
        display: flex;
        align-items: center;
        gap: 6px;
        font-size: 13px;
        color: var(--text-secondary);
    }

    .profile-meta-item .material-symbols-rounded {
        font-size: 16px;
        color: var(--text-muted);
    }

    /* === ALERT MESSAGES === */
    .alert-msg {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 16px 20px;
        border-radius: var(--radius-md);
        margin-bottom: 20px;
        font-size: 14px;
        font-weight: 500;
        animation: fadeInUp 0.3s ease;
    }

    .alert-msg .material-symbols-rounded {
        font-size: 22px;
        flex-shrink: 0;
    }

    .alert-success {
        background: var(--success-bg);
        color: var(--success);
        border: 1px solid rgba(52, 211, 153, 0.3);
    }

    .alert-error {
        background: var(--danger-bg);
        color: var(--danger);
        border: 1px solid rgba(248, 113, 113, 0.3);
    }

    /* === FORM CARDS === */
    .profile-card {
        background: var(--bg-card);
        backdrop-filter: blur(12px);
        padding: 32px;
        border-radius: var(--radius-xl);
        margin-bottom: 24px;
        border: 1px solid var(--glass-border);
        transition: all 0.3s;
    }

    .profile-card:hover {
        border-color: rgba(167, 139, 250, 0.25);
        box-shadow: 0 4px 20px rgba(0,0,0,0.2);
    }

    .card-title {
        font-size: 1.15rem;
        font-weight: 700;
        color: var(--text-primary);
        margin-bottom: 24px;
        padding-bottom: 14px;
        border-bottom: 1px dashed var(--glass-border);
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .card-title .material-symbols-rounded {
        color: #a78bfa;
        font-size: 22px;
    }

    .card-title .card-subtitle {
        font-size: 12px;
        font-weight: 400;
        color: var(--text-muted);
        margin-left: auto;
    }

    /* === FORM INPUTS === */
    .input-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 24px;
    }

    .form-group {
        display: flex;
        flex-direction: column;
    }

    .form-group label {
        display: block;
        font-weight: 600;
        color: var(--text-secondary);
        margin-bottom: 10px;
        font-size: 12px;
        text-transform: uppercase;
        letter-spacing: 0.8px;
    }

    .form-group label .required {
        color: var(--danger);
        margin-left: 2px;
    }

    .custom-input {
        width: 100%;
        padding: 14px 16px;
        background: rgba(0, 0, 0, 0.2);
        border: 1px solid var(--glass-border);
        border-radius: var(--radius-md);
        outline: none;
        transition: all 0.3s;
        color: var(--text-primary);
        font-family: inherit;
        font-size: 14px;
    }

    .custom-input:focus {
        border-color: #a78bfa;
        background: rgba(167, 139, 250, 0.05);
        box-shadow: 0 0 0 3px rgba(167, 139, 250, 0.15);
    }

    .custom-input:disabled,
    .custom-input[readonly] {
        opacity: 0.5;
        cursor: not-allowed;
        background: rgba(0, 0, 0, 0.1);
    }

    .input-hint {
        font-size: 11px;
        color: var(--text-muted);
        margin-top: 6px;
        display: flex;
        align-items: center;
        gap: 4px;
    }

    .input-hint .material-symbols-rounded {
        font-size: 14px;
    }

    /* === FILE UPLOAD === */
    .avatar-upload-area {
        display: flex;
        align-items: center;
        gap: 20px;
        padding: 20px;
        background: rgba(0, 0, 0, 0.15);
        border: 2px dashed rgba(167, 139, 250, 0.25);
        border-radius: var(--radius-lg);
        transition: all 0.3s;
        cursor: pointer;
    }

    .avatar-upload-area:hover {
        border-color: rgba(167, 139, 250, 0.5);
        background: rgba(167, 139, 250, 0.05);
    }

    .avatar-upload-area.dragover {
        border-color: #a78bfa;
        background: rgba(167, 139, 250, 0.1);
        box-shadow: 0 0 0 4px rgba(167, 139, 250, 0.1);
    }

    .avatar-preview-mini {
        width: 64px;
        height: 64px;
        border-radius: 50%;
        background: linear-gradient(135deg, var(--accent), #a78bfa);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 24px;
        font-weight: 700;
        color: white;
        overflow: hidden;
        flex-shrink: 0;
        box-shadow: 0 0 0 3px var(--bg-secondary), 0 0 0 4px rgba(167, 139, 250, 0.3);
    }

    .avatar-preview-mini img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    .upload-info {
        flex: 1;
    }

    .upload-info h4 {
        font-size: 14px;
        color: var(--text-primary);
        margin-bottom: 4px;
    }

    .upload-info p {
        font-size: 12px;
        color: var(--text-muted);
        line-height: 1.5;
    }

    .upload-info .upload-btn-inline {
        display: inline-flex;
        align-items: center;
        gap: 4px;
        color: #a78bfa;
        font-weight: 600;
        font-size: 13px;
        cursor: pointer;
        margin-top: 8px;
        transition: 0.2s;
    }

    .upload-info .upload-btn-inline:hover {
        color: var(--accent);
    }

    .upload-actions {
        display: flex;
        gap: 8px;
    }

    .btn-remove-avatar {
        padding: 8px 12px;
        background: var(--danger-bg);
        color: var(--danger);
        border: 1px solid rgba(248,113,113,0.2);
        border-radius: var(--radius-sm);
        cursor: pointer;
        font-size: 12px;
        font-weight: 600;
        display: flex;
        align-items: center;
        gap: 4px;
        transition: 0.3s;
        font-family: inherit;
    }

    .btn-remove-avatar:hover {
        background: rgba(248, 113, 113, 0.2);
    }

    /* === BUTTONS === */
    .btn-action {
        padding: 14px 28px;
        border-radius: var(--radius-md);
        font-weight: 600;
        cursor: pointer;
        border: none;
        transition: all 0.3s;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        font-family: inherit;
        font-size: 14px;
    }

    .btn-submit {
        background: linear-gradient(135deg, #a78bfa, #7c5cfc);
        color: white;
        box-shadow: 0 4px 16px rgba(167, 139, 250, 0.3);
    }

    .btn-submit:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 24px rgba(167, 139, 250, 0.4);
    }

    .btn-secondary {
        background: var(--bg-tertiary);
        color: var(--text-primary);
        border: 1px solid var(--glass-border);
    }

    .btn-secondary:hover {
        border-color: rgba(167, 139, 250, 0.3);
        background: rgba(167, 139, 250, 0.08);
    }

    .form-actions {
        display: flex;
        justify-content: flex-end;
        gap: 12px;
        margin-top: 28px;
        padding-top: 20px;
        border-top: 1px solid var(--glass-border);
    }

    /* === PASSWORD SECTION === */
    .password-grid {
        display: grid;
        grid-template-columns: 1fr;
        gap: 20px;
        max-width: 450px;
    }

    .password-input-wrapper {
        position: relative;
    }

    .password-toggle {
        position: absolute;
        right: 14px;
        top: 50%;
        transform: translateY(-50%);
        background: none;
        border: none;
        color: var(--text-muted);
        cursor: pointer;
        padding: 4px;
        display: flex;
        align-items: center;
        transition: 0.2s;
    }

    .password-toggle:hover {
        color: #a78bfa;
    }

    .password-toggle .material-symbols-rounded {
        font-size: 20px;
    }

    /* === INFO STATS === */
    .info-stats {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
        gap: 16px;
        margin-bottom: 24px;
    }

    .info-stat-item {
        background: rgba(0, 0, 0, 0.15);
        border: 1px solid var(--glass-border);
        border-radius: var(--radius-md);
        padding: 16px;
        text-align: center;
        transition: 0.3s;
    }

    .info-stat-item:hover {
        border-color: rgba(167, 139, 250, 0.3);
        background: rgba(167, 139, 250, 0.05);
    }

    .info-stat-item .stat-icon {
        font-size: 28px;
        color: #a78bfa;
        margin-bottom: 8px;
    }

    .info-stat-item .stat-label {
        font-size: 11px;
        color: var(--text-muted);
        text-transform: uppercase;
        letter-spacing: 0.5px;
        margin-bottom: 4px;
    }

    .info-stat-item .stat-value {
        font-size: 14px;
        font-weight: 700;
        color: var(--text-primary);
    }

    /* === TABS === */
    .profile-tabs {
        display: flex;
        gap: 4px;
        margin-bottom: 24px;
        background: rgba(0, 0, 0, 0.15);
        padding: 4px;
        border-radius: var(--radius-md);
        border: 1px solid var(--glass-border);
    }

    .profile-tab {
        flex: 1;
        padding: 12px 20px;
        text-align: center;
        font-size: 13px;
        font-weight: 600;
        color: var(--text-secondary);
        cursor: pointer;
        border-radius: var(--radius-sm);
        transition: all 0.3s;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        border: none;
        background: transparent;
        font-family: inherit;
    }

    .profile-tab:hover {
        color: var(--text-primary);
        background: rgba(255, 255, 255, 0.03);
    }

    .profile-tab.active {
        background: linear-gradient(135deg, rgba(167, 139, 250, 0.2), rgba(124, 92, 252, 0.1));
        color: #a78bfa;
        box-shadow: 0 2px 8px rgba(167, 139, 250, 0.15);
    }

    .profile-tab .material-symbols-rounded {
        font-size: 18px;
    }

    .tab-content {
        display: none;
        animation: fadeInUp 0.3s ease;
    }

    .tab-content.active {
        display: block;
    }

    /* === RESPONSIVE === */
    @media (max-width: 768px) {
        .profile-hero {
            flex-direction: column;
            text-align: center;
            padding: 28px;
        }
        .profile-hero-info .profile-meta {
            justify-content: center;
        }
        .input-grid {
            grid-template-columns: 1fr;
        }
        .info-stats {
            grid-template-columns: 1fr 1fr;
        }
    }
</style>

<div class="main-wrapper">
    <?php include 'includes/topbar.php'; ?>

    <div class="content">
        <div class="form-center-container">

            <!-- Alert Message -->
            <?php if (!empty($msg)): ?>
                <div class="alert-msg alert-<?= $msg_type ?>">
                    <span class="material-symbols-rounded"><?= $msg_type == 'success' ? 'check_circle' : 'error' ?></span>
                    <?= htmlspecialchars($msg) ?>
                </div>
            <?php endif; ?>

            <!-- ===== PROFILE HERO ===== -->
            <div class="profile-hero">
                <div class="profile-avatar-wrapper">
                    <div class="profile-avatar">
                        <?php if (!empty($user['anhDaiDien']) && file_exists($user['anhDaiDien'])): ?>
                            <img src="<?= htmlspecialchars($user['anhDaiDien']) ?>" alt="Avatar">
                        <?php else: ?>
                            <?= $avatar_letter ?>
                        <?php endif; ?>
                    </div>
                    <label for="quickAvatarInput" class="avatar-edit-overlay" title="Thay đổi ảnh đại diện">
                        <span class="material-symbols-rounded">photo_camera</span>
                    </label>
                </div>
                <div class="profile-hero-info">
                    <h1><?= htmlspecialchars($user['hoTen']) ?></h1>
                    <div class="role-badge">
                        <span class="material-symbols-rounded">shield_person</span>
                        <?= htmlspecialchars($role_name) ?>
                    </div>
                    <div class="profile-meta">
                        <div class="profile-meta-item">
                            <span class="material-symbols-rounded">badge</span>
                            @<?= htmlspecialchars($user['tenDangNhap']) ?>
                        </div>
                        <?php if (!empty($user['emailNV'])): ?>
                        <div class="profile-meta-item">
                            <span class="material-symbols-rounded">mail</span>
                            <?= htmlspecialchars($user['emailNV']) ?>
                        </div>
                        <?php endif; ?>
                        <?php if (!empty($user['soDienThoai'])): ?>
                        <div class="profile-meta-item">
                            <span class="material-symbols-rounded">phone</span>
                            <?= htmlspecialchars($user['soDienThoai']) ?>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- ===== INFO STATS ===== -->
            <div class="info-stats">
                <div class="info-stat-item">
                    <div class="stat-icon"><span class="material-symbols-rounded">person</span></div>
                    <div class="stat-label">Tên đăng nhập</div>
                    <div class="stat-value"><?= htmlspecialchars($user['tenDangNhap']) ?></div>
                </div>
                <div class="info-stat-item">
                    <div class="stat-icon"><span class="material-symbols-rounded">work</span></div>
                    <div class="stat-label">Vai trò</div>
                    <div class="stat-value"><?= htmlspecialchars($role_name) ?></div>
                </div>
                <div class="info-stat-item">
                    <div class="stat-icon"><span class="material-symbols-rounded">event</span></div>
                    <div class="stat-label">Ngày tạo tài khoản</div>
                    <div class="stat-value"><?= $user['createAt'] ? date('d/m/Y', strtotime($user['createAt'])) : 'N/A' ?></div>
                </div>
                <div class="info-stat-item">
                    <div class="stat-icon"><span class="material-symbols-rounded">verified</span></div>
                    <div class="stat-label">Trạng thái</div>
                    <div class="stat-value" style="color: var(--success);">
                        <?= htmlspecialchars($user['trangThai'] ?? 'Hoạt động') ?>
                    </div>
                </div>
            </div>

            <!-- ===== TABS ===== -->
            <div class="profile-tabs">
                <button class="profile-tab active" onclick="switchTab('info')" id="tab-info">
                    <span class="material-symbols-rounded">edit</span> Thông tin cá nhân
                </button>
                <button class="profile-tab" onclick="switchTab('password')" id="tab-password">
                    <span class="material-symbols-rounded">lock</span> Đổi mật khẩu
                </button>
                <button class="profile-tab" onclick="switchTab('avatar')" id="tab-avatar">
                    <span class="material-symbols-rounded">account_circle</span> Ảnh đại diện
                </button>
            </div>

            <!-- ===== TAB 1: THÔNG TIN CÁ NHÂN ===== -->
            <div class="tab-content active" id="content-info">
                <form method="POST" enctype="multipart/form-data">
                    <div class="profile-card">
                        <div class="card-title">
                            <span class="material-symbols-rounded">person</span>
                            Thông tin cơ bản
                            <span class="card-subtitle">Chỉnh sửa thông tin hiển thị của bạn</span>
                        </div>
                        <div class="input-grid">
                            <div class="form-group">
                                <label>Họ và tên <span class="required">*</span></label>
                                <input type="text" name="hoTen" class="custom-input" 
                                       value="<?= htmlspecialchars($user['hoTen']) ?>" required
                                       placeholder="Nhập họ và tên đầy đủ">
                            </div>
                            <div class="form-group">
                                <label>Tên đăng nhập</label>
                                <input type="text" class="custom-input" value="<?= htmlspecialchars($user['tenDangNhap']) ?>" disabled>
                                <div class="input-hint">
                                    <span class="material-symbols-rounded">info</span>
                                    Tên đăng nhập không thể thay đổi
                                </div>
                            </div>
                        </div>

                        <div class="input-grid" style="margin-top: 24px;">
                            <div class="form-group">
                                <label>Số điện thoại</label>
                                <input type="tel" name="soDienThoai" class="custom-input" 
                                       value="<?= htmlspecialchars($user['soDienThoai'] ?? '') ?>"
                                       placeholder="Ví dụ: 0912 345 678">
                            </div>
                            <div class="form-group">
                                <label>Email</label>
                                <input type="email" name="emailNV" class="custom-input" 
                                       value="<?= htmlspecialchars($user['emailNV'] ?? '') ?>"
                                       placeholder="Ví dụ: ten@email.com">
                            </div>
                        </div>
                    </div>

                    <div class="form-actions">
                        <a href="index.php" class="btn-action btn-secondary">
                            <span class="material-symbols-rounded">arrow_back</span> Quay lại
                        </a>
                        <button type="submit" name="btnCapNhat" class="btn-action btn-submit">
                            <span class="material-symbols-rounded">save</span> Lưu thay đổi
                        </button>
                    </div>
                </form>
            </div>

            <!-- ===== TAB 2: ĐỔI MẬT KHẨU ===== -->
            <div class="tab-content" id="content-password">
                <form method="POST">
                    <div class="profile-card">
                        <div class="card-title">
                            <span class="material-symbols-rounded">lock</span>
                            Bảo mật tài khoản
                            <span class="card-subtitle">Thay đổi mật khẩu đăng nhập</span>
                        </div>

                        <div class="password-grid">
                            <div class="form-group">
                                <label>Mật khẩu hiện tại <span class="required">*</span></label>
                                <div class="password-input-wrapper">
                                    <input type="password" name="matKhauCu" class="custom-input" required
                                           placeholder="Nhập mật khẩu hiện tại" id="pwCurrent">
                                    <button type="button" class="password-toggle" onclick="togglePw('pwCurrent')">
                                        <span class="material-symbols-rounded">visibility</span>
                                    </button>
                                </div>
                            </div>
                            <div class="form-group">
                                <label>Mật khẩu mới <span class="required">*</span></label>
                                <div class="password-input-wrapper">
                                    <input type="password" name="matKhauMoi" class="custom-input" required
                                           placeholder="Tối thiểu 4 ký tự" id="pwNew" minlength="4">
                                    <button type="button" class="password-toggle" onclick="togglePw('pwNew')">
                                        <span class="material-symbols-rounded">visibility</span>
                                    </button>
                                </div>
                            </div>
                            <div class="form-group">
                                <label>Xác nhận mật khẩu mới <span class="required">*</span></label>
                                <div class="password-input-wrapper">
                                    <input type="password" name="xacNhanMatKhau" class="custom-input" required
                                           placeholder="Nhập lại mật khẩu mới" id="pwConfirm">
                                    <button type="button" class="password-toggle" onclick="togglePw('pwConfirm')">
                                        <span class="material-symbols-rounded">visibility</span>
                                    </button>
                                </div>
                                <div class="input-hint" id="pwMatchHint" style="display: none;">
                                    <span class="material-symbols-rounded">error</span>
                                    <span>Mật khẩu xác nhận không khớp</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="form-actions">
                        <a href="index.php" class="btn-action btn-secondary">
                            <span class="material-symbols-rounded">arrow_back</span> Quay lại
                        </a>
                        <button type="submit" name="btnDoiMatKhau" class="btn-action btn-submit">
                            <span class="material-symbols-rounded">vpn_key</span> Đổi mật khẩu
                        </button>
                    </div>
                </form>
            </div>

            <!-- ===== TAB 3: ẢNH ĐẠI DIỆN ===== -->
            <div class="tab-content" id="content-avatar">
                <form method="POST" enctype="multipart/form-data" id="avatarForm">
                    <div class="profile-card">
                        <div class="card-title">
                            <span class="material-symbols-rounded">account_circle</span>
                            Ảnh đại diện
                            <span class="card-subtitle">Tải lên ảnh đại diện mới</span>
                        </div>

                        <div class="avatar-upload-area" id="dropZone" onclick="document.getElementById('avatarInput').click()">
                            <div class="avatar-preview-mini" id="avatarPreview">
                                <?php if (!empty($user['anhDaiDien']) && file_exists($user['anhDaiDien'])): ?>
                                    <img src="<?= htmlspecialchars($user['anhDaiDien']) ?>" alt="Avatar" id="previewImg">
                                <?php else: ?>
                                    <span id="previewLetter"><?= $avatar_letter ?></span>
                                <?php endif; ?>
                            </div>
                            <div class="upload-info">
                                <h4>Chọn ảnh đại diện mới</h4>
                                <p>Kéo thả ảnh vào đây hoặc nhấn để chọn file.<br>
                                   Hỗ trợ: JPG, PNG, GIF, WebP — Tối đa 5MB</p>
                                <div class="upload-btn-inline">
                                    <span class="material-symbols-rounded">cloud_upload</span>
                                    Chọn ảnh từ máy tính
                                </div>
                            </div>
                            <?php if (!empty($user['anhDaiDien']) && file_exists($user['anhDaiDien'])): ?>
                            <div class="upload-actions" onclick="event.stopPropagation();">
                                <button type="button" class="btn-remove-avatar" onclick="removeAvatar()">
                                    <span class="material-symbols-rounded" style="font-size: 14px;">delete</span> Xóa ảnh
                                </button>
                            </div>
                            <?php endif; ?>
                        </div>
                        <input type="file" id="avatarInput" name="anhDaiDien" accept="image/*" 
                               style="display: none;" onchange="previewAvatar(this)">
                        <!-- Hidden quick avatar input for hero overlay button -->
                        <input type="file" id="quickAvatarInput" accept="image/*" 
                               style="display: none;" onchange="document.getElementById('avatarInput').files = this.files; previewAvatar(this); switchTab('avatar');">
                    </div>

                    <div class="form-actions">
                        <a href="index.php" class="btn-action btn-secondary">
                            <span class="material-symbols-rounded">arrow_back</span> Quay lại
                        </a>
                        <button type="submit" name="btnCapNhatAnh" class="btn-action btn-submit" id="btnSaveAvatar">
                            <span class="material-symbols-rounded">save</span> Lưu ảnh đại diện
                        </button>
                    </div>
                </form>
            </div>

        </div>
    </div>
</div>

<script>
// === TAB SWITCHING ===
function switchTab(tabName) {
    // Remove active from all tabs and contents
    document.querySelectorAll('.profile-tab').forEach(t => t.classList.remove('active'));
    document.querySelectorAll('.tab-content').forEach(c => c.classList.remove('active'));
    
    // Activate selected tab
    document.getElementById('tab-' + tabName).classList.add('active');
    document.getElementById('content-' + tabName).classList.add('active');
}

// === PASSWORD TOGGLE ===
function togglePw(inputId) {
    const input = document.getElementById(inputId);
    const icon = input.nextElementSibling.querySelector('.material-symbols-rounded');
    
    if (input.type === 'password') {
        input.type = 'text';
        icon.textContent = 'visibility_off';
    } else {
        input.type = 'password';
        icon.textContent = 'visibility';
    }
}

// === PASSWORD MATCH CHECK ===
document.getElementById('pwConfirm')?.addEventListener('input', function() {
    const pwNew = document.getElementById('pwNew').value;
    const hint = document.getElementById('pwMatchHint');
    
    if (this.value && this.value !== pwNew) {
        hint.style.display = 'flex';
        hint.querySelector('span:last-child').textContent = 'Mật khẩu xác nhận không khớp';
        hint.style.color = 'var(--danger)';
    } else if (this.value && this.value === pwNew) {
        hint.style.display = 'flex';
        hint.querySelector('.material-symbols-rounded').textContent = 'check_circle';
        hint.querySelector('span:last-child').textContent = 'Mật khẩu khớp!';
        hint.style.color = 'var(--success)';
    } else {
        hint.style.display = 'none';
    }
});

// === AVATAR PREVIEW ===
function previewAvatar(input) {
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) {
            const preview = document.getElementById('avatarPreview');
            preview.innerHTML = '<img src="' + e.target.result + '" alt="Preview" style="width:100%;height:100%;object-fit:cover;">';
            
            // Also update hero avatar
            const heroAvatar = document.querySelector('.profile-avatar');
            heroAvatar.innerHTML = '<img src="' + e.target.result + '" alt="Preview">';
        };
        reader.readAsDataURL(input.files[0]);
    }
}

// === DRAG & DROP ===
const dropZone = document.getElementById('dropZone');
if (dropZone) {
    ['dragenter', 'dragover'].forEach(evt => {
        dropZone.addEventListener(evt, (e) => {
            e.preventDefault();
            dropZone.classList.add('dragover');
        });
    });
    
    ['dragleave', 'drop'].forEach(evt => {
        dropZone.addEventListener(evt, (e) => {
            e.preventDefault();
            dropZone.classList.remove('dragover');
        });
    });
    
    dropZone.addEventListener('drop', (e) => {
        const files = e.dataTransfer.files;
        if (files.length > 0) {
            document.getElementById('avatarInput').files = files;
            previewAvatar(document.getElementById('avatarInput'));
        }
    });
}

// === REMOVE AVATAR ===
function removeAvatar() {
    if (!confirm('Bạn có chắc muốn xóa ảnh đại diện?')) return;
    
    const formData = new FormData();
    formData.append('action', 'remove_avatar');
    
    fetch('thongtin_canhan.php', { method: 'POST', body: formData })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            window.location.reload();
        }
    });
}
</script>

<?php include 'includes/footer.php'; ?>
