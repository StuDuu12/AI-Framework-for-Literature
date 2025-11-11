<?php
// Redirect admin to admin dashboard
if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin') {
    header("Location: " . URL_ROOT . "/views/admin/dashboard.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php require_once './views/templates/assets.php'; ?>
    <title>Document</title>
    <style>
        .class-card {
            transition: all 0.2s ease;
        }

        .class-card:has(.class-checkbox:checked) {
            box-shadow: 0 0 0 2px #dc3545;
            background-color: #fff5f5;
        }

        .class-checkbox {
            cursor: pointer;
            width: 18px;
            height: 18px;
        }

        /* Disable sidebar links on dashboard page */
        .sidebar-disabled .nav-link {
            pointer-events: none;
            opacity: 0.5;
            cursor: not-allowed;
        }

        .sidebar-disabled .nav-heading {
            opacity: 0.5;
        }

        /* Hide toggle sidebar button on dashboard page */
        body.dashboard-page .toggle-sidebar-btn {
            display: none !important;
        }

        /* Ensure sidebar is always visible on dashboard */
        body.dashboard-page.toggle-sidebar #sidebar {
            transform: translateX(0);
        }
    </style>
    <script>
        // Disable sidebar navigation on dashboard page
        document.addEventListener('DOMContentLoaded', function() {
            // Add dashboard-page class to body
            document.body.classList.add('dashboard-page');

            const sidebarNav = document.getElementById('sidebar-nav');
            if (sidebarNav && sidebarNav.classList.contains('sidebar-disabled')) {
                const navLinks = sidebarNav.querySelectorAll('.nav-link');
                navLinks.forEach(link => {
                    link.addEventListener('click', function(e) {
                        e.preventDefault();
                        e.stopPropagation();
                        return false;
                    });
                });
            }

            // Disable toggle sidebar button
            const toggleBtn = document.querySelector('.toggle-sidebar-btn');
            if (toggleBtn) {
                toggleBtn.style.pointerEvents = 'none';
                toggleBtn.style.cursor = 'not-allowed';
                toggleBtn.addEventListener('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    return false;
                });
            }
        });
    </script>
</head>

