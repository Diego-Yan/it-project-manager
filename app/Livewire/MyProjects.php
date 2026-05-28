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

        $counts = [
            'total'       => $base->count(),
            'in_progress' => (clone $base)->where('progress', 'in_progress')->count(),
            'completed'   => (clone $base)->where('progress', 'completed')->count(),
        ];

        return view('livewire.my-projects', compact('projects', 'counts'))
            ->layout('layouts.app', ['title' => '我的项目']);
    }
}
