<?php
session_start();
require_once 'db.php';

// Kiểm tra đăng nhập
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$fullname = $_SESSION['fullname'];
$role_id = $_SESSION['role_id'];

// XỬ LÝ AJAX LẤY TỒN KHO CHI TIẾT
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'get_inventory_details') {
    header('Content-Type: application/json; charset=utf-8');
    $maMau = intval($_POST['maMau'] ?? 0);
    $result = ['success' => false, 'data' => []];
    
    if ($maMau > 0) {
        $sql = "SELECT k.tenKho, COUNT(ds.maSerial) as soLuong
                FROM kho k
                LEFT JOIN danserial ds ON k.maKho = ds.maKho AND ds.maMau = ? AND ds.trangThai = 'Trong kho'
                GROUP BY k.maKho, k.tenKho";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $maMau);
        $stmt->execute();
        $res = $stmt->get_result();
        
        $data = [];
        $total = 0;
        while ($row = $res->fetch_assoc()) {
            $data[] = $row;
            $total += $row['soLuong'];
        }
        
        $result = [
            'success' => true,
            'total' => $total,
            'data' => $data
        ];
    }
    echo json_encode($result);
    exit();
}

// XỬ LÝ AJAX CẬP NHẬT GIÁ BÁN
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'update_gia_ban') {
    header('Content-Type: application/json; charset=utf-8');
    
    // Check permission
    if ($role_id != 1) {
        echo json_encode(['success' => false, 'message' => 'Bạn không có quyền thực hiện thao tác này.']);
        exit();
    }
    
    $maSerial = intval($_POST['maSerial'] ?? 0);
    $giaBan = floatval($_POST['giaBan'] ?? 0);
    
    if ($maSerial > 0 && $giaBan >= 0) {
        $st = $conn->prepare("UPDATE danserial SET giaBan = ? WHERE maSerial = ?");
        $st->bind_param("di", $giaBan, $maSerial);
        if ($st->execute()) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Lỗi cập nhật CSDL: ' . $conn->error]);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Dữ liệu không hợp lệ.']);
    }
    exit();
}

// Xử lý từ khóa tìm kiếm (nếu có)
$search_query = "";
$search_param = "%";
if (isset($_GET['q']) && !empty(trim($_GET['q']))) {
    $search_query = trim($_GET['q']);
    $search_param = "%" . $search_query . "%";
}
// Filter handling
$filter_brand = $_GET['brand'] ?? '';
$filter_category = $_GET['category'] ?? '';
$filter_brand = $filter_brand !== '' ? intval($filter_brand) : '';
$filter_category = $filter_category !== '' ? intval($filter_category) : '';
$sort = $_GET['sort'] ?? '';
?>
<?php
// Fetch brand and category lists for filter dropdowns
$brands = $conn->query('SELECT maHang, tenHang FROM HangDan')->fetch_all(MYSQLI_ASSOC);
$categories = $conn->query('SELECT maLoai, tenLoai FROM LoaiDan')->fetch_all(MYSQLI_ASSOC);
?>

<?php include 'includes/header.php'; ?>
<?php include 'includes/sidebar.php'; ?>


