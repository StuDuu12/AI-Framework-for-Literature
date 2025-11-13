<!-- ======= Sidebar cho Admin ======= -->
<aside id="sidebar" class="sidebar">
    <?php
    // Get current page path for active menu highlighting
    $current_path = $_SERVER['REQUEST_URI'];
    // $current_path = basename($_SERVER['PHP_SELF']);
    ?>

    <ul class="sidebar-nav" id="sidebar-nav">
        <li class="nav-item">
            <a class="nav-link <?= strpos($current_path, 'dashboard') !== false ? '' : 'collapsed' ?>" href="<?= URL_ROOT ?>/dashboard">
                <i class="bi bi-speedometer2"></i>
                <span>Trang chủ</span>
            </a>
        </li><!-- End Dashboard Nav -->

        <li class="nav-item">
            <a class="nav-link <?= strpos($current_path, 'admin/users') !== false ? '' : 'collapsed' ?>"
                data-bs-target="#users-nav" data-bs-toggle="collapse" href="#">
                <i class="bi bi-people"></i>
                <span>Quản lý người dùng</span>
                <i class="bi bi-chevron-down ms-auto"></i>
            </a>
            <ul id="users-nav" class="nav-content collapse <?= strpos($current_path, 'admin/users') !== false ? 'show' : '' ?>" data-bs-parent="#sidebar-nav">
                <li>
                    <a href="<?= URL_ROOT ?>/admin/users" class="<?= strpos($current_path, 'admin/users') !== false ? 'active' : '' ?>">
                        <i class="bi bi-circle"></i><span>Tất cả người dùng</span>
                    </a>
                </li>
                <li>
                    <a href="<?= URL_ROOT ?>/admin/users?role=teacher">
                        <i class="bi bi-circle"></i><span>Giáo viên</span>
                    </a>
                </li>
                <li>
                    <a href="<?= URL_ROOT ?>/admin/users?role=student">
                        <i class="bi bi-circle"></i><span>Học sinh</span>
                    </a>
                </li>
                <li>
                    <a href="<?= URL_ROOT ?>/admin/users/create?role=teacher">
                        <i class="bi bi-circle"></i><span>Thêm giáo viên</span>
                    </a>
                </li>
                <li>
                    <a href="<?= URL_ROOT ?>/admin/users/create?role=student">
                        <i class="bi bi-circle"></i><span>Thêm học sinh</span>
                    </a>
                </li>
            </ul>
        </li><!-- End Users Nav -->

        <li class="nav-item">
            <a class="nav-link <?= strpos($current_path, 'reflect') !== false ? '' : 'collapsed' ?>" href="<?= URL_ROOT ?>/reflect">
                <i class="bi bi-journal-text"></i>
                <span>Quản lý Phản hồi</span>
                <?php
                // Hiển thị số reflect chờ xử lý
                try {
                    $stmt = $pdo->query("SELECT COUNT(*) as count FROM reflect_ticket WHERE status = 0");
                    $pendingReflect = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
                    if ($pendingReflect > 0) {
                        echo '<span class="badge bg-warning badge-number ms-auto">' . $pendingReflect . '</span>';
                    }
                } catch (PDOException $e) {
                    error_log("Error counting pending reflects: " . $e->getMessage());
                }
                ?>
            </a>
        </li><!-- End Reflect Nav -->


    </ul>
</aside><!-- End Sidebar -->