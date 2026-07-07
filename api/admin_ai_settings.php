<?php
header('Content-Type: application/json');
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/helper_ai_settings.php';

$role = $_SESSION['user']['role'] ?? $_SESSION['role'] ?? '';
if ($role !== 'admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Admin only']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    echo json_encode(['success' => true, 'settings' => getAiSettings($pdo)]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $model = trim($input['model'] ?? '');
    $temperature = $input['temperature'] ?? '0.7';
    $maxTokens = (int)($input['max_tokens'] ?? 4000);
    $aiEnabled = !empty($input['ai_enabled']) ? '1' : '0';
    $promptLang = $input['system_prompt_lang'] ?? 'vi';
    if (!in_array($promptLang, ['vi', 'en'], true)) {
        $promptLang = 'vi';
    }

    if ($model === '') {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Model is required']);
        exit;
    }

    $provider = resolveAiProvider();
    if ($provider === 'opencode') {
        $allowed = array_merge(
            array_keys(getOpenCodeZenFreeModels()),
            array_keys(getOpenCodeZenPaidModels()),
            array_keys(getOpenCodeGoModels())
        );
        if (!in_array($model, $allowed, true)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Unknown OpenCode model: ' . $model]);
            exit;
        }
    } else {
        if (!array_key_exists($model, getGeminiModels())) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Unknown Gemini model: ' . $model]);
            exit;
        }
    }

    $temp = max(0, min(2, (float)$temperature));
    $maxTokens = max(256, min(8192, $maxTokens));

    if ($provider === 'opencode') {
        setAppSetting($pdo, 'opencode_model', $model);
    } else {
        setAppSetting($pdo, 'gemini_model', $model);
    }
    setAppSetting($pdo, 'ai_temperature', (string)$temp);
    setAppSetting($pdo, 'ai_max_tokens', (string)$maxTokens);
    setAppSetting($pdo, 'gemini_temperature', (string)$temp);
    setAppSetting($pdo, 'gemini_max_tokens', (string)$maxTokens);
    setAppSetting($pdo, 'ai_enabled', $aiEnabled);
    setAppSetting($pdo, 'ai_system_prompt_lang', $promptLang);

    echo json_encode(['success' => true, 'settings' => getAiSettings($pdo)]);
    exit;
}

http_response_code(405);
echo json_encode(['success' => false, 'message' => 'Method not allowed']);
