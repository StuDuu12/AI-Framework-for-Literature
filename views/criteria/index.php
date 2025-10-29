<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php require_once './views/templates/assets.php'; ?>
    <title>Quản lý Tiêu chí Đánh giá</title>
    <style>
        /* Toast container positioning */
        .toast-container {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 9999;
        }
        
        .group-card {
            cursor: pointer;
            transition: all 0.3s ease;
            border-left: 4px solid transparent;
        }
        .group-card:hover {
            border-left-color: #4154f1;
            box-shadow: 0 0 20px rgba(65, 84, 241, 0.1);
        }
        .group-card.active {
            border-left-color: #4154f1;
            background-color: #f8f9ff;
        }
        .criteria-item {
            transition: all 0.2s ease;
        }
        .criteria-item:hover {
            background-color: #f8f9fa;
        }
        .badge-score {
            font-size: 0.85rem;
            padding: 0.35rem 0.65rem;
        }
        .empty-state {
            text-align: center;
            padding: 3rem;
            color: #6c757d;
        }
        .empty-state i {
            font-size: 4rem;
            opacity: 0.3;
            margin-bottom: 1rem;
        }
        .group-actions {
            opacity: 0;
            transition: opacity 0.2s ease;
        }
        .group-card:hover .group-actions {
            opacity: 1;
        }
        .list-group-item {
            border-left: 3px solid transparent;
        }
        .list-group-item.active {
            background-color: #f8f9ff;
            border-left-color: #4154f1;
            color: #333;
        }
    </style>
</head>

