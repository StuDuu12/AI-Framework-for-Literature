<?php
if (PHP_SAPI !== 'cli') { http_response_code(403); exit('CLI only'); }
/**
 * Download literary work cover images â€” Open Library, Gutenberg, Internet Archive (not Wikipedia-first).
 * Run: php fetch_literary_covers.php
 *       php fetch_literary_covers.php --missing-only
 *       php fetch_literary_covers.php --force
 */
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../config/helper_workspace.php';

$outDir = __DIR__ . '/assets/img/literary';
if (!is_dir($outDir)) {
    mkdir($outDir, 0755, true);
}

$missingOnly = in_array('--missing-only', $argv ?? [], true);
$force = in_array('--force', $argv ?? [], true);

function downloadImage($url)
{
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_TIMEOUT => 45,
        CURLOPT_USERAGENT => 'LiteratureVer2/1.0 (https://literaturever2.test; educational)',
        CURLOPT_HTTPHEADER => ['Accept: image/*,*/*'],
    ]);
    $body = curl_exec($ch);
    $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $type = curl_getinfo($ch, CURLINFO_CONTENT_TYPE) ?: '';
    curl_close($ch);

    if ($code < 200 || $code >= 300 || !$body || strlen($body) < 800) {
        return null;
    }
    return ['body' => $body, 'type' => $type, 'url' => $url];
}

function slugify($s)
{
    $ascii = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $s);
    $s = strtolower($ascii ?: $s);
    $s = preg_replace('/[^a-z0-9]+/', '-', $s);
    return trim($s, '-') ?: 'work';
}

function extFrom($mime, $url)
{
    if (stripos($mime, 'png') !== false) {
        return 'png';
    }
    if (stripos($mime, 'webp') !== false) {
        return 'webp';
    }
    if (preg_match('/\.(jpe?g|png|webp)(\?|$)/i', $url, $m)) {
        return $m[1] === 'jpeg' ? 'jpg' : strtolower($m[1]);
    }
    return 'jpg';
}

function openLibraryCoverUrl($title, $author = '')
{
    $q = trim($title . ($author ? ' ' . $author : ''));
    $url = 'https://openlibrary.org/search.json?' . http_build_query([
        'q' => $q,
        'limit' => 5,
        'fields' => 'title,author_name,cover_i',
    ]);
    $json = literaryHttpGet($url);
    if (!$json) {
        return null;
    }
    $data = json_decode($json, true);
    $titleLower = mb_strtolower($title);
    foreach ($data['docs'] ?? [] as $doc) {
        if (empty($doc['cover_i'])) {
            continue;
        }
        $docTitle = mb_strtolower($doc['title'] ?? '');
        if ($docTitle === $titleLower || strpos($docTitle, $titleLower) !== false || strpos($titleLower, $docTitle) !== false) {
            return 'https://covers.openlibrary.org/b/id/' . $doc['cover_i'] . '-L.jpg';
        }
    }
    foreach ($data['docs'] ?? [] as $doc) {
        if (!empty($doc['cover_i'])) {
            return 'https://covers.openlibrary.org/b/id/' . $doc['cover_i'] . '-L.jpg';
        }
    }
    return null;
}

function gutenbergCoverUrl($title, $author = '')
{
    $q = trim($title . ($author ? ' ' . $author : ''));
    $url = 'https://gutendex.com/books/?search=' . rawurlencode($q);
    $json = literaryHttpGet($url);
    if (!$json) {
        return null;
    }
    $data = json_decode($json, true);
    foreach ($data['results'] ?? [] as $book) {
        foreach ($book['formats'] ?? [] as $fmt => $link) {
            if (strpos($fmt, 'image/jpeg') === 0 || strpos($fmt, 'image/png') === 0) {
                return $link;
            }
        }
    }
    return null;
}

function archiveCoverUrl($title, $author = '')
{
    $q = 'title:("' . $title . '") OR creator:("' . ($author ?: $title) . '") AND mediatype:texts';
    $url = 'https://archive.org/advancedsearch.php?' . http_build_query([
        'q' => $q,
        'fl' => 'identifier,title',
        'rows' => 3,
        'output' => 'json',
    ]);
    $json = literaryHttpGet($url);
    if (!$json) {
        return null;
    }
    $data = json_decode($json, true);
    foreach ($data['response']['docs'] ?? [] as $doc) {
        $id = $doc['identifier'] ?? '';
        if ($id) {
            return 'https://archive.org/services/img/' . $id;
        }
    }
    return null;
}

