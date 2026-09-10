<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'db.php';
require_once 'functions.php';

// Kiểm tra quyền (Chỉ Admin và Sales)
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role_id'], [1, 2])) {
    header("Location: index.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$role_id = $_SESSION['role_id'];
$msg = "";

if (!isset($_GET['id'])) {
    header("Location: hoadon_moi.php");
    exit();
}
$maHoaDon = intval($_GET['id']);

// Lấy thông tin hóa đơn
$stmt = $conn->prepare("SELECT hd.*, kh.hoTen as tenKH FROM hoadon hd LEFT JOIN khachhang kh ON hd.maKhachHang = kh.maKhachHang WHERE hd.maHoaDon = ?");
$stmt->bind_param("i", $maHoaDon);
$stmt->execute();
$res = $stmt->get_result();
if ($res->num_rows == 0) {
    die("Hóa đơn không tồn tại.");
}
$hoadon = $res->fetch_assoc();

// Chỉ cho phép sửa nếu trạng thái là Chờ giao
if ($hoadon['trangThai'] !== 'Chờ giao') {
    die("Hóa đơn đã được xử lý (trạng thái: ".$hoadon['trangThai']."), không thể sửa.");
}

// Kiểm tra quyền: Admin hoặc người lập hóa đơn mới được sửa
if ($role_id != 1 && $hoadon['maNhanVien'] != $user_id) {
    die("Bạn không có quyền sửa hóa đơn của người khác.");
}

// === AJAX: Tra cứu giá niêm yết theo Serial ===
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'lookup_serial') {
    header('Content-Type: application/json; charset=utf-8');
    $serial = trim($_POST['serial'] ?? '');
    if (empty($serial)) {
        echo json_encode(['found' => false, 'error' => 'Serial trống']);
        exit();
    }
    
    // Kiểm tra xem serial này có đang nằm trong chính hóa đơn này không
    $st_self = $conn->prepare("SELECT ct.maSerial FROM chitiethoadon ct JOIN danserial ds ON ct.maSerial = ds.maSerial WHERE ct.maHoaDon = ? AND ds.soSerial = ?");
    $st_self->bind_param("is", $maHoaDon, $serial);
    $st_self->execute();
    $is_self = $st_self->get_result()->num_rows > 0;

    $st = $conn->prepare(
        "SELECT ds.maSerial, ds.soSerial, ds.trangThai, ds.giaBan,
                md.tenMau, lo.tenLoai, ha.tenHang
         FROM danserial ds
         JOIN maudan md ON ds.maMau = md.maMau
         LEFT JOIN loaidan lo ON md.maLoai = lo.maLoai
         LEFT JOIN hangdan ha ON md.maHang = ha.maHang
         WHERE ds.soSerial = ? LIMIT 1"
    );
    $st->bind_param("s", $serial);
    $st->execute();
    $res = $st->get_result();
    if ($res->num_rows == 0) {
        echo json_encode(['found' => false, 'error' => 'Không tìm thấy Serial này trong hệ thống']);
        exit();
    }
    $row = $res->fetch_assoc();
    
    if (!$is_self && $row['trangThai'] !== 'Trong kho') {
        echo json_encode(['found' => false, 'error' => 'Serial [' . $serial . '] không sẵn sàng (Trạng thái: ' . $row['trangThai'] . ')']);
        exit();
    }
    $giaBan = (float)($row['giaBan'] ?? 0);
    if ($giaBan <= 0) {
        echo json_encode([
            'found'      => false,
            'isUnpriced' => true,
            'error'      => 'Đàn vừa nhập kho chưa được Quản trị viên niêm yết giá bán. Không thể lập hóa đơn!',
            'tenMau'     => $row['tenMau'],
            'tenHang'    => $row['tenHang'] ?? ''
        ]);
        exit();
    }
    echo json_encode([
        'found'    => true,
        'tenMau'   => $row['tenMau'],
        'tenLoai'  => $row['tenLoai'] ?? '',
        'tenHang'  => $row['tenHang'] ?? '',
        'giaBan'   => $giaBan,
        'giaBanFmt'=> number_format($giaBan, 0, ',', '.'),
    ]);
    exit();
}

