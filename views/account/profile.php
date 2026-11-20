<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../../config/env.php';
require_once __DIR__ . '/../../config/db.php';

if (!isset($_SESSION['user']) || empty($_SESSION['user'])) {
    header("Location: " . URL_ROOT . "/login");
    exit();
}

$userId = $_SESSION['user']['user_id'];
$user = $_SESSION['user'];

$stmt = $pdo->prepare("SELECT user_id, fullname, username, email, role, created_at FROM users WHERE user_id = :id");
$stmt->execute([':id' => $userId]);
$profile = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$profile) {
    header("Location: " . URL_ROOT . "/login");
    exit();
}

$roleLabel = match($profile['role']) {
    'admin'   => 'Quản trị viên',
    'teacher' => 'Giáo viên',
    'student' => 'Học sinh',
    default   => 'Người dùng'
};

$joinedDate = date('d/m/Y', strtotime($profile['created_at']));
?>
<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="utf-8">
    <meta content="width=device-width, initial-scale=1.0" name="viewport">
    <title>Hồ sơ cá nhân - Literature System</title>
    <?php require_once __DIR__ . '/../templates/assets.php'; ?>
    <style>
        .profile-page { max-width: 900px; margin: 0 auto; }

        .profile-hero {
            background: linear-gradient(135deg, var(--lit-primary) 0%, #717ff5 100%);
            border-radius: 16px;
            padding: 2.5rem 2rem 6rem;
            color: #fff;
            position: relative;
            margin-bottom: 0;
        }
        .profile-hero h2 { margin: 0; font-weight: 700; font-size: 1.5rem; }
        .profile-hero p { margin: 0.25rem 0 0; opacity: 0.85; font-size: 0.9rem; }

        .profile-avatar-wrap {
            display: flex;
            justify-content: center;
            margin-top: -48px;
            position: relative;
            z-index: 2;
        }
        .profile-avatar {
            width: 96px;
            height: 96px;
            border-radius: 50%;
            background: var(--lit-surface);
            border: 4px solid #fff;
            box-shadow: 0 4px 20px rgba(0,0,0,0.12);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2.5rem;
            color: var(--lit-primary);
        }

        .profile-cards { display: grid; grid-template-columns: 1fr 1fr; gap: 1.25rem; margin-top: 1.5rem; }
        @media (max-width: 768px) { .profile-cards { grid-template-columns: 1fr; } }

        .profile-card {
            background: var(--lit-surface);
            border: 1px solid #e3e6f0;
            border-radius: 12px;
            padding: 1.5rem;
            box-shadow: 0 2px 8px rgba(0,0,0,0.04);
        }
        .profile-card h5 {
            font-size: 0.85rem;
            font-weight: 600;
            color: var(--lit-text-secondary);
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin: 0 0 1rem;
        }

        .info-row {
            display: flex;
            align-items: center;
            padding: 0.65rem 0;
            border-bottom: 1px solid #f1f3f5;
        }
        .info-row:last-child { border-bottom: none; }
        .info-row .info-icon {
            width: 36px;
            height: 36px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 0.85rem;
            flex-shrink: 0;
            font-size: 1rem;
        }
        .info-icon.bg-primary-subtle { background: rgba(var(--lit-primary-rgb), 0.1); color: var(--lit-primary); }
        .info-icon.bg-success-subtle { background: rgba(25,135,84,0.1); color: #198754; }
        .info-icon.bg-warning-subtle { background: rgba(255,193,7,0.15); color: #cc9a06; }
        .info-icon.bg-info-subtle    { background: rgba(13,202,240,0.12); color: #0aa2c0; }
        .info-icon.bg-danger-subtle  { background: rgba(220,53,69,0.1); color: #dc3545; }
        .info-label { font-size: 0.78rem; color: var(--lit-text-secondary); }
        .info-value { font-size: 0.92rem; color: var(--lit-heading); font-weight: 500; }

        .form-group { margin-bottom: 1rem; }
        .form-group label { font-size: 0.85rem; font-weight: 600; color: var(--lit-heading); margin-bottom: 0.35rem; }
        .form-group .form-control {
            border-radius: 8px;
            border: 1.5px solid #e3e6f0;
            padding: 0.6rem 0.85rem;
            font-size: 0.9rem;
            transition: border-color 0.2s, box-shadow 0.2s;
        }
        .form-group .form-control:focus {
            border-color: var(--lit-primary);
            box-shadow: 0 0 0 3px rgba(var(--lit-primary-rgb), 0.1);
        }

        .password-toggle {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: var(--lit-text-secondary);
            cursor: pointer;
            padding: 4px;
            font-size: 1rem;
        }
        .password-toggle:hover { color: var(--lit-primary); }

        .btn-save {
            background: var(--lit-primary);
            border: none;
            border-radius: 8px;
            padding: 0.6rem 1.5rem;
            color: #fff;
            font-weight: 600;
            font-size: 0.9rem;
            transition: background 0.2s;
        }
        .btn-save:hover { background: #3445d1; color: #fff; }
        .btn-save:disabled { opacity: 0.6; cursor: not-allowed; }

        .btn-danger-outline {
            background: transparent;
            border: 1.5px solid #dc3545;
            border-radius: 8px;
            padding: 0.6rem 1.5rem;
            color: #dc3545;
            font-weight: 600;
            font-size: 0.9rem;
            transition: all 0.2s;
        }
        .btn-danger-outline:hover { background: #dc3545; color: #fff; }

        .strength-bar { height: 4px; border-radius: 2px; background: var(--lit-surface-3); margin-top: 0.35rem; overflow: hidden; }
        .strength-bar-fill { height: 100%; border-radius: 2px; transition: width 0.3s, background 0.3s; }

        .alert-msg {
            padding: 0.75rem 1rem;
            border-radius: 8px;
            font-size: 0.85rem;
            margin-bottom: 1rem;
            display: none;
        }
        .alert-msg.success { display: block; background: #d1e7dd; color: #0f5132; border: 1px solid #badbcc; }
        .alert-msg.error   { display: block; background: #f8d7da; color: #842029; border: 1px solid #f5c2c7; }
    </style>
</head>

<body>
    <?php
    require_once __DIR__ . '/../templates/header.php';
    require_once __DIR__ . '/../templates/sidebar.php';
    ?>

    <main id="main" class="main">
        <div class="pagetitle">
            <h1>Hồ sơ cá nhân</h1>
            <nav>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="<?= URL_ROOT ?>/dashboard">Trang chủ</a></li>
                    <li class="breadcrumb-item active">Hồ sơ</li>
                </ol>
            </nav>
        </div>

        <section class="section profile-page">
            <!-- Hero -->
            <div class="profile-hero">
                <h2><?= htmlspecialchars($profile['fullname']) ?></h2>
                <p><?= $roleLabel ?> &middot; Thành viên từ <?= $joinedDate ?></p>
            </div>

            <!-- Avatar -->
            <div class="profile-avatar-wrap">
                <div class="profile-avatar">
                    <i class="bi bi-person-fill"></i>
                </div>
            </div>

            <!-- Info Cards -->
            <div class="profile-cards">
                <!-- Thông tin cá nhân -->
                <div class="profile-card">
                    <h5><i class="bi bi-person me-1"></i> Thông tin cá nhân</h5>

                    <div class="info-row">
                        <div class="info-icon bg-primary-subtle"><i class="bi bi-person-badge"></i></div>
                        <div>
                            <div class="info-label">Họ và tên</div>
                            <div class="info-value" id="displayFullname"><?= htmlspecialchars($profile['fullname']) ?></div>
                        </div>
                    </div>

                    <div class="info-row">
                        <div class="info-icon bg-info-subtle"><i class="bi bi-at"></i></div>
                        <div>
                            <div class="info-label">Tên đăng nhập</div>
                            <div class="info-value"><?= htmlspecialchars($profile['username']) ?></div>
                        </div>
                    </div>

                    <div class="info-row">
                        <div class="info-icon bg-success-subtle"><i class="bi bi-envelope"></i></div>
                        <div>
                            <div class="info-label">Email</div>
                            <div class="info-value" id="displayEmail"><?= htmlspecialchars($profile['email'] ?: 'Chưa cập nhật') ?></div>
                        </div>
                    </div>

                    <div class="info-row">
                        <div class="info-icon bg-warning-subtle"><i class="bi bi-shield-lock"></i></div>
                        <div>
                            <div class="info-label">Vai trò</div>
                            <div class="info-value"><?= $roleLabel ?></div>
                        </div>
                    </div>
                </div>

                <!-- Chỉnh sửa thông tin -->
                <div class="profile-card">
                    <h5><i class="bi bi-pencil-square me-1"></i> Chỉnh sửa thông tin</h5>
                    <div id="updateInfoMsg" class="alert-msg"></div>
                    <form id="updateInfoForm">
                        <div class="form-group">
                            <label for="editFullname">Họ và tên</label>
                            <input type="text" class="form-control" id="editFullname" value="<?= htmlspecialchars($profile['fullname']) ?>" required maxlength="255">
                        </div>
                        <div class="form-group">
                            <label for="editEmail">Email</label>
                            <input type="email" class="form-control" id="editEmail" value="<?= htmlspecialchars($profile['email']) ?>" placeholder="ten@truong.edu.vn">
                        </div>
                        <button type="submit" class="btn btn-save" id="btnUpdateInfo">
                            <i class="bi bi-check-lg me-1"></i> Lưu thay đổi
                        </button>
                    </form>
                </div>
            </div>

            <!-- Đổi mật khẩu -->
            <div class="profile-cards" style="margin-top: 1.25rem;">
                <div class="profile-card" style="grid-column: 1 / -1;">
                    <h5><i class="bi bi-lock me-1"></i> Đổi mật khẩu</h5>
                    <div id="changePassMsg" class="alert-msg"></div>
                    <form id="changePassForm" style="max-width: 480px;">
                        <div class="form-group">
                            <label for="currentPassword">Mật khẩu hiện tại</label>
                            <div style="position:relative;">
                                <input type="password" class="form-control" id="currentPassword" required>
                                <button type="button" class="password-toggle" onclick="togglePass('currentPassword', this)" aria-label="Hiện/ẩn mật khẩu" title="Hiện/ẩn mật khẩu">
                                    <i class="bi bi-eye" aria-hidden="true"></i>
                                </button>
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="newPassword">Mật khẩu mới</label>
                            <div style="position:relative;">
                                <input type="password" class="form-control" id="newPassword" required minlength="6">
                                <button type="button" class="password-toggle" onclick="togglePass('newPassword', this)" aria-label="Hiện/ẩn mật khẩu" title="Hiện/ẩn mật khẩu">
                                    <i class="bi bi-eye" aria-hidden="true"></i>
                                </button>
                            </div>
                            <div class="strength-bar"><div class="strength-bar-fill" id="strengthBar"></div></div>
                            <small class="text-muted" id="strengthText"></small>
                        </div>
                        <div class="form-group">
                            <label for="confirmPassword">Xác nhận mật khẩu mới</label>
                            <div style="position:relative;">
                                <input type="password" class="form-control" id="confirmPassword" required minlength="6">
                                <button type="button" class="password-toggle" onclick="togglePass('confirmPassword', this)" aria-label="Hiện/ẩn mật khẩu" title="Hiện/ẩn mật khẩu">
                                    <i class="bi bi-eye" aria-hidden="true"></i>
                                </button>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-save" id="btnChangePass">
                            <i class="bi bi-shield-lock me-1"></i> Đổi mật khẩu
                        </button>
                    </form>
                </div>
            </div>

            <!-- Thống kê -->
            <div class="profile-cards" style="margin-top: 1.25rem;">
                <div class="profile-card" style="grid-column: 1 / -1;">
                    <h5><i class="bi bi-bar-chart me-1"></i> Hoạt động</h5>
                    <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 1rem;" id="statsContainer">
                        <div class="info-row" style="border:none; justify-content:center; text-align:center;">
                            <div>
                                <div class="info-label">Bài phân tích</div>
                                <div class="info-value" id="statAnalysis" style="font-size:1.5rem;">--</div>
                            </div>
                        </div>
                        <div class="info-row" style="border:none; justify-content:center; text-align:center;">
                            <div>
                                <div class="info-label">Lớp học</div>
                                <div class="info-value" id="statClasses" style="font-size:1.5rem;">--</div>
                            </div>
                        </div>
                        <div class="info-row" style="border:none; justify-content:center; text-align:center;">
                            <div>
                                <div class="info-label">Đánh giá đã cho</div>
                                <div class="info-value" id="statEvaluations" style="font-size:1.5rem;">--</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <?php
    require_once __DIR__ . '/../templates/footer.php';
    require_once __DIR__ . '/../templates/vendors.php';
    ?>

    <script>
    const API = '<?= URL_ROOT ?>/api/profile.php';
    const CSRF = '<?= csrf_token() ?>';

    function togglePass(id, btn) {
        const input = document.getElementById(id);
        const icon = btn.querySelector('i');
        if (input.type === 'password') {
            input.type = 'text';
            icon.className = 'bi bi-eye-slash';
        } else {
            input.type = 'password';
            icon.className = 'bi bi-eye';
        }
    }

    function showMsg(el, type, msg) {
        el.className = 'alert-msg ' + type;
        el.textContent = msg;
        el.style.display = 'block';
    }

    function hideMsg(el) {
        el.style.display = 'none';
        el.className = 'alert-msg';
    }

    /* ---- Update info ---- */
    document.getElementById('updateInfoForm').addEventListener('submit', async function(e) {
        e.preventDefault();
        const btn = document.getElementById('btnUpdateInfo');
        const msg = document.getElementById('updateInfoMsg');
        hideMsg(msg);
        btn.disabled = true;
        btn.innerHTML = '<i class="bi bi-arrow-repeat spinning me-1"></i> Đang lưu...';

        try {
            const res = await fetch(API, {
                method: 'POST',
                credentials: 'same-origin',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': CSRF },
                body: JSON.stringify({
                    action: 'update_info',
                    fullname: document.getElementById('editFullname').value.trim(),
                    email: document.getElementById('editEmail').value.trim()
                })
            });
            const data = await res.json();
            if (data.success) {
                showMsg(msg, 'success', data.message || 'Cập nhật thành công');
                document.getElementById('displayFullname').textContent = document.getElementById('editFullname').value.trim();
                document.getElementById('displayEmail').textContent = document.getElementById('editEmail').value.trim() || 'Chưa cập nhật';
                document.getElementById('headerProfileName').textContent = document.getElementById('editFullname').value.trim();
                document.getElementById('headerProfileFullName').textContent = document.getElementById('editFullname').value.trim();
            } else {
                showMsg(msg, 'error', data.message || 'Có lỗi xảy ra');
            }
        } catch(err) {
            showMsg(msg, 'error', 'Lỗi kết nối. Vui lòng thử lại.');
        }
        btn.disabled = false;
        btn.innerHTML = '<i class="bi bi-check-lg me-1"></i> Lưu thay đổi';
    });

    /* ---- Change password ---- */
    document.getElementById('newPassword').addEventListener('input', function() {
        const val = this.value;
        const bar = document.getElementById('strengthBar');
        const txt = document.getElementById('strengthText');
        let score = 0;
        if (val.length >= 6) score++;
        if (val.length >= 10) score++;
        if (/[A-Z]/.test(val) && /[a-z]/.test(val)) score++;
        if (/[0-9]/.test(val)) score++;
        if (/[^A-Za-z0-9]/.test(val)) score++;

        const levels = [
            { w: '0%', bg: '#e9ecef', text: '' },
            { w: '20%', bg: '#dc3545', text: 'Rất yếu' },
            { w: '40%', bg: '#fd7e14', text: 'Yếu' },
            { w: '60%', bg: '#ffc107', text: 'Trung bình' },
            { w: '80%', bg: '#198754', text: 'Mạnh' },
            { w: '100%', bg: '#0d6efd', text: 'Rất mạnh' }
        ];
        const lvl = levels[score] || levels[0];
        bar.style.width = lvl.w;
        bar.style.background = lvl.bg;
        txt.textContent = lvl.text;
    });

    document.getElementById('changePassForm').addEventListener('submit', async function(e) {
        e.preventDefault();
        const btn = document.getElementById('btnChangePass');
        const msg = document.getElementById('changePassMsg');
        hideMsg(msg);

        const current = document.getElementById('currentPassword').value;
        const newPass = document.getElementById('newPassword').value;
        const confirm = document.getElementById('confirmPassword').value;

        if (newPass.length < 6) {
            showMsg(msg, 'error', 'Mật khẩu mới phải có ít nhất 6 ký tự');
            return;
        }
        if (newPass !== confirm) {
            showMsg(msg, 'error', 'Mật khẩu xác nhận không khớp');
            return;
        }
        if (current === newPass) {
            showMsg(msg, 'error', 'Mật khẩu mới phải khác mật khẩu hiện tại');
            return;
        }

        btn.disabled = true;
        btn.innerHTML = '<i class="bi bi-arrow-repeat spinning me-1"></i> Đang đổi...';

        try {
            const res = await fetch(API, {
                method: 'POST',
                credentials: 'same-origin',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': CSRF },
                body: JSON.stringify({
                    action: 'change_password',
                    current_password: current,
                    new_password: newPass
                })
            });
            const data = await res.json();
            if (data.success) {
                showMsg(msg, 'success', data.message || 'Đổi mật khẩu thành công');
                document.getElementById('changePassForm').reset();
                document.getElementById('strengthBar').style.width = '0%';
                document.getElementById('strengthText').textContent = '';
            } else {
                showMsg(msg, 'error', data.message || 'Có lỗi xảy ra');
            }
        } catch(err) {
            showMsg(msg, 'error', 'Lỗi kết nối. Vui lòng thử lại.');
        }
        btn.disabled = false;
        btn.innerHTML = '<i class="bi bi-shield-lock me-1"></i> Đổi mật khẩu';
    });

    /* ---- Load stats ---- */
    (async function loadStats() {
        try {
            const res = await fetch(API + '?action=stats', { credentials: 'same-origin' });
            const data = await res.json();
            if (data.success) {
                document.getElementById('statAnalysis').textContent = data.analysis_count ?? 0;
                document.getElementById('statClasses').textContent = data.class_count ?? 0;
                document.getElementById('statEvaluations').textContent = data.evaluation_count ?? 0;
            }
        } catch(e) {}
    })();
    </script>

    <style>
    .spinning { animation: spin 0.8s linear infinite; display: inline-block; }
    @keyframes spin { from { transform: rotate(0deg); } to { transform: rotate(360deg); } }
    </style>
</body>

</html>
