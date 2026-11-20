<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php require_once __DIR__ . '/../templates/assets.php'; ?>
    <title data-lang-key="my_groups_title">Nhóm của tôi</title>
</head>

<body>
    <?php
    require_once __DIR__ . '/../templates/header.php';
    require_once __DIR__ . '/../../config/db.php';
    require_once __DIR__ . '/../../config/helper_class.php';
    require_once __DIR__ . '/../templates/sidebar.php';

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
            <h1 data-lang-key="my_groups_title">Nhóm của tôi</h1>
            <nav>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="<?= URL_ROOT ?>/class"><?= htmlspecialchars($_SESSION['current_class_name'] ?? 'Lớp học') ?></a></li>
                    <li class="breadcrumb-item active" data-lang-key="my_groups_title">Nhóm của tôi</li>
                </ol>
            </nav>
        </div><!-- End Page Title -->

        <section class="section dashboard my-groups-page">
            <!-- Debug Info -->
            <?php if (isset($_GET['debug']) && ($_SESSION['user']['role'] ?? '') === 'admin'): ?>
                <div class="alert alert-info">
                    <h6><i class="bi bi-bug"></i> Debug Information</h6>
                    <p><strong>User ID:</strong> <?= $userId ?></p>
                    <p><strong>Mã lớp:</strong> <?= $classId ?? 'NULL' ?></p>
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
                            <summary>Các nhóm của người dùng này (<?= count($allMemberships) ?> nhóm)</summary>
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
                                <summary>Tất cả nhóm trong lớp (<?= count($classGroups) ?> nhóm)</summary>
                                <pre><?php print_r($classGroups); ?></pre>
                            </details>
                        <?php endif; ?>
                    <?php else: ?>
                        <div class="alert alert-danger mt-2">
                            <strong>ERROR:</strong> User ID = 0. Session has no user_id!
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($myGroups)): ?>
                        <details>
                            <summary>Chi tiết nhóm</summary>
                            <pre><?php print_r($myGroups); ?></pre>
                        </details>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <?php if (empty($myGroups)): ?>
                <div class="card border-0 shadow-sm">
                    <div class="card-body empty-state">
                        <i class="bi bi-people"></i>
                        <h5 class="mt-3 mb-2" data-lang-key="not_in_any_group">Bạn chưa ở nhóm nào</h5>
                        <p class="text-muted mb-4" data-lang-key="teacher_will_add_you">Thầy cô sẽ thêm bạn vào nhóm khi cần.</p>
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
                                            <div class="members-label" data-lang-key="members">Thành viên</div>
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
                                                        title="<?= count($members) - $displayLimit ?> more members">
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
                                            <i class="bi bi-eye me-2"></i><span data-lang-key="view_details">Xem chi tiết</span>
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
                                                        <th>Họ tên</th>
                                                        <th>Tên đăng nhập</th>
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
    require_once __DIR__ . '/../templates/footer.php';
    require_once __DIR__ . '/../templates/vendors.php';
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
