<?php
$errorCode = '403';
$errorTitle = 'Không có quyền truy cập';
$errorMessage = 'Bạn không có quyền xem trang này.<br>Nếu bạn nghĩ đây là nhầm lẫn, hãy liên hệ thầy cô hoặc quản trị viên.';
$errorIcon = '🔒';
$errorClass = 'error-page--403';
require __DIR__ . '/../templates/error_page.php';
