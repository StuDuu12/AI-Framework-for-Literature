<?php
$ticketId = isset($_GET['id']) ? intval($_GET['id']) : 0;
$user = $_SESSION['user'];
?>
<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title data-lang-key="reflect_detail">Chi tiết bài làm</title>

    <?php include_once __DIR__ . '/../templates/assets.php'; ?>
    <link rel="stylesheet" href="<?php echo URL_ROOT; ?>/assets/css/reflect.css">

</head>

<body>
    <?php
    require_once __DIR__ . '/../templates/header.php';
    require_once __DIR__ . '/../templates/sidebar.php';
    ?>

    <main id="main" class="main">
        <div class="pagetitle">
            <h1><i class="bi bi-ticket-detailed"></i> <span data-lang-key="reflect_detail">Chi tiết bài làm</span></h1>
            <nav>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="<?php echo URL_ROOT; ?>">Trang chủ</a></li>
                    <li class="breadcrumb-item"><a href="<?php echo URL_ROOT; ?>/reflect">Bài làm</a></li>
                    <li class="breadcrumb-item active">Chi tiết</li>
                </ol>
            </nav>
        </div>

        <section class="section">
            <div class="row">
                <div class="col-lg-12">
                    <!-- Ticket Detail Card -->
                    <div class="card">
                        <div id="ticketDetailContainer" class="card-body" style="padding: 0;">
                            <!-- Loading -->
                            <div class="loading-spinner">
                                <div class="spinner"></div>
                            </div>
                        </div>
                    </div>

                    <!-- Responses Card -->
                    <div class="card" id="responsesCard" style="display: none;">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h5 class="card-title mb-0">
                                    <i class="bi bi-chat-dots"></i> <span>Bài làm</span>
                                    <span id="responseCount" class="badge bg-primary ms-2">0</span>
                                </h5>
                                <button class="btn btn-sm btn-outline-secondary" onclick="collapseAllResponses()">
                                    <i class="bi bi-arrows-collapse"></i> Thu gọn tất cả
                                </button>
                            </div>
                            <div id="responsesContainer"></div>
                        </div>
                    </div>

                    <!-- Add Response Card -->
                    <div class="card" id="responseFormCard" style="display: none;">
                        <div class="card-body">
                            <h5 class="card-title"><i class="bi bi-reply"></i> <span>Thêm phản hồi</span></h5>
                            <form id="responseForm" class="response-form">
                                <div class="form-group">
                                    <label for="responseContent">Nội dung bài làm <span class="required">*</span></label>
                                    <textarea id="responseContent" class="form-control"
                                        placeholder="Nhập nội dung bài làm…" required></textarea>
                                </div>
                                <div class="text-end">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="bi bi-send"></i> <span>Gửi phản hồi</span>
                                    </button>
                                </div>
                            </form>
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
        const ticketId = <?php echo $ticketId; ?>;
        const currentUserId = <?php echo $user['user_id']; ?>;
        const currentUserRole = '<?php echo $user['role']; ?>';
        let ticketData = null;

        // Initialize
        document.addEventListener('DOMContentLoaded', function () {
            loadTicketDetail();

            // Response form submit
            document.getElementById('responseForm').addEventListener('submit', async function (e) {
                e.preventDefault();
                await addResponse();
            });
        });

        // Load ticket detail
        async function loadTicketDetail() {
            try {
                const response = await fetch('<?php echo URL_ROOT; ?>/api/reflect_manager.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        action: 'get_ticket_detail',
                        ticket_id: ticketId
                    })
                });

                const data = await response.json();
                console.log('Ticket detail response:', data); // Debug

                if (data.success && data.data) {
                    ticketData = data.data;
                    displayTicketDetail(ticketData);
                    displayResponses(ticketData.responses);

                    // Show response form if ticket is not closed
                    if (ticketData.ticket.status != 3) {
                        document.getElementById('responseFormCard').style.display = 'block';
                    }
                } else {
                    console.error('Ticket detail error:', data.message || 'Có lỗi xảy ra. Vui lòng thử lại.');
                    UI.error(data.message || 'Không tải được chi tiết góp ý.');
                    window.location.href = '<?php echo URL_ROOT; ?>/reflect';
                }
            } catch (error) {
                console.error('Error loading ticket:', error);
                UI.networkError(error, 'Không tải được chi tiết góp ý.');
                window.location.href = '<?php echo URL_ROOT; ?>/reflect';
            }
        }

        // Display ticket detail
        function displayTicketDetail(data) {
            const ticket = data.ticket;
            const canUpdate = currentUserRole === 'admin' || ticket.id_user == currentUserId;

            const statusOptions = canUpdate ? `
        <select class="status-select" onchange="updateStatus(this.value)">
            <option value="0" ${ticket.status == 0 ? 'selected' : ''}>Mới</option>
            <option value="1" ${ticket.status == 1 ? 'selected' : ''}>Đang xử lý</option>
            <option value="2" ${ticket.status == 2 ? 'selected' : ''}>Đã xử lý</option>
            <option value="3" ${ticket.status == 3 ? 'selected' : ''}>Đã đóng</option>
        </select>
    ` : `<span class="ticket-status-badge ${getStatusBadgeClass(ticket.status)}">${getStatusLabel(ticket.status)}</span>`;

            const deleteButton = canUpdate ? `
        <button class="btn btn-danger btn-sm" onclick="deleteTicket()">
            <i class="bi bi-trash"></i> Xoá yêu cầu
        </button>
    ` : '';

            document.getElementById('ticketDetailContainer').innerHTML = `
        <div class="ticket-detail-header">
            <div class="header-top">
                <div class="ticket-detail-title p-1">
                    <i class="bi bi-ticket-detailed-fill"></i>
                    ${escapeHtml(ticket.title)}
                </div>
            </div>
            <div class="header-content">
                <div class="header-left">
                    <div class="info-row">
                        <i class="bi bi-tag-fill"></i>
                        <span class="info-label">Loại:</span>
                        <span class="type-badge">${escapeHtml(ticket.type_name)}</span>
                    </div>
                    <div class="info-row">
                        <i class="bi bi-person-fill"></i>
                        <span class="info-label">Người tạo:</span>
                        <span class="info-value">${escapeHtml(ticket.creator_name)}</span>
                    </div>
                    <div class="info-row">
                        <i class="bi bi-envelope-fill"></i>
                        <span class="info-label">Email:</span>
                        <span class="info-value">${escapeHtml(ticket.creator_email)}</span>
                    </div>
                </div>
                <div class="header-right">
                    <div class="info-row">
                        <i class="bi bi-clock-fill"></i>
                        <span class="info-label">Tạo lúc:</span>
                        <span class="info-value">${formatDateTime(ticket.created_at)}</span>
                    </div>
                    <div class="info-row">
                        <i class="bi bi-arrow-clockwise"></i>
                        <span class="info-label">Cập nhật:</span>
                        <span class="info-value">${formatDateTime(ticket.updated_at)}</span>
                    </div>
                    <div class="info-row">
                        <i class="bi bi-info-circle-fill"></i>
                        <span class="info-label">Trạng thái:</span>
                        ${statusOptions}
                    </div>
                </div>
            </div>
        </div>
        <div class="ticket-detail-body">
            <div class="initial-content">
                        <h6><i class="bi bi-file-text"></i> Nội dung ban đầu</h6>
                <div class="response-content">${escapeHtml(data.responses[0].content)}</div>
            </div>
            
            ${deleteButton ? `<div class="text-end">${deleteButton}</div>` : ''}
        </div>
    `;

            document.getElementById('responsesCard').style.display = 'block';
        }

        // Display responses
        function displayResponses(responses) {
            // Skip first response (initial ticket content)
            const actualResponses = responses.slice(1);

            document.getElementById('responseCount').textContent = actualResponses.length;

            if (actualResponses.length === 0) {
                document.getElementById('responsesContainer').innerHTML = `
            <div class="empty-state">
                <i class="bi bi-chat"></i>
                <p>Chưa có phản hồi</p>
            </div>
        `;
                return;
            }

            // Organize responses by parent
            const topLevelResponses = actualResponses.filter(r => !r.parent_reflect_detail);
            const repliesMap = {};

            actualResponses.filter(r => r.parent_reflect_detail).forEach(reply => {
                if (!repliesMap[reply.parent_reflect_detail]) {
                    repliesMap[reply.parent_reflect_detail] = [];
                }
                repliesMap[reply.parent_reflect_detail].push(reply);
            });

            // Render responses with collapse groups
            let html = '';

            topLevelResponses.forEach(response => {
                // Start parent group
                html += `<div class="parent-response-group" id="parent-group-${response.id_reflect_detail}">`;
                html += renderResponse(response, false, repliesMap[response.id_reflect_detail]);

                // Render replies container
                if (repliesMap[response.id_reflect_detail]) {
                    html += `<div class="replies-container" id="replies-${response.id_reflect_detail}">`;
                    repliesMap[response.id_reflect_detail].forEach(reply => {
                        html += renderResponse(reply, true, null);
                    });
                    html += `</div>`;
                }

                html += `</div>`;
            });

            document.getElementById('responsesContainer').innerHTML = html;
        }

        function renderResponse(response, isReply, replies) {
            const replyClass = isReply ? 'is-reply' : '';
            const canReply = ticketData.ticket.status != 3 && !isReply; // Not closed and not already a reply
            const hasReplies = replies && replies.length > 0;
            const canDelete = currentUserRole === 'admin'; // Only admin can delete

            return `
        <div class="response-item ${replyClass}" id="response-${response.id_reflect_detail}">
            <div class="response-header" ${!isReply ? `onclick="toggleCollapse(${response.id_reflect_detail})"` : ''}>
                <div class="response-author">
                    <div class="response-author-avatar">${getInitials(response.user_name)}</div>
                    <div class="response-author-info">
                        <div class="response-author-name">${escapeHtml(response.user_name)}</div>
                        <div class="response-author-role">${getRoleLabel(response.user_role)} � ${formatDateTime(response.created_at)}</div>
                    </div>
                </div>
                ${!isReply ? `
                    <div class="d-flex align-items-center gap-2">
                        ${hasReplies ? `<span class="badge bg-secondary">${replies.length} responses</span>` : ''}
                        <i class="bi bi-chevron-down collapse-icon"></i>
                    </div>
                ` : ''}
            </div>
            <div class="response-body" id="response-body-${response.id_reflect_detail}">
                <div class="response-content">${escapeHtml(response.content)}</div>
                <div class="response-actions">
                    ${canReply ? `
                        <button class="btn btn-sm btn-outline-primary btn-reply" onclick="event.stopPropagation(); toggleReplyForm(${response.id_reflect_detail})">
                            <i class="bi bi-reply"></i> Phản hồi
                        </button>
                    ` : ''}
                    ${canDelete ? `
                        <button class="btn btn-sm btn-outline-danger" onclick="event.stopPropagation(); deleteResponse(${response.id_reflect_detail}, ${hasReplies ? replies.length : 0})">
                            <i class="bi bi-trash"></i> Xoá
                        </button>
                    ` : ''}
                </div>
                ${canReply ? `
                    <div id="reply-form-${response.id_reflect_detail}" class="reply-form">
                        <div class="form-group mb-2">
                            <textarea id="reply-content-${response.id_reflect_detail}" class="form-control" rows="3" placeholder="Nhập nội dung trả lời…"></textarea>
                        </div>
                        <div class="text-end">
                            <button class="btn btn-sm btn-secondary me-2" onclick="event.stopPropagation(); toggleReplyForm(${response.id_reflect_detail})">Huỷ</button>
                            <button class="btn btn-sm btn-primary" onclick="event.stopPropagation(); submitReply(${response.id_reflect_detail})">
                                <i class="bi bi-send"></i> Gửi
                            </button>
                        </div>
                    </div>
                ` : ''}
            </div>
        </div>
    `;
        }

        // Add response
        async function addResponse() {
            const content = document.getElementById('responseContent').value.trim();

            if (!content) {
                UI.warning('Vui lòng nhập nội dung phản hồi.');
                return;
            }

            try {
                const response = await fetch('<?php echo URL_ROOT; ?>/api/reflect_manager.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        action: 'add_response',
                        ticket_id: ticketId,
                        content: content
                    })
                });

                const data = await response.json();

                if (data.success) {
                    document.getElementById('responseContent').value = '';
                    loadTicketDetail(); // Reload to show new response
                } else {
                    UI.error(data.message);
                }
            } catch (error) {
                console.error('Error adding response:', error);
                UI.error('Không thêm được phản hồi.');
            }
        }

        // Toggle collapse - now collapses parent with all replies
        function toggleCollapse(detailId) {
            const parentGroup = document.getElementById(`parent-group-${detailId}`);
            const responseItem = document.getElementById(`response-${detailId}`);
            const responseBody = document.getElementById(`response-body-${detailId}`);
            const repliesContainer = document.getElementById(`replies-${detailId}`);

            if (responseItem && responseBody) {
                responseItem.classList.toggle('collapsed');
                responseBody.classList.toggle('collapsed');

                // Also toggle replies container
                if (repliesContainer) {
                    repliesContainer.classList.toggle('collapsed');
                }
            }
        }

        // Collapse all responses
        function collapseAllResponses() {
            const allParentGroups = document.querySelectorAll('.parent-response-group');

            allParentGroups.forEach(group => {
                const responseItem = group.querySelector('.response-item:not(.is-reply)');
                const responseBody = group.querySelector('.response-body');
                const repliesContainer = group.querySelector('.replies-container');

                if (responseItem) responseItem.classList.add('collapsed');
                if (responseBody) responseBody.classList.add('collapsed');
                if (repliesContainer) repliesContainer.classList.add('collapsed');
            });
        }

        // Expand all responses
        function expandAllResponses() {
            const allParentGroups = document.querySelectorAll('.parent-response-group');

            allParentGroups.forEach(group => {
                const responseItem = group.querySelector('.response-item:not(.is-reply)');
                const responseBody = group.querySelector('.response-body');
                const repliesContainer = group.querySelector('.replies-container');

                if (responseItem) responseItem.classList.remove('collapsed');
                if (responseBody) responseBody.classList.remove('collapsed');
                if (repliesContainer) repliesContainer.classList.remove('collapsed');
            });
        }

        // Toggle reply form
        function toggleReplyForm(detailId) {
            const form = document.getElementById(`reply-form-${detailId}`);
            if (form) {
                form.classList.toggle('active');
                if (form.classList.contains('active')) {
                    document.getElementById(`reply-content-${detailId}`).focus();
                }
            }
        }

        // Submit reply
        async function submitReply(parentDetailId) {
            const content = document.getElementById(`reply-content-${parentDetailId}`).value.trim();

            if (!content) {
                UI.warning('Vui lòng nhập nội dung trả lời.');
                return;
            }

            try {
                const response = await fetch('<?php echo URL_ROOT; ?>/api/reflect_manager.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        action: 'add_response',
                        ticket_id: ticketId,
                        content: content,
                        parent_detail_id: parentDetailId
                    })
                });

                const data = await response.json();

                if (data.success) {
                    loadTicketDetail(); // Reload to show new reply
                } else {
                    UI.error(data.message);
                }
            } catch (error) {
                console.error('Error submitting reply:', error);
                UI.error('Không gửi được trả lời.');
            }
        }

        // Update status
        async function updateStatus(newStatus) {
            if (!await UI.confirm('Đổi trạng thái góp ý này?')) {
                loadTicketDetail(); // Reset dropdown
                return;
            }

            try {
                const response = await fetch('<?php echo URL_ROOT; ?>/api/reflect_manager.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        action: 'update_status',
                        ticket_id: ticketId,
                        status: newStatus
                    })
                });

                const data = await response.json();

                if (data.success) {
                    UI.success('Đã cập nhật trạng thái.');
                    loadTicketDetail();
                } else {
                    UI.error(data.message);
                    loadTicketDetail(); // Reset dropdown
                }
            } catch (error) {
                console.error('Error updating status:', error);
                UI.error('Không cập nhật được trạng thái.');
                loadTicketDetail();
            }
        }

        // Delete ticket
        async function deleteTicket() {
            if (!await UI.confirm('Xoá góp ý này? Thao tác này không thể hoàn tác.')) {
                return;
            }

            try {
                const response = await fetch('<?php echo URL_ROOT; ?>/api/reflect_manager.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        action: 'delete_ticket',
                        ticket_id: ticketId
                    })
                });

                const data = await response.json();

                if (data.success) {
                    UI.success('Đã xoá góp ý.');
                    window.location.href = '<?php echo URL_ROOT; ?>/reflect';
                } else {
                    UI.error(data.message);
                }
            } catch (error) {
                console.error('Error deleting ticket:', error);
                UI.error('Không xoá được góp ý.');
            }
        }

        // Delete response
        async function deleteResponse(detailId, replyCount) {
            const confirmMessage = replyCount > 0
                ? `Phản hồi này có ${replyCount} trả lời. Xoá phản hồi sẽ xoá luôn các trả lời. Tiếp tục xoá?`
                : 'Xoá phản hồi này? Thao tác này không thể hoàn tác.';

            if (!await UI.confirm(confirmMessage)) {
                return;
            }

            try {
                const response = await fetch('<?php echo URL_ROOT; ?>/api/reflect_manager.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        action: 'delete_response',
                        detail_id: detailId
                    })
                });

                const data = await response.json();

                if (data.success) {
                    UI.success('Đã xoá phản hồi.');
                    loadTicketDetail(); // Reload to refresh list
                } else {
                    UI.error(data.message);
                }
            } catch (error) {
                console.error('Error deleting response:', error);
                UI.error('Không xoá được phản hồi.');
            }
        }

        // Helper functions
        function getStatusLabel(status) {
            const labels = {
                0: 'Mới',
                1: 'Đang xử lý',
                2: 'Đã xử lý',
                3: 'Đã đóng'
            };
            return labels[status] || 'Không rõ';
        }

        function getStatusBadgeClass(status) {
            const classes = {
                0: 'bg-info',
                1: 'bg-warning',
                2: 'bg-success',
                3: 'bg-secondary'
            };
            return classes[status] || 'bg-secondary';
        }

        function getRoleLabel(role) {
            const labels = {
                admin: 'Quản trị viên',
                teacher: 'Giáo viên',
                student: 'Học sinh'
            };
            return labels[role] || role;
        }

        function getInitials(name) {
            return name.split(' ').map(n => n[0]).join('').toUpperCase().slice(0, 2);
        }

        function formatDateTime(dateString) {
            const date = new Date(dateString);
            return date.toLocaleString('vi-VN', {
                year: 'numeric',
                month: '2-digit',
                day: '2-digit',
                hour: '2-digit',
                minute: '2-digit'
            });
        }

        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
    </script>

</body>

</html>