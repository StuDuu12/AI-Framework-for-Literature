<!-- ======= Sidebar ======= -->
<aside id="sidebar" class="sidebar glass" aria-label="Menu chính">

    <?php
    // Get current page path for active menu highlighting
    $current_path = $_SERVER['REQUEST_URI'];
    $current_page = parse_url($current_path, PHP_URL_PATH);

    // Helper function to check if menu is active
    function isActive($path, $current)
    {
        // Remove URL_ROOT from comparison
        $path = str_replace(URL_ROOT, '', $path);
        // Exact match or starts with path
        return $current === $path || strpos($current, $path) === 0;
    }

    $hasActiveClass = !empty($_SESSION['current_class_id'])
        && (int)$_SESSION['current_class_id'] > 0
        && !empty($_SESSION['current_class_name']);
    ?>

    <ul class="sidebar-nav" id="sidebar-nav"<?php echo (strpos($_SERVER['REQUEST_URI'], '/dashboard') !== false) ? ' class="sidebar-disabled"' : ''; ?>>
        <!-- ====== NAVBAR CHUNG ====== -->
        <li class="nav-item">
            <a class="nav-link <?= strpos($current_page, '/dashboard') !== false ? '' : 'collapsed' ?>" href="<?= URL_ROOT ?>/dashboard">
                <i class="bi bi-grid" aria-hidden="true"></i>
                <span data-lang-key="class_list_title">Danh sách lớp học</span>
            </a>
        </li>
        <?php if ($hasActiveClass): ?>
        <li class="nav-item">
            <a class="nav-link <?= $current_page === '/class' || $current_page === '/class/' ? '' : 'collapsed' ?>" href="<?= URL_ROOT ?>/class">
                <i class="bi bi-building" aria-hidden="true"></i>
                <span><span data-lang-key="menu_current_class">Lớp hiện tại:</span> <b class="sidebar-current-class-name"><?= htmlspecialchars($_SESSION['current_class_name'], ENT_QUOTES, 'UTF-8') ?></b></span>
            </a>
        </li>

        <li class="nav-heading" data-lang-key="menu_heading_members">Quản lý thành viên</li>
        <li class="nav-item">
            <a class="nav-link <?= strpos($current_page, '/class/participants') !== false ? '' : 'collapsed' ?>" href="<?= URL_ROOT ?>/class/participants">
                <i class="bi bi-people" aria-hidden="true"></i>
                <span data-lang-key="menu_manage_members">Quản lý thành viên lớp</span>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?= strpos($current_page, '/class/groups') !== false ? '' : 'collapsed' ?>" href="<?= URL_ROOT ?>/class/groups">
                <i class="bi bi-diagram-3" aria-hidden="true"></i>
                <span data-lang-key="menu_manage_groups">Quản lý nhóm</span>
            </a>
        </li>

        <li class="nav-heading" data-lang-key="menu_heading_content">Nội dung bài học</li>
        <li class="nav-item">
            <a class="nav-link <?= strpos($current_page, '/class/criteria') !== false ? '' : 'collapsed' ?>" href="<?= URL_ROOT ?>/class/criteria">
                <i class="bi bi-check-circle" aria-hidden="true"></i>
                <span data-lang-key="menu_manage_criteria">Quản lý tiêu chí</span>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?= ($current_page === '/class' || $current_page === '/class/') ? '' : 'collapsed' ?>" href="<?= URL_ROOT ?>/class">
                <i class="bi bi-box-seam" aria-hidden="true"></i>
                <span data-lang-key="menu_topic_list">Danh sách chủ đề</span>
            </a>
        </li>
        <?php
        $navTopicId = (int)($_SESSION['nav_topic_id'] ?? ($_GET['topic_id'] ?? 0));
        $navPhaseId = (int)($_SESSION['nav_phase_id'] ?? ($_GET['phase_id'] ?? 0));
        ?>
        <?php if ($navTopicId > 0): ?>
        <li class="nav-item">
            <a class="nav-link <?= strpos($current_page, '/class/phases') !== false ? '' : 'collapsed' ?>" href="<?= URL_ROOT ?>/class/phases?topic_id=<?= $navTopicId ?>">
                <i class="bi bi-file-earmark-text" aria-hidden="true"></i>
                <span data-lang-key="menu_phase_list">Giai đoạn học tập</span>
            </a>
        </li>
        <?php endif; ?>
        <?php if ($navPhaseId > 0 && $navTopicId > 0): ?>
        <li class="nav-item">
            <a class="nav-link <?= strpos($current_page, '/class/task') !== false ? '' : 'collapsed' ?>" href="<?= URL_ROOT ?>/class/task?topic_id=<?= $navTopicId ?>&phase_id=<?= $navPhaseId ?>">
                <i class="bi bi-file-earmark-text" aria-hidden="true"></i>
                <span data-lang-key="menu_task_list">Nhiệm vụ giai đoạn</span>
            </a>
        </li>
        <?php endif; ?>
        <?php endif; ?>

        <li class="nav-heading" data-lang-key="menu_heading_feedback">Hỗ trợ</li>
        <li class="nav-item">
            <a class="nav-link <?= strpos($current_page, '/reflect') !== false ? '' : 'collapsed' ?>" href="<?= URL_ROOT ?>/reflect">
                <i class="bi bi-chat-dots" aria-hidden="true"></i>
                <span data-lang-key="menu_feedback">Góp ý & hỗ trợ</span>
            </a>
        </li>

        <li class="nav-heading" data-lang-key="menu_heading_account">Tài khoản</li>
        <li class="nav-item">
            <a class="nav-link collapsed text-danger" href="<?= URL_ROOT ?>/logout">
                <i class="bi bi-box-arrow-right text-danger" aria-hidden="true"></i>
                <span data-lang-key="logout">Đăng xuất</span>
            </a>
        </li>
    </ul>

</aside><!-- End Sidebar-->