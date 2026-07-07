<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php require_once './views/templates/assets.php'; ?>
    <title>Admin Analytics Dashboard</title>
    <style>
        .stat-card { transition: transform 0.2s; }
        .stat-card:hover { transform: translateY(-2px); }
        .stat-icon { width: 48px; height: 48px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1.5rem; }
        .chart-container { position: relative; height: 250px; }
    </style>
</head>

<body>
    <?php
    require_once './views/templates/header.php';
    require_once './views/templates/sidebar_admin.php';
    ?>

    <main id="main" class="main">
        <div class="pagetitle">
            <h1><i class="bi bi-graph-up"></i> Analytics Dashboard</h1>
            <nav>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="<?= URL_ROOT ?>/admin">Admin</a></li>
                    <li class="breadcrumb-item active">Analytics</li>
                </ol>
            </nav>
        </div>

        <section class="section">
            <!-- Loading indicator -->
            <div id="loadingState" class="text-center py-5">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
                <p class="mt-2 text-muted">Loading analytics data...</p>
            </div>

            <div id="analyticsContent" style="display: none;">
                <!-- Summary Cards -->
                <div class="row g-3 mb-4">
                    <div class="col-md-3">
                        <div class="card stat-card">
                            <div class="card-body d-flex align-items-center gap-3">
                                <div class="stat-icon bg-primary bg-opacity-10 text-primary">
                                    <i class="bi bi-people"></i>
                                </div>
                                <div>
                                    <h3 class="mb-0" id="totalUsers">0</h3>
                                    <small class="text-muted">Tổng người dùng</small>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card stat-card">
                            <div class="card-body d-flex align-items-center gap-3">
                                <div class="stat-icon bg-success bg-opacity-10 text-success">
                                    <i class="bi bi-book"></i>
                                </div>
                                <div>
                                    <h3 class="mb-0" id="totalClasses">0</h3>
                                    <small class="text-muted">Lớp học</small>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card stat-card">
                            <div class="card-body d-flex align-items-center gap-3">
                                <div class="stat-icon bg-warning bg-opacity-10 text-warning">
                                    <i class="bi bi-clipboard-check"></i>
                                </div>
                                <div>
                                    <h3 class="mb-0" id="totalTasks">0</h3>
                                    <small class="text-muted">Nhiệm vụ</small>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card stat-card">
                            <div class="card-body d-flex align-items-center gap-3">
                                <div class="stat-icon bg-info bg-opacity-10 text-info">
                                    <i class="bi bi-check-circle"></i>
                                </div>
                                <div>
                                    <h3 class="mb-0" id="completionRate">0%</h3>
                                    <small class="text-muted">Tỷ lệ hoàn thành</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Second row -->
                <div class="row g-3 mb-4">
                    <div class="col-md-3">
                        <div class="card stat-card">
                            <div class="card-body d-flex align-items-center gap-3">
                                <div class="stat-icon bg-secondary bg-opacity-10 text-secondary">
                                    <i class="bi bi-person-badge"></i>
                                </div>
                                <div>
                                    <h3 class="mb-0" id="totalStudents">0</h3>
                                    <small class="text-muted">Học viên</small>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card stat-card">
                            <div class="card-body d-flex align-items-center gap-3">
                                <div class="stat-icon bg-secondary bg-opacity-10 text-secondary">
                                    <i class="bi bi-mortarboard"></i>
                                </div>
                                <div>
                                    <h3 class="mb-0" id="totalTeachers">0</h3>
                                    <small class="text-muted">Giáo viên</small>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card stat-card">
                            <div class="card-body d-flex align-items-center gap-3">
                                <div class="stat-icon bg-danger bg-opacity-10 text-danger">
                                    <i class="bi bi-hourglass-split"></i>
                                </div>
                                <div>
                                    <h3 class="mb-0" id="pendingRegistrations">0</h3>
                                    <small class="text-muted">Chờ duyệt</small>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card stat-card">
                            <div class="card-body d-flex align-items-center gap-3">
                                <div class="stat-icon bg-primary bg-opacity-10 text-primary">
                                    <i class="bi bi-star"></i>
                                </div>
                                <div>
                                    <h3 class="mb-0" id="totalEvaluations">0</h3>
                                    <small class="text-muted">Đánh giá</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Charts Row -->
                <div class="row g-3 mb-4">
                    <div class="col-md-8">
                        <div class="card">
                            <div class="card-body">
                                <h5 class="card-title"><i class="bi bi-bar-chart"></i> Thống kê theo lớp</h5>
                                <div class="chart-container">
                                    <canvas id="classChart"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card">
                            <div class="card-body">
                                <h5 class="card-title"><i class="bi bi-pie-chart"></i> Phân loại người dùng</h5>
                                <div class="chart-container">
                                    <canvas id="userChart"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Top Teachers -->
                <div class="row g-3">
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-body">
                                <h5 class="card-title"><i class="bi bi-trophy"></i> Top Giáo viên</h5>
                                <div id="topTeachersList"></div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-body">
                                <h5 class="card-title"><i class="bi bi-calendar-check"></i> Đăng ký gần đây</h5>
                                <div id="recentRegChart" style="height: 200px;"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <?php
    require_once './views/templates/footer.php';
    require_once './views/templates/vendors.php';
    ?>

    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', async function() {
            try {
                const resp = await fetch('/api/admin_analytics.php');
                const data = await resp.json();

                if (!data.success) {
                    alert(data.message || 'Error loading analytics');
                    return;
                }

                const s = data.stats;

                // Update summary cards
                document.getElementById('totalUsers').textContent = s.users.total;
                document.getElementById('totalClasses').textContent = s.classes.total;
                document.getElementById('totalTasks').textContent = s.tasks.total;
                document.getElementById('completionRate').textContent = s.tasks.completion_rate + '%';
                document.getElementById('totalStudents').textContent = s.users.students;
                document.getElementById('totalTeachers').textContent = s.users.teachers;
                document.getElementById('pendingRegistrations').textContent = s.users.pending_registrations;
                document.getElementById('totalEvaluations').textContent = s.evaluations.total;

                // Class stats chart
                if (s.class_stats.length > 0) {
                    new Chart(document.getElementById('classChart'), {
                        type: 'bar',
                        data: {
                            labels: s.class_stats.map(c => c.class_name),
                            datasets: [
                                {
                                    label: 'Nhiệm vụ',
                                    data: s.class_stats.map(c => c.task_count),
                                    backgroundColor: 'rgba(54, 162, 235, 0.7)',
                                },
                                {
                                    label: 'Hoàn thành',
                                    data: s.class_stats.map(c => c.completed_count),
                                    backgroundColor: 'rgba(75, 192, 192, 0.7)',
                                }
                            ]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: { legend: { position: 'top' } }
                        }
                    });
                }

                // User distribution chart
                new Chart(document.getElementById('userChart'), {
                    type: 'doughnut',
                    data: {
                        labels: ['Học viên', 'Giáo viên', 'Admin'],
                        datasets: [{
                            data: [s.users.students, s.users.teachers, s.users.admins],
                            backgroundColor: ['#36a2eb', '#ffce56', '#ff6384'],
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                    }
                });

                // Top teachers
                const teachersHtml = s.top_teachers.length > 0
                    ? s.top_teachers.map((t, i) => `
                        <div class="d-flex align-items-center justify-content-between py-2 ${i < s.top_teachers.length - 1 ? 'border-bottom' : ''}">
                            <div class="d-flex align-items-center gap-2">
                                <span class="badge bg-${i === 0 ? 'warning' : i === 1 ? 'secondary' : 'light text-dark'}">${i + 1}</span>
                                <span>${escapeHtml(t.fullname)}</span>
                            </div>
                            <span class="badge bg-primary">${t.task_count} nhiệm vụ</span>
                        </div>
                    `).join('')
                    : '<p class="text-muted">Chưa có dữ liệu</p>';
                document.getElementById('topTeachersList').innerHTML = teachersHtml;

                // Recent registrations
                if (s.recent_registrations.length > 0) {
                    const regHtml = s.recent_registrations.map(r => `
                        <div class="d-flex justify-content-between py-1 border-bottom">
                            <small>${r.date}</small>
                            <span class="badge bg-info">${r.count}</span>
                        </div>
                    `).join('');
                    document.getElementById('recentRegChart').innerHTML = regHtml;
                } else {
                    document.getElementById('recentRegChart').innerHTML = '<p class="text-muted text-center py-3">Chưa có đăng ký nào</p>';
                }

                document.getElementById('loadingState').style.display = 'none';
                document.getElementById('analyticsContent').style.display = 'block';

            } catch (error) {
                console.error('Error:', error);
                alert('Lỗi tải dữ liệu analytics');
            }
        });

        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text || '';
            return div.innerHTML;
        }
    </script>
</body>

</html>
