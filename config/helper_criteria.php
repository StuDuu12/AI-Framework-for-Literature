<?php
require_once __DIR__ . '/db.php';

/**
 * =====================================================
 * CRITERIA GROUP (NHOM_TC) FUNCTIONS
 * =====================================================
 */

/**
 * Get all criteria groups for a specific class
 * @param int $class_id The class ID
 * @param int $user_id The teacher user ID (for permission check)
 * @return array Array of criteria groups
 */
function teacherCanAccessClassCriteria(int $class_id, int $user_id): bool
{
    global $pdo;

    if (!empty($_SESSION['user']['role']) && $_SESSION['user']['role'] === 'admin') {
        return true;
    }

    $check_stmt = $pdo->prepare("SELECT 1 FROM class_member WHERE class_id = :class_id AND user_id = :user_id AND role = 'teacher'");
    $check_stmt->bindParam(':class_id', $class_id, PDO::PARAM_INT);
    $check_stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    $check_stmt->execute();

    return $check_stmt->rowCount() > 0;
}

function getAllNhomTC($class_id, $user_id)
{
    global $pdo;

    if (!teacherCanAccessClassCriteria((int) $class_id, (int) $user_id)) {
        return ['success' => false, 'message' => 'Bạn không có quyền truy cập lớp này.'];
    }

    $stmt = $pdo->prepare("SELECT nhom_tc_id, class_id, ten_nhom_tc, description 
                          FROM nhom_tc 
                          WHERE class_id = :class_id 
                          ORDER BY ten_nhom_tc ASC");
    $stmt->bindParam(':class_id', $class_id, PDO::PARAM_INT);
    $stmt->execute();

    $groups = $stmt->fetchAll(PDO::FETCH_ASSOC);

    return ['success' => true, 'data' => $groups];
}

/**
 * Get a specific criteria group by ID
 * @param int $nhom_tc_id The group ID
 * @return array Group data or error
 */
function getNhomTCById($nhom_tc_id)
{
    global $pdo;

    $stmt = $pdo->prepare("SELECT nhom_tc_id, class_id, ten_nhom_tc, description 
                          FROM nhom_tc 
                          WHERE nhom_tc_id = :nhom_tc_id");
    $stmt->bindParam(':nhom_tc_id', $nhom_tc_id, PDO::PARAM_INT);
    $stmt->execute();

    $group = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$group) {
        return ['success' => false, 'message' => 'Không tìm thấy nhóm tiêu chí'];
    }

    return ['success' => true, 'data' => $group];
}

/**
 * Create a new criteria group
 * @param int $class_id The class ID
 * @param string $ten_nhom_tc Group name
 * @param string $description Group description (optional)
 * @param int $user_id The teacher user ID (for permission check)
 * @return array Result with new group ID or error
 */
function createNhomTC($class_id, $ten_nhom_tc, $description, $user_id)
{
    global $pdo;

    if (!teacherCanAccessClassCriteria((int) $class_id, (int) $user_id)) {
        return ['success' => false, 'message' => 'Bạn không có quyền tạo bộ tiêu chí cho lớp này.'];
    }

    // Validate input
    if (empty(trim($ten_nhom_tc))) {
        return ['success' => false, 'message' => 'Vui lòng nhập tên bộ tiêu chí.'];
    }

    $stmt = $pdo->prepare("INSERT INTO nhom_tc (class_id, ten_nhom_tc, description) VALUES (:class_id, :ten_nhom_tc, :description)");
    $stmt->bindParam(':class_id', $class_id, PDO::PARAM_INT);
    $stmt->bindParam(':ten_nhom_tc', $ten_nhom_tc, PDO::PARAM_STR);
    $stmt->bindParam(':description', $description, PDO::PARAM_STR);

    if ($stmt->execute()) {
        return ['success' => true, 'nhom_tc_id' => $pdo->lastInsertId(), 'message' => 'Đã tạo bộ tiêu chí.'];
    } else {
        return ['success' => false, 'message' => 'Không tạo được bộ tiêu chí.'];
    }
}

/**
 * Update an existing criteria group
 * @param int $nhom_tc_id The group ID
 * @param string $ten_nhom_tc New group name
 * @param string $description New description
 * @return array Success or error message
 */
function updateNhomTC($nhom_tc_id, $ten_nhom_tc, $description)
{
    global $pdo;

    // Validate input
    if (empty(trim($ten_nhom_tc))) {
        return ['success' => false, 'message' => 'Vui lòng nhập tên bộ tiêu chí.'];
    }

    $stmt = $pdo->prepare("UPDATE nhom_tc SET ten_nhom_tc = :ten_nhom_tc, description = :description WHERE nhom_tc_id = :nhom_tc_id");
    $stmt->bindParam(':ten_nhom_tc', $ten_nhom_tc, PDO::PARAM_STR);
    $stmt->bindParam(':description', $description, PDO::PARAM_STR);
    $stmt->bindParam(':nhom_tc_id', $nhom_tc_id, PDO::PARAM_INT);

    if ($stmt->execute()) {
        if ($stmt->rowCount() > 0) {
            return ['success' => true, 'message' => 'Đã cập nhật bộ tiêu chí.'];
        } else {
            return ['success' => false, 'message' => 'Không tìm thấy bộ tiêu chí hoặc không có gì thay đổi.'];
        }
    } else {
        return ['success' => false, 'message' => 'Không cập nhật được bộ tiêu chí.'];
    }
}

/**
 * Delete a criteria group (will also delete associated criteria)
 * @param int $nhom_tc_id The group ID
 * @return array Success or error message
 */
function deleteNhomTC($nhom_tc_id)
{
    global $pdo;

    // Check if group has associated criteria
    $check_stmt = $pdo->prepare("SELECT COUNT(*) as count FROM tieu_chi WHERE nhom_tc_id = :nhom_tc_id");
    $check_stmt->bindParam(':nhom_tc_id', $nhom_tc_id, PDO::PARAM_INT);
    $check_stmt->execute();
    $result = $check_stmt->fetch(PDO::FETCH_ASSOC);
    $count = $result['count'];

    if ($count > 0) {
        return ['success' => false, 'message' => "Cannot delete this group because it has $count linked criteria. Please delete the criteria first."];
    }

    $stmt = $pdo->prepare("DELETE FROM nhom_tc WHERE nhom_tc_id = :nhom_tc_id");
    $stmt->bindParam(':nhom_tc_id', $nhom_tc_id, PDO::PARAM_INT);

    if ($stmt->execute()) {
        if ($stmt->rowCount() > 0) {
            return ['success' => true, 'message' => 'Đã xoá bộ tiêu chí.'];
        } else {
return ['success' => false, 'message' => 'Không tìm thấy bộ tiêu chí.'];
        }
    } else {
        return ['success' => false, 'message' => 'Không xoá được bộ tiêu chí.'];
    }
}

/**
 * =====================================================
 * CRITERIA (TIEU_CHI) FUNCTIONS
 * =====================================================
 */

/**
 * Get all criteria for a specific group
 * @param int $nhom_tc_id The group ID
 * @return array Array of criteria
 */
function getTieuChiByNhomTC($nhom_tc_id)
{
    global $pdo;

    $stmt = $pdo->prepare("SELECT tc_id, name_tc, nhom_tc_id, description, min_score, max_score 
                          FROM tieu_chi 
                          WHERE nhom_tc_id = :nhom_tc_id 
                          ORDER BY name_tc ASC");
    $stmt->bindParam(':nhom_tc_id', $nhom_tc_id, PDO::PARAM_INT);
    $stmt->execute();

    $criteria = $stmt->fetchAll(PDO::FETCH_ASSOC);

    return ['success' => true, 'data' => $criteria];
}

/**
 * Get a specific criterion by ID
 * @param int $tc_id The criterion ID
 * @return array Criterion data or error
 */
function getTieuChiById($tc_id)
{
    global $pdo;

    $stmt = $pdo->prepare("SELECT tc_id, name_tc, nhom_tc_id, description, min_score, max_score 
                          FROM tieu_chi 
                          WHERE tc_id = :tc_id");
    $stmt->bindParam(':tc_id', $tc_id, PDO::PARAM_INT);
    $stmt->execute();

    $criterion = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$criterion) {
        return ['success' => false, 'message' => 'Không tìm thấy tiêu chí.'];
    }

    return ['success' => true, 'data' => $criterion];
}

/**
 * Create a new criterion
 * @param int $nhom_tc_id The group ID
 * @param string $name_tc Criterion name
 * @param string $description Description (optional)
 * @param int $min_score Minimum score
 * @param int $max_score Maximum score
 * @return array Result with new criterion ID or error
 */
function createTieuChi($nhom_tc_id, $name_tc, $description, $min_score, $max_score)
{
    global $pdo;

    // Validate input
    if (empty(trim($name_tc))) {
        return ['success' => false, 'message' => 'Vui lòng nhập tên tiêu chí.'];
    }

    // Validate scores
    if (!is_numeric($min_score) || !is_numeric($max_score)) {
        return ['success' => false, 'message' => 'Điểm thấp nhất và cao nhất phải là số.'];
    }

    if ($min_score < 0 || $max_score < 0) {
        return ['success' => false, 'message' => 'Điểm không được âm.'];
    }

    if ($min_score > $max_score) {
        return ['success' => false, 'message' => 'Điểm thấp nhất không được lớn hơn điểm cao nhất.'];
    }

    // Check if group exists
    $check_stmt = $pdo->prepare("SELECT 1 FROM nhom_tc WHERE nhom_tc_id = :nhom_tc_id");
    $check_stmt->bindParam(':nhom_tc_id', $nhom_tc_id, PDO::PARAM_INT);
    $check_stmt->execute();

    if ($check_stmt->rowCount() === 0) {
        return ['success' => false, 'message' => 'Bộ tiêu chí không tồn tại.'];
    }

    $stmt = $pdo->prepare("INSERT INTO tieu_chi (nhom_tc_id, name_tc, description, min_score, max_score) VALUES (:nhom_tc_id, :name_tc, :description, :min_score, :max_score)");
    $stmt->bindParam(':nhom_tc_id', $nhom_tc_id, PDO::PARAM_INT);
    $stmt->bindParam(':name_tc', $name_tc, PDO::PARAM_STR);
    $stmt->bindParam(':description', $description, PDO::PARAM_STR);
    $stmt->bindParam(':min_score', $min_score, PDO::PARAM_INT);
    $stmt->bindParam(':max_score', $max_score, PDO::PARAM_INT);

    if ($stmt->execute()) {
        return ['success' => true, 'tc_id' => $pdo->lastInsertId(), 'message' => 'Đã tạo tiêu chí.'];
    } else {
        return ['success' => false, 'message' => 'Không tạo được tiêu chí.'];
    }
}

/**
 * Update an existing criterion
 * @param int $tc_id The criterion ID
 * @param string $name_tc New criterion name
 * @param string $description New description
 * @param int $min_score New minimum score
 * @param int $max_score New maximum score
 * @return array Success or error message
 */
function updateTieuChi($tc_id, $name_tc, $description, $min_score, $max_score)
{
    global $pdo;

    // Validate input
    if (empty(trim($name_tc))) {
        return ['success' => false, 'message' => 'Vui lòng nhập tên tiêu chí.'];
    }

    // Validate scores
    if (!is_numeric($min_score) || !is_numeric($max_score)) {
        return ['success' => false, 'message' => 'Điểm thấp nhất và cao nhất phải là số.'];
    }

    if ($min_score < 0 || $max_score < 0) {
        return ['success' => false, 'message' => 'Điểm không được âm.'];
    }

    if ($min_score > $max_score) {
        return ['success' => false, 'message' => 'Điểm thấp nhất không được lớn hơn điểm cao nhất.'];
    }

    $stmt = $pdo->prepare("UPDATE tieu_chi SET name_tc = :name_tc, description = :description, min_score = :min_score, max_score = :max_score WHERE tc_id = :tc_id");
    $stmt->bindParam(':name_tc', $name_tc, PDO::PARAM_STR);
    $stmt->bindParam(':description', $description, PDO::PARAM_STR);
    $stmt->bindParam(':min_score', $min_score, PDO::PARAM_INT);
    $stmt->bindParam(':max_score', $max_score, PDO::PARAM_INT);
    $stmt->bindParam(':tc_id', $tc_id, PDO::PARAM_INT);

    if ($stmt->execute()) {
        if ($stmt->rowCount() > 0) {
            return ['success' => true, 'message' => 'Đã cập nhật tiêu chí.'];
        } else {
            return ['success' => false, 'message' => 'Không tìm thấy tiêu chí hoặc không có gì thay đổi.'];
        }
    } else {
        return ['success' => false, 'message' => 'Không cập nhật được tiêu chí.'];
    }
}

/**
 * Delete a criterion
 * @param int $tc_id The criterion ID
 * @return array Success or error message
 */
function deleteTieuChi($tc_id)
{
    global $pdo;

    // Check if criterion is being used in evaluations
    $check_stmt = $pdo->prepare("SELECT COUNT(*) as count FROM detail_evaluate WHERE tc_id = :tc_id");
    $check_stmt->bindParam(':tc_id', $tc_id, PDO::PARAM_INT);
    $check_stmt->execute();
    $result = $check_stmt->fetch(PDO::FETCH_ASSOC);
    $count = $result['count'];

    if ($count > 0) {
        return ['success' => false, 'message' => "Cannot delete this criterion because it has been used in $count evaluations"];
    }

    $stmt = $pdo->prepare("DELETE FROM tieu_chi WHERE tc_id = :tc_id");
    $stmt->bindParam(':tc_id', $tc_id, PDO::PARAM_INT);

    if ($stmt->execute()) {
        if ($stmt->rowCount() > 0) {
            return ['success' => true, 'message' => 'Đã xoá tiêu chí.'];
        } else {
return ['success' => false, 'message' => 'Không tìm thấy tiêu chí.'];
        }
    } else {
        return ['success' => false, 'message' => 'Không xoá được tiêu chí.'];
    }
}

/**
 * Get all criteria groups with their criteria count
 * @param int $class_id The class ID
 * @param int $user_id The teacher user ID
 * @return array Array of groups with criteria count
 */
function getNhomTCWithCount($class_id, $user_id)
{
    global $pdo;

    if (!teacherCanAccessClassCriteria((int) $class_id, (int) $user_id)) {
        return ['success' => false, 'message' => 'Bạn không có quyền truy cập lớp này.'];
    }

    $stmt = $pdo->prepare("SELECT 
                n.nhom_tc_id, 
                n.class_id, 
                n.ten_nhom_tc, 
                n.description,
                COUNT(t.tc_id) as criteria_count
            FROM nhom_tc n
            LEFT JOIN tieu_chi t ON n.nhom_tc_id = t.nhom_tc_id
            WHERE n.class_id = :class_id
            GROUP BY n.nhom_tc_id, n.class_id, n.ten_nhom_tc, n.description
            ORDER BY n.ten_nhom_tc ASC");
    $stmt->bindParam(':class_id', $class_id, PDO::PARAM_INT);
    $stmt->execute();

    $groups = $stmt->fetchAll(PDO::FETCH_ASSOC);

    return ['success' => true, 'data' => $groups];
}
