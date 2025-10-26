<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php require_once './views/templates/assets.php'; ?>
    <title>Tạo nhiệm vụ mới</title>
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

    // Get phase_id and topic_id from URL
    $phaseId = isset($_GET['phase_id']) ? intval($_GET['phase_id']) : 0;
    $topicId = isset($_GET['topic_id']) ? intval($_GET['topic_id']) : 0;

    if (!$phaseId || !$topicId) {
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
    ?>

    <main id="main" class="main">
        <div class="pagetitle">
            <h1>Tạo nhiệm vụ mới</h1>
            <nav>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="/class"><?= $_SESSION['current_class_name']; ?></a></li>
                    <li class="breadcrumb-item"><a href="/class"><?= htmlspecialchars($topic['topic_name']) ?></a></li>
                    <li class="breadcrumb-item"><a href="<?= URL_ROOT ?>/class/phases?topic_id=<?= $topicId ?>">Pha học tập</a></li>
                    <li class="breadcrumb-item"><a
                            href="<?= URL_ROOT ?>/class/task?topic_id=<?= $topicId ?>&phase_id=<?= $phaseId ?>">Nhiệm vụ</a></li>
                    <li class="breadcrumb-item active">Tạo mới</li>
                </ol>
            </nav>
        </div><!-- End Page Title -->

        <section class="section">
            <div class="row">
                <div class="col-lg-8">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title">Thông tin nhiệm vụ</h5>

                            <form id="createTaskForm" enctype="multipart/form-data">
                                <input type="hidden" name="phase_id" value="<?= $phaseId ?>">
                                <input type="hidden" name="topic_id" value="<?= $topicId ?>">

                                <!-- Task Title -->
                                <div class="mb-3">
                                    <label class="form-label">Tiêu đề nhiệm vụ <span
                                            class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="taskTitle" name="title"
                                        placeholder="Nhập tiêu đề nhiệm vụ..." required>
                                </div>

                                <!-- Task Description -->
                                <div class="mb-3">
                                    <label class="form-label">Mô tả</label>
                                    <textarea class="form-control" id="taskDescription" name="description" rows="3"
                                        placeholder="Nhập mô tả nhiệm vụ (không bắt buộc)..."></textarea>
                                </div>

                                <!-- Content Type Selection -->
                                <div class="mb-3">
                                    <label class="form-label">Nội dung nhiệm vụ</label>
                                    <div class="btn-group w-100 mb-3" role="group">
                                        <input type="radio" class="btn-check" name="content_type" id="contentTypeText"
                                            value="text" checked autocomplete="off">
                                        <label class="btn btn-outline-primary" for="contentTypeText">
                                            <i class="bi bi-textarea-t"></i> Nhập văn bản
                                        </label>

                                        <input type="radio" class="btn-check" name="content_type" id="contentTypeFile"
                                            value="file" autocomplete="off">
                                        <label class="btn btn-outline-primary" for="contentTypeFile">
                                            <i class="bi bi-file-earmark-arrow-up"></i> Tải file lên
                                        </label>

                                        <input type="radio" class="btn-check" name="content_type" id="contentTypeNone"
                                            value="none" autocomplete="off">
                                        <label class="btn btn-outline-secondary" for="contentTypeNone">
                                            <i class="bi bi-x-circle"></i> Không có nội dung
                                        </label>
                                    </div>

                                    <!-- Text Content Area -->
                                    <div id="textContentArea">
                                        <label class="form-label">Nhập nội dung văn bản</label>
                                        <textarea class="form-control" id="contentText" name="content_text" rows="8"
                                            placeholder="Paste nội dung vào đây..."></textarea>
                                        <div class="form-text">
                                            <i class="bi bi-info-circle"></i>
                                            Bạn có thể paste nội dung trực tiếp vào đây
                                        </div>
                                    </div>

                                    <!-- File Upload Area -->
                                    <div id="fileContentArea" style="display: none;">
                                        <label class="form-label">Tải file lên (chỉ chấp nhận .txt)</label>
                                        <input class="form-control" type="file" id="contentFile" name="content_file"
                                            accept=".txt">
                                        <div class="form-text">
                                            <i class="bi bi-info-circle"></i>
                                            Chỉ chấp nhận file .txt
                                        </div>
                                    </div>
                                </div>

                                <!-- Buttons -->
                                <div class="d-flex gap-2">
                                    <button type="submit" class="btn btn-primary" id="submitBtn">
                                        <i class="bi bi-check-circle"></i> Tạo nhiệm vụ
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
                            <p><strong>Loại pha:</strong> <?= htmlspecialchars($phase['m_phase_name']) ?></p>
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

                // Remove existing background classes
                toastHeader.classList.remove('bg-success', 'bg-danger', 'bg-warning', 'bg-info', 'text-white');
                toastIcon.className = 'bi me-2';

                // Set style based on type
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

            // Content type toggle
            const textContentArea = document.getElementById('textContentArea');
            const fileContentArea = document.getElementById('fileContentArea');
            const contentTypeRadios = document.getElementsByName('content_type');

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
                    } else {
                        textContentArea.style.display = 'none';
                        fileContentArea.style.display = 'none';
                        document.getElementById('contentText').value = '';
                        document.getElementById('contentFile').value = '';
                    }
                });
            });

            // Form submission
            document.getElementById('createTaskForm').addEventListener('submit', async function(e) {
                e.preventDefault();

                const title = document.getElementById('taskTitle').value.trim();
                const contentType = document.querySelector('input[name="content_type"]:checked').value;

                // Validate
                if (!title) {
                    showToast('Vui lòng nhập tiêu đề nhiệm vụ!', 'warning');
                    return;
                }

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

                const submitBtn = document.getElementById('submitBtn');
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Đang tạo...';

                try {
                    const formData = new FormData(this);
                    formData.append('action', 'create_task');

                    const response = await fetch('/api/manage_tasks.php', {
                        method: 'POST',
                        body: formData
                    });

                    const data = await response.json();

                    if (data.success) {
                        showToast('Tạo nhiệm vụ thành công!', 'success');
                        setTimeout(() => {
                            window.location.href = '/class/task?topic_id=<?= $topicId ?>&phase_id=<?= $phaseId ?>';
                        }, 1500);
                    } else {
                        showToast(data.message || 'Không thể tạo nhiệm vụ', 'error');
                        submitBtn.disabled = false;
                        submitBtn.innerHTML = '<i class="bi bi-check-circle"></i> Tạo nhiệm vụ';
                    }
                } catch (error) {
                    console.error('Error:', error);
                    showToast('Có lỗi xảy ra khi tạo nhiệm vụ', 'error');
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = '<i class="bi bi-check-circle"></i> Tạo nhiệm vụ';
                }
            });
        })();
    </script>
</body>

</html>