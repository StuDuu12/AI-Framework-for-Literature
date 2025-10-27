<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php require_once './views/templates/assets.php'; ?>
    <title>Nhiệm vụ của tôi</title>
    <style>
        body {
            font-family: 'Nunito', 'Segoe UI', 'Open Sans', sans-serif;
        }

        .task-card {
            transition: transform 0.2s, box-shadow 0.2s;
            border-left: 4px solid #4154f1;
        }

        .task-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        .task-status {
            font-size: 0.875rem;
            padding: 0.25rem 0.75rem;
            border-radius: 12px;
            font-weight: 500;
        }

        .status-pending {
            background-color: #fff3cd;
            color: #856404;
        }

        .status-completed {
            background-color: #d1e7dd;
            color: #0f5132;
        }

        .status-overdue {
            background-color: #f8d7da;
            color: #842029;
        }

        .phase-badge {
            background-color: #e7f1ff;
            color: #084298;
            padding: 0.25rem 0.5rem;
            border-radius: 6px;
            font-size: 0.875rem;
        }

        .assignment-type {
            display: inline-flex;
            align-items: center;
            gap: 0.25rem;
            font-size: 0.8rem;
            color: #6c757d;
        }

        .empty-state {
            text-align: center;
            padding: 3rem 1rem;
            color: #6c757d;
        }

        .empty-state i {
            font-size: 4rem;
            color: #dee2e6;
            margin-bottom: 1rem;
        }

        .filter-tabs {
            border-bottom: 2px solid #f0f0f0;
            margin-bottom: 1.5rem;
        }

        .filter-tabs .nav-link {
            color: #6c757d;
            border: none;
            padding: 0.75rem 1.5rem;
            position: relative;
        }

        .filter-tabs .nav-link.active {
            color: #4154f1;
            background: transparent;
            font-weight: 600;
        }

        .filter-tabs .nav-link.active::after {
            content: '';
            position: absolute;
            bottom: -2px;
            left: 0;
            right: 0;
            height: 2px;
            background-color: #4154f1;
        }

        .task-meta {
            display: flex;
            align-items: center;
            gap: 1rem;
            flex-wrap: wrap;
            font-size: 0.875rem;
            color: #6c757d;
        }

        .task-meta>div {
            display: flex;
            align-items: center;
            gap: 0.25rem;
        }
    </style>
</head>

