<?php
/**
 * Form chấm điểm theo tiêu chí, dạng thẻ (dùng chung cho học sinh chấm chéo và giáo viên chấm).
 *
 * Biến cần có:
 *   $pdo, $criteria (getEvaluationCriteria), $existingScores [tc_id => điểm], $evaluation (có id_evaluate, status, total_score)
 *   $rf = [
 *     'hidden'       => ['deliver_id' => .., 'task_id' => .., ...],   // trường ẩn gửi kèm
 *     'submit_text'  => 'Hoàn thành đánh giá',
 *     'done_text'    => 'Bạn đã hoàn thành đánh giá này.',
 *     'confirm'      => ['title' => .., 'message' => ..],
 *     'redirect'     => URL sau khi hoàn thành (null = tải lại trang),
 *   ]
 *
 * Nếu tiêu chí có mức mô tả (bảng rubric_level), mỗi mức hiện thành một thẻ chọn kèm mô tả;
 * nếu không, hiện các nút điểm.
 */
$__locked = (int)($evaluation['status'] ?? 0) === 2;

// Mức mô tả của từng tiêu chí (nếu giáo viên đã thiết lập)
$__levels = [];
$__tcIds = array_map(fn($c) => (int)$c['tc_id'], $criteria);
if ($__tcIds) {
    try {
        $in = implode(',', array_fill(0, count($__tcIds), '?'));
        $st = $pdo->prepare("SELECT tc_id, level_name, score, description FROM rubric_level WHERE tc_id IN ($in) ORDER BY tc_id, sort_order, score DESC");
        $st->execute($__tcIds);
        foreach ($st->fetchAll(PDO::FETCH_ASSOC) as $lv) {
            $__levels[(int)$lv['tc_id']][] = $lv;
        }
    } catch (Throwable $e) {
        error_log('_rubric_form levels: ' . $e->getMessage());
    }
}

