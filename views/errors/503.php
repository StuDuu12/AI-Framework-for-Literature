<?php
$errorCode = '503';
$errorTitle = 'Service Unavailable';
$errorMessage = 'The service is temporarily unavailable.<br>We are working to restore it. Please try again in a few minutes.';
$errorIcon = '🔧';
$errorClass = 'error-page--503';
require __DIR__ . '/../templates/error_page.php';
