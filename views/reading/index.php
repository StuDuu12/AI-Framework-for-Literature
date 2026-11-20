<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php require_once __DIR__ . '/../templates/assets.php'; ?>
    <title>Danh sách đọc</title>
</head>

<body>
    <?php
    require_once __DIR__ . '/../templates/header.php';
    require_once __DIR__ . '/../templates/sidebar.php';
    ?>

    <main id="main" class="main">
        <div class="pagetitle">
            <h1><i class="bi bi-bookmark-check"></i> Danh sách đọc</h1>
            <nav>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="<?= URL_ROOT ?>/class">Trang chủ</a></li>
                    <li class="breadcrumb-item active">Danh sách đọc</li>
                </ol>
            </nav>
        </div>

        <section class="section">
            <div class="row">
                <!-- Bookmarks -->
                <div class="col-lg-6">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title"><i class="bi bi-bookmark-star"></i> Bookmark của tôi</h5>
                            <div id="bookmarksList">
                                <div class="text-center py-3">
                                    <div class="spinner-border spinner-border-sm text-primary" role="status"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Reading Lists -->
                <div class="col-lg-6">
                    <div class="card">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h5 class="card-title mb-0"><i class="bi bi-list-ul"></i> Danh sách</h5>
                                <button class="btn btn-primary btn-sm" onclick="showCreateListModal()">
                                    <i class="bi bi-plus"></i> Tạo mới
                                </button>
                            </div>
                            <div id="listsContainer">
                                <div class="text-center py-3">
                                    <div class="spinner-border spinner-border-sm text-primary" role="status"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <!-- Modal: Create List -->
    <div class="modal fade" id="createListModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Tạo danh sách mới</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="listName" class="form-label">Tên danh sách <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="listName" placeholder="VD: Bài đọc yêu thích">
                    </div>
                    <div class="mb-3">
                        <label for="listDescription" class="form-label">Mô tả</label>
                        <textarea class="form-control" id="listDescription" rows="2" placeholder="Mô tả ngắn..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                    <button type="button" class="btn btn-primary" onclick="createList()">Tạo</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal: List Detail -->
    <div class="modal fade" id="listDetailModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="listDetailTitle">Chi tiết danh sách</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div id="listDetailContent"></div>
                </div>
            </div>
        </div>
    </div>

    <?php
    require_once __DIR__ . '/../templates/footer.php';
    require_once __DIR__ . '/../templates/vendors.php';
    ?>

    <script>
        let createListModal, listDetailModal;

        document.addEventListener('DOMContentLoaded', function() {
            createListModal = new bootstrap.Modal(document.getElementById('createListModal'));
            listDetailModal = new bootstrap.Modal(document.getElementById('listDetailModal'));
            loadBookmarks();
            loadLists();
        });

        async function loadBookmarks() {
            try {
                const resp = await fetch('/api/reading_list.php?action=get_bookmarks');
                const data = await resp.json();
                const container = document.getElementById('bookmarksList');

                if (!data.success || data.bookmarks.length === 0) {
                    container.innerHTML = '<p class="text-muted text-center py-3">Chưa có bookmark nào</p>';
                    return;
                }

                container.innerHTML = data.bookmarks.map(b => `
                    <div class="d-flex justify-content-between align-items-start py-2 border-bottom">
                        <div>
                            <h6 class="mb-1">${escapeHtml(b.task_title)}</h6>
                            <small class="text-muted">
                                <i class="bi bi-folder"></i> ${escapeHtml(b.topic_name || '')}
                                · <i class="bi bi-signpost"></i> ${escapeHtml(b.phase_name || '')}
                            </small>
                            ${b.note ? `<br><small class="text-info"><i class="bi bi-chat-left-text"></i> ${escapeHtml(b.note)}</small>` : ''}
                            <br><small class="text-muted"><i class="bi bi-clock"></i> ${formatDate(b.created_at)}</small>
                        </div>
                        <button class="btn btn-sm btn-outline-danger" onclick="toggleBookmark(${b.task_id})" title="Xóa bookmark">
                            <i class="bi bi-bookmark-x"></i>
                        </button>
                    </div>
                `).join('');
            } catch (e) {
                console.error(e);
            }
        }

        async function loadLists() {
            try {
                const resp = await fetch('/api/reading_list.php?action=get_lists');
                const data = await resp.json();
                const container = document.getElementById('listsContainer');

                if (!data.success || data.lists.length === 0) {
                    container.innerHTML = '<p class="text-muted text-center py-3">Chưa có danh sách nào</p>';
                    return;
                }

                container.innerHTML = data.lists.map(l => `
                    <div class="d-flex justify-content-between align-items-center py-2 border-bottom">
                        <div class="flex-grow-1" style="cursor:pointer" onclick="viewListDetail(${l.list_id}, '${escapeHtml(l.name).replace(/'/g, "\\'")}')">
                            <h6 class="mb-0"><i class="bi bi-list-ul text-primary"></i> ${escapeHtml(l.name)}</h6>
                            <small class="text-muted">${l.item_count} mục</small>
                        </div>
                        <button class="btn btn-sm btn-outline-danger" onclick="deleteList(${l.list_id})" title="Xóa">
                            <i class="bi bi-trash"></i>
                        </button>
                    </div>
                `).join('');
            } catch (e) {
                console.error(e);
            }
        }

        async function toggleBookmark(taskId) {
            try {
                await fetch('/api/reading_list.php?action=toggle_bookmark', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ task_id: taskId })
                });
                loadBookmarks();
            } catch (e) {
                UI.error('Lỗi kết nối máy chủ. Vui lòng thử lại.');
            }
        }

        function showCreateListModal() {
            document.getElementById('listName').value = '';
            document.getElementById('listDescription').value = '';
            createListModal.show();
        }

        async function createList() {
            const name = document.getElementById('listName').value.trim();
            const description = document.getElementById('listDescription').value.trim();

            if (!name) {
                UI.warning('Vui lòng nhập tên danh sách.');
                return;
            }

            try {
                const resp = await fetch('/api/reading_list.php?action=create_list', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ name, description })
                });
                const data = await resp.json();

                if (data.success) {
                    createListModal.hide();
                    loadLists();
                } else {
                    UI.error(data.message || 'Không tạo được danh sách.');
                }
            } catch (e) {
                UI.error('Lỗi kết nối máy chủ. Vui lòng thử lại.');
            }
        }

        async function viewListDetail(listId, name) {
            document.getElementById('listDetailTitle').textContent = name;
            document.getElementById('listDetailContent').innerHTML = '<div class="text-center py-3"><div class="spinner-border spinner-border-sm text-primary"></div></div>';
            listDetailModal.show();

            try {
                const resp = await fetch(`/api/reading_list.php?action=get_list_items&list_id=${listId}`);
                const data = await resp.json();

                if (!data.success || data.items.length === 0) {
                    document.getElementById('listDetailContent').innerHTML = '<p class="text-muted text-center">Danh sách trống</p>';
                    return;
                }

                document.getElementById('listDetailContent').innerHTML = data.items.map(item => `
                    <div class="d-flex justify-content-between align-items-center py-2 border-bottom">
                        <div>
                            <h6 class="mb-0">${escapeHtml(item.task_title)}</h6>
                            <small class="text-muted">${escapeHtml(item.topic_name || '')} · ${escapeHtml(item.phase_name || '')}</small>
                        </div>
                        <button class="btn btn-sm btn-outline-danger" onclick="removeFromList(${listId}, ${item.task_id})" aria-label="Bỏ khỏi danh sách đọc" title="Bỏ khỏi danh sách đọc">
                            <i class="bi bi-x" aria-hidden="true"></i>
                        </button>
                    </div>
                `).join('');
            } catch (e) {
                document.getElementById('listDetailContent').innerHTML = '<p class="text-danger">Lỗi tải dữ liệu</p>';
            }
        }

        async function removeFromList(listId, taskId) {
            try {
                await fetch('/api/reading_list.php?action=remove_from_list', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ list_id: listId, task_id: taskId })
                });
                viewListDetail(listId, document.getElementById('listDetailTitle').textContent);
                loadLists();
            } catch (e) {
                UI.error('Lỗi kết nối máy chủ. Vui lòng thử lại.');
            }
        }

        async function deleteList(listId) {
            if (!await UI.confirm('Xóa danh sách này?')) return;

            try {
                await fetch('/api/reading_list.php?action=delete_list', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ list_id: listId })
                });
                loadLists();
            } catch (e) {
                UI.error('Lỗi kết nối máy chủ. Vui lòng thử lại.');
            }
        }

        function formatDate(dateStr) {
            if (!dateStr) return '';
            const d = new Date(dateStr);
            return d.toLocaleDateString('vi-VN') + ' ' + d.toLocaleTimeString('vi-VN', { hour: '2-digit', minute: '2-digit' });
        }

        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text || '';
            return div.innerHTML;
        }
    </script>
</body>

</html>
