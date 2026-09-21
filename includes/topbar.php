<?php
// Lấy thông báo
$uid_tb = $_SESSION['user_id'] ?? 0;
$rid_tb = $_SESSION['role_id'] ?? 0;
$thongbao_sql = "SELECT * FROM thongbao WHERE (maTaiKhoan = $uid_tb OR maVaiTro = $rid_tb) ORDER BY ngayTao DESC LIMIT 10";
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

    <!-- Container Tìm kiếm nhanh thông minh (Smart Quick Search) -->
    <div class="global-search-wrapper" id="global-search-wrapper">
        <form class="global-search" id="global-search-form" action="tracuu.php" method="GET" autocomplete="off">
            <span class="material-symbols-rounded search-icon">search</span>
            <input type="text" id="global-search-input" name="q" placeholder="<?php echo __('search_placeholder'); ?>" autocomplete="off" spellcheck="false">
            <button type="button" id="global-search-clear" class="search-clear-btn" title="Xóa tìm kiếm" style="display: none;">
                <span class="material-symbols-rounded">close</span>
            </button>
            <div class="search-kbd-badge" title="Phím tắt: Ctrl + K">
                <span id="search-shortcut-key">Ctrl K</span>
            </div>
        </form>

        <!-- Dropdown hiển thị kết quả tìm kiếm tức thì -->
        <div id="quick-search-dropdown" class="quick-search-dropdown" style="display: none;">
            <div id="quick-search-status" class="quick-search-status" style="display: none;"></div>
            <div id="quick-search-results" class="quick-search-results-list"></div>
            <div id="quick-search-footer" class="quick-search-footer" style="display: none;">
                <a href="tracuu.php" id="quick-search-view-all" class="quick-search-view-all">
                    <span class="material-symbols-rounded">manage_search</span>
                    <span>Xem tất cả kết quả trên trang Tra cứu đàn</span>
                </a>
                <div class="quick-search-shortcuts-hint">
                    <span><kbd>↑</kbd><kbd>↓</kbd> Chọn</span>
                    <span><kbd>↵</kbd> Mở</span>
                    <span><kbd>ESC</kbd> Đóng</span>
                </div>
            </div>
        </div>
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

<!-- Styles và Logic Tìm Kiếm Nhanh Toàn Hệ Thống -->
<style>
/* CSS Tìm kiếm nhanh đa năng */
.global-search-wrapper {
    position: relative;
    width: 360px;
    max-width: 100%;
}

.global-search {
    display: flex;
    align-items: center;
    background: var(--bg-tertiary, rgba(255, 255, 255, 0.05));
    border: 1px solid var(--border, rgba(255, 255, 255, 0.1));
    border-radius: var(--radius-full, 9999px);
    padding: 7px 14px;
    width: 100%;
    transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
    position: relative;
    gap: 6px;
}

