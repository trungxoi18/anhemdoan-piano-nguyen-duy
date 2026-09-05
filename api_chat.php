<?php
header('Content-Type: application/json');
require_once 'db.php'; 

$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['message']) || empty(trim($data['message']))) {
    echo json_encode(['reply' => 'Vui lòng nhập câu hỏi.']);
    exit;
}

$userMessage = $data['message'];
$apiKey = 'Mã API GEMINI'; 
$url = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-3.6-flash:generateContent?key=' . $apiKey;

// 1. TỐI ƯU TỐC ĐỘ BẰNG CACHE (SESSION)
// Chỉ truy vấn Database 5 phút 1 lần thay vì truy vấn mỗi khi chat
$khoData = "Hiện tại kho chưa có sản phẩm nào.";
$cacheLifetime = 300; // 300 giây = 5 phút

if (isset($_SESSION['ai_kho_data']) && isset($_SESSION['ai_kho_time']) && (time() - $_SESSION['ai_kho_time'] < $cacheLifetime)) {
    // Lấy dữ liệu từ RAM/Session (Siêu nhanh)
    $khoData = $_SESSION['ai_kho_data'];
} else {
    // Nếu chưa có cache hoặc cache hết hạn, mới chạy lệnh SQL (Truy vấn ổ cứng)
    $sql = "SELECT m.tenMau, IFNULL(h.tenHang, 'Chưa rõ') AS tenHang, IFNULL(l.tenLoai, 'Chưa phân loại') AS tenLoai, m.moTa, s.giaBan, COUNT(s.soSerial) AS soLuongTon
            FROM maudan m
            LEFT JOIN hangdan h ON m.maHang = h.maHang
            LEFT JOIN loaidan l ON m.maLoai = l.maLoai
            LEFT JOIN danserial s ON m.maMau = s.maMau AND s.trangThai = 'Trong kho'
            GROUP BY m.maMau, m.tenMau, h.tenHang, l.tenLoai, m.moTa, s.giaBan";
    
    $result = $conn->query($sql);
    
    if ($result && $result->num_rows > 0) {
        $khoData = "Danh sách sản phẩm/nhạc cụ hiện có trong kho:\n";
        while($row = $result->fetch_assoc()) {
            $giaDisplay = $row["giaBan"] ? number_format($row["giaBan"]) . " VNĐ" : "Chưa niêm yết";
            $khoData .= "- " . $row["tenMau"] . " (Loại: " . $row["tenLoai"] . ", Hãng: " . $row["tenHang"] . ") | Giá bán: " . $giaDisplay . " | Tồn kho: " . $row["soLuongTon"] . " chiếc\n";
        }
        // Lưu kết quả vào Session để dùng cho lần chat sau
        $_SESSION['ai_kho_data'] = $khoData;
        $_SESSION['ai_kho_time'] = time();
    }
}

$systemPrompt = "Bạn là trợ lý AI tư vấn bán nhạc cụ cho cửa hàng Quản Lý Bán Đàn. Dựa vào dữ liệu kho sau để trả lời ngắn gọn, súc tích (dưới 50 từ). Nếu khách hỏi mẫu không có, báo rõ và gợi ý mẫu khác. Không tự bịa đặt.\n\n" . $khoData;

$payload = json_encode([
    "system_instruction" => ["parts" => [["text" => $systemPrompt]]],
    "contents" => [["parts" => [["text" => $userMessage]]]]
]);

// 2. TỐI ƯU KẾT NỐI MẠNG (cURL)
$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Connection: keep-alive' // Giữ kết nối mạng mở
]);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); 
curl_setopt($ch, CURLOPT_TIMEOUT, 30); // Ép AI phải trả lời dưới 10 giây
curl_setopt($ch, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4); // Tăng tốc độ phân giải tên miền DNS

$response = curl_exec($ch);

if (curl_errno($ch)) {
    echo json_encode(['reply' => 'Hệ thống phản hồi chậm, vui lòng thử lại sau.']);
    curl_close($ch);
    exit;
}
curl_close($ch);

$responseData = json_decode($response, true);

if (isset($responseData['candidates'][0]['content']['parts'][0]['text'])) {
    echo json_encode(['reply' => $responseData['candidates'][0]['content']['parts'][0]['text']]);
} else {
    // Ép in ra nguyên nhân lỗi gốc từ Google
    $errorMsg = $responseData['error']['message'] ?? 'Dữ liệu trả về (Raw): ' . $response;
    echo json_encode(['reply' => 'Chi tiết lỗi từ Google: ' . $errorMsg]);
}
?>