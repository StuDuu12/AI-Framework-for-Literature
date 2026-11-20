<?php
// Start session for authentication
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Nạp cấu hình (GEMINI_API_KEY nằm trong config/env.local.php — không hardcode)
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/helper_ai_settings.php';
require_once __DIR__ . '/../config/ai_system_prompts.php';

if (!isAiEnabled($pdo)) {
    http_response_code(503);
    echo json_encode(['error' => 'AI features are currently disabled by administrator.']);
    exit;
}

$aiProvider = resolveAiProvider();
if (!isAiApiKeyConfigured()) {
    http_response_code(500);
    $missing = $aiProvider === 'opencode' ? 'OPENCODE_API_KEY' : 'GEMINI_API_KEY';
    echo json_encode(['error' => "Missing {$missing} in config/env.local.php"]);
    exit;
}

// Set header to return JSON
header('Content-Type: application/json');

// Check authentication (skip if already validated by analyze_task.php)
if (!defined('AUTH_PASSED')) {
    $loggedIn = (!empty($_SESSION['user'])) || (!empty($_SESSION['user_id']));
    if (!$loggedIn) {
        http_response_code(401);
        echo json_encode(['error' => 'Unauthorized - Please login']);
        exit;
    }
}

// ── OpenCode.ai (Zen) — MiMo V2.5 — OpenAI-compatible chat/completions ─────
function openCodeApiUrl(): string
{
    global $pdo;
    return resolveOpenCodeApiUrl($pdo ?? null);
}

function parseOpenCodeErrorMessage(string $responseBody): string
{
    if ($responseBody === '') {
        return '';
    }
    $decoded = json_decode($responseBody, true);
    if (!is_array($decoded)) {
        return '';
    }
    $msg = $decoded['error']['message'] ?? $decoded['message'] ?? '';
    if (!is_string($msg) || $msg === '') {
        return '';
    }
    if (stripos($msg, 'No payment method') !== false || stripos($msg, 'CreditsError') !== false) {
        return 'OpenCode Go requires billing. Use Zen free tier: OPENCODE_API_URL=zen/v1/chat/completions and OPENCODE_MODEL=mimo-v2.5-free in env.local.php.';
    }
    if (stripos($msg, 'not supported') !== false) {
        return 'AI model/endpoint mismatch: ' . $msg . ' — check OPENCODE_API_URL and OPENCODE_MODEL in env.local.php.';
    }
    return 'AI API error: ' . $msg;
}

function contentsToOpenAiMessages(string $systemPrompt, array $contents): array
{
    $messages = [['role' => 'system', 'content' => $systemPrompt]];
    foreach ($contents as $item) {
        $role = $item['role'] ?? 'user';
        if ($role === 'model') {
            $role = 'assistant';
        }
        $text = '';
        if (!empty($item['parts']) && is_array($item['parts'])) {
            foreach ($item['parts'] as $part) {
                $text .= $part['text'] ?? '';
            }
        } elseif (isset($item['content'])) {
            $text = (string)$item['content'];
        }
        if ($text !== '') {
            $messages[] = ['role' => $role, 'content' => $text];
        }
    }
    return $messages;
}

/**
 * Chuẩn hoá lịch sử hội thoại do client gửi lên trước khi chuyển cho AI:
 *  - chỉ chấp nhận vai trò user / model (assistant được đổi thành model) — client
 *    KHÔNG được chèn tin nhắn 'system' để ghi đè system prompt (prompt injection);
 *  - giới hạn số lượt, độ dài mỗi tin và tổng độ dài để chống đốt token.
 */
