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
    public array $filterProgress = [];
    public array $filterCategory = [];
    public array $filterType = [];
    public array $filterUrgency = [];
    public array $filterImportance = [];
    public array $filterRegion = [];

    public function updatingSearch(): void { $this->resetPage(); }

    public function toggleFilter(string $field, string $value): void
    {
        // [REVIEW-FIX] C1: allowlist 防止通过 Livewire 请求篡改任意属性
        $allowed = ['filterProgress', 'filterCategory', 'filterType', 'filterUrgency', 'filterImportance', 'filterRegion'];
        if (!in_array($field, $allowed, true)) return;

        $key = array_search($value, $this->{$field});
        if ($key !== false) {
            unset($this->{$field}[$key]);
        } else {
            $this->{$field}[] = $value;
        }
        $this->{$field} = array_values($this->{$field});
        $this->resetPage();
    }

    public function clearFilters(): void
    {
        $this->filterProgress = [];
        $this->filterCategory = [];
        $this->filterType = [];
        $this->filterUrgency = [];
        $this->filterImportance = [];
        $this->filterRegion = [];
        $this->search = '';
        $this->resetPage();
    }

    public function applyToProject(int $id): void
    {
        $project = Project::findOrFail($id);
        $user = auth()->user();

        // 已是成员
        if ($project->members()->where('user_id', $user->id)->exists()) {
            session()->flash('error', __('你已经是该项目成员。'));
            return;
        }

        // 已有待审批的申请
        $existing = ProjectApplication::where('project_id', $id)
            ->where('user_id', $user->id)
            ->where('status', 'pending')
            ->exists();

        if ($existing) {
            session()->flash('error', __('已提交过申请，等待审批中。'));
            return;
        }

        // updateOrCreate: 如果之前被拒绝，复用旧记录重新申请
        ProjectApplication::updateOrCreate(
            ['project_id' => $id, 'user_id' => $user->id],
            ['status' => 'pending', 'message' => null],
        );

        session()->flash('success', __('申请已提交，等待项目负责人审批。'));
    }

    public function deleteProject(int $id): void
    {
        $project = Project::findOrFail($id);
        $this->authorize('delete projects');
        $project->delete();
        session()->flash('success', __('项目已删除。'));
    }

    public function render()
    {
        $user = auth()->user();
        $isAdmin = $user->can('view all projects');

        $projects = Project::with(['category', 'creator', 'members', 'region'])
            ->when(!$isAdmin, function ($q) use ($user) {
                // [REVIEW-FIX] I2: orWhere 包裹在嵌套 where() 防止 AND/OR 分组错乱
                $q->where(function ($sub) use ($user) {
                    $sub->whereHas('members', fn($m) => $m->where('user_id', $user->id))
                        ->orWhere('created_by', $user->id);
                });
            })
            ->when($this->search, function ($q) {
                // [REVIEW-FIX] I1: 转义 LIKE 通配符防止 %_ 被误匹配
                $escaped = addcslashes($this->search, '%_');
                $q->where('title', 'like', "%{$escaped}%");
            })
            ->when(!empty($this->filterProgress), fn($q) => $q->whereIn('progress', $this->filterProgress))
            ->when(!empty($this->filterCategory), fn($q) => $q->whereIn('category_id', $this->filterCategory))
            ->when(!empty($this->filterType), fn($q) => $q->whereIn('type', $this->filterType))
            ->when(!empty($this->filterUrgency), fn($q) => $q->whereIn('urgency', $this->filterUrgency))
            ->when(!empty($this->filterImportance), fn($q) => $q->whereIn('importance', $this->filterImportance))
            ->when(!empty($this->filterRegion), fn($q) => $q->whereIn('region_id', $this->filterRegion))
            ->latest()
            ->paginate(15);

        // [REVIEW-FIX] P2.12: 成员身份和申请状态缓存5分钟（登录期间不变）
        $memberOfIds = \Illuminate\Support\Facades\Cache::remember("member_of:{$user->id}", 300, fn() =>
            $user->assignedProjects()->pluck('project_id')->toArray()
        );
        $appliedIds = \Illuminate\Support\Facades\Cache::remember("applied_ids:{$user->id}", 300, fn() =>
            ProjectApplication::where('user_id', $user->id)->where('status', 'pending')->pluck('project_id')->toArray()
        );

        $categories = ProjectCategory::where('is_active', true)->orderBy('sort_order')->get();
        $regions = \App\Models\Region::orderBy('sort_order')->get();

        return view('livewire.projects.project-list', compact('projects', 'categories', 'regions', 'memberOfIds', 'appliedIds'))
            ->layout('layouts.app', ['title' => __('项目管理')]);
    }
}
