<?php
// Start session for authentication
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Set header to return JSON
header('Content-Type: application/json');

// Check authentication (skip if already validated by analyze_task.php)
if (!defined('AUTH_PASSED')) {
    if (!isset($_SESSION['user']) || empty($_SESSION['user'])) {
        http_response_code(401);
        echo json_encode(['error' => 'Unauthorized - Please login']);
        exit;
    }
}

// --- Main Function to Call Google Gemini API ---
// This function now accepts a $contents array for chat-style requests.
function callGeminiApi($systemPrompt, $contents, $schema)
{
    $apiKey = "AIzaSyBBPzAU_iuvVsHjZw7sdFhKUakA4HiAL3I"; // This is handled by the execution environment.
    $apiUrl = "https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash-preview-05-20:generateContent?key={$apiKey}";

    $payload = [
        'contents' => $contents, // Use the full contents array for chat
        'systemInstruction' => [
            'parts' => [['text' => $systemPrompt]]
        ],
        'generationConfig' => [
            'responseMimeType' => "application/json",
            'responseSchema' => $schema
        ]
    ];

    $jsonData = json_encode($payload);

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $apiUrl);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonData);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Content-Length: ' . strlen($jsonData)
    ]);

    $response = curl_exec($ch);
    $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);

    if ($httpcode != 200) {
        return ['error' => "API call failed with status code $httpcode: $response $error"];
    }

    $result = json_decode($response, true);

    if (isset($result['candidates'][0]['content']['parts'][0]['text'])) {
        // The API returns a JSON string, so we need to decode it *again*
        return json_decode($result['candidates'][0]['content']['parts'][0]['text'], true);
    } else {
        return ['error' => 'Invalid response structure from API.'];
    }
}

// --- Get Request Data ---
// Check if called from analyze_task.php (data in $_POST) or directly (data in php://input)
if (defined('AUTH_PASSED') && isset($_POST['action'])) {
    // Called from analyze_task.php
    $action = $_POST['action'] ?? '';
    $data = $_POST['data'] ?? '';
} else {
    // Direct call - read from JSON body
    $input = json_decode(file_get_contents('php://input'), true);
    $action = $input['action'] ?? '';
    $data = $input['data'] ?? '';
}

$response = null;

