<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php require_once './views/templates/assets.php'; ?>
    <title>Nhóm của tôi</title>
    <style>
        body {
            font-family: 'Nunito', 'Segoe UI', 'Open Sans', sans-serif;
        }

        .group-card {
            transition: all 0.3s ease;
            border: 1px solid #e0e0e0;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
        }

        .group-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 4px 16px rgba(0, 0, 0, 0.12);
            border-color: #4154f1;
        }

        .group-header {
            background: #4154f1;
            color: white;
            padding: 20px;
        }

        .group-title {
            font-size: 1.1rem;
            font-weight: 600;
            margin: 0;
        }

        .member-badge {
            background: rgba(255, 255, 255, 0.2);
            padding: 6px 14px;
            border-radius: 20px;
            font-size: 0.9rem;
            font-weight: 500;
        }

        .member-avatar {
            width: 42px;
            height: 42px;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            color: white;
            font-size: 15px;
            transition: transform 0.2s ease;
        }

        .member-avatar:hover {
            transform: scale(1.15);
        }

        .avatar-stack {
            display: flex;
            padding: 10px 0;
        }

        .avatar-stack .member-avatar {
            border: 3px solid white;
            margin-left: -12px;
            cursor: pointer;
        }

        .avatar-stack .member-avatar:first-child {
            margin-left: 0;
        }

        .avatar-more {
            background: #95a5a6;
        }

        .btn-view-details {
            background: #4154f1;
            border: none;
            color: white;
            font-weight: 500;
            padding: 10px;
            border-radius: 8px;
            transition: all 0.2s ease;
        }

        .btn-view-details:hover {
            background: #2c3cdd;
            transform: translateY(-2px);
        }

        .members-section {
            background: #f8f9fc;
            padding: 15px;
            border-radius: 8px;
            margin: 15px 0;
        }

        .members-label {
            color: #6c757d;
            font-weight: 600;
            margin-bottom: 10px;
            font-size: 0.85rem;
            text-transform: uppercase;
        }

        .empty-state {
            padding: 60px 20px;
            text-align: center;
        }

        .empty-state i {
            font-size: 4rem;
            color: #d0d5dd;
        }

        .info-box {
            background: #f0f4ff;
            border: 1px solid #d4e1ff;
            border-radius: 10px;
            padding: 20px;
        }

        .info-box h6 {
            color: #4154f1;
            font-weight: 600;
            margin-bottom: 15px;
        }

        .info-box ol {
            color: #666;
        }

        .modal-header {
            background: #4154f1;
            color: white;
            border: none;
        }

        .modal-title {
            font-weight: 600;
        }

        .table thead {
            background: #f8f9fc;
        }

        .table-hover tbody tr:hover {
            background: #f8f9fc;
        }

        .badge.bg-success {
            background: #198754 !important;
        }
    </style>
</head>