function sanitizeAiHistory($history, int $maxTurns = 30, int $maxCharsPerTurn = 8000, int $maxTotalChars = 60000): array
{
    if (!is_array($history)) {
        return [];
    }

    $clean = [];
    foreach ($history as $item) {
        if (!is_array($item)) {
            continue;
        }
        $role = $item['role'] ?? 'user';
        if ($role === 'assistant') {
            $role = 'model';
        }
        if (!in_array($role, ['user', 'model'], true)) {
            continue; // bỏ 'system' và mọi vai trò lạ
        }

        $text = '';
        if (!empty($item['parts']) && is_array($item['parts'])) {
            foreach ($item['parts'] as $part) {
                if (is_array($part) && isset($part['text']) && is_scalar($part['text'])) {
                    $text .= (string)$part['text'];
                }
            }
        } elseif (isset($item['content']) && is_scalar($item['content'])) {
            $text = (string)$item['content'];
        }

        $text = trim($text);
        if ($text === '') {
            continue;
        }
        $clean[] = ['role' => $role, 'parts' => [['text' => mb_substr($text, 0, $maxCharsPerTurn)]]];
    }

    // Giữ các lượt gần nhất, rồi cắt bớt từ đầu cho tới khi tổng độ dài nằm trong giới hạn
    $clean = array_slice($clean, -$maxTurns);
    $total = array_sum(array_map(static fn($m) => mb_strlen($m['parts'][0]['text']), $clean));
    while ($total > $maxTotalChars && count($clean) > 1) {
        $removed = array_shift($clean);
        $total -= mb_strlen($removed['parts'][0]['text']);
    }

    return $clean;
}

function isOpenCodeReasoningModel(string $model): bool
{
    return preg_match('/deepseek|nemotron|north-mini/i', $model) === 1;
}

function openCodeReasoningHint(): string
{
    return "\n\nIMPORTANT: Put the complete user-facing answer in the main response body. "
        . "Do not spend all tokens on internal reasoning; keep reasoning brief.";
}

function extractOpenCodeChoice(?array $body): ?array
{
    if (!is_array($body) || empty($body['choices'][0]) || !is_array($body['choices'][0])) {
        return null;
    }
    return $body['choices'][0];
}

/**
 * Some Zen models (e.g. deepseek-v4-flash-free) return an empty message.content
 * when max_tokens is consumed by reasoning_content.
 */
function extractOpenCodeAssistantText(?array $body, bool $jsonMode = false): ?string
{
    $choice = extractOpenCodeChoice($body);
    if (!$choice) {
        return null;
    }

    $message = $choice['message'] ?? null;
    if (is_array($message)) {
        $content = trim((string)($message['content'] ?? ''));
        if ($content !== '') {
            return $content;
        }

        if ($jsonMode) {
            $reasoning = trim((string)($message['reasoning_content'] ?? ''));
            if ($reasoning !== '' && preg_match('/^[\[{]/', $reasoning)) {
                return $reasoning;
            }
            return null;
        }

        $reasoning = trim((string)($message['reasoning_content'] ?? ''));
        if ($reasoning !== '') {
            return $reasoning;
        }
    }

    $legacy = trim((string)($choice['text'] ?? ''));
    return $legacy !== '' ? $legacy : null;
}

function extractJsonCandidateFromText(string $text): string
{
    $text = trim($text);
    $text = preg_replace('/^\xEF\xBB\xBF/', '', $text);
    $text = preg_replace('/^```(?:json)?\s*/i', '', $text);
    $text = preg_replace('/\s*```\s*$/', '', $text);
    $text = trim($text);

    $start = strpos($text, '{');
    if ($start === false) {
        $start = strpos($text, '[');
    }
    if ($start === false) {
        return $text;
    }

    $sub = substr($text, $start);
    $depth = 0;
    $inString = false;
    $escape = false;
    $end = -1;
    $len = strlen($sub);
    for ($i = 0; $i < $len; $i++) {
        $ch = $sub[$i];
        if ($inString) {
            if ($escape) {
                $escape = false;
                continue;
            }
            if ($ch === '\\') {
                $escape = true;
                continue;
            }
            if ($ch === '"') {
                $inString = false;
            }
            continue;
        }
        if ($ch === '"') {
            $inString = true;
            continue;
        }
        if ($ch === '{' || $ch === '[') {
            $depth++;
        } elseif ($ch === '}' || $ch === ']') {
            $depth--;
            if ($depth === 0) {
                $end = $i;
                break;
            }
        }
    }

    return $end >= 0 ? substr($sub, 0, $end + 1) : $sub;
}

function repairTruncatedJson(string $json): string
{
    $json = rtrim($json);
    $json = preg_replace('/,\s*"[^"]*":\s*"[^"]*$/s', '', $json);
    $json = preg_replace('/,\s*"[^"]*":\s*$/s', '', $json);
    $json = preg_replace('/,\s*$/', '', $json);

    $openCurly = substr_count($json, '{') - substr_count($json, '}');
    $openSquare = substr_count($json, '[') - substr_count($json, ']');

    return $json . str_repeat(']', max(0, $openSquare)) . str_repeat('}', max(0, $openCurly));
}

