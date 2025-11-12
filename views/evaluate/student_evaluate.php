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
    // Get detailed error from session if available
    $errorMsg = 'Lỗi khởi tạo đánh giá.';

    if (isset($_SESSION['last_db_error'])) {
        $errorMsg .= '<br><strong>Chi tiết lỗi:</strong> ' . $_SESSION['last_db_error'];
        unset($_SESSION['last_db_error']); // Clear after reading
    }

    $_SESSION['error_message'] = $errorMsg;
    error_log("EVALUATION INIT FAILED - User: $userId, Deliver: $deliverId, Response: {$responseInfo['id_response']}");
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
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Open+Sans:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/assets/css/comment.css">
    <style>
        /* Main Layout */
        body {
            font-family: 'Open Sans', sans-serif;
            background: #f5f7fa;
        }

        .main {
            background: #f5f7fa;
        }

        /* Content Display Area */
        .content-display-container {
            position: relative;
            padding: 20px;
            background: #ffffff;
            border-radius: 16px;
            box-shadow: 0 2px 20px rgba(0, 0, 0, 0.08);
        }

        /* Content Block Container - gắn ảnh và comment lại với nhau */
        .content-block-with-comments {
            background: #ffffff;
            border-radius: 20px;
            padding: 24px;
            margin-bottom: 40px;
            /* Khoảng cách lớn giữa các block */
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            border: 3px solid transparent;
        }

        .content-block-with-comments:hover {
            box-shadow: 0 8px 32px rgba(33, 150, 243, 0.15);
            border-color: rgba(33, 150, 243, 0.2);
        }

        .content-block-with-comments.selected {
            border: 3px solid #2196F3;
            box-shadow: 0 8px 40px rgba(33, 150, 243, 0.25);
            background: linear-gradient(135deg, #ffffff 0%, #f8fbff 100%);
        }

        .content-image-wrapper {
            cursor: pointer;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            border-radius: 12px;
            padding: 0;
            margin-bottom: 0;
            /* Không có margin vì đã có trong parent */
            background: transparent;
            position: relative;
        }

        .content-image-wrapper:hover {
            transform: translateY(-2px);
        }

        .content-image-wrapper img {
            max-width: 100%;
            height: auto;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
            transition: transform 0.3s ease;
        }

        .content-image-wrapper:hover img {
            transform: scale(1.01);
        }

        /* Score Panel - Sticky at Top Right */
        .score-panel {
            position: relative;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 20px;
            padding: 30px;
            color: white;
            box-shadow: 0 10px 40px rgba(102, 126, 234, 0.3);
            margin-bottom: 30px;
            z-index: 100;
        }

        .score-panel-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 24px;
            padding-bottom: 20px;
            border-bottom: 2px solid rgba(255, 255, 255, 0.2);
        }

        .score-panel-title {
            font-size: 1.5rem;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .total-score-badge {
            background: rgba(255, 255, 255, 0.25);
            backdrop-filter: blur(10px);
            padding: 20px 30px;
            border-radius: 16px;
            text-align: center;
            border: 2px solid rgba(255, 255, 255, 0.3);
        }

        .total-score-label {
            font-size: 0.9rem;
            opacity: 0.9;
            margin-bottom: 8px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .total-score-value {
            font-size: 3rem;
            font-weight: 800;
            line-height: 1;
            text-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        /* Criteria Horizontal Layout */
        .criteria-quick-list-horizontal {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            margin-bottom: 20px;
        }

        .criteria-quick-item-horizontal {
            background: rgba(255, 255, 255, 0.15);
            backdrop-filter: blur(10px);
            padding: 16px 20px;
            border-radius: 12px;
            display: flex;
            flex-direction: column;
            gap: 8px;
            border: 2px solid transparent;
            transition: all 0.3s ease;
            flex: 1 1 calc(33.333% - 12px);
            min-width: 200px;
        }

        .criteria-quick-item-horizontal:hover {
            background: rgba(255, 255, 255, 0.25);
            border-color: rgba(255, 255, 255, 0.4);
            transform: translateY(-2px);
        }

        .criteria-quick-name {
            font-size: 0.9rem;
            font-weight: 600;
            line-height: 1.4;
        }

        .criteria-quick-score {
            display: flex;
            align-items: center;
            gap: 8px;
            font-weight: 700;
            font-size: 1.1rem;
        }

        .score-input-compact {
            width: 70px;
            text-align: center;
            font-size: 1.2rem;
            font-weight: 700;
            border: 2px solid rgba(255, 255, 255, 0.5);
            background: rgba(255, 255, 255, 0.95);
            color: #667eea;
            border-radius: 10px;
            padding: 8px;
            transition: all 0.3s ease;
        }

        .score-input-compact:focus {
            outline: none;
            border-color: #ffffff;
            box-shadow: 0 0 0 4px rgba(255, 255, 255, 0.2);
            transform: scale(1.05);
        }

        .score-range-compact {
            font-size: 0.75rem;
            opacity: 0.8;
            white-space: nowrap;
        }

        /* Action Buttons */
        .action-buttons-vertical {
            display: flex;
            flex-direction: column;
            gap: 10px;
            margin-top: 10px;
        }

        .action-buttons-fixed {
            display: flex;
            gap: 12px;
            margin-top: 20px;
        }

        .btn-action {
            flex: 1;
            padding: 12px 20px;
            border-radius: 12px;
            font-weight: 600;
            font-size: 0.95rem;
            border: none;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .btn-draft {
            background: rgba(255, 255, 255, 0.25);
            color: white;
            border: 2px solid rgba(255, 255, 255, 0.4);
        }

        .btn-draft:hover {
            background: rgba(255, 255, 255, 0.35);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        }

        .btn-submit {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            color: white;
            box-shadow: 0 4px 15px rgba(245, 87, 108, 0.4);
        }

        .btn-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(245, 87, 108, 0.5);
        }

        .btn-detail {
            width: 100%;
            padding: 10px;
            background: rgba(255, 255, 255, 0.2);
            color: white;
            border: 2px solid rgba(255, 255, 255, 0.3);
            border-radius: 12px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            font-size: 0.9rem;
        }

        .btn-detail:hover {
            background: rgba(255, 255, 255, 0.3);
            border-color: rgba(255, 255, 255, 0.5);
        }

        /* Modal for Detailed Criteria */
        .modal-criteria {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.7);
            backdrop-filter: blur(8px);
            z-index: 9999;
            animation: fadeIn 0.3s ease;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
            }

            to {
                opacity: 1;
            }
        }

        .modal-criteria.active {
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .modal-content-criteria {
            background: white;
            border-radius: 24px;
            max-width: 800px;
            width: 100%;
            max-height: 90vh;
            overflow: hidden;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            animation: slideUp 0.3s ease;
        }

        @keyframes slideUp {
            from {
                transform: translateY(50px);
                opacity: 0;
            }

            to {
                transform: translateY(0);
                opacity: 1;
            }
        }

        .modal-header-criteria {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .modal-title-criteria {
            font-size: 1.8rem;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .modal-close {
            background: rgba(255, 255, 255, 0.2);
            border: none;
            color: white;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            cursor: pointer;
            font-size: 1.5rem;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s ease;
        }

        .modal-close:hover {
            background: rgba(255, 255, 255, 0.3);
            transform: rotate(90deg);
        }

        .modal-body-criteria {
            padding: 30px;
            max-height: calc(90vh - 200px);
            overflow-y: auto;
        }

        .criteria-detail-card {
            background: linear-gradient(135deg, #f8f9fa 0%, #ffffff 100%);
            border: 2px solid #e9ecef;
            border-radius: 16px;
            padding: 24px;
            margin-bottom: 20px;
            transition: all 0.3s ease;
        }

        .criteria-detail-card:hover {
            border-color: #667eea;
            box-shadow: 0 4px 20px rgba(102, 126, 234, 0.15);
            transform: translateY(-2px);
        }

        .criteria-detail-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 16px;
            gap: 20px;
        }

        .criteria-detail-name {
            flex: 1;
            font-size: 1.2rem;
            font-weight: 700;
            color: #2c3e50;
            line-height: 1.4;
        }

        .criteria-detail-score-box {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 8px;
            min-width: 120px;
        }

        .criteria-detail-score-input {
            width: 100px;
            text-align: center;
            font-size: 1.5rem;
            font-weight: 800;
            border: 3px solid #667eea;
            background: white;
            color: #667eea;
            border-radius: 12px;
            padding: 12px;
            transition: all 0.3s ease;
        }

        .criteria-detail-score-input:focus {
            outline: none;
            border-color: #764ba2;
            box-shadow: 0 0 0 4px rgba(102, 126, 234, 0.2);
            transform: scale(1.05);
        }

        .criteria-detail-range {
            font-size: 0.85rem;
            color: #6c757d;
            font-weight: 600;
        }

        .criteria-detail-description {
            color: #495057;
            line-height: 1.8;
            font-size: 0.95rem;
            padding: 16px;
            background: rgba(102, 126, 234, 0.05);
            border-radius: 12px;
            border-left: 4px solid #667eea;
        }

        /* Comment Section - Using styles from comment.css */
        .fb-comment-block {
            margin-top: 16px;
            /* Khoảng cách nhỏ với ảnh để gắn liền */
            padding-top: 16px;
            border-top: 2px solid #e9ecef;
            /* Đường kẻ phân cách ảnh và comment */
            animation: slideDown 0.3s ease;
        }

        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Status Badge */
        .evaluation-status {
            padding: 10px 20px;
            border-radius: 25px;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-size: 0.9rem;
        }

        .status-0 {
            background: linear-gradient(135deg, #fce4ec 0%, #f8bbd0 100%);
            color: #c2185b;
        }

        .status-1 {
            background: linear-gradient(135deg, #fff3e0 0%, #ffe0b2 100%);
            color: #f57c00;
        }

        .status-2 {
            background: linear-gradient(135deg, #e8f5e9 0%, #c8e6c9 100%);
            color: #388e3c;
        }

        /* Info Badge */
        .info-badge {
            padding: 6px 16px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
            margin-left: 10px;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        .badge-user {
            background: linear-gradient(135deg, #e3f2fd 0%, #bbdefb 100%);
            color: #1976d2;
        }

        .badge-group {
            background: linear-gradient(135deg, #f3e5f5 0%, #e1bee7 100%);
            color: #7b1fa2;
        }

        /* Scrollbar Styling */
        .modal-body-criteria::-webkit-scrollbar {
            width: 10px;
        }

        .modal-body-criteria::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 10px;
        }

        .modal-body-criteria::-webkit-scrollbar-thumb {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 10px;
        }

        .modal-body-criteria::-webkit-scrollbar-thumb:hover {
            background: linear-gradient(135deg, #764ba2 0%, #667eea 100%);
        }

        /* Responsive */
        @media (max-width: 992px) {
            .score-panel {
                position: relative;
                top: 0;
            }

            .total-score-value {
                font-size: 2.5rem;
            }

            .criteria-quick-item-horizontal {
                flex: 1 1 calc(50% - 12px);
            }
        }

        @media (max-width: 768px) {
            .criteria-quick-item-horizontal {
                flex: 1 1 100%;
            }

            .total-score-value {
                font-size: 2rem;
            }

            .col-md-2,
            .col-md-8 {
                margin-bottom: 20px;
            }
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
                <!-- Left: Response Content with Comments -->
                <div class="col-lg-12">
                    <!-- Score Panel (Full Width Above Content) -->
                    <div class="score-panel mb-4">
                        <div class="score-panel-header">
                            <div class="score-panel-title">
                                <i class="bi bi-clipboard-check-fill"></i>
                                Đánh giá
                            </div>
                            <?php if ($evaluation['status'] == 0): ?>
                                <span class="evaluation-status status-0">
                                    <i class="bi bi-circle-fill"></i> Nháp
                                </span>
                            <?php elseif ($evaluation['status'] == 1): ?>
                                <span class="evaluation-status status-1"
                                    style="background: rgba(255,255,255,0.25); color: white;">
                                    <i class="bi bi-hourglass-split"></i> Đang chấm
                                </span>
                            <?php else: ?>
                                <span class="evaluation-status status-2"
                                    style="background: rgba(255,255,255,0.25); color: white;">
                                    <i class="bi bi-check-circle-fill"></i> Hoàn thành
                                </span>
                            <?php endif; ?>
                        </div>

                        <div class="row align-items-center">
                            <!-- Total Score Badge -->
                            <div class="col-md-2">
                                <div class="total-score-badge">
                                    <div class="total-score-label">Tổng điểm</div>
                                    <div class="total-score-value" id="totalScoreDisplay">
                                        <?= $evaluation['total_score'] ?></div>
                                </div>
                            </div>

                            <!-- Criteria Quick List -->
                            <div class="col-md-8">
                                <form id="evaluationForm" method="POST" action="/api/evaluate/submit_evaluation.php">
                                    <input type="hidden" name="evaluate_id" value="<?= $evaluation['id_evaluate'] ?>">
                                    <input type="hidden" name="deliver_id" value="<?= $deliverId ?>">
                                    <input type="hidden" name="task_id" value="<?= $taskId ?>">
                                    <input type="hidden" name="topic_id" value="<?= $topicId ?>">
                                    <input type="hidden" name="phase_id" value="<?= $phaseId ?>">

                                    <div class="criteria-quick-list-horizontal">
                                        <?php foreach ($criteria as $criterion): ?>
                                            <div class="criteria-quick-item-horizontal">
                                                <div class="criteria-quick-name">
                                                    <?= htmlspecialchars($criterion['name_tc']) ?>
                                                </div>
                                                <div class="criteria-quick-score">
                                                    <input type="number" name="scores[<?= $criterion['tc_id'] ?>]"
                                                        class="score-input-compact score-input"
                                                        data-tc-id="<?= $criterion['tc_id'] ?>"
                                                        min="<?= $criterion['min_score'] ?>"
                                                        max="<?= $criterion['max_score'] ?>" step="0.5"
                                                        value="<?= isset($existingScores[$criterion['tc_id']]) && $existingScores[$criterion['tc_id']] !== null ? $existingScores[$criterion['tc_id']] : $criterion['min_score'] ?>"
                                                        <?= $evaluation['status'] == 2 ? 'readonly' : 'required' ?>>
                                                    <span class="score-range-compact">
                                                        /<?= $criterion['max_score'] ?>
                                                    </span>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </form>
                            </div>

                            <!-- Action Buttons -->
                            <div class="col-md-2">
                                <button type="button" class="btn-detail mb-2" id="btnShowDetailCriteria">
                                    <i class="bi bi-list-ul"></i> Chi tiết
                                </button>

                                <?php if ($evaluation['status'] != 2): ?>
                                    <div class="action-buttons-vertical">
                                        <button type="submit" form="evaluationForm" data-action="draft"
                                            class="btn-action btn-draft">
                                            <i class="bi bi-save-fill"></i> Lưu
                                        </button>
                                        <button type="submit" form="evaluationForm" data-action="submit"
                                            class="btn-action btn-submit">
                                            <i class="bi bi-send-fill"></i> Hoàn thành
                                        </button>
                                    </div>
                                <?php else: ?>
                                    <div
                                        style="background: rgba(255,255,255,0.2); padding: 12px; border-radius: 12px; text-align: center;">
                                        <i class="bi bi-check-circle-fill" style="font-size: 1.5rem;"></i>
                                        <p style="margin: 8px 0 0 0; font-weight: 600; font-size: 0.85rem;">Hoàn thành</p>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <!-- Content Display -->
                    <div class="card" style="border: none; box-shadow: none; background: transparent;">
                        <div class="card-body" style="padding: 0;">
                            <h5 class="card-title mb-4" style="font-size: 1.5rem; font-weight: 700; color: #2c3e50;">
                                <i class="bi bi-file-text-fill"></i> Bài làm cần đánh giá
                            </h5>

                            <?php if (!empty($responseInfo['contents'])): ?>
                                <div id="content-blocks-container" class="content-display-container">
                                    <?php foreach ($responseInfo['contents'] as $index => $content): ?>
                                        <!-- Content Block with integrated Comment Section -->
                                        <div class="content-block-with-comments" data-content-id="<?= $content['content_id'] ?>">
                                            <div class="content-image-wrapper">
                                                <img id="img-<?= $content['content_id'] ?>" src=""
                                                    alt="Content Block <?= $index + 1 ?>">
                                            </div>

                                            <!-- Facebook-style Comment Section (gắn liền với ảnh) -->
                                            <div class="fb-comment-block" id="comment-section-<?= $content['content_id'] ?>"
                                                style="display: none;">
                                                <div class="fb-comments-list" id="comments-list-<?= $content['content_id'] ?>">
                                                    <div class="text-center text-slate-400 text-sm py-2">Chưa có bình luận nào</div>
                                                </div>

                                                <!-- Add Comment Input Area -->
                                                <div class="fb-comment-input-area">
                                                    <img src="<?= htmlspecialchars($_SESSION['user']['avatar'] ?? 'https://i.pinimg.com/564x/92/26/5c/92265c40c8e428122e0b32adc1994594.jpg') ?>"
                                                        alt="Avatar">
                                                    <textarea class="fb-comment-input"
                                                        data-content-id="<?= $content['content_id'] ?>"
                                                        placeholder="Viết bình luận..." rows="1"></textarea>
                                                    <button class="fb-comment-btn" data-content-id="<?= $content['content_id'] ?>">
                                                        Gửi
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php else: ?>
                                <div class="alert alert-warning">
                                    <i class="bi bi-exclamation-triangle"></i> Chưa có nội dung bài làm
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Modal for Detailed Criteria -->
            <div class="modal-criteria" id="modalCriteria">
                <div class="modal-content-criteria">
                    <div class="modal-header-criteria">
                        <div class="modal-title-criteria">
                            <i class="bi bi-list-check"></i>
                            Chi tiết tiêu chí đánh giá
                        </div>
                        <button class="modal-close" id="btnCloseModal">
                            <i class="bi bi-x-lg"></i>
                        </button>
                    </div>
                    <div class="modal-body-criteria">
                        <?php foreach ($criteria as $criterion): ?>
                            <div class="criteria-detail-card">
                                <div class="criteria-detail-header">
                                    <div class="criteria-detail-name">
                                        <i class="bi bi-check-square-fill" style="color: #667eea;"></i>
                                        <?= htmlspecialchars($criterion['name_tc']) ?>
                                    </div>
                                    <div class="criteria-detail-score-box">
                                        <input type="number" class="criteria-detail-score-input score-input-modal"
                                            data-tc-id="<?= $criterion['tc_id'] ?>" min="<?= $criterion['min_score'] ?>"
                                            max="<?= $criterion['max_score'] ?>" step="0.5"
                                            value="<?= isset($existingScores[$criterion['tc_id']]) && $existingScores[$criterion['tc_id']] !== null ? $existingScores[$criterion['tc_id']] : $criterion['min_score'] ?>"
                                            <?= $evaluation['status'] == 2 ? 'readonly' : '' ?>>
                                        <div class="criteria-detail-range">
                                            <?= $criterion['min_score'] ?> - <?= $criterion['max_score'] ?> điểm
                                        </div>
                                    </div>
                                </div>
                                <?php if ($criterion['description']): ?>
                                    <div class="criteria-detail-description">
                                        <?= nl2br(htmlspecialchars($criterion['description'])) ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <?php
    require_once 'views/templates/footer.php';
    require_once 'views/templates/vendors.php';
    ?>

    <!-- Toast Container for Errors -->
    <div class="position-fixed bottom-0 end-0 p-3" style="z-index: 11">
        <div id="errorToast" class="toast" role="alert" aria-live="assertive" aria-atomic="true">
            <div class="toast-header bg-danger text-white">
                <i class="bi bi-exclamation-triangle-fill me-2"></i>
                <strong class="me-auto">Lỗi</strong>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="toast"></button>
            </div>
            <div class="toast-body" id="errorToastMessage"></div>
        </div>
    </div>

    <?php if (isset($_SESSION['error_message'])): ?>
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const errorMsg = <?= json_encode($_SESSION['error_message']) ?>;
                const toastEl = document.getElementById('errorToast');
                const toastBody = document.getElementById('errorToastMessage');

                toastBody.innerHTML = errorMsg;

                const toast = new bootstrap.Toast(toastEl, {
                    autohide: false // Don't auto-hide for errors
                });
                toast.show();
            });
        </script>
    <?php
        unset($_SESSION['error_message']);
    endif;
    ?>

    <script>
        // Calculate total score on input change
        function updateTotalScore() {
            let total = 0;
            document.querySelectorAll('.score-input').forEach(inp => {
                total += parseFloat(inp.value) || 0;
            });
            const displayElement = document.getElementById('totalScoreDisplay');
            if (displayElement) {
                displayElement.textContent = total.toFixed(1);
            }
        }

        document.querySelectorAll('.score-input').forEach(input => {
            input.addEventListener('input', function() {
                updateTotalScore();

                // Sync with modal inputs
                const tcId = this.getAttribute('data-tc-id');
                const modalInput = document.querySelector(`.score-input-modal[data-tc-id="${tcId}"]`);
                if (modalInput) {
                    modalInput.value = this.value;
                }
            });
        });

        // Sync modal inputs with compact inputs
        document.querySelectorAll('.score-input-modal').forEach(input => {
            input.addEventListener('input', function() {
                const tcId = this.getAttribute('data-tc-id');
                const compactInput = document.querySelector(`.score-input-compact[data-tc-id="${tcId}"]`);
                if (compactInput) {
                    compactInput.value = this.value;
                }
                updateTotalScore();
            });
        });

        // Modal controls
        const btnShowModal = document.getElementById('btnShowDetailCriteria');
        const btnCloseModal = document.getElementById('btnCloseModal');
        const modal = document.getElementById('modalCriteria');

        if (btnShowModal) {
            btnShowModal.addEventListener('click', function() {
                modal.classList.add('active');
            });
        }

        if (btnCloseModal) {
            btnCloseModal.addEventListener('click', function() {
                modal.classList.remove('active');
            });
        }

        // Close modal on outside click
        modal.addEventListener('click', function(e) {
            if (e.target === modal) {
                modal.classList.remove('active');
            }
        });

        // Form submission
        const form = document.getElementById('evaluationForm');
        if (form) {
            form.addEventListener('submit', async function(e) {
                e.preventDefault();

                // Get action from the button's data-action attribute
                const action = e.submitter ? e.submitter.getAttribute('data-action') : 'draft';

                const formData = new FormData(this);
                formData.set('action', action); // Explicitly set action in FormData

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
                    // Debug log
                    console.log('Submitting evaluation with action:', action);
                    console.log('FormData entries:', Array.from(formData.entries()));

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

        // ========== TEXT TO IMAGE AND COMMENT FUNCTIONALITY ==========

        // PHP data for comments
        const RESPONSE_ID = <?= $responseInfo['id_response'] ?>;
        const TASK_ID = <?= $taskId ?>;
        const CURRENT_USER_ID = <?= $userId ?>;
        const CURRENT_USER_ROLE = <?= json_encode($_SESSION['user']['role']) ?>;
        const USER_AVATAR = 'https://i.pinimg.com/564x/92/26/5c/92265c40c8e428122e0b32adc1994594.jpg';
        const USER_NAME = <?= json_encode($_SESSION['user']['fullname'] ?? 'User') ?>;

        // Content blocks data from PHP
        const contentBlocksData = <?= json_encode($responseInfo['contents']) ?>;

        let selectedBlockId = null;

        // Initialize when DOM is ready
        document.addEventListener('DOMContentLoaded', function() {
            // Generate images for all content blocks
            generateAllContentImages();

            // Setup click handlers for content blocks
            setupContentBlockHandlers();

            // Load comments for all blocks
            loadAllComments();
        });

        // === Text to Image Conversion ===
        function textToImage(text, maxWidth = 800, options = {}) {
            const fontSize = options.fontSize || 18;
            const fontFamily = options.fontFamily || '"Open Sans", Arial, sans-serif';
            const padding = options.padding || 50;
            const lineHeight = options.lineHeight || 1.8;
            const bgColor = options.bgColor || '#ffffff';
            const textColor = options.textColor || '#1f2937';

            const canvas = document.createElement('canvas');
            const ctx = canvas.getContext('2d');

            ctx.imageSmoothingEnabled = true;
            ctx.imageSmoothingQuality = 'high';

            const segments = parseStyledText(text);
            const lines = wrapTextWithStyles(ctx, segments, maxWidth - padding * 2, fontSize, lineHeight, fontFamily);

            let totalHeight = padding;
            lines.forEach(line => {
                const maxFontSize = Math.max(...line.map(seg => {
                    if (seg.style === '#') return fontSize * 1.8;
                    if (seg.style === '##') return fontSize * 1.5;
                    return fontSize;
                }));
                totalHeight += maxFontSize * lineHeight;
            });
            totalHeight += padding;

            canvas.width = maxWidth;
            canvas.height = totalHeight;

            ctx.fillStyle = bgColor;
            ctx.fillRect(0, 0, canvas.width, canvas.height);

            let y = padding;
            const maxLineWidth = maxWidth - padding * 2;

            lines.forEach((line, lineIndex) => {
                let x = padding;

                const maxFontSize = Math.max(...line.map(seg => {
                    if (seg.style === '#') return fontSize * 1.8;
                    if (seg.style === '##') return fontSize * 1.5;
                    return fontSize;
                }));

                y += maxFontSize;

                const isHeading = line.some(seg => seg.style === '#' || seg.style === '##');

                line.forEach(seg => {
                    ctx.font = applyStyle(seg.style, fontSize, fontFamily);
                    ctx.textBaseline = 'bottom';

                    if (seg.style === '#') {
                        ctx.fillStyle = '#1e293b';
                    } else if (seg.style === '##') {
                        ctx.fillStyle = '#334155';
                    } else if (seg.style === '**') {
                        ctx.fillStyle = '#0f172a';
                    } else {
                        ctx.fillStyle = textColor;
                    }

                    ctx.fillText(seg.text, x, y);
                    const segmentWidth = ctx.measureText(seg.text).width;
                    x += segmentWidth;
                });

                if (isHeading) {
                    y += fontSize * 0.5;
                } else {
                    y += maxFontSize * (lineHeight - 1);
                }
            });

            return canvas.toDataURL('image/png');
        }

        function parseStyledText(text) {
            const segments = [];
            const lines = text.split('\n');

            lines.forEach((line, index) => {
                if (!line.trim()) {
                    if (segments.length > 0) {
                        segments.push({
                            text: '',
                            style: 'break'
                        });
                    }
                    return;
                }

                const lineRegex = /(\*\*.*?\*\*|##?\s[^\n]+|[^\*#\n]+)/g;
                let match;
                let hasContent = false;

                while ((match = lineRegex.exec(line)) !== null) {
                    const segment = match[0];

                    if (segment.startsWith('**') && segment.endsWith('**')) {
                        segments.push({
                            text: segment.slice(2, -2),
                            style: '**'
                        });
                        hasContent = true;
                    } else if (segment.startsWith('## ')) {
                        if (segments.length > 0 && segments[segments.length - 1].style !== 'break') {
                            segments.push({
                                text: '',
                                style: 'break'
                            });
                        }
                        segments.push({
                            text: segment.slice(3),
                            style: '##'
                        });
                        hasContent = true;
                    } else if (segment.startsWith('# ')) {
                        if (segments.length > 0 && segments[segments.length - 1].style !== 'break') {
                            segments.push({
                                text: '',
                                style: 'break'
                            });
                        }
                        segments.push({
                            text: segment.slice(2),
                            style: '#'
                        });
                        hasContent = true;
                    } else if (segment.trim()) {
                        segments.push({
                            text: segment,
                            style: 'normal'
                        });
                        hasContent = true;
                    }
                }

                if (hasContent && index < lines.length - 1) {
                    const lastSeg = segments[segments.length - 1];
                    if (lastSeg && lastSeg.style !== 'break') {
                        segments.push({
                            text: '',
                            style: 'break'
                        });
                    }
                }
            });

            return segments;
        }

        function applyStyle(style, fontSize, fontFamily) {
            if (style === '#') return `bold ${Math.round(fontSize * 1.8)}px ${fontFamily}`;
            if (style === '##') return `bold ${Math.round(fontSize * 1.5)}px ${fontFamily}`;
            if (style === '**') return `bold ${fontSize}px ${fontFamily}`;
            return `${fontSize}px ${fontFamily}`;
        }

        function wrapTextWithStyles(ctx, segments, maxWidth, fontSize, lineHeight, fontFamily) {
            const lines = [];
            let currentLine = [];
            let currentWidth = 0;

            segments.forEach(seg => {
                if (seg.style === 'break') {
                    if (currentLine.length > 0) {
                        lines.push(currentLine);
                        currentLine = [];
                        currentWidth = 0;
                    }
                    return;
                }

                ctx.font = applyStyle(seg.style, fontSize, fontFamily);
                const words = seg.text.split(' ');

                words.forEach((word, wordIndex) => {
                    const isLastWord = wordIndex === words.length - 1;
                    const testText = word + (isLastWord ? '' : ' ');
                    const metrics = ctx.measureText(testText);
                    const wordWidth = metrics.width;

                    if (currentWidth + wordWidth > maxWidth && currentLine.length > 0) {
                        lines.push(currentLine);
                        currentLine = [{
                            text: testText,
                            style: seg.style
                        }];
                        currentWidth = wordWidth;
                    } else {
                        currentLine.push({
                            text: testText,
                            style: seg.style
                        });
                        currentWidth += wordWidth;
                    }
                });
            });

            if (currentLine.length > 0) {
                lines.push(currentLine);
            }

            return lines;
        }

        // Generate images for all content blocks
        function generateAllContentImages() {
            document.fonts.ready.then(() => {
                contentBlocksData.forEach(block => {
                    const wrapper = document.querySelector(`[data-content-id="${block.content_id}"]`);
                    const img = document.getElementById(`img-${block.content_id}`);

                    if (!img || !wrapper) return;

                    const wrapperWidth = wrapper.offsetWidth - 32;
                    const maxWidth = Math.min(wrapperWidth, 1200);

                    const imageData = textToImage(block.content, maxWidth, {
                        fontSize: 18,
                        lineHeight: 1.8,
                        padding: 50,
                        textAlign: 'justify',
                    });

                    img.src = imageData;
                    img.style.maxWidth = '100%';
                    img.style.height = 'auto';
                });
            });
        }

        // Setup click handlers for content blocks
        function setupContentBlockHandlers() {
            const contentBlocks = document.querySelectorAll('.content-block-with-comments');

            contentBlocks.forEach(block => {
                const wrapper = block.querySelector('.content-image-wrapper');

                wrapper.addEventListener('click', function() {
                    const contentId = block.dataset.contentId;

                    // Remove selected class from all blocks
                    contentBlocks.forEach(b => {
                        b.classList.remove('selected');
                        const commentBlock = b.querySelector('.fb-comment-block');
                        if (commentBlock) commentBlock.style.display = 'none';
                    });

                    // Add selected class to clicked block
                    block.classList.add('selected');
                    selectedBlockId = contentId;

                    // Show comment block
                    const commentBlock = block.querySelector('.fb-comment-block');
                    if (commentBlock) {
                        commentBlock.style.display = 'block';
                    }
                });
            });

            // Setup comment button handlers
            setupCommentButtons();
        }

        // Setup comment submit buttons
        function setupCommentButtons() {
            const buttons = document.querySelectorAll('.fb-comment-btn');

            buttons.forEach(btn => {
                btn.addEventListener('click', async function() {
                    const contentId = this.dataset.contentId;
                    const textarea = document.querySelector(`textarea[data-content-id="${contentId}"]`);
                    const commentText = textarea.value.trim();

                    if (!commentText) {
                        alert('Vui lòng nhập nội dung bình luận!');
                        return;
                    }

                    this.disabled = true;
                    this.textContent = 'Đang gửi...';

                    try {
                        await addComment(contentId, commentText);
                        textarea.value = '';
                    } catch (error) {
                        alert('Lỗi: ' + error.message);
                    } finally {
                        this.disabled = false;
                        this.innerHTML = '<i class="bi bi-send"></i> Gửi bình luận';
                    }
                });
            });
        }

        // Load comments for all content blocks
        async function loadAllComments() {
            for (const block of contentBlocksData) {
                await loadComments(block.content_id);
            }
        }

        // Load comments for a content block
        async function loadComments(contentId) {
            try {
                const response = await fetch('<?= URL_ROOT ?>/api/comment_actions.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        action: 'load_comments',
                        content_id: contentId
                    })
                });

                const result = await response.json();

                if (result.success) {
                    renderComments(contentId, result.comments);
                }
            } catch (error) {
                console.error('Error loading comments:', error);
            }
        }

        // Render comments
        function renderComments(contentId, comments) {
            const container = document.getElementById(`comments-list-${contentId}`);

            if (comments.length === 0) {
                container.innerHTML = '<div class="text-center text-slate-400 text-sm py-2">Chưa có bình luận nào</div>';
                return;
            }

            container.innerHTML = comments.map(comment => createCommentHTML(comment)).join('');
            attachCommentEventListeners(contentId);
        }

        // Create comment HTML (Facebook style with bubbles and reply)
        function createCommentHTML(comment) {
            const avatar = comment.avatar || 'https://i.pinimg.com/564x/92/26/5c/92265c40c8e428122e0b32adc1994594.jpg';
            const time = new Date(comment.created_at).toLocaleString('vi-VN');
            const depth = comment.depth || 0;
            const canReply = depth < 2; // Max 3 levels (0, 1, 2)

            // Labels
            const roleLabel = comment.role_label || 'Học sinh';
            const roleClass = (comment.role_label === 'Giáo viên') ? 'label-teacher' : 'label-student';
            const phaseLabel = comment.m_phase_name || 'Không rõ';

            let html = `
                <div class="fb-comment-item" data-comment-id="${comment.comment_id}" data-depth="${depth}">
                    <div class="fb-comment-core">
                        <img src="${avatar}" alt="${escapeHtml(comment.fullname)}">
                        <div style="flex-grow: 1;">
                            <div class="fb-comment-bubble">
                                <div class="comment-author">
                                    <span>${escapeHtml(comment.fullname)}</span>
                                    <span class="comment-label ${roleClass}">${roleLabel}</span>
                                    <span class="comment-label label-phase">${phaseLabel}</span>
                                </div>
                                <div class="comment-content">${escapeHtml(comment.comment_text)}</div>
                            </div>
                            <div class="fb-comment-actions">
                                <span class="comment-time">${time}</span>
                                ${canReply ? `<button class="reply-btn" data-comment-id="${comment.comment_id}">Trả lời</button>` : ''}
                                ${comment.user_id == CURRENT_USER_ID ? `<button class="delete-btn" data-comment-id="${comment.comment_id}">Xóa</button>` : ''}
                            </div>
                            ${canReply ? `
                            <div class="fb-reply-form" id="reply-form-${comment.comment_id}" style="display:none;">
                                <div class="fb-comment-input-area">
                                    <img src="${USER_AVATAR}" alt="Avatar">
                                    <textarea class="fb-comment-input" placeholder="Viết trả lời..." rows="1"></textarea>
                                    <button class="fb-comment-btn submit-reply-btn" data-parent-id="${comment.comment_id}">Gửi</button>
                                </div>
                            </div>
                            ` : ''}
                        </div>
                    </div>
            `;

            // Render replies recursively
            if (comment.replies && comment.replies.length > 0) {
                html += '<div class="fb-replies">';
                comment.replies.forEach(reply => {
                    html += createCommentHTML(reply);
                });
                html += '</div>';
            }

            html += '</div>';
            return html;
        }

        // Add comment
        async function addComment(contentId, commentText, replyToId = null) {
            const response = await fetch('<?= URL_ROOT ?>/api/comment_actions.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    action: 'add_comment_danhgia',
                    content_id: contentId,
                    task_id: TASK_ID,
                    comment_text: commentText,
                    user_id: CURRENT_USER_ID,
                    reply_comment_id: replyToId
                })
            });

            const result = await response.json();

            if (result.success) {
                await loadComments(contentId);
            } else {
                throw new Error(result.message);
            }
        }

        // Attach event listeners to comment actions
        function attachCommentEventListeners(contentId) {
            const container = document.getElementById(`comments-list-${contentId}`);

            // Reply buttons
            container.querySelectorAll('.reply-btn').forEach(btn => {
                btn.addEventListener('click', function() {
                    const commentId = this.dataset.commentId;
                    const replyForm = document.getElementById(`reply-form-${commentId}`);
                    replyForm.style.display = replyForm.style.display === 'none' ? 'block' : 'none';
                });
            });

            // Delete buttons
            container.querySelectorAll('.delete-btn').forEach(btn => {
                btn.addEventListener('click', async function() {
                    if (!confirm('Bạn có chắc muốn xóa bình luận này?')) return;

                    const commentId = this.dataset.commentId;

                    try {
                        const response = await fetch('<?= URL_ROOT ?>/api/comment_actions.php', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json'
                            },
                            body: JSON.stringify({
                                action: 'delete_comment',
                                comment_id: commentId
                            })
                        });

                        const result = await response.json();

                        if (result.success) {
                            await loadComments(contentId);
                        } else {
                            alert('Lỗi: ' + result.message);
                        }
                    } catch (error) {
                        alert('Lỗi: ' + error.message);
                    }
                });
            });

            // Submit reply buttons
            container.querySelectorAll('.submit-reply-btn').forEach(btn => {
                btn.addEventListener('click', async function() {
                    const parentId = this.dataset.parentId;
                    const textarea = this.previousElementSibling;
                    const replyText = textarea.value.trim();

                    if (!replyText) {
                        alert('Vui lòng nhập nội dung trả lời!');
                        return;
                    }

                    this.disabled = true;
                    this.textContent = 'Đang gửi...';

                    try {
                        await addComment(contentId, replyText, parentId);
                        textarea.value = '';
                        document.getElementById(`reply-form-${parentId}`).style.display = 'none';
                    } catch (error) {
                        alert('Lỗi: ' + error.message);
                    } finally {
                        this.disabled = false;
                        this.textContent = 'Gửi';
                    }
                });
            });
        }

        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        // Regenerate images on window resize
        let resizeTimeout;
        window.addEventListener('resize', function() {
            clearTimeout(resizeTimeout);
            resizeTimeout = setTimeout(() => {
                generateAllContentImages();
            }, 300);
        });
    </script>
</body>

</html>