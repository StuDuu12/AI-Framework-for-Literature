<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php require_once __DIR__ . '/../templates/assets.php'; ?>
    <title data-lang-key="manage_groups">Manage Groups - Literature System</title>
</head>

<body>
    <?php
    require_once __DIR__ . '/../templates/header.php';
    require_once __DIR__ . '/../../config/db.php';
    require_once __DIR__ . '/../../config/helper_class.php';
    require_once __DIR__ . '/../../config/helper_breadcrumb.php';
    require_once __DIR__ . '/../templates/sidebar.php';

    $classId = getCurrentClassId();
    if (!$classId) {
        echo '<main id="main" class="main"><div class="alert alert-warning m-3">';
        echo '<a href="' . URL_ROOT . '/dashboard">Please select a class from Class List first.</a></div></main>';
        require_once __DIR__ . '/../templates/footer.php';
        require_once __DIR__ . '/../templates/vendors.php';
        echo '</body></html>';
        exit;
    }
    $groups = getClassGroups($classId);
    ?>

    <main id="main" class="main">
        <div class="pagetitle">
            <h1 data-lang-key="manage_groups">Manage Groups</h1>
            <nav>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="<?= breadcrumbClassUrl() ?>"><?= breadcrumbClassLabel() ?></a></li>
                    <li class="breadcrumb-item active" data-lang-key="manage_groups">Manage Groups</li>
                </ol>
            </nav>
        </div>

        <section class="section dashboard groups-page">
            <div class="row">
                <!-- Groups List -->
                <div class="col-lg-5">
                    <div class="card">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h5 class="card-title" data-lang-key="group_list">Group List</h5>
                                <div class="d-flex gap-2">
                                    <?php if (isset($_SESSION['user']['role']) && $_SESSION['user']['role'] === 'teacher'): ?>
                                        <button type="button"
                                            id="deleteSelectedGroupsBtn"
                                            class="btn btn-sm btn-danger"
                                            style="display: none;">
                                            <i class="bi bi-trash"></i> <span data-lang-key="delete_selected">Delete Selected</span> (<span id="selectedGroupCount">0</span>)
                                        </button>
                                        <button class="btn btn-primary btn-sm" data-bs-toggle="modal"
                                            data-bs-target="#createGroupModal">
                                            <i class="bi bi-plus-circle"></i> <span data-lang-key="create_group">Create Group</span>
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <div class="list-group" id="groupsList">
                                <?php if (empty($groups)): ?>
                                    <div class="list-group-item text-muted text-center">
                                        No groups yet
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
                                                    <br><small class="text-muted"><?= intval($group['member_count']) ?> members</small>
                                                </div>
                                                <?php if (isset($_SESSION['user']['role']) && $_SESSION['user']['role'] === 'teacher'): ?>
                                                    <button class="btn btn-sm btn-outline-secondary edit-group-btn"
                                                        data-group-id="<?= intval($group['group_id']) ?>"
                                                        data-group-name="<?= htmlspecialchars($group['group_name']) ?>"
                                                        onclick="event.stopPropagation();"
                                                        title="Edit group">
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
                                    <h5 class="card-title mb-0" id="groupDetailsTitle" data-lang-key="group_details">Group Details</h5>
                                    <small class="text-muted" id="groupDetailsMemberCount"></small>
                                </div>
                                <div class="d-flex gap-2">
                                    <?php if (isset($_SESSION['user']['role']) && $_SESSION['user']['role'] === 'teacher'): ?>
                                        <button type="button"
                                            id="deleteSelectedMembersBtn"
                                            class="btn btn-sm btn-danger"
                                            style="display: none;">
                                            <i class="bi bi-trash"></i> Delete Selected (<span id="selectedMemberCount">0</span>)
                                        </button>
                                        <button class="btn btn-primary btn-sm" id="addMemberToGroupBtn">
                                            <i class="bi bi-person-plus"></i> <span data-lang-key="add_member">Add Member</span>
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
                                            <th>Full Name</th>
                                            <th>Username</th>
                                            <th>Email</th>
                                            <?php if (isset($_SESSION['user']['role']) && $_SESSION['user']['role'] === 'teacher'): ?>
                                                <th>Actions</th>
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
                            <p class="mt-3">Select a group to view details</p>
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
                        <h5 class="modal-title">Create New Group</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Group Name</label>
                            <input type="text" class="form-control" id="newGroupName" placeholder="Enter group name...">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="button" class="btn btn-primary" id="createGroupBtn">Create Group</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Edit Group Modal -->
        <div class="modal fade" id="editGroupModal" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Edit Group</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" id="editGroupId">
                        <div class="mb-3">
                            <label class="form-label">Group Name</label>
                            <input type="text" class="form-control" id="editGroupName" placeholder="Enter group name...">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="button" class="btn btn-primary" id="updateGroupBtn">Update</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Add Members Modal -->
        <div class="modal fade" id="addMembersModal" tabindex="-1">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Add Members to Group</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Search Members</label>
                            <input type="text" class="form-control" id="searchMemberInput"
                                placeholder="Enter name, username or email...">
                            <small class="text-muted">Enter at least 2 characters to search</small>
                        </div>

                        <div id="searchMemberResults" style="max-height: 400px; overflow: auto;">
                            <p class="text-muted text-center">Enter keywords to search</p>
                        </div>

                        <div id="selectedMembersSection" class="mt-3">
                            <h6><span>Selected</span> (<span id="selectedMemberCount">0</span>):</h6>
                            <div id="selectedMembersList" class="d-flex flex-wrap gap-2"></div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="button" class="btn btn-primary" id="addSelectedMembersBtn" disabled>Add Selected</button>
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
                                data.group.members.length + ' members';

                            const tbody = document.querySelector('#groupMembersTable tbody');
                            tbody.innerHTML = '';

                            if (data.group.members.length === 0) {
                                tbody.innerHTML = '<tr><td colspan="<?php echo isset($_SESSION['user']['role']) && $_SESSION['user']['role'] === 'teacher' ? '7' : '5'; ?>" class="text-center text-muted">No members yet</td></tr>';
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
                            alert('Error: ' + data.message);
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        alert('Error loading group details');
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
                        alert('Please enter a group name');
                        return;
                    }

                    this.disabled = true;
                    this.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Creating...';

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
                                alert('Error: ' + data.message);
                                this.disabled = false;
                                this.textContent = 'Create Group';
                            }
                        })
                        .catch(error => {
                            console.error('Error:', error);
                            alert('Error creating group');
                            this.disabled = false;
                            this.textContent = 'Create Group';
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
                        alert('Please select at least one group to delete!');
                        return;
                    }

                    const count = groupIds.length;
                    if (!confirm(`Are you sure you want to delete ${count} selected group(s)?\n\nAll members in these groups will be removed from the group.`)) {
                        return;
                    }

                    this.disabled = true;
                    this.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Deleting...';

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
                            alert(`Successfully deleted ${successCount} group(s)!`);
                        }
                        if (failCount > 0) {
                            alert(`Failed to delete ${failCount} group(s)!`);
                        }

                        location.reload();

                    } catch (error) {
                        console.error('Error:', error);
                        alert('An error occurred while deleting groups');
                        this.disabled = false;
                        this.innerHTML = '<i class="bi bi-trash"></i> Delete Selected (<span id="selectedGroupCount">' + count + '</span>)';
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
                        alert('Please enter a group name');
                        return;
                    }

                    this.disabled = true;
                    this.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Updating...';

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
                                alert('Error: ' + data.message);
                                this.disabled = false;
                                this.textContent = 'Update';
                            }
                        })
                        .catch(error => {
                            console.error('Error:', error);
                            alert('Error updating group');
                            this.disabled = false;
                            this.textContent = 'Update';
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
                        alert('Please select at least one member to delete!');
                        return;
                    }

                    const count = userIds.length;
                    if (!confirm(`Are you sure you want to remove ${count} selected member(s) from the group?`)) {
                        return;
                    }

                    this.disabled = true;
                    this.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Deleting...';

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
                            alert(`Successfully removed ${successCount} member(s)!`);
                        }
                        if (failCount > 0) {
                            alert(`Failed to remove ${failCount} member(s)!`);
                        }

                        // Reload group details
                        loadGroupDetails(currentGroupId, document.getElementById('groupDetailsTitle').textContent);

                    } catch (error) {
                        console.error('Error:', error);
                        alert('An error occurred while removing members');
                        this.disabled = false;
                        this.innerHTML = '<i class="bi bi-trash"></i> Delete Selected (<span id="selectedMemberCount">' + count + '</span>)';
                    }
                });
            }

            // Remove member from group
            function handleRemoveMember(e) {
                const userId = parseInt(this.dataset.userId);
                const userName = this.dataset.userName;
                const row = this.closest('tr');

                if (!confirm(`Confirm remove "${userName}" from the group?`)) {
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
                                tbody.innerHTML = '<tr><td colspan="5" class="text-center text-muted">No members yet</td></tr>';
                            }
                            loadGroupDetails(currentGroupId, document.getElementById('groupDetailsTitle').textContent);
                        } else {
                            alert('Error: ' + data.message);
                            this.disabled = false;
                            this.innerHTML = '<i class="bi bi-trash"></i>';
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        alert('Error removing member');
                        this.disabled = false;
                        this.innerHTML = '<i class="bi bi-trash"></i>';
                    });
            }

            // Add members modal
            const addMemberToGroupBtn = document.getElementById('addMemberToGroupBtn');
            if (addMemberToGroupBtn) {
                addMemberToGroupBtn.addEventListener('click', function () {
                    if (!currentGroupId) {
                        alert('Please select a group first');
                        return;
                    }

                    selectedMembers.clear();
                    updateSelectedMembersList();
                    document.getElementById('searchMemberInput').value = '';
                    document.getElementById('searchMemberResults').innerHTML = '<p class="text-muted text-center">Enter keywords to search</p>';

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
                        document.getElementById('searchMemberResults').innerHTML = '<p class="text-muted text-center">Enter at least 2 characters to search</p>';
                        return;
                    }

                    document.getElementById('searchMemberResults').innerHTML = '<p class="text-muted text-center"><span class="spinner-border spinner-border-sm"></span> Searching...</p>';

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
                                                ${isSelected ? 'Selected' : 'Select'}
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
                                    document.getElementById('searchMemberResults').innerHTML = '<p class="text-muted text-center">No members found</p>';
                                }
                            })
                            .catch(error => {
                                console.error('Error:', error);
                                document.getElementById('searchMemberResults').innerHTML = '<p class="text-danger text-center">Search error</p>';
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
                btn.textContent = 'Selected';
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
                    list.innerHTML = '<span class="text-muted">No members selected yet</span>';
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
                    btn.textContent = 'Select';
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
                    this.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Adding...';

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
                                alert('Error: ' + data.message);
                                this.disabled = false;
                                this.textContent = 'Add Selected';
                            }
                        })
                        .catch(error => {
                            console.error('Error:', error);
                            alert('Error adding members');
                            this.disabled = false;
                            this.textContent = 'Add Selected';
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
