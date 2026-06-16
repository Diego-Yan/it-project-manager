<?php

namespace App\Livewire\Projects;

use App\Models\Project;
use App\Models\Task;
use Livewire\Component;

class TaskKanban extends Component
{
    public Project $project;

    protected $listeners = ['task-updated' => '$refresh'];

    public function mount(Project $project): void
    {
        $this->project = $project;
    }

    public function moveTask(int $taskId, string $newStatus): void
    {
        $task = Task::where('project_id', $this->project->id)->findOrFail($taskId);
        $user = auth()->user();

        // 只有被分配人或项目负责人可以移动
        $isAssignee = (int)$task->assigned_to === $user->id;
        $canManage = $user->can('view all projects')
            || (int)$this->project->created_by === $user->id
            || $this->project->isLead($user->id);

        if (!$isAssignee && !$canManage) {
            session()->flash('task_error', '只有任务分配人和项目负责人可以移动任务。');
            return;
        }

        // [REVIEW-FIX] P0.3: 状态白名单校验，防止非法值写入
        $allowedStatuses = ['pending_confirmation', 'in_progress', 'completed'];
        if (!in_array($newStatus, $allowedStatuses)) {
            session()->flash('task_error', '无效的任务状态。');
            return;
        }

        // [REVIEW-FIX] R13.4: 跳过无变化的状态更新
        if ($task->status === $newStatus) return;
        $task->update(['status' => $newStatus]);

        if ($newStatus === 'completed') {
            $task->update(['completed_at' => now()]);
        }
        if ($newStatus === 'in_progress' && !$task->confirmed_at) {
            $task->update(['confirmed_at' => now()]);
        }

        $this->project->load('tasks.assignee');
    }

    public function render()
    {
        $tasks = $this->project->tasks()->with('assignee')->get();

        $columns = [
            ['key' => 'pending_confirmation', 'label' => '待确认', 'color' => 'amber'],
            ['key' => 'in_progress',          'label' => '进行中', 'color' => 'sky'],
            ['key' => 'completed',            'label' => '已完成', 'color' => 'green'],
        ];

        return view('livewire.projects.task-kanban', compact('tasks', 'columns'))
            ->layout('layouts.app', ['title' => $this->project->title . ' - 看板']);
    }
}