<body class="toggle-sidebar">
    <?php
    require_once './views/templates/header.php';
    if (isset($_SESSION['user']['role']) && $_SESSION['user']['role'] === 'teacher') {
        require_once './views/templates/sidebar_teacher.php';
    } else if (isset($_SESSION['user']['role']) && $_SESSION['user']['role'] === 'student') {
        require_once './views/templates/sidebar_student.php';
    }
    ?>

    <main id="main" class="main">
        <div class="pagetitle">
            <div class="d-flex justify-content-between align-items-center">
                <h1>Danh sách các lớp</h1>
                <div class="d-flex gap-2">
                    <?php if (isset($_SESSION['user']['role']) && $_SESSION['user']['role'] === 'teacher'): ?>
                        <button type="button"
                            id="deleteSelectedClassesBtn"
                            class="btn btn-sm btn-danger"
                            style="display: none;">
                            <i class="bi bi-trash"></i> Xóa đã chọn (<span id="selectedClassCount">0</span>)
                        </button>
                        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createClassModal">
                            <i class="bi bi-plus-circle"></i> Tạo lớp mới
                        </button>
                    <?php endif; ?>
                </div>
            </div>
        </div><!-- End Page Title -->

        <section class="section dashboard">
            <?php
            $userid = $_SESSION['user']['user_id'];

            global $pdo;
            $stmt = $pdo->prepare("SELECT cm.class_id, c.class_name, cm.role FROM `class_member` cm LEFT JOIN `class` c ON c.class_id = cm.class_id WHERE `user_id` = :userid");
            $stmt->bindParam(':userid', $userid, PDO::PARAM_INT);
            $stmt->execute();
            $classes = $stmt->fetchAll(PDO::FETCH_ASSOC);
            if ($classes):
            ?>
                <div class="row">
                    <?php foreach ($classes as $class): ?>
                        <div class="col-lg-4 col-md-6 mb-4">
                            <div class="card h-100 class-card" data-class-id="<?php echo intval($class['class_id']); ?>">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-start mb-2">
                                        <div class="d-flex align-items-start gap-2 flex-grow-1">
                                            <?php if ($class['role'] === 'teacher'): ?>
                                                <input class="form-check-input class-checkbox mt-1"
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
                                                title="Chỉnh sửa tên lớp">
                                                <i class="bi bi-pencil"></i>
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                    <p class="card-text">Vai trò: <?php echo htmlspecialchars(ucfirst($class['role'])); ?></p>
                                    <a href="/joinclass?id=<?php echo urlencode($class['class_id']); ?>" class="btn btn-primary">Vào lớp</a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="alert alert-info" role="alert">
                    Bạn chưa tham gia lớp học nào.
                </div>
            <?php endif; ?>
        </section>

    </main><!-- End #main -->

    <!-- Create Class Modal -->
    <div class="modal fade" id="createClassModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Tạo lớp mới</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Tên lớp <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="newClassName" placeholder="Nhập tên lớp...">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                    <button type="button" class="btn btn-primary" id="createClassBtn">Tạo lớp</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit Class Modal -->
    <div class="modal fade" id="editClassModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Chỉnh sửa tên lớp</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="editClassId">
                    <div class="mb-3">
                        <label class="form-label">Tên lớp <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="editClassName" placeholder="Nhập tên lớp...">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                    <button type="button" class="btn btn-primary" id="updateClassBtn">Cập nhật</button>
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

            // Create class
            const createClassBtn = document.getElementById('createClassBtn');
            if (createClassBtn) {
                createClassBtn.addEventListener('click', function() {
                    const className = document.getElementById('newClassName').value.trim();

                    if (!className) {
                        showToast('Vui lòng nhập tên lớp', 'warning');
                        return;
                    }

                    this.disabled = true;
                    this.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Đang tạo...';

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
                                showToast('Tạo lớp thành công!', 'success');
                                setTimeout(() => {
                                    location.reload();
                                }, 1500);
                            } else {
                                showToast(data.message || 'Không thể tạo lớp', 'error');
                                this.disabled = false;
                                this.textContent = 'Tạo lớp';
                            }
                        })
                        .catch(error => {
                            console.error('Error:', error);
                            showToast('Có lỗi xảy ra khi tạo lớp', 'error');
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
                        showToast('Vui lòng nhập tên lớp', 'warning');
                        return;
                    }

                    this.disabled = true;
                    this.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Đang cập nhật...';

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
                                showToast('Cập nhật tên lớp thành công!', 'success');
                                setTimeout(() => {
                                    location.reload();
                                }, 1500);
                            } else {
                                showToast(data.message || 'Không thể cập nhật tên lớp', 'error');
                                this.disabled = false;
                                this.textContent = 'Cập nhật';
                            }
                        })
                        .catch(error => {
                            console.error('Error:', error);
                            showToast('Có lỗi xảy ra khi cập nhật tên lớp', 'error');
                            this.disabled = false;
                            this.textContent = 'Cập nhật';
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
                        showToast('Vui lòng chọn ít nhất một lớp để xóa!', 'warning');
                        return;
                    }

                    const count = classIds.length;
                    const confirmMessage = count === 1 ?
                        `Bạn có chắc chắn muốn xóa lớp "${classNames[0]}"?\n\nTất cả dữ liệu liên quan đến lớp này sẽ bị xóa.` :
                        `Bạn có chắc chắn muốn xóa ${count} lớp đã chọn?\n\nTất cả dữ liệu liên quan đến các lớp này sẽ bị xóa.`;

                    if (!confirm(confirmMessage)) {
                        return;
                    }

                    this.disabled = true;
                    this.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Đang xóa...';

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
                            showToast(`Đã xóa thành công ${successCount} lớp!`, 'success');
                        }
                        if (failCount > 0) {
                            showToast(`Không thể xóa ${failCount} lớp!`, 'error');
                        }

                        setTimeout(() => {
                            location.reload();
                        }, 1500);

                    } catch (error) {
                        console.error('Error:', error);
                        showToast('Có lỗi xảy ra khi xóa lớp', 'error');
                        this.disabled = false;
                        this.innerHTML = '<i class="bi bi-trash"></i> Xóa đã chọn (<span id="selectedClassCount">' + count + '</span>)';
                    }
                });
            }
        })();
    </script>
</body>

</html>