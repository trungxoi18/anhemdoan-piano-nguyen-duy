<?php
ob_start();
ini_set('display_errors', 0);
error_reporting(0);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'db.php'; 

// Đảm bảo không in lỗi ra làm hỏng JSON response
ini_set('display_errors', 0);
error_reporting(0);

function sendResponse($reply) {
    while (ob_get_level()) {
        ob_end_clean();
    }
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['reply' => $reply], JSON_UNESCAPED_UNICODE);
    exit;
}

// Bắt lỗi toàn cục
set_exception_handler(function($e) {
    sendResponse("Xin lỗi, hệ thống AI tạm thời gián đoạn: " . $e->getMessage());
});

$userMessage = '';
// Chỉ nhận dữ liệu qua POST (FormData) với tên biến đã đổi để vượt tường lửa
if (isset($_POST['noidung_chat']) && !empty(trim($_POST['noidung_chat']))) {
    $userMessage = trim($_POST['noidung_chat']);
}

if (empty($userMessage)) {
    sendResponse('Vui lòng nhập câu hỏi của bạn.');
}
// 1. CẤU HÌNH API KEY (Tùy chọn: Điền Gemini API Key nếu muốn dùng Cloud AI)
$apiKey = ''; 

// 2. LẤY DỮ LIỆU KHO HIỆN TẠI TỪ DATABASE
$khoItems = [];
$khoTextList = [];

try {
    if (isset($conn) && !$conn->connect_error) {
        $sql = "SELECT m.maMau, m.tenMau, IFNULL(h.tenHang, 'Chưa rõ') AS tenHang, IFNULL(l.tenLoai, 'Chưa phân loại') AS tenLoai, 
                       m.moTa, s.giaBan, COUNT(s.soSerial) AS soLuongTon
                FROM maudan m
                LEFT JOIN hangdan h ON m.maHang = h.maHang
                LEFT JOIN loaidan l ON m.maLoai = l.maLoai
                LEFT JOIN danserial s ON m.maMau = s.maMau AND s.trangThai = 'Trong kho'
                GROUP BY m.maMau, m.tenMau, h.tenHang, l.tenLoai, m.moTa, s.giaBan";

        $result = $conn->query($sql);
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $khoItems[] = $row;
                $giaDisplay = $row["giaBan"] ? number_format($row["giaBan"]) . " VNĐ" : "Liên hệ";
                $khoTextList[] = "- " . $row["tenMau"] . " (" . $row["tenHang"] . " - " . $row["tenLoai"] . ") | Giá: " . $giaDisplay . " | Còn: " . $row["soLuongTon"] . " cây";
            }
        }
    }
} catch (Throwable $e) {
    // Không làm gián đoạn chatbot nếu lỗi query kho
}

// 3. THỬ GỌI GOOGLE GEMINI AI NẾU CÓ API KEY HỢP LỆ
$hasValidApiKey = !empty($apiKey) && strpos($apiKey, 'Mã API') === false && strlen($apiKey) > 20;

if ($hasValidApiKey) {
    $khoDataPrompt = "Danh sách nhạc cụ trong kho hiện tại:\n" . implode("\n", $khoTextList);
    $systemPrompt = "Bạn là Trợ lý AI chuyên viên tư vấn bán đàn tại Cửa hàng Kho Đàn Piano Nguyễn Duy. "
                  . "Hãy trả lời thân thiện, lịch sự, ngắn gọn và chính xác dựa trên danh sách kho sau. "
                  . "Nếu khách hỏi mẫu không có trong kho, báo rõ và gợi ý các mẫu tương đương hiện có.\n\n" . $khoDataPrompt;

    $url = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash:generateContent?key=' . urlencode($apiKey);
    
    $payload = json_encode([
        "system_instruction" => ["parts" => [["text" => $systemPrompt]]],
        "contents" => [["parts" => [["text" => $userMessage]]]],
        "generationConfig" => [
            "temperature" => 0.7,
            "maxOutputTokens" => 350
        ]
    ]);

    if (function_exists('curl_init')) {
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
            curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_TIMEOUT, 5); // Giảm timeout xuống 5s để tránh treo host

            $response = @curl_exec($ch); // Thêm @ để ẩn warning cURL của host
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($httpCode === 200 && $response) {
                $resData = json_decode($response, true);
                if (!empty($resData['candidates'][0]['content']['parts'][0]['text'])) {
                    sendResponse(trim($resData['candidates'][0]['content']['parts'][0]['text']));
                }
            }
        }
}

