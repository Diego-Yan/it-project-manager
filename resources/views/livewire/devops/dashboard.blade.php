<div class="space-y-6">
    <div>
        <h1 class="text-xl font-bold text-zinc-900 dark:text-white">DevOps 概览</h1>
        <p class="text-sm text-zinc-500 mt-1">服务健康 · 变更管理 · 发布追踪 · 故障响应</p>
    </div>

    {{-- 统计卡片 --}}
    <div class="grid grid-cols-2 lg:grid-cols-5 gap-4">
        @php
        $cards = [
            ['label'=>'服务总数','value'=>$stats['services'],'color'=>'sky','sub'=>'宕机 '.$stats['services_down']],
            ['label'=>'进行中变更','value'=>$stats['active_changes'],'color'=>'amber','sub'=>'待审批+执行中'],
            ['label'=>'未关闭故障','value'=>$stats['open_incidents'],'color'=>'red','sub'=>'需立即处理'],
            ['label'=>'近期发布','value'=>$stats['recent_releases'],'color'=>'green','sub'=>'成功部署'],
        ];
        @endphp
        @foreach($cards as $card)
        <div class="bg-white dark:bg-zinc-900 rounded-2xl border border-zinc-200 dark:border-zinc-800 p-4">
            <p class="text-xs text-zinc-500">{{ $card['label'] }}</p>
            <p class="text-2xl font-bold text-{{ $card['color'] }}-600 dark:text-{{ $card['color'] }}-400 mt-1">{{ $card['value'] }}</p>
            <p class="text-xs text-zinc-400 mt-0.5">{{ $card['sub'] }}</p>
        </div>
        @endforeach
    </div>

    <div class="grid lg:grid-cols-2 gap-6">
        {{-- 未关闭故障 --}}
        <div class="bg-white dark:bg-zinc-900 rounded-2xl border border-zinc-200 dark:border-zinc-800 p-5">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-sm font-semibold text-zinc-900 dark:text-white">未关闭故障</h2>
                <a href="{{ route('devops.incidents') }}" class="text-xs text-sky-600 dark:text-sky-400 hover:underline">全部</a>
            </div>
            @if($openIncidents->isEmpty())
            <p class="text-xs text-zinc-400 text-center py-4">暂无进行中故障 ✓</p>
            @else
            <div class="space-y-2">
                @foreach($openIncidents as $inc)
                <a href="{{ route('devops.incidents') }}" class="flex items-center gap-3 p-3 rounded-xl hover:bg-zinc-50 dark:hover:bg-zinc-800/50 group">
                    <span class="w-2 h-2 rounded-full bg-{{ $inc->severityColor }}-500 shrink-0"></span>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm text-zinc-800 dark:text-zinc-200 truncate">{{ $inc->title }}</p>
                        <p class="text-xs text-zinc-500">{{ $inc->project->title }} · {{ $inc->severityLabel }}</p>
                    </div>
                    <span class="text-xs text-zinc-400">{{ $inc->created_at->diffForHumans() }}</span>
                </a>
                @endforeach
            </div>
            @endif
        </div>

        {{-- 待审批变更 --}}
        <div class="bg-white dark:bg-zinc-900 rounded-2xl border border-zinc-200 dark:border-zinc-800 p-5">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-sm font-semibold text-zinc-900 dark:text-white">待审批变更</h2>
                <a href="{{ route('devops.changes') }}" class="text-xs text-sky-600 dark:text-sky-400 hover:underline">全部</a>
            </div>
            @if($pendingChanges->isEmpty())
            <p class="text-xs text-zinc-400 text-center py-4">暂无待审批变更</p>
            @else
            <div class="space-y-2">
                @foreach($pendingChanges as $cr)
                <div class="flex items-center gap-3 p-3 rounded-xl hover:bg-zinc-50 dark:hover:bg-zinc-800/50">
                    <span class="w-2 h-2 rounded-full bg-amber-500 shrink-0"></span>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm text-zinc-800 dark:text-zinc-200 truncate">{{ $cr->title }}</p>
                        <p class="text-xs text-zinc-500">{{ $cr->typeLabel }} · 风险 {{ $cr->riskLabel }} · {{ $cr->requester->name }}</p>
                    </div>
                </div>
                @endforeach
            </div>
            @endif
        </div>
    </div>

    {{-- 近期发布 --}}
    <div class="bg-white dark:bg-zinc-900 rounded-2xl border border-zinc-200 dark:border-zinc-800 p-5">
        <h2 class="text-sm font-semibold text-zinc-900 dark:text-white mb-4">近期发布</h2>
        @if($recentReleases->isEmpty())
        <p class="text-xs text-zinc-400 text-center py-4">暂无发布记录</p>
        @else
        <div class="space-y-2">
            @foreach($recentReleases as $rel)
            <div class="flex items-center gap-4 px-3 py-2 rounded-xl hover:bg-zinc-50 dark:hover:bg-zinc-800/50">
                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-{{ $rel->statusColor }}-100 dark:bg-{{ $rel->statusColor }}-950/40 text-{{ $rel->statusColor }}-700 dark:text-{{ $rel->statusColor }}-400 shrink-0">{{ $rel->statusLabel }}</span>
                <div class="flex-1 min-w-0">
                    <span class="text-sm text-zinc-800 dark:text-zinc-200 font-mono">{{ $rel->version }}</span>
                    <span class="text-xs text-zinc-500 ml-2">{{ $rel->project->title }}</span>
                </div>
                <span class="text-xs text-zinc-400">{{ $rel->created_at->format('m/d H:i') }}</span>
            </div>
            @endforeach
        </div>
        @endif
    </div>
</div>
