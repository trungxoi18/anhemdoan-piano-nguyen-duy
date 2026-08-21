<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'db.php';
require_once 'functions.php';

if (!isset($_SESSION['role_id']) || $_SESSION['role_id'] != 1) {
    header("Location: index.php");
    exit();
}

$msg = "";

$roles = [];
$res_roles = $conn->query("SELECT * FROM vaitro");
while ($r = $res_roles->fetch_assoc()) {
    $roles[] = $r;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    
    if ($action == 'approve') {
        $maTaiKhoan = intval($_POST['maTaiKhoan']);
        $maNhanVien = intval($_POST['maNhanVien']);
        $maVaiTro = intval($_POST['maVaiTro']);
        
        $conn->begin_transaction();
        try {
            $stmt1 = $conn->prepare("UPDATE taikhoan SET TrangThai = 'Hoạt động', maVaiTro = ? WHERE maTaiKhoan = ?");
            $stmt1->bind_param("ii", $maVaiTro, $maTaiKhoan);
            $stmt1->execute();
            
            $stmt2 = $conn->prepare("UPDATE nhanvien SET TrangThai = 'Đang làm việc' WHERE maNhanVien = ?");
            $stmt2->bind_param("i", $maNhanVien);
            $stmt2->execute();
            
            $conn->commit();
            $msg = "<div class='alert-msg alert-success'><span class='material-symbols-rounded'>check_circle</span> Đã phê duyệt tài khoản!</div>";
        } catch (Exception $e) {
            $conn->rollback();
            $msg = "<div class='alert-msg alert-error'><span class='material-symbols-rounded'>error</span> Lỗi: " . $conn->error . "</div>";
        }
    } elseif ($action == 'reject') {
        $maTaiKhoan = intval($_POST['maTaiKhoan']);
        $maNhanVien = intval($_POST['maNhanVien']);
        
        $conn->begin_transaction();
        try {
            $stmt1 = $conn->prepare("UPDATE taikhoan SET TrangThai = 'Từ chối' WHERE maTaiKhoan = ?");
            $stmt1->bind_param("i", $maTaiKhoan);
            $stmt1->execute();
            
            $stmt2 = $conn->prepare("UPDATE nhanvien SET TrangThai = 'Từ chối' WHERE maNhanVien = ?");
            $stmt2->bind_param("i", $maNhanVien);
            $stmt2->execute();
            
            $conn->commit();
            $msg = "<div class='alert-msg alert-success'><span class='material-symbols-rounded'>check_circle</span> Đã từ chối tài khoản đăng ký!</div>";
        } catch (Exception $e) {
            $conn->rollback();
            $msg = "<div class='alert-msg alert-error'><span class='material-symbols-rounded'>error</span> Lỗi: " . $conn->error . "</div>";
        }
    } elseif ($action == 'update_role') {
        $maTaiKhoan = intval($_POST['maTaiKhoan']);
        $maVaiTro = intval($_POST['maVaiTro']);
        $trangThai = $_POST['trangThai'];
        
        $stmt = $conn->prepare("UPDATE taikhoan SET maVaiTro = ?, TrangThai = ? WHERE maTaiKhoan = ?");
        $stmt->bind_param("isi", $maVaiTro, $trangThai, $maTaiKhoan);
        
        if ($stmt->execute()) {
            // Also update nhanvien status based on taikhoan status
            $nvStatus = ($trangThai == 'Hoạt động') ? 'Đang làm việc' : 'Đã nghỉ/Khóa';
            $stmt_nv = $conn->prepare("UPDATE nhanvien nv JOIN taikhoan tk ON nv.maNhanVien = tk.maNhanVien SET nv.TrangThai = ? WHERE tk.maTaiKhoan = ?");
            $stmt_nv->bind_param("si", $nvStatus, $maTaiKhoan);
            $stmt_nv->execute();
            
            $msg = "<div class='alert-msg alert-success'><span class='material-symbols-rounded'>check_circle</span> Cập nhật tài khoản thành công!</div>";
        } else {
            $msg = "<div class='alert-msg alert-error'><span class='material-symbols-rounded'>error</span> Lỗi: " . $conn->error . "</div>";
        }
    } elseif ($action == 'add_account') {
        $hoTen = trim($_POST['hoTen']);
        $soDienThoai = trim($_POST['soDienThoai']);
        $email = trim($_POST['email']);
        $username = trim($_POST['username']);
        $password = $_POST['password'];
        $maVaiTro = intval($_POST['maVaiTro']);
        
        $stmt_check = $conn->prepare("SELECT COUNT(*) as count_user FROM taikhoan WHERE TenDangNhap = ?");
        $stmt_check->bind_param("s", $username);
        $stmt_check->execute();
        $result_check = $stmt_check->get_result()->fetch_assoc();
        
        if ($result_check['count_user'] > 0) {
            $msg = "<div class='alert-msg alert-error'><span class='material-symbols-rounded'>error</span> Tên đăng nhập đã tồn tại!</div>";
        } else {
            $conn->begin_transaction();
            try {
                $stmt_nv = $conn->prepare("INSERT INTO nhanvien (HoTen, SoDienThoai, EmailNV, TrangThai, createAt) VALUES (?, ?, ?, 'Đang làm việc', NOW())");
                $stmt_nv->bind_param("sss", $hoTen, $soDienThoai, $email);
                $stmt_nv->execute();
                $maNhanVien = $conn->insert_id;
                
                $stmt_tk = $conn->prepare("INSERT INTO taikhoan (TenDangNhap, MatKhau, MaNhanVien, MaVaiTro, TrangThai, NgayTao) VALUES (?, ?, ?, ?, 'Hoạt động', NOW())");
                $stmt_tk->bind_param("ssii", $username, $password, $maNhanVien, $maVaiTro);
                $stmt_tk->execute();
                
                $conn->commit();
                $msg = "<div class='alert-msg alert-success'><span class='material-symbols-rounded'>check_circle</span> Đã tạo tài khoản thành công!</div>";
            } catch (Exception $e) {
                $conn->rollback();
                $msg = "<div class='alert-msg alert-error'><span class='material-symbols-rounded'>error</span> Lỗi: " . $conn->error . "</div>";
            }
        }
    }
}

