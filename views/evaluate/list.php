<?php
/**
 * TRANG DANH SÁCH ĐÁNH GIÁ CHÉO
 * 
 * Mục đích: Hiển thị tất cả các bài mà học sinh được phân công evaluation
 * 
 * Tính năng:
 * - Xem danh sách tất cả bài được giao chấm
 * - Hiển thị trạng thái: Not Graded / Draft / Grading / Completed
 * - Hiển thị points số (nếu đã hoàn thành)
 * - Progress bar thể hiện tiến độ hoàn thành
 * - Button "Start" or "Continue" evaluation
 * 
 * URL: /class/evaluate/list?task_id=X&topic_id=Y&phase_id=Z
 */
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../config/helper_evaluate.php';
require_once __DIR__ . '/../../config/helper_homework.php';
require_once __DIR__ . '/../../config/helper_breadcrumb.php';
require_once __DIR__ . '/../../config/helper_criteria_i18n.php';
require_once __DIR__ . '/../../config/helper_phase.php';
require_once __DIR__ . '/../../config/helper_task.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in
if (!isset($_SESSION['user']['user_id'])) {
    header("Location: /login");
    exit;
}

$userId = $_SESSION['user']['user_id'];
$taskId = isset($_GET['task_id']) ? intval($_GET['task_id']) : 0;
$topicId = isset($_GET['topic_id']) ? intval($_GET['topic_id']) : 0;
$phaseId = isset($_GET['phase_id']) ? intval($_GET['phase_id']) : 0;

if (!$taskId) {
    header("Location: /404");
    exit;
}

// Get task info
$taskInfo = getInfoTask($taskId);
if (!$taskInfo) {
    header("Location: /404");
    exit;
}

// Check anonymous mode
$stmtAnon = $pdo->prepare("SELECT is_anonymous FROM setting WHERE task_id = :task_id");
$stmtAnon->execute([':task_id' => $taskId]);
$anonRow = $stmtAnon->fetch(PDO::FETCH_ASSOC);
$isAnonymous = $anonRow ? (int)$anonRow['is_anonymous'] : 0;

// Get assigned evaluations
$assignments = getAssignedEvaluations($userId, $taskId);
$criteriaList = getEvaluationCriteria($taskId);
$phaseInfo = $phaseId ? getPhaseDetails($phaseId) : null;
$topicInfo = $topicId ? getTopicDetails($topicId) : null;
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php require_once './views/templates/assets.php'; ?>
    <title data-lang-key="evaluate_list">Evaluation List</title>
</head>

