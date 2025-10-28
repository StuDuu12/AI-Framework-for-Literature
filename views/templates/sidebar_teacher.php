<!-- ======= Sidebar ======= -->
<aside id="sidebar" class="sidebar">

    <ul class="sidebar-nav" id="sidebar-nav">
        <!-- ====== NAVBAR CHUNG ====== -->
        <li class="nav-item">
            <a class="nav-link collapsed" href="<?= URL_ROOT ?>/dashboard">
                <i class="bi bi-grid"></i>
                <span>Danh sách các lớp học</span>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link" href="<?= URL_ROOT ?>/class">
                <i class="bi bi-building"></i>
                <span>Bạn đang ở <b style="color: red"><?= $_SESSION['current_class_name'] ?></b></span>
            </a>
        </li>

        <!-- ====== NGƯỜI DÙNG VÀ NHÓM ====== -->
        <li class="nav-heading">Quản lý thành viên</li>
        <li class="nav-item">
            <a class="nav-link collapsed" href="<?= URL_ROOT ?>/class/participants">
                <i class="bi bi-people"></i>
                <span>Quản lý thành viên lớp học</span>
            </a>
        </li>

        <li class="nav-item">
            <a class="nav-link collapsed" href="<?= URL_ROOT ?>/class/groups">
                <i class="bi bi-diagram-3"></i>
                <span>Quản lý nhóm</span>
            </a>
        </li>

        <!-- ====== NỘI DUNG ====== -->
        <li class="nav-heading">Quản lý nội dung</li>
        <?php if (isset($_SESSION['current_class_id']) && $_SESSION['current_class_id'] > 0): ?>
            <li class="nav-item">
                <a class="nav-link collapsed" href="<?= URL_ROOT ?>/class">
                    <i class="bi bi-box-seam"></i>
                    <span>Danh sách chủ đề</span>
                </a>
            </li>
        <?php endif; ?>
        <?php if (isset($_GET['topic_id']) && $_GET['topic_id'] > 0): ?>
            <li class="nav-item">
                <a class="nav-link collapsed" href="<?= URL_ROOT ?>/class/phases?topic_id=<?= $_GET['topic_id'] ?>">
                    <i class="bi bi-file-earmark-text"></i>
                    <span>Các pha của chủ đề hiện tại</span>
                </a>
            </li>
        <?php endif; ?>
        <?php if(isset($_GET['phase_id']) && $_GET['phase_id'] > 0): ?>
        <li class="nav-item">
            <a class="nav-link collapsed" href="<?= URL_ROOT ?>/class/task?topic_id=<?= $_GET['topic_id'] ?>&phase_id=<?= $_GET['phase_id'] ?>">
                <i class="bi bi-file-earmark-text"></i>
                <span>Các nhiệm vụ của pha hiện tại</span>
            </a>
        </li>
        <?php endif; ?>
    </ul>

</aside><!-- End Sidebar-->