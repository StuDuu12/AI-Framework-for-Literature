<!-- ======= Header ======= -->
<header id="header" class="header glass fixed-top d-flex align-items-center">

    <div class="d-flex align-items-center justify-content-between">
        <a href="<?= URL_ROOT . '/' ?>" class="logo d-flex align-items-center">
            <img src="<?= URL_ROOT ?>/assets/img/fit.png" alt="Literature System Logo" style="width: 30px; height: auto;">
            <span class="d-none d-lg-block text-primary fw-bold" data-lang-key="system_title">Literature System</span>
        </a>
        <i class="bi bi-list toggle-sidebar-btn"></i>
    </div><!-- End Logo -->
    <div class="search-bar">
        <form class="search-form d-flex align-items-center" method="POST" action="#">
            <input type="text" name="query" placeholder="Search" title="Enter search keywords" data-lang-key="search_placeholder" data-lang-attr="placeholder">
            <button type="submit" title="Search"><i class="bi bi-search"></i></button>
        </form>
    </div><!-- End Search Bar -->

    <nav class="header-nav ms-auto">
        <ul class="d-flex align-items-center">

            <li class="nav-item d-block d-lg-none">
                <a class="nav-link nav-icon search-bar-toggle " href="#">
                    <i class="bi bi-search"></i>
                </a>
            </li><!-- End Search Icon-->

            <!-- Theme Toggle Button -->
            <li class="nav-item">
                <button class="nav-link nav-icon theme-toggle" onclick="toggleTheme()" title="Toggle Theme" data-lang-key="theme_toggle" data-lang-attr="title">
                    <i class="bi bi-moon-fill" id="theme-icon"></i>
                </button>
            </li><!-- End Theme Toggle -->

            <!-- Language Toggle Button -->
            <li class="nav-item">
                <button class="nav-link nav-icon" id="language-toggle-btn" title="Toggle Language" style="border: none; background: transparent; padding: 0.5rem 0.75rem;">
                    <span id="language-icon" style="font-weight: 600; font-size: 0.875rem; color: var(--bs-nav-link-color);">VI</span>
                </button>
            </li><!-- End Language Toggle -->

            <!-- Notifications -->
            <li class="nav-item dropdown">
                <a class="nav-link nav-icon position-relative" href="#" data-bs-toggle="dropdown" aria-expanded="false" title="Notifications" data-lang-key="notifications" data-lang-attr="title">
                    <i class="bi bi-bell"></i>
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
                <a class="nav-link nav-profile d-flex align-items-center pe-0" href="#" data-bs-toggle="dropdown" aria-expanded="false">
                    <div class="profile-avatar rounded-circle d-flex align-items-center justify-content-center shadow-sm bg-white" style="width: 42px; height: 42px; border: 2px solid var(--lit-primary);">
                        <span class="text-primary fw-bold fs-5">
                            <?php 
                                $fname = isset($_SESSION['user']['fullname']) ? $_SESSION['user']['fullname'] : 'U';
                                echo mb_strtoupper(mb_substr($fname, 0, 1, 'UTF-8'), 'UTF-8'); 
                            ?>
                        </span>
                    </div>
                </a><!-- End Profile Icon -->

                <ul class="dropdown-menu dropdown-menu-end dropdown-menu-arrow profile shadow border-0 rounded-4 mt-2" style="min-width: 260px; padding: 0;">
                    <li class="dropdown-header d-flex flex-column align-items-center p-4 bg-light rounded-top-4 border-bottom">
                        <div class="rounded-circle bg-primary d-flex align-items-center justify-content-center shadow-sm mb-3" style="width: 60px; height: 60px;">
                            <span class="text-white fw-bold fs-3">
                                <?php echo mb_strtoupper(mb_substr($fname, 0, 1, 'UTF-8'), 'UTF-8'); ?>
                            </span>
                        </div>
                        <h6 class="fw-bold text-dark fs-6 mb-1 text-truncate" style="max-width: 220px;" title="<?php echo isset($_SESSION['user']['fullname']) ? htmlspecialchars($_SESSION['user']['fullname']) : 'User'; ?>">
                            <?php echo isset($_SESSION['user']['fullname']) ? htmlspecialchars($_SESSION['user']['fullname']) : 'User'; ?>
                        </h6>
                        <span class="text-muted small text-truncate mb-2" style="max-width: 220px;" title="<?php echo isset($_SESSION['user']['email']) ? htmlspecialchars($_SESSION['user']['email']) : ''; ?>">
                            <?php echo isset($_SESSION['user']['email']) ? htmlspecialchars($_SESSION['user']['email']) : 'No email'; ?>
                        </span>
                        <span class="badge bg-primary-subtle text-primary rounded-pill px-3 py-1 fw-semibold">
                            <?php
                            if (isset($_SESSION['user']['role'])) {
                                switch ($_SESSION['user']['role']) {
                                    case 'admin': echo 'Administrator'; break;
                                    case 'teacher': echo 'Teacher'; break;
                                    case 'student': echo 'Student'; break;
                                    default: echo 'User';
                                }
                            } else {
                                echo 'User';
                            }
                            ?>
                        </span>
                    </li>

                    <li class="py-2">
                        <a class="dropdown-item d-flex align-items-center px-4 py-2" href="<?= URL_ROOT ?>/profile" style="transition: all 0.2s;">
                            <i class="bi bi-person me-3 fs-5 text-muted"></i>
                            <span class="fw-medium">Hồ sơ cá nhân</span>
                        </a>
                    </li>
                    
                    <li>
                        <hr class="dropdown-divider m-0 opacity-50">
                    </li>

                    <li class="py-2">
                        <a class="dropdown-item d-flex align-items-center px-4 py-2 text-danger" href="<?= URL_ROOT ?>/logout" style="transition: all 0.2s;">
                            <i class="bi bi-box-arrow-right me-3 fs-5"></i>
                            <span class="fw-medium">Đăng xuất</span>
                        </a>
                    </li>
                </ul><!-- End Profile Dropdown Items -->
            </li><!-- End Profile Nav -->

        </ul>
    </nav><!-- End Icons Navigation -->

</header><!-- End Header -->