function parseJsonFromAiText(string $text): ?array
{
    $text = trim($text);
    if ($text === '') {
        return null;
    }

    $candidates = array_unique([
        $text,
        extractJsonCandidateFromText($text),
        repairTruncatedJson(extractJsonCandidateFromText($text)),
    ]);

    foreach ($candidates as $candidate) {
        if ($candidate === '') {
            continue;
        }
        $decoded = json_decode($candidate, true);
        if (is_array($decoded)) {
            return $decoded;
        }
    }

    return null;
}

function getOpenCodeFinishReason(?array $body): string
{
    $choice = extractOpenCodeChoice($body);
    return is_array($choice) ? (string)($choice['finish_reason'] ?? '') : '';
}

function logOpenCodeStructureFailure(?array $body, string $context): void
{
    $choice = extractOpenCodeChoice($body);
    $message = is_array($choice) ? ($choice['message'] ?? []) : [];
    $keys = is_array($body) ? implode(',', array_keys($body)) : 'null';
    $messageKeys = is_array($message) ? implode(',', array_keys($message)) : 'none';
    error_log(sprintf(
        'OpenCode invalid structure (%s): body_keys=%s message_keys=%s finish=%s',
        $context,
        $keys,
        $messageKeys,
        getOpenCodeFinishReason($body)
    ));
}

function openCodeRequest(array $payload, int $timeout = 180): array
{
    $jsonData = json_encode($payload, JSON_UNESCAPED_UNICODE);

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, openCodeApiUrl());
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonData);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
    curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Authorization: Bearer ' . OPENCODE_API_KEY,
    ]);

    $response = curl_exec($ch);
    $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);

    if ($httpcode != 200) {
        $apiMessage = parseOpenCodeErrorMessage((string)$response);
        error_log("OpenCode API error (HTTP $httpcode): $error | body: " . substr((string)$response, 0, 500));
        return [
            'error' => $apiMessage ?: 'AI service is temporarily unavailable. Please try again.',
            'httpcode' => $httpcode,
        ];
    }

    $decoded = json_decode((string)$response, true);
    if (!is_array($decoded)) {
        error_log('OpenCode API returned non-JSON body: ' . substr((string)$response, 0, 500));
        return ['error' => 'AI returned an invalid response format.'];
    }

    return ['body' => $decoded];
}

function requestOpenCodeAssistantText(array $payload, int $timeout = 180, string $context = 'request'): array
{
    $jsonMode = ($context === 'json');
    $model = (string)($payload['model'] ?? '');
    if (isOpenCodeReasoningModel($model)) {
        $minTokens = $jsonMode ? 6000 : 6000;
        $payload['max_tokens'] = max((int)($payload['max_tokens'] ?? 0), $minTokens);
    }

    $result = openCodeRequest($payload, $timeout);
    if (isset($result['error'])) {
        return $result;
    }

    $body = $result['body'] ?? null;
    $text = extractOpenCodeAssistantText($body, $jsonMode);
    if ($text !== null && $text !== '') {
        return ['body' => $body, 'text' => $text];
    }

    $finish = getOpenCodeFinishReason($body);
    if ($finish === 'length' || isOpenCodeReasoningModel($model)) {
        $retryPayload = $payload;
        $retryPayload['max_tokens'] = min(12000, max((int)($retryPayload['max_tokens'] ?? 0) * 2, 8000));

        $messages = $retryPayload['messages'] ?? [];
        $hint = $jsonMode
            ? "\n\nIMPORTANT: Return ONLY one valid JSON object in the main response. No markdown, no reasoning text."
            : openCodeReasoningHint();
        if (!empty($messages[0]['role']) && $messages[0]['role'] === 'system') {
            $messages[0]['content'] = rtrim((string)$messages[0]['content']) . $hint;
        } else {
            array_unshift($messages, ['role' => 'system', 'content' => trim($hint)]);
        }
        $retryPayload['messages'] = $messages;

        $retry = openCodeRequest($retryPayload, max($timeout, 180));
        if (!isset($retry['error'])) {
            $retryText = extractOpenCodeAssistantText($retry['body'] ?? null, $jsonMode);
            if ($retryText !== null && $retryText !== '') {
                return ['body' => $retry['body'], 'text' => $retryText];
            }
            $body = $retry['body'] ?? $body;
        }
    }

    logOpenCodeStructureFailure($body, $context);
    return [
        'error' => 'Invalid response structure from API.',
        'body' => $body,
    ];
}

