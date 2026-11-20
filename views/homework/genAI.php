<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php
    require_once __DIR__ . '/../templates/assets.php';
    require_once __DIR__ . '/../../config/db.php';
    require_once __DIR__ . '/../../config/helper_homework.php';
    require_once __DIR__ . '/../../config/helper_task.php';

    // Get parameters from URL
    $task_id = isset($_GET['task_id']) ? intval($_GET['task_id']) : 0;
    $topic_id = isset($_GET['topic_id']) ? intval($_GET['topic_id']) : 0;
    $phase_id = isset($_GET['phase_id']) ? intval($_GET['phase_id']) : 0;
    $deliver_id = isset($_GET['deliver_id']) ? intval($_GET['deliver_id']) : 0;

    // Initialize variables
    $taskData = null;
    $mainPhaseCode = '';
    $fileContent = '';
    $isPhaseStart = false;
    $errorMessage = '';

    // Get user_id early for access checks
    $user_id = null;
    if (isset($_SESSION['user']['user_id'])) {
        $user_id = $_SESSION['user']['user_id'];
    } elseif (isset($_SESSION['user_id'])) {
        $user_id = $_SESSION['user_id'];
    }

    // Validate parameters
    if ($task_id > 0) {
        try {
            // Use helper function to get task info
            $taskData = getInfoTask($task_id);

            if ($taskData) {
                $mainPhaseCode = $taskData['phase_code'];

                // Check if this is PHASE_START
                $isPhaseStart = ($mainPhaseCode === 'PHASE_START');

                // Validate topic_id if provided
                if ($topic_id > 0 && $taskData['topic_id'] != $topic_id) {
                    $errorMessage = 'Topic ID does not match the task.';
                    $taskData = null;
                }

                // Literary text (story / poem / drama) from task file — never replace with analysis output
                if ($taskData && !empty($taskData['file_content'])) {
                    $loaded = readTaskFileContent($taskData['file_content']);
                    if ($loaded !== false) {
                        $fileContent = $loaded;
                    }
                }

                // Check student access permission
                if ($taskData && isset($_SESSION['user']['role']) && $_SESSION['user']['role'] === 'student' && $user_id) {
                    // Check if deliver_id provided, validate specific deliver access
                    if ($deliver_id > 0) {
                        if (!validateUserDeliverAccess($deliver_id, $user_id)) {
                            $errorMessage = 'You do not have permission to access this task.';
                            $taskData = null;
                        }
                    } else {
                        // No deliver_id, get one for this user
                        $deliver_id = getDeliverIdForUser($task_id, $user_id);
                        if ($deliver_id === null) {
                            $errorMessage = 'This task has not been assigned to you.';
                            $taskData = null;
                        }
                    }
                }
            } else {
                $errorMessage = 'Not found task.';
            }
        } catch (Exception $e) {
            error_log("Error fetching task data: " . $e->getMessage());
            $errorMessage = 'An error occurred when loading data.';
            $taskData = null;
        }
    } else {
        $errorMessage = 'Invalid task information.';
    }

    // If task not found or error, redirect to previous page
    if (!$taskData) {
        if ($topic_id > 0) {
            header("Location: /404");
        } else {
            header("Location: /404");
        }
        exit;
    }

    // Check if user has created analysis (based on deliver_id)
    $json_data = null;
    $id_response = null;
    $deliver_status = 0;
    $confirmed_history_id = null;
    $taskContentLabel = $taskData['task_title'] ?? 'Assigned text';

    if ($user_id && $deliver_id > 0) {
        $deliverDetails = getDeliverDetails($deliver_id);
        if ($deliverDetails) {
            $deliver_status = (int)($deliverDetails['status'] ?? 0);
        }

        require_once __DIR__ . '/../../config/helper_analysis.php';
        $confirmed_history_id = getConfirmedHistoryId($user_id, $task_id, $deliver_id);

        $responseFromDeliver = getResponseFromDeliver($deliver_id, $user_id);
        if ($responseFromDeliver && !empty($responseFromDeliver['id_response'])) {
            $id_response = (int)$responseFromDeliver['id_response'];
            if (!empty($responseFromDeliver['json_ai'])) {
                $json_data = is_array($responseFromDeliver['json_ai'])
                    ? $responseFromDeliver['json_ai']
                    : json_decode($responseFromDeliver['json_ai'], true);
            }
        }
    }

    ?>
    <title><?= htmlspecialchars($taskData['task_title']) ?> - <span>Literary Analysis</span></title>
    <link rel="stylesheet" href="<?= URL_ROOT ?>/assets/css/literary_analysis.css">
    <link rel="stylesheet" href="<?= URL_ROOT ?>/assets/css/genai-layout.css">
    <link rel="stylesheet" href="<?= URL_ROOT ?>/assets/css/genai-content.css">
    <link rel="stylesheet" href="<?= URL_ROOT ?>/assets/css/outline-validation.css">
    <link rel="stylesheet" href="<?= URL_ROOT ?>/assets/css/research-figures.css">
</head>

