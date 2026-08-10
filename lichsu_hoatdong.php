<?php
// Kiểm tra session để tránh lỗi Notice
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'db.php';
require_once 'functions.php';

// Kiểm tra đăng nhập
checkLogin();

$user_id = $_SESSION['user_id'];
$role_id = $_SESSION['role_id'];

// Xử lý bộ lọc
$filter_user = "";
$filter_action = "";
$params = [];
$types = "";

// Mặc định câu SQL
$sql = "SELECT nk.*, nv.hoTen, tk.tenDangNhap 
        FROM NhatKyHeThong nk 
        JOIN TaiKhoan tk ON nk.maTaiKhoan = tk.maTaiKhoan 
        JOIN NhanVien nv ON tk.maNhanVien = nv.maNhanVien 
        WHERE 1=1";

// Phân quyền xem
if ($role_id != 1) {
    // Không phải Admin -> Chỉ xem của chính mình
    $sql .= " AND nk.maTaiKhoan = ?";
    $params[] = $user_id;
    $types .= "i";
} else {
    // Admin -> Có thể lọc theo user
    if (isset($_GET['user']) && $_GET['user'] != '') {
        $sql .= " AND nk.maTaiKhoan = ?";
        $params[] = $_GET['user'];
        $types .= "i";
        $filter_user = $_GET['user'];
    }
}

// Lọc theo hành động (chung cho tất cả)
if (isset($_GET['action']) && $_GET['action'] != '') {
    $sql .= " AND nk.loaiHanhDong = ?";
    $params[] = $_GET['action'];
    $types .= "s";
    $filter_action = $_GET['action'];
}

$sql .= " ORDER BY nk.ngayTao DESC LIMIT 100"; // Giới hạn 100 sự kiện gần nhất cho nhẹ

$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();

// Danh sách user cho Admin lọc
$users = [];
if ($role_id == 1) {
    $res_users = $conn->query("SELECT tk.maTaiKhoan, nv.hoTen FROM TaiKhoan tk JOIN NhanVien nv ON tk.maNhanVien = nv.maNhanVien");
    while($u = $res_users->fetch_assoc()) {
        $users[] = $u;
    }
}
?>

<?php include 'includes/header.php'; ?>
<?php include 'includes/sidebar.php'; ?>