<div class="main-wrapper">
    <?php include 'includes/topbar.php'; ?>

    <div class="content">
        <form method="GET" action="" class="search-header" style="position: relative; z-index: 99;">
    <div class="search-box">
        <span class="material-symbols-rounded">search</span>
        <input type="text" name="q" placeholder="Nhập tên đàn, mã đàn hoặc tên hãng để tra cứu..." value="<?php echo htmlspecialchars($search_query); ?>">
    </div>
    <style>
    .custom-filter { position: relative; }
    .btn-filter-dropdown {
        display: flex; align-items: center; gap: 6px; padding: 8px 16px;
        background: rgba(255, 255, 255, 0.05); border: 1px solid var(--glass-border);
        border-radius: var(--radius-md); color: var(--text-primary);
        cursor: pointer; font-family: inherit; font-size: 14px; font-weight: 500; transition: 0.2s;
    }
    .btn-filter-dropdown:hover { background: rgba(255, 255, 255, 0.1); }
    .filter-dropdown-menu {
        position: absolute; top: calc(100% + 8px); left: 0; width: 280px;
        background: var(--bg-card); border: 1px solid var(--glass-border);
        border-radius: var(--radius-lg); padding: 16px; box-shadow: 0 10px 30px rgba(0,0,0,0.5);
        z-index: 100; display: none; flex-direction: column; gap: 16px; text-align: left;
    }
    .filter-dropdown-menu.active { display: flex; }
    .filter-group { display: flex; flex-direction: column; gap: 8px; }
    .filter-group-title { font-size: 12px; text-transform: uppercase; color: var(--text-muted); font-weight: 700; letter-spacing: 0.5px; }
    .radio-label { display: flex; align-items: center; gap: 8px; font-size: 14px; color: var(--text-secondary); cursor: pointer; }
    .radio-label input { accent-color: var(--accent); }
    </style>
    <div class="filter-box" style="margin-left:8px;">
        <div class="custom-filter">
            <button type="button" class="btn-filter-dropdown" onclick="document.getElementById('fMenu').classList.toggle('active')">
                <span class="material-symbols-rounded" style="font-size: 18px;">tune</span> Bộ lọc nâng cao
            </button>
            <div class="filter-dropdown-menu" id="fMenu">
                <div class="filter-group">
                    <div class="filter-group-title">Sắp xếp theo</div>
                    <label class="radio-label"><input type="radio" name="sort" value="" <?= $sort == '' ? 'checked' : '' ?>> Mặc định</label>
                    <label class="radio-label"><input type="radio" name="sort" value="new" <?= $sort == 'new' ? 'checked' : '' ?>> Hàng mới nhập</label>
                    <label class="radio-label"><input type="radio" name="sort" value="hot" <?= $sort == 'hot' ? 'checked' : '' ?>> Hàng bán chạy</label>
                </div>
                <div class="filter-group">
                    <div class="filter-group-title">Loại đàn</div>
                    <label class="radio-label"><input type="radio" name="category" value="" <?= $filter_category == '' ? 'checked' : '' ?>> Tất cả loại</label>
                    <?php foreach ($categories as $c): ?>
                        <label class="radio-label"><input type="radio" name="category" value="<?= $c['maLoai'] ?>" <?= $filter_category == $c['maLoai'] ? 'checked' : '' ?>> <?= htmlspecialchars($c['tenLoai']) ?></label>
                    <?php endforeach; ?>
                </div>
                <div class="filter-group">
                    <div class="filter-group-title">Hãng sản xuất</div>
                    <select name="brand" style="padding:8px; border:1px solid var(--glass-border); border-radius:6px; background:rgba(0,0,0,0.2); color:var(--text-primary); font-family: inherit;">
                        <option value="">Tất cả hãng</option>
                        <?php foreach ($brands as $b): ?>
                            <option value="<?= $b['maHang'] ?>" <?= $filter_brand == $b['maHang'] ? 'selected' : '' ?>><?= htmlspecialchars($b['tenHang']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
        </div>
    </div>
    <button type="submit" class="btn-search">
        Tìm kiếm <span class="material-symbols-rounded" style="font-size: 20px;">manage_search</span>
    </button>
    <a href="tracuu.php?all=1" class="btn-search" style="background:linear-gradient(135deg, #6b7280, #9ca3af);margin-left:8px;">Hiển thị tất cả</a>
    <?php if(!empty($search_query) || $filter_brand || $filter_category || !empty($sort) || isset($_GET['all'])): ?>
        <a href="tracuu.php" style="color: var(--danger); margin-left: 8px; text-decoration: none; font-weight: 600; padding: 8px 16px; border-radius: 8px; transition: 0.2s;" onmouseover="this.style.background='rgba(248,113,113,0.1)'" onmouseout="this.style.background='transparent'">Xóa lọc</a>
    <?php endif; ?>
</form>

<script>
    // Close dropdown when clicking outside
    document.addEventListener('click', function(event) {
        const filterBox = document.querySelector('.custom-filter');
        const fMenu = document.getElementById('fMenu');
        if (filterBox && fMenu && !filterBox.contains(event.target)) {
            fMenu.classList.remove('active');
        }
    });
</script>

        <?php
        // Cờ kiểm tra xem có đang tìm kiếm/lọc không
        $is_searching = !empty($search_query) || !empty($filter_brand) || !empty($filter_category) || !empty($sort) || isset($_GET['all']);
        
        // Câu SQL chung để JOIN 3 bảng: MauDan, HangDan, LoaiDan
        $sql = "SELECT md.maMau, md.tenMau, md.moTa, md.hinhAnh, hd.tenHang, ld.tenLoai,
                (SELECT COUNT(*) FROM danserial ds WHERE ds.maMau = md.maMau AND ds.trangThai = 'Trong kho') AS soLuongTon
        FROM MauDan md
        JOIN HangDan hd ON md.maHang = hd.maHang
        JOIN LoaiDan ld ON md.maLoai = ld.maLoai
        WHERE 1=1";
        $types = '';
        $params = [];
        if ($search_query !== '') {
            $sql .= " AND (md.tenMau LIKE ? OR hd.tenHang LIKE ? OR md.maMau IN (SELECT maMau FROM danserial WHERE soSerial LIKE ?))";
            $types .= 'sss';
            $params[] = $search_param;
            $params[] = $search_param;
            $params[] = $search_param;
        }
        if (!empty($filter_brand)) {
            $sql .= " AND hd.maHang = ?";
            $types .= 'i';
            $params[] = $filter_brand;
        }
        if (!empty($filter_category)) {
            $sql .= " AND ld.maLoai = ?";
            $types .= 'i';
            $params[] = $filter_category;
        }
        
        if ($sort == 'new') {
            $sql .= " ORDER BY md.maMau DESC";
        } elseif ($sort == 'hot') {
            $sql .= " ORDER BY md.maMau ASC"; // Tạm thời dùng ASC làm bán chạy
        } else {
            $sql .= " ORDER BY md.maMau DESC"; // Mặc định
        }
        
        $stmt = $conn->prepare($sql);
        if ($types) {
            // Dynamically bind parameters
            $bind_names[] = $types;
            foreach ($params as $key => $value) {
                $bind_name = 'bind' . $key;
                $$bind_name = $value;
                $bind_names[] = &$$bind_name;
            }
            call_user_func_array([$stmt, 'bind_param'], $bind_names);
        }
        $stmt->execute();
        $result = $stmt->get_result();
        ?>

        <?php if($is_searching): ?>
            <?php if(!empty($search_query)): 
                $sql_serial = "SELECT ds.maSerial, ds.soSerial, md.tenMau, hd.tenHang, k.tenKho, ds.trangThai, ds.tinhTrang, ds.giaNhap, ds.giaBan
                               FROM danserial ds
                               JOIN MauDan md ON ds.maMau = md.maMau
                               JOIN HangDan hd ON md.maHang = hd.maHang
                               LEFT JOIN kho k ON ds.maKho = k.maKho
                               WHERE ds.soSerial LIKE ?";
                $stmt_serial = $conn->prepare($sql_serial);
                $stmt_serial->bind_param("s", $search_param);
                $stmt_serial->execute();
                $res_serial = $stmt_serial->get_result();
                if($res_serial->num_rows > 0):
            ?>
            <div class="section-title">
                <span class="material-symbols-rounded" style="color: var(--accent);">qr_code_2</span> 
                Kết quả Mã Đàn (Serial)
            </div>
            <div style="background: var(--bg-card); border-radius: var(--radius-xl); border: 1px solid var(--glass-border); overflow: hidden; margin-bottom: 32px;">
                <table style="width: 100%; border-collapse: collapse; text-align: left;">
                    <thead>
                        <tr style="background: rgba(0,0,0,0.2); border-bottom: 1px solid var(--glass-border);">
                            <th style="padding: 16px; color: var(--text-secondary); font-weight: 600;">Mã Serial</th>
                            <th style="padding: 16px; color: var(--text-secondary); font-weight: 600;">Sản phẩm</th>
                            <th style="padding: 16px; color: var(--text-secondary); font-weight: 600;">Vị trí (Kho)</th>
                            <th style="padding: 16px; color: var(--text-secondary); font-weight: 600;">Trạng thái</th>
                            <?php if ($role_id == 1): ?>
                            <th style="padding: 16px; color: var(--text-secondary); font-weight: 600;">Giá nhập</th>
                            <th style="padding: 16px; color: var(--text-secondary); font-weight: 600;">Giá bán</th>
                            <th style="padding: 16px; color: var(--text-secondary); font-weight: 600;">Thao tác</th>
                            <?php endif; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($s_row = $res_serial->fetch_assoc()): ?>
                        <tr style="border-bottom: 1px solid var(--glass-border); transition: 0.2s;" onmouseover="this.style.background='rgba(124, 92, 252, 0.05)'" onmouseout="this.style.background='transparent'">
                            <td style="padding: 16px; font-weight: 700; color: var(--accent);"><?php echo htmlspecialchars($s_row['soSerial']); ?></td>
                            <td style="padding: 16px; color: var(--text-primary);">
                                <strong><?php echo htmlspecialchars($s_row['tenMau']); ?></strong><br>
                                <span style="font-size: 13px; color: var(--text-muted);"><?php echo htmlspecialchars($s_row['tenHang']); ?> &bull; <?php echo htmlspecialchars($s_row['tinhTrang']); ?></span>
                            </td>
                            <td style="padding: 16px; color: var(--text-primary);"><?php echo htmlspecialchars($s_row['tenKho'] ?? 'Không có'); ?></td>
                            <td style="padding: 16px;">
                                <?php 
                                $statusColor = 'var(--text-secondary)';
                                $st = $s_row['trangThai'];
                                if($st == 'Trong kho') $statusColor = 'var(--success)';
                                elseif($st == 'Đã bán') $statusColor = 'var(--accent)';
                                elseif($st == 'Đã xuất hãng' || $st == 'Đã hủy') $statusColor = 'var(--danger)';
                                elseif($st == 'Đang bảo hành') $statusColor = '#f59e0b';
                                ?>
                                <span style="color: <?php echo $statusColor; ?>; font-weight: 600; padding: 4px 12px; border-radius: 20px; background: rgba(255,255,255,0.05); font-size: 13px;">
                                    <?php echo htmlspecialchars($st); ?>
                                </span>
                            </td>
                            <?php if ($role_id == 1): ?>
                            <td style="padding: 16px; font-weight: 600; color: var(--text-muted);"><?php echo number_format($s_row['giaNhap'] ?? 0, 0, ',', '.'); ?>đ</td>
                            <td style="padding: 16px; font-weight: 600; color: var(--success);"><?php echo number_format($s_row['giaBan'] ?? 0, 0, ',', '.'); ?>đ</td>
                            <td style="padding: 16px;">
                                <button class="btn-action" onclick="openEditSerialModal('<?php echo $s_row['maSerial']; ?>', '<?php echo $s_row['soSerial']; ?>', <?php echo $s_row['giaBan'] ?? 0; ?>)" style="background: rgba(124, 92, 252, 0.1); color: var(--accent); border: none; padding: 6px 12px; border-radius: 6px; cursor: pointer; font-size: 13px; font-weight: 600; display: inline-flex; align-items: center; gap: 4px;">
                                    <span class="material-symbols-rounded" style="font-size: 16px;">edit</span> Đặt giá
                                </button>
                            </td>
                            <?php endif; ?>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
            <?php 
                endif;
            endif; 
            ?>

            <div class="section-title">
                <span class="material-symbols-rounded" style="color: var(--accent);">manage_search</span> 
                <?php if (isset($_GET['all'])): ?>
                    Tất cả sản phẩm
                <?php else: ?>
                    Kết quả Mẫu sản phẩm cho: "<?php echo htmlspecialchars($search_query); ?>"
                <?php endif; ?>
            </div>
            <div class="product-grid">
                <?php
                // Parameters already bound dynamically

                $stmt->execute();
                $result = $stmt->get_result();

                if($result->num_rows > 0):
                    while($row = $result->fetch_assoc()):
                ?>
                    <div class="product-card">
                        <div class="product-img" style="cursor: pointer;" onclick="window.location.href='chi_tiet_san_pham.php?id=<?php echo $row['maMau']; ?>'">
                            <?php if(!empty($row['hinhAnh'])): ?>
                                <img src="images/<?php echo htmlspecialchars($row['hinhAnh']); ?>" alt="<?php echo htmlspecialchars($row['tenMau']); ?>">
                            <?php else: ?>
                                <span class="material-symbols-rounded" style="font-size: 64px; color: rgba(255,255,255,0.1);">piano</span>
                            <?php endif; ?>
                        </div>
                        <div class="product-info">
                            <div class="brand-name"><?php echo $row['tenHang']; ?></div>
                            <div class="product-name" style="cursor: pointer; transition: 0.2s;" onmouseover="this.style.color='var(--accent)'" onmouseout="this.style.color='var(--text-primary)'" onclick="window.location.href='chi_tiet_san_pham.php?id=<?php echo $row['maMau']; ?>'"><?php echo $row['tenMau']; ?></div>
                            <div class="product-desc"><?php echo $row['moTa']; ?></div>
                            <div class="product-meta">
                                <span class="category-tag"><?php echo $row['tenLoai']; ?></span>
                                <button type="button" class="btn-check-inventory" onclick="openInventoryModal(<?php echo $row['maMau']; ?>, '<?php echo htmlspecialchars(addslashes($row['tenMau'])); ?>')">
                                    <span class="material-symbols-rounded" style="font-size: 16px;">inventory_2</span>
                                    Kho: <?php echo $row['soLuongTon']; ?>
                                </button>
                            </div>
                        </div>
                    </div>
                <?php 
                    endwhile; 
                else: 
                    echo "<p style='color: var(--danger); font-weight: 500;'>Không tìm thấy sản phẩm nào phù hợp!</p>";
                endif; 
                ?>
            </div>

        <?php else: ?>  
            
            <div class="section-title">
                <span class="material-symbols-rounded" style="color: #f43f5e; filter: drop-shadow(0 0 8px rgba(244,63,94,0.5));">new_releases</span> 
                Sản phẩm Mới nhập
            </div>
            <div class="product-grid">
                <?php
                $sql_new = "SELECT md.*, hd.tenHang, ld.tenLoai,
                            (SELECT COUNT(*) FROM danserial ds WHERE ds.maMau = md.maMau AND ds.trangThai = 'Trong kho') AS soLuongTon
                            FROM MauDan md
                            JOIN HangDan hd ON md.maHang = hd.maHang
                            JOIN LoaiDan ld ON md.maLoai = ld.maLoai
                            ORDER BY md.maMau DESC LIMIT 4";
                $result_new = $conn->query($sql_new);
                while($row = $result_new->fetch_assoc()):
                ?>
                    <div class="product-card">
                        <div class="product-img" style="cursor: pointer;" onclick="window.location.href='chi_tiet_san_pham.php?id=<?php echo $row['maMau']; ?>'">
                            <span class="badge badge-new">MỚI</span>
                            <?php if(!empty($row['hinhAnh'])): ?>
                                <img src="images/<?php echo htmlspecialchars($row['hinhAnh']); ?>" alt="<?php echo htmlspecialchars($row['tenMau']); ?>">
                            <?php else: ?>
                                <span class="material-symbols-rounded" style="font-size: 64px; color: rgba(255,255,255,0.1);">music_note</span>
                            <?php endif; ?>
                        </div>
                        <div class="product-info">
                            <div class="brand-name"><?php echo $row['tenHang']; ?></div>
                            <div class="product-name" style="cursor: pointer; transition: 0.2s;" onmouseover="this.style.color='var(--accent)'" onmouseout="this.style.color='var(--text-primary)'" onclick="window.location.href='chi_tiet_san_pham.php?id=<?php echo $row['maMau']; ?>'"><?php echo $row['tenMau']; ?></div>
                            <div class="product-desc"><?php echo $row['moTa']; ?></div>
                            <div class="product-meta">
                                <span class="category-tag"><?php echo $row['tenLoai']; ?></span>
                                <button type="button" class="btn-check-inventory" onclick="openInventoryModal(<?php echo $row['maMau']; ?>, '<?php echo htmlspecialchars(addslashes($row['tenMau'])); ?>')">
                                    <span class="material-symbols-rounded" style="font-size: 16px;">inventory_2</span>
                                    Kho: <?php echo $row['soLuongTon']; ?>
                                </button>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>

            <div class="section-title">
                <span class="material-symbols-rounded" style="color: #f59e0b; filter: drop-shadow(0 0 8px rgba(245,158,11,0.5));">local_fire_department</span> 
                Sản phẩm Bán chạy nhất
            </div>
            <div class="product-grid">
                <?php
                $sql_hot = "SELECT md.*, hd.tenHang, ld.tenLoai,
                            (SELECT COUNT(*) FROM danserial ds WHERE ds.maMau = md.maMau AND ds.trangThai = 'Trong kho') AS soLuongTon
                            FROM MauDan md
                            JOIN HangDan hd ON md.maHang = hd.maHang
                            JOIN LoaiDan ld ON md.maLoai = ld.maLoai
                            ORDER BY md.maMau ASC LIMIT 4";
                $result_hot = $conn->query($sql_hot);
                while($row = $result_hot->fetch_assoc()):
                ?>
                    <div class="product-card">
                        <div class="product-img" style="cursor: pointer;" onclick="window.location.href='chi_tiet_san_pham.php?id=<?php echo $row['maMau']; ?>'">
                            <span class="badge badge-hot">HOT</span>
                            <?php if(!empty($row['hinhAnh'])): ?>
                                <img src="images/<?php echo htmlspecialchars($row['hinhAnh']); ?>" alt="<?php echo htmlspecialchars($row['tenMau']); ?>">
                            <?php else: ?>
                                <span class="material-symbols-rounded" style="font-size: 64px; color: rgba(255,255,255,0.1);">queue_music</span>
                            <?php endif; ?>
                        </div>
                        <div class="product-info">
                            <div class="brand-name"><?php echo $row['tenHang']; ?></div>
                            <div class="product-name" style="cursor: pointer; transition: 0.2s;" onmouseover="this.style.color='var(--accent)'" onmouseout="this.style.color='var(--text-primary)'" onclick="window.location.href='chi_tiet_san_pham.php?id=<?php echo $row['maMau']; ?>'"><?php echo $row['tenMau']; ?></div>
                            <div class="product-desc"><?php echo $row['moTa']; ?></div>
                            <div class="product-meta">
                                <span class="category-tag"><?php echo $row['tenLoai']; ?></span>
                                <button type="button" class="btn-check-inventory" onclick="openInventoryModal(<?php echo $row['maMau']; ?>, '<?php echo htmlspecialchars(addslashes($row['tenMau'])); ?>')">
                                    <span class="material-symbols-rounded" style="font-size: 16px;">inventory_2</span>
                                    Kho: <?php echo $row['soLuongTon']; ?>
                                </button>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>

        <?php endif; ?>

    </div>
</div>

<!-- Modal Kiểm tra kho -->
<div class="modal-overlay" id="inventoryModal">
    <div class="modal-card">
        <div class="modal-header">
            <h3 class="modal-title">
                <span class="material-symbols-rounded" style="color: var(--accent);">inventory_2</span>
                Tồn kho hiện tại
            </h3>
            <button class="modal-close" onclick="closeInventoryModal()"><span class="material-symbols-rounded">close</span></button>
        </div>
        <div class="modal-body">
            <div style="margin-bottom: 16px; font-weight: 600; color: var(--text-secondary);" id="invProductName">Đang tải...</div>
            <div id="invContent">
                <div style="text-align: center; padding: 20px; color: var(--text-muted);">
                    <span class="material-symbols-rounded" style="font-size: 32px; animation: spin 1s linear infinite;">autorenew</span>
                </div>
            </div>
            <div style="margin-top: 20px; display: flex; justify-content: space-between; padding-top: 16px; border-top: 1px dashed var(--glass-border); font-weight: 700;">
                <span>Tổng cộng:</span>
                <span id="invTotalQty" style="color: var(--accent); font-size: 18px;">0</span>
            </div>
        </div>
    </div>
</div>

<script>
    const inventoryModal = document.getElementById('inventoryModal');
    
    async function openInventoryModal(maMau, tenMau) {
        document.getElementById('invProductName').textContent = tenMau;
        document.getElementById('invContent').innerHTML = '<div style="text-align: center; padding: 20px; color: var(--text-muted);"><span class="material-symbols-rounded" style="font-size: 32px; animation: spin 1s linear infinite;">autorenew</span></div>';
        document.getElementById('invTotalQty').textContent = '0';
        inventoryModal.classList.add('active');
        
        try {
            const fd = new FormData();
            fd.append('action', 'get_inventory_details');
            fd.append('maMau', maMau);
            
            const resp = await fetch('', { method: 'POST', body: fd });
            const result = await resp.json();
            
            if (result.success) {
                let html = '';
                if (result.data.length > 0) {
                    result.data.forEach(item => {
                        let qtyClass = item.soLuong > 0 ? '' : 'empty';
                        html += `
                            <div class="inventory-item">
                                <span class="warehouse-name"><span class="material-symbols-rounded" style="font-size: 18px; vertical-align: middle; margin-right: 6px; color: var(--text-muted);">warehouse</span>${item.tenKho}</span>
                                <span class="warehouse-qty ${qtyClass}">${item.soLuong}</span>
                            </div>
                        `;
                    });
                } else {
                    html = '<div style="text-align: center; padding: 20px; color: var(--danger);">Không tìm thấy thông tin kho.</div>';
                }
                document.getElementById('invContent').innerHTML = html;
                document.getElementById('invTotalQty').textContent = result.total;
            } else {
                document.getElementById('invContent').innerHTML = '<div style="color: var(--danger); text-align: center;">Lỗi tải dữ liệu</div>';
            }
        } catch(e) {
            document.getElementById('invContent').innerHTML = '<div style="color: var(--danger); text-align: center;">Lỗi kết nối máy chủ</div>';
        }
    }
    
    function closeInventoryModal() {
        inventoryModal.classList.remove('active');
    }
    
    inventoryModal.addEventListener('click', (e) => {
        if (e.target === inventoryModal) closeInventoryModal();
    });
</script>

<?php if ($role_id == 1): ?>
<!-- Modal Thiết lập giá bán -->
<div class="modal-overlay" id="editSerialModal">
    <div class="modal-card" style="max-width: 400px; padding: 24px;">
        <div class="modal-header">
            <h3 class="modal-title">
                <span class="material-symbols-rounded" style="color: var(--accent);">edit</span>
                Thiết lập giá bán
            </h3>
            <button class="modal-close" onclick="closeEditSerialModal()"><span class="material-symbols-rounded">close</span></button>
        </div>
        <div class="modal-body">
            <p style="margin-bottom: 20px; color: var(--text-secondary);">Mã Serial: <strong id="editSerialNum" style="color: var(--text-primary); font-family: monospace; font-size: 16px;"></strong></p>
            <form id="editSerialForm" onsubmit="submitEditSerial(event)">
                <input type="hidden" id="editMaSerial" name="maSerial">
                <input type="hidden" name="action" value="update_gia_ban">
                <div class="form-group" style="margin-bottom: 24px;">
                    <label style="display: block; margin-bottom: 8px; font-weight: 500; font-size: 14px; color: var(--text-muted);">Giá bán (VNĐ):</label>
                    <input type="number" id="editGiaBan" name="giaBan" class="custom-input" style="width: 100%; padding: 12px; border-radius: 8px; border: 1px solid var(--border); box-sizing: border-box; font-size: 16px; background: rgba(0,0,0,0.02);" required>
                </div>
                <button type="submit" style="width: 100%; padding: 14px; background: var(--accent); color: white; border: none; border-radius: 8px; font-size: 15px; font-weight: 600; cursor: pointer; transition: 0.2s;" onmouseover="this.style.opacity='0.9'" onmouseout="this.style.opacity='1'">Lưu thiết lập</button>
            </form>
        </div>
    </div>
</div>

<script>
    const editSerialModal = document.getElementById('editSerialModal');
    
    function openEditSerialModal(id, serial, giaBan) {
        document.getElementById('editMaSerial').value = id;
        document.getElementById('editSerialNum').textContent = serial;
        document.getElementById('editGiaBan').value = giaBan == 0 ? '' : giaBan;
        editSerialModal.classList.add('active');
    }
    
    function closeEditSerialModal() {
        editSerialModal.classList.remove('active');
    }
    
    editSerialModal.addEventListener('click', (e) => {
        if (e.target === editSerialModal) closeEditSerialModal();
    });
    
    async function submitEditSerial(e) {
        e.preventDefault();
        const fd = new FormData(e.target);
        const submitBtn = e.target.querySelector('button[type="submit"]');
        const oldText = submitBtn.textContent;
        submitBtn.textContent = 'Đang xử lý...';
        submitBtn.disabled = true;
        
        try {
            const resp = await fetch('tracuu.php', { method: 'POST', body: fd });
            const result = await resp.json();
            if(result.success) {
                location.reload();
            } else {
                alert(result.message || 'Có lỗi xảy ra!');
            }
        } catch(err) {
            alert('Lỗi kết nối máy chủ!');
        } finally {
            submitBtn.textContent = oldText;
            submitBtn.disabled = false;
        }
    }
</script>
<?php endif; ?>

<?php include 'includes/footer.php'; ?>