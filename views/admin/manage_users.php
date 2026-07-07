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

$pageTitle = "User Management";
?>
<!DOCTYPE html>
<html lang="en">

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
                    <li class="breadcrumb-item"><a href="<?= URL_ROOT ?>/dashboard" data-lang-key="menu_dashboard">Dashboard</a></li>
                    <li class="breadcrumb-item active" data-lang-key="admin_user_management">User Management</li>
                </ol>
            </nav>
        </div><!-- End Page Title -->

        <section class="section">
            <!-- Filters and Search -->
            <div class="card mb-4">
                <div class="card-body">
                    <h5 class="card-title" data-lang-key="filters">Filters</h5>
                    <form method="GET" class="row g-3">
                        <div class="col-md-3">
                            <label class="form-label">Role</label>
                            <select name="role" class="form-select" onchange="this.form.submit()">
                                <option value="all" <?= $role === 'all' ? 'selected' : '' ?>>All</option>
                                <option value="admin" <?= $role === 'admin' ? 'selected' : '' ?>>Admin</option>
                                <option value="teacher" <?= $role === 'teacher' ? 'selected' : '' ?>>Teacher</option>
                                <option value="student" <?= $role === 'student' ? 'selected' : '' ?>>Student</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Search</label>
                            <input type="text" name="search" class="form-control" placeholder="Name, username, email..." value="<?= htmlspecialchars($search) ?>">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">&nbsp;</label>
                            <div>
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-search"></i> <span>Search</span>
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
                                    <th>Username</th>
                                    <th>Email</th>
                                    <th>Role</th>
                                    <th>Created Date</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($users)): ?>
                                    <tr>
                                        <td colspan="7" class="text-center">No data available</td>
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
                                                    class="btn btn-sm btn-warning" title="Edit">
                                                    <i class="bi bi-pencil"></i>
                                                </a>
                                                <?php if ($user['user_id'] != $_SESSION['user_id']): ?>
                                                    <button class="btn btn-sm btn-danger delete-user"
                                                        data-id="<?= $user['user_id'] ?>"
                                                        data-name="<?= htmlspecialchars($user['fullname']) ?>"
                                                        title="Delete">
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
                                        Previous
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
                                        Next
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

                if (confirm(`Are you sure you want to delete the user "${userName}"?`)) {
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
                                alert('User deleted successfully!');
                                location.reload();
                            } else {
                                alert('Error: ' + (data.message || 'Could not delete user'));
                            }
                        })
                        .catch(error => {
                            console.error('Error:', error);
                            alert('An error occurred!');
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
