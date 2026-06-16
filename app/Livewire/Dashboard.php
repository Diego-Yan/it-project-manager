<?php

namespace App\Livewire;

use App\Models\Project;
use App\Models\ProjectApplication;
use App\Models\ProjectCategory;
use App\Models\ProjectLog;
use App\Models\Task;
use Livewire\Component;

class Dashboard extends Component
{
    public function render()
    {
        $user = auth()->user();
        $isAdmin = $user->can('view all projects');

        // 项目统计
        $query = $isAdmin ? Project::query() : Project::whereHas('members', fn($q) => $q->where('user_id', $user->id))
                                                       ->orWhere('created_by', $user->id);

        // [REVIEW-FIX] M4: 4次独立 COUNT → 1次 GROUP BY
        $allCounts = (clone $query)->selectRaw("
                COUNT(*) as total,
                SUM(CASE WHEN progress = 'in_progress' THEN 1 ELSE 0 END) as in_progress,
                SUM(CASE WHEN progress = 'completed' THEN 1 ELSE 0 END) as completed,
                SUM(CASE WHEN progress != 'completed' AND end_date < ? THEN 1 ELSE 0 END) as overdue
            ", [now()])->first();
        $stats = [
            'total'       => (int) $allCounts->total,
            'in_progress' => (int) $allCounts->in_progress,
            'completed'   => (int) $allCounts->completed,
            'overdue'     => (int) $allCounts->overdue,
        ];

        // 分类分布
        // [REVIEW-FIX] C2: 移除未使用的 $query 闭包参数（内联作用域已覆盖需求）
        $byCategory = ProjectCategory::withCount(['projects as count' => function ($q) use ($isAdmin, $user) {
            if (!$isAdmin) {
                $q->where(function ($sub) use ($user) {
                    $sub->whereHas('members', fn($m) => $m->where('user_id', $user->id))
                        ->orWhere('created_by', $user->id);
                });
            }
        }])->orderByDesc('count')->get();

        // 进度分布 — [REVIEW-FIX] I2: orWhere 包裹在嵌套 where() 防止 AND/OR 分组错乱
        $byProgress = Project::when(!$isAdmin, function ($q) use ($user) {
            $q->where(function ($sub) use ($user) {
                $sub->whereHas('members', fn($m) => $m->where('user_id', $user->id))
                    ->orWhere('created_by', $user->id);
            });
        })->selectRaw('progress, count(*) as count')
          ->groupBy('progress')->pluck('count', 'progress');

        // 我的待确认任务
        $pendingTasks = Task::where('assigned_to', $user->id)
            ->where('status', 'pending_confirmation')
            ->with('project')
            ->latest()
            ->limit(5)->get();

        // 我的进行中任务
        $myTasks = Task::where('assigned_to', $user->id)
            ->where('status', 'in_progress')
            ->with('project')
            ->latest('updated_at')
            ->limit(5)->get();

        // 待审批的加入申请（我负责的项目）
        $pendingApplications = collect();
        if ($isAdmin || $user->assignedProjects()->wherePivot('role', 'lead')->exists()) {
            // [REVIEW-FIX] R6.4: 管理员跳过 whereIn 避免 pluck 全量 ID（内存浪费）
            $pendingApplications = ProjectApplication::where('status', 'pending')
                ->when(!$isAdmin, function ($q) use ($user) {
                    $myProjectIds = $user->assignedProjects()
                        ->wherePivot('role', 'lead')
                        ->pluck('project_id');
                    $q->whereIn('project_id', $myProjectIds);
                })
                ->with(['user', 'project'])
                ->latest()
                ->limit(5)->get();
        }

        // [REVIEW-FIX] R6.2: 3次独立 COUNT → 1次 GROUP BY
        $taskStatsRaw = Task::where('assigned_to', $user->id)
            ->selectRaw("
                COUNT(*) as my_total,
                SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as my_completed,
                SUM(CASE WHEN status = 'pending_confirmation' THEN 1 ELSE 0 END) as my_pending
            ")->first();
        $taskStats = [
            'my_total'    => (int) $taskStatsRaw->my_total,
            'my_completed'=> (int) $taskStatsRaw->my_completed,
            'my_pending'  => (int) $taskStatsRaw->my_pending,
            'app_pending' => $pendingApplications->count(),
        ];

        // 最近日志
        $recentLogs = ProjectLog::with(['user', 'project'])
            ->latest('created_at')->limit(10)->get();

        // 即将到期项目 — [REVIEW-FIX] I2: orWhere 包裹在嵌套 where()
        $upcomingDeadlines = Project::when(!$isAdmin, function ($q) use ($user) {
            $q->where(function ($sub) use ($user) {
                $sub->whereHas('members', fn($m) => $m->where('user_id', $user->id))
                    ->orWhere('created_by', $user->id);
            });
        })->where('progress', '!=', 'completed')
          ->whereNotNull('end_date')
          ->whereBetween('end_date', [now(), now()->addDays(7)])
          ->with('category')
          ->orderBy('end_date')
          ->limit(5)->get();

        return view('livewire.dashboard', compact(
            'stats', 'byCategory', 'byProgress', 'recentLogs', 'upcomingDeadlines',
            'pendingTasks', 'myTasks', 'pendingApplications', 'taskStats'
        ))->layout('layouts.app', ['title' => '仪表盘']);
    }
}
