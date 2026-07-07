<?php 

$role = isset($_SESSION['user']['role']) ? $_SESSION['user']['role'] : '';
switch ($role) {
    case 'admin':
        require_once __DIR__ . '/../templates/sidebar_admin.php';
        break;
    case 'teacher':
        require_once __DIR__ . '/../templates/sidebar_teacher.php';
        break;
    case 'student':
        require_once __DIR__ . '/../templates/sidebar_student.php';
        break;
    default:
        exit();
        // break;
}