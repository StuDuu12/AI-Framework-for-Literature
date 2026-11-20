<?php
session_start();
require_once __DIR__ . '/../../config/env.php';
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../config/security.php';

if (isset($_SESSION['user']) && !empty($_SESSION['user'])) {
    header('Location: ' . URL_ROOT . '/dashboard');
    exit;
}

$message = '';
$msgType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify()) {
        $message = 'Phiên hết hạn. Vui lòng tải lại trang.';
        $msgType = 'error';
    } else {
        $email = trim($_POST['email'] ?? '');
        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $message = 'Vui lòng nhập email hợp lệ.';
            $msgType = 'error';
        } else {
            try {
                $stmt = $pdo->prepare("SELECT user_id, fullname FROM users WHERE email = :email");
                $stmt->execute([':email' => $email]);
                $user = $stmt->fetch(PDO::FETCH_ASSOC);

                if ($user) {
                    $token = bin2hex(random_bytes(32));
                    $expires = date('Y-m-d H:i:s', strtotime('+15 minutes'));

                    $stmt = $pdo->prepare("DELETE FROM password_resets WHERE user_id = :uid");
                    $stmt->execute([':uid' => $user['user_id']]);

                    $stmt = $pdo->prepare("INSERT INTO password_resets (user_id, token, expires_at) VALUES (:uid, :token, :expires)");
                    // Chỉ lưu hash của token — token gốc chỉ nằm trong link gửi qua email
                    $stmt->execute([':uid' => $user['user_id'], ':token' => hash('sha256', $token), ':expires' => $expires]);

                    $resetUrl = URL_ROOT . "/reset_password?token=" . $token;

                    // Tuyệt đối không trả link reset về trình duyệt (chống chiếm tài khoản).
                    // Chưa cấu hình mailer => link chỉ được gửi khi có sendPasswordResetEmail().
                    if (function_exists('sendPasswordResetEmail')) {
                        sendPasswordResetEmail($user['fullname'], $email, $resetUrl);
                    } else {
                        error_log('Forgot password: chưa cấu hình sendPasswordResetEmail(), không gửi được email đặt lại mật khẩu.');
                    }
                }

                // Cùng một thông báo cho mọi trường hợp để không lộ email nào tồn tại
                $message = 'Nếu email tồn tại, hướng dẫn đặt lại mật khẩu đã được gửi.';
                $msgType = 'success';
            } catch (PDOException $e) {
                error_log("Forgot password error: " . $e->getMessage());
                $message = 'Lỗi hệ thống. Vui lòng thử lại.';
                $msgType = 'error';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="utf-8">
    <meta content="width=device-width, initial-scale=1.0" name="viewport">
    <title>Quên mật khẩu - Literature System</title>
    <?php require_once __DIR__ . '/../templates/assets.php'; ?>
    <style>
        .forgot-page { background: #f0f2f5; min-height: 100vh; }
        .forgot-page main { display: flex; align-items: center; justify-content: center; min-height: 100vh; padding: 2rem 1rem; }
        .forgot-card-wrap { width: 100%; max-width: 420px; }
        .forgot-card-wrap .card { border: none; border-radius: 16px; box-shadow: 0 4px 24px rgba(0,0,0,0.08); }
        .forgot-card-wrap .card-body { padding: 2.5rem 2rem; }
        .forgot-brand { display: flex; align-items: center; gap: 0.5rem; justify-content: center; margin-bottom: 1.5rem; text-decoration: none; color: #012970; font-weight: 700; }
        .forgot-brand img { width: 32px; }
        .forgot-title { font-size: 1.25rem; font-weight: 700; color: #012970; text-align: center; margin-bottom: 0.25rem; }
        .forgot-sub { text-align: center; color: #6c757d; font-size: 0.85rem; margin-bottom: 1.5rem; }
        .form-floating { margin-bottom: 1rem; }
        .form-floating .form-control { border-radius: 10px; border: 1.5px solid #e3e6f0; padding: 1rem 0.75rem 0.5rem; height: 48px; }
        .form-floating .form-control:focus { border-color: #4154f1; box-shadow: 0 0 0 3px rgba(65,84,241,0.1); }
        .form-floating label { padding: 0.75rem 0.75rem 0; font-size: 0.8rem; color: #6c757d; }
        .btn-forgot { background: #4154f1; border: none; border-radius: 10px; padding: 0.65rem; font-weight: 600; font-size: 0.95rem; width: 100%; }
        .btn-forgot:hover { background: #3445d1; }
        .forgot-footer { text-align: center; margin-top: 1.25rem; font-size: 0.85rem; color: #6c757d; }
        .forgot-footer a { color: #4154f1; text-decoration: none; font-weight: 600; }
        .alert { border-radius: 10px; font-size: 0.85rem; padding: 0.75rem 1rem; }
    </style>
</head>

<body class="login-page">
    <main>
        <div class="forgot-card-wrap">
            <div class="card">
                <div class="card-body">
                    <a href="<?= URL_ROOT ?>/" class="forgot-brand">
                        <img src="<?= URL_ROOT ?>/assets/img/fit.png" alt="Logo">
                        <span>Literature System</span>
                    </a>
                    <h3 class="forgot-title">Quên mật khẩu?</h3>
                    <p class="forgot-sub">Nhập email đã đăng ký để nhận hướng dẫn đặt lại mật khẩu</p>

                    <?php if ($message): ?>
                        <div class="alert alert-<?= $msgType === 'error' ? 'danger' : ($msgType === 'success' ? 'success' : 'info') ?>">
                            <i class="bi bi-<?= $msgType === 'error' ? 'exclamation-circle' : 'check-circle' ?> me-1"></i>
                            <?= htmlspecialchars($message) ?>
                        </div>
                    <?php endif; ?>

                    <?php if ($msgType !== 'success'): ?>
                    <form method="POST" action="">
                        <?= csrf_field() ?>
                        <div class="form-floating">
                            <input type="email" class="form-control" id="email" name="email" placeholder="Email" required
                                value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
                            <label for="email">Email đã đăng ký</label>
                        </div>
                        <button type="submit" class="btn btn-forgot">
                            <i class="bi bi-send me-1"></i> Gửi hướng dẫn
                        </button>
                    </form>
                    <?php endif; ?>

                    <div class="forgot-footer">
                        <a href="<?= URL_ROOT ?>/login"><i class="bi bi-arrow-left me-1"></i>Quay lại đăng nhập</a>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <?php require_once __DIR__ . '/../templates/vendors.php'; ?>
</body>

</html>
