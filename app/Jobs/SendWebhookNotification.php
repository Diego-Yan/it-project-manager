<?php

namespace App\Jobs;

use App\Models\Notification;
use App\Models\User;
use App\Models\WebhookConfig;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

// [REVIEW-FIX] R8.5: Webhook HTTP 调用异步化 — 从 Livewire 请求周期中剥离
// [REVIEW-FIX] M10: 移除 SerializesModels — 无 Eloquent 模型参数，序列化开销无价值
// 避免慢速/故障的 webhook 端点阻塞 UI 响应
class SendWebhookNotification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    public int $tries = 3;
    public int $backoff = 10; // 重试间隔（秒）

    public function __construct(
        public string $event,
        public array $payload,
    ) {}

    public function handle(): void
    {
        $payload = $this->payload;
        $payload['event'] = $this->event;
        $payload['timestamp'] = now()->toIso8601String();

        $projectId = $payload['project_id'] ?? null;

        $webhooks = WebhookConfig::where('is_active', true)
            ->where(function ($q) use ($projectId) {
                $q->whereNull('project_id')
                  ->orWhere('project_id', $projectId);
            })
            ->where(function ($q) {
                $q->whereNull('events')
                  ->orWhereJsonContains('events', $this->event);
            })
            ->get();

        foreach ($webhooks as $webhook) {
            $body = match ($webhook->type) {
                'wechat'   => $this->formatWechat($payload),
                'dingtalk' => $this->formatDingTalk($payload),
                default    => $this->formatCustom($payload),
            };

            // [REVIEW-FIX] R16.4: 单个 webhook 失败不阻塞其余投递
            try {
                Http::timeout(10)->post($webhook->url, $body);
                Log::info("Webhook sent: {$webhook->name} event={$this->event}");
            } catch (\Throwable $e) {
                Log::warning("Webhook failed: {$webhook->name} event={$this->event}, error=" . $e->getMessage());
            }
        }
    }

    private function formatWechat(array $payload): array
    {
        return [
            'msgtype'  => 'markdown',
            'markdown' => ['content' => $this->buildMarkdown($payload)],
        ];
    }

    private function formatDingTalk(array $payload): array
    {
        $markdown = $this->buildMarkdown($payload);
        return [
            'msgtype'  => 'markdown',
            'markdown' => [
                'title' => $this->eventTitle($this->event),
                'text'  => $markdown,
            ],
        ];
    }

    private function formatCustom(array $payload): array
    {
        return $payload;
    }

    private function buildMarkdown(array $p): string
    {
        $lines = [];
        $lines[] = '## ' . $this->eventTitle($this->event);
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

    private function eventTitle(string $event): string
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
            'ticket.created'         => '🎫 工单已创建',
            'ticket.assigned'        => '👤 工单已分配',
            'ticket.resolved'        => '✅ 工单已解决',
            'ticket.closed'          => '🔒 工单已关闭',
            'ticket.proxy_created'   => '📋 代填工单已创建',
            'daily.digest'           => '📊 每日项目概报',
            default                  => "🔔 {$event}",
        };
    }

    public function failed(\Throwable $e): void
    {
        // [REVIEW-FIX] I8: 失败时记录完整上下文并通知管理员，便于排障
        Log::error("Webhook job failed after {$this->tries} retries", [
            'event'   => $this->event,
            'payload' => $this->payload,
            'error'   => $e->getMessage(),
        ]);

        // 站内通知管理员 webhook 投递失败
        try {
            // [REVIEW-FIX] R6: 管理员角色名（应后续提取为 config/app.admin_role_name）
            $adminIds = User::role('超级管理员')->pluck('id')->toArray();
            foreach ($adminIds as $uid) {
                Notification::send($uid,
                    'Webhook 投递失败',
                    "事件 {$this->event} 在 {$this->tries} 次重试后仍然失败：{$e->getMessage()}",
                    'error'
                );
            }
        } catch (\Throwable $_) {
            // 静默处理 — 至少日志已经记录了
        }
    }
}
