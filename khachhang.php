<?php
// Kiểm tra session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'db.php';
require_once 'functions.php';

// Cấu hình phân quyền: Chỉ Admin và Sales (role 1, 2) mới được thêm/sửa khách hàng
$is_admin_or_sales = (isset($_SESSION['role_id']) && in_array($_SESSION['role_id'], [1, 2]));
$msg = "";

// XỬ LÝ AJAX LẤY LỊCH SỬ MUA HÀNG
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'get_history') {
    header('Content-Type: application/json; charset=utf-8');
    $maKhachHang = intval($_POST['maKhachHang'] ?? 0);
    
    $result = ['success' => false, 'tongChiTieu' => 0, 'soHoaDon' => 0, 'data' => []];
    
    if ($maKhachHang > 0) {
        $sql_hd = "SELECT hd.maHoaDon, hd.ngayLap, hd.tongTien, hd.hinhThucThanhToan, hd.trangThai
                   FROM hoadon hd
                   WHERE hd.maKhachHang = ?
                   ORDER BY hd.ngayLap DESC";
        $stmt_hd = $conn->prepare($sql_hd);
        $stmt_hd->bind_param("i", $maKhachHang);
        $stmt_hd->execute();
        $res_hd = $stmt_hd->get_result();
        
        $tongChiTieu = 0;
        $soHoaDon = $res_hd->num_rows;
        $hoadons = [];
        
        while ($hd = $res_hd->fetch_assoc()) {
            if ($hd['trangThai'] != 'Đã hủy') {
                $tongChiTieu += (float)$hd['tongTien'];
            }
            
            // Lấy chi tiết sản phẩm cho từng hóa đơn
            $sql_ct = "SELECT ds.soSerial, md.tenMau
                       FROM chitiethoadon ct
                       JOIN danserial ds ON ct.maSerial = ds.maSerial
                       JOIN maudan md ON ds.maMau = md.maMau
                       WHERE ct.maHoaDon = ?";
            $stmt_ct = $conn->prepare($sql_ct);
            $stmt_ct->bind_param("i", $hd['maHoaDon']);
            $stmt_ct->execute();
            $res_ct = $stmt_ct->get_result();
            
            $sanphams = [];
            while ($ct = $res_ct->fetch_assoc()) {
                $sanphams[] = $ct['tenMau'] . ' (SN: ' . $ct['soSerial'] . ')';
            }
            $hd['sanphams'] = $sanphams;
            $hd['ngayLapFmt'] = date('d/m/Y H:i', strtotime($hd['ngayLap']));
            $hd['tongTienFmt'] = number_format((float)$hd['tongTien'], 0, ',', '.') . ' ₫';
            
            $hoadons[] = $hd;
        }
        
        $result = [
            'success' => true,
            'tongChiTieuFmt' => number_format($tongChiTieu, 0, ',', '.') . ' ₫',
            'soHoaDon' => $soHoaDon,
            'data' => $hoadons
        ];
    }
    echo json_encode($result);
    exit();
}

