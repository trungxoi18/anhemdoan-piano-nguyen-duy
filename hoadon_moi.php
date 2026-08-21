<?php
// Kiểm tra session để tránh lỗi Notice
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'db.php';
require_once 'functions.php';

// Kiểm tra quyền (Chỉ Admin và Sales được lập hóa đơn)
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role_id'], [1, 2, 3])) {
    header("Location: index.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$role_id = $_SESSION['role_id'];
$msg = "";

// === AJAX: Tra cứu giá niêm yết theo Serial ===
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'lookup_serial') {
    header('Content-Type: application/json; charset=utf-8');
    $serial = trim($_POST['serial'] ?? '');
    if (empty($serial)) {
        echo json_encode(['found' => false, 'error' => 'Serial trống']);
        exit();
    }
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
    if ($row['trangThai'] !== 'Trong kho') {
        echo json_encode(['found' => false, 'error' => 'Serial [' . $serial . '] không sẵn sàng (Trạng thái: ' . $row['trangThai'] . ')']);
        exit();
    }
    echo json_encode([
        'found'    => true,
        'tenMau'   => $row['tenMau'],
        'tenLoai'  => $row['tenLoai'] ?? '',
        'tenHang'  => $row['tenHang'] ?? '',
        'giaBan'   => (float)$row['giaBan'],
        'giaBanFmt'=> number_format((float)$row['giaBan'], 0, ',', '.'),
    ]);
    exit();
}

