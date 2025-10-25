<!-- ======= Sidebar ======= -->
<aside id="sidebar" class="sidebar">

    <ul class="sidebar-nav" id="sidebar-nav">
        <li class="nav-item">
            <a class="nav-link collapsed" href="<?= URL_ROOT ?>/">
                <i class="bi bi-grid"></i>
                <span>Danh sách các lớp học</span>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link">
                <i class="bi bi-grid"></i>
                <span>Bạn đang ở <b><?= $_SESSION['current_class_name'] ?></b></span>
            </a>
        </li>

        <li class="nav-heading">Pages</li>

        <li class="nav-item">
            <a class="nav-link collapsed" href="<?= URL_ROOT ?>/class/participants">
                <i class="bi bi-people"></i>
                <span>Quản lý thành viên lớp học</span>
            </a>
        </li><!-- End User Management Nav -->

        <li class="nav-item">
            <a class="nav-link collapsed" href="<?= URL_ROOT ?>/groups.php">
                <i class="bi bi-diagram-3"></i>
                <span>Quản lý nhóm</span>
            </a>
        </li><!-- End Group Management Nav -->

        <li class="nav-item">
            <a class="nav-link collapsed" href="<?= URL_ROOT ?>/tasks.php">
                <i class="bi bi-list-task"></i>
                <span>Quản lý nhiệm vụ</span>
            </a>
        </li><!-- End Task Management Nav -->

    </ul>

</aside><!-- End Sidebar-->