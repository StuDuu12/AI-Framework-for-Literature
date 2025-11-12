<?php
// All PHP logic MUST be before any HTML output
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../config/helper_evaluate.php';

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
    $_SESSION['error_message'] = 'Tham số không hợp lệ.';
    header("Location: /404");
    exit;
}

// STEP 1: Check if user has access to this deliver
$deliverInfo = checkDeliverAccess($userId, $deliverId);
if (!$deliverInfo) {
    $_SESSION['error_message'] = 'Bạn không có quyền truy cập phân công đánh giá này.';
    header("Location: /dashboard");
    exit;
}

// Get task info
$taskInfo = getTaskInfo($taskId);
if (!$taskInfo) {
    $_SESSION['error_message'] = 'Không tìm thấy thông tin bài tập.';
    header("Location: /dashboard");
    exit;
}

// Get response to evaluate
$responseInfo = getResponseToEvaluate($deliverId);
if (!$responseInfo) {
    $_SESSION['error_message'] = 'Không tìm thấy bài làm cần đánh giá.';
    header("Location: /dashboard");
    exit;
}

// STEP 2: Get or create evaluation record for this user
$evaluation = getOrCreateEvaluation($userId, $deliverId, $responseInfo['id_response']);
if (!$evaluation) {
    $_SESSION['error_message'] = 'Lỗi khởi tạo đánh giá.';
    header("Location: /dashboard");
    exit;
}

