<!-- ======= Sidebar cho học sinh ======= -->
<aside id="sidebar" class="sidebar">
    <?php
    // Get current page path for active menu highlighting
    $current_path = $_SERVER['REQUEST_URI'];
    $current_page = parse_url($current_path, PHP_URL_PATH);
    ?>
    
    <ul class="sidebar-nav" id="sidebar-nav" class="<?php echo (strpos($_SERVER['REQUEST_URI'], '/dashboard') !== false) ? 'sidebar-disabled' : ''; ?>">
        <li class="nav-item">
            <a class="nav-link <?= strpos($current_page, '/dashboard') !== false ? '' : 'collapsed' ?>" href="<?= URL_ROOT ?>/dashboard">
                <i class="bi bi-grid"></i>
                <span>Danh sách các lớp học</span>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?= $current_page === '/class' || $current_page === '/class/' ? '' : 'collapsed' ?>" href="<?= URL_ROOT ?>/class">
                <i class="bi bi-grid"></i>
                <span>Bạn đang ở <b style="color: red"><?= $_SESSION['current_class_name'] ?></b></span>
            </a>
        </li>

        <li class="nav-heading">Học tập</li>

        <li class="nav-item">
            <a class="nav-link <?= strpos($current_page, '/class/my_tasks') !== false ? '' : 'collapsed' ?>" href="<?= URL_ROOT ?>/class/my_tasks">
                <i class="bi bi-list-task"></i>
                <span>Nhiệm vụ của tôi</span>
            </a>
        </li>

        <li class="nav-item">
            <a class="nav-link <?= strpos($current_page, '/class/my_groups') !== false ? '' : 'collapsed' ?>" href="<?= URL_ROOT ?>/class/my_groups">
                <i class="bi bi-people"></i>
                <span>Nhóm của tôi</span>
            </a>
        </li>

    </ul>
</aside>