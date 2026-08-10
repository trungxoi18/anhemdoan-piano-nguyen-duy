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
});
</script>
</body>
</html>