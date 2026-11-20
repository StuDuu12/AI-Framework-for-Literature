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

$code = trim($_GET['code'] ?? '');
?>
<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="utf-8">
    <meta content="width=device-width, initial-scale=1.0" name="viewport">
    <title>Tham gia lớp học - Literature System</title>
    <?php require_once __DIR__ . '/../templates/assets.php'; ?>
    <style>
        .join-page { max-width: 480px; margin: 2rem auto; }
        .join-card { background: var(--lit-surface); border: 1px solid #e3e6f0; border-radius: 16px; padding: 2rem; box-shadow: 0 2px 12px rgba(0,0,0,0.05); }
        .join-icon { width: 64px; height: 64px; border-radius: 50%; background: rgba(var(--lit-primary-rgb), 0.1); display: flex; align-items: center; justify-content: center; margin: 0 auto 1.25rem; font-size: 1.5rem; color: var(--lit-primary); }
        .join-title { font-size: 1.25rem; font-weight: 700; color: var(--lit-heading); text-align: center; margin-bottom: 0.25rem; }
        .join-sub { text-align: center; color: var(--lit-text-secondary); font-size: 0.85rem; margin-bottom: 1.5rem; }
        .code-input { font-size: 1.5rem; font-weight: 700; letter-spacing: 0.35rem; text-align: center; text-transform: uppercase; border-radius: 12px; border: 2px solid #e3e6f0; padding: 0.75rem; width: 100%; }
        .code-input:focus { border-color: var(--lit-primary); box-shadow: 0 0 0 3px rgba(var(--lit-primary-rgb), 0.1); outline: none; }
        .btn-join { background: var(--lit-primary); border: none; border-radius: 10px; padding: 0.65rem; font-weight: 600; font-size: 0.95rem; width: 100%; color: #fff; }
        .btn-join:hover { background: #3445d1; }
        .btn-join:disabled { opacity: 0.6; cursor: not-allowed; }
        .alert { border-radius: 10px; font-size: 0.85rem; }
        .success-state { text-align: center; padding: 1rem 0; }
        .success-state i { font-size: 3rem; color: #198754; margin-bottom: 1rem; }
    </style>
</head>

<body>
    <?php
    require_once __DIR__ . '/../templates/header.php';
    require_once __DIR__ . '/../templates/sidebar.php';
    ?>

    <main id="main" class="main">
        <div class="pagetitle">
            <h1><i class="bi bi-box-arrow-in-right me-2"></i>Tham gia lớp học</h1>
        </div>

        <section class="section join-page">
            <div class="join-card">
                <div class="join-icon"><i class="bi bi-key"></i></div>
                <h3 class="join-title">Nhập mã mời</h3>
                <p class="join-sub">Nhập mã mời từ giáo viên để tham gia lớp học</p>

                <div id="joinForm">
                    <div id="joinMsg" class="alert" style="display:none;"></div>
                    <input aria-label="Mã mời" type="text" class="code-input form-control mb-3" id="inviteCode"
                        placeholder="XXXX0000" maxlength="12"
                        value="<?= htmlspecialchars($code) ?>">
                    <button class="btn btn-join" id="btnJoin" onclick="joinClass()">
                        <i class="bi bi-box-arrow-in-right me-1"></i> Tham gia
                    </button>
                </div>

                <div id="successState" class="success-state" style="display:none;">
                    <i class="bi bi-check-circle d-block"></i>
                    <h5 class="text-success" id="successMsg">Tham gia thành công!</h5>
                    <a href="<?= URL_ROOT ?>/class" class="btn btn-join mt-3" id="btnGoClass">
                        <i class="bi bi-arrow-right me-1"></i> Vào lớp học
                    </a>
                </div>
            </div>
        </section>
    </main>

    <?php
    require_once __DIR__ . '/../templates/footer.php';
    require_once __DIR__ . '/../templates/vendors.php';
    ?>

    <script>
    const API = '<?= URL_ROOT ?>/api/class_invitation.php';
    const CSRF = '<?= csrf_token() ?>';

    async function joinClass() {
        const code = document.getElementById('inviteCode').value.trim();
        const msg = document.getElementById('joinMsg');
        const btn = document.getElementById('btnJoin');

        if (!code) {
            msg.className = 'alert alert-danger';
            msg.textContent = 'Vui lòng nhập mã mời';
            msg.style.display = 'block';
            return;
        }

        btn.disabled = true;
        btn.innerHTML = '<i class="bi bi-arrow-repeat spinning me-1"></i> Đang xử lý...';
        msg.style.display = 'none';

        try {
            const res = await fetch(API, {
                method: 'POST',
                credentials: 'same-origin',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': CSRF },
                body: JSON.stringify({ action: 'verify_code', code: code })
            });
            const data = await res.json();

            if (data.success) {
                document.getElementById('joinForm').style.display = 'none';
                document.getElementById('successState').style.display = 'block';
                document.getElementById('successMsg').textContent = data.message;
                if (data.class_id) {
                    document.getElementById('btnGoClass').href = '<?= URL_ROOT ?>/class?class_id=' + data.class_id;
                }
            } else {
                msg.className = 'alert alert-danger';
                msg.textContent = data.message;
                msg.style.display = 'block';
            }
        } catch(e) {
            msg.className = 'alert alert-danger';
            msg.textContent = 'Lỗi kết nối. Vui lòng thử lại.';
            msg.style.display = 'block';
        }

        btn.disabled = false;
        btn.innerHTML = '<i class="bi bi-box-arrow-in-right me-1"></i> Tham gia';
    }

    document.getElementById('inviteCode').addEventListener('keypress', function(e) {
        if (e.key === 'Enter') joinClass();
    });
    </script>
    <style>.spinning { animation: spin 0.8s linear infinite; display: inline-block; } @keyframes spin { from { transform: rotate(0deg); } to { transform: rotate(360deg); } }</style>
</body>

</html>
