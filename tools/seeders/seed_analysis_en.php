<?php
/**
 * Seed full English literary analysis data (json_ai + content blocks)
 * for all demo student submissions on analysis tasks.
 *
 * Run: php seed_analysis_en.php
 */
require_once __DIR__ . '/../../config/db.php';

function poetryKhucMuaThuJson(): array
{
    return [
        'title' => 'KhÃºc mÃ¹a thu',
        'characteristics' => [
            'Genre' => 'Free verse poetry',
            'Style' => 'Modern lyric, confessional',
            'Tone' => 'Melancholic, reflective, obsessive',
        ],
        'keywords' => ['autumn', 'love', 'nostalgia', 'memory', 'time', 'longing'],
        'generalAnalysis' => [
            'thematicAnalysis' => [
                [
                    'heading' => 'Love as obsession and memory',
                    'content' => 'The poem treats love not as fulfilled romance but as an enduring obsession with the past. The speaker accepts the beloved\'s flaws yet cannot escape idealized memory ("cung Háº±ng", "nguyá»‡t quáº¿"). Autumn becomes both season and psychological state â€” the "quáº§ng thu thuá»Ÿ áº¥y" condenses nostalgia into a visible halo around the absent woman.',
                ],
                [
                    'heading' => 'Time and irrecoverable loss',
                    'content' => 'Rhetorical questions at the close ("CÃ³ biáº¿t khÃ´ng em...") open interpretive space and invite shared reflection. Personal emotion is situated within post-war uncertainty: happiness is fragile, remembrance carries moral weight, and the poem asks whether beauty can survive the passage of years.',
                ],
            ],
            'linguisticInsights' => [
                'motifs' => ['autumn light', 'hair hiding night', 'celestial bodies', 'rhetorical questioning'],
                'symbolsAndMetaphors' => ['"giáº¥u Ä‘Ãªm vÃ o trong tÃ³c" â€” intimacy withheld', '"cung Háº±ng" / "nguyá»‡t quáº¿" â€” idealized femininity', 'autumn halo â€” memory made visible'],
                'temporalElements' => 'Past and present interweave through anaphora and repeated temporal markers; memory beautifies and distorts the past simultaneously.',
                'natureAndScenery' => 'Autumn scenery externalizes inner mood; landscape is never decorative â€” it embodies consciousness under the pressure of time.',
            ],
        ],
        'poetryAnalysis' => [
            'rhyming' => 'Free verse with occasional internal rhyme and assonance; musicality arises from repetition rather than fixed end-rhyme.',
            'emotionalFlow' => 'Emotional intensity builds through obsessive return to key images; the closing questions release tension into open-ended meditation.',
            'structure' => 'Stanzas follow emotional shifts, not fixed meter. Parallel constructions reinforce the speaker\'s inability to leave the past.',
            'imagery' => 'Concrete sensory images (hair, night, autumn halo) anchor abstract longing; celestial imagery elevates personal love to cosmic scale.',
            'rhythm' => 'Line breaks coincide with breath units, producing a confessional cadence typical of modern Vietnamese lyric poetry.',
            'spaceTimeOrganization' => 'Domestic and cosmic spaces collapse into one symbolic field; time moves cyclically through seasonal metaphor.',
            'rhetoricalDevices' => 'Anaphora, rhetorical questions, and parallelism intensify obsessive tone without melodrama.',
        ],
        'concludingThoughts' => [
            'lesson' => 'Unfulfilled love leaves an indelible mark on consciousness; the poem honors honesty over sentimental consolation.',
            'message' => '"KhÃºc mÃ¹a thu" endures because it portrays memory as both wound and treasure.',
        ],
        'intertextuality' => [
            'initialAnalysis' => 'The poem dialogues with classical Vietnamese autumn poetry while modernizing voice through direct address.',
            'comparativeText' => ['title' => 'Thu Ä‘Ã´ng', 'author' => 'HÃ n Máº·c Tá»­'],
            'comparison' => [
                'similarities' => 'Shared autumnal mood and symbolic use of nature.',
                'differences' => 'XuÃ¢n Diá»‡u\'s lyrics are exuberant; this poem is restrained, philosophically weighted.',
                'effects' => 'Intertextual awareness deepens the poem\'s historical and emotional resonance.',
            ],
        ],
    ];
}

