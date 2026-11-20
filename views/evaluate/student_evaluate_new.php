<?php
// All PHP logic MUST be before any HTML output
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../config/helper_evaluate.php';
require_once __DIR__ . '/../../config/helper_breadcrumb.php';
require_once __DIR__ . '/../../config/helper_phase.php';
require_once __DIR__ . '/../../config/helper_task.php';
require_once __DIR__ . '/../../config/helper_criteria_i18n.php';
require_once __DIR__ . '/../../config/helper_class.php';

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
$deliverId = isset($_GET['deliver_id']) ? intval($_GET['deliver_id']) : 0;

if (!$taskId || !$deliverId) {
    $_SESSION['error_message'] = 'Đường dẫn không hợp lệ.';
    header("Location: /404");
    exit;
}

// STEP 1: Check if user has access to this deliver
$deliverInfo = checkDeliverAccess($userId, $deliverId);
if (!$deliverInfo) {
    $_SESSION['error_message'] = 'Bạn không được phân công chấm bài này.';
    header("Location: /dashboard");
    exit;
}

// Deliver theo nhóm → chuyển sang luồng Cổng Đồng thuận (GĐ 4)
if ($deliverInfo['type'] === 'group') {
    header("Location: /class/evaluate/consensus?task_id={$taskId}&topic_id={$topicId}&phase_id={$phaseId}&deliver_id={$deliverId}");
    exit;
}

// Get task info
$taskInfo = getTaskInfo($taskId);
if (!$taskInfo) {
    $_SESSION['error_message'] = 'Không tìm thấy nhiệm vụ.';
    header("Location: /dashboard");
    exit;
}

// Check anonymous mode
$stmtAnon = $pdo->prepare("SELECT is_anonymous FROM setting WHERE task_id = :task_id");
$stmtAnon->execute([':task_id' => $taskId]);
$anonRow = $stmtAnon->fetch(PDO::FETCH_ASSOC);
$isAnonymous = $anonRow ? (int)$anonRow['is_anonymous'] : 0;

// Get response to evaluate
$responseInfo = getResponseToEvaluate($deliverId);
if (!$responseInfo) {
    $_SESSION['error_message'] = 'Không tìm thấy bài cần chấm.';
    header("Location: /dashboard");
    exit;
}

// STEP 2: Get or create evaluation record for this user
$evaluation = getOrCreateEvaluation($userId, $deliverId, $responseInfo['id_response']);
if (!$evaluation) {
    $_SESSION['error_message'] = 'Không mở được phiếu chấm. Vui lòng thử lại.';
    header("Location: /dashboard");
    exit;
}

// Get criteria
$criteria = getEvaluationCriteria($taskId);
$phaseInfo = getPhaseDetails($phaseId);
$topicInfo = getTopicDetails($topicId);
if (empty($criteria)) {
    $_SESSION['error_message'] = 'Giáo viên chưa đặt tiêu chí chấm cho nhiệm vụ này.';
    header("Location: /dashboard");
    exit;
}

// Build scores array from evaluation details
$existingScores = [];
if (!empty($evaluation['details'])) {
    foreach ($evaluation['details'] as $detail) {
        $existingScores[$detail['tc_id']] = $detail['diem'];
    }
}

// Now start HTML output
$evalStatus = (int)$evaluation['status'];
$statusBadge = $evalStatus === 2 ? ['Đã hoàn thành', 'success', 'bi-check2-circle']
    : ($evalStatus === 1 ? ['Đang chấm', 'primary', 'bi-hourglass-split'] : ['Bản nháp', 'warning', 'bi-pencil']);
$listUrl = URL_ROOT . '/class/evaluate/list?' . http_build_query(['task_id' => $taskId, 'topic_id' => $topicId, 'phase_id' => $phaseId]);
?>
<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php require_once __DIR__ . '/../templates/assets.php'; ?>
    <title>Đánh giá chéo · <?= htmlspecialchars($taskInfo['title']) ?></title>
</head>

