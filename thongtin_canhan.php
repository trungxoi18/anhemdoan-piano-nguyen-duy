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
        $maxSize = 5 * 1024 * 1024; // 5MB
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $allowed_exts = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        
        $is_image = false;
        if (in_array($ext, $allowed_exts) && file_exists($file['tmp_name'])) {
            $img_info = @getimagesize($file['tmp_name']);
            if ($img_info !== false) {
                $is_image = true;
            }
        }
        
        if (!$is_image) {
            $msg = "File tải lên không hợp lệ hoặc không phải là hình ảnh (chỉ chấp nhận JPG, PNG, GIF, WebP)!";
            $msg_type = "error";
        } elseif ($file['size'] > $maxSize) {
            $msg = "Dung lượng ảnh không được vượt quá 5MB!";
            $msg_type = "error";
        } else {
            // Tạo tên file unique
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
