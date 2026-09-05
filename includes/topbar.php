<?php
// Lấy thông báo
$uid_tb = $_SESSION['user_id'] ?? 0;
$rid_tb = $_SESSION['role_id'] ?? 0;
$thongbao_sql = "SELECT * FROM ThongBao WHERE (maTaiKhoan = $uid_tb OR maVaiTro = $rid_tb) ORDER BY ngayTao DESC LIMIT 10";
$thongbao_res = $conn->query($thongbao_sql);
$tb_count = 0;
$thongbaos = [];
if ($thongbao_res) {
    while($tb = $thongbao_res->fetch_assoc()) {
        $thongbaos[] = $tb;
        if ($tb['daDoc'] == 0) $tb_count++;
    }
}
?>
<div class="topbar">
    <div style="display: flex; align-items: center; gap: 12px;">
        <button id="btn-sidebar-toggle" class="action-btn" title="Ẩn/Hiện menu" onclick="toggleSidebar()" style="flex-shrink: 0;">
            <span class="material-symbols-rounded">menu</span>
        </button>
        <div class="topbar-title"><?php echo __('dashboard_title'); ?></div>
    </div>

    <div class="global-search">
        <span class="material-symbols-rounded">search</span>
        <input type="text" placeholder="<?php echo __('search_placeholder'); ?>">
    </div>

    <div class="topbar-right">
        <style>
            .lang-segmented {
                display: flex;
                background: var(--bg-tertiary);
                border-radius: 20px;
                padding: 4px;
                border: 1px solid var(--border);
                align-items: center;
            }
            .lang-segmented a {
                text-decoration: none;
                font-size: 11px;
                font-weight: 700;
                padding: 5px 12px;
                border-radius: 16px;
                color: var(--text-secondary);
                transition: 0.3s cubic-bezier(0.4, 0, 0.2, 1);
                display: flex;
                align-items: center;
            }
            .lang-segmented a:hover:not(.active) {
                color: var(--text-primary);
                background: rgba(255, 255, 255, 0.05);
            }
            .lang-segmented a.active {
                background: linear-gradient(135deg, var(--accent), var(--accent-secondary));
                color: white;
                box-shadow: 0 2px 8px rgba(124, 92, 252, 0.35);
            }
        </style>
        <div class="lang-segmented">
            <a href="?lang=vn" class="<?php echo (!isset($_SESSION['lang']) || $_SESSION['lang'] == 'vn') ? 'active' : ''; ?>">VN</a>
            <a href="?lang=en" class="<?php echo (isset($_SESSION['lang']) && $_SESSION['lang'] == 'en') ? 'active' : ''; ?>">EN</a>
        </div>

        <a href="todo.php" class="action-btn" title="Việc cần làm" style="color: inherit; text-decoration: none;">
            <span class="material-symbols-rounded">checklist</span>
        </a>

        <div class="action-btn" title="Thông báo" style="position: relative;" onclick="document.getElementById('notif-dropdown').classList.toggle('show')">
            <span class="material-symbols-rounded">notifications</span>
            <?php if($tb_count > 0): ?>
                <span class="notification-badge"><?php echo $tb_count; ?></span>
            <?php endif; ?>
            
            <div id="notif-dropdown" class="dropdown-menu" style="display: none; position: absolute; top: 100%; right: 0; background: var(--bg-card); border: 1px solid var(--glass-border); border-radius: 8px; width: 300px; padding: 15px; z-index: 1000; box-shadow: 0 4px 12px rgba(0,0,0,0.5); text-align: left;">
                <div style="font-weight: bold; margin-bottom: 10px; padding-bottom: 5px; border-bottom: 1px solid var(--glass-border); color: var(--text-primary);">Thông báo mới</div>
                <?php if (count($thongbaos) > 0): ?>
                    <?php foreach($thongbaos as $tb): ?>
                        <a href="<?php echo htmlspecialchars($tb['link'] ?? '#'); ?>" style="display: block; padding: 10px 0; border-bottom: 1px solid rgba(255,255,255,0.05); color: <?php echo $tb['daDoc'] ? 'var(--text-secondary)' : 'var(--text-primary)'; ?>; font-size: 13px; text-decoration: none;">
                            <?php if(!$tb['daDoc']) echo "<span style='color: var(--accent); font-size: 12px; margin-right: 5px;'>●</span>"; ?>
                            <?php echo htmlspecialchars($tb['noiDung']); ?>
                            <br><small style="color: var(--text-muted);"><?php echo date('H:i d/m', strtotime($tb['ngayTao'])); ?></small>
                        </a>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div style="color: var(--text-muted); font-size: 13px;">Không có thông báo nào.</div>
                <?php endif; ?>
            </div>
        </div>
        

        <div class="user-profile">
            <div class="avatar">
                <?php if (!empty($user_avatar)): ?>
                    <img src="<?php echo htmlspecialchars($user_avatar); ?>" alt="Avatar" style="width: 100%; height: 100%; border-radius: 50%; object-fit: cover;">
                <?php else: ?>
                    <?php echo $avatar_letter; ?>
                <?php endif; ?>
            </div>
            <div class="user-info">
                <span class="user-name"><?php echo htmlspecialchars($fullname); ?></span>
            </div>
        </div>
    </div>
</div>