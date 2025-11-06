<?php
/**
 * TRANG DANH SÁCH ĐÁNH GIÁ CHÉO
 * 
 * Mục đích: Hiển thị tất cả các bài mà học sinh được phân công đánh giá
 * 
 * Tính năng:
 * - Xem danh sách tất cả bài được giao chấm
 * - Hiển thị trạng thái: Chưa chấm / Nháp / Đang chấm / Đã hoàn thành
 * - Hiển thị điểm số (nếu đã hoàn thành)
 * - Progress bar thể hiện tiến độ hoàn thành
 * - Button "Bắt đầu" hoặc "Tiếp tục" đánh giá
 * 
 * URL: /class/evaluate/list?task_id=X&topic_id=Y&phase_id=Z
 */
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../config/helper_evaluate.php';
require_once __DIR__ . '/../../config/helper_homework.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in
if (!isset($_SESSION['user']['user_id'])) {
    header("Location: /account/login");
    exit;
}

$userId = $_SESSION['user']['user_id'];
$taskId = isset($_GET['task_id']) ? intval($_GET['task_id']) : 0;
$topicId = isset($_GET['topic_id']) ? intval($_GET['topic_id']) : 0;
$phaseId = isset($_GET['phase_id']) ? intval($_GET['phase_id']) : 0;

if (!$taskId) {
    header("Location: /404");
    exit;
}

// Get task info
$taskInfo = getInfoTask($taskId);
if (!$taskInfo) {
    header("Location: /404");
    exit;
}

