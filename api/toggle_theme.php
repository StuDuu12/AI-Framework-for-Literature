<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Lấy theme hiện tại, mặc định là 'light'
$current_theme = isset($_SESSION['theme']) ? $_SESSION['theme'] : 'light';

// Toggle theme
$new_theme = ($current_theme === 'light') ? 'dark' : 'light';

// Lưu vào session
$_SESSION['theme'] = $new_theme;

// Trả về theme mới
header('Content-Type: application/json');
echo json_encode([
    'success' => true,
    'theme' => $new_theme
]);
