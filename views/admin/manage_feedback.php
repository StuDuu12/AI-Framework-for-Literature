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

$status = $_GET['status'] ?? 'all';
$search = $_GET['search'] ?? '';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 20;
$offset = ($page - 1) * $limit;

// Xây dựng query
$where = [];
$params = [];

if ($status !== 'all') {
    $where[] = "f.status = :status";
    $params[':status'] = $status;
}

if (!empty($search)) {
    $where[] = "(f.subject LIKE :search OR f.message LIKE :search OR u.fullname LIKE :search)";
    $params[':search'] = "%$search%";
}

$whereClause = !empty($where) ? "WHERE " . implode(" AND ", $where) : "";

try {
    // Đếm tổng số
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as total 
        FROM feedback f 
        JOIN users u ON f.user_id = u.user_id 
        $whereClause
    ");
    $stmt->execute($params);
    $total = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    $totalPages = ceil($total / $limit);

    // Lấy danh sách feedback
    $query = "
        SELECT f.*, u.fullname, u.username, u.email
        FROM feedback f
        JOIN users u ON f.user_id = u.user_id
        $whereClause
        ORDER BY 
            CASE WHEN f.status = 'pending' THEN 1 
                 WHEN f.status = 'reviewed' THEN 2 
                 ELSE 3 END,
            f.created_at DESC
        LIMIT $limit OFFSET $offset
    ";
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $feedbacks = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Error fetching feedbacks: " . $e->getMessage());
    $feedbacks = [];
    $total = 0;
    $totalPages = 0;
}