// Get assigned evaluations
$assignments = getAssignedEvaluations($userId, $taskId);
?>
<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php require_once './views/templates/assets.php'; ?>
    <title>Danh sách đánh giá</title>
    <style>
        .assignment-card {
            border: 2px solid #e0e0e0;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 15px;
            transition: all 0.3s ease;
        }

        .assignment-card:hover {
            border-color: #2196F3;
            box-shadow: 0 4px 12px rgba(33, 150, 243, 0.15);
            transform: translateY(-2px);
        }

        .status-badge {
            padding: 6px 14px;
            border-radius: 20px;
            font-weight: 500;
            font-size: 0.875rem;
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

        .score-display {
            font-size: 1.5rem;
            font-weight: bold;
            color: #2196F3;
        }

        .progress-ring {
            width: 60px;
            height: 60px;
        }

        .progress-text {
            font-size: 1.2rem;
            font-weight: bold;
        }
    </style>
</head>

<body>
    <?php
    require_once './views/templates/header.php';
    if (isset($_SESSION['user']['role']) && $_SESSION['user']['role'] === 'teacher') {
        require_once './views/templates/sidebar_teacher.php';
    } else if (isset($_SESSION['user']['role']) && $_SESSION['user']['role'] === 'student') {
        require_once './views/templates/sidebar_student.php';
    }
    ?>

    <main id="main" class="main">
        <div class="pagetitle">
            <h1><i class="bi bi-list-check"></i> Danh sách bài cần đánh giá</h1>
            <nav>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="/">Trang chủ</a></li>
                    <li class="breadcrumb-item"><a href="/homeclass">Lớp học</a></li>
                    <li class="breadcrumb-item active">Đánh giá</li>
                </ol>
            </nav>
        </div>

        <section class="section">
            <!-- Task Info -->
            <div class="card mb-4">
                <div class="card-body">
                    <h5 class="card-title"><i class="bi bi-info-circle"></i> Thông tin bài tập</h5>
                    <div class="row">
                        <div class="col-md-8">
                            <p><strong>Tên bài tập:</strong> <?= htmlspecialchars($taskInfo['task_title']) ?></p>
                            <p><strong>Mô tả:</strong>
                                <?= htmlspecialchars($taskInfo['task_description'] ?? 'Không có mô tả') ?></p>
                        </div>
                        <div class="col-md-4 text-end">
                            <?php
                            $totalAssigned = count($assignments);
                            $completed = 0;
                            foreach ($assignments as $assignment) {
                                if ($assignment['eval_status'] == 2) {
                                    $completed++;
                                }
                            }
                            $percentage = $totalAssigned > 0 ? round(($completed / $totalAssigned) * 100) : 0;
                            ?>
                            <div class="d-flex align-items-center justify-content-end gap-3">
                                <div>
                                    <div class="progress-text text-primary"><?= $completed ?> / <?= $totalAssigned ?>
                                    </div>
                                    <small class="text-muted">Đã hoàn thành</small>
                                </div>
                                <div class="progress-ring">
                                    <svg width="60" height="60">
                                        <circle cx="30" cy="30" r="25" fill="none" stroke="#e0e0e0" stroke-width="5">
                                        </circle>
                                        <circle cx="30" cy="30" r="25" fill="none" stroke="#2196F3" stroke-width="5"
                                            stroke-dasharray="<?= $percentage * 1.57 ?> 157" stroke-dashoffset="0"
                                            transform="rotate(-90 30 30)"></circle>
                                        <text x="30" y="35" text-anchor="middle" font-size="12" font-weight="bold"
                                            fill="#2196F3">
                                            <?= $percentage ?>%
                                        </text>
                                    </svg>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Assignments List -->
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title"><i class="bi bi-clipboard-check"></i> Bài cần đánh giá</h5>

                    <?php if (empty($assignments)): ?>
                        <div class="alert alert-info">
                            <i class="bi bi-info-circle"></i> Bạn chưa được phân công đánh giá bài nào.
                        </div>
                    <?php else: ?>
                        <?php foreach ($assignments as $assignment): ?>
                            <div class="assignment-card">
                                <div class="row align-items-center">
                                    <div class="col-md-6">
                                        <h6 class="mb-2">
                                            <i class="bi bi-person-circle"></i>
                                            <?= htmlspecialchars($assignment['response_author']) ?>
                                        </h6>
                                        <p class="text-muted mb-1 small">
                                            <i class="bi bi-file-text"></i>
                                            <?= htmlspecialchars(mb_substr($assignment['response_content'] ?? 'Không có nội dung', 0, 100)) ?>...
                                        </p>
                                        <small class="text-muted">
                                            <i class="bi bi-clock"></i>
                                            Nộp lúc: <?= date('d/m/Y H:i', strtotime($assignment['created_at'] ?? 'now')) ?>
                                        </small>
                                    </div>

                                    <div class="col-md-3 text-center">
                                        <?php if ($assignment['eval_status'] !== null): ?>
                                            <span class="status-badge status-<?= $assignment['eval_status'] ?>">
                                                <?php if ($assignment['eval_status'] == 0): ?>
                                                    <i class="bi bi-circle"></i> Nháp
                                                <?php elseif ($assignment['eval_status'] == 1): ?>
                                                    <i class="bi bi-hourglass-split"></i> Đang chấm
                                                <?php else: ?>
                                                    <i class="bi bi-check-circle"></i> Đã hoàn thành
                                                <?php endif; ?>
                                            </span>
                                            <?php if ($assignment['eval_status'] == 2): ?>
                                                <div class="score-display mt-2">
                                                    <i class="bi bi-star-fill"></i>
                                                    <?= number_format($assignment['total_score'], 1) ?>
                                                </div>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            <span class="status-badge status-0">
                                                <i class="bi bi-exclamation-circle"></i> Chưa chấm
                                            </span>
                                        <?php endif; ?>
                                    </div>

                                    <div class="col-md-3 text-end">
                                        <?php if ($assignment['eval_status'] == 2): ?>
                                            <a href="/class/evaluate/student?task_id=<?= $taskId ?>&topic_id=<?= $topicId ?>&phase_id=<?= $phaseId ?>&deliver_id=<?= $assignment['id_deliver'] ?>"
                                                class="btn btn-outline-primary">
                                                <i class="bi bi-eye"></i> Xem đánh giá
                                            </a>
                                        <?php else: ?>
                                            <a href="/class/evaluate/student?task_id=<?= $taskId ?>&topic_id=<?= $topicId ?>&phase_id=<?= $phaseId ?>&deliver_id=<?= $assignment['id_deliver'] ?>"
                                                class="btn btn-primary">
                                                <i class="bi bi-pencil-square"></i>
                                                <?= $assignment['eval_status'] !== null ? 'Tiếp tục' : 'Bắt đầu' ?> đánh giá
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </section>
    </main>

    <?php
    require_once 'views/templates/footer.php';
    require_once 'views/templates/vendors.php';
    ?>
</body>

</html>