<?php 
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$role = isset($_SESSION['user']['role']) ? $_SESSION['user']['role'] : null;

switch ($role) {
    case 'admin':
        require_once 'views/admin/dashboard.php';
        break;
    case 'teacher':
    case 'student':
        require_once 'views/index.php';
        break;
    default:
        exit();
}
