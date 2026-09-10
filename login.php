<?php
// Chỉ cần require db.php là hệ thống sẽ tự động lo việc kết nối CSDL và bật Session an toàn
require_once 'db.php';
require_once 'functions.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $user = $_POST['username'];
    $pass = $_POST['password'];

    // Truy vấn kết hợp bảng TaiKhoan và NhanVien
    $sql = "SELECT tk.MaTaiKhoan, tk.TenDangNhap, nv.HoTen, tk.MaVaiTro 
            FROM TaiKhoan tk
            JOIN NhanVien nv ON tk.MaNhanVien = nv.MaNhanVien
            WHERE tk.TenDangNhap = ? AND tk.MatKhau = ? AND tk.TrangThai = 'Hoạt động'";

    $stmt = $conn->prepare($sql);
    
    // Ràng buộc tham số
    $stmt->bind_param("ss", $user, $pass);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        
        // Lưu thông tin vào Session
        $_SESSION['user_id'] = $row['MaTaiKhoan'];
        $_SESSION['username'] = $row['TenDangNhap'];
        $_SESSION['fullname'] = $row['HoTen'];
        $_SESSION['role_id'] = $row['MaVaiTro'];

        // Ghi log
        writeLog($conn, 'ĐĂNG NHẬP', 'Đăng nhập vào hệ thống thành công');

        // Chuyển hướng về trang chủ
        header("Location: index.php");
        exit();
    } else {
        $error = "Tài khoản/mật khẩu không đúng hoặc đã bị khóa!";
    }
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
    <meta name="theme-color" content="#0ea5e9">
    <title>Đăng nhập hệ thống - Kho Đàn Piano Nguyễn Duy</title>
    
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Rounded:opsz,wght,FILL,GRAD@24,400,0,0" />
    <link rel="stylesheet" href="assets/css/style.css">
    
    
</head>
<body class="auth-body">
    <div class="auth-container">
        <div class="auth-card">
            <div class="auth-brand">
                <span class="material-symbols-rounded">piano</span>
                <h2>Kho Đàn Nguyễn Duy</h2>
                <p>Đăng nhập để vào bảng điều khiển</p>
            </div>
            
            <?php if(isset($error)): ?>
                <div class="alert alert-error">
                    <span class="material-symbols-rounded">error</span>
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <div class="form-group">
                    <label>Tên đăng nhập</label>
                    <div class="input-wrapper">
                        <input type="text" name="username" class="form-control" placeholder="Nhập mã nhân viên hoặc username" required>
                        <span class="material-symbols-rounded">person</span>
                    </div>
                </div>
                
                <div class="form-group" style="margin-bottom: 12px;">
                    <label>Mật khẩu</label>
                    <div class="input-wrapper">
                        <input type="password" name="password" class="form-control" placeholder="Nhập mật khẩu của bạn" required>
                        <span class="material-symbols-rounded">lock</span>
                    </div>
                </div>
                
                <div class="auth-links" style="margin-bottom: 24px;">
                    <a href="register.php">Đăng ký tài khoản</a>
                    <a href="forgot_password.php">Quên mật khẩu?</a>
                </div>
                
                <button type="submit" class="btn btn-primary" style="width: 100%; padding: 14px; font-size: 15px;">
                    Đăng nhập
                    <span class="material-symbols-rounded">arrow_forward</span>
                </button>
            </form>
        </div>
    </div>
</body>
</html>