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

$role = $_GET['role'] ?? 'all';
$search = $_GET['search'] ?? '';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 20;
$offset = ($page - 1) * $limit;

// Xây dựng query
$where = [];
$params = [];

if ($role !== 'all') {
    $where[] = "role = :role";
    $params[':role'] = $role;
}

if (!empty($search)) {
    $where[] = "(fullname LIKE :search OR username LIKE :search OR email LIKE :search)";
    $params[':search'] = "%$search%";
}

$whereClause = !empty($where) ? "WHERE " . implode(" AND ", $where) : "";

try {
    // Đếm tổng số
    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM users $whereClause");
    $stmt->execute($params);
    $total = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    $totalPages = ceil($total / $limit);

    // Lấy danh sách users - Sửa lại cách bind parameters cho LIMIT và OFFSET
    $query = "SELECT * FROM users $whereClause ORDER BY created_at DESC LIMIT $limit OFFSET $offset";
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Error fetching users: " . $e->getMessage());
    $users = [];
    $total = 0;
    $totalPages = 0;
}

$pageTitle = "Quản lý người dùng";
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
            <h1>Quản lý người dùng</h1>
            <nav>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="<?= URL_ROOT ?>/views/admin/dashboard.php">Dashboard</a></li>
                    <li class="breadcrumb-item active">Quản lý người dùng</li>
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
                            <label class="form-label">Vai trò</label>
                            <select name="role" class="form-select" onchange="this.form.submit()">
                                <option value="all" <?= $role === 'all' ? 'selected' : '' ?>>Tất cả</option>
                                <option value="admin" <?= $role === 'admin' ? 'selected' : '' ?>>Admin</option>
                                <option value="teacher" <?= $role === 'teacher' ? 'selected' : '' ?>>Giáo viên</option>
                                <option value="student" <?= $role === 'student' ? 'selected' : '' ?>>Học sinh</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Tìm kiếm</label>
                            <input type="text" name="search" class="form-control" placeholder="Tên, username, email..." value="<?= htmlspecialchars($search) ?>">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">&nbsp;</label>
                            <div>
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-search"></i> Tìm kiếm
                                </button>
                                <a href="<?= URL_ROOT ?>/views/admin/manage_users.php" class="btn btn-secondary">
                                    <i class="bi bi-arrow-clockwise"></i>
                                </a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Users Table -->
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="card-title">Danh sách người dùng (<?= $total ?? 0 ?>)</h5>
                        <div>
                            <a href="<?= URL_ROOT ?>/views/admin/create_user.php?role=teacher" class="btn btn-sm btn-primary">
                                <i class="bi bi-person-plus"></i> Thêm giáo viên
                            </a>
                            <a href="<?= URL_ROOT ?>/views/admin/create_user.php?role=student" class="btn btn-sm btn-success">
                                <i class="bi bi-person-plus"></i> Thêm học sinh
                            </a>
                        </div>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Họ tên</th>
                                    <th>Username</th>
                                    <th>Email</th>
                                    <th>Vai trò</th>
                                    <th>Ngày tạo</th>
                                    <th>Thao tác</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($users)): ?>
                                    <tr>
                                        <td colspan="7" class="text-center">Không có dữ liệu</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($users as $user): ?>
                                        <tr>
                                            <td><?= $user['user_id'] ?></td>
                                            <td><?= htmlspecialchars($user['fullname']) ?></td>
                                            <td><?= htmlspecialchars($user['username']) ?></td>
                                            <td><?= htmlspecialchars($user['email'] ?? 'N/A') ?></td>
                                            <td>
                                                <?php
                                                $roleClass = match ($user['role']) {
                                                    'admin' => 'danger',
                                                    'teacher' => 'primary',
                                                    'student' => 'success',
                                                    default => 'secondary'
                                                };
                                                $roleText = match ($user['role']) {
                                                    'admin' => 'Admin',
                                                    'teacher' => 'Giáo viên',
                                                    'student' => 'Học sinh',
                                                    default => $user['role']
                                                };
                                                ?>
                                                <span class="badge bg-<?= $roleClass ?>"><?= $roleText ?></span>
                                            </td>
                                            <td><?= date('d/m/Y H:i', strtotime($user['created_at'])) ?></td>
                                            <td>
                                                <a href="<?= URL_ROOT ?>/views/admin/edit_user.php?id=<?= $user['user_id'] ?>"
                                                    class="btn btn-sm btn-warning" title="Sửa">
                                                    <i class="bi bi-pencil"></i>
                                                </a>
                                                <?php if ($user['user_id'] != $_SESSION['user_id']): ?>
                                                    <button class="btn btn-sm btn-danger delete-user"
                                                        data-id="<?= $user['user_id'] ?>"
                                                        data-name="<?= htmlspecialchars($user['fullname']) ?>"
                                                        title="Xóa">
                                                        <i class="bi bi-trash"></i>
                                                    </button>
                                                <?php endif; ?>
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
                                    <a class="page-link" href="?role=<?= $role ?>&search=<?= urlencode($search) ?>&page=<?= $page - 1 ?>">
                                        Trước
                                    </a>
                                </li>
                                <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                                    <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                                        <a class="page-link" href="?role=<?= $role ?>&search=<?= urlencode($search) ?>&page=<?= $i ?>">
                                            <?= $i ?>
                                        </a>
                                    </li>
                                <?php endfor; ?>
                                <li class="page-item <?= $page >= $totalPages ? 'disabled' : '' ?>">
                                    <a class="page-link" href="?role=<?= $role ?>&search=<?= urlencode($search) ?>&page=<?= $page + 1 ?>">
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

    <script>
        document.querySelectorAll('.delete-user').forEach(btn => {
            btn.addEventListener('click', function() {
                const userId = this.dataset.id;
                const userName = this.dataset.name;

                if (confirm(`Bạn có chắc muốn xóa người dùng "${userName}"?`)) {
                    fetch('<?= URL_ROOT ?>/api/admin_actions.php', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json'
                            },
                            body: JSON.stringify({
                                action: 'delete_user',
                                user_id: userId
                            })
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                alert('Xóa người dùng thành công!');
                                location.reload();
                            } else {
                                alert('Lỗi: ' + (data.message || 'Không thể xóa người dùng'));
                            }
                        })
                        .catch(error => {
                            console.error('Error:', error);
                            alert('Có lỗi xảy ra!');
                        });
                }
            });
        });
    </script>

    <?php include __DIR__ . '/../templates/footer.php'; ?>

</body>

</html>