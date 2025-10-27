<?php
require_once '../config/db.php';
require_once '../config/helper_phase.php';
require_once '../config/helper_task.php';
require_once '../config/helper_class.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Start output buffering to prevent any output before JSON
ob_start();

header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user'])) {
    ob_end_clean();
    echo json_encode(['success' => false, 'message' => 'Bạn cần đăng nhập']);
    exit;
}

$action = $_POST['action'] ?? '';
$userId = $_SESSION['user']['user_id'];

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
                echo json_encode(['success' => false, 'message' => 'Vui lòng điền đầy đủ thông tin']);
                exit;
            }

            // Check if user is teacher
            if (!isTeacherInTopicClass($topicId, $userId)) {
                ob_end_clean();
                echo json_encode(['success' => false, 'message' => 'Bạn không có quyền thực hiện thao tác này']);
                exit;
            }

            // Get phase details to check if it's evaluation phase
            $phase = getPhaseDetails($phaseId);
            if (!$phase) {
                ob_end_clean();
                echo json_encode(['success' => false, 'message' => 'Không tìm thấy pha học tập']);
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
                    echo json_encode(['success' => false, 'message' => 'Vui lòng chọn nhiệm vụ cần đánh giá']);
                    exit;
                }

                // Validate source task exists
                $sourceTask = getTaskDetails($sourceTaskId);
                if (!$sourceTask) {
                    ob_end_clean();
                    echo json_encode(['success' => false, 'message' => 'Nhiệm vụ nguồn không tồn tại']);
                    exit;
                }

                // Validate source task is from correct phase (PHASE_START or PHASE_BTVN)
                $sourcePhase = getPhaseDetails($sourceTask['phase_id']);
                if (!$sourcePhase || !in_array($sourcePhase['phase_code'], ['PHASE_START', 'PHASE_BTVN'])) {
                    ob_end_clean();
                    echo json_encode(['success' => false, 'message' => 'Nhiệm vụ nguồn phải từ pha Khởi động hoặc Bài tập về nhà']);
                    exit;
                }

                $reviewTask = $sourceTaskId;
            } else {
                // Handle regular phase with content
                // Handle file upload
                if ($contentType === 'file' && isset($_FILES['content_file']) && $_FILES['content_file']['error'] === UPLOAD_ERR_OK) {
                    $file = $_FILES['content_file'];
                    $allowedTypes = ['text/plain'];

                    if (!in_array($file['type'], $allowedTypes)) {
                        ob_end_clean();
                        echo json_encode(['success' => false, 'message' => 'Chỉ chấp nhận file .txt']);
                        exit;
                    }

                    // Create upload directory if not exists
                    $uploadDir = '../uploads/tasks/';
                    if (!is_dir($uploadDir)) {
                        mkdir($uploadDir, 0777, true);
                    }

                    $fileName = uniqid('task_') . '_' . basename($file['name']);
                    $uploadPath = $uploadDir . $fileName;

                    if (move_uploaded_file($file['tmp_name'], $uploadPath)) {
                        $fileContent = 'tasks/' . $fileName;
                    } else {
                        ob_end_clean();
                        echo json_encode(['success' => false, 'message' => 'Lỗi khi tải file lên']);
                        exit;
                    }
                } elseif ($contentType === 'text' && !empty($contentText)) {
                    // Save text content to file
                    $uploadDir = '../uploads/tasks/';
                    if (!is_dir($uploadDir)) {
                        mkdir($uploadDir, 0777, true);
                    }

                    $fileName = uniqid('task_') . '.txt';
                    $uploadPath = $uploadDir . $fileName;

                    if (file_put_contents($uploadPath, $contentText) !== false) {
                        $fileContent = 'tasks/' . $fileName;
                        $contentCreator = $userId;
                    } else {
                        ob_end_clean();
                        echo json_encode(['success' => false, 'message' => 'Lỗi khi lưu nội dung']);
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
                'added_by' => $userId
            ];

            $taskId = createTask($taskData);

            if ($taskId) {
                ob_end_clean();
                echo json_encode(['success' => true, 'message' => 'Tạo nhiệm vụ thành công', 'task_id' => $taskId]);
            } else {
                ob_end_clean();
                echo json_encode(['success' => false, 'message' => 'Không thể tạo nhiệm vụ']);
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
                echo json_encode(['success' => false, 'message' => 'Vui lòng điền đầy đủ thông tin']);
                exit;
            }

            // Check if user is teacher
            if (!isTeacherInTopicClass($topicId, $userId)) {
                ob_end_clean();
                echo json_encode(['success' => false, 'message' => 'Bạn không có quyền thực hiện thao tác này']);
                exit;
            }

            // Get current task
            $currentTask = getTaskDetails($taskId);
            if (!$currentTask) {
                ob_end_clean();
                echo json_encode(['success' => false, 'message' => 'Không tìm thấy nhiệm vụ']);
                exit;
            }

            // Get phase details to check if it's evaluation phase
            $phase = getPhaseDetails($currentTask['phase_id']);
            if (!$phase) {
                ob_end_clean();
                echo json_encode(['success' => false, 'message' => 'Không tìm thấy pha học tập']);
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
                    echo json_encode(['success' => false, 'message' => 'Vui lòng chọn nhiệm vụ cần đánh giá']);
                    exit;
                }

                // Validate source task exists
                $sourceTask = getTaskDetails($sourceTaskId);
                if (!$sourceTask) {
                    ob_end_clean();
                    echo json_encode(['success' => false, 'message' => 'Nhiệm vụ nguồn không tồn tại']);
                    exit;
                }

                // Validate source task is from correct phase
                $sourcePhase = getPhaseDetails($sourceTask['phase_id']);
                if (!$sourcePhase || !in_array($sourcePhase['phase_code'], ['PHASE_START', 'PHASE_BTVN'])) {
                    ob_end_clean();
                    echo json_encode(['success' => false, 'message' => 'Nhiệm vụ nguồn phải từ pha Khởi động hoặc Bài tập về nhà']);
                    exit;
                }

                $reviewTask = $sourceTaskId;
            } else {
                // Handle regular phase with content
                // Handle new file upload or text content
                if ($keepOldFile === 'no') {
                    if ($contentType === 'file' && isset($_FILES['content_file']) && $_FILES['content_file']['error'] === UPLOAD_ERR_OK) {
                        $file = $_FILES['content_file'];
                        $allowedTypes = ['text/plain'];

                        if (!in_array($file['type'], $allowedTypes)) {
                            ob_end_clean();
                            echo json_encode(['success' => false, 'message' => 'Chỉ chấp nhận file .txt']);
                            exit;
                        }

                        $uploadDir = '../uploads/tasks/';
                        if (!is_dir($uploadDir)) {
                            mkdir($uploadDir, 0777, true);
                        }

                        // Delete old file if exists
                        if ($currentTask['file_content']) {
                            $oldFilePath = '../uploads/' . $currentTask['file_content'];
                            if (file_exists($oldFilePath)) {
                                unlink($oldFilePath);
                            }
                        }

                        $fileName = uniqid('task_') . '_' . basename($file['name']);
                        $uploadPath = $uploadDir . $fileName;

                        if (move_uploaded_file($file['tmp_name'], $uploadPath)) {
                            $fileContent = 'tasks/' . $fileName;
                            $contentCreator = null;
                        }
                    } elseif ($contentType === 'text' && !empty($contentText)) {
                        $uploadDir = '../uploads/tasks/';
                        if (!is_dir($uploadDir)) {
                            mkdir($uploadDir, 0777, true);
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
                'review_task' => $reviewTask
            ];

            if (updateTask($taskId, $taskData)) {
                ob_end_clean();
                echo json_encode(['success' => true, 'message' => 'Cập nhật nhiệm vụ thành công']);
            } else {
                ob_end_clean();
                echo json_encode(['success' => false, 'message' => 'Không thể cập nhật nhiệm vụ']);
            }
            break;

        case 'delete_task':
            $taskId = intval($_POST['task_id'] ?? 0);
            $topicId = intval($_POST['topic_id'] ?? 0);

            if (!$taskId || !$topicId) {
                ob_end_clean();
                echo json_encode(['success' => false, 'message' => 'Thông tin không hợp lệ']);
                exit;
            }

            // Check if user is teacher
            if (!isTeacherInTopicClass($topicId, $userId)) {
                ob_end_clean();
                echo json_encode(['success' => false, 'message' => 'Bạn không có quyền thực hiện thao tác này']);
                exit;
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
                echo json_encode(['success' => true, 'message' => 'Xóa nhiệm vụ thành công']);
            } else {
                ob_end_clean();
                echo json_encode(['success' => false, 'message' => 'Không thể xóa nhiệm vụ']);
            }
            break;

        case 'get_task':
            $taskId = intval($_POST['task_id'] ?? 0);

            if (!$taskId) {
                ob_end_clean();
                echo json_encode(['success' => false, 'message' => 'Thông tin không hợp lệ']);
                exit;
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
                echo json_encode(['success' => false, 'message' => 'Không tìm thấy nhiệm vụ']);
            }
            break;

        case 'assign_task':
            $taskId = intval($_POST['task_id'] ?? 0);
            $topicId = intval($_POST['topic_id'] ?? 0);
            $assignType = intval($_POST['assign_type'] ?? 0);
            $assignValues = $_POST['assign_values'] ?? [];

            if (!$taskId || !$topicId || !$assignType) {
                ob_end_clean();
                echo json_encode(['success' => false, 'message' => 'Thông tin không hợp lệ']);
                exit;
            }

            // Check if user is teacher
            if (!isTeacherInTopicClass($topicId, $userId)) {
                ob_end_clean();
                echo json_encode(['success' => false, 'message' => 'Bạn không có quyền thực hiện thao tác này']);
                exit;
            }

            // Validate assign values
            if (empty($assignValues)) {
                ob_end_clean();
                echo json_encode(['success' => false, 'message' => 'Vui lòng chọn đối tượng để giao nhiệm vụ']);
                exit;
            }

            // Check for duplicates
            $duplicates = [];
            foreach ($assignValues as $value) {
                if (isTaskAssigned($taskId, $assignType, $value)) {
                    $duplicates[] = $value;
                }
            }

            if (!empty($duplicates)) {
                ob_end_clean();
                echo json_encode([
                    'success' => false,
                    'message' => 'Một số đối tượng đã được giao nhiệm vụ này trước đó'
                ]);
                exit;
            }

            if (assignTask($taskId, $assignType, $assignValues, $userId)) {
                ob_end_clean();
                echo json_encode(['success' => true, 'message' => 'Giao nhiệm vụ thành công']);
            } else {
                ob_end_clean();
                echo json_encode(['success' => false, 'message' => 'Không thể giao nhiệm vụ']);
            }
            break;

        case 'remove_assignment':
            $deliverId = intval($_POST['deliver_id'] ?? 0);
            $topicId = intval($_POST['topic_id'] ?? 0);

            if (!$deliverId || !$topicId) {
                ob_end_clean();
                echo json_encode(['success' => false, 'message' => 'Thông tin không hợp lệ']);
                exit;
            }

            // Check if user is teacher
            if (!isTeacherInTopicClass($topicId, $userId)) {
                ob_end_clean();
                echo json_encode(['success' => false, 'message' => 'Bạn không có quyền thực hiện thao tác này']);
                exit;
            }

            if (removeTaskAssignment($deliverId)) {
                ob_end_clean();
                echo json_encode(['success' => true, 'message' => 'Hủy giao nhiệm vụ thành công']);
            } else {
                ob_end_clean();
                echo json_encode(['success' => false, 'message' => 'Không thể hủy giao nhiệm vụ']);
            }
            break;

        default:
            ob_end_clean();
            echo json_encode(['success' => false, 'message' => 'Hành động không hợp lệ']);
            break;
    }
} catch (Exception $e) {
    ob_end_clean();
    error_log("Error in manage_tasks.php: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Có lỗi xảy ra: ' . $e->getMessage()]);
}
