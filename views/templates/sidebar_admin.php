<!-- ======= Sidebar for Admin ======= -->
<aside id="sidebar" class="sidebar glass">
    <?php
    // Get current page path for active menu highlighting
    $current_path = $_SERVER['REQUEST_URI'];
    // $current_path = basename($_SERVER['PHP_SELF']);
    ?>

    <ul class="sidebar-nav" id="sidebar-nav">
        <li class="nav-item">
            <a class="nav-link <?= strpos($current_path, 'dashboard') !== false ? '' : 'collapsed' ?>" href="<?= URL_ROOT ?>/dashboard">
                <i class="bi bi-speedometer2"></i>
                <span data-lang-key="menu_dashboard">Trang quản trị</span>
            </a>
        </li><!-- End Dashboard Nav -->

        <li class="nav-item">
            <a class="nav-link <?= strpos($current_path, 'admin/users') !== false ? '' : 'collapsed' ?>"
                data-bs-target="#users-nav" data-bs-toggle="collapse" href="#">
                <i class="bi bi-people"></i>
                <span data-lang-key="admin_user_management">User Management</span>
                <i class="bi bi-chevron-down ms-auto"></i>
            </a>
            <ul id="users-nav" class="nav-content collapse <?= strpos($current_path, 'admin/users') !== false ? 'show' : '' ?>" data-bs-parent="#sidebar-nav">
                <li>
                    <a href="<?= URL_ROOT ?>/admin/users" class="<?= strpos($current_path, 'admin/users') !== false && strpos($current_path, 'registrations') === false ? 'active' : '' ?>">
                        <i class="bi bi-circle"></i><span data-lang-key="admin_all_users">All Users</span>
                    </a>
                </li>
                <li>
                    <a href="<?= URL_ROOT ?>/admin/users?role=teacher">
                        <i class="bi bi-circle"></i><span data-lang-key="admin_teachers">Teachers</span>
                    </a>
                </li>
                <li>
                    <a href="<?= URL_ROOT ?>/admin/users?role=student">
                        <i class="bi bi-circle"></i><span data-lang-key="admin_students">Students</span>
                    </a>
                </li>
                <li>
                    <a href="<?= URL_ROOT ?>/admin/users/create?role=teacher">
                        <i class="bi bi-circle"></i><span data-lang-key="admin_add_teacher">Add Teacher</span>
                    </a>
                </li>
                <li>
                    <a href="<?= URL_ROOT ?>/admin/users/create?role=student">
                        <i class="bi bi-circle"></i><span data-lang-key="admin_add_student">Add Student</span>
                    </a>
                </li>
                <li>
                    <a href="<?= URL_ROOT ?>/admin/users/import" class="<?= strpos($current_path, 'import') !== false ? 'active' : '' ?>">
                        <i class="bi bi-circle"></i><span>Import CSV</span>
                    </a>
                </li>
                <li>
                    <a href="<?= URL_ROOT ?>/admin/registrations" class="<?= strpos($current_path, 'registrations') !== false ? 'active' : '' ?>">
                        <i class="bi bi-circle"></i><span>Duyệt đăng ký</span>
                        <?php
                        try {
                            $stmt = $pdo->query("SELECT COUNT(*) as cnt FROM registration_requests WHERE status = 'pending'");
                            $pendingReg = $stmt->fetch(PDO::FETCH_ASSOC)['cnt'];
                            if ($pendingReg > 0) {
                                echo '<span class="badge bg-danger badge-number ms-auto">' . $pendingReg . '</span>';
                            }
                        } catch (PDOException $e) {}
                        ?>
                    </a>
                </li>
            </ul>
        </li><!-- End Users Nav -->

        <li class="nav-item">
            <a class="nav-link <?= strpos($current_path, 'reflect') !== false ? '' : 'collapsed' ?>" href="<?= URL_ROOT ?>/reflect">
                <i class="bi bi-journal-text"></i>
                <span data-lang-key="manage_feedback">Manage Feedback</span>
                <?php
                // Display pending reflect count
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

        <li class="nav-item">
            <a class="nav-link <?= strpos($current_path, 'admin/ai-settings') !== false ? '' : 'collapsed' ?>" href="<?= URL_ROOT ?>/admin/ai-settings">
                <i class="bi bi-robot"></i>
                <span data-lang-key="admin_ai_settings">Cấu hình AI</span>
            </a>
        </li>

        <li class="nav-item">
            <a class="nav-link <?= strpos($current_path, 'admin/analytics') !== false ? '' : 'collapsed' ?>" href="<?= URL_ROOT ?>/admin/analytics">
                <i class="bi bi-graph-up"></i>
                <span>Analytics</span>
            </a>
        </li>

        <li class="nav-heading">Tài khoản</li>
        <li class="nav-item">
            <a class="nav-link collapsed text-danger" href="<?= URL_ROOT ?>/logout">
                <i class="bi bi-box-arrow-right text-danger"></i>
                <span>Đăng xuất</span>
            </a>
        </li>

    </ul>
</aside><!-- End Sidebar -->
