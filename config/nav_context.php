<?php
/**
 * Đồng bộ ngữ cảnh điều hướng (lớp / chủ đề / giai đoạn) từ URL vào session.
 *
 * Trước đây "lớp hiện tại" chỉ nằm trong session: mở link nhiệm vụ của lớp khác,
 * hoặc mở 2 tab 2 lớp, thì sidebar/breadcrumb hiển thị sai lớp. Giờ URL là nguồn sự thật:
 * nếu URL có topic_id/phase_id/task_id, ta suy ra lớp từ chính đối tượng đó
 * (chỉ khi người dùng là thành viên lớp hoặc admin) và cập nhật session.
 *
 * Được gọi ở index.php cho các route bắt đầu bằng "class".
 */

if (!function_exists('syncNavContextFromUrl')) {
    function syncNavContextFromUrl(PDO $pdo): void
    {
        $topicId = (int)($_GET['topic_id'] ?? 0);
        $phaseId = (int)($_GET['phase_id'] ?? 0);
        $taskId = (int)($_GET['task_id'] ?? 0);
        if ($topicId <= 0 && $phaseId <= 0 && $taskId <= 0) {
            return;
        }

        try {
            // Suy ra topic từ phase/task nếu URL không có topic_id
            if ($topicId <= 0 && $phaseId > 0) {
                $st = $pdo->prepare('SELECT topic_id FROM phase WHERE phase_id = ?');
                $st->execute([$phaseId]);
                $topicId = (int)$st->fetchColumn();
            }
            if ($topicId <= 0 && $taskId > 0) {
                $st = $pdo->prepare('SELECT p.topic_id, p.phase_id FROM task t JOIN phase p ON p.phase_id = t.phase_id WHERE t.task_id = ?');
                $st->execute([$taskId]);
                $row = $st->fetch(PDO::FETCH_ASSOC) ?: [];
                $topicId = (int)($row['topic_id'] ?? 0);
                if ($phaseId <= 0) {
                    $phaseId = (int)($row['phase_id'] ?? 0);
                }
            }
            if ($topicId <= 0) {
                return;
            }

            $st = $pdo->prepare('SELECT c.class_id, c.class_name FROM topic t JOIN class c ON c.class_id = t.class_id WHERE t.topic_id = ?');
            $st->execute([$topicId]);
            $class = $st->fetch(PDO::FETCH_ASSOC);
            if (!$class) {
                return;
            }

            $userId = (int)($_SESSION['user']['user_id'] ?? 0);
            $role = $_SESSION['user']['role'] ?? '';
            if ($role !== 'admin') {
                $st = $pdo->prepare('SELECT 1 FROM class_member WHERE class_id = ? AND user_id = ?');
                $st->execute([(int)$class['class_id'], $userId]);
                if (!$st->fetchColumn()) {
                    return; // không phải thành viên: không đổi ngữ cảnh (view tự chặn quyền)
                }
            }

            $_SESSION['current_class_id'] = (int)$class['class_id'];
            $_SESSION['current_class_name'] = $class['class_name'];
            $_SESSION['nav_topic_id'] = $topicId;
            $phaseOk = false;
            if ($phaseId > 0) {
                $st = $pdo->prepare('SELECT 1 FROM phase WHERE phase_id = ? AND topic_id = ?');
                $st->execute([$phaseId, $topicId]);
                $phaseOk = (bool)$st->fetchColumn();
            }
            if ($phaseOk) {
                $_SESSION['nav_phase_id'] = $phaseId;
            } elseif (isset($_GET['topic_id'])) {
                unset($_SESSION['nav_phase_id']); // sang chủ đề khác: bỏ giai đoạn cũ
            }
        } catch (Throwable $e) {
            error_log('syncNavContextFromUrl: ' . $e->getMessage());
        }
    }
}
