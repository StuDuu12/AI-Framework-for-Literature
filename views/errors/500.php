<?php
$errorCode = '500';
$errorTitle = 'Internal Server Error';
$errorMessage = 'Something went wrong on our end.<br>Please try again later or contact support if the problem persists.';
$errorIcon = '💥';
$errorClass = 'error-page--500';
require __DIR__ . '/../templates/error_page.php';
