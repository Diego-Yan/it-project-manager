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

        // [REVIEW-FIX-R3 #4 P3] 修复 webhook 失败无法触发重试/管理员通知：
        // 原 handle() 中单个 webhook 失败被 try/catch 吞掉且只记 warning 日志，
        // 导致 Job 永远"成功"完成 → failed() 方法（管理员通知）永远不会被触发，
        // $tries=3 重试机制也形同虚设。
        // 修复：用 $failures 计数器收集失败，若全部失败则抛出异常触发重试和 failed() 回调。
        $failures = [];
        $successCount = 0;

        foreach ($webhooks as $webhook) {
            if ($webhooks->isEmpty()) break;

            $body = match ($webhook->type) {
                'wechat'   => $this->formatWechat($payload),
                'dingtalk' => $this->formatDingTalk($payload),
                default    => $this->formatCustom($payload),
            };

            // [REVIEW-FIX] R16.4: 单个 webhook 失败不阻塞其余投递
            try {
                $resp = Http::timeout(10)->post($webhook->url, $body);
                if ($resp->successful()) {
                    $successCount++;
                    Log::info("Webhook sent: {$webhook->name} event={$this->event}");
                } else {
                    $failures[] = "{$webhook->name} (HTTP {$resp->status()})";
                    Log::warning("Webhook HTTP error: {$webhook->name} event={$this->event}, status={$resp->status()}");
                }
            } catch (\Throwable $e) {
                $failures[] = "{$webhook->name} ({$e->getMessage()})";
                Log::warning("Webhook failed: {$webhook->name} event={$this->event}, error=" . $e->getMessage());
            }
        }

        // [REVIEW-FIX-R3 #4 P3] 全部 webhook 投递失败时抛出异常，触发 $tries 重试 + failed() 管理员通知。
        // 部分成功时不抛出（已投递成功的 webhook 不应重复投递）。
        if (!empty($failures) && $successCount === 0) {
            throw new \RuntimeException(
                "All webhook deliveries failed for event={$this->event}: " . implode('; ', $failures)
            );
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
            $lines[] = "**" . __('项目') . "**: {$p['project_title']}";
        }
        if (!empty($p['task_title'])) {
            $lines[] = "**" . __('任务') . "**: {$p['task_title']}";
        }
        if (!empty($p['user_name'])) {
            $lines[] = "**" . __('操作人') . "**: {$p['user_name']}";
        }
        if (!empty($p['assignee_name'])) {
            $lines[] = "**" . __('分配给') . "**: {$p['assignee_name']}";
        }
        if (!empty($p['message'])) {
            $lines[] = '';
            $lines[] = $p['message'];
        }
        if (!empty($p['status_from']) && !empty($p['status_to'])) {
            $lines[] = "**" . __('状态') . "**: {$p['status_from']} → {$p['status_to']}";
        }
        if (!empty($p['comment'])) {
            $lines[] = "> {$p['comment']}";
        }

        $lines[] = '';
        $lines[] = '---';
        $lines[] = '📅 ' . ($p['timestamp'] ?? now()->toDateTimeString());
        $lines[] = '🤖 ' . config('app.name', 'IT 服务管理');

        return implode("\n", $lines);
    }

    private function eventTitle(string $event): string
    {
        return match ($event) {
            'project.created'        => __('📁 项目已创建'),
            'project.updated'        => __('📝 项目已更新'),
            'project.completed'      => __('✅ 项目已完成'),
            'project.deadline_near'  => __('⏰ 项目即将到期'),
            'project.overdue'        => __('🚨 项目已逾期'),
            'task.assigned'          => __('📋 新任务已分配'),
            'task.confirmed'         => __('✔️ 任务已确认'),
            'task.completed'         => __('✅ 任务已完成'),
            'task.unassigned'        => __('🔄 任务待认领'),
            'task.deadline_near'     => __('⏰ 任务即将到期'),
            'member.joined'          => __('👤 新成员加入'),
            'application.submitted'  => __('📨 新的加入申请'),
            'ticket.created'         => __('🎫 工单已创建'),
            'ticket.assigned'        => __('👤 工单已分配'),
            'ticket.resolved'        => __('✅ 工单已解决'),
            'ticket.closed'          => __('🔒 工单已关闭'),
            'ticket.proxy_created'   => __('📋 代填工单已创建'),
            'daily.digest'           => __('📊 每日项目概报'),
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
                    __('Webhook 投递失败'),
                    __('事件 :event 在 :tries 次重试后仍然失败：:error', [
                        'event' => $this->event,
                        'tries' => $this->tries,
                        'error' => $e->getMessage(),
                    ]),
                    'error'
                );
            }
        } catch (\Throwable $_) {
            // 静默处理 — 至少日志已经记录了
        }
    }
}
