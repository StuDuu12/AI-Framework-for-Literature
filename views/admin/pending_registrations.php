<?php
session_start();
require_once __DIR__ . '/../../config/env.php';
require_once __DIR__ . '/../../config/db.php';

if (!isset($_SESSION['user']) || empty($_SESSION['user'])) {
    header("Location: " . URL_ROOT . "/login");
    exit();
}
$role = $_SESSION['user']['role'] ?? '';
if (!in_array($role, ['admin', 'teacher'])) {
    header("Location: " . URL_ROOT . "/dashboard");
    exit();
}

$currentStatus = $_GET['status'] ?? 'pending';
if (!in_array($currentStatus, ['pending', 'approved', 'rejected', 'all'])) $currentStatus = 'pending';

try {
    if ($currentStatus === 'all') {
        $stmt = $pdo->query("SELECT * FROM registration_requests ORDER BY created_at DESC LIMIT 100");
    } else {
        $stmt = $pdo->prepare("SELECT * FROM registration_requests WHERE status = :status ORDER BY created_at DESC LIMIT 100");
        $stmt->execute([':status' => $currentStatus]);
    }
    $requests = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Error fetching registrations: " . $e->getMessage());
    $requests = [];
}

$countPending = 0;
try {
    $stmt = $pdo->query("SELECT COUNT(*) as cnt FROM registration_requests WHERE status = 'pending'");
    $countPending = $stmt->fetch(PDO::FETCH_ASSOC)['cnt'];
} catch (PDOException $e) {}
?>
<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="utf-8">
    <meta content="width=device-width, initial-scale=1.0" name="viewport">
    <title>Duyệt đăng ký - Literature System</title>
    <?php require_once __DIR__ . '/../templates/assets.php'; ?>
    <style>
        .status-badge { padding: 0.3rem 0.7rem; border-radius: 20px; font-size: 0.75rem; font-weight: 600; }
        .status-pending  { background: #fff3cd; color: #664d03; }
        .status-approved { background: #d1e7dd; color: #0f5132; }
        .status-rejected { background: #f8d7da; color: #842029; }
        .filter-tabs { display: flex; gap: 0.5rem; margin-bottom: 1.25rem; flex-wrap: wrap; }
        .filter-tab { padding: 0.4rem 1rem; border-radius: 20px; border: 1.5px solid #e3e6f0; background: #fff; color: #6c757d; font-size: 0.85rem; font-weight: 500; text-decoration: none; transition: all 0.15s; }
        .filter-tab:hover { border-color: #4154f1; color: #4154f1; }
        .filter-tab.active { background: #4154f1; color: #fff; border-color: #4154f1; }
        .filter-tab .count { background: rgba(0,0,0,0.1); padding: 0.1rem 0.45rem; border-radius: 10px; font-size: 0.75rem; margin-left: 0.3rem; }
        .filter-tab.active .count { background: rgba(255,255,255,0.25); }
        .req-card { background: #fff; border: 1px solid #e3e6f0; border-radius: 12px; padding: 1.25rem; margin-bottom: 0.75rem; transition: box-shadow 0.15s; }
        .req-card:hover { box-shadow: 0 2px 12px rgba(0,0,0,0.06); }
        .req-info { display: flex; align-items: center; gap: 1rem; }
        .req-avatar { width: 44px; height: 44px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 1.1rem; flex-shrink: 0; }
        .req-avatar.student { background: rgba(65,84,241,0.1); color: #4154f1; }
        .req-avatar.teacher { background: rgba(25,135,84,0.1); color: #198754; }
        .req-name { font-weight: 600; color: #012970; font-size: 0.95rem; }
        .req-meta { font-size: 0.8rem; color: #6c757d; margin-top: 0.15rem; }
        .req-actions { display: flex; gap: 0.5rem; margin-top: 0.75rem; }
        .btn-approve { background: #198754; border: none; color: #fff; padding: 0.4rem 1rem; border-radius: 8px; font-size: 0.82rem; font-weight: 600; }
        .btn-approve:hover { background: #157347; color: #fff; }
        .btn-reject { background: transparent; border: 1.5px solid #dc3545; color: #dc3545; padding: 0.4rem 1rem; border-radius: 8px; font-size: 0.82rem; font-weight: 600; }
        .btn-reject:hover { background: #dc3545; color: #fff; }
        .btn-approve:disabled, .btn-reject:disabled { opacity: 0.5; cursor: not-allowed; }
        .empty-state { text-align: center; padding: 3rem 1rem; color: #6c757d; }
        .empty-state i { font-size: 3rem; opacity: 0.3; margin-bottom: 1rem; display: block; }
        .toast-msg { position: fixed; top: 80px; right: 20px; z-index: 9999; padding: 0.75rem 1.25rem; border-radius: 10px; font-size: 0.85rem; font-weight: 500; display: none; }
        .toast-msg.show { display: block; animation: slideIn 0.3s ease; }
        .toast-msg.success { background: #d1e7dd; color: #0f5132; border: 1px solid #badbcc; }
        .toast-msg.error { background: #f8d7da; color: #842029; border: 1px solid #f5c2c7; }
        @keyframes slideIn { from { transform: translateX(100%); opacity: 0; } to { transform: translateX(0); opacity: 1; } }
    </style>
</head>

<body>
    <?php
    require_once __DIR__ . '/../templates/header.php';
    require_once __DIR__ . '/../templates/sidebar.php';
    ?>

    <main id="main" class="main">
        <div class="pagetitle">
            <h1><i class="bi bi-person-check me-2"></i>Duyệt đăng ký</h1>
            <nav>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="<?= URL_ROOT ?>/dashboard">Trang quản trị</a></li>
                    <li class="breadcrumb-item active">Duyệt đăng ký</li>
                </ol>
            </nav>
        </div>

        <section class="section">
            <div class="filter-tabs">
                <a href="?status=pending" class="filter-tab <?= $currentStatus === 'pending' ? 'active' : '' ?>">
                    Chờ duyệt <span class="count"><?= $countPending ?></span>
                </a>
                <a href="?status=approved" class="filter-tab <?= $currentStatus === 'approved' ? 'active' : '' ?>">Đã duyệt</a>
                <a href="?status=rejected" class="filter-tab <?= $currentStatus === 'rejected' ? 'active' : '' ?>">Đã từ chối</a>
                <a href="?status=all" class="filter-tab <?= $currentStatus === 'all' ? 'active' : '' ?>">Tất cả</a>
            </div>

            <div id="requestsList">
                <?php if (empty($requests)): ?>
                    <div class="empty-state">
                        <i class="bi bi-inbox"></i>
                        <p>Không có yêu cầu nào</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($requests as $req): ?>
                        <div class="req-card" id="req-<?= $req['id'] ?>">
                            <div class="req-info">
                                <div class="req-avatar <?= $req['role'] ?>">
                                    <i class="bi <?= $req['role'] === 'teacher' ? 'bi-person-workspace' : 'bi-mortarboard' ?>"></i>
                                </div>
                                <div>
                                    <div class="req-name"><?= htmlspecialchars($req['fullname']) ?></div>
                                    <div class="req-meta">
                                        @<?= htmlspecialchars($req['username']) ?>
                                        <?php if ($req['email']): ?>
                                            &middot; <?= htmlspecialchars($req['email']) ?>
                                        <?php endif; ?>
                                        &middot; <?= $req['role'] === 'teacher' ? 'Giáo viên' : 'Học sinh' ?>
                                    </div>
                                    <div class="req-meta">
                                        <?= date('d/m/Y H:i', strtotime($req['created_at'])) ?>
                                        <span class="status-badge status-<?= $req['status'] ?>"><?= match($req['status']) { 'pending' => 'Chờ duyệt', 'approved' => 'Đã duyệt', 'rejected' => 'Đã từ chối', default => $req['status'] } ?></span>
                                    </div>
                                    <?php if ($req['reject_reason']): ?>
                                        <div class="req-meta text-danger">Lý do: <?= htmlspecialchars($req['reject_reason']) ?></div>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <?php if ($req['status'] === 'pending'): ?>
                                <div class="req-actions">
                                    <button class="btn-approve" onclick="approveReq(<?= $req['id'] ?>)">
                                        <i class="bi bi-check-lg me-1"></i>Duyệt
                                    </button>
                                    <button class="btn-reject" onclick="rejectReq(<?= $req['id'] ?>)">
                                        <i class="bi bi-x-lg me-1"></i>Từ chối
                                    </button>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </section>
    </main>

    <div class="toast-msg" id="toastMsg"></div>

    <?php
    require_once __DIR__ . '/../templates/footer.php';
    require_once __DIR__ . '/../templates/vendors.php';
    ?>

    <script>
    const API = '<?= URL_ROOT ?>/api/auth.php';
    const CSRF = '<?= csrf_token() ?>';

    function showToast(type, msg) {
        const t = document.getElementById('toastMsg');
        t.className = 'toast-msg show ' + type;
        t.textContent = msg;
        setTimeout(() => { t.className = 'toast-msg'; }, 3000);
    }

    async function approveReq(id) {
        if (!confirm('Duyệt yêu cầu đăng ký này?')) return;
        const card = document.getElementById('req-' + id);
        const btns = card.querySelectorAll('button');
        btns.forEach(b => b.disabled = true);

        try {
            const res = await fetch(API, {
                method: 'POST',
                credentials: 'same-origin',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': CSRF },
                body: JSON.stringify({ action: 'approve_registration', request_id: id })
            });
            const data = await res.json();
            if (data.success) {
                showToast('success', data.message);
                card.querySelector('.status-badge').className = 'status-badge status-approved';
                card.querySelector('.status-badge').textContent = 'Đã duyệt';
                card.querySelector('.req-actions')?.remove();
            } else {
                showToast('error', data.message);
                btns.forEach(b => b.disabled = false);
            }
        } catch(e) {
            showToast('error', 'Lỗi kết nối');
            btns.forEach(b => b.disabled = false);
        }
    }

    async function rejectReq(id) {
        const reason = prompt('Lý do từ chối (không bắt buộc):');
        if (reason === null) return;
        const card = document.getElementById('req-' + id);
        const btns = card.querySelectorAll('button');
        btns.forEach(b => b.disabled = true);

        try {
            const res = await fetch(API, {
                method: 'POST',
                credentials: 'same-origin',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': CSRF },
                body: JSON.stringify({ action: 'reject_registration', request_id: id, reason: reason })
            });
            const data = await res.json();
            if (data.success) {
                showToast('success', data.message);
                card.querySelector('.status-badge').className = 'status-badge status-rejected';
                card.querySelector('.status-badge').textContent = 'Đã từ chối';
                card.querySelector('.req-actions')?.remove();
            } else {
                showToast('error', data.message);
                btns.forEach(b => b.disabled = false);
            }
        } catch(e) {
            showToast('error', 'Lỗi kết nối');
            btns.forEach(b => b.disabled = false);
        }
    }
    </script>
</body>

</html>
