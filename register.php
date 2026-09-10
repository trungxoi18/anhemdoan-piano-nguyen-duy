<?php
require_once 'db.php';
require_once 'functions.php';

$success = false;
$error = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $fullname = trim($_POST['fullname']);
    $phone = trim($_POST['phone']);
    $email = trim($_POST['email']);
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    // 1. Validate inputs
    if (empty($fullname) || empty($phone) || empty($email) || empty($username) || empty($password)) {
        $error = "Vui lòng nhập đầy đủ thông tin bắt buộc.";
    } elseif ($password !== $confirm_password) {
        $error = "Mật khẩu xác nhận không khớp.";
    } else {
        // 2. Check if username or email already exists
        $stmt_check = $conn->prepare("
            SELECT 
                (SELECT COUNT(*) FROM taikhoan WHERE TenDangNhap = ?) as count_user,
                (SELECT COUNT(*) FROM nhanvien WHERE EmailNV = ?) as count_email
        ");
        $stmt_check->bind_param("ss", $username, $email);
        $stmt_check->execute();
        $result_check = $stmt_check->get_result()->fetch_assoc();
        
        if ($result_check['count_user'] > 0) {
            $error = "Tên đăng nhập đã tồn tại. Vui lòng chọn tên khác.";
        } elseif ($result_check['count_email'] > 0) {
            $error = "Email đã được sử dụng. Vui lòng chọn email khác.";
        } else {
            // 3. Insert into NhanVien
            $conn->begin_transaction();
            try {
                $status = 'Chờ duyệt';
                
                $stmt_nv = $conn->prepare("INSERT INTO nhanvien (HoTen, SoDienThoai, EmailNV, TrangThai, createAt) VALUES (?, ?, ?, ?, NOW())");
                $stmt_nv->bind_param("ssss", $fullname, $phone, $email, $status);
                $stmt_nv->execute();
                
                $maNhanVien = $conn->insert_id;
                
                // 4. Insert into TaiKhoan (MaVaiTro để trống hoặc NULL tùy DB, ở đây dùng 0 tạm hoặc NULL nếu DB cho phép)
                // Assuming MaVaiTro can be NULL for pending users, or we set it to a dummy role.
                // Looking at DB, it might not accept NULL for MaVaiTro if it's strict, but let's try NULL.
                // Wait, if maVaiTro is int, it might default to 0. Let's use NULL.
                $stmt_tk = $conn->prepare("INSERT INTO taikhoan (TenDangNhap, MatKhau, MaNhanVien, MaVaiTro, TrangThai, NgayTao) VALUES (?, ?, ?, NULL, ?, NOW())");
                $stmt_tk->bind_param("ssis", $username, $password, $maNhanVien, $status);
                $stmt_tk->execute();
                
                // 5. Create Notification for Admins
                $admin_role_id = 1;
                $noti_content = "Có một yêu cầu đăng ký tài khoản mới từ " . $fullname . ".";
                $noti_link = "quanly_taikhoan.php";
                
                $stmt_noti = $conn->prepare("INSERT INTO thongbao (MaTaiKhoan, MaVaiTro, NoiDung, Link, DaDoc, NgayTao) VALUES (NULL, ?, ?, ?, 0, NOW())");
                $stmt_noti->bind_param("iss", $admin_role_id, $noti_content, $noti_link);
                $stmt_noti->execute();
                
                $conn->commit();
                $success = true;
                
            } catch (Exception $e) {
                $conn->rollback();
                $error = "Đã xảy ra lỗi trong quá trình đăng ký. Vui lòng thử lại sau.";
                // $error = $e->getMessage(); // For debugging
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
    <meta name="theme-color" content="#0ea5e9">
    <title>Đăng ký tài khoản - Kho Đàn Piano Nguyễn Duy</title>
    
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Rounded:opsz,wght,FILL,GRAD@24,400,0,0" />
    <link rel="stylesheet" href="assets/css/style.css">
    
    
</head>
<body class="auth-body">
    <div class="auth-container" style="max-width: 500px;">
        <div class="auth-card">
            <div class="auth-brand">
                <span class="material-symbols-rounded">piano</span>
                <h2>Kho Đàn Nguyễn Duy</h2>
                <p>Đăng ký tài khoản nhân viên mới</p>
            </div>
            
            <?php if($success): ?>
                <div class="alert alert-success">
                    <span class="material-symbols-rounded">check_circle</span>
                    Đăng ký thành công! Đơn đăng ký của bạn đang chờ Quản trị viên phê duyệt.
                </div>
                <div class="auth-links" style="justify-content: center;">
                    <a href="login.php" style="display: flex; align-items: center; gap: 6px;">
                        <span class="material-symbols-rounded" style="font-size: 18px;">arrow_back</span>
                        Quay lại trang Đăng nhập
                    </a>
                </div>
            <?php else: ?>
                <?php if(!empty($error)): ?>
                    <div class="alert alert-error">
                        <span class="material-symbols-rounded">error</span>
                        <?php echo $error; ?>
                    </div>
                <?php endif; ?>
                
                <form method="POST" action="">
                    <div class="form-grid">
                        <div class="form-group full-width">
                            <label>Họ và tên</label>
                            <div class="input-wrapper">
                                <input type="text" name="fullname" class="form-control" placeholder="Nhập họ và tên đầy đủ" value="<?php echo isset($_POST['fullname']) ? htmlspecialchars($_POST['fullname']) : ''; ?>" required>
                                <span class="material-symbols-rounded">badge</span>
                            </div>
                        </div>

                        <div class="form-group">
                            <label>Số điện thoại</label>
                            <div class="input-wrapper">
                                <input type="tel" name="phone" class="form-control" placeholder="Số điện thoại" value="<?php echo isset($_POST['phone']) ? htmlspecialchars($_POST['phone']) : ''; ?>" required>
                                <span class="material-symbols-rounded">phone</span>
                            </div>
                        </div>

                        <div class="form-group">
                            <label>Email</label>
                            <div class="input-wrapper">
                                <input type="email" name="email" class="form-control" placeholder="Địa chỉ email" value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>" required>
                                <span class="material-symbols-rounded">mail</span>
                            </div>
                        </div>

                        <div class="form-group full-width">
                            <label>Tên đăng nhập</label>
                            <div class="input-wrapper">
                                <input type="text" name="username" class="form-control" placeholder="Chọn tên đăng nhập (VD: nv_nguyenvana)" value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>" required>
                                <span class="material-symbols-rounded">person</span>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label>Mật khẩu</label>
                            <div class="input-wrapper">
                                <input type="password" name="password" class="form-control" placeholder="Nhập mật khẩu" required>
                                <span class="material-symbols-rounded">lock</span>
                            </div>
                        </div>

                        <div class="form-group">
                            <label>Xác nhận MK</label>
                            <div class="input-wrapper">
                                <input type="password" name="confirm_password" class="form-control" placeholder="Nhập lại mật khẩu" required>
                                <span class="material-symbols-rounded">lock_reset</span>
                            </div>
                        </div>
                    </div>
                    
                    <button type="submit" class="btn btn-primary" style="width: 100%; padding: 14px; font-size: 15px; margin-top: 10px;">
                        Đăng ký ngay
                        <span class="material-symbols-rounded">app_registration</span>
                    </button>

                    <div class="auth-links" style="justify-content: center; margin-top: 24px;">
                        <span style="color: var(--text-secondary); margin-right: 4px;">Đã có tài khoản?</span> <a href="login.php">Đăng nhập</a>
                    </div>
                </form>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
