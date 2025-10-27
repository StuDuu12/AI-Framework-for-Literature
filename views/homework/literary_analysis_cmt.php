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
    <style>
        body {
            font-family: 'Inter', sans-serif;
        }

        .prose-custom {
            color: #374151;
        }

        .prose-custom h1 {
            color: #111827;
            font-weight: 700 !important;
        }

        .prose-custom h2 {
            color: #111827;
            font-weight: 700 !important;
            border-bottom: 1px solid #e5e7eb;
            padding-bottom: 0.5rem;
            margin-top: 2rem;
            margin-bottom: 1rem;
        }

        .prose-custom h3 {
            color: #1f2937;
            font-weight: 700 !important;
            margin-top: 1.5rem;
            margin-bottom: 0.75rem;
        }

        .prose-custom h4 {
            color: #374151;
            font-weight: 700 !important;
            margin-top: 1rem;
            margin-bottom: 0.5rem;
        }

        .prose-custom p {
            line-height: 1.6;
            margin-bottom: 1rem;
        }

        .prose-custom ul {
            list-style-position: inside;
            padding-left: 0;
            margin-left: 1rem;
        }

        .prose-custom li {
            margin-bottom: 0.5rem;
            list-style-type: none;
            /* Remove default bullet */
            position: relative;
            padding-left: 1.5rem;
            /* Add space for custom bullet */
        }

        /* Custom bullet point */
        .prose-custom li::before {
            content: '•';
            position: absolute;
            left: 0;
            top: 0;
            color: #4f46e5;
            /* Indigo color for bullet */
            font-weight: bold;
            display: inline-block;
            width: 1em;
            margin-left: -1.5em;
            /* Position bullet correctly */
        }

        /* Style for genre-specific analysis points */
        .analysis-point {
            margin-bottom: 1rem;
        }

        .analysis-point strong {
            display: block;
            margin-bottom: 0.25rem;
        }

        .analysis-point span {
            display: block;
            padding-left: 1.5rem;
            position: relative;
        }

        .analysis-point span::before {
            content: '•';
            position: absolute;
            left: 0;
            top: 0.1em;
            /* Adjust vertical alignment */
            color: #4f46e5;
            /* Indigo color for bullet */
            font-weight: bold;
            display: inline-block;
            width: 1em;
            margin-left: -0.2em;
            /* Adjust horizontal position */
        }

        .prose-custom strong {
            color: #1f2937;
        }

        .prose-custom i {
            font-style: italic;
            color: #4338ca;
        }

        .prose-custom blockquote {
            margin-top: 0.5rem;
            padding-left: 1rem;
            border-left: 4px solid #e5e7eb;
            color: #4b5563;
        }

        .prose-custom pre {
            background-color: #f3f4f6;
            padding: 1rem;
            border-radius: 0.5rem;
            white-space: pre-wrap;
            word-wrap: break-word;
        }

        .prose-custom code {
            font-family: monospace;
        }

        /* Custom scrollbar */
        ::-webkit-scrollbar {
            width: 8px;
        }

        ::-webkit-scrollbar-track {
            background: #f1f5f9;
        }

        ::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 10px;
        }

        ::-webkit-scrollbar-thumb:hover {
            background: #94a3b8;
        }

        /* Tab styles */
        .tab-button {
            transition: all 0.2s ease-in-out;
        }

        .tab-button.active {
            border-color: #4f46e5;
            color: #4f46e5;
            font-weight: 600;
        }

        /* Slideshow styles */
        #slideshow-container {
            overflow: hidden;
            position: relative;
        }

        #slide-wrapper {
            display: flex;
            transition: transform 0.5s ease-in-out;
        }

        .slide {
            min-width: 100%;
            box-sizing: border-box;
        }

        .loader-container {
            display: flex;
            justify-content: center;
            align-items: center;
            height: 200px;
        }

        /* Content Image Wrapper & Comment Styles */
        .content-image-wrapper {
            border: 2px solid #e2e8f0;
            border-radius: 0.75rem;
            overflow: hidden;
            cursor: pointer;
            transition: all 0.3s ease;
            background-color: white;
            margin-bottom: 1.5rem;
        }

        .content-image-wrapper:hover {
            border-color: #cbd5e1;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        }

        .content-image-wrapper.selected {
            border-color: #4f46e5;
            box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.1);
        }

        .content-image-wrapper img {
            width: 100%;
            display: block;
            border-bottom: 1px solid #e2e8f0;
        }

        /* Facebook-style Comment Block */
        .fb-comment-block {
            padding: 1rem;
            background-color: #f8fafc;
        }

        .fb-comments-list {
            max-height: 300px;
            overflow-y: auto;
            margin-bottom: 0.75rem;
        }

        .fb-comment-item {
            margin-bottom: 0.75rem;
        }

        .fb-comment-core {
            display: flex;
            gap: 0.5rem;
        }

        .fb-comment-core img {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            flex-shrink: 0;
            border: none;
        }

        .fb-comment-bubble {
            background-color: #e5e7eb;
            padding: 0.5rem 0.75rem;
            border-radius: 1rem;
            margin-bottom: 0.25rem;
        }

        .comment-author {
            font-weight: 600;
            font-size: 0.8125rem;
            color: #1f2937;
            margin-bottom: 0.125rem;
        }

        .comment-content {
            font-size: 0.875rem;
            color: #374151;
            line-height: 1.4;
        }

        .fb-comment-actions {
            display: flex;
            gap: 1rem;
            font-size: 0.75rem;
            padding-left: 0.75rem;
            align-items: center;
        }

        .fb-comment-actions button {
            color: #6b7280;
            font-weight: 600;
            cursor: pointer;
            background: none;
            border: none;
            padding: 0;
            transition: color 0.2s;
        }

        .fb-comment-actions button:hover {
            color: #4f46e5;
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
            gap: 0.5rem;
            align-items: center;
            background-color: white;
            padding: 0.5rem;
            border-radius: 1.5rem;
            border: 1px solid #e5e7eb;
        }

        .fb-comment-input-area img {
            width: 28px;
            height: 28px;
            border-radius: 50%;
            flex-shrink: 0;
            border: none;
        }

        .fb-comment-input {
            flex: 1;
            border: none;
            outline: none;
            font-size: 0.875rem;
            padding: 0.25rem 0.5rem;
            background: transparent;
        }

        .fb-comment-btn {
            background-color: #4f46e5;
            color: white;
            border: none;
            padding: 0.375rem 0.75rem;
            border-radius: 1rem;
            font-size: 0.8125rem;
            font-weight: 600;
            cursor: pointer;
            transition: background-color 0.2s;
        }

        .fb-comment-btn:hover {
            background-color: #4338ca;
        }

        .fb-comment-btn:disabled {
            background-color: #cbd5e1;
            cursor: not-allowed;
        }

        .fb-replies {
            margin-top: 0.5rem;
        }

        .fb-reply-form {
            margin-top: 0.5rem;
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
            <h1>Bộ Công cụ Phân tích Văn học</h1>
            <nav>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="?route=class">Lớp học</a></li>
                    <li class="breadcrumb-item active">Phân tích Văn học</li>
                </ol>
            </nav>
        </div><!-- End Page Title -->

        <section class="section">
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 h-full">

                <!-- Left Panel: Input -->
                <div class="lg:col-span-4 h-full">
                    <div class="bg-white p-6 rounded-xl shadow-md sticky top-24">
                        <h2 class="text-lg font-semibold mb-4 text-slate-800">Nhập văn bản cần phân tích</h2>

                        <!-- File Upload -->
                        <div class="mb-4">
                            <label for="file-upload" class="block text-sm font-medium text-slate-700 mb-2">Tải lên tệp tin:</label>
                            <label for="file-upload" class="relative cursor-pointer bg-white rounded-md font-medium text-indigo-600 hover:text-indigo-500 border border-dashed border-slate-300 p-4 flex justify-center items-center flex-col min-h-[150px]">
                                <div id="upload-prompt" class="text-center">
                                    <svg class="mx-auto h-12 w-12 text-slate-400" stroke="currentColor" fill="none" viewBox="0 0 48 48" aria-hidden="true">
                                        <path d="M28 8H12a4 4 0 00-4 4v20m32-12v8m0 0v8a4 4 0 01-4 4H12a4 4 0 01-4-4v-4m32-4l-3.172-3.172a4 4 0 00-5.656 0L28 28M8 32l9.172-9.172a4 4 0 015.656 0L28 28m0 0l4 4m4-24h8m-4-4v8" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                    </svg>
                                    <span class="mt-2 block text-sm">Nhấn để tải lên</span>
                                    <p class="text-xs text-slate-500">DOCX, PDF, TXT</p>
                                </div>
                                <div id="file-info" class="hidden flex items-center justify-between w-full">
                                    <span id="file-name" class="text-sm font-semibold text-indigo-700 truncate pr-2"></span>
                                    <button id="delete-file-button" type="button" class="p-1 rounded-full text-slate-500 hover:text-red-600 hover:bg-red-100 focus:outline-none focus:ring-2 focus:ring-red-500">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                            <line x1="18" y1="6" x2="6" y2="18"></line>
                                            <line x1="6" y1="6" x2="18" y2="18"></line>
                                        </svg>
                                    </button>
                                </div>
                            </label>
                            <input id="file-upload" name="file-upload" type="file" class="sr-only">
                        </div>

                        <div class="relative mb-4">
                            <div class="absolute inset-0 flex items-center">
                                <div class="w-full border-t border-slate-300"></div>
                            </div>
                            <div class="relative flex justify-center"><span class="bg-white px-2 text-sm text-slate-500">hoặc</span></div>
                        </div>

                        <!-- Text Area -->
                        <div>
                            <label for="text-input" class="block text-sm font-medium text-slate-700">Dán văn bản vào đây:</label>
                            <div class="mt-1">
                                <textarea id="text-input" rows="10" class="shadow-sm focus:ring-indigo-500 focus:border-indigo-500 block w-full sm:text-sm border-slate-300 rounded-md" placeholder="Nhập hoặc dán nội dung tác phẩm, bài phân tích..."></textarea>
                            </div>
                        </div>

                        <!-- Analyze Button -->
                        <div class="mt-6">
                            <button id="analyze-button" class="w-full flex justify-center py-3 px-4 border border-transparent rounded-lg shadow-sm text-sm font-semibold text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-colors">
                                <svg id="analyze-icon" xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="mr-2">
                                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                                    <polyline points="14 2 14 8 20 8"></polyline>
                                    <line x1="16" y1="13" x2="8" y2="13"></line>
                                    <line x1="16" y1="17" x2="8" y2="17"></line>
                                    <polyline points="10 9 9 9 8 9"></polyline>
                                </svg>
                                <svg id="loading-spinner" class="animate-spin -ml-1 mr-3 h-5 w-5 text-white hidden" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                <span id="button-text">Phân tích Văn bản</span>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Right Panel: Output -->
                <div class="lg:col-span-8">
                    <div id="results-container" class="bg-white rounded-xl shadow-md min-h-[calc(100vh-12rem)]">
                        <!-- Placeholder -->
                        <div id="placeholder" class="text-center text-slate-500 h-full flex flex-col justify-center items-center p-8">
                            <svg xmlns="http://www.w3.org/2000/svg" width="60" height="60" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" class="text-slate-400 mb-4">
                                <path d="M21.5 12c0-5.25-4.25-9.5-9.5-9.5S2.5 6.75 2.5 12s4.25 9.5 9.5 9.5c.93 0 1.84-.14 2.7-.4"></path>
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
                            <!-- Tab Headers -->
                            <div class="border-b border-slate-200">
                                <nav class="-mb-px flex space-x-6 px-8" aria-label="Tabs">
                                    <button id="tab-analysis" class="tab-button active whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">Phân tích Chi tiết</button>
                                </nav>
                            </div>
                            <!-- Tab Content -->
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
            analyzeButton = document.getElementById('analyze-button'),
            buttonText = document.getElementById('button-text'),
            loadingSpinner = document.getElementById('loading-spinner'),
            placeholder = document.getElementById('placeholder'),
            resultsTabs = document.getElementById('results-tabs'),
            tabAnalysis = document.getElementById('tab-analysis'),
            contentAnalysis = document.getElementById('content-analysis'),
            fileInfo = document.getElementById('file-info'),
            deleteFileButton = document.getElementById('delete-file-button');

        const tabs = [tabAnalysis];
        const contents = [contentAnalysis];

        // State
        let currentAnalysisData = null;
        let currentAnalysisId = null; // ID của phân tích đã lưu
        let contentBlocksMap = {}; // Map section_name => content_id từ database

        // --- Event Listeners ---
        fileUpload.addEventListener('change', handleFileUpload);
        deleteFileButton.addEventListener('click', handleDeleteFile);
        analyzeButton.addEventListener('click', handleAnalysis);
        tabs.forEach(tab => tab.addEventListener('click', handleTabClick));

        // --- Handlers ---
        function handleFileUpload(e) {
            const file = e.target.files[0];
            if (file) {
                fileNameDisplay.textContent = file.name;
                fileInfo.classList.remove('hidden');
                uploadPrompt.classList.add('hidden');
                const reader = new FileReader();
                reader.onload = (event) => {
                    textInput.value = event.target.result;
                };
                if (file.name.endsWith('.txt')) {
                    reader.readAsText(file);
                } else {
                    textInput.value = `(Xem trước nội dung tệp ${file.name}...\nĐây là công cụ mô phỏng, vui lòng dán nội dung văn bản để phân tích chi tiết.)`;
                }
            }
        }

        function handleDeleteFile(e) {
            e.preventDefault(); // Prevent label click from reopening file dialog
            e.stopPropagation();
            fileUpload.value = ''; // Reset the file input
            textInput.value = ''; // Clear the textarea
            fileInfo.classList.add('hidden');
            fileNameDisplay.textContent = '';
            uploadPrompt.classList.remove('hidden');
        }

        async function handleAnalysis() {
            const textToAnalyze = textInput.value.trim();
            if (!textToAnalyze) {
                alert('Vui lòng nhập văn bản hoặc tải lên một tệp tin.');
                return;
            }
            setLoadingState(true);

            const analysisResult = await performMainAnalysis(textToAnalyze);

            if (analysisResult) {
                currentAnalysisData = analysisResult;
                await displayAnalysis(analysisResult);
                setupGeneratorTabs(); // Setup buttons for slideshow and quiz

                placeholder.classList.add('hidden');
                resultsTabs.classList.remove('hidden');
                switchTab(tabAnalysis);
            }

            setLoadingState(false);
        }

        function handleTabClick(e) {
            switchTab(e.target);
        }


        // --- UI Logic ---
        function setLoadingState(isLoading) {
            if (isLoading) {
                loadingSpinner.classList.remove('hidden');
                analyzeButton.querySelector('#analyze-icon').classList.add('hidden');
                buttonText.textContent = 'Đang phân tích...';
                analyzeButton.disabled = true;
            } else {
                loadingSpinner.classList.add('hidden');
                analyzeButton.querySelector('#analyze-icon').classList.remove('hidden');
                buttonText.textContent = 'Phân tích Văn bản';
                analyzeButton.disabled = false;
            }
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
         * Makes a POST request to the local PHP backend (api/call_api.php).
         * @param {string} action - The action to perform (e.g., 'analyze', 'slideshow', 'quiz').
         * @param {string} data - The text or JSON string to send as data.
         * @returns {Promise<object|null>} The JSON response from the backend.
         */
        async function makeApiCall(action, data) {
            try {
                const response = await fetch('api/call_api.php', {
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
        async function displayAnalysis(result) {
            if (!result) return;
            let html = `<h1>${result.title || 'Không có tiêu đề'}</h1>`;

            const formatDetailedAnalysis = (text) => {
                if (!text) return '';

                // QUAN TRỌNG: Loại bỏ TẤT CẢ ký tự ** (bold markdown) khỏi text
                // Vì API không nên trả về bold formatting theo quy tắc mới
                let cleanText = text.replace(/\*\*/g, '');

                // Chuyển đổi line breaks thành <br>
                cleanText = cleanText.replace(/\\n/g, '<br>');
                cleanText = cleanText.replace(/\n/g, '<br>');

                // Trả về văn bản đã được làm sạch
                return `<p>${cleanText}</p>`;
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
                        // Giữ nguyên ** trong heading để in đậm, loại bỏ ** khỏi content
                        const heading = section.heading; // Giữ nguyên ** trong heading
                        const content = section.content.replace(/\*\*/g, '').replace(/\n/g, '<br>');

                        // Chuyển đổi **text** thành <strong>text</strong> cho heading
                        const formattedHeading = heading.replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>');
                        html += `<h3>${formattedHeading}</h3><p>${content}</p>`;
                    });
                }

                // Linguistic Insights within General Analysis
                if (ga.linguisticInsights) {
                    html += `<h3>Ngôn ngữ & Biểu tượng</h3>`;
                    const insights = ga.linguisticInsights;

                    // Mô-típ: Chuyển array thành văn bản liền mạch
                    if (insights.motifs?.length > 0) {
                        const motifsText = insights.motifs.map(item => item.replace(/\*\*/g, '')).join(', ');
                        html += `<p><strong>Mô-típ lặp lại:</strong> ${motifsText}.</p>`;
                    }

                    // Biểu tượng: Chuyển array thành văn bản liền mạch
                    if (insights.symbolsAndMetaphors?.length > 0) {
                        const symbolsText = insights.symbolsAndMetaphors.map(item => item.replace(/\*\*/g, '')).join('; ');
                        html += `<p><strong>Biểu tượng & Ẩn dụ:</strong> ${symbolsText}.</p>`;
                    }

                    // Thời gian: Đoạn văn
                    if (insights.temporalElements) {
                        html += `<p><strong>Yếu tố Thời gian:</strong> ${insights.temporalElements.replace(/\*\*/g, '')}</p>`;
                    }

                    // Thiên nhiên: Đoạn văn
                    if (insights.natureAndScenery) {
                        html += `<p><strong>Thiên nhiên & Phong cảnh:</strong> ${insights.natureAndScenery.replace(/\*\*/g, '')}</p>`;
                    }
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
                if (pa.rhyming) html += `<h3>Gieo vần trong thơ</h3><p>${pa.rhyming.replace(/\*\*/g, '')}</p>`;
                if (pa.emotionalFlow) html += `<h3>Mạch cảm xúc</h3><p>${pa.emotionalFlow.replace(/\*\*/g, '')}</p>`;
                if (pa.structure) html += `<h3>Cấu trúc bài thơ</h3><p>${pa.structure.replace(/\*\*/g, '')}</p>`;
                if (pa.imagery) html += `<h3>Hình ảnh trong thơ</h3><p>${pa.imagery.replace(/\*\*/g, '')}</p>`;
                if (pa.rhythm) html += `<h3>Phân tích Nhịp điệu</h3><p>${pa.rhythm.replace(/\*\*/g, '').replace(/\n/g, '<br>')}</p>`;
                if (pa.spaceTimeOrganization) html += `<h3>Tổ chức không gian & thời gian</h3><p>${pa.spaceTimeOrganization.replace(/\*\*/g, '')}</p>`;
                if (pa.rhetoricalDevices) html += `<h3>Biện pháp tu từ</h3><p>${pa.rhetoricalDevices.replace(/\*\*/g, '')}</p>`;
            }

            // --- Concluding Sections ---
            if (result.concludingThoughts) {
                html += `<h2>Bài học & Thông điệp</h2>`;
                const ct = result.concludingThoughts;
                if (ct.lesson) html += `<h3>Bài học</h3><p>${ct.lesson.replace(/\*\*/g, '')}</p>`;
                if (ct.message) html += `<h3>Thông điệp</h3><p>${ct.message.replace(/\*\*/g, '')}</p>`;
            }

            if (result.intertextuality) {
                html += `<h2>Phân tích Liên văn bản</h2>`;
                const it = result.intertextuality;
                if (it.initialAnalysis) {
                    html += `<p>${it.initialAnalysis.replace(/\*\*/g, '')}</p>`;
                }
                if (it.comparativeText) {
                    html += `<h3>So sánh với tác phẩm tương đồng: <em>${it.comparativeText.title}</em> - ${it.comparativeText.author}</h3>`;
                }
                if (it.comparison) {
                    if (it.comparison.similarities) html += `<h4>Điểm tương đồng</h4><p>${it.comparison.similarities.replace(/\*\*/g, '')}</p>`;
                    if (it.comparison.differences) html += `<h4>Điểm khác biệt/thay đổi</h4><p>${it.comparison.differences.replace(/\*\*/g, '')}</p>`;
                    if (it.comparison.effects) html += `<h4>Hiệu quả</h4><p>${it.comparison.effects.replace(/\*\*/g, '')}</p>`;
                }
            }


            contentAnalysis.innerHTML = html;

            // Sau khi hiển thị HTML cơ bản, tạo image blocks và lưu vào database
            await generateAndSaveContentImages(result);
        }

        // === Text to Image Conversion (from v4.html) ===
        function textToImage(text, maxWidth = 700, options = {}) {
            return new Promise((resolve) => {
                const canvas = document.createElement('canvas');
                const ctx = canvas.getContext('2d');
                const {
                    fontSize = 16,
                        lineHeight = 1.6,
                        fontFamily = 'Inter, Arial, sans-serif',
                        backgroundColor = '#ffffff',
                        textColor = '#1f2937',
                        padding = 20
                } = options;

                ctx.font = `${fontSize}px ${fontFamily}`;
                const segments = parseStyledText(text);
                const lines = wrapTextWithStyles(ctx, segments, maxWidth - padding * 2, fontSize, lineHeight);
                const canvasHeight = lines.reduce((sum, line) => sum + line.height, 0) + padding * 2;

                canvas.width = maxWidth;
                canvas.height = canvasHeight;
                ctx.fillStyle = backgroundColor;
                ctx.fillRect(0, 0, canvas.width, canvas.height);
                ctx.textBaseline = 'top';

                let currentY = padding;
                lines.forEach(line => {
                    let currentX = padding;
                    line.segments.forEach(segment => {
                        ctx.font = applyStyle(segment.style, fontSize, fontFamily);
                        ctx.fillStyle = textColor;
                        ctx.fillText(segment.text, currentX, currentY);
                        currentX += ctx.measureText(segment.text).width;
                    });
                    currentY += line.height;
                });

                resolve(canvas.toDataURL('image/png'));
            });
        }

        function parseStyledText(text) {
            const segments = [];
            let current = '';
            let style = 'normal';
            let i = 0;

            while (i < text.length) {
                if (text[i] === '*' && text[i + 1] !== '*') {
                    if (current) segments.push({
                        text: current,
                        style
                    });
                    current = '';
                    style = style === 'bold' ? 'normal' : 'bold';
                    i++;
                } else if (text[i] === '_') {
                    if (current) segments.push({
                        text: current,
                        style
                    });
                    current = '';
                    style = style === 'italic' ? 'normal' : 'italic';
                    i++;
                } else {
                    current += text[i];
                    i++;
                }
            }

            if (current) segments.push({
                text: current,
                style
            });
            return segments;
        }

        function applyStyle(style, fontSize = 16, fontFamily = 'Inter, Arial, sans-serif') {
            if (style === 'bold') return `bold ${fontSize}px ${fontFamily}`;
            if (style === 'italic') return `italic ${fontSize}px ${fontFamily}`;
            return `${fontSize}px ${fontFamily}`;
        }

        function wrapTextWithStyles(ctx, segments, maxWidth, fontSize, lineHeight) {
            const lines = [];
            let currentLine = {
                segments: [],
                height: fontSize * lineHeight
            };
            let currentWidth = 0;

            segments.forEach(segment => {
                ctx.font = applyStyle(segment.style, fontSize);
                const words = segment.text.split(/(\s+|\n)/);

                words.forEach(word => {
                    if (word === '\n') {
                        lines.push(currentLine);
                        currentLine = {
                            segments: [],
                            height: fontSize * lineHeight
                        };
                        currentWidth = 0;
                    } else if (word.trim()) {
                        const wordWidth = ctx.measureText(word).width;
                        if (currentWidth + wordWidth > maxWidth && currentLine.segments.length > 0) {
                            lines.push(currentLine);
                            currentLine = {
                                segments: [],
                                height: fontSize * lineHeight
                            };
                            currentWidth = 0;
                        }
                        currentLine.segments.push({
                            text: word,
                            style: segment.style
                        });
                        currentWidth += wordWidth;
                    } else if (word) {
                        currentLine.segments.push({
                            text: word,
                            style: segment.style
                        });
                        currentWidth += ctx.measureText(word).width;
                    }
                });
            });

            if (currentLine.segments.length > 0) lines.push(currentLine);
            return lines;
        }

        // === Format functions for image generation ===
        const formatForImage = (content) =>
            content ? String(content).replace(/<br\s*\/?>/gi, '\n').replace(/<\/?i>/gi, '_').replace(/<\/?em>/gi, '_').replace(/<\/?strong>/gi, '*') : '';

        function formatThematicAnalysisForImage(data) {
            if (!data || !Array.isArray(data)) return '';
            return data.map(item => `*${item.heading || 'Chủ đề'}*\n${formatForImage(item.content)}`).join('\n\n');
        }

        function formatLinguisticInsightsForImage(data) {
            if (!data) return '';
            let text = '';
            if (data.motifs?.length) text += `*Mô-típ:*\n${data.motifs.map(i => `• ${formatForImage(i)}`).join('\n')}\n\n`;
            if (data.symbolsAndMetaphors?.length) text += `*Biểu tượng/Ẩn dụ:*\n${data.symbolsAndMetaphors.map(i => `• ${formatForImage(i)}`).join('\n')}\n\n`;
            if (data.temporalElements) text += `*Yếu tố Thời gian:*\n${formatForImage(data.temporalElements)}\n\n`;
            if (data.natureAndScenery) text += `*Thiên nhiên & Phong cảnh:*\n${formatForImage(data.natureAndScenery)}`;
            return text.trim();
        }

        function formatGenreAnalysisForImage(data, keys, type) {
            if (!data) return '';
            return keys.map(key => data[key] ? `*${getVietnameseTitle(key, type)}:*\n${formatForImage(data[key])}` : null).filter(Boolean).join('\n\n');
        }

        function formatConclusionForImage(data) {
            if (!data) return '';
            let text = '';
            if (data.lesson) text += `*Bài học:*\n${formatForImage(data.lesson)}\n\n`;
            if (data.message) text += `*Thông điệp:*\n${formatForImage(data.message)}`;
            return text.trim();
        }

        function formatIntertextualityForImage(data) {
            if (!data) return '';
            let text = '';
            if (data.initialAnalysis) text += `*Phân tích ban đầu:*\n${formatForImage(data.initialAnalysis)}\n\n`;
            if (data.comparativeText) text += `*So sánh với:*\n_${data.comparativeText.title || ''}_ - ${data.comparativeText.author || ''}\n\n`;
            if (data.comparison) {
                if (data.comparison.similarities) text += `*Điểm tương đồng:*\n${formatForImage(data.comparison.similarities)}\n\n`;
                if (data.comparison.differences) text += `*Điểm khác biệt:*\n${formatForImage(data.comparison.differences)}\n\n`;
                if (data.comparison.effects) text += `*Ý nghĩa/Hiệu quả:*\n${formatForImage(data.comparison.effects)}`;
            }
            return text.trim();
        }

        function getVietnameseTitle(key, type) {
            const titles = {
                story: {
                    character: 'Nhân vật',
                    space: 'Không gian',
                    time: 'Thời gian',
                    dialogue: 'Lời thoại',
                    narrator: 'Người kể chuyện',
                    situation: 'Tình huống',
                    prominentDetails: 'Chi tiết nổi bật'
                },
                drama: {
                    characters: 'Nhân vật',
                    conflict: 'Xung đột',
                    action: 'Hành động',
                    structure: 'Cấu trúc',
                    dialogue: 'Đối thoại'
                },
                poetry: {
                    rhyming: 'Vần',
                    rhythm: 'Nhịp điệu',
                    structure: 'Bố cục',
                    imagery: 'Hình ảnh',
                    emotionalFlow: 'Mạch cảm xúc',
                    rhetoricalDevices: 'Biện pháp tu từ',
                    spaceTimeOrganization: 'Không gian/Thời gian'
                }
            };
            return titles[type]?.[key] || key;
        }

        // === Generate Content Images and Save to Database ===
        async function generateAndSaveContentImages(result) {
            const sectionsToImage = [{
                    id: 'thematic-section',
                    name: 'Phân tích Chủ đề',
                    data: result.generalAnalysis?.thematicAnalysis,
                    formatFunc: formatThematicAnalysisForImage,
                    bgColor: '#f9fafb',
                    textColor: '#1f2937'
                },
                {
                    id: 'linguistic-section',
                    name: 'Ngôn ngữ & Biểu tượng',
                    data: result.generalAnalysis?.linguisticInsights,
                    formatFunc: formatLinguisticInsightsForImage,
                    bgColor: '#e0f2fe',
                    textColor: '#075985'
                },
                {
                    id: 'story-section',
                    name: 'Phân tích Truyện',
                    data: result.storyAnalysis,
                    formatFunc: (data) => formatGenreAnalysisForImage(data, ['character', 'space', 'time', 'dialogue', 'narrator', 'situation', 'prominentDetails'], 'story'),
                    bgColor: '#f0f9ff',
                    textColor: '#1e40af'
                },
                {
                    id: 'drama-section',
                    name: 'Phân tích Kịch',
                    data: result.dramaAnalysis,
                    formatFunc: (data) => formatGenreAnalysisForImage(data, ['characters', 'conflict', 'action', 'structure', 'dialogue'], 'drama'),
                    bgColor: '#fef3c7',
                    textColor: '#92400e'
                },
                {
                    id: 'poetry-section',
                    name: 'Phân tích Thơ ca',
                    data: result.poetryAnalysis,
                    formatFunc: (data) => formatGenreAnalysisForImage(data, ['rhyming', 'emotionalFlow', 'structure', 'imagery', 'rhythm', 'spaceTimeOrganization', 'rhetoricalDevices'], 'poetry'),
                    bgColor: '#fce7f3',
                    textColor: '#831843'
                },
                {
                    id: 'conclusion-section',
                    name: 'Bài học & Thông điệp',
                    data: result.concludingThoughts,
                    formatFunc: formatConclusionForImage,
                    bgColor: '#dcfce7',
                    textColor: '#14532d'
                },
                {
                    id: 'intertextuality-section',
                    name: 'Phân tích Liên văn bản',
                    data: result.intertextuality,
                    formatFunc: formatIntertextualityForImage,
                    bgColor: '#ede9fe',
                    textColor: '#4c1d95'
                }
            ];

            // Tạo container cho image blocks
            const insertPoint = contentAnalysis.querySelector('h2:last-of-type');
            if (insertPoint) {
                const imageContainer = document.createElement('div');
                imageContainer.id = 'image-blocks-container';
                imageContainer.innerHTML = '<h2>Nội dung Phân tích (Dạng Hình ảnh)</h2>';
                insertPoint.parentNode.insertBefore(imageContainer, insertPoint.nextSibling);

                // Tạo từng image block
                const contentBlocks = [];
                for (let i = 0; i < sectionsToImage.length; i++) {
                    const section = sectionsToImage[i];
                    if (!section.data) continue;

                    const textContent = section.formatFunc(section.data);
                    if (!textContent) continue;

                    try {
                        const imageData = await textToImage(textContent, 750, {
                            fontSize: 15,
                            lineHeight: 1.7,
                            backgroundColor: section.bgColor,
                            textColor: section.textColor,
                            padding: 25
                        });

                        // Thêm vào DOM
                        const blockHtml = createImageBlock(imageData, section.name);
                        imageContainer.insertAdjacentHTML('beforeend', blockHtml);

                        // Lưu vào array để gửi lên database
                        contentBlocks.push({
                            section_name: section.name,
                            content_text: textContent,
                            image_data: imageData,
                            order_index: i
                        });
                    } catch (error) {
                        console.error(`Error creating image for ${section.name}:`, error);
                    }
                }

                // Lưu toàn bộ phân tích vào database
                if (contentBlocks.length > 0) {
                    await saveAnalysisToDatabase(result, contentBlocks);
                }

                // Setup event handlers cho image blocks và comments
                setupImageBlockHandlers();
                setupFbCommentBlocks();
            }
        }

        // === Create Image Block HTML ===
        function createImageBlock(imageData, sectionName) {
            return `
                <div class="content-image-wrapper" data-section="${sectionName}">
                    <img src="${imageData}" alt="${sectionName}">
                    <div class="fb-comment-block" data-section="${sectionName}">
                        <div class="fb-comments-list"></div>
                        <div class="fb-comment-input-area">
                            <img src="https://i.pravatar.cc/36?img=<?php echo $_SESSION['user']['id'] ?? 0; ?>" alt="avatar">
                            <input type="text" class="fb-comment-input" placeholder="Viết bình luận...">
                            <button class="fb-comment-btn">Gửi</button>
                        </div>
                    </div>
                </div>
            `;
        }

        // === Image Block Interaction ===
        let selectedBlock = null;

        function setupImageBlockHandlers() {
            const wrappers = document.querySelectorAll('.content-image-wrapper');
            wrappers.forEach(wrapper => {
                wrapper.addEventListener('click', function(e) {
                    if (e.target.closest('.fb-comment-block')) return;
                    if (selectedBlock && selectedBlock !== this) {
                        selectedBlock.classList.remove('selected');
                    }
                    this.classList.toggle('selected');
                    selectedBlock = this.classList.contains('selected') ? this : null;
                });
            });
        }

        // === Facebook-style Comment System ===
        function setupFbCommentBlocks() {
            const commentBlocks = document.querySelectorAll('.fb-comment-block');
            commentBlocks.forEach(block => {
                const section = block.dataset.section;
                const listContainer = block.querySelector('.fb-comments-list');
                const input = block.querySelector('.fb-comment-input');
                const btn = block.querySelector('.fb-comment-btn');

                // Load comments từ database
                loadCommentsFromDatabase(section, listContainer);

                // Gửi comment mới
                btn.addEventListener('click', () => {
                    const text = input.value.trim();
                    if (text) {
                        saveCommentToDatabase(section, text, null, listContainer);
                        input.value = '';
                    }
                });

                input.addEventListener('keypress', (e) => {
                    if (e.key === 'Enter') {
                        btn.click();
                    }
                });
            });
        }

        // === Database API Functions ===
        async function saveAnalysisToDatabase(result, contentBlocks) {
            try {
                // Lưu từng content block vào bảng content
                const response_id = null; // Có thể link với response nếu cần

                for (let i = 0; i < contentBlocks.length; i++) {
                    const block = contentBlocks[i];

                    const response = await fetch('api/api_analysis_content.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded'
                        },
                        body: new URLSearchParams({
                            action: 'save_content',
                            response_id: response_id || '',
                            content: JSON.stringify({
                                section_name: block.section_name,
                                content_text: block.content_text,
                                image_data: block.image_data,
                                analysis_data: {
                                    title: result.title,
                                    characteristics: result.characteristics,
                                    keywords: result.keywords
                                }
                            }),
                            order: block.order_index,
                            type_create: 'ai_analysis'
                        })
                    });

                    const data = await response.json();
                    if (data.success) {
                        // Map section name to content_id
                        contentBlocksMap[block.section_name] = data.content_id;
                        console.log(`✅ Đã lưu ${block.section_name}, content_id:`, data.content_id);
                    } else {
                        console.error('Lỗi lưu content block:', data.error);
                    }
                }

                console.log('✅ Đã lưu tất cả content blocks vào database');
            } catch (error) {
                console.error('Lỗi khi gọi API:', error);
            }
        }

        async function saveCommentToDatabase(section, commentText, parentId, listContainer) {
            const contentId = contentBlocksMap[section];
            if (!contentId) {
                console.error('Không tìm thấy content_id cho section:', section);
                return;
            }

            try {
                const response = await fetch('api/api_analysis_content.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded'
                    },
                    body: new URLSearchParams({
                        action: 'save_comment',
                        content_id: contentId,
                        comment_text: commentText,
                        reply_comment: parentId || 0, // 0 = comment gốc
                        task_id: 0
                    })
                });

                const data = await response.json();
                if (data.success) {
                    loadCommentsFromDatabase(section, listContainer);
                } else {
                    console.error('Lỗi lưu comment:', data.error);
                    alert('Không thể lưu bình luận. Vui lòng thử lại.');
                }
            } catch (error) {
                console.error('Lỗi khi gọi API:', error);
                alert('Đã xảy ra lỗi khi lưu bình luận.');
            }
        }

        async function loadCommentsFromDatabase(section, listContainer) {
            const contentId = contentBlocksMap[section];
            if (!contentId) return;

            try {
                const response = await fetch(`api/api_analysis_content.php?action=load_comments&content_id=${contentId}`);
                const data = await response.json();
                if (data.success) {
                    renderFbComments(section, listContainer, data.comments);
                }
            } catch (error) {
                console.error('Lỗi load comments:', error);
            }
        }

        function renderFbComments(section, container, comments) {
            if (!container) return;
            container.innerHTML = comments.length === 0 ? '<p class="text-sm text-slate-500">Chưa có bình luận nào.</p>' : comments.map(c => createCommentHtml(c, section)).join('');
            attachCommentEventListeners(section, container);
        }

        function createCommentHtml(comment, section) {
            return `
                <div class="fb-comment-item" data-comment-id="${comment.comment_id}">
                    <div class="fb-comment-core">
                        <img src="https://i.pravatar.cc/40?img=${comment.user_id}" alt="${comment.username}">
                        <div class="flex-1">
                            <div class="fb-comment-bubble">
                                <div class="comment-author">${comment.username}</div>
                                <div class="comment-content">${comment.comment_text}</div>
                            </div>
                            <div class="fb-comment-actions">
                                <button class="like-btn" data-id="${comment.comment_id}">Thích</button>
                                <button class="reply-btn" data-id="${comment.comment_id}" data-section="${section}">Trả lời</button>
                                <span class="comment-time">${new Date(comment.created_at).toLocaleString('vi-VN')}</span>
                            </div>
                            ${comment.replies && comment.replies.length > 0 ? `
                                <div class="fb-replies pl-4 border-l-2 border-slate-200 ml-5">
                                    ${comment.replies.map(r => createCommentHtml(r, section)).join('')}
                                </div>
                            ` : ''}
                        </div>
                    </div>
                </div>
            `;
        }

        function attachCommentEventListeners(section, container) {
            // Like button (tạm thời bỏ qua vì bảng comment chưa có trường likes)
            container.querySelectorAll('.like-btn').forEach(btn => {
                btn.addEventListener('click', async (e) => {
                    e.stopPropagation();
                    alert('Chức năng like đang được phát triển');
                });
            });

            // Reply button
            container.querySelectorAll('.reply-btn').forEach(btn => {
                btn.addEventListener('click', (e) => {
                    e.stopPropagation();
                    const parentId = btn.dataset.id;
                    const section = btn.dataset.section;
                    const commentItem = btn.closest('.fb-comment-item');

                    // Tạo reply form
                    let replyForm = commentItem.querySelector('.fb-reply-form');
                    if (!replyForm) {
                        replyForm = document.createElement('div');
                        replyForm.className = 'fb-reply-form ml-5 mt-2';
                        replyForm.innerHTML = `
                            <div class="fb-comment-input-area">
                                <img src="https://i.pravatar.cc/28?img=<?php echo $_SESSION['user']['id'] ?? 0; ?>" alt="avatar">
                                <input type="text" class="fb-comment-input" placeholder="Viết câu trả lời...">
                                <button class="fb-comment-btn reply-submit-btn" data-parent="${parentId}" data-section="${section}">Gửi</button>
                            </div>
                        `;
                        commentItem.querySelector('.fb-comment-core').appendChild(replyForm);

                        const input = replyForm.querySelector('.fb-comment-input');
                        const submitBtn = replyForm.querySelector('.reply-submit-btn');

                        submitBtn.addEventListener('click', async () => {
                            const text = input.value.trim();
                            if (text) {
                                const listContainer = commentItem.closest('.fb-comment-block').querySelector('.fb-comments-list');
                                await saveCommentToDatabase(section, text, parentId, listContainer);
                                replyForm.remove();
                            }
                        });

                        input.focus();
                    }
                });
            });
        }
    </script>

</body>

</html>