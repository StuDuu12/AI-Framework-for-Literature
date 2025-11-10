<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý Reflect - Hệ thống hỗ trợ</title>

    <?php include_once __DIR__ . '/../templates/assets.php'; ?>
    <link rel="stylesheet" href="<?php echo URL_ROOT; ?>/assets/css/reflect.css">
</head>

<body>

    <?php
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    if (!isset($_SESSION['user'])) {
        header('Location: ' . URL_ROOT . '/login');
        exit;
    }

    $user = $_SESSION['user'];
    $isAdmin = ($user['role'] === 'admin');

    include_once __DIR__ . '/../templates/header.php';
    include_once __DIR__ . '/../templates/sidebar_' . $user['role'] . '.php';
    ?>

    <main id="main" class="main">
        <div class="pagetitle">
            <h1><i class="bi bi-ticket-detailed"></i> Quản lý phản hồi</h1>
            <nav>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="<?php echo URL_ROOT; ?>">Trang chủ</a></li>
                    <li class="breadcrumb-item active">Phản hồi</li>
                </ol>
            </nav>
        </div>

        <section class="section">
            <div class="row">
                <div class="col-lg-12">
                    <!-- Filter Card -->
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title">Bộ lọc</h5>
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <label class="form-label">Loại phản hồi</label>
                                    <select id="filterType" class="form-select">
                                        <option value="">Tất cả loại</option>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Trạng thái</label>
                                    <select id="filterStatus" class="form-select">
                                        <option value="">Tất cả trạng thái</option>
                                        <option value="0">Mới tạo</option>
                                        <option value="1">Đang xử lý</option>
                                        <option value="2">Đã giải quyết</option>
                                        <option value="3">Đã đóng</option>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Tìm kiếm</label>
                                    <input type="text" id="searchInput" class="form-control"
                                        placeholder="Tìm theo tiêu đề...">
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Tickets Card -->
                    <div class="card">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <h5 class="card-title">Danh sách phản hồi</h5>
                                <span id="ticketCount" class="badge bg-primary">0 ticket</span>
                            </div>

                            <!-- Loading State -->
                            <div id="loadingState" class="loading-spinner" style="display: none;">
                                <div class="spinner"></div>
                            </div>

                            <!-- Tickets Container -->
                            <div id="ticketsContainer"></div>

                            <!-- Empty State -->
                            <div id="emptyState" class="empty-state" style="display: none;">
                                <i class="bi bi-inbox"></i>
                                <p>Chưa có ticket nào</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Create Ticket Button -->
        <button class="btn-create-ticket" onclick="openCreateModal()" title="Tạo ticket mới">
            <i class="bi bi-plus-lg"></i>
        </button>
    </main>

    <!-- Create Ticket Modal -->
    <div id="createModal" class="reflect-modal">
        <div class="modal-content">
            <div class="modal-header">
                <h5>Tạo Reflect mới</h5>
                <button class="modal-close" onclick="closeCreateModal()">×</button>
            </div>
            <div class="modal-body">
                <form id="createTicketForm">
                    <div class="form-group">
                        <label>Loại Reflect <span class="required">*</span></label>
                        <select id="createType" class="form-control" required>
                            <option value="">-- Chọn loại --</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Tiêu đề <span class="required">*</span></label>
                        <input type="text" id="createTitle" class="form-control" placeholder="Nhập tiêu đề..." required>
                    </div>
                    <div class="form-group">
                        <label>Nội dung <span class="required">*</span></label>
                        <textarea id="createContent" class="form-control" placeholder="Mô tả chi tiết vấn đề của bạn..."
                            required></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeCreateModal()">Hủy</button>
                <button type="button" class="btn btn-primary" onclick="createTicket()">
                    <i class="bi bi-send"></i> Tạo Reflect
                </button>
            </div>
        </div>
    </div>

    <?php include_once __DIR__ . '/../templates/footer.php'; ?>

    <script>
        let allTickets = [];
        let reflectTypes = [];
        const isAdmin = <?php echo $isAdmin ? 'true' : 'false'; ?>;

        // Initialize
        document.addEventListener('DOMContentLoaded', function () {
            loadReflectTypes();
            loadTickets();

            // Filter listeners
            document.getElementById('filterType').addEventListener('change', applyFilters);
            document.getElementById('filterStatus').addEventListener('change', applyFilters);
            document.getElementById('searchInput').addEventListener('input', applyFilters);
        });

        // Load reflect types
        async function loadReflectTypes() {
            try {
                const response = await fetch('<?php echo URL_ROOT; ?>/api/reflect_manager.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ action: 'get_types' })
                });

                const data = await response.json();
                console.log('Types response:', data); // Debug

                if (data.success && data.data) {
                    reflectTypes = data.data;

                    // Populate filter dropdown
                    const filterSelect = document.getElementById('filterType');
                    data.data.forEach(type => {
                        const option = document.createElement('option');
                        option.value = type.id_reflect_type;
                        option.textContent = type.name;
                        filterSelect.appendChild(option);
                    });

                    // Populate create modal dropdown
                    const createSelect = document.getElementById('createType');
                    data.data.forEach(type => {
                        const option = document.createElement('option');
                        option.value = type.id_reflect_type;
                        option.textContent = type.name;
                        createSelect.appendChild(option);
                    });
                } else {
                    console.error('Types API error:', data.message || 'Unknown error');
                    alert('Không thể tải danh sách loại reflect: ' + (data.message || 'Unknown error'));
                }
            } catch (error) {
                console.error('Error loading types:', error);
                alert('Có lỗi khi kết nối tới server: ' + error.message);
            }
        }

        // Load tickets
        async function loadTickets() {
            const container = document.getElementById('ticketsContainer');
            const loading = document.getElementById('loadingState');
            const empty = document.getElementById('emptyState');

            container.innerHTML = '';
            loading.style.display = 'flex';
            empty.style.display = 'none';

            try {
                const action = isAdmin ? 'get_all_tickets' : 'get_my_tickets';
                const response = await fetch('<?php echo URL_ROOT; ?>/api/reflect_manager.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ action })
                });

                const data = await response.json();
                console.log('Tickets response:', data); // Debug

                loading.style.display = 'none';

                if (data.success && data.data && data.data.length > 0) {
                    allTickets = data.data;
                    applyFilters();
                } else {
                    empty.style.display = 'block';
                    document.getElementById('ticketCount').textContent = '0 ticket';
                    if (!data.success) {
                        console.error('Tickets API error:', data.message || 'Unknown error');
                    }
                }
            } catch (error) {
                loading.style.display = 'none';
                console.error('Error loading tickets:', error);
                alert('Có lỗi khi tải danh sách ticket: ' + error.message);
            }
        }

        // Apply filters
        function applyFilters() {
            const typeFilter = document.getElementById('filterType').value;
            const statusFilter = document.getElementById('filterStatus').value;
            const searchText = document.getElementById('searchInput').value.toLowerCase();

            let filtered = allTickets.filter(ticket => {
                const matchType = !typeFilter || ticket.id_reflect_type == typeFilter;
                const matchStatus = statusFilter === '' || ticket.status == statusFilter;
                const matchSearch = !searchText || ticket.title.toLowerCase().includes(searchText);
                return matchType && matchStatus && matchSearch;
            });

            displayTickets(filtered);
        }

        // Display tickets
        function displayTickets(tickets) {
            const container = document.getElementById('ticketsContainer');
            const empty = document.getElementById('emptyState');

            document.getElementById('ticketCount').textContent = `${tickets.length} ticket${tickets.length !== 1 ? 's' : ''}`;

            if (tickets.length === 0) {
                container.innerHTML = '';
                empty.style.display = 'block';
                return;
            }

            empty.style.display = 'none';
            container.innerHTML = tickets.map(ticket => `
        <div class="ticket-card status-${ticket.status}" onclick="viewTicket(${ticket.id_reflect})">
            <div class="ticket-header">
                <h3 class="ticket-title">
                    <i class="bi bi-ticket-detailed-fill"></i> ${escapeHtml(ticket.title)}
                </h3>
                <span class="ticket-status-badge ${getStatusBadgeClass(ticket.status)}">
                    ${getStatusLabel(ticket.status)}
                </span>
            </div>
            <div class="ticket-meta">
                <div class="ticket-meta-item">
                    <i class="bi bi-tag"></i>
                    <span>${escapeHtml(ticket.type_name)}</span>
                </div>
                <div class="ticket-meta-item">
                    <i class="bi bi-person"></i>
                    <span>${escapeHtml(ticket.creator_name)}</span>
                </div>
                <div class="ticket-meta-item">
                    <i class="bi bi-chat-dots"></i>
                    <span>${ticket.response_count} phản hồi</span>
                </div>
                <div class="ticket-meta-item">
                    <i class="bi bi-clock"></i>
                    <span>${formatDate(ticket.created_at)}</span>
                </div>
            </div>
        </div>
    `).join('');
        }

        // Get status label
        function getStatusLabel(status) {
            const labels = {
                0: 'Mới tạo',
                1: 'Đang xử lý',
                2: 'Đã giải quyết',
                3: 'Đã đóng'
            };
            return labels[status] || 'Không xác định';
        }

        // Get status badge class
        function getStatusBadgeClass(status) {
            const classes = {
                0: 'bg-info',
                1: 'bg-warning',
                2: 'bg-success',
                3: 'bg-secondary'
            };
            return classes[status] || 'bg-secondary';
        }

        // Format date
        function formatDate(dateString) {
            const date = new Date(dateString);
            const now = new Date();
            const diff = now - date;

            if (diff < 60000) return 'Vừa xong';
            if (diff < 3600000) return `${Math.floor(diff / 60000)} phút trước`;
            if (diff < 86400000) return `${Math.floor(diff / 3600000)} giờ trước`;
            if (diff < 604800000) return `${Math.floor(diff / 86400000)} ngày trước`;

            return date.toLocaleDateString('vi-VN');
        }

        // Escape HTML
        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        // View ticket
        function viewTicket(ticketId) {
            window.location.href = `<?php echo URL_ROOT; ?>/reflect/detail?id=${ticketId}`;
        }

        // Modal functions
        function openCreateModal() {
            document.getElementById('createModal').classList.add('active');
            document.getElementById('createTicketForm').reset();
        }

        function closeCreateModal() {
            document.getElementById('createModal').classList.remove('active');
        }

        // Create ticket
        async function createTicket() {
            const typeId = document.getElementById('createType').value;
            const title = document.getElementById('createTitle').value.trim();
            const content = document.getElementById('createContent').value.trim();

            if (!typeId || !title || !content) {
                alert('Vui lòng điền đầy đủ thông tin');
                return;
            }

            try {
                const response = await fetch('<?php echo URL_ROOT; ?>/api/reflect_manager.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        action: 'create_ticket',
                        type_id: typeId,
                        title: title,
                        content: content
                    })
                });

                const data = await response.json();
                if (data.success) {
                    closeCreateModal();
                    alert('Tạo reflect thành công!');
                    loadTickets();
                } else {
                    alert('Lỗi: ' + data.message);
                }
            } catch (error) {
                console.error('Error creating ticket:', error);
                alert('Có lỗi khi tạo ticket');
            }
        }

        // Close modal when clicking outside
        document.getElementById('createModal').addEventListener('click', function (e) {
            if (e.target === this) {
                closeCreateModal();
            }
        });
    </script>

</body>

</html>