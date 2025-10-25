<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php require_once './views/templates/assets.php'; ?>
    <title>Document</title>
</head>

<body>
    <?php
    require_once './views/templates/header.php';
    require_once __DIR__ . '/../config/db.php';
    require_once __DIR__ . '/../config/helper_class.php';
    if (isset($_SESSION['user']['role']) && $_SESSION['user']['role'] === 'teacher') {
        require_once './views/templates/sidebar_teacher.php';
    } else if (isset($_SESSION['user']['role']) && $_SESSION['user']['role'] === 'student') {
        require_once './views/templates/sidebar_student.php';
    }

    $classId = function_exists('getCurrentClassId') ? getCurrentClassId() : (isset($_SESSION['current_class_id']) ? $_SESSION['current_class_id'] : 1);
    $topics = getClassTopics($classId);
    ?>

    <main id="main" class="main">
        <div class="pagetitle">
            <h1>Các chủ đề</h1>
            <nav>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href=""><?= $_SESSION['current_class_name']; ?></a></li>
                    <li class="breadcrumb-item active">Các chủ đề</li>
                </ol>
            </nav>
        </div><!-- End Page Title -->

        <section class="section dashboard">
            <div class="row">
                <!-- Topics Grid -->
                <div class="col-lg-12">
                    <div class="card">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h5 class="card-title">Danh sách chủ đề</h5>
                                <?php if (isset($_SESSION['user']['role']) && $_SESSION['user']['role'] === 'teacher'): ?>
                                    <button class="btn btn-primary" data-bs-toggle="modal"
                                        data-bs-target="#createTopicModal">
                                        <i class="bi bi-plus-circle"></i> Tạo chủ đề mới
                                    </button>
                                <?php endif; ?>
                            </div>

                            <div class="list-group" id="topicsList">
                                <?php if (empty($topics)): ?>
                                    <div class="alert alert-info text-center">
                                        <i class="bi bi-info-circle"></i> Chưa có chủ đề nào
                                    </div>
                                <?php else: ?>
                                    <?php foreach ($topics as $topic): ?>
                                        <div class="list-group-item list-group-item-action d-flex justify-content-between align-items-center py-3"
                                            data-topic-id="<?= intval($topic['topic_id']) ?>">
                                            <div class="flex-grow-1">
                                                <div class="d-flex align-items-center mb-2">
                                                    <h5 class="mb-0 me-3">
                                                        <?= htmlspecialchars($topic['topic_name']) ?>
                                                    </h5>
                                                    <span class="badge bg-primary">
                                                        <i class="bi bi-file-text"></i> Chủ đề
                                                    </span>
                                                </div>
                                                <div class="text-muted small">
                                                    <i class="bi bi-person"></i>
                                                    <?= htmlspecialchars($topic['added_by_name'] ?? 'N/A') ?>
                                                    <span class="mx-2">•</span>
                                                    <i class="bi bi-clock"></i>
                                                    <?= date('d/m/Y H:i', strtotime($topic['created_at'])) ?>
                                                </div>
                                            </div>
                                            <div class="d-flex gap-2 align-items-center">
                                                <a href="class/phases?topic_id=<?= intval($topic['topic_id']) ?>" class="btn btn-outline-primary btn-sm topic-link"
                                                    data-topic-id="<?= intval($topic['topic_id']) ?>" title="Vào chủ đề">
                                                    <i class="bi bi-box-arrow-in-right"></i> Vào chủ đề
                                                </a>
                                                <?php if (isset($_SESSION['user']['role']) && $_SESSION['user']['role'] === 'teacher'): ?>
                                                    <button class="btn btn-outline-secondary btn-sm edit-topic-btn"
                                                        data-topic-id="<?= intval($topic['topic_id']) ?>"
                                                        data-topic-name="<?= htmlspecialchars($topic['topic_name']) ?>"
                                                        title="Chỉnh sửa">
                                                        <i class="bi bi-pencil"></i>
                                                    </button>
                                                    <button class="btn btn-outline-danger btn-sm delete-topic-btn"
                                                        data-topic-id="<?= intval($topic['topic_id']) ?>"
                                                        data-topic-name="<?= htmlspecialchars($topic['topic_name']) ?>" title="Xóa">
                                                        <i class="bi bi-trash"></i>
                                                    </button>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Create Topic Modal -->
        <div class="modal fade" id="createTopicModal" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Tạo chủ đề mới</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Tên chủ đề</label>
                            <input type="text" class="form-control" id="newTopicName" placeholder="Nhập tên chủ đề...">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                        <button type="button" class="btn btn-primary" id="createTopicBtn">Tạo chủ đề</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Edit Topic Modal -->
        <div class="modal fade" id="editTopicModal" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Sửa chủ đề</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" id="editTopicId">
                        <div class="mb-3">
                            <label class="form-label">Tên chủ đề</label>
                            <input type="text" class="form-control" id="editTopicName" placeholder="Nhập tên chủ đề...">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                        <button type="button" class="btn btn-primary" id="updateTopicBtn">Cập nhật</button>
                    </div>
                </div>
            </div>
        </div>

    </main><!-- End #main -->
    <?php
    require_once 'views/templates/footer.php';
    require_once 'views/templates/vendors.php';
    ?>

    <script>
        // Topic Management JavaScript
        (function () {
            'use strict';

            const createModal = new bootstrap.Modal(document.getElementById('createTopicModal'));
            const editModal = new bootstrap.Modal(document.getElementById('editTopicModal'));

            // Create Topic
            document.getElementById('createTopicBtn')?.addEventListener('click', async function () {
                const topicName = document.getElementById('newTopicName').value.trim();

                if (!topicName) {
                    alert('Vui lòng nhập tên chủ đề');
                    return;
                }

                const btn = this;
                btn.disabled = true;
                btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Đang tạo...';

                try {
                    const formData = new FormData();
                    formData.append('action', 'create_topic');
                    formData.append('class_id', '<?= $classId ?>');
                    formData.append('topic_name', topicName);

                    const response = await fetch('api/manage_topics.php', {
                        method: 'POST',
                        body: formData
                    });

                    const data = await response.json();

                    if (data.success) {
                        alert('Tạo chủ đề thành công!');
                        createModal.hide();
                        document.getElementById('newTopicName').value = '';
                        location.reload(); // Reload to show new topic
                    } else {
                        alert('Lỗi: ' + (data.message || 'Không thể tạo chủ đề'));
                    }
                } catch (error) {
                    console.error('Error:', error);
                    alert('Có lỗi xảy ra khi tạo chủ đề');
                } finally {
                    btn.disabled = false;
                    btn.textContent = 'Tạo chủ đề';
                }
            });

            // Edit Topic - Open Modal
            document.querySelectorAll('.edit-topic-btn').forEach(btn => {
                btn.addEventListener('click', function () {
                    const topicId = this.dataset.topicId;
                    const topicName = this.dataset.topicName;

                    document.getElementById('editTopicId').value = topicId;
                    document.getElementById('editTopicName').value = topicName;

                    editModal.show();
                });
            });

            // Update Topic
            document.getElementById('updateTopicBtn')?.addEventListener('click', async function () {
                const topicId = document.getElementById('editTopicId').value;
                const topicName = document.getElementById('editTopicName').value.trim();

                if (!topicName) {
                    alert('Vui lòng nhập tên chủ đề');
                    return;
                }

                const btn = this;
                btn.disabled = true;
                btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Đang cập nhật...';

                try {
                    const formData = new FormData();
                    formData.append('action', 'update_topic');
                    formData.append('topic_id', topicId);
                    formData.append('topic_name', topicName);

                    const response = await fetch('api/manage_topics.php', {
                        method: 'POST',
                        body: formData
                    });

                    const data = await response.json();

                    if (data.success) {
                        alert('Cập nhật chủ đề thành công!');
                        editModal.hide();
                        location.reload(); // Reload to show updated topic
                    } else {
                        alert('Lỗi: ' + (data.message || 'Không thể cập nhật chủ đề'));
                    }
                } catch (error) {
                    console.error('Error:', error);
                    alert('Có lỗi xảy ra khi cập nhật chủ đề');
                } finally {
                    btn.disabled = false;
                    btn.textContent = 'Cập nhật';
                }
            });

            // Delete Topic
            document.querySelectorAll('.delete-topic-btn').forEach(btn => {
                btn.addEventListener('click', async function () {
                    const topicId = this.dataset.topicId;
                    const topicName = this.dataset.topicName;

                    if (!confirm(`Bạn có chắc muốn xóa chủ đề "${topicName}"?\n\nLưu ý: Tất cả dữ liệu liên quan đến chủ đề này sẽ bị xóa.`)) {
                        return;
                    }

                    try {
                        const formData = new FormData();
                        formData.append('action', 'delete_topic');
                        formData.append('topic_id', topicId);

                        const response = await fetch('api/manage_topics.php', {
                            method: 'POST',
                            body: formData
                        });

                        const data = await response.json();

                        if (data.success) {
                            alert('Xóa chủ đề thành công!');
                            // Remove topic item from DOM
                            const topicItem = document.querySelector(`[data-topic-id="${topicId}"]`);
                            if (topicItem) {
                                topicItem.remove();
                            }

                            // Check if list is empty
                            const topicsList = document.getElementById('topicsList');
                            if (topicsList.querySelectorAll('.list-group-item').length === 0) {
                                topicsList.innerHTML = `
                                <div class="alert alert-info text-center">
                                    <i class="bi bi-info-circle"></i> Chưa có chủ đề nào
                                </div>
                            `;
                            }
                        } else {
                            alert('Lỗi: ' + (data.message || 'Không thể xóa chủ đề'));
                        }
                    } catch (error) {
                        console.error('Error:', error);
                        alert('Có lỗi xảy ra khi xóa chủ đề');
                    }
                });
            });

            // Clear create modal when closed
            document.getElementById('createTopicModal')?.addEventListener('hidden.bs.modal', function () {
                document.getElementById('newTopicName').value = '';
            });

        })();
    </script>
</body>

</html>