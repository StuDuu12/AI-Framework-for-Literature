<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php
    require_once './views/templates/assets.php';
    require_once './config/db.php';
    require_once './config/helper_homework.php';
    require_once './config/helper_task.php';
    require_once './config/helper_phase.php';

    // Get parameters from URL
    $submission_id = isset($_GET['submission_id']) ? intval($_GET['submission_id']) : 0;
    $task_id = isset($_GET['task_id']) ? intval($_GET['task_id']) : 0;
    $topic_id = isset($_GET['topic_id']) ? intval($_GET['topic_id']) : 0;
    $phase_id = isset($_GET['phase_id']) ? intval($_GET['phase_id']) : 0;

    // Validate required parameters
    if ($submission_id <= 0 || $task_id <= 0) {
        header("Location: /404");
        exit;
    }

    // Get current user info
    $current_user_id = $_SESSION['user']['user_id'] ?? $_SESSION['user_id'] ?? null;
    $current_user_role = $_SESSION['user']['role'] ?? 'student';

    if (!$current_user_id) {
        header("Location: /login");
        exit;
    }

    // Get response details
    try {
        $stmt = $pdo->prepare("
            SELECT r.*, u.fullname, u.user_id,
                   d.type as deliver_type, d.value as deliver_value, d.id_deliver
            FROM response r
            INNER JOIN user u ON r.user_id = u.user_id
            INNER JOIN deliver d ON r.deliver_id = d.id_deliver
            WHERE r.id_response = :submission_id
            AND r.task_id = :task_id
            AND r.parent_response IS NULL
        ");
        $stmt->execute([
            ':submission_id' => $submission_id,
            ':task_id' => $task_id
        ]);
        $submission = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$submission) {
            header("Location: /404");
            exit;
        }

        // Validate access rights
        // Teachers can view all submissions in their class
        // Students can only view submissions if:
        // 1. It's their own submission
        // 2. The task is assigned to their group and they're a group member

        $hasAccess = false;

        if ($current_user_role === 'teacher') {
            // Teacher - check if they teach this class
            $taskInfo = getTaskDetails($task_id);
            if ($taskInfo) {
                $stmt = $pdo->prepare("
                    SELECT 1 FROM class_member cm
                    INNER JOIN topic t ON cm.class_id = t.class_id
                    INNER JOIN phase p ON t.topic_id = p.topic_id
                    INNER JOIN task tk ON p.phase_id = tk.phase_id
                    WHERE tk.task_id = :task_id
                    AND cm.user_id = :user_id
                    AND cm.role = 'teacher'
                ");
                $stmt->execute([
                    ':task_id' => $task_id,
                    ':user_id' => $current_user_id
                ]);
                $hasAccess = $stmt->fetch() !== false;
            }
        } else {
            // Student - check if it's their own submission
            if ($submission['user_id'] == $current_user_id) {
                $hasAccess = true;
            } else {
                // Check if task is assigned to student's group and student is member
                if ($submission['deliver_type'] === 'group') {
                    $stmt = $pdo->prepare("
                        SELECT 1 FROM group_member
                        WHERE group_id = :group_id
                        AND user_id = :user_id
                    ");
                    $stmt->execute([
                        ':group_id' => $submission['deliver_value'],
                        ':user_id' => $current_user_id
                    ]);
                    $hasAccess = $stmt->fetch() !== false;
                }
            }
        }

        if (!$hasAccess) {
            header("Location: /403");
            exit;
        }

        // Parse JSON data
        $analysisData = json_decode($submission['json_ai'], true);

        // Get task details
        $taskInfo = getTaskDetails($task_id);
        if (!$taskInfo) {
            header("Location: /404");
            exit;
        }
    } catch (PDOException $e) {
        error_log("Error fetching submission: " . $e->getMessage());
        header("Location: /500");
        exit;
    }

    ?>
    <title data-lang-key="analysis_detail">Analysis Details - Literature System</title>
</head>

<body>
    <?php
    require_once './views/templates/header.php';
    if ($current_user_role === 'teacher') {
        require_once './views/templates/sidebar_teacher.php';
    } else {
        require_once './views/templates/sidebar_student.php';
    }
    ?>

    <main id="main" class="main">
        <div class="pagetitle">
            <h1 data-lang-key="analysis_detail">Analysis Details</h1>
            <nav>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="/class" data-lang-key="class">Class</a></li>
                    <?php if ($topic_id && $phase_id): ?>
                        <li class="breadcrumb-item"><a href="/class/task?topic_id=<?= $topic_id ?>&phase_id=<?= $phase_id ?>" data-lang-key="tasks">Tasks</a></li>
                    <?php endif; ?>
                    <?php if ($current_user_role === 'teacher'): ?>
                        <li class="breadcrumb-item"><a href="/class/task/view?task_id=<?= $task_id ?>&phase_id=<?= $phase_id ?>&topic_id=<?= $topic_id ?>" data-lang-key="task_details">Task Details</a></li>
                    <?php endif; ?>
                    <li class="breadcrumb-item active" data-lang-key="analysis">Analysis</li>
                </ol>
            </nav>
        </div>

        <section class="section">
            <!-- Student Info -->
            <div class="card mb-4">
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <h5 class="card-title" data-lang-key="student_info">Student Information</h5>
                            <p><strong>Full Name:</strong> <?= htmlspecialchars($submission['fullname']) ?></p>
                            <p><strong>Task:</strong> <?= htmlspecialchars($taskInfo['task_title']) ?></p>
                        </div>
                        <div class="col-md-6 text-end">
                            <p><strong>Submission Time:</strong><br><?= date('d/m/Y H:i', strtotime($submission['created_at'])) ?></p>
                            <?php if ($submission['deliver_type'] === 'group'): ?>
                                <span class="badge bg-info">
                                    <i class="bi bi-people"></i> <span data-lang-key="group_work">Group Work</span>
                                </span>
                            <?php else: ?>
                                <span class="badge bg-primary">
                                    <i class="bi bi-person"></i> <span data-lang-key="individual_work">Individual Work</span>
                                </span>
                            <?php endif; ?>
                        </div>
                    </div>

                    <?php if ($current_user_role === 'teacher'): ?>
                        <div class="mt-3">
                            <a href="/class/homework/comment?task_id=<?= $task_id ?>&deliver_id=<?= $submission['id_deliver'] ?>&topic_id=<?= $topic_id ?>&phase_id=<?= $phase_id ?>"
                                class="btn btn-primary">
                                <i class="bi bi-chat-left-text"></i> <span data-lang-key="comment_and_evaluate">Comments & Evaluation</span>
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <?php if ($analysisData): ?>
                <!-- Title -->
                <?php if (isset($analysisData['title'])): ?>
                    <div class="analysis-section">
                        <h3>Title</h3>
                        <h4 class="text-center"><?= htmlspecialchars($analysisData['title']) ?></h4>
                    </div>
                <?php endif; ?>

                <!-- Keywords -->
                <?php if (isset($analysisData['keywords']) && is_array($analysisData['keywords'])): ?>
                    <div class="analysis-section">
                        <h3>Keywords</h3>
                        <div>
                            <?php foreach ($analysisData['keywords'] as $keyword): ?>
                                <span class="keyword-badge"><?= htmlspecialchars($keyword) ?></span>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Statistics -->
                <?php if (isset($analysisData['stats'])): ?>
                    <div class="analysis-section">
                        <h3>Text Statistics</h3>
                        <div class="row">
                            <?php if (isset($analysisData['stats']['charCount'])): ?>
                                <div class="col-md-4">
                                    <div class="stat-box">
                                        <strong>Characters:</strong> <?= number_format($analysisData['stats']['charCount']) ?>
                                    </div>
                                </div>
                            <?php endif; ?>
                            <?php if (isset($analysisData['stats']['wordCount'])): ?>
                                <div class="col-md-4">
                                    <div class="stat-box">
                                        <strong>Words:</strong> <?= number_format($analysisData['stats']['wordCount']) ?>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- General Analysis -->
                <?php if (isset($analysisData['generalAnalysis'])): ?>
                    <div class="analysis-section">
                        <h3>General Analysis</h3>

                        <?php if (isset($analysisData['generalAnalysis']['thematicAnalysis'])): ?>
                            <h5>Thematic Analysis</h5>
                            <div class="content-text">
                                <?= nl2br(htmlspecialchars($analysisData['generalAnalysis']['thematicAnalysis'])) ?>
                            </div>
                        <?php endif; ?>

                        <?php if (isset($analysisData['generalAnalysis']['linguisticInsights'])): ?>
                            <h5 class="mt-3">Linguistic Features</h5>
                            <?php foreach ($analysisData['generalAnalysis']['linguisticInsights'] as $key => $value): ?>
                                <div class="mt-2">
                                    <strong><?= ucfirst(htmlspecialchars($key)) ?>:</strong>
                                    <div class="content-text"><?= nl2br(htmlspecialchars($value)) ?></div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>

                <!-- Story Analysis -->
                <?php if (isset($analysisData['storyAnalysis'])): ?>
                    <div class="analysis-section">
                        <h3>Story Analysis</h3>
                        <?php foreach ($analysisData['storyAnalysis'] as $key => $value): ?>
                            <?php if ($value): ?>
                                <h5><?= ucfirst(htmlspecialchars($key)) ?></h5>
                                <div class="content-text"><?= nl2br(htmlspecialchars($value)) ?></div>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <!-- Drama Analysis -->
                <?php if (isset($analysisData['dramaAnalysis'])): ?>
                    <div class="analysis-section">
                        <h3>Drama Analysis</h3>
                        <?php foreach ($analysisData['dramaAnalysis'] as $key => $value): ?>
                            <?php if ($value): ?>
                                <h5><?= ucfirst(htmlspecialchars($key)) ?></h5>
                                <div class="content-text"><?= nl2br(htmlspecialchars($value)) ?></div>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <!-- Poetry Analysis -->
                <?php if (isset($analysisData['poetryAnalysis'])): ?>
                    <div class="analysis-section">
                        <h3>Poetry Analysis</h3>
                        <?php foreach ($analysisData['poetryAnalysis'] as $key => $value): ?>
                            <?php if ($value): ?>
                                <h5><?= ucfirst(htmlspecialchars($key)) ?></h5>
                                <div class="content-text"><?= nl2br(htmlspecialchars($value)) ?></div>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <!-- Concluding Thoughts -->
                <?php if (isset($analysisData['concludingThoughts'])): ?>
                    <div class="analysis-section">
                        <h3>Conclusion</h3>
                        <?php if (isset($analysisData['concludingThoughts']['lesson'])): ?>
                            <h5>Lesson</h5>
                            <div class="content-text"><?= nl2br(htmlspecialchars($analysisData['concludingThoughts']['lesson'])) ?></div>
                        <?php endif; ?>
                        <?php if (isset($analysisData['concludingThoughts']['message'])): ?>
                            <h5 class="mt-3">Message</h5>
                            <div class="content-text"><?= nl2br(htmlspecialchars($analysisData['concludingThoughts']['message'])) ?></div>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>

                <!-- Intertextuality -->
                <?php if (isset($analysisData['intertextuality'])): ?>
                    <div class="analysis-section">
                        <h3>Intertextuality</h3>
                        <?php foreach ($analysisData['intertextuality'] as $key => $value): ?>
                            <?php if ($value): ?>
                                <h5><?= ucfirst(htmlspecialchars($key)) ?></h5>
                                <div class="content-text"><?= nl2br(htmlspecialchars($value)) ?></div>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

            <?php else: ?>
                <div class="alert alert-warning">
                    <i class="bi bi-exclamation-triangle"></i> <span>No analysis data available.</span>
                </div>
            <?php endif; ?>

            <!-- Back Button -->
            <div class="text-center mt-4">
                <?php if ($current_user_role === 'teacher' && $topic_id && $phase_id): ?>
                    <a href="/class/task/view?task_id=<?= $task_id ?>&phase_id=<?= $phase_id ?>&topic_id=<?= $topic_id ?>" class="btn btn-secondary">
                        <i class="bi bi-arrow-left"></i> <span>Go Back to List</span>
                    </a>
                <?php else: ?>
                    <a href="/class/my_tasks" class="btn btn-secondary">
                        <i class="bi bi-arrow-left"></i> <span>Go Back to My Tasks</span>
                    </a>
                <?php endif; ?>
            </div>
        </section>

    </main>

    <?php
    require_once 'views/templates/footer.php';
    require_once 'views/templates/vendors.php';
    ?>

</body>

</html>
