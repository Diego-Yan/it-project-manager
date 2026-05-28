<?php

namespace App\Livewire\Projects;

use App\Models\Project;
use App\Models\ProjectCategory;
use Livewire\Component;
use Livewire\WithPagination;

class ProjectList extends Component
{
    use WithPagination;

    public string $search = '';
    public string $filterProgress = '';
    public string $filterCategory = '';
    public string $filterType = '';

    protected $queryString = [
        'search'          => ['except' => ''],
        'filterProgress'  => ['except' => ''],
        'filterCategory'  => ['except' => ''],
    ];

    public function updatingSearch(): void { $this->resetPage(); }
    public function updatingFilterProgress(): void { $this->resetPage(); }
    public function updatingFilterCategory(): void { $this->resetPage(); }

    public function deleteProject(int $id): void
    {
        $project = Project::findOrFail($id);
        $this->authorize('delete projects');
        $project->delete();
        session()->flash('success', '项目已删除。');
    }

    public function render()
    {
        $user = auth()->user();
        $isAdmin = $user->can('view all projects');

        $projects = Project::with(['category', 'creator', 'members'])
            ->when(!$isAdmin, function ($q) use ($user) {
                $q->whereHas('members', fn($m) => $m->where('user_id', $user->id))
                  ->orWhere('created_by', $user->id);
            })
            ->when($this->search, fn($q) => $q->where('title', 'like', "%{$this->search}%"))
            ->when($this->filterProgress, fn($q) => $q->where('progress', $this->filterProgress))
            ->when($this->filterCategory, fn($q) => $q->where('category_id', $this->filterCategory))
            ->when($this->filterType, fn($q) => $q->where('type', $this->filterType))
            ->latest()
            ->paginate(15);

        $categories = ProjectCategory::where('is_active', true)->orderBy('sort_order')->get();

        return view('livewire.projects.project-list', compact('projects', 'categories'))
            ->layout('layouts.app', ['title' => '项目管理']);
    }
}
