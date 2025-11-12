<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chi tiết Phản hồi</title>

    <?php include_once __DIR__ . '/../templates/assets.php'; ?>
    <link rel="stylesheet" href="<?php echo URL_ROOT; ?>/assets/css/reflect.css">

    <style>
        /* Ticket Detail Container */
        #ticketDetailContainer {
            padding: 0 !important;
        }

        .ticket-detail-header {
            background: #fff;
            border-bottom: 3px solid #4154f1;
            padding: 0;
            border-radius: 12px 12px 0 0;
        }

        .header-top {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            padding: 24px 30px;
            border-radius: 12px 12px 0 0;
        }

        .ticket-detail-title {
            font-size: 24px;
            font-weight: 700;
            color: #2c3e50;
            margin: 0;
            line-height: 1.4;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .ticket-detail-title i {
            color: #4154f1;
            font-size: 28px;
            flex-shrink: 0;
        }

        .header-content {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
            padding: 24px 30px;
        }

        .header-left,
        .header-right {
            display: flex;
            flex-direction: column;
            gap: 14px;
        }

        .header-right {
            border-left: 2px solid #e9ecef;
            padding-left: 30px;
        }

        .info-row {
            display: flex;
            align-items: center;
            gap: 12px;
            font-size: 14px;
            color: #495057;
        }

        .info-row i {
            width: 28px;
            height: 28px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #f0f2ff;
            color: #4154f1;
            border-radius: 6px;
            font-size: 14px;
            flex-shrink: 0;
        }

        .info-label {
            font-weight: 600;
            color: #6c757d;
            min-width: 90px;
        }

        .info-value {
            color: #2c3e50;
            font-weight: 500;
            flex: 1;
        }

        .type-badge {
            display: inline-block;
            padding: 6px 16px;
            background: linear-gradient(135deg, #4154f1 0%, #2c3cdd 100%);
            color: #fff;
            border-radius: 20px;
            font-size: 13px;
            font-weight: 600;
            white-space: nowrap;
        }

        @media (max-width: 768px) {
            .header-content {
                grid-template-columns: 1fr;
                gap: 20px;
            }

            .header-right {
                border-left: none;
                border-top: 2px solid #e9ecef;
                padding-left: 0;
                padding-top: 20px;
            }

            .ticket-detail-title {
                font-size: 20px;
            }

            .ticket-detail-title i {
                font-size: 24px;
            }
        }

        .ticket-detail-body {
            padding: 24px 30px;
        }

        .initial-content {
            background: #f8f9fa;
            border: 1px solid #e9ecef;
            border-radius: 8px;
            padding: 24px;
            margin-bottom: 20px;
        }

        .initial-content h6 {
            color: #2c3e50;
            font-weight: 600;
            margin-bottom: 16px;
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 16px;
        }

        .initial-content h6 i {
            color: #4154f1;
        }

        /* Response Item */
        .response-item {
            background: #fff;
            border: 1px solid #e9ecef;
            border-radius: 10px;
            margin-bottom: 16px;
            border-left: 4px solid #4154f1;
            overflow: hidden;
            transition: all 0.3s ease;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
        }

        .response-item:hover {
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            transform: translateY(-2px);
        }

        .response-item.is-reply {
            margin-left: 50px;
            border-left-color: #6c757d;
            background: #f8f9fa;
            margin-bottom: 12px;
        }

        .parent-response-group {
            margin-bottom: 24px;
        }

        .replies-container {
            margin-top: 12px;
            transition: all 0.3s ease;
        }

        .replies-container.collapsed {
            display: none;
        }

        .response-header {
            padding: 18px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .response-item:not(.is-reply) .response-header {
            cursor: pointer;
            user-select: none;
        }

        .response-item:not(.is-reply) .response-header:hover {
            background: #f8f9fa;
        }

        .response-body {
            padding: 0 20px 20px 20px;
        }

        .response-body.collapsed {
            display: none;
        }

        .collapse-icon {
            transition: transform 0.3s ease;
            font-size: 20px;
            color: #6c757d;
        }

        .response-item.collapsed .collapse-icon {
            transform: rotate(-90deg);
        }

        .response-content {
            color: #495057;
            line-height: 1.7;
            white-space: pre-wrap;
            word-wrap: break-word;
            margin-bottom: 16px;
            font-size: 14px;
        }

        /* Response Actions */
        .response-actions {
            margin-top: 16px;
            display: flex;
            gap: 8px;
            align-items: center;
        }

        .btn-reply {
            font-size: 13px;
            padding: 8px 16px;
            border-radius: 6px;
            transition: all 0.2s ease;
        }

        .btn-reply:hover {
            transform: translateY(-1px);
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.1);
        }

        /* Reply Form */
        .reply-form {
            margin-top: 16px;
            padding: 18px;
            background: #f8f9fa;
            border: 2px dashed #dee2e6;
            border-radius: 8px;
            display: none;
        }

        .reply-form.active {
            display: block;
            animation: slideDown 0.3s ease;
        }

        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Response Author */
        .response-author {
            display: flex;
            align-items: center;
            gap: 14px;
        }

        .response-author-avatar {
            width: 44px;
            height: 44px;
            border-radius: 50%;
            background: linear-gradient(135deg, #4154f1 0%, #2c3cdd 100%);
            color: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 15px;
            flex-shrink: 0;
            box-shadow: 0 2px 8px rgba(65, 84, 241, 0.3);
        }

        .response-author-info {
            display: flex;
            flex-direction: column;
            gap: 4px;
        }

        .response-author-name {
            font-weight: 600;
            color: #2c3e50;
            font-size: 15px;
        }

        .response-author-role {
            font-size: 13px;
            color: #6c757d;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .response-time {
            font-size: 12px;
            color: #6c757d;
        }

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #6c757d;
        }

        .empty-state i {
            font-size: 64px;
            color: #dee2e6;
            margin-bottom: 16px;
        }

        .empty-state p {
            font-size: 16px;
            margin: 0;
        }

        /* Status Select */
        .status-select {
            padding: 6px 12px;
            border-radius: 6px;
            border: 1px solid #dee2e6;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s;
        }

        .status-select:hover {
            border-color: #4154f1;
        }

        /* Loading Spinner */
        .loading-spinner {
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 60px 20px;
        }

        .spinner {
            width: 50px;
            height: 50px;
            border: 4px solid #f3f3f3;
            border-top: 4px solid #4154f1;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% {
                transform: rotate(0deg);
            }

            100% {
                transform: rotate(360deg);
            }
        }

        /* Card Enhancements */
        .card {
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
            border: none;
            border-radius: 12px;
            margin-bottom: 24px;
        }

        .card-title {
            font-size: 18px;
            font-weight: 600;
            color: #2c3e50;
        }

        /* Form Group */
        .form-group {
            margin-bottom: 16px;
        }

        .form-group label {
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 8px;
            display: block;
            font-size: 14px;
        }

        .form-control {
            border-radius: 8px;
            border: 1px solid #dee2e6;
            padding: 10px 14px;
            font-size: 14px;
            transition: all 0.2s;
        }

        .form-control:focus {
            border-color: #4154f1;
            box-shadow: 0 0 0 3px rgba(65, 84, 241, 0.1);
        }

        .required {
            color: #dc3545;
        }

        /* Buttons */
        .btn {
            border-radius: 6px;
            padding: 10px 20px;
            font-weight: 500;
            font-size: 14px;
            transition: all 0.2s ease;
        }

        .btn-primary {
            background: linear-gradient(135deg, #4154f1 0%, #2c3cdd 100%);
            border: none;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(65, 84, 241, 0.3);
        }

        /* Badge */
        .badge {
            padding: 6px 12px;
            border-radius: 12px;
            font-weight: 600;
            font-size: 12px;
        }
    </style>
</head>

<body>
    <?php
    require_once './views/templates/header.php';
    require_once './views/templates/sidebar.php';
    

    ?>

    <main id="main" class="main">
        <div class="pagetitle">
            <h1><i class="bi bi-ticket-detailed"></i> Chi tiết Phản hồi</h1>
            <nav>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="<?php echo URL_ROOT; ?>">Trang chủ</a></li>
                    <li class="breadcrumb-item"><a href="<?php echo URL_ROOT; ?>/reflect">Phản hồi</a></li>
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
                                    <i class="bi bi-chat-dots"></i> Các phản hồi
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
                            <h5 class="card-title"><i class="bi bi-reply"></i> Thêm phản hồi</h5>
                            <form id="responseForm" class="response-form">
                                <div class="form-group">
                                    <label>Nội dung phản hồi <span class="required">*</span></label>
                                    <textarea id="responseContent" class="form-control"
                                        placeholder="Nhập nội dung phản hồi..." required></textarea>
                                </div>
                                <div class="text-end">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="bi bi-send"></i> Gửi phản hồi
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
    require_once 'views/templates/footer.php';
    require_once 'views/templates/vendors.php';
    ?>

    <script>
        const ticketId = <?php echo $ticketId; ?>;
        const currentUserId = <?php echo $user['user_id']; ?>;
        const currentUserRole = '<?php echo $user['role']; ?>';
        let ticketData = null;

        // Initialize
        document.addEventListener('DOMContentLoaded', function() {
            loadTicketDetail();

            // Response form submit
            document.getElementById('responseForm').addEventListener('submit', async function(e) {
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
                    console.error('Ticket detail error:', data.message || 'Unknown error');
                    alert('Lỗi: ' + (data.message || 'Không thể tải chi tiết ticket'));
                    window.location.href = '<?php echo URL_ROOT; ?>/reflect';
                }
            } catch (error) {
                console.error('Error loading ticket:', error);
                alert('Có lỗi khi tải chi tiết ticket: ' + error.message);
                window.location.href = '<?php echo URL_ROOT; ?>/reflect';
            }
        }

        // Display ticket detail
        function displayTicketDetail(data) {
            const ticket = data.ticket;
            const canUpdate = currentUserRole === 'admin' || ticket.id_user == currentUserId;

            const statusOptions = canUpdate ? `
        <select class="status-select" onchange="updateStatus(this.value)">
            <option value="0" ${ticket.status == 0 ? 'selected' : ''}>Mới tạo</option>
            <option value="1" ${ticket.status == 1 ? 'selected' : ''}>Đang xử lý</option>
            <option value="2" ${ticket.status == 2 ? 'selected' : ''}>Đã giải quyết</option>
            <option value="3" ${ticket.status == 3 ? 'selected' : ''}>Đã đóng</option>
        </select>
    ` : `<span class="ticket-status-badge ${getStatusBadgeClass(ticket.status)}">${getStatusLabel(ticket.status)}</span>`;

            const deleteButton = canUpdate ? `
        <button class="btn btn-danger btn-sm" onclick="deleteTicket()">
            <i class="bi bi-trash"></i> Xóa ticket
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
                <p>Chưa có phản hồi nào</p>
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

            return `
        <div class="response-item ${replyClass}" id="response-${response.id_reflect_detail}">
            <div class="response-header" ${!isReply ? `onclick="toggleCollapse(${response.id_reflect_detail})"` : ''}>
                <div class="response-author">
                    <div class="response-author-avatar">${getInitials(response.user_name)}</div>
                    <div class="response-author-info">
                        <div class="response-author-name">${escapeHtml(response.user_name)}</div>
                        <div class="response-author-role">${getRoleLabel(response.user_role)} • ${formatDateTime(response.created_at)}</div>
                    </div>
                </div>
                ${!isReply ? `
                    <div class="d-flex align-items-center gap-2">
                        ${hasReplies ? `<span class="badge bg-secondary">${replies.length} phản hồi</span>` : ''}
                        <i class="bi bi-chevron-down collapse-icon"></i>
                    </div>
                ` : ''}
            </div>
            <div class="response-body" id="response-body-${response.id_reflect_detail}">
                <div class="response-content">${escapeHtml(response.content)}</div>
                ${canReply ? `
                    <div class="response-actions">
                        <button class="btn btn-sm btn-outline-primary btn-reply" onclick="event.stopPropagation(); toggleReplyForm(${response.id_reflect_detail})">
                            <i class="bi bi-reply"></i> Trả lời
                        </button>
                    </div>
                    <div id="reply-form-${response.id_reflect_detail}" class="reply-form">
                        <div class="form-group mb-2">
                            <textarea id="reply-content-${response.id_reflect_detail}" class="form-control" rows="3" placeholder="Nhập nội dung trả lời..."></textarea>
                        </div>
                        <div class="text-end">
                            <button class="btn btn-sm btn-secondary me-2" onclick="event.stopPropagation(); toggleReplyForm(${response.id_reflect_detail})">Hủy</button>
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
                alert('Vui lòng nhập nội dung phản hồi');
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
                    alert('Lỗi: ' + data.message);
                }
            } catch (error) {
                console.error('Error adding response:', error);
                alert('Có lỗi khi thêm phản hồi');
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
                alert('Vui lòng nhập nội dung trả lời');
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
                    alert('Lỗi: ' + data.message);
                }
            } catch (error) {
                console.error('Error submitting reply:', error);
                alert('Có lỗi khi gửi trả lời');
            }
        }

        // Update status
        async function updateStatus(newStatus) {
            if (!confirm('Bạn có chắc muốn thay đổi trạng thái?')) {
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
                    alert('Đã cập nhật trạng thái!');
                    loadTicketDetail();
                } else {
                    alert('Lỗi: ' + data.message);
                    loadTicketDetail(); // Reset dropdown
                }
            } catch (error) {
                console.error('Error updating status:', error);
                alert('Có lỗi khi cập nhật trạng thái');
                loadTicketDetail();
            }
        }

        // Delete ticket
        async function deleteTicket() {
            if (!confirm('Bạn có chắc muốn xóa ticket này? Hành động không thể hoàn tác!')) {
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
                    alert('Đã xóa ticket thành công!');
                    window.location.href = '<?php echo URL_ROOT; ?>/reflect';
                } else {
                    alert('Lỗi: ' + data.message);
                }
            } catch (error) {
                console.error('Error deleting ticket:', error);
                alert('Có lỗi khi xóa ticket');
            }
        }

        // Helper functions
        function getStatusLabel(status) {
            const labels = {
                0: 'Mới tạo',
                1: 'Đang xử lý',
                2: 'Đã giải quyết',
                3: 'Đã đóng'
            };
            return labels[status] || 'Không xác định';
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