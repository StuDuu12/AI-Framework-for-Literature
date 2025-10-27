<?php
require_once 'config/helper_account.php';

$error = '';
$success = '';
$username = isset($_POST['username']) ? trim($_POST['username']) : '';
$password = isset($_POST['password']) ? trim($_POST['password']) : '';
if (isset($_POST['login'])) {
    $username = isset($_POST['username']) ? trim($_POST['username']) : '';
    $password = isset($_POST['password']) ? trim($_POST['password']) : '';

    $error = '';
    $success = '';

    if (empty($username) || empty($password)) {
        $error = 'Vui lòng nhập đầy đủ thông tin.';
    } else {
        // Kiểm tra đăng nhập
        if (login($username, $password)) {
            // Đăng nhập thành công, chuyển hướng đến trang dashboard
            header('Location: ' . URL_ROOT . '/dashboard');
            exit;
        } else {
            $error = 'Tên đăng nhập hoặc mật khẩu không đúng.';
        }
    }
}

if (isset($_SESSION['user']) && !empty($_SESSION['user'])) {
    // Nếu đã đăng nhập, chuyển hướng đến trang dashboard
    header('Location: ' . URL_ROOT . '/dashboard');
    exit;
}
?>
<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="utf-8">
    <meta content="width=device-width, initial-scale=1.0" name="viewport">
    <title>Đăng nhập - Literature System</title>
    <meta content="" name="description">
    <meta content="" name="keywords">

    <!-- Google Font: Inter -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

    <!-- NiceAdmin Assets -->
    <?php require_once 'views/templates/assets.php'; ?>

    <style>
        /* Áp dụng toàn bộ trang dùng font Inter */
        body,
        html {
            font-family: 'Inter', sans-serif !important;
            font-weight: 400;
        }

        h5,
        label,
        button,
        .form-control,
        .alert,
        .small {
            font-family: 'Inter', sans-serif !important;
        }

        .btn-primary {
            font-weight: 600;
        }

        .card-title {
            font-weight: 600;
        }
    </style>
</head>

<body>
    <main>
        <div class="container">
            <section class="section register min-vh-100 d-flex flex-column align-items-center justify-content-center py-4">
                <div class="container">
                    <div class="row justify-content-center">
                        <div class="col-lg-4 col-md-6 d-flex flex-column align-items-center justify-content-center">

                            <div class="d-flex justify-content-center py-4">
                                <a href="index.php" class="logo d-flex align-items-center w-auto">
                                    <img src="NiceAdmin/NiceAdmin/assets/img/logo.png" alt="">
                                    <span class="d-none d-lg-block">Literature System</span>
                                </a>
                            </div>

                            <div class="card mb-3">
                                <div class="card-body">
                                    <div class="pt-4 pb-2">
                                        <h5 class="card-title text-center pb-0 fs-4">Đăng nhập tài khoản</h5>
                                        <p class="text-center small">Nhập tên đăng nhập và mật khẩu để đăng nhập</p>
                                    </div>

                                    <form method="POST" action="" class="row g-3">
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
                                            <label for="email" class="form-label">Username</label>
                                            <div class="input-group">
                                                <span class="input-group-text" id="inputGroupPrepend">@</span>
                                                <input type="text" name="username" class="form-control" id="username"
                                                    value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>" required>
                                            </div>
                                        </div>

                                        <div class="col-12">
                                            <label for="password" class="form-label">Mật khẩu</label>
                                            <input type="password" name="password" class="form-control" id="password" required>
                                        </div>

                                        <div class="col-12">
                                            <button class="btn btn-primary w-100" type="submit" name="login">Đăng nhập</button>
                                        </div>

                                        <div class="col-12">
                                            <p class="small mb-0 text-muted">
                                                <i class="bi bi-info-circle"></i> Tài khoản học sinh được tạo bởi giáo viên
                                            </p>
                                        </div>
                                    </form>
                                </div>
                            </div>

                        </div>
                    </div>
                </div>
            </section>
        </div>
    </main>

    <?php require_once './views/templates/vendors.php'; ?>
</body>

</html>