// Xử lý lưu hóa đơn mới
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $maKhachHang = $_POST['maKhachHang'];
    $maKM = !empty($_POST['maKM']) ? $_POST['maKM'] : NULL;
    $tenDonVi = $_POST['tenDonVi'] ?? '';
    $maSoThue = $_POST['maSoThue'] ?? '';
    $thueGTGT = $_POST['thueGTGT'] ?? 0;
    $hinhThucThanhToan = $_POST['hinhThucThanhToan'];
    $list_serial = $_POST['soSerial'];
    // Giá bán được lấy từ DB, không tin vào $_POST['donGia'] (chỉ dùng làm fallback)
    $list_dongia_post = $_POST['donGia'] ?? [];
    
    // Hóa đơn được lập luôn không cần duyệt, chuyển sang trạng thái chờ giao để Thủ kho làm phiếu xuất
    $trangThai = 'Chờ giao';

    $conn->begin_transaction();
    try {
        // 1. Lưu thông tin chung của Hóa đơn (tongTien tạm thời = 0)
        $sql_hd = "INSERT INTO hoadon (maKhachHang, maKM, tenDonVi, maSoThue, hinhThucThanhToan, thueGTGT, maNhanVien, ngayLap, tongTien, trangThai) VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), 0, ?)";
        $stmt = $conn->prepare($sql_hd);
        $stmt->bind_param("iisssiis", $maKhachHang, $maKM, $tenDonVi, $maSoThue, $hinhThucThanhToan, $thueGTGT, $user_id, $trangThai);
        $stmt->execute();
        $maHoaDon = $conn->insert_id;

        $tongTien = 0;

        // 2. Lưu chi tiết hóa đơn (từng cây đàn) — Giá lấy từ DB (giaBan)
        for ($i = 0; $i < count($list_serial); $i++) {
            $serial = trim($list_serial[$i]);
            if(empty($serial)) continue;
            
            // Kiểm tra Serial tồn tại, đang Trong kho và lấy giaBan từ DB
            $st_check = $conn->prepare(
                "SELECT ds.maSerial, ds.trangThai, ds.giaBan, md.tenMau
                 FROM danserial ds
                 JOIN maudan md ON ds.maMau = md.maMau
                 WHERE ds.soSerial = ? AND ds.trangThai = 'Trong kho'"
            );
            $st_check->bind_param("s", $serial);
            $st_check->execute();
            $res = $st_check->get_result();
            
            if ($res->num_rows == 0) {
                // Kiểm tra xem serial có tồn tại nhưng sai trạng thái không
                $st_any = $conn->prepare("SELECT trangThai FROM danserial WHERE soSerial = ?");
                $st_any->bind_param("s", $serial);
                $st_any->execute();
                $res_any = $st_any->get_result();
                if ($res_any->num_rows > 0) {
                    $r_any = $res_any->fetch_assoc();
                    throw new Exception("Serial [$serial] không sẵn sàng để bán (Trạng thái: {$r_any['trangThai']})");
                }
                throw new Exception("Mã Serial [$serial] không tồn tại trong hệ thống!");
            }
            
            $row = $res->fetch_assoc();
            $maSerial = $row['maSerial'];
            $donGia   = (float)$row['giaBan']; // ← Lấy giá niêm yết từ DB
            
            if ($donGia <= 0) {
                throw new Exception("Serial [$serial] ({$row['tenMau']}) chưa có giá niêm yết trong hệ thống!");
            }
            
            // Thêm vào chi tiết hóa đơn
            $st_ct = $conn->prepare("INSERT INTO chitiethoadon (maHoaDon, maSerial, donGia, khuyenMai) VALUES (?, ?, ?, 0)");
            $st_ct->bind_param("iid", $maHoaDon, $maSerial, $donGia);
            $st_ct->execute();
            
            // Cập nhật trạng thái sản phẩm sang 'Chờ giao'
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

        // Cập nhật lại tổng tiền cho hóa đơn
        $conn->query("UPDATE hoadon SET tongTien = $tongTienCuoi WHERE maHoaDon = $maHoaDon");

        $conn->commit();
        
        // Ghi log
        writeLog($conn, 'LẬP HÓA ĐƠN', "Đã lập hóa đơn #$maHoaDon với tổng tiền " . number_format($tongTien, 0, ',', '.') . "đ");

        // Thêm thông báo cho người lập (Tài khoản hiện tại)
        $msg_user = "Đã lập hóa đơn thành công #" . $maHoaDon;
        $conn->query("INSERT INTO ThongBao (maTaiKhoan, noiDung, link) VALUES ($user_id, '$msg_user', 'hoadon_action.php?id=$maHoaDon')");

        // Thêm thông báo cho Thủ kho (maVaiTro = 3)
        $msg_thukho = "Nhập đơn xuất cho hóa đơn #" . $maHoaDon;
        $conn->query("INSERT INTO ThongBao (maVaiTro, noiDung, link) VALUES (3, '$msg_thukho', 'hoadon_action.php?id=$maHoaDon')");

        // Thêm thông báo cho Admin (maVaiTro = 1)
        $msg_admin = "Hóa đơn mới được lập #" . $maHoaDon;
        $conn->query("INSERT INTO ThongBao (maVaiTro, noiDung, link) VALUES (1, '$msg_admin', 'hoadon_action.php?id=$maHoaDon')");

        $_SESSION['flash_success'] = "Lập hóa đơn #$maHoaDon thành công!";
        header("Location: hoadon_action.php?id=$maHoaDon");
        exit();
    } catch (Exception $e) {
        $conn->rollback();
        $msg = "<div class='alert alert-danger' style='background: var(--danger-bg); color: var(--danger); border: 1px solid rgba(248,113,113,0.3); padding:15px; border-radius:12px; margin-bottom:24px; display: flex; align-items: center; gap: 10px;'><span class='material-symbols-rounded'>error</span> Lỗi: " . $e->getMessage() . "</div>";
    }
}

// Lấy danh sách khách hàng
$khachhangs = $conn->query("SELECT maKhachHang, hoTen, soDienThoai FROM khachhang ORDER BY hoTen ASC");

// Lấy chương trình khuyến mãi đang diễn ra
$sql_km = "SELECT * FROM chuongtrinhkhuyenmai WHERE trangThai = 'Đang diễn ra'";
$khuyenmais = $conn->query($sql_km);

// Lấy lịch sử hóa đơn
$sql_history = "SELECT hd.*, kh.hoTen as tenKH, nv.hoTen as tenNV 
                FROM hoadon hd 
                LEFT JOIN khachhang kh ON hd.maKhachHang = kh.maKhachHang
                LEFT JOIN nhanvien nv ON hd.maNhanVien = nv.maNhanVien ";
