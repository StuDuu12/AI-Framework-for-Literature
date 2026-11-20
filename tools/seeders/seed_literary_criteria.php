<?php
if (PHP_SAPI !== 'cli') { http_response_code(403); exit('CLI only'); }
/**
 * Seed literary evaluation criteria (Poetry, Story, Drama) for one or all classes.
 *
 * Usage:
 *   php seed_literary_criteria.php           # all classes
 *   php seed_literary_criteria.php 4       # class_id = 4
 */

require_once __DIR__ . '/../../config/db.php';

$classFilter = isset($argv[1]) ? (int) $argv[1] : 0;

function j(string $vi, string $en): string
{
    return json_encode(['vi' => $vi, 'en' => $en], JSON_UNESCAPED_UNICODE);
}

$genreGroups = [
    [
        'code' => 'POETRY',
        'name' => j('ThÆ¡', 'Poetry'),
        'desc' => j(
            'Bá»™ tiÃªu chÃ­ Ä‘Ã¡nh giÃ¡ phÃ¢n tÃ­ch tÃ¡c pháº©m thÆ¡ â€” phÃ¹ há»£p giai Ä‘oáº¡n Ä‘Ã¡nh giÃ¡ ngang hÃ ng.',
            'Rubric for poetry analysis â€” use in peer evaluation phases.'
        ),
        'criteria' => [
            [j('Hiá»ƒu ná»™i dung & chá»§ Ä‘á»', 'Theme & content comprehension'), j('Náº¯m Ä‘Æ°á»£c chá»§ Ä‘á», tÆ° tÆ°á»Ÿng, cáº£m xÃºc chá»§ Ä‘áº¡o cá»§a bÃ i thÆ¡.', 'Grasps the poemâ€™s main theme, ideas, and emotional tone.'), 0, 10],
            [j('PhÃ¢n tÃ­ch hÃ¬nh áº£nh & biá»‡n phÃ¡p tu tá»«', 'Imagery & figurative language'), j('PhÃ¢n tÃ­ch hÃ¬nh áº£nh, so sÃ¡nh, nhÃ¢n hÃ³a, áº©n dá»¥â€¦ vÃ  liÃªn há»‡ ná»™i dung.', 'Analyzes imagery and figurative devices and links them to meaning.'), 0, 10],
            [j('Nháº¡c tÃ­nh thÆ¡', 'Poetic musicality'), j('Nháº­n xÃ©t váº§n, nhá»‹p, thanh Ä‘iá»‡u, cÃ¡ch ngáº¯t dÃ²ng vÃ  hiá»‡u quáº£ biá»ƒu cáº£m.', 'Comments on rhyme, rhythm, tone, line breaks, and expressive effect.'), 0, 10],
            [j('Cáº¥u trÃºc & bá»‘ cá»¥c', 'Structure & composition'), j('PhÃ¢n tÃ­ch cÃ¡ch bá»‘ cá»¥c khá»• thÆ¡, máº¡ch phÃ¡t triá»ƒn Ã½, Ä‘iá»ƒm nháº¥n cáº¥u trÃºc.', 'Analyzes stanza layout, development of ideas, and structural highlights.'), 0, 10],
            [j('Giá»ng Ä‘iá»‡u & sáº¯c thÃ¡i', 'Tone & mood'), j('Nháº­n diá»‡n giá»ng thÆ¡, thÃ¡i Ä‘á»™ ngÆ°á»i nÃ³i, khÃ´ng khÃ­ cáº£m xÃºc.', 'Identifies speaker tone, attitude, and emotional atmosphere.'), 0, 10],
            [j('KhÃ´ng gian & thá»i gian', 'Space & time'), j('PhÃ¢n tÃ­ch bá»‘i cáº£nh, khÃ´ng gian ná»™i tÃ¢m/thá»±c táº¿ vÃ  yáº¿u tá»‘ thá»i gian trong thÆ¡.', 'Analyzes setting, inner/outer space, and temporal elements.'), 0, 10],
            [j('GiÃ¡ trá»‹ tÆ° tÆ°á»Ÿng & nhÃ¢n Ä‘áº¡o', 'Values & humanistic meaning'), j('RÃºt ra thÃ´ng Ä‘iá»‡p, giÃ¡ trá»‹ nhÃ¢n vÄƒn hoáº·c liÃªn há»‡ Ä‘á»i sá»‘ng.', 'Draws out message, humanistic value, or real-life relevance.'), 0, 10],
            [j('Dáº«n chá»©ng tá»« bÃ i thÆ¡', 'Textual evidence'), j('TrÃ­ch dáº«n chÃ­nh xÃ¡c, Ä‘á»§, phÃ¢n tÃ­ch sÃ¢u â€” khÃ´ng chá»‰ liá»‡t kÃª.', 'Uses accurate, sufficient quotes with deep analysis.'), 0, 10],
            [j('Láº­p luáº­n & máº¡ch phÃ¢n tÃ­ch', 'Argument & coherence'), j('Luáº­n Ä‘iá»ƒm rÃµ, liÃªn káº¿t logic giá»¯a cÃ¡c pháº§n, trÃ¡nh lan man.', 'Clear thesis, logical flow, focused reasoning.'), 0, 10],
            [j('SÃ¡ng táº¡o & gÃ³c nhÃ¬n riÃªng', 'Creativity & original insight'), j('CÃ³ nháº­n xÃ©t mang tÃ­nh khÃ¡m phÃ¡, khÃ´ng chá»‰ láº·p láº¡i Ã½ sáºµn cÃ³.', 'Offers insightful, non-generic interpretations.'), 0, 10],
        ],
    ],
    [
        'code' => 'STORY',
        'name' => j('Truyá»‡n', 'Short story'),
        'desc' => j(
            'Bá»™ tiÃªu chÃ­ Ä‘Ã¡nh giÃ¡ phÃ¢n tÃ­ch truyá»‡n ngáº¯n / táº£n vÄƒn ká»ƒ chuyá»‡n.',
            'Rubric for short story / narrative prose analysis.'
        ),
        'criteria' => [
            [j('Náº¯m cá»‘t truyá»‡n', 'Plot summary'), j('TÃ³m táº¯t Ä‘Ãºng diá»…n biáº¿n, nÃªu rÃµ xung Ä‘á»™t vÃ  káº¿t.', 'Accurate summary with conflict and resolution.'), 0, 10],
            [j('XÃ¢y dá»±ng nhÃ¢n váº­t', 'Characterization'), j('PhÃ¢n tÃ­ch tÃ­nh cÃ¡ch, Ä‘á»™ng cÆ¡, sá»± thay Ä‘á»•i cá»§a nhÃ¢n váº­t.', 'Analyzes traits, motives, and character development.'), 0, 10],
            [j('Bá»‘i cáº£nh & khÃ´ng gian', 'Setting & world-building'), j('Vai trÃ² cá»§a bá»‘i cáº£nh thá»i gian, khÃ´ng gian Ä‘á»‘i vá»›i ná»™i dung.', 'Role of time/place in shaping the narrative.'), 0, 10],
            [j('Diá»…n biáº¿n & cao trÃ o', 'Plot development & climax'), j('Nháº­n xÃ©t cÃ¡ch dá»±ng tÃ¬nh huá»‘ng, cao trÃ o, káº¿t thÃºc.', 'Comments on rising action, climax, and ending.'), 0, 10],
            [j('NgÃ´n ngá»¯ & lá»i ká»ƒ', 'Language & narration'), j('Giá»ng ká»ƒ, ngÃ´n ngá»¯, chi tiáº¿t miÃªu táº£ vÃ  hiá»‡u quáº£ biá»ƒu Ä‘áº¡t.', 'Narrative voice, diction, detail, and expression.'), 0, 10],
            [j('Chá»§ Ä‘á» & thÃ´ng Ä‘iá»‡p', 'Theme & message'), j('LÃ m rÃµ chá»§ Ä‘á», thÃ´ng Ä‘iá»‡p tÃ¡c giáº£ muá»‘n truyá»n táº£i.', 'Clarifies theme and authorial message.'), 0, 10],
            [j('Káº¿t cáº¥u truyá»‡n ngáº¯n', 'Short-story structure'), j('Má»Ÿâ€“thÃ¢nâ€“káº¿t gá»n, máº­t Ä‘á»™ chi tiáº¿t, tÃ­nh há»£p lÃ½ cá»§a bá»‘ cá»¥c.', 'Tight openingâ€“bodyâ€“ending and structural logic.'), 0, 10],
            [j('Cáº£m xÃºc & tÃ¡c Ä‘á»™ng', 'Emotional impact'), j('BÃ i phÃ¢n tÃ­ch truyá»n táº£i Ä‘Æ°á»£c cáº£m xÃºc tÃ¡c pháº©m gÃ¢y ra.', 'Conveys the workâ€™s emotional effect on the reader.'), 0, 10],
            [j('Dáº«n chá»©ng & trÃ­ch dáº«n', 'Evidence & quotations'), j('Dáº«n chá»©ng cá»¥ thá»ƒ tá»« truyá»‡n, phÃ¢n tÃ­ch gáº¯n vá»›i luáº­n Ä‘iá»ƒm.', 'Specific evidence tied to claims.'), 0, 10],
            [j('Nháº­n xÃ©t phÃª bÃ¬nh', 'Critical evaluation'), j('CÃ³ Ä‘Ã¡nh giÃ¡ tá»•ng há»£p, Æ°uâ€“nhÆ°á»£c Ä‘iá»ƒm hoáº·c gÃ³c nhÃ¬n phÃª bÃ¬nh.', 'Overall critique with strengths/limitations.'), 0, 10],
        ],
    ],
    [
        'code' => 'DRAMA',
        'name' => j('Ká»‹ch', 'Drama'),
        'desc' => j(
            'Bá»™ tiÃªu chÃ­ Ä‘Ã¡nh giÃ¡ phÃ¢n tÃ­ch tÃ¡c pháº©m ká»‹ch / tuá»“ng / ká»‹ch báº£n.',
            'Rubric for drama / play / script analysis.'
        ),
        'criteria' => [
            [j('Náº¯m tÃ¬nh huá»‘ng & xung Ä‘á»™t', 'Situation & conflict'), j('XÃ¡c Ä‘á»‹nh rÃµ mÃ¢u thuáº«n, cao trÃ o vÃ  diá»…n biáº¿n sÃ¢n kháº¥u.', 'Identifies conflict, climax, and stage action.'), 0, 10],
            [j('PhÃ¢n tÃ­ch nhÃ¢n váº­t sÃ¢n kháº¥u', 'Dramatic characters'), j('TÃ­nh cÃ¡ch, má»‘i quan há»‡, hÃ nh Ä‘á»™ng thá»ƒ hiá»‡n trÃªn sÃ¢n kháº¥u.', 'Character traits, relations, and stage behavior.'), 0, 10],
            [j('Äá»‘i thoáº¡i & Ä‘á»™c thoáº¡i', 'Dialogue & monologue'), j('NgÃ´n ngá»¯ nhÃ¢n váº­t, lá»i thoáº¡i mang tÃ­nh ká»‹ch, áº©n Ã½.', 'Character speech, subtext, and dramatic dialogue.'), 0, 10],
            [j('Cáº¥u trÃºc há»“i / cáº£nh', 'Act & scene structure'), j('Bá»‘ cá»¥c cÃ¡c há»“i, cáº£nh; nhá»‹p Ä‘iá»‡u leo thang ká»‹ch tÃ­nh.', 'Act/scene layout and dramatic pacing.'), 0, 10],
            [j('Biá»ƒu tÆ°á»£ng & áº©n dá»¥', 'Symbolism & metaphor'), j('PhÃ¢n tÃ­ch hÃ¬nh áº£nh, biá»ƒu tÆ°á»£ng sÃ¢n kháº¥u náº¿u cÃ³.', 'Stage symbols and metaphorical meaning.'), 0, 10],
            [j('Bá»‘i cáº£nh lá»‹ch sá»­ â€“ xÃ£ há»™i', 'Historical & social context'), j('LiÃªn há»‡ hoÃ n cáº£nh ra Ä‘á»i tÃ¡c pháº©m vá»›i ná»™i dung ká»‹ch.', 'Links social/historical context to the play.'), 0, 10],
            [j('ThÃ´ng Ä‘iá»‡p tÆ° tÆ°á»Ÿng', 'Ideological message'), j('LÃ m rÃµ quan Ä‘iá»ƒm, thÃ´ng Ä‘iá»‡p tÆ° tÆ°á»Ÿng cá»§a tÃ¡c pháº©m.', 'Clarifies the playâ€™s ideas and message.'), 0, 10],
            [j('Hiá»‡u á»©ng sÃ¢n kháº¥u', 'Stage effect'), j('Nháº­n xÃ©t cÃ¡ch tÃ¡c giáº£ dá»±ng tÃ¬nh huá»‘ng, khÃ´ng khÃ­, tÃ­nh ká»‹ch.', 'Comments on staging, atmosphere, and drama.'), 0, 10],
            [j('Dáº«n chá»©ng vÄƒn báº£n', 'Textual evidence'), j('TrÃ­ch thoáº¡i / hÆ°á»›ng dáº«n sÃ¢n kháº¥u phÃ¹ há»£p, phÃ¢n tÃ­ch sÃ¢u.', 'Quotes dialogue/stage directions with analysis.'), 0, 10],
            [j('Nháº­n Ä‘á»‹nh tá»•ng há»£p', 'Overall assessment'), j('Káº¿t luáº­n cÃ³ cÆ¡ sá»Ÿ vá» giÃ¡ trá»‹ nghá»‡ thuáº­t tÃ¡c pháº©m ká»‹ch.', 'Grounded conclusion on the playâ€™s artistic value.'), 0, 10],
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

