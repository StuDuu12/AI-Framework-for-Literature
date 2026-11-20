<?php
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../config/helper_evaluate.php';
require_once __DIR__ . '/../../config/helper_phase.php';
require_once __DIR__ . '/../../config/helper_class.php';
require_once __DIR__ . '/../../config/helper_breadcrumb.php';
require_once __DIR__ . '/../../config/helper_criteria_i18n.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$teacherId = (int)($_SESSION['user']['user_id'] ?? 0);
$role = $_SESSION['user']['role'] ?? '';
$taskId = (int)($_GET['task_id'] ?? 0);
$topicId = (int)($_GET['topic_id'] ?? 0);
$phaseId = (int)($_GET['phase_id'] ?? 0);
$responseId = (int)($_GET['response_id'] ?? 0);
$deliverId = (int)($_GET['deliver_id'] ?? 0);

if (!$taskId || !$responseId || $role !== 'teacher') {
    header('Location: ' . URL_ROOT . '/403');
    exit;
}

require_once __DIR__ . '/../../config/helper_task.php';

$taskInfo = getTaskInfo($taskId);
requireIdsInTopic($topicId, $taskId, $phaseId);
if (!$taskInfo || !isTeacherInTopicClass($topicId, $teacherId)) {
    header('Location: ' . URL_ROOT . '/403');
    exit;
}

if (!$deliverId) {
    $deliverId = (int)(resolveSubmissionDeliverForGrading($taskId, $responseId) ?? 0);
}

if (!$deliverId || !isResponseInGradingTask($taskId, $responseId)) {
    header('Location: ' . URL_ROOT . '/404');
    exit;
}

$phase = getPhaseDetails($phaseId);
$topic = getTopicDetails($topicId);
$responseInfo = getResponseForGrading($responseId);
if (!$responseInfo) {
    header('Location: ' . URL_ROOT . '/404');
    exit;
}

$evaluation = getOrCreateTeacherEvaluation($teacherId, $responseId, $deliverId, $taskId);
$criteria = getEvaluationCriteria($taskId);
$peerEvals = getPeerEvaluationsForResponse($responseId);
$systemEval = getSystemEvaluationForResponse($responseId);
$sMax = getMaxScoreForTask($taskId);
$peerAvg = count($peerEvals)
    ? round(array_sum(array_column($peerEvals, 'total_score')) / count($peerEvals), 2)
    : null;
$teacherScore = ((int)($evaluation['status'] ?? 0) === 2)
    ? (float)$evaluation['total_score'] : null;
$deltaPeers = ($teacherScore !== null && $peerAvg !== null)
    ? abs($teacherScore - $peerAvg) : null;

