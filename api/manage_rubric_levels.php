<?php
session_start();
require_once __DIR__ . '/../config/env.php';
require_once __DIR__ . '/../config/db.php';

header('Content-Type: application/json');

$userId = $_SESSION['user']['user_id'] ?? $_SESSION['user_id'] ?? null;
if (!$userId) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$action = $_GET['action'] ?? $_POST['action'] ?? '';

// ---- Phân quyền theo đối tượng: rubric thuộc tiêu chí/nhóm tiêu chí của một lớp,
// chỉ giáo viên của lớp đó (hoặc admin) mới được đọc/sửa ----
require_once __DIR__ . '/../config/helper_criteria.php';

function rubricDeny(): void
{
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Không có quyền thao tác rubric của lớp này']);
    exit();
}

function rubricClassOfCriterion(PDO $pdo, int $tcId): int
{
    $stmt = $pdo->prepare("SELECT n.class_id FROM tieu_chi t INNER JOIN nhom_tc n ON t.nhom_tc_id = n.nhom_tc_id WHERE t.tc_id = :id");
    $stmt->execute([':id' => $tcId]);
    return (int)($stmt->fetchColumn() ?: 0);
}

function rubricClassOfGroup(PDO $pdo, int $groupId): int
{
    $stmt = $pdo->prepare("SELECT class_id FROM nhom_tc WHERE nhom_tc_id = :id");
    $stmt->execute([':id' => $groupId]);
    return (int)($stmt->fetchColumn() ?: 0);
}

function rubricRequireClass(int $classId, int $userId): void
{
    if ($classId <= 0 || !teacherCanAccessClassCriteria($classId, $userId)) {
        rubricDeny();
    }
}

$sessionRole = $_SESSION['user']['role'] ?? ($_SESSION['role'] ?? '');
if (!in_array($sessionRole, ['teacher', 'admin'], true)) {
    rubricDeny();
}

