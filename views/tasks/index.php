<?php
require_once './config/db.php';
require_once './config/helper_task.php';
require_once './config/helper_phase.php';
require_once './config/helper_class.php';

$topicId = isset($_GET['topic_id']) ? intval($_GET['topic_id']) : 0;
$isTeacher = isTeacherInTopicClass($topicId, $_SESSION['user']['user_id']);

if ($isTeacher) {
    require_once './views/tasks/view_task_in_phase_teacher.php';
} else {
    // echo $isTeacher;
    require_once './views/tasks/view_task_in_phase_student.php';
}