<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../../config/env.php';
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../config/security.php';

$token = trim($_GET['token'] ?? '');

if ($token === '') {
    header('Location: ' . URL_ROOT . '/login');
    exit;
}

$validToken = false;
$userId = null;

try {
    $stmt = $pdo->prepare("SELECT user_id FROM password_resets WHERE token = :token AND used = 0 AND expires_at > NOW()");
    $stmt->execute([':token' => hash('sha256', $token)]); // DB chỉ lưu hash của token
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($row) {
        $validToken = true;
        $userId = $row['user_id'];
    }
} catch (PDOException $e) {
    error_log("Reset password token check error: " . $e->getMessage());
}

$message = '';
$msgType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $validToken) {
    if (!csrf_verify()) {
        $message = 'Phiên hết hạn. Vui lòng tải lại trang.';
        $msgType = 'error';
    } else {
        $newPassword = $_POST['new_password'] ?? '';
        $confirm = $_POST['confirm_password'] ?? '';

        if (mb_strlen($newPassword) < 6) {
            $message = 'Mật khẩu phải có ít nhất 6 ký tự.';
            $msgType = 'error';
        } elseif ($newPassword !== $confirm) {
            $message = 'Mật khẩu xác nhận không khớp.';
            $msgType = 'error';
        } else {
            try {
                $newHash = password_hash($newPassword, PASSWORD_DEFAULT);

                $stmt = $pdo->prepare("UPDATE users SET password_hash = :hash WHERE user_id = :uid");
                $stmt->execute([':hash' => $newHash, ':uid' => $userId]);

                $stmt = $pdo->prepare("UPDATE password_resets SET used = 1 WHERE token = :token");
                $stmt->execute([':token' => hash('sha256', $token)]);

                $message = 'Đặt lại mật khẩu thành công! Bạn có thể đăng nhập ngay.';
                $msgType = 'success';
                $validToken = false;
            } catch (PDOException $e) {
                error_log("Reset password error: " . $e->getMessage());
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
    <title>Đặt lại mật khẩu - Literature System</title>
    <?php require_once __DIR__ . '/../templates/assets.php'; ?>
    <style>
        .reset-page { background: #f0f2f5; min-height: 100vh; }
        .reset-page main { display: flex; align-items: center; justify-content: center; min-height: 100vh; padding: 2rem 1rem; }
        .reset-card-wrap { width: 100%; max-width: 420px; }
        .reset-card-wrap .card { border: none; border-radius: 16px; box-shadow: 0 4px 24px rgba(0,0,0,0.08); }
        .reset-card-wrap .card-body { padding: 2.5rem 2rem; }
        .reset-brand { display: flex; align-items: center; gap: 0.5rem; justify-content: center; margin-bottom: 1.5rem; text-decoration: none; color: var(--lit-heading); font-weight: 700; }
        .reset-brand img { width: 32px; }
        .reset-title { font-size: 1.25rem; font-weight: 700; color: var(--lit-heading); text-align: center; margin-bottom: 0.25rem; }
        .reset-sub { text-align: center; color: var(--lit-text-secondary); font-size: 0.85rem; margin-bottom: 1.5rem; }
        .form-floating { margin-bottom: 0.75rem; position: relative; }
        .form-floating .form-control { border-radius: 10px; border: 1.5px solid #e3e6f0; padding: 1rem 0.75rem 0.5rem; height: 48px; }
        .form-floating .form-control:focus { border-color: var(--lit-primary); box-shadow: 0 0 0 3px rgba(var(--lit-primary-rgb), 0.1); }
        .form-floating label { padding: 0.75rem 0.75rem 0; font-size: 0.8rem; color: var(--lit-text-secondary); }
        .password-toggle { position: absolute; right: 12px; top: 50%; transform: translateY(-50%); background: none; border: none; color: var(--lit-text-secondary); cursor: pointer; padding: 4px; font-size: 1rem; z-index: 5; }
        .password-toggle:hover { color: var(--lit-primary); }
        .btn-reset { background: var(--lit-primary); border: none; border-radius: 10px; padding: 0.65rem; font-weight: 600; font-size: 0.95rem; width: 100%; }
        .btn-reset:hover { background: #3445d1; }
        .strength-bar { height: 4px; border-radius: 2px; background: var(--lit-surface-3); margin-top: 0.3rem; overflow: hidden; }
        .strength-bar-fill { height: 100%; border-radius: 2px; transition: width 0.3s, background 0.3s; }
        .reset-footer { text-align: center; margin-top: 1.25rem; font-size: 0.85rem; color: var(--lit-text-secondary); }
        .reset-footer a { color: var(--lit-primary); text-decoration: none; font-weight: 600; }
        .alert { border-radius: 10px; font-size: 0.85rem; padding: 0.75rem 1rem; }
        .invalid-token { text-align: center; padding: 2rem 0; }
        .invalid-token i { font-size: 3rem; color: #dc3545; opacity: 0.5; }
    </style>
</head>

<body class="login-page">
    <main>
        <div class="reset-card-wrap">
            <div class="card">
                <div class="card-body">
                    <a href="<?= URL_ROOT ?>/" class="reset-brand">
                        <img src="<?= URL_ROOT ?>/assets/img/fit.png" alt="Logo">
                        <span>Literature System</span>
                    </a>

                    <?php if (!$validToken && $msgType !== 'success'): ?>
                        <div class="invalid-token">
                            <i class="bi bi-exclamation-triangle d-block mb-3"></i>
                            <h5 class="text-danger">Liên kết không hợp lệ</h5>
                            <p class="text-muted">Liên kết đặt lại mật khẩu đã hết hạn hoặc không tồn tại.</p>
                            <a href="<?= URL_ROOT ?>/forgot_password" class="btn btn-reset mt-3" style="max-width:200px; margin:0 auto;">Yêu cầu lại</a>
                        </div>
                    <?php else: ?>
                        <h3 class="reset-title">Đặt lại mật khẩu</h3>
                        <p class="reset-sub">Nhập mật khẩu mới cho tài khoản của bạn</p>

                        <?php if ($message): ?>
                            <div class="alert alert-<?= $msgType === 'error' ? 'danger' : 'success' ?>">
                                <i class="bi bi-<?= $msgType === 'error' ? 'exclamation-circle' : 'check-circle' ?> me-1"></i>
                                <?= htmlspecialchars($message) ?>
                            </div>
                        <?php endif; ?>

                        <?php if ($msgType !== 'success'): ?>
                        <form method="POST" action="">
                            <?= csrf_field() ?>
                            <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">

                            <div class="form-floating">
                                <input type="password" class="form-control" id="new_password" name="new_password" placeholder="Mật khẩu mới" required minlength="6">
                                <label for="new_password">Mật khẩu mới</label>
                                <button type="button" class="password-toggle" onclick="togglePass('new_password', this)" aria-label="Hiện/ẩn mật khẩu" title="Hiện/ẩn mật khẩu"><i class="bi bi-eye" aria-hidden="true"></i></button>
                            </div>
                            <div class="strength-bar"><div class="strength-bar-fill" id="strengthBar"></div></div>
                            <small class="text-muted d-block mb-2" id="strengthText"></small>

                            <div class="form-floating">
                                <input type="password" class="form-control" id="confirm_password" name="confirm_password" placeholder="Xác nhận mật khẩu" required minlength="6">
                                <label for="confirm_password">Xác nhận mật khẩu</label>
                                <button type="button" class="password-toggle" onclick="togglePass('confirm_password', this)" aria-label="Hiện/ẩn mật khẩu" title="Hiện/ẩn mật khẩu"><i class="bi bi-eye" aria-hidden="true"></i></button>
                            </div>

                            <button type="submit" class="btn btn-reset mt-2">
                                <i class="bi bi-shield-lock me-1"></i> Đặt lại mật khẩu
                            </button>
                        </form>
                        <?php endif; ?>
                    <?php endif; ?>

                    <div class="reset-footer">
                        <a href="<?= URL_ROOT ?>/login"><i class="bi bi-arrow-left me-1"></i>Quay lại đăng nhập</a>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <?php require_once __DIR__ . '/../templates/vendors.php'; ?>
    <script>
    function togglePass(id, btn) {
        const input = document.getElementById(id);
        const icon = btn.querySelector('i');
        if (input.type === 'password') { input.type = 'text'; icon.className = 'bi bi-eye-slash'; }
        else { input.type = 'password'; icon.className = 'bi bi-eye'; }
    }
    document.getElementById('new_password')?.addEventListener('input', function() {
        const val = this.value;
        const bar = document.getElementById('strengthBar');
        const txt = document.getElementById('strengthText');
        let score = 0;
        if (val.length >= 6) score++;
        if (val.length >= 10) score++;
        if (/[A-Z]/.test(val) && /[a-z]/.test(val)) score++;
        if (/[0-9]/.test(val)) score++;
        if (/[^A-Za-z0-9]/.test(val)) score++;
        const levels = [
            { w: '0%', bg: '#e9ecef', text: '' },
            { w: '20%', bg: '#dc3545', text: 'Rất yếu' },
            { w: '40%', bg: '#fd7e14', text: 'Yếu' },
            { w: '60%', bg: '#ffc107', text: 'Trung bình' },
            { w: '80%', bg: '#198754', text: 'Mạnh' },
            { w: '100%', bg: '#0d6efd', text: 'Rất mạnh' }
        ];
        const lvl = levels[score] || levels[0];
        bar.style.width = lvl.w;
        bar.style.background = lvl.bg;
        txt.textContent = lvl.text;
    });
    </script>
</body>

</html>