function getCompactAnalyzeSchemaHint(): string
{
    return 'Return ONE valid JSON object (no markdown). Required keys: title, characteristics (Thể loại, Phong cách, Giọng điệu), keywords[], generalAnalysis (thematicAnalysis[{heading,content}], linguisticInsights), concludingThoughts (lesson, message), intertextuality (initialAnalysis, comparativeText{title,author}, comparison{similarities,differences,effects}). Create EXACTLY ONE genre block: poetryAnalysis OR storyAnalysis OR dramaAnalysis — never more than one.';
}

function callOpenCodeJsonApi($systemPrompt, $contents, $schema, array $options = []): array
{
    global $pdo;
    $compactSchema = !empty($options['compact_schema']);
    if ($compactSchema) {
        $jsonSystem = $systemPrompt . "\n\n───────────────────────────────\n"
            . "OUTPUT: Return ONLY one valid JSON object (no markdown, no extra text).\n"
            . getCompactAnalyzeSchemaHint();
    } else {
        $schemaJson = json_encode($schema, JSON_UNESCAPED_UNICODE);
        $jsonSystem = $systemPrompt . "\n\n───────────────────────────────\n"
            . "YÊU CẦU ĐẦU RA: Trả về DUY NHẤT một JSON object hợp lệ (không markdown, không giải thích thêm), tuân thủ schema:\n"
            . $schemaJson;
    }

    $defaultMax = (int)getAppSetting($pdo, 'ai_max_tokens', getAppSetting($pdo, 'gemini_max_tokens', '6000'));
    $maxTokens = max(6000, (int)($options['max_tokens'] ?? $defaultMax));
    $temperature = (float)($options['temperature'] ?? getAppSetting($pdo, 'ai_temperature', getAppSetting($pdo, 'gemini_temperature', '0.7')));

    $model = resolveOpenCodeJsonModel($pdo ?? null);
    $payload = [
        'model' => $model,
        'messages' => contentsToOpenAiMessages($jsonSystem, $contents),
        'temperature' => $temperature,
        'max_tokens' => $maxTokens,
        'response_format' => ['type' => 'json_object'],
    ];

    $timeout = (int)($options['timeout'] ?? 180);
    $result = requestOpenCodeAssistantText($payload, $timeout, 'json');
    if (isset($result['error'])) {
        return ['error' => $result['error']];
    }

    $text = $result['text'] ?? '';
    $decoded = parseJsonFromAiText($text);
    if (!is_array($decoded) && $model !== 'mimo-v2.5-free') {
        $payload['model'] = 'mimo-v2.5-free';
        $payload['max_tokens'] = max($maxTokens, 6000);
        $retry = requestOpenCodeAssistantText($payload, $timeout, 'json');
        if (!isset($retry['error'])) {
            $decoded = parseJsonFromAiText($retry['text'] ?? '');
        }
    }

    if (!is_array($decoded)) {
        error_log('OpenCode invalid JSON (model=' . $model . '): ' . substr($text, 0, 400));
        return ['error' => 'AI returned invalid JSON.'];
    }

    return $decoded;
}

function callOpenCodeFreeform(string $systemPrompt, array $contents, array $generationConfig = []): array
{
    global $pdo;
    $payload = [
        'model' => resolveOpenCodeModel($pdo ?? null),
        'messages' => contentsToOpenAiMessages($systemPrompt, $contents),
        'temperature' => (float)($generationConfig['temperature'] ?? getAppSetting($pdo, 'ai_temperature', getAppSetting($pdo, 'gemini_temperature', '0.7'))),
        'max_tokens' => (int)($generationConfig['maxOutputTokens'] ?? $generationConfig['max_tokens'] ?? getAppSetting($pdo, 'ai_max_tokens', getAppSetting($pdo, 'gemini_max_tokens', '4000'))),
    ];

    $result = requestOpenCodeAssistantText($payload, 180, 'freeform');
    if (isset($result['error'])) {
        return ['error' => $result['error']];
    }

    $text = $result['text'] ?? '';
    return ['answer' => $text, 'response' => $text, 'message' => $text];
}

