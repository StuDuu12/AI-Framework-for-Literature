<?php

/**
 * Algorithmic validation of the AI structural analysis outline (Fig. 10).
 */

function outlineValidationLabels(): array
{
    return [
        'figure' => 'Fig. 10',
        'title' => 'Algorithmic validation of the structural outline',
        'title_vi' => 'Kiểm tra thuật toán khung phân tích cấu trúc',
    ];
}

function isNonEmptyString($value): bool
{
    return is_string($value) && trim($value) !== '';
}

function isNonEmptyArray($value): bool
{
    return is_array($value) && count($value) > 0;
}

function checkOutlineField(array $data, string $path, string $label, bool $required = true): array
{
    $parts = explode('.', $path);
    $current = $data;
    $found = true;

    foreach ($parts as $part) {
        if (!is_array($current) || !array_key_exists($part, $current)) {
            $found = false;
            break;
        }
        $current = $current[$part];
    }

    $status = 'pass';
    $detail = 'Present';

    if (!$found) {
        $status = $required ? 'fail' : 'skip';
        $detail = $required ? 'Missing' : 'Optional — not provided';
    } elseif (is_string($current)) {
        if (!isNonEmptyString($current)) {
            $status = 'fail';
            $detail = 'Empty string';
        }
    } elseif (is_array($current)) {
        if (count($current) === 0) {
            $status = 'fail';
            $detail = 'Empty array';
        } else {
            $detail = count($current) . ' item(s)';
        }
    }

    return [
        'id' => str_replace('.', '_', $path),
        'path' => $path,
        'label' => $label,
        'required' => $required,
        'status' => $status,
        'detail' => $detail,
    ];
}

