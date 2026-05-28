<?php

namespace App\Livewire\Projects;

use App\Models\Project;
use App\Models\User;
use Livewire\Component;

class ProjectDetail extends Component
{
    public Project $project;
    public string $newMemberUsername = '';
    public string $progressNote = '';
    public bool $showMemberModal = false;
    public bool $showProgressModal = false;

    public function mount(Project $project): void
    {
        $this->project = $project->load(['category', 'creator', 'owner', 'members', 'logs.user', 'attachments.uploader']);
    }

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
        $this->authorize('assign project members');
        $user = User::where('username', $this->newMemberUsername)
                    ->orWhere('name', $this->newMemberUsername)
                    ->first();

        if (!$user) {
            $this->addError('newMemberUsername', '未找到该用户');
            return;
        }

        if ($this->project->members()->where('user_id', $user->id)->exists()) {
            $this->addError('newMemberUsername', '该用户已在项目中');
            return;
        }

        $this->project->members()->attach($user->id, ['role' => 'member']);
        $this->project->logAction(auth()->id(), 'member_added', ['user' => $user->name]);
        $this->newMemberUsername = '';
        $this->showMemberModal = false;
        $this->project->load('members');
    }

    public function removeMember(int $userId): void
    {
        $this->authorize('assign project members');
        $user = User::find($userId);
        $this->project->members()->detach($userId);
        $this->project->logAction(auth()->id(), 'member_removed', ['user' => $user?->name]);
        $this->project->load('members');
    }

    public function render()
    {
        return view('livewire.projects.project-detail')
            ->layout('layouts.app', ['title' => $this->project->title]);
    }
}