function poetryWarTruckJson(): array
{
    return [
        'title' => 'BÃ i thÆ¡ vá» tiá»ƒu Ä‘á»™i xe khÃ´ng kÃ­nh',
        'characteristics' => [
            'Genre' => 'War poetry',
            'Style' => 'Heroic lyric, collective voice',
            'Tone' => 'Solemn, defiant, elegiac',
        ],
        'keywords' => ['war', 'sacrifice', 'youth', 'camaraderie', 'patriotism', 'soldiers'],
        'generalAnalysis' => [
            'thematicAnalysis' => [
                [
                    'heading' => 'Anonymous heroism',
                    'content' => 'Pháº¡m Tiáº¿n Duáº­t names the un-named: young truck drivers who face bombs without windshields become national symbols. The poem transforms a military episode into myth by refusing individual celebrity â€” heroism belongs to the collective "tiá»ƒu Ä‘á»™i".',
                ],
                [
                    'heading' => 'Vulnerability as strength',
                    'content' => '"KhÃ´ng cÃ³ kÃ­nh" is not deficiency but moral choice: open exposure to wind, dust, rain, and death paradoxically proves spiritual freedom. The drivers look directly at earth, sky, and their own hearts â€” unfiltered perception equals courage.',
                ],
            ],
            'linguisticInsights' => [
                'motifs' => ['broken windshield', 'dust on hair', 'rain-soaked shirts', 'handshake through broken glass'],
                'symbolsAndMetaphors' => ['open truck â€” unshielded patriotism', 'HoÃ ng Cáº§m stove â€” improvised family', 'white dust â€” premature aging'],
                'temporalElements' => 'Present-tense narration creates immediacy; episodic structure mirrors convoy movement along the trail.',
                'natureAndScenery' => 'Wind, rain, stars, and birds enter the driver\'s cabin â€” nature is not backdrop but witness to sacrifice.',
            ],
        ],
        'poetryAnalysis' => [
            'rhyming' => 'Loose rhyme and rhythmic repetition of "KhÃ´ng cÃ³ kÃ­nh" anchor the poem\'s incantatory quality.',
            'emotionalFlow' => 'Defiant humor ("cÆ°á»i ha ha") alternates with solemn cataloguing of danger, producing heroic pathos without sentimentality.',
            'structure' => 'Refrain-like openings build to communal reunion ("há»p thÃ nh tiá»ƒu Ä‘á»™i") and domestic metaphor (shared rice bowl).',
            'imagery' => 'Vivid battlefield details â€” bomb shock, dust whitening hair, rain soaking shirts â€” materialize abstract patriotism.',
            'rhythm' => 'Driving cadence mimics convoy motion; short lines accelerate urgency.',
            'spaceTimeOrganization' => 'Road, cabin, and sky merge into one mobile space of war; time is the endless haul of supplies under fire.',
            'rhetoricalDevices' => 'Anaphora, parallelism, and ironic understatement ("á»« thÃ¬ cÃ³ bá»¥i") elevate ordinary soldiers.',
        ],
        'concludingThoughts' => [
            'lesson' => 'True courage often appears ordinary; the poem teaches reverence for anonymous sacrifice.',
            'message' => 'Youth on the TrÆ°á»ng SÆ¡n trail becomes eternal through collective memory and poetic naming.',
        ],
        'intertextuality' => [
            'initialAnalysis' => 'Belongs to the tradition of resistance war poetry that dignifies logistical labor, not only combat.',
            'comparativeText' => ['title' => 'BÃ i thÆ¡ vá» con Ä‘Æ°á»ng TrÆ°á»ng SÆ¡n', 'author' => 'Pháº¡m Tiáº¿n Duáº­t'],
            'comparison' => [
                'similarities' => 'Celebration of TrÆ°á»ng SÆ¡n soldiers and concrete military imagery.',
                'differences' => 'This poem narrows focus to truck drivers and ironic humor amid danger.',
                'effects' => 'Expands the canon of war poetry beyond front-line infantry to support troops.',
            ],
        ],
    ];
}