// Xử lý Cập nhật hóa đơn
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['btnUpdateHoaDon'])) {
    $maKhachHang = $_POST['maKhachHang'];
    $maKM = !empty($_POST['maKM']) ? $_POST['maKM'] : NULL;
    $tenDonVi = $_POST['tenDonVi'] ?? '';
    $maSoThue = $_POST['maSoThue'] ?? '';
    $thueGTGT = $_POST['thueGTGT'] ?? 0;
    $hinhThucThanhToan = $_POST['hinhThucThanhToan'];
    $list_serial = $_POST['soSerial'] ?? [];
    
    $conn->begin_transaction();
    try {
        // 1. Phục hồi trạng thái các đàn cũ
        $st_old = $conn->prepare("SELECT maSerial FROM chitiethoadon WHERE maHoaDon = ?");
        $st_old->bind_param("i", $maHoaDon);
        $st_old->execute();
        $res_old = $st_old->get_result();
        while($r = $res_old->fetch_assoc()) {
            $conn->query("UPDATE danserial SET trangThai = 'Trong kho' WHERE maSerial = " . $r['maSerial']);
        }
        
        // Xóa chi tiết cũ
        $conn->query("DELETE FROM chitiethoadon WHERE maHoaDon = $maHoaDon");

        $tongTien = 0;

        // 2. Lưu chi tiết hóa đơn mới
        for ($i = 0; $i < count($list_serial); $i++) {
            $serial = trim($list_serial[$i]);
            if(empty($serial)) continue;
            
            $st_check = $conn->prepare(
                "SELECT ds.maSerial, ds.trangThai, ds.giaBan, md.tenMau
                 FROM danserial ds
                 JOIN maudan md ON ds.maMau = md.maMau
                 WHERE ds.soSerial = ?"
            );
            $st_check->bind_param("s", $serial);
            $st_check->execute();
            $res_check = $st_check->get_result();
            
            if ($res_check->num_rows == 0) {
                throw new Exception("Mã Serial [$serial] không tồn tại!");
            }
            
            $r = $res_check->fetch_assoc();
            $maSerial = $r['maSerial'];
            if ($r['trangThai'] !== 'Trong kho') {
                throw new Exception("Mã Serial [$serial] không sẵn sàng (Trạng thái: {$r['trangThai']})!");
            }
            
            $donGia = (float)$r['giaBan'];
            if ($donGia <= 0) {
                throw new Exception("Serial [$serial] chưa có giá niêm yết!");
            }
            
            $st_ct = $conn->prepare("INSERT INTO chitiethoadon (maHoaDon, maSerial, donGia, khuyenMai) VALUES (?, ?, ?, 0)");
            $st_ct->bind_param("iid", $maHoaDon, $maSerial, $donGia);
            $st_ct->execute();
            
            $conn->query("UPDATE danserial SET trangThai = 'Chờ giao' WHERE maSerial = $maSerial");
            $tongTien += $donGia;
        }

        if ($tongTien == 0) {
            throw new Exception("Hóa đơn phải có ít nhất 1 sản phẩm hợp lệ!");
        }

        // Tính toán Khuyến mãi và Thuế
        $phanTramGiam = 0;
        if (!empty($maKM)) {
            $km_res = $conn->query("SELECT phanTramGiam FROM chuongtrinhkhuyenmai WHERE maKM = " . intval($maKM));
            if ($km_res->num_rows > 0) {
                $phanTramGiam = (float)$km_res->fetch_assoc()['phanTramGiam'];
            }
        }
        $tienGiam = $tongTien * ($phanTramGiam / 100);
        $tienSauGiam = $tongTien - $tienGiam;
        $tienVAT = $tienSauGiam * ((float)$thueGTGT / 100);
        $tongTienCuoi = $tienSauGiam + $tienVAT;

        // Cập nhật thông tin hóa đơn
        $sql_upd = "UPDATE hoadon SET maKhachHang=?, maKM=?, tenDonVi=?, maSoThue=?, hinhThucThanhToan=?, thueGTGT=?, tongTien=? WHERE maHoaDon=?";
        $stmt_upd = $conn->prepare($sql_upd);
        $stmt_upd->bind_param("iisssidi", $maKhachHang, $maKM, $tenDonVi, $maSoThue, $hinhThucThanhToan, $thueGTGT, $tongTienCuoi, $maHoaDon);
        $stmt_upd->execute();

        $conn->commit();
        writeLog($conn, 'SỬA HÓA ĐƠN', "Đã sửa hóa đơn #$maHoaDon");
        
        $_SESSION['flash_success'] = "Đã cập nhật hóa đơn #$maHoaDon thành công!";
        header("Location: hoadon_moi.php");
        exit();
    } catch (Exception $e) {
        $conn->rollback();
        $msg = "<div class='alert alert-danger'><span class='material-symbols-rounded'>error</span> Lỗi: " . $e->getMessage() . "</div>";
    }
}

