<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'db.php';
require_once 'functions.php';

// Kiểm tra quyền
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$role_id = $_SESSION['role_id']; // 1: Admin, 3: Thủ kho, 2: Sales
$is_admin = ($role_id == 1);
$msg = "";

// Xử lý các thao tác POST (Áp dụng chuẩn Post/Redirect/Get để tránh lỗi gửi lại biểu mẫu khi F5)
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $maPhieuBT = intval($_POST['maPhieuBT'] ?? 0);
    $action = $_POST['action'] ?? '';
    
    if ($maPhieuBT > 0) {
        // Lấy thông tin phiếu và serial
        $st = $conn->prepare("SELECT pb.*, ds.maKho as maKhoHienTai FROM phieubaotri pb JOIN danserial ds ON pb.maSerial = ds.maSerial WHERE pb.maPhieuBT = ?");
        $st->bind_param("i", $maPhieuBT);
        $st->execute();
        $res = $st->get_result();
        
        if ($res->num_rows > 0) {
            $phieu = $res->fetch_assoc();
            $trangThaiHienTai = $phieu['trangThai'];
            $maSerial = $phieu['maSerial'];
            
            $conn->begin_transaction();
            try {
                // ==================== ADMIN DUYỆT ====================
                if ($is_admin && $action == 'approve') {
                    if ($trangThaiHienTai == 'Chờ duyệt tiếp nhận') {
                        $conn->query("UPDATE phieubaotri SET trangThai = 'Đã tiếp nhận', ngayCapNhat = NOW() WHERE maPhieuBT = $maPhieuBT");
                        writeLog($conn, 'DUYỆT BẢO TRÍ', "Admin đã duyệt Phiếu tiếp nhận bảo trì #$maPhieuBT");
                        $_SESSION['flash_success'] = "Đã duyệt Phiếu Tiếp Nhận #$maPhieuBT thành công!";
                    } 
                    elseif ($trangThaiHienTai == 'Chờ duyệt nhập kho') {
                        $conn->query("UPDATE phieubaotri SET trangThai = 'Đã nhập kho', ngayCapNhat = NOW() WHERE maPhieuBT = $maPhieuBT");
                        // Cập nhật trạng thái đàn -> Đang bảo hành & cập nhật vị trí Kho tiếp nhận
                        $khoUpdateSql = !empty($phieu['maKho']) ? ", maKho = " . intval($phieu['maKho']) : "";
                        $conn->query("UPDATE danserial SET trangThai = 'Đang bảo hành' $khoUpdateSql WHERE maSerial = $maSerial");
                        writeLog($conn, 'DUYỆT BẢO TRÍ', "Admin đã duyệt Phiếu nhập kho bảo trì #$maPhieuBT");
                        $_SESSION['flash_success'] = "Đã duyệt Phiếu Nhập Kho #$maPhieuBT thành công! Vị trí kho và trạng thái đàn đã được cập nhật.";
                    }
                    elseif ($trangThaiHienTai == 'Chờ duyệt xuất hãng') {
                        $conn->query("UPDATE phieubaotri SET trangThai = 'Đã xuất hãng', ngayCapNhat = NOW() WHERE maPhieuBT = $maPhieuBT");
                        // Cập nhật trạng thái đàn -> Đã xuất hãng
                        $conn->query("UPDATE danserial SET trangThai = 'Đã xuất hãng' WHERE maSerial = $maSerial");
                        writeLog($conn, 'DUYỆT BẢO TRÍ', "Admin đã duyệt Phiếu xuất hãng bảo trì #$maPhieuBT");
                        $_SESSION['flash_success'] = "Đã duyệt Phiếu Xuất Hãng #$maPhieuBT thành công! Trạng thái đàn đã cập nhật.";
                    }
                }
                // ==================== ADMIN TỪ CHỐI ====================
                elseif ($is_admin && $action == 'reject') {
                    if ($trangThaiHienTai == 'Chờ duyệt tiếp nhận') {
                        $conn->query("UPDATE phieubaotri SET trangThai = 'Đã hủy', ngayCapNhat = NOW() WHERE maPhieuBT = $maPhieuBT");
                        writeLog($conn, 'TỪ CHỐI BẢO TRÍ', "Admin đã từ chối Phiếu tiếp nhận bảo trì #$maPhieuBT");
                        $_SESSION['flash_error'] = "Đã từ chối Phiếu #$maPhieuBT.";
                    }
                    elseif ($trangThaiHienTai == 'Chờ duyệt nhập kho') {
                        $conn->query("UPDATE phieubaotri SET trangThai = 'Đã tiếp nhận', ngayCapNhat = NOW() WHERE maPhieuBT = $maPhieuBT");
                        writeLog($conn, 'TỪ CHỐI BẢO TRÍ', "Admin đã từ chối Yêu cầu nhập kho bảo trì #$maPhieuBT");
                        $_SESSION['flash_error'] = "Đã từ chối Yêu cầu nhập kho #$maPhieuBT.";
                    }
                    elseif ($trangThaiHienTai == 'Chờ duyệt xuất hãng') {
                        $conn->query("UPDATE phieubaotri SET trangThai = 'Đã nhập kho', ngayCapNhat = NOW() WHERE maPhieuBT = $maPhieuBT");
                        writeLog($conn, 'TỪ CHỐI BẢO TRÍ', "Admin đã từ chối Yêu cầu xuất hãng #$maPhieuBT");
                        $_SESSION['flash_error'] = "Đã từ chối Yêu cầu xuất hãng #$maPhieuBT.";
                    }
                }
                // ==================== CHUYỂN TRẠNG THÁI TIẾP THEO ====================
                elseif ($action == 'request_nhap') {
                    $maKho = intval($_POST['maKho'] ?? 0);
                    if ($maKho > 0) {
                        $conn->query("UPDATE phieubaotri SET trangThai = 'Chờ duyệt nhập kho', maKho = $maKho, ngayCapNhat = NOW() WHERE maPhieuBT = $maPhieuBT");
                        $infoKho = $conn->query("SELECT tenKho FROM kho WHERE maKho = $maKho")->fetch_assoc();
                        $tenKhoStr = $infoKho ? $infoKho['tenKho'] : "Kho #$maKho";
                        writeLog($conn, 'YÊU CẦU BẢO TRÍ', "Yêu cầu nhập $tenKhoStr bảo trì phiếu #$maPhieuBT");
                        $_SESSION['flash_success'] = "Đã gửi yêu cầu nhập vào $tenKhoStr để bảo trì, chờ Admin duyệt.";
                    } else {
                        throw new Exception("Vui lòng chọn Kho để tiếp nhận bảo trì!");
                    }
                }
                elseif ($action == 'request_xuat') {
                    // Lấy hãng đàn từ form
                    $maHang = intval($_POST['maHang'] ?? 0);
                    if ($maHang > 0) {
                        $conn->query("UPDATE phieubaotri SET trangThai = 'Chờ duyệt xuất hãng', maHang = $maHang, ngayCapNhat = NOW() WHERE maPhieuBT = $maPhieuBT");
                        writeLog($conn, 'YÊU CẦU BẢO TRÍ', "Yêu cầu xuất hãng bảo trì phiếu #$maPhieuBT");
                        $_SESSION['flash_success'] = "Đã gửi yêu cầu xuất hãng, chờ Admin duyệt.";
                    } else {
                        throw new Exception("Vui lòng chọn hãng đàn để xuất!");
                    }
                }
                elseif ($action == 'complete') {
                    $conn->query("UPDATE phieubaotri SET trangThai = 'Hoàn thành', ngayCapNhat = NOW() WHERE maPhieuBT = $maPhieuBT");
                    // Khi hoàn thành, trả đàn cho khách hàng -> cập nhật trạng thái 'Đã bán'
                    $conn->query("UPDATE danserial SET trangThai = 'Đã bán' WHERE maSerial = $maSerial");
                    writeLog($conn, 'HOÀN THÀNH BẢO TRÍ', "Đã hoàn thành phiếu bảo trì #$maPhieuBT");
                    $_SESSION['flash_success'] = "Đã đánh dấu hoàn thành Phiếu Bảo Trì #$maPhieuBT. Trạng thái đàn đã được cập nhật thành 'Đã bán'.";
                }
                
                $conn->commit();
            } catch (Exception $e) {
                $conn->rollback();
                $_SESSION['flash_error'] = "Lỗi: " . $e->getMessage();
            }
        }
    }
    header("Location: quanly_baotri.php");
    exit();
}

