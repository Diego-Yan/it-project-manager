<?php

namespace App\Livewire\Projects;

use App\Models\Project;
use App\Models\ProjectApplication;
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

    public function applyToProject(int $id): void
    {
        $project = Project::findOrFail($id);
        $user = auth()->user();

        // 已是成员
        if ($project->members()->where('user_id', $user->id)->exists()) {
            session()->flash('error', '你已经是该项目成员。');
            return;
        }

        // 已有待审批的申请
        $existing = ProjectApplication::where('project_id', $id)
            ->where('user_id', $user->id)
            ->where('status', 'pending')
            ->exists();

        if ($existing) {
            session()->flash('error', '已提交过申请，等待审批中。');
            return;
        }

        // updateOrCreate: 如果之前被拒绝，复用旧记录重新申请
        ProjectApplication::updateOrCreate(
            ['project_id' => $id, 'user_id' => $user->id],
            ['status' => 'pending', 'message' => null],
        );

        session()->flash('success', '申请已提交，等待项目负责人审批。');
    }

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

        // 当前用户的成员身份和申请状态
        $memberOfIds = $user->assignedProjects()->pluck('project_id')->toArray();
        $appliedIds = ProjectApplication::where('user_id', $user->id)->where('status', 'pending')->pluck('project_id')->toArray();

        $categories = ProjectCategory::where('is_active', true)->orderBy('sort_order')->get();

        return view('livewire.projects.project-list', compact('projects', 'categories', 'memberOfIds', 'appliedIds'))
            ->layout('layouts.app', ['title' => '项目管理']);
    }
}