// 4. CHẾ ĐỘ TRỢ LÝ THÔNG MINH NỘI BỘ (Smart Local Assistant - Hoạt động trực tiếp với Database)
$msgLower = mb_strtolower($userMessage, 'UTF-8');

// Hàm kiểm tra từ khóa
function hasKeywords($text, $keywords) {
    foreach ($keywords as $kw) {
        if (mb_strpos($text, $kw, 0, 'UTF-8') !== false) {
            return true;
        }
    }
    return false;
}

// A. Chào hỏi
if (hasKeywords($msgLower, ['chào', 'hello', 'hi', 'bạn là ai', 'tro ly', 'trợ lý', 'xin chào'])) {
    $totalStock = 0;
    foreach ($khoItems as $item) { $totalStock += (int)$item['soLuongTon']; }
    
    $reply = "Xin chào bạn! 👋 Tôi là Trợ lý AI Kho Đàn Piano Nguyễn Duy 🎹.\n"
           . "Hiện tại trong kho đang có " . count($khoItems) . " dòng sản phẩm (Tổng cộng {$totalStock} cây đàn sẵn sàng giao).\n\n"
           . "Bạn có thể hỏi tôi về:\n"
           . "• Tìm kiếm mẫu đàn: Ví dụ 'Grand Piano', 'Kawai K-300', 'Yamaha'...\n"
           . "• Tư vấn theo giá: 'Đàn dưới 50 triệu', 'Đàn giá rẻ'...\n"
           . "• Tra cứu: 'Chính sách bảo hành', 'Chương trình khuyến mãi'...";
    sendResponse($reply);
}

// B. Tra cứu Chính sách bảo hành
if (hasKeywords($msgLower, ['bảo hành', 'bao hanh', 'đổi trả', 'doi tra', 'bảo trì'])) {
    $reply = "🛡️ **Chính sách bảo hành tại Kho Đàn Nguyễn Duy:**\n\n";
    try {
        if (isset($conn) && !$conn->connect_error) {
            $resPolicy = $conn->query("SELECT * FROM chinhsachbaohanh ORDER BY maCS ASC LIMIT 3");
            if ($resPolicy && $resPolicy->num_rows > 0) {
                while ($p = $resPolicy->fetch_assoc()) {
                    $reply .= "📌 **" . $p['tenChinhSach'] . ":**\n" . mb_substr($p['noiDung'], 0, 160, 'UTF-8') . "...\n\n";
                }
                $reply .= "👉 Bạn có thể vào mục **Chính sách bảo hành** trên thanh menu để xem chi tiết đầy đủ!";
                sendResponse($reply);
            }
        }
    } catch (Throwable $e) {}
    
    $reply = "Cửa hàng cam kết bảo hành chính hãng từ 12 đến 60 tháng tùy dòng sản phẩm, hỗ trợ lên dây và bảo dưỡng định kỳ tận nhà.";
    sendResponse($reply);
}

// C. Tra cứu Chương trình khuyến mãi
if (hasKeywords($msgLower, ['khuyến mãi', 'khuyen mai', 'giảm giá', 'giam gia', 'ưu đãi', 'uu dai', 'sale'])) {
    try {
        if (isset($conn) && !$conn->connect_error) {
            $resPromo = $conn->query("SELECT * FROM chuongtrinhkhuyenmai WHERE trangThai = 'Đang diễn ra' ORDER BY ngayBatDau DESC LIMIT 3");
            if ($resPromo && $resPromo->num_rows > 0) {
                $reply = "🎉 **Chương trình khuyến mãi đang diễn ra:**\n\n";
                while ($pr = $resPromo->fetch_assoc()) {
                    $giam = $pr['phanTramGiam'] > 0 ? " (Giảm {$pr['phanTramGiam']}%)" : "";
                    $reply .= "🎁 **" . $pr['tenChuongTrinh'] . $giam . "**\n"
                           . "• " . $pr['moTa'] . "\n"
                           . "• Áp dụng đến: " . date('d/m/Y', strtotime($pr['ngayKetThuc'])) . "\n\n";
                }
                sendResponse($reply);
            }
        }
    } catch (Throwable $e) {}

    $reply = "Hiện tại cửa hàng đang áp dụng ưu đãi miễn phí vận chuyển nội thành và tặng kèm phụ kiện (ghế piano, khăn phủ phím) cho tất cả đơn hàng!";
    sendResponse($reply);
}