/*
 * ── Google Gemini (dự phòng) ───────────────────────────────────────────────
 * Bỏ comment block này và đổi AI_PROVIDER = 'gemini' trong env.local.php để dùng lại.
 *
function geminiApiUrl(): string
{
    global $pdo;
    $model = resolveGeminiModel($pdo ?? null);
    return "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent";
}

function geminiRequest(array $payload, int $timeout = 180): array
{
    $apiKey = GEMINI_API_KEY;
    $jsonData = json_encode($payload);

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, geminiApiUrl());
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonData);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
    curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'x-goog-api-key: ' . $apiKey,
        'Content-Length: ' . strlen($jsonData)
    ]);

    $response = curl_exec($ch);
    $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);

    if ($httpcode != 200) {
        error_log("Gemini API error (HTTP $httpcode): $error | body: " . substr((string)$response, 0, 500));
        return ['error' => 'AI service is temporarily unavailable. Please try again.', 'httpcode' => $httpcode];
    }

    return ['body' => json_decode($response, true)];
}

function callGeminiApi($systemPrompt, $contents, $schema)
{
    $payload = [
        'contents' => $contents,
        'systemInstruction' => [
            'parts' => [['text' => $systemPrompt]]
        ],
        'generationConfig' => [
            'responseMimeType' => 'application/json',
            'responseSchema' => $schema
        ]
    ];

    $result = geminiRequest($payload, 180);
    if (isset($result['error'])) {
        return ['error' => $result['error']];
    }

    $body = $result['body'];
    if (isset($body['candidates'][0]['content']['parts'][0]['text'])) {
        return json_decode($body['candidates'][0]['content']['parts'][0]['text'], true);
    }

    return ['error' => 'Invalid response structure from API.'];
}

function callGeminiFreeform(string $systemPrompt, array $contents, array $generationConfig = []): array
{
    global $pdo;
    $defaults = [
        'temperature' => (float)getAppSetting($pdo, 'gemini_temperature', '0.7'),
        'maxOutputTokens' => (int)getAppSetting($pdo, 'gemini_max_tokens', '2000')
    ];
    $payload = [
        'contents' => $contents,
        'systemInstruction' => [
            'parts' => [['text' => $systemPrompt]]
        ],
        'generationConfig' => array_merge($defaults, $generationConfig)
    ];

    $result = geminiRequest($payload, 120);
    if (isset($result['error'])) {
        return ['error' => $result['error']];
    }

    $body = $result['body'];
    if (isset($body['candidates'][0]['content']['parts'][0]['text'])) {
        $text = $body['candidates'][0]['content']['parts'][0]['text'];
        return ['answer' => $text, 'response' => $text, 'message' => $text];
    }

    return ['error' => 'Invalid response structure from API.'];
}
 */

// ── Provider router (đổi AI_PROVIDER trong env.local.php để chuyển backend) ───
function callAiJsonApi($systemPrompt, $contents, $schema, array $options = []): array
{
    if (resolveAiProvider() === 'gemini') {
        // return callGeminiApi($systemPrompt, $contents, $schema);
        return ['error' => 'Gemini provider is commented out. Set AI_PROVIDER=opencode or uncomment Gemini functions.'];
    }
    return callOpenCodeJsonApi($systemPrompt, $contents, $schema, $options);
}

/** Limit input size sent to the model (long stories slow down free-tier APIs). */
function truncateAnalysisInput(string $text, int $maxChars = 12000): array
{
    $len = mb_strlen($text, 'UTF-8');
    if ($len <= $maxChars) {
        return ['text' => $text, 'truncated' => false];
    }
    $cut = mb_substr($text, 0, $maxChars, 'UTF-8');
    return [
        'text' => $cut . "\n\n[... text truncated for analysis — " . ($len - $maxChars) . " characters omitted ...]",
        'truncated' => true,
    ];
}

function callAiFreeform(string $systemPrompt, array $contents, array $generationConfig = []): array
{
    if (resolveAiProvider() === 'gemini') {
        // return callGeminiFreeform($systemPrompt, $contents, $generationConfig);
        return ['error' => 'Gemini provider is commented out. Set AI_PROVIDER=opencode or uncomment Gemini functions.'];
    }
    return callOpenCodeFreeform($systemPrompt, $contents, $generationConfig);
}

