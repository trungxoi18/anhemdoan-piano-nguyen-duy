<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'db.php';
require_once 'functions.php';

// Kiểm tra quyền (Admin và Thủ kho)
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role_id'], [1, 3])) {
    header("Location: index.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$role_id = $_SESSION['role_id'];
$msg = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['btnLuuPhieuDC'])) {
    $maKhoXuat = $_POST['maKhoXuat'];
    $maKhoNhap = $_POST['maKhoNhap'];
    $ghiChu = $_POST['ghiChu'];
    $list_serial = $_POST['soSerial'];

    if ($maKhoXuat == $maKhoNhap) {
        $msg = "<div class='alert alert-danger'>Lỗi: Kho xuất và Kho nhập không được trùng nhau!</div>";
    } else {
        $conn->begin_transaction();
        try {
            $sql_phieu = "INSERT INTO phieudieuchuyen (maKhoXuat, maKhoNhap, maNhanVienLap, ghiChu, trangThai) VALUES (?, ?, ?, ?, 'Chờ duyệt')";
            $stmt = $conn->prepare($sql_phieu);
            $stmt->bind_param("iiis", $maKhoXuat, $maKhoNhap, $user_id, $ghiChu);
            $stmt->execute();
            $maPhieuDC = $conn->insert_id;

            $soLuong = 0;
            foreach ($list_serial as $serial) {
                $serial = trim($serial);
                if(empty($serial)) continue;
                
                $st_check = $conn->prepare("SELECT maSerial, trangThai FROM danserial WHERE soSerial = ? AND maKho = ?");
                $st_check->bind_param("si", $serial, $maKhoXuat);
                $st_check->execute();
                $res = $st_check->get_result();
                
                if ($res->num_rows == 0) throw new Exception("Mã Serial [$serial] không có trong Kho Xuất!");
                
                $row = $res->fetch_assoc();
                $maSerial = $row['maSerial'];
                if ($row['trangThai'] != 'Trong kho') {
                    throw new Exception("Mã Serial [$serial] đang ở trạng thái '".$row['trangThai']."', không thể điều chuyển!");
                }
                
                $conn->query("INSERT INTO chitietdieuchuyen (maPhieuDC, maSerial) VALUES ($maPhieuDC, $maSerial)");
                
                // Cập nhật trạng thái đàn sang Đang điều chuyển
                $conn->query("UPDATE danserial SET trangThai = 'Đang điều chuyển' WHERE maSerial = $maSerial");
                $soLuong++;
            }

            if ($soLuong == 0) {
                throw new Exception("Phiếu điều chuyển phải có ít nhất 1 sản phẩm!");
            }

            $conn->commit();
            
            writeLog($conn, 'ĐIỀU CHUYỂN', "Đã lập phiếu điều chuyển #$maPhieuDC - Chuyển $soLuong sản phẩm");

            $msg_dc = "Lập phiếu điều chuyển thành công (Chờ duyệt)! Phiếu #$maPhieuDC";
            $conn->query("INSERT INTO ThongBao (maTaiKhoan, noiDung, link) VALUES ($user_id, '$msg_dc', 'dieuchuyen.php')");

            $msg_admin = "Có phiếu điều chuyển mới #$maPhieuDC cần phê duyệt ($soLuong SP)";
            $conn->query("INSERT INTO ThongBao (maVaiTro, noiDung, link) VALUES (1, '$msg_admin', 'duyet_phieu.php')");

            $_SESSION['flash_success'] = "Lập phiếu điều chuyển thành công! Phiếu #$maPhieuDC đang chờ phê duyệt.";
            header("Location: dieuchuyen.php");
            exit();
        } catch (Exception $e) {
            $conn->rollback();
            $msg = "<div class='alert alert-danger' style='background: var(--danger-bg); color: var(--danger); border: 1px solid rgba(248,113,113,0.3); padding:15px; border-radius:12px; margin-bottom:24px;'><span class='material-symbols-rounded'>error</span> Lỗi: " . $e->getMessage() . "</div>";
        }
    }
}

$khos = $conn->query("SELECT * FROM kho");
$khos_arr = $khos->fetch_all(MYSQLI_ASSOC);
?>

<?php include 'includes/header.php'; ?>
<?php include 'includes/sidebar.php'; ?>

