<?php

namespace App\Services;

// [REVIEW-FIX] R8.5: 同步 HTTP 调用 → 异步队列 Job
// Livewire 组件中不再阻塞等待 webhook 响应
class NotificationService
{
    /**
     * 发送项目通知到所有匹配的 webhook（通过队列异步执行）
     */
    public static function send(string $event, array $payload): void
    {
        \App\Jobs\SendWebhookNotification::dispatch($event, $payload);
    }

    // ── 便捷通知方法 ────────────────────────────────────

    public static function taskAssigned($task): void
    {
        self::send('task.assigned', [
            'project_id'    => $task->project_id,
            // [REVIEW-FIX] SP12.3: null-safe project access (project could be deleted)
            'project_title' => $task->project?->title ?? '',
            'task_title'    => $task->title,
            'assignee_name' => $task->assignee?->name,
            'user_name'     => $task->creator?->name,
        ]);
    }

    public static function taskConfirmed($task): void
    {
        self::send('task.confirmed', [
            'project_id'    => $task->project_id,
            // [REVIEW-FIX] SP12.3: null-safe project access (project could be deleted)
            'project_title' => $task->project?->title ?? '',
            'task_title'    => $task->title,
            'user_name'     => $task->assignee?->name,
        ]);
    }

    // [REVIEW-FIX] R15.1: 任务被拒绝时通知创建者
    public static function taskRejected($task): void
    {
        self::send('task.rejected', [
            'project_id'    => $task->project_id,
            // [REVIEW-FIX] SP12.3: null-safe project access (project could be deleted)
            'project_title' => $task->project?->title ?? '',
            'task_title'    => $task->title,
            'rejector_name' => $task->assignee?->name,
            'creator_name'  => $task->creator?->name,
        ]);
    }

    public static function taskCompleted($task): void
    {
        self::send('task.completed', [
            'project_id'    => $task->project_id,
            // [REVIEW-FIX] SP12.3: null-safe project access (project could be deleted)
            'project_title' => $task->project?->title ?? '',
            'task_title'    => $task->title,
            'user_name'     => $task->assignee?->name,
        ]);
    }

    public static function applicationSubmitted($application): void
    {
        self::send('application.submitted', [
            'project_id'    => $application->project_id,
            'project_title' => $application->project?->title ?? '',
            'user_name'     => $application->user?->name ?? '',
            'message'       => $application->message,
        ]);
    }

    public static function projectCompleted($project): void
    {
        self::send('project.completed', [
            'project_id'    => $project->id,
            'project_title' => $project->title,
        ]);
    }
}