$__maxTotal = array_sum(array_map(fn($c) => (float)$c['max_score'], $criteria));
$__fmt = fn($v) => (abs($v - round($v)) < 0.001) ? (string)(int)round($v) : number_format((float)$v, 1);
$__scoredCount = 0;
foreach ($criteria as $c) {
    if (isset($existingScores[$c['tc_id']]) && $existingScores[$c['tc_id']] !== null) $__scoredCount++;
}
?>
<form id="evaluationForm" class="rubric-form" method="POST" action="<?= URL_ROOT ?>/api/evaluate/submit_evaluation.php"
      data-redirect="<?= htmlspecialchars((string)($rf['redirect'] ?? '')) ?>"
      data-confirm-title="<?= htmlspecialchars($rf['confirm']['title'] ?? 'Hoàn thành?') ?>"
      data-confirm-message="<?= htmlspecialchars($rf['confirm']['message'] ?? '') ?>"
      data-submit-text="<?= htmlspecialchars($rf['submit_text'] ?? 'Hoàn thành') ?>">
    <input type="hidden" name="evaluate_id" value="<?= (int)$evaluation['id_evaluate'] ?>">
    <?php foreach (($rf['hidden'] ?? []) as $k => $v): ?>
        <input type="hidden" name="<?= htmlspecialchars($k) ?>" value="<?= htmlspecialchars((string)$v) ?>">
    <?php endforeach; ?>

    <div class="rubric-summary">
        <div>
            <div class="rubric-summary__label">Tổng điểm</div>
            <div class="rubric-summary__total"><span id="totalScore"><?= $__fmt((float)$evaluation['total_score']) ?></span><span class="rubric-summary__max"> / <?= $__fmt($__maxTotal) ?></span></div>
        </div>
        <div class="text-end">
            <div class="rubric-summary__label">Đã chọn điểm</div>
            <div class="rubric-summary__count"><span id="rubricScored"><?= $__scoredCount ?></span>/<?= count($criteria) ?> tiêu chí</div>
        </div>
        <div class="ui-progress rubric-summary__bar" aria-hidden="true"><span id="rubricBar" style="width: <?= count($criteria) ? round($__scoredCount * 100 / count($criteria)) : 0 ?>%"></span></div>
    </div>

    <div class="rubric-list">
        <?php foreach ($criteria as $i => $criterion):
            $tcId = (int)$criterion['tc_id'];
            $min = (float)$criterion['min_score'];
            $max = (float)$criterion['max_score'];
            $has = isset($existingScores[$tcId]) && $existingScores[$tcId] !== null;
            $cur = $has ? (float)$existingScores[$tcId] : $min;
            $step = ($max - $min) <= 4 ? 1 : 0.5;
            $levels = $__levels[$tcId] ?? [];
            $nameId = 'rc-name-' . $tcId;
        ?>
            <fieldset class="rubric-card<?= $has ? ' is-scored' : '' ?>" data-tc="<?= $tcId ?>" aria-labelledby="<?= $nameId ?>">
                <div class="rubric-card__head">
                    <span class="rubric-card__num" aria-hidden="true"><?= $i + 1 ?></span>
                    <div class="rubric-card__title">
                        <legend id="<?= $nameId ?>"><?= htmlspecialchars(criteriaLabel($criterion['name_tc'])) ?></legend>
                        <?php if (!empty($criterion['description'])): ?>
                            <p><?= nl2br(htmlspecialchars(criteriaLabel($criterion['description']))) ?></p>
                        <?php endif; ?>
                    </div>
                    <span class="rubric-card__score" data-tc="<?= $tcId ?>"><?= $has ? $__fmt($cur) : '—' ?><small> / <?= $__fmt($max) ?></small></span>
                </div>

                <?php if ($levels): ?>
                    <div class="rubric-levels" role="radiogroup" aria-labelledby="<?= $nameId ?>">
                        <?php foreach ($levels as $lv): $sel = $has && abs($cur - (float)$lv['score']) < 0.01; ?>
                            <button type="button" class="rubric-level<?= $sel ? ' active' : '' ?>" role="radio" aria-checked="<?= $sel ? 'true' : 'false' ?>"
                                    data-value="<?= $__fmt((float)$lv['score']) ?>"<?= $__locked ? ' disabled' : '' ?>>
                                <span class="rubric-level__top"><strong><?= htmlspecialchars($lv['level_name']) ?></strong><span class="rubric-level__pts"><?= $__fmt((float)$lv['score']) ?> đ</span></span>
                                <?php if (!empty($lv['description'])): ?><span class="rubric-level__desc"><?= htmlspecialchars($lv['description']) ?></span><?php endif; ?>
                            </button>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="rubric-chips" role="radiogroup" aria-labelledby="<?= $nameId ?>">
                        <?php for ($s = $min; $s <= $max + 0.001; $s += $step): $sel = $has && abs($cur - $s) < 0.01; ?>
                            <button type="button" class="rubric-chip<?= $sel ? ' active' : '' ?>" role="radio" aria-checked="<?= $sel ? 'true' : 'false' ?>"
                                    aria-label="<?= $__fmt($s) ?> điểm" data-value="<?= $__fmt($s) ?>"<?= $__locked ? ' disabled' : '' ?>><?= $__fmt($s) ?></button>
                        <?php endfor; ?>
                    </div>
                <?php endif; ?>
                <input type="hidden" name="scores[<?= $tcId ?>]" class="score-input" data-tc="<?= $tcId ?>" data-min="<?= $min ?>" data-max="<?= $max ?>" value="<?= $cur ?>">
            </fieldset>
        <?php endforeach; ?>
    </div>

    <?php if (!$__locked): ?>
        <div class="rubric-actions">
            <span class="rubric-actions__hint" id="rubricHint" aria-live="polite"></span>
            <button type="submit" name="action" value="draft" class="btn btn-outline-secondary"><i class="bi bi-save" aria-hidden="true"></i> Lưu nháp</button>
            <button type="submit" name="action" value="submit" class="btn btn-primary"><i class="bi bi-send" aria-hidden="true"></i> <?= htmlspecialchars($rf['submit_text'] ?? 'Hoàn thành') ?></button>
        </div>
    <?php else: ?>
        <div class="ui-empty ui-empty--compact rubric-done"><i class="bi bi-check2-circle text-success" aria-hidden="true"></i> <?= htmlspecialchars($rf['done_text'] ?? 'Đã hoàn thành.') ?></div>
    <?php endif; ?>