if ($_SESSION['role_id'] != 1) {
    $sql_history .= " WHERE hd.maNhanVien = $user_id ";
}
$sql_history .= " ORDER BY hd.ngayLap DESC LIMIT 50";
$history_result = $conn->query($sql_history);
?>

<?php include 'includes/header.php'; ?>
<?php include 'includes/sidebar.php'; ?>



<div class="main-wrapper">
    <?php include 'includes/topbar.php'; ?>

    <div class="content">
        <div class="form-center-container">
            <div class="welcome-banner-sales">
                <h1 style="margin: 0 0 8px 0; font-size: 1.6rem; color: var(--sales-text);">Lập Hóa Đơn Mới</h1>
                <p style="margin: 0; opacity: 0.8; font-size: 14px;">Nhân viên thực hiện: <b style="color: white;"><?php echo htmlspecialchars($_SESSION['fullname'] ?? 'Admin'); ?></b></p>
            </div>

            <?php echo $msg; ?>

            <div class="nav-tabs">
                <div class="nav-tab active" onclick="switchTab('new')">
                    <span class="material-symbols-rounded">add_circle</span> Lập hóa đơn mới
                </div>
                <div class="nav-tab" onclick="switchTab('history')">
                    <span class="material-symbols-rounded">history</span> Lịch sử hóa đơn
                </div>
            </div>

            <div class="tab-pane active" id="tab-new">
                <form method="POST" id="hoadonForm">
                <div class="form-card">
                    <div class="card-title"><span class="material-symbols-rounded" style="color: var(--sales-text);">person</span> 1. Thông tin Khách Hàng & Thanh toán</div>
                    <div class="input-grid">
                        <div class="form-group">
                            <label>Chọn Khách hàng:</label>
                            <div style="display: flex; gap: 8px;">
                                <select name="maKhachHang" class="custom-input" required>
                                    <option value="">-- Chọn khách hàng --</option>
                                    <?php while($kh = $khachhangs->fetch_assoc()): ?>
                                        <option value="<?= $kh['maKhachHang'] ?>"><?= htmlspecialchars($kh['hoTen']) ?> (<?= htmlspecialchars($kh['soDienThoai']) ?>)</option>
                                    <?php endwhile; ?>
                                </select>
                                <a href="khachhang.php" class="btn-action btn-add" style="padding: 14px;" title="Thêm KH mới"><span class="material-symbols-rounded" style="margin:0;">person_add</span></a>
                            </div>
                        </div>
                        <div class="form-group">
                            <label>Hình thức thanh toán:</label>
                            <select name="hinhThucThanhToan" class="custom-input" required>
                                <option value="Tiền mặt">Tiền mặt</option>
                                <option value="Chuyển khoản">Chuyển khoản</option>
                                <option value="Thẻ tín dụng">Thẻ tín dụng / Quẹt thẻ</option>
                                <option value="Trả góp">Trả góp</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Tên đơn vị:</label>
                            <input type="text" name="tenDonVi" class="custom-input" placeholder="Tên công ty / Đơn vị (tùy chọn)">
                        </div>
                        <div class="form-group">
                            <label>Mã số thuế:</label>
                            <input type="text" name="maSoThue" class="custom-input" placeholder="Mã số thuế (tùy chọn)">
                        </div>
                        <div class="form-group">
                            <label>Áp dụng khuyến mãi:</label>
                            <select name="maKM" class="custom-input" id="kmSelect" onchange="calculateTotal()">
                                <option value="" data-discount="0">-- Không áp dụng --</option>
                                <?php while($km = $khuyenmais->fetch_assoc()): ?>
                                    <option value="<?= $km['maKM'] ?>" data-discount="<?= $km['phanTramGiam'] ?>"><?= htmlspecialchars($km['tenChuongTrinh']) ?> (Giảm <?= $km['phanTramGiam'] ?>%)</option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Thuế suất GTGT (%):</label>
                            <input type="number" name="thueGTGT" class="custom-input" min="0" max="100" value="0" required oninput="calculateTotal()">
                        </div>
                    </div>
                </div>

                <div class="form-card">
                    <div class="card-title"><span class="material-symbols-rounded" style="color: var(--sales-text);">shopping_cart</span> 2. Chi tiết Sản phẩm <small style="font-size:12px; font-weight:400; opacity:0.6; margin-left:8px;">Giá bán tự động lấy từ hệ thống</small></div>
                    <div id="serial-container">
                        <div class="serial-row-sales">
                            <div>
                                <label style="font-weight: 600; font-size: 0.8rem; text-transform: uppercase; margin-bottom: 8px; display: block; color: var(--text-secondary);">Số Serial</label>
                                <input type="text" name="soSerial[]" class="custom-input serial-input" placeholder="Nhập hoặc quét mã Serial..." required
                                    onblur="lookupSerial(this)" onkeydown="if(event.key==='Enter'){event.preventDefault();lookupSerial(this);}">
                                <div class="serial-info-badge"></div>
                            </div>
                            <div class="col-info">
                                <label style="font-weight: 600; font-size: 0.8rem; text-transform: uppercase; margin-bottom: 8px; display: block; color: var(--text-secondary);">Tên Mẫu Đàn</label>
                                <div class="price-display empty" style="font-size:13px;">Chưa tra cứu Serial</div>
                            </div>
                            <div class="col-price">
                                <label style="font-weight: 600; font-size: 0.8rem; text-transform: uppercase; margin-bottom: 8px; display: block; color: var(--text-secondary);">Giá Niêm Yết (VNĐ)</label>
                                <div class="price-display empty">-- Chưa có --</div>
                                <input type="hidden" name="donGia[]" class="price-input" value="0">
                            </div>
                            <button type="button" class="btn-action btn-remove" onclick="removeSerialRow(this)" title="Xóa"><span class="material-symbols-rounded">delete</span></button>
                        </div>
                    </div>
                    
                    <button type="button" class="btn-action btn-add" onclick="addSerialRow()" style="margin-top: 16px;">
                        <span class="material-symbols-rounded">add</span> Thêm sản phẩm
                    </button>

                    <div class="total-box">
                        <span>Tổng tiền thanh toán:</span>
                        <h2 id="displayTotal">0 đ</h2>
                    </div>
                </div>

                <div style="text-align: right; padding-bottom: 50px;">
                    <button type="button" class="btn-action btn-submit" onclick="submitForm()">
                        <span class="material-symbols-rounded">send</span> TẠO HÓA ĐƠN
                    </button>
                </div>
            </form>
            </div> <!-- End tab-new -->

            <!-- TAB LỊCH SỬ -->
            <div class="tab-pane" id="tab-history">
                <div class="form-card">
                    <div class="card-title">
                        <span class="material-symbols-rounded">history</span> 
                        Lịch sử lập hóa đơn
                    </div>
                    <?php if ($history_result && $history_result->num_rows > 0): ?>
                    <table class="history-table">
                        <thead>
                            <tr>
                                <th>Mã HĐ</th>
                                <th>Khách hàng</th>
                                <th>Thời gian</th>
                                <th>Thanh toán</th>
                                <th>Tổng tiền</th>
                                <th>Người lập</th>
                                <th>Trạng thái</th>
                                <th>Thao tác</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while($row = $history_result->fetch_assoc()): ?>
                            <tr>
                                <td><strong>#<?= $row['maHoaDon'] ?></strong></td>
                                <td><?= htmlspecialchars($row['tenKH'] ?? 'Khách lẻ') ?></td>
                                <td><?= date('d/m/Y H:i', strtotime($row['ngayLap'])) ?></td>
                                <td><?= htmlspecialchars($row['hinhThucThanhToan'] ?? 'N/A') ?></td>
                                <td style="color: var(--sales-text); font-weight: 700;"><?= number_format($row['tongTien']) ?>đ</td>
                                <td><?= htmlspecialchars($row['tenNV'] ?? 'N/A') ?></td>
                                <td><span class="status-badge <?= str_replace(' ', '.', $row['trangThai']) ?>"><?= $row['trangThai'] ?></span></td>
                                <td>
                                    <?php if ($row['trangThai'] == 'Chờ giao'): ?>
                                        <a href="sua_hoadon.php?id=<?= $row['maHoaDon'] ?>" class="btn-action" style="padding: 6px 12px; background: rgba(59, 130, 246, 0.1); color: var(--sales-text); font-size: 13px; text-decoration: none; border-radius: 6px; margin-right: 5px;">Sửa</a>
                                    <?php endif; ?>
                                    <a href="xuat_pdf.php?type=hoadon&id=<?= $row['maHoaDon'] ?>" target="_blank" class="btn-action" style="padding: 6px 12px; background: rgba(16, 185, 129, 0.1); color: var(--success); font-size: 13px; text-decoration: none; border-radius: 6px;">In HĐ</a>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                    <?php else: ?>
                        <div style="text-align: center; padding: 40px; color: var(--text-muted);">
                            <span class="material-symbols-rounded" style="font-size: 48px; opacity: 0.5;">receipt_long</span>
                            <p>Chưa có lịch sử lập hóa đơn nào.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div> <!-- End tab-history -->
        </div>
    </div>
