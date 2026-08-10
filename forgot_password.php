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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Khôi phục mật khẩu - Kho Đàn Piano Nguyễn Duy</title>
    
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
                radial-gradient(circle at 85% 50%, rgba(124, 92, 252, 0.15), transparent 25%),
                radial-gradient(circle at 15% 30%, rgba(94, 234, 212, 0.1), transparent 25%);
            height: 100vh; 
            display: flex; 
            justify-content: center; 
            align-items: center; 
            color: var(--text-primary);
            overflow: hidden;
            position: relative;
        }

        body::before {
            content: '';
            position: absolute;
            top: 0; left: 0; width: 100%; height: 100%;
            background-image: url('https://images.unsplash.com/photo-1552422535-c45813c61732?q=80&w=2070&auto=format&fit=crop');
            background-size: cover;
            background-position: center;
            opacity: 0.15;
            mix-blend-mode: luminosity;
            transform: scaleX(-1); /* Flip horizontally just to look a bit different from login */
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

        .login-container {
            position: relative;
            animation: float 6s ease-in-out infinite;
        }

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

        .brand { text-align: center; margin-bottom: 32px; }
        .brand .material-symbols-rounded { 
            font-size: 64px; 
            background: linear-gradient(135deg, var(--accent), var(--accent-secondary));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 16px; 
            filter: drop-shadow(0 4px 8px rgba(124, 92, 252, 0.3));
        }
        .brand h2 { font-size: 26px; font-weight: 800; letter-spacing: -0.5px; margin-bottom: 8px; }
        .brand p { color: var(--text-secondary); font-size: 15px; }

        .error { 
            color: #f87171; background: rgba(248, 113, 113, 0.1); border: 1px solid rgba(248, 113, 113, 0.2);
            padding: 14px 16px; border-radius: 12px; font-size: 14px; font-weight: 500; margin-bottom: 24px; 
            display: flex; align-items: center; gap: 10px;
        }

        .success { 
            color: #34d399; background: rgba(52, 211, 153, 0.1); border: 1px solid rgba(52, 211, 153, 0.2);
            padding: 14px 16px; border-radius: 12px; font-size: 14px; font-weight: 500; margin-bottom: 24px; 
            display: flex; align-items: center; gap: 10px;
        }

        .input-group { margin-bottom: 20px; }
        .input-group label { display: block; color: var(--text-secondary); font-size: 13px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 8px; }
        .input-wrapper { position: relative; display: flex; align-items: center; }
        .input-wrapper .material-symbols-rounded { position: absolute; left: 18px; color: var(--text-secondary); font-size: 20px; transition: 0.3s; }
        
        .input-wrapper input {
            width: 100%; padding: 16px 16px 16px 52px; background: rgba(0, 0, 0, 0.2); border: 1px solid var(--glass-border);
            border-radius: 16px; font-size: 15px; color: var(--text-primary); transition: all 0.3s; outline: none;
            font-family: inherit;
        }
        .input-wrapper input::placeholder { color: #4b455b; }
        .input-wrapper input:focus { border-color: var(--accent); background: rgba(124, 92, 252, 0.05); box-shadow: 0 0 0 4px rgba(124, 92, 252, 0.15); }
        .input-wrapper input:focus + .material-symbols-rounded { color: var(--accent); transform: scale(1.1); }

        .form-options { display: flex; justify-content: center; margin-top: 24px; }
        .back-link {
            color: var(--text-secondary); font-size: 14px; font-weight: 600; text-decoration: none;
            transition: all 0.2s ease; display: flex; align-items: center; gap: 6px;
        }
        .back-link:hover { color: var(--text-primary); }
        .back-link .material-symbols-rounded { font-size: 18px; transition: transform 0.2s; }
        .back-link:hover .material-symbols-rounded { transform: translateX(-4px); }

        button { 
            width: 100%; padding: 16px; background: linear-gradient(135deg, var(--accent), #9061f9); color: white; border: none; 
            border-radius: 16px; font-size: 16px; font-weight: 700; cursor: pointer; 
            transition: all 0.3s; display: flex; justify-content: center; align-items: center; gap: 10px;
            box-shadow: 0 8px 20px rgba(124, 92, 252, 0.3); margin-top: 8px; position: relative; overflow: hidden;
        }
        button::before {
            content: ''; position: absolute; top: 0; left: -100%; width: 100%; height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent); transition: 0.5s;
        }
        button:hover { transform: translateY(-2px); box-shadow: 0 12px 28px rgba(124, 92, 252, 0.5); }
        button:hover::before { left: 100%; }
        button .material-symbols-rounded { transition: transform 0.3s; }
        button:hover .material-symbols-rounded { transform: scale(1.1); }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-wrapper">
            <div class="brand">
                <span class="material-symbols-rounded">lock_reset</span>
                <h2>Khôi phục mật khẩu</h2>
                <p>Vui lòng xác minh thông tin tài khoản</p>
            </div>
            
            <?php if($message != ""): ?>
                <div class="<?php echo $msg_type; ?>">
                    <span class="material-symbols-rounded">
                        <?php echo ($msg_type == 'error') ? 'error' : 'check_circle'; ?>
                    </span>
                    <?php echo $message; ?>
                </div>
            <?php endif; ?>
            
            <?php if($msg_type != 'success'): ?>
            <form method="POST" action="">
                <div class="input-group">
                    <label>Tên đăng nhập</label>
                    <div class="input-wrapper">
                        <input type="text" name="username" placeholder="Nhập tên đăng nhập của bạn" required>
                        <span class="material-symbols-rounded">person</span>
                    </div>
                </div>
                
                <div class="input-group">
                    <label>Email xác minh</label>
                    <div class="input-wrapper">
                        <input type="email" name="email" placeholder="Nhập Email đã đăng ký" required>
                        <span class="material-symbols-rounded">mail</span>
                    </div>
                </div>

                <div class="input-group">
                    <label>Mật khẩu mới</label>
                    <div class="input-wrapper">
                        <input type="password" name="new_password" placeholder="Nhập mật khẩu mới" required>
                        <span class="material-symbols-rounded">key</span>
                    </div>
                </div>
                
                <button type="submit">
                    Xác nhận đổi mật khẩu
                    <span class="material-symbols-rounded">done_all</span>
                </button>
            </form>
            <?php endif; ?>

            <div class="form-options">
                <a href="login.php" class="back-link">
                    <span class="material-symbols-rounded">arrow_back</span>
                    Quay lại đăng nhập
                </a>
            </div>
        </div>
    </div>
</body>
</html>