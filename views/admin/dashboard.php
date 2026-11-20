<?php
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

// Lấy thống kê
try {
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM users WHERE role = 'teacher'");
    $totalTeachers = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

    $stmt = $pdo->query("SELECT COUNT(*) as total FROM users WHERE role = 'student'");
    $totalStudents = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

    // Kiểm tra bảng feedback có tồn tại không
    $pendingFeedback = 0;
    try {
        $stmt = $pdo->query("SELECT COUNT(*) as total FROM feedback WHERE status = 'pending'");
        $pendingFeedback = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    } catch (PDOException $e) {
        // Bảng feedback chưa tồn tại
        $pendingFeedback = 0;
    }

    // Sửa tên bảng từ 'classes' thành 'class'
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM class");
    $totalClasses = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

    // Thống kê reflect chờ xử lý
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM reflect_ticket WHERE status = 0");
    $pendingReflect = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
} catch (PDOException $e) {
    error_log("Error fetching admin stats: " . $e->getMessage());
    $totalTeachers = 0;
    $totalStudents = 0;
    $pendingFeedback = 0;
    $totalClasses = 0;
    $pendingReflect = 0;
}

$pageTitle = "Admin Dashboard";
?>
<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="utf-8">
    <meta content="width=device-width, initial-scale=1.0" name="viewport">
    <title data-lang-key="admin_dashboard"><?= $pageTitle ?> - Literature System</title>
    <meta content="" name="description">
    <meta content="" name="keywords">

    <?php require_once __DIR__ . '/../templates/assets.php'; ?>
</head>

<body>
    <?php
    require_once __DIR__ . '/../templates/header.php';
    require_once __DIR__ . '/../templates/sidebar_admin.php';
    ?>

    <main id="main" class="main">
        <div class="pagetitle">
            <h1 data-lang-key="admin_dashboard">Tổng quan quản trị</h1>
            <nav>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item active" data-lang-key="menu_dashboard">Trang chủ</li>
                </ol>
            </nav>
        </div><!-- End Page Title -->

        <section class="section dashboard">
            <div class="row">
                <!-- Statistics Cards -->
                <div class="col-lg-12">
                    <div class="row" data-stagger>
                        <!-- Teachers Card -->
                        <div class="col-xxl-3 col-md-6">
                            <div class="card info-card sales-card">
                                <div class="card-body">
                                    <h5 class="card-title" data-lang-key="teachers">Giáo viên</h5>
                                    <div class="d-flex align-items-center">
                                        <div class="card-icon rounded-circle d-flex align-items-center justify-content-center" style="background: #e8ebfd; color: var(--lit-primary);">
                                            <i class="bi bi-person-badge"></i>
                                        </div>
                                        <div class="ps-3">
                                            <h6><?= $totalTeachers ?? 0 ?></h6>
                                            <span class="text-muted small pt-2 ps-1">người dùng</span>
                                        </div>
                                    </div>
                                    <div class="mt-3">
                                        <a href="<?= URL_ROOT ?>/admin/users?role=teacher" class="btn btn-sm btn-outline-primary">
                                            <i class="bi bi-eye"></i> <span data-lang-key="view_details">Xem chi tiết</span>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div><!-- End Teachers Card -->

                        <!-- Students Card -->
                        <div class="col-xxl-3 col-md-6">
                            <div class="card info-card revenue-card">
                                <div class="card-body">
                                    <h5 class="card-title" data-lang-key="students">Học sinh</h5>
                                    <div class="d-flex align-items-center">
                                        <div class="card-icon rounded-circle d-flex align-items-center justify-content-center" style="background: #e8f5e9; color: #4caf50;">
                                            <i class="bi bi-people"></i>
                                        </div>
                                        <div class="ps-3">
                                            <h6><?= $totalStudents ?? 0 ?></h6>
                                            <span class="text-muted small pt-2 ps-1">người dùng</span>
                                        </div>
                                    </div>
                                    <div class="mt-3">
                                        <a href="<?= URL_ROOT ?>/admin/users?role=student" class="btn btn-sm btn-outline-success">
                                            <i class="bi bi-eye"></i> <span data-lang-key="view_details">Xem chi tiết</span>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div><!-- End Students Card -->


                        <!-- Pending Reflect Card -->
                        <div class="col-xxl-3 col-md-6">
                            <div class="card info-card">
                                <div class="card-body">
                                    <h5 class="card-title" data-lang-key="pending_feedback">Góp ý đang chờ</h5>
                                    <div class="d-flex align-items-center">
                                        <div class="card-icon rounded-circle d-flex align-items-center justify-content-center" style="background: #fce4ec; color: #e91e63;">
                                            <i class="bi bi-journal-text"></i>
                                        </div>
                                        <div class="ps-3">
                                            <h6><?= $pendingReflect ?? 0 ?></h6>
                                            <span class="text-muted small pt-2 ps-1">góp ý</span>
                                        </div>
                                    </div>
                                    <div class="mt-3">
                                        <a href="<?= URL_ROOT ?>/reflect" class="btn btn-sm btn-outline-danger">
                                            <i class="bi bi-eye"></i> <span data-lang-key="view_details">Xem chi tiết</span>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div><!-- End Reflect Card -->

                        <!-- Classes Card -->
                        <div class="col-xxl-3 col-md-6">
                            <div class="card info-card">
                                <div class="card-body">
                                    <h5 class="card-title" data-lang-key="classes">Lớp học</h5>
                                    <div class="d-flex align-items-center">
                                        <div class="card-icon rounded-circle d-flex align-items-center justify-content-center" style="background: #e1f5fe; color: #00bcd4;">
                                            <i class="bi bi-book"></i>
                                        </div>
                                        <div class="ps-3">
                                            <h6><?= $totalClasses ?? 0 ?></h6>
                                            <span class="text-muted small pt-2 ps-1">lớp</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div><!-- End Classes Card -->
                    </div>
                </div><!-- End Left side columns -->
            </div>

            <!-- Quick Actions -->
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title" data-lang-key="quick_actions">Thao tác nhanh</h5>
                            <div class="row">
                                <div class="col-md-3 mb-3">
                                    <div class="d-grid">
                                        <a href="<?= URL_ROOT ?>/admin/users/create?role=teacher" class="btn btn-primary">
                                            <i class="bi bi-person-plus me-2"></i><span data-lang-key="create_teacher_account">Tạo tài khoản giáo viên</span>
                                        </a>
                                    </div>
                                </div>
                                <div class="col-md-3 mb-3">
                                    <div class="d-grid">
                                        <a href="<?= URL_ROOT ?>/admin/users/create?role=student" class="btn btn-success">
                                            <i class="bi bi-person-plus me-2"></i><span data-lang-key="create_student_account">Tạo tài khoản học sinh</span>
                                        </a>
                                    </div>
                                </div>
                                <!-- <div class="col-md-3 mb-3">
                                    <div class="d-grid">
                                        <a href="<?= URL_ROOT ?>/views/admin/manage_feedback.php" class="btn btn-warning">
                                            <i class="bi bi-chat-left-text me-2"></i>Quản lý góp ý
                                        </a>
                                    </div>
                                </div> -->
                                <div class="col-md-3 mb-3">
                                    <div class="d-grid">
                                        <a href="<?= URL_ROOT ?>/reflect" class="btn btn-info">
                                            <i class="bi bi-journal-text me-2"></i><span data-lang-key="manage_feedback">Quản lý góp ý</span>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </main><!-- End #main -->

    <?php
    require_once __DIR__ . '/../templates/footer.php';
    require_once __DIR__ . '/../templates/vendors.php';
    ?>

</body>

</html>
