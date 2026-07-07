<?php
require_once './config/db.php';
require_once './config/helper_task.php';
require_once './config/helper_phase.php';
require_once './config/helper_class.php';

$topicId = isset($_GET['topic_id']) ? intval($_GET['topic_id']) : 0;
$phaseId = isset($_GET['phase_id']) ? intval($_GET['phase_id']) : 0;
if ($topicId > 0) {
    $_SESSION['nav_topic_id'] = $topicId;
}
if ($phaseId > 0) {
    $_SESSION['nav_phase_id'] = $phaseId;
}
$isTeacher = isTeacherInTopicClass($topicId, $_SESSION['user']['user_id']);

if ($isTeacher) {
    require_once './views/tasks/view_task_in_phase_teacher.php';
} else {
    // echo $isTeacher;
    require_once './views/tasks/view_task_in_phase_student.php';
}