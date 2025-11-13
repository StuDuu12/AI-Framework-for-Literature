<?php
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

$userId = $_GET['id'] ?? 0;
$error = '';
$success = '';

// Lấy thông tin user
try {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE user_id = :id");
    $stmt->execute([':id' => $userId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        header("Location: " . URL_ROOT . "/views/admin/manage_users.php");
        exit();
    }
} catch (PDOException $e) {
    error_log("Error fetching user: " . $e->getMessage());
    header("Location: " . URL_ROOT . "/views/admin/manage_users.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullname = trim($_POST['fullname'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $role = $_POST['role'] ?? 'student';

    // Validation
    if (empty($fullname) || empty($username)) {
        $error = "Vui lòng điền đầy đủ thông tin bắt buộc!";
    } else {
        try {
            // Kiểm tra username đã tồn tại (trừ chính user này)
            $stmt = $pdo->prepare("SELECT user_id FROM users WHERE username = :username AND user_id != :id");
            $stmt->execute([':username' => $username, ':id' => $userId]);
            if ($stmt->fetch()) {
                $error = "Username đã tồn tại!";
            } else {
                // Cập nhật user
                if (!empty($password)) {
                    $stmt = $pdo->prepare("UPDATE users SET fullname = :fullname, username = :username, email = :email, password_hash = :password, role = :role WHERE user_id = :id");
                    $stmt->execute([
                        ':fullname' => $fullname,
                        ':username' => $username,
                        ':email' => !empty($email) ? $email : null,
                        ':password' => password_hash($password, PASSWORD_DEFAULT),
                        ':role' => $role,
                        ':id' => $userId
                    ]);
                } else {
                    $stmt = $pdo->prepare("UPDATE users SET fullname = :fullname, username = :username, email = :email, role = :role WHERE user_id = :id");
                    $stmt->execute([
                        ':fullname' => $fullname,
                        ':username' => $username,
                        ':email' => !empty($email) ? $email : null,
                        ':role' => $role,
                        ':id' => $userId
                    ]);
                }

                $success = "Cập nhật tài khoản thành công!";

                // Cập nhật lại thông tin user để hiển thị
                $user['fullname'] = $fullname;
                $user['username'] = $username;
                $user['email'] = $email;
                $user['role'] = $role;

                // Redirect sau 2 giây
                header("refresh:2;url=" . URL_ROOT . "/views/admin/manage_users.php?role=" . $role);
            }
        } catch (PDOException $e) {
            error_log("Error updating user: " . $e->getMessage());
            $error = "Có lỗi xảy ra khi cập nhật tài khoản!";
        }
    }
}

$pageTitle = "Sửa tài khoản";
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
            <h1>Sửa tài khoản</h1>
            <nav>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="<?= URL_ROOT ?>/views/admin/dashboard.php">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="<?= URL_ROOT ?>/views/admin/manage_users.php">Quản lý người dùng</a></li>
                    <li class="breadcrumb-item active">Sửa tài khoản</li>
                </ol>
            </nav>
        </div><!-- End Page Title -->

        <section class="section">
            <div class="row justify-content-center">
                <div class="col-lg-8">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title">
                                <i class="bi bi-pencil me-2"></i>Chỉnh sửa thông tin tài khoản
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
                                    <input type="text" name="fullname" class="form-control" required value="<?= htmlspecialchars($user['fullname']) ?>">
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">Username <span class="text-danger">*</span></label>
                                    <input type="text" name="username" class="form-control" required value="<?= htmlspecialchars($user['username']) ?>">
                                    <small class="form-text text-muted">Username dùng để đăng nhập</small>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">Email</label>
                                    <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($user['email'] ?? '') ?>">
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">Mật khẩu mới</label>
                                    <input type="password" name="password" class="form-control">
                                    <small class="form-text text-muted">Để trống nếu không muốn đổi mật khẩu</small>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">Vai trò <span class="text-danger">*</span></label>
                                    <select name="role" class="form-select" required>
                                        <option value="student" <?= $user['role'] === 'student' ? 'selected' : '' ?>>Học sinh</option>
                                        <option value="teacher" <?= $user['role'] === 'teacher' ? 'selected' : '' ?>>Giáo viên</option>
                                        <option value="admin" <?= $user['role'] === 'admin' ? 'selected' : '' ?>>Admin</option>
                                    </select>
                                </div>

                                <div class="d-grid gap-2">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="bi bi-save me-1"></i> Lưu thay đổi
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