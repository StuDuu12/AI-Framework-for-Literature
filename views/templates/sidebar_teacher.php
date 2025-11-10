<!-- ======= Sidebar ======= -->
<aside id="sidebar" class="sidebar">

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
    ?>

    <ul class="sidebar-nav" id="sidebar-nav" class="<?php echo (strpos($_SERVER['REQUEST_URI'], '/dashboard') !== false) ? 'sidebar-disabled' : ''; ?>">
        <!-- ====== NAVBAR CHUNG ====== -->
        <li class="nav-item">
            <a class="nav-link <?= strpos($current_page, '/dashboard') !== false ? '' : 'collapsed' ?>" href="<?= URL_ROOT ?>/dashboard">
                <i class="bi bi-grid"></i>
                <span>Danh sách các lớp học</span>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?= $current_page === '/class' || $current_page === '/class/' ? '' : 'collapsed' ?>" href="<?= URL_ROOT ?>/class">
                <i class="bi bi-building"></i>
                <span>Bạn đang ở <b style="color: red"><?= $_SESSION['current_class_name'] ?></b></span>
            </a>
        </li>

        <!-- ====== NGƯỜI DÙNG VÀ NHÓM ====== -->
        <li class="nav-heading">Quản lý thành viên</li>
        <li class="nav-item">
            <a class="nav-link <?= strpos($current_page, '/class/participants') !== false ? '' : 'collapsed' ?>" href="<?= URL_ROOT ?>/class/participants">
                <i class="bi bi-people"></i>
                <span>Quản lý thành viên lớp học</span>
            </a>
        </li>

        <li class="nav-item">
            <a class="nav-link <?= strpos($current_page, '/class/groups') !== false ? '' : 'collapsed' ?>" href="<?= URL_ROOT ?>/class/groups">
                <i class="bi bi-diagram-3"></i>
                <span>Quản lý nhóm</span>
            </a>
        </li>

        <!-- ====== NỘI DUNG ====== -->
        <li class="nav-heading">Quản lý nội dung</li>
        <?php if (isset($_SESSION['current_class_id']) && $_SESSION['current_class_id'] > 0): ?>
            <li class="nav-item">
                <a class="nav-link <?= strpos($current_page, '/class/criteria') !== false ? '' : 'collapsed' ?>" href="<?= URL_ROOT ?>/class/criteria">
                    <i class="bi bi-check-circle"></i>
                    <span>Quản lý tiêu chí</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= ($current_page === '/class' || $current_page === '/class/') ? '' : 'collapsed' ?>" href="<?= URL_ROOT ?>/class">
                    <i class="bi bi-box-seam"></i>
                    <span>Danh sách chủ đề</span>
                </a>
            </li>
        <?php endif; ?>
        <?php if (isset($_GET['topic_id']) && $_GET['topic_id'] > 0): ?>
            <li class="nav-item">
                <a class="nav-link <?= strpos($current_page, '/class/phases') !== false ? '' : 'collapsed' ?>" href="<?= URL_ROOT ?>/class/phases?topic_id=<?= $_GET['topic_id'] ?>">
                    <i class="bi bi-file-earmark-text"></i>
                    <span>Các pha của chủ đề hiện tại</span>
                </a>
            </li>
        <?php endif; ?>
        <?php if (isset($_GET['phase_id']) && $_GET['phase_id'] > 0): ?>
            <li class="nav-item">
                <a class="nav-link <?= strpos($current_page, '/class/task') !== false ? '' : 'collapsed' ?>" href="<?= URL_ROOT ?>/class/task?topic_id=<?= $_GET['topic_id'] ?>&phase_id=<?= $_GET['phase_id'] ?>">
                    <i class="bi bi-file-earmark-text"></i>
                    <span>Các nhiệm vụ của pha hiện tại</span>
                </a>
            </li>
        <?php endif; ?>

        <li class="nav-heading">Góp ý</li>
        <li class="nav-item">
            <a class="nav-link <?= strpos($current_page, '/views/feedback') !== false ? '' : 'collapsed' ?>" href="<?= URL_ROOT ?>/views/feedback/index.php">
                <i class="bi bi-chat-left-text"></i>
                <span>Gửi góp ý</span>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link collapsed" href="<?= URL_ROOT ?>/reflect">
                <i class="bi bi-chat-dots"></i>
                <span>Phản hồi</span>
            </a>
        </li>
    </ul>

</aside><!-- End Sidebar-->