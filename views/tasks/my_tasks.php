<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php require_once __DIR__ . '/../templates/assets.php'; ?>
    <title data-lang-key="menu_my_tasks">My Tasks</title>
</head>

<body>
    <?php
    require_once __DIR__ . '/../../config/db.php';
    require_once __DIR__ . '/../../config/helper_homework.php';
    require_once __DIR__ . '/../../config/helper_class.php';
    require_once __DIR__ . '/../../config/helper_breadcrumb.php';
    require_once __DIR__ . '/../templates/header.php';
    require_once __DIR__ . '/../templates/sidebar.php';
    


    // Get current user and class
    $userId = $_SESSION['user']['user_id'] ?? 0;
    $classId = getCurrentClassId();

    if (!$userId || !$classId) {
        echo '<main id="main" class="main"><div class="alert alert-warning">Please log in and select a class.</div></main>';
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
                creator.fullname AS created_by_name,
                CASE 
                    WHEN d.type = 'group' THEN g.group_name
                    WHEN d.type = 'user' THEN u.fullname
                    ELSE NULL
                END AS assignment_name
            FROM deliver d
            INNER JOIN task t ON d.task_id = t.task_id
            INNER JOIN phase p ON t.phase_id = p.phase_id
            INNER JOIN topic top ON p.topic_id = top.topic_id
            LEFT JOIN users creator ON t.added_by = creator.user_id
            LEFT JOIN `group` g ON d.type = 'group' AND d.value = g.group_id
            LEFT JOIN users u ON d.type = 'user' AND d.value = u.user_id
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
            return ['label' => 'Ongoing', 'class' => 'status-active'];
        }

        if ($now < $startTime) {
            return ['label' => 'Upcoming', 'class' => 'status-pending'];
        }

        if ($now > $endTime) {
            return ['label' => 'Ended', 'class' => 'status-overdue'];
        }

        if ($now >= $startTime && $now <= $endTime) {
            return ['label' => 'Ongoing', 'class' => 'status-active'];
        }

        return ['label' => 'Not Started', 'class' => 'status-pending'];
    }

    /**
     * Get assignment type label
     */
    function getAssignmentTypeLabel($type, $assignmentName = null)
    {
        switch ($type) {
            case 'user':
                return '<i class="bi bi-person-fill"></i> Individual';
            case 'group':
                $label = '<i class="bi bi-people-fill"></i> Group';
                if ($assignmentName) {
                    $label .= ': ' . htmlspecialchars($assignmentName);
                }
                return $label;
            case 'class':
                return '<i class="bi bi-building"></i> Whole Class';
            default:
                return $type;
        }
    }

    $tasks = getMyTasks($userId, $classId, $filter);
    $totalTasks = count($tasks);
    ?>

    <main id="main" class="main">
        <div class="pagetitle">
            <h1 data-lang-key="menu_my_tasks">My Tasks</h1>
            <nav>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="<?= breadcrumbClassUrl() ?>"><?= breadcrumbClassLabel() ?></a></li>
                    <li class="breadcrumb-item active" data-lang-key="menu_my_tasks">Nhiệm vụ của tôi</li>
                </ol>
            </nav>
        </div><!-- End Page Title -->

        <section class="section">
            <div class="row">
                <div class="col-lg-12">
                    <div class="card">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center mt-3 mb-3">
                                <h5 class=" mb-0">
                                    <span data-lang-key="all_tasks">All Tasks</span>
                                    <span class="badge bg-primary rounded-pill"><?= $totalTasks ?></span>
                                </h5>
                            </div>

                            <!-- Filter Tabs -->
                            <ul class="nav nav-tabs filter-tabs" role="tablist">
                                <li class="nav-item">
                                    <a class="nav-link <?= $filter === 'all' ? 'active' : '' ?>"
                                        href="?filter=all">
                                        <span data-lang-key="filter_all">All</span>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link <?= $filter === 'active' ? 'active' : '' ?>"
                                        href="?filter=active">
                                        <span data-lang-key="ongoing">Đang diễn ra</span>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link <?= $filter === 'upcoming' ? 'active' : '' ?>"
                                        href="?filter=upcoming">
                                        <span data-lang-key="upcoming">Sắp tới</span>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link <?= $filter === 'past' ? 'active' : '' ?>"
                                        href="?filter=past">
                                        <span data-lang-key="ended">Đã kết thúc</span>
                                    </a>
                                </li>
                            </ul>

                            <!-- Tasks List -->
                            <?php if (empty($tasks)): ?>
                                <div class="empty-state">
                                    <i class="bi bi-clipboard-x"></i>
                                    <h5 data-lang-key="no_tasks_yet">Chưa có nhiệm vụ</h5>
                                    <p data-lang-key="no_tasks_assigned_in_class">Bạn chưa được giao nhiệm vụ nào trong lớp này.</p>
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
                                                            <span>End: <?= date('d/m/Y H:i', strtotime($task['end_time'])) ?></span>
                                                        </div>
                                                    </div>

                                                    <div class="d-flex justify-content-between align-items-center">
                                                        <div class="assignment-type">
                                                            <?= getAssignmentTypeLabel($task['assignment_type'], $task['assignment_name'] ?? null) ?>
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
    require_once __DIR__ . '/../templates/footer.php';
    require_once __DIR__ . '/../templates/vendors.php';
    ?>
</body>

</html>
