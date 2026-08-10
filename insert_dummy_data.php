<?php
require 'db.php';

$conn->begin_transaction();

try {
    // 1. Thêm Chính sách bảo hành
    $sql_policy = "INSERT INTO chinhsachbaohanh (tenChinhSach, noiDung) VALUES (?, ?)";
    $stmt_policy = $conn->prepare($sql_policy);
    
    $policies = [
        [
            "Chính sách bảo hành Piano Cơ (Upright & Grand)",
            "1. Thời gian bảo hành: 10 NĂM kể từ ngày giao đàn.\n2. Nội dung bảo hành:\n- Bảo hành các lỗi kỹ thuật do nhà sản xuất (kẹt phím, đứt dây tự nhiên, nứt thùng âm, gãy búa...).\n- Miễn phí căn chỉnh dây (tuning) 2 lần trong năm đầu tiên.\n3. Trường hợp KHÔNG bảo hành:\n- Đàn bị hỏng do thiên tai, hỏa hoạn, ngập nước, chuột bọ cắn phá.\n- Để đàn ở nơi có độ ẩm quá cao/quá thấp không theo hướng dẫn.\n- Tự ý nhờ thợ ngoài sửa chữa khi chưa thông báo cho cửa hàng."
        ],
        [
            "Chính sách bảo hành Piano Điện & Keyboard",
            "1. Thời gian bảo hành: 2 NĂM chính hãng.\n2. Điều kiện bảo hành:\n- Lỗi bo mạch, màn hình LCD, liệt phím điện tử, hỏng nút bấm do lỗi nhà sản xuất.\n- Khách hàng giữ nguyên tem bảo hành, không có dấu hiệu cạy mở.\n3. 1 ĐỔI 1 TRONG 30 NGÀY:\n- Nếu phát sinh lỗi phần cứng không thể khắc phục, cửa hàng hỗ trợ đổi mới 100% sản phẩm cùng loại."
        ],
        [
            "Chính sách Nâng cấp & Thu cũ đổi mới",
            "1. Cam kết thu mua lại (trong 2 năm đầu):\n- Cửa hàng cam kết thu mua lại đàn đã bán với mức khấu hao từ 15% - 25% giá trị hóa đơn (tùy tình trạng đàn).\n2. Trợ giá nâng cấp đàn:\n- Khi khách hàng có nhu cầu nâng cấp từ Piano Điện lên Piano Cơ, hoặc từ Upright lên Grand, sẽ được trợ giá thêm 10% (Tối đa 5.000.000đ) trên giá trị đàn mới."
        ]
    ];
    
    foreach ($policies as $p) {
        $stmt_policy->bind_param("ss", $p[0], $p[1]);
        $stmt_policy->execute();
    }
    
    // 2. Thêm Chương trình khuyến mãi
    $sql_promo = "INSERT INTO chuongtrinhkhuyenmai (tenChuongTrinh, moTa, phanTramGiam, ngayBatDau, ngayKetThuc, trangThai) VALUES (?, ?, ?, ?, ?, ?)";
    $stmt_promo = $conn->prepare($sql_promo);
    
    $promos = [
        [
            "Vui Tựu Trường - Vững Bước Tương Lai (Back to School)",
            "Chào mừng năm học mới, Kho Đàn Pro mang đến gói ưu đãi khủng dành riêng cho học sinh, sinh viên:\n- Giảm trực tiếp 15% giá trị hóa đơn.\n- Tặng kèm 1 khóa học Piano cơ bản trị giá 2.000.000đ.\n- Tặng ghế da cao cấp và khăn phủ phím.\n* Yêu cầu: Khách hàng xuất trình thẻ Học sinh/Sinh viên còn hạn sử dụng.",
            15,
            date('Y-m-01'), // Đầu tháng này
            date('Y-m-t', strtotime('+1 month')), // Cuối tháng sau
            "Đang diễn ra"
        ],
        [
            "Trung Thu Đoàn Viên - Rinh Đàn Cực Chiến",
            "Sự kiện chào mừng Tết Trung Thu:\n- Giảm 5% cho tất cả dòng Piano Cơ.\n- Tặng Full phụ kiện: Ống sưởi, khăn phủ toàn đàn, ghế xoay thủy lực cao cấp.\n- Miễn phí vận chuyển toàn quốc.",
            5,
            date('Y-m-d', strtotime('+1 month')),
            date('Y-m-d', strtotime('+1 month + 15 days')),
            "Sắp tới"
        ],
        [
            "Clearance Sale - Xả Kho Đón Năm Mới 2026",
            "Chương trình xả kho lớn nhất trong năm:\n- Đồng giá hoặc giảm sâu lên đến 30% cho các mẫu Piano trưng bày tại cửa hàng.\n- Số lượng có hạn, áp dụng cho đến khi hết hàng tồn kho.",
            30,
            '2025-12-01',
            '2025-12-31',
            "Đã kết thúc"
        ]
    ];
    
    foreach ($promos as $p) {
        $stmt_promo->bind_param("ssisss", $p[0], $p[1], $p[2], $p[3], $p[4], $p[5]);
        $stmt_promo->execute();
    }

    $conn->commit();
    echo "Thêm dữ liệu mẫu thành công!";
} catch (Exception $e) {
    $conn->rollback();
    echo "Lỗi: " . $e->getMessage();
}
?>
