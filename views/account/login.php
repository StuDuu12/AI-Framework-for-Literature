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
            // Đăng nhập thành công, kiểm tra role để chuyển hướng
            if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin') {
                header('Location: ' . URL_ROOT . '/views/admin/dashboard.php');
            } else {
                header('Location: ' . URL_ROOT . '/dashboard');
            }
            exit;
        } else {
            $error = 'Tên đăng nhập hoặc mật khẩu không đúng.';
        }
    }
}

if (isset($_SESSION['user']) && !empty($_SESSION['user'])) {
    // Nếu đã đăng nhập, kiểm tra role để chuyển hướng
    if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin') {
        header('Location: ' . URL_ROOT . '/views/admin/dashboard.php');
    } else {
        header('Location: ' . URL_ROOT . '/dashboard');
    }
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
    <?php require_once './views/templates/assets.php'; ?>

    <style>
        /* Áp dụng toàn bộ trang dùng font Inter */
        body,
        html {
            font-family: 'Inter', sans-serif !important;
            font-weight: 400;
            margin: 0;
            padding: 0;
            height: 100vh;
        }

        h5,
        label,
        button,
        .form-control,
        .alert,
        .small {
            font-family: 'Inter', sans-serif !important;
        }

        /* Background image - giữ nguyên màu gốc */
        body {
            background-image: url('/assets/img/nensao.png');
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            background-attachment: fixed;
            position: relative;
        }

        /* Remove white overlay - giữ màu gốc của ảnh */
        body::before {
            content: '';
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(135deg, rgba(0, 0, 0, 0.3), rgba(0, 0, 0, 0.5));
            z-index: -1;
        }

        /* Beautiful Login Card */
        .card {
            border: none !important;
            border-radius: 20px !important;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3) !important;
            backdrop-filter: blur(20px) !important;
            background: rgba(255, 255, 255, 0.9) !important;
            overflow: hidden;
        }

        .card-body {
            padding: 2rem 2rem !important;
        }

        /* Logo and Title */
        .logo {
            text-decoration: none;
            gap: 8px;
            margin-bottom: 1rem;
            justify-content: center;
            align-items: center;
        }

        .logo img {
            height: 45px;
            width: auto;
        }

        .logo span {
            font-size: 1.5rem;
            font-weight: 700;
            color: #4154f1;
            display: block !important;
            text-align: center;
        }

        .card-title {
            font-size: 1.75rem;
            font-weight: 700;
            color: #2c3e50;
            margin-bottom: 0.75rem;
            text-align: center;
            letter-spacing: -0.5px;
            justify-content: center;
        }

        .text-center.small {
            color: #6c757d;
            font-size: 0.9rem;
            margin-bottom: 1.5rem;
        }

        /* Form Controls */
        .form-label {
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 0.5rem;
            font-size: 0.9rem;
        }

        .form-control {
            border: 2px solid #e0e6ed;
            border-radius: 12px;
            padding: 0.75rem 1rem;
            font-size: 0.95rem;
            transition: all 0.3s ease;
        }

        .form-control:focus {
            border-color: #4154f1;
            box-shadow: 0 0 0 0.2rem rgba(65, 84, 241, 0.15);
        }

        .input-group-text {
            border: 2px solid #e0e6ed;
            border-right: none;
            border-radius: 12px 0 0 12px;
            background-color: #f8f9fa;
            font-weight: 600;
            color: #4154f1;
        }

        .input-group .form-control {
            border-left: none;
            border-radius: 0 12px 12px 0;
        }

        .input-group .form-control:focus {
            border-left: none;
        }

        .input-group:focus-within .input-group-text {
            border-color: #4154f1;
            background-color: rgba(65, 84, 241, 0.1);
        }

        /* Button */
        .btn-primary {
            background: linear-gradient(135deg, #4154f1 0%, #2c3e87 100%);
            border: none;
            border-radius: 12px;
            padding: 0.875rem 1.5rem;
            font-size: 1rem;
            font-weight: 600;
            letter-spacing: 0.5px;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(65, 84, 241, 0.3);
            margin-top: 0.25rem;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(65, 84, 241, 0.4);
            background: linear-gradient(135deg, #5568ff 0%, #3d4fa3 100%);
        }

        .btn-primary:active {
            transform: translateY(0);
        }

        /* Alert Messages */
        .alert {
            border: none;
            border-radius: 12px;
            padding: 1rem 1.25rem;
            font-weight: 500;
        }

        .alert-danger {
            background-color: #ffe5e5;
            color: #dc3545;
        }

        .alert-success {
            background-color: #d4edda;
            color: #155724;
        }

        /* Info Text */
        .text-muted {
            background-color: #e7f3ff;
            padding: 0.75rem 1rem;
            border-radius: 10px;
            border-left: 4px solid #4154f1;
            margin-top: 0.75rem;
        }

        /* Form spacing */
        .row.g-3 {
            gap: 1rem !important;
        }

        .col-12 {
            padding-left: 0 !important;
            padding-right: 0 !important;
        }

        /* Animations */
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .card {
            animation: fadeInUp 0.6s ease-out;
        }

        /* Container adjustments */
        .section.register {
            padding-top: 1rem !important;
            padding-bottom: 1rem !important;
        }

        /* Card max width to prevent overflow */
        .card {
            max-width: 100%;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .card-body {
                padding: 1.5rem 1.25rem !important;
            }

            .card-title {
                font-size: 1.5rem;
            }

            .logo span {
                font-size: 1.25rem;
            }

            .logo img {
                height: 40px;
            }

            .row.g-3 {
                gap: 0.75rem !important;
            }

            .text-center.small {
                margin-bottom: 1rem;
            }
        }

        /* Reduce overall height on small screens */
        @media (max-height: 700px) {
            .card-body {
                padding: 1.5rem 2rem !important;
            }

            .logo {
                margin-bottom: 0.75rem;
            }

            .text-center.small {
                margin-bottom: 1rem;
            }

            .row.g-3 {
                gap: 0.75rem !important;
            }
        }
    </style>
</head>

<body>
    <img src="NiceAdmin/NiceAdmin/assets/img/logo.png" alt="">
    <main>
        <div class="container">
            <section class="section register min-vh-100 d-flex flex-column align-items-center justify-content-center py-4">
                <div class="container">
                    <div class="row justify-content-center">
                        <div class="col-lg-4 col-md-6 d-flex flex-column align-items-center justify-content-center">
                            <div class="card mb-3">
                                <div class="card-body">
                                    <div class="text-center mb-4">
                                        <div class="d-flex justify-content-center mb-3">
                                            <a href="index.php" class="logo d-flex align-items-center">
                                                <img src="/views/Landing/photo/logo.png" alt="">
                                                <span>Literature System</span>
                                            </a>
                                        </div>
                                        <h5 class="card-title">Đăng nhập</h5>
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