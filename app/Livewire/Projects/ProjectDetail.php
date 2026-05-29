<?php

namespace App\Livewire\Projects;

use App\Models\Project;
use App\Models\User;
use Livewire\Component;

class ProjectDetail extends Component
{
    public Project $project;
    public int|string $selectedUserId = '';
    public string $progressNote = '';
    public bool $showMemberModal = false;
    public bool $showProgressModal = false;

    public function mount(Project $project): void
    {
        $this->project = $project->load(['category', 'creator', 'owner', 'members', 'leads', 'logs.user', 'attachments.uploader']);
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
        $old = $this->project->progress;
        $this->project->update(['progress' => $progress]);
        $this->project->logAction(auth()->id(), 'status_changed', [
            'from' => $old, 'to' => $progress,
        ], $this->progressNote);
        $this->progressNote = '';
        $this->showProgressModal = false;
        $this->project->refresh();
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
        $this->project->load('members', 'leads');
    }

    public function removeMember(int $userId): void
    {
        if (!$this->canManageProject()) {
            abort(403);
        }
        $user = User::find($userId);
        $this->project->members()->detach($userId);
        $this->project->logAction(auth()->id(), 'member_removed', ['user' => $user?->name]);
        $this->project->load('members', 'leads');
    }

    public function promoteToLead(int $userId): void
    {
        if (!$this->canManageProject()) {
            abort(403);
        }
        $this->project->members()->updateExistingPivot($userId, ['role' => 'lead']);
        $user = User::find($userId);
        $this->project->logAction(auth()->id(), 'member_added', ['user' => $user?->name, 'role' => 'lead']);
        $this->project->load('members', 'leads');
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
        $this->project->load('members', 'leads');
    }

    public function render()
    {
        $memberIds = $this->project->members()->pluck('user_id')->toArray();
        $availableUsers = User::where('is_active', true)
            ->whereNotIn('id', $memberIds)
            ->orderBy('name')
            ->get(['id', 'name', 'department', 'username']);

        return view('livewire.projects.project-detail', compact('availableUsers'))
            ->layout('layouts.app', ['title' => $this->project->title]);
    }
}
