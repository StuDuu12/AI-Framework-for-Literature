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
        .prose-custom { color: #374151; }
        .prose-custom h1 { color: #111827; font-weight: 700; }
        .prose-custom h2 { color: #111827; font-weight: 600; border-bottom: 1px solid #e5e7eb; padding-bottom: 0.5rem; margin-top: 2rem; margin-bottom: 1rem; }
        .prose-custom h3 { color: #1f2937; font-weight: 600; margin-top: 1.5rem; margin-bottom: 0.75rem; }
        .prose-custom h4 { color: #374151; font-weight: 600; margin-top: 1rem; margin-bottom: 0.5rem; }
        .prose-custom p { line-height: 1.6; }
        .prose-custom ul { list-style-position: inside; padding-left: 0; }
        .prose-custom li { margin-bottom: 0.5rem; list-style-type: disc; margin-left: 1rem;}
        .prose-custom strong { color: #1f2937; }
        .prose-custom i { font-style: italic; color: #4338ca; }
        .prose-custom blockquote {
            margin-top: 0.5rem;
            padding-left: 1rem;
            border-left: 4px solid #e5e7eb;
            color: #4b5563;
        }
        .prose-custom pre { background-color: #f3f4f6; padding: 1rem; border-radius: 0.5rem; white-space: pre-wrap; word-wrap: break-word; }
        .prose-custom code { font-family: monospace; }
        
        /* Custom scrollbar */
        ::-webkit-scrollbar { width: 8px; }
        ::-webkit-scrollbar-track { background: #f1f5f9; }
        ::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 10px; }
        ::-webkit-scrollbar-thumb:hover { background: #94a3b8; }

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
    } else if(isset($_SESSION['user']['role']) && $_SESSION['user']['role'] === 'student') {
        require_once './views/templates/sidebar_student.php';
    }

    // Kiểm tra phase hiện tại và quyền sử dụng AI
    $current_phase_number = null;
    $m_phase_id = null;
    $ai_enabled = false; // Mặc định KHÔNG cho phép AI
    $comment_enabled = false;
    $edit_enabled = false;
    $topic_id = isset($_GET['topic_id']) ? intval($_GET['topic_id']) : null;
    $has_active_phase = false; // Flag để kiểm tra có phase active không
    
    if ($topic_id) {
        try {
            // Lấy phase đang active của topic cùng với thông tin main_phase
            $stmt = $pdo->prepare("
                SELECT 
                    p.phase_number, 
                    p.m_phase_id,
                    mp.AI as ai_enabled,
                    mp.Comment as comment_enabled,
                    mp.Edit as edit_enabled,
                    mp.m_phase_name,
                    mp.phase_code
                FROM phase p
                LEFT JOIN main_phase mp ON p.m_phase_id = mp.m_phase_id
                WHERE p.topic_id = ? AND p.flag_active = 1 
                ORDER BY p.phase_number ASC 
                LIMIT 1
            ");
            $stmt->execute([$topic_id]);
            $active_phase = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($active_phase) {
                $has_active_phase = true;
                $current_phase_number = (int)$active_phase['phase_number'];
                $m_phase_id = (int)$active_phase['m_phase_id'];
                // MySQL tinyint(1): 0 = false, 1 = true
                $ai_enabled = ($active_phase['ai_enabled'] == 1);
                $comment_enabled = ($active_phase['comment_enabled'] == 1);
                $edit_enabled = ($active_phase['edit_enabled'] == 1);
                
                // Debug log
                error_log("✅ Topic {$topic_id} - Trạng thái: {$current_phase_number}, m_phase_id: {$m_phase_id}, AI: " . ($ai_enabled ? 'YES' : 'NO'));
            } else {
                error_log("⚠️ Chưa có trạng thái active cho topic_id: {$topic_id}");
            }
        } catch (PDOException $e) {
            error_log("❌ Lỗi kiểm tra trạng thái: " . $e->getMessage());
        }
    } else {
        error_log("⚠️ Chưa có topic_id trong URL");
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
                <?php if ($ai_enabled && $_SESSION['user']['role'] === 'student'): ?>
                <div class="lg:col-span-4 h-full">
                    <div class="bg-white p-6 rounded-xl shadow-md">
                        <h2 class="text-lg font-semibold mb-4 text-slate-800">Nhập văn bản cần phân tích</h2>
                        
                        <!-- File Upload -->
                        <div class="mb-4">
                            <label for="file-upload" class="block text-sm font-medium text-slate-700 mb-2">Tải lên tệp tin:</label>
                            <label for="file-upload" class="relative cursor-pointer bg-white rounded-md font-medium text-indigo-600 hover:text-indigo-500 border border-dashed border-slate-300 p-4 flex justify-center items-center flex-col min-h-[150px]">
                                <div id="upload-prompt" class="text-center">
                                    <svg class="mx-auto h-12 w-12 text-slate-400" stroke="currentColor" fill="none" viewBox="0 0 48 48" aria-hidden="true"><path d="M28 8H12a4 4 0 00-4 4v20m32-12v8m0 0v8a4 4 0 01-4 4H12a4 4 0 01-4-4v-4m32-4l-3.172-3.172a4 4 0 00-5.656 0L28 28M8 32l9.172-9.172a4 4 0 015.656 0L28 28m0 0l4 4m4-24h8m-4-4v8" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" /></svg>
                                    <span class="mt-2 block text-sm">Nhấn để tải lên</span>
                                    <p class="text-xs text-slate-500">DOCX, PDF, TXT</p>
                                </div>
                                <div id="file-info" class="hidden flex items-center justify-between w-full">
                                    <span id="file-name" class="text-sm font-semibold text-indigo-700 truncate pr-2"></span>
                                    <button id="delete-file-button" type="button" class="p-1 rounded-full text-slate-500 hover:text-red-600 hover:bg-red-100 focus:outline-none focus:ring-2 focus:ring-red-500">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
                                    </button>
                                </div>
                            </label>
                            <input id="file-upload" name="file-upload" type="file" class="sr-only">
                        </div>
                        
                        <div class="relative mb-4">
                            <div class="absolute inset-0 flex items-center"><div class="w-full border-t border-slate-300"></div></div>
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
                                <svg id="analyze-icon" xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="mr-2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><polyline points="10 9 9 9 8 9"></polyline></svg>
                                <svg id="loading-spinner" class="animate-spin -ml-1 mr-3 h-5 w-5 text-white hidden" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                                <span id="button-text">Phân tích Văn bản</span>
                            </button>
                        </div>
                    </div>
                </div>
                <?php elseif (!$has_active_phase && $_SESSION['user']['role'] === 'student'): ?>
                <!-- Thông báo chưa có topic hoặc chưa được kích hoạt -->
                <div class="lg:col-span-4 h-full">
                    <div class="bg-white p-6 rounded-xl shadow-md">
                        <div class="text-center py-8">
                            <svg xmlns="http://www.w3.org/2000/svg" width="60" height="60" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" class="mx-auto text-slate-400 mb-4">
                                <circle cx="12" cy="12" r="10"></circle>
                                <line x1="12" y1="8" x2="12" y2="12"></line>
                                <line x1="12" y1="16" x2="12.01" y2="16"></line>
                            </svg>
                            <h3 class="text-lg font-semibold text-slate-700 mb-2">
                                <?php if (!$topic_id): ?>
                                    Chưa chọn chủ đề
                                <?php else: ?>
                                    Chưa được kích hoạt
                                <?php endif; ?>
                            </h3>
                            <p class="text-sm text-slate-500 mb-4">
                                <?php if (!$topic_id): ?>
                                    Vui lòng chọn chủ đề từ danh sách lớp học để bắt đầu sử dụng công cụ phân tích văn học.
                                <?php else: ?>
                                    Chủ đề này chưa được kích hoạt. Vui lòng đợi giáo viên bắt đầu.
                                <?php endif; ?>
                            </p>
                            <a href="?route=class" class="inline-block mt-4 px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition-colors">
                                <i class="bi bi-arrow-left me-2"></i>Quay lại lớp học
                            </a>
                        </div>
                    </div>
                </div>
                <?php elseif (!$ai_enabled && $has_active_phase && $_SESSION['user']['role'] === 'student'): ?>
                <!-- Thông báo không được phép sử dụng AI lúc này -->
                <div class="lg:col-span-4 h-full">
                    <div class="bg-white p-6 rounded-xl shadow-md">
                        <div class="text-center py-8">
                            <svg xmlns="http://www.w3.org/2000/svg" width="60" height="60" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" class="mx-auto text-amber-500 mb-4">
                                <circle cx="12" cy="12" r="10"></circle>
                                <line x1="12" y1="8" x2="12" y2="12"></line>
                                <line x1="12" y1="16" x2="12.01" y2="16"></line>
                            </svg>
                            <h3 class="text-lg font-semibold text-slate-700 mb-2">Không được phép làm lúc này</h3>
                            <p class="text-sm text-slate-500 mb-4">
                                Bạn không được phép sử dụng công cụ AI để phân tích văn bản trong thời điểm này.
                            </p>
                            <?php if ($comment_enabled): ?>
                            <div class="mt-4 p-3 bg-blue-50 rounded-lg">
                                <p class="text-sm text-blue-700"><i class="bi bi-info-circle me-2"></i>Bạn có thể xem và bình luận các phân tích</p>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Right Panel: Output -->
                <?php 
                // Xác định width của panel output dựa trên điều kiện
                $output_width = 'lg:col-span-12'; // Mặc định full width
                if ($ai_enabled && $has_active_phase && $_SESSION['user']['role'] === 'student') {
                    $output_width = 'lg:col-span-8'; // Có AI panel bên trái
                } elseif (!$has_active_phase || (!$ai_enabled && $has_active_phase)) {
                    $output_width = 'lg:col-span-8'; // Có thông báo panel bên trái
                }
                ?>
                <div class="<?php echo $output_width; ?>">
                     <div id="results-container" class="bg-white rounded-xl shadow-md min-h-[calc(100vh-12rem)]">
                        <!-- Placeholder -->
                        <div id="placeholder" class="text-center text-slate-500 h-full flex flex-col justify-center items-center p-8">
                            <svg xmlns="http://www.w3.org/2000/svg" width="60" height="60" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" class="text-slate-400 mb-4"><path d="M21.5 12c0-5.25-4.25-9.5-9.5-9.5S2.5 6.75 2.5 12s4.25 9.5 9.5 9.5c.93 0 1.84-.14 2.7-.4"></path><path d="m18 16 3.5 3.5"></path><path d="m15.5 6-4 4"></path><path d="m13.5 10-2-2"></path><path d="m11.5 8 2 2"></path></svg>
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

    <!-- Load script tương ứng - XỬ LÝ HOÀN TOÀN BẰNG PHP -->
    <?php 
    // Logic load script dựa trên trạng thái và quyền hạn
    if ($has_active_phase && $ai_enabled && $_SESSION['user']['role'] === 'student') {
        // Student được kích hoạt VÀ cho phép sử dụng AI: Load literature_script.php
        include 'views/literary_analysis/literature_script.php';
    } elseif ($has_active_phase || $_SESSION['user']['role'] === 'teacher') {
        // Đã kích hoạt NHƯNG không cho phép AI, hoặc là teacher: Load literature_comment.php
        include 'views/literary_analysis/literature_comment.php';
    }
    // Nếu chưa kích hoạt và là student: không load script nào
    ?>

</body>
</html>
