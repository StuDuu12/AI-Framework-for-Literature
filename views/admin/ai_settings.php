<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php require_once __DIR__ . '/../templates/assets.php'; ?>
    <title data-lang-key="admin_ai_settings">Cấu hình AI - Literature System</title>
</head>
<body>
<?php
require_once __DIR__ . '/../../config/helper_ai_settings.php';
require_once __DIR__ . '/../templates/header.php';
require_once __DIR__ . '/../templates/sidebar.php';

$settings = getAiSettings($pdo);
$zenFreeModels = getOpenCodeZenFreeModels();
$zenPaidModels = getOpenCodeZenPaidModels();
$goModels = getOpenCodeGoModels();
$geminiModels = getGeminiModels();
$isOpenCode = ($settings['provider'] ?? 'opencode') === 'opencode';
?>

<main id="main" class="main">
    <div class="pagetitle">
        <h1 data-lang-key="admin_ai_settings">Cấu hình AI</h1>
        <nav>
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="<?= URL_ROOT ?>/dashboard">Trang chủ</a></li>
                <li class="breadcrumb-item active">Cài đặt AI</li>
            </ol>
        </nav>
    </div>

    <section class="section">
        <div class="row">
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">Nhà cung cấp AI: <?= htmlspecialchars(strtoupper($settings['provider'] ?? 'opencode')) ?></h5>
                        <p class="text-muted small">
                            API key / provider được cấu hình trong <code>config/env.local.php</code> (không lưu trên database).
                            Hiện tại: <strong><?= ($settings['provider'] ?? 'opencode') === 'opencode' ? 'OpenCode Zen — ' . htmlspecialchars($settings['model']) : 'Google Gemini' ?></strong>.
                            <?php if ($isOpenCode): ?>
                                <br>Endpoint đang dùng: <code id="active-endpoint"><?= htmlspecialchars($settings['api_url'] ?? resolveOpenCodeApiUrl($pdo)) ?></code>
                                <br><span class="text-muted">Zen <strong>miễn phí</strong> models (<?= count($zenFreeModels) ?>) auto-use <code>zen/v1</code>. Go models use <code>zen/go/v1</code> (billing required).</span>
                            <?php endif; ?>
                            <?php if ($settings['api_key_configured']): ?>
                                <span class="text-success"><i class="bi bi-check-circle"></i> Đã cấu hình key</span>
                            <?php else: ?>
                                <span class="text-danger"><i class="bi bi-x-circle"></i> Chưa có API key</span>
                            <?php endif; ?>
                        </p>

                        <form id="ai-settings-form">
                            <div class="mb-3 form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="ai_enabled" <?= $settings['ai_enabled'] ? 'checked' : '' ?>>
                                <label class="form-check-label" for="ai_enabled">Bật AI cho toàn hệ thống</label>
                            </div>
                            <div class="mb-3">
                                <label for="system_prompt_lang" class="form-label">Ngôn ngữ prompt hệ thống (AI)</label>
                                <select class="form-select" id="system_prompt_lang">
                                    <option value="vi" <?= ($settings['system_prompt_lang'] ?? 'vi') === 'vi' ? 'selected' : '' ?>>Tiếng Việt — phân tích văn học bằng tiếng Việt</option>
                                    <option value="en" <?= ($settings['system_prompt_lang'] ?? 'vi') === 'en' ? 'selected' : '' ?>>Tiếng Anh — phân tích văn học bằng tiếng Anh</option>
                                </select>
                                <div class="form-text">Áp dụng cho phân tích chính, chat/refine, slideshow và quiz trong Gen AI.</div>
                            </div>
                            <div class="mb-3">
                                <label for="gemini_model" class="form-label">Model</label>
                                <select class="form-select" id="gemini_model">
                                    <?php if ($isOpenCode): ?>
                                    <optgroup label="OpenCode Zen — Free (recommended)">
                                        <?php foreach ($zenFreeModels as $id => $label): ?>
                                        <option value="<?= htmlspecialchars($id) ?>" data-tier="free" <?= $settings['model'] === $id ? 'selected' : '' ?>><?= htmlspecialchars($label) ?> (<?= htmlspecialchars($id) ?>)</option>
                                        <?php endforeach; ?>
                                    </optgroup>
                                    <optgroup label="OpenCode Zen — Paid (credits)">
                                        <?php foreach ($zenPaidModels as $id => $label): ?>
                                        <option value="<?= htmlspecialchars($id) ?>" data-tier="zen-paid" <?= $settings['model'] === $id ? 'selected' : '' ?>><?= htmlspecialchars($label) ?> (<?= htmlspecialchars($id) ?>)</option>
                                        <?php endforeach; ?>
                                    </optgroup>
                                    <optgroup label="OpenCode Go — Subscription">
                                        <?php foreach ($goModels as $id => $label): ?>
                                        <option value="<?= htmlspecialchars($id) ?>" data-tier="go" <?= $settings['model'] === $id ? 'selected' : '' ?>><?= htmlspecialchars($label) ?> (<?= htmlspecialchars($id) ?>)</option>
                                        <?php endforeach; ?>
                                    </optgroup>
                                    <?php else: ?>
                                        <?php foreach ($geminiModels as $id => $label): ?>
                                        <option value="<?= htmlspecialchars($id) ?>" <?= $settings['model'] === $id ? 'selected' : '' ?>><?= htmlspecialchars($label) ?></option>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </select>
                                <div class="form-text" id="model-tier-hint"></div>
                            </div>
                            <div class="mb-3">
                                <label for="gemini_temperature" class="form-label">Temperature (0–2): <span id="temp-val"><?= $settings['temperature'] ?></span></label>
                                <input type="range" class="form-range" id="gemini_temperature" min="0" max="2" step="0.1" value="<?= $settings['temperature'] ?>">
                            </div>
                            <div class="mb-3">
                                <label for="gemini_max_tokens" class="form-label">Số token đầu ra tối đa</label>
                                <input type="number" class="form-control" id="gemini_max_tokens" min="256" max="8192" value="<?= (int)$settings['max_tokens'] ?>">
                            </div>
                            <button type="submit" class="btn btn-primary"><i class="bi bi-save"></i> Lưu cấu hình</button>
                        </form>
                        <div id="save-msg" class="mt-3"></div>
                    </div>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">Lưu ý bảo mật</h5>
                        <ul class="small text-muted mb-0">
                            <li>Rotate API key nếu từng bị lộ trong git</li>
                            <li>Key chỉ nên nằm trong <code>env.local.php</code></li>
                            <li>Tắt AI sẽ chặn mọi request tới Gemini</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </section>
