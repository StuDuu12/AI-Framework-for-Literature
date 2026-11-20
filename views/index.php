<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php require_once './views/templates/assets.php'; ?>
    <title data-lang-key="class_list_title">Class List - Literature System</title>
</head>

<body>
    <?php
    require_once './views/templates/header.php';
    require_once './views/templates/sidebar.php';
    

    ?>

    <main id="main" class="main">
        <div class="pagetitle">
            <div class="d-flex justify-content-between align-items-center">
                <h1 data-lang-key="class_list_title">Class List</h1>
                <div class="d-flex gap-2">
                    <?php if (isset($_SESSION['user']['role']) && $_SESSION['user']['role'] === 'teacher'): ?>
                        <button type="button"
                            id="deleteSelectedClassesBtn"
                            class="btn btn-sm btn-danger"
                            style="display: none;">
                            <i class="bi bi-trash"></i> <span data-lang-key="delete_selected">Delete Selected</span> (<span id="selectedClassCount">0</span>)
                        </button>
                        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createClassModal">
                            <i class="bi bi-plus-circle"></i> <span data-lang-key="create_new_class">Create New Class</span>
                        </button>
                    <?php endif; ?>
                </div>
            </div>
        </div><!-- End Page Title -->

        <section class="section dashboard">
            <?php
            $userid = $_SESSION['user']['user_id'];
            $userRole = $_SESSION['user']['role'] ?? 'student';
            global $pdo;

            $statClasses = 0;
            $statTasks = 0;
            $statDone = 0;
            $statPendingEval = 0;

            $stmt = $pdo->prepare("SELECT COUNT(*) FROM class_member WHERE user_id = :uid");
            $stmt->execute([':uid' => $userid]);
            $statClasses = (int)$stmt->fetchColumn();

            if ($userRole === 'student') {
                require_once __DIR__ . '/../config/helper_task.php';
                $statTasks = countStudentAssignedDelivers($pdo, (int)$userid, false);
                $statDone = countStudentAssignedDelivers($pdo, (int)$userid, true);

                $stmt = $pdo->prepare("
                    SELECT COUNT(*) FROM evaluate
                    WHERE user_id = :uid AND status < 2
                ");
                $stmt->execute([':uid' => $userid]);
                $statPendingEval = (int)$stmt->fetchColumn();
            } else {
                $stmt = $pdo->prepare("
                    SELECT COUNT(DISTINCT tk.task_id) FROM task tk
                    JOIN phase ph ON ph.phase_id = tk.phase_id
                    JOIN topic t ON t.topic_id = ph.topic_id
                    JOIN class_member cm ON cm.class_id = t.class_id AND cm.user_id = :uid
                ");
                $stmt->execute([':uid' => $userid]);
                $statTasks = (int)$stmt->fetchColumn();
            }
            ?>
            <div class="row dashboard-stats mb-4" data-stagger>
                <div class="col-lg-3 col-md-6 mb-3">
                    <div class="stat-card">
                        <div class="stat-icon stat-icon--primary"><i class="bi bi-collection"></i></div>
                        <div class="stat-value"><?= $statClasses ?></div>
                        <div class="stat-label" data-lang-key="stat_classes">Lớp học</div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 mb-3">
                    <div class="stat-card">
                        <div class="stat-icon stat-icon--info"><i class="bi bi-list-task"></i></div>
                        <div class="stat-value"><?= $statTasks ?></div>
                        <div class="stat-label" data-lang-key="<?= $userRole === 'student' ? 'stat_tasks_assigned' : 'stat_tasks_in_class' ?>"><?= $userRole === 'student' ? 'Nhiệm vụ được giao' : 'Nhiệm vụ trong lớp' ?></div>
                    </div>
                </div>
                <?php if ($userRole === 'student'): ?>
                <div class="col-lg-3 col-md-6 mb-3">
                    <div class="stat-card">
                        <div class="stat-icon stat-icon--success"><i class="bi bi-check-circle"></i></div>
                        <div class="stat-value"><?= $statDone ?></div>
                        <div class="stat-label" data-lang-key="stat_completed">Đã nộp bài</div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 mb-3">
                    <div class="stat-card">
                        <div class="stat-icon stat-icon--warning"><i class="bi bi-star"></i></div>
                        <div class="stat-value"><?= $statPendingEval ?></div>
                        <div class="stat-label" data-lang-key="stat_evaluations">Chờ đánh giá</div>
                    </div>
                </div>
                <?php endif; ?>
            </div>
            <?php
            // ===== STUDENT PROGRESS OVERVIEW =====
            if ($userRole === 'student'):
                $progressStmt = $pdo->prepare("
                    SELECT
                        c.class_id,
                        c.class_name,
                        COUNT(DISTINCT d.id_deliver) AS total_tasks,
                        SUM(CASE WHEN d.status = 2 THEN 1 ELSE 0 END) AS done_tasks,
                        (SELECT COUNT(*) FROM evaluate e2 WHERE e2.user_id = :uid2 AND e2.status < 2
                            AND EXISTS (SELECT 1 FROM deliver d2 INNER JOIN task tk2 ON d2.task_id = tk2.task_id
                                        INNER JOIN phase ph2 ON tk2.phase_id = ph2.phase_id
                                        INNER JOIN topic tp2 ON ph2.topic_id = tp2.topic_id
                                        WHERE d2.id_deliver = e2.deliver_id AND tp2.class_id = c.class_id)
                        ) AS pending_eval
                    FROM class_member cm
                    INNER JOIN class c ON cm.class_id = c.class_id
                    LEFT JOIN topic tp ON tp.class_id = c.class_id
                    LEFT JOIN phase ph ON ph.topic_id = tp.topic_id
                    LEFT JOIN task tk ON tk.phase_id = ph.phase_id
                    LEFT JOIN deliver d ON d.task_id = tk.task_id
                        AND (d.type = 'user' AND d.value = :uid
                            OR d.type = 'group' AND EXISTS (
                                SELECT 1 FROM group_member gm WHERE gm.group_id = d.value AND gm.user_id = :uid3
                            ))
                    WHERE cm.user_id = :uid4
                    GROUP BY c.class_id, c.class_name
                    ORDER BY c.class_name
                ");
                $progressStmt->execute([':uid' => $userid, ':uid2' => $userid, ':uid3' => $userid, ':uid4' => $userid]);
                $progressRows = $progressStmt->fetchAll(PDO::FETCH_ASSOC);
                if (!empty($progressRows)):
            ?>
            <div class="progress-overview mb-4">
                <div class="progress-overview__header d-flex justify-content-between align-items-center mb-2">
                    <h6 class="mb-0 fw-600" style="color:var(--app-heading,var(--lit-heading))">
                        <i class="bi bi-bar-chart-line me-1"></i>
                        <span data-lang-key="progress_overview">Tiến độ học tập</span>
                    </h6>
                </div>
                <div class="card">
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover mb-0 progress-table">
                                <thead>
                                    <tr>
                                        <th data-lang-key="class_name_label">Lớp</th>
                                        <th class="text-center" data-lang-key="stat_tasks_assigned">Nhiệm vụ</th>
                                        <th style="min-width:160px" data-lang-key="stat_completed">Tiến độ</th>
                                        <th class="text-center" data-lang-key="stat_evaluations">Chờ chấm</th>
                                    </tr>
                                </thead>
                                <tbody>
                                <?php foreach ($progressRows as $pr):
                                    $total = (int)$pr['total_tasks'];
                                    $done  = (int)$pr['done_tasks'];
                                    $pct   = $total > 0 ? round($done / $total * 100) : 0;
                                    $pendEval = (int)$pr['pending_eval'];
                                ?>
                                    <tr>
                                        <td>
                                            <a href="/joinclass?id=<?= urlencode($pr['class_id']) ?>"
                                               class="fw-600 text-decoration-none" style="color:var(--app-primary,var(--lit-primary))">
                                                <?= htmlspecialchars($pr['class_name']) ?>
                                            </a>
                                        </td>
                                        <td class="text-center">
                                            <span class="fw-600"><?= $done ?></span>
                                            <span class="text-muted">/<?= $total ?></span>
                                        </td>
                                        <td>
                                            <div class="d-flex align-items-center gap-2">
                                                <div class="progress flex-grow-1" style="height:6px;border-radius:3px">
                                                    <div class="progress-bar" role="progressbar"
                                                         style="width:<?= $pct ?>%;background:<?= $pct >= 100 ? '#198754' : ($pct >= 50 ? '#4154f1' : '#ffc107') ?>"
                                                         aria-valuenow="<?= $pct ?>" aria-valuemin="0" aria-valuemax="100"></div>
                                                </div>
                                                <small class="text-muted" style="width:36px;text-align:right"><?= $pct ?>%</small>
                                            </div>
                                        </td>
                                        <td class="text-center">
                                            <?php if ($pendEval > 0): ?>
                                                <span class="badge bg-warning text-dark"><?= $pendEval ?></span>
                                            <?php else: ?>
                                                <span class="text-muted">—</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; endif; ?>

            <?php
            $stmt = $pdo->prepare("SELECT cm.class_id, c.class_name, cm.role FROM `class_member` cm LEFT JOIN `class` c ON c.class_id = cm.class_id WHERE `user_id` = :userid");
            $stmt->bindParam(':userid', $userid, PDO::PARAM_INT);
            $stmt->execute();
            $classes = $stmt->fetchAll(PDO::FETCH_ASSOC);
            if ($classes):
            $classIdx = 0;
            ?>
                <div class="row" data-stagger>
                    <?php foreach ($classes as $class): ?>
                        <div class="col-lg-4 col-md-6 mb-4">
                            <div class="card h-100 class-card" data-class-id="<?php echo intval($class['class_id']); ?>" data-index="<?php echo $classIdx % 10; ?>">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-start mb-2">
                                        <div class="d-flex align-items-start gap-2 flex-grow-1">
                                            <?php if ($class['role'] === 'teacher'): ?>
                                                <input aria-label="Chọn" class="form-check-input class-checkbox mt-1"
                                                    type="checkbox"
                                                    data-class-id="<?php echo intval($class['class_id']); ?>"
                                                    data-class-name="<?php echo htmlspecialchars($class['class_name']); ?>">
                                            <?php endif; ?>
                                            <h5 class="card-title mb-0"><?php echo htmlspecialchars($class['class_name']); ?></h5>
                                        </div>
                                        <?php if ($class['role'] === 'teacher'): ?>
                                            <button class="btn btn-sm btn-outline-secondary edit-class-btn"
                                                data-class-id="<?php echo intval($class['class_id']); ?>"
                                                data-class-name="<?php echo htmlspecialchars($class['class_name']); ?>"
                                                title="Sửa tên lớp">
                                                <i class="bi bi-pencil"></i>
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                    <p class="card-text"><span data-lang-key="role_label">Vai trò:</span> <?php echo htmlspecialchars(ucfirst($class['role'])); ?></p>
                                    <a href="/joinclass?id=<?php echo urlencode($class['class_id']); ?>" class="btn btn-primary" data-lang-key="enter_class">Enter Class</a>
                                </div>
                            </div>
                        </div>
                    <?php $classIdx++; endforeach; ?>
                </div>
            <?php else: ?>
                <div class="alert alert-info" role="alert">
                    <span data-lang-key="no_class_joined">Bạn chưa tham gia lớp nào.</span>
                </div>
            <?php endif; ?>
        </section>

    </main><!-- End #main -->

    <!-- Create Class Modal -->
    <div class="modal fade" id="createClassModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" data-lang-key="create_new_class">Create New Class</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="newClassName" class="form-label" data-lang-key="class_name_label">Class Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="newClassName" placeholder="Nhập tên lớp…" data-lang-key="enter_class_name">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" data-lang-key="cancel">Huỷ</button>
                    <button type="button" class="btn btn-primary" id="createClassBtn" data-lang-key="create_class">Create Class</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit Class Modal -->
    <div class="modal fade" id="editClassModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" data-lang-key="edit_class_name">Edit Class Name</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="editClassId">
                    <div class="mb-3">
                        <label for="editClassName" class="form-label" data-lang-key="class_name_label">Class Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="editClassName" placeholder="Nhập tên lớp…" data-lang-key="enter_class_name">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" data-lang-key="cancel">Huỷ</button>
                    <button type="button" class="btn btn-primary" id="updateClassBtn" data-lang-key="update">Cập nhật</button>
                </div>
            </div>
        </div>
    </div>

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
    require_once 'views/templates/footer.php';
    require_once 'views/templates/vendors.php';
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

            // Create class
            const createClassBtn = document.getElementById('createClassBtn');
            if (createClassBtn) {
                createClassBtn.addEventListener('click', function() {
                    const className = document.getElementById('newClassName').value.trim();

                    if (!className) {
                        showToast('Vui lòng nhập tên lớp.', 'warning');
                        return;
                    }

                    this.disabled = true;
                    this.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Creating...';

                    fetch('/api/manage_class.php', {
                            method: 'POST',
                            body: new URLSearchParams({
                                action: 'create_class',
                                class_name: className
                            })
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                showToast('Đã tạo lớp.', 'success');
                                setTimeout(() => {
                                    location.reload();
                                }, 1500);
                            } else {
                                showToast(data.message || 'Could not create class', 'error');
                                this.disabled = false;
                                this.textContent = 'Tạo lớp';
                            }
                        })
                        .catch(error => {
                            console.error('Error:', error);
                            showToast('Không tạo được lớp. Vui lòng thử lại.', 'error');
                            this.disabled = false;
                            this.textContent = 'Tạo lớp';
                        });
                });
            }

            // Edit class button
            document.querySelectorAll('.edit-class-btn').forEach(btn => {
                btn.addEventListener('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();

                    const classId = parseInt(this.dataset.classId);
                    const className = this.dataset.className;

                    document.getElementById('editClassId').value = classId;
                    document.getElementById('editClassName').value = className;

                    const modal = new bootstrap.Modal(document.getElementById('editClassModal'));
                    modal.show();
                });
            });

            // Update class
            const updateClassBtn = document.getElementById('updateClassBtn');
            if (updateClassBtn) {
                updateClassBtn.addEventListener('click', function() {
                    const classId = parseInt(document.getElementById('editClassId').value);
                    const className = document.getElementById('editClassName').value.trim();

                    if (!className) {
                        showToast('Vui lòng nhập tên lớp.', 'warning');
                        return;
                    }

                    this.disabled = true;
                    this.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Updating...';

                    fetch('/api/manage_class.php', {
                            method: 'POST',
                            body: new URLSearchParams({
                                action: 'update_class_name',
                                class_id: classId,
                                class_name: className
                            })
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                showToast('Đã đổi tên lớp.', 'success');
                                setTimeout(() => {
                                    location.reload();
                                }, 1500);
                            } else {
                                showToast(data.message || 'Could not update class name', 'error');
                                this.disabled = false;
                                this.textContent = 'Update';
                            }
                        })
                        .catch(error => {
                            console.error('Error:', error);
                            showToast('Không đổi được tên lớp. Vui lòng thử lại.', 'error');
                            this.disabled = false;
                            this.textContent = 'Update';
                        });
                });
            }

            // Update delete button visibility
            function updateDeleteButton() {
                const checkedBoxes = document.querySelectorAll('.class-checkbox:checked');
                const deleteBtn = document.getElementById('deleteSelectedClassesBtn');
                const countSpan = document.getElementById('selectedClassCount');

                if (deleteBtn && countSpan) {
                    if (checkedBoxes.length > 0) {
                        deleteBtn.style.display = 'inline-block';
                        countSpan.textContent = checkedBoxes.length;
                    } else {
                        deleteBtn.style.display = 'none';
                        countSpan.textContent = '0';
                    }
                }
            }

            // Handle class checkbox changes
            document.querySelectorAll('.class-checkbox').forEach(checkbox => {
                checkbox.addEventListener('change', function() {
                    updateDeleteButton();
                });
            });

            // Delete selected classes
            const deleteSelectedClassesBtn = document.getElementById('deleteSelectedClassesBtn');
            if (deleteSelectedClassesBtn) {
                deleteSelectedClassesBtn.addEventListener('click', async function() {
                    const checkedBoxes = document.querySelectorAll('.class-checkbox:checked');
                    const classIds = Array.from(checkedBoxes).map(cb => parseInt(cb.dataset.classId));
                    const classNames = Array.from(checkedBoxes).map(cb => cb.dataset.className);

                    if (classIds.length === 0) {
                        showToast('Hãy chọn ít nhất một lớp để xoá.', 'warning');
                        return;
                    }

                    const count = classIds.length;
                    const confirmMessage = count === 1 ?
                        `Xoá lớp "${classNames[0]}"?\n\nToàn bộ dữ liệu của lớp (chủ đề, nhiệm vụ, bài làm) sẽ bị xoá và không thể khôi phục.` :
                        `Xoá ${count} lớp đã chọn?\n\nToàn bộ dữ liệu của các lớp này sẽ bị xoá và không thể khôi phục.`;

                    if (!await UI.confirm(confirmMessage)) {
                        return;
                    }

                    this.disabled = true;
                    this.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Deleting...';

                    let successCount = 0;
                    let failCount = 0;

                    try {
                        for (const classId of classIds) {
                            try {
                                const response = await fetch('/api/manage_class.php', {
                                    method: 'POST',
                                    body: new URLSearchParams({
                                        action: 'delete_class',
                                        class_id: classId
                                    })
                                });

                                const data = await response.json();

                                if (data.success) {
                                    successCount++;
                                } else {
                                    failCount++;
                                }
                            } catch (error) {
                                console.error('Error deleting class:', classId, error);
                                failCount++;
                            }
                        }

                        if (successCount > 0) {
                            showToast(`Đã xoá ${successCount} lớp.`, 'success');
                        }
                        if (failCount > 0) {
                            showToast(`Không xoá được ${failCount} lớp.`, 'error');
                        }

                        setTimeout(() => {
                            location.reload();
                        }, 1500);

                    } catch (error) {
                        console.error('Error:', error);
                        showToast('Không xoá được lớp. Vui lòng thử lại.', 'error');
                        this.disabled = false;
                        this.innerHTML = '<i class="bi bi-trash"></i> Delete Selected (<span id="selectedClassCount">' + count + '</span>)';
                    }
                });
            }
        })();
    </script>
</body>

</html>
