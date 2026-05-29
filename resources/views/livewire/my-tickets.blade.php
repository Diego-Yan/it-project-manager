<div class="space-y-5">
    <div><h1 class="text-xl font-bold text-zinc-900 dark:text-white">我的工单</h1><p class="text-sm text-zinc-500 mt-1">我创建或分配给我的工单</p></div>

    <div class="flex gap-2 flex-wrap">
        @php
        $filters = [
            ''            => ['label' => '全部',   'count' => $counts['open'] + $counts['in_progress'] + $counts['resolved'], 'color' => 'zinc'],
            'open'        => ['label' => '待处理', 'count' => $counts['open'],        'color' => 'amber'],
            'in_progress' => ['label' => '处理中', 'count' => $counts['in_progress'], 'color' => 'sky'],
            'resolved'    => ['label' => '已解决', 'count' => $counts['resolved'],    'color' => 'green'],
        ];
        @endphp
        @foreach($filters as $key => $f)
        <button wire:click="$set('filterStatus', '{{ $key }}')"
            class="px-3 py-1.5 text-xs font-medium rounded-lg border transition-colors
                {{ $filterStatus === $key ? 'bg-'.$f['color'].'-600 border-'.$f['color'].'-600 text-white' : 'border-zinc-200 dark:border-zinc-700 text-zinc-600 dark:text-zinc-400' }}">
            {{ $f['label'] }} {{ $f['count'] }}
        </button>
        @endforeach
    </div>

    <div class="bg-white dark:bg-zinc-900 rounded-2xl border overflow-hidden">
        @if($tickets->isEmpty())
        <div class="text-center py-16 text-zinc-400 text-sm">暂无工单</div>
        @else
        <div class="divide-y divide-zinc-100 dark:divide-zinc-800">
            @foreach($tickets as $ticket)
            <div class="px-5 py-4 hover:bg-zinc-50 dark:hover:bg-zinc-800/30 flex items-center gap-4">
                <div class="flex-1 min-w-0">
                    <div class="flex items-center gap-2 flex-wrap mb-1">
                        <span class="inline-flex px-2 py-0.5 rounded text-xs font-medium bg-{{ $ticket->typeColor }}-100 dark:bg-{{ $ticket->typeColor }}-950/40 text-{{ $ticket->typeColor }}-700 dark:text-{{ $ticket->typeColor }}-400">{{ $ticket->typeLabel }}</span>
                        <span class="inline-flex px-2 py-0.5 rounded text-xs font-medium bg-{{ $ticket->priorityColor }}-100 dark:bg-{{ $ticket->priorityColor }}-950/40 text-{{ $ticket->priorityColor }}-700 dark:text-{{ $ticket->priorityColor }}-400">{{ $ticket->priorityLabel }}优先</span>
                        <span class="text-sm font-medium text-zinc-900 dark:text-white">{{ $ticket->title }}</span>
                        @if($ticket->isSlaBreached())<span class="text-xs text-red-500 font-bold">SLA超时</span>@endif
                    </div>
                    <div class="text-xs text-zinc-500">
                        状态: {{ $ticket->statusLabel }}
                        @if($ticket->assignee)<span>· {{ $ticket->assignee->name }}</span>@endif
                        @if($ticket->asset)<span>· {{ $ticket->asset->name }}</span>@endif
                        <span>· {{ $ticket->sourceLabel }}</span>
                        <span>· {{ $ticket->created_at->diffForHumans() }}</span>
                    </div>
                </div>
                <span class="inline-flex px-2.5 py-1 rounded-lg text-xs font-medium bg-{{ $ticket->priorityColor }}-100 dark:bg-{{ $ticket->priorityColor }}-950/40 text-{{ $ticket->priorityColor }}-700 dark:text-{{ $ticket->priorityColor }}-400 shrink-0">{{ $ticket->statusLabel }}</span>
            </div>
            @endforeach
        </div>
        @endif
    </div>
    @if($tickets->hasPages())<div class="flex justify-center">{{ $tickets->links() }}</div>@endif
</div>
