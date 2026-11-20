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

$role = $_GET['role'] ?? 'all';
$search = $_GET['search'] ?? '';
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
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

$pageTitle = "User Management";
?>
<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="utf-8">
    <meta content="width=device-width, initial-scale=1.0" name="viewport">
    <title data-lang-key="admin_user_management"><?= $pageTitle ?> - Literature System</title>
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
            <h1 data-lang-key="admin_user_management">User Management</h1>
            <nav>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="<?= URL_ROOT ?>/dashboard" data-lang-key="menu_dashboard">Trang chủ</a></li>
                    <li class="breadcrumb-item active" data-lang-key="admin_user_management">Quản lý người dùng</li>
                </ol>
            </nav>
        </div><!-- End Page Title -->

        <section class="section">
            <!-- Filters and Search -->
            <div class="card mb-4">
                <div class="card-body">
                    <h5 class="card-title" data-lang-key="filters">Bộ lọc</h5>
                    <form method="GET" class="row g-3">
                        <div class="col-md-3">
                            <label class="form-label">Vai trò</label>
                            <select aria-label="Vai trò" name="role" class="form-select" onchange="this.form.submit()">
                                <option value="all" <?= $role === 'all' ? 'selected' : '' ?>>All</option>
                                <option value="admin" <?= $role === 'admin' ? 'selected' : '' ?>>Admin</option>
                                <option value="teacher" <?= $role === 'teacher' ? 'selected' : '' ?>>Giáo viên</option>
                                <option value="student" <?= $role === 'student' ? 'selected' : '' ?>>Học sinh</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Tìm kiếm</label>
                            <input aria-label="Tên, tên đăng nhập, email" type="text" name="search" class="form-control" placeholder="Tên, tên đăng nhập, email…" value="<?= htmlspecialchars($search) ?>">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">&nbsp;</label>
                            <div>
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-search"></i> <span>Tìm kiếm</span>
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
                        <h5 class="card-title"><span data-lang-key="user_list">User List</span> (<?= $total ?? 0 ?>)</h5>
                        <div>
                            <a href="<?= URL_ROOT ?>/views/admin/create_user.php?role=teacher" class="btn btn-sm btn-primary">
                                <i class="bi bi-person-plus"></i> <span data-lang-key="admin_add_teacher">Add Teacher</span>
                            </a>
                            <a href="<?= URL_ROOT ?>/views/admin/create_user.php?role=student" class="btn btn-sm btn-success">
                                <i class="bi bi-person-plus"></i> <span data-lang-key="admin_add_student">Add Student</span>
                            </a>
                        </div>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Full Name</th>
                                    <th>Tên đăng nhập</th>
                                    <th>Email</th>
                                    <th>Vai trò</th>
                                    <th>Created Date</th>
                                    <th>Thao tác</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($users)): ?>
                                    <tr>
                                        <td colspan="7" class="text-center">Chưa có dữ liệu</td>
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
                                                    'teacher' => 'Teacher',
                                                    'student' => 'Student',
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
                                                        title="Xoá">
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
                        <nav aria-label="Phân trang">
                            <ul class="pagination justify-content-center">
                                <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
                                    <a class="page-link" href="?role=<?= urlencode($role) ?>&search=<?= urlencode($search) ?>&page=<?= $page - 1 ?>">
                                        Trước
                                    </a>
                                </li>
                                <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                                    <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                                        <a class="page-link" href="?role=<?= urlencode($role) ?>&search=<?= urlencode($search) ?>&page=<?= $i ?>">
                                            <?= $i ?>
                                        </a>
                                    </li>
                                <?php endfor; ?>
                                <li class="page-item <?= $page >= $totalPages ? 'disabled' : '' ?>">
                                    <a class="page-link" href="?role=<?= urlencode($role) ?>&search=<?= urlencode($search) ?>&page=<?= $page + 1 ?>">
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
            btn.addEventListener('click', async function() {
                const userId = this.dataset.id;
                const userName = this.dataset.name;

                if (await UI.confirm(`Xoá người dùng "${userName}"? Thao tác này không thể hoàn tác.`)) {
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
                                UI.success('Đã xoá người dùng.');
                                location.reload();
                            } else {
                                UI.error(data.message || 'Không xoá được người dùng.');
                            }
                        })
                        .catch(error => {
                            console.error('Error:', error);
                            UI.error('Đã có lỗi xảy ra. Vui lòng thử lại.');
                        });
                }
            });
        });
    </script>

    <?php
    require_once __DIR__ . '/../templates/footer.php';
    require_once __DIR__ . '/../templates/vendors.php';
    ?>


</body>

</html>
