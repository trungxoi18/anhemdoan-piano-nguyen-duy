<div class="sidebar">
    <div class="brand">
        <span class="material-symbols-rounded">piano</span> 
        <span class="brand-text">Nguyễn Duy Piano</span>
    </div>
    <div class="nav-menu">
        <a href="index.php" class="nav-item active"><span class="material-symbols-rounded">dashboard</span> <?php echo __('menu_dashboard'); ?></a>
        
        <div class="nav-section-title"><?php echo __('section_work'); ?></div>
        <a href="todo.php" class="nav-item"><span class="material-symbols-rounded">checklist</span> <?php echo __('menu_todo'); ?></a>
        <a href="lichsu_hoatdong.php" class="nav-item"><span class="material-symbols-rounded">history</span> <?php echo __('menu_history'); ?></a>

        <?php if (isset($_SESSION['role_id']) && in_array($_SESSION['role_id'], [1, 2, 3])): ?>
        <div class="nav-section-title">Kho vận</div>
        <?php if (in_array($_SESSION['role_id'], [1, 2, 3])): ?>
            <a href="hoadon_moi.php" class="nav-item"><span class="material-symbols-rounded">receipt_long</span> Lập hóa đơn</a>
        <?php endif; ?>
        <a href="phieunhap.php" class="nav-item"><span class="material-symbols-rounded">input</span> Phiếu nhập kho</a>
        <a href="phieuxuat.php" class="nav-item"><span class="material-symbols-rounded">output</span> Phiếu xuất kho</a>
        <a href="dieuchuyen.php" class="nav-item"><span class="material-symbols-rounded">local_shipping</span> Điều chuyển nội bộ</a>
        <a href="baocao_nhapxuatton.php" class="nav-item"><span class="material-symbols-rounded">analytics</span> Báo cáo nhập xuất tồn</a>
        <a href="tonkho_hientai.php" class="nav-item"><span class="material-symbols-rounded">inventory</span> Tồn kho hiện tại</a>
        <?php endif; ?>

        <div class="nav-section-title"><?php echo __('section_utils'); ?></div>
        <a href="tracuu.php" class="nav-item"><span class="material-symbols-rounded">manage_search</span> <?php echo __('menu_search'); ?></a>
        <a href="quanly_baotri.php" class="nav-item"><span class="material-symbols-rounded">build</span> Tiếp nhận Bảo trì</a>
        <a href="cs_baohanh.php" class="nav-item"><span class="material-symbols-rounded">policy</span> <?php echo __('menu_policy'); ?></a>
        <a href="cs_khuyenmai.php" class="nav-item"><span class="material-symbols-rounded">redeem</span> <?php echo __('menu_promo'); ?></a>

        <?php if (isset($_SESSION['role_id']) && $_SESSION['role_id'] == 1): ?>
        <div class="nav-section-title">Hệ thống</div>
        <a href="quanly_taikhoan.php" class="nav-item"><span class="material-symbols-rounded">manage_accounts</span> Phân quyền tài khoản</a>
        <?php endif; ?>

        <div class="nav-section-title"><?php echo __('section_personal'); ?></div>
        <a href="thongtin_canhan.php" class="nav-item"><span class="material-symbols-rounded">account_circle</span> <?php echo __('menu_profile'); ?></a>
        
        <a href="logout.php" class="nav-item logout"><span class="material-symbols-rounded">logout</span> <?php echo __('menu_logout'); ?></a>
    </div>
</div>