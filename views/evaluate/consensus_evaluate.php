<?php
// GĐ 4 — Đánh giá chéo theo nhóm với Cổng Đồng thuận
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../config/helper_evaluate.php';
require_once __DIR__ . '/../../config/helper_consensus.php';
require_once __DIR__ . '/../../config/helper_breadcrumb.php';
require_once __DIR__ . '/../../config/helper_criteria_i18n.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user']['user_id'])) {
    header("Location: /login");
    exit;
}

$userId    = (int)$_SESSION['user']['user_id'];
$taskId    = isset($_GET['task_id']) ? intval($_GET['task_id']) : 0;
$topicId   = isset($_GET['topic_id']) ? intval($_GET['topic_id']) : 0;
$phaseId   = isset($_GET['phase_id']) ? intval($_GET['phase_id']) : 0;
$deliverId = isset($_GET['deliver_id']) ? intval($_GET['deliver_id']) : 0;

if (!$taskId || !$deliverId) {
    $_SESSION['error_message'] = 'Đường dẫn không hợp lệ.';
    header("Location: /404");
    exit;
}

// Quyền: phải là thành viên nhóm của deliver
$deliverInfo = checkDeliverAccess($userId, $deliverId);
if (!$deliverInfo || $deliverInfo['type'] !== 'group') {
    $_SESSION['error_message'] = 'Trang này chỉ dành cho đánh giá theo nhóm.';
    header("Location: /dashboard");
    exit;
}

$taskInfo = getTaskInfo($taskId);
if (!$taskInfo) {
    $_SESSION['error_message'] = 'Không tìm thấy nhiệm vụ.';
    header("Location: /dashboard");
    exit;
}

$responseInfo = getResponseToEvaluate($deliverId);
if (!$responseInfo) {
    $_SESSION['error_message'] = 'Không tìm thấy bài cần chấm.';
    header("Location: /dashboard");
    exit;
}

$criteria = getEvaluationCriteria($taskId);
$sMax = getMaxScoreForTask($taskId);
if ($sMax <= 0) {
    $sMax = 10;
}

