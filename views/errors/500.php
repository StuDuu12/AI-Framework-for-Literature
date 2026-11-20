<?php
$errorCode = '500';
$errorTitle = 'Lỗi máy chủ';
$errorMessage = 'Hệ thống gặp sự cố.<br>Vui lòng thử lại sau ít phút; nếu vẫn lỗi, hãy báo quản trị viên.';
$errorIcon = '💥';
$errorClass = 'error-page--500';
require __DIR__ . '/../templates/error_page.php';
