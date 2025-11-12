<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php require_once './views/templates/assets.php'; ?>
    <title>Tạo pha học tập</title>
</head>

<body>
    <?php
    require_once './config/db.php';
    require_once './config/helper_phase.php';
    require_once './config/helper_class.php';
    require_once './views/templates/header.php';
    require_once './views/templates/sidebar.php';

    // Get topic_id from URL
    $topicId = isset($_GET['topic_id']) ? intval($_GET['topic_id']) : 0;

    if (!$topicId) {
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

    // Get next phase order
    $nextOrder = getNextPhaseOrder($topicId);

    // Check if there's an active phase
    $hasActive = hasActivePhase($topicId);

    // Get all main phases
    $mainPhases = getAllMainPhases();
    ?>

    <main id="main" class="main">
        <div class="pagetitle">
            <h1>Tạo pha học tập mới</h1>
            <nav>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="/class"><?= $_SESSION['current_class_name']; ?></a></li>
                    <li class="breadcrumb-item"><a href="/class"><?= htmlspecialchars($topic['topic_name']) ?></a></li>
                    <li class="breadcrumb-item"><a href="/class/phases?topic_id=<?= $topicId ?>">Pha học tập</a></li>
                    <li class="breadcrumb-item active">Tạo mới</li>
                </ol>
            </nav>
        </div><!-- End Page Title -->

        <section class="section">
            <div class="row">
                <div class="col-lg-8">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title">Thông tin pha học tập</h5>

                            <form id="createPhaseForm">
                                <input type="hidden" name="topic_id" value="<?= $topicId ?>">

                                <!-- Phase Order (Read-only) -->
                                <div class="mb-3">
                                    <label class="form-label">Thứ tự pha <i>(Hệ thống tự động xác định thứ tự)</i> <span
                                            class="text-danger">*</span></label>
                                    <input type="text" class="form-control disabled" value="<?= $nextOrder ?>" readonly
                                        disabled>
                                    <div class="form-text"></div>
                                </div>

                                <!-- Phase Name -->
                                <div class="mb-3">
                                    <label class="form-label">Tên pha <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="phaseName" name="phase_name"
                                        placeholder="Nhập tên pha học tập..." required>
                                </div>

                                <!-- Main Phase Type (Modal Selection) -->
                                <div class="mb-3">
                                    <label class="form-label">Kiểu pha <span class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <input type="text" class="form-control" id="selectedPhaseType" readonly
                                            placeholder="Chọn kiểu pha..." required>
                                        <input type="hidden" id="mainPhaseId" name="main_phase_id" required>
                                        <button class="btn btn-outline-secondary" type="button" data-bs-toggle="modal"
                                            data-bs-target="#selectPhaseTypeModal">
                                            <i class="bi bi-search"></i> Chọn
                                        </button>
                                    </div>
                                </div>

                                <!-- Start Time -->
                                <div class="mb-3">
                                    <label class="form-label">Thời gian bắt đầu <span
                                            class="text-danger">*</span></label>
                                    <input type="datetime-local" class="form-control" id="startTime" name="start_time"
                                        required>
                                </div>

                                <!-- End Time -->
                                <div class="mb-3">
                                    <label class="form-label">Thời gian kết thúc <span
                                            class="text-danger">*</span></label>
                                    <input type="datetime-local" class="form-control" id="endTime" name="end_time"
                                        required>
                                </div>

                                <!-- Flag Active -->
                                <div class="mb-3">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" id="flagActive"
                                            name="flag_active" value="1" <?= $hasActive ? 'disabled' : '' ?>>
                                        <label class="form-check-label" for="flagActive">
                                            Kích hoạt pha này ngay lập tức
                                        </label>
                                        <?php if ($hasActive): ?>
                                            <div class="form-text text-warning">
                                                <i class="bi bi-exclamation-triangle"></i>
                                                Đã có pha đang hoạt động. Vui lòng tắt pha đó trước khi kích hoạt pha mới.
                                            </div>
                                        <?php else: ?>
                                            <div class="form-text">
                                                Nếu bật, pha này sẽ được kích hoạt ngay và ghi đè thời gian.
                                                Chỉ có một pha được phép hoạt động trong cùng một chủ đề.
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <!-- Buttons -->
                                <div class="d-flex gap-2">
                                    <button type="submit" class="btn btn-primary" id="submitBtn">
                                        <i class="bi bi-check-circle"></i> Tạo pha
                                    </button>
                                    <a href="<?= URL_ROOT ?>/class/phases?topic_id=<?= $topicId ?>" class="btn btn-secondary">
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
                            <h5 class="card-title">Hướng dẫn</h5>
                            <ul class="list-unstyled">
                                <li class="mb-2">
                                    <i class="bi bi-info-circle text-primary"></i>
                                    Thứ tự pha được hệ thống tự động xác định
                                </li>
                                <li class="mb-2">
                                    <i class="bi bi-info-circle text-primary"></i>
                                    Kiểu pha xác định chức năng của pha (AI, Comment, Edit...)
                                </li>
                                <li class="mb-2">
                                    <i class="bi bi-info-circle text-primary"></i>
                                    Thời gian bắt đầu phải trước thời gian kết thúc
                                </li>
                                <li class="mb-2">
                                    <i class="bi bi-info-circle text-primary"></i>
                                    Flag active có mức ưu tiên cao hơn thời gian
                                </li>
                                <li class="mb-2">
                                    <i class="bi bi-exclamation-triangle text-warning"></i>
                                    Chỉ một pha được phép active trong cùng chủ đề
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </section>

    </main><!-- End #main -->

    <!-- Select Phase Type Modal -->
    <div class="modal fade" id="selectPhaseTypeModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Chọn kiểu pha</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="list-group">
                        <?php foreach ($mainPhases as $mp): ?>
                            <a href="#" class="list-group-item list-group-item-action phase-type-option"
                                data-id="<?= $mp['m_phase_id'] ?>" data-name="<?= htmlspecialchars($mp['m_phase_name']) ?>"
                                data-code="<?= htmlspecialchars($mp['phase_code']) ?>">
                                <div class="d-flex w-100 justify-content-between">
                                    <h6 class="mb-1"><?= htmlspecialchars($mp['m_phase_name']) ?></h6>
                                    <small class="text-muted"><?= htmlspecialchars($mp['phase_code']) ?></small>
                                </div>
                                <p class="mb-2"><?= nl2br(htmlspecialchars($mp['description'])) ?></p>
                                <div class="d-flex gap-3 text-muted small">
                                    <span>
                                        <i class="bi bi-robot"></i> AI:
                                        <?= $mp['AI'] ? '<span class="text-success">Có</span>' : '<span class="text-danger">Không</span>' ?>
                                    </span>
                                    <span>
                                        <i class="bi bi-chat-dots"></i> Comment:
                                        <?= $mp['Comment'] ? '<span class="text-success">Có</span>' : '<span class="text-danger">Không</span>' ?>
                                    </span>
                                    <span>
                                        <i class="bi bi-pencil-square"></i> Edit:
                                        <?= $mp['Edit'] ? '<span class="text-success">Có</span>' : '<span class="text-danger">Không</span>' ?>
                                    </span>
                                </div>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Toast Container -->
    <div class="position-fixed top-0 end-0 p-3" style="z-index: 11; margin-top: 60px;">
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

            const selectPhaseTypeModal = new bootstrap.Modal(document.getElementById('selectPhaseTypeModal'));

            // Select phase type
            document.querySelectorAll('.phase-type-option').forEach(option => {
                option.addEventListener('click', function(e) {
                    e.preventDefault();

                    const id = this.dataset.id;
                    const name = this.dataset.name;

                    document.getElementById('mainPhaseId').value = id;
                    document.getElementById('selectedPhaseType').value = name;

                    selectPhaseTypeModal.hide();
                });
            });

            // Form validation
            document.getElementById('createPhaseForm').addEventListener('submit', async function(e) {
                e.preventDefault();

                const startTime = document.getElementById('startTime').value;
                const endTime = document.getElementById('endTime').value;

                // Validate times
                if (new Date(startTime) >= new Date(endTime)) {
                    showToast('Thời gian bắt đầu phải trước thời gian kết thúc!', 'warning');
                    return;
                }

                const submitBtn = document.getElementById('submitBtn');
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Đang tạo...';

                try {
                    const formData = new FormData(this);
                    formData.append('action', 'create_phase');

                    const response = await fetch('/api/manage_phases.php', {
                        method: 'POST',
                        body: formData
                    });

                    const data = await response.json();

                    if (data.success) {
                        showToast('Tạo pha thành công!', 'success');
                        setTimeout(() => {
                            window.location.href = '/class/phases?topic_id=<?= $topicId ?>';
                        }, 1000);
                    } else {
                        showToast(data.message || 'Không thể tạo pha', 'error');
                        submitBtn.disabled = false;
                        submitBtn.innerHTML = '<i class="bi bi-check-circle"></i> Tạo pha';
                    }
                } catch (error) {
                    console.error('Error:', error);
                    showToast('Có lỗi xảy ra khi tạo pha', 'error');
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = '<i class="bi bi-check-circle"></i> Tạo pha';
                }
            });
        })();
    </script>
</body>

</html>