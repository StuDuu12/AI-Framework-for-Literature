<?php
/**
 * Insert 100 expansion works into literary_work.
 * Run: php apply_literary_expansion.php
 */
require_once __DIR__ . '/../../config/db.php';

$works = require __DIR__ . '/../../database/literary_expansion_works.php';
if (!is_array($works) || count($works) === 0) {
    fwrite(STDERR, "No expansion works found.\n");
    exit(1);
}

$check = $pdo->prepare('SELECT id FROM literary_work WHERE title = :title LIMIT 1');
$ins = $pdo->prepare("
    INSERT INTO literary_work
    (title, author, region, country, genre, genre_label, era, language, excerpt, description, keywords, wikipedia_url, featured, sort_order)
    VALUES
    (:title, :author, :region, :country, :genre, :genre_label, :era, :language, :excerpt, :description, :keywords, :wikipedia_url, :featured, :sort_order)
");

$added = 0;
$skipped = 0;

foreach ($works as $w) {
    $check->execute([':title' => $w['title']]);
    if ($check->fetchColumn()) {
        $skipped++;
        continue;
    }
    $ins->execute([
        ':title' => $w['title'],
        ':author' => $w['author'],
        ':region' => $w['region'],
        ':country' => $w['country'] ?? '',
        ':genre' => $w['genre'],
        ':genre_label' => $w['genre_label'] ?? '',
        ':era' => $w['era'] ?? '',
        ':language' => $w['language'] ?? 'Vietnamese',
        ':excerpt' => $w['excerpt'] ?? '',
        ':description' => $w['description'] ?? '',
        ':keywords' => $w['keywords'] ?? '',
        ':wikipedia_url' => $w['wikipedia_url'] ?? '',
        ':featured' => !empty($w['featured']) ? 1 : 0,
        ':sort_order' => (int) ($w['sort_order'] ?? 0),
    ]);
    $added++;
}

// Link author_id where author name matches
$pdo->exec("
    UPDATE literary_work lw
    INNER JOIN literary_author la ON la.name = lw.author
    SET lw.author_id = la.id
    WHERE lw.author_id IS NULL
");

// Auto-create missing authors
$missing = $pdo->query("
    SELECT DISTINCT author, region, country FROM literary_work
    WHERE author_id IS NULL AND author != ''
")->fetchAll(PDO::FETCH_ASSOC);

$insAuthor = $pdo->prepare("
    INSERT IGNORE INTO literary_author (name, slug, region, country, biography)
    VALUES (:name, :slug, :region, :country, :bio)
");
foreach ($missing as $row) {
    $slug = preg_replace('/[^a-z0-9]+/', '-', strtolower(@iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $row['author']) ?: $row['author']));
    $slug = trim($slug, '-') ?: 'author';
    $insAuthor->execute([
        ':name' => $row['author'],
        ':slug' => $slug,
        ':region' => $row['region'] === 'international' ? 'international' : 'vietnam',
        ':country' => $row['country'] ?? '',
        ':bio' => 'TÃ¡c giáº£ / Author trong kho tri thá»©c vÄƒn há»c LiteratureVer2.',
    ]);
}
$pdo->exec("
    UPDATE literary_work lw INNER JOIN literary_author la ON la.name = lw.author
    SET lw.author_id = la.id WHERE lw.author_id IS NULL
");

$total = (int) $pdo->query('SELECT COUNT(*) FROM literary_work')->fetchColumn();
echo "Expansion: {$added} added, {$skipped} skipped (already exist).\n";
echo "Total literary_work: {$total}\n";