$consensus = getOrCreateConsensus($deliverId, $responseInfo['id_response']);
$state = getConsensusState($consensus['id'], $userId);
?>
<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php require_once __DIR__ . '/../templates/assets.php'; ?>
    <title>Đánh giá nhóm · <?= htmlspecialchars($taskInfo['title']) ?></title>
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
                    <li class="breadcrumb-item"><a href="<?= breadcrumbTopicPhasesUrl($topicId) ?>">Lộ trình chủ đề</a></li>
                    <li class="breadcrumb-item"><a
                            href="<?= URL_ROOT ?>/class/evaluate/list?task_id=<?= $taskId ?>&topic_id=<?= $topicId ?>&phase_id=<?= $phaseId ?>">Đánh giá chéo</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Chấm theo nhóm</li>
                </ol>
            </nav>
            <h1>Chấm theo nhóm: <?= htmlspecialchars($taskInfo['title']) ?></h1>
        </div>

        <section class="section">
            <div class="card mb-3">
                <div class="card-body py-3">
                    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2">
                        <div>
                            <strong><?= htmlspecialchars($taskInfo['title']) ?></strong>
                            <span class="text-muted ms-2">Bài của tác giả ẩn danh · nộp
                                <?= date('d/m/Y H:i', strtotime($responseInfo['response_created_at'])) ?></span>
                        </div>
                        <span id="statusPill" class="status-pill pending">Đang chấm riêng</span>
                    </div>
                    <ol class="work-steps" id="consensusSteps" aria-label="Các bước chấm theo nhóm">
                        <li class="work-steps__item is-current" data-step="1"><span class="work-steps__num" aria-hidden="true">1</span><span class="work-steps__label">Mỗi người chấm riêng</span></li>
                        <li class="work-steps__item is-todo" data-step="2"><span class="work-steps__num" aria-hidden="true">2</span><span class="work-steps__label">Thảo luận nếu điểm lệch nhau</span></li>
                        <li class="work-steps__item is-todo" data-step="3"><span class="work-steps__num" aria-hidden="true">3</span><span class="work-steps__label">Chốt điểm chung</span></li>
                    </ol>
                </div>
            </div>

            <div class="row">
                <!-- Trái: bài cần đánh giá -->
                <div class="col-lg-6">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title"><i class="bi bi-file-text"></i> <span>Bài cần chấm</span></h5>
                            <?php if (!empty($responseInfo['contents'])): ?>
                                <?php foreach ($responseInfo['contents'] as $content): ?>
                                    <div class="submission-body"><?= renderSubmissionMarkdown($content['content']) ?></div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="alert alert-warning mb-0">Chưa có nội dung bài nộp.</div>
                            <?php endif; ?>

                            <?php if (!empty($criteria)): ?>
                                <hr>
                                <p class="mb-2"><strong>Tiêu chí chấm</strong>
                                    <span class="text-muted">(tổng tối đa <?= $sMax ?> điểm)</span></p>
                                <div class="rubric-preview mb-2">
                                    <?php foreach ($criteria as $criterion): ?>
                                        <span class="ui-badge" title="<?= htmlspecialchars(criteriaLabel($criterion['description'] ?? '')) ?>">
                                            <?= htmlspecialchars(criteriaLabel($criterion['name_tc'])) ?>
                                            (<?= $criterion['min_score'] ?>–<?= $criterion['max_score'] ?>)
                                        </span>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Phải: cổng đồng thuận -->
                <div class="col-lg-6">
                    <!-- Bước 1: chấm ẩn -->
                    <div class="card" id="panelScore">
                        <div class="card-body">
                            <h5 class="card-title"><i class="bi bi-incognito"></i> Bước 1 — Chấm riêng</h5>
                            <p class="text-muted small mb-3">
                                Chọn điểm cho từng tiêu chí. Điểm của bạn được giữ kín cho tới khi cả nhóm chấm xong.
                            </p>
                            <div class="consensus-step">
                                <?php if (empty($criteria)): ?>
                                    <div class="alert alert-warning mb-0">Nhiệm vụ này chưa có tiêu chí chấm. Hãy báo thầy cô thiết lập tiêu chí.</div>
                                <?php else: ?>
                                <div class="step-label">Điểm theo tiêu chí</div>
                                <div class="accordion eval-criteria-accordion mb-2" id="criteriaAccordion">
                                <?php foreach ($criteria as $i => $criterion):
                                    $tcId = (int)$criterion['tc_id'];
                                    $min = (float)$criterion['min_score'];
                                    $max = (float)$criterion['max_score'];
                                    $step = ($max - $min) <= 4 ? 1 : 0.5;
                                    $cur = $min;
                                ?>
                                    <div class="accordion-item">
                                        <h2 class="accordion-header">
                                            <button class="accordion-button <?= $i > 0 ? 'collapsed' : '' ?>" type="button" data-bs-toggle="collapse" data-bs-target="#c<?= $tcId ?>">
                                                <?= htmlspecialchars(criteriaLabel($criterion['name_tc'])) ?>
                                                <span class="ms-auto me-2 badge bg-primary criterion-score-badge" data-tc="<?= $tcId ?>"><?= $cur ?></span>
                                            </button>
                                        </h2>
                                        <div id="c<?= $tcId ?>" class="accordion-collapse collapse <?= $i === 0 ? 'show' : '' ?>" data-bs-parent="#criteriaAccordion">
                                            <div class="accordion-body py-2">
                                                <?php if (!empty($criterion['description'])): ?>
                                                    <p class="small text-muted mb-2"><?= htmlspecialchars(criteriaLabel($criterion['description'])) ?></p>
                                                <?php endif; ?>
                                                <div class="score-chip-group" data-tc="<?= $tcId ?>">
                                                <?php for ($s = $min; $s <= $max + 0.001; $s += $step): ?>
                                                    <button type="button" class="score-chip <?= abs($cur - $s) < 0.01 ? 'active' : '' ?>" data-value="<?= $step >= 1 ? (int)$s : number_format($s, 1) ?>"><?= $step >= 1 ? (int)$s : number_format($s, 1) ?></button>
                                                <?php endfor; ?>
                                                </div>
                                                <input type="hidden" class="score-input" data-tc="<?= $tcId ?>" data-min="<?= $min ?>" data-max="<?= $max ?>" value="<?= $cur ?>">
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                                </div>

                                <div class="total-score-display mb-3">
                                    <div>Tổng điểm của bạn</div>
                                    <div id="totalScore">0</div>
                                    <div class="text-muted small">/ tối đa <?= $sMax ?></div>
                                </div>

                                <div class="mb-3">
                                    <label for="myComment" class="form-label small fw-semibold">Nhận xét ngắn (không bắt buộc)</label>
                                    <textarea id="myComment" class="form-control" rows="2" maxlength="500"
                                        placeholder="Ví dụ: Luận điểm rõ nhưng dẫn chứng từ văn bản còn ít…"></textarea>
                                </div>
                                <button id="btnSubmitScore" class="btn btn-primary">
                                    <i class="bi bi-lock"></i> <span id="btnSubmitLabel">Xác nhận điểm</span>
                                </button>
                                <p class="text-muted small mt-2 mb-0" id="submitHint">
                                    Sau khi xác nhận, bạn chỉ sửa được khi cả nhóm đã chấm xong.
                                </p>
                                <?php endif; ?>
                            </div>

                            <div class="consensus-step">
                                <div class="step-label">Tiến độ của nhóm</div>
                                <div id="consensusScale" class="consensus-scale mb-3" hidden></div>
                                <div id="memberList"></div>
                            </div>
                        </div>
                    </div>

                    <!-- Bước 2: thảo luận (ẩn mặc định) -->
                    <div class="card d-none" id="panelDiscuss">
                        <div class="card-body">
                            <h5 class="card-title"><i class="bi bi-chat-square-text"></i> Bước 2 — Thảo luận để thống nhất</h5>
                            <div class="alert alert-warning py-2 small">
                                Điểm của nhóm còn lệch nhau quá ±<?= CONSENSUS_EPSILON ?> điểm.
                                Hãy trao đổi lý do và chỉnh lại điểm; hệ thống tự chốt khi điểm của mọi người đủ gần nhau.
                            </div>
                            <div class="chat-box mb-2" id="chatBox"></div>
                            <div class="input-group mb-3">
                                <input aria-label="Tin nhắn thảo luận" type="text" id="chatInput" class="form-control" maxlength="2000"
                                    placeholder="Giải thích vì sao bạn cho điểm như vậy…">
                                <button class="btn btn-outline-primary" id="btnSendChat" aria-label="Gửi tin nhắn" title="Gửi tin nhắn"><i class="bi bi-send" aria-hidden="true"></i></button>
                            </div>
                        </div>
                    </div>

                    <!-- Bước 3: kết quả -->
                    <div class="card d-none" id="panelAgreed">
                        <div class="card-body">
                            <h5 class="card-title"><i class="bi bi-check2-circle"></i> Nhóm đã thống nhất điểm</h5>
                            <div class="final-score-box mb-3">
                                <div class="text-muted small">Điểm chung của nhóm</div>
                                <div class="value"><span id="finalScore">–</span> / <?= $sMax ?></div>
                            </div>
                            <p class="text-muted small mb-0">
                                Điểm này được ghi cho mọi thành viên. Độ chính xác khi chấm (S_Acc) sẽ được tính sau khi thầy cô chấm điểm cuối.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <?php
    require_once __DIR__ . '/../templates/footer.php';
    require_once __DIR__ . '/../templates/vendors.php';
    ?>

    <script>
        (function () {
            const API = '/api/evaluate/consensus.php';
            const DELIVER_ID = <?= (int)$deliverId ?>;
            const MY_ID = <?= (int)$userId ?>;
            const S_MAX = <?= (float)$sMax ?>;
            const EPS = <?= (float)CONSENSUS_EPSILON ?>;

            const statusPill = document.getElementById('statusPill');
            const panelScore = document.getElementById('panelScore');
            const panelDiscuss = document.getElementById('panelDiscuss');
            const panelAgreed = document.getElementById('panelAgreed');
            const memberList = document.getElementById('memberList');
            const chatBox = document.getElementById('chatBox');
            const myCommentInput = document.getElementById('myComment');
            const totalScoreEl = document.getElementById('totalScore');
            const scoreLocked = { value: false };
            const btnSubmit = document.getElementById('btnSubmitScore');
            const btnSubmitLabel = document.getElementById('btnSubmitLabel');
            const submitHint = document.getElementById('submitHint');

            let currentState = null;
            let pollTimer = null;
            let chatWasAtBottom = true;

            function post(action, extra) {
                const fd = new FormData();
                fd.append('action', action);
                fd.append('deliver_id', DELIVER_ID);
                for (const k in (extra || {})) fd.append(k, extra[k]);
                return fetch(API, { method: 'POST', body: fd }).then(r => r.json());
            }

            function escapeHtml(s) {
                const div = document.createElement('div');
                div.textContent = s == null ? '' : String(s);
                return div.innerHTML;
            }

            function render(state) {
                currentState = state;

                // Status pill
                const labels = {
                    pending: ['pending', 'Đang chấm riêng (' + state.submitted_count + '/' + state.member_count + ')'],
                    discussing: ['discussing', 'Đang thảo luận'],
                    agreed: ['agreed', 'Đã thống nhất'],
                    timeout: ['pending', 'Hết thời gian — chờ thầy cô quyết định']
                };
                const [cls, label] = labels[state.status] || labels.pending;
                statusPill.className = 'status-pill ' + cls;
                statusPill.textContent = label;

                // Member list
                memberList.innerHTML = state.members.map(m => {
                    const reveal = state.status !== 'pending';
                    let right;
                    if (!m.submitted) {
                        right = '<span class="text-muted small">Chưa chấm</span>';
                    } else if (reveal || m.is_me) {
                        right = '<span class="member-score">' + (m.score != null ? m.score : '·') + '</span>';
                    } else {
                        right = '<span class="text-success small"><i class="bi bi-check-lg" aria-hidden="true"></i> Đã chấm</span>';
                    }
                    const reason = (reveal && m.comment)
                        ? '<span class="member-reason">' + escapeHtml(m.comment) + '</span>'
                        : '<span class="member-reason"></span>';
                    return '<div class="member-row' + (m.is_me ? ' is-me' : '') + '">'
                        + '<span class="small fw-semibold">' + escapeHtml(m.fullname) + (m.is_me ? ' (bạn)' : '') + '</span>'
                        + reason + right + '</div>';
                }).join('');

                // My submitted state
                const me = state.members.find(m => m.is_me) || {};

                if (state.status === 'pending') {
                    panelDiscuss.classList.add('d-none');
                    panelAgreed.classList.add('d-none');
                    panelScore.classList.remove('d-none');
                    if (me.submitted) {
                        applyCriterionScores(me.criterion_scores, true);
                        if (myCommentInput) myCommentInput.disabled = true;
                        if (btnSubmit) btnSubmit.disabled = true;
                        if (btnSubmitLabel) btnSubmitLabel.textContent = 'Đã gửi — chờ cả nhóm';
                        if (submitHint) submitHint.textContent = 'Đang chờ các bạn khác chấm xong.';
                    } else {
                        setScoringEnabled(true);
                    }
                } else if (state.status === 'discussing') {
                    panelScore.classList.remove('d-none');
                    panelAgreed.classList.add('d-none');
                    panelDiscuss.classList.remove('d-none');
                    setScoringEnabled(true);
                    if (btnSubmitLabel) btnSubmitLabel.textContent = 'Cập nhật điểm của tôi';
                    if (submitHint) submitHint.textContent = 'Chỉnh điểm trong lúc thảo luận. Hệ thống tự chốt khi điểm của mọi người đủ gần nhau.';
                    if (me.submitted && me.criterion_scores) {
                        applyCriterionScores(me.criterion_scores, false);
                    }
                    renderChat(state.chat || []);
                } else if (state.status === 'agreed') {
                    panelScore.classList.add('d-none');
                    panelDiscuss.classList.add('d-none');
                    panelAgreed.classList.remove('d-none');
                    document.getElementById('finalScore').textContent =
                        state.final_score != null ? state.final_score : '–';
                    stopPolling();
                }

                renderSteps(state.status);
                renderScale(state);
                recalcTotal();
            }

            function renderSteps(status) {
                const cur = status === 'agreed' ? 4 : (status === 'discussing' || status === 'timeout' ? 2 : 1);
                document.querySelectorAll('#consensusSteps .work-steps__item').forEach(li => {
                    const n = +li.dataset.step;
                    li.classList.toggle('is-done', n < cur);
                    li.classList.toggle('is-current', n === cur);
                    li.classList.toggle('is-todo', n > cur);
                });
            }

            // Thang điểm: vị trí điểm của từng thành viên (chỉ hiện khi điểm đã được công khai)
            function renderScale(state) {
                const box = document.getElementById('consensusScale');
                if (!box) return;
                const pts = state.status === 'pending' ? [] : state.members.filter(m => m.submitted && m.score != null);
                if (pts.length < 2) { box.hidden = true; return; }
                const vals = pts.map(m => parseFloat(m.score));
                const spread = Math.max.apply(null, vals) - Math.min.apply(null, vals);
                const pos = v => Math.max(0, Math.min(100, v * 100 / S_MAX));
                box.innerHTML = '<div class="consensus-scale__track">'
                    + pts.map(m => '<span class="consensus-scale__dot' + (m.is_me ? ' is-me' : '') + '" style="left:' + pos(parseFloat(m.score)) + '%" title="' + escapeHtml(m.fullname) + ': ' + m.score + '"></span>').join('')
                    + '</div><div class="consensus-scale__axis"><span>0</span><span>' + S_MAX + '</span></div>'
                    + '<p class="small mb-0 mt-1 ' + (spread <= EPS ? 'text-success' : 'text-warning') + '">Điểm lệch nhau ' + spread.toFixed(1) + ' điểm'
                    + (spread <= EPS ? ' — đủ gần để chốt.' : ' — cần ≤ ' + EPS + ' để chốt.') + '</p>';
                box.hidden = false;
            }

            function setScoringEnabled(enabled) {
                scoreLocked.value = !enabled;
                document.querySelectorAll('.score-chip').forEach(chip => {
                    chip.disabled = !enabled;
                    chip.style.pointerEvents = enabled ? '' : 'none';
                    chip.style.opacity = enabled ? '' : '0.6';
                });
                if (myCommentInput) myCommentInput.disabled = !enabled;
                if (btnSubmit) btnSubmit.disabled = !enabled;
            }

            function applyCriterionScores(scores, lock) {
                if (!scores) return;
                Object.keys(scores).forEach(tcId => {
                    const val = scores[tcId];
                    const input = document.querySelector('.score-input[data-tc="' + tcId + '"]');
                    const badge = document.querySelector('.criterion-score-badge[data-tc="' + tcId + '"]');
                    const group = document.querySelector('.score-chip-group[data-tc="' + tcId + '"]');
                    if (input) input.value = val;
                    if (badge) badge.textContent = val;
                    if (group) {
                        group.querySelectorAll('.score-chip').forEach(c => {
                            c.classList.toggle('active', String(c.dataset.value) === String(val) || parseFloat(c.dataset.value) === parseFloat(val));
                        });
                    }
                });
                recalcTotal();
                if (lock) setScoringEnabled(false);
            }

            function renderChat(messages) {
                chatWasAtBottom = chatBox.scrollHeight - chatBox.scrollTop - chatBox.clientHeight < 40;
                chatBox.innerHTML = messages.map(m => {
                    const mine = parseInt(m.user_id, 10) === MY_ID;
                    return '<div class="chat-msg' + (mine ? ' mine' : '') + '">'
                        + '<div class="author">' + escapeHtml(m.fullname) + '</div>'
                        + '<div class="bubble">' + escapeHtml(m.message) + '</div>'
                        + '</div>';
                }).join('');
                if (chatWasAtBottom) chatBox.scrollTop = chatBox.scrollHeight;
            }

            function refresh() {
                post('state').then(res => {
                    if (res.success) render(res.data);
                }).catch(() => { /* giữ phiên polling */ });
            }

            function startPolling() {
                if (!pollTimer) pollTimer = setInterval(refresh, 4000);
            }

            function stopPolling() {
                if (pollTimer) { clearInterval(pollTimer); pollTimer = null; }
            }

            function recalcTotal() {
                if (!totalScoreEl) return;
                let total = 0;
                document.querySelectorAll('.score-input').forEach(inp => {
                    total += parseFloat(inp.value) || 0;
                });
                totalScoreEl.textContent = total.toFixed(1);
            }

            document.querySelectorAll('.score-chip-group').forEach(group => {
                const tc = group.dataset.tc;
                const input = document.querySelector('.score-input[data-tc="' + tc + '"]');
                const badge = document.querySelector('.criterion-score-badge[data-tc="' + tc + '"]');
                group.querySelectorAll('.score-chip').forEach(chip => {
                    chip.addEventListener('click', () => {
                        if (scoreLocked.value) return;
                        group.querySelectorAll('.score-chip').forEach(c => c.classList.remove('active'));
                        chip.classList.add('active');
                        if (input) input.value = chip.dataset.value;
                        if (badge) badge.textContent = chip.dataset.value;
                        recalcTotal();
                    });
                });
            });
            recalcTotal();

            if (btnSubmit) btnSubmit.addEventListener('click', function () {
                const scores = {};
                let valid = true;
                document.querySelectorAll('.score-input').forEach(inp => {
                    const tc = inp.dataset.tc;
                    const min = parseFloat(inp.dataset.min);
                    const max = parseFloat(inp.dataset.max);
                    const val = parseFloat(inp.value);
                    if (isNaN(val) || val < min || val > max) valid = false;
                    scores[tc] = val;
                });
                if (!valid) {
                    UI.warning('Vui lòng chọn điểm hợp lệ cho mọi tiêu chí.');
                    return;
                }
                const total = Object.values(scores).reduce((a, b) => a + b, 0);
                if (total < 0 || total > S_MAX) {
                    UI.warning('Tổng điểm phải nằm trong khoảng 0 – ' + S_MAX + '.');
                    return;
                }
                btnSubmit.disabled = true;
                const payload = { comment: myCommentInput ? (myCommentInput.value || '') : '', scores_json: JSON.stringify(scores) };
                post('submit_score', payload)
                    .then(res => {
                        if (res.success) {
                            if (res.data) render(res.data);
                        } else {
                            btnSubmit.disabled = false;
                            UI.error(res.message || 'Không gửi được điểm. Vui lòng thử lại.');
                        }
                    })
                    .catch(err => { btnSubmit.disabled = false; UI.networkError(err); });
            });

            const chatInput = document.getElementById('chatInput');
            const btnSendChat = document.getElementById('btnSendChat');

            function sendChat() {
                const msg = chatInput.value.trim();
                if (!msg) return;
                chatInput.value = '';
                post('send_chat', { message: msg }).then(res => {
                    if (res.success && res.data) render(res.data);
                });
            }

            btnSendChat.addEventListener('click', sendChat);
            chatInput.addEventListener('keydown', function (e) {
                if (e.key === 'Enter') { e.preventDefault(); sendChat(); }
            });

            // Khởi tạo từ state render server-side đầu tiên
            render(<?= json_encode($state, JSON_UNESCAPED_UNICODE) ?>);
            if (currentState && currentState.status !== 'agreed') startPolling();
            document.addEventListener('visibilitychange', function () {
                if (document.hidden) { stopPolling(); }
                else if (currentState && currentState.status !== 'agreed') { refresh(); startPolling(); }
            });
        })();
    </script>
</body>

</html>
