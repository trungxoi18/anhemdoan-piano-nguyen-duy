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
