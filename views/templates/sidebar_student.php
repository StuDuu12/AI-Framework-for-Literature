<!-- ======= Sidebar cho học sinh ======= -->
<aside id="sidebar" class="sidebar">
    <ul class="sidebar-nav" id="sidebar-nav">
        <li class="nav-item">
            <a class="nav-link collapsed" href="<?= URL_ROOT ?>/">
                <i class="bi bi-grid"></i>
                <span>Danh sách các lớp học</span>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link" href="<?= URL_ROOT ?>/class">
                <i class="bi bi-grid"></i>
                <span>Bạn đang ở <b><?= $_SESSION['current_class_name'] ?></b></span>
            </a>
        </li>

        <li class="nav-heading">Học tập</li>

        <li class="nav-item">
            <a class="nav-link collapsed" href="<?= URL_ROOT ?>/student_tasks.php">
                <i class="bi bi-list-task"></i>
                <span>Nhiệm vụ của tôi</span>
            </a>
        </li>

        <li class="nav-item">
            <a class="nav-link collapsed" href="<?= URL_ROOT ?>/student_groups.php">
                <i class="bi bi-people"></i>
                <span>Nhóm của tôi</span>
            </a>
        </li>

        <li class="nav-heading">Công cụ</li>

        <li class="nav-item">
            <a class="nav-link collapsed" href="<?= URL_ROOT ?>/literary_analysis.php">
                <i class="bi bi-book"></i>
                <span>Phân tích Văn học</span>
            </a>
        </li>

    </ul>
</aside>