<!-- Favicons -->
<link href="<?= URL_ROOT ?>/assets/img/fit.png" rel="icon">
<link href="<?= URL_ROOT ?>/assets/img/fit.png" rel="apple-touch-icon">

<!-- CSRF token cho mọi request POST (fetch/XHR tự gắn header qua csrf.js) -->
<meta name="csrf-token" content="<?= htmlspecialchars(function_exists('csrf_token') ? csrf_token() : '') ?>">
<meta name="url-root" content="<?= URL_ROOT ?>">
<script src="<?= URL_ROOT ?>/assets/js/csrf.js"></script>

<!-- Google Fonts — Inter (body) + Playfair Display (headings) + Roboto (fallback) -->
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=Playfair+Display:wght@400;500;600;700&family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">

<!-- Vendor CSS Files -->
<link href="<?= URL_ROOT ?>/assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
<link href="<?= URL_ROOT ?>/assets/vendor/bootstrap-icons/bootstrap-icons.css" rel="stylesheet">
<link href="<?= URL_ROOT ?>/assets/vendor/boxicons/css/boxicons.min.css" rel="stylesheet">
<link href="<?= URL_ROOT ?>/assets/vendor/quill/quill.snow.css" rel="stylesheet">
<link href="<?= URL_ROOT ?>/assets/vendor/quill/quill.bubble.css" rel="stylesheet">
<link href="<?= URL_ROOT ?>/assets/vendor/remixicon/remixicon.css" rel="stylesheet">
<link href="<?= URL_ROOT ?>/assets/vendor/simple-datatables/style.css" rel="stylesheet">

<!-- Consolidated App CSS (merged: style.css, typography, theme, pages, app-consistency) -->
<?php $appCssVer = @filemtime(PATH_ROOT . '/assets/css/app.css') ?: time(); ?>
<link href="<?= URL_ROOT ?>/assets/css/app.css?v=<?= $appCssVer ?>" rel="stylesheet">

<!-- Glassmorphism Design Tokens -->
<?php $glassCssVer = @filemtime(PATH_ROOT . '/assets/css/glass.css') ?: time(); ?>
<link href="<?= URL_ROOT ?>/assets/css/glass.css?v=<?= $glassCssVer ?>" rel="stylesheet">

<!-- Dark Theme -->
<?php $darkCssVer = @filemtime(PATH_ROOT . '/assets/css/dark.css') ?: time(); ?>
<link href="<?= URL_ROOT ?>/assets/css/dark.css?v=<?= $darkCssVer ?>" rel="stylesheet">

<!-- Animation Utilities (GSAP + CSS fallback) -->
<?php $animationsCssVer = @filemtime(PATH_ROOT . '/assets/css/animations.css') ?: time(); ?>
<link href="<?= URL_ROOT ?>/assets/css/animations.css?v=<?= $animationsCssVer ?>" rel="stylesheet">

<!-- Module-specific CSS (feature modules, NOT consolidated) -->
<?php $modulesCssVer = @filemtime(PATH_ROOT . '/assets/css/modules.css') ?: time(); ?>
<link href="<?= URL_ROOT ?>/assets/css/modules.css?v=<?= $modulesCssVer ?>" rel="stylesheet">

<?php require_once __DIR__ . '/theme-toggle.php'; ?>