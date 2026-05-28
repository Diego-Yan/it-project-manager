<?php

namespace App\Livewire\Projects;

use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use App\Services\NotificationService;
use Livewire\Component;

class TaskManager extends Component
{
    public Project $project;
    public bool $showTaskForm = false;
    public bool $editingTask = false;
    public ?int $editingTaskId = null;

    public string $taskTitle = '';
    public string $taskDescription = '';
    public int|string $taskAssignedTo = '';
    public string $taskPriority = 'normal';
    public string $taskDueDate = '';

    protected function rules(): array
    {
        return [
            'taskTitle'       => 'required|string|max:200',
            'taskDescription' => 'nullable|string|max:1000',
            'taskAssignedTo'  => 'nullable|exists:users,id',
            'taskPriority'    => 'required|in:not_urgent,normal,urgent',
            'taskDueDate'     => 'nullable|date',
        ];
    }

    public function mount(Project $project): void
    {
        $this->project = $project;
    }

    /** 当前用户是否可以管理指定任务（编辑/删除） */
    public function canManageTask(Task $task): bool
    {
        $user = auth()->user();
        if ($user->can('view all projects')) return true;
        if ((int)$task->created_by === $user->id) return true;
        return $this->project->isLead($user->id);
    }

    // ── 创建任务 ──────────────────────────────────────────
    public function saveTask(): void
    {
        $this->validate();

        $data = [
            'project_id'  => $this->project->id,
            'title'       => $this->taskTitle,
            'description' => $this->taskDescription ?: null,
            'assigned_to' => $this->taskAssignedTo ?: null,
            'priority'    => $this->taskPriority,
            'due_date'    => $this->taskDueDate ?: null,
            'created_by'  => auth()->id(),
        ];

        if ($this->editingTask && $this->editingTaskId) {
            $task = Task::findOrFail($this->editingTaskId);
            $task->update($data);
            session()->flash('task_success', '任务已更新。');
        } else {
            $task = Task::create($data);
            // 如果创建时分配给了自己，自动确认
            if ($task->assigned_to == auth()->id()) {
                $task->update(['status' => 'in_progress', 'confirmed_at' => now()]);
            }
            // 通知被分配人
            if ($task->assigned_to && $task->assigned_to != auth()->id()) {
                try { NotificationService::taskAssigned($task->load(['assignee', 'creator', 'project'])); } catch (\Throwable $e) {}
            }
            session()->flash('task_success', '任务已创建。');
        }

        $this->resetTaskForm();
    }

    // ── 确认任务 ──────────────────────────────────────────
    public function confirmTask(int $taskId): void
    {
        $task = Task::where('project_id', $this->project->id)->findOrFail($taskId);
        if ($task->assigned_to == auth()->id() && $task->status === 'pending_confirmation') {
            $task->update(['status' => 'in_progress', 'confirmed_at' => now()]);
            try { NotificationService::taskConfirmed($task->load(['assignee', 'project'])); } catch (\Throwable $e) {}
        }
    }

    // ── 拒绝任务 ──────────────────────────────────────────
    public function rejectTask(int $taskId): void
    {
        $task = Task::where('project_id', $this->project->id)->findOrFail($taskId);
        if ($task->assigned_to == auth()->id() && $task->status === 'pending_confirmation') {
            $task->update(['assigned_to' => null, 'status' => 'in_progress']);
        }
    }

    // ── 认领任务 ──────────────────────────────────────────
    public function claimTask(int $taskId): void
    {
        $task = Task::where('project_id', $this->project->id)->findOrFail($taskId);
        if (!$task->assigned_to && $task->status !== 'completed') {
            $task->update(['assigned_to' => auth()->id(), 'status' => 'in_progress', 'confirmed_at' => now()]);
        }
    }

    // ── 完成任务 ──────────────────────────────────────────
    public function completeTask(int $taskId): void
    {
        $task = Task::where('project_id', $this->project->id)->findOrFail($taskId);
        if ($task->assigned_to == auth()->id() && $task->status === 'in_progress') {
            $task->update(['status' => 'completed', 'completed_at' => now()]);
            try { NotificationService::taskCompleted($task->load(['assignee', 'project'])); } catch (\Throwable $e) {}
        }
    }

    // ── 删除任务 ──────────────────────────────────────────
    public function deleteTask(int $taskId): void
    {
        $task = Task::where('project_id', $this->project->id)->findOrFail($taskId);
        if (!$this->canManageTask($task)) {
            session()->flash('task_error', '只有任务创建人和项目负责人才能删除任务。');
            return;
        }
        $task->delete();
    }

    // ── 编辑任务 ──────────────────────────────────────────
    public function editTask(int $taskId): void
    {
        $task = Task::where('project_id', $this->project->id)->findOrFail($taskId);
        if (!$this->canManageTask($task)) {
            session()->flash('task_error', '只有任务创建人和项目负责人才能编辑任务。');
            return;
        }
        $this->editingTask    = true;
        $this->editingTaskId  = $task->id;
        $this->showTaskForm   = true;
        $this->taskTitle      = $task->title;
        $this->taskDescription = $task->description ?? '';
        $this->taskAssignedTo = $task->assigned_to ?? '';
        $this->taskPriority   = $task->priority;
        $this->taskDueDate    = $task->due_date?->format('Y-m-d') ?? '';
    }

    public function resetTaskForm(): void
    {
        $this->showTaskForm  = false;
        $this->editingTask   = false;
        $this->editingTaskId = null;
        $this->reset(['taskTitle', 'taskDescription', 'taskAssignedTo', 'taskPriority', 'taskDueDate']);
        $this->taskPriority  = 'normal';
        $this->project->load('tasks.assignee');
    }

    public function render()
    {
        $tasks = $this->project->tasks()->with('assignee')->orderByRaw(
            "CASE status WHEN 'pending_confirmation' THEN 0 WHEN 'in_progress' THEN 1 ELSE 2 END"
        )->orderBy('created_at', 'desc')->get();

        $members = $this->project->members;

        return view('livewire.projects.task-manager', compact('tasks', 'members'));
    }
}
