<?php

namespace App\Livewire;

use App\Models\Task;
use Livewire\Component;

class MyTasks extends Component
{
    public string $filterStatus = '';

    public function confirmTask(int $taskId): void
    {
        $task = Task::findOrFail($taskId);
        if ($task->assigned_to == auth()->id() && $task->status === 'pending_confirmation') {
            $task->update(['status' => 'in_progress', 'confirmed_at' => now()]);
        }
    }

    public function completeTask(int $taskId): void
    {
        $task = Task::findOrFail($taskId);
        if ($task->assigned_to == auth()->id() && $task->status === 'in_progress') {
            $task->update(['status' => 'completed', 'completed_at' => now()]);
        }
    }

    public function render()
    {
        $user = auth()->user();

        $tasks = Task::where('assigned_to', $user->id)
            ->when($this->filterStatus, fn($q) => $q->where('status', $this->filterStatus))
            ->with('project')
            ->orderByRaw("CASE status WHEN 'pending_confirmation' THEN 0 WHEN 'in_progress' THEN 1 ELSE 2 END")
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        $counts = [
            'pending' => Task::where('assigned_to', $user->id)->where('status', 'pending_confirmation')->count(),
            'in_progress' => Task::where('assigned_to', $user->id)->where('status', 'in_progress')->count(),
            'completed' => Task::where('assigned_to', $user->id)->where('status', 'completed')->count(),
        ];

        return view('livewire.my-tasks', compact('tasks', 'counts'))
            ->layout('layouts.app', ['title' => '我的任务']);
    }
}
