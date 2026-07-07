<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php require_once './views/templates/assets.php'; ?>
    <title data-lang-key="literary_analysis">Literary Analysis - Literature System</title>
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
                    <li class="breadcrumb-item"><a href="<?= breadcrumbClassUrl() ?>" data-lang-key="class"><?= breadcrumbClassLabel() ?></a></li>
                    <li class="breadcrumb-item active" data-lang-key="literary_analysis">Literary Analysis</li>
                </ol>
            </nav>
        </div><!-- End Page Title -->

        <section class="section">
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 h-full">

                <!-- Left Panel: Input -->
                <div class="lg:col-span-4 h-full">
                    <div class="bg-white p-6 rounded-xl shadow-md sticky top-24">
                        <h2 class="text-lg font-semibold mb-4 text-slate-800">💬 <span>Chat with AI</span></h2>

                        <!-- Text Area for Analysis Content -->
                        <div id="analysis-input-section" class="mb-4">
                            <div class="flex items-center justify-between mb-2">
                                <label for="text-input" class="block text-sm font-medium text-slate-700">Analysis content:</label>
                                <!-- File Upload Icon -->
                                <label for="file-upload"
                                    class="cursor-pointer text-indigo-600 hover:text-indigo-700 transition-colors"
                                    title="Upload file">
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
                                placeholder="Paste text to analyze..."></textarea>
                        </div>

                        <!-- Chat Messages Area (Hidden initially) -->
                        <div id="chat-section" class="hidden mb-4">
                            <div id="chat-messages"
                                class="mb-4 space-y-3 max-h-[300px] overflow-y-auto border border-slate-200 rounded-lg p-3 bg-slate-50">
                                <div class="text-center text-slate-400 text-sm py-4">
                                    Ask questions for AI to answer...
                                </div>
                            </div>

                            <!-- Question Input -->
                            <div class="mb-4">
                                <label for="question-input" class="block text-sm font-medium text-slate-700 mb-2">Your question:</label>
                                <textarea id="question-input" rows="4"
                                    class="shadow-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 block w-full text-sm border-slate-300 rounded-lg p-3 resize-y"
                                    placeholder="Example: Analyze the symbolism in more depth..."></textarea>
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
                                <span id="button-text">Analysis</span>
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
                                <span>Retry</span>
                            </button>

                            <!-- Chat Button (hidden initially, shown after analysis) -->
                            <button id="chat-button" disabled
                                class="hidden flex-1 flex justify-center items-center py-3 px-4 border border-indigo-600 rounded-lg shadow-sm text-sm font-semibold text-indigo-600 bg-white hover:bg-indigo-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-colors disabled:opacity-50 disabled:cursor-not-allowed">
                                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24"
                                    fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                    stroke-linejoin="round" class="mr-2">
                                    <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path>
                                </svg>
                                <span id="chat-button-text">Ask AI</span>
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
                            <h3 class="text-lg font-semibold text-slate-700">Analysis Result will appear here</h3>
                            <p class="mt-1 text-sm">Upload or paste text to get started.</p>
                        </div>

                        <!-- Results with Tabs -->
                        <div id="results-tabs" class="genai-tabs hidden h-full flex flex-col">
                            <div class="genai-tabs__header">
                                <nav class="genai-tabs__nav" aria-label="Tabs">
                                    <button id="tab-analysis" type="button"
                                        class="genai-tabs__btn tab-button active" data-lang-key="detailed_analysis">
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
            contentAnalysis = document.getElementById('content-analysis'),
            fileInfo = document.getElementById('file-info'),
            deleteFileButton = document.getElementById('delete-file-button'),
            chatMessages = document.getElementById('chat-messages'),
            chatSection = document.getElementById('chat-section'),
            analysisInputSection = document.getElementById('analysis-input-section');

        const tabs = [tabAnalysis];
        const contents = [contentAnalysis];

        // State
        let currentAnalysisData = null;
        let conversationHistory = []; // Store chat history for refinement
        let isAnalyzed = false; // Track if initial analysis is done

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
                    alert('Only text files (.txt) are accepted. Please select another file.');
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
                    alert('Cannot read file. Please try again.');
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
            const textToAnalyze = textInput.value.trim();
            if (!textToAnalyze) {
                alert('Please enter text or upload a file.');
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
                addChatMessage('assistant', 'Analysis completed. You can view the results on the right or ask questions to learn more.');
            }

            setLoadingState(false, 'analyze');
        }

        function switchToChatMode() {
            // Make text input readonly and disabled
            textInput.setAttribute('readonly', true);
            textInput.classList.add('bg-slate-100', 'cursor-not-allowed');

            // Hide analyze button, show reset and chat buttons
            analyzeButton.classList.add('hidden');
            resetButton.classList.remove('hidden');
            resetButton.removeAttribute('disabled'); // Enable reset button
            chatButton.classList.remove('hidden');
            chatButton.removeAttribute('disabled'); // Enable chat button

            // Show chat section
            chatSection.classList.remove('hidden');

            // Clear chat messages placeholder
            chatMessages.innerHTML = '';
        }

        function handleReset() {
            // Security check: prevent unauthorized reset
            if (!isAnalyzed) {
                alert('Cannot perform this action. Please analyze the text first.');
                return;
            }

            if (!confirm('Are you sure you want to retry? All analysis data will be lost.')) {
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

            // Hide chat section
            chatSection.classList.add('hidden');

            // Reset chat messages
            chatMessages.innerHTML = `<div class="text-center text-slate-400 text-sm py-4">Ask questions for AI to answer...</div>`;

            // Reset file upload
            fileUpload.value = '';
            fileInfo.classList.add('hidden');
        }

        // Handle chat submission for refinement
        async function handleChatSubmit() {
            // Security check: prevent unauthorized chat submission
            if (!isAnalyzed) {
                alert('Cannot perform this action. Please analyze the text first.');
                return;
            }

            const userMessage = questionInput.value.trim();
            if (!userMessage) {
                alert('Please enter a question.');
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
                const responseText = result.response || result.message || 'Your request has been processed.';
                addChatMessage('assistant', responseText);

                // Add model response to conversation history
                conversationHistory.push({
                    role: 'model',
                    parts: [{
                        text: responseText
                    }]
                });

                // Update the analysis display with new information
                appendToAnalysisDisplay(userMessage, responseText);

            } catch (error) {
                console.error("Error during chat:", error);
                addChatMessage('system', 'An error occurred. Please try again.');
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
                    <div class="ai-markdown-bubble">
                        ${formatAiMarkdown(answer)}
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
            // Security check: Prevent tab switching if not analyzed
            const targetTab = e.target;
            if ((targetTab === tabSlideshow || targetTab === tabQuiz) && !isAnalyzed) {
                alert('Cannot perform this action. Please analyze the text first.');
                return;
            }

            switchTab(targetTab);
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
                    buttonText.textContent = 'Analysis';
                    analyzeButton.disabled = false;
                    chatButton.disabled = false;
                }
            } else if (mode === 'chat') {
                if (isLoading) {
                    chatButtonText.textContent = 'Processing...';
                    chatButton.disabled = true;
                    analyzeButton.disabled = true;
                } else {
                    chatButtonText.textContent = 'Ask AI';
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
                alert('An error occurred communicating with the server. Please try again.');
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
            console.log(result);
            if (!result) return;

            // Create main analysis content
            let html = `<div id="analysis-content">`;
            html += `<h1>${result.title || 'No Title'}</h1>`;

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


            html += `<h2>Text Characteristics</h2><div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">`;
            if (result.characteristics) {
                for (const [key, value] of Object.entries(result.characteristics)) {
                    html += `<div class="bg-slate-100 p-3 rounded-lg"><strong class="block text-sm text-slate-600">${key}</strong><span class="text-indigo-700 font-semibold">${value}</span></div>`;
                }
            }
            html += `</div>`;

            html += `<h2>Language Statistics</h2><div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">`;
            if (result.stats) {
                html += `<div class="bg-slate-100 p-3 rounded-lg"><strong class="block text-sm text-slate-600">Characters</strong><span class="text-indigo-700 font-semibold">${result.stats.charCount.toLocaleString('vi-VN')}</span></div>`;
                html += `<div class="bg-slate-100 p-3 rounded-lg"><strong class="block text-sm text-slate-600">Words</strong><span class="text-indigo-700 font-semibold">${result.stats.wordCount.toLocaleString('vi-VN')}</span></div>`;
                html += `<div class="bg-slate-100 p-3 rounded-lg"><strong class="block text-sm text-slate-600">Top Words (frequency)</strong><span class="text-indigo-700 font-semibold">${result.stats.topWords.map(w => w[0]).join(', ')}</span></div>`;
            }
            html += `</div>`;

            if (result.keywords && result.keywords.length > 0) {
                html += `<h2>Keywords</h2><div class="flex flex-wrap gap-2 mb-6">`;
                result.keywords.forEach(keyword => {
                    html += `<span class="bg-indigo-100 text-indigo-800 text-sm font-medium px-3 py-1.5 rounded-full">${keyword}</span>`;
                });
                html += `</div>`;
            }

            // --- General Analysis Section ---
            if (result.generalAnalysis) {
                html += `<h2>Analysis Chung</h2>`;
                const ga = result.generalAnalysis;

                // Thematic Analysis within General Analysis
                if (ga.thematicAnalysis) {
                    ga.thematicAnalysis.forEach(section => {
                        html += `<h3>${section.heading}</h3><p>${section.content}</p>`;
                    });
                }

                // Linguistic Insights within General Analysis
                if (ga.linguisticInsights) {
                    html += `<h3>Language & Symbolism</h3>`; // Sub-heading for linguistic insights
                    const insights = ga.linguisticInsights;
                    html += `<ul>`; // Start bullet list for insights
                    if (insights.motifs?.length > 0) {
                        html += `<li><strong>Recurring Motifs:</strong><ul>`; // Nested list for motifs
                        insights.motifs.forEach(item => {
                            html += `<li>${item}</li>`;
                        });
                        html += `</ul></li>`;
                    }
                    if (insights.symbolsAndMetaphors?.length > 0) {
                        html += `<li><strong>Symbols & Metaphors:</strong><ul>`; // Nested list for symbols
                        insights.symbolsAndMetaphors.forEach(item => {
                            html += `<li>${item}</li>`;
                        });
                        html += `</ul></li>`;
                    }
                    if (insights.temporalElements) {
                        html += `<li><strong>Temporal Elements:</strong> ${insights.temporalElements}</li>`;
                    }
                    if (insights.natureAndScenery) {
                        html += `<li><strong>Nature & Scenery:</strong> ${insights.natureAndScenery}</li>`;
                    }
                    html += `</ul>`; // End bullet list for insights
                }
            }


            // --- Genre-Specific Analysis ---
            if (result.storyAnalysis) {
                html += `<h2>Story Analysis</h2>`;
                const sa = result.storyAnalysis;
                if (sa.character) html += `<h3>Characters</h3><div>${formatDetailedAnalysis(sa.character)}</div>`;
                if (sa.space) html += `<h3>Space</h3><div>${formatDetailedAnalysis(sa.space)}</div>`;
                if (sa.time) html += `<h3>Time</h3><div>${formatDetailedAnalysis(sa.time)}</div>`;
                if (sa.dialogue) html += `<h3>Dialogue & Inner Monologue</h3><div>${formatDetailedAnalysis(sa.dialogue)}</div>`; // Combined dialogue/monologue
                if (sa.narrator) html += `<h3>Narrator</h3><div>${formatDetailedAnalysis(sa.narrator)}</div>`;
                if (sa.situation) html += `<h3>Story Situation</h3><div>${formatDetailedAnalysis(sa.situation)}</div>`;
                if (sa.prominentDetails) html += `<h3>Prominent Details</h3><div>${formatDetailedAnalysis(sa.prominentDetails)}</div>`;
            }

            if (result.dramaAnalysis) {
                html += `<h2>Drama Analysis</h2>`;
                const has = result.dramaAnalysis;
                if (has.characters) html += `<h3>Characters</h3><div>${formatDetailedAnalysis(has.characters)}</div>`;
                if (has.conflict) html += `<h3>Conflict</h3><div>${formatDetailedAnalysis(has.conflict)}</div>`;
                if (has.action) html += `<h3>Action</h3><div>${formatDetailedAnalysis(has.action)}</div>`;
                if (has.structure) html += `<h3>Structure</h3><div>${formatDetailedAnalysis(has.structure)}</div>`;
                if (has.dialogue) html += `<h3>Dialogue</h3><div>${formatDetailedAnalysis(has.dialogue)}</div>`;
            }

            if (result.poetryAnalysis) {
                html += `<h2>Poetry Analysis</h2>`;
                const pa = result.poetryAnalysis;
                if (pa.rhyming) html += `<h3>Rhyming in Poetry</h3><p>${pa.rhyming}</p>`;
                if (pa.emotionalFlow) html += `<h3>Emotional Flow</h3><p>${pa.emotionalFlow}</p>`;
                if (pa.structure) html += `<h3>Poem Structure</h3><p>${pa.structure}</p>`;
                if (pa.imagery) html += `<h3>Imagery in Poetry</h3><p>${pa.imagery}</p>`;
                if (pa.rhythm) html += `<h3>Rhythm Analysis</h3><p>${pa.rhythm.replace(/\n/g, '<br>')}</p>`;
                if (pa.spaceTimeOrganization) html += `<h3>Space & Time Organization</h3><p>${pa.spaceTimeOrganization}</p>`;
                if (pa.rhetoricalDevices) html += `<h3>Rhetorical Devices</h3><p>${pa.rhetoricalDevices}</p>`;
            }

            // --- Concluding Sections ---
            if (result.concludingThoughts) {
                html += `<h2>Lesson & Message</h2>`;
                const ct = result.concludingThoughts;
                if (ct.lesson) html += `<h3>Lesson</h3><p>${ct.lesson}</p>`;
                if (ct.message) html += `<h3>Message</h3><p>${ct.message}</p>`;
            }

            if (result.intertextuality) {
                html += `<h2>Analysis Intertextuality</h2>`;
                const it = result.intertextuality;
                if (it.initialAnalysis) {
                    html += `<p>${it.initialAnalysis}</p>`;
                }
                if (it.comparativeText) {
                    html += `<h3>Comparison with similar work: <em>${it.comparativeText.title}</em> - ${it.comparativeText.author}</h3>`;
                }
                if (it.comparison) {
                    if (it.comparison.similarities) html += `<h4>Similarities</h4><p>${it.comparison.similarities}</p>`;
                    if (it.comparison.differences) html += `<h4>Differences/Changes</h4><p>${it.comparison.differences}</p>`;
                    if (it.comparison.effects) html += `<h4>Effects</h4><p>${it.comparison.effects}</p>`;
                }
            }

            html += `</div>`; // Close analysis-content

            html += `</div>`; // Close analysis-content

            contentAnalysis.innerHTML = html;

            // Initialize conversation history with initial analysis
            conversationHistory = [{
                    role: 'user',
                    parts: [{
                        text: `Analyze the following text:\n\n${textInput.value.trim()}`
                    }]
                },
                {
                    role: 'model',
                    parts: [{
                        text: JSON.stringify(result)
                    }]
                }
            ];
        }
    </script>

</body>

</html>