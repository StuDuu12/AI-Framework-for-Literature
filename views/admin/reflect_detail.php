<!-- KHÔNG SỬ DỤNG TRANG NÀY, SỬ DỤNG TRANG TRONG THƯ MỤC /reflect -->
<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
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

$reflectId = $_GET['id'] ?? 0;

// Lấy thông tin reflect
try {
    $stmt = $pdo->prepare("
        SELECT rt.*, u.fullname, u.username, u.email, u.role as user_role, rtype.name as type_name
        FROM reflect_ticket rt
        JOIN users u ON rt.id_user = u.user_id
        JOIN reflect_type rtype ON rt.id_reflect_type = rtype.id_reflect_type
        WHERE rt.id_reflect = :id
    ");
    $stmt->execute([':id' => $reflectId]);
    $reflect = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$reflect) {
        header("Location: " . URL_ROOT . "/views/admin/manage_reflect.php");
        exit();
    }

    // Lấy tất cả replies
    $stmt = $pdo->prepare("
        SELECT rd.*, u.fullname, u.username, u.role as user_role
        FROM reflect_detail rd
        JOIN users u ON rd.id_user = u.user_id
        WHERE rd.id_reflect = :id
        ORDER BY rd.created_at ASC
    ");
    $stmt->execute([':id' => $reflectId]);
    $replies = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Error fetching reflect detail: " . $e->getMessage());
    header("Location: " . URL_ROOT . "/views/admin/manage_reflect.php");
    exit();
}

// Xử lý cập nhật trạng thái
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $newStatus = $_POST['status'];
    try {
        $stmt = $pdo->prepare("UPDATE reflect_ticket SET status = :status WHERE id_reflect = :id");
        $stmt->execute([':status' => $newStatus, ':id' => $reflectId]);
        header("Location: " . $_SERVER['PHP_SELF'] . "?id=" . $reflectId);
        exit();
    } catch (PDOException $e) {
        error_log("Error updating status: " . $e->getMessage());
    }
}

// Xử lý thêm comment mới
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_comment'])) {
    $content = trim($_POST['content'] ?? '');

    if (!empty($content)) {
        try {
            $stmt = $pdo->prepare("
                INSERT INTO reflect_detail (content, id_user, id_reflect, created_at) 
                VALUES (:content, :user_id, :reflect_id, NOW())
            ");
            $stmt->execute([
                ':content' => $content,
                ':user_id' => $_SESSION['user_id'],
                ':reflect_id' => $reflectId
            ]);
            header("Location: " . $_SERVER['PHP_SELF'] . "?id=" . $reflectId);
            exit();
        } catch (PDOException $e) {
            error_log("Error adding comment: " . $e->getMessage());
            $error = "Cannot add response. Please try again!";
        }
    } else {
        $error = "Content cannot be empty!";
    }
}

