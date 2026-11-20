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
$gradesUrl = URL_ROOT . '/class/evaluate/teacher_grades?' . http_build_query(['task_id' => $taskId, 'topic_id' => $topicId, 'phase_id' => $phaseId]);
$scale = $sMax ?: array_sum(array_map(fn($c) => (float)$c['max_score'], $criteria));
$pct = fn($v) => $scale > 0 ? max(0, min(100, round($v * 100 / $scale, 1))) : 0;
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php require_once __DIR__ . '/../templates/assets.php'; ?>
    <link rel="stylesheet" href="<?= URL_ROOT ?>/assets/css/research-figures.css">
    <title>Chấm điểm · <?= htmlspecialchars($responseInfo['response_author']) ?></title>
</head>
<body>
<?php require_once __DIR__ . '/../templates/header.php'; require_once __DIR__ . '/../templates/sidebar.php'; ?>
<main id="main" class="main">
    <div class="pagetitle">
        <nav aria-label="Vị trí trang">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="<?= breadcrumbClassUrl() ?>"><?= breadcrumbClassLabel() ?></a></li>
                <?php if ($topic): ?><li class="breadcrumb-item"><a href="<?= breadcrumbTopicPhasesUrl($topicId) ?>"><?= htmlspecialchars($topic['topic_name']) ?></a></li><?php endif; ?>
                <li class="breadcrumb-item"><a href="<?= htmlspecialchars($gradesUrl) ?>">Chấm điểm cuối</a></li>
                <li class="breadcrumb-item active" aria-current="page"><?= htmlspecialchars($responseInfo['response_author']) ?></li>
            </ol>
        </nav>
        <h1>Chấm điểm: <?= htmlspecialchars($responseInfo['response_author']) ?></h1>
    </div>

    <section class="section">
        <!-- Tổng quan điểm: bạn bè, giáo viên, hệ thống -->
        <section class="ui-card p-4 mb-4" id="fig18-instructor-detail" aria-label="Tổng quan điểm">
            <div class="grade-metrics">
                <div class="grade-metric">
                    <div class="grade-metric__value"><?= $peerAvg !== null ? number_format($peerAvg, 1) : '—' ?></div>
                    <div class="grade-metric__label">TB bạn chấm <span class="text-muted">(S<sub>Peer</sub>)</span></div>
                </div>
                <div class="grade-metric">
                    <div class="grade-metric__value"><?= $teacherScore !== null ? number_format($teacherScore, 1) : '—' ?></div>
                    <div class="grade-metric__label">Điểm giáo viên <span class="text-muted">(S<sub>Teacher</sub>)</span></div>
                </div>
                <div class="grade-metric">
                    <div class="grade-metric__value"><?= $deltaPeers !== null ? number_format($deltaPeers, 1) : '—' ?></div>
                    <div class="grade-metric__label">Chênh lệch <span class="text-muted">(Δ)</span></div>
                </div>
                <div class="grade-metric">
                    <div class="grade-metric__value"><?= is_array($systemEval) && $systemEval['outline_score'] !== null ? (int)$systemEval['outline_score'] . '%' : '—' ?></div>
                    <div class="grade-metric__label">Dàn ý đạt chuẩn (hệ thống)</div>
                </div>
                <div class="grade-metric">
                    <div class="grade-metric__value"><?= is_array($systemEval) && $systemEval['avg_edit_change_percent'] !== null ? number_format((float)$systemEval['avg_edit_change_percent'], 1) . '%' : '—' ?></div>
                    <div class="grade-metric__label">Mức tự sửa bài</div>
                </div>
            </div>

            <?php if ($peerEvals && $scale > 0): ?>
                <!-- Mức đồng thuận: vị trí từng điểm trên thang điểm -->
                <div class="consensus-scale mt-4" aria-label="Phân bố điểm trên thang 0 – <?= htmlspecialchars((string)$scale) ?>">
                    <div class="consensus-scale__track">
                        <?php foreach ($peerEvals as $pe): $v = (float)$pe['total_score']; ?>
                            <span class="consensus-scale__dot" style="left: <?= $pct($v) ?>%" title="<?= htmlspecialchars($pe['evaluator_name']) ?>: <?= number_format($v, 1) ?>"></span>
                        <?php endforeach; ?>
                        <?php if ($peerAvg !== null): ?>
                            <span class="consensus-scale__mark consensus-scale__mark--peer" style="left: <?= $pct($peerAvg) ?>%"><span>TB bạn <?= number_format($peerAvg, 1) ?></span></span>
                        <?php endif; ?>
                        <?php if ($teacherScore !== null): ?>
                            <span class="consensus-scale__mark consensus-scale__mark--teacher" style="left: <?= $pct($teacherScore) ?>%"><span>GV <?= number_format($teacherScore, 1) ?></span></span>
                        <?php endif; ?>
                    </div>
                    <div class="consensus-scale__axis"><span>0</span><span><?= htmlspecialchars((string)$scale) ?></span></div>
                    <p class="small text-muted mb-0 mt-1">Mỗi chấm tròn là điểm của một bạn. Các chấm càng gần nhau, các bạn càng đồng thuận.</p>
                </div>
            <?php endif; ?>
        </section>

        <div class="row g-4">
            <div class="col-lg-6">
                <article class="ui-card p-4 mb-4" aria-label="Bài làm">
                    <div class="d-flex justify-content-between align-items-baseline gap-2 mb-2">
                        <h2 class="perform-label m-0">Bài làm</h2>
                        <span class="small text-muted">Nộp lúc <?= date('H:i d/m/Y', strtotime($responseInfo['response_created_at'])) ?></span>
                    </div>
                    <?php if (!empty($responseInfo['contents'])): ?>
                        <?php foreach ($responseInfo['contents'] as $content): ?>
                            <div class="submission-body"><?= renderSubmissionMarkdown($content['content']) ?></div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p class="text-muted mb-0">Bài này chưa có nội dung.</p>
                    <?php endif; ?>
                </article>

                <div class="ui-card p-4 mb-4">
                    <h2 class="perform-label">Bạn bè đã chấm (<?= count($peerEvals) ?>)</h2>
                    <?php if (empty($peerEvals)): ?>
                        <p class="text-muted small mb-0">Chưa có bạn nào chấm bài này.</p>
                    <?php else: ?>
                        <ul class="list-unstyled m-0">
                        <?php foreach ($peerEvals as $i => $pe):
                            $sAcc = ($pe['accuracy_score'] !== null && $pe['accuracy_score'] !== '') ? (float)$pe['accuracy_score'] : null;
                            $delta = ($teacherScore !== null) ? abs((float)$pe['total_score'] - $teacherScore) : null;
                        ?>
                            <li class="d-flex justify-content-between align-items-center gap-2 py-2<?= $i ? ' border-top' : '' ?>">
                                <span><?= htmlspecialchars($pe['evaluator_name']) ?>
                                    <?php if ($delta !== null): ?><span class="small text-muted d-block">Lệch so với GV: <?= number_format($delta, 1) ?></span><?php endif; ?>
                                </span>
                                <span class="text-end text-nowrap">
                                    <strong><?= number_format((float)$pe['total_score'], 1) ?></strong>
                                    <?php if ($sAcc !== null): ?><span class="ui-badge ui-badge--primary ms-1" title="Độ chính xác khi chấm (S_Acc)">Chính xác <?= number_format($sAcc, 1) ?></span><?php endif; ?>
                                </span>
                            </li>
                        <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </div>

                <?php if (is_array($systemEval)): ?>
                    <div class="ui-card p-4">
                        <h2 class="perform-label">Dữ liệu hệ thống ghi nhận</h2>
                        <dl class="perform-info">
                            <?php if ($systemEval['outline_score'] !== null): ?><dt>Kiểm tra dàn ý</dt><dd><strong><?= (int)$systemEval['outline_score'] ?>%</strong><?= !empty($systemEval['outline_passed']) ? ' (đạt)' : '' ?></dd><?php endif; ?>
                            <?php if ($systemEval['avg_edit_change_percent'] !== null): ?><dt>Mức tự sửa bài</dt><dd><?= number_format((float)$systemEval['avg_edit_change_percent'], 1) ?>%</dd><?php endif; ?>
                            <?php if ($systemEval['sections_edited'] !== null): ?><dt>Số phần đã sửa</dt><dd><?= (int)$systemEval['sections_edited'] ?></dd><?php endif; ?>
                            <?php if ($systemEval['total_copy_percent'] !== null): ?><dt>Tỉ lệ sao chép</dt><dd><?= number_format((float)$systemEval['total_copy_percent'], 1) ?>%</dd><?php endif; ?>
                            <?php if ($systemEval['session_duration_secs'] !== null): $sec = (int)$systemEval['session_duration_secs']; ?><dt>Thời gian làm bài</dt><dd><?= $sec >= 60 ? floor($sec / 60) . ' phút ' . ($sec % 60) . ' giây' : $sec . ' giây' ?></dd><?php endif; ?>
                        </dl>
                    </div>
                <?php endif; ?>
            </div>

            <div class="col-lg-6">
                <div class="ui-card p-4 eval-sticky">
                    <h2 class="h5 mb-1">Điểm chính thức của giáo viên</h2>
                    <p class="small text-muted mb-3">Điểm này là điểm cuối cùng học sinh nhận được.</p>
                    <?php
                    $rf = [
                        'hidden' => ['deliver_id' => $deliverId, 'task_id' => $taskId],
                        'submit_text' => 'Chốt điểm',
                        'done_text' => 'Đã chốt điểm cho bài này.',
                        'confirm' => ['title' => 'Chốt điểm?', 'message' => 'Sau khi chốt, học sinh sẽ thấy điểm này.'],
                        'redirect' => $gradesUrl,
                    ];
                    require __DIR__ . '/_rubric_form.php';
                    ?>
                </div>
            </div>
        </div>
    </section>
</main>
<?php require_once __DIR__ . '/../templates/footer.php'; require_once __DIR__ . '/../templates/vendors.php'; ?>
</body>
</html>
