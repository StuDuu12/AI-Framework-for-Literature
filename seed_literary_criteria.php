<?php
/**
 * Seed literary evaluation criteria (Poetry, Story, Drama) for one or all classes.
 *
 * Usage:
 *   php seed_literary_criteria.php           # all classes
 *   php seed_literary_criteria.php 4       # class_id = 4
 */

require_once __DIR__ . '/config/db.php';

$classFilter = isset($argv[1]) ? (int) $argv[1] : 0;

function j(string $vi, string $en): string
{
    return json_encode(['vi' => $vi, 'en' => $en], JSON_UNESCAPED_UNICODE);
}

$genreGroups = [
    [
        'code' => 'POETRY',
        'name' => j('Thơ', 'Poetry'),
        'desc' => j(
            'Bộ tiêu chí đánh giá phân tích tác phẩm thơ — phù hợp giai đoạn đánh giá ngang hàng.',
            'Rubric for poetry analysis — use in peer evaluation phases.'
        ),
        'criteria' => [
            [j('Hiểu nội dung & chủ đề', 'Theme & content comprehension'), j('Nắm được chủ đề, tư tưởng, cảm xúc chủ đạo của bài thơ.', 'Grasps the poem’s main theme, ideas, and emotional tone.'), 0, 10],
            [j('Phân tích hình ảnh & biện pháp tu từ', 'Imagery & figurative language'), j('Phân tích hình ảnh, so sánh, nhân hóa, ẩn dụ… và liên hệ nội dung.', 'Analyzes imagery and figurative devices and links them to meaning.'), 0, 10],
            [j('Nhạc tính thơ', 'Poetic musicality'), j('Nhận xét vần, nhịp, thanh điệu, cách ngắt dòng và hiệu quả biểu cảm.', 'Comments on rhyme, rhythm, tone, line breaks, and expressive effect.'), 0, 10],
            [j('Cấu trúc & bố cục', 'Structure & composition'), j('Phân tích cách bố cục khổ thơ, mạch phát triển ý, điểm nhấn cấu trúc.', 'Analyzes stanza layout, development of ideas, and structural highlights.'), 0, 10],
            [j('Giọng điệu & sắc thái', 'Tone & mood'), j('Nhận diện giọng thơ, thái độ người nói, không khí cảm xúc.', 'Identifies speaker tone, attitude, and emotional atmosphere.'), 0, 10],
            [j('Không gian & thời gian', 'Space & time'), j('Phân tích bối cảnh, không gian nội tâm/thực tế và yếu tố thời gian trong thơ.', 'Analyzes setting, inner/outer space, and temporal elements.'), 0, 10],
            [j('Giá trị tư tưởng & nhân đạo', 'Values & humanistic meaning'), j('Rút ra thông điệp, giá trị nhân văn hoặc liên hệ đời sống.', 'Draws out message, humanistic value, or real-life relevance.'), 0, 10],
            [j('Dẫn chứng từ bài thơ', 'Textual evidence'), j('Trích dẫn chính xác, đủ, phân tích sâu — không chỉ liệt kê.', 'Uses accurate, sufficient quotes with deep analysis.'), 0, 10],
            [j('Lập luận & mạch phân tích', 'Argument & coherence'), j('Luận điểm rõ, liên kết logic giữa các phần, tránh lan man.', 'Clear thesis, logical flow, focused reasoning.'), 0, 10],
            [j('Sáng tạo & góc nhìn riêng', 'Creativity & original insight'), j('Có nhận xét mang tính khám phá, không chỉ lặp lại ý sẵn có.', 'Offers insightful, non-generic interpretations.'), 0, 10],
        ],
    ],
    [
        'code' => 'STORY',
        'name' => j('Truyện', 'Short story'),
        'desc' => j(
            'Bộ tiêu chí đánh giá phân tích truyện ngắn / tản văn kể chuyện.',
            'Rubric for short story / narrative prose analysis.'
        ),
        'criteria' => [
            [j('Nắm cốt truyện', 'Plot summary'), j('Tóm tắt đúng diễn biến, nêu rõ xung đột và kết.', 'Accurate summary with conflict and resolution.'), 0, 10],
            [j('Xây dựng nhân vật', 'Characterization'), j('Phân tích tính cách, động cơ, sự thay đổi của nhân vật.', 'Analyzes traits, motives, and character development.'), 0, 10],
            [j('Bối cảnh & không gian', 'Setting & world-building'), j('Vai trò của bối cảnh thời gian, không gian đối với nội dung.', 'Role of time/place in shaping the narrative.'), 0, 10],
            [j('Diễn biến & cao trào', 'Plot development & climax'), j('Nhận xét cách dựng tình huống, cao trào, kết thúc.', 'Comments on rising action, climax, and ending.'), 0, 10],
            [j('Ngôn ngữ & lời kể', 'Language & narration'), j('Giọng kể, ngôn ngữ, chi tiết miêu tả và hiệu quả biểu đạt.', 'Narrative voice, diction, detail, and expression.'), 0, 10],
            [j('Chủ đề & thông điệp', 'Theme & message'), j('Làm rõ chủ đề, thông điệp tác giả muốn truyền tải.', 'Clarifies theme and authorial message.'), 0, 10],
            [j('Kết cấu truyện ngắn', 'Short-story structure'), j('Mở–thân–kết gọn, mật độ chi tiết, tính hợp lý của bố cục.', 'Tight opening–body–ending and structural logic.'), 0, 10],
            [j('Cảm xúc & tác động', 'Emotional impact'), j('Bài phân tích truyền tải được cảm xúc tác phẩm gây ra.', 'Conveys the work’s emotional effect on the reader.'), 0, 10],
            [j('Dẫn chứng & trích dẫn', 'Evidence & quotations'), j('Dẫn chứng cụ thể từ truyện, phân tích gắn với luận điểm.', 'Specific evidence tied to claims.'), 0, 10],
            [j('Nhận xét phê bình', 'Critical evaluation'), j('Có đánh giá tổng hợp, ưu–nhược điểm hoặc góc nhìn phê bình.', 'Overall critique with strengths/limitations.'), 0, 10],
        ],
    ],
    [
        'code' => 'DRAMA',
        'name' => j('Kịch', 'Drama'),
        'desc' => j(
            'Bộ tiêu chí đánh giá phân tích tác phẩm kịch / tuồng / kịch bản.',
            'Rubric for drama / play / script analysis.'
        ),
        'criteria' => [
            [j('Nắm tình huống & xung đột', 'Situation & conflict'), j('Xác định rõ mâu thuẫn, cao trào và diễn biến sân khấu.', 'Identifies conflict, climax, and stage action.'), 0, 10],
            [j('Phân tích nhân vật sân khấu', 'Dramatic characters'), j('Tính cách, mối quan hệ, hành động thể hiện trên sân khấu.', 'Character traits, relations, and stage behavior.'), 0, 10],
            [j('Đối thoại & độc thoại', 'Dialogue & monologue'), j('Ngôn ngữ nhân vật, lời thoại mang tính kịch, ẩn ý.', 'Character speech, subtext, and dramatic dialogue.'), 0, 10],
            [j('Cấu trúc hồi / cảnh', 'Act & scene structure'), j('Bố cục các hồi, cảnh; nhịp điệu leo thang kịch tính.', 'Act/scene layout and dramatic pacing.'), 0, 10],
            [j('Biểu tượng & ẩn dụ', 'Symbolism & metaphor'), j('Phân tích hình ảnh, biểu tượng sân khấu nếu có.', 'Stage symbols and metaphorical meaning.'), 0, 10],
            [j('Bối cảnh lịch sử – xã hội', 'Historical & social context'), j('Liên hệ hoàn cảnh ra đời tác phẩm với nội dung kịch.', 'Links social/historical context to the play.'), 0, 10],
            [j('Thông điệp tư tưởng', 'Ideological message'), j('Làm rõ quan điểm, thông điệp tư tưởng của tác phẩm.', 'Clarifies the play’s ideas and message.'), 0, 10],
            [j('Hiệu ứng sân khấu', 'Stage effect'), j('Nhận xét cách tác giả dựng tình huống, không khí, tính kịch.', 'Comments on staging, atmosphere, and drama.'), 0, 10],
            [j('Dẫn chứng văn bản', 'Textual evidence'), j('Trích thoại / hướng dẫn sân khấu phù hợp, phân tích sâu.', 'Quotes dialogue/stage directions with analysis.'), 0, 10],
            [j('Nhận định tổng hợp', 'Overall assessment'), j('Kết luận có cơ sở về giá trị nghệ thuật tác phẩm kịch.', 'Grounded conclusion on the play’s artistic value.'), 0, 10],
        ],
    ],
];