$msg = "";
if (isset($_SESSION['flash_success'])) {
    $msg = "<div class='alert alert-success' style='background: rgba(52, 211, 153, 0.1); color: #34d399; border: 1px solid rgba(52, 211, 153, 0.3); padding:15px; border-radius:12px; margin-bottom:24px; display: flex; align-items: center; gap: 10px;'><span class='material-symbols-rounded'>check_circle</span> " . htmlspecialchars($_SESSION['flash_success']) . "</div>";
    unset($_SESSION['flash_success']);
}
if (isset($_SESSION['flash_error'])) {
    $msg = "<div class='alert alert-danger' style='background: rgba(248, 113, 113, 0.1); color: #f87171; border: 1px solid rgba(248, 113, 113, 0.3); padding:15px; border-radius:12px; margin-bottom:24px; display: flex; align-items: center; gap: 10px;'><span class='material-symbols-rounded'>error</span> " . htmlspecialchars($_SESSION['flash_error']) . "</div>";
    unset($_SESSION['flash_error']);
}

// Lấy danh sách phiếu bảo trì
$sql = "SELECT pb.*, kh.hoTen as tenKH, ds.soSerial, md.tenMau, nv.hoTen as tenNVLap, hd.tenHang, k.tenKho
        FROM phieubaotri pb
        JOIN khachhang kh ON pb.maKhachHang = kh.maKhachHang
        JOIN danserial ds ON pb.maSerial = ds.maSerial
        JOIN maudan md ON ds.maMau = md.maMau
        JOIN nhanvien nv ON pb.maNhanVienLap = nv.maNhanVien
        LEFT JOIN hangdan hd ON pb.maHang = hd.maHang
        LEFT JOIN kho k ON pb.maKho = k.maKho
        ORDER BY pb.ngayTiepNhan DESC";
