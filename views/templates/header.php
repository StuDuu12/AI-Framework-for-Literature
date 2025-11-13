<!-- Optimized Dropdown Styles - Ultra Smooth -->
<style>
    /* Force GPU acceleration */
    .dropdown-menu {
        will-change: transform;
        transform: translate3d(0, 0, 0);
        backface-visibility: hidden;
        perspective: 1000px;
    }

    /* Smooth show animation */
    .dropdown-menu.show {
        display: block !important;
        opacity: 1 !important;
        visibility: visible !important;
    }

    /* Hidden state - keep display: none for Bootstrap */
    .dropdown-menu:not(.show) {
        display: none;
    }

    /* Ultra fast hover - minimal transition */
    .dropdown-item {
        transition: background-color 0.1s ease !important;
        transform: translate3d(0, 0, 0);
    }

    .dropdown-item:hover,
    .dropdown-item:focus {
        background-color: rgba(13, 110, 253, 0.08) !important;
    }

    /* Profile dropdown optimization */
    .nav-profile {
        cursor: pointer;
        transform: translate3d(0, 0, 0);
    }

    /* Fixed width to prevent layout shift */
    .dropdown-menu.profile {
        min-width: 280px;
        max-width: 280px;
        right: 0 !important;
        left: auto !important;
    }

    /* Optimize text rendering */
    .dropdown-menu * {
        text-rendering: optimizeSpeed;
        -webkit-font-smoothing: subpixel-antialiased;
    }
</style>

<!-- ======= Header ======= -->
<header id="header" class="header fixed-top d-flex align-items-center">

    <div class="d-flex align-items-center justify-content-between">
        <a href="<?= URL_ROOT . '/' ?>" class="logo d-flex align-items-center">
            <i class="bi bi-book text-primary fs-2 me-2"></i>
            <span class="d-none d-lg-block text-primary fw-bold">Literature System</span>
        </a>
        <i class="bi bi-list toggle-sidebar-btn"></i>
    </div><!-- End Logo -->
    <div class="search-bar">
        <form class="search-form d-flex align-items-center" method="POST" action="#">
            <input type="text" name="query" placeholder="Search" title="Enter search keyword">
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
                <button class="nav-link nav-icon theme-toggle" onclick="toggleTheme()" title="Đổi giao diện">
                    <i class="bi bi-moon-fill" id="theme-icon"></i>
                </button>
            </li><!-- End Theme Toggle -->

            <li class="nav-item dropdown pe-3" id="profileDropdown">
                <a class="nav-link nav-profile d-flex align-items-center pe-0" href="#" data-bs-toggle="dropdown" aria-expanded="false">
                    <div class="rounded-circle bg-primary d-flex align-items-center justify-content-center" style="width: 36px; height: 36px;">
                        <i class="bi bi-person-fill text-white d-flex justify-content-around"></i>
                    </div>
                    <span class="d-none d-md-block dropdown-toggle ps-2" id="headerProfileName">
                        <?php echo isset($_SESSION['user']['fullname']) ? htmlspecialchars($_SESSION['user']['fullname']) : 'User'; ?>
                    </span>
                </a><!-- End Profile Icon -->

                <ul class="dropdown-menu dropdown-menu-end dropdown-menu-arrow profile">
                    <li class="dropdown-header">
                        <h6 id="headerProfileFullName">
                            <?php echo isset($_SESSION['user']['fullname']) ? htmlspecialchars($_SESSION['user']['fullname']) : 'User'; ?>
                        </h6>
                        <span id="headerProfileRole">
                            <?php
                            if (isset($_SESSION['user']['role'])) {
                                switch ($_SESSION['user']['role']) {
                                    case 'admin':
                                        echo 'Quản trị viên';
                                        break;
                                    case 'teacher':
                                        echo 'Giáo viên';
                                        break;
                                    case 'student':
                                        echo 'Học sinh';
                                        break;
                                    default:
                                        echo "";
                                }
                            } else {
                                echo 'User';
                            }
                            ?>
                        </span>
                    </li>
                    <li>
                        <hr class="dropdown-divider">
                    </li>

                    <li>
                        <div class="dropdown-item-text d-flex align-items-center">
                            <i class="bi bi-envelope me-2"></i>
                            <small class="text-muted" id="headerProfileEmail">
                                <?php echo isset($_SESSION['user']['email']) ? htmlspecialchars($_SESSION['user']['email']) : 'No email'; ?>
                            </small>
                        </div>
                    </li>

                    <li>
                        <hr class="dropdown-divider">
                    </li>

                    <li>
                        <a class="dropdown-item d-flex align-items-center" href="/logout">
                            <i class="bi bi-box-arrow-right text-danger"></i>
                            <span class="text-danger">Đăng xuất</span>
                        </a>
                    </li>

                </ul><!-- End Profile Dropdown Items -->
            </li><!-- End Profile Nav -->

        </ul>
    </nav><!-- End Icons Navigation -->

</header><!-- End Header -->

<!-- Optimized Dropdown Script -->
<script>
    (function() {
        'use strict';

        document.addEventListener('DOMContentLoaded', function() {
            const profileDropdown = document.getElementById('profileDropdown');
            if (!profileDropdown) return;

            const dropdownMenu = profileDropdown.querySelector('.dropdown-menu');
            if (!dropdownMenu) return;

            // Force hardware acceleration for smooth rendering
            dropdownMenu.style.transform = 'translate3d(0, 0, 0)';

            // Prevent multiple rapid clicks
            const dropdownToggle = profileDropdown.querySelector('[data-bs-toggle="dropdown"]');
            if (dropdownToggle) {
                let clickTimeout = null;
                dropdownToggle.addEventListener('click', function(e) {
                    if (clickTimeout) {
                        clearTimeout(clickTimeout);
                    }
                    clickTimeout = setTimeout(() => {
                        clickTimeout = null;
                    }, 150);
                }, {
                    passive: true
                });
            }
        });
    })();
</script>