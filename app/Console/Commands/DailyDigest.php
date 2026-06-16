<?php

namespace App\Console\Commands;

use App\Models\Project;
use App\Models\Task;
use App\Services\NotificationService;
use Illuminate\Console\Command;

class DailyDigest extends Command
{
    protected $signature = 'daily:digest {--dry-run : 只显示内容不发送}';
    protected $description = '发送每日项目概报到 Webhook';

    public function handle(): int
    {
        $dryRun = $this->option('dry-run');
        // [REVIEW-FIX] I7: 先创建 $today 再创建 $now，意图清晰避免混淆
        $today = now()->startOfDay();
        $now = now();

        // ── 统计 ────────────────────────────────────────────
        $totalProjects    = Project::count();
        $activeProjects   = Project::where('progress', 'in_progress')->count();
        $completedToday   = Project::where('progress', 'completed')->whereDate('updated_at', $now->toDateString())->count();
        $totalTasks       = Task::count();
        $completedTasks   = Task::where('status', 'completed')->whereDate('updated_at', $now->toDateString())->count();
        $pendingTasks     = Task::where('status', 'pending_confirmation')->count();

        // 即将到期的项目
        $nearProjects = Project::where('progress', '!=', 'completed')
            ->whereNotNull('end_date')
            ->whereBetween('end_date', [$today, $today->copy()->addDays(7)->endOfDay()])
            ->orderBy('end_date')
            ->limit(5)
            ->get();

        // 构建消息
        $lines = [];
        $lines[] = "## 📊 IT 项目管理日报";
        $lines[] = "**{$now->format('Y年m月d日')}**";
        $lines[] = '';
        $lines[] = "### 项目概况";
        $lines[] = "- 活跃项目: **{$activeProjects}** / 总计 {$totalProjects}";
        $lines[] = "- 今日完成项目: **{$completedToday}**";
        $lines[] = "- 今日完成任务: **{$completedTasks}** / 总计 {$totalTasks}";
        $lines[] = "- 待确认任务: **{$pendingTasks}**";
        $lines[] = '';

        if ($nearProjects->isNotEmpty()) {
            $lines[] = "### ⏰ 即将到期（7天内）";
            foreach ($nearProjects as $p) {
                $days = (int) $now->diffInDays($p->end_date, false);
                $icon = $days <= 1 ? '🔴' : ($days <= 3 ? '🟡' : '🟢');
                $lines[] = "- {$icon} **{$p->title}** — {$days} 天后（{$p->progressLabel} · {$p->completion_percent}%）";
            }
            $lines[] = '';
        }

        $lines[] = '---';
        $lines[] = '🤖 IT项目管理系统 · ' . $now->format('H:i');

        $message = implode("\n", $lines);

        if ($dryRun) {
            $this->info("[DRY RUN] Daily digest:");
            $this->line($message);
        } else {
            NotificationService::send('daily.digest', [
                'message' => $message,
            ]);
            $this->info('日报已发送');
        }

        return 0;
    }
}
