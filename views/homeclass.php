<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php require_once __DIR__ . '/templates/assets.php'; ?>
    <title data-lang-key="topics_page_title">Topics</title>
</head>

<body>
    <?php
    require_once __DIR__ . '/templates/header.php';
    require_once __DIR__ . '/../config/db.php';
    require_once __DIR__ . '/../config/helper_class.php';
    require_once __DIR__ . '/templates/sidebar.php';
    

    $classId = getCurrentClassId();
    if (!$classId) {
        echo '<main id="main" class="main"><div class="pagetitle"><h1 data-lang-key="topics_page_title">Topics</h1></div>';
        echo '<section class="section"><div class="alert alert-warning m-3">';
        echo '<i class="bi bi-exclamation-triangle me-2"></i>';
        echo '<span data-lang-key="select_class_first">Please select a class from the Class List first.</span> ';
        echo '<a href="' . URL_ROOT . '/dashboard" class="alert-link" data-lang-key="class_list_title">Class List</a>';
        echo '</div></section></main>';
        require_once __DIR__ . '/templates/footer.php';
        require_once __DIR__ . '/templates/vendors.php';
        echo '</body></html>';
        exit;
    }
    $topics = getClassTopics($classId);
    ?>

    <main id="main" class="main">
        <div class="pagetitle">
            <h1 data-lang-key="topics_page_title">Topics</h1>
            <nav>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item active"><?= htmlspecialchars($_SESSION['current_class_name'] ?? 'Class', ENT_QUOTES, 'UTF-8') ?></li>
                </ol>
            </nav>
        </div><!-- End Page Title -->

        <section class="section dashboard topics-page">
            <div class="row">
                <!-- Topics Grid -->
                <div class="col-lg-12">
                    <div class="card">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h5 class="card-title" data-lang-key="topic_list">Topic List</h5>
                                <?php if (isset($_SESSION['user']['role']) && $_SESSION['user']['role'] === 'teacher'): ?>
                                    <button class="btn btn-primary" data-bs-toggle="modal"
                                        data-bs-target="#createTopicModal">
                                        <i class="bi bi-plus-circle"></i> <span data-lang-key="create_new_topic">Create New Topic</span>
                                    </button>
                                <?php endif; ?>
                            </div>

                            <div class="list-group" id="topicsList">
                                <?php if (empty($topics)): ?>
                                    <div class="alert alert-info text-center fade-in">
                                        <i class="bi bi-info-circle"></i> <span data-lang-key="no_topics">No topics yet</span>
                                    </div>
                                <?php else: ?>
                                    <?php foreach ($topics as $index => $topic): ?>
                                        <div class="list-group-item list-group-item-action d-flex justify-content-between align-items-center py-3 topic-item"
                                            data-topic-id="<?= intval($topic['topic_id']) ?>"
                                            style="animation-delay: <?= $index * 0.1 ?>s">
                                            <div class="flex-grow-1">
                                                <div class="d-flex align-items-center mb-2">
                                                    <h5 class="mb-0 me-3 topic-title">
                                                        <i class="bi bi-book text-primary me-2"></i>
                                                        <?= htmlspecialchars($topic['topic_name']) ?>
                                                    </h5>
                                                    <span class="badge bg-primary topic-badge">
                                                        <i class="bi bi-file-text"></i> <span>Topic</span>
                                                    </span>
                                                </div>
                                                <div class="text-muted small topic-meta">
                                                    <i class="bi bi-person"></i>
                                                    <?= htmlspecialchars($topic['added_by_name'] ?? 'N/A') ?>
                                                    <span class="mx-2">&bull;</span>
                                                    <i class="bi bi-clock"></i>
                                                    <?= date('d/m/Y H:i', strtotime($topic['created_at'])) ?>
                                                </div>
                                            </div>
                                            <div class="d-flex gap-2 align-items-center topic-actions">
                                                <a href="<?= URL_ROOT ?>/class/phases?topic_id=<?= intval($topic['topic_id']) ?>"
                                                    class="btn btn-primary btn-sm topic-link"
                                                    data-topic-id="<?= intval($topic['topic_id']) ?>"
                                                    title="Enter topic">
                                                    <i class="bi bi-box-arrow-in-right"></i> <span data-lang-key="enter_topic">Enter Topic</span>
                                                </a>
                                                <?php if (isset($_SESSION['user']['role']) && $_SESSION['user']['role'] === 'teacher'): ?>
                                                    <button class="btn btn-outline-secondary btn-sm edit-topic-btn"
                                                        data-topic-id="<?= intval($topic['topic_id']) ?>"
                                                        data-topic-name="<?= htmlspecialchars($topic['topic_name']) ?>"
                                                        title="Edit">
                                                        <i class="bi bi-pencil"></i>
                                                    </button>
                                                    <button class="btn btn-outline-danger btn-sm delete-topic-btn"
                                                        data-topic-id="<?= intval($topic['topic_id']) ?>"
                                                        data-topic-name="<?= htmlspecialchars($topic['topic_name']) ?>"
                                                        title="Delete">
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
                        <h5 class="modal-title">Create New Topic</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Topic Name</label>
                            <input type="text" class="form-control" id="newTopicName" placeholder="Enter topic name...">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="button" class="btn btn-primary" id="createTopicBtn">Create Topic</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Edit Topic Modal -->
        <div class="modal fade" id="editTopicModal" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Edit Topic</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" id="editTopicId">
                        <div class="mb-3">
                            <label class="form-label">Topic Name</label>
                            <input type="text" class="form-control" id="editTopicName" placeholder="Enter topic name...">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="button" class="btn btn-primary" id="updateTopicBtn">Update</button>
                    </div>
                </div>
            </div>
        </div>

    </main><!-- End #main -->
    <?php
    require_once __DIR__ . '/templates/footer.php';
    require_once __DIR__ . '/templates/vendors.php';
    ?>
    <script>
        // Topic Management JavaScript
        (function() {
            'use strict';

            const createModal = new bootstrap.Modal(document.getElementById('createTopicModal'));
            const editModal = new bootstrap.Modal(document.getElementById('editTopicModal'));

            // Create Topic
            document.getElementById('createTopicBtn')?.addEventListener('click', async function() {
                const topicName = document.getElementById('newTopicName').value.trim();

                if (!topicName) {
                    alert('Please enter a topic name');
                    return;
                }

                const btn = this;
                btn.disabled = true;
                btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Creating...';

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
                        alert('Topic created successfully!');
                        createModal.hide();
                        document.getElementById('newTopicName').value = '';
                        location.reload(); // Reload to show new topic
                    } else {
                        alert('Error: ' + (data.message || 'Could not create topic'));
                    }
                } catch (error) {
                    console.error('Error:', error);
                    alert('An error occurred while creating the topic');
                } finally {
                    btn.disabled = false;
                    btn.textContent = 'Create Topic';
                }
            });

            // Edit Topic - Open Modal
            document.querySelectorAll('.edit-topic-btn').forEach(btn => {
                btn.addEventListener('click', function() {
                    const topicId = this.dataset.topicId;
                    const topicName = this.dataset.topicName;

                    document.getElementById('editTopicId').value = topicId;
                    document.getElementById('editTopicName').value = topicName;

                    editModal.show();
                });
            });

            // Update Topic
            document.getElementById('updateTopicBtn')?.addEventListener('click', async function() {
                const topicId = document.getElementById('editTopicId').value;
                const topicName = document.getElementById('editTopicName').value.trim();

                if (!topicName) {
                    alert('Please enter a topic name');
                    return;
                }

                const btn = this;
                btn.disabled = true;
                btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Updating...';

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
                        alert('Topic updated successfully!');
                        editModal.hide();
                        location.reload(); // Reload to show updated topic
                    } else {
                        alert('Error: ' + (data.message || 'Could not update topic'));
                    }
                } catch (error) {
                    console.error('Error:', error);
                    alert('An error occurred while updating the topic');
                } finally {
                    btn.disabled = false;
                    btn.textContent = 'Update';
                }
            });

            // Delete Topic
            document.querySelectorAll('.delete-topic-btn').forEach(btn => {
                btn.addEventListener('click', async function() {
                    const topicId = this.dataset.topicId;
                    const topicName = this.dataset.topicName;

                    if (!confirm(`Are you sure you want to delete the topic "${topicName}"?\n\nNote: All data related to this topic will be deleted.`)) {
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
                            alert('Topic deleted successfully!');
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
                                    <i class="bi bi-info-circle"></i> No topics yet
                                </div>
                            `;
                            }
                        } else {
                            alert('Error: ' + (data.message || 'Could not delete topic'));
                        }
                    } catch (error) {
                        console.error('Error:', error);
                        alert('An error occurred while deleting the topic');
                    }
                });
            });

            // Clear create modal when closed
            document.getElementById('createTopicModal')?.addEventListener('hidden.bs.modal', function() {
                document.getElementById('newTopicName').value = '';
            });

        })();
    </script>
</body>

</html>
