<?php
/**
 * Multi-source online literary search (not Wikipedia-only).
 */

function literarySourceMeta($source)
{
    $map = [
        'openlibrary' => ['label' => 'Open Library', 'color' => '#2b6cb0', 'icon' => 'bi-book-half'],
        'gutenberg' => ['label' => 'Project Gutenberg', 'color' => '#047857', 'icon' => 'bi-journal-text'],
        'archive' => ['label' => 'Internet Archive', 'color' => '#b45309', 'icon' => 'bi-archive'],
        'wikidata' => ['label' => 'Wikidata', 'color' => '#7c3aed', 'icon' => 'bi-database'],
        'wikipedia' => ['label' => 'Wikipedia', 'color' => '#64748b', 'icon' => 'bi-wikipedia'],
    ];
    return $map[$source] ?? ['label' => ucfirst($source), 'color' => '#475569', 'icon' => 'bi-globe2'];
}

function enrichOnlineResult(array $item)
{
    $meta = literarySourceMeta($item['source'] ?? 'web');
    $item['source_label'] = $meta['label'];
    $item['source_color'] = $meta['color'];
    $item['source_icon'] = $meta['icon'];
    return $item;
}
function fetchOpenLibraryResults($query, $limit = 5)
{
    $query = trim($query);
    if ($query === '') {
        return [];
    }

    $url = 'https://openlibrary.org/search.json?' . http_build_query([
        'q' => $query,
        'limit' => $limit,
        'fields' => 'key,title,author_name,first_publish_year,cover_i,subject',
    ]);

    $json = literaryHttpGet($url);
    if (!$json) {
        return [];
    }

    $data = json_decode($json, true);
    $docs = $data['docs'] ?? [];
    $out = [];

    foreach ($docs as $doc) {
        $title = $doc['title'] ?? '';
        if ($title === '') {
            continue;
        }
        $authors = $doc['author_name'] ?? [];
        $author = is_array($authors) ? implode(', ', array_slice($authors, 0, 2)) : '';
        $year = $doc['first_publish_year'] ?? '';
        $cover = '';
        if (!empty($doc['cover_i'])) {
            $cover = 'https://covers.openlibrary.org/b/id/' . $doc['cover_i'] . '-M.jpg';
        }
        $key = $doc['key'] ?? '';
        $snippet = $author;
        if ($year) {
            $snippet .= ($snippet ? ' · ' : '') . $year;
        }
        $subjects = $doc['subject'] ?? [];
        if (is_array($subjects) && !empty($subjects[0])) {
            $snippet .= ($snippet ? ' · ' : '') . $subjects[0];
        }

        $out[] = enrichOnlineResult([
            'title' => $title,
            'snippet' => $snippet,
            'url' => $key ? 'https://openlibrary.org' . $key : 'https://openlibrary.org/search?q=' . rawurlencode($query),
            'source' => 'openlibrary',
            'lang' => 'en',
            'cover' => $cover,
            'author' => $author,
        ]);
    }

    return $out;
}

function fetchGutenbergResults($query, $limit = 5)
{
    $query = trim($query);
    if ($query === '') {
        return [];
    }

    $url = 'https://gutendex.com/books/?' . http_build_query(['search' => $query]);
    $json = literaryHttpGet($url);
    if (!$json) {
        return [];
    }

    $data = json_decode($json, true);
    $out = [];
    foreach ($data['results'] ?? [] as $book) {
        $title = $book['title'] ?? '';
        if ($title === '') {
            continue;
        }
        $authors = array_filter(array_column($book['authors'] ?? [], 'name'));
        $author = implode(', ', array_slice($authors, 0, 2));
        $cover = '';
        foreach ($book['formats'] ?? [] as $fmt => $link) {
            if (strpos($fmt, 'image/jpeg') === 0 || strpos($fmt, 'image/png') === 0) {
                $cover = $link;
                break;
            }
        }

        $out[] = enrichOnlineResult([
            'title' => $title,
            'snippet' => ($author ? $author . ' · ' : '') . 'Public domain · free to read',
            'url' => 'https://www.gutenberg.org/ebooks/' . ($book['id'] ?? ''),
            'source' => 'gutenberg',
            'lang' => 'en',
            'cover' => $cover,
            'author' => $author,
        ]);
        if (count($out) >= $limit) {
            break;
        }
    }

    return $out;
}

