<!-- ======= Header ======= -->
<header id="header" class="header fixed-top d-flex align-items-center">

    <div class="d-flex align-items-center justify-content-between">
        <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'student'): ?>
            <a href="student.php" class="logo d-flex align-items-center">
                <i class="bi bi-book text-primary fs-2 me-2"></i>
                <span class="d-none d-lg-block text-primary fw-bold">Literature System</span>
            </a>
        <?php else: ?>
            <a href="main.php" class="logo d-flex align-items-center">
                <i class="bi bi-mortarboard text-primary fs-2 me-2"></i>
                <span class="d-none d-lg-block text-primary fw-bold">Literature System</span>
            </a>
        <?php endif; ?>
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

            <li class="nav-item dropdown pe-3">
                <a class="nav-link nav-profile d-flex align-items-center pe-0" href="#" data-bs-toggle="dropdown">
                    <div class="rounded-circle bg-primary d-flex align-items-center justify-content-center" style="width: 36px; height: 36px;">
                        <i class="bi bi-person-fill text-white"></i>
                    </div>
                    <span class="d-none d-md-block dropdown-toggle ps-2" id="headerProfileName">
                        <?php echo isset($_SESSION['username']) ? htmlspecialchars($_SESSION['username']) : 'User'; ?>
                    </span>
                </a><!-- End Profile Icon -->

                <ul class="dropdown-menu dropdown-menu-end dropdown-menu-arrow profile">
                    <li class="dropdown-header">
                        <h6 id="headerProfileFullName">
                            <?php echo isset($_SESSION['username']) ? htmlspecialchars($_SESSION['username']) : 'User'; ?>
                        </h6>
                        <span id="headerProfileRole">
                            <?php
                            if (isset($_SESSION['role'])) {
                                switch ($_SESSION['role']) {
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
                                        echo ucfirst($_SESSION['role']);
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
                                <?php echo isset($_SESSION['email']) ? htmlspecialchars($_SESSION['email']) : 'No email'; ?>
                            </small>
                        </div>
                    </li>

                    <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'student'): ?>
                        <li>
                            <hr class="dropdown-divider">
                        </li>
                        <li>
                            <a class="dropdown-item d-flex align-items-center" href="student.php">
                                <i class="bi bi-house"></i>
                                <span>Trang chủ</span>
                            </a>
                        </li>
                    <?php endif; ?>

                    <?php if (isset($_SESSION['role']) && ($_SESSION['role'] === 'teacher' || $_SESSION['role'] === 'admin')): ?>
                        <li>
                            <hr class="dropdown-divider">
                        </li>
                        <li>
                            <a class="dropdown-item d-flex align-items-center" href="main.php">
                                <i class="bi bi-speedometer2"></i>
                                <span>Dashboard</span>
                            </a>
                        </li>
                    <?php endif; ?>

                    <li>
                        <hr class="dropdown-divider">
                    </li>

                    <li>
                        <a class="dropdown-item d-flex align-items-center" href="../auth/logout.php" onclick="return confirm('Bạn có chắc muốn đăng xuất?');">
                            <i class="bi bi-box-arrow-right text-danger"></i>
                            <span class="text-danger">Đăng xuất</span>
                        </a>
                    </li>

                </ul><!-- End Profile Dropdown Items -->
            </li><!-- End Profile Nav -->

        </ul>
    </nav><!-- End Icons Navigation -->

</header><!-- End Header -->