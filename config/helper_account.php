<?php

/**
 * Đăng nhập: hỗ trợ mật khẩu đã hash (password_hash) và tự nâng cấp
 * các tài khoản cũ còn lưu plaintext sang bcrypt ngay lần đăng nhập đầu.
 */
function login($username, $password)
{
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM `users` WHERE `username` = :username");
    $stmt->bindParam(':username', $username);
    $stmt->execute();
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        return false;
    }

    $stored = (string)$user['password_hash'];
    $valid = false;
    $needsRehash = false;

    if (password_get_info($stored)['algo']) {
        // Mật khẩu đã hash chuẩn
        $valid = password_verify($password, $stored);
        $needsRehash = $valid && password_needs_rehash($stored, PASSWORD_DEFAULT);
    } else {
        // Dữ liệu cũ: plaintext — so sánh an toàn rồi nâng cấp lên hash
        $valid = hash_equals($stored, $password);
        $needsRehash = $valid;
    }

    if (!$valid) {
        return false;
    }

    if ($needsRehash) {
        $newHash = password_hash($password, PASSWORD_DEFAULT);
        $upd = $pdo->prepare("UPDATE `users` SET `password_hash` = :hash WHERE `user_id` = :id");
        $upd->execute([':hash' => $newHash, ':id' => $user['user_id']]);
        $user['password_hash'] = $newHash;
    }

    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    // Chống session fixation: cấp session ID mới sau khi xác thực thành công
    session_regenerate_id(true);

    // Không lưu credential vào session
    unset($user['password_hash']);

    $_SESSION['user'] = $user;
    $_SESSION['user_id'] = $user['user_id'];
    $_SESSION['role'] = $user['role'];
    return true;
}

function is_login()
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    return isset($_SESSION['user']) && !empty($_SESSION['user']);
}
