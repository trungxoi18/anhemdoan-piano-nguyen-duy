<?php
// Tải các cấu hình và hàm dùng chung
require_once 'db.php';
require_once 'functions.php';

// Kiểm tra đăng nhập
checkLogin();

// Lấy thông tin từ Session
$fullname = $_SESSION['fullname'];
$role = $_SESSION['role_id'];

// Map role_id với Tên vai trò và Class CSS
if ($role == 1) { 
    $role_name = "Quản trị viên"; 
    $role_class = "admin"; 
} else { 
    $role_name = "Thủ kho"; 
    $role_class = "staff"; 
}

// Lấy chữ cái đầu tiên của tên để làm Avatar
$avatar_letter = mb_substr($fullname, 0, 1, "UTF-8");

// Lấy ảnh đại diện nếu có
$user_avatar = null;
$uid_header = $_SESSION['user_id'] ?? 0;
$stmt_avatar = $conn->prepare("SELECT nv.anhDaiDien FROM NhanVien nv JOIN TaiKhoan tk ON tk.maNhanVien = nv.maNhanVien WHERE tk.maTaiKhoan = ?");
if ($stmt_avatar) {
    $stmt_avatar->bind_param("i", $uid_header);
    $stmt_avatar->execute();
    $res_avatar = $stmt_avatar->get_result();
    if ($row_avatar = $res_avatar->fetch_assoc()) {
        if (!empty($row_avatar['anhDaiDien']) && file_exists($row_avatar['anhDaiDien'])) {
            $user_avatar = $row_avatar['anhDaiDien'];
        }
    }
    $stmt_avatar->close();
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - KHO ĐÀN PRO</title>
    
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Rounded:opsz,wght,FILL,GRAD@24,400,0,0" />
    
    <style>
        /* ========================================
           DARK MODE PREMIUM DESIGN SYSTEM
           Kho Đàn Piano Nguyễn Duy
           ======================================== */

        :root {
            /* Core Dark Palette */
            --bg-primary: #0f0d1a;
            --bg-secondary: #1a1625;
            --bg-tertiary: #231f30;
            --bg-card: rgba(255, 255, 255, 0.04);
            --bg-card-hover: rgba(255, 255, 255, 0.07);
            --bg-card-solid: #1e1a2b;

            /* Accent Colors */
            --accent: #7c5cfc;
            --accent-hover: #6a4ce8;
            --accent-glow: rgba(124, 92, 252, 0.25);
            --accent-secondary: #5eead4;
            --accent-tertiary: #f472b6;

            /* Functional Colors */
            --success: #34d399;
            --success-bg: rgba(52, 211, 153, 0.12);
            --warning: #fbbf24;
            --warning-bg: rgba(251, 191, 36, 0.12);
            --danger: #f87171;
            --danger-bg: rgba(248, 113, 113, 0.12);
            --info: #60a5fa;
            --info-bg: rgba(96, 165, 250, 0.12);

            /* Text */
            --text-primary: #f1f0f5;
            --text-secondary: #8b8698;
            --text-muted: #5c5670;

            /* Borders & Surfaces */
            --border: rgba(255, 255, 255, 0.06);
            --border-hover: rgba(255, 255, 255, 0.12);
            --glass: rgba(255, 255, 255, 0.03);
            --glass-border: rgba(255, 255, 255, 0.08);

            /* Role Colors */
            --admin-bg: rgba(248, 113, 113, 0.12);
            --admin-text: #f87171;
            --staff-bg: rgba(52, 211, 153, 0.12);
            --staff-text: #34d399;
            --sales-bg: rgba(96, 165, 250, 0.12);
            --sales-text: #60a5fa;

            /* Shadows */
            --shadow-sm: 0 2px 8px rgba(0, 0, 0, 0.3);
            --shadow-md: 0 4px 16px rgba(0, 0, 0, 0.4);
            --shadow-lg: 0 8px 32px rgba(0, 0, 0, 0.5);
            --shadow-glow: 0 0 20px var(--accent-glow);

            /* Sizing */
            --sidebar-width: 270px;
            --topbar-height: 72px;
            --radius-sm: 8px;
            --radius-md: 12px;
            --radius-lg: 16px;
            --radius-xl: 20px;
            --radius-full: 99px;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        body { 
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            display: flex; 
            height: 100vh; 
            background-color: var(--bg-primary); 
            color: var(--text-primary); 
            overflow: hidden;
            -webkit-font-smoothing: antialiased;
        }

        /* ===== ANIMATIONS ===== */
        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(16px); }
            to { opacity: 1; transform: translateY(0); }
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        @keyframes slideInLeft {
            from { opacity: 0; transform: translateX(-20px); }
            to { opacity: 1; transform: translateX(0); }
        }

        @keyframes pulse-ring {
            0% { box-shadow: 0 0 0 0 rgba(248, 113, 113, 0.5); }
            70% { box-shadow: 0 0 0 6px rgba(248, 113, 113, 0); }
            100% { box-shadow: 0 0 0 0 rgba(248, 113, 113, 0); }
        }

        @keyframes shimmer {
            0% { background-position: -200% center; }
            100% { background-position: 200% center; }
        }

        @keyframes glow-pulse {
            0%, 100% { box-shadow: 0 0 8px var(--accent-glow); }
            50% { box-shadow: 0 0 20px var(--accent-glow), 0 0 40px rgba(124, 92, 252, 0.1); }
        }

        @keyframes float {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-4px); }
        }

        /* ===== SCROLLBAR ===== */
        ::-webkit-scrollbar { width: 6px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: var(--border-hover); border-radius: 99px; }
        ::-webkit-scrollbar-thumb:hover { background: var(--text-muted); }

        /* ===== SIDEBAR ===== */
        .sidebar { 
            width: var(--sidebar-width); 
            background: var(--bg-secondary); 
            border-right: 1px solid var(--border); 
            display: flex; 
            flex-direction: column; 
            z-index: 10;
            position: relative;
        }

        .sidebar::after {
            content: '';
            position: absolute;
            top: 0; right: 0;
            width: 1px; height: 100%;
            background: linear-gradient(to bottom, var(--accent-glow), transparent 50%, var(--accent-glow));
            opacity: 0.5;
        }

        .brand { 
            padding: 24px 20px; 
            font-size: 18px; 
            font-weight: 700; 
            color: var(--text-primary); 
            display: flex; 
            align-items: center; 
            gap: 12px; 
            border-bottom: 1px solid var(--border);
            position: relative;
        }

        .brand .material-symbols-rounded {
            font-size: 28px;
            background: linear-gradient(135deg, var(--accent), var(--accent-secondary));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .brand span:last-child,
        .brand-text {
            background: linear-gradient(135deg, var(--text-primary), var(--text-secondary));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .nav-menu { 
            padding: 16px 12px; 
            flex: 1; 
            display: flex; 
            flex-direction: column; 
            gap: 4px; 
            overflow-y: auto; 
        }

        .nav-item { 
            display: flex; 
            align-items: center; 
            gap: 12px; 
            padding: 11px 16px; 
            color: var(--text-secondary); 
            text-decoration: none; 
            font-weight: 500; 
            font-size: 13.5px; 
            border-radius: var(--radius-md); 
            transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            overflow: hidden;
        }

        .nav-item .material-symbols-rounded {
            font-size: 20px;
            transition: all 0.25s;
        }

        .nav-item:hover { 
            background: var(--bg-card-hover); 
            color: var(--text-primary);
            padding-left: 20px;
        }

        .nav-item:hover .material-symbols-rounded {
            color: var(--accent);
        }

        .nav-item.active { 
            background: linear-gradient(135deg, rgba(124, 92, 252, 0.15), rgba(94, 234, 212, 0.08));
            color: var(--accent);
            font-weight: 600;
        }

        .nav-item.active::before {
            content: '';
            position: absolute;
            left: 0; top: 15%; height: 70%;
            width: 3px;
            background: linear-gradient(to bottom, var(--accent), var(--accent-secondary));
            border-radius: 0 4px 4px 0;
        }

        .nav-item.active .material-symbols-rounded {
            color: var(--accent);
        }

        .nav-section-title { 
            font-size: 10px; 
            font-weight: 700; 
            color: var(--text-muted); 
            text-transform: uppercase; 
            letter-spacing: 1.5px; 
            margin: 20px 0 6px 16px;
        }

        .nav-item.logout { margin-top: auto; color: var(--danger); }
        .nav-item.logout:hover { background: var(--danger-bg); color: var(--danger); }
        .nav-item.logout .material-symbols-rounded { color: var(--danger); }

        /* ===== MAIN CONTENT WRAPPER ===== */
        .main-wrapper { 
            flex: 1; 
            display: flex; 
            flex-direction: column; 
            overflow: hidden;
            background: var(--bg-primary);
        }
        
        /* ===== TOPBAR ===== */
        .topbar { 
            height: var(--topbar-height); 
            background: rgba(15, 13, 26, 0.8);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border-bottom: 1px solid var(--border); 
            display: flex; 
            align-items: center; 
            justify-content: space-between; 
            padding: 0 28px; 
            z-index: 5;
        }

        .topbar-title { 
            font-size: 16px; 
            font-weight: 600; 
            color: var(--text-secondary);
        }

        .topbar-right { display: flex; align-items: center; gap: 16px; }
        
        .lang-toggle { 
            display: flex; 
            align-items: center; 
            background: var(--bg-tertiary); 
            border: 1px solid var(--border); 
            border-radius: var(--radius-full); 
            padding: 3px;
        }

        .lang-btn { 
            padding: 4px 12px; 
            font-size: 12px; 
            font-weight: 600; 
            color: var(--text-muted); 
            border-radius: var(--radius-full); 
            cursor: pointer; 
            text-decoration: none; 
            transition: 0.2s; 
            border: none; 
            background: transparent;
            font-family: inherit;
        }

        .lang-btn.active { 
            background: var(--accent); 
            color: white; 
            box-shadow: 0 2px 8px var(--accent-glow);
        }

        .lang-btn:hover:not(.active) { color: var(--text-primary); }
        
        .action-btn { 
            position: relative; 
            display: flex; 
            align-items: center; 
            justify-content: center; 
            width: 38px; height: 38px; 
            border-radius: 50%; 
            background: var(--bg-tertiary); 
            border: 1px solid var(--border); 
            cursor: pointer; 
            color: var(--text-secondary); 
            transition: 0.25s;
        }

        .action-btn:hover { 
            color: var(--accent); 
            background: rgba(124, 92, 252, 0.1); 
            border-color: rgba(124, 92, 252, 0.3);
            transform: scale(1.05);
        }

        .notification-badge { 
            position: absolute; 
            top: -3px; right: -3px; 
            background: linear-gradient(135deg, #ef4444, #f97316);
            color: white; 
            font-size: 9px; 
            font-weight: 700; 
            width: 16px; height: 16px; 
            border-radius: 50%; 
            display: flex; 
            align-items: center; 
            justify-content: center; 
            border: 2px solid var(--bg-primary);
            animation: pulse-ring 2s infinite;
        }

        .user-profile { 
            display: flex; 
            align-items: center; 
            gap: 12px; 
            background: var(--bg-tertiary); 
            padding: 5px 14px 5px 5px; 
            border-radius: var(--radius-full); 
            border: 1px solid var(--border);
            transition: 0.25s;
            cursor: pointer;
        }

        .user-profile:hover {
            border-color: rgba(124, 92, 252, 0.3);
            background: rgba(124, 92, 252, 0.08);
        }

        .avatar { 
            width: 34px; height: 34px; 
            border-radius: 50%; 
            background: linear-gradient(135deg, var(--accent), #a78bfa);
            color: white; 
            display: flex; 
            align-items: center; 
            justify-content: center; 
            font-weight: 700; 
            font-size: 14px; 
            text-transform: uppercase;
            box-shadow: 0 0 0 2px var(--bg-tertiary), 0 0 0 3px rgba(124, 92, 252, 0.4);
        }

        .user-info { display: flex; flex-direction: column; gap: 1px; }
        .user-name { font-weight: 600; font-size: 13px; line-height: 1; color: var(--text-primary); }
        
        /* Badge */
        .badge { 
            padding: 3px 8px; 
            border-radius: 6px; 
            font-size: 10px; 
            font-weight: 700; 
            display: inline-flex; 
            align-items: center; 
            width: max-content; 
            line-height: 1; 
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .badge.admin { background: var(--admin-bg); color: var(--admin-text); }
        .badge.staff { background: var(--staff-bg); color: var(--staff-text); }
        .badge.sales { background: var(--sales-bg); color: var(--sales-text); }

        /* ===== DASHBOARD CONTENT ===== */
        .content { 
            padding: 28px; 
            overflow-y: auto; 
            flex: 1;
            animation: fadeIn 0.4s ease;
        }

        .welcome-card { 
            background: linear-gradient(135deg, #7c5cfc 0%, #5eead4 50%, #a78bfa 100%);
            background-size: 200% 200%;
            animation: shimmer 8s ease infinite;
            border-radius: var(--radius-xl); 
            padding: 36px; 
            color: white; 
            margin-bottom: 28px; 
            box-shadow: 0 8px 32px rgba(124, 92, 252, 0.25);
            position: relative;
            overflow: hidden;
        }

        .welcome-card::before {
            content: '';
            position: absolute;
            top: -50%; right: -30%;
            width: 60%; height: 200%;
            background: radial-gradient(circle, rgba(255,255,255,0.08) 0%, transparent 70%);
            pointer-events: none;
        }

        .welcome-card h1 { 
            font-size: 24px; 
            margin-bottom: 6px; 
            font-weight: 700;
            position: relative;
            z-index: 1;
        }

        /* ===== KPI CARDS ===== */
        .kpi-row { 
            display: grid; 
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); 
            gap: 16px; 
            margin-bottom: 28px; 
        }

        .kpi-card { 
            background: var(--bg-card);
            backdrop-filter: blur(12px);
            border: 1px solid var(--glass-border); 
            padding: 20px; 
            border-radius: var(--radius-lg); 
            display: flex; 
            align-items: center; 
            gap: 16px; 
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            animation: fadeInUp 0.5s ease both;
        }

        .kpi-card:nth-child(1) { animation-delay: 0.05s; }
        .kpi-card:nth-child(2) { animation-delay: 0.1s; }
        .kpi-card:nth-child(3) { animation-delay: 0.15s; }
        .kpi-card:nth-child(4) { animation-delay: 0.2s; }

        .kpi-card:hover {
            border-color: rgba(124, 92, 252, 0.25);
            background: var(--bg-card-hover);
            transform: translateY(-2px);
            box-shadow: var(--shadow-glow);
        }

        .kpi-icon { 
            width: 48px; height: 48px; 
            border-radius: var(--radius-md); 
            display: flex; 
            align-items: center; 
            justify-content: center; 
            font-size: 24px;
            flex-shrink: 0;
        }

        .kpi-info p { 
            font-size: 11px; 
            color: var(--text-secondary); 
            font-weight: 600; 
            margin-bottom: 6px; 
            text-transform: uppercase; 
            letter-spacing: 0.5px;
        }

        .kpi-info h3 { 
            font-size: 22px; 
            font-weight: 800; 
            color: var(--text-primary); 
            line-height: 1;
        }

        /* ===== ACTION CARDS GRID ===== */
        .grid { 
            display: grid; 
            grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); 
            gap: 20px; 
        }

        .action-card { 
            background: var(--bg-card);
            backdrop-filter: blur(12px);
            border: 1px solid var(--glass-border); 
            border-radius: var(--radius-xl); 
            padding: 24px; 
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            animation: fadeInUp 0.5s ease both;
            position: relative;
            overflow: hidden;
        }

        .action-card::before {
            content: '';
            position: absolute;
            top: 0; left: 0;
            width: 3px; height: 100%;
            border-radius: 0 4px 4px 0;
            transition: height 0.3s;
        }

        .action-card:hover { 
            transform: translateY(-4px); 
            box-shadow: var(--shadow-lg);
            border-color: var(--border-hover);
            background: var(--bg-card-hover);
        }

        .section-title { 
            font-size: 15px; 
            font-weight: 700; 
            margin-bottom: 18px; 
            color: var(--text-primary); 
            display: flex; 
            align-items: center; 
            gap: 10px; 
        }
        
        .btn-group { display: flex; flex-direction: column; gap: 8px; }
        
        .btn { 
            display: flex; 
            align-items: center; 
            gap: 12px; 
            padding: 11px 16px; 
            border: none; 
            border-radius: var(--radius-md); 
            font-size: 13px; 
            font-weight: 600; 
            cursor: pointer; 
            text-decoration: none; 
            background: rgba(255, 255, 255, 0.03); 
            color: var(--text-secondary); 
            transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
            font-family: inherit;
        }

        .btn .material-symbols-rounded { 
            font-size: 18px; 
            color: var(--text-muted); 
            transition: 0.25s; 
        }
        
        /* Sales Cards */
        .action-card.sales-card::before { background: linear-gradient(to bottom, var(--sales-text), transparent); }
        .action-card.sales-card .btn:hover { 
            background: var(--sales-bg); 
            color: var(--sales-text); 
            padding-left: 22px;
        }
        .action-card.sales-card .btn:hover .material-symbols-rounded { color: var(--sales-text); }
        
        /* Staff Cards */
        .action-card.staff-card::before { background: linear-gradient(to bottom, var(--staff-text), transparent); }
        .action-card.staff-card .btn:hover { 
            background: var(--staff-bg); 
            color: var(--staff-text); 
            padding-left: 22px;
        }
        .action-card.staff-card .btn:hover .material-symbols-rounded { color: var(--staff-text); }
        
        /* Admin Cards */
        .action-card.admin-card::before { background: linear-gradient(to bottom, var(--admin-text), transparent); }
        .action-card.admin-card .btn:hover { 
            background: var(--admin-bg); 
            color: var(--admin-text);
            padding-left: 22px;
        }
        .action-card.admin-card .btn:hover .material-symbols-rounded { color: var(--admin-text); }
        
        .btn-primary { 
            background: linear-gradient(135deg, var(--accent), #a78bfa); 
            color: white; 
            justify-content: center;
            box-shadow: 0 4px 12px var(--accent-glow);
        }

        .btn-primary:hover { 
            background: linear-gradient(135deg, var(--accent-hover), #9061f9);
            padding-left: 16px !important; 
            color: white !important;
            transform: translateY(-1px);
            box-shadow: 0 6px 20px var(--accent-glow);
        }

        .btn-primary .material-symbols-rounded { color: white !important; }

        /* ===== GLOBAL SEARCH ===== */
        .global-search { 
            display: flex; 
            align-items: center; 
            background: var(--bg-tertiary); 
            border: 1px solid var(--border); 
            border-radius: var(--radius-full); 
            padding: 6px 16px; 
            width: 320px; 
            transition: 0.3s;
        }

        .global-search:focus-within { 
            border-color: rgba(124, 92, 252, 0.4); 
            box-shadow: 0 0 0 3px var(--accent-glow);
            background: var(--bg-card-solid);
        }

        .global-search input { 
            border: none; 
            background: transparent; 
            outline: none; 
            flex: 1; 
            padding-left: 8px; 
            font-size: 13px; 
            color: var(--text-primary);
            font-family: inherit;
        }

        .global-search input::placeholder { color: var(--text-muted); }
        .global-search .material-symbols-rounded { color: var(--text-muted); font-size: 20px; }

        /* ===== PAGE CONTENT WRAPPER (for form pages) ===== */
        .page-content-wrapper { 
            width: 100%; 
            padding: 28px; 
            align-self: flex-start !important; 
            display: block !important;
            animation: fadeIn 0.4s ease;
        }

        /* ===== HERO BANNER ===== */
        .hero-banner { 
            background: linear-gradient(to right, rgba(15, 13, 26, 0.9), rgba(15, 13, 26, 0.4)), url('https://images.unsplash.com/photo-1552422535-c45813c61732?q=80&w=2070&auto=format&fit=crop') center/cover;
            border-radius: var(--radius-xl); 
            padding: 48px 40px; 
            color: white; 
            margin-bottom: 32px; 
            box-shadow: var(--shadow-lg);
            position: relative;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            justify-content: center;
            border: 1px solid var(--glass-border);
            animation: fadeIn 0.6s ease;
        }
        .hero-banner h1 { font-size: 32px; margin-bottom: 12px; font-weight: 800; text-shadow: 0 4px 12px rgba(0,0,0,0.5); letter-spacing: -0.5px; }
        .hero-banner p { font-size: 15px; color: rgba(255, 255, 255, 0.8); max-width: 500px; line-height: 1.6; }

        /* ===== VISUAL KPIs ===== */
        .kpi-card { position: relative; overflow: hidden; }
        .kpi-card::after { content: ''; position: absolute; right: -20px; bottom: -20px; width: 100px; height: 100px; background: radial-gradient(circle, currentColor 0%, transparent 70%); opacity: 0.05; pointer-events: none; }
        
        /* ===== FEATURED PRODUCTS ===== */
        .featured-section { margin-bottom: 32px; animation: fadeInUp 0.5s ease; }
        .featured-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(220px, 1fr)); gap: 20px; }
        .mini-product { background: var(--bg-card); border: 1px solid var(--glass-border); border-radius: var(--radius-lg); overflow: hidden; display: flex; flex-direction: column; transition: all 0.3s ease; }
        .mini-product:hover { transform: translateY(-4px); border-color: rgba(124, 92, 252, 0.3); box-shadow: var(--shadow-glow); }
        .mini-product-img { height: 130px; background: #000; display: flex; align-items: center; justify-content: center; overflow: hidden; position: relative; }
        .mini-product-img img { width: 100%; height: 100%; object-fit: cover; opacity: 0.8; transition: 0.5s; }
        .mini-product:hover .mini-product-img img { transform: scale(1.1); opacity: 1; }
        .mini-product-info { padding: 16px; }
        .mini-product-brand { font-size: 10px; color: var(--accent-secondary); font-weight: 700; text-transform: uppercase; margin-bottom: 4px; }
        .mini-product-name { font-size: 14px; font-weight: 700; color: var(--text-primary); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }

        /* ===== QUICK ACCESS GRID ===== */
        .quick-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(160px, 1fr)); gap: 16px; margin-bottom: 32px; animation: fadeInUp 0.6s ease; }
        .quick-card { background: var(--bg-card); border: 1px solid var(--glass-border); border-radius: var(--radius-lg); padding: 20px 16px; display: flex; flex-direction: column; align-items: center; text-align: center; gap: 12px; cursor: pointer; text-decoration: none; transition: all 0.3s; }
        .quick-card .material-symbols-rounded { font-size: 32px; transition: 0.3s; }
        .quick-card span.label { font-size: 13px; font-weight: 600; color: var(--text-primary); }
        .quick-card:hover { background: var(--bg-card-hover); transform: translateY(-4px); }
        .quick-card.sales:hover { border-color: var(--sales-text); box-shadow: 0 8px 24px rgba(96, 165, 250, 0.2); }
        .quick-card.sales .material-symbols-rounded { color: var(--sales-text); }
        .quick-card.staff:hover { border-color: var(--staff-text); box-shadow: 0 8px 24px rgba(52, 211, 153, 0.2); }
        .quick-card.staff .material-symbols-rounded { color: var(--staff-text); }
        .quick-card.admin:hover { border-color: var(--admin-text); box-shadow: 0 8px 24px rgba(248, 113, 113, 0.2); }
        .quick-card.admin .material-symbols-rounded { color: var(--admin-text); }
        
        .section-header { font-size: 16px; font-weight: 800; color: var(--text-primary); margin-bottom: 16px; display: flex; align-items: center; gap: 8px; }
    </style>
</head>
<body>