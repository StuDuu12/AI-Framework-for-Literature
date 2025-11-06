<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php require_once './views/templates/assets.php'; ?>
    <title>Phân tích Văn học</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= URL_ROOT ?>/assets/css/literary_analysis.css">
    <style>
        /* Enhanced textarea styling */
        .editable-content {
            font-family: 'Inter', sans-serif;
            line-height: 1.6;
            transition: all 0.2s ease;
            overflow: hidden;
            resize: none;
            min-height: 40px;
            height: auto;
            box-sizing: border-box;
        }

        .editable-title {
            font-family: 'Inter', sans-serif;
            overflow: hidden;
            resize: none;
            height: auto;
            box-sizing: border-box;
        }

        .editable-content:hover {
            border-color: #6366f1;
            box-shadow: 0 1px 3px 0 rgba(99, 102, 241, 0.1);
        }

        .editable-content:focus {
            outline: none;
            border-color: #6366f1;
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
            resize: vertical;
        }

        .analysis-point {
            transition: all 0.2s ease;
        }

        .analysis-point:hover {
            background-color: #f8fafc;
            padding: 12px;
            margin: -12px;
            border-radius: 8px;
        }

        /* Sortable section styles */
        .sortable-section {
            position: relative;
            padding: 16px;
            margin-bottom: 16px;
            border: 2px solid transparent;
            border-radius: 8px;
            background: white;
            transition: all 0.2s ease;
        }

        .sortable-section:hover {
            border-color: #e2e8f0;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
        }

        .sortable-section.dragging {
            opacity: 0.5;
            cursor: move;
        }

        .sortable-section.drag-over {
            border-color: #6366f1;
            background-color: #eef2ff;
        }

        .section-controls {
            display: flex;
            gap: 8px;
            margin-bottom: 8px;
            opacity: 0;
            transition: opacity 0.2s ease;
        }

        .sortable-section:hover .section-controls {
            opacity: 1;
        }

        .drag-handle {
            cursor: move;
            padding: 4px 8px;
            background: #f1f5f9;
            border: 1px solid #cbd5e1;
            border-radius: 4px;
            font-size: 12px;
            color: #64748b;
            user-select: none;
        }

        .drag-handle:hover {
            background: #e2e8f0;
            color: #475569;
        }

        .delete-section-btn {
            padding: 4px 8px;
            background: #fee2e2;
            border: 1px solid #fca5a5;
            border-radius: 4px;
            font-size: 12px;
            color: #dc2626;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .delete-section-btn:hover {
            background: #fecaca;
            border-color: #f87171;
        }

        .add-section-btn {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 8px 16px;
            background: #dbeafe;
            border: 1px dashed #60a5fa;
            border-radius: 6px;
            font-size: 14px;
            color: #2563eb;
            cursor: pointer;
            transition: all 0.2s ease;
            margin: 16px 0;
        }

        .add-section-btn:hover {
            background: #bfdbfe;
            border-color: #3b82f6;
        }
    </style>
</head>

