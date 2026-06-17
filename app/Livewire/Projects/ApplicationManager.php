<?php

namespace App\Livewire\Projects;

use App\Models\Project;
use App\Models\ProjectApplication;
use Livewire\Component;

class ApplicationManager extends Component
{
    public Project $project;

    // [REVIEW-FIX] H1: 隐藏 project 模型，防止 Livewire 序列化到前端
    protected function getPropertyList(): array
    {
        return array_diff(parent::getPropertyList(), ['project']);
    }

    public function mount(Project $project): void
    {
        $this->project = $project;
    }

    /** Permission guard shared by all actions */
    private function guard(): void
    {
        $user = auth()->user();
        if ($user->can('view all projects')) return;
        if ((int)$this->project->created_by === $user->id) return;
        if ($this->project->isLead($user->id)) return;
        abort(403);
    }

    // [REVIEW-FIX] SP13.1: 事务包裹保证申请审批 + 成员添加原子性
    public function approve(int $appId): void
    {
        $this->guard();
        \DB::transaction(function () use ($appId) {
            $app = ProjectApplication::where('project_id', $this->project->id)->findOrFail($appId);
            $app->update(['status' => 'approved']);
            $this->project->members()->syncWithoutDetaching([$app->user_id => ['role' => 'member']]);
            // [REVIEW-FIX] SP12.9: null-safe user access
            $this->project->logAction(auth()->id(), 'member_added', ['user' => $app->user?->name ?? '未知用户', 'via' => 'application']);
            session()->flash('app_success', ($app->user?->name ?? '未知用户') . ' 已加入项目。');  // [REVIEW-FIX] SP12.9: null-safe user access
        });
    }

    public function reject(int $appId): void
    {
        $this->guard();
        $app = ProjectApplication::where('project_id', $this->project->id)->findOrFail($appId);
        $app->update(['status' => 'rejected']);
        session()->flash('app_success', '已拒绝该申请。');
    }

    public function render()
    {
        $applications = $this->project->applications()
            ->with('user')
            ->where('status', 'pending')
            ->latest()
            ->get();

        return view('livewire.projects.application-manager', compact('applications'));
    }
}
