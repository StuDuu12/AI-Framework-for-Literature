<?php
$errorCode = '403';
$errorTitle = 'Access Denied';
$errorMessage = 'You do not have permission to access this page.<br>Please contact your teacher or administrator if you believe this is an error.';
$errorIcon = '🔒';
$errorClass = 'error-page--403';
require __DIR__ . '/../templates/error_page.php';
