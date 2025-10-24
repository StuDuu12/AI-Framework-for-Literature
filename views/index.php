<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php require_once './views/templates/assets.php'; ?>
    <title>Document</title>
</head>

<body class="toggle-sidebar">
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
            <h1>Danh sách các lớp</h1>
        </div><!-- End Page Title -->

        <section class="section dashboard">
        <?php 
        $userid = $_SESSION['user']['user_id'];
        
        global $pdo;
        $stmt = $pdo->prepare("SELECT cm.class_id, c.class_name, cm.role FROM `class_member` cm LEFT JOIN `class` c ON c.class_id = cm.class_id WHERE `user_id` = :userid");
        $stmt->bindParam(':userid', $userid, PDO::PARAM_INT);
        $stmt->execute();
        $classes = $stmt->fetchAll(PDO::FETCH_ASSOC);
        if ($classes):
        ?>
            <div class="row">
                <?php foreach ($classes as $class): ?>
                    <div class="col-lg-4 col-md-6 mb-4">
                        <div class="card h-100">
                            <div class="card-body">
                                <h5 class="card-title"><?php echo htmlspecialchars($class['class_name']); ?></h5>
                                <p class="card-text">Vai trò: <?php echo htmlspecialchars(ucfirst($class['role'])); ?></p>
                                <a href="/joinclass?id=<?php echo urlencode($class['class_id']); ?>" class="btn btn-primary">Vào lớp</a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="alert alert-info" role="alert">
                Bạn chưa tham gia lớp học nào.
            </div>
        <?php endif; ?>
        </section>

    </main><!-- End #main -->
    <?php
    require_once 'views/templates/footer.php';
    require_once 'views/templates/vendors.php';
    ?>
</body>

</html>