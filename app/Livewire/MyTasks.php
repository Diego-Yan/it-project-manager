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
        if ((int)$task->assigned_to === auth()->id() && $task->status === 'pending_confirmation') { // [REVIEW-FIX] R15.5
            $task->update(['status' => 'in_progress', 'confirmed_at' => now()]);
            // [REVIEW-FIX] C7: 刷新侧边栏待确认任务计数
            \App\View\Composers\SidebarComposer::flushForUser(auth()->id());
            // [REVIEW-FIX] R15.3: 确认任务时通知创建者
            // [REVIEW-FIX] SP12.2: 通知失败时记录日志，不吞没异常
            try { \App\Services\NotificationService::taskConfirmed($task->load(['assignee', 'project'])); } catch (\Throwable $e) { \Illuminate\Support\Facades\Log::warning('Notification failed in confirmTask', ['error' => $e->getMessage(), 'task_id' => $taskId]); }
        }
    }

    public function completeTask(int $taskId): void
    {
        $task = Task::findOrFail($taskId);
        if ((int)$task->assigned_to === auth()->id() && $task->status === 'in_progress') { // [REVIEW-FIX] R15.5
            $task->update(['status' => 'completed', 'completed_at' => now()]);
            // [REVIEW-FIX] C7: 刷新侧边栏计数
            \App\View\Composers\SidebarComposer::flushForUser(auth()->id());
            // [REVIEW-FIX] R15.3: 完成任务时通知创建者
            try { \App\Services\NotificationService::taskCompleted($task->load(['assignee', 'project'])); } catch (\Throwable $e) { \Illuminate\Support\Facades\Log::warning('Notification failed in completeTask', ['error' => $e->getMessage(), 'task_id' => $taskId]); }
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

        // [REVIEW-FIX] R3.5: 3次独立 COUNT → 1次 GROUP BY
        $countsRaw = Task::where('assigned_to', $user->id)
            ->selectRaw("
                SUM(CASE WHEN status = 'pending_confirmation' THEN 1 ELSE 0 END) as pending,
                SUM(CASE WHEN status = 'in_progress' THEN 1 ELSE 0 END) as in_progress,
                SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed
            ")->first();
        $counts = [
            'pending'     => (int) ($countsRaw->pending ?? 0),
            'in_progress' => (int) ($countsRaw->in_progress ?? 0),
            'completed'   => (int) ($countsRaw->completed ?? 0),
        ];

        return view('livewire.my-tasks', compact('tasks', 'counts'))
            ->layout('layouts.app', ['title' => '我的任务']);
    }
}
