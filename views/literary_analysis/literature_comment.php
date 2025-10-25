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
            displayAnalysis(analysisResult); // This will now call the image/comment functions
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
    async function makeApiCall(systemPrompt, userText, schema) {
        const apiKey = ""; // This is handled by the execution environment.
        const apiUrl = `https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash-preview-05-20:generateContent?key=${apiKey}`;
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
            alert('Đã xảy ra lỗi khi giao tiếp với AI. Vui lòng thử lại.');
            return null;
        }
    }

    async function performMainAnalysis(text) {
        const systemPrompt = `Bạn là một chuyên gia phân tích văn học. Phân tích văn bản được cung cấp và trả về một đối tượng JSON. Nội dung phải bằng tiếng Việt và chi tiết, sâu sắc.

### QUY TẮC PHÂN LOẠI THỂ LOẠI ###
Dựa vào các quy tắc sau để xác định 'Thể loại' trong 'characteristics'. Hãy chú ý đến cấu trúc tổng thể và đặc điểm nổi trội nhất. Khi trả về giá trị cho 'Thể loại', hãy sử dụng định dạng: "**[Tên thể loại chính] – [Tên thể loại phụ/đặc điểm]**".
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

- **Nếu là Kịch (dramaAnalysis):** Phân tích chi tiết thành các ý nhỏ, mỗi ý có tiêu đề in đậm và xuống dòng theo định dạng: "**Tiêu đề ý:**\\n• Nội dung phân tích. <i>Dẫn chứng...</i>" cho các mục: **characters, conflict, action, structure, dialogue**.
- **Nếu là Thơ (poetryAnalysis):** Phân tích các mục sau: **rhyming, emotionalFlow, structure, imagery, rhythm, spaceTimeOrganization, rhetoricalDevices**.
- **Nếu là Truyện (storyAnalysis):** Phân tích từng mục thành các ý nhỏ, mỗi ý có tiêu đề in đậm và xuống dòng theo định dạng: "**Tiêu đề ý:**\\n• Nội dung phân tích. <i>Dẫn chứng...</i>" cho các mục: **character, space, time, dialogue (bao gồm cả đối thoại và độc thoại nội tâm), narrator, situation (tình huống truyện), prominentDetails (chi tiết nổi bật)**.

### PHÂN TÍCH CUỐI CÙNG ###
1. 'concludingThoughts': Rút ra 'lesson' (Bài học) và 'message' (Thông điệp).
2. 'intertextuality' (Phân tích Liên văn bản):
   - **Quy tắc chung:** Phân tích phải dựa trên thể loại của tác phẩm chính.
   
   - **1. Đối với Kịch (Drama):**
     - **Yêu cầu:** Tác phẩm so sánh ('comparativeText') BẮT BUỘC phải là một vở kịch của một tác giả khác.
     - **Mục tiêu:** Làm nổi bật ý tưởng, phong cách biểu đạt, xung đột kịch và thông điệp nhân văn.
     - **Cấu trúc:**
       + initialAnalysis: Nhận xét chung về nội dung, chủ đề, phong cách của vở kịch chính.
       + comparativeText: { title, author }
       + comparison: { similarities (tương đồng), differences (khác biệt), effects (hiệu quả) }

   - **2. Đối với Văn xuôi / Truyện ngắn / Tiểu thuyết (Prose / Story):**
     - **Yêu cầu:** Tác phẩm so sánh ('comparativeText') BẮT BUỘC phải là một truyện ngắn hoặc tiểu thuyết khác, có cùng chủ đề hoặc kiểu nhân vật, của một tác giả khác.
     - **Mục tiêu:** So sánh cách kể chuyện, xây dựng nhân vật, điểm nhìn trần thuật và thông điệp nhân văn.
     - **Lựa chọn gợi ý:** Nếu truyện chính là hiện thực → chọn một truyện hiện thực khác. Nếu truyện có yếu tố tượng trưng/tâm lý → chọn tác phẩm cùng phong cách.
     - **Cấu trúc:**
       + initialAnalysis: Tóm tắt chủ đề, thông điệp, lối kể chuyện.
       + comparativeText: { title, author }
       + comparison: { similarities, differences, effects }

   - **3. Đối với Thơ (Poetry):**
     - **Yêu cầu:** Tác phẩm so sánh là một bài thơ khác, cùng hoặc khác thời kỳ, có chủ đề hoặc nguồn cảm hứng tương tự (tình yêu, quê hương, chiến tranh, v.v.).
     - **Mục tiêu:** Làm nổi bật giọng điệu, hình ảnh nghệ thuật, thủ pháp tu từ và cảm xúc trữ tình.
     - **Lựa chọn gợi ý:** Nếu thơ cổ điển → so sánh với thơ hiện đại cùng chủ đề. Nếu thơ hiện đại → so sánh với tác phẩm cùng thời kỳ.
     - **Cấu trúc:**
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
        const systemPrompt = `Bạn là một chuyên gia biên tập nội dung. Dựa trên phân tích văn học chi tiết sau (dạng JSON), hãy tóm tắt các điểm chính từ các phần phân tích chuyên sâu (poetryAnalysis, storyAnalysis, dramaAnalysis) và các phần khác thành nội dung ngắn gọn cho các slide thuyết trình. Mỗi slide nên có tiêu đề (heading) và nội dung (content) súc tích, dễ đọc. Đồng thời, cung cấp thêm 3-4 bài báo nghiên cứu học thuật liên quan (về tác phẩm, tác giả, hoặc thể loại), định dạng theo chuẩn MLA Việt hóa (Tác giả. <i>Tên sách</i>. NXB, Năm. hoặc Tác giả. "Tên bài báo." <i>Tên tạp chí</i>, số, năm, trang.). Trả về một đối tượng JSON.`;
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

    // === CÁC HÀM TẠO ẢNH VÀ COMMENT ĐƯỢC THÊM VÀO ===

    // === HELPER: Tạo HTML cho khối ảnh với comment ===
    function createImageBlock(imageData, sectionName) {
        return `
                <div class="content-image-wrapper" data-section="${sectionName}" style="margin-bottom: 2px;">
                    <img src="${imageData}" alt="${sectionName}" style="display: block; margin: 0; padding: 0;">
                    <div class="fb-comment-block" data-section="${sectionName}" style="margin-top: 0; padding: 8px; background: #ffffff; border-radius: 8px; border: 1px solid #e5e7eb;">
                        <div class="fb-comments-list" style="max-height: 400px; overflow-y: auto; margin-bottom: 8px;"></div>
                        <div style="display: flex; gap: 8px; align-items: center;">
                            <img src="https://i.pravatar.cc/32?img=0" class="rounded-full" style="width: 32px; height: 32px;" alt="avatar">
                            <input type="text" class="fb-comment-input" placeholder="Viết bình luận..." style="flex: 1; padding: 8px 12px; border: 1px solid #d1d5db; border-radius: 20px; font-size: 14px; background: #f0f2f5;">
                            <button class="fb-comment-btn" style="padding: 8px 16px; background: #4f46e5; color: white; border: none; border-radius: 6px; cursor: pointer; font-weight: 600; font-size: 14px; transition: all 0.2s;">Gửi</button>
                        </div>
                    </div>
                </div>
            `;
    }

    // --- Text to Image Converter with HTML Parsing ---
    async function textToImage(text, width = 800, options = {}) {
        const {
            fontSize = 16,
                fontFamily = 'Inter, sans-serif',
                lineHeight = 1.6,
                padding = 30,
                backgroundColor = '#ffffff',
                textColor = '#374151',
                titleColor = '#111827',
                maxWidth = width - (padding * 2)
        } = options;

        // Parse HTML và tạo các đoạn text với formatting
        const parser = new DOMParser();
        const doc = parser.parseFromString(`<div>${text}</div>`, 'text/html');
        const container = doc.querySelector('div');

        // Tạo canvas tạm để đo kích thước
        const tempCanvas = document.createElement('canvas');
        const tempCtx = tempCanvas.getContext('2d');

        // Xử lý các đoạn văn và format
        const segments = [];

        function parseNode(node, style = {}) {
            if (node.nodeType === Node.TEXT_NODE) {
                const text = node.textContent;
                if (text.trim()) {
                    segments.push({
                        text: text,
                        style: {
                            ...style
                        }
                    });
                }
            } else if (node.nodeType === Node.ELEMENT_NODE) {
                const newStyle = {
                    ...style
                };

                switch (node.tagName.toLowerCase()) {
                    case 'i':
                    case 'em':
                        newStyle.italic = true;
                        break;
                    case 'b':
                    case 'strong':
                        newStyle.bold = true;
                        break;
                    case 'u':
                        newStyle.underline = true;
                        break;
                    case 'br':
                        segments.push({
                            text: '\n',
                            style: {}
                        });
                        return;
                    case 'p':
                    case 'div':
                        if (segments.length > 0) {
                            segments.push({
                                text: '\n',
                                style: {}
                            });
                        }
                        break;
                }

                Array.from(node.childNodes).forEach(child => parseNode(child, newStyle));

                if (['p', 'div'].includes(node.tagName.toLowerCase())) {
                    segments.push({
                        text: '\n',
                        style: {}
                    });
                }
            }
        }

        parseNode(container);

        // Tạo các dòng với format
        const lines = [];
        let currentLine = [];
        let currentWidth = 0;

        segments.forEach(segment => {
            if (segment.text === '\n') {
                if (currentLine.length > 0) {
                    lines.push([...currentLine]);
                    currentLine = [];
                    currentWidth = 0;
                }
                return;
            }

            const words = segment.text.split(' ');
            words.forEach((word, idx) => {
                if (!word && idx > 0) return;

                const testWord = (idx > 0 ? ' ' : '') + word;

                // Đo kích thước với style
                let font = `${fontSize}px ${fontFamily}`;
                if (segment.style.bold && segment.style.italic) {
                    font = `italic bold ${fontSize}px ${fontFamily}`;
                } else if (segment.style.bold) {
                    font = `bold ${fontSize}px ${fontFamily}`;
                } else if (segment.style.italic) {
                    font = `italic ${fontSize}px ${fontFamily}`;
                }
                tempCtx.font = font;

                const metrics = tempCtx.measureText(testWord);

                if (currentWidth + metrics.width > maxWidth && currentLine.length > 0) {
                    lines.push([...currentLine]);
                    currentLine = [{
                        text: word,
                        style: segment.style
                    }];
                    currentWidth = tempCtx.measureText(word).width;
                } else {
                    currentLine.push({
                        text: testWord,
                        style: segment.style
                    });
                    currentWidth += metrics.width;
                }
            });
        });

        if (currentLine.length > 0) {
            lines.push(currentLine);
        }

        // Tính chiều cao canvas
        const lineHeightPx = fontSize * lineHeight;
        const height = Math.max((lines.length * lineHeightPx) + (padding * 2), 100);

        // Tạo canvas chính
        const canvas = document.createElement('canvas');
        canvas.width = width;
        canvas.height = height;
        const ctx = canvas.getContext('2d');

        // Vẽ nền
        ctx.fillStyle = backgroundColor;
        ctx.fillRect(0, 0, width, height);

        // Vẽ text với formatting
        ctx.textBaseline = 'top';

        lines.forEach((line, lineIndex) => {
            let x = padding;
            const y = padding + (lineIndex * lineHeightPx);

            line.forEach(segment => {
                const style = segment.style;

                // Set font style
                let font = `${fontSize}px ${fontFamily}`;
                if (style.bold && style.italic) {
                    font = `italic bold ${fontSize}px ${fontFamily}`;
                } else if (style.bold) {
                    font = `bold ${fontSize}px ${fontFamily}`;
                } else if (style.italic) {
                    font = `italic ${fontSize}px ${fontFamily}`;
                }
                ctx.font = font;
                ctx.fillStyle = textColor;

                // Vẽ text
                ctx.fillText(segment.text, x, y);

                // Vẽ underline nếu cần
                if (style.underline) {
                    const metrics = ctx.measureText(segment.text);
                    ctx.strokeStyle = textColor;
                    ctx.lineWidth = 1;
                    ctx.beginPath();
                    ctx.moveTo(x, y + fontSize + 2);
                    ctx.lineTo(x + metrics.width, y + fontSize + 2);
                    ctx.stroke();
                }

                x += ctx.measureText(segment.text).width;
            });
        });

        return canvas.toDataURL('image/png');
    }

    async function htmlSectionToImage(htmlContent, title, width = 800) {
        // Chuyển HTML thành plain text với format
        const tempDiv = document.createElement('div');
        tempDiv.innerHTML = htmlContent;

        // Xử lý các thẻ HTML
        let text = title ? title + '\n\n' : '';

        // Lấy text từ HTML, giữ nguyên cấu trúc
        tempDiv.querySelectorAll('*').forEach(el => {
            if (el.tagName === 'H2' || el.tagName === 'H3' || el.tagName === 'H4') {
                text += '\n' + el.textContent.trim() + '\n';
            } else if (el.tagName === 'P') {
                text += el.textContent.trim() + '\n\n';
            } else if (el.tagName === 'LI') {
                text += '• ' + el.textContent.trim() + '\n';
            } else if (el.tagName === 'STRONG') {
                text += el.textContent.trim();
            }
        });

        return await textToImage(text, width, {
            fontSize: 15,
            lineHeight: 1.7,
            padding: 25,
            backgroundColor: '#f9fafb',
            textColor: '#1f2937'
        });
    }

    // --- Display Functions ---
    // *** THAY THẾ hàm displayAnalysis cũ BẰNG HÀM MỚI NÀY ***
    async function displayAnalysis(result) {
        if (!result) return;

        contentAnalysis.innerHTML = '<div class="text-center py-8"><p class="text-slate-600">Đang chuyển đổi nội dung thành hình ảnh...</p></div>';

        let html = `<h1>${result.title || 'Không có tiêu đề'}</h1>`;

        const formatDetailedAnalysis = (text) => {
            if (!text) return '';
            // The AI returns text with markdown-like bolding for titles.
            // Replace **Title** with <strong>Title</strong> and newlines with <br>
            return text
                .replace(/\*\*(.*?):\*\*/g, '<br><strong>$1:</strong>')
                .replace(/\n/g, '<br>');
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
                html += `<div id="thematic-images"></div>`; // Placeholder for images
            }

            // Linguistic Insights within General Analysis
            if (ga.linguisticInsights) {
                html += `<h3>Ngôn ngữ & Biểu tượng</h3>`; // Sub-heading
                html += `<div id="linguistic-images"></div>`; // Placeholder for images
            }
        }


        // --- Genre-Specific Analysis ---
        if (result.storyAnalysis) {
            html += `<h2>Phân tích Truyện</h2>`;
            html += `<div id="story-images"></div>`;
        }

        if (result.dramaAnalysis) {
            html += `<h2>Phân tích Kịch</h2>`;
            html += `<div id="drama-images"></div>`;
        }

        if (result.poetryAnalysis) {
            html += `<h2>Phân tích Thơ ca</h2>`;
            html += `<div id="poetry-images"></div>`;
        }

        // --- Concluding Sections ---
        if (result.concludingThoughts) {
            html += `<h2>Bài học & Thông điệp</h2>`;
            html += `<div id="conclusion-images"></div>`;
        }

        if (result.intertextuality) {
            html += `<h2>Phân tích Liên văn bản</h2>`;
            html += `<div id="intertextuality-images"></div>`;
        }


        contentAnalysis.innerHTML = html;

        // Tạo ảnh cho từng phần (async)
        await generateContentImages(result);
    }

    // Tạo hình ảnh cho tất cả nội dung
    async function generateContentImages(result) {
        const formatDetailedAnalysis = (text) => {
            if (!text) return '';
            return text
                .replace(/\*\*(.*?):\*\*/g, '\n$1:\n')
                .replace(/\n/g, '\n');
        };

        // 1. Phân tích chủ đề (trong Phân tích chung)
        if (result.generalAnalysis && result.generalAnalysis.thematicAnalysis) {
            const container = document.getElementById('thematic-images');
            for (const section of result.generalAnalysis.thematicAnalysis) {
                const text = `${section.heading}\n\n${section.content}`;
                const imageData = await textToImage(text, 750, {
                    fontSize: 15,
                    lineHeight: 1.7,
                    backgroundColor: '#f9fafb',
                    textColor: '#1f2937'
                });
                container.innerHTML += createImageBlock(imageData, section.heading);
            }
        }

        // 2. Phân tích ngôn ngữ & biểu tượng (trong Phân tích chung)
        if (result.generalAnalysis && result.generalAnalysis.linguisticInsights) {
            const container = document.getElementById('linguistic-images');
            const insights = result.generalAnalysis.linguisticInsights;

            // Mô-típ lặp lại
            if (insights.motifs?.length > 0) {
                let text = 'Mô-típ lặp lại\n\n';
                insights.motifs.forEach(item => {
                    text += `• ${item}\n`;
                });
                const imageData = await textToImage(text, 750, {
                    fontSize: 14,
                    lineHeight: 1.7,
                    backgroundColor: '#e0f2fe',
                    textColor: '#075985'
                });
                container.innerHTML += createImageBlock(imageData, 'Mô-típ lặp lại');
            }

            // Biểu tượng & Ẩn dụ
            if (insights.symbolsAndMetaphors?.length > 0) {
                let text = 'Biểu tượng & Ẩn dụ\n\n';
                insights.symbolsAndMetaphors.forEach(item => {
                    text += `• ${item}\n`;
                });
                const imageData = await textToImage(text, 750, {
                    fontSize: 14,
                    lineHeight: 1.7,
                    backgroundColor: '#e0f2fe',
                    textColor: '#075985'
                });
                container.innerHTML += createImageBlock(imageData, 'Biểu tượng & Ẩn dụ');
            }

            // Yếu tố thời gian
            if (insights.temporalElements) {
                const text = `Yếu tố Thời gian\n\n${insights.temporalElements}`;
                const imageData = await textToImage(text, 750, {
                    fontSize: 14,
                    lineHeight: 1.7,
                    backgroundColor: '#e0f2fe',
                    textColor: '#075985'
                });
                container.innerHTML += createImageBlock(imageData, 'Yếu tố Thời gian');
            }

            // Thiên nhiên & Phong cảnh
            if (insights.natureAndScenery) {
                const text = `Thiên nhiên & Phong cảnh\n\n${insights.natureAndScenery}`;
                const imageData = await textToImage(text, 750, {
                    fontSize: 14,
                    lineHeight: 1.7,
                    backgroundColor: '#e0f2fe',
                    textColor: '#075985'
                });
                container.innerHTML += createImageBlock(imageData, 'Thiên nhiên & Phong cảnh');
            }
        }

        // 3. Phân tích truyện
        if (result.storyAnalysis) {
            const container = document.getElementById('story-images');
            const sa = result.storyAnalysis;
            const sections = [{
                    title: 'Nhân vật',
                    content: sa.character
                },
                {
                    title: 'Không gian',
                    content: sa.space
                },
                {
                    title: 'Thời gian',
                    content: sa.time
                },
                {
                    title: 'Lời thoại & Độc thoại nội tâm',
                    content: sa.dialogue
                },
                {
                    title: 'Người kể chuyện',
                    content: sa.narrator
                },
                {
                    title: 'Tình huống truyện',
                    content: sa.situation
                },
                {
                    title: 'Chi tiết nổi bật',
                    content: sa.prominentDetails
                }
            ];

            for (const section of sections) {
                if (section.content) {
                    const text = `${section.title}\n\n${formatDetailedAnalysis(section.content)}`;
                    const imageData = await textToImage(text, 750, {
                        fontSize: 14,
                        lineHeight: 1.7,
                        backgroundColor: '#f0f9ff',
                        textColor: '#1e40af'
                    });
                    container.innerHTML += createImageBlock(imageData, section.title);
                }
            }
        }

        // 4. Phân tích kịch
        if (result.dramaAnalysis) {
            const container = document.getElementById('drama-images');
            const da = result.dramaAnalysis;
            const sections = [{
                    title: 'Xung đột',
                    content: da.conflict
                },
                {
                    title: 'Hành động',
                    content: da.action
                },
                {
                    title: 'Nhân vật',
                    content: da.characters
                },
                {
                    title: 'Cấu trúc',
                    content: da.structure
                },
                {
                    title: 'Đối thoại',
                    content: da.dialogue
                }
            ];

            for (const section of sections) {
                if (section.content) {
                    const text = `${section.title}\n\n${formatDetailedAnalysis(section.content)}`;
                    const imageData = await textToImage(text, 750, {
                        fontSize: 14,
                        lineHeight: 1.7,
                        backgroundColor: '#fef3c7',
                        textColor: '#92400e'
                    });
                    container.innerHTML += createImageBlock(imageData, section.title);
                }
            }
        }

        // 5. Phân tích thơ
        if (result.poetryAnalysis) {
            const container = document.getElementById('poetry-images');
            const pa = result.poetryAnalysis;
            const sections = [{
                    title: 'Gieo vần trong thơ',
                    content: pa.rhyming
                },
                {
                    title: 'Mạch cảm xúc',
                    content: pa.emotionalFlow
                },
                {
                    title: 'Cấu trúc bài thơ',
                    content: pa.structure
                },
                {
                    title: 'Hình ảnh trong thơ',
                    content: pa.imagery
                },
                {
                    title: 'Phân tích Nhịp điệu',
                    content: pa.rhythm
                },
                {
                    title: 'Tổ chức không gian & thời gian',
                    content: pa.spaceTimeOrganization
                },
                {
                    title: 'Biện pháp tu từ',
                    content: pa.rhetoricalDevices
                }
            ];

            for (const section of sections) {
                if (section.content) {
                    const text = `${section.title}\n\n${section.content}`;
                    const imageData = await textToImage(text, 750, {
                        fontSize: 14,
                        lineHeight: 1.7,
                        backgroundColor: '#fce7f3',
                        textColor: '#831843'
                    });
                    container.innerHTML += createImageBlock(imageData, section.title);
                }
            }
        }

        // 6. Bài học & thông điệp
        if (result.concludingThoughts) {
            const container = document.getElementById('conclusion-images');
            const ct = result.concludingThoughts;

            if (ct.lesson) {
                const text = `Bài học\n\n${ct.lesson}`;
                const imageData = await textToImage(text, 750, {
                    fontSize: 15,
                    lineHeight: 1.8,
                    backgroundColor: '#dcfce7',
                    textColor: '#14532d'
                });
                container.innerHTML += createImageBlock(imageData, 'Bài học');
            }

            if (ct.message) {
                const text = `Thông điệp\n\n${ct.message}`;
                const imageData = await textToImage(text, 750, {
                    fontSize: 15,
                    lineHeight: 1.8,
                    backgroundColor: '#dcfce7',
                    textColor: '#14532d'
                });
                container.innerHTML += createImageBlock(imageData, 'Thông điệp');
            }
        }

        // 7. Liên văn bản
        if (result.intertextuality) {
            const container = document.getElementById('intertextuality-images');
            const it = result.intertextuality;

            let text = '';
            if (it.initialAnalysis) {
                text += it.initialAnalysis + '\n\n';
            }
            if (it.comparativeText) {
                text += `So sánh với: ${it.comparativeText.title} - ${it.comparativeText.author}\n\n`;
            }
            if (it.comparison) {
                if (it.comparison.similarities) {
                    text += `Điểm tương đồng:\n${it.comparison.similarities}\n\n`;
                }
                if (it.comparison.differences) {
                    text += `Điểm khác biệt:\n${it.comparison.differences}\n\n`;
                }
                if (it.comparison.effects) {
                    text += `Hiệu quả:\n${it.comparison.effects}`;
                }
            }

            const imageData = await textToImage(text, 750, {
                fontSize: 14,
                lineHeight: 1.7,
                backgroundColor: '#ede9fe',
                textColor: '#4c1d95'
            });
            container.innerHTML += createImageBlock(imageData, 'Liên văn bản');
        }

        console.log('✅ Đã chuyển đổi tất cả nội dung thành hình ảnh');

        // Setup click handlers và comment system
        setTimeout(() => {
            setupImageBlockHandlers();
            setupFbCommentBlocks();
        }, 500);
    }

    // === KẾT THÚC HÀM TẠO ẢNH ===

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

    // === CHỨC NĂNG HIGHLIGHT VÀ COMMENT CHO KHỐI ẢNH ===
    let selectedBlock = null;

    function setupImageBlockHandlers() {
        const wrappers = document.querySelectorAll('.content-image-wrapper');

        wrappers.forEach(wrapper => {
            const section = wrapper.getAttribute('data-section') || wrapper.querySelector('img')?.alt || 'Unknown';
            wrapper.setAttribute('data-section', section);

            // Click để select
            wrapper.addEventListener('click', function(e) {
                // Không toggle khi click vào comment block
                if (e.target.closest('.fb-comment-block') ||
                    e.target.classList.contains('comment-btn') ||
                    e.target.classList.contains('comment-input') ||
                    e.target.classList.contains('fb-comment-btn') ||
                    e.target.classList.contains('fb-comment-input') ||
                    e.target.classList.contains('like-btn') ||
                    e.target.classList.contains('reply-btn')) {
                    return;
                }

                // Remove previous selection
                if (selectedBlock && selectedBlock !== this) {
                    selectedBlock.classList.remove('selected');
                }

                // Toggle current
                this.classList.toggle('selected');
                selectedBlock = this.classList.contains('selected') ? this : null;

                if (selectedBlock) {
                    console.log(`📌 Đã chọn đoạn: "${section}"`);
                }
            });
        });
    }

    // Facebook-style comment logic
    const FAKE_USERS = [{
            name: 'Nguyễn Văn A',
            avatar: 'https://i.pravatar.cc/40?img=1'
        },
        {
            name: 'Trần Thị B',
            avatar: 'https://i.pravatar.cc/40?img=2'
        },
        {
            name: 'Lê Văn C',
            avatar: 'https://i.pravatar.cc/40?img=3'
        },
        {
            name: 'Phạm Thị D',
            avatar: 'https://i.pravatar.cc/40?img=4'
        }
    ];

    function getRandomUser() {
        return FAKE_USERS[Math.floor(Math.random() * FAKE_USERS.length)];
    }

    function getFbComments(section) {
        try {
            return JSON.parse(localStorage.getItem('fb_comments_E' + section)) || [];
        } catch {
            return [];
        }
    }

    function saveFbComments(section, comments) {
        localStorage.setItem('fb_comments_' + section, JSON.stringify(comments));
    }

    function addFbComment(section, text, parentId = null) {
        const comments = getFbComments(section);
        const user = getRandomUser();
        const newComment = {
            id: Date.now() + Math.random().toString(36).slice(2),
            user,
            text,
            time: new Date().toLocaleString('vi-VN'),
            likes: 0,
            parentId,
            replies: []
        };
        if (parentId) {
            // Reply
            const parent = comments.find(c => c.id === parentId);
            if (parent) parent.replies.push(newComment);
        } else {
            comments.push(newComment);
        }
        saveFbComments(section, comments);
    }

    function likeFbComment(section, commentId) {
        const comments = getFbComments(section);

        function likeRecursive(list) {
            for (const c of list) {
                if (c.id === commentId) {
                    c.likes++;
                    return true;
                }
                if (c.replies && likeRecursive(c.replies)) return true;
            }
            return false;
        }
        likeRecursive(comments);
        saveFbComments(section, comments);
    }

    function renderFbComments(section, container, parentList = null) {
        const comments = parentList || getFbComments(section);

        if (comments.length === 0 && !parentList) {
            container.innerHTML = '<p style="color: #9ca3af; font-size: 13px; font-style: italic; padding: 10px; text-align: center;">Chưa có bình luận nào. Hãy là người đầu tiên!</p>';
            return;
        }

        container.innerHTML = comments.map((c, idx) => `
    <div class="fb-comment-item" data-comment-id="${c.id}" style="margin-bottom: 8px;">
      <div style="display: flex; gap: 6px;">
        <img src="${c.user.avatar}" style="width: 28px; height: 28px; border-radius: 50%; flex-shrink: 0;" alt="avatar">
        <div style="flex: 1;">
          <div style="background: #f0f2f5; border-radius: 16px; padding: 6px 10px; display: inline-block; max-width: 100%;">
            <div style="font-weight: 600; font-size: 12px; color: #050505;">${c.user.name}</div>
            <div style="font-size: 13px; color: #050505; margin-top: 1px; word-wrap: break-word;">${c.text}</div>
          </div>
          <div style="display: flex; gap: 10px; margin-top: 2px; margin-left: 10px; align-items: center;">
            <button class="like-btn" data-id="${c.id}" style="background: none; border: none; cursor: pointer; font-size: 11px; font-weight: 600; color: ${c.likes > 0 ? '#4f46e5' : '#65676b'}; padding: 0;">
              ${c.likes > 0 ? '👍' : 'Thích'}${c.likes > 0 ? ` ${c.likes}` : ''}
            </button>
            <button class="reply-btn" data-id="${c.id}" data-name="${c.user.name}" style="background: none; border: none; cursor: pointer; font-size: 11px; font-weight: 600; color: #65676b; padding: 0;">Trả lời</button>
            <span style="font-size: 11px; color: #65676b;">${c.time}</span>
          </div>
          <div class="fb-replies" data-parent-id="${c.id}" style="margin-left: 0; margin-top: 4px;"></div>
          <div class="reply-form-${c.id}" style="display: none; margin-top: 4px;"></div>
        </div>
      </div>
    </div>
  `).join('');

        // Setup like buttons
        container.querySelectorAll('.like-btn').forEach(btn => {
            btn.onclick = () => {
                likeFbComment(section, btn.dataset.id);
                const mainContainer = document.querySelector(`.fb-comment-block[data-section="${section}"] .fb-comments-list`);
                renderFbComments(section, mainContainer);
            };
        });

        // Setup reply buttons
        container.querySelectorAll('.reply-btn').forEach(btn => {
            btn.onclick = () => {
                const commentId = btn.dataset.id;
                const replyFormDiv = container.querySelector(`.reply-form-${commentId}`);

                // Toggle reply form
                if (replyFormDiv.style.display === 'flex') {
                    replyFormDiv.style.display = 'none';
                    replyFormDiv.innerHTML = '';
                    return;
                }

                // Hide all other reply forms
                container.querySelectorAll('[class^="reply-form-"]').forEach(form => {
                    form.style.display = 'none';
                    form.innerHTML = '';
                });

                // Show current reply form
                replyFormDiv.style.display = 'flex';
                replyFormDiv.style.gap = '8px';
                replyFormDiv.style.alignItems = 'center';
                replyFormDiv.innerHTML = `
        <img src="https://i.pravatar.cc/32?img=0" style="width: 28px; height: 28px; border-radius: 50%;" alt="avatar">
        <input type="text" class="reply-input-field" placeholder="Trả lời ${btn.dataset.name}..." style="flex: 1; padding: 6px 12px; border: 1px solid #ccd0d5; border-radius: 16px; font-size: 13px; background: #f0f2f5;">
        <button class="reply-send-btn" style="padding: 6px 16px; background: #4f46e5; color: white; border: none; border-radius: 6px; cursor: pointer; font-weight: 600; font-size: 13px;">Gửi</button>
      `;

                const replyInput = replyFormDiv.querySelector('.reply-input-field');
                const sendBtn = replyFormDiv.querySelector('.reply-send-btn');

                replyInput.focus();

                const sendReply = () => {
                    if (replyInput.value.trim()) {
                        addFbComment(section, replyInput.value.trim(), commentId);
                        const mainContainer = document.querySelector(`.fb-comment-block[data-section="${section}"] .fb-comments-list`);
                        renderFbComments(section, mainContainer);
                    }
                };

                sendBtn.onclick = sendReply;
                replyInput.onkeydown = (e) => {
                    if (e.key === 'Enter') {
                        e.preventDefault();
                        sendReply();
                    }
                };
            };
        });

        // Render replies recursively
        comments.forEach((c, idx) => {
            if (c.replies && c.replies.length > 0) {
                const repliesDiv = container.querySelector(`[data-parent-id="${c.id}"]`);
                if (repliesDiv) {
                    renderFbComments(section, repliesDiv, c.replies);
                }
            }
        });
    }

    function setupFbCommentBlocks() {
        document.querySelectorAll('.fb-comment-block').forEach(block => {
            const section = block.getAttribute('data-section');
            const input = block.querySelector('.fb-comment-input');
            const btn = block.querySelector('.fb-comment-btn');
            const list = block.querySelector('.fb-comments-list');
            renderFbComments(section, list);
            btn.onclick = () => {
                if (input.value.trim()) {
                    addFbComment(section, input.value.trim());
                    input.value = '';
                    renderFbComments(section, list);
                }
            };
            input.onkeydown = e => {
                if (e.key === 'Enter') btn.click();
            };
        });
    }
</script>