</div>

<script>
function switchTab(tabId) {
    document.querySelectorAll('.nav-tab').forEach(t => t.classList.remove('active'));
    document.querySelectorAll('.tab-pane').forEach(p => p.classList.remove('active'));
    
    document.querySelector('.nav-tab[onclick*="' + tabId + '"]').classList.add('active');
    document.getElementById('tab-' + tabId).classList.add('active');
}

// ============================================================
// Tra cứu Serial → Tự động điền giá niêm yết từ DB
// ============================================================
async function lookupSerial(input) {
    const serial = input.value.trim();
    const row    = input.closest('.serial-row-sales');
    const badge  = row.querySelector('.serial-info-badge');
    const displays = row.querySelectorAll('.price-display');
    const hiddenPrice = row.querySelector('.price-input');

    if (!serial) return;

    // --- Loading state ---
    row.className = 'serial-row-sales is-loading';
    badge.className = 'serial-info-badge loading';
    badge.innerHTML = '<span class="material-symbols-rounded" style="font-size:14px;">autorenew</span> Đang tra cứu...';
    displays.forEach(d => { d.className = 'price-display empty'; });
    displays[0].textContent = 'Đang tải...';
    displays[1].textContent = '-- Đang tải --';

    try {
        const fd = new FormData();
        fd.append('action', 'lookup_serial');
        fd.append('serial', serial);

        const resp = await fetch(window.location.href, { method: 'POST', body: fd });
        const data = await resp.json();

        if (data.found) {
            // --- Thành công: điền giá ---
            row.className = 'serial-row-sales is-valid';
            badge.className = 'serial-info-badge ok';
            badge.innerHTML = '<span class="material-symbols-rounded" style="font-size:14px;">check_circle</span> Tìm thấy — Sẵn sàng bán';

            const tenDay = [data.tenHang, data.tenMau, data.tenLoai].filter(Boolean).join(' · ');
            displays[0].className = 'price-display';
            displays[0].textContent = tenDay || data.tenMau;

            displays[1].className = 'price-display';
            displays[1].textContent = data.giaBanFmt + ' ₫';

            hiddenPrice.value = data.giaBan;
            calculateTotal();
        } else {
            // --- Lỗi: serial không hợp lệ ---
            row.className = 'serial-row-sales is-error';
            badge.className = 'serial-info-badge error';
            badge.innerHTML = '<span class="material-symbols-rounded" style="font-size:14px;">error</span> ' + (data.error || 'Không tìm thấy');

            displays[0].className = 'price-display empty';
            displays[0].textContent = 'Không tìm thấy';
            displays[1].className = 'price-display empty';
            displays[1].textContent = '-- Lỗi --';
            hiddenPrice.value = 0;
            calculateTotal();
        }
    } catch(e) {
        badge.className = 'serial-info-badge error';
        badge.innerHTML = '<span class="material-symbols-rounded" style="font-size:14px;">wifi_off</span> Lỗi kết nối server';
        row.className = 'serial-row-sales is-error';
    }
}

