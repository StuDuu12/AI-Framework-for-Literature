<?php

/**
 * Literary knowledge canon — student workspace (Vietnam + international).
 */

function literaryCanonTableExists()
{
    global $pdo;

    try {
        $stmt = $pdo->query("SHOW TABLES LIKE 'literary_work'");
        return $stmt && $stmt->rowCount() > 0;
    } catch (PDOException $e) {
        return false;
    }
}

/**
 * Map DB row to workspace catalog item.
 */
function mapLiteraryWorkRow(array $row)
{
    $gradients = [
        'poem' => 'linear-gradient(135deg, #38bdf8 0%, #0369a1 100%)',
        'story' => 'linear-gradient(135deg, #f59e0b 0%, #b45309 100%)',
        'novel' => 'linear-gradient(135deg, #a78bfa 0%, #5b21b6 100%)',
        'drama' => 'linear-gradient(135deg, #f43f5e 0%, #9f1239 100%)',
        'essay' => 'linear-gradient(135deg, #34d399 0%, #047857 100%)',
        'epic' => 'linear-gradient(135deg, #6366f1 0%, #312e81 100%)',
    ];

    $genre = $row['genre'] ?? 'story';
    $cover = $row['cover_image'] ?? '';
    if ($cover && strpos($cover, 'http') !== 0 && strpos($cover, '/') === 0) {
        $cover = (defined('URL_ROOT') ? URL_ROOT : '') . $cover;
    }

    return [
        'id' => (int) ($row['id'] ?? 0),
        'slug' => 'lw-' . ($row['id'] ?? 0),
        'title' => $row['title'] ?? '',
        'title_original' => $row['title_original'] ?? '',
        'author' => $row['author'] ?? '',
        'region' => $row['region'] ?? 'vietnam',
        'country' => $row['country'] ?? '',
        'genre' => $genre,
        'genre_label' => $row['genre_label'] ?? ucfirst($genre),
        'era' => $row['era'] ?? '',
        'excerpt' => $row['excerpt'] ?? '',
        'description' => $row['description'] ?? '',
        'keywords' => $row['keywords'] ?? '',
        'cover_image' => $cover,
        'wikipedia_url' => $row['wikipedia_url'] ?? '',
        'full_content' => $row['full_content'] ?? '',
        'content_html' => !empty($row['content_html']),
        'author_id' => isset($row['author_id']) ? (int) $row['author_id'] : null,
        'gradient' => $gradients[$genre] ?? $gradients['story'],
        'featured' => !empty($row['featured']),
        'source' => 'canon',
    ];
}

/**
 * Full canon for gallery (knowledge base only — not class tasks).
 */
function getLiteraryCanonWorks($filters = [])
{
    global $pdo;

    if (!literaryCanonTableExists()) {
        return getCuratedLiteraryWorksFallback();
    }

    $region = $filters['region'] ?? '';
    $genre = $filters['genre'] ?? '';
    $featuredOnly = !empty($filters['featured']);
    $limit = (int) ($filters['limit'] ?? 0);

    $sql = 'SELECT * FROM literary_work WHERE 1=1';
    $params = [];

    if ($region && in_array($region, ['vietnam', 'international'], true)) {
        $sql .= ' AND region = :region';
        $params[':region'] = $region;
    }
    if ($genre && in_array($genre, ['poem', 'story', 'novel', 'drama', 'essay', 'epic'], true)) {
        $sql .= ' AND genre = :genre';
        $params[':genre'] = $genre;
    }
    if ($featuredOnly) {
        $sql .= ' AND featured = 1';
    }

    $sql .= ' ORDER BY featured DESC, sort_order ASC, title ASC';
    if ($limit > 0) {
        $sql .= ' LIMIT ' . $limit;
    }

    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return array_map('mapLiteraryWorkRow', $stmt->fetchAll(PDO::FETCH_ASSOC));
    } catch (PDOException $e) {
        error_log('getLiteraryCanonWorks: ' . $e->getMessage());
        return getCuratedLiteraryWorksFallback();
    }
}

/**
 * Search local canon (FULLTEXT with LIKE fallback).
 */
