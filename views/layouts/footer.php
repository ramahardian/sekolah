</main>
</div>
<script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>

<script>
    // Contoh fungsi untuk konfirmasi hapus global
    function confirmDelete(url) {
        if (confirm('Apakah Anda yakin ingin menghapus data ini? Tindakan ini tidak dapat dibatalkan.')) {
            window.location.href = url;
        }
    }

    // Mobile sidebar toggle
    (function () {
        const button = document.getElementById('mobileMenuButton');
        const sidebar = document.getElementById('sidebar');
        const overlay = document.getElementById('mobileMenuOverlay');
        if (!button || !sidebar || !overlay) return;

        function openMenu() {
            sidebar.classList.remove('-translate-x-full');
            overlay.classList.remove('hidden');
        }

        function closeMenu() {
            sidebar.classList.add('-translate-x-full');
            overlay.classList.add('hidden');
        }

        button.addEventListener('click', () => {
            if (sidebar.classList.contains('-translate-x-full')) {
                openMenu();
            } else {
                closeMenu();
            }
        });

        overlay.addEventListener('click', closeMenu);
        window.addEventListener('resize', () => {
            if (window.innerWidth >= 768) {
                closeMenu();
            }
        });
    })();

    // Auto-hide alert setelah 3 detik
    setTimeout(() => {
        const alerts = document.querySelectorAll('.alert-box');
        alerts.forEach(alert => {
            alert.style.display = 'none';
        });
    }, 3000);
</script>

<?php if (isset($extra_js))
    echo $extra_js; ?>

</body>

</html>
