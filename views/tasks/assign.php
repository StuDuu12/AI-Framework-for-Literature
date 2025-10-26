<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php require_once './views/templates/assets.php'; ?>
    <title>Giao nhiệm vụ</title>
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

    // Get class_id from topic
    $classId = $topic['class_id'];

    // Get existing assignments
    $existingAssignments = getTaskAssignments($taskId);

    // Get class students, groups
    $classStudents = getClassStudents($classId);
    $classGroups = getClassGroupsForTask($classId);
    ?>

    <main id="main" class="main">
        <div class="pagetitle">
            <h1>Giao nhiệm vụ</h1>
            <nav>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="/class"><?= $_SESSION['current_class_name']; ?></a></li>
                    <li class="breadcrumb-item"><a href="/class"><?= htmlspecialchars($topic['topic_name']) ?></a></li>
                    <li class="breadcrumb-item"><a href="/class/phases?topic_id=<?= $topicId ?>">Pha học tập</a></li>
                    <li class="breadcrumb-item"><a
                            href="/class/task?topic_id=<?= $topicId ?>&phase_id=<?= $phaseId ?>">Nhiệm vụ</a></li>
                    <li class="breadcrumb-item active">Giao nhiệm vụ</li>
                </ol>
            </nav>
        </div><!-- End Page Title -->

        <section class="section">
            <div class="row">
                <!-- Assignment Form -->
                <div class="col-lg-8">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title">
                                <i class="bi bi-bell"></i> Giao nhiệm vụ: <?= htmlspecialchars($task['title']) ?>
                            </h5>

                            <form id="assignTaskForm">
                                <input type="hidden" name="task_id" value="<?= $taskId ?>">
                                <input type="hidden" name="topic_id" value="<?= $topicId ?>">
                                <input type="hidden" name="phase_id" value="<?= $phaseId ?>">

                                <!-- Assignment Type Selection -->
                                <div class="mb-4">
                                    <label class="form-label">Loại giao nhiệm vụ <span
                                            class="text-danger">*</span></label>
                                    <div class="btn-group w-100" role="group">
                                        <input type="radio" class="btn-check" name="assign_type" id="assignTypeClass"
                                            value="1" checked autocomplete="off">
                                        <label class="btn btn-outline-primary" for="assignTypeClass">
                                            <i class="bi bi-people-fill"></i> Cả lớp
                                        </label>

                                        <input type="radio" class="btn-check" name="assign_type" id="assignTypeGroup"
                                            value="2" autocomplete="off">
                                        <label class="btn btn-outline-primary" for="assignTypeGroup">
                                            <i class="bi bi-diagram-3-fill"></i> Nhóm
                                        </label>

                                        <input type="radio" class="btn-check" name="assign_type"
                                            id="assignTypeIndividual" value="3" autocomplete="off">
                                        <label class="btn btn-outline-primary" for="assignTypeIndividual">
                                            <i class="bi bi-person-fill"></i> Cá nhân
                                        </label>
                                    </div>
                                </div>

                                <!-- Class Assignment -->
                                <div id="assignClassArea">
                                    <div class="alert alert-info">
                                        <i class="bi bi-info-circle"></i>
                                        Giao nhiệm vụ cho <strong>toàn bộ lớp học</strong>:
                                        <?= htmlspecialchars($_SESSION['current_class_name']) ?>
                                    </div>
                                    <input type="hidden" name="class_value" value="<?= $classId ?>">
                                </div>

                                <!-- Group Assignment -->
                                <div id="assignGroupArea" style="display: none;">
                                    <label class="form-label">Chọn nhóm</label>
                                    <?php if (empty($classGroups)): ?>
                                        <div class="alert alert-warning">
                                            <i class="bi bi-exclamation-triangle"></i>
                                            Chưa có nhóm nào trong lớp này.
                                        </div>
                                    <?php else: ?>
                                        <div class="list-group" style="max-height: 400px; overflow-y: auto;">
                                            <?php foreach ($classGroups as $group): ?>
                                                <label class="list-group-item">
                                                    <input class="form-check-input me-2" type="checkbox" name="group_values[]"
                                                        value="<?= $group['group_id'] ?>">
                                                    <i class="bi bi-diagram-3"></i>
                                                    <?= htmlspecialchars($group['group_name']) ?>
                                                    <span class="badge bg-secondary ms-2"><?= $group['member_count'] ?> thành
                                                        viên</span>
                                                </label>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php endif; ?>
                                </div>

                                <!-- Individual Assignment -->
                                <div id="assignIndividualArea" style="display: none;">
                                    <label class="form-label">Chọn học sinh</label>
                                    <?php if (empty($classStudents)): ?>
                                        <div class="alert alert-warning">
                                            <i class="bi bi-exclamation-triangle"></i>
                                            Chưa có học sinh nào trong lớp này.
                                        </div>
                                    <?php else: ?>
                                        <div class="mb-3">
                                            <input type="text" class="form-control" id="searchStudent"
                                                placeholder="Tìm kiếm học sinh...">
                                        </div>
                                        <div class="list-group" id="studentList"
                                            style="max-height: 400px; overflow-y: auto;">
                                            <?php foreach ($classStudents as $student): ?>
                                                <label class="list-group-item student-item"
                                                    data-name="<?= strtolower(htmlspecialchars($student['fullname'])) ?>"
                                                    data-username="<?= strtolower(htmlspecialchars($student['username'])) ?>">
                                                    <input class="form-check-input me-2" type="checkbox"
                                                        name="individual_values[]" value="<?= $student['user_id'] ?>">
                                                    <i class="bi bi-person"></i>
                                                    <?= htmlspecialchars($student['fullname']) ?>
                                                    <small
                                                        class="text-muted">(<?= htmlspecialchars($student['username']) ?>)</small>
                                                </label>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php endif; ?>
                                </div>

                                <!-- Buttons -->
                                <div class="d-flex gap-2 mt-4">
                                    <button type="submit" class="btn btn-primary" id="submitBtn">
                                        <i class="bi bi-check-circle"></i> Giao nhiệm vụ
                                    </button>
                                    <a href="/class/task?topic_id=<?= $topicId ?>&phase_id=<?= $phaseId ?>"
                                        class="btn btn-secondary">
                                        <i class="bi bi-x-circle"></i> Hủy
                                    </a>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Existing Assignments -->
                <div class="col-lg-4">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title">Đã giao cho</h5>

                            <?php if (empty($existingAssignments)): ?>
                                <div class="alert alert-info">
                                    <i class="bi bi-info-circle"></i>
                                    Chưa giao cho ai.
                                </div>
                            <?php else: ?>
                                <div class="list-group">
                                    <?php foreach ($existingAssignments as $assignment): ?>
                                        <div class="list-group-item d-flex justify-content-between align-items-center">
                                            <div>
                                                <?php if ($assignment['type'] == 'class'): ?>
                                                    <i class="bi bi-people-fill text-primary"></i>
                                                    <strong>Cả lớp:</strong>
                                                <?php elseif ($assignment['type'] == 'group'): ?>
                                                    <i class="bi bi-diagram-3-fill text-success"></i>
                                                    <strong>Nhóm:</strong>
                                                <?php else: ?>
                                                    <i class="bi bi-person-fill text-info"></i>
                                                    <strong>Cá nhân:</strong>
                                                <?php endif; ?>
                                                <br>
                                                <small><?= htmlspecialchars($assignment['assigned_to_name'] ?? '') ?></small>
                                            </div>
                                            <button type="button" class="btn btn-sm btn-outline-danger remove-assignment"
                                                data-deliver-id="<?= $assignment['id_deliver'] ?>" title="Hủy giao">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Task Info -->
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title">Thông tin nhiệm vụ</h5>
                            <p><strong>Tiêu đề:</strong> <?= htmlspecialchars($task['title'] ?? '') ?></p>
                            <p><strong>Pha:</strong> Bước <?= htmlspecialchars($phase['phase_number'] ?? '') ?> -
                                <?= htmlspecialchars($phase['phase_name'] ?? '') ?></p>
                            <p><strong>Chủ đề:</strong> <?= htmlspecialchars($topic['topic_name'] ?? '') ?></p>
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

            // Toggle assignment areas
            const assignTypeRadios = document.getElementsByName('assign_type');
            const assignClassArea = document.getElementById('assignClassArea');
            const assignGroupArea = document.getElementById('assignGroupArea');
            const assignIndividualArea = document.getElementById('assignIndividualArea');

            assignTypeRadios.forEach(radio => {
                radio.addEventListener('change', function() {
                    assignClassArea.style.display = 'none';
                    assignGroupArea.style.display = 'none';
                    assignIndividualArea.style.display = 'none';

                    if (this.value === '1') {
                        assignClassArea.style.display = 'block';
                    } else if (this.value === '2') {
                        assignGroupArea.style.display = 'block';
                    } else if (this.value === '3') {
                        assignIndividualArea.style.display = 'block';
                    }
                });
            });

            // Search students
            const searchStudent = document.getElementById('searchStudent');
            if (searchStudent) {
                searchStudent.addEventListener('input', function() {
                    const searchTerm = this.value.toLowerCase();
                    const studentItems = document.querySelectorAll('.student-item');

                    studentItems.forEach(item => {
                        const name = item.dataset.name;
                        const username = item.dataset.username;

                        if (name.includes(searchTerm) || username.includes(searchTerm)) {
                            item.style.display = '';
                        } else {
                            item.style.display = 'none';
                        }
                    });
                });
            }

            // Form submission
            document.getElementById('assignTaskForm').addEventListener('submit', async function(e) {
                e.preventDefault();

                const assignType = document.querySelector('input[name="assign_type"]:checked').value;
                let assignValues = [];

                if (assignType === '1') {
                    assignValues = [document.querySelector('input[name="class_value"]').value];
                } else if (assignType === '2') {
                    const checkedGroups = document.querySelectorAll('input[name="group_values[]"]:checked');
                    if (checkedGroups.length === 0) {
                        showToast('Vui lòng chọn ít nhất một nhóm!', 'warning');
                        return;
                    }
                    assignValues = Array.from(checkedGroups).map(cb => cb.value);
                } else if (assignType === '3') {
                    const checkedStudents = document.querySelectorAll('input[name="individual_values[]"]:checked');
                    if (checkedStudents.length === 0) {
                        showToast('Vui lòng chọn ít nhất một học sinh!', 'warning');
                        return;
                    }
                    assignValues = Array.from(checkedStudents).map(cb => cb.value);
                }

                const submitBtn = document.getElementById('submitBtn');
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Đang giao...';

                try {
                    const formData = new FormData();
                    formData.append('action', 'assign_task');
                    formData.append('task_id', <?= $taskId ?>);
                    formData.append('topic_id', <?= $topicId ?>);
                    formData.append('assign_type', assignType);
                    assignValues.forEach(value => {
                        formData.append('assign_values[]', value);
                    });

                    const response = await fetch('/api/manage_tasks.php', {
                        method: 'POST',
                        body: formData
                    });

                    const data = await response.json();

                    if (data.success) {
                        showToast('Giao nhiệm vụ thành công!', 'success');
                        setTimeout(() => {
                            window.location.reload();
                        }, 1500);
                    } else {
                        showToast(data.message || 'Không thể giao nhiệm vụ', 'error');
                        submitBtn.disabled = false;
                        submitBtn.innerHTML = '<i class="bi bi-check-circle"></i> Giao nhiệm vụ';
                    }
                } catch (error) {
                    console.error('Error:', error);
                    showToast('Có lỗi xảy ra khi giao nhiệm vụ', 'error');
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = '<i class="bi bi-check-circle"></i> Giao nhiệm vụ';
                }
            });

            // Remove assignment
            document.querySelectorAll('.remove-assignment').forEach(btn => {
                btn.addEventListener('click', async function() {
                    const deliverId = this.dataset.deliverId;

                    if (!confirm('Bạn có chắc chắn muốn hủy giao nhiệm vụ này?')) {
                        return;
                    }

                    try {
                        const formData = new FormData();
                        formData.append('action', 'remove_assignment');
                        formData.append('deliver_id', deliverId);
                        formData.append('topic_id', <?= $topicId ?>);

                        const response = await fetch('/api/manage_tasks.php', {
                            method: 'POST',
                            body: formData
                        });

                        const data = await response.json();

                        if (data.success) {
                            showToast('Hủy giao nhiệm vụ thành công!', 'success');
                            setTimeout(() => {
                                window.location.reload();
                            }, 1500);
                        } else {
                            showToast(data.message || 'Không thể hủy giao nhiệm vụ', 'error');
                        }
                    } catch (error) {
                        console.error('Error:', error);
                        showToast('Có lỗi xảy ra khi hủy giao nhiệm vụ', 'error');
                    }
                });
            });
        })();
    </script>
</body>

</html>