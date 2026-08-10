<?php
// =========================================================
// TỆP CÁC HÀM TIỆN ÍCH DÙNG CHUNG - KHO ĐÀN PRO
// Lưu ý: Không cần gọi session_start() ở đây vì db.php đã lo việc đó
// =========================================================

/**
 * 1. HÀM KIỂM TRA ĐĂNG NHẬP
 */
function checkLogin() {
    if (!isset($_SESSION['user_id'])) {
        header("Location: login.php");
        exit();
    }
}

/**
 * 2. HÀM PHÂN QUYỀN (Role-Based Access Control)
 */
function allowRoles($allowed_roles) {
    if (!isset($_SESSION['role_id']) || !in_array($_SESSION['role_id'], $allowed_roles)) {
        die('<div style="font-family: sans-serif; text-align: center; margin-top: 50px;">
                <h2 style="color: #ef4444;">⛔ CẢNH BÁO BẢO MẬT</h2>
                <p>Bạn không có quyền truy cập vào chức năng này!</p>
                <a href="index.php" style="color: #4f46e5; text-decoration: none; font-weight: bold;">&larr; Quay lại Bảng điều khiển</a>
             </div>');
    }
}

/**
 * 3. HÀM ĐỊNH DẠNG TIỀN TỆ VIỆT NAM
 */
function formatVND($amount) {
    if (!is_numeric($amount)) return "0 đ";
    return number_format($amount, 0, ',', '.') . " đ";
}

/**
 * 4. HÀM HIỂN THỊ TRẠNG THÁI (BADGE)
 */
function getStatusBadge($status) {
    switch ($status) {
        case 'Trong kho':
            return '<span style="padding: 4px 10px; background: #dcfce7; color: #16a34a; border-radius: 6px; font-size: 12px; font-weight: bold; display: inline-block;">Trong kho</span>';
        case 'Đã bán':
            return '<span style="padding: 4px 10px; background: #fee2e2; color: #dc2626; border-radius: 6px; font-size: 12px; font-weight: bold; display: inline-block;">Đã bán</span>';
        case 'Chờ duyệt':
            return '<span style="padding: 4px 10px; background: #fef9c3; color: #ca8a04; border-radius: 6px; font-size: 12px; font-weight: bold; display: inline-block;">Chờ duyệt</span>';
        case 'Đang bảo trì':
            return '<span style="padding: 4px 10px; background: #f3e8ff; color: #9333ea; border-radius: 6px; font-size: 12px; font-weight: bold; display: inline-block;">Bảo trì</span>';
        default:
            return '<span style="padding: 4px 10px; background: #f1f5f9; color: #64748b; border-radius: 6px; font-size: 12px; font-weight: bold; display: inline-block;">' . htmlspecialchars($status) . '</span>';
    }
}

/**
 * 5. HÀM GHI NHẬT KÝ HỆ THỐNG
 */
function writeLog($conn, $hanhDong, $chiTiet) {
    $maTaiKhoan = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
    $sql = "INSERT INTO NhatKyHeThong (maTaiKhoan, loaiHanhDong, chiTiet) VALUES (?, ?, ?)";
    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("iss", $maTaiKhoan, $hanhDong, $chiTiet);
        $stmt->execute();
        $stmt->close();
    }
}

/**
 * 6. HÀM LÀM SẠCH DỮ LIỆU ĐẦU VÀO
 */
function sanitize($string) {
    return htmlspecialchars(trim($string), ENT_QUOTES, 'UTF-8');
}

/**
 * 7. ĐA NGÔN NGỮ (i18n)
 */
// Xác định ngôn ngữ hiện tại
if (isset($_GET['lang'])) {
    $lang = $_GET['lang'];
    if (in_array($lang, ['vn', 'en'])) {
        $_SESSION['lang'] = $lang;
    }
    
    // Xóa param ?lang= ra khỏi URL để sạch đẹp
    $url = strtok($_SERVER["REQUEST_URI"], '?');
    $query = $_GET;
    unset($query['lang']);
    if (count($query) > 0) {
        $url .= '?' . http_build_query($query);
    }
    header("Location: $url");
    exit();
}

// Ngôn ngữ mặc định
if (!isset($_SESSION['lang'])) {
    $_SESSION['lang'] = 'vn';
}

// Tải từ điển
$lang_file = __DIR__ . '/lang/' . $_SESSION['lang'] . '.php';
if (file_exists($lang_file)) {
    $translations = include($lang_file);
} else {
    $translations = [];
}

/**
 * Lấy chuỗi dịch thuật dựa trên key
 */
function __($key) {
    global $translations;
    return isset($translations[$key]) ? $translations[$key] : $key;
}