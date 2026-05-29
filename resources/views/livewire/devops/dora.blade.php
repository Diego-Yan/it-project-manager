<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div><h1 class="text-xl font-bold text-zinc-900 dark:text-white">DORA 指标</h1><p class="text-sm text-zinc-500 mt-1">DevOps Research & Assessment — 软件交付效能度量</p></div>
        <select wire:model.live="period" class="px-3 py-2 text-sm border rounded-xl dark:bg-zinc-800 dark:border-zinc-700">
            <option value="7d">最近 7 天</option><option value="30d">最近 30 天</option><option value="90d">最近 90 天</option>
        </select>
    </div>

    {{-- 四大指标 --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        @php
        $metrics = [
            ['label'=>'部署频率','value'=>$deployFreq,'unit'=>'次/周','color'=>'sky','sub'=>'Deployment Frequency'],
            ['label'=>'交付周期','value'=>$avgLeadTime,'unit'=>'小时','color'=>'violet','sub'=>'Lead Time for Changes'],
            ['label'=>'MTTR','value'=>$mttr,'unit'=>'分钟','color'=>'amber','sub'=>'Mean Time to Restore'],
            ['label'=>'变更失败率','value'=>$failureRate,'unit'=>'%','color'=>'red','sub'=>'Change Failure Rate'],
        ];
        @endphp
        @foreach($metrics as $m)
        <div class="bg-white dark:bg-zinc-900 rounded-2xl border border-zinc-200 dark:border-zinc-800 p-5">
            <p class="text-xs text-zinc-500">{{ $m['label'] }} <span class="text-zinc-300">· {{ $m['sub'] }}</span></p>
            <p class="text-3xl font-bold text-{{ $m['color'] }}-600 dark:text-{{ $m['color'] }}-400 mt-2">
                {{ $m['value'] ?? '—' }}
                <span class="text-sm font-normal text-zinc-400">{{ $m['unit'] }}</span>
            </p>
        </div>
        @endforeach
    </div>

    {{-- 趋势图 --}}
    <div class="bg-white dark:bg-zinc-900 rounded-2xl border border-zinc-200 dark:border-zinc-800 p-5">
        <h2 class="text-sm font-semibold text-zinc-900 dark:text-white mb-4">月度趋势</h2>
        <div class="grid grid-cols-3 gap-4">
            @foreach($months as $mo)
            <div class="p-4 rounded-xl bg-zinc-50 dark:bg-zinc-800/50">
                <p class="text-xs font-medium text-zinc-500 mb-3">{{ $mo['month'] }}</p>
                <div class="space-y-3">
                    <div class="flex items-center justify-between"><span class="text-xs text-zinc-500">部署次数</span><span class="text-sm font-bold text-sky-600">{{ $mo['deploys'] }}</span></div>
                    <div class="flex items-center justify-between"><span class="text-xs text-zinc-500">故障数</span><span class="text-sm font-bold text-red-600">{{ $mo['incidents'] }}</span></div>
                    <div class="flex items-center justify-between"><span class="text-xs text-zinc-500">失败回滚</span><span class="text-sm font-bold text-amber-600">{{ $mo['failures'] }}</span></div>
                </div>
            </div>
            @endforeach
        </div>
    </div>

    {{-- 评级参考 --}}
    <div class="bg-white dark:bg-zinc-900 rounded-2xl border border-zinc-200 dark:border-zinc-800 p-5">
        <h2 class="text-sm font-semibold text-zinc-900 dark:text-white mb-3">DORA 能力评级参考</h2>
        <div class="overflow-x-auto">
        <table class="w-full text-xs">
            <thead><tr class="text-left text-zinc-500"><th class="py-2 pr-4">指标</th><th class="py-2 px-3">精英</th><th class="py-2 px-3">高绩效</th><th class="py-2 px-3">中等</th><th class="py-2 px-3">低绩效</th></tr></thead>
            <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                <tr><td class="py-2 pr-4 font-medium">部署频率</td><td class="px-3">按需(每日多次)</td><td class="px-3">每日1次~每周1次</td><td class="px-3">每周1次~每月1次</td><td class="px-3">每月1次~半年1次</td></tr>
                <tr><td class="py-2 pr-4 font-medium">交付周期</td><td class="px-3">&lt; 1 小时</td><td class="px-3">1天~1周</td><td class="px-3">1周~1月</td><td class="px-3">1月~6月</td></tr>
                <tr><td class="py-2 pr-4 font-medium">MTTR</td><td class="px-3">&lt; 1 小时</td><td class="px-3">&lt; 1 天</td><td class="px-3">1天~1周</td><td class="px-3">&gt; 1 周</td></tr>
                <tr><td class="py-2 pr-4 font-medium">变更失败率</td><td class="px-3">0-5%</td><td class="px-3">5-10%</td><td class="px-3">10-15%</td><td class="px-3">15-30%</td></tr>
            </tbody>
        </table>
        </div>
    </div>
</div>