</form>

<script>
(function () {
    'use strict';
    var form = document.getElementById('evaluationForm');
    if (!form) return;
    var cards = form.querySelectorAll('.rubric-card');
    var dirty = false;

    function fmt(v) { return Math.abs(v - Math.round(v)) < 0.001 ? String(Math.round(v)) : v.toFixed(1); }
    function refresh() {
        var total = 0, scored = 0;
        form.querySelectorAll('.score-input').forEach(function (i) { total += parseFloat(i.value) || 0; });
        cards.forEach(function (c) { if (c.classList.contains('is-scored')) scored++; });
        document.getElementById('totalScore').textContent = fmt(total);
        document.getElementById('rubricScored').textContent = scored;
        document.getElementById('rubricBar').style.width = (cards.length ? Math.round(scored * 100 / cards.length) : 0) + '%';
        var hint = document.getElementById('rubricHint');
        if (hint) hint.textContent = scored < cards.length ? 'Còn ' + (cards.length - scored) + ' tiêu chí chưa chọn điểm' : 'Đã chọn điểm đủ các tiêu chí';
    }

    cards.forEach(function (card) {
        var tc = card.dataset.tc;
        var input = card.querySelector('.score-input');
        var label = card.querySelector('.rubric-card__score');
        var opts = card.querySelectorAll('[role="radio"]');
        opts.forEach(function (btn, idx) {
            btn.addEventListener('click', function () {
                if (btn.disabled) return;
                opts.forEach(function (o) { o.classList.remove('active'); o.setAttribute('aria-checked', 'false'); });
                btn.classList.add('active');
                btn.setAttribute('aria-checked', 'true');
                input.value = btn.dataset.value;
                label.firstChild.nodeValue = btn.dataset.value;
                card.classList.add('is-scored');
                dirty = true;
                refresh();
            });
            // Phím mũi tên để chuyển lựa chọn trong nhóm
            btn.addEventListener('keydown', function (e) {
                var d = (e.key === 'ArrowRight' || e.key === 'ArrowDown') ? 1 : (e.key === 'ArrowLeft' || e.key === 'ArrowUp') ? -1 : 0;
                if (!d) return;
                e.preventDefault();
                var next = opts[(idx + d + opts.length) % opts.length];
                next.focus(); next.click();
            });
        });
    });
    refresh();

    window.addEventListener('beforeunload', function (e) { if (dirty) { e.preventDefault(); e.returnValue = ''; } });

    form.addEventListener('submit', async function (e) {
        e.preventDefault();
        var action = (e.submitter && e.submitter.value) || 'draft';
        if (action === 'submit') {
            var missing = Array.prototype.filter.call(cards, function (c) { return !c.classList.contains('is-scored'); }).length;
            var msg = form.dataset.confirmMessage || '';
            if (missing) msg = 'Còn ' + missing + ' tiêu chí bạn chưa chọn điểm (sẽ tính điểm thấp nhất). ' + msg;
            var ok = await UI.confirm({ title: form.dataset.confirmTitle, message: msg, confirmText: form.dataset.submitText });
            if (!ok) return;
        }
        var fd = new FormData(form);
        fd.set('action', action); // nút submit không tự có trong FormData
        var buttons = form.querySelectorAll('button[type="submit"]');
        buttons.forEach(function (b) { b.disabled = true; });
        try {
            var res = await fetch(form.action, { method: 'POST', body: fd });
            var data = await res.json();
            if (data.success) {
                dirty = false;
                UI.success(data.message || (action === 'submit' ? 'Đã hoàn thành.' : 'Đã lưu nháp.'));
                if (action === 'submit' && form.dataset.redirect) window.location.href = form.dataset.redirect;
                else location.reload();
            } else {
                UI.error(data.message || 'Không lưu được. Vui lòng thử lại.');
            }
        } catch (err) {
            UI.networkError(err, 'Không lưu được điểm. Kiểm tra mạng rồi thử lại.');
        } finally {
            buttons.forEach(function (b) { b.disabled = false; });
        }
    });
})();
</script>