function storyLaoHacJson(): array
{
    return [
        'title' => 'LÃ£o Háº¡c',
        'characteristics' => [
            'Genre' => 'Short story (realist fiction)',
            'Style' => 'Restrained realism, humanistic',
            'Tone' => 'Tragic, compassionate, quietly devastating',
        ],
        'keywords' => ['poverty', 'dignity', 'peasantry', 'sacrifice', 'fatherhood', 'moral integrity'],
        'generalAnalysis' => [
            'thematicAnalysis' => [
                [
                    'heading' => 'Poverty and human dignity',
                    'content' => 'Old Man Hac embodies pre-revolutionary peasant tragedy: material deprivation never extinguishes moral integrity. Selling his dog "cáº­u VÃ ng" is economic desperation and spiritual wound â€” the dog is companion, family surrogate, and last symbol of self-respect.',
                ],
                [
                    'heading' => 'Understanding the oppressed',
                    'content' => 'Nam Cao\'s humanistic message â€” "cá»‘ tÃ¬m mÃ  hiá»ƒu" â€” is enacted through the teacher-narrator\'s journey from judgment to empathy. The story refuses to sentimentalize poverty; systemic inequality forces impossible choices.',
                ],
            ],
            'linguisticInsights' => [
                'motifs' => ['tobacco pipe ritual', 'the yellow dog', 'garden plot', 'poisoned bait'],
                'symbolsAndMetaphors' => ['cáº­u VÃ ng â€” remaining dignity', 'garden â€” hope for absent son', 'poison â€” final self-respecting exit'],
                'temporalElements' => 'Slow, patient narration mirrors rural time; backstory unfolds through dialogue fragments.',
                'natureAndScenery' => 'Sparse village setting intensifies focus on gesture, silence, and social pressure.',
            ],
        ],
        'storyAnalysis' => [
            'character' => 'LÃ£o Háº¡c is drawn through gesture, speech habit, and silence. His restraint becomes the moral center; minor figures (LÃ½, GiÃ¡o, neighbors) form a social chorus.',
            'space' => 'Confined village space amplifies economic entrapment; the garden plot is the last territory of hope.',
            'time' => 'Linear narration with embedded flashbacks explaining the son\'s departure and failed marriage prospects.',
            'dialogue' => 'Sparse dialogue is socially revealing; what characters avoid saying matters as much as speech.',
            'narrator' => 'First-person teacher narration provides documentary credibility and ironic distance; initial incomprehension models society\'s blindness.',
            'situation' => 'Selling the dog precipitates the final tragedy; each economic setback tightens the noose of dignity.',
            'prominentDetails' => 'The pipe-lighting ritual, the dog\'s sale, and poisoned bait condense theme without allegorical heaviness.',
        ],
        'concludingThoughts' => [
            'lesson' => 'Dignity persists even when society denies peasants full personhood.',
            'message' => 'Artistic merit rests on restrained realism and symbolic economy â€” a landmark of Vietnamese humanist fiction.',
        ],
        'intertextuality' => [
            'initialAnalysis' => 'Nam Cao pioneers psychological realism in Vietnamese short fiction.',
            'comparativeText' => ['title' => 'Táº¯t Ä‘Ã¨n', 'author' => 'NgÃ´ Táº¥t Tá»‘'],
            'comparison' => [
                'similarities' => 'Both expose rural suffering under colonial-feudal pressure.',
                'differences' => 'NgÃ´ Táº¥t Tá»‘ emphasizes social rebellion; Nam Cao emphasizes inner tragedy.',
                'effects' => 'Reading across texts clarifies the development of Vietnamese realist fiction.',
            ],
        ],
    ];
}

