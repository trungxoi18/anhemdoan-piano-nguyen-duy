<?php
// Kiểm tra session để tránh lỗi Notice
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'db.php';
require_once 'functions.php';

// Kiểm tra quyền
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role_id'], [1, 2])) {
    header("Location: index.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$role_id = $_SESSION['role_id'];
$msg = "";

// Xử lý lưu phiếu xuất
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['btnLuuPhieuXuat'])) {
    $maHoaDon = !empty($_POST['maHoaDon']) ? $_POST['maHoaDon'] : NULL;
    $lyDoXuat = $_POST['lyDoXuat'];
    $trangThai = 'Chờ duyệt'; // Chờ admin duyệt
    
    $nguoiNhanHang = trim($_POST['nguoiNhanHang'] ?? '');
    $soChungTu = trim($_POST['soChungTu'] ?? '');
    $ngayChungTu = !empty($_POST['ngayChungTu']) ? $_POST['ngayChungTu'] : NULL;
    $donViNhan = trim($_POST['donViNhan'] ?? '');
    
    $list_serial = $_POST['soSerial'];

    $conn->begin_transaction();
    try {
        if (!empty($maHoaDon)) {
            $check_hd = $conn->prepare("SELECT trangThai FROM hoadon WHERE maHoaDon = ?");
            $check_hd->bind_param("i", $maHoaDon);
            $check_hd->execute();
            $res_hd = $check_hd->get_result();
            if ($res_hd->num_rows > 0) {
                $hd_status = $res_hd->fetch_assoc()['trangThai'];
                if ($hd_status != 'Chờ giao') {
                    throw new Exception("Hóa đơn này đã được xử lý hoặc lập phiếu xuất trước đó!");
                }
            }
        }

        $sql_phieu = "INSERT INTO phieuxuat (maNhanVien, maHoaDon, nguoiNhanHang, soChungTu, ngayChungTu, donViNhan, ngayXuat, lyDoXuat, maKho, trangThai) VALUES (?, ?, ?, ?, ?, ?, NOW(), ?, NULL, ?)";
        $stmt = $conn->prepare($sql_phieu);
        $stmt->bind_param("iissssss", $user_id, $maHoaDon, $nguoiNhanHang, $soChungTu, $ngayChungTu, $donViNhan, $lyDoXuat, $trangThai);
        $stmt->execute();
        $maPhieuXuat = $conn->insert_id;

        $list_serial = $_POST['soSerial'];
        $list_makho = $_POST['maKhoList'];
        $soLuongXuat = 0;
        foreach ($list_serial as $index => $serial) {
            $serial = trim($serial);
            if(empty($serial)) continue;
            
            $maKhoSelected = intval($list_makho[$index]);
            
            // Bước 1: Khóa dòng (Row-level lock) Serial này để tránh đụng độ
            $st_check_kho = $conn->prepare("SELECT maSerial, trangThai FROM danserial WHERE soSerial = ? AND maKho = ? FOR UPDATE");
            $st_check_kho->bind_param("si", $serial, $maKhoSelected);
            $st_check_kho->execute();
            $res_kho = $st_check_kho->get_result();
            if ($res_kho->num_rows == 0) {
                // Check where it actually is to show a better error
                $st_any = $conn->prepare("SELECT k.tenKho FROM danserial ds JOIN kho k ON ds.maKho = k.maKho WHERE ds.soSerial = ?");
                $st_any->bind_param("s", $serial);
                $st_any->execute();
                $res_any = $st_any->get_result();
                if ($res_any->num_rows > 0) {
                    $r_any = $res_any->fetch_assoc();
                    throw new Exception("Mã Serial [$serial] không thuộc kho đã chọn! (Đang ở: {$r_any['tenKho']})");
                }
                throw new Exception("Mã Serial [$serial] không tồn tại trong hệ thống!");
            }
            $row_kho = $res_kho->fetch_assoc();

            // Bước 2: Kiểm tra trạng thái serial (State Machine Validation khắt khe)
            if ($maHoaDon && !in_array($row_kho['trangThai'], ['Trong kho', 'Chờ giao'])) {
                throw new Exception("Mã Serial [$serial] không thuộc hóa đơn hoặc không sẵn sàng (Trạng thái hiện tại: {$row_kho['trangThai']})!");
            } elseif (!$maHoaDon && $row_kho['trangThai'] !== 'Trong kho') {
                throw new Exception("Mã Serial [$serial] hiện không sẵn sàng để xuất trực tiếp (Trạng thái hiện tại: {$row_kho['trangThai']})!");
            }
            
            $maSerial = $row_kho['maSerial'];
            
            $conn->query("INSERT INTO chitietphieuxuat (maPhieuXuat, maSerial) VALUES ($maPhieuXuat, $maSerial)");
            
            // Cập nhật trạng thái đàn sang "Chờ xuất" thay vì Đã bán
            $conn->query("UPDATE danserial SET trangThai = 'Chờ xuất' WHERE maSerial = $maSerial");
            $soLuongXuat++;
        }

        if ($soLuongXuat == 0) {
            throw new Exception("Phiếu xuất phải có ít nhất 1 sản phẩm hợp lệ!");
        }

        // Cập nhật trạng thái hóa đơn nếu có liên kết
        if ($maHoaDon) {
            $conn->query("UPDATE hoadon SET trangThai = 'Đang giao' WHERE maHoaDon = $maHoaDon");
        }
        $conn->commit();
        
        // Ghi log
        writeLog($conn, 'XUẤT KHO', "Đã lập phiếu xuất kho #$maPhieuXuat - Xuất $soLuongXuat sản phẩm");

        // Thông báo cho người xuất
        $user_link = ($_SESSION['role_id'] == 1) ? 'duyet_phieu.php' : 'phieuxuat.php';
        $msg_xuat = "Lập phiếu xuất thành công (Chờ duyệt)! Phiếu #$maPhieuXuat - $soLuongXuat sản phẩm";
        $conn->query("INSERT INTO ThongBao (maTaiKhoan, noiDung, link) VALUES ($user_id, '$msg_xuat', '$user_link')");

        // Thông báo cho Admin (role 1)
        $msg_admin = "Có phiếu xuất kho mới #$maPhieuXuat cần được phê duyệt ($soLuongXuat SP)";
        $conn->query("INSERT INTO ThongBao (maVaiTro, noiDung, link) VALUES (1, '$msg_admin', 'duyet_phieu.php')");

        $_SESSION['flash_success'] = "Lập phiếu xuất thành công! Phiếu #$maPhieuXuat đang chờ phê duyệt.";
        header("Location: xuat_pdf.php?type=phieuxuat&id=$maPhieuXuat");
        exit();
    } catch (Exception $e) {
        $conn->rollback();
        $msg = "<div class='alert alert-danger' style='background: var(--danger-bg); color: var(--danger); border: 1px solid rgba(248,113,113,0.3); padding:15px; border-radius:12px; margin-bottom:24px; display: flex; align-items: center; gap: 10px;'><span class='material-symbols-rounded'>error</span> Lỗi: " . $e->getMessage() . "</div>";
    }
}

