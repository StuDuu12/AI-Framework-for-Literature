<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php require_once __DIR__ . '/../templates/assets.php'; ?>
    <title>Quản lý nhóm</title>
    <style>
        .group-item-wrapper {
            transition: all 0.2s ease;
            border-radius: 0.375rem;
        }

        .group-item-wrapper:hover {
            background-color: rgba(13, 110, 253, 0.1);
        }

        .group-item-wrapper.active {
            background-color: #0d6efd !important;
            color: white;
        }

        .group-item-wrapper.active strong,
        .group-item-wrapper.active small {
            color: white !important;
        }

        .group-item-wrapper:has(.group-checkbox:checked) {
            background-color: #fff5f5;
            border-left: 3px solid #dc3545;
        }

        .group-item-wrapper:has(.group-checkbox:checked).active {
            background-color: #0d6efd !important;
            border-left: 3px solid #0a58ca;
        }

        .group-checkbox {
            cursor: pointer;
            width: 18px;
            height: 18px;
        }

        tr.member-row {
            transition: all 0.2s ease;
        }

        tr.member-row:has(.member-checkbox:checked) {
            background-color: #fff5f5;
        }

        .member-checkbox {
            cursor: pointer;
            width: 18px;
            height: 18px;
        }
    </style>
</head>

