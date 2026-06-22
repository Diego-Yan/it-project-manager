<?php

namespace App\Console\Commands;

use App\Models\Project;
use App\Models\Notification;
use App\Models\Task;
use App\Services\NotificationService;
use Illuminate\Console\Command;

class CheckDeadlines extends Command
{
    protected $signature = 'check:deadlines {--dry-run : 只显示将要发送的通知，不实际发送}';
    protected $description = '检查项目和任务截止日期，通过 Webhook + 站内通知发送提醒';

    public function handle(): int
    {
        $now = now();
        $dryRun = $this->option('dry-run');
        $count = 0;

        // [REVIEW-FIX-R3 #5 P2] 通知去重：原代码每天 9:00 和 15:00 各执行一次，
        // 对每个逾期/即将到期项目都发送通知 → 同一项目/成员一天收到 2 次、一周 14 次重复通知。
        // 修复：用 Cache 标记已通知的项目，即将到期（3天）每天通知 1 次，逾期每天通知 1 次。
        $notifyKey = function (string $type, int $id): string {
            return "deadline_notified:{$type}:{$id}:" . now()->format('Y-m-d');
        };

        // ── 1. 项目即将到期（3天内） ──────────────────────
        $nearProjects = Project::where('progress', '!=', 'completed')
            ->whereNotNull('end_date')
            ->whereBetween('end_date', [$now->copy()->startOfDay(), $now->copy()->addDays(3)->endOfDay()])
            ->with('members')
            ->get();

        foreach ($nearProjects as $project) {
            $daysLeft = (int) $now->diffInDays($project->end_date, false);
            $this->info("  项目即将到期: {$project->title} ({$daysLeft} 天)");

            // [REVIEW-FIX-R3 #5 P2] 每天最多通知 1 次（去重）
            $dedupKey = $notifyKey('near', $project->id);
            $alreadyNotified = \Illuminate\Support\Facades\Cache::has($dedupKey);

            if (!$dryRun && !$alreadyNotified) {
                // [REVIEW-FIX] R6.3: webhook 通知
                NotificationService::send('project.deadline_near', [
                    'project_id'    => $project->id,
                    'project_title' => $project->title,
                    'message'       => __('项目「:title」将在 :days 天后到期', ['title' => $project->title, 'days' => $daysLeft]),
                    'status_from'   => __('进行中'),
                    'status_to'     => __(':days天后到期', ['days' => $daysLeft]),
                    'comment'       => $project->progressLabel . ' · ' . ($project->completion_percent ?? 0) . '% 完成',
                ]);

                // [REVIEW-FIX] R6.3: 站内铃铛通知（双写）
                $memberIds = $project->members->pluck('id')->toArray();
                $memberIds[] = $project->created_by;
                foreach (array_unique($memberIds) as $uid) {
                    Notification::send($uid,
                        __('⏰ 项目即将到期'),
                        __('「:title」将在 :days 天后到期', ['title' => $project->title, 'days' => $daysLeft]),
                        'warning',
                        "/projects/{$project->id}"
                    );
                }
                // 标记当天已通知，TTL 到当天结束
                \Illuminate\Support\Facades\Cache::put($dedupKey, true, now()->endOfDay());
            }
            $count++;
        }

        // ── 2. 项目已逾期 ──────────────────────────────────
        $overdueProjects = Project::where('progress', '!=', 'completed')
            ->whereNotNull('end_date')
            ->where('end_date', '<', $now->copy()->startOfDay())
            ->with('members')
            ->get();

        foreach ($overdueProjects as $project) {
            $daysOverdue = (int) $now->diffInDays($project->end_date);
            $this->warn("  项目已逾期: {$project->title} (逾期 {$daysOverdue} 天)");

            // [REVIEW-FIX-R3 #5 P2] 逾期通知每天最多 1 次（去重）
            $dedupKey = $notifyKey('overdue', $project->id);
            $alreadyNotified = \Illuminate\Support\Facades\Cache::has($dedupKey);

            if (!$dryRun && !$alreadyNotified) {
                NotificationService::send('project.overdue', [
                    'project_id'    => $project->id,
                    'project_title' => $project->title,
                    'message'       => __('⚠️ 项目「:title」已逾期 :days 天', ['title' => $project->title, 'days' => $daysOverdue]),
                    'status_from'   => $project->progressLabel,
                    'status_to'     => __('逾期:days天', ['days' => $daysOverdue]),
                ]);

                // [REVIEW-FIX] R6.3: 站内铃铛通知
                $memberIds = $project->members->pluck('id')->toArray();
                $memberIds[] = $project->created_by;
                foreach (array_unique($memberIds) as $uid) {
                    Notification::send($uid,
                        __('🚨 项目已逾期'),
                        __('「:title」已逾期 :days 天', ['title' => $project->title, 'days' => $daysOverdue]),
                        'error',
                        "/projects/{$project->id}"
                    );
                }
                // 标记当天已通知
                \Illuminate\Support\Facades\Cache::put($dedupKey, true, now()->endOfDay());
            }
            $count++;
        }

        // ── 3. 任务即将到期（1天内） ──────────────────────
        $nearTasks = Task::where('status', '!=', 'completed')
            ->whereNotNull('due_date')
            ->whereNotNull('assigned_to')
            ->whereBetween('due_date', [$now->copy()->startOfDay(), $now->copy()->addDay()->endOfDay()])
            ->with(['project', 'assignee'])
            ->get();

        foreach ($nearTasks as $task) {
            $this->info("  任务即将到期: {$task->title} (" . ($task->assignee?->name ?? '未分配') . ")");

            // [REVIEW-FIX-R3 #5 P2] 任务到期通知每天最多 1 次（去重）
            $dedupKey = $notifyKey('task', $task->id);
            $alreadyNotified = \Illuminate\Support\Facades\Cache::has($dedupKey);

            if (!$dryRun && !$alreadyNotified) {
                NotificationService::send('task.deadline_near', [
                    'project_id'    => $task->project_id,
                    // [REVIEW-FIX] SP12.4: null-safe project access (consistent with SP12.1)
                    'project_title' => $task->project?->title ?? '',
                    'task_title'    => $task->title,
                // [REVIEW-FIX] SP12.1: null-safe assignee access (consistency with line 103)
                    'assignee_name' => $task->assignee?->name ?? __('未分配'),
                    'message'       => __('任务「:title」即将到期，分配给 :assignee', ['title' => $task->title, 'assignee' => $task->assignee?->name ?? __('未分配')]),
                ]);

                // [REVIEW-FIX] R6.3: 站内铃铛通知（仅通知负责人）
                Notification::send($task->assigned_to,
                    __('⏰ 任务即将到期'),
                    __('「:title」已到期，请及时处理', ['title' => $task->title]),
                    'warning',
                    "/projects/{$task->project_id}/kanban"
                );
                // 标记当天已通知
                \Illuminate\Support\Facades\Cache::put($dedupKey, true, now()->endOfDay());
            }
            $count++;
        }

        $label = $dryRun ? '[DRY RUN] ' : '';
        $this->info(__(':label共发送 :count 条提醒通知', ['label' => $label, 'count' => $count]));

        return 0;
    }
}