<body class="literary-bg">
    <?php
    require_once __DIR__ . '/../templates/header.php';
    require_once __DIR__ . '/../templates/sidebar.php';
    ?>

    <main id="main" class="main">
        <div class="pagetitle">
            <h1><?= htmlspecialchars($taskData['task_title']) ?></h1>
            <nav>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a
                            href="<?= URL_ROOT ?>/class"><?= htmlspecialchars($_SESSION['current_class_name'] ?? 'Class') ?></a></li>
                    <li class="breadcrumb-item"><a
                            href="<?= URL_ROOT ?>/class/phases?topic_id=<?= $topic_id ?>"><?= htmlspecialchars($taskData['topic_name'] ?? 'Topic') ?></a></li>
                    <li class="breadcrumb-item"><a
                            href="<?= URL_ROOT ?>/class/task?topic_id=<?= $topic_id ?>&phase_id=<?= $phase_id ?>">Tasks - Step <?= (int)($taskData['phase_number'] ?? 1) ?></a></li>
                    <li class="breadcrumb-item active"><?= htmlspecialchars($taskData['phase_name']) ?></li>
                </ol>
            </nav>
        </div><!-- End Page Title -->

        <!-- Task Info -->
        <div class="alert alert-info mb-4">
            <h5><i class="bi bi-info-circle"></i> <?= htmlspecialchars($taskData['m_phase_name']) ?></h5>
            <p class="mb-0"><?= htmlspecialchars($taskData['task_description']) ?></p>
            <?php if ($isPhaseStart): ?>
                <div class="mt-2 text-indigo-700 bg-indigo-50 border border-indigo-200 rounded-lg p-2 text-sm flex items-center gap-2">
                    <i class="bi bi-chat-left-quote-fill text-indigo-600"></i>
                    <span><strong>Giai đoạn 1:</strong> Thực hiện tạo bài phân tích AI và tự phản biện (bằng tính năng bình luận).</span>
                </div>
            <?php endif; ?>
        </div>

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
                                placeholder="Paste text to analyze..." <?php if ($fileContent): ?>readonly<?php endif; ?>></textarea>
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
                        <div class="flex gap-2 flex-wrap">
                            <!-- Analyze Button (visible before analysis) -->
                            <button id="analyze-button" type="button"
                                class="genai-btn-primary flex justify-center items-center disabled:opacity-50 disabled:cursor-not-allowed">
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
                            <button id="reset-button" type="button" disabled
                                class="hidden genai-btn-outline genai-btn-danger flex justify-center items-center disabled:opacity-50 disabled:cursor-not-allowed">
                                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24"
                                    fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                    stroke-linejoin="round" class="mr-2">
                                    <polyline points="1 4 1 10 7 10"></polyline>
                                    <path d="M3.51 15a9 9 0 1 0 2.13-9.36L1 10"></path>
                                </svg>
                                <span>Retry</span>
                            </button>

                            <!-- Chat Button (hidden initially, shown after analysis) -->
                            <button id="chat-button" type="button" disabled
                                class="hidden genai-btn-outline flex justify-center items-center disabled:opacity-50 disabled:cursor-not-allowed">
                                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24"
                                    fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                    stroke-linejoin="round" class="mr-2">
                                    <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path>
                                </svg>
                                <span id="chat-button-text">Ask AI</span>
                            </button>

                            <!-- Comment & Self-Critique Button -->
                            <a id="comment-page-btn" href="/class/homework/comment?task_id=<?= $task_id ?>&topic_id=<?= $topic_id ?>&phase_id=<?= $phase_id ?>&deliver_id=<?= $deliver_id ?>"
                                class="genai-btn-outline genai-btn-success flex justify-center items-center <?= ($json_data) ? '' : 'hidden' ?>"
                                style="display: inline-flex; align-items: center; gap: 0.5rem; padding: 0.65rem 1.1rem; font-size: 0.875rem; font-weight: 600; color: #047857; background-color: #ecfdf5; border: 1px solid #a7f3d0; border-radius: 8px; text-decoration: none; transition: all 0.15s ease;">
                                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor" viewBox="0 0 16 16" style="margin-right: 2px;">
                                    <path d="M2.678 11.894a1 1 0 0 1 .287.801 10.97 10.97 0 0 1-.398 2c1.395-.323 2.247-.697 2.634-.893a1 1 0 0 1 .71-.074A8.06 8.06 0 0 0 8 14c3.996 0 7-2.807 7-6 0-3.192-3.004-6-7-6S1 4.808 1 8c0 1.468.617 2.83 1.678 3.894zm-.493 3.905a21.682 21.682 0 0 1-.713.129c-.2.032-.352-.176-.273-.362a9.68 9.68 0 0 0 .244-.637l.003-.01c.248-.72.45-1.548.524-2.319C.743 11.37 0 9.76 0 8c0-3.866 3.582-7 8-7s8 3.134 8 7-3.582 7-8 7a9.06 9.06 0 0 1-2.347-.306c-.52.263-1.639.742-3.468 1.105z"/>
                                </svg>
                                <span><?= $isPhaseStart ? 'Tự phản biện & Bình luận' : 'Go to Comments' ?></span>
                            </a>
                        </div>

                        <div class="flex flex-wrap items-center gap-3 mt-3">
                            <div id="autosave-status" class="autosave-status"></div>
                            <button type="button" id="export-analysis-btn" class="hidden text-sm font-semibold text-indigo-600 hover:text-indigo-800 border border-indigo-200 rounded-lg px-3 py-1.5 bg-white" data-lang-key="export">
                                <i class="bi bi-download"></i> Xuất bài phân tích
                            </button>
                        </div>

                        <!-- Review Previous Work Button (shown when localStorage history exists) -->
                        <button id="review-work-btn"
                            class="hidden mt-3 w-full flex justify-center items-center py-2.5 px-4 border border-amber-500 rounded-lg shadow-sm text-sm font-semibold text-amber-700 bg-amber-50 hover:bg-amber-100 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-amber-400 transition-colors gap-2">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <circle cx="12" cy="12" r="10"></circle>
                                <polyline points="12 6 12 12 16 14"></polyline>
                            </svg>
                            Review Previous Work
                        </button>

                        <!-- Preview Button (shown only after user edits a section) -->
                        <button id="preview-changes-button" type="button"
                            class="hidden mt-4 w-full flex justify-center items-center py-3 px-4 border border-purple-600 rounded-lg shadow-sm text-sm font-semibold text-purple-600 bg-white hover:bg-purple-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-purple-500 transition-colors">
                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24"
                                fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                stroke-linejoin="round" class="mr-2">
                                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                <circle cx="12" cy="12" r="3"></circle>
                            </svg>
                            <span>Preview Edits</span>
                        </button>

                        <!-- Active revision interface -->
                        <section id="fig15-revision-interface" class="research-figure research-figure--revision hidden mt-4" aria-label="Active revision interface">
                            <div class="research-figure__body">
                                <div class="flex justify-end mb-2">
                                    <span id="fig15-status-badge" class="research-figure__badge research-figure__badge--ready">REFLECTION COMPLETE</span>
                                </div>
                                <div class="research-figure__workflow" id="fig15-workflow">
                                    <div class="research-figure__workflow-step research-figure__workflow-step--done" data-workflow-step="1">
                                        <span class="research-figure__workflow-num">1</span>
                                        <span class="research-figure__workflow-label">AI outline validated</span>
                                    </div>
                                    <div class="research-figure__workflow-step" data-workflow-step="2">
                                        <span class="research-figure__workflow-num">2</span>
                                        <span class="research-figure__workflow-label">Self-reflection submitted</span>
                                    </div>
                                    <div class="research-figure__workflow-step" data-workflow-step="3">
                                        <span class="research-figure__workflow-num">3</span>
                                        <span class="research-figure__workflow-label">Active draft revision</span>
                                    </div>
                                </div>
                                <div id="fig15-section-tracker" class="research-figure__tracker hidden"></div>
                                <div id="change-statistics" class="hidden change-stats-panel">
                                    <h4>Revision metrics</h4>
                                    <div id="stats-content" class="space-y-2 text-sm"></div>
                                </div>
                            </div>
                        </section>

                        <!-- Analysis History Panel -->
                        <div id="history-panel" class="hidden mt-4">
                            <button id="toggle-history-btn" onclick="toggleHistoryPanel()"
                                class="w-full flex items-center justify-between p-3 bg-slate-50 hover:bg-slate-100 rounded-lg border border-slate-200 text-sm font-medium text-slate-700 transition-colors">
                                <span class="flex items-center gap-2">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <circle cx="12" cy="12" r="10"></circle>
                                        <polyline points="12 6 12 12 16 14"></polyline>
                                    </svg>
                                    Analysis History
                                </span>
                                <svg id="history-chevron" xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="transition-transform duration-200">
                                    <polyline points="6 9 12 15 18 9"></polyline>
                                </svg>
                            </button>
                            <div id="history-list" class="hidden mt-2 space-y-1 max-h-52 overflow-y-auto pr-1">
                                <!-- Populated by renderHistoryPanel() -->
                            </div>
                            <div id="submission-status-banner" class="hidden mt-2"></div>
                        </div>

                    </div>
                </div>

                <!-- Right Panel: Output -->
                <div class="lg:col-span-8">
                    <div id="results-container" class="bg-white rounded-xl shadow-md min-h-[calc(100vh-12rem)]">
                        <!-- Placeholder -->
                        <div id="placeholder"
                            class="genai-placeholder text-center text-slate-500 h-full flex flex-col justify-center items-center p-8">
                            <div class="genai-placeholder__icon">
                            <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24"
                                fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"
                                stroke-linejoin="round">
                                <path
                                    d="M21.5 12c0-5.25-4.25-9.5-9.5-9.5S2.5 6.75 2.5 12s4.25 9.5 9.5 9.5c.93 0 1.84-.14 2.7-.4">
                                </path>
                                <path d="m18 16 3.5 3.5"></path>
                                <path d="m15.5 6-4 4"></path>
                                <path d="m13.5 10-2-2"></path>
                                <path d="m11.5 8 2 2"></path>
                            </svg>
                            </div>
                            <h3 class="text-lg font-semibold text-slate-700">The results of the analysis will be displayed here</h3>
                            <p class="mt-1 text-sm">Upload or paste text to get started.</p>

                            <!-- Previous work hint (injected by JS when history exists) -->
                            <div id="previous-work-hint" class="hidden mt-6 w-full max-w-sm text-left p-4 bg-amber-50 border border-amber-200 rounded-xl shadow-sm">
                                <div class="flex items-start gap-3">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-amber-500 mt-0.5 shrink-0">
                                        <circle cx="12" cy="12" r="10"></circle>
                                        <polyline points="12 6 12 12 16 14"></polyline>
                                    </svg>
                                    <div>
                                        <p class="text-sm font-semibold text-amber-800">You have previous work</p>
                                        <p id="previous-work-label" class="text-xs text-amber-600 mt-0.5"></p>
                                        <button id="hint-review-btn" onclick="openMostRecentHistory()"
                                            class="mt-2 inline-flex items-center gap-1.5 text-xs font-semibold text-amber-700 border border-amber-400 bg-white hover:bg-amber-50 rounded-lg px-3 py-1.5 transition-colors">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                                <circle cx="12" cy="12" r="3"></circle>
                                            </svg>
                                            <span data-lang-key="review_previous_work">Review Work</span>
                                        </button>
                                        <button type="button" id="hint-clear-history-btn" onclick="clearHistoryFromHint()"
                                            class="mt-2 ml-2 inline-flex items-center gap-1.5 text-xs font-semibold text-red-700 border border-red-300 bg-white hover:bg-red-50 rounded-lg px-3 py-1.5 transition-colors">
                                            <i class="bi bi-trash"></i>
                                            <span data-lang-key="clear_previous_work">Clear history</span>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Results with Tabs -->
                        <div id="results-tabs" class="genai-tabs hidden h-full flex flex-col">
                            <div class="genai-tabs__header">
                                <nav class="genai-tabs__nav" aria-label="Tabs">
                                    <button id="tab-analysis" type="button"
                                        class="genai-tabs__btn tab-button active" data-lang-key="detailed_analysis">
                                        <i class="bi bi-journal-text"></i><span>Detailed Analysis</span>
                                    </button>
                                    <button id="tab-slideshow" type="button" disabled
                                        class="hidden genai-tabs__btn tab-button" data-lang-key="slideshow">
                                        <i class="bi bi-easel"></i><span>Slideshow</span>
                                    </button>
                                    <button id="tab-quiz" type="button" disabled
                                        class="hidden genai-tabs__btn tab-button" data-lang-key="quiz">
                                        <i class="bi bi-patch-question"></i><span>Quiz</span>
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
    <script src="<?= URL_ROOT ?>/assets/js/outline-validation.js"></script>
    <script src="<?= URL_ROOT ?>/assets/js/revision-interface.js"></script>
    <script>
        // PHP data passed to JavaScript
        const IS_PHASE_START = <?= $isPhaseStart ? 'true' : 'false' ?>;
        const FILE_CONTENT = <?= json_encode($fileContent) ?>;
        const MAIN_PHASE_CODE = <?= json_encode($mainPhaseCode) ?>;
        const TASK_TITLE = <?= json_encode($taskData['task_title']) ?>;
        const TASK_CONTENT_LABEL = <?= json_encode($taskContentLabel) ?>;
        const TASK_ID = <?= $task_id ?>;
        const DELIVER_ID = <?= $deliver_id ?>;
        let deliverStatus = <?= (int)($deliver_status ?? 0) ?>;
        let confirmedHistoryId = <?= $confirmed_history_id ? (int)$confirmed_history_id : 'null' ?>;
        let historyEntries = [];
        const HAS_TASK_CONTENT = <?= !empty($fileContent) ? 'true' : 'false' ?>;

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

        // State
        let currentAnalysisData = <?php echo isset($json_data) ? json_encode($json_data) : 'null'; ?>; // Store current analysis data
        let conversationHistory = []; // Store chat history for refinement
        let isAnalyzed = false; // Track if initial analysis is done
        let currentResponseId = <?php echo isset($id_response) ? $id_response : 'null'; ?>; // Track current response ID for chat threading
        let originalContent = { sections: {} }; // Store original content from DOM for comparison
        let editTimers = {}; // Active countdown intervals per section

        // === CACHE & HISTORY (localStorage) ===
        const CACHE_KEY  = `genai_cache_task_${TASK_ID}_${DELIVER_ID}`;
        const HISTORY_KEY = `genai_history_task_${TASK_ID}_${DELIVER_ID}`;
        const WORK_CACHE_KEY = `task_cache_${TASK_ID}_${DELIVER_ID}`; // For perform.php restore button

        function saveToCache(data) {
            try {
                const cacheData = {
                    analysisData: JSON.parse(JSON.stringify(data)),
                    savedAt: new Date().toISOString(),
                    timestamp: Date.now()
                };
                localStorage.setItem(CACHE_KEY, JSON.stringify(cacheData));
                // Also save to work cache for perform.php
                localStorage.setItem(WORK_CACHE_KEY, JSON.stringify(cacheData));
                if (window.GenAITools && TASK_ID && DELIVER_ID) {
                    GenAITools.scheduleAutosave(TASK_ID, DELIVER_ID, () => data);
                }
            } catch(e) { console.warn('Cache save failed', e); }
        }

        function loadFromCache() {
            try {
                const raw = localStorage.getItem(CACHE_KEY);
                return raw ? JSON.parse(raw) : null;
            } catch(e) { return null; }
        }

        function clearCache() {
            localStorage.removeItem(CACHE_KEY);
            localStorage.removeItem(WORK_CACHE_KEY);
        }

        function clearLocalHistory() {
            localStorage.removeItem(HISTORY_KEY);
            clearCache();
        }

        let historySuppressed = false;

        function hideHistoryUI() {
            historyEntries = [];
            const panel = document.getElementById('history-panel');
            const reviewBtn = document.getElementById('review-work-btn');
            const hint = document.getElementById('previous-work-hint');
            const banner = document.getElementById('submission-status-banner');
            const historyList = document.getElementById('history-list');
            if (panel) panel.classList.add('hidden');
            if (reviewBtn) reviewBtn.classList.add('hidden');
            if (hint) hint.classList.add('hidden');
            if (banner) banner.classList.add('hidden');
            if (historyList) historyList.innerHTML = '';
        }

        async function clearAllWorkHistory() {
            clearLocalHistory();
            deliverStatus = 0;
            confirmedHistoryId = null;
            historyEntries = [];
            if (window.GenAITools && TASK_ID && DELIVER_ID) {
                const result = await GenAITools.clearAllHistory(TASK_ID, DELIVER_ID);
                if (!result || !result.success) {
                    console.warn('Server history clear failed', result);
                }
            }
        }

        async function addToHistory(data) {
            try {
                const raw = localStorage.getItem(HISTORY_KEY);
                const history = raw ? JSON.parse(raw) : [];
                const now = new Date();
                const label = now.toLocaleString('vi-VN', { hour: '2-digit', minute: '2-digit', day: '2-digit', month: '2-digit', year: 'numeric' });
                let serverId = null;
                if (window.GenAITools && TASK_ID && DELIVER_ID) {
                    serverId = await GenAITools.saveHistory(TASK_ID, DELIVER_ID, data, label);
                }
                history.unshift({
                    id: serverId || now.getTime(),
                    serverId: serverId,
                    timestamp: now.toISOString(),
                    label: label,
                    analysisData: JSON.parse(JSON.stringify(data))
                });
                if (history.length > 20) history.splice(20);
                localStorage.setItem(HISTORY_KEY, JSON.stringify(history));
                await renderHistoryPanel();
            } catch(e) { console.warn('History save failed', e); }
        }

        function loadHistory() {
            try {
                const raw = localStorage.getItem(HISTORY_KEY);
                return raw ? JSON.parse(raw) : [];
            } catch(e) { return []; }
        }

        // Initialize: Load file content if available
        window.addEventListener('DOMContentLoaded', function() {
            // Check if we need to restore cache from perform.php button
            const urlParams = new URLSearchParams(window.location.search);
            const shouldRestoreCache = urlParams.get('restore_cache') === '1';
            
            if (shouldRestoreCache && !currentAnalysisData) {
                const cached = loadFromCache();
                if (cached && cached.analysisData) {
                    currentAnalysisData = cached.analysisData;
                    isAnalyzed = true;
                    
                    // Display the cached analysis
                    displayAnalysis(currentAnalysisData);
                    setupGeneratorTabs();
                    
                    // Hide placeholder, show results
                    placeholder.classList.add('hidden');
                    resultsTabs.classList.remove('hidden');
                    const pwHint1 = document.getElementById('previous-work-hint');
                    if (pwHint1) pwHint1.classList.add('hidden');
                    switchTab(tabAnalysis);
                    
                    // Switch to chat mode
                    switchToChatMode();
                    
                    // Initialize conversation history
                    conversationHistory = [{
                        role: 'user',
                        parts: [{
                            text: `Analyze the following text:\n\n${FILE_CONTENT || textInput.value.trim()}`
                        }]
                    },
                    {
                        role: 'model',
                        parts: [{
                            text: JSON.stringify(currentAnalysisData)
                        }]
                    }];
                    
                    // Show notification
                    const cacheDate = new Date(cached.savedAt);
                    addChatMessage('system', `🔄 Previous work restored (saved at ${cacheDate.toLocaleString('vi-VN')})`);
                    
                    if (!IS_PHASE_START) {
                        chatSection.classList.remove('hidden');
                    }
                    
                    // Remove restore_cache param from URL
                    urlParams.delete('restore_cache');
                    const newUrl = window.location.pathname + (urlParams.toString() ? '?' + urlParams.toString() : '');
                    window.history.replaceState({}, '', newUrl);
                }
            }
            
            if (FILE_CONTENT) {
                textInput.value = FILE_CONTENT;

                // Show file info if content loaded from task
                if (FILE_CONTENT.trim()) {
                    fileNameDisplay.textContent = TASK_CONTENT_LABEL;
                    fileInfo.classList.remove('hidden');
                }
            }

            // Disable file upload when task already provides the literary text
            if (FILE_CONTENT) {
                fileUpload.disabled = true;
                fileUpload.parentElement.style.opacity = '0.5';
                fileUpload.parentElement.title = 'Task text is loaded. Upload is disabled.';
            }

            // Check if analysis already exists and display it
            if (currentAnalysisData && currentResponseId) {
                // Mark as analyzed
                isAnalyzed = true;

                // Display the existing analysis
                displayAnalysis(currentAnalysisData);
                setupGeneratorTabs();

                // Hide placeholder, show results
                placeholder.classList.add('hidden');
                resultsTabs.classList.remove('hidden');
                const pwHint1 = document.getElementById('previous-work-hint');
                if (pwHint1) pwHint1.classList.add('hidden');
                switchTab(tabAnalysis);

                // Switch to chat mode (disable analysis, enable chat)
                switchToChatMode();

                // Initialize conversation history with the existing analysis
                conversationHistory = [{
                        role: 'user',
                        parts: [{
                            text: `Analyze the following text:\n\n${FILE_CONTENT || textInput.value.trim()}`
                        }]
                    },
                    {
                        role: 'model',
                        parts: [{
                            text: JSON.stringify(currentAnalysisData)
                        }]
                    }
                ];

                // Add initial message to chat
                if (IS_PHASE_START) {
                    addChatMessage('system', '✅ Previous analysis found. You can view the results on the right.');
                } else {
                    addChatMessage('system', '✅ Previous analysis found. You can view the results on the right or ask questions to learn more.');
                }

                // Show chat section if not PHASE_START
                if (!IS_PHASE_START) {
                    chatSection.classList.remove('hidden');
                }

                // Restore any unsaved edits from localStorage cache
                const cached = loadFromCache();
                if (cached && cached.analysisData && cached.analysisData._editedSections) {
                    const editedSections = cached.analysisData._editedSections;
                    const count = Object.keys(editedSections).length;
                    if (count > 0) {
                        currentAnalysisData._editedSections = editedSections;
                        Object.keys(editedSections).forEach(sectionId => {
                            const contentDiv = document.getElementById(`content-${sectionId}`);
                            if (contentDiv) contentDiv.innerHTML = `<p>${formatTextForDisplay(editedSections[sectionId])}</p>`;
                            const ta = document.getElementById(`textarea-${sectionId}`);
                            if (ta) ta.value = editedSections[sectionId];
                            if (behaviorTracker.sections[sectionId]) {
                                behaviorTracker.sections[sectionId].currentText = editedSections[sectionId];
                                behaviorTracker.sections[sectionId].lastSavedText = editedSections[sectionId];
                            }
                        });
                        updateChangeStatistics();
                        updatePreviewButtonVisibility();
                        if (typeof RevisionInterface !== 'undefined') {
                            RevisionInterface.refreshFromTracker(behaviorTracker);
                        }
                        const savedAt = new Date(cached.savedAt).toLocaleString('vi-VN');
                        behaviorTracker.showTrackingIndicator(`✅ Restored ${count} sections edited (saved at ${savedAt})`);
                    }
                }
            }

            // Always render history panel (shows if history exists)
            renderHistoryPanel();
            updateSubmissionStatusBanner();

            const previewBtn = document.getElementById('preview-changes-button');
            if (previewBtn && !previewBtn.dataset.bound) {
                previewBtn.dataset.bound = '1';
                previewBtn.addEventListener('click', showPreviewModal);
            }
            updatePreviewButtonVisibility();
        });

        // --- Event Listeners ---
        fileUpload.addEventListener('change', handleFileUpload);
        deleteFileButton.addEventListener('click', handleDeleteFile);
        analyzeButton.addEventListener('click', handleAnalysis);
        chatButton.addEventListener('click', handleChatSubmit);
        resetButton.addEventListener('click', handleReset);
        tabs.forEach(tab => tab.addEventListener('click', handleTabClick));

        // --- Setup Functions ---
        function setupGeneratorTabs() {
            // Setup slideshow tab with generate button
            contentSlideshow.innerHTML = `
                <div class="genai-panel-empty">
                    <div class="genai-panel-empty__icon"><i class="bi bi-easel"></i></div>
                    <h3>Create Slideshow</h3>
                    <p>Turn the analysis into a short slide presentation.</p>
                    <button id="generate-slideshow-button" type="button" class="genai-btn-primary">
                        <i class="bi bi-play-circle"></i> Create Slideshow
                    </button>
                </div>
            `;

            contentQuiz.innerHTML = `
                <div class="genai-panel-empty">
                    <div class="genai-panel-empty__icon"><i class="bi bi-patch-question"></i></div>
                    <h3>Create Quiz</h3>
                    <p>Generate practice questions from the analysis.</p>
                    <button id="generate-quiz-button" type="button" class="genai-btn-primary">
                        <i class="bi bi-lightning"></i> Create Quiz
                    </button>
                </div>
            `;

            // Attach event listeners to the buttons
            const slideshowBtn = document.getElementById('generate-slideshow-button');
            const quizBtn = document.getElementById('generate-quiz-button');

            if (slideshowBtn) {
                slideshowBtn.addEventListener('click', handleGenerateSlideshow);
            }
            if (quizBtn) {
                quizBtn.addEventListener('click', handleGenerateQuiz);
            }
        }

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

            // Don't allow deleting content from task (checked by backend)
            if (HAS_TASK_CONTENT) {
                alert('Cannot delete content from task.');
                return;
            }

            fileUpload.value = ''; // Reset the file input
            textInput.value = ''; // Clear the textarea
            fileInfo.classList.add('hidden');
            fileNameDisplay.textContent = '';
        }

        async function handleAnalysis() {
            // Prevent re-analysis if already analyzed
            if (isAnalyzed && currentAnalysisData) {
                alert('Text has been analyzed. Please reset if you want to analyze again.');
                return;
            }

            const textToAnalyze = textInput.value.trim();
            if (!textToAnalyze) {
                alert('Please enter text or upload a file.');
                return;
            }
            setLoadingState(true, 'analyze', 'Analyzing with AI...');
            startOutlineValidationLoading();

            const analysisResult = await performMainAnalysis(textToAnalyze);

            if (analysisResult) {
                historySuppressed = false;
                currentAnalysisData = analysisResult;
                isAnalyzed = true;
                displayAnalysis(analysisResult);
                setupGeneratorTabs();

                placeholder.classList.add('hidden');
                resultsTabs.classList.remove('hidden');
                const pwHint2 = document.getElementById('previous-work-hint');
                if (pwHint2) pwHint2.classList.add('hidden');
                switchTab(tabAnalysis);

                // Show results immediately — don't keep spinner during DB/history saves
                setLoadingState(false, 'analyze');
                switchToChatMode();

                if (analysisResult._meta?.input_truncated && typeof showToast === 'function') {
                    showToast('info', 'Long text was shortened for faster AI analysis.');
                }

                const analysisContent = extractAnalysisContent(analysisResult);
                setLoadingState(true, 'analyze', 'Saving results...');
                try {
                    await saveAnalysisResult('analyze', analysisResult, analysisContent);
                    addToHistory(analysisResult);
                    saveToCache(currentAnalysisData);
                } finally {
                    setLoadingState(false, 'analyze');
                }

                if (IS_PHASE_START) {
                    addChatMessage('assistant', '✅ Analysis completed. You can view the results on the right.');
                } else {
                    addChatMessage('assistant', '✅ Analysis completed. You can view the results on the right or ask questions to learn more.');
                }
            } else {
                stopOutlineValidationLoading();
                setLoadingState(false, 'analyze');
            }
        }

        function switchToChatMode() {
            // Make text input readonly and disabled
            textInput.setAttribute('readonly', true);
            textInput.classList.add('bg-slate-100', 'cursor-not-allowed');

            // Hide analyze button, show reset button and comment button
            analyzeButton.classList.add('hidden');
            resetButton.classList.remove('hidden');
            resetButton.removeAttribute('disabled'); // Enable reset button

            // Show Comment / Self-Critique button
            const commentPageBtn = document.getElementById('comment-page-btn');
            if (commentPageBtn) {
                commentPageBtn.classList.remove('hidden');
            }

            // Check if PHASE_START - disable chat functionality (UI only, backend validates)
            if (IS_PHASE_START) {
                // Keep chat button hidden and disabled for PHASE_START
                chatButton.classList.add('hidden');
                chatButton.setAttribute('disabled', true);

                // Hide chat section completely for PHASE_START
                chatSection.classList.add('hidden');

                // Add notice message
                addChatMessage('system', '⚠️ This phase only allows one analysis, no follow-up questions.');
            } else {
                // Normal behavior for other phases
                chatButton.classList.remove('hidden');
                chatButton.removeAttribute('disabled'); // Enable chat button

                // Show chat section
                chatSection.classList.remove('hidden');

                // Clear chat messages placeholder
                chatMessages.innerHTML = '';
            }
        }

        function handleReset() {
            // Prevent reset for PHASE_START
            if (IS_PHASE_START) {
                alert('This phase does not allow retry. You can only analyze once.');
                return;
            }

            if (!confirm('Are you sure you want to retry? All analysis data and history will be lost.')) {
                return;
            }

            historySuppressed = true;

            // Reset all states
            currentAnalysisData = null;
            conversationHistory = [];
            isAnalyzed = false;
            currentResponseId = null;

            clearAllWorkHistory().finally(() => {
                hideHistoryUI();
            });

            // Reset text input - restore original file content if available
            if (HAS_TASK_CONTENT) {
                textInput.value = FILE_CONTENT; // Restore task content
                textInput.setAttribute('readonly', true);
                textInput.classList.remove('bg-slate-100', 'cursor-not-allowed');
            } else {
                textInput.value = '';
                textInput.removeAttribute('readonly');
                textInput.classList.remove('bg-slate-100', 'cursor-not-allowed');
            }

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

            behaviorTracker.sections = {};
            document.getElementById('preview-changes-button')?.classList.add('hidden');
            hideHistoryUI();
        }

        // Handle chat submission for refinement
        async function handleChatSubmit() {
            // Basic client-side check (backend will validate again)
            if (!isAnalyzed) {
                alert('Please analyze the text first.');
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
                // Call backend API with validation
                const response = await fetch('<?= URL_ROOT ?>/api/analyze_task.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        action: 'chat',
                        task_id: TASK_ID,
                        deliver_id: DELIVER_ID,
                        data: userMessage,
                        history: conversationHistory
                    })
                });

                const result = await response.json();

                if (!response.ok) {
                    // Backend validation failed
                    throw new Error(result.message || 'An error occurred');
                }

                const answerText = result.answer || result.response || result.message;
                if (answerText) {
                    // Add AI response to chat
                    addChatMessage('assistant', answerText);

                    // Add to conversation history
                    conversationHistory.push({
                        role: 'model',
                        parts: [{
                            text: answerText
                        }]
                    });

                    // Append to analysis display
                    appendToAnalysisDisplay(userMessage, answerText);

                    // Save chat result to database (child of current response)
                    // Store as array: [question, answer]
                    const chatContent = [
                        `**Question:** ${userMessage}`,
                        `**Answer:** ${answerText}`
                    ];
                    await saveAnalysisResult('chat', {
                        question: userMessage,
                        answer: answerText
                    }, chatContent);
                } else {
                    addChatMessage('system', '⚠️ No response from AI. Please try again.');
                }

            } catch (error) {
                console.error('Error during chat:', error);
                addChatMessage('system', `⚠️ Error: ${error.message}`);
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
            const targetTab = e.target.closest('.genai-tabs__btn, .tab-button') || e.target;
            if (!targetTab || !tabs.includes(targetTab)) return;
            if ((targetTab === tabSlideshow || targetTab === tabQuiz) && !isAnalyzed) {
                alert('Please analyze the text first.');
                return;
            }

            switchTab(targetTab);
        }

        async function handleGenerateSlideshow() {
            if (!isAnalyzed || !currentAnalysisData) {
                alert('Please analyze the text first.');
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
                container.innerHTML = `<p class="text-red-500">Cannot create slideshow. Please try again.</p>`;
            }
        }

        async function handleGenerateQuiz() {
            if (!isAnalyzed || !currentAnalysisData) {
                alert('Please analyze the text first.');
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
                container.innerHTML = `<p class="text-red-500">Cannot create quiz. Please try again.</p>`;
            }
        }

        // --- UI Logic ---
        function setLoadingState(isLoading, mode = 'analyze', label = 'Analyzing...') {
            if (mode === 'analyze') {
                if (isLoading) {
                    loadingSpinner.classList.remove('hidden');
                    analyzeButton.querySelector('#analyze-icon').classList.add('hidden');
                    buttonText.textContent = label;
                    analyzeButton.disabled = true;
                    chatButton.disabled = true;
                } else {
                    loadingSpinner.classList.add('hidden');
                    analyzeButton.querySelector('#analyze-icon').classList.remove('hidden');
                    buttonText.textContent = 'Analyze Text';
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
         * Makes a POST request to the backend API with task validation.
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

                const result = await response.json();

                if (!response.ok) {
                    // Backend validation failed
                    throw new Error(result.message || `API call failed: ${response.status}`);
                }

                if (result.error) throw new Error(result.error);
                return result;
            } catch (error) {
                console.error("Error during API call:", error);
                alert(`Error: ${error.message}`);
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
         * Saves analysis result to database
         * @param {string} actionType - Type of action: 'analyze', 'chat', 'slideshow', 'quiz'
         * @param {object} aiResponse - The AI response data
         * @param {string} userContent - The user's input content
         * @returns {Promise<boolean>} Success status
         */
        async function saveAnalysisResult(actionType, aiResponse, userContent) {
            try {
                const response = await fetch('<?= URL_ROOT ?>/api/save_response.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        task_id: TASK_ID,
                        deliver_id: DELIVER_ID,
                        action_type: actionType,
                        ai_response: aiResponse,
                        user_content: userContent,
                        parent_response_id: actionType === 'chat' ? currentResponseId : null
                    })
                });

                const result = await response.json();

                if (response.ok && result.success) {
                    // Store response_id for threading chat responses
                    if (actionType === 'analyze') {
                        currentResponseId = result.data.response_id;
                    }
                    console.log('Saved:', result.data);
                    return true;
                } else {
                    console.warn('Failed to save:', result.message);
                    return false;
                }
            } catch (error) {
                console.error('Error saving analysis result:', error);
                return false;
            }
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

        /**
         * Extract analysis content as ordered array for database storage
         * @param {object} result - The analysis result object
         * @returns {Array<string>} Array of content sections in display order
         */
        function extractAnalysisContent(result) {
            const content = [];

            // Title
            if (result.title) {
                content.push(`# ${result.title}`);
            }

            // Keywords
            if (result.keywords && result.keywords.length > 0) {
                content.push(`**Keywords:** ${result.keywords.join(', ')}`);
            }

            // General Analysis - Thematic Analysis
            if (result.generalAnalysis?.thematicAnalysis) {
                result.generalAnalysis.thematicAnalysis.forEach(section => {
                    content.push(`## ${section.heading}\n${section.content}`);
                });
            }

            // General Analysis - Linguistic Insights
            if (result.generalAnalysis?.linguisticInsights) {
                const insights = result.generalAnalysis.linguisticInsights;
                let linguisticText = '## Language & Symbolism\n';

                if (insights.motifs?.length > 0) {
                    linguisticText += `\n**Recurring Motifs:**\n${insights.motifs.map(m => `- ${m}`).join('\n')}`;
                }
                if (insights.symbolsAndMetaphors?.length > 0) {
                    linguisticText += `\n**Symbols & Metaphors:**\n${insights.symbolsAndMetaphors.map(s => `- ${s}`).join('\n')}`;
                }
                if (insights.temporalElements) {
                    linguisticText += `\n**Temporal Elements:** ${insights.temporalElements}`;
                }
                if (insights.natureAndScenery) {
                    linguisticText += `\n**Nature & Scenery:** ${insights.natureAndScenery}`;
                }

                content.push(linguisticText);
            }

            // Story Analysis
            if (result.storyAnalysis) {
                const sa = result.storyAnalysis;
                if (sa.character) content.push(`## Characters\n${sa.character}`);
                if (sa.space) content.push(`## Space\n${sa.space}`);
                if (sa.time) content.push(`## Time\n${sa.time}`);
                if (sa.dialogue) content.push(`## Dialogue & Inner Monologue\n${sa.dialogue}`);
                if (sa.narrator) content.push(`## Narrator\n${sa.narrator}`);
                if (sa.situation) content.push(`## Story Situation\n${sa.situation}`);
                if (sa.prominentDetails) content.push(`## Prominent Details\n${sa.prominentDetails}`);
            }

            // Drama Analysis
            if (result.dramaAnalysis) {
                const has = result.dramaAnalysis;
                if (has.characters) content.push(`## Characters (Drama)\n${has.characters}`);
                if (has.conflict) content.push(`## Conflict\n${has.conflict}`);
                if (has.action) content.push(`## Action\n${has.action}`);
                if (has.structure) content.push(`## Structure\n${has.structure}`);
                if (has.dialogue) content.push(`## Dialogue\n${has.dialogue}`);
            }

            // Poetry Analysis
            if (result.poetryAnalysis) {
                const pa = result.poetryAnalysis;
                if (pa.rhyming) content.push(`## Rhyming in Poetry\n${pa.rhyming}`);
                if (pa.emotionalFlow) content.push(`## Emotional Flow\n${pa.emotionalFlow}`);
                if (pa.structure) content.push(`## Poem Structure\n${pa.structure}`);
                if (pa.imagery) content.push(`## Imagery in Poetry\n${pa.imagery}`);
                if (pa.rhythm) content.push(`## Rhythm Analysis\n${pa.rhythm}`);
                if (pa.spaceTimeOrganization) content.push(`## Space & Time Organization\n${pa.spaceTimeOrganization}`);
                if (pa.rhetoricalDevices) content.push(`## Rhetorical Devices\n${pa.rhetoricalDevices}`);
            }

            // Concluding Thoughts
            if (result.concludingThoughts) {
                const ct = result.concludingThoughts;
                if (ct.lesson) content.push(`## Lesson\n${ct.lesson}`);
                if (ct.message) content.push(`## Message\n${ct.message}`);
            }

            // Intertextuality
            if (result.intertextuality) {
                const it = result.intertextuality;
                let intertextText = '## Analysis Intertextuality\n';

                if (it.initialAnalysis) intertextText += `${it.initialAnalysis}\n`;
                if (it.comparativeText) {
                    intertextText += `\n**Comparison with:** ${it.comparativeText.title} - ${it.comparativeText.author}\n`;
                }
                if (it.comparison) {
                    if (it.comparison.similarities) intertextText += `\n**Similarities:** ${it.comparison.similarities}\n`;
                    if (it.comparison.differences) intertextText += `\n**Differences:** ${it.comparison.differences}\n`;
                    if (it.comparison.effects) intertextText += `\n**Effects:** ${it.comparison.effects}`;
                }

                content.push(intertextText);
            }

            return content;
        }

        // === TEXT TO IMAGE CONVERSION (from v4.html) ===
        async function textToImage(text, width = 800, options = {}) {
            const {
                fontSize = 16,
                    fontFamily = 'Inter, sans-serif',
                    lineHeight = 1.7,
                    padding = 30,
                    backgroundColor = '#ffffff',
                    textColor = '#334155',
                    maxWidth = width - padding * 2,
            } = options;

            const canvas = document.createElement('canvas');
            const ctx = canvas.getContext('2d');

            const parseInlineFormatting = (line) => {
                const segments = [];
                let remainingText = line;
                let match;
                const regex = /(\*|_)(.*?)\1/g;
                let lastIndex = 0;

                while ((match = regex.exec(remainingText)) !== null) {
                    if (match.index > lastIndex) {
                        segments.push({
                            text: remainingText.substring(lastIndex, match.index),
                            style: {}
                        });
                    }
                    const style = {};
                    if (match[1] === '*') style.bold = true;
                    if (match[1] === '_') style.italic = true;
                    segments.push({
                        text: match[2],
                        style: style
                    });
                    lastIndex = regex.lastIndex;
                }

                if (lastIndex < remainingText.length) {
                    segments.push({
                        text: remainingText.substring(lastIndex),
                        style: {}
                    });
                }

                if (segments.length === 0 && line) {
                    segments.push({
                        text: line,
                        style: {}
                    });
                }

                return segments;
            };

            const applyStyle = (style) => {
                let fontStyle = '';
                if (style.italic) fontStyle += 'italic ';
                if (style.bold) fontStyle += 'bold ';
                return `${fontStyle} ${fontSize}px ${fontFamily}`;
            };

            const paragraphs = text.split('\n\n');
            const lines = [];

            paragraphs.forEach((paragraph) => {
                paragraph.split('\n').forEach((rawLine) => {
                    if (rawLine.trim() === '') {
                        lines.push({
                            segments: [],
                            height: lineHeight * fontSize * 0.5
                        });
                        return;
                    }

                    const parsedSegments = parseInlineFormatting(rawLine);
                    let currentLineSegments = [];
                    let currentLineWidth = 0;

                    parsedSegments.forEach((segment) => {
                        ctx.font = applyStyle(segment.style);
                        const words = segment.text.split(/(\s+)/).filter(Boolean);

                        words.forEach((word) => {
                            const wordWidth = ctx.measureText(word).width;

                            if (currentLineWidth + wordWidth > maxWidth && currentLineSegments.length > 0 && !/^\s+$/.test(word)) {
                                lines.push({
                                    segments: [...currentLineSegments],
                                    height: lineHeight * fontSize
                                });
                                const startingWord = /^\s+$/.test(word) ? '' : word.trimStart();
                                currentLineSegments = [{
                                    text: startingWord,
                                    style: segment.style
                                }];
                                currentLineWidth = startingWord ? ctx.measureText(startingWord).width : 0;
                            } else {
                                if (currentLineSegments.length === 0 && /^\s+$/.test(word)) return;
                                currentLineSegments.push({
                                    text: word,
                                    style: segment.style
                                });
                                currentLineWidth += wordWidth;
                            }
                        });
                    });

                    if (currentLineSegments.length > 0) {
                        lines.push({
                            segments: [...currentLineSegments],
                            height: lineHeight * fontSize
                        });
                    }
                });
                lines.push({
                    segments: [],
                    height: lineHeight * fontSize * 0.7
                });
            });

            if (lines.length > 0 && lines[lines.length - 1].segments.length === 0) lines.pop();

            const totalHeight = lines.reduce((sum, line) => sum + line.height, 0) + padding * 2;
            canvas.width = width;
            canvas.height = Math.max(totalHeight, 50);

            ctx.fillStyle = backgroundColor;
            ctx.fillRect(0, 0, canvas.width, canvas.height);
            ctx.textBaseline = 'top';

            let currentY = padding;
            lines.forEach((line) => {
                let currentX = padding;
                line.segments.forEach((segment) => {
                    ctx.font = applyStyle(segment.style);
                    ctx.fillStyle = textColor;
                    ctx.fillText(segment.text, currentX, currentY);
                    currentX += ctx.measureText(segment.text).width;
                });
                currentY += line.height;
            });

            return canvas.toDataURL('image/png');
        }

        // === FORMATTING FUNCTIONS FOR IMAGE GENERATION ===
        const formatForImage = (content) =>
            content ?
            String(content)
            .replace(/<br\s*\/?>/gi, '\n')
            .replace(/<\/?i>/gi, '_')
            .replace(/<\/?em>/gi, '_')
            .replace(/<\/?sin>/gi, '*')
            .replace(/<\/?b>/gi, '*') :
            '';

        function formatGeneralAnalysisForImage(data) {
            let text = '';

            // Thematic Analysis
            if (data.thematicAnalysis && data.thematicAnalysis.length > 0) {
                data.thematicAnalysis.forEach(section => {
                    text += `*${section.heading}*\n${formatForImage(section.content)}\n\n`;
                });
            }

            // Linguistic Insights
            if (data.linguisticInsights) {
                const insights = data.linguisticInsights;
                if (insights.motifs?.length) {
                    text += `*Recurring Motifs:*\n${insights.motifs.map(i => `• ${formatForImage(i)}`).join('\n')}\n\n`;
                }
                if (insights.symbolsAndMetaphors?.length) {
                    text += `*Symbols & Metaphors:*\n${insights.symbolsAndMetaphors.map(i => `• ${formatForImage(i)}`).join('\n')}\n\n`;
                }
                if (insights.temporalElements) {
                    text += `*Temporal Elements:*\n${formatForImage(insights.temporalElements)}\n\n`;
                }
                if (insights.natureAndScenery) {
                    text += `*Nature & Scenery:*\n${formatForImage(insights.natureAndScenery)}`;
                }
            }

            return text.trim();
        }

        function formatStoryAnalysisForImage(data) {
            const order = ['character', 'space', 'time', 'dialogue', 'narrator', 'situation', 'prominentDetails'];
            const titles = {
                character: 'Characters',
                space: 'Space',
                time: 'Time',
                dialogue: 'Dialogue & Inner Monologue',
                narrator: 'Narrator',
                situation: 'Story Situation',
                prominentDetails: 'Prominent Details'
            };

            return order
                .map(key => data[key] ? `*${titles[key]}:*\n${formatForImage(data[key])}` : null)
                .filter(Boolean)
                .join('\n\n');
        }

        function formatDramaAnalysisForImage(data) {
            const order = ['characters', 'conflict', 'action', 'structure', 'dialogue'];
            const titles = {
                characters: 'Characters',
                conflict: 'Conflict',
                action: 'Action',
                structure: 'Structure',
                dialogue: 'Dialogue'
            };

            return order
                .map(key => data[key] ? `*${titles[key]}:*\n${formatForImage(data[key])}` : null)
                .filter(Boolean)
                .join('\n\n');
        }

        function formatPoetryAnalysisForImage(data) {
            const order = ['rhyming', 'rhythm', 'structure', 'imagery', 'emotionalFlow', 'rhetoricalDevices', 'spaceTimeOrganization'];
            const titles = {
                rhyming: 'Rhyming in Poetry',
                rhythm: 'Rhythm Analysis',
                structure: 'Poem Structure',
                imagery: 'Imagery in Poetry',
                emotionalFlow: 'Emotional Flow',
                rhetoricalDevices: 'Rhetorical Devices',
                spaceTimeOrganization: 'Space & Time Organization'
            };

            return order
                .map(key => data[key] ? `*${titles[key]}:*\n${formatForImage(data[key])}` : null)
                .filter(Boolean)
                .join('\n\n');
        }

        function formatConclusionForImage(data) {
            let text = '';
            if (data.lesson) text += `*Lesson:*\n${formatForImage(data.lesson)}\n\n`;
            if (data.message) text += `*Message:*\n${formatForImage(data.message)}`;
            return text.trim();
        }

        function formatIntertextualityForImage(data) {
            let text = '';
            if (data.initialAnalysis) text += `${formatForImage(data.initialAnalysis)}\n\n`;
            if (data.comparativeText) {
                text += `*Comparison with:*\n_${data.comparativeText.title || ''}_ - ${data.comparativeText.author || ''}\n\n`;
            }
            if (data.comparison) {
                if (data.comparison.similarities) text += `*Similarities:*\n${formatForImage(data.comparison.similarities)}\n\n`;
                if (data.comparison.differences) text += `*Differences:*\n${formatForImage(data.comparison.differences)}\n\n`;
                if (data.comparison.effects) text += `*Effects:*\n${formatForImage(data.comparison.effects)}`;
            }
            return text.trim();
        }

        // === CREATE IMAGE BLOCK HTML ===
        function createImageBlock(imageData, sectionName) {
            return `
                <div class="content-image-wrapper" data-section="${sectionName}">
                    <img src="${imageData}" alt="${sectionName} Analysis Image">
                </div>
            `;
        }

        // === GENERATE AND INSERT IMAGE BLOCKS ===
        async function generateContentImages(result) {
            const sectionsToImage = [{
                    id: 'general-images',
                    title: 'General Analysis',
                    data: result.generalAnalysis,
                    formatFunc: formatGeneralAnalysisForImage,
                    bgColor: '#f9fafb',
                    textColor: '#1f2937',
                },
                {
                    id: 'story-images',
                    title: 'Story Analysis',
                    data: result.storyAnalysis,
                    formatFunc: formatStoryAnalysisForImage,
                    bgColor: '#f0f9ff',
                    textColor: '#1e40af',
                },
                {
                    id: 'drama-images',
                    title: 'Drama Analysis',
                    data: result.dramaAnalysis,
                    formatFunc: formatDramaAnalysisForImage,
                    bgColor: '#fef3c7',
                    textColor: '#92400e',
                },
                {
                    id: 'poetry-images',
                    title: 'Poetry Analysis',
                    data: result.poetryAnalysis,
                    formatFunc: formatPoetryAnalysisForImage,
                    bgColor: '#fce7f3',
                    textColor: '#831843',
                },
                {
                    id: 'conclusion-images',
                    title: 'Lesson & Message',
                    data: result.concludingThoughts,
                    formatFunc: formatConclusionForImage,
                    bgColor: '#dcfce7',
                    textColor: '#14532d',
                },
                {
                    id: 'intertextuality-images',
                    title: 'Analysis Intertextuality',
                    data: result.intertextuality,
                    formatFunc: formatIntertextualityForImage,
                    bgColor: '#ede9fe',
                    textColor: '#4c1d95',
                },
            ];

            const imagePromises = [];

            sectionsToImage.forEach((section) => {
                const container = document.getElementById(section.id);
                if (container && section.data) {
                    const textContent = section.formatFunc(section.data);
                    if (textContent) {
                        const promise = textToImage(textContent, 750, {
                                fontSize: 15,
                                lineHeight: 1.7,
                                backgroundColor: section.bgColor,
                                textColor: section.textColor,
                                padding: 25,
                            })
                            .then((imageData) => {
                                if (imageData) {
                                    container.innerHTML = createImageBlock(imageData, section.title);
                                } else {
                                    console.warn(`Cannot create image for section: ${section.title}`);
                                }
                            })
                            .catch((error) => {
                                console.error(`Error creating image for ${section.title}:`, error);
                            });
                        imagePromises.push(promise);
                    }
                }
            });

            try {
                await Promise.all(imagePromises);
                console.log('✅ All analysis images generated and displayed');
                setupImageBlockHandlers();
            } catch (error) {
                console.error('Error during image generation/display:', error);
            }
        }

        // === IMAGE BLOCK INTERACTION ===
        let selectedBlock = null;

        function setupImageBlockHandlers() {
            const wrappers = document.querySelectorAll('.content-image-wrapper');
            wrappers.forEach((wrapper) => {
                const section = wrapper.dataset.section || 'Unknown';
                wrapper.addEventListener('click', function(e) {
                    if (selectedBlock && selectedBlock !== this) {
                        selectedBlock.classList.remove('selected');
                    }
                    this.classList.toggle('selected');
                    selectedBlock = this.classList.contains('selected') ? this : null;
                    console.log(selectedBlock ? `📌 Selected Image: "${section}"` : `⚪ Deselected Image: "${section}"`);
                });
            });
        }

        // === USER BEHAVIOR TRACKING SYSTEM ===
        const behaviorTracker = {
            sections: {},
            globalStats: {
                totalCopyEvents: 0,
                totalCopiedChars: 0,
                totalOriginalChars: 0,
                totalEditedChars: 0,
                editHistory: [],
                saveEvents: [],
                startTime: Date.now()
            },

            // Initialize tracking for a section
            initSection(sectionId, originalText) {
                this.sections[sectionId] = {
                    originalText: originalText,
                    currentText: originalText,
                    copyEvents: [],
                    editEvents: [],
                    pasteEvents: [],
                    totalCopied: 0,
                    editStartTime: null,
                    lastSavedText: originalText
                };
                this.globalStats.totalOriginalChars += originalText.length;
            },

            // Track copy event
            trackCopy(sectionId, copiedText) {
                if (!this.sections[sectionId]) return;
                
                const section = this.sections[sectionId];
                const copyPercent = (copiedText.length / section.originalText.length * 100).toFixed(1);
                
                section.copyEvents.push({
                    timestamp: Date.now(),
                    copiedText: copiedText,
                    copiedLength: copiedText.length,
                    copyPercent: parseFloat(copyPercent)
                });
                section.totalCopied += copiedText.length;
                this.globalStats.totalCopyEvents++;
                this.globalStats.totalCopiedChars += copiedText.length;

                console.log(`📋 Copy tracked: ${copyPercent}% of section "${sectionId}"`);
                this.showTrackingIndicator(`Copied ${copyPercent}% content`);
            },

            // Track paste event (detect if user pastes content)
            trackPaste(sectionId, pastedText) {
                if (!this.sections[sectionId]) return;
                
                this.sections[sectionId].pasteEvents.push({
                    timestamp: Date.now(),
                    pastedLength: pastedText.length
                });
                console.log(`📝 Paste tracked in section "${sectionId}": ${pastedText.length} chars`);
            },

            // Track edit start
            startEdit(sectionId) {
                if (!this.sections[sectionId]) return;
                this.sections[sectionId].editStartTime = Date.now();
            },

            // Track edit changes and calculate diff
            trackEdit(sectionId, newText) {
                if (!this.sections[sectionId]) return;
                
                const section = this.sections[sectionId];
                const oldText = section.lastSavedText;
                const diff = this.calculateDiff(oldText, newText);
                
                section.editEvents.push({
                    timestamp: Date.now(),
                    editDuration: section.editStartTime ? Date.now() - section.editStartTime : 0,
                    changePercent: diff.changePercent,
                    addedChars: diff.added,
                    removedChars: diff.removed,
                    totalChanges: diff.totalChanges
                });
                
                section.currentText = newText;
                section.lastSavedText = newText;
                section.editStartTime = null;

                return diff;
            },

            // Calculate text difference
            calculateDiff(oldText, newText) {
                const oldLen = oldText.length;
                const newLen = newText.length;
                
                // Simple Levenshtein-like comparison for change detection
                let commonPrefix = 0;
                let commonSuffix = 0;
                const minLen = Math.min(oldLen, newLen);
                
                // Find common prefix
                while (commonPrefix < minLen && oldText[commonPrefix] === newText[commonPrefix]) {
                    commonPrefix++;
                }
                
                // Find common suffix
                while (commonSuffix < (minLen - commonPrefix) && 
                       oldText[oldLen - 1 - commonSuffix] === newText[newLen - 1 - commonSuffix]) {
                    commonSuffix++;
                }
                
                const removed = oldLen - commonPrefix - commonSuffix;
                const added = newLen - commonPrefix - commonSuffix;
                const totalChanges = removed + added;
                const changePercent = oldLen > 0 ? ((totalChanges / oldLen) * 100).toFixed(1) : 0;

                return {
                    removed,
                    added,
                    totalChanges,
                    changePercent: parseFloat(changePercent),
                    isSignificant: parseFloat(changePercent) >= 10
                };
            },

            // Track save event
            trackSave(sectionId) {
                const section = this.sections[sectionId];
                if (!section) return;

                const saveEvent = {
                    sectionId,
                    timestamp: Date.now(),
                    copyEventsCount: section.copyEvents.length,
                    editEventsCount: section.editEvents.length,
                    totalCopiedPercent: section.originalText.length > 0 
                        ? (section.totalCopied / section.originalText.length * 100).toFixed(1) 
                        : 0
                };
                
                this.globalStats.saveEvents.push(saveEvent);
                console.log('💾 Save tracked:', saveEvent);
            },

            // Get summary for a section
            getSectionSummary(sectionId) {
                const section = this.sections[sectionId];
                if (!section) return null;

                const totalCopyPercent = section.originalText.length > 0
                    ? (section.totalCopied / section.originalText.length * 100).toFixed(1)
                    : 0;

                const lastEdit = section.editEvents[section.editEvents.length - 1];
                const editChangePercent = lastEdit ? lastEdit.changePercent : 0;

                return {
                    sectionId,
                    copyEventsCount: section.copyEvents.length,
                    totalCopyPercent: parseFloat(totalCopyPercent),
                    editEventsCount: section.editEvents.length,
                    lastEditChangePercent: editChangePercent,
                    pasteEventsCount: section.pasteEvents.length
                };
            },

            // Get global summary
            getGlobalSummary() {
                const now = Date.now();
                const sessionDuration = Math.floor((now - this.globalStats.startTime) / 1000);
                
                let totalEditChange = 0;
                let sectionsEdited = 0;
                
                Object.keys(this.sections).forEach(sectionId => {
                    const section = this.sections[sectionId];
                    if (section.editEvents.length > 0) {
                        sectionsEdited++;
                        const lastEdit = section.editEvents[section.editEvents.length - 1];
                        totalEditChange += lastEdit.changePercent;
                    }
                });

                const avgEditChange = sectionsEdited > 0 ? (totalEditChange / sectionsEdited).toFixed(1) : 0;
                const copyPercent = this.globalStats.totalOriginalChars > 0
                    ? (this.globalStats.totalCopiedChars / this.globalStats.totalOriginalChars * 100).toFixed(1)
                    : 0;

                return {
                    sessionDuration,
                    totalCopyEvents: this.globalStats.totalCopyEvents,
                    totalCopyPercent: parseFloat(copyPercent),
                    totalSaveEvents: this.globalStats.saveEvents.length,
                    sectionsEdited,
                    averageEditChangePercent: parseFloat(avgEditChange)
                };
            },

            // Show tracking indicator
            showTrackingIndicator(message) {
                let indicator = document.getElementById('tracking-indicator');
                if (!indicator) {
                    indicator = document.createElement('div');
                    indicator.id = 'tracking-indicator';
                    indicator.className = 'tracking-indicator';
                    document.body.appendChild(indicator);
                }
                
                indicator.textContent = message;
                indicator.classList.add('show');
                
                setTimeout(() => {
                    indicator.classList.remove('show');
                }, 2000);
            },

            // Save tracking data to server
            async saveTrackingData() {
                const summary = this.getGlobalSummary();
                const sectionSummaries = {};
                
                Object.keys(this.sections).forEach(sectionId => {
                    sectionSummaries[sectionId] = this.getSectionSummary(sectionId);
                });

                try {
                    const response = await fetch('<?= URL_ROOT ?>/api/save_response.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({
                            task_id: TASK_ID,
                            deliver_id: DELIVER_ID,
                            action_type: 'tracking',
                            tracking_data: {
                                globalSummary: summary,
                                sectionSummaries: sectionSummaries,
                                rawData: this.globalStats
                            }
                        })
                    });
                    const result = await response.json();
                    console.log('📊 Tracking data saved:', result);
                } catch (error) {
                    console.error('Error saving tracking data:', error);
                }
            }
        };

        // === FORMAT TEXT FOR DISPLAY (convert formatting markers to HTML) ===
        function formatTextForDisplay(text) {
            if (!text) return '';
            return text
                .replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>')
                .replace(/\*(.*?)\*/g, '<em>$1</em>')
                .replace(/_(.*?)_/g, '<em>$1</em>')
                .replace(/\n\n/g, '</p><p>')
                .replace(/\n/g, '<br>');
        }

        // === CREATE EDITABLE TEXT BLOCK ===
        function createEditableTextBlock(sectionId, title, content, bgColor = '#f9fafb') {
            const formattedContent = formatTextForDisplay(content);
            
            // Initialize tracking for this section
            behaviorTracker.initSection(sectionId, content);
            const commentUrl = `/class/homework/comment?task_id=${TASK_ID}&topic_id=<?= $topic_id ?>&phase_id=<?= $phase_id ?>&deliver_id=${DELIVER_ID}`;
            
            return `
                <div class="content-text-wrapper" data-section="${sectionId}" data-original="${encodeURIComponent(content)}" style="background-color: ${bgColor};">
                    <div class="section-header flex justify-between items-center mb-2">
                        <h3 class="section-title text-base font-semibold">${title}</h3>
                        <div class="flex items-center gap-2">
                            <a href="${commentUrl}" class="px-3 py-1 text-xs font-semibold bg-emerald-600 hover:bg-emerald-700 text-white rounded transition-colors inline-flex items-center gap-1" title="Bình luận & Tự phản biện phần này">
                                <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" fill="currentColor" viewBox="0 0 16 16">
                                    <path d="M2.678 11.894a1 1 0 0 1 .287.801 10.97 10.97 0 0 1-.398 2c1.395-.323 2.247-.697 2.634-.893a1 1 0 0 1 .71-.074A8.06 8.06 0 0 0 8 14c3.996 0 7-2.807 7-6 0-3.192-3.004-6-7-6S1 4.808 1 8c0 1.468.617 2.83 1.678 3.894zm-.493 3.905a21.682 21.682 0 0 1-.713.129c-.2.032-.352-.176-.273-.362a9.68 9.68 0 0 0 .244-.637l.003-.01c.248-.72.45-1.548.524-2.319C.743 11.37 0 9.76 0 8c0-3.866 3.582-7 8-7s8 3.134 8 7-3.582 7-8 7a9.06 9.06 0 0 1-2.347-.306c-.52.263-1.639.742-3.468 1.105z"/>
                                </svg>
                                💬 Bình luận
                            </a>
                            <button class="edit-btn" data-section="${sectionId}" onclick="toggleEditMode('${sectionId}')">
                                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                                    <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                                </svg>
                                Edit
                            </button>
                        </div>
                    </div>
                    <div class="text-content" id="content-${sectionId}">
                        <p>${formattedContent}</p>
                    </div>
                    <div class="edit-mode hidden" id="edit-${sectionId}">
                        <textarea class="edit-textarea" id="textarea-${sectionId}" rows="8">${content}</textarea>
                        <div class="edit-actions">
                            <button class="cancel-btn" onclick="cancelEdit('${sectionId}')">Cancel</button>
                            <button class="save-btn" onclick="saveEdit('${sectionId}')">Save Changes</button>
                        </div>
                    </div>
                </div>
            `;
        }

        // === TOGGLE EDIT MODE ===
        function toggleEditMode(sectionId) {
            const wrapper = document.querySelector(`[data-section="${sectionId}"]`);
            const contentDiv = document.getElementById(`content-${sectionId}`);
            const editDiv = document.getElementById(`edit-${sectionId}`);
            const editBtn = wrapper.querySelector('.edit-btn');
            const textarea = document.getElementById(`textarea-${sectionId}`);
            
            if (editDiv.classList.contains('hidden')) {
                // Enter edit mode
                contentDiv.classList.add('hidden');
                editDiv.classList.remove('hidden');
                editBtn.classList.add('active');
                editBtn.innerHTML = `
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="18" y1="6" x2="6" y2="18"></line>
                        <line x1="6" y1="6" x2="18" y2="18"></line>
                    </svg>
                    In progress...
                `;
                wrapper.classList.add('editing');
                behaviorTracker.startEdit(sectionId);
                if (typeof RevisionInterface !== 'undefined') {
                    RevisionInterface.activateRevision(sectionId, behaviorTracker);
                }
                
                // Start live timer display
                const editActionsDiv = editDiv.querySelector('.edit-actions');
                const timerEl = document.createElement('div');
                timerEl.id = `timer-${sectionId}`;
                timerEl.className = 'text-xs text-slate-500 flex items-center gap-1 mt-1 pt-1 border-t border-slate-100';
                timerEl.innerHTML = `<span>⏱</span><span id="timer-display-${sectionId}">0:00</span><span class="text-slate-400"> Editing...</span>`;
                editActionsDiv.prepend(timerEl);
                let elapsed = 0;
                editTimers[sectionId] = setInterval(() => {
                    elapsed++;
                    const m = Math.floor(elapsed / 60), s = elapsed % 60;
                    const display = document.getElementById(`timer-display-${sectionId}`);
                    if (display) display.textContent = `${m}:${s.toString().padStart(2, '0')}`;
                }, 1000);
                
                // Setup copy/paste tracking on textarea
                setupTextareaTracking(textarea, sectionId);
            } else {
                // Exit edit mode without saving
                cancelEdit(sectionId);
            }
        }

        // === SETUP TEXTAREA TRACKING ===
        function setupTextareaTracking(textarea, sectionId) {
            // Track copy events
            textarea.addEventListener('copy', (e) => {
                const selection = window.getSelection().toString();
                if (selection) {
                    behaviorTracker.trackCopy(sectionId, selection);
                }
            });

            // Track paste events
            textarea.addEventListener('paste', (e) => {
                const pastedText = e.clipboardData.getData('text');
                if (pastedText) {
                    behaviorTracker.trackPaste(sectionId, pastedText);
                }
            });

            // Track selection for copy detection
            textarea.addEventListener('select', () => {
                const selection = textarea.value.substring(textarea.selectionStart, textarea.selectionEnd);
                if (selection.length > 50) {
                    console.log(`📌 Selection detected: ${selection.length} chars`);
                }
            });
        }

        // === CANCEL EDIT ===
        function cancelEdit(sectionId) {
            const wrapper = document.querySelector(`[data-section="${sectionId}"]`);
            const contentDiv = document.getElementById(`content-${sectionId}`);
            const editDiv = document.getElementById(`edit-${sectionId}`);
            const editBtn = wrapper.querySelector('.edit-btn');
            const textarea = document.getElementById(`textarea-${sectionId}`);
            
            // Restore original content in textarea
            const originalContent = decodeURIComponent(wrapper.dataset.original);
            const section = behaviorTracker.sections[sectionId];
            textarea.value = section ? section.lastSavedText : originalContent;
            
            // Clear active timer
            if (editTimers[sectionId]) { clearInterval(editTimers[sectionId]); delete editTimers[sectionId]; }
            const timerElCancel = document.getElementById(`timer-${sectionId}`);
            if (timerElCancel) timerElCancel.remove();
            
            contentDiv.classList.remove('hidden');
            editDiv.classList.add('hidden');
            editBtn.classList.remove('active');
            editBtn.innerHTML = `
                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                    <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                </svg>
                Edit
            `;
            wrapper.classList.remove('editing');
        }

        // === SAVE EDIT ===
        async function saveEdit(sectionId) {
            const wrapper = document.querySelector(`[data-section="${sectionId}"]`);
            const contentDiv = document.getElementById(`content-${sectionId}`);
            const editDiv = document.getElementById(`edit-${sectionId}`);
            const editBtn = wrapper.querySelector('.edit-btn');
            const textarea = document.getElementById(`textarea-${sectionId}`);
            
            const newContent = textarea.value;
            
            // Clear active timer
            if (editTimers[sectionId]) { clearInterval(editTimers[sectionId]); delete editTimers[sectionId]; }
            const timerElSave = document.getElementById(`timer-${sectionId}`);
            if (timerElSave) timerElSave.remove();
            
            // Track the edit
            const diff = behaviorTracker.trackEdit(sectionId, newContent);
            behaviorTracker.trackSave(sectionId);
            
            // Show diff info
            let changeClass = 'low';
            if (diff.changePercent >= 50) changeClass = 'high';
            else if (diff.changePercent >= 20) changeClass = 'medium';
            
            behaviorTracker.showTrackingIndicator(
                `Saved! Changes: ${diff.changePercent}% (${diff.added > 0 ? '+' + diff.added : ''}${diff.removed > 0 ? ' -' + diff.removed : ''} characters)`
            );
            
            // Update display
            const formattedContent = formatTextForDisplay(newContent);
            contentDiv.innerHTML = `<p>${formattedContent}</p>`;
            
            // Exit edit mode
            contentDiv.classList.remove('hidden');
            editDiv.classList.add('hidden');
            editBtn.classList.remove('active');
            editBtn.innerHTML = `
                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                    <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                </svg>
                Edit
            `;
            wrapper.classList.remove('editing');

            // Update the analysis data for saving
            updateAnalysisSection(sectionId, newContent);

            // Persist edits to cache so they survive page refresh
            saveToCache(currentAnalysisData);

            updateChangeStatistics();
            updatePreviewButtonVisibility();

            if (typeof RevisionInterface !== 'undefined') {
                RevisionInterface.activateRevision(sectionId, behaviorTracker);
            }

            await behaviorTracker.saveTrackingData();
        }

        // === UPDATE ANALYSIS SECTION ===
        function updateAnalysisSection(sectionId, newContent) {
            if (!currentAnalysisData) return;
            
            // Map section IDs to analysis data paths
            const sectionMap = {
                'general': 'generalAnalysis',
                'story': 'storyAnalysis',
                'drama': 'dramaAnalysis',
                'poetry': 'poetryAnalysis',
                'conclusion': 'concludingThoughts',
                'intertextuality': 'intertextuality'
            };
            
            // For now, store edited content in a separate object
            if (!currentAnalysisData._editedSections) {
                currentAnalysisData._editedSections = {};
            }
            currentAnalysisData._editedSections[sectionId] = newContent;
            
            console.log('📝 Analysis section updated:', sectionId);
        }

        let outlineValidationTimer = null;

        function getOutlineValidationLang() {
            return (document.documentElement.lang || 'en').toLowerCase().startsWith('vi') ? 'vi' : 'en';
        }

        function mountOutlineValidationPanel(validation, options = {}) {
            const panelHtml = OutlineValidation.renderOutlineValidationPanel(validation, {
                loading: !!options.loading,
                activeStep: options.activeStep ?? -1,
                lang: getOutlineValidationLang(),
            });
            const existing = document.getElementById('outline-validation-panel');
            if (existing) {
                existing.outerHTML = panelHtml;
                return;
            }
            contentAnalysis.insertAdjacentHTML('afterbegin', panelHtml);
        }

        function startOutlineValidationLoading() {
            stopOutlineValidationLoading();
            placeholder.classList.add('hidden');
            resultsTabs.classList.remove('hidden');
            switchTab(tabAnalysis);
            contentAnalysis.innerHTML = '';
            let step = 0;
            mountOutlineValidationPanel(OutlineValidation.getLoadingValidation(), { loading: true, activeStep: step });
            outlineValidationTimer = setInterval(() => {
                step = (step + 1) % 6;
                mountOutlineValidationPanel(OutlineValidation.getLoadingValidation(), { loading: true, activeStep: step });
            }, 900);
        }

        function stopOutlineValidationLoading() {
            if (outlineValidationTimer) {
                clearInterval(outlineValidationTimer);
                outlineValidationTimer = null;
            }
        }

        function renderOutlineValidationForResult(result) {
            if (!result || typeof OutlineValidation === 'undefined') return;
            const validation = OutlineValidation.validateStructuralOutlineClient(result);
            mountOutlineValidationPanel(validation, { loading: false });
        }

        // --- Display Functions ---
        function displayAnalysis(result) {
            if (!result) return;

            stopOutlineValidationLoading();
            renderOutlineValidationForResult(result);

            // Create main analysis content with editable text blocks
            let html = `<div id="analysis-content">`;
            html += `<h1>${escapeHtml(result.title || 'No Title')}</h1>`;

            html += `<h2>Text Characteristics</h2><div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">`;
            if (result.characteristics) {
                for (const [key, value] of Object.entries(result.characteristics)) {
                    html += `<div class="bg-slate-100 p-3 rounded-lg"><strong class="block text-sm text-slate-600">${escapeHtml(key)}</strong><span class="text-indigo-700 font-semibold">${escapeHtml(String(value))}</span></div>`;
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
                    html += `<span class="bg-indigo-100 text-indigo-800 text-sm font-medium px-3 py-1.5 rounded-full">${escapeHtml(keyword)}</span>`;
                });
                html += `</div>`;
            }

            // --- Editable Text Blocks (replacing image blocks) ---
            if (result.generalAnalysis) {
                const content = formatGeneralAnalysisForImage(result.generalAnalysis);
                html += `<h2>General Analysis</h2>`;
                html += createEditableTextBlock('general', 'General Analysis', content, '#f9fafb');
            }

            if (result.storyAnalysis) {
                const content = formatStoryAnalysisForImage(result.storyAnalysis);
                html += `<h2>Story Analysis</h2>`;
                html += createEditableTextBlock('story', 'Story Analysis', content, '#f0f9ff');
            }

            if (result.dramaAnalysis) {
                const content = formatDramaAnalysisForImage(result.dramaAnalysis);
                html += `<h2>Drama Analysis</h2>`;
                html += createEditableTextBlock('drama', 'Drama Analysis', content, '#fef3c7');
            }

            if (result.poetryAnalysis) {
                const content = formatPoetryAnalysisForImage(result.poetryAnalysis);
                html += `<h2>Poetry Analysis</h2>`;
                html += createEditableTextBlock('poetry', 'Poetry Analysis', content, '#fce7f3');
            }

            if (result.concludingThoughts) {
                const content = formatConclusionForImage(result.concludingThoughts);
                html += `<h2>Lessons & Messages</h2>`;
                html += createEditableTextBlock('conclusion', 'Lessons & Messages', content, '#dcfce7');
            }

            if (result.intertextuality) {
                const content = formatIntertextualityForImage(result.intertextuality);
                html += `<h2>Intertextuality Analysis</h2>`;
                html += createEditableTextBlock('intertextuality', 'Intertextuality Analysis', content, '#ede9fe');
            }

            html += `</div>`; // Close analysis-content

            const existingAnalysis = document.getElementById('analysis-content');
            if (existingAnalysis) {
                existingAnalysis.outerHTML = html;
            } else {
                contentAnalysis.insertAdjacentHTML('beforeend', html);
            }

            // Enable and show tab buttons for slideshow and quiz
            tabSlideshow.classList.remove('hidden');
            tabSlideshow.removeAttribute('disabled');
            tabQuiz.classList.remove('hidden');
            tabQuiz.removeAttribute('disabled');

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

            // Setup global copy tracking on the content area
            setupGlobalCopyTracking();

            // Store original content for comparison
            storeOriginalContent();

            document.getElementById('export-analysis-btn')?.classList.remove('hidden');
            updateChangeStatistics();
            updatePreviewButtonVisibility();

            if (typeof RevisionInterface !== 'undefined') {
                RevisionInterface.showAfterReflection();
            }
            
            // Auto-save to cache
            saveToCache(result);
            
            console.log('✅ Analysis displayed with editable text blocks');
        }

        // === STORE ORIGINAL CONTENT FROM DOM ===
        function storeOriginalContent() {
            originalContent.sections = {};
            Object.keys(behaviorTracker.sections).forEach(sectionId => {
                originalContent.sections[sectionId] = behaviorTracker.sections[sectionId].originalText;
            });
        }

        // === CALCULATE CHANGE PERCENTAGE ===
        function calculateChangePercentage(original, current) {
            if (!original && !current) return 0;
            if (!original) return 100;
            if (original === current) return 0;

            const maxLen = Math.max(original.length, current.length);
            if (maxLen === 0) return 0;

            let changes = 0;
            const minLen = Math.min(original.length, current.length);
            for (let i = 0; i < minLen; i++) {
                if (original[i] !== current[i]) changes++;
            }
            changes += Math.abs(original.length - current.length);
            return Math.min(100, Math.round((changes / maxLen) * 100));
        }

        function getEditedSectionIds() {
            return Object.keys(behaviorTracker.sections).filter(sectionId => {
                const section = behaviorTracker.sections[sectionId];
                return section && section.originalText !== section.currentText;
            });
        }

        function updatePreviewButtonVisibility() {
            const previewBtn = document.getElementById('preview-changes-button');
            if (!previewBtn) return;
            const hasEdits = getEditedSectionIds().length > 0;
            previewBtn.classList.toggle('hidden', !hasEdits);
        }

        // === UPDATE CHANGE STATISTICS DISPLAY ===
        function updateChangeStatistics() {
            const statsContainer = document.getElementById('change-statistics');
            const statsContent = document.getElementById('stats-content');
            if (!statsContainer || !statsContent) return;

            let totalOriginalChars = 0;
            let totalCurrentChars = 0;
            let changedSections = 0;
            let totalSections = 0;

            Object.keys(behaviorTracker.sections).forEach(sectionId => {
                const section = behaviorTracker.sections[sectionId];
                totalSections++;
                totalOriginalChars += section.originalText.length;
                totalCurrentChars += section.currentText.length;

                if (section.originalText !== section.currentText) {
                    changedSections++;
                }
            });

            // Calculate overall change
            let allOriginal = '';
            let allCurrent = '';
            Object.keys(behaviorTracker.sections).forEach(sectionId => {
                const section = behaviorTracker.sections[sectionId];
                allOriginal += section.originalText;
                allCurrent += section.currentText;
            });
            const overallChange = calculateChangePercentage(allOriginal, allCurrent);

            let statsHTML = `
                <div class="flex items-center justify-between p-2 bg-white rounded border border-blue-100">
                    <span class="font-medium text-slate-700">Overall change rate:</span>
                    <div class="flex items-center gap-2">
                        <div class="w-24 h-2 bg-slate-200 rounded-full overflow-hidden">
                            <div class="h-full bg-gradient-to-r from-blue-500 to-purple-500 transition-all" style="width: ${overallChange}%"></div>
                        </div>
                        <span class="font-bold text-${overallChange > 50 ? 'purple' : overallChange > 20 ? 'blue' : 'slate'}-600">${overallChange}%</span>
                    </div>
                </div>
            `;

            if (changedSections > 0) {
                statsHTML += `
                    <div class="flex items-center justify-between p-2 bg-white rounded border border-blue-100">
                        <span class="text-sm text-slate-600">Sections edited:</span>
                        <span class="text-sm font-semibold text-purple-600">${changedSections}/${totalSections} sections</span>
                    </div>
                `;
            }

            const charDiff = totalCurrentChars - totalOriginalChars;
            if (charDiff !== 0) {
                statsHTML += `
                    <div class="flex items-center justify-between p-2 bg-white rounded border border-blue-100">
                        <span class="text-sm text-slate-600">Character change:</span>
                        <span class="text-sm font-semibold ${charDiff > 0 ? 'text-green-600' : 'text-red-600'}">${charDiff > 0 ? '+' : ''}${charDiff} characters</span>
                    </div>
                `;
            }

            // Copy tracking stats
            const globalSummary = behaviorTracker.getGlobalSummary();
            if (globalSummary.totalCopyEvents > 0) {
                statsHTML += `
                    <div class="flex items-center justify-between p-2 bg-white rounded border border-blue-100">
                        <span class="text-sm text-slate-600">Copy count:</span>
                        <span class="text-sm font-semibold text-amber-600">${globalSummary.totalCopyEvents} times (${globalSummary.totalCopyPercent}%)</span>
                    </div>
                `;
            }

            // Average edit time across all save events
            let totalDurationMs = 0, totalEditCount = 0;
            Object.keys(behaviorTracker.sections).forEach(sid => {
                behaviorTracker.sections[sid].editEvents.forEach(ev => {
                    if (ev.editDuration > 0) { totalDurationMs += ev.editDuration; totalEditCount++; }
                });
            });
            if (totalEditCount > 0) {
                const avgMs = Math.round(totalDurationMs / totalEditCount);
                const avgSec = Math.round(avgMs / 1000);
                const m = Math.floor(avgSec / 60), s = avgSec % 60;
                statsHTML += `
                    <div class="flex items-center justify-between p-2 bg-white rounded border border-blue-100">
                        <span class="text-sm text-slate-600">Avg time per edit:</span>
                        <span class="text-sm font-semibold text-indigo-600">${m > 0 ? m + 'p ' : ''}${s}s</span>
                    </div>
                `;
            }

            statsContent.innerHTML = statsHTML;
            statsContainer.classList.remove('hidden');
        }

        // === SHOW PREVIEW MODAL ===
        // === WORD-LEVEL DIFF for preview highlighting ===
        function computeWordDiff(oldText, newText) {
            const oldWords = oldText.split(/(\s+)/);
            const newWords = newText.split(/(\s+)/);

            // LCS (Longest Common Subsequence) table
            const m = oldWords.length, n = newWords.length;
            // Use typed array for speed on large texts
            const dp = Array.from({ length: m + 1 }, () => new Uint16Array(n + 1));

            for (let i = 1; i <= m; i++) {
                for (let j = 1; j <= n; j++) {
                    if (oldWords[i - 1] === newWords[j - 1]) {
                        dp[i][j] = dp[i - 1][j - 1] + 1;
                    } else {
                        dp[i][j] = Math.max(dp[i - 1][j], dp[i][j - 1]);
                    }
                }
            }

            // Back-Quizce to build diff
            const diff = [];
            let i = m, j = n;
            while (i > 0 || j > 0) {
                if (i > 0 && j > 0 && oldWords[i - 1] === newWords[j - 1]) {
                    diff.unshift({ type: 'equal', value: oldWords[i - 1] });
                    i--; j--;
                } else if (j > 0 && (i === 0 || dp[i][j - 1] >= dp[i - 1][j])) {
                    diff.unshift({ type: 'added', value: newWords[j - 1] });
                    j--;
                } else {
                    diff.unshift({ type: 'removed', value: oldWords[i - 1] });
                    i--;
                }
            }
            return diff;
        }

        function renderDiffBefore(diff) {
            return diff
                .filter(d => d.type !== 'added')
                .map(d => d.type === 'removed'
                    ? `<span style="background:#fecaca;text-decoration:line-through;border-radius:2px;padding:0 2px">${escapeHtml(d.value)}</span>`
                    : escapeHtml(d.value))
                .join('');
        }

        function renderDiffAfter(diff) {
            return diff
                .filter(d => d.type !== 'removed')
                .map(d => d.type === 'added'
                    ? `<span style="background:#bbf7d0;border-radius:2px;padding:0 2px">${escapeHtml(d.value)}</span>`
                    : escapeHtml(d.value))
                .join('');
        }

        function renderSectionDiffContent(oldText, newText) {
            const maxWords = 500;
            const oldWordCount = oldText.split(/\s+/).filter(Boolean).length;
            const newWordCount = newText.split(/\s+/).filter(Boolean).length;
            if (oldWordCount > maxWords || newWordCount > maxWords) {
                return {
                    before: escapeHtml(oldText),
                    after: escapeHtml(newText)
                };
            }
            const diff = computeWordDiff(oldText, newText);
            return {
                before: renderDiffBefore(diff),
                after: renderDiffAfter(diff)
            };
        }

        function showPreviewModal() {
            const changedIds = getEditedSectionIds();
            if (changedIds.length === 0) {
                if (typeof showToast === 'function') {
                    showToast('info', 'Edit at least one section to preview changes.');
                }
                updatePreviewButtonVisibility();
                return;
            }

            const sections = behaviorTracker.sections;
            const sectionNames = {
                'general': 'General Analysis',
                'story': 'Story Analysis',
                'drama': 'Drama Analysis',
                'poetry': 'Poetry Analysis',
                'conclusion': 'Lessons & Messages',
                'intertextuality': 'Intertextuality'
            };

            let beforeHTML = '';
            let afterHTML = '';

            changedIds.forEach(sectionId => {
                const section = sections[sectionId];
                if (!section) return;
                const displayName = sectionNames[sectionId] || sectionId;
                const diffContent = renderSectionDiffContent(section.originalText, section.currentText);

                beforeHTML += `<div class="bg-red-50 border-red-200 border rounded-lg p-4">
                    <div class="text-xs font-semibold text-red-600 mb-2">${escapeHtml(displayName)}</div>
                    <div class="text-sm whitespace-pre-wrap max-h-60 overflow-y-auto">${diffContent.before}</div>
                </div>`;

                afterHTML += `<div class="bg-green-50 border-green-200 border rounded-lg p-4">
                    <div class="text-xs font-semibold text-green-600 mb-2">${escapeHtml(displayName)}</div>
                    <div class="text-sm whitespace-pre-wrap max-h-60 overflow-y-auto">${diffContent.after}</div>
                </div>`;
            });

            const comparisonHTML = `
                <p class="text-sm text-slate-600 mb-4">${changedIds.length} edited section(s). <span class="text-red-600">Red</span> = removed, <span class="text-green-600">green</span> = added.</p>
                <div class="genai-preview-grid">
                    <div>
                        <h3 class="text-lg font-bold text-red-700 mb-4 flex items-center">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="mr-2">
                                <circle cx="12" cy="12" r="10"></circle>
                                <line x1="15" y1="9" x2="9" y2="15"></line>
                                <line x1="9" y1="9" x2="15" y2="15"></line>
                            </svg>
                            Before Editing
                        </h3>
                        <div class="space-y-4">${beforeHTML}</div>
                    </div>
                    <div>
                        <h3 class="text-lg font-bold text-green-700 mb-4 flex items-center">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="mr-2">
                                <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                                <polyline points="22 4 12 14.01 9 11.01"></polyline>
                            </svg>
                            After Editing
                        </h3>
                        <div class="space-y-4">${afterHTML}</div>
                    </div>
                </div>
            `;

            const modal = document.getElementById('preview-modal');
            const modalBody = document.getElementById('preview-modal-body');
            if (modal && modalBody) {
                modalBody.innerHTML = comparisonHTML;
                modal.classList.remove('hidden');
                document.body.style.overflow = 'hidden';
            }
        }

        // === CLOSE PREVIEW MODAL ===
        function closePreviewModal() {
            const modal = document.getElementById('preview-modal');
            if (modal) {
                modal.classList.add('hidden');
                document.body.style.overflow = '';
            }
        }

        window.showPreviewModal = showPreviewModal;
        window.closePreviewModal = closePreviewModal;

        async function clearHistoryFromHint() {
            if (!confirm('Delete all previous analysis history for this task?')) {
                return;
            }
            historySuppressed = true;
            currentAnalysisData = null;
            isAnalyzed = false;
            currentResponseId = null;
            await clearAllWorkHistory();
            hideHistoryUI();
        }
        window.clearHistoryFromHint = clearHistoryFromHint;

        // === HISTORY PANEL & MODAL ===
        function mergeHistoryLists(serverRows, localRows) {
            const merged = [];
            const seen = new Set();

            (serverRows || []).forEach((row) => {
                const id = parseInt(row.id, 10);
                merged.push({
                    id: id,
                    serverId: id,
                    label: row.label || row.created_at || 'Version',
                    timestamp: row.created_at || '',
                    analysisData: row.analysis_data || row.analysisData,
                    isConfirmed: !!row.is_confirmed,
                    source: 'server'
                });
                seen.add(id);
            });

            (localRows || []).forEach((entry) => {
                const sid = entry.serverId ? parseInt(entry.serverId, 10) : null;
                if (sid && seen.has(sid)) return;
                merged.push({
                    id: entry.id,
                    serverId: sid,
                    label: entry.label,
                    timestamp: entry.timestamp || '',
                    analysisData: entry.analysisData,
                    isConfirmed: confirmedHistoryId && sid === confirmedHistoryId,
                    source: 'local'
                });
            });

            return merged.sort((a, b) => {
                const ta = new Date(a.timestamp || 0).getTime();
                const tb = new Date(b.timestamp || 0).getTime();
                return tb - ta;
            });
        }

        function updateSubmissionStatusBanner() {
            const banner = document.getElementById('submission-status-banner');
            if (!banner) return;
            if (deliverStatus >= 2 && confirmedHistoryId) {
                banner.className = 'mt-3 p-3 rounded-lg border border-green-200 bg-green-50 text-sm text-green-800';
                banner.innerHTML = '<i class="bi bi-check-circle-fill me-1"></i> <strong>Final submission confirmed.</strong> Your teacher will only see the confirmed version.';
                banner.classList.remove('hidden');
            } else if (historyEntries.length > 0) {
                banner.className = 'mt-3 p-3 rounded-lg border border-amber-200 bg-amber-50 text-sm text-amber-800';
                banner.innerHTML = '<i class="bi bi-info-circle me-1"></i> Choose a version below and click <strong>Confirm as final</strong> so your teacher can view your work.';
                banner.classList.remove('hidden');
            } else {
                banner.classList.add('hidden');
            }
        }

        async function renderHistoryPanel() {
            if (historySuppressed) {
                hideHistoryUI();
                return;
            }

            const panel = document.getElementById('history-panel');
            const reviewBtn = document.getElementById('review-work-btn');
            const hint = document.getElementById('previous-work-hint');
            const hintLabel = document.getElementById('previous-work-label');

            let serverMeta = { history: [], deliverStatus: deliverStatus, confirmedHistoryId: confirmedHistoryId };
            if (window.GenAITools && TASK_ID && DELIVER_ID) {
                serverMeta = await GenAITools.loadServerHistory(TASK_ID, DELIVER_ID);
                deliverStatus = serverMeta.deliverStatus ?? deliverStatus;
                confirmedHistoryId = serverMeta.confirmedHistoryId ?? confirmedHistoryId;
            }

            const local = loadHistory();
            historyEntries = mergeHistoryLists(serverMeta.history, local);

            // Drop stale browser history after teacher reset or when no server submission exists
            if ((serverMeta.history || []).length === 0 && deliverStatus === 0 && !currentResponseId) {
                if (local.length > 0) {
                    clearLocalHistory();
                    historyEntries = [];
                }
            }

            if (historyEntries.length === 0) {
                if (panel) panel.classList.add('hidden');
                if (reviewBtn) reviewBtn.classList.add('hidden');
                if (hint) hint.classList.add('hidden');
                updateSubmissionStatusBanner();
                return;
            }

            if (reviewBtn) {
                reviewBtn.classList.remove('hidden');
                reviewBtn.onclick = () => openMostRecentHistory();
            }

            if (hint) {
                hint.classList.remove('hidden');
                const confirmed = historyEntries.find(h => h.isConfirmed);
                if (hintLabel) {
                    hintLabel.textContent = confirmed
                        ? `Final: ${confirmed.label}`
                        : `Latest: ${historyEntries[0].label}`;
                }
            }

            if (panel) {
                panel.classList.remove('hidden');
                const list = document.getElementById('history-list');
                if (list) {
                    list.innerHTML = historyEntries.map((entry) => {
                        const entryKey = entry.serverId || entry.id;
                        const confirmedBadge = entry.isConfirmed
                            ? '<span class="text-[10px] px-1.5 py-0.5 rounded bg-green-100 text-green-700 font-semibold uppercase">Final</span>'
                            : '';
                        return `
                        <div class="flex items-center justify-between gap-2 px-3 py-2 rounded-lg bg-white border ${entry.isConfirmed ? 'border-green-300' : 'border-slate-100'} hover:border-indigo-200 transition-colors">
                            <span class="text-xs text-slate-600 truncate flex-1 flex items-center gap-1">${confirmedBadge}<span>${escapeHtml(entry.label)}</span></span>
                            <div class="flex gap-1 shrink-0 flex-wrap justify-end">
                                <button onclick="showHistoryModal('${entryKey}')"
                                    class="text-xs px-2 py-1 rounded bg-indigo-50 text-indigo-600 hover:bg-indigo-100 transition-colors font-medium">
                                    View
                                </button>
                                ${entry.isConfirmed ? '' : `<button onclick="confirmHistoryEntry('${entryKey}')"
                                    class="text-xs px-2 py-1 rounded bg-green-50 text-green-700 hover:bg-green-100 transition-colors font-medium">
                                    Confirm
                                </button>`}
                                ${entry.source === 'local' && !entry.serverId ? `<button onclick="deleteHistoryEntry(${entry.id})"
                                    class="text-xs px-2 py-1 rounded bg-red-50 text-red-500 hover:bg-red-100 transition-colors font-medium">
                                    Delete
                                </button>` : ''}
                            </div>
                        </div>`;
                    }).join('');
                }
            }
            updateSubmissionStatusBanner();
        }

        function findHistoryEntry(entryKey) {
            const key = String(entryKey);
            return historyEntries.find(h => String(h.serverId || h.id) === key)
                || loadHistory().find(h => String(h.serverId || h.id) === key);
        }

        function openMostRecentHistory() {
            if (historyEntries.length > 0) {
                const entry = historyEntries[0];
                showHistoryModal(entry.serverId || entry.id);
            }
        }

        function toggleHistoryPanel() {
            const list = document.getElementById('history-list');
            const chevron = document.getElementById('history-chevron');
            const isHidden = list.classList.contains('hidden');
            list.classList.toggle('hidden', !isHidden);
            if (chevron) chevron.style.transform = isHidden ? 'rotate(180deg)' : '';
        }

        let activeHistoryEntryKey = null;

        function showHistoryModal(entryKey) {
            const entry = findHistoryEntry(entryKey);
            if (!entry) return;

            activeHistoryEntryKey = String(entry.serverId || entry.id);
            const modal = document.getElementById('history-modal');
            const body = document.getElementById('history-modal-body');
            const title = document.getElementById('history-modal-title');
            const confirmBtn = document.getElementById('history-confirm-btn');
            const restoreBtn = document.getElementById('history-restore-btn');

            const confirmedTag = entry.isConfirmed
                ? ' <span class="text-xs px-2 py-0.5 rounded bg-green-100 text-green-700 font-semibold">FINAL</span>'
                : '';

            title.innerHTML = `
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-indigo-600">
                    <circle cx="12" cy="12" r="10"></circle>
                    <polyline points="12 6 12 12 16 14"></polyline>
                </svg>
                Analysis History — <span class="text-indigo-600">${escapeHtml(entry.label)}</span>${confirmedTag}
            `;

            if (confirmBtn) {
                confirmBtn.classList.toggle('hidden', !!entry.isConfirmed);
                confirmBtn.onclick = () => confirmHistoryEntry(activeHistoryEntryKey);
            }
            if (restoreBtn) {
                restoreBtn.onclick = () => restoreHistoryToEditor(entry);
            }

            const data = entry.analysisData;
            const sectionDefs = [
                { key: 'generalAnalysis', label: 'General Analysis' },
                { key: 'storyAnalysis', label: 'Story Analysis' },
                { key: 'dramaAnalysis', label: 'Drama Analysis' },
                { key: 'poetryAnalysis', label: 'Poetry Analysis' },
                { key: 'concludingThoughts', label: 'Lesson & Message' },
                { key: 'intertextuality', label: 'Intertextuality' }
            ];

            let html = '';
            if (data.title) {
                html += `<div class="p-3 bg-slate-50 rounded-lg border border-slate-200 mb-2">
                    <span class="text-sm font-semibold text-slate-700">${escapeHtml(data.title)}</span>
                </div>`;
            }

            sectionDefs.forEach(({ key, label }) => {
                const raw = data[key];
                if (!raw) return;
                const text = (typeof raw === 'string') ? raw : JSON.stringify(raw, null, 2);
                html += `
                    <div class="p-4 bg-white rounded-lg border border-slate-200">
                        <h4 class="text-sm font-bold text-slate-700 mb-2 flex items-center gap-1">
                            <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path></svg>
                            ${label}
                        </h4>
                        <p class="text-sm text-slate-600 whitespace-pre-wrap leading-relaxed">${escapeHtml(text.substring(0, 800))}${text.length > 800 ? '…' : ''}</p>
                    </div>
                `;
            });

            if (data._editedSections && Object.keys(data._editedSections).length > 0) {
                html += `<div class="p-3 bg-amber-50 rounded-lg border border-amber-200 text-xs text-amber-700 font-medium">
                    ✏️ This analysis has ${Object.keys(data._editedSections).length} sections have been edited.
                </div>`;
            }

            body.innerHTML = html || '<p class="text-sm text-slate-500 text-center py-8">No data.</p>';
            modal.classList.remove('hidden');
            document.body.style.overflow = 'hidden';
        }

        function closeHistoryModal() {
            const modal = document.getElementById('history-modal');
            if (modal) {
                modal.classList.add('hidden');
                document.body.style.overflow = '';
            }
        }

        function deleteHistoryEntry(entryId) {
            try {
                const raw = localStorage.getItem(HISTORY_KEY);
                if (!raw) return;
                const history = JSON.parse(raw).filter(h => h.id !== entryId);
                localStorage.setItem(HISTORY_KEY, JSON.stringify(history));
                renderHistoryPanel();
            } catch(e) { console.warn('Delete history entry failed', e); }
        }

        async function confirmHistoryEntry(entryKey) {
            const entry = findHistoryEntry(entryKey);
            if (!entry) return;

            const msg = 'Confirm this version as your final submission? Your teacher will only see this version.';
            if (!confirm(msg)) return;

            try {
                const historyId = entry.serverId || (entry.source === 'server' ? entry.id : null);
                const result = await GenAITools.confirmVersion(
                    TASK_ID,
                    DELIVER_ID,
                    historyId,
                    historyId ? null : entry.analysisData
                );

                if (result.success) {
                    deliverStatus = 2;
                    confirmedHistoryId = result.data?.history_id || historyId;
                    if (result.data?.response_id) {
                        currentResponseId = result.data.response_id;
                    }
                    if (typeof showToast === 'function') {
                        showToast('success', result.message || 'Final submission confirmed.');
                    }
                    closeHistoryModal();
                    await renderHistoryPanel();
                } else if (typeof showToast === 'function') {
                    showToast('error', result.message || 'Could not confirm submission.');
                } else {
                    alert(result.message || 'Could not confirm submission.');
                }
            } catch (e) {
                console.error('Confirm failed', e);
                if (typeof showToast === 'function') {
                    showToast('error', 'Could not confirm submission.');
                }
            }
        }

        function restoreHistoryToEditor(entry) {
            if (!entry?.analysisData) return;
            currentAnalysisData = JSON.parse(JSON.stringify(entry.analysisData));
            isAnalyzed = true;
            displayAnalysis(currentAnalysisData);
            setupGeneratorTabs();
            placeholder.classList.add('hidden');
            resultsTabs.classList.remove('hidden');
            const pwHint = document.getElementById('previous-work-hint');
            if (pwHint) pwHint.classList.add('hidden');
            switchTab(tabAnalysis);
            switchToChatMode();
            saveToCache(currentAnalysisData);
            closeHistoryModal();
            if (typeof showToast === 'function') {
                showToast('info', 'Version loaded into editor. Confirm when ready for your teacher.');
            }
        }

        // === SETUP GLOBAL COPY TRACKING ===
        function setupGlobalCopyTracking() {
            const contentArea = document.getElementById('content-analysis');
            if (!contentArea) return;

            contentArea.addEventListener('copy', (e) => {
                const selection = window.getSelection().toString();
                if (selection && selection.length > 10) {
                    // Try to determine which section the copy is from
                    const anforrNode = window.getSelection().anforrNode;
                    if (anforrNode) {
                        const wrapper = anforrNode.parentElement?.closest('.content-text-wrapper');
                        if (wrapper) {
                            const sectionId = wrapper.dataset.section;
                            behaviorTracker.trackCopy(sectionId, selection);
                        } else {
                            // General copy event
                            behaviorTracker.globalStats.totalCopyEvents++;
                            behaviorTracker.globalStats.totalCopiedChars += selection.length;
                            behaviorTracker.showTrackingIndicator(`Copied ${selection.length} characters`);
                        }
                    }
                }
            });
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
                    <h3 class="text-xl font-bold mb-4 text-indigo-700">${index + 1}. ${escapeHtml(slide.heading || '')}</h3>
                    <div class="prose max-w-none">${formatMarkdown(slide.content || '')}</div>
                </div>`;
            });
            slideHTML += `</div>`;

            const totalSlides = slides.length;
            contentSlideshow.innerHTML = `
                <div class="mb-4">
                    <h2 class="text-2xl font-bold">${currentAnalysisData.title || 'Presentation'}</h2>
                    <p class="text-slate-600">Summary presentation of the analysis content.</p>
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
                quizHTML += `<div class="question-block"><p class="font-semibold mb-2">${index + 1}. ${escapeHtml(item.q || '')}</p><div class="space-y-2">`;
                item.o.forEach((option, optionIndex) => {
                    quizHTML += `<div><label class="flex items-center space-x-3 p-3 border rounded-lg hover:bg-slate-50 cursor-pointer">
                                <input type="radio" name="question-${index}" value="${optionIndex}" class="h-4 w-4 text-indigo-600 border-slate-300 focus:ring-indigo-500">
                                <span>${escapeHtml(option)}</span></label></div>`;
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
                resultsDiv.textContent = `Your score: ${score} / ${result.quiz.length}`;
                resultsDiv.style.color = score / result.quiz.length > 0.5 ? 'green' : 'red';
            });
        }
    </script>

    <!-- History Modal -->
    <div id="history-modal" class="hidden fixed inset-0 z-[1000] overflow-y-auto" role="dialog" aria-modal="true">
        <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true" onclick="closeHistoryModal()"></div>
            <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-4xl sm:w-full">
                <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                    <div class="flex justify-between items-center mb-4 pb-4 border-b">
                        <h3 id="history-modal-title" class="text-xl font-bold text-slate-800 flex items-center gap-2">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-indigo-600">
                                <circle cx="12" cy="12" r="10"></circle>
                                <polyline points="12 6 12 12 16 14"></polyline>
                            </svg>
                            Review Analysis
                        </h3>
                        <button type="button" class="text-gray-400 hover:text-gray-500" onclick="closeHistoryModal()">
                            <svg class="h-6 w-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                    <div id="history-modal-body" class="max-h-[70vh] overflow-y-auto space-y-4">
                        <!-- Populated by showHistoryModal() -->
                    </div>
                </div>
                <div class="bg-gray-50 px-4 py-3 sm:px-6 flex flex-wrap justify-end gap-2">
                    <button type="button" id="history-restore-btn"
                        class="inline-flex justify-center rounded-md border border-slate-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-slate-700 hover:bg-slate-50 sm:text-sm">
                        Load to editor
                    </button>
                    <button type="button" id="history-confirm-btn"
                        class="inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-green-600 text-base font-medium text-white hover:bg-green-700 sm:text-sm">
                        Confirm as final
                    </button>
                    <button type="button" onclick="closeHistoryModal()"
                        class="inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-indigo-600 text-base font-medium text-white hover:bg-indigo-700 sm:text-sm">
                        Close
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div id="preview-modal" class="hidden genai-modal" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="genai-modal__backdrop" aria-hidden="true" onclick="closePreviewModal()"></div>
        <div class="flex items-start justify-center min-h-screen pt-4 px-4 pb-20">
            <div class="genai-modal__panel text-left overflow-hidden">
                <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                    <!-- Modal Header -->
                    <div class="flex justify-between items-center mb-6 pb-4 border-b">
                        <h3 class="text-2xl font-bold text-slate-800 flex items-center" id="modal-title">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="mr-3 text-purple-600">
                                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                <circle cx="12" cy="12" r="3"></circle>
                            </svg>
                            Before &amp; After Edit
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
                        Close
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
</html>