<?php
/**
 * Shared breadcrumb helpers — consistent class › topic › phases › tasks flow.
 */

function breadcrumbClassLabel(): string
{
    return htmlspecialchars($_SESSION['current_class_name'] ?? 'Class', ENT_QUOTES, 'UTF-8');
}

function breadcrumbClassUrl(): string
{
    return (defined('URL_ROOT') ? URL_ROOT : '') . '/class';
}

function breadcrumbTopicPhasesUrl(int $topicId): string
{
    return breadcrumbClassUrl() . '/phases?topic_id=' . $topicId;
}

function breadcrumbPhaseTasksUrl(int $topicId, int $phaseId): string
{
    return breadcrumbClassUrl() . '/task?topic_id=' . $topicId . '&phase_id=' . $phaseId;
}
