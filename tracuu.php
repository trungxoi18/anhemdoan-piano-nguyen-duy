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
?>
<?php
// Fetch brand and category lists for filter dropdowns
$brands = $conn->query('SELECT maHang, tenHang FROM HangDan')->fetch_all(MYSQLI_ASSOC);
$categories = $conn->query('SELECT maLoai, tenLoai FROM LoaiDan')->fetch_all(MYSQLI_ASSOC);
?>

<?php include 'includes/header.php'; ?>
<?php include 'includes/sidebar.php'; ?>

<style>
    /* Dark Mode Premium Search Styles */
    
    .search-header {
        background: var(--bg-card);
        backdrop-filter: blur(12px);
        padding: 24px;
        border-radius: var(--radius-xl);
        border: 1px solid var(--glass-border);
        box-shadow: var(--shadow-sm);
        margin-bottom: 32px;
        display: flex;
        gap: 16px;
        align-items: center;
        animation: fadeInUp 0.4s ease;
    }

    .search-box {
        flex: 1;
        position: relative;
    }

    .search-box .material-symbols-rounded {
        position: absolute;
        left: 20px;
        top: 50%;
        transform: translateY(-50%);
        color: var(--text-secondary);
        font-size: 22px;
        transition: 0.3s;
    }

    .search-box input {
        width: 100%;
        padding: 16px 20px 16px 56px;
        background: rgba(0, 0, 0, 0.2);
        border: 1px solid var(--glass-border);
        border-radius: var(--radius-lg);
        font-size: 15px;
        color: var(--text-primary);
        outline: none;
        transition: 0.3s;
        font-family: inherit;
    }

    .search-box input::placeholder { color: var(--text-muted); }
    
    .search-box input:focus {
        border-color: var(--accent);
        box-shadow: 0 0 0 4px rgba(124, 92, 252, 0.15);
        background: rgba(124, 92, 252, 0.05);
    }
    .search-box input:focus + .material-symbols-rounded { color: var(--accent); }

    .btn-search {
        background: linear-gradient(135deg, var(--accent), #9061f9);
        color: white;
        border: none;
        padding: 16px 32px;
        border-radius: var(--radius-lg);
        font-weight: 700;
        cursor: pointer;
        display: flex;
        gap: 8px;
        align-items: center;
        transition: 0.3s;
        box-shadow: 0 4px 12px var(--accent-glow);
        font-family: inherit;
    }
    
    .btn-search:hover { 
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(124, 92, 252, 0.4);
    }

    .section-title {
        font-size: 1.25rem;
        font-weight: 800;
        color: var(--text-primary);
        margin-bottom: 24px;
        display: flex;
        align-items: center;
        gap: 12px;
        animation: fadeIn 0.5s ease;
    }

    .product-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
        gap: 24px;
        margin-bottom: 48px;
    }

    .product-card {
        background: var(--bg-card);
        backdrop-filter: blur(12px);
        border-radius: var(--radius-xl);
        overflow: hidden;
        border: 1px solid var(--glass-border);
        display: flex;
        flex-direction: column;
        transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        transform-style: preserve-3d;
    }

    .product-card:hover {
        transform: translateY(-8px) scale(1.02);
        box-shadow: var(--shadow-glow);
        border-color: rgba(124, 92, 252, 0.3);
        z-index: 2;
    }

    .product-img {
        height: 220px;
        background: rgba(0,0,0,0.4);
        display: flex;
        align-items: center;
        justify-content: center;
        position: relative;
        overflow: hidden; 
    }
    
    .product-img::after {
        content: ''; position: absolute; bottom: 0; left: 0; width: 100%; height: 50%;
        background: linear-gradient(to top, var(--bg-card-solid), transparent);
        pointer-events: none;
    }

    .product-img img {
        width: 100%;
        height: 100%;
        object-fit: cover; 
        transition: transform 0.6s cubic-bezier(0.4, 0, 0.2, 1);
        opacity: 0.8;
    }

    .product-card:hover .product-img img {
        transform: scale(1.1);
        opacity: 1;
    }

    .badge {
        position: absolute;
        top: 16px;
        right: 16px;
        padding: 6px 14px;
        border-radius: var(--radius-full);
        font-size: 11px;
        font-weight: 800;
        z-index: 2; 
        letter-spacing: 0.5px;
        box-shadow: 0 4px 10px rgba(0,0,0,0.5);
    }
    .badge-new { background: linear-gradient(135deg, #f43f5e, #fb7185); color: white; }
    .badge-hot { background: linear-gradient(135deg, #f59e0b, #fbbf24); color: white; }

    .product-info {
        padding: 24px;
        flex: 1;
        display: flex;
        flex-direction: column;
        position: relative;
        z-index: 2;
    }

    .brand-name {
        color: var(--accent-secondary);
        font-size: 12px;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 1px;
        margin-bottom: 6px;
    }

    .product-name {
        color: var(--text-primary);
        font-size: 18px;
        font-weight: 700;
        margin-bottom: 12px;
        line-height: 1.3;
    }

    .product-desc {
        color: var(--text-secondary);
        font-size: 14px;
        line-height: 1.6;
        margin-bottom: 20px;
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }

    .product-meta {
        margin-top: auto;
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding-top: 16px;
        border-top: 1px dashed var(--glass-border);
    }

    .category-tag {
        background: rgba(124, 92, 252, 0.15);
        color: var(--accent);
        border: 1px solid rgba(124, 92, 252, 0.2);
        padding: 4px 12px;
        border-radius: 8px;
        font-size: 12px;
        font-weight: 700;
    }

    .btn-detail {
        color: var(--accent-secondary);
        text-decoration: none;
        font-weight: 600;
        font-size: 14px;
        display: flex;
        align-items: center;
        gap: 6px;
        transition: 0.3s;
    }
    .btn-detail:hover { color: var(--accent); text-shadow: 0 0 8px var(--accent-glow); }
    .btn-detail .material-symbols-rounded { transition: transform 0.3s; font-size: 18px; }
    .btn-detail:hover .material-symbols-rounded { transform: translateX(4px); }
</style>

<div class="main-wrapper">
    <?php include 'includes/topbar.php'; ?>

    <div class="content">
        <form method="GET" action="" class="search-header">
    <div class="search-box">
        <span class="material-symbols-rounded">search</span>
        <input type="text" name="q" placeholder="Nhập tên đàn, mã đàn hoặc tên hãng để tra cứu..." value="<?php echo htmlspecialchars($search_query); ?>">
    </div>
    <div class="filter-box" style="display:flex;gap:8px;margin-left:8px;">
        <select name="brand" class="filter-select" style="padding:8px 12px;border:1px solid var(--glass-border);border-radius:var(--radius-md);background:rgba(0,0,0,0.2);color:var(--text-primary);">
            <option value="">Tất cả hãng</option>
            <?php foreach ($brands as $b): ?>
                <option value="<?php echo $b['maHang']; ?>" <?php if ($filter_brand == $b['maHang']) echo 'selected'; ?>><?php echo htmlspecialchars($b['tenHang']); ?></option>
            <?php endforeach; ?>
        </select>
        <select name="category" class="filter-select" style="padding:8px 12px;border:1px solid var(--glass-border);border-radius:var(--radius-md);background:rgba(0,0,0,0.2);color:var(--text-primary);">
            <option value="">Tất cả loại</option>
            <?php foreach ($categories as $c): ?>
                <option value="<?php echo $c['maLoai']; ?>" <?php if ($filter_category == $c['maLoai']) echo 'selected'; ?>><?php echo htmlspecialchars($c['tenLoai']); ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <button type="submit" class="btn-search">
        Tìm kiếm <span class="material-symbols-rounded" style="font-size: 20px;">manage_search</span>
    </button>
    <a href="tracuu.php?all=1" class="btn-search" style="background:linear-gradient(135deg, #6b7280, #9ca3af);margin-left:8px;">Hiển thị tất cả</a>
    <?php if(!empty($search_query) || $filter_brand || $filter_category || isset($_GET['all'])): ?>
        <a href="tracuu.php" style="color: var(--danger); margin-left: 8px; text-decoration: none; font-weight: 600; padding: 8px 16px; border-radius: 8px; transition: 0.2s;" onmouseover="this.style.background='rgba(248,113,113,0.1)'" onmouseout="this.style.background='transparent'">Xóa lọc</a>
    <?php endif; ?>
</form>

        <?php
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
            $sql .= " AND (md.tenMau LIKE ? OR hd.tenHang LIKE ?)";
            $types .= 'ss';
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

        <?php if(!empty($search_query) || !empty($filter_brand) || !empty($filter_category) || isset($_GET['all'])): ?>
            <div class="section-title">
                <span class="material-symbols-rounded" style="color: var(--accent);">manage_search</span> 
                <?php if (isset($_GET['all'])): ?>
                    Tất cả sản phẩm
                <?php else: ?>
                    Kết quả tìm kiếm cho: "<?php echo htmlspecialchars($search_query); ?>"
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
                        <div class="product-img">
                            <?php if(!empty($row['hinhAnh'])): ?>
                                <img src="images/<?php echo htmlspecialchars($row['hinhAnh']); ?>" alt="<?php echo htmlspecialchars($row['tenMau']); ?>">
                            <?php else: ?>
                                <span class="material-symbols-rounded" style="font-size: 64px; color: rgba(255,255,255,0.1);">piano</span>
                            <?php endif; ?>
                        </div>
                        <div class="product-info">
                            <div class="brand-name"><?php echo $row['tenHang']; ?></div>
                            <div class="product-name"><?php echo $row['tenMau']; ?></div>
                            <div class="product-desc"><?php echo $row['moTa']; ?></div>
                            <div class="product-meta">
                                <span class="category-tag"><?php echo $row['tenLoai']; ?></span>
                                <span style="font-size: 13px; font-weight: 600; color: <?php echo $row['soLuongTon'] > 0 ? 'var(--success)' : 'var(--text-muted)'; ?>;">
                                    Tồn kho: <?php echo $row['soLuongTon']; ?>
                                </span>
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
                        <div class="product-img">
                            <span class="badge badge-new">MỚI</span>
                            <?php if(!empty($row['hinhAnh'])): ?>
                                <img src="images/<?php echo htmlspecialchars($row['hinhAnh']); ?>" alt="<?php echo htmlspecialchars($row['tenMau']); ?>">
                            <?php else: ?>
                                <span class="material-symbols-rounded" style="font-size: 64px; color: rgba(255,255,255,0.1);">music_note</span>
                            <?php endif; ?>
                        </div>
                        <div class="product-info">
                            <div class="brand-name"><?php echo $row['tenHang']; ?></div>
                            <div class="product-name"><?php echo $row['tenMau']; ?></div>
                            <div class="product-desc"><?php echo $row['moTa']; ?></div>
                            <div class="product-meta">
                                <span class="category-tag"><?php echo $row['tenLoai']; ?></span>
                                <span style="font-size: 13px; font-weight: 600; color: <?php echo $row['soLuongTon'] > 0 ? 'var(--success)' : 'var(--text-muted)'; ?>;">
                                    Tồn kho: <?php echo $row['soLuongTon']; ?>
                                </span>
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
                        <div class="product-img">
                            <span class="badge badge-hot">HOT</span>
                            <?php if(!empty($row['hinhAnh'])): ?>
                                <img src="images/<?php echo htmlspecialchars($row['hinhAnh']); ?>" alt="<?php echo htmlspecialchars($row['tenMau']); ?>">
                            <?php else: ?>
                                <span class="material-symbols-rounded" style="font-size: 64px; color: rgba(255,255,255,0.1);">queue_music</span>
                            <?php endif; ?>
                        </div>
                        <div class="product-info">
                            <div class="brand-name"><?php echo $row['tenHang']; ?></div>
                            <div class="product-name"><?php echo $row['tenMau']; ?></div>
                            <div class="product-desc"><?php echo $row['moTa']; ?></div>
                            <div class="product-meta">
                                <span class="category-tag"><?php echo $row['tenLoai']; ?></span>
                                <span style="font-size: 13px; font-weight: 600; color: <?php echo $row['soLuongTon'] > 0 ? 'var(--success)' : 'var(--text-muted)'; ?>;">
                                    Tồn kho: <?php echo $row['soLuongTon']; ?>
                                </span>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>

        <?php endif; ?>

    </div>
</div>

<?php include 'includes/footer.php'; ?>