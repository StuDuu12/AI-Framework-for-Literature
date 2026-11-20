<?php
$__user = $_SESSION['user'] ?? [];
$__fullname = (string)($__user['fullname'] ?? '');
$__initial = $__fullname !== '' ? mb_strtoupper(mb_substr($__fullname, 0, 1, 'UTF-8'), 'UTF-8') : 'U';
$__roleKey = in_array($__user['role'] ?? '', ['admin', 'teacher', 'student'], true) ? $__user['role'] : 'user';
$__roleLabel = ['admin' => 'Quản trị viên', 'teacher' => 'Giáo viên', 'student' => 'Học sinh', 'user' => 'Người dùng'][$__roleKey];
?>
<script>
    // Đồng bộ theme lên <body> ngay khi body bắt đầu render (tránh nháy nền)
    if (document.documentElement.classList.contains('dark-theme')) document.body.classList.add('dark-theme');
</script>
<a href="#main" class="visually-hidden-focusable">Bỏ qua tới nội dung chính</a>

<!-- ======= Header ======= -->
<header id="header" class="header glass fixed-top d-flex align-items-center">

    <div class="d-flex align-items-center justify-content-between">
        <a href="<?= URL_ROOT . '/' ?>" class="logo d-flex align-items-center">
            <img src="<?= URL_ROOT ?>/assets/img/fit.png" alt="" width="30" height="30" style="height: auto;">
            <span class="d-none d-lg-block text-primary fw-bold" data-lang-key="system_title">Literature System</span>
        </a>
        <button type="button" class="toggle-sidebar-btn border-0 bg-transparent p-0" aria-label="Ẩn/hiện menu" data-lang-key="toggle_menu" data-lang-attr="aria-label">
            <i class="bi bi-list" aria-hidden="true"></i>
        </button>
    </div><!-- End Logo -->

    <div class="search-bar">
        <form class="search-form d-flex align-items-center" method="POST" action="#" role="search">
            <input type="text" name="query" placeholder="Tìm lớp, nhiệm vụ, chủ đề… (Ctrl+K)" title="Tìm kiếm (Ctrl+K hoặc /)" aria-label="Tìm kiếm" autocomplete="off"
                   data-lang-key="search_placeholder_shortcut" data-lang-attr="placeholder">
            <button type="submit" aria-label="Tìm kiếm"><i class="bi bi-search" aria-hidden="true"></i></button>
        </form>
    </div><!-- End Search Bar -->

    <nav class="header-nav ms-auto" aria-label="Công cụ">
        <ul class="d-flex align-items-center">

            <li class="nav-item d-block d-lg-none">
                <a class="nav-link nav-icon search-bar-toggle" href="#" aria-label="Mở ô tìm kiếm">
                    <i class="bi bi-search" aria-hidden="true"></i>
                </a>
            </li>

            <li class="nav-item">
                <button type="button" class="nav-link nav-icon theme-toggle" onclick="toggleTheme()" aria-label="Đổi giao diện sáng/tối" title="Đổi giao diện sáng/tối"
                        data-lang-key="theme_toggle" data-lang-attr="title">
                    <i class="bi bi-moon-fill" id="theme-icon" aria-hidden="true"></i>
                </button>
            </li>

            <li class="nav-item">
                <button type="button" class="nav-link nav-icon" id="language-toggle-btn" aria-label="Đổi ngôn ngữ" title="Đổi ngôn ngữ"
                        data-lang-key="language_toggle" data-lang-attr="title" style="border: none; background: transparent; padding: 0.5rem 0.75rem;">
                    <span id="language-icon" style="font-weight: 600; font-size: 0.875rem; color: var(--bs-nav-link-color);">VI</span>
                </button>
            </li>

            <!-- Thông báo -->
            <li class="nav-item dropdown">
                <a class="nav-link nav-icon position-relative" href="#" data-bs-toggle="dropdown" aria-expanded="false" aria-label="Thông báo" title="Thông báo"
                   data-lang-key="notifications" data-lang-attr="title">
                    <i class="bi bi-bell" aria-hidden="true"></i>
                    <span class="badge bg-danger badge-number d-none" id="notif-badge">0</span>
                </a>
                <ul class="dropdown-menu dropdown-menu-end dropdown-menu-arrow notifications-dropdown" id="notif-dropdown">
                    <li class="dropdown-header d-flex justify-content-between align-items-center">
                        <span data-lang-key="notifications">Thông báo</span>
                        <a href="#" class="small" id="mark-all-read-btn" data-lang-key="mark_all_read">Đánh dấu đã đọc</a>
                    </li>
                    <li><hr class="dropdown-divider"></li>
                </ul>
            </li>

            <li class="nav-item dropdown pe-3" id="profileDropdown">
                <a class="nav-link nav-profile d-flex align-items-center pe-0" href="#" data-bs-toggle="dropdown" aria-expanded="false" aria-label="Tài khoản: <?= htmlspecialchars($__fullname ?: 'Người dùng') ?>">
                    <div class="profile-avatar rounded-circle d-flex align-items-center justify-content-center shadow-sm bg-white" style="width: 42px; height: 42px; border: 2px solid var(--lit-primary);">
                        <span class="text-primary fw-bold fs-5" aria-hidden="true"><?= htmlspecialchars($__initial) ?></span>
                    </div>
                </a>

                <ul class="dropdown-menu dropdown-menu-end dropdown-menu-arrow profile shadow border-0 rounded-4 mt-2" style="min-width: 260px; padding: 0;">
                    <li class="dropdown-header d-flex flex-column align-items-center p-4 bg-light rounded-top-4 border-bottom">
                        <div class="rounded-circle bg-primary d-flex align-items-center justify-content-center shadow-sm mb-3" style="width: 60px; height: 60px;">
                            <span class="text-white fw-bold fs-3" aria-hidden="true"><?= htmlspecialchars($__initial) ?></span>
                        </div>
                        <h6 class="fw-bold text-dark fs-6 mb-1 text-truncate" style="max-width: 220px;" title="<?= htmlspecialchars($__fullname) ?>">
                            <?= htmlspecialchars($__fullname ?: 'Người dùng') ?>
                        </h6>
                        <span class="text-muted small text-truncate mb-2" style="max-width: 220px;">
                            <?php if (!empty($__user['email'])): ?>
                                <?= htmlspecialchars($__user['email']) ?>
                            <?php else: ?>
                                <span data-lang-key="no_email">Chưa có email</span>
                            <?php endif; ?>
                        </span>
                        <span class="badge bg-primary-subtle text-primary rounded-pill px-3 py-1 fw-semibold" data-lang-key="role_<?= $__roleKey ?>"><?= $__roleLabel ?></span>
                    </li>

                    <li class="py-2">
                        <a class="dropdown-item d-flex align-items-center px-4 py-2" href="<?= URL_ROOT ?>/profile">
                            <i class="bi bi-person me-3 fs-5 text-muted" aria-hidden="true"></i>
                            <span class="fw-medium" data-lang-key="my_profile">Hồ sơ cá nhân</span>
                        </a>
                    </li>
                    <li><hr class="dropdown-divider m-0 opacity-50"></li>
                    <li class="py-2">
                        <a class="dropdown-item d-flex align-items-center px-4 py-2 text-danger" href="<?= URL_ROOT ?>/logout">
                            <i class="bi bi-box-arrow-right me-3 fs-5" aria-hidden="true"></i>
                            <span class="fw-medium" data-lang-key="logout">Đăng xuất</span>
                        </a>
                    </li>
                </ul>
            </li>

        </ul>
    </nav>

</header><!-- End Header -->
