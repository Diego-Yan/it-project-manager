<div class="space-y-5">

    {{-- 顶部操作栏 --}}
    <div class="space-y-3">
        <div class="flex items-center gap-3">
            <div class="relative flex-1">
                <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-zinc-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z"/>
                </svg>
                <input type="text" wire:model.live.debounce.300ms="search" placeholder="搜索项目名称..."
                    class="w-full pl-9 pr-4 py-2.5 text-sm bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-700 rounded-xl text-zinc-900 dark:text-white placeholder-zinc-400 focus:outline-none focus:border-sky-500 transition-colors">
            </div>
            @can('create projects')
            <a href="{{ route('projects.create') }}"
                class="flex items-center gap-2 px-4 py-2.5 bg-sky-600 hover:bg-sky-500 text-white text-sm font-medium rounded-xl transition-all shrink-0">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                <span class="hidden sm:inline">新建项目</span>
            </a>
            @endcan
        </div>

        {{-- 多选筛选标签 --}}
        <div class="space-y-2">
            @php
            $filterGroups = [
                '进度' => [''=>'filterProgress', 'pending'=>'未开始','in_progress'=>'进行中','paused'=>'已暂停','completed'=>'已完成'],
                '分类' => [''=>'filterCategory'],
                '类型' => [''=>'filterType', 'new'=>'新增','improved'=>'改善','issue'=>'异常'],
                '紧急度' => [''=>'filterUrgency', 'not_urgent'=>'不紧急','normal'=>'一般','urgent'=>'紧急'],
                '重要性' => [''=>'filterImportance', 'normal'=>'一般','important'=>'重要','very_important'=>'非常重要'],
            ];
            @endphp
            @foreach($filterGroups as $groupName => $group)
            @php $field = $group['']; unset($group['']); @endphp
            <div class="flex items-center gap-1.5 flex-wrap">
                <span class="text-xs font-medium text-zinc-500 w-12 shrink-0">{{ $groupName }}</span>
                @if($field === 'filterCategory')
                @foreach($categories as $cat)
                <button wire:click="toggleFilter('{{ $field }}', '{{ $cat->id }}')"
                    class="px-2.5 py-1 text-xs rounded-lg border transition-colors {{ in_array($cat->id, $filterCategory) ? 'border-sky-500 bg-sky-50 dark:bg-sky-950/30 text-sky-700 dark:text-sky-400' : 'border-zinc-200 dark:border-zinc-700 text-zinc-500 hover:border-zinc-300' }}">
                    {{ $cat->name }}
                </button>
                @endforeach
                @elseif($field === 'filterRegion')
                @foreach($regions as $r)
                <button wire:click="toggleFilter('{{ $field }}', '{{ $r->id }}')"
                    class="px-2.5 py-1 text-xs rounded-lg border transition-colors {{ in_array($r->id, $filterRegion) ? 'border-sky-500 bg-sky-50 dark:bg-sky-950/30 text-sky-700 dark:text-sky-400' : 'border-zinc-200 dark:border-zinc-700 text-zinc-500 hover:border-zinc-300' }}">
                    {{ $r->name }}
                </button>
                @endforeach
                @else
                @foreach($group as $val => $label)
                <button wire:click="toggleFilter('{{ $field }}', '{{ $val }}')"
                    class="px-2.5 py-1 text-xs rounded-lg border transition-colors {{ in_array($val, ${$field}) ? 'border-sky-500 bg-sky-50 dark:bg-sky-950/30 text-sky-700 dark:text-sky-400' : 'border-zinc-200 dark:border-zinc-700 text-zinc-500 hover:border-zinc-300' }}">
                    {{ $label }}
                </button>
                @endforeach
                @endif
            </div>
            @endforeach

            {{-- 地区 --}}
            <div class="flex items-center gap-1.5 flex-wrap">
                <span class="text-xs font-medium text-zinc-500 w-12 shrink-0">地区</span>
                @foreach($regions as $r)
                <button wire:click="toggleFilter('filterRegion', '{{ $r->id }}')"
                    class="px-2.5 py-1 text-xs rounded-lg border transition-colors {{ in_array($r->id, $filterRegion) ? 'border-sky-500 bg-sky-50 dark:bg-sky-950/30 text-sky-700 dark:text-sky-400' : 'border-zinc-200 dark:border-zinc-700 text-zinc-500 hover:border-zinc-300' }}">
                    {{ $r->name }}
                </button>
                @endforeach
            </div>
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

    {{-- 项目列表 — 桌面端表格 / 移动端卡片 --}}
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

        {{-- 表头（仅桌面端显示） --}}
        <div class="hidden md:grid grid-cols-12 gap-3 px-5 py-3 text-xs font-medium text-zinc-500 dark:text-zinc-400 border-b border-zinc-100 dark:border-zinc-800 uppercase tracking-wider">
            <div class="col-span-4">项目名称</div>
            <div class="col-span-1">类型</div>
            <div class="col-span-1">分类</div>
            <div class="col-span-1">紧急度</div>
            <div class="col-span-1">重要性</div>
            <div class="col-span-2">进度</div>
            <div class="col-span-1">截止</div>
            <div class="col-span-1 text-right">操作</div>
        </div>

        @foreach($projects as $project)
        @php
        $progressColors = ['pending'=>'zinc','in_progress'=>'sky','paused'=>'amber','completed'=>'green'];
        $color = $progressColors[$project->progress] ?? 'zinc';
        $isOverdue = $project->isOverdue();
        $isMember = in_array($project->id, $memberOfIds);
        $hasApplied = in_array($project->id, $appliedIds);
        @endphp

        @php
        $uColor = $project->urgencyColor;
        $iColor = $project->importanceColor;
        @endphp

        {{-- 桌面端：表格行 --}}
        <div class="hidden md:grid grid-cols-12 gap-3 px-5 py-3.5 border-b border-zinc-50 dark:border-zinc-800/50 hover:bg-zinc-50/50 dark:hover:bg-zinc-800/30 transition-colors items-center">
            <div class="col-span-4 min-w-0">
                <a href="{{ route('projects.show', $project) }}" class="text-sm font-medium text-zinc-900 dark:text-white hover:text-sky-600 dark:hover:text-sky-400 transition-colors truncate block">
                    {{ $project->title }}
                </a>
                <p class="text-xs text-zinc-400 mt-0.5">创建于 {{ $project->created_at->format('Y/m/d') }}</p>
            </div>
            <div class="col-span-1">
                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-{{ $project->typeColor }}-100 dark:bg-{{ $project->typeColor }}-950/40 text-{{ $project->typeColor }}-700 dark:text-{{ $project->typeColor }}-400">
                    {{ $project->typeLabel }}
                </span>
            </div>
            <div class="col-span-1">
                <span class="inline-flex items-center px-2 py-0.5 rounded-lg text-xs font-medium {{ $project->category->colorClass }}">
                    {{ $project->category->name }}
                </span>
                @if($project->region)<span class="inline-flex items-center px-2 py-0.5 rounded text-xs text-zinc-500 ml-1">{{ $project->region->name }}</span>@endif
            </div>
            <div class="col-span-1">
                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-xs font-medium bg-{{ $uColor }}-100 dark:bg-{{ $uColor }}-950/40 text-{{ $uColor }}-700 dark:text-{{ $uColor }}-400">
                    {{ $project->urgencyLabel }}
                </span>
            </div>
            <div class="col-span-1">
                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-xs font-medium bg-{{ $iColor }}-100 dark:bg-{{ $iColor }}-950/40 text-{{ $iColor }}-700 dark:text-{{ $iColor }}-400">
                    {{ $project->importanceLabel }}
                </span>
            </div>
            <div class="col-span-2">
                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-xs font-medium bg-{{ $color }}-100 dark:bg-{{ $color }}-950/40 text-{{ $color }}-700 dark:text-{{ $color }}-400">
                    <span class="w-1.5 h-1.5 rounded-full bg-{{ $color }}-500"></span>
                    {{ $project->progressLabel }}
                </span>
            </div>
            <div class="col-span-1">
                @if($project->end_date)
                <span class="text-xs {{ $isOverdue ? 'text-red-600 dark:text-red-400 font-medium' : 'text-zinc-600 dark:text-zinc-400' }}">
                    {{ $project->end_date->format('m/d') }}
                    @if($isOverdue)<br><span class="text-xs">逾期</span>@endif
                </span>
                @else
                <span class="text-zinc-400 text-xs">—</span>
                @endif
            </div>
            <div class="col-span-1 flex items-center justify-end gap-0.5">
                @if(!$isMember && $project->progress !== 'completed')
                @if($hasApplied)
                <span class="text-xs text-amber-500 font-medium px-1.5" title="等待审批">审批中</span>
                @else
                <button wire:click="applyToProject({{ $project->id }})"
                    class="text-xs text-sky-600 dark:text-sky-400 hover:text-sky-700 font-medium px-1.5 py-0.5 rounded hover:bg-sky-50 dark:hover:bg-sky-950/30 transition-colors"
                    title="申请加入">
                    加入
                </button>
                @endif
                @endif
                <a href="{{ route('projects.show', $project) }}"
                    class="p-1.5 rounded-lg text-zinc-400 hover:text-sky-600 dark:hover:text-sky-400 hover:bg-sky-50 dark:hover:bg-sky-950/30 transition-colors" title="查看详情">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                    </svg>
                </a>
                @can('edit projects')
                <a href="{{ route('projects.edit', $project) }}"
                    class="p-1.5 rounded-lg text-zinc-400 hover:text-amber-600 dark:hover:text-amber-400 hover:bg-amber-50 dark:hover:bg-amber-950/30 transition-colors" title="编辑">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125"/>
                    </svg>
                </a>
                @endcan
                @can('delete projects')
                <button wire:click="deleteProject({{ $project->id }})"
                    wire:confirm="确定要删除「{{ $project->title }}」吗？此操作不可恢复。"
                    class="p-1.5 rounded-lg text-zinc-400 hover:text-red-600 dark:hover:text-red-400 hover:bg-red-50 dark:hover:bg-red-950/30 transition-colors" title="删除">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0"/>
                    </svg>
                </button>
                @endcan
            </div>
        </div>

        {{-- 移动端：卡片布局 --}}
        <a href="{{ route('projects.show', $project) }}"
            class="block md:hidden px-4 py-4 border-b border-zinc-50 dark:border-zinc-800/50 active:bg-zinc-50 dark:active:bg-zinc-800/30 transition-colors">
            <div class="flex items-start justify-between gap-3 mb-2.5">
                <div class="flex-1 min-w-0">
                    <h3 class="text-sm font-semibold text-zinc-900 dark:text-white truncate">{{ $project->title }}</h3>
                    <p class="text-xs text-zinc-400 mt-0.5">{{ $project->created_at->format('Y/m/d') }}</p>
                </div>
                <span class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-full text-xs font-medium bg-{{ $color }}-100 dark:bg-{{ $color }}-950/40 text-{{ $color }}-700 dark:text-{{ $color }}-400 shrink-0">
                    <span class="w-1.5 h-1.5 rounded-full bg-{{ $color }}-500"></span>
                    {{ $project->progressLabel }}
                </span>
            </div>
            <div class="flex items-center gap-2 text-xs flex-wrap">
                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-{{ $project->typeColor }}-100 dark:bg-{{ $project->typeColor }}-950/40 text-{{ $project->typeColor }}-700 dark:text-{{ $project->typeColor }}-400">
                    {{ $project->typeLabel }}
                </span>
                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium {{ $project->category->colorClass }}">
                    {{ $project->category->name }}
                </span>
                @if($project->region)<span class="text-xs text-zinc-500">📍{{ $project->region->name }}</span>@endif
                <span class="text-zinc-300">|</span>
                @if($project->end_date)
                <span class="{{ $isOverdue ? 'text-red-500 font-medium' : 'text-zinc-500' }}">
                    {{ $project->end_date->format('m/d') }} 截止
                </span>
                @else
                <span class="text-zinc-400">无截止日</span>
                @endif
            </div>
            <div class="flex items-center gap-2 text-xs mt-2">
                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-xs font-medium bg-{{ $uColor }}-100 dark:bg-{{ $uColor }}-950/40 text-{{ $uColor }}-700 dark:text-{{ $uColor }}-400">
                    急: {{ $project->urgencyLabel }}
                </span>
                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-xs font-medium bg-{{ $iColor }}-100 dark:bg-{{ $iColor }}-950/40 text-{{ $iColor }}-700 dark:text-{{ $iColor }}-400">
                    重: {{ $project->importanceLabel }}
                </span>
                <span class="text-zinc-300">|</span>
                <span class="text-zinc-500">{{ $project->completion_percent }}%</span>
                @if(!$isMember && $project->progress !== 'completed')
                @if($hasApplied)
                <span class="text-amber-500 font-medium ml-auto">审批中</span>
                @else
                <button wire:click="applyToProject({{ $project->id }})"
                    onclick="event.stopPropagation(); event.preventDefault();"
                    class="ml-auto text-xs text-sky-600 dark:text-sky-400 font-medium px-2 py-0.5 rounded bg-sky-50 dark:bg-sky-950/40">
                    加入
                </button>
                @endif
                @endif
            </div>
        </a>
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