function commonsFileUrl($filename, $width = 640)
{
    return 'https://commons.wikimedia.org/wiki/Special:FilePath/' . rawurlencode($filename) . '?width=' . $width;
}

/** Direct Commons files (not Wikipedia page API) */
$commonsFiles = [
    'LÃ£o Háº¡c' => 'Portrait of Nam Cao.jpg',
    'Vá»£ nháº·t' => 'Kim LÃ¢n SÆ¡n Xuáº¥t Tháº¿.jpg',
    'Vá»£ chá»“ng A Phá»§' => 'Portrait of Nam Cao.jpg',
    'Lá»¡ bÆ°á»›c sang ngang' => 'Nguyen Binh.jpg',
    'ChÃ¢n quÃª' => 'Nguyen Binh.jpg',
    'MÆ°a phÃ¹n' => 'Vu Trong Phung.jpg',
    'ÄÃ¢y thÃ´n VÄ© Dáº¡nh' => 'Portrait of HÃ n Máº·c Tá»­ (cropped).jpg',
    'Äau thÆ°Æ¡ng' => 'Hanmactu.jpg',
    'Dáº¿ MÃ¨n PhiÃªu LÆ°u KÃ½' => 'BÃ¬a_Dáº¿_MÃ¨n_PhiÃªu_LÆ°u_KÃ½_cá»§a_NXB_TÃ¢n_DÃ¢n.jpg',
    'ChÃ­ PhÃ¨o' => 'Chi_Pheo_cover_1957.jpg',
    'Truyá»‡n Kiá»u' => 'Kim_VÃ¢n_Kiá»u_tÃ¢n_truyá»‡n.jpg',
    'Sá»‘ Ä‘á»' => 'Sodobanindau.jpg',
    'GiÃ³ lÃªn' => 'Ivory comb.jpg',
    'CÃ¡m Æ¡n sá»± Ä‘á»i Ä‘Ã¡nh thá»©c há»“n má»™ng má»‹' => 'Ivory comb.jpg',
    'Sang thu' => 'Há»¯u Thá»‰nh.jpg',
    'MÃ¹a lÃ¡ rÆ¡i lá»… há»™i' => 'Há»¯u Thá»‰nh.jpg',
    'NÃ³i vá»›i con' => 'Temple of Literature Hanoi.jpg',
    'ÄÃ n bÃ  tráº¯ng' => 'Temple of Literature Hanoi.jpg',
    'NÃ³i khÃ©p máº¯t láº¡i' => 'Temple of Literature Hanoi.jpg',
    'Nam quá»‘c sÆ¡n hÃ ' => 'Chá»¯ NÃ´m.jpg',
    'Qua Ä‘Ã¨o Ngang' => 'Lang Co beach.jpg',
    'Má»™t chiáº¿c lÆ°á»£c ngÃ ' => 'Ivory comb.jpg',
    'Nhá»› mÃ¹a thu HÃ  Ná»™i' => 'Hoan Kiem Lake.jpg',
];

/** Author portrait / thematic Commons fallback */
$authorCommonsFiles = [
    'XuÃ¢n Quá»³nh' => 'Ivory comb.jpg',
    'Y PhÆ°Æ¡ng' => 'Temple of Literature Hanoi.jpg',
    'Há»¯u Thá»‰nh' => 'Há»¯u Thá»‰nh.jpg',
    'LÃ½ ThÆ°á»ng Kiá»‡t' => 'Chá»¯ NÃ´m.jpg',
    'BÃ  Huyá»‡n Thanh Quan' => 'Phá»‘ BÃ  Huyá»‡n Thanh Quan, HÃ  Ná»™i 003.JPG',
    'Nguyá»…n BÃ¡ Khoáº£n' => 'Ivory comb.jpg',
    'VÅ© Kháº¯c Tung' => 'Hoan Kiem Lake.jpg',
];

/** Curated Open Library cover IDs (not Wikipedia) */
$openLibraryCoverIds = [
    'GiÃ³ lÃªn' => 9987118,
    'CÃ¡m Æ¡n sá»± Ä‘á»i Ä‘Ã¡nh thá»©c há»“n má»™ng má»‹' => 9987118,
    'NÃ³i vá»›i con' => 161662,
    'ÄÃ n bÃ  tráº¯ng' => 161662,
    'NÃ³i khÃ©p máº¯t láº¡i' => 161662,
    'XuÃ¢n Quá»³nh' => 9987118,
    'Y PhÆ°Æ¡ng' => 161662,
];

function openLibraryCoverById($coverId)
{
    $coverId = (int) $coverId;
    return $coverId > 0 ? 'https://covers.openlibrary.org/b/id/' . $coverId . '-L.jpg' : null;
}