$hoadons = $conn->query("SELECT maHoaDon, ngayLap FROM hoadon WHERE trangThai = 'Chờ giao'");
$khos = $conn->query("SELECT * FROM kho");
$khos_data = [];
while($k = $khos->fetch_assoc()) {
    $khos_data[] = $k;
}
$kho_options_html = "";
foreach($khos_data as $k) {
    $kho_options_html .= "<option value='{$k['maKho']}'>".htmlspecialchars($k['tenKho'])."</option>";
}

$prefill_serials = [];
if (isset($_GET['maHoaDon']) && !empty($_GET['maHoaDon'])) {
    $maHoaDon = intval($_GET['maHoaDon']);
    $sql_serials = "SELECT ds.soSerial, ds.maKho FROM chitiethoadon cthd 
                    JOIN danserial ds ON cthd.maSerial = ds.maSerial 
                    WHERE cthd.maHoaDon = ?";
    $stmt_serials = $conn->prepare($sql_serials);
    $stmt_serials->bind_param("i", $maHoaDon);
    $stmt_serials->execute();
    $res_serials = $stmt_serials->get_result();
    while ($r = $res_serials->fetch_assoc()) {
        $prefill_serials[] = [
            'soSerial' => $r['soSerial'],
            'maKho' => $r['maKho']
        ];
    }
}

// Lấy lịch sử phiếu xuất (Admin thấy tất cả, User thấy của mình)
$sql_history = "SELECT p.*, k.tenKho, nv.hoTen, 
                (SELECT COUNT(*) FROM chitietphieuxuat ct WHERE ct.maPhieuXuat = p.maPhieuXuat) as soLuong 
                FROM phieuxuat p 
                LEFT JOIN kho k ON p.maKho = k.maKho
                LEFT JOIN nhanvien nv ON p.maNhanVien = nv.maNhanVien ";
