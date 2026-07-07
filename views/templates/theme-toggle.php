<!-- Dark Theme CSS is loaded via assets.php (dark.css) -->

<!-- Theme Toggle Script -->
<script>
    // Initialize theme from PHP session
    document.addEventListener('DOMContentLoaded', function() {
        const currentTheme = '<?php echo isset($_SESSION['theme']) ? $_SESSION['theme'] : 'light'; ?>';
        if (currentTheme === 'dark') {
            document.body.classList.add('dark-theme');
            updateThemeIcon('dark');
        } else {
            updateThemeIcon('light');
        }
    });

    function toggleTheme() {
        fetch('<?= URL_ROOT ?>/api/toggle_theme.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    if (data.theme === 'dark') {
                        document.body.classList.add('dark-theme');
                        updateThemeIcon('dark');
                    } else {
                        document.body.classList.remove('dark-theme');
                        updateThemeIcon('light');
                    }
                }
            })
            .catch(error => {
                console.error('Error toggling theme:', error);
            });
    }

    function updateThemeIcon(theme) {
        const icon = document.getElementById('theme-icon');
        if (icon) {
            if (theme === 'dark') {
                icon.className = 'bi bi-sun-fill';
                icon.style.color = '#ffc107';
            } else {
                icon.className = 'bi bi-moon-fill';
                icon.style.color = '#6c757d';
            }
        }
    }
</script>