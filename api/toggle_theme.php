<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Nhận theme mong muốn {"theme":"dark"|"light"}; không gửi thì đảo theme hiện tại
$input = json_decode((string)file_get_contents('php://input'), true);
$wanted = is_array($input) ? ($input['theme'] ?? null) : null;
$current = $_SESSION['theme'] ?? 'light';
$new_theme = in_array($wanted, ['light', 'dark'], true) ? $wanted : ($current === 'light' ? 'dark' : 'light');

$_SESSION['theme'] = $new_theme;

header('Content-Type: application/json');
echo json_encode([
    'success' => true,
    'theme' => $new_theme
]);
