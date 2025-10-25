<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php require_once './views/templates/assets.php'; ?>
    <title>Pha học tập</title>
</head>

<body>
    <?php
    require_once './config/db.php';
    require_once './config/helper_phase.php';
    require_once './config/helper_class.php';
    require_once './views/templates/header.php';

    if (isset($_SESSION['user']['role']) && $_SESSION['user']['role'] === 'teacher') {
        require_once './views/templates/sidebar_teacher.php';
    } else if (isset($_SESSION['user']['role']) && $_SESSION['user']['role'] === 'student') {
        require_once './views/templates/sidebar_student.php';
    }

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

    // Get phases
    $phases = getTopicPhases($topicId);

    // Check if user is teacher
    $isTeacher = isTeacherInTopicClass($topicId, $_SESSION['user']['user_id']);
    ?>

    <main id="main" class="main">
        <div class="pagetitle">
            <h1>Danh sách các pha (bước) học tập</h1>
            <nav>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="/class"><?= $_SESSION['current_class_name']; ?></a></li>
                    <li class="breadcrumb-item"><a href="/class"><?= htmlspecialchars($topic['topic_name']) ?></a></li>
                    <li class="breadcrumb-item active">Pha học tập</li>
                </ol>
            </nav>
        </div><!-- End Page Title -->

        <section class="section dashboard">
            <div class="row">
                <div class="col-lg-12">
                    <div class="card">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h5 class="card-title"><?= htmlspecialchars($topic['topic_name']) ?></h5>
                                <?php if ($isTeacher): ?>
                                    <a href="<?= URL_ROOT ?>/class/phases/create?topic_id=<?= $topicId ?>"
                                        class="btn btn-primary">
                                        <i class="bi bi-plus-circle"></i> Tạo pha mới
                                    </a>
                                <?php endif; ?>
                            </div>

                            <?php if (empty($phases)): ?>
                                <div class="alert alert-info text-center">
                                    <i class="bi bi-info-circle"></i> Chưa có pha học tập nào
                                </div>
                            <?php else: ?>
                                <dl class="row mb-0">
                                    <?php foreach ($phases as $index => $phase): ?>
                                        <dt class="col-12 mb-0">
                                            <div class="list-group-item py-3">
                                                <!-- Header: Số bước + Tên + Actions -->
                                                <div class="d-flex justify-content-between align-items-center mb-3">
                                                    <div class="d-flex align-items-center gap-2">
                                                        <span class="badge bg-secondary"
                                                            style="font-size: 0.9rem; padding: 0.4rem 0.6rem;">
                                                            Bước <?= $phase['phase_number'] ?>
                                                        </span>
                                                        <h5 class="mb-0"><?= htmlspecialchars($phase['phase_name']) ?></h5>
                                                        <?php if ($phase['flag_active']): ?>
                                                            <span class="badge bg-success">
                                                                <i class="bi bi-lightning-fill"></i> Đang hoạt động
                                                            </span>
                                                        <?php endif; ?>
                                                    </div>
                                                    <div class="d-flex gap-2 align-items-center">
                                                        <a href="/class/task?topic_id=<?= intval($topic['topic_id']) ?>&phase_id=<?= $phase['phase_id'] ?>" class="btn btn-outline-primary btn-sm topic-link"
                                                            data-topic-id="<?= intval($topic['topic_id']) ?>" title="Xem các danh sách các nhiệm vụ">
                                                            <i class="bi bi-box-arrow-in-right"></i> Nhiệm vụ
                                                        </a>
                                                        <?php if ($isTeacher): ?>
                                                            <a href="<?= URL_ROOT ?>/class/phases/edit?phase_id=<?= $phase['phase_id'] ?>&topic_id=<?= $topicId ?>"
                                                                class="btn btn-outline-secondary btn-sm" title="Chỉnh sửa">
                                                                <i class="bi bi-pencil"></i>
                                                            </a>
                                                            <button class="btn btn-outline-danger btn-sm delete-phase-btn"
                                                                data-phase-id="<?= $phase['phase_id'] ?>"
                                                                data-phase-name="<?= htmlspecialchars($phase['phase_name']) ?>"
                                                                title="Xóa">
                                                                <i class="bi bi-trash"></i>
                                                            </button>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>

                                                <!-- Content: Kiểu pha + Thời gian + Tính năng -->
                                                <div class="d-flex flex-wrap align-items-center gap-4">
                                                    <!-- Kiểu pha với tooltip -->
                                                    <div class="d-flex align-items-center gap-2">
                                                        <i class="bi bi-layers text-primary" style="font-size: 1.1rem;"></i>
                                                        <strong
                                                            class="text-primary"><?= htmlspecialchars($phase['m_phase_name']) ?></strong>
                                                        <i class="bi bi-info-circle text-muted"
                                                            style="cursor: help; font-size: 1rem;" data-bs-toggle="tooltip"
                                                            data-bs-placement="top" data-bs-html="true"
                                                            title="<?= htmlspecialchars($phase['phase_description']) ?>"></i>
                                                    </div>

                                                    <!-- Thời gian -->
                                                    <div class="d-flex align-items-center gap-2">
                                                        <i class="bi bi-calendar-event text-secondary"></i>
                                                        <span
                                                            class="badge bg-success-subtle text-success border border-success">
                                                            <?= date('d/m/Y H:i', strtotime($phase['start_time'])) ?>
                                                        </span>
                                                        <i class="bi bi-arrow-right text-muted" style="font-size: 0.8rem;"></i>
                                                        <span class="badge bg-danger-subtle text-danger border border-danger">
                                                            <?= date('d/m/Y H:i', strtotime($phase['end_time'])) ?>
                                                        </span>
                                                    </div>

                                                    <!-- Icons tính năng -->
                                                    <div class="d-flex gap-2 align-items-center">
                                                        <?php if ($phase['AI']): ?>
                                                            <span class="badge bg-primary" title="AI được bật">
                                                                <i class="bi bi-robot"></i>
                                                            </span>
                                                        <?php endif; ?>
                                                        <?php if ($phase['Comment']): ?>
                                                            <span class="badge bg-info" title="Comment được bật">
                                                                <i class="bi bi-chat-dots"></i>
                                                            </span>
                                                        <?php endif; ?>
                                                        <?php if ($phase['Edit']): ?>
                                                            <span class="badge bg-warning text-dark" title="Edit được bật">
                                                                <i class="bi bi-pencil-square"></i>
                                                            </span>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            </div>
                                        </dt>
                                        <?php if ($index < count($phases) - 1): ?>
                                            <div class="col-12">
                                                <hr class="my-2">
                                            </div>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                </dl>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </section>

    </main><!-- End #main -->

    <!-- Toast Container -->
    <div class="position-fixed top-0 end-0 p-3" style="z-index: 11">
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
    require_once 'views/templates/footer.php';
    require_once 'views/templates/vendors.php';
    ?>

    <script>
        // Initialize Bootstrap tooltips for all users
        document.addEventListener('DOMContentLoaded', function () {
            const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
            tooltipTriggerList.map(function (tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl);
            });
        });
    </script>

    <?php if ($isTeacher): ?>
        <script>
            // Toast utility function
            function showToast(message, type = 'success') {
                const toastEl = document.getElementById('toastNotification');
                const toastIcon = document.getElementById('toastIcon');
                const toastTitle = document.getElementById('toastTitle');
                const toastMessage = document.getElementById('toastMessage');
                const toastHeader = toastEl.querySelector('.toast-header');

                // Remove existing background classes
                toastHeader.classList.remove('bg-success', 'bg-danger', 'bg-warning', 'bg-info', 'text-white', 'text-dark');
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

            // Delete Phase
            document.querySelectorAll('.delete-phase-btn').forEach(btn => {
                btn.addEventListener('click', async function () {
                    const phaseId = this.dataset.phaseId;
                    const phaseName = this.dataset.phaseName;

                    if (!confirm(`Bạn có chắc muốn xóa pha "${phaseName}"?\n\nLưu ý: Tất cả dữ liệu liên quan đến pha này sẽ bị xóa.`)) {
                        return;
                    }

                    try {
                        const formData = new FormData();
                        formData.append('action', 'delete_phase');
                        formData.append('phase_id', phaseId);
                        formData.append('topic_id', '<?= $topicId ?>');

                        const response = await fetch('api/manage_phases.php', {
                            method: 'POST',
                            body: formData
                        });

                        const data = await response.json();

                        if (data.success) {
                            showToast('Xóa pha thành công!', 'success');
                            setTimeout(() => {
                                location.reload();
                            }, 1000);
                        } else {
                            showToast(data.message || 'Không thể xóa pha', 'error');
                        }
                    } catch (error) {
                        console.error('Error:', error);
                        showToast('Có lỗi xảy ra khi xóa pha', 'error');
                    }
                });
            });
        </script>
    <?php endif; ?>
</body>

</html>