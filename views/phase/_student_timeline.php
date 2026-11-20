<?php
/**
 * Timeline chủ đề cho học sinh: mọi giai đoạn trên một trang, nhiệm vụ nằm ngay trong giai đoạn.
 * Biến cần có: $topic, $topicId, $phases (getTopicPhases).
 * Học sinh bấm thẳng "Làm bài" thay vì đi Giai đoạn → Danh sách nhiệm vụ → Nhiệm vụ.
 */
$__uid = (int)($_SESSION['user']['user_id'] ?? 0);
$__tasksByPhase = getStudentTasksByPhase((int)$topicId, $__uid);
$__now = time();

$__total = 0;
$__done = 0;
foreach ($__tasksByPhase as $__list) {
    foreach ($__list as $__t) {
        $__total++;
        if ((int)$__t['status'] >= 2) $__done++;
    }
}
$__pct = $__total ? (int)round($__done * 100 / $__total) : 0;

$__statusLabel = [0 => ['Chưa làm', ''], 1 => ['Đang làm', 'ui-badge--warning'], 2 => ['Đã nộp', 'ui-badge--success']];
?>
<div class="ui-card p-3 p-md-4 mb-4">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
        <div>
            <div class="small text-muted mb-1"><i class="bi bi-journal-bookmark" aria-hidden="true"></i> Chủ đề</div>
            <h2 class="h4 mb-0"><?= htmlspecialchars($topic['topic_name']) ?></h2>
        </div>
        <div style="min-width: 220px;">
            <div class="d-flex justify-content-between small mb-1">
                <span class="text-muted">Tiến độ của bạn</span>
                <strong><?= $__done ?>/<?= $__total ?> nhiệm vụ</strong>
            </div>
            <div class="ui-progress" role="progressbar" aria-label="Tiến độ chủ đề" aria-valuenow="<?= $__pct ?>" aria-valuemin="0" aria-valuemax="100"><span style="width: <?= $__pct ?>%"></span></div>
        </div>
    </div>
</div>

<?php if (empty($phases)): ?>
    <div class="ui-empty">
        <i class="bi bi-hourglass" aria-hidden="true"></i>
        <div class="ui-empty__title">Chủ đề chưa có giai đoạn nào</div>
        <p>Giáo viên sẽ mở các giai đoạn học tập khi đến lúc. Bạn sẽ thấy nhiệm vụ ở trang <a href="<?= URL_ROOT ?>/today">Hôm nay</a>.</p>
    </div>
<?php else: ?>
    <ol class="ui-timeline">
        <?php foreach ($phases as $__p):
            $__start = strtotime($__p['start_time']);
            $__end = strtotime($__p['end_time']);
            $__open = !empty($__p['flag_active']) || ($__now >= $__start && $__now <= $__end);
            $__ended = !$__open && $__now > $__end;
            $__tasks = $__tasksByPhase[(int)$__p['phase_id']] ?? [];
            $__allDone = $__tasks && count(array_filter($__tasks, fn($t) => (int)$t['status'] >= 2)) === count($__tasks);
            $__state = $__open ? 'is-open' : ($__ended || $__allDone ? 'is-done' : 'is-locked');
            [$__typeLabel, $__typeIcon] = phaseTypeMeta($__p['phase_code'] ?? null, (int)$__p['phase_number']);
        ?>
            <li class="ui-timeline__item <?= $__state ?>">
                <span class="ui-timeline__dot" aria-hidden="true">
                    <?php if ($__state === 'is-done'): ?><i class="bi bi-check-lg"></i>
                    <?php elseif ($__state === 'is-locked'): ?><i class="bi bi-lock"></i>
                    <?php else: ?><?= (int)$__p['phase_number'] ?><?php endif; ?>
                </span>
                <div class="ui-card p-3">
                    <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-2">
                        <div>
                            <div class="d-flex flex-wrap align-items-center gap-2 mb-1">
                                <span class="ui-badge ui-badge--primary"><i class="bi <?= $__typeIcon ?>" aria-hidden="true"></i><?= htmlspecialchars($__typeLabel) ?></span>
                                <?php if ($__open): ?>
                                    <span class="ui-badge ui-badge--success"><i class="bi bi-broadcast" aria-hidden="true"></i>Đang mở</span>
                                <?php elseif ($__state === 'is-locked'): ?>
                                    <span class="ui-badge"><i class="bi bi-clock" aria-hidden="true"></i>Mở lúc <?= date('H:i d/m', $__start) ?></span>
                                <?php else: ?>
                                    <span class="ui-badge">Đã kết thúc</span>
                                <?php endif; ?>
                            </div>
                            <h3 class="h6 mb-0 fw-bold">Giai đoạn <?= (int)$__p['phase_number'] ?>: <?= htmlspecialchars($__p['phase_name']) ?></h3>
                        </div>
                        <div class="small text-muted text-nowrap">
                            <i class="bi bi-calendar3" aria-hidden="true"></i>
                            <?= date('d/m H:i', $__start) ?> → <?= date('d/m H:i', $__end) ?>
                        </div>
                    </div>

                    <?php if ($__tasks): ?>
                        <ul class="list-unstyled mb-0 mt-3">
                            <?php foreach ($__tasks as $__i => $__t):
                                [$__sl, $__sc] = $__statusLabel[min(2, (int)$__t['status'])];
                                $__due = strtotime($__t['due_at']);
                                $__url = URL_ROOT . '/class/task/perform?' . http_build_query([
                                    'task_id' => (int)$__t['task_id'], 'topic_id' => (int)$topicId,
                                    'phase_id' => (int)$__p['phase_id'], 'deliver_id' => (int)$__t['id_deliver'],
                                ]);
                            ?>
                                <li class="d-flex flex-wrap align-items-center gap-2 py-2<?= $__i ? ' border-top' : '' ?>">
                                    <i class="bi <?= $__t['deliver_type'] === 'group' ? 'bi-people' : 'bi-file-earmark-text' ?> text-muted" aria-hidden="true"></i>
                                    <span class="flex-grow-1 fw-semibold"><?= htmlspecialchars($__t['title']) ?></span>
                                    <span class="ui-badge <?= $__sc ?>"><?= $__sl ?></span>
                                    <?php if ((int)$__t['status'] < 2 && $__open && $__due < $__now): ?>
                                        <span class="ui-badge ui-badge--danger">Quá hạn</span>
                                    <?php endif; ?>
                                    <?php if ($__open || (int)$__t['status'] >= 1): ?>
                                        <a class="btn btn-sm <?= $__open && (int)$__t['status'] < 2 ? 'btn-primary' : 'btn-outline-primary' ?>" href="<?= htmlspecialchars($__url) ?>">
                                            <?= (int)$__t['status'] >= 2 ? 'Xem bài' : ((int)$__t['status'] === 1 ? 'Làm tiếp' : 'Làm bài') ?>
                                            <i class="bi bi-arrow-right" aria-hidden="true"></i>
                                        </a>
                                    <?php endif; ?>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php else: ?>
                        <p class="small text-muted mb-0 mt-2">
                            <?= $__state === 'is-locked' ? 'Nhiệm vụ sẽ hiện ở đây khi giai đoạn mở.' : 'Bạn không có nhiệm vụ nào ở giai đoạn này.' ?>
                        </p>
                    <?php endif; ?>
                </div>
            </li>
        <?php endforeach; ?>
    </ol>
<?php endif; ?>
