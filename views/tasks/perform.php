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

// ---- Dữ liệu hiển thị ----
$phaseCode = $phase['phase_code'];
[$phaseLabel, $phaseIcon] = phaseTypeMeta($phaseCode, (int)$phase['phase_number']);
$isTeacher = ($_SESSION['user']['role'] ?? '') === 'teacher';

$deliverStatus = 0; $dueTs = null;
if ($deliverId > 0) {
    $st = $pdo->prepare("SELECT d.status, COALESCE(d.deadline, t.deadline, p.end_time) AS due
                         FROM deliver d JOIN task t ON t.task_id = d.task_id JOIN phase p ON p.phase_id = t.phase_id
                         WHERE d.id_deliver = ?");
    $st->execute([$deliverId]);
    if ($row = $st->fetch(PDO::FETCH_ASSOC)) {
        $deliverStatus = (int)$row['status'];
        $dueTs = $row['due'] ? strtotime($row['due']) : null;
    }
}

$q = fn(array $a) => http_build_query($a);
$work = ['task_id' => $dataTaskId, 'topic_id' => $topicId, 'phase_id' => $dataPhaseId, 'deliver_id' => $deliverId];
$self = ['task_id' => $taskId, 'topic_id' => $topicId, 'phase_id' => $phaseId];
switch ($phaseCode) {
    case 'PHASE_CHUABAI':
    case 'PHASE_TAKENOTE':
        $href = '/class/homework/comment?' . $q($work);
        break;
    case 'PHASE_BTVN':
        $href = '/class/homework/analysis_chat?' . $q($work);
        break;
    case 'PHASE_DANHGIANGUOIHOC':
        $href = ($isTeacher ? '/class/task/setting_evaluation?' : '/class/evaluate/list?') . $q($self);
        break;
    case 'PHASE_CHAMDIEM':
        $href = $isTeacher ? URL_ROOT . '/class/evaluate/teacher_grades?' . $q($self)
                           : URL_ROOT . '/class/task?' . $q(['topic_id' => $topicId, 'phase_id' => $phaseId]);
        break;
    case 'PHASE_START':
    default:
        $href = '/class/homework/analysis_start?' . $q($work);
}

// Giải thích ngắn: ở bước này học sinh sẽ làm gì
$howTo = [
    'PHASE_START' => ['Đọc văn bản được giao (hoặc dán văn bản của bạn).', 'Bấm “Phân tích” để AI gợi ý bài phân tích.', 'Đọc kỹ, sửa những chỗ chưa đúng và tự phản biện bằng bình luận.', 'Chốt phiên bản tốt nhất làm bài nộp.'],
    'PHASE_BTVN' => ['Dán văn bản cần phân tích.', 'Nhờ AI phân tích và hỏi thêm khi cần.', 'Sửa, thêm hoặc kéo sắp xếp lại các ý.', 'Lưu bài rồi sang phần bình luận.'],
    'PHASE_CHUABAI' => ['Mở lại bài đã làm ở giai đoạn trước.', 'Đọc bình luận của thầy cô và bạn bè.', 'Chữa bài và trả lời các bình luận.'],
    'PHASE_TAKENOTE' => ['Mở bài làm.', 'Ghi chú những kiến thức rút ra ngay trên bài.'],
    'PHASE_DANHGIANGUOIHOC' => ['Mở danh sách bài được phân công đánh giá.', 'Chấm từng tiêu chí và viết nhận xét cụ thể.', 'Gửi đánh giá trước hạn.'],
    'PHASE_CHAMDIEM' => ['Thầy cô chấm điểm bài của bạn.', 'Theo dõi kết quả và nhận xét tại danh sách nhiệm vụ.'],
][$phaseCode] ?? [];

if ($phaseCode === 'PHASE_CHAMDIEM' && !$isTeacher) {
    $cta = ['Xem danh sách nhiệm vụ', 'bi-list-check'];
} elseif ($phaseCode === 'PHASE_DANHGIANGUOIHOC') {
    $cta = [$isTeacher ? 'Thiết lập đánh giá' : 'Bắt đầu đánh giá', 'bi-people'];
} elseif ($deliverStatus >= 2) {
    $cta = ['Xem bài đã nộp', 'bi-eye'];
} elseif ($deliverStatus === 1 || $hasSubmissionResponse) {
    $cta = ['Tiếp tục làm bài', 'bi-play-fill'];
} else {
    $cta = ['Bắt đầu làm bài', 'bi-play-fill'];
}

$statusBadge = $deliverStatus >= 2 ? ['Đã nộp', 'success', 'bi-check2-circle']
    : ($deliverStatus === 1 ? ['Đang làm', 'primary', 'bi-pencil'] : ['Chưa làm', 'warning', 'bi-circle']);
$dueText = null; $dueTone = '';
if ($dueTs) {
    $left = $dueTs - time();
    $dueText = $left < 0 ? 'Đã quá hạn (' . date('H:i d/m/Y', $dueTs) . ')'
        : 'Hạn nộp ' . date('H:i d/m/Y', $dueTs) . ' · còn ' . ($left >= 86400 ? (int)floor($left / 86400) . ' ngày' : max(1, (int)ceil($left / 3600)) . ' giờ');
    $dueTone = $left < 0 ? 'danger' : ($left < 86400 ? 'warning' : '');
}
$taskListUrl = URL_ROOT . '/class/task?' . htmlspecialchars($q(['topic_id' => $topicId, 'phase_id' => $phaseId]));
?>
<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php require_once __DIR__ . '/../templates/assets.php'; ?>
    <title><?= htmlspecialchars($task['title']) ?> · Làm bài</title>
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
                    <li class="breadcrumb-item"><a href="<?= URL_ROOT ?>/class"><?= htmlspecialchars($_SESSION['current_class_name'] ?? 'Lớp học', ENT_QUOTES, 'UTF-8') ?></a></li>
                    <li class="breadcrumb-item"><a href="<?= URL_ROOT ?>/class/phases?topic_id=<?= (int)$topicId ?>"><?= htmlspecialchars($topic['topic_name']) ?></a></li>
                    <li class="breadcrumb-item"><a href="<?= $taskListUrl ?>">Giai đoạn <?= (int)$phase['phase_number'] ?>: <?= htmlspecialchars($phase['phase_name']) ?></a></li>
                    <li class="breadcrumb-item active" aria-current="page"><?= htmlspecialchars($task['title']) ?></li>
                </ol>
            </nav>
            <h1><?= htmlspecialchars($task['title']) ?></h1>
        </div>

        <section class="section">
            <div class="row g-4">
                <div class="col-lg-8">
                    <div class="ui-card p-4">
                        <div class="d-flex flex-wrap gap-2 mb-3">
                            <span class="ui-badge ui-badge--accent"><i class="bi <?= htmlspecialchars($phaseIcon) ?>" aria-hidden="true"></i> <?= htmlspecialchars($phaseLabel) ?></span>
                            <?php if (!$isTeacher && $deliverId > 0): ?>
                                <span class="ui-badge ui-badge--<?= $statusBadge[1] ?>"><i class="bi <?= $statusBadge[2] ?>" aria-hidden="true"></i> <?= $statusBadge[0] ?></span>
                            <?php endif; ?>
                            <?php if ($dueText): ?>
                                <span class="ui-badge<?= $dueTone ? ' ui-badge--' . $dueTone : '' ?>"><i class="bi bi-calendar-event" aria-hidden="true"></i> <?= htmlspecialchars($dueText) ?></span>
                            <?php endif; ?>
                        </div>

                        <?php if (!empty($task['description'])): ?>
                            <h2 class="perform-label">Đề bài</h2>
                            <p class="perform-desc"><?= nl2br(htmlspecialchars($task['description'])) ?></p>
                        <?php endif; ?>

                        <?php if ($howTo): ?>
                            <h2 class="perform-label">Bạn sẽ làm gì</h2>
                            <ol class="perform-howto">
                                <?php foreach ($howTo as $line): ?><li><?= htmlspecialchars($line) ?></li><?php endforeach; ?>
                            </ol>
                        <?php endif; ?>

                        <div class="d-flex gap-2 flex-wrap">
                            <a class="btn btn-primary btn-lg px-4" href="<?= htmlspecialchars($href) ?>" id="startWorkingBtn">
                                <i class="bi <?= $cta[1] ?>" aria-hidden="true"></i> <?= $cta[0] ?>
                            </a>
                            <button type="button" class="btn btn-outline-primary btn-lg" id="restoreCacheBtn" hidden data-task-id="<?= (int)$dataTaskId ?>" data-deliver-id="<?= (int)$deliverId ?>">
                                <i class="bi bi-clock-history" aria-hidden="true"></i> <span>Khôi phục bản nháp</span>
                            </button>
                            <a href="<?= $taskListUrl ?>" class="btn btn-link btn-lg text-decoration-none">
                                <i class="bi bi-arrow-left" aria-hidden="true"></i> Danh sách nhiệm vụ
                            </a>
                        </div>
                        <?php if ($shouldLookBack): ?>
                            <p class="small text-muted mt-3 mb-0"><i class="bi bi-info-circle" aria-hidden="true"></i> Giai đoạn này làm tiếp trên bài của bạn ở giai đoạn trước.</p>
                        <?php endif; ?>
                    </div>

                    <?php if ($taskContent !== false && !empty($taskContent)): ?>
                        <div class="ui-card p-4 mt-4">
                            <div class="d-flex justify-content-between align-items-center gap-2 mb-3">
                                <h2 class="h5 m-0"><i class="bi bi-book" aria-hidden="true"></i> Văn bản</h2>
                                <?php if ($task['content_creator']): ?>
                                    <small class="text-muted">Người soạn: <?= htmlspecialchars($task['content_creator_name']) ?></small>
                                <?php endif; ?>
                            </div>
                            <div class="reading-text perform-text"><?= nl2br(htmlspecialchars($taskContent)) ?></div>
                        </div>
                    <?php endif; ?>
                </div>

                <aside class="col-lg-4" aria-label="Thông tin nhiệm vụ">
                    <div class="ui-card p-4">
                        <h2 class="perform-label">Thông tin</h2>
                        <dl class="perform-info">
                            <dt>Chủ đề</dt><dd><?= htmlspecialchars($topic['topic_name']) ?></dd>
                            <dt>Giai đoạn</dt><dd><?= (int)$phase['phase_number'] ?>. <?= htmlspecialchars($phase['phase_name']) ?></dd>
                            <dt>Giáo viên giao</dt><dd><?= htmlspecialchars($task['added_by_name'] ?? '—') ?></dd>
                            <dt>Ngày giao</dt><dd><?= date('d/m/Y H:i', strtotime($task['created_at'])) ?></dd>
                        </dl>
                        <?php if ($phase['AI'] || $phase['Comment'] || $phase['Edit']): ?>
                            <h2 class="perform-label mt-4">Trong giai đoạn này</h2>
                            <div class="d-flex flex-wrap gap-2">
                                <?php if ($phase['AI']): ?><span class="ui-badge ui-badge--primary"><i class="bi bi-robot" aria-hidden="true"></i> Có AI hỗ trợ</span><?php endif; ?>
                                <?php if ($phase['Comment']): ?><span class="ui-badge ui-badge--success"><i class="bi bi-chat-dots" aria-hidden="true"></i> Được bình luận</span><?php endif; ?>
                                <?php if ($phase['Edit']): ?><span class="ui-badge ui-badge--warning"><i class="bi bi-pencil-square" aria-hidden="true"></i> Được sửa bài</span><?php endif; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </aside>
            </div>
        </section>
    </main>

    <?php
    require_once __DIR__ . '/../templates/footer.php';
    require_once __DIR__ . '/../templates/vendors.php';
    ?>

    <script>
        // Hiện nút "Khôi phục bản nháp" nếu trình duyệt còn bản lưu tạm trong 7 ngày
        document.addEventListener('DOMContentLoaded', function () {
            const restoreBtn = document.getElementById('restoreCacheBtn');
            const startBtn = document.getElementById('startWorkingBtn');
            if (!restoreBtn || !startBtn) return;
            const key = `task_cache_${restoreBtn.dataset.taskId}_${restoreBtn.dataset.deliverId}`;
            let cache = null;
            try { cache = JSON.parse(localStorage.getItem(key) || 'null'); } catch (e) { try { localStorage.removeItem(key); } catch (_) {} }
            if (!cache || !cache.timestamp) return;
            if (Date.now() - cache.timestamp > 7 * 24 * 60 * 60 * 1000) {
                try { localStorage.removeItem(key); } catch (_) {}
                return;
            }
            const when = new Date(cache.timestamp).toLocaleString('vi-VN', { hour: '2-digit', minute: '2-digit', day: '2-digit', month: '2-digit' });
            restoreBtn.querySelector('span').textContent = 'Khôi phục bản nháp (' + when + ')';
            restoreBtn.hidden = false;
            restoreBtn.addEventListener('click', function () {
                window.location.href = startBtn.href + (startBtn.href.includes('?') ? '&' : '?') + 'restore_cache=1';
            });
        });
    </script>
</body>

</html>
