<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php require_once './views/templates/assets.php'; ?>
    <title>Xem bài phân tích học sinh</title>
    <style>
        .submission-card {
            transition: all 0.3s ease;
            border-left: 4px solid #6c757d;
            margin-bottom: 1rem;
        }

        .submission-card:hover {
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
            transform: translateY(-2px);
        }

        .submission-card.has-comments {
            border-left-color: #0d6efd;
        }

        .submission-card.has-teacher-feedback {
            border-left-color: #198754;
        }

        .comment-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.25rem;
            padding: 0.25rem 0.5rem;
            border-radius: 0.25rem;
            font-size: 0.875rem;
        }

        .student-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            object-fit: cover;
        }

        .filter-tabs {
            margin-bottom: 1.5rem;
        }

        .stat-box {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 1.5rem;
            border-radius: 0.5rem;
            text-align: center;
        }

        .stat-box.primary {
            background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
        }

        .stat-box.success {
            background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
        }

        .stat-box.warning {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
        }
    </style>
</head>

<body>
    <?php
    require_once './config/db.php';
    require_once './config/helper_task.php';
    require_once './config/helper_phase.php';
    require_once './views/templates/header.php';

    if (!isset($_SESSION['user']['role']) || $_SESSION['user']['role'] !== 'teacher') {
        header('Location: /class');
        exit;
    }

    require_once './views/templates/sidebar.php';

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

    // Simplified teacher check - if they can access view.php, they can access this
    // Just verify the teacher role
    if ($_SESSION['user']['role'] !== 'teacher') {
        header('Location: /class');
        exit;
    }

    // Determine if this phase should view data from a previous phase
    // Only Phase 2 (CHỮA BÀI - phase_code: 'review' or 'CHUABAI') should look back to Phase 1
    // Phase 3 (LÀM BÀI TẬP VỀ NHÀ - phase_code: 'homework' or 'BTVN') is independent
    $shouldLookBack = false;

    // Check current phase's main_phase_code
    $phaseCheckStmt = $pdo->prepare("
        SELECT mp.phase_code
        FROM phase p
        INNER JOIN main_phase mp ON p.m_phase_id = mp.m_phase_id
        WHERE p.phase_id = :phase_id
    ");
    $phaseCheckStmt->execute([':phase_id' => $phaseId]);
    $currentPhaseCode = $phaseCheckStmt->fetchColumn();

    // Only CHỮA BÀI (review phase) should look back to previous phase
    if ($currentPhaseCode === 'CHUABAI' || $currentPhaseCode === 'review') {
        $shouldLookBack = true;
    }

    $submissionTaskId = $taskId; // Default: use current task

    if ($shouldLookBack) {
        // Find task from previous phase (where submissions actually exist)
        // This is important for Phase 2 (CHỮA BÀI) to view Phase 1 submissions
        $sourceTaskStmt = $pdo->prepare("
            SELECT t.task_id, p.phase_number, p.topic_id
            FROM task t
            INNER JOIN phase p ON t.phase_id = p.phase_id
            WHERE p.topic_id = (SELECT topic_id FROM phase WHERE phase_id = :current_phase_id)
            AND p.phase_number < (SELECT phase_number FROM phase WHERE phase_id = :current_phase_id)
            ORDER BY p.phase_number DESC
            LIMIT 1
        ");
        $sourceTaskStmt->execute([':current_phase_id' => $phaseId]);
        $sourceTask = $sourceTaskStmt->fetch(PDO::FETCH_ASSOC);

        // Use source task if found (Phase 1), otherwise use current task
        if ($sourceTask) {
            $submissionTaskId = $sourceTask['task_id'];
        }
    }


    // Get all student submissions with comment counts
    // Fixed: Only count comments where comment_id IS NOT NULL
    $stmt = $pdo->prepare("
        SELECT 
            r.id_response,
            r.user_id,
            r.task_id,
            r.created_at as submitted_at,
            u.username as student_name,
            (SELECT COUNT(c2.comment_id) 
             FROM content ct2 
             INNER JOIN comment c2 ON c2.content_id = ct2.content_id 
             WHERE ct2.response_id = r.id_response) as total_comments,
            (SELECT COUNT(c3.comment_id) 
             FROM content ct3 
             INNER JOIN comment c3 ON c3.content_id = ct3.content_id 
             WHERE ct3.response_id = r.id_response AND c3.user_id != r.user_id) as teacher_comments,
            (SELECT COUNT(c4.comment_id) 
             FROM content ct4 
             INNER JOIN comment c4 ON c4.content_id = ct4.content_id 
             WHERE ct4.response_id = r.id_response AND c4.user_id = r.user_id) as student_comments,
            (SELECT COUNT(ct5.content_id) 
             FROM content ct5 
             WHERE ct5.response_id = r.id_response) as content_blocks
        FROM response r
        INNER JOIN users u ON r.user_id = u.user_id
        WHERE r.task_id = :task_id 
        AND r.parent_response IS NULL
        AND u.role = 'student'
        ORDER BY r.created_at DESC
    ");

    $stmt->execute([':task_id' => $submissionTaskId]);
    $submissions = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Remove duplicates by user_id (keep only the first response per user)
    $uniqueSubmissions = [];
    $seenUserIds = [];
    foreach ($submissions as $submission) {
        if (!in_array($submission['user_id'], $seenUserIds)) {
            $uniqueSubmissions[] = $submission;
            $seenUserIds[] = $submission['user_id'];
        }
    }
    $submissions = $uniqueSubmissions;

    // Calculate statistics
    $totalSubmissions = count($submissions);
    $submissionsWithComments = 0;
    $submissionsWithTeacherFeedback = 0;

    foreach ($submissions as $submission) {
        if ($submission['total_comments'] > 0) {
            $submissionsWithComments++;
        }
        if ($submission['teacher_comments'] > 0) {
            $submissionsWithTeacherFeedback++;
        }
    }
    ?>

    <main id="main" class="main">
        <div class="pagetitle">
            <h1>Xem bài phân tích học sinh</h1>
            <nav>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="/class">Lớp học</a></li>
                    <li class="breadcrumb-item"><a href="/class/phases?topic_id=<?= $topicId ?>">Pha học tập</a></li>
                    <li class="breadcrumb-item"><a
                            href="/class/task?topic_id=<?= $topicId ?>&phase_id=<?= $phaseId ?>">Nhiệm vụ</a></li>
                    <li class="breadcrumb-item"><a
                            href="/class/task/view?task_id=<?= $taskId ?>&topic_id=<?= $topicId ?>&phase_id=<?= $phaseId ?>">Chi
                            tiết</a></li>
                    <li class="breadcrumb-item active">Xem bài phân tích</li>
                </ol>
            </nav>
        </div>

        <?php
        // Debug: Check if there are any comments in the database for this task
        $debugStmt = $pdo->prepare("
            SELECT COUNT(*) as comment_count 
            FROM comment c 
            INNER JOIN content ct ON c.content_id = ct.content_id
            INNER JOIN response r ON ct.response_id = r.id_response
            WHERE r.task_id = :task_id
        ");
        $debugStmt->execute([':task_id' => $submissionTaskId]);
        $debugCount = $debugStmt->fetchColumn();

        if ($debugCount > 0 && $totalSubmissions > 0 && $submissionsWithComments == 0):
        ?>
            <div class="alert alert-warning alert-dismissible fade show" role="alert">
                <i class="bi bi-exclamation-triangle"></i>
                <strong>Thông báo:</strong> Có <?= $debugCount ?> comment trong database nhưng không hiển thị. Có thể do vấn đề query hoặc cấu trúc dữ liệu.
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <section class="section">
            <!-- Task Info Card -->
            <div class="row mb-4">
                <div class="col-12">
                    <div class="card">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <h5 class="card-title mb-1">
                                        <i class="bi bi-clipboard-check text-primary"></i>
                                        <?= htmlspecialchars($task['title']) ?>
                                    </h5>
                                    <p class="text-muted mb-0">
                                        <i class="bi bi-layers"></i> Pha: Bước <?= $phase['phase_number'] ?> -
                                        <?= htmlspecialchars($phase['phase_name']) ?>
                                    </p>
                                </div>
                                <a href="/class/task/view?task_id=<?= $taskId ?>&topic_id=<?= $topicId ?>&phase_id=<?= $phaseId ?>"
                                    class="btn btn-secondary">
                                    <i class="bi bi-arrow-left"></i> Quay lại
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Statistics -->
            <div class="row mb-4">
                <div class="col-md-4 mb-3">
                    <div class="stat-box primary">
                        <i class="bi bi-file-earmark-text" style="font-size: 2rem;"></i>
                        <h3 class="mt-2 mb-0"><?= $totalSubmissions ?></h3>
                        <p class="mb-0">Tổng số bài nộp</p>
                    </div>
                </div>
                <div class="col-md-4 mb-3">
                    <div class="stat-box warning">
                        <i class="bi bi-chat-dots" style="font-size: 2rem;"></i>
                        <h3 class="mt-2 mb-0"><?= $submissionsWithComments ?></h3>
                        <p class="mb-0">Bài có bình luận</p>
                    </div>
                </div>
                <div class="col-md-4 mb-3">
                    <div class="stat-box success">
                        <i class="bi bi-check-circle" style="font-size: 2rem;"></i>
                        <h3 class="mt-2 mb-0"><?= $submissionsWithTeacherFeedback ?></h3>
                        <p class="mb-0">Đã phản hồi</p>
                    </div>
                </div>
            </div>

            <!-- Filter Tabs -->
            <div class="filter-tabs">
                <ul class="nav nav-tabs" id="filterTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="all-tab" data-bs-toggle="tab" data-bs-target="#all-submissions"
                            type="button" role="tab">
                            <i class="bi bi-list"></i> Tất cả (<?= $totalSubmissions ?>)
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="with-comments-tab" data-bs-toggle="tab"
                            data-bs-target="#with-comments" type="button" role="tab">
                            <i class="bi bi-chat-dots text-primary"></i> Có bình luận (<?= $submissionsWithComments ?>)
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="feedback-tab" data-bs-toggle="tab" data-bs-target="#with-feedback"
                            type="button" role="tab">
                            <i class="bi bi-check-circle text-success"></i> Đã phản hồi
                            (<?= $submissionsWithTeacherFeedback ?>)
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="no-feedback-tab" data-bs-toggle="tab" data-bs-target="#no-feedback"
                            type="button" role="tab">
                            <i class="bi bi-clock text-warning"></i> Chưa phản hồi
                            (<?= $totalSubmissions - $submissionsWithTeacherFeedback ?>)
                        </button>
                    </li>
                </ul>
            </div>

            <!-- Tab Content -->
            <div class="tab-content" id="filterTabsContent">
                <!-- All Submissions -->
                <div class="tab-pane fade show active" id="all-submissions" role="tabpanel">
                    <?php renderSubmissionsList($submissions, $taskId, $topicId, $phaseId); ?>
                </div>

                <!-- With Comments -->
                <div class="tab-pane fade" id="with-comments" role="tabpanel">
                    <?php
                    $filtered = array_filter($submissions, function ($s) {
                        return $s['total_comments'] > 0;
                    });
                    renderSubmissionsList($filtered, $taskId, $topicId, $phaseId);
                    ?>
                </div>

                <!-- With Teacher Feedback -->
                <div class="tab-pane fade" id="with-feedback" role="tabpanel">
                    <?php
                    $filtered = array_filter($submissions, function ($s) {
                        return $s['teacher_comments'] > 0;
                    });
                    renderSubmissionsList($filtered, $taskId, $topicId, $phaseId);
                    ?>
                </div>

                <!-- No Feedback -->
                <div class="tab-pane fade" id="no-feedback" role="tabpanel">
                    <?php
                    $filtered = array_filter($submissions, function ($s) {
                        return $s['teacher_comments'] == 0;
                    });
                    renderSubmissionsList($filtered, $taskId, $topicId, $phaseId);
                    ?>
                </div>
            </div>
        </section>
    </main>

    <?php
    require_once './views/templates/footer.php';
    require_once './views/templates/vendors.php';
    ?>
</body>

</html>

<?php
/**
 * Render list of submissions
 */
function renderSubmissionsList($submissions, $taskId, $topicId, $phaseId)
{
    if (empty($submissions)): ?>
        <div class="alert alert-info">
            <i class="bi bi-info-circle"></i> Không có bài nộp nào
        </div>
    <?php return;
    endif;
    ?>

    <div class="row">
        <?php foreach ($submissions as $submission): ?>
            <?php
            $cardClass = 'submission-card';
            if ($submission['teacher_comments'] > 0) {
                $cardClass .= ' has-teacher-feedback';
            } elseif ($submission['total_comments'] > 0) {
                $cardClass .= ' has-comments';
            }
            ?>
            <div class="col-md-6 col-lg-4">
                <div class="card <?= $cardClass ?>">
                    <div class="card-body">
                        <div class="d-flex align-items-center mb-3">
                            <img src="https://i.pinimg.com/564x/92/26/5c/92265c40c8e428122e0b32adc1994594.jpg"
                                alt="Avatar" class="student-avatar me-3">
                            <div>
                                <h6 class="mb-0">
                                    <i class="bi bi-person"></i>
                                    <?= htmlspecialchars($submission['student_name']) ?>
                                </h6>
                                <small class="text-muted">
                                    <i class="bi bi-clock"></i>
                                    <?= date('d/m/Y H:i', strtotime($submission['submitted_at'])) ?>
                                </small>
                            </div>
                        </div>

                        <div class="mb-3">
                            <div class="d-flex gap-2 flex-wrap">
                                <span class="comment-badge bg-light text-dark border">
                                    <i class="bi bi-file-text"></i>
                                    <?= $submission['content_blocks'] ?> nội dung
                                </span>
                                <?php if ($submission['total_comments'] > 0): ?>
                                    <span class="comment-badge bg-primary text-white">
                                        <i class="bi bi-chat-dots"></i>
                                        <?= $submission['total_comments'] ?> bình luận
                                    </span>
                                <?php endif; ?>
                                <?php if ($submission['student_comments'] > 0): ?>
                                    <span class="comment-badge bg-info text-white">
                                        <i class="bi bi-person-circle"></i>
                                        <?= $submission['student_comments'] ?> của HS
                                    </span>
                                <?php endif; ?>
                                <?php if ($submission['teacher_comments'] > 0): ?>
                                    <span class="comment-badge bg-success text-white">
                                        <i class="bi bi-check-circle"></i>
                                        <?= $submission['teacher_comments'] ?> phản hồi
                                    </span>
                                <?php else: ?>
                                    <span class="comment-badge bg-warning text-dark">
                                        <i class="bi bi-exclamation-circle"></i>
                                        Chưa phản hồi
                                    </span>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="d-grid gap-2">
                            <a href="/class/homework/comment?task_id=<?= $submission['task_id'] ?>&user_id=<?= $submission['user_id'] ?>&viewing_from_task=<?= $taskId ?>"
                                class="btn btn-primary btn-sm">
                                <i class="bi bi-eye"></i> Xem & Phản hồi
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php
}
?>