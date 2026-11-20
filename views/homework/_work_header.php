<?php
/**
 * Đầu trang dùng chung cho các trang làm bài với AI (genAI.php, genAI_BTVN.php).
 *
 * Hiển thị: breadcrumb đầy đủ, tên nhiệm vụ, đề bài, hạn nộp, trạng thái bài,
 * thanh các bước làm bài, trạng thái lưu và trạng thái AI.
 *
 * Biến cần có trước khi include:
 *   $pdo
 *   $wh = [
 *     'task_id', 'topic_id', 'phase_id', 'deliver_id' (int),
 *     'title', 'description', 'topic_name', 'phase_name', 'phase_number', 'phase_code',
 *     'steps'   => ['Nhập văn bản', 'AI phân tích', ...],
 *     'step'    => số bước hiện tại (1-based) tính từ phía server,
 *   ]
 *
 * JS: window.WorkHeader.setStep(n) để chuyển bước từ trang.
 * Trạng thái lưu và AI tự cập nhật bằng cách theo dõi fetch tới các API tương ứng.
 */
require_once __DIR__ . '/../../config/helper_phase.php';
$__w = $wh;
$__deadline = null;
$__status = 0;
try {
    $st = $pdo->prepare("
        SELECT d.status, COALESCE(d.deadline, t.deadline, p.end_time) AS due
        FROM deliver d
        JOIN task t ON t.task_id = d.task_id
        JOIN phase p ON p.phase_id = t.phase_id
        WHERE d.id_deliver = ?");
    $st->execute([(int)$__w['deliver_id']]);
    if ($row = $st->fetch(PDO::FETCH_ASSOC)) {
        $__status = (int)$row['status'];
        $__deadline = $row['due'] ? strtotime($row['due']) : null;
    }
} catch (Throwable $e) {
    error_log('_work_header: ' . $e->getMessage());
}

[$__phaseLabel, $__phaseIcon] = phaseTypeMeta($__w['phase_code'] ?? null, (int)($__w['phase_number'] ?? 0));
$__statusMeta = $__status >= 2
    ? ['Đã nộp', 'success', 'bi-check2-circle']
    : ($__status === 1 ? ['Đang làm', 'primary', 'bi-pencil'] : ['Chưa làm', 'warning', 'bi-circle']);

$__dueText = null; $__dueTone = '';
if ($__deadline) {
    $left = $__deadline - time();
    if ($left < 0) {
        $__dueText = 'Đã quá hạn (' . date('H:i d/m', $__deadline) . ')';
        $__dueTone = 'danger';
    } else {
        $__dueText = 'Hạn nộp ' . date('H:i d/m', $__deadline) . ' · còn '
            . ($left >= 86400 ? (int)floor($left / 86400) . ' ngày' : max(1, (int)ceil($left / 3600)) . ' giờ');
        $__dueTone = $left < 86400 ? 'warning' : '';
    }
}
$__steps = $__w['steps'];
$__step = max(1, min(count($__steps), (int)$__w['step']));
$__q = fn(array $a) => htmlspecialchars(http_build_query($a));
?>
<div class="pagetitle">
    <nav aria-label="Vị trí trang">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="<?= URL_ROOT ?>/class"><?= htmlspecialchars($_SESSION['current_class_name'] ?? 'Lớp học') ?></a></li>
            <li class="breadcrumb-item"><a href="<?= URL_ROOT ?>/class/phases?<?= $__q(['topic_id' => $__w['topic_id']]) ?>"><?= htmlspecialchars($__w['topic_name'] ?: 'Chủ đề') ?></a></li>
            <li class="breadcrumb-item"><a href="<?= URL_ROOT ?>/class/task?<?= $__q(['topic_id' => $__w['topic_id'], 'phase_id' => $__w['phase_id']]) ?>">Giai đoạn <?= (int)$__w['phase_number'] ?><?= $__w['phase_name'] ? ': ' . htmlspecialchars($__w['phase_name']) : '' ?></a></li>
            <li class="breadcrumb-item active" aria-current="page"><?= htmlspecialchars($__w['title'] ?: 'Nhiệm vụ') ?></li>
        </ol>
    </nav>
    <h1><?= htmlspecialchars($__w['title'] ?: 'Làm bài') ?></h1>
</div>

<section class="ui-card work-header mb-4" aria-label="Thông tin nhiệm vụ">
    <div class="work-header__top">
        <div class="work-header__meta">
            <span class="ui-badge ui-badge--accent"><i class="bi <?= htmlspecialchars($__phaseIcon) ?>" aria-hidden="true"></i> <?= htmlspecialchars($__phaseLabel) ?></span>
            <span class="ui-badge ui-badge--<?= $__statusMeta[1] ?>"><i class="bi <?= $__statusMeta[2] ?>" aria-hidden="true"></i> <?= $__statusMeta[0] ?></span>
            <?php if ($__dueText): ?>
                <span class="ui-badge<?= $__dueTone ? ' ui-badge--' . $__dueTone : '' ?>"><i class="bi bi-calendar-event" aria-hidden="true"></i> <?= htmlspecialchars($__dueText) ?></span>
            <?php endif; ?>
        </div>
        <div class="work-header__live" aria-live="polite">
            <span id="ai-status" class="work-status work-status--ai" hidden><span class="work-status__spinner" aria-hidden="true"></span><span class="work-status__text">AI đang xử lý…</span></span>
            <span id="autosave-status" class="work-status" data-state="idle"></span>
        </div>
    </div>
    <?php if (!empty($__w['description'])): ?>
        <p class="work-header__desc"><?= nl2br(htmlspecialchars($__w['description'])) ?></p>
    <?php endif; ?>

    <ol class="work-steps" id="work-steps" aria-label="Các bước làm bài">
        <?php foreach ($__steps as $i => $label): $n = $i + 1;
            $state = $n < $__step ? 'done' : ($n === $__step ? 'current' : 'todo'); ?>
            <li class="work-steps__item is-<?= $state ?>" data-step="<?= $n ?>"<?= $state === 'current' ? ' aria-current="step"' : '' ?>>
                <span class="work-steps__num" aria-hidden="true"><?= $n ?></span>
                <span class="work-steps__label"><?= htmlspecialchars($label) ?></span>
            </li>
        <?php endforeach; ?>
    </ol>
</section>

<script>
(function () {
    'use strict';
    var stepsEl = document.getElementById('work-steps');
    var saveEl = document.getElementById('autosave-status');
    var aiEl = document.getElementById('ai-status');
    var current = <?= (int)$__step ?>;

    function setStep(n) {
        if (!stepsEl) return;
        current = Math.max(1, Math.min(n, stepsEl.children.length));
        Array.prototype.forEach.call(stepsEl.children, function (li) {
            var s = +li.dataset.step;
            li.classList.toggle('is-done', s < current);
            li.classList.toggle('is-current', s === current);
            li.classList.toggle('is-todo', s > current);
            if (s === current) li.setAttribute('aria-current', 'step'); else li.removeAttribute('aria-current');
        });
    }

    function hhmm(d) { return ('0' + d.getHours()).slice(-2) + ':' + ('0' + d.getMinutes()).slice(-2); }
    var pendingSaves = 0;
    function setSave(state) {
        if (!saveEl) return;
        saveEl.dataset.state = state;
        var map = {
            saving: '<i class="bi bi-arrow-repeat" aria-hidden="true"></i> Đang lưu…',
            saved: '<i class="bi bi-cloud-check" aria-hidden="true"></i> Đã lưu lúc ' + hhmm(new Date()),
            error: '<i class="bi bi-exclamation-triangle" aria-hidden="true"></i> Chưa lưu được — kiểm tra mạng rồi thử lại'
        };
        saveEl.innerHTML = map[state] || '';
    }

    var aiTimer = null, aiStart = 0, aiPending = 0;
    function setAi(on) {
        if (!aiEl) return;
        if (on) {
            aiPending++;
            if (aiPending > 1) return;
            aiStart = Date.now();
            aiEl.hidden = false;
            var txt = aiEl.querySelector('.work-status__text');
            var tick = function () {
                var s = Math.round((Date.now() - aiStart) / 1000);
                txt.textContent = 'AI đang xử lý… ' + s + ' giây' + (s > 45 ? ' (văn bản dài có thể mất tới 2 phút)' : '');
            };
            tick();
            aiTimer = setInterval(tick, 1000);
        } else {
            aiPending = Math.max(0, aiPending - 1);
            if (aiPending) return;
            clearInterval(aiTimer);
            aiEl.hidden = true;
        }
    }

    // Theo dõi fetch để cập nhật trạng thái lưu và AI mà không phải sửa từng chỗ gọi API.
    var SAVE_RE = /\/api\/(save_[a-z_]+|analysis_history)\.php/;
    var AI_RE = /\/api\/(analyze_task|call_api)\.php/;
    if (window.fetch && !window.fetch.__workHeader) {
        var orig = window.fetch.bind(window);
        var wrapped = function (input, init) {
            var url = typeof input === 'string' ? input : (input && input.url) || '';
            var method = ((init && init.method) || (input && input.method) || 'GET').toUpperCase();
            var isSave = SAVE_RE.test(url) && method !== 'GET';
            var isAi = AI_RE.test(url);
            if (!isSave && !isAi) return orig(input, init);
            if (isSave) { pendingSaves++; setSave('saving'); }
            if (isAi) { setAi(true); if (current < 2) setStep(2); }
            return orig(input, init).then(function (res) {
                if (isSave) { pendingSaves--; setSave(res.ok ? 'saved' : 'error'); }
                if (isAi) { setAi(false); if (res.ok && current < 3) setStep(3); }
                return res;
            }, function (err) {
                if (isSave) { pendingSaves--; setSave('error'); }
                if (isAi) setAi(false);
                throw err;
            });
        };
        wrapped.__workHeader = true;
        window.fetch = wrapped;
    }

    // Cảnh báo khi rời trang lúc đang lưu hoặc AI đang chạy
    window.addEventListener('beforeunload', function (e) {
        if (pendingSaves > 0 || aiPending > 0) { e.preventDefault(); e.returnValue = ''; }
    });

    window.WorkHeader = { setStep: setStep, setSave: setSave };
})();
</script>
