<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/helper_phase.php';
require_once __DIR__ . '/../config/helper_task.php';
require_once __DIR__ . '/../config/helper_class.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Start output buffering to prevent any output before JSON
ob_start();

header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user'])) {
    ob_end_clean();
    echo json_encode(['success' => false, 'message' => 'Vui lòng đăng nhập.']);
    exit;
}

$action = $_POST['action'] ?? '';
// Ngoại lệ duy nhất cho GET: kiểm tra trùng giao bài (chỉ đọc). UI gọi bằng GET nên
// trước đây luôn rơi vào "Invalid action". Các action ghi dữ liệu vẫn bắt buộc POST
// (POST mới được kiểm tra CSRF).
if ($action === '' && ($_GET['action'] ?? '') === 'check_assignment_conflict') {
    $action = 'check_assignment_conflict';
}
$userId = $_SESSION['user']['user_id'];

/**
 * Kiểm tra file nội dung task tải lên. Không tin Content-Type do trình duyệt gửi:
 * bắt buộc đuôi .txt, MIME thật (finfo) là văn bản, tối đa 2MB.
 * Trả về thông báo lỗi, hoặc null nếu hợp lệ.
 */
function validateTaskUpload(array $file): ?string
{
    if (strtolower(pathinfo($file['name'] ?? '', PATHINFO_EXTENSION)) !== 'txt') {
        return 'Only .txt files are accepted';
    }
    if (($file['size'] ?? 0) <= 0 || $file['size'] > 2 * 1024 * 1024) {
        return 'File quá lớn hoặc rỗng (tối đa 2MB)';
    }
    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
    if (!in_array($mime, ['text/plain', 'text/x-c', 'text/x-c++', 'application/octet-stream', 'inode/x-empty'], true)
        && strpos((string)$mime, 'text/') !== 0) {
        return 'Only .txt files are accepted';
    }
    return null;
}

/** Tên file lưu trên server: id ngẫu nhiên + phần tên gốc đã làm sạch, luôn đuôi .txt */
function safeTaskFileName(string $originalName): string
{
    $stem = pathinfo($originalName, PATHINFO_FILENAME);
    $stem = preg_replace('/[^A-Za-z0-9_-]+/', '', $stem);
    $stem = substr((string)$stem, 0, 40);
    return uniqid('task_') . ($stem !== '' ? '_' . $stem : '') . '.txt';
}

/** Trả 403 JSON và dừng — dùng khi đối tượng không thuộc lớp của giáo viên */
function denyTaskAccess(string $message = 'Bạn không có quyền thực hiện thao tác này.'): void
{
    ob_end_clean();
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => $message]);
    exit;
}

