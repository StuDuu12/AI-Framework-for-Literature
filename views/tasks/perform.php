<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php require_once './views/templates/assets.php'; ?>
    <title>Thực hiện nhiệm vụ</title>
</head>

<body>
    <?php
    require_once './config/db.php';
    require_once './config/helper_task.php';
    require_once './config/helper_phase.php';
    require_once './config/helper_class.php';
    require_once './views/templates/header.php';

    if (!isset($_SESSION['user']['role']) || $_SESSION['user']['role'] !== 'teacher') {
        require_once './views/templates/sidebar_student.php';
    } else {
        require_once './views/templates/sidebar_teacher.php';
    }

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

    // Read task file content if exists
    $taskContent = '';
    if ($task['file_content']) {
        $taskContent = readTaskFileContent($task['file_content']);
    }
    ?>

    <main id="main" class="main">
        <div class="pagetitle">
            <h1>Thực hiện nhiệm vụ</h1>
            <nav>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="/class"><?= $_SESSION['current_class_name']; ?></a></li>
                    <li class="breadcrumb-item"><a href="/class"><?= htmlspecialchars($topic['topic_name']) ?></a></li>
                    <li class="breadcrumb-item"><a href="index.php?topic_id=<?= $topicId ?>">Pha học tập</a></li>
                    <li class="breadcrumb-item"><a
                            href="index.php?topic_id=<?= $topicId ?>&phase_id=<?= $phaseId ?>">Nhiệm vụ</a></li>
                    <li class="breadcrumb-item active">Thực hiện</li>
                </ol>
            </nav>
        </div><!-- End Page Title -->

        <section class="section">
            <div class="row">
                <div class="col-lg-8">
                    <!-- Task Info Card -->
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title">
                                <i class="bi bi-clipboard-check text-primary"></i>
                                <?= htmlspecialchars($task['title']) ?>
                            </h5>

                            <?php if (!empty($task['description'])): ?>
                                <div class="alert alert-info">
                                    <h6><i class="bi bi-info-circle"></i> Mô tả:</h6>
                                    <p class="mb-0"><?= nl2br(htmlspecialchars($task['description'])) ?></p>
                                </div>
                            <?php endif; ?>

                            <?php if ($taskContent !== false && !empty($taskContent)): ?>
                                <div class="mb-3">
                                    <h6><i class="bi bi-file-text"></i> Nội dung nhiệm vụ:</h6>
                                    <div class="border rounded p-3 bg-light" style="max-height: 400px; overflow-y: auto;">
                                        <pre class="mb-0"
                                            style="white-space: pre-wrap; word-wrap: break-word;"><?= htmlspecialchars($taskContent) ?></pre>
                                    </div>
                                    <?php if ($task['content_creator']): ?>
                                        <small class="text-muted">
                                            <i class="bi bi-pencil"></i>
                                            Nội dung được tạo bởi: <?= htmlspecialchars($task['content_creator_name']) ?>
                                        </small>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>

                            <!-- Student Work Area -->
                            <div class="card border-primary">
                                <div class="card-header bg-primary text-white">
                                    <h6 class="mb-0">
                                        <i class="bi bi-pencil-square"></i> Khu vực làm việc của bạn
                                    </h6>
                                </div>
                                <div class="card-body">
                                    <p class="text-muted">
                                        <i class="bi bi-lightbulb"></i>
                                        Đây là nơi bạn sẽ thực hiện nhiệm vụ. Chức năng này đang được phát triển...
                                    </p>

                                    <!-- Placeholder for future implementation -->
                                    <div class="alert alert-warning">
                                        <i class="bi bi-tools"></i>
                                        <strong>Đang phát triển:</strong>
                                        Khu vực này sẽ cho phép bạn nhập câu trả lời, tải file lên, hoặc tương tác với
                                        các công cụ AI.
                                    </div>

                                    <button class="btn btn-success" disabled>
                                        <i class="bi bi-check-circle"></i> Nộp bài (Sắp ra mắt)
                                    </button>
                                    <a href="index.php?topic_id=<?= $topicId ?>&phase_id=<?= $phaseId ?>"
                                        class="btn btn-secondary">
                                        <i class="bi bi-arrow-left"></i> Quay lại
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Side Info Card -->
                <div class="col-lg-4">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title">Thông tin</h5>

                            <div class="mb-3">
                                <h6 class="text-muted mb-2">Chủ đề</h6>
                                <p class="mb-0"><?= htmlspecialchars($topic['topic_name']) ?></p>
                            </div>

                            <div class="mb-3">
                                <h6 class="text-muted mb-2">Pha học tập</h6>
                                <p class="mb-0">
                                    <span class="badge bg-primary">Bước <?= $phase['phase_number'] ?></span>
                                    <?= htmlspecialchars($phase['phase_name']) ?>
                                </p>
                            </div>

                            <div class="mb-3">
                                <h6 class="text-muted mb-2">Loại pha</h6>
                                <p class="mb-0"><?= htmlspecialchars($phase['m_phase_name']) ?></p>
                            </div>

                            <div class="mb-3">
                                <h6 class="text-muted mb-2">Giáo viên phụ trách</h6>
                                <p class="mb-0">
                                    <i class="bi bi-person-circle"></i>
                                    <?= htmlspecialchars($task['added_by_name'] ?? 'N/A') ?>
                                </p>
                            </div>

                            <div class="mb-3">
                                <h6 class="text-muted mb-2">Thời gian tạo</h6>
                                <p class="mb-0">
                                    <i class="bi bi-calendar"></i>
                                    <?= date('d/m/Y H:i', strtotime($task['created_at'])) ?>
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Phase Features -->
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title">Tính năng pha</h5>
                            <div class="d-flex flex-column gap-2">
                                <?php if ($phase['AI']): ?>
                                    <div class="badge bg-info">
                                        <i class="bi bi-robot"></i> Hỗ trợ AI
                                    </div>
                                <?php endif; ?>
                                <?php if ($phase['Comment']): ?>
                                    <div class="badge bg-success">
                                        <i class="bi bi-chat-dots"></i> Bình luận
                                    </div>
                                <?php endif; ?>
                                <?php if ($phase['Edit']): ?>
                                    <div class="badge bg-warning">
                                        <i class="bi bi-pencil-square"></i> Chỉnh sửa
                                    </div>
                                <?php endif; ?>
                            </div>
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