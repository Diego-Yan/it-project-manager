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

        // ── 1. 项目即将到期（3天内） ──────────────────────
        $nearProjects = Project::where('progress', '!=', 'completed')
            ->whereNotNull('end_date')
            ->whereBetween('end_date', [$now->copy()->startOfDay(), $now->copy()->addDays(3)->endOfDay()])
            ->with('members')
            ->get();

        foreach ($nearProjects as $project) {
            $daysLeft = (int) $now->diffInDays($project->end_date, false);
            $this->info("  项目即将到期: {$project->title} ({$daysLeft} 天)");

            if (!$dryRun) {
                // [REVIEW-FIX] R6.3: webhook 通知
                NotificationService::send('project.deadline_near', [
                    'project_id'    => $project->id,
                    'project_title' => $project->title,
                    'message'       => "项目「{$project->title}」将在 {$daysLeft} 天后到期",
                    'status_from'   => '进行中',
                    'status_to'     => "{$daysLeft}天后到期",
                    'comment'       => $project->progressLabel . ' · ' . ($project->completion_percent ?? 0) . '% 完成',
                ]);

                // [REVIEW-FIX] R6.3: 站内铃铛通知（双写）
                $memberIds = $project->members->pluck('id')->toArray();
                $memberIds[] = $project->created_by;
                foreach (array_unique($memberIds) as $uid) {
                    Notification::send($uid,
                        "⏰ 项目即将到期",
                        "「{$project->title}」将在 {$daysLeft} 天后到期",
                        'warning',
                        "/projects/{$project->id}"
                    );
                }
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

            if (!$dryRun) {
                NotificationService::send('project.overdue', [
                    'project_id'    => $project->id,
                    'project_title' => $project->title,
                    'message'       => "⚠️ 项目「{$project->title}」已逾期 {$daysOverdue} 天",
                    'status_from'   => $project->progressLabel,
                    'status_to'     => "逾期{$daysOverdue}天",
                ]);

                // [REVIEW-FIX] R6.3: 站内铃铛通知
                $memberIds = $project->members->pluck('id')->toArray();
                $memberIds[] = $project->created_by;
                foreach (array_unique($memberIds) as $uid) {
                    Notification::send($uid,
                        "🚨 项目已逾期",
                        "「{$project->title}」已逾期 {$daysOverdue} 天",
                        'error',
                        "/projects/{$project->id}"
                    );
                }
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

            if (!$dryRun) {
                NotificationService::send('task.deadline_near', [
                    'project_id'    => $task->project_id,
                    'project_title' => $task->project->title,
                    'task_title'    => $task->title,
                    'assignee_name' => $task->assignee->name,
                    'message'       => "任务「{$task->title}」即将到期，分配给 {$task->assignee->name}",
                ]);

                // [REVIEW-FIX] R6.3: 站内铃铛通知（仅通知负责人）
                Notification::send($task->assigned_to,
                    "⏰ 任务即将到期",
                    "「{$task->title}」已到期，请及时处理",
                    'warning',
                    "/projects/{$task->project_id}/kanban"
                );
            }
            $count++;
        }

        $label = $dryRun ? '[DRY RUN] ' : '';
        $this->info("{$label}共发送 {$count} 条提醒通知");

        return 0;
    }
}