try {
    switch ($action) {
        case 'create_task':
            $phaseId = intval($_POST['phase_id'] ?? 0);
            $topicId = intval($_POST['topic_id'] ?? 0);
            $title = trim($_POST['title'] ?? '');
            $description = trim($_POST['description'] ?? '');
            $contentType = $_POST['content_type'] ?? 'text'; // 'text' or 'file'
            $contentText = trim($_POST['content_text'] ?? '');
            $sourceTaskId = intval($_POST['source_task_id'] ?? 0); // For evaluation phases

            // Validate required fields
            if (!$phaseId || !$topicId || empty($title)) {
                ob_end_clean();
                echo json_encode(['success' => false, 'message' => 'Vui lòng điền đủ các mục bắt buộc.']);
                exit;
            }

            // Check if user is teacher
            if (!isTeacherInTopicClass($topicId, $userId)) {
                ob_end_clean();
                echo json_encode(['success' => false, 'message' => 'Bạn không có quyền thực hiện thao tác này.']);
                exit;
            }

            // Phase phải thuộc đúng topic mà giáo viên quản lý
            if (getTopicIdOfPhase($phaseId) !== $topicId) {
                denyTaskAccess('Giai đoạn không thuộc chủ đề này.');
            }

            // Get phase details to check if it's evaluation phase
            $phase = getPhaseDetails($phaseId);
            if (!$phase) {
                ob_end_clean();
                echo json_encode(['success' => false, 'message' => 'Không tìm thấy giai đoạn.']);
                exit;
            }

            $isEvaluationPhase = isEvaluationPhase($phase['phase_code']);

            $fileContent = null;
            $contentCreator = null;
            $reviewTask = null;

            // Handle evaluation phase
            if ($isEvaluationPhase) {
                if (!$sourceTaskId) {
                    ob_end_clean();
                    echo json_encode(['success' => false, 'message' => 'Hãy chọn nhiệm vụ cần đánh giá.']);
                    exit;
                }

                // Validate source task exists
                $sourceTask = getTaskDetails($sourceTaskId);
                if (!$sourceTask) {
                    ob_end_clean();
                    echo json_encode(['success' => false, 'message' => 'Nhiệm vụ nguồn không tồn tại.']);
                    exit;
                }

                // Bài nguồn phải thuộc lớp của giáo viên
                if (!isTeacherOfTask($sourceTaskId, $userId)) {
                    denyTaskAccess('Nhiệm vụ nguồn không thuộc lớp của bạn.');
                }

                // Validate source task is from correct phase (PHASE_START or PHASE_BTVN)
                $sourcePhase = getPhaseDetails($sourceTask['phase_id']);
                if (!$sourcePhase || !in_array($sourcePhase['phase_code'], ['PHASE_START', 'PHASE_BTVN'])) {
                    ob_end_clean();
                    echo json_encode(['success' => false, 'message' => 'Nhiệm vụ nguồn phải thuộc giai đoạn Phân tích cùng AI hoặc Bài tập về nhà.']);
                    exit;
                }

                $reviewTask = $sourceTaskId;
            } else {
                // Handle regular phase with content
                // Handle file upload
                if ($contentType === 'file' && isset($_FILES['content_file']) && $_FILES['content_file']['error'] === UPLOAD_ERR_OK) {
                    $file = $_FILES['content_file'];

                    $uploadError = validateTaskUpload($file);
                    if ($uploadError !== null) {
                        ob_end_clean();
                        echo json_encode(['success' => false, 'message' => $uploadError]);
                        exit;
                    }

                    // Create upload directory if not exists
                    $uploadDir = '../uploads/tasks/';
                    if (!is_dir($uploadDir)) {
                        mkdir($uploadDir, 0755, true);
                    }

                    $fileName = safeTaskFileName($file['name']);
                    $uploadPath = $uploadDir . $fileName;

                    if (move_uploaded_file($file['tmp_name'], $uploadPath)) {
                        $fileContent = 'tasks/' . $fileName;
                    } else {
                        ob_end_clean();
                        echo json_encode(['success' => false, 'message' => 'Không tải được tệp lên.']);
                        exit;
                    }
                } elseif ($contentType === 'text' && !empty($contentText)) {
                    // Save text content to file
                    $uploadDir = '../uploads/tasks/';
                    if (!is_dir($uploadDir)) {
                        mkdir($uploadDir, 0755, true);
                    }

                    $fileName = uniqid('task_') . '.txt';
                    $uploadPath = $uploadDir . $fileName;

                    if (file_put_contents($uploadPath, $contentText) !== false) {
                        $fileContent = 'tasks/' . $fileName;
                        $contentCreator = $userId;
                    } else {
                        ob_end_clean();
                        echo json_encode(['success' => false, 'message' => 'Không lưu được nội dung.']);
                        exit;
                    }
                }
            }

            $taskData = [
                'phase_id' => $phaseId,
                'title' => $title,
                'description' => $description,
                'file_content' => $fileContent,
                'content_creator' => $contentCreator,
                'review_task' => $reviewTask,
                'added_by' => $userId,
                'deadline' => !empty($_POST['deadline']) ? $_POST['deadline'] : null
            ];

            $taskId = createTask($taskData);

            if ($taskId) {
                ob_end_clean();
                echo json_encode(['success' => true, 'message' => 'Đã tạo nhiệm vụ.', 'task_id' => $taskId]);
            } else {
                ob_end_clean();
                echo json_encode(['success' => false, 'message' => 'Không tạo được nhiệm vụ.']);
            }
            break;

        case 'update_task':
            $taskId = intval($_POST['task_id'] ?? 0);
            $topicId = intval($_POST['topic_id'] ?? 0);
            $title = trim($_POST['title'] ?? '');
            $description = trim($_POST['description'] ?? '');
            $contentType = $_POST['content_type'] ?? 'text';
            $contentText = trim($_POST['content_text'] ?? '');
            $keepOldFile = $_POST['keep_old_file'] ?? 'yes';
            $sourceTaskId = intval($_POST['source_task_id'] ?? 0); // For evaluation phases

            if (!$taskId || !$topicId || empty($title)) {
                ob_end_clean();
                echo json_encode(['success' => false, 'message' => 'Vui lòng điền đủ các mục bắt buộc.']);
                exit;
            }

            // Check if user is teacher
            if (!isTeacherInTopicClass($topicId, $userId)) {
                ob_end_clean();
                echo json_encode(['success' => false, 'message' => 'Bạn không có quyền thực hiện thao tác này.']);
                exit;
            }

            // Task phải thuộc đúng topic đã kiểm tra quyền
            if (!taskBelongsToTopic($taskId, $topicId)) {
                denyTaskAccess();
            }

            // Get current task
            $currentTask = getTaskDetails($taskId);
            if (!$currentTask) {
                ob_end_clean();
                echo json_encode(['success' => false, 'message' => 'Không tìm thấy nhiệm vụ.']);
                exit;
            }

            // Get phase details to check if it's evaluation phase
            $phase = getPhaseDetails($currentTask['phase_id']);
            if (!$phase) {
                ob_end_clean();
                echo json_encode(['success' => false, 'message' => 'Không tìm thấy giai đoạn.']);
                exit;
            }

            $isEvaluationPhase = isEvaluationPhase($phase['phase_code']);

            $fileContent = $currentTask['file_content'];
            $contentCreator = $currentTask['content_creator'];
            $reviewTask = $currentTask['review_task'];

            // Handle evaluation phase
            if ($isEvaluationPhase) {
                if (!$sourceTaskId) {
                    ob_end_clean();
                    echo json_encode(['success' => false, 'message' => 'Hãy chọn nhiệm vụ cần đánh giá.']);
                    exit;
                }

                // Validate source task exists
                $sourceTask = getTaskDetails($sourceTaskId);
                if (!$sourceTask) {
                    ob_end_clean();
                    echo json_encode(['success' => false, 'message' => 'Nhiệm vụ nguồn không tồn tại.']);
                    exit;
                }

                // Bài nguồn phải thuộc lớp của giáo viên
                if (!isTeacherOfTask($sourceTaskId, $userId)) {
                    denyTaskAccess('Nhiệm vụ nguồn không thuộc lớp của bạn.');
                }

                // Validate source task is from correct phase
                $sourcePhase = getPhaseDetails($sourceTask['phase_id']);
                if (!$sourcePhase || !in_array($sourcePhase['phase_code'], ['PHASE_START', 'PHASE_BTVN'])) {
                    ob_end_clean();
                    echo json_encode(['success' => false, 'message' => 'Nhiệm vụ nguồn phải thuộc giai đoạn Phân tích cùng AI hoặc Bài tập về nhà.']);
                    exit;
                }

                $reviewTask = $sourceTaskId;
            } else {
                // Handle regular phase with content
                // Handle new file upload or text content
                if ($keepOldFile === 'no') {
                    if ($contentType === 'file' && isset($_FILES['content_file']) && $_FILES['content_file']['error'] === UPLOAD_ERR_OK) {
                        $file = $_FILES['content_file'];

                        $uploadError = validateTaskUpload($file);
                        if ($uploadError !== null) {
                            ob_end_clean();
                            echo json_encode(['success' => false, 'message' => $uploadError]);
                            exit;
                        }

                        $uploadDir = '../uploads/tasks/';
                        if (!is_dir($uploadDir)) {
                            mkdir($uploadDir, 0755, true);
                        }

                        // Delete old file if exists
                        if ($currentTask['file_content']) {
                            $oldFilePath = '../uploads/' . $currentTask['file_content'];
                            if (file_exists($oldFilePath)) {
                                unlink($oldFilePath);
                            }
                        }

                        $fileName = safeTaskFileName($file['name']);
                        $uploadPath = $uploadDir . $fileName;

                        if (move_uploaded_file($file['tmp_name'], $uploadPath)) {
                            $fileContent = 'tasks/' . $fileName;
                            $contentCreator = null;
                        }
                    } elseif ($contentType === 'text' && !empty($contentText)) {
                        $uploadDir = '../uploads/tasks/';
                        if (!is_dir($uploadDir)) {
                            mkdir($uploadDir, 0755, true);
                        }

                        // Delete old file if exists
                        if ($currentTask['file_content']) {
                            $oldFilePath = '../uploads/' . $currentTask['file_content'];
                            if (file_exists($oldFilePath)) {
                                unlink($oldFilePath);
                            }
                        }

                        $fileName = uniqid('task_') . '.txt';
                        $uploadPath = $uploadDir . $fileName;

                        if (file_put_contents($uploadPath, $contentText) !== false) {
                            $fileContent = 'tasks/' . $fileName;
                            $contentCreator = $userId;
                        }
                    }
                }
            }

            $taskData = [
                'title' => $title,
                'description' => $description,
                'file_content' => $fileContent,
                'content_creator' => $contentCreator,
                'review_task' => $reviewTask,
                'deadline' => !empty($_POST['deadline']) ? $_POST['deadline'] : null
            ];

            if (updateTask($taskId, $taskData)) {
                ob_end_clean();
                echo json_encode(['success' => true, 'message' => 'Đã cập nhật nhiệm vụ.']);
            } else {
                ob_end_clean();
                echo json_encode(['success' => false, 'message' => 'Không cập nhật được nhiệm vụ.']);
            }
            break;

        case 'delete_task':
            $taskId = intval($_POST['task_id'] ?? 0);
            $topicId = intval($_POST['topic_id'] ?? 0);

            if (!$taskId || !$topicId) {
                ob_end_clean();
                echo json_encode(['success' => false, 'message' => 'Thông tin không hợp lệ.']);
                exit;
            }

            // Check if user is teacher
            if (!isTeacherInTopicClass($topicId, $userId)) {
                ob_end_clean();
                echo json_encode(['success' => false, 'message' => 'Bạn không có quyền thực hiện thao tác này.']);
                exit;
            }

            if (!taskBelongsToTopic($taskId, $topicId)) {
                denyTaskAccess();
            }

            // Get task to delete file
            $task = getTaskDetails($taskId);
            if ($task && $task['file_content']) {
                $filePath = '../uploads/' . $task['file_content'];
                if (file_exists($filePath)) {
                    unlink($filePath);
                }
            }

            if (deleteTask($taskId)) {
                ob_end_clean();
                echo json_encode(['success' => true, 'message' => 'Đã xoá nhiệm vụ.']);
            } else {
                ob_end_clean();
                echo json_encode(['success' => false, 'message' => 'Không xoá được nhiệm vụ.']);
            }
            break;

        case 'get_task':
            $taskId = intval($_POST['task_id'] ?? 0);

            if (!$taskId) {
                ob_end_clean();
                echo json_encode(['success' => false, 'message' => 'Thông tin không hợp lệ.']);
                exit;
            }

            if (!isTeacherOfTask($taskId, $userId)) {
                denyTaskAccess();
            }

            $task = getTaskDetails($taskId);

            if ($task) {
                // Read file content if exists
                if ($task['file_content']) {
                    $fileContentText = readTaskFileContent($task['file_content']);
                    $task['file_content_text'] = $fileContentText;
                }

                ob_end_clean();
                echo json_encode(['success' => true, 'task' => $task]);
            } else {
                ob_end_clean();
                echo json_encode(['success' => false, 'message' => 'Không tìm thấy nhiệm vụ.']);
            }
            break;

        case 'assign_task':
            $taskId = intval($_POST['task_id'] ?? 0);
            $topicId = intval($_POST['topic_id'] ?? 0);
            $assignType = intval($_POST['assign_type'] ?? 0);
            $assignValues = $_POST['assign_values'] ?? [];

            if (!$taskId || !$topicId || !$assignType) {
                ob_end_clean();
                echo json_encode(['success' => false, 'message' => 'Thông tin không hợp lệ.']);
                exit;
            }

            // Check if user is teacher
            if (!isTeacherInTopicClass($topicId, $userId)) {
                ob_end_clean();
                echo json_encode(['success' => false, 'message' => 'Bạn không có quyền thực hiện thao tác này.']);
                exit;
            }

            if (!taskBelongsToTopic($taskId, $topicId)) {
                denyTaskAccess();
            }

            // Validate assign values
            if (empty($assignValues)) {
                ob_end_clean();
                echo json_encode(['success' => false, 'message' => 'Hãy chọn người hoặc nhóm để giao nhiệm vụ.']);
                exit;
            }

            // Convert assign_type to string type for deliver table
            $typeMap = [
                1 => 'class',
                2 => 'group',
                3 => 'user'
            ];
            if (!isset($typeMap[$assignType]) || !is_array($assignValues)) {
                ob_end_clean();
                echo json_encode(['success' => false, 'message' => 'Kiểu giao bài không hợp lệ.']);
                exit;
            }
            $deliverType = $typeMap[$assignType];

            // Đối tượng được giao (lớp / nhóm / học sinh) phải thuộc đúng lớp chứa task
            $taskClassId = getClassIdOfTopic($topicId);
            $assignValues = array_values(array_unique(array_map('intval', $assignValues)));
            foreach ($assignValues as $value) {
                if ($deliverType === 'class') {
                    $belongs = ($value === $taskClassId);
                } elseif ($deliverType === 'group') {
                    $belongs = (getClassIdOfGroup($value) === $taskClassId);
                } else {
                    $memberStmt = $pdo->prepare("SELECT 1 FROM class_member WHERE class_id = :cid AND user_id = :uid");
                    $memberStmt->execute([':cid' => $taskClassId, ':uid' => $value]);
                    $belongs = (bool)$memberStmt->fetchColumn();
                }
                if ($value <= 0 || !$belongs) {
                    denyTaskAccess('Người hoặc nhóm được giao không thuộc lớp này.');
                }
            }

            // Check for conflicts for each value
            $conflicts = [];
            foreach ($assignValues as $value) {
                $conflictCheck = checkAssignmentConflict($taskId, $assignType, $value);
                if ($conflictCheck['has_conflict']) {
                    $conflicts[] = [
                        'value' => $value,
                        'message' => $conflictCheck['message'],
                        'type' => $conflictCheck['conflict_type']
                    ];
                }
            }

            // If there are conflicts, return error with details
            if (!empty($conflicts)) {
                ob_end_clean();
                echo json_encode([
                    'success' => false,
                    'message' => $conflicts[0]['message'], // Show first conflict message
                    'conflicts' => $conflicts
                ]);
                exit;
            }

            // All checks passed, proceed with assignment
            if (assignTask($taskId, $assignType, $assignValues, $userId)) {
                require_once __DIR__ . '/../config/helper_notifications.php';
                notifyTaskAssignment($pdo, $taskId, $assignType, $assignValues, $userId);
                ob_end_clean();
                echo json_encode(['success' => true, 'message' => 'Đã giao nhiệm vụ.']);
            } else {
                ob_end_clean();
                echo json_encode(['success' => false, 'message' => 'Không giao được nhiệm vụ.']);
            }
            break;

        case 'reset_submission':
            $topicId = intval($_POST['topic_id'] ?? 0);
            $studentUserId = intval($_POST['user_id'] ?? 0);
            $groupId = intval($_POST['group_id'] ?? 0);

            if (!$topicId || (!$studentUserId && !$groupId)) {
                ob_end_clean();
                echo json_encode(['success' => false, 'message' => 'Thông tin không hợp lệ.']);
                exit;
            }

            if (!isTeacherInTopicClass($topicId, $userId)) {
                ob_end_clean();
                echo json_encode(['success' => false, 'message' => 'Bạn không có quyền thực hiện thao tác này.']);
                exit;
            }

            if ($groupId) {
                $result = resetGroupSubmissionsInTopic($topicId, $groupId);
            } else {
                $result = resetStudentSubmissionsInTopic($topicId, $studentUserId);
            }

            ob_end_clean();
            echo json_encode($result);
            break;

        case 'remove_assignment':
            $deliverId = intval($_POST['deliver_id'] ?? 0);
            $topicId = intval($_POST['topic_id'] ?? 0);

            if (!$deliverId || !$topicId) {
                ob_end_clean();
                echo json_encode(['success' => false, 'message' => 'Thông tin không hợp lệ.']);
                exit;
            }

            // Check if user is teacher
            if (!isTeacherInTopicClass($topicId, $userId)) {
                ob_end_clean();
                echo json_encode(['success' => false, 'message' => 'Bạn không có quyền thực hiện thao tác này.']);
                exit;
            }

            // Deliver phải thuộc một task trong topic này
            if (getTopicIdOfDeliver($deliverId) !== $topicId) {
                denyTaskAccess();
            }

            if (removeTaskAssignment($deliverId)) {
                ob_end_clean();
                echo json_encode(['success' => true, 'message' => 'Đã bỏ giao nhiệm vụ.']);
            } else {
                ob_end_clean();
                echo json_encode(['success' => false, 'message' => 'Không bỏ giao được nhiệm vụ.']);
            }
            break;

        case 'check_assignment_conflict':
            $taskId = intval($_POST['task_id'] ?? $_GET['task_id'] ?? 0);
            $assignType = intval($_POST['assign_type'] ?? $_GET['assign_type'] ?? 0);
            $assignValue = intval($_POST['assign_value'] ?? $_GET['assign_value'] ?? 0);

            if (!$taskId || !$assignType || !$assignValue) {
                ob_end_clean();
                echo json_encode(['success' => false, 'message' => 'Thông tin không hợp lệ.']);
                exit;
            }

            if (!isTeacherOfTask($taskId, $userId)) {
                denyTaskAccess();
            }

            $conflictCheck = checkAssignmentConflict($taskId, $assignType, $assignValue);

            ob_end_clean();
            echo json_encode([
                'success' => true,
                'has_conflict' => $conflictCheck['has_conflict'],
                'message' => $conflictCheck['message'],
                'conflict_type' => $conflictCheck['conflict_type']
            ]);
            break;

        default:
            ob_end_clean();
            echo json_encode(['success' => false, 'message' => 'Thao tác không hợp lệ.']);
            break;
    }
} catch (Exception $e) {
    ob_end_clean();
    error_log("Error in manage_tasks.php: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Có lỗi xảy ra: ' . client_error_message($e)]);
}
