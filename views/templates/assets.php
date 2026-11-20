<?php
$__assetVer = static function (string $rel): int {
    return @filemtime(PATH_ROOT . $rel) ?: 1;
};
$__isDark = (($_SESSION['theme'] ?? 'light') === 'dark');
?>
<!-- Gắn theme lên <html> ngay khi parse <head> để không nháy nền sáng khi dùng chế độ tối -->
<script>
    (function (d) {
        var dark = <?= $__isDark ? 'true' : 'false' ?>;
        var html = d.documentElement;
        if (dark) html.classList.add('dark-theme');
        html.setAttribute('data-bs-theme', dark ? 'dark' : 'light');
        try {
            var l = localStorage.getItem('app_language') || 'vi';
            html.lang = l;
        } catch (e) { html.lang = 'vi'; }
    })(document);
</script>

<!-- Favicons -->
<link href="<?= URL_ROOT ?>/assets/img/fit.png" rel="icon">
<link href="<?= URL_ROOT ?>/assets/img/fit.png" rel="apple-touch-icon">

<!-- CSRF token cho mọi request POST (fetch/XHR tự gắn header qua csrf.js) -->
<meta name="csrf-token" content="<?= htmlspecialchars(function_exists('csrf_token') ? csrf_token() : '') ?>">
<meta name="url-root" content="<?= URL_ROOT ?>">
<?php $__flash = function_exists('flash_consume') ? flash_consume() : []; ?>
<?php if ($__flash): ?>
<meta name="app-flash" content="<?= htmlspecialchars(json_encode($__flash, JSON_UNESCAPED_UNICODE), ENT_QUOTES, 'UTF-8') ?>">
<?php endif; ?>
<script src="<?= URL_ROOT ?>/assets/js/csrf.js"></script>
<!-- Toast + hộp thoại xác nhận dùng chung (thay alert/confirm) -->
<script src="<?= URL_ROOT ?>/assets/js/ui.js?v=<?= $__assetVer('/assets/js/ui.js') ?>"></script>

<!-- Font: Inter (giao diện) · Playfair Display (tiêu đề) · Literata (đọc văn bản dài) -->
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=Literata:ital,wght@0,400;0,600;1,400&family=Playfair+Display:wght@600;700&display=swap" rel="stylesheet">

<!-- Vendor CSS (một bộ icon duy nhất: Bootstrap Icons) -->
<link href="<?= URL_ROOT ?>/assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
<link href="<?= URL_ROOT ?>/assets/vendor/bootstrap-icons/bootstrap-icons.css" rel="stylesheet">
<link href="<?= URL_ROOT ?>/assets/vendor/quill/quill.snow.css" rel="stylesheet">
<link href="<?= URL_ROOT ?>/assets/vendor/quill/quill.bubble.css" rel="stylesheet">
<link href="<?= URL_ROOT ?>/assets/vendor/simple-datatables/style.css" rel="stylesheet">

<!-- Design tokens: nguồn màu/chữ/khoảng cách duy nhất (sáng + tối) -->
<link href="<?= URL_ROOT ?>/assets/css/tokens.css?v=<?= $__assetVer('/assets/css/tokens.css') ?>" rel="stylesheet">

<!-- Layout & thành phần chung -->
<link href="<?= URL_ROOT ?>/assets/css/app.css?v=<?= $__assetVer('/assets/css/app.css') ?>" rel="stylesheet">
<link href="<?= URL_ROOT ?>/assets/css/glass.css?v=<?= $__assetVer('/assets/css/glass.css') ?>" rel="stylesheet">
<link href="<?= URL_ROOT ?>/assets/css/dark.css?v=<?= $__assetVer('/assets/css/dark.css') ?>" rel="stylesheet">
<link href="<?= URL_ROOT ?>/assets/css/animations.css?v=<?= $__assetVer('/assets/css/animations.css') ?>" rel="stylesheet">
<link href="<?= URL_ROOT ?>/assets/css/modules.css?v=<?= $__assetVer('/assets/css/modules.css') ?>" rel="stylesheet">
<link href="<?= URL_ROOT ?>/assets/css/ui.css?v=<?= $__assetVer('/assets/css/ui.css') ?>" rel="stylesheet">

<?php require_once __DIR__ . '/theme-toggle.php'; ?>
