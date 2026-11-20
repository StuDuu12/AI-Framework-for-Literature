<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php require_once __DIR__ . '/../templates/assets.php'; ?>
    <title data-lang-key="menu_dashboard">Tổng quan</title>
</head>

<body>
    <?php
    require_once __DIR__ . '/../templates/header.php';
    require_once __DIR__ . '/../templates/sidebar.php';
    ?>

    <main id="main" class="main">
        <div class="pagetitle">
            <h1 data-lang-key="menu_dashboard">Tổng quan</h1>
            <nav>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="<?= URL_ROOT ?>/" data-lang-key="back_home">Trang chủ</a></li>
                    <li class="breadcrumb-item active" data-lang-key="menu_dashboard">Trang chủ</li>
                </ol>
            </nav>
        </div><!-- End Page Title -->

        <section class="section dashboard">

        </section>

    </main><!-- End #main -->
    <?php
    require_once __DIR__ . '/../templates/footer.php';
    require_once __DIR__ . '/../templates/vendors.php';
    ?>
</body>

</html>