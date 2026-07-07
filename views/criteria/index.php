<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php require_once './views/templates/assets.php'; ?>
    <title data-lang-key="menu_manage_criteria">Manage Evaluation Criteria</title>
</head>

<body>
    <?php
    require_once './views/templates/header.php';
    require_once './views/templates/sidebar.php';

    if (($_SESSION['user']['role'] ?? '') !== 'teacher') {
        header('Location: ' . URL_ROOT . '/class');
        exit;
    }

    // Get class_id from session or URL
    $class_id = isset($_SESSION['current_class_id']) ? $_SESSION['current_class_id'] : (isset($_GET['class_id']) ? intval($_GET['class_id']) : null);
    ?>

    <main id="main" class="main">
        <div class="pagetitle">
            <h1><i class="bi bi-card-checklist"></i> <span data-lang-key="menu_manage_criteria">Manage Evaluation Criteria</span></h1>
            <nav>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="<?= URL_ROOT ?>/class">Home</a></li>
                    <li class="breadcrumb-item active">Evaluation Criteria</li>
                </ol>
            </nav>
        </div><!-- End Page Title -->

        <section class="section criteria-page">
            <div class="row">
                <!-- Left Column: Criteria Groups -->
                <div class="col-lg-4">
                    <div class="card">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center mb-3 mt-3">
                                <h5 class="card-title mb-0">Criteria Groups</h5>
                                <button class="btn btn-primary btn-sm" onclick="showAddGroupModal()">
                                    <i class="bi bi-plus-circle"></i> <span>Add Group</span>
                                </button>
                            </div>

                            <div id="groupsList" class="list-group">
                                <!-- Groups will be loaded here -->
                                <div class="text-center py-4">
                                    <div class="spinner-border text-primary" role="status">
                                        <span class="visually-hidden">Loading...</span>
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
                                    <h5 class="card-title mb-0" id="criteriaTitle">Criteria</h5>
                                    <small class="text-muted" id="groupDescription"></small>
                                </div>
                                <button class="btn btn-success btn-sm" id="addCriteriaBtn"
                                    onclick="showAddCriteriaModal()" disabled>
                                    <i class="bi bi-plus-circle"></i> <span>Add Criterion</span>
                                </button>
                            </div>

                            <div id="criteriaList">
                                <!-- Criteria will be loaded here -->
                                <div class="empty-state">
                                    <i class="bi bi-inbox"></i>
                                    <h5>No criteria</h5>
                                    <p>Please select a criteria group on the left or create a new one</p>
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
                        <h5 class="modal-title" id="groupModalTitle">Add Criteria Group</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <form id="groupForm">
                            <input type="hidden" id="groupId" name="nhom_tc_id">
                            <div class="mb-3">
                                <label for="groupName" class="form-label">Group Name <span
                                        class="text-hasnger">*</span></label>
                                <input type="text" class="form-control" id="groupName" name="ten_nhom_tc" required
                                    placeholder="Example: Content evaluation">
                            </div>
                            <div class="mb-3">
                                <label for="groupDescription" class="form-label">Description</label>
                                <textarea class="form-control" id="groupDescription" name="description" rows="3"
                                    placeholder="Detailed description of this criteria group"></textarea>
                            </div>
                        </form>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="button" class="btn btn-primary" onclick="saveGroup()">
                            <i class="bi bi-check-circle"></i> <span>Save</span>
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
                        <h5 class="modal-title" id="criteriaModalTitle">Add Criterion</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <form id="criteriaForm">
                            <input type="hidden" id="criteriaId" name="tc_id">
                            <input type="hidden" id="criteriaGroupId" name="nhom_tc_id">

                            <div class="mb-3">
                                <label for="criteriaName" class="form-label">Criterion Name <span
                                        class="text-hasnger">*</span></label>
                                <input type="text" class="form-control" id="criteriaName" name="name_tc" required
                                    placeholder="Example: Accuracy of content">
                            </div>

                            <div class="mb-3">
                                <label for="criteriaDescription" class="form-label">Description</label>
                                <textarea class="form-control" id="criteriaDescription" name="description" rows="2"
                                    placeholder="Detailed description of the criterion"></textarea>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="minScore" class="form-label">Min Score <span
                                                class="text-hasnger">*</span></label>
                                        <input type="number" class="form-control" id="minScore" name="min_score" min="0"
                                            step="1" value="0" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="maxScore" class="form-label">Max Score <span
                                                class="text-hasnger">*</span></label>
                                        <input type="number" class="form-control" id="maxScore" name="max_score" min="0"
                                            value="10" required>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="button" class="btn btn-success" onclick="saveCriteria()">
                            <i class="bi bi-check-circle"></i> <span>Save</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal: Rubric Levels -->
        <div class="modal fade" id="rubricLevelsModal" tabindex="-1">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="rubricLevelsTitle">Rubric Levels</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <p class="text-muted mb-3">
                            <i class="bi bi-info-circle"></i>
                            Define performance levels for this criterion (e.g., Excellent, Good, Fair, Poor).
                        </p>
                        <input type="hidden" id="rubricTcId">
                        <div id="rubricLevelsContainer">
                            <!-- Levels will be loaded here -->
                        </div>
                        <button type="button" class="btn btn-sm btn-outline-success mt-2" onclick="addRubricLevelRow()">
                            <i class="bi bi-plus"></i> Add Level
                        </button>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="button" class="btn btn-primary" onclick="saveRubricLevels()">
                            <i class="bi bi-save"></i> Save Levels
                        </button>
                    </div>
                </div>
            </div>
        </div>

    </main><!-- End #main -->

    <!-- Toast Container -->
    <div class="toast-container position-fixed top-0 end-0 p-3">
        <div id="successToast" class="toast align-items-center text-white bg-success border-0" role="alert"
            aria-live="assertive" aria-atomic="true">
            <div class="d-flex">
                <div class="toast-body">
                    <i class="bi bi-check-circle me-2"></i>
                    <span id="successToastMessage"></span>
                </div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"
                    aria-label="Close"></button>
            </div>
        </div>

        <div id="errorToast" class="toast align-items-center text-white bg-hasnger border-0" role="alert"
            aria-live="assertive" aria-atomic="true">
            <div class="d-flex">
                <div class="toast-body">
                    <i class="bi bi-exclamation-circle me-2"></i>
                    <span id="errorToastMessage"></span>
                </div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"
                    aria-label="Close"></button>
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
        let groupsCache = [];
        let criteriaCache = [];

        function tcLabel(raw) {
            if (typeof criteriaLabel === 'function') {
                return criteriaLabel(raw);
            }
            if (!raw) return '';
            const text = String(raw).trim();
            if (text.charAt(0) === '{') {
                try {
                    const decoded = JSON.parse(text);
                    if (decoded && typeof decoded === 'object') {
                        return decoded.vi || decoded.en || text;
                    }
                } catch (e) { /* ignore */ }
            }
            return text;
        }

        function showGroupsError(message) {
            document.getElementById('groupsList').innerHTML = `
                <div class="empty-state text-danger">
                    <i class="bi bi-exclamation-triangle"></i>
                    <p class="mb-0">${escapeHtml(message)}</p>
                    <button type="button" class="btn btn-sm btn-outline-primary mt-2" onclick="loadGroups()">Thử lại</button>
                </div>
            `;
        }

        document.addEventListener('DOMContentLoaded', function() {
            groupModal = new bootstrap.Modal(document.getElementById('groupModal'));
            criteriaModal = new bootstrap.Modal(document.getElementById('criteriaModal'));

            if (currentClassId) {
                loadGroups();
            } else {
                showGroupsError('Không tìm thấy lớp học. Vui lòng vào trang lớp trước hoặc thêm ?class_id=... vào URL.');
            }

            window.addEventListener('languageChanged', () => {
                if (currentClassId) {
                    loadGroups();
                    if (selectedGroupId) {
                        loadCriteria(selectedGroupId);
                    }
                }
            });
        });

        // Load groups
        async function loadGroups() {
            const container = document.getElementById('groupsList');
            container.innerHTML = `
                <div class="text-center py-4">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                </div>
            `;

            try {
                const response = await fetch(`/api/manage_criteria.php?action=get_groups&class_id=${currentClassId}`);
                if (!response.ok) {
                    throw new Error(`HTTP ${response.status}`);
                }

                const text = await response.text();
                let result;
                try {
                    result = JSON.parse(text);
                } catch (e) {
                    console.error('Invalid JSON:', text.slice(0, 300));
                    throw new Error('Invalid response from server');
                }

                if (result.success) {
                    groupsCache = result.data || [];
                    displayGroups(groupsCache);
                } else {
                    showGroupsError(result.message || 'Cannot load criteria groups');
                    showError(result.message || 'Cannot load criteria groups');
                }
            } catch (error) {
                console.error('Error loading groups:', error);
                showGroupsError('Error loading criteria groups');
                showError('Error loading criteria groups');
            }
        }

        // Display groups
        function displayGroups(groups) {
            const container = document.getElementById('groupsList');

            if (groups.length === 0) {
                container.innerHTML = `
                    <div class="empty-state">
                        <i class="bi bi-folder-x"></i>
                        <p class="mb-0">No criteria groups yet</p>
                        <small class="text-muted">Click "Add Group" to get started</small>
                    </div>
                `;
                return;
            }

            container.innerHTML = groups.map(group => `
                <div class="list-group-item list-group-item-action ${group.nhom_tc_id == selectedGroupId ? 'active' : ''}" 
                     data-group-id="${group.nhom_tc_id}"
                     onclick="selectGroupById(${group.nhom_tc_id}, this)">
                    <div class="d-flex justify-content-between align-items-center">
                        <div class="flex-grow-1">
                            <h6 class="mb-1">${escapeHtml(tcLabel(group.ten_nhom_tc))}</h6>
                            ${group.description ? `<small class="text-muted">${escapeHtml(tcLabel(group.description))}</small>` : ''}
                            <div class="mt-1">
                                <span class="badge bg-info">${group.criteria_count || 0} criteria</span>
                            </div>
                        </div>
                        <div class="btn-group group-actions" role="group">
                            <button class="btn btn-sm btn-outline-primary" onclick="event.stopPropagation(); editGroup(${group.nhom_tc_id})" title="Edit">
                                <i class="bi bi-pencil"></i>
                            </button>
                            <button class="btn btn-sm btn-outline-hasnger" onclick="event.stopPropagation(); deleteGroup(${group.nhom_tc_id})" title="Delete">
                                <i class="bi bi-trash"></i>
                            </button>
                        </div>
                    </div>
                </div>
            `).join('');
        }

        async function selectGroupById(groupId, el) {
            const group = groupsCache.find(g => Number(g.nhom_tc_id) === Number(groupId));
            if (!group) return;

            selectedGroupId = groupId;
            document.getElementById('criteriaTitle').textContent = tcLabel(group.ten_nhom_tc);
            document.getElementById('groupDescription').textContent = tcLabel(group.description || '');
            document.getElementById('addCriteriaBtn').disabled = false;

            document.querySelectorAll('#groupsList .list-group-item').forEach(item => {
                item.classList.remove('active');
            });
            if (el) {
                el.classList.add('active');
            }

            await loadCriteria(groupId);
        }

        // Load criteria
        async function loadCriteria(groupId) {
            try {
                const response = await fetch(`/api/manage_criteria.php?action=get_criteria&nhom_tc_id=${groupId}`);
                const result = await response.json();

                if (result.success) {
                    criteriaCache = result.data || [];
                    displayCriteria(criteriaCache);
                } else {
                    showError(result.message);
                }
            } catch (error) {
                console.error('Error loading criteria:', error);
                showError('Error loading criteria list');
            }
        }

        // Display criteria
        function displayCriteria(criteria) {
            const container = document.getElementById('criteriaList');

            if (criteria.length === 0) {
                container.innerHTML = `
                    <div class="empty-state">
                        <i class="bi bi-list-check"></i>
                        <h6>No criteria yet</h6>
                        <p class="mb-0 text-muted">Click "Add Criterion" to add new criteria</p>
                    </div>
                `;
                return;
            }

            container.innerHTML = `
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th width="30%">Criterion Name</th>
                                <th width="25%">Description</th>
                                <th width="12%" class="text-center">Score Range</th>
                                <th width="18%" class="text-center">Rubric Levels</th>
                                <th width="15%" class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            ${criteria.map(item => `
                                <tr class="criteria-item">
                                    <td><strong>${escapeHtml(tcLabel(item.name_tc))}</strong></td>
                                    <td><small class="text-muted">${escapeHtml(tcLabel(item.description || '') || 'No description')}</small></td>
                                    <td class="text-center">
                                        <span class="badge badge-score bg-success">${item.min_score} - ${item.max_score}</span>
                                    </td>
                                    <td class="text-center">
                                        <button class="btn btn-sm btn-outline-info" onclick="editRubricLevels(${item.tc_id}, '${escapeHtml(tcLabel(item.name_tc)).replace(/'/g, "\\'")}', ${item.min_score}, ${item.max_score})" title="Edit rubric levels">
                                            <i class="bi bi-list-ol"></i> Levels
                                        </button>
                                    </td>
                                    <td class="text-end">
                                        <button class="btn btn-sm btn-outline-primary" onclick="editCriteria(${item.tc_id})" title="Edit">
                                            <i class="bi bi-pencil"></i>
                                        </button>
                                        <button class="btn btn-sm btn-outline-hasnger" onclick="deleteCriteria(${item.tc_id})" title="Delete">
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
            document.getElementById('groupModalTitle').textContent = 'Add Criteria Group';
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
                    document.getElementById('groupModalTitle').textContent = 'Edit Criteria Groups';
                    document.getElementById('groupId').value = group.nhom_tc_id;
                    document.getElementById('groupName').value = group.ten_nhom_tc;
                    document.getElementById('groupDescription').value = group.description || '';
                    groupModal.show();
                }
            } catch (error) {
                console.error('Error loading group:', error);
                showError('Error loading group info');
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
                showError('Error saving criteria group');
            }
        }

        // Delete group
        async function deleteGroup(groupId) {
            const group = groupsCache.find(g => Number(g.nhom_tc_id) === Number(groupId));
            const groupName = group ? tcLabel(group.ten_nhom_tc) : 'this group';
            if (!confirm(`Are you sure you want to delete the group "${groupName}"?\n\nNote: Must delete all criteria in the group before deleting the group.`)) {
                return;
            }

            try {
                const response = await fetch('/api/manage_criteria.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        action: 'delete_group',
                        nhom_tc_id: groupId
                    })
                });
                const result = await response.json();

                if (result.success) {
                    showSuccess(result.message);
                    if (selectedGroupId == groupId) {
                        selectedGroupId = null;
                        document.getElementById('criteriaList').innerHTML = `
                            <div class="empty-state">
                                <i class="bi bi-inbox"></i>
                                <p>Please select a criteria group</p>
                            </div>
                        `;
                    }
                    await loadGroups();
                } else {
                    showError(result.message);
                }
            } catch (error) {
                console.error('Error deleting group:', error);
                showError('Error deleting criteria group');
            }
        }

        // Show add criteria modal
        function showAddCriteriaModal() {
            if (!selectedGroupId) {
                showError('Please select a criteria group first');
                return;
            }

            document.getElementById('criteriaModalTitle').textContent = 'Add Criterion';
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
                    document.getElementById('criteriaModalTitle').textContent = 'Edit Criteria';
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
                showError('Error loading criteria info');
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
                showError('Min Score cannot be greater than max score');
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
                showError('Error saving criteria');
            }
        }

        // Delete criteria
        async function deleteCriteria(criteriaId) {
            const item = criteriaCache.find(c => Number(c.tc_id) === Number(criteriaId));
            const criteriaName = item ? tcLabel(item.name_tc) : 'this criterion';
            if (!confirm(`Are you sure you want to delete the criterion "${criteriaName}"?`)) {
                return;
            }

            try {
                const response = await fetch('/api/manage_criteria.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        action: 'delete_criteria',
                        tc_id: criteriaId
                    })
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
                showError('Error deleting criteria');
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

        // Rubric Levels
        let rubricLevelsModal;
        let currentRubricTcId = 0;

        async function editRubricLevels(tcId, name, minScore, maxScore) {
            currentRubricTcId = tcId;
            document.getElementById('rubricTcId').value = tcId;
            document.getElementById('rubricLevelsTitle').textContent = 'Rubric Levels: ' + name;

            const container = document.getElementById('rubricLevelsContainer');
            container.innerHTML = '<div class="text-center py-3"><div class="spinner-border spinner-border-sm text-primary"></div> Loading...</div>';

            rubricLevelsModal = new bootstrap.Modal(document.getElementById('rubricLevelsModal'));
            rubricLevelsModal.show();

            try {
                const resp = await fetch(`/api/manage_rubric_levels.php?action=get_levels&tc_id=${tcId}`);
                const data = await resp.json();

                if (data.success && data.levels.length > 0) {
                    renderRubricLevels(data.levels);
                } else {
                    // Create default levels based on score range
                    const range = maxScore - minScore;
                    const step = Math.max(1, Math.floor(range / 3));
                    const defaults = [
                        { level_name: 'Excellent', score: maxScore, description: '' },
                        { level_name: 'Good', score: maxScore - step, description: '' },
                        { level_name: 'Fair', score: maxScore - step * 2, description: '' },
                        { level_name: 'Poor', score: minScore, description: '' },
                    ];
                    renderRubricLevels(defaults);
                }
            } catch (e) {
                container.innerHTML = '<div class="text-danger">Error loading levels</div>';
            }
        }

        function renderRubricLevels(levels) {
            const container = document.getElementById('rubricLevelsContainer');
            container.innerHTML = levels.map((lvl, i) => `
                <div class="row mb-2 rubric-level-row align-items-center">
                    <div class="col-md-3">
                        <input type="text" class="form-control form-control-sm" placeholder="Level name"
                            value="${escapeHtml(lvl.level_name || '')}" data-field="level_name">
                    </div>
                    <div class="col-md-2">
                        <input type="number" class="form-control form-control-sm" placeholder="Score"
                            value="${lvl.score ?? 0}" data-field="score" min="0">
                    </div>
                    <div class="col-md-6">
                        <input type="text" class="form-control form-control-sm" placeholder="Description (optional)"
                            value="${escapeHtml(lvl.description || '')}" data-field="description">
                    </div>
                    <div class="col-md-1">
                        <button class="btn btn-sm btn-outline-danger" onclick="this.closest('.rubric-level-row').remove()" title="Remove">
                            <i class="bi bi-x"></i>
                        </button>
                    </div>
                </div>
            `).join('');
        }

        function addRubricLevelRow() {
            const container = document.getElementById('rubricLevelsContainer');
            const row = document.createElement('div');
            row.className = 'row mb-2 rubric-level-row align-items-center';
            row.innerHTML = `
                <div class="col-md-3">
                    <input type="text" class="form-control form-control-sm" placeholder="Level name" data-field="level_name">
                </div>
                <div class="col-md-2">
                    <input type="number" class="form-control form-control-sm" placeholder="Score" data-field="score" min="0" value="0">
                </div>
                <div class="col-md-6">
                    <input type="text" class="form-control form-control-sm" placeholder="Description (optional)" data-field="description">
                </div>
                <div class="col-md-1">
                    <button class="btn btn-sm btn-outline-danger" onclick="this.closest('.rubric-level-row').remove()" title="Remove">
                        <i class="bi bi-x"></i>
                    </button>
                </div>
            `;
            container.appendChild(row);
        }

        async function saveRubricLevels() {
            const tcId = currentRubricTcId;
            const rows = document.querySelectorAll('#rubricLevelsContainer .rubric-level-row');
            const levels = [];

            rows.forEach(row => {
                const name = row.querySelector('[data-field="level_name"]').value.trim();
                const score = parseInt(row.querySelector('[data-field="score"]').value) || 0;
                const desc = row.querySelector('[data-field="description"]').value.trim();
                if (name) {
                    levels.push({ level_name: name, score, description: desc });
                }
            });

            if (levels.length === 0) {
                alert('Vui lòng thêm ít nhất 1 level');
                return;
            }

            try {
                const resp = await fetch('/api/manage_rubric_levels.php?action=save_levels', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ tc_id: tcId, levels })
                });
                const data = await resp.json();

                if (data.success) {
                    rubricLevelsModal.hide();
                    showSuccess('Đã lưu rubric levels');
                } else {
                    alert(data.message || 'Lỗi lưu');
                }
            } catch (e) {
                alert('Lỗi kết nối');
            }
        }
    </script>
</body>

</html>