$pageTitle = "Quản lý góp ý";
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
            <h1>Quản lý góp ý</h1>
            <nav>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="<?= URL_ROOT ?>/views/admin/dashboard.php">Dashboard</a></li>
                    <li class="breadcrumb-item active">Quản lý góp ý</li>
                </ol>
            </nav>
        </div><!-- End Page Title -->

        <section class="section">
            <!-- Filters and Search -->
            <div class="card mb-4">
                <div class="card-body">
                    <h5 class="card-title">Bộ lọc</h5>
                    <form method="GET" class="row g-3">
                        <div class="col-md-3">
                            <label class="form-label">Trạng thái</label>
                            <select name="status" class="form-select" onchange="this.form.submit()">
                                <option value="all" <?= $status === 'all' ? 'selected' : '' ?>>Tất cả</option>
                                <option value="pending" <?= $status === 'pending' ? 'selected' : '' ?>>Chờ xử lý</option>
                                <option value="reviewed" <?= $status === 'reviewed' ? 'selected' : '' ?>>Đã xem</option>
                                <option value="resolved" <?= $status === 'resolved' ? 'selected' : '' ?>>Đã giải quyết</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Tìm kiếm</label>
                            <input type="text" name="search" class="form-control" placeholder="Tiêu đề, nội dung, người gửi..." value="<?= htmlspecialchars($search) ?>">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">&nbsp;</label>
                            <div>
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-search"></i> Tìm kiếm
                                </button>
                                <a href="<?= URL_ROOT ?>/views/admin/manage_feedback.php" class="btn btn-secondary">
                                    <i class="bi bi-arrow-clockwise"></i>
                                </a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Feedbacks Table -->
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Danh sách góp ý (<?= $total ?? 0 ?>)</h5>

                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th scope="col">#</th>
                                    <th scope="col">Tiêu đề</th>
                                    <th scope="col">Người gửi</th>
                                    <th scope="col">Trạng thái</th>
                                    <th scope="col">Ngày tạo</th>
                                    <th scope="col">Thao tác</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($feedbacks)): ?>
                                    <tr>
                                        <td colspan="6" class="text-center text-muted">
                                            <i class="bi bi-inbox fs-1 d-block mb-2"></i>
                                            Chưa có góp ý nào
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($feedbacks as $feedback): ?>
                                        <tr>
                                            <th scope="row"><?= $feedback['feedback_id'] ?></th>
                                            <td><?= htmlspecialchars($feedback['subject']) ?></td>
                                            <td>
                                                <?= htmlspecialchars($feedback['fullname']) ?>
                                                <br><small class="text-muted"><?= htmlspecialchars($feedback['email'] ?? 'N/A') ?></small>
                                            </td>
                                            <td>
                                                <?php
                                                $statusClass = match ($feedback['status']) {
                                                    'pending' => 'warning',
                                                    'reviewed' => 'info',
                                                    'resolved' => 'success',
                                                    default => 'secondary'
                                                };
                                                $statusText = match ($feedback['status']) {
                                                    'pending' => 'Chờ xử lý',
                                                    'reviewed' => 'Đã xem',
                                                    'resolved' => 'Đã giải quyết',
                                                    default => 'Không xác định'
                                                };
                                                ?>
                                                <span class="badge bg-<?= $statusClass ?>"><?= $statusText ?></span>
                                            </td>
                                            <td><?= date('d/m/Y H:i', strtotime($feedback['created_at'])) ?></td>
                                            <td>
                                                <button class="btn btn-sm btn-primary view-feedback"
                                                    data-bs-toggle="modal"
                                                    data-bs-target="#feedbackModal"
                                                    data-id="<?= $feedback['feedback_id'] ?>"
                                                    data-subject="<?= htmlspecialchars($feedback['subject']) ?>"
                                                    data-message="<?= htmlspecialchars($feedback['message']) ?>"
                                                    data-sender="<?= htmlspecialchars($feedback['fullname']) ?>"
                                                    data-email="<?= htmlspecialchars($feedback['email'] ?? '') ?>"
                                                    data-status="<?= $feedback['status'] ?>"
                                                    data-response="<?= htmlspecialchars($feedback['admin_response'] ?? '') ?>"
                                                    title="Xem chi tiết">
                                                    <i class="bi bi-eye"></i>
                                                </button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <?php if ($totalPages > 1): ?>
                        <nav aria-label="Page navigation">
                            <ul class="pagination justify-content-center">
                                <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
                                    <a class="page-link" href="?status=<?= $status ?>&search=<?= urlencode($search) ?>&page=<?= $page - 1 ?>">
                                        Trước
                                    </a>
                                </li>
                                <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                                    <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                                        <a class="page-link" href="?status=<?= $status ?>&search=<?= urlencode($search) ?>&page=<?= $i ?>">
                                            <?= $i ?>
                                        </a>
                                    </li>
                                <?php endfor; ?>
                                <li class="page-item <?= $page >= $totalPages ? 'disabled' : '' ?>">
                                    <a class="page-link" href="?status=<?= $status ?>&search=<?= urlencode($search) ?>&page=<?= $page + 1 ?>">
                                        Sau
                                    </a>
                                </li>
                            </ul>
                        </nav>
                    <?php endif; ?>
                </div>
            </div>
        </section>
    </main><!-- End #main -->

    <!-- Feedback Modal -->
    <div class="modal fade" id="feedbackModal" tabindex="-1" aria-labelledby="feedbackModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="feedbackModalLabel">Chi tiết góp ý</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Người gửi</label>
                        <p id="modal-sender"></p>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Email</label>
                        <p id="modal-email"></p>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Tiêu đề</label>
                        <p id="modal-subject"></p>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Nội dung</label>
                        <p id="modal-message" style="white-space: pre-wrap;"></p>
                    </div>
                    <hr>
                    <form id="responseForm">
                        <input type="hidden" id="feedback-id" name="feedback_id">
                        <div class="mb-3">
                            <label class="form-label fw-bold">Trạng thái</label>
                            <select class="form-select" id="feedback-status" name="status">
                                <option value="pending">Chờ xử lý</option>
                                <option value="reviewed">Đã xem</option>
                                <option value="resolved">Đã giải quyết</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold">Phản hồi của admin</label>
                            <textarea class="form-control" id="admin-response" name="admin_response" rows="4" placeholder="Nhập phản hồi..."></textarea>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button>
                    <button type="button" class="btn btn-primary" id="saveResponse">
                        <i class="bi bi-save"></i> Lưu phản hồi
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
        // View feedback details
        document.querySelectorAll('.view-feedback').forEach(btn => {
            btn.addEventListener('click', function() {
                document.getElementById('feedback-id').value = this.dataset.id;
                document.getElementById('modal-sender').textContent = this.dataset.sender;
                document.getElementById('modal-email').textContent = this.dataset.email || 'N/A';
                document.getElementById('modal-subject').textContent = this.dataset.subject;
                document.getElementById('modal-message').textContent = this.dataset.message;
                document.getElementById('feedback-status').value = this.dataset.status;
                document.getElementById('admin-response').value = this.dataset.response;
            });
        });

        // Save response
        document.getElementById('saveResponse').addEventListener('click', function() {
            const formData = new FormData(document.getElementById('responseForm'));
            const data = {
                action: 'respond_feedback',
                feedback_id: formData.get('feedback_id'),
                status: formData.get('status'),
                admin_response: formData.get('admin_response')
            };

            fetch('<?= URL_ROOT ?>/api/admin_actions.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(data)
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('Lưu phản hồi thành công!');
                        location.reload();
                    } else {
                        alert('Lỗi: ' + (data.message || 'Không thể lưu phản hồi'));
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Có lỗi xảy ra!');
                });
        });
    </script>

    <?php include __DIR__ . '/../templates/footer.php'; ?>

</body>

</html>