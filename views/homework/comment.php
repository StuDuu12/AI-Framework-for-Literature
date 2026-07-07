<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php
    require_once './views/templates/assets.php';
    require_once './config/db.php';
    require_once './config/helper_homework.php';
    require_once './config/helper_task.php';
    require_once './config/helper_phase.php';

    // Get parameters from URL
    $task_id = isset($_GET['task_id']) ? intval($_GET['task_id']) : 0;
    $topic_id = isset($_GET['topic_id']) ? intval($_GET['topic_id']) : 0;
    $phase_id = isset($_GET['phase_id']) ? intval($_GET['phase_id']) : 0;
    $deliver_id = isset($_GET['deliver_id']) ? intval($_GET['deliver_id']) : 0;

    // Legacy support: allow user_id parameter for backward compatibility
    $target_user_id = isset($_GET['user_id']) ? intval($_GET['user_id']) : 0;

    // Get viewing_from_task parameter - this indicates we're viewing from a different phase
    // For example: viewing Phase 1 submissions from Phase 2 review page
    $viewing_from_task_id = isset($_GET['viewing_from_task']) ? intval($_GET['viewing_from_task']) : 0;

    // Validate required parameters
    if ($task_id <= 0 || $deliver_id <= 0) {
        header("Location: /404");
        exit;
    }

    // Get current user info
    $current_user_id = $_SESSION['user']['user_id'] ?? $_SESSION['user_id'] ?? null;
    $current_user_role = $_SESSION['user']['role'] ?? 'student';

    if (!$current_user_id) {
        header("Location: /login");
        exit;
    }

    // Validate access to deliver
    if (!validateUserDeliverAccess($deliver_id, $current_user_id)) {
        header("Location: /403");
        exit;
    }

    // Get task details
    $taskInfo = getTaskDetails($task_id);
    if (!$taskInfo) {
        header("Location: /404");
        exit;
    }

    // Get phase details to check permissions
    // Use phase_id from URL if provided (for Phase 4 viewing Phase 1 data)
    $display_phase_id = isset($_GET['phase_id']) && $_GET['phase_id'] > 0 ? intval($_GET['phase_id']) : $taskInfo['phase_id'];

    // If viewing_from_task is provided, use that task's phase for context
    if ($viewing_from_task_id > 0) {
        $viewingTaskInfo = getTaskDetails($viewing_from_task_id);
        if ($viewingTaskInfo) {
            $phaseInfo = getPhaseDetails($viewingTaskInfo['phase_id']);
        } else {
            $phaseInfo = getPhaseDetails($display_phase_id);
        }
    } else {
        // Use the display_phase_id (from URL) for getting phase name
        $phaseInfo = getPhaseDetails($display_phase_id);
    }

    if (!$phaseInfo) {
        header("Location: /404");
        exit;
    }

    // Get current phase name for display (from URL phase_id, not task's phase_id)
    $currentPhaseName = $phaseInfo['m_phase_name'] ?? 'Unknown';

    // Only override topic_id and phase_id if they weren't provided in URL
    if (!isset($_GET['topic_id']) || $_GET['topic_id'] <= 0) {
        $topic_id = $phaseInfo['topic_id'] ?? 0;
    }
    if (!isset($_GET['phase_id']) || $_GET['phase_id'] <= 0) {
        $phase_id = $phaseInfo['phase_id'] ?? 0;
    }

    // If viewing from different task, use that task's ID for back link
    $back_task_id = $viewing_from_task_id > 0 ? $viewing_from_task_id : $task_id;

    // Check permissions
    // Teachers can view all students' work
    // Students can only view their own work (or assigned peer reviews - will handle later)
    // if ($current_user_role === 'student' && $target_user_id != $current_user_id) {
    //     // TODO: Check if student is assigned to review this user's work
    //     header("Location: /403");
    //     exit;
    // }
    
    // Get response based on deliver_id or legacy method
    $responseData = null;
    $display_response_id = null;
    $target_user_name = '';

    // Get response from deliver (REQUIRED)
    // Pass current_user_id to auto-create response if needed
    $responseData = getResponseFromDeliver($deliver_id, $current_user_id);

    if (!$responseData) {
        // No response yet for this deliver - show empty state
        echo "<script>alert('No submission for this deliver yet. Please complete your work first.'); window.location.href='/class/task?topic_id={$topic_id}&phase_id={$phase_id}';</script>";
        exit;
    }

    // Get deliver details to identify the owner
    $deliverDetails = getDeliverDetails($deliver_id);
    $deliver_status = 0;
    if ($deliverDetails) {
        $deliver_status = (int)($deliverDetails['status'] ?? 0);
        $target_user_id = $deliverDetails['type'] === 'user' ? $deliverDetails['value'] : $current_user_id;

        // Get user name
        $userStmt = $pdo->prepare("SELECT fullname FROM users WHERE user_id = :user_id");
        $userStmt->execute([':user_id' => $target_user_id]);
        $userData = $userStmt->fetch(PDO::FETCH_ASSOC);
        $target_user_name = $userData['fullname'] ?? 'Unknown';
    }

    // Teachers only see the student's confirmed final submission
    if ($current_user_role === 'teacher' && $deliver_status < 2) {
        echo "<script>alert('This student has not confirmed a final submission yet.'); history.back();</script>";
        exit;
    }

    $response_id = $responseData['id_response'];
    $display_response_id = $response_id;

    // Get task title and allow_comment setting
    $task_title = $taskInfo['title'];
    $allow_comment = $responseData['allow_comment'] ?? 0;

    // Get all content blocks for display (from student's response if teacher is viewing)
    $contentBlocks = getContentByResponse($display_response_id);

    // Check if there are no content blocks
    $hasNoContent = empty($contentBlocks);

    // Take Note — only in PHASE_TAKENOTE
    $phaseCode = $phaseInfo['phase_code'] ?? '';
    $isTakeNotePhase = ($phaseCode === 'PHASE_TAKENOTE');
    $studentNotes = [];
    // Use $display_response_id to fetch the target student's notes, even if viewed by teacher
    $studentNotes = getNotesByResponse($display_response_id);
    ?>
    <title data-lang-key="comment_page">Comments - <?= htmlspecialchars($task_title) ?></title>
    <link rel="stylesheet" href="<?= URL_ROOT ?>/assets/css/comment.css">
