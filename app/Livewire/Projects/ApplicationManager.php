<?php

namespace App\Livewire\Projects;

use App\Models\Project;
use App\Models\ProjectApplication;
use Livewire\Component;

class ApplicationManager extends Component
{
    public Project $project;

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

    public function approve(int $appId): void
    {
        $this->guard();
        $app = ProjectApplication::where('project_id', $this->project->id)->findOrFail($appId);
        $app->update(['status' => 'approved']);
        $this->project->members()->syncWithoutDetaching([$app->user_id => ['role' => 'member']]);
        $this->project->logAction(auth()->id(), 'member_added', ['user' => $app->user->name, 'via' => 'application']);
        session()->flash('app_success', "{$app->user->name} 已加入项目。");
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