$sql_pending = "SELECT tk.MaTaiKhoan, tk.TenDangNhap, tk.NgayTao, nv.MaNhanVien, nv.HoTen, nv.SoDienThoai, nv.EmailNV
                FROM taikhoan tk
                JOIN nhanvien nv ON tk.MaNhanVien = nv.MaNhanVien
                WHERE tk.TrangThai = 'Chờ duyệt'
                ORDER BY tk.NgayTao DESC";
$res_pending = $conn->query($sql_pending);

$sql_all = "SELECT tk.MaTaiKhoan, tk.TenDangNhap, tk.TrangThai, tk.MaVaiTro, tk.NgayTao, 
                   nv.MaNhanVien, nv.HoTen, nv.SoDienThoai, vt.tenVaiTro
            FROM taikhoan tk
            JOIN nhanvien nv ON tk.MaNhanVien = nv.MaNhanVien
            LEFT JOIN vaitro vt ON tk.MaVaiTro = vt.maVaiTro
            WHERE tk.TrangThai != 'Chờ duyệt' AND tk.TrangThai != 'Từ chối'
            ORDER BY tk.NgayTao DESC";
$res_all = $conn->query($sql_all);

$title = 'Phân quyền tài khoản';
?>
<?php include 'includes/header.php'; ?>
<?php include 'includes/sidebar.php'; ?>



