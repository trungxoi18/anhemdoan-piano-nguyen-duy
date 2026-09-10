<?php
require_once 'db.php';

$message = "";
$msg_type = ""; 

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $user = trim($_POST['username']);
    $email = trim($_POST['email']);
    $new_pass = $_POST['new_password'];

    $sql_check = "SELECT tk.MaTaiKhoan 
                  FROM TaiKhoan tk
                  JOIN NhanVien nv ON tk.MaNhanVien = nv.MaNhanVien
                  WHERE tk.TenDangNhap = ? AND nv.emailNV = ? AND tk.TrangThai = 'Hoạt động'";
    
    $stmt_check = $conn->prepare($sql_check);
    $stmt_check->bind_param("ss", $user, $email);
    $stmt_check->execute();
    $result = $stmt_check->get_result();

    if ($result->num_rows > 0) {
    
        $row = $result->fetch_assoc();
        $maTaiKhoan = $row['MaTaiKhoan'];

        
        $sql_update = "UPDATE TaiKhoan SET MatKhau = ? WHERE MaTaiKhoan = ?";
        $stmt_update = $conn->prepare($sql_update);
        $stmt_update->bind_param("si", $new_pass, $maTaiKhoan);
        
        if ($stmt_update->execute()) {
            $msg_type = "success";
            $message = "Đổi mật khẩu thành công! Bạn có thể đăng nhập ngay.";
        } else {
            $msg_type = "error";
            $message = "Có lỗi hệ thống xảy ra khi cập nhật mật khẩu.";
        }
    } else {
        $msg_type = "error";
        $message = "Tên đăng nhập hoặc Email không chính xác!";
    }
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
    <meta name="theme-color" content="#0ea5e9">
    <title>Khôi phục mật khẩu - Kho Đàn Piano Nguyễn Duy</title>
    
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Rounded:opsz,wght,FILL,GRAD@24,400,0,0" />
    <link rel="stylesheet" href="assets/css/style.css">
    
    
</head>
<body class="auth-body">
    <div class="auth-container">
        <div class="auth-card">
            <div class="auth-brand">
                <span class="material-symbols-rounded">lock_reset</span>
                <h2>Khôi phục mật khẩu</h2>
                <p>Vui lòng xác minh thông tin tài khoản</p>
            </div>
            
            <?php if($message != ""): ?>
                <div class="alert alert-<?php echo ($msg_type == 'error') ? 'error' : 'success'; ?>">
                    <span class="material-symbols-rounded">
                        <?php echo ($msg_type == 'error') ? 'error' : 'check_circle'; ?>
                    </span>
                    <?php echo $message; ?>
                </div>
            <?php endif; ?>
            
            <?php if($msg_type != 'success'): ?>
            <form method="POST" action="">
                <div class="form-group">
                    <label>Tên đăng nhập</label>
                    <div class="input-wrapper">
                        <input type="text" name="username" class="form-control" placeholder="Nhập tên đăng nhập của bạn" required>
                        <span class="material-symbols-rounded">person</span>
                    </div>
                </div>
                
                <div class="form-group">
                    <label>Email xác minh</label>
                    <div class="input-wrapper">
                        <input type="email" name="email" class="form-control" placeholder="Nhập Email đã đăng ký" required>
                        <span class="material-symbols-rounded">mail</span>
                    </div>
                </div>

                <div class="form-group">
                    <label>Mật khẩu mới</label>
                    <div class="input-wrapper">
                        <input type="password" name="new_password" class="form-control" placeholder="Nhập mật khẩu mới" required>
                        <span class="material-symbols-rounded">key</span>
                    </div>
                </div>
                
                <button type="submit" class="btn btn-primary" style="width: 100%; padding: 14px; font-size: 15px; margin-top: 10px;">
                    Xác nhận đổi mật khẩu
                    <span class="material-symbols-rounded">done_all</span>
                </button>
            </form>
            <?php endif; ?>

            <div class="auth-links" style="justify-content: center; margin-top: 24px;">
                <a href="login.php" style="display: flex; align-items: center; gap: 6px;">
                    <span class="material-symbols-rounded" style="font-size: 18px;">arrow_back</span>
                    Quay lại đăng nhập
                </a>
            </div>
        </div>
    </div>
</body>
</html>