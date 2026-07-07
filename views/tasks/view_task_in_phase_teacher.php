<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php require_once './views/templates/assets.php'; ?>
    <title data-lang-key="task_list_title">Task List</title>
</head>

<body>
    <?php
    require_once './config/db.php';
    require_once './config/helper_task.php';
    require_once './config/helper_phase.php';
    require_once './config/helper_class.php';
    require_once './views/templates/header.php';
    require_once './views/templates/sidebar.php';
    


    // Get phase_id and topic_id from URL
    $phaseId = isset($_GET['phase_id']) ? intval($_GET['phase_id']) : 0;
    $topicId = isset($_GET['topic_id']) ? intval($_GET['topic_id']) : 0;

    if (!$phaseId || !$topicId) {
        header('Location: /class');
        exit;
    }

    // Get phase details with main_phase info
    $stmtPhase = $pdo->prepare("
        SELECT p.*, mp.phase_code, mp.m_phase_name
        FROM phase p
        LEFT JOIN main_phase mp ON p.m_phase_id = mp.m_phase_id
        WHERE p.phase_id = :phase_id
    ");
    $stmtPhase->execute([':phase_id' => $phaseId]);
    $phase = $stmtPhase->fetch(PDO::FETCH_ASSOC);

    if (!$phase) {
        header('Location: /class');
        exit;
    }

    // Check if this is a peer evaluation phase
    $isPeerEvaluationPhase = ($phase['phase_code'] === 'PHASE_DANHGIANGUOIHOC');
    $isGradingPhase = ($phase['phase_code'] === 'PHASE_CHAMDIEM');

    // Get topic details
    $topic = getTopicDetails($topicId);
    if (!$topic) {
        header('Location: /class');
        exit;
    }

    // Check if user is teacher
    $isTeacher = isTeacherInTopicClass($topicId, $_SESSION['user']['user_id']);

    if (!$isTeacher) {
        header('Location: /class');
        exit;
    }

    // Get all tasks for this phase with assignment stats
    $tasks = getPhaseTasks($phaseId);

    // Get assignment stats for each task
    foreach ($tasks as &$task) {
        $task['assignment_stats'] = getTaskAssignmentStats($task['task_id']);
    }
    ?>

    <main id="main" class="main">
        <div class="pagetitle">
            <h1 data-lang-key="task_list_title">Task List</h1>
            <nav>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="<?= URL_ROOT ?>/class"><?= htmlspecialchars($_SESSION['current_class_name'] ?? 'Class', ENT_QUOTES, 'UTF-8') ?></a></li>
                    <li class="breadcrumb-item"><a href="<?= URL_ROOT ?>/class"><?= htmlspecialchars($topic['topic_name']) ?></a></li>
                    <li class="breadcrumb-item"><a href="<?= URL_ROOT ?>/class/phases?topic_id=<?= $topicId ?>">Learning
                            Phases</a></li>
                    <li class="breadcrumb-item active">Tasks - Step <?= $phase['phase_number'] ?></li>
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
                                <div class="d-flex gap-2">
                                    <a href="<?= URL_ROOT ?>/class/phases?topic_id=<?= $topicId ?>"
                                        class="btn btn-outline-secondary">
                                        <i class="bi bi-arrow-left"></i> <span>Back</span>
                                    </a>
                                    <a href="<?= URL_ROOT ?>/class/task/create?topic_id=<?= $topicId ?>&phase_id=<?= $phaseId ?>"
                                        class="btn btn-primary">
                                        <i class="bi bi-plus-circle"></i> <span>Create Task</span>
                                    </a>
                                </div>
                            </div>

                            <?php if (empty($tasks)): ?>
                                <div class="alert alert-info">
                                    <i class="bi bi-info-circle"></i>
                                    <span>No tasks in this phase yet.</span>
                                </div>
                            <?php else: ?>
                                <div class="list-group">
                                    <?php foreach ($tasks as $task): ?>
                                        <div class="list-group-item">
                                            <div class="d-flex w-100 justify-content-between align-items-start mb-2">
                                                <div class="flex-grow-1">
                                                    <h5 class="mb-1">
                                                        <i class="bi bi-clipboard-check text-primary"></i>
                                                        <?= htmlspecialchars($task['title']) ?>
                                                    </h5>
                                                    <?php if (!empty($task['description'])): ?>
                                                        <p class="mb-2 text-muted">
                                                            <?= nl2br(htmlspecialchars($task['description'])) ?>
                                                        </p>
                                                    <?php endif; ?>

                                                    <div
                                                        class="d-flex flex-wrap gap-3 align-items-center small text-muted mb-2">
                                                        <span>
                                                            <i class="bi bi-person"></i>
                                                            <span>Created by</span>:
                                                            <strong><?= htmlspecialchars($task['added_by_name'] ?? 'N/A') ?></strong>
                                                        </span>
                                                        <span>
                                                            <i class="bi bi-calendar"></i>
                                                            <?= date('d/m/Y H:i', strtotime($task['created_at'])) ?>
                                                        </span>
                                                        <?php if ($task['file_content']): ?>
                                                            <span class="badge bg-success">
                                                                <i class="bi bi-file-earmark-text"></i> <span>Has Content</span>
                                                            </span>
                                                        <?php endif; ?>
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

                                                    <?php if (isset($task['assignment_stats']) && $task['assignment_stats']['total_assigned'] > 0): ?>
                                                        <div class="task-stats mb-2">
                                                            <div class="stat-item stat-total">
                                                                <i class="bi bi-people-fill"></i>
                                                                <span><span>Assigned</span>:
                                                                    <strong><?= $task['assignment_stats']['total_assigned'] ?></strong></span>
                                                            </div>
                                                            <div class="stat-item stat-completed">
                                                                <i class="bi bi-check-circle-fill"></i>
                                                                <span><span>Completed</span>:
                                                                    <strong><?= $task['assignment_stats']['completed'] ?></strong></span>
                                                            </div>
                                                            <div class="stat-item stat-progress">
                                                                <i class="bi bi-hourglass-split"></i>
                                                                <span><span>In Progress</span>:
                                                                    <strong><?= $task['assignment_stats']['in_progress'] ?></strong></span>
                                                            </div>
                                                            <div class="stat-item stat-pending">
                                                                <i class="bi bi-clock-fill"></i>
                                                                <span><span>Not Started</span>:
                                                                    <strong><?= $task['assignment_stats']['not_started'] ?></strong></span>
                                                            </div>
                                                        </div>

                                                        <?php if (!empty($task['assignment_stats']['types'])): ?>
                                                            <div class="d-flex flex-wrap gap-2 small">
                                                                <span class="text-muted">
                                                                    <i class="bi bi-tag-fill"></i> Assignment types:
                                                                </span>
                                                                <?php foreach ($task['assignment_stats']['types'] as $typeInfo): ?>
                                                                    <?php
                                                                    $typeLabel = '';
                                                                    $typeColor = 'secondary';
                                                                    switch ($typeInfo['type']) {
                                                                        case 'user':
                                                                            $typeLabel = 'Individual';
                                                                            $typeColor = 'info';
                                                                            break;
                                                                        case 'group':
                                                                            $typeLabel = 'Group';
                                                                            $typeColor = 'warning';
                                                                            break;
                                                                    }
                                                                    ?>
                                                                    <span class="badge bg-<?= $typeColor ?>"
                                                                        title="<?= htmlspecialchars($typeInfo['names']) ?>">
                                                                        <?= $typeLabel ?>: <?= $typeInfo['count'] ?>
                                                                    </span>
                                                                <?php endforeach; ?>
                                                            </div>
                                                        <?php endif; ?>
                                                    <?php else: ?>
                                                        <div class="alert alert-warning py-1 px-2 mb-0 small">
                                                            <i class="bi bi-exclamation-triangle"></i>
                                                            <span>This task has not been assigned yet</span>
                                                        </div>
                                                    <?php endif; ?>
                                                </div>

                                                <div class="btn-group ms-3" role="group">
                                                    <a href="/class/task/view?task_id=<?= $task['task_id'] ?>&topic_id=<?= $topicId ?>&phase_id=<?= $phaseId ?>"
                                                        class="btn btn-sm btn-outline-success" title="View">
                                                        <i class="bi bi-eye"></i>
                                                    </a>


                                                    <?php if ($isPeerEvaluationPhase): ?>
                                                        <a href="/class/task/setting_evaluation?task_id=<?= $task['task_id'] ?>&topic_id=<?= $topicId ?>&phase_id=<?= $phaseId ?>"
                                                            class="btn btn-sm btn-outline-info" title="Configure peer evaluation">
                                                            <i class="bi bi-gear-fill"></i>
                                                        </a>
                                                    <?php elseif ($isGradingPhase): ?>
                                                        <a href="<?= URL_ROOT ?>/class/evaluate/teacher_grades?task_id=<?= $task['task_id'] ?>&topic_id=<?= $topicId ?>&phase_id=<?= $phaseId ?>"
                                                            class="btn btn-sm btn-outline-info" title="Grade submissions">
                                                            <i class="bi bi-award"></i>
                                                        </a>
                                                    <?php else: ?>
                                                        <a href="/class/task/assign?task_id=<?= $task['task_id'] ?>&topic_id=<?= $topicId ?>&phase_id=<?= $phaseId ?>"
                                                            class="btn btn-sm btn-outline-warning" title="Assign task">
                                                            <i class="bi bi-bell"></i>
                                                        </a>
                                                    <?php endif; ?>
                                                    <a href="/class/task/edit?task_id=<?= $task['task_id'] ?>&topic_id=<?= $topicId ?>&phase_id=<?= $phaseId ?>"
                                                        class="btn btn-sm btn-outline-primary" title="Edit">
                                                        <i class="bi bi-pencil"></i>
                                                    </a>
                                                    <button type="button" class="btn btn-sm btn-outline-danger delete-task"
                                                        data-task-id="<?= $task['task_id'] ?>"
                                                        data-task-title="<?= htmlspecialchars($task['title']) ?>" title="Delete">
                                                        <i class="bi bi-trash"></i>
                                                    </button>
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

    <!-- Toast Container -->
    <div class="position-fixed top-0 end-0 p-3" style="z-index: 1100; margin-top: 60px;">
        <div id="toastNotification" class="toast" role="alert" aria-live="assertive" aria-atomic="true">
            <div class="toast-header">
                <i class="bi me-2" id="toastIcon"></i>
                <strong class="me-auto" id="toastTitle">Notification</strong>
                <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
            <div class="toast-body" id="toastMessage">
                Notification content
            </div>
        </div>
    </div>

    <?php
    require_once 'views/templates/footer.php';
    require_once 'views/templates/vendors.php';
    ?>

    <script>
        // Initialize Bootstrap tooltips
        document.addEventListener('DOMContentLoaded', function () {
            const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
            tooltipTriggerList.map(function (tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl);
            });
        });
    </script>

    <?php if ($isTeacher): ?>
        <script>
            // Toast utility function
            function showToast(message, type = 'success') {
                const toastEl = document.getElementById('toastNotification');
                const toastIcon = document.getElementById('toastIcon');
                const toastTitle = document.getElementById('toastTitle');
                const toastMessage = document.getElementById('toastMessage');
                const toastHeader = toastEl.querySelector('.toast-header');

                // Remove existing background classes
                toastHeader.classList.remove('bg-success', 'bg-danger', 'bg-warning', 'bg-info', 'text-white');
                toastIcon.className = 'bi me-2';

                // Set style based on type
                switch (type) {
                    case 'success':
                        toastHeader.classList.add('bg-success', 'text-white');
                        toastIcon.classList.add('bi-check-circle-fill');
                        toastTitle.textContent = 'Success';
                        break;
                    case 'error':
                        toastHeader.classList.add('bg-danger', 'text-white');
                        toastIcon.classList.add('bi-x-circle-fill');
                        toastTitle.textContent = 'Error';
                        break;
                    case 'warning':
                        toastHeader.classList.add('bg-warning', 'text-dark');
                        toastIcon.classList.add('bi-exclamation-triangle-fill');
                        toastTitle.textContent = 'Warning';
                        break;
                    case 'info':
                        toastHeader.classList.add('bg-info', 'text-white');
                        toastIcon.classList.add('bi-info-circle-fill');
                        toastTitle.textContent = 'Info';
                        break;
                }

                toastMessage.textContent = message;

                const toast = new bootstrap.Toast(toastEl, {
                    autohide: true,
                    delay: 3000
                });
                toast.show();
            }

            // Delete task
            document.querySelectorAll('.delete-task').forEach(btn => {
                btn.addEventListener('click', async function () {
                    const taskId = this.dataset.taskId;
                    const taskTitle = this.dataset.taskTitle;

                    if (!confirm(`Are you sure you want to delete task "${taskTitle}"?`)) {
                        return;
                    }

                    try {
                        const formData = new FormData();
                        formData.append('action', 'delete_task');
                        formData.append('task_id', taskId);
                        formData.append('topic_id', <?= $topicId ?>);

                        const response = await fetch('/api/manage_tasks.php', {
                            method: 'POST',
                            body: formData
                        });

                        const data = await response.json();

                        if (data.success) {
                            showToast('Task deleted successfully!', 'success');
                            setTimeout(() => {
                                window.location.reload();
                            }, 1500);
                        } else {
                            showToast(data.message || 'Cannot delete task', 'error');
                        }
                    } catch (error) {
                        console.error('Error:', error);
                        showToast('An error occurred while deleting the task', 'error');
                    }
                });
            });
        </script>
    <?php endif; ?>
</body>

</html>