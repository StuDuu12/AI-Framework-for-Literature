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
$role = $_SESSION['user']['role'] ?? '';
if ($role !== 'admin') {
    header("Location: " . URL_ROOT . "/dashboard");
    exit();
}
?>
<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="utf-8">
    <meta content="width=device-width, initial-scale=1.0" name="viewport">
    <title>Nhập người dùng</title>
    <?php require_once __DIR__ . '/../templates/assets.php'; ?>
    <style>
        .import-page { max-width: 720px; margin: 0 auto; }
        .upload-zone {
            border: 2px dashed #c3c6d1;
            border-radius: 12px;
            padding: 2.5rem 1.5rem;
            text-align: center;
            cursor: pointer;
            transition: all 0.2s;
            background: var(--lit-surface-2);
        }
        .upload-zone:hover, .upload-zone.dragover { border-color: var(--lit-primary); background: rgba(var(--lit-primary-rgb), 0.03); }
        .upload-zone i { font-size: 2.5rem; color: var(--lit-text-muted); margin-bottom: 0.75rem; display: block; }
        .upload-zone p { color: var(--lit-text-secondary); margin: 0; }
        .upload-zone .small { font-size: 0.8rem; color: var(--lit-text-muted); margin-top: 0.5rem; }
        .preview-table { max-height: 400px; overflow-y: auto; }
        .result-card { background: var(--lit-surface); border: 1px solid #e3e6f0; border-radius: 12px; padding: 1.5rem; margin-top: 1rem; }
        .result-row { display: flex; align-items: center; gap: 0.75rem; padding: 0.5rem 0; border-bottom: 1px solid #f1f3f5; }
        .result-row:last-child { border: none; }
        .result-icon { width: 28px; height: 28px; border-radius: 50%; display: flex; align-items: center; justify-content: center; flex-shrink: 0; font-size: 0.85rem; }
        .result-icon.ok { background: #d1e7dd; color: #0f5132; }
        .result-icon.err { background: #f8d7da; color: #842029; }
        .btn-import { background: var(--lit-primary); border: none; color: #fff; padding: 0.55rem 1.5rem; border-radius: 8px; font-weight: 600; }
        .btn-import:hover { background: #3445d1; color: #fff; }
        .btn-import:disabled { opacity: 0.6; cursor: not-allowed; }
        .format-help { background: var(--lit-surface-2); border: 1px solid var(--lit-border); border-radius: 10px; padding: 1rem 1.25rem; margin-bottom: 1.25rem; font-size: 0.85rem; }
        .format-help code { background: var(--lit-surface-3); padding: 0.15rem 0.4rem; border-radius: 4px; font-size: 0.82rem; }
    </style>
</head>

<body>
    <?php
    require_once __DIR__ . '/../templates/header.php';
    require_once __DIR__ . '/../templates/sidebar.php';
    ?>

    <main id="main" class="main">
        <div class="pagetitle">
            <h1><i class="bi bi-file-earmark-spreadsheet me-2"></i>Nhập người dùng</h1>
            <nav>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="<?= URL_ROOT ?>/dashboard">Trang quản trị</a></li>
                    <li class="breadcrumb-item"><a href="<?= URL_ROOT ?>/admin/users">Người dùng</a></li>
                    <li class="breadcrumb-item active">Nhập</li>
                </ol>
            </nav>
        </div>

        <section class="section import-page">
            <div class="format-help">
                <strong><i class="bi bi-info-circle me-1"></i> Định dạng CSV:</strong>
                <br>File CSV phải có header ở dòng đầu tiên:
                <br><code>fullname,username,email,role,password</code>
                <br>Trong đó: <code>role</code> = student | teacher, <code>password</code> (tuỳ chọn, tối thiểu 6 ký tự; để trống thì hệ thống tự sinh mật khẩu ngẫu nhiên và cho tải danh sách về)
                <br>Ví dụ: <code>Nguyễn Văn A,nguyenvana,nguyenvana@gmail.com,student,</code>
            </div>

            <div class="card">
                <div class="card-body">
                    <div id="uploadZone" class="upload-zone" onclick="document.getElementById('csvFile').click()">
                        <i class="bi bi-cloud-arrow-up"></i>
                        <p>Nhấn hoặc kéo thả file CSV vào đây</p>
                        <div class="small">Hỗ trợ file .csv, tối đa 5MB</div>
                    </div>
                    <input aria-label="Tệp CSV" type="file" id="csvFile" accept=".csv" style="display:none" onchange="handleFile(this)">

                    <div id="previewSection" style="display:none; margin-top:1.25rem;">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <h6 class="mb-0">Xem trước (<span id="rowCount">0</span> dòng)</h6>
                            <button class="btn btn-import btn-sm" id="btnImport" onclick="importUsers()">
                                <i class="bi bi-upload me-1"></i> Nhập
                            </button>
                        </div>
                        <div class="table-responsive preview-table">
                            <table class="table table-sm table-striped" id="previewTable">
                                <thead id="previewHead"></thead>
                                <tbody id="previewBody"></tbody>
                            </table>
                        </div>
                    </div>

                    <div id="resultSection" style="display:none;"></div>
                </div>
            </div>
        </section>
    </main>

    <?php
    require_once __DIR__ . '/../templates/footer.php';
    require_once __DIR__ . '/../templates/vendors.php';
    ?>

    <script>
    const API_URL = '<?= URL_ROOT ?>/api/admin_actions.php';
    const CSRF = '<?= csrf_token() ?>';
    let csvData = [];

    // Drag & drop
    const zone = document.getElementById('uploadZone');
    zone.addEventListener('dragover', e => { e.preventDefault(); zone.classList.add('dragover'); });
    zone.addEventListener('dragleave', () => zone.classList.remove('dragover'));
    zone.addEventListener('drop', e => {
        e.preventDefault();
        zone.classList.remove('dragover');
        const file = e.dataTransfer.files[0];
        if (file && file.name.endsWith('.csv')) parseCSV(file);
    });

    function handleFile(input) {
        if (input.files[0]) parseCSV(input.files[0]);
    }

    function parseCSV(file) {
        const reader = new FileReader();
        reader.onload = function(e) {
            const lines = e.target.result.split(/\r?\n/).filter(l => l.trim());
            if (lines.length < 2) { UI.warning('File CSV phải có ít nhất 2 dòng (dòng tiêu đề + dữ liệu).'); return; }

            const headers = lines[0].split(',').map(h => h.trim().toLowerCase());
            const required = ['fullname', 'username'];
            const missing = required.filter(r => !headers.includes(r));
            if (missing.length) { UI.warning('Thiếu cột bắt buộc: ' + missing.join(', ')); return; }

            csvData = [];
            for (let i = 1; i < lines.length; i++) {
                const vals = lines[i].split(',').map(v => v.trim());
                const row = {};
                headers.forEach((h, idx) => { row[h] = vals[idx] || ''; });
                csvData.push(row);
            }

            document.getElementById('rowCount').textContent = csvData.length;
            const thead = document.getElementById('previewHead');
            const tbody = document.getElementById('previewBody');
            thead.innerHTML = '<tr><th>#</th>' + headers.map(h => '<th>' + escapeHtml(h) + '</th>').join('') + '</tr>';
            tbody.innerHTML = csvData.slice(0, 50).map((row, i) =>
                '<tr><td>' + (i+1) + '</td>' + headers.map(h => '<td>' + escapeHtml(row[h] || '') + '</td>').join('') + '</tr>'
            ).join('');

            document.getElementById('uploadZone').style.display = 'none';
            document.getElementById('previewSection').style.display = 'block';
        };
        reader.readAsText(file);
    }

    async function importUsers() {
        const btn = document.getElementById('btnImport');
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Đang import...';

        try {
            const res = await fetch(API_URL, {
                method: 'POST',
                credentials: 'same-origin',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': CSRF },
                body: JSON.stringify({ action: 'import_users', users: csvData })
            });
            const data = await res.json();

            document.getElementById('previewSection').style.display = 'none';
            const section = document.getElementById('resultSection');
            section.style.display = 'block';

            if (data.success) {
                let html = '<div class="alert alert-success"><i class="bi bi-check-circle me-1"></i> ' + escapeHtml(data.message) + '</div>';
                if (data.generated && data.generated.length) {
                    // Mật khẩu ngẫu nhiên chỉ hiển thị MỘT lần — admin cần tải về để phát cho người dùng
                    lastGenerated = data.generated;
                    html += '<div class="alert alert-warning"><i class="bi bi-key me-1"></i> ' + data.generated.length +
                        ' tài khoản không có mật khẩu trong file đã được cấp mật khẩu ngẫu nhiên. ' +
                        '<strong>Danh sách chỉ hiển thị một lần</strong> — hãy tải về ngay.' +
                        ' <button type="button" class="btn btn-sm btn-warning ms-2" onclick="downloadGenerated()"><i class="bi bi-download me-1"></i>Tải CSV</button></div>';
                    html += '<div class="result-card"><table class="table table-sm mb-0"><thead><tr><th>Họ tên</th><th>Tên đăng nhập</th><th>Mật khẩu</th></tr></thead><tbody>' +
                        data.generated.map(g => '<tr><td>' + escapeHtml(g.fullname) + '</td><td>' + escapeHtml(g.username) + '</td><td><code>' + escapeHtml(g.password) + '</code></td></tr>').join('') +
                        '</tbody></table></div>';
                }
                if (data.errors && data.errors.length) {
                    html += '<div class="result-card"><h6>Lỗi (' + data.errors.length + '):</h6>';
                    data.errors.forEach(e => {
                        html += '<div class="result-row"><div class="result-icon err"><i class="bi bi-x"></i></div><span>' + escapeHtml(e) + '</span></div>';
                    });
                    html += '</div>';
                }
                section.innerHTML = html;
            } else {
                section.innerHTML = '<div class="alert alert-danger"><i class="bi bi-exclamation-circle me-1"></i> ' + escapeHtml(data.message) + '</div>';
            }
        } catch(e) {
            document.getElementById('resultSection').style.display = 'block';
            document.getElementById('resultSection').innerHTML = '<div class="alert alert-danger">Lỗi kết nối</div>';
        }
        btn.disabled = false;
        btn.innerHTML = '<i class="bi bi-upload me-1"></i> Import';
    }

    function escapeHtml(t) { const d = document.createElement('div'); d.textContent = t; return d.innerHTML; }

    let lastGenerated = [];
    function downloadGenerated() {
        const q = v => '"' + String(v).replace(/"/g, '""') + '"';
        const csv = '﻿' + ['fullname,username,password']
            .concat(lastGenerated.map(g => [g.fullname, g.username, g.password].map(q).join(',')))
            .join('\r\n');
        const a = document.createElement('a');
        a.href = URL.createObjectURL(new Blob([csv], { type: 'text/csv;charset=utf-8' }));
        a.download = 'mat_khau_tai_khoan_moi.csv';
        a.click();
        URL.revokeObjectURL(a.href);
    }
    </script>
</body>

</html>
