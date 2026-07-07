<?php
/**
 * Download literary work cover images — Open Library, Gutenberg, Internet Archive (not Wikipedia-first).
 * Run: php fetch_literary_covers.php
 *       php fetch_literary_covers.php --missing-only
 *       php fetch_literary_covers.php --force
 */
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/config/helper_workspace.php';

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
    'Lão Hạc' => 'Portrait of Nam Cao.jpg',
    'Vợ nhặt' => 'Kim Lân Sơn Xuất Thế.jpg',
    'Vợ chồng A Phủ' => 'Portrait of Nam Cao.jpg',
    'Lỡ bước sang ngang' => 'Nguyen Binh.jpg',
    'Chân quê' => 'Nguyen Binh.jpg',
    'Mưa phùn' => 'Vu Trong Phung.jpg',
    'Đây thôn Vĩ Dạnh' => 'Portrait of Hàn Mặc Tử (cropped).jpg',
    'Đau thương' => 'Hanmactu.jpg',
    'Dế Mèn Phiêu Lưu Ký' => 'Bìa_Dế_Mèn_Phiêu_Lưu_Ký_của_NXB_Tân_Dân.jpg',
    'Chí Phèo' => 'Chi_Pheo_cover_1957.jpg',
    'Truyện Kiều' => 'Kim_Vân_Kiều_tân_truyện.jpg',
    'Số đỏ' => 'Sodobanindau.jpg',
    'Gió lên' => 'Ivory comb.jpg',
    'Cám ơn sự đời đánh thức hồn mộng mị' => 'Ivory comb.jpg',
    'Sang thu' => 'Hữu Thỉnh.jpg',
    'Mùa lá rơi lễ hội' => 'Hữu Thỉnh.jpg',
    'Nói với con' => 'Temple of Literature Hanoi.jpg',
    'Đàn bà trắng' => 'Temple of Literature Hanoi.jpg',
    'Nói khép mắt lại' => 'Temple of Literature Hanoi.jpg',
    'Nam quốc sơn hà' => 'Chữ Nôm.jpg',
    'Qua đèo Ngang' => 'Lang Co beach.jpg',
    'Một chiếc lược ngà' => 'Ivory comb.jpg',
    'Nhớ mùa thu Hà Nội' => 'Hoan Kiem Lake.jpg',
];

/** Author portrait / thematic Commons fallback */
$authorCommonsFiles = [
    'Xuân Quỳnh' => 'Ivory comb.jpg',
    'Y Phương' => 'Temple of Literature Hanoi.jpg',
    'Hữu Thỉnh' => 'Hữu Thỉnh.jpg',
    'Lý Thường Kiệt' => 'Chữ Nôm.jpg',
    'Bà Huyện Thanh Quan' => 'Phố Bà Huyện Thanh Quan, Hà Nội 003.JPG',
    'Nguyễn Bá Khoản' => 'Ivory comb.jpg',
    'Vũ Khắc Tung' => 'Hoan Kiem Lake.jpg',
];

/** Curated Open Library cover IDs (not Wikipedia) */
$openLibraryCoverIds = [
    'Gió lên' => 9987118,
    'Cám ơn sự đời đánh thức hồn mộng mị' => 9987118,
    'Nói với con' => 161662,
    'Đàn bà trắng' => 161662,
    'Nói khép mắt lại' => 161662,
    'Xuân Quỳnh' => 9987118,
    'Y Phương' => 161662,
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
$pdo->exec("UPDATE literary_work SET title = 'Vợ chồng A Phủ', wikipedia_url = 'https://vi.wikipedia.org/wiki/Vợ_chồng_A_Phủ' WHERE title = 'Vợ chóng A Phủ'");

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
