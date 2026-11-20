<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php require_once __DIR__ . '/../templates/assets.php'; ?>
    <title>Bảng xếp hạng</title>
    <style>
        .leaderboard-rank { width: 40px; height: 40px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: bold; font-size: 1.1rem; }
        .rank-1 { background: linear-gradient(135deg, #FFD700, #FFA500); color: #fff; }
        .rank-2 { background: linear-gradient(135deg, #C0C0C0, #A0A0A0); color: #fff; }
        .rank-3 { background: linear-gradient(135deg, #CD7F32, #A0522D); color: #fff; }
        .rank-default { background: var(--lit-surface-3); color: var(--lit-text); }
        .badge-glow { animation: glow 2s ease-in-out infinite alternate; }
        @keyframes glow { from { box-shadow: 0 0 5px rgba(255,215,0,0.5); } to { box-shadow: 0 0 15px rgba(255,215,0,0.8); } }
    </style>
</head>

<body>
    <?php
    require_once __DIR__ . '/../templates/header.php';
    require_once __DIR__ . '/../templates/sidebar.php';
    ?>

    <main id="main" class="main">
        <div class="pagetitle">
            <h1><i class="bi bi-trophy"></i> Bảng xếp hạng</h1>
            <nav>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="<?= URL_ROOT ?>/class">Trang chủ</a></li>
                    <li class="breadcrumb-item active">Bảng xếp hạng</li>
                </ol>
            </nav>
        </div>

        <section class="section">
            <div class="row">
                <!-- Leaderboard -->
                <div class="col-lg-8">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title"><i class="bi bi-list-ol"></i> Top học viên</h5>
                            <div id="leaderboardList">
                                <div class="text-center py-4">
                                    <div class="spinner-border text-primary" role="status"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- My Stats -->
                <div class="col-lg-4">
                    <div class="card mb-3">
                        <div class="card-body text-center">
                            <h5 class="card-title">Thành tích của tôi</h5>
                            <div id="myStats">
                                <div class="spinner-border spinner-border-sm text-primary" role="status"></div>
                            </div>
                        </div>
                    </div>

                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title"><i class="bi bi-award"></i> Huy hiệu</h5>
                            <div id="myBadges">
                                <p class="text-muted text-center">Đang tải...</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <?php
    require_once __DIR__ . '/../templates/footer.php';
    require_once __DIR__ . '/../templates/vendors.php';
    ?>

    <script>
        document.addEventListener('DOMContentLoaded', async function() {
            await Promise.all([loadLeaderboard(), loadMyStats()]);
        });

        async function loadLeaderboard() {
            try {
                const resp = await fetch('/api/gamification.php?action=get_leaderboard&limit=20');
                const data = await resp.json();
                const container = document.getElementById('leaderboardList');

                if (!data.success || data.leaderboard.length === 0) {
                    container.innerHTML = '<p class="text-muted text-center py-3">Chưa có dữ liệu</p>';
                    return;
                }

                container.innerHTML = data.leaderboard.map((item, i) => {
                    const rankClass = i === 0 ? 'rank-1' : i === 1 ? 'rank-2' : i === 2 ? 'rank-3' : 'rank-default';
                    const medal = i === 0 ? '🥇' : i === 1 ? '🥈' : i === 2 ? '🥉' : '';
                    return `
                        <div class="d-flex align-items-center justify-content-between py-3 ${i < data.leaderboard.length - 1 ? 'border-bottom' : ''}">
                            <div class="d-flex align-items-center gap-3">
                                <div class="leaderboard-rank ${rankClass}">${i + 1}</div>
                                <div>
                                    <h6 class="mb-0">${escapeHtml(item.fullname)} ${medal}</h6>
                                    <small class="text-muted">
                                        <i class="bi bi-fire text-danger"></i> ${item.current_streak} ngày streak
                                        · <i class="bi bi-award text-warning"></i> ${item.badge_count} huy hiệu
                                    </small>
                                </div>
                            </div>
                            <div class="text-end">
                                <span class="badge bg-primary fs-6">${item.total_points} điểm</span>
                            </div>
                        </div>
                    `;
                }).join('');
            } catch (e) {
                console.error(e);
            }
        }

        async function loadMyStats() {
            try {
                const resp = await fetch('/api/gamification.php?action=get_profile');
                const data = await resp.json();
                const statsContainer = document.getElementById('myStats');
                const badgesContainer = document.getElementById('myBadges');

                if (!data.success) return;

                statsContainer.innerHTML = `
                    <div class="row text-center mt-3">
                        <div class="col-4">
                            <h3 class="text-primary mb-0">${data.total_points}</h3>
                            <small class="text-muted">Điểm</small>
                        </div>
                        <div class="col-4">
                            <h3 class="text-danger mb-0">${data.streak.current_streak}</h3>
                            <small class="text-muted">Streak</small>
                        </div>
                        <div class="col-4">
                            <h3 class="text-warning mb-0">${data.badges.length}</h3>
                            <small class="text-muted">Huy hiệu</small>
                        </div>
                    </div>
                `;

                if (data.badges.length > 0) {
                    badgesContainer.innerHTML = data.badges.map(b => `
                        <div class="d-flex align-items-center gap-2 py-2 border-bottom">
                            <i class="bi ${b.icon} text-${b.color} fs-4"></i>
                            <div>
                                <div class="fw-semibold">${escapeHtml(b.name)}</div>
                                <small class="text-muted">${escapeHtml(b.description || '')}</small>
                            </div>
                        </div>
                    `).join('');
                } else {
                    badgesContainer.innerHTML = '<p class="text-muted text-center">Chưa có huy hiệu</p>';
                }
            } catch (e) {
                console.error(e);
            }
        }

        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text || '';
            return div.innerHTML;
        }
    </script>
</body>

</html>
