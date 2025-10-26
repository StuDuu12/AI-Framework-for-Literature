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
                    <li class="breadcrumb-item"><a href="/class"><?= htmlspecialchars($_SESSION['current_class_name']) ?></a></li>
                    <li class="breadcrumb-item"><a href="class/task?topic_id=<?= $topic_id ?>&phase_id=<?= $phase_id ?>">Nhiệm vụ</a></li>
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
        window.addEventListener('DOMContentLoaded', function () {
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
                conversationHistory = [
                    {
                        role: 'user',
                        parts: [{ text: `Phân tích văn bản sau:\n\n${FILE_CONTENT || textInput.value.trim()}` }]
                    },
                    {
                        role: 'model',
                        parts: [{ text: JSON.stringify(currentAnalysisData) }]
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

            // Hide analyze button, show reset button
            analyzeButton.classList.add('hidden');
            resetButton.classList.remove('hidden');
            resetButton.removeAttribute('disabled'); // Enable reset button

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
                parts: [{ text: userMessage }]
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
                        parts: [{ text: result.answer }]
                    });

                    // Append to analysis display
                    appendToAnalysisDisplay(userMessage, result.answer);

                    // Save chat result to database (child of current response)
                    // Store as array: [question, answer]
                    const chatContent = [
                        `**Câu hỏi:** ${userMessage}`,
                        `**Trả lời:** ${result.answer}`
                    ];
                    await saveAnalysisResult('chat', { question: userMessage, answer: result.answer }, chatContent);
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
            qaSection.scrollIntoView({ behavior: 'smooth', block: 'start' });
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

        // --- Display Functions ---
        function displayAnalysis(result) {
            // console.log(result);

            if (!result) return;

            // Create main analysis content
            let html = `<div id="analysis-content">`;
            html += `<h1>${result.title || 'Không có tiêu đề'}</h1>`;

            const formatDetailedAnalysis = (text) => {
                if (!text) return '';
                // Split by the bold title pattern, keeping the delimiter
                const parts = text.split(/(\*\*(?:.*?):\*\*)/g).filter(part => part.trim() !== '');
                let formattedHtml = '';
                for (let i = 0; i < parts.length; i += 2) {
                    const title = parts[i] ? parts[i].replace(/\*\*/g, '') : '';
                    // Remove leading newline and bullet, then format newlines
                    const content = parts[i + 1] ? parts[i + 1].replace(/^\s*\\n\s*•?\s*/, '').replace(/\\n/g, '<br>') : '';
                    if (title) {
                        formattedHtml += `<div class="analysis-point"><strong>${title}</strong><span>${content}</span></div>`;
                    } else if (content) {
                        formattedHtml += `<p>${content.replace(/\\n/g, '<br>')}</p>`; // Fallback for content without title
                    }
                }
                // Remove initial <br> if it exists after processing
                return formattedHtml.replace(/^<br>/, '');
            };


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

            // --- General Analysis Section ---
            if (result.generalAnalysis) {
                html += `<h2>Phân tích Chung</h2>`;
                const ga = result.generalAnalysis;

                // Thematic Analysis within General Analysis
                if (ga.thematicAnalysis) {
                    ga.thematicAnalysis.forEach(section => {
                        html += `<h3>${section.heading}</h3><p>${section.content}</p>`;
                    });
                }

                // Linguistic Insights within General Analysis
                if (ga.linguisticInsights) {
                    html += `<h3>Ngôn ngữ & Biểu tượng</h3>`; // Sub-heading for linguistic insights
                    const insights = ga.linguisticInsights;
                    html += `<ul>`; // Start bullet list for insights
                    if (insights.motifs?.length > 0) {
                        html += `<li><strong>Mô-típ lặp lại:</strong><ul>`; // Nested list for motifs
                        insights.motifs.forEach(item => {
                            html += `<li>${item}</li>`;
                        });
                        html += `</ul></li>`;
                    }
                    if (insights.symbolsAndMetaphors?.length > 0) {
                        html += `<li><strong>Biểu tượng & Ẩn dụ:</strong><ul>`; // Nested list for symbols
                        insights.symbolsAndMetaphors.forEach(item => {
                            html += `<li>${item}</li>`;
                        });
                        html += `</ul></li>`;
                    }
                    if (insights.temporalElements) {
                        html += `<li><strong>Yếu tố Thời gian:</strong> ${insights.temporalElements}</li>`;
                    }
                    if (insights.natureAndScenery) {
                        html += `<li><strong>Thiên nhiên & Phong cảnh:</strong> ${insights.natureAndScenery}</li>`;
                    }
                    html += `</ul>`; // End bullet list for insights
                }
            }


            // --- Genre-Specific Analysis ---
            if (result.storyAnalysis) {
                html += `<h2>Phân tích Truyện</h2>`;
                const sa = result.storyAnalysis;
                if (sa.character) html += `<h3>Nhân vật</h3><div>${formatDetailedAnalysis(sa.character)}</div>`;
                if (sa.space) html += `<h3>Không gian</h3><div>${formatDetailedAnalysis(sa.space)}</div>`;
                if (sa.time) html += `<h3>Thời gian</h3><div>${formatDetailedAnalysis(sa.time)}</div>`;
                if (sa.dialogue) html += `<h3>Lời thoại & Độc thoại nội tâm</h3><div>${formatDetailedAnalysis(sa.dialogue)}</div>`; // Combined dialogue/monologue
                if (sa.narrator) html += `<h3>Người kể chuyện</h3><div>${formatDetailedAnalysis(sa.narrator)}</div>`;
                if (sa.situation) html += `<h3>Tình huống truyện</h3><div>${formatDetailedAnalysis(sa.situation)}</div>`;
                if (sa.prominentDetails) html += `<h3>Chi tiết nổi bật</h3><div>${formatDetailedAnalysis(sa.prominentDetails)}</div>`;
            }

            if (result.dramaAnalysis) {
                html += `<h2>Phân tích Kịch</h2>`;
                const da = result.dramaAnalysis;
                if (da.characters) html += `<h3>Nhân vật</h3><div>${formatDetailedAnalysis(da.characters)}</div>`;
                if (da.conflict) html += `<h3>Xung đột</h3><div>${formatDetailedAnalysis(da.conflict)}</div>`;
                if (da.action) html += `<h3>Hành động</h3><div>${formatDetailedAnalysis(da.action)}</div>`;
                if (da.structure) html += `<h3>Cấu trúc</h3><div>${formatDetailedAnalysis(da.structure)}</div>`;
                if (da.dialogue) html += `<h3>Đối thoại</h3><div>${formatDetailedAnalysis(da.dialogue)}</div>`;
            }

            if (result.poetryAnalysis) {
                html += `<h2>Phân tích Thơ ca</h2>`;
                const pa = result.poetryAnalysis;
                if (pa.rhyming) html += `<h3>Gieo vần trong thơ</h3><p>${pa.rhyming}</p>`;
                if (pa.emotionalFlow) html += `<h3>Mạch cảm xúc</h3><p>${pa.emotionalFlow}</p>`;
                if (pa.structure) html += `<h3>Cấu trúc bài thơ</h3><p>${pa.structure}</p>`;
                if (pa.imagery) html += `<h3>Hình ảnh trong thơ</h3><p>${pa.imagery}</p>`;
                if (pa.rhythm) html += `<h3>Phân tích Nhịp điệu</h3><p>${pa.rhythm.replace(/\n/g, '<br>')}</p>`;
                if (pa.spaceTimeOrganization) html += `<h3>Tổ chức không gian & thời gian</h3><p>${pa.spaceTimeOrganization}</p>`;
                if (pa.rhetoricalDevices) html += `<h3>Biện pháp tu từ</h3><p>${pa.rhetoricalDevices}</p>`;
            }

            // --- Concluding Sections ---
            if (result.concludingThoughts) {
                html += `<h2>Bài học & Thông điệp</h2>`;
                const ct = result.concludingThoughts;
                if (ct.lesson) html += `<h3>Bài học</h3><p>${ct.lesson}</p>`;
                if (ct.message) html += `<h3>Thông điệp</h3><p>${ct.message}</p>`;
            }

            if (result.intertextuality) {
                html += `<h2>Phân tích Liên văn bản</h2>`;
                const it = result.intertextuality;
                if (it.initialAnalysis) {
                    html += `<p>${it.initialAnalysis}</p>`;
                }
                if (it.comparativeText) {
                    html += `<h3>So sánh với tác phẩm tương đồng: <em>${it.comparativeText.title}</em> - ${it.comparativeText.author}</h3>`;
                }
                if (it.comparison) {
                    if (it.comparison.similarities) html += `<h4>Điểm tương đồng</h4><p>${it.comparison.similarities}</p>`;
                    if (it.comparison.differences) html += `<h4>Điểm khác biệt/thay đổi</h4><p>${it.comparison.differences}</p>`;
                    if (it.comparison.effects) html += `<h4>Hiệu quả</h4><p>${it.comparison.effects}</p>`;
                }
            }

            html += `</div>`; // Close analysis-content

            html += `</div>`; // Close analysis-content

            contentAnalysis.innerHTML = html;

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
    </script>

</body>

</html>