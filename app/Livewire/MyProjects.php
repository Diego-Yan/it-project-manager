<?php

namespace App\Livewire;

use App\Models\Project;
use Livewire\Component;
use Livewire\WithPagination;

class MyProjects extends Component
{
    use WithPagination;

    public string $filterProgress = '';

    private function myProjectQuery()
    {
        $user = auth()->user();
        return Project::where(function ($q) use ($user) {
            $q->where('created_by', $user->id)
              ->orWhereHas('members', fn($m) => $m->where('user_id', $user->id));
        });
    }

    public function render()
    {
        $base = $this->myProjectQuery();

        $projects = (clone $base)->with(['category', 'members'])
            ->when($this->filterProgress, fn($q) => $q->where('progress', $this->filterProgress))
            ->latest()
            ->paginate(15);

        // [REVIEW-FIX] R16.2: 3次独立 COUNT → 1次 GROUP BY（同 R3.5/R15.6 优化模式）
        $countsRaw = (clone $base)->selectRaw("
                COUNT(*) as total,
                SUM(CASE WHEN progress = 'in_progress' THEN 1 ELSE 0 END) as in_progress,
                SUM(CASE WHEN progress = 'completed' THEN 1 ELSE 0 END) as completed
            ")->first();
        $counts = [
            'total'       => (int) ($countsRaw->total ?? 0),
            'in_progress' => (int) ($countsRaw->in_progress ?? 0),
            'completed'   => (int) ($countsRaw->completed ?? 0),
        ];

        return view('livewire.my-projects', compact('projects', 'counts'))
            ->layout('layouts.app', ['title' => __('我的项目')]);
    }
}