function getSerialRowTemplate() {
    return `
        <div>
            <label style="font-weight:600;font-size:0.8rem;text-transform:uppercase;margin-bottom:8px;display:block;color:var(--text-secondary);">Số Serial</label>
            <input type="text" name="soSerial[]" class="custom-input serial-input" placeholder="Nhập hoặc quét mã Serial..." required
                onblur="lookupSerial(this)" onkeydown="if(event.key==='Enter'){event.preventDefault();lookupSerial(this);}">
            <div class="serial-info-badge"></div>
        </div>
        <div class="col-info">
            <label style="font-weight:600;font-size:0.8rem;text-transform:uppercase;margin-bottom:8px;display:block;color:var(--text-secondary);">Tên Mẫu Đàn</label>
            <div class="price-display empty" style="font-size:13px;">Chưa tra cứu Serial</div>
        </div>
        <div class="col-price">
            <label style="font-weight:600;font-size:0.8rem;text-transform:uppercase;margin-bottom:8px;display:block;color:var(--text-secondary);">Giá Niêm Yết (VNĐ)</label>
            <div class="price-display empty">-- Chưa có --</div>
            <input type="hidden" name="donGia[]" class="price-input" value="0">
        </div>
        <button type="button" class="btn-action btn-remove" onclick="removeSerialRow(this)" title="Xóa"><span class="material-symbols-rounded">delete</span></button>
    `;
}