$phieus = $conn->query($sql);

$hangdans = $conn->query("SELECT * FROM hangdan ORDER BY tenHang ASC");
$khos = $conn->query("SELECT * FROM kho ORDER BY maKho ASC");
?>

<?php include 'includes/header.php'; ?>
<?php include 'includes/sidebar.php'; ?>


<div class="main-wrapper">
    <?php include 'includes/topbar.php'; ?>

    <div class="content">
        <div class="page-content-wrapper">
            <div class="page-header">
                <div class="page-title">
                    <span class="material-symbols-rounded">build</span> Quản lý Phiếu Bảo Trì
                </div>
                <a href="taophieu_baotri.php" class="btn btn-primary"><span class="material-symbols-rounded">add</span> Lập phiếu bảo trì</a>
            </div>
            
            <?php echo $msg; ?>
            
            <div class="table-container">
                <table class="standard-table">
                    <thead>
                        <tr>
                            <th>Mã Phiếu</th>
                            <th>Ngày Tiếp Nhận</th>
                            <th>Khách Hàng</th>
                            <th>Mã Đàn (Serial)</th>
                            <th>Trạng Thái</th>
                            <th>Thao Tác</th>
                            <th>In Phiếu</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($phieus->num_rows > 0): ?>
                            <?php while($p = $phieus->fetch_assoc()): 
                                $statusClass = 'badge-info';
                                if(strpos($p['trangThai'], 'Chờ duyệt') !== false) $statusClass = 'badge-warning';
                                elseif($p['trangThai'] == 'Đã xuất hãng') $statusClass = 'badge-primary';
                                elseif($p['trangThai'] == 'Hoàn thành') $statusClass = 'badge-success';
                                elseif($p['trangThai'] == 'Đã hủy') $statusClass = 'badge-danger';
                            ?>
                            <tr>
                                <td>#<?= $p['maPhieuBT'] ?></td>
                                <td><?= date('d/m/Y H:i', strtotime($p['ngayTiepNhan'])) ?></td>
                                <td>
                                    <strong><?= htmlspecialchars($p['tenKH']) ?></strong><br>
                                    <small style="color: var(--text-muted);">HĐ: #<?= $p['maHoaDon'] ?></small>
                                </td>
                                <td>
                                    <?= htmlspecialchars($p['tenMau']) ?><br>
                                    <small style="color: var(--text-secondary);"><?= htmlspecialchars($p['soSerial']) ?></small>
                                </td>
                                <td>
                                    <span class="badge-pill <?= $statusClass ?>"><?= $p['trangThai'] ?></span>
                                    <?php if(!empty($p['tenKho']) && in_array($p['trangThai'], ['Chờ duyệt nhập kho', 'Đã nhập kho', 'Chờ duyệt xuất hãng'])): ?>
                                        <div style="font-size: 11px; color: var(--text-secondary); margin-top: 4px; display: flex; align-items: center; gap: 3px;">
                                            <span class="material-symbols-rounded" style="font-size: 13px;">warehouse</span> <?= htmlspecialchars($p['tenKho']) ?>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div style="display:flex; gap: 8px; flex-wrap: wrap;">
                                        <!-- Actions cho Admin -->
                                        <?php if($is_admin && strpos($p['trangThai'], 'Chờ duyệt') !== false): ?>
                                            <form method="POST" style="margin:0;">
                                                <input type="hidden" name="maPhieuBT" value="<?= $p['maPhieuBT'] ?>">
                                                <button type="submit" name="action" value="approve" class="btn-action btn-approve"><span class="material-symbols-rounded" style="font-size:16px;">check</span> Duyệt</button>
                                                <button type="submit" name="action" value="reject" class="btn-action btn-reject" onclick="return confirm('Xác nhận từ chối?');"><span class="material-symbols-rounded" style="font-size:16px;">close</span> Từ chối</button>
                                            </form>
                                        <?php endif; ?>
                                        
                                        <!-- Actions chuyển bước tiếp theo -->
                                        <?php if($p['trangThai'] == 'Đã tiếp nhận'): ?>
                                            <button type="button" class="btn-action btn-next" onclick="openNhapKhoModal(<?= $p['maPhieuBT'] ?>, '<?= htmlspecialchars(addslashes($p['tenMau'])) ?>', '<?= htmlspecialchars(addslashes($p['soSerial'])) ?>')"><span class="material-symbols-rounded" style="font-size:16px;">warehouse</span> YC Nhập Kho</button>
                                        <?php elseif($p['trangThai'] == 'Đã nhập kho'): ?>
                                            <button type="button" class="btn-action btn-next" onclick="openXuatModal(<?= $p['maPhieuBT'] ?>)"><span class="material-symbols-rounded" style="font-size:16px;">local_shipping</span> YC Xuất Hãng</button>
                                        <?php elseif($p['trangThai'] == 'Đã xuất hãng'): ?>
                                            <form method="POST" style="margin:0;">
                                                <input type="hidden" name="maPhieuBT" value="<?= $p['maPhieuBT'] ?>">
                                                <button type="submit" name="action" value="complete" class="btn-action btn-approve" onclick="return confirm('Xác nhận đã bảo trì xong và trả khách?');"><span class="material-symbols-rounded" style="font-size:16px;">task_alt</span> Hoàn Thành</button>
                                            </form>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td>
                                    <div style="display:flex; gap: 4px; flex-direction:column;">
                                        <?php if($p['trangThai'] != 'Chờ duyệt tiếp nhận' && $p['trangThai'] != 'Đã hủy'): ?>
                                            <a href="xuat_pdf.php?type=baotri_tiepnhan&id=<?= $p['maPhieuBT'] ?>" class="btn-action btn-print"><span class="material-symbols-rounded" style="font-size:14px;">print</span> Phiếu TN</a>
                                        <?php endif; ?>
                                        
                                        <?php if(in_array($p['trangThai'], ['Đã nhập kho', 'Chờ duyệt xuất hãng', 'Đã xuất hãng', 'Hoàn thành'])): ?>
                                            <a href="xuat_pdf.php?type=baotri_nhap&id=<?= $p['maPhieuBT'] ?>" class="btn-action btn-print"><span class="material-symbols-rounded" style="font-size:14px;">print</span> Phiếu NK</a>
                                        <?php endif; ?>
                                        
                                        <?php if(in_array($p['trangThai'], ['Đã xuất hãng', 'Hoàn thành'])): ?>
                                            <a href="xuat_pdf.php?type=baotri_xuat&id=<?= $p['maPhieuBT'] ?>" class="btn-action btn-print"><span class="material-symbols-rounded" style="font-size:14px;">print</span> Phiếu XH</a>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr><td colspan="7" style="text-align:center;">Chưa có phiếu bảo trì nào.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Modal Nhập Kho Bảo Trì -->
