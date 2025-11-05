<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php
    require_once './views/templates/assets.php';
    require_once './config/db.php';
    require_once './config/helper_homework.php';

    // Lấy parameters từ URL
    $task_id = isset($_GET['task_id']) ? intval($_GET['task_id']) : 0;
    $topic_id = isset($_GET['topic_id']) ? intval($_GET['topic_id']) : 0;
    $phase_id = isset($_GET['phase_id']) ? intval($_GET['phase_id']) : 0;

    // Khởi tạo biến
    $taskData = null;
    $mainPhaseCode = '';
    $fileContent = '';
    $isPhaseStart = false;
    $errorMessage = '';

    // Validate parameters
    if ($task_id > 0) {
        try {
            // Sử dụng hàm helper để lấy thông tin task
            $taskData = getInfoTask($task_id);

            if ($taskData) {
                $mainPhaseCode = $taskData['phase_code'];

                // Kiểm tra xem có phải PHASE_START không
                $isPhaseStart = ($mainPhaseCode === 'PHASE_START');

                // Validate topic_id nếu được cung cấp
                if ($topic_id > 0 && $taskData['topic_id'] != $topic_id) {
                    $errorMessage = 'Topic ID không khớp với nhiệm vụ.';
                    $taskData = null;
                }

                // Đọc nội dung file nếu có
                if ($taskData && !empty($taskData['file_content'])) {
                    $filePath = __DIR__ . '/../../uploads/' . $taskData['file_content'];
                    if (file_exists($filePath)) {
                        $fileContent = file_get_contents($filePath);
                    }
                }

                // Kiểm tra quyền truy cập của student
                if ($taskData && isset($_SESSION['user']['role']) && $_SESSION['user']['role'] === 'student') {
                    $student_id = $_SESSION['user']['user_id'];
                    if (!checkTaskAssignedToStudent($task_id, $student_id)) {
                        $errorMessage = 'Bạn không có quyền truy cập nhiệm vụ này.';
                        $taskData = null;
                    }
                }
            } else {
                $errorMessage = 'Không tìm thấy nhiệm vụ.';
            }
        } catch (Exception $e) {
            error_log("Error fetching task data: " . $e->getMessage());
            $errorMessage = 'Đã xảy ra lỗi khi tải dữ liệu.';
            $taskData = null;
        }
    } else {
        $errorMessage = 'Thông tin nhiệm vụ không hợp lệ.';
    }

    // Nếu không tìm thấy task hoặc có lỗi, redirect về trang trước
    if (!$taskData) {
        if ($topic_id > 0) {
            header("Location: /404");
        } else {
            header("Location: /404");
        }
        exit;
    }

    // Kiểm tra xem người dùng đã tạo phân tích chưa
    $json_data = null;
    $id_response = null;

    // Get user_id with flexible session structure
    $user_id = null;
    if (isset($_SESSION['user']['user_id'])) {
        $user_id = $_SESSION['user']['user_id'];
    } elseif (isset($_SESSION['user_id'])) {
        $user_id = $_SESSION['user_id'];
    }

    if ($user_id) {
        $analysisData = getResponseUserByTask($task_id, $user_id);
        if ($analysisData && isset($analysisData['id_response'])) {
            $json_data = json_decode($analysisData['json_ai'], true);
            $id_response = $analysisData['id_response'];
        }
    }

    ?>
    <title><?= htmlspecialchars($taskData['task_title']) ?> - Phân tích Văn học</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= URL_ROOT ?>/assets/css/literary_analysis.css">
    <style>
        /* Background image cho body trong dark theme - genAI page */
        body.dark-theme {
            background-image: url('/assets/img/vanhoc.jpg') !important;
            background-size: cover !important;
            background-position: center !important;
            background-repeat: no-repeat !important;
            background-attachment: fixed !important;
            position: relative;
        }

        /* Overlay tối cho body dark theme */
        body.dark-theme::before {
            content: '';
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(135deg, rgba(0, 0, 0, 0.75), rgba(0, 0, 0, 0.85));
            z-index: -1;
            pointer-events: none;
        }

        /* Main content transparent */
        body.dark-theme #main {
            background: transparent !important;
        }

        /* Footer transparent - cho phép ảnh nền hiển thị xuyên qua */
        body.dark-theme .footer {
            background: transparent !important;
            border-top: 1px solid rgba(61, 90, 128, 0.4) !important;
            box-shadow: 0 -2px 10px rgba(0, 0, 0, 0.4);
        }

        /* Image Block Styles - từ v4.html */
        .content-image-wrapper {
            margin: 1.5rem 0;
            position: relative;
            user-select: none;
            -webkit-user-select: none;
            -moz-user-select: none;
            -ms-user-select: none;
            padding: 0;
            border-radius: 0.75rem;
            transition: all 0.3s ease;
            cursor: pointer;
            background: #ffffff;
            border: 1px solid #e2e8f0;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
            overflow: hidden;
        }

        .content-image-wrapper:hover {
            box-shadow: 0 6px 16px rgba(0, 0, 0, 0.08), 0 0 0 3px rgba(79, 70, 229, 0.4);
            transform: translateY(-2px);
        }

        .content-image-wrapper.selected {
            box-shadow: 0 6px 16px rgba(0, 0, 0, 0.08), 0 0 0 4px rgba(79, 70, 229, 0.6);
            transform: translateY(-2px);
            background-color: #f9fafb;
        }

        .content-image-wrapper img {
            max-width: 100%;
            height: auto;
            border-radius: 0;
            box-shadow: none;
            pointer-events: none;
            display: block;
            width: 100%;
        }
    </style>
</head>