function addSerialRow() {
    const container = document.getElementById('serial-container');
    const newRow = document.createElement('div');
    newRow.className = 'serial-row-sales';
    newRow.innerHTML = getSerialRowTemplate();
    container.appendChild(newRow);
    newRow.style.opacity = '0';
    newRow.style.transform = 'translateY(10px)';
    setTimeout(() => {
        newRow.style.transition = '0.3s ease';
        newRow.style.opacity = '1';
        newRow.style.transform = 'translateY(0)';
        newRow.querySelector('.serial-input').focus();
    }, 10);
}

function removeSerialRow(btn) {
    const rows = document.querySelectorAll('.serial-row-sales');
    if (rows.length > 1) {
        const row = btn.closest('.serial-row-sales');
        row.style.opacity = '0';
        row.style.transform = 'scale(0.95)';
        setTimeout(() => { row.remove(); calculateTotal(); }, 300);
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
    // Kiểm tra tất cả serial rows đã lookup thành công
    const errorRows = document.querySelectorAll('.serial-row-sales.is-error');
    const loadingRows = document.querySelectorAll('.serial-row-sales.is-loading');
    if (loadingRows.length > 0) {
        alert('Vui lòng đợi hệ thống tra cứu Serial xong!');
        return;
    }
    const validRows = document.querySelectorAll('.serial-row-sales.is-valid');
    const allRows   = document.querySelectorAll('.serial-row-sales');
    if (validRows.length === 0) {
        alert('Chưa có sản phẩm nào hợp lệ! Vui lòng nhập và tra cứu mã Serial.');
        return;
    }
    if (errorRows.length > 0) {
        if (!confirm('Có ' + errorRows.length + ' dòng Serial lỗi sẽ bị bỏ qua. Tiếp tục?')) return;
    }
    const form = document.getElementById('hoadonForm');
    if (form.reportValidity()) {
        if (confirm('Xác nhận tạo hóa đơn? Hóa đơn sẽ được lập luôn và chuyển cho Thủ kho làm phiếu xuất.')) {
            const hidden = document.createElement('input');
            hidden.type = 'hidden'; hidden.name = 'btnLuuHoaDon'; hidden.value = '1';
            form.appendChild(hidden);
            form.submit();
        }
    }
}
</script>

<?php include 'includes/footer.php'; ?>