function wikidataAuthorImageUrl($author)
{
    $author = trim($author);
    if ($author === '') {
        return null;
    }

    $searchUrl = 'https://www.wikidata.org/w/api.php?' . http_build_query([
        'action' => 'wbsearchentities',
        'search' => $author,
        'language' => 'vi',
        'format' => 'json',
        'limit' => 2,
    ]);
    $json = literaryHttpGet($searchUrl);
    $data = json_decode($json ?: '[]', true);
    foreach ($data['search'] ?? [] as $hit) {
        $id = $hit['id'] ?? '';
        if ($id === '') {
            continue;
        }
        $entityJson = literaryHttpGet('https://www.wikidata.org/wiki/Special:EntityData/' . $id . '.json');
        $entity = json_decode($entityJson ?: '[]', true);
        $filename = $entity['entities'][$id]['claims']['P18'][0]['mainsnak']['datavalue']['value'] ?? '';
        if ($filename) {
            return commonsFileUrl($filename);
        }
    }
    return null;
}

function resolveCoverUrl($title, $author, $commonsFiles, $authorCommonsFiles = [], $openLibraryCoverIds = [])
{
    $sources = [
        fn() => !empty($openLibraryCoverIds[$title]) ? openLibraryCoverById($openLibraryCoverIds[$title]) : null,
        fn() => !empty($openLibraryCoverIds[$author]) ? openLibraryCoverById($openLibraryCoverIds[$author]) : null,
        fn() => openLibraryCoverUrl($title, $author),
        fn() => gutenbergCoverUrl($title, $author),
        fn() => archiveCoverUrl($title, $author),
        fn() => !empty($commonsFiles[$title]) ? commonsFileUrl($commonsFiles[$title]) : null,
        fn() => !empty($authorCommonsFiles[$author]) ? commonsFileUrl($authorCommonsFiles[$author]) : null,
        fn() => wikidataAuthorImageUrl($author),
        fn() => openLibraryCoverUrl($author . ' Vietnamese poetry', ''),
        fn() => openLibraryCoverUrl($author, ''),
    ];

    foreach ($sources as $fn) {
        $url = $fn();
        if ($url) {
            $test = downloadImage($url);
            if ($test) {
                return ['url' => $url, 'body' => $test['body'], 'type' => $test['type']];
            }
        }
        usleep(200000);
    }
    return null;
}

function hasValidLocalCover($work)
{
    $existing = $work['cover_image'] ?? '';
    if (!$existing || strpos($existing, '/assets/img/literary/') !== 0) {
        return false;
    }
    $path = __DIR__ . $existing;
    return is_file($path) && filesize($path) > 800;
}

$works = $pdo->query('SELECT id, title, author, cover_image, wikipedia_url FROM literary_work ORDER BY id')->fetchAll(PDO::FETCH_ASSOC);
$upd = $pdo->prepare('UPDATE literary_work SET cover_image = :img WHERE id = :id');

// Fix known title typo in DB
$pdo->exec("UPDATE literary_work SET title = 'Vá»£ chá»“ng A Phá»§', wikipedia_url = 'https://vi.wikipedia.org/wiki/Vá»£_chá»“ng_A_Phá»§' WHERE title = 'Vá»£ chÃ³ng A Phá»§'");

$ok = 0;
$skip = 0;
$fail = 0;

foreach ($works as $work) {
    $id = (int) $work['id'];
    $title = $work['title'];
    $author = $work['author'] ?? '';

    if (!$force && hasValidLocalCover($work)) {
        if (!$missingOnly) {
            echo "SKIP #{$id} {$title} (local exists)\n";
            $skip++;
        }
        continue;
    }

    $resolved = resolveCoverUrl($title, $author, $commonsFiles, $authorCommonsFiles, $openLibraryCoverIds);
    if (!$resolved) {
        echo "FAIL #{$id} {$title}\n";
        $fail++;
        continue;
    }

    $ext = extFrom($resolved['type'], $resolved['url']);
    $filename = 'work-' . $id . '-' . slugify($title) . '.' . $ext;
    file_put_contents($outDir . '/' . $filename, $resolved['body']);

    $publicPath = '/assets/img/literary/' . $filename;
    $upd->execute([':img' => $publicPath, ':id' => $id]);
    echo "OK   #{$id} {$title} -> {$publicPath}\n";
    $ok++;
    usleep(250000);
}

echo "\nDone: {$ok} saved, {$skip} skipped, {$fail} failed.\n";

