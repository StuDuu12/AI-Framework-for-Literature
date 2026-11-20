<?php
/**
 * Trang "Hôm nay" — trang chủ của học sinh.
 * Trả lời 3 câu hỏi ngay khi đăng nhập: đang làm dở gì, sắp đến hạn gì, có phản hồi mới không.
 * Mọi link đều mang đủ ngữ cảnh (task/topic/phase/deliver) nên bấm là vào thẳng bài,
 * không cần đi qua Lớp → Chủ đề → Giai đoạn → Nhiệm vụ.
 */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../config/helper_phase.php';

$role = $_SESSION['user']['role'] ?? '';
if ($role !== 'student') {
    header('Location: ' . URL_ROOT . '/dashboard');
    exit;
}
$userId = (int)$_SESSION['user']['user_id'];
$fullname = (string)($_SESSION['user']['fullname'] ?? '');

// ---------- Tất cả bài được giao cho học sinh (mọi lớp) ----------
$items = [];
try {
    $stmt = $pdo->prepare("
        SELECT d.id_deliver, d.status, d.type AS deliver_type, d.updated_at AS deliver_updated, d.submitted_at,
               t.task_id, t.title,
               p.phase_id, p.phase_number, p.phase_name, p.start_time, p.end_time, p.flag_active,
               mp.phase_code,
               top.topic_id, top.topic_name,
               c.class_id, c.class_name,
               COALESCE(d.deadline, t.deadline, p.end_time) AS due_at
        FROM deliver d
        JOIN task t ON t.task_id = d.task_id
        JOIN phase p ON p.phase_id = t.phase_id
        JOIN topic top ON top.topic_id = p.topic_id
        JOIN class c ON c.class_id = top.class_id
        JOIN class_member cm ON cm.class_id = c.class_id AND cm.user_id = :u1
        LEFT JOIN main_phase mp ON mp.m_phase_id = p.m_phase_id
        WHERE (mp.student IS NULL OR mp.student = 1)
          AND (
                (d.type = 'user' AND d.value = :u2)
             OR (d.type = 'class' AND d.value = c.class_id)
             OR (d.type = 'group' AND EXISTS (SELECT 1 FROM group_member gm WHERE gm.group_id = d.value AND gm.user_id = :u3))
          )
        ORDER BY due_at ASC
    ");
    $stmt->execute([':u1' => $userId, ':u2' => $userId, ':u3' => $userId]);
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
    error_log('today.php items: ' . $e->getMessage());
}

$now = time();
$continue = $todo = $upcoming = [];
$stats = ['open' => 0, 'soon' => 0, 'overdue' => 0, 'done_week' => 0];
$classProgress = [];

foreach ($items as $it) {
    $start = strtotime($it['start_time']);
    $end = strtotime($it['end_time']);
    $due = strtotime($it['due_at']);
    $submitted = (int)$it['status'] >= 2;
    $open = !empty($it['flag_active']) || ($now >= $start && $now <= $end);

    $cid = (int)$it['class_id'];
    $classProgress[$cid] ??= ['name' => $it['class_name'], 'total' => 0, 'done' => 0];
    $classProgress[$cid]['total']++;
    if ($submitted) {
        $classProgress[$cid]['done']++;
        if ($it['submitted_at'] && strtotime($it['submitted_at']) >= $now - 7 * 86400) {
            $stats['done_week']++;
        }
        continue;
    }

    $it['_due'] = $due;
    $it['_overdue'] = $due < $now;
    $it['_soon'] = !$it['_overdue'] && $due - $now <= 3 * 86400;

    if ($open) {
        $stats['open']++;
        if ($it['_overdue']) $stats['overdue']++;
        elseif ($it['_soon']) $stats['soon']++;
        if ((int)$it['status'] === 1) $continue[] = $it;
        else $todo[] = $it;
    } elseif ($start > $now && $start - $now <= 14 * 86400) {
        $upcoming[] = $it;
    }
}
// "Tiếp tục làm": bài vừa đụng tới gần nhất lên đầu
usort($continue, fn($a, $b) => strcmp((string)$b['deliver_updated'], (string)$a['deliver_updated']));
usort($upcoming, fn($a, $b) => strtotime($a['start_time']) <=> strtotime($b['start_time']));

// ---------- Phản hồi mới trên bài của mình (14 ngày) ----------
$feedback = [];
try {
    $stmt = $pdo->prepare("
        SELECT cmt.comment_id, cmt.comment_text, cmt.created_at,
               u.fullname AS author, u.role AS author_role,
               d.id_deliver, t.task_id, t.title, p.phase_id, p.topic_id
        FROM comment cmt
        LEFT JOIN content ct ON ct.content_id = cmt.content_id
        JOIN response r ON r.id_response = COALESCE(NULLIF(cmt.reponse_general, 0), ct.response_id)
        JOIN deliver d ON d.response = r.id_response
        JOIN task t ON t.task_id = d.task_id
        JOIN phase p ON p.phase_id = t.phase_id
        JOIN users u ON u.user_id = cmt.user_id
        WHERE COALESCE(r.owner_user_id, r.user_id) = :u1
          AND cmt.user_id <> :u2
          AND cmt.created_at >= DATE_SUB(NOW(), INTERVAL 14 DAY)
        ORDER BY cmt.created_at DESC
        LIMIT 5
    ");
    $stmt->execute([':u1' => $userId, ':u2' => $userId]);
    $feedback = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
    error_log('today.php feedback: ' . $e->getMessage());
}

// ---------- Helpers hiển thị ----------
function todayTaskUrl(array $it): string
{
    return URL_ROOT . '/class/task/perform?' . http_build_query([
        'task_id' => (int)$it['task_id'],
        'topic_id' => (int)$it['topic_id'],
        'phase_id' => (int)$it['phase_id'],
        'deliver_id' => (int)$it['id_deliver'],
    ]);
}

function todayRelative(int $ts): string
{
    $diff = $ts - time();
    $abs = abs($diff);
    if ($abs < 3600) $txt = max(1, (int)round($abs / 60)) . ' phút';
    elseif ($abs < 86400) $txt = (int)round($abs / 3600) . ' giờ';
    else $txt = (int)round($abs / 86400) . ' ngày';
    return $diff >= 0 ? 'còn ' . $txt : 'quá hạn ' . $txt;
}

function todayDueBadge(array $it): string
{
    $label = htmlspecialchars(todayRelative($it['_due']));
    $title = 'Hạn: ' . date('H:i d/m/Y', $it['_due']);
    if ($it['_overdue']) return '<span class="ui-badge ui-badge--danger" title="' . $title . '"><i class="bi bi-exclamation-circle" aria-hidden="true"></i>' . $label . '</span>';
    if ($it['_soon']) return '<span class="ui-badge ui-badge--warning" title="' . $title . '"><i class="bi bi-clock" aria-hidden="true"></i>' . $label . '</span>';
    return '<span class="ui-badge" title="' . $title . '"><i class="bi bi-calendar3" aria-hidden="true"></i>' . date('d/m', $it['_due']) . '</span>';
}

function todayPhaseBadge(array $it): string
{
    [$label, $icon] = phaseTypeMeta($it['phase_code'] ?? null, (int)$it['phase_number']);
    return '<span class="ui-badge ui-badge--primary"><i class="bi ' . $icon . '" aria-hidden="true"></i>' . htmlspecialchars($label) . '</span>';
}

function todayCard(array $it, string $cta): void
{
    $cls = $it['_overdue'] ? ' ui-task--overdue' : ($it['_soon'] ? ' ui-task--soon' : '');
    ?>
    <a class="ui-task<?= $cls ?>" href="<?= htmlspecialchars(todayTaskUrl($it)) ?>">
        <div class="ui-task__meta">
            <?= todayPhaseBadge($it) ?>
            <?= todayDueBadge($it) ?>
        </div>
        <h3 class="ui-task__title"><?= htmlspecialchars($it['title']) ?></h3>
        <div class="ui-task__path" title="<?= htmlspecialchars($it['class_name'] . ' › ' . $it['topic_name']) ?>">
            <i class="bi bi-building" aria-hidden="true"></i> <?= htmlspecialchars($it['class_name']) ?> › <?= htmlspecialchars($it['topic_name']) ?>
        </div>
        <div class="ui-task__foot">
            <span class="ui-task__path"><?= $it['deliver_type'] === 'group' ? '<i class="bi bi-people" aria-hidden="true"></i> Bài nhóm' : '<i class="bi bi-person" aria-hidden="true"></i> Bài cá nhân' ?></span>
            <span class="ui-task__cta"><?= htmlspecialchars($cta) ?> <i class="bi bi-arrow-right" aria-hidden="true"></i></span>
        </div>
    </a>
    <?php
}

$hour = (int)date('G');
$greeting = $hour < 11 ? 'Chào buổi sáng' : ($hour < 14 ? 'Chào buổi trưa' : ($hour < 18 ? 'Chào buổi chiều' : 'Chào buổi tối'));
$firstName = trim((string)preg_replace('/^.*\s/u', '', $fullname)) ?: $fullname;
$weekdays = ['Chủ nhật', 'Thứ hai', 'Thứ ba', 'Thứ tư', 'Thứ năm', 'Thứ sáu', 'Thứ bảy'];
$todayLabel = $weekdays[(int)date('w')] . ', ' . date('d/m/Y');
?>
<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php require_once __DIR__ . '/../templates/assets.php'; ?>
    <title>Hôm nay · Literature System</title>
</head>

<body>
    <?php
    require_once __DIR__ . '/../templates/header.php';
    require_once __DIR__ . '/../templates/sidebar.php';
    ?>

    <main id="main" class="main" tabindex="-1">
        <section class="ui-hero" aria-labelledby="today-title">
            <div>
                <p class="mb-1 small"><?= htmlspecialchars($todayLabel) ?></p>
                <h1 id="today-title"><?= htmlspecialchars($greeting) ?>, <?= htmlspecialchars($firstName) ?>!</h1>
                <p>
                    <?php if ($stats['overdue'] > 0): ?>
                        Bạn có <strong><?= $stats['overdue'] ?></strong> bài đã quá hạn — hãy ưu tiên hoàn thành nhé.
                    <?php elseif ($stats['soon'] > 0): ?>
                        Có <strong><?= $stats['soon'] ?></strong> bài sắp đến hạn trong 3 ngày tới.
                    <?php elseif ($stats['open'] > 0): ?>
                        Bạn đang có <strong><?= $stats['open'] ?></strong> bài cần làm. Bắt đầu thôi!
                    <?php else: ?>
                        Bạn đã hoàn thành mọi việc được giao. Tuyệt vời!
                    <?php endif; ?>
                </p>
            </div>
            <div class="ui-hero__stats" role="list">
                <div class="ui-hero__stat" role="listitem"><strong><?= $stats['open'] ?></strong><span>Cần làm</span></div>
                <div class="ui-hero__stat" role="listitem"><strong><?= $stats['soon'] + $stats['overdue'] ?></strong><span>Gấp</span></div>
                <div class="ui-hero__stat" role="listitem"><strong><?= $stats['done_week'] ?></strong><span>Đã nộp (7 ngày)</span></div>
            </div>
        </section>

        <div class="row g-4">
            <div class="col-xl-8">

                <?php if ($continue): ?>
                    <section class="ui-section" aria-labelledby="sec-continue">
                        <div class="ui-section__head">
                            <h2 class="ui-section__title" id="sec-continue"><i class="bi bi-play-circle text-primary" aria-hidden="true"></i> Tiếp tục làm <span class="ui-count"><?= count($continue) ?></span></h2>
                        </div>
                        <div class="ui-task-grid">
                            <?php foreach ($continue as $it) todayCard($it, 'Làm tiếp'); ?>
                        </div>
                    </section>
                <?php endif; ?>

                <section class="ui-section" aria-labelledby="sec-todo">
                    <div class="ui-section__head">
                        <h2 class="ui-section__title" id="sec-todo"><i class="bi bi-list-check text-primary" aria-hidden="true"></i> Cần làm <span class="ui-count"><?= count($todo) ?></span></h2>
                    </div>
                    <?php if ($todo): ?>
                        <div class="ui-task-grid">
                            <?php foreach ($todo as $it) todayCard($it, 'Bắt đầu'); ?>
                        </div>
                    <?php elseif (!$continue): ?>
                        <div class="ui-empty">
                            <i class="bi bi-cup-hot" aria-hidden="true"></i>
                            <div class="ui-empty__title">Không có bài nào đang chờ bạn</div>
                            <p>Khi giáo viên giao nhiệm vụ mới, nhiệm vụ sẽ xuất hiện ở đây kèm hạn nộp.</p>
                            <a class="btn btn-outline-primary btn-sm" href="<?= URL_ROOT ?>/workspace"><i class="bi bi-book" aria-hidden="true"></i> Khám phá thư viện tác phẩm</a>
                        </div>
                    <?php else: ?>
                        <div class="ui-empty ui-empty--compact">
                            <div class="ui-empty__title">Không còn bài mới</div>
                            <p>Hãy hoàn thành các bài đang làm dở ở trên.</p>
                        </div>
                    <?php endif; ?>
                </section>

                <?php if ($upcoming): ?>
                    <section class="ui-section" aria-labelledby="sec-upcoming">
                        <div class="ui-section__head">
                            <h2 class="ui-section__title" id="sec-upcoming"><i class="bi bi-calendar-event text-primary" aria-hidden="true"></i> Sắp mở (14 ngày tới) <span class="ui-count"><?= count($upcoming) ?></span></h2>
                        </div>
                        <div class="ui-card">
                            <ul class="list-unstyled m-0">
                                <?php foreach ($upcoming as $i => $it): ?>
                                    <li class="d-flex align-items-center gap-3 px-3 py-2<?= $i ? ' border-top' : '' ?>">
                                        <span class="ui-badge"><i class="bi bi-unlock" aria-hidden="true"></i><?= date('H:i d/m', strtotime($it['start_time'])) ?></span>
                                        <div class="flex-grow-1 text-truncate">
                                            <div class="fw-semibold text-truncate"><?= htmlspecialchars($it['title']) ?></div>
                                            <div class="small text-muted text-truncate"><?= htmlspecialchars($it['class_name'] . ' › ' . $it['topic_name']) ?></div>
                                        </div>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    </section>
                <?php endif; ?>
            </div>

            <aside class="col-xl-4">
                <section class="ui-section" aria-labelledby="sec-feedback">
                    <div class="ui-section__head">
                        <h2 class="ui-section__title" id="sec-feedback"><i class="bi bi-chat-left-text text-primary" aria-hidden="true"></i> Phản hồi mới</h2>
                    </div>
                    <?php if ($feedback): ?>
                        <div class="ui-card">
                            <ul class="list-unstyled m-0">
                                <?php foreach ($feedback as $i => $f):
                                    $url = URL_ROOT . '/class/homework/comment?' . http_build_query([
                                        'task_id' => (int)$f['task_id'], 'topic_id' => (int)$f['topic_id'],
                                        'phase_id' => (int)$f['phase_id'], 'deliver_id' => (int)$f['id_deliver'],
                                    ]);
                                    $isTeacher = $f['author_role'] === 'teacher';
                                ?>
                                    <li class="px-3 py-3<?= $i ? ' border-top' : '' ?>">
                                        <a href="<?= htmlspecialchars($url) ?>" class="d-block text-reset">
                                            <div class="d-flex justify-content-between gap-2 small mb-1">
                                                <span class="fw-semibold">
                                                    <?= htmlspecialchars($f['author']) ?>
                                                    <?php if ($isTeacher): ?><span class="ui-badge ui-badge--accent ms-1">Giáo viên</span><?php endif; ?>
                                                </span>
                                                <span class="text-muted text-nowrap"><?= date('d/m H:i', strtotime($f['created_at'])) ?></span>
                                            </div>
                                            <div class="small text-secondary" style="display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden;">
                                                <?= htmlspecialchars(mb_strimwidth(strip_tags((string)$f['comment_text']), 0, 180, '…')) ?>
                                            </div>
                                            <div class="small text-muted mt-1 text-truncate"><i class="bi bi-file-text" aria-hidden="true"></i> <?= htmlspecialchars($f['title']) ?></div>
                                        </a>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php else: ?>
                        <div class="ui-empty ui-empty--compact">
                            <i class="bi bi-chat-square" aria-hidden="true"></i>
                            <p class="mb-0">Chưa có nhận xét mới trên bài của bạn trong 2 tuần qua.</p>
                        </div>
                    <?php endif; ?>
                </section>

                <section class="ui-section" aria-labelledby="sec-classes">
                    <div class="ui-section__head">
                        <h2 class="ui-section__title" id="sec-classes"><i class="bi bi-building text-primary" aria-hidden="true"></i> Lớp của tôi</h2>
                        <a href="<?= URL_ROOT ?>/dashboard" class="small">Tất cả lớp</a>
                    </div>
                    <?php if ($classProgress): ?>
                        <div class="ui-card">
                            <ul class="list-unstyled m-0">
                                <?php $i = 0; foreach ($classProgress as $cid => $cp):
                                    $pct = $cp['total'] ? (int)round($cp['done'] * 100 / $cp['total']) : 0; ?>
                                    <li class="px-3 py-3<?= $i++ ? ' border-top' : '' ?>">
                                        <a href="<?= URL_ROOT ?>/joinclass?id=<?= (int)$cid ?>" class="d-block text-reset">
                                            <div class="d-flex justify-content-between small mb-2">
                                                <span class="fw-semibold text-truncate"><?= htmlspecialchars($cp['name']) ?></span>
                                                <span class="text-muted text-nowrap"><?= $cp['done'] ?>/<?= $cp['total'] ?> bài</span>
                                            </div>
                                            <div class="ui-progress" role="progressbar" aria-label="Tiến độ lớp <?= htmlspecialchars($cp['name']) ?>" aria-valuenow="<?= $pct ?>" aria-valuemin="0" aria-valuemax="100"><span style="width: <?= $pct ?>%"></span></div>
                                        </a>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php else: ?>
                        <div class="ui-empty ui-empty--compact">
                            <i class="bi bi-door-open" aria-hidden="true"></i>
                            <p>Bạn chưa tham gia lớp nào.</p>
                            <a class="btn btn-primary btn-sm" href="<?= URL_ROOT ?>/dashboard"><i class="bi bi-plus-lg" aria-hidden="true"></i> Tham gia lớp bằng mã mời</a>
                        </div>
                    <?php endif; ?>
                </section>
            </aside>
        </div>
    </main>

    <?php
    require_once __DIR__ . '/../templates/footer.php';
    require_once __DIR__ . '/../templates/vendors.php';
    ?>
</body>

</html>