// Get criteria
$criteria = getEvaluationCriteria($taskId);
if (empty($criteria)) {
    $_SESSION['error_message'] = 'Chưa có tiêu chí đánh giá cho bài tập này.';
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
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php require_once './views/templates/assets.php'; ?>
    <title>Đánh giá chéo - <?= htmlspecialchars($taskInfo['title']) ?></title>
    <style>
        .response-content {
            background: #f8f9fa;
            border-left: 4px solid #2196F3;
            padding: 20px;
            margin: 15px 0;
            border-radius: 8px;
        }

        .criteria-card {
            border: 2px solid #e0e0e0;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 20px;
            transition: all 0.3s ease;
        }

        .criteria-card:hover {
            border-color: #2196F3;
            box-shadow: 0 4px 12px rgba(33, 150, 243, 0.1);
        }

        .score-input {
            width: 80px;
            text-align: center;
            font-size: 1.2rem;
            font-weight: bold;
            border: 2px solid #2196F3;
            border-radius: 8px;
            padding: 8px;
        }

        .score-range {
            color: #666;
            font-size: 0.9rem;
            margin-left: 10px;
        }

        .total-score-display {
            font-size: 2rem;
            font-weight: bold;
            color: #2196F3;
            text-align: center;
            padding: 20px;
            background: linear-gradient(135deg, #e3f2fd 0%, #bbdefb 100%);
            border-radius: 12px;
            margin: 20px 0;
        }

        .evaluation-status {
            padding: 8px 16px;
            border-radius: 20px;
            font-weight: 500;
            display: inline-block;
        }

        .status-0 {
            background: #fce4ec;
            color: #c2185b;
        }

        .status-1 {
            background: #fff3e0;
            color: #f57c00;
        }

        .status-2 {
            background: #e8f5e9;
            color: #388e3c;
        }

        .markdown-content h1,
        .markdown-content h2,
        .markdown-content h3 {
            margin-top: 20px;
            margin-bottom: 10px;
            color: #1976d2;
        }

        .markdown-content p {
            line-height: 1.8;
            margin-bottom: 15px;
        }

        .markdown-content ul,
        .markdown-content ol {
            margin-left: 25px;
            margin-bottom: 15px;
        }

        .markdown-content strong {
            color: #1565c0;
        }

        .info-badge {
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 0.85rem;
            font-weight: 500;
            margin-left: 10px;
        }

        .badge-user {
            background: #e3f2fd;
            color: #1976d2;
        }

        .badge-group {
            background: #f3e5f5;
            color: #7b1fa2;
        }
    </style>
</head>

<body>
    <?php
    require_once './views/templates/header.php';
    require_once './views/templates/sidebar.php';
    ?>

    <main id="main" class="main">
        <div class="pagetitle">
            <h1><i class="bi bi-star-fill"></i> Đánh giá chéo</h1>
            <nav>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="/">Trang chủ</a></li>
                    <li class="breadcrumb-item"><a href="/homeclass">Lớp học</a></li>
                    <li class="breadcrumb-item"><a
                            href="/class/evaluate/list?task_id=<?= $taskId ?>&topic_id=<?= $topicId ?>&phase_id=<?= $phaseId ?>">Danh
                            sách đánh giá</a></li>
                    <li class="breadcrumb-item active">Thực hiện đánh giá</li>
                </ol>
            </nav>
        </div>

        <section class="section">
            <!-- Task Info -->
            <div class="card mb-4">
                <div class="card-body">
                    <h5 class="card-title">
                        <i class="bi bi-info-circle"></i> Thông tin bài tập
                        <?php if ($deliverInfo['type'] === 'user'): ?>
                            <span class="info-badge badge-user">
                                <i class="bi bi-person"></i> Cá nhân
                            </span>
                        <?php else: ?>
                            <span class="info-badge badge-group">
                                <i class="bi bi-people"></i> Nhóm
                            </span>
                        <?php endif; ?>
                    </h5>
                    <div class="row">
                        <div class="col-md-9">
                            <p><strong>Bài tập:</strong> <?= htmlspecialchars($taskInfo['title']) ?></p>
                            <p><strong>Người làm bài:</strong> <?= htmlspecialchars($responseInfo['response_author']) ?>
                            </p>
                            <p><strong>Ngày nộp:</strong>
                                <?= date('d/m/Y H:i', strtotime($responseInfo['response_created_at'])) ?></p>
                        </div>
                        <div class="col-md-3 text-end">
                            <?php if ($evaluation['status'] == 0): ?>
                                <span class="evaluation-status status-0">
                                    <i class="bi bi-circle"></i> Nháp
                                </span>
                            <?php elseif ($evaluation['status'] == 1): ?>
                                <span class="evaluation-status status-1">
                                    <i class="bi bi-hourglass-split"></i> Đang chấm
                                </span>
                            <?php else: ?>
                                <span class="evaluation-status status-2">
                                    <i class="bi bi-check-circle"></i> Đã hoàn thành
                                </span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <!-- Left: Response Content -->
                <div class="col-lg-6">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title"><i class="bi bi-file-text"></i> Bài làm cần đánh giá</h5>

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
                                    <i class="bi bi-exclamation-triangle"></i> Chưa có nội dung bài làm
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Right: Evaluation Form -->
                <div class="col-lg-6">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title"><i class="bi bi-clipboard-check"></i> Đánh giá theo tiêu chí</h5>

                            <form id="evaluationForm" method="POST" action="/api/evaluate/submit_evaluation.php">
                                <input type="hidden" name="evaluate_id" value="<?= $evaluation['id_evaluate'] ?>">
                                <input type="hidden" name="deliver_id" value="<?= $deliverId ?>">
                                <input type="hidden" name="task_id" value="<?= $taskId ?>">
                                <input type="hidden" name="topic_id" value="<?= $topicId ?>">
                                <input type="hidden" name="phase_id" value="<?= $phaseId ?>">

                                <?php foreach ($criteria as $criterion): ?>
                                    <div class="criteria-card">
                                        <div class="d-flex justify-content-between align-items-start mb-3">
                                            <div class="flex-grow-1">
                                                <h6 class="mb-2">
                                                    <i class="bi bi-check-square"></i>
                                                    <?= htmlspecialchars($criterion['name_tc']) ?>
                                                </h6>
                                                <?php if ($criterion['description']): ?>
                                                    <p class="text-muted small mb-0">
                                                        <?= nl2br(htmlspecialchars($criterion['description'])) ?>
                                                    </p>
                                                <?php endif; ?>
                                            </div>
                                        </div>

                                        <div class="d-flex align-items-center justify-content-between">
                                            <div>
                                                <label class="form-label mb-0">Điểm:</label>
                                                <input type="number" name="scores[<?= $criterion['tc_id'] ?>]"
                                                    class="score-input" min="<?= $criterion['min_score'] ?>"
                                                    max="<?= $criterion['max_score'] ?>" step="0.5"
                                                    value="<?= isset($existingScores[$criterion['tc_id']]) && $existingScores[$criterion['tc_id']] !== null ? $existingScores[$criterion['tc_id']] : $criterion['min_score'] ?>"
                                                    <?= $evaluation['status'] == 2 ? 'readonly' : 'required' ?>>
                                                <span class="score-range">
                                                    (<?= $criterion['min_score'] ?> - <?= $criterion['max_score'] ?>)
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>

                                <!-- Total Score Display -->
                                <div class="total-score-display">
                                    <div>Tổng điểm</div>
                                    <div id="totalScore"><?= $evaluation['total_score'] ?></div>
                                </div>

                                <!-- Action Buttons -->
                                <?php if ($evaluation['status'] != 2): ?>
                                    <div class="d-flex gap-2 justify-content-end">
                                        <button type="submit" name="action" value="draft" class="btn btn-secondary">
                                            <i class="bi bi-save"></i> Lưu nháp
                                        </button>
                                        <button type="submit" name="action" value="submit" class="btn btn-primary">
                                            <i class="bi bi-send"></i> Hoàn thành đánh giá
                                        </button>
                                    </div>
                                <?php else: ?>
                                    <div class="alert alert-success">
                                        <i class="bi bi-check-circle"></i> Bạn đã hoàn thành đánh giá này.
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
        // Calculate total score on input change
        document.querySelectorAll('.score-input').forEach(input => {
            input.addEventListener('input', function () {
                let total = 0;
                document.querySelectorAll('.score-input').forEach(inp => {
                    total += parseFloat(inp.value) || 0;
                });
                document.getElementById('totalScore').textContent = total.toFixed(1);
            });
        });

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
                    alert('Vui lòng nhập điểm hợp lệ cho tất cả tiêu chí!');
                    return;
                }

                // Confirm before submit
                if (action === 'submit') {
                    if (!confirm('Bạn có chắc muốn hoàn thành đánh giá này? Sau khi hoàn thành, bạn không thể chỉnh sửa.')) {
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
                        alert('Lỗi: ' + result.message);
                    }
                } catch (error) {
                    console.error('Error:', error);
                    alert('Có lỗi xảy ra khi lưu đánh giá!');
                }
            });
        }
    </script>
</body>

</html>