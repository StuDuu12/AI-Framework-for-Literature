<?php
/**
 * App settings helper (AI config stored in DB, API key stays in env.local.php)
 */

function getAppSetting(PDO $pdo, string $key, $default = null)
{
    try {
        $stmt = $pdo->prepare('SELECT setting_value FROM app_settings WHERE setting_key = :k LIMIT 1');
        $stmt->execute([':k' => $key]);
        $val = $stmt->fetchColumn();
        return $val !== false ? $val : $default;
    } catch (PDOException $e) {
        return $default;
    }
}

function setAppSetting(PDO $pdo, string $key, string $value): bool
{
    $stmt = $pdo->prepare('
        INSERT INTO app_settings (setting_key, setting_value) VALUES (:k, :v)
        ON DUPLICATE KEY UPDATE setting_value = :v2, updated_at = CURRENT_TIMESTAMP
    ');
    return $stmt->execute([':k' => $key, ':v' => $value, ':v2' => $value]);
}

function resolveAiProvider(): string
{
    return defined('AI_PROVIDER') ? AI_PROVIDER : 'gemini';
}

/**
 * OpenCode Zen — free models (endpoint: zen/v1/chat/completions).
 * @see https://opencode.ai/docs/zen/
 */
function getOpenCodeZenFreeModels(): array
{
    return [
        'mimo-v2.5-free'         => 'MiMo V2.5 Free',
        'deepseek-v4-flash-free' => 'DeepSeek V4 Flash Free',
        'north-mini-code-free'   => 'North Mini Code Free',
        'nemotron-3-ultra-free'  => 'Nemotron 3 Ultra Free',
        'big-pickle'             => 'Big Pickle (Free)',
    ];
}

/** Paid Zen models on zen/v1 (require credits / billing on OpenCode account). */
function getOpenCodeZenPaidModels(): array
{
    return [
        'deepseek-v4-pro'   => 'DeepSeek V4 Pro',
        'deepseek-v4-flash' => 'DeepSeek V4 Flash',
        'glm-5'             => 'GLM 5',
        'glm-5.1'           => 'GLM 5.1',
        'kimi-k2.5'         => 'Kimi K2.5',
        'kimi-k2.6'         => 'Kimi K2.6',
        'minimax-m2.5'      => 'MiniMax M2.5',
        'minimax-m2.7'      => 'MiniMax M2.7',
    ];
}

/** OpenCode Go subscription models (endpoint: zen/go/v1/chat/completions). */
function getOpenCodeGoModels(): array
{
    return [
        'mimo-v2.5'         => 'MiMo V2.5 (Go)',
        'mimo-v2.5-pro'     => 'MiMo V2.5 Pro (Go)',
        'glm-5'             => 'GLM 5 (Go)',
        'glm-5.1'           => 'GLM 5.1 (Go)',
        'kimi-k2.5'         => 'Kimi K2.5 (Go)',
        'kimi-k2.6'         => 'Kimi K2.6 (Go)',
        'deepseek-v4-pro'   => 'DeepSeek V4 Pro (Go)',
        'deepseek-v4-flash' => 'DeepSeek V4 Flash (Go)',
        'qwen3.6-plus'      => 'Qwen 3.6 Plus (Go)',
        'qwen3.7-plus'      => 'Qwen 3.7 Plus (Go)',
        'qwen3.7-max'       => 'Qwen 3.7 Max (Go)',
    ];
}

function getGeminiModels(): array
{
    return [
        'gemini-2.5-pro'   => 'Gemini 2.5 Pro',
        'gemini-2.5-flash' => 'Gemini 2.5 Flash',
        'gemini-2.0-flash' => 'Gemini 2.0 Flash',
        'gemini-1.5-pro'   => 'Gemini 1.5 Pro',
    ];
}

function isOpenCodeZenFreeModel(string $model): bool
{
    return array_key_exists($model, getOpenCodeZenFreeModels());
}

function isOpenCodeGoModel(string $model): bool
{
    return array_key_exists($model, getOpenCodeGoModels());
}

function resolveOpenCodeApiUrl(PDO $pdo = null): string
{
    $model = resolveOpenCodeModel($pdo);

    if (isOpenCodeZenFreeModel($model)) {
        return 'https://opencode.ai/zen/v1/chat/completions';
    }

    if (isOpenCodeGoModel($model)) {
        return 'https://opencode.ai/zen/go/v1/chat/completions';
    }

    if (defined('OPENCODE_API_URL') && OPENCODE_API_URL !== '') {
        return OPENCODE_API_URL;
    }

    return 'https://opencode.ai/zen/v1/chat/completions';
}

function resolveOpenCodeModel(PDO $pdo = null): string
{
    if ($pdo) {
        $m = getAppSetting($pdo, 'opencode_model', null);
        if ($m) {
            return $m;
        }
    }
    return defined('OPENCODE_MODEL') ? OPENCODE_MODEL : 'mimo-v2.5-free';
}

/** Reasoning models often return thinking text instead of strict JSON — use MiMo for structured output. */
function resolveOpenCodeJsonModel(PDO $pdo = null): string
{
    $model = resolveOpenCodeModel($pdo);
    if (preg_match('/deepseek|nemotron|north-mini/i', $model) === 1) {
        return 'mimo-v2.5-free';
    }
    return $model;
}

function resolveGeminiModel(PDO $pdo = null): string
{
    if ($pdo) {
        $m = getAppSetting($pdo, 'gemini_model', null);
        if ($m) {
            return $m;
        }
    }
    return defined('GEMINI_MODEL') ? GEMINI_MODEL : 'gemini-2.5-pro';
}

function isAiApiKeyConfigured(): bool
{
    $provider = resolveAiProvider();
    if ($provider === 'opencode') {
        return defined('OPENCODE_API_KEY') && OPENCODE_API_KEY !== '';
    }
    return defined('GEMINI_API_KEY') && GEMINI_API_KEY !== '';
}

function resolveAiPromptLang(?PDO $pdo = null): string
{
    if (!$pdo instanceof PDO) {
        return 'vi';
    }
    $lang = getAppSetting($pdo, 'ai_system_prompt_lang', 'vi');
    return in_array($lang, ['vi', 'en'], true) ? $lang : 'vi';
}

function getAiSettings(PDO $pdo): array
{
    $provider = resolveAiProvider();
    if ($provider === 'opencode') {
        $model = resolveOpenCodeModel($pdo);
    } else {
        $model = resolveGeminiModel($pdo);
    }

    $settings = [
        'provider' => $provider,
        'model' => $model,
        'temperature' => (float)getAppSetting($pdo, 'ai_temperature', getAppSetting($pdo, 'gemini_temperature', '0.7')),
        'max_tokens' => (int)getAppSetting($pdo, 'ai_max_tokens', getAppSetting($pdo, 'gemini_max_tokens', '4000')),
        'ai_enabled' => getAppSetting($pdo, 'ai_enabled', '1') === '1',
        'system_prompt_lang' => resolveAiPromptLang($pdo),
        'api_key_configured' => isAiApiKeyConfigured(),
    ];

    if ($provider === 'opencode') {
        $settings['api_url'] = resolveOpenCodeApiUrl($pdo);
        $settings['zen_free_models'] = getOpenCodeZenFreeModels();
        $settings['zen_paid_models'] = getOpenCodeZenPaidModels();
        $settings['go_models'] = getOpenCodeGoModels();
        $settings['is_free_model'] = isOpenCodeZenFreeModel($model);
    }

    return $settings;
}

function isAiEnabled(PDO $pdo = null): bool
{
    if (!$pdo) {
        return true;
    }
    return getAppSetting($pdo, 'ai_enabled', '1') === '1';
}