function poetryChanQueJson(): array
{
    return [
        'title' => 'ChÃ¢n quÃª',
        'characteristics' => [
            'Genre' => 'Lyric poetry',
            'Style' => 'Folk-influenced, sensory realism',
            'Tone' => 'Gentle, yearning, rooted',
        ],
        'keywords' => ['homeland', 'nostalgia', 'rural life', 'village', 'rootedness'],
        'generalAnalysis' => [
            'thematicAnalysis' => [
                [
                    'heading' => 'Rootedness through concrete detail',
                    'content' => '"ChÃ¢n quÃª" celebrates attachment to village life through sensory images â€” sounds, smells, textures of rural existence â€” rather than abstract urban nostalgia. The poem\'s strength is particularity: each detail anchors identity in place.',
                ],
                [
                    'heading' => 'Countering superficial longing',
                    'content' => 'Against readings that over-emphasize abstract "homesickness," the text shows homeland as lived practice: labor, seasons, neighborly speech, and bodily memory of fields and paths.',
                ],
            ],
            'linguisticInsights' => [
                'motifs' => ['village paths', 'field labor', 'domestic sounds', 'seasonal cycles'],
                'symbolsAndMetaphors' => ['"chÃ¢n quÃª" â€” moral and emotional anchor', 'landscape as bodily memory'],
                'temporalElements' => 'Cyclical rural time contrasts with disruptive modern departure.',
                'natureAndScenery' => 'Scenery is functional and familiar, not picturesque â€” nature belongs to workers.',
            ],
        ],
        'poetryAnalysis' => [
            'rhyming' => 'Musical folk rhythm with paired sounds sustains oral quality.',
            'emotionalFlow' => 'Gentle accumulation of rural images produces warmth without melodrama.',
            'structure' => 'Lyric progression moves from scene to reflection to quiet affirmation.',
            'imagery' => 'Concrete village imagery resists vague sentimentalism.',
            'rhythm' => 'Balanced phrases echo folk song cadence.',
            'spaceTimeOrganization' => 'Village space is centered; departure creates emotional tension.',
            'rhetoricalDevices' => 'Repetition and parallel structure reinforce rooted identity.',
        ],
        'concludingThoughts' => [
            'lesson' => 'Authentic nostalgia requires concrete memory, not clichÃ©.',
            'message' => 'Nguyá»…n BÃ­nh affirms that "chÃ¢n quÃª" is ethical as well as emotional belonging.',
        ],
        'intertextuality' => [
            'initialAnalysis' => 'Dialogues with New Poetry movement depictions of countryside.',
            'comparativeText' => ['title' => 'QuÃª hÆ°Æ¡ng', 'author' => 'Táº¿ Hanh'],
            'comparison' => [
                'similarities' => 'Shared theme of homeland memory.',
                'differences' => '"ChÃ¢n quÃª" stresses moral rootedness; other poems may stress scenic beauty alone.',
                'effects' => 'Clarifies Nguyá»…n BÃ­nh\'s restrained, folk-inflected lyric voice.',
            ],
        ],
    ];
}

