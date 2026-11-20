<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php require_once __DIR__ . '/../templates/assets.php'; ?>
    <title data-lang-key="task_list_title">Task List</title>
</head>

<body>
    <?php
    require_once __DIR__ . '/../../config/db.php';
    require_once __DIR__ . '/../../config/helper_task.php';
    require_once __DIR__ . '/../../config/helper_phase.php';
    require_once __DIR__ . '/../../config/helper_class.php';
    require_once __DIR__ . '/../templates/header.php';
    require_once __DIR__ . '/../templates/sidebar.php';
    


    // Get phase_id and topic_id from URL
    $phaseId = isset($_GET['phase_id']) ? intval($_GET['phase_id']) : 0;
    $topicId = isset($_GET['topic_id']) ? intval($_GET['topic_id']) : 0;

    if (!$phaseId || !$topicId) {
        header('Location: /class');
        exit;
    }

    // Get phase details
    $phase = getPhaseDetails($phaseId);
    if (!$phase) {
        header('Location: /class');
        exit;
    }

    // Get topic details
    $topic = getTopicDetails($topicId);
    if (!$topic) {
        header('Location: /class');
        exit;
    }

    // Get tasks assigned to this student in this phase
    $tasks = getStudentAssignedTasks($phaseId, $_SESSION['user']['user_id']);
    ?>

    <main id="main" class="main">
        <div class="pagetitle">
            <h1 data-lang-key="task_list_title">Task List</h1>
            <nav>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="/class"><?= $_SESSION['current_class_name']; ?></a></li>
                    <li class="breadcrumb-item"><a href="/class"><?= htmlspecialchars($topic['topic_name']) ?></a></li>
                    <li class="breadcrumb-item"><a href="<?= URL_ROOT ?>/class/phases?topic_id=<?= $topicId ?>">Learning
                            Phases</a></li>
                    <li class="breadcrumb-item active">Nhiệm vụ · Giai đoạn <?= $phase['phase_number'] ?></li>
                </ol>
            </nav>
        </div><!-- End Page Title -->

        <section class="section task-list-page">
            <div class="row">
                <div class="col-lg-12">
                    <div class="card">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h5 class="card-title mb-0">
                                    <i class="bi bi-list-task"></i>
                                    <?= htmlspecialchars($phase['phase_name']) ?>
                                </h5>
                                <a href="<?= URL_ROOT ?>/class/phases?topic_id=<?= $topicId ?>"
                                    class="btn btn-outline-secondary">
                                    <i class="bi bi-arrow-left"></i> <span>Quay lại</span>
                                </a>
                            </div>

                            <?php if (empty($tasks)): ?>
                                <div class="alert alert-info">
                                    <i class="bi bi-info-circle"></i>
                                    <span>Bạn chưa được giao nhiệm vụ nào trong giai đoạn này.</span>
                                </div>
                            <?php else: ?>
                                <div class="list-group">
                                    <?php foreach ($tasks as $task): ?>
                                        <div class="list-group-item">
                                            <div class="d-flex w-100 justify-content-between align-items-start mb-2">
                                                <div class="flex-grow-1">
                                                    <div class="d-flex align-items-center gap-2 mb-2">
                                                        <h5 class="mb-0">
                                                            <i class="bi bi-clipboard-check text-primary"></i>
                                                            <?= htmlspecialchars($task['title']) ?>
                                                        </h5>

                                                        <!-- Delivery Type Badge -->
                                                        <?php if ($task['delivery_type'] == 'user'): ?>
                                                            <span class="badge bg-primary">
                                                                <i class="bi bi-person"></i> <span>Cá nhân</span>
                                                            </span>
                                                        <?php elseif ($task['delivery_type'] == 'group'): ?>
                                                            <span class="badge bg-info">
                                                                <i class="bi bi-people"></i> <span>Nhóm</span>: <?= htmlspecialchars($task['assigned_to_name'] ?? 'N/A') ?>
                                                            </span>
                                                        <?php endif; ?>

                                                        <!-- Status Badge -->
                                                        <?php $status = (int) ($task['delivery_status'] ?? 0); ?>
                                                        <?php if ($status === 2): ?>
                                                            <span class="badge bg-success">
                                                                <i class="bi bi-check-circle"></i> <span>Đã hoàn thành</span>
                                                            </span>
                                                        <?php elseif ($status === 1): ?>
                                                            <span class="badge bg-primary">
                                                                <i class="bi bi-arrow-repeat"></i> <span>In Progress</span>
                                                            </span>
                                                        <?php else: ?>
                                                            <span class="badge bg-warning text-dark">
                                                                <i class="bi bi-clock"></i> <span>Not Started</span>
                                                            </span>
                                                        <?php endif; ?>
                                                    </div>

                                                    <?php if (!empty($task['description'])): ?>
                                                        <p class="mb-2 text-muted">
                                                            <?= nl2br(htmlspecialchars($task['description'])) ?>
                                                        </p>
                                                    <?php endif; ?>

                                                    <div class="d-flex flex-wrap gap-3 align-items-center small text-muted">
                                                        <span>
                                                            <i class="bi bi-person-badge"></i>
                                                            <span>Người tạo</span>:
                                                            <strong><?= htmlspecialchars($task['added_by_name'] ?? 'N/A') ?></strong>
                                                        </span>
                                                        <span>
                                                            <i class="bi bi-calendar"></i>
                                                            <?= date('d/m/Y H:i', strtotime($task['created_at'])) ?>
                                                        </span>
                                                        <?php if (!empty($task['deadline'])):
                                                            $deadlineTime = strtotime($task['deadline']);
                                                            $isOverdue = $deadlineTime < time();
                                                            $badgeClass = $isOverdue ? 'bg-danger' : 'bg-warning text-dark';
                                                        ?>
                                                            <span class="badge <?= $badgeClass ?>">
                                                                <i class="bi bi-clock"></i>
                                                                <?= $isOverdue ? 'Quá hạn' : 'Hạn:' ?>
                                                                <?= date('d/m/Y H:i', $deadlineTime) ?>
                                                            </span>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>

                                                <div class="ms-3">
                                                    <?php if ($task['delivery_status'] == 1): ?>
                                                        <a href="/class/task/perform?task_id=<?= $task['task_id'] ?>&topic_id=<?= $topicId ?>&phase_id=<?= $phaseId ?>&deliver_id=<?= $task['id_deliver'] ?>"
                                                            class="btn btn-sm btn-outline-success">
                                                            <i class="bi bi-eye"></i> <span>View Submission</span>
                                                        </a>
                                                    <?php else: ?>
                                                        <a href="/class/task/perform?task_id=<?= $task['task_id'] ?>&topic_id=<?= $topicId ?>&phase_id=<?= $phaseId ?>&deliver_id=<?= $task['id_deliver'] ?>"
                                                            class="btn btn-sm btn-success">
                                                            <i class="bi bi-play-circle"></i> <span>Làm bài</span>
                                                        </a>
                                                    <?php endif; ?>
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