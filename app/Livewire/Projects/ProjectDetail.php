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
        // [REVIEW-FIX] SP13.6: 事务包裹 update + logAction 保证一致性
        \DB::transaction(function () use ($progress, $old) {
            $this->project->update(['progress' => $progress]);
            $this->project->logAction(auth()->id(), 'status_changed', [
                'from' => $old, 'to' => $progress,
            ], $this->progressNote);
        });
        $this->progressNote = '';
        $this->showProgressModal = false;

        // [REVIEW-FIX] CRIT-2: 项目完成时发送 webhook + 刷新缓存
        if ($progress === 'completed') {
            $this->project->load('members');
            foreach ($this->project->members as $member) {
                \App\View\Composers\SidebarComposer::flushForUser($member->id);
            }
            try {
                \App\Services\NotificationService::send('project.completed', [
                    'project_id' => $this->project->id,
                    'project_title' => $this->project->title,
                    'user_name' => auth()->user()->name,
                    'message' => "项目已完成: {$this->project->title}",
                    'status_from' => $old,
                    'status_to' => 'completed',
                ]);
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::warning("Webhook failed: project completed", ["error" => $e->getMessage()]);
            }
        }
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

        // [REVIEW-FIX] SP13.4: 事务包裹 attach + logAction 保证一致性
        \DB::transaction(function () use ($user) {
            $this->project->members()->attach($user->id, ['role' => 'member']);
            $this->project->logAction(auth()->id(), 'member_added', ['user' => $user->name]);
        });
        $this->selectedUserId = '';
        $this->showMemberModal = false;
        $this->flushMemberCaches();
        \App\View\Composers\SidebarComposer::flushForUser($user->id);

        // [REVIEW-FIX] CRIT-3: 成员加入 webhook 通知
        try {
            \App\Services\NotificationService::send('member.joined', [
                'project_id' => $this->project->id,
                'project_title' => $this->project->title,
                'user_name' => $user->name,
                'message' => "{$user->name} 加入了项目 {$this->project->title}",
            ]);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning("Webhook failed: member joined", ["error" => $e->getMessage()]);
        }
    }

    public function removeMember(int $userId): void
    {
        if (!$this->canManageProject()) {
            abort(403);
        }
        // [REVIEW-FIX] SP13.10: 不能移除自己或最后一个成员
        if ($userId === auth()->id()) {
            session()->flash('error', '不能将自己移出项目。');
            return;
        }
        if ($this->project->members()->count() <= 1) {
            session()->flash('error', '不能移除唯一的项目成员。');
            return;
        }
        $user = User::find($userId);
        // [REVIEW-FIX] SP13.5: 事务包裹 detach + logAction 保证一致性
        \DB::transaction(function () use ($userId, $user) {
            $this->project->members()->detach($userId);
            $this->project->logAction(auth()->id(), 'member_removed', ['user' => $user?->name]);
        });
        $this->flushMemberCaches();
        // [REVIEW-FIX] I6: 刷新被移除成员的侧边栏计数
        \App\View\Composers\SidebarComposer::flushForUser($userId);
        // [REVIEW-FIX] SP7.2: render() 中已调用 loadProject()，此处冗余
    }

    public function promoteToLead(int $userId): void
    {
        if (!$this->canManageProject()) {
            abort(403);
        }
        // [REVIEW-FIX] SP13.7: 事务包裹 updateExistingPivot + logAction 保证一致性
        \DB::transaction(function () use ($userId) {
            $this->project->members()->updateExistingPivot($userId, ['role' => 'lead']);
            $user = User::find($userId);
            $this->project->logAction(auth()->id(), 'member_added', ['user' => $user?->name, 'role' => 'lead']);
        });
        // [REVIEW-FIX] SP7.2: render() 中已调用 loadProject()，此处冗余
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
        // [REVIEW-FIX] SP13.8: 事务包裹 updateExistingPivot + logAction 保证一致性
        \DB::transaction(function () use ($userId) {
            $this->project->members()->updateExistingPivot($userId, ['role' => 'member']);
            $user = User::find($userId);
            $this->project->logAction(auth()->id(), 'member_added', ['user' => $user?->name, 'role' => 'member']);
        });
        // [REVIEW-FIX] SP7.2: render() 中已调用 loadProject()，此处冗余
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
