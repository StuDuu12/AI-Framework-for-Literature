<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php require_once __DIR__ . '/../templates/assets.php'; ?>
    <title data-lang-key="edit_task">Sửa nhiệm vụ</title>
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
    requireIdsInTopic($topicId, $taskId, $phaseId);
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
            <h1 data-lang-key="edit_task">Sửa nhiệm vụ</h1>
            <nav>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="/class"><?= $_SESSION['current_class_name']; ?></a></li>
                    <li class="breadcrumb-item"><a href="/class"><?= htmlspecialchars($topic['topic_name']) ?></a></li>
                    <li class="breadcrumb-item"><a href="<?= URL_ROOT ?>/class/phases?topic_id=<?= $topicId ?>" data-lang-key="learning_phases">Giai đoạn học tập</a></li>
                    <li class="breadcrumb-item"><a
                            href="<?= URL_ROOT ?>/class/task?topic_id=<?= $topicId ?>&phase_id=<?= $phaseId ?>" data-lang-key="tasks">Nhiệm vụ</a></li>
                    <li class="breadcrumb-item active" data-lang-key="edit">Sửa</li>
                </ol>
            </nav>
        </div><!-- End Page Title -->

        <section class="section">
            <div class="row">
                <div class="col-lg-8">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title" data-lang-key="task_info">Thông tin nhiệm vụ</h5>

                            <form id="editTaskForm" enctype="multipart/form-data">
                                <input type="hidden" name="task_id" value="<?= $taskId ?>">
                                <input type="hidden" name="phase_id" value="<?= $phaseId ?>">
                                <input type="hidden" name="topic_id" value="<?= $topicId ?>">

                                <!-- Task Title -->
                                <div class="mb-3">
                                    <label for="taskTitle" class="form-label">Tên nhiệm vụ <span
                                            class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="taskTitle" name="title"
                                        value="<?= htmlspecialchars($task['title']) ?>"
                                        placeholder="Nhập tên nhiệm vụ…" required>
                                </div>

                                <!-- Task Description -->
                                <div class="mb-3">
                                    <label for="taskDescription" class="form-label">Mô tả</label>
                                    <textarea class="form-control" id="taskDescription" name="description" rows="3"
                                        placeholder="Nhập mô tả nhiệm vụ (không bắt buộc)…"><?= htmlspecialchars($task['description'] ?? '') ?></textarea>
                                </div>

                                <?php if ($isEvaluationPhase): ?>
                                    <!-- Evaluation Phase: Select Source Task -->
                                    <div class="mb-3">
                                        <label class="form-label">Đánh giá dựa trên nội dung nhiệm vụ <span
                                                class="text-danger">*</span></label>

                                        <?php if ($task['review_task']): ?>
                                            <?php
                                            $currentSourceTask = getTaskDetails($task['review_task']);
                                            if ($currentSourceTask):
                                                $currentSourcePhase = getPhaseDetails($currentSourceTask['phase_id']);
                                                ?>
                                                <div class="alert alert-info mb-3">
                                                    <i class="bi bi-info-circle"></i>
                                                    <strong>Đang chấm:</strong>
                                                    [<?= htmlspecialchars($currentSourcePhase['phase_name'] ?? 'N/A') ?>]
                                                    <?= htmlspecialchars($currentSourceTask['title']) ?>
                                                </div>
                                            <?php endif; ?>
                                        <?php endif; ?>

                                        <?php if (empty($sourceTasks)): ?>
                                            <div class="alert alert-warning" role="alert">
                                                <i class="bi bi-exclamation-triangle"></i>
                                                Chưa có nhiệm vụ nào từ <strong>Gợi ý mở đầu</strong> hoặc <strong>Bài tập về nhà</strong>
                                                giai đoạn để đánh giá.
                                            </div>
                                        <?php else: ?>
                                            <select aria-label="Nhiệm vụ cần đánh giá" class="form-select" name="source_task_id" id="sourceTaskSelect" required>
                                                <option value="">-- Chọn nhiệm vụ cần đánh giá --</option>
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
                                                Chọn một nhiệm vụ từ <strong>Gợi ý mở đầu</strong> hoặc <strong>Bài tập về nhà</strong>
                                            </div>

                                            <!-- Preview selected task -->
                                            <!-- <div id="taskPreview" class="mt-3" style="display: none;">
                                                <div class="card border-info">
                                                    <div class="card-body">
                                                        <h6 class="card-subtitle mb-2 text-muted">Mô tả nhiệm vụ đã chọn:</h6>
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
                                            <label class="form-label">Nội dung hiện tại</label>
                                            <div class="alert alert-info">
                                                <i class="bi bi-file-earmark-text"></i>
                                                Đã có nội dung
                                                <?php if ($task['content_creator']): ?>
                                                    (người soạn: <?= htmlspecialchars($task['content_creator_name']) ?>)
                                                <?php else: ?>
                                                    (từ tệp đã tải lên)
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
                                        <label for="replaceContent" class="form-label">Nội dung nhiệm vụ</label>
                                        <div class="form-check form-switch mb-3">
                                            <input class="form-check-input" type="checkbox" id="replaceContent"
                                                name="keep_old_file" value="no">
                                            <label class="form-check-label" for="replaceContent">
                                                <?= $task['file_content'] ? 'Thay nội dung hiện tại' : 'Thêm nội dung mới' ?>
                                            </label>
                                        </div>

                                        <div id="contentReplaceArea" style="display: none;">
                                            <div class="btn-group w-100 mb-3" role="group">
                                                <input type="radio" class="btn-check" name="content_type"
                                                    id="contentTypeText" value="text" checked autocomplete="off">
                                                <label class="btn btn-outline-primary" for="contentTypeText">
                                                    <i class="bi bi-textarea-t"></i> Nhập văn bản
                                                </label>

                                                <input type="radio" class="btn-check" name="content_type"
                                                    id="contentTypeFile" value="file" autocomplete="off">
                                                <label class="btn btn-outline-primary" for="contentTypeFile">
                                                    <i class="bi bi-file-earmark-arrow-up"></i> Tải tệp lên
                                                </label>
                                            </div>

                                            <!-- Text Content Area -->
                                            <div id="textContentArea">
                                                <label for="contentText" class="form-label">Nhập nội dung mới</label>
                                                <textarea class="form-control" id="contentText" name="content_text" rows="8"
                                                    placeholder="Dán nội dung mới vào đây…"></textarea>
                                            </div>

                                            <!-- File Upload Area -->
                                            <div id="fileContentArea" style="display: none;">
                                                <label for="contentFile" class="form-label">Tải tệp mới (chỉ .txt)</label>
                                                <input class="form-control" type="file" id="contentFile" name="content_file"
                                                    accept=".txt">
                                            </div>
                                        </div>
                                    </div>
                                <?php endif; ?>

                                <!-- Deadline -->
                                <div class="mb-3">
                                    <label for="taskDeadline" class="form-label">Deadline (tùy chọn)</label>
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
                                        <i class="bi bi-check-circle"></i> <span data-lang-key="update">Cập nhật</span>
                                    </button>
                                    <a href="<?= URL_ROOT ?>/class/task?topic_id=<?= $topicId ?>&phase_id=<?= $phaseId ?>"
                                        class="btn btn-secondary">
                                        <i class="bi bi-x-circle"></i> <span data-lang-key="cancel">Huỷ</span>
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
                            <h5 class="card-title">Thông tin</h5>
                            <p><strong>Chủ đề:</strong> <?= htmlspecialchars($topic['topic_name']) ?></p>
                            <p><strong>Giai đoạn:</strong> Bước <?= $phase['phase_number'] ?> -
                                <?= htmlspecialchars($phase['phase_name']) ?>
                            </p>
                            <p><strong>Người tạo:</strong> <?= htmlspecialchars($task['added_by_name'] ?? 'N/A') ?></p>
                            <p><strong>Tạo lúc:</strong> <?= date('d/m/Y H:i', strtotime($task['created_at'])) ?></p>
                            <p><strong>Cập nhật:</strong> <?= date('d/m/Y H:i', strtotime($task['updated_at'])) ?></p>
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
                <strong class="me-auto" id="toastTitle">Thông báo</strong>
                <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Đóng"></button>
            </div>
            <div class="toast-body" id="toastMessage">
                Nội dung thông báo
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
                        toastTitle.textContent = 'Thành công';
                        break;
                    case 'error':
                        toastHeader.classList.add('bg-danger', 'text-white');
                        toastIcon.classList.add('bi-x-circle-fill');
                        toastTitle.textContent = 'Error';
                        break;
                    case 'warning':
                        toastHeader.classList.add('bg-warning', 'text-dark');
                        toastIcon.classList.add('bi-exclamation-triangle-fill');
                        toastTitle.textContent = 'Lưu ý';
                        break;
                    case 'info':
                        toastHeader.classList.add('bg-info', 'text-white');
                        toastIcon.classList.add('bi-info-circle-fill');
                        toastTitle.textContent = 'Thông tin';
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
                    showToast('Vui lòng nhập tên nhiệm vụ.', 'warning');
                    return;
                }

                // Validate based on phase type
                if (isEvaluationPhase) {
                    const sourceTaskId = document.querySelector('select[name="source_task_id"]')?.value;
                    if (!sourceTaskId) {
                        showToast('Hãy chọn nhiệm vụ cần đánh giá.', 'warning');
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
                                showToast('Vui lòng nhập nội dung văn bản.', 'warning');
                                return;
                            }
                        } else if (contentType === 'file') {
                            const contentFile = document.getElementById('contentFile').files[0];
                            if (!contentFile) {
                                showToast('Hãy chọn tệp để tải lên.', 'warning');
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
                        showToast('Đã cập nhật nhiệm vụ.', 'success');
                        setTimeout(() => {
                            window.location.href = '/class/task?topic_id=<?= $topicId ?>&phase_id=<?= $phaseId ?>';
                        }, 1500);
                    } else {
                        showToast(data.message || 'Không cập nhật được nhiệm vụ.', 'error');
                        submitBtn.disabled = false;
                        submitBtn.innerHTML = '<i class="bi bi-check-circle"></i> Cập nhật';
                    }
                } catch (error) {
                    console.error('Error:', error);
                    showToast('Không cập nhật được nhiệm vụ. Vui lòng thử lại.', 'error');
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = '<i class="bi bi-check-circle"></i> Cập nhật';
                }
            });
        })();
    </script>
</body>

</html>
