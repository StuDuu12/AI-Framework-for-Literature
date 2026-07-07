<?php
$errorCode = '400';
$errorTitle = 'Bad Request';
$errorMessage = 'The request could not be understood by the server.<br>Please check the URL or form data and try again.';
$errorIcon = '⚠️';
$errorClass = 'error-page--400';
require __DIR__ . '/../templates/error_page.php';
