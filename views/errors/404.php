<?php
$errorCode = '404';
$errorTitle = 'Không tìm thấy trang';
$errorMessage = 'Trang bạn tìm không tồn tại hoặc đã được chuyển đi.<br>Đường dẫn có thể bị sai hoặc trang đã bị xoá.';
$errorIcon = '🔍';
$errorClass = 'error-page--404';
require __DIR__ . '/../templates/error_page.php';