// --- Get Request Data ---
// Check if called from analyze_task.php (data in $_POST) or directly (data in php://input)
if (defined('AUTH_PASSED') && isset($_POST['action'])) {
    // Called from analyze_task.php
    $action = $_POST['action'] ?? '';
    $data = $_POST['data'] ?? '';
} else {
    // Direct call - read from JSON body
    $input = json_decode(file_get_contents('php://input'), true);
    $action = $input['action'] ?? '';
    $data = $input['data'] ?? '';
}

$response = null;

// --- Main Action Switch ---
switch ($action) {
    case 'analyze':
        $systemPrompt = getAiAnalyzePrompt($pdo);

        $schema = [
            "type" => "OBJECT",
            "properties" => [
                "title" => ["type" => "STRING"],
                "characteristics" => [
                    "type" => "OBJECT",
                    "properties" => ["Thể loại" => ["type" => "STRING"], "Phong cách" => ["type" => "STRING"], "Giọng điệu" => ["type" => "STRING"],],
                    "required" => ["Thể loại", "Phong cách", "Giọng điệu"]
                ],
                "keywords" => ["type" => "ARRAY", "items" => ["type" => "STRING"]],
                "generalAnalysis" => [
                    "type" => "OBJECT",
                    "properties" => [
                        "thematicAnalysis" => [
                            "type" => "ARRAY",
                            "items" => [
                                "type" => "OBJECT",
                                "properties" => ["heading" => ["type" => "STRING"], "content" => ["type" => "STRING"],],
                                "required" => ["heading", "content"]
                            ]
                        ],
                        "linguisticInsights" => [
                            "type" => "OBJECT",
                            "properties" => [
                                "motifs" => ["type" => "ARRAY", "items" => ["type" => "STRING"]],
                                "symbolsAndMetaphors" => ["type" => "ARRAY", "items" => ["type" => "STRING"]],
                                "temporalElements" => ["type" => "STRING"],
                                "natureAndScenery" => ["type" => "STRING"]
                            ],
                            "required" => ["motifs", "symbolsAndMetaphors", "temporalElements", "natureAndScenery"]
                        ]
                    ],
                    "required" => ["thematicAnalysis", "linguisticInsights"]
                ],
                "poetryAnalysis" => [
                    "type" => "OBJECT",
                    "properties" => [
                        "rhyming" => ["type" => "STRING"],
                        "emotionalFlow" => ["type" => "STRING"],
                        "structure" => ["type" => "STRING"],
                        "imagery" => ["type" => "STRING"],
                        "rhythm" => ["type" => "STRING"],
                        "spaceTimeOrganization" => ["type" => "STRING"],
                        "rhetoricalDevices" => ["type" => "STRING"]
                    ],
                    "required" => ["rhyming", "emotionalFlow", "structure", "imagery", "rhythm", "spaceTimeOrganization", "rhetoricalDevices"]
                ],
                "storyAnalysis" => [
                    "type" => "OBJECT",
                    "properties" => [
                        "character" => ["type" => "STRING"],
                        "space" => ["type" => "STRING"],
                        "time" => ["type" => "STRING"],
                        "dialogue" => ["type" => "STRING"],
                        "narrator" => ["type" => "STRING"],
                        "situation" => ["type" => "STRING"],
                        "prominentDetails" => ["type" => "STRING"]
                    ],
                    "required" => ["character", "space", "time", "dialogue", "narrator", "situation", "prominentDetails"]
                ],
                "dramaAnalysis" => [
                    "type" => "OBJECT",
                    "properties" => [
                        "characters" => ["type" => "STRING"],
                        "conflict" => ["type" => "STRING"],
                        "action" => ["type" => "STRING"],
                        "structure" => ["type" => "STRING"],
                        "dialogue" => ["type" => "STRING"]
                    ],
                    "required" => ["characters", "conflict", "action", "structure", "dialogue"]
                ],
                "concludingThoughts" => [
                    "type" => "OBJECT",
                    "properties" => [
                        "lesson" => ["type" => "STRING"],
                        "message" => ["type" => "STRING"]
                    ],
                    "required" => ["lesson", "message"]
                ],
                "intertextuality" => [
                    "type" => "OBJECT",
                    "properties" => [
                        "initialAnalysis" => ["type" => "STRING"],
                        "comparativeText" => [
                            "type" => "OBJECT",
                            "properties" => [
                                "title" => ["type" => "STRING"],
                                "author" => ["type" => "STRING"]
                            ],
                            "required" => ["title", "author"]
                        ],
                        "comparison" => [
                            "type" => "OBJECT",
                            "properties" => [
                                "similarities" => ["type" => "STRING"],
                                "differences" => ["type" => "STRING"],
                                "effects" => ["type" => "STRING"]
                            ],
                            "required" => ["similarities", "differences", "effects"]
                        ]
                    ],
                    "required" => ["initialAnalysis", "comparativeText", "comparison"]
                ]
            ],
            "required" => ["title", "characteristics", "keywords", "generalAnalysis", "concludingThoughts", "intertextuality"]
        ];

        $input = truncateAnalysisInput((string)$data);
        $contents = [
            ['role' => 'user', 'parts' => [['text' => $input['text']]]]
        ];

        $analyzeMaxTokens = max(6000, (int)getAppSetting($pdo, 'ai_max_tokens', 6000));
        $analyzeTemp = min(0.8, (float)getAppSetting($pdo, 'ai_temperature', 0.7));

        $response = callAiJsonApi($systemPrompt, $contents, $schema, [
            'compact_schema' => true,
            'max_tokens' => $analyzeMaxTokens,
            'temperature' => $analyzeTemp,
        ]);

        if (is_array($response) && !isset($response['error'])) {
            if ($input['truncated']) {
                $response['_meta'] = ['input_truncated' => true];
            }
            require_once __DIR__ . '/../config/helper_outline_validation.php';
            $response['_validation'] = validateStructuralOutline($response, [
                'input_truncated' => !empty($input['truncated']),
            ]);
        }
        break;

    case 'chat':
        $input = json_decode(file_get_contents('php://input'), true);
        $history = sanitizeAiHistory($input['history'] ?? []);
        $userMessage = $input['data'] ?? '';

        if (empty($history)) {
            $response = ['error' => 'No conversation history provided'];
            break;
        }

        $systemPrompt = getAiChatPrompt($pdo);
        $response = callAiFreeform($systemPrompt, $history);
        break;

    case 'refine':
        $requestData = json_decode((string)$data, true);
        $history = sanitizeAiHistory($requestData['history'] ?? []);

        if (empty($history)) {
            $response = ['error' => 'No conversation history provided'];
            break;
        }

        $systemPrompt = getAiRefinePrompt($pdo);
        $response = callAiFreeform($systemPrompt, $history, [
            'temperature' => 0.8,
            'maxOutputTokens' => 4000
        ]);
        break;

    case 'slideshow':
        $systemPrompt = getAiSlideshowPrompt($pdo);
        $schema = [
            'type' => 'object',
            'properties' => [
                'slides' => [
                    'type' => 'array',
                    'items' => [
                        'type' => 'object',
                        'properties' => [
                            'heading' => ['type' => 'string'],
                            'content' => ['type' => 'string']
                        ],
                        'required' => ['heading', 'content']
                    ]
                ],
                'references' => [
                    'type' => 'array',
                    'items' => ['type' => 'string']
                ]
            ],
            'required' => ['slides']
        ];
        $contents = [['role' => 'user', 'parts' => [['text' => mb_substr((string)$data, 0, 12000)]]]];
        $response = callAiJsonApi($systemPrompt, $contents, $schema);
        break;

    case 'quiz':
        $systemPrompt = getAiQuizPrompt($pdo);
        $schema = [
            'type' => 'object',
            'properties' => [
                'quiz' => [
                    'type' => 'array',
                    'items' => [
                        'type' => 'object',
                        'properties' => [
                            'q' => ['type' => 'string'],
                            'o' => [
                                'type' => 'array',
                                'items' => ['type' => 'string']
                            ],
                            'a' => ['type' => 'integer']
                        ],
                        'required' => ['q', 'o', 'a']
                    ]
                ]
            ],
            'required' => ['quiz']
        ];
        $contents = [['role' => 'user', 'parts' => [['text' => mb_substr((string)$data, 0, 12000)]]]];
        $response = callAiJsonApi($systemPrompt, $contents, $schema);
        break;

    default:
        $response = ['error' => 'Invalid action provided.'];
        break;
}

// Echo the final JSON response
echo json_encode($response);