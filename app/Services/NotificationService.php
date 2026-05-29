<?php

namespace App\Services;

use App\Models\WebhookConfig;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class NotificationService
{
    /**
     * 发送项目通知到所有匹配的 webhook
     */
    public static function send(string $event, array $payload): void
    {
        $payload['event'] = $event;
        $payload['timestamp'] = now()->toIso8601String();

        // 查找匹配的 webhook（全局 + 项目级）
        $projectId = $payload['project_id'] ?? null;

        $webhooks = WebhookConfig::where('is_active', true)
            ->where(function ($q) use ($projectId) {
                $q->whereNull('project_id')
                  ->orWhere('project_id', $projectId);
            })
            ->where(function ($q) use ($event) {
                $q->whereNull('events')          // null = 所有事件
                  ->orWhereJsonContains('events', $event);
            })
            ->get();

        foreach ($webhooks as $webhook) {
            try {
                $body = match ($webhook->type) {
                    'wechat'   => self::formatWechat($payload),
                    'dingtalk' => self::formatDingTalk($payload),
                    default    => self::formatCustom($payload),
                };

                Http::timeout(10)->post($webhook->url, $body);
                Log::info("Webhook sent: {$webhook->name} event={$event}");
            } catch (\Exception $e) {
                Log::error("Webhook failed: {$webhook->name}: " . $e->getMessage());
            }
        }
    }

    // ── 企微机器人消息格式 ──────────────────────────────

    private static function formatWechat(array $payload): array
    {
        $event = $payload['event'];
        $markdown = self::buildMarkdown($payload);

        return [
            'msgtype'  => 'markdown',
            'markdown' => ['content' => $markdown],
        ];
    }

    // ── 钉钉机器人消息格式 ──────────────────────────────

    private static function formatDingTalk(array $payload): array
    {
        $event = $payload['event'];
        $markdown = self::buildMarkdown($payload);

        return [
            'msgtype'  => 'markdown',
            'markdown' => [
                'title' => self::eventTitle($event),
                'text'  => $markdown,
            ],
        ];
    }

    // ── 自定义 JSON 格式 ────────────────────────────────

    private static function formatCustom(array $payload): array
    {
        return $payload;
    }

    // ── Markdown 消息构建 ───────────────────────────────

    private static function buildMarkdown(array $p): string
    {
        $event = $p['event'];
        $lines = [];

        $lines[] = '## ' . self::eventTitle($event);
        $lines[] = '';

        if (!empty($p['project_title'])) {
            $lines[] = "**项目**: {$p['project_title']}";
        }
        if (!empty($p['task_title'])) {
            $lines[] = "**任务**: {$p['task_title']}";
        }
        if (!empty($p['user_name'])) {
            $lines[] = "**操作人**: {$p['user_name']}";
        }
        if (!empty($p['assignee_name'])) {
            $lines[] = "**分配给**: {$p['assignee_name']}";
        }
        if (!empty($p['message'])) {
            $lines[] = '';
            $lines[] = $p['message'];
        }
        if (!empty($p['status_from']) && !empty($p['status_to'])) {
            $lines[] = "**状态**: {$p['status_from']} → {$p['status_to']}";
        }
        if (!empty($p['comment'])) {
            $lines[] = "> {$p['comment']}";
        }

        $lines[] = '';
        $lines[] = '---';
        $lines[] = '📅 ' . ($p['timestamp'] ?? now()->toDateTimeString());
        $lines[] = '🤖 IT服务管理系统';

        return implode("\n", $lines);
    }

    private static function eventTitle(string $event): string
    {
        return match ($event) {
            'project.created'        => '📁 项目已创建',
            'project.updated'        => '📝 项目已更新',
            'project.completed'      => '✅ 项目已完成',
            'project.deadline_near'  => '⏰ 项目即将到期',
            'project.overdue'        => '🚨 项目已逾期',
            'task.assigned'          => '📋 新任务已分配',
            'task.confirmed'         => '✔️ 任务已确认',
            'task.completed'         => '✅ 任务已完成',
            'task.unassigned'        => '🔄 任务待认领',
            'task.deadline_near'     => '⏰ 任务即将到期',
            'member.joined'          => '👤 新成员加入',
            'application.submitted'  => '📨 新的加入申请',
            default                  => "🔔 {$event}",
        };
    }

    // ── 便捷通知方法 ────────────────────────────────────

    public static function taskAssigned($task): void
    {
        self::send('task.assigned', [
            'project_id'    => $task->project_id,
            'project_title' => $task->project->title,
            'task_title'    => $task->title,
            'assignee_name' => $task->assignee?->name,
            'user_name'     => $task->creator?->name,
        ]);
    }

    public static function taskConfirmed($task): void
    {
        self::send('task.confirmed', [
            'project_id'    => $task->project_id,
            'project_title' => $task->project->title,
            'task_title'    => $task->title,
            'user_name'     => $task->assignee?->name,
        ]);
    }

    public static function taskCompleted($task): void
    {
        self::send('task.completed', [
            'project_id'    => $task->project_id,
            'project_title' => $task->project->title,
            'task_title'    => $task->title,
            'user_name'     => $task->assignee?->name,
        ]);
    }

    public static function applicationSubmitted($application): void
    {
        self::send('application.submitted', [
            'project_id'    => $application->project_id,
            'project_title' => $application->project->title,
            'user_name'     => $application->user->name,
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
