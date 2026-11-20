<?php
/**
 * Shared error page layout.
 * Variables: $errorCode, $errorTitle, $errorMessage, $errorIcon, $errorClass (e.g. error-page--404)
 */
if (!defined('URL_ROOT')) {
    require_once __DIR__ . '/../../config/env.php';
}
$errorCode = $errorCode ?? '404';
$errorTitle = $errorTitle ?? 'Page Not Found';
$errorMessage = $errorMessage ?? 'The page you requested could not be found.';
$errorIcon = $errorIcon ?? '🔍';
$errorClass = $errorClass ?? 'error-page--404';
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($errorCode) ?> — Literature System</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&display=swap" rel="stylesheet">
    <link href="<?= URL_ROOT ?>/assets/css/error-pages.css" rel="stylesheet">
</head>
<body class="error-page <?= htmlspecialchars($errorClass) ?>">
    <div class="error-page__inner">
        <div class="error-page__icon"><?= $errorIcon ?></div>
        <div class="error-page__code"><?= htmlspecialchars($errorCode) ?></div>
        <h1 class="error-page__title" data-lang-key="error_<?= htmlspecialchars($errorCode) ?>_heading"><?= htmlspecialchars($errorTitle) ?></h1>
        <p class="error-page__message" data-lang-key="error_<?= htmlspecialchars($errorCode) ?>_message" data-lang-attr="html"><?= $errorMessage ?></p>
        <div class="error-page__actions">
            <a href="<?= URL_ROOT ?>/" class="error-page__btn error-page__btn--primary" data-lang-key="back_home">Về trang chủ</a>
            <a href="<?= URL_ROOT ?>/dashboard" class="error-page__btn error-page__btn--secondary" data-lang-key="menu_dashboard">Tổng quan</a>
        </div>
        <p class="error-page__meta" data-lang-key="error_<?= htmlspecialchars($errorCode) ?>_code">Mã lỗi: <?= htmlspecialchars($errorCode) ?></p>
    </div>
    <script src="<?= URL_ROOT ?>/assets/js/translations.js"></script>
    <script src="<?= URL_ROOT ?>/assets/js/language-switcher.js"></script>
</body>
</html>
