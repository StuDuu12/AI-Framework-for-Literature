<?php
// Start session for authentication
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Set header to return JSON
header('Content-Type: application/json');

// Check authentication
if (!isset($_SESSION['user']) || empty($_SESSION['user'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized - Please login']);
    exit;
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
$input = json_decode(file_get_contents('php://input'), true);
$action = $input['action'] ?? '';
$data = $input['data'] ?? '';

$response = null;

// --- Main Action Switch ---
switch ($action) {
    case 'analyze':
        $systemPrompt = 'Bạn là một chuyên gia phân tích văn học. Phân tích văn bản được cung cấp và trả về một đối tượng JSON. Nội dung phải bằng tiếng Việt và chi tiết, sâu sắc.

### QUY TẮC PHÂN LOẠI THỂ LOẠI ###
Dựa vào các quy tắc sau để xác định \'Thể loại\' trong \'characteristics\'. Hãy chú ý đến cấu trúc tổng thể và đặc điểm nổi trội nhất. Khi trả về giá trị cho \'Thể loại\', hãy sử dụng định dạng: "**[Tên thể loại chính] – [Tên thể loại phụ/đặc điểm]**".
1. VĂN BẢN (VĂN XUÔI):
- Tự sự (Truyện): Có nhân vật, sự kiện, cốt truyện.
- Miêu tả, Biểu cảm, Nghị luận, Thuyết minh, Hành chính - công vụ.
- Kịch: Nhận diện qua cấu trúc đối thoại, chỉ dẫn sân khấu, và xung đột rõ ràng.
2. THƠ:
- Lục bát, Thơ tự do, Thơ 4/5/6/7/8 chữ, Thất ngôn bát cú/Tứ tuyệt, Thơ châm biếm, Song Thất Lục Bát.

### QUY TẮC PHÂN TÍCH CHUNG (General Analysis) ###
Gộp các phân tích chủ đề (thematicAnalysis) và ngôn ngữ/biểu tượng (linguisticInsights) vào một mục lớn "generalAnalysis". Trong đó:
- \'thematicAnalysis\': Giữ nguyên cấu trúc mảng các object {heading, content}.
- \'linguisticInsights\': Chia thành các mục con: motifs, symbolsAndMetaphors, temporalElements, natureAndScenery. Mỗi mục con trình bày dưới dạng danh sách gạch đầu dòng (sử dụng dấu • ).

### QUY TẮC PHÂN TÍCH CHUYÊN SÂU THEO THỂ LOẠI ###
BẮT BUỘC phải tạo object phân tích chuyên sâu tương ứng với thể loại đã xác định. Mỗi phân tích phải rõ ràng, có cấu trúc và kèm dẫn chứng cụ thể từ văn bản.

**Nếu là Kịch (dramaAnalysis):** 
Phân tích 5 yếu tố: **characters**, **conflict**, **action**, **structure**, **dialogue**

**Nếu là Thơ (poetryAnalysis):** 
Phân tích 7 yếu tố: **rhyming**, **emotionalFlow**, **structure**, **imagery**, **rhythm**, **spaceTimeOrganization**, **rhetoricalDevices**

**Nếu là Truyện (storyAnalysis):** 
Phân tích 7 yếu tố: **character**, **space**, **time**, **dialogue**, **narrator**, **situation**, **prominentDetails**

### PHÂN TÍCH CUỐI CÙNG ###
1. \'concludingThoughts\': Rút ra \'lesson\' (Bài học) và \'message\' (Thông điệp).
2. \'intertextuality\' (Phân tích Liên văn bản):
   - **Quy tắc chung:** Phân tích phải dựa trên thể loại của tác phẩm chính.
   
   - **1. Đối với Kịch (Drama):**
     - **Yêu cầu:** Tác phẩm so sánh (\'comparativeText\') BẮT BUỘC phải là một vở kịch của một tác giả khác.
     - **Mục tiêu:** Làm nổi bật ý tưởng, phong cách biểu đạt, xung đột kịch và thông điệp nhân văn.
     - **Cấu trúc:**
       + initialAnalysis: Nhận xét chung về nội dung, chủ đề, phong cách của vở kịch chính.
       + comparativeText: { title, author }
       + comparison: { similarities (tương đồng), differences (khác biệt), effects (hiệu quả) }

   - **2. Đối với Văn xuôi / Truyện ngắn / Tiểu thuyết (Prose / Story):**
     - **Yêu cầu:** Tác phẩm so sánh (\'comparativeText\') BẮT BUỘC phải là một truyện ngắn hoặc tiểu thuyết khác, có cùng chủ đề hoặc kiểu nhân vật, của một tác giả khác.
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
