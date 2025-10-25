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
                          ORDER BY cm.`role` ASC, cm.created_at DESC;");
    $stmt->bindParam(':class_id', $classId, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getClassOfStudent($studentId)
{
    global $pdo;
    $stmt = $pdo->prepare("SELECT class_id FROM class_member WHERE user_id = :user_id");
    $stmt->bindParam(':user_id', $studentId, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_COLUMN);
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

// ============= GROUP MANAGEMENT FUNCTIONS =============

/**
 * Get all groups in a class
 */
function getClassGroups($classId = null)
{
    if ($classId === null) {
        $classId = getCurrentClassId();
    }
    global $pdo;
    $stmt = $pdo->prepare("SELECT g.group_id, g.group_name, 
                          COUNT(gm.user_id) as member_count
                          FROM `group` g
                          LEFT JOIN group_member gm ON g.group_id = gm.group_id
                          WHERE g.class_id = :class_id
                          GROUP BY g.group_id
                          ORDER BY g.group_name ASC");
    $stmt->bindParam(':class_id', $classId, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Get group details with members
 */
function getGroupDetails($groupId)
{
    global $pdo;

    // Get group info
    $stmt = $pdo->prepare("SELECT g.*, c.class_name 
                          FROM `group` g
                          LEFT JOIN `class` c ON g.class_id = c.class_id
                          WHERE g.group_id = :group_id");
    $stmt->bindParam(':group_id', $groupId, PDO::PARAM_INT);
    $stmt->execute();
    $group = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$group)
        return null;

    // Get members
    $stmt = $pdo->prepare("SELECT gm.user_id, u.fullname, u.username, u.email, gm.added_by,
                          u2.fullname as added_by_name
                          FROM group_member gm
                          LEFT JOIN users u ON gm.user_id = u.user_id
                          LEFT JOIN users u2 ON gm.added_by = u2.user_id
                          WHERE gm.group_id = :group_id
                          ORDER BY u.fullname ASC");
    $stmt->bindParam(':group_id', $groupId, PDO::PARAM_INT);
    $stmt->execute();
    $group['members'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

    return $group;
}

/**
 * Create a new group
 */
function createGroup($classId, $groupName)
{
    global $pdo;
    $stmt = $pdo->prepare('INSERT INTO `group` (class_id, group_name) VALUES (:class_id, :group_name)');
    $stmt->bindParam(':class_id', $classId, PDO::PARAM_INT);
    $stmt->bindParam(':group_name', $groupName, PDO::PARAM_STR);

    if ($stmt->execute()) {
        return $pdo->lastInsertId();
    }
    return false;
}

/**
 * Delete a group
 */
function deleteGroup($groupId)
{
    global $pdo;
    // Members will be deleted automatically via CASCADE
    $stmt = $pdo->prepare('DELETE FROM `group` WHERE group_id = :group_id');
    $stmt->bindParam(':group_id', $groupId, PDO::PARAM_INT);
    return $stmt->execute();
}

/**
 * Add members to a group
 */
function addGroupMembers($groupId, $userIds, $addedBy = null)
{
    global $pdo;
    $stmt = $pdo->prepare('INSERT IGNORE INTO group_member (group_id, user_id, added_by) VALUES (:group_id, :user_id, :added_by)');

    $success = 0;
    foreach ($userIds as $userId) {
        $userId = intval($userId);
        if ($userId <= 0)
            continue;

        $stmt->bindParam(':group_id', $groupId, PDO::PARAM_INT);
        $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
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
 * Remove member from group
 */
function removeGroupMember($groupId, $userId)
{
    global $pdo;
    $stmt = $pdo->prepare('DELETE FROM group_member WHERE group_id = :group_id AND user_id = :user_id');
    $stmt->bindParam(':group_id', $groupId, PDO::PARAM_INT);
    $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
    return $stmt->execute();
}

/**
 * Search users in class who are not in specific group
 */
function searchUsersNotInGroup($keyword, $classId, $groupId, $limit = 50)
{
    global $pdo;

    $keyword = '%' . $keyword . '%';
    $stmt = $pdo->prepare("SELECT u.user_id, u.fullname, u.username, u.email 
                          FROM users u
                          INNER JOIN class_member cm ON u.user_id = cm.user_id
                          WHERE cm.class_id = :class_id
                          AND (u.fullname LIKE :keyword OR u.username LIKE :keyword OR u.email LIKE :keyword)
                          AND u.user_id NOT IN (SELECT user_id FROM group_member WHERE group_id = :group_id)
                          ORDER BY u.fullname 
                          LIMIT :limit");
    $stmt->bindParam(':keyword', $keyword, PDO::PARAM_STR);
    $stmt->bindParam(':class_id', $classId, PDO::PARAM_INT);
    $stmt->bindParam(':group_id', $groupId, PDO::PARAM_INT);
    $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Update group name
 */
function updateGroupName($groupId, $groupName)
{
    global $pdo;
    $stmt = $pdo->prepare('UPDATE `group` SET group_name = :group_name WHERE group_id = :group_id');
    $stmt->bindParam(':group_name', $groupName, PDO::PARAM_STR);
    $stmt->bindParam(':group_id', $groupId, PDO::PARAM_INT);
    return $stmt->execute();
}

// ============= TOPIC MANAGEMENT FUNCTIONS =============

/**
 * Get all topics in a class
 */
function getClassTopics($classId = null)
{
    if ($classId === null) {
        $classId = getCurrentClassId();
    }
    global $pdo;
    $stmt = $pdo->prepare("SELECT t.topic_id, t.topic_name, t.class_id, t.added_by, 
                          t.created_at, t.updated_at,
                          u.fullname as added_by_name
                          FROM topic t
                          LEFT JOIN users u ON t.added_by = u.user_id
                          WHERE t.class_id = :class_id
                          ORDER BY t.created_at DESC");
    $stmt->bindParam(':class_id', $classId, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Get topic details
 */
function getTopicDetails($topicId)
{
    global $pdo;
    $stmt = $pdo->prepare("SELECT t.*, u.fullname as added_by_name,
                          c.class_name
                          FROM topic t
                          LEFT JOIN users u ON t.added_by = u.user_id
                          LEFT JOIN class c ON t.class_id = c.class_id
                          WHERE t.topic_id = :topic_id");
    $stmt->bindParam(':topic_id', $topicId, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

/**
 * Create a new topic
 */
function createTopic($classId, $topicName, $addedBy = null)
{
    global $pdo;
    $stmt = $pdo->prepare('INSERT INTO topic (class_id, topic_name, added_by, created_at, updated_at) 
                          VALUES (:class_id, :topic_name, :added_by, NOW(), NOW())');
    $stmt->bindParam(':class_id', $classId, PDO::PARAM_INT);
    $stmt->bindParam(':topic_name', $topicName, PDO::PARAM_STR);
    $stmt->bindParam(':added_by', $addedBy, PDO::PARAM_INT);

    if ($stmt->execute()) {
        return $pdo->lastInsertId();
    }
    return false;
}

/**
 * Update topic name
 */
function updateTopicName($topicId, $topicName)
{
    global $pdo;
    $stmt = $pdo->prepare('UPDATE topic SET topic_name = :topic_name, updated_at = NOW() 
                          WHERE topic_id = :topic_id');
    $stmt->bindParam(':topic_name', $topicName, PDO::PARAM_STR);
    $stmt->bindParam(':topic_id', $topicId, PDO::PARAM_INT);
    return $stmt->execute();
}

/**
 * Delete a topic
 */
function deleteTopic($topicId)
{
    global $pdo;
    $stmt = $pdo->prepare('DELETE FROM topic WHERE topic_id = :topic_id');
    $stmt->bindParam(':topic_id', $topicId, PDO::PARAM_INT);
    return $stmt->execute();
}

/**
 * Get groups in a class that include a specific user
 */
function getAllGroupsOfStudentInClasses($userId, $classId = null)
{
    if ($classId === null) {
        $classId = getCurrentClassId();
    }

    if (!$classId || !$userId) {
        return [];
    }

    global $pdo;
    $stmt = $pdo->prepare(
        "SELECT g.group_id, g.group_name, g.class_id
         FROM `group` g
         INNER JOIN group_member gm ON gm.group_id = g.group_id
         WHERE g.class_id = :class_id AND gm.user_id = :user_id
         ORDER BY g.group_name ASC"
    );
    $stmt->bindParam(':class_id', $classId, PDO::PARAM_INT);
    $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
    $stmt->execute();

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}