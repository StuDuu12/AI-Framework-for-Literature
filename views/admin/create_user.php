<?php
session_start();
require_once __DIR__ . '/../../config/env.php';
require_once __DIR__ . '/../../config/db.php';

// Kiểm tra đăng nhập
if (!isset($_SESSION['user']) || empty($_SESSION['user'])) {
    header("Location: " . URL_ROOT . "/login");
    exit();
}

// Kiểm tra quyền admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: " . URL_ROOT . "/index.php");
    exit();
}

$roleParam = $_GET['role'] ?? 'student';
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullname = trim($_POST['fullname'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $role = $_POST['role'] ?? 'student';

    // Validation
    if (empty($fullname) || empty($username) || empty($password)) {
        $error = "Vui lòng điền đầy đủ thông tin bắt buộc!";
    } else {
        try {
            // Kiểm tra username đã tồn tại
            $stmt = $pdo->prepare("SELECT user_id FROM users WHERE username = :username");
            $stmt->execute([':username' => $username]);
            if ($stmt->fetch()) {
                $error = "Username đã tồn tại!";
            } else {
                // Thêm user mới
                $stmt = $pdo->prepare("INSERT INTO users (fullname, username, email, password_hash, role) VALUES (:fullname, :username, :email, :password, :role)");
                $stmt->execute([
                    ':fullname' => $fullname,
                    ':username' => $username,
                    ':email' => !empty($email) ? $email : null,
                    ':password' => password_hash($password, PASSWORD_DEFAULT),
                    ':role' => $role
                ]);

                $success = "Tạo tài khoản thành công!";

                // Redirect sau 2 giây
                header("refresh:2;url=" . URL_ROOT . "/views/admin/manage_users.php?role=" . $role);
            }
        } catch (PDOException $e) {
            error_log("Error creating user: " . $e->getMessage());
            $error = "Có lỗi xảy ra khi tạo tài khoản!";
        }
    }
}

$pageTitle = "Tạo tài khoản";
?>
<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="utf-8">
    <meta content="width=device-width, initial-scale=1.0" name="viewport">
    <title><?= $pageTitle ?> - Literature System</title>
    <meta content="" name="description">
    <meta content="" name="keywords">

    <?php include __DIR__ . '/../templates/assets.php'; ?>
</head>

<body>
    <?php
    include __DIR__ . '/../templates/header.php';
    include __DIR__ . '/../templates/sidebar_admin.php';
    ?>

    <main id="main" class="main">
        <div class="pagetitle">
            <h1>Tạo tài khoản mới</h1>
            <nav>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="<?= URL_ROOT ?>/views/admin/dashboard.php">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="<?= URL_ROOT ?>/views/admin/manage_users.php">Quản lý người dùng</a></li>
                    <li class="breadcrumb-item active">Tạo tài khoản</li>
                </ol>
            </nav>
        </div><!-- End Page Title -->

        <section class="section">
            <div class="row justify-content-center">
                <div class="col-lg-8">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title">
                                <i class="bi bi-person-plus me-2"></i>Thông tin tài khoản
                            </h5>
                            <?php if ($error): ?>
                                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                    <i class="bi bi-exclamation-triangle me-2"></i><?= $error ?>
                                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                                </div>
                            <?php endif; ?>

                            <?php if ($success): ?>
                                <div class="alert alert-success alert-dismissible fade show" role="alert">
                                    <i class="bi bi-check-circle me-2"></i><?= $success ?>
                                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                                </div>
                            <?php endif; ?>

                            <form method="POST" action="">
                                <div class="mb-3">
                                    <label class="form-label">Họ và tên <span class="text-danger">*</span></label>
                                    <input type="text" name="fullname" class="form-control" required value="<?= htmlspecialchars($_POST['fullname'] ?? '') ?>">
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">Username <span class="text-danger">*</span></label>
                                    <input type="text" name="username" class="form-control" required value="<?= htmlspecialchars($_POST['username'] ?? '') ?>">
                                    <small class="form-text text-muted">Username dùng để đăng nhập</small>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">Email</label>
                                    <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">Mật khẩu <span class="text-danger">*</span></label>
                                    <input type="password" name="password" class="form-control" required>
                                    <small class="form-text text-muted">Tối thiểu 6 ký tự</small>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">Vai trò <span class="text-danger">*</span></label>
                                    <select name="role" class="form-select" required>
                                        <option value="student" <?= ($roleParam === 'student' || ($_POST['role'] ?? '') === 'student') ? 'selected' : '' ?>>Học sinh</option>
                                        <option value="teacher" <?= ($roleParam === 'teacher' || ($_POST['role'] ?? '') === 'teacher') ? 'selected' : '' ?>>Giáo viên</option>
                                        <option value="admin" <?= ($_POST['role'] ?? '') === 'admin' ? 'selected' : '' ?>>Admin</option>
                                    </select>
                                </div>

                                <div class="d-grid gap-2">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="bi bi-save me-1"></i> Tạo tài khoản
                                    </button>
                                    <a href="<?= URL_ROOT ?>/views/admin/manage_users.php" class="btn btn-secondary">
                                        <i class="bi bi-x-circle me-1"></i> Hủy
                                    </a>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </main><!-- End #main -->

    <?php
    require_once 'views/templates/footer.php';
    require_once 'views/templates/vendors.php';
    ?>
</body>

</html>