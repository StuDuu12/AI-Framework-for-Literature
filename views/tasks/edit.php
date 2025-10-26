<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php require_once './views/templates/assets.php'; ?>
    <title>Chỉnh sửa nhiệm vụ</title>
</head>

<body>
    <?php
    require_once './config/db.php';
    require_once './config/helper_task.php';
    require_once './config/helper_phase.php';
    require_once './config/helper_class.php';
    require_once './views/templates/header.php';

    if (!isset($_SESSION['user']['role']) || $_SESSION['user']['role'] !== 'teacher') {
        require_once './views/templates/sidebar_student.php';
    } else {
        require_once './views/templates/sidebar_teacher.php';
    }

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

    // Read existing file content if exists
    $existingContent = '';
    if ($task['file_content']) {
        $existingContent = readTaskFileContent($task['file_content']);
    }
    ?>

    <main id="main" class="main">
        <div class="pagetitle">
            <h1>Chỉnh sửa nhiệm vụ</h1>
            <nav>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="/class"><?= $_SESSION['current_class_name']; ?></a></li>
                    <li class="breadcrumb-item"><a href="/class"><?= htmlspecialchars($topic['topic_name']) ?></a></li>
                    <li class="breadcrumb-item"><a href="<?= URL_ROOT ?>/class/phases?topic_id=<?= $topicId ?>">Pha học tập</a></li>
                    <li class="breadcrumb-item"><a
                            href="<?= URL_ROOT ?>/class/task?topic_id=<?= $topicId ?>&phase_id=<?= $phaseId ?>">Nhiệm vụ</a></li>
                    <li class="breadcrumb-item active">Chỉnh sửa</li>
                </ol>
            </nav>
        </div><!-- End Page Title -->

        <section class="section">
            <div class="row">
                <div class="col-lg-8">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title">Thông tin nhiệm vụ</h5>

                            <form id="editTaskForm" enctype="multipart/form-data">
                                <input type="hidden" name="task_id" value="<?= $taskId ?>">
                                <input type="hidden" name="phase_id" value="<?= $phaseId ?>">
                                <input type="hidden" name="topic_id" value="<?= $topicId ?>">

                                <!-- Task Title -->
                                <div class="mb-3">
                                    <label class="form-label">Tiêu đề nhiệm vụ <span
                                            class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="taskTitle" name="title"
                                        value="<?= htmlspecialchars($task['title']) ?>"
                                        placeholder="Nhập tiêu đề nhiệm vụ..." required>
                                </div>

                                <!-- Task Description -->
                                <div class="mb-3">
                                    <label class="form-label">Mô tả</label>
                                    <textarea class="form-control" id="taskDescription" name="description" rows="3"
                                        placeholder="Nhập mô tả nhiệm vụ (không bắt buộc)..."><?= htmlspecialchars($task['description'] ?? '') ?></textarea>
                                </div>

                                <!-- Existing Content Display -->
                                <?php if ($task['file_content']): ?>
                                    <div class="mb-3">
                                        <label class="form-label">Nội dung hiện tại</label>
                                        <div class="alert alert-info">
                                            <i class="bi bi-file-earmark-text"></i>
                                            Đã có nội dung
                                            <?php if ($task['content_creator']): ?>
                                                (Tự tạo bởi <?= htmlspecialchars($task['content_creator_name']) ?>)
                                            <?php else: ?>
                                                (Từ file upload)
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
                                    <label class="form-label">Nội dung nhiệm vụ</label>
                                    <div class="form-check form-switch mb-3">
                                        <input class="form-check-input" type="checkbox" id="replaceContent"
                                            name="keep_old_file" value="no">
                                        <label class="form-check-label" for="replaceContent">
                                            <?= $task['file_content'] ? 'Thay đổi nội dung hiện tại' : 'Thêm nội dung mới' ?>
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
                                                <i class="bi bi-file-earmark-arrow-up"></i> Tải file lên
                                            </label>
                                        </div>

                                        <!-- Text Content Area -->
                                        <div id="textContentArea">
                                            <label class="form-label">Nhập nội dung văn bản mới</label>
                                            <textarea class="form-control" id="contentText" name="content_text" rows="8"
                                                placeholder="Paste nội dung mới vào đây..."></textarea>
                                        </div>

                                        <!-- File Upload Area -->
                                        <div id="fileContentArea" style="display: none;">
                                            <label class="form-label">Tải file mới lên (chỉ chấp nhận .txt)</label>
                                            <input class="form-control" type="file" id="contentFile" name="content_file"
                                                accept=".txt">
                                        </div>
                                    </div>
                                </div>

                                <!-- Buttons -->
                                <div class="d-flex gap-2">
                                    <button type="submit" class="btn btn-primary" id="submitBtn">
                                        <i class="bi bi-check-circle"></i> Cập nhật
                                    </button>
                                    <a href="<?= URL_ROOT ?>/class/task?topic_id=<?= $topicId ?>&phase_id=<?= $phaseId ?>"
                                        class="btn btn-secondary">
                                        <i class="bi bi-x-circle"></i> Hủy
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
                            <p><strong>Pha:</strong> Bước <?= $phase['phase_number'] ?> -
                                <?= htmlspecialchars($phase['phase_name']) ?></p>
                            <p><strong>Tạo bởi:</strong> <?= htmlspecialchars($task['added_by_name'] ?? 'N/A') ?></p>
                            <p><strong>Ngày tạo:</strong> <?= date('d/m/Y H:i', strtotime($task['created_at'])) ?></p>
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
                <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
            <div class="toast-body" id="toastMessage">
                Nội dung thông báo
            </div>
        </div>
    </div>

    <?php
    require_once './views/templates/footer.php';
    require_once './views/templates/vendors.php';
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
                        toastTitle.textContent = 'Lỗi';
                        break;
                    case 'warning':
                        toastHeader.classList.add('bg-warning', 'text-dark');
                        toastIcon.classList.add('bi-exclamation-triangle-fill');
                        toastTitle.textContent = 'Cảnh báo';
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

            // Replace content toggle
            const replaceContentCheckbox = document.getElementById('replaceContent');
            const contentReplaceArea = document.getElementById('contentReplaceArea');
            const textContentArea = document.getElementById('textContentArea');
            const fileContentArea = document.getElementById('fileContentArea');
            const contentTypeRadios = document.getElementsByName('content_type');

            replaceContentCheckbox.addEventListener('change', function() {
                contentReplaceArea.style.display = this.checked ? 'block' : 'none';
                if (!this.checked) {
                    document.getElementById('contentText').value = '';
                    document.getElementById('contentFile').value = '';
                }
            });

            contentTypeRadios.forEach(radio => {
                radio.addEventListener('change', function() {
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

            // Form submission
            document.getElementById('editTaskForm').addEventListener('submit', async function(e) {
                e.preventDefault();

                const title = document.getElementById('taskTitle').value.trim();

                if (!title) {
                    showToast('Vui lòng nhập tiêu đề nhiệm vụ!', 'warning');
                    return;
                }

                // Validate if replacing content
                if (replaceContentCheckbox.checked) {
                    const contentType = document.querySelector('input[name="content_type"]:checked').value;

                    if (contentType === 'text') {
                        const contentText = document.getElementById('contentText').value.trim();
                        if (!contentText) {
                            showToast('Vui lòng nhập nội dung văn bản!', 'warning');
                            return;
                        }
                    } else if (contentType === 'file') {
                        const contentFile = document.getElementById('contentFile').files[0];
                        if (!contentFile) {
                            showToast('Vui lòng chọn file để tải lên!', 'warning');
                            return;
                        }
                    }
                }

                const submitBtn = document.getElementById('submitBtn');
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Đang cập nhật...';

                try {
                    const formData = new FormData(this);
                    formData.append('action', 'update_task');

                    // Set keep_old_file based on checkbox
                    if (!replaceContentCheckbox.checked) {
                        formData.set('keep_old_file', 'yes');
                    }

                    const response = await fetch('/api/manage_tasks.php', {
                        method: 'POST',
                        body: formData
                    });

                    const data = await response.json();

                    if (data.success) {
                        showToast('Cập nhật nhiệm vụ thành công!', 'success');
                        setTimeout(() => {
                            window.location.href = '/class/task?topic_id=<?= $topicId ?>&phase_id=<?= $phaseId ?>';
                        }, 1500);
                    } else {
                        showToast(data.message || 'Không thể cập nhật nhiệm vụ', 'error');
                        submitBtn.disabled = false;
                        submitBtn.innerHTML = '<i class="bi bi-check-circle"></i> Cập nhật';
                    }
                } catch (error) {
                    console.error('Error:', error);
                    showToast('Có lỗi xảy ra khi cập nhật nhiệm vụ', 'error');
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = '<i class="bi bi-check-circle"></i> Cập nhật';
                }
            });
        })();
    </script>
</body>

</html>