<div class="main-wrapper">
    <?php include 'includes/topbar.php'; ?>
    
    <div class="content">
        <div class="page-header">
            <h1 class="page-title"><span class="material-symbols-rounded">manage_accounts</span> Phân quyền tài khoản</h1>
            <button class="btn-action btn-add-new" onclick="openModal('add')">
                <span class="material-symbols-rounded">person_add</span> Thêm tài khoản mới
            </button>
        </div>

        <?php echo $msg; ?>

        <div class="nav-tabs">
            <button class="nav-tab active" onclick="switchTab('pending')">
                Tài khoản chờ duyệt 
                <?php if($res_pending->num_rows > 0): ?>
                    <span style="background: var(--danger); color: white; border-radius: 10px; padding: 2px 8px; font-size: 11px; margin-left: 8px;"><?= $res_pending->num_rows ?></span>
                <?php endif; ?>
            </button>
            <button class="nav-tab" onclick="switchTab('all')">Tất cả tài khoản</button>
        </div>

        <!-- Tab Chờ duyệt -->
        <div id="tab-pending" class="tab-pane active">
            <div class="table-card">
                <table>
                    <thead>
                        <tr>
                            <th>Nhân viên</th>
                            <th>Liên hệ</th>
                            <th>Tên đăng nhập</th>
                            <th>Ngày đăng ký</th>
                            <th style="text-align: right;">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($res_pending->num_rows > 0): ?>
                            <?php while ($row = $res_pending->fetch_assoc()): ?>
                                <tr>
                                    <td style="font-weight: 600; color: var(--accent);"><?= htmlspecialchars($row['HoTen']) ?></td>
                                    <td>
                                        <div style="font-size: 13px;"><span class="material-symbols-rounded" style="font-size: 14px; vertical-align: middle; color: var(--text-muted);">phone</span> <?= htmlspecialchars($row['SoDienThoai']) ?></div>
                                        <div style="font-size: 13px; margin-top: 4px;"><span class="material-symbols-rounded" style="font-size: 14px; vertical-align: middle; color: var(--text-muted);">mail</span> <?= htmlspecialchars($row['EmailNV']) ?></div>
                                    </td>
                                    <td><span style="background: rgba(255,255,255,0.05); padding: 4px 8px; border-radius: 6px; font-family: monospace;"><?= htmlspecialchars($row['TenDangNhap']) ?></span></td>
                                    <td><?= date('d/m/Y H:i', strtotime($row['NgayTao'])) ?></td>
                                    <td style="text-align: right;">
                                        <button class="btn-icon approve" onclick="openApproveModal(<?= $row['MaTaiKhoan'] ?>, <?= $row['MaNhanVien'] ?>, '<?= htmlspecialchars(addslashes($row['HoTen'])) ?>')" title="Phê duyệt">
                                            <span class="material-symbols-rounded">check_circle</span>
                                        </button>
                                        <form method="POST" style="display:inline;" onsubmit="return confirm('Từ chối yêu cầu đăng ký này?');">
                                            <input type="hidden" name="action" value="reject">
                                            <input type="hidden" name="maTaiKhoan" value="<?= $row['MaTaiKhoan'] ?>">
                                            <input type="hidden" name="maNhanVien" value="<?= $row['MaNhanVien'] ?>">
                                            <button type="submit" class="btn-icon reject" title="Từ chối">
                                                <span class="material-symbols-rounded">cancel</span>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" style="text-align: center; padding: 40px; color: var(--text-muted);">
                                    <span class="material-symbols-rounded" style="font-size: 48px; opacity: 0.5; margin-bottom: 12px; display: block;">check_circle</span>
                                    Không có tài khoản nào chờ duyệt.
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Tab Tất cả -->
        <div id="tab-all" class="tab-pane">
            <div class="table-card">
                <table>
                    <thead>
                        <tr>
                            <th>Tài khoản</th>
                            <th>Nhân viên</th>
                            <th>Vai trò</th>
                            <th>Trạng thái</th>
                            <th style="text-align: right;">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($res_all->num_rows > 0): ?>
                            <?php while ($row = $res_all->fetch_assoc()): ?>
                                <tr>
                                    <td><span style="background: rgba(255,255,255,0.05); padding: 4px 8px; border-radius: 6px; font-family: monospace;"><?= htmlspecialchars($row['TenDangNhap']) ?></span></td>
                                    <td style="font-weight: 600;"><?= htmlspecialchars($row['HoTen']) ?></td>
                                    <td style="color: var(--accent);"><?= htmlspecialchars($row['tenVaiTro'] ?? 'Chưa cấp') ?></td>
                                    <td>
                                        <?php if($row['TrangThai'] == 'Hoạt động'): ?>
                                            <span class="badge-pill badge-success">Hoạt động</span>
                                        <?php else: ?>
                                            <span class="badge-pill badge-danger"><?= htmlspecialchars($row['TrangThai']) ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td style="text-align: right;">
                                        <?php if($row['MaTaiKhoan'] != $_SESSION['user_id']): ?>
                                            <button class="btn-icon edit" onclick="openEditRoleModal(<?= htmlspecialchars(json_encode($row)) ?>)" title="Cập nhật phân quyền">
                                                <span class="material-symbols-rounded">edit_square</span>
                                            </button>
                                        <?php else: ?>
                                            <span style="font-size: 12px; color: var(--text-muted); font-style: italic;">Bạn</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Modal Thêm/Sửa Account -->