<body>
    <?php
    require_once __DIR__ . '/../templates/header.php';
    require_once __DIR__ . '/../templates/sidebar.php';
    ?>

    <main id="main" class="main">
        <div class="pagetitle">
            <nav aria-label="Vị trí trang">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="<?= breadcrumbClassUrl() ?>"><?= breadcrumbClassLabel() ?></a></li>
                    <?php if ($topicInfo): ?>
                        <li class="breadcrumb-item"><a href="<?= breadcrumbTopicPhasesUrl($topicId) ?>"><?= htmlspecialchars($topicInfo['topic_name']) ?></a></li>
                    <?php endif; ?>
                    <li class="breadcrumb-item"><a href="<?= breadcrumbPhaseTasksUrl($topicId, $phaseId) ?>">Giai đoạn <?= $phaseInfo ? (int)$phaseInfo['phase_number'] : '' ?></a></li>
                    <li class="breadcrumb-item"><a href="<?= htmlspecialchars($listUrl) ?>">Đánh giá chéo</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Chấm bài</li>
                </ol>
            </nav>
            <h1>Đánh giá chéo: <?= htmlspecialchars($taskInfo['title']) ?></h1>
        </div>

        <section class="section">
            <div class="ui-card p-3 mb-4 d-flex flex-wrap align-items-center gap-2">
                <span class="ui-badge ui-badge--<?= $statusBadge[1] ?>"><i class="bi <?= $statusBadge[2] ?>" aria-hidden="true"></i> <?= $statusBadge[0] ?></span>
                <span class="ui-badge"><i class="bi <?= $deliverInfo['type'] === 'user' ? 'bi-person' : 'bi-people' ?>" aria-hidden="true"></i> <?= $deliverInfo['type'] === 'user' ? 'Bài cá nhân' : 'Bài nhóm' ?></span>
                <span class="small text-muted ms-1">
                    Người làm: <?= $isAnonymous ? '<em>ẩn danh</em>' : '<strong>' . htmlspecialchars($responseInfo['response_author']) . '</strong>' ?>
                    · Nộp lúc <?= date('H:i d/m/Y', strtotime($responseInfo['response_created_at'])) ?>
                </span>
                <a class="btn btn-sm btn-link ms-auto text-decoration-none" href="<?= htmlspecialchars($listUrl) ?>"><i class="bi bi-arrow-left" aria-hidden="true"></i> Danh sách bài cần chấm</a>
            </div>

            <div class="row g-4">
                <div class="col-lg-7">
                    <article class="ui-card p-4" aria-label="Bài cần chấm">
                        <h2 class="perform-label">Bài cần chấm</h2>
                        <?php if (!empty($responseInfo['contents'])): ?>
                            <?php foreach ($responseInfo['contents'] as $content): ?>
                                <div class="submission-body"><?= renderSubmissionMarkdown($content['content']) ?></div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="ui-empty ui-empty--compact"><p class="mb-0">Bài này chưa có nội dung.</p></div>
                        <?php endif; ?>
                    </article>
                </div>

                <div class="col-lg-5">
                    <div class="ui-card p-4 eval-sticky">
                        <h2 class="h5 mb-1">Chấm theo tiêu chí</h2>
                        <p class="small text-muted mb-3">Đọc kỹ bài bên trái, chọn mức điểm phù hợp cho từng tiêu chí.</p>
                        <?php
                        $rf = [
                            'hidden' => ['deliver_id' => $deliverId, 'task_id' => $taskId, 'topic_id' => $topicId, 'phase_id' => $phaseId],
                            'submit_text' => 'Hoàn thành đánh giá',
                            'done_text' => 'Bạn đã hoàn thành đánh giá này.',
                            'confirm' => ['title' => 'Hoàn thành đánh giá?', 'message' => 'Sau khi hoàn thành, bạn sẽ không sửa được nữa.'],
                            'redirect' => $listUrl,
                        ];
                        require __DIR__ . '/_rubric_form.php';
                        ?>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <?php
    require_once __DIR__ . '/../templates/footer.php';
    require_once __DIR__ . '/../templates/vendors.php';
    ?>
</body>

</html>
