<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php require_once './views/templates/assets.php'; ?>
    <title>Literary Analysis</title>
    <link rel="stylesheet" href="<?= URL_ROOT ?>/assets/css/literary_analysis.css">
    <link rel="stylesheet" href="<?= URL_ROOT ?>/assets/css/genai-layout.css">
</head>

<body class="literary-bg">
    <?php
    require_once __DIR__ . '/../../config/helper_breadcrumb.php';
    require_once './views/templates/header.php';
    require_once './views/templates/sidebar.php';
    ?>

    <main id="main" class="main">
        <div class="pagetitle">
            <h1 data-lang-key="literary_analysis_toolkit">Literary Analysis Toolkit</h1>
            <nav>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="<?= breadcrumbClassUrl() ?>"><?= breadcrumbClassLabel() ?></a></li>
                    <li class="breadcrumb-item active">Literary Analysis</li>
                </ol>
            </nav>
        </div><!-- End Page Title -->

        <section class="section">
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 h-full">

                <!-- Left Panel: Input -->
                <div class="lg:col-span-4 h-full">
                    <div class="bg-white p-6 rounded-xl shadow-md sticky top-24">
                        <h2 class="text-lg font-semibold mb-4 text-slate-800">Enter text to analyze</h2>

                        <!-- File Upload -->
                        <div class="mb-4">
                            <label for="file-upload" class="block text-sm font-medium text-slate-700 mb-2">Upload file:</label>
                            <label for="file-upload" class="relative cursor-pointer bg-white rounded-md font-medium text-indigo-600 hover:text-indigo-500 border border-dashed border-slate-300 p-4 flex justify-center items-center flex-col min-h-[150px]">
                                <div id="upload-prompt" class="text-center">
                                    <svg class="mx-auto h-12 w-12 text-slate-400" stroke="currentColor" fill="none" viewBox="0 0 48 48" aria-hidden="true">
                                        <path d="M28 8H12a4 4 0 00-4 4v20m32-12v8m0 0v8a4 4 0 01-4 4H12a4 4 0 01-4-4v-4m32-4l-3.172-3.172a4 4 0 00-5.656 0L28 28M8 32l9.172-9.172a4 4 0 015.656 0L28 28m0 0l4 4m4-24h8m-4-4v8" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                    </svg>
                                    <span class="mt-2 block text-sm">Click to upload</span>
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
                            <div class="relative flex justify-center"><span class="bg-white px-2 text-sm text-slate-500">or</span></div>
                        </div>

                        <!-- Text Area -->
                        <div>
                            <label for="text-input" class="block text-sm font-medium text-slate-700">Paste text here:</label>
                            <div class="mt-1">
                                <textarea id="text-input" rows="10" class="shadow-sm focus:ring-indigo-500 focus:border-indigo-500 block w-full sm:text-sm border-slate-300 rounded-md" placeholder="Enter or paste text content, analysis..."></textarea>
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
                                <span id="button-text">Analyze Text</span>
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
                            <h3 class="text-lg font-semibold text-slate-700">Analysis results will appear here</h3>
                            <p class="mt-1 text-sm">Upload or paste text to get started.</p>
                        </div>

                        <!-- Results with Tabs -->
                        <div id="results-tabs" class="genai-tabs hidden h-full flex flex-col">
                            <div class="genai-tabs__header">
                                <nav class="genai-tabs__nav" aria-label="Tabs">
                                    <button id="tab-analysis" type="button" class="genai-tabs__btn tab-button active" data-lang-key="detailed_analysis">
                                        <i class="bi bi-journal-text"></i><span>Detailed Analysis</span>
                                    </button>
                                </nav>
                            </div>
                            <div class="genai-tabs__body flex-grow overflow-auto">
                                <div id="content-analysis" class="tab-content prose-custom max-w-none"></div>
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
                    textInput.value = `(File content preview ${file.name}...\nThis is a simulation tool, please paste text content for detailed analysis.)`;
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
                alert('Please enter text or upload a file.');
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
                container.innerHTML = `<p class="text-red-500">Cannot generate slideshow. Please try again.</p>`;
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
                container.innerHTML = `<p class="text-red-500">Cannot generate questions. Please try again.</p>`;
            }
        }

        // --- UI Logic ---
        function setLoadingState(isLoading) {
            if (isLoading) {
                loadingSpinner.classList.remove('hidden');
                analyzeButton.querySelector('#analyze-icon').classList.add('hidden');
                buttonText.textContent = 'Analyzing...';
                analyzeButton.disabled = true;
            } else {
                loadingSpinner.classList.add('hidden');
                analyzeButton.querySelector('#analyze-icon').classList.remove('hidden');
                buttonText.textContent = 'Analyze Text';
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
                <h3 class="text-lg font-semibold text-slate-700 mb-4">Generate Slideshow</h3>
                <p class="text-slate-500 mb-6">Click the button below to create a presentation based on analysis results.</p>
                <button id="generate-slideshow-button" class="px-6 py-3 bg-indigo-600 text-white font-semibold rounded-lg hover:bg-indigo-700">Generate Slideshow</button>
            </div>`;
            contentQuiz.innerHTML = `<div class="text-center p-8">
                 <h3 class="text-lg font-semibold text-slate-700 mb-4">Generate Quiz</h3>
                <p class="text-slate-500 mb-6">Click the button below to create a short quiz based on the text content.</p>
                <button id="generate-quiz-button" class="px-6 py-3 bg-indigo-600 text-white font-semibold rounded-lg hover:bg-indigo-700">Generate Questions</button>
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
                alert('An error occurred while communicating with the server. Please try again.');
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
            let html = `<h1>${result.title || 'No title'}</h1>`;

            const formatDetailedAnalysis = (text) => {
                if (!text) return '';

                // QUAN TRỌNG: Type bỏ TẤT CẢ ký tự ** (bold markdown) khỏi text
                // Vì API không nên trả về bold formatting theo quy tắc mới
                let cleanText = text.replace(/\*\*/g, '');

                // Chuyển đổi line breaks thành <br>
                cleanText = cleanText.replace(/\\n/g, '<br>');
                cleanText = cleanText.replace(/\n/g, '<br>');

                // Trả về văn bản đã được làm sạch
                return `<p>${cleanText}</p>`;
            };


            html += `<h2>Text Characteristics</h2><div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">`;
            if (result.characteristics) {
                for (const [key, value] of Object.entries(result.characteristics)) {
                    html += `<div class="bg-slate-100 p-3 rounded-lg"><strong class="block text-sm text-slate-600">${key}</strong><span class="text-indigo-700 font-semibold">${value}</span></div>`;
                }
            }
            html += `</div>`;

            html += `<h2>Language Statistics</h2><div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">`;
            if (result.stats) {
                html += `<div class="bg-slate-100 p-3 rounded-lg"><strong class="block text-sm text-slate-600">Character Count</strong><span class="text-indigo-700 font-semibold">${result.stats.charCount.toLocaleString('vi-VN')}</span></div>`;
                html += `<div class="bg-slate-100 p-3 rounded-lg"><strong class="block text-sm text-slate-600">Word Count</strong><span class="text-indigo-700 font-semibold">${result.stats.wordCount.toLocaleString('vi-VN')}</span></div>`;
                html += `<div class="bg-slate-100 p-3 rounded-lg"><strong class="block text-sm text-slate-600">Top Words (frequency)</strong><span class="text-indigo-700 font-semibold">${result.stats.topWords.map(w => w[0]).join(', ')}</span></div>`;
            }
            html += `</div>`;

            if (result.keywords && result.keywords.length > 0) {
                html += `<h2>Key Terms</h2><div class="flex flex-wrap gap-2 mb-6">`;
                result.keywords.forEach(keyword => {
                    html += `<span class="bg-indigo-100 text-indigo-800 text-sm font-medium px-3 py-1.5 rounded-full">${keyword}</span>`;
                });
                html += `</div>`;
            }

            // --- General Analysis Section ---
            if (result.generalAnalysis) {
                html += `<h2>General Analysis</h2>`;
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
                    html += `<h3>Language & Symbolism</h3>`;
                    const insights = ga.linguisticInsights;

                    // Mô-típ: Chuyển array thành văn bản liền mạch
                    if (insights.motifs?.length > 0) {
                        const motifsText = insights.motifs.map(item => item.replace(/\*\*/g, '')).join(', ');
                        html += `<p><strong>Recurring Motifs:</strong> ${motifsText}.</p>`;
                    }

                    // Biểu tượng: Chuyển array thành văn bản liền mạch
                    if (insights.symbolsAndMetaphors?.length > 0) {
                        const symbolsText = insights.symbolsAndMetaphors.map(item => item.replace(/\*\*/g, '')).join('; ');
                        html += `<p><strong>Symbols & Metaphors:</strong> ${symbolsText}.</p>`;
                    }

                    // Time: Đoạn văn
                    if (insights.temporalElements) {
                        html += `<p><strong>Temporal Elements:</strong> ${insights.temporalElements.replace(/\*\*/g, '')}</p>`;
                    }

                    // Thiên nhiên: Đoạn văn
                    if (insights.natureAndScenery) {
                        html += `<p><strong>Nature & Scenery:</strong> ${insights.natureAndScenery.replace(/\*\*/g, '')}</p>`;
                    }
                }
            }


            // --- Genre-Specific Analysis ---
            if (result.storyAnalysis) {
                html += `<h2>Story Analysis</h2>`;
                const sa = result.storyAnalysis;
                if (sa.character) html += `<h3>Characters</h3><div>${formatDetailedAnalysis(sa.character)}</div>`;
                if (sa.space) html += `<h3>Space</h3><div>${formatDetailedAnalysis(sa.space)}</div>`;
                if (sa.time) html += `<h3>Time</h3><div>${formatDetailedAnalysis(sa.time)}</div>`;
                if (sa.dialogue) html += `<h3>Dialogue & Internal Monologue</h3><div>${formatDetailedAnalysis(sa.dialogue)}</div>`; // Combined dialogue/monologue
                if (sa.narrator) html += `<h3>Narrator</h3><div>${formatDetailedAnalysis(sa.narrator)}</div>`;
                if (sa.situation) html += `<h3>Story Situation</h3><div>${formatDetailedAnalysis(sa.situation)}</div>`;
                if (sa.prominentDetails) html += `<h3>Prominent Details</h3><div>${formatDetailedAnalysis(sa.prominentDetails)}</div>`;
            }

            if (result.dramaAnalysis) {
                html += `<h2>Drama Analysis</h2>`;
                const da = result.dramaAnalysis;
                if (da.characters) html += `<h3>Characters</h3><div>${formatDetailedAnalysis(da.characters)}</div>`;
                if (da.conflict) html += `<h3>Conflict</h3><div>${formatDetailedAnalysis(da.conflict)}</div>`;
                if (da.action) html += `<h3>Actions</h3><div>${formatDetailedAnalysis(da.action)}</div>`;
                if (da.structure) html += `<h3>Structure</h3><div>${formatDetailedAnalysis(da.structure)}</div>`;
                if (da.dialogue) html += `<h3>Dialogue</h3><div>${formatDetailedAnalysis(da.dialogue)}</div>`;
            }

            if (result.poetryAnalysis) {
                html += `<h2>Poetry Analysis</h2>`;
                const pa = result.poetryAnalysis;
                if (pa.rhyming) html += `<h3>Rhyming in Poetry</h3><p>${pa.rhyming.replace(/\*\*/g, '')}</p>`;
                if (pa.emotionalFlow) html += `<h3>Emotional Flow</h3><p>${pa.emotionalFlow.replace(/\*\*/g, '')}</p>`;
                if (pa.structure) html += `<h3>Poem Structure</h3><p>${pa.structure.replace(/\*\*/g, '')}</p>`;
                if (pa.imagery) html += `<h3>Imagery in Poetry</h3><p>${pa.imagery.replace(/\*\*/g, '')}</p>`;
                if (pa.rhythm) html += `<h3>Rhythm Analysis</h3><p>${pa.rhythm.replace(/\*\*/g, '').replace(/\n/g, '<br>')}</p>`;
                if (pa.spaceTimeOrganization) html += `<h3>Space & Time Organization</h3><p>${pa.spaceTimeOrganization.replace(/\*\*/g, '')}</p>`;
                if (pa.rhetoricalDevices) html += `<h3>Rhetorical Devices</h3><p>${pa.rhetoricalDevices.replace(/\*\*/g, '')}</p>`;
            }

            // --- Concluding Sections ---
            if (result.concludingThoughts) {
                html += `<h2>Lessons & Messages</h2>`;
                const ct = result.concludingThoughts;
                if (ct.lesson) html += `<h3>Lesson</h3><p>${ct.lesson.replace(/\*\*/g, '')}</p>`;
                if (ct.message) html += `<h3>Message</h3><p>${ct.message.replace(/\*\*/g, '')}</p>`;
            }

            if (result.intertextuality) {
                html += `<h2>Intertextuality Analysis</h2>`;
                const it = result.intertextuality;
                if (it.initialAnalysis) {
                    html += `<p>${it.initialAnalysis.replace(/\*\*/g, '')}</p>`;
                }
                if (it.comparativeText) {
                    html += `<h3>Comparison with similar work: <em>${it.comparativeText.title}</em> - ${it.comparativeText.author}</h3>`;
                }
                if (it.comparison) {
                    if (it.comparison.similarities) html += `<h4>Similarities</h4><p>${it.comparison.similarities.replace(/\*\*/g, '')}</p>`;
                    if (it.comparison.differences) html += `<h4>Differences/Changes</h4><p>${it.comparison.differences.replace(/\*\*/g, '')}</p>`;
                    if (it.comparison.effects) html += `<h4>Effects</h4><p>${it.comparison.effects.replace(/\*\*/g, '')}</p>`;
                }
            }


            contentAnalysis.innerHTML = html;
        }
    </script>

</body>

</html>