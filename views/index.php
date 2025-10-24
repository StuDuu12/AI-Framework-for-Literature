<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php require_once './views/templates/assets.php'; ?>
    <title>Document</title>
</head>

<body>
    <?php
    require_once './views/templates/header.php';
    if (isset($_SESSION['user']['role']) && $_SESSION['user']['role'] === 'teacher') {
        require_once './views/templates/sidebar_teacher.php';
    } else if(isset($_SESSION['user']['role']) && $_SESSION['user']['role'] === 'student') {
        require_once './views/templates/sidebar_student.php';
    }
    ?>

    <main id="main" class="main">
        <div class="pagetitle">
            <h1>Dashboard</h1>
            <nav>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="index.html">Home</a></li>
                    <li class="breadcrumb-item active">Dashboard</li>
                </ol>
            </nav>
        </div><!-- End Page Title -->

        <section class="section dashboard">
        <pre>
            <?php 
            var_dump($_SESSION);
            ?>
        </pre>
        </section>

    </main><!-- End #main -->
    <?php
    require_once 'views/templates/footer.php';
    require_once 'views/templates/vendors.php';
    ?>
</body>

</html>