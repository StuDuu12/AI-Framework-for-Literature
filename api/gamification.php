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

$action = $_GET['action'] ?? '';

try {
    switch ($action) {
        case 'get_profile':
            // Get user badges
            $stmt = $pdo->prepare("
                SELECT b.*, ub.earned_at 
                FROM user_badge ub 
                INNER JOIN badge b ON ub.badge_id = b.badge_id 
                WHERE ub.user_id = :uid 
                ORDER BY ub.earned_at DESC
            ");
            $stmt->execute([':uid' => $userId]);
            $badges = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Get streak
            $stmt = $pdo->prepare("SELECT * FROM user_streak WHERE user_id = :uid");
            $stmt->execute([':uid' => $userId]);
            $streak = $stmt->fetch(PDO::FETCH_ASSOC);

            // Get points
            $stmt = $pdo->prepare("SELECT total_points FROM user_points WHERE user_id = :uid");
            $stmt->execute([':uid' => $userId]);
            $points = $stmt->fetch(PDO::FETCH_ASSOC);

            // Get recent points log
            $stmt = $pdo->prepare("SELECT * FROM points_log WHERE user_id = :uid ORDER BY created_at DESC LIMIT 10");
            $stmt->execute([':uid' => $userId]);
            $pointsLog = $stmt->fetchAll(PDO::FETCH_ASSOC);

            echo json_encode([
                'success' => true,
                'badges' => $badges,
                'streak' => $streak ?: ['current_streak' => 0, 'longest_streak' => 0],
                'total_points' => $points ? (int)$points['total_points'] : 0,
                'points_log' => $pointsLog,
            ]);
            break;

        case 'get_leaderboard':
            $limit = min(intval($_GET['limit'] ?? 10), 50);
            $stmt = $pdo->prepare("
                SELECT u.user_id, u.fullname, u.role,
                       COALESCE(up.total_points, 0) as total_points,
                       COALESCE(us.current_streak, 0) as current_streak,
                       (SELECT COUNT(*) FROM user_badge WHERE user_id = u.user_id) as badge_count
                FROM users u
                LEFT JOIN user_points up ON u.user_id = up.user_id
                LEFT JOIN user_streak us ON u.user_id = us.user_id
                WHERE u.role = 'student'
                ORDER BY up.total_points DESC, us.current_streak DESC
                LIMIT :lim
            ");
            $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
            $stmt->execute();
            $leaderboard = $stmt->fetchAll(PDO::FETCH_ASSOC);

            echo json_encode(['success' => true, 'leaderboard' => $leaderboard]);
            break;

        case 'get_all_badges':
            $stmt = $pdo->query("SELECT * FROM badge ORDER BY badge_id ASC");
            echo json_encode(['success' => true, 'badges' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
            break;

        case 'record_activity':
            $input = json_decode(file_get_contents('php://input'), true);
            $activityType = $input['type'] ?? 'login';

            $today = date('Y-m-d');

            // Insert streak log (ignore duplicate)
            $stmt = $pdo->prepare("INSERT IGNORE INTO streak_log (user_id, log_date, activity_type) VALUES (:uid, :date, :type)");
            $stmt->execute([':uid' => $userId, ':date' => $today, ':type' => $activityType]);

            // Update streak
            $stmt = $pdo->prepare("SELECT * FROM user_streak WHERE user_id = :uid");
            $stmt->execute([':uid' => $userId]);
            $streak = $stmt->fetch(PDO::FETCH_ASSOC);

            $yesterday = date('Y-m-d', strtotime('-1 day'));

            if (!$streak) {
                // First time
                $stmt = $pdo->prepare("INSERT INTO user_streak (user_id, current_streak, longest_streak, last_active_date) VALUES (:uid, 1, 1, :date)");
                $stmt->execute([':uid' => $userId, ':date' => $today]);
            } elseif ($streak['last_active_date'] === $today) {
                // Already recorded today
            } elseif ($streak['last_active_date'] === $yesterday) {
                // Consecutive day
                $newStreak = $streak['current_streak'] + 1;
                $longest = max($newStreak, $streak['longest_streak']);
                $stmt = $pdo->prepare("UPDATE user_streak SET current_streak = :s, longest_streak = :l, last_active_date = :date WHERE user_id = :uid");
                $stmt->execute([':s' => $newStreak, ':l' => $longest, ':date' => $today, ':uid' => $userId]);
            } else {
                // Streak broken
                $stmt = $pdo->prepare("UPDATE user_streak SET current_streak = 1, last_active_date = :date WHERE user_id = :uid");
                $stmt->execute([':date' => $today, ':uid' => $userId]);
            }

            // Check and award badges
            checkAndAwardBadges($pdo, $userId);

            echo json_encode(['success' => true]);
            break;

        case 'add_points':
            $input = json_decode(file_get_contents('php://input'), true);
            $points = intval($input['points'] ?? 0);
            $reason = trim($input['reason'] ?? '');

            if ($points <= 0 || !$reason) {
                echo json_encode(['success' => false, 'message' => 'Invalid params']);
                exit();
            }

            // Upsert user_points
            $stmt = $pdo->prepare("INSERT INTO user_points (user_id, total_points) VALUES (:uid, :pts) ON DUPLICATE KEY UPDATE total_points = total_points + :pts2");
            $stmt->execute([':uid' => $userId, ':pts' => $points, ':pts2' => $points]);

            // Log
            $stmt = $pdo->prepare("INSERT INTO points_log (user_id, points, reason) VALUES (:uid, :pts, :reason)");
            $stmt->execute([':uid' => $userId, ':pts' => $points, ':reason' => $reason]);

            echo json_encode(['success' => true]);
            break;

        default:
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
            break;
    }
} catch (PDOException $e) {
    error_log("gamification error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error']);
}

function checkAndAwardBadges(PDO $pdo, int $userId): void
{
    // Get all badge definitions
    $badges = $pdo->query("SELECT * FROM badge")->fetchAll(PDO::FETCH_ASSOC);

    foreach ($badges as $badge) {
        // Check if already earned
        $stmt = $pdo->prepare("SELECT 1 FROM user_badge WHERE user_id = :uid AND badge_id = :bid");
        $stmt->execute([':uid' => $userId, ':bid' => $badge['badge_id']]);
        if ($stmt->fetch()) continue;

        $earned = false;

        switch ($badge['criteria_type']) {
            case 'tasks_completed':
                $stmt = $pdo->prepare("SELECT COUNT(*) FROM deliver WHERE user_id = :uid AND status = 1");
                $stmt->execute([':uid' => $userId]);
                $count = (int)$stmt->fetchColumn();
                $earned = $count >= $badge['criteria_value'];
                break;

            case 'evaluations_done':
                $stmt = $pdo->prepare("SELECT COUNT(*) FROM evaluate WHERE user_id = :uid AND status = 2");
                $stmt->execute([':uid' => $userId]);
                $count = (int)$stmt->fetchColumn();
                $earned = $count >= $badge['criteria_value'];
                break;

            case 'streak_days':
                $stmt = $pdo->prepare("SELECT current_streak FROM user_streak WHERE user_id = :uid");
                $stmt->execute([':uid' => $userId]);
                $streak = $stmt->fetch(PDO::FETCH_ASSOC);
                $earned = $streak && $streak['current_streak'] >= $badge['criteria_value'];
                break;
        }

        if ($earned) {
            $stmt = $pdo->prepare("INSERT IGNORE INTO user_badge (user_id, badge_id) VALUES (:uid, :bid)");
            $stmt->execute([':uid' => $userId, ':bid' => $badge['badge_id']]);

            // Award points for badge
            $stmt = $pdo->prepare("INSERT INTO user_points (user_id, total_points) VALUES (:uid, 10) ON DUPLICATE KEY UPDATE total_points = total_points + 10");
            $stmt->execute([':uid' => $userId]);

            $stmt = $pdo->prepare("INSERT INTO points_log (user_id, points, reason) VALUES (:uid, 10, :reason)");
            $stmt->execute([':uid' => $userId, ':reason' => 'Badge earned: ' . $badge['name']]);
        }
    }
}
