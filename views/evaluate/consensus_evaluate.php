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
    $_SESSION['error_message'] = 'Invalid parameters.';
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
    $_SESSION['error_message'] = 'Task information not found.';
    header("Location: /dashboard");
    exit;
}

$responseInfo = getResponseToEvaluate($deliverId);
if (!$responseInfo) {
    $_SESSION['error_message'] = 'Submission to evaluate not found.';
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
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php require_once './views/templates/assets.php'; ?>
    <title data-lang-key="consensus_evaluation">Consensus Evaluation - Literature System</title>
</head>

<body>
    <?php
    require_once './views/templates/header.php';
    require_once './views/templates/sidebar.php';
    ?>

    <main id="main" class="main">
        <div class="pagetitle">
            <h1 data-lang-key="group_peer_evaluation">Group Peer Evaluation</h1>
            <nav>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="<?= breadcrumbClassUrl() ?>"><?= breadcrumbClassLabel() ?></a></li>
                    <li class="breadcrumb-item"><a href="<?= breadcrumbTopicPhasesUrl($topicId) ?>">Learning Phases</a></li>
                    <li class="breadcrumb-item"><a
                            href="/class/evaluate/list?task_id=<?= $taskId ?>&topic_id=<?= $topicId ?>&phase_id=<?= $phaseId ?>">Evaluation List</a></li>
                    <li class="breadcrumb-item active">Consensus Gateway</li>
                </ol>
            </nav>
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
                        <span id="statusPill" class="status-pill pending">Scoring privately</span>
                    </div>
                </div>
            </div>

            <div class="row">
                <!-- Trái: bài cần đánh giá -->
                <div class="col-lg-6">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title"><i class="bi bi-file-text"></i> <span data-lang-key="response_to_evaluate">Submission to Evaluate</span></h5>
                            <?php if (!empty($responseInfo['contents'])): ?>
                                <?php foreach ($responseInfo['contents'] as $content): ?>
                                    <div class="response-content markdown-content">
                                        <?php
                                        $html = $content['content'];
                                        $html = preg_replace('/^### (.+)$/m', '<h3>$1</h3>', $html);
                                        $html = preg_replace('/^## (.+)$/m', '<h2>$1</h2>', $html);
                                        $html = preg_replace('/^# (.+)$/m', '<h1>$1</h1>', $html);
                                        $html = preg_replace('/\*\*(.+?)\*\*/s', '<strong>$1</strong>', $html);
                                        echo nl2br($html);
                                        ?>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="alert alert-warning mb-0">Chưa có nội dung bài nộp.</div>
                            <?php endif; ?>

                            <?php if (!empty($criteria)): ?>
                                <hr>
                                <p class="mb-2"><strong>Reference rubric</strong>
                                    <span class="text-muted">(max <?= $sMax ?> points total)</span></p>
                                <div class="rubric-preview mb-2">
                                    <?php foreach ($criteria as $criterion): ?>
                                        <span class="rubric-chip" title="<?= htmlspecialchars(criteriaLabel($criterion['description'] ?? '')) ?>">
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
                            <h5 class="card-title"><i class="bi bi-incognito"></i> Step 1 — Independent scoring</h5>
                            <p class="text-muted small mb-3">
                                Score each criterion below. Your scores stay hidden until every group member submits.
                            </p>
                            <div class="consensus-step">
                                <?php if (empty($criteria)): ?>
                                    <div class="alert alert-warning mb-0">No rubric configured for this task. Ask your teacher to set evaluation criteria.</div>
                                <?php else: ?>
                                <div class="step-label">Score by criterion</div>
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
                                    <div>Your total</div>
                                    <div id="totalScore">0</div>
                                    <div class="text-muted small">/ <?= $sMax ?> max</div>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label small fw-semibold">Brief comment (optional)</label>
                                    <textarea id="myComment" class="form-control" rows="2" maxlength="500"
                                        placeholder="e.g. Strong thesis but limited textual evidence in the body..."></textarea>
                                </div>
                                <button id="btnSubmitScore" class="btn btn-primary">
                                    <i class="bi bi-lock"></i> <span id="btnSubmitLabel">Confirm scores</span>
                                </button>
                                <p class="text-muted small mt-2 mb-0" id="submitHint">
                                    After confirming, you cannot edit until all members finish scoring.
                                </p>
                                <?php endif; ?>
                            </div>

                            <div class="consensus-step">
                                <div class="step-label">Group progress</div>
                                <div id="memberList"></div>
                            </div>
                        </div>
                    </div>

                    <!-- Bước 2: thảo luận (ẩn mặc định) -->
                    <div class="card d-none" id="panelDiscuss">
                        <div class="card-body">
                            <h5 class="card-title"><i class="bi bi-chat-square-text"></i> Step 2 — Discuss to reach consensus</h5>
                            <div class="alert alert-warning py-2 small">
                                Scores still differ by more than ±<?= CONSENSUS_EPSILON ?>.
                                Discuss and adjust your criterion scores — the system finalizes when everyone is close enough.
                            </div>
                            <div class="chat-box mb-2" id="chatBox"></div>
                            <div class="input-group mb-3">
                                <input type="text" id="chatInput" class="form-control" maxlength="2000"
                                    placeholder="Explain why you gave these scores...">
                                <button class="btn btn-outline-primary" id="btnSendChat"><i class="bi bi-send"></i></button>
                            </div>
                        </div>
                    </div>

                    <!-- Bước 3: kết quả -->
                    <div class="card d-none" id="panelAgreed">
                        <div class="card-body">
                            <h5 class="card-title"><i class="bi bi-check2-circle"></i> Consensus reached</h5>
                            <div class="final-score-box mb-3">
                                <div class="text-muted small">Group consensus score</div>
                                <div class="value"><span id="finalScore">–</span> / <?= $sMax ?></div>
                            </div>
                            <p class="text-muted small mb-0">
                                This score is recorded for every group member. Accuracy (S_Acc) is calculated
                                after the teacher completes final grading.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <?php
    require_once 'views/templates/footer.php';
    require_once 'views/templates/vendors.php';
    ?>

    <script>
        (function () {
            const API = '/api/evaluate/consensus.php';
            const DELIVER_ID = <?= (int)$deliverId ?>;
            const MY_ID = <?= (int)$userId ?>;
            const S_MAX = <?= (float)$sMax ?>;

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
                    pending: ['pending', 'Private scoring (' + state.submitted_count + '/' + state.member_count + ')'],
                    discussing: ['discussing', 'Discussing to reach consensus'],
                    agreed: ['agreed', 'Consensus reached'],
                    timeout: ['pending', 'Timed out — awaiting teacher']
                };
                const [cls, label] = labels[state.status] || labels.pending;
                statusPill.className = 'status-pill ' + cls;
                statusPill.textContent = label;

                // Member list
                memberList.innerHTML = state.members.map(m => {
                    const reveal = state.status !== 'pending';
                    let right;
                    if (!m.submitted) {
                        right = '<span class="text-muted small">Not submitted</span>';
                    } else if (reveal || m.is_me) {
                        right = '<span class="member-score">' + (m.score != null ? m.score : '·') + '</span>';
                    } else {
                        right = '<span class="text-success small"><i class="bi bi-check-lg"></i> Submitted</span>';
                    }
                    const reason = (reveal && m.comment)
                        ? '<span class="member-reason">' + escapeHtml(m.comment) + '</span>'
                        : '<span class="member-reason"></span>';
                    return '<div class="member-row' + (m.is_me ? ' is-me' : '') + '">'
                        + '<span class="small fw-semibold">' + escapeHtml(m.fullname) + (m.is_me ? ' (you)' : '') + '</span>'
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
                        if (btnSubmitLabel) btnSubmitLabel.textContent = 'Submitted — waiting for group';
                        if (submitHint) submitHint.textContent = 'Waiting for other members to finish scoring.';
                    } else {
                        setScoringEnabled(true);
                    }
                } else if (state.status === 'discussing') {
                    panelScore.classList.remove('d-none');
                    panelAgreed.classList.add('d-none');
                    panelDiscuss.classList.remove('d-none');
                    setScoringEnabled(true);
                    if (btnSubmitLabel) btnSubmitLabel.textContent = 'Update my scores';
                    if (submitHint) submitHint.textContent = 'Adjust scores during discussion. The system finalizes when everyone is close enough.';
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

                recalcTotal();
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
                    const msg = 'Please enter a valid score for every criterion.';
                    if (window.Toast) Toast.error(msg); else alert(msg);
                    return;
                }
                const total = Object.values(scores).reduce((a, b) => a + b, 0);
                if (total < 0 || total > S_MAX) {
                    const msg = 'Total must be between 0 and ' + S_MAX;
                    if (window.Toast) Toast.error(msg); else alert(msg);
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
                            if (window.Toast) Toast.error(res.message); else alert(res.message);
                        }
                    })
                    .catch(() => { btnSubmit.disabled = false; });
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
            startPolling();
        })();
    </script>
</body>

</html>