// D. Lọc theo khoảng giá
$maxBudget = 0;
if (preg_match('/(dưới|duoi|tầm|tam|khoảng|khoang|dưới mức)\s*(\d+)\s*(triệu|trieu|tr|k|000)/i', $msgLower, $matches)) {
    $num = (int)$matches[2];
    $unit = mb_strtolower($matches[3], 'UTF-8');
    if ($unit === 'k') {
        $maxBudget = $num * 1000;
    } else {
        $maxBudget = $num * 1000000;
    }
}

if ($maxBudget > 0) {
    $matching = [];
    foreach ($khoItems as $item) {
        if ($item['giaBan'] > 0 && $item['giaBan'] <= $maxBudget) {
            $matching[] = $item;
        }
    }

    if (count($matching) > 0) {
        $reply = "🎹 **Các mẫu đàn có giá dưới " . number_format($maxBudget) . " VNĐ hiện có trong kho:**\n\n";
        foreach (array_slice($matching, 0, 5) as $m) {
            $reply .= "• **" . $m['tenMau'] . "** (" . $m['tenHang'] . ")\n"
                   . "  Giá bán: " . number_format($m['giaBan']) . " VNĐ | Tồn kho: " . $m['soLuongTon'] . " cây\n";
        }
        sendResponse($reply);
    } else {
        sendResponse("Hiện tại chưa có mẫu đàn nào trong kho có giá dưới " . number_format($maxBudget) . " VNĐ. Bạn có thể tăng mức ngân sách hoặc liên hệ Hotline để được tư vấn thêm.");
    }
}

// E. Tìm kiếm theo Tên đàn / Hãng / Loại
$foundItems = [];
foreach ($khoItems as $item) {
    $itemFull = mb_strtolower($item['tenMau'] . ' ' . $item['tenHang'] . ' ' . $item['tenLoai'], 'UTF-8');
    
    // Tách từ khóa người dùng
    $words = explode(' ', $msgLower);
    $matchesCount = 0;
    foreach ($words as $w) {
        $w = trim($w);
        if (mb_strlen($w, 'UTF-8') >= 2 && mb_strpos($itemFull, $w, 0, 'UTF-8') !== false) {
            $matchesCount++;
        }
    }
    if ($matchesCount > 0) {
        $foundItems[] = ['item' => $item, 'score' => $matchesCount];
    }
}

// Sắp xếp theo độ khớp cao nhất
usort($foundItems, function($a, $b) {
    return $b['score'] - $a['score'];
});

if (count($foundItems) > 0) {
    $reply = "🔍 **Kết quả tìm kiếm nhạc cụ phù hợp trong kho:**\n\n";
    $shown = 0;
    foreach ($foundItems as $f) {
        $item = $f['item'];
        $gia = $item['giaBan'] ? number_format($item['giaBan']) . " VNĐ" : "Chưa niêm yết";
        $tonKho = (int)$item['soLuongTon'];
        $trangThaiTon = $tonKho > 0 ? "🟢 Còn {$tonKho} cây sẵn kho" : "🔴 Tạm hết hàng";

        $reply .= "• **" . $item['tenMau'] . "**\n"
               . "  Hãng: " . $item['tenHang'] . " | Phân loại: " . $item['tenLoai'] . "\n"
               . "  Giá: **" . $gia . "** | " . $trangThaiTon . "\n\n";
        $shown++;
        if ($shown >= 4) break;
    }
    sendResponse($reply);
}

// F. Phản hồi mặc định nếu không khớp từ khóa cụ thể
$sampleList = "";
foreach (array_slice($khoItems, 0, 4) as $item) {
    $gia = $item['giaBan'] ? number_format($item['giaBan']) . " đ" : "Liên hệ";
    $sampleList .= "• " . $item['tenMau'] . " (" . $item['tenHang'] . ") - " . $gia . "\n";
}

$reply = "Dạ, tôi chưa hiểu rõ yêu cầu của bạn. Dưới đây là một số mẫu đàn nổi bật sẵn có trong kho:\n\n"
       . $sampleList . "\n"
       . "💡 Bạn có thể thử gõ: *'Grand Piano'*, *'Giá đàn Kawai'*, *'Đàn dưới 30 triệu'*, *'Chính sách bảo hành'* hoặc *'Khuyến mãi'* để tôi hỗ trợ nhanh nhất nhé!";

sendResponse($reply);