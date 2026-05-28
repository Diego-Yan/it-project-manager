<div class="bg-white dark:bg-zinc-900 rounded-2xl border border-zinc-200 dark:border-zinc-800 p-5">
    @if(session('task_success'))
    <div class="mb-3 text-xs text-green-600 dark:text-green-400">{{ session('task_success') }}</div>
    @endif
    @if(session('task_error'))
    <div class="mb-3 text-xs text-red-500">{{ session('task_error') }}</div>
    @endif
    <div class="flex items-center justify-between mb-4">
        <h3 class="text-sm font-semibold text-zinc-900 dark:text-white">
            项目任务
            <span class="ml-2 text-xs font-normal text-zinc-400">
                {{ $tasks->where('status','completed')->count() }}/{{ $tasks->count() }} 已完成
            </span>
        </h3>
        <button type="button" wire:click="$set('showTaskForm', true)"
            class="px-3 py-1.5 text-xs font-medium text-sky-600 dark:text-sky-400 bg-sky-50 dark:bg-sky-950/40 hover:bg-sky-100 dark:hover:bg-sky-950/60 rounded-lg transition-colors">
            + 新建任务
        </button>
    </div>

    {{-- 新建/编辑任务表单 --}}
    @if($showTaskForm)
    <div class="mb-4 p-4 bg-zinc-50 dark:bg-zinc-800/50 rounded-xl border border-zinc-200 dark:border-zinc-700">
        <div class="space-y-3">
            <input type="text" wire:model="taskTitle" placeholder="任务标题"
                class="w-full px-3 py-2 text-sm bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-700 rounded-lg text-zinc-900 dark:text-white placeholder-zinc-400 focus:outline-none focus:border-sky-500">
            @error('taskTitle') <p class="text-xs text-red-500">{{ $message }}</p> @enderror

            <textarea wire:model="taskDescription" rows="2" placeholder="任务描述（可选）"
                class="w-full px-3 py-2 text-sm bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-700 rounded-lg text-zinc-900 dark:text-white placeholder-zinc-400 focus:outline-none focus:border-sky-500 resize-none"></textarea>

            <div class="grid sm:grid-cols-3 gap-3">
                <select wire:model="taskAssignedTo"
                    class="text-sm bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-700 rounded-lg px-3 py-2 text-zinc-700 dark:text-zinc-300 focus:outline-none focus:border-sky-500">
                    <option value="">不分配（可认领）</option>
                    @foreach($members as $m)
                    <option value="{{ $m->id }}">{{ $m->name }}</option>
                    @endforeach
                </select>
                <select wire:model="taskPriority"
                    class="text-sm bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-700 rounded-lg px-3 py-2 text-zinc-700 dark:text-zinc-300 focus:outline-none focus:border-sky-500">
                    <option value="not_urgent">不紧急</option>
                    <option value="normal">一般</option>
                    <option value="urgent">紧急</option>
                </select>
                <input type="date" wire:model="taskDueDate"
                    class="text-sm bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-700 rounded-lg px-3 py-2 text-zinc-700 dark:text-zinc-300 focus:outline-none focus:border-sky-500">
            </div>

            <div class="flex gap-2 justify-end">
                <button wire:click="resetTaskForm"
                    class="px-3 py-1.5 text-xs text-zinc-500 hover:text-zinc-700 dark:hover:text-zinc-300">取消</button>
                <button wire:click="saveTask"
                    class="px-4 py-1.5 text-xs font-medium text-white bg-sky-600 hover:bg-sky-500 rounded-lg transition-colors">
                    {{ $editingTask ? '保存修改' : '创建任务' }}
                </button>
            </div>
        </div>
    </div>
    @endif

    {{-- 任务列表 --}}
    @if($tasks->isEmpty())
    <p class="text-center text-xs text-zinc-400 py-6">暂无任务</p>
    @else
    <div class="space-y-2">
        @foreach($tasks as $task)
        @php
        $sColor = $task->statusColor;
        $pColor = $task->priorityColor;
        $isMine = $task->assigned_to == auth()->id();
        @endphp
        <div class="flex items-start gap-3 p-3 rounded-xl border border-zinc-100 dark:border-zinc-800 bg-zinc-50/50 dark:bg-zinc-800/30 hover:bg-zinc-50 dark:hover:bg-zinc-800/50 transition-colors {{ $task->status === 'completed' ? 'opacity-60' : '' }}">
            {{-- 状态指示 --}}
            <div class="shrink-0 mt-0.5">
                @if($task->status === 'completed')
                <div class="w-5 h-5 rounded-full bg-green-100 dark:bg-green-900/50 flex items-center justify-center">
                    <svg class="w-3 h-3 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M4.5 12.75l6 6 9-13.5"/>
                    </svg>
                </div>
                @elseif($task->status === 'pending_confirmation')
                <div class="w-5 h-5 rounded-full bg-amber-100 dark:bg-amber-900/50 flex items-center justify-center">
                    <svg class="w-3 h-3 text-amber-600 dark:text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M12 6v6h4.5m6 0a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                @else
                <div class="w-5 h-5 rounded-full border-2 border-sky-300 dark:border-sky-600"></div>
                @endif
            </div>

            {{-- 内容 --}}
            <div class="flex-1 min-w-0">
                <div class="flex items-center gap-2 flex-wrap">
                    <span class="text-sm font-medium text-zinc-900 dark:text-white {{ $task->status === 'completed' ? 'line-through' : '' }}">{{ $task->title }}</span>
                    <span class="inline-flex items-center px-1.5 py-0.5 rounded text-xs font-medium bg-{{ $pColor }}-100 dark:bg-{{ $pColor }}-950/40 text-{{ $pColor }}-700 dark:text-{{ $pColor }}-400">
                        {{ $task->priorityLabel }}
                    </span>
                    <span class="inline-flex items-center px-1.5 py-0.5 rounded text-xs font-medium bg-{{ $sColor }}-100 dark:bg-{{ $sColor }}-950/40 text-{{ $sColor }}-700 dark:text-{{ $sColor }}-400">
                        {{ $task->statusLabel }}
                    </span>
                </div>

                <div class="flex items-center gap-2 mt-1 text-xs text-zinc-500">
                    @if($task->assignee)
                    <span>{{ $task->assignee->name }}</span>
                    @else
                    <span class="text-zinc-400">待认领</span>
                    @endif
                    @if($task->due_date)
                    <span>· {{ $task->due_date->format('m/d') }} 截止</span>
                    @endif
                </div>

                @if($task->description)
                <p class="text-xs text-zinc-400 mt-1 line-clamp-1">{{ $task->description }}</p>
                @endif
            </div>

            {{-- 操作按钮 --}}
            <div class="flex items-center gap-1.5 shrink-0 flex-wrap justify-end">
                {{-- 待确认：被分配人可确认/拒绝 --}}
                @if($isMine && $task->status === 'pending_confirmation')
                <button wire:click="confirmTask({{ $task->id }})"
                    class="px-3 py-1.5 text-xs font-medium text-green-700 dark:text-green-400 bg-green-100 dark:bg-green-950/40 hover:bg-green-200 dark:hover:bg-green-950/60 rounded-lg transition-colors min-w-[44px]">
                    确认
                </button>
                <button wire:click="rejectTask({{ $task->id }})"
                    class="px-3 py-1.5 text-xs text-zinc-500 hover:text-red-500 rounded-lg transition-colors min-w-[44px]">
                    拒绝
                </button>
                @endif

                {{-- 未分配：成员可认领 --}}
                @if(!$task->assignee && $task->status !== 'completed')
                @if($members->contains('id', auth()->id()))
                <button wire:click="claimTask({{ $task->id }})"
                    class="px-3 py-1.5 text-xs font-medium text-sky-700 dark:text-sky-400 bg-sky-100 dark:bg-sky-950/40 hover:bg-sky-200 dark:hover:bg-sky-950/60 rounded-lg transition-colors min-w-[44px]">
                    认领
                </button>
                @endif
                @endif

                {{-- 进行中：被分配人可完成 --}}
                @if($isMine && $task->status === 'in_progress')
                <button wire:click="completeTask({{ $task->id }})"
                    class="px-3 py-1.5 text-xs font-medium text-green-700 dark:text-green-400 bg-green-100 dark:bg-green-950/40 hover:bg-green-200 dark:hover:bg-green-950/60 rounded-lg transition-colors min-w-[44px]">
                    完成
                </button>
                @endif

                {{-- 编辑/删除：仅创建人、负责人、管理员可见 --}}
                @if($this->canManageTask($task))
                <button wire:click="editTask({{ $task->id }})"
                    class="p-1.5 rounded text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-300 hover:bg-zinc-100 dark:hover:bg-zinc-800 transition-colors min-w-[36px]">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931z"/>
                    </svg>
                </button>
                <button wire:click="deleteTask({{ $task->id }})"
                    wire:confirm="确定删除此任务？"
                    class="p-1.5 rounded text-zinc-400 hover:text-red-500 hover:bg-red-50 dark:hover:bg-red-950/30 transition-colors min-w-[36px]">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
                @endif
            </div>
        </div>
        @endforeach
    </div>
    @endif
</div>
