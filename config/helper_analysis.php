<?php
/**
 * Helpers for literary analysis version history and confirmed submissions.
 */

require_once __DIR__ . '/helper_homework.php';
require_once __DIR__ . '/helper_task.php';

/**
 * Convert analysis JSON to ordered markdown sections (mirrors genAI extractAnalysisContent).
 *
 * @param array $result
 * @return array<int, string>
 */
function analysisDataToContentSections(array $result): array
{
    $content = [];

    if (!empty($result['title'])) {
        $content[] = '# ' . $result['title'];
    }

    if (!empty($result['keywords']) && is_array($result['keywords'])) {
        $content[] = '**Keywords:** ' . implode(', ', $result['keywords']);
    }

    if (!empty($result['generalAnalysis']['thematicAnalysis']) && is_array($result['generalAnalysis']['thematicAnalysis'])) {
        foreach ($result['generalAnalysis']['thematicAnalysis'] as $section) {
            $heading = $section['heading'] ?? 'Section';
            $body = $section['content'] ?? '';
            $content[] = "## {$heading}\n{$body}";
        }
    }

    if (!empty($result['generalAnalysis']['linguisticInsights'])) {
        $insights = $result['generalAnalysis']['linguisticInsights'];
        $linguisticText = "## Language & Symbolism\n";

        if (!empty($insights['motifs']) && is_array($insights['motifs'])) {
            $linguisticText .= "\n**Recurring Motifs:**\n" . implode("\n", array_map(fn($m) => "- {$m}", $insights['motifs']));
        }
        if (!empty($insights['symbolsAndMetaphors']) && is_array($insights['symbolsAndMetaphors'])) {
            $linguisticText .= "\n**Symbols & Metaphors:**\n" . implode("\n", array_map(fn($s) => "- {$s}", $insights['symbolsAndMetaphors']));
        }
        if (!empty($insights['temporalElements'])) {
            $linguisticText .= "\n**Temporal Elements:** {$insights['temporalElements']}";
        }
        if (!empty($insights['natureAndScenery'])) {
            $linguisticText .= "\n**Nature & Scenery:** {$insights['natureAndScenery']}";
        }

        $content[] = $linguisticText;
    }

    if (!empty($result['storyAnalysis'])) {
        $sa = $result['storyAnalysis'];
        $map = [
            'character' => 'Characters',
            'space' => 'Space',
            'time' => 'Time',
            'dialogue' => 'Dialogue & Inner Monologue',
            'narrator' => 'Narrator',
            'situation' => 'Story Situation',
            'prominentDetails' => 'Prominent Details',
        ];
        foreach ($map as $key => $label) {
            if (!empty($sa[$key])) {
                $content[] = "## {$label}\n{$sa[$key]}";
            }
        }
    }

    if (!empty($result['dramaAnalysis'])) {
        $da = $result['dramaAnalysis'];
        $map = [
            'characters' => 'Characters (Drama)',
            'conflict' => 'Conflict',
            'action' => 'Action',
            'structure' => 'Structure',
            'dialogue' => 'Dialogue',
        ];
        foreach ($map as $key => $label) {
            if (!empty($da[$key])) {
                $content[] = "## {$label}\n{$da[$key]}";
            }
        }
    }

    if (!empty($result['poetryAnalysis'])) {
        $pa = $result['poetryAnalysis'];
        $map = [
            'rhyming' => 'Rhyming in Poetry',
            'emotionalFlow' => 'Emotional Flow',
            'structure' => 'Poem Structure',
            'imagery' => 'Imagery in Poetry',
            'rhythm' => 'Rhythm Analysis',
            'spaceTimeOrganization' => 'Space & Time Organization',
            'rhetoricalDevices' => 'Rhetorical Devices',
        ];
        foreach ($map as $key => $label) {
            if (!empty($pa[$key])) {
                $content[] = "## {$label}\n{$pa[$key]}";
            }
        }
    }

    if (!empty($result['concludingThoughts'])) {
        $ct = $result['concludingThoughts'];
        if (!empty($ct['lesson'])) {
            $content[] = "## Lesson\n{$ct['lesson']}";
        }
        if (!empty($ct['message'])) {
            $content[] = "## Message\n{$ct['message']}";
        }
    }

    if (!empty($result['intertextuality'])) {
        $it = $result['intertextuality'];
        $intertextText = "## Analysis Intertextuality\n";
        if (!empty($it['initialAnalysis'])) {
            $intertextText .= $it['initialAnalysis'] . "\n";
        }
        if (!empty($it['comparativeText'])) {
            $title = $it['comparativeText']['title'] ?? '';
            $author = $it['comparativeText']['author'] ?? '';
            $intertextText .= "\n**Comparison with:** {$title} - {$author}\n";
        }
        if (!empty($it['comparison'])) {
            $cmp = $it['comparison'];
            if (!empty($cmp['similarities'])) {
                $intertextText .= "\n**Similarities:** {$cmp['similarities']}\n";
            }
            if (!empty($cmp['differences'])) {
                $intertextText .= "\n**Differences:** {$cmp['differences']}\n";
            }
            if (!empty($cmp['effects'])) {
                $intertextText .= "\n**Effects:** {$cmp['effects']}";
            }
        }
        $content[] = $intertextText;
    }

    return $content;
}