<body>
    <?php
    require_once './views/templates/header.php';
    if (isset($_SESSION['user']['role']) && $_SESSION['user']['role'] === 'teacher') {
        require_once './views/templates/sidebar_teacher.php';
    } else if (isset($_SESSION['user']['role']) && $_SESSION['user']['role'] === 'student') {
        require_once './views/templates/sidebar_student.php';
    }
    ?>

    <main id="main" class="main">
        <div class="pagetitle">
            <h1><?= htmlspecialchars($taskData['task_title']) ?></h1>
            <nav>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a
                            href="/class"><?= htmlspecialchars($_SESSION['current_class_name']) ?></a></li>
                    <li class="breadcrumb-item"><a
                            href="class/task?topic_id=<?= $topic_id ?>&phase_id=<?= $phase_id ?>">Nhiệm vụ</a></li>
                    <li class="breadcrumb-item active"><?= htmlspecialchars($taskData['phase_name']) ?></li>
                </ol>
            </nav>
        </div><!-- End Page Title -->

        <!-- Task Info -->
        <div class="alert alert-info mb-4 hidden">
            <h5><i class="bi bi-info-circle"></i> <?= htmlspecialchars($taskData['m_phase_name']) ?></h5>
            <p class="mb-0"><?= htmlspecialchars($taskData['task_description']) ?></p>
            <?php if ($isPhaseStart): ?>
                <small class="text-warning"><i class="bi bi-exclamation-triangle"></i> Lưu ý: Giai đoạn này chỉ cho phép
                    phân tích 1 lần, không thể đặt câu hỏi bổ sung.</small>
            <?php endif; ?>
        </div>

        <section class="section">
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 h-full">

                <!-- Left Panel: Input -->
                <div class="lg:col-span-4 h-full">
                    <div class="bg-white p-6 rounded-xl shadow-md sticky top-24">
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
                                placeholder="Dán văn bản để phân tích..." <?php if ($fileContent): ?>readonly<?php endif; ?>></textarea>
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
                        <div class="flex gap-2 flex-wrap">
                            <!-- Analyze Button (visible before analysis) -->
                            <button id="analyze-button"
                                class="flex justify-center items-center py-3 px-6 border border-transparent rounded-lg shadow-sm text-sm font-semibold text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-colors disabled:opacity-50 disabled:cursor-not-allowed">
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
                                class="hidden flex justify-center items-center py-3 px-6 border border-red-600 rounded-lg shadow-sm text-sm font-semibold text-red-600 bg-white hover:bg-red-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 transition-colors disabled:opacity-50 disabled:cursor-not-allowed">
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
                                class="hidden flex justify-center items-center py-3 px-6 border border-indigo-600 rounded-lg shadow-sm text-sm font-semibold text-indigo-600 bg-white hover:bg-indigo-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-colors disabled:opacity-50 disabled:cursor-not-allowed">
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
        // PHP data passed to JavaScript
        const IS_PHASE_START = <?= $isPhaseStart ? 'true' : 'false' ?>;
        const FILE_CONTENT = <?= json_encode($fileContent) ?>;
        const MAIN_PHASE_CODE = <?= json_encode($mainPhaseCode) ?>;
        const TASK_TITLE = <?= json_encode($taskData['task_title']) ?>;
        const TASK_ID = <?= $task_id ?>;
        const HAS_TASK_CONTENT = <?= !empty($fileContent) ? 'true' : 'false' ?>;

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

        // State
        let currentAnalysisData = <?php echo isset($json_data) ? json_encode($json_data) : 'null'; ?>; // Store current analysis data
        let conversationHistory = []; // Store chat history for refinement
        let isAnalyzed = false; // Track if initial analysis is done
        let currentResponseId = <?php echo isset($id_response) ? $id_response : 'null'; ?>; // Track current response ID for chat threading

        // Initialize: Load file content if available
        window.addEventListener('DOMContentLoaded', function() {
            if (FILE_CONTENT) {
                textInput.value = FILE_CONTENT;

                // Show file info if content loaded from task
                if (FILE_CONTENT.trim()) {
                    fileNameDisplay.textContent = 'Nội dung từ nhiệm vụ';
                    fileInfo.classList.remove('hidden');
                }
            }

            // Disable file upload for task-based analysis
            if (FILE_CONTENT) {
                fileUpload.disabled = true;
                fileUpload.parentElement.style.opacity = '0.5';
                fileUpload.parentElement.title = 'Đã có nội dung từ nhiệm vụ';
            }

            // Check if analysis already exists and display it
            if (currentAnalysisData && currentResponseId) {
                // Mark as analyzed
                isAnalyzed = true;

                // Display the existing analysis
                displayAnalysis(currentAnalysisData);
                setupGeneratorTabs();

                // Hide placeholder, show results
                placeholder.classList.add('hidden');
                resultsTabs.classList.remove('hidden');
                switchTab(tabAnalysis);

                // Switch to chat mode (disable analysis, enable chat)
                switchToChatMode();

                // Initialize conversation history with the existing analysis
                conversationHistory = [{
                        role: 'user',
                        parts: [{
                            text: `Phân tích văn bản sau:\n\n${FILE_CONTENT || textInput.value.trim()}`
                        }]
                    },
                    {
                        role: 'model',
                        parts: [{
                            text: JSON.stringify(currentAnalysisData)
                        }]
                    }
                ];

                // Add initial message to chat
                if (IS_PHASE_START) {
                    addChatMessage('system', '✅ Đã có phân tích trước đó. Bạn có thể xem kết quả bên phải.');
                } else {
                    addChatMessage('system', '✅ Đã có phân tích trước đó. Bạn có thể xem kết quả bên phải hoặc đặt câu hỏi để tìm hiểu thêm.');
                }

                // Show chat section if not PHASE_START
                if (!IS_PHASE_START) {
                    chatSection.classList.remove('hidden');
                }
            }
        });

        // --- Event Listeners ---
        fileUpload.addEventListener('change', handleFileUpload);
        deleteFileButton.addEventListener('click', handleDeleteFile);
        analyzeButton.addEventListener('click', handleAnalysis);
        chatButton.addEventListener('click', handleChatSubmit);
        resetButton.addEventListener('click', handleReset);
        tabs.forEach(tab => tab.addEventListener('click', handleTabClick));

        // --- Setup Functions ---
        function setupGeneratorTabs() {
            // Setup slideshow tab with generate button
            contentSlideshow.innerHTML = `
                <div class="text-center py-12">
                    <svg xmlns="http://www.w3.org/2000/svg" width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" class="mx-auto mb-4 text-indigo-400">
                        <rect x="2" y="3" width="20" height="14" rx="2" ry="2"></rect>
                        <line x1="8" y1="21" x2="16" y2="21"></line>
                        <line x1="12" y1="17" x2="12" y2="21"></line>
                    </svg>
                    <h3 class="text-xl font-semibold text-slate-700 mb-2">Tạo Trình chiếu</h3>
                    <p class="text-slate-500 mb-6">Tạo bản trình bày từ kết quả phân tích</p>
                    <button id="generate-slideshow-button" class="px-6 py-3 bg-indigo-600 text-white font-semibold rounded-lg hover:bg-indigo-700 transition">
                        Tạo Trình chiếu
                    </button>
                </div>
            `;

            // Setup quiz tab with generate button
            contentQuiz.innerHTML = `
                <div class="text-center py-12">
                    <svg xmlns="http://www.w3.org/2000/svg" width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" class="mx-auto mb-4 text-indigo-400">
                        <circle cx="12" cy="12" r="10"></circle>
                        <path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"></path>
                        <line x1="12" y1="17" x2="12.01" y2="17"></line>
                    </svg>
                    <h3 class="text-xl font-semibold text-slate-700 mb-2">Tạo Câu hỏi</h3>
                    <p class="text-slate-500 mb-6">Tạo câu hỏi trắc nghiệm từ kết quả phân tích</p>
                    <button id="generate-quiz-button" class="px-6 py-3 bg-indigo-600 text-white font-semibold rounded-lg hover:bg-indigo-700 transition">
                        Tạo Câu hỏi
                    </button>
                </div>
            `;

            // Attach event listeners to the buttons
            const slideshowBtn = document.getElementById('generate-slideshow-button');
            const quizBtn = document.getElementById('generate-quiz-button');

            if (slideshowBtn) {
                slideshowBtn.addEventListener('click', handleGenerateSlideshow);
            }
            if (quizBtn) {
                quizBtn.addEventListener('click', handleGenerateQuiz);
            }
        }

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

            // Don't allow deleting content from task (checked by backend)
            if (HAS_TASK_CONTENT) {
                alert('Không thể xóa nội dung từ nhiệm vụ.');
                return;
            }

            fileUpload.value = ''; // Reset the file input
            textInput.value = ''; // Clear the textarea
            fileInfo.classList.add('hidden');
            fileNameDisplay.textContent = '';
        }

        async function handleAnalysis() {
            // Prevent re-analysis if already analyzed
            if (isAnalyzed && currentAnalysisData) {
                alert('Văn bản đã được phân tích. Vui lòng làm mới (Reset) nếu muốn phân tích lại.');
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

                // Extract analysis content in order for database storage
                const analysisContent = extractAnalysisContent(analysisResult);

                // Save analysis result to database
                await saveAnalysisResult('analyze', analysisResult, analysisContent);

                // Switch UI to chat mode
                switchToChatMode();

                // Add initial analysis message based on phase type
                if (IS_PHASE_START) {
                    addChatMessage('assistant', '✅ Đã hoàn thành phân tích văn bản. Bạn có thể xem kết quả bên phải.');
                } else {
                    addChatMessage('assistant', '✅ Đã hoàn thành phân tích văn bản. Bạn có thể xem kết quả bên phải hoặc đặt câu hỏi để tìm hiểu thêm.');
                }
            }

            setLoadingState(false, 'analyze');
        }

        function switchToChatMode() {
            // Make text input readonly and disabled
            textInput.setAttribute('readonly', true);
            textInput.classList.add('bg-slate-100', 'cursor-not-allowed');

            // Hide analyze button, show reset button and comment button
            analyzeButton.classList.add('hidden');
            resetButton.classList.remove('hidden');
            resetButton.removeAttribute('disabled'); // Enable reset button

            // Show "Go to Comment" button
            if (currentResponseId) {
                const commentBtnHTML = `
                    <a href="/class/homework/comment?task_id=<?= $task_id ?>&topic_id=<?= $topic_id ?>&phase_id=<?= $phase_id ?>&deliver_id=<?= $deliver_id ?>" 
                       class="px-6 py-3 bg-green-600 text-white font-semibold rounded-lg hover:bg-green-700 transition inline-flex items-center gap-2">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" viewBox="0 0 16 16">
                            <path d="M2.678 11.894a1 1 0 0 1 .287.801 10.97 10.97 0 0 1-.398 2c1.395-.323 2.247-.697 2.634-.893a1 1 0 0 1 .71-.074A8.06 8.06 0 0 0 8 14c3.996 0 7-2.807 7-6 0-3.192-3.004-6-7-6S1 4.808 1 8c0 1.468.617 2.83 1.678 3.894zm-.493 3.905a21.682 21.682 0 0 1-.713.129c-.2.032-.352-.176-.273-.362a9.68 9.68 0 0 0 .244-.637l.003-.01c.248-.72.45-1.548.524-2.319C.743 11.37 0 9.76 0 8c0-3.866 3.582-7 8-7s8 3.134 8 7-3.582 7-8 7a9.06 9.06 0 0 1-2.347-.306c-.52.263-1.639.742-3.468 1.105z"/>
                        </svg>
                        Chuyển sang Bình luận
                    </a>
                `;

                // Insert before reset button
                if (!document.getElementById('go-comment-btn')) {
                    const commentBtn = document.createElement('div');
                    commentBtn.id = 'go-comment-btn';
                    commentBtn.innerHTML = commentBtnHTML;
                    resetButton.parentElement.insertBefore(commentBtn, resetButton);
                }
            }

            // Check if PHASE_START - disable chat functionality (UI only, backend validates)
            if (IS_PHASE_START) {
                // Keep chat button hidden and disabled for PHASE_START
                chatButton.classList.add('hidden');
                chatButton.setAttribute('disabled', true);

                // Hide chat section completely for PHASE_START
                chatSection.classList.add('hidden');

                // Add notice message
                addChatMessage('system', '⚠️ Giai đoạn này chỉ cho phép phân tích 1 lần, không thể đặt câu hỏi bổ sung.');
            } else {
                // Normal behavior for other phases
                chatButton.classList.remove('hidden');
                chatButton.removeAttribute('disabled'); // Enable chat button

                // Show chat section
                chatSection.classList.remove('hidden');

                // Clear chat messages placeholder
                chatMessages.innerHTML = '';
            }
        }

        function handleReset() {
            // Prevent reset for PHASE_START
            if (IS_PHASE_START) {
                alert('Giai đoạn này không cho phép làm lại. Bạn chỉ có thể phân tích 1 lần duy nhất.');
                return;
            }

            if (!confirm('Bạn có chắc chắn muốn làm lại? Tất cả dữ liệu phân tích sẽ bị mất.')) {
                return;
            }

            // Reset all states
            currentAnalysisData = null;
            conversationHistory = [];
            isAnalyzed = false;
            currentResponseId = null; // Reset response ID

            // Reset text input - restore original file content if available
            if (HAS_TASK_CONTENT) {
                textInput.value = FILE_CONTENT; // Restore task content
                textInput.setAttribute('readonly', true);
                textInput.classList.remove('bg-slate-100', 'cursor-not-allowed');
            } else {
                textInput.value = '';
                textInput.removeAttribute('readonly');
                textInput.classList.remove('bg-slate-100', 'cursor-not-allowed');
            }

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
            // Basic client-side check (backend will validate again)
            if (!isAnalyzed) {
                alert('Vui lòng phân tích văn bản trước.');
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
                parts: [{
                    text: userMessage
                }]
            });

            try {
                // Call backend API with validation
                const response = await fetch('./api/analyze_task.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        action: 'chat',
                        task_id: TASK_ID,
                        data: userMessage,
                        history: conversationHistory
                    })
                });

                const result = await response.json();

                if (!response.ok) {
                    // Backend validation failed
                    throw new Error(result.message || 'Đã xảy ra lỗi');
                }

                if (result.answer) {
                    // Add AI response to chat
                    addChatMessage('assistant', result.answer);

                    // Add to conversation history
                    conversationHistory.push({
                        role: 'model',
                        parts: [{
                            text: result.answer
                        }]
                    });

                    // Append to analysis display
                    appendToAnalysisDisplay(userMessage, result.answer);

                    // Save chat result to database (child of current response)
                    // Store as array: [question, answer]
                    const chatContent = [
                        `**Câu hỏi:** ${userMessage}`,
                        `**Trả lời:** ${result.answer}`
                    ];
                    await saveAnalysisResult('chat', {
                        question: userMessage,
                        answer: result.answer
                    }, chatContent);
                } else {
                    addChatMessage('system', '⚠️ Không nhận được phản hồi từ AI. Vui lòng thử lại.');
                }

            } catch (error) {
                console.error('Error during chat:', error);
                addChatMessage('system', `⚠️ Lỗi: ${error.message}`);
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
                    <div class="prose prose-slate max-w-none">
                        ${formatMarkdown(answer)}
                    </div>
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

            // Scroll to the new section
            qaSection.scrollIntoView({
                behavior: 'smooth',
                block: 'start'
            });
        }

        function handleTabClick(e) {
            // Basic check (no need for complex security here)
            const targetTab = e.target;
            if ((targetTab === tabSlideshow || targetTab === tabQuiz) && !isAnalyzed) {
                alert('Vui lòng phân tích văn bản trước.');
                return;
            }

            switchTab(targetTab);
        }

        async function handleGenerateSlideshow() {
            if (!isAnalyzed || !currentAnalysisData) {
                alert('Vui lòng phân tích văn bản trước.');
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
            if (!isAnalyzed || !currentAnalysisData) {
                alert('Vui lòng phân tích văn bản trước.');
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

        // --- API & Analysis Functions ---

        /**
         * Makes a POST request to the backend API with task validation.
         * @param {string} action - The action to perform (e.g., 'analyze', 'slideshow', 'quiz').
         * @param {string} data - The text or JSON string to send as data.
         * @returns {Promise<object|null>} The JSON response from the backend.
         */
        async function makeApiCall(action, data) {
            try {
                const response = await fetch('<?= URL_ROOT ?>/api/analyze_task.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        action: action,
                        task_id: TASK_ID,
                        data: data
                    })
                });

                const result = await response.json();

                if (!response.ok) {
                    // Backend validation failed
                    throw new Error(result.message || `API call failed: ${response.status}`);
                }

                if (result.error) throw new Error(result.error);
                return result;
            } catch (error) {
                console.error("Error during API call:", error);
                alert(`Lỗi: ${error.message}`);
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
         * Saves analysis result to database
         * @param {string} actionType - Type of action: 'analyze', 'chat', 'slideshow', 'quiz'
         * @param {object} aiResponse - The AI response data
         * @param {string} userContent - The user's input content
         * @returns {Promise<boolean>} Success status
         */
        async function saveAnalysisResult(actionType, aiResponse, userContent) {
            try {
                const response = await fetch('<?= URL_ROOT ?>/api/save_response.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        task_id: TASK_ID,
                        action_type: actionType,
                        ai_response: aiResponse,
                        user_content: userContent,
                        parent_response_id: actionType === 'chat' ? currentResponseId : null
                    })
                });

                const result = await response.json();

                if (response.ok && result.success) {
                    // Store response_id for threading chat responses
                    if (actionType === 'analyze') {
                        currentResponseId = result.data.response_id;
                    }
                    console.log('Saved:', result.data);
                    return true;
                } else {
                    console.warn('Failed to save:', result.message);
                    return false;
                }
            } catch (error) {
                console.error('Error saving analysis result:', error);
                return false;
            }
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

        /**
         * Extract analysis content as ordered array for database storage
         * @param {object} result - The analysis result object
         * @returns {Array<string>} Array of content sections in display order
         */
        function extractAnalysisContent(result) {
            const content = [];

            // Title
            if (result.title) {
                content.push(`# ${result.title}`);
            }

            // Keywords
            if (result.keywords && result.keywords.length > 0) {
                content.push(`**Từ khóa:** ${result.keywords.join(', ')}`);
            }

            // General Analysis - Thematic Analysis
            if (result.generalAnalysis?.thematicAnalysis) {
                result.generalAnalysis.thematicAnalysis.forEach(section => {
                    content.push(`## ${section.heading}\n${section.content}`);
                });
            }

            // General Analysis - Linguistic Insights
            if (result.generalAnalysis?.linguisticInsights) {
                const insights = result.generalAnalysis.linguisticInsights;
                let linguisticText = '## Ngôn ngữ & Biểu tượng\n';

                if (insights.motifs?.length > 0) {
                    linguisticText += `\n**Mô-típ lặp lại:**\n${insights.motifs.map(m => `- ${m}`).join('\n')}`;
                }
                if (insights.symbolsAndMetaphors?.length > 0) {
                    linguisticText += `\n**Biểu tượng & Ẩn dụ:**\n${insights.symbolsAndMetaphors.map(s => `- ${s}`).join('\n')}`;
                }
                if (insights.temporalElements) {
                    linguisticText += `\n**Yếu tố Thời gian:** ${insights.temporalElements}`;
                }
                if (insights.natureAndScenery) {
                    linguisticText += `\n**Thiên nhiên & Phong cảnh:** ${insights.natureAndScenery}`;
                }

                content.push(linguisticText);
            }

            // Story Analysis
            if (result.storyAnalysis) {
                const sa = result.storyAnalysis;
                if (sa.character) content.push(`## Nhân vật\n${sa.character}`);
                if (sa.space) content.push(`## Không gian\n${sa.space}`);
                if (sa.time) content.push(`## Thời gian\n${sa.time}`);
                if (sa.dialogue) content.push(`## Lời thoại & Độc thoại nội tâm\n${sa.dialogue}`);
                if (sa.narrator) content.push(`## Người kể chuyện\n${sa.narrator}`);
                if (sa.situation) content.push(`## Tình huống truyện\n${sa.situation}`);
                if (sa.prominentDetails) content.push(`## Chi tiết nổi bật\n${sa.prominentDetails}`);
            }

            // Drama Analysis
            if (result.dramaAnalysis) {
                const da = result.dramaAnalysis;
                if (da.characters) content.push(`## Nhân vật (Kịch)\n${da.characters}`);
                if (da.conflict) content.push(`## Xung đột\n${da.conflict}`);
                if (da.action) content.push(`## Hành động\n${da.action}`);
                if (da.structure) content.push(`## Cấu trúc\n${da.structure}`);
                if (da.dialogue) content.push(`## Đối thoại\n${da.dialogue}`);
            }

            // Poetry Analysis
            if (result.poetryAnalysis) {
                const pa = result.poetryAnalysis;
                if (pa.rhyming) content.push(`## Gieo vần trong thơ\n${pa.rhyming}`);
                if (pa.emotionalFlow) content.push(`## Mạch cảm xúc\n${pa.emotionalFlow}`);
                if (pa.structure) content.push(`## Cấu trúc bài thơ\n${pa.structure}`);
                if (pa.imagery) content.push(`## Hình ảnh trong thơ\n${pa.imagery}`);
                if (pa.rhythm) content.push(`## Phân tích Nhịp điệu\n${pa.rhythm}`);
                if (pa.spaceTimeOrganization) content.push(`## Tổ chức không gian & thời gian\n${pa.spaceTimeOrganization}`);
                if (pa.rhetoricalDevices) content.push(`## Biện pháp tu từ\n${pa.rhetoricalDevices}`);
            }

            // Concluding Thoughts
            if (result.concludingThoughts) {
                const ct = result.concludingThoughts;
                if (ct.lesson) content.push(`## Bài học\n${ct.lesson}`);
                if (ct.message) content.push(`## Thông điệp\n${ct.message}`);
            }

            // Intertextuality
            if (result.intertextuality) {
                const it = result.intertextuality;
                let intertextText = '## Phân tích Liên văn bản\n';

                if (it.initialAnalysis) intertextText += `${it.initialAnalysis}\n`;
                if (it.comparativeText) {
                    intertextText += `\n**So sánh với:** ${it.comparativeText.title} - ${it.comparativeText.author}\n`;
                }
                if (it.comparison) {
                    if (it.comparison.similarities) intertextText += `\n**Điểm tương đồng:** ${it.comparison.similarities}\n`;
                    if (it.comparison.differences) intertextText += `\n**Điểm khác biệt:** ${it.comparison.differences}\n`;
                    if (it.comparison.effects) intertextText += `\n**Hiệu quả:** ${it.comparison.effects}`;
                }

                content.push(intertextText);
            }

            return content;
        }

        // === TEXT TO IMAGE CONVERSION (từ v4.html) ===
        async function textToImage(text, width = 800, options = {}) {
            const {
                fontSize = 16,
                    fontFamily = 'Inter, sans-serif',
                    lineHeight = 1.7,
                    padding = 30,
                    backgroundColor = '#ffffff',
                    textColor = '#334155',
                    maxWidth = width - padding * 2,
            } = options;

            const canvas = document.createElement('canvas');
            const ctx = canvas.getContext('2d');

            const parseInlineFormatting = (line) => {
                const segments = [];
                let remainingText = line;
                let match;
                const regex = /(\*|_)(.*?)\1/g;
                let lastIndex = 0;

                while ((match = regex.exec(remainingText)) !== null) {
                    if (match.index > lastIndex) {
                        segments.push({
                            text: remainingText.substring(lastIndex, match.index),
                            style: {}
                        });
                    }
                    const style = {};
                    if (match[1] === '*') style.bold = true;
                    if (match[1] === '_') style.italic = true;
                    segments.push({
                        text: match[2],
                        style: style
                    });
                    lastIndex = regex.lastIndex;
                }

                if (lastIndex < remainingText.length) {
                    segments.push({
                        text: remainingText.substring(lastIndex),
                        style: {}
                    });
                }

                if (segments.length === 0 && line) {
                    segments.push({
                        text: line,
                        style: {}
                    });
                }

                return segments;
            };

            const applyStyle = (style) => {
                let fontStyle = '';
                if (style.italic) fontStyle += 'italic ';
                if (style.bold) fontStyle += 'bold ';
                return `${fontStyle} ${fontSize}px ${fontFamily}`;
            };

            const paragraphs = text.split('\n\n');
            const lines = [];

            paragraphs.forEach((paragraph) => {
                paragraph.split('\n').forEach((rawLine) => {
                    if (rawLine.trim() === '') {
                        lines.push({
                            segments: [],
                            height: lineHeight * fontSize * 0.5
                        });
                        return;
                    }

                    const parsedSegments = parseInlineFormatting(rawLine);
                    let currentLineSegments = [];
                    let currentLineWidth = 0;

                    parsedSegments.forEach((segment) => {
                        ctx.font = applyStyle(segment.style);
                        const words = segment.text.split(/(\s+)/).filter(Boolean);

                        words.forEach((word) => {
                            const wordWidth = ctx.measureText(word).width;

                            if (currentLineWidth + wordWidth > maxWidth && currentLineSegments.length > 0 && !/^\s+$/.test(word)) {
                                lines.push({
                                    segments: [...currentLineSegments],
                                    height: lineHeight * fontSize
                                });
                                const startingWord = /^\s+$/.test(word) ? '' : word.trimStart();
                                currentLineSegments = [{
                                    text: startingWord,
                                    style: segment.style
                                }];
                                currentLineWidth = startingWord ? ctx.measureText(startingWord).width : 0;
                            } else {
                                if (currentLineSegments.length === 0 && /^\s+$/.test(word)) return;
                                currentLineSegments.push({
                                    text: word,
                                    style: segment.style
                                });
                                currentLineWidth += wordWidth;
                            }
                        });
                    });

                    if (currentLineSegments.length > 0) {
                        lines.push({
                            segments: [...currentLineSegments],
                            height: lineHeight * fontSize
                        });
                    }
                });
                lines.push({
                    segments: [],
                    height: lineHeight * fontSize * 0.7
                });
            });

            if (lines.length > 0 && lines[lines.length - 1].segments.length === 0) lines.pop();

            const totalHeight = lines.reduce((sum, line) => sum + line.height, 0) + padding * 2;
            canvas.width = width;
            canvas.height = Math.max(totalHeight, 50);

            ctx.fillStyle = backgroundColor;
            ctx.fillRect(0, 0, canvas.width, canvas.height);
            ctx.textBaseline = 'top';

            let currentY = padding;
            lines.forEach((line) => {
                let currentX = padding;
                line.segments.forEach((segment) => {
                    ctx.font = applyStyle(segment.style);
                    ctx.fillStyle = textColor;
                    ctx.fillText(segment.text, currentX, currentY);
                    currentX += ctx.measureText(segment.text).width;
                });
                currentY += line.height;
            });

            return canvas.toDataURL('image/png');
        }

        // === FORMATTING FUNCTIONS FOR IMAGE GENERATION ===
        const formatForImage = (content) =>
            content ?
            String(content)
            .replace(/<br\s*\/?>/gi, '\n')
            .replace(/<\/?i>/gi, '_')
            .replace(/<\/?em>/gi, '_')
            .replace(/<\/?strong>/gi, '*')
            .replace(/<\/?b>/gi, '*') :
            '';

        function formatGeneralAnalysisForImage(data) {
            let text = '';

            // Thematic Analysis
            if (data.thematicAnalysis && data.thematicAnalysis.length > 0) {
                data.thematicAnalysis.forEach(section => {
                    text += `*${section.heading}*\n${formatForImage(section.content)}\n\n`;
                });
            }

            // Linguistic Insights
            if (data.linguisticInsights) {
                const insights = data.linguisticInsights;
                if (insights.motifs?.length) {
                    text += `*Mô-típ lặp lại:*\n${insights.motifs.map(i => `• ${formatForImage(i)}`).join('\n')}\n\n`;
                }
                if (insights.symbolsAndMetaphors?.length) {
                    text += `*Biểu tượng & Ẩn dụ:*\n${insights.symbolsAndMetaphors.map(i => `• ${formatForImage(i)}`).join('\n')}\n\n`;
                }
                if (insights.temporalElements) {
                    text += `*Yếu tố Thời gian:*\n${formatForImage(insights.temporalElements)}\n\n`;
                }
                if (insights.natureAndScenery) {
                    text += `*Thiên nhiên & Phong cảnh:*\n${formatForImage(insights.natureAndScenery)}`;
                }
            }

            return text.trim();
        }

        function formatStoryAnalysisForImage(data) {
            const order = ['character', 'space', 'time', 'dialogue', 'narrator', 'situation', 'prominentDetails'];
            const titles = {
                character: 'Nhân vật',
                space: 'Không gian',
                time: 'Thời gian',
                dialogue: 'Lời thoại & Độc thoại nội tâm',
                narrator: 'Người kể chuyện',
                situation: 'Tình huống truyện',
                prominentDetails: 'Chi tiết nổi bật'
            };

            return order
                .map(key => data[key] ? `*${titles[key]}:*\n${formatForImage(data[key])}` : null)
                .filter(Boolean)
                .join('\n\n');
        }

        function formatDramaAnalysisForImage(data) {
            const order = ['characters', 'conflict', 'action', 'structure', 'dialogue'];
            const titles = {
                characters: 'Nhân vật',
                conflict: 'Xung đột',
                action: 'Hành động',
                structure: 'Cấu trúc',
                dialogue: 'Đối thoại'
            };

            return order
                .map(key => data[key] ? `*${titles[key]}:*\n${formatForImage(data[key])}` : null)
                .filter(Boolean)
                .join('\n\n');
        }

        function formatPoetryAnalysisForImage(data) {
            const order = ['rhyming', 'rhythm', 'structure', 'imagery', 'emotionalFlow', 'rhetoricalDevices', 'spaceTimeOrganization'];
            const titles = {
                rhyming: 'Gieo vần trong thơ',
                rhythm: 'Phân tích Nhịp điệu',
                structure: 'Cấu trúc bài thơ',
                imagery: 'Hình ảnh trong thơ',
                emotionalFlow: 'Mạch cảm xúc',
                rhetoricalDevices: 'Biện pháp tu từ',
                spaceTimeOrganization: 'Tổ chức không gian & thời gian'
            };

            return order
                .map(key => data[key] ? `*${titles[key]}:*\n${formatForImage(data[key])}` : null)
                .filter(Boolean)
                .join('\n\n');
        }

        function formatConclusionForImage(data) {
            let text = '';
            if (data.lesson) text += `*Bài học:*\n${formatForImage(data.lesson)}\n\n`;
            if (data.message) text += `*Thông điệp:*\n${formatForImage(data.message)}`;
            return text.trim();
        }

        function formatIntertextualityForImage(data) {
            let text = '';
            if (data.initialAnalysis) text += `${formatForImage(data.initialAnalysis)}\n\n`;
            if (data.comparativeText) {
                text += `*So sánh với:*\n_${data.comparativeText.title || ''}_ - ${data.comparativeText.author || ''}\n\n`;
            }
            if (data.comparison) {
                if (data.comparison.similarities) text += `*Điểm tương đồng:*\n${formatForImage(data.comparison.similarities)}\n\n`;
                if (data.comparison.differences) text += `*Điểm khác biệt:*\n${formatForImage(data.comparison.differences)}\n\n`;
                if (data.comparison.effects) text += `*Hiệu quả:*\n${formatForImage(data.comparison.effects)}`;
            }
            return text.trim();
        }

        // === CREATE IMAGE BLOCK HTML ===
        function createImageBlock(imageData, sectionName) {
            return `
                <div class="content-image-wrapper" data-section="${sectionName}">
                    <img src="${imageData}" alt="${sectionName} Analysis Image">
                </div>
            `;
        }

        // === GENERATE AND INSERT IMAGE BLOCKS ===
        async function generateContentImages(result) {
            const sectionsToImage = [{
                    id: 'general-images',
                    title: 'Phân tích Chung',
                    data: result.generalAnalysis,
                    formatFunc: formatGeneralAnalysisForImage,
                    bgColor: '#f9fafb',
                    textColor: '#1f2937',
                },
                {
                    id: 'story-images',
                    title: 'Phân tích Truyện',
                    data: result.storyAnalysis,
                    formatFunc: formatStoryAnalysisForImage,
                    bgColor: '#f0f9ff',
                    textColor: '#1e40af',
                },
                {
                    id: 'drama-images',
                    title: 'Phân tích Kịch',
                    data: result.dramaAnalysis,
                    formatFunc: formatDramaAnalysisForImage,
                    bgColor: '#fef3c7',
                    textColor: '#92400e',
                },
                {
                    id: 'poetry-images',
                    title: 'Phân tích Thơ ca',
                    data: result.poetryAnalysis,
                    formatFunc: formatPoetryAnalysisForImage,
                    bgColor: '#fce7f3',
                    textColor: '#831843',
                },
                {
                    id: 'conclusion-images',
                    title: 'Bài học & Thông điệp',
                    data: result.concludingThoughts,
                    formatFunc: formatConclusionForImage,
                    bgColor: '#dcfce7',
                    textColor: '#14532d',
                },
                {
                    id: 'intertextuality-images',
                    title: 'Phân tích Liên văn bản',
                    data: result.intertextuality,
                    formatFunc: formatIntertextualityForImage,
                    bgColor: '#ede9fe',
                    textColor: '#4c1d95',
                },
            ];

            const imagePromises = [];

            sectionsToImage.forEach((section) => {
                const container = document.getElementById(section.id);
                if (container && section.data) {
                    const textContent = section.formatFunc(section.data);
                    if (textContent) {
                        const promise = textToImage(textContent, 750, {
                                fontSize: 15,
                                lineHeight: 1.7,
                                backgroundColor: section.bgColor,
                                textColor: section.textColor,
                                padding: 25,
                            })
                            .then((imageData) => {
                                if (imageData) {
                                    container.innerHTML = createImageBlock(imageData, section.title);
                                } else {
                                    console.warn(`Cannot create image for section: ${section.title}`);
                                }
                            })
                            .catch((error) => {
                                console.error(`Error creating image for ${section.title}:`, error);
                            });
                        imagePromises.push(promise);
                    }
                }
            });

            try {
                await Promise.all(imagePromises);
                console.log('✅ All analysis images generated and displayed');
                setupImageBlockHandlers();
            } catch (error) {
                console.error('Error during image generation/display:', error);
            }
        }

        // === IMAGE BLOCK INTERACTION ===
        let selectedBlock = null;

        function setupImageBlockHandlers() {
            const wrappers = document.querySelectorAll('.content-image-wrapper');
            wrappers.forEach((wrapper) => {
                const section = wrapper.dataset.section || 'Unknown';
                wrapper.addEventListener('click', function(e) {
                    if (selectedBlock && selectedBlock !== this) {
                        selectedBlock.classList.remove('selected');
                    }
                    this.classList.toggle('selected');
                    selectedBlock = this.classList.contains('selected') ? this : null;
                    console.log(selectedBlock ? `📌 Selected Image: "${section}"` : `⚪ Deselected Image: "${section}"`);
                });
            });
        }

        // --- Display Functions ---
        function displayAnalysis(result) {
            // console.log(result);

            if (!result) return;

            // Create main analysis content
            let html = `<div id="analysis-content">`;
            html += `<h1>${result.title || 'Không có tiêu đề'}</h1>`;

            html += `<h2>Đặc điểm văn bản</h2><div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">`;
            if (result.characteristics) {
                for (const [key, value] of Object.entries(result.characteristics)) {
                    html += `<div class="bg-slate-100 p-3 rounded-lg"><strong class="block text-sm text-slate-600">${key}</strong><span class="text-indigo-700 font-semibold">${value}</span></div>`;
                }
            }
            html += `</div>`;

            html += `<h2>Thống kê Ngôn ngữ</h2><div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">`;
            if (result.stats) {
                html += `<div class="bg-slate-100 p-3 rounded-lg"><strong class="block text-sm text-slate-600">Số ký tự</strong><span class="text-indigo-700 font-semibold">${result.stats.charCount.toLocaleString('vi-VN')}</span></div>`;
                html += `<div class="bg-slate-100 p-3 rounded-lg"><strong class="block text-sm text-slate-600">Số từ</strong><span class="text-indigo-700 font-semibold">${result.stats.wordCount.toLocaleString('vi-VN')}</span></div>`;
                html += `<div class="bg-slate-100 p-3 rounded-lg"><strong class="block text-sm text-slate-600">Từ nổi bật (tần suất)</strong><span class="text-indigo-700 font-semibold">${result.stats.topWords.map(w => w[0]).join(', ')}</span></div>`;
            }
            html += `</div>`;

            if (result.keywords && result.keywords.length > 0) {
                html += `<h2>Từ khóa chính</h2><div class="flex flex-wrap gap-2 mb-6">`;
                result.keywords.forEach(keyword => {
                    html += `<span class="bg-indigo-100 text-indigo-800 text-sm font-medium px-3 py-1.5 rounded-full">${keyword}</span>`;
                });
                html += `</div>`;
            }

            // --- Image Block Containers ---
            if (result.generalAnalysis) {
                html += `<h2>Phân tích Chung</h2><div id="general-images" class="mb-6"><div class="text-center py-4 text-slate-400"><svg class="animate-spin h-8 w-8 mx-auto text-indigo-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg><p class="mt-2">Đang tạo hình ảnh...</p></div></div>`;
            }

            if (result.storyAnalysis) {
                html += `<h2>Phân tích Truyện</h2><div id="story-images" class="mb-6"><div class="text-center py-4 text-slate-400"><svg class="animate-spin h-8 w-8 mx-auto text-indigo-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg><p class="mt-2">Đang tạo hình ảnh...</p></div></div>`;
            }

            if (result.dramaAnalysis) {
                html += `<h2>Phân tích Kịch</h2><div id="drama-images" class="mb-6"><div class="text-center py-4 text-slate-400"><svg class="animate-spin h-8 w-8 mx-auto text-indigo-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg><p class="mt-2">Đang tạo hình ảnh...</p></div></div>`;
            }

            if (result.poetryAnalysis) {
                html += `<h2>Phân tích Thơ ca</h2><div id="poetry-images" class="mb-6"><div class="text-center py-4 text-slate-400"><svg class="animate-spin h-8 w-8 mx-auto text-indigo-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg><p class="mt-2">Đang tạo hình ảnh...</p></div></div>`;
            }

            if (result.concludingThoughts) {
                html += `<h2>Bài học & Thông điệp</h2><div id="conclusion-images" class="mb-6"><div class="text-center py-4 text-slate-400"><svg class="animate-spin h-8 w-8 mx-auto text-indigo-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg><p class="mt-2">Đang tạo hình ảnh...</p></div></div>`;
            }

            if (result.intertextuality) {
                html += `<h2>Phân tích Liên văn bản</h2><div id="intertextuality-images" class="mb-6"><div class="text-center py-4 text-slate-400"><svg class="animate-spin h-8 w-8 mx-auto text-indigo-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg><p class="mt-2">Đang tạo hình ảnh...</p></div></div>`;
            }

            html += `</div>`; // Close analysis-content

            contentAnalysis.innerHTML = html;

            // Enable and show tab buttons for slideshow and quiz
            tabSlideshow.classList.remove('hidden');
            tabSlideshow.removeAttribute('disabled');
            tabQuiz.classList.remove('hidden');
            tabQuiz.removeAttribute('disabled');

            // Initialize conversation history with initial analysis
            conversationHistory = [{
                    role: 'user',
                    parts: [{
                        text: `Phân tích văn bản sau:\n\n${textInput.value.trim()}`
                    }]
                },
                {
                    role: 'model',
                    parts: [{
                        text: JSON.stringify(result)
                    }]
                }
            ];

            // Generate images asynchronously
            generateContentImages(result);
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
    </script>

</body>

</html>