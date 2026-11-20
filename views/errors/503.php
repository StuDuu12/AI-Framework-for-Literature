<?php
$errorCode = '503';
$errorTitle = 'Hệ thống tạm ngừng';
$errorMessage = 'Dịch vụ đang tạm ngừng để khắc phục.<br>Vui lòng thử lại sau vài phút.';
$errorIcon = '🔧';
$errorClass = 'error-page--503';
require __DIR__ . '/../templates/error_page.php';