<div class="modal-overlay" id="nhapKhoModal">
    <div class="modal-card" style="width: 450px;">
        <div class="modal-header">
            <h3 class="modal-title"><span class="material-symbols-rounded">warehouse</span> Yêu cầu nhập kho bảo trì</h3>
            <button class="modal-close" onclick="document.getElementById('nhapKhoModal').classList.remove('active')"><span class="material-symbols-rounded">close</span></button>
        </div>
        <div class="modal-body">
            <form method="POST" id="nhapKhoForm">
                <input type="hidden" name="maPhieuBT" id="nhapKho_maPhieuBT">
                <input type="hidden" name="action" value="request_nhap">
                
                <div style="background: rgba(255,255,255,0.03); border: 1px solid var(--glass-border); padding: 12px 16px; border-radius: var(--radius-md); margin-bottom: 16px;">
                    <div style="font-size: 13px; color: var(--text-secondary);">Sản phẩm tiếp nhận:</div>
                    <div style="font-weight: 600; font-size: 15px; color: var(--text-primary); margin-top: 4px;" id="nhapKho_danInfo">--</div>
                </div>

                <div class="form-group">
                    <label style="font-weight: 600; font-size: 0.85rem; margin-bottom: 6px; display: block;">Chọn Kho Tiếp Nhận Lưu Trữ: <span style="color:red;">*</span></label>
                    <select name="maKho" class="custom-input" required style="width: 100%; height: 44px;">
                        <option value="">-- Chọn Kho --</option>
                        <?php 
                        $khos->data_seek(0);
                        while($k = $khos->fetch_assoc()): 
                        ?>
                            <option value="<?= $k['maKho'] ?>"><?= htmlspecialchars($k['tenKho']) ?> (<?= htmlspecialchars($k['diaChi'] ?? 'Chưa có ĐC') ?>)</option>
                        <?php endwhile; ?>
                    </select>
                </div>
            </form>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" onclick="document.getElementById('nhapKhoModal').classList.remove('active')">Hủy</button>
            <button type="submit" form="nhapKhoForm" class="btn btn-primary"><span class="material-symbols-rounded" style="font-size: 16px;">check</span> Gửi Yêu Cầu Nhập Kho</button>
        </div>
    </div>