<body>
    <?php
    require_once './config/db.php';
    require_once './config/helper_homework.php';
    require_once './config/helper_class.php';
    require_once './views/templates/header.php';

    if (!isset($_SESSION['user']['role']) || $_SESSION['user']['role'] !== 'student') {
        require_once './views/templates/sidebar_teacher.php';
    } else {
        require_once './views/templates/sidebar_student.php';
    }

    // Get current user and class
    $userId = $_SESSION['user']['user_id'] ?? 0;
    $classId = getCurrentClassId();

    if (!$userId || !$classId) {
        echo '<main id="main" class="main"><div class="alert alert-warning">Vui lòng đăng nhập và chọn lớp học.</div></main>';
        exit;
    }

    // Get filter
    $filter = $_GET['filter'] ?? 'all';

    /**
     * Get all tasks assigned to current student
     */
    function getMyTasks($userId, $classId, $filter = 'all')
    {
        global $pdo;

        $sql = "
            SELECT DISTINCT
                t.task_id,
                t.title,
                t.description,
                t.created_at,
                t.updated_at,
                p.phase_id,
                p.phase_number,
                p.phase_name,
                p.start_time,
                p.end_time,
                p.flag_active,
                top.topic_id,
                top.topic_name,
                d.type AS assignment_type,
                d.value AS assignment_value,
                d.created_at AS assigned_at,
                creator.fullname AS created_by_name
            FROM deliver d
            INNER JOIN task t ON d.task_id = t.task_id
            INNER JOIN phase p ON t.phase_id = p.phase_id
            INNER JOIN topic top ON p.topic_id = top.topic_id
            LEFT JOIN users creator ON t.added_by = creator.user_id
            WHERE top.class_id = :class_id
            AND (
                (d.type = 'user' AND d.value = :user_id)
                OR (d.type = 'class' AND EXISTS (
                    SELECT 1 FROM class_member cm 
                    WHERE cm.class_id = d.value AND cm.user_id = :user_id
                ))
                OR (d.type = 'group' AND EXISTS (
                    SELECT 1 FROM group_member gm 
                    WHERE gm.group_id = d.value AND gm.user_id = :user_id
                ))
            )
        ";

        // Apply filter
        if ($filter === 'active') {
            $sql .= " AND p.flag_active = 1";
        } elseif ($filter === 'upcoming') {
            $sql .= " AND p.start_time > NOW() AND p.flag_active = 0";
        } elseif ($filter === 'past') {
            $sql .= " AND p.end_time < NOW()";
        }

        $sql .= " ORDER BY p.start_time DESC, t.created_at DESC";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            'user_id' => $userId,
            'class_id' => $classId
        ]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get status of a task based on phase timing
     */
    function getTaskStatus($task)
    {
        $now = time();
        $startTime = strtotime($task['start_time']);
        $endTime = strtotime($task['end_time']);

        if ($task['flag_active'] ?? false) {
            return ['label' => 'Đang diễn ra', 'class' => 'status-completed'];
        }

        if ($now < $startTime) {
            return ['label' => 'Sắp tới', 'class' => 'status-pending'];
        }

        if ($now > $endTime) {
            return ['label' => 'Đã kết thúc', 'class' => 'status-overdue'];
        }

        if ($now >= $startTime && $now <= $endTime) {
            return ['label' => 'Đang diễn ra', 'class' => 'status-completed'];
        }

        return ['label' => 'Chưa bắt đầu', 'class' => 'status-pending'];
    }

    /**
     * Get assignment type label
     */
    function getAssignmentTypeLabel($type)
    {
        switch ($type) {
            case 'user':
                return '<i class="bi bi-person-fill"></i> Cá nhân';
            case 'group':
                return '<i class="bi bi-people-fill"></i> Nhóm';
            case 'class':
                return '<i class="bi bi-building"></i> Cả lớp';
            default:
                return $type;
        }
    }

    $tasks = getMyTasks($userId, $classId, $filter);
    $totalTasks = count($tasks);
    ?>

    <main id="main" class="main">
        <div class="pagetitle">
            <h1>Nhiệm vụ của tôi</h1>
            <nav>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="/class"><?= $_SESSION['current_class_name'] ?? 'Lớp học' ?></a></li>
                    <li class="breadcrumb-item active">Nhiệm vụ của tôi</li>
                </ol>
            </nav>
        </div><!-- End Page Title -->

        <section class="section">
            <div class="row">
                <div class="col-lg-12">
                    <div class="card">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center mt-3 mb-3">
                                <h5 class="card-title mb-0">
                                    Tất cả nhiệm vụ
                                    <span class="badge bg-primary rounded-pill"><?= $totalTasks ?></span>
                                </h5>
                            </div>

                            <!-- Filter Tabs -->
                            <ul class="nav nav-tabs filter-tabs" role="tablist">
                                <li class="nav-item">
                                    <a class="nav-link <?= $filter === 'all' ? 'active' : '' ?>"
                                        href="?filter=all">
                                        Tất cả
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link <?= $filter === 'active' ? 'active' : '' ?>"
                                        href="?filter=active">
                                        Đang diễn ra
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link <?= $filter === 'upcoming' ? 'active' : '' ?>"
                                        href="?filter=upcoming">
                                        Sắp tới
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link <?= $filter === 'past' ? 'active' : '' ?>"
                                        href="?filter=past">
                                        Đã kết thúc
                                    </a>
                                </li>
                            </ul>

                            <!-- Tasks List -->
                            <?php if (empty($tasks)): ?>
                                <div class="empty-state">
                                    <i class="bi bi-clipboard-x"></i>
                                    <h5>Chưa có nhiệm vụ nào</h5>
                                    <p>Bạn chưa được giao nhiệm vụ nào trong lớp học này.</p>
                                </div>
                            <?php else: ?>
                                <div class="row">
                                    <?php foreach ($tasks as $task):
                                        $status = getTaskStatus($task);
                                    ?>
                                        <div class="col-lg-6 mb-3">
                                            <div class="card task-card h-100">
                                                <div class="card-body">
                                                    <div class="d-flex justify-content-between align-items-start mb-2">
                                                        <h5 class="card-title mb-1">
                                                            <a href="<?= URL_ROOT ?>/class/task/perform?task_id=<?= $task['task_id'] ?>&topic_id=<?= $task['topic_id'] ?>&phase_id=<?= $task['phase_id'] ?>"
                                                                class="text-decoration-none text-dark">
                                                                <?= htmlspecialchars($task['title']) ?>
                                                            </a>
                                                        </h5>
                                                        <span class="task-status <?= $status['class'] ?>">
                                                            <?= $status['label'] ?>
                                                        </span>
                                                    </div>

                                                    <?php if ($task['description']): ?>
                                                        <p class="text-muted small mb-2">
                                                            <?= mb_substr(htmlspecialchars($task['description']), 0, 100) ?>
                                                            <?= mb_strlen($task['description']) > 100 ? '...' : '' ?>
                                                        </p>
                                                    <?php endif; ?>

                                                    <div class="task-meta mb-3">
                                                        <div>
                                                            <i class="bi bi-book text-primary"></i>
                                                            <span><?= htmlspecialchars($task['topic_name']) ?></span>
                                                        </div>
                                                        <div>
                                                            <span class="phase-badge">
                                                                Bước <?= $task['phase_number'] ?>: <?= htmlspecialchars($task['phase_name']) ?>
                                                            </span>
                                                        </div>
                                                    </div>

                                                    <div class="task-meta mb-3">
                                                        <div>
                                                            <i class="bi bi-calendar-event"></i>
                                                            <span>Bắt đầu: <?= date('d/m/Y H:i', strtotime($task['start_time'])) ?></span>
                                                        </div>
                                                        <div>
                                                            <i class="bi bi-calendar-check"></i>
                                                            <span>Kết thúc: <?= date('d/m/Y H:i', strtotime($task['end_time'])) ?></span>
                                                        </div>
                                                    </div>

                                                    <div class="d-flex justify-content-between align-items-center">
                                                        <div class="assignment-type">
                                                            <?= getAssignmentTypeLabel($task['assignment_type']) ?>
                                                        </div>
                                                        <a href="<?= URL_ROOT ?>/class/task/perform?task_id=<?= $task['task_id'] ?>&topic_id=<?= $task['topic_id'] ?>&phase_id=<?= $task['phase_id'] ?>"
                                                            class="btn btn-sm btn-primary">
                                                            <i class="bi bi-arrow-right-circle"></i> Xem chi tiết
                                                        </a>
                                                    </div>

                                                    <div class="text-muted small mt-2">
                                                        <i class="bi bi-person"></i> Giáo viên: <?= htmlspecialchars($task['created_by_name'] ?? 'N/A') ?>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </section>

    </main><!-- End #main -->

    <?php
    require_once './views/templates/footer.php';
    require_once './views/templates/vendors.php';
    ?>
</body>

</html>