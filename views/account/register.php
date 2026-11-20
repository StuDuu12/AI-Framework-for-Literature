<?php
session_start();
require_once __DIR__ . '/../../config/env.php';
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../config/security.php';

if (isset($_SESSION['user']) && !empty($_SESSION['user'])) {
    $role = $_SESSION['user']['role'] ?? '';
    $dest = ($role === 'student') ? '/workspace' : '/dashboard';
    header('Location: ' . URL_ROOT . $dest);
    exit;
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify()) {
        $error = 'Phiên hết hạn. Vui lòng tải lại trang.';
    } else {
        $fullname = trim($_POST['fullname'] ?? '');
        $username = trim($_POST['username'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirm = $_POST['confirm_password'] ?? '';

        if ($fullname === '' || $username === '' || $password === '' || $confirm === '') {
            $error = 'Vui lòng nhập đầy đủ thông tin.';
        } elseif (mb_strlen($fullname) > 255) {
            $error = 'Họ và tên tối đa 255 ký tự.';
        } elseif (mb_strlen($username) < 3 || mb_strlen($username) > 100) {
            $error = 'Tên đăng nhập phải từ 3-100 ký tự.';
        } elseif (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
            $error = 'Tên đăng nhập chỉ được dùng chữ, số và gạch dưới.';
        } elseif (mb_strlen($password) < 6) {
            $error = 'Mật khẩu phải có ít nhất 6 ký tự.';
        } elseif ($password !== $confirm) {
            $error = 'Mật khẩu xác nhận không khớp.';
        } elseif ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Email không hợp lệ.';
        } else {
            try {
                $stmt = $pdo->prepare("SELECT COUNT(*) as cnt FROM users WHERE username = :username");
                $stmt->execute([':username' => $username]);
                if ($stmt->fetch(PDO::FETCH_ASSOC)['cnt'] > 0) {
                    $error = 'Tên đăng nhập đã tồn tại.';
                } else {
                    $stmt = $pdo->prepare("SELECT COUNT(*) as cnt FROM registration_requests WHERE username = :username AND status = 'pending'");
                    $stmt->execute([':username' => $username]);
                    if ($stmt->fetch(PDO::FETCH_ASSOC)['cnt'] > 0) {
                        $error = 'Bạn đã có yêu cầu đăng ký đang chờ duyệt.';
                    } else {
                        $hash = password_hash($password, PASSWORD_DEFAULT);
                        $stmt = $pdo->prepare("INSERT INTO registration_requests (fullname, username, email, password_hash, role) VALUES (:fullname, :username, :email, :hash, 'student')");
                        $stmt->execute([
                            ':fullname' => $fullname,
                            ':username' => $username,
                            ':email' => $email !== '' ? $email : null,
                            ':hash' => $hash,
                        ]);
                        $success = 'Đăng ký thành công! Yêu cầu của bạn đang chờ quản trị viên duyệt.';
                    }
                }
            } catch (PDOException $e) {
                error_log("Register error: " . $e->getMessage());
                $error = 'Lỗi hệ thống. Vui lòng thử lại.';
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
    <title>Đăng ký - Literature System</title>
    <?php require_once __DIR__ . '/../templates/assets.php'; ?>
    <style>
        .register-page { background: #f0f2f5; min-height: 100vh; }
        .register-page main { display: flex; align-items: center; justify-content: center; min-height: 100vh; padding: 2rem 1rem; }
        .register-card-wrap { width: 100%; max-width: 440px; }
        .register-card-wrap .card { border: none; border-radius: 16px; box-shadow: 0 4px 24px rgba(0,0,0,0.08); overflow: hidden; }
        .register-card-wrap .card-body { padding: 2.5rem 2rem; }
        .register-brand { display: flex; align-items: center; gap: 0.5rem; justify-content: center; margin-bottom: 1.5rem; text-decoration: none; color: #012970; font-weight: 700; font-size: 1.1rem; }
        .register-brand img { width: 32px; height: auto; }
        .register-title { font-size: 1.35rem; font-weight: 700; color: #012970; text-align: center; margin-bottom: 0.25rem; }
        .register-sub { text-align: center; color: #6c757d; font-size: 0.85rem; margin-bottom: 1.5rem; }
        .form-floating { margin-bottom: 0.75rem; }
        .form-floating .form-control { border-radius: 10px; border: 1.5px solid #e3e6f0; padding: 1rem 0.75rem 0.5rem; height: 48px; font-size: 0.9rem; }
        .form-floating .form-control:focus { border-color: #4154f1; box-shadow: 0 0 0 3px rgba(65,84,241,0.1); }
        .form-floating label { padding: 0.75rem 0.75rem 0; font-size: 0.8rem; color: #6c757d; }
        .btn-register { background: #4154f1; border: none; border-radius: 10px; padding: 0.65rem; font-weight: 600; font-size: 0.95rem; transition: background 0.2s; width: 100%; }
        .btn-register:hover { background: #3445d1; }
        .btn-register:disabled { opacity: 0.6; cursor: not-allowed; }
        .register-footer { text-align: center; margin-top: 1.25rem; font-size: 0.85rem; color: #6c757d; }
        .register-footer a { color: #4154f1; text-decoration: none; font-weight: 600; }
        .register-footer a:hover { text-decoration: underline; }
        .strength-bar { height: 4px; border-radius: 2px; background: #e9ecef; margin-top: 0.3rem; overflow: hidden; }
        .strength-bar-fill { height: 100%; border-radius: 2px; transition: width 0.3s, background 0.3s; }
        .password-wrapper { position: relative; }
        .password-toggle { position: absolute; right: 12px; top: 50%; transform: translateY(-50%); background: none; border: none; color: #6c757d; cursor: pointer; padding: 4px; font-size: 1rem; z-index: 5; }
        .password-toggle:hover { color: #4154f1; }
        .alert { border-radius: 10px; font-size: 0.85rem; padding: 0.75rem 1rem; }
        .role-tabs { display: flex; gap: 0; margin-bottom: 1.25rem; background: #f1f3f5; border-radius: 10px; padding: 4px; }
        .role-tab { flex: 1; text-align: center; padding: 0.5rem; border-radius: 8px; border: none; background: transparent; font-weight: 600; font-size: 0.85rem; color: #6c757d; cursor: pointer; transition: all 0.2s; }
        .role-tab.active { background: #fff; color: #4154f1; box-shadow: 0 1px 4px rgba(0,0,0,0.08); }
        .role-tab:hover:not(.active) { color: #012970; }
    </style>
</head>

<body class="login-page">
    <main>
        <div class="register-card-wrap">
            <div class="card">
                <div class="card-body">
                    <a href="<?= URL_ROOT ?>/" class="register-brand">
                        <img src="<?= URL_ROOT ?>/assets/img/fit.png" alt="Logo">
                        <span>Literature System</span>
                    </a>
                    <h3 class="register-title">Tạo tài khoản mới</h3>
                    <p class="register-sub">Điền thông tin bên dưới để đăng ký</p>

                    <?php if ($error): ?>
                        <div class="alert alert-danger"><i class="bi bi-exclamation-circle me-1"></i> <?= htmlspecialchars($error) ?></div>
                    <?php endif; ?>
                    <?php if ($success): ?>
                        <div class="alert alert-success"><i class="bi bi-check-circle me-1"></i> <?= htmlspecialchars($success) ?></div>
                    <?php endif; ?>

                    <?php if (!$success): ?>
                    <form method="POST" action="" id="registerForm">
                        <?= csrf_field() ?>

                        <div class="role-tabs">
                            <button type="button" class="role-tab active" data-role="student" onclick="setRole('student', this)">
                                <i class="bi bi-mortarboard me-1"></i> Học sinh
                            </button>
                            <button type="button" class="role-tab" data-role="teacher" onclick="setRole('teacher', this)">
                                <i class="bi bi-person-workspace me-1"></i> Giáo viên
                            </button>
                        </div>
                        <input type="hidden" name="role" id="selectedRole" value="student">

                        <div class="form-floating">
                            <input type="text" class="form-control" id="fullname" name="fullname" placeholder="Họ và tên" required maxlength="255"
                                value="<?= htmlspecialchars($_POST['fullname'] ?? '') ?>">
                            <label for="fullname">Họ và tên *</label>
                        </div>

                        <div class="form-floating">
                            <input type="text" class="form-control" id="username" name="username" placeholder="Tên đăng nhập" required minlength="3" maxlength="100" pattern="[a-zA-Z0-9_]+"
                                value="<?= htmlspecialchars($_POST['username'] ?? '') ?>">
                            <label for="username">Tên đăng nhập *</label>
                        </div>

                        <div class="form-floating">
                            <input type="email" class="form-control" id="email" name="email" placeholder="Email"
                                value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
                            <label for="email">Email (không bắt buộc)</label>
                        </div>

                        <div class="form-floating password-wrapper">
                            <input type="password" class="form-control" id="password" name="password" placeholder="Mật khẩu" required minlength="6">
                            <label for="password">Mật khẩu *</label>
                            <button type="button" class="password-toggle" onclick="togglePass('password', this)"><i class="bi bi-eye"></i></button>
                        </div>
                        <div class="strength-bar"><div class="strength-bar-fill" id="strengthBar"></div></div>
                        <small class="text-muted d-block mb-2" id="strengthText"></small>

                        <div class="form-floating password-wrapper">
                            <input type="password" class="form-control" id="confirm_password" name="confirm_password" placeholder="Xác nhận mật khẩu" required minlength="6">
                            <label for="confirm_password">Xác nhận mật khẩu *</label>
                            <button type="button" class="password-toggle" onclick="togglePass('confirm_password', this)"><i class="bi bi-eye"></i></button>
                        </div>

                        <button type="submit" class="btn btn-register mt-3" id="btnRegister">
                            <i class="bi bi-person-plus me-1"></i> Đăng ký
                        </button>
                    </form>
                    <?php endif; ?>

                    <div class="register-footer">
                        Đã có tài khoản? <a href="<?= URL_ROOT ?>/login">Đăng nhập</a>
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

    function setRole(role, el) {
        document.getElementById('selectedRole').value = role;
        document.querySelectorAll('.role-tab').forEach(t => t.classList.remove('active'));
        el.classList.add('active');
    }

    document.getElementById('password')?.addEventListener('input', function() {
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