<body>
    <?php
    require_once './views/templates/header.php';
    require_once __DIR__ . '/../../config/db.php';
    require_once __DIR__ . '/../../config/helper_class.php';

    if (isset($_SESSION['user']['role']) && $_SESSION['user']['role'] === 'teacher') {
        require_once './views/templates/sidebar_teacher.php';
    } else if (isset($_SESSION['user']['role']) && $_SESSION['user']['role'] === 'student') {
        require_once './views/templates/sidebar_student.php';
    }

    // Get current user and class
    $userId = $_SESSION['user']['user_id'] ?? ($_SESSION['user']['id'] ?? 0);
    $classId = getCurrentClassId();

    // Debug: Check what's in session
    if (isset($_GET['debug'])) {
        error_log("Session user data: " . print_r($_SESSION['user'] ?? 'NO USER SESSION', true));
    }

    // Get groups that the user is a member of
    $myGroups = getAllGroupsOfStudentInClasses($userId, $classId);

    // Helper functions
    function getAvatarColor($str)
    {
        $colors = ['#3498db', '#2ecc71', '#e74c3c', '#f39c12', '#9b59b6', '#1abc9c', '#34495e', '#e67e22'];
        $index = ord($str[0]) % count($colors);
        return $colors[$index];
    }

    function getInitials($name)
    {
        $words = explode(' ', trim($name));
        if (count($words) >= 2) {
            return mb_substr($words[0], 0, 1) . mb_substr($words[count($words) - 1], 0, 1);
        }
        return mb_substr($name, 0, 2);
    }

    // Get group members for display
    function getGroupMembersForDisplay($groupId)
    {
        $groupDetails = getGroupDetails($groupId);
        return $groupDetails ? $groupDetails['members'] : [];
    }
    ?>

    <main id="main" class="main">
        <div class="pagetitle">
            <h1>Nhóm của tôi</h1>
            <nav>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="<?= URL_ROOT ?>/class"><?= htmlspecialchars($_SESSION['current_class_name'] ?? 'Lớp học') ?></a></li>
                    <li class="breadcrumb-item active">Nhóm của tôi</li>
                </ol>
            </nav>
        </div><!-- End Page Title -->

        <section class="section dashboard">
            <!-- Debug Info -->
            <?php if (isset($_GET['debug'])): ?>
                <div class="alert alert-info">
                    <h6><i class="bi bi-bug"></i> Debug Information</h6>
                    <p><strong>User ID:</strong> <?= $userId ?></p>
                    <p><strong>Class ID:</strong> <?= $classId ?? 'NULL' ?></p>
                    <p><strong>Số nhóm tìm thấy:</strong> <?= count($myGroups) ?></p>

                    <details>
                        <summary>Session User Data</summary>
                        <pre><?php print_r($_SESSION['user'] ?? 'NO USER SESSION'); ?></pre>
                    </details>

                    <?php if ($userId > 0): ?>
                        <?php
                        // Check if user exists in group_member table
                        $checkStmt = $pdo->prepare("SELECT gm.*, g.group_name, g.class_id 
                                                    FROM group_member gm 
                                                    LEFT JOIN `group` g ON gm.group_id = g.group_id 
                                                    WHERE gm.user_id = ?");
                        $checkStmt->execute([$userId]);
                        $allMemberships = $checkStmt->fetchAll(PDO::FETCH_ASSOC);
                        ?>
                        <details>
                            <summary>All group memberships for this user (<?= count($allMemberships) ?> found)</summary>
                            <pre><?php print_r($allMemberships); ?></pre>
                        </details>

                        <?php
                        // Check groups in this class
                        if ($classId):
                            $classGroupStmt = $pdo->prepare("SELECT * FROM `group` WHERE class_id = ?");
                            $classGroupStmt->execute([$classId]);
                            $classGroups = $classGroupStmt->fetchAll(PDO::FETCH_ASSOC);
                        ?>
                            <details>
                                <summary>All groups in this class (<?= count($classGroups) ?> found)</summary>
                                <pre><?php print_r($classGroups); ?></pre>
                            </details>
                        <?php endif; ?>
                    <?php else: ?>
                        <div class="alert alert-danger mt-2">
                            <strong>LỖI:</strong> User ID = 0. Session không có thông tin user_id!
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($myGroups)): ?>
                        <details>
                            <summary>Chi tiết các nhóm</summary>
                            <pre><?php print_r($myGroups); ?></pre>
                        </details>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <?php if (empty($myGroups)): ?>
                <div class="card border-0 shadow-sm">
                    <div class="card-body empty-state">
                        <i class="bi bi-people"></i>
                        <h5 class="mt-3 mb-2">Bạn chưa tham gia nhóm nào</h5>
                        <p class="text-muted mb-4">Giáo viên sẽ thêm bạn vào nhóm khi cần thiết.</p>
                    </div>
                </div>
            <?php else: ?>
                <div class="row">
                    <?php foreach ($myGroups as $group):
                        $members = getGroupMembersForDisplay($group['group_id']);
                    ?>
                        <div class="col-md-6 col-lg-4 mb-4">
                            <div class="card group-card h-100">
                                <div class="card-body">
                                    <div class="group-header">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <h5 class="group-title">
                                                <i class="bi bi-people-fill me-2"></i>
                                                <?= htmlspecialchars($group['group_name']) ?>
                                            </h5>
                                            <span class="member-badge">
                                                <?= count($members) ?> thành viên
                                            </span>
                                        </div>
                                    </div>

                                    <?php if (!empty($members)): ?>
                                        <div class="members-section">
                                            <div class="members-label">Thành viên</div>
                                            <div class="avatar-stack">
                                                <?php
                                                $displayLimit = 5;
                                                $displayMembers = array_slice($members, 0, $displayLimit);
                                                foreach ($displayMembers as $member):
                                                    $initials = getInitials($member['fullname']);
                                                    $color = getAvatarColor($member['fullname']);
                                                ?>
                                                    <div class="member-avatar"
                                                        style="background-color: <?= $color ?>;"
                                                        data-bs-toggle="tooltip"
                                                        data-bs-placement="top"
                                                        title="<?= htmlspecialchars($member['fullname']) ?>">
                                                        <?= strtoupper($initials) ?>
                                                    </div>
                                                <?php endforeach; ?>
                                                <?php if (count($members) > $displayLimit): ?>
                                                    <div class="member-avatar avatar-more"
                                                        data-bs-toggle="tooltip"
                                                        title="Còn <?= count($members) - $displayLimit ?> thành viên khác">
                                                        +<?= count($members) - $displayLimit ?>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    <?php endif; ?>

                                    <div class="d-grid">
                                        <button class="btn btn-view-details"
                                            data-bs-toggle="modal"
                                            data-bs-target="#groupModal<?= $group['group_id'] ?>">
                                            <i class="bi bi-eye me-2"></i>Xem chi tiết
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Group Detail Modal -->
                        <div class="modal fade" id="groupModal<?= $group['group_id'] ?>" tabindex="-1">
                            <div class="modal-dialog modal-lg">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title">
                                            <i class="bi bi-people-fill me-2"></i>
                                            <?= htmlspecialchars($group['group_name']) ?>
                                        </h5>
                                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                    </div>
                                    <div class="modal-body">
                                        <h6 class="mb-3 text-muted">
                                            <i class="bi bi-list-ul me-2"></i>Danh sách thành viên (<?= count($members) ?>)
                                        </h6>
                                        <div class="table-responsive">
                                            <table class="table table-hover align-middle">
                                                <thead>
                                                    <tr>
                                                        <th style="width: 60px;">#</th>
                                                        <th>Họ và tên</th>
                                                        <th>Username</th>
                                                        <th>Email</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php foreach ($members as $index => $member): ?>
                                                        <tr>
                                                            <td>
                                                                <div class="member-avatar"
                                                                    style="background-color: <?= getAvatarColor($member['fullname']) ?>; width: 36px; height: 36px; font-size: 13px;">
                                                                    <?= strtoupper(getInitials($member['fullname'])) ?>
                                                                </div>
                                                            </td>
                                                            <td>
                                                                <?= htmlspecialchars($member['fullname']) ?>
                                                                <?php if ($member['user_id'] == $userId): ?>
                                                                    <span class="badge bg-success ms-2">Bạn</span>
                                                                <?php endif; ?>
                                                            </td>
                                                            <td class="text-muted"><?= htmlspecialchars($member['username']) ?></td>
                                                            <td class="text-muted"><?= htmlspecialchars($member['email']) ?></td>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                                            <i class="bi bi-x-circle me-1"></i>Đóng
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>

    </main><!-- End #main -->

    <?php
    require_once 'views/templates/footer.php';
    require_once 'views/templates/vendors.php';
    ?>

    <script>
        // Initialize tooltips
        document.addEventListener('DOMContentLoaded', function() {
            var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
            var tooltipList = tooltipTriggerList.map(function(tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl);
            });
        });
    </script>
</body>

</html>