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
                                    <button id="tab-slideshow" class="tab-button text-slate-500 hover:text-slate-700 whitespace-nowrap py-4 px-1 border-b-2 border-transparent font-medium text-sm">Trình chiếu</button>
                                    <button id="tab-quiz" class="tab-button text-slate-500 hover:text-slate-700 whitespace-nowrap py-4 px-1 border-b-2 border-transparent font-medium text-sm">Kiểm tra</button>
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
            tabSlideshow = document.getElementById('tab-slideshow'),
            tabQuiz = document.getElementById('tab-quiz'),
            contentAnalysis = document.getElementById('content-analysis'),
            contentSlideshow = document.getElementById('content-slideshow'),
            contentQuiz = document.getElementById('content-quiz'),
            fileInfo = document.getElementById('file-info'),
            deleteFileButton = document.getElementById('delete-file-button');

        const tabs = [tabAnalysis, tabSlideshow, tabQuiz];
        const contents = [contentAnalysis, contentSlideshow, contentQuiz];

        // State
        let currentAnalysisData = null;

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
                displayAnalysis(analysisResult);
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

        async function handleGenerateSlideshow() {
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