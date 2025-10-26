<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php 
    require_once './views/templates/assets.php';
    require_once './config/db.php';
    require_once './config/helper_homework.php';
    
    // Get response_id from URL
    $response_id = isset($_GET['response_id']) ? intval($_GET['response_id']) : 0;
    
    if ($response_id <= 0) {
        header("Location: /404");
        exit;
    }
    
    // Get response with task info
    $responseData = getResponseWithTaskInfo($response_id);
    
    if (!$responseData) {
        header("Location: /404");
        exit;
    }
    
    // Check if user has permission
    $user_id = $_SESSION['user']['user_id'] ?? $_SESSION['user_id'] ?? null;
    $user_role = $_SESSION['user']['role'] ?? 'student';
    
    // Students can only view their own responses
    if ($user_role === 'student' && $responseData['user_id'] != $user_id) {
        header("Location: /403");
        exit;
    }
    
    // Get all content blocks for this response
    $contentBlocks = getContentByResponse($response_id);
    
    $task_id = $responseData['task_id'];
    $task_title = $responseData['task_title'];
    $allow_comment = $responseData['allow_comment'];
    ?>
    <title>Bình luận - <?= htmlspecialchars($task_title) ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Open+Sans:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Open Sans', sans-serif; }
        
        /* Content Image Wrapper & Comment Styles */
        .content-image-wrapper {
            position: relative;
            margin-bottom: 2rem;
            padding: 1rem;
            background: white;
            border-radius: 0.75rem;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            cursor: pointer;
            transition: all 0.2s;
        }
        
        .content-image-wrapper:hover {
            box-shadow: 0 4px 12px rgba(79, 70, 229, 0.15);
            transform: translateY(-2px);
        }
        
        .content-image-wrapper.selected {
            border: 2px solid #4f46e5;
            box-shadow: 0 4px 12px rgba(79, 70, 229, 0.25);
        }
        
        .content-image-wrapper img {
            width: 100%;
            height: auto;
            max-width: 100%;
            border-radius: 0.5rem;
            display: block;
            object-fit: contain;
        }
        
        .content-image-wrapper canvas {
            max-width: 100%;
            height: auto;
        }
        
        /* Facebook-style Comment Block */
        .fb-comment-block {
            margin-top: 1rem;
            display: none;
        }
        
        .fb-comment-block.active {
            display: block;
        }
        
        .fb-comments-list {
            max-height: 500px;
            overflow-y: auto;
            padding: 1rem;
            background: #f8f9fa;
            border-radius: 0.5rem;
        }
        
        .fb-comment-item {
            margin-bottom: 1rem;
        }
        
        .fb-comment-core {
            display: flex;
            gap: 0.75rem;
        }
        
        .fb-comment-core img {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            object-fit: cover;
            flex-shrink: 0;
        }
        
        .fb-comment-bubble {
            background: #fff;
            padding: 0.5rem 0.75rem;
            border-radius: 1rem;
            flex-grow: 1;
        }
        
        .comment-author {
            font-weight: 600;
            font-size: 0.875rem;
            color: #1f2937;
            margin-bottom: 0.25rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            flex-wrap: wrap;
        }
        
        .comment-label {
            display: inline-block;
            padding: 0.125rem 0.5rem;
            border-radius: 0.25rem;
            font-size: 0.7rem;
            font-weight: 600;
            text-transform: uppercase;
        }
        
        .label-teacher {
            background: #dbeafe;
            color: #1e40af;
        }
        
        .label-student {
            background: #dcfce7;
            color: #15803d;
        }
        
        .label-phase {
            background: #fef3c7;
            color: #92400e;
        }
        
        .comment-content {
            font-size: 0.875rem;
            color: #374151;
            line-height: 1.5;
        }
        
        .fb-comment-actions {
            display: flex;
            gap: 1rem;
            margin-top: 0.25rem;
            margin-left: 2.75rem;
            font-size: 0.75rem;
        }
        
        .fb-comment-actions button {
            color: #6b7280;
            background: none;
            border: none;
            cursor: pointer;
            padding: 0;
            font-weight: 600;
        }
        
        .fb-comment-actions button:hover {
            text-decoration: underline;
        }
        
        .fb-comment-actions .like-btn.liked {
            color: #4f46e5;
        }
        
        .comment-time {
            color: #9ca3af;
            font-size: 0.75rem;
        }
        
        .fb-comment-input-area {
            display: flex;
            gap: 0.75rem;
            padding: 1rem;
            background: white;
            border-radius: 0.5rem;
            margin-top: 1rem;
            align-items: flex-start;
        }
        
        .fb-comment-input-area img {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            object-fit: cover;
            flex-shrink: 0;
        }
        
        .fb-comment-input {
            flex-grow: 1;
            border: 1px solid #e5e7eb;
            border-radius: 1rem;
            padding: 0.5rem 0.75rem;
            font-size: 0.875rem;
            resize: vertical;
            min-height: 36px;
        }
        
        .fb-comment-btn {
            background: #4f46e5;
            color: white;
            border: none;
            padding: 0.5rem 1rem;
            border-radius: 0.5rem;
            font-size: 0.875rem;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.2s;
        }
        
        .fb-comment-btn:hover {
            background: #4338ca;
        }
        
        .fb-comment-btn:disabled {
            background: #cbd5e1;
            cursor: not-allowed;
        }
        
        .fb-replies {
            margin-left: 2.75rem;
        }
        
        .fb-reply-form {
            margin-left: 2.75rem;
        }
        
        /* Visual indicator for max depth */
        .fb-comment-item[data-depth="2"] {
            border-left: 2px solid #e5e7eb;
            padding-left: 0.5rem;
        }
        
        .fb-comment-item[data-depth="2"] .fb-comment-bubble {
            background: #f9fafb;
        }
        
        /* Comment view toggle */
        .comment-view-toggle {
            position: sticky;
            top: 80px;
            z-index: 100;
            background: white;
            padding: 1rem;
            margin-bottom: 1rem;
            border-radius: 0.5rem;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .toggle-buttons {
            display: flex;
            gap: 0.5rem;
        }
        
        .toggle-btn {
            padding: 0.5rem 1rem;
            border: 1px solid #e5e7eb;
            background: white;
            border-radius: 0.375rem;
            cursor: pointer;
            font-size: 0.875rem;
            transition: all 0.2s;
        }
        
        .toggle-btn:hover {
            background: #f9fafb;
        }
        
        .toggle-btn.active {
            background: #4f46e5;
            color: white;
            border-color: #4f46e5;
        }
        
        /* View modes */
        .view-mode-individual .fb-comment-block {
            display: none;
        }
        
        .view-mode-individual .fb-comment-block.active {
            display: block;
        }
        
        .view-mode-all .fb-comment-block {
            display: block !important;
        }
        
        .view-mode-all .content-image-wrapper {
            cursor: default;
        }
    </style>
</head>

<body>
    <?php
    require_once './views/templates/header.php';
    if (isset($_SESSION['user']['role']) && $_SESSION['user']['role'] === 'teacher') {
        require_once './views/templates/sidebar_teacher.php';
    } else if(isset($_SESSION['user']['role']) && $_SESSION['user']['role'] === 'student') {
        require_once './views/templates/sidebar_student.php';
    }
    ?>

    <main id="main" class="main">
        <div class="pagetitle">
            <h1><?= htmlspecialchars($task_title) ?></h1>
            <nav>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="/class">Lớp học</a></li>
                    <li class="breadcrumb-item"><a href="?route=genAI&task_id=<?= $task_id ?>">Phân tích</a></li>
                    <li class="breadcrumb-item active">Bình luận</li>
                </ol>
            </nav>
        </div>

        <?php if (!$allow_comment): ?>
        <div class="alert alert-warning">
            <i class="bi bi-exclamation-triangle"></i>
            Giai đoạn này không cho phép bình luận.
        </div>
        <?php endif; ?>

        <?php if ($allow_comment): ?>
        <!-- Comment View Toggle -->
        <div class="comment-view-toggle">
            <div>
                <i class="bi bi-chat-dots"></i>
                <strong>Chế độ xem bình luận</strong>
            </div>
            <div class="toggle-buttons">
                <button class="toggle-btn active" data-view="individual">
                    <i class="bi bi-file-earmark-text"></i> Theo từng content
                </button>
                <button class="toggle-btn" data-view="all">
                    <i class="bi bi-list-ul"></i> Hiển thị tất cả
                </button>
            </div>
        </div>
        <?php endif; ?>

        <section class="section view-mode-individual" id="comment-container">
            <div id="content-blocks-container">
                <?php foreach ($contentBlocks as $index => $block): ?>
                <div class="content-image-wrapper" data-content-id="<?= $block['content_id'] ?>" data-order="<?= $block['order'] ?>">
                    <!-- Image will be generated here -->
                    <canvas id="canvas-<?= $block['content_id'] ?>" style="display:none;"></canvas>
                    <img id="img-<?= $block['content_id'] ?>" src="" alt="Content Block <?= $index + 1 ?>">
                    
                    <?php if ($allow_comment): ?>
                    <!-- Comment Block -->
                    <div class="fb-comment-block" id="comment-block-<?= $block['content_id'] ?>">
                        <!-- Comments List -->
                        <div class="fb-comments-list" id="comments-list-<?= $block['content_id'] ?>">
                            <div class="text-center text-slate-400 text-sm py-2">Đang tải bình luận...</div>
                        </div>
                        
                        <!-- Comment Input -->
                        <div class="fb-comment-input-area">
                            <img src="https://i.pinimg.com/564x/92/26/5c/92265c40c8e428122e0b32adc1994594.jpg" alt="Avatar">
                            <textarea 
                                class="fb-comment-input" 
                                placeholder="Viết bình luận..."
                                data-content-id="<?= $block['content_id'] ?>"
                                rows="1"></textarea>
                            <button class="fb-comment-btn" data-content-id="<?= $block['content_id'] ?>">Gửi</button>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>
        </section>

    </main>

    <?php
    require_once 'views/templates/footer.php';
    require_once 'views/templates/vendors.php';
    ?>

    <script>
        // PHP data
        const RESPONSE_ID = <?= $response_id ?>;
        const TASK_ID = <?= $task_id ?>;
        const ALLOW_COMMENT = <?= $allow_comment ? 'true' : 'false' ?>;
        const USER_AVATAR = 'https://i.pinimg.com/564x/92/26/5c/92265c40c8e428122e0b32adc1994594.jpg';
        const USER_NAME = <?= json_encode($_SESSION['user']['fullname'] ?? 'User') ?>;
        
        // Content blocks data from PHP
        const contentBlocksData = <?= json_encode($contentBlocks) ?>;
        
        let selectedBlockId = null;
        
        // Initialize when DOM is ready
        document.addEventListener('DOMContentLoaded', function() {
            // Generate images for all content blocks
            generateAllContentImages();
            
            // Setup click handlers for content blocks
            setupContentBlockHandlers();
            
            // Setup view mode toggle
            setupViewModeToggle();
            
            // Load comments for all blocks
            if (ALLOW_COMMENT) {
                loadAllComments();
            }
        });
        
        // === Text to Image Conversion ===
        function textToImage(text, maxWidth = 800, options = {}) {
            const fontSize = options.fontSize || 18;
            const fontFamily = options.fontFamily || '"Open Sans", Arial, sans-serif';
            const padding = options.padding || 50;
            const lineHeight = options.lineHeight || 1.8;
            const bgColor = options.bgColor || '#ffffff';
            const textColor = options.textColor || '#1f2937';
            
            const canvas = document.createElement('canvas');
            const ctx = canvas.getContext('2d');
            
            // Enable anti-aliasing for better text quality
            ctx.imageSmoothingEnabled = true;
            ctx.imageSmoothingQuality = 'high';
            
            // Parse styled text
            const segments = parseStyledText(text);
            
            // Measure and wrap text with improved algorithm
            const lines = wrapTextWithStyles(ctx, segments, maxWidth - padding * 2, fontSize, lineHeight, fontFamily);
            
            // Calculate canvas height with extra spacing for paragraphs
            let totalHeight = padding;
            lines.forEach(line => {
                const maxFontSize = Math.max(...line.map(seg => {
                    if (seg.style === '#') return fontSize * 1.8;
                    if (seg.style === '##') return fontSize * 1.5;
                    return fontSize;
                }));
                totalHeight += maxFontSize * lineHeight;
            });
            totalHeight += padding;
            
            canvas.width = maxWidth;
            canvas.height = totalHeight;
            
            // Fill background
            ctx.fillStyle = bgColor;
            ctx.fillRect(0, 0, canvas.width, canvas.height);
            
            // Draw text with improved rendering and text justification
            let y = padding;
            const maxLineWidth = maxWidth - padding * 2;
            
            lines.forEach((line, lineIndex) => {
                let x = padding;
                
                // Calculate line height based on largest font in line
                const maxFontSize = Math.max(...line.map(seg => {
                    if (seg.style === '#') return fontSize * 1.8;
                    if (seg.style === '##') return fontSize * 1.5;
                    return fontSize;
                }));
                
                y += maxFontSize;
                
                // Check if this is a heading or last line (don't justify)
                const isHeading = line.some(seg => seg.style === '#' || seg.style === '##');
                const isLastLine = lineIndex === lines.length - 1;
                
                // Calculate total line width without justification
                let totalLineWidth = 0;
                let spaceCount = 0;
                line.forEach(seg => {
                    ctx.font = applyStyle(seg.style, fontSize, fontFamily);
                    totalLineWidth += ctx.measureText(seg.text).width;
                    if (seg.text === ' ') spaceCount++;
                });
                
                // Calculate extra spacing for justification
                let extraSpacing = 0;
                if (!isHeading && !isLastLine && spaceCount > 0 && totalLineWidth < maxLineWidth) {
                    extraSpacing = (maxLineWidth - totalLineWidth) / spaceCount;
                }
                
                // Draw text with justification
                line.forEach(seg => {
                    ctx.font = applyStyle(seg.style, fontSize, fontFamily);
                    ctx.textBaseline = 'bottom';
                    
                    // Apply colors
                    if (seg.style === '#') {
                        ctx.fillStyle = '#1e293b'; // Darker for headings
                    } else if (seg.style === '##') {
                        ctx.fillStyle = '#334155';
                    } else if (seg.style === '**') {
                        ctx.fillStyle = '#0f172a'; // Bold text
                    } else {
                        ctx.fillStyle = textColor;
                    }
                    
                    ctx.fillText(seg.text, x, y);
                    const segmentWidth = ctx.measureText(seg.text).width;
                    
                    // Add extra spacing for justification on spaces
                    if (seg.text === ' ' && extraSpacing > 0) {
                        x += segmentWidth + extraSpacing;
                    } else {
                        x += segmentWidth;
                    }
                });
                
                // Add extra spacing after headings
                if (isHeading) {
                    y += fontSize * 0.5;
                } else {
                    y += maxFontSize * (lineHeight - 1);
                }
            });
            
            return canvas.toDataURL('image/png');
        }
        
        function parseStyledText(text) {
            const segments = [];
            
            // Split by newlines first to preserve paragraph structure
            const lines = text.split('\n');
            
            lines.forEach((line, index) => {
                if (!line.trim()) {
                    // Empty line = paragraph break
                    if (segments.length > 0) {
                        segments.push({ text: '\n', style: 'break' });
                    }
                    return;
                }
                
                // Parse line for markdown
                const lineRegex = /(\*\*.*?\*\*|##?\s[^\n]+|[^\*#\n]+)/g;
                let match;
                let hasContent = false;
                
                while ((match = lineRegex.exec(line)) !== null) {
                    const segment = match[0];
                    
                    if (segment.startsWith('**') && segment.endsWith('**')) {
                        segments.push({ text: segment.slice(2, -2), style: '**' });
                        hasContent = true;
                    } else if (segment.startsWith('## ')) {
                        segments.push({ text: segment.slice(3).trim(), style: '##' });
                        segments.push({ text: '\n', style: 'break' });
                        hasContent = true;
                    } else if (segment.startsWith('# ')) {
                        segments.push({ text: segment.slice(2).trim(), style: '#' });
                        segments.push({ text: '\n', style: 'break' });
                        hasContent = true;
                    } else if (segment.trim()) {
                        segments.push({ text: segment, style: '' });
                        hasContent = true;
                    }
                }
                
                // Add line break after each line unless it's a heading
                if (hasContent && index < lines.length - 1) {
                    const lastSeg = segments[segments.length - 1];
                    if (lastSeg && lastSeg.style !== 'break') {
                        segments.push({ text: ' ', style: '' });
                    }
                }
            });
            
            return segments;
        }
        
        function applyStyle(style, fontSize, fontFamily) {
            if (style === '#') return `bold ${Math.round(fontSize * 1.8)}px ${fontFamily}`;
            if (style === '##') return `bold ${Math.round(fontSize * 1.5)}px ${fontFamily}`;
            if (style === '**') return `bold ${fontSize}px ${fontFamily}`;
            return `${fontSize}px ${fontFamily}`;
        }
        
        function wrapTextWithStyles(ctx, segments, maxWidth, fontSize, lineHeight, fontFamily) {
            const lines = [];
            let currentLine = [];
            let currentWidth = 0;
            
            segments.forEach(seg => {
                // Handle paragraph breaks
                if (seg.style === 'break') {
                    if (currentLine.length > 0) {
                        lines.push(currentLine);
                        currentLine = [];
                        currentWidth = 0;
                    }
                    return;
                }
                
                ctx.font = applyStyle(seg.style, fontSize, fontFamily);
                
                // Split text by spaces for word wrapping
                const words = seg.text.split(' ');
                
                words.forEach((word, wordIndex) => {
                    const isLastWord = wordIndex === words.length - 1;
                    const testText = word + (isLastWord ? '' : ' ');
                    const metrics = ctx.measureText(testText);
                    const wordWidth = metrics.width;
                    
                    // Check if adding this word exceeds max width
                    if (currentWidth + wordWidth > maxWidth && currentLine.length > 0) {
                        // Start new line
                        lines.push(currentLine);
                        currentLine = [{ text: word, style: seg.style }];
                        currentWidth = ctx.measureText(word).width;
                        
                        if (!isLastWord) {
                            currentLine.push({ text: ' ', style: seg.style });
                            currentWidth += ctx.measureText(' ').width;
                        }
                    } else {
                        // Add word to current line
                        currentLine.push({ text: word, style: seg.style });
                        currentWidth += wordWidth;
                        
                        if (!isLastWord) {
                            currentLine.push({ text: ' ', style: seg.style });
                        }
                    }
                });
            });
            
            // Push last line
            if (currentLine.length > 0) {
                lines.push(currentLine);
            }
            
            return lines;
        }
        
        // Generate images for all content blocks
        function generateAllContentImages() {
            // Wait for Open Sans font to load
            document.fonts.ready.then(() => {
                contentBlocksData.forEach(block => {
                    const wrapper = document.querySelector(`[data-content-id="${block.content_id}"]`);
                    const img = document.getElementById(`img-${block.content_id}`);
                    
                    if (!img || !wrapper) return;
                    
                    // Get wrapper width for responsive image
                    const wrapperWidth = wrapper.offsetWidth - 32; // Subtract padding
                    const maxWidth = Math.min(wrapperWidth, 1200); // Max 1200px
                    
                    const imageData = textToImage(block.content, maxWidth, {
                        fontSize: 16,
                        fontFamily: 'Arial, sans-serif',
                        padding: 20,
                        lineHeight: 1.8,
                        textAlign: 'justify',
                    });
                    
                    img.src = imageData;
                    img.style.maxWidth = '100%';
                    img.style.height = 'auto';
                });
            });
        }
        
        // Regenerate images on window resize
        let resizeTimeout;
        window.addEventListener('resize', function() {
            clearTimeout(resizeTimeout);
            resizeTimeout = setTimeout(() => {
                generateAllContentImages();
            }, 300);
        });
        
        // Setup click handlers for content blocks
        function setupContentBlockHandlers() {
            const wrappers = document.querySelectorAll('.content-image-wrapper');
            
            wrappers.forEach(wrapper => {
                wrapper.addEventListener('click', function() {
                    // Only handle click in individual mode
                    const container = document.getElementById('comment-container');
                    if (container.classList.contains('view-mode-all')) {
                        return;
                    }
                    
                    const contentId = this.dataset.contentId;
                    
                    // Remove selected class from all
                    wrappers.forEach(w => {
                        w.classList.remove('selected');
                        const commentBlock = w.querySelector('.fb-comment-block');
                        if (commentBlock) commentBlock.classList.remove('active');
                    });
                    
                    // Add selected class to clicked
                    this.classList.add('selected');
                    selectedBlockId = contentId;
                    
                    // Show comment block if allowed
                    if (ALLOW_COMMENT) {
                        const commentBlock = document.getElementById(`comment-block-${contentId}`);
                        if (commentBlock) {
                            commentBlock.classList.add('active');
                        }
                    }
                });
            });
            
            // Setup comment button handlers
            if (ALLOW_COMMENT) {
                setupCommentButtons();
            }
        }
        
        // Setup view mode toggle
        function setupViewModeToggle() {
            const toggleButtons = document.querySelectorAll('.toggle-btn');
            const container = document.getElementById('comment-container');
            
            toggleButtons.forEach(btn => {
                btn.addEventListener('click', function() {
                    const viewMode = this.dataset.view;
                    
                    // Update active button
                    toggleButtons.forEach(b => b.classList.remove('active'));
                    this.classList.add('active');
                    
                    // Update container class
                    container.className = 'section';
                    if (viewMode === 'individual') {
                        container.classList.add('view-mode-individual');
                        // Reset to no selection
                        document.querySelectorAll('.content-image-wrapper').forEach(w => {
                            w.classList.remove('selected');
                        });
                        document.querySelectorAll('.fb-comment-block').forEach(b => {
                            b.classList.remove('active');
                        });
                    } else {
                        container.classList.add('view-mode-all');
                    }
                });
            });
        }
        
        // Setup comment submit buttons
        function setupCommentButtons() {
            const buttons = document.querySelectorAll('.fb-comment-btn');
            
            buttons.forEach(btn => {
                btn.addEventListener('click', async function() {
                    const contentId = this.dataset.contentId;
                    const textarea = document.querySelector(`textarea[data-content-id="${contentId}"]`);
                    const commentText = textarea.value.trim();
                    
                    if (!commentText) {
                        alert('Vui lòng nhập nội dung bình luận');
                        return;
                    }
                    
                    this.disabled = true;
                    this.textContent = 'Đang gửi...';
                    
                    try {
                        await addComment(contentId, commentText);
                        textarea.value = '';
                    } catch (error) {
                        alert('Lỗi: ' + error.message);
                    } finally {
                        this.disabled = false;
                        this.textContent = 'Gửi';
                    }
                });
            });
        }
        
        // Load comments for all content blocks
        async function loadAllComments() {
            for (const block of contentBlocksData) {
                await loadComments(block.content_id);
            }
        }
        
        // Load comments for a content block
        async function loadComments(contentId) {
            try {
                const response = await fetch('<?= URL_ROOT ?>/api/comment_actions.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        action: 'load_comments',
                        content_id: contentId
                    })
                });
                
                const result = await response.json();
                
                if (result.success) {
                    renderComments(contentId, result.comments);
                }
            } catch (error) {
                console.error('Error loading comments:', error);
            }
        }
        
        // Render comments
        function renderComments(contentId, comments) {
            const container = document.getElementById(`comments-list-${contentId}`);
            
            if (comments.length === 0) {
                container.innerHTML = '<div class="text-center text-slate-400 text-sm py-2">Chưa có bình luận nào</div>';
                return;
            }
            
            container.innerHTML = comments.map(comment => createCommentHTML(comment)).join('');
            
            // Attach event listeners
            attachCommentEventListeners(contentId);
        }
        
        // Create comment HTML
        // Create comment HTML (with depth limit and labels)
        function createCommentHTML(comment) {
            const avatar = comment.avatar || 'https://i.pinimg.com/564x/92/26/5c/92265c40c8e428122e0b32adc1994594.jpg';
            const time = new Date(comment.created_at).toLocaleString('vi-VN');
            const depth = comment.depth || 0;
            const canReply = depth < 2; // Only allow reply if depth < 2 (max 3 levels: 0, 1, 2)
            
            // Labels
            const roleLabel = comment.role_label || 'Học sinh';
            const roleClass = (comment.role_label === 'Giáo viên') ? 'label-teacher' : 'label-student';
            const phaseLabel = comment.phase_label || 'Không rõ';
            
            let html = `
                <div class="fb-comment-item" data-comment-id="${comment.comment_id}" data-depth="${depth}">
                    <div class="fb-comment-core">
                        <img src="${avatar}" alt="${comment.fullname}">
                        <div style="flex-grow: 1;">
                            <div class="fb-comment-bubble">
                                <div class="comment-author">
                                    <span>${comment.fullname}</span>
                                    <span class="comment-label ${roleClass}">${roleLabel}</span>
                                    <span class="comment-label label-phase">${phaseLabel}</span>
                                </div>
                                <div class="comment-content">${escapeHtml(comment.comment_text)}</div>
                            </div>
                            <div class="fb-comment-actions">
                                <span class="comment-time">${time}</span>
                                ${canReply ? `<button class="reply-btn" data-comment-id="${comment.comment_id}">Trả lời</button>` : ''}
                                <button class="delete-btn" data-comment-id="${comment.comment_id}">Xóa</button>
                            </div>
                            ${canReply ? `
                            <div class="fb-reply-form" id="reply-form-${comment.comment_id}" style="display:none;">
                                <div class="fb-comment-input-area">
                                    <img src="${USER_AVATAR}" alt="Avatar">
                                    <textarea class="fb-comment-input" placeholder="Viết trả lời..." rows="1"></textarea>
                                    <button class="fb-comment-btn submit-reply-btn" data-parent-id="${comment.comment_id}">Gửi</button>
                                </div>
                            </div>
                            ` : ''}
                        </div>
                    </div>
            `;
            
            // Render replies
            if (comment.replies && comment.replies.length > 0) {
                html += '<div class="fb-replies">';
                comment.replies.forEach(reply => {
                    html += createCommentHTML(reply);
                });
                html += '</div>';
            }
            
            html += '</div>';
            
            return html;
        }
        
        // Add comment
        async function addComment(contentId, commentText, replyToId = null) {
            const response = await fetch('<?= URL_ROOT ?>/api/comment_actions.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    action: 'add_comment',
                    content_id: contentId,
                    task_id: TASK_ID,
                    response_id: RESPONSE_ID,
                    comment_text: commentText,
                    reply_comment_id: replyToId
                })
            });
            
            const result = await response.json();
            
            if (result.success) {
                // Reload comments
                await loadComments(contentId);
            } else {
                throw new Error(result.message);
            }
        }
        
        // Attach event listeners to comment actions
        function attachCommentEventListeners(contentId) {
            const container = document.getElementById(`comments-list-${contentId}`);
            
            // Reply buttons
            container.querySelectorAll('.reply-btn').forEach(btn => {
                btn.addEventListener('click', function() {
                    const commentId = this.dataset.commentId;
                    const replyForm = document.getElementById(`reply-form-${commentId}`);
                    replyForm.style.display = replyForm.style.display === 'none' ? 'block' : 'none';
                });
            });
            
            // Delete buttons
            container.querySelectorAll('.delete-btn').forEach(btn => {
                btn.addEventListener('click', async function() {
                    if (!confirm('Bạn có chắc muốn xóa bình luận này?')) return;
                    
                    const commentId = this.dataset.commentId;
                    
                    try {
                        const response = await fetch('<?= URL_ROOT ?>/api/comment_actions.php', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json' },
                            body: JSON.stringify({
                                action: 'delete_comment',
                                comment_id: commentId
                            })
                        });
                        
                        const result = await response.json();
                        
                        if (result.success) {
                            await loadComments(contentId);
                        } else {
                            alert('Lỗi: ' + result.message);
                        }
                    } catch (error) {
                        alert('Lỗi: ' + error.message);
                    }
                });
            });
            
            // Submit reply buttons
            container.querySelectorAll('.submit-reply-btn').forEach(btn => {
                btn.addEventListener('click', async function() {
                    const parentId = this.dataset.parentId;
                    const textarea = this.previousElementSibling;
                    const replyText = textarea.value.trim();
                    
                    if (!replyText) {
                        alert('Vui lòng nhập nội dung trả lời');
                        return;
                    }
                    
                    this.disabled = true;
                    this.textContent = 'Đang gửi...';
                    
                    try {
                        await addComment(contentId, replyText, parentId);
                        textarea.value = '';
                        document.getElementById(`reply-form-${parentId}`).style.display = 'none';
                    } catch (error) {
                        alert('Lỗi: ' + error.message);
                    } finally {
                        this.disabled = false;
                        this.textContent = 'Gửi';
                    }
                });
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
