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
    async function makeApiCall(systemPrompt, userText, schema) {
        const apiKey = ""; // This is handled by the execution environment.
        const apiUrl = `https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-pro:generateContent?key=${apiKey}`;
        const payload = {
            contents: [{
                parts: [{
                    text: userText
                }]
            }],
            systemInstruction: {
                parts: [{
                    text: systemPrompt
                }]
            },
            generationConfig: {
                responseMimeType: "application/json",
                responseSchema: schema
            }
        };

        try {
            const response = await fetch(apiUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(payload)
            });
            if (!response.ok) throw new Error(`API call failed: ${response.status}`);
            const result = await response.json();
            if (result.candidates?.[0]?.content?.parts?.[0]?.text) {
                return JSON.parse(result.candidates[0].content.parts[0].text);
            } else {
                console.warn("API response was valid but contained no text.", result);
                return null; // Return null if no text is present
            }
        } catch (error) {
            console.error("Error during API call:", error);
            alert('An error occurred while communicating with AI. Please try again.');
            return null;
        }
    }

    async function performMainAnalysis(text) {
        const systemPrompt = `Bạn là một chuyên gia phân tích văn học. Phân tích văn bản được cung cấp và trả về một đối tượng JSON. Content phải bằng tiếng Việt và chi tiết, sâu sắc.

### QUY TẮC PHÂN LOẠI THỂ LOẠI ###
Dựa vào các quy tắc sau để xác định 'Thể loại' trong 'characteristics'. Hãy chú ý đến cấu trúc tổng thể và đặc points nổi trội nhất. Khi trả về giá trị cho 'Thể loại', hãy sử dụng định dạng: "**[Tên thể loại chính] – [Tên thể loại phụ/đặc points]**".
1. VĂN BẢN (VĂN XUÔI):
- Tự sự (Truyện): Có nhân vật, sự kiện, cốt truyện.
- Miêu tả, Biểu cảm, Nghị luận, Thuyết minh, Hành chính - công vụ.
- Kịch: Nhận diện qua cấu trúc đối thoại, chỉ dẫn sân khấu, và xung đột rõ ràng.
2. THƠ:
- Lục bát, Thơ tự do, Thơ 4/5/6/7/8 chữ, Thất ngôn bát cú/Tứ tuyệt, Thơ châm biếm, Song Thất Lục Bát.

### QUY TẮC PHÂN TÍCH CHUNG (General Analysis) ###
Gộp các phân tích chủ đề (thematicAnalysis) và ngôn ngữ/biểu tượng (linguisticInsights) vào một mục lớn "generalAnalysis". Trong đó:
- 'thematicAnalysis': Giữ nguyên cấu trúc mảng các object {heading, content}.
- 'linguisticInsights': Chia thành các mục con: motifs, symbolsAndMetaphors, temporalElements, natureAndScenery. Mỗi mục con trình bày dưới dạng danh sách gạch đầu dòng (sử dụng dấu • ).

### QUY TẮC PHÂN TÍCH CHUYÊN SÂU THEO THỂ LOẠI ###
BẮT BUỘC phải tạo object phân tích chuyên sâu tương ứng với thể loại đã xác định và thực hiện đầy đủ các mục phân tích yêu cầu cho thể loại đó. Mỗi phân tích phải được chia thành các ý nhỏ, rõ ràng và kèm theo dẫn chứng trực tiếp từ văn bản, đặt trong thẻ <i>.

- **Nếu là Kịch (dramaAnalysis):** Phân tích chi tiết thành các ý nhỏ, mỗi ý có tiêu đề in đậm và xuống dòng theo định dạng: "**Title ý:**\\n• Content phân tích. <i>Dẫn chứng...</i>" cho các mục: **characters, conflict, action, structure, dialogue**.
- **Nếu là Thơ (poetryAnalysis):** Phân tích các mục sau: **rhyming, emotionalFlow, structure, imagery, rhythm, spaceTimeOrganization, rhetoricalDevices**.
- **Nếu là Truyện (storyAnalysis):** Phân tích từng mục thành các ý nhỏ, mỗi ý có tiêu đề in đậm và xuống dòng theo định dạng: "**Title ý:**\\n• Content phân tích. <i>Dẫn chứng...</i>" cho các mục: **character, space, time, dialogue (bao gồm cả đối thoại và độc thoại nội tâm), narrator, situation (tình huống truyện), prominentDetails (chi tiết nổi bật)**.

### PHÂN TÍCH CUỐI CÙNG ###
1. 'concludingThoughts': Rút ra 'lesson' (Lesson) và 'message' (Message).
2. 'intertextuality' (Intertextuality Analysis):
   - **Quy tắc chung:** Phân tích phải dựa trên thể loại của tác phẩm chính.
   
   - **1. Đối với Kịch (Drama):**
     - **Yêu cầu:** Tác phẩm so sánh ('comparativeText') BẮT BUỘC phải là một vở kịch của một tác giả khác.
     - **Mục tiêu:** Làm nổi bật ý tưởng, phong cách biểu đạt, xung đột kịch và thông điệp nhân văn.
     - **Structure:**
       + initialAnalysis: Nhận xét chung về nội dung, chủ đề, phong cách của vở kịch chính.
       + comparativeText: { title, author }
       + comparison: { similarities (tương đồng), differences (khác biệt), effects (hiệu quả) }

   - **2. Đối với Văn xuôi / Truyện ngắn / Tiểu thuyết (Prose / Story):**
     - **Yêu cầu:** Tác phẩm so sánh ('comparativeText') BẮT BUỘC phải là một truyện ngắn or tiểu thuyết khác, có cùng chủ đề or kiểu nhân vật, của một tác giả khác.
     - **Mục tiêu:** So sánh cách kể chuyện, xây dựng nhân vật, points nhìn trần thuật và thông điệp nhân văn.
     - **Lựa chọn gợi ý:** Nếu truyện chính là hiện thực → chọn một truyện hiện thực khác. Nếu truyện có yếu tố tượng trưng/tâm lý → chọn tác phẩm cùng phong cách.
     - **Structure:**
       + initialAnalysis: Tóm tắt chủ đề, thông điệp, lối kể chuyện.
       + comparativeText: { title, author }
       + comparison: { similarities, differences, effects }

   - **3. Đối với Thơ (Poetry):**
     - **Yêu cầu:** Tác phẩm so sánh là một bài thơ khác, cùng or khác thời kỳ, có chủ đề or nguồn cảm hứng tương tự (tình yêu, quê hương, chiến tranh, v.v.).
     - **Mục tiêu:** Làm nổi bật giọng điệu, hình ảnh nghệ thuật, thủ pháp tu từ và cảm xúc trữ tình.
     - **Lựa chọn gợi ý:** Nếu thơ cổ điển → so sánh với thơ hiện đại cùng chủ đề. Nếu thơ hiện đại → so sánh với tác phẩm cùng thời kỳ.
     - **Structure:**
       + initialAnalysis: Nhận xét về chủ đề, cảm xúc chủ đạo, phong cách thơ.
       + comparativeText: { title, author }
       + comparison: { similarities, differences, effects }


### YÊU CẦU ĐẦU RA JSON ###
- title, characteristics, keywords, generalAnalysis ({thematicAnalysis, linguisticInsights}), poetryAnalysis (nếu là thơ), storyAnalysis (nếu là truyện), dramaAnalysis (nếu là kịch), concludingThoughts, intertextuality.`;

        const schema = {
            type: "OBJECT",
            properties: {
                "title": {
                    "type": "STRING"
                },
                "characteristics": {
                    "type": "OBJECT",
                    properties: {
                        "Thể loại": {
                            "type": "STRING"
                        },
                        "Phong cách": {
                            "type": "STRING"
                        },
                        "Giọng điệu": {
                            "type": "STRING"
                        },
                    },
                    "required": ["Thể loại", "Phong cách", "Giọng điệu"]
                },
                "keywords": {
                    "type": "ARRAY",
                    "items": {
                        "type": "STRING"
                    }
                },
                "generalAnalysis": {
                    "type": "OBJECT",
                    properties: {
                        "thematicAnalysis": {
                            "type": "ARRAY",
                            "items": {
                                "type": "OBJECT",
                                properties: {
                                    "heading": {
                                        "type": "STRING"
                                    },
                                    "content": {
                                        "type": "STRING"
                                    },
                                },
                                "required": ["heading", "content"]
                            }
                        },
                        "linguisticInsights": {
                            "type": "OBJECT",
                            properties: {
                                "motifs": {
                                    "type": "ARRAY",
                                    "items": {
                                        "type": "STRING"
                                    }
                                },
                                "symbolsAndMetaphors": {
                                    "type": "ARRAY",
                                    "items": {
                                        "type": "STRING"
                                    }
                                },
                                "temporalElements": {
                                    "type": "STRING"
                                },
                                "natureAndScenery": {
                                    "type": "STRING"
                                }
                            },
                            "required": ["motifs", "symbolsAndMetaphors", "temporalElements", "natureAndScenery"]
                        }
                    },
                    "required": ["thematicAnalysis", "linguisticInsights"]
                },
                "poetryAnalysis": {
                    "type": "OBJECT",
                    "properties": {
                        "rhyming": {
                            "type": "STRING"
                        },
                        "emotionalFlow": {
                            "type": "STRING"
                        },
                        "structure": {
                            "type": "STRING"
                        },
                        "imagery": {
                            "type": "STRING"
                        },
                        "rhythm": {
                            "type": "STRING"
                        },
                        "spaceTimeOrganization": {
                            "type": "STRING"
                        },
                        "rhetoricalDevices": {
                            "type": "STRING"
                        }
                    }
                },
                "storyAnalysis": {
                    "type": "OBJECT",
                    "properties": {
                        "character": {
                            "type": "STRING"
                        },
                        "space": {
                            "type": "STRING"
                        },
                        "time": {
                            "type": "STRING"
                        },
                        "dialogue": {
                            "type": "STRING"
                        }, // Includes both dialogue and internal monologue
                        "narrator": {
                            "type": "STRING"
                        },
                        "situation": {
                            "type": "STRING"
                        },
                        "prominentDetails": {
                            "type": "STRING"
                        }
                    }
                },
                "dramaAnalysis": {
                    "type": "OBJECT",
                    "properties": {
                        "characters": {
                            "type": "STRING"
                        },
                        "conflict": {
                            "type": "STRING"
                        },
                        "action": {
                            "type": "STRING"
                        },
                        "structure": {
                            "type": "STRING"
                        },
                        "dialogue": {
                            "type": "STRING"
                        }
                    }
                },
                "concludingThoughts": {
                    "type": "OBJECT",
                    "properties": {
                        "lesson": {
                            "type": "STRING"
                        },
                        "message": {
                            "type": "STRING"
                        }
                    },
                    "required": ["lesson", "message"]
                },
                "intertextuality": {
                    "type": "OBJECT",
                    "properties": {
                        "initialAnalysis": {
                            "type": "STRING"
                        },
                        "comparativeText": {
                            "type": "OBJECT",
                            "properties": {
                                "title": {
                                    "type": "STRING"
                                },
                                "author": {
                                    "type": "STRING"
                                }
                            },
                            "required": ["title", "author"]
                        },
                        "comparison": {
                            "type": "OBJECT",
                            "properties": {
                                "similarities": {
                                    "type": "STRING"
                                },
                                "differences": {
                                    "type": "STRING"
                                },
                                "effects": {
                                    "type": "STRING"
                                }
                            },
                            "required": ["similarities", "differences", "effects"]
                        }
                    },
                    "required": ["initialAnalysis", "comparativeText", "comparison"]
                }
            },
            "required": ["title", "characteristics", "keywords", "generalAnalysis", "concludingThoughts", "intertextuality"]
        };

        const analysis = await makeApiCall(systemPrompt, `Phân tích văn bản sau: ${text}`, schema);
        if (analysis) {
            analysis.stats = performLinguisticAnalysis(text);
        }
        return analysis;
    }

    async function generateSlideshowContent(analysisContext) {
        const systemPrompt = `Bạn là một chuyên gia biên tập nội dung. Dựa trên phân tích văn học chi tiết sau (dạng JSON), hãy tóm tắt các points chính từ các phần phân tích chuyên sâu (poetryAnalysis, storyAnalysis, dramaAnalysis) và các phần khác thành nội dung ngắn gọn cho các slide thuyết trình. Mỗi slide nên có tiêu đề (heading) và nội dung (content) súc tích, dễ đọc. Đồng thời, cung cấp thêm 3-4 bài báo nghiên cứu học thuật liên quan (về tác phẩm, tác giả, or thể loại), định dạng theo chuẩn MLA Việt hóa (Tác giả. <i>Tên sách</i>. NXB, Năm. or Tác giả. "Tên bài báo." <i>Tên tạp chí</i>, số, năm, trang.). Trả về một đối tượng JSON.`;
        const schema = {
            type: "OBJECT",
            properties: {
                "slides": {
                    "type": "ARRAY",
                    "items": {
                        "type": "OBJECT",
                        "properties": {
                            "heading": {
                                "type": "STRING"
                            },
                            "content": {
                                "type": "STRING"
                            }
                        },
                        "required": ["heading", "content"]
                    }
                },
                "references": {
                    "type": "ARRAY",
                    "items": {
                        "type": "STRING"
                    }
                },
            },
            "required": ["slides", "references"]
        };
        return await makeApiCall(systemPrompt, analysisContext, schema);
    }

    async function generateQuizContent(analysisContext) {
        const systemPrompt = `Dựa trên phân tích văn học sau đây, hãy tạo một bài kiểm tra trắc nghiệm gồm 3 câu hỏi. Mỗi câu hỏi phải có 3 lựa chọn và một câu trả lời đúng. Trả về một đối tượng JSON.`;
        const schema = {
            type: "OBJECT",
            properties: {
                "quiz": {
                    "type": "ARRAY",
                    "items": {
                        "type": "OBJECT",
                        "properties": {
                            "q": {
                                "type": "STRING"
                            },
                            "o": {
                                "type": "ARRAY",
                                "items": {
                                    "type": "STRING"
                                }
                            },
                            "a": {
                                "type": "INTEGER"
                            },
                        },
                        "required": ["q", "o", "a"]
                    }
                }
            },
            "required": ["quiz"]
        };
        return await makeApiCall(systemPrompt, analysisContext, schema);
    }

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
            // Removed plot for Story analysis as per last instructions
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
            html += `<h2>Lessons & Messages</h2>`;
            const ct = result.concludingThoughts;
            if (ct.lesson) html += `<h3>Lesson</h3><p>${ct.lesson}</p>`;
            if (ct.message) html += `<h3>Message</h3><p>${ct.message}</p>`;
        }

        if (result.intertextuality) {
            html += `<h2>Intertextuality Analysis</h2>`;
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
                heading: 'References',
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
                    <h2 class="text-2xl font-bold">${currentAnalysisData.title || 'Presentation'}</h2>
                    <p class="text-slate-600">Presentation summarizing analysis content.</p>
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
                    <h2 class="text-2xl font-bold">Quiz: ${result.title || 'Text'}</h2>
                    <p class="text-slate-600">Test your knowledge of the work.</p>
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
                <button id="submit-quiz" class="px-6 py-2 bg-indigo-600 text-white font-semibold rounded-lg hover:bg-indigo-700">Submit</button>
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
            resultsDiv.textContent = `Your score: ${score} / ${result.quiz.length}`;
            resultsDiv.style.color = score / result.quiz.length > 0.5 ? 'green' : 'red';
        });
    }
</script>