function buildContentBlocks(string $work, string $variant = 'default'): array
{
    $blocks = [];

    if ($work === 'khuc_mua_thu') {
        $title = $variant === 'group2' ? 'KhÃºc mÃ¹a thu â€” Group 2 analysis' : 'KhÃºc mÃ¹a thu';
        $blocks = [
            "# {$title}",
            '**Keywords:** autumn, love, nostalgia, memory, time, longing',
            "## Thematic focus\nThe poem explores love as obsession with an irrecoverable past. The speaker accepts the beloved's flaws yet cannot escape idealized memory (\"cung Háº±ng\", \"nguyá»‡t quáº¿\"). Autumn functions as both calendar season and psychological state.\n\nPersonal emotion intersects post-war uncertainty: happiness is fragile, remembrance carries moral weight, and rhetorical questions at the close invite shared reflection rather than passive reading.",
            "## Imagery and symbolism\nKey images â€” \"giáº¥u Ä‘Ãªm vÃ o trong tÃ³c\", \"quáº§ng thu thuá»Ÿ áº¥y\" â€” construct a symbolic cosmos. Hair-as-night suggests intimacy withheld; the autumn halo condenses nostalgia into visible form. Celestial bodies embody idealized femininity and unreachable desire.",
            "## Rhythm and musicality\nFree rhythm mirrors emotional instability; partial rhymes and assonance sustain musicality without rigid structure. Line breaks coincide with breath units, producing confessional cadence.",
            "## Formal analysis\nStanzas follow emotional shifts rather than fixed meter. Parallel constructions and anaphora reinforce obsessive repetition. The work modernizes classical autumn poetry through direct address and psychological interiority.",
            "## Conclusion\n\"KhÃºc mÃ¹a thu\" affirms that unfulfilled love leaves an indelible mark on consciousness. Memory simultaneously beautifies and distorts the past â€” the poem's enduring honesty lies there.",
            "## Intertextuality\nCompared with HÃ n Máº·c Tá»­'s autumn lyrics and XuÃ¢n Diá»‡u's exuberant love poetry: this text is restrained, autumnal, and philosophically weighted. It inherits tradition while speaking in a modern confessional voice.",
        ];
    } elseif ($work === 'war_poem') {
        $title = match ($variant) {
            'group2' => 'Homework â€” war poetry analysis',
            'topic37' => 'BÃ i thÆ¡ vá» tiá»ƒu Ä‘á»™i xe khÃ´ng kÃ­nh (Pháº¡m Tiáº¿n Duáº­t)',
            default => 'BÃ i thÆ¡ vá» tiá»ƒu Ä‘á»™i xe khÃ´ng kÃ­nh',
        };
        $blocks = [
            "# {$title}",
            '**Keywords:** war, sacrifice, youth, camaraderie, patriotism, TrÆ°á»ng SÆ¡n',
            "## Historical context\nWritten during the resistance war, the poem celebrates anonymous soldiers who drive open-top trucks through bombing zones. It transforms logistical labor into national myth by naming those history might forget.",
            "## Theme: vulnerability as courage\n\"KhÃ´ng cÃ³ kÃ­nh\" is not lack but choice: wind, dust, and rain strike the body directly â€” yet drivers sit \"ung dung,\" looking at earth, sky, and heart without filter. Exposure becomes moral freedom.",
            "## Imagery and artistic features\nVivid details â€” dust whitening hair like old age, rain soaking shirts, stars and birds rushing into the cabin â€” materialize heroism without bombast. The HoÃ ng Cáº§m stove scene turns the convoy into improvised family.",
            "## Rhythm and structure\nRefrain-like openings (\"KhÃ´ng cÃ³ kÃ­nh...\") build incantatory rhythm. Episodic structure mirrors the road; humor (\"cÆ°á»i ha ha\") alternates with solemn danger.",
            "## Rhetorical devices\nAnaphora and ironic understatement elevate ordinary youth. Collective handshake \"qua cá»­a kÃ­nh vá»¡\" symbolizes solidarity forged in shared risk.",
            "## Conclusion\nThe poem teaches that heroism is often anonymous and ordinary. Youth on the trail becomes eternal through poetic naming and collective memory.",
            "## Intertextuality\nBelongs to TrÆ°á»ng SÆ¡n war poetry tradition (Pháº¡m Tiáº¿n Duáº­t, Nguyá»…n Khoa Äiá»m). Unlike infantry battle poems, this text honors support troops and ironic resilience.",
        ];
    } elseif ($work === 'lao_hac') {
        $title = match ($variant) {
            'hs2' => 'Analysis of "LÃ£o Háº¡c"',
            'hs3' => 'LÃ£o Háº¡c â€” moral choice at the edge of survival',
            'hs10' => 'LÃ£o Háº¡c â€” fatherhood and dignity',
            default => 'LÃ£o Háº¡c (Nam Cao)',
        };
        $extra = match ($variant) {
            'hs2' => "## Father-son bond\nFather-son love drives every decision. The garden plot symbolizes hope retained for the absent son; selling cáº­u VÃ ng wounds the bond materialized in the dog bought for the son's future wedding feast.",
            'hs3' => "## Final moral choice\nDeath by poisoned bait is a last act of self-respect: he protects his son's inheritance and refuses to burden neighbors. Understatement at the climax intensifies tragedy.",
            'hs10' => "## Humanity under poverty\nThe teacher-narrator's empathy models \"cá»‘ tÃ¬m mÃ  hiá»ƒu.\" Every economic pressure tightens dignity without destroying moral core.",
            default => "## Narrative perspective\nFirst-person teacher narration moves from misunderstanding to profound empathy. The narrator's initial incomprehension mirrors society's blindness to peasant interiority.",
        };
        $blocks = [
            "# {$title}",
            '**Keywords:** poverty, dignity, peasantry, sacrifice, realism, humanity',
            "## Poverty and human dignity\nOld Man Hac embodies pre-revolutionary peasant tragedy. Selling his dog \"cáº­u VÃ ng\" is economic desperation and spiritual wound â€” companion, family surrogate, last symbol of self-respect. Nam Cao refuses sentimental poverty porn; systemic inequality forces impossible choices.",
            $extra,
            "## Characterization\nLÃ£o Háº¡c is drawn through gesture, speech habit, and silence. Minor characters (LÃ½, GiÃ¡o, neighbors) form a social chorus witnessing his fall.",
            "## Artistic techniques\nUnderstatement dominates climactic moments. Symbolism of garden plot and poisoned bait condenses theme without allegorical heaviness. Sparse dialogue is socially revealing.",
            "## Conclusion\nHumanistic message: dignity persists when society denies peasants full personhood. A landmark of Vietnamese realist fiction.",
            "## Intertextuality\nCompared with NgÃ´ Táº¥t Tá»‘'s \"Táº¯t Ä‘Ã¨n\": both expose rural suffering; Nam Cao emphasizes inner tragedy over outward rebellion. Compared with Kim LÃ¢n's wartime stories: quieter, more psychologically concentrated.",
        ];
    } elseif ($work === 'chan_que') {
        $blocks = [
            '# ChÃ¢n quÃª (Nguyá»…n BÃ­nh)',
            '**Keywords:** homeland, nostalgia, rural imagery, rootedness, village life',
            "## Thesis\n\"ChÃ¢n quÃª\" celebrates rootedness through concrete rural detail and musical folk rhythm â€” not abstract urban longing alone.",
            "## Counter-argument to AI draft\nThe AI over-emphasized generic nostalgia. The poem's strength is sensory particularity: paths, labor, neighborly speech, seasonal cycles anchor identity in place.",
            "## Imagery and rhythm\nBalanced phrases echo folk cadence. Each image resists clichÃ© â€” scenery belongs to workers, not postcard beauty.",
            "## Thematic depth\nHomeland is ethical belonging: memory of village is moral orientation, not mere sentiment.",
            "## Formal features\nLyric structure moves from scene to reflection to quiet affirmation. Repetition reinforces rooted identity.",
            "## Conclusion\nNguyá»…n BÃ­nh shows authentic nostalgia requires embodied memory. The poem endures through restraint and folk musicality.",
            "## Intertextuality\nDialogues with Táº¿ Hanh's \"QuÃª hÆ°Æ¡ng\" and New Poetry countryside depictions â€” BÃ­nh's voice is gentler, more folk-inflected, morally grounded.",
        ];
    }

    return $blocks;
}