<body>
    <?php
    // Get task information from URL parameters
    require_once __DIR__ . '/../../config/helper_homework.php';
    require_once __DIR__ . '/../../config/helper_task.php';
    require_once __DIR__ . '/../../config/db.php';

    $taskId = isset($_GET['task_id']) ? intval($_GET['task_id']) : null;
    $topicId = isset($_GET['topic_id']) ? intval($_GET['topic_id']) : null;
    $phaseId = isset($_GET['phase_id']) ? intval($_GET['phase_id']) : null;
    $deliver_id = isset($_GET['deliver_id']) ? intval($_GET['deliver_id']) : 0;

    // Validate required parameters
    if (!$taskId || !$deliver_id) {
        header("Location: /404");
        exit;
    }

    // Get current user
    $current_user_id = $_SESSION['user']['user_id'] ?? $_SESSION['user_id'] ?? null;
    if (!$current_user_id) {
        header("Location: /login");
        exit;
    }

    // Validate access to deliver
    if (!validateUserDeliverAccess($deliver_id, $current_user_id)) {
        header("Location: /403");
        exit;
    }

    $taskContent = '';
    $taskTitle = '';
    $taskInfo = null;

    // Load task content if task_id is provided
    if ($taskId) {
        $taskInfo = getInfoTask($taskId);
        if ($taskInfo) {
            $taskTitle = $taskInfo['task_title'] ?? '';
            $filePath = $taskInfo['file_content'] ?? '';

            // Read file content from uploads folder if file path exists
            if (!empty($filePath)) {
                $fullPath = __DIR__ . '/../../uploads/' . $filePath;
                if (file_exists($fullPath)) {
                    $taskContent = file_get_contents($fullPath);
                } else {
                    // Fallback: try without uploads prefix if path already includes it
                    $alternatePath = __DIR__ . '/../../' . $filePath;
                    if (file_exists($alternatePath)) {
                        $taskContent = file_get_contents($alternatePath);
                    }
                }
            }
        }
    }

    // Check if deliver has existing response
    $json_data = null;
    $id_response = null;
    $hasExistingResponse = false;
    $savedContent = null; // To store title and sections from database
    
    if ($current_user_id && $deliver_id) {
        // Get deliver info to check if response exists
        $stmtDeliver = $pdo->prepare("SELECT response FROM deliver WHERE id_deliver = :deliver_id");
        $stmtDeliver->execute([':deliver_id' => $deliver_id]);
        $deliverInfo = $stmtDeliver->fetch(PDO::FETCH_ASSOC);

        if ($deliverInfo && !empty($deliverInfo['response'])) {
            // Deliver has existing response - load it
            $hasExistingResponse = true;

            // Get response data and validate access
            $responseData = getResponseFromDeliver($deliver_id, null); // Don't auto-create
    
            if ($responseData && isset($responseData['id_response'])) {
                // Additional access check for group deliveries
                $canAccess = true;

                // Get deliver type to check group membership
                $stmtDeliverType = $pdo->prepare("
                    SELECT type, value 
                    FROM deliver 
                    WHERE id_deliver = :deliver_id
                ");
                $stmtDeliverType->execute([':deliver_id' => $deliver_id]);
                $deliverTypeInfo = $stmtDeliverType->fetch(PDO::FETCH_ASSOC);

                if ($deliverTypeInfo && $deliverTypeInfo['type'] === 'group') {
                    // Check if user is member of this group
                    $stmtGroupMember = $pdo->prepare("
                        SELECT COUNT(*) as is_member
                        FROM group_member
                        WHERE group_id = :group_id AND user_id = :user_id
                    ");
                    $stmtGroupMember->execute([
                        ':group_id' => $deliverTypeInfo['value'],
                        ':user_id' => $current_user_id
                    ]);
                    $memberCheck = $stmtGroupMember->fetch(PDO::FETCH_ASSOC);
                    $canAccess = ($memberCheck['is_member'] > 0);
                }

                if ($canAccess) {
                    // User has access - load the response data
                    $json_data = json_decode($responseData['json_ai'], true);
                    $id_response = $responseData['id_response'];

                    // Load saved content (title and sections) from database
                    $contents = getContentByResponse($id_response);

                    // Extract title and sections
                    $title = '';
                    $sections = [];

                    foreach ($contents as $content) {
                        $text = $content['content'];

                        // Check if first content is title (order 0 and starts with #)
                        if ($content['order'] == 0 && strpos(trim($text), '#') === 0) {
                            $title = trim(str_replace('#', '', $text));
                        } else {
                            $sections[] = [
                                'content' => $text,
                                'order' => $content['order'],
                                'content_id' => $content['content_id']
                            ];
                        }
                    }

                    // Store for JavaScript
                    $savedContent = [
                        'title' => $title,
                        'sections' => $sections
                    ];
                } else {
                    // User doesn't have access to this group response
                    header("Location: /403");
                    exit;
                }
            }
        }
        // If no response exists, allow fresh start (don't set $hasExistingResponse)
    }

    require_once './views/templates/header.php';
    if (isset($_SESSION['user']['role']) && $_SESSION['user']['role'] === 'teacher') {
        require_once './views/templates/sidebar_teacher.php';
    } else if (isset($_SESSION['user']['role']) && $_SESSION['user']['role'] === 'student') {
        require_once './views/templates/sidebar_student.php';
    }
    ?>

    <main id="main" class="main">
        <div class="pagetitle">
            <h1>Bộ Công cụ Phân tích Văn học<?php if ($taskTitle): ?> -
                    <?= htmlspecialchars($taskTitle) ?><?php endif; ?>
            </h1>
            <nav>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="?route=class">Lớp học</a></li>
                    <?php if ($taskInfo): ?>
                        <li class="breadcrumb-item"><a
                                href="?route=class&id=<?= $taskInfo['class_id'] ?>"><?= htmlspecialchars($taskInfo['topic_name']) ?></a>
                        </li>
                    <?php endif; ?>
                    <li class="breadcrumb-item active">Phân tích Văn học</li>
                </ol>
            </nav>
        </div><!-- End Page Title -->

        <section class="section">
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 h-full">

                <!-- Left Panel: Input -->
                <div class="lg:col-span-4 h-full">
                    <div class="bg-white p-6 rounded-xl shadow-md sticky top-24">
                        <?php if ($taskInfo): ?>
                            <div class="mb-4 p-3 bg-blue-50 border border-blue-200 rounded-lg hidden">
                                <div class="flex items-start gap-2">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24"
                                        fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                        stroke-linejoin="round" class="text-blue-600 flex-shrink-0 mt-0.5">
                                        <circle cx="12" cy="12" r="10"></circle>
                                        <line x1="12" y1="16" x2="12" y2="12"></line>
                                        <line x1="12" y1="8" x2="12.01" y2="8"></line>
                                    </svg>
                                    <div class="text-sm">
                                        <p class="font-semibold text-blue-900">Bài tập: <?= htmlspecialchars($taskTitle) ?>
                                        </p>
                                        <?php if (!empty($taskInfo['task_description'])): ?>
                                            <p class="text-blue-700 mt-1"><?= htmlspecialchars($taskInfo['task_description']) ?>
                                            </p>
                                        <?php endif; ?>
                                        <p class="text-blue-600 text-xs mt-1">
                                            Giai đoạn: <?= htmlspecialchars($taskInfo['phase_name']) ?>
                                            (<?= htmlspecialchars($taskInfo['m_phase_name']) ?>)
                                        </p>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>
                        <h2 class="text-lg font-semibold mb-4 text-slate-800">💬 Trò chuyện với AI</h2>

                        <!-- Text Area for Analysis Content -->
                        <div id="analysis-input-section" class="mb-4">
                            <div class="flex items-center justify-between mb-2">
                                <label for="text-input" class="block text-sm font-medium text-slate-700">Nội dung phân
                                    tích:</label>
                                <!-- File Upload Icon -->
                                <label for="file-upload"
                                    class="cursor-pointer text-indigo-600 hover:text-indigo-700 transition-colors"
                                    title="Tải file lên">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24"
                                        fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                        stroke-linejoin="round">
                                        <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                                        <polyline points="17 8 12 3 7 8"></polyline>
                                        <line x1="12" y1="3" x2="12" y2="15"></line>
                                    </svg>
                                </label>
                                <input id="file-upload" name="file-upload" type="file" class="sr-only" accept=".txt">
                            </div>
                            <!-- File info display -->
                            <div id="file-info"
                                class="hidden mb-2 flex items-center justify-between bg-indigo-50 border border-indigo-200 rounded-lg p-2">
                                <span id="file-name" class="text-sm font-semibold text-indigo-700 truncate"></span>
                                <button id="delete-file-button" type="button"
                                    class="p-1 rounded-full text-slate-500 hover:text-red-600 hover:bg-red-100">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24"
                                        fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"
                                        stroke-linejoin="round">
                                        <line x1="18" y1="6" x2="6" y2="18"></line>
                                        <line x1="6" y1="6" x2="18" y2="18"></line>
                                    </svg>
                                </button>
                            </div>
                            <textarea id="text-input" rows="8"
                                class="shadow-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 block w-full text-sm border-slate-300 rounded-lg p-3 resize-none"
                                placeholder="Dán văn bản để phân tích..."><?= htmlspecialchars($taskContent) ?></textarea>
                        </div>

                        <!-- Chat Messages Area (Hidden initially) -->
                        <div id="chat-section" class="hidden mb-4">
                            <div id="chat-messages"
                                class="mb-4 space-y-3 max-h-[300px] overflow-y-auto border border-slate-200 rounded-lg p-3 bg-slate-50">
                                <div class="text-center text-slate-400 text-sm py-4">
                                    Đặt câu hỏi để AI giải đáp thêm...
                                </div>
                            </div>

                            <!-- Question Input -->
                            <div class="mb-4">
                                <label for="question-input" class="block text-sm font-medium text-slate-700 mb-2">Câu
                                    hỏi của bạn:</label>
                                <textarea id="question-input" rows="4"
                                    class="shadow-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 block w-full text-sm border-slate-300 rounded-lg p-3 resize-y"
                                    placeholder="Ví dụ: Hãy phân tích sâu hơn về biểu tượng..."></textarea>
                            </div>
                        </div>

                        <!-- Action Buttons -->
                        <div class="flex gap-2">
                            <!-- Analyze Button (visible before analysis) -->
                            <button id="analyze-button"
                                class="flex-1 flex justify-center items-center py-3 px-4 border border-transparent rounded-lg shadow-sm text-sm font-semibold text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-colors disabled:opacity-50 disabled:cursor-not-allowed">
                                <svg id="analyze-icon" xmlns="http://www.w3.org/2000/svg" width="18" height="18"
                                    viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                    stroke-linecap="round" stroke-linejoin="round" class="mr-2">
                                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                                    <polyline points="14 2 14 8 20 8"></polyline>
                                </svg>
                                <svg id="loading-spinner" class="animate-spin h-5 w-5 text-white hidden mr-2"
                                    xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                                        stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor"
                                        d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                                    </path>
                                </svg>
                                <span id="button-text">Phân tích</span>
                            </button>

                            <!-- Reset Button (hidden initially, shown after analysis) -->
                            <button id="reset-button" disabled
                                class="hidden flex-1 flex justify-center items-center py-3 px-4 border border-red-600 rounded-lg shadow-sm text-sm font-semibold text-red-600 bg-white hover:bg-red-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 transition-colors disabled:opacity-50 disabled:cursor-not-allowed">
                                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24"
                                    fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                    stroke-linejoin="round" class="mr-2">
                                    <polyline points="1 4 1 10 7 10"></polyline>
                                    <path d="M3.51 15a9 9 0 1 0 2.13-9.36L1 10"></path>
                                </svg>
                                <span>Làm lại</span>
                            </button>

                            <!-- Chat Button (hidden initially, shown after analysis) -->
                            <button id="chat-button" disabled
                                class="hidden flex-1 flex justify-center items-center py-3 px-4 border border-indigo-600 rounded-lg shadow-sm text-sm font-semibold text-indigo-600 bg-white hover:bg-indigo-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-colors disabled:opacity-50 disabled:cursor-not-allowed">
                                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24"
                                    fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                    stroke-linejoin="round" class="mr-2">
                                    <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path>
                                </svg>
                                <span id="chat-button-text">Hỏi AI</span>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Right Panel: Output -->
                <div class="lg:col-span-8">
                    <div id="results-container" class="bg-white rounded-xl shadow-md min-h-[calc(100vh-12rem)]">
                        <!-- Placeholder -->
                        <div id="placeholder"
                            class="text-center text-slate-500 h-full flex flex-col justify-center items-center p-8">
                            <svg xmlns="http://www.w3.org/2000/svg" width="60" height="60" viewBox="0 0 24 24"
                                fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"
                                stroke-linejoin="round" class="text-slate-400 mb-4">
                                <path
                                    d="M21.5 12c0-5.25-4.25-9.5-9.5-9.5S2.5 6.75 2.5 12s4.25 9.5 9.5 9.5c.93 0 1.84-.14 2.7-.4">
                                </path>
                                <path d="m18 16 3.5 3.5"></path>
                                <path d="m15.5 6-4 4"></path>
                                <path d="m13.5 10-2-2"></path>
                                <path d="m11.5 8 2 2"></path>
                            </svg>
                            <h3 class="text-lg font-semibold text-slate-700">Kết quả phân tích sẽ xuất hiện ở đây</h3>
                            <p class="mt-1 text-sm">Tải lên hoặc dán văn bản để bắt đầu.</p>
                        </div>

                        <!-- Results with Tabs -->
                        <div id="results-tabs" class="hidden h-full flex flex-col">
                            <div class="border-b border-slate-200">
                                <nav class="-mb-px flex space-x-6 px-8" aria-label="Tabs">
                                    <button id="tab-analysis"
                                        class="tab-button active whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">Phân
                                        tích Chi tiết</button>
                                    <button id="tab-slideshow" disabled
                                        class="hidden tab-button text-slate-500 hover:text-slate-700 whitespace-nowrap py-4 px-1 border-b-2 border-transparent font-medium text-sm disabled:opacity-50 disabled:cursor-not-allowed">Trình
                                        chiếu</button>
                                    <button id="tab-quiz" disabled
                                        class="hidden tab-button text-slate-500 hover:text-slate-700 whitespace-nowrap py-4 px-1 border-b-2 border-transparent font-medium text-sm disabled:opacity-50 disabled:cursor-not-allowed">Kiểm
                                        tra</button>
                                </nav>
                            </div>
                            <div class="flex-grow overflow-auto p-8">
                                <div id="content-analysis" class="tab-content prose-custom max-w-none"></div>
                                <div id="content-slideshow" class="tab-content hidden h-full"></div>
                                <div id="content-quiz" class="tab-content hidden"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

    </main><!-- End #main -->

    <?php
    require_once 'views/templates/footer.php';
    require_once 'views/templates/vendors.php';
    ?>

    <script>
        // DOM Elements
        const fileUpload = document.getElementById('file-upload'),
            fileNameDisplay = document.getElementById('file-name'),
            uploadPrompt = document.getElementById('upload-prompt'),
            textInput = document.getElementById('text-input'),
            questionInput = document.getElementById('question-input'),
            analyzeButton = document.getElementById('analyze-button'),
            chatButton = document.getElementById('chat-button'),
            resetButton = document.getElementById('reset-button'),
            buttonText = document.getElementById('button-text'),
            chatButtonText = document.getElementById('chat-button-text'),
            loadingSpinner = document.getElementById('loading-spinner'),
            placeholder = document.getElementById('placeholder'),
            resultsTabs = document.getElementById('results-tabs'),
            tabAnalysis = document.getElementById('tab-analysis'),
            tabSlideshow = document.getElementById('tab-slideshow'),
            tabQuiz = document.getElementById('tab-quiz'),
            contentAnalysis = document.getElementById('content-analysis'),
            contentSlideshow = document.getElementById('content-slideshow'),
            contentQuiz = document.getElementById('content-quiz'),
            fileInfo = document.getElementById('file-info'),
            deleteFileButton = document.getElementById('delete-file-button'),
            chatMessages = document.getElementById('chat-messages'),
            chatSection = document.getElementById('chat-section'),
            analysisInputSection = document.getElementById('analysis-input-section');

        const tabs = [tabAnalysis, tabSlideshow, tabQuiz];
        const contents = [contentAnalysis, contentSlideshow, contentQuiz];

        // State - Initialize from PHP
        let currentAnalysisData = <?php echo isset($json_data) ? json_encode($json_data) : 'null'; ?>;
        let conversationHistory = []; // Store chat history for refinement
        let isAnalyzed = <?php echo isset($json_data) && $json_data ? 'true' : 'false'; ?>; // Track if initial analysis is done
        let currentResponseId = <?php echo isset($id_response) ? $id_response : 'null'; ?>; // Track response ID from deliver
        let savedContent = <?php echo isset($savedContent) ? json_encode($savedContent) : 'null'; ?>; // Saved title and sections from database

        // --- Event Listeners ---
        fileUpload.addEventListener('change', handleFileUpload);
        deleteFileButton.addEventListener('click', handleDeleteFile);
        analyzeButton.addEventListener('click', handleAnalysis);
        chatButton.addEventListener('click', handleChatSubmit);
        resetButton.addEventListener('click', handleResetOrComment); // Use wrapper function
        tabs.forEach(tab => tab.addEventListener('click', handleTabClick));

        // --- Handlers ---
        function handleFileUpload(e) {
            const file = e.target.files[0];
            if (file) {
                // Validate file type - only accept .txt files
                if (!file.name.toLowerCase().endsWith('.txt')) {
                    alert('Chỉ chấp nhận file văn bản (.txt). Vui lòng chọn file khác.');
                    fileUpload.value = ''; // Reset file input
                    return;
                }

                // Display file info
                fileNameDisplay.textContent = file.name;
                fileInfo.classList.remove('hidden');

                // Read file content
                const reader = new FileReader();
                reader.onload = (event) => {
                    textInput.value = event.target.result;
                };
                reader.onerror = () => {
                    alert('Không thể đọc file. Vui lòng thử lại.');
                    fileUpload.value = '';
                    fileInfo.classList.add('hidden');
                };
                reader.readAsText(file, 'UTF-8'); // Specify UTF-8 encoding for Vietnamese text
            }
        }

        function handleDeleteFile(e) {
            e.preventDefault(); // Prevent label click from reopening file dialog
            e.stopPropagation();
            fileUpload.value = ''; // Reset the file input
            textInput.value = ''; // Clear the textarea
            fileInfo.classList.add('hidden');
            fileNameDisplay.textContent = '';
        }

        async function handleAnalysis() {
            // Check if user already has saved analysis data - prevent re-analysis
            if (currentResponseId && currentAnalysisData) {
                alert('Bạn đã có phân tích được lưu cho bài tập này. Không thể phân tích lại.');
                return;
            }

            const textToAnalyze = textInput.value.trim();
            if (!textToAnalyze) {
                alert('Vui lòng nhập văn bản hoặc tải lên một tệp tin.');
                return;
            }
            setLoadingState(true, 'analyze');

            const analysisResult = await performMainAnalysis(textToAnalyze);

            if (analysisResult) {
                currentAnalysisData = analysisResult;
                isAnalyzed = true;
                displayAnalysis(analysisResult);
                setupGeneratorTabs(); // Setup buttons for slideshow and quiz

                placeholder.classList.add('hidden');
                resultsTabs.classList.remove('hidden');
                switchTab(tabAnalysis);
                resultsTabs.querySelector('nav').classList.add('hidden');

                // Switch UI to chat mode
                switchToChatMode();

                // Add initial analysis to chat
                addChatMessage('assistant', 'Đã hoàn thành phân tích văn bản. Bạn có thể xem kết quả bên phải hoặc đặt câu hỏi để tìm hiểu thêm.');
            }

            setLoadingState(false, 'analyze');
        }

        function switchToChatMode() {
            // Make text input readonly and disabled
            textInput.setAttribute('readonly', true);
            textInput.classList.add('bg-slate-100', 'cursor-not-allowed');

            // Hide analyze button
            analyzeButton.classList.add('hidden');

            // Show chat button
            chatButton.classList.remove('hidden');
            chatButton.removeAttribute('disabled'); // Enable chat button

            // Check if user already has saved analysis data
            if (currentResponseId && currentAnalysisData) {
                // User has saved work - show "Go to Comment" button instead of "Reset"
                resetButton.classList.remove('hidden');
                resetButton.removeAttribute('disabled');
                resetButton.innerHTML = `
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24"
                        fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                        stroke-linejoin="round" class="mr-2">
                        <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path>
                    </svg>
                    <span>Chuyển sang bình luận</span>
                `;
                resetButton.classList.remove('border-red-600', 'text-red-600', 'hover:bg-red-50', 'focus:ring-red-500');
                resetButton.classList.add('border-blue-600', 'text-blue-600', 'hover:bg-blue-50', 'focus:ring-blue-500');
            } else {
                // No saved work yet - show normal "Reset" button
                resetButton.classList.remove('hidden');
                resetButton.removeAttribute('disabled'); // Enable reset button
            }

            // Show chat section
            chatSection.classList.remove('hidden');

            // Clear chat messages placeholder
            chatMessages.innerHTML = '';
        }

        // Wrapper function to handle reset or comment redirect based on state
        function handleResetOrComment() {
            if (currentResponseId && currentAnalysisData) {
                // User has saved response with data - redirect to comment
                handleCommentRedirect();
            } else {
                // No saved response or no data - allow reset
                handleReset();
            }
        }

        // Handle redirect to comment page (save first, then redirect)
        async function handleCommentRedirect() {
            try {
                // Show loading state
                resetButton.disabled = true;
                const originalHTML = resetButton.innerHTML;
                resetButton.innerHTML = `
                    <svg class="animate-spin h-5 w-5 mr-2" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    <span>Đang lưu...</span>
                `;

                // Save current analysis first
                const saveResult = await handleSaveAnalysis();

                if (!saveResult || !saveResult.success) {
                    throw new Error('Không thể lưu dữ liệu');
                }

                // Get URL parameters
                const urlParams = new URLSearchParams(window.location.search);
                const taskId = urlParams.get('task_id');
                const topicId = urlParams.get('topic_id');
                const phaseId = urlParams.get('phase_id');
                const userId = '<?= $_SESSION['user_id'] ?? '' ?>'; // Get from PHP session
                const deliverId = urlParams.get('deliver_id'); // Get from save result

                // Build comment page URL
                const commentUrl = `/class/homework/comment?task_id=${taskId}&topic_id=${topicId}&phase_id=${phaseId}&deliver_id=${deliverId}&user_id=${userId}`;

                // Redirect to comment page
                window.location.href = commentUrl;

            } catch (error) {
                console.error('Error during save and redirect:', error);
                alert('Có lỗi xảy ra khi lưu và chuyển trang. Vui lòng thử lại.');

                // Restore button
                resetButton.disabled = false;
                resetButton.innerHTML = `
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24"
                        fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                        stroke-linejoin="round" class="mr-2">
                        <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path>
                    </svg>
                    <span>Chuyển sang bình luận</span>
                `;
            }
        }

        function handleReset() {
            // Security check: prevent unauthorized reset
            if (!isAnalyzed) {
                alert('Không thể thực hiện thao tác này. Vui lòng phân tích văn bản trước.');
                return;
            }

            if (!confirm('Bạn có chắc chắn muốn làm lại? Tất cả dữ liệu phân tích sẽ bị mất.')) {
                return;
            }

            // Reset all states
            currentAnalysisData = null;
            conversationHistory = [];
            isAnalyzed = false;

            // Reset text input
            textInput.value = '';
            textInput.removeAttribute('readonly');
            textInput.classList.remove('bg-slate-100', 'cursor-not-allowed');

            // Reset question input
            if (questionInput) questionInput.value = '';

            // Hide results and show placeholder
            resultsTabs.classList.add('hidden');
            placeholder.classList.remove('hidden');

            // Reset buttons
            analyzeButton.classList.remove('hidden');
            resetButton.classList.add('hidden');
            resetButton.setAttribute('disabled', true); // Re-disable reset button
            chatButton.classList.add('hidden');
            chatButton.setAttribute('disabled', true); // Re-disable chat button

            // Hide and disable tabs
            tabSlideshow.classList.add('hidden');
            tabSlideshow.setAttribute('disabled', true);
            tabQuiz.classList.add('hidden');
            tabQuiz.setAttribute('disabled', true);

            // Hide chat section
            chatSection.classList.add('hidden');

            // Reset chat messages
            chatMessages.innerHTML = `<div class="text-center text-slate-400 text-sm py-4">Đặt câu hỏi để AI giải đáp thêm...</div>`;

            // Reset file upload
            fileUpload.value = '';
            fileInfo.classList.add('hidden');
        }

        // Handle chat submission for refinement
        async function handleChatSubmit() {
            // Security check: prevent unauthorized chat submission
            if (!isAnalyzed) {
                alert('Không thể thực hiện thao tác này. Vui lòng phân tích văn bản trước.');
                return;
            }

            const userMessage = questionInput.value.trim();
            if (!userMessage) {
                alert('Vui lòng nhập câu hỏi.');
                return;
            }

            // Add user message to chat
            addChatMessage('user', userMessage);
            questionInput.value = '';

            // Show loading state
            setLoadingState(true, 'chat');

            // Add user message to conversation history
            conversationHistory.push({
                role: 'user',
                parts: [{ text: userMessage }]
            });

            try {
                // Call refine API with full conversation history
                const response = await fetch('/api/call_api.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        action: 'refine',
                        data: JSON.stringify({
                            history: conversationHistory,
                            currentAnalysis: currentAnalysisData
                        })
                    })
                });

                if (!response.ok) throw new Error(`API call failed: ${response.status}`);
                const result = await response.json();
                if (result.error) throw new Error(result.error);

                // Add assistant response to chat
                const responseText = result.response || result.message || 'Đã xử lý yêu cầu của bạn.';
                addChatMessage('assistant', responseText);

                // Add model response to conversation history
                conversationHistory.push({
                    role: 'model',
                    parts: [{ text: responseText }]
                });

                // Update the analysis display with new information
                appendToAnalysisDisplay(userMessage, responseText);

            } catch (error) {
                console.error("Error during chat:", error);
                addChatMessage('system', 'Đã xảy ra lỗi. Vui lòng thử lại.');
            }

            setLoadingState(false, 'chat');
        }

        // Append chat response to the analysis display
        function appendToAnalysisDisplay(question, answer) {
            const analysisContent = document.getElementById('analysis-content');
            if (!analysisContent) return;

            // Create a new section for the Q&A
            const qaSection = document.createElement('div');
            qaSection.className = 'mt-8 border-t pt-6';
            qaSection.innerHTML = `
                <div class="bg-gradient-to-r from-indigo-50 to-purple-50 rounded-lg p-6 mb-4">
                    <h3 class="text-lg font-bold text-indigo-900 mb-3 flex items-center">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="mr-2">
                            <circle cx="12" cy="12" r="10"></circle>
                            <path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"></path>
                            <line x1="12" y1="17" x2="12.01" y2="17"></line>
                        </svg>
                        Câu hỏi: ${escapeHtml(question)}
                    </h3>
                    <textarea class="editable-content w-full p-3 border border-indigo-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 text-sm bg-white">${escapeHtml(answer)}</textarea>
                    <div class="mt-3 text-xs text-slate-500 flex items-center">
                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="mr-1">
                            <circle cx="12" cy="12" r="10"></circle>
                            <polyline points="12 6 12 12 16 14"></polyline>
                        </svg>
                        ${new Date().toLocaleTimeString('vi-VN')}
                    </div>
                </div>
            `;

            analysisContent.appendChild(qaSection);

            // Initialize auto-resize for the new textarea
            const newTextarea = qaSection.querySelector('.editable-content');
            if (newTextarea) {
                autoResizeTextarea(newTextarea);
                newTextarea.addEventListener('input', function () {
                    autoResizeTextarea(this);
                });
                newTextarea.addEventListener('focus', function () {
                    this.style.resize = 'vertical';
                });
                newTextarea.addEventListener('blur', function () {
                    this.style.resize = 'none';
                    autoResizeTextarea(this);
                });
            }

            // Scroll to the new section
            qaSection.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }

        // Handle save analysis changes
        async function handleSaveAnalysis() {
            try {
                // Get task_id and deliver_id from URL params
                const urlParams = new URLSearchParams(window.location.search);
                const taskId = urlParams.get('task_id');
                const deliverId = urlParams.get('deliver_id');

                if (!taskId || !deliverId) {
                    alert('Thông tin không đầy đủ. Vui lòng truy cập từ link bài tập.');
                    return;
                }

                // Collect all edited content in order
                const title = document.querySelector('.editable-title')?.value || '';
                const sectionElements = document.querySelectorAll('.sortable-section');

                const sections = [];
                sectionElements.forEach((section) => {
                    const textarea = section.querySelector('.editable-content');
                    if (textarea && textarea.value.trim()) {
                        sections.push(textarea.value.trim());
                    }
                });

                if (sections.length === 0 && !title) {
                    alert('Không có nội dung để lưu.');
                    return;
                }

                // Show loading state
                const saveButton = document.getElementById('save-analysis-button');
                const originalHTML = saveButton.innerHTML;
                saveButton.disabled = true;
                saveButton.innerHTML = `
                    <svg class="animate-spin h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    Đang lưu...
                `;

                // Prepare AI history data
                const aiHistory = {
                    analysisData: currentAnalysisData,
                    conversationHistory: conversationHistory,
                    savedAt: new Date().toISOString()
                };

                // Send to backend API
                const response = await fetch('/api/save_analysis.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        task_id: parseInt(taskId),
                        deliver_id: parseInt(deliverId),
                        title: title,
                        sections: sections,
                        ai_history: aiHistory
                    })
                });

                const result = await response.json();

                if (!response.ok || !result.success) {
                    throw new Error(result.message || 'Lưu thất bại');
                }

                // Store response_id for future reference
                if (result.response_id) {
                    currentResponseId = result.response_id;

                    // Update UI to show "Go to Comment" button if not already done
                    if (isAnalyzed) {
                        switchToChatMode();
                    }
                }

                // Show success notification
                saveButton.innerHTML = `
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <polyline points="20 6 9 17 4 12"></polyline>
                    </svg>
                    ${result.message}
                `;
                saveButton.classList.remove('bg-green-600', 'hover:bg-green-700');
                saveButton.classList.add('bg-green-700');

                setTimeout(() => {
                    saveButton.innerHTML = originalHTML;
                    saveButton.classList.add('bg-green-600', 'hover:bg-green-700');
                    saveButton.classList.remove('bg-green-700');
                    saveButton.disabled = false;
                }, 2000);

                // Return result for other functions to use
                return result;

            } catch (error) {
                console.error('Error saving analysis:', error);
                alert('Có lỗi xảy ra khi lưu: ' + error.message);

                // Restore button
                const saveButton = document.getElementById('save-analysis-button');
                saveButton.disabled = false;
                saveButton.innerHTML = `
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path>
                        <polyline points="17 21 17 13 7 13 7 21"></polyline>
                        <polyline points="7 3 7 8 15 8"></polyline>
                    </svg>
                    Lưu thay đổi
                `;

                // Return null to indicate failure
                return null;
            }
        }

        // Handle add new section
        function handleAddSection() {
            const container = document.getElementById('sortable-container');
            if (!container) return;

            const newSectionId = `section-${Date.now()}-${Math.random().toString(36).substr(2, 9)}`;
            const newSectionHTML = `<div class="sortable-section" draggable="true" data-section-id="${newSectionId}">
                <div class="section-controls">
                    <span class="drag-handle" title="Kéo để sắp xếp">
                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="display: inline; vertical-align: middle;">
                            <line x1="3" y1="12" x2="21" y2="12"></line>
                            <line x1="3" y1="6" x2="21" y2="6"></line>
                            <line x1="3" y1="18" x2="21" y2="18"></line>
                        </svg>
                        Kéo
                    </span>
                    <button class="delete-section-btn" onclick="deleteSection('${newSectionId}')" title="Xóa section này">
                        <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="display: inline; vertical-align: middle;">
                            <polyline points="3 6 5 6 21 6"></polyline>
                            <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                        </svg>
                        Xóa
                    </button>
                </div>
                <div class="mb-6">
                    <textarea class="editable-content w-full p-3 border border-slate-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 text-sm" placeholder="Nhập nội dung mới...">Tiêu đề mới:\n\nNội dung của section mới...</textarea>
                </div>
            </div>`;

            // Insert before the last element or at the end
            container.insertAdjacentHTML('beforeend', newSectionHTML);

            // Re-initialize drag and drop
            initializeDragAndDrop();

            // Initialize auto-resize for new textarea
            const newSection = container.querySelector(`[data-section-id="${newSectionId}"]`);
            const newTextarea = newSection?.querySelector('.editable-content');
            if (newTextarea) {
                autoResizeTextarea(newTextarea);
                newTextarea.addEventListener('input', function () {
                    autoResizeTextarea(this);
                });
                newTextarea.addEventListener('focus', function () {
                    this.style.resize = 'vertical';
                });
                newTextarea.addEventListener('blur', function () {
                    this.style.resize = 'none';
                    autoResizeTextarea(this);
                });
                newTextarea.addEventListener('paste', function () {
                    setTimeout(() => autoResizeTextarea(this), 0);
                });

                // Focus on new textarea
                newTextarea.focus();
                newTextarea.select();
            }

            // Scroll to new section
            newSection?.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }

        // Delete a section
        function deleteSection(sectionId) {
            if (!confirm('Bạn có chắc muốn xóa section này?')) {
                return;
            }

            const section = document.querySelector(`[data-section-id="${sectionId}"]`);
            if (section) {
                section.style.transition = 'all 0.3s ease';
                section.style.opacity = '0';
                section.style.transform = 'translateX(-20px)';

                setTimeout(() => {
                    section.remove();
                }, 300);
            }
        }

        // Initialize drag and drop functionality
        function initializeDragAndDrop() {
            const container = document.getElementById('sortable-container');
            if (!container) return;

            let draggedElement = null;

            // Add drag event listeners to all sortable sections
            const sections = container.querySelectorAll('.sortable-section');
            sections.forEach(section => {
                // Remove old listeners by cloning
                const newSection = section.cloneNode(true);
                section.parentNode.replaceChild(newSection, section);

                newSection.addEventListener('dragstart', handleDragStart);
                newSection.addEventListener('dragend', handleDragEnd);
                newSection.addEventListener('dragover', handleDragOver);
                newSection.addEventListener('drop', handleDrop);
                newSection.addEventListener('dragenter', handleDragEnter);
                newSection.addEventListener('dragleave', handleDragLeave);
            });

            function handleDragStart(e) {
                draggedElement = this;
                this.classList.add('dragging');
                e.dataTransfer.effectAllowed = 'move';
                e.dataTransfer.setData('text/html', this.innerHTML);
            }

            function handleDragEnd(e) {
                this.classList.remove('dragging');

                // Remove all drag-over classes
                container.querySelectorAll('.sortable-section').forEach(section => {
                    section.classList.remove('drag-over');
                });
            }

            function handleDragOver(e) {
                if (e.preventDefault) {
                    e.preventDefault();
                }
                e.dataTransfer.dropEffect = 'move';
                return false;
            }

            function handleDragEnter(e) {
                if (this !== draggedElement) {
                    this.classList.add('drag-over');
                }
            }

            function handleDragLeave(e) {
                this.classList.remove('drag-over');
            }

            function handleDrop(e) {
                if (e.stopPropagation) {
                    e.stopPropagation();
                }

                if (draggedElement !== this) {
                    // Get the bounding rectangle of the target
                    const rect = this.getBoundingClientRect();
                    const midpoint = rect.top + rect.height / 2;

                    // Determine if we should insert before or after
                    if (e.clientY < midpoint) {
                        // Insert before
                        this.parentNode.insertBefore(draggedElement, this);
                    } else {
                        // Insert after
                        this.parentNode.insertBefore(draggedElement, this.nextSibling);
                    }
                }

                return false;
            }
        }

        // Auto-resize textarea based on content
        function autoResizeTextarea(textarea) {
            if (!textarea) return;

            // Save current scroll position
            const scrollTop = textarea.scrollTop;

            // Reset height to get accurate scrollHeight
            textarea.style.height = '0px';
            textarea.style.height = 'auto';

            // Calculate new height based on content
            const scrollHeight = textarea.scrollHeight;
            const minHeight = 40; // Minimum height in pixels
            const newHeight = Math.max(minHeight, scrollHeight);

            // Set the new height
            textarea.style.height = newHeight + 'px';

            // Restore scroll position if needed
            if (scrollTop > 0) {
                textarea.scrollTop = scrollTop;
            }
        }

        // Initialize auto-resize for all textareas
        function initializeTextareaAutoResize() {
            const textareas = document.querySelectorAll('.editable-content, .editable-title');

            textareas.forEach(textarea => {
                // Important: Set initial height immediately
                setTimeout(() => autoResizeTextarea(textarea), 0);

                // Add input event listener for dynamic resizing
                textarea.addEventListener('input', function () {
                    autoResizeTextarea(this);
                });

                // Add focus event to allow manual resize
                textarea.addEventListener('focus', function () {
                    this.style.resize = 'vertical';
                });

                // Remove resize handle on blur and auto-fit
                textarea.addEventListener('blur', function () {
                    this.style.resize = 'none';
                    autoResizeTextarea(this);
                });

                // Handle paste event
                textarea.addEventListener('paste', function () {
                    setTimeout(() => autoResizeTextarea(this), 0);
                });
            });
        }

        function handleTabClick(e) {
            // Security check: Prevent tab switching if not analyzed
            const targetTab = e.target;
            if ((targetTab === tabSlideshow || targetTab === tabQuiz) && !isAnalyzed) {
                alert('Không thể thực hiện thao tác này. Vui lòng phân tích văn bản trước.');
                return;
            }

            switchTab(targetTab);
        }

        async function handleGenerateSlideshow() {
            // Security check: Prevent unauthorized slideshow generation
            if (!isAnalyzed || !currentAnalysisData) {
                alert('Không thể thực hiện thao tác này. Vui lòng phân tích văn bản trước.');
                return;
            }

            const btn = document.getElementById('generate-slideshow-button');
            const container = btn.parentElement;
            container.innerHTML = `<div class="loader-container">
                <svg class="animate-spin h-8 w-8 text-indigo-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
            </div>`;

            // Pass the full analysis context to the backend for summarization
            const slideshowData = await generateSlideshowContent(JSON.stringify(currentAnalysisData));
            if (slideshowData) {
                displaySlideshow(slideshowData);
            } else {
                container.innerHTML = `<p class="text-red-500">Không thể tạo trình chiếu. Vui lòng thử lại.</p>`;
            }
        }

        async function handleGenerateQuiz() {
            // Security check: Prevent unauthorized quiz generation
            if (!isAnalyzed || !currentAnalysisData) {
                alert('Không thể thực hiện thao tác này. Vui lòng phân tích văn bản trước.');
                return;
            }

            const btn = document.getElementById('generate-quiz-button');
            const container = btn.parentElement;
            container.innerHTML = `<div class="loader-container">
                <svg class="animate-spin h-8 w-8 text-indigo-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
            </div>`;
            const quizData = await generateQuizContent(JSON.stringify(currentAnalysisData));
            if (quizData) {
                currentAnalysisData.quiz = quizData.quiz; // Merge quiz data
                displayQuiz(currentAnalysisData);
            } else {
                container.innerHTML = `<p class="text-red-500">Không thể tạo câu hỏi. Vui lòng thử lại.</p>`;
            }
        }

        // --- UI Logic ---
        function setLoadingState(isLoading, mode = 'analyze') {
            if (mode === 'analyze') {
                if (isLoading) {
                    loadingSpinner.classList.remove('hidden');
                    analyzeButton.querySelector('#analyze-icon').classList.add('hidden');
                    buttonText.textContent = 'Đang phân tích...';
                    analyzeButton.disabled = true;
                    chatButton.disabled = true;
                } else {
                    loadingSpinner.classList.add('hidden');
                    analyzeButton.querySelector('#analyze-icon').classList.remove('hidden');
                    buttonText.textContent = 'Phân tích';
                    analyzeButton.disabled = false;
                    chatButton.disabled = false;
                }
            } else if (mode === 'chat') {
                if (isLoading) {
                    chatButtonText.textContent = 'Đang xử lý...';
                    chatButton.disabled = true;
                    analyzeButton.disabled = true;
                } else {
                    chatButtonText.textContent = 'Hỏi AI';
                    chatButton.disabled = false;
                    analyzeButton.disabled = false;
                }
            }
        }

        function addChatMessage(role, message) {
            // Clear placeholder on first message
            if (chatMessages.querySelector('.text-slate-400')) {
                chatMessages.innerHTML = '';
            }

            const messageDiv = document.createElement('div');
            messageDiv.className = `chat-message ${role} mb-3`;

            if (role === 'user') {
                messageDiv.innerHTML = `
                    <div class="flex justify-end">
                        <div class="bg-indigo-600 text-white px-3 py-2 rounded-lg max-w-[85%] text-sm">
                            ${escapeHtml(message)}
                        </div>
                    </div>
                `;
            } else if (role === 'assistant') {
                messageDiv.innerHTML = `
                    <div class="flex justify-start">
                        <div class="bg-white text-slate-800 px-3 py-2 rounded-lg max-w-[85%] text-sm border border-slate-200 prose prose-sm">
                            ${formatMarkdown(message)}
                        </div>
                    </div>
                `;
            } else {
                messageDiv.innerHTML = `
                    <div class="flex justify-center">
                        <div class="bg-red-100 text-red-800 px-3 py-2 rounded-lg text-xs">
                            ${escapeHtml(message)}
                        </div>
                    </div>
                `;
            }

            chatMessages.appendChild(messageDiv);
            chatMessages.scrollTop = chatMessages.scrollHeight;
        }

        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        function formatMarkdown(text) {
            // Basic markdown formatting
            return text
                .replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>')
                .replace(/\*(.*?)\*/g, '<em>$1</em>')
                .replace(/\n/g, '<br>')
                .replace(/^- (.*?)$/gm, '<li>$1</li>')
                .replace(/(<li>.*<\/li>)/s, '<ul>$1</ul>');
        }

        function switchTab(clickedTab) {
            tabs.forEach((tab, index) => {
                const content = contents[index];
                if (tab === clickedTab) {
                    tab.classList.add('active');
                    content.classList.remove('hidden');
                } else {
                    tab.classList.remove('active');
                    content.classList.add('hidden');
                }
            });
        }

        function setupGeneratorTabs() {
            contentSlideshow.innerHTML = `<div class="text-center p-8">
                <h3 class="text-lg font-semibold text-slate-700 mb-4">Tạo Trình chiếu</h3>
                <p class="text-slate-500 mb-6">Nhấn nút bên dưới để tạo một bản trình bày dựa trên kết quả phân tích.</p>
                <button id="generate-slideshow-button" class="px-6 py-3 bg-indigo-600 text-white font-semibold rounded-lg hover:bg-indigo-700">Tạo Trình chiếu</button>
            </div>`;
            contentQuiz.innerHTML = `<div class="text-center p-8">
                 <h3 class="text-lg font-semibold text-slate-700 mb-4">Tạo Bài kiểm tra</h3>
                <p class="text-slate-500 mb-6">Nhấn nút bên dưới để tạo một bài kiểm tra ngắn dựa trên nội dung văn bản.</p>
                <button id="generate-quiz-button" class="px-6 py-3 bg-indigo-600 text-white font-semibold rounded-lg hover:bg-indigo-700">Tạo Câu hỏi</button>
            </div>`;

            document.getElementById('generate-slideshow-button').addEventListener('click', handleGenerateSlideshow);
            document.getElementById('generate-quiz-button').addEventListener('click', handleGenerateQuiz);
        }

        // --- API & Analysis Functions ---

        /**
         * Makes a POST request to the local PHP backend (api/call_api.php).
         * @param {string} action - The action to perform (e.g., 'analyze', 'slideshow', 'quiz').
         * @param {string} data - The text or JSON string to send as data.
         * @returns {Promise<object|null>} The JSON response from the backend.
         */
        async function makeApiCall(action, data) {
            try {
                const response = await fetch('/api/call_api.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        action: action,
                        data: data
                    })
                });
                if (!response.ok) throw new Error(`API call failed: ${response.status}`);
                const result = await response.json();
                if (result.error) throw new Error(result.error);
                return result;
            } catch (error) {
                console.error("Error during API call:", error);
                alert('Đã xảy ra lỗi khi giao tiếp với máy chủ. Vui lòng thử lại.');
                return null;
            }
        }

        /**
         * Sends text to the backend for analysis and merges client-side stats.
         * @param {string} text - The text to analyze.
         * @returns {Promise<object|null>} The complete analysis object.
         */
        async function performMainAnalysis(text) {
            const analysis = await makeApiCall('analyze', text);
            if (analysis) {
                analysis.stats = performLinguisticAnalysis(text);
            }
            return analysis;
        }

        /**
         * Sends the full analysis context to the backend for slideshow summarization.
         * @param {string} analysisContext - The full analysis object as a JSON string.
         * @returns {Promise<object|null>} The slideshow content.
         */
        async function generateSlideshowContent(analysisContext) {
            return await makeApiCall('slideshow', analysisContext);
        }

        /**
         * Sends the full analysis context to the backend for quiz generation.
         * @param {string} analysisContext - The full analysis object as a JSON string.
         * @returns {Promise<object|null>} The quiz content.
         */
        async function generateQuizContent(analysisContext) {
            return await makeApiCall('quiz', analysisContext);
        }

        /**
         * Performs simple client-side text statistics.
         * @param {string} text - The input text.
         * @returns {object} An object with charCount, wordCount, and topWords.
         */
        function performLinguisticAnalysis(text) {
            const words = text.match(/\b\w+\b/g) || [];
            const wordCount = words.length;
            const charCount = text.length;
            const stopWords = new Set(['và', 'của', 'là', 'có', 'trong', 'được', 'cho', 'khi', 'thì', 'mà', 'với', 'như', 'đã', 'một', 'để', 'không', 'về', 'tại', 'ra', 'này', 'ấy', 'anh', 'em', 'ông', 'bà']);
            const wordFrequencies = words.reduce((map, word) => {
                const lowerWord = word.toLowerCase();
                if (!stopWords.has(lowerWord) && isNaN(lowerWord)) {
                    map[lowerWord] = (map[lowerWord] || 0) + 1;
                }
                return map;
            }, {});
            const sortedWords = Object.entries(wordFrequencies).sort((a, b) => b[1] - a[1]);
            const topWords = sortedWords.slice(0, 5);
            return {
                charCount,
                wordCount,
                topWords
            };
        }

        // --- Display Functions ---
        function displayAnalysis(result) {
            if (!result) return;

            // Helper function to create editable textarea
            const createEditableTextarea = (content, minRows = 2) => {
                if (!content) return '';
                // Strip HTML tags and decode entities for plain text editing
                const plainText = content
                    .replace(/<br\s*\/?>/gi, '\n')
                    .replace(/<\/?(strong|em|b|i|p|div|span|ul|li)>/gi, '')
                    .replace(/&nbsp;/g, ' ')
                    .replace(/&lt;/g, '<')
                    .replace(/&gt;/g, '>')
                    .replace(/&amp;/g, '&')
                    .replace(/\n{3,}/g, '\n\n') // Remove excessive newlines
                    .trim();

                // Don't set rows attribute, let CSS and JS handle height
                return `<textarea class="editable-content w-full p-3 border border-slate-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 text-sm">${escapeHtml(plainText)}</textarea>`;
            };

            // Helper function to wrap content in sortable section
            const createSortableSection = (content, sectionId = '') => {
                const id = sectionId || `section-${Date.now()}-${Math.random().toString(36).substr(2, 9)}`;
                return `<div class="sortable-section" draggable="true" data-section-id="${id}">
                    <div class="section-controls">
                        <span class="drag-handle" title="Kéo để sắp xếp">
                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="display: inline; vertical-align: middle;">
                                <line x1="3" y1="12" x2="21" y2="12"></line>
                                <line x1="3" y1="6" x2="21" y2="6"></line>
                                <line x1="3" y1="18" x2="21" y2="18"></line>
                            </svg>
                            Kéo
                        </span>
                        <button class="delete-section-btn" onclick="deleteSection('${id}')" title="Xóa section này">
                            <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="display: inline; vertical-align: middle;">
                                <polyline points="3 6 5 6 21 6"></polyline>
                                <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                            </svg>
                            Xóa
                        </button>
                    </div>
                    ${content}
                </div>`;
            };

            const formatDetailedAnalysis = (text) => {
                if (!text) return '';
                // Split by the bold title pattern, keeping the delimiter
                const parts = text.split(/(\*\*(?:.*?):\*\*)/g).filter(part => part.trim() !== '');
                let formattedHtml = '';
                for (let i = 0; i < parts.length; i += 2) {
                    const title = parts[i] ? parts[i].replace(/\*\*/g, '').trim() : '';
                    // Remove leading newline and bullet, then format newlines
                    const content = parts[i + 1] ? parts[i + 1].replace(/^\s*\\n\s*•?\s*/, '').replace(/\\n/g, '\n').trim() : '';
                    if (title && content) {
                        // Combine title and content in the same textarea
                        const combinedText = `${title}\n\n${content}`;
                        formattedHtml += createSortableSection(`<div class="analysis-point mb-4">${createEditableTextarea(combinedText, 4)}</div>`);
                    } else if (content) {
                        formattedHtml += createSortableSection(`<div class="mb-4">${createEditableTextarea(content, 2)}</div>`);
                    }
                }
                return formattedHtml;
            };

            // Create main analysis content
            let html = `<div id="analysis-content">`;

            // Save button at the top
            html += `<div class="mb-6 flex justify-between items-center sticky top-0 bg-white z-10 py-3 border-b">
                <div>
                    <h2 class="text-2xl font-bold text-slate-800">Kết quả phân tích</h2>
                    <p class="text-sm text-slate-600 mt-1">Bạn có thể chỉnh sửa, thêm mới hoặc sắp xếp lại nội dung</p>
                </div>
                <div class="flex gap-2">
                    <button id="add-section-button" class="px-4 py-2 bg-blue-600 text-white font-semibold rounded-lg hover:bg-blue-700 focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-colors flex items-center gap-2">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <line x1="12" y1="5" x2="12" y2="19"></line>
                            <line x1="5" y1="12" x2="19" y2="12"></line>
                        </svg>
                        Thêm Section
                    </button>
                    <button id="save-analysis-button" class="px-6 py-2.5 bg-green-600 text-white font-semibold rounded-lg hover:bg-green-700 focus:ring-2 focus:ring-green-500 focus:ring-offset-2 transition-colors flex items-center gap-2">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path>
                            <polyline points="17 21 17 13 7 13 7 21"></polyline>
                            <polyline points="7 3 7 8 15 8"></polyline>
                        </svg>
                        Lưu thay đổi
                    </button>
                </div>
            </div>`;

            html += `<div id="sortable-container">`;

            html += createSortableSection(`<div class="mb-6">
                <label class="block text-sm font-medium text-slate-700 mb-2">Tiêu đề</label>
                <input type="text" class="editable-title w-full p-3 border border-slate-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 text-2xl font-bold" value="${escapeHtml(result.title || 'Không có tiêu đề')}">
            </div>`, 'title-section');

            html += `<h2 class="text-xl font-bold mt-6 mb-4 text-slate-800">Đặc điểm văn bản</h2><div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">`;
            if (result.characteristics) {
                for (const [key, value] of Object.entries(result.characteristics)) {
                    const charSection = `<div class="bg-slate-50 p-3 rounded-lg border border-slate-200">
                        <label class="block text-sm font-medium text-slate-600 mb-2">${key}</label>
                        <textarea class="editable-content w-full p-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 text-sm font-semibold text-indigo-700 bg-white">${escapeHtml(value)}</textarea>
                    </div>`;
                    html += createSortableSection(charSection, `char-${key.replace(/\s+/g, '-')}`);
                }
            }
            html += `</div>`;

            html += `<h2 class="text-xl font-bold mt-6 mb-4 text-slate-800">Thống kê Ngôn ngữ</h2><div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">`;
            if (result.stats) {
                html += `<div class="bg-slate-100 p-3 rounded-lg"><strong class="block text-sm text-slate-600">Số ký tự</strong><span class="text-indigo-700 font-semibold">${result.stats.charCount.toLocaleString('vi-VN')}</span></div>`;
                html += `<div class="bg-slate-100 p-3 rounded-lg"><strong class="block text-sm text-slate-600">Số từ</strong><span class="text-indigo-700 font-semibold">${result.stats.wordCount.toLocaleString('vi-VN')}</span></div>`;
                html += `<div class="bg-slate-100 p-3 rounded-lg"><strong class="block text-sm text-slate-600">Từ nổi bật (tần suất)</strong><span class="text-indigo-700 font-semibold">${result.stats.topWords.map(w => w[0]).join(', ')}</span></div>`;
            }
            html += `</div>`;

            if (result.keywords && result.keywords.length > 0) {
                html += `<h2 class="text-xl font-bold mt-6 mb-4 text-slate-800">Từ khóa chính</h2><div class="flex flex-wrap gap-2 mb-6">`;
                result.keywords.forEach(keyword => {
                    html += `<span class="bg-indigo-100 text-indigo-800 text-sm font-medium px-3 py-1.5 rounded-full">${keyword}</span>`;
                });
                html += `</div>`;
            }

            // --- General Analysis Section ---
            if (result.generalAnalysis) {
                const ga = result.generalAnalysis;

                // Thematic Analysis within General Analysis
                if (ga.thematicAnalysis) {
                    ga.thematicAnalysis.forEach((section, index) => {
                        // Add section title to first item
                        const prefix = index === 0 ? `Phân tích Chung:\n\n` : '';
                        const combinedText = `${prefix}${section.heading}\n\n${section.content}`;
                        html += createSortableSection(`<div class="mb-6">${createEditableTextarea(combinedText, 4)}</div>`, `ga-theme-${index}`);
                    });
                }

                // Linguistic Insights within General Analysis
                if (ga.linguisticInsights) {
                    const insights = ga.linguisticInsights;

                    if (insights.motifs?.length > 0) {
                        const combinedText = `Mô-típ lặp lại:\n\n${insights.motifs.join('\n')}`;
                        html += createSortableSection(`<div class="mb-4">${createEditableTextarea(combinedText, 3)}</div>`, 'ga-motifs');
                    }
                    if (insights.symbolsAndMetaphors?.length > 0) {
                        const combinedText = `Biểu tượng & Ẩn dụ:\n\n${insights.symbolsAndMetaphors.join('\n')}`;
                        html += createSortableSection(`<div class="mb-4">${createEditableTextarea(combinedText, 3)}</div>`, 'ga-symbols');
                    }
                    if (insights.temporalElements) {
                        const combinedText = `Yếu tố Thời gian:\n\n${insights.temporalElements}`;
                        html += createSortableSection(`<div class="mb-4">${createEditableTextarea(combinedText, 2)}</div>`, 'ga-temporal');
                    }
                    if (insights.natureAndScenery) {
                        const combinedText = `Thiên nhiên & Phong cảnh:\n\n${insights.natureAndScenery}`;
                        html += createSortableSection(`<div class="mb-4">${createEditableTextarea(combinedText, 2)}</div>`, 'ga-nature');
                    }
                }
            }


            // --- Genre-Specific Analysis ---
            if (result.storyAnalysis) {
                const sa = result.storyAnalysis;
                let isFirstStorySection = true;

                if (sa.character) {
                    const prefix = isFirstStorySection ? `Phân tích Truyện:\n\n` : '';
                    isFirstStorySection = false;
                    html += `<div class="mb-6">${formatDetailedAnalysis(prefix + sa.character)}</div>`;
                }
                if (sa.space) html += `<div class="mb-6">${formatDetailedAnalysis(sa.space)}</div>`;
                if (sa.time) html += `<div class="mb-6">${formatDetailedAnalysis(sa.time)}</div>`;
                if (sa.dialogue) html += `<div class="mb-6">${formatDetailedAnalysis(sa.dialogue)}</div>`;
                if (sa.narrator) html += `<div class="mb-6">${formatDetailedAnalysis(sa.narrator)}</div>`;
                if (sa.situation) html += `<div class="mb-6">${formatDetailedAnalysis(sa.situation)}</div>`;
                if (sa.prominentDetails) html += `<div class="mb-6">${formatDetailedAnalysis(sa.prominentDetails)}</div>`;
            }

            if (result.dramaAnalysis) {
                const da = result.dramaAnalysis;
                let isFirstDramaSection = true;

                if (da.characters) {
                    const prefix = isFirstDramaSection ? `Phân tích Kịch:\n\n` : '';
                    isFirstDramaSection = false;
                    html += `<div class="mb-6">${formatDetailedAnalysis(prefix + da.characters)}</div>`;
                }
                if (da.conflict) html += `<div class="mb-6">${formatDetailedAnalysis(da.conflict)}</div>`;
                if (da.action) html += `<div class="mb-6">${formatDetailedAnalysis(da.action)}</div>`;
                if (da.structure) html += `<div class="mb-6">${formatDetailedAnalysis(da.structure)}</div>`;
                if (da.dialogue) html += `<div class="mb-6">${formatDetailedAnalysis(da.dialogue)}</div>`;
            }

            if (result.poetryAnalysis) {
                const pa = result.poetryAnalysis;
                let isFirstPoetrySection = true;

                if (pa.rhyming) {
                    const prefix = isFirstPoetrySection ? `Phân tích Thơ ca:\n\n` : '';
                    isFirstPoetrySection = false;
                    const combinedText = `${prefix}Gieo vần trong thơ:\n\n${pa.rhyming}`;
                    html += createSortableSection(`<div class="mb-6">${createEditableTextarea(combinedText, 3)}</div>`, 'pa-rhyming');
                }
                if (pa.emotionalFlow) {
                    const combinedText = `Mạch cảm xúc:\n\n${pa.emotionalFlow}`;
                    html += createSortableSection(`<div class="mb-6">${createEditableTextarea(combinedText, 3)}</div>`, 'pa-emotion');
                }
                if (pa.structure) {
                    const combinedText = `Cấu trúc bài thơ:\n\n${pa.structure}`;
                    html += createSortableSection(`<div class="mb-6">${createEditableTextarea(combinedText, 3)}</div>`, 'pa-structure');
                }
                if (pa.imagery) {
                    const combinedText = `Hình ảnh trong thơ:\n\n${pa.imagery}`;
                    html += createSortableSection(`<div class="mb-6">${createEditableTextarea(combinedText, 3)}</div>`, 'pa-imagery');
                }
                if (pa.rhythm) {
                    const combinedText = `Phân tích Nhịp điệu:\n\n${pa.rhythm}`;
                    html += createSortableSection(`<div class="mb-6">${createEditableTextarea(combinedText, 4)}</div>`, 'pa-rhythm');
                }
                if (pa.spaceTimeOrganization) {
                    const combinedText = `Tổ chức không gian & thời gian:\n\n${pa.spaceTimeOrganization}`;
                    html += createSortableSection(`<div class="mb-6">${createEditableTextarea(combinedText, 3)}</div>`, 'pa-spacetime');
                }
                if (pa.rhetoricalDevices) {
                    const combinedText = `Biện pháp tu từ:\n\n${pa.rhetoricalDevices}`;
                    html += createSortableSection(`<div class="mb-6">${createEditableTextarea(combinedText, 3)}</div>`, 'pa-rhetorical');
                }
            }

            // --- Concluding Sections ---
            if (result.concludingThoughts) {
                const ct = result.concludingThoughts;
                if (ct.lesson) {
                    const combinedText = `Bài học & Thông điệp:\n\nBài học:\n\n${ct.lesson}`;
                    html += createSortableSection(`<div class="mb-6">${createEditableTextarea(combinedText, 3)}</div>`, 'ct-lesson');
                }
                if (ct.message) {
                    const combinedText = `Thông điệp:\n\n${ct.message}`;
                    html += createSortableSection(`<div class="mb-6">${createEditableTextarea(combinedText, 3)}</div>`, 'ct-message');
                }
            }

            if (result.intertextuality) {
                const it = result.intertextuality;
                if (it.initialAnalysis) {
                    const combinedText = `Phân tích Liên văn bản:\n\n${it.initialAnalysis}`;
                    html += createSortableSection(`<div class="mb-6">${createEditableTextarea(combinedText, 3)}</div>`, 'it-initial');
                }
                if (it.comparativeText) {
                    html += `<h3 class="text-lg font-semibold mb-3 text-slate-700">So sánh với tác phẩm tương đồng: <em>${it.comparativeText.title}</em> - ${it.comparativeText.author}</h3>`;
                }
                if (it.comparison) {
                    if (it.comparison.similarities) {
                        const combinedText = `Điểm tương đồng:\n\n${it.comparison.similarities}`;
                        html += createSortableSection(`<div class="mb-6">${createEditableTextarea(combinedText, 3)}</div>`, 'it-similarities');
                    }
                    if (it.comparison.differences) {
                        const combinedText = `Điểm khác biệt/thay đổi:\n\n${it.comparison.differences}`;
                        html += createSortableSection(`<div class="mb-6">${createEditableTextarea(combinedText, 3)}</div>`, 'it-differences');
                    }
                    if (it.comparison.effects) {
                        const combinedText = `Hiệu quả:\n\n${it.comparison.effects}`;
                        html += createSortableSection(`<div class="mb-6">${createEditableTextarea(combinedText, 3)}</div>`, 'it-effects');
                    }
                }
            }

            html += `</div>`; // Close sortable-container
            html += `</div>`; // Close analysis-content

            contentAnalysis.innerHTML = html;

            // Add event listener for save button
            const saveButton = document.getElementById('save-analysis-button');
            if (saveButton) {
                saveButton.addEventListener('click', handleSaveAnalysis);
            }

            // Add event listener for add section button
            const addSectionButton = document.getElementById('add-section-button');
            if (addSectionButton) {
                addSectionButton.addEventListener('click', handleAddSection);
            }

            // Initialize drag and drop
            initializeDragAndDrop();

            // Initialize auto-resize for all textareas with a slight delay to ensure DOM is ready
            setTimeout(() => {
                initializeTextareaAutoResize();
            }, 50);

            // Enable and show tab buttons for slideshow and quiz
            tabSlideshow.classList.remove('hidden');
            tabSlideshow.removeAttribute('disabled');
            tabQuiz.classList.remove('hidden');
            tabQuiz.removeAttribute('disabled');

            // Initialize conversation history with initial analysis
            conversationHistory = [
                {
                    role: 'user',
                    parts: [{ text: `Phân tích văn bản sau:\n\n${textInput.value.trim()}` }]
                },
                {
                    role: 'model',
                    parts: [{ text: JSON.stringify(result) }]
                }
            ];
        }

        function displaySlideshow(slideshowResult) {
            if (!slideshowResult || !slideshowResult.slides) return;

            let slideIndex = 0;
            const slides = slideshowResult.slides || [];

            if (slideshowResult.references) {
                // Format references for display
                const formattedReferences = slideshowResult.references.map(ref => {
                    // Basic heuristic to italicize book/journal titles
                    return ref.replace(/<i>(.*?)<\/i>/g, '<em>$1</em>'); // Use em for italics in prose context
                }).join('<br>');
                slides.push({
                    heading: 'Tài liệu tham khảo',
                    content: formattedReferences
                });
            }

            let slideHTML = `<div id="slide-wrapper">`;
            slides.forEach((slide, index) => {
                slideHTML += `<div class="slide p-6 border border-slate-200 rounded-lg bg-slate-50">
                    <h3 class="text-xl font-bold mb-4 text-indigo-700">${index + 1}. ${slide.heading}</h3>
                    <div class="prose max-w-none">${slide.content}</div>
                </div>`;
            });
            slideHTML += `</div>`;

            const totalSlides = slides.length;
            contentSlideshow.innerHTML = `
                <div class="mb-4">
                    <h2 class="text-2xl font-bold">${currentAnalysisData.title || 'Bản trình bày'}</h2>
                    <p class="text-slate-600">Bản trình bày tóm tắt nội dung phân tích.</p>
                </div>
                <div id="slideshow-container" class="bg-white rounded-xl shadow-inner">${slideHTML}</div>
                <div class="flex items-center justify-between mt-4">
                    <button id="prev-slide" class="px-4 py-2 bg-slate-200 hover:bg-slate-300 rounded-lg font-semibold text-slate-700">&larr; Trước</button>
                    <span id="slide-counter" class="text-sm font-medium text-slate-600">1 / ${totalSlides}</span>
                    <button id="next-slide" class="px-4 py-2 bg-slate-200 hover:bg-slate-300 rounded-lg font-semibold text-slate-700">Sau &rarr;</button>
                </div>`;

            const slideWrapper = contentSlideshow.querySelector('#slide-wrapper');
            const prevButton = contentSlideshow.querySelector('#prev-slide');
            const nextButton = contentSlideshow.querySelector('#next-slide');
            const slideCounter = contentSlideshow.querySelector('#slide-counter');

            function updateSlides() {
                if (totalSlides === 0) return;
                slideWrapper.style.transform = `translateX(-${slideIndex * 100}%)`;
                slideCounter.textContent = `${slideIndex + 1} / ${totalSlides}`;
                prevButton.disabled = slideIndex === 0;
                nextButton.disabled = slideIndex === totalSlides - 1;
            }

            prevButton.addEventListener('click', () => {
                if (slideIndex > 0) {
                    slideIndex--;
                    updateSlides();
                }
            });
            nextButton.addEventListener('click', () => {
                if (slideIndex < totalSlides - 1) {
                    slideIndex++;
                    updateSlides();
                }
            });

            updateSlides();
        }

        function displayQuiz(result) {
            if (!result || !result.quiz) return;
            let quizHTML = `
                <div class="mb-6">
                    <h2 class="text-2xl font-bold">Bài kiểm tra: ${result.title || 'Văn bản'}</h2>
                    <p class="text-slate-600">Kiểm tra kiến thức của bạn về tác phẩm.</p>
                </div>
                <div id="quiz-form" class="space-y-6">`;

            result.quiz.forEach((item, index) => {
                quizHTML += `<div class="question-block"><p class="font-semibold mb-2">${index + 1}. ${item.q}</p><div class="space-y-2">`;
                item.o.forEach((option, optionIndex) => {
                    quizHTML += `<div><label class="flex items-center space-x-3 p-3 border rounded-lg hover:bg-slate-50 cursor-pointer">
                                <input type="radio" name="question-${index}" value="${optionIndex}" class="h-4 w-4 text-indigo-600 border-slate-300 focus:ring-indigo-500">
                                <span>${option}</span></label></div>`;
                });
                quizHTML += `</div></div>`;
            });

            quizHTML += `</div><div class="mt-8">
                <button id="submit-quiz" class="px-6 py-2 bg-indigo-600 text-white font-semibold rounded-lg hover:bg-indigo-700">Nộp bài</button>
                <div id="quiz-results" class="mt-4 text-lg font-bold"></div>
            </div>`;
            contentQuiz.innerHTML = quizHTML;

            const submitButton = contentQuiz.querySelector('#submit-quiz');
            const resultsDiv = contentQuiz.querySelector('#quiz-results');

            submitButton.addEventListener('click', () => {
                let score = 0;
                result.quiz.forEach((item, index) => {
                    const selected = contentQuiz.querySelector(`input[name="question-${index}"]:checked`);
                    const questionBlock = selected?.closest('.question-block');
                    questionBlock?.querySelectorAll('label').forEach(l => {
                        l.classList.remove('bg-red-100', 'border-red-400', 'bg-green-100', 'border-green-400');
                    });
                    if (selected) {
                        const selectedLabel = selected.parentElement;
                        if (parseInt(selected.value) === item.a) {
                            score++;
                            selectedLabel.classList.add('bg-green-100', 'border-green-400');
                        } else {
                            selectedLabel.classList.add('bg-red-100', 'border-red-400');
                            const correctLabel = questionBlock.querySelector(`input[value="${item.a}"]`).parentElement;
                            if (correctLabel) {
                                correctLabel.classList.add('bg-green-100', 'border-green-400');
                            }
                        }
                    }
                });
                resultsDiv.textContent = `Kết quả của bạn: ${score} / ${result.quiz.length}`;
                resultsDiv.style.color = score / result.quiz.length > 0.5 ? 'green' : 'red';
            });
        }

        // Load saved analysis data on page load
        async function loadSavedAnalysis() {
            try {
                const urlParams = new URLSearchParams(window.location.search);
                const taskId = urlParams.get('task_id');
                const deliverId = urlParams.get('deliver_id');

                if (!taskId || !deliverId) {
                    return; // Required parameters missing
                }

                const response = await fetch(`/api/load_analysis.php?task_id=${taskId}&deliver_id=${deliverId}`);
                const result = await response.json();

                if (!response.ok) {
                    console.error('Error loading saved data:', result.message);
                    return;
                }

                if (!result.has_data) {
                    return;
                }

                // Data exists, restore it
                const savedData = result.data;

                // Store response_id
                if (savedData.response_id) {
                    currentResponseId = savedData.response_id;
                }

                // Restore AI history if available
                if (savedData.ai_history) {
                    if (savedData.ai_history.analysisData) {
                        currentAnalysisData = savedData.ai_history.analysisData;
                    }
                    if (savedData.ai_history.conversationHistory) {
                        conversationHistory = savedData.ai_history.conversationHistory;
                    }
                }

                // If we have analysis data, display it
                if (currentAnalysisData) {
                    isAnalyzed = true;

                    // Display the saved analysis
                    displaySavedAnalysis(savedData);

                    // Setup UI
                    setupGeneratorTabs();
                    placeholder.classList.add('hidden');
                    resultsTabs.classList.remove('hidden');
                    switchTab(tabAnalysis);
                    resultsTabs.querySelector('nav').classList.add('hidden');
                    switchToChatMode();

                    // Show notification
                    const notification = document.createElement('div');
                    notification.className = 'fixed top-20 right-4 bg-blue-500 text-white px-6 py-3 rounded-lg shadow-lg z-50 flex items-center gap-2';
                    notification.innerHTML = `
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                            <polyline points="22 4 12 14.01 9 11.01"></polyline>
                        </svg>
                        Đã tải kết quả phân tích đã lưu
                    `;
                    document.body.appendChild(notification);
                    setTimeout(() => notification.remove(), 3000);
                }

            } catch (error) {
                console.error('Error loading saved analysis:', error);
            }
        }

        // Display saved analysis (similar to displayAnalysis but from saved data)
        function displaySavedAnalysis(savedData) {
            const { title, sections } = savedData;

            // Helper function to create editable textarea
            const createEditableTextarea = (content, minRows = 2) => {
                if (!content) return '';
                return `<textarea class="editable-content w-full p-3 border border-slate-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 text-sm">${escapeHtml(content)}</textarea>`;
            };

            // Helper function to wrap content in sortable section
            const createSortableSection = (content, sectionId = '') => {
                const id = sectionId || `section-${Date.now()}-${Math.random().toString(36).substr(2, 9)}`;
                return `<div class="sortable-section" draggable="true" data-section-id="${id}">
                    <div class="section-controls">
                        <span class="drag-handle" title="Kéo để sắp xếp">
                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="display: inline; vertical-align: middle;">
                                <line x1="3" y1="12" x2="21" y2="12"></line>
                                <line x1="3" y1="6" x2="21" y2="6"></line>
                                <line x1="3" y1="18" x2="21" y2="18"></line>
                            </svg>
                            Kéo
                        </span>
                        <button class="delete-section-btn" onclick="deleteSection('${id}')" title="Xóa section này">
                            <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="display: inline; vertical-align: middle;">
                                <polyline points="3 6 5 6 21 6"></polyline>
                                <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                            </svg>
                            Xóa
                        </button>
                    </div>
                    ${content}
                </div>`;
            };

            // Create main analysis content
            let html = `<div id="analysis-content">`;

            // Save button at the top
            html += `<div class="mb-6 flex justify-between items-center sticky top-0 bg-white z-10 py-3 border-b">
                <div>
                    <h2 class="text-2xl font-bold text-slate-800">Kết quả phân tích</h2>
                    <p class="text-sm text-slate-600 mt-1">Bạn có thể chỉnh sửa, thêm mới hoặc sắp xếp lại nội dung</p>
                </div>
                <div class="flex gap-2">
                    <button id="add-section-button" class="px-4 py-2 bg-blue-600 text-white font-semibold rounded-lg hover:bg-blue-700 focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-colors flex items-center gap-2">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <line x1="12" y1="5" x2="12" y2="19"></line>
                            <line x1="5" y1="12" x2="19" y2="12"></line>
                        </svg>
                        Thêm Section
                    </button>
                    <button id="save-analysis-button" class="px-6 py-2.5 bg-green-600 text-white font-semibold rounded-lg hover:bg-green-700 focus:ring-2 focus:ring-green-500 focus:ring-offset-2 transition-colors flex items-center gap-2">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path>
                            <polyline points="17 21 17 13 7 13 7 21"></polyline>
                            <polyline points="7 3 7 8 15 8"></polyline>
                        </svg>
                        Lưu thay đổi
                    </button>
                </div>
            </div>`;

            html += `<div id="sortable-container">`;

            // Title section
            html += createSortableSection(`<div class="mb-6">
                <label class="block text-sm font-medium text-slate-700 mb-2">Tiêu đề</label>
                <input type="text" class="editable-title w-full p-3 border border-slate-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 text-2xl font-bold" value="${escapeHtml(title || 'Không có tiêu đề')}">
            </div>`, 'title-section');

            // Add all saved sections
            sections.forEach((section, index) => {
                html += createSortableSection(`<div class="mb-6">${createEditableTextarea(section.content, 4)}</div>`, `saved-section-${index}`);
            });

            html += `</div>`; // Close sortable-container
            html += `</div>`; // Close analysis-content

            contentAnalysis.innerHTML = html;

            // Add event listeners
            const saveButton = document.getElementById('save-analysis-button');
            if (saveButton) {
                saveButton.addEventListener('click', handleSaveAnalysis);
            }

            const addSectionButton = document.getElementById('add-section-button');
            if (addSectionButton) {
                addSectionButton.addEventListener('click', handleAddSection);
            }

            // Initialize drag and drop
            initializeDragAndDrop();

            // Initialize auto-resize
            setTimeout(() => {
                initializeTextareaAutoResize();
            }, 50);

            // Enable tabs
            tabSlideshow.classList.remove('hidden');
            tabSlideshow.removeAttribute('disabled');
            tabQuiz.classList.remove('hidden');
            tabQuiz.removeAttribute('disabled');
        }

        // Call loadSavedAnalysis on page load
        // Call on page load
        document.addEventListener('DOMContentLoaded', function () {
            // If we have saved content from database, display it
            if (savedContent && currentResponseId) {
                // Mark as analyzed
                isAnalyzed = true;

                // Display the saved content (title and sections from database)
                displaySavedAnalysis(savedContent);

                // Setup UI
                setupGeneratorTabs();
                placeholder.classList.add('hidden');
                resultsTabs.classList.remove('hidden');
                switchTab(tabAnalysis);
                resultsTabs.querySelector('nav').classList.add('hidden');
                switchToChatMode();

            } else if (currentAnalysisData && currentResponseId) {
                // Fallback: If we have AI analysis data but no saved content yet
                isAnalyzed = true;

                // Display the analysis
                displayAnalysis(currentAnalysisData);

                // Setup UI
                setupGeneratorTabs();
                placeholder.classList.add('hidden');
                resultsTabs.classList.remove('hidden');
                switchTab(tabAnalysis);
                resultsTabs.querySelector('nav').classList.add('hidden');
                switchToChatMode();

            } else {
                // No saved data yet - show fresh analysis interface

                // If task has content, pre-fill it
                const taskContent = <?php echo json_encode($taskContent); ?>;
                if (taskContent && textInput) {
                    textInput.value = taskContent;
                }
            }
        });
    </script>

</body>

</html>