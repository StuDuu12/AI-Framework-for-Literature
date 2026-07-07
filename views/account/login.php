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

    if (!csrf_verify()) {
        $error = 'Your session has expired. Please try again.';
    } elseif (empty($username) || empty($password)) {
        $error = 'Please fill in all required fields.';
    } else {
        // Kiểm tra đăng nhập
        if (login($username, $password)) {
            $role = $_SESSION['user']['role'] ?? '';
            $dest = ($role === 'student') ? '/workspace' : '/dashboard';
            header('Location: ' . URL_ROOT . $dest);
            exit;
        } else {
            $error = 'Invalid username or password.';
        }
    }
}

if (isset($_SESSION['user']) && !empty($_SESSION['user'])) {
    $role = $_SESSION['user']['role'] ?? '';
    $dest = ($role === 'student') ? '/workspace' : '/dashboard';
    header('Location: ' . URL_ROOT . $dest);
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta content="width=device-width, initial-scale=1.0" name="viewport">
    <title data-lang-key="login_title">Login - Literature System</title>
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
                                <img src="<?= URL_ROOT ?>/assets/img/fit.png" alt="Literature System">
                                <span data-lang-key="system_title">Literature System</span>
                            </a>
                        </div>
                        <h5 class="card-title">Login</h5>
                        <p class="text-center small">Enter your username and password to log in</p>
                    </div>

                    <form method="POST" action="" class="row g-3">
                        <?= csrf_field() ?>
                        <?php if ($error): ?>
                            <div class="alert alert-danger" role="alert">
                                <?php echo htmlspecialchars($error); ?>
                            </div>
                        <?php endif; ?>

                        <?php if ($success): ?>
                            <div class="alert alert-success" role="alert">
                                <?php echo htmlspecialchars($success); ?>
                            </div>
                        <?php endif; ?>

                        <div class="col-12">
                            <label for="username" class="form-label">Username</label>
                            <div class="input-group">
                                <span class="input-group-text" id="inputGroupPrepend">@</span>
                                <input type="text" name="username" class="form-control" id="username"
                                    value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>" required>
                            </div>
                        </div>

                        <div class="col-12">
                            <label for="password" class="form-label">Password</label>
                            <input type="password" name="password" class="form-control" id="password" required>
                        </div>

                        <div class="col-12">
                            <button class="btn btn-primary w-100" type="submit" name="login">Login</button>
                        </div>

                        <div class="col-12 d-flex justify-content-between">
                            <p class="small mb-0">
                                <a href="<?= URL_ROOT ?>/forgot_password" class="text-muted">Quên mật khẩu?</a>
                            </p>
                            <p class="small mb-0">
                                <a href="<?= URL_ROOT ?>/register" class="text-primary">Đăng ký</a>
                            </p>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </main>

    <?php require_once __DIR__ . '/../templates/vendors.php'; ?>
</body>

</html>
