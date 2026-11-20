<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php require_once __DIR__ . '/../templates/assets.php'; ?>
    <title>Bài tập về nhà - Phân tích văn học</title>
    <link rel="stylesheet" href="<?= URL_ROOT ?>/assets/css/literary_analysis.css">
    <link rel="stylesheet" href="<?= URL_ROOT ?>/assets/css/genai-layout.css">
    <link rel="stylesheet" href="<?= URL_ROOT ?>/assets/css/genai-content.css">
</head>

<body class="literary-bg">
    <?php
    // Get task information from URL parameters
    require_once __DIR__ . '/../../config/helper_homework.php';
    require_once __DIR__ . '/../../config/helper_task.php';
    require_once __DIR__ . '/../../config/db.php';
    require_once __DIR__ . '/../../config/helper_breadcrumb.php';

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

    require_once __DIR__ . '/../templates/header.php';
    require_once __DIR__ . '/../templates/sidebar.php';
    ?>

    <main id="main" class="main">
        <?php
        $__ds = $pdo->prepare('SELECT status FROM deliver WHERE id_deliver = ?');
        $__ds->execute([$deliver_id]);
        $__btvnStatus = (int)$__ds->fetchColumn();
        $wh = [
            'task_id' => $taskId, 'topic_id' => $topicId ?: (int)($taskInfo['topic_id'] ?? 0),
            'phase_id' => $phaseId ?: (int)($taskInfo['phase_id'] ?? 0), 'deliver_id' => $deliver_id,
            'title' => $taskTitle ?: 'Bài tập về nhà',
            'description' => $taskInfo['task_description'] ?? '',
            'topic_name' => $taskInfo['topic_name'] ?? '',
            'phase_name' => $taskInfo['phase_name'] ?? '',
            'phase_number' => (int)($taskInfo['phase_number'] ?? 1),
            'phase_code' => $taskInfo['phase_code'] ?? 'PHASE_BTVN',
            'steps' => ['Nhập văn bản', 'AI phân tích', 'Sửa, thêm, sắp xếp ý', 'Lưu và sang bình luận'],
            'step' => $__btvnStatus >= 2 ? 4 : ($hasExistingResponse ? 3 : 1),
        ];
        require __DIR__ . '/_work_header.php';
        ?>

        <section class="section">
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 h-full">

                <!-- Left Panel: Input -->
                <div class="lg:col-span-4 h-full">
                    <div class="bg-white p-6 rounded-xl shadow-md sticky top-24">
                        <h2 class="text-lg font-semibold mb-4 text-slate-800">💬 <span>Làm việc với AI</span></h2>

                        <!-- Text Area for Analysis Content -->
                        <div id="analysis-input-section" class="mb-4">
                            <div class="flex items-center justify-between mb-2">
                                <label for="text-input" class="block text-sm font-medium text-slate-700">Văn bản cần phân tích</label>
                                <!-- File Upload Icon -->
                                <label for="file-upload"
                                    class="cursor-pointer text-indigo-600 hover:text-indigo-700 transition-colors"
                                    title="Tải tệp .txt lên">
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
                                placeholder="Dán văn bản cần phân tích vào đây…"><?= htmlspecialchars($taskContent) ?></textarea>
                        </div>

                        <!-- Chat Messages Area (Hidden initially) -->
                        <div id="chat-section" class="hidden mb-4">
                            <div id="chat-messages"
                                class="mb-4 space-y-3 max-h-[300px] overflow-y-auto border border-slate-200 rounded-lg p-3 bg-slate-50">
                                <div class="text-center text-slate-400 text-sm py-4">
                                    Đặt câu hỏi để AI giải thích thêm về bài phân tích.
                                </div>
                            </div>

                            <!-- Question Input -->
                            <div class="mb-4">
                                <label for="question-input" class="block text-sm font-medium text-slate-700 mb-2">Câu hỏi của bạn</label>
                                <textarea id="question-input" rows="4"
                                    class="shadow-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 block w-full text-sm border-slate-300 rounded-lg p-3 resize-y"
                                    placeholder="Ví dụ: Phân tích sâu hơn ý nghĩa biểu tượng trong bài…"></textarea>
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

                        <!-- Preview Button (shown after analysis) -->
                        <button id="preview-changes-button"
                            class="hidden mt-4 w-full flex justify-center items-center py-3 px-4 border border-purple-600 rounded-lg shadow-sm text-sm font-semibold text-purple-600 bg-white hover:bg-purple-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-purple-500 transition-colors">
                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24"
                                fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                stroke-linejoin="round" class="mr-2">
                                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                <circle cx="12" cy="12" r="3"></circle>
                            </svg>
                            <span>Xem trước chỉnh sửa</span>
                        </button>

                        <div class="flex flex-wrap items-center gap-3 mt-3">
                            <button type="button" id="export-analysis-btn" class="hidden text-sm font-semibold text-indigo-600 border border-indigo-200 rounded-lg px-3 py-1.5 bg-white" data-lang-key="genai_export">Xuất bài phân tích</button>
                        </div>

                        <!-- Change Statistics (shown after first save) -->
                        <div id="change-statistics" class="hidden mt-4 p-4 bg-gradient-to-r from-blue-50 to-purple-50 rounded-lg border border-blue-200">
                            <h4 class="text-sm font-bold text-slate-700 mb-3 flex items-center">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="mr-2">
                                    <path d="M21.21 15.89A10 10 0 1 1 8 2.83"></path>
                                    <path d="M22 12A10 10 0 0 0 12 2v10z"></path>
                                </svg>
                                <span>Thống kê chỉnh sửa</span>
                            </h4>
                            <div id="stats-content" class="space-y-2 text-sm">
                                <!-- Stats will be dynamically inserted here -->
                            </div>
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
                            <h3 class="text-lg font-semibold text-slate-700">Kết quả phân tích sẽ hiện ở đây</h3>
                            <p class="mt-1 text-sm">Dán văn bản hoặc tải tệp lên ở khung bên trái, rồi bấm “Phân tích”.</p>
                        </div>

                        <!-- Results with Tabs -->
                        <div id="results-tabs" class="genai-tabs hidden h-full flex flex-col">
                            <div class="genai-tabs__header">
                                <nav class="genai-tabs__nav" aria-label="Các thẻ kết quả">
                                    <button id="tab-analysis" type="button"
                                        class="genai-tabs__btn tab-button active" data-lang-key="detailed_analysis">
                                        <i class="bi bi-journal-text"></i><span>Phân tích chi tiết</span>
                                    </button>
                                    <button id="tab-slideshow" type="button" disabled
                                        class="hidden genai-tabs__btn tab-button" data-lang-key="slideshow">
                                        <i class="bi bi-easel"></i><span>Trình chiếu</span>
                                    </button>
                                    <button id="tab-quiz" type="button" disabled
                                        class="hidden genai-tabs__btn tab-button" data-lang-key="quiz">
                                        <i class="bi bi-patch-question"></i><span>Câu hỏi ôn tập</span>
                                    </button>
                                </nav>
                            </div>
                            <div class="genai-tabs__body flex-grow overflow-auto">
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
    require_once __DIR__ . '/../templates/footer.php';
    require_once __DIR__ . '/../templates/vendors.php';
    ?>
    <script src="<?= URL_ROOT ?>/assets/js/markdown-format.js"></script>
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
        const TASK_ID = <?= (int)$taskId ?>;
        const DELIVER_ID = <?= (int)$deliver_id ?>;
        let currentAnalysisData = <?php echo isset($json_data) ? json_encode($json_data) : 'null'; ?>;
        let conversationHistory = []; // Store chat history for refinement
        let isAnalyzed = <?php echo isset($json_data) && $json_data ? 'true' : 'false'; ?>; // Track if initial analysis is done
        let currentResponseId = <?php echo isset($id_response) ? $id_response : 'null'; ?>; // Track response ID from deliver
        let savedContent = <?php echo isset($savedContent) ? json_encode($savedContent) : 'null'; ?>; // Saved title and sections from database
        let originalAnalysisData = null; // Store original analysis data for comparison
        let originalContent = { title: '', sections: [] }; // Store original content from DOM

        // Copy/paste tracking state
        let copyTracker = {
            events: [],          // Individual copy events: {sectionId, copiedChars, totalChars, copyPercent, timestamp}
            totalCopyEvents: 0,
            sessionStartTime: Date.now(),
            saveEvents: 0
        };

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
                    UI.warning('Chỉ chấp nhận tệp văn bản (.txt). Vui lòng chọn tệp khác.');
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
                    UI.error('Không đọc được tệp. Vui lòng thử lại.');
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
                UI.warning('Bạn đã lưu bài phân tích cho nhiệm vụ này nên không thể phân tích lại.');
                return;
            }

            const textToAnalyze = textInput.value.trim();
            if (!textToAnalyze) {
                UI.warning('Vui lòng nhập văn bản hoặc tải tệp lên.');
                return;
            }
            setLoadingState(true, 'analyze');

            const analysisResult = await performMainAnalysis(textToAnalyze);

            if (analysisResult) {
                currentAnalysisData = analysisResult;
                isAnalyzed = true;
                originalAnalysisData = JSON.parse(JSON.stringify(analysisResult)); // Deep copy
                displayAnalysis(analysisResult);
                setupGeneratorTabs(); // Setup buttons for slideshow and quiz

                placeholder.classList.add('hidden');
                resultsTabs.classList.remove('hidden');
                switchTab(tabAnalysis);
                resultsTabs.querySelector('nav').classList.add('hidden');

                // Switch UI to chat mode
                switchToChatMode();

                document.getElementById('export-analysis-btn')?.classList.remove('hidden');
                if (window.GenAITools) {
                    GenAITools.scheduleAutosave(TASK_ID, DELIVER_ID, () => currentAnalysisData);
                    GenAITools.saveHistory(TASK_ID, DELIVER_ID, analysisResult);
                }

                // Add initial analysis to chat
                addChatMessage('assistant', 'Analysis completed. You can view the results on the right or ask questions to learn more.');
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
                // User has saved work - show "Sang phần bình luận" button instead of "Làm lại"
                resetButton.classList.remove('hidden');
                resetButton.removeAttribute('disabled');
                resetButton.innerHTML = `
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24"
                        fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                        stroke-linejoin="round" class="mr-2">
                        <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path>
                    </svg>
                    <span>Sang phần bình luận</span>
                `;
                resetButton.classList.remove('border-red-600', 'text-red-600', 'hover:bg-red-50', 'focus:ring-red-500');
                resetButton.classList.add('border-blue-600', 'text-blue-600', 'hover:bg-blue-50', 'focus:ring-blue-500');
            } else {
                // No saved work yet - show normal "Làm lại" button
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
                    <span>Đang lưu…</span>
                `;

                // Save current analysis first
                const saveResult = await handleSaveAnalysis();

                if (!saveResult || !saveResult.success) {
                    throw new Error('Không lưu được dữ liệu.');
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
                UI.error('Có lỗi khi lưu. Vui lòng thử lại.');

                // Restore button
                resetButton.disabled = false;
                resetButton.innerHTML = `
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24"
                        fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                        stroke-linejoin="round" class="mr-2">
                        <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path>
                    </svg>
                    <span>Sang phần bình luận</span>
                `;
            }
        }

        async function handleReset() {
            // Security check: prevent unauthorized reset
            if (!isAnalyzed) {
                UI.warning('Chưa thể thực hiện. Vui lòng phân tích văn bản trước.');
                return;
            }

            if (!await UI.confirm({ title: 'Làm lại từ đầu?', message: 'Toàn bộ dữ liệu phân tích sẽ bị xoá.', confirmText: 'Làm lại', danger: true })) {
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
            chatMessages.innerHTML = `<div class="text-center text-slate-400 text-sm py-4">Đặt câu hỏi để AI giải thích thêm về bài phân tích.</div>`;

            // Reset file upload
            fileUpload.value = '';
            fileInfo.classList.add('hidden');

            if (window.GenAITools && TASK_ID && DELIVER_ID) {
                GenAITools.clearAllHistory(TASK_ID, DELIVER_ID).catch((e) => console.warn('Clear history failed', e));
            }
            try {
                localStorage.removeItem('task_cache_' + TASK_ID + '_' + DELIVER_ID);
                localStorage.removeItem('genai_history_' + TASK_ID + '_' + DELIVER_ID);
            } catch (e) { /* ignore */ }
        }

        // Handle chat submission for refinement
        async function handleChatSubmit() {
            // Security check: prevent unauthorized chat submission
            if (!isAnalyzed) {
                UI.warning('Chưa thể thực hiện. Vui lòng phân tích văn bản trước.');
                return;
            }

            const userMessage = questionInput.value.trim();
            if (!userMessage) {
                UI.warning('Vui lòng nhập câu hỏi.');
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
                const response = await fetch('<?= URL_ROOT ?>/api/analyze_task.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        action: 'chat',
                        task_id: TASK_ID,
                        deliver_id: DELIVER_ID,
                        data: userMessage,
                        history: conversationHistory
                    })
                });

                if (!response.ok) {
                    const err = await response.json();
                    throw new Error(err.message || `API call failed: ${response.status}`);
                }
                const result = await response.json();
                if (result.error) throw new Error(result.error);

                const responseText = result.answer || result.response || result.message || 'Đã xử lý yêu cầu.';
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
                addChatMessage('system', 'Có lỗi xảy ra. Vui lòng thử lại.');
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
                        Question: ${escapeHtml(question)}
                    </h3>
                    <textarea class="editable-content w-full p-3 border border-indigo-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 text-sm bg-white">${escapeHtml(answer)}</textarea>
                    <div class="mt-3 text-xs text-slate-500 flex items-center">
                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="mr-1">
                            <circle cx="12" cy="12" r="10"></circle>
                            <polyline points="12 6 12 12 16 14"></polyline>
                        </svg>
                        ${new Date().toLocaleTimeString('en-US')}
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
        }

        // Handle save analysis changes
        async function handleSaveAnalysis() {
            try {
                // Get task_id and deliver_id from URL params
                const urlParams = new URLSearchParams(window.location.search);
                const taskId = urlParams.get('task_id');
                const deliverId = urlParams.get('deliver_id');

                if (!taskId || !deliverId) {
                    UI.warning('Thiếu thông tin. Vui lòng mở từ đường dẫn của nhiệm vụ.');
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
                    UI.warning('Chưa có nội dung để lưu.');
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
                    Đang lưu…
                `;

                // Prepare AI history data
                const aiHistory = {
                    analysisData: currentAnalysisData,
                    conversationHistory: conversationHistory,
                    savedAt: new Date().toISOString()
                };

                // Send to backend API
                const response = await fetch('<?= URL_ROOT ?>/api/save_analysis.php', {
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
                    throw new Error(result.message || 'Lưu không thành công.');
                }

                // Store response_id for future reference
                if (result.response_id) {
                    currentResponseId = result.response_id;

                    // Show preview button and update statistics
                    const previewBtn = document.getElementById('preview-changes-button');
                    if (previewBtn) {
                        previewBtn.classList.remove('hidden');
                        console.log('✅ Preview button shown after save');
                    }

                    // Update change statistics
                    console.log('🔄 Calling updateChangeStatistics()...');
                    const changeStats = updateChangeStatistics();

                    // Send tracking data (copy events + change stats) to backend
                    copyTracker.saveEvents++;
                    sendTrackingData(
                        changeStats ? changeStats.overallChange : 0,
                        changeStats ? changeStats.changedSections : 0
                    );

                    // Update originalContent to current state after successful save
                    storeOriginalContent();
                    console.log('💾 Original content updated');

                    // Update UI to show "Sang phần bình luận" button if not already done
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
                UI.networkError(error, 'Không lưu được. Vui lòng kiểm tra kết nối và thử lại.');

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
                    <button class="delete-section-btn" onclick="deleteSection('${newSectionId}')" title="Xoá phần này">
                        <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="display: inline; vertical-align: middle;">
                            <polyline points="3 6 5 6 21 6"></polyline>
                            <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                        </svg>
                        Xoá
                    </button>
                </div>
                <div class="mb-6">
                    <textarea class="editable-content w-full p-3 border border-slate-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 text-sm" placeholder="Nhập nội dung mới…">New Title:\n\nContent of new section...</textarea>
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
        async function deleteSection(sectionId) {
            if (!await UI.confirm('Xoá phần này?')) {
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
            const targetTab = e.target.closest('.genai-tabs__btn, .tab-button') || e.target;
            if (!targetTab || !tabs.includes(targetTab)) return;
            if ((targetTab === tabSlideshow || targetTab === tabQuiz) && !isAnalyzed) {
                UI.warning('Chưa thể thực hiện. Vui lòng phân tích văn bản trước.');
                return;
            }

            switchTab(targetTab);
        }

        async function handleGenerateSlideshow() {
            // Security check: Prevent unauthorized slideshow generation
            if (!isAnalyzed || !currentAnalysisData) {
                UI.warning('Chưa thể thực hiện. Vui lòng phân tích văn bản trước.');
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
                container.innerHTML = `<p class="text-red-500">Không tạo được trình chiếu. Vui lòng thử lại.</p>`;
            }
        }

        async function handleGenerateQuiz() {
            // Security check: Prevent unauthorized quiz generation
            if (!isAnalyzed || !currentAnalysisData) {
                UI.warning('Chưa thể thực hiện. Vui lòng phân tích văn bản trước.');
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
                container.innerHTML = `<p class="text-red-500">Không tạo được câu hỏi ôn tập. Vui lòng thử lại.</p>`;
            }
        }

        // --- UI Logic ---
        function setLoadingState(isLoading, mode = 'analyze') {
            if (mode === 'analyze') {
                if (isLoading) {
                    loadingSpinner.classList.remove('hidden');
                    analyzeButton.querySelector('#analyze-icon').classList.add('hidden');
                    buttonText.textContent = 'Analyzing...';
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
                    chatButtonText.textContent = 'Processing...';
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
                        <div class="bg-white text-slate-800 px-3 py-2 rounded-lg max-w-[85%] text-sm border border-slate-200 ai-markdown-bubble">
                            ${formatAiMarkdown(message)}
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
            return typeof formatAiMarkdown === 'function' ? formatAiMarkdown(text) : escapeHtml(text || '');
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
            contentSlideshow.innerHTML = `<div class="genai-panel-empty">
                <div class="genai-panel-empty__icon"><i class="bi bi-easel"></i></div>
                <h3>Tạo trình chiếu</h3>
                <p>Biến bài phân tích thành vài trang trình chiếu ngắn.</p>
                <button id="generate-slideshow-button" type="button" class="genai-btn-primary"><i class="bi bi-play-circle"></i> Tạo trình chiếu</button>
            </div>`;
            contentQuiz.innerHTML = `<div class="genai-panel-empty">
                <div class="genai-panel-empty__icon"><i class="bi bi-patch-question"></i></div>
                <h3>Tạo câu hỏi ôn tập</h3>
                <p>Tạo câu hỏi luyện tập từ bài phân tích.</p>
                <button id="generate-quiz-button" type="button" class="genai-btn-primary"><i class="bi bi-lightning"></i> Tạo câu hỏi ôn tập</button>
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
                const response = await fetch('<?= URL_ROOT ?>/api/analyze_task.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        action: action,
                        task_id: TASK_ID,
                        deliver_id: DELIVER_ID,
                        data: data
                    })
                });
                if (!response.ok) {
                    const err = await response.json();
                    throw new Error(err.message || `API call failed: ${response.status}`);
                }
                const result = await response.json();
                if (result.error) throw new Error(result.error);
                return result;
            } catch (error) {
                console.error("Error during API call:", error);
                UI.error('Không kết nối được máy chủ. Vui lòng thử lại.');
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
            const stopWords = new Set(['the', 'of', 'is', 'have', 'in', 'for', 'when', 'then', 'which', 'with', 'as', 'been', 'one', 'to', 'not', 'about', 'at', 'out', 'this', 'that', 'he', 'she', 'mr', 'mrs']);
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
                    .replace(/<\/?(sin|em|b|i|p|div|span|ul|li)>/gi, '')
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
                        <button class="delete-section-btn" onclick="deleteSection('${id}')" title="Xoá phần này">
                            <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="display: inline; vertical-align: middle;">
                                <polyline points="3 6 5 6 21 6"></polyline>
                                <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                            </svg>
                            Xoá
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
                    <p class="text-sm text-slate-600 mt-1">Bạn có thể sửa, thêm mới hoặc kéo để sắp xếp lại các phần</p>
                </div>
                <div class="flex gap-2">
                    <button id="add-section-button" class="px-4 py-2 bg-blue-600 text-white font-semibold rounded-lg hover:bg-blue-700 focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-colors flex items-center gap-2">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <line x1="12" y1="5" x2="12" y2="19"></line>
                            <line x1="5" y1="12" x2="19" y2="12"></line>
                        </svg>
                        Thêm phần
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
                <input type="text" class="editable-title w-full p-3 border border-slate-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 text-2xl font-bold" value="${escapeHtml(result.title || 'Chưa có tiêu đề')}">
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

            html += `<h2 class="text-xl font-bold mt-6 mb-4 text-slate-800">Thống kê ngôn ngữ</h2><div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">`;
            if (result.stats) {
                html += `<div class="bg-slate-100 p-3 rounded-lg"><strong class="block text-sm text-slate-600">Nhân vật</strong><span class="text-indigo-700 font-semibold">${result.stats.charCount.toLocaleString('en-US')}</span></div>`;
                html += `<div class="bg-slate-100 p-3 rounded-lg"><strong class="block text-sm text-slate-600">Từ</strong><span class="text-indigo-700 font-semibold">${result.stats.wordCount.toLocaleString('en-US')}</span></div>`;
                html += `<div class="bg-slate-100 p-3 rounded-lg"><strong class="block text-sm text-slate-600">Từ xuất hiện nhiều</strong><span class="text-indigo-700 font-semibold">${result.stats.topWords.map(w => w[0]).join(', ')}</span></div>`;
            }
            html += `</div>`;

            if (result.keywords && result.keywords.length > 0) {
                html += `<h2 class="text-xl font-bold mt-6 mb-4 text-slate-800">Từ khoá</h2><div class="flex flex-wrap gap-2 mb-6">`;
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
                        const prefix = index === 0 ? `Analysis Chung:\n\n` : '';
                        const combinedText = `${prefix}${section.heading}\n\n${section.content}`;
                        html += createSortableSection(`<div class="mb-6">${createEditableTextarea(combinedText, 4)}</div>`, `ga-adde-${index}`);
                    });
                }

                // Linguistic Insights within General Analysis
                if (ga.linguisticInsights) {
                    const insights = ga.linguisticInsights;

                    if (insights.motifs?.length > 0) {
                        const combinedText = `Recurring Motifs:\n\n${insights.motifs.join('\n')}`;
                        html += createSortableSection(`<div class="mb-4">${createEditableTextarea(combinedText, 3)}</div>`, 'ga-motifs');
                    }
                    if (insights.symbolsAndMetaphors?.length > 0) {
                        const combinedText = `Symbols & Metaphors:\n\n${insights.symbolsAndMetaphors.join('\n')}`;
                        html += createSortableSection(`<div class="mb-4">${createEditableTextarea(combinedText, 3)}</div>`, 'ga-symbols');
                    }
                    if (insights.temporalElements) {
                        const combinedText = `Temporal Elements:\n\n${insights.temporalElements}`;
                        html += createSortableSection(`<div class="mb-4">${createEditableTextarea(combinedText, 2)}</div>`, 'ga-temporal');
                    }
                    if (insights.natureAndScenery) {
                        const combinedText = `Nature & Scenery:\n\n${insights.natureAndScenery}`;
                        html += createSortableSection(`<div class="mb-4">${createEditableTextarea(combinedText, 2)}</div>`, 'ga-nature');
                    }
                }
            }


            // --- Genre-Specific Analysis ---
            if (result.storyAnalysis) {
                const sa = result.storyAnalysis;
                let isFirstStorySection = true;

                if (sa.character) {
                    const prefix = isFirstStorySection ? `Story Analysis:\n\n` : '';
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
                const has = result.dramaAnalysis;
                let isFirstDramaSection = true;

                if (has.characters) {
                    const prefix = isFirstDramaSection ? `Drama Analysis:\n\n` : '';
                    isFirstDramaSection = false;
                    html += `<div class="mb-6">${formatDetailedAnalysis(prefix + has.characters)}</div>`;
                }
                if (has.conflict) html += `<div class="mb-6">${formatDetailedAnalysis(has.conflict)}</div>`;
                if (has.action) html += `<div class="mb-6">${formatDetailedAnalysis(has.action)}</div>`;
                if (has.structure) html += `<div class="mb-6">${formatDetailedAnalysis(has.structure)}</div>`;
                if (has.dialogue) html += `<div class="mb-6">${formatDetailedAnalysis(has.dialogue)}</div>`;
            }

            if (result.poetryAnalysis) {
                const pa = result.poetryAnalysis;
                let isFirstPoetrySection = true;

                if (pa.rhyming) {
                    const prefix = isFirstPoetrySection ? `Poetry Analysis:\n\n` : '';
                    isFirstPoetrySection = false;
                    const combinedText = `${prefix}Rhyming in Poetry:\n\n${pa.rhyming}`;
                    html += createSortableSection(`<div class="mb-6">${createEditableTextarea(combinedText, 3)}</div>`, 'pa-rhyming');
                }
                if (pa.emotionalFlow) {
                    const combinedText = `Emotional Flow:\n\n${pa.emotionalFlow}`;
                    html += createSortableSection(`<div class="mb-6">${createEditableTextarea(combinedText, 3)}</div>`, 'pa-emotion');
                }
                if (pa.structure) {
                    const combinedText = `Poem Structure:\n\n${pa.structure}`;
                    html += createSortableSection(`<div class="mb-6">${createEditableTextarea(combinedText, 3)}</div>`, 'pa-structure');
                }
                if (pa.imagery) {
                    const combinedText = `Imagery in Poetry:\n\n${pa.imagery}`;
                    html += createSortableSection(`<div class="mb-6">${createEditableTextarea(combinedText, 3)}</div>`, 'pa-imagery');
                }
                if (pa.rhythm) {
                    const combinedText = `Rhythm Analysis:\n\n${pa.rhythm}`;
                    html += createSortableSection(`<div class="mb-6">${createEditableTextarea(combinedText, 4)}</div>`, 'pa-rhythm');
                }
                if (pa.spaceTimeOrganization) {
                    const combinedText = `Space & Time Organization:\n\n${pa.spaceTimeOrganization}`;
                    html += createSortableSection(`<div class="mb-6">${createEditableTextarea(combinedText, 3)}</div>`, 'pa-spacetime');
                }
                if (pa.rhetoricalDevices) {
                    const combinedText = `Rhetorical Devices:\n\n${pa.rhetoricalDevices}`;
                    html += createSortableSection(`<div class="mb-6">${createEditableTextarea(combinedText, 3)}</div>`, 'pa-rhetorical');
                }
            }

            // --- Concluding Sections ---
            if (result.concludingThoughts) {
                const ct = result.concludingThoughts;
                if (ct.lesson) {
                    const combinedText = `Lesson & Message:\n\nLesson:\n\n${ct.lesson}`;
                    html += createSortableSection(`<div class="mb-6">${createEditableTextarea(combinedText, 3)}</div>`, 'ct-lesson');
                }
                if (ct.message) {
                    const combinedText = `Message:\n\n${ct.message}`;
                    html += createSortableSection(`<div class="mb-6">${createEditableTextarea(combinedText, 3)}</div>`, 'ct-message');
                }
            }

            if (result.intertextuality) {
                const it = result.intertextuality;
                if (it.initialAnalysis) {
                    const combinedText = `Analysis Intertextuality:\n\n${it.initialAnalysis}`;
                    html += createSortableSection(`<div class="mb-6">${createEditableTextarea(combinedText, 3)}</div>`, 'it-initial');
                }
                if (it.comparativeText) {
                    html += `<h3 class="text-lg font-semibold mb-3 text-slate-700">So sánh với tác phẩm tương tự: <em>${it.comparativeText.title}</em> - ${it.comparativeText.author}</h3>`;
                }
                if (it.comparison) {
                    if (it.comparison.similarities) {
                        const combinedText = `Similarities:\n\n${it.comparison.similarities}`;
                        html += createSortableSection(`<div class="mb-6">${createEditableTextarea(combinedText, 3)}</div>`, 'it-similarities');
                    }
                    if (it.comparison.differences) {
                        const combinedText = `Differences/Changes:\n\n${it.comparison.differences}`;
                        html += createSortableSection(`<div class="mb-6">${createEditableTextarea(combinedText, 3)}</div>`, 'it-differences');
                    }
                    if (it.comparison.effects) {
                        const combinedText = `Effects:\n\n${it.comparison.effects}`;
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

            // Add event listener for preview button
            const previewButton = document.getElementById('preview-changes-button');
            console.log('🔍 Preview button found:', previewButton);
            if (previewButton) {
                previewButton.addEventListener('click', showPreviewModal);
                // Show preview button after analysis is complete
                previewButton.classList.remove('hidden');
                console.log('✅ Preview button displayed!');
            } else {
                console.error('❌ Preview button NOT found in DOM!');
            }

            // Store original content after rendering
            storeOriginalContent();

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
                    parts: [{ text: `Analyze the following text:\n\n${textInput.value.trim()}` }]
                },
                {
                    role: 'model',
                    parts: [{ text: JSON.stringify(result) }]
                }
            ];

            // Start tracking copy events inside the analysis editor
            initializeCopyTracking();
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
                    <h2 class="text-2xl font-bold">${currentAnalysisData.title || 'Trình chiếu'}</h2>
                    <p class="text-slate-600">Tóm tắt nội dung bài phân tích dưới dạng trình chiếu.</p>
                </div>
                <div id="slideshow-container" class="bg-white rounded-xl shadow-inner">${slideHTML}</div>
                <div class="flex items-center justify-between mt-4">
                    <button id="prev-slide" class="px-4 py-2 bg-slate-200 hover:bg-slate-300 rounded-lg font-semibold text-slate-700">&larr; Previous</button>
                    <span id="slide-counter" class="text-sm font-medium text-slate-600">1 / ${totalSlides}</span>
                    <button id="next-slide" class="px-4 py-2 bg-slate-200 hover:bg-slate-300 rounded-lg font-semibold text-slate-700">Next &rarr;</button>
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
                    <h2 class="text-2xl font-bold">Quiz: ${result.title || 'Văn bản'}</h2>
                    <p class="text-slate-600">Kiểm tra mức hiểu tác phẩm của bạn.</p>
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
                <button id="submit-quiz" class="px-6 py-2 bg-indigo-600 text-white font-semibold rounded-lg hover:bg-indigo-700">Gửi</button>
                <div id="quiz-results" class="mt-4 text-lg font-bold"></div>
            </div>`;
            contentQuiz.innerHTML = quizHTML;

            const submitButton = contentQuiz.querySelector('#submit-quiz');
            const resultsDiv = contentQuiz.querySelector('#quiz-results');

            submitButton.addEventListener('click', () => {
                let score = 0;
                result.quiz.forEach((item, index) => {
                    const selected = contentQuiz.querySelector(`input[name="question-${index}"]:checked`);
                    const questionBlock = selected.closest('.question-block');
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
                resultsDiv.textContent = `Điểm của bạn: ${score} / ${result.quiz.length}`;
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

                const response = await fetch(`<?= URL_ROOT ?>/api/load_analysis.php?task_id=${taskId}&deliver_id=${deliverId}`);
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
                        <button class="delete-section-btn" onclick="deleteSection('${id}')" title="Xoá phần này">
                            <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="display: inline; vertical-align: middle;">
                                <polyline points="3 6 5 6 21 6"></polyline>
                                <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                            </svg>
                            Xoá
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
                    <p class="text-sm text-slate-600 mt-1">Bạn có thể sửa, thêm mới hoặc kéo để sắp xếp lại các phần</p>
                </div>
                <div class="flex gap-2">
                    <button id="add-section-button" class="px-4 py-2 bg-blue-600 text-white font-semibold rounded-lg hover:bg-blue-700 focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-colors flex items-center gap-2">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <line x1="12" y1="5" x2="12" y2="19"></line>
                            <line x1="5" y1="12" x2="19" y2="12"></line>
                        </svg>
                        Thêm phần
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
                <input type="text" class="editable-title w-full p-3 border border-slate-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 text-2xl font-bold" value="${escapeHtml(title || 'Chưa có tiêu đề')}">
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

            // Start tracking copy events inside the analysis editor
            initializeCopyTracking();
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

        // Store original content from DOM
        function storeOriginalContent() {
            const titleElement = document.querySelector('.editable-title');
            const sectionElements = document.querySelectorAll('.sortable-section .editable-content');
            
            originalContent.title = titleElement ? titleElement.value : '';
            originalContent.sections = [];
            
            sectionElements.forEach((section) => {
                if (section.value && section.value.trim()) {
                    originalContent.sections.push(section.value.trim());
                }
            });
        }

        // Check if content has changed
        function hasContentChanged() {
            const currentTitle = document.querySelector('.editable-title')?.value || '';
            const currentSections = [];
            
            document.querySelectorAll('.sortable-section .editable-content').forEach((section) => {
                if (section.value && section.value.trim()) {
                    currentSections.push(section.value.trim());
                }
            });

            // Compare title
            if (currentTitle !== originalContent.title) {
                return true;
            }

            // Compare sections count
            if (currentSections.length !== originalContent.sections.length) {
                return true;
            }

            // Compare each section
            for (let i = 0; i < currentSections.length; i++) {
                if (currentSections[i] !== originalContent.sections[i]) {
                    return true;
                }
            }

            return false;
        }

        // Calculate change statistics
        function calculateChangePercentage(original, current) {
            if (!original && !current) return 0;
            if (!original) return 100;
            if (original === current) return 0;

            // Calculate Levenshtein distance for more accurate change detection
            const maxLen = Math.max(original.length, current.length);
            if (maxLen === 0) return 0;

            // Simple character difference percentage
            let changes = 0;
            const minLen = Math.min(original.length, current.length);
            
            for (let i = 0; i < minLen; i++) {
                if (original[i] !== current[i]) changes++;
            }
            
            // Add difference in length
            changes += Math.abs(original.length - current.length);
            
            return Math.min(100, Math.round((changes / maxLen) * 100));
        }

        // Initialize copy event tracking via event delegation on the analysis container
        function initializeCopyTracking() {
            const container = document.getElementById('content-analysis');
            if (!container || container._copyTrackingInit) return;
            container._copyTrackingInit = true;

            // Helper: record a copy event from a textarea target
            const recordCopy = (target) => {
                const totalText = target.value || '';
                const start = target.selectionStart || 0;
                const end = target.selectionEnd || 0;
                const copiedText = totalText.substring(start, end);

                if (!copiedText || copiedText.length === 0) return null;

                const copiedChars = copiedText.length;
                const totalChars = totalText.length || 1;
                const copyPercent = Math.min(100, Math.round((copiedChars / totalChars) * 100));

                const ev = {
                    sectionId: target.closest('[data-section-id]')?.dataset.sectionId || 'title',
                    copiedChars,
                    totalChars,
                    copyPercent,
                    timestamp: new Date().toISOString()
                };

                copyTracker.events.push(ev);
                copyTracker.totalCopyEvents++;
                console.log(`📋 Copy detected: ${copiedChars}/${totalChars} chars (${copyPercent}%)`, ev.sectionId);
                return ev;
            };

            // 'copy' event — fired for both right-click Copy and Ctrl+C
            container.addEventListener('copy', function (e) {
                const target = e.target;
                if (!target.matches('.editable-content, .editable-title')) return;
                recordCopy(target);
            });

            // Explicit Ctrl+C / Cmd+C keydown on the document (catches cases where
            // the 'copy' event is suppressed or fired before the selection is finalized)
            container.addEventListener('keydown', function (e) {
                if (!((e.ctrlKey || e.metaKey) && e.key === 'c')) return;
                const target = e.target;
                if (!target.matches('.editable-content, .editable-title')) return;

                setTimeout(() => {
                    // Only record if no 'copy' event already fired within 100 ms for this target
                    const lastEv = copyTracker.events[copyTracker.events.length - 1];
                    const sectionId = target.closest('[data-section-id]')?.dataset.sectionId || 'title';
                    const now = new Date().toISOString();
                    const alreadyRecorded = lastEv && lastEv.sectionId === sectionId &&
                        (new Date(now) - new Date(lastEv.timestamp)) < 100;
                    if (!alreadyRecorded) {
                        recordCopy(target);
                    }
                }, 0);
            });
        }

        // Build the globalSummary object for sending to backend
        function buildTrackingData(overallChangePercent, changedSectionsCount, totalSections) {
            const sessionDuration = Math.round((Date.now() - copyTracker.sessionStartTime) / 1000);
            const avgCopyPercent = copyTracker.events.length > 0
                ? Math.round(copyTracker.events.reduce((sum, e) => sum + e.copyPercent, 0) / copyTracker.events.length)
                : 0;

            return {
                copyEvents: copyTracker.events.map(e => ({
                    sectionId: e.sectionId,
                    copyPercent: e.copyPercent,
                    timestamp: e.timestamp
                })),
                globalSummary: {
                    averageEditChangePercent: overallChangePercent,
                    sectionsEdited: changedSectionsCount,
                    totalCopyEvents: copyTracker.totalCopyEvents,
                    totalCopyPercent: avgCopyPercent,
                    sessionDuration: sessionDuration,
                    totalSaveEvents: copyTracker.saveEvents
                }
            };
        }

        // Send tracking data to backend
        async function sendTrackingData(overallChangePercent, changedSectionsCount) {
            if (!currentResponseId) return;

            const urlParams = new URLSearchParams(window.location.search);
            const taskId = urlParams.get('task_id');
            const deliverId = urlParams.get('deliver_id');
            if (!taskId || !deliverId) return;

            const trackingPayload = buildTrackingData(overallChangePercent, changedSectionsCount);

            try {
                await fetch('<?= URL_ROOT ?>/api/save_response.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        task_id: parseInt(taskId),
                        deliver_id: parseInt(deliverId),
                        action_type: 'tracking',
                        tracking_data: trackingPayload
                    })
                });
                console.log('✅ Tracking data sent to backend');
            } catch (err) {
                console.warn('⚠️ Failed to send tracking data:', err);
            }
        }

        // Update change statistics display
        function updateChangeStatistics() {
            console.log('🔍 updateChangeStatistics() called');
            const statsContainer = document.getElementById('change-statistics');
            const statsContent = document.getElementById('stats-content');
            
            console.log('📊 Stats container:', statsContainer);
            console.log('📊 Stats content:', statsContent);
            
            if (!statsContainer || !statsContent) {
                console.error('❌ Stats container or content not found!');
                return;
            }

            // Get current content
            const currentTitle = document.querySelector('.editable-title')?.value || '';
            const currentSections = [];
            
            document.querySelectorAll('.sortable-section .editable-content').forEach((section) => {
                if (section.value && section.value.trim()) {
                    currentSections.push(section.value.trim());
                }
            });

            // Calculate changes
            const titleChange = calculateChangePercentage(originalContent.title, currentTitle);
            let totalOriginalChars = originalContent.title.length;
            let totalCurrentChars = currentTitle.length;
            let totalChangePercent = 0;
            let changedSections = 0;

            const sectionChanges = [];
            for (let i = 0; i < Math.max(originalContent.sections.length, currentSections.length); i++) {
                const origSection = originalContent.sections[i] || '';
                const currSection = currentSections[i] || '';
                const changePercent = calculateChangePercentage(origSection, currSection);
                
                totalOriginalChars += origSection.length;
                totalCurrentChars += currSection.length;
                
                if (changePercent > 0) {
                    changedSections++;
                    sectionChanges.push({ index: i + 1, percent: changePercent });
                }
            }

            // Calculate overall change
            const overallChange = calculateChangePercentage(
                originalContent.title + originalContent.sections.join(''),
                currentTitle + currentSections.join('')
            );

            // Build statistics HTML
            let statsHTML = `
                <div class="flex items-center justify-between p-2 bg-white rounded border border-blue-100">
                    <span class="font-medium text-slate-700">Tỉ lệ thay đổi:</span>
                    <div class="flex items-center gap-2">
                        <div class="w-24 h-2 bg-slate-200 rounded-full overflow-hidden">
                            <div class="h-full bg-gradient-to-r from-blue-500 to-purple-500 transition-all" style="width: ${overallChange}%"></div>
                        </div>
                        <span class="font-bold text-${overallChange > 50 ? 'purple' : overallChange > 20 ? 'blue' : 'slate'}-600">${overallChange}%</span>
                    </div>
                </div>
            `;

            if (titleChange > 0) {
                statsHTML += `
                    <div class="flex items-center justify-between p-2 bg-white rounded border border-blue-100">
                        <span class="text-sm text-slate-600">Tiêu đề:</span>
                        <span class="text-sm font-semibold text-blue-600">${titleChange}%</span>
                    </div>
                `;
            }

            if (changedSections > 0) {
                statsHTML += `
                    <div class="flex items-center justify-between p-2 bg-white rounded border border-blue-100">
                        <span class="text-sm text-slate-600">Số phần đã sửa:</span>
                        <span class="text-sm font-semibold text-purple-600">${changedSections}/${currentSections.length} sections</span>
                    </div>
                `;
            }

            const charDiff = totalCurrentChars - totalOriginalChars;
            if (charDiff !== 0) {
                statsHTML += `
                    <div class="flex items-center justify-between p-2 bg-white rounded border border-blue-100">
                        <span class="text-sm text-slate-600">Số ký tự thay đổi:</span>
                        <span class="text-sm font-semibold ${charDiff > 0 ? 'text-green-600' : 'text-red-600'}">${charDiff > 0 ? '+' : ''}${charDiff} characters</span>
                    </div>
                `;
            }

            // Copy/paste statistics
            if (copyTracker.totalCopyEvents > 0) {
                const avgCopyPercent = Math.round(
                    copyTracker.events.reduce((sum, e) => sum + e.copyPercent, 0) / copyTracker.events.length
                );
                statsHTML += `
                    <div class="flex items-center justify-between p-2 bg-orange-50 rounded border border-orange-200 mt-1">
                        <span class="text-sm text-slate-600 flex items-center gap-1">
                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="9" y="9" width="13" height="13" rx="2" ry="2"></rect><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"></path></svg>
                            Total copy times:
                        </span>
                        <span class="text-sm font-semibold text-orange-600">${copyTracker.totalCopyEvents} times</span>
                    </div>
                    <div class="flex items-center justify-between p-2 bg-orange-50 rounded border border-orange-200">
                        <span class="text-sm text-slate-600">% avg text copied:</span>
                        <span class="text-sm font-semibold text-orange-600">${avgCopyPercent}%</span>
                    </div>
                `;
            }

            statsContent.innerHTML = statsHTML;
            statsContainer.classList.remove('hidden');
            console.log('✅ Stats updated! HTML:', statsHTML.substring(0, 100) + '...');
            console.log('✅ Stats container now visible');

            return { overallChange, changedSections };
        }

        // Show preview modal with before/after comparison
        function showPreviewModal() {
            const currentTitle = document.querySelector('.editable-title')?.value || '';
            const currentSections = [];
            
            document.querySelectorAll('.sortable-section .editable-content').forEach((section) => {
                if (section.value && section.value.trim()) {
                    currentSections.push(section.value.trim());
                }
            });

            let comparisonHTML = `
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Before Column -->
                    <div>
                        <h3 class="text-lg font-bold text-red-700 mb-4 flex items-center">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="mr-2">
                                <circle cx="12" cy="12" r="10"></circle>
                                <line x1="15" y1="9" x2="9" y2="15"></line>
                                <line x1="9" y1="9" x2="15" y2="15"></line>
                            </svg>
                            Trước khi sửa
                        </h3>
                        <div class="space-y-4">
                            ${originalContent.title !== currentTitle ? 
                                `<div class="bg-red-50 border border-red-200 rounded-lg p-4">
                                    <div class="text-xs font-semibold text-red-600 mb-2">TITLE</div>
                                    <div class="text-sm whitespace-pre-wrap">${escapeHtml(originalContent.title)}</div>
                                </div>` : 
                                `<div class="bg-slate-50 border border-slate-200 rounded-lg p-4">
                                    <div class="text-xs font-semibold text-slate-600 mb-2">TITLE (unchanged)</div>
                                    <div class="text-sm whitespace-pre-wrap">${escapeHtml(originalContent.title)}</div>
                                </div>`
                            }
                            ${originalContent.sections.map((section, index) => {
                                const hasChanged = currentSections[index] !== section;
                                return `<div class="${hasChanged ? 'bg-red-50 border-red-200' : 'bg-slate-50 border-slate-200'} border rounded-lg p-4">
                                    <div class="text-xs font-semibold ${hasChanged ? 'text-red-600' : 'text-slate-600'} mb-2">SECTION ${index + 1}${hasChanged ? ' (changed)' : ''}</div>
                                    <div class="text-sm whitespace-pre-wrap">${escapeHtml(section)}</div>
                                </div>`;
                            }).join('')}
                        </div>
                    </div>

                    <!-- After Column -->
                    <div>
                        <h3 class="text-lg font-bold text-green-700 mb-4 flex items-center">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="mr-2">
                                <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                                <polyline points="22 4 12 14.01 9 11.01"></polyline>
                            </svg>
                            Sau khi sửa
                        </h3>
                        <div class="space-y-4">
                            ${originalContent.title !== currentTitle ? 
                                `<div class="bg-green-50 border border-green-200 rounded-lg p-4">
                                    <div class="text-xs font-semibold text-green-600 mb-2">TITLE</div>
                                    <div class="text-sm whitespace-pre-wrap">${escapeHtml(currentTitle)}</div>
                                </div>` : 
                                `<div class="bg-slate-50 border border-slate-200 rounded-lg p-4">
                                    <div class="text-xs font-semibold text-slate-600 mb-2">TITLE (unchanged)</div>
                                    <div class="text-sm whitespace-pre-wrap">${escapeHtml(currentTitle)}</div>
                                </div>`
                            }
                            ${currentSections.map((section, index) => {
                                const hasChanged = originalContent.sections[index] !== section;
                                const isNew = index >= originalContent.sections.length;
                                return `<div class="${hasChanged || isNew ? 'bg-green-50 border-green-200' : 'bg-slate-50 border-slate-200'} border rounded-lg p-4">
                                    <div class="text-xs font-semibold ${hasChanged || isNew ? 'text-green-600' : 'text-slate-600'} mb-2">SECTION ${index + 1}${isNew ? ' (new)' : hasChanged ? ' (changed)' : ''}</div>
                                    <div class="text-sm whitespace-pre-wrap">${escapeHtml(section)}</div>
                                </div>`;
                            }).join('')}
                        </div>
                    </div>
                </div>
            `;

            const modal = document.getElementById('preview-modal');
            const modalBody = document.getElementById('preview-modal-body');
            
            if (modal && modalBody) {
                modalBody.innerHTML = comparisonHTML;
                modal.classList.remove('hidden');
                document.body.style.overflow = 'hidden'; // Prevent background scrolling
            }
        }

        // Close preview modal
        function closePreviewModal() {
            const modal = document.getElementById('preview-modal');
            if (modal) {
                modal.classList.add('hidden');
                document.body.style.overflow = ''; // Restore scrolling
            }
        }
    </script>

    <!-- Preview Modal -->
    <div id="preview-modal" class="hidden fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <!-- Background overlay -->
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true" onclick="closePreviewModal()"></div>

            <!-- Modal panel -->
            <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-7xl sm:w-full">
                <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                    <!-- Modal Header -->
                    <div class="flex justify-between items-center mb-6 pb-4 border-b">
                        <h3 class="text-2xl font-bold text-slate-800 flex items-center" id="modal-title">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="mr-3 text-purple-600">
                                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                <circle cx="12" cy="12" r="3"></circle>
                            </svg>
                            Xem bản trước khi sửa
                        </h3>
                        <button type="button" class="text-gray-400 hover:text-gray-500 focus:outline-none" onclick="closePreviewModal()">
                            <svg class="h-6 w-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    <!-- Modal Body -->
                    <div id="preview-modal-body" class="max-h-[70vh] overflow-y-auto">
                        <!-- Content will be inserted by JavaScript -->
                    </div>
                </div>

                <!-- Modal Footer -->
                <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                    <button type="button" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-purple-600 text-base font-medium text-white hover:bg-purple-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-purple-500 sm:ml-3 sm:w-auto sm:text-sm" onclick="closePreviewModal()">
                        Đóng
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script src="<?= URL_ROOT ?>/assets/js/genai-tools.js"></script>
    <script>
    document.getElementById('export-analysis-btn')?.addEventListener('click', function () {
        if (currentAnalysisData && window.GenAITools) {
            GenAITools.exportAnalysisHtml(currentAnalysisData.title, currentAnalysisData);
        }
    });
    </script>
</body>

</html>