<body>
    <?php
    require_once './views/templates/header.php';
    require_once './views/templates/sidebar.php';
    ?>

    <main id="main" class="main">
        <div class="pagetitle">
            <h1><i class="bi bi-list-check"></i> <span data-lang-key="evaluate_list">Evaluation Assignment List</span></h1>
            <nav>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="<?= breadcrumbClassUrl() ?>"><?= breadcrumbClassLabel() ?></a></li>
                    <?php if ($topicInfo): ?><li class="breadcrumb-item"><a href="<?= breadcrumbClassUrl() ?>"><?= htmlspecialchars($topicInfo['topic_name']) ?></a></li><?php endif; ?>
                    <li class="breadcrumb-item"><a href="<?= breadcrumbTopicPhasesUrl($topicId) ?>">Learning Phases</a></li>
                    <li class="breadcrumb-item"><a href="<?= breadcrumbPhaseTasksUrl($topicId, $phaseId) ?>">Tasks<?= $phaseInfo ? ' - Step ' . (int)$phaseInfo['phase_number'] : '' ?></a></li>
                    <li class="breadcrumb-item active">Peer Evaluation</li>
                </ol>
            </nav>
        </div>

        <section class="section">
            <!-- Task Info -->
            <div class="card mb-4">
                <div class="card-body">
                    <h5 class="card-title"><i class="bi bi-info-circle"></i> <span data-lang-key="task_info">Task Info</span></h5>
                    <div class="row">
                        <div class="col-md-8">
                            <p><strong>Task Name:</strong> <?= htmlspecialchars($taskInfo['task_title']) ?></p>
                            <p><strong>Description:</strong>
                                <?= htmlspecialchars($taskInfo['task_description'] ?? 'No description') ?></p>
                            <?php if (!empty($criteriaList)): ?>
                                <div class="rubric-preview mt-2">
                                    <small class="text-muted d-block mb-1"><i class="bi bi-list-check"></i> <span>Evaluation criteria:</span></small>
                                    <?php foreach ($criteriaList as $c): ?>
                                        <span class="rubric-chip" title="<?= htmlspecialchars(criteriaLabel($c['description'] ?? '')) ?>">
                                            <?= htmlspecialchars(criteriaLabel($c['name_tc'])) ?>
                                            (<?= $c['min_score'] ?>–<?= $c['max_score'] ?>)
                                        </span>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="col-md-4 text-end">
                            <?php
                            $totalAssigned = count($assignments);
                            $completed = 0;
                            foreach ($assignments as $assignment) {
                                if ($assignment['eval_status'] == 2) {
                                    $completed++;
                                }
                            }
                            $percentage = $totalAssigned > 0 ? round(($completed / $totalAssigned) * 100) : 0;
                            ?>
                            <div class="d-flex align-items-center justify-content-end gap-3">
                                <div>
                                    <div class="progress-text text-primary"><?= $completed ?> / <?= $totalAssigned ?>
                                    </div>
                                    <small class="text-muted">Completed</small>
                                </div>
                                <div class="progress-ring">
                                    <svg width="60" height="60">
                                        <circle cx="30" cy="30" r="25" fill="none" stroke="#e0e0e0" stroke-width="5">
                                        </circle>
                                        <circle cx="30" cy="30" r="25" fill="none" stroke="#4154f1" stroke-width="5"
                                            stroke-dasharray="<?= $percentage * 1.57 ?> 157" stroke-dashoffset="0"
                                            transform="rotate(-90 30 30)"></circle>
                                        <text x="30" y="35" text-anchor="middle" font-size="12" font-weight="bold"
                                            fill="#4154f1">
                                            <?= $percentage ?>%
                                        </text>
                                    </svg>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Assignments List -->
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title"><i class="bi bi-clipboard-check"></i> <span data-lang-key="evaluate_assignments">Assignments to Evaluate</span></h5>

                    <?php if (empty($assignments)): ?>
                        <div class="alert alert-info">
                            <i class="bi bi-info-circle"></i> <span>You have not been assigned any evaluations yet.</span>
                        </div>
                    <?php else: ?>
                        <?php foreach ($assignments as $assignment): ?>
                            <div class="assignment-card">
                                <div class="row align-items-center">
                                    <div class="col-md-6">
                                        <h6 class="mb-2">
                                            <i class="bi bi-person-circle"></i>
                                            <?= $isAnonymous ? '<span class="text-muted fst-italic">Ẩn danh</span>' : htmlspecialchars($assignment['response_author']) ?>
                                        </h6>
                                        <p class="text-muted mb-1 small">
                                            <i class="bi bi-file-text"></i>
                                            <?= htmlspecialchars(mb_substr($assignment['response_content'] ?? 'No content', 0, 100)) ?>...
                                        </p>
                                        <small class="text-muted">
                                            <i class="bi bi-clock"></i>
                                            <span>Submitted at:</span> <?= date('d/m/Y H:i', strtotime($assignment['created_at'] ?? 'now')) ?>
                                        </small>
                                    </div>

                                    <div class="col-md-3 text-center">
                                        <?php if ($assignment['eval_status'] !== null): ?>
                                            <span class="status-badge status-<?= $assignment['eval_status'] ?>">
                                                <?php if ($assignment['eval_status'] == 0): ?>
                                                    <i class="bi bi-circle"></i> <span>Draft</span>
                                                <?php elseif ($assignment['eval_status'] == 1): ?>
                                                    <i class="bi bi-hourglass-split"></i> <span>Grading</span>
                                                <?php else: ?>
                                                    <i class="bi bi-check-circle"></i> <span>Completed</span>
                                                <?php endif; ?>
                                            </span>
                                            <?php if ($assignment['eval_status'] == 2): ?>
                                                <div class="score-display mt-2">
                                                    <i class="bi bi-star-fill"></i>
                                                    <span title="S_Peer - your assigned score">
                                                        <?= number_format($assignment['total_score'], 1) ?>
                                                    </span>
                                                </div>
                                                <?php if ($assignment['accuracy_score'] !== null): ?>
                                                    <?php
                                                        $sPerf  = (float)$assignment['total_score'];
                                                        $sAcc   = (float)$assignment['accuracy_score'];
                                                        $sFinal = $sPerf + $sAcc;
                                                    ?>
                                                    <div class="mt-1 small text-muted">
                                                        <i class="bi bi-bullseye"></i>
                                                        S<sub>Acc</sub> = <strong class="text-success"><?= number_format($sAcc, 1) ?></strong>
                                                    </div>
                                                    <div class="mt-1 small fw-bold text-primary">
                                                        <i class="bi bi-trophy-fill"></i>
                                                        Score<sub>Final</sub> = <?= number_format($sFinal, 1) ?>
                                                    </div>
                                                <?php else: ?>
                                                    <div class="mt-1 small text-warning">
                                                        <i class="bi bi-hourglass"></i>
                                                        <em>Waiting for teacher grading to calculate S<sub>Acc</sub></em>
                                                    </div>
                                                <?php endif; ?>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            <span class="status-badge status-0">
                                                <i class="bi bi-exclamation-circle"></i> <span>Not Graded</span>
                                            </span>
                                        <?php endif; ?>
                                    </div>

                                    <div class="col-md-3 text-end">
                                        <?php if ($assignment['eval_status'] == 2): ?>
                                            <a href="/class/evaluate/student?task_id=<?= $taskId ?>&topic_id=<?= $topicId ?>&phase_id=<?= $phaseId ?>&deliver_id=<?= $assignment['id_deliver'] ?>"
                                                class="btn btn-outline-primary">
                                                <i class="bi bi-eye"></i> <span>View Evaluation</span>
                                            </a>
                                        <?php else: ?>
                                            <a href="/class/evaluate/student?task_id=<?= $taskId ?>&topic_id=<?= $topicId ?>&phase_id=<?= $phaseId ?>&deliver_id=<?= $assignment['id_deliver'] ?>"
                                                class="btn btn-primary">
                                                <i class="bi bi-pencil-square"></i>
                                                <?= $assignment['eval_status'] !== null ? '<span>Continue</span>' : '<span>Start</span>' ?> <span>evaluation</span>
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </section>
    </main>

    <?php
    require_once 'views/templates/footer.php';
    require_once 'views/templates/vendors.php';
    ?>
</body>

</html>