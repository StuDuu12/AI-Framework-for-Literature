<!-- Đổi giao diện sáng/tối. Class dark-theme đã được gắn sẵn lên <html> trong assets.php
     và lên <body> trong header.php, nên ở đây chỉ cập nhật icon và xử lý nút bấm. -->
<script>
    function applyTheme(theme) {
        var dark = theme === 'dark';
        document.documentElement.classList.toggle('dark-theme', dark);
        document.documentElement.setAttribute('data-bs-theme', dark ? 'dark' : 'light');
        if (document.body) document.body.classList.toggle('dark-theme', dark);
        updateThemeIcon(dark ? 'dark' : 'light');
    }

    function toggleTheme() {
        var next = document.documentElement.classList.contains('dark-theme') ? 'light' : 'dark';
        applyTheme(next); // đổi ngay, không chờ server
        fetch('<?= URL_ROOT ?>/api/toggle_theme.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ theme: next })
        })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                if (data && data.success && data.theme !== next) applyTheme(data.theme);
            })
            .catch(function (err) { console.error('Không lưu được lựa chọn giao diện:', err); });
    }

    function updateThemeIcon(theme) {
        var icon = document.getElementById('theme-icon');
        if (!icon) return;
        icon.className = theme === 'dark' ? 'bi bi-sun-fill' : 'bi bi-moon-fill';
        icon.style.color = theme === 'dark' ? 'var(--lit-accent)' : 'var(--lit-text-secondary)';
    }

    document.addEventListener('DOMContentLoaded', function () {
        applyTheme(document.documentElement.classList.contains('dark-theme') ? 'dark' : 'light');
    });
</script>
