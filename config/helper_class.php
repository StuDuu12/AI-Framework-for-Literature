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
    $stmt = $pdo->prepare("SELECT * FROM `class` WHERE class_id = :classId");
    $stmt->bindParam(':classId', $classId);
    $stmt->execute();
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

/**
 * Get all members of a class
 */
function getClassMembers($classId = null)
{
    if ($classId === null) {
        $classId = getCurrentClassId();
    }
    global $pdo;
    $stmt = $pdo->prepare("SELECT cm.user_id, u.fullname, u.username, u.email, cm.role 
                          FROM `class_member` cm 
                          LEFT JOIN `users` u ON cm.user_id = u.user_id 
                          WHERE cm.class_id = :class_id
                          ORDER BY cm.role DESC, u.fullname ASC");
    $stmt->bindParam(':class_id', $classId, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Search users by keyword (fullname, username, email)
 */
function searchUsers($keyword, $classId = null, $limit = 50)
{
    global $pdo;
    if ($classId === null) {
        $classId = getCurrentClassId();
    }

    $keyword = '%' . $keyword . '%';
    $stmt = $pdo->prepare("SELECT user_id, fullname, username, email, role 
                          FROM users 
                          WHERE (fullname LIKE :keyword OR username LIKE :keyword OR email LIKE :keyword)
                          AND user_id NOT IN (SELECT user_id FROM class_member WHERE class_id = :class_id)
                          ORDER BY fullname 
                          LIMIT :limit");
    $stmt->bindParam(':keyword', $keyword, PDO::PARAM_STR);
    $stmt->bindParam(':class_id', $classId, PDO::PARAM_INT);
    $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Add multiple members to a class with specified roles
 */
function addClassMembers($classId, $userIds, $roles, $addedBy = null)
{
    global $pdo;
    $stmt = $pdo->prepare('INSERT IGNORE INTO class_member (class_id, user_id, role, added_by) VALUES (:class_id, :user_id, :role, :added_by)');

    $success = 0;
    foreach ($userIds as $index => $userId) {
        $userId = intval($userId);
        $role = isset($roles[$index]) ? $roles[$index] : 'student';

        if ($userId <= 0 || !in_array($role, ['student', 'teacher', 'admin'])) {
            continue;
        }

        $stmt->bindParam(':class_id', $classId, PDO::PARAM_INT);
        $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
        $stmt->bindParam(':role', $role, PDO::PARAM_STR);
        $stmt->bindParam(':added_by', $addedBy, PDO::PARAM_INT);

        try {
            $stmt->execute();
            $success++;
        } catch (Exception $e) {
            continue;
        }
    }

    return $success;
}

/**
 * Remove a member from a class
 */
function removeClassMember($classId, $userId)
{
    global $pdo;
    $stmt = $pdo->prepare('DELETE FROM class_member WHERE class_id = :class_id AND user_id = :user_id');
    $stmt->bindParam(':class_id', $classId, PDO::PARAM_INT);
    $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
    return $stmt->execute();
}

/**
 * Update member role in a class
 */
function updateMemberRole($classId, $userId, $role)
{
    if (!in_array($role, ['student', 'teacher', 'admin'])) {
        return false;
    }

    global $pdo;
    $stmt = $pdo->prepare('UPDATE class_member SET role = :role WHERE class_id = :class_id AND user_id = :user_id');
    $stmt->bindParam(':role', $role, PDO::PARAM_STR);
    $stmt->bindParam(':class_id', $classId, PDO::PARAM_INT);
    $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
    return $stmt->execute();
}

function getRoleInClass($classId, $userId)
{
    global $pdo;
    $stmt = $pdo->prepare('SELECT role FROM class_member WHERE class_id = :class_id AND user_id = :user_id');
    $stmt->bindParam(':class_id', $classId, PDO::PARAM_INT);
    $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchColumn();
}
