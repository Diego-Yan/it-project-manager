<div class="space-y-6">

    {{-- 统计卡片 --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        @php
        $cards = [
            ['label'=>'项目总数','value'=>$stats['total'],'color'=>'sky','icon'=>'M2.25 12.75V12A2.25 2.25 0 014.5 9.75h15A2.25 2.25 0 0121.75 12v.75m-8.69-6.44l-2.12-2.12a1.5 1.5 0 00-1.061-.44H4.5A2.25 2.25 0 002.25 6v12a2.25 2.25 0 002.25 2.25h15A2.25 2.25 0 0021.75 18V9a2.25 2.25 0 00-2.25-2.25h-5.379a1.5 1.5 0 01-1.06-.44z'],
            ['label'=>'进行中','value'=>$stats['in_progress'],'color'=>'violet','icon'=>'M5.25 5.653c0-.856.917-1.398 1.667-.986l11.54 6.348a1.125 1.125 0 010 1.971l-11.54 6.347a1.125 1.125 0 01-1.667-.985V5.653z'],
            ['label'=>'已完成','value'=>$stats['completed'],'color'=>'green','icon'=>'M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z'],
            ['label'=>'已逾期','value'=>$stats['overdue'],'color'=>'red','icon'=>'M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z'],
        ];
        @endphp

        @foreach($cards as $card)
        <div class="bg-white dark:bg-zinc-900 rounded-2xl border border-zinc-200 dark:border-zinc-800 p-5">
            <div class="flex items-start justify-between">
                <div>
                    <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ $card['label'] }}</p>
                    <p class="text-3xl font-bold text-zinc-900 dark:text-white mt-1">{{ $card['value'] }}</p>
                </div>
                <div class="w-10 h-10 rounded-xl bg-{{ $card['color'] }}-100 dark:bg-{{ $card['color'] }}-950/50 flex items-center justify-center">
                    <svg class="w-5 h-5 text-{{ $card['color'] }}-600 dark:text-{{ $card['color'] }}-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="{{ $card['icon'] }}"/>
                    </svg>
                </div>
            </div>
        </div>
        @endforeach
    </div>

    {{-- 任务统计卡片 --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        @php
        $taskCards = [
            ['label'=>'我的任务','value'=>$taskStats['my_total'],'color'=>'sky','sub'=>'已完成 '.$taskStats['my_completed']],
            ['label'=>'待确认','value'=>$taskStats['my_pending'],'color'=>'amber','sub'=>'需要我确认的任务'],
            ['label'=>'待审批','value'=>$taskStats['app_pending'],'color'=>'violet','sub'=>'加入申请'],
        ];
        @endphp
        @foreach($taskCards as $card)
        <div class="bg-white dark:bg-zinc-900 rounded-2xl border border-zinc-200 dark:border-zinc-800 p-4">
            <p class="text-xs text-zinc-500 dark:text-zinc-400">{{ $card['label'] }}</p>
            <p class="text-2xl font-bold text-{{ $card['color'] }}-600 dark:text-{{ $card['color'] }}-400 mt-1">{{ $card['value'] }}</p>
            <p class="text-xs text-zinc-400 mt-0.5">{{ $card['sub'] }}</p>
        </div>
        @endforeach
    </div>

    {{-- 待确认任务 + 进行中任务 + 待审批 --}}
    <div class="grid lg:grid-cols-3 gap-6">
        {{-- 待确认任务 --}}
        <div class="bg-white dark:bg-zinc-900 rounded-2xl border border-zinc-200 dark:border-zinc-800 p-5">
            <h2 class="text-sm font-semibold text-zinc-900 dark:text-white mb-4">
                待确认的任务
                @if($pendingTasks->isNotEmpty())
                <span class="ml-1 inline-flex items-center justify-center w-5 h-5 rounded-full bg-amber-500 text-white text-xs">{{ $pendingTasks->count() }}</span>
                @endif
            </h2>
            @if($pendingTasks->isEmpty())
            <p class="text-xs text-zinc-400 text-center py-4">暂无待确认任务 ✓</p>
            @else
            <div class="space-y-2">
                @foreach($pendingTasks as $task)
                <a href="{{ route('projects.show', $task->project) }}" class="flex items-center gap-3 p-2.5 rounded-xl hover:bg-zinc-50 dark:hover:bg-zinc-800/50 transition-colors group">
                    <span class="w-2 h-2 rounded-full bg-amber-500 shrink-0"></span>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm text-zinc-800 dark:text-zinc-200 truncate group-hover:text-sky-600 dark:group-hover:text-sky-400">{{ $task->title }}</p>
                        <p class="text-xs text-zinc-500">{{ $task->project->title }}</p>
                    </div>
                </a>
                @endforeach
            </div>
            @endif
        </div>

        {{-- 我的进行中任务 --}}
        <div class="bg-white dark:bg-zinc-900 rounded-2xl border border-zinc-200 dark:border-zinc-800 p-5">
            <h2 class="text-sm font-semibold text-zinc-900 dark:text-white mb-4">我的任务 (进行中)</h2>
            @if($myTasks->isEmpty())
            <p class="text-xs text-zinc-400 text-center py-4">暂无进行中任务</p>
            @else
            <div class="space-y-2">
                @foreach($myTasks as $task)
                <a href="{{ route('projects.show', $task->project) }}" class="flex items-center gap-3 p-2.5 rounded-xl hover:bg-zinc-50 dark:hover:bg-zinc-800/50 transition-colors group">
                    <span class="w-2 h-2 rounded-full bg-sky-500 shrink-0"></span>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm text-zinc-800 dark:text-zinc-200 truncate group-hover:text-sky-600 dark:group-hover:text-sky-400">{{ $task->title }}</p>
                        <p class="text-xs text-zinc-500">{{ $task->project->title }}</p>
                    </div>
                </a>
                @endforeach
            </div>
            @endif
        </div>

        {{-- 待审批申请 --}}
        <div class="bg-white dark:bg-zinc-900 rounded-2xl border border-zinc-200 dark:border-zinc-800 p-5">
            <h2 class="text-sm font-semibold text-zinc-900 dark:text-white mb-4">
                待审批的加入申请
                @if($pendingApplications->isNotEmpty())
                <span class="ml-1 inline-flex items-center justify-center w-5 h-5 rounded-full bg-violet-500 text-white text-xs">{{ $pendingApplications->count() }}</span>
                @endif
            </h2>
            @if($pendingApplications->isEmpty())
            <p class="text-xs text-zinc-400 text-center py-4">暂无待审批申请</p>
            @else
            <div class="space-y-2">
                @foreach($pendingApplications as $app)
                <div class="flex items-center gap-3 p-2.5 rounded-xl bg-zinc-50 dark:bg-zinc-800/50">
                    <div class="w-6 h-6 rounded-lg bg-violet-100 dark:bg-violet-950/40 flex items-center justify-center text-violet-600 text-xs font-semibold shrink-0">
                        {{ mb_substr($app->user->name, 0, 1) }}
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm text-zinc-800 dark:text-zinc-200 truncate">{{ $app->user->name }}</p>
                        <p class="text-xs text-zinc-500">{{ $app->project->title }}</p>
                    </div>
                    <a href="{{ route('projects.show', $app->project) }}" class="text-xs text-sky-600 dark:text-sky-400 hover:underline shrink-0">去处理</a>
                </div>
                @endforeach
            </div>
            @endif
        </div>
    </div>

    <div class="grid lg:grid-cols-3 gap-6">

        {{-- 即将到期 --}}
        <div class="lg:col-span-2 bg-white dark:bg-zinc-900 rounded-2xl border border-zinc-200 dark:border-zinc-800 p-5">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-sm font-semibold text-zinc-900 dark:text-white">即将到期（7天内）</h2>
                <a href="{{ route('projects.index') }}" class="text-xs text-sky-600 dark:text-sky-400 hover:underline">查看全部</a>
            </div>

            @if($upcomingDeadlines->isEmpty())
            <div class="text-center py-8 text-zinc-400 text-sm">暂无即将到期的项目 ✓</div>
            @else
            <div class="space-y-2">
                @foreach($upcomingDeadlines as $p)
                <a href="{{ route('projects.show', $p) }}" class="flex items-center gap-3 p-3 rounded-xl hover:bg-zinc-50 dark:hover:bg-zinc-800/50 transition-colors group">
                    <span class="w-2 h-2 rounded-full bg-amber-500 shrink-0"></span>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-medium text-zinc-900 dark:text-white truncate group-hover:text-sky-600 dark:group-hover:text-sky-400">{{ $p->title }}</p>
                        <p class="text-xs text-zinc-500">{{ $p->category->name }}</p>
                    </div>
                    <span class="text-xs text-amber-600 dark:text-amber-400 shrink-0">{{ $p->end_date->format('m/d') }}</span>
                </a>
                @endforeach
            </div>
            @endif
        </div>

        {{-- 分类分布 --}}
        <div class="bg-white dark:bg-zinc-900 rounded-2xl border border-zinc-200 dark:border-zinc-800 p-5">
            <h2 class="text-sm font-semibold text-zinc-900 dark:text-white mb-4">分类分布</h2>
            <div class="space-y-3">
                @foreach($byCategory->where('count','>',0) as $cat)
                <div>
                    <div class="flex items-center justify-between mb-1">
                        <span class="text-xs text-zinc-600 dark:text-zinc-400 truncate">{{ $cat->name }}</span>
                        <span class="text-xs font-medium text-zinc-900 dark:text-white">{{ $cat->count }}</span>
                    </div>
                    <div class="h-1.5 bg-zinc-100 dark:bg-zinc-800 rounded-full overflow-hidden">
                        <div class="h-full bg-{{ $cat->color }}-500 rounded-full transition-all"
                            style="width: {{ $stats['total'] > 0 ? round($cat->count/$stats['total']*100) : 0 }}%"></div>
                    </div>
                </div>
                @endforeach
                @if($byCategory->where('count','>',0)->isEmpty())
                <p class="text-xs text-zinc-400 text-center py-4">暂无数据</p>
                @endif
            </div>
        </div>
    </div>

    {{-- 最近动态 --}}
    <div class="bg-white dark:bg-zinc-900 rounded-2xl border border-zinc-200 dark:border-zinc-800 p-5">
        <h2 class="text-sm font-semibold text-zinc-900 dark:text-white mb-4">最近操作动态</h2>
        @if($recentLogs->isEmpty())
        <p class="text-center text-sm text-zinc-400 py-6">暂无操作记录</p>
        @else
        <div class="space-y-3">
            @foreach($recentLogs as $log)
            <div class="flex items-start gap-3">
                <div class="w-7 h-7 rounded-lg bg-sky-100 dark:bg-sky-950/50 flex items-center justify-center text-sky-600 dark:text-sky-400 text-xs font-semibold shrink-0 mt-0.5">
                    {{ mb_substr($log->user?->name ?? '?', 0, 1) }}
                </div>
                <div class="flex-1 min-w-0">
                    <p class="text-sm text-zinc-800 dark:text-zinc-200">
                        <span class="font-medium">{{ $log->user?->name ?? '未知用户' }}</span>
                        <span class="text-zinc-500">{{ $log->actionLabel }}</span>
                        <a href="{{ route('projects.show', $log->project_id) }}" class="font-medium text-sky-600 dark:text-sky-400 hover:underline">
                            {{ $log->project?->title ?? '#' . $log->project_id }}
                        </a>
                    </p>
                    <p class="text-xs text-zinc-400 mt-0.5">{{ $log->created_at->diffForHumans() }}</p>
                </div>
            </div>
            @endforeach
        </div>
        @endif
    </div>

</div>
