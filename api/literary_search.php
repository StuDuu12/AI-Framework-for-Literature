<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/helper_workspace.php';
require_once __DIR__ . '/../config/helper_online_sources.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

ob_start();
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['user'])) {
    ob_end_clean();
    echo json_encode(['success' => false, 'message' => 'Please log in']);
    exit;
}

$action = $_GET['action'] ?? $_POST['action'] ?? 'search';
$query = trim($_GET['q'] ?? $_POST['q'] ?? '');

try {
    switch ($action) {
        case 'suggest':
            if (mb_strlen($query) < 1) {
                ob_end_clean();
                echo json_encode(['success' => true, 'local' => [], 'online' => [], 'combined' => []]);
                exit;
            }

            $local = suggestLiteraryCanon($query, 6);
            $online = [];
            if (mb_strlen($query) >= 2) {
                $online = fetchMultiSourceSuggestions($query, 6);
            }

            $combined = [];
            foreach ($local as $item) {
                $combined[] = [
                    'type' => 'local',
                    'title' => $item['title'],
                    'subtitle' => $item['author'] . ($item['country'] ? ' · ' . $item['country'] : ''),
                    'snippet' => $item['excerpt'] ?? '',
                    'url' => (defined('URL_ROOT') ? URL_ROOT : '') . '/canon/view?id=' . ($item['id'] ?? 0),
                    'cover' => $item['cover_image'] ?? '',
                    'genre' => $item['genre_label'] ?? '',
                    'region' => $item['region'] ?? '',
                    'source' => 'canon',
                    'source_label' => 'Knowledge Base',
                ];
            }
            foreach ($online as $item) {
                $combined[] = [
                    'type' => 'online',
                    'title' => $item['title'],
                    'subtitle' => ($item['source_label'] ?? 'Web') . ($item['lang'] ? ' · ' . strtoupper($item['lang']) : ''),
                    'snippet' => $item['snippet'] ?? '',
                    'url' => $item['url'] ?? '',
                    'cover' => $item['cover'] ?? '',
                    'genre' => '',
                    'region' => 'online',
                    'source' => $item['source'] ?? 'web',
                    'source_label' => $item['source_label'] ?? 'Web',
                    'source_color' => $item['source_color'] ?? '#475569',
                    'source_icon' => $item['source_icon'] ?? 'bi-globe2',
                ];
            }

            ob_end_clean();
            echo json_encode([
                'success' => true,
                'local' => $local,
                'online' => $online,
                'combined' => array_slice($combined, 0, 10),
            ]);
            break;

        case 'search':
            if ($query === '') {
                ob_end_clean();
                echo json_encode(['success' => false, 'message' => 'Empty query']);
                exit;
            }

            $local = searchLiteraryCanon($query, 12);
            $online = fetchMultiSourceOnlineResults($query, 4, 16);

            ob_end_clean();
            echo json_encode([
                'success' => true,
                'query' => $query,
                'local' => $local,
                'online' => $online,
                'sources' => ['openlibrary', 'gutenberg', 'archive', 'wikidata', 'wikipedia'],
            ]);
            break;

        default:
            ob_end_clean();
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
    }
} catch (Exception $e) {
    ob_end_clean();
    error_log('literary_search.php: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Search error']);
}