function saveResponseAnalysis(PDO $pdo, int $responseId, int $userId, array $json, array $contentBlocks): void
{
    $jsonStr = json_encode($json, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    $upd = $pdo->prepare('UPDATE response SET json_ai = :json, updated_at = NOW() WHERE id_response = :id');
    $upd->execute([':json' => $jsonStr, ':id' => $responseId]);

    $pdo->prepare('DELETE FROM content WHERE response_id = :rid')->execute([':rid' => $responseId]);

    $ins = $pdo->prepare('
        INSERT INTO content (response_id, content, `order`, type_create, added_by, created_at, updated_at)
        VALUES (:rid, :content, :ord, \'user\', :uid, NOW(), NOW())
    ');

    foreach ($contentBlocks as $i => $text) {
        $ins->execute([
            ':rid' => $responseId,
            ':content' => $text,
            ':ord' => $i + 1,
            ':uid' => $userId,
        ]);
    }
}

// response_id => [work, variant, owner user_id from seed]
$map = [
    1  => ['khuc_mua_thu', 'default', 390],
    2  => ['khuc_mua_thu', 'group2', 388],
    3  => ['khuc_mua_thu', 'default', 390],
    4  => ['khuc_mua_thu', 'default', 366],
    5  => ['war_poem', 'default', 390],
    6  => ['war_poem', 'group2', 388],
    7  => ['war_poem', 'default', 366],
    8  => ['lao_hac', 'default', 388],
    9  => ['lao_hac', 'hs2', 391],
    10 => ['lao_hac', 'hs3', 392],
    11 => ['lao_hac', 'hs10', 401],
    12 => ['war_poem', 'topic37', 388],
    13 => ['war_poem', 'topic37', 391],
    14 => ['chan_que', 'default', 390],
];

$jsonBuilders = [
    'khuc_mua_thu' => 'poetryKhucMuaThuJson',
    'war_poem'     => 'poetryWarTruckJson',
    'lao_hac'      => 'storyLaoHacJson',
    'chan_que'     => 'poetryChanQueJson',
];

echo "Seeding full English analysis data...\n";

try {
    $pdo->beginTransaction();
    foreach ($map as $responseId => [$work, $variant, $userId]) {
        $builder = $jsonBuilders[$work];
        $json = $builder();
        $blocks = buildContentBlocks($work, $variant);
        saveResponseAnalysis($pdo, $responseId, $userId, $json, $blocks);
        echo "  OK response #{$responseId} ({$work}/{$variant}) â€” " . count($blocks) . " content blocks\n";
    }
    $pdo->commit();
    echo "Done. Updated " . count($map) . " submissions.\n";
} catch (Throwable $e) {
    $pdo->rollBack();
    fwrite(STDERR, "ERROR: " . $e->getMessage() . "\n");
    exit(1);
}