</main>

<?php require_once __DIR__ . '/../templates/footer.php'; ?>
<?php require_once __DIR__ . '/../templates/vendors.php'; ?>
<script>
const ZEN_V1 = 'https://opencode.ai/zen/v1/chat/completions';
const ZEN_GO = 'https://opencode.ai/zen/go/v1/chat/completions';

function updateModelHint() {
    const sel = document.getElementById('gemini_model');
    const hint = document.getElementById('model-tier-hint');
    const endpointEl = document.getElementById('active-endpoint');
    if (!sel || !hint) return;
    const opt = sel.options[sel.selectedIndex];
    const tier = opt ? opt.dataset.tier : '';
    if (tier === 'free') {
        hint.textContent = 'Gói miễn phí — không cần đăng ký Go.';
        if (endpointEl) endpointEl.textContent = ZEN_V1;
    } else if (tier === 'go') {
        hint.textContent = 'Cần đăng ký Go và phương thức thanh toán trên opencode.ai.';
        if (endpointEl) endpointEl.textContent = ZEN_GO;
    } else if (tier === 'zen-paid') {
        hint.textContent = 'Dùng credit Zen trả phí trong tài khoản OpenCode.';
        if (endpointEl) endpointEl.textContent = ZEN_V1;
    } else {
        hint.textContent = '';
    }
}

document.getElementById('gemini_model').addEventListener('change', updateModelHint);
updateModelHint();

document.getElementById('gemini_temperature').addEventListener('input', function () {
    document.getElementById('temp-val').textContent = this.value;
});
document.getElementById('ai-settings-form').addEventListener('submit', async function (e) {
    e.preventDefault();
    const msg = document.getElementById('save-msg');
    try {
        const res = await fetch('<?= URL_ROOT ?>/api/admin_ai_settings.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                model: document.getElementById('gemini_model').value,
                temperature: document.getElementById('gemini_temperature').value,
                max_tokens: document.getElementById('gemini_max_tokens').value,
                ai_enabled: document.getElementById('ai_enabled').checked,
                system_prompt_lang: document.getElementById('system_prompt_lang').value
            })
        });
        const data = await res.json();
        msg.innerHTML = data.success
            ? '<div class="alert alert-success py-2">Đã lưu cấu hình AI.</div>'
            : '<div class="alert alert-danger py-2">' + (data.message || 'Lỗi') + '</div>';
    } catch (err) {
        msg.innerHTML = '<div class="alert alert-danger py-2">Không thể lưu.</div>';
    }
});
</script>
</body>
</html>
