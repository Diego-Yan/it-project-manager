<div class="bg-white dark:bg-zinc-900 rounded-2xl border border-zinc-200 dark:border-zinc-800 p-5">
    <div class="flex items-center justify-between mb-4">
        <h3 class="text-sm font-semibold text-zinc-900 dark:text-white">任务看板</h3>
        <a href="{{ route('projects.show', $project) }}" class="text-xs text-zinc-400 hover:text-sky-500">列表视图</a>
    </div>

    @if(session('task_error'))
    <div class="mb-3 text-xs text-red-500">{{ session('task_error') }}</div>
    @endif

    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        @foreach($columns as $col)
        @php $colTasks = $tasks->where('status', $col['key']); @endphp
        <div class="rounded-xl border border-zinc-200 dark:border-zinc-700 bg-zinc-50 dark:bg-zinc-800/30 overflow-hidden">
            {{-- 列头 --}}
            <div class="px-4 py-3 border-b border-zinc-200 dark:border-zinc-700 bg-{{ $col['color'] }}-50 dark:bg-{{ $col['color'] }}-950/30">
                <div class="flex items-center justify-between">
                    <span class="text-sm font-semibold text-{{ $col['color'] }}-700 dark:text-{{ $col['color'] }}-400">
                        {{ $col['label'] }}
                    </span>
                    <span class="inline-flex items-center justify-center min-w-[22px] h-5 px-1.5 rounded-full bg-{{ $col['color'] }}-200 dark:bg-{{ $col['color'] }}-900/50 text-{{ $col['color'] }}-700 dark:text-{{ $col['color'] }}-400 text-xs font-bold">
                        {{ $colTasks->count() }}
                    </span>
                </div>
            </div>

            {{-- 任务卡片 --}}
            <div class="p-3 space-y-2 min-h-[100px]">
                @forelse($colTasks as $task)
                @php $pColor = $task->priorityColor; @endphp
                <div class="bg-white dark:bg-zinc-900 rounded-xl border border-zinc-200 dark:border-zinc-700 p-3 shadow-sm hover:shadow-md transition-shadow">
                    <div class="flex items-start justify-between gap-2 mb-2">
                        <span class="text-sm font-medium text-zinc-900 dark:text-white leading-snug">{{ $task->title }}</span>
                    </div>

                    <div class="flex items-center gap-1.5 mb-2 flex-wrap">
                        <span class="inline-flex px-1.5 py-0.5 rounded text-xs font-medium bg-{{ $pColor }}-100 dark:bg-{{ $pColor }}-950/40 text-{{ $pColor }}-700 dark:text-{{ $pColor }}-400">
                            {{ $task->priorityLabel }}
                        </span>
                        @if($task->assignee)
                        <span class="text-xs text-zinc-500">{{ $task->assignee->name }}</span>
                        @else
                        <span class="text-xs text-zinc-400">待认领</span>
                        @endif
                    </div>

                    @if($task->description)
                    <p class="text-xs text-zinc-400 mb-2 line-clamp-2">{{ $task->description }}</p>
                    @endif

                    @if($task->due_date)
                    <p class="text-xs {{ $task->due_date->isPast() && $task->status !== 'completed' ? 'text-red-500' : 'text-zinc-400' }} mb-2">
                        {{ $task->due_date->format('m/d') }} 截止
                    </p>
                    @endif

                    {{-- 移动按钮 --}}
                    <div class="flex gap-1 pt-2 border-t border-zinc-100 dark:border-zinc-800">
                        @if($col['key'] === 'pending_confirmation')
                        <button wire:click="moveTask({{ $task->id }}, 'in_progress')"
                            class="flex-1 px-2 py-1 text-xs text-sky-600 dark:text-sky-400 hover:bg-sky-50 dark:hover:bg-sky-950/30 rounded transition-colors">
                            → 进行中
                        </button>
                        <button wire:click="moveTask({{ $task->id }}, 'completed')"
                            class="flex-1 px-2 py-1 text-xs text-green-600 dark:text-green-400 hover:bg-green-50 dark:hover:bg-green-950/30 rounded transition-colors">
                            → 完成
                        </button>
                        @elseif($col['key'] === 'in_progress')
                        <button wire:click="moveTask({{ $task->id }}, 'pending_confirmation')"
                            class="flex-1 px-2 py-1 text-xs text-amber-600 dark:text-amber-400 hover:bg-amber-50 dark:hover:bg-amber-950/30 rounded transition-colors">
                            ← 退回
                        </button>
                        <button wire:click="moveTask({{ $task->id }}, 'completed')"
                            class="flex-1 px-2 py-1 text-xs text-green-600 dark:text-green-400 hover:bg-green-50 dark:hover:bg-green-950/30 rounded transition-colors">
                            → 完成
                        </button>
                        @else
                        <button wire:click="moveTask({{ $task->id }}, 'in_progress')"
                            class="flex-1 px-2 py-1 text-xs text-sky-600 dark:text-sky-400 hover:bg-sky-50 dark:hover:bg-sky-950/30 rounded transition-colors">
                            ← 重开
                        </button>
                        @endif
                    </div>
                </div>
                @empty
                <div class="text-center py-6 text-xs text-zinc-400">
                    暂无
                </div>
                @endforelse
            </div>
        </div>
        @endforeach
    </div>
</div>