// Lấy ds khách hàng
$khachhangs = $conn->query("SELECT maKhachHang, hoTen, soDienThoai FROM khachhang ORDER BY hoTen ASC");
// Lấy ds KM
$khuyenmais = $conn->query("SELECT maKM, tenChuongTrinh, phanTramGiam FROM chuongtrinhkhuyenmai WHERE (ngayBatDau <= CURDATE() AND ngayKetThuc >= CURDATE()) OR trangThai = 'Đang diễn ra'");

// Lấy danh sách Serial hiện tại của hóa đơn
$st_serials = $conn->query("SELECT ds.soSerial FROM chitiethoadon ct JOIN danserial ds ON ct.maSerial = ds.maSerial WHERE ct.maHoaDon = $maHoaDon");
$current_serials = [];
while ($r = $st_serials->fetch_assoc()) {
    $current_serials[] = $r['soSerial'];
}

$title = 'Sửa Hóa Đơn Mới';
?>
<?php include 'includes/header.php'; ?>
<?php include 'includes/sidebar.php'; ?>


<div class="main-wrapper">
    <?php include 'includes/topbar.php'; ?>
    <div class="content">
        <div class="form-center-container">
            
            <a href="hoadon_moi.php" style="display:inline-flex; align-items:center; gap:5px; color:var(--text-secondary); text-decoration:none; margin-bottom:15px; font-weight:600;"><span class="material-symbols-rounded">arrow_back</span> Quay lại</a>
            
            <div class="welcome-banner-sales">
                <h2 style="margin:0; font-size: 24px; font-weight: 800; display:flex; align-items:center; gap:10px;">
                    <span class="material-symbols-rounded" style="font-size: 32px; color: var(--sales-text);">edit_document</span>
                    Sửa Hóa Đơn #<?= $maHoaDon ?>
                </h2>
                <p style="margin: 8px 0 0 0; color: var(--text-secondary); font-size: 14px;">Cập nhật lại thông tin hóa đơn khi khách hàng thay đổi ý định hoặc nhập sai sót.</p>
            </div>

            <?= $msg ?>

            <form method="POST" id="hoadonForm">
                <div class="form-card">
                    <div class="card-title"><span class="material-symbols-rounded" style="color: var(--sales-text);">person</span> 1. Thông tin chung</div>
                    <div class="form-grid">
                        <div class="form-group">
                            <label>Khách hàng:</label>
                            <select name="maKhachHang" class="custom-input" required>
                                <?php while($kh = $khachhangs->fetch_assoc()): ?>
                                    <option value="<?= $kh['maKhachHang'] ?>" <?= $kh['maKhachHang'] == $hoadon['maKhachHang'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($kh['hoTen']) ?> - <?= htmlspecialchars($kh['soDienThoai']) ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Hình thức thanh toán:</label>
                            <select name="hinhThucThanhToan" class="custom-input" required>
                                <option value="Tiền mặt" <?= $hoadon['hinhThucThanhToan'] == 'Tiền mặt' ? 'selected' : '' ?>>Tiền mặt</option>
                                <option value="Chuyển khoản" <?= $hoadon['hinhThucThanhToan'] == 'Chuyển khoản' ? 'selected' : '' ?>>Chuyển khoản ngân hàng</option>
                                <option value="Thẻ tín dụng" <?= $hoadon['hinhThucThanhToan'] == 'Thẻ tín dụng' ? 'selected' : '' ?>>Thẻ tín dụng / Quẹt thẻ</option>
                                <option value="Trả góp" <?= $hoadon['hinhThucThanhToan'] == 'Trả góp' ? 'selected' : '' ?>>Trả góp</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Tên đơn vị:</label>
                            <input type="text" name="tenDonVi" class="custom-input" value="<?= htmlspecialchars($hoadon['tenDonVi'] ?? '') ?>" placeholder="Tên công ty / Đơn vị (tùy chọn)">
                        </div>
                        <div class="form-group">
                            <label>Mã số thuế:</label>
                            <input type="text" name="maSoThue" class="custom-input" value="<?= htmlspecialchars($hoadon['maSoThue'] ?? '') ?>" placeholder="Mã số thuế (tùy chọn)">
                        </div>
                        <div class="form-group">
                            <label>Áp dụng khuyến mãi:</label>
                            <select name="maKM" class="custom-input" id="kmSelect" onchange="calculateTotal()">
                                <option value="" data-discount="0">-- Không áp dụng --</option>
                                <?php while($km = $khuyenmais->fetch_assoc()): ?>
                                    <option value="<?= $km['maKM'] ?>" data-discount="<?= $km['phanTramGiam'] ?>" <?= $km['maKM'] == $hoadon['maKM'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($km['tenChuongTrinh']) ?> (Giảm <?= $km['phanTramGiam'] ?>%)
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Thuế suất GTGT (%):</label>
                            <input type="number" name="thueGTGT" class="custom-input" min="0" max="100" value="<?= $hoadon['thueGTGT'] ?>" required oninput="calculateTotal()">
                        </div>
                    </div>
                </div>

                <div class="form-card">
                    <div class="card-title"><span class="material-symbols-rounded" style="color: var(--sales-text);">shopping_cart</span> 2. Chi tiết Sản phẩm</div>
                    <div id="serial-container">
                        <!-- Serials sẽ được tạo bằng JS -->
                    </div>
                    
                    <button type="button" class="btn-action btn-add" onclick="addSerialRow()" style="margin-top: 16px;">
                        <span class="material-symbols-rounded">add</span> Thêm sản phẩm
                    </button>

                    <div class="total-box">
                        <span>Tổng tiền thanh toán:</span>
                        <h2 id="displayTotal"><?= number_format($hoadon['tongTien'], 0, ',', '.') ?> đ</h2>
                    </div>
                </div>

                <div style="text-align: right; padding-bottom: 50px;">
                    <button type="button" class="btn-action btn-submit" onclick="submitForm()">
                        <span class="material-symbols-rounded">save</span> LƯU THAY ĐỔI
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
const existingSerials = <?= json_encode($current_serials) ?>;

window.addEventListener('DOMContentLoaded', () => {
    if (existingSerials.length === 0) {
        addSerialRow();
    } else {
        existingSerials.forEach(serial => {
            addSerialRow(serial);
        });
    }
});

async function lookupSerial(input) {
    const serial = input.value.trim();
    const row    = input.closest('.serial-row-sales');
    const badge  = row.querySelector('.serial-info-badge');
    const displays = row.querySelectorAll('.price-display');
    const hiddenPrice = row.querySelector('.price-input');

    if (!serial) return;

    row.className = 'serial-row-sales is-loading';
    badge.className = 'serial-info-badge loading';
    badge.innerHTML = '<span class="material-symbols-rounded" style="font-size:14px;">autorenew</span> Đang tra cứu...';
    displays.forEach(d => { 
        d.className = 'price-display empty'; 
        d.style.color = '';
        d.style.borderColor = '';
        d.style.background = '';
    });
    displays[0].textContent = 'Đang tải...';
    displays[1].textContent = '-- Đang tải --';

    try {
        const fd = new FormData();
        fd.append('action', 'lookup_serial');
        fd.append('serial', serial);

        const resp = await fetch(window.location.href, { method: 'POST', body: fd });
        const data = await resp.json();

        if (data.found) {
            row.className = 'serial-row-sales is-valid';
            badge.className = 'serial-info-badge ok';
            badge.innerHTML = '<span class="material-symbols-rounded" style="font-size:14px;">check_circle</span> Tìm thấy — Sẵn sàng bán';

            const tenDay = [data.tenHang, data.tenMau, data.tenLoai].filter(Boolean).join(' · ');
            displays[0].className = 'price-display';
            displays[0].textContent = tenDay || data.tenMau;
            displays[0].style.color = '';
            displays[0].style.borderColor = '';
            displays[0].style.background = '';

            displays[1].className = 'price-display';
            displays[1].textContent = data.giaBanFmt + ' đ';
            displays[1].style.color = '';
            displays[1].style.borderColor = '';
            displays[1].style.background = '';

            hiddenPrice.value = data.giaBan;
            calculateTotal();
        } else {
            row.className = 'serial-row-sales is-error';
            badge.className = 'serial-info-badge error';

            if (data.isUnpriced) {
                badge.innerHTML = '<span class="material-symbols-rounded" style="font-size:14px; color:#ef4444;">warning</span> Chưa niêm yết giá bán — Không thể lập hóa đơn!';
                displays[0].className = 'price-display';
                displays[0].style.color = 'var(--text-secondary)';
                displays[0].style.borderColor = 'rgba(239, 68, 68, 0.3)';
                displays[0].style.background = 'rgba(239, 68, 68, 0.05)';
                displays[0].textContent = (data.tenHang ? data.tenHang + ' · ' : '') + (data.tenMau || 'Đã tìm thấy mẫu');

                displays[1].className = 'price-display';
                displays[1].style.color = '#ef4444';
                displays[1].style.borderColor = 'rgba(239, 68, 68, 0.3)';
                displays[1].style.background = 'rgba(239, 68, 68, 0.05)';
                displays[1].textContent = 'Chưa niêm yết giá (0 ₫)';
            } else {
                badge.innerHTML = '<span class="material-symbols-rounded" style="font-size:14px;">error</span> ' + (data.error || 'Không tìm thấy');
                displays[0].className = 'price-display empty';
                displays[0].textContent = 'Không tìm thấy';
                displays[1].className = 'price-display empty';
                displays[1].textContent = '-- Lỗi --';
            }
            hiddenPrice.value = 0;
            calculateTotal();
        }
    } catch(e) {
        badge.className = 'serial-info-badge error';
        badge.innerHTML = '<span class="material-symbols-rounded" style="font-size:14px;">wifi_off</span> Lỗi kết nối server';
        row.className = 'serial-row-sales is-error';
        displays.forEach(d => { d.className = 'price-display empty'; });
        hiddenPrice.value = 0;
        calculateTotal();
    }
}

function getSerialRowTemplate() {
    return `
        <div style="display: flex; flex-direction: column;">
            <label style="font-weight:600;font-size:0.8rem;text-transform:uppercase;margin-bottom:8px;display:block;color:var(--text-secondary);line-height:18px;height:18px;">Số Serial</label>
            <input type="text" name="soSerial[]" class="custom-input serial-input" placeholder="Nhập hoặc quét mã Serial..." required
                onblur="lookupSerial(this)" onkeydown="if(event.key==='Enter'){event.preventDefault();lookupSerial(this);}"
                style="height: 46px; box-sizing: border-box;">
            <div class="serial-info-badge" style="margin-top: 8px; font-size: 12px; min-height: 20px; display: flex; align-items: center; gap: 6px;"></div>
        </div>
        <div class="col-info" style="display: flex; flex-direction: column;">
            <label style="font-weight:600;font-size:0.8rem;text-transform:uppercase;margin-bottom:8px;display:block;color:var(--text-secondary);line-height:18px;height:18px;">Tên Mẫu Đàn</label>
            <div class="price-display empty" style="font-size: 13px; height: 46px; padding: 12px 16px; display: flex; align-items: center; box-sizing: border-box; border-radius: var(--radius-md);">Chưa tra cứu Serial</div>
        </div>
        <div class="col-price" style="display: flex; flex-direction: column;">
            <label style="font-weight:600;font-size:0.8rem;text-transform:uppercase;margin-bottom:8px;display:block;color:var(--text-secondary);line-height:18px;height:18px;">Giá Niêm Yết (VNĐ)</label>
            <div class="price-display empty" style="height: 46px; padding: 12px 16px; display: flex; align-items: center; box-sizing: border-box; border-radius: var(--radius-md);">-- Chưa có --</div>
            <input type="hidden" name="donGia[]" class="price-input" value="0">
        </div>
        <div class="col-action" style="display: flex; flex-direction: column;">
            <label style="font-weight:600;font-size:0.8rem;text-transform:uppercase;margin-bottom:8px;display:block;visibility:hidden;line-height:18px;height:18px;">&nbsp;</label>
            <button type="button" class="btn-action btn-remove" onclick="removeSerialRow(this)" title="Xóa" style="height: 46px; width: 46px; min-width: 46px; padding: 0; display: inline-flex; align-items: center; justify-content: center; box-sizing: border-box; border-radius: var(--radius-md);"><span class="material-symbols-rounded">delete</span></button>
        </div>
    `;
}

function addSerialRow(initialSerial = '') {
    const container = document.getElementById('serial-container');
    const newRow = document.createElement('div');
    newRow.className = 'serial-row-sales';
    newRow.style.display = 'grid';
    newRow.style.gridTemplateColumns = '1.5fr 1fr 1fr auto';
    newRow.style.gap = '16px';
    newRow.style.alignItems = 'start';
    newRow.style.marginBottom = '16px';
    newRow.style.background = 'rgba(255,255,255,0.02)';
    newRow.style.padding = '20px';
    newRow.style.borderRadius = 'var(--radius-md)';
    newRow.style.border = '1px solid var(--glass-border)';
    newRow.innerHTML = getSerialRowTemplate();
    container.appendChild(newRow);
    
    if (initialSerial) {
        const input = newRow.querySelector('.serial-input');
        input.value = initialSerial;
        lookupSerial(input);
    }
}

function removeSerialRow(btn) {
    const rows = document.querySelectorAll('.serial-row-sales');
    if (rows.length > 1) {
        const row = btn.closest('.serial-row-sales');
        row.remove(); 
        calculateTotal();
    } else {
        alert('Phải có ít nhất 1 sản phẩm!');
    }
}

function calculateTotal() {
    const priceInputs = document.querySelectorAll('.price-input');
    let total = 0;
    priceInputs.forEach(input => {
        let val = parseFloat(input.value);
        if (!isNaN(val)) total += val;
    });

    const kmSelect = document.getElementById('kmSelect');
    const discountRate = kmSelect && kmSelect.options[kmSelect.selectedIndex]
        ? parseFloat(kmSelect.options[kmSelect.selectedIndex].getAttribute('data-discount')) || 0 : 0;
    const discountAmount       = total * (discountRate / 100);
    const subTotalAfterDiscount = total - discountAmount;

    const vatInput = document.querySelector('input[name="thueGTGT"]');
    const vatRate   = vatInput ? parseFloat(vatInput.value) || 0 : 0;
    const vatAmount = subTotalAfterDiscount * (vatRate / 100);
    const finalTotal = subTotalAfterDiscount + vatAmount;

    const fmt = v => new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND' }).format(v);
    let text = fmt(total);
    if (discountRate > 0 || vatRate > 0) {
        text = fmt(finalTotal) + ' (Giảm ' + discountRate + '% + VAT ' + vatRate + '%)';
    }
    document.getElementById('displayTotal').innerText = text;
}

function submitForm() {
    const errorRows = document.querySelectorAll('.serial-row-sales.is-error');
    const loadingRows = document.querySelectorAll('.serial-row-sales.is-loading');
    if (loadingRows.length > 0) {
        alert('Vui lòng đợi hệ thống tra cứu Serial xong!');
        return;
    }
    
    const allRows = document.querySelectorAll('.serial-row-sales');
    let hasUnpriced = false;
    let unpricedSerials = [];
    let validCount = 0;
    
    allRows.forEach(r => {
        const input = r.querySelector('.serial-input');
        const priceInput = r.querySelector('.price-input');
        const price = parseFloat(priceInput ? priceInput.value : 0) || 0;
        const serialVal = input ? input.value.trim() : '';
        if (serialVal !== '') {
            if (price <= 0) {
                hasUnpriced = true;
                unpricedSerials.push(serialVal);
            } else {
                validCount++;
            }
        }
    });
    
    if (hasUnpriced) {
        alert('CẢNH BÁO: Cây đàn [' + unpricedSerials.join(', ') + '] chưa được Quản trị viên niêm yết giá bán (Giá: 0đ).\n\nKhông thể lập hoặc cập nhật hóa đơn bán hàng cho sản phẩm chưa có giá niêm yết!');
        return;
    }
    
    if (errorRows.length > 0) {
        alert('Có dòng Serial bị lỗi trong danh sách. Vui lòng kiểm tra lại hoặc xóa dòng lỗi trước khi lưu hóa đơn!');
        return;
    }
    
    if (validCount === 0) {
        alert('Chưa có sản phẩm nào hợp lệ! Vui lòng nhập và tra cứu mã Serial.');
        return;
    }
    
    const form = document.getElementById('hoadonForm');
    if (form.reportValidity()) {
        if (confirm('Xác nhận lưu thay đổi hóa đơn này?')) {
            const hidden = document.createElement('input');
            hidden.type = 'hidden'; hidden.name = 'btnUpdateHoaDon'; hidden.value = '1';
            form.appendChild(hidden);
            form.submit();
        }
    }
}
</script>

<?php include 'includes/footer.php'; ?>
