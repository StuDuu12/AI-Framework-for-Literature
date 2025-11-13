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
    // bring in DB and helper to get current class id
    require_once __DIR__ . '/../../config/db.php';
    require_once __DIR__ . '/../../config/helper_class.php';
    require_once './views/templates/sidebar.php';
    ?>

    <main id="main" class="main">
        <div class="pagetitle">
            <h1>Danh sách thành viên lớp học</h1>
            <nav>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="index.html"><?= $_SESSION['current_class_name'] ?></a></li>
                    <li class="breadcrumb-item active">Danh sách thành viên lớp học</li>
                </ol>
            </nav>
        </div><!-- End Page Title -->

        <section class="section dashboard">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between mb-3">
                        <h5 class="card-title">Thành viên</h5>
                        <?php if (isset($_SESSION['user']['role']) && $_SESSION['user']['role'] === 'teacher'): ?>
                            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addMemberModal">
                                <i class="bi bi-person-plus"></i> Thêm thành viên
                            </button>
                        <?php endif; ?>
                    </div>

                    <?php
                    // determine class id
                    $classId = function_exists('getCurrentClassId') ? getCurrentClassId() : (isset($_SESSION['current_class_id']) ? $_SESSION['current_class_id'] : 1);

                    // fetch current class members using helper
                    $members = getClassMembers($classId);
                    ?>

                    <div class="table-responsive">
                        <table class="table table-striped datatable" id="membersTable">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Họ và tên</th>
                                    <th>Username</th>
                                    <th>Email</th>
                                    <th>Vai trò</th>
                                    <?php if (isset($_SESSION['user']['role']) && $_SESSION['user']['role'] === 'teacher'): ?>
                                        <th>Hành động</th>
                                    <?php endif; ?>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($members as $i => $m): ?>
                                    <tr data-user-id="<?= intval($m['user_id']) ?>">
                                        <td><?= $i + 1 ?></td>
                                        <td><?= htmlspecialchars($m['fullname']) ?></td>
                                        <td><?= htmlspecialchars($m['username']) ?></td>
                                        <td><?= htmlspecialchars($m['email']) ?></td>
                                        <td>
                                            <?php if (isset($_SESSION['user']['role']) && $_SESSION['user']['role'] === 'teacher'): ?>
                                                <select class="form-select form-select-sm role-selector"
                                                    data-user-id="<?= intval($m['user_id']) ?>" style="width: auto;">
                                                    <option value="student" <?= $m['role'] === 'student' ? 'selected' : '' ?>>Học
                                                        sinh</option>
                                                    <option value="teacher" <?= $m['role'] === 'teacher' ? 'selected' : '' ?>>Giáo
                                                        viên</option>
                                                </select>
                                            <?php else: ?>
                                                <span class="badge bg-<?= $m['role'] === 'teacher' ? 'info' : 'secondary' ?>">
                                                    <?= $m['role'] === 'teacher' ? 'Giáo viên' : 'Học sinh' ?>
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                        <?php if (isset($_SESSION['user']['role']) && $_SESSION['user']['role'] === 'teacher'): ?>
                                            <td>
                                                <button class="btn btn-sm btn-danger delete-member"
                                                    data-user-id="<?= intval($m['user_id']) ?>" title="Xóa">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </td>
                                        <?php endif; ?>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Add Member Modal -->
            <div class="modal fade" id="addMemberModal" tabindex="-1" aria-labelledby="addMemberModalLabel"
                aria-hidden="true">
                <div class="modal-dialog modal-lg">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="addMemberModalLabel">Thêm thành viên vào lớp</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <div class="mb-3">
                                <label class="form-label">Tìm kiếm người dùng</label>
                                <input id="memberSearch" class="form-control"
                                    placeholder="Nhập tên, username hoặc email để tìm kiếm...">
                                <small class="text-muted">Nhập ít nhất 2 ký tự để tìm kiếm</small>
                            </div>

                            <div id="searchResults" style="max-height:400px; overflow:auto;">
                                <p class="text-muted text-center">Nhập từ khóa để tìm kiếm người dùng</p>
                            </div>

                            <div id="selectedUsers" class="mt-3">
                                <h6>Đã chọn (<span id="selectedCount">0</span>):</h6>
                                <div id="selectedList" class="d-flex flex-wrap gap-2"></div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button>
                            <button type="button" class="btn btn-primary" id="addSelectedBtn" disabled>Thêm đã
                                chọn</button>
                        </div>
                    </div>
                </div>
            </div>

        </section>

    </main><!-- End #main -->
    <?php
    require_once 'views/templates/footer.php';
    require_once 'views/templates/vendors.php';
    ?>

    <script>
        // AJAX operations for class members
        (function () {
            const classId = <?= intval($classId) ?>;
            let searchTimeout = null;
            let selectedUsers = new Map(); // userId -> {fullname, username, email, role}

            // Search users with debounce
            const searchInput = document.getElementById('memberSearch');
            const searchResults = document.getElementById('searchResults');
            const selectedList = document.getElementById('selectedList');
            const selectedCount = document.getElementById('selectedCount');
            const addSelectedBtn = document.getElementById('addSelectedBtn');

            if (searchInput) {
                searchInput.addEventListener('input', function () {
                    const keyword = this.value.trim();

                    clearTimeout(searchTimeout);

                    if (keyword.length < 2) {
                        searchResults.innerHTML = '<p class="text-muted text-center">Nhập ít nhất 2 ký tự để tìm kiếm</p>';
                        return;
                    }

                    searchResults.innerHTML = '<p class="text-muted text-center"><span class="spinner-border spinner-border-sm"></span> Đang tìm kiếm...</p>';

                    searchTimeout = setTimeout(() => {
                        fetch('/api/add_members.php', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/x-www-form-urlencoded',
                            },
                            body: `action=search&keyword=${encodeURIComponent(keyword)}&class_id=${classId}`
                        })
                            .then(response => {
                                // Log raw response for debugging
                                return response.text().then(text => {
                                    try {
                                        return JSON.parse(text);
                                    } catch (e) {
                                        console.error('Invalid JSON response:', text);
                                        throw new Error('Server returned invalid JSON: ' + text.substring(0, 100));
                                    }
                                });
                            })
                            .then(data => {
                                if (data.success && data.users.length > 0) {
                                    let html = '<div class="list-group">';
                                    data.users.forEach(user => {
                                        const isSelected = selectedUsers.has(user.user_id);
                                        html += `
                                    <div class="list-group-item ${isSelected ? 'bg-light' : ''}">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <div>
                                                <strong>${escapeHtml(user.fullname)}</strong> 
                                                <small class="text-muted">(${escapeHtml(user.username)})</small>
                                                <br><small>${escapeHtml(user.email)}</small>
                                            </div>
                                            <div class="d-flex gap-2 align-items-center">
                                                <select class="form-select form-select-sm" id="role-${user.user_id}" style="width: 120px;" ${isSelected ? 'disabled' : ''}>
                                                    <option value="student">Học sinh</option>
                                                    <option value="teacher">Giáo viên</option>
                                                </select>
                                                <button class="btn btn-sm btn-${isSelected ? 'secondary' : 'primary'} add-user-btn" 
                                                        data-user-id="${user.user_id}" 
                                                        data-fullname="${escapeHtml(user.fullname)}"
                                                        data-username="${escapeHtml(user.username)}"
                                                        data-email="${escapeHtml(user.email)}"
                                                        ${isSelected ? 'disabled' : ''}>
                                                    ${isSelected ? 'Đã chọn' : 'Chọn'}
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                `;
                                    });
                                    html += '</div>';
                                    searchResults.innerHTML = html;

                                    // Attach click handlers
                                    document.querySelectorAll('.add-user-btn').forEach(btn => {
                                        btn.addEventListener('click', handleAddUser);
                                    });
                                } else {
                                    searchResults.innerHTML = '<p class="text-muted text-center">Không tìm thấy người dùng nào</p>';
                                }
                            })
                            .catch(error => {
                                console.error('Error:', error);
                                searchResults.innerHTML = '<p class="text-danger text-center">Lỗi khi tìm kiếm</p>';
                            });
                    }, 500);
                });
            }

            // Add user to selected list
            function handleAddUser(e) {
                const btn = e.currentTarget;
                const userId = parseInt(btn.dataset.userId);
                const roleSelect = document.getElementById(`role-${userId}`);
                const role = roleSelect ? roleSelect.value : 'student';

                selectedUsers.set(userId, {
                    fullname: btn.dataset.fullname,
                    username: btn.dataset.username,
                    email: btn.dataset.email,
                    role: role
                });

                updateSelectedList();
                btn.disabled = true;
                btn.textContent = 'Đã chọn';
                btn.classList.replace('btn-primary', 'btn-secondary');
                if (roleSelect) roleSelect.disabled = true;
            }

            // Update selected users display
            function updateSelectedList() {
                selectedCount.textContent = selectedUsers.size;
                addSelectedBtn.disabled = selectedUsers.size === 0;

                if (selectedUsers.size === 0) {
                    selectedList.innerHTML = '<span class="text-muted">Chưa chọn người dùng nào</span>';
                    return;
                }

                let html = '';
                selectedUsers.forEach((user, userId) => {
                    html += `
                    <span class="badge bg-primary p-2">
                        ${escapeHtml(user.fullname)} (${user.role === 'teacher' ? 'GV' : 'HS'})
                        <i class="bi bi-x-circle ms-1" style="cursor:pointer;" onclick="removeSelectedUser(${userId})"></i>
                    </span>
                `;
                });
                selectedList.innerHTML = html;
            }

            // Remove user from selected list (global function for onclick)
            window.removeSelectedUser = function (userId) {
                selectedUsers.delete(userId);
                updateSelectedList();

                // Re-enable button if visible
                const btn = document.querySelector(`.add-user-btn[data-user-id="${userId}"]`);
                if (btn) {
                    btn.disabled = false;
                    btn.textContent = 'Chọn';
                    btn.classList.replace('btn-secondary', 'btn-primary');
                    const roleSelect = document.getElementById(`role-${userId}`);
                    if (roleSelect) roleSelect.disabled = false;
                }
            };

            // Add selected users to class
            if (addSelectedBtn) {
                addSelectedBtn.addEventListener('click', function () {
                    if (selectedUsers.size === 0) return;

                    const userIds = [];
                    const roles = [];
                    selectedUsers.forEach((user, userId) => {
                        userIds.push(userId);
                        roles.push(user.role);
                    });

                    addSelectedBtn.disabled = true;
                    addSelectedBtn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Đang thêm...';

                    // Use FormData to send arrays properly
                    const formData = new FormData();
                    formData.append('action', 'add');
                    formData.append('class_id', classId);
                    userIds.forEach(id => formData.append('user_ids[]', id));
                    roles.forEach(role => formData.append('roles[]', role));

                    fetch('/api/add_members.php', {
                        method: 'POST',
                        body: formData
                    })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                // Reload page to show new members
                                location.reload();
                            } else {
                                alert('Lỗi: ' + (data.message || 'Không thể thêm thành viên'));
                                addSelectedBtn.disabled = false;
                                addSelectedBtn.textContent = 'Thêm đã chọn';
                            }
                        })
                        .catch(error => {
                            console.error('Error:', error);
                            alert('Lỗi khi thêm thành viên');
                            addSelectedBtn.disabled = false;
                            addSelectedBtn.textContent = 'Thêm đã chọn';
                        });
                });
            }

            // Update member role
            document.querySelectorAll('.role-selector').forEach(select => {
                select.addEventListener('change', function () {
                    const userId = parseInt(this.dataset.userId);
                    const newRole = this.value;
                    const originalValue = this.querySelector('option[selected]')?.value || 'student';

                    if (!confirm(`Xác nhận thay đổi vai trò thành viên này thành "${newRole === 'teacher' ? 'Giáo viên' : 'Học sinh'}"?`)) {
                        this.value = originalValue;
                        return;
                    }

                    this.disabled = true;

                    fetch('/api/add_members.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: `action=update_role&class_id=${classId}&user_id=${userId}&role=${newRole}`
                    })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                location.reload();
                            } else {
                                alert('Lỗi: ' + (data.message || 'Không thể cập nhật vai trò'));
                                this.value = originalValue;
                                this.disabled = false;
                            }
                        })
                        .catch(error => {
                            console.error('Error:', error);
                            alert('Lỗi khi cập nhật vai trò');
                            this.value = originalValue;
                            this.disabled = false;
                        });
                });
            });

            // Delete member
            document.querySelectorAll('.delete-member').forEach(btn => {
                btn.addEventListener('click', function () {
                    const userId = parseInt(this.dataset.userId);
                    const row = this.closest('tr');
                    const userName = row.querySelector('td:nth-child(2)').textContent;

                    if (!confirm(`Xác nhận xóa "${userName}" khỏi lớp học?`)) {
                        return;
                    }

                    this.disabled = true;
                    this.innerHTML = '<span class="spinner-border spinner-border-sm"></span>';

                    fetch('/api/add_members.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: `action=delete&class_id=${classId}&user_id=${userId}`
                    })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                row.remove();
                                // Renumber rows
                                document.querySelectorAll('#membersTable tbody tr').forEach((tr, idx) => {
                                    tr.querySelector('td:first-child').textContent = idx + 1;
                                });
                            } else {
                                alert('Lỗi: ' + (data.message || 'Không thể xóa thành viên'));
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
                });
            });

            // Reset modal on close
            const modal = document.getElementById('addMemberModal');
            if (modal) {
                modal.addEventListener('hidden.bs.modal', function () {
                    searchInput.value = '';
                    searchResults.innerHTML = '<p class="text-muted text-center">Nhập từ khóa để tìm kiếm người dùng</p>';
                    selectedUsers.clear();
                    updateSelectedList();
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