function validateStructuralOutline(array $data, array $context = []): array
{
    $labels = outlineValidationLabels();
    $sections = [];

    $sections[] = checkOutlineField($data, 'title', 'Title');
    $sections[] = checkOutlineField($data, 'characteristics.Thể loại', 'Genre (Thể loại)');
    $sections[] = checkOutlineField($data, 'characteristics.Phong cách', 'Style (Phong cách)');
    $sections[] = checkOutlineField($data, 'characteristics.Giọng điệu', 'Tone (Giọng điệu)');
    $sections[] = checkOutlineField($data, 'keywords', 'Keywords');
    $sections[] = checkOutlineField($data, 'generalAnalysis.thematicAnalysis', 'Thematic analysis blocks');
    $sections[] = checkOutlineField($data, 'generalAnalysis.linguisticInsights.motifs', 'Motifs');
    $sections[] = checkOutlineField($data, 'generalAnalysis.linguisticInsights.symbolsAndMetaphors', 'Symbols & metaphors');
    $sections[] = checkOutlineField($data, 'generalAnalysis.linguisticInsights.temporalElements', 'Temporal elements');
    $sections[] = checkOutlineField($data, 'generalAnalysis.linguisticInsights.natureAndScenery', 'Nature & scenery');
    $sections[] = checkOutlineField($data, 'concludingThoughts.lesson', 'Lesson');
    $sections[] = checkOutlineField($data, 'concludingThoughts.message', 'Message');
    $sections[] = checkOutlineField($data, 'intertextuality.initialAnalysis', 'Intertextuality — initial analysis');
    $sections[] = checkOutlineField($data, 'intertextuality.comparativeText.title', 'Comparative text — title');
    $sections[] = checkOutlineField($data, 'intertextuality.comparativeText.author', 'Comparative text — author');
    $sections[] = checkOutlineField($data, 'intertextuality.comparison.similarities', 'Comparison — similarities');
    $sections[] = checkOutlineField($data, 'intertextuality.comparison.differences', 'Comparison — differences');
    $sections[] = checkOutlineField($data, 'intertextuality.comparison.effects', 'Comparison — effects');

    $genreBlocks = [
        'poetryAnalysis' => ['Rhyming', 'Emotional flow', 'Structure', 'Imagery', 'Rhythm', 'Space-time', 'Rhetorical devices'],
        'storyAnalysis' => ['Character', 'Space', 'Time', 'Dialogue', 'Narrator', 'Situation', 'Prominent details'],
        'dramaAnalysis' => ['Characters', 'Conflict', 'Action', 'Structure', 'Dialogue'],
    ];

    $genreKeys = array_keys($genreBlocks);
    $activeGenres = [];
    foreach ($genreKeys as $genreKey) {
        if (!empty($data[$genreKey]) && is_array($data[$genreKey])) {
            $activeGenres[] = $genreKey;
        }
    }

    $genreRuleOk = count($activeGenres) === 1;
    $genreBlock = $genreRuleOk ? $activeGenres[0] : null;

    if ($genreBlock) {
        $subfields = [
            'poetryAnalysis' => ['rhyming', 'emotionalFlow', 'structure', 'imagery', 'rhythm', 'spaceTimeOrganization', 'rhetoricalDevices'],
            'storyAnalysis' => ['character', 'space', 'time', 'dialogue', 'narrator', 'situation', 'prominentDetails'],
            'dramaAnalysis' => ['characters', 'conflict', 'action', 'structure', 'dialogue'],
        ];
        foreach ($subfields[$genreBlock] as $sub) {
            $sections[] = checkOutlineField($data, $genreBlock . '.' . $sub, ucfirst($sub) . ' (' . $genreBlock . ')');
        }
    } else {
        $sections[] = [
            'id' => 'genre_block',
            'path' => 'genre',
            'label' => 'Genre block (exactly one required)',
            'required' => true,
            'status' => 'fail',
            'detail' => count($activeGenres) === 0
                ? 'No poetry/story/drama block found'
                : 'Multiple genre blocks: ' . implode(', ', $activeGenres),
        ];
    }

    $requiredSections = array_filter($sections, static fn($s) => !empty($s['required']));
    $passedCount = count(array_filter($requiredSections, static fn($s) => $s['status'] === 'pass'));
    $totalRequired = count($requiredSections);
    $score = $totalRequired > 0 ? (int)round(($passedCount / $totalRequired) * 100) : 0;
    $passed = $genreRuleOk && $passedCount === $totalRequired;

    $inputTruncated = !empty($context['input_truncated']) || !empty($data['_meta']['input_truncated']);

    $steps = [
        [
            'id' => 'ingest',
            'label' => 'Text ingestion',
            'label_vi' => 'Thu thập văn bản',
            'status' => 'pass',
            'detail' => $inputTruncated ? 'Input truncated for token limit' : 'Full input accepted',
        ],
        [
            'id' => 'schema_request',
            'label' => 'Schema-constrained AI request',
            'label_vi' => 'Gọi AI với ràng buộc JSON schema',
            'status' => 'pass',
            'detail' => 'response_format: json_object',
        ],
        [
            'id' => 'json_parse',
            'label' => 'JSON extraction & parse',
            'label_vi' => 'Trích xuất và parse JSON',
            'status' => 'pass',
            'detail' => 'parseJsonFromAiText',
        ],
        [
            'id' => 'schema_check',
            'label' => 'Structural outline schema check',
            'label_vi' => 'Kiểm tra schema khung phân tích',
            'status' => $passed ? 'pass' : 'fail',
            'detail' => $passedCount . '/' . $totalRequired . ' required fields valid',
        ],
        [
            'id' => 'genre_rule',
            'label' => 'Single genre block rule',
            'label_vi' => 'Quy tắc một khối thể loại',
            'status' => $genreRuleOk ? 'pass' : 'fail',
            'detail' => $genreBlock
                ? 'Active: ' . $genreBlock
                : (count($activeGenres) === 0 ? 'No genre block' : 'Multiple genre blocks'),
        ],
        [
            'id' => 'map_blocks',
            'label' => 'Map to editable content blocks',
            'label_vi' => 'Ánh xạ sang khối nội dung chỉnh sửa',
            'status' => $passed ? 'pass' : 'warn',
            'detail' => $passed ? 'Ready for student self-critique' : 'Partial mapping — review required',
        ],
    ];

    return [
        'figure' => $labels['figure'],
        'title' => $labels['title'],
        'title_vi' => $labels['title_vi'],
        'passed' => $passed,
        'score' => $score,
        'summary' => $passed
            ? 'Structural outline passed algorithmic validation.'
            : 'Structural outline failed one or more validation rules.',
        'summary_vi' => $passed
            ? 'Khung phân tích đã vượt qua kiểm tra thuật toán.'
            : 'Khung phân tích chưa đạt một hoặc nhiều quy tắc kiểm tra.',
        'steps' => $steps,
        'sections' => $sections,
        'genre_block' => $genreBlock,
        'genre_rule_ok' => $genreRuleOk,
        'validated_at' => date('c'),
    ];
}
