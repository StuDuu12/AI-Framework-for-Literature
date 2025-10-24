<?php 
define('DB_HOST', 'nvme.h2cloud.vn');
define('DB_USER', 'diemdanh_literature');
define('DB_PASS', 'literature@mon');
define('DB_NAME', 'diemdanh_literature');

$pdo = new PDO("mysql:host=".DB_HOST.";dbname=".DB_NAME, DB_USER, DB_PASS);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$pdo->exec("SET NAMES 'utf8'");
