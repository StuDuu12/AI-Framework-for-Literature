<?php
require_once __DIR__ . '/../../config/helper_account.php';

$error = '';
$success = '';
$username = isset($_POST['username']) ? trim($_POST['username']) : '';
$password = isset($_POST['password']) ? trim($_POST['password']) : '';
if (isset($_POST['login'])) {
    $username = isset($_POST['username']) ? trim($_POST['username']) : '';
    $password = isset($_POST['password']) ? trim($_POST['password']) : '';

    $error = '';
    $success = '';

    // Chống dò mật khẩu: tối đa 10 lần/5 phút cho mỗi username, 60 lần/5 phút cho mỗi IP
    // (IP nới rộng vì cả lớp học thường dùng chung một IP NAT).
    $loginByUser = rate_limit_check('login', 10, 300, 'login-user:' . mb_strtolower($username));
    $loginByIp = rate_limit_check('login', 60, 300, 'login-ip:' . ($_SERVER['REMOTE_ADDR'] ?? ''));

    if (!csrf_verify()) {
        $error = 'Phiên làm việc đã hết hạn. Vui lòng thử lại.';
    } elseif (!$loginByUser['allowed'] || !$loginByIp['allowed']) {
        $error = 'Bạn đã thử đăng nhập quá nhiều lần. Vui lòng đợi vài phút rồi thử lại.';
    } elseif (empty($username) || empty($password)) {
        $error = 'Vui lòng nhập đủ tên đăng nhập và mật khẩu.';
    } else {
        // Kiểm tra đăng nhập
        if (login($username, $password)) {
            $role = $_SESSION['user']['role'] ?? '';
            $dest = ($role === 'student') ? '/today' : '/dashboard';
            header('Location: ' . URL_ROOT . $dest);
            exit;
        } else {
            $error = 'Tên đăng nhập hoặc mật khẩu không đúng.';
        }
    }
}

if (isset($_SESSION['user']) && !empty($_SESSION['user'])) {
    $role = $_SESSION['user']['role'] ?? '';
    $dest = ($role === 'student') ? '/today' : '/dashboard';
    header('Location: ' . URL_ROOT . $dest);
    exit;
}
?>
<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="utf-8">
    <meta content="width=device-width, initial-scale=1.0" name="viewport">
    <title>Đăng nhập · Literature System</title>
    <?php require_once __DIR__ . '/../templates/assets.php'; ?>
</head>

<body class="login-page">
    <main>
        <div class="login-card-wrap">
            <div class="card mb-0">
                <div class="card-body">
                    <div class="text-center mb-4">
                        <div class="d-flex justify-content-center mb-3">
                            <a href="<?= URL_ROOT ?>/" class="logo d-flex align-items-center gap-2">
                                <img src="<?= URL_ROOT ?>/assets/img/fit.png" alt="" width="40" height="40">
                                <span data-lang-key="system_title">Literature System</span>
                            </a>
                        </div>
                        <h1 class="card-title h5">Đăng nhập</h1>
                        <p class="text-center small">Nhập tên đăng nhập và mật khẩu của bạn</p>
                    </div>

                    <form method="POST" action="" class="row g-3" id="login-form" novalidate>
                        <?= csrf_field() ?>
                        <?php if ($error): ?>
                            <div class="alert alert-danger d-flex align-items-start gap-2" role="alert">
                                <i class="bi bi-exclamation-circle" aria-hidden="true"></i>
                                <span><?php echo htmlspecialchars($error); ?></span>
                            </div>
                        <?php endif; ?>

                        <?php if ($success): ?>
                            <div class="alert alert-success d-flex align-items-start gap-2" role="status">
                                <i class="bi bi-check-circle" aria-hidden="true"></i>
                                <span><?php echo htmlspecialchars($success); ?></span>
                            </div>
                        <?php endif; ?>

                        <div class="col-12">
                            <label for="username" class="form-label">Tên đăng nhập</label>
                            <div class="input-group">
                                <span class="input-group-text" aria-hidden="true"><i class="bi bi-person"></i></span>
                                <input type="text" name="username" class="form-control" id="username"
                                    autocomplete="username" autocapitalize="none" spellcheck="false"
                                    value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>" required
                                    <?= empty($_POST['username']) ? 'autofocus' : '' ?>>
                            </div>
                        </div>

                        <div class="col-12">
                            <label for="password" class="form-label">Mật khẩu</label>
                            <div class="input-group">
                                <span class="input-group-text" aria-hidden="true"><i class="bi bi-lock"></i></span>
                                <input type="password" name="password" class="form-control" id="password"
                                    autocomplete="current-password" required <?= !empty($_POST['username']) ? 'autofocus' : '' ?>>
                                <button class="btn btn-outline-secondary" type="button" id="toggle-password"
                                    aria-label="Hiện mật khẩu" aria-controls="password" aria-pressed="false">
                                    <i class="bi bi-eye" aria-hidden="true"></i>
                                </button>
                            </div>
                        </div>

                        <div class="col-12">
                            <button class="btn btn-primary w-100" type="submit" name="login" value="1" id="login-submit">
                                <span class="label">Đăng nhập</span>
                            </button>
                        </div>

                        <div class="col-12 d-flex justify-content-between">
                            <p class="small mb-0">
                                <a href="<?= URL_ROOT ?>/forgot_password" class="text-muted">Quên mật khẩu?</a>
                            </p>
                            <p class="small mb-0">
                                Chưa có tài khoản? <a href="<?= URL_ROOT ?>/register" class="text-primary fw-semibold">Đăng ký</a>
                            </p>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </main>

    <?php require_once __DIR__ . '/../templates/vendors.php'; ?>
    <script>
        (function () {
            var pwd = document.getElementById('password');
            var toggle = document.getElementById('toggle-password');
            toggle.addEventListener('click', function () {
                var show = pwd.type === 'password';
                pwd.type = show ? 'text' : 'password';
                toggle.setAttribute('aria-pressed', show ? 'true' : 'false');
                toggle.setAttribute('aria-label', show ? 'Ẩn mật khẩu' : 'Hiện mật khẩu');
                toggle.querySelector('i').className = show ? 'bi bi-eye-slash' : 'bi bi-eye';
            });

            var form = document.getElementById('login-form');
            var btn = document.getElementById('login-submit');
            form.addEventListener('submit', function (e) {
                var u = document.getElementById('username');
                if (!u.value.trim() || !pwd.value) {
                    e.preventDefault();
                    UI.warning('Vui lòng nhập đủ tên đăng nhập và mật khẩu.');
                    (u.value.trim() ? pwd : u).focus();
                    return;
                }
                // Giữ name="login" khi nút bị disable (server kiểm tra isset($_POST['login']))
                var hidden = document.createElement('input');
                hidden.type = 'hidden'; hidden.name = 'login'; hidden.value = '1';
                form.appendChild(hidden);
                btn.disabled = true;
                btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2" aria-hidden="true"></span>Đang đăng nhập…';
            });
        })();
    </script>
</body>

</html>