if ($_SESSION['role_id'] != 1) {
    $sql_history .= " WHERE p.maNhanVien = $user_id ";
}
$sql_history .= " ORDER BY p.ngayXuat DESC LIMIT 50";
$history_result = $conn->query($sql_history);
?>

<?php include 'includes/header.php'; ?>
<?php include 'includes/sidebar.php'; ?>


<div class="main-wrapper">
    <?php include 'includes/topbar.php'; ?>

    <div class="content">
        <div class="form-center-container">
            <div class="welcome-banner-export">
                <h1>Lập Phiếu Xuất Kho</h1>
                <p>Nhân viên thực hiện: <b><?php echo htmlspecialchars($_SESSION['fullname'] ?? 'Admin'); ?></b></p>
            </div>

            <?php echo $msg; ?>

            <div class="nav-tabs">
                <div class="nav-tab active" onclick="switchTab('new')">
                    <span class="material-symbols-rounded">add_circle</span> Lập phiếu mới
                </div>
                <div class="nav-tab" onclick="switchTab('history')">
                    <span class="material-symbols-rounded">history</span> Lịch sử lập phiếu
                </div>
            </div>

            <div class="tab-pane active" id="tab-new">
                <form method="POST" onsubmit="return confirm('Xác nhận lưu phiếu xuất này?');">
                <div class="form-card">
                    <div class="card-title"><span class="material-symbols-rounded" style="color: var(--accent-secondary);">route</span> 1. Thông tin điều hướng xuất kho</div>
                    <div class="input-grid">
                        <div class="form-group">
                            <label>Liên kết Hóa đơn (Sales):</label>
                            <select name="maHoaDon" class="custom-input">
                                <option value="">-- Xuất nội bộ / Lý do khác --</option>
                                <?php while($hd = $hoadons->fetch_assoc()): ?>
                                    <option value="<?= $hd['maHoaDon'] ?>" <?= (isset($_GET['maHoaDon']) && $_GET['maHoaDon'] == $hd['maHoaDon']) ? 'selected' : '' ?>>Đơn #<?= $hd['maHoaDon'] ?> - Ngày <?= date('d/m/Y', strtotime($hd['ngayLap'])) ?></option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <div class="form-group" style="display: none;">
                            <label>Kho xuất:</label>
                            <input type="text" class="custom-input" value="Tự động theo sản phẩm" disabled>
                        </div>
                        <div class="form-group">
                            <label>Lý do xuất:</label>
                            <input type="text" name="lyDoXuat" class="custom-input" placeholder="VD: Xuất bán hàng..." required>
                        </div>
                    </div>
                    <div class="input-grid" style="margin-top: 16px;">
                        <div class="form-group">
                            <label><span class="material-symbols-rounded">person</span> Họ tên người nhận hàng</label>
                            <input type="text" name="nguoiNhanHang" class="custom-input" placeholder="Tên người nhận hàng...">
                        </div>
                        <div class="form-group">
                            <label><span class="material-symbols-rounded">apartment</span> Đơn vị nhận (Của)</label>
                            <input type="text" name="donViNhan" class="custom-input" placeholder="Tên đơn vị/bộ phận nhận...">
                        </div>
                        <div class="form-group">
                            <label><span class="material-symbols-rounded">receipt</span> Theo chứng từ số</label>
                            <input type="text" name="soChungTu" class="custom-input" placeholder="Số chứng từ gốc...">
                        </div>
                        <div class="form-group">
                            <label><span class="material-symbols-rounded">calendar_today</span> Ngày chứng từ</label>
                            <input type="date" name="ngayChungTu" class="custom-input">
                        </div>
                    </div>
                </div>

                <div class="form-card">
                    <div class="card-title" style="margin-top: 30px;"><span class="material-symbols-rounded" style="color: var(--accent-secondary);">qr_code_scanner</span> 3. Danh sách Mã đàn xuất (Serial)</div>
                    <div id="serial-container">
                        <?php if(empty($prefill_serials)): ?>
                            <div class="serial-row" style="display: flex; gap: 10px; align-items: center;">
                                <input type="text" name="soSerial[]" class="custom-input" placeholder="Nhập/Quét mã Serial" style="flex: 2;" required>
                                <select name="maKhoList[]" class="custom-input" style="flex: 1;" required>
                                    <option value="">-- Chọn Kho --</option>
                                    <?= $kho_options_html ?>
                                </select>
                                <button type="button" class="btn-action btn-add" onclick="addSerialRow()">
                                    <span class="material-symbols-rounded">add</span>
                                </button>
                            </div>
                        <?php else: ?>
                            <?php foreach($prefill_serials as $index => $item): ?>
                            <div class="serial-row" style="display: flex; gap: 10px; align-items: center;">
                                <input type="text" name="soSerial[]" class="custom-input" placeholder="Nhập/Quét mã Serial" value="<?= htmlspecialchars($item['soSerial']) ?>" style="flex: 2;" required>
                                <select name="maKhoList[]" class="custom-input" style="flex: 1;" required>
                                    <option value="">-- Chọn Kho --</option>
                                    <?php foreach($khos_data as $k): ?>
                                        <option value="<?= $k['maKho'] ?>" <?= ($item['maKho'] == $k['maKho']) ? 'selected' : '' ?>><?= htmlspecialchars($k['tenKho']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <?php if($index == 0): ?>
                                <button type="button" class="btn-action btn-add" onclick="addSerialRow()">
                                    <span class="material-symbols-rounded">add</span>
                                </button>
                                <?php else: ?>
                                <button type="button" class="btn-action btn-remove" onclick="removeSerialRow(this)">
                                    <span class="material-symbols-rounded">delete</span>
                                </button>
                                <?php endif; ?>
                            </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                    <button type="button" class="btn-action btn-add" onclick="addSerialRow()" style="margin-top: 16px;">
                        <span class="material-symbols-rounded">add</span> Thêm mã Serial
                    </button>
                </div>

                <div style="text-align: right; padding-bottom: 50px;">
                    <button type="submit" name="btnLuuPhieuXuat" class="btn-action btn-submit">
                        <span class="material-symbols-rounded">done_all</span> XÁC NHẬN XUẤT KHO
                    </button>
                </div>
            </form>
            </div> <!-- End tab-new -->

            <!-- TAB LỊCH SỬ -->
            <div class="tab-pane" id="tab-history">
                <div class="form-card">
                    <div class="card-title">
                        <span class="material-symbols-rounded">history</span> 
                        Lịch sử lập phiếu xuất
                    </div>
                    <?php if ($history_result && $history_result->num_rows > 0): ?>
                    <table class="history-table">
                        <thead>
                            <tr>
                                <th>Mã Phiếu</th>
                                <th>Thời gian</th>
                                <th>Kho xuất</th>
                                <th>Lý do</th>
                                <th>Người nhận</th>
                                <th>Sản phẩm</th>
                                <th>Người lập</th>
                                <th>Trạng thái</th>
                                <th>Thao tác</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while($row = $history_result->fetch_assoc()): ?>
                            <tr>
                                <td><strong>#<?= $row['maPhieuXuat'] ?></strong></td>
                                <td><?= date('d/m/Y H:i', strtotime($row['ngayXuat'])) ?></td>
                                <td><?= htmlspecialchars($row['tenKho'] ?? 'Nhiều kho') ?></td>
                                <td><?= htmlspecialchars($row['lyDoXuat'] ?? 'N/A') ?></td>
                                <td><?= htmlspecialchars($row['nguoiNhanHang'] ?? 'N/A') ?></td>
                                <td><?= $row['soLuong'] ?> SP</td>
                                <td><?= htmlspecialchars($row['hoTen'] ?? 'N/A') ?></td>
                                <td><span class="status-badge <?= str_replace(' ', '.', $row['trangThai']) ?>"><?= $row['trangThai'] ?></span></td>
                                <td>
                                    <?php if ($row['trangThai'] == 'Chờ duyệt'): ?>
                                        <a href="sua_phieuxuat.php?id=<?= $row['maPhieuXuat'] ?>" class="btn-action" style="padding: 6px 12px; background: rgba(124, 92, 252, 0.1); color: var(--accent); font-size: 13px;">Sửa phiếu</a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                    <?php else: ?>
                        <div style="text-align: center; padding: 40px; color: var(--text-muted);">
                            <span class="material-symbols-rounded" style="font-size: 48px; opacity: 0.5;">inbox</span>
                            <p>Chưa có lịch sử lập phiếu xuất nào.</p>
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

function addSerialRow() {
    const container = document.getElementById('serial-container');
    const newRow = document.createElement('div');
    newRow.className = 'serial-row';
    newRow.style.display = 'flex';
    newRow.style.gap = '10px';
    newRow.style.alignItems = 'center';
    
    const khoOptions = `<?= $kho_options_html ?>`;
    
    newRow.innerHTML = `
        <input type="text" name="soSerial[]" class="custom-input" placeholder="Nhập hoặc quét mã Serial..." style="flex: 2;" required>
        <select name="maKhoList[]" class="custom-input" style="flex: 1;" required>
            <option value="">-- Chọn Kho --</option>
            ${khoOptions}
        </select>
        <button type="button" class="btn-action btn-remove" onclick="removeSerialRow(this)" title="Xóa"><span class="material-symbols-rounded">delete</span></button>
    `;
    container.appendChild(newRow);
    
    // Animation
    newRow.style.opacity = '0';
    newRow.style.transform = 'translateY(10px)';
    setTimeout(() => {
        newRow.style.transition = '0.3s ease';
        newRow.style.opacity = '1';
        newRow.style.transform = 'translateY(0)';
    }, 10);
}

function removeSerialRow(btn) {
    const row = btn.parentElement;
    row.style.opacity = '0';
    row.style.transform = 'scale(0.95)';
    setTimeout(() => row.remove(), 300);
}

// Auto-fill invoice details when selecting a sales invoice
document.addEventListener('DOMContentLoaded', function() {
    const hdSelect = document.querySelector('select[name="maHoaDon"]');
    if (hdSelect) {
        hdSelect.addEventListener('change', function() {
            let hdId = this.value;
            if (!hdId) {
                // Reset fields
                document.querySelector('input[name="lyDoXuat"]').value = '';
                document.querySelector('input[name="nguoiNhanHang"]').value = '';
                document.querySelector('input[name="donViNhan"]').value = '';
                document.querySelector('input[name="soChungTu"]').value = '';
                document.querySelector('input[name="ngayChungTu"]').value = '';
                document.getElementById('serial-container').innerHTML = `
                    <div class="serial-row">
                        <input type="text" name="soSerial[]" class="custom-input" placeholder="Nhập hoặc quét mã Serial..." required>
                        <button type="button" class="btn-action btn-add" onclick="addSerialRow()">
                            <span class="material-symbols-rounded">add</span>
                        </button>
                    </div>
                `;
                return;
            }

            // Call AJAX API
            fetch('api_get_hoadon.php?id=' + hdId)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        document.querySelector('input[name="lyDoXuat"]').value = 'Xuất hàng theo hóa đơn số #' + hdId;
                        document.querySelector('input[name="nguoiNhanHang"]').value = data.khachHang || '';
                        document.querySelector('input[name="donViNhan"]').value = data.donVi || '';
                        document.querySelector('input[name="soChungTu"]').value = 'HĐ' + hdId;
                        document.querySelector('input[name="ngayChungTu"]').value = data.ngayLap || '';
                        
                        const khoOptions = `<?= $kho_options_html ?>`;
                        
                        // Render serials
                        let container = document.getElementById('serial-container');
                        container.innerHTML = '';
                        if (data.serials && data.serials.length > 0) {
                            data.serials.forEach((serial, index) => {
                                let btn = index === 0 
                                    ? `<button type="button" class="btn-action btn-add" onclick="addSerialRow()"><span class="material-symbols-rounded">add</span></button>`
                                    : `<button type="button" class="btn-action btn-remove" onclick="removeSerialRow(this)"><span class="material-symbols-rounded">delete</span></button>`;
                                
                                let rowHTML = `
                                <div class="serial-row" style="display: flex; gap: 10px; align-items: center;">
                                    <input type="text" name="soSerial[]" class="custom-input" value="${serial}" placeholder="Nhập hoặc quét mã Serial..." style="flex: 2;" required>
                                    <select name="maKhoList[]" class="custom-input" style="flex: 1;" required>
                                        <option value="">-- Chọn Kho --</option>
                                        ${khoOptions}
                                    </select>
                                    ${btn}
                                </div>`;
                                container.insertAdjacentHTML('beforeend', rowHTML);
                            });
                        } else {
                            // fallback empty row
                            container.innerHTML = `
                            <div class="serial-row" style="display: flex; gap: 10px; align-items: center;">
                                <input type="text" name="soSerial[]" class="custom-input" placeholder="Nhập hoặc quét mã Serial..." style="flex: 2;" required>
                                <select name="maKhoList[]" class="custom-input" style="flex: 1;" required>
                                    <option value="">-- Chọn Kho --</option>
                                    ${khoOptions}
                                </select>
                                <button type="button" class="btn-action btn-add" onclick="addSerialRow()">
                                    <span class="material-symbols-rounded">add</span>
                                </button>
                            </div>`;
                        }
                    }
                })
                .catch(err => console.error('Error fetching invoice details:', err));
        });
    }
});
</script>

<?php include 'includes/footer.php'; ?>