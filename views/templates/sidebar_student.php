<!-- ======= Sidebar for students ======= -->

<aside id="sidebar" class="sidebar glass">

    <?php

    $current_path = $_SERVER['REQUEST_URI'];

    $current_page = parse_url($current_path, PHP_URL_PATH);

    $hasActiveClass = !empty($_SESSION['current_class_id'])
        && (int)$_SESSION['current_class_id'] > 0
        && !empty($_SESSION['current_class_name']);

    ?>



    <ul class="sidebar-nav" id="sidebar-nav">

        <li class="nav-item">

            <a class="nav-link <?= strpos($current_page, '/workspace') !== false ? '' : 'collapsed' ?>" href="<?= URL_ROOT ?>/workspace">

                <i class="bi bi-house-door"></i>

                <span data-lang-key="menu_workspace">Workspace</span>

            </a>

        </li>

        <li class="nav-item">

            <a class="nav-link <?= strpos($current_page, '/canon') !== false ? '' : 'collapsed' ?>" href="<?= URL_ROOT ?>/canon">

                <i class="bi bi-book"></i>

                <span data-lang-key="menu_canon">Kho tri thức văn học</span>

            </a>

        </li>

        <li class="nav-item">

            <a class="nav-link <?= strpos($current_page, '/dashboard') !== false ? '' : 'collapsed' ?>" href="<?= URL_ROOT ?>/dashboard">

                <i class="bi bi-grid"></i>

                <span data-lang-key="menu_classes">Danh sách lớp học</span>

            </a>

        </li>

        <?php if ($hasActiveClass): ?>
        <li class="nav-item">

            <a class="nav-link <?= $current_page === '/class' || $current_page === '/class/' ? '' : 'collapsed' ?>" href="<?= URL_ROOT ?>/class">

                <i class="bi bi-building"></i>

                <span><span data-lang-key="menu_current_class">Lớp hiện tại:</span> <b class="sidebar-current-class-name"><?= htmlspecialchars($_SESSION['current_class_name'], ENT_QUOTES, 'UTF-8') ?></b></span>

            </a>

        </li>
        <?php endif; ?>



        <?php if ($hasActiveClass): ?>
        <li class="nav-heading" data-lang-key="menu_heading_study">Học tập</li>



        <li class="nav-item">

            <a class="nav-link <?= strpos($current_page, '/class/my_tasks') !== false ? '' : 'collapsed' ?>" href="<?= URL_ROOT ?>/class/my_tasks">

                <i class="bi bi-list-task"></i>

                <span data-lang-key="menu_my_tasks">Nhiệm vụ của tôi</span>

            </a>

        </li>



        <li class="nav-item">

            <a class="nav-link <?= strpos($current_page, '/class/my_groups') !== false ? '' : 'collapsed' ?>" href="<?= URL_ROOT ?>/class/my_groups">

                <i class="bi bi-people"></i>

                <span data-lang-key="menu_my_groups">Nhóm của tôi</span>

            </a>

        </li>

        <?php endif; ?>



        <?php if ($hasActiveClass): ?>
        <li class="nav-heading" data-lang-key="menu_heading_content">Nội dung bài học</li>

            <li class="nav-item">

                <a class="nav-link collapsed" href="<?= URL_ROOT ?>/class">

                    <i class="bi bi-box-seam"></i>

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

                    <i class="bi bi-file-earmark-text"></i>

                    <span data-lang-key="menu_phase_list">Giai đoạn học tập</span>

                </a>

            </li>

        <?php endif; ?>

        <?php if ($navPhaseId > 0 && $navTopicId > 0): ?>

            <li class="nav-item">

                <a class="nav-link <?= strpos($current_page, '/class/task') !== false ? '' : 'collapsed' ?>" href="<?= URL_ROOT ?>/class/task?topic_id=<?= $navTopicId ?>&phase_id=<?= $navPhaseId ?>">

                    <i class="bi bi-file-earmark-text"></i>

                    <span data-lang-key="menu_task_list">Nhiệm vụ giai đoạn</span>

                </a>

            </li>

        <?php endif; ?>

        <?php endif; ?>

        <li class="nav-heading">Thành tích</li>

        <li class="nav-item">
            <a class="nav-link <?= strpos($current_page, '/leaderboard') !== false ? '' : 'collapsed' ?>" href="<?= URL_ROOT ?>/leaderboard">
                <i class="bi bi-trophy"></i>
                <span>Bảng xếp hạng</span>
            </a>
        </li>

        <li class="nav-item">
            <a class="nav-link <?= strpos($current_page, '/reading-list') !== false ? '' : 'collapsed' ?>" href="<?= URL_ROOT ?>/reading-list">
                <i class="bi bi-bookmark-check"></i>
                <span>Danh sách đọc</span>
            </a>
        </li>

        <li class="nav-heading" data-lang-key="menu_heading_feedback">Hỗ trợ</li>

        <li class="nav-item">

            <a class="nav-link <?= strpos($current_page, '/reflect') !== false ? '' : 'collapsed' ?>" href="<?= URL_ROOT ?>/reflect">

                <i class="bi bi-chat-dots"></i>

                <span data-lang-key="menu_feedback">Góp ý & hỗ trợ</span>

            </a>

        </li>

    </ul>

</aside>


