<?php
$errorCode = '404';
$errorTitle = 'Page Not Found';
$errorMessage = 'Sorry! The page you are looking for does not exist or has been moved.<br>The URL may be incorrect or the page may have been deleted.';
$errorIcon = '🔍';
$errorClass = 'error-page--404';
require __DIR__ . '/../templates/error_page.php';
