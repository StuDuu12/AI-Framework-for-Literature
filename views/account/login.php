<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta content="width=device-width, initial-scale=1.0" name="viewport">
    <title>Đăng nhập - Literature System</title>
    <meta content="" name="description">
    <meta content="" name="keywords">

    <?php
    require_once '../includes/config.php';

    // Xử lý đăng nhập với PHP
    $error = '';
    $success = '';

    // Kiểm tra thông báo từ URL
    if (isset($_GET['error'])) {
        switch ($_GET['error']) {
            case 'not_logged_in':
                $error = 'Vui lòng đăng nhập để truy cập trang này';
                break;
            case 'session_expired':
                $error = 'Phiên đăng nhập đã hết hạn, vui lòng đăng nhập lại';
                break;
            case 'access_denied':
                $error = 'Bạn không có quyền truy cập trang này';
                break;
            case 'register_disabled':
                $error = 'Chức năng đăng ký đã bị vô hiệu hóa. Vui lòng liên hệ giáo viên để được tạo tài khoản';
                break;
        }
    }

    if (isset($_GET['message']) && $_GET['message'] === 'logged_out') {
        $success = 'Đăng xuất thành công';
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $remember = isset($_POST['remember']);

        if (empty($email) || empty($password)) {
            $error = 'Vui lòng nhập đầy đủ email và mật khẩu';
        } else {
            try {
                $pdo = getDBConnection();

                // Tìm user theo email
                $query = "SELECT user_id, username, email, password_hash, role FROM users WHERE email = ?";
                $stmt = $pdo->prepare($query);
                $stmt->execute([trim($email)]);
                $user = $stmt->fetch(PDO::FETCH_ASSOC);

                if (!$user) {
                    $error = 'Email này chưa được đăng ký trong hệ thống';
                } elseif (empty($user['password_hash'])) {
                    $error = 'Tài khoản chưa được thiết lập mật khẩu';
                } elseif (!password_verify($password, $user['password_hash'])) {
                    $error = 'Mật khẩu không đúng';
                } else {
                    // Đăng nhập thành công
                    session_start();
                    $_SESSION['user_id'] = $user['user_id'];
                    $_SESSION['username'] = $user['username'];
                    $_SESSION['email'] = $user['email'];
                    $_SESSION['role'] = $user['role'];
                    $_SESSION['login_time'] = time();

                    // Remember me
                    if ($remember) {
                        $token = bin2hex(random_bytes(32));
                        setcookie('remember_token', $token, time() + (86400 * 30), '/', '', false, true);
                    }

                    // Chuyển hướng theo role
                    if ($user['role'] === 'teacher' || $user['role'] === 'admin') {
                        redirect_to('main.php');
                    } else {
                        redirect_to('student.php');
                    }
                    exit;
                }
            } catch (PDOException $e) {
                $error = 'Lỗi hệ thống: ' . $e->getMessage();
            }
        }
    }

    include_template('assets.php');
    ?>
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
                                        <p class="text-center small">Nhập email và mật khẩu để đăng nhập</p>
                                    </div>

                                    <form method="POST" action="" class="row g-3 needs-validation" novalidate>
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
                                            <label for="email" class="form-label">Email</label>
                                            <div class="input-group has-validation">
                                                <span class="input-group-text" id="inputGroupPrepend">@</span>
                                                <input type="email" name="email" class="form-control" id="email"
                                                    value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" required>
                                                <div class="invalid-feedback">Vui lòng nhập email!</div>
                                            </div>
                                        </div>

                                        <div class="col-12">
                                            <label for="password" class="form-label">Mật khẩu</label>
                                            <input type="password" name="password" class="form-control" id="password" required>
                                            <div class="invalid-feedback">Vui lòng nhập mật khẩu!</div>
                                        </div>

                                        <div class="col-12">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" name="remember" value="1" id="rememberMe">
                                                <label class="form-check-label" for="rememberMe">Ghi nhớ đăng nhập</label>
                                            </div>
                                        </div>

                                        <div class="col-12">
                                            <button class="btn btn-primary w-100" type="submit">Đăng nhập</button>
                                        </div>

                                        <div class="col-12">
                                            <p class="small mb-0 text-muted">
                                                <i class="bi bi-info-circle"></i> Tài khoản học sinh được tạo bởi giáo viên
                                            </p>
                                        </div>
                                    </form>
                                </div>
                            </div>

                            <div class="credits">
                                Designed by <a href="https://bootstrapmade.com/">BootstrapMade</a>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
        </div>
    </main>

    <?php include_template('vendors.php'); ?>

    <script>
        // Ngăn chặn back button và cache
        if (window.history.replaceState) {
            window.history.replaceState(null, null, window.location.href);
        }

        window.addEventListener('popstate', function(event) {
            window.history.pushState(null, null, window.location.href);
        });

        // Ngăn chặn cache
        if (window.performance && window.performance.navigation.type === 2) {
            window.location.reload();
        }
    </script>
</body>

</html>