<?php
// All PHP logic MUST be before any HTML output
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../config/helper_evaluate.php';
require_once __DIR__ . '/../../config/helper_breadcrumb.php';
require_once __DIR__ . '/../../config/helper_phase.php';
require_once __DIR__ . '/../../config/helper_task.php';
require_once __DIR__ . '/../../config/helper_criteria_i18n.php';

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
    $_SESSION['error_message'] = 'Invalid parameters.';
    header("Location: /404");
    exit;
}

// STEP 1: Check if user has access to this deliver
$deliverInfo = checkDeliverAccess($userId, $deliverId);
if (!$deliverInfo) {
    $_SESSION['error_message'] = 'You do not have access to this evaluation assignment.';
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
    $_SESSION['error_message'] = 'Task information not found.';
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
    $_SESSION['error_message'] = 'Submission to evaluate not found.';
    header("Location: /dashboard");
    exit;
}

// STEP 2: Get or create evaluation record for this user
$evaluation = getOrCreateEvaluation($userId, $deliverId, $responseInfo['id_response']);
if (!$evaluation) {
    $_SESSION['error_message'] = 'Error initializing evaluation.';
    header("Location: /dashboard");
    exit;
}

// Get criteria
$criteria = getEvaluationCriteria($taskId);
$phaseInfo = getPhaseDetails($phaseId);
$topicInfo = getTopicDetails($topicId);
if (empty($criteria)) {
    $_SESSION['error_message'] = 'No evaluation criteria set for this task yet.';
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
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php require_once './views/templates/assets.php'; ?>
    <title data-lang-key="peer_evaluation">Peer Evaluation - Literature System</title>
</head>

<body>
    <?php
    require_once './views/templates/header.php';
    require_once './views/templates/sidebar.php';
    ?>

    <main id="main" class="main">
        <div class="pagetitle">
            <h1><i class="bi bi-star-fill"></i> <span data-lang-key="peer_evaluation">Peer Evaluation</span></h1>
            <nav>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="<?= breadcrumbClassUrl() ?>"><?= breadcrumbClassLabel() ?></a></li>
                    <?php if ($topicInfo): ?>
                    <li class="breadcrumb-item"><a href="<?= breadcrumbClassUrl() ?>"><?= htmlspecialchars($topicInfo['topic_name']) ?></a></li>
                    <?php endif; ?>
                    <li class="breadcrumb-item"><a href="<?= breadcrumbTopicPhasesUrl($topicId) ?>">Learning Phases</a></li>
                    <li class="breadcrumb-item"><a href="<?= breadcrumbPhaseTasksUrl($topicId, $phaseId) ?>">Tasks<?= $phaseInfo ? ' - Step ' . (int)$phaseInfo['phase_number'] : '' ?></a></li>
                    <li class="breadcrumb-item"><a href="<?= URL_ROOT ?>/class/evaluate/list?task_id=<?= $taskId ?>&topic_id=<?= $topicId ?>&phase_id=<?= $phaseId ?>">Peer Evaluation</a></li>
                    <li class="breadcrumb-item active">Score</li>
                </ol>
            </nav>
        </div>

        <section class="section">
            <!-- Task Info -->
            <div class="card mb-4">
                <div class="card-body">
                    <h5 class="card-title">
                        <i class="bi bi-info-circle"></i> <span data-lang-key="task_info_label">Task Info</span>
                        <?php if ($deliverInfo['type'] === 'user'): ?>
                            <span class="info-badge badge-user">
                                <i class="bi bi-person"></i> <span>Personal</span>
                            </span>
                        <?php else: ?>
                            <span class="info-badge badge-group">
                                <i class="bi bi-people"></i> <span>Group</span>
                            </span>
                        <?php endif; ?>
                    </h5>
                    <div class="row">
                        <div class="col-md-9">
                            <p><strong>Task:</strong> <?= htmlspecialchars($taskInfo['title']) ?></p>
                            <p><strong>Respondent:</strong>
                                <?= $isAnonymous ? '<span class="text-muted fst-italic">Ẩn danh</span>' : htmlspecialchars($responseInfo['response_author']) ?>
                            </p>
                            <p><strong>Submission Date:</strong>
                                <?= date('d/m/Y H:i', strtotime($responseInfo['response_created_at'])) ?></p>
                        </div>
                        <div class="col-md-3 text-end">
                            <?php if ($evaluation['status'] == 0): ?>
                                <span class="evaluation-status status-0">
                                    <i class="bi bi-circle"></i> <span>Draft</span>
                                </span>
                            <?php elseif ($evaluation['status'] == 1): ?>
                                <span class="evaluation-status status-1">
                                    <i class="bi bi-hourglass-split"></i> <span>Grading</span>
                                </span>
                            <?php else: ?>
                                <span class="evaluation-status status-2">
                                    <i class="bi bi-check-circle"></i> <span>Completed</span>
                                </span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row g-3">
                <!-- Left: Response Content -->
                <div class="col-lg-7">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title"><i class="bi bi-file-text"></i> <span data-lang-key="response_to_evaluate">Submission to Evaluate</span></h5>

                            <?php if (!empty($responseInfo['contents'])): ?>
                                <?php foreach ($responseInfo['contents'] as $content): ?>
                                    <div class="response-content markdown-content">
                                        <?php
                                        // Simple markdown to HTML conversion
                                        $html = $content['content'];

                                        // Headers
                                        $html = preg_replace('/^### (.+)$/m', '<h3>$1</h3>', $html);
                                        $html = preg_replace('/^## (.+)$/m', '<h2>$1</h2>', $html);
                                        $html = preg_replace('/^# (.+)$/m', '<h1>$1</h1>', $html);

                                        // Bold
                                        $html = preg_replace('/\*\*(.+?)\*\*/s', '<strong>$1</strong>', $html);

                                        // Line breaks
                                        $html = nl2br($html);

                                        echo $html;
                                        ?>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="alert alert-warning">
                                    <i class="bi bi-exclamation-triangle"></i> <span>No response content yet</span>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Right: Evaluation Form (compact accordion) -->
                <div class="col-lg-5">
                    <div class="card eval-compact sticky-top" style="top: 80px;">
                        <div class="card-body">
                            <h6 class="card-title mb-2"><i class="bi bi-clipboard-check"></i> Score by criterion</h6>

                            <form id="evaluationForm" method="POST" action="/api/evaluate/submit_evaluation.php">
                                <input type="hidden" name="evaluate_id" value="<?= $evaluation['id_evaluate'] ?>">
                                <input type="hidden" name="deliver_id" value="<?= $deliverId ?>">
                                <input type="hidden" name="task_id" value="<?= $taskId ?>">
                                <input type="hidden" name="topic_id" value="<?= $topicId ?>">
                                <input type="hidden" name="phase_id" value="<?= $phaseId ?>">

                                <div class="accordion eval-criteria-accordion" id="criteriaAccordion">
                                <?php foreach ($criteria as $i => $criterion):
                                    $tcId = $criterion['tc_id'];
                                    $cur = isset($existingScores[$tcId]) && $existingScores[$tcId] !== null ? $existingScores[$tcId] : $criterion['min_score'];
                                    $min = (float)$criterion['min_score'];
                                    $max = (float)$criterion['max_score'];
                                    $step = ($max - $min) <= 4 ? 1 : 0.5;
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
                                                <?php if ($criterion['description']): ?><p class="small text-muted mb-2"><?= htmlspecialchars(criteriaLabel($criterion['description'])) ?></p><?php endif; ?>
                                                <?php if ($evaluation['status'] != 2): ?>
                                                <div class="score-chip-group" data-tc="<?= $tcId ?>">
                                                <?php for ($s = $min; $s <= $max + 0.001; $s += $step): ?>
                                                    <button type="button" class="score-chip <?= abs((float)$cur - $s) < 0.01 ? 'active' : '' ?>" data-value="<?= $step >= 1 ? (int)$s : number_format($s, 1) ?>"><?= $step >= 1 ? (int)$s : number_format($s, 1) ?></button>
                                                <?php endfor; ?>
                                                </div>
                                                <?php endif; ?>
                                                <input type="hidden" name="scores[<?= $tcId ?>]" class="score-input" data-tc="<?= $tcId ?>" data-min="<?= $min ?>" data-max="<?= $max ?>" value="<?= $cur ?>" <?= $evaluation['status'] == 2 ? 'readonly' : '' ?>>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                                </div>

                                <!-- Total Score Display -->
                                <div class="total-score-display">
                                    <div>Total Score</div>
                                    <div id="totalScore"><?= $evaluation['total_score'] ?></div>
                                </div>

                                <!-- Action Buttons -->
                                <?php if ($evaluation['status'] != 2): ?>
                                    <div class="d-flex gap-2 justify-content-end">
                                        <button type="submit" name="action" value="draft" class="btn btn-secondary">
                                            <i class="bi bi-save"></i> <span>Save Draft</span>
                                        </button>
                                        <button type="submit" name="action" value="submit" class="btn btn-primary">
                                            <i class="bi bi-send"></i> <span>Complete evaluation</span>
                                        </button>
                                    </div>
                                <?php else: ?>
                                    <div class="alert alert-success">
                                        <i class="bi bi-check-circle"></i> <span>You have completed this evaluation.</span>
                                    </div>
                                <?php endif; ?>
                            </form>
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
        document.querySelectorAll('.score-chip-group').forEach(group => {
            const tc = group.dataset.tc;
            const input = document.querySelector('.score-input[data-tc="' + tc + '"]');
            const badge = document.querySelector('.criterion-score-badge[data-tc="' + tc + '"]');
            group.querySelectorAll('.score-chip').forEach(chip => {
                chip.addEventListener('click', () => {
                    if (!input || input.readOnly) return;
                    group.querySelectorAll('.score-chip').forEach(c => c.classList.remove('active'));
                    chip.classList.add('active');
                    input.value = chip.dataset.value;
                    if (badge) badge.textContent = chip.dataset.value;
                    recalcTotal();
                });
            });
        });

        function recalcTotal() {
            let total = 0;
            document.querySelectorAll('.score-input').forEach(inp => {
                total += parseFloat(inp.value) || 0;
            });
            document.getElementById('totalScore').textContent = total.toFixed(1);
        }

        // Form submission
        const form = document.getElementById('evaluationForm');
        if (form) {
            form.addEventListener('submit', async function (e) {
                e.preventDefault();

                const formData = new FormData(this);
                const action = e.submitter.value; // 'draft' or 'submit'

                // Validate scores
                let isValid = true;
                document.querySelectorAll('.score-input').forEach(input => {
                    const min = parseFloat(input.min);
                    const max = parseFloat(input.max);
                    const value = parseFloat(input.value);

                    if (value < min || value > max) {
                        isValid = false;
                        input.classList.add('is-invalid');
                    } else {
                        input.classList.remove('is-invalid');
                    }
                });

                if (!isValid) {
                    alert('Please enter valid scores for all criteria!');
                    return;
                }

                // Confirm before submit
                if (action === 'submit') {
                    if (!confirm('Are you sure you want to complete this evaluation? Once completed, you cannot edit it.')) {
                        return;
                    }
                }

                try {
                    const response = await fetch(this.action, {
                        method: 'POST',
                        body: formData
                    });

                    const result = await response.json();

                    if (result.success) {
                        alert(result.message);
                        if (action === 'submit') {
                            // Redirect back to evaluation list
                            window.location.href = '/class/evaluate/list?task_id=<?= $taskId ?>&topic_id=<?= $topicId ?>&phase_id=<?= $phaseId ?>';
                        } else {
                            // Reload to show updated status
                            location.reload();
                        }
                    } else {
                        alert('Error: ' + result.message);
                    }
                } catch (error) {
                    console.error('Error:', error);
                    alert('An error occurred while saving the evaluation!');
                }
            });
        }
    </script>
</body>

</html>