try {
    switch ($action) {
        case 'get_levels':
            $tcId = intval($_GET['tc_id'] ?? 0);
            if (!$tcId) {
                echo json_encode(['success' => false, 'message' => 'Missing tc_id']);
                exit();
            }
            rubricRequireClass(rubricClassOfCriterion($pdo, $tcId), (int)$userId);
            $stmt = $pdo->prepare("SELECT * FROM rubric_level WHERE tc_id = :tc_id ORDER BY score DESC, sort_order ASC");
            $stmt->execute([':tc_id' => $tcId]);
            echo json_encode(['success' => true, 'levels' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
            break;

        case 'save_levels':
            $input = json_decode(file_get_contents('php://input'), true);
            $tcId = intval($input['tc_id'] ?? 0);
            $levels = $input['levels'] ?? [];

            if (!$tcId) {
                echo json_encode(['success' => false, 'message' => 'Missing tc_id']);
                exit();
            }
            rubricRequireClass(rubricClassOfCriterion($pdo, $tcId), (int)$userId);
            if (!is_array($levels)) {
                $levels = [];
            }

            $pdo->beginTransaction();

            // Delete existing levels
            $del = $pdo->prepare("DELETE FROM rubric_level WHERE tc_id = :tc_id");
            $del->execute([':tc_id' => $tcId]);

            // Insert new levels
            $stmt = $pdo->prepare("INSERT INTO rubric_level (tc_id, level_name, score, description, sort_order) VALUES (:tc_id, :name, :score, :desc, :sort)");
            foreach ($levels as $i => $level) {
                $stmt->execute([
                    ':tc_id' => $tcId,
                    ':name' => trim($level['level_name'] ?? ''),
                    ':score' => intval($level['score'] ?? 0),
                    ':desc' => trim($level['description'] ?? ''),
                    ':sort' => $i,
                ]);
            }

            $pdo->commit();
            echo json_encode(['success' => true, 'message' => 'Đã lưu rubric levels']);
            break;

        case 'create_template':
            $input = json_decode(file_get_contents('php://input'), true);
            $name = trim($input['name'] ?? '');
            $description = trim($input['description'] ?? '');
            $classId = intval($input['class_id'] ?? 0);
            $groupId = intval($input['group_id'] ?? 0);

            if (!$name || !$groupId) {
                echo json_encode(['success' => false, 'message' => 'Thiếu thông tin']);
                exit();
            }
            // Template gắn vào nhóm tiêu chí của lớp mình; class_id lấy từ nhóm, không tin client
            $groupClassId = rubricClassOfGroup($pdo, $groupId);
            rubricRequireClass($groupClassId, (int)$userId);
            if ($classId) {
                $classId = $groupClassId;
            }

            $stmt = $pdo->prepare("INSERT INTO rubric_template (class_id, name, description, created_by) VALUES (:class_id, :name, :desc, :created_by)");
            $stmt->execute([
                ':class_id' => $classId ?: null,
                ':name' => $name,
                ':desc' => $description,
                ':created_by' => $userId,
            ]);
            $templateId = $pdo->lastInsertId();

            // Link template to group
            $upd = $pdo->prepare("UPDATE nhom_tc SET template_id = :tid WHERE nhom_tc_id = :gid");
            $upd->execute([':tid' => $templateId, ':gid' => $groupId]);

            echo json_encode(['success' => true, 'message' => 'Đã tạo rubric template', 'template_id' => $templateId]);
            break;

        case 'get_templates':
            $classId = intval($_GET['class_id'] ?? 0);
            if ($classId > 0) {
                rubricRequireClass($classId, (int)$userId);
            }
            $sql ="SELECT rt.*, 
                    (SELECT COUNT(*) FROM nhom_tc WHERE template_id = rt.template_id) as group_count
                    FROM rubric_template rt 
                    WHERE rt.class_id = :class_id OR rt.class_id IS NULL
                    ORDER BY rt.name ASC";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([':class_id' => $classId]);
            echo json_encode(['success' => true, 'templates' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
            break;

        case 'apply_template':
            $input = json_decode(file_get_contents('php://input'), true);
            $templateId = intval($input['template_id'] ?? 0);
            $targetGroupId = intval($input['target_group_id'] ?? 0);

            if (!$templateId || !$targetGroupId) {
                echo json_encode(['success' => false, 'message' => 'Thiếu thông tin']);
                exit();
            }

            // Nhóm đích phải thuộc lớp mình; template phải là template dùng chung (class_id NULL)
            // hoặc thuộc lớp mình đang dạy
            rubricRequireClass(rubricClassOfGroup($pdo, $targetGroupId), (int)$userId);
            $tplStmt = $pdo->prepare("SELECT class_id FROM rubric_template WHERE template_id = :tid");
            $tplStmt->execute([':tid' => $templateId]);
            $tplRow = $tplStmt->fetch(PDO::FETCH_ASSOC);
            if (!$tplRow) {
                echo json_encode(['success' => false, 'message' => 'Template không tồn tại']);
                exit();
            }
            if (!empty($tplRow['class_id'])) {
                rubricRequireClass((int)$tplRow['class_id'], (int)$userId);
            }

            // Get criteria from template group
            $stmt = $pdo->prepare("SELECT * FROM tieu_chi WHERE nhom_tc_id = (SELECT nhom_tc_id FROM nhom_tc WHERE template_id = :tid LIMIT 1)");
            $stmt->execute([':tid' => $templateId]);
            $templateCriteria = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $pdo->beginTransaction();
            $ins = $pdo->prepare("INSERT INTO tieu_chi (name_tc, nhom_tc_id, description, min_score, max_score) VALUES (:name, :gid, :desc, :min, :max)");
            $insLevel = $pdo->prepare("INSERT INTO rubric_level (tc_id, level_name, score, description, sort_order) VALUES (:tc_id, :name, :score, :desc, :sort)");

            foreach ($templateCriteria as $c) {
                $ins->execute([
                    ':name' => $c['name_tc'],
                    ':gid' => $targetGroupId,
                    ':desc' => $c['description'],
                    ':min' => $c['min_score'],
                    ':max' => $c['max_score'],
                ]);
                $newTcId = $pdo->lastInsertId();

                // Copy levels
                $lvlStmt = $pdo->prepare("SELECT * FROM rubric_level WHERE tc_id = :old_tc_id ORDER BY sort_order ASC");
                $lvlStmt->execute([':old_tc_id' => $c['tc_id']]);
                $levels = $lvlStmt->fetchAll(PDO::FETCH_ASSOC);
                foreach ($levels as $lvl) {
                    $insLevel->execute([
                        ':tc_id' => $newTcId,
                        ':name' => $lvl['level_name'],
                        ':score' => $lvl['score'],
                        ':desc' => $lvl['description'],
                        ':sort' => $lvl['sort_order'],
                    ]);
                }
            }
            $pdo->commit();

            echo json_encode(['success' => true, 'message' => 'Đã áp dụng rubric template']);
            break;

        default:
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
            break;
    }
} catch (PDOException $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    error_log("rubric_levels error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error']);
}
