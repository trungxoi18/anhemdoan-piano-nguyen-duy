<script>
// Scroll-triggered fade-in animation for cards
document.addEventListener('DOMContentLoaded', function() {
    const cards = document.querySelectorAll('.action-card, .kpi-card, .form-card, .product-card');
    
    const observer = new IntersectionObserver((entries) => {
        entries.forEach((entry, index) => {
            if (entry.isIntersecting) {
                entry.target.style.animationDelay = (index * 0.06) + 's';
                entry.target.classList.add('animate-in');
                observer.unobserve(entry.target);
            }
        });
    }, { threshold: 0.1 });

    cards.forEach(card => observer.observe(card));

    // Active sidebar nav highlighting
    const currentPage = window.location.pathname.split('/').pop() || 'index.php';
    document.querySelectorAll('.nav-item').forEach(item => {
        item.classList.remove('active');
        if (item.getAttribute('href') === currentPage) {
            item.classList.add('active');
        }
    });

    // Inject Modern Footer
    const contentArea = document.querySelector('.content');
    if (contentArea && !document.querySelector('.site-footer')) {
        const footerHTML = `
            <footer class="site-footer">
                <div class="footer-left">
                    <div class="copyright">&copy; ${new Date().getFullYear()} Kho Đàn Piano Nguyễn Duy. All rights reserved.</div>
                    <div class="footer-address">
                        <span class="material-symbols-rounded" style="font-size: 14px; vertical-align: middle; margin-right: 4px;">location_on</span>
                        Địa chỉ: Kho Đàn Nguyễn Duy
                    </div>
                </div>
                <div class="footer-links">
                    <div class="footer-support-container">
                        <a id="btn-support" style="display: flex; align-items: center; gap: 4px;">
                            <span class="material-symbols-rounded" style="font-size: 18px;">support_agent</span>
                            Hỗ trợ
                        </a>
                        <div id="support-dropdown" class="footer-support-dropdown">
                            <a href="tel:0915738381" class="footer-support-item">
                                <span class="material-symbols-rounded" style="color: var(--success);">call</span>
                                <div>
                                    <div style="font-size: 12px; color: var(--text-muted);">Hotline</div>
                                    <div style="font-weight: 600;">0915 738 381</div>
                                </div>
                            </a>
                            <a href="https://www.facebook.com/ndtxun2803" target="_blank" class="footer-support-item">
                                <span class="material-symbols-rounded" style="color: #3b5998;">facebook</span>
                                <div>
                                    <div style="font-size: 12px; color: var(--text-muted);">Facebook</div>
                                    <div style="font-weight: 600;">Nguyễn Đức Trung</div>
                                </div>
                            </a>
                        </div>
                    </div>
                    <a href="#">Chính sách</a>
                    <span class="version">v2.1.0</span>
                </div>
            </footer>
        `;
        contentArea.insertAdjacentHTML('beforeend', footerHTML);

        // Support Dropdown Toggle Logic
        const btnSupport = document.getElementById('btn-support');
        const supportDropdown = document.getElementById('support-dropdown');
        if (btnSupport && supportDropdown) {
            btnSupport.addEventListener('click', (e) => {
                e.stopPropagation();
                supportDropdown.classList.toggle('show');
            });
            document.addEventListener('click', (e) => {
                if (!supportDropdown.contains(e.target) && e.target !== btnSupport) {
                    supportDropdown.classList.remove('show');
                }
            });
        }
    }

    // Sidebar state restoration
    const sidebar = document.querySelector('.sidebar');
    if (sidebar && window.innerWidth > 992) {
        if (localStorage.getItem('sidebar_collapsed') === 'true') {
            sidebar.classList.add('collapsed');
        }
    }
});

// Toggle Sidebar function
function toggleSidebar() {
    const sidebar = document.querySelector('.sidebar');
    const overlay = document.getElementById('sidebar-overlay');
    if (!sidebar) return;

    if (window.innerWidth <= 992) {
        sidebar.classList.toggle('mobile-open');
        if (overlay) overlay.classList.toggle('active');
    } else {
        sidebar.classList.toggle('collapsed');
        localStorage.setItem('sidebar_collapsed', sidebar.classList.contains('collapsed'));
    }
}
</script>
<div id="sidebar-overlay" class="sidebar-overlay" onclick="toggleSidebar()"></div>
<?php include_once __DIR__ . '/../chat_widget.php'; ?>
</body>
</html>