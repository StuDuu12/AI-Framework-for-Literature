<?php
/**
 * AI system prompts — Vietnamese / English, selected via app_settings.ai_system_prompt_lang
 */

require_once __DIR__ . '/helper_ai_settings.php';

function loadAiPromptFile(string $basename, string $lang): string
{
    $primary = __DIR__ . '/prompts/' . $basename . '.' . $lang . '.txt';
    if (is_file($primary)) {
        return (string) file_get_contents($primary);
    }
    $fallback = __DIR__ . '/prompts/' . $basename . '.vi.txt';
    return is_file($fallback) ? (string) file_get_contents($fallback) : '';
}

function getAiAnalyzePrompt(?PDO $pdo = null): string
{
    return loadAiPromptFile('analyze', resolveAiPromptLang($pdo));
}

function getAiChatPrompt(?PDO $pdo = null): string
{
    $lang = resolveAiPromptLang($pdo);
    if ($lang === 'en') {
        return 'You are a literary analysis expert. Based on the previous conversation and the current analysis, answer the user\'s question in depth. Be concise but complete. Use plain text; avoid complex markdown.';
    }
    return 'Bạn là một chuyên gia phân tích văn học. Dựa trên cuộc hội thoại trước đó và phân tích hiện tại, hãy trả lời câu hỏi của người dùng một cách chi tiết, chuyên sâu. Trả lời ngắn gọn, súc tích nhưng đầy đủ ý. Sử dụng văn bản thuần, không dùng markdown phức tạp.';
}

function getAiRefinePrompt(?PDO $pdo = null): string
{
    $lang = resolveAiPromptLang($pdo);
    if ($lang === 'en') {
        return 'You are a literary analysis expert. Based on the previous conversation and the current analysis results, answer the user\'s question in depth. If the user asks to adjust or extend the analysis, provide specific, well-grounded information. Return plain text (not JSON); you may use markdown for formatting when helpful.';
    }
    return 'Bạn là một chuyên gia phân tích văn học. Dựa trên cuộc hội thoại trước đó và kết quả phân tích hiện tại, hãy trả lời câu hỏi của người dùng một cách chi tiết, chuyên sâu. Nếu người dùng yêu cầu điều chỉnh hoặc bổ sung phân tích, hãy cung cấp thông tin cụ thể và có căn cứ. Trả về câu trả lời dạng văn bản thuần (không phải JSON), sử dụng markdown để format nếu cần.';
}

function getAiSlideshowPrompt(?PDO $pdo = null): string
{
    $lang = resolveAiPromptLang($pdo);
    if ($lang === 'en') {
        return 'You are a literature specialist. Based on the provided JSON analysis, create a concise slideshow presentation in English. Each slide should have a title and succinct content (simple HTML is allowed in content).';
    }
    return 'Bạn là chuyên gia văn học. Dựa trên kết quả phân tích JSON được cung cấp, tạo bài trình bày slideshow ngắn gọn bằng tiếng Việt. Mỗi slide có tiêu đề và nội dung súc tích (HTML đơn giản được phép trong content).';
}

function getAiQuizPrompt(?PDO $pdo = null): string
{
    $lang = resolveAiPromptLang($pdo);
    if ($lang === 'en') {
        return 'You are a literature teacher. Based on the provided JSON analysis, create 5–8 multiple-choice questions in English. Each question has 4 options; field "a" is the index of the correct answer (0–3).';
    }
    return 'Bạn là giáo viên văn học. Dựa trên kết quả phân tích JSON, tạo 5-8 câu hỏi trắc nghiệm tiếng Việt. Mỗi câu có 4 lựa chọn; trường "a" là chỉ số đáp án đúng (0-3).';
}

function getAiPromptLangLabel(string $lang): string
{
    return $lang === 'en' ? 'English' : 'Tiếng Việt';
}
