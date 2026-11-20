<?php
/**
 * Tổng quan lớp cho giáo viên (đặt đầu trang /class).
 * Trả lời: giai đoạn nào đang mở và đã nộp bao nhiêu, bài nào chờ chấm, học sinh nào đang chậm.
 * Biến cần có: $pdo, $classId.
 */
$__cid = (int)$classId;
$__ov = ['students' => 0, 'open_phases' => [], 'to_grade' => [], 'behind' => []];
try {
    $st = $pdo->prepare("SELECT COUNT(*) FROM class_member WHERE class_id = ? AND role = 'student'");
    $st->execute([$__cid]);
    $__ov['students'] = (int)$st->fetchColumn();

    $st = $pdo->prepare("
        SELECT p.phase_id, p.phase_number, p.phase_name, p.end_time, mp.phase_code,
               top.topic_id, top.topic_name,
               COUNT(d.id_deliver) AS total,
               COALESCE(SUM(d.status >= 2), 0) AS submitted,
               COALESCE(SUM(d.status = 1), 0) AS in_progress
        FROM phase p
        JOIN topic top ON top.topic_id = p.topic_id
        LEFT JOIN main_phase mp ON mp.m_phase_id = p.m_phase_id
        LEFT JOIN task t ON t.phase_id = p.phase_id
        LEFT JOIN deliver d ON d.task_id = t.task_id
        WHERE top.class_id = ?
          AND (p.flag_active = 1 OR NOW() BETWEEN p.start_time AND p.end_time)
        GROUP BY p.phase_id, p.phase_number, p.phase_name, p.end_time, mp.phase_code, top.topic_id, top.topic_name
        ORDER BY p.end_time ASC
        LIMIT 6
    ");
    $st->execute([$__cid]);
    $__ov['open_phases'] = $st->fetchAll(PDO::FETCH_ASSOC);

    // Bài đã nộp (giai đoạn làm bài) mà giáo viên chưa chấm xong
    $st = $pdo->prepare("
        SELECT t.task_id, t.title, p.phase_id, p.topic_id, top.topic_name, COUNT(*) AS waiting
        FROM deliver d
        JOIN task t ON t.task_id = d.task_id
        JOIN phase p ON p.phase_id = t.phase_id
        JOIN topic top ON top.topic_id = p.topic_id
        LEFT JOIN main_phase mp ON mp.m_phase_id = p.m_phase_id
        WHERE top.class_id = ?
          AND d.status >= 2
          AND (mp.phase_code IS NULL OR mp.phase_code IN ('PHASE_START', 'PHASE_BTVN'))
          AND NOT EXISTS (SELECT 1 FROM evaluate e WHERE e.deliver_id = d.id_deliver AND e.is_teacher = 1 AND e.status = 2)
        GROUP BY t.task_id, t.title, p.phase_id, p.topic_id, top.topic_name
        ORDER BY waiting DESC
        LIMIT 5
    ");
    $st->execute([$__cid]);
    $__ov['to_grade'] = $st->fetchAll(PDO::FETCH_ASSOC);

    // Học sinh có bài quá hạn chưa nộp (bài cá nhân + bài nhóm)
    $st = $pdo->prepare("
        SELECT u.user_id, u.fullname, COUNT(*) AS overdue
        FROM deliver d
        JOIN task t ON t.task_id = d.task_id
        JOIN phase p ON p.phase_id = t.phase_id
        JOIN topic top ON top.topic_id = p.topic_id
        LEFT JOIN group_member gm ON d.type = 'group' AND gm.group_id = d.value
        JOIN users u ON u.user_id = CASE WHEN d.type = 'user' THEN d.value ELSE gm.user_id END
        WHERE top.class_id = ?
          AND d.type IN ('user', 'group')
          AND d.status < 2
          AND COALESCE(d.deadline, t.deadline, p.end_time) < NOW()
        GROUP BY u.user_id, u.fullname
        ORDER BY overdue DESC, u.fullname
        LIMIT 5
    ");
    $st->execute([$__cid]);
    $__ov['behind'] = $st->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
    error_log('teacher_class_overview: ' . $e->getMessage());
}

$__sumTotal = array_sum(array_map(fn($r) => (int)$r['total'], $__ov['open_phases']));
$__sumDone = array_sum(array_map(fn($r) => (int)$r['submitted'], $__ov['open_phases']));
$__waiting = array_sum(array_map(fn($r) => (int)$r['waiting'], $__ov['to_grade']));
?>
<section class="ui-section" aria-labelledby="ov-title">
    <h2 id="ov-title" class="visually-hidden">Tổng quan lớp</h2>
    <div class="row g-3 mb-3">
        <?php foreach ([
            ['bi-people', $__ov['students'], 'Học sinh', 'primary', URL_ROOT . '/class/participants'],
            ['bi-broadcast', count($__ov['open_phases']), 'Giai đoạn đang mở', 'success', null],
            ['bi-inbox', $__sumDone . '/' . $__sumTotal, 'Đã nộp (giai đoạn mở)', 'accent', null],
            ['bi-hourglass-split', $__waiting, 'Bài chờ chấm', $__waiting ? 'warning' : 'success', null],
        ] as [$icon, $value, $label, $tone, $href]): ?>
            <div class="col-6 col-lg-3">
                <<?= $href ? 'a href="' . htmlspecialchars($href) . '"' : 'div' ?> class="ui-card d-flex align-items-center gap-3 p-3 h-100 text-reset">
                    <span class="ui-badge ui-badge--<?= $tone ?>" style="width:44px;height:44px;justify-content:center;font-size:1.25rem;padding:0;"><i class="bi <?= $icon ?>" aria-hidden="true"></i></span>
                    <span>
                        <strong class="d-block fs-5 lh-sm"><?= htmlspecialchars((string)$value) ?></strong>
                        <span class="small text-muted"><?= $label ?></span>
                    </span>
                </<?= $href ? 'a' : 'div' ?>>
            </div>
        <?php endforeach; ?>
    </div>

    <div class="row g-3">
        <div class="col-lg-6">
            <div class="ui-card p-3 h-100">
                <div class="ui-section__head">
                    <h3 class="ui-section__title"><i class="bi bi-broadcast text-primary" aria-hidden="true"></i> Giai đoạn đang mở</h3>
                </div>
                <?php if ($__ov['open_phases']): ?>
                    <ul class="list-unstyled m-0">
                        <?php foreach ($__ov['open_phases'] as $i => $ph):
                            $pct = $ph['total'] ? (int)round($ph['submitted'] * 100 / $ph['total']) : 0;
                            [$tl] = phaseTypeMeta($ph['phase_code'] ?? null, (int)$ph['phase_number']);
                            $left = strtotime($ph['end_time']) - time();
                        ?>
                            <li class="py-2<?= $i ? ' border-top' : '' ?>">
                                <a class="d-block text-reset" href="<?= URL_ROOT ?>/class/task?topic_id=<?= (int)$ph['topic_id'] ?>&phase_id=<?= (int)$ph['phase_id'] ?>">
                                    <div class="d-flex justify-content-between gap-2 small mb-1">
                                        <span class="fw-semibold text-truncate"><?= htmlspecialchars($ph['topic_name']) ?> › <?= htmlspecialchars($ph['phase_name']) ?></span>
                                        <span class="text-muted text-nowrap"><?= (int)$ph['submitted'] ?>/<?= (int)$ph['total'] ?> đã nộp</span>
                                    </div>
                                    <div class="ui-progress mb-1" role="progressbar" aria-label="Tỉ lệ nộp bài" aria-valuenow="<?= $pct ?>" aria-valuemin="0" aria-valuemax="100"><span style="width: <?= $pct ?>%"></span></div>
                                    <div class="small text-muted"><?= htmlspecialchars($tl) ?> · <?= $left > 0 ? 'còn ' . ($left >= 86400 ? (int)floor($left / 86400) . ' ngày' : (int)ceil($left / 3600) . ' giờ') : 'đã hết hạn' ?></div>
                                </a>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php else: ?>
                    <div class="ui-empty ui-empty--compact">
                        <p class="mb-0">Chưa có giai đoạn nào đang mở. Vào một chủ đề bên dưới để mở giai đoạn mới.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        <div class="col-lg-3 col-md-6">
            <div class="ui-card p-3 h-100">
                <div class="ui-section__head">
                    <h3 class="ui-section__title"><i class="bi bi-hourglass-split text-primary" aria-hidden="true"></i> Chờ chấm</h3>
                </div>
                <?php if ($__ov['to_grade']): ?>
                    <ul class="list-unstyled m-0">
                        <?php foreach ($__ov['to_grade'] as $i => $g): ?>
                            <li class="py-2<?= $i ? ' border-top' : '' ?>">
                                <a class="d-flex justify-content-between gap-2 text-reset" href="<?= URL_ROOT ?>/class/evaluate/teacher_grades?task_id=<?= (int)$g['task_id'] ?>&topic_id=<?= (int)$g['topic_id'] ?>&phase_id=<?= (int)$g['phase_id'] ?>">
                                    <span class="small text-truncate"><?= htmlspecialchars($g['title']) ?></span>
                                    <span class="ui-badge ui-badge--warning"><?= (int)$g['waiting'] ?></span>
                                </a>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php else: ?>
                    <p class="small text-muted mb-0"><i class="bi bi-check2-circle text-success" aria-hidden="true"></i> Không có bài nào chờ chấm.</p>
                <?php endif; ?>
            </div>
        </div>
        <div class="col-lg-3 col-md-6">
            <div class="ui-card p-3 h-100">
                <div class="ui-section__head">
                    <h3 class="ui-section__title"><i class="bi bi-exclamation-triangle text-primary" aria-hidden="true"></i> Cần nhắc nhở</h3>
                </div>
                <?php if ($__ov['behind']): ?>
                    <ul class="list-unstyled m-0">
                        <?php foreach ($__ov['behind'] as $i => $b): ?>
                            <li class="d-flex justify-content-between gap-2 py-2<?= $i ? ' border-top' : '' ?>">
                                <span class="small text-truncate"><?= htmlspecialchars($b['fullname']) ?></span>
                                <span class="ui-badge ui-badge--danger" title="Số bài quá hạn chưa nộp"><?= (int)$b['overdue'] ?> quá hạn</span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php else: ?>
                    <p class="small text-muted mb-0"><i class="bi bi-emoji-smile text-success" aria-hidden="true"></i> Không có học sinh nào trễ hạn.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>
