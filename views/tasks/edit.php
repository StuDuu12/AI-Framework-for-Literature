<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php require_once __DIR__ . '/../templates/assets.php'; ?>
    <title data-lang-key="edit_task">Edit Task - Literature System</title>
</head>

<body>
    <?php
    require_once __DIR__ . '/../../config/db.php';
    require_once __DIR__ . '/../../config/helper_task.php';
    require_once __DIR__ . '/../../config/helper_phase.php';
    require_once __DIR__ . '/../../config/helper_class.php';
    require_once __DIR__ . '/../templates/header.php';
    require_once __DIR__ . '/../templates/sidebar.php';
    


    // Get IDs from URL
    $taskId = isset($_GET['task_id']) ? intval($_GET['task_id']) : 0;
    $phaseId = isset($_GET['phase_id']) ? intval($_GET['phase_id']) : 0;
    $topicId = isset($_GET['topic_id']) ? intval($_GET['topic_id']) : 0;

    if (!$taskId || !$phaseId || !$topicId) {
        header('Location: /class');
        exit;
    }

    // Get task details
    $task = getTaskDetails($taskId);
    if (!$task) {
        header('Location: /class');
        exit;
    }

    // Get phase details
    $phase = getPhaseDetails($phaseId);
    if (!$phase) {
        header('Location: /class');
        exit;
    }

    // Get topic details
    $topic = getTopicDetails($topicId);
    if (!$topic) {
        header('Location: /class');
        exit;
    }

    // Check if user is teacher
    $isTeacher = isTeacherInTopicClass($topicId, $_SESSION['user']['user_id']);
    if (!$isTeacher) {
        header('Location: /class');
        exit;
    }

    // Check if this is an evaluation phase
    $isEvaluationPhase = isEvaluationPhase($phase['phase_code']);

    // Get source tasks if this is an evaluation phase
    $sourceTasks = [];
    if ($isEvaluationPhase) {
        $sourceTasks = getEvaluationSourceTasks($topicId);
    }

    // Read existing file content if exists
    $existingContent = '';
    if ($task['file_content']) {
        $existingContent = readTaskFileContent($task['file_content']);
    }
    ?>

    <main id="main" class="main">
        <div class="pagetitle">
            <h1 data-lang-key="edit_task">Edit Task</h1>
            <nav>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="/class"><?= $_SESSION['current_class_name']; ?></a></li>
                    <li class="breadcrumb-item"><a href="/class"><?= htmlspecialchars($topic['topic_name']) ?></a></li>
                    <li class="breadcrumb-item"><a href="<?= URL_ROOT ?>/class/phases?topic_id=<?= $topicId ?>" data-lang-key="learning_phases">Learning Phases</a></li>
                    <li class="breadcrumb-item"><a
                            href="<?= URL_ROOT ?>/class/task?topic_id=<?= $topicId ?>&phase_id=<?= $phaseId ?>" data-lang-key="tasks">Tasks</a></li>
                    <li class="breadcrumb-item active" data-lang-key="edit">Edit</li>
                </ol>
            </nav>
        </div><!-- End Page Title -->

        <section class="section">
            <div class="row">
                <div class="col-lg-8">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title" data-lang-key="task_info">Task Information</h5>

                            <form id="editTaskForm" enctype="multipart/form-data">
                                <input type="hidden" name="task_id" value="<?= $taskId ?>">
                                <input type="hidden" name="phase_id" value="<?= $phaseId ?>">
                                <input type="hidden" name="topic_id" value="<?= $topicId ?>">

                                <!-- Task Title -->
                                <div class="mb-3">
                                    <label class="form-label">Task Title <span
                                            class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="taskTitle" name="title"
                                        value="<?= htmlspecialchars($task['title']) ?>"
                                        placeholder="Enter task title..." required>
                                </div>

                                <!-- Task Description -->
                                <div class="mb-3">
                                    <label class="form-label">Description</label>
                                    <textarea class="form-control" id="taskDescription" name="description" rows="3"
                                        placeholder="Enter task description (optional)..."><?= htmlspecialchars($task['description'] ?? '') ?></textarea>
                                </div>

                                <?php if ($isEvaluationPhase): ?>
                                    <!-- Evaluation Phase: Select Source Task -->
                                    <div class="mb-3">
                                        <label class="form-label">Evaluate based on task content <span
                                                class="text-danger">*</span></label>

                                        <?php if ($task['review_task']): ?>
                                            <?php
                                            $currentSourceTask = getTaskDetails($task['review_task']);
                                            if ($currentSourceTask):
                                                $currentSourcePhase = getPhaseDetails($currentSourceTask['phase_id']);
                                                ?>
                                                <div class="alert alert-info mb-3">
                                                    <i class="bi bi-info-circle"></i>
                                                    <strong>Currently evaluating:</strong>
                                                    [<?= htmlspecialchars($currentSourcePhase['phase_name'] ?? 'N/A') ?>]
                                                    <?= htmlspecialchars($currentSourceTask['title']) ?>
                                                </div>
                                            <?php endif; ?>
                                        <?php endif; ?>

                                        <?php if (empty($sourceTasks)): ?>
                                            <div class="alert alert-warning" role="alert">
                                                <i class="bi bi-exclamation-triangle"></i>
                                                There are no tasks from <strong>Starter</strong> or <strong>Homework</strong>
                                                phases to evaluate.
                                            </div>
                                        <?php else: ?>
                                            <select class="form-select" name="source_task_id" id="sourceTaskSelect" required>
                                                <option value="">-- Select task to evaluate --</option>
                                                <?php foreach ($sourceTasks as $sourceTask): ?>
                                                    <option value="<?= $sourceTask['task_id'] ?>"
                                                        data-description="<?= htmlspecialchars($sourceTask['description']) ?>"
                                                        <?= ($task['review_task'] == $sourceTask['task_id']) ? 'selected' : '' ?>>
                                                        [<?= htmlspecialchars($sourceTask['phase_name']) ?>]
                                                        <?= htmlspecialchars($sourceTask['title']) ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                            <div class="form-text">
                                                <i class="bi bi-info-circle"></i>
                                                Select a task from <strong>Starter</strong> or <strong>Homework</strong>
                                            </div>

                                            <!-- Preview selected task -->
                                            <!-- <div id="taskPreview" class="mt-3" style="display: none;">
                                                <div class="card border-info">
                                                    <div class="card-body">
                                                        <h6 class="card-subtitle mb-2 text-muted">Selected task description:</h6>
                                                        <p class="card-text" id="taskPreviewText"></p>
                                                    </div>
                                                </div>
                                            </div> -->
                                        <?php endif; ?>
                                    </div>
                                <?php else: ?>
                                    <!-- Regular Phase: Content Management -->
                                    <!-- Existing Content Display -->
                                    <?php if ($task['file_content']): ?>
                                        <div class="mb-3">
                                            <label class="form-label">Current Content</label>
                                            <div class="alert alert-info">
                                                <i class="bi bi-file-earmark-text"></i>
                                                Content exists
                                                <?php if ($task['content_creator']): ?>
                                                    (Created by <?= htmlspecialchars($task['content_creator_name']) ?>)
                                                <?php else: ?>
                                                    (From uploaded file)
                                                <?php endif; ?>
                                            </div>
                                            <?php if ($existingContent !== false): ?>
                                                <div class="border p-3 bg-light" style="max-height: 200px; overflow-y: auto;">
                                                    <pre class="mb-0"><?= htmlspecialchars($existingContent) ?></pre>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    <?php endif; ?>

                                    <!-- Keep or Replace Content -->
                                    <div class="mb-3">
                                        <label class="form-label">Task Content</label>
                                        <div class="form-check form-switch mb-3">
                                            <input class="form-check-input" type="checkbox" id="replaceContent"
                                                name="keep_old_file" value="no">
                                            <label class="form-check-label" for="replaceContent">
                                                <?= $task['file_content'] ? 'Change current content' : 'Add new content' ?>
                                            </label>
                                        </div>

                                        <div id="contentReplaceArea" style="display: none;">
                                            <div class="btn-group w-100 mb-3" role="group">
                                                <input type="radio" class="btn-check" name="content_type"
                                                    id="contentTypeText" value="text" checked autocomplete="off">
                                                <label class="btn btn-outline-primary" for="contentTypeText">
                                                    <i class="bi bi-textarea-t"></i> Enter text
                                                </label>

                                                <input type="radio" class="btn-check" name="content_type"
                                                    id="contentTypeFile" value="file" autocomplete="off">
                                                <label class="btn btn-outline-primary" for="contentTypeFile">
                                                    <i class="bi bi-file-earmark-arrow-up"></i> Upload file
                                                </label>
                                            </div>

                                            <!-- Text Content Area -->
                                            <div id="textContentArea">
                                                <label class="form-label">Enter new text content</label>
                                                <textarea class="form-control" id="contentText" name="content_text" rows="8"
                                                    placeholder="Paste new content here..."></textarea>
                                            </div>

                                            <!-- File Upload Area -->
                                            <div id="fileContentArea" style="display: none;">
                                                <label class="form-label">Upload new file (.txt only)</label>
                                                <input class="form-control" type="file" id="contentFile" name="content_file"
                                                    accept=".txt">
                                            </div>
                                        </div>
                                    </div>
                                <?php endif; ?>

                                <!-- Deadline -->
                                <div class="mb-3">
                                    <label class="form-label">Deadline (tùy chọn)</label>
                                    <input type="datetime-local" class="form-control" id="taskDeadline" name="deadline"
                                        value="<?= !empty($task['deadline']) ? date('Y-m-d\TH:i', strtotime($task['deadline'])) : '' ?>">
                                    <div class="form-text">
                                        <i class="bi bi-info-circle"></i>
                                        Học viên sẽ thấy thông báo nhắc nhở trước khi hết hạn
                                    </div>
                                </div>

                                <!-- Buttons -->
                                <div class="d-flex gap-2">
                                    <button type="submit" class="btn btn-primary" id="submitBtn">
                                        <i class="bi bi-check-circle"></i> <span data-lang-key="update">Update</span>
                                    </button>
                                    <a href="<?= URL_ROOT ?>/class/task?topic_id=<?= $topicId ?>&phase_id=<?= $phaseId ?>"
                                        class="btn btn-secondary">
                                        <i class="bi bi-x-circle"></i> <span data-lang-key="cancel">Cancel</span>
                                    </a>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Info Card -->
                <div class="col-lg-4">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title">Information</h5>
                            <p><strong>Topic:</strong> <?= htmlspecialchars($topic['topic_name']) ?></p>
                            <p><strong>Phase:</strong> Step <?= $phase['phase_number'] ?> -
                                <?= htmlspecialchars($phase['phase_name']) ?>
                            </p>
                            <p><strong>Created by:</strong> <?= htmlspecialchars($task['added_by_name'] ?? 'N/A') ?></p>
                            <p><strong>Created at:</strong> <?= date('d/m/Y H:i', strtotime($task['created_at'])) ?></p>
                            <p><strong>Updated:</strong> <?= date('d/m/Y H:i', strtotime($task['updated_at'])) ?></p>
                        </div>
                    </div>
                </div>
            </div>
        </section>

    </main><!-- End #main -->

    <!-- Toast Container -->
    <div class="position-fixed top-0 end-0 p-3" style="z-index: 1100; margin-top: 60px;">
        <div id="toastNotification" class="toast" role="alert" aria-live="assertive" aria-atomic="true">
            <div class="toast-header">
                <i class="bi me-2" id="toastIcon"></i>
                <strong class="me-auto" id="toastTitle">Notification</strong>
                <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
            <div class="toast-body" id="toastMessage">
                Notification content
            </div>
        </div>
    </div>

    <?php
    require_once __DIR__ . '/../templates/footer.php';
    require_once __DIR__ . '/../templates/vendors.php';
    ?>

    <script>
        (function() {
            'use strict';

            // Toast utility function
            function showToast(message, type = 'success') {
                const toastEl = document.getElementById('toastNotification');
                const toastIcon = document.getElementById('toastIcon');
                const toastTitle = document.getElementById('toastTitle');
                const toastMessage = document.getElementById('toastMessage');
                const toastHeader = toastEl.querySelector('.toast-header');

                toastHeader.classList.remove('bg-success', 'bg-danger', 'bg-warning', 'bg-info', 'text-white');
                toastIcon.className = 'bi me-2';

                switch (type) {
                    case 'success':
                        toastHeader.classList.add('bg-success', 'text-white');
                        toastIcon.classList.add('bi-check-circle-fill');
                        toastTitle.textContent = 'Success';
                        break;
                    case 'error':
                        toastHeader.classList.add('bg-danger', 'text-white');
                        toastIcon.classList.add('bi-x-circle-fill');
                        toastTitle.textContent = 'Error';
                        break;
                    case 'warning':
                        toastHeader.classList.add('bg-warning', 'text-dark');
                        toastIcon.classList.add('bi-exclamation-triangle-fill');
                        toastTitle.textContent = 'Warning';
                        break;
                    case 'info':
                        toastHeader.classList.add('bg-info', 'text-white');
                        toastIcon.classList.add('bi-info-circle-fill');
                        toastTitle.textContent = 'Info';
                        break;
                }

                toastMessage.textContent = message;

                const toast = new bootstrap.Toast(toastEl, {
                    autohide: true,
                    delay: 3000
                });
                toast.show();
            }

            const isEvaluationPhase = <?= $isEvaluationPhase ? 'true' : 'false' ?>;

            // Task preview for evaluation phase
            if (isEvaluationPhase) {
                const sourceTaskSelect = document.getElementById('sourceTaskSelect');
                const taskPreview = document.getElementById('taskPreview');
                const taskPreviewText = document.getElementById('taskPreviewText');

                if (sourceTaskSelect) {
                    // Show preview if task is already selected
                    const selectedOption = sourceTaskSelect.options[sourceTaskSelect.selectedIndex];
                    if (selectedOption.value) {
                        const description = selectedOption.getAttribute('data-description');
                        if (description && description.trim()) {
                            taskPreviewText.textContent = description;
                            taskPreview.style.display = 'block';
                        }
                    }

                    // Handle selection change
                    sourceTaskSelect.addEventListener('change', function () {
                        const selectedOption = this.options[this.selectedIndex];
                        const description = selectedOption.getAttribute('data-description');

                        if (description && description.trim()) {
                            taskPreviewText.textContent = description;
                            taskPreview.style.display = 'block';
                        } else {
                            taskPreview.style.display = 'none';
                        }
                    });
                }
            }

            // Replace content toggle (only for non-evaluation phases)
            if (!isEvaluationPhase) {
                const replaceContentCheckbox = document.getElementById('replaceContent');
                const contentReplaceArea = document.getElementById('contentReplaceArea');
                const textContentArea = document.getElementById('textContentArea');
                const fileContentArea = document.getElementById('fileContentArea');
                const contentTypeRadios = document.getElementsByName('content_type');

                if (replaceContentCheckbox) {
                    replaceContentCheckbox.addEventListener('change', function () {
                        contentReplaceArea.style.display = this.checked ? 'block' : 'none';
                        if (!this.checked) {
                            document.getElementById('contentText').value = '';
                            document.getElementById('contentFile').value = '';
                        }
                    });
                }

                contentTypeRadios.forEach(radio => {
                    radio.addEventListener('change', function () {
                        if (this.value === 'text') {
                            textContentArea.style.display = 'block';
                            fileContentArea.style.display = 'none';
                            document.getElementById('contentFile').value = '';
                        } else if (this.value === 'file') {
                            textContentArea.style.display = 'none';
                            fileContentArea.style.display = 'block';
                            document.getElementById('contentText').value = '';
                        }
                    });
                });
            }

            // Form submission
            document.getElementById('editTaskForm').addEventListener('submit', async function(e) {
                e.preventDefault();

                const title = document.getElementById('taskTitle').value.trim();

                if (!title) {
                    showToast('Please enter task title!', 'warning');
                    return;
                }

                // Validate based on phase type
                if (isEvaluationPhase) {
                    const sourceTaskId = document.querySelector('select[name="source_task_id"]')?.value;
                    if (!sourceTaskId) {
                        showToast('Please select a task to evaluate!', 'warning');
                        return;
                    }
                } else {
                    // Validate if replacing content
                    const replaceContentCheckbox = document.getElementById('replaceContent');
                    if (replaceContentCheckbox && replaceContentCheckbox.checked) {
                        const contentType = document.querySelector('input[name="content_type"]:checked').value;

                        if (contentType === 'text') {
                            const contentText = document.getElementById('contentText').value.trim();
                            if (!contentText) {
                                showToast('Please enter text content!', 'warning');
                                return;
                            }
                        } else if (contentType === 'file') {
                            const contentFile = document.getElementById('contentFile').files[0];
                            if (!contentFile) {
                                showToast('Please select a file to upload!', 'warning');
                                return;
                            }
                        }
                    }
                }

                const submitBtn = document.getElementById('submitBtn');
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Updating...';

                try {
                    const formData = new FormData(this);
                    formData.append('action', 'update_task');

                    // Set keep_old_file based on checkbox (only for non-evaluation phases)
                    if (!isEvaluationPhase) {
                        const replaceContentCheckbox = document.getElementById('replaceContent');
                        if (replaceContentCheckbox && !replaceContentCheckbox.checked) {
                            formData.set('keep_old_file', 'yes');
                        }
                    }

                    const response = await fetch('/api/manage_tasks.php', {
                        method: 'POST',
                        body: formData
                    });

                    const data = await response.json();

                    if (data.success) {
                        showToast('Task updated successfully!', 'success');
                        setTimeout(() => {
                            window.location.href = '/class/task?topic_id=<?= $topicId ?>&phase_id=<?= $phaseId ?>';
                        }, 1500);
                    } else {
                        showToast(data.message || 'Cannot update task', 'error');
                        submitBtn.disabled = false;
                        submitBtn.innerHTML = '<i class="bi bi-check-circle"></i> Update';
                    }
                } catch (error) {
                    console.error('Error:', error);
                    showToast('An error occurred while updating the task', 'error');
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = '<i class="bi bi-check-circle"></i> Update';
                }
            });
        })();
    </script>
</body>

</html>
