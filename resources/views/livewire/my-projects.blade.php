<div class="space-y-5">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-xl font-bold text-zinc-900 dark:text-white">我的项目</h1>
            <p class="text-sm text-zinc-500 mt-1">我创建或参与的项目</p>
        </div>
        @can('create projects')
        <a href="{{ route('projects.create') }}"
            class="inline-flex items-center gap-2 px-4 py-2 bg-sky-600 hover:bg-sky-500 text-white text-sm font-medium rounded-xl transition-all">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.5v15m7.5-7.5h-15"/>
            </svg>
            新建项目
        </a>
        @endcan
    </div>

    {{-- 统计 --}}
    <div class="grid grid-cols-3 gap-4">
        @php $statCards = [['key'=>'','label'=>'全部','count'=>$counts['total'],'color'=>'zinc'],['key'=>'in_progress','label'=>'进行中','count'=>$counts['in_progress'],'color'=>'sky'],['key'=>'completed','label'=>'已完成','count'=>$counts['completed'],'color'=>'green']]; @endphp
        @foreach($statCards as $card)
        <button wire:click="$set('filterProgress', '{{ $card['key'] }}')"
            class="bg-white dark:bg-zinc-900 rounded-xl border p-4 text-left transition-colors
                {{ $filterProgress === $card['key'] ? 'border-'.$card['color'].'-500 ring-1 ring-'.$card['color'].'-500' : 'border-zinc-200 dark:border-zinc-800 hover:border-zinc-300' }}">
            <p class="text-xs text-zinc-500">{{ $card['label'] }}</p>
            <p class="text-2xl font-bold text-{{ $card['color'] }}-600 dark:text-{{ $card['color'] }}-400 mt-1">{{ $card['count'] }}</p>
        </button>
        @endforeach
    </div>

    {{-- 项目列表 --}}
    <div class="space-y-3">
        @forelse($projects as $project)
        @php $color = $project->progressColor; @endphp
        <a href="{{ route('projects.show', $project) }}"
            class="block bg-white dark:bg-zinc-900 rounded-2xl border border-zinc-200 dark:border-zinc-800 p-5 hover:border-sky-300 dark:hover:border-sky-700 transition-all group">
            <div class="flex items-start justify-between gap-4">
                <div class="flex-1 min-w-0">
                    <div class="flex items-center gap-2 mb-1.5">
                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium {{ $project->category->colorClass }}">{{ $project->category->name }}</span>
                        <span class="text-xs text-zinc-400">{{ $project->typeLabel }}</span>
                        @if($project->isOverdue())
                        <span class="text-xs text-red-500 font-medium">已逾期</span>
                        @endif
                    </div>
                    <h3 class="text-base font-semibold text-zinc-900 dark:text-white group-hover:text-sky-600 dark:group-hover:text-sky-400 transition-colors truncate">{{ $project->title }}</h3>
                    <p class="text-xs text-zinc-500 mt-1">
                        {{ $project->progressLabel }} · {{ $project->completion_percent }}% ·
                        @if($project->end_date) {{ $project->end_date->format('Y/m/d') }} 截止 @else 无截止日期 @endif
                    </p>
                </div>
                <div class="shrink-0 flex flex-col items-end gap-1.5">
                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-xs font-medium bg-{{ $color }}-100 dark:bg-{{ $color }}-950/40 text-{{ $color }}-700 dark:text-{{ $color }}-400">
                        <span class="w-1.5 h-1.5 rounded-full bg-{{ $color }}-500"></span>
                        {{ $project->progressLabel }}
                    </span>
                    <span class="text-xs text-zinc-400">
                        @if($project->tasks_count ?? false) {{ $project->tasks_count }} 个任务 · @endif
                        {{ $project->members_count ?? $project->members->count() }} 人
                    </span>
                </div>
            </div>
            {{-- 进度条 --}}
            <div class="mt-3 h-1.5 bg-zinc-100 dark:bg-zinc-800 rounded-full overflow-hidden">
                <div class="h-full bg-{{ $color }}-500 rounded-full transition-all" style="width: {{ $project->completion_percent }}%"></div>
            </div>
        </a>
        @empty
        <div class="text-center py-16 bg-white dark:bg-zinc-900 rounded-2xl border border-zinc-200 dark:border-zinc-800">
            <p class="text-zinc-500 text-sm">暂无项目</p>
            @can('create projects')
            <a href="{{ route('projects.create') }}" class="mt-2 inline-block text-sky-600 text-sm hover:underline">创建第一个项目</a>
            @endcan
        </div>
        @endforelse
    </div>

    @if($projects->hasPages())
    <div class="flex justify-center">{{ $projects->links() }}</div>
    @endif
</div>
