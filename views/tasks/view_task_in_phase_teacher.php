<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php require_once './views/templates/assets.php'; ?>
    <title>Danh sách nhiệm vụ</title>
    <style>
        .list-group-item {
            transition: all 0.3s ease;
            border-left: 4px solid transparent;
        }

        .list-group-item:hover {
            border-left-color: #0d6efd;
            box-shadow: 0 0 15px rgba(13, 110, 253, 0.1);
            background-color: #f8f9fa;
        }

        .badge {
            font-size: 0.85rem;
            padding: 0.4rem 0.7rem;
            font-weight: 500;
        }

        .task-stats {
            display: flex;
            gap: 1rem;
            flex-wrap: wrap;
            margin-top: 0.5rem;
        }

        .stat-item {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.25rem 0.75rem;
            background: #f8f9fa;
            border-radius: 0.25rem;
            font-size: 0.85rem;
        }
    </style>
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
            <h1>Danh sách nhiệm vụ</h1>
            <nav>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="/class"><?= $_SESSION['current_class_name']; ?></a></li>
                    <li class="breadcrumb-item"><a href="/class"><?= htmlspecialchars($topic['topic_name']) ?></a></li>
                    <li class="breadcrumb-item"><a href="<?= URL_ROOT ?>/class/phases?topic_id=<?= $topicId ?>">Pha học
                            tập</a></li>
                    <li class="breadcrumb-item active">Nhiệm vụ - Bước <?= $phase['phase_number'] ?></li>
                </ol>
            </nav>
        </div><!-- End Page Title -->

        <section class="section">
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
                                        <i class="bi bi-arrow-left"></i> Quay lại
                                    </a>
                                    <a href="<?= URL_ROOT ?>/class/task/create?topic_id=<?= $topicId ?>&phase_id=<?= $phaseId ?>"
                                        class="btn btn-primary">
                                        <i class="bi bi-plus-circle"></i> Tạo nhiệm vụ
                                    </a>
                                </div>
                            </div>

                            <?php if (empty($tasks)): ?>
                                <div class="alert alert-info">
                                    <i class="bi bi-info-circle"></i>
                                    Chưa có nhiệm vụ nào trong pha này.
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
                                                            Tạo bởi:
                                                            <strong><?= htmlspecialchars($task['added_by_name'] ?? 'N/A') ?></strong>
                                                        </span>
                                                        <span>
                                                            <i class="bi bi-calendar"></i>
                                                            <?= date('d/m/Y H:i', strtotime($task['created_at'])) ?>
                                                        </span>
                                                        <?php if ($task['file_content']): ?>
                                                            <span class="badge bg-success">
                                                                <i class="bi bi-file-earmark-text"></i> Có nội dung
                                                            </span>
                                                        <?php endif; ?>
                                                    </div>

                                                    <?php if (isset($task['assignment_stats']) && $task['assignment_stats']['total_assigned'] > 0): ?>
                                                        <div class="task-stats mb-2">
                                                            <div class="stat-item stat-total">
                                                                <i class="bi bi-people-fill"></i>
                                                                <span>Đã giao:
                                                                    <strong><?= $task['assignment_stats']['total_assigned'] ?></strong></span>
                                                            </div>
                                                            <div class="stat-item stat-completed">
                                                                <i class="bi bi-check-circle-fill"></i>
                                                                <span>Hoàn thành:
                                                                    <strong><?= $task['assignment_stats']['completed'] ?></strong></span>
                                                            </div>
                                                            <div class="stat-item stat-progress">
                                                                <i class="bi bi-hourglass-split"></i>
                                                                <span>Đang làm:
                                                                    <strong><?= $task['assignment_stats']['in_progress'] ?></strong></span>
                                                            </div>
                                                            <div class="stat-item stat-pending">
                                                                <i class="bi bi-clock-fill"></i>
                                                                <span>Chưa làm:
                                                                    <strong><?= $task['assignment_stats']['not_started'] ?></strong></span>
                                                            </div>
                                                        </div>

                                                        <?php if (!empty($task['assignment_stats']['types'])): ?>
                                                            <div class="d-flex flex-wrap gap-2 small">
                                                                <span class="text-muted">
                                                                    <i class="bi bi-tag-fill"></i> Phân loại đã giao:
                                                                </span>
                                                                <?php foreach ($task['assignment_stats']['types'] as $typeInfo): ?>
                                                                    <?php
                                                                    $typeLabel = '';
                                                                    $typeColor = 'secondary';
                                                                    switch ($typeInfo['type']) {
                                                                        case 'user':
                                                                            $typeLabel = 'Cá nhân';
                                                                            $typeColor = 'info';
                                                                            break;
                                                                        case 'group':
                                                                            $typeLabel = 'Nhóm';
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
                                                            Chưa giao nhiệm vụ này
                                                        </div>
                                                    <?php endif; ?>
                                                </div>

                                                <div class="btn-group ms-3" role="group">
                                                    <a href="/class/task/view?task_id=<?= $task['task_id'] ?>&topic_id=<?= $topicId ?>&phase_id=<?= $phaseId ?>"
                                                        class="btn btn-sm btn-outline-success" title="Xem">
                                                        <i class="bi bi-eye"></i>
                                                    </a>


                                                    <?php if ($isPeerEvaluationPhase): ?>
                                                        <a href="/class/task/setting_evaluation?task_id=<?= $task['task_id'] ?>&topic_id=<?= $topicId ?>&phase_id=<?= $phaseId ?>"
                                                            class="btn btn-sm btn-outline-info" title="Cấu hình đánh giá chéo">
                                                            <i class="bi bi-gear-fill"></i>
                                                        </a>
                                                    <?php else: ?>
                                                        <a href="/class/task/assign?task_id=<?= $task['task_id'] ?>&topic_id=<?= $topicId ?>&phase_id=<?= $phaseId ?>"
                                                            class="btn btn-sm btn-outline-warning" title="Giao nhiệm vụ">
                                                            <i class="bi bi-bell"></i>
                                                        </a>
                                                    <?php endif; ?>
                                                    <a href="/class/task/edit?task_id=<?= $task['task_id'] ?>&topic_id=<?= $topicId ?>&phase_id=<?= $phaseId ?>"
                                                        class="btn btn-sm btn-outline-primary" title="Chỉnh sửa">
                                                        <i class="bi bi-pencil"></i>
                                                    </a>
                                                    <button type="button" class="btn btn-sm btn-outline-danger delete-task"
                                                        data-task-id="<?= $task['task_id'] ?>"
                                                        data-task-title="<?= htmlspecialchars($task['title']) ?>" title="Xóa">
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
                <strong class="me-auto" id="toastTitle">Thông báo</strong>
                <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
            <div class="toast-body" id="toastMessage">
                Nội dung thông báo
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
                        toastTitle.textContent = 'Thành công';
                        break;
                    case 'error':
                        toastHeader.classList.add('bg-danger', 'text-white');
                        toastIcon.classList.add('bi-x-circle-fill');
                        toastTitle.textContent = 'Lỗi';
                        break;
                    case 'warning':
                        toastHeader.classList.add('bg-warning', 'text-dark');
                        toastIcon.classList.add('bi-exclamation-triangle-fill');
                        toastTitle.textContent = 'Cảnh báo';
                        break;
                    case 'info':
                        toastHeader.classList.add('bg-info', 'text-white');
                        toastIcon.classList.add('bi-info-circle-fill');
                        toastTitle.textContent = 'Thông tin';
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

                    if (!confirm(`Bạn có chắc chắn muốn xóa nhiệm vụ "${taskTitle}"?`)) {
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
                            showToast('Xóa nhiệm vụ thành công!', 'success');
                            setTimeout(() => {
                                window.location.reload();
                            }, 1500);
                        } else {
                            showToast(data.message || 'Không thể xóa nhiệm vụ', 'error');
                        }
                    } catch (error) {
                        console.error('Error:', error);
                        showToast('Có lỗi xảy ra khi xóa nhiệm vụ', 'error');
                    }
                });
            });
        </script>
    <?php endif; ?>
</body>

</html>