</head>

<body>
    <?php
    require_once './views/templates/header.php';
    require_once './views/templates/sidebar.php';
    ?>

    <main id="main" class="main">
        <div class="pagetitle">
            <!-- DEBUG INFO (Remove after testing) -->
            <?php if (isset($_GET['debug']) && $_GET['debug'] == '1'): ?>
                <div class="alert alert-info">
                    <strong>Debug Info:</strong><br>
                    Current User ID: <?= $current_user_id ?> (<?= $current_user_role ?>)<br>
                    Target User ID: <?= $target_user_id ?> (<?= $target_user_name ?>)<br>
                    Deliver ID: <?= $deliver_id ?><br>
                    Response ID: <?= $response_id ?><br>
                    Display Response ID: <?= $display_response_id ?><br>
                    Number of Content Blocks: <?= count($contentBlocks) ?><br>
                    <?php if (!empty($contentBlocks)): ?>
                        Content IDs: <?= implode(', ', array_column($contentBlocks, 'content_id')) ?>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
            <!-- END DEBUG INFO -->

            <div class="d-flex justify-content-between align-items-center mb-3">
                <div>
                    <?php if ($target_user_id != $current_user_id): ?>
                        <p class="text-muted mb-0">
                            <i class="bi bi-person"></i>
                            <span>Work by:</span> <strong><?= htmlspecialchars($target_user_name) ?></strong>
                        </p>
                    <?php endif; ?>
                </div>
                <?php if ($current_user_role === 'teacher'): ?>
                    <div>
                        <a href="/class/task/view?task_id=<?= $back_task_id ?>&topic_id=<?= $topic_id ?>&phase_id=<?= $phase_id ?>"
                            class="btn btn-secondary">
                            <i class="bi bi-arrow-left"></i> <span>Go Back to List</span>
                        </a>
                    </div>
                <?php endif; ?>
            </div>
            <nav>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="/class">Class</a></li>
                    <li class="breadcrumb-item"><a
                            href="/class/homework/genAI?task_id=<?= $task_id ?>&topic_id=<?= $topic_id ?>&phase_id=<?= $phase_id ?>&deliver_id=<?= $deliver_id ?>">Analysis</a>
                    </li>
                    <li class="breadcrumb-item active">Comments</li>
                </ol>
            </nav>
        </div>

        <?php if ($isTakeNotePhase): ?>
        <!-- ===== PHASE 4: KNOWLEDGE BRIDGE — Classroom Instruction ===== -->

        <div id="notes-panel">
            <!-- Header — Knowledge Bridge -->
            <div class="np-header">
                <div class="np-header-left">
                    <div class="np-icon"><i class="bi bi-diagram-3"></i></div>
                    <div>
                        <p class="np-title">Take Note</p>
                        <p class="np-subtitle">Connect AI analysis with teacher's lecture</p>
                    </div>
                </div>
                <div style="display: flex; align-items: center; gap: 1rem; position: relative; z-index: 1;">
                    <span class="np-badge" id="notes-count-badge"><?= count($studentNotes) ?> notes</span>
                </div>
            </div>

            <!-- Two-pane body -->
            <div id="np-body-content" class="">
                <div class="np-body <?= $current_user_role === 'student' ? 'np-body--dual' : 'np-body--single' ?>">
                    <?php if ($current_user_role === 'student'): ?>
                    <!-- Write pane — Teacher's Insights (Student Only) -->
                    <div class="np-write">
                        <p class="np-section-label"><i class="bi bi-mortarboard"></i> Take note from lecture</p>
                        <textarea id="note-input" class="np-textarea"
                            placeholder="Write down the deep analysis from the teacher that AI may have missed...&#10;&#10;For example:&#10;• Historical and cultural context added by the teacher&#10;• Subtle feelings that the algorithm did not capture&#10;• Connections between the work and real life&#10;• Extended questions from the discussion"></textarea>
                        <button id="save-note-btn" class="np-save-btn">
                            <i class="bi bi-plus-circle"></i> Add note
                        </button>
                        <span id="note-save-status" class="np-status"></span>
                        <p class="np-hint"><i class="bi bi-keyboard"></i> Ctrl + Enter to save</p>
                    </div>
                    <?php endif; ?>

                <!-- Read pane — Bridge Journal -->
                <div class="np-read">
                    <p class="np-section-label"><i class="bi bi-link-45deg"></i> Bridge Journal <?= $current_user_role === 'teacher' ? '(Student\'s)' : '' ?></p>
                    <div id="notes-list" class="np-items">
                        <?php foreach ($studentNotes as $i => $note): ?>
                        <div class="np-note-card" data-note-id="<?= $note['content_id'] ?>">
                            <span class="np-note-num">#<?= $i + 1 ?></span>
                            <p class="np-note-text"><?= htmlspecialchars($note['content']) ?></p>
                            <div class="np-note-footer">
                                <span class="np-note-time"><i class="bi bi-clock"></i> <?= date('d/m H:i', strtotime($note['created_at'])) ?></span>
                                <?php if ($current_user_role === 'student'): ?>
                                <button class="np-delete-btn delete-note-btn" data-note-id="<?= $note['content_id'] ?>" title="Delete">
                                    <i class="bi bi-x-lg"></i>
                                </button>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                        <?php if (empty($studentNotes)): ?>
                        <div id="notes-empty-state" class="np-empty">
                            <i class="bi bi-diagram-3 fs-2"></i>
                            <span>Notes from the live lecture<br>will appear here</span>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        </div>
        <!-- ===== END PHASE 4: KNOWLEDGE BRIDGE ===== -->
        <?php endif; ?>

        <?php if (!$allow_comment): ?>
            <div class="alert alert-warning">
                <i class="bi bi-exclamation-triangle"></i>
                <span>This phase does not allow comments.</span>
            </div>
        <?php endif; ?>

        <?php if ($hasNoContent): ?>
            <div class="alert alert-info">
                <i class="bi bi-info-circle"></i>
                <strong>The student has not submitted any content yet.</strong>
                <?php if ($current_user_role === 'teacher'): ?>
                    <br>You can leave a general comment by adding a content block or wait for the student to submit their work.
                <?php else: ?>
                    <br>Please complete your work before accessing this page.
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <?php if ($allow_comment && !$hasNoContent): ?>
            <!-- General Comment Section (Before Content Blocks) -->
            <div class="card mb-4 hidden">
                <div class="card-body">
                    <h5 class="card-title">
                        <i class="bi bi-chat-square-text-fill text-primary"></i>
                        General Comment
                    </h5>

                    <!-- General Comments List -->
                    <div class="fb-comment-section" style="display: block;">
                        <div class="fb-comments-list" id="general-comments-list">
                            <!-- General comments will be loaded here -->
                        </div>

                        <!-- Add General Comment Form -->
                        <div class="fb-comment-input-area">
                            <img src="https://i.pinimg.com/564x/92/26/5c/92265c40c8e428122e0b32adc1994594.jpg" alt="Avatar"
                                class="fb-avatar">
                            <textarea class="fb-comment-input" id="general-comment-input"
                                placeholder="Write a general comment about the work..."></textarea>
                            <button class="fb-comment-btn" id="general-comment-btn">
                                <i class="bi bi-send-fill"></i> <span>Send Comment</span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Comment View Toggle -->
            <div class="comment-view-toggle">
                <div>
                    <i class="bi bi-chat-dots"></i>
                    <strong>Comment View Mode</strong>
                    <span id="comment-live-badge" class="comment-live-badge" title="Comments refresh automatically">
                        <span class="comment-live-dot"></span> Live
                    </span>
                </div>
                <div class="toggle-buttons">
                    <button class="toggle-btn active" data-view="individual">
                        <i class="bi bi-file-earmark-text"></i> <span>View by Content</span>
                    </button>
                    <button class="toggle-btn" data-view="all">
                        <i class="bi bi-list-ul"></i> <span>Show All</span>
                    </button>
                </div>
            </div>
        <?php endif; ?>

        <section class="section view-mode-individual" id="comment-container">
            <?php if ($hasNoContent): ?>
                <div class="text-center py-5">
                    <i class="bi bi-file-earmark-x" style="font-size: 4rem; color: #6c757d;"></i>
                    <p class="text-muted mt-3">No content to display</p>
                </div>
            <?php else: ?>
                <div id="content-blocks-container">
                    <?php foreach ($contentBlocks as $index => $block): ?>
                        <div class="content-image-wrapper" data-content-id="<?= $block['content_id'] ?>"
                            data-order="<?= $block['order'] ?>">
                            <!-- Image will be generated here -->
                            <canvas id="canvas-<?= $block['content_id'] ?>" style="display:none;"></canvas>
                            <img id="img-<?= $block['content_id'] ?>" src="" alt="Content Block <?= $index + 1 ?>">

                            <?php if ($allow_comment): ?>
                                <!-- Comment Block -->
                                <div class="fb-comment-block" id="comment-block-<?= $block['content_id'] ?>">
                                    <!-- Comments List -->
                                    <div class="fb-comments-list" id="comments-list-<?= $block['content_id'] ?>">
                                        <div class="text-center text-slate-400 text-sm py-2">Loading comments...</div>
                                    </div>

                                    <!-- Comment Input -->
                                    <div class="fb-comment-input-area">
                                        <img src="https://i.pinimg.com/564x/92/26/5c/92265c40c8e428122e0b32adc1994594.jpg"
                                            alt="Avatar">
                                        <textarea class="fb-comment-input" placeholder="Write comments..."
                                            data-content-id="<?= $block['content_id'] ?>" rows="1"></textarea>
                                        <button class="fb-comment-btn" data-content-id="<?= $block['content_id'] ?>">Send</button>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
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
        const TARGET_USER_ID = <?= $target_user_id ?>;
        const CURRENT_USER_ID = <?= $current_user_id ?>;
        const CURRENT_USER_ROLE = <?= json_encode($current_user_role) ?>;
        const ALLOW_COMMENT = <?= $allow_comment ? 'true' : 'false' ?>;
        const IS_TAKE_NOTE_PHASE = <?= $isTakeNotePhase ? 'true' : 'false' ?>;
        const USER_AVATAR = 'https://i.pinimg.com/564x/92/26/5c/92265c40c8e428122e0b32adc1994594.jpg';
        const USER_NAME = <?= json_encode($_SESSION['user']['fullname'] ?? 'User') ?>;
        const TARGET_USER_NAME = <?= json_encode($target_user_name) ?>;

        // Viewing context - if we're viewing from a different phase (e.g., Phase 2 viewing Phase 1 data)
        const VIEWING_FROM_TASK_ID = <?= $viewing_from_task_id > 0 ? $viewing_from_task_id : 'null' ?>;
        // Use viewing task for new comments if provided, otherwise use original task
        const COMMENT_TASK_ID = VIEWING_FROM_TASK_ID || TASK_ID;
        const DELIVER_ID = <?= (int)$deliver_id ?>;
        // Current phase name for badge display
        const CURRENT_PHASE_NAME = <?= json_encode($currentPhaseName) ?>;

        // Content blocks data from PHP
        const contentBlocksData = <?= json_encode($contentBlocks) ?>;

        let selectedBlockId = null;

        // Initialize when DOM is ready
        document.addEventListener('DOMContentLoaded', function () {
            // Generate images for all content blocks
            generateAllContentImages(); // Setup click handlers for content blocks
            setupContentBlockHandlers();

            // Setup view mode toggle
            setupViewModeToggle();

            // Load comments for all blocks
            if (ALLOW_COMMENT) {
                loadAllComments();
                setupGeneralCommentButton();
                startCommentPolling();
            }

            if (IS_TAKE_NOTE_PHASE) {
                setupNotesHandlers();
                startNotesPolling();
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

                // Check if this is a heading
                const isHeading = line.some(seg => seg.style === '#' || seg.style === '##');

                // Draw text with normal spacing (NO justification)
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

                    // Move x position by exact width (no extra spacing)
                    x += segmentWidth;
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
                        segments.push({
                            text: '\n',
                            style: 'break'
                        });
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
                        segments.push({
                            text: segment.slice(2, -2),
                            style: '**'
                        });
                        hasContent = true;
                    } else if (segment.startsWith('## ')) {
                        segments.push({
                            text: segment.slice(3).trim(),
                            style: '##'
                        });
                        segments.push({
                            text: '\n',
                            style: 'break'
                        });
                        hasContent = true;
                    } else if (segment.startsWith('# ')) {
                        segments.push({
                            text: segment.slice(2).trim(),
                            style: '#'
                        });
                        segments.push({
                            text: '\n',
                            style: 'break'
                        });
                        hasContent = true;
                    } else if (segment.trim()) {
                        segments.push({
                            text: segment,
                            style: ''
                        });
                        hasContent = true;
                    }
                }

                // Add line break after each line unless it's a heading
                if (hasContent && index < lines.length - 1) {
                    const lastSeg = segments[segments.length - 1];
                    if (lastSeg && lastSeg.style !== 'break') {
                        segments.push({
                            text: ' ',
                            style: ''
                        });
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
                        currentLine = [{
                            text: word,
                            style: seg.style
                        }];
                        currentWidth = ctx.measureText(word).width;

                        if (!isLastWord) {
                            currentLine.push({
                                text: ' ',
                                style: seg.style
                            });
                            currentWidth += ctx.measureText(' ').width;
                        }
                    } else {
                        // Add word to current line
                        currentLine.push({
                            text: word,
                            style: seg.style
                        });
                        currentWidth += wordWidth;

                        if (!isLastWord) {
                            currentLine.push({
                                text: ' ',
                                style: seg.style
                            });
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
        window.addEventListener('resize', function () {
            clearTimeout(resizeTimeout);
            resizeTimeout = setTimeout(() => {
                generateAllContentImages();
            }, 300);
        });

        // Setup click handlers for content blocks
        function setupContentBlockHandlers() {
            const wrappers = document.querySelectorAll('.content-image-wrapper');

            wrappers.forEach(wrapper => {
                wrapper.addEventListener('click', function () {
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
                btn.addEventListener('click', function () {
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
                btn.addEventListener('click', async function () {
                    const contentId = this.dataset.contentId;
                    const textarea = document.querySelector(`textarea[data-content-id="${contentId}"]`);
                    const commentText = textarea.value.trim();

                    if (!commentText) {
                        alert('Please enter a comment');
                        return;
                    }

                    this.disabled = true;
                    this.textContent = 'Sending...';

                    try {
                        await addComment(contentId, commentText);
                        textarea.value = '';
                    } catch (error) {
                        alert('Error: ' + error.message);
                    } finally {
                        this.disabled = false;
                        this.textContent = 'Send';
                    }
                });
            });
        }

        // ========== GENERAL COMMENTS FUNCTIONS ==========

        async function loadGeneralComments() {
            await pollAllComments(true);
        }

        // Setup general comment button
        function setupGeneralCommentButton() {
            const btn = document.getElementById('general-comment-btn');
            const textarea = document.getElementById('general-comment-input');

            if (!btn || !textarea) return;

            btn.addEventListener('click', async function () {
                const commentText = textarea.value.trim();

                if (!commentText) {
                    alert('Please enter comment content');
                    return;
                }

                this.disabled = true;
                this.innerHTML = '<i class="bi bi-hourglass-split"></i> Sending...';

                try {
                    await addGeneralComment(commentText);
                    textarea.value = '';
                } catch (error) {
                    alert('Error: ' + error.message);
                } finally {
                    this.disabled = false;
                    this.innerHTML = '<i class="bi bi-send-fill"></i> Send Comment';
                }
            });
        }

        // Add general comment
        async function addGeneralComment(commentText, replyToId = null) {
            isSubmittingComment = true;
            try {
                const response = await fetch('<?= URL_ROOT ?>/api/comment_actions.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        action: 'add_comment',
                        content_id: null,
                        task_id: COMMENT_TASK_ID,
                        response_id: RESPONSE_ID,
                        comment_text: commentText,
                        reply_comment_id: replyToId
                    })
                });

                const result = await response.json();

                if (result.success) {
                    await pollAllComments(true);
                } else {
                    throw new Error(result.message);
                }
            } finally {
                isSubmittingComment = false;
            }
        }

        // Attach event listeners to general comment actions
        function attachGeneralCommentEventListeners() {
            const container = document.getElementById('general-comments-list');

            // Reply buttons
            container.querySelectorAll('.reply-btn').forEach(btn => {
                btn.addEventListener('click', function () {
                    const commentId = this.dataset.commentId;
                    const replyForm = document.getElementById(`reply-form-${commentId}`);
                    replyForm.style.display = replyForm.style.display === 'none' ? 'block' : 'none';
                });
            });

            // Delete buttons
            container.querySelectorAll('.delete-btn').forEach(btn => {
                btn.addEventListener('click', async function () {
                    if (!confirm('Are you sure you want to delete this comment?')) return;

                    const commentId = this.dataset.commentId;

                    try {
                        const response = await fetch('<?= URL_ROOT ?>/api/comment_actions.php', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json'
                            },
                            body: JSON.stringify({
                                action: 'delete_comment',
                                comment_id: commentId
                            })
                        });

                        const result = await response.json();

                        if (result.success) {
                            await pollAllComments(true);
                        } else {
                            alert('Error: ' + result.message);
                        }
                    } catch (error) {
                        alert('Error: ' + error.message);
                    }
                });
            });

            // Submit reply buttons
            container.querySelectorAll('.submit-reply-btn').forEach(btn => {
                btn.addEventListener('click', async function () {
                    const parentId = this.dataset.parentId; // ✅ Use parentId instead of commentId
                    const replyForm = document.getElementById(`reply-form-${parentId}`);
                    const textarea = replyForm.querySelector('.fb-comment-input'); // ✅ Use .fb-comment-input not .fb-reply-input
                    const replyText = textarea.value.trim();

                    if (!replyText) {
                        alert('Please enter a reply.');
                        return;
                    }

                    this.disabled = true;
                    this.innerHTML = '<i class="bi bi-hourglass-split"></i> Sending...';

                    try {
                        await addGeneralComment(replyText, parentId);
                        textarea.value = '';
                        replyForm.style.display = 'none';
                    } catch (error) {
                        alert('Error: ' + error.message);
                    } finally {
                        this.disabled = false;
                        this.innerHTML = '<i class="bi bi-send-fill"></i> Send';
                    }
                });
            });
        }

        // ========== END GENERAL COMMENTS FUNCTIONS ==========

        let commentPollTimer = null;
        let notesPollTimer = null;
        const commentFingerprints = {};
        let generalCommentsFingerprint = '';
        let notesFingerprint = '';
        const COMMENT_POLL_MS = 4000;
        const NOTES_POLL_MS = 4000;
        let isSubmittingComment = false;

        function countAllComments(comments) {
            let total = comments.length;
            comments.forEach((c) => {
                if (c.replies && c.replies.length) {
                    total += countAllComments(c.replies);
                }
            });
            return total;
        }

        function fingerprintComments(comments) {
            const parts = [];
            const walk = (items) => {
                items.forEach((c) => {
                    parts.push(`${c.comment_id}:${c.created_at}`);
                    if (c.replies && c.replies.length) {
                        walk(c.replies);
                    }
                });
            };
            walk(comments);
            return parts.join('|');
        }

        function getOpenReplyFormIds(container) {
            if (!container) return [];
            return Array.from(container.querySelectorAll('.fb-reply-form'))
                .filter((form) => form.style.display !== 'none')
                .map((form) => form.id);
        }

        function restoreOpenReplyForms(container, openIds) {
            openIds.forEach((id) => {
                const form = container.querySelector(`#${id}`);
                if (form) form.style.display = 'block';
            });
        }

        function renderComments(contentId, comments, force = false) {
            const container = document.getElementById(`comments-list-${contentId}`);
            if (!container) return;

            const fp = fingerprintComments(comments);
            if (!force && commentFingerprints[contentId] === fp) {
                return;
            }

            const scrollTop = container.scrollTop;
            const openReplyForms = getOpenReplyFormIds(container);

            if (comments.length === 0) {
                container.innerHTML = '<div class="text-center text-slate-400 text-sm py-2">No comments yet</div>';
            } else {
                container.innerHTML = comments.map((comment) => createCommentHTML(comment)).join('');
                attachCommentEventListeners(contentId);
                restoreOpenReplyForms(container, openReplyForms);
            }

            container.scrollTop = scrollTop;
            commentFingerprints[contentId] = fp;
        }

        function renderGeneralComments(comments, force = false) {
            const container = document.getElementById('general-comments-list');
            if (!container) return;

            const fp = fingerprintComments(comments);
            if (!force && generalCommentsFingerprint === fp) {
                return;
            }

            const scrollTop = container.scrollTop;
            const openReplyForms = getOpenReplyFormIds(container);

            if (comments.length === 0) {
                container.innerHTML = '<div class="text-center text-slate-400 text-sm py-2">No comments yet</div>';
            } else {
                container.innerHTML = comments.map((comment) => createCommentHTML(comment)).join('');
                attachGeneralCommentEventListeners();
                restoreOpenReplyForms(container, openReplyForms);
            }

            container.scrollTop = scrollTop;
            generalCommentsFingerprint = fp;
        }

        function createCommentHTML(comment) {
            const avatar = comment.avatar || USER_AVATAR;
            const time = new Date(comment.created_at).toLocaleString('vi-VN');
            const depth = comment.depth || 0;
            const canReply = depth < 2;
            const roleLabel = comment.role_label || (comment.role === 'teacher' ? 'Teacher' : 'Student');
            const roleClass = roleLabel === 'Teacher' ? 'label-teacher' : 'label-student';
            const phaseLabel = comment.phase_label || comment.m_phase_name || CURRENT_PHASE_NAME || 'Unknown';
            const canDelete = comment.user_id == CURRENT_USER_ID || CURRENT_USER_ROLE === 'teacher';

            let html = `
                <div class="fb-comment-item" data-comment-id="${comment.comment_id}" data-depth="${depth}">
                    <div class="fb-comment-core">
                        <img src="${avatar}" alt="${escapeHtml(comment.fullname || 'User')}">
                        <div style="flex-grow: 1;">
                            <div class="fb-comment-bubble">
                                <div class="comment-author">
                                    <span>${escapeHtml(comment.fullname || 'User')}</span>
                                    <span class="comment-label ${roleClass}">${escapeHtml(roleLabel)}</span>
                                    <span class="comment-label label-phase">${escapeHtml(phaseLabel)}</span>
                                </div>
                                <div class="comment-content">${escapeHtml(comment.comment_text)}</div>
                            </div>
                            <div class="fb-comment-actions">
                                <span class="comment-time">${time}</span>
                                ${canReply ? `<button type="button" class="reply-btn" data-comment-id="${comment.comment_id}">Reply</button>` : ''}
                                ${canDelete ? `<button type="button" class="delete-btn" data-comment-id="${comment.comment_id}">Delete</button>` : ''}
                            </div>
                            ${canReply ? `
                            <div class="fb-reply-form" id="reply-form-${comment.comment_id}" style="display:none;">
                                <div class="fb-comment-input-area">
                                    <img src="${USER_AVATAR}" alt="Avatar">
                                    <textarea class="fb-comment-input" placeholder="Write a reply..." rows="1"></textarea>
                                    <button type="button" class="fb-comment-btn submit-reply-btn" data-parent-id="${comment.comment_id}">Send</button>
                                </div>
                            </div>
                            ` : ''}
                        </div>
                    </div>
            `;

            if (comment.replies && comment.replies.length > 0) {
                html += '<div class="fb-replies">';
                comment.replies.forEach((reply) => {
                    html += createCommentHTML(reply);
                });
                html += '</div>';
            }

            html += '</div>';
            return html;
        }

        async function addComment(contentId, commentText, replyToId = null) {
            isSubmittingComment = true;
            try {
                const response = await fetch('<?= URL_ROOT ?>/api/comment_actions.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        action: 'add_comment',
                        content_id: contentId,
                        task_id: COMMENT_TASK_ID,
                        response_id: RESPONSE_ID,
                        comment_text: commentText,
                        reply_comment_id: replyToId
                    })
                });

                const result = await response.json();
                if (!result.success) {
                    throw new Error(result.message || 'Could not send comment');
                }

                await pollAllComments(true);
            } finally {
                isSubmittingComment = false;
            }
        }

        function attachCommentEventListeners(contentId) {
            const container = document.getElementById(`comments-list-${contentId}`);
            if (!container) return;

            container.querySelectorAll('.reply-btn').forEach((btn) => {
                btn.addEventListener('click', function () {
                    const commentId = this.dataset.commentId;
                    const replyForm = document.getElementById(`reply-form-${commentId}`);
                    if (replyForm) {
                        replyForm.style.display = replyForm.style.display === 'none' ? 'block' : 'none';
                    }
                });
            });

            container.querySelectorAll('.delete-btn').forEach((btn) => {
                btn.addEventListener('click', async function () {
                    if (!confirm('Are you sure you want to delete this comment?')) return;

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
                            await pollAllComments(true);
                        } else {
                            alert('Error: ' + result.message);
                        }
                    } catch (error) {
                        alert('Error: ' + error.message);
                    }
                });
            });

            container.querySelectorAll('.submit-reply-btn').forEach((btn) => {
                btn.addEventListener('click', async function () {
                    const parentId = this.dataset.parentId;
                    const replyForm = document.getElementById(`reply-form-${parentId}`);
                    const textarea = replyForm ? replyForm.querySelector('.fb-comment-input') : null;
                    const replyText = textarea ? textarea.value.trim() : '';

                    if (!replyText) {
                        alert('Please enter a reply.');
                        return;
                    }

                    this.disabled = true;
                    const original = this.textContent;
                    this.textContent = 'Sending...';

                    try {
                        await addComment(contentId, replyText, parentId);
                        if (textarea) textarea.value = '';
                        if (replyForm) replyForm.style.display = 'none';
                    } catch (error) {
                        alert('Error: ' + error.message);
                    } finally {
                        this.disabled = false;
                        this.textContent = original;
                    }
                });
            });
        }

        async function pollAllComments(force = false) {
            if (!ALLOW_COMMENT) return;

            const contentIds = contentBlocksData.map((b) => b.content_id);
            try {
                const response = await fetch('<?= URL_ROOT ?>/api/comment_actions.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        action: 'poll_all_comments',
                        content_ids: contentIds,
                        response_id: RESPONSE_ID,
                        deliver_id: DELIVER_ID
                    })
                });

                const result = await response.json();
                if (!result.success) return;

                Object.entries(result.content || {}).forEach(([contentId, comments]) => {
                    renderComments(parseInt(contentId, 10), comments, force);
                });

                if (Array.isArray(result.general)) {
                    renderGeneralComments(result.general, force);
                }
            } catch (error) {
                console.error('Comment poll failed:', error);
            }
        }

        async function loadComments(contentId) {
            await pollAllComments(true);
        }

        async function loadAllComments() {
            await pollAllComments(true);
        }

        function startCommentPolling() {
            if (commentPollTimer) clearInterval(commentPollTimer);
            commentPollTimer = setInterval(() => {
                if (!isSubmittingComment && !document.hidden) {
                    pollAllComments(false);
                }
            }, COMMENT_POLL_MS);
        }

        function stopCommentPolling() {
            if (commentPollTimer) {
                clearInterval(commentPollTimer);
                commentPollTimer = null;
            }
        }

        document.addEventListener('visibilitychange', () => {
            if (document.hidden) {
                stopCommentPolling();
                stopNotesPolling();
            } else {
                if (ALLOW_COMMENT) {
                    pollAllComments(false);
                    startCommentPolling();
                }
                if (IS_TAKE_NOTE_PHASE) {
                    pollNotes(false);
                    startNotesPolling();
                }
            }
        });

        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text ?? '';
            return div.innerHTML;
        }

        // ========== TAKE NOTE REAL-TIME ==========
        function fingerprintNotes(notes) {
            return notes.map((n) => `${n.content_id}:${n.created_at}`).join('|');
        }

        function renderNotes(notes, force = false) {
            const list = document.getElementById('notes-list');
            const badge = document.getElementById('notes-count-badge');
            if (!list) return;

            const fp = fingerprintNotes(notes);
            if (!force && notesFingerprint === fp) return;
            notesFingerprint = fp;

            if (badge) {
                badge.textContent = `${notes.length} note${notes.length === 1 ? '' : 's'}`;
            }

            if (!notes.length) {
                list.innerHTML = `
                    <div id="notes-empty-state" class="np-empty">
                        <i class="bi bi-diagram-3 fs-2"></i>
                        <span>Notes from the live lecture<br>will appear here</span>
                    </div>`;
                return;
            }

            list.innerHTML = notes.map((note, i) => `
                <div class="np-note-card" data-note-id="${note.content_id}">
                    <span class="np-note-num">#${i + 1}</span>
                    <p class="np-note-text">${escapeHtml(note.content)}</p>
                    <div class="np-note-footer">
                        <span class="np-note-time"><i class="bi bi-clock"></i> ${new Date(note.created_at).toLocaleString('vi-VN')}</span>
                        ${CURRENT_USER_ROLE === 'student' ? `
                        <button type="button" class="np-delete-btn delete-note-btn" data-note-id="${note.content_id}" title="Delete">
                            <i class="bi bi-x-lg"></i>
                        </button>` : ''}
                    </div>
                </div>
            `).join('');

            list.querySelectorAll('.delete-note-btn').forEach((btn) => {
                btn.addEventListener('click', () => deleteNote(btn.dataset.noteId));
            });
        }

        async function pollNotes(force = false) {
            if (!IS_TAKE_NOTE_PHASE) return;
            try {
                const response = await fetch('<?= URL_ROOT ?>/api/save_note.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        action: 'load_notes',
                        response_id: RESPONSE_ID
                    })
                });
                const result = await response.json();
                if (result.success) {
                    renderNotes(result.notes || [], force);
                }
            } catch (error) {
                console.error('Notes poll failed:', error);
            }
        }

        function startNotesPolling() {
            if (notesPollTimer) clearInterval(notesPollTimer);
            notesPollTimer = setInterval(() => {
                if (!document.hidden) {
                    pollNotes(false);
                }
            }, NOTES_POLL_MS);
        }

        function stopNotesPolling() {
            if (notesPollTimer) {
                clearInterval(notesPollTimer);
                notesPollTimer = null;
            }
        }

        async function deleteNote(noteId) {
            if (!confirm('Delete this note?')) return;
            try {
                const response = await fetch('<?= URL_ROOT ?>/api/save_note.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        action: 'delete_note',
                        content_id: parseInt(noteId, 10)
                    })
                });
                const result = await response.json();
                if (result.success) {
                    await pollNotes(true);
                } else {
                    alert(result.message || 'Could not delete note');
                }
            } catch (error) {
                alert('Error: ' + error.message);
            }
        }

        function setupNotesHandlers() {
            const saveBtn = document.getElementById('save-note-btn');
            const noteInput = document.getElementById('note-input');
            const statusEl = document.getElementById('note-save-status');
            if (!saveBtn || !noteInput || CURRENT_USER_ROLE !== 'student') return;

            const saveNote = async () => {
                const noteText = noteInput.value.trim();
                if (!noteText) {
                    if (statusEl) statusEl.textContent = 'Please enter note content.';
                    return;
                }

                saveBtn.disabled = true;
                if (statusEl) statusEl.textContent = 'Saving...';

                try {
                    const response = await fetch('<?= URL_ROOT ?>/api/save_note.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({
                            action: 'save_note',
                            response_id: RESPONSE_ID,
                            note_text: noteText
                        })
                    });
                    const result = await response.json();
                    if (!result.success) {
                        throw new Error(result.message || 'Could not save note');
                    }
                    noteInput.value = '';
                    if (statusEl) statusEl.textContent = 'Saved';
                    await pollNotes(true);
                    setTimeout(() => {
                        if (statusEl) statusEl.textContent = '';
                    }, 2000);
                } catch (error) {
                    if (statusEl) statusEl.textContent = error.message;
                } finally {
                    saveBtn.disabled = false;
                }
            };

            saveBtn.addEventListener('click', saveNote);
            noteInput.addEventListener('keydown', (e) => {
                if (e.ctrlKey && e.key === 'Enter') {
                    e.preventDefault();
                    saveNote();
                }
            });
        }
    </script>
</body>

</html>
