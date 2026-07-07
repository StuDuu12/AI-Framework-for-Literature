<?php
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../config/helper_evaluate.php';
require_once __DIR__ . '/../../config/helper_phase.php';
require_once __DIR__ . '/../../config/helper_class.php';
require_once __DIR__ . '/../../config/helper_breadcrumb.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$taskId = (int)($_GET['task_id'] ?? 0);
$topicId = (int)($_GET['topic_id'] ?? 0);
$phaseId = (int)($_GET['phase_id'] ?? 0);
$teacherId = (int)($_SESSION['user']['user_id'] ?? 0);
$role = $_SESSION['user']['role'] ?? '';

if (!$taskId || $role !== 'teacher') {
    header('Location: ' . URL_ROOT . '/403');
    exit;
}

$taskInfo = getTaskInfo($taskId);
if (!$taskInfo || !isTeacherInTopicClass($topicId, $teacherId)) {
    header('Location: ' . URL_ROOT . '/403');
    exit;
}
$phase = getPhaseDetails($phaseId);
$topic = getTopicDetails($topicId);
$submissions = getGradingSubmissions($taskId);
$dashboardSummary = getInstructorReviewDashboardSummary($submissions);
$sMax = getMaxScoreForTask($taskId);

function renderDeltaBadge(?float $delta): string
{
    if ($delta === null) {
        return '<span class="text-muted">—</span>';
    }
    $class = 'research-figure__delta--low';
    if ($delta > 2) {
        $class = 'research-figure__delta--high';
    } elseif ($delta > 0.5) {
        $class = 'research-figure__delta--mid';
    }
    return '<span class="research-figure__delta ' . $class . '">Δ ' . number_format($delta, 1) . '</span>';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php require_once __DIR__ . '/../templates/assets.php'; ?>
    <link rel="stylesheet" href="<?= URL_ROOT ?>/assets/css/research-figures.css">
    <title>Final Grading - Literature System</title>
</head>
<body>
<?php
require_once __DIR__ . '/../templates/header.php';
require_once __DIR__ . '/../templates/sidebar.php';
?>
<main id="main" class="main">
    <div class="pagetitle">
        <h1><i class="bi bi-award"></i> Final Grading</h1>
        <nav>
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="<?= breadcrumbClassUrl() ?>"><?= breadcrumbClassLabel() ?></a></li>
                <?php if ($topic): ?>
                <li class="breadcrumb-item"><a href="<?= breadcrumbClassUrl() ?>"><?= htmlspecialchars($topic['topic_name']) ?></a></li>
                <?php endif; ?>
                <li class="breadcrumb-item"><a href="<?= breadcrumbTopicPhasesUrl($topicId) ?>">Learning Phases</a></li>
                <li class="breadcrumb-item"><a href="<?= breadcrumbPhaseTasksUrl($topicId, $phaseId) ?>">Tasks<?= $phase ? ' - Step ' . (int)$phase['phase_number'] : '' ?></a></li>
                <li class="breadcrumb-item active">Final Grading</li>
            </ol>
        </nav>
    </div>

    <section class="section">
        <div class="card">
            <div class="card-body">
                <h5 class="card-title"><?= htmlspecialchars($taskInfo['title'] ?? 'Grading task') ?></h5>

                <section class="research-figure research-figure--instructor" id="fig18-instructor-dashboard" aria-label="Instructor review dashboard">
                    <div class="research-figure__body">
                        <div class="research-figure__metrics">
                            <div class="research-figure__metric">
                                <div class="research-figure__metric-value"><?= (int)$dashboardSummary['submission_count'] ?></div>
                                <div class="research-figure__metric-label">Submissions</div>
                            </div>
                            <div class="research-figure__metric">
                                <div class="research-figure__metric-value"><?= (int)$dashboardSummary['peer_review_count'] ?></div>
                                <div class="research-figure__metric-label">Peer reviews</div>
                            </div>
                            <div class="research-figure__metric">
                                <div class="research-figure__metric-value"><?= $dashboardSummary['peer_avg_score'] !== null ? number_format($dashboardSummary['peer_avg_score'], 1) : '—' ?></div>
                                <div class="research-figure__metric-label">Avg peer score (S<sub>Peer</sub>)</div>
                            </div>
                            <div class="research-figure__metric">
                                <div class="research-figure__metric-value"><?= $dashboardSummary['peer_accuracy_avg'] !== null ? number_format($dashboardSummary['peer_accuracy_avg'], 1) : '—' ?></div>
                                <div class="research-figure__metric-label">Avg accuracy (S<sub>Acc</sub>)</div>
                            </div>
                            <div class="research-figure__metric">
                                <div class="research-figure__metric-value"><?= $dashboardSummary['system_outline_avg'] !== null ? number_format($dashboardSummary['system_outline_avg'], 0) . '%' : '—' ?></div>
                                <div class="research-figure__metric-label">System outline validation</div>
                            </div>
                            <div class="research-figure__metric">
                                <div class="research-figure__metric-value"><?= $dashboardSummary['system_edit_avg'] !== null ? number_format($dashboardSummary['system_edit_avg'], 1) . '%' : '—' ?></div>
                                <div class="research-figure__metric-label">Avg revision change</div>
                            </div>
                            <div class="research-figure__metric">
                                <div class="research-figure__metric-value"><?= (int)$dashboardSummary['teacher_graded_count'] ?>/<?= (int)$dashboardSummary['submission_count'] ?></div>
                                <div class="research-figure__metric-label">Teacher grades (S<sub>Teacher</sub>)</div>
                            </div>
                        </div>

                        <?php if (empty($submissions)): ?>
                            <div class="alert alert-info mb-0">No submitted work found for this grading task.</div>
                        <?php else: ?>
                            <div class="research-figure__table-wrap">
                                <table class="research-figure__table">
                                    <thead>
                                        <tr>
                                            <th>Submitter</th>
                                            <th>System evaluation</th>
                                            <th>Peer evaluations</th>
                                            <th>S<sub>Teacher</sub></th>
                                            <th>Δ vs peers</th>
                                            <th></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                    <?php foreach ($submissions as $row):
                                        $responseId = (int)($row['response'] ?? 0);
                                        if (!$responseId) continue;
                                        $peers = $row['peer_evaluations'] ?? [];
                                        $peerAvg = $row['peer_avg'] ?? null;
                                        $teacherEval = $row['teacher_evaluation'] ?? null;
                                        $teacherScore = ($teacherEval && (int)$teacherEval['status'] === 2)
                                            ? (float)$teacherEval['total_score'] : null;
                                        $delta = ($teacherScore !== null && $peerAvg !== null)
                                            ? abs($teacherScore - $peerAvg) : null;
                                        $system = $row['system_evaluation'] ?? [];
                                    ?>
                                        <tr>
                                            <td><strong><?= htmlspecialchars($row['submitter_name'] ?? 'Unknown') ?></strong></td>
                                            <td>
                                                <?php if (!empty($system)): ?>
                                                    <div class="small">
                                                        <?php if ($system['outline_score'] !== null): ?>
                                                            <div>Outline: <strong><?= (int)$system['outline_score'] ?>%</strong><?= $system['outline_passed'] ? ' ✓' : '' ?></div>
                                                        <?php endif; ?>
                                                        <?php if ($system['avg_edit_change_percent'] !== null): ?>
                                                            <div>Revision: <strong><?= number_format((float)$system['avg_edit_change_percent'], 1) ?>%</strong></div>
                                                        <?php endif; ?>
                                                        <?php if ($system['total_copy_percent'] !== null): ?>
                                                            <div>Copy: <?= number_format((float)$system['total_copy_percent'], 1) ?>%</div>
                                                        <?php endif; ?>
                                                        <?php if ($system['outline_score'] === null && $system['avg_edit_change_percent'] === null): ?>
                                                            <span class="text-muted">No system metrics</span>
                                                        <?php endif; ?>
                                                    </div>
                                                <?php else: ?>
                                                    <span class="text-muted">—</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if (empty($peers)): ?>
                                                    <span class="text-muted small">No peer reviews</span>
                                                <?php else: ?>
                                                    <ul class="research-figure__peer-list">
                                                    <?php foreach ($peers as $pe): ?>
                                                        <li>
                                                            <span><?= htmlspecialchars($pe['evaluator_name']) ?></span>
                                                            <span>
                                                                <strong><?= number_format((float)$pe['total_score'], 1) ?></strong>
                                                                <?php if ($pe['accuracy_score'] !== null && $pe['accuracy_score'] !== ''): ?>
                                                                    <span class="text-muted">(S<sub>Acc</sub> <?= number_format((float)$pe['accuracy_score'], 1) ?>)</span>
                                                                <?php endif; ?>
                                                            </span>
                                                        </li>
                                                    <?php endforeach; ?>
                                                    </ul>
                                                    <?php if ($peerAvg !== null): ?>
                                                        <div class="small text-muted mt-1">Avg: <strong><?= number_format($peerAvg, 1) ?></strong></div>
                                                    <?php endif; ?>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if ($teacherScore !== null): ?>
                                                    <span class="badge bg-success"><?= number_format($teacherScore, 1) ?></span>
                                                <?php else: ?>
                                                    <span class="badge bg-warning text-dark">Pending</span>
                                                <?php endif; ?>
                                            </td>
                                            <td><?= renderDeltaBadge($delta) ?></td>
                                            <td class="text-end">
                                                <a class="btn btn-sm btn-primary" href="<?= URL_ROOT ?>/class/evaluate/teacher_grade?task_id=<?= $taskId ?>&topic_id=<?= $topicId ?>&phase_id=<?= $phaseId ?>&response_id=<?= $responseId ?>&deliver_id=<?= (int)$row['id_deliver'] ?>">
                                                    Review
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                            <p class="text-muted small mt-2 mb-0">S<sub>Acc</sub> = max(0, S<sub>Max</sub> − α·Δ) where Δ = |S<sub>Peer</sub> − S<sub>Teacher</sub>| and S<sub>Max</sub> = <?= number_format((float)$sMax, 1) ?> for this task.</p>
                        <?php endif; ?>
                    </div>
                </section>
            </div>
        </div>
    </section>
</main>
<?php require_once __DIR__ . '/../templates/footer.php'; require_once __DIR__ . '/../templates/vendors.php'; ?>
</body>
</html>
