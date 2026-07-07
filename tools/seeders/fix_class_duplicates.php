<?php
if (PHP_SAPI !== 'cli') { http_response_code(403); die('CLI only'); }
require_once __DIR__ . '/../../config/db.php';

echo "Creating backup table 'class_backup'...\n";
try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS class_backup AS SELECT * FROM `class`");
    echo "Backup created (class_backup).\n";
} catch (PDOException $e) {
    echo "Backup failed: " . $e->getMessage() . "\n";
    exit(1);
}

// Find duplicates
$stmt = $pdo->query("SELECT class_id, COUNT(*) AS cnt FROM `class` GROUP BY class_id HAVING cnt > 1");
$dups = $stmt->fetchAll(PDO::FETCH_ASSOC);
if (empty($dups)) {
    echo "No duplicate class_id found.\n";
    exit(0);
}

echo "Found duplicate class_id values:\n";
foreach ($dups as $d) {
    echo " - class_id={$d['class_id']} (count={$d['cnt']})\n";
}

// Show rows for each duplicate
foreach ($dups as $d) {
    $id = (int)$d['class_id'];
    echo "\nRows for class_id=$id:\n";
    $rows = $pdo->prepare("SELECT * FROM `class` WHERE class_id = :id ORDER BY created_at ASC, updated_at ASC");
    $rows->execute(['id' => $id]);
    $all = $rows->fetchAll(PDO::FETCH_ASSOC);
    foreach ($all as $r) {
        echo json_encode($r) . "\n";
    }
}

echo "\nTo proceed with automatic deduplication, run this script with 'apply' argument:\n";
echo "php fix_class_duplicates.php apply\n";