/**
 * Apply analysis data to an existing response (json_ai + content blocks).
 */
function applyAnalysisDataToResponse(int $responseId, array $analysisData, int $userId): void
{
    global $pdo;

    $json_ai = json_encode($analysisData, JSON_UNESCAPED_UNICODE);
    $stmt = $pdo->prepare("
        UPDATE response
        SET json_ai = :json_ai, updated_at = NOW()
        WHERE id_response = :response_id
    ");
    $stmt->execute([
        ':json_ai' => $json_ai,
        ':response_id' => $responseId,
    ]);

    $sections = analysisDataToContentSections($analysisData);
    $existingContent = getContentByResponse($responseId);

    if (!empty($existingContent)) {
        $existingByOrder = [];
        foreach ($existingContent as $row) {
            $existingByOrder[(int)$row['order']] = $row;
        }

        foreach ($sections as $index => $sectionContent) {
            $order = $index;
            if (isset($existingByOrder[$order])) {
                $contentId = $existingByOrder[$order]['content_id'];
                $upd = $pdo->prepare("
                    UPDATE content
                    SET content = :content, type_create = 'user', updated_at = NOW()
                    WHERE content_id = :content_id
                ");
                $upd->execute([
                    ':content' => $sectionContent,
                    ':content_id' => $contentId,
                ]);
                unset($existingByOrder[$order]);
            } else {
                $ins = $pdo->prepare("
                    INSERT INTO content (response_id, content, `order`, type_create, added_by, created_at)
                    VALUES (:response_id, :content, :order, 'user', :added_by, NOW())
                ");
                $ins->execute([
                    ':response_id' => $responseId,
                    ':content' => $sectionContent,
                    ':order' => $order,
                    ':added_by' => $userId,
                ]);
            }
        }

        foreach ($existingByOrder as $remaining) {
            $del = $pdo->prepare("DELETE FROM content WHERE content_id = :content_id");
            $del->execute([':content_id' => $remaining['content_id']]);
        }
    } else {
        insertContent($responseId, $sections, $userId);
    }
}

/**
 * Ensure deliver has a response record; create if missing.
 */
function ensureDeliverResponse(int $deliverId, int $taskId, int $userId): int
{
    global $pdo;

    $stmt = $pdo->prepare("SELECT response FROM deliver WHERE id_deliver = :deliver_id");
    $stmt->execute([':deliver_id' => $deliverId]);
    $deliverInfo = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($deliverInfo && !empty($deliverInfo['response'])) {
        return (int)$deliverInfo['response'];
    }

    $stmtDeliverType = $pdo->prepare("SELECT type, value FROM deliver WHERE id_deliver = :deliver_id");
    $stmtDeliverType->execute([':deliver_id' => $deliverId]);
    $deliverTypeInfo = $stmtDeliverType->fetch(PDO::FETCH_ASSOC);

    $ownerUserId = $userId;
    if ($deliverTypeInfo && $deliverTypeInfo['type'] === 'user') {
        $ownerUserId = (int)$deliverTypeInfo['value'];
    }

    $responseId = (int)insertResponse($taskId, $userId, ['owner_user_id' => $ownerUserId]);
    if (!$responseId) {
        throw new RuntimeException('Failed to create response');
    }

    updateDeliverStatus($deliverId, $responseId, 1);
    return $responseId;
}

/**
 * Confirm an analysis history entry as the student's final submission.
 *
 * @return array{response_id:int, history_id:int}
 */
function confirmAnalysisHistoryEntry(
    int $userId,
    int $taskId,
    int $deliverId,
    ?int $historyId = null,
    ?array $analysisData = null
): array {
    global $pdo;

    if ($historyId) {
        $stmt = $pdo->prepare("
            SELECT id, analysis_data
            FROM analysis_history
            WHERE id = :id AND user_id = :uid AND task_id = :tid AND deliver_id = :did
        ");
        $stmt->execute([
            ':id' => $historyId,
            ':uid' => $userId,
            ':tid' => $taskId,
            ':did' => $deliverId,
        ]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) {
            throw new InvalidArgumentException('History entry not found');
        }
        $analysisData = json_decode($row['analysis_data'], true);
        if (!is_array($analysisData)) {
            throw new InvalidArgumentException('Invalid analysis data');
        }
    } elseif (!is_array($analysisData) || empty($analysisData)) {
        throw new InvalidArgumentException('Missing analysis data');
    } else {
        $label = date('d/m/Y H:i') . ' (confirmed)';
        $json = json_encode($analysisData, JSON_UNESCAPED_UNICODE);
        $ins = $pdo->prepare("
            INSERT INTO analysis_history (user_id, task_id, deliver_id, label, analysis_data)
            VALUES (:uid, :tid, :did, :label, :data)
        ");
        $ins->execute([
            ':uid' => $userId,
            ':tid' => $taskId,
            ':did' => $deliverId,
            ':label' => $label,
            ':data' => $json,
        ]);
        $historyId = (int)$pdo->lastInsertId();
    }

    $pdo->beginTransaction();
    try {
        $pdo->prepare("
            UPDATE analysis_history
            SET is_confirmed = 0, confirmed_at = NULL
            WHERE user_id = :uid AND task_id = :tid AND deliver_id = :did
        ")->execute([':uid' => $userId, ':tid' => $taskId, ':did' => $deliverId]);

        $pdo->prepare("
            UPDATE analysis_history
            SET is_confirmed = 1, confirmed_at = NOW()
            WHERE id = :id
        ")->execute([':id' => $historyId]);

        $responseId = ensureDeliverResponse($deliverId, $taskId, $userId);
        applyAnalysisDataToResponse($responseId, $analysisData, $userId);
        updateDeliverStatus($deliverId, $responseId, 2);

        $pdo->commit();

        return [
            'response_id' => $responseId,
            'history_id' => $historyId,
        ];
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $e;
    }
}

/**
 * Get confirmed history id for a deliver (if any).
 */
function getConfirmedHistoryId(int $userId, int $taskId, int $deliverId): ?int
{
    global $pdo;

    try {
        $stmt = $pdo->prepare("
            SELECT id FROM analysis_history
            WHERE user_id = :uid AND task_id = :tid AND deliver_id = :did AND is_confirmed = 1
            ORDER BY confirmed_at DESC
            LIMIT 1
        ");
        $stmt->execute([':uid' => $userId, ':tid' => $taskId, ':did' => $deliverId]);
        $id = $stmt->fetchColumn();
        return $id ? (int)$id : null;
    } catch (PDOException $e) {
        return null;
    }
}
