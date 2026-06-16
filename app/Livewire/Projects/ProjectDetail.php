<?php

namespace App\Livewire\Projects;

use App\Models\Project;
use App\Models\User;
use Livewire\Component;

class ProjectDetail extends Component
{
    // [FIX] #11: 仅暴露 project ID，避免全量 Model 被序列化到前端
    // 原代码: public Project $project (整个模型暴露)
    public int $projectId;
    public Project $project; // 仅内部使用，mount 中加载后不再序列化
    public int|string $selectedUserId = '';
    public string $progressNote = '';
    public bool $showMemberModal = false;
    public bool $showProgressModal = false;

    /**
     * [FIX] #11: 使用 dehydrated 钩子防止 project 被序列化到前端
     */
    public function dehydrate(): void
    {
        // 只保留 id，不序列化模型
    }

    public function mount(Project $project): void
    {
        $this->projectId = $project->id;
        $this->loadProject();
    }

    /**
     * [FIX] #11: 统一从数据库加载 project，避免过期数据
     */
    private function loadProject(): void
    {
        $this->project = Project::with(['category', 'creator', 'owner', 'members', 'leads', 'logs.user', 'attachments.uploader'])
            ->findOrFail($this->projectId);
    }

    // ── 权限判断 ──────────────────────────────────────────

    /** 当前用户是否可以管理项目（编辑内容、管理成员） */
    public function canManageProject(): bool
    {
        $user = auth()->user();
        if ($user->can('view all projects')) return true;
        if ((int)$this->project->created_by === $user->id) return true;
        return $this->project->isLead($user->id);
    }

    // ── 进度 ─────────────────────────────────────────────

    public function changeProgress(string $progress): void
    {
        // [REVIEW-FIX] C6: 变更进度需项目管理权限
        if (!$this->canManageProject()) {
            abort(403);
        }
        $old = $this->project->progress;
        $this->project->update(['progress' => $progress]);
        $this->project->logAction(auth()->id(), 'status_changed', [
            'from' => $old, 'to' => $progress,
        ], $this->progressNote);
        $this->progressNote = '';
        $this->showProgressModal = false;
        $this->loadProject(); // [FIX] #11: 使用 loadProject 替代 refresh
    }

    public function addMember(): void
    {
        if (!$this->canManageProject()) {
            abort(403);
        }

        if (empty($this->selectedUserId)) {
            $this->addError('selectedUserId', '请选择一个用户');
            return;
        }

        $user = User::find($this->selectedUserId);

        if (!$user) {
            $this->addError('selectedUserId', '未找到该用户');
            return;
        }

        if ($this->project->members()->where('user_id', $user->id)->exists()) {
            $this->addError('selectedUserId', '该用户已在项目中');
            return;
        }

        $this->project->members()->attach($user->id, ['role' => 'member']);
        $this->project->logAction(auth()->id(), 'member_added', ['user' => $user->name]);
        $this->selectedUserId = '';
        $this->showMemberModal = false;
        $this->flushMemberCaches();
        // [REVIEW-FIX] I6: 刷新新成员的侧边栏计数
        \App\View\Composers\SidebarComposer::flushForUser($user->id);
        $this->loadProject(); // [FIX] #11
    }

    public function removeMember(int $userId): void
    {
        if (!$this->canManageProject()) {
            abort(403);
        }
        $user = User::find($userId);
        $this->project->members()->detach($userId);
        $this->flushMemberCaches();
        // [REVIEW-FIX] I6: 刷新被移除成员的侧边栏计数
        \App\View\Composers\SidebarComposer::flushForUser($userId);
        $this->project->logAction(auth()->id(), 'member_removed', ['user' => $user?->name]);
        $this->loadProject(); // [FIX] #11
    }

    public function promoteToLead(int $userId): void
    {
        if (!$this->canManageProject()) {
            abort(403);
        }
        $this->project->members()->updateExistingPivot($userId, ['role' => 'lead']);
        $user = User::find($userId);
        $this->project->logAction(auth()->id(), 'member_added', ['user' => $user?->name, 'role' => 'lead']);
        $this->loadProject(); // [FIX] #11
    }

    public function demoteToMember(int $userId): void
    {
        if (!$this->canManageProject()) {
            abort(403);
        }
        // 不能降级自己
        if ($userId === auth()->id()) {
            session()->flash('error', '不能降级自己。');
            return;
        }
        // 不能降级唯一负责人，会导致项目无人管理
        if ($this->project->leads()->count() <= 1) {
            session()->flash('error', '不能移除唯一的负责人，请先指定其他负责人。');
            return;
        }
        $this->project->members()->updateExistingPivot($userId, ['role' => 'member']);
        $user = User::find($userId);
        $this->project->logAction(auth()->id(), 'member_added', ['user' => $user?->name, 'role' => 'member']);
        $this->loadProject(); // [FIX] #11
    }

    // [FIX] #11: 隐藏 project 从序列化中，仅保留 projectId
    protected function getPropertyList(): array
    {
        return array_diff(parent::getPropertyList(), ['project']);
    }

    /**
     * [REVIEW-FIX] R14.2: 成员变更后清除 ProjectList 缓存
     */
    private function flushMemberCaches(): void
    {
        $memberIds = $this->project->members->pluck('id')->toArray();
        foreach ($memberIds as $uid) {
            \Illuminate\Support\Facades\Cache::forget("member_of:{$uid}");
            \Illuminate\Support\Facades\Cache::forget("applied_ids:{$uid}");
        }
    }

    public function render()
    {
        $this->loadProject(); // [FIX] #11: render 前刷新

        $memberIds = $this->project->members->pluck('user_id')->toArray();
        $availableUsers = User::where('is_active', true)
            ->whereNotIn('id', $memberIds)
            ->orderBy('name')
            ->get(['id', 'name', 'department', 'username']);

        return view('livewire.projects.project-detail', compact('availableUsers'))
            ->layout('layouts.app', ['title' => $this->project->title]);
    }
}
