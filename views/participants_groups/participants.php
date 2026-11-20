<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php require_once __DIR__ . '/../templates/assets.php'; ?>
    <title data-lang-key="class_member_list">Class Members - Literature System</title>
</head>

<body>
    <?php
    require_once __DIR__ . '/../templates/header.php';
    // bring in DB and helper to get current class id
    require_once __DIR__ . '/../../config/db.php';
    require_once __DIR__ . '/../../config/helper_class.php';
    require_once __DIR__ . '/../../config/helper_breadcrumb.php';
    require_once __DIR__ . '/../templates/sidebar.php';
    ?>

    <main id="main" class="main">
        <div class="pagetitle">
            <h1 data-lang-key="class_member_list">Class Members</h1>
            <nav>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="<?= breadcrumbClassUrl() ?>"><?= breadcrumbClassLabel() ?></a></li>
                    <li class="breadcrumb-item active" data-lang-key="class_member_list">Class Members</li>
                </ol>
            </nav>
        </div><!-- End Page Title -->

        <section class="section dashboard participants-page">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between mb-3">
                        <h5 class="card-title" data-lang-key="members">Thành viên</h5>
                        <?php if (isset($_SESSION['user']['role']) && $_SESSION['user']['role'] === 'teacher'): ?>
                            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addMemberModal">
                                <i class="bi bi-person-plus"></i> <span data-lang-key="add_member">Add Member</span>
                            </button>
                        <?php endif; ?>
                    </div>

                    <?php
                    // determine class id
                    $classId = getCurrentClassId();
                    if (!$classId) {
                        echo '<div class="alert alert-warning"><i class="bi bi-exclamation-triangle me-2"></i>Please select a class from <a href="' . URL_ROOT . '/dashboard">Class List</a> first.</div>';
                        $members = [];
                    } else {
                        $members = getClassMembers($classId);
                    }
                    ?>

                    <div class="table-responsive">
                        <table class="table table-striped datatable" id="membersTable">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Full Name</th>
                                    <th>Tên đăng nhập</th>
                                    <th>Email</th>
                                    <th>Vai trò</th>
                                    <?php if (isset($_SESSION['user']['role']) && $_SESSION['user']['role'] === 'teacher'): ?>
                                        <th>Thao tác</th>
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
                                                <select aria-label="Vai trò" class="form-select form-select-sm role-selector"
                                                    data-user-id="<?= intval($m['user_id']) ?>" style="width: auto;">
                                                    <option value="student" <?= $m['role'] === 'student' ? 'selected' : '' ?>>Học sinh</option>
                                                    <option value="teacher" <?= $m['role'] === 'teacher' ? 'selected' : '' ?>>Giáo viên</option>
                                                </select>
                                            <?php else: ?>
                                                <span class="badge bg-<?= $m['role'] === 'teacher' ? 'info' : 'secondary' ?>">
                                                    <?= $m['role'] === 'teacher' ? 'Teacher' : 'Student' ?>
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                        <?php if (isset($_SESSION['user']['role']) && $_SESSION['user']['role'] === 'teacher'): ?>
                                            <td>
                                                <button class="btn btn-sm btn-danger delete-member"
                                                    data-user-id="<?= intval($m['user_id']) ?>" title="Xoá">
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
                            <h5 class="modal-title" id="addMemberModalLabel" data-lang-key="add_member_to_class">Thêm thành viên vào lớp</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Đóng"></button>
                        </div>
                        <div class="modal-body">
                            <div class="mb-3">
                                <label for="memberSearch" class="form-label">Search User</label>
                                <input id="memberSearch" class="form-control"
                                    placeholder="Nhập tên, tên đăng nhập hoặc email để tìm…">
                                <small class="text-muted">Enter at least 2 characters to search</small>
                            </div>

                            <div id="searchResults" style="max-height:400px; overflow:auto;">
                                <p class="text-muted text-center">Nhập từ khoá để tìm người dùng</p>
                            </div>

                            <div id="selectedUsers" class="mt-3">
                                <h6>Selected (<span id="selectedCount">0</span>):</h6>
                                <div id="selectedList" class="d-flex flex-wrap gap-2"></div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" data-lang-key="close">Đóng</button>
                            <button type="button" class="btn btn-primary" id="addSelectedBtn" disabled data-lang-key="add_selected">Add Selected</button>
                        </div>
                    </div>
                </div>
            </div>

        </section>

        <?php if (isset($_SESSION['user']['role']) && $_SESSION['user']['role'] === 'teacher' && $classId): ?>
        <section class="section">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title"><i class="bi bi-key me-2"></i>Mã mời lớp học</h5>
                    <p class="text-muted small mb-3">Tạo mã mời để học sinh tự tham gia lớp học</p>

                    <div class="d-flex align-items-center gap-3 mb-3">
                        <div class="input-group" style="max-width: 250px;">
                            <span class="input-group-text">Hết hạn sau</span>
                            <select aria-label="Thời hạn" class="form-select" id="expiresDays" style="max-width: 120px;">
                                <option value="3">3 ngày</option>
                                <option value="7" selected>7 ngày</option>
                                <option value="14">14 ngày</option>
                                <option value="30">30 ngày</option>
                                <option value="0">Không hết hạn</option>
                            </select>
                        </div>
                        <button class="btn btn-primary btn-sm" id="btnCreateCode" onclick="createInviteCode()">
                            <i class="bi bi-plus-lg me-1"></i> Tạo mã mời
                        </button>
                    </div>

                    <div id="inviteMsg" class="alert" style="display:none;"></div>

                    <div id="inviteCodesList"></div>
                </div>
            </div>
        </section>
        <script>
        const INVITE_API = '<?= URL_ROOT ?>/api/class_invitation.php';
        const CLASS_ID = <?= (int)$classId ?>;
        const CSRF_TOKEN = '<?= csrf_token() ?>';

        async function loadInviteCodes() {
            try {
                const res = await fetch(INVITE_API + '?action=list_codes&class_id=' + CLASS_ID, { credentials: 'same-origin' });
                const data = await res.json();
                if (!data.success) return;
                const list = document.getElementById('inviteCodesList');
                if (!data.codes.length) {
                    list.innerHTML = '<p class="text-muted small">Chưa có mã mời nào</p>';
                    return;
                }
                list.innerHTML = data.codes.map(c => {
                    const isActive = c.active && (!c.expires_at || new Date(c.expires_at) > new Date());
                    const expires = c.expires_at ? new Date(c.expires_at).toLocaleDateString('vi-VN') : 'Không hết hạn';
                    const usage = c.max_uses ? c.use_count + '/' + c.max_uses : c.use_count + '/∞';
                    return '<div class="d-flex align-items-center justify-content-between p-2 mb-2 ' + (isActive ? 'bg-light' : 'bg-light opacity-50') + '" style="border-radius:8px;">' +
                        '<div>' +
                            '<code class="fw-bold" style="font-size:1rem; letter-spacing:2px;">' + c.code + '</code>' +
                            '<span class="ms-2 small text-muted">Hết hạn: ' + expires + ' &middot; Lượt dùng: ' + usage + '</span>' +
                            (!isActive ? ' <span class="badge bg-secondary ms-1">Đã tắt</span>' : '') +
                        '</div>' +
                        '<div class="d-flex gap-1">' +
                            (isActive ? '<button class="btn btn-sm btn-outline-secondary" onclick="copyInvite(\'' + c.code + '\')" title="Sao chép liên kết"><i class="bi bi-clipboard"></i></button>' +
                            '<button class="btn btn-sm btn-outline-danger" onclick="deactivateCode(' + c.id + ')" title="Tắt mã"><i class="bi bi-x-lg"></i></button>' : '') +
                        '</div>' +
                    '</div>';
                }).join('');
            } catch(e) {}
        }

        async function createInviteCode() {
            const btn = document.getElementById('btnCreateCode');
            const msg = document.getElementById('inviteMsg');
            btn.disabled = true;
            msg.style.display = 'none';

            try {
                const res = await fetch(INVITE_API, {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': CSRF_TOKEN },
                    body: JSON.stringify({
                        action: 'create_code',
                        class_id: CLASS_ID,
                        expires_days: parseInt(document.getElementById('expiresDays').value)
                    })
                });
                const data = await res.json();
                if (data.success) {
                    msg.className = 'alert alert-success';
                    msg.textContent = 'Mã mời: ' + data.code + ' — Link: ' + data.invite_url;
                    msg.style.display = 'block';
                    loadInviteCodes();
                } else {
                    msg.className = 'alert alert-danger';
                    msg.textContent = data.message;
                    msg.style.display = 'block';
                }
            } catch(e) {
                msg.className = 'alert alert-danger';
                msg.textContent = 'Lỗi kết nối';
                msg.style.display = 'block';
            }
            btn.disabled = false;
        }

        function copyInvite(code) {
            const url = '<?= URL_ROOT ?>/join?code=' + code;
            navigator.clipboard.writeText(url).then(() => {
                UI.success('Đã sao chép link mời.');
            });
        }

        async function deactivateCode(id) {
            if (!await UI.confirm('Tắt mã mời này?')) return;
            try {
                const res = await fetch(INVITE_API, {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': CSRF_TOKEN },
                    body: JSON.stringify({ action: 'deactivate_code', code_id: id })
                });
                const data = await res.json();
                if (data.success) loadInviteCodes();
            } catch(e) {}
        }

        loadInviteCodes();
        </script>
        <?php endif; ?>

    </main><!-- End #main -->
    <?php
    require_once __DIR__ . '/../templates/footer.php';
    require_once __DIR__ . '/../templates/vendors.php';
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
                        searchResults.innerHTML = '<p class="text-muted text-center">Enter at least 2 characters to search</p>';
                        return;
                    }

                    searchResults.innerHTML = '<p class="text-muted text-center"><span class="spinner-border spinner-border-sm"></span> Searching...</p>';

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
                                                    <option value="student">Student</option>
                                                    <option value="teacher">Teacher</option>
                                                </select>
                                                <button class="btn btn-sm btn-${isSelected ? 'secondary' : 'primary'} add-user-btn" 
                                                        data-user-id="${user.user_id}" 
                                                        data-fullname="${escapeHtml(user.fullname)}"
                                                        data-username="${escapeHtml(user.username)}"
                                                        data-email="${escapeHtml(user.email)}"
                                                        ${isSelected ? 'disabled' : ''}>
                                                    ${isSelected ? 'Selected' : 'Select'}
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
                                    searchResults.innerHTML = '<p class="text-muted text-center">Không tìm thấy người dùng</p>';
                                }
                            })
                            .catch(error => {
                                console.error('Error:', error);
                                searchResults.innerHTML = '<p class="text-danger text-center">Không tìm được, vui lòng thử lại</p>';
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
                btn.textContent = 'Selected';
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
                        ${escapeHtml(user.fullname)} (${user.role === 'teacher' ? 'T' : 'S'})
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
                    btn.textContent = 'Select';
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
                    addSelectedBtn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Adding...';

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
                                UI.error(data.message || 'Không thêm được thành viên.');
                                addSelectedBtn.disabled = false;
                                addSelectedBtn.textContent = 'Thêm người đã chọn';
                            }
                        })
                        .catch(error => {
                            console.error('Error:', error);
                            UI.error('Không thêm được thành viên.');
                            addSelectedBtn.disabled = false;
                            addSelectedBtn.textContent = 'Thêm người đã chọn';
                        });
                });
            }

            // Update member role
            document.querySelectorAll('.role-selector').forEach(select => {
                select.addEventListener('change', async function () {
                    const userId = parseInt(this.dataset.userId);
                    const newRole = this.value;
                    const originalValue = this.querySelector('option[selected]')?.value || 'student';

                    if (!await UI.confirm({ title: 'Đổi vai trò?', message: `Đổi vai trò thành viên này thành "${newRole === 'teacher' ? 'Giáo viên' : 'Học sinh'}"?`, confirmText: 'Đổi vai trò' })) {
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
                                UI.error(data.message || 'Không cập nhật được vai trò.');
                                this.value = originalValue;
                                this.disabled = false;
                            }
                        })
                        .catch(error => {
                            console.error('Error:', error);
                            UI.error('Không cập nhật được vai trò.');
                            this.value = originalValue;
                            this.disabled = false;
                        });
                });
            });

            // Delete member
            document.querySelectorAll('.delete-member').forEach(btn => {
                btn.addEventListener('click', async function () {
                    const userId = parseInt(this.dataset.userId);
                    const row = this.closest('tr');
                    const userName = row.querySelector('td:nth-child(2)').textContent;

                    if (!await UI.confirm(`Gỡ "${userName}" khỏi lớp?`)) {
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
                                UI.error(data.message || 'Không xoá được thành viên.');
                                this.disabled = false;
                                this.innerHTML = '<i class="bi bi-trash"></i>';
                            }
                        })
                        .catch(error => {
                            console.error('Error:', error);
                            UI.error('Không xoá được thành viên.');
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
                    searchResults.innerHTML = '<p class="text-muted text-center">Nhập từ khoá để tìm người dùng</p>';
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