$pageTitle = "Details Reflect";
?>
<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="utf-8">
    <meta content="width=device-width, initial-scale=1.0" name="viewport">
    <title data-lang-key="reflect_detail"><?= $pageTitle ?> - Literature System</title>
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
            <h1 data-lang-key="reflect_detail">Feedback Details</h1>
            <nav>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="<?= URL_ROOT ?>/views/admin/dashboard.php" data-lang-key="menu_dashboard">Trang chủ</a></li>
                    <li class="breadcrumb-item"><a href="<?= URL_ROOT ?>/views/admin/manage_reflect.php" data-lang-key="manage_reflect_title">Quản lý góp ý</a></li>
                    <li class="breadcrumb-item active" data-lang-key="details">Chi tiết</li>
                </ol>
            </nav>
        </div><!-- End Page Title -->

        <section class="section">
            <div class="row">
                <div class="col-lg-8">
                    <!-- Reflect Card -->
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title"><?= htmlspecialchars($reflect['title']) ?></h5>

                            <div class="d-flex align-items-center mb-3">
                                <div class="rounded-circle bg-primary d-flex align-items-center justify-content-center me-3"
                                    style="width: 48px; height: 48px;">
                                    <i class="bi bi-person-fill text-white fs-4"></i>
                                </div>
                                <div>
                                    <h6 class="mb-0"><?= htmlspecialchars($reflect['fullname']) ?></h6>
                                    <small class="text-muted">
                                        <?= htmlspecialchars($reflect['username']) ?> |
                                        <?= date('d/m/Y H:i', strtotime($reflect['created_at'])) ?>
                                    </small>
                                </div>
                            </div>

                            <!-- Replies -->
                            <div class="mt-4">
                                <h6 class="mb-3">
                                    <i class="bi bi-chat-dots me-2"></i><span>Bài làm</span> (<?= count($replies) ?>)
                                </h6>

                                <?php if (isset($error)): ?>
                                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                        <i class="bi bi-exclamation-triangle me-2"></i><?= $error ?>
                                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Đóng"></button>
                                    </div>
                                <?php endif; ?>

                                <?php if (empty($replies)): ?>
                                    <div class="alert alert-info">
                                        <i class="bi bi-info-circle me-2"></i><span>Chưa có bài làm</span>
                                    </div>
                                <?php else: ?>
                                    <?php foreach ($replies as $reply): ?>
                                        <div class="card mb-3">
                                            <div class="card-body">
                                                <div class="d-flex align-items-start">
                                                    <div class="rounded-circle bg-<?= $reply['user_role'] === 'teacher' ? 'success' : ($reply['user_role'] === 'admin' ? 'danger' : 'secondary') ?> d-flex align-items-center justify-content-center me-3"
                                                        style="width: 40px; height: 40px; min-width: 40px;">
                                                        <i class="bi bi-person-fill text-white"></i>
                                                    </div>
                                                    <div class="flex-grow-1">
                                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                                            <div>
                                                                <strong><?= htmlspecialchars($reply['fullname']) ?></strong>
                                                                <?php if ($reply['user_role'] === 'teacher'): ?>
                                                                    <span class="badge bg-success ms-2">Giáo viên</span>
                                                                <?php elseif ($reply['user_role'] === 'admin'): ?>
                                                                    <span class="badge bg-danger ms-2">Admin</span>
                                                                <?php else: ?>
                                                                    <span class="badge bg-secondary ms-2">Học sinh</span>
                                                                <?php endif; ?>
                                                            </div>
                                                            <small class="text-muted">
                                                                <?= date('d/m/Y H:i', strtotime($reply['created_at'])) ?>
                                                            </small>
                                                        </div>
                                                        <p class="mb-0"><?= nl2br(htmlspecialchars($reply['content'])) ?></p>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>

                                <!-- Add Comment Form -->
                                <div class="card mt-4">
                                    <div class="card-body">
                                        <h6 class="card-title">
                                            <i class="bi bi-pencil-square me-2"></i><span>Add Response</span>
                                        </h6>
                                        <form method="POST">
                                            <div class="mb-3">
                                                <textarea aria-label="Nhập nội dung bài làm" name="content" class="form-control" rows="4"
                                                    placeholder="Nhập nội dung bài làm…" required></textarea>
                                            </div>
                                            <div class="d-grid">
                                                <button type="submit" name="add_comment" class="btn btn-primary">
                                                    <i class="bi bi-send me-2"></i><span>Send Response</span>
                                                </button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-4">
                    <!-- Info Card -->
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title">Thông tin</h5>

                            <div class="mb-3">
                                <label class="form-label text-muted">Loại</label>
                                <div>
                                    <span class="badge bg-info"><?= htmlspecialchars($reflect['type_name']) ?></span>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label text-muted">Người gửi</label>
                                <div>
                                    <strong><?= htmlspecialchars($reflect['fullname']) ?></strong><br>
                                    <small><?= htmlspecialchars($reflect['email'] ?? 'N/A') ?></small>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label text-muted">Reply Count</label>
                                <div>
                                    <span class="badge bg-secondary">
                                        <i class="bi bi-chat-dots"></i> <?= count($replies) ?>
                                    </span>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label text-muted">Created Date</label>
                                <div><?= date('d/m/Y H:i:s', strtotime($reflect['created_at'])) ?></div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label text-muted">Last Updated</label>
                                <div><?= date('d/m/Y H:i:s', strtotime($reflect['updated_at'])) ?></div>
                            </div>

                            <hr>

                            <!-- Update Status -->
                            <form method="POST">
                                <div class="mb-3">
                                    <label class="form-label">Trạng thái</label>
                                    <select aria-label="Trạng thái" name="status" class="form-select">
                                        <option value="0" <?= $reflect['status'] == 0 ? 'selected' : '' ?>>Đang chờ</option>
                                        <option value="1" <?= $reflect['status'] == 1 ? 'selected' : '' ?>>In Progress</option>
                                        <option value="2" <?= $reflect['status'] == 2 ? 'selected' : '' ?>>Đã hoàn thành</option>
                                    </select>
                                </div>
                                <div class="d-grid gap-2">
                                    <button type="submit" name="update_status" class="btn btn-primary">
                                        <i class="bi bi-check-circle"></i> <span>Update Status</span>
                                    </button>
                                    <a href="<?= URL_ROOT ?>/views/admin/manage_reflect.php" class="btn btn-secondary">
                                        <i class="bi bi-arrow-left"></i> <span>Go Back</span>
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
    require_once __DIR__ . '/../templates/footer.php';
    require_once __DIR__ . '/../templates/vendors.php';
    ?>


</body>

</html>