<body>
    <?php
    require_once './views/templates/header.php';
    if (isset($_SESSION['user']['role']) && $_SESSION['user']['role'] === 'teacher') {
        require_once './views/templates/sidebar_teacher.php';
    } else if(isset($_SESSION['user']['role']) && $_SESSION['user']['role'] === 'student') {
        require_once './views/templates/sidebar_student.php';
    }
    
    // Get class_id from session or URL
    $class_id = isset($_SESSION['current_class_id']) ? $_SESSION['current_class_id'] : (isset($_GET['class_id']) ? intval($_GET['class_id']) : null);
    ?>

    <main id="main" class="main">
        <div class="pagetitle">
            <h1><i class="bi bi-card-checklist"></i> Quản lý Tiêu chí Đánh giá</h1>
            <nav>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="/">Trang chủ</a></li>
                    <li class="breadcrumb-item active">Tiêu chí đánh giá</li>
                </ol>
            </nav>
        </div><!-- End Page Title -->

        <section class="section">
            <div class="row">
                <!-- Left Column: Criteria Groups -->
                <div class="col-lg-4">
                    <div class="card">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center mb-3 mt-3">
                                <h5 class="card-title mb-0">Nhóm Tiêu chí</h5>
                                <button class="btn btn-primary btn-sm" onclick="showAddGroupModal()">
                                    <i class="bi bi-plus-circle"></i> Thêm nhóm
                                </button>
                            </div>
                            
                            <div id="groupsList" class="list-group">
                                <!-- Groups will be loaded here -->
                                <div class="text-center py-4">
                                    <div class="spinner-border text-primary" role="status">
                                        <span class="visually-hidden">Đang tải...</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Right Column: Criteria List -->
                <div class="col-lg-8">
                    <div class="card">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center mb-3 mt-3">
                                <div>
                                    <h5 class="card-title mb-0" id="criteriaTitle">Tiêu chí</h5>
                                    <small class="text-muted" id="groupDescription"></small>
                                </div>
                                <button class="btn btn-success btn-sm" id="addCriteriaBtn" onclick="showAddCriteriaModal()" disabled>
                                    <i class="bi bi-plus-circle"></i> Thêm tiêu chí
                                </button>
                            </div>
                            
                            <div id="criteriaList">
                                <!-- Criteria will be loaded here -->
                                <div class="empty-state">
                                    <i class="bi bi-inbox"></i>
                                    <h5>Chưa có tiêu chí</h5>
                                    <p>Vui lòng chọn một nhóm tiêu chí bên trái hoặc tạo nhóm mới</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Modal: Add/Edit Group -->
        <div class="modal fade" id="groupModal" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="groupModalTitle">Thêm Nhóm Tiêu chí</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <form id="groupForm">
                            <input type="hidden" id="groupId" name="nhom_tc_id">
                            <div class="mb-3">
                                <label for="groupName" class="form-label">Tên nhóm <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="groupName" name="ten_nhom_tc" required 
                                       placeholder="Ví dụ: Đánh giá nội dung">
                            </div>
                            <div class="mb-3">
                                <label for="groupDescription" class="form-label">Mô tả</label>
                                <textarea class="form-control" id="groupDescription" name="description" rows="3" 
                                          placeholder="Mô tả chi tiết về nhóm tiêu chí này"></textarea>
                            </div>
                        </form>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                        <button type="button" class="btn btn-primary" onclick="saveGroup()">
                            <i class="bi bi-check-circle"></i> Lưu
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal: Add/Edit Criteria -->
        <div class="modal fade" id="criteriaModal" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="criteriaModalTitle">Thêm Tiêu chí</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <form id="criteriaForm">
                            <input type="hidden" id="criteriaId" name="tc_id">
                            <input type="hidden" id="criteriaGroupId" name="nhom_tc_id">
                            
                            <div class="mb-3">
                                <label for="criteriaName" class="form-label">Tên tiêu chí <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="criteriaName" name="name_tc" required 
                                       placeholder="Ví dụ: Độ chính xác của nội dung">
                            </div>
                            
                            <div class="mb-3">
                                <label for="criteriaDescription" class="form-label">Mô tả</label>
                                <textarea class="form-control" id="criteriaDescription" name="description" rows="2" 
                                          placeholder="Mô tả chi tiết về tiêu chí"></textarea>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="minScore" class="form-label">Điểm tối thiểu <span class="text-danger">*</span></label>
                                        <input type="number" class="form-control" id="minScore" name="min_score" 
                                               min="0" step="1" value="0" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="maxScore" class="form-label">Điểm tối đa <span class="text-danger">*</span></label>
                                        <input type="number" class="form-control" id="maxScore" name="max_score" 
                                               min="0" step="1" value="10" required>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                        <button type="button" class="btn btn-success" onclick="saveCriteria()">
                            <i class="bi bi-check-circle"></i> Lưu
                        </button>
                    </div>
                </div>
            </div>
        </div>

    </main><!-- End #main -->
    
    <!-- Toast Container -->
    <div class="toast-container position-fixed top-0 end-0 p-3">
        <div id="successToast" class="toast align-items-center text-white bg-success border-0" role="alert" aria-live="assertive" aria-atomic="true">
            <div class="d-flex">
                <div class="toast-body">
                    <i class="bi bi-check-circle me-2"></i>
                    <span id="successToastMessage"></span>
                </div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
        </div>
        
        <div id="errorToast" class="toast align-items-center text-white bg-danger border-0" role="alert" aria-live="assertive" aria-atomic="true">
            <div class="d-flex">
                <div class="toast-body">
                    <i class="bi bi-exclamation-circle me-2"></i>
                    <span id="errorToastMessage"></span>
                </div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
        </div>
    </div>
    
    <?php
    require_once 'views/templates/footer.php';
    require_once 'views/templates/vendors.php';
    ?>
    
    <script>
        let currentClassId = <?php echo $class_id ? $class_id : 'null'; ?>;
        let selectedGroupId = null;
        let groupModal, criteriaModal;

        document.addEventListener('DOMContentLoaded', function() {
            groupModal = new bootstrap.Modal(document.getElementById('groupModal'));
            criteriaModal = new bootstrap.Modal(document.getElementById('criteriaModal'));
            
            if (currentClassId) {
                loadGroups();
            } else {
                showError('Không tìm thấy thông tin lớp học');
            }
        });

        // Load groups
        async function loadGroups() {
            try {
                const response = await fetch(`/api/manage_criteria.php?action=get_groups&class_id=${currentClassId}`);
                const result = await response.json();
                
                if (result.success) {
                    displayGroups(result.data);
                } else {
                    showError(result.message);
                }
            } catch (error) {
                console.error('Error loading groups:', error);
                showError('Lỗi khi tải danh sách nhóm tiêu chí');
            }
        }

        // Display groups
        function displayGroups(groups) {
            const container = document.getElementById('groupsList');
            
            if (groups.length === 0) {
                container.innerHTML = `
                    <div class="empty-state">
                        <i class="bi bi-folder-x"></i>
                        <p class="mb-0">Chưa có nhóm tiêu chí nào</p>
                        <small class="text-muted">Nhấn "Thêm nhóm" để bắt đầu</small>
                    </div>
                `;
                return;
            }
            
            container.innerHTML = groups.map(group => `
                <div class="list-group-item list-group-item-action ${group.nhom_tc_id == selectedGroupId ? 'active' : ''}" 
                     onclick="selectGroup(${group.nhom_tc_id}, '${escapeHtml(group.ten_nhom_tc)}', '${escapeHtml(group.description || '')}')">
                    <div class="d-flex justify-content-between align-items-center">
                        <div class="flex-grow-1">
                            <h6 class="mb-1">${escapeHtml(group.ten_nhom_tc)}</h6>
                            ${group.description ? `<small class="text-muted">${escapeHtml(group.description)}</small>` : ''}
                            <div class="mt-1">
                                <span class="badge bg-info">${group.criteria_count || 0} tiêu chí</span>
                            </div>
                        </div>
                        <div class="btn-group group-actions" role="group">
                            <button class="btn btn-sm btn-outline-primary" onclick="event.stopPropagation(); editGroup(${group.nhom_tc_id})" title="Sửa">
                                <i class="bi bi-pencil"></i>
                            </button>
                            <button class="btn btn-sm btn-outline-danger" onclick="event.stopPropagation(); deleteGroup(${group.nhom_tc_id}, '${escapeHtml(group.ten_nhom_tc)}')" title="Xóa">
                                <i class="bi bi-trash"></i>
                            </button>
                        </div>
                    </div>
                </div>
            `).join('');
        }

        // Select group
        async function selectGroup(groupId, groupName, description) {
            selectedGroupId = groupId;
            document.getElementById('criteriaTitle').textContent = groupName;
            document.getElementById('groupDescription').textContent = description;
            document.getElementById('addCriteriaBtn').disabled = false;
            
            // Update active state
            document.querySelectorAll('#groupsList .list-group-item').forEach(item => {
                item.classList.remove('active');
            });
            event.currentTarget.classList.add('active');
            
            await loadCriteria(groupId);
        }

        // Load criteria
        async function loadCriteria(groupId) {
            try {
                const response = await fetch(`/api/manage_criteria.php?action=get_criteria&nhom_tc_id=${groupId}`);
                const result = await response.json();
                
                if (result.success) {
                    displayCriteria(result.data);
                } else {
                    showError(result.message);
                }
            } catch (error) {
                console.error('Error loading criteria:', error);
                showError('Lỗi khi tải danh sách tiêu chí');
            }
        }

        // Display criteria
        function displayCriteria(criteria) {
            const container = document.getElementById('criteriaList');
            
            if (criteria.length === 0) {
                container.innerHTML = `
                    <div class="empty-state">
                        <i class="bi bi-list-check"></i>
                        <h6>Chưa có tiêu chí nào</h6>
                        <p class="mb-0 text-muted">Nhấn "Thêm tiêu chí" để thêm tiêu chí mới</p>
                    </div>
                `;
                return;
            }
            
            container.innerHTML = `
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th width="40%">Tên tiêu chí</th>
                                <th width="30%">Mô tả</th>
                                <th width="15%" class="text-center">Thang điểm</th>
                                <th width="15%" class="text-end">Thao tác</th>
                            </tr>
                        </thead>
                        <tbody>
                            ${criteria.map(item => `
                                <tr class="criteria-item">
                                    <td><strong>${escapeHtml(item.name_tc)}</strong></td>
                                    <td><small class="text-muted">${escapeHtml(item.description || 'Không có mô tả')}</small></td>
                                    <td class="text-center">
                                        <span class="badge badge-score bg-success">${item.min_score} - ${item.max_score}</span>
                                    </td>
                                    <td class="text-end">
                                        <button class="btn btn-sm btn-outline-primary" onclick="editCriteria(${item.tc_id})" title="Sửa">
                                            <i class="bi bi-pencil"></i>
                                        </button>
                                        <button class="btn btn-sm btn-outline-danger" onclick="deleteCriteria(${item.tc_id}, '${escapeHtml(item.name_tc)}')" title="Xóa">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                            `).join('')}
                        </tbody>
                    </table>
                </div>
            `;
        }

        // Show add group modal
        function showAddGroupModal() {
            document.getElementById('groupModalTitle').textContent = 'Thêm Nhóm Tiêu chí';
            document.getElementById('groupForm').reset();
            document.getElementById('groupId').value = '';
            groupModal.show();
        }

        // Edit group
        async function editGroup(groupId) {
            try {
                const response = await fetch(`/api/manage_criteria.php?action=get_groups&nhom_tc_id=${groupId}`);
                const result = await response.json();
                
                if (result.success && result.data) {
                    const group = result.data;
                    document.getElementById('groupModalTitle').textContent = 'Sửa Nhóm Tiêu chí';
                    document.getElementById('groupId').value = group.nhom_tc_id;
                    document.getElementById('groupName').value = group.ten_nhom_tc;
                    document.getElementById('groupDescription').value = group.description || '';
                    groupModal.show();
                }
            } catch (error) {
                console.error('Error loading group:', error);
                showError('Lỗi khi tải thông tin nhóm');
            }
        }

        // Save group
        async function saveGroup() {
            const form = document.getElementById('groupForm');
            if (!form.checkValidity()) {
                form.reportValidity();
                return;
            }
            
            const formData = new FormData(form);
            formData.append('class_id', currentClassId);
            
            const groupId = document.getElementById('groupId').value;
            formData.append('action', groupId ? 'update_group' : 'create_group');
            const url = '/api/manage_criteria.php';
            
            try {
                const response = await fetch(url, {
                    method: 'POST',
                    body: formData
                });
                const result = await response.json();
                
                if (result.success) {
                    showSuccess(result.message);
                    groupModal.hide();
                    await loadGroups();
                    
                    if (groupId && selectedGroupId == groupId) {
                        // Reload criteria if we edited the selected group
                        await loadCriteria(groupId);
                    }
                } else {
                    showError(result.message);
                }
            } catch (error) {
                console.error('Error saving group:', error);
                showError('Lỗi khi lưu nhóm tiêu chí');
            }
        }

        // Delete group
        async function deleteGroup(groupId, groupName) {
            if (!confirm(`Bạn có chắc chắn muốn xóa nhóm "${groupName}"?\n\nLưu ý: Phải xóa hết các tiêu chí trong nhóm trước khi xóa nhóm.`)) {
                return;
            }
            
            try {
                const response = await fetch('/api/manage_criteria.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ action: 'delete_group', nhom_tc_id: groupId })
                });
                const result = await response.json();
                
                if (result.success) {
                    showSuccess(result.message);
                    if (selectedGroupId == groupId) {
                        selectedGroupId = null;
                        document.getElementById('criteriaList').innerHTML = `
                            <div class="empty-state">
                                <i class="bi bi-inbox"></i>
                                <p>Vui lòng chọn một nhóm tiêu chí</p>
                            </div>
                        `;
                    }
                    await loadGroups();
                } else {
                    showError(result.message);
                }
            } catch (error) {
                console.error('Error deleting group:', error);
                showError('Lỗi khi xóa nhóm tiêu chí');
            }
        }

        // Show add criteria modal
        function showAddCriteriaModal() {
            if (!selectedGroupId) {
                showError('Vui lòng chọn một nhóm tiêu chí trước');
                return;
            }
            
            document.getElementById('criteriaModalTitle').textContent = 'Thêm Tiêu chí';
            document.getElementById('criteriaForm').reset();
            document.getElementById('criteriaId').value = '';
            document.getElementById('criteriaGroupId').value = selectedGroupId;
            document.getElementById('minScore').value = '0';
            document.getElementById('maxScore').value = '10';
            criteriaModal.show();
        }

        // Edit criteria
        async function editCriteria(criteriaId) {
            try {
                const response = await fetch(`/api/manage_criteria.php?action=get_criteria&tc_id=${criteriaId}`);
                const result = await response.json();
                
                if (result.success && result.data) {
                    const criteria = result.data;
                    document.getElementById('criteriaModalTitle').textContent = 'Sửa Tiêu chí';
                    document.getElementById('criteriaId').value = criteria.tc_id;
                    document.getElementById('criteriaGroupId').value = criteria.nhom_tc_id;
                    document.getElementById('criteriaName').value = criteria.name_tc;
                    document.getElementById('criteriaDescription').value = criteria.description || '';
                    document.getElementById('minScore').value = criteria.min_score;
                    document.getElementById('maxScore').value = criteria.max_score;
                    criteriaModal.show();
                }
            } catch (error) {
                console.error('Error loading criteria:', error);
                showError('Lỗi khi tải thông tin tiêu chí');
            }
        }

        // Save criteria
        async function saveCriteria() {
            const form = document.getElementById('criteriaForm');
            if (!form.checkValidity()) {
                form.reportValidity();
                return;
            }
            
            const minScore = parseInt(document.getElementById('minScore').value);
            const maxScore = parseInt(document.getElementById('maxScore').value);
            
            if (minScore > maxScore) {
                showError('Điểm tối thiểu không được lớn hơn điểm tối đa');
                return;
            }
            
            const formData = new FormData(form);
            const criteriaId = document.getElementById('criteriaId').value;
            formData.append('action', criteriaId ? 'update_criteria' : 'create_criteria');
            const url = '/api/manage_criteria.php';
            
            try {
                const response = await fetch(url, {
                    method: 'POST',
                    body: formData
                });
                const result = await response.json();
                
                if (result.success) {
                    showSuccess(result.message);
                    criteriaModal.hide();
                    await loadCriteria(selectedGroupId);
                    await loadGroups(); // Reload to update count
                } else {
                    showError(result.message);
                }
            } catch (error) {
                console.error('Error saving criteria:', error);
                showError('Lỗi khi lưu tiêu chí');
            }
        }

        // Delete criteria
        async function deleteCriteria(criteriaId, criteriaName) {
            if (!confirm(`Bạn có chắc chắn muốn xóa tiêu chí "${criteriaName}"?`)) {
                return;
            }
            
            try {
                const response = await fetch('/api/manage_criteria.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ action: 'delete_criteria', tc_id: criteriaId })
                });
                const result = await response.json();
                
                if (result.success) {
                    showSuccess(result.message);
                    await loadCriteria(selectedGroupId);
                    await loadGroups(); // Reload to update count
                } else {
                    showError(result.message);
                }
            } catch (error) {
                console.error('Error deleting criteria:', error);
                showError('Lỗi khi xóa tiêu chí');
            }
        }

        // Helper functions
        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        function showSuccess(message) {
            const toastEl = document.getElementById('successToast');
            const messageEl = document.getElementById('successToastMessage');
            messageEl.textContent = message;
            const toast = new bootstrap.Toast(toastEl, {
                autohide: true,
                delay: 3000
            });
            toast.show();
        }

        function showError(message) {
            const toastEl = document.getElementById('errorToast');
            const messageEl = document.getElementById('errorToastMessage');
            messageEl.textContent = message;
            const toast = new bootstrap.Toast(toastEl, {
                autohide: true,
                delay: 4000
            });
            toast.show();
        }
    </script>
</body>

</html>