<body>
    <?php
    require_once __DIR__ . '/../templates/header.php';
    require_once __DIR__ . '/../../config/db.php';
    require_once __DIR__ . '/../../config/helper_class.php';
    require_once __DIR__ . '/../templates/sidebar.php';

    $classId = function_exists('getCurrentClassId') ? getCurrentClassId() : (isset($_SESSION['current_class_id']) ? $_SESSION['current_class_id'] : 1);
    $groups = getClassGroups($classId);
    ?>

    <main id="main" class="main">
        <div class="pagetitle">
            <h1>Quản lý nhóm</h1>
            <nav>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a
                            href="index.html"><?= $_SESSION['current_class_name'] ?? 'Lớp học' ?></a></li>
                    <li class="breadcrumb-item active">Quản lý nhóm</li>
                </ol>
            </nav>
        </div>

        <section class="section dashboard">
            <div class="row">
                <!-- Groups List -->
                <div class="col-lg-5">
                    <div class="card">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h5 class="card-title">Danh sách nhóm</h5>
                                <div class="d-flex gap-2">
                                    <?php if (isset($_SESSION['user']['role']) && $_SESSION['user']['role'] === 'teacher'): ?>
                                        <button type="button"
                                            id="deleteSelectedGroupsBtn"
                                            class="btn btn-sm btn-danger"
                                            style="display: none;">
                                            <i class="bi bi-trash"></i> Xóa đã chọn (<span id="selectedGroupCount">0</span>)
                                        </button>
                                        <button class="btn btn-primary btn-sm" data-bs-toggle="modal"
                                            data-bs-target="#createGroupModal">
                                            <i class="bi bi-plus-circle"></i> Tạo nhóm
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <div class="list-group" id="groupsList">
                                <?php if (empty($groups)): ?>
                                    <div class="list-group-item text-muted text-center">
                                        Chưa có nhóm nào
                                    </div>
                                <?php else: ?>
                                    <?php foreach ($groups as $group): ?>
                                        <div class="list-group-item group-item-wrapper" data-group-id="<?= intval($group['group_id']) ?>">
                                            <div class="d-flex align-items-center gap-2">
                                                <?php if (isset($_SESSION['user']['role']) && $_SESSION['user']['role'] === 'teacher'): ?>
                                                    <input class="form-check-input group-checkbox" 
                                                        type="checkbox"
                                                        id="group-<?= intval($group['group_id']) ?>"
                                                        data-group-id="<?= intval($group['group_id']) ?>"
                                                        onclick="event.stopPropagation();">
                                                <?php endif; ?>
                                                <div class="flex-grow-1 group-item" 
                                                    style="cursor: pointer;"
                                                    data-group-id="<?= intval($group['group_id']) ?>"
                                                    data-group-name="<?= htmlspecialchars($group['group_name']) ?>">
                                                    <strong><?= htmlspecialchars($group['group_name']) ?></strong>
                                                    <br><small class="text-muted"><?= intval($group['member_count']) ?> thành viên</small>
                                                </div>
                                                <?php if (isset($_SESSION['user']['role']) && $_SESSION['user']['role'] === 'teacher'): ?>
                                                    <button class="btn btn-sm btn-outline-secondary edit-group-btn"
                                                        data-group-id="<?= intval($group['group_id']) ?>"
                                                        data-group-name="<?= htmlspecialchars($group['group_name']) ?>"
                                                        onclick="event.stopPropagation();"
                                                        title="Chỉnh sửa nhóm">
                                                        <i class="bi bi-pencil"></i>
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

                <!-- Group Details -->
                <div class="col-lg-7">
                    <div class="card" id="groupDetailsCard" style="display: none;">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <div>
                                    <h5 class="card-title mb-0" id="groupDetailsTitle">Chi tiết nhóm</h5>
                                    <small class="text-muted" id="groupDetailsMemberCount"></small>
                                </div>
                                <div class="d-flex gap-2">
                                    <?php if (isset($_SESSION['user']['role']) && $_SESSION['user']['role'] === 'teacher'): ?>
                                        <button type="button"
                                            id="deleteSelectedMembersBtn"
                                            class="btn btn-sm btn-danger"
                                            style="display: none;">
                                            <i class="bi bi-trash"></i> Xóa đã chọn (<span id="selectedMemberCount">0</span>)
                                        </button>
                                        <button class="btn btn-primary btn-sm" id="addMemberToGroupBtn">
                                            <i class="bi bi-person-plus"></i> Thêm thành viên
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <div class="table-responsive">
                                <table class="table table-sm" id="groupMembersTable">
                                    <thead>
                                        <tr>
                                            <?php if (isset($_SESSION['user']['role']) && $_SESSION['user']['role'] === 'teacher'): ?>
                                                <th style="width: 30px;">
                                                    <input type="checkbox" class="form-check-input" id="selectAllMembers">
                                                </th>
                                            <?php endif; ?>
                                            <th>#</th>
                                            <th>Họ và tên</th>
                                            <th>Username</th>
                                            <th>Email</th>
                                            <?php if (isset($_SESSION['user']['role']) && $_SESSION['user']['role'] === 'teacher'): ?>
                                                <th>Hành động</th>
                                            <?php endif; ?>
                                        </tr>
                                    </thead>
                                    <tbody></tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <div class="card" id="noGroupSelected">
                        <div class="card-body text-center text-muted py-5">
                            <i class="bi bi-arrow-left" style="font-size: 3rem;"></i>
                            <p class="mt-3">Chọn một nhóm để xem chi tiết</p>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Create Group Modal -->
        <div class="modal fade" id="createGroupModal" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Tạo nhóm mới</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Tên nhóm</label>
                            <input type="text" class="form-control" id="newGroupName" placeholder="Nhập tên nhóm...">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                        <button type="button" class="btn btn-primary" id="createGroupBtn">Tạo nhóm</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Edit Group Modal -->
        <div class="modal fade" id="editGroupModal" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Chỉnh sửa nhóm</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" id="editGroupId">
                        <div class="mb-3">
                            <label class="form-label">Tên nhóm</label>
                            <input type="text" class="form-control" id="editGroupName" placeholder="Nhập tên nhóm...">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                        <button type="button" class="btn btn-primary" id="updateGroupBtn">Cập nhật</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Add Members Modal -->
        <div class="modal fade" id="addMembersModal" tabindex="-1">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Thêm thành viên vào nhóm</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Tìm kiếm thành viên</label>
                            <input type="text" class="form-control" id="searchMemberInput"
                                placeholder="Nhập tên, username hoặc email...">
                            <small class="text-muted">Nhập ít nhất 2 ký tự để tìm kiếm</small>
                        </div>

                        <div id="searchMemberResults" style="max-height: 400px; overflow: auto;">
                            <p class="text-muted text-center">Nhập từ khóa để tìm kiếm</p>
                        </div>

                        <div id="selectedMembersSection" class="mt-3">
                            <h6>Đã chọn (<span id="selectedMemberCount">0</span>):</h6>
                            <div id="selectedMembersList" class="d-flex flex-wrap gap-2"></div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button>
                        <button type="button" class="btn btn-primary" id="addSelectedMembersBtn" disabled>Thêm đã
                            chọn</button>
                    </div>
                </div>
            </div>
        </div>

    </main>
    <?php
    require_once __DIR__ . '/../templates/footer.php';
    require_once __DIR__ . '/../templates/vendors.php';
    ?>

    <script>
        (function () {
            const classId = <?= intval($classId) ?>;
            let currentGroupId = null;
            let selectedMembers = new Map();
            let searchTimeout = null;

            // Load group details
            function loadGroupDetails(groupId, groupName) {
                currentGroupId = groupId;

                fetch('/api/manage_groups.php', {
                    method: 'POST',
                    body: new URLSearchParams({
                        action: 'get_group_details',
                        class_id: classId,
                        group_id: groupId
                    })
                })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            document.getElementById('groupDetailsTitle').textContent = data.group.group_name;
                            document.getElementById('groupDetailsMemberCount').textContent =
                                data.group.members.length + ' thành viên';

                            const tbody = document.querySelector('#groupMembersTable tbody');
                            tbody.innerHTML = '';

                            if (data.group.members.length === 0) {
                                tbody.innerHTML = '<tr><td colspan="<?php echo isset($_SESSION['user']['role']) && $_SESSION['user']['role'] === 'teacher' ? '7' : '5'; ?>" class="text-center text-muted">Chưa có thành viên nào</td></tr>';
                            } else {
                                data.group.members.forEach((member, idx) => {
                                    const row = `
                                <tr class="member-row" data-user-id="${member.user_id}">
                                    <?php if (isset($_SESSION['user']['role']) && $_SESSION['user']['role'] === 'teacher'): ?>
                                    <td>
                                        <input class="form-check-input member-checkbox" 
                                            type="checkbox"
                                            data-user-id="${member.user_id}"
                                            data-user-name="${escapeHtml(member.fullname)}">
                                    </td>
                                    <?php endif; ?>
                                    <td>${idx + 1}</td>
                                    <td>${escapeHtml(member.fullname)}</td>
                                    <td>${escapeHtml(member.username)}</td>
                                    <td>${escapeHtml(member.email)}</td>
                                    <?php if (isset($_SESSION['user']['role']) && $_SESSION['user']['role'] === 'teacher'): ?>
                                    <td>
                                        <button class="btn btn-sm btn-danger remove-member-btn" 
                                                data-user-id="${member.user_id}" 
                                                data-user-name="${escapeHtml(member.fullname)}">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </td>
                                    <?php endif; ?>
                                </tr>
                            `;
                                    tbody.innerHTML += row;
                                });

                                // Attach remove handlers
                                document.querySelectorAll('.remove-member-btn').forEach(btn => {
                                    btn.addEventListener('click', handleRemoveMember);
                                });

                                // Attach checkbox handlers
                                document.querySelectorAll('.member-checkbox').forEach(checkbox => {
                                    checkbox.addEventListener('change', updateDeleteMembersButton);
                                });

                                // Reset select all checkbox
                                const selectAllMembers = document.getElementById('selectAllMembers');
                                if (selectAllMembers) {
                                    selectAllMembers.checked = false;
                                }

                                // Hide delete button
                                updateDeleteMembersButton();
                            }

                            document.getElementById('noGroupSelected').style.display = 'none';
                            document.getElementById('groupDetailsCard').style.display = 'block';

                            // Highlight selected group
                            document.querySelectorAll('.group-item-wrapper').forEach(item => {
                                item.classList.remove('active');
                            });
                            document.querySelector(`.group-item-wrapper[data-group-id="${groupId}"]`).classList.add('active');
                        } else {
                            alert('Lỗi: ' + data.message);
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        alert('Lỗi khi tải chi tiết nhóm');
                    });
            }

            // Group item click
            document.querySelectorAll('.group-item').forEach(item => {
                item.addEventListener('click', function (e) {
                    e.preventDefault();
                    const groupId = parseInt(this.dataset.groupId);
                    const groupName = this.dataset.groupName;
                    loadGroupDetails(groupId, groupName);
                });
            });

            // Create group
            const createGroupBtn = document.getElementById('createGroupBtn');
            if (createGroupBtn) {
                createGroupBtn.addEventListener('click', function () {
                    const groupName = document.getElementById('newGroupName').value.trim();

                    if (!groupName) {
                        alert('Vui lòng nhập tên nhóm');
                        return;
                    }

                    this.disabled = true;
                    this.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Đang tạo...';

                    fetch('/api/manage_groups.php', {
                        method: 'POST',
                        body: new URLSearchParams({
                            action: 'create_group',
                            class_id: classId,
                            group_name: groupName
                        })
                    })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                location.reload();
                            } else {
                                alert('Lỗi: ' + data.message);
                                this.disabled = false;
                                this.textContent = 'Tạo nhóm';
                            }
                        })
                        .catch(error => {
                            console.error('Error:', error);
                            alert('Lỗi khi tạo nhóm');
                            this.disabled = false;
                            this.textContent = 'Tạo nhóm';
                        });
                });
            }

            // Update delete button visibility
            function updateDeleteButton() {
                const checkedBoxes = document.querySelectorAll('.group-checkbox:checked');
                const deleteBtn = document.getElementById('deleteSelectedGroupsBtn');
                const countSpan = document.getElementById('selectedGroupCount');

                if (checkedBoxes.length > 0) {
                    deleteBtn.style.display = 'inline-block';
                    countSpan.textContent = checkedBoxes.length;
                } else {
                    deleteBtn.style.display = 'none';
                    countSpan.textContent = '0';
                }
            }

            // Handle group checkbox changes
            document.querySelectorAll('.group-checkbox').forEach(checkbox => {
                checkbox.addEventListener('change', function() {
                    updateDeleteButton();
                });
            });

            // Delete selected groups
            const deleteSelectedGroupsBtn = document.getElementById('deleteSelectedGroupsBtn');
            if (deleteSelectedGroupsBtn) {
                deleteSelectedGroupsBtn.addEventListener('click', async function() {
                    const checkedBoxes = document.querySelectorAll('.group-checkbox:checked');
                    const groupIds = Array.from(checkedBoxes).map(cb => parseInt(cb.dataset.groupId));

                    if (groupIds.length === 0) {
                        alert('Vui lòng chọn ít nhất một nhóm để xóa!');
                        return;
                    }

                    const count = groupIds.length;
                    if (!confirm(`Bạn có chắc chắn muốn xóa ${count} nhóm đã chọn?\n\nTất cả thành viên trong các nhóm này sẽ bị xóa khỏi nhóm.`)) {
                        return;
                    }

                    this.disabled = true;
                    this.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Đang xóa...';

                    let successCount = 0;
                    let failCount = 0;

                    try {
                        for (const groupId of groupIds) {
                            try {
                                const response = await fetch('/api/manage_groups.php', {
                                    method: 'POST',
                                    body: new URLSearchParams({
                                        action: 'delete_group',
                                        class_id: classId,
                                        group_id: groupId
                                    })
                                });

                                const data = await response.json();

                                if (data.success) {
                                    successCount++;
                                } else {
                                    failCount++;
                                }
                            } catch (error) {
                                console.error('Error deleting group:', groupId, error);
                                failCount++;
                            }
                        }

                        if (successCount > 0) {
                            alert(`Đã xóa thành công ${successCount} nhóm!`);
                        }
                        if (failCount > 0) {
                            alert(`Không thể xóa ${failCount} nhóm!`);
                        }

                        location.reload();

                    } catch (error) {
                        console.error('Error:', error);
                        alert('Có lỗi xảy ra khi xóa nhóm');
                        this.disabled = false;
                        this.innerHTML = '<i class="bi bi-trash"></i> Xóa đã chọn (<span id="selectedGroupCount">' + count + '</span>)';
                    }
                });
            }

            // Edit group button
            document.querySelectorAll('.edit-group-btn').forEach(btn => {
                btn.addEventListener('click', function (e) {
                    e.preventDefault();
                    e.stopPropagation();

                    const groupId = parseInt(this.dataset.groupId);
                    const groupName = this.dataset.groupName;

                    document.getElementById('editGroupId').value = groupId;
                    document.getElementById('editGroupName').value = groupName;

                    const modal = new bootstrap.Modal(document.getElementById('editGroupModal'));
                    modal.show();
                });
            });

            // Update group
            const updateGroupBtn = document.getElementById('updateGroupBtn');
            if (updateGroupBtn) {
                updateGroupBtn.addEventListener('click', function () {
                    const groupId = parseInt(document.getElementById('editGroupId').value);
                    const groupName = document.getElementById('editGroupName').value.trim();

                    if (!groupName) {
                        alert('Vui lòng nhập tên nhóm');
                        return;
                    }

                    this.disabled = true;
                    this.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Đang cập nhật...';

                    fetch('/api/manage_groups.php', {
                        method: 'POST',
                        body: new URLSearchParams({
                            action: 'update_group_name',
                            class_id: classId,
                            group_id: groupId,
                            group_name: groupName
                        })
                    })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                location.reload();
                            } else {
                                alert('Lỗi: ' + data.message);
                                this.disabled = false;
                                this.textContent = 'Cập nhật';
                            }
                        })
                        .catch(error => {
                            console.error('Error:', error);
                            alert('Lỗi khi cập nhật nhóm');
                            this.disabled = false;
                            this.textContent = 'Cập nhật';
                        });
                });
            }

            // Update delete members button visibility
            function updateDeleteMembersButton() {
                const checkedBoxes = document.querySelectorAll('.member-checkbox:checked');
                const deleteBtn = document.getElementById('deleteSelectedMembersBtn');
                const countSpan = document.getElementById('selectedMemberCount');

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

            // Select all members checkbox
            const selectAllMembers = document.getElementById('selectAllMembers');
            if (selectAllMembers) {
                selectAllMembers.addEventListener('change', function() {
                    const memberCheckboxes = document.querySelectorAll('.member-checkbox');
                    memberCheckboxes.forEach(checkbox => {
                        checkbox.checked = this.checked;
                    });
                    updateDeleteMembersButton();
                });
            }

            // Delete selected members
            const deleteSelectedMembersBtn = document.getElementById('deleteSelectedMembersBtn');
            if (deleteSelectedMembersBtn) {
                deleteSelectedMembersBtn.addEventListener('click', async function() {
                    const checkedBoxes = document.querySelectorAll('.member-checkbox:checked');
                    const userIds = Array.from(checkedBoxes).map(cb => parseInt(cb.dataset.userId));

                    if (userIds.length === 0) {
                        alert('Vui lòng chọn ít nhất một thành viên để xóa!');
                        return;
                    }

                    const count = userIds.length;
                    if (!confirm(`Bạn có chắc chắn muốn xóa ${count} thành viên đã chọn khỏi nhóm?`)) {
                        return;
                    }

                    this.disabled = true;
                    this.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Đang xóa...';

                    let successCount = 0;
                    let failCount = 0;

                    try {
                        for (const userId of userIds) {
                            try {
                                const response = await fetch('/api/manage_groups.php', {
                                    method: 'POST',
                                    body: new URLSearchParams({
                                        action: 'remove_member',
                                        class_id: classId,
                                        group_id: currentGroupId,
                                        user_id: userId
                                    })
                                });

                                const data = await response.json();

                                if (data.success) {
                                    successCount++;
                                } else {
                                    failCount++;
                                }
                            } catch (error) {
                                console.error('Error removing member:', userId, error);
                                failCount++;
                            }
                        }

                        if (successCount > 0) {
                            alert(`Đã xóa thành công ${successCount} thành viên!`);
                        }
                        if (failCount > 0) {
                            alert(`Không thể xóa ${failCount} thành viên!`);
                        }

                        // Reload group details
                        loadGroupDetails(currentGroupId, document.getElementById('groupDetailsTitle').textContent);

                    } catch (error) {
                        console.error('Error:', error);
                        alert('Có lỗi xảy ra khi xóa thành viên');
                        this.disabled = false;
                        this.innerHTML = '<i class="bi bi-trash"></i> Xóa đã chọn (<span id="selectedMemberCount">' + count + '</span>)';
                    }
                });
            }

            // Remove member from group
            function handleRemoveMember(e) {
                const userId = parseInt(this.dataset.userId);
                const userName = this.dataset.userName;
                const row = this.closest('tr');

                if (!confirm(`Xác nhận xóa "${userName}" khỏi nhóm?`)) {
                    return;
                }

                this.disabled = true;
                this.innerHTML = '<span class="spinner-border spinner-border-sm"></span>';

                fetch('/api/manage_groups.php', {
                    method: 'POST',
                    body: new URLSearchParams({
                        action: 'remove_member',
                        class_id: classId,
                        group_id: currentGroupId,
                        user_id: userId
                    })
                })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            row.remove();
                            const tbody = document.querySelector('#groupMembersTable tbody');
                            if (tbody.children.length === 0) {
                                tbody.innerHTML = '<tr><td colspan="5" class="text-center text-muted">Chưa có thành viên nào</td></tr>';
                            }
                            loadGroupDetails(currentGroupId, document.getElementById('groupDetailsTitle').textContent);
                        } else {
                            alert('Lỗi: ' + data.message);
                            this.disabled = false;
                            this.innerHTML = '<i class="bi bi-trash"></i>';
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        alert('Lỗi khi xóa thành viên');
                        this.disabled = false;
                        this.innerHTML = '<i class="bi bi-trash"></i>';
                    });
            }

            // Add members modal
            const addMemberToGroupBtn = document.getElementById('addMemberToGroupBtn');
            if (addMemberToGroupBtn) {
                addMemberToGroupBtn.addEventListener('click', function () {
                    if (!currentGroupId) {
                        alert('Vui lòng chọn nhóm trước');
                        return;
                    }

                    selectedMembers.clear();
                    updateSelectedMembersList();
                    document.getElementById('searchMemberInput').value = '';
                    document.getElementById('searchMemberResults').innerHTML = '<p class="text-muted text-center">Nhập từ khóa để tìm kiếm</p>';

                    const modal = new bootstrap.Modal(document.getElementById('addMembersModal'));
                    modal.show();
                });
            }

            // Search members
            const searchMemberInput = document.getElementById('searchMemberInput');
            if (searchMemberInput) {
                searchMemberInput.addEventListener('input', function () {
                    const keyword = this.value.trim();

                    clearTimeout(searchTimeout);

                    if (keyword.length < 2) {
                        document.getElementById('searchMemberResults').innerHTML = '<p class="text-muted text-center">Nhập ít nhất 2 ký tự để tìm kiếm</p>';
                        return;
                    }

                    document.getElementById('searchMemberResults').innerHTML = '<p class="text-muted text-center"><span class="spinner-border spinner-border-sm"></span> Đang tìm kiếm...</p>';

                    searchTimeout = setTimeout(() => {
                        fetch('/api/manage_groups.php', {
                            method: 'POST',
                            body: new URLSearchParams({
                                action: 'search_users',
                                class_id: classId,
                                group_id: currentGroupId,
                                keyword: keyword
                            })
                        })
                            .then(response => response.json())
                            .then(data => {
                                if (data.success && data.users.length > 0) {
                                    let html = '<div class="list-group">';
                                    data.users.forEach(user => {
                                        const isSelected = selectedMembers.has(user.user_id);
                                        html += `
                                    <div class="list-group-item ${isSelected ? 'bg-light' : ''}">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <div>
                                                <strong>${escapeHtml(user.fullname)}</strong> 
                                                <small class="text-muted">(${escapeHtml(user.username)})</small>
                                                <br><small>${escapeHtml(user.email)}</small>
                                            </div>
                                            <button class="btn btn-sm btn-${isSelected ? 'secondary' : 'primary'} select-member-btn" 
                                                    data-user-id="${user.user_id}" 
                                                    data-fullname="${escapeHtml(user.fullname)}"
                                                    data-username="${escapeHtml(user.username)}"
                                                    data-email="${escapeHtml(user.email)}"
                                                    ${isSelected ? 'disabled' : ''}>
                                                ${isSelected ? 'Đã chọn' : 'Chọn'}
                                            </button>
                                        </div>
                                    </div>
                                `;
                                    });
                                    html += '</div>';
                                    document.getElementById('searchMemberResults').innerHTML = html;

                                    document.querySelectorAll('.select-member-btn').forEach(btn => {
                                        btn.addEventListener('click', handleSelectMember);
                                    });
                                } else {
                                    document.getElementById('searchMemberResults').innerHTML = '<p class="text-muted text-center">Không tìm thấy thành viên nào</p>';
                                }
                            })
                            .catch(error => {
                                console.error('Error:', error);
                                document.getElementById('searchMemberResults').innerHTML = '<p class="text-danger text-center">Lỗi khi tìm kiếm</p>';
                            });
                    }, 500);
                });
            }

            // Select member
            function handleSelectMember(e) {
                const btn = e.currentTarget;
                const userId = parseInt(btn.dataset.userId);

                selectedMembers.set(userId, {
                    fullname: btn.dataset.fullname,
                    username: btn.dataset.username,
                    email: btn.dataset.email
                });

                updateSelectedMembersList();
                btn.disabled = true;
                btn.textContent = 'Đã chọn';
                btn.classList.replace('btn-primary', 'btn-secondary');
            }

            // Update selected members list
            function updateSelectedMembersList() {
                const count = document.getElementById('selectedMemberCount');
                const list = document.getElementById('selectedMembersList');
                const addBtn = document.getElementById('addSelectedMembersBtn');

                count.textContent = selectedMembers.size;
                addBtn.disabled = selectedMembers.size === 0;

                if (selectedMembers.size === 0) {
                    list.innerHTML = '<span class="text-muted">Chưa chọn thành viên nào</span>';
                    return;
                }

                let html = '';
                selectedMembers.forEach((member, userId) => {
                    html += `
                    <span class="badge bg-primary p-2">
                        ${escapeHtml(member.fullname)}
                        <i class="bi bi-x-circle ms-1" style="cursor:pointer;" onclick="unselectMember(${userId})"></i>
                    </span>
                `;
                });
                list.innerHTML = html;
            }

            // Unselect member
            window.unselectMember = function (userId) {
                selectedMembers.delete(userId);
                updateSelectedMembersList();

                const btn = document.querySelector(`.select-member-btn[data-user-id="${userId}"]`);
                if (btn) {
                    btn.disabled = false;
                    btn.textContent = 'Chọn';
                    btn.classList.replace('btn-secondary', 'btn-primary');
                }
            };

            // Add selected members
            const addSelectedMembersBtn = document.getElementById('addSelectedMembersBtn');
            if (addSelectedMembersBtn) {
                addSelectedMembersBtn.addEventListener('click', function () {
                    if (selectedMembers.size === 0) return;

                    const userIds = Array.from(selectedMembers.keys());

                    this.disabled = true;
                    this.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Đang thêm...';

                    const formData = new FormData();
                    formData.append('action', 'add_members');
                    formData.append('class_id', classId);
                    formData.append('group_id', currentGroupId);
                    userIds.forEach(id => formData.append('user_ids[]', id));

                    fetch('/api/manage_groups.php', {
                        method: 'POST',
                        body: formData
                    })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                bootstrap.Modal.getInstance(document.getElementById('addMembersModal')).hide();
                                loadGroupDetails(currentGroupId, document.getElementById('groupDetailsTitle').textContent);
                            } else {
                                alert('Lỗi: ' + data.message);
                                this.disabled = false;
                                this.textContent = 'Thêm đã chọn';
                            }
                        })
                        .catch(error => {
                            console.error('Error:', error);
                            alert('Lỗi khi thêm thành viên');
                            this.disabled = false;
                            this.textContent = 'Thêm đã chọn';
                        });
                });
            }

            // Helper function
            function escapeHtml(text) {
                const div = document.createElement('div');
                div.textContent = text;
                return div.innerHTML;
            }
        })();
    </script>
</body>

</html>