</div>

<!-- Modal Xuất Hãng -->
<div class="modal-overlay" id="xuatModal">
    <div class="modal-card" style="width: 400px;">
        <div class="modal-header">
            <h3 class="modal-title"><span class="material-symbols-rounded">local_shipping</span> Yêu cầu xuất gửi Hãng</h3>
            <button class="modal-close" onclick="document.getElementById('xuatModal').classList.remove('active')"><span class="material-symbols-rounded">close</span></button>
        </div>
        <div class="modal-body">
            <form method="POST" id="xuatForm">
                <input type="hidden" name="maPhieuBT" id="xuat_maPhieuBT">
                <input type="hidden" name="action" value="request_xuat">
                
                <div class="form-group">
                    <label>Chọn Hãng Đàn:</label>
                    <select name="maHang" class="custom-input" required>
                        <option value="">-- Chọn Hãng --</option>
                        <?php 
                        $hangdans->data_seek(0);
                        while($h = $hangdans->fetch_assoc()): 
                        ?>
                            <option value="<?= $h['maHang'] ?>"><?= htmlspecialchars($h['tenHang']) ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
            </form>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" onclick="document.getElementById('xuatModal').classList.remove('active')">Hủy</button>
            <button type="submit" form="xuatForm" class="btn btn-primary">Gửi Yêu Cầu</button>
        </div>
    </div>
</div>

<script>
function openNhapKhoModal(id, tenMau, serial) {
    document.getElementById('nhapKho_maPhieuBT').value = id;
    document.getElementById('nhapKho_danInfo').textContent = tenMau + ' (Serial: ' + serial + ')';
    document.getElementById('nhapKhoModal').classList.add('active');
}

function openXuatModal(id) {
    document.getElementById('xuat_maPhieuBT').value = id;
    document.getElementById('xuatModal').classList.add('active');
}
</script>

<?php include 'includes/footer.php'; ?>