$existingScores = [];
if (!empty($evaluation['details'])) {
    foreach ($evaluation['details'] as $detail) {
        $existingScores[$detail['tc_id']] = $detail['diem'];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php require_once __DIR__ . '/../templates/assets.php'; ?>
    <link rel="stylesheet" href="<?= URL_ROOT ?>/assets/css/research-figures.css">
    <title>Teacher Final Grade</title>
</head>
<body>
<?php require_once __DIR__ . '/../templates/header.php'; require_once __DIR__ . '/../templates/sidebar.php'; ?>
<main id="main" class="main">
    <div class="pagetitle">
        <h1><i class="bi bi-award"></i> Teacher Final Grade</h1>
        <nav>
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="<?= breadcrumbClassUrl() ?>"><?= breadcrumbClassLabel() ?></a></li>
                <?php if ($topic): ?><li class="breadcrumb-item"><a href="<?= breadcrumbClassUrl() ?>"><?= htmlspecialchars($topic['topic_name']) ?></a></li><?php endif; ?>
                <li class="breadcrumb-item"><a href="<?= breadcrumbTopicPhasesUrl($topicId) ?>">Learning Phases</a></li>
                <li class="breadcrumb-item"><a href="<?= URL_ROOT ?>/class/evaluate/teacher_grades?task_id=<?= $taskId ?>&topic_id=<?= $topicId ?>&phase_id=<?= $phaseId ?>">Final Grading</a></li>
                <li class="breadcrumb-item active">Grade submission</li>
            </ol>
        </nav>
    </div>

    <section class="section">
        <section class="research-figure research-figure--instructor mb-3" id="fig18-instructor-detail" aria-label="Instructor review detail">
            <div class="research-figure__body">
                <div class="research-figure__metrics">
                    <div class="research-figure__metric">
                        <div class="research-figure__metric-value"><?= $peerAvg !== null ? number_format($peerAvg, 1) : '—' ?></div>
                        <div class="research-figure__metric-label">Peer avg (S<sub>Peer</sub>)</div>
                    </div>
                    <div class="research-figure__metric">
                        <div class="research-figure__metric-value"><?= $teacherScore !== null ? number_format($teacherScore, 1) : '—' ?></div>
                        <div class="research-figure__metric-label">Teacher (S<sub>Teacher</sub>)</div>
                    </div>
                    <div class="research-figure__metric">
                        <div class="research-figure__metric-value"><?= $deltaPeers !== null ? number_format($deltaPeers, 1) : '—' ?></div>
                        <div class="research-figure__metric-label">Δ |S<sub>Peer</sub> − S<sub>Teacher</sub>|</div>
                    </div>
                    <div class="research-figure__metric">
                        <div class="research-figure__metric-value"><?= is_array($systemEval) && $systemEval['outline_score'] !== null ? (int)$systemEval['outline_score'] . '%' : '—' ?></div>
                        <div class="research-figure__metric-label">System outline score</div>
                    </div>
                    <div class="research-figure__metric">
                        <div class="research-figure__metric-value"><?= is_array($systemEval) && $systemEval['avg_edit_change_percent'] !== null ? number_format((float)$systemEval['avg_edit_change_percent'], 1) . '%' : '—' ?></div>
                        <div class="research-figure__metric-label">System revision change</div>
                    </div>
                </div>
            </div>
        </section>

        <div class="row g-3">
            <div class="col-lg-5">
                <div class="card mb-3">
                    <div class="card-body">
                        <h6 class="card-title">Submission</h6>
                        <p class="mb-1"><strong>Author:</strong> <?= htmlspecialchars($responseInfo['response_author']) ?></p>
                        <p class="mb-0 text-muted small"><?= date('d/m/Y H:i', strtotime($responseInfo['response_created_at'])) ?></p>
                        <?php foreach ($responseInfo['contents'] ?? [] as $content): ?>
                            <div class="response-content markdown-content mt-2 small"><?= nl2br(htmlspecialchars($content['content'])) ?></div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <div class="card">
                    <div class="card-body">
                        <h6 class="card-title">Peer evaluations (S<sub>Peer</sub> &amp; S<sub>Acc</sub>)</h6>
                        <?php if (empty($peerEvals)): ?>
                            <p class="text-muted small mb-0">No peer evaluations yet.</p>
                        <?php else: ?>
                            <ul class="list-group list-group-flush">
                            <?php foreach ($peerEvals as $pe):
                                $sAcc = ($pe['accuracy_score'] !== null && $pe['accuracy_score'] !== '')
                                    ? (float)$pe['accuracy_score'] : null;
                                $delta = ($teacherScore !== null)
                                    ? abs((float)$pe['total_score'] - $teacherScore) : null;
                            ?>
                                <li class="list-group-item px-0">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <span><?= htmlspecialchars($pe['evaluator_name']) ?></span>
                                        <span class="text-end">
                                            <strong><?= number_format((float)$pe['total_score'], 1) ?></strong>
                                            <?php if ($sAcc !== null): ?>
                                                <span class="badge bg-info text-dark ms-1">S<sub>Acc</sub> <?= number_format($sAcc, 1) ?></span>
                                            <?php endif; ?>
                                        </span>
                                    </div>
                                    <?php if ($delta !== null): ?>
                                        <div class="small text-muted">Δ vs teacher: <?= number_format($delta, 1) ?></div>
                                    <?php endif; ?>
                                </li>
                            <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>
                    </div>
                </div>
                <?php if (is_array($systemEval)): ?>
                <div class="card mt-3">
                    <div class="card-body">
                        <h6 class="card-title">System evaluation</h6>
                        <ul class="list-unstyled small mb-0">
                            <?php if ($systemEval['outline_score'] !== null): ?>
                                <li>Outline validation: <strong><?= (int)$systemEval['outline_score'] ?>%</strong><?= !empty($systemEval['outline_passed']) ? ' (passed)' : '' ?></li>
                            <?php endif; ?>
                            <?php if ($systemEval['avg_edit_change_percent'] !== null): ?>
                                <li>Revision change: <strong><?= number_format((float)$systemEval['avg_edit_change_percent'], 1) ?>%</strong></li>
                            <?php endif; ?>
                            <?php if ($systemEval['sections_edited'] !== null): ?>
                                <li>Sections edited: <?= (int)$systemEval['sections_edited'] ?></li>
                            <?php endif; ?>
                            <?php if ($systemEval['total_copy_percent'] !== null): ?>
                                <li>Copy ratio: <?= number_format((float)$systemEval['total_copy_percent'], 1) ?>%</li>
                            <?php endif; ?>
                            <?php if ($systemEval['session_duration_secs'] !== null): ?>
                                <li>Session duration: <?= (int)$systemEval['session_duration_secs'] ?>s</li>
                            <?php endif; ?>
                        </ul>
                    </div>
                </div>
                <?php endif; ?>
            </div>
            <div class="col-lg-7">
                <div class="card">
                    <div class="card-body eval-compact">
                        <h6 class="card-title">Official teacher score</h6>
                        <form id="evaluationForm" method="POST" action="/api/evaluate/submit_evaluation.php">
                            <input type="hidden" name="evaluate_id" value="<?= $evaluation['id_evaluate'] ?>">
                            <input type="hidden" name="deliver_id" value="<?= $deliverId ?>">
                            <input type="hidden" name="task_id" value="<?= $taskId ?>">
                            <div class="accordion eval-criteria-accordion" id="criteriaAccordion">
                            <?php foreach ($criteria as $i => $criterion):
                                $tcId = $criterion['tc_id'];
                                $cur = $existingScores[$tcId] ?? $criterion['min_score'];
                                $min = (float)$criterion['min_score'];
                                $max = (float)$criterion['max_score'];
                                $step = ($max - $min) <= 4 ? 1 : 0.5;
                            ?>
                                <div class="accordion-item">
                                    <h2 class="accordion-header">
                                        <button class="accordion-button <?= $i > 0 ? 'collapsed' : '' ?>" type="button" data-bs-toggle="collapse" data-bs-target="#c<?= $tcId ?>">
                                            <?= htmlspecialchars(criteriaLabel($criterion['name_tc'])) ?>
                                            <span class="ms-auto me-2 badge bg-primary criterion-score-badge" data-tc="<?= $tcId ?>"><?= $cur ?></span>
                                        </button>
                                    </h2>
                                    <div id="c<?= $tcId ?>" class="accordion-collapse collapse <?= $i === 0 ? 'show' : '' ?>" data-bs-parent="#criteriaAccordion">
                                        <div class="accordion-body">
                                            <?php if ($criterion['description']): ?><p class="small text-muted"><?= htmlspecialchars(criteriaLabel($criterion['description'])) ?></p><?php endif; ?>
                                            <div class="score-chip-group" data-tc="<?= $tcId ?>">
                                            <?php for ($s = $min; $s <= $max + 0.001; $s += $step): ?>
                                                <button type="button" class="score-chip <?= abs($cur - $s) < 0.01 ? 'active' : '' ?>" data-value="<?= $s ?>"><?= $step >= 1 ? (int)$s : number_format($s, 1) ?></button>
                                            <?php endfor; ?>
                                            </div>
                                            <input type="hidden" name="scores[<?= $tcId ?>]" class="score-input" data-tc="<?= $tcId ?>" value="<?= $cur ?>" <?= (int)$evaluation['status'] === 2 ? 'readonly' : '' ?>>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                            </div>
                            <div class="total-score-display mt-3">
                                <div>Total</div>
                                <div id="totalScore"><?= $evaluation['total_score'] ?></div>
                            </div>
                            <?php if ((int)$evaluation['status'] !== 2): ?>
                            <div class="d-flex gap-2 justify-content-end mt-3">
                                <button type="submit" name="action" value="draft" class="btn btn-secondary btn-sm">Save draft</button>
                                <button type="submit" name="action" value="submit" class="btn btn-primary btn-sm">Submit final grade</button>
                            </div>
                            <?php else: ?>
                            <div class="alert alert-success mt-3 mb-0"><i class="bi bi-check-circle"></i> Final grade submitted.</div>
                            <?php endif; ?>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </section>
</main>
<?php require_once __DIR__ . '/../templates/footer.php'; require_once __DIR__ . '/../templates/vendors.php'; ?>
<script>
document.querySelectorAll('.score-chip-group').forEach(group => {
    const tc = group.dataset.tc;
    const input = document.querySelector('.score-input[data-tc="' + tc + '"]');
    const badge = document.querySelector('.criterion-score-badge[data-tc="' + tc + '"]');
    group.querySelectorAll('.score-chip').forEach(chip => {
        chip.addEventListener('click', () => {
            if (!input || input.readOnly) return;
            group.querySelectorAll('.score-chip').forEach(c => c.classList.remove('active'));
            chip.classList.add('active');
            input.value = chip.dataset.value;
            if (badge) badge.textContent = chip.dataset.value;
            recalcTotal();
        });
    });
});
function recalcTotal() {
    let t = 0;
    document.querySelectorAll('.score-input').forEach(i => { t += parseFloat(i.value) || 0; });
    const el = document.getElementById('totalScore');
    if (el) el.textContent = t.toFixed(1);
}
const form = document.getElementById('evaluationForm');
if (form) form.addEventListener('submit', async e => {
    e.preventDefault();
    const fd = new FormData(form);
    fd.append('action', e.submitter.value);
    const res = await fetch(form.action, { method: 'POST', body: fd });
    const data = await res.json();
    if (data.success) { if (window.Toast) Toast.success(data.message); else alert(data.message); location.reload(); }
    else { if (window.Toast) Toast.error(data.message); else alert(data.message); }
});
</script>
</body>
</html>
