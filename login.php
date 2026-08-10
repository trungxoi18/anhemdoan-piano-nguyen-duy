<?php
// Chỉ cần require db.php là hệ thống sẽ tự động lo việc kết nối CSDL và bật Session an toàn
require_once 'db.php';
require_once 'functions.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $user = $_POST['username'];
    $pass = $_POST['password'];

    // Truy vấn kết hợp bảng TaiKhoan và NhanVien
    $sql = "SELECT tk.MaTaiKhoan, nv.HoTen, tk.MaVaiTro 
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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đăng nhập hệ thống - Kho Đàn Piano Nguyễn Duy</title>
    
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Rounded:opsz,wght,FILL,GRAD@24,400,0,0" />
    
    <style>
        :root {
            --bg-primary: #0f0d1a;
            --accent: #7c5cfc;
            --accent-glow: rgba(124, 92, 252, 0.4);
            --accent-secondary: #5eead4;
            --glass-bg: rgba(26, 22, 37, 0.6);
            --glass-border: rgba(255, 255, 255, 0.08);
            --text-primary: #f1f0f5;
            --text-secondary: #8b8698;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Inter', sans-serif; }
        
        body { 
            background: var(--bg-primary);
            background-image: 
                radial-gradient(circle at 15% 50%, rgba(124, 92, 252, 0.15), transparent 25%),
                radial-gradient(circle at 85% 30%, rgba(94, 234, 212, 0.1), transparent 25%);
            height: 100vh; 
            display: flex; 
            justify-content: center; 
            align-items: center; 
            color: var(--text-primary);
            overflow: hidden;
            position: relative;
        }

        /* Piano background pattern */
        body::before {
            content: '';
            position: absolute;
            top: 0; left: 0; width: 100%; height: 100%;
            background-image: url('https://images.unsplash.com/photo-1552422535-c45813c61732?q=80&w=2070&auto=format&fit=crop');
            background-size: cover;
            background-position: center;
            opacity: 0.15;
            mix-blend-mode: luminosity;
            z-index: -1;
        }

        @keyframes float {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-10px); }
        }

        @keyframes pulse-glow {
            0%, 100% { box-shadow: 0 0 30px rgba(124, 92, 252, 0.2); }
            50% { box-shadow: 0 0 50px rgba(124, 92, 252, 0.4), 0 0 100px rgba(94, 234, 212, 0.2); }
        }

        @keyframes border-rotate {
            100% { transform: rotate(1turn); }
        }

        .login-container {
            position: relative;
            animation: float 6s ease-in-out infinite;
        }

        /* Animated glowing border trick */
        .login-wrapper {
            position: relative;
            background: var(--glass-bg);
            backdrop-filter: blur(24px);
            -webkit-backdrop-filter: blur(24px);
            width: 100%;
            max-width: 440px;
            border-radius: 28px;
            padding: 48px 40px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.8);
            border: 1px solid var(--glass-border);
            z-index: 1;
            animation: pulse-glow 4s ease-in-out infinite;
        }

        .brand {
            text-align: center;
            margin-bottom: 40px;
        }
        
        .brand .material-symbols-rounded {
            font-size: 64px;
            background: linear-gradient(135deg, var(--accent), var(--accent-secondary));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 16px;
            filter: drop-shadow(0 4px 8px rgba(124, 92, 252, 0.3));
        }

        .brand h2 {
            font-size: 26px;
            font-weight: 800;
            letter-spacing: -0.5px;
            margin-bottom: 8px;
        }
        
        .brand p {
            color: var(--text-secondary);
            font-size: 15px;
        }

        .error { 
            color: #f87171; 
            background: rgba(248, 113, 113, 0.1); 
            border: 1px solid rgba(248, 113, 113, 0.2);
            padding: 14px 16px; 
            border-radius: 12px;
            font-size: 14px; 
            font-weight: 500;
            margin-bottom: 24px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .input-group {
            margin-bottom: 24px;
        }

        .input-group label {
            display: block;
            color: var(--text-secondary);
            font-size: 13px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 8px;
        }

        .input-wrapper {
            position: relative;
            display: flex;
            align-items: center;
        }

        .input-wrapper .material-symbols-rounded {
            position: absolute;
            left: 18px;
            color: var(--text-secondary);
            font-size: 20px;
            transition: 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .input-wrapper input {
            width: 100%;
            padding: 16px 16px 16px 52px;
            background: rgba(0, 0, 0, 0.2);
            border: 1px solid var(--glass-border);
            border-radius: 16px;
            font-size: 15px;
            color: var(--text-primary);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            outline: none;
            font-family: inherit;
        }

        .input-wrapper input::placeholder {
            color: #4b455b;
        }

        .input-wrapper input:focus {
            border-color: var(--accent);
            background: rgba(124, 92, 252, 0.05);
            box-shadow: 0 0 0 4px rgba(124, 92, 252, 0.15);
        }

        .input-wrapper input:focus + .material-symbols-rounded {
            color: var(--accent);
            transform: scale(1.1);
        }

        .form-options {
            display: flex;
            justify-content: flex-end;
            margin-top: -8px;
            margin-bottom: 32px;
        }

        .forgot-password-link {
            color: var(--accent-secondary);
            font-size: 13px;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.2s ease;
        }

        .forgot-password-link:hover {
            color: var(--accent);
            text-shadow: 0 0 8px var(--accent-glow);
        }

        button { 
            width: 100%; 
            padding: 16px; 
            background: linear-gradient(135deg, var(--accent), #9061f9);
            color: white; 
            border: none; 
            border-radius: 16px; 
            font-size: 16px;
            font-weight: 700; 
            cursor: pointer; 
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 10px;
            box-shadow: 0 8px 20px rgba(124, 92, 252, 0.3);
            position: relative;
            overflow: hidden;
        }
        
        button::before {
            content: '';
            position: absolute;
            top: 0; left: -100%;
            width: 100%; height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
            transition: 0.5s;
        }

        button:hover { 
            transform: translateY(-2px);
            box-shadow: 0 12px 28px rgba(124, 92, 252, 0.5);
        }

        button:hover::before {
            left: 100%;
        }

        button .material-symbols-rounded {
            transition: transform 0.3s;
        }

        button:hover .material-symbols-rounded {
            transform: translateX(4px);
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-wrapper">
            <div class="brand">
                <span class="material-symbols-rounded">piano</span>
                <h2>Kho Đàn Nguyễn Duy</h2>
                <p>Đăng nhập để vào bảng điều khiển</p>
            </div>
            
            <?php if(isset($error)): ?>
                <div class='error'>
                    <span class="material-symbols-rounded">error</span>
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <div class="input-group">
                    <label>Tên đăng nhập</label>
                    <div class="input-wrapper">
                        <input type="text" name="username" placeholder="Nhập mã nhân viên hoặc username" required>
                        <span class="material-symbols-rounded">person</span>
                    </div>
                </div>
                
                <div class="input-group" style="margin-bottom: 12px;">
                    <label>Mật khẩu</label>
                    <div class="input-wrapper">
                        <input type="password" name="password" placeholder="Nhập mật khẩu của bạn" required>
                        <span class="material-symbols-rounded">lock</span>
                    </div>
                </div>
                
                <div class="form-options">
                    <a href="forgot_password.php" class="forgot-password-link">Quên mật khẩu?</a>
                </div>
                
                <button type="submit">
                    Đăng nhập
                    <span class="material-symbols-rounded">arrow_forward</span>
                </button>
            </form>
        </div>
    </div>
</body>
</html>