function fetchInternetArchiveResults($query, $limit = 4)
{
    $query = trim($query);
    if ($query === '') {
        return [];
    }

    $url = 'https://archive.org/advancedsearch.php?' . http_build_query([
        'q' => 'title:(' . $query . ') OR creator:(' . $query . ') AND mediatype:texts',
        'fl' => 'identifier,title,creator,description',
        'rows' => $limit,
        'output' => 'json',
    ]);

    $json = literaryHttpGet($url);
    if (!$json) {
        return [];
    }

    $data = json_decode($json, true);
    $docs = $data['response']['docs'] ?? [];
    $out = [];

    foreach ($docs as $doc) {
        $title = $doc['title'] ?? '';
        if (is_array($title)) {
            $title = $title[0] ?? '';
        }
        if ($title === '') {
            continue;
        }
        $creator = $doc['creator'] ?? '';
        if (is_array($creator)) {
            $creator = implode(', ', array_slice($creator, 0, 2));
        }
        $desc = $doc['description'] ?? '';
        if (is_array($desc)) {
            $desc = $desc[0] ?? '';
        }
        $desc = mb_substr(strip_tags($desc), 0, 160);
        $id = $doc['identifier'] ?? '';

        $out[] = enrichOnlineResult([
            'title' => $title,
            'snippet' => ($creator ? $creator . ' · ' : '') . ($desc ?: 'Digitized text · Internet Archive'),
            'url' => $id ? 'https://archive.org/details/' . $id : 'https://archive.org/search?query=' . rawurlencode($query),
            'source' => 'archive',
            'lang' => 'en',
            'cover' => $id ? 'https://archive.org/services/img/' . $id : '',
            'author' => $creator,
        ]);
    }

    return $out;
}

function fetchWikidataLiteraryResults($query, $limit = 4)
{
    $query = trim($query);
    if ($query === '') {
        return [];
    }

    $url = 'https://www.wikidata.org/w/api.php?' . http_build_query([
        'action' => 'wbsearchentities',
        'search' => $query,
        'language' => 'vi',
        'uselang' => 'en',
        'type' => 'item',
        'limit' => $limit,
        'format' => 'json',
    ]);

    $json = literaryHttpGet($url);
    if (!$json) {
        return [];
    }

    $data = json_decode($json, true);
    $out = [];
    foreach ($data['search'] ?? [] as $hit) {
        $title = $hit['label'] ?? '';
        if ($title === '') {
            continue;
        }
        $desc = $hit['description'] ?? '';
        $id = $hit['id'] ?? '';
        if ($desc && stripos($desc, 'book') === false && stripos($desc, 'novel') === false
            && stripos($desc, 'poem') === false && stripos($desc, 'writer') === false
            && stripos($desc, 'literary') === false && stripos($desc, 'sách') === false
            && stripos($desc, 'tiểu thuyết') === false && stripos($desc, 'thơ') === false
            && stripos($desc, 'tác phẩm') === false) {
            continue;
        }

        $out[] = enrichOnlineResult([
            'title' => $title,
            'snippet' => $desc ?: 'Structured knowledge · Wikidata',
            'url' => $id ? 'https://www.wikidata.org/wiki/' . $id : 'https://www.wikidata.org',
            'source' => 'wikidata',
            'lang' => 'multi',
            'cover' => '',
            'author' => '',
        ]);
    }

    return $out;
}

/**
 * Merge online results from multiple sources (dedupe by title).
 */
function fetchMultiSourceOnlineResults($query, $perSource = 4, $totalLimit = 16)
{
    $query = trim($query);
    if ($query === '') {
        return [];
    }

    $buckets = [
        fetchOpenLibraryResults($query, $perSource),
        fetchGutenbergResults($query, $perSource),
        fetchInternetArchiveResults($query, $perSource),
        fetchWikidataLiteraryResults($query, $perSource),
        array_map('enrichOnlineResult', fetchWikipediaSearchResults($query, $perSource)),
    ];

    $merged = [];
    foreach ($buckets as $items) {
        foreach ($items as $item) {
            $key = mb_strtolower(($item['title'] ?? '') . '|' . ($item['source'] ?? ''));
            if (!isset($merged[$key])) {
                $merged[$key] = $item;
            }
        }
    }

    return array_values(array_slice($merged, 0, $totalLimit));
}

function fetchMultiSourceSuggestions($query, $limit = 8)
{
    $query = trim($query);
    if ($query === '') {
        return [];
    }

    $items = fetchMultiSourceOnlineResults($query, 3, $limit * 2);
    $suggestions = [];
    foreach ($items as $item) {
        $suggestions[] = [
            'title' => $item['title'],
            'snippet' => $item['snippet'] ?? '',
            'url' => $item['url'] ?? '',
            'source' => $item['source'] ?? 'web',
            'source_label' => $item['source_label'] ?? 'Web',
            'source_color' => $item['source_color'] ?? '#475569',
            'lang' => $item['lang'] ?? 'en',
            'cover' => $item['cover'] ?? '',
        ];
    }

    return array_slice($suggestions, 0, $limit);
}