<div class="modal-overlay" id="accountModal">
    <div class="modal-card">
        <div class="modal-header">
            <h3 class="modal-title" id="modalTitle"><span class="material-symbols-rounded">person_add</span> Thêm tài khoản</h3>
            <button class="modal-close" onclick="closeModal()"><span class="material-symbols-rounded">close</span></button>
        </div>
        <form method="POST" id="accountForm">
            <input type="hidden" name="action" id="formAction" value="add_account">
            <input type="hidden" name="maTaiKhoan" id="editMaTaiKhoan" value="">
            
            <div class="modal-body" id="modalBody">
                <!-- Add mode fields -->
                <div id="addFields">
                    <div class="form-group">
                        <label>Họ và tên nhân viên *</label>
                        <input type="text" name="hoTen" id="addHoTen" class="custom-input" placeholder="Ví dụ: Nguyễn Văn A">
                    </div>
                    <div style="display: flex; gap: 16px;">
                        <div class="form-group" style="flex: 1;">
                            <label>Số điện thoại *</label>
                            <input type="text" name="soDienThoai" id="addPhone" class="custom-input" placeholder="SĐT">
                        </div>
                        <div class="form-group" style="flex: 1;">
                            <label>Email</label>
                            <input type="email" name="email" id="addEmail" class="custom-input" placeholder="Email">
                        </div>
                    </div>
                    <div style="display: flex; gap: 16px;">
                        <div class="form-group" style="flex: 1;">
                            <label>Tên đăng nhập *</label>
                            <input type="text" name="username" id="addUsername" class="custom-input" placeholder="Tên đăng nhập">
                        </div>
                        <div class="form-group" style="flex: 1;">
                            <label>Mật khẩu *</label>
                            <input type="password" name="password" id="addPassword" class="custom-input" placeholder="Mật khẩu">
                        </div>
                    </div>
                </div>

                <!-- Shared fields -->
                <div class="form-group">
                    <label>Phân quyền (Vai trò) *</label>
                    <select name="maVaiTro" id="maVaiTro" class="custom-input" required>
                        <option value="">-- Chọn vai trò --</option>
                        <?php foreach($roles as $r): ?>
                            <option value="<?= $r['maVaiTro'] ?>"><?= htmlspecialchars($r['tenVaiTro']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Edit mode only fields -->
                <div id="editFields" style="display: none;">
                    <div class="form-group">
                        <label>Trạng thái tài khoản</label>
                        <select name="trangThai" id="trangThai" class="custom-input">
                            <option value="Hoạt động">Hoạt động</option>
                            <option value="Đã khóa">Đã khóa / Tạm dừng</option>
                            <option value="Đã nghỉ">Đã nghỉ việc</option>
                        </select>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-action btn-cancel" onclick="closeModal()">Hủy</button>
                <button type="submit" id="btnSubmitForm" class="btn-action btn-save">Lưu tài khoản</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Approve -->
<div class="modal-overlay" id="approveModal">
    <div class="modal-card">
        <div class="modal-header">
            <h3 class="modal-title"><span class="material-symbols-rounded">verified_user</span> Phê duyệt đăng ký</h3>
            <button class="modal-close" onclick="closeApproveModal()"><span class="material-symbols-rounded">close</span></button>
        </div>
        <form method="POST">
            <input type="hidden" name="action" value="approve">
            <input type="hidden" name="maTaiKhoan" id="app_maTaiKhoan" value="">
            <input type="hidden" name="maNhanVien" id="app_maNhanVien" value="">
            
            <div class="modal-body">
                <div style="margin-bottom: 20px;">
                    Cấp quyền cho nhân viên: <strong id="app_hoTen" style="color: var(--accent);"></strong>
                </div>
                <div class="form-group">
                    <label>Phân quyền (Vai trò) *</label>
                    <select name="maVaiTro" class="custom-input" required>
                        <option value="">-- Chọn vai trò --</option>
                        <?php foreach($roles as $r): ?>
                            <option value="<?= $r['maVaiTro'] ?>"><?= htmlspecialchars($r['tenVaiTro']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-action btn-cancel" onclick="closeApproveModal()">Hủy</button>
                <button type="submit" class="btn-action btn-save">Phê duyệt</button>
            </div>
        </form>
    </div>
</div>

<script>
    function switchTab(tabId) {
        document.querySelectorAll('.nav-tab').forEach(t => t.classList.remove('active'));
        document.querySelectorAll('.tab-pane').forEach(c => c.classList.remove('active'));
        
        event.currentTarget.classList.add('active');
        document.getElementById('tab-' + tabId).classList.add('active');
    }

    const modal = document.getElementById('accountModal');
    const modalTitle = document.getElementById('modalTitle');
    const formAction = document.getElementById('formAction');
    const btnSubmit = document.getElementById('btnSubmitForm');

    function openModal(mode, data = null) {
        modal.classList.add('active');
        document.getElementById('accountForm').reset();
        
        const addFields = document.getElementById('addFields');
        const editFields = document.getElementById('editFields');
        
        if (mode === 'add') {
            modalTitle.innerHTML = '<span class="material-symbols-rounded">person_add</span> Thêm tài khoản mới';
            formAction.value = 'add_account';
            btnSubmit.textContent = 'Tạo tài khoản';
            
            addFields.style.display = 'block';
            editFields.style.display = 'none';
            
            // Require fields for add
            document.getElementById('addHoTen').required = true;
            document.getElementById('addPhone').required = true;
            document.getElementById('addUsername').required = true;
            document.getElementById('addPassword').required = true;
        }
    }

    function openEditRoleModal(data) {
        modal.classList.add('active');
        
        modalTitle.innerHTML = '<span class="material-symbols-rounded">manage_accounts</span> Chỉnh sửa phân quyền';
        formAction.value = 'update_role';
        btnSubmit.textContent = 'Lưu thay đổi';
        
        document.getElementById('editMaTaiKhoan').value = data.MaTaiKhoan;
        document.getElementById('maVaiTro').value = data.MaVaiTro || '';
        document.getElementById('trangThai').value = data.TrangThai;
        
        const addFields = document.getElementById('addFields');
        const editFields = document.getElementById('editFields');
        
        addFields.style.display = 'none';
        editFields.style.display = 'block';
        
        // Remove required from add fields
        document.getElementById('addHoTen').required = false;
        document.getElementById('addPhone').required = false;
        document.getElementById('addUsername').required = false;
        document.getElementById('addPassword').required = false;
    }

    function closeModal() {
        modal.classList.remove('active');
    }

    modal.addEventListener('click', (e) => {
        if (e.target === modal) closeModal();
    });

    const approveModal = document.getElementById('approveModal');
    function openApproveModal(maTaiKhoan, maNhanVien, hoTen) {
        document.getElementById('app_maTaiKhoan').value = maTaiKhoan;
        document.getElementById('app_maNhanVien').value = maNhanVien;
        document.getElementById('app_hoTen').textContent = hoTen;
        approveModal.classList.add('active');
    }
    
    function closeApproveModal() {
        approveModal.classList.remove('active');
    }

    approveModal.addEventListener('click', (e) => {
        if (e.target === approveModal) closeApproveModal();
    });
</script>

<?php include 'includes/footer.php'; ?>
