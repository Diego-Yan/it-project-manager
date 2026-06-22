<?php

namespace App\Livewire\Projects;

use App\Models\Project;
use App\Models\Task;
use Livewire\Component;

class TaskKanban extends Component
{
    public Project $project;

    protected $listeners = ['task-updated' => '$refresh'];

    // [REVIEW-FIX] H1: 隐藏 project 模型，防止 Livewire 序列化到前端
    protected function getPropertyList(): array
    {
        return array_diff(parent::getPropertyList(), ['project']);
    }

    public function mount(Project $project): void
    {
        // [REVIEW-FIX-R6 #3 P2] IDOR 防护：与 ProjectDetail 一致，非管理员只能查看
        // 自己创建或作为成员参与的项目看板。原 mount() 无访问控制，任意用户可通过
        // 修改 URL 查看非成员项目的所有任务。
        $user = auth()->user();
        if (!$user->can('view all projects')) {
            $isMember = $project->members()->where('user_id', $user->id)->exists()
                || (int) $project->created_by === $user->id;
            if (!$isMember) {
                abort(403, __('无权访问此项目。'));
            }
        }
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
            session()->flash('task_error', __('只有任务分配人和项目负责人可以移动任务。'));
            return;
        }

        // [REVIEW-FIX] P0.3: 状态白名单校验，防止非法值写入
        $allowedStatuses = ['pending_confirmation', 'in_progress', 'completed'];
        if (!in_array($newStatus, $allowedStatuses)) {
            session()->flash('task_error', __('无效的任务状态。'));
            return;
        }

        // [REVIEW-FIX] R13.4: 跳过无变化的状态更新
        if ($task->status === $newStatus) return;

        // [REVIEW-FIX] SP13.3: 事务包裹多个 update 保证任务状态一致性
        \DB::transaction(function () use ($task, $newStatus) {
            $task->update(['status' => $newStatus]);

            if ($newStatus === 'completed') {
                $task->update(['completed_at' => now()]);
            }
            if ($newStatus === 'in_progress' && !$task->confirmed_at) {
                $task->update(['confirmed_at' => now()]);
            }
        });

        $this->project->load('tasks.assignee');
    }

    public function render()
    {
        $tasks = $this->project->tasks()->with('assignee')->get();

        $columns = [
            ['key' => 'pending_confirmation', 'label' => __('待确认'), 'color' => 'amber'],
            ['key' => 'in_progress',          'label' => __('进行中'), 'color' => 'sky'],
            ['key' => 'completed',            'label' => __('已完成'), 'color' => 'green'],
        ];

        return view('livewire.projects.task-kanban', compact('tasks', 'columns'))
            ->layout('layouts.app', ['title' => $this->project->title . ' - ' . __('看板')]);
    }
}
