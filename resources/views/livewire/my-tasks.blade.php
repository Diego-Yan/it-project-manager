<div class="space-y-5">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-xl font-bold text-zinc-900 dark:text-white">我的任务</h1>
            <p class="text-sm text-zinc-500 mt-1">跨项目的个人任务汇总</p>
        </div>
    </div>

    {{-- 状态统计 --}}
    <div class="flex gap-2 flex-wrap">
        @php $filters = [''=>['全部',$counts['pending']+$counts['in_progress']+$counts['completed'],'zinc'],'pending_confirmation'=>['待确认',$counts['pending'],'amber'],'in_progress'=>['进行中',$counts['in_progress'],'sky'],'completed'=>['已完成',$counts['completed'],'green']]; @endphp
        @foreach($filters as $key => $f)
        <button wire:click="$set('filterStatus', '{{ $key }}')"
            class="px-3 py-1.5 text-xs font-medium rounded-lg border transition-colors
                {{ $filterStatus === $key
                    ? 'bg-{{ $f[2] }}-600 border-{{ $f[2] }}-600 text-white'
                    : 'border-zinc-200 dark:border-zinc-700 text-zinc-600 dark:text-zinc-400 hover:border-{{ $f[2] }}-400' }}">
            {{ $f[0] }} {{ $f[1] }}
        </button>
        @endforeach
    </div>

    {{-- 任务列表 --}}
    <div class="bg-white dark:bg-zinc-900 rounded-2xl border border-zinc-200 dark:border-zinc-800 overflow-hidden">
        @if($tasks->isEmpty())
        <div class="text-center py-16">
            <svg class="w-12 h-12 text-zinc-300 dark:text-zinc-700 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <p class="text-zinc-500 text-sm">暂无任务</p>
        </div>
        @else
        <div class="divide-y divide-zinc-100 dark:divide-zinc-800">
            @foreach($tasks as $task)
            @php $sColor = $task->statusColor; $pColor = $task->priorityColor; @endphp
            <div class="flex items-start gap-4 px-5 py-4 hover:bg-zinc-50/50 dark:hover:bg-zinc-800/30 transition-colors {{ $task->status === 'completed' ? 'opacity-60' : '' }}">
                {{-- 状态图标 --}}
                <div class="shrink-0 mt-0.5">
                    @if($task->status === 'completed')
                    <div class="w-5 h-5 rounded-full bg-green-100 dark:bg-green-900/50 flex items-center justify-center">
                        <svg class="w-3 h-3 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M4.5 12.75l6 6 9-13.5"/>
                        </svg>
                    </div>
                    @elseif($task->status === 'pending_confirmation')
                    <div class="w-5 h-5 rounded-full bg-amber-100 dark:bg-amber-900/50 flex items-center justify-center">
                        <svg class="w-3 h-3 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
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
                    </div>
                    <div class="flex items-center gap-2 mt-1 text-xs text-zinc-500">
                        <a href="{{ route('projects.show', $task->project) }}" class="text-sky-600 dark:text-sky-400 hover:underline">{{ $task->project->title }}</a>
                        @if($task->due_date)
                        <span>· {{ $task->due_date->format('m/d') }} 截止</span>
                        @endif
                        <span>· {{ $task->statusLabel }}</span>
                    </div>
                    @if($task->description)
                    <p class="text-xs text-zinc-400 mt-1 line-clamp-1">{{ $task->description }}</p>
                    @endif
                </div>

                {{-- 操作 --}}
                <div class="flex items-center gap-1 shrink-0">
                    @if($task->status === 'pending_confirmation')
                    <button wire:click="confirmTask({{ $task->id }})"
                        class="px-3 py-1.5 text-xs font-medium text-green-700 dark:text-green-400 bg-green-100 dark:bg-green-950/40 hover:bg-green-200 rounded-lg min-w-[44px]">确认</button>
                    @endif
                    @if($task->status === 'in_progress')
                    <button wire:click="completeTask({{ $task->id }})"
                        class="px-3 py-1.5 text-xs font-medium text-green-700 dark:text-green-400 bg-green-100 dark:bg-green-950/40 hover:bg-green-200 rounded-lg min-w-[44px]">完成</button>
                    @endif
                </div>
            </div>
            @endforeach
        </div>
        @endif
    </div>

    @if($tasks->hasPages())
    <div class="flex justify-center">{{ $tasks->links() }}</div>
    @endif
</div>
