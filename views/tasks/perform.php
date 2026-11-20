<?php
// ALL VALIDATION AND REDIRECTS MUST HAPPEN BEFORE ANY OUTPUT
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../config/helper_task.php';
require_once __DIR__ . '/../../config/helper_phase.php';
require_once __DIR__ . '/../../config/helper_class.php';
require_once __DIR__ . '/../../config/helper_redirect.php';

// Get IDs from URL
$taskId = isset($_GET['task_id']) ? intval($_GET['task_id']) : 0;
$phaseId = isset($_GET['phase_id']) ? intval($_GET['phase_id']) : 0;
$topicId = isset($_GET['topic_id']) ? intval($_GET['topic_id']) : 0;
$deliverId = isset($_GET['deliver_id']) ? intval($_GET['deliver_id']) : 0;

if (!$taskId || !$phaseId || !$topicId) {
    redirectWithError('/class', 'invalid_data');
}

// Get current user ID
$userId = $_SESSION['user']['user_id'];

// Check if current task has review_task (points to previous phase's task)
$reviewTaskStmt = $pdo->prepare("
    SELECT review_task 
    FROM task 
    WHERE task_id = :task_id
");
$reviewTaskStmt->execute([':task_id' => $taskId]);
$reviewTask = $reviewTaskStmt->fetchColumn();

$shouldLookBack = false;
$dataTaskId = $taskId;
$dataPhaseId = $phaseId;

// If this task reviews another task, use that task's data
if ($reviewTask && $reviewTask > 0) {
    $shouldLookBack = true;
    $dataTaskId = $reviewTask;

    // Get the phase_id of the review task
    $reviewPhaseStmt = $pdo->prepare("SELECT phase_id FROM task WHERE task_id = :task_id");
    $reviewPhaseStmt->execute([':task_id' => $reviewTask]);
    $dataPhaseId = $reviewPhaseStmt->fetchColumn();
} else {
    // Fallback: Check phase code for backward compatibility
    $phaseCheckStmt = $pdo->prepare("
        SELECT mp.phase_code
        FROM phase p
        INNER JOIN main_phase mp ON p.m_phase_id = mp.m_phase_id
        WHERE p.phase_id = :phase_id
    ");
    $phaseCheckStmt->execute([':phase_id' => $phaseId]);
    $currentPhaseCode = $phaseCheckStmt->fetchColumn();

    // If this is CHUA BAI phase, look back to previous phase for data
    if ($currentPhaseCode === 'CHUABAI' || $currentPhaseCode === 'review') {
        $shouldLookBack = true;

        // Find task from previous phase
        $sourceTaskStmt = $pdo->prepare("
            SELECT t.task_id, p.phase_id
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
            $dataPhaseId = $sourceTask['phase_id'];
        }
    }
}

// If deliver_id is provided, validate user access
if ($deliverId > 0) {
    if (!validateUserDeliverAccess($deliverId, $userId)) {
        // User doesn't have access to this deliver
        $redirectUrl = buildUrl('/class/task', [
            'topic_id' => $topicId,
            'phase_id' => $phaseId
        ]);
        redirectWithError($redirectUrl, 'unauthorized');
    }
} else {
    // No deliver_id provided, try to get it for the user
    // Use dataTaskId (Phase 1 if in review phase, otherwise current task)
    $deliverId = getDeliverIdForUser($dataTaskId, $userId);
    if ($deliverId === null) {
        // Task not assigned to this user
        $redirectUrl = buildUrl('/class/task', [
            'topic_id' => $topicId,
            'phase_id' => $phaseId
        ]);
        redirectWithError($redirectUrl, 'not_assigned');
    }
}

// Check if there is an existing submission response for this deliver
$hasSubmissionResponse = false;
if ($deliverId > 0) {
    $stmtCheckResp = $pdo->prepare("SELECT response FROM deliver WHERE id_deliver = :deliver_id AND response IS NOT NULL");
    $stmtCheckResp->execute([':deliver_id' => $deliverId]);
    $hasSubmissionResponse = (bool)$stmtCheckResp->fetchColumn();
}

// Get task details
$task = getTaskDetails($taskId);
if (!$task) {
    redirectWithError('/class', 'not_found');
}

// Get phase details
$phase = getPhaseDetails($phaseId);
if (!$phase) {
    redirectWithError('/class', 'not_found');
}

// Get topic details
$topic = getTopicDetails($topicId);
if (!$topic) {
    redirectWithError('/class', 'not_found');
}

// Read task file content if exists
$taskContent = '';
if ($task['file_content']) {
    $taskContent = readTaskFileContent($task['file_content']);
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php require_once __DIR__ . '/../templates/assets.php'; ?>
    <title>Perform Task</title>
</head>

<body>
    <?php
    require_once __DIR__ . '/../templates/header.php';
    require_once __DIR__ . '/../templates/sidebar.php';
    

    ?>

    <main id="main" class="main">
        <div class="pagetitle">
            <h1 data-lang-key="perform_task">Perform Task</h1>
            <nav>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="<?= URL_ROOT ?>/class"><?= htmlspecialchars($_SESSION['current_class_name'] ?? 'Class', ENT_QUOTES, 'UTF-8') ?></a></li>
                    <li class="breadcrumb-item"><a href="<?= URL_ROOT ?>/class"><?= htmlspecialchars($topic['topic_name']) ?></a></li>
                    <li class="breadcrumb-item"><a href="<?= URL_ROOT ?>/class/phases?topic_id=<?= $topicId ?>">Learning
                            Phases</a></li>
                    <li class="breadcrumb-item"><a
                            href="<?= URL_ROOT ?>/class/task?topic_id=<?= $topicId ?>&phase_id=<?= $phaseId ?>">Tasks</a></li>
                    <li class="breadcrumb-item active">Perform</li>
                </ol>
            </nav>
        </div><!-- End Page Title -->

        <section class="section">
            <div class="row">
                <div class="col-lg-8">
                    <!-- Task Info Card -->
                    <div class="card task-card">
                        <div class="card-body">
                            <h5 class="card-title">
                                <i class="bi bi-clipboard-check text-primary"></i>
                                <?= htmlspecialchars($task['title']) ?>
                            </h5>
                            <?php if ($phase['phase_number']): ?>
                                <span class="phase-badge mb-3 d-inline-block">
                                    <span data-lang-key="step">Step</span> <?= (int)$phase['phase_number'] ?>
                                </span>
                            <?php endif; ?>

                            <?php if (!empty($task['description'])): ?>
                                <div class="alert alert-info">
                                    <h6><i class="bi bi-info-circle"></i> Description:</h6>
                                    <p class="mb-0"><?= nl2br(htmlspecialchars($task['description'])) ?></p>
                                </div>
                            <?php endif; ?>

                            <?php if ($taskContent !== false && !empty($taskContent)): ?>
                                <div class="mb-3">
                                    <h6><i class="bi bi-file-text"></i> Task Content:</h6>
                                    <div class="border rounded p-3 bg-light" style="max-height: 400px; overflow-y: auto;">
                                        <pre class="mb-0"
                                            style="white-space: pre-wrap; word-wrap: break-word;"><?= htmlspecialchars($taskContent) ?></pre>
                                    </div>
                                    <?php if ($task['content_creator']): ?>
                                        <small class="text-muted">
                                            <i class="bi bi-pencil"></i>
                                            Content created by: <?= htmlspecialchars($task['content_creator_name']) ?>
                                        </small>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                            <?php
                            $href = '';
                            $phaseCode = $phase['phase_code'];
                            switch ($phaseCode) {
                                case 'PHASE_START':
                                    $href = "/class/homework/analysis_start?task_id=$dataTaskId&topic_id=$topicId&phase_id=$dataPhaseId&deliver_id=$deliverId";
                                    break;
                                case 'PHASE_CHUABAI':
                                    $href = "/class/homework/comment?task_id=$dataTaskId&topic_id=$topicId&phase_id=$dataPhaseId&deliver_id=$deliverId";
                                    break;
                                case 'PHASE_TAKENOTE':
                                    $href = "/class/homework/comment?task_id=$dataTaskId&topic_id=$topicId&phase_id=$dataPhaseId&deliver_id=$deliverId";
                                    break;
                                case 'PHASE_BTVN':
                                    $href = "/class/homework/analysis_chat?task_id=$dataTaskId&topic_id=$topicId&phase_id=$dataPhaseId&deliver_id=$deliverId";
                                    break;
                                case 'PHASE_DANHGIANGUOIHOC':
                                    if (($_SESSION['user']['role'] ?? '') === 'teacher') {
                                        $href = "/class/task/setting_evaluation?task_id=$taskId&topic_id=$topicId&phase_id=$phaseId";
                                    } else {
                                        $href = "/class/evaluate/list?task_id=$taskId&topic_id=$topicId&phase_id=$phaseId";
                                    }
                                    break;
                                case 'PHASE_CHAMDIEM':
                                    if (($_SESSION['user']['role'] ?? '') === 'teacher') {
                                        $href = URL_ROOT . "/class/evaluate/teacher_grades?task_id=$taskId&topic_id=$topicId&phase_id=$phaseId";
                                    } else {
                                        $href = URL_ROOT . "/class/task?topic_id=$topicId&phase_id=$phaseId";
                                    }
                                    break;
                                default:
                                    $href = "/class/homework/analysis_start?task_id=$dataTaskId&topic_id=$topicId&phase_id=$dataPhaseId&deliver_id=$deliverId";
                                    break;
                            }
                            ?>
                            <div class="d-flex gap-2 flex-wrap">
                                <a class="btn btn-success" href="<?= $href ?>" id="startWorkingBtn">
                                    <i class="bi bi-check-circle"></i> <span data-lang-key="start_working">Start Working</span>
                                </a>
                                <button class="btn btn-info" id="restoreCacheBtn" style="display: none;" data-task-id="<?= $dataTaskId ?>" data-deliver-id="<?= $deliverId ?>">
                                    <i class="bi bi-clock-history"></i> <span data-lang-key="restore_work">Restore Previous Work</span>
                                </button>
                                <a href="<?= URL_ROOT ?>/class/task?topic_id=<?= $topicId ?>&phase_id=<?= $phaseId ?>"
                                    class="btn btn-secondary">
                                    <i class="bi bi-arrow-left"></i> <span data-lang-key="back">Back</span>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Side Info Card -->
                <div class="col-lg-4">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title">Information</h5>

                            <div class="mb-3">
                                <h6 class="text-muted mb-2">Topic</h6>
                                <p class="mb-0"><?= htmlspecialchars($topic['topic_name']) ?></p>
                            </div>

                            <div class="mb-3">
                                <h6 class="text-muted mb-2">Learning Phases</h6>
                                <p class="mb-0">
                                    <span class="badge bg-primary"><span>Step</span> <?= $phase['phase_number'] ?></span>
                                    <?= htmlspecialchars($phase['phase_name']) ?>
                                </p>
                            </div>

                            <div class="mb-3">
                                <h6 class="text-muted mb-2">Phase Type</h6>
                                <p class="mb-0"><?= htmlspecialchars($phase['m_phase_name']) ?></p>
                            </div>

                            <div class="mb-3">
                                <h6 class="text-muted mb-2">Teacher in Charge</h6>
                                <p class="mb-0">
                                    <i class="bi bi-person-circle"></i>
                                    <?= htmlspecialchars($task['added_by_name'] ?? 'N/A') ?>
                                </p>
                            </div>

                            <div class="mb-3">
                                <h6 class="text-muted mb-2">Created Time</h6>
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
                            <h5 class="card-title">Phase Features</h5>
                            <div class="d-flex flex-column gap-2">
                                <?php if ($phase['AI']): ?>
                                    <div class="badge bg-info">
                                        <i class="bi bi-robot"></i> AI Support
                                    </div>
                                <?php endif; ?>
                                <?php if ($phase['Comment']): ?>
                                    <div class="badge bg-success">
                                        <i class="bi bi-chat-dots"></i> Comment
                                    </div>
                                <?php endif; ?>
                                <?php if ($phase['Edit']): ?>
                                    <div class="badge bg-warning">
                                        <i class="bi bi-pencil-square"></i> Edit
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
    require_once __DIR__ . '/../templates/footer.php';
    require_once __DIR__ . '/../templates/vendors.php';
    ?>

    <script>
        // Cache Management System
        document.addEventListener('DOMContentLoaded', function() {
            const restoreBtn = document.getElementById('restoreCacheBtn');
            const startWorkingBtn = document.getElementById('startWorkingBtn');
            
            if (restoreBtn) {
                const taskId = restoreBtn.dataset.taskId;
                const deliverId = restoreBtn.dataset.deliverId;
                const cacheKey = `task_cache_${taskId}_${deliverId}`;
                
                // Check if cache exists
                const cachedData = localStorage.getItem(cacheKey);
                
                if (cachedData) {
                    try {
                        const cache = JSON.parse(cachedData);
                        const cacheAge = Date.now() - cache.timestamp;
                        const maxAge = 7 * 24 * 60 * 60 * 1000; // 7 days
                        
                        if (cacheAge < maxAge) {
                            // Show restore button
                            restoreBtn.style.display = 'inline-block';
                            
                            // Add timestamp info
                            const cacheDate = new Date(cache.timestamp);
                            restoreBtn.title = `Saved work from: ${cacheDate.toLocaleString()}`;
                        } else {
                            // Cache too old, remove it
                            localStorage.removeItem(cacheKey);
                        }
                    } catch (e) {
                        console.error('Error parsing cache:', e);
                        localStorage.removeItem(cacheKey);
                    }
                }
                
                // Handle restore click
                restoreBtn.addEventListener('click', function() {
                    const href = startWorkingBtn.href;
                    // Redirect with restore flag
                    window.location.href = href + '&restore_cache=1';
                });
            }
        });
    </script>
</body>

</html>
