<?php

namespace App\Livewire;

use App\Models\Project;
use App\Models\ProjectApplication;
use App\Models\ProjectCategory;
use App\Models\ProjectLog;
use App\Models\Task;
use App\Models\User;
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

        $stats = [
            'total'       => (clone $query)->count(),
            'in_progress' => (clone $query)->where('progress', 'in_progress')->count(),
            'completed'   => (clone $query)->where('progress', 'completed')->count(),
            'overdue'     => (clone $query)->where('progress', '!=', 'completed')
                                            ->where('end_date', '<', now())->count(),
        ];

        // 分类分布
        $byCategory = ProjectCategory::withCount(['projects as count' => function ($q) use ($isAdmin, $user, $query) {
            if (!$isAdmin) {
                $q->whereHas('members', fn($m) => $m->where('user_id', $user->id))
                  ->orWhere('created_by', $user->id);
            }
        }])->orderByDesc('count')->get();

        // 进度分布
        $byProgress = Project::when(!$isAdmin, function ($q) use ($user) {
            $q->whereHas('members', fn($m) => $m->where('user_id', $user->id))
              ->orWhere('created_by', $user->id);
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
            $myProjectIds = $isAdmin
                ? Project::pluck('id')
                : $user->assignedProjects()->wherePivot('role', 'lead')->pluck('project_id');

            $pendingApplications = ProjectApplication::whereIn('project_id', $myProjectIds)
                ->where('status', 'pending')
                ->with(['user', 'project'])
                ->latest()
                ->limit(5)->get();
        }

        // 任务统计
        $taskStats = [
            'my_total'      => Task::where('assigned_to', $user->id)->count(),
            'my_completed'  => Task::where('assigned_to', $user->id)->where('status', 'completed')->count(),
            'my_pending'    => Task::where('assigned_to', $user->id)->where('status', 'pending_confirmation')->count(),
            'app_pending'   => $pendingApplications->count(),
        ];

        // 最近日志
        $recentLogs = ProjectLog::with(['user', 'project'])
            ->latest('created_at')->limit(10)->get();

        // 即将到期项目
        $upcomingDeadlines = Project::when(!$isAdmin, function ($q) use ($user) {
            $q->whereHas('members', fn($m) => $m->where('user_id', $user->id))
              ->orWhere('created_by', $user->id);
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
