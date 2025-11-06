<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php require_once './views/templates/assets.php'; ?>
    <title>Cấu hình đánh giá chéo</title>
    <style>
        .criteria-badge {
            display: inline-block;
            padding: 4px 12px;
            background: #e3f2fd;
            color: #1976d2;
            border-radius: 12px;
            font-size: 0.875rem;
            margin: 2px;
        }

        .deliver-card {
            border-left: 4px solid #2196F3;
            transition: all 0.3s ease;
        }

        .deliver-card:hover {
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            transform: translateY(-2px);
        }

        .status-badge {
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 0.875rem;
            font-weight: 500;
        }

        .status-pending {
            background: #fff3e0;
            color: #e65100;
        }

        .status-in-progress {
            background: #e3f2fd;
            color: #1565c0;
        }

        .status-submitted {
            background: #e8f5e9;
            color: #2e7d32;
        }

        .evaluator-select {
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            padding: 8px 12px;
        }

        .evaluator-select:focus {
            border-color: #2196F3;
            outline: none;
        }
    </style>
</head>

<body>
    <?php
    require_once __DIR__ . '/../../config/db.php';
    require_once __DIR__ . '/../../config/helper_class.php';
    require_once __DIR__ . '/../../config/helper_homework.php';

    require_once './views/templates/header.php';
    if (isset($_SESSION['user']['role']) && $_SESSION['user']['role'] === 'teacher') {
        require_once './views/templates/sidebar_teacher.php';
    } else if (isset($_SESSION['user']['role']) && $_SESSION['user']['role'] === 'student') {
        require_once './views/templates/sidebar_student.php';
    }

    // Get parameters
    $task_id = isset($_GET['task_id']) ? intval($_GET['task_id']) : 0;
    $topic_id = isset($_GET['topic_id']) ? intval($_GET['topic_id']) : 0;
    $phase_id = isset($_GET['phase_id']) ? intval($_GET['phase_id']) : 0;

    if (!$task_id) {
        header("Location: /404");
        exit;
    }

    // Get task info
    $taskInfo = getInfoTask($task_id);
    if (!$taskInfo) {
        header("Location: /404");
        exit;
    }

    // Get class_id from phase and check if it's evaluation phase
    $stmtPhase = $pdo->prepare("
        SELECT t.class_id, p.m_phase_id, c.class_name, mp.phase_code, mp.m_phase_name
        FROM phase p 
        JOIN topic t ON p.topic_id = t.topic_id
        JOIN class c ON t.class_id = c.class_id 
        LEFT JOIN main_phase mp ON p.m_phase_id = mp.m_phase_id
        WHERE p.phase_id = :phase_id
    ");
    $stmtPhase->execute([':phase_id' => $phase_id]);
    $phaseInfo = $stmtPhase->fetch(PDO::FETCH_ASSOC);
    $class_id = $phaseInfo['class_id'] ?? 0;
    $class_name = $phaseInfo['class_name'] ?? '';

    // Check if this phase is for peer evaluation
    if (!$phaseInfo || $phaseInfo['phase_code'] !== 'PHASE_DANHGIANGUOIHOC') {
        // Show error message
        require_once './views/templates/header.php';
        if (isset($_SESSION['user']['role']) && $_SESSION['user']['role'] === 'teacher') {
            require_once './views/templates/sidebar_teacher.php';
        } else if (isset($_SESSION['user']['role']) && $_SESSION['user']['role'] === 'student') {
            require_once './views/templates/sidebar_student.php';
        }
        ?>
        <main id="main" class="main">
            <div class="pagetitle">
                <h1><i class="bi bi-exclamation-triangle text-warning"></i> Không thể truy cập</h1>
            </div>
            <section class="section">
                <div class="card">
                    <div class="card-body text-center py-5">
                        <i class="bi bi-gear-fill" style="font-size: 4rem; color: #ccc;"></i>
                        <h3 class="mt-3">Trang cấu hình đánh giá chéo</h3>
                        <p class="text-muted">Trang này chỉ khả dụng cho các phase có loại <strong>"Đánh giá đồng
                                đẳng"</strong></p>

                        <?php if ($phaseInfo): ?>
                            <div class="alert alert-warning mt-4">
                                <strong>Phase hiện tại:</strong>
                                <?= htmlspecialchars($phaseInfo['m_phase_name'] ?? 'Không xác định') ?><br>
                                <small>Vui lòng chọn phase có loại "Đánh giá đồng đẳng" (PHASE_DANHGIANGUOIHOC) để cấu hình đánh
                                    giá chéo.</small>
                            </div>
                        <?php endif; ?>

                        <a href="javascript:history.back()" class="btn btn-primary mt-3">
                            <i class="bi bi-arrow-left"></i> Quay lại
                        </a>
                    </div>
                </div>
            </section>
        </main>
        <?php
        require_once 'views/templates/footer.php';
        require_once 'views/templates/vendors.php';
        echo '</body></html>';
        exit;
    }

    // Get available criteria groups for this class
    $criteriaGroups = [];
    $stmtCriteriaGroups = $pdo->prepare("
        SELECT nhom_tc_id, ten_nhom_tc, description 
        FROM nhom_tc 
        WHERE class_id = :class_id
        ORDER BY ten_nhom_tc ASC
    ");
    $stmtCriteriaGroups->execute([':class_id' => $class_id]);
    $criteriaGroups = $stmtCriteriaGroups->fetchAll(PDO::FETCH_ASSOC);

    // Get current selected criteria group for this task
    $criteriaGroup = getTaskCriteriaGroup($task_id);
    $currentCriteriaGroupId = $criteriaGroup['id_group_tc'] ?? 0;

    // Check if any evaluation has been done
    $hasEvaluations = hasTaskEvaluations($task_id);

    // Get all responses that need to be evaluated (status = 2 = submitted)
    $responses = getResponsesForEvaluation($task_id);

    // Get all students and groups in class for assignment options
    $students = [];
    $stmtStudents = $pdo->prepare("
        SELECT u.user_id, u.fullname 
        FROM class_member cm
        JOIN users u ON cm.user_id = u.user_id
        WHERE cm.class_id = :class_id AND cm.role = 'student'
        ORDER BY u.fullname ASC
    ");
    $stmtStudents->execute([':class_id' => $class_id]);
    $students = $stmtStudents->fetchAll(PDO::FETCH_ASSOC);

    $groups = [];
    $stmtGroups = $pdo->prepare("
        SELECT group_id, group_name 
        FROM `group` 
        WHERE class_id = :class_id
        ORDER BY group_name ASC
    ");
    $stmtGroups->execute([':class_id' => $class_id]);
    $groups = $stmtGroups->fetchAll(PDO::FETCH_ASSOC);

    // Get existing evaluator assignments
    $evaluatorAssignments = getEvaluatorAssignments($task_id);
    ?>

    <main id="main" class="main">
        <div class="pagetitle">
            <h1><i class="bi bi-gear-fill"></i> Cấu hình đánh giá chéo</h1>
            <nav>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="/">Trang chủ</a></li>
                    <li class="breadcrumb-item"><a
                            href="/class/detail?class_id=<?= $class_id ?>"><?= htmlspecialchars($class_name) ?></a></li>
                    <li class="breadcrumb-item active"><?= htmlspecialchars($taskInfo['task_title']) ?></li>
                </ol>
            </nav>
        </div><!-- End Page Title -->

        <section class="section">
            <!-- Task Info Card -->
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
                            <span class="badge bg-info">Tổng số bài nộp: <?= count($responses) ?></span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Criteria Selection Card -->
            <div class="card mb-4">
                <div class="card-body">
                    <h5 class="card-title"><i class="bi bi-clipboard-check"></i> Bộ tiêu chí đánh giá</h5>

                    <?php if ($hasEvaluations): ?>
                        <div class="alert alert-warning">
                            <i class="bi bi-exclamation-triangle-fill"></i>
                            Đã có bài chấm được thực hiện. Không thể thay đổi bộ tiêu chí.
                        </div>
                        <?php if ($currentCriteriaGroupId): ?>
                            <?php
                            $stmtCurrentGroup = $pdo->prepare("SELECT ten_nhom_tc FROM nhom_tc WHERE nhom_tc_id = :id");
                            $stmtCurrentGroup->execute([':id' => $currentCriteriaGroupId]);
                            $currentGroup = $stmtCurrentGroup->fetch(PDO::FETCH_ASSOC);
                            ?>
                            <p><strong>Bộ tiêu chí hiện tại:</strong>
                                <span class="badge bg-success"><?= htmlspecialchars($currentGroup['ten_nhom_tc']) ?></span>
                            </p>
                        <?php endif; ?>
                    <?php else: ?>
                        <form id="criteriaForm" method="POST" action="/api/evaluate/set_criteria.php">
                            <input type="hidden" name="task_id" value="<?= $task_id ?>">
                            <div class="row">
                                <div class="col-md-8">
                                    <select name="nhom_tc_id" class="form-select" required <?= empty($criteriaGroups) ? 'disabled' : '' ?>>
                                        <option value="">-- Chọn bộ tiêu chí --</option>
                                        <?php foreach ($criteriaGroups as $group): ?>
                                            <option value="<?= $group['nhom_tc_id'] ?>"
                                                <?= $currentCriteriaGroupId == $group['nhom_tc_id'] ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($group['ten_nhom_tc']) ?>
                                                <?= $group['description'] ? ' - ' . htmlspecialchars($group['description']) : '' ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <?php if (empty($criteriaGroups)): ?>
                                        <small class="text-muted">Chưa có bộ tiêu chí nào cho lớp này.
                                            <a href="/class/criteria?class_id=<?= $class_id ?>">Tạo bộ tiêu chí mới</a>
                                        </small>
                                    <?php endif; ?>
                                </div>
                                <div class="col-md-4">
                                    <button type="submit" class="btn btn-primary w-100" <?= empty($criteriaGroups) ? 'disabled' : '' ?>>
                                        <i class="bi bi-save"></i> Lưu cấu hình
                                    </button>
                                </div>
                            </div>
                        </form>

                        <?php if ($currentCriteriaGroupId): ?>
                            <div class="mt-3">
                                <h6>Các tiêu chí trong bộ:</h6>
                                <?php
                                $stmtCriteria = $pdo->prepare("
                                    SELECT tc_id, name_tc, min_score, max_score 
                                    FROM tieu_chi 
                                    WHERE nhom_tc_id = :nhom_tc_id
                                    ORDER BY tc_id ASC
                                ");
                                $stmtCriteria->execute([':nhom_tc_id' => $currentCriteriaGroupId]);
                                $criteria = $stmtCriteria->fetchAll(PDO::FETCH_ASSOC);

                                foreach ($criteria as $criterion):
                                    ?>
                                    <span class="criteria-badge">
                                        <?= htmlspecialchars($criterion['name_tc']) ?>
                                        (<?= $criterion['min_score'] ?>-<?= $criterion['max_score'] ?> điểm)
                                    </span>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Deliver Assignment Card -->
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title"><i class="bi bi-people-fill"></i> Phân công chấm bài</h5>

                    <?php if (!$currentCriteriaGroupId): ?>
                        <div class="alert alert-info">
                            <i class="bi bi-info-circle"></i> 
                            Vui lòng chọn bộ tiêu chí đánh giá trước khi phân công chấm bài.
                        </div>
                    <?php elseif (empty($responses)): ?>
                        <div class="alert alert-warning">
                            <i class="bi bi-exclamation-triangle"></i> 
                            Chưa có bài nộp nào hoàn thành (status = 2) cho bài tập này.
                        </div>
                    <?php else: ?>
                        <form id="assignEvaluatorsForm" method="POST" action="/api/evaluate/assign_evaluators.php">
                            <input type="hidden" name="task_id" value="<?= $task_id ?>">
                            
                            <div class="alert alert-info">
                                <strong>Hướng dẫn:</strong> Chọn người chấm cho từng bài nộp. Một bài có thể được chấm bởi nhiều người.<br>
                                <small>* Bạn có thể chọn nhiều người chấm cho cùng một bài bằng cách click vào nút "+ Thêm người chấm"</small>
                            </div>
                            
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead class="table-light">
                                        <tr>
                                            <th width="25%">Bài nộp</th>
                                            <th width="15%">Loại</th>
                                            <th width="10%">Trạng thái</th>
                                            <th width="40%">Người chấm</th>
                                            <th width="10%">Hành động</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($responses as $deliver): ?>
                                            <tr data-deliver-id="<?= $deliver['id_deliver'] ?>" data-response-id="<?= $deliver['response'] ?>">
                                                <td>
                                                    <strong><?= htmlspecialchars($deliver['submitter_name']) ?></strong>
                                                    <br><small class="text-muted">
                                                        Giao lúc: <?= date('d/m/Y H:i', strtotime($deliver['created_at'])) ?>
                                                    </small>
                                                </td>
                                                <td>
                                                    <?php if ($deliver['deliver_type'] == 'user'): ?>
                                                        <span class="badge bg-primary">Cá nhân</span>
                                                    <?php elseif ($deliver['deliver_type'] == 'group'): ?>
                                                        <span class="badge bg-info">Nhóm</span>
                                                    <?php else: ?>
                                                        <span class="badge bg-secondary">Cả lớp</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <?php if ($deliver['deliver_status'] == 0): ?>
                                                        <span class="badge bg-secondary">Đã giao</span>
                                                    <?php elseif ($deliver['deliver_status'] == 1): ?>
                                                        <span class="badge bg-warning">Đang làm</span>
                                                    <?php else: ?>
                                                        <span class="badge bg-success">Đã nộp</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <div class="evaluators-container" data-response-id="<?= $deliver['response'] ?>">
                                                        <?php 
                                                        $assignments = $evaluatorAssignments[$deliver['response']] ?? [];
                                                        if (empty($assignments)): 
                                                        ?>
                                                            <div class="evaluator-row mb-2">
                                                                <div class="input-group">
                                                                    <select name="assignments[<?= $deliver['response'] ?>][]" 
                                                                            class="form-select form-select-sm evaluator-select"
                                                                            data-deliver-type="<?= $deliver['deliver_type'] ?>"
                                                                            data-deliver-value="<?= $deliver['deliver_value'] ?>">
                                                                        <option value="">-- Chọn người chấm --</option>
                                                                        <optgroup label="Học sinh">
                                                                            <?php foreach ($students as $student): ?>
                                                                                <?php 
                                                                                // Don't allow submitter to evaluate their own work
                                                                                if ($deliver['deliver_type'] == 'user' && $deliver['deliver_value'] == $student['user_id']) continue; 
                                                                                ?>
                                                                                <option value="user_<?= $student['user_id'] ?>">
                                                                                    <?= htmlspecialchars($student['fullname']) ?>
                                                                                </option>
                                                                            <?php endforeach; ?>
                                                                        </optgroup>
                                                                        <optgroup label="Nhóm">
                                                                            <?php foreach ($groups as $group): ?>
                                                                                <?php if ($deliver['deliver_type'] == 'group' && $deliver['deliver_value'] == $group['group_id']) continue; ?>
                                                                                <option value="group_<?= $group['group_id'] ?>">
                                                                                    <?= htmlspecialchars($group['group_name']) ?>
                                                                                </option>
                                                                            <?php endforeach; ?>
                                                                        </optgroup>
                                                                    </select>
                                                                    <button type="button" class="btn btn-sm btn-danger remove-evaluator" style="display:none;">
                                                                        <i class="bi bi-x"></i>
                                                                    </button>
                                                                </div>
                                                            </div>
                                                        <?php else: ?>
                                                            <?php foreach ($assignments as $assignment): ?>
                                                                <div class="evaluator-row mb-2">
                                                                    <div class="input-group">
                                                                        <select name="assignments[<?= $deliver['response'] ?>][]" 
                                                                                class="form-select form-select-sm evaluator-select"
                                                                                data-deliver-type="<?= $deliver['deliver_type'] ?>"
                                                                                data-deliver-value="<?= $deliver['deliver_value'] ?>">
                                                                            <option value="">-- Chọn người chấm --</option>
                                                                            <optgroup label="Học sinh">
                                                                                <?php foreach ($students as $student): ?>
                                                                                    <?php if ($deliver['deliver_type'] == 'user' && $deliver['deliver_value'] == $student['user_id']) continue; ?>
                                                                                    <option value="user_<?= $student['user_id'] ?>"
                                                                                        <?= ($assignment['evaluator_type'] == 'user' && $assignment['evaluator_value'] == $student['user_id']) ? 'selected' : '' ?>>
                                                                                        <?= htmlspecialchars($student['fullname']) ?>
                                                                                    </option>
                                                                                <?php endforeach; ?>
                                                                            </optgroup>
                                                                            <optgroup label="Nhóm">
                                                                                <?php foreach ($groups as $group): ?>
                                                                                    <?php if ($deliver['deliver_type'] == 'group' && $deliver['deliver_value'] == $group['group_id']) continue; ?>
                                                                                    <option value="group_<?= $group['group_id'] ?>"
                                                                                        <?= ($assignment['evaluator_type'] == 'group' && $assignment['evaluator_value'] == $group['group_id']) ? 'selected' : '' ?>>
                                                                                        <?= htmlspecialchars($group['group_name']) ?>
                                                                                    </option>
                                                                                <?php endforeach; ?>
                                                                            </optgroup>
                                                                        </select>
                                                                        <button type="button" class="btn btn-sm btn-danger remove-evaluator">
                                                                            <i class="bi bi-x"></i>
                                                                        </button>
                                                                    </div>
                                                                </div>
                                                            <?php endforeach; ?>
                                                        <?php endif; ?>
                                                    </div>
                                                    <button type="button" class="btn btn-sm btn-outline-success add-evaluator" 
                                                            data-response-id="<?= $deliver['response'] ?>">
                                                        <i class="bi bi-plus-circle"></i> Thêm người chấm
                                                    </button>
                                                </td>
                                                <td>
                                                    <?php if (!empty($assignments)): ?>
                                                        <span class="badge bg-success" title="Đã có <?= count($assignments) ?> người chấm">
                                                            <i class="bi bi-check-circle"></i> <?= count($assignments) ?>
                                                        </span>
                                                    <?php else: ?>
                                                        <span class="badge bg-warning">
                                                            <i class="bi bi-exclamation-circle"></i> Chưa phân công
                                                        </span>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                            
                            <div class="text-center mt-3">
                                <button type="submit" class="btn btn-primary btn-lg">
                                    <i class="bi bi-save"></i> Lưu phân công chấm bài
                                </button>
                            </div>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
        </section>

    </main><!-- End #main -->

    <?php
    require_once 'views/templates/footer.php';
    require_once 'views/templates/vendors.php';
    ?>

    <script>
        // Auto assign random
        function autoAssignRandom() {
            const selects = document.querySelectorAll('.evaluator-select');
            selects.forEach(select => {
                const options = Array.from(select.options).filter(opt => opt.value !== '');
                if (options.length > 0) {
                    const randomOption = options[Math.floor(Math.random() * options.length)];
                    select.value = randomOption.value;
                }
            });
        }

        // Auto assign round-robin
        function autoAssignRoundRobin() {
            const selects = document.querySelectorAll('.evaluator-select');
            const allOptions = [];

            // Collect all available evaluators
            if (selects.length > 0) {
                const firstSelect = selects[0];
                Array.from(firstSelect.options).forEach(opt => {
                    if (opt.value !== '') allOptions.push(opt.value);
                });
            }

            if (allOptions.length === 0) return;

            let currentIndex = 0;
            selects.forEach(select => {
                select.value = allOptions[currentIndex % allOptions.length];
                currentIndex++;
            });
        }

        // Clear all assignments
        function clearAllAssignments() {
            if (confirm('Bạn có chắc muốn xóa tất cả phân công chấm bài?')) {
                document.querySelectorAll('.evaluator-select').forEach(select => {
                    select.value = '';
                });
            }
        }

        // Form submission
        document.getElementById('criteriaForm')?.addEventListener('submit', async function (e) {
            e.preventDefault();

            const formData = new FormData(this);

            try {
                const response = await fetch(this.action, {
                    method: 'POST',
                    body: formData
                });

                const result = await response.json();

                if (result.success) {
                    alert('Đã lưu cấu hình bộ tiêu chí thành công!');
                    location.reload();
                } else {
                    alert('Lỗi: ' + result.message);
                }
            } catch (error) {
                console.error('Error:', error);
                alert('Có lỗi xảy ra khi lưu cấu hình!');
            }
        });

        document.getElementById('assignEvaluatorsForm')?.addEventListener('submit', async function (e) {
            e.preventDefault();

            const formData = new FormData(this);
            const taskId = formData.get('task_id');
            
            // Build assignments array
            const assignments = [];
            
            // Parse assignments from FormData
            for (const [key, value] of formData.entries()) {
                if (key.startsWith('assignments[') && value) {
                    const responseId = key.match(/assignments\[(\d+)\]/)[1];
                    const [evaluatorType, evaluatorValue] = value.split('_');
                    
                    assignments.push({
                        response_id: parseInt(responseId),
                        evaluator_type: evaluatorType,
                        evaluator_value: parseInt(evaluatorValue)
                    });
                }
            }
            
            if (assignments.length === 0) {
                alert('Vui lòng phân công ít nhất một người chấm bài');
                return;
            }
            
            // Send as FormData with JSON string
            const payload = new FormData();
            payload.append('task_id', taskId);
            payload.append('assignments', JSON.stringify(assignments));

            try {
                const response = await fetch(this.action, {
                    method: 'POST',
                    body: payload
                });

                const result = await response.json();

                if (result.success) {
                    alert(result.message);
                    location.reload();
                } else {
                    alert('Lỗi: ' + result.message);
                    if (result.errors && result.errors.length > 0) {
                        console.error('Errors:', result.errors);
                    }
                }
            } catch (error) {
                console.error('Error:', error);
                alert('Có lỗi xảy ra khi phân công chấm bài!');
            }
        });
        
        // Add evaluator row
        document.addEventListener('click', function(e) {
            if (e.target.classList.contains('add-evaluator') || e.target.closest('.add-evaluator')) {
                const button = e.target.classList.contains('add-evaluator') ? e.target : e.target.closest('.add-evaluator');
                const responseId = button.dataset.responseId;
                const container = document.querySelector(`.evaluators-container[data-response-id="${responseId}"]`);
                
                // Get first select to clone options
                const firstSelect = container.querySelector('select');
                const newRow = document.createElement('div');
                newRow.className = 'evaluator-row mb-2';
                newRow.innerHTML = `
                    <div class="input-group">
                        <select name="assignments[${responseId}][]" class="form-select form-select-sm evaluator-select">
                            ${firstSelect.innerHTML}
                        </select>
                        <button type="button" class="btn btn-sm btn-danger remove-evaluator">
                            <i class="bi bi-x"></i>
                        </button>
                    </div>
                `;
                
                container.appendChild(newRow);
                
                // Show remove buttons for all rows now
                container.querySelectorAll('.remove-evaluator').forEach(btn => {
                    btn.style.display = '';
                });
            }
            
            // Remove evaluator row
            if (e.target.classList.contains('remove-evaluator') || e.target.closest('.remove-evaluator')) {
                const button = e.target.classList.contains('remove-evaluator') ? e.target : e.target.closest('.remove-evaluator');
                const row = button.closest('.evaluator-row');
                const container = row.closest('.evaluators-container');
                
                row.remove();
                
                // Hide remove button if only one row left
                const remainingRows = container.querySelectorAll('.evaluator-row');
                if (remainingRows.length === 1) {
                    remainingRows[0].querySelector('.remove-evaluator').style.display = 'none';
                }
            }
        });
    </script>
</body>

</html>