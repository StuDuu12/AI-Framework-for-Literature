<?php

function login($username, $password)
{
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM `users` WHERE `username` = :username");
    $stmt->bindParam(':username', $username);
    $stmt->execute();
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && $password == $user['password_hash']) {
        // Đăng nhập thành công
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $_SESSION['user'] = $user;
        $_SESSION['user_id'] = $user['user_id'];
        $_SESSION['role'] = $user['role'];
        return true;
    } else {
        // Đăng nhập thất bại
        return false;
    }
}

function is_login()
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    return isset($_SESSION['user']) && !empty($_SESSION['user']);
}