// XỬ LÝ BACKEND (Thêm / Sửa / Xóa)
if ($is_admin_or_sales && $_SERVER['REQUEST_METHOD'] == 'POST') {
    
    // THÊM MỚI
    if (isset($_POST['btnAdd'])) {
        $hoTen = trim($_POST['hoTen']);
        $soDienThoai = trim($_POST['soDienThoai']);
        $emailKH = trim($_POST['emailKH']);
        $diaChi = trim($_POST['diaChi']);
        
        $stmt = $conn->prepare("INSERT INTO khachhang (hoTen, soDienThoai, emailKH, diaChi) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("ssss", $hoTen, $soDienThoai, $emailKH, $diaChi);
        
        if ($stmt->execute()) {
            $msg = "<div class='alert-msg alert-success'><span class='material-symbols-rounded'>check_circle</span> Thêm khách hàng thành công!</div>";
        } else {
            $msg = "<div class='alert-msg alert-error'><span class='material-symbols-rounded'>error</span> Lỗi: " . $conn->error . "</div>";
        }
    }
    
    // SỬA
    if (isset($_POST['btnEdit'])) {
        $maKhachHang = intval($_POST['maKhachHang']);
        $hoTen = trim($_POST['hoTen']);
        $soDienThoai = trim($_POST['soDienThoai']);
        $emailKH = trim($_POST['emailKH']);
        $diaChi = trim($_POST['diaChi']);
        
        $stmt = $conn->prepare("UPDATE khachhang SET hoTen = ?, soDienThoai = ?, emailKH = ?, diaChi = ? WHERE maKhachHang = ?");
        $stmt->bind_param("ssssi", $hoTen, $soDienThoai, $emailKH, $diaChi, $maKhachHang);
        
        if ($stmt->execute()) {
            $msg = "<div class='alert-msg alert-success'><span class='material-symbols-rounded'>check_circle</span> Cập nhật thông tin khách hàng thành công!</div>";
        } else {
            $msg = "<div class='alert-msg alert-error'><span class='material-symbols-rounded'>error</span> Lỗi: " . $conn->error . "</div>";
        }
    }

    // XÓA
    if (isset($_POST['btnDelete'])) {
        $maKhachHang = intval($_POST['maKhachHang']);
        
        // Kiểm tra xem khách hàng này đã có hóa đơn chưa
        $check_hd = $conn->query("SELECT maHoaDon FROM hoadon WHERE maKhachHang = $maKhachHang LIMIT 1");
        if ($check_hd->num_rows > 0) {
            $msg = "<div class='alert-msg alert-error'><span class='material-symbols-rounded'>error</span> Không thể xóa khách hàng đã từng giao dịch (đã có hóa đơn)!</div>";
        } else {
            $stmt = $conn->prepare("DELETE FROM khachhang WHERE maKhachHang = ?");
            $stmt->bind_param("i", $maKhachHang);
            if ($stmt->execute()) {
                $msg = "<div class='alert-msg alert-success'><span class='material-symbols-rounded'>check_circle</span> Đã xóa khách hàng!</div>";
            } else {
                $msg = "<div class='alert-msg alert-error'><span class='material-symbols-rounded'>error</span> Lỗi xóa khách hàng: " . $conn->error . "</div>";
            }
        }
    }
}

// Lấy danh sách khách hàng
$sql = "SELECT * FROM khachhang ORDER BY maKhachHang DESC";
$result = $conn->query($sql);

$title = 'Quản lý Khách hàng';
?>
<?php include 'includes/header.php'; ?>
<?php include 'includes/sidebar.php'; ?>

<style>
    .page-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; animation: fadeIn 0.4s ease; }
    .page-title { font-size: 24px; font-weight: 800; color: var(--text-primary); display: flex; align-items: center; gap: 12px; }
    .page-title .material-symbols-rounded { font-size: 32px; color: var(--info); background: var(--info-bg); padding: 8px; border-radius: 12px; }
    
    .btn-action { padding: 12px 20px; border-radius: var(--radius-md); font-weight: 600; cursor: pointer; border: none; transition: 0.3s; display: inline-flex; align-items: center; gap: 8px; font-family: inherit; font-size: 14px; text-decoration: none;}
    .btn-add-new { background: linear-gradient(135deg, #3b82f6, #60a5fa); color: white; box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3); }
    .btn-add-new:hover { transform: translateY(-2px); box-shadow: 0 6px 20px rgba(59, 130, 246, 0.4); }

    .table-card { background: var(--bg-card); backdrop-filter: blur(12px); border: 1px solid var(--glass-border); border-radius: var(--radius-xl); overflow: hidden; animation: fadeInUp 0.5s ease; box-shadow: var(--shadow-sm); }
    
    table { width: 100%; border-collapse: collapse; text-align: left; }
    th, td { padding: 16px 24px; border-bottom: 1px solid var(--border); }
    th { background: rgba(0,0,0,0.2); font-size: 13px; font-weight: 700; color: var(--text-secondary); text-transform: uppercase; letter-spacing: 0.5px; }
    td { font-size: 14px; color: var(--text-primary); vertical-align: middle; }
    tr:last-child td { border-bottom: none; }
    tr:hover td { background: rgba(255,255,255,0.02); }

    .btn-icon { background: transparent; border: none; color: var(--text-muted); cursor: pointer; padding: 6px; border-radius: 6px; transition: 0.2s; display: inline-flex; }
    .btn-icon:hover { background: rgba(255,255,255,0.1); color: var(--text-primary); }
    .btn-icon.edit:hover { color: var(--info); background: var(--info-bg); }
    .btn-icon.delete:hover { color: var(--danger); background: var(--danger-bg); }

    /* Modal Form */
    .modal-overlay {
        position: fixed; top: 0; left: 0; width: 100%; height: 100%;
        background: rgba(15, 13, 26, 0.85); backdrop-filter: blur(8px);
        display: none; justify-content: center; align-items: center;
        z-index: 9999; padding: 20px;
    }
    .modal-overlay.active { display: flex; animation: fadeIn 0.3s ease; }
    
    .modal-card {
        background: var(--bg-secondary); border: 1px solid var(--border-hover);
        border-radius: var(--radius-xl); width: 100%; max-width: 500px;
        box-shadow: 0 20px 40px rgba(0,0,0,0.5);
        transform: translateY(20px); opacity: 0;
        transition: 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }
    .modal-overlay.active .modal-card { transform: translateY(0); opacity: 1; }

    .modal-header { padding: 24px 32px; border-bottom: 1px solid var(--border); display: flex; justify-content: space-between; align-items: center; }
    .modal-title { font-size: 1.2rem; font-weight: 700; color: var(--info); margin: 0; display: flex; align-items: center; gap: 10px; }
    .modal-close { background: transparent; border: none; color: var(--text-muted); cursor: pointer; display: flex; align-items: center; justify-content: center; transition: 0.2s; padding: 4px; border-radius: 50%; }
    .modal-close:hover { background: rgba(255,255,255,0.1); color: var(--text-primary); }

    .modal-body { padding: 32px; }
    .form-group { margin-bottom: 20px; }
    .form-group label { display: block; font-weight: 600; color: var(--text-secondary); margin-bottom: 10px; font-size: 13px; text-transform: uppercase; letter-spacing: 0.5px; }
    .custom-input { width: 100%; padding: 14px 16px; background: rgba(0, 0, 0, 0.2); border: 1px solid var(--glass-border); border-radius: var(--radius-md); outline: none; transition: 0.3s; color: var(--text-primary); font-family: inherit; font-size: 14px; }
    .custom-input:focus { border-color: var(--info); background: rgba(96, 165, 250, 0.05); box-shadow: 0 0 0 3px rgba(96, 165, 250, 0.15); }

    .modal-footer { padding: 24px 32px; border-top: 1px solid var(--border); display: flex; justify-content: flex-end; gap: 12px; background: rgba(0,0,0,0.1); border-radius: 0 0 var(--radius-xl) var(--radius-xl); }
    
    .btn-cancel { background: transparent; color: var(--text-secondary); border: 1px solid var(--border); }
    .btn-cancel:hover { background: rgba(255,255,255,0.05); color: var(--text-primary); }
    .btn-save { background: var(--info); color: white; }
    .btn-save:hover { background: #3b82f6; }

    /* Alert */
    .alert-msg { padding: 16px 20px; border-radius: var(--radius-lg); margin-bottom: 24px; display: flex; align-items: center; gap: 12px; animation: fadeInUp 0.4s ease; }
    .alert-msg .material-symbols-rounded { font-size: 24px; }
    .alert-success { background: var(--success-bg); color: var(--success); border: 1px solid rgba(52,211,153,0.3); }
    .alert-error { background: var(--danger-bg); color: var(--danger); border: 1px solid rgba(248,113,113,0.3); }

    @keyframes spin { 100% { transform: rotate(360deg); } }
</style>

<div class="main-wrapper">
    <?php include 'includes/topbar.php'; ?>
    
    <div class="content">
        <div class="page-header">
            <h1 class="page-title"><span class="material-symbols-rounded">groups</span> Quản lý Khách hàng</h1>
            <?php if ($is_admin_or_sales): ?>
                <button class="btn-action btn-add-new" onclick="openModal('add')">
                    <span class="material-symbols-rounded">person_add</span> Thêm khách hàng
                </button>
            <?php endif; ?>
        </div>

        <?php echo $msg; ?>

        <div class="table-card">
            <table>
                <thead>
                    <tr>
                        <th style="width: 50px;">ID</th>
                        <th>Họ tên</th>
                        <th>Số điện thoại</th>
                        <th>Email</th>
                        <th>Địa chỉ</th>
                        <?php if ($is_admin_or_sales): ?>
                            <th style="text-align: right;">Thao tác</th>
                        <?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($result->num_rows > 0): ?>
                        <?php while ($row = $result->fetch_assoc()): ?>
                            <tr>
                                <td style="color: var(--text-muted);">#<?= $row['maKhachHang'] ?></td>
                                <td style="font-weight: 600; color: var(--info);"><?= htmlspecialchars($row['hoTen']) ?></td>
                                <td><?= htmlspecialchars($row['soDienThoai']) ?></td>
                                <td><?= htmlspecialchars($row['emailKH'] ?? '') ?></td>
                                <td><?= htmlspecialchars($row['diaChi'] ?? '') ?></td>
                                <?php if ($is_admin_or_sales): ?>
                                    <td style="text-align: right;">
                                        <button class="btn-icon" onclick="openHistoryModal(<?= $row['maKhachHang'] ?>, '<?= htmlspecialchars(addslashes($row['hoTen'])) ?>')" title="Lịch sử giao dịch" style="color: var(--success);">
                                            <span class="material-symbols-rounded">history</span>
                                        </button>
                                        <button class="btn-icon edit" onclick="openModal('edit', <?= htmlspecialchars(json_encode($row)) ?>)" title="Sửa">
                                            <span class="material-symbols-rounded">edit</span>
                                        </button>
                                        <form method="POST" style="display:inline;" onsubmit="return confirm('Bạn có chắc chắn muốn xóa khách hàng này không?');">
                                            <input type="hidden" name="maKhachHang" value="<?= $row['maKhachHang'] ?>">
                                            <button type="submit" name="btnDelete" class="btn-icon delete" title="Xóa">
                                                <span class="material-symbols-rounded">delete</span>
                                            </button>
                                        </form>
                                    </td>
                                <?php endif; ?>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" style="text-align: center; padding: 40px; color: var(--text-muted);">
                                <span class="material-symbols-rounded" style="font-size: 48px; opacity: 0.5; margin-bottom: 12px; display: block;">person_off</span>
                                Chưa có dữ liệu khách hàng.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal Form -->
<div class="modal-overlay" id="customerModal">
    <div class="modal-card">
        <div class="modal-header">
            <h3 class="modal-title" id="modalTitle"><span class="material-symbols-rounded">person_add</span> Thêm khách hàng mới</h3>
            <button class="modal-close" onclick="closeModal()"><span class="material-symbols-rounded">close</span></button>
        </div>
        <form method="POST" id="customerForm">
            <input type="hidden" name="maKhachHang" id="maKhachHang" value="">
            <div class="modal-body">
                <div class="form-group">
                    <label>Họ tên khách hàng *</label>
                    <input type="text" name="hoTen" id="hoTen" class="custom-input" required placeholder="Ví dụ: Nguyễn Văn A">
                </div>
                <div class="form-group">
                    <label>Số điện thoại *</label>
                    <input type="text" name="soDienThoai" id="soDienThoai" class="custom-input" required placeholder="Ví dụ: 0912345678">
                </div>
                <div class="form-group">
                    <label>Email</label>
                    <input type="email" name="emailKH" id="emailKH" class="custom-input" placeholder="Ví dụ: email@domain.com">
                </div>
                <div class="form-group">
                    <label>Địa chỉ</label>
                    <input type="text" name="diaChi" id="diaChi" class="custom-input" placeholder="Địa chỉ thường trú/nhận hàng...">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-action btn-cancel" onclick="closeModal()">Hủy</button>
                <button type="submit" name="btnAdd" id="btnSubmitForm" class="btn-action btn-save">Lưu Khách Hàng</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Lịch sử mua hàng -->
<div class="modal-overlay" id="historyModal">
    <div class="modal-card" style="max-width: 700px;">
        <div class="modal-header">
            <h3 class="modal-title"><span class="material-symbols-rounded">history</span> Lịch sử giao dịch: <span id="historyCustomerName" style="color: var(--text-primary); margin-left: 8px;"></span></h3>
            <button class="modal-close" onclick="closeHistoryModal()"><span class="material-symbols-rounded">close</span></button>
        </div>
        <div class="modal-body">
            <div style="display: flex; gap: 20px; margin-bottom: 24px;">
                <div style="flex: 1; background: rgba(96, 165, 250, 0.1); border: 1px solid rgba(96, 165, 250, 0.3); border-radius: var(--radius-md); padding: 16px; text-align: center;">
                    <div style="font-size: 13px; color: var(--text-secondary); text-transform: uppercase; font-weight: 600; margin-bottom: 4px;">Tổng Hóa Đơn</div>
                    <div id="historyCount" style="font-size: 24px; font-weight: 800; color: var(--info);">0</div>
                </div>
                <div style="flex: 1; background: rgba(52, 211, 153, 0.1); border: 1px solid rgba(52, 211, 153, 0.3); border-radius: var(--radius-md); padding: 16px; text-align: center;">
                    <div style="font-size: 13px; color: var(--text-secondary); text-transform: uppercase; font-weight: 600; margin-bottom: 4px;">Tổng Chi Tiêu</div>
                    <div id="historyTotal" style="font-size: 24px; font-weight: 800; color: var(--success);">0 ₫</div>
                </div>
            </div>
            
            <div id="historyContent" style="max-height: 400px; overflow-y: auto; padding-right: 8px;">
                <!-- Content will be loaded here -->
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn-action btn-cancel" onclick="closeHistoryModal()">Đóng</button>
        </div>
    </div>
</div>

<script>
    const modal = document.getElementById('customerModal');
    const form = document.getElementById('customerForm');
    const modalTitle = document.getElementById('modalTitle');
    const btnSubmit = document.getElementById('btnSubmitForm');

    function openModal(mode, data = null) {
        modal.classList.add('active');
        if (mode === 'add') {
            modalTitle.innerHTML = '<span class="material-symbols-rounded">person_add</span> Thêm khách hàng mới';
            btnSubmit.name = 'btnAdd';
            btnSubmit.textContent = 'Thêm Khách Hàng';
            form.reset();
            document.getElementById('maKhachHang').value = '';
        } else if (mode === 'edit' && data) {
            modalTitle.innerHTML = '<span class="material-symbols-rounded">edit</span> Cập nhật thông tin';
            btnSubmit.name = 'btnEdit';
            btnSubmit.textContent = 'Cập nhật';
            
            document.getElementById('maKhachHang').value = data.maKhachHang;
            document.getElementById('hoTen').value = data.hoTen;
            document.getElementById('soDienThoai').value = data.soDienThoai;
            document.getElementById('emailKH').value = data.emailKH || '';
            document.getElementById('diaChi').value = data.diaChi || '';
        }
    }

    function closeModal() {
        modal.classList.remove('active');
    }

    // Close modal when clicking outside
    modal.addEventListener('click', (e) => {
        if (e.target === modal) {
            closeModal();
        }
    });

    // === Xử lý AJAX Lịch sử giao dịch ===
    const historyModal = document.getElementById('historyModal');
    
    async function openHistoryModal(maKhachHang, hoTen) {
        document.getElementById('historyCustomerName').textContent = hoTen;
        historyModal.classList.add('active');
        
        const contentDiv = document.getElementById('historyContent');
        contentDiv.innerHTML = '<div style="text-align: center; padding: 40px; color: var(--text-muted);"><span class="material-symbols-rounded" style="font-size: 32px; animation: spin 1s linear infinite;">autorenew</span><div style="margin-top: 10px;">Đang tải dữ liệu...</div></div>';
        document.getElementById('historyCount').textContent = '0';
        document.getElementById('historyTotal').textContent = '0 ₫';
        
        try {
            const fd = new FormData();
            fd.append('action', 'get_history');
            fd.append('maKhachHang', maKhachHang);
            
            const resp = await fetch('', { method: 'POST', body: fd });
            const result = await resp.json();
            
            if (result.success) {
                document.getElementById('historyCount').textContent = result.soHoaDon;
                document.getElementById('historyTotal').textContent = result.tongChiTieuFmt;
                
                if (result.soHoaDon > 0) {
                    let html = '<div style="display: flex; flex-direction: column; gap: 16px;">';
                    result.data.forEach(hd => {
                        let statusColor = 'var(--info)';
                        if (hd.trangThai === 'Hoàn thành') statusColor = 'var(--success)';
                        if (hd.trangThai === 'Đã hủy') statusColor = 'var(--danger)';
                        
                        let spHtml = hd.sanphams.map(sp => `<div style="padding: 6px 12px; background: rgba(255,255,255,0.05); border-radius: 6px; font-size: 13px; margin-bottom: 4px; display: flex; align-items: center; gap: 6px;"><span class="material-symbols-rounded" style="font-size: 16px; color: var(--success);">piano</span> ${sp}</div>`).join('');
                        if(hd.sanphams.length === 0) spHtml = '<div style="color: var(--text-muted); font-style: italic; font-size: 13px;">Không có sản phẩm</div>';
                        
                        html += `
                        <div style="border: 1px solid var(--border); border-radius: var(--radius-lg); padding: 16px; background: rgba(0,0,0,0.2);">
                            <div style="display: flex; justify-content: space-between; margin-bottom: 12px; border-bottom: 1px dashed var(--border); padding-bottom: 12px;">
                                <div>
                                    <div style="font-weight: 700; color: var(--text-primary); font-size: 15px;">Hóa đơn #${hd.maHoaDon}</div>
                                    <div style="font-size: 13px; color: var(--text-muted); margin-top: 4px;">Ngày lập: ${hd.ngayLapFmt}</div>
                                </div>
                                <div style="text-align: right;">
                                    <div style="font-weight: 800; color: var(--success); font-size: 16px;">${hd.tongTienFmt}</div>
                                    <div style="font-size: 12px; font-weight: 600; color: ${statusColor}; margin-top: 4px;">${hd.trangThai}</div>
                                </div>
                            </div>
                            <div>
                                <div style="font-size: 12px; text-transform: uppercase; color: var(--text-secondary); margin-bottom: 8px; font-weight: 600;">Sản phẩm đã mua</div>
                                ${spHtml}
                            </div>
                        </div>`;
                    });
                    html += '</div>';
                    contentDiv.innerHTML = html;
                } else {
                    contentDiv.innerHTML = '<div style="text-align: center; padding: 40px; color: var(--text-muted);"><span class="material-symbols-rounded" style="font-size: 48px; opacity: 0.5;">receipt_long</span><div style="margin-top: 10px;">Khách hàng này chưa có giao dịch nào.</div></div>';
                }
            } else {
                contentDiv.innerHTML = '<div style="color: var(--danger); text-align: center; padding: 20px;">Lỗi tải dữ liệu</div>';
            }
        } catch(e) {
            contentDiv.innerHTML = '<div style="color: var(--danger); text-align: center; padding: 20px;">Lỗi kết nối máy chủ</div>';
        }
    }
    
    function closeHistoryModal() {
        historyModal.classList.remove('active');
    }
    
    historyModal.addEventListener('click', (e) => {
        if (e.target === historyModal) {
            closeHistoryModal();
        }
    });
</script>

<?php include 'includes/footer.php'; ?>
