<?php
/**
 * TEMPORARY one-shot import script — DELETE AFTER USE.
 * Imports database/diemdanh_literature_v2.sql into the running database.
 * Requires: logged-in teacher/admin + ?confirm=REWRITE-EN
 */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: text/plain; charset=utf-8');

$role = $_SESSION['user']['role'] ?? ($_SESSION['role'] ?? '');
if (!in_array($role, ['teacher', 'admin'], true)) {
    http_response_code(403);
    die('Forbidden: login as teacher/admin first.');
}

if (($_GET['confirm'] ?? '') !== 'REWRITE-EN') {
    die("Add ?confirm=REWRITE-EN to run the import. THIS WILL WIPE ALL DATA.");
}

// Load DB credentials
if (!defined('DB_NAME') && file_exists(__DIR__ . '/../config/env.local.php')) {
    require_once __DIR__ . '/../config/env.local.php';
}
if (!defined('DB_HOST')) define('DB_HOST', 'localhost');
if (!defined('DB_USER')) define('DB_USER', 'root');
if (!defined('DB_PASS')) define('DB_PASS', '');
if (!defined('DB_NAME')) define('DB_NAME', 'diemdanh_literature');

$sqlFile = __DIR__ . '/../database/diemdanh_literature_v2.sql';
if (!is_file($sqlFile)) {
    die("SQL file not found: $sqlFile");
}
$sql = file_get_contents($sqlFile);

$mysqli = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($mysqli->connect_error) {
    die('Connect failed: ' . $mysqli->connect_error);
}
$mysqli->set_charset('utf8mb4');

$ok = 0;
$errors = [];
if ($mysqli->multi_query($sql)) {
    do {
        if ($result = $mysqli->store_result()) {
            $result->free();
        }
        $ok++;
        if ($mysqli->errno) {
            $errors[] = "[stmt $ok] " . $mysqli->error;
        }
    } while ($mysqli->more_results() && $mysqli->next_result());
}
if ($mysqli->errno) {
    $errors[] = "[final] ({$mysqli->errno}) " . $mysqli->error;
}

echo "Statements executed: $ok\n";
if ($errors) {
    echo "ERRORS:\n" . implode("\n", $errors) . "\n";
} else {
    echo "Import completed without errors.\n";
}

// Quick sanity check
$check = $mysqli->query("SELECT (SELECT COUNT(*) FROM users) AS users,
    (SELECT COUNT(*) FROM class) AS classes,
    (SELECT COUNT(*) FROM topic) AS topics,
    (SELECT COUNT(*) FROM phase) AS phases,
    (SELECT COUNT(*) FROM task) AS tasks,
    (SELECT COUNT(*) FROM response) AS responses,
    (SELECT COUNT(*) FROM content) AS contents,
    (SELECT COUNT(*) FROM comment) AS comments");
if ($check) {
    print_r($check->fetch_assoc());
}
$mysqli->close();