.global-search:focus-within {
    border-color: var(--accent, #3b82f6);
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.2), 0 8px 24px rgba(0, 0, 0, 0.25);
    background: var(--bg-secondary, #1e293b);
}

.global-search .search-icon {
    color: var(--text-muted, #94a3b8);
    font-size: 20px;
    flex-shrink: 0;
    transition: color 0.2s;
}

.global-search:focus-within .search-icon {
    color: var(--accent, #3b82f6);
}

.global-search input {
    border: none;
    background: transparent;
    outline: none;
    flex: 1;
    font-size: 13.5px;
    color: var(--text-primary, #f8fafc);
    min-width: 0;
    font-family: inherit;
}

.global-search input::placeholder {
    color: var(--text-muted, #64748b);
    font-size: 13px;
}

.search-clear-btn {
    background: transparent;
    border: none;
    color: var(--text-muted, #94a3b8);
    cursor: pointer;
    padding: 2px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 50%;
    transition: all 0.2s;
    flex-shrink: 0;
}

.search-clear-btn:hover {
    background: rgba(255, 255, 255, 0.1);
    color: var(--text-primary, #f8fafc);
}

.search-clear-btn .material-symbols-rounded {
    font-size: 16px;
}

.search-kbd-badge {
    background: rgba(255, 255, 255, 0.08);
    border: 1px solid rgba(255, 255, 255, 0.12);
    border-radius: 6px;
    padding: 2px 6px;
    font-size: 10.5px;
    font-weight: 700;
    color: var(--text-muted, #94a3b8);
    letter-spacing: 0.5px;
    user-select: none;
    flex-shrink: 0;
    line-height: 1;
}

/* Quick Search Dropdown */
.quick-search-dropdown {
    position: absolute;
    top: calc(100% + 10px);
    left: 0;
    width: 480px;
    max-width: 92vw;
    background: var(--bg-card, #0f172a);
    backdrop-filter: blur(20px);
    -webkit-backdrop-filter: blur(20px);
    border: 1px solid var(--border, rgba(255, 255, 255, 0.15));
    border-radius: 16px;
    box-shadow: 0 20px 40px rgba(0, 0, 0, 0.5), 0 0 0 1px rgba(255, 255, 255, 0.05);
    z-index: 9999;
    overflow: hidden;
    animation: searchFadeIn 0.2s ease-out;
}

@keyframes searchFadeIn {
    from { opacity: 0; transform: translateY(-8px) scale(0.98); }
    to { opacity: 1; transform: translateY(0) scale(1); }
}

.quick-search-status {
    padding: 18px 20px;
    text-align: center;
    color: var(--text-muted, #94a3b8);
    font-size: 13.5px;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
}

.search-spinner {
    width: 18px;
    height: 18px;
    border: 2px solid rgba(255, 255, 255, 0.1);
    border-top-color: var(--accent, #3b82f6);
    border-radius: 50%;
    animation: spin 0.7s linear infinite;
}

@keyframes spin {
    to { transform: rotate(360deg); }
}

.quick-search-results-list {
    max-height: 440px;
    overflow-y: auto;
    padding: 8px;
    scrollbar-width: thin;
}

.quick-search-results-list::-webkit-scrollbar {
    width: 6px;
}
.quick-search-results-list::-webkit-scrollbar-thumb {
    background: rgba(255, 255, 255, 0.15);
    border-radius: 3px;
}

.qs-section {
    margin-bottom: 8px;
}
.qs-section:last-child {
    margin-bottom: 0;
}

.qs-section-title {
    font-size: 11px;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: 0.8px;
    color: var(--text-muted, #94a3b8);
    padding: 6px 10px 4px;
    display: flex;
    align-items: center;
    gap: 6px;
}

.qs-item {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 8px 12px;
    border-radius: 10px;
    color: var(--text-primary, #f8fafc);
    text-decoration: none;
    transition: all 0.15s ease;
    cursor: pointer;
    margin-bottom: 2px;
}

.qs-item:hover, .qs-item.qs-active {
    background: rgba(59, 130, 246, 0.12);
    outline: none;
}

.qs-item.qs-active {
    border-left: 3px solid var(--accent, #3b82f6);
}

.qs-item-thumb {
    width: 42px;
    height: 42px;
    border-radius: 8px;
    background: rgba(255, 255, 255, 0.05);
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    overflow: hidden;
    border: 1px solid rgba(255, 255, 255, 0.08);
}

.qs-item-thumb img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.qs-item-thumb .material-symbols-rounded {
    font-size: 22px;
    color: var(--accent, #3b82f6);
}

.qs-item-info {
    flex: 1;
    min-width: 0;
}

.qs-item-title {
    font-size: 13.5px;
    font-weight: 600;
    color: var(--text-primary, #f8fafc);
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    display: flex;
    align-items: center;
    gap: 8px;
}

.qs-item-subtitle {
    font-size: 12px;
    color: var(--text-secondary, #94a3b8);
    margin-top: 2px;
    display: flex;
    align-items: center;
    gap: 8px;
    flex-wrap: wrap;
}

.qs-badge {
    font-size: 10.5px;
    font-weight: 700;
    padding: 2px 7px;
    border-radius: 6px;
    letter-spacing: 0.3px;
    display: inline-flex;
    align-items: center;
}

.qs-badge-in-stock { background: rgba(16, 185, 129, 0.15); color: #34d399; }
.qs-badge-out-stock { background: rgba(239, 68, 68, 0.15); color: #f87171; }
.qs-badge-sold { background: rgba(148, 163, 184, 0.15); color: #94a3b8; }
.qs-badge-pending { background: rgba(245, 158, 11, 0.15); color: #fbbf24; }
.qs-badge-done { background: rgba(59, 130, 246, 0.15); color: #60a5fa; }
.qs-badge-brand { background: rgba(124, 92, 252, 0.15); color: #a78bfa; }

.qs-highlight {
    color: #38bdf8;
    background: rgba(56, 189, 248, 0.15);
    padding: 0 2px;
    border-radius: 3px;
    font-weight: 700;
}

.qs-empty-state {
    padding: 24px 20px;
    text-align: center;
    color: var(--text-muted, #94a3b8);
}
.qs-empty-icon {
    font-size: 36px;
    color: rgba(255, 255, 255, 0.2);
    margin-bottom: 8px;
}

.quick-search-footer {
    padding: 10px 14px;
    background: rgba(0, 0, 0, 0.2);
    border-top: 1px solid var(--border, rgba(255, 255, 255, 0.08));
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 10px;
    font-size: 12px;
}

.quick-search-view-all {
    color: var(--accent, #3b82f6);
    text-decoration: none;
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 6px;
    transition: 0.2s;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
.quick-search-view-all:hover {
    color: #60a5fa;
    text-decoration: underline;
}

.quick-search-shortcuts-hint {
    display: flex;
    align-items: center;
    gap: 8px;
    color: var(--text-muted, #64748b);
    font-size: 11px;
    flex-shrink: 0;
}
.quick-search-shortcuts-hint kbd {
    background: rgba(255, 255, 255, 0.08);
    border: 1px solid rgba(255, 255, 255, 0.12);
    padding: 1px 5px;
    border-radius: 4px;
    font-family: inherit;
    font-size: 10px;
}

@media (max-width: 768px) {
    .global-search-wrapper {
        width: 100%;
        order: 10;
        margin-top: 4px;
    }
    .quick-search-dropdown {
        width: 100%;
        max-width: 100%;
        left: 0;
        right: 0;
    }
    .quick-search-shortcuts-hint {
        display: none;
    }
}
</style>

<script>
(function() {
    // Check OS for shortcut badge
    var isMac = navigator.platform.toUpperCase().indexOf('MAC') >= 0;
    var kbdSpan = document.getElementById('search-shortcut-key');
    if (kbdSpan && isMac) {
        kbdSpan.textContent = '⌘ K';
    }

    var searchInput = document.getElementById('global-search-input');
    var searchForm = document.getElementById('global-search-form');
    var clearBtn = document.getElementById('global-search-clear');
    var dropdown = document.getElementById('quick-search-dropdown');
    var statusBox = document.getElementById('quick-search-status');
    var resultsBox = document.getElementById('quick-search-results');
    var footerBox = document.getElementById('quick-search-footer');
    var viewAllLink = document.getElementById('quick-search-view-all');

    if (!searchInput || !dropdown) return;

    var debounceTimer = null;
    var abortCtrl = null;
    var currentSelectedIndex = -1;

    // Format currency helper
    function formatMoney(amount) {
        if (!amount || amount <= 0) return '0 đ';
        return new Intl.NumberFormat('vi-VN').format(amount) + ' đ';
    }

    // Escape regex
    function escapeRegExp(string) {
        return string.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
    }

    // Highlight search term
    function highlightText(text, query) {
        if (!query || !text) return text || '';
        var safeQuery = escapeRegExp(query.trim());
        var regex = new RegExp('(' + safeQuery + ')', 'gi');
        return String(text).replace(regex, '<span class="qs-highlight">$1</span>');
    }

    // Perform Search Fetch
    function executeSearch(query) {
        if (abortCtrl) {
            abortCtrl.abort();
        }
        abortCtrl = new AbortController();

        var q = query.trim();
        if (q.length === 0) {
            dropdown.style.display = 'none';
            resultsBox.innerHTML = '';
            footerBox.style.display = 'none';
            return;
        }

        dropdown.style.display = 'block';
        statusBox.style.display = 'flex';
        statusBox.innerHTML = '<div class="search-spinner"></div><span>Đang tìm kiếm cho "' + escapeHtml(q) + '"...</span>';
        resultsBox.innerHTML = '';
        footerBox.style.display = 'none';
        currentSelectedIndex = -1;

        fetch('api_quick_search.php?q=' + encodeURIComponent(q), {
            signal: abortCtrl.signal
        })
        .then(function(res) { return res.json(); })
        .then(function(data) {
            statusBox.style.display = 'none';
            if (!data.success) {
                statusBox.style.display = 'block';
                statusBox.innerHTML = '<span>Lỗi: ' + (data.message || 'Không thể tìm kiếm') + '</span>';
                return;
            }

            renderResults(data, q);
        })
        .catch(function(err) {
            if (err.name === 'AbortError') return;
            statusBox.style.display = 'block';
            statusBox.innerHTML = '<span>Không thể tải kết quả. Vui lòng thử lại.</span>';
        });
    }

    function escapeHtml(str) {
        if (!str) return '';
        var div = document.createElement('div');
        div.textContent = str;
        return div.innerHTML;
    }

    // Render results
    function renderResults(data, q) {
        var res = data.results;
        var html = '';
        var hasAny = false;

        // 1. Models / Pianos
        if (res.models && res.models.length > 0) {
            hasAny = true;
            html += '<div class="qs-section">';
            html += '<div class="qs-section-title"><span class="material-symbols-rounded" style="font-size:15px; color:#38bdf8;">piano</span> Sản phẩm & Mẫu đàn (' + res.models.length + ')</div>';
            res.models.forEach(function(m) {
                var stockBadge = m.stock > 0 
                    ? '<span class="qs-badge qs-badge-in-stock">Kho: ' + m.stock + '</span>'
                    : '<span class="qs-badge qs-badge-out-stock">Hết hàng</span>';
                var imgHtml = m.image 
                    ? '<img src="' + escapeHtml(m.image) + '" alt="">'
                    : '<span class="material-symbols-rounded">music_note</span>';
                
                html += '<a href="' + escapeHtml(m.url) + '" class="qs-item" data-qs-link="1">' +
                    '<div class="qs-item-thumb">' + imgHtml + '</div>' +
                    '<div class="qs-item-info">' +
                        '<div class="qs-item-title">' + highlightText(m.title, q) + ' ' + stockBadge + '</div>' +
                        '<div class="qs-item-subtitle">' +
                            '<span class="qs-badge qs-badge-brand">' + escapeHtml(m.brand) + '</span>' +
                            '<span>' + escapeHtml(m.category) + '</span>' +
                            (m.price > 0 ? '<span style="color:#10b981; font-weight:600; margin-left:auto;">' + formatMoney(m.price) + '</span>' : '') +
                        '</div>' +
                    '</div>' +
                '</a>';
            });
            html += '</div>';
        }

        // 2. Serials
        if (res.serials && res.serials.length > 0) {
            hasAny = true;
            html += '<div class="qs-section">';
            html += '<div class="qs-section-title"><span class="material-symbols-rounded" style="font-size:15px; color:#a78bfa;">qr_code_2</span> Số Serial (' + res.serials.length + ')</div>';
            res.serials.forEach(function(s) {
                var statusClass = s.status === 'Trong kho' ? 'qs-badge-in-stock' : (s.status === 'Đã bán' ? 'qs-badge-sold' : 'qs-badge-pending');
                html += '<a href="' + escapeHtml(s.url) + '" class="qs-item" data-qs-link="1">' +
                    '<div class="qs-item-thumb"><span class="material-symbols-rounded">barcode_reader</span></div>' +
                    '<div class="qs-item-info">' +
                        '<div class="qs-item-title"><code style="color:#38bdf8; font-family:monospace; font-size:13px;">' + highlightText(s.serial, q) + '</code> <span class="qs-badge ' + statusClass + '">' + escapeHtml(s.status) + '</span></div>' +
                        '<div class="qs-item-subtitle">' +
                            '<span>' + escapeHtml(s.model) + '</span>' +
                            '<span style="color:var(--text-muted); font-size:11px;">📍 ' + escapeHtml(s.warehouse) + '</span>' +
                            (s.price > 0 ? '<span style="color:#10b981; margin-left:auto;">' + formatMoney(s.price) + '</span>' : '') +
                        '</div>' +
                    '</div>' +
                '</a>';
            });
            html += '</div>';
        }

        // 3. Invoices
        if (res.invoices && res.invoices.length > 0) {
            hasAny = true;
            html += '<div class="qs-section">';
            html += '<div class="qs-section-title"><span class="material-symbols-rounded" style="font-size:15px; color:#10b981;">receipt_long</span> Hóa đơn bán hàng (' + res.invoices.length + ')</div>';
            res.invoices.forEach(function(inv) {
                html += '<a href="' + escapeHtml(inv.url) + '" class="qs-item" data-qs-link="1">' +
                    '<div class="qs-item-thumb" style="background:rgba(16,185,129,0.1);"><span class="material-symbols-rounded" style="color:#10b981;">receipt</span></div>' +
                    '<div class="qs-item-info">' +
                        '<div class="qs-item-title"><strong>' + highlightText(inv.code, q) + '</strong> - ' + highlightText(inv.customer, q) + ' <span class="qs-badge qs-badge-done">' + escapeHtml(inv.status) + '</span></div>' +
                        '<div class="qs-item-subtitle">' +
                            '<span>' + escapeHtml(inv.phone) + '</span>' +
                            '<span>' + escapeHtml(inv.date) + '</span>' +
                            '<span style="color:#10b981; font-weight:700; margin-left:auto;">' + formatMoney(inv.total) + '</span>' +
                        '</div>' +
                    '</div>' +
                '</a>';
            });
            html += '</div>';
        }

        // 4. Import & Export Slips
        var totalSlips = (res.import_slips ? res.import_slips.length : 0) + (res.export_slips ? res.export_slips.length : 0);
        if (totalSlips > 0) {
            hasAny = true;
            html += '<div class="qs-section">';
            html += '<div class="qs-section-title"><span class="material-symbols-rounded" style="font-size:15px; color:#f59e0b;">swap_horiz</span> Phiếu Nhập / Xuất kho (' + totalSlips + ')</div>';
            
            if (res.import_slips) {
                res.import_slips.forEach(function(pn) {
                    html += '<a href="' + escapeHtml(pn.url) + '" class="qs-item" data-qs-link="1">' +
                        '<div class="qs-item-thumb" style="background:rgba(245,158,11,0.1);"><span class="material-symbols-rounded" style="color:#f59e0b;">move_to_inbox</span></div>' +
                        '<div class="qs-item-info">' +
                            '<div class="qs-item-title"><strong>' + highlightText(pn.code, q) + '</strong> (Nhập kho) - ' + highlightText(pn.supplier, q) + ' <span class="qs-badge ' + (pn.status==='Hoàn thành'?'qs-badge-done':'qs-badge-pending') + '">' + escapeHtml(pn.status) + '</span></div>' +
                            '<div class="qs-item-subtitle">' +
                                '<span>Ngày: ' + escapeHtml(pn.date) + '</span>' +
                                (pn.total > 0 ? '<span style="color:#f59e0b; margin-left:auto;">' + formatMoney(pn.total) + '</span>' : '') +
                            '</div>' +
                        '</div>' +
                    '</a>';
                });
            }

            if (res.export_slips) {
                res.export_slips.forEach(function(px) {
                    html += '<a href="' + escapeHtml(px.url) + '" class="qs-item" data-qs-link="1">' +
                        '<div class="qs-item-thumb" style="background:rgba(239,68,68,0.1);"><span class="material-symbols-rounded" style="color:#f87171;">outbox</span></div>' +
                        '<div class="qs-item-info">' +
                            '<div class="qs-item-title"><strong>' + highlightText(px.code, q) + '</strong> (Xuất kho) - ' + highlightText(px.recipient, q) + ' <span class="qs-badge ' + (px.status==='Hoàn thành'?'qs-badge-done':'qs-badge-pending') + '">' + escapeHtml(px.status) + '</span></div>' +
                            '<div class="qs-item-subtitle">' +
                                '<span>' + escapeHtml(px.reason) + '</span>' +
                                '<span style="margin-left:auto;">' + escapeHtml(px.date) + '</span>' +
                            '</div>' +
                        '</div>' +
                    '</a>';
                });
            }
            html += '</div>';
        }

        // 5. Customers
        if (res.customers && res.customers.length > 0) {
            hasAny = true;
            html += '<div class="qs-section">';
            html += '<div class="qs-section-title"><span class="material-symbols-rounded" style="font-size:15px; color:#ec4899;">person</span> Khách hàng (' + res.customers.length + ')</div>';
            res.customers.forEach(function(c) {
                html += '<a href="' + escapeHtml(c.url) + '" class="qs-item" data-qs-link="1">' +
                    '<div class="qs-item-thumb" style="background:rgba(236,72,153,0.1);"><span class="material-symbols-rounded" style="color:#ec4899;">person</span></div>' +
                    '<div class="qs-item-info">' +
                        '<div class="qs-item-title"><strong>' + highlightText(c.name, q) + '</strong></div>' +
                        '<div class="qs-item-subtitle">' +
                            '<span>📞 ' + highlightText(c.phone, q) + '</span>' +
                            (c.address ? '<span style="margin-left:auto; color:var(--text-muted);">' + escapeHtml(c.address) + '</span>' : '') +
                        '</div>' +
                    '</div>' +
                '</a>';
            });
            html += '</div>';
        }

        if (!hasAny) {
            html = '<div class="qs-empty-state">' +
                '<span class="material-symbols-rounded qs-empty-icon">search_off</span>' +
                '<div style="font-size:14px; font-weight:600; color:var(--text-primary);">Không tìm thấy kết quả</div>' +
                '<div style="font-size:12px; margin-top:4px;">Không có đàn, số serial, hóa đơn hay khách hàng nào khớp với "<b>' + escapeHtml(q) + '</b>"</div>' +
            '</div>';
        }

        resultsBox.innerHTML = html;
        viewAllLink.href = 'tracuu.php?q=' + encodeURIComponent(q);
        viewAllLink.querySelector('span:last-child').innerHTML = 'Xem tất cả kết quả cho "<b>' + escapeHtml(q) + '</b>" trên Tra cứu';
        footerBox.style.display = 'flex';
    }

    // Input events
    searchInput.addEventListener('input', function() {
        var val = this.value;
        if (val.trim().length > 0) {
            clearBtn.style.display = 'flex';
        } else {
            clearBtn.style.display = 'none';
        }

        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(function() {
            executeSearch(val);
        }, 200);
    });

    searchInput.addEventListener('focus', function() {
        if (this.value.trim().length > 0) {
            dropdown.style.display = 'block';
            if (resultsBox.children.length === 0) {
                executeSearch(this.value);
            }
        }
    });

    // Clear button
    clearBtn.addEventListener('click', function() {
        searchInput.value = '';
        clearBtn.style.display = 'none';
        dropdown.style.display = 'none';
        resultsBox.innerHTML = '';
        searchInput.focus();
    });

    // Keyboard navigation (Arrow up, Arrow down, Enter, ESC)
    searchInput.addEventListener('keydown', function(e) {
        var items = resultsBox.querySelectorAll('.qs-item');
        if (e.key === 'ArrowDown') {
            e.preventDefault();
            if (items.length > 0) {
                currentSelectedIndex++;
                if (currentSelectedIndex >= items.length) currentSelectedIndex = 0;
                updateActiveItem(items);
            }
        } else if (e.key === 'ArrowUp') {
            e.preventDefault();
            if (items.length > 0) {
                currentSelectedIndex--;
                if (currentSelectedIndex < 0) currentSelectedIndex = items.length - 1;
                updateActiveItem(items);
            }
        } else if (e.key === 'Enter') {
            if (currentSelectedIndex >= 0 && items[currentSelectedIndex]) {
                e.preventDefault();
                items[currentSelectedIndex].click();
            }
        } else if (e.key === 'Escape') {
            dropdown.style.display = 'none';
            searchInput.blur();
        }
    });

    function updateActiveItem(items) {
        items.forEach(function(el, i) {
            if (i === currentSelectedIndex) {
                el.classList.add('qs-active');
                el.scrollIntoView({ block: 'nearest' });
            } else {
                el.classList.remove('qs-active');
            }
        });
    }

    // Global shortcut Ctrl+K / Cmd+K or "/"
    document.addEventListener('keydown', function(e) {
        if ((e.ctrlKey || e.metaKey) && (e.key === 'k' || e.key === 'K')) {
            e.preventDefault();
            searchInput.focus();
            searchInput.select();
        } else if (e.key === '/' && document.activeElement.tagName !== 'INPUT' && document.activeElement.tagName !== 'TEXTAREA') {
            e.preventDefault();
            searchInput.focus();
            searchInput.select();
        }
    });

    // Click outside to close
    document.addEventListener('click', function(e) {
        var wrapper = document.getElementById('global-search-wrapper');
        if (wrapper && !wrapper.contains(e.target)) {
            dropdown.style.display = 'none';
        }
    });
})();
</script>