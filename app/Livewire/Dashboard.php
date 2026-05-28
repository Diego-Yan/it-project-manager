<?php

namespace App\Livewire;

use App\Models\Project;
use App\Models\ProjectCategory;
use App\Models\ProjectLog;
use App\Models\User;
use Livewire\Component;

class Dashboard extends Component
{
    public function render()
    {
        $user = auth()->user();
        $isAdmin = $user->hasRole(['超级管理员', '管理员']);

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
            'stats', 'byCategory', 'byProgress', 'recentLogs', 'upcomingDeadlines'
        ))->layout('layouts.app', ['title' => '仪表盘']);
    }
}
