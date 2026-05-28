<div class="space-y-5">

    {{-- 顶部操作栏 --}}
    <div class="flex flex-col sm:flex-row gap-3">
        <div class="flex-1 relative">
            <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-zinc-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z"/>
            </svg>
            <input type="text" wire:model.live.debounce.300ms="search" placeholder="搜索项目名称..."
                class="w-full pl-9 pr-4 py-2.5 text-sm bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-700 rounded-xl text-zinc-900 dark:text-white placeholder-zinc-400 focus:outline-none focus:border-sky-500 focus:ring-1 focus:ring-sky-500 transition-colors">
        </div>

        <div class="flex items-center gap-2">
            <select wire:model.live="filterProgress"
                class="text-sm bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-700 rounded-xl px-3 py-2.5 text-zinc-700 dark:text-zinc-300 focus:outline-none focus:border-sky-500 transition-colors">
                <option value="">全部进度</option>
                <option value="pending">未开始</option>
                <option value="in_progress">进行中</option>
                <option value="paused">已暂停</option>
                <option value="completed">已完成</option>
            </select>

            <select wire:model.live="filterCategory"
                class="text-sm bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-700 rounded-xl px-3 py-2.5 text-zinc-700 dark:text-zinc-300 focus:outline-none focus:border-sky-500 transition-colors">
                <option value="">全部分类</option>
                @foreach($categories as $cat)
                <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                @endforeach
            </select>

            @can('create projects')
            <a href="{{ route('projects.create') }}"
                class="flex items-center gap-2 px-4 py-2.5 bg-sky-600 hover:bg-sky-500 text-white text-sm font-medium rounded-xl transition-all hover:shadow-lg hover:shadow-sky-500/20">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.5v15m7.5-7.5h-15"/>
                </svg>
                新建项目
            </a>
            @endcan
        </div>
    </div>

    {{-- Flash 消息 --}}
    @if(session('success'))
    <div class="flex items-center gap-3 bg-green-50 dark:bg-green-950/30 border border-green-200 dark:border-green-800 text-green-700 dark:text-green-400 px-4 py-3 rounded-xl text-sm">
        <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
        </svg>
        {{ session('success') }}
    </div>
    @endif

    {{-- 项目列表 --}}
    <div class="bg-white dark:bg-zinc-900 rounded-2xl border border-zinc-200 dark:border-zinc-800 overflow-hidden">
        @if($projects->isEmpty())
        <div class="text-center py-16">
            <svg class="w-12 h-12 text-zinc-300 dark:text-zinc-700 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M2.25 12.75V12A2.25 2.25 0 014.5 9.75h15A2.25 2.25 0 0121.75 12v.75m-8.69-6.44l-2.12-2.12a1.5 1.5 0 00-1.061-.44H4.5A2.25 2.25 0 002.25 6v12a2.25 2.25 0 002.25 2.25h15A2.25 2.25 0 0021.75 18V9a2.25 2.25 0 00-2.25-2.25h-5.379a1.5 1.5 0 01-1.06-.44z"/>
            </svg>
            <p class="text-zinc-500 text-sm">暂无项目</p>
            @can('create projects')
            <a href="{{ route('projects.create') }}" class="mt-3 inline-block text-sky-600 text-sm hover:underline">创建第一个项目</a>
            @endcan
        </div>
        @else

        {{-- 表头 --}}
        <div class="grid grid-cols-12 gap-4 px-5 py-3 text-xs font-medium text-zinc-500 dark:text-zinc-400 border-b border-zinc-100 dark:border-zinc-800 uppercase tracking-wider">
            <div class="col-span-5">项目名称</div>
            <div class="col-span-2">分类</div>
            <div class="col-span-2">进度</div>
            <div class="col-span-2">截止日期</div>
            <div class="col-span-1 text-right">操作</div>
        </div>

        {{-- 项目行 --}}
        @foreach($projects as $project)
        @php
        $progressColors = ['pending'=>'zinc','in_progress'=>'sky','paused'=>'amber','completed'=>'green'];
        $color = $progressColors[$project->progress] ?? 'zinc';
        $isOverdue = $project->isOverdue();
        @endphp
        <div class="grid grid-cols-12 gap-4 px-5 py-4 border-b border-zinc-50 dark:border-zinc-800/50 hover:bg-zinc-50/50 dark:hover:bg-zinc-800/30 transition-colors items-center">

            {{-- 项目名称 --}}
            <div class="col-span-5 min-w-0">
                <a href="{{ route('projects.show', $project) }}" class="text-sm font-medium text-zinc-900 dark:text-white hover:text-sky-600 dark:hover:text-sky-400 transition-colors truncate block">
                    {{ $project->title }}
                </a>
                <p class="text-xs text-zinc-400 mt-0.5">
                    {{ $project->typeLabel }} · 创建于 {{ $project->created_at->format('Y/m/d') }}
                </p>
            </div>

            {{-- 分类 --}}
            <div class="col-span-2">
                <span class="inline-flex items-center px-2 py-0.5 rounded-lg text-xs font-medium {{ $project->category->colorClass }}">
                    {{ $project->category->name }}
                </span>
            </div>

            {{-- 进度 --}}
            <div class="col-span-2">
                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-xs font-medium bg-{{ $color }}-100 dark:bg-{{ $color }}-950/40 text-{{ $color }}-700 dark:text-{{ $color }}-400">
                    <span class="w-1.5 h-1.5 rounded-full bg-{{ $color }}-500"></span>
                    {{ $project->progressLabel }}
                </span>
            </div>

            {{-- 截止日期 --}}
            <div class="col-span-2">
                @if($project->end_date)
                <span class="text-sm {{ $isOverdue ? 'text-red-600 dark:text-red-400 font-medium' : 'text-zinc-600 dark:text-zinc-400' }}">
                    {{ $project->end_date->format('Y/m/d') }}
                    @if($isOverdue) <span class="text-xs">(逾期)</span> @endif
                </span>
                @else
                <span class="text-zinc-400 text-sm">—</span>
                @endif
            </div>

            {{-- 操作 --}}
            <div class="col-span-1 flex items-center justify-end gap-1">
                <a href="{{ route('projects.show', $project) }}"
                    class="p-1.5 rounded-lg text-zinc-400 hover:text-sky-600 dark:hover:text-sky-400 hover:bg-sky-50 dark:hover:bg-sky-950/30 transition-colors"
                    title="查看详情">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                    </svg>
                </a>
                @can('edit projects')
                <a href="{{ route('projects.edit', $project) }}"
                    class="p-1.5 rounded-lg text-zinc-400 hover:text-amber-600 dark:hover:text-amber-400 hover:bg-amber-50 dark:hover:bg-amber-950/30 transition-colors"
                    title="编辑">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125"/>
                    </svg>
                </a>
                @endcan
                @can('delete projects')
                <button wire:click="deleteProject({{ $project->id }})"
                    wire:confirm="确定要删除「{{ $project->title }}」吗？此操作不可恢复。"
                    class="p-1.5 rounded-lg text-zinc-400 hover:text-red-600 dark:hover:text-red-400 hover:bg-red-50 dark:hover:bg-red-950/30 transition-colors"
                    title="删除">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0"/>
                    </svg>
                </button>
                @endcan
            </div>

        </div>
        @endforeach
        @endif
    </div>

    {{-- 分页 --}}
    @if($projects->hasPages())
    <div class="flex justify-center">
        {{ $projects->links() }}
    </div>
    @endif

</div>