function searchLiteraryCanon($query, $limit = 12)
{
    global $pdo;

    $query = trim($query);
    if ($query === '' || !literaryCanonTableExists()) {
        return [];
    }

    $limit = max(1, min(30, (int) $limit));

    try {
        $stmt = $pdo->prepare("
            SELECT *, MATCH(title, author, keywords, description) AGAINST(:q IN NATURAL LANGUAGE MODE) AS relevance
            FROM literary_work
            WHERE MATCH(title, author, keywords, description) AGAINST(:q2 IN NATURAL LANGUAGE MODE)
               OR title LIKE :like
               OR author LIKE :like2
               OR keywords LIKE :like3
            ORDER BY relevance DESC, featured DESC, sort_order ASC
            LIMIT {$limit}
        ");
        $like = '%' . $query . '%';
        $stmt->execute([
            ':q' => $query,
            ':q2' => $query,
            ':like' => $like,
            ':like2' => $like,
            ':like3' => $like,
        ]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        if (!empty($rows)) {
            return array_map('mapLiteraryWorkRow', $rows);
        }
    } catch (PDOException $e) {
        // FULLTEXT may fail on short terms — fall through to LIKE
    }

    try {
        $stmt = $pdo->prepare("
            SELECT * FROM literary_work
            WHERE title LIKE :like OR author LIKE :like2 OR keywords LIKE :like3
            ORDER BY featured DESC, sort_order ASC
            LIMIT {$limit}
        ");
        $like = '%' . $query . '%';
        $stmt->execute([':like' => $like, ':like2' => $like, ':like3' => $like]);
        return array_map('mapLiteraryWorkRow', $stmt->fetchAll(PDO::FETCH_ASSOC));
    } catch (PDOException $e) {
        error_log('searchLiteraryCanon: ' . $e->getMessage());
        return [];
    }
}

/**
 * Suggest from local canon (autocomplete).
 */
function suggestLiteraryCanon($query, $limit = 8)
{
    return searchLiteraryCanon($query, $limit);
}

/**
 * Fetch Wikipedia opensearch suggestions (VI + EN).
 */
function fetchWikipediaSuggestions($query, $limit = 6)
{
    $query = trim($query);
    if ($query === '') {
        return [];
    }

    $results = [];
    $langs = ['vi', 'en'];

    foreach ($langs as $lang) {
        $url = 'https://' . $lang . '.wikipedia.org/w/api.php?' . http_build_query([
            'action' => 'opensearch',
            'search' => $query,
            'limit' => $limit,
            'namespace' => 0,
            'format' => 'json',
        ]);

        $json = literaryHttpGet($url);
        if (!$json) {
            continue;
        }

        $data = json_decode($json, true);
        if (!is_array($data) || count($data) < 4) {
            continue;
        }

        $titles = $data[1] ?? [];
        $descriptions = $data[2] ?? [];
        $urls = $data[3] ?? [];

        foreach ($titles as $i => $title) {
            if (count($results) >= $limit * 2) {
                break 2;
            }
            $key = mb_strtolower($title);
            if (isset($results[$key])) {
                continue;
            }
            $results[$key] = [
                'title' => $title,
                'snippet' => $descriptions[$i] ?? '',
                'url' => $urls[$i] ?? ('https://' . $lang . '.wikipedia.org/wiki/' . rawurlencode(str_replace(' ', '_', $title))),
                'source' => 'wikipedia',
                'lang' => $lang,
            ];
        }
    }

    return array_values(array_slice($results, 0, $limit));
}

/**
 * Wikipedia full search results with short extract.
 */
function fetchWikipediaSearchResults($query, $limit = 8)
{
    $query = trim($query);
    if ($query === '') {
        return [];
    }

    $items = [];
    foreach (['vi', 'en'] as $lang) {
        $url = 'https://' . $lang . '.wikipedia.org/w/api.php?' . http_build_query([
            'action' => 'query',
            'list' => 'search',
            'srsearch' => $query . ' literature OR văn học OR tác phẩm OR tác giả',
            'srlimit' => $limit,
            'format' => 'json',
        ]);

        $json = literaryHttpGet($url);
        if (!$json) {
            continue;
        }

        $data = json_decode($json, true);
        $hits = $data['query']['search'] ?? [];
        foreach ($hits as $hit) {
            $title = $hit['title'] ?? '';
            if ($title === '') {
                continue;
            }
            $key = mb_strtolower($title);
            if (isset($items[$key])) {
                continue;
            }
            $snippet = strip_tags($hit['snippet'] ?? '');
            $items[$key] = [
                'title' => $title,
                'snippet' => html_entity_decode($snippet, ENT_QUOTES | ENT_HTML5, 'UTF-8'),
                'url' => 'https://' . $lang . '.wikipedia.org/wiki/' . rawurlencode(str_replace(' ', '_', $title)),
                'source' => 'wikipedia',
                'lang' => $lang,
            ];
        }
    }

    return array_values(array_slice($items, 0, $limit));
}

function literaryHttpGet($url)
{
    if (function_exists('curl_init')) {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 6,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_USERAGENT => 'LiteratureVer2-Workspace/1.0',
        ]);
        $body = curl_exec($ch);
        curl_close($ch);
        return $body ?: null;
    }

    $ctx = stream_context_create([
        'http' => [
            'timeout' => 6,
            'header' => "User-Agent: LiteratureVer2-Workspace/1.0\r\n",
        ],
    ]);
    $body = @file_get_contents($url, false, $ctx);
    return $body ?: null;
}

/**
 * Featured + popular suggestion chips.
 */
function getLiterarySuggestionChips($limit = 10)
{
    $works = getLiteraryCanonWorks(['featured' => true, 'limit' => $limit]);
    $chips = [];
    foreach ($works as $w) {
        $chips[] = ['label' => $w['title'], 'author' => $w['author'], 'query' => $w['title']];
    }
    return $chips;
}

/** @deprecated Use getLiteraryCanonWorks */
function getWorkspaceLiteraryCatalog($userId)
{
    return getLiteraryCanonWorks();
}

function getCuratedLiteraryWorksFallback()
{
    $legacy = getCuratedLiteraryWorks();
    foreach ($legacy as &$w) {
        $w['source'] = 'canon';
        $w['region'] = 'vietnam';
        $w['cover_image'] = '';
        $w['wikipedia_url'] = '';
    }
    return $legacy;
}

function mapLiteraryAuthorRow(array $row)
{
    $portrait = $row['portrait_image'] ?? '';
    if ($portrait && strpos($portrait, 'http') !== 0 && strpos($portrait, '/') === 0) {
        $portrait = (defined('URL_ROOT') ? URL_ROOT : '') . $portrait;
    }

    $lifespan = '';
    if (!empty($row['birth_year'])) {
        $lifespan = $row['birth_year'];
        if (!empty($row['death_year'])) {
            $lifespan .= ' – ' . $row['death_year'];
        }
    }

    return [
        'id' => (int) ($row['id'] ?? 0),
        'name' => $row['name'] ?? '',
        'name_original' => $row['name_original'] ?? '',
        'slug' => $row['slug'] ?? '',
        'region' => $row['region'] ?? 'vietnam',
        'country' => $row['country'] ?? '',
        'birth_year' => $row['birth_year'] ?? '',
        'death_year' => $row['death_year'] ?? '',
        'lifespan' => $lifespan,
        'era' => $row['era'] ?? '',
        'biography' => $row['biography'] ?? '',
        'portrait_image' => $portrait,
        'wikipedia_url' => $row['wikipedia_url'] ?? '',
    ];
}

function getLiteraryWorkById($id)
{
    global $pdo;

    $id = (int) $id;
    if ($id <= 0 || !literaryCanonTableExists()) {
        return null;
    }

    try {
        $stmt = $pdo->prepare('SELECT * FROM literary_work WHERE id = :id LIMIT 1');
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ? mapLiteraryWorkRow($row) : null;
    } catch (PDOException $e) {
        error_log('getLiteraryWorkById: ' . $e->getMessage());
        return null;
    }
}

function getLiteraryAuthorById($id)
{
    global $pdo;

    $id = (int) $id;
    if ($id <= 0) {
        return null;
    }

    try {
        $stmt = $pdo->prepare('SELECT * FROM literary_author WHERE id = :id LIMIT 1');
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ? mapLiteraryAuthorRow($row) : null;
    } catch (PDOException $e) {
        return null;
    }
}

function getLiteraryAuthorForWork(array $work)
{
    if (!empty($work['author_id'])) {
        $author = getLiteraryAuthorById($work['author_id']);
        if ($author) {
            return $author;
        }
    }

    global $pdo;
    if (empty($work['author']) || !literaryCanonTableExists()) {
        return null;
    }

    try {
        $stmt = $pdo->prepare('SELECT * FROM literary_author WHERE name = :name LIMIT 1');
        $stmt->execute([':name' => $work['author']]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ? mapLiteraryAuthorRow($row) : null;
    } catch (PDOException $e) {
        return null;
    }
}

function getRelatedWorksByAuthor($authorId, $excludeWorkId = 0, $limit = 8)
{
    global $pdo;

    $authorId = (int) $authorId;
    $excludeWorkId = (int) $excludeWorkId;
    $limit = max(1, min(20, (int) $limit));

    if ($authorId <= 0 || !literaryCanonTableExists()) {
        return [];
    }

    try {
        $stmt = $pdo->prepare("
            SELECT * FROM literary_work
            WHERE author_id = :aid AND id != :wid
            ORDER BY featured DESC, sort_order ASC, title ASC
            LIMIT {$limit}
        ");
        $stmt->execute([':aid' => $authorId, ':wid' => $excludeWorkId]);
        return array_map('mapLiteraryWorkRow', $stmt->fetchAll(PDO::FETCH_ASSOC));
    } catch (PDOException $e) {
        error_log('getRelatedWorksByAuthor: ' . $e->getMessage());
        return [];
    }
}

function getCanonListFiltersFromRequest()
{
    return [
        'region' => $_GET['region'] ?? '',
        'genre' => $_GET['genre'] ?? '',
        'q' => trim($_GET['q'] ?? ''),
    ];
}

function searchCanonForLibrary($filters = [], $limit = 0)
{
    $q = trim($filters['q'] ?? '');
    if ($q !== '') {
        return searchLiteraryCanon($q, $limit > 0 ? $limit : 50);
    }

    $region = $filters['region'] ?? '';
    $genre = $filters['genre'] ?? '';
    return getLiteraryCanonWorks([
        'region' => $region,
        'genre' => $genre,
        'limit' => $limit > 0 ? $limit : 0,
    ]);
}

/**
 * Quick stats for workspace header.
 */
function getStudentWorkspaceStats($userId)
{
    global $pdo;

    $userId = (int) $userId;
    $stats = [
        'classes' => 0,
        'tasks' => 0,
        'completed' => 0,
        'pending_eval' => 0,
        'canon_count' => 0,
    ];

    try {
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM class_member WHERE user_id = :uid');
        $stmt->execute([':uid' => $userId]);
        $stats['classes'] = (int) $stmt->fetchColumn();

        require_once __DIR__ . '/helper_task.php';
        $stats['tasks'] = countStudentAssignedDelivers($pdo, $userId, false);
        $stats['completed'] = countStudentAssignedDelivers($pdo, $userId, true);

        $stmt = $pdo->prepare('SELECT COUNT(*) FROM evaluate WHERE user_id = :uid AND status < 2');
        $stmt->execute([':uid' => $userId]);
        $stats['pending_eval'] = (int) $stmt->fetchColumn();

        if (literaryCanonTableExists()) {
            $stats['canon_count'] = (int) $pdo->query('SELECT COUNT(*) FROM literary_work')->fetchColumn();
        }
    } catch (PDOException $e) {
        error_log('getStudentWorkspaceStats: ' . $e->getMessage());
    }

    return $stats;
}

// Legacy inline list kept as DB migration fallback only
function getCuratedLiteraryWorks()
{
    return [
        ['id' => 1, 'title' => 'Lão Hạc', 'author' => 'Nam Cao', 'genre' => 'story', 'genre_label' => 'Short Story',
            'excerpt' => 'Lão Hạc thổi cái mồi rơm, châm đóm.', 'gradient' => 'linear-gradient(135deg,#f59e0b,#92400e)',
            'keywords' => 'nam cao lao hac', 'region' => 'vietnam', 'featured' => 1],
        ['id' => 2, 'title' => 'Truyện Kiều', 'author' => 'Nguyễn Du', 'genre' => 'epic', 'genre_label' => 'Epic Poem',
            'excerpt' => 'Trăm năm trong cõi người ta...', 'gradient' => 'linear-gradient(135deg,#6366f1,#312e81)',
            'keywords' => 'nguyen du truyen kieu', 'region' => 'vietnam', 'featured' => 1],
    ];
}