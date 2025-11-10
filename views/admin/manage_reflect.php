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
    $where[] = "rt.status = :status";
    $params[':status'] = $status;
}

if (!empty($search)) {
    $where[] = "(rt.title LIKE :search OR u.fullname LIKE :search)";
    $params[':search'] = "%$search%";
}

$whereClause = !empty($where) ? "WHERE " . implode(" AND ", $where) : "";

try {
    // Đếm tổng số
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as total 
        FROM reflect_ticket rt 
        JOIN users u ON rt.id_user = u.user_id 
        $whereClause
    ");
    $stmt->execute($params);
    $total = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    $totalPages = ceil($total / $limit);

    // Lấy danh sách reflect - Sửa lại cách bind parameters cho LIMIT và OFFSET
    $query = "
        SELECT rt.*, u.fullname, u.username, u.role as user_role, rtype.name as type_name,
               (SELECT COUNT(*) FROM reflect_detail WHERE id_reflect = rt.id_reflect) as reply_count
        FROM reflect_ticket rt
        JOIN users u ON rt.id_user = u.user_id
        JOIN reflect_type rtype ON rt.id_reflect_type = rtype.id_reflect_type
        $whereClause
        ORDER BY 
            CASE WHEN rt.status = 0 THEN 1 
                 WHEN rt.status = 1 THEN 2 
                 ELSE 3 END,
            rt.created_at DESC
        LIMIT $limit OFFSET $offset
    ";
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $reflects = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Error fetching reflects: " . $e->getMessage());
    $reflects = [];
    $total = 0;
    $totalPages = 0;
}

$pageTitle = "Quản lý Reflect";
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
            <h1>Quản lý Reflect</h1>
            <nav>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="<?= URL_ROOT ?>/views/admin/dashboard.php">Dashboard</a></li>
                    <li class="breadcrumb-item active">Quản lý Reflect</li>
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
                                <option value="0" <?= $status === '0' ? 'selected' : '' ?>>Chờ xử lý</option>
                                <option value="1" <?= $status === '1' ? 'selected' : '' ?>>Đang xử lý</option>
                                <option value="2" <?= $status === '2' ? 'selected' : '' ?>>Đã hoàn thành</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Tìm kiếm</label>
                            <input type="text" name="search" class="form-control" placeholder="Tiêu đề, người gửi..." value="<?= htmlspecialchars($search) ?>">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">&nbsp;</label>
                            <div>
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-search"></i> Tìm kiếm
                                </button>
                                <a href="<?= URL_ROOT ?>/views/admin/manage_reflect.php" class="btn btn-secondary">
                                    <i class="bi bi-arrow-clockwise"></i>
                                </a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Reflects Table -->
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Danh sách Reflect (<?= $total ?? 0 ?>)</h5>

                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th scope="col">#</th>
                                <th scope="col">Tiêu đề</th>
                                <th scope="col">Loại</th>
                                <th scope="col">Người gửi</th>
                                <th scope="col">Trả lời</th>
                                <th scope="col">Trạng thái</th>
                                <th scope="col">Ngày tạo</th>
                                <th scope="col">Thao tác</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($reflects)): ?>
                                <tr>
                                    <td colspan="8" class="text-center">Không có dữ liệu</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($reflects as $reflect): ?>
                                    <tr>
                                        <th scope="row"><?= $reflect['id_reflect'] ?></th>
                                        <td><?= htmlspecialchars($reflect['title']) ?></td>
                                        <td><span class="badge bg-info"><?= htmlspecialchars($reflect['type_name']) ?></span></td>
                                        <td>
                                            <?= htmlspecialchars($reflect['fullname']) ?>
                                            <br><small class="text-muted"><?= htmlspecialchars($reflect['username']) ?></small>
                                        </td>
                                        <td>
                                            <span class="badge bg-secondary">
                                                <i class="bi bi-chat-dots"></i> <?= $reflect['reply_count'] ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php
                                            $statusClass = match ((int)$reflect['status']) {
                                                0 => 'warning',
                                                1 => 'info',
                                                2 => 'success',
                                                default => 'secondary'
                                            };
                                            $statusText = match ((int)$reflect['status']) {
                                                0 => 'Chờ xử lý',
                                                1 => 'Đang xử lý',
                                                2 => 'Đã hoàn thành',
                                                default => 'Không xác định'
                                            };
                                            ?>
                                            <span class="badge bg-<?= $statusClass ?>"><?= $statusText ?></span>
                                        </td>
                                        <td><?= date('d/m/Y H:i', strtotime($reflect['created_at'])) ?></td>
                                        <td>
                                            <a href="<?= URL_ROOT ?>/views/admin/reflect_detail.php?id=<?= $reflect['id_reflect'] ?>"
                                                class="btn btn-sm btn-primary" title="Xem chi tiết">
                                                <i class="bi bi-eye"></i>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>

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

    <?php include __DIR__ . '/../templates/footer.php'; ?>

</body>

</html>