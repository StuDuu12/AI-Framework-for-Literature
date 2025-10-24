<?php 
$classid = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($classid <= 0) {
    header("Location: /404");
    exit;
}
$_SESSION['current_class_id'] = $classid;
global $pdo;
$get_name_class = $pdo->prepare("SELECT * FROM `class` WHERE class_id = :classid");
$get_name_class->bindParam(':classid', $classid);
$get_name_class->execute();
$classInfo = $get_name_class->fetch(PDO::FETCH_ASSOC);
$_SESSION['current_class_name'] = $classInfo['class_name'];

header("Location: /class");
exit;