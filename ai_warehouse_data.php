<?php
// Chỉ được nạp từ ai_assistant.php; các SELECT cố định, không chạy SQL do AI tạo.
if (!isset($context) || !function_exists('readDataset')) {
    http_response_code(404);
    exit;
}
$context['serials'] = readDataset($conn,
    "SELECT s.maSerial, s.soSerial, s.maMau, m.tenMau, h.tenHang, l.tenLoai,
        s.maKho, k.tenKho, s.trangThai, s.tinhTrang, s.giaBan
     FROM danserial s
     LEFT JOIN maudan m ON m.maMau = s.maMau
     LEFT JOIN hangdan h ON h.maHang = m.maHang
     LEFT JOIN loaidan l ON l.maLoai = m.maLoai
     LEFT JOIN kho k ON k.maKho = s.maKho
     ORDER BY s.maSerial", 'serials');
$context['status_summary'] = readDataset($conn,
    "SELECT s.maMau, m.tenMau, s.maKho, k.tenKho, s.trangThai, COUNT(*) AS soLuong
     FROM danserial s
     LEFT JOIN maudan m ON m.maMau = s.maMau
     LEFT JOIN kho k ON k.maKho = s.maKho
     GROUP BY s.maMau, m.tenMau, s.maKho, k.tenKho, s.trangThai
     ORDER BY s.maMau, s.maKho, s.trangThai", 'status_summary');
$context['sales_lines'] = readDataset($conn,
    "SELECT c.maChiTiet, c.maSerial, s.soSerial, m.tenMau,
        c.maHoaDon, d.ngayLap AS ngayLapHoaDon, d.trangThai AS trangThaiHoaDon,
        c.donGia, c.khuyenMai AS khuyenMaiLuuTrongDong
     FROM chitiethoadon c
     LEFT JOIN hoadon d ON d.maHoaDon = c.maHoaDon
     LEFT JOIN danserial s ON s.maSerial = c.maSerial
     LEFT JOIN maudan m ON m.maMau = s.maMau
     ORDER BY d.ngayLap DESC, c.maChiTiet DESC", 'sales_lines');
