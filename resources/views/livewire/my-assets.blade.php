<div class="space-y-5">
    <div><h1 class="text-xl font-bold text-zinc-900 dark:text-white">{{ __('我的资产') }}</h1><p class="text-sm text-zinc-500 mt-1">{{ __('分配给我的 IT 设备和软件') }}</p></div>

    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        @php
        $cards = [
            ['label'=>__('全部资产'),'value'=>$counts['total'],'color'=>'sky'],
            ['label'=>__('使用中'),'value'=>$counts['in_use'],'color'=>'green'],
            ['label'=>__('维修中'),'value'=>$counts['repair'],'color'=>'amber'],
            ['label'=>__('即将过保'),'value'=>$counts['warranty_soon'],'color'=>'red'],
        ];
        @endphp
        @foreach($cards as $c)
        <div class="bg-white dark:bg-zinc-900 rounded-2xl border p-4">
            <p class="text-xs text-zinc-500">{{ $c['label'] }}</p>
            <p class="text-2xl font-bold text-{{ $c['color'] }}-600 dark:text-{{ $c['color'] }}-400 mt-1">{{ $c['value'] }}</p>
        </div>
        @endforeach
    </div>

    <div class="bg-white dark:bg-zinc-900 rounded-2xl border overflow-hidden">
        @if($assets->isEmpty())
        <div class="text-center py-16 text-zinc-400 text-sm">{{ __('暂无资产分配给你') }}</div>
        @else
        <div class="divide-y divide-zinc-100 dark:divide-zinc-800">
            @foreach($assets as $a)
            <div class="flex items-center gap-4 px-5 py-4 hover:bg-zinc-50 dark:hover:bg-zinc-800/30">
                <span class="text-2xl shrink-0">{{ $a->typeIcon }}</span>
                <div class="flex-1 min-w-0">
                    <div class="flex items-center gap-2 flex-wrap">
                        <span class="text-sm font-medium text-zinc-900 dark:text-white">{{ $a->name }}</span>
                        <span class="text-xs font-mono text-zinc-500">{{ $a->asset_tag }}</span>
                        <span class="inline-flex px-2 py-0.5 rounded text-xs font-medium bg-{{ $a->categoryColor }}-100 dark:bg-{{ $a->categoryColor }}-950/40 text-{{ $a->categoryColor }}-700 dark:text-{{ $a->categoryColor }}-400">{{ $a->categoryLabel }}</span>
                        <span class="inline-flex px-2 py-0.5 rounded text-xs font-medium bg-{{ $a->statusColor }}-100 dark:bg-{{ $a->statusColor }}-950/40 text-{{ $a->statusColor }}-700 dark:text-{{ $a->statusColor }}-400">{{ $a->statusLabel }}</span>
                    </div>
                    <div class="text-xs text-zinc-500 mt-0.5">
                        {{ $a->brand ?: '' }} {{ $a->model ?: '' }} {{ $a->serial_number ? 'SN:'.$a->serial_number : '' }}
                        @if($a->location)<span>· {{ $a->location }}</span>@endif
                        @if($a->warranty_expiry)
                        <span class="{{ $a->warranty_expiry->isPast() ? 'text-red-500 font-medium' : ($a->warranty_expiry->lt(now()->addDays(30)) ? 'text-amber-500' : 'text-zinc-400') }}">
                            · {{ __('保修至') }} {{ $a->warranty_expiry->format('Y-m-d') }}
                        </span>
                        @endif
                    </div>
                </div>
            </div>
            @endforeach
        </div>
        @endif
    </div>
    @if($assets->hasPages())<div class="flex justify-center">{{ $assets->links() }}</div>@endif
</div>