<style>
    .form-center-container { max-width: 900px; margin: 0 auto; animation: fadeInUp 0.5s ease; }
    
    .welcome-banner { 
        background: linear-gradient(135deg, rgba(245, 158, 11, 0.2), rgba(245, 158, 11, 0.05)); 
        border: 1px solid rgba(245, 158, 11, 0.3);
        color: var(--text-primary); 
        padding: 28px 32px; 
        border-radius: var(--radius-xl); 
        margin-bottom: 28px; 
    }
    
    .form-card { 
        background: var(--bg-card); 
        backdrop-filter: blur(12px);
        padding: 32px; 
        border-radius: var(--radius-xl); 
        margin-bottom: 24px; 
        border: 1px solid var(--glass-border); 
    }
    
    .card-title { 
        font-size: 1.15rem; 
        font-weight: 700; 
        margin-bottom: 24px; 
        padding-bottom: 12px; 
        border-bottom: 1px dashed var(--glass-border); 
        display: flex; align-items: center; gap: 8px;
    }
    
    .input-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 24px; }
    .form-group label { display: block; font-weight: 600; color: var(--text-secondary); margin-bottom: 10px; font-size: 13px; text-transform: uppercase; }
    
    .custom-input { 
        width: 100%; padding: 14px 16px; 
        background: rgba(0, 0, 0, 0.2);
        border: 1px solid var(--glass-border); 
        border-radius: var(--radius-md); 
        outline: none; color: var(--text-primary);
    }
    
    .custom-input:focus { border-color: #f59e0b; box-shadow: 0 0 0 3px rgba(245, 158, 11, 0.15); }
    .custom-input option { background: var(--bg-secondary); color: var(--text-primary); }
    
    .serial-row { 
        display: flex; gap: 16px; margin-bottom: 16px; align-items: center; 
        background: rgba(255,255,255,0.02); padding: 12px 20px; 
        border-radius: var(--radius-md); border: 1px solid var(--glass-border); 
    }
    
    .btn-action { padding: 14px 24px; border-radius: var(--radius-md); font-weight: 600; cursor: pointer; border: none; display: inline-flex; align-items: center; gap: 8px; }
    .btn-submit { background: linear-gradient(135deg, #f59e0b, #fbbf24); color: #fff; text-shadow: 0 1px 2px rgba(0,0,0,0.2); font-size: 1rem; }
    .btn-add { background: var(--bg-tertiary); color: var(--text-primary); border: 1px dashed var(--glass-border); }
    .btn-remove { background: var(--danger-bg); color: var(--danger); padding: 14px; }
</style>

<div class="main-wrapper">
    <?php include 'includes/topbar.php'; ?>

    <div class="content">
        <div class="form-center-container">
            <div class="welcome-banner">
                <h1 style="margin: 0 0 8px 0; font-size: 1.6rem; color: #f59e0b;"><span class="material-symbols-rounded">local_shipping</span> Lập Phiếu Điều Chuyển Kho</h1>
                <p style="margin: 0; opacity: 0.8; font-size: 14px;">Chuyển hàng hóa từ kho này sang kho khác.</p>
            </div>

            <?php echo $msg; ?>
            <?php if (isset($_SESSION['flash_success'])): ?>
                <div class="alert alert-success" style="background: rgba(16, 185, 129, 0.1); color: #10b981; border: 1px solid rgba(16, 185, 129, 0.3); padding:15px; border-radius:12px; margin-bottom:24px;">
                    <span class="material-symbols-rounded">check_circle</span> <?php echo $_SESSION['flash_success']; unset($_SESSION['flash_success']); ?>
                </div>
            <?php endif; ?>

            <form method="POST" onsubmit="return confirm('Xác nhận lập phiếu điều chuyển?');">
                <div class="form-card">
                    <div class="card-title"><span class="material-symbols-rounded" style="color: #f59e0b;">route</span> 1. Thông tin Kho</div>
                    <div class="input-grid">
                        <div class="form-group">
                            <label>Từ Kho (Kho Xuất):</label>
                            <select name="maKhoXuat" class="custom-input" required>
                                <?php foreach($khos_arr as $k) echo "<option value='{$k['maKho']}'>{$k['tenKho']}</option>"; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Đến Kho (Kho Nhập):</label>
                            <select name="maKhoNhap" class="custom-input" required>
                                <?php foreach($khos_arr as $k) echo "<option value='{$k['maKho']}'>{$k['tenKho']}</option>"; ?>
                            </select>
                        </div>
                    </div>
                    <div class="form-group" style="margin-top: 16px;">
                        <label>Ghi chú / Lý do điều chuyển:</label>
                        <input type="text" name="ghiChu" class="custom-input" placeholder="VD: Bổ sung hàng hóa cho chi nhánh...">
                    </div>
                </div>

                <div class="form-card">
                    <div class="card-title"><span class="material-symbols-rounded" style="color: #f59e0b;">qr_code_scanner</span> 2. Danh sách Mã đàn xuất (Serial)</div>
                    <div id="serial-container">
                        <div class="serial-row">
                            <input type="text" name="soSerial[]" class="custom-input" placeholder="Nhập/Quét mã Serial" required>
                            <button type="button" class="btn-action btn-add" onclick="addSerialRow()">
                                <span class="material-symbols-rounded">add</span>
                            </button>
                        </div>
                    </div>
                    <button type="button" class="btn-action btn-add" onclick="addSerialRow()" style="margin-top: 16px;">
                        <span class="material-symbols-rounded">add</span> Thêm mã Serial
                    </button>
                </div>

                <div style="text-align: right; padding-bottom: 50px;">
                    <button type="submit" name="btnLuuPhieuDC" class="btn-action btn-submit">
                        <span class="material-symbols-rounded">send</span> GỬI YÊU CẦU ĐIỀU CHUYỂN
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function addSerialRow() {
    const container = document.getElementById('serial-container');
    const newRow = document.createElement('div');
    newRow.className = 'serial-row';
    newRow.innerHTML = `
        <div style="flex: 1;">
            <input type="text" name="soSerial[]" class="custom-input" placeholder="Nhập hoặc quét mã Serial..." required>
        </div>
        <button type="button" class="btn-action btn-remove" onclick="this.parentElement.remove()" title="Xóa"><span class="material-symbols-rounded">delete</span></button>
    `;
    container.appendChild(newRow);
}
</script>

<?php include 'includes/footer.php'; ?>