try {
    if ($classFilter > 0) {
        $classIds = [$classFilter];
    } else {
        $classIds = $pdo->query('SELECT class_id FROM class ORDER BY class_id')->fetchAll(PDO::FETCH_COLUMN);
    }

    if (empty($classIds)) {
        echo "No classes found.\n";
        exit(1);
    }

    $findGroup = $pdo->prepare('SELECT nhom_tc_id FROM nhom_tc WHERE class_id = :cid AND ten_nhom_tc = :name LIMIT 1');
    $insertGroup = $pdo->prepare('INSERT INTO nhom_tc (class_id, ten_nhom_tc, description) VALUES (:cid, :name, :desc)');
    $findCriterion = $pdo->prepare('SELECT tc_id FROM tieu_chi WHERE nhom_tc_id = :gid AND name_tc = :name LIMIT 1');
    $insertCriterion = $pdo->prepare('INSERT INTO tieu_chi (name_tc, nhom_tc_id, description, min_score, max_score) VALUES (:name, :gid, :desc, :min, :max)');

    $totalGroups = 0;
    $totalCriteria = 0;

    foreach ($classIds as $classId) {
        echo "Class #{$classId}\n";

        foreach ($genreGroups as $group) {
            $findGroup->execute([':cid' => $classId, ':name' => $group['name']]);
            $groupId = $findGroup->fetchColumn();

            if (!$groupId) {
                $insertGroup->execute([
                    ':cid' => $classId,
                    ':name' => $group['name'],
                    ':desc' => $group['desc'],
                ]);
                $groupId = (int) $pdo->lastInsertId();
                $totalGroups++;
                echo "  + Group {$group['code']} (id {$groupId})\n";
            } else {
                echo "  = Group {$group['code']} exists (id {$groupId})\n";
            }

            foreach ($group['criteria'] as [$name, $desc, $min, $max]) {
                $findCriterion->execute([':gid' => $groupId, ':name' => $name]);
                if ($findCriterion->fetchColumn()) {
                    continue;
                }
                $insertCriterion->execute([
                    ':name' => $name,
                    ':gid' => $groupId,
                    ':desc' => $desc,
                    ':min' => $min,
                    ':max' => $max,
                ]);
                $totalCriteria++;
            }
        }
    }

    echo "\nDone. Added {$totalGroups} group(s), {$totalCriteria} criterion/criteria.\n";
} catch (Throwable $e) {
    fwrite(STDERR, 'Error: ' . $e->getMessage() . "\n");
    exit(1);
}