<style>
    /* Dark Mode Timeline Styles */
    .timeline-container { 
        max-width: 900px; 
        margin: 0 auto; 
        padding: 20px 0;
        animation: fadeInUp 0.5s ease;
    }

    .filter-card {
        background: var(--bg-card);
        backdrop-filter: blur(12px);
        border: 1px solid var(--glass-border);
        border-radius: var(--radius-lg);
        padding: 20px 24px;
        margin-bottom: 32px;
        display: flex;
        gap: 16px;
        align-items: center;
        flex-wrap: wrap;
    }

    .filter-card .custom-input {
        background: rgba(0,0,0,0.2);
        border: 1px solid var(--glass-border);
        color: var(--text-primary);
        padding: 10px 16px;
        border-radius: var(--radius-md);
        font-family: inherit;
        outline: none;
        transition: 0.3s;
        min-width: 200px;
    }
    .filter-card .custom-input:focus {
        border-color: var(--accent);
        background: rgba(124, 92, 252, 0.05);
    }
    .filter-card .custom-input option {
        background: var(--bg-secondary);
        color: var(--text-primary);
    }

    .btn-filter {
        background: linear-gradient(135deg, var(--accent), #9061f9);
        color: white;
        border: none;
        padding: 10px 24px;
        border-radius: var(--radius-md);
        font-weight: 600;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        transition: 0.3s;
        font-family: inherit;
    }
    .btn-filter:hover { transform: translateY(-2px); box-shadow: 0 4px 12px var(--accent-glow); }

    /* Timeline */
    .timeline {
        position: relative;
        padding-left: 40px;
    }
    
    .timeline::before {
        content: '';
        position: absolute;
        left: 15px;
        top: 0;
        bottom: 0;
        width: 2px;
        background: var(--glass-border);
    }

    .timeline-item {
        position: relative;
        margin-bottom: 24px;
        animation: fadeIn 0.5s ease backwards;
    }

    /* Tạo delay cho từng item để có hiệu ứng cascade */
    .timeline-item:nth-child(1) { animation-delay: 0.1s; }
    .timeline-item:nth-child(2) { animation-delay: 0.2s; }
    .timeline-item:nth-child(3) { animation-delay: 0.3s; }
    .timeline-item:nth-child(4) { animation-delay: 0.4s; }
    .timeline-item:nth-child(5) { animation-delay: 0.5s; }

    .timeline-dot {
        position: absolute;
        left: -40px;
        width: 32px;
        height: 32px;
        border-radius: 50%;
        background: var(--bg-tertiary);
        border: 2px solid var(--accent);
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 2;
        color: var(--accent);
        box-shadow: 0 0 10px var(--accent-glow);
    }

    .timeline-content {
        background: var(--bg-card);
        border: 1px solid var(--glass-border);
        border-radius: var(--radius-lg);
        padding: 20px;
        position: relative;
        transition: 0.3s;
    }
    
    .timeline-content:hover {
        border-color: rgba(124, 92, 252, 0.3);
        transform: translateX(4px);
        background: var(--bg-card-hover);
    }

    .timeline-content::before {
        content: '';
        position: absolute;
        left: -8px;
        top: 14px;
        width: 14px;
        height: 14px;
        background: var(--bg-card);
        border-left: 1px solid var(--glass-border);
        border-bottom: 1px solid var(--glass-border);
        transform: rotate(45deg);
    }
    .timeline-content:hover::before {
        background: var(--bg-card-hover);
        border-color: rgba(124, 92, 252, 0.3);
    }

    .time-badge {
        font-size: 12px;
        color: var(--text-muted);
        font-weight: 600;
        margin-bottom: 8px;
        display: inline-flex;
        align-items: center;
        gap: 4px;
    }

    .action-title {
        font-size: 16px;
        font-weight: 700;
        color: var(--text-primary);
        margin-bottom: 6px;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .action-detail {
        font-size: 14px;
        color: var(--text-secondary);
        line-height: 1.5;
    }

    .user-badge {
        background: rgba(255,255,255,0.05);
        border: 1px solid var(--glass-border);
        padding: 4px 10px;
        border-radius: var(--radius-full);
        font-size: 11px;
        color: var(--text-primary);
        font-weight: 600;
        display: inline-flex;
        align-items: center;
        gap: 4px;
        margin-left: auto;
    }

    .empty-state {
        text-align: center;
        padding: 60px 20px;
        color: var(--text-muted);
    }
    .empty-state .material-symbols-rounded { font-size: 64px; opacity: 0.2; margin-bottom: 16px; }

</style>

<div class="main-wrapper">
    <?php include 'includes/topbar.php'; ?>

    <div class="content">
        <div class="timeline-container">
            <h1 style="font-size: 1.5rem; font-weight: 800; margin-bottom: 24px; color: var(--text-primary); display: flex; align-items: center; gap: 8px;">
                <span class="material-symbols-rounded" style="color: var(--accent);">history</span> 
                Lịch sử hoạt động
            </h1>

            <form method="GET" class="filter-card">
                <div style="font-weight: 600; color: var(--text-secondary); margin-right: 8px;">Bộ lọc:</div>
                
                <?php if ($role_id == 1): ?>
                <select name="user" class="custom-input">
                    <option value="">Tất cả tài khoản</option>
                    <?php foreach($users as $u): ?>
                        <option value="<?= $u['maTaiKhoan'] ?>" <?= ($filter_user == $u['maTaiKhoan']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($u['hoTen']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <?php endif; ?>

                <select name="action" class="custom-input">
                    <option value="">Tất cả thao tác</option>
                    <option value="ĐĂNG NHẬP" <?= ($filter_action == 'ĐĂNG NHẬP') ? 'selected' : '' ?>>Đăng nhập</option>
                    <option value="LẬP HÓA ĐƠN" <?= ($filter_action == 'LẬP HÓA ĐƠN') ? 'selected' : '' ?>>Lập Hóa đơn</option>
                    <option value="NHẬP KHO" <?= ($filter_action == 'NHẬP KHO') ? 'selected' : '' ?>>Nhập kho</option>
                    <option value="XUẤT KHO" <?= ($filter_action == 'XUẤT KHO') ? 'selected' : '' ?>>Xuất kho</option>
                </select>

                <button type="submit" class="btn-filter">
                    <span class="material-symbols-rounded">filter_alt</span> Lọc kết quả
                </button>
                
                <?php if ($filter_user != '' || $filter_action != ''): ?>
                    <a href="lichsu_hoatdong.php" style="color: var(--danger); text-decoration: none; font-size: 13px; font-weight: 600; margin-left: 8px;">Xóa bộ lọc</a>
                <?php endif; ?>
            </form>

            <div class="timeline">
                <?php if ($result->num_rows > 0): ?>
                    <?php while($row = $result->fetch_assoc()): 
                        // Chọn Icon và Màu dựa theo loại hành động
                        $icon = 'history';
                        $color = 'var(--accent)';
                        if ($row['loaiHanhDong'] == 'ĐĂNG NHẬP') { $icon = 'login'; $color = '#10b981'; }
                        if ($row['loaiHanhDong'] == 'LẬP HÓA ĐƠN') { $icon = 'receipt_long'; $color = '#3b82f6'; }
                        if ($row['loaiHanhDong'] == 'NHẬP KHO') { $icon = 'input'; $color = '#f59e0b'; }
                        if ($row['loaiHanhDong'] == 'XUẤT KHO') { $icon = 'output'; $color = '#f43f5e'; }
                    ?>
                        <div class="timeline-item">
                            <div class="timeline-dot" style="border-color: <?= $color ?>; color: <?= $color ?>; box-shadow: 0 0 10px <?= str_replace(')', ', 0.3)', str_replace('rgb', 'rgba', $color)) ?>;">
                                <span class="material-symbols-rounded" style="font-size: 18px;"><?= $icon ?></span>
                            </div>
                            <div class="timeline-content">
                                <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 8px;">
                                    <div class="time-badge">
                                        <span class="material-symbols-rounded" style="font-size: 14px;">schedule</span>
                                        <?= date('H:i - d/m/Y', strtotime($row['ngayTao'])) ?>
                                    </div>
                                    <?php if ($role_id == 1): ?>
                                    <div class="user-badge" title="<?= htmlspecialchars($row['tenDangNhap']) ?>">
                                        <span class="material-symbols-rounded" style="font-size: 14px;">person</span>
                                        <?= htmlspecialchars($row['hoTen']) ?>
                                    </div>
                                    <?php endif; ?>
                                </div>
                                <div class="action-title" style="color: <?= $color ?>;">
                                    <?= htmlspecialchars($row['loaiHanhDong']) ?>
                                </div>
                                <div class="action-detail">
                                    <?= htmlspecialchars($row['chiTiet']) ?>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="empty-state">
                        <span class="material-symbols-rounded">inbox</span>
                        <p>Chưa có lịch sử hoạt động nào được ghi nhận.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
