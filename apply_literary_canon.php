<?php
require_once __DIR__ . '/config/db.php';

$sqlFile = __DIR__ . '/database/literary_canon.sql';
if (!is_readable($sqlFile)) {
    fwrite(STDERR, "Cannot read {$sqlFile}\n");
    exit(1);
}

$sql = file_get_contents($sqlFile);
$pdo->exec($sql);

$count = (int) $pdo->query('SELECT COUNT(*) FROM literary_work')->fetchColumn();
echo "literary_work table ready: {$count} works\n";
