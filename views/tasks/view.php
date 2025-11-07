<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php require_once './views/templates/assets.php'; ?>
    <title>Chi tiết nhiệm vụ</title>
    <style>
        .assignment-card {
            transition: all 0.3s ease;
            border-left: 4px solid transparent;
        }

        .assignment-card:hover {
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
            transform: translateY(-2px);
        }

        .assignment-card.status-completed {
            border-left-color: #198754;
        }

        .assignment-card.status-in-progress {
            border-left-color: #ffc107;
        }

        .assignment-card.status-not-started {
            border-left-color: #6c757d;
        }

        .stat-card {
            border-radius: 10px;
            padding: 20px;
            text-align: center;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }

        .stat-card.success {
            background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
        }

        .stat-card.warning {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
        }

        .stat-card.info {
            background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
        }

        .stat-number {
            font-size: 2.5rem;
            font-weight: bold;
            margin: 10px 0;
        }

        .progress-bar-animated {
            animation: progress-bar-stripes 1s linear infinite;
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

    if (!isset($_SESSION['user']['role']) || $_SESSION['user']['role'] !== 'teacher') {
        header('Location: /class');
        exit;
    }

    require_once './views/templates/sidebar_teacher.php';

    // Get IDs from URL
    $taskId = isset($_GET['task_id']) ? intval($_GET['task_id']) : 0;
    $phaseId = isset($_GET['phase_id']) ? intval($_GET['phase_id']) : 0;
    $topicId = isset($_GET['topic_id']) ? intval($_GET['topic_id']) : 0;

    if (!$taskId || !$phaseId || !$topicId) {
        header('Location: /class');
        exit;
    }

    // Get task details
    $task = getTaskDetails($taskId);
    if (!$task) {
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

    // Check if user is teacher
    $isTeacher = isTeacherInTopicClass($topicId, $_SESSION['user']['user_id']);
    if (!$isTeacher) {
        header('Location: /class');
        exit;
    }

    // Determine if this phase should view data from a previous phase
    // Only Phase 2 (CHỮA BÀI - phase_code: 'review' or 'CHUABAI') should look back to Phase 1
    // Phase 3 (LÀM BÀI TẬP VỀ NHÀ - phase_code: 'homework' or 'BTVN') is independent
    $shouldLookBack = false;

    // Get current phase code first
    $phaseCheckStmt = $pdo->prepare("
        SELECT mp.phase_code
        FROM phase p
        INNER JOIN main_phase mp ON p.m_phase_id = mp.m_phase_id
        WHERE p.phase_id = :phase_id
    ");
    $phaseCheckStmt->execute([':phase_id' => $phaseId]);
    $currentPhaseCode = $phaseCheckStmt->fetchColumn();

    // Check if current task has review_task (points to previous phase's task)
    $reviewTaskStmt = $pdo->prepare("
        SELECT review_task 
        FROM task 
        WHERE task_id = :task_id
    ");
    $reviewTaskStmt->execute([':task_id' => $taskId]);
    $reviewTask = $reviewTaskStmt->fetchColumn();

    $dataTaskId = $taskId; // Default: use current task

    // If this task reviews another task, use that task's data
    if ($reviewTask && $reviewTask > 0) {
        $shouldLookBack = true;
        $dataTaskId = $reviewTask;
    } else {
        // Only CHỮA BÀI (review phase) should look back to previous phase
        if ($currentPhaseCode === 'CHUABAI' || $currentPhaseCode === 'review') {
            $shouldLookBack = true;

            // Find task from previous phase
            $sourceTaskStmt = $pdo->prepare("
                SELECT t.task_id, p.phase_number
                FROM task t
                INNER JOIN phase p ON t.phase_id = p.phase_id
                WHERE p.topic_id = (SELECT topic_id FROM phase WHERE phase_id = :current_phase_id)
                AND p.phase_number < (SELECT phase_number FROM phase WHERE phase_id = :current_phase_id)
                ORDER BY p.phase_number DESC
                LIMIT 1
            ");
            $sourceTaskStmt->execute([':current_phase_id' => $phaseId]);
            $sourceTask = $sourceTaskStmt->fetch(PDO::FETCH_ASSOC);

            if ($sourceTask) {
                $dataTaskId = $sourceTask['task_id'];
            }
        }
    }

    // Check if this is peer evaluation phase
    $isPeerEvaluationPhase = ($currentPhaseCode === 'PHASE_DANHGIANGUOIHOC');

    // Get peer evaluation statistics if applicable
    $peerEvalStats = null;
    if ($isPeerEvaluationPhase) {
        // Thống kê đánh giá chéo
        $peerEvalStatsStmt = $pdo->prepare("
            SELECT 
                COUNT(DISTINCT d.id_deliver) as total_evaluations_assigned,
                COUNT(DISTINCT CASE WHEN e.status = 2 THEN d.id_deliver END) as completed_evaluations,
                COUNT(DISTINCT CASE WHEN e.status = 1 THEN d.id_deliver END) as in_progress_evaluations,
                COUNT(DISTINCT CASE WHEN e.status = 0 OR e.id_evaluate IS NULL THEN d.id_deliver END) as not_started_evaluations,
                AVG(CASE WHEN e.status = 2 THEN e.total_score END) as average_score,
                COUNT(DISTINCT de.response_review) as total_responses_being_reviewed
            FROM deliver d
            LEFT JOIN deliver_evaluate de ON d.id_deliver = de.id_deliver
            LEFT JOIN evaluate e ON d.id_deliver = e.deliver_id
            WHERE d.task_id = :task_id
        ");
        $peerEvalStatsStmt->execute([':task_id' => $taskId]);
        $peerEvalStats = $peerEvalStatsStmt->fetch(PDO::FETCH_ASSOC);
    }

    // Get task assignments with detailed info
    $assignments = getTaskAssignmentsDetailed($dataTaskId);

    // Calculate statistics - need to count actual students, not just assignment records
    // Use dataTaskId for both assignments and submission data
    $studentStats = getTaskStudentStatistics($dataTaskId, $topic['class_id'], $dataTaskId);

    $totalAssignments = $studentStats['total'];
    $completedCount = $studentStats['completed'];
    $inProgressCount = $studentStats['in_progress'];
    $notStartedCount = $studentStats['not_started'];

    $completionRate = $totalAssignments > 0 ? round(($completedCount / $totalAssignments) * 100, 1) : 0;
    ?>

    <main id="main" class="main">
        <div class="pagetitle">
            <h1>Chi tiết nhiệm vụ đã giao</h1>
            <nav>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="/class"><?= $_SESSION['current_class_name']; ?></a></li>
                    <li class="breadcrumb-item"><a href="/class"><?= htmlspecialchars($topic['topic_name']) ?></a></li>
                    <li class="breadcrumb-item"><a href="/class/phases?topic_id=<?= $topicId ?>">Pha học tập</a></li>
                    <li class="breadcrumb-item"><a
                            href="/class/task?topic_id=<?= $topicId ?>&phase_id=<?= $phaseId ?>">Nhiệm vụ</a></li>
                    <li class="breadcrumb-item active">Chi tiết</li>
                </ol>
            </nav>
        </div><!-- End Page Title -->

        <section class="section">
            <!-- Task Information Card -->
            <div class="row mb-4">
                <div class="col-12">
                    <div class="card">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start">
                                <div class="flex-grow-1">
                                    <h5 class="card-title mb-1">
                                        <i class="bi bi-clipboard-check text-primary"></i>
                                        <?= htmlspecialchars($task['title']) ?>
                                    </h5>
                                    <p class="text-muted mb-3">
                                        <i class="bi bi-layers"></i> Pha: Bước <?= $phase['phase_number'] ?> -
                                        <?= htmlspecialchars($phase['phase_name']) ?>
                                        <?php if ($shouldLookBack && $dataTaskId != $taskId): ?>
                                            <br>
                                            <small class="badge bg-info">
                                                <i class="bi bi-eye"></i> Đang xem dữ liệu từ pha trước
                                            </small>
                                        <?php endif; ?>
                                    </p>

                                    <?php if (!empty($task['description'])): ?>
                                        <div class="mb-3">
                                            <h6><i class="bi bi-file-text"></i> Mô tả:</h6>
                                            <p><?= nl2br(htmlspecialchars($task['description'])) ?></p>
                                        </div>
                                    <?php endif; ?>

                                    <div class="d-flex flex-wrap gap-3 small text-muted">
                                        <span>
                                            <i class="bi bi-person"></i>
                                            Tạo bởi: <strong><?= htmlspecialchars($task['added_by_name'] ?? 'N/A') ?></strong>
                                        </span>
                                        <span>
                                            <i class="bi bi-calendar"></i>
                                            Ngày tạo: <?= date('d/m/Y H:i', strtotime($task['created_at'])) ?>
                                        </span>
                                        <?php if ($task['content_creator']): ?>
                                            <span>
                                                <i class="bi bi-pencil"></i>
                                                Nội dung: <strong><?= htmlspecialchars($task['content_creator_name'] ?? 'N/A') ?></strong>
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <div class="btn-group" role="group">
                                    <?php
                                    // Get count of submissions with comments (for badge)
                                    $commentCountStmt = $pdo->prepare("
                                        SELECT COUNT(DISTINCT r.id_response) as count_with_comments
                                        FROM response r
                                        INNER JOIN users u ON r.user_id = u.user_id
                                        LEFT JOIN content ct ON r.id_response = ct.response_id
                                        LEFT JOIN comment c ON c.task_id = r.task_id AND c.content_id = ct.content_id
                                        WHERE r.task_id = :task_id 
                                        AND r.parent_response IS NULL
                                        AND u.role = 'student'
                                        AND c.comment_id IS NOT NULL
                                    ");
                                    $commentCountStmt->execute([':task_id' => $dataTaskId]);
                                    $commentCount = $commentCountStmt->fetchColumn();
                                    ?>
                                    <a href="/class/task/assign?task_id=<?= $taskId ?>&topic_id=<?= $topicId ?>&phase_id=<?= $phaseId ?>"
                                        class="btn btn-warning" title="Giao thêm">
                                        <i class="bi bi-bell"></i> Giao thêm
                                    </a>
                                    <a href="/class/task?topic_id=<?= $topicId ?>&phase_id=<?= $phaseId ?>"
                                        class="btn btn-secondary">
                                        <i class="bi bi-arrow-left"></i> Quay lại
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Statistics Row -->
            <div class="row mb-4">
                <div class="col-md-3 col-sm-6 mb-3">
                    <div class="stat-card info">
                        <i class="bi bi-people-fill" style="font-size: 2rem;"></i>
                        <div class="stat-number"><?= $totalAssignments ?></div>
                        <div>Tổng số đã giao</div>
                    </div>
                </div>
                <div class="col-md-3 col-sm-6 mb-3">
                    <div class="stat-card success">
                        <i class="bi bi-check-circle-fill" style="font-size: 2rem;"></i>
                        <div class="stat-number"><?= $completedCount ?></div>
                        <div>Đã hoàn thành</div>
                    </div>
                </div>
                <div class="col-md-3 col-sm-6 mb-3">
                    <div class="stat-card warning">
                        <i class="bi bi-clock-history" style="font-size: 2rem;"></i>
                        <div class="stat-number"><?= $inProgressCount ?></div>
                        <div>Đang thực hiện</div>
                    </div>
                </div>
                <div class="col-md-3 col-sm-6 mb-3">
                    <div class="stat-card">
                        <i class="bi bi-dash-circle-fill" style="font-size: 2rem;"></i>
                        <div class="stat-number"><?= $notStartedCount ?></div>
                        <div>Chưa bắt đầu</div>
                    </div>
                </div>
            </div>

            <?php if ($isPeerEvaluationPhase && $peerEvalStats): ?>
                <!-- Peer Evaluation Statistics -->
                <div class="row mb-4">
                    <div class="col-12">
                        <div class="card border-primary">
                            <div class="card-header bg-primary text-white">
                                <h5 class="mb-0">
                                    <i class="bi bi-star-fill"></i> Thống kê đánh giá chéo
                                </h5>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-3 col-sm-6 mb-3">
                                        <div class="stat-card info">
                                            <i class="bi bi-people-fill" style="font-size: 2rem;"></i>
                                            <div class="stat-number"><?= $peerEvalStats['total_evaluations_assigned'] ?></div>
                                            <div>Tổng số đánh giá</div>
                                        </div>
                                    </div>
                                    <div class="col-md-3 col-sm-6 mb-3">
                                        <div class="stat-card success">
                                            <i class="bi bi-check-circle-fill" style="font-size: 2rem;"></i>
                                            <div class="stat-number"><?= $peerEvalStats['completed_evaluations'] ?></div>
                                            <div>Đã hoàn thành</div>
                                        </div>
                                    </div>
                                    <div class="col-md-3 col-sm-6 mb-3">
                                        <div class="stat-card warning">
                                            <i class="bi bi-clock-history" style="font-size: 2rem;"></i>
                                            <div class="stat-number"><?= $peerEvalStats['in_progress_evaluations'] ?></div>
                                            <div>Đang đánh giá</div>
                                        </div>
                                    </div>
                                    <div class="col-md-3 col-sm-6 mb-3">
                                        <div class="stat-card">
                                            <i class="bi bi-dash-circle-fill" style="font-size: 2rem;"></i>
                                            <div class="stat-number"><?= $peerEvalStats['not_started_evaluations'] ?></div>
                                            <div>Chưa bắt đầu</div>
                                        </div>
                                    </div>
                                </div>

                                <div class="row mt-3">
                                    <div class="col-md-6">
                                        <div class="card bg-light">
                                            <div class="card-body text-center">
                                                <h6 class="text-muted mb-2">Số bài được đánh giá</h6>
                                                <div class="display-6 text-primary">
                                                    <i class="bi bi-file-earmark-text-fill"></i>
                                                    <?= $peerEvalStats['total_responses_being_reviewed'] ?>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="card bg-light">
                                            <div class="card-body text-center">
                                                <h6 class="text-muted mb-2">Điểm trung bình</h6>
                                                <div class="display-6 text-success">
                                                    <i class="bi bi-graph-up-arrow"></i>
                                                    <?= $peerEvalStats['average_score'] ? number_format($peerEvalStats['average_score'], 1) : 'N/A' ?>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Progress bar for peer evaluation -->
                                <?php
                                $peerCompletionRate = $peerEvalStats['total_evaluations_assigned'] > 0
                                    ? round(($peerEvalStats['completed_evaluations'] / $peerEvalStats['total_evaluations_assigned']) * 100, 1)
                                    : 0;
                                ?>
                                <div class="mt-4">
                                    <h6 class="mb-2">Tiến độ hoàn thành đánh giá chéo</h6>
                                    <div class="d-flex align-items-center">
                                        <div class="flex-grow-1">
                                            <div class="progress" style="height: 30px;">
                                                <div class="progress-bar bg-primary progress-bar-striped progress-bar-animated"
                                                    role="progressbar" style="width: <?= $peerCompletionRate ?>%"
                                                    aria-valuenow="<?= $peerCompletionRate ?>" aria-valuemin="0" aria-valuemax="100">
                                                    <?= $peerCompletionRate ?>%
                                                </div>
                                            </div>
                                        </div>
                                        <div class="ms-3">
                                            <h4 class="mb-0 text-primary"><?= $peerCompletionRate ?>%</h4>
                                        </div>
                                    </div>
                                    <div class="d-flex justify-content-between text-muted small mt-2">
                                        <span><i class="bi bi-check-circle text-success"></i> <?= $peerEvalStats['completed_evaluations'] ?> hoàn thành</span>
                                        <span><i class="bi bi-clock text-warning"></i> <?= $peerEvalStats['in_progress_evaluations'] ?> đang làm</span>
                                        <span><i class="bi bi-dash-circle text-secondary"></i> <?= $peerEvalStats['not_started_evaluations'] ?> chưa làm</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Completion Progress -->
            <div class="row mb-4">
                <div class="col-12">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title">Tiến độ hoàn thành</h5>
                            <div class="d-flex align-items-center mb-3">
                                <div class="flex-grow-1">
                                    <div class="progress" style="height: 30px;">
                                        <div class="progress-bar bg-success progress-bar-striped progress-bar-animated"
                                            role="progressbar" style="width: <?= $completionRate ?>%"
                                            aria-valuenow="<?= $completionRate ?>" aria-valuemin="0" aria-valuemax="100">
                                            <?= $completionRate ?>%
                                        </div>
                                    </div>
                                </div>
                                <div class="ms-3">
                                    <h4 class="mb-0 text-success"><?= $completionRate ?>%</h4>
                                </div>
                            </div>
                            <div class="d-flex justify-content-between text-muted small">
                                <span><i class="bi bi-check-circle text-success"></i> <?= $completedCount ?> hoàn
                                    thành</span>
                                <span><i class="bi bi-clock text-warning"></i> <?= $inProgressCount ?> đang làm</span>
                                <span><i class="bi bi-dash-circle text-secondary"></i> <?= $notStartedCount ?> chưa
                                    làm</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Assignments List -->
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title">Danh sách đã giao (<?= $totalAssignments ?>)</h5>

                            <?php if (empty($assignments)): ?>
                                <div class="alert alert-info">
                                    <i class="bi bi-info-circle"></i>
                                    Chưa giao nhiệm vụ này cho ai.
                                    <a href="/class/task/assign?task_id=<?= $taskId ?>&topic_id=<?= $topicId ?>&phase_id=<?= $phaseId ?>"
                                        class="alert-link">Giao nhiệm vụ ngay</a>
                                </div>
                            <?php else: ?>
                                <!-- Filter Tabs -->
                                <ul class="nav nav-tabs mb-3" id="assignmentTabs" role="tablist">
                                    <li class="nav-item" role="presentation">
                                        <button class="nav-link active" id="all-tab" data-bs-toggle="tab"
                                            data-bs-target="#all" type="button" role="tab">
                                            <i class="bi bi-list"></i> Tất cả (<?= $totalAssignments ?>)
                                        </button>
                                    </li>
                                    <li class="nav-item" role="presentation">
                                        <button class="nav-link" id="completed-tab" data-bs-toggle="tab"
                                            data-bs-target="#completed" type="button" role="tab">
                                            <i class="bi bi-check-circle text-success"></i> Hoàn thành
                                            (<?= $completedCount ?>)
                                        </button>
                                    </li>
                                    <li class="nav-item" role="presentation">
                                        <button class="nav-link" id="progress-tab" data-bs-toggle="tab"
                                            data-bs-target="#progress" type="button" role="tab">
                                            <i class="bi bi-clock text-warning"></i> Đang làm (<?= $inProgressCount ?>)
                                        </button>
                                    </li>
                                    <li class="nav-item" role="presentation">
                                        <button class="nav-link" id="notstarted-tab" data-bs-toggle="tab"
                                            data-bs-target="#notstarted" type="button" role="tab">
                                            <i class="bi bi-dash-circle text-secondary"></i> Chưa bắt đầu
                                            (<?= $notStartedCount ?>)
                                        </button>
                                    </li>
                                </ul>

                                <!-- Tab Content -->
                                <div class="tab-content" id="assignmentTabsContent">
                                    <!-- All Assignments -->
                                    <div class="tab-pane fade show active" id="all" role="tabpanel">
                                        <?php renderAssignmentsList($assignments, $dataTaskId); ?>
                                    </div>

                                    <!-- Completed -->
                                    <div class="tab-pane fade" id="completed" role="tabpanel">
                                        <?php renderStudentsByStatus($dataTaskId, $assignments, 'completed'); ?>
                                    </div>

                                    <!-- In Progress -->
                                    <div class="tab-pane fade" id="progress" role="tabpanel">
                                        <?php renderStudentsByStatus($dataTaskId, $assignments, 'in_progress'); ?>
                                    </div>

                                    <!-- Not Started -->
                                    <div class="tab-pane fade" id="notstarted" role="tabpanel">
                                        <?php renderStudentsByStatus($dataTaskId, $assignments, 'not_started'); ?>
                                    </div>
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
    require_once './views/templates/footer.php';
    require_once './views/templates/vendors.php';
    ?>

    <script>
        // Toast utility function
        function showToast(message, type = 'success') {
            const toastEl = document.getElementById('toastNotification');
            const toastIcon = document.getElementById('toastIcon');
            const toastTitle = document.getElementById('toastTitle');
            const toastMessage = document.getElementById('toastMessage');
            const toastHeader = toastEl.querySelector('.toast-header');

            toastHeader.classList.remove('bg-success', 'bg-danger', 'bg-warning', 'bg-info', 'text-white');
            toastIcon.className = 'bi me-2';

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
            }

            toastMessage.textContent = message;

            const toast = new bootstrap.Toast(toastEl, {
                autohide: true,
                delay: 3000
            });
            toast.show();
        }

        // Remove assignment
        document.querySelectorAll('.remove-assignment').forEach(btn => {
            btn.addEventListener('click', async function() {
                const deliverId = this.dataset.deliverId;
                const assignedTo = this.dataset.assignedTo;

                if (!confirm(`Bạn có chắc chắn muốn hủy giao nhiệm vụ cho "${assignedTo}"?`)) {
                    return;
                }

                try {
                    const formData = new FormData();
                    formData.append('action', 'remove_assignment');
                    formData.append('deliver_id', deliverId);
                    formData.append('topic_id', <?= $topicId ?>);

                    const response = await fetch('/api/manage_tasks.php', {
                        method: 'POST',
                        body: formData
                    });

                    const data = await response.json();

                    if (data.success) {
                        showToast('Hủy giao nhiệm vụ thành công!', 'success');
                        setTimeout(() => {
                            window.location.reload();
                        }, 1500);
                    } else {
                        showToast(data.message || 'Không thể hủy giao nhiệm vụ', 'error');
                    }
                } catch (error) {
                    console.error('Error:', error);
                    showToast('Có lỗi xảy ra khi hủy giao nhiệm vụ', 'error');
                }
            });
        });
    </script>
</body>

</html>

<?php
/**
 * Helper function to render assignments list
 */
function renderAssignmentsList($assignments, $dataTaskId = null)
{
    global $taskId, $topicId, $phaseId;

    // If dataTaskId not provided, use taskId (backward compatibility)
    if ($dataTaskId === null) {
        $dataTaskId = $taskId;
    }

    if (empty($assignments)): ?>
        <div class="alert alert-info">
            <i class="bi bi-info-circle"></i> Không có kết quả
        </div>
    <?php return;
    endif;
    ?>

    <div class="list-group">
        <?php foreach ($assignments as $assignment): ?>
            <?php
            // Determine assignment type
            $typeIcon = 'person-fill';
            $typeText = 'Cá nhân';
            $typeBadge = 'info';

            if ($assignment['assign_type'] == 1) {
                $typeIcon = 'people-fill';
                $typeText = 'Cả lớp';
                $typeBadge = 'primary';
            } elseif ($assignment['assign_type'] == 2) {
                $typeIcon = 'diagram-3-fill';
                $typeText = 'Nhóm';
                $typeBadge = 'success';
            }

            // Get students for this assignment - use dataTaskId to get correct submission data
            $students = getStudentsForAssignment($dataTaskId, $assignment);

            // For group assignment, check if there's a group submission
            $groupSubmission = null;
            $isGroupAssignment = ($assignment['type'] == 'group');

            if ($isGroupAssignment) {
                $groupSubmission = getGroupSubmission($dataTaskId, $assignment['id_deliver']);

                // Debug: Check if any student in group has submitted
                if (!$groupSubmission && !empty($students)) {
                    // Try to find any response from group members
                    foreach ($students as $student) {
                        if ($student['submission_id']) {
                            // Found a submission from a group member, use it
                            $groupSubmission = [
                                'id_response' => $student['submission_id'],
                                'submitted_at' => $student['submitted_at'],
                                'user_id' => $student['user_id']
                            ];
                            break;
                        }
                    }
                }
            }
            ?>

            <div class="list-group-item">
                <!-- DEBUG INFO -->
                <?php if (isset($_GET['debug']) && $_GET['debug'] == '1'): ?>
                    <div class="alert alert-warning alert-sm mb-2">
                        <small>
                            <strong>DEBUG:</strong>
                            Assignment ID: <?= $assignment['id_deliver'] ?> |
                            Type: <?= $assignment['type'] ?> |
                            Value: <?= $assignment['value'] ?> |
                            Task ID: <?= $assignment['task_id'] ?> |
                            DataTaskId: <?= $dataTaskId ?> |
                            Is Group: <?= $isGroupAssignment ? 'Yes' : 'No' ?> |
                            Has Group Submission: <?= $groupSubmission ? 'Yes (ID: ' . $groupSubmission['id_response'] . ')' : 'No' ?> |
                            Students Count: <?= count($students) ?>
                            <?php if (!empty($students)): ?>
                                <br><strong>Students Debug:</strong>
                                <?php foreach ($students as $student): ?>
                                    <br>→ <?= $student['fullname'] ?>
                                    | User: <?= $student['user_id'] ?>
                                    | Deliver: <?= $student['deliver_id'] ?? 'NULL' ?>
                                    | Response: <?= $student['submission_id'] ?? 'NULL' ?>
                                    | Time: <?= $student['submitted_at'] ?? 'NULL' ?>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </small>
                    </div>
                <?php endif; ?>
                <!-- END DEBUG -->

                <div class="d-flex justify-content-between align-items-start mb-3">
                    <div class="flex-grow-1">
                        <h6 class="mb-1">
                            <i class="bi bi-<?= $typeIcon ?> text-<?= $typeBadge ?>"></i>
                            <?= htmlspecialchars($assignment['assigned_to_name']) ?>
                        </h6>
                        <div class="d-flex gap-2 align-items-center flex-wrap">
                            <span class="badge bg-<?= $typeBadge ?>"><?= $typeText ?></span>
                            <small class="text-muted">
                                <i class="bi bi-calendar"></i> <?= date('d/m/Y H:i', strtotime($assignment['created_at'])) ?>
                            </small>
                            <?php if (isset($assignment['students_total']) && $assignment['students_total'] > 0): ?>
                                <?php if ($isGroupAssignment): ?>
                                    <small>
                                        <i class="bi bi-people"></i>
                                        <?= $assignment['students_total'] ?> thành viên
                                        <?php if ($groupSubmission): ?>
                                            - <span class="text-success fw-bold"><i class="bi bi-check-circle-fill"></i> Đã nộp</span>
                                        <?php else: ?>
                                            - <span class="text-muted"><i class="bi bi-dash-circle"></i> Chưa nộp</span>
                                        <?php endif; ?>
                                    </small>
                                <?php else: ?>
                                    <small>
                                        <i class="bi bi-people"></i>
                                        <span class="text-success fw-bold"><?= $assignment['students_completed'] ?></span>
                                        /<?= $assignment['students_total'] ?> học sinh đã nộp
                                    </small>
                                <?php endif; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                    <button type="button" class="btn btn-sm btn-outline-danger remove-assignment"
                        data-deliver-id="<?= $assignment['id_deliver'] ?>"
                        data-assigned-to="<?= htmlspecialchars($assignment['assigned_to_name']) ?>"
                        title="Hủy giao">
                        <i class="bi bi-trash"></i> Hủy giao
                    </button>
                </div>

                <?php if ($isGroupAssignment): ?>
                    <!-- GROUP ASSIGNMENT: Show single comment button for the whole group -->

                    <!-- Show list of group members as table -->
                    <?php if (!empty($students)): ?>
                        <div class="table-responsive">
                            <table class="table table-sm mb-0">
                                <thead>
                                    <tr>
                                        <th>Thành viên</th>
                                        <th>Trạng thái</th>
                                        <th>Thời gian nộp</th>
                                        <th>Thao tác</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $rowCount = 0;
                                    // For group: if group has submitted, all members show "Đã nộp"
                                    $groupStatus = $groupSubmission ? 'submitted' : 'not_submitted';
                                    $groupSubmittedTime = $groupSubmission ? $groupSubmission['submitted_at'] : null;

                                    foreach ($students as $student):
                                        $rowCount++;

                                        // Use group status for all members
                                        if ($groupStatus === 'submitted') {
                                            $statusBadge = 'success';
                                            $statusIcon = 'check-circle-fill';
                                            $statusText = 'Đã nộp';
                                        } else {
                                            $statusBadge = 'secondary';
                                            $statusIcon = 'dash-circle';
                                            $statusText = 'Chưa nộp';
                                        }
                                    ?>
                                        <tr style="border-bottom: 1px solid #dee2e6;">
                                            <td>
                                                <i class="bi bi-person"></i>
                                                <?= htmlspecialchars($student['fullname']) ?>
                                            </td>
                                            <td>
                                                <span class="badge bg-<?= $statusBadge ?>">
                                                    <i class="bi bi-<?= $statusIcon ?>"></i> <?= $statusText ?>
                                                </span>
                                            </td>
                                            <td>
                                                <?php if ($groupSubmittedTime): ?>
                                                    <small><?= date('d/m/Y H:i', strtotime($groupSubmittedTime)) ?></small>
                                                <?php else: ?>
                                                    <small class="text-muted">-</small>
                                                <?php endif; ?>
                                            </td>
                                            <?php if ($rowCount === 1): ?>
                                                <!-- Show comment button only on first row, spanning all rows -->
                                                <td rowspan="<?= count($students) ?>" class="align-middle text-center" style="vertical-align: middle; background-color: #f8f9fa;">
                                                    <?php if ($groupSubmission): ?>
                                                        <a href="/class/homework/comment?task_id=<?= $dataTaskId ?>&deliver_id=<?= $assignment['id_deliver'] ?>&topic_id=<?= $topicId ?>&phase_id=<?= $phaseId ?>"
                                                            class="btn btn-success btn-sm" title="Nhận xét & Đánh giá chung cho cả nhóm">
                                                            <i class="bi bi-chat-left-text"></i> Nhận xét chung
                                                        </a>
                                                    <?php else: ?>
                                                        <small class="text-muted">Chưa có bài nộp</small>
                                                    <?php endif; ?>
                                                </td>
                                            <?php endif; ?>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>

                <?php elseif (!empty($students)): ?>
                    <!-- For individual or class assignment, show each student -->
                    <div class="table-responsive">
                        <table class="table table-sm table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>Học sinh</th>
                                    <th>Trạng thái</th>
                                    <th>Thời gian nộp</th>
                                    <th>Thao tác</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($students as $student): ?>
                                    <?php
                                    $statusBadge = 'secondary';
                                    $statusIcon = 'dash-circle';
                                    $statusText = 'Chưa nộp';

                                    if ($student['submitted_at']) {
                                        $statusBadge = 'success';
                                        $statusIcon = 'check-circle-fill';
                                        $statusText = 'Đã nộp';
                                    }
                                    ?>
                                    <tr>
                                        <td>
                                            <i class="bi bi-person"></i>
                                            <?= htmlspecialchars($student['fullname']) ?>
                                        </td>
                                        <td>
                                            <span class="badge bg-<?= $statusBadge ?>">
                                                <i class="bi bi-<?= $statusIcon ?>"></i> <?= $statusText ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php if ($student['submitted_at']): ?>
                                                <small><?= date('d/m/Y H:i', strtotime($student['submitted_at'])) ?></small>
                                            <?php else: ?>
                                                <small class="text-muted">-</small>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($student['submission_id']): ?>
                                                <a href="/class/homework/comment?task_id=<?= $dataTaskId ?>&deliver_id=<?= $student['deliver_id'] ?>&topic_id=<?= $topicId ?>&phase_id=<?= $phaseId ?>"
                                                    class="btn btn-sm btn-outline-success" title="Nhận xét & Đánh giá">
                                                    <i class="bi bi-chat-left-text"></i> Nhận xét
                                                </a>
                                            <?php else: ?>
                                                <small class="text-muted">-</small>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    </div>
    <?php
}

/**
 * Get list of students for a specific assignment
 * @param int $taskId
 * @param array $assignment
 * @return array
 */
function getStudentsForAssignment($taskId, $assignment)
{
    global $pdo;

    try {
        // $taskId here is actually the dataTaskId passed from caller
        // It represents the task where actual submissions exist (Phase 1 for Phase 2 viewing)
        $params = [':task_id' => $taskId];

        if ($assignment['type'] == 'class') {
            // Get students who are members of this specific class
            $sql = "
                SELECT 
                    u.user_id, 
                    u.username as fullname,
                    (SELECT r2.id_response FROM response r2 
                     WHERE r2.task_id = :task_id 
                     AND r2.user_id = u.user_id 
                     AND r2.parent_response IS NULL 
                     LIMIT 1) as submission_id,
                    (SELECT COALESCE(
                         (SELECT MAX(c.created_at) FROM comment c
                          INNER JOIN content ct ON c.content_id = ct.content_id
                          INNER JOIN response r_inner ON ct.response_id = r_inner.id_response
                          WHERE r_inner.task_id = :task_id 
                          AND c.user_id = u.user_id),
                         (SELECT r3.created_at FROM response r3 
                          WHERE r3.task_id = :task_id 
                          AND r3.user_id = u.user_id 
                          AND r3.parent_response IS NULL 
                          LIMIT 1)
                     )) as submitted_at,
                    (SELECT d2.id_deliver FROM deliver d2
                     WHERE d2.task_id = :task_id
                     AND (
                         (d2.type = 'user' AND d2.value = u.user_id) OR
                         (d2.type = 'class' AND d2.value = cm.class_id)
                     )
                     AND EXISTS (
                         SELECT 1 FROM response r4 
                         WHERE r4.task_id = :task_id 
                         AND r4.user_id = u.user_id 
                         AND r4.parent_response IS NULL
                     )
                     ORDER BY CASE d2.type 
                         WHEN 'user' THEN 1 
                         WHEN 'class' THEN 3 
                     END
                     LIMIT 1) as actual_deliver_id
                FROM users u
                INNER JOIN class_member cm ON u.user_id = cm.user_id
                WHERE cm.class_id = :value 
                AND u.role = 'student'
                ORDER BY u.username
            ";
            $params[':value'] = $assignment['value'];
        } elseif ($assignment['type'] == 'group') {
            $sql = "
                SELECT 
                    u.user_id, 
                    u.username as fullname,
                    (SELECT r2.id_response FROM response r2 
                     WHERE r2.task_id = :task_id 
                     AND r2.user_id = u.user_id 
                     AND r2.parent_response IS NULL 
                     LIMIT 1) as submission_id,
                    (SELECT COALESCE(
                         (SELECT MAX(c.created_at) FROM comment c
                          INNER JOIN content ct ON c.content_id = ct.content_id
                          INNER JOIN response r_inner ON ct.response_id = r_inner.id_response
                          WHERE r_inner.task_id = :task_id 
                          AND c.user_id = u.user_id),
                         (SELECT r3.created_at FROM response r3 
                          WHERE r3.task_id = :task_id 
                          AND r3.user_id = u.user_id 
                          AND r3.parent_response IS NULL 
                          LIMIT 1)
                     )) as submitted_at,
                    (SELECT d2.id_deliver FROM deliver d2
                     WHERE d2.task_id = :task_id
                     AND (
                         (d2.type = 'user' AND d2.value = u.user_id) OR
                         (d2.type = 'group' AND d2.value = gm.group_id)
                     )
                     AND EXISTS (
                         SELECT 1 FROM response r4 
                         WHERE r4.task_id = :task_id 
                         AND r4.user_id = u.user_id 
                         AND r4.parent_response IS NULL
                     )
                     ORDER BY CASE d2.type 
                         WHEN 'user' THEN 1 
                         WHEN 'group' THEN 2 
                     END
                     LIMIT 1) as actual_deliver_id
                FROM users u
                INNER JOIN group_member gm ON u.user_id = gm.user_id
                WHERE gm.group_id = :value AND u.role = 'student'
                ORDER BY u.username
            ";
            $params[':value'] = $assignment['value'];
        } else {
            // Individual user assignment
            $sql = "
                SELECT 
                    u.user_id, 
                    u.username as fullname,
                    (SELECT r2.id_response FROM response r2 
                     WHERE r2.task_id = :task_id 
                     AND r2.user_id = u.user_id 
                     AND r2.parent_response IS NULL 
                     LIMIT 1) as submission_id,
                    (SELECT COALESCE(
                         (SELECT MAX(c.created_at) FROM comment c
                          INNER JOIN content ct ON c.content_id = ct.content_id
                          INNER JOIN response r_inner ON ct.response_id = r_inner.id_response
                          WHERE r_inner.task_id = :task_id 
                          AND c.user_id = u.user_id),
                         (SELECT r3.created_at FROM response r3 
                          WHERE r3.task_id = :task_id 
                          AND r3.user_id = u.user_id 
                          AND r3.parent_response IS NULL 
                          LIMIT 1)
                     )) as submitted_at,
                    (SELECT d2.id_deliver FROM deliver d2
                     WHERE d2.task_id = :task_id
                     AND d2.type = 'user' 
                     AND d2.value = u.user_id
                     AND EXISTS (
                         SELECT 1 FROM response r4 
                         WHERE r4.task_id = :task_id 
                         AND r4.user_id = u.user_id 
                         AND r4.parent_response IS NULL
                     )
                     LIMIT 1) as actual_deliver_id
                FROM users u
                WHERE u.user_id = :value AND u.role = 'student'
            ";
            $params[':value'] = $assignment['value'];
        }

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $students = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Set deliver_id based on actual usage or fallback to assignment's deliver_id
        foreach ($students as &$student) {
            // If student has submitted and we found the actual deliver they used, use it
            // Otherwise fallback to the assignment's deliver_id
            $student['deliver_id'] = $student['actual_deliver_id'] ?? $assignment['id_deliver'];
        }
        unset($student); // Break reference

        return $students;
    } catch (PDOException $e) {
        error_log("Error in getStudentsForAssignment: " . $e->getMessage());
        return [];
    }
}

/**
 * Get detailed task assignments with status
 * @param int $taskId - Task ID for assignments (current viewing task)
 * @param int $dataTaskId - Task ID for submissions data (Phase 1 if viewing Phase 2)
 * @return array
 */
function getTaskAssignmentsDetailed($taskId, $dataTaskId = null)
{
    global $pdo;

    // If dataTaskId not provided, use taskId
    if ($dataTaskId === null) {
        $dataTaskId = $taskId;
    }

    try {
        // Get unique assignments without duplication
        $stmt = $pdo->prepare("
            SELECT DISTINCT
                d.id_deliver,
                d.task_id,
                d.type,
                d.value,
                d.created_at,
                CASE 
                    WHEN d.type = 'class' THEN c.class_name
                    WHEN d.type = 'group' THEN g.group_name
                    WHEN d.type = 'user' THEN u.fullname
                END as assigned_to_name,
                CASE 
                    WHEN d.type = 'class' THEN 1
                    WHEN d.type = 'group' THEN 2
                    WHEN d.type = 'user' THEN 3
                END as assign_type
            FROM deliver d
            LEFT JOIN class c ON d.type = 'class' AND d.value = c.class_id
            LEFT JOIN `group` g ON d.type = 'group' AND d.value = g.group_id
            LEFT JOIN users u ON d.type = 'user' AND d.value = u.user_id
            WHERE d.task_id = :task_id
            ORDER BY d.id_deliver DESC
        ");

        $stmt->execute([':task_id' => $taskId]);
        $assignments = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Calculate completion status for each assignment using dataTaskId for submissions
        foreach ($assignments as &$assignment) {
            $students = getStudentsForAssignment($dataTaskId, $assignment);
            $total = count($students);
            $completed = 0;

            foreach ($students as $student) {
                if ($student['submitted_at']) {
                    $completed++;
                }
            }

            // Determine overall status for this assignment
            if ($completed == 0) {
                $assignment['completion_status'] = 'not_started';
            } elseif ($completed == $total) {
                $assignment['completion_status'] = 'completed';
            } else {
                $assignment['completion_status'] = 'in_progress';
            }

            $assignment['students_total'] = $total;
            $assignment['students_completed'] = $completed;
        }

        return $assignments;
    } catch (PDOException $e) {
        error_log("Error in getTaskAssignmentsDetailed: " . $e->getMessage());
        return [];
    }
}

/**
 * Get statistics for task assignments by counting actual students
 * @param int $taskId
 * @param int $classId
 * @return array
 */
function getTaskStudentStatistics($taskId, $classId, $dataTaskId = null)
{
    global $pdo;

    // If dataTaskId not provided, use taskId (backward compatibility)
    if ($dataTaskId === null) {
        $dataTaskId = $taskId;
    }

    try {
        // Get all unique students assigned to this task
        $allStudents = [];

        // Get all assignments - use dataTaskId because assignments were created for Phase 1 task
        // For Phase 2 viewing Phase 1 data, we need to get Phase 1 assignments
        $stmt = $pdo->prepare("SELECT * FROM deliver WHERE task_id = :task_id");
        $stmt->execute([':task_id' => $dataTaskId]);
        $assignments = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($assignments as $assignment) {
            // Use dataTaskId to get actual submission data
            $students = getStudentsForAssignment($dataTaskId, $assignment);
            foreach ($students as $student) {
                // Use user_id as key to avoid duplicates
                $allStudents[$student['user_id']] = $student;
            }
        }

        // Convert back to indexed array
        $allStudents = array_values($allStudents);

        $stats = [
            'total' => count($allStudents),
            'completed' => 0,
            'in_progress' => 0,
            'not_started' => 0
        ];

        foreach ($allStudents as $student) {
            if ($student['submitted_at']) {
                $stats['completed']++;
            } else {
                $stats['not_started']++;
            }
        }

        return $stats;
    } catch (PDOException $e) {
        error_log("Error in getTaskStudentStatistics: " . $e->getMessage());
        return [
            'total' => 0,
            'completed' => 0,
            'in_progress' => 0,
            'not_started' => 0
        ];
    }
}

/**
 * Render students filtered by their submission status
 * @param int $taskId
 * @param array $assignments
 * @param string $status ('completed', 'in_progress', 'not_started')
 */
function renderStudentsByStatus($dataTaskId, $assignments, $status)
{
    global $topicId, $phaseId, $taskId;

    // $dataTaskId is the task where actual submissions exist (Phase 1 for Phase 2 viewing)
    // $taskId is the current task (Phase 2) - needed for links

    // Collect all unique students from all assignments
    $allStudents = [];
    foreach ($assignments as $assignment) {
        $students = getStudentsForAssignment($dataTaskId, $assignment);
        foreach ($students as $student) {
            // Use user_id as key to avoid duplicates, store assignment info
            if (!isset($allStudents[$student['user_id']])) {
                $student['assignment_name'] = $assignment['assigned_to_name'];
                $student['assignment_type'] = $assignment['assign_type'];
                // deliver_id is already set in getStudentsForAssignment() using getDeliverIdForUser()
                $allStudents[$student['user_id']] = $student;
            }
        }
    }

    // Filter by status
    $filteredStudents = array_filter($allStudents, function ($student) use ($status) {
        if ($status == 'completed') {
            return !empty($student['submitted_at']);
        } elseif ($status == 'in_progress') {
            // For now, in_progress is not used for individual students
            return false;
        } else { // not_started
            return empty($student['submitted_at']);
        }
    });

    if (empty($filteredStudents)): ?>
        <div class="alert alert-info">
            <i class="bi bi-info-circle"></i> Không có học sinh nào trong trạng thái này
        </div>
    <?php return;
    endif;
    ?>

    <div class="table-responsive">
        <table class="table table-hover">
            <thead>
                <tr>
                    <th>Học sinh</th>
                    <th>Được giao từ</th>
                    <th>Trạng thái</th>
                    <th>Thời gian nộp</th>
                    <th>Thao tác</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($filteredStudents as $student): ?>
                    <?php
                    $statusBadge = 'secondary';
                    $statusIcon = 'dash-circle';
                    $statusText = 'Chưa nộp';
                    $typeIcon = 'person-fill';
                    $typeBadge = 'info';

                    if ($student['submitted_at']) {
                        $statusBadge = 'success';
                        $statusIcon = 'check-circle-fill';
                        $statusText = 'Đã nộp';
                    }

                    if ($student['assignment_type'] == 1) {
                        $typeIcon = 'people-fill';
                        $typeBadge = 'primary';
                    } elseif ($student['assignment_type'] == 2) {
                        $typeIcon = 'diagram-3-fill';
                        $typeBadge = 'success';
                    }
                    ?>
                    <tr>
                        <td>
                            <i class="bi bi-person"></i>
                            <strong><?= htmlspecialchars($student['fullname']) ?></strong>
                        </td>
                        <td>
                            <span class="badge bg-<?= $typeBadge ?>">
                                <i class="bi bi-<?= $typeIcon ?>"></i>
                                <?= htmlspecialchars($student['assignment_name']) ?>
                            </span>
                        </td>
                        <td>
                            <span class="badge bg-<?= $statusBadge ?>">
                                <i class="bi bi-<?= $statusIcon ?>"></i> <?= $statusText ?>
                            </span>
                        </td>
                        <td>
                            <?php if ($student['submitted_at']): ?>
                                <small><?= date('d/m/Y H:i', strtotime($student['submitted_at'])) ?></small>
                            <?php else: ?>
                                <small class="text-muted">-</small>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($student['submission_id']): ?>
                                <a href="/class/homework/comment?task_id=<?= $dataTaskId ?>&deliver_id=<?= $student['deliver_id'] ?>&topic_id=<?= $topicId ?>&phase_id=<?= $phaseId ?>"
                                    class="btn btn-sm btn-outline-success" title="Nhận xét & Đánh giá">
                                    <i class="bi bi-chat-left-text"></i> Nhận xét
                                </a>
                            <?php else: ?>
                                <small class="text-muted">-</small>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php
}
?>