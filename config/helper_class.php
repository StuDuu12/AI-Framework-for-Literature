<?php 

function getCurrentClassId()
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    return isset($_SESSION['current_class_id']) ? $_SESSION['current_class_id'] : null;
}

function infoClass($classId = '')
{
    if ($classId === '') {
        $classId = getCurrentClassId();
    }
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM `class` WHERE class_id :classId");
    $stmt->bindParam(':classId', $classId);
    $stmt->execute();
    return $stmt->fetch(PDO::FETCH_ASSOC);
}