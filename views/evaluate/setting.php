<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php require_once __DIR__ . '/../templates/assets.php'; ?>
    <title data-lang-key="evaluate_setting_title">Peer Evaluation Configuration</title>
</head>

<body>
    <?php
    require_once __DIR__ . '/../../config/db.php';
    require_once __DIR__ . '/../../config/helper_class.php';
    require_once __DIR__ . '/../../config/helper_homework.php';
    require_once __DIR__ . '/../../config/helper_breadcrumb.php';
    require_once __DIR__ . '/../../config/helper_criteria_i18n.php';
    require_once __DIR__ . '/../../config/helper_phase.php';

    $task_id = isset($_GET['task_id']) ? intval($_GET['task_id']) : 0;
    $topic_id = isset($_GET['topic_id']) ? intval($_GET['topic_id']) : 0;
    $phase_id = isset($_GET['phase_id']) ? intval($_GET['phase_id']) : 0;
    $userId = (int)($_SESSION['user']['user_id'] ?? 0);

    if (!$task_id || !$topic_id) {
        header('Location: ' . URL_ROOT . '/404');
        exit;
    }

    requireIdsInTopic($topic_id, $task_id, $phase_id);
    if (!isTeacherInTopicClass($topic_id, $userId)) {
        header('Location: ' . URL_ROOT . '/403');
        exit;
    }

    require_once __DIR__ . '/../templates/header.php';
    require_once __DIR__ . '/../templates/sidebar.php';

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
        ?>
        <main id="main" class="main">
            <div class="pagetitle">
                <h1><i class="bi bi-exclamation-triangle text-warning"></i> <span data-lang-key="cannot_access">Cannot Access</span></h1>
            </div>
            <section class="section">
                <div class="card">
                    <div class="card-body text-center py-5">
                        <i class="bi bi-gear-fill text-muted" style="font-size: 4rem;"></i>
                        <h3 class="mt-3" data-lang-key="peer_eval_config_page">Peer Evaluation Configuration Page</h3>
                        <p class="text-muted" data-lang-key="page_only_for_peer_eval">This page is only available for phases with type <strong>"Peer Evaluation"</strong></p>

                        <?php if ($phaseInfo): ?>
                            <div class="alert alert-warning mt-4">
                                <strong data-lang-key="current_phase">Current Phase:</strong>
                                <?= htmlspecialchars($phaseInfo['m_phase_name'] ?? 'Unknown') ?><br>
                                <small>Please select a phase with type "Peer Evaluation" (PHASE_DANHGIANGUOIHOC) to configure peer evaluation.</small>
                            </div>
                        <?php endif; ?>

                        <a href="javascript:history.back()" class="btn btn-primary mt-3">
                            <i class="bi bi-arrow-left"></i> <span data-lang-key="go_back">Go Back</span>
                        </a>
                    </div>
                </div>
            </section>
        </main>
        <?php
        require_once __DIR__ . '/../templates/footer.php';
        require_once __DIR__ . '/../templates/vendors.php';
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
            <h1><i class="bi bi-gear-fill"></i> <span data-lang-key="evaluate_setting_title">Peer Evaluation Configuration</span></h1>
            <nav>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="<?= breadcrumbClassUrl() ?>"><?= breadcrumbClassLabel() ?></a></li>
                    <li class="breadcrumb-item"><a href="<?= breadcrumbTopicPhasesUrl($topic_id) ?>"><?= htmlspecialchars($taskInfo['topic_name'] ?? 'Topic') ?></a></li>
                    <li class="breadcrumb-item"><a href="<?= breadcrumbPhaseTasksUrl($topic_id, $phase_id) ?>">Learning Phases</a></li>
                    <li class="breadcrumb-item active">Peer Evaluation Configuration</li>
                </ol>
            </nav>
        </div><!-- End Page Title -->

        <section class="section">
            <!-- Task Info Card -->
            <div class="card mb-4">
                <div class="card-body">
                    <h5 class="card-title"><i class="bi bi-info-circle"></i> <span data-lang-key="task_info_label">Task Info</span></h5>
                    <div class="row">
                        <div class="col-md-8">
                            <p><strong>Task Name:</strong> <?= htmlspecialchars($taskInfo['task_title']) ?></p>
                            <p><strong>Description:</strong>
                                <?= htmlspecialchars($taskInfo['task_description'] ?? 'No description') ?></p>
                        </div>
                        <div class="col-md-4 text-end">
                            <span class="badge bg-info"><span>Total Submissions:</span> <?= count($responses) ?></span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Criteria Selection Card -->
            <div class="card mb-4">
                <div class="card-body">
                    <h5 class="card-title"><i class="bi bi-clipboard-check"></i> <span data-lang-key="criteria_set">Evaluation Criteria Set</span></h5>

                    <?php if ($hasEvaluations): ?>
                        <div class="alert alert-warning">
                            <i class="bi bi-exclamation-triangle-fill"></i>
                            <span>Evaluations have already been completed. Cannot change the criteria set.</span>
                        </div>
                        <?php if ($currentCriteriaGroupId): ?>
                            <?php
                            $stmtCurrentGroup = $pdo->prepare("SELECT ten_nhom_tc FROM nhom_tc WHERE nhom_tc_id = :id");
                            $stmtCurrentGroup->execute([':id' => $currentCriteriaGroupId]);
                            $currentGroup = $stmtCurrentGroup->fetch(PDO::FETCH_ASSOC);
                            ?>
                            <p><strong>Current Criteria Set:</strong>
                                <span class="badge bg-success"><?= htmlspecialchars(criteriaLabel($currentGroup['ten_nhom_tc'])) ?></span>
                            </p>
                        <?php endif; ?>
                    <?php else: ?>
                        <form id="criteriaForm" method="POST" action="/api/evaluate/set_criteria.php">
                            <input type="hidden" name="task_id" value="<?= $task_id ?>">
                            <div class="row">
                                <div class="col-md-8">
                                    <select name="nhom_tc_id" class="form-select" required <?= empty($criteriaGroups) ? 'disabled' : '' ?>>
                                    <option value="">-- -- Select Criteria Set --</option>
                                        <?php foreach ($criteriaGroups as $group): ?>
                                            <option value="<?= $group['nhom_tc_id'] ?>"
                                                <?= $currentCriteriaGroupId == $group['nhom_tc_id'] ? 'selected' : '' ?>>
                                                <?= htmlspecialchars(criteriaLabel($group['ten_nhom_tc'])) ?>
                                                <?= $group['description'] ? ' - ' . htmlspecialchars(criteriaLabel($group['description'])) : '' ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <?php if (empty($criteriaGroups)): ?>
                                        <small class="text-muted"><span>No criteria sets for this class yet.</span>
                                            <a href="/class/criteria?class_id=<?= $class_id ?>">Create new criteria set</a>
                                        </small>
                                    <?php endif; ?>
                                </div>
                                <div class="col-md-4">
                                    <button type="submit" class="btn btn-primary w-100" <?= empty($criteriaGroups) ? 'disabled' : '' ?>>
                                        <i class="bi bi-save"></i> <span>Save Config</span>
                                    </button>
                                </div>
                            </div>
                        </form>

                        <?php if ($currentCriteriaGroupId): ?>
                            <div class="mt-3">
                                <h6>Criteria in set:</h6>
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
                                        <?= htmlspecialchars(criteriaLabel($criterion['name_tc'])) ?>
                                        (<?= $criterion['min_score'] ?>-<?= $criterion['max_score'] ?> points)
                                    </span>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Anonymous Evaluation Toggle -->
            <div class="card mb-4">
                <div class="card-body">
                    <h5 class="card-title"><i class="bi bi-eye-slash"></i> Đánh giá ẩn danh</h5>
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <p class="mb-1">Kích hoạt chế độ ẩn danh khi chấm điểm.</p>
                            <small class="text-muted">Khi bật, học viên sẽ không thấy tên tác giả bài nộp khi đánh giá.</small>
                        </div>
                        <div class="form-check form-switch">
                            <?php
                            $isAnonymous = 0;
                            try {
                                $stmtAnon = $pdo->prepare("SELECT is_anonymous FROM setting WHERE task_id = :task_id");
                                $stmtAnon->execute([':task_id' => $task_id]);
                                $anonRow = $stmtAnon->fetch(PDO::FETCH_ASSOC);
                                $isAnonymous = $anonRow ? (int)$anonRow['is_anonymous'] : 0;
                            } catch (PDOException $e) {
                                $isAnonymous = 0;
                            }
                            ?>
                            <input class="form-check-input" type="checkbox" id="anonymousToggle"
                                <?= $isAnonymous ? 'checked' : '' ?>
                                onchange="toggleAnonymous(this)">
                            <label class="form-check-label" for="anonymousToggle">
                                <?= $isAnonymous ? 'Đang bật' : 'Đang tắt' ?>
                            </label>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Deliver Assignment Card -->
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title"><i class="bi bi-people-fill"></i> <span data-lang-key="assign_graders">Assign Graders</span></h5>

                    <?php if (!$currentCriteriaGroupId): ?>
                        <div class="alert alert-info">
                            <i class="bi bi-info-circle"></i> 
                            <span>Please select an evaluation criteria set before assigning graders.</span>
                        </div>
                    <?php elseif (empty($responses)): ?>
                        <div class="alert alert-warning">
                            <i class="bi bi-exclamation-triangle"></i> 
                            <span>No completed submissions (status = 2) for this task yet.</span>
                        </div>
                    <?php else: ?>
                        <form id="assignEvaluatorsForm" method="POST" action="/api/evaluate/assign_evaluators.php">
                            <input type="hidden" name="task_id" value="<?= $task_id ?>">
                            
                            <div class="alert alert-info">
                                <strong>Guide:</strong> <span>Select a grader for each submission. A submission can be graded by multiple people.</span><br>
                                <small>* You can select multiple graders for the same submission by clicking the "+ Add Grader" button</small>
                            </div>
                            
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead class="table-light">
                                        <tr>
                                            <th width="25%">Submission</th>
                                            <th width="15%">Type</th>
                                            <th width="10%">Status</th>
                                            <th width="40%">Graders</th>
                                            <th width="10%">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($responses as $deliver): ?>
                                            <tr data-deliver-id="<?= $deliver['id_deliver'] ?>" data-response-id="<?= $deliver['response'] ?>">
                                                <td>
                                                    <strong><?= htmlspecialchars($deliver['submitter_name']) ?></strong>
                                                    <br><small class="text-muted">
                                                        <span>Assigned at:</span> <?= date('d/m/Y H:i', strtotime($deliver['created_at'])) ?>
                                                    </small>
                                                </td>
                                                <td>
                                                    <?php if ($deliver['deliver_type'] == 'user'): ?>
                                                        <span class="badge bg-primary">Personal</span>
                                                    <?php elseif ($deliver['deliver_type'] == 'group'): ?>
                                                        <span class="badge bg-info">Group</span>
                                                    <?php else: ?>
                                                        <span class="badge bg-secondary">Whole Class</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <?php if ($deliver['deliver_status'] == 0): ?>
                                                        <span class="badge bg-secondary">Assigned</span>
                                                    <?php elseif ($deliver['deliver_status'] == 1): ?>
                                                        <span class="badge bg-warning">In Progress</span>
                                                    <?php else: ?>
                                                        <span class="badge bg-success">Submitted</span>
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
                                                                        <option value="">-- -- Select Grader --</option>
                                                                        <optgroup label="Students">
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
                                                                        <optgroup label="Group">
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
                                                                            <option value="">-- -- Select Grader --</option>
                                                                            <optgroup label="Students">
                                                                                <?php foreach ($students as $student): ?>
                                                                                    <?php if ($deliver['deliver_type'] == 'user' && $deliver['deliver_value'] == $student['user_id']) continue; ?>
                                                                                    <option value="user_<?= $student['user_id'] ?>"
                                                                                        <?= ($assignment['evaluator_type'] == 'user' && $assignment['evaluator_value'] == $student['user_id']) ? 'selected' : '' ?>>
                                                                                        <?= htmlspecialchars($student['fullname']) ?>
                                                                                    </option>
                                                                                <?php endforeach; ?>
                                                                            </optgroup>
                                                                            <optgroup label="Group">
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
                                                        <i class="bi bi-plus-circle"></i> <span>Add Grader</span>
                                                    </button>
                                                </td>
                                                <td>
                                                    <?php if (!empty($assignments)): ?>
                                                        <span class="badge bg-success" title="Already has <?= count($assignments) ?> grader(s)">
                                                            <i class="bi bi-check-circle"></i> <?= count($assignments) ?>
                                                        </span>
                                                    <?php else: ?>
                                                        <span class="badge bg-warning">
                                                            <i class="bi bi-exclamation-circle"></i> <span>Not Assigned</span>
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
                                    <i class="bi bi-save"></i> <span>Save Grading Assignment</span>
                                </button>
                            </div>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
        </section>

    </main><!-- End #main -->

    <?php
    require_once __DIR__ . '/../templates/footer.php';
    require_once __DIR__ . '/../templates/vendors.php';
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
            if (confirm('Clear all evaluator assignments?')) {
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
                    alert('Configuration saved successfully!');
                    location.reload();
                } else {
                    alert('Error: ' + result.message);
                }
            } catch (error) {
                console.error('Error:', error);
                alert('Error saving configuration');
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
                alert('Please assign at least one grader');
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
                    alert('Error: ' + result.message);
                    if (result.errors && result.errors.length > 0) {
                        console.error('Errors:', result.errors);
                    }
                }
            } catch (error) {
                console.error('Error:', error);
                alert('Error assigning graders');
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

        // Handle optgroup label translations (label attribute is not textContent)
        function applyOptgroupLabels() {
            document.querySelectorAll('[data-lang-label-key]').forEach(function(el) {
                el.setAttribute('label', el.getAttribute('data-lang-label-key'));
            });
        }
        setTimeout(applyOptgroupLabels, 200);

        async function toggleAnonymous(checkbox) {
            const label = checkbox.nextElementSibling;
            try {
                const resp = await fetch('/api/evaluate/toggle_anonymous.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        task_id: <?= $task_id ?>,
                        is_anonymous: checkbox.checked ? 1 : 0
                    })
                });
                const data = await resp.json();
                if (data.success) {
                    label.textContent = checkbox.checked ? 'Đang bật' : 'Đang tắt';
                } else {
                    checkbox.checked = !checkbox.checked;
                    label.textContent = checkbox.checked ? 'Đang bật' : 'Đang tắt';
                    alert(data.message || 'Lỗi cập nhật');
                }
            } catch (e) {
                checkbox.checked = !checkbox.checked;
                label.textContent = checkbox.checked ? 'Đang bật' : 'Đang tắt';
                alert('Lỗi kết nối');
            }
        }
    </script>
</body>

</html>