// --- Main Action Switch ---
switch ($action) {
    case 'analyze':
        $systemPrompt = "Bạn là một **Giáo sư – Tiến sĩ chuyên ngành Văn học**, am hiểu sâu sắc về **lý luận, phê bình và phân tích tác phẩm văn chương** ở nhiều giai đoạn, trào lưu và phong cách khác nhau. Bạn có khả năng **giải mã tầng nghĩa tư tưởng, biểu tượng nghệ thuật, cấu trúc ngôn ngữ**, đồng thời **đặt tác phẩm trong mối quan hệ văn hóa – lịch sử và liên văn bản**. Giọng văn của bạn mang phong cách **học thuật, chuẩn mực, logic, có chiều sâu lý luận và cảm xúc nhân văn**. Khi thực hiện phân tích, bạn cần **diễn đạt bằng tiếng Việt học thuật**, tạo **đối tượng JSON hoàn chỉnh, chi tiết và hệ thống**.

───────────────────────────────
### I. QUY TẮC XÁC ĐỊNH THỂ LOẠI (Genre Classification)
───────────────────────────────
1. **Mục tiêu:** Xác định chính xác thể loại văn bản và gán vào trường 'Thể loại' trong 'characteristics' theo định dạng:
   \"[Thể loại chính] – [Thể loại phụ hoặc đặc điểm nổi bật]\".

2. **Phân cấp thể loại:**
   a. **Văn xuôi (Prose):**
      - *Tự sự (Truyện):* Có nhân vật, sự kiện, cốt truyện rõ ràng.
      - *Miêu tả, Biểu cảm, Nghị luận, Thuyết minh, Hành chính – công vụ.*
      - *Kịch:* Nhận diện qua đối thoại, chỉ dẫn sân khấu, xung đột và hành động kịch.
   b. **Thơ (Poetry):**
      - *Lục bát, Song Thất Lục Bát, Thơ tự do, Thơ 4/5/6/7/8 chữ.*
      - *Thất ngôn bát cú / Tứ tuyệt, Thơ châm biếm, Thơ hiện đại.*

───────────────────────────────
### II. QUY TẮC PHÂN TÍCH TỔNG QUÁT (General Analysis)
───────────────────────────────
1. **Cấu trúc mục 'generalAnalysis':**
   Bao gồm hai phần con chính:
   - 'thematicAnalysis': Mảng các đối tượng {heading, content}, trong đó:
     + heading: Tiêu đề chủ đề, viết hoa chữ đầu, không in đậm.
     + content: Diễn giải nội dung bằng văn bản thuần túy.
   - 'linguisticInsights': Phân tích ngôn ngữ và biểu tượng qua 4 mục con, trình bày bằng danh sách gạch đầu dòng (•):
     + motifs: Các mô-típ, hình ảnh lặp lại trong văn bản.
     + symbolsAndMetaphors: Các biểu tượng và ẩn dụ nghệ thuật.
     + temporalElements: Thời gian, ký ức, trình tự kể.
     + natureAndScenery: Không gian, thiên nhiên và vai trò biểu cảm.

───────────────────────────────
### III. PHÂN TÍCH CHUYÊN SÂU THEO THỂ LOẠI (Genre-Specific Deep Analysis)
───────────────────────────────
**Yêu cầu chung:**
- Bắt buộc tạo object phân tích chuyên sâu tương ứng với thể loại đã xác định.
- Mỗi phần phải có cấu trúc rõ ràng, lập luận chặt chẽ, và dẫn chứng cụ thể từ văn bản.

1. **Nếu là Kịch (dramaAnalysis):**
   Phân tích 5 yếu tố then chốt:
   - characters: Hệ thống nhân vật, tính cách, xung đột nội tâm.
   - conflict: Xung đột trung tâm, đối kháng và quá trình phát triển.
   - action: Diễn tiến hành động kịch, sự kiện chính.
   - structure: Bố cục, các hồi, lớp và cao trào.
   - dialogue: Đặc điểm lời thoại, giọng điệu, ngữ cảnh sân khấu.

2. **Nếu là Thơ (poetryAnalysis):**
   Phân tích 7 yếu tố cốt lõi:
   - rhyming: Vần, luật, cách gieo nhịp.
   - emotionalFlow: Diễn biến cảm xúc, cung bậc trữ tình.
   - structure: Bố cục, mạch cảm xúc và tổ chức khổ thơ.
   - imagery: Hình ảnh, biểu tượng, không gian cảm xúc.
   - rhythm: Nhịp điệu, tiết tấu, nhạc tính của ngôn ngữ thơ.
   - spaceTimeOrganization: Sự vận động của thời gian và không gian trữ tình.
   - rhetoricalDevices: Biện pháp tu từ (ẩn dụ, nhân hóa, hoán dụ...).

3. **Nếu là Truyện (storyAnalysis):**
   Phân tích 7 yếu tố nền tảng:
   - character: Tuyến nhân vật, tâm lý, hành động, phát triển.
   - space: Không gian nghệ thuật, ý nghĩa biểu trưng.
   - time: Thời gian kể chuyện, hồi ức, tuyến tính hoặc phi tuyến.
   - dialogue: Ngôn ngữ đối thoại, phong cách trần thuật.
   - narrator: Điểm nhìn và giọng kể của người trần thuật.
   - situation: Tình huống truyện, xung đột, mâu thuẫn trung tâm.
   - prominentDetails: Chi tiết nghệ thuật tiêu biểu, giá trị biểu tượng.

───────────────────────────────
### IV. PHÂN TÍCH CUỐI CÙNG (Final Synthesis)
───────────────────────────────
1. **concludingThoughts:**
   - Rút ra 'lesson' (bài học) và 'message' (thông điệp) mang tính khái quát, nhân văn và giá trị tư tưởng lâu dài.
   - Tổng kết giá trị nội dung và nghệ thuật, nhấn mạnh đóng góp của tác phẩm đối với người đọc và thời đại.

2. **intertextuality (Liên văn bản):**
   - Phân tích dựa theo thể loại của tác phẩm chính.
   - Gồm ba nhánh quy tắc:

     a. **Kịch (Drama):**
        - *Yêu cầu:* Tác phẩm so sánh (comparativeText) phải là một vở kịch của tác giả khác.
        - *Mục tiêu:* Làm nổi bật tư tưởng, phong cách biểu đạt, xung đột và thông điệp nhân sinh.
        - *Cấu trúc:*
          + initialAnalysis: Nhận xét chung về nội dung, chủ đề, phong cách.
          + comparativeText: { title, author }
          + comparison: { similarities, differences, effects }

     b. **Văn xuôi / Truyện ngắn / Tiểu thuyết (Prose / Story):**
        - *Yêu cầu:* Tác phẩm so sánh là một truyện hoặc tiểu thuyết khác, có cùng chủ đề hoặc kiểu nhân vật, của tác giả khác.
        - *Mục tiêu:* So sánh cách kể chuyện, xây dựng nhân vật, điểm nhìn trần thuật và thông điệp nhân văn.
        - *Cấu trúc:*
          + initialAnalysis: Tóm tắt chủ đề, thông điệp, phong cách trần thuật.
          + comparativeText: { title, author }
          + comparison: { similarities, differences, effects }

     c. **Thơ (Poetry):**
        - *Yêu cầu:* Tác phẩm so sánh là một bài thơ khác (cùng hoặc khác thời kỳ), có chủ đề hoặc cảm hứng tương đồng.
        - *Mục tiêu:* So sánh giọng điệu, hình ảnh, thủ pháp nghệ thuật và cảm xúc trữ tình.
        - *Cấu trúc:*
          + initialAnalysis: Nhận xét về chủ đề, cảm xúc chủ đạo, phong cách thơ.
          + comparativeText: { title, author }
          + comparison: { similarities, differences, effects }

───────────────────────────────
### V. QUY TẮC HÌNH THỨC (Stylistic & Technical Rules)
───────────────────────────────
1. Toàn bộ nội dung **phải là văn bản thuần túy**, không sử dụng:
   - Ký tự in đậm (**), nghiêng (*), Markdown hoặc các ký hiệu đặc biệt.
2. Không in đậm hay đánh dấu:
   - Tên nhân vật, địa danh, tác phẩm, trích dẫn hoặc từ khóa nhấn mạnh.
3. Mỗi đoạn phân tích được viết **liền mạch, rõ ràng, chính xác**, thể hiện **phong cách học thuật, súc tích và logic của một học giả chuyên nghiệp**.
";
        '
### YÊU CẦU ĐẦU RA JSON ###
- title, characteristics, keywords, generalAnalysis ({thematicAnalysis, linguisticInsights}), poetryAnalysis (nếu là thơ), storyAnalysis (nếu là truyện), dramaAnalysis (nếu là kịch), concludingThoughts, intertextuality.';

        $schema = [
            "type" => "OBJECT",
            "properties" => [
                "title" => ["type" => "STRING"],
                "characteristics" => [
                    "type" => "OBJECT",
                    "properties" => ["Thể loại" => ["type" => "STRING"], "Phong cách" => ["type" => "STRING"], "Giọng điệu" => ["type" => "STRING"],],
                    "required" => ["Thể loại", "Phong cách", "Giọng điệu"]
                ],
                "keywords" => ["type" => "ARRAY", "items" => ["type" => "STRING"]],
                "generalAnalysis" => [
                    "type" => "OBJECT",
                    "properties" => [
                        "thematicAnalysis" => [
                            "type" => "ARRAY",
                            "items" => [
                                "type" => "OBJECT",
                                "properties" => ["heading" => ["type" => "STRING"], "content" => ["type" => "STRING"],],
                                "required" => ["heading", "content"]
                            ]
                        ],
                        "linguisticInsights" => [
                            "type" => "OBJECT",
                            "properties" => [
                                "motifs" => ["type" => "ARRAY", "items" => ["type" => "STRING"]],
                                "symbolsAndMetaphors" => ["type" => "ARRAY", "items" => ["type" => "STRING"]],
                                "temporalElements" => ["type" => "STRING"],
                                "natureAndScenery" => ["type" => "STRING"]
                            ],
                            "required" => ["motifs", "symbolsAndMetaphors", "temporalElements", "natureAndScenery"]
                        ]
                    ],
                    "required" => ["thematicAnalysis", "linguisticInsights"]
                ],
                "poetryAnalysis" => [
                    "type" => "OBJECT",
                    "properties" => [
                        "rhyming" => ["type" => "STRING"],
                        "emotionalFlow" => ["type" => "STRING"],
                        "structure" => ["type" => "STRING"],
                        "imagery" => ["type" => "STRING"],
                        "rhythm" => ["type" => "STRING"],
                        "spaceTimeOrganization" => ["type" => "STRING"],
                        "rhetoricalDevices" => ["type" => "STRING"]
                    ]
                ],
                "storyAnalysis" => [
                    "type" => "OBJECT",
                    "properties" => [
                        "character" => ["type" => "STRING"],
                        "space" => ["type" => "STRING"],
                        "time" => ["type" => "STRING"],
                        "dialogue" => ["type" => "STRING"],
                        "narrator" => ["type" => "STRING"],
                        "situation" => ["type" => "STRING"],
                        "prominentDetails" => ["type" => "STRING"]
                    ]
                ],
                "dramaAnalysis" => [
                    "type" => "OBJECT",
                    "properties" => [
                        "characters" => ["type" => "STRING"],
                        "conflict" => ["type" => "STRING"],
                        "action" => ["type" => "STRING"],
                        "structure" => ["type" => "STRING"],
                        "dialogue" => ["type" => "STRING"]
                    ]
                ],
                "concludingThoughts" => [
                    "type" => "OBJECT",
                    "properties" => [
                        "lesson" => ["type" => "STRING"],
                        "message" => ["type" => "STRING"]
                    ],
                    "required" => ["lesson", "message"]
                ],
                "intertextuality" => [
                    "type" => "OBJECT",
                    "properties" => [
                        "initialAnalysis" => ["type" => "STRING"],
                        "comparativeText" => [
                            "type" => "OBJECT",
                            "properties" => [
                                "title" => ["type" => "STRING"],
                                "author" => ["type" => "STRING"]
                            ],
                            "required" => ["title", "author"]
                        ],
                        "comparison" => [
                            "type" => "OBJECT",
                            "properties" => [
                                "similarities" => ["type" => "STRING"],
                                "differences" => ["type" => "STRING"],
                                "effects" => ["type" => "STRING"]
                            ],
                            "required" => ["similarities", "differences", "effects"]
                        ]
                    ],
                    "required" => ["initialAnalysis", "comparativeText", "comparison"]
                ]
            ],
            "required" => ["title", "characteristics", "keywords", "generalAnalysis", "concludingThoughts", "intertextuality"]
        ];

        // Format the user text as a chat 'contents' array
        $contents = [
            ['role' => 'user', 'parts' => [['text' => $data]]]
        ];

        $response = callGeminiApi($systemPrompt, $contents, $schema);
        break;

    case 'slideshow':
        $systemPrompt = 'Bạn là một chuyên gia biên tập nội dung. Dựa trên phân tích văn học chi tiết sau (dạng JSON), hãy tóm tắt các điểm chính từ các phần phân tích chuyên sâu (poetryAnalysis, storyAnalysis, dramaAnalysis) và các phần khác thành nội dung ngắn gọn cho các slide thuyết trình. Mỗi slide nên có tiêu đề (heading) và nội dung (content) súc tích, dễ đọc. Đồng thời, cung cấp thêm 3-4 bài báo nghiên cứu học thuật liên quan (về tác phẩm, tác giả, hoặc thể loại), định dạng theo chuẩn MLA Việt hóa (Tác giả. <i>Tên sách</i>. NXB, Năm. hoặc Tác giả. "Tên bài báo." <i>Tên tạp chí</i>, số, năm, trang.). Trả về một đối tượng JSON.';
        $schema = [
            "type" => "OBJECT",
            "properties" => [
                "slides" => [
                    "type" => "ARRAY",
                    "items" => [
                        "type" => "OBJECT",
                        "properties" => [
                            "heading" => ["type" => "STRING"],
                            "content" => ["type" => "STRING"]
                        ],
                        "required" => ["heading", "content"]
                    ]
                ],
                "references" => ["type" => "ARRAY", "items" => ["type" => "STRING"]],
            ],
            "required" => ["slides", "references"]
        ];

        // Format the analysis context as a chat 'contents' array
        $contents = [
            ['role' => 'user', 'parts' => [['text' => $data]]]
        ];

        $response = callGeminiApi($systemPrompt, $contents, $schema);
        break;

    case 'quiz':
        $systemPrompt = 'Dựa trên phân tích văn học sau đây, hãy tạo một bài kiểm tra trắc nghiệm gồm 3 câu hỏi. Mỗi câu hỏi phải có 3 lựa chọn và một câu trả lời đúng. Trả về một đối tượng JSON.';
        $schema = [
            "type" => "OBJECT",
            "properties" => [
                "quiz" => [
                    "type" => "ARRAY",
                    "items" => [
                        "type" => "OBJECT",
                        "properties" => ["q" => ["type" => "STRING"], "o" => ["type" => "ARRAY", "items" => ["type" => "STRING"]], "a" => ["type" => "INTEGER"],],
                        "required" => ["q", "o", "a"]
                    ]
                ]
            ],
            "required" => ["quiz"]
        ];

        // Format the analysis context as a chat 'contents' array
        $contents = [
            ['role' => 'user', 'parts' => [['text' => $data]]]
        ];

        $response = callGeminiApi($systemPrompt, $contents, $schema);
        break;

    case 'refine':
        // Multi-turn conversation for refining analysis
        $requestData = json_decode($data, true);
        $history = $requestData['history'] ?? [];
        $currentAnalysis = $requestData['currentAnalysis'] ?? null;

        if (empty($history)) {
            $response = ['error' => 'No conversation history provided'];
            break;
        }

        $systemPrompt = 'Bạn là một chuyên gia phân tích văn học. Dựa trên cuộc hội thoại trước đó và kết quả phân tích hiện tại, hãy trả lời câu hỏi của người dùng một cách chi tiết, chuyên sâu. Nếu người dùng yêu cầu điều chỉnh hoặc bổ sung phân tích, hãy cung cấp thông tin cụ thể và có căn cứ. Trả về câu trả lời dạng văn bản thuần (không phải JSON), sử dụng markdown để format nếu cần.';

        // Use the full conversation history for context
        $response = [
            'response' => '',
            'message' => ''
        ];

        // Call API without schema for free-form text response
        $apiKey = "AIzaSyBBPzAU_iuvVsHjZw7sdFhKUakA4HiAL3I";
        $apiUrl = "https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash-preview-05-20:generateContent?key={$apiKey}";

        $payload = [
            'contents' => $history,
            'systemInstruction' => [
                'parts' => [['text' => $systemPrompt]]
            ],
            'generationConfig' => [
                'temperature' => 0.8,
                'maxOutputTokens' => 4000
            ]
        ];

        $jsonData = json_encode($payload);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $apiUrl);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonData);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Content-Length: ' . strlen($jsonData)
        ]);

        $apiResponse = curl_exec($ch);
        $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpcode == 200) {
            $result = json_decode($apiResponse, true);
            if (isset($result['candidates'][0]['content']['parts'][0]['text'])) {
                $responseText = $result['candidates'][0]['content']['parts'][0]['text'];
                $response['response'] = $responseText;
                $response['message'] = $responseText;
            } else {
                $response['error'] = 'Invalid response structure from API.';
            }
        } else {
            $response['error'] = "API call failed with status code $httpcode";
        }
        break;

    default:
        $response = ['error' => 'Invalid action provided.'];
        break;
}

// Echo the final JSON response
echo json_encode($response);
