<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php require_once __DIR__ . '/../templates/assets.php'; ?>
    <title data-lang-key="assign_task">Assign Task - Literature System</title>
</head>

<body>
    <?php
    require_once __DIR__ . '/../../config/db.php';
    require_once __DIR__ . '/../../config/helper_task.php';
    require_once __DIR__ . '/../../config/helper_phase.php';
    require_once __DIR__ . '/../../config/helper_class.php';
    require_once __DIR__ . '/../templates/header.php';
    require_once __DIR__ . '/../templates/sidebar.php';
    


    // Get IDs from URL
    $taskId = isset($_GET['task_id']) ? intval($_GET['task_id']) : 0;
    $phaseId = isset($_GET['phase_id']) ? intval($_GET['phase_id']) : 0;
    $topicId = isset($_GET['topic_id']) ? intval($_GET['topic_id']) : 0;

    if (!$taskId || !$phaseId || !$topicId) {
        header('Location: /class');
        exit;
    }

    // Get task details
    $task = getTaskDetails($taskId);
    if (!$task) {
        header('Location: /class');
        exit;
    }

    // Get phase details
    $phase = getPhaseDetails($phaseId);
    if (!$phase) {
        header('Location: /class');
        exit;
    }

    // Get topic details
    $topic = getTopicDetails($topicId);
    if (!$topic) {
        header('Location: /class');
        exit;
    }

    // Check if user is teacher
    $isTeacher = isTeacherInTopicClass($topicId, $_SESSION['user']['user_id']);
    if (!$isTeacher) {
        header('Location: /class');
        exit;
    }

    // Get class_id from topic
    $classId = $topic['class_id'];

    // Get existing assignments
    $existingAssignments = getTaskAssignments($taskId);

    // Get class students, groups
    $classStudents = getClassStudents($classId);
    $classGroups = getClassGroupsForTask($classId);

    // Get members for each group
    $groupMembers = [];
    foreach ($classGroups as $group) {
        $groupDetails = getGroupDetails($group['group_id']);
        if ($groupDetails && isset($groupDetails['members'])) {
            $groupMembers[$group['group_id']] = $groupDetails['members'];
        }
    }
    ?>

    <main id="main" class="main">
        <div class="pagetitle">
            <h1 data-lang-key="assign_task">Assign Task</h1>
            <nav>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="/class"><?= $_SESSION['current_class_name']; ?></a></li>
                    <li class="breadcrumb-item"><a href="/class"><?= htmlspecialchars($topic['topic_name']) ?></a></li>
                    <li class="breadcrumb-item"><a href="/class/phases?topic_id=<?= $topicId ?>" data-lang-key="learning_phases">Learning Phases</a></li>
                    <li class="breadcrumb-item"><a
                            href="/class/task?topic_id=<?= $topicId ?>&phase_id=<?= $phaseId ?>" data-lang-key="tasks">Tasks</a></li>
                    <li class="breadcrumb-item active" data-lang-key="assign_task">Assign Task</li>
                </ol>
            </nav>
        </div><!-- End Page Title -->

        <section class="section assign-page">
            <div class="row">
                <!-- Assignment Form -->
                <div class="col-lg-8">
                    <div class="card">
                        <div class="card-body">
                            <div class="mb-3">
                                <a href="/class/task/view?task_id=<?= $taskId ?>&topic_id=<?= $topicId ?>&phase_id=<?= $phaseId ?>"
                                    class="btn btn-outline-secondary btn-sm">
                                    <i class="bi bi-arrow-left"></i> <span data-lang-key="back_to_task_details">Back to Task Details</span>
                                </a>
                            </div>
                            <h5 class="card-title">
                                <i class="bi bi-bell"></i> Assign Task: <?= htmlspecialchars($task['title']) ?>
                            </h5>

                            <form id="assignTaskForm">
                                <input type="hidden" name="task_id" value="<?= $taskId ?>">
                                <input type="hidden" name="topic_id" value="<?= $topicId ?>">
                                <input type="hidden" name="phase_id" value="<?= $phaseId ?>">

                                <!-- Assignment Type Selection -->
                                <div class="mb-4">
                                    <label class="form-label">Assignment Type <span
                                            class="text-danger">*</span></label>
                                    <div class="btn-group w-100" role="group">
                                        <input type="radio" class="btn-check" name="assign_type" id="assignTypeGroup"
                                            value="2" checked autocomplete="off">
                                        <label class="btn btn-outline-primary" for="assignTypeGroup">
                                            <i class="bi bi-diagram-3-fill"></i> <span>Group</span>
                                        </label>

                                        <input type="radio" class="btn-check" name="assign_type"
                                            id="assignTypeIndividual" value="3" autocomplete="off">
                                        <label class="btn btn-outline-primary" for="assignTypeIndividual">
                                            <i class="bi bi-person-fill"></i> <span>Individual</span>
                                        </label>
                                    </div>
                                </div>

                                <!-- Group Assignment -->
                                <div id="assignGroupArea">
                                    <label class="form-label">Select Group</label>
                                    <?php if (empty($classGroups)): ?>
                                        <div class="alert alert-warning">
                                            <i class="bi bi-exclamation-triangle"></i>
                                            No groups in this class yet.
                                        </div>
                                    <?php else: ?>
                                        <div style="max-height: 500px; overflow-y: auto;">
                                            <?php foreach ($classGroups as $group): ?>
                                                <div class="card mb-3">
                                                    <div class="card-body p-3">
                                                        <label class="d-flex align-items-center mb-0 cursor-pointer">
                                                            <input class="form-check-input me-2 group-checkbox"
                                                                type="checkbox"
                                                                name="group_values[]"
                                                                value="<?= $group['group_id'] ?>"
                                                                data-group-id="<?= $group['group_id'] ?>">
                                                            <div class="flex-grow-1">
                                                                <i class="bi bi-diagram-3"></i>
                                                                <strong><?= htmlspecialchars($group['group_name']) ?></strong>
                                                                <span class="badge bg-secondary ms-2"><?= $group['member_count'] ?> members</span>
                                                            </div>
                                                        </label>

                                                        <!-- Member list (hidden by default) -->
                                                        <div class="group-members mt-2 ms-4"
                                                            id="members-<?= $group['group_id'] ?>"
                                                            style="display: none;">
                                                            <div class="border-start border-2 border-primary ps-3">
                                                                <small class="text-muted d-block mb-2">
                                                                    <i class="bi bi-people"></i> Group members:
                                                                </small>
                                                                <?php if (!empty($groupMembers[$group['group_id']])): ?>
                                                                    <div class="list-group list-group-flush">
                                                                        <?php foreach ($groupMembers[$group['group_id']] as $member): ?>
                                                                            <div class="list-group-item px-2 py-1 bg-light border-0">
                                                                                <i class="bi bi-person-fill text-primary"></i>
                                                                                <small>
                                                                                    <?= htmlspecialchars($member['fullname']) ?>
                                                                                    <span class="text-muted">(<?= htmlspecialchars($member['username']) ?>)</span>
                                                                                </small>
                                                                            </div>
                                                                        <?php endforeach; ?>
                                                                    </div>
                                                                <?php else: ?>
                                                                    <small class="text-muted">
                                                                        <i class="bi bi-info-circle"></i> No members yet
                                                                    </small>
                                                                <?php endif; ?>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php endif; ?>
                                </div>

                                <!-- Individual Assignment -->
                                <div id="assignIndividualArea" style="display: none;">
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <label class="form-label mb-0">Select Student</label>
                                        <?php if (!empty($classStudents)): ?>
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" id="selectAllStudents">
                                                <label class="form-check-label" for="selectAllStudents">
                                                    Select all
                                                </label>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    <?php if (empty($classStudents)): ?>
                                        <div class="alert alert-warning">
                                            <i class="bi bi-exclamation-triangle"></i>
                                            No students in this class yet.
                                        </div>
                                    <?php else: ?>
                                        <div class="mb-3">
                                            <input type="text" class="form-control" id="searchStudent"
                                                placeholder="Search students...">
                                        </div>
                                        <div class="list-group" id="studentList"
                                            style="max-height: 400px; overflow-y: auto;">
                                            <?php foreach ($classStudents as $student): ?>
                                                <label class="list-group-item student-item"
                                                    data-name="<?= strtolower(htmlspecialchars($student['fullname'])) ?>"
                                                    data-username="<?= strtolower(htmlspecialchars($student['username'])) ?>">
                                                    <input class="form-check-input me-2 student-checkbox" type="checkbox"
                                                        name="individual_values[]" value="<?= $student['user_id'] ?>">
                                                    <i class="bi bi-person"></i>
                                                    <?= htmlspecialchars($student['fullname']) ?>
                                                    <small
                                                        class="text-muted">(<?= htmlspecialchars($student['username']) ?>)</small>
                                                </label>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php endif; ?>
                                </div>

                                <!-- Buttons -->
                                <div class="d-flex gap-2 mt-4">
                                    <button type="submit" class="btn btn-primary" id="submitBtn">
                                        <i class="bi bi-check-circle"></i> <span data-lang-key="assign_task">Assign Task</span>
                                    </button>
                                    <a href="/class/task?topic_id=<?= $topicId ?>&phase_id=<?= $phaseId ?>"
                                        class="btn btn-secondary">
                                        <i class="bi bi-x-circle"></i> <span>Cancel</span>
                                    </a>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Existing Assignments -->
                <div class="col-lg-4">
                    <div class="card">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h5 class="card-title mb-0" data-lang-key="assigned_list_title">Assigned to</h5>
                                <button type="button"
                                    id="deleteSelectedBtn"
                                    class="btn btn-sm btn-danger"
                                    style="display: none;">
                                    <i class="bi bi-trash"></i> Delete selected (<span id="selectedCount">0</span>)
                                </button>
                            </div>

                            <?php if (empty($existingAssignments)): ?>
                                <div class="alert alert-info">
                                    <i class="bi bi-info-circle"></i>
                                    Not assigned to anyone yet.
                                </div>
                            <?php else: ?>
                                <div class="list-group">
                                    <?php foreach ($existingAssignments as $assignment): ?>
                                        <div class="list-group-item assignment-item" data-deliver-id="<?= $assignment['id_deliver'] ?>">
                                            <div class="d-flex align-items-center">
                                                <input class="form-check-input me-2 assignment-checkbox"
                                                    type="checkbox"
                                                    id="assign-<?= $assignment['id_deliver'] ?>"
                                                    data-deliver-id="<?= $assignment['id_deliver'] ?>">
                                                <label class="form-check-label flex-grow-1" for="assign-<?= $assignment['id_deliver'] ?>">
                                                    <div>
                                                        <?php if ($assignment['type'] == 'class'): ?>
                                                            <i class="bi bi-people-fill text-primary"></i>
                                                            <strong>Whole Class:</strong>
                                                        <?php elseif ($assignment['type'] == 'group'): ?>
                                                            <i class="bi bi-diagram-3-fill text-success"></i>
                                                            <strong>Group:</strong>
                                                        <?php else: ?>
                                                            <i class="bi bi-person-fill text-info"></i>
                                                            <strong>Individual:</strong>
                                                        <?php endif; ?>
                                                        <br>
                                                        <small><?= htmlspecialchars($assignment['assigned_to_name'] ?? '') ?></small>
                                                    </div>
                                                </label>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Task Info -->
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title">Task Information</h5>
                            <p><strong>Title:</strong> <?= htmlspecialchars($task['title'] ?? '') ?></p>
                            <p><strong>Phase:</strong> Step <?= htmlspecialchars($phase['phase_number'] ?? '') ?> -
                                <?= htmlspecialchars($phase['phase_name'] ?? '') ?></p>
                            <p><strong>Topic:</strong> <?= htmlspecialchars($topic['topic_name'] ?? '') ?></p>
                        </div>
                    </div>
                </div>
            </div>
        </section>

    </main><!-- End #main -->

    <!-- Toast Container -->
    <div class="position-fixed top-0 end-0 p-3" style="z-index: 1100; margin-top: 60px;">
        <div id="toastNotification" class="toast" role="alert" aria-live="assertive" aria-atomic="true">
            <div class="toast-header">
                <i class="bi me-2" id="toastIcon"></i>
                <strong class="me-auto" id="toastTitle">Notification</strong>
                <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
            <div class="toast-body" id="toastMessage">
                Notification content
            </div>
        </div>
    </div>

    <?php
    require_once __DIR__ . '/../templates/footer.php';
    require_once __DIR__ . '/../templates/vendors.php';
    ?>

    <script>
        (function() {
            'use strict';

            // Toast utility function
            function showToast(message, type = 'success') {
                const toastEl = document.getElementById('toastNotification');
                const toastIcon = document.getElementById('toastIcon');
                const toastTitle = document.getElementById('toastTitle');
                const toastMessage = document.getElementById('toastMessage');
                const toastHeader = toastEl.querySelector('.toast-header');

                toastHeader.classList.remove('bg-success', 'bg-danger', 'bg-warning', 'bg-info', 'text-white');
                toastIcon.className = 'bi me-2';

                switch (type) {
                    case 'success':
                        toastHeader.classList.add('bg-success', 'text-white');
                        toastIcon.classList.add('bi-check-circle-fill');
                        toastTitle.textContent = 'Success';
                        break;
                    case 'error':
                        toastHeader.classList.add('bg-danger', 'text-white');
                        toastIcon.classList.add('bi-x-circle-fill');
                        toastTitle.textContent = 'Error';
                        break;
                    case 'warning':
                        toastHeader.classList.add('bg-warning', 'text-dark');
                        toastIcon.classList.add('bi-exclamation-triangle-fill');
                        toastTitle.textContent = 'Warning';
                        break;
                    case 'info':
                        toastHeader.classList.add('bg-info', 'text-white');
                        toastIcon.classList.add('bi-info-circle-fill');
                        toastTitle.textContent = 'Info';
                        break;
                }

                toastMessage.textContent = message;

                const toast = new bootstrap.Toast(toastEl, {
                    autohide: true,
                    delay: 3000
                });
                toast.show();
            }

            // Conflict warning alert
            let conflictAlert = null;

            // Function to show conflict warning
            function showConflictWarning(message) {
                // Remove existing alert if any
                if (conflictAlert) {
                    conflictAlert.remove();
                }

                // Create new alert
                conflictAlert = document.createElement('div');
                conflictAlert.className = 'alert alert-danger alert-dismissible fade show';
                conflictAlert.innerHTML = `
                    <i class="bi bi-exclamation-triangle-fill"></i>
                    <strong>Cannot assign!</strong>
                    <p class="mb-0 mt-2">${message}</p>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                `;

                // Insert before the assignment type selection
                const form = document.getElementById('assignTaskForm');
                form.insertBefore(conflictAlert, form.firstChild);

                // Disable submit button
                const submitBtn = document.getElementById('submitBtn');
                submitBtn.disabled = true;
                submitBtn.classList.add('disabled');
                submitBtn.innerHTML = '<i class="bi bi-exclamation-circle"></i> Cannot assign (Conflict)';
            }

            // Function to hide conflict warning
            function hideConflictWarning() {
                if (conflictAlert) {
                    conflictAlert.remove();
                    conflictAlert = null;
                }

                // Enable submit button
                const submitBtn = document.getElementById('submitBtn');
                submitBtn.disabled = false;
                submitBtn.classList.remove('disabled');
                submitBtn.innerHTML = '<i class="bi bi-check-circle"></i> Assign Task';
            }

            // Function to check conflict for current selection
            async function checkCurrentConflict() {
                const assignType = document.querySelector('input[name="assign_type"]:checked').value;
                const taskId = <?= $taskId ?>;

                hideConflictWarning();

                // For class assignment - check immediately
                if (assignType === '1') {
                    const classId = <?= $classId ?>;
                    const response = await fetch(`/api/manage_tasks.php?action=check_assignment_conflict&task_id=${taskId}&assign_type=1&assign_value=${classId}`);
                    const data = await response.json();

                    if (data.success && data.has_conflict) {
                        showConflictWarning(data.message);
                    }
                }
                // For group/individual - check when selection changes
            }

            // Toggle assignment areas
            const assignTypeRadios = document.getElementsByName('assign_type');
            const assignGroupArea = document.getElementById('assignGroupArea');
            const assignIndividualArea = document.getElementById('assignIndividualArea');

            assignTypeRadios.forEach(radio => {
                radio.addEventListener('change', function() {
                    assignGroupArea.style.display = 'none';
                    assignIndividualArea.style.display = 'none';

                    if (this.value === '2') {
                        assignGroupArea.style.display = 'block';
                        hideConflictWarning();
                    } else if (this.value === '3') {
                        assignIndividualArea.style.display = 'block';
                        hideConflictWarning();
                    }
                });
            });

            // Toggle group members display when checkbox is checked/unchecked
            const groupCheckboxes = document.querySelectorAll('.group-checkbox');
            groupCheckboxes.forEach(checkbox => {
                checkbox.addEventListener('change', async function() {
                    const groupId = this.dataset.groupId;
                    const membersDiv = document.getElementById('members-' + groupId);

                    if (membersDiv) {
                        if (this.checked) {
                            membersDiv.style.display = 'block';

                            // Check conflict for this group
                            const taskId = <?= $taskId ?>;
                            const response = await fetch(`/api/manage_tasks.php?action=check_assignment_conflict&task_id=${taskId}&assign_type=2&assign_value=${groupId}`);
                            const data = await response.json();

                            if (data.success && data.has_conflict) {
                                showConflictWarning(data.message);
                                // Uncheck the checkbox
                                this.checked = false;
                                membersDiv.style.display = 'none';
                            } else {
                                hideConflictWarning();
                            }
                        } else {
                            membersDiv.style.display = 'none';
                            // Check if any other group is still checked
                            const anyChecked = document.querySelector('.group-checkbox:checked');
                            if (!anyChecked) {
                                hideConflictWarning();
                            }
                        }
                    }
                });
            });

            // Select all students functionality
            const selectAllStudents = document.getElementById('selectAllStudents');
            if (selectAllStudents) {
                selectAllStudents.addEventListener('change', function() {
                    const studentCheckboxes = document.querySelectorAll('.student-checkbox');
                    const visibleCheckboxes = Array.from(studentCheckboxes).filter(cb => {
                        const item = cb.closest('.student-item');
                        return item && item.style.display !== 'none';
                    });

                    visibleCheckboxes.forEach(checkbox => {
                        checkbox.checked = this.checked;
                    });
                });
            }

            // Check conflict for individual students
            const studentCheckboxes = document.querySelectorAll('input[name="individual_values[]"]');
            studentCheckboxes.forEach(checkbox => {
                checkbox.addEventListener('change', async function() {
                    const userId = this.value;
                    const taskId = <?= $taskId ?>;

                    // Update "select all" checkbox state
                    if (selectAllStudents) {
                        const allCheckboxes = document.querySelectorAll('.student-checkbox');
                        const visibleCheckboxes = Array.from(allCheckboxes).filter(cb => {
                            const item = cb.closest('.student-item');
                            return item && item.style.display !== 'none';
                        });
                        const allChecked = visibleCheckboxes.length > 0 && visibleCheckboxes.every(cb => cb.checked);
                        selectAllStudents.checked = allChecked;
                    }

                    if (this.checked) {
                        // Check conflict for this user
                        const response = await fetch(`/api/manage_tasks.php?action=check_assignment_conflict&task_id=${taskId}&assign_type=3&assign_value=${userId}`);
                        const data = await response.json();

                        if (data.success && data.has_conflict) {
                            showConflictWarning(data.message);
                            // Uncheck the checkbox
                            this.checked = false;
                            // Update select all state
                            if (selectAllStudents) {
                                selectAllStudents.checked = false;
                            }
                        } else {
                            hideConflictWarning();
                        }
                    } else {
                        // Check if any other student is still checked
                        const anyChecked = document.querySelector('input[name="individual_values[]"]:checked');
                        if (!anyChecked) {
                            hideConflictWarning();
                        }
                    }
                });
            });

            // Search students
            const searchStudent = document.getElementById('searchStudent');
            if (searchStudent) {
                searchStudent.addEventListener('input', function() {
                    const searchTerm = this.value.toLowerCase();
                    const studentItems = document.querySelectorAll('.student-item');

                    studentItems.forEach(item => {
                        const name = item.dataset.name;
                        const username = item.dataset.username;

                        if (name.includes(searchTerm) || username.includes(searchTerm)) {
                            item.style.display = '';
                        } else {
                            item.style.display = 'none';
                        }
                    });

                    // Update "select all" checkbox state after search
                    if (selectAllStudents) {
                        const allCheckboxes = document.querySelectorAll('.student-checkbox');
                        const visibleCheckboxes = Array.from(allCheckboxes).filter(cb => {
                            const item = cb.closest('.student-item');
                            return item && item.style.display !== 'none';
                        });
                        const allChecked = visibleCheckboxes.length > 0 && visibleCheckboxes.every(cb => cb.checked);
                        selectAllStudents.checked = allChecked;
                    }
                });
            }

            // Form submission
            document.getElementById('assignTaskForm').addEventListener('submit', async function(e) {
                e.preventDefault();

                const assignType = document.querySelector('input[name="assign_type"]:checked').value;
                let assignValues = [];

                if (assignType === '2') {
                    const checkedGroups = document.querySelectorAll('input[name="group_values[]"]:checked');
                    if (checkedGroups.length === 0) {
                        showToast('Please select at least one group!', 'warning');
                        return;
                    }
                    assignValues = Array.from(checkedGroups).map(cb => cb.value);
                } else if (assignType === '3') {
                    const checkedStudents = document.querySelectorAll('input[name="individual_values[]"]:checked');
                    if (checkedStudents.length === 0) {
                        showToast('Please select at least one student!', 'warning');
                        return;
                    }
                    assignValues = Array.from(checkedStudents).map(cb => cb.value);
                }

                const submitBtn = document.getElementById('submitBtn');
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Assigning...';

                try {
                    const formData = new FormData();
                    formData.append('action', 'assign_task');
                    formData.append('task_id', <?= $taskId ?>);
                    formData.append('topic_id', <?= $topicId ?>);
                    formData.append('assign_type', assignType);
                    assignValues.forEach(value => {
                        formData.append('assign_values[]', value);
                    });

                    const response = await fetch('/api/manage_tasks.php', {
                        method: 'POST',
                        body: formData
                    });

                    const data = await response.json();

                    if (data.success) {
                        showToast('Task assigned successfully!', 'success');
                        setTimeout(() => {
                            window.location.reload();
                        }, 1500);
                    } else {
                        showToast(data.message || 'Cannot assign task', 'error');
                        submitBtn.disabled = false;
                        submitBtn.innerHTML = '<i class="bi bi-check-circle"></i> Assign Task';
                    }
                } catch (error) {
                    console.error('Error:', error);
                    showToast('An error occurred while assigning the task', 'error');
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = '<i class="bi bi-check-circle"></i> Assign Task';
                }
            });

            // Handle assignment checkbox changes - update delete button visibility
            function updateDeleteButton() {
                const checkedBoxes = document.querySelectorAll('.assignment-checkbox:checked');
                const deleteBtn = document.getElementById('deleteSelectedBtn');
                const countSpan = document.getElementById('selectedCount');

                if (checkedBoxes.length > 0) {
                    deleteBtn.style.display = 'inline-block';
                    countSpan.textContent = checkedBoxes.length;
                } else {
                    deleteBtn.style.display = 'none';
                    countSpan.textContent = '0';
                }
            }

            document.querySelectorAll('.assignment-checkbox').forEach(checkbox => {
                checkbox.addEventListener('change', function() {
                    updateDeleteButton();
                });
            });

            // Delete selected assignments
            const deleteSelectedBtn = document.getElementById('deleteSelectedBtn');
            if (deleteSelectedBtn) {
                deleteSelectedBtn.addEventListener('click', async function() {
                    const checkedBoxes = document.querySelectorAll('.assignment-checkbox:checked');
                    const deliverIds = Array.from(checkedBoxes).map(cb => cb.dataset.deliverId);

                    if (deliverIds.length === 0) {
                        showToast('Please select at least one item to delete!', 'warning');
                        return;
                    }

                    const count = deliverIds.length;
                    if (!confirm(`Are you sure you want to unassign ${count} selected items?`)) {
                        return;
                    }

                    // Disable button while processing
                    this.disabled = true;
                    this.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Deleting...';

                    let successCount = 0;
                    let failCount = 0;

                    try {
                        // Delete each assignment
                        for (const deliverId of deliverIds) {
                            try {
                                const formData = new FormData();
                                formData.append('action', 'remove_assignment');
                                formData.append('deliver_id', deliverId);
                                formData.append('topic_id', <?= $topicId ?>);

                                const response = await fetch('/api/manage_tasks.php', {
                                    method: 'POST',
                                    body: formData
                                });

                                const data = await response.json();

                                if (data.success) {
                                    successCount++;
                                } else {
                                    failCount++;
                                }
                            } catch (error) {
                                console.error('Error deleting:', deliverId, error);
                                failCount++;
                            }
                        }

                        // Show result
                        if (successCount > 0) {
                            showToast(`Successfully unassigned ${successCount} tasks!`, 'success');
                        }
                        if (failCount > 0) {
                            showToast(`Cannot unassign ${failCount} tasks!`, 'error');
                        }

                        setTimeout(() => {
                            window.location.reload();
                        }, 1500);

                    } catch (error) {
                        console.error('Error:', error);
                        showToast('An error occurred while unassigning tasks', 'error');
                        this.disabled = false;
                        this.innerHTML = '<i class="bi bi-trash"></i> Delete selected (<span id="selectedCount">' + count + '</span>)';
                    }
                });
            }
        